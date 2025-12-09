<?php
// cancel_registration.php

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// POST로 전달된 studentid 값을 받아옵니다.
if (isset($_POST['studentid']) && is_numeric($_POST['studentid'])) {
    $studentid = (int) $_POST['studentid'];
} else {
    die("학생 ID가 올바르지 않습니다.");
}

// 현재 활성 상태의 등록 내역을 찾습니다.
$registration = $DB->get_record_sql("
    SELECT *
      FROM {abessi_registration}
     WHERE studentid = :studentid
       AND status = 'active'
     ORDER BY id DESC
     LIMIT 1
", ['studentid' => $studentid]);

if (!$registration) {
    echo "활성 등록 내역이 존재하지 않습니다.";
    exit;
}

// DB의 status를 inactive로 업데이트합니다.
$registration->status = 'inactive';
$updateResult = $DB->update_record('abessi_registration', $registration);

if ($updateResult) {
    echo "수강취소가 완료되었습니다.";
} else {
    echo "수강취소에 실패했습니다.";
}
?>
