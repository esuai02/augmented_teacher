<?php
/**
 * AgentDependencyManager.php
 *
 * 에이전트 의존성 관리 모듈 - 22개 에이전트 실행 순서 강제
 * 의존성 그래프 기반 실행 검증 및 순환 의존성 방지
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore/Orchestration
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/engine_core/orchestration/AgentDependencyManager.php
 */

defined('MOODLE_INTERNAL') || die();

class AgentDependencyManager {

    /**
     * @var array 에이전트 의존성 그래프
     * 키: 에이전트 번호, 값: 선행 필수 에이전트 번호 배열
     */
    private $dependencyGraph = [
        // Phase 1: Daily Information Collection (기본 에이전트 - 의존성 없음)
        1  => [],                  // Agent 01 온보딩 - 의존성 없음
        2  => [],                  // Agent 02 시험일정 - 의존성 없음
        3  => [1],                 // Agent 03 목표분석 - 온보딩 완료 필요
        4  => [1],                 // Agent 04 약점검사 - 온보딩 완료 필요
        5  => [1],                 // Agent 05 학습감정 - 온보딩 완료 필요
        6  => [],                  // Agent 06 교사피드백 - 의존성 없음

        // Phase 2: Real-time Interaction
        7  => [3, 4, 5],           // Agent 07 상호작용타겟팅 - 목표분석, 약점검사, 학습감정 필요
        8  => [5],                 // Agent 08 평온도 - 학습감정 필요
        9  => [3, 4],              // Agent 09 학습관리 - 목표분석, 약점검사 필요
        10 => [4],                 // Agent 10 개념노트 - 약점검사 필요
        11 => [4],                 // Agent 11 문제노트 - 약점검사 필요
        12 => [8],                 // Agent 12 휴식루틴 - 평온도 필요
        13 => [5, 9],              // Agent 13 학습이탈 - 학습감정, 학습관리 필요

        // Phase 3: Diagnosis & Preparation
        14 => [9, 10],             // Agent 14 커리큘럼혁신 - 학습관리, 개념노트 필요
        15 => [11],                // Agent 15 문제재정의 - 문제노트 필요
        16 => [7],                 // Agent 16 상호작용준비 - 상호작용타겟팅 필요
        17 => [9],                 // Agent 17 남은활동 - 학습관리 필요
        18 => [12],                // Agent 18 시그니처루틴 - 휴식루틴 필요
        19 => [16],                // Agent 19 상호작용콘텐츠 - 상호작용준비 필요

        // Phase 4: Intervention & Improvement
        20 => [13, 16, 17],        // Agent 20 개입준비 - 학습이탈, 상호작용준비, 남은활동 필요
        21 => [20, 19],            // Agent 21 개입실행 - 개입준비, 상호작용콘텐츠 필요
        22 => [21],                // Agent 22 개선평가 - 개입실행 완료 필요
    ];

    /**
     * @var array Phase별 에이전트 그룹
     */
    private $phases = [
        1 => [1, 2, 3, 4, 5, 6],           // Phase 1: Daily Information Collection
        2 => [7, 8, 9, 10, 11, 12, 13],    // Phase 2: Real-time Interaction
        3 => [14, 15, 16, 17, 18, 19],     // Phase 3: Diagnosis & Preparation
        4 => [20, 21, 22]                   // Phase 4: Intervention & Improvement
    ];

    /**
     * @var array 완료된 에이전트 목록 (세션별)
     */
    private $completedAgents = [];

    /**
     * @var int 현재 학생 ID
     */
    private $studentId;

    /**
     * 생성자
     *
     * @param int $studentId 학생 ID
     */
    public function __construct(int $studentId = 0) {
        $this->studentId = $studentId;
        $this->loadCompletedAgents();
    }

    /**
     * 에이전트 실행 가능 여부 확인
     *
     * @param int $agentId 확인할 에이전트 번호
     * @return array ['can_execute' => bool, 'missing' => array, 'reason' => string]
     */
    public function canExecute(int $agentId): array {
        $result = [
            'can_execute' => true,
            'missing' => [],
            'reason' => ''
        ];

        // 의존성 그래프에 없는 에이전트
        if (!isset($this->dependencyGraph[$agentId])) {
            $result['reason'] = 'Agent not found in dependency graph';
            return $result;
        }

        // 의존성이 없는 에이전트는 항상 실행 가능
        if (empty($this->dependencyGraph[$agentId])) {
            $result['reason'] = 'No dependencies required';
            return $result;
        }

        // 의존성 확인
        foreach ($this->dependencyGraph[$agentId] as $requiredAgent) {
            if (!in_array($requiredAgent, $this->completedAgents)) {
                $result['can_execute'] = false;
                $result['missing'][] = $requiredAgent;
            }
        }

        if (!$result['can_execute']) {
            $result['reason'] = 'Missing required agents: ' . implode(', ', $result['missing']);
        } else {
            $result['reason'] = 'All dependencies satisfied';
        }

        return $result;
    }

    /**
     * 에이전트 완료 표시
     *
     * @param int $agentId 완료된 에이전트 번호
     * @param array $metadata 추가 메타데이터
     * @return bool 성공 여부
     */
    public function markCompleted(int $agentId, array $metadata = []): bool {
        if (!in_array($agentId, $this->completedAgents)) {
            $this->completedAgents[] = $agentId;
            $this->saveCompletedAgents($agentId, $metadata);
        }
        return true;
    }

    /**
     * 완료된 에이전트 초기화
     *
     * @param int|null $phase 특정 Phase만 초기화 (null이면 전체)
     */
    public function resetCompleted(?int $phase = null): void {
        if ($phase === null) {
            $this->completedAgents = [];
        } else if (isset($this->phases[$phase])) {
            foreach ($this->phases[$phase] as $agentId) {
                $key = array_search($agentId, $this->completedAgents);
                if ($key !== false) {
                    unset($this->completedAgents[$key]);
                }
            }
            $this->completedAgents = array_values($this->completedAgents);
        }
    }

    /**
     * 실행 가능한 에이전트 목록 조회
     *
     * @return array 실행 가능한 에이전트 번호 배열
     */
    public function getExecutableAgents(): array {
        $executable = [];

        foreach (array_keys($this->dependencyGraph) as $agentId) {
            // 이미 완료된 에이전트 제외
            if (in_array($agentId, $this->completedAgents)) {
                continue;
            }

            $result = $this->canExecute($agentId);
            if ($result['can_execute']) {
                $executable[] = $agentId;
            }
        }

        return $executable;
    }

    /**
     * 다음 실행 권장 에이전트 (우선순위 기반)
     *
     * @return int|null 권장 에이전트 번호
     */
    public function getNextRecommendedAgent(): ?int {
        $executable = $this->getExecutableAgents();

        if (empty($executable)) {
            return null;
        }

        // Phase 순서대로 정렬하여 낮은 번호 먼저 반환
        sort($executable);
        return $executable[0];
    }

    /**
     * 의존성 그래프 조회
     *
     * @param int|null $agentId 특정 에이전트 (null이면 전체)
     * @return array 의존성 정보
     */
    public function getDependencies(?int $agentId = null): array {
        if ($agentId !== null) {
            return $this->dependencyGraph[$agentId] ?? [];
        }
        return $this->dependencyGraph;
    }

    /**
     * 역방향 의존성 조회 (이 에이전트에 의존하는 에이전트들)
     *
     * @param int $agentId 에이전트 번호
     * @return array 의존하는 에이전트 목록
     */
    public function getDependents(int $agentId): array {
        $dependents = [];

        foreach ($this->dependencyGraph as $agent => $dependencies) {
            if (in_array($agentId, $dependencies)) {
                $dependents[] = $agent;
            }
        }

        return $dependents;
    }

    /**
     * Phase 진행 상태 조회
     *
     * @return array Phase별 완료율
     */
    public function getPhaseProgress(): array {
        $progress = [];

        foreach ($this->phases as $phaseNum => $agents) {
            $completed = array_intersect($agents, $this->completedAgents);
            $progress[$phaseNum] = [
                'total' => count($agents),
                'completed' => count($completed),
                'percentage' => round((count($completed) / count($agents)) * 100, 1),
                'agents' => $agents,
                'completed_agents' => array_values($completed)
            ];
        }

        return $progress;
    }

    /**
     * 순환 의존성 검사
     *
     * @return array 순환 의존성 발견 시 해당 경로
     */
    public function detectCyclicDependencies(): array {
        $cycles = [];
        $visited = [];
        $recStack = [];

        foreach (array_keys($this->dependencyGraph) as $agentId) {
            $path = [];
            if ($this->detectCycleUtil($agentId, $visited, $recStack, $path)) {
                $cycles[] = $path;
            }
        }

        return $cycles;
    }

    /**
     * 순환 의존성 검사 유틸리티 (DFS)
     */
    private function detectCycleUtil(int $node, array &$visited, array &$recStack, array &$path): bool {
        $visited[$node] = true;
        $recStack[$node] = true;
        $path[] = $node;

        foreach ($this->dependencyGraph[$node] ?? [] as $dependent) {
            if (!isset($visited[$dependent])) {
                if ($this->detectCycleUtil($dependent, $visited, $recStack, $path)) {
                    return true;
                }
            } else if (isset($recStack[$dependent]) && $recStack[$dependent]) {
                $path[] = $dependent;
                return true;
            }
        }

        $recStack[$node] = false;
        array_pop($path);
        return false;
    }

    /**
     * 완료된 에이전트 로드 (DB에서)
     */
    private function loadCompletedAgents(): void {
        if ($this->studentId <= 0) {
            return;
        }

        global $DB;

        try {
            // mdl_at_agent_execution_log에서 오늘 완료된 에이전트 조회
            $today = strtotime('today midnight');

            if ($DB->get_manager()->table_exists(new xmldb_table('at_agent_execution_log'))) {
                $records = $DB->get_records_sql(
                    "SELECT DISTINCT agent_id
                     FROM {at_agent_execution_log}
                     WHERE student_id = ?
                       AND status = 'completed'
                       AND executed_at >= ?",
                    [$this->studentId, $today]
                );

                foreach ($records as $record) {
                    $this->completedAgents[] = (int)$record->agent_id;
                }
            }
        } catch (Exception $e) {
            error_log("[AgentDependencyManager] loadCompletedAgents error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    /**
     * 완료된 에이전트 저장 (DB에)
     *
     * @param int $agentId 에이전트 번호
     * @param array $metadata 추가 메타데이터
     */
    private function saveCompletedAgents(int $agentId, array $metadata = []): void {
        if ($this->studentId <= 0) {
            return;
        }

        global $DB;

        try {
            if ($DB->get_manager()->table_exists(new xmldb_table('at_agent_execution_log'))) {
                $record = new stdClass();
                $record->student_id = $this->studentId;
                $record->agent_id = $agentId;
                $record->status = 'completed';
                $record->metadata = json_encode($metadata);
                $record->executed_at = time();

                $DB->insert_record('at_agent_execution_log', $record);
            }
        } catch (Exception $e) {
            error_log("[AgentDependencyManager] saveCompletedAgents error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }

    /**
     * 완료된 에이전트 목록 반환
     *
     * @return array
     */
    public function getCompletedAgents(): array {
        return $this->completedAgents;
    }

    /**
     * 의존성 그래프 시각화 (ASCII)
     *
     * @return string ASCII 그래프
     */
    public function visualizeDependencies(): string {
        $output = "=== Agent Dependency Graph ===\n\n";

        foreach ($this->phases as $phaseNum => $agents) {
            $output .= "Phase {$phaseNum}:\n";
            foreach ($agents as $agentId) {
                $deps = $this->dependencyGraph[$agentId] ?? [];
                $status = in_array($agentId, $this->completedAgents) ? '✓' : '○';
                $depsStr = empty($deps) ? '(no deps)' : '← [' . implode(', ', $deps) . ']';
                $output .= sprintf("  %s Agent %02d %s\n", $status, $agentId, $depsStr);
            }
            $output .= "\n";
        }

        return $output;
    }
}

/**
 * 헬퍼 함수: 에이전트 실행 가능 여부 확인
 *
 * @param int $agentId 에이전트 번호
 * @param int $studentId 학생 ID
 * @return array
 */
function can_execute_agent(int $agentId, int $studentId): array {
    $manager = new AgentDependencyManager($studentId);
    return $manager->canExecute($agentId);
}

/**
 * 헬퍼 함수: 에이전트 완료 표시
 *
 * @param int $agentId 에이전트 번호
 * @param int $studentId 학생 ID
 * @param array $metadata 메타데이터
 * @return bool
 */
function mark_agent_completed(int $agentId, int $studentId, array $metadata = []): bool {
    $manager = new AgentDependencyManager($studentId);
    return $manager->markCompleted($agentId, $metadata);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * require_once(__DIR__ . '/../engine_core/orchestration/AgentDependencyManager.php');
 *
 * // 1. 에이전트 실행 전 의존성 확인
 * $manager = new AgentDependencyManager($USER->id);
 * $result = $manager->canExecute(21); // Agent 21 실행 가능 여부
 *
 * if (!$result['can_execute']) {
 *     echo "Cannot execute Agent 21. Missing: " . implode(', ', $result['missing']);
 *     exit;
 * }
 *
 * // 2. 에이전트 실행 완료 후 표시
 * $manager->markCompleted(21, ['duration' => 5.2, 'result' => 'success']);
 *
 * // 3. 다음 실행 권장 에이전트 조회
 * $nextAgent = $manager->getNextRecommendedAgent();
 *
 * // 4. Phase 진행 상태 확인
 * $progress = $manager->getPhaseProgress();
 * // Phase 1: 83.3%, Phase 2: 0%, ...
 *
 * // 5. 의존성 그래프 시각화
 * echo $manager->visualizeDependencies();
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 참조 테이블: mdl_at_agent_execution_log
 *
 * 필드:
 * - id (int): PK
 * - student_id (int): 학생 ID
 * - agent_id (int): 에이전트 번호 (1-22)
 * - status (varchar): 실행 상태 (pending, running, completed, failed)
 * - metadata (text): JSON 형식 추가 데이터
 * - executed_at (int): 실행 시간 (timestamp)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
