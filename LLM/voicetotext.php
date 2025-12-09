<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$timecreated=time(); 

$wboardid = $_POST['wboardid'];
$contentstype= $_POST['contentstype'];
$contentsid= $_POST['contentsid'];
$studentid = $_POST['studentid'];
$voicefileurl = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/recorder/'.$wboardid.'/'.$wboardid.'.wav';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
$showthis='output3 :'.$wboardid; include("../showthis.php");
$DB->execute("UPDATE {abessi_messages} SET boardtype='thinkaloud', timecreated='$timecreated'  WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1");

$command = "python3.10 voicetotext.py " . escapeshellarg($voicefileurl) . " " . escapeshellarg($USER->id);
exec($command, $output, $return_var);
if ($return_var == 0) {
    $voicestring = implode(", ", $output); // 쉼표와 공백으로 구분
    $voicestring = str_replace(['[', ']'], '', $voicestring);

   // $voicestring=implode("\n", $output);
} else {
    $voicestring ='결과'.implode(", ", $output); // 쉼표와 공백으로 구분
    $voicestring  .= "Error in python script execution!";
}
 
/*
$voicefileurl_escaped = escapeshellarg($voicefileurl);
$userid_escaped = escapeshellarg($USER->id);
$command = "python3.10 voicetotext.py $voicefileurl_escaped $userid_escaped > /dev/null 2>&1 &";
$output = shell_exec($command);
*/
// 여기에서는 실행의 완료 상태나 출력을 확인할 수 없으므로, 필요한 경우 추가 로직을 구현해야 합니다.
 
$showthis='output3 :'.$voicestring; include("../showthis.php");
// 먼저 업데이트하려는 레코드를 찾습니다.
 


$record = new stdClass();
$record->userid = $studentid;
$record->type = 'voice';
$record->wboardid = $wboardid;
$record->mathexpression = $voicestring;
$record->timecreated = $timecreated;
$DB->insert_record('abessi_solutionlog', $record);
echo json_encode( array("wbid" =>$wboardid) );
?>