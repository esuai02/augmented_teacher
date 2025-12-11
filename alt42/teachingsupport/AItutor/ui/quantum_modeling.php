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

        // HybridStateTracker 인스턴스 (전역)
        let tracker = null;

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            updateUI(hybridState);
            startAutoLoop();
            addLog('⚛️ HybridStateStabilizer 연결됨 | User ID: <?php echo $userId; ?>', 'prediction');

            // [quantum_modeling.php:L965] HybridStateTracker 초기화 및 시작
            try {
                tracker = new HybridStateTracker({
                    userId: <?php echo $userId; ?>,
                    debug: true,
                    onStateChange: (newState) => {
                        // 상태 변경 시 UI 업데이트
                        // snake_case (서버) → camelCase (JS) 매핑
                        hybridState = {
                            ...hybridState,
                            predicted_state: newState.predicted_state ?? newState.predictedState ?? hybridState.predicted_state,
                            uncertainty: newState.uncertainty ?? hybridState.uncertainty,
                            confidence: newState.confidence ?? hybridState.confidence,
                            state_vector: newState.state_vector ?? newState.stateVector ?? hybridState.state_vector,
                            dominant_state: newState.dominant_state ?? newState.dominantState ?? hybridState.dominant_state,
                            needs_ping: newState.needs_ping ?? newState.needsPing ?? hybridState.needs_ping
                        };
                        updateUI(hybridState);
                        const stateValue = newState.predicted_state ?? newState.predictedState ?? 0.5;
                        addLog('🔄 상태 업데이트: ' + Math.round(stateValue * 100) + '%', 'prediction');
                    },
                    onPingFired: (pingData) => {
                        // Active Ping 발사 시 로그
                        addLog('🎯 Active Ping 발사 (Level ' + pingData.level + ')', 'ping');
                    },
                    onCorrectionMade: (correction) => {
                        // Kalman 보정 시 로그
                        addLog('📊 Kalman 보정: ' + correction.eventType, 'event');
                    }
                });
                tracker.start();
                addLog('✅ HybridStateTracker 시작됨', 'prediction');
            } catch (error) {
                console.error('[quantum_modeling.php] HybridStateTracker 초기화 실패:', error);
                addLog('❌ Tracker 초기화 실패: ' + error.message, 'error');
            }
        });

        // 페이지 언로드 시 tracker 중지
        window.addEventListener('beforeunload', () => {
            if (tracker) {
                tracker.stop();
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
