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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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

        @media (max-width: 1400px) {
            .col-8 { grid-column: span 7; }
            .col-6 { grid-column: span 5; }
            .col-4 { grid-column: span 12; }
        }

        @media (max-width: 1200px) {
            .col-8, .col-6 { grid-column: span 6; }
            .col-4 { grid-column: span 12; }
        }

        @media (max-width: 992px) {
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

        /* 차트 컨테이너 */
        .chart-container {
            position: relative;
            height: 280px;
            margin: 15px 0;
        }

        .chart-container.small {
            height: 200px;
        }

        /* 인지노드 네트워크 */
        .cognitive-network {
            position: relative;
            width: 100%;
            height: 300px;
            background: var(--bg-dark);
            border-radius: 12px;
            overflow: hidden;
        }

        .cognitive-node {
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 2;
        }

        .cognitive-node:hover {
            transform: scale(1.15);
            z-index: 10;
        }

        .cognitive-node .icon {
            font-size: 1.3rem;
            margin-bottom: 3px;
        }

        .cognitive-node .value {
            font-size: 0.9rem;
            font-weight: 700;
        }

        .cognitive-node.focus {
            background: linear-gradient(135deg, #10b981, #059669);
            left: 50%;
            top: 15%;
            transform: translateX(-50%);
        }

        .cognitive-node.flow {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            left: 80%;
            top: 40%;
            transform: translateX(-50%);
        }

        .cognitive-node.struggle {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            left: 65%;
            top: 75%;
            transform: translateX(-50%);
        }

        .cognitive-node.lost {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            left: 35%;
            top: 75%;
            transform: translateX(-50%);
        }

        .cognitive-node.center {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 90px;
            height: 90px;
            font-size: 0.85rem;
        }

        .cognitive-node.center .icon {
            font-size: 1.5rem;
        }

        .cognitive-node.center .value {
            font-size: 1.1rem;
        }

        /* 노드 연결선 SVG */
        .network-connections {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .network-connections line {
            stroke: var(--border);
            stroke-width: 2;
            stroke-dasharray: 5, 5;
            opacity: 0.5;
        }

        .network-connections line.active {
            stroke: var(--primary);
            stroke-width: 3;
            stroke-dasharray: none;
            opacity: 0.8;
            animation: pulse-line 1.5s ease-in-out infinite;
        }

        @keyframes pulse-line {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 0.4; }
        }

        /* 게이지 차트 */
        .gauge-container {
            position: relative;
            width: 180px;
            height: 100px;
            margin: 0 auto;
        }

        .gauge-bg {
            position: absolute;
            width: 180px;
            height: 90px;
            border-radius: 90px 90px 0 0;
            background: var(--bg-dark);
            overflow: hidden;
        }

        .gauge-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 180px;
            height: 90px;
            border-radius: 90px 90px 0 0;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
            transform-origin: bottom center;
            transition: transform 0.5s ease;
        }

        .gauge-center {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            height: 70px;
            border-radius: 70px 70px 0 0;
            background: var(--bg-card);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 10px;
        }

        .gauge-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .gauge-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* 미니 스파크라인 */
        .sparkline-container {
            height: 40px;
            margin-top: 10px;
        }

        /* 통계 그리드 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .stat-item {
            background: var(--bg-dark);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .stat-item .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .stat-item .stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .stat-item.positive .stat-value { color: var(--success); }
        .stat-item.warning .stat-value { color: var(--warning); }
        .stat-item.negative .stat-value { color: var(--danger); }
        .stat-item.neutral .stat-value { color: var(--primary); }

        /* 탭 시스템 */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .tab {
            padding: 8px 16px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.85rem;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
        }

        .tab:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .tab.active {
            background: var(--bg-dark);
            color: var(--primary);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 반응형 추가 */
        @media (max-width: 992px) {
            .chart-container {
                height: 240px;
            }

            .cognitive-network {
                height: 280px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .card {
                padding: 16px;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .confidence-panel {
                grid-template-columns: 1fr;
            }

            .cognitive-node {
                width: 55px;
                height: 55px;
                font-size: 0.6rem;
            }

            .cognitive-node .icon {
                font-size: 1rem;
            }

            .cognitive-node.center {
                width: 70px;
                height: 70px;
            }

            .chart-container {
                height: 220px;
            }

            .cognitive-network {
                height: 260px;
            }

            .tabs {
                gap: 5px;
            }

            .tab {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .cognitive-node {
                width: 48px;
                height: 48px;
                font-size: 0.55rem;
            }

            .cognitive-node .icon {
                font-size: 0.9rem;
                margin-bottom: 2px;
            }

            .cognitive-node .value {
                font-size: 0.75rem;
            }

            .cognitive-node.center {
                width: 60px;
                height: 60px;
            }

            .stat-item .stat-value {
                font-size: 1.2rem;
            }

            .stat-item .stat-label {
                font-size: 0.65rem;
            }
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
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🧠 인지노드 네트워크</div>
                        <span class="status-badge online" style="font-size: 0.7rem;">
                            <span class="dot"></span>
                            실시간
                        </span>
                    </div>
                    <div class="cognitive-network" id="cognitiveNetwork">
                        <!-- SVG 연결선 -->
                        <svg class="network-connections" id="networkConnections">
                            <line id="line-focus-center" x1="50%" y1="15%" x2="50%" y2="50%"></line>
                            <line id="line-flow-center" x1="80%" y1="40%" x2="50%" y2="50%"></line>
                            <line id="line-struggle-center" x1="65%" y1="75%" x2="50%" y2="50%"></line>
                            <line id="line-lost-center" x1="35%" y1="75%" x2="50%" y2="50%"></line>
                            <line id="line-focus-flow" x1="50%" y1="15%" x2="80%" y2="40%"></line>
                            <line id="line-struggle-lost" x1="65%" y1="75%" x2="35%" y2="75%"></line>
                        </svg>

                        <!-- 인지 노드들 -->
                        <div class="cognitive-node focus" id="node-focus">
                            <span class="icon">🎯</span>
                            <span class="value" id="nodeValue-focus"><?php echo round($hybridState['state_vector']['focus'] * 100); ?>%</span>
                            <span>집중</span>
                        </div>
                        <div class="cognitive-node flow" id="node-flow">
                            <span class="icon">🌊</span>
                            <span class="value" id="nodeValue-flow"><?php echo round($hybridState['state_vector']['flow'] * 100); ?>%</span>
                            <span>몰입</span>
                        </div>
                        <div class="cognitive-node struggle" id="node-struggle">
                            <span class="icon">💪</span>
                            <span class="value" id="nodeValue-struggle"><?php echo round($hybridState['state_vector']['struggle'] * 100); ?>%</span>
                            <span>고군분투</span>
                        </div>
                        <div class="cognitive-node lost" id="node-lost">
                            <span class="icon">😶</span>
                            <span class="value" id="nodeValue-lost"><?php echo round($hybridState['state_vector']['lost'] * 100); ?>%</span>
                            <span>이탈</span>
                        </div>
                        <div class="cognitive-node center" id="node-center">
                            <span class="icon">⚛️</span>
                            <span class="value" id="nodeValue-center"><?php echo round($hybridState['predicted_state'] * 100); ?>%</span>
                            <span>인지상태</span>
                        </div>
                    </div>

                    <!-- 통계 그리드 -->
                    <div class="stats-grid">
                        <div class="stat-item <?php echo $hybridState['confidence'] >= 0.6 ? 'positive' : ($hybridState['confidence'] >= 0.3 ? 'warning' : 'negative'); ?>" id="statConfidence">
                            <div class="stat-value"><?php echo round($hybridState['confidence'] * 100); ?>%</div>
                            <div class="stat-label">확신도</div>
                        </div>
                        <div class="stat-item warning" id="statUncertainty">
                            <div class="stat-value"><?php echo round($hybridState['uncertainty'] * 100); ?>%</div>
                            <div class="stat-label">불확실성</div>
                        </div>
                        <div class="stat-item neutral" id="statLoopCount">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Fast Loop</div>
                        </div>
                        <div class="stat-item <?php echo $hybridState['needs_ping'] ? 'negative' : 'positive'; ?>" id="statPing">
                            <div class="stat-value"><?php echo $hybridState['needs_ping'] ? '필요' : 'OK'; ?></div>
                            <div class="stat-label">Active Ping</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 차트 패널 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 상태 분석</div>
                    </div>

                    <!-- 탭 네비게이션 -->
                    <div class="tabs">
                        <button class="tab active" onclick="switchTab('radar')">레이더</button>
                        <button class="tab" onclick="switchTab('history')">히스토리</button>
                        <button class="tab" onclick="switchTab('bars')">바 차트</button>
                    </div>

                    <!-- 레이더 차트 탭 -->
                    <div class="tab-content active" id="tab-radar">
                        <div class="chart-container">
                            <canvas id="radarChart"></canvas>
                        </div>
                    </div>

                    <!-- 히스토리 탭 -->
                    <div class="tab-content" id="tab-history">
                        <div class="chart-container">
                            <canvas id="historyChart"></canvas>
                        </div>
                    </div>

                    <!-- 바 차트 탭 -->
                    <div class="tab-content" id="tab-bars">
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
            </div>

            <!-- 메인 상태 모니터 -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📈 실시간 상태 모니터</div>
                        <span id="loopCount" style="font-size: 0.8rem; color: var(--text-secondary);">0회</span>
                    </div>

                    <div class="realtime-indicator">
                        <div class="pulse"></div>
                        <span>Kalman Filter + Active Ping 하이브리드 추적</span>
                    </div>

                    <!-- 집중도 미터 -->
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>예측 집중도 (Predicted State)</span>
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

                    <!-- Kalman 시각화 (항상 표시) -->
                    <div id="kalmanViz" style="margin-top: 20px;">
                        <h5 style="font-size: 0.85rem; margin-bottom: 10px;">⚖️ Kalman Filter 보정 상태</h5>
                        <div class="kalman-viz">
                            <div class="kalman-box prediction">
                                <div class="label">예측</div>
                                <div class="value" id="kalmanPred"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                            <span class="kalman-arrow">→</span>
                            <div class="kalman-gain">
                                <div class="label">Gain (K)</div>
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
                                <div class="value" id="kalmanRes"><?php echo round($hybridState['predicted_state'] * 100); ?>%</div>
                            </div>
                        </div>
                    </div>

                    <!-- 확신도/불확실성 미니 차트 -->
                    <div style="display: flex; gap: 20px; margin-top: 20px;">
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px;">
                                <span>확신도</span>
                                <span id="confidenceValue"><?php echo round($hybridState['confidence'] * 100); ?>%</span>
                            </div>
                            <div class="state-meter" style="height: 20px;">
                                <div class="state-meter-fill" id="confidenceMeterFill"
                                     style="width: <?php echo $hybridState['confidence'] * 100; ?>%; background: linear-gradient(90deg, #ef4444, #10b981);"></div>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px;">
                                <span>불확실성</span>
                                <span id="uncertaintyValue"><?php echo round($hybridState['uncertainty'] * 100); ?>%</span>
                            </div>
                            <div class="state-meter" style="height: 20px;">
                                <div class="state-meter-fill" id="uncertaintyMeterFill"
                                     style="width: <?php echo $hybridState['uncertainty'] * 100; ?>%; background: linear-gradient(90deg, #10b981, #ef4444);"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 컨트롤 패널 -->
            <div class="col-4">
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

        // 히스토리 데이터 (최근 50개)
        let stateHistory = {
            timestamps: [],
            predicted: [],
            confidence: [],
            focus: [],
            flow: [],
            struggle: [],
            lost: []
        };

        // Chart.js 인스턴스
        let radarChart = null;
        let historyChart = null;

        const CONFIDENCE_DECAY = 0.99;
        const UNCERTAINTY_GROWTH = 1.05;
        const PING_THRESHOLD = 0.4;
        const MAX_HISTORY = 50;

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

        // 탭 전환
        function switchTab(tabName) {
            // 모든 탭 비활성화
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // 선택한 탭 활성화
            document.querySelector(`.tab[onclick*="${tabName}"]`).classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');

            // 차트 리사이즈
            if (tabName === 'radar' && radarChart) radarChart.resize();
            if (tabName === 'history' && historyChart) historyChart.resize();
        }

        // 레이더 차트 초기화
        function initRadarChart() {
            const ctx = document.getElementById('radarChart').getContext('2d');
            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['집중 (Focus)', '몰입 (Flow)', '고군분투 (Struggle)', '이탈 (Lost)'],
                    datasets: [{
                        label: '상태 벡터',
                        data: [
                            hybridState.state_vector.focus * 100,
                            hybridState.state_vector.flow * 100,
                            hybridState.state_vector.struggle * 100,
                            hybridState.state_vector.lost * 100
                        ],
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: [
                            '#10b981', '#6366f1', '#f59e0b', '#ef4444'
                        ],
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 25,
                                color: '#94a3b8',
                                backdropColor: 'transparent'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.2)'
                            },
                            angleLines: {
                                color: 'rgba(148, 163, 184, 0.2)'
                            },
                            pointLabels: {
                                color: '#f1f5f9',
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            }
                        }
                    }
                }
            });
        }

        // 히스토리 차트 초기화
        function initHistoryChart() {
            const ctx = document.getElementById('historyChart').getContext('2d');
            historyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: '집중도',
                            data: [],
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: '확신도',
                            data: [],
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#94a3b8',
                                usePointStyle: true,
                                padding: 15
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            },
                            ticks: {
                                color: '#94a3b8',
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            display: true,
                            min: 0,
                            max: 100,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)'
                            },
                            ticks: {
                                color: '#94a3b8',
                                callback: value => value + '%'
                            }
                        }
                    }
                }
            });
        }

        // 히스토리에 데이터 추가
        function addToHistory(state) {
            const now = new Date();
            const timeLabel = now.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            stateHistory.timestamps.push(timeLabel);
            stateHistory.predicted.push(Math.round(state.predicted_state * 100));
            stateHistory.confidence.push(Math.round(state.confidence * 100));
            stateHistory.focus.push(Math.round(state.state_vector.focus * 100));
            stateHistory.flow.push(Math.round(state.state_vector.flow * 100));
            stateHistory.struggle.push(Math.round(state.state_vector.struggle * 100));
            stateHistory.lost.push(Math.round(state.state_vector.lost * 100));

            // 최대 개수 유지
            if (stateHistory.timestamps.length > MAX_HISTORY) {
                stateHistory.timestamps.shift();
                stateHistory.predicted.shift();
                stateHistory.confidence.shift();
                stateHistory.focus.shift();
                stateHistory.flow.shift();
                stateHistory.struggle.shift();
                stateHistory.lost.shift();
            }
        }

        // 인지노드 네트워크 업데이트
        function updateCognitiveNetwork(state) {
            // 노드 값 업데이트
            document.getElementById('nodeValue-focus').textContent = Math.round(state.state_vector.focus * 100) + '%';
            document.getElementById('nodeValue-flow').textContent = Math.round(state.state_vector.flow * 100) + '%';
            document.getElementById('nodeValue-struggle').textContent = Math.round(state.state_vector.struggle * 100) + '%';
            document.getElementById('nodeValue-lost').textContent = Math.round(state.state_vector.lost * 100) + '%';
            document.getElementById('nodeValue-center').textContent = Math.round(state.predicted_state * 100) + '%';

            // 지배 상태에 따른 연결선 활성화
            const dominant = state.dominant_state;
            document.querySelectorAll('.network-connections line').forEach(line => {
                line.classList.remove('active');
            });

            const lineId = 'line-' + dominant + '-center';
            const activeLine = document.getElementById(lineId);
            if (activeLine) {
                activeLine.classList.add('active');
            }

            // 노드 크기 조절 (상태값에 따라)
            const nodes = ['focus', 'flow', 'struggle', 'lost'];
            nodes.forEach(n => {
                const node = document.getElementById('node-' + n);
                const val = state.state_vector[n];
                const scale = 0.8 + (val * 0.4); // 0.8 ~ 1.2
                if (n === dominant) {
                    node.style.transform = `translateX(-50%) scale(${scale * 1.1})`;
                    node.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.5)';
                } else {
                    node.style.transform = `translateX(-50%) scale(${scale})`;
                    node.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
                }
            });
        }

        // 차트 업데이트
        function updateCharts(state) {
            // 레이더 차트
            if (radarChart) {
                radarChart.data.datasets[0].data = [
                    state.state_vector.focus * 100,
                    state.state_vector.flow * 100,
                    state.state_vector.struggle * 100,
                    state.state_vector.lost * 100
                ];
                radarChart.update('none');
            }

            // 히스토리 차트
            if (historyChart) {
                historyChart.data.labels = stateHistory.timestamps;
                historyChart.data.datasets[0].data = stateHistory.predicted;
                historyChart.data.datasets[1].data = stateHistory.confidence;
                historyChart.update('none');
            }
        }

        // 통계 그리드 업데이트
        function updateStatsGrid(state) {
            // 확신도
            const confStat = document.getElementById('statConfidence');
            confStat.querySelector('.stat-value').textContent = Math.round(state.confidence * 100) + '%';
            confStat.className = 'stat-item ' + (state.confidence >= 0.6 ? 'positive' : (state.confidence >= 0.3 ? 'warning' : 'negative'));

            // 불확실성
            const uncStat = document.getElementById('statUncertainty');
            uncStat.querySelector('.stat-value').textContent = Math.round(state.uncertainty * 100) + '%';

            // 루프 카운트
            document.getElementById('statLoopCount').querySelector('.stat-value').textContent = loopCount;

            // 핑 상태
            const pingStat = document.getElementById('statPing');
            pingStat.querySelector('.stat-value').textContent = state.needs_ping ? '필요' : 'OK';
            pingStat.className = 'stat-item ' + (state.needs_ping ? 'negative' : 'positive');
        }

        // UI 업데이트 (통합)
        function updateUI(state) {
            if (!state) state = hybridState;

            // 집중도 미터
            document.getElementById('stateMeterFill').style.width = (state.predicted_state * 100) + '%';
            document.getElementById('stateValue').textContent = Math.round(state.predicted_state * 100) + '%';
            document.getElementById('stateLabel').textContent = {
                'focus': 'Focus', 'flow': 'Flow', 'struggle': 'Struggle', 'lost': 'Lost'
            }[state.dominant_state] || 'Focus';

            // 확신도/불확실성 미터
            document.getElementById('confidenceValue').textContent = Math.round(state.confidence * 100) + '%';
            document.getElementById('confidenceMeterFill').style.width = (state.confidence * 100) + '%';
            document.getElementById('uncertaintyValue').textContent = Math.round(state.uncertainty * 100) + '%';
            document.getElementById('uncertaintyMeterFill').style.width = (state.uncertainty * 100) + '%';

            // 상태 벡터 바 (바 차트 탭)
            for (const [key, val] of Object.entries(state.state_vector)) {
                const bar = document.getElementById('stateBar_' + key);
                const value = document.getElementById('stateBarValue_' + key);
                if (bar) bar.style.height = (val * 100) + '%';
                if (value) value.textContent = Math.round(val * 100) + '%';
            }

            // 루프 카운트
            document.getElementById('loopCount').textContent = loopCount + '회';

            // 인지노드 네트워크 업데이트
            updateCognitiveNetwork(state);

            // 통계 그리드 업데이트
            updateStatsGrid(state);

            // 히스토리에 추가 & 차트 업데이트
            addToHistory(state);
            updateCharts(state);
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
            // 차트 초기화
            initRadarChart();
            initHistoryChart();

            // UI 및 자동 루프 시작
            updateUI(hybridState);
            startAutoLoop();

            // 초기 히스토리 데이터 추가
            addToHistory(hybridState);

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
