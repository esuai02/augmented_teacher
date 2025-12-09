<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json');

try {
    $userid = required_param('userid', PARAM_INT);
    
    // 태그 데이터 조회
    $record = $DB->get_record('mdl_abessi_brain_dump', array('userid' => $userid));
    
    if ($record && $record->tags) {
        echo json_encode(array(
            'status' => 'success', 
            'tags' => $record->tags,
            'message' => '태그가 성공적으로 로드되었습니다.'
        ));
    } else {
        echo json_encode(array(
            'status' => 'success', 
            'tags' => '[]',
            'message' => '저장된 태그가 없습니다.'
        ));
    }
} catch (Exception $e) {
    echo json_encode(array('status' => 'error', 'message' => '오류: ' . $e->getMessage()));
}
?> 