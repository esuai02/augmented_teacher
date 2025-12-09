<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$studentid = $_POST['userid'] ?? $_GET['userid'] ?? $USER->id;

switch ($action) {
    case 'add':
        // 최근 강좌 추가/업데이트
        $course_name = $_POST['course_name'] ?? '';
        $course_type = $_POST['course_type'] ?? '';
        
        if (empty($course_name) || empty($course_type)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            exit;
        }
        
        $existing = $DB->get_record('user_recent_courses', 
            array('userid' => $studentid, 'course_name' => $course_name)
        );
        
        if ($existing) {
            // 업데이트
            $existing->visit_count = $existing->visit_count + 1;
            $existing->last_visited = time();
            $DB->update_record('user_recent_courses', $existing);
        } else {
            // 새로 추가
            $record = new stdClass();
            $record->userid = $studentid;
            $record->course_name = $course_name;
            $record->course_type = $course_type;
            $record->visit_count = 1;
            $record->last_visited = time();
            $record->timecreated = time();
            $DB->insert_record('user_recent_courses', $record);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'get':
        // 최근 1주일 이내 방문한 강좌 조회
        $week_ago = time() - (7 * 24 * 60 * 60);
        
        $courses = $DB->get_records_sql(
            "SELECT * FROM {user_recent_courses} 
             WHERE userid = ? AND last_visited > ? 
             ORDER BY last_visited DESC",
            array($studentid, $week_ago)
        );
        
        echo json_encode(['success' => true, 'courses' => array_values($courses)]);
        break;
        
    case 'remove':
        // 최근 강좌 제거
        $course_name = $_POST['course_name'] ?? '';
        
        if (empty($course_name)) {
            echo json_encode(['success' => false, 'error' => 'Missing course name']);
            exit;
        }
        
        $DB->delete_records('user_recent_courses', 
            array('userid' => $studentid, 'course_name' => $course_name)
        );
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>