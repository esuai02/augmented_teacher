<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$debug_info = array();
$debug_info['input_received'] = $input;
$debug_info['user_id'] = $USER->id;
$debug_info['timestamp'] = time();

try {
    // 1. 기본 정보 수집
    $debug_info['config_check'] = array(
        'prefix' => $CFG->prefix ?? 'unknown',
        'dbtype' => $CFG->dbtype ?? 'unknown'
    );
    
    // 2. 테이블 존재 확인
    $dbman = $DB->get_manager();
    $debug_info['table_exists'] = array(
        'ktm_teaching_interactions' => $dbman->table_exists('ktm_teaching_interactions'),
        'ktm_teaching_events' => $dbman->table_exists('ktm_teaching_events'),
        'user' => $dbman->table_exists('user')
    );
    
    // 3. 학생 ID 검증
    $studentId = $input['studentId'] ?? 0;
    $debug_info['student_id'] = $studentId;
    
    if ($studentId > 0) {
        try {
            $student = $DB->get_record('user', array('id' => $studentId));
            $debug_info['student_exists'] = $student ? true : false;
            if ($student) {
                $debug_info['student_info'] = array(
                    'id' => $student->id,
                    'username' => $student->username,
                    'email' => $student->email
                );
            }
        } catch (Exception $e) {
            $debug_info['student_check_error'] = $e->getMessage();
        }
    }
    
    // 4. 테이블 생성 시도
    if (!$debug_info['table_exists']['ktm_teaching_interactions']) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_teaching_interactions (
                id BIGINT(10) NOT NULL AUTO_INCREMENT,
                userid BIGINT(10) NOT NULL,
                teacherid BIGINT(10) DEFAULT NULL,
                problem_type VARCHAR(255) DEFAULT NULL,
                problem_text LONGTEXT DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                timecreated BIGINT(10) NOT NULL,
                timemodified BIGINT(10) NOT NULL,
                PRIMARY KEY (id)
            )";
            
            $DB->execute($sql);
            $debug_info['table_creation'] = 'success';
            
            // 다시 확인
            $debug_info['table_exists_after_creation'] = $dbman->table_exists('ktm_teaching_interactions');
        } catch (Exception $e) {
            $debug_info['table_creation_error'] = $e->getMessage();
        }
    }
    
    // 5. 간단한 삽입 테스트
    if ($studentId > 0 && $debug_info['student_exists']) {
        try {
            $interaction = new stdClass();
            $interaction->userid = $studentId;
            $interaction->teacherid = $USER->id;
            $interaction->problem_type = 'debug_test';
            $interaction->problem_text = 'Debug test problem';
            $interaction->status = 'pending';
            $interaction->timecreated = time();
            $interaction->timemodified = time();
            
            $debug_info['insert_data'] = $interaction;
            
            $interaction_id = $DB->insert_record('ktm_teaching_interactions', $interaction);
            
            if ($interaction_id) {
                $debug_info['insert_result'] = 'success';
                $debug_info['interaction_id'] = $interaction_id;
            } else {
                $debug_info['insert_result'] = 'failed - no ID returned';
            }
            
        } catch (Exception $e) {
            $debug_info['insert_error'] = $e->getMessage();
            $debug_info['insert_error_type'] = get_class($e);
        }
    }
    
    echo json_encode(array(
        'success' => true,
        'debug_info' => $debug_info
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => $debug_info
    ));
}
?>