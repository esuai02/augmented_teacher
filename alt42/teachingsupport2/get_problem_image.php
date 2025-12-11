<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $CFG, $DB;
require_login();

$filename = required_param('file', PARAM_TEXT);
$interaction_id = optional_param('id', 0, PARAM_INT);

// 보안 검증
$filename = clean_param($filename, PARAM_FILE);
if (empty($filename)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// interaction_id가 제공된 경우 권한 확인
if ($interaction_id > 0) {
    $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interaction_id));
    if (!$interaction) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    // 학생 본인이거나 담당 교사이거나 관리자만 접근 가능
    $context = context_system::instance();
    if ($interaction->userid != $USER->id && 
        $interaction->teacherid != $USER->id && 
        !has_capability('moodle/site:config', $context)) {
        header('HTTP/1.0 403 Forbidden');
        exit;
    }
}

// 파일 경로
$filepath = $CFG->dataroot . '/ktm_teaching/images/' . $filename;

// 파일이 base64 데이터인 경우 (구버전 호환성)
if (strpos($filename, 'data:') === 0) {
    // base64 데이터 직접 출력
    list($type, $data) = explode(';', $filename);
    list(, $data) = explode(',', $data);
    
    // MIME 타입 추출
    $mime = str_replace('data:', '', $type);
    
    header('Content-Type: ' . $mime);
    echo base64_decode($data);
    exit;
}

// 파일 존재 확인
if (!file_exists($filepath)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// MIME 타입 결정
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filepath);
finfo_close($finfo);

// 이미지 파일인지 확인
if (strpos($mime, 'image/') !== 0) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// 캐시 헤더
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=86400'); // 1일 캐시

// 파일 출력
readfile($filepath);
?>