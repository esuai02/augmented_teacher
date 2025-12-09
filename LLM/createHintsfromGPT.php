<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
  
$eventid= $_POST['eventid'];
$contentstype= $_POST['contentstype'];
$contentsid= $_POST['contentsid']; 
$timecreated=time();   
 
// 개인화된 피드백 

if($eventid==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
    $maintext=$cnttext->maintext;

    $prompt='"'.$maintext.'"를 이해하도록 다음과 같이 지시해줘. 설명없이 실행 위주의 활동을 진행하는 진행자야. 1단계, 2단계와 같은 방식으로 이름을 붙이고 각각의 단계에 대한 제목을 붙인 다음 상세한 지시를 내려줘. 배경지식이 없는 학생도 따라하다가 배우게 되도록 하는 것이 목적이야. 필요하다면 수학개념이나 문제를 다이어그램이나 그래프를 아주 작은 단위로 실행하도록 상세한 지시사항을 제시해줘.  설명 후에는 반드시 설명한 내용을 빠짐없이 그림에 표시했는지 꼼꼼하게 확인체크해줘 ! 사용자에게 당신이 묘사하는 것을 연습장에 직접 그리면서 따라오도록 지시를 해줘. 친근한 친구말투, 이모티콘 충분히 사용해줘.
    <hr> 필수로 입력.';
    $title='절차기억 만들기';
    } 
elseif($eventid==2)
    {
    $qstn=$DB->get_record_sql("SELECT * FROM mdl_question WHERE id ='$contentsid' ");
    $qstntext=$qstn->mathexpression;  $soltext=$qstntext->ans1; 

    $prompt=$qstntext.' | 해설:'.$soltext.' 에 대한 단계별 지시사항 만들어줘.  ';
    $title='절차기억 만들기';
    } 
  
$command2 = "python3.10 sendgptinput.py " . escapeshellarg($prompt);
$output2 = shell_exec($command2);
 
// Decode the JSON output
$result2 = json_decode($output2, true);
$gptresult = $result2['result'];

$record = new stdClass();
$record->userid = $USER->id;
$record->contentstype = $contentstype;
$record->contentsid = $contentsid;
//$record->mathexpression = $convertedinfo;
$record->gptresult = $gptresult;
$record->timecreated = $timecreated;
$DB->insert_record('abessi_solutionlog', $record);
    
$solutionlog=$DB->get_record_sql("SELECT id FROM mdl_abessi_solutionlog WHERE contentstype LIKE '$contentstype' AND contentsid='$contentsid' ORDER BY id DESC LIMIT 1");
$logid=$solutionlog->id;
echo json_encode( array("logid" =>$logid,"title" =>$title,) );
//echo json_encode( array("outputtext" =>$gptresult) );
//$conn->close();
?>