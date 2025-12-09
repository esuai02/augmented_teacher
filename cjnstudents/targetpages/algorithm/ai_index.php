<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

header('Location: http://mathking.kr/moodle/local/augmented_teacher/students/begintoday.html');
 
/*
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')$url = "https://";   
else $url = "http://";   
$url.= $_SERVER['HTTP_HOST'];   
$url.= $_SERVER['REQUEST_URI'];    
if(strpos($url, 'php?id')!= false)$studentid=required_param('id', PARAM_INT); 
else $studentid=$USER->id;

$timeback=time()-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
$tlastinput=$checkgoal->timecreated;

if($tlastinput<$timeback && $role==='student' && strpos($url, 'missionhome.php')!= false && strpos($url, 'edittoday.php')!= false) // 목표설정 --> 현재 적용된 대로 목표입력 화면으로 이동
	{
	header('Location: http://mathking.kr/moodle/local/augmented_teacher/students/begintoday.html');
	}

elseif() // 오답노트 ... 24시간 지난 미처리 오답노트 --> 추후 기억 연장하기로 강제 이동
	{

$timeafter=time()-86400*60; // 오답노트 점검 기간 

$wboard=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student'  AND  turn LIKE '0' AND timemodified > '$timeafter' ORDER BY id DESC ");
$waitinglist= json_decode(json_encode($wboard), True);
$tab1=NULL;$tab2=NULL;$tab3=NULL;
$count=0;
$nreturned=0;
$ncomplete=0;
$count0=0;
$count1=0;
$count2=0;
$count3=0;
$count4=0;
$count5=0;
$userperiod=$personalperiod*86400;

unset($value);
foreach($waitinglist as $value)
	{	
	$count++;
	$boardid=$value['wboardid'];
	$questionid=$value['contentsid'];
	$timemodified=date("m-d h:i A", $value['timemodified']);
 	$reviewperiod=time()-$value['timereviewed'];   
	
	}
elseif() // 미션 데드라인 미설정 --> 추후 편집화면으로 redirection
	{
	
	}
elseif() // 미션 실패 (데드라인 지난 것 중에 통과 못한 부분)
	{
	
	}
elseif() // 
	{
	
	}
elseif()
	{
	
	}	

 */
// 먼저 상단 메세지에 출력하는 방식으로 한 다음.. twine을 매개로 한 강제 redirection을 적용한다 !
/*

- 선생님 클릭으로 상황별 intervention message를 발송하고 이들에 대한 처리 여부를 데이터로 체크하여 자동으로 반응을 하도록 한다.
- 챗봇처럼 ... 얘매한 상황에서는 양쪽으로 직접 채팅창을 열어서 소통하도록 한다 !!!!!!!!!!!!!!!!!!!!
*/


/*
$userid=$studentid;
$timeafter=time()-86400*30;
$wboard=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$userid' AND turn LIKE '0' AND timemodified > '$timeafter' AND  status NOT LIKE 'complete' ");
$waitinglist= json_decode(json_encode($wboard), True);

unset($value);
foreach($waitinglist as $value)
	{	
	}
*/
?>