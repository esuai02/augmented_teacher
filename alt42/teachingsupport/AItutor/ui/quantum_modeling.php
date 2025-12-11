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

        /* 시뮬레이션 로그 */
        .sim-log {
            max-height: 180px;
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

        /* 인지노드 네트워크 시각화 */
        .node-network {
            position: relative;
            width: 100%;
            height: 320px;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
            border-radius: 12px;
            overflow: hidden;
        }

        .node-network svg {
            width: 100%;
            height: 100%;
        }

        .cognitive-node {
            transition: all 0.5s ease;
            cursor: pointer;
        }

        .cognitive-node:hover {
            filter: brightness(1.3);
        }

        .node-label {
            font-size: 11px;
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

        .node-connection {
            stroke: var(--border);
            stroke-width: 2;
            fill: none;
            transition: all 0.5s ease;
        }

        .node-connection.active {
            stroke: var(--primary);
            stroke-width: 3;
            filter: drop-shadow(0 0 5px rgba(99, 102, 241, 0.5));
        }

        /* 레이더 차트 */
        .radar-chart {
            position: relative;
            width: 100%;
            height: 200px;
        }

        .radar-chart svg {
            width: 100%;
            height: 100%;
        }

        .radar-polygon {
            fill: rgba(99, 102, 241, 0.3);
            stroke: var(--primary);
            stroke-width: 2;
            transition: all 0.5s ease;
        }

        .radar-axis {
            stroke: var(--border);
            stroke-width: 1;
        }

        .radar-ring {
            fill: none;
            stroke: var(--border);
            stroke-width: 1;
            stroke-dasharray: 4;
        }

        /* 실시간 파형 */
        .waveform-container {
            height: 60px;
            background: var(--bg-dark);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .waveform-canvas {
            width: 100%;
            height: 100%;
        }

        /* 상태 트랜지션 링 */
        .state-ring {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 0 auto;
        }

        .state-ring svg {
            transform: rotate(-90deg);
        }

        .ring-bg {
            fill: none;
            stroke: var(--bg-dark);
            stroke-width: 12;
        }

        .ring-progress {
            fill: none;
            stroke-width: 12;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease, stroke 0.3s ease;
        }

        .ring-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .ring-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .ring-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* 미니 스탯 카드 */
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .mini-stat {
            background: var(--bg-dark);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .mini-stat .icon {
            font-size: 1.2rem;
        }

        .mini-stat .value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .mini-stat .label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* 확장된 col 클래스 */
        .col-5 { grid-column: span 5; }
        .col-7 { grid-column: span 7; }
        .col-3 { grid-column: span 3; }

        /* 반응형 레이아웃 최적화 */
        @media (max-width: 1400px) {
            .col-5 { grid-column: span 6; }
            .col-7 { grid-column: span 6; }
            .state-ring {
                width: 140px;
                height: 140px;
            }
            .ring-value { font-size: 1.5rem; }
        }

        @media (max-width: 1200px) {
            .col-5, .col-7, .col-3 { grid-column: span 12; }
            .node-network { height: 250px; }
            .state-ring {
                width: 130px;
                height: 130px;
            }
            .mini-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 10px; }
            .header h1 { font-size: 1.2rem; }
            .node-network { height: 200px; }
            .state-ring {
                width: 100px;
                height: 100px;
            }
            .ring-value { font-size: 1.2rem; }
            .ring-text { font-size: 0.65rem; }
            .state-vector-bars { gap: 5px; }
            .state-bar { height: 70px; }
            .mini-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .kalman-viz {
                flex-wrap: wrap;
                gap: 10px;
            }
            .kalman-box { padding: 8px 12px; }
            .ping-btn { padding: 6px 12px; font-size: 0.75rem; }
        }

        @media (max-width: 480px) {
            .confidence-panel {
                flex-direction: column;
                gap: 8px;
            }
            .state-ring {
                width: 80px;
                height: 80px;
            }
            .ring-value { font-size: 1rem; }
            .event-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* 개선된 상태 바 */
        .state-bar {
            height: 100px;
            background: var(--bg-dark);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        /* 노드 펄스 애니메이션 */
        @keyframes nodePulse {
            0%, 100% { r: 28; opacity: 1; }
            50% { r: 32; opacity: 0.8; }
        }

        .node-pulse {
            animation: nodePulse 2s infinite;
        }

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
            <!-- 인지노드 네트워크 시각화 -->
            <div class="col-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🧠 인지노드 네트워크</div>
                        <span class="status-badge online" style="font-size: 0.7rem; padding: 3px 8px;">
                            <span class="dot"></span>
                            Live
                        </span>
                    </div>

                    <div class="node-network" id="nodeNetwork">
                        <svg viewBox="0 0 400 300" preserveAspectRatio="xMidYMid meet">
                            <defs>
                                <!-- 그라디언트 정의 -->
                                <radialGradient id="focusGrad" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color:#34d399;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#10b981;stop-opacity:0.8" />
                                </radialGradient>
                                <radialGradient id="flowGrad" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color:#818cf8;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.8" />
                                </radialGradient>
                                <radialGradient id="struggleGrad" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f59e0b;stop-opacity:0.8" />
                                </radialGradient>
                                <radialGradient id="lostGrad" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color:#f87171;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#ef4444;stop-opacity:0.8" />
                                </radialGradient>
                                <!-- 발광 효과 -->
                                <filter id="glow">
                                    <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>

                            <!-- 연결선 -->
                            <g class="connections">
                                <line class="node-connection" id="conn_focus_flow" x1="130" y1="90" x2="270" y2="90"/>
                                <line class="node-connection" id="conn_focus_struggle" x1="130" y1="90" x2="130" y2="210"/>
                                <line class="node-connection" id="conn_flow_lost" x1="270" y1="90" x2="270" y2="210"/>
                                <line class="node-connection" id="conn_struggle_lost" x1="130" y1="210" x2="270" y2="210"/>
                                <line class="node-connection" id="conn_focus_lost" x1="130" y1="90" x2="270" y2="210" style="stroke-dasharray: 5,5;"/>
                                <line class="node-connection" id="conn_flow_struggle" x1="270" y1="90" x2="130" y2="210" style="stroke-dasharray: 5,5;"/>
                            </g>

                            <!-- 중앙 상태 표시 -->
                            <g class="center-state" transform="translate(200, 150)">
                                <circle r="25" fill="var(--bg-dark)" stroke="var(--border)" stroke-width="2"/>
                                <text class="node-label" y="5" fill="var(--text-primary)" id="centerStateText">⚛️</text>
                            </g>

                            <!-- Focus 노드 -->
                            <g class="cognitive-node" id="node_focus" transform="translate(130, 90)">
                                <circle class="node-pulse" r="28" fill="url(#focusGrad)" filter="url(#glow)"/>
                                <text class="node-label" y="-35">🎯 집중</text>
                                <text class="node-value" y="5" id="nodeVal_focus"><?php echo round($hybridState['state_vector']['focus'] * 100); ?>%</text>
                            </g>

                            <!-- Flow 노드 -->
                            <g class="cognitive-node" id="node_flow" transform="translate(270, 90)">
                                <circle r="28" fill="url(#flowGrad)" filter="url(#glow)"/>
                                <text class="node-label" y="-35">🌊 몰입</text>
                                <text class="node-value" y="5" id="nodeVal_flow"><?php echo round($hybridState['state_vector']['flow'] * 100); ?>%</text>
                            </g>

                            <!-- Struggle 노드 -->
                            <g class="cognitive-node" id="node_struggle" transform="translate(130, 210)">
                                <circle r="28" fill="url(#struggleGrad)" filter="url(#glow)"/>
                                <text class="node-label" y="45">💪 고군분투</text>
                                <text class="node-value" y="5" id="nodeVal_struggle"><?php echo round($hybridState['state_vector']['struggle'] * 100); ?>%</text>
                            </g>

                            <!-- Lost 노드 -->
                            <g class="cognitive-node" id="node_lost" transform="translate(270, 210)">
                                <circle r="28" fill="url(#lostGrad)" filter="url(#glow)"/>
                                <text class="node-label" y="45">😶 이탈</text>
                                <text class="node-value" y="5" id="nodeVal_lost"><?php echo round($hybridState['state_vector']['lost'] * 100); ?>%</text>
                            </g>
                        </svg>
                    </div>

                    <!-- 상태 링 (원형 진행 표시) -->
                    <div style="display: flex; justify-content: space-around; margin-top: 20px;">
                        <div class="state-ring" id="confidenceRing">
                            <svg viewBox="0 0 160 160">
                                <circle class="ring-bg" cx="80" cy="80" r="65"/>
                                <circle class="ring-progress" cx="80" cy="80" r="65"
                                        stroke="var(--success)"
                                        stroke-dasharray="408.4"
                                        stroke-dashoffset="<?php echo 408.4 * (1 - $hybridState['confidence']); ?>"
                                        id="confidenceRingProgress"/>
                            </svg>
                            <div class="ring-label">
                                <div class="ring-value" id="ringConfidence"><?php echo round($hybridState['confidence'] * 100); ?>%</div>
                                <div class="ring-text">확신도</div>
                            </div>
                        </div>
                        <div class="state-ring" id="stateRing">
                            <svg viewBox="0 0 160 160">
                                <circle class="ring-bg" cx="80" cy="80" r="65"/>
                                <circle class="ring-progress" cx="80" cy="80" r="65"
                                        stroke="var(--primary)"
                                        stroke-dasharray="408.4"
                                        stroke-dashoffset="<?php echo 408.4 * (1 - $hybridState['predicted_state']); ?>"
                                        id="stateRingProgress"/>
                            </svg>
                            <div class="ring-label">
                                <div class="ring-value" id="ringState"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                                <div class="ring-text">집중도</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 메인 상태 모니터 -->
            <div class="col-7">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 실시간 상태 모니터</div>
                        <div class="realtime-indicator" style="margin: 0; padding: 6px 12px;">
                            <div class="pulse"></div>
                            <span>Fast Loop</span>
                            <span style="margin-left: 5px; color: var(--text-secondary);" id="loopCount">0회</span>
                        </div>
                    </div>

                    <!-- 집중도 미터 -->
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>집중도 (Predicted State)</span>
                            <span id="stateValue"><?php echo round($hybridState['predicted_state'] * 100); ?>%</span>
                        </div>
                        <div class="state-meter">
                            <div class="state-meter-fill" id="stateMeterFill"
                                 style="width: <?php echo $hybridState['predicted_state'] * 100; ?>%"></div>
                            <span class="state-meter-label" id="stateLabel">
                                <?php echo ucfirst($hybridState['dominant_state']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- 확신도 패널 -->
                    <div class="confidence-panel">
                        <?php
                        $conf = $hybridState['confidence'];
                        $confClass = $conf >= 0.6 ? 'high' : ($conf >= 0.3 ? 'medium' : 'low');
                        ?>
                        <div class="confidence-item <?php echo $confClass; ?>" id="confidencePanel">
                            <div class="icon"><?php echo $confClass === 'high' ? '✅' : ($confClass === 'medium' ? '⚠️' : '❓'); ?></div>
                            <div class="label">확신도</div>
                            <div class="value" id="confidenceValue"><?php echo round($conf * 100); ?>%</div>
                        </div>
                        <div class="confidence-item">
                            <div class="icon">📊</div>
                            <div class="label">불확실성</div>
                            <div class="value" id="uncertaintyValue"><?php echo round($hybridState['uncertainty'] * 100); ?>%</div>
                        </div>
                        <div class="confidence-item" id="pingNeeded" style="<?php echo $hybridState['needs_ping'] ? '' : 'opacity: 0.5;'; ?>">
                            <div class="icon">📡</div>
                            <div class="label">Active Ping</div>
                            <div class="value"><?php echo $hybridState['needs_ping'] ? '필요' : '불필요'; ?></div>
                        </div>
                    </div>

                    <!-- 상태 벡터 바 차트 -->
                    <h4 style="margin: 20px 0 12px; font-size: 0.9rem;">상태 분포 (State Vector)</h4>
                    <div class="state-vector-bars">
                        <?php
                        $stateVector = $hybridState['state_vector'];
                        $stateLabels = ['focus' => '집중', 'flow' => '몰입', 'struggle' => '고군분투', 'lost' => '이탈'];
                        $stateIcons = ['focus' => '🎯', 'flow' => '🌊', 'struggle' => '💪', 'lost' => '😶'];
                        foreach ($stateVector as $state => $value):
                        ?>
                        <div class="state-bar-container">
                            <div class="state-bar">
                                <div class="state-bar-fill <?php echo $state; ?>"
                                     id="stateBar_<?php echo $state; ?>"
                                     style="height: <?php echo $value * 100; ?>%"></div>
                            </div>
                            <div class="state-bar-label">
                                <?php echo $stateIcons[$state]; ?> <?php echo $stateLabels[$state]; ?>
                            </div>
                            <div class="state-bar-value" id="stateBarValue_<?php echo $state; ?>">
                                <?php echo round($value * 100); ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 컨트롤 패널 -->
            <div class="col-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎛️ 시뮬레이션 컨트롤</div>
                    </div>

                    <!-- Active Ping 버튼 -->
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

                    <!-- 이벤트 시뮬레이션 -->
                    <h5 style="font-size: 0.85rem; margin: 20px 0 10px;">⚡ 이벤트 (Kalman Correction)</h5>
                    <div class="event-buttons">
                        <button class="event-btn positive" onclick="simulateEvent('correct_answer')">✅ 정답</button>
                        <button class="event-btn positive" onclick="simulateEvent('quick_response')">⚡ 빠른응답</button>
                        <button class="event-btn" onclick="simulateEvent('scroll_active')">📜 스크롤</button>
                        <button class="event-btn negative" onclick="simulateEvent('hint_click')">💡 힌트</button>
                        <button class="event-btn negative" onclick="simulateEvent('wrong_answer')">❌ 오답</button>
                        <button class="event-btn negative" onclick="simulateEvent('skip_problem')">⏭️ 건너뛰기</button>
                        <button class="event-btn negative" onclick="simulateEvent('long_pause')">⏸️ 긴멈춤</button>
                    </div>

                    <!-- Fast Loop 시뮬레이션 -->
                    <h5 style="font-size: 0.85rem; margin: 20px 0 10px;">🔄 센서 데이터</h5>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="simulateSensor('active')" style="flex: 1;">
                            🖱️ 활발
                        </button>
                        <button class="btn btn-secondary" onclick="simulateSensor('idle')" style="flex: 1;">
                            😴 Idle
                        </button>
                    </div>

                    <!-- Kalman 시각화 -->
                    <div id="kalmanViz" style="display: none; margin-top: 20px;">
                        <h5 style="font-size: 0.85rem; margin-bottom: 10px;">⚖️ Kalman Filter</h5>
                        <div class="kalman-viz">
                            <div class="kalman-box prediction">
                                <div class="label">예측</div>
                                <div class="value" id="kalmanPred">-</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-gain">
                                <div class="label">K</div>
                                <div class="value" id="kalmanK">-</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-box measurement">
                                <div class="label">측정</div>
                                <div class="value" id="kalmanMeas">-</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-box result">
                                <div class="label">보정</div>
                                <div class="value" id="kalmanRes">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 시뮬레이션 로그 -->
            <div class="col-7">
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

            <!-- Kalman Filter 시각화 패널 -->
            <div class="col-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">⚖️ Kalman Filter 보정</div>
                    </div>
                    <div id="kalmanVizPanel">
                        <div class="kalman-viz">
                            <div class="kalman-box prediction">
                                <div class="label">예측(P)</div>
                                <div class="value" id="kalmanPredVal"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                            <span class="kalman-arrow">+</span>
                            <div class="kalman-gain">
                                <div class="label">K·(M-P)</div>
                                <div class="value" id="kalmanKVal">0</div>
                            </div>
                            <span class="kalman-arrow">=</span>
                            <div class="kalman-box result">
                                <div class="label">보정(X)</div>
                                <div class="value" id="kalmanResVal"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                        </div>
                        <div class="mini-stats">
                            <div class="mini-stat">
                                <div class="icon">📡</div>
                                <div class="value" id="totalPings">0</div>
                                <div class="label">Active Pings</div>
                            </div>
                            <div class="mini-stat">
                                <div class="icon">⚡</div>
                                <div class="value" id="totalEvents">0</div>
                                <div class="label">이벤트</div>
                            </div>
                            <div class="mini-stat">
                                <div class="icon">🔄</div>
                                <div class="value" id="totalCorrections">0</div>
                                <div class="label">보정 횟수</div>
                            </div>
                            <div class="mini-stat">
                                <div class="icon">⏱️</div>
                                <div class="value" id="avgResponseTime">-</div>
                                <div class="label">평균 응답</div>
                            </div>
                        </div>
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

        // 통계 카운터
        let statsCounter = {
            totalPings: 0,
            totalEvents: 0,
            totalCorrections: 0,
            responseTimes: []
        };

        // UI 업데이트
        function updateUI(state) {
            if (!state) state = hybridState;

            // 집중도 미터
            document.getElementById('stateMeterFill').style.width = (state.predicted_state * 100) + '%';
            document.getElementById('stateValue').textContent = Math.round(state.predicted_state * 100) + '%';
            document.getElementById('stateLabel').textContent = {
                'focus': 'Focus', 'flow': 'Flow', 'struggle': 'Struggle', 'lost': 'Lost'
            }[state.dominant_state] || 'Focus';

            // 확신도
            document.getElementById('confidenceValue').textContent = Math.round(state.confidence * 100) + '%';
            document.getElementById('uncertaintyValue').textContent = Math.round(state.uncertainty * 100) + '%';

            const confPanel = document.getElementById('confidencePanel');
            confPanel.className = 'confidence-item ' +
                (state.confidence >= 0.6 ? 'high' : (state.confidence >= 0.3 ? 'medium' : 'low'));
            confPanel.querySelector('.icon').textContent =
                state.confidence >= 0.6 ? '✅' : (state.confidence >= 0.3 ? '⚠️' : '❓');

            // Ping 필요 여부
            const pingPanel = document.getElementById('pingNeeded');
            pingPanel.style.opacity = state.needs_ping ? '1' : '0.5';
            pingPanel.querySelector('.value').textContent = state.needs_ping ? '필요' : '불필요';

            // 상태 벡터 바
            for (const [key, val] of Object.entries(state.state_vector)) {
                const bar = document.getElementById('stateBar_' + key);
                const value = document.getElementById('stateBarValue_' + key);
                if (bar) bar.style.height = (val * 100) + '%';
                if (value) value.textContent = Math.round(val * 100) + '%';
            }

            // 루프 카운트
            document.getElementById('loopCount').textContent = loopCount + '회';

            // 인지노드 네트워크 업데이트
            updateNodeNetwork(state);

            // 상태 링 업데이트
            updateStateRings(state);

            // 통계 업데이트
            updateStats();
        }

        // 인지노드 네트워크 업데이트
        function updateNodeNetwork(state) {
            const stateVector = state.state_vector;
            const dominant = state.dominant_state;

            // 노드 값 업데이트
            for (const [key, val] of Object.entries(stateVector)) {
                const nodeVal = document.getElementById('nodeVal_' + key);
                if (nodeVal) nodeVal.textContent = Math.round(val * 100) + '%';

                // 노드 크기 조절 (dominant 상태일 경우 강조)
                const node = document.getElementById('node_' + key);
                if (node) {
                    const circle = node.querySelector('circle');
                    if (circle) {
                        const baseR = 28;
                        const scale = 1 + (val * 0.3); // 값에 따라 최대 30% 크기 증가
                        circle.setAttribute('r', Math.round(baseR * scale));

                        // dominant 상태에 펄스 애니메이션 추가
                        if (key === dominant) {
                            circle.classList.add('node-pulse');
                        } else {
                            circle.classList.remove('node-pulse');
                        }
                    }
                }
            }

            // 중앙 상태 텍스트 업데이트
            const centerText = document.getElementById('centerStateText');
            if (centerText) {
                const stateEmojis = { 'focus': '🎯', 'flow': '🌊', 'struggle': '💪', 'lost': '😶' };
                centerText.textContent = stateEmojis[dominant] || '⚛️';
            }

            // 연결선 활성화 (dominant 상태와 연결된 선)
            const connections = {
                'focus': ['conn_focus_flow', 'conn_focus_struggle', 'conn_focus_lost'],
                'flow': ['conn_focus_flow', 'conn_flow_lost', 'conn_flow_struggle'],
                'struggle': ['conn_focus_struggle', 'conn_struggle_lost', 'conn_flow_struggle'],
                'lost': ['conn_flow_lost', 'conn_struggle_lost', 'conn_focus_lost']
            };

            // 모든 연결선 비활성화
            document.querySelectorAll('.node-connection').forEach(conn => {
                conn.classList.remove('active');
            });

            // dominant 상태 연결선 활성화
            if (connections[dominant]) {
                connections[dominant].forEach(connId => {
                    const conn = document.getElementById(connId);
                    if (conn) conn.classList.add('active');
                });
            }
        }

        // 상태 링 업데이트
        function updateStateRings(state) {
            const circumference = 408.4; // 2 * π * 65

            // 확신도 링
            const confProgress = document.getElementById('confidenceRingProgress');
            if (confProgress) {
                const confOffset = circumference * (1 - state.confidence);
                confProgress.style.strokeDashoffset = confOffset;

                // 색상 변경
                if (state.confidence >= 0.6) {
                    confProgress.style.stroke = 'var(--success)';
                } else if (state.confidence >= 0.3) {
                    confProgress.style.stroke = 'var(--warning)';
                } else {
                    confProgress.style.stroke = 'var(--danger)';
                }
            }
            const ringConf = document.getElementById('ringConfidence');
            if (ringConf) ringConf.textContent = Math.round(state.confidence * 100) + '%';

            // 집중도 링
            const stateProgress = document.getElementById('stateRingProgress');
            if (stateProgress) {
                const stateOffset = circumference * (1 - state.predicted_state);
                stateProgress.style.strokeDashoffset = stateOffset;

                // 색상 변경
                if (state.predicted_state >= 0.7) {
                    stateProgress.style.stroke = 'var(--success)';
                } else if (state.predicted_state >= 0.4) {
                    stateProgress.style.stroke = 'var(--primary)';
                } else {
                    stateProgress.style.stroke = 'var(--danger)';
                }
            }
            const ringState = document.getElementById('ringState');
            if (ringState) ringState.textContent = Math.round(state.predicted_state * 100) + '%';
        }

        // 통계 업데이트
        function updateStats() {
            document.getElementById('totalPings').textContent = statsCounter.totalPings;
            document.getElementById('totalEvents').textContent = statsCounter.totalEvents;
            document.getElementById('totalCorrections').textContent = statsCounter.totalCorrections;

            if (statsCounter.responseTimes.length > 0) {
                const avg = statsCounter.responseTimes.reduce((a, b) => a + b, 0) / statsCounter.responseTimes.length;
                document.getElementById('avgResponseTime').textContent = avg.toFixed(1) + 's';
            }
        }

        // Kalman 시각화 업데이트
        function updateKalmanViz(prevState, measurement, kalmanGain, newState) {
            document.getElementById('kalmanPredVal').textContent = Math.round(prevState * 100) + '%';
            document.getElementById('kalmanKVal').textContent = ((kalmanGain || 0) * (measurement - prevState)).toFixed(2);
            document.getElementById('kalmanResVal').textContent = Math.round(newState * 100) + '%';
            statsCounter.totalCorrections++;
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

            // 통계 카운터 증가
            statsCounter.totalPings++;
            updateStats();

            // 버튼 활성화
            document.querySelectorAll('.ping-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('pingBtn' + level).classList.add('active');

            const pingStartTime = Date.now();

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
                            const prevState = hybridState.predicted_state;
                            hybridState = respResult.state;
                            updateUI(hybridState);

                            // 응답 시간 기록
                            const actualResponseTime = (Date.now() - pingStartTime) / 1000;
                            statsCounter.responseTimes.push(actualResponseTime);
                            if (statsCounter.responseTimes.length > 50) {
                                statsCounter.responseTimes.shift(); // 최근 50개만 유지
                            }

                            // Kalman 시각화 업데이트
                            const measurement = responded ? 0.85 : 0.15;
                            updateKalmanViz(prevState, measurement, respResult.result?.kalman_gain || 0.5, hybridState.predicted_state);

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
            // 통계 카운터 증가
            statsCounter.totalEvents++;
            updateStats();

            try {
                const prevState = hybridState.predicted_state;
                const measurement = EVENT_SIGNALS[eventType] || 0.5;

                const result = await apiCall('kalman_correction', {
                    event_type: eventType,
                    event_data: {}
                });

                if (result.success) {
                    hybridState = result.state;
                    updateUI(hybridState);

                    // 새로운 Kalman 패널 업데이트
                    const kalmanGain = result.result.kalman_gain || 0.5;
                    updateKalmanViz(prevState, measurement, kalmanGain, hybridState.predicted_state);

                    // 기존 Kalman 시각화 업데이트 (존재하는 경우)
                    const viz = document.getElementById('kalmanViz');
                    if (viz) {
                        viz.style.display = 'block';
                        const kalmanPred = document.getElementById('kalmanPred');
                        const kalmanMeas = document.getElementById('kalmanMeas');
                        const kalmanK = document.getElementById('kalmanK');
                        const kalmanRes = document.getElementById('kalmanRes');
                        if (kalmanPred) kalmanPred.textContent = Math.round(prevState * 100) + '%';
                        if (kalmanMeas) kalmanMeas.textContent = Math.round(measurement * 100) + '%';
                        if (kalmanK) kalmanK.textContent = kalmanGain.toFixed(2);
                        if (kalmanRes) kalmanRes.textContent = Math.round(hybridState.predicted_state * 100) + '%';
                    }

                    addLog('⚡ [' + eventType + '] Kalman 보정: ' +
                           Math.round(prevState * 100) + '% → ' +
                           Math.round(hybridState.predicted_state * 100) + '%', 'event');
                }
            } catch (error) {
                addLog('❌ 이벤트 오류: ' + error.message, 'error');
            }
        }

        // 자동 Fast Loop (실제 센서 데이터는 HybridStateTracker.js에서 처리)
        function startAutoLoop() {
            fastLoopId = setInterval(() => {
                // 실제 센서 데이터 수집은 HybridStateTracker가 담당
                // 여기서는 상태만 갱신
                loopCount++;
                document.getElementById('loopCount').textContent = loopCount + '회';
            }, 500);
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            updateUI(hybridState);
            startAutoLoop();
            addLog('⚛️ HybridStateStabilizer 연결됨 | User ID: <?php echo $userId; ?>', 'prediction');
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
