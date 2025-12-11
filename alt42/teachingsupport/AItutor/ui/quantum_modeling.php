<?php
/**
 * Quantum Modeling - 인지노드 시각화 페이지
 *
 * HybridStateStabilizer + HybridStateTracker 통합 페이지
 * Kalman Filter + Active Ping 기반 학생 상태 추적 및 시각화
 *
 * @package AugmentedTeacher\TeachingSupport\AItutor\UI
 * @version 1.0.0
 * @since 2025-12-11
 *
 * URL: /moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/quantum_modeling.php
 * 파라미터: id (세션 ID 형식: {session_id}_user{user_id}_{date})
 */

$currentFile = __FILE__;

// [quantum_modeling.php:L16] Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// [quantum_modeling.php:L21] 필수 모듈 로드
$quantumModelingPath = dirname(dirname(dirname(__DIR__))) . '/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling';
require_once($quantumModelingPath . '/HybridStateStabilizer.php');

// [quantum_modeling.php:L25] URL 파라미터 파싱
$sessionId = $_GET['id'] ?? '';
$userId = $USER->id;

// 세션 ID에서 사용자 ID 추출 시도 (형식: {session}_user{id}_{date})
if (preg_match('/user(\d+)/', $sessionId, $matches)) {
    $extractedUserId = intval($matches[1]);
    // 권한 확인: 자신의 데이터 또는 교사/관리자인 경우만 허용
    $userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid=? AND fieldid=22", [$USER->id]);
    $role = $userrole->data ?? 'student';

    if ($extractedUserId === $USER->id || in_array($role, ['teacher', 'admin'])) {
        $userId = $extractedUserId;
    }
}

// [quantum_modeling.php:L41] HybridStateStabilizer 초기화
$stabilizer = new HybridStateStabilizer($userId);
$hybridState = $stabilizer->getFullState();

// [quantum_modeling.php:L45] POST 요청 처리 (AJAX 시뮬레이션)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $result = null;

    try {
        switch ($action) {
            case 'fast_loop':
                $sensorData = json_decode($_POST['sensor_data'] ?? '{}', true);
                $result = $stabilizer->fastLoopPredict($sensorData);
                break;

            case 'kalman_correction':
                $eventType = $_POST['event_type'] ?? 'page_view';
                $eventData = json_decode($_POST['event_data'] ?? '{}', true);
                $result = $stabilizer->kalmanCorrection($eventType, $eventData);
                break;

            case 'fire_ping':
                $level = intval($_POST['level'] ?? 1);
                $result = $stabilizer->firePing($level);
                break;

            case 'ping_response':
                $pingId = $_POST['ping_id'] ?? '';
                $responded = $_POST['responded'] === 'true';
                $responseTime = floatval($_POST['response_time'] ?? 0);
                $result = $stabilizer->processPingResponse($pingId, $responded, $responseTime);
                break;

            case 'get_state':
                $result = $stabilizer->getFullState();
                break;

            default:
                throw new Exception("Unknown action: $action");
        }

        echo json_encode([
            'success' => true,
            'result' => $result,
            'state' => $stabilizer->getFullState()
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $currentFile,
            'line' => $e->getLine()
        ]);
    }
    exit;
}

// [quantum_modeling.php:L99] 학생 정보 조회
$student = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname, email');
$studentName = $student ? ($student->lastname . $student->firstname) : '알 수 없음';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>인지노드 시각화 | Quantum Modeling</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .session-info {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.online {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .status-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* 그리드 레이아웃 */
        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .col-12 { grid-column: span 12; }
        .col-8 { grid-column: span 8; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }

        @media (max-width: 1200px) {
            .col-8, .col-6, .col-4 { grid-column: span 12; }
        }

        /* 카드 */
        .card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* 상태 미터 */
        .state-meter {
            position: relative;
            height: 40px;
            background: var(--bg-dark);
            border-radius: 20px;
            overflow: hidden;
            margin: 15px 0;
        }

        .state-meter-fill {
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease, background 0.3s ease;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
        }

        .state-meter-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 700;
            font-size: 1rem;
            color: white;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }

        /* 확신도 표시 */
        .confidence-panel {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .confidence-item {
            background: var(--bg-dark);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .confidence-item .icon {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .confidence-item .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .confidence-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 5px;
        }

        .confidence-item.high .value { color: var(--success); }
        .confidence-item.medium .value { color: var(--warning); }
        .confidence-item.low .value { color: var(--danger); }

        /* 상태 벡터 바 */
        .state-vector-bars {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .state-bar-container {
            flex: 1;
            text-align: center;
        }

        .state-bar {
            height: 120px;
            background: var(--bg-dark);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .state-bar-fill {
            position: absolute;
            bottom: 0;
            width: 100%;
            border-radius: 10px;
            transition: height 0.5s ease;
        }

        .state-bar-fill.focus { background: linear-gradient(to top, #10b981, #34d399); }
        .state-bar-fill.flow { background: linear-gradient(to top, #6366f1, #818cf8); }
        .state-bar-fill.struggle { background: linear-gradient(to top, #f59e0b, #fbbf24); }
        .state-bar-fill.lost { background: linear-gradient(to top, #ef4444, #f87171); }

        .state-bar-label {
            margin-top: 8px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .state-bar-value {
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* 핑 버튼 */
        .ping-buttons {
            display: flex;
            gap: 12px;
            margin: 15px 0;
        }

        .ping-btn {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--border);
            background: var(--bg-dark);
            border-radius: 12px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .ping-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .ping-btn.active {
            background: var(--primary);
            border-color: var(--primary);
        }

        .ping-btn .icon {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 5px;
        }

        .ping-btn .name {
            font-weight: 600;
        }

        .ping-btn .desc {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* 이벤트 버튼 */
        .event-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .event-btn {
            padding: 8px 14px;
            border: 1px solid var(--border);
            background: var(--bg-dark);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .event-btn:hover {
            border-color: var(--primary);
        }

        .event-btn.positive { border-color: var(--success); }
        .event-btn.positive:hover { background: rgba(16, 185, 129, 0.2); }
        .event-btn.negative { border-color: var(--danger); }
        .event-btn.negative:hover { background: rgba(239, 68, 68, 0.2); }

        /* Kalman 시각화 */
        .kalman-viz {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: var(--bg-dark);
            border-radius: 12px;
            margin: 15px 0;
        }

        .kalman-box {
            text-align: center;
            padding: 15px 20px;
            border-radius: 10px;
            min-width: 90px;
        }

        .kalman-box.prediction { background: rgba(99, 102, 241, 0.2); border: 2px solid var(--primary); }
        .kalman-box.measurement { background: rgba(245, 158, 11, 0.2); border: 2px solid var(--warning); }
        .kalman-box.result { background: rgba(16, 185, 129, 0.2); border: 2px solid var(--success); }

        .kalman-box .label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .kalman-box .value {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .kalman-arrow {
            font-size: 1.3rem;
            color: var(--text-secondary);
        }

        .kalman-gain {
            text-align: center;
            padding: 10px 15px;
            background: var(--bg-card);
            border-radius: 8px;
        }

        .kalman-gain .value {
            font-weight: 700;
            color: var(--primary);
        }

        /* 인지노드 네트워크 시각화 */
        .node-network {
            position: relative;
            width: 100%;
            height: 320px;
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            border-radius: 16px;
            overflow: hidden;
        }

        .node-network svg {
            width: 100%;
            height: 100%;
        }

        .cognitive-node {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cognitive-node:hover {
            filter: brightness(1.3);
        }

        .node-circle {
            transition: all 0.5s ease;
        }

        .node-label {
            font-size: 11px;
            font-weight: 600;
            fill: var(--text-primary);
            text-anchor: middle;
            pointer-events: none;
        }

        .node-value {
            font-size: 10px;
            fill: var(--text-secondary);
            text-anchor: middle;
            pointer-events: none;
        }

        .edge-line {
            stroke-linecap: round;
            transition: all 0.5s ease;
        }

        .edge-flow {
            fill: none;
            stroke: rgba(99, 102, 241, 0.6);
            stroke-width: 2;
            stroke-dasharray: 8 4;
            animation: flowAnimation 2s linear infinite;
        }

        @keyframes flowAnimation {
            0% { stroke-dashoffset: 12; }
            100% { stroke-dashoffset: 0; }
        }

        /* 중앙 상태 표시 */
        .central-state {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .central-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .central-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        /* 게이지 원형 */
        .circular-gauge {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto;
        }

        .circular-gauge svg {
            transform: rotate(-90deg);
        }

        .gauge-bg {
            fill: none;
            stroke: var(--bg-dark);
            stroke-width: 10;
        }

        .gauge-fill {
            fill: none;
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease, stroke 0.3s ease;
        }

        .gauge-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .gauge-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .gauge-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* 상태 벡터 레이더 차트 */
        .radar-chart {
            position: relative;
            width: 100%;
            max-width: 280px;
            height: 280px;
            margin: 0 auto;
        }

        .radar-chart svg {
            width: 100%;
            height: 100%;
        }

        .radar-grid {
            fill: none;
            stroke: var(--border);
            stroke-width: 1;
        }

        .radar-axis {
            stroke: var(--border);
            stroke-width: 1;
        }

        .radar-area {
            fill: rgba(99, 102, 241, 0.3);
            stroke: var(--primary);
            stroke-width: 2;
            transition: all 0.5s ease;
        }

        .radar-point {
            fill: var(--primary);
            transition: all 0.3s ease;
        }

        .radar-label {
            font-size: 11px;
            font-weight: 600;
            fill: var(--text-secondary);
        }

        /* 시뮬레이션 로그 */
        .sim-log {
            max-height: 250px;
            overflow-y: auto;
            padding: 10px;
            background: var(--bg-dark);
            border-radius: 8px;
            font-family: 'Fira Code', monospace;
            font-size: 0.75rem;
        }

        .log-entry {
            padding: 6px 10px;
            border-bottom: 1px solid var(--border);
            border-left: 3px solid transparent;
        }

        .log-entry.prediction { border-left-color: var(--primary); }
        .log-entry.event { border-left-color: var(--success); }
        .log-entry.ping { border-left-color: var(--warning); }
        .log-entry.error { border-left-color: var(--danger); }

        /* 실시간 인디케이터 */
        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid var(--primary);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .realtime-indicator .pulse {
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 1s infinite;
        }

        /* 버튼 */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: var(--bg-dark);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>
                ⚛️ 인지노드 시각화
                <span class="status-badge online">
                    <span class="dot"></span>
                    실시간
                </span>
            </h1>
            <div class="session-info">
                <div>학생: <strong><?php echo htmlspecialchars($studentName); ?></strong> (ID: <?php echo $userId; ?>)</div>
                <div>세션: <?php echo htmlspecialchars($sessionId); ?></div>
            </div>
        </div>

        <div class="grid">
            <!-- 인지노드 네트워크 시각화 (전체 너비) -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🧠 인지노드 네트워크</div>
                        <div class="realtime-indicator" style="margin: 0; padding: 8px 12px;">
                            <div class="pulse"></div>
                            <span>실시간 동기화</span>
                            <span style="margin-left: 15px; color: var(--text-secondary);" id="loopCount">0회</span>
                        </div>
                    </div>

                    <div class="node-network" id="nodeNetwork">
                        <svg id="networkSvg" viewBox="0 0 800 320">
                            <!-- 배경 그리드 -->
                            <defs>
                                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(99, 102, 241, 0.1)" stroke-width="1"/>
                                </pattern>
                                <radialGradient id="nodeGlow" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color: var(--primary); stop-opacity: 0.4"/>
                                    <stop offset="100%" style="stop-color: var(--primary); stop-opacity: 0"/>
                                </radialGradient>
                                <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                                    <feGaussianBlur stdDeviation="3" result="blur"/>
                                    <feMerge>
                                        <feMergeNode in="blur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid)"/>

                            <!-- 엣지 (연결선) -->
                            <g id="edgeGroup">
                                <!-- 동적으로 생성됨 -->
                            </g>

                            <!-- 노드 -->
                            <g id="nodeGroup">
                                <!-- 중앙 노드: 현재 상태 -->
                                <g class="cognitive-node" id="node_state" transform="translate(400, 160)">
                                    <circle class="node-circle" r="50" fill="url(#nodeGlow)" filter="url(#glow)"/>
                                    <circle class="node-circle" r="45" fill="var(--bg-card)" stroke="var(--primary)" stroke-width="3"/>
                                    <text class="node-label" y="-8">인지 상태</text>
                                    <text class="node-value" y="12" id="centralStateValue"><?php echo round($hybridState['predicted_state'] * 100); ?>%</text>
                                    <text class="node-value" y="26" style="font-size: 9px;" id="centralStateLabel"><?php echo ucfirst($hybridState['dominant_state']); ?></text>
                                </g>

                                <!-- 집중 노드 -->
                                <g class="cognitive-node" id="node_focus" transform="translate(200, 80)">
                                    <circle class="node-circle" r="35" fill="rgba(16, 185, 129, 0.2)" stroke="var(--success)" stroke-width="2"/>
                                    <text class="node-label" y="-5">🎯 집중</text>
                                    <text class="node-value" y="12" id="focusNodeValue"><?php echo round(($hybridState['state_vector']['focus'] ?? 0.5) * 100); ?>%</text>
                                </g>

                                <!-- 몰입 노드 -->
                                <g class="cognitive-node" id="node_flow" transform="translate(600, 80)">
                                    <circle class="node-circle" r="35" fill="rgba(99, 102, 241, 0.2)" stroke="var(--primary)" stroke-width="2"/>
                                    <text class="node-label" y="-5">🌊 몰입</text>
                                    <text class="node-value" y="12" id="flowNodeValue"><?php echo round(($hybridState['state_vector']['flow'] ?? 0.3) * 100); ?>%</text>
                                </g>

                                <!-- 고군분투 노드 -->
                                <g class="cognitive-node" id="node_struggle" transform="translate(200, 250)">
                                    <circle class="node-circle" r="35" fill="rgba(245, 158, 11, 0.2)" stroke="var(--warning)" stroke-width="2"/>
                                    <text class="node-label" y="-5">💪 고군분투</text>
                                    <text class="node-value" y="12" id="struggleNodeValue"><?php echo round(($hybridState['state_vector']['struggle'] ?? 0.2) * 100); ?>%</text>
                                </g>

                                <!-- 이탈 노드 -->
                                <g class="cognitive-node" id="node_lost" transform="translate(600, 250)">
                                    <circle class="node-circle" r="35" fill="rgba(239, 68, 68, 0.2)" stroke="var(--danger)" stroke-width="2"/>
                                    <text class="node-label" y="-5">😶 이탈</text>
                                    <text class="node-value" y="12" id="lostNodeValue"><?php echo round(($hybridState['state_vector']['lost'] ?? 0.1) * 100); ?>%</text>
                                </g>

                                <!-- 센서 입력 노드 -->
                                <g class="cognitive-node" id="node_sensor" transform="translate(80, 160)">
                                    <circle class="node-circle" r="25" fill="rgba(139, 92, 246, 0.2)" stroke="#8b5cf6" stroke-width="2"/>
                                    <text class="node-label" y="-3">🖱️</text>
                                    <text class="node-value" y="10" style="font-size: 8px;">센서</text>
                                </g>

                                <!-- Kalman 필터 노드 -->
                                <g class="cognitive-node" id="node_kalman" transform="translate(720, 160)">
                                    <circle class="node-circle" r="25" fill="rgba(14, 165, 233, 0.2)" stroke="#0ea5e9" stroke-width="2"/>
                                    <text class="node-label" y="-3">⚖️</text>
                                    <text class="node-value" y="10" style="font-size: 8px;">Kalman</text>
                                </g>

                                <!-- 확신도 노드 -->
                                <g class="cognitive-node" id="node_confidence" transform="translate(400, 50)">
                                    <circle class="node-circle" r="28" fill="rgba(34, 197, 94, 0.2)" stroke="#22c55e" stroke-width="2"/>
                                    <text class="node-label" y="-3">📊</text>
                                    <text class="node-value" y="10" id="confNodeValue"><?php echo round($hybridState['confidence'] * 100); ?>%</text>
                                </g>

                                <!-- Ping 노드 -->
                                <g class="cognitive-node" id="node_ping" transform="translate(400, 280)">
                                    <circle class="node-circle" r="28" fill="rgba(251, 146, 60, 0.2)" stroke="#fb923c" stroke-width="2" id="pingNodeCircle"/>
                                    <text class="node-label" y="-3">📡</text>
                                    <text class="node-value" y="10" id="pingNodeStatus"><?php echo $hybridState['needs_ping'] ? '필요' : 'OK'; ?></text>
                                </g>
                            </g>

                            <!-- 데이터 플로우 애니메이션 -->
                            <g id="flowAnimations">
                                <!-- 센서 → 상태 -->
                                <path class="edge-flow" d="M 105 160 Q 250 160 355 160" id="flowSensorState"/>
                                <!-- 상태 → Kalman -->
                                <path class="edge-flow" d="M 445 160 Q 580 160 695 160" id="flowStateKalman"/>
                            </g>
                        </svg>

                        <!-- 중앙 상태 오버레이 (숨김 - SVG에서 표시) -->
                    </div>
                </div>
            </div>

            <!-- 메인 상태 모니터 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 상태 분석</div>
                    </div>

                    <!-- 집중도 원형 게이지 -->
                    <div style="display: flex; align-items: center; gap: 30px;">
                        <div class="circular-gauge">
                            <svg viewBox="0 0 140 140">
                                <circle class="gauge-bg" cx="70" cy="70" r="55"/>
                                <circle class="gauge-fill" id="mainGaugeFill" cx="70" cy="70" r="55"
                                        stroke="var(--success)"
                                        stroke-dasharray="345.58"
                                        stroke-dashoffset="<?php echo 345.58 * (1 - $hybridState['predicted_state']); ?>"/>
                            </svg>
                            <div class="gauge-center">
                                <div class="gauge-value" id="stateValue" style="color: var(--success);"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                                <div class="gauge-label">집중도</div>
                            </div>
                        </div>

                        <div style="flex: 1;">
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="font-size: 0.85rem;">현재 상태</span>
                                    <span id="stateLabel" style="font-weight: 600; color: var(--primary);"><?php echo ucfirst($hybridState['dominant_state']); ?></span>
                                </div>
                                <div class="state-meter">
                                    <div class="state-meter-fill" id="stateMeterFill"
                                         style="width: <?php echo $hybridState['predicted_state'] * 100; ?>%"></div>
                                </div>
                            </div>

                            <!-- 미니 확신도/불확실성 표시 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div style="background: var(--bg-dark); padding: 10px; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 0.7rem; color: var(--text-secondary);">확신도</div>
                                    <div style="font-size: 1.2rem; font-weight: 700;" id="confidenceValue"><?php echo round($conf * 100); ?>%</div>
                                </div>
                                <div style="background: var(--bg-dark); padding: 10px; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 0.7rem; color: var(--text-secondary);">불확실성</div>
                                    <div style="font-size: 1.2rem; font-weight: 700;" id="uncertaintyValue"><?php echo round($hybridState['uncertainty'] * 100); ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kalman 시각화 -->
                    <div id="kalmanViz" style="margin-top: 20px;">
                        <h5 style="font-size: 0.85rem; margin-bottom: 10px;">⚖️ Kalman Filter 상태</h5>
                        <div class="kalman-viz">
                            <div class="kalman-box prediction">
                                <div class="label">예측</div>
                                <div class="value" id="kalmanPred"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-gain">
                                <div class="label">K</div>
                                <div class="value" id="kalmanK">0.50</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-box measurement">
                                <div class="label">측정</div>
                                <div class="value" id="kalmanMeas">-</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-box result">
                                <div class="label">보정</div>
                                <div class="value" id="kalmanRes"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상태 벡터 레이더 차트 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎯 상태 벡터 분포</div>
                    </div>

                    <div class="radar-chart" id="radarChart">
                        <svg viewBox="0 0 280 280">
                            <!-- 배경 그리드 (동심원) -->
                            <g transform="translate(140, 140)">
                                <circle class="radar-grid" r="100" opacity="0.3"/>
                                <circle class="radar-grid" r="75" opacity="0.2"/>
                                <circle class="radar-grid" r="50" opacity="0.15"/>
                                <circle class="radar-grid" r="25" opacity="0.1"/>

                                <!-- 축선 -->
                                <line class="radar-axis" x1="0" y1="-100" x2="0" y2="100"/>
                                <line class="radar-axis" x1="-100" y1="0" x2="100" y2="0"/>
                                <line class="radar-axis" x1="-71" y1="-71" x2="71" y2="71"/>
                                <line class="radar-axis" x1="71" y1="-71" x2="-71" y2="71"/>

                                <!-- 레이더 영역 (상태 벡터) -->
                                <polygon class="radar-area" id="radarArea" points="0,-50 50,0 0,50 -50,0"/>

                                <!-- 포인트 -->
                                <circle class="radar-point" id="radarFocus" cx="0" cy="-50" r="6"/>
                                <circle class="radar-point" id="radarFlow" cx="50" cy="0" r="6"/>
                                <circle class="radar-point" id="radarStruggle" cx="0" cy="50" r="6"/>
                                <circle class="radar-point" id="radarLost" cx="-50" cy="0" r="6"/>
                            </g>

                            <!-- 레이블 -->
                            <text class="radar-label" x="140" y="25" text-anchor="middle">🎯 집중</text>
                            <text class="radar-label" x="265" y="145" text-anchor="end">🌊 몰입</text>
                            <text class="radar-label" x="140" y="270" text-anchor="middle">💪 고군분투</text>
                            <text class="radar-label" x="15" y="145" text-anchor="start">😶 이탈</text>
                        </svg>
                    </div>

                    <!-- 상태 벡터 값 표시 -->
                    <div class="state-vector-bars" style="margin-top: 20px;">
                        <?php
                        $stateVector = $hybridState['state_vector'];
                        $stateLabels = ['focus' => '집중', 'flow' => '몰입', 'struggle' => '고군분투', 'lost' => '이탈'];
                        $stateIcons = ['focus' => '🎯', 'flow' => '🌊', 'struggle' => '💪', 'lost' => '😶'];
                        $stateColors = ['focus' => 'var(--success)', 'flow' => 'var(--primary)', 'struggle' => 'var(--warning)', 'lost' => 'var(--danger)'];
                        foreach ($stateVector as $state => $value):
                        ?>
                        <div class="state-bar-container">
                            <div class="state-bar" style="height: 80px;">
                                <div class="state-bar-fill <?php echo $state; ?>"
                                     id="stateBar_<?php echo $state; ?>"
                                     style="height: <?php echo $value * 100; ?>%"></div>
                            </div>
                            <div class="state-bar-label">
                                <?php echo $stateIcons[$state]; ?>
                            </div>
                            <div class="state-bar-value" id="stateBarValue_<?php echo $state; ?>" style="color: <?php echo $stateColors[$state]; ?>;">
                                <?php echo round($value * 100); ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 컨트롤 패널 (전체 너비) -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎛️ 시뮬레이션 컨트롤</div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <!-- Active Ping 버튼 -->
                        <div>
                            <h5 style="font-size: 0.85rem; margin-bottom: 10px;">📡 Active Ping</h5>
                            <div class="ping-buttons">
                                <button class="ping-btn" onclick="firePing(1)" id="pingBtn1">
                                    <span class="icon">💡</span>
                                    <span class="name">Subtle</span>
                                    <span class="desc">미세 자극</span>
                                </button>
                                <button class="ping-btn" onclick="firePing(2)" id="pingBtn2">
                                    <span class="icon">💬</span>
                                    <span class="name">Nudge</span>
                                    <span class="desc">넛지</span>
                                </button>
                                <button class="ping-btn" onclick="firePing(3)" id="pingBtn3">
                                    <span class="icon">❓</span>
                                    <span class="name">Alert</span>
                                    <span class="desc">직접 질문</span>
                                </button>
                            </div>
                        </div>

                        <!-- 이벤트 시뮬레이션 -->
                        <div>
                            <h5 style="font-size: 0.85rem; margin-bottom: 10px;">⚡ 이벤트 (Kalman Correction)</h5>
                            <div class="event-buttons">
                                <button class="event-btn positive" onclick="simulateEvent('correct_answer')">✅ 정답</button>
                                <button class="event-btn positive" onclick="simulateEvent('quick_response')">⚡ 빠른응답</button>
                                <button class="event-btn" onclick="simulateEvent('scroll_active')">📜 스크롤</button>
                                <button class="event-btn negative" onclick="simulateEvent('hint_click')">💡 힌트</button>
                                <button class="event-btn negative" onclick="simulateEvent('wrong_answer')">❌ 오답</button>
                                <button class="event-btn negative" onclick="simulateEvent('skip_problem')">⏭️ 건너뛰기</button>
                                <button class="event-btn negative" onclick="simulateEvent('long_pause')">⏸️ 긴멈춤</button>
                            </div>
                        </div>

                        <!-- Fast Loop 시뮬레이션 -->
                        <div>
                            <h5 style="font-size: 0.85rem; margin-bottom: 10px;">🔄 센서 데이터</h5>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <button class="btn btn-secondary" onclick="simulateSensor('active')" style="flex: 1;">
                                    🖱️ 활발
                                </button>
                                <button class="btn btn-secondary" onclick="simulateSensor('idle')" style="flex: 1;">
                                    😴 Idle
                                </button>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-primary" onclick="toggleAutoLoop()" id="autoLoopBtn" style="flex: 1;">
                                    ▶️ 자동 루프
                                </button>
                                <button class="btn btn-secondary" onclick="resetState()" style="flex: 1;">
                                    🔄 초기화
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 시뮬레이션 로그 -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📋 시뮬레이션 로그</div>
                        <button class="btn btn-secondary" onclick="clearLog()" style="padding: 5px 10px; font-size: 0.75rem;">
                            🗑️ 지우기
                        </button>
                    </div>
                    <div class="sim-log" id="simLog">
                        <div class="log-entry prediction">🚀 [<?php echo date('H:i:s'); ?>] 시스템 초기화 완료 | 초기 상태: <?php echo round($hybridState['predicted_state'] * 100); ?>% 집중</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HybridStateTracker.js 로드 -->
    <script src="<?php echo $CFG->wwwroot; ?>/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/assets/js/HybridStateTracker.js"></script>

    <script>
        // [quantum_modeling.php:JS] 상태 관리
        let hybridState = <?php echo json_encode($hybridState); ?>;
        let loopCount = 0;
        let fastLoopId = null;

        const CONFIDENCE_DECAY = 0.99;
        const UNCERTAINTY_GROWTH = 1.05;
        const PING_THRESHOLD = 0.4;

        const EVENT_SIGNALS = {
            'correct_answer': 0.9,
            'quick_response': 0.85,
            'scroll_active': 0.7,
            'mouse_movement': 0.6,
            'click_problem': 0.75,
            'page_view': 0.5,
            'idle_short': 0.4,
            'hint_click': 0.2,
            'wrong_answer': 0.3,
            'skip_problem': 0.15,
            'long_pause': 0.25,
            'tab_switch': 0.1,
            'idle_long': 0.1
        };

        // 노드 엣지 그리기
        function drawNetworkEdges() {
            const edgeGroup = document.getElementById('edgeGroup');
            edgeGroup.innerHTML = '';

            const edges = [
                // 센서 → 중앙 상태
                { from: [105, 160], to: [355, 160], weight: 0.8, color: '#8b5cf6' },
                // 중앙 상태 → Kalman
                { from: [445, 160], to: [695, 160], weight: 0.6, color: '#0ea5e9' },
                // 중앙 → 집중
                { from: [380, 120], to: [235, 95], weight: hybridState.state_vector?.focus || 0.5, color: '#10b981' },
                // 중앙 → 몰입
                { from: [420, 120], to: [565, 95], weight: hybridState.state_vector?.flow || 0.3, color: '#6366f1' },
                // 중앙 → 고군분투
                { from: [380, 200], to: [235, 235], weight: hybridState.state_vector?.struggle || 0.2, color: '#f59e0b' },
                // 중앙 → 이탈
                { from: [420, 200], to: [565, 235], weight: hybridState.state_vector?.lost || 0.1, color: '#ef4444' },
                // 중앙 → 확신도
                { from: [400, 115], to: [400, 78], weight: hybridState.confidence || 0.5, color: '#22c55e' },
                // 중앙 → Ping
                { from: [400, 205], to: [400, 252], weight: hybridState.needs_ping ? 0.9 : 0.3, color: '#fb923c' }
            ];

            edges.forEach(edge => {
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('class', 'edge-line');
                line.setAttribute('x1', edge.from[0]);
                line.setAttribute('y1', edge.from[1]);
                line.setAttribute('x2', edge.to[0]);
                line.setAttribute('y2', edge.to[1]);
                line.setAttribute('stroke', edge.color);
                line.setAttribute('stroke-width', Math.max(1, edge.weight * 4));
                line.setAttribute('opacity', Math.max(0.3, edge.weight));
                edgeGroup.appendChild(line);
            });
        }

        // 레이더 차트 업데이트
        function updateRadarChart(stateVector) {
            const sv = stateVector || hybridState.state_vector || { focus: 0.5, flow: 0.3, struggle: 0.2, lost: 0.1 };
            const maxRadius = 100;

            // 포인트 계산 (상단=focus, 우측=flow, 하단=struggle, 좌측=lost)
            const points = {
                focus: { x: 0, y: -sv.focus * maxRadius },
                flow: { x: sv.flow * maxRadius, y: 0 },
                struggle: { x: 0, y: sv.struggle * maxRadius },
                lost: { x: -sv.lost * maxRadius, y: 0 }
            };

            // 폴리곤 포인트 문자열
            const polygonPoints = `${points.focus.x},${points.focus.y} ${points.flow.x},${points.flow.y} ${points.struggle.x},${points.struggle.y} ${points.lost.x},${points.lost.y}`;

            const radarArea = document.getElementById('radarArea');
            if (radarArea) radarArea.setAttribute('points', polygonPoints);

            // 각 포인트 원 위치 업데이트
            ['focus', 'flow', 'struggle', 'lost'].forEach(key => {
                const pointEl = document.getElementById('radar' + key.charAt(0).toUpperCase() + key.slice(1));
                if (pointEl) {
                    pointEl.setAttribute('cx', points[key].x);
                    pointEl.setAttribute('cy', points[key].y);
                }
            });
        }

        // 원형 게이지 업데이트
        function updateCircularGauge(value) {
            const circumference = 345.58; // 2 * PI * 55
            const offset = circumference * (1 - value);
            const gaugeFill = document.getElementById('mainGaugeFill');

            if (gaugeFill) {
                gaugeFill.setAttribute('stroke-dashoffset', offset);

                // 색상 변경
                let color = '#ef4444'; // 빨강
                if (value >= 0.7) color = '#10b981'; // 녹색
                else if (value >= 0.4) color = '#f59e0b'; // 노랑

                gaugeFill.setAttribute('stroke', color);
            }
        }

        // 네트워크 노드 업데이트
        function updateNetworkNodes(state) {
            // 중앙 상태 노드
            const centralValue = document.getElementById('centralStateValue');
            const centralLabel = document.getElementById('centralStateLabel');
            if (centralValue) centralValue.textContent = Math.round(state.predicted_state * 100) + '%';
            if (centralLabel) centralLabel.textContent = { 'focus': 'Focus', 'flow': 'Flow', 'struggle': 'Struggle', 'lost': 'Lost' }[state.dominant_state] || 'Focus';

            // 상태 벡터 노드들
            const nodeMap = { focus: 'focusNodeValue', flow: 'flowNodeValue', struggle: 'struggleNodeValue', lost: 'lostNodeValue' };
            Object.entries(state.state_vector || {}).forEach(([key, val]) => {
                const el = document.getElementById(nodeMap[key]);
                if (el) el.textContent = Math.round(val * 100) + '%';
            });

            // 확신도 노드
            const confNode = document.getElementById('confNodeValue');
            if (confNode) confNode.textContent = Math.round(state.confidence * 100) + '%';

            // Ping 노드
            const pingStatus = document.getElementById('pingNodeStatus');
            const pingCircle = document.getElementById('pingNodeCircle');
            if (pingStatus) pingStatus.textContent = state.needs_ping ? '필요' : 'OK';
            if (pingCircle) {
                pingCircle.setAttribute('fill', state.needs_ping ? 'rgba(251, 146, 60, 0.4)' : 'rgba(251, 146, 60, 0.2)');
                pingCircle.setAttribute('stroke-width', state.needs_ping ? '3' : '2');
            }
        }

        // UI 업데이트
        function updateUI(state) {
            if (!state) state = hybridState;

            // 원형 게이지 업데이트
            updateCircularGauge(state.predicted_state);

            // 집중도 미터
            const stateMeterFill = document.getElementById('stateMeterFill');
            if (stateMeterFill) stateMeterFill.style.width = (state.predicted_state * 100) + '%';

            const stateValue = document.getElementById('stateValue');
            if (stateValue) stateValue.textContent = Math.round(state.predicted_state * 100) + '%';

            const stateLabel = document.getElementById('stateLabel');
            if (stateLabel) stateLabel.textContent = { 'focus': 'Focus', 'flow': 'Flow', 'struggle': 'Struggle', 'lost': 'Lost' }[state.dominant_state] || 'Focus';

            // 확신도
            const confValue = document.getElementById('confidenceValue');
            if (confValue) confValue.textContent = Math.round(state.confidence * 100) + '%';

            const uncValue = document.getElementById('uncertaintyValue');
            if (uncValue) uncValue.textContent = Math.round(state.uncertainty * 100) + '%';

            // 상태 벡터 바
            for (const [key, val] of Object.entries(state.state_vector || {})) {
                const bar = document.getElementById('stateBar_' + key);
                const value = document.getElementById('stateBarValue_' + key);
                if (bar) bar.style.height = (val * 100) + '%';
                if (value) value.textContent = Math.round(val * 100) + '%';
            }

            // 루프 카운트
            const loopCountEl = document.getElementById('loopCount');
            if (loopCountEl) loopCountEl.textContent = loopCount + '회';

            // 레이더 차트 업데이트
            updateRadarChart(state.state_vector);

            // 네트워크 노드 업데이트
            updateNetworkNodes(state);

            // 네트워크 엣지 다시 그리기
            drawNetworkEdges();
        }

        // 로그 추가
        function addLog(message, type = 'prediction') {
            const log = document.getElementById('simLog');
            const entry = document.createElement('div');
            entry.className = 'log-entry ' + type;
            entry.textContent = '[' + new Date().toLocaleTimeString() + '] ' + message;
            log.insertBefore(entry, log.firstChild);

            // 최대 100개
            while (log.children.length > 100) {
                log.removeChild(log.lastChild);
            }
        }

        function clearLog() {
            document.getElementById('simLog').innerHTML = '';
            addLog('로그가 초기화되었습니다', 'prediction');
        }

        // 상태 벡터 업데이트 (클라이언트 측)
        function updateStateVector(state) {
            const s = state;
            if (s >= 0.7) {
                return { focus: s, flow: s - 0.2, struggle: 0.1, lost: 0.0 };
            } else if (s >= 0.4) {
                return { focus: s, flow: Math.max(0, s - 0.4), struggle: 0.5 - Math.abs(s - 0.5), lost: Math.max(0, 0.4 - s) };
            } else {
                return { focus: s, flow: 0.0, struggle: s, lost: 1.0 - s };
            }
        }

        // API 호출
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const [key, val] of Object.entries(data)) {
                formData.append(key, typeof val === 'object' ? JSON.stringify(val) : val);
            }

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            return response.json();
        }

        // Fast Loop 시뮬레이션
        async function simulateSensor(type) {
            loopCount++;

            const sensorData = type === 'active' ? {
                mouse_velocity: 1.2,
                scroll_rate: 2,
                keystroke_rate: 1,
                pause_duration: 1
            } : {
                mouse_velocity: 0,
                scroll_rate: 0,
                keystroke_rate: 0,
                pause_duration: 10
            };

            try {
                const result = await apiCall('fast_loop', { sensor_data: sensorData });

                if (result.success) {
                    hybridState = result.state;
                    updateUI(hybridState);

                    const msg = type === 'active'
                        ? '🖱️ 활발한 활동 감지 → 집중도 상승'
                        : '😴 Idle 감지 → 확신도 감쇠 (Decoherence)';
                    addLog(msg, 'prediction');

                    if (hybridState.needs_ping) {
                        addLog('⚠️ 확신도 임계값 이하! Active Ping 권장', 'ping');
                    }
                }
            } catch (error) {
                addLog('❌ 오류: ' + error.message, 'error');
            }
        }

        // Active Ping 발사
        async function firePing(level) {
            const pingNames = {1: 'Subtle (미세 자극)', 2: 'Nudge (넛지)', 3: 'Alert (직접 질문)'};
            addLog('📡 Active Ping 발사: ' + pingNames[level], 'ping');

            // 버튼 활성화
            document.querySelectorAll('.ping-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('pingBtn' + level).classList.add('active');

            try {
                const result = await apiCall('fire_ping', { level });

                if (result.success) {
                    // 시뮬레이션: 1.5초 후 반응 처리
                    setTimeout(async () => {
                        const responded = Math.random() > 0.3;
                        const responseTime = Math.random() * 3;

                        const respResult = await apiCall('ping_response', {
                            ping_id: result.result.ping.id,
                            responded: responded ? 'true' : 'false',
                            response_time: responseTime
                        });

                        if (respResult.success) {
                            hybridState = respResult.state;
                            updateUI(hybridState);

                            const msg = responded
                                ? '✅ 반응 감지! (' + responseTime.toFixed(1) + '초) → 상태 붕괴: Focus'
                                : '❌ 무반응 → 상태 붕괴: Lost';
                            addLog(msg, 'event');
                        }

                        document.querySelectorAll('.ping-btn').forEach(btn => btn.classList.remove('active'));
                    }, 1500);
                }
            } catch (error) {
                addLog('❌ Ping 오류: ' + error.message, 'error');
                document.querySelectorAll('.ping-btn').forEach(btn => btn.classList.remove('active'));
            }
        }

        // 이벤트 시뮬레이션 (Kalman Correction)
        async function simulateEvent(eventType) {
            try {
                const prevState = hybridState.predicted_state;
                const result = await apiCall('kalman_correction', {
                    event_type: eventType,
                    event_data: {}
                });

                if (result.success) {
                    hybridState = result.state;
                    updateUI(hybridState);

                    // Kalman 시각화 업데이트
                    const viz = document.getElementById('kalmanViz');
                    viz.style.display = 'block';

                    document.getElementById('kalmanPred').textContent = Math.round(prevState * 100) + '%';
                    document.getElementById('kalmanMeas').textContent = Math.round(EVENT_SIGNALS[eventType] * 100) + '%';
                    document.getElementById('kalmanK').textContent = result.result.kalman_gain?.toFixed(2) || '-';
                    document.getElementById('kalmanRes').textContent = Math.round(hybridState.predicted_state * 100) + '%';

                    addLog('⚡ [' + eventType + '] Kalman 보정: ' +
                           Math.round(prevState * 100) + '% → ' +
                           Math.round(hybridState.predicted_state * 100) + '%', 'event');
                }
            } catch (error) {
                addLog('❌ 이벤트 오류: ' + error.message, 'error');
            }
        }

        // 자동 루프 상태
        let autoLoopRunning = false;

        // 자동 Fast Loop 토글
        function toggleAutoLoop() {
            const btn = document.getElementById('autoLoopBtn');
            if (autoLoopRunning) {
                clearInterval(fastLoopId);
                fastLoopId = null;
                autoLoopRunning = false;
                btn.textContent = '▶️ 자동 루프';
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-primary');
                addLog('🛑 자동 루프 중지', 'prediction');
            } else {
                startAutoLoop();
                autoLoopRunning = true;
                btn.textContent = '⏹️ 중지';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-danger');
                addLog('▶️ 자동 루프 시작 (0.5초 주기)', 'prediction');
            }
        }

        // 자동 Fast Loop
        function startAutoLoop() {
            fastLoopId = setInterval(async () => {
                loopCount++;
                const loopCountEl = document.getElementById('loopCount');
                if (loopCountEl) loopCountEl.textContent = loopCount + '회';

                // 랜덤 센서 시뮬레이션 (실제로는 HybridStateTracker.js에서 처리)
                const randomActive = Math.random() > 0.3;
                const sensorData = randomActive ? {
                    mouse_velocity: Math.random() * 2,
                    scroll_rate: Math.random() * 3,
                    keystroke_rate: Math.random() * 2,
                    pause_duration: Math.random() * 2
                } : {
                    mouse_velocity: 0,
                    scroll_rate: 0,
                    keystroke_rate: 0,
                    pause_duration: 5 + Math.random() * 10
                };

                try {
                    const result = await apiCall('fast_loop', { sensor_data: sensorData });
                    if (result.success) {
                        hybridState = result.state;
                        updateUI(hybridState);

                        // Ping 필요 알림 (10회마다 한번)
                        if (hybridState.needs_ping && loopCount % 10 === 0) {
                            addLog('⚠️ 확신도 저하: Active Ping 권장', 'ping');
                        }
                    }
                } catch (error) {
                    // 조용히 처리
                }
            }, 500);
        }

        // 상태 초기화
        async function resetState() {
            try {
                const result = await apiCall('get_state', {});
                if (result.success) {
                    hybridState = result.state;
                    loopCount = 0;
                    updateUI(hybridState);
                    addLog('🔄 상태 초기화 완료', 'prediction');
                }
            } catch (error) {
                addLog('❌ 초기화 오류: ' + error.message, 'error');
            }
        }

        // 노드 클릭 이벤트
        function setupNodeInteraction() {
            const nodes = document.querySelectorAll('.cognitive-node');
            nodes.forEach(node => {
                node.addEventListener('click', () => {
                    const nodeId = node.id.replace('node_', '');
                    const nodeInfo = {
                        'state': { name: '인지 상태', desc: '학생의 현재 인지 상태 (집중도)' },
                        'focus': { name: '집중', desc: '학습에 집중하고 있는 상태' },
                        'flow': { name: '몰입', desc: '깊이 몰입하여 학습 효율이 최고인 상태' },
                        'struggle': { name: '고군분투', desc: '어려움을 겪고 있지만 노력 중인 상태' },
                        'lost': { name: '이탈', desc: '주의가 산만하거나 학습을 중단한 상태' },
                        'sensor': { name: '센서 입력', desc: '마우스, 키보드, 스크롤 등의 입력 데이터' },
                        'kalman': { name: 'Kalman Filter', desc: '예측과 측정을 결합하여 상태를 추정' },
                        'confidence': { name: '확신도', desc: '현재 상태 추정의 신뢰도' },
                        'ping': { name: 'Active Ping', desc: '학생에게 직접 확인하는 메커니즘' }
                    };
                    const info = nodeInfo[nodeId];
                    if (info) {
                        addLog(`🔍 [${info.name}] ${info.desc}`, 'prediction');
                    }
                });
            });
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            updateUI(hybridState);
            drawNetworkEdges();
            setupNodeInteraction();
            addLog('⚛️ HybridStateStabilizer 연결됨 | User ID: <?php echo $userId; ?>', 'prediction');
            addLog('🧠 인지노드 네트워크 시각화 초기화 완료', 'prediction');
        });
    </script>
</body>
</html>
<?php
/**
 * 관련 DB 테이블:
 * - mdl_at_hybrid_state: 하이브리드 상태 저장
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/teachingsupport/AItutor/ui/quantum_modeling.php
 *
 * 연결 파일:
 * - /orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/HybridStateStabilizer.php
 * - /orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/assets/js/HybridStateTracker.js
 * - /orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/api/hybrid_state_api.php
 */
?>
