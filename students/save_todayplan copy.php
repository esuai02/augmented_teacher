<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

// Set default time zone
date_default_timezone_set('Asia/Seoul'); // 필요한 시간대로 변경하세요

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

// 주간 목표와 시간을 배열로 수집
$plans = array();
$times = array();

for ($i = 1; $i <= 16; $i++) {
    $planField = 'week' . $i;
    $timeField = 'due' . $i;

    $planValue = isset($_POST[$planField]) ? trim($_POST[$planField]) : '';
    $timeValue = isset($_POST[$timeField]) ? trim($_POST[$timeField]) : '';

    // 시간 값을 MySQL DATETIME 형식으로 변환
    if (!empty($timeValue)) {
        // '오전 HH:MM' 또는 '오후 HH:MM' 형식 처리
        // 공백 제거
        $timeValue = str_replace(' ', '', $timeValue);

        // 정규식을 사용하여 시간 추출
        if (preg_match('/^(오전|오후)(\d{1,2}):(\d{2})$/', $timeValue, $matches)) {
            $ampm = $matches[1];
            $hour = intval($matches[2]);
            $minute = intval($matches[3]);

            // 24시간 형식으로 변환
            if ($ampm == '오전') {
                if ($hour == 12) {
                    $hour = 0;
                }
            } elseif ($ampm == '오후') {
                if ($hour != 12) {
                    $hour += 12;
                }
            }

            // 시간과 분을 두 자리 숫자로 패딩
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $minuteStr = str_pad($minute, 2, '0', STR_PAD_LEFT);

            $dateString = date('Y-m-d'); // 오늘 날짜 사용
            $dateTimeString = $dateString . ' ' . $hourStr . ':' . $minuteStr . ':00'; // DATETIME 문자열 생성

            // DATETIME 문자열 유효성 검사
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString);

            if ($dateTime !== false) {
                $times[$i] = $dateTime->format('Y-m-d H:i:s');
            } else {
                $times[$i] = null;
                // 파싱 오류 처리 가능
            }

        } else {
            // 유효하지 않은 시간 형식 처리 가능
            $times[$i] = null;
        }
    } else {
        $times[$i] = null;
    }

    $plans[$i] = $planValue;
}

// 기존 계획이 있는지 확인
$existing_plan = $DB->get_record('abessi_todayplans', array('userid' => $studentid, 'progressid' => $pid));

$timecreated = time();

if ($existing_plan) {
    // 업데이트할 데이터 준비
    $update_data = new stdClass();
    $update_data->id = $existing_plan->id;

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $timeField = 'due' . $i;

        $update_data->$planField = $plans[$i];
        $update_data->$timeField = $times[$i];
    }

    $update_data->timemodified = $timecreated;

    // 레코드 업데이트
    $DB->update_record('abessi_todayplans', $update_data);

    echo json_encode(array('status' => 'success', 'message' => '계획이 저장 되었습니다.'));
} else {
    // 삽입할 데이터 준비
    $insert_data = new stdClass();
    $insert_data->userid = $studentid;
    $insert_data->progressid = $pid;

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;
        $timeField = 'due' . $i;

        $insert_data->$planField = $plans[$i];
        $insert_data->$timeField = $times[$i];
    }

    $insert_data->timecreated = $timecreated;
    $insert_data->timemodified = $timecreated;

    // 레코드 삽입
    $DB->insert_record('abessi_todayplans', $insert_data);

    echo json_encode(array('status' => 'success', 'message' => '계획이 업데이트 되었습니다.'));
}
?>
