<?php
// save_registration.php

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['courseType'], $_POST['startDate'], $_POST['studyHours'], $_POST['studentid'], $_POST['timecreated'])) {
        http_response_code(400);
        echo "필수 데이터가 누락되었습니다.";
        exit;
    }
    
    $courseType  = trim($_POST['courseType']);
    $startDate   = (int) $_POST['startDate'];     // Unix timestamp (초)
    $studyHours  = (int) $_POST['studyHours'];
    $studentid   = (int) $_POST['studentid'];
    $timecreated = (int) $_POST['timecreated'];
    
    $record = new stdClass();
    $record->studentid   = $studentid;
    $record->course_type = $courseType;
    $record->start_date  = $startDate;
    $record->study_hours = $studyHours;
    $record->timecreated = $timecreated;
    $record->status      = 'active';  // 시작 시 active
    
    try {
        $insertedId = $DB->insert_record('abessi_homestudy', $record);
        echo "등록되었습니다. (ID: {$insertedId})";
    } catch (Exception $e) {
        http_response_code(500);
        echo "저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "잘못된 요청 방식입니다.";
}
?>
