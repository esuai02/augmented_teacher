<?php
// Moodle 로그인 페이지로 리다이렉트
// 실제 Moodle 설치 경로에 맞게 수정하세요

$moodle_url = 'https://your-moodle-domain.com/login/index.php';
$return_url = urlencode($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/exam_system.php');

// Moodle 로그인 페이지로 리다이렉트
header("Location: {$moodle_url}?wantsurl={$return_url}");
exit;
?>