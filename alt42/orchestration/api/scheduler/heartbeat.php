<?php
/**
 * Heartbeat Scheduler
 * 30분 주기 학생 상태 재평가
 * 시나리오 그룹 단위 평가 (주기적 체크)
 * 
 * @package ALT42\Scheduler
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Standalone mode (Moodle 독립)
// Moodle config 체크는 하되, 없어도 동작하도록
$moodle_available = file_exists('/home/moodle/public_html/moodle/config.php');
if ($moodle_available) {
    require_once('/home/moodle/public_html/moodle/config.php');
}

// Include dependencies
require_once(__DIR__ . '/../events/event_bus.php');
require_once(__DIR__ . '/../database/agent_data_layer.php');
require_once(__DIR__ . '/../mapping/event_scenario_mapper.php');
require_once(__DIR__ . '/../oa/route.php');
require_once(__DIR__ . '/../config/event_schemas.php');
require_once(__DIR__ . '/../rule_engine/rule_evaluator.php');

use ALT42\Events\EventBus;
use ALT42\Database\AgentDataLayer;
use ALT42\Mapping\EventScenarioMapper;
use ALT42\OA\OrchestratorRouter;
use ALT42\Config\EventSchemas;
use ALT42\RuleEngine\RuleEvaluator;

class HeartbeatScheduler {
    private $eventBus;
    private $dataLayer;
    private $mapper;
    private $router;
    private $ruleEvaluator;
    private $intervalMinutes = 30;
    
    public function __construct() {
        $this->eventBus = new EventBus();
        $this->dataLayer = new AgentDataLayer();
        $this->mapper = new EventScenarioMapper();
        $this->router = new OrchestratorRouter();
        $this->ruleEvaluator = new RuleEvaluator();
    }
    
    /**
     * Execute heartbeat for all active students
     * 
     * @return array Execution result
     */
    public function execute(): array {
        $startTime = microtime(true);
        $timestamp = date('c');
        
        $this->log("Starting heartbeat scheduler at {$timestamp}");
        
        try {
            // Get active students (학습 중인 학생들)
            $activeStudents = $this->getActiveStudents();
            
            $this->log("Found " . count($activeStudents) . " active students");
            
            $processed = 0;
            $errors = 0;
            $results = [];
            
            foreach ($activeStudents as $student) {
                try {
                    $result = $this->processStudentHeartbeat($student['student_id'], $timestamp);
                    $results[$student['student_id']] = $result;
                    $processed++;
                } catch (\Exception $e) {
                    $errorMsg = "Heartbeat error for student {$student['student_id']}: " . $e->getMessage();
                    error_log($errorMsg . " at " . __FILE__ . ":" . __LINE__);
                    $errors++;
                    $results[$student['student_id']] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->log("Completed: {$processed} processed, {$errors} errors, {$duration}ms");
            
            // Log heartbeat execution
            $this->logHeartbeatExecution([
                'timestamp' => $timestamp,
                'students_processed' => $processed,
                'errors' => $errors,
                'duration_ms' => $duration
            ]);
            
            return [
                'success' => true,
                'timestamp' => $timestamp,
                'students_processed' => $processed,
                'errors' => $errors,
                'duration_ms' => $duration,
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            $errorMsg = "Heartbeat scheduler fatal error: " . $e->getMessage();
            error_log($errorMsg . " at " . __FILE__ . ":" . __LINE__);
            throw $e;
        }
    }
    
    /**
     * Get active students (학습 중인 학생들)
     * 최근 24시간 내 활동이 있는 학생들 조회
     * 
     * @return array Active students list
     */
    private function getActiveStudents(): array {
        try {
            // 최근 24시간 내 세션이 있거나 진행 중인 학생들
            $sql = "
                SELECT DISTINCT student_id 
                FROM mdl_alt42_learning_sessions 
                WHERE (session_end IS NULL OR session_end >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
                   AND student_id IS NOT NULL
                ORDER BY student_id
                LIMIT 1000
            ";
            
            $stmt = AgentDataLayer::executeQuery($sql);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 테이블이 없을 경우를 대비한 fallback
            if (empty($students)) {
                // 대안: 다른 테이블에서 활성 학생 조회
                $sql = "
                    SELECT DISTINCT student_id 
                    FROM mdl_alt42_student_activity 
                    WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      AND student_id IS NOT NULL
                    ORDER BY student_id
                    LIMIT 1000
                ";
                
                try {
                    $stmt = AgentDataLayer::executeQuery($sql);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (\Exception $e) {
                    // 테이블이 없으면 빈 배열 반환
                    $this->log("Warning: Could not find active students table. Returning empty array.");
                    return [];
                }
            }
            
            return $students;
            
        } catch (\Exception $e) {
            error_log("Error getting active students: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return [];
        }
    }
    
    /**
     * Process heartbeat for a single student
     * 시나리오 그룹 단위 평가 수행
     * 
     * @param string $studentId Student ID
     * @param string $timestamp Timestamp
     * @return array Processing result
     */
    private function processStudentHeartbeat(string $studentId, string $timestamp): array {
        // Get student current state
        $studentState = $this->getStudentState($studentId);
        
        // Create heartbeat event
        $heartbeatEvent = [
            'topic' => 'cron.heartbeat_30m',
            'type' => 'periodic',
            'student_id' => $studentId,
            'interval_minutes' => $this->intervalMinutes,
            'timestamp' => $timestamp,
            'student_state' => $studentState
        ];
        
        // Validate event schema (static method call)
        $validation = EventSchemas::validateEvent('cron.heartbeat_30m', $heartbeatEvent);
        if (!$validation['valid']) {
            throw new \Exception("Heartbeat event validation failed: " . implode(', ', $validation['errors']) . " at " . __FILE__ . ":" . __LINE__);
        }
        
        // Get scenarios for heartbeat (전체 시나리오 평가)
        $eventConfig = $this->mapper->getScenariosForEvent('cron.heartbeat_30m');
        
        $scenarioResults = [];
        
        // Evaluate scenarios (시나리오 그룹 단위)
        foreach ($eventConfig['scenarios'] as $scenarioId) {
            try {
                $scenarioResult = $this->evaluateScenario($scenarioId, $studentId, $studentState);
                $scenarioResults[$scenarioId] = $scenarioResult;
            } catch (\Exception $e) {
                error_log("Scenario evaluation error for {$scenarioId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
                $scenarioResults[$scenarioId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Publish heartbeat event to event bus
        $this->eventBus->publish(
            'cron.heartbeat_30m',
            $heartbeatEvent,
            $eventConfig['priority'] ?? 2
        );
        
        // Route through OA Router (기존 라우팅 규칙 활용)
        $routingResult = $this->router->route($heartbeatEvent);
        
        return [
            'success' => true,
            'student_id' => $studentId,
            'scenarios_evaluated' => count($scenarioResults),
            'scenario_results' => $scenarioResults,
            'routing_result' => $routingResult
        ];
    }
    
    /**
     * Get student current state
     * 
     * @param string $studentId Student ID
     * @return array Student state
     */
    private function getStudentState(string $studentId): array {
        try {
            // v_student_state 뷰가 있다면 사용, 없으면 기본 정보만 조회
            $sql = "
                SELECT 
                    student_id,
                    emotion_state,
                    immersion_level,
                    stress_level,
                    concentration_level,
                    engagement_score,
                    math_confidence,
                    updated_at
                FROM mdl_alt42_v_student_state
                WHERE student_id = ?
                LIMIT 1
            ";
            
            $stmt = AgentDataLayer::executeQuery($sql, [$studentId]);
            $state = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($state) {
                return $state;
            }
            
            // Fallback: 기본 학생 정보만 조회
            $sql = "SELECT id as student_id FROM mdl_user WHERE id = ? LIMIT 1";
            $stmt = AgentDataLayer::executeQuery($sql, [$studentId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: ['student_id' => $studentId];
            
        } catch (\Exception $e) {
            error_log("Error getting student state for {$studentId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return ['student_id' => $studentId];
        }
    }
    
    /**
     * Evaluate scenario group for student
     * 시나리오 그룹 단위 평가 (주기적 체크)
     * 
     * @param string $scenarioId Scenario ID (e.g., 'S1')
     * @param string $studentId Student ID
     * @param array $studentState Student state
     * @return array Evaluation result
     */
    private function evaluateScenario(string $scenarioId, string $studentId, array $studentState): array {
        $scenarioConfig = $this->mapper->getRulesForScenario($scenarioId);
        $rules = $scenarioConfig['rules'] ?? [];
        $evaluationMode = $scenarioConfig['evaluation_mode'] ?? 'priority_first';
        
        $this->log("  Evaluating scenario {$scenarioId} for student {$studentId} (" . count($rules) . " rules)");
        
        // 룰 엔진을 통한 시나리오 그룹 평가
        $context = array_merge($studentState, ['student_id' => $studentId]);
        $result = $this->ruleEvaluator->evaluateScenario(
            $scenarioId,
            $rules,
            $context,
            $evaluationMode
        );
        
        // 로그 저장
        $this->logScenarioEvaluation($studentId, $scenarioId, $result);
        
        return $result;
    }
    
    /**
     * Log scenario evaluation
     * 
     * @param string $studentId Student ID
     * @param string $scenarioId Scenario ID
     * @param array $result Evaluation result
     */
    private function logScenarioEvaluation(string $studentId, string $scenarioId, array $result): void {
        try {
            $sql = "
                INSERT INTO mdl_alt42_scenario_evaluation_log 
                (scenario_id, student_id, rules_count, evaluation_mode, evaluation_result, evaluated_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            
            AgentDataLayer::executeQuery($sql, [
                $scenarioId,
                $studentId,
                $result['rules_evaluated'] ?? 0,
                $result['evaluation_mode'] ?? 'priority_first',
                json_encode($result)
            ]);
        } catch (\Exception $e) {
            // 테이블이 없으면 로그만 기록
            error_log("Could not log scenario evaluation: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
        }
    }
    
    /**
     * Log heartbeat execution to database
     * 
     * @param array $data Execution data
     */
    private function logHeartbeatExecution(array $data): void {
        try {
            // ISO 8601 형식의 timestamp를 MySQL TIMESTAMP로 변환
            $execution_time = date('Y-m-d H:i:s', strtotime($data['timestamp']));
            
            // 테이블이 없으면 생성 시도 (실패해도 계속 진행)
            $sql = "
                INSERT INTO mdl_alt42_heartbeat_log 
                (execution_time, students_processed, errors, duration_ms, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            AgentDataLayer::executeQuery($sql, [
                $execution_time,
                $data['students_processed'],
                $data['errors'],
                $data['duration_ms']
            ]);
        } catch (\Exception $e) {
            // 테이블이 없으면 로그만 기록
            error_log("Could not log heartbeat execution: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
        }
    }
    
    /**
     * Log message (CLI or file)
     * 
     * @param string $message Log message
     */
    private function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        } else {
            error_log($logMessage);
        }
    }
}

// Execution
try {
    $scheduler = new HeartbeatScheduler();
    
    if (php_sapi_name() === 'cli') {
        // CLI 실행 모드
        $result = $scheduler->execute();
        exit($result['errors'] > 0 ? 1 : 0);
    } else {
        // Web 호출 시 (수동 실행용)
        header('Content-Type: application/json');
        $result = $scheduler->execute();
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
} catch (\Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    if (php_sapi_name() === 'cli') {
        echo json_encode($error, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode($error, JSON_PRETTY_PRINT);
    }
}

