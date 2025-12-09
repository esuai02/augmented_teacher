<?php
// update_userpersona.php

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// PHP 에러 디버그 모드 (개발 환경에서만 사용)
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;

// 이미 출력된 내용이 있다면 버퍼를 비웁니다.
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

// POST 방식 체크
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 방식의 요청이 필요합니다.']);
    exit;
}

// userid, prsnid, strength는 항상 전달되어야 함
$userid = filter_var($_POST['userid'], FILTER_VALIDATE_INT);
if ($userid === false) {
    echo json_encode(['success' => false, 'message' => 'userid는 정수여야 합니다.']);
    exit;
}

$prsnid = filter_var($_POST['prsnid'], FILTER_VALIDATE_INT);
if ($prsnid === false) {
    echo json_encode(['success' => false, 'message' => 'prsnid는 정수여야 합니다.']);
    exit;
}

if (!isset($_POST['strength']) || !is_numeric($_POST['strength'])) {
    echo json_encode(['success' => false, 'message' => 'strength는 숫자여야 합니다.']);
    exit;
}
$strength = floatval($_POST['strength']);
if ($strength < 0 || $strength > 10) {
    echo json_encode(['success' => false, 'message' => 'strength는 0에서 10 사이의 값이어야 합니다.']);
    exit;
}

$time = time();
$type = isset($_POST['type']) ? $_POST['type'] : 'defaultcontents';

// 분기: status와 npersona가 함께 전달되었는지 확인 (카드 토글 업데이트)
if (isset($_POST['status']) && isset($_POST['npersona'])) {
    $statusInt = (int)$_POST['status'];
    // 0: 부정, 1: 긍정, 2: enepoem
    if (!in_array($statusInt, [0, 1, 2], true)) {
        echo json_encode(['success' => false, 'message' => 'status는 0, 1 또는 2여야 합니다.']);
        exit;
    }
    $npersona = filter_var($_POST['npersona'], FILTER_VALIDATE_INT);
    if ($npersona === false) {
        echo json_encode(['success' => false, 'message' => 'npersona는 정수여야 합니다.']);
        exit;
    }
    
    // 해당 레코드가 존재하는지 확인
    $exist = $DB->get_record_sql(
        "SELECT * FROM mdl_prsn_usermap 
         WHERE userid = :userid AND type = :type AND prsnid = :prsnid AND npersona = :npersona 
         ORDER BY id DESC LIMIT 1",
        [
            'userid'   => $userid,
            'type'     => $type,
            'prsnid'   => $prsnid,
            'npersona' => $npersona
        ]
    );
    
    if ($exist) {
        // 기존 레코드 업데이트
        $record = new stdClass();
        $record->id = $exist->id;
        $record->status = $statusInt;
        $record->strength = $strength;
        $record->timemodified = $time;
        $result = $DB->update_record('prsn_usermap', $record);
        if ($result) {
            echo json_encode(['success' => true, 'message' => '업데이트 성공']);
        } else {
            echo json_encode(['success' => false, 'message' => '업데이트 실패']);
        }
    } else {
        // 신규 레코드 삽입
        $newRecord = new stdClass();
        $newRecord->userid       = $userid;
        $newRecord->type         = $type;
        $newRecord->prsnid       = $prsnid;
        $newRecord->npersona     = $npersona;
        $newRecord->status       = $statusInt;
        $newRecord->strength     = $strength;
        $newRecord->timemodified = $time;
        $newRecord->timecreated  = $time;
        $result = $DB->insert_record('prsn_usermap', $newRecord);
        if ($result) {
            echo json_encode(['success' => true, 'message' => '삽입 성공']);
        } else {
            echo json_encode(['success' => false, 'message' => '삽입 실패']);
        }
    }
    
} else {
    // 슬라이더 업데이트: strength만 업데이트 (status와 npersona 미전달)
    $exist = $DB->get_record_sql(
        "SELECT * FROM mdl_prsn_usermap 
         WHERE userid = :userid 
           AND type = :type 
           AND prsnid = :prsnid
           AND status = 2
         ORDER BY id DESC LIMIT 1",
        [
            'userid' => $userid,
            'type'   => $type,
            'prsnid' => $prsnid
        ]
    );
    
    if ($exist) {
        $record = new stdClass();
        $record->id = $exist->id;
        $record->strength = $strength;
        $record->timemodified = $time;
        $result = $DB->update_record('prsn_usermap', $record);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'strength 업데이트 성공']);
        } else {
            echo json_encode(['success' => false, 'message' => 'strength 업데이트 실패']);
        }
    } else {
        // 레코드가 없으면 strength 업데이트가 불가능함 (npersona 등 추가 정보 필요)
        echo json_encode(['success' => false, 'message' => '레코드가 존재하지 않아 strength 업데이트 불가 (npersona 필요)']);
    }
}
?>
