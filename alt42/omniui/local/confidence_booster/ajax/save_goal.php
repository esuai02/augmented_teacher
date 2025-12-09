<?php
/**
 * 오늘의 목표 저장
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$goal = $input['goal'] ?? '';

// 세션에 저장
$_SESSION['today_goal'] = $goal;

echo json_encode([
    'success' => true,
    'message' => '목표가 저장되었습니다.'
], JSON_UNESCAPED_UNICODE);
?>