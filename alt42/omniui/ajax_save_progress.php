<?php
/**
 * 루틴 진행상황 저장 AJAX 핸들러
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// CSRF 토큰 검증
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$progress = isset($input['routine_progress']) ? intval($input['routine_progress']) : 0;
$date = isset($input['date']) ? $input['date'] : date('Y-m-d');

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

// DB 연결
try {
    require_once('config.php');

    // 기존 진행상황 확인
    $sql = "SELECT id FROM mdl_abessi_progress
            WHERE userid = ? AND date_format(from_unixtime(timecreated), '%Y-%m-%d') = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 업데이트
        $sql = "UPDATE mdl_abessi_progress
                SET progress_data = ?, timemodified = ?
                WHERE id = ?";
        $progress_data = json_encode(['routine_progress' => $progress]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$progress_data, time(), $existing['id']]);
    } else {
        // 새로 삽입
        $sql = "INSERT INTO mdl_abessi_progress (userid, progress_data, timecreated, timemodified)
                VALUES (?, ?, ?, ?)";
        $progress_data = json_encode(['routine_progress' => $progress]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $progress_data, time(), time()]);
    }

    echo json_encode(['success' => true, 'progress' => $progress]);

} catch (Exception $e) {
    error_log("Progress save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>