<?php
// reset_persona.php
// 이 스크립트는 prsn_contents 테이블에서 해당 contentstype 및 contentsid에 해당하는 레코드를 삭제합니다.
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// PHP 에러 디버깅 활성화 (개발 환경에서만)
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;

header('Content-Type: application/json; charset=utf-8');

// POST 파라미터 받기
$cnttype = $_POST['cnttype'] ?? 0;
$cntid = $_POST['cntid'] ?? 0;

// 사용자 역할 확인 (학생이면 권한 없음)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;
if ($role === 'student') {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// prsn_contents 테이블의 관련 레코드 삭제
$deleted = $DB->delete_records('prsn_contents', ['contentstype' => $cnttype, 'contentsid' => $cntid]);
if ($deleted) {
    echo json_encode(['success' => true, 'message' => '초기화 완료']);
} else {
    echo json_encode(['success' => false, 'message' => '초기화 실패']);
}
?>
