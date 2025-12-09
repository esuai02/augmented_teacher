<?php
/**
 * 루틴 진행상황 불러오기 AJAX 핸들러
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

// DB 연결
try {
    require_once('config.php');

    // 오늘의 진행상황 조회
    $date = date('Y-m-d');
    $sql = "SELECT progress_data
            FROM mdl_abessi_progress
            WHERE userid = ? AND date_format(from_unixtime(timecreated), '%Y-%m-%d') = ?
            ORDER BY timemodified DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date]);
    $result = $stmt->fetch();

    if ($result && $result['progress_data']) {
        $progress_data = json_decode($result['progress_data'], true);
        $progress = isset($progress_data['routine_progress']) ? $progress_data['routine_progress'] : 0;
        echo json_encode(['success' => true, 'progress' => $progress]);
    } else {
        echo json_encode(['success' => true, 'progress' => 0]);
    }

} catch (Exception $e) {
    error_log("Progress load error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>