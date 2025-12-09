<?php  
$timeback=time()-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
$tlastinput=$checkgoal->timecreated;
$goaltext=$checkgoal->text;
$ngoal=0;
if($tlastinput>$timeback && strpos($goaltext, '통과') !== false || strpos($goaltext, '점') !== false ||strpos($goaltext, '합격') !== false || strpos($goaltext, '끝') !== false   ||strpos($goaltext, '오답') !== false ||strpos($goaltext, '유형') !== false||strpos($goaltext, '주제') !== false  ||strpos($goaltext, '단원') !== false ||strpos($goaltext, '까지') !== false|| strpos($goaltext, '0') !== false || strpos($goaltext, '5') !== false)$ngoal=1;

$tlastlog= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$studentid' AND page='studentfullengagement'  ORDER BY id DESC LIMIT 1 ");
$tfulleng=$tlastlog->timecreated;
$tindex= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$studentid' AND page='studentindex'  ORDER BY id DESC LIMIT 1 ");
$schedule= $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid'   ORDER BY id DESC LIMIT 1 ");
$editnew=$schedule->editnew;
$nn=0;

if($nn==0 && $tindex->timecreated==NULL) // 신규 사용자 인사
	{
	$nn=1;
	header('Location: http://mathking.kr/moodle/local/augmented_teacher/students/firstvisit.html');
	}
elseif($nn==0 && $editnew==1 && $role==='student' && strpos($url, 'edit')==false) // Editnew 스케줄 업데이트 push 
	{
	$nn=1;
	header('Location: http://mathking.kr/moodle/local/augmented_teacher/students/newschedule.html');
	}
elseif($nn==0  && ($ngoal==0 ||  $tlastinput<$timeback) && $role==='student'  && strpos($url, 'edit')==false
  && strpos($url, 'missionhome.php')==false && strpos($url, 'selectmission.php')==false ) // 목표설정 --> 현재 적용된 대로 목표입력 화면으로 이동  
	{
	$nn=1;
	header('Location: http://mathking.kr/moodle/local/augmented_teacher/students/begintoday.html');
	}
elseif($nn==0 && $role==='student' && strpos($url, 'edit')==false) // 오답노트 점검하기로 이동
	{
	$getperiod=$DB->get_record_sql("SELECT data AS period FROM mdl_user_info_data where userid='$studentid' AND fieldid='67' "); 
	$personalperiod=$getperiod->period*86400;
	$userperiod=time()-$personalperiod;

	$timeafter=time()-86400*60; // 오답노트 점검 기간 
 
	$wboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student'  AND  turn LIKE '0' AND timemodified > '$timeafter' 
	AND ( (timereviewed < '$userperiod' AND status LIKE 'review') OR status LIKE 'begin')  ORDER BY id DESC ");
	$reviewcount=count($wboards);
	$t_tmp=time()-43200;

	if($reviewcount >0 && strpos($url, 'fullengagement.php')==false )  // && $tfulleng < $t_tmp ) 
		{
		$nn=1;
		header('Location: https://mathking.kr/moodle/local/augmented_teacher/students/gotoreview.html');
		}
  	}



/*



// 먼저 상단 메세지에 출력하는 방식으로 한 다음.. twine을 매개로 한 강제 redirection을 적용한다 !
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