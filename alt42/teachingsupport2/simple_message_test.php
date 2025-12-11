<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$studentid = $_POST['studentid'] ?? $_GET['studentid'] ?? 0;
$teacherid = $_POST['teacherid'] ?? $_GET['teacherid'] ?? $USER->id;

try {
    if (!$studentid) {
        throw new Exception('학생 ID가 필요합니다.');
    }
    
    // 사용자 정보 확인
    $student = $DB->get_record('user', array('id' => $studentid));
    $teacher = $DB->get_record('user', array('id' => $teacherid));
    
    if (!$student) {
        throw new Exception('학생을 찾을 수 없습니다.');
    }
    
    if (!$teacher) {
        throw new Exception('선생님을 찾을 수 없습니다.');
    }
    
    // 자체 메시지 테이블에 저장
    $messagedata = new stdClass();
    $messagedata->teacher_id = $teacherid;
    $messagedata->student_id = $studentid;
    $messagedata->interaction_id = null;
    $messagedata->subject = '📚 하이튜터링 테스트 메시지';
    $messagedata->message_content = '테스트 메시지입니다. 시간: ' . date('Y-m-d H:i:s');
    $messagedata->solution_text = '테스트 풀이 내용';
    $messagedata->audio_url = '';
    $messagedata->explanation_url = '';
    $messagedata->is_read = 0;
    $messagedata->timecreated = time();
    $messagedata->timeread = null;
    
    // ktm_mathmessages 테이블에 삽입
    $message_id = $DB->insert_record('ktm_mathmessages', $messagedata);
    
    if ($message_id) {
        echo json_encode(array(
            'success' => true,
            'message_id' => $message_id,
            'student_name' => fullname($student),
            'teacher_name' => fullname($teacher),
            'debug_info' => array(
                'studentid' => $studentid,
                'teacherid' => $teacherid,
                'table_used' => 'ktm_mathmessages',
                'message_data' => $messagedata
            )
        ));
    } else {
        throw new Exception('메시지 저장에 실패했습니다.');
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => array(
            'studentid' => $studentid,
            'teacherid' => $teacherid,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        )
    ));
}
?>