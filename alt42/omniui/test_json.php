<?php
// 간단한 JSON 응답 테스트
header('Content-Type: application/json');

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 교사 권한 확인
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

if (!$isTeacher) {
    echo json_encode(array('error' => '권한 없음'));
    exit;
}

// 간단한 학생 목록 가져오기
try {
    $sql = "SELECT u.id, u.firstname, u.lastname
            FROM mdl_user u
            INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
            WHERE uid.fieldid = 22 AND uid.data = 'student'
            AND u.deleted = 0 AND u.suspended = 0
            ORDER BY u.firstname ASC
            LIMIT 5";
    
    $students = $DB->get_records_sql($sql);
    
    $result = array();
    if ($students) {
        foreach ($students as $student) {
            $result[] = array(
                'id' => $student->id,
                'name' => $student->firstname . ' ' . $student->lastname
            );
        }
    }
    
    echo json_encode(array(
        'status' => 'success',
        'count' => count($result),
        'students' => $result
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>ㄺㄷㄱㅎㄱㄱㅎㄳ4ㄷㄺㅎㄱㅎㅁㅁ