<?php
// File: mvp_system/orchestrator.php (Line 1)
// Mathking Agentic MVP System - Pipeline Orchestrator
//
// Purpose: Coordinate Sensing → Decision → Execution pipeline
// Input: Student ID + optional activity data
// Output: Complete intervention result with SLA tracking
// SLA Target: < 3 minutes (180 seconds) for complete pipeline

// Load dependencies
require_once(__DIR__ . '/config/app.config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/logger.php');
require_once(__DIR__ . '/execution/intervention_dispatcher.php');

// =============================================================================
// Agent Dependency Manager
// =============================================================================
// Purpose: Enforce agent execution order based on dependency graph
// 22개 에이전트 간의 의존성을 관리하고 실행 순서를 강제합니다.
// =============================================================================

class AgentDependencyManager
{
    /**
     * Agent Dependency Graph
     *
     * Key: Agent ID (number)
     * Value: Array of required agent IDs that must complete before this agent
     *
     * Phase 1 (Foundation): Agents 01-06
     * Phase 2 (Analysis): Agents 07-13
     * Phase 3 (Planning): Agents 14-19
     * Phase 4 (Execution): Agents 20-22
     */
    private static $dependencyGraph = [
        // Phase 1: Foundation (대부분 독립적)
        1 => [],                    // Agent 01: 온보딩 - 최초 실행
        2 => [1],                   // Agent 02: 시험일정 - 온보딩 후
        3 => [1],                   // Agent 03: 목표분석 - 온보딩 후
        4 => [1, 3],                // Agent 04: 약점검사 - 온보딩, 목표분석 후
        5 => [1],                   // Agent 05: 학습감정 - 온보딩 후
        6 => [4, 5],                // Agent 06: 교사피드백 - 약점검사, 감정분석 후

        // Phase 2: Analysis (Foundation 의존)
        7 => [4],                   // Agent 07: 성과예측 - 약점검사 후
        8 => [3, 4],                // Agent 08: 난이도조절 - 목표, 약점 후
        9 => [4, 7],                // Agent 09: 단원예측 - 약점, 성과예측 후
        10 => [4, 8],               // Agent 10: 개념노트 - 약점, 난이도 후
        11 => [5, 6],               // Agent 11: 멘탈케어 - 감정, 피드백 후
        12 => [7, 9],               // Agent 12: 학습진도 - 성과예측, 단원예측 후
        13 => [10, 11],             // Agent 13: 동기부여 - 개념노트, 멘탈케어 후

        // Phase 3: Planning (Analysis 의존)
        14 => [8, 12],              // Agent 14: 콘텐츠추천 - 난이도, 진도 후
        15 => [12, 13],             // Agent 15: 루틴설계 - 진도, 동기부여 후
        16 => [14, 15],             // Agent 16: 상호작용준비 - 콘텐츠, 루틴 후
        17 => [12, 15],             // Agent 17: 잔여활동 - 진도, 루틴 후
        18 => [16],                 // Agent 18: 메시지생성 - 상호작용준비 후
        19 => [16, 18],             // Agent 19: 상호작용콘텐츠 - 준비, 메시지 후

        // Phase 4: Execution (Planning 의존)
        20 => [16, 17, 19],         // Agent 20: 개입준비 - 상호작용, 잔여활동 후
        21 => [19, 20],             // Agent 21: 개입실행 - 콘텐츠, 준비 후
        22 => [21]                  // Agent 22: 피드백수집 - 개입실행 후
    ];

    /**
     * Phase definitions for agents
     */
    private static $phases = [
        1 => [1, 2, 3, 4, 5, 6],        // Phase 1: Foundation
        2 => [7, 8, 9, 10, 11, 12, 13], // Phase 2: Analysis
        3 => [14, 15, 16, 17, 18, 19],  // Phase 3: Planning
        4 => [20, 21, 22]               // Phase 4: Execution
    ];

    /** @var array 완료된 에이전트 목록 */
    private $completedAgents = [];

    /** @var array 현재 실행 중인 에이전트 */
    private $runningAgents = [];

    /** @var MVPLogger 로거 */
    private $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = new MVPLogger('dependency_manager');
    }

    /**
     * 에이전트 실행 가능 여부 확인
     *
     * @param int $agentId 에이전트 ID (1-22)
     * @return bool 실행 가능 여부
     */
    public function canExecute(int $agentId): bool
    {
        if (!$this->isValidAgentId($agentId)) {
            $this->logger->error("Invalid agent ID: {$agentId} at " . __FILE__ . ":" . __LINE__);
            return false;
        }

        $dependencies = self::$dependencyGraph[$agentId] ?? [];

        // 의존성이 없으면 바로 실행 가능
        if (empty($dependencies)) {
            return true;
        }

        // 모든 의존 에이전트가 완료되었는지 확인
        foreach ($dependencies as $requiredAgent) {
            if (!in_array($requiredAgent, $this->completedAgents)) {
                $this->logger->debug("Agent {$agentId} blocked: waiting for agent {$requiredAgent}");
                return false;
            }
        }

        return true;
    }

    /**
     * 에이전트 실행 가능 여부 확인 (상세 정보 포함)
     *
     * @param int $agentId 에이전트 ID
     * @return array 실행 가능 여부 및 상세 정보
     */
    public function checkExecutionStatus(int $agentId): array
    {
        $dependencies = self::$dependencyGraph[$agentId] ?? [];
        $missingDependencies = [];
        $completedDependencies = [];

        foreach ($dependencies as $dep) {
            if (in_array($dep, $this->completedAgents)) {
                $completedDependencies[] = $dep;
            } else {
                $missingDependencies[] = $dep;
            }
        }

        return [
            'agent_id' => $agentId,
            'can_execute' => empty($missingDependencies),
            'phase' => $this->getAgentPhase($agentId),
            'dependencies' => [
                'required' => $dependencies,
                'completed' => $completedDependencies,
                'missing' => $missingDependencies
            ],
            'completion_percentage' => count($dependencies) > 0
                ? round((count($completedDependencies) / count($dependencies)) * 100, 1)
                : 100
        ];
    }

    /**
     * 에이전트 완료 등록
     *
     * @param int $agentId 완료된 에이전트 ID
     * @return bool 등록 성공 여부
     */
    public function markCompleted(int $agentId): bool
    {
        if (!$this->isValidAgentId($agentId)) {
            return false;
        }

        if (!in_array($agentId, $this->completedAgents)) {
            $this->completedAgents[] = $agentId;
            $this->logger->info("Agent {$agentId} marked as completed");

            // 실행 중 목록에서 제거
            $this->runningAgents = array_diff($this->runningAgents, [$agentId]);
        }

        return true;
    }

    /**
     * 에이전트 실행 시작 등록
     *
     * @param int $agentId 시작하는 에이전트 ID
     * @return bool 등록 성공 여부
     */
    public function markStarted(int $agentId): bool
    {
        if (!$this->canExecute($agentId)) {
            $this->logger->warning("Cannot start agent {$agentId}: dependencies not met at " . __FILE__ . ":" . __LINE__);
            return false;
        }

        if (!in_array($agentId, $this->runningAgents)) {
            $this->runningAgents[] = $agentId;
            $this->logger->info("Agent {$agentId} started");
        }

        return true;
    }

    /**
     * 다음 실행 가능한 에이전트 목록 조회
     *
     * @return array 실행 가능한 에이전트 ID 배열
     */
    public function getExecutableAgents(): array
    {
        $executable = [];

        for ($agentId = 1; $agentId <= 22; $agentId++) {
            // 이미 완료되었거나 실행 중인 에이전트는 제외
            if (in_array($agentId, $this->completedAgents) || in_array($agentId, $this->runningAgents)) {
                continue;
            }

            if ($this->canExecute($agentId)) {
                $executable[] = $agentId;
            }
        }

        return $executable;
    }

    /**
     * 특정 Phase의 에이전트 완료 상태 확인
     *
     * @param int $phase Phase 번호 (1-4)
     * @return array Phase 상태 정보
     */
    public function getPhaseStatus(int $phase): array
    {
        $phaseAgents = self::$phases[$phase] ?? [];
        $completed = array_intersect($phaseAgents, $this->completedAgents);
        $running = array_intersect($phaseAgents, $this->runningAgents);
        $pending = array_diff($phaseAgents, $this->completedAgents, $this->runningAgents);

        return [
            'phase' => $phase,
            'total_agents' => count($phaseAgents),
            'completed' => array_values($completed),
            'running' => array_values($running),
            'pending' => array_values($pending),
            'completion_percentage' => count($phaseAgents) > 0
                ? round((count($completed) / count($phaseAgents)) * 100, 1)
                : 0,
            'is_complete' => count($completed) === count($phaseAgents)
        ];
    }

    /**
     * 전체 의존성 그래프 조회
     *
     * @return array 의존성 그래프
     */
    public static function getDependencyGraph(): array
    {
        return self::$dependencyGraph;
    }

    /**
     * 에이전트의 Phase 조회
     *
     * @param int $agentId 에이전트 ID
     * @return int Phase 번호 (1-4), 없으면 0
     */
    public function getAgentPhase(int $agentId): int
    {
        foreach (self::$phases as $phase => $agents) {
            if (in_array($agentId, $agents)) {
                return $phase;
            }
        }
        return 0;
    }

    /**
     * 순환 의존성 검사
     *
     * @return array 순환 의존성이 있으면 관련 에이전트 목록, 없으면 빈 배열
     */
    public static function detectCyclicDependencies(): array
    {
        $visited = [];
        $recursionStack = [];
        $cyclicAgents = [];

        foreach (array_keys(self::$dependencyGraph) as $agentId) {
            if (self::hasCycle($agentId, $visited, $recursionStack, $cyclicAgents)) {
                return $cyclicAgents;
            }
        }

        return [];
    }

    /**
     * DFS를 이용한 순환 검사
     */
    private static function hasCycle(int $agentId, array &$visited, array &$recursionStack, array &$cyclicAgents): bool
    {
        $visited[$agentId] = true;
        $recursionStack[$agentId] = true;

        foreach (self::$dependencyGraph[$agentId] ?? [] as $dependency) {
            if (!isset($visited[$dependency])) {
                if (self::hasCycle($dependency, $visited, $recursionStack, $cyclicAgents)) {
                    $cyclicAgents[] = $agentId;
                    return true;
                }
            } elseif (isset($recursionStack[$dependency]) && $recursionStack[$dependency]) {
                $cyclicAgents[] = $agentId;
                $cyclicAgents[] = $dependency;
                return true;
            }
        }

        $recursionStack[$agentId] = false;
        return false;
    }

    /**
     * 유효한 에이전트 ID인지 확인
     *
     * @param int $agentId 에이전트 ID
     * @return bool 유효 여부
     */
    private function isValidAgentId(int $agentId): bool
    {
        return $agentId >= 1 && $agentId <= 22;
    }

    /**
     * 완료된 에이전트 목록 조회
     *
     * @return array 완료된 에이전트 ID 배열
     */
    public function getCompletedAgents(): array
    {
        return $this->completedAgents;
    }

    /**
     * 실행 중인 에이전트 목록 조회
     *
     * @return array 실행 중인 에이전트 ID 배열
     */
    public function getRunningAgents(): array
    {
        return $this->runningAgents;
    }

    /**
     * 상태 초기화 (테스트용)
     */
    public function reset(): void
    {
        $this->completedAgents = [];
        $this->runningAgents = [];
        $this->logger->info("Dependency manager state reset");
    }

    /**
     * 세션에서 상태 복원
     *
     * @param array $completedAgents 완료된 에이전트 목록
     * @param array $runningAgents 실행 중인 에이전트 목록
     */
    public function restoreState(array $completedAgents, array $runningAgents = []): void
    {
        $this->completedAgents = array_filter($completedAgents, function ($id) {
            return $this->isValidAgentId($id);
        });
        $this->runningAgents = array_filter($runningAgents, function ($id) {
            return $this->isValidAgentId($id);
        });
        $this->logger->info("State restored", [
            'completed' => count($this->completedAgents),
            'running' => count($this->runningAgents)
        ]);
    }

    /**
     * 전체 상태 요약 조회
     *
     * @return array 상태 요약
     */
    public function getStatusSummary(): array
    {
        return [
            'total_agents' => 22,
            'completed_count' => count($this->completedAgents),
            'running_count' => count($this->runningAgents),
            'pending_count' => 22 - count($this->completedAgents) - count($this->runningAgents),
            'overall_completion' => round((count($this->completedAgents) / 22) * 100, 1),
            'phases' => [
                1 => $this->getPhaseStatus(1),
                2 => $this->getPhaseStatus(2),
                3 => $this->getPhaseStatus(3),
                4 => $this->getPhaseStatus(4)
            ],
            'executable_agents' => $this->getExecutableAgents()
        ];
    }
}


// =============================================================================
// End of Agent Dependency Manager
// =============================================================================

class PipelineOrchestrator
{
    /**
     * Pipeline Orchestrator
     *
     * Coordinates the complete Calm Break intervention pipeline:
     * 1. Sensing: Calculate calm_score from activity data
     * 2. Decision: Evaluate rules and determine action
     * 3. Execution: Dispatch intervention to LMS
     */

    private $db;
    private $logger;
    private $dispatcher;
    private $sla_limit_seconds = 180; // 3 minutes

    public function __construct()
    {
        $this->db = new MVPDatabase();
        $this->logger = new MVPLogger('orchestrator');
        $this->dispatcher = new InterventionDispatcher();
    }

    /**
     * Execute complete pipeline for a student
     *
     * @param int $student_id Student ID
     * @param array $activity_data Optional activity data (if not provided, fetches from DB)
     * @return array Pipeline execution result
     */
    public function execute($student_id, $activity_data = null)
    {
        $pipeline_start_time = microtime(true);
        $pipeline_id = 'pipeline-' . uniqid() . '-' . $student_id;

        $this->logger->info("Starting pipeline execution", [
            'pipeline_id' => $pipeline_id,
            'student_id' => $student_id
        ]);

        $result = [
            'pipeline_id' => $pipeline_id,
            'student_id' => $student_id,
            'success' => false,
            'steps' => [],
            'performance' => [],
            'errors' => []
        ];

        try {
            // ============================================================
            // Step 1: Sensing Layer - Calculate Metrics
            // ============================================================
            $sensing_result = $this->executeSensing($student_id, $activity_data);
            $result['steps']['sensing'] = $sensing_result;

            if (!$sensing_result['success']) {
                throw new Exception("Sensing layer failed: " . ($sensing_result['error'] ?? 'Unknown error') . " at " . __FILE__ . ":" . __LINE__);
            }

            $metrics = $sensing_result['data'];

            // ============================================================
            // Step 2: Decision Layer - Evaluate Rules
            // ============================================================
            $decision_result = $this->executeDecision($metrics);
            $result['steps']['decision'] = $decision_result;

            if (!$decision_result['success']) {
                throw new Exception("Decision layer failed: " . ($decision_result['error'] ?? 'Unknown error') . " at " . __FILE__ . ":" . __LINE__);
            }

            $decision = $decision_result['data'];

            // ============================================================
            // Step 3: Execution Layer - Dispatch Intervention
            // ============================================================
            // Skip execution if action is 'none'
            if ($decision['action'] === 'none') {
                $this->logger->info("No intervention needed", [
                    'pipeline_id' => $pipeline_id,
                    'action' => 'none',
                    'calm_score' => $metrics['calm_score']
                ]);

                $result['steps']['execution'] = [
                    'success' => true,
                    'message' => 'No intervention required',
                    'action' => 'none'
                ];
            } else {
                $execution_result = $this->executeIntervention($decision);
                $result['steps']['execution'] = $execution_result;

                if (!$execution_result['success']) {
                    throw new Exception("Execution layer failed: " . ($execution_result['error'] ?? 'Unknown error') . " at " . __FILE__ . ":" . __LINE__);
                }
            }

            // ============================================================
            // Pipeline Success
            // ============================================================
            $result['success'] = true;

            $pipeline_end_time = microtime(true);
            $total_time_seconds = round($pipeline_end_time - $pipeline_start_time, 3);
            $total_time_ms = round($total_time_seconds * 1000, 2);

            $result['performance'] = [
                'sensing_ms' => $sensing_result['performance']['execution_time_ms'] ?? 0,
                'decision_ms' => $decision_result['performance']['execution_time_ms'] ?? 0,
                'execution_ms' => $execution_result['performance']['execution_time_ms'] ?? 0,
                'total_ms' => $total_time_ms,
                'total_seconds' => $total_time_seconds,
                'sla_limit_seconds' => $this->sla_limit_seconds,
                'sla_met' => $total_time_seconds <= $this->sla_limit_seconds
            ];

            // Log SLA status
            if ($result['performance']['sla_met']) {
                $this->logger->info("Pipeline completed within SLA", [
                    'pipeline_id' => $pipeline_id,
                    'total_seconds' => $total_time_seconds,
                    'sla_limit' => $this->sla_limit_seconds
                ]);
            } else {
                $this->logger->warning("Pipeline exceeded SLA", [
                    'pipeline_id' => $pipeline_id,
                    'total_seconds' => $total_time_seconds,
                    'sla_limit' => $this->sla_limit_seconds,
                    'overage_seconds' => $total_time_seconds - $this->sla_limit_seconds
                ]);
            }

            // Record pipeline metrics
            $this->recordPipelineMetrics($pipeline_id, $result);

            $this->logger->info("Pipeline execution completed successfully", [
                'pipeline_id' => $pipeline_id,
                'total_time_ms' => $total_time_ms,
                'sla_met' => $result['performance']['sla_met']
            ]);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();

            $this->logger->error("Pipeline execution failed", $e, [
                'pipeline_id' => $pipeline_id,
                'student_id' => $student_id
            ]);
        }

        return $result;
    }

    /**
     * Execute Sensing Layer
     *
     * @param int $student_id Student ID
     * @param array|null $activity_data Optional activity data
     * @return array Sensing result
     */
    private function executeSensing($student_id, $activity_data = null)
    {
        $start_time = microtime(true);

        $this->logger->debug("Executing Sensing layer", ['student_id' => $student_id]);

        try {
            // If no activity data provided, use defaults or fetch from DB
            if ($activity_data === null) {
                // Default test data for MVP
                $activity_data = [
                    'session_duration' => 600, // 10 minutes
                    'interruptions' => 5,
                    'focus_time' => 400,
                    'correct_answers' => 7,
                    'total_attempts' => 10
                ];

                $this->logger->debug("Using default activity data for student", [
                    'student_id' => $student_id
                ]);
            }

            // Call Python calm calculator
            $python_script = __DIR__ . '/sensing/calm_calculator.py';
            $input_data = array_merge(['student_id' => $student_id], $activity_data);
            $json_input = escapeshellarg(json_encode($input_data));
            $command = "python3 $python_script $json_input 2>&1";

            $output = shell_exec($command);

            if ($output === null) {
                throw new Exception("Failed to execute Calm Calculator at " . __FILE__ . ":" . __LINE__);
            }

            $metrics = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($metrics['calm_score'])) {
                throw new Exception("Invalid Calm Calculator output: " . json_last_error_msg() . " at " . __FILE__ . ":" . __LINE__);
            }

            $execution_time = round((microtime(true) - $start_time) * 1000, 2);

            return [
                'success' => true,
                'data' => $metrics,
                'performance' => [
                    'execution_time_ms' => $execution_time
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'performance' => [
                    'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
                ]
            ];
        }
    }

    /**
     * Execute Decision Layer
     *
     * @param array $metrics Metrics from Sensing layer
     * @return array Decision result
     */
    private function executeDecision($metrics)
    {
        $start_time = microtime(true);

        $this->logger->debug("Executing Decision layer", [
            'student_id' => $metrics['student_id'],
            'calm_score' => $metrics['calm_score']
        ]);

        try {
            // Call Python rule engine
            $python_script = __DIR__ . '/decision/rule_engine.py';
            $json_input = escapeshellarg(json_encode($metrics));
            $command = "python3 $python_script $json_input 2>&1";

            $output = shell_exec($command);

            if ($output === null) {
                throw new Exception("Failed to execute Rule Engine at " . __FILE__ . ":" . __LINE__);
            }

            $decision = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($decision['action'])) {
                throw new Exception("Invalid Rule Engine output: " . json_last_error_msg() . " at " . __FILE__ . ":" . __LINE__);
            }

            // Store decision in database
            $decision_db_data = [
                'student_id' => $decision['student_id'],
                'action' => $decision['action'],
                'params' => $decision['params'],
                'confidence' => $decision['confidence'],
                'rationale' => $decision['rationale'],
                'rule_id' => $decision['rule_id'],
                'trace_data' => $decision['trace_data'],
                'timestamp' => date('Y-m-d H:i:s', strtotime($decision['timestamp']))
            ];

            $decision_id = $this->db->insert('decision_log', $decision_db_data);
            $decision['id'] = $decision_id;

            $execution_time = round((microtime(true) - $start_time) * 1000, 2);

            return [
                'success' => true,
                'data' => $decision,
                'performance' => [
                    'execution_time_ms' => $execution_time
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'performance' => [
                    'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
                ]
            ];
        }
    }

    /**
     * Execute Execution Layer
     *
     * @param array $decision Decision from Decision layer
     * @return array Execution result
     */
    private function executeIntervention($decision)
    {
        $start_time = microtime(true);

        $this->logger->debug("Executing Execution layer", [
            'decision_id' => $decision['id'] ?? null,
            'action' => $decision['action']
        ]);

        try {
            // Prepare intervention
            $intervention = $this->dispatcher->prepare($decision);

            // Execute intervention
            $result = $this->dispatcher->execute($intervention);

            if (!$result['success']) {
                throw new Exception("Intervention dispatch failed: " . ($result['error'] ?? 'Unknown error') . " at " . __FILE__ . ":" . __LINE__);
            }

            $execution_time = round((microtime(true) - $start_time) * 1000, 2);

            return [
                'success' => true,
                'data' => $result,
                'performance' => [
                    'execution_time_ms' => $execution_time
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'performance' => [
                    'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
                ]
            ];
        }
    }

    /**
     * Record pipeline performance metrics
     *
     * @param string $pipeline_id Pipeline ID
     * @param array $result Pipeline result
     */
    private function recordPipelineMetrics($pipeline_id, $result)
    {
        // Record total pipeline time
        $this->db->insert('system_metrics', [
            'metric_name' => 'pipeline_total_time',
            'metric_value' => $result['performance']['total_ms'],
            'unit' => 'ms',
            'context' => json_encode([
                'pipeline_id' => $pipeline_id,
                'student_id' => $result['student_id'],
                'action' => $result['steps']['decision']['data']['action'] ?? 'unknown'
            ]),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Record SLA compliance
        $this->db->insert('system_metrics', [
            'metric_name' => 'pipeline_sla_met',
            'metric_value' => $result['performance']['sla_met'] ? 1 : 0,
            'unit' => 'boolean',
            'context' => json_encode([
                'pipeline_id' => $pipeline_id,
                'total_seconds' => $result['performance']['total_seconds'],
                'sla_limit' => $this->sla_limit_seconds
            ]),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Record individual layer times
        foreach (['sensing', 'decision', 'execution'] as $layer) {
            $time_ms = $result['performance'][$layer . '_ms'] ?? 0;
            if ($time_ms > 0) {
                $this->db->insert('system_metrics', [
                    'metric_name' => "pipeline_{$layer}_time",
                    'metric_value' => $time_ms,
                    'unit' => 'ms',
                    'context' => json_encode(['pipeline_id' => $pipeline_id]),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     * Get pipeline SLA statistics
     *
     * @param int $hours Number of hours to look back (default: 24)
     * @return array SLA statistics
     */
    public function getSLAStats($hours = 24)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        $stats = $this->db->query(
            "SELECT
                COUNT(*) as total_pipelines,
                SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met_count,
                AVG(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) * 100 as sla_compliance_percent
            FROM mdl_mvp_system_metrics
            WHERE metric_name = 'pipeline_sla_met'
            AND timestamp >= ?",
            [$since]
        );

        $times = $this->db->query(
            "SELECT
                AVG(metric_value) as avg_ms,
                MIN(metric_value) as min_ms,
                MAX(metric_value) as max_ms
            FROM mdl_mvp_system_metrics
            WHERE metric_name = 'pipeline_total_time'
            AND timestamp >= ?",
            [$since]
        );

        return [
            'period_hours' => $hours,
            'total_pipelines' => $stats[0]['total_pipelines'] ?? 0,
            'sla_met_count' => $stats[0]['sla_met_count'] ?? 0,
            'sla_compliance_percent' => round($stats[0]['sla_compliance_percent'] ?? 0, 2),
            'avg_time_ms' => round($times[0]['avg_ms'] ?? 0, 2),
            'min_time_ms' => round($times[0]['min_ms'] ?? 0, 2),
            'max_time_ms' => round($times[0]['max_ms'] ?? 0, 2),
            'sla_target_seconds' => $this->sla_limit_seconds
        ];
    }
}


// =============================================================================
// CLI Usage
// =============================================================================

if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $student_id = intval($argv[1]);

    if ($student_id <= 0) {
        echo "Usage: php orchestrator.php <student_id>\n";
        exit(1);
    }

    $orchestrator = new PipelineOrchestrator();
    $result = $orchestrator->execute($student_id);

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit($result['success'] ? 0 : 1);
}


// =============================================================================
// Usage Examples
// =============================================================================
//
// Command line:
// php orchestrator.php 123
//
// From PHP:
// $orchestrator = new PipelineOrchestrator();
// $result = $orchestrator->execute(123);
// $result = $orchestrator->execute(123, ['session_duration' => 600, 'interruptions' => 8]);
//
// Get SLA stats:
// $stats = $orchestrator->getSLAStats(24); // Last 24 hours
//
// =============================================================================
