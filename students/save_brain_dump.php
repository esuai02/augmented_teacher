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
    $tags = required_param('tags', PARAM_RAW);
    
    // 기존 태그 데이터 확인
    $existingRecord = $DB->get_record('mdl_abessi_brain_dump', array('userid' => $userid));
    
    $data = new stdClass();
    $data->userid = $userid;
    $data->tags = $tags;
    $data->timecreated = time();
    
    if ($existingRecord) {
        // 기존 레코드 업데이트
        $data->id = $existingRecord->id;
        $data->timemodified = time();
        $result = $DB->update_record('mdl_abessi_brain_dump', $data);
    } else {
        // 새 레코드 생성
        $result = $DB->insert_record('mdl_abessi_brain_dump', $data);
    }
    
    if ($result) {
        echo json_encode(array('status' => 'success', 'message' => '태그가 성공적으로 저장되었습니다.'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => '태그 저장에 실패했습니다.'));
    }
} catch (Exception $e) {
    echo json_encode(array('status' => 'error', 'message' => '오류: ' . $e->getMessage()));
}
?> 