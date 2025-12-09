<?php
/**
 * Quantum Orchestrator Bridge - Standalone PHP-Python Interface
 * ==============================================================
 * Phase 8.3: 독립형 양자 오케스트레이터 브릿지
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/orchestrator_bridge.php
 *
 * 사용법:
 *   include_once('orchestrator_bridge.php');
 *   $bridge = new QuantumOrchestratorBridge($userid);
 *   $state = $bridge->getCurrentState();
 *
 * API 메서드:
 *   getCurrentState()      - 8D StateVector 반환
 *   getRecommendations()   - 에이전트 순서 추천
 *   getAnalysis()          - 학생 상태 분석
 *   getEntanglementMap()   - 22개 에이전트 얽힘 맵
 *   testConnection()       - Python 연결 테스트
 *
 * @file    orchestrator_bridge.php
 * @package QuantumOrchestration
 * @phase   8.3
 * @version 1.0.0
 * @created 2025-12-09
 */

// =============================================================================
// 상수 정의 (중복 정의 방지)
// =============================================================================
if (!defined('HOLONS_PATH')) {
    define('HOLONS_PATH', __DIR__);
}
if (!defined('PYTHON_ORCHESTRATOR')) {
    define('PYTHON_ORCHESTRATOR', HOLONS_PATH . '/_quantum_orchestrator.py');
}
if (!defined('PYTHON_DATA_INTERFACE')) {
    define('PYTHON_DATA_INTERFACE', HOLONS_PATH . '/_quantum_data_interface.py');
}
if (!defined('PYTHON_CMD')) {
    define('PYTHON_CMD', 'python3');
}

// =============================================================================
// QuantumOrchestratorBridge 클래스
// =============================================================================
/**
 * PHP-Python 양자 오케스트레이터 브릿지
 *
 * Phase 7 Data Interface와 Phase 8 Orchestrator를 통합하여
 * 8D StateVector 계산, 에이전트 추천, 얽힘 분석을 수행
 */
class QuantumOrchestratorBridge {

    private $db;
    private $userid;
    private $errors = [];
    private $debug = false;

    // 22개 에이전트 정보 (Phase 1~4)
    private $agents = [
        1 => ['name' => '학습 적응형 에이전트', 'phase' => 1],
        2 => ['name' => '감정/정서 에이전트', 'phase' => 1],
        3 => ['name' => '목표 설정 에이전트', 'phase' => 1],
        4 => ['name' => '참여도 분석 에이전트', 'phase' => 1],
        5 => ['name' => '학습 감정 에이전트', 'phase' => 1],
        6 => ['name' => '멘탈 지원 에이전트', 'phase' => 1],
        7 => ['name' => '컨텐츠 적응 에이전트', 'phase' => 2],
        8 => ['name' => '침착도 에이전트', 'phase' => 2],
        9 => ['name' => '학습 관리 에이전트', 'phase' => 2],
        10 => ['name' => '유지력 에이전트', 'phase' => 2],
        11 => ['name' => '문제노트 에이전트', 'phase' => 2],
        12 => ['name' => '휴식 루틴 에이전트', 'phase' => 2],
        13 => ['name' => '마인드맵 에이전트', 'phase' => 2],
        14 => ['name' => '진단 기반 학습 에이전트', 'phase' => 3],
        15 => ['name' => '학습 유형 에이전트', 'phase' => 3],
        16 => ['name' => '오답 분석 에이전트', 'phase' => 3],
        17 => ['name' => '자기평가 에이전트', 'phase' => 3],
        18 => ['name' => '힌트 제공 에이전트', 'phase' => 3],
        19 => ['name' => '자료 추천 에이전트', 'phase' => 3],
        20 => ['name' => '학습 계획 에이전트', 'phase' => 4],
        21 => ['name' => '장기기억 에이전트', 'phase' => 4],
        22 => ['name' => '이탈방지 에이전트', 'phase' => 4]
    ];

    // 8D StateVector 차원 정보
    private $dimensions = [
        0 => ['name' => 'cognitive_clarity', 'label' => '인지적 명확성', 'color' => '#58a6ff'],
        1 => ['name' => 'emotional_stability', 'label' => '정서적 안정성', 'color' => '#7ee787'],
        2 => ['name' => 'engagement_level', 'label' => '참여 수준', 'color' => '#f0883e'],
        3 => ['name' => 'concept_mastery', 'label' => '개념 숙달도', 'color' => '#a371f7'],
        4 => ['name' => 'routine_strength', 'label' => '루틴 강도', 'color' => '#f778ba'],
        5 => ['name' => 'metacognitive_awareness', 'label' => '메타인지 인식', 'color' => '#79c0ff'],
        6 => ['name' => 'dropout_risk', 'label' => '이탈 위험도', 'color' => '#f85149'],
        7 => ['name' => 'intervention_readiness', 'label' => '개입 준비도', 'color' => '#238636']
    ];

    /**
     * 생성자
     * @param int $userid 대상 사용자 ID (null이면 현재 로그인 사용자)
     * @param bool $debug 디버그 모드 활성화
     */
    public function __construct($userid = null, $debug = false) {
        global $DB, $USER;
        $this->db = $DB ?? null;
        $this->userid = $userid ?? ($USER->id ?? 0);
        $this->debug = $debug;
    }

    // =========================================================================
    // 공개 API 메서드
    // =========================================================================

    /**
     * Python 연결 테스트
     * @return array 테스트 결과
     */
    public function testConnection(): array {
        $result = [
            'success' => false,
            'timestamp' => $this->getCurrentTimestamp(),
            'checks' => []
        ];

        // Python 버전 확인
        $pythonVersion = shell_exec(PYTHON_CMD . ' --version 2>&1');
        $result['checks']['python_version'] = [
            'status' => strpos($pythonVersion, 'Python 3') !== false ? 'ok' : 'error',
            'value' => trim($pythonVersion)
        ];

        // Orchestrator 파일 존재 확인
        $result['checks']['orchestrator_file'] = [
            'status' => file_exists(PYTHON_ORCHESTRATOR) ? 'ok' : 'error',
            'value' => file_exists(PYTHON_ORCHESTRATOR) ? basename(PYTHON_ORCHESTRATOR) : 'NOT FOUND'
        ];

        // Data Interface 파일 존재 확인
        $result['checks']['data_interface_file'] = [
            'status' => file_exists(PYTHON_DATA_INTERFACE) ? 'ok' : 'error',
            'value' => file_exists(PYTHON_DATA_INTERFACE) ? basename(PYTHON_DATA_INTERFACE) : 'NOT FOUND'
        ];

        // Python 모듈 import 테스트
        $importTest = shell_exec(PYTHON_CMD . ' -c "from dataclasses import dataclass; print(\'OK\')" 2>&1');
        $result['checks']['python_modules'] = [
            'status' => trim($importTest) === 'OK' ? 'ok' : 'error',
            'value' => trim($importTest)
        ];

        // 전체 상태 판정
        $allOk = true;
        foreach ($result['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $allOk = false;
                break;
            }
        }
        $result['success'] = $allOk;

        return $result;
    }

    /**
     * 현재 8D StateVector 조회
     * @return array 8D StateVector 데이터
     */
    public function getCurrentState(): array {
        $result = [
            'success' => false,
            'timestamp' => $this->getCurrentTimestamp(),
            'userid' => $this->userid,
            'state_vector' => [],
            'dimensions' => $this->dimensions,
            'errors' => []
        ];

        $pythonCode = $this->generateStateScript();
        $output = $this->runPythonCode($pythonCode);

        if ($output) {
            $jsonStart = strpos($output, '{"success"');
            if ($jsonStart !== false) {
                $jsonStr = substr($output, $jsonStart);
                $decoded = json_decode($jsonStr, true);
                if ($decoded) {
                    $decoded['dimensions'] = $this->dimensions;
                    return $decoded;
                }
            }
            $result['errors'][] = "Error [orchestrator_bridge.php:" . __LINE__ . "]: Failed to parse state output";
            $result['raw_output'] = $output;
        }

        return $result;
    }

    /**
     * 에이전트 순서 추천 조회
     * @param array $triggeredAgents 트리거된 에이전트 ID 배열
     * @return array 추천 결과
     */
    public function getRecommendations(array $triggeredAgents = []): array {
        if (empty($triggeredAgents)) {
            $triggeredAgents = array_keys($this->agents);
        }

        $result = [
            'success' => false,
            'timestamp' => $this->getCurrentTimestamp(),
            'userid' => $this->userid,
            'recommendations' => [],
            'errors' => []
        ];

        $pythonCode = $this->generateRecommendationScript($triggeredAgents);
        $output = $this->runPythonCode($pythonCode);

        if ($output) {
            $jsonStart = strpos($output, '{"success"');
            if ($jsonStart !== false) {
                $jsonStr = substr($output, $jsonStart);
                $decoded = json_decode($jsonStr, true);
                if ($decoded) {
                    return $decoded;
                }
            }
            $result['errors'][] = "Error [orchestrator_bridge.php:" . __LINE__ . "]: Failed to parse recommendation output";
            $result['raw_output'] = $output;
        }

        return $result;
    }

    /**
     * 학생 상태 분석 조회
     * @return array 분석 결과
     */
    public function getAnalysis(): array {
        $result = [
            'success' => false,
            'timestamp' => $this->getCurrentTimestamp(),
            'userid' => $this->userid,
            'analysis' => [],
            'errors' => []
        ];

        $pythonCode = $this->generateAnalysisScript();
        $output = $this->runPythonCode($pythonCode);

        if ($output) {
            $jsonStart = strpos($output, '{"success"');
            if ($jsonStart !== false) {
                $jsonStr = substr($output, $jsonStart);
                $decoded = json_decode($jsonStr, true);
                if ($decoded) {
                    return $decoded;
                }
            }
            $result['errors'][] = "Error [orchestrator_bridge.php:" . __LINE__ . "]: Failed to parse analysis output";
            $result['raw_output'] = $output;
        }

        return $result;
    }

    /**
     * 얽힘 맵 조회
     * @return array 얽힘 맵 데이터
     */
    public function getEntanglementMap(): array {
        $result = [
            'success' => false,
            'timestamp' => $this->getCurrentTimestamp(),
            'agents' => $this->agents,
            'entanglement' => [],
            'errors' => []
        ];

        $pythonCode = $this->generateEntanglementScript();
        $output = $this->runPythonCode($pythonCode);

        if ($output) {
            $jsonStart = strpos($output, '{"success"');
            if ($jsonStart !== false) {
                $jsonStr = substr($output, $jsonStart);
                $decoded = json_decode($jsonStr, true);
                if ($decoded) {
                    $decoded['agents'] = $this->agents;
                    return $decoded;
                }
            }
            $result['errors'][] = "Error [orchestrator_bridge.php:" . __LINE__ . "]: Failed to parse entanglement output";
            $result['raw_output'] = $output;
        }

        return $result;
    }

    // =========================================================================
    // Getter 메서드
    // =========================================================================

    /**
     * 에이전트 정보 반환
     * @return array 22개 에이전트 정보
     */
    public function getAgents(): array {
        return $this->agents;
    }

    /**
     * 차원 정보 반환
     * @return array 8D 차원 정보
     */
    public function getDimensions(): array {
        return $this->dimensions;
    }

    /**
     * 에러 목록 반환
     * @return array 에러 배열
     */
    public function getErrors(): array {
        return $this->errors;
    }

    // =========================================================================
    // Private 헬퍼 메서드
    // =========================================================================

    /**
     * 현재 타임스탬프 반환
     * @return string ISO 8601 형식 타임스탬프
     */
    private function getCurrentTimestamp(): string {
        return date('Y-m-d\TH:i:s\Z');
    }

    /**
     * 경로 이스케이프
     * @param string $path 원본 경로
     * @return string 이스케이프된 경로
     */
    private function escapePath(string $path): string {
        return str_replace("'", "\\'", $path);
    }

    /**
     * Python 코드 실행
     * @param string $code 실행할 Python 코드
     * @return string|null 실행 결과
     */
    private function runPythonCode(string $code): ?string {
        $tempFile = tempnam(sys_get_temp_dir(), 'qob_');
        file_put_contents($tempFile, $code);

        $cmd = PYTHON_CMD . ' ' . escapeshellarg($tempFile) . ' 2>&1';
        $output = shell_exec($cmd);

        unlink($tempFile);
        return $output;
    }

    /**
     * 에이전트 컨텍스트 수집
     * @return array 에이전트별 컨텍스트 데이터
     */
    private function collectAgentContexts(): array {
        $contexts = [];

        // 기본 컨텍스트 (실제 구현에서는 DB에서 조회)
        foreach ($this->agents as $id => $agent) {
            $contexts[$id] = [
                'active' => true,
                'phase' => $agent['phase'],
                'sample_value' => 0.5 + (($id % 5) * 0.1)
            ];
        }

        // 침착도 에이전트 (Agent 8)
        $contexts[8]['calm_score'] = 0.72;
        $contexts[8]['calmness_level'] = 3;

        // 문제노트 에이전트 (Agent 11)
        $contexts[11]['accuracy_rate'] = 0.85;
        $contexts[11]['total_problems'] = 20;

        // 휴식 루틴 에이전트 (Agent 12)
        $contexts[12]['rest_count'] = 5;
        $contexts[12]['average_interval'] = 55;

        // 목표 설정 에이전트 (Agent 3)
        $contexts[3]['goal_progress'] = 0.6;
        $contexts[3]['goal_effectiveness'] = 0.7;

        // 학습 관리 에이전트 (Agent 9)
        $contexts[9]['pomodoro_completion'] = 0.8;

        // 참여도 분석 에이전트 (Agent 4)
        $contexts[4]['engagement_level'] = 0.75;
        $contexts[4]['dropout_risk'] = 0.15;

        return $contexts;
    }

    // =========================================================================
    // Python 스크립트 생성 메서드
    // =========================================================================

    /**
     * StateVector 계산 Python 스크립트 생성
     * @return string Python 코드
     */
    private function generateStateScript(): string {
        $holonsPath = $this->escapePath(HOLONS_PATH);
        $agentContexts = $this->collectAgentContexts();
        $contextsJson = json_encode($agentContexts, JSON_UNESCAPED_UNICODE);
        $timestamp = $this->getCurrentTimestamp();

        return <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import json
sys.path.insert(0, '{$holonsPath}')

try:
    from _quantum_orchestrator import New8DStateVector, DATA_INTERFACE_AVAILABLE

    agent_contexts = {$contextsJson}

    if DATA_INTERFACE_AVAILABLE:
        state = New8DStateVector.from_agent_data(
            student_id={$this->userid},
            agent_contexts=agent_contexts
        )
    else:
        state = New8DStateVector(
            cognitive_clarity=0.5,
            emotional_stability=0.5,
            engagement_level=0.5,
            concept_mastery=0.5,
            routine_strength=0.5,
            metacognitive_awareness=0.5,
            dropout_risk=0.3,
            intervention_readiness=0.6
        )

    result = {
        'success': True,
        'timestamp': '{$timestamp}',
        'userid': {$this->userid},
        'state_vector': {
            'cognitive_clarity': state.cognitive_clarity,
            'emotional_stability': state.emotional_stability,
            'engagement_level': state.engagement_level,
            'concept_mastery': state.concept_mastery,
            'routine_strength': state.routine_strength,
            'metacognitive_awareness': state.metacognitive_awareness,
            'dropout_risk': state.dropout_risk,
            'intervention_readiness': state.intervention_readiness
        },
        'data_interface_available': DATA_INTERFACE_AVAILABLE,
        'errors': []
    }

    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    print(json.dumps({
        'success': False,
        'error': str(e),
        'errors': [str(e)]
    }, ensure_ascii=False))
PYTHON;
    }

    /**
     * 추천 스크립트 생성
     * @param array $triggeredAgents 트리거된 에이전트 ID 배열
     * @return string Python 코드
     */
    private function generateRecommendationScript(array $triggeredAgents): string {
        $holonsPath = $this->escapePath(HOLONS_PATH);
        $agentContexts = $this->collectAgentContexts();
        $contextsJson = json_encode($agentContexts, JSON_UNESCAPED_UNICODE);
        $triggeredJson = json_encode($triggeredAgents);
        $timestamp = $this->getCurrentTimestamp();

        return <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import json
sys.path.insert(0, '{$holonsPath}')

try:
    from _quantum_orchestrator import (
        QuantumOrchestrator,
        New8DStateVector,
        DATA_INTERFACE_AVAILABLE
    )

    orchestrator = QuantumOrchestrator()
    agent_contexts = {$contextsJson}
    triggered_agents = {$triggeredJson}

    if DATA_INTERFACE_AVAILABLE:
        state = New8DStateVector.from_agent_data(
            student_id={$this->userid},
            agent_contexts=agent_contexts
        )
    else:
        state = New8DStateVector()

    results = orchestrator.suggest_agent_order_from_new8d(
        student_state=state,
        triggered_agents=triggered_agents,
        agent_scenarios={a: 'E' for a in triggered_agents}
    )

    result = {
        'success': True,
        'timestamp': '{$timestamp}',
        'userid': {$this->userid},
        'recommendations': {
            'priority_order': results.get('agent_order', []),
            'priority_scores': results.get('priority_scores', {}),
            'persona_weights': results.get('persona_weights', {}),
            'matched_persona': results.get('matched_persona', 'Unknown')
        },
        'agent_order': results.get('agent_order', []),
        'data_interface_available': DATA_INTERFACE_AVAILABLE,
        'errors': []
    }

    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    import traceback
    print(json.dumps({
        'success': False,
        'error': str(e),
        'traceback': traceback.format_exc(),
        'errors': [str(e)]
    }, ensure_ascii=False))
PYTHON;
    }

    /**
     * 분석 스크립트 생성
     * @return string Python 코드
     */
    private function generateAnalysisScript(): string {
        $holonsPath = $this->escapePath(HOLONS_PATH);
        $agentContexts = $this->collectAgentContexts();
        $contextsJson = json_encode($agentContexts, JSON_UNESCAPED_UNICODE);
        $timestamp = $this->getCurrentTimestamp();

        return <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import json
sys.path.insert(0, '{$holonsPath}')

try:
    from _quantum_orchestrator import (
        QuantumOrchestrator,
        New8DStateVector,
        DATA_INTERFACE_AVAILABLE
    )

    orchestrator = QuantumOrchestrator()
    agent_contexts = {$contextsJson}

    if DATA_INTERFACE_AVAILABLE:
        state = New8DStateVector.from_agent_data(
            student_id={$this->userid},
            agent_contexts=agent_contexts
        )
    else:
        state = New8DStateVector()

    # 상태 분석
    analysis = {
        'risk_level': 'low' if state.dropout_risk < 0.3 else ('medium' if state.dropout_risk < 0.6 else 'high'),
        'intervention_recommended': state.intervention_readiness > 0.5,
        'primary_concerns': [],
        'strengths': [],
        'persona_match': ''
    }

    # 우려 사항 및 강점 분석
    if state.cognitive_clarity < 0.4:
        analysis['primary_concerns'].append('낮은 인지적 명확성')
    if state.emotional_stability < 0.4:
        analysis['primary_concerns'].append('정서적 불안정')
    if state.engagement_level < 0.4:
        analysis['primary_concerns'].append('낮은 참여도')

    if state.concept_mastery > 0.7:
        analysis['strengths'].append('높은 개념 숙달도')
    if state.routine_strength > 0.7:
        analysis['strengths'].append('강한 학습 루틴')
    if state.metacognitive_awareness > 0.7:
        analysis['strengths'].append('우수한 메타인지')

    # 페르소나 매칭
    persona_results = orchestrator.match_persona_from_new8d(state)
    analysis['persona_match'] = persona_results.get('persona', 'Unknown')

    result = {
        'success': True,
        'timestamp': '{$timestamp}',
        'userid': {$this->userid},
        'analysis': analysis,
        'state_summary': {
            'cognitive_clarity': state.cognitive_clarity,
            'emotional_stability': state.emotional_stability,
            'engagement_level': state.engagement_level,
            'dropout_risk': state.dropout_risk
        },
        'data_interface_available': DATA_INTERFACE_AVAILABLE,
        'errors': []
    }

    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    import traceback
    print(json.dumps({
        'success': False,
        'error': str(e),
        'traceback': traceback.format_exc(),
        'errors': [str(e)]
    }, ensure_ascii=False))
PYTHON;
    }

    /**
     * 얽힘 맵 스크립트 생성
     * @return string Python 코드
     */
    private function generateEntanglementScript(): string {
        $holonsPath = $this->escapePath(HOLONS_PATH);
        $timestamp = $this->getCurrentTimestamp();

        return <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import json
sys.path.insert(0, '{$holonsPath}')

try:
    from _quantum_entanglement import EntanglementMap

    em = EntanglementMap()
    map_data = em.get_map()

    # 22x22 행렬 생성
    matrix = {}
    for i in range(1, 23):
        matrix[i] = {}
        for j in range(1, 23):
            if i == j:
                matrix[i][j] = 1.0
            else:
                matrix[i][j] = map_data.get((i, j), map_data.get((j, i), 0.0))

    result = {
        'success': True,
        'timestamp': '{$timestamp}',
        'matrix': matrix,
        'total_connections': len(map_data),
        'errors': []
    }

    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    import traceback
    print(json.dumps({
        'success': False,
        'error': str(e),
        'traceback': traceback.format_exc(),
        'errors': [str(e)]
    }, ensure_ascii=False))
PYTHON;
    }
}

// =============================================================================
// Standalone 실행 (API 모드)
// =============================================================================
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'orchestrator_bridge.php') {
    // Moodle 통합
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
    require_login();

    header('Content-Type: application/json; charset=utf-8');

    $action = $_GET['action'] ?? 'test';
    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;
    $debug = isset($_GET['debug']);

    $bridge = new QuantumOrchestratorBridge($userid, $debug);

    switch ($action) {
        case 'test':
            echo json_encode($bridge->testConnection(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'state':
            echo json_encode($bridge->getCurrentState(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'recommend':
            $agents = isset($_GET['agents']) ? explode(',', $_GET['agents']) : [];
            $agents = array_map('intval', $agents);
            echo json_encode($bridge->getRecommendations($agents), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'analysis':
            echo json_encode($bridge->getAnalysis(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'entanglement':
            echo json_encode($bridge->getEntanglementMap(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'agents':
            echo json_encode(['agents' => $bridge->getAgents()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        case 'dimensions':
            echo json_encode(['dimensions' => $bridge->getDimensions()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode([
                'error' => "Unknown action: $action",
                'available_actions' => ['test', 'state', 'recommend', 'analysis', 'entanglement', 'agents', 'dimensions']
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * DB 테이블 정보
 * ================
 * 이 파일은 Moodle의 기존 테이블을 사용합니다:
 *
 * - mdl_user (id, username, email, ...)
 * - mdl_user_info_data (userid, fieldid, data) - fieldid=22: 사용자 역할
 *
 * 향후 확장 테이블 (계획):
 * - mdl_quantum_agent_state (id, userid, agent_id, state_value, timestamp)
 * - mdl_quantum_student_vector (id, userid, dimension_id, value, timestamp)
 */
