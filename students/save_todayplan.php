<?php
// 에러 로깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
header('Content-Type: application/json; charset=utf-8');
global $DB, $USER;

// Set default time zone
date_default_timezone_set('Asia/Seoul'); // Replace with your desired time zone

// 디버깅: 받은 POST 데이터 전체 로그
error_log("=== POMODORO SAVE REQUEST ===");
error_log("POST 데이터: " . print_r($_POST, true));

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// POST로부터 studentid 받아오기 (pid는 더이상 사용하지 않음)
$studentid = isset($_POST['studentid']) ? intval($_POST['studentid']) : 0;

error_log("Student ID: " . $studentid);

if (!$studentid) {
    $error_msg = '학생 ID가 제공되지 않았습니다. (studentid: ' . $studentid . ')';
    error_log("ERROR: " . $error_msg);
    echo json_encode(array('status' => 'error', 'message' => $error_msg));
    exit;
}

// ============================================================================
// 부분 업데이트 모드 감지 (status 또는 tend만 전송된 경우)
// ============================================================================
$isPartialUpdate = false;
$partialUpdateFields = array();

// status, tend, fback 필드만 있는지 확인
foreach ($_POST as $key => $value) {
    if (preg_match('/^status\d{2}$/', $key) || preg_match('/^tend\d{2}$/', $key) || preg_match('/^fback\d+$/', $key)) {
        $isPartialUpdate = true;
        $partialUpdateFields[$key] = $value;
    }
}

// week 또는 time 필드가 하나라도 있으면 전체 업데이트 모드
foreach ($_POST as $key => $value) {
    if (preg_match('/^week\d+$/', $key) || preg_match('/^time\d+$/', $key)) {
        $isPartialUpdate = false;
        break;
    }
}

if ($isPartialUpdate) {
    error_log("부분 업데이트 모드 활성화 - status/tend/fback 필드만 업데이트");
    error_log("업데이트할 필드: " . print_r($partialUpdateFields, true));

    // 기존 레코드 조회
    $twelveHoursAgo = time() - 43200;
    $existing_plan = $DB->get_record_sql(
        "SELECT * FROM {abessi_todayplans} WHERE userid = ? AND timecreated >= ? ORDER BY timecreated DESC LIMIT 1",
        array($studentid, $twelveHoursAgo)
    );

    if (!$existing_plan) {
        echo json_encode(array('status' => 'error', 'message' => '업데이트할 기존 계획이 없습니다.'));
        exit;
    }

    // 부분 업데이트 실행
    $update_data = new stdClass();
    $update_data->id = $existing_plan->id;

    foreach ($partialUpdateFields as $fieldName => $fieldValue) {
        // tend 필드는 정수형으로 변환
        if (strpos($fieldName, 'tend') === 0) {
            $update_data->$fieldName = intval($fieldValue);
        } else {
            $update_data->$fieldName = $fieldValue;
        }
    }

    $update_data->timemodified = time();

    try {
        $result = $DB->update_record('abessi_todayplans', $update_data);
        error_log("부분 업데이트 결과: " . ($result ? "성공" : "실패"));

        if ($result) {
            echo json_encode(array('status' => 'success', 'message' => '데이터가 업데이트되었습니다.', 'mode' => 'partial'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => '데이터베이스 업데이트 실패'));
        }
    } catch (Exception $e) {
        error_log("부분 업데이트 오류: " . $e->getMessage());
        echo json_encode(array('status' => 'error', 'message' => '업데이트 오류: ' . $e->getMessage()));
    }

    exit; // 부분 업데이트 완료, 여기서 종료
}

// 주간 목표와 시간을 배열로 수집
$plans = array();
$times = array();
$urls = array(); // URL 배열 추가
$fbacks = array(); // fback 배열 추가
$statuses = array(); // status 배열 추가 (만족/매우만족/불만족)
$tends = array(); // tend 배열 추가 (완료 시점 unixtime)

for ($i = 1; $i <= 16; $i++) {
    $planField = 'week' . $i;   // 폼의 목표 입력 필드 이름
    $timeField = 'time' . $i;   // 폼의 시간 입력 필드 이름
    $urlField = 'url' . $i;     // 폼의 URL 입력 필드 이름 추가
    $fbackField = 'fback' . $i; // 폼의 fback 입력 필드 이름 추가
    $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...
    $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT); // tend01, tend02, ...

    $planValue = isset($_POST[$planField]) ? trim($_POST[$planField]) : '';
    $timeValue = isset($_POST[$timeField]) ? trim($_POST[$timeField]) : '';
    $urlValue = isset($_POST[$urlField]) ? trim($_POST[$urlField]) : ''; // URL 값 수집
    $fbackValue = isset($_POST[$fbackField]) ? trim($_POST[$fbackField]) : '결과보고'; // fback 값 수집, 기본값은 '결과보고'
    $statusValue = isset($_POST[$statusField]) ? trim($_POST[$statusField]) : ''; // status 값 수집
    $tendValue = isset($_POST[$tendField]) ? intval($_POST[$tendField]) : null; // tend 값 수집 (unixtime)

    // 디버깅: 각 필드별 데이터 로그
    if (!empty($planValue) || !empty($timeValue)) {
        error_log("필드 $i: plan='$planValue', time='$timeValue', url='$urlValue'");
    }

    // 새 형식: 선택한 사용 시간을 분 단위 정수로 저장 (5분 단위)
    if ($timeValue !== '') {
        if (ctype_digit($timeValue)) {
            $minutes = intval($timeValue);
            if ($minutes >= 0 && $minutes <= 1440) {
                $times[$i] = $minutes; // 분 저장
                error_log("분 저장 성공 $i: $minutes");
            } else {
                $times[$i] = null;
                error_log("분 범위 오류 $i: $timeValue");
            }
        } else if (preg_match('/^\d{2}:\d{2}$/', $timeValue)) {
            // 하위 호환: HH:MM로 오면 분으로 변환
            list($h, $m) = explode(':', $timeValue);
            $times[$i] = intval($h) * 60 + intval($m);
            error_log("레거시 HH:MM -> 분 변환 $i: $timeValue -> " . $times[$i]);
        } else {
            $times[$i] = null;
            error_log("시간 형식 미일치 $i: $timeValue");
        }
    } else {
        $times[$i] = null;
    }

    $plans[$i] = $planValue;
    $urls[$i] = $urlValue;    // URL 배열에 값 추가
    $fbacks[$i] = $fbackValue; // fback 배열에 값 추가
    $statuses[$i] = $statusValue; // status 배열에 값 추가
    $tends[$i] = $tendValue; // tend 배열에 값 추가

    // 디버깅: status와 tend 값 로그
    if (!empty($statusValue) || !empty($tendValue)) {
        error_log("필드 $i: status='$statusValue', tend='$tendValue'");
    }
}

// 수집된 데이터 로그
error_log("최종 수집된 계획: " . print_r($plans, true));
error_log("최종 수집된 시간: " . print_r($times, true));

// Check for existing plan within last 12 hours
// 최근 12시간 동안 저장한 내용이 없으면 새롭게 저장
try {
    $twelveHoursAgo = time() - 43200; // 12시간 전 (60 * 60 * 12 = 43200)

    // 1. userid로 가장 최근 레코드 찾기 (progressid 필드 제거됨)
    // Moodle DB API는 자동으로 prefix를 추가하므로 'abessi_todayplans'만 사용
    $existing_plan = $DB->get_record_sql(
        "SELECT * FROM {abessi_todayplans} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
        array($studentid)
    );

    if ($existing_plan) {
        // 2. timecreated가 12시간 이내인지 확인
        if ($existing_plan->timecreated && $existing_plan->timecreated >= $twelveHoursAgo) {
            // 12시간 이내면 업데이트
            error_log("기존 계획 발견 (ID: " . $existing_plan->id . ", 생성시간: " . date('Y-m-d H:i:s', $existing_plan->timecreated) . ") - 업데이트 모드");
        } else {
            // 12시간 이상 지났으면 새로 생성
            error_log("기존 계획이 12시간 이상 지남 (생성시간: " . date('Y-m-d H:i:s', $existing_plan->timecreated) . ") - 신규 생성 모드");
            $existing_plan = null;
        }
    } else {
        error_log("기존 계획 없음 - 신규 생성 모드");
    }
} catch (Exception $e) {
    error_log("기존 계획 검색 오류: " . $e->getMessage());
    echo json_encode(array('status' => 'error', 'message' => '데이터베이스 검색 오류: ' . $e->getMessage()));
    exit;
}

$timecreated = time();

if ($existing_plan) {
    // Prepare data for update
    $update_data = new stdClass();
    $update_data->id = $existing_plan->id;

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;  // 데이터베이스의 필드 이름
        $dueField = 'due' . $i;    // 데이터베이스의 필드 이름 (분 단위 저장)
        $urlDBField = 'url' . $i;  // 데이터베이스의 URL 필드 이름 추가
        $fbackDBField = 'fback' . $i; // 데이터베이스의 fback 필드 이름 추가
        $statusDBField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...
        $tendDBField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT); // tend01, tend02, ...

        $update_data->$planField = isset($plans[$i]) ? $plans[$i] : '';
        $update_data->$dueField = isset($times[$i]) ? $times[$i] : null; // 분 값 그대로 저장
        $update_data->$urlDBField = isset($urls[$i]) ? $urls[$i] : '';   // URL 값 추가
        $update_data->$fbackDBField = isset($fbacks[$i]) ? $fbacks[$i] : '결과보고'; // fback 값 추가
        $update_data->$statusDBField = isset($statuses[$i]) ? $statuses[$i] : ''; // status 값 추가
        $update_data->$tendDBField = isset($tends[$i]) ? $tends[$i] : null; // tend 값 추가 (unixtime)
    }

    $update_data->timemodified = $timecreated;
    // tbegin은 기존 값 유지 (업데이트 시 변경하지 않음)

    error_log("업데이트 데이터: " . print_r($update_data, true));

    // Update record
    try {
        $result = $DB->update_record('abessi_todayplans', $update_data);
        error_log("업데이트 결과: " . ($result ? "성공" : "실패"));

        if ($result) {
            echo json_encode(array('status' => 'success', 'message' => '계획이 업데이트되었습니다.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => '데이터베이스 업데이트 실패'));
        }
    } catch (Exception $e) {
        error_log("업데이트 오류: " . $e->getMessage());
        echo json_encode(array('status' => 'error', 'message' => '업데이트 오류: ' . $e->getMessage()));
    }
} else {
    // Prepare data for insertion (progressid 필드 제거됨)
    $insert_data = new stdClass();
    $insert_data->userid = $studentid;

    for ($i = 1; $i <= 16; $i++) {
        $planField = 'plan' . $i;  // 데이터베이스의 필드 이름
        $dueField = 'due' . $i;    // 데이터베이스의 필드 이름 (분 단위 저장)
        $urlDBField = 'url' . $i;  // 데이터베이스의 URL 필드 이름 추가
        $fbackDBField = 'fback' . $i; // 데이터베이스의 fback 필드 이름 추가
        $statusDBField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...
        $tendDBField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT); // tend01, tend02, ...

        $insert_data->$planField = isset($plans[$i]) ? $plans[$i] : '';
        $insert_data->$dueField = isset($times[$i]) ? $times[$i] : null; // 분 값 그대로 저장
        $insert_data->$urlDBField = isset($urls[$i]) ? $urls[$i] : '';   // URL 값 추가
        $insert_data->$fbackDBField = isset($fbacks[$i]) ? $fbacks[$i] : '결과보고'; // fback 값 추가
        $insert_data->$statusDBField = isset($statuses[$i]) ? $statuses[$i] : ''; // status 값 추가
        $insert_data->$tendDBField = isset($tends[$i]) ? $tends[$i] : null; // tend 값 추가 (unixtime)
    }

    $insert_data->tbegin = $timecreated; // 입력 시작 시점의 unixtime
    $insert_data->timecreated = $timecreated;
    $insert_data->timemodified = $timecreated;

    error_log("tbegin 설정: " . date('Y-m-d H:i:s', $timecreated));

    error_log("삽입 데이터: " . print_r($insert_data, true));

    // Insert record
    try {
        $result = $DB->insert_record('abessi_todayplans', $insert_data);
        error_log("삽입 결과: " . ($result ? "성공 (ID: $result)" : "실패"));

        if ($result) {
            echo json_encode(array('status' => 'success', 'message' => '계획이 저장되었습니다.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => '데이터베이스 삽입 실패'));
        }
    } catch (Exception $e) {
        error_log("삽입 오류: " . $e->getMessage());
        echo json_encode(array('status' => 'error', 'message' => '삽입 오류: ' . $e->getMessage()));
    }
}

error_log("=== SAVE REQUEST COMPLETED ===");
?>
