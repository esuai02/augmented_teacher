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

        /* 인지노드 미로 시각화 */
        #quantum-maze {
            width: 100%;
            height: 600px;
            background: var(--bg-dark);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        #maze-svg {
            width: 100%;
            height: 100%;
            cursor: grab;
        }

        #maze-svg:active {
            cursor: grabbing;
        }

        .maze-node {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .maze-node:hover {
            filter: brightness(1.3);
            transform: scale(1.1);
        }

        .maze-node.visited {
            opacity: 0.7;
        }

        .maze-node.current {
            filter: brightness(1.5) drop-shadow(0 0 8px var(--primary));
            animation: pulse-node 2s infinite;
        }

        @keyframes pulse-node {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .maze-path {
            stroke: var(--primary);
            stroke-width: 2;
            fill: none;
            opacity: 0.6;
        }

        .maze-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .scale-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 200px;
        }

        .scale-slider {
            flex: 1;
            height: 6px;
            background: var(--bg-dark);
            border-radius: 3px;
            outline: none;
            -webkit-appearance: none;
        }

        .scale-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
        }

        .scale-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }

        .scale-btn {
            padding: 6px 12px;
            font-size: 0.85rem;
            min-width: 60px;
        }

        .maze-actions {
            display: flex;
            gap: 8px;
        }

        .maze-btn {
            padding: 8px 16px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* 모달 스타일 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--border);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .suggestion-item {
            padding: 15px;
            background: var(--bg-dark);
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
        }

        .suggestion-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .version-item {
            padding: 12px;
            background: var(--bg-dark);
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
        }

        .version-item:hover {
            border-color: var(--primary);
            background: var(--bg-hover);
        }

        .version-item.active {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.2);
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
            <!-- 인지노드 미로 시각화 -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🧠 인지노드 미로</div>
                        <div class="maze-actions">
                            <button class="btn btn-secondary maze-btn" onclick="if(typeof toggleGrowthMenu==='function') toggleGrowthMenu()">
                                🌱 성장
                            </button>
                            <button class="btn btn-secondary maze-btn" onclick="if(typeof openVersionHistory==='function') openVersionHistory()">
                                📜 버전
                            </button>
                        </div>
                    </div>

                    <!-- 맵 크기 조절 컨트롤 -->
                    <div class="maze-controls">
                        <div class="scale-controls">
                            <span style="font-size: 0.85rem; color: var(--text-secondary); min-width: 60px;">크기:</span>
                            <input type="range" id="mapScaleSlider" class="scale-slider" min="50" max="200" value="100" 
                                   oninput="if(typeof updateMapScale==='function') updateMapScale(this.value)">
                            <span id="scaleValue" style="font-size: 0.85rem; color: var(--text-primary); min-width: 40px; text-align: right;">100%</span>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary scale-btn" onclick="if(typeof updateMapScale==='function') updateMapScale(50)">축소</button>
                            <button class="btn btn-secondary scale-btn" onclick="if(typeof updateMapScale==='function') updateMapScale(100)">기본</button>
                            <button class="btn btn-secondary scale-btn" onclick="if(typeof updateMapScale==='function') updateMapScale(150)">확대</button>
                        </div>
                        <div style="display: flex; gap: 6px; margin-left: auto;">
                            <button class="btn btn-secondary maze-btn" onclick="if(typeof backtrackOne==='function') backtrackOne()">
                                ⬅️ 뒤로
                            </button>
                            <button class="btn btn-secondary maze-btn" onclick="if(typeof resetMaze==='function') resetMaze()">
                                🔄 리셋
                            </button>
                        </div>
                    </div>

                    <!-- SVG 미로 -->
                    <div id="quantum-maze">
                        <svg id="maze-svg" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid meet">
                            <!-- 경로들 -->
                            <path class="maze-path" d="M 100 300 L 200 300" />
                            <path class="maze-path" d="M 200 300 L 300 200" />
                            <path class="maze-path" d="M 300 200 L 400 200" />
                            <path class="maze-path" d="M 400 200 L 500 300" />
                            <path class="maze-path" d="M 500 300 L 600 300" />
                            <path class="maze-path" d="M 600 300 L 700 400" />
                            
                            <!-- 노드들 -->
                            <circle class="maze-node current" cx="100" cy="300" r="15" fill="#6366f1" data-node-id="1" onclick="if(typeof handleNodeClick==='function') handleNodeClick(1)" />
                            <text x="100" y="305" text-anchor="middle" fill="white" font-size="10" font-weight="bold">1</text>
                            
                            <circle class="maze-node" cx="200" cy="300" r="12" fill="#10b981" data-node-id="2" onclick="if(typeof handleNodeClick==='function') handleNodeClick(2)" />
                            <text x="200" y="305" text-anchor="middle" fill="white" font-size="9">2</text>
                            
                            <circle class="maze-node" cx="300" cy="200" r="12" fill="#f59e0b" data-node-id="3" onclick="if(typeof handleNodeClick==='function') handleNodeClick(3)" />
                            <text x="300" y="205" text-anchor="middle" fill="white" font-size="9">3</text>
                            
                            <circle class="maze-node" cx="400" cy="200" r="12" fill="#ef4444" data-node-id="4" onclick="if(typeof handleNodeClick==='function') handleNodeClick(4)" />
                            <text x="400" y="205" text-anchor="middle" fill="white" font-size="9">4</text>
                            
                            <circle class="maze-node" cx="500" cy="300" r="12" fill="#8b5cf6" data-node-id="5" onclick="if(typeof handleNodeClick==='function') handleNodeClick(5)" />
                            <text x="500" y="305" text-anchor="middle" fill="white" font-size="9">5</text>
                            
                            <circle class="maze-node" cx="600" cy="300" r="12" fill="#06b6d4" data-node-id="6" onclick="if(typeof handleNodeClick==='function') handleNodeClick(6)" />
                            <text x="600" y="305" text-anchor="middle" fill="white" font-size="9">6</text>
                            
                            <circle class="maze-node" cx="700" cy="400" r="12" fill="#ec4899" data-node-id="7" onclick="if(typeof handleNodeClick==='function') handleNodeClick(7)" />
                            <text x="700" y="405" text-anchor="middle" fill="white" font-size="9">7</text>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 메인 상태 모니터 -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 실시간 상태 모니터</div>
                    </div>

                    <div class="realtime-indicator">
                        <div class="pulse"></div>
                        <span>Fast Loop 실행 중 (0.5초 주기)</span>
                        <span style="margin-left: auto; color: var(--text-secondary);" id="loopCount">0회</span>
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
                    <h4 style="margin: 25px 0 15px; font-size: 0.95rem;">상태 분포 (State Vector)</h4>
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

    <!-- 성장 메뉴 모달 -->
    <div id="growthModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">🌱 인지노드 성장</div>
                <button class="modal-close" onclick="if(typeof closeGrowthModal==='function') closeGrowthModal()">&times;</button>
            </div>
            <div>
                <p style="margin-bottom: 15px; color: var(--text-secondary);">인지노드를 성장시켜 학습 경로를 확장하세요.</p>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="if(typeof generateSuggestion==='function') generateSuggestion()" style="width: 100%;">
                        🤖 AI 제안 받기
                    </button>
                </div>
                <div id="suggestionsList"></div>
            </div>
        </div>
    </div>

    <!-- AI 제안 모달 -->
    <div id="suggestionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">💡 AI 제안</div>
                <button class="modal-close" onclick="if(typeof closeSuggestionModal==='function') closeSuggestionModal()">&times;</button>
            </div>
            <div id="suggestionContent">
                <p>AI가 학습 경로를 분석 중...</p>
            </div>
        </div>
    </div>

    <!-- 버전 관리 모달 -->
    <div id="versionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">📜 버전 관리</div>
                <button class="modal-close" onclick="if(typeof closeVersionHistory==='function') closeVersionHistory()">&times;</button>
            </div>
            <div id="versionList">
                <div class="version-item active" onclick="if(typeof rollbackVersion==='function') rollbackVersion(0)">
                    <div style="font-weight: 600;">현재 버전</div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <?php echo date('Y-m-d H:i:s'); ?>
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
            updateUI(hybridState);
            startAutoLoop();
            addLog('⚛️ HybridStateStabilizer 연결됨 | User ID: <?php echo $userId; ?>', 'prediction');
            
            // 미로 초기화
            initMaze();
        });

        // ============================================================================
        // 인지노드 미로 시각화 기능
        // ============================================================================
        
        let mazeState = {
            currentNode: 1,
            visitedNodes: [1],
            path: [1],
            scale: 100,
            versions: [{
                id: 0,
                timestamp: new Date().toISOString(),
                node: 1,
                path: [1]
            }]
        };

        // 미로 초기화
        function initMaze() {
            updateMazeDisplay();
            updateMapScale(100);
        }

        // 맵 크기 조절
        function updateMapScale(value) {
            mazeState.scale = parseInt(value);
            const svg = document.getElementById('maze-svg');
            if (svg) {
                const scale = value / 100;
                svg.style.transform = `scale(${scale})`;
                svg.style.transformOrigin = 'center center';
                
                // 컨테이너 크기 조절
                const container = document.getElementById('quantum-maze');
                if (container) {
                    const baseHeight = 600;
                    container.style.height = (baseHeight * scale) + 'px';
                }
            }
            
            // 슬라이더 및 값 업데이트
            const slider = document.getElementById('mapScaleSlider');
            const valueDisplay = document.getElementById('scaleValue');
            if (slider) slider.value = value;
            if (valueDisplay) valueDisplay.textContent = value + '%';
            
            addLog('🔍 맵 크기 조절: ' + value + '%', 'prediction');
        }

        // 노드 클릭 핸들러
        function handleNodeClick(nodeId) {
            if (mazeState.visitedNodes.includes(nodeId)) {
                addLog('⚠️ 이미 방문한 노드입니다: ' + nodeId, 'event');
                return;
            }

            mazeState.currentNode = nodeId;
            mazeState.visitedNodes.push(nodeId);
            mazeState.path.push(nodeId);
            
            updateMazeDisplay();
            addLog('📍 노드 ' + nodeId + ' 방문', 'event');
        }

        // 미로 표시 업데이트
        function updateMazeDisplay() {
            const nodes = document.querySelectorAll('.maze-node');
            nodes.forEach(node => {
                const nodeId = parseInt(node.getAttribute('data-node-id'));
                node.classList.remove('current', 'visited');
                
                if (nodeId === mazeState.currentNode) {
                    node.classList.add('current');
                } else if (mazeState.visitedNodes.includes(nodeId)) {
                    node.classList.add('visited');
                }
            });
        }

        // 뒤로가기
        function backtrackOne() {
            if (mazeState.path.length <= 1) {
                addLog('⚠️ 더 이상 뒤로 갈 수 없습니다', 'event');
                return;
            }

            mazeState.path.pop();
            mazeState.currentNode = mazeState.path[mazeState.path.length - 1];
            updateMazeDisplay();
            addLog('⬅️ 한 단계 뒤로 이동: 노드 ' + mazeState.currentNode, 'event');
        }

        // 리셋
        function resetMaze() {
            if (!confirm('미로를 초기 상태로 리셋하시겠습니까?')) {
                return;
            }

            mazeState.currentNode = 1;
            mazeState.visitedNodes = [1];
            mazeState.path = [1];
            updateMazeDisplay();
            addLog('🔄 미로 리셋 완료', 'prediction');
        }

        // 성장 메뉴 토글
        function toggleGrowthMenu() {
            const modal = document.getElementById('growthModal');
            if (modal) {
                modal.classList.toggle('active');
            }
        }

        // 성장 모달 열기
        function openGrowthModal() {
            const modal = document.getElementById('growthModal');
            if (modal) {
                modal.classList.add('active');
            }
        }

        // 성장 모달 닫기
        function closeGrowthModal() {
            const modal = document.getElementById('growthModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // AI 제안 생성
        async function generateSuggestion() {
            const content = document.getElementById('suggestionContent');
            if (content) {
                content.innerHTML = '<p>🤖 AI가 학습 경로를 분석 중...</p>';
            }

            const modal = document.getElementById('suggestionModal');
            if (modal) {
                modal.classList.add('active');
            }

            // 시뮬레이션: AI 제안 생성
            setTimeout(() => {
                const suggestions = [
                    {
                        id: 1,
                        title: '인지 패턴 연결',
                        description: '노드 3과 노드 5를 연결하여 새로운 학습 경로를 만들어보세요.',
                        confidence: 0.85
                    },
                    {
                        id: 2,
                        title: '약점 보완',
                        description: '노드 4의 약점을 보완하기 위해 추가 연습을 권장합니다.',
                        confidence: 0.72
                    }
                ];

                if (content) {
                    let html = '';
                    suggestions.forEach(suggestion => {
                        html += `
                            <div class="suggestion-item">
                                <div style="font-weight: 600; margin-bottom: 8px;">${suggestion.title}</div>
                                <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 10px;">
                                    ${suggestion.description}
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">
                                    확신도: ${Math.round(suggestion.confidence * 100)}%
                                </div>
                                <div class="suggestion-actions">
                                    <button class="btn btn-primary" onclick="if(typeof approveSuggestion==='function') approveSuggestion(${suggestion.id})">
                                        ✅ 승인
                                    </button>
                                    <button class="btn btn-secondary" onclick="if(typeof rejectSuggestion==='function') rejectSuggestion(${suggestion.id})">
                                        ❌ 거부
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    content.innerHTML = html;
                }
            }, 1500);

            addLog('🤖 AI 제안 생성 중...', 'prediction');
        }

        // AI 제안 승인
        function approveSuggestion(suggestionId) {
            addLog('✅ AI 제안 ' + suggestionId + ' 승인됨', 'event');
            closeSuggestionModal();
            closeGrowthModal();
        }

        // AI 제안 거부
        function rejectSuggestion(suggestionId) {
            addLog('❌ AI 제안 ' + suggestionId + ' 거부됨', 'event');
            closeSuggestionModal();
        }

        // 제안 모달 닫기
        function closeSuggestionModal() {
            const modal = document.getElementById('suggestionModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // 버전 관리 열기
        function openVersionHistory() {
            const modal = document.getElementById('versionModal');
            const list = document.getElementById('versionList');
            
            if (modal && list) {
                // 버전 목록 생성
                let html = '';
                mazeState.versions.forEach((version, index) => {
                    const date = new Date(version.timestamp);
                    const isActive = index === mazeState.versions.length - 1;
                    html += `
                        <div class="version-item ${isActive ? 'active' : ''}" onclick="if(typeof rollbackVersion==='function') rollbackVersion(${index})">
                            <div style="font-weight: 600;">버전 ${index + 1} ${isActive ? '(현재)' : ''}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                                ${date.toLocaleString('ko-KR')} | 노드: ${version.node} | 경로: [${version.path.join(', ')}]
                            </div>
                        </div>
                    `;
                });
                list.innerHTML = html;
                modal.classList.add('active');
            }
        }

        // 버전 관리 닫기
        function closeVersionHistory() {
            const modal = document.getElementById('versionModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // 버전 롤백
        function rollbackVersion(versionIndex) {
            if (versionIndex >= mazeState.versions.length) {
                addLog('⚠️ 잘못된 버전 인덱스입니다', 'error');
                return;
            }

            const version = mazeState.versions[versionIndex];
            mazeState.currentNode = version.node;
            mazeState.visitedNodes = [...version.path];
            mazeState.path = [...version.path];
            
            updateMazeDisplay();
            closeVersionHistory();
            addLog('📜 버전 ' + (versionIndex + 1) + '로 롤백 완료', 'event');
        }

        // 모달 외부 클릭 시 닫기
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
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
