<?php
/**
 * 활동 기록 저장 AJAX 핸들러
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
$activity = isset($input['activity']) ? $input['activity'] : '';
$timestamp = isset($input['timestamp']) ? intval($input['timestamp']) : time();

if ($user_id <= 0 || empty($activity)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// DB 연결 (CLAUDE.md 설정 사용)
try {
    require_once('config.php');

    // 활동 로그 저장
    $sql = "INSERT INTO mdl_abessi_missionlog (userid, page, timecreated) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, 'auto_intervention_' . $activity, $timestamp]);

    echo json_encode(['success' => true, 'message' => 'Activity logged']);

} catch (Exception $e) {
    error_log("Activity log error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>