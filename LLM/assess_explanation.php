<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

$timecreated=time(); 

$wboardid = $_POST['wboardid'];
$contentstype= $_POST['contentstype'];
$contentsid= $_POST['contentsid'];
$studentid = $_POST['studentid'];
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

$recordlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_solutionlog WHERE  wboardid LIKE '$wboardid' AND timemodified IS NULL  ORDER BY id DESC LIMIT 1 "); 
 
if($recordlog->id!=NULL)$voicestring=$recordlog->mathexpression;
else exit;
 
$showthis='output1 :'.$voicestring; include("../showthis.php");
if($contentstype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
    $cnttext1=$cnttext->maintext;  
    $cnttext2=$cnttext->reflections;  
    $prompt='본문내용 : '.$cnttext1.' \n 을 학생이 설명한 음성파일의 내용은 다음과 같습니다. \n '.$voicestring.' \n 본문 내용을 토대로 학생의 설명을 평가해 주세요. 논리전개의 구체성과 완결성에 대해 간략한 평가하여 굵은 글씨로 합격, 불합격을 판정해 주세요. 불합격인 긴 경우 추가지시 사항을 전달하고 재시도를 전달해 주세요.  음성인식 결과로 인하여 발음 등의 문제로 일부 글자들이 오류가 있을 수 있습니다. 이를 본문 내용을 토대로 유추하여 매끄럽게 재구성하여 평가해 주세요';
    }
elseif($contentstype==2)
    {
    $qstn = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");
    $cnttext1=$qstn->mathexpression;  $soltext=$qstn->ans1;
    $cnttext1=$qstn->description;
    $prompt='문제내용 : '.$cnttext1.' \n 을 학생이 설명한 음성파일의 내용은 다음과 같습니다. \n '.$voicestring.' \n 음성인식 결과로 인하여 발음 등의 문제로 일부 글자들이 오류가 있을 수 있습니다. 이를 수학문제 내용을 토대로 유추하여 매끄럽게 재구성한 다음 학생의 설명내용을 평가하고 굵은 글씨로 합격, 불합격을 판정해 주세요. 불합격인 긴 경우 추가지시 사항을 전달하고 재시도를 전달해 주세요.';
    }
$showthis='output2 :'.$prompt; include("../showthis.php");
$command2 = "python3.10 sendgpt4.py " . escapeshellarg($prompt);
$output2 = shell_exec($command2);

// $command2 = "python3.10 sendgptinput.py " . escapeshellarg($prompt) . " > /dev/null 2>&1 &";
// $output2 = shell_exec($command2);

$result2 = json_decode($output2, true);
$gptresult =$result2['result']; 


$record = new stdClass();
$record->id = $recordlog->id;
$record->gptresult = $gptresult;
$record->timemodified = $timecreated;
$DB->update_record('abessi_solutionlog', $record);  
echo json_encode( array("wbid" =>$wboardid,"cnttype" =>$contentstype,"cntid" =>$contentsid) );
?>