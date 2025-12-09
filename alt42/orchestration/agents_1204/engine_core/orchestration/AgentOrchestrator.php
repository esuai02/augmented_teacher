<?php
/**
 * AgentOrchestrator.php
 *
 * 22개 에이전트 오케스트레이션 메인 컨트롤러
 * AgentDependencyManager를 활용하여 Phase별 순차 실행 및 의존성 검증
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore/Orchestration
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 핵심 모듈 로드
require_once(__DIR__ . '/AgentDependencyManager.php');
require_once(__DIR__ . '/../errors/AgentErrorHandler.php');
require_once(__DIR__ . '/../validation/DataSourceValidator.php');

/**
 * 에이전트 실행 상태 정의
 */
class AgentStatus {
    const PENDING   = 'pending';
    const RUNNING   = 'running';
    const COMPLETED = 'completed';
    const FAILED    = 'failed';
    const SKIPPED   = 'skipped';
}

/**
 * 에이전트 오케스트레이터
 */
class AgentOrchestrator {

    /**
     * @var int 학생 ID
     */
    private $studentId;

    /**
     * @var AgentDependencyManager 의존성 관리자
     */
    private $dependencyManager;

    /**
     * @var AgentErrorHandler 에러 핸들러
     */
    private $errorHandler;

    /**
     * @var string 세션 ID
     */
    private $sessionId;

    /**
     * @var array 실행 결과 로그
     */
    private $executionLog = [];

    /**
     * @var array 에이전트 설정 맵
     */
    private $agentConfig = [
        1  => ['name' => 'Onboarding', 'folder' => 'agent01_onboarding', 'timeout' => 30],
        2  => ['name' => 'ExamSchedule', 'folder' => 'agent02_exam_schedule', 'timeout' => 20],
        3  => ['name' => 'GoalsAnalysis', 'folder' => 'agent03_goals_analysis', 'timeout' => 25],
        4  => ['name' => 'InspectWeakpoints', 'folder' => 'agent04_inspect_weakpoints', 'timeout' => 30],
        5  => ['name' => 'LearningEmotion', 'folder' => 'agent05_learning_emotion', 'timeout' => 20],
        6  => ['name' => 'TeacherFeedback', 'folder' => 'agent06_teacher_feedback', 'timeout' => 15],
        7  => ['name' => 'InteractionTargeting', 'folder' => 'agent07_interaction_targeting', 'timeout' => 25],
        8  => ['name' => 'Calmness', 'folder' => 'agent08_calmness', 'timeout' => 20],
        9  => ['name' => 'LearningManagement', 'folder' => 'agent09_learning_management', 'timeout' => 25],
        10 => ['name' => 'ConceptNotes', 'folder' => 'agent10_concept_notes', 'timeout' => 30],
        11 => ['name' => 'ProblemNotes', 'folder' => 'agent11_problem_notes', 'timeout' => 30],
        12 => ['name' => 'RestRoutine', 'folder' => 'agent12_rest_routine', 'timeout' => 15],
        13 => ['name' => 'LearningDropout', 'folder' => 'agent13_learning_dropout', 'timeout' => 20],
        14 => ['name' => 'CurrentPosition', 'folder' => 'agent14_current_position', 'timeout' => 25],
        15 => ['name' => 'ProblemRedefinition', 'folder' => 'agent15_problem_redefinition', 'timeout' => 30],
        16 => ['name' => 'InteractionPreparation', 'folder' => 'agent16_interaction_preparation', 'timeout' => 25],
        17 => ['name' => 'RemainingActivities', 'folder' => 'agent17_remaining_activities', 'timeout' => 20],
        18 => ['name' => 'SignatureRoutine', 'folder' => 'agent18_signature_routine', 'timeout' => 20],
        19 => ['name' => 'InteractionContent', 'folder' => 'agent19_interaction_content', 'timeout' => 30],
        20 => ['name' => 'InterventionPreparation', 'folder' => 'agent20_intervention_preparation', 'timeout' => 30],
        21 => ['name' => 'InterventionExecution', 'folder' => 'agent21_intervention_execution', 'timeout' => 45],
        22 => ['name' => 'ModuleImprovement', 'folder' => 'agent22_module_improvement', 'timeout' => 30],
    ];

    /**
     * 생성자
     *
     * @param int $studentId 학생 ID
     */
    public function __construct(int $studentId) {
        $this->studentId = $studentId;
        $this->sessionId = uniqid('session_', true);
        $this->dependencyManager = new AgentDependencyManager($studentId);
        $this->errorHandler = new AgentErrorHandler('Orchestrator');
    }

    /**
     * 전체 파이프라인 실행
     *
     * @param array $options 실행 옵션
     *        - 'phase': 특정 Phase만 실행 (1-4)
     *        - 'agents': 특정 에이전트만 실행 (배열)
     *        - 'force': 의존성 무시하고 강제 실행
     *        - 'dry_run': 실제 실행 없이 계획만 반환
     * @return array 실행 결과
     */
    public function executePipeline(array $options = []): array {
        $startTime = microtime(true);

        $result = [
            'session_id' => $this->sessionId,
            'student_id' => $this->studentId,
            'success' => false,
            'executed' => [],
            'skipped' => [],
            'failed' => [],
            'phase_progress' => [],
            'performance' => [],
            'errors' => []
        ];

        try {
            // 세션 시작 기록
            $this->logSessionStart($options);

            // 실행할 에이전트 결정
            $agentsToExecute = $this->determineAgentsToExecute($options);

            if ($options['dry_run'] ?? false) {
                $result['dry_run'] = true;
                $result['planned_agents'] = $agentsToExecute;
                $result['success'] = true;
                return $result;
            }

            // 순차 실행
            foreach ($agentsToExecute as $agentId) {
                $agentResult = $this->executeAgent($agentId, $options);

                if ($agentResult['status'] === AgentStatus::COMPLETED) {
                    $result['executed'][] = $agentResult;
                    $this->dependencyManager->markCompleted($agentId, [
                        'session_id' => $this->sessionId,
                        'duration_ms' => $agentResult['duration_ms']
                    ]);
                } else if ($agentResult['status'] === AgentStatus::SKIPPED) {
                    $result['skipped'][] = $agentResult;
                } else if ($agentResult['status'] === AgentStatus::FAILED) {
                    $result['failed'][] = $agentResult;
                    $result['errors'][] = $agentResult['error'];

                    // 실패 시 후속 에이전트 스킵 여부 결정
                    if (!($options['continue_on_failure'] ?? false)) {
                        $this->errorHandler->log(
                            "Pipeline halted due to Agent {$agentId} failure",
                            ErrorSeverity::WARNING,
                            ['agent_id' => $agentId, 'error' => $agentResult['error']]
                        );
                        break;
                    }
                }
            }

            // Phase 진행 상태
            $result['phase_progress'] = $this->dependencyManager->getPhaseProgress();

            // 성공 여부 결정
            $result['success'] = empty($result['failed']);

        } catch (Exception $e) {
            $result['errors'][] = AgentErrorHandler::handle($e, 'Orchestrator', 'executePipeline');
        }

        // 성능 통계
        $endTime = microtime(true);
        $result['performance'] = [
            'total_duration_ms' => round(($endTime - $startTime) * 1000, 2),
            'agents_executed' => count($result['executed']),
            'agents_skipped' => count($result['skipped']),
            'agents_failed' => count($result['failed'])
        ];

        // 세션 종료 기록
        $this->logSessionEnd($result);

        return $result;
    }

    /**
     * 단일 에이전트 실행
     *
     * @param int $agentId 에이전트 번호
     * @param array $options 실행 옵션
     * @return array 실행 결과
     */
    public function executeAgent(int $agentId, array $options = []): array {
        $startTime = microtime(true);

        $result = [
            'agent_id' => $agentId,
            'agent_name' => $this->agentConfig[$agentId]['name'] ?? "Agent{$agentId}",
            'status' => AgentStatus::PENDING,
            'started_at' => date('Y-m-d H:i:s'),
            'output' => null,
            'error' => null,
            'duration_ms' => 0
        ];

        // 의존성 확인 (강제 실행이 아닌 경우)
        if (!($options['force'] ?? false)) {
            $canExecute = $this->dependencyManager->canExecute($agentId);

            if (!$canExecute['can_execute']) {
                $result['status'] = AgentStatus::SKIPPED;
                $result['error'] = $canExecute['reason'];
                $result['missing_dependencies'] = $canExecute['missing'];
                return $result;
            }
        }

        // 실행 상태 업데이트
        $result['status'] = AgentStatus::RUNNING;
        $this->updateExecutionLog($agentId, AgentStatus::RUNNING);

        try {
            // 에이전트 실행
            $agentOutput = $this->invokeAgent($agentId, $options);

            if ($agentOutput['success']) {
                $result['status'] = AgentStatus::COMPLETED;
                $result['output'] = $agentOutput['data'];
            } else {
                $result['status'] = AgentStatus::FAILED;
                $result['error'] = $agentOutput['error'] ?? 'Unknown error';
            }

        } catch (Exception $e) {
            $result['status'] = AgentStatus::FAILED;
            $result['error'] = $e->getMessage();

            $this->errorHandler->log(
                "Agent {$agentId} execution failed: " . $e->getMessage(),
                ErrorSeverity::ERROR,
                ['agent_id' => $agentId, 'file' => $e->getFile(), 'line' => $e->getLine()]
            );
        }

        $endTime = microtime(true);
        $result['duration_ms'] = round(($endTime - $startTime) * 1000, 2);
        $result['completed_at'] = date('Y-m-d H:i:s');

        // 실행 로그 업데이트
        $this->updateExecutionLog($agentId, $result['status'], $result);

        return $result;
    }

    /**
     * 실제 에이전트 호출
     *
     * @param int $agentId 에이전트 번호
     * @param array $options 옵션
     * @return array
     */
    private function invokeAgent(int $agentId, array $options): array {
        $config = $this->agentConfig[$agentId] ?? null;

        if (!$config) {
            return ['success' => false, 'error' => "Agent {$agentId} not configured"];
        }

        // 에이전트의 data_access.php 경로 확인
        $agentBasePath = dirname(__DIR__, 2) . '/' . $config['folder'];
        $dataAccessPath = $agentBasePath . '/rules/data_access.php';

        if (!file_exists($dataAccessPath)) {
            return ['success' => false, 'error' => "Agent data_access.php not found: {$dataAccessPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]"];
        }

        try {
            // 에이전트의 컨텍스트 데이터 수집 함수 호출
            require_once($dataAccessPath);

            // 표준 함수명 패턴: get{AgentName}Context 또는 prepare{AgentName}Context
            $functionNames = [
                'prepareRuleContext',  // 표준 패턴
                'get' . $config['name'] . 'Context',
                'prepare' . $config['name'] . 'Context',
            ];

            $contextData = null;
            foreach ($functionNames as $funcName) {
                if (function_exists($funcName)) {
                    $contextData = call_user_func($funcName, $this->studentId);
                    break;
                }
            }

            if ($contextData === null) {
                // 기본 컨텍스트 반환
                $contextData = [
                    'student_id' => $this->studentId,
                    'agent_id' => $agentId,
                    'timestamp' => date('Y-m-d\TH:i:s\Z'),
                    'warning' => 'No standard context function found'
                ];
            }

            // 검증 결과 확인
            if (isset($contextData['validation_status']) && !$contextData['validation_status']['success']) {
                return [
                    'success' => false,
                    'error' => 'Data validation failed: ' . json_encode($contextData['validation_status']['missing']),
                    'data' => $contextData
                ];
            }

            return [
                'success' => true,
                'data' => $contextData
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage() . " [File: " . $e->getFile() . ", Line: " . $e->getLine() . "]"
            ];
        }
    }

    /**
     * 실행할 에이전트 결정
     *
     * @param array $options 옵션
     * @return array 에이전트 번호 배열
     */
    private function determineAgentsToExecute(array $options): array {
        // 특정 에이전트 지정
        if (!empty($options['agents'])) {
            return array_map('intval', (array)$options['agents']);
        }

        // 특정 Phase 지정
        if (!empty($options['phase'])) {
            $phases = $this->dependencyManager->getPhaseProgress();
            $phase = (int)$options['phase'];
            return $phases[$phase]['agents'] ?? [];
        }

        // 기본: 실행 가능한 모든 에이전트
        return $this->dependencyManager->getExecutableAgents();
    }

    /**
     * 세션 시작 로그
     */
    private function logSessionStart(array $options): void {
        global $DB;

        try {
            $dbman = $DB->get_manager();
            if ($dbman->table_exists(new xmldb_table('at_agent_execution_log'))) {
                $record = new stdClass();
                $record->session_id = $this->sessionId;
                $record->student_id = $this->studentId;
                $record->agent_id = 0;  // 0 = orchestrator
                $record->status = 'started';
                $record->result_data = json_encode(['options' => $options]);
                $record->started_at = time();
                $record->created_at = time();

                $DB->insert_record('at_agent_execution_log', $record);
            }
        } catch (Exception $e) {
            error_log("[AgentOrchestrator] logSessionStart error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    /**
     * 세션 종료 로그
     */
    private function logSessionEnd(array $result): void {
        global $DB;

        try {
            $dbman = $DB->get_manager();
            if ($dbman->table_exists(new xmldb_table('at_agent_execution_log'))) {
                $DB->execute(
                    "UPDATE {at_agent_execution_log}
                     SET status = ?, result_data = ?, completed_at = ?
                     WHERE session_id = ? AND agent_id = 0",
                    [
                        $result['success'] ? 'completed' : 'failed',
                        json_encode($result['performance']),
                        time(),
                        $this->sessionId
                    ]
                );
            }
        } catch (Exception $e) {
            error_log("[AgentOrchestrator] logSessionEnd error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    /**
     * 실행 로그 업데이트
     */
    private function updateExecutionLog(int $agentId, string $status, array $data = []): void {
        global $DB;

        try {
            $dbman = $DB->get_manager();
            if ($dbman->table_exists(new xmldb_table('at_agent_execution_log'))) {
                $record = new stdClass();
                $record->session_id = $this->sessionId;
                $record->student_id = $this->studentId;
                $record->agent_id = $agentId;
                $record->status = $status;
                $record->result_data = json_encode($data);
                $record->started_at = $status === AgentStatus::RUNNING ? time() : null;
                $record->completed_at = in_array($status, [AgentStatus::COMPLETED, AgentStatus::FAILED, AgentStatus::SKIPPED]) ? time() : null;
                $record->created_at = time();

                $DB->insert_record('at_agent_execution_log', $record);
            }
        } catch (Exception $e) {
            error_log("[AgentOrchestrator] updateExecutionLog error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    /**
     * 현재 상태 조회
     *
     * @return array 상태 정보
     */
    public function getStatus(): array {
        return [
            'session_id' => $this->sessionId,
            'student_id' => $this->studentId,
            'completed_agents' => $this->dependencyManager->getCompletedAgents(),
            'executable_agents' => $this->dependencyManager->getExecutableAgents(),
            'next_recommended' => $this->dependencyManager->getNextRecommendedAgent(),
            'phase_progress' => $this->dependencyManager->getPhaseProgress(),
            'dependency_graph' => $this->dependencyManager->visualizeDependencies()
        ];
    }

    /**
     * 세션 ID 반환
     */
    public function getSessionId(): string {
        return $this->sessionId;
    }
}

// =============================================================================
// API 엔드포인트 처리
// =============================================================================

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'] ?? $_POST['action'] ?? 'status';
    $studentId = (int)($_GET['student_id'] ?? $_POST['student_id'] ?? $USER->id ?? 0);

    if ($studentId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid student_id', 'file' => __FILE__, 'line' => __LINE__]);
        exit;
    }

    $orchestrator = new AgentOrchestrator($studentId);

    switch ($action) {
        case 'execute':
            $options = [
                'phase' => $_GET['phase'] ?? $_POST['phase'] ?? null,
                'agents' => isset($_GET['agents']) ? explode(',', $_GET['agents']) : (isset($_POST['agents']) ? $_POST['agents'] : null),
                'force' => (bool)($_GET['force'] ?? $_POST['force'] ?? false),
                'dry_run' => (bool)($_GET['dry_run'] ?? $_POST['dry_run'] ?? false),
                'continue_on_failure' => (bool)($_GET['continue_on_failure'] ?? $_POST['continue_on_failure'] ?? false),
            ];
            $result = $orchestrator->executePipeline($options);
            break;

        case 'execute_agent':
            $agentId = (int)($_GET['agent_id'] ?? $_POST['agent_id'] ?? 0);
            if ($agentId <= 0 || $agentId > 22) {
                echo json_encode(['success' => false, 'error' => 'Invalid agent_id (must be 1-22)', 'file' => __FILE__, 'line' => __LINE__]);
                exit;
            }
            $result = $orchestrator->executeAgent($agentId, [
                'force' => (bool)($_GET['force'] ?? $_POST['force'] ?? false)
            ]);
            break;

        case 'status':
        default:
            $result = $orchestrator->getStatus();
            break;
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // API 호출 예시
 *
 * // 1. 현재 상태 조회
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=status&student_id=123
 *
 * // 2. 전체 파이프라인 실행
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=execute&student_id=123
 *
 * // 3. 특정 Phase만 실행
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=execute&student_id=123&phase=1
 *
 * // 4. 특정 에이전트만 실행
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=execute&student_id=123&agents=1,2,3
 *
 * // 5. 단일 에이전트 실행
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=execute_agent&student_id=123&agent_id=4
 *
 * // 6. Dry Run (실행 계획만 확인)
 * GET /orchestration/agents_1204/engine_core/orchestration/AgentOrchestrator.php?action=execute&student_id=123&dry_run=1
 *
 * // PHP 코드에서 사용
 * require_once(__DIR__ . '/engine_core/orchestration/AgentOrchestrator.php');
 *
 * $orchestrator = new AgentOrchestrator($USER->id);
 *
 * // 상태 확인
 * $status = $orchestrator->getStatus();
 *
 * // Phase 1만 실행
 * $result = $orchestrator->executePipeline(['phase' => 1]);
 *
 * // 특정 에이전트 실행
 * $result = $orchestrator->executeAgent(4);
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 참조 테이블: mdl_at_agent_execution_log
 *
 * 필드:
 * - id (int): PK
 * - session_id (varchar 64): 실행 세션 ID
 * - student_id (int): 학생 ID
 * - agent_id (int): 에이전트 번호 (1-22, 0=orchestrator)
 * - status (varchar 20): pending, running, completed, failed, skipped, started
 * - result_data (text): JSON 형식 실행 결과
 * - error_message (text): 에러 메시지
 * - started_at (int): 시작 시간 (timestamp)
 * - completed_at (int): 완료 시간 (timestamp)
 * - created_at (int): 생성 시간 (timestamp)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
