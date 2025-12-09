<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$feedbacktype = $_POST['feedbacktype'];
$text = $_POST['text'];

$userid = $_POST['userid'];
$tutorid = $_POST['tutorid'];
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$eventid = $_POST['eventid'];
$contextid = $_POST['contextid'];
$currenturl = $_POST['currenturl'];
$inputtext = $_POST['inputtext'];
$timecreated=time();


if($eventid==1) // 페이지별 요청사항 전달 .. 실시간 팝업
	{
	$feedbacktype='개선요청';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	$recenttime=$timecreated-180;
	$probability=$DB->get_record_sql("SELECT * FROM  mdl_abessi_forecast WHERE userid='$userid' AND timemodified > '$recenttime'  ORDER BY id DESC LIMIT 1 ");
	 
	if($probability->id==NULL)exit();
	//if($probability->prob1==NULL)$np=1;
	if($probability->prob2==NULL)$np=2;
	elseif($probability->prob3==NULL)$np=3;
	elseif($probability->prob4==NULL)$np=4;
	elseif($probability->prob5==NULL)$np=5;
	elseif($probability->prob6==NULL)$np=6;
	elseif($probability->prob7==NULL)$np=7;
	elseif($probability->prob8==NULL)$np=8;
	elseif($probability->prob9==NULL)$np=9;
	elseif($probability->prob10==NULL)$np=10;
	else $np=11;

 
	$txtstr='text'.($np-1);
	$DB->execute("UPDATE {abessi_forecast} SET  ".$txtstr."='$inputtext' WHERE  userid='$userid'   ORDER BY id DESC LIMIT 1 ");


	}
if($eventid==11) // 페이지별 요청사항 전달 .. 퀴즈 피드백
	{
	$feedbacktype='개선요청';
	$attemptid = $_POST['attemptid'];
	/*
	$quiztitle = $_POST['quiztitle'];
	if(strpos($quiztitle, '단원-주제')!= false)$quizgoal='최소 시도로 연속 3회 100점을 맞는 것이 목적입니다';
	elseif(strpos($quiztitle, '개념도약')!= false)$quizgoal='개념을 정확히 익히고 연습을 하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '내신')!= false)$quizgoal='최소 시도로 커트라인을 통과 후 레벨업 하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '인지촉진')!= false)$quizgoal='최소 시도로 연속 3회 100점을 맞는 것이 목적입니다.';
	elseif(strpos($quiztitle, '보강학습')!= false)$quizgoal='문제지를 풀 듯이 한 문제씩 정확히 풀고 이해하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '인증시험')!= false)$quizgoal='최소 시도로 커트라인을 통과 후 다음 단원으로 넘어가는 것이 목적입니다.';
	elseif(strpos($quiztitle, '모의고사')!= false)$quizgoal='데드라인까지 목표점수를 통과하는 것이 목표입니다.';
	*/
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	$DB->execute("UPDATE {quiz_attempts} SET comment='$inputtext'  WHERE id='$attemptid' ");  
	}
if($eventid==2) //시간표 편집
	{
	$feedbacktype='변경사항';
	$inputtext='<b>최근에 시간표가 수정되었습니다</b>';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	}
if($eventid==3) //미션
	{
	$feedbacktype='변경사항';
	$inputtext='최근에 새로운 수업을 시작하였습니다';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	}
if($eventid==4) //미션 스케줄
	{
	$feedbacktype='변경사항';
	$inputtext='최근에 수업 회차별 데드라인을 설정하였습니다.';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	}
if($eventid==5) //목표 입력
	{
	$feedbacktype='변경사항';
	$inputtext='최근에 '.$inputtext.'를 설정하였습니다.';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	} 
?>

