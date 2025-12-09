<?php
// savepersonas.php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

header('Content-Type: application/json; charset=utf-8');

$eventid       = $_POST['eventid']       ?? '';
$wboardid      = $_POST['wboardid']      ?? '';
$contentstype  = $_POST['contentstype']  ?? 0;
$contentsid    = $_POST['contentsid']    ?? 0;
$persona_pairs = $_POST['persona_pairs'] ?? '[]';

try {
  $pairs = json_decode($persona_pairs, true);
  if(!is_array($pairs)) {
    throw new Exception("JSON 파싱 실패");
  }

  $countInserted = 0;
  $timenow = time();

  foreach($pairs as $p) {
    // DB Insert용 객체
    $record = new stdClass();
    $record->type         = 'contents'; // 필요시 다른 값 사용
    $record->wboardid     = $wboardid;
    $record->contentstype = $contentstype;
    $record->contentsid   = $contentsid;

    // npersona (1~6)
    $record->npersona     = $p['nindex'] ?? 0;
    // icon 칼럼에 부정 페르소나 아이콘 저장
    $record->icon         = $p['neg_icon'] ?? '';

    // 부정 페르소나
    $record->negative_prsnname     = $p['neg_name'] ?? '';
    $record->negative_persona = $p['neg_desc'] ?? '';

    // 긍정 페르소나
    $record->positive_prsnname     = $p['pos_name'] ?? '';
    $record->positive_persona = $p['pos_desc'] ?? '';
    // 시
    $record->enepoem          = $p['pos_enepoem'] ?? '';

    // 시간
    $record->timecreated  = $timenow;
    $record->timemodified = $timenow;

    $DB->insert_record('prsn_contents', $record);
    $countInserted++;
  }

  $res = [
    "status" => "success",
    "msg"    => "{$countInserted}건 저장 완료"
  ];
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
  exit;

} catch(Exception $ex) {
  $err = [
    "status" => "error",
    "msg"    => $ex->getMessage()
  ];
  echo json_encode($err, JSON_UNESCAPED_UNICODE);
  exit;
}
