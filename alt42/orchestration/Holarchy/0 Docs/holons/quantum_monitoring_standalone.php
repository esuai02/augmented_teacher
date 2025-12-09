<?php
/**
 * Quantum Monitoring Dashboard - Standalone Version
 * ==================================================
 * 독립형 학생 상태 모니터링 대시보드 (Moodle 의존성 없음)
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/quantum_monitoring_standalone.php
 *
 * 기능:
 *   1. 8D StateVector 실시간 시각화 (레이더 차트)
 *   2. 양자 오케스트레이터 에이전트 추천
 *   3. 상태 분석 및 개입 권장
 *   4. 22개 에이전트 얽힘 맵 시각화
 *
 * API 엔드포인트:
 *   ?action=dashboard       - 대시보드 UI (기본)
 *   ?action=state           - 현재 8D StateVector JSON
 *   ?action=recommendations - 에이전트 추천 JSON
 *   ?action=analysis        - 상태 분석 JSON
 *   ?action=entanglement    - 얽힘 맵 JSON
 *
 * @file    quantum_monitoring_standalone.php
 * @package QuantumOrchestration
 * @version 1.0.0 (Standalone)
 * @created 2025-12-09
 */

// =============================================================================
// 에러 표시 설정
// =============================================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =============================================================================
// 독립형 시뮬레이션 브릿지 클래스
// =============================================================================
/**
 * SimulatedQuantumBridge - Moodle 없이 작동하는 시뮬레이션 데이터 제공
 */
class SimulatedQuantumBridge {
    private $userid;
    private $seed;

    // 22개 에이전트 정의
    private $agents = [
        1  => ['name' => 'Mission Instructor', 'phase' => 1, 'description' => '미션 지시자'],
        2  => ['name' => 'Data Collector', 'phase' => 1, 'description' => '데이터 수집기'],
        3  => ['name' => 'Pattern Analyzer', 'phase' => 1, 'description' => '패턴 분석기'],
        4  => ['name' => 'Knowledge Mapper', 'phase' => 1, 'description' => '지식 매퍼'],
        5  => ['name' => 'State Observer', 'phase' => 1, 'description' => '상태 관찰자'],
        6  => ['name' => 'Real-time Tutor', 'phase' => 2, 'description' => '실시간 튜터'],
        7  => ['name' => 'Emotion Coach', 'phase' => 2, 'description' => '감정 코치'],
        8  => ['name' => 'Feedback Generator', 'phase' => 2, 'description' => '피드백 생성기'],
        9  => ['name' => 'Motivation Booster', 'phase' => 2, 'description' => '동기 부스터'],
        10 => ['name' => 'Attention Monitor', 'phase' => 2, 'description' => '주의력 모니터'],
        11 => ['name' => 'Diagnostic Engine', 'phase' => 3, 'description' => '진단 엔진'],
        12 => ['name' => 'Gap Identifier', 'phase' => 3, 'description' => '갭 식별기'],
        13 => ['name' => 'Path Planner', 'phase' => 3, 'description' => '경로 플래너'],
        14 => ['name' => 'Skill Assessor', 'phase' => 3, 'description' => '스킬 평가자'],
        15 => ['name' => 'Readiness Checker', 'phase' => 3, 'description' => '준비도 체커'],
        16 => ['name' => 'Intervention Designer', 'phase' => 4, 'description' => '개입 설계자'],
        17 => ['name' => 'Content Adapter', 'phase' => 4, 'description' => '콘텐츠 어댑터'],
        18 => ['name' => 'Practice Generator', 'phase' => 4, 'description' => '연습 생성기'],
        19 => ['name' => 'Review Scheduler', 'phase' => 4, 'description' => '복습 스케줄러'],
        20 => ['name' => 'Progress Tracker', 'phase' => 4, 'description' => '진도 추적기'],
        21 => ['name' => 'Meta Orchestrator', 'phase' => 4, 'description' => '메타 오케스트레이터'],
        22 => ['name' => 'Report Generator', 'phase' => 4, 'description' => '리포트 생성기']
    ];

    // 8D 차원 정의
    private $dimensions = [
        ['name' => 'cognitive_clarity', 'label' => '인지 명확성', 'color' => '#58a6ff'],
        ['name' => 'emotional_stability', 'label' => '정서 안정성', 'color' => '#7ee787'],
        ['name' => 'engagement_level', 'label' => '참여 수준', 'color' => '#f0883e'],
        ['name' => 'concept_mastery', 'label' => '개념 숙달도', 'color' => '#a371f7'],
        ['name' => 'routine_strength', 'label' => '루틴 강도', 'color' => '#f778ba'],
        ['name' => 'metacognitive_awareness', 'label' => '메타인지', 'color' => '#79c0ff'],
        ['name' => 'dropout_risk', 'label' => '이탈 위험도', 'color' => '#f85149'],
        ['name' => 'intervention_readiness', 'label' => '개입 준비도', 'color' => '#ffd33d']
    ];

    public function __construct($userid = 1, $usePython = false) {
        $this->userid = $userid;
        $this->seed = $userid * 42; // 재현 가능한 시드
        mt_srand($this->seed);
    }

    /**
     * 8D StateVector 생성
     */
    public function getCurrentState() {
        $stateVector = [];
        foreach ($this->dimensions as $dim) {
            // 시뮬레이션: 0.3 ~ 0.9 사이의 값 생성
            $stateVector[$dim['name']] = round(0.3 + (mt_rand(0, 60) / 100), 2);
        }

        return [
            'success' => true,
            'student_id' => $this->userid,
            'state_vector' => $stateVector,
            'timestamp' => date('Y-m-d H:i:s'),
            'data_interface_available' => true,
            'data_source' => 'simulation'
        ];
    }

    /**
     * 상태 분석 생성
     */
    public function getStateAnalysis() {
        $stateData = $this->getCurrentState();
        $stateVector = $stateData['state_vector'];

        // 평균값 계산
        $avgValue = array_sum($stateVector) / count($stateVector);

        // 위험 수준 판정
        if ($avgValue >= 0.7) {
            $riskLevel = 'Low';
            $recommendation = '현재 학습 상태가 양호합니다. 현재 학습 패턴을 유지하세요.';
        } elseif ($avgValue >= 0.5) {
            $riskLevel = 'Medium';
            $recommendation = '일부 영역에서 개선이 필요합니다. 집중력 향상 활동을 권장합니다.';
        } else {
            $riskLevel = 'High';
            $recommendation = '즉각적인 개입이 필요합니다. 기초 개념 복습을 권장합니다.';
        }

        // 강점/약점 추출
        $sorted = $stateVector;
        asort($sorted);
        $weaknesses = array_slice(array_keys($sorted), 0, 2);
        arsort($sorted);
        $strengths = array_slice(array_keys($sorted), 0, 2);

        // 한글 변환
        $dimLabels = [];
        foreach ($this->dimensions as $dim) {
            $dimLabels[$dim['name']] = $dim['label'];
        }

        return [
            'success' => true,
            'student_id' => $this->userid,
            'analysis' => [
                'risk_level' => $riskLevel,
                'intervention_recommendation' => $recommendation,
                'strengths' => array_map(function($k) use ($dimLabels) { return $dimLabels[$k] ?? $k; }, $strengths),
                'weaknesses' => array_map(function($k) use ($dimLabels) { return $dimLabels[$k] ?? $k; }, $weaknesses),
                'average_score' => round($avgValue, 3),
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'data_source' => 'simulation'
        ];
    }

    /**
     * 에이전트 추천 생성
     */
    public function getRecommendations($triggeredAgents = []) {
        $stateData = $this->getCurrentState();
        $analysisData = $this->getStateAnalysis();

        // 상태 기반 우선순위 점수 계산
        $priorityScores = [];
        foreach ($this->agents as $id => $agent) {
            // 기본 점수: Phase에 따른 가중치 + 랜덤 요소
            $baseScore = (5 - $agent['phase']) * 0.2; // Phase 1이 높은 점수
            $randomFactor = mt_rand(0, 30) / 100;
            $priorityScores[$id] = round($baseScore + $randomFactor, 2);
        }

        // 트리거된 에이전트 보너스
        foreach ($triggeredAgents as $agentId) {
            if (isset($priorityScores[$agentId])) {
                $priorityScores[$agentId] += 0.3;
            }
        }

        // 정렬
        arsort($priorityScores);
        $agentOrder = array_keys($priorityScores);

        // 매칭된 페르소나 결정
        $riskLevel = $analysisData['analysis']['risk_level'] ?? 'Medium';
        $matchedPersona = $riskLevel === 'High' ? 'Intervention Focus' :
                         ($riskLevel === 'Low' ? 'Enhancement Focus' : 'Balanced Support');

        return [
            'success' => true,
            'student_id' => $this->userid,
            'agent_order' => $agentOrder,
            'recommendations' => [
                'matched_persona' => $matchedPersona,
                'priority_scores' => $priorityScores,
                'triggered_agents' => $triggeredAgents,
                'top_5' => array_slice($agentOrder, 0, 5),
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'data_source' => 'simulation'
        ];
    }

    /**
     * 얽힘 맵 생성
     */
    public function getEntanglementMap() {
        $agents = [];
        foreach ($this->agents as $id => $agent) {
            $agents[] = [
                'id' => $id,
                'name' => $agent['name'],
                'phase' => $agent['phase'],
                'description' => $agent['description']
            ];
        }

        // 22x22 얽힘 매트릭스 생성
        $matrix = [];
        foreach ($this->agents as $fromId => $fromAgent) {
            $matrix[$fromId] = [];
            foreach ($this->agents as $toId => $toAgent) {
                if ($fromId === $toId) {
                    $matrix[$fromId][$toId] = 1.0; // 자기 자신
                } else {
                    // 같은 Phase면 연결이 더 강함
                    $phaseBonus = ($fromAgent['phase'] === $toAgent['phase']) ? 0.3 : 0;
                    // 인접 Phase면 중간 연결
                    $adjacentBonus = (abs($fromAgent['phase'] - $toAgent['phase']) === 1) ? 0.15 : 0;
                    // 기본 랜덤 연결
                    $baseConnection = mt_rand(0, 50) / 100;
                    $matrix[$fromId][$toId] = min(1.0, round($baseConnection + $phaseBonus + $adjacentBonus, 2));
                }
            }
        }

        return [
            'success' => true,
            'agents' => $agents,
            'matrix' => $matrix,
            'total_agents' => count($agents),
            'timestamp' => date('Y-m-d H:i:s'),
            'data_source' => 'simulation'
        ];
    }

    /**
     * 에이전트 목록 반환
     */
    public function getAgents() {
        return $this->agents;
    }

    /**
     * 차원 목록 반환
     */
    public function getDimensions() {
        return $this->dimensions;
    }
}

// =============================================================================
// API 요청 처리
// =============================================================================
$action = $_GET['action'] ?? 'dashboard';
$userid = isset($_GET['userid']) ? (int)$_GET['userid'] : 1;
$format = $_GET['format'] ?? 'html';

// 시뮬레이션 브릿지 생성
$bridge = new SimulatedQuantumBridge($userid, false);

// 시뮬레이션 사용자 정보
$simulatedUser = [
    'id' => $userid,
    'username' => 'SimUser_' . $userid,
    'firstname' => 'Test',
    'lastname' => 'User'
];

// JSON API 응답
if ($format === 'json' || in_array($action, ['state', 'recommendations', 'analysis', 'entanglement'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    $result = null;
    switch ($action) {
        case 'state':
            $result = $bridge->getCurrentState();
            break;
        case 'recommendations':
            $triggered = isset($_GET['agents']) ? explode(',', $_GET['agents']) : [];
            $triggered = array_map('intval', $triggered);
            $result = $bridge->getRecommendations($triggered);
            break;
        case 'analysis':
            $result = $bridge->getStateAnalysis();
            break;
        case 'entanglement':
            $result = $bridge->getEntanglementMap();
            break;
        default:
            $result = ['error' => 'Unknown action', 'actions' => ['state', 'recommendations', 'analysis', 'entanglement']];
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 데이터 조회 (대시보드용)
$stateData = $bridge->getCurrentState();
$analysisData = $bridge->getStateAnalysis();
$recommendationsData = $bridge->getRecommendations([5, 8, 10, 12]);
$entanglementData = $bridge->getEntanglementMap();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Monitoring Dashboard - Standalone</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #1a1f2c 100%);
            color: #c9d1d9;
            min-height: 100vh;
        }

        .dashboard {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #30363d;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            color: #58a6ff;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1::before {
            content: '🔮';
            font-size: 28px;
        }

        .phase-badge {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .standalone-badge {
            background: linear-gradient(135deg, #f0883e 0%, #db6d28 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #8b949e;
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f0883e, #db6d28);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .card {
            background: rgba(22, 27, 34, 0.9);
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #21262d;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #c9d1d9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            background: #21262d;
            color: #7ee787;
        }

        /* StateVector Card */
        .state-card { grid-column: span 6; }

        .radar-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Analysis Card */
        .analysis-card { grid-column: span 6; }

        .risk-indicator {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .risk-indicator.low { background: rgba(35, 134, 54, 0.2); border-left: 4px solid #238636; }
        .risk-indicator.medium { background: rgba(240, 136, 62, 0.2); border-left: 4px solid #f0883e; }
        .risk-indicator.high { background: rgba(248, 81, 73, 0.2); border-left: 4px solid #f85149; }

        .risk-icon { font-size: 32px; }
        .risk-details h3 { font-size: 16px; margin-bottom: 5px; }
        .risk-details p { font-size: 13px; color: #8b949e; }

        .analysis-section { margin-top: 20px; }
        .analysis-section h4 { font-size: 12px; color: #8b949e; text-transform: uppercase; margin-bottom: 10px; }

        .tag-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag { padding: 5px 12px; border-radius: 15px; font-size: 12px; }
        .tag.strength { background: rgba(126, 231, 135, 0.2); color: #7ee787; }
        .tag.weakness { background: rgba(248, 81, 73, 0.2); color: #f85149; }

        /* Recommendations Card */
        .recommendations-card { grid-column: span 6; }
        .agent-list { display: flex; flex-direction: column; gap: 10px; }

        .agent-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #21262d;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .agent-item:hover { transform: translateX(5px); }

        .agent-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
        }

        .agent-rank.rank-1 { background: linear-gradient(135deg, #ffd700, #ffb347); color: #000; }
        .agent-rank.rank-2 { background: linear-gradient(135deg, #c0c0c0, #a8a8a8); color: #000; }
        .agent-rank.rank-3 { background: linear-gradient(135deg, #cd7f32, #b87333); color: #fff; }
        .agent-rank.rank-other { background: #30363d; color: #8b949e; }

        .agent-info { flex: 1; }
        .agent-name { font-size: 14px; font-weight: 500; margin-bottom: 3px; }
        .agent-id { font-size: 11px; color: #8b949e; }
        .agent-score { font-size: 16px; font-weight: bold; color: #58a6ff; }

        /* Dimensions Card */
        .dimensions-card { grid-column: span 6; }
        .dimension-bars { display: flex; flex-direction: column; gap: 12px; }
        .dimension-bar { display: flex; align-items: center; gap: 15px; }
        .dimension-label { width: 120px; font-size: 12px; color: #8b949e; }

        .dimension-track {
            flex: 1;
            height: 8px;
            background: #21262d;
            border-radius: 4px;
            overflow: hidden;
        }

        .dimension-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .dimension-value { width: 45px; text-align: right; font-size: 13px; font-weight: 500; }

        /* Entanglement Card */
        .entanglement-card { grid-column: span 12; }
        .entanglement-container { display: flex; gap: 30px; flex-wrap: wrap; }
        .entanglement-matrix { flex: 1; min-width: 400px; overflow-x: auto; }

        .matrix-table { border-collapse: collapse; font-size: 10px; }
        .matrix-table th, .matrix-table td { width: 28px; height: 28px; text-align: center; border: 1px solid #21262d; padding: 2px; }
        .matrix-table th { background: #21262d; color: #8b949e; font-weight: 500; position: sticky; }
        .matrix-table th.row-header { left: 0; z-index: 2; }
        .matrix-table th.col-header { top: 0; z-index: 1; }
        .matrix-table th.corner { z-index: 3; }

        .matrix-cell { cursor: pointer; transition: all 0.2s; }
        .matrix-cell:hover { transform: scale(1.3); z-index: 10; box-shadow: 0 0 10px rgba(88, 166, 255, 0.5); }
        .matrix-cell.self { background: #30363d; }
        .matrix-cell.strength-0 { background: #161b22; }
        .matrix-cell.strength-1 { background: rgba(88, 166, 255, 0.2); }
        .matrix-cell.strength-2 { background: rgba(88, 166, 255, 0.4); }
        .matrix-cell.strength-3 { background: rgba(88, 166, 255, 0.6); }
        .matrix-cell.strength-4 { background: rgba(88, 166, 255, 0.8); }
        .matrix-cell.strength-5 { background: #58a6ff; }

        .phase-legend { flex: 0 0 280px; }

        .phase-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #21262d;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .phase-group.phase-1 { border-color: #7ee787; }
        .phase-group.phase-2 { border-color: #58a6ff; }
        .phase-group.phase-3 { border-color: #f0883e; }
        .phase-group.phase-4 { border-color: #f778ba; }

        .phase-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .phase-1 .phase-title { color: #7ee787; }
        .phase-2 .phase-title { color: #58a6ff; }
        .phase-3 .phase-title { color: #f0883e; }
        .phase-4 .phase-title { color: #f778ba; }

        .phase-agents { display: flex; flex-wrap: wrap; gap: 6px; }
        .agent-chip { padding: 4px 8px; border-radius: 4px; font-size: 10px; background: rgba(255,255,255,0.1); color: #c9d1d9; }

        .strength-legend, .legend-title, .legend-scale, .legend-item, .legend-color {
            background: #21262d;
            padding: 12px;
            border-radius: 6px;
        }

        .legend-title { font-size: 12px; font-weight: 600; color: #c9d1d9; margin-bottom: 10px; border-bottom: 1px solid #30363d; padding-bottom: 6px; }
        .legend-scale { display: flex; flex-direction: column; gap: 4px; padding: 0; background: none; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #8b949e; padding: 0; background: none; }
        .legend-color { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; padding: 0; }
        .legend-color.strength-0 { background: #21262d; }
        .legend-color.strength-2 { background: rgba(88, 166, 255, 0.4); }
        .legend-color.strength-4 { background: rgba(88, 166, 255, 0.8); }
        .legend-color.strength-5 { background: #58a6ff; }

        .entanglement-stats {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #21262d;
        }

        .stat-item { padding: 10px 15px; background: #21262d; border-radius: 6px; text-align: center; }
        .stat-value { font-size: 20px; font-weight: bold; color: #58a6ff; }
        .stat-label { font-size: 11px; color: #8b949e; margin-top: 4px; }

        /* Status Footer */
        .status-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #30363d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #8b949e;
            font-size: 12px;
        }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot.active { background: #238636; }
        .status-dot.simulation { background: #f0883e; }

        .refresh-btn {
            padding: 8px 16px;
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #c9d1d9;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .refresh-btn:hover { background: #30363d; border-color: #58a6ff; }

        .api-links { display: flex; gap: 10px; margin-top: 15px; }
        .api-link { font-size: 11px; color: #58a6ff; text-decoration: none; }
        .api-link:hover { text-decoration: underline; }

        .matrix-tooltip {
            position: fixed;
            padding: 10px 14px;
            background: #1f2937;
            border: 1px solid #30363d;
            border-radius: 8px;
            font-size: 12px;
            color: #c9d1d9;
            pointer-events: none;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .matrix-tooltip .tooltip-title { font-weight: 600; margin-bottom: 6px; color: #58a6ff; }
        .matrix-tooltip .tooltip-strength { display: flex; align-items: center; gap: 8px; }
        .strength-bar { flex: 1; height: 6px; background: #30363d; border-radius: 3px; overflow: hidden; }
        .strength-fill { height: 100%; background: #58a6ff; border-radius: 3px; }

        /* User ID Input */
        .user-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 15px;
        }

        .user-input-container label {
            font-size: 12px;
            color: #8b949e;
        }

        .user-input-container input {
            width: 80px;
            padding: 6px 10px;
            border: 1px solid #30363d;
            border-radius: 6px;
            background: #21262d;
            color: #c9d1d9;
            font-size: 13px;
        }

        .user-input-container button {
            padding: 6px 12px;
            background: #238636;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 12px;
            cursor: pointer;
        }

        .user-input-container button:hover {
            background: #2ea043;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="header">
            <h1>
                Quantum Monitoring Dashboard
                <span class="phase-badge">Phase 8.2</span>
                <span class="standalone-badge">🎲 Standalone</span>
            </h1>
            <div class="user-info">
                <div class="user-input-container">
                    <label for="userIdInput">User ID:</label>
                    <input type="number" id="userIdInput" value="<?php echo $userid; ?>" min="1" max="9999">
                    <button onclick="changeUser()">변경</button>
                </div>
                <div class="avatar"><?php echo strtoupper(substr($simulatedUser['username'], 0, 1)); ?></div>
                <div>
                    <div style="font-weight: 500; color: #c9d1d9;"><?php echo htmlspecialchars($simulatedUser['username']); ?></div>
                    <div style="font-size: 12px;">ID: <?php echo $userid; ?></div>
                </div>
                <button class="refresh-btn" onclick="location.reload()">🔄 새로고침</button>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid">
            <!-- StateVector Radar Chart -->
            <div class="card state-card">
                <div class="card-header">
                    <span class="card-title">📊 8D StateVector</span>
                    <span class="card-badge"><?php echo $stateData['success'] ? '✅ Simulation' : '❌ Error'; ?></span>
                </div>
                <div class="radar-container">
                    <canvas id="stateRadar"></canvas>
                </div>
            </div>

            <!-- State Analysis -->
            <div class="card analysis-card">
                <div class="card-header">
                    <span class="card-title">🔬 상태 분석</span>
                    <span class="card-badge"><?php
                        $risk = $analysisData['analysis']['risk_level'] ?? 'Unknown';
                        echo $risk;
                    ?></span>
                </div>

                <?php
                $riskLevel = strtolower($analysisData['analysis']['risk_level'] ?? 'medium');
                $riskClass = in_array($riskLevel, ['low', 'medium', 'high']) ? $riskLevel : 'medium';
                $riskIcon = ['low' => '✅', 'medium' => '⚠️', 'high' => '🚨'][$riskClass];
                ?>
                <div class="risk-indicator <?php echo $riskClass; ?>">
                    <span class="risk-icon"><?php echo $riskIcon; ?></span>
                    <div class="risk-details">
                        <h3>위험 수준: <?php echo ucfirst($riskLevel); ?></h3>
                        <p><?php echo htmlspecialchars($analysisData['analysis']['intervention_recommendation'] ?? '분석 중...'); ?></p>
                    </div>
                </div>

                <div class="analysis-section">
                    <h4>강점</h4>
                    <div class="tag-list">
                        <?php
                        $strengths = $analysisData['analysis']['strengths'] ?? ['분석 중...'];
                        foreach ($strengths as $s): ?>
                        <span class="tag strength"><?php echo htmlspecialchars($s); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="analysis-section">
                    <h4>개선 필요</h4>
                    <div class="tag-list">
                        <?php
                        $weaknesses = $analysisData['analysis']['weaknesses'] ?? ['분석 중...'];
                        foreach ($weaknesses as $w): ?>
                        <span class="tag weakness"><?php echo htmlspecialchars($w); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Agent Recommendations -->
            <div class="card recommendations-card">
                <div class="card-header">
                    <span class="card-title">🎯 에이전트 추천 순위</span>
                    <span class="card-badge"><?php
                        echo htmlspecialchars($recommendationsData['recommendations']['matched_persona'] ?? 'Unknown');
                    ?></span>
                </div>
                <div class="agent-list">
                    <?php
                    $agentOrder = $recommendationsData['agent_order'] ?? [];
                    $priorityScores = $recommendationsData['recommendations']['priority_scores'] ?? [];
                    $agents = $bridge->getAgents();

                    $rank = 0;
                    foreach (array_slice($agentOrder, 0, 5) as $agentId):
                        $rank++;
                        $agentInfo = $agents[$agentId] ?? ['name' => "Agent $agentId", 'phase' => 0];
                        $score = $priorityScores[$agentId] ?? 0;
                        $rankClass = $rank <= 3 ? "rank-$rank" : "rank-other";
                    ?>
                    <div class="agent-item">
                        <div class="agent-rank <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                        <div class="agent-info">
                            <div class="agent-name"><?php echo htmlspecialchars($agentInfo['name']); ?></div>
                            <div class="agent-id">Agent <?php echo $agentId; ?> · Phase <?php echo $agentInfo['phase']; ?></div>
                        </div>
                        <div class="agent-score"><?php echo number_format($score, 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dimension Bars -->
            <div class="card dimensions-card">
                <div class="card-header">
                    <span class="card-title">📈 차원별 상세</span>
                    <span class="card-badge">8 Dimensions</span>
                </div>
                <div class="dimension-bars">
                    <?php
                    $stateVector = $stateData['state_vector'] ?? [];
                    $dimensions = $bridge->getDimensions();

                    foreach ($dimensions as $idx => $dim):
                        $value = $stateVector[$dim['name']] ?? 0;
                        $percent = $value * 100;
                    ?>
                    <div class="dimension-bar">
                        <span class="dimension-label"><?php echo $dim['label']; ?></span>
                        <div class="dimension-track">
                            <div class="dimension-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $dim['color']; ?>;"></div>
                        </div>
                        <span class="dimension-value" style="color: <?php echo $dim['color']; ?>;"><?php echo number_format($value, 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Entanglement Map -->
            <div class="card entanglement-card">
                <div class="card-header">
                    <span class="card-title">🔗 22 Agent Entanglement Map</span>
                    <span class="card-badge"><?php echo count($entanglementData['agents'] ?? []) . ' Agents'; ?></span>
                </div>
                <div class="entanglement-container">
                    <!-- Matrix Heatmap -->
                    <div class="matrix-section">
                        <table class="matrix-table" id="entanglementMatrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php
                                    $agentsList = $entanglementData['agents'] ?? [];
                                    foreach ($agentsList as $agent):
                                    ?>
                                    <th class="matrix-header phase-<?php echo $agent['phase']; ?>" title="Agent <?php echo $agent['id']; ?>: <?php echo htmlspecialchars($agent['name']); ?>">
                                        <?php echo $agent['id']; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $matrix = $entanglementData['matrix'] ?? [];
                                foreach ($agentsList as $rowAgent):
                                    $rowId = $rowAgent['id'];
                                ?>
                                <tr>
                                    <td class="matrix-header phase-<?php echo $rowAgent['phase']; ?>" title="Agent <?php echo $rowId; ?>: <?php echo htmlspecialchars($rowAgent['name']); ?>">
                                        <?php echo $rowId; ?>
                                    </td>
                                    <?php foreach ($agentsList as $colAgent):
                                        $colId = $colAgent['id'];
                                        $strength = $matrix[$rowId][$colId] ?? 0;
                                        $strengthClass = min(5, max(0, round($strength * 5)));
                                    ?>
                                    <td class="matrix-cell strength-<?php echo $strengthClass; ?>"
                                        data-from="<?php echo $rowId; ?>"
                                        data-to="<?php echo $colId; ?>"
                                        data-strength="<?php echo $strength; ?>"
                                        data-from-name="<?php echo htmlspecialchars($rowAgent['name']); ?>"
                                        data-to-name="<?php echo htmlspecialchars($colAgent['name']); ?>">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phase Legend & Stats -->
                    <div class="phase-legend">
                        <?php
                        $phaseNames = [
                            1 => ['name' => 'Daily Information', 'icon' => '📊'],
                            2 => ['name' => 'Real-time Interaction', 'icon' => '⚡'],
                            3 => ['name' => 'Diagnosis & Preparation', 'icon' => '🔬'],
                            4 => ['name' => 'Intervention & Improvement', 'icon' => '🎯']
                        ];
                        $agentsByPhase = [];
                        foreach ($agentsList as $agent) {
                            $agentsByPhase[$agent['phase']][] = $agent;
                        }
                        foreach ($phaseNames as $phaseNum => $phaseInfo):
                        ?>
                        <div class="phase-group phase-<?php echo $phaseNum; ?>">
                            <div class="phase-title"><?php echo $phaseInfo['icon']; ?> Phase <?php echo $phaseNum; ?>: <?php echo $phaseInfo['name']; ?></div>
                            <div class="phase-agents">
                                <?php foreach ($agentsByPhase[$phaseNum] ?? [] as $agent): ?>
                                <span class="agent-chip"><?php echo $agent['id']; ?>. <?php echo htmlspecialchars(substr($agent['name'], 0, 15)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Strength Legend -->
                        <div class="strength-legend">
                            <div class="legend-title">연결 강도</div>
                            <div class="legend-scale">
                                <span class="legend-item">
                                    <span class="legend-color strength-0"></span>
                                    <span>0 (없음)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-2"></span>
                                    <span>0.4 (약함)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-4"></span>
                                    <span>0.8 (강함)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-5"></span>
                                    <span>1.0 (최대)</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Entanglement Stats -->
                <div class="entanglement-stats">
                    <?php
                    $totalConnections = 0;
                    $strongConnections = 0;
                    $avgStrength = 0;
                    $connectionCount = 0;
                    foreach ($matrix as $rowId => $row) {
                        foreach ($row as $colId => $strength) {
                            if ($rowId != $colId && $strength > 0) {
                                $totalConnections++;
                                $avgStrength += $strength;
                                $connectionCount++;
                                if ($strength >= 0.6) $strongConnections++;
                            }
                        }
                    }
                    $avgStrength = $connectionCount > 0 ? $avgStrength / $connectionCount : 0;
                    ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalConnections; ?></div>
                        <div class="stat-label">총 연결 수</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $strongConnections; ?></div>
                        <div class="stat-label">강한 연결 (≥0.6)</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($avgStrength, 2); ?></div>
                        <div class="stat-label">평균 강도</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($agentsList); ?></div>
                        <div class="stat-label">에이전트 수</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Footer -->
        <div class="status-footer">
            <div>
                <span class="status-dot simulation"></span>
                Data Source: 시뮬레이션 (Moodle 연동 없음)
                &nbsp;|&nbsp;
                최종 업데이트: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            <div class="api-links">
                <a href="?action=state&format=json" class="api-link">📄 State API</a>
                <a href="?action=analysis&format=json" class="api-link">📄 Analysis API</a>
                <a href="?action=recommendations&format=json" class="api-link">📄 Recommendations API</a>
                <a href="?action=entanglement&format=json" class="api-link">📄 Entanglement API</a>
            </div>
        </div>
    </div>

    <!-- Chart.js Radar Chart -->
    <script>
        // StateVector data from PHP
        const stateVector = <?php echo json_encode($stateData['state_vector'] ?? []); ?>;

        // Radar chart
        const ctx = document.getElementById('stateRadar').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: [
                    '인지 명확성',
                    '정서 안정성',
                    '참여 수준',
                    '개념 숙달도',
                    '루틴 강도',
                    '메타인지',
                    '이탈 위험도',
                    '개입 준비도'
                ],
                datasets: [{
                    label: '현재 상태',
                    data: [
                        stateVector.cognitive_clarity || 0,
                        stateVector.emotional_stability || 0,
                        stateVector.engagement_level || 0,
                        stateVector.concept_mastery || 0,
                        stateVector.routine_strength || 0,
                        stateVector.metacognitive_awareness || 0,
                        stateVector.dropout_risk || 0,
                        stateVector.intervention_readiness || 0
                    ],
                    backgroundColor: 'rgba(88, 166, 255, 0.2)',
                    borderColor: '#58a6ff',
                    borderWidth: 2,
                    pointBackgroundColor: '#58a6ff',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#58a6ff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    r: {
                        min: 0,
                        max: 1,
                        ticks: { stepSize: 0.2, color: '#8b949e', backdropColor: 'transparent' },
                        grid: { color: '#30363d' },
                        angleLines: { color: '#30363d' },
                        pointLabels: { color: '#c9d1d9', font: { size: 11 } }
                    }
                }
            }
        });

        // User ID change function
        function changeUser() {
            const newUserId = document.getElementById('userIdInput').value;
            if (newUserId && newUserId > 0) {
                window.location.href = '?userid=' + newUserId;
            }
        }

        // Enter key support for user ID input
        document.getElementById('userIdInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                changeUser();
            }
        });
    </script>

    <!-- Entanglement Matrix Tooltip -->
    <div class="matrix-tooltip" id="matrixTooltip">
        <div class="tooltip-title"></div>
        <div class="tooltip-strength">
            <span>연결 강도:</span>
            <div class="strength-bar">
                <div class="strength-fill"></div>
            </div>
            <span class="strength-value"></span>
        </div>
    </div>

    <script>
        // Entanglement Matrix Tooltip
        (function() {
            const tooltip = document.getElementById('matrixTooltip');
            const cells = document.querySelectorAll('.matrix-cell');

            cells.forEach(cell => {
                cell.addEventListener('mouseenter', function(e) {
                    const fromId = this.dataset.from;
                    const toId = this.dataset.to;
                    const strength = parseFloat(this.dataset.strength) || 0;

                    if (fromId === toId) {
                        tooltip.style.display = 'none';
                        return;
                    }

                    tooltip.querySelector('.tooltip-title').textContent = `Agent ${fromId} → Agent ${toId}`;
                    tooltip.querySelector('.strength-value').textContent = strength.toFixed(2);
                    tooltip.querySelector('.strength-fill').style.width = (strength * 100) + '%';
                    tooltip.style.display = 'block';
                });

                cell.addEventListener('mousemove', function(e) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                });

                cell.addEventListener('mouseleave', function() {
                    tooltip.style.display = 'none';
                });
            });

            cells.forEach(cell => {
                cell.addEventListener('mouseenter', function() {
                    const fromId = this.dataset.from;
                    const toId = this.dataset.to;

                    document.querySelectorAll('.matrix-header').forEach(h => { h.style.opacity = '0.5'; });
                    document.querySelectorAll(`.matrix-header[title*="Agent ${fromId}:"]`).forEach(h => { h.style.opacity = '1'; h.style.fontWeight = 'bold'; });
                    document.querySelectorAll(`.matrix-header[title*="Agent ${toId}:"]`).forEach(h => { h.style.opacity = '1'; h.style.fontWeight = 'bold'; });
                });

                cell.addEventListener('mouseleave', function() {
                    document.querySelectorAll('.matrix-header').forEach(h => { h.style.opacity = '1'; h.style.fontWeight = 'normal'; });
                });
            });
        })();
    </script>
</body>
</html>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * Quantum Monitoring Dashboard - Standalone Version
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 특징:
 *   - Moodle 의존성 없음 (독립 실행 가능)
 *   - 내장 SimulatedQuantumBridge 클래스
 *   - 시뮬레이션 데이터로 모든 기능 테스트 가능
 *
 * API Endpoints:
 *   - ?action=state          → 8D StateVector JSON
 *   - ?action=analysis       → State Analysis JSON
 *   - ?action=recommendations → Agent Recommendations JSON
 *   - ?action=entanglement   → Entanglement Map JSON
 *   - ?userid=N              → 특정 사용자 ID로 시뮬레이션
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
