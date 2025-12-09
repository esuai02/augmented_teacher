<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", array($USER->id)); 
$role = isset($userrole->role) ? $userrole->role : '';

// POST로부터 studentid와 pid 받아오기
$studentid = isset($_POST['studentid']) ? intval($_POST['studentid']) : 0;
$pid = isset($_POST['pid']) ? intval($_POST['pid']) : 0;

if (!$studentid || !$pid) {
    echo json_encode(array('status' => 'error', 'message' => '학생 ID 또는 PID가 제공되지 않았습니다.'));
    exit;
}

// 주간 목표와 날짜 데이터를 배열로 수집 (plan8-16, date8-16 사용)
$plans = array();
$dates = array();

for ($i = 1; $i <= 9; $i++) {
    $planField = 'week' . $i;
    $dateField = 'date' . $i;

    $planValue = isset($_POST[$planField]) ? trim($_POST[$planField]) : '';
    $dateValue = isset($_POST[$dateField]) ? trim($_POST[$dateField]) : '';

    // 날짜 유효성 검사 및 포맷팅
    if (!empty($dateValue)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $dateValue);
        if ($dateObj && $dateObj->format('Y-m-d') === $dateValue) {
            $dates[$i + 7] = $dateValue; // week1 → date8, week2 → date9, ..., week9 → date16
        } else {
            $dates[$i + 7] = null; // 유효하지 않은 날짜
        }
    } else {
        $dates[$i + 7] = null;
    }

    $plans[$i + 7] = $planValue; // week1 → plan8, week2 → plan9, ..., week9 → plan16
}

// 기존에 해당 학생의 주간 계획이 있는지 확인 (userid와 progressid로 확인)
$existing_plan = $DB->get_record('abessi_weeklyplans', array('userid' => $studentid, 'progressid' => $pid));

$timecreated = time();

if ($existing_plan) {
    // 업데이트할 데이터 준비 (plan8-16과 date8-16만 업데이트, plan1-7은 일별목표용으로 보존)
    $update_data = new stdClass();
    $update_data->id = $existing_plan->id;

    for ($i = 8; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $dateField = 'date' . $i;

        $update_data->$planField = isset($plans[$i]) ? $plans[$i] : '';
        $update_data->$dateField = isset($dates[$i]) ? $dates[$i] : null;
    }

    $update_data->timemodified = $timecreated;

    // 레코드 업데이트
    $DB->update_record('abessi_weeklyplans', $update_data);

    echo json_encode(array('status' => 'success', 'message' => '주간 목표가 성공적으로 업데이트되었습니다.'));
} else {
    // 삽입할 데이터 준비
    $insert_data = new stdClass();
    $insert_data->userid = $studentid;
    $insert_data->progressid = $pid;

    // plan1-7, date1-7은 일별목표용으로 비워둠
    for ($i = 1; $i <= 7; $i++) {
        $planField = 'plan' . $i;
        $dateField = 'date' . $i;
        $insert_data->$planField = '';
        $insert_data->$dateField = null;
    }

    // plan8-16, date8-16은 주간목표 데이터로 설정
    for ($i = 8; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $dateField = 'date' . $i;

        $insert_data->$planField = isset($plans[$i]) ? $plans[$i] : '';
        $insert_data->$dateField = isset($dates[$i]) ? $dates[$i] : null;
    }

    $insert_data->timecreated = $timecreated;
    $insert_data->timemodified = $timecreated;

    // 레코드 삽입
    $DB->insert_record('abessi_weeklyplans', $insert_data);

    echo json_encode(array('status' => 'success', 'message' => '주간 목표가 성공적으로 저장되었습니다.'));
}
?>
