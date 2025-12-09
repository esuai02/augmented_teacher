<?php
// 리포트 이미지 표시 페이지
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 이미지 파일명 가져오기
$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

if (empty($filename)) {
    http_response_code(404);
    die('이미지 파일을 찾을 수 없습니다.');
}

// 파일 경로
$filePath = '/home/moodle/public_html/studentimg/' . $filename;

// 파일 존재 확인
if (!file_exists($filePath)) {
    http_response_code(404);
    die('이미지 파일을 찾을 수 없습니다. (file: ' . __FILE__ . ', line: ' . __LINE__ . ')');
}

// 이미지 파일인지 확인
$imageInfo = @getimagesize($filePath);
if ($imageInfo === false) {
    http_response_code(400);
    die('유효하지 않은 이미지 파일입니다. (file: ' . __FILE__ . ', line: ' . __LINE__ . ')');
}

// MIME 타입 설정
$mimeType = $imageInfo['mime'];
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=3600');

// 이미지 출력
readfile($filePath);
exit;
?>

