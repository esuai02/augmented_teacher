<?php
// 에러 표시 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 간단한 출력
echo '<h1>PHP 테스트 파일</h1>';
echo '<p>현재 시간: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>PHP 버전: ' . phpversion() . '</p>';
?> 