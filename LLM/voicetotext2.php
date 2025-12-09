<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$timecreated=time(); 

#$voicefileurl="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/recoder/cjnNotepageid1977jnrsorksqcrark/2023-08-03T23%3A31%3A49.091Z.wav";

$voicefileurl = $_POST['voicefileurl'];
$wboardid = $_POST['wboardid'];
$contentstype= $_POST['contentstype'];
$contentsid= $_POST['contentsid'];
$studentid = $_POST['studentid'];
  
$command = "python3.10 voicetotext.py " . escapeshellarg($voicefileurl);
exec($command, $output, $return_var);

if ($return_var == 0) {
    $voicestring=implode("\n", $output);
    echo $voicestring;
} else {
    echo "Error in python script execution!";
}
if($contentstype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
    $cnttext1=$cnttext->maintext;  
    $cnttext2=$cnttext->reflections;  
    $prompt='본문내용 : '.$cnttext1.' \n 을 학생이 설명한 음성파일의 내용은 다음과 같습니다. \n '.$voicestring.' \n 본문 내용을 토대로 학생의 설명을 평가해 주세요. 논리전개의 구체성과 완결성에 대해 간략한 평가를 해주세요';
    }
elseif($contentstype==2)
    {
    $qstn=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id ='$contentsid' ");
    $cnttext1=$qstn->mathexpression;  $soltext=$qstntext->ans1;
    $cnttext1=$qstn->description;
    $prompt='문제내용 : '.$cnttext1.' \n 을 학생이 설명한 음성파일의 내용은 다음과 같습니다. \n '.$voicestring.' \n 학생의 설명내용을 요약하고 평가해주세요';
    }

$command2 = "python3.10 sendgptinput.py " . escapeshellarg($prompt);
$output2 = shell_exec($command2);
  
$result2 = json_decode($output2, true);
$gptresult = $convertedinfo.'<hr>'.$result2['result'];
echo $gptresult;
if($wboardid!=NULL) 
    {
    $record = new stdClass();
    $record->userid = $studentid;
    $record->wboardid = $wboardid;
    $record->mathexpression = $voicestring;
    $record->gptresult = $gptresult;
    $record->timecreated = $timecreated;
    $DB->insert_record('abessi_solutionlog', $record);
    }
$solutionlog=$DB->get_record_sql("SELECT id FROM mdl_abessi_solutionlog WHERE wboardid='$wboardid' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
$logid=$solutionlog->id;
echo json_encode( array("logid" =>$logid,"title" =>$title,) );
 
?>