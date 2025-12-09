<?php
/**
 * A/B Testing Bridge - PHP Interface
 *
 * Python A/B Testing Framework와 Moodle 통합을 위한 PHP Bridge
 *
 * 사용법:
 *   include_once(__DIR__ . '/ab_testing_bridge.php');
 *   $abTest = new ABTestingBridge('quantum_v1', $userid);
 *   $group = $abTest->getGroup();
 *   $abTest->recordOutcome(['learning_gain' => 0.15, 'engagement_rate' => 0.85]);
 *
 * @version 1.0
 * @date 2025-12-09
 */

// Moodle config (서버 환경용)
if (file_exists("/home/moodle/public_html/moodle/config.php")) {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
}

/**
 * A/B Testing Bridge Class
 *
 * Python _ab_testing_framework.py와 통신하여
 * 테스트 그룹 할당 및 결과 기록을 수행
 */
class ABTestingBridge {

    /** @var string 테스트 ID */
    private $testId;

    /** @var int 학생 ID */
    private $studentId;

    /** @var float Treatment 그룹 비율 */
    private $treatmentRatio;

    /** @var int 랜덤 시드 */
    private $seed;

    /** @var string Python 경로 */
    private $pythonPath;

    /** @var string 스크립트 디렉토리 */
    private $scriptDir;

    /** @var bool 디버그 모드 */
    private $debug;

    /** @var string|null 캐시된 그룹 */
    private $cachedGroup = null;

    /**
     * Constructor
     *
     * @param string $testId 테스트 식별자
     * @param int $studentId 학생 ID
     * @param float $treatmentRatio Treatment 그룹 비율 (0.0-1.0)
     * @param int $seed 랜덤 시드
     * @param bool $debug 디버그 모드
     */
    public function __construct($testId = 'quantum_v1', $studentId = null, $treatmentRatio = 0.5, $seed = 42, $debug = false) {
        $this->testId = $testId;
        $this->studentId = $studentId ?? ($GLOBALS['USER']->id ?? 0);
        $this->treatmentRatio = $treatmentRatio;
        $this->seed = $seed;
        $this->debug = $debug;

        // Python 경로 설정
        $this->pythonPath = '/usr/bin/python3';
        $this->scriptDir = __DIR__;
    }

    /**
     * 학생의 테스트 그룹 반환
     *
     * @return string 'control' 또는 'treatment'
     */
    public function getGroup() {
        if ($this->cachedGroup !== null) {
            return $this->cachedGroup;
        }

        // PHP 내에서 직접 해시 기반 할당 (Python과 동일한 로직)
        $hashInput = "{$this->testId}_{$this->seed}_{$this->studentId}";
        $hashValue = md5($hashInput);

        // 해시의 첫 8자리를 16진수로 변환
        $hashInt = hexdec(substr($hashValue, 0, 8));
        $ratio = $hashInt / 0xFFFFFFFF;

        $this->cachedGroup = ($ratio < $this->treatmentRatio) ? 'treatment' : 'control';

        return $this->cachedGroup;
    }

    /**
     * Treatment 그룹 여부 확인
     *
     * @return bool Treatment 그룹이면 true
     */
    public function isTreatment() {
        return $this->getGroup() === 'treatment';
    }

    /**
     * Control 그룹 여부 확인
     *
     * @return bool Control 그룹이면 true
     */
    public function isControl() {
        return $this->getGroup() === 'control';
    }

    /**
     * 그룹 정보 상세 반환
     *
     * @return array 그룹 정보 배열
     */
    public function getGroupInfo() {
        return [
            'student_id' => $this->studentId,
            'test_id' => $this->testId,
            'group' => $this->getGroup(),
            'is_treatment' => $this->isTreatment(),
            'assigned_at' => date('c')
        ];
    }

    /**
     * 학습 결과 기록
     *
     * @param array $metrics 측정 지표 배열
     * @param string|null $sessionId 세션 ID
     * @return array|false 기록 결과 또는 실패시 false
     */
    public function recordOutcome($metrics, $sessionId = null) {
        global $DB;

        $record = [
            'test_id' => $this->testId,
            'student_id' => $this->studentId,
            'session_id' => $sessionId ?? uniqid('session_'),
            'group_name' => $this->getGroup(),
            'metrics' => json_encode($metrics),
            'recorded_at' => time()
        ];

        // DB 저장 시도
        if ($DB && $this->tableExists('ab_test_outcomes')) {
            try {
                $DB->insert_record('ab_test_outcomes', (object)$record);
            } catch (Exception $e) {
                if ($this->debug) {
                    error_log("ABTestingBridge::recordOutcome error: " . $e->getMessage());
                }
            }
        }

        // Python 프레임워크에도 기록 (비동기)
        $this->callPythonAsync('record_outcome', [
            'student_id' => $this->studentId,
            'metrics' => $metrics,
            'session_id' => $record['session_id']
        ]);

        return $record;
    }

    /**
     * 8D 상태 변화 기록
     *
     * @param array $preState 개입 전 8D 상태
     * @param array $postState 개입 후 8D 상태
     * @param array $agentSequence 적용된 에이전트 순서
     * @return array|false 기록 결과 또는 실패시 false
     */
    public function recordStateChange($preState, $postState, $agentSequence) {
        global $DB;

        // 효과성 점수 계산
        $dimensionNames = [
            'cognitive_clarity', 'emotional_stability', 'engagement_level',
            'concept_mastery', 'routine_strength', 'metacognitive_awareness',
            'dropout_risk', 'intervention_readiness'
        ];

        $totalImprovement = 0;
        foreach ($dimensionNames as $i => $name) {
            $change = $postState[$i] - $preState[$i];
            // dropout_risk는 감소가 좋음
            $improvement = ($name === 'dropout_risk') ? -$change : $change;
            $totalImprovement += $improvement;
        }
        $effectivenessScore = $totalImprovement / count($dimensionNames);

        $record = [
            'test_id' => $this->testId,
            'student_id' => $this->studentId,
            'group_name' => $this->getGroup(),
            'pre_state' => json_encode($preState),
            'post_state' => json_encode($postState),
            'agent_sequence' => json_encode($agentSequence),
            'effectiveness_score' => $effectivenessScore,
            'recorded_at' => time()
        ];

        // DB 저장 시도
        if ($DB && $this->tableExists('ab_test_state_changes')) {
            try {
                $DB->insert_record('ab_test_state_changes', (object)$record);
            } catch (Exception $e) {
                if ($this->debug) {
                    error_log("ABTestingBridge::recordStateChange error: " . $e->getMessage());
                }
            }
        }

        return $record;
    }

    /**
     * A/B 테스트 리포트 생성
     *
     * @return array 리포트 데이터
     */
    public function generateReport() {
        $result = $this->callPython('generate_report', [
            'test_id' => $this->testId
        ]);

        if ($result && isset($result['success']) && $result['success']) {
            return $result['report'];
        }

        // Python 실패시 DB에서 직접 집계
        return $this->generateReportFromDB();
    }

    /**
     * DB에서 직접 리포트 생성 (Python 폴백)
     *
     * @return array 리포트 데이터
     */
    private function generateReportFromDB() {
        global $DB;

        if (!$DB || !$this->tableExists('ab_test_outcomes')) {
            return ['error' => 'Database not available'];
        }

        $outcomes = $DB->get_records('ab_test_outcomes', ['test_id' => $this->testId]);

        $controlMetrics = [];
        $treatmentMetrics = [];

        foreach ($outcomes as $outcome) {
            $metrics = json_decode($outcome->metrics, true);
            $group = $outcome->group_name;

            foreach ($metrics as $key => $value) {
                if ($group === 'control') {
                    if (!isset($controlMetrics[$key])) $controlMetrics[$key] = [];
                    $controlMetrics[$key][] = $value;
                } else {
                    if (!isset($treatmentMetrics[$key])) $treatmentMetrics[$key] = [];
                    $treatmentMetrics[$key][] = $value;
                }
            }
        }

        // 각 지표별 통계 계산
        $metricAnalysis = [];
        $allMetrics = array_unique(array_merge(array_keys($controlMetrics), array_keys($treatmentMetrics)));

        foreach ($allMetrics as $metricName) {
            $controlValues = $controlMetrics[$metricName] ?? [];
            $treatmentValues = $treatmentMetrics[$metricName] ?? [];

            if (!empty($controlValues) && !empty($treatmentValues)) {
                $metricAnalysis[$metricName] = [
                    'control' => [
                        'n' => count($controlValues),
                        'mean' => $this->mean($controlValues),
                        'std' => $this->std($controlValues)
                    ],
                    'treatment' => [
                        'n' => count($treatmentValues),
                        'mean' => $this->mean($treatmentValues),
                        'std' => $this->std($treatmentValues)
                    ],
                    'comparison' => $this->calculateComparison($controlValues, $treatmentValues)
                ];
            }
        }

        return [
            'test_id' => $this->testId,
            'generated_at' => date('c'),
            'sample_sizes' => [
                'control' => count(array_filter($outcomes, function($o) { return $o->group_name === 'control'; })),
                'treatment' => count(array_filter($outcomes, function($o) { return $o->group_name === 'treatment'; }))
            ],
            'metrics' => $metricAnalysis
        ];
    }

    /**
     * 그룹 간 비교 통계 계산
     */
    private function calculateComparison($control, $treatment) {
        $n1 = count($control);
        $n2 = count($treatment);

        if ($n1 < 2 || $n2 < 2) {
            return ['t_statistic' => 0, 'p_value' => 1, 'effect_size' => 0, 'significant' => false];
        }

        $m1 = $this->mean($control);
        $m2 = $this->mean($treatment);
        $s1 = $this->std($control);
        $s2 = $this->std($treatment);

        // Pooled standard error
        $se = sqrt(($s1**2 / $n1) + ($s2**2 / $n2));
        if ($se == 0) {
            return ['t_statistic' => 0, 'p_value' => 1, 'effect_size' => 0, 'significant' => false];
        }

        $tStat = ($m2 - $m1) / $se;

        // p-value 근사 (정규분포)
        $pValue = 2 * (1 - $this->normalCdf(abs($tStat)));

        // Cohen's d
        $pooledStd = sqrt((($n1 - 1) * $s1**2 + ($n2 - 1) * $s2**2) / ($n1 + $n2 - 2));
        $effectSize = ($pooledStd != 0) ? ($m2 - $m1) / $pooledStd : 0;

        return [
            't_statistic' => $tStat,
            'p_value' => $pValue,
            'effect_size' => $effectSize,
            'significant' => $pValue < 0.05,
            'effect_interpretation' => $this->interpretEffectSize($effectSize)
        ];
    }

    /**
     * 효과 크기 해석
     */
    private function interpretEffectSize($d) {
        $absD = abs($d);
        if ($absD < 0.2) return 'negligible';
        if ($absD < 0.5) return 'small';
        if ($absD < 0.8) return 'medium';
        return 'large';
    }

    /**
     * 평균 계산
     */
    private function mean($values) {
        if (empty($values)) return 0;
        return array_sum($values) / count($values);
    }

    /**
     * 표준편차 계산
     */
    private function std($values) {
        if (count($values) < 2) return 0;
        $mean = $this->mean($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return ($x - $mean) ** 2;
        }, $values)) / (count($values) - 1);
        return sqrt($variance);
    }

    /**
     * 정규분포 CDF 근사
     */
    private function normalCdf($x) {
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $sign = ($x >= 0) ? 1 : -1;
        $x = abs($x) / sqrt(2);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - (((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x));

        return 0.5 * (1.0 + $sign * $y);
    }

    /**
     * 테이블 존재 여부 확인
     */
    private function tableExists($tableName) {
        global $DB;
        try {
            $dbManager = $DB->get_manager();
            return $dbManager->table_exists($tableName);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Python 스크립트 호출
     */
    private function callPython($action, $params) {
        $input = json_encode([
            'action' => $action,
            'params' => $params
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'ab_');
        file_put_contents($tempFile, $input);

        $cmd = sprintf(
            '%s %s/ab_testing_cli.py %s 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->scriptDir),
            escapeshellarg($tempFile)
        );

        $output = shell_exec($cmd);
        unlink($tempFile);

        return json_decode($output, true);
    }

    /**
     * Python 스크립트 비동기 호출
     */
    private function callPythonAsync($action, $params) {
        // 비동기 실행을 위해 백그라운드로 실행
        $input = json_encode([
            'action' => $action,
            'params' => $params,
            'test_id' => $this->testId
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'ab_async_');
        file_put_contents($tempFile, $input);

        $cmd = sprintf(
            '%s %s/ab_testing_cli.py %s > /dev/null 2>&1 &',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->scriptDir),
            escapeshellarg($tempFile)
        );

        exec($cmd);
        // 파일은 Python에서 정리
    }
}


/**
 * A/B 테스트 유틸리티 함수들
 */

/**
 * 학생의 테스트 그룹 빠르게 확인
 *
 * @param int $studentId 학생 ID
 * @param string $testId 테스트 ID
 * @return string 'control' 또는 'treatment'
 */
function ab_get_group($studentId, $testId = 'quantum_v1') {
    $bridge = new ABTestingBridge($testId, $studentId);
    return $bridge->getGroup();
}

/**
 * Treatment 그룹 여부 확인
 *
 * @param int $studentId 학생 ID
 * @param string $testId 테스트 ID
 * @return bool
 */
function ab_is_treatment($studentId, $testId = 'quantum_v1') {
    $bridge = new ABTestingBridge($testId, $studentId);
    return $bridge->isTreatment();
}

/**
 * 에이전트 선택 로직 분기
 *
 * @param int $studentId 학생 ID
 * @param array $triggeredAgents 트리거된 에이전트 목록
 * @param array $state8d 8D StateVector
 * @return array 최종 에이전트 순서
 */
function ab_select_agent_order($studentId, $triggeredAgents, $state8d = null) {
    $bridge = new ABTestingBridge('quantum_v1', $studentId);

    if ($bridge->isTreatment() && $state8d !== null) {
        // Treatment: 양자 모델 사용
        include_once(__DIR__ . '/orchestrator_bridge.php');
        $orchestrator = new QuantumOrchestratorBridge($studentId, false);
        return $orchestrator->suggestAgentOrder($triggeredAgents, $state8d);
    } else {
        // Control: 기존 순서 그대로 사용
        return $triggeredAgents;
    }
}


// ==============================================================================
// API Endpoint Handling
// ==============================================================================

// API 모드로 호출된 경우
if (isset($_GET['api']) || isset($_POST['api'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $studentId = intval($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
    $testId = $_GET['test_id'] ?? $_POST['test_id'] ?? 'quantum_v1';

    $bridge = new ABTestingBridge($testId, $studentId);

    switch ($action) {
        case 'get_group':
            echo json_encode($bridge->getGroupInfo());
            break;

        case 'record_outcome':
            $metrics = json_decode($_POST['metrics'] ?? '{}', true);
            $sessionId = $_POST['session_id'] ?? null;
            $result = $bridge->recordOutcome($metrics, $sessionId);
            echo json_encode(['success' => true, 'record' => $result]);
            break;

        case 'record_state_change':
            $preState = json_decode($_POST['pre_state'] ?? '[]', true);
            $postState = json_decode($_POST['post_state'] ?? '[]', true);
            $agentSequence = json_decode($_POST['agent_sequence'] ?? '[]', true);
            $result = $bridge->recordStateChange($preState, $postState, $agentSequence);
            echo json_encode(['success' => true, 'record' => $result]);
            break;

        case 'generate_report':
            $report = $bridge->generateReport();
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action', 'available' => ['get_group', 'record_outcome', 'record_state_change', 'generate_report']]);
    }
    exit;
}

// ==============================================================================
// Database Schema
// ==============================================================================
/*
CREATE TABLE IF NOT EXISTS mdl_ab_tests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    test_id VARCHAR(64) NOT NULL UNIQUE,
    treatment_ratio DECIMAL(3,2) DEFAULT 0.50,
    seed INT DEFAULT 42,
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_id (test_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mdl_ab_test_outcomes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    test_id VARCHAR(64) NOT NULL,
    student_id BIGINT NOT NULL,
    session_id VARCHAR(64),
    group_name ENUM('control', 'treatment') NOT NULL,
    metrics JSON,
    recorded_at INT NOT NULL,
    INDEX idx_test_student (test_id, student_id),
    INDEX idx_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mdl_ab_test_state_changes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    test_id VARCHAR(64) NOT NULL,
    student_id BIGINT NOT NULL,
    group_name ENUM('control', 'treatment') NOT NULL,
    pre_state JSON,
    post_state JSON,
    agent_sequence JSON,
    effectiveness_score DECIMAL(5,4),
    recorded_at INT NOT NULL,
    INDEX idx_test_student (test_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/
