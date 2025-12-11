<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$interactionId = $input['messageId'] ?? 0;  // 이제 interaction ID를 받음
$studentId = $input['studentId'] ?? $USER->id;

try {
    // 권한 확인
    $context = context_system::instance();
    if ($studentId != $USER->id && !has_capability('moodle/site:config', $context)) {
        throw new Exception('접근 권한이 없습니다.');
    }

    // 읽음 상태 테이블 생성 (없으면)
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('ktm_interaction_read_status')) {
        $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_interaction_read_status (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            interaction_id BIGINT(10) NOT NULL,
            student_id BIGINT(10) NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timeread BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_interaction_student (interaction_id, student_id),
            INDEX idx_student_id (student_id)
        )";
        $DB->execute($sql);
    }

    // 기존 읽음 상태 확인
    $existing = $DB->get_record('ktm_interaction_read_status', 
        array('interaction_id' => $interactionId, 'student_id' => $studentId));
    
    if ($existing) {
        // 이미 읽음 상태인 경우
        if ($existing->is_read == 1) {
            echo json_encode(array(
                'success' => true,
                'message' => '이미 읽음 처리된 메시지입니다.'
            ));
            return;
        }
        
        // 업데이트
        $existing->is_read = 1;
        $existing->timeread = time();
        $DB->update_record('ktm_interaction_read_status', $existing);
    } else {
        // 새로 삽입
        $read_status = new stdClass();
        $read_status->interaction_id = $interactionId;
        $read_status->student_id = $studentId;
        $read_status->is_read = 1;
        $read_status->timeread = time();
        $DB->insert_record('ktm_interaction_read_status', $read_status);
    }
    
    // 이벤트 로그 추가 (선택사항)
    if ($DB->get_manager()->table_exists('ktm_teaching_events')) {
        $event = new stdClass();
        $event->userid = $studentId;
        $event->interactionid = $interactionId;
        $event->event_type = 'message_read';
        $event->event_description = '학생이 문제 해설을 확인했습니다.';
        $event->timecreated = time();
        
        $DB->insert_record('ktm_teaching_events', $event);
    }
    
    echo json_encode(array(
        'success' => true,
        'message' => '메시지가 읽음으로 표시되었습니다.'
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>