<?php
/**
 * 학습 타이머 API (기존 DB 사용)
 * mdl_abessi_tracking 테이블 활용
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once 'config.php';

try {
    // 데이터베이스 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 요청 데이터 파싱
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];

    $action = $data['action'] ?? $_GET['action'] ?? 'status';
    $user_id = $data['user_id'] ?? $_GET['user_id'] ?? $_SESSION['userid'] ?? null;

    if (!$user_id) {
        throw new Exception('사용자 ID가 필요합니다.');
    }

    $response = [];

    // 액션별 처리
    switch ($action) {
        case 'start':
            $response = startTimer($pdo, $user_id);
            break;

        case 'pause':
            $duration = $data['duration'] ?? 0;
            $response = pauseTimer($pdo, $user_id, $duration);
            break;

        case 'reset':
            $duration = $data['duration'] ?? 0;
            $response = resetTimer($pdo, $user_id, $duration);
            break;

        case 'status':
            $response = getTimerStatus($pdo, $user_id);
            break;

        case 'history':
            $limit = $data['limit'] ?? 10;
            $response = getTimerHistory($pdo, $user_id, $limit);
            break;

        case 'stats':
            $response = getTimerStats($pdo, $user_id);
            break;

        default:
            throw new Exception('알 수 없는 액션입니다: ' . $action);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Learning timer API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 타이머 시작 (mdl_abessi_tracking 사용)
 */
function startTimer($pdo, $user_id) {
    $now = time();

    // 새 세션 시작
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_tracking
        (userid, type, status, duration, timemodified)
        VALUES (?, 'timer_start', 'running', 0, ?)
    ");
    $stmt->execute([$user_id, $now]);

    $session_id = $pdo->lastInsertId();

    // 활동 로그 기록
    logActivity($pdo, $user_id, 'timer_start');

    return [
        'success' => true,
        'session_id' => $session_id,
        'started_at' => $now,
        'message' => '타이머가 시작되었습니다.'
    ];
}

/**
 * 타이머 일시정지
 */
function pauseTimer($pdo, $user_id, $duration) {
    $now = time();

    // 현재 진행중인 세션 확인
    $stmt = $pdo->prepare("
        SELECT id, timemodified
        FROM mdl_abessi_tracking
        WHERE userid = ? AND type = 'timer_start' AND status = 'running'
        ORDER BY timemodified DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $session = $stmt->fetch();

    if (!$session) {
        throw new Exception('진행중인 타이머가 없습니다.');
    }

    // 일시정지 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_tracking
        (userid, type, status, duration, timemodified)
        VALUES (?, 'timer_pause', 'paused', ?, ?)
    ");
    $stmt->execute([$user_id, $duration, $now]);

    // 활동 로그 기록
    logActivity($pdo, $user_id, 'timer_pause');

    return [
        'success' => true,
        'duration' => $duration,
        'paused_at' => $now,
        'message' => '타이머가 일시정지되었습니다.'
    ];
}

/**
 * 타이머 초기화
 */
function resetTimer($pdo, $user_id, $duration) {
    $now = time();

    // 학습 세션 완료로 기록 (duration이 0보다 크면)
    if ($duration > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_tracking
            (userid, type, status, duration, timemodified)
            VALUES (?, 'timer_complete', 'completed', ?, ?)
        ");
        $stmt->execute([$user_id, $duration, $now]);

        // 학습 시간을 분 단위로 계산
        $study_minutes = round($duration / 60);

        // 활동 로그에 학습 시간 기록
        logActivity($pdo, $user_id, 'timer_complete');
    }

    // 초기화 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_tracking
        (userid, type, status, duration, timemodified)
        VALUES (?, 'timer_reset', 'idle', ?, ?)
    ");
    $stmt->execute([$user_id, $duration, $now]);

    return [
        'success' => true,
        'duration' => $duration,
        'reset_at' => $now,
        'message' => '타이머가 초기화되었습니다.',
        'saved' => $duration > 0
    ];
}

/**
 * 타이머 상태 조회
 */
function getTimerStatus($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT type, status, duration, timemodified
        FROM mdl_abessi_tracking
        WHERE userid = ?
        ORDER BY timemodified DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $status = $stmt->fetch();

    if (!$status) {
        return [
            'success' => true,
            'status' => 'idle',
            'message' => '타이머가 준비 상태입니다.'
        ];
    }

    return [
        'success' => true,
        'status' => $status['status'] ?? 'idle',
        'last_action' => $status['type'],
        'duration' => $status['duration'] ?? 0,
        'timestamp' => $status['timemodified']
    ];
}

/**
 * 타이머 기록 조회
 */
function getTimerHistory($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT type, status, duration, timemodified
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
        AND duration > 0
        ORDER BY timemodified DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);

    $history = [];
    while ($row = $stmt->fetch()) {
        $history[] = [
            'action' => $row['type'],
            'duration' => $row['duration'],
            'duration_formatted' => formatDuration($row['duration']),
            'date' => date('Y-m-d H:i:s', $row['timemodified']),
            'timestamp' => $row['timemodified']
        ];
    }

    return [
        'success' => true,
        'history' => $history,
        'count' => count($history)
    ];
}

/**
 * 타이머 통계
 */
function getTimerStats($pdo, $user_id) {
    // 오늘의 학습 시간
    $today_start = strtotime(date('Y-m-d 00:00:00'));
    $today_end = strtotime(date('Y-m-d 23:59:59'));

    $stmt = $pdo->prepare("
        SELECT SUM(duration) as total_seconds
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
        AND timemodified BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $today_start, $today_end]);
    $today_seconds = $stmt->fetchColumn() ?? 0;

    // 이번 주 학습 시간
    $week_start = strtotime('monday this week 00:00:00');
    $stmt = $pdo->prepare("
        SELECT SUM(duration) as total_seconds
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
        AND timemodified >= ?
    ");
    $stmt->execute([$user_id, $week_start]);
    $week_seconds = $stmt->fetchColumn() ?? 0;

    // 이번 달 학습 시간
    $month_start = strtotime(date('Y-m-01 00:00:00'));
    $stmt = $pdo->prepare("
        SELECT SUM(duration) as total_seconds
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
        AND timemodified >= ?
    ");
    $stmt->execute([$user_id, $month_start]);
    $month_seconds = $stmt->fetchColumn() ?? 0;

    // 총 학습 시간
    $stmt = $pdo->prepare("
        SELECT SUM(duration) as total_seconds
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
    ");
    $stmt->execute([$user_id]);
    $total_seconds = $stmt->fetchColumn() ?? 0;

    // 평균 세션 시간
    $stmt = $pdo->prepare("
        SELECT AVG(duration) as avg_seconds
        FROM mdl_abessi_tracking
        WHERE userid = ?
        AND type IN ('timer_complete', 'timer_reset')
        AND duration > 0
    ");
    $stmt->execute([$user_id]);
    $avg_seconds = $stmt->fetchColumn() ?? 0;

    return [
        'success' => true,
        'stats' => [
            'today' => [
                'seconds' => $today_seconds,
                'formatted' => formatDuration($today_seconds)
            ],
            'week' => [
                'seconds' => $week_seconds,
                'formatted' => formatDuration($week_seconds)
            ],
            'month' => [
                'seconds' => $month_seconds,
                'formatted' => formatDuration($month_seconds)
            ],
            'total' => [
                'seconds' => $total_seconds,
                'formatted' => formatDuration($total_seconds)
            ],
            'average_session' => [
                'seconds' => round($avg_seconds),
                'formatted' => formatDuration(round($avg_seconds))
            ]
        ]
    ];
}

/**
 * 활동 로그 기록
 */
function logActivity($pdo, $user_id, $page) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $page, time()]);
    } catch (Exception $e) {
        // 로그 기록 실패는 무시
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * 시간 포맷팅 (초 -> HH:MM:SS)
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}
?>
