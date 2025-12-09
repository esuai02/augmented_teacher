<?php 
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$status = $_POST['isactive'];
$eventid = $_POST['eventid'];
$wboardid =$_POST['wboardid'];
$checkimsi = $_POST['checkimsi']; 

$userid = $_POST['userid'];
$tutorid = $_POST['tutorid'];
$feedbackid = $_POST['feedbackid'];
$feedbacktype = $_POST['feedbacktype'];
$contextid = $_POST['contextid'];
$currenturl = $_POST['currenturl'];
$nstep = $_POST['nstep'];
$inputtext = $_POST['inputtext'];
$wboardid = $_POST['wboardid'];
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$contentstype0 = $_POST['contentstype0'];
$contentsid0 = $_POST['contentsid0'];
$userid0 = $_POST['userid0'];
$contentsid0 = $_POST['contentsid0'];
$contentstype0 = $_POST['contentstype0'];
$timecreated=time();
 
if($eventid==1) // 공부법 단계 선택
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,t_trigger) VALUES('chat','$userid','$tutorid','$chatid','$wboardid','$timecreated')");	
	} 
elseif($eventid==2) // 참고자료 추가 및 수정하기
	{
	$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_references WHERE contentsid='$contentsid' AND contentsid0='$contentsid0' AND contentstype='$contentstype0' AND  contentstype='$contentstype' AND nstep='$nstep' "); 
	if($exit->id==NULL)$DB->execute("INSERT INTO {abessi_references} (userid,pageurl,nstep,active,contentsid,contentsid0,contentstype,contentstype0,teacherid,timemodified,timecreated) VALUES('$userid','$currenturl','$nstep','$checkimsi','$contentsid','$contentsid0','$contentstype','$contentstype0','$tutorid','$timecreated','$timecreated')");	
	else $DB->execute("UPDATE {abessi_references} SET active='$checkimsi', timemodified='$timecreated' WHERE contentsid='$contentsid' AND contentsid0='$contentsid0' AND contentstype='$contentstype0' AND  contentstype='$contentstype'   ");
 	}  
 
elseif($eventid==200) // 채팅 요청 DB write
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,t_trigger) VALUES('chat','$userid','$tutorid','$chatid','$wboardid','$timecreated')");	
	}
elseif($eventid==201) // 새로고침 DB write
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];
	 
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,t_trigger) VALUES('refresh','$userid','$tutorid','$chatid','$wboardid','$timecreated')");	
	}
 
elseif($eventid==300) // 질의응답 text 입력
	{
	$msglast=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE userid LIKE '$userid' ORDER BY timemodified DESC LIMIT 1 ");
	$boardid =$msglast->wboardid;  // 추후 개념부분도 추가
 
	$DB->execute("UPDATE {abessi_messages} SET helptext='$inputtext', timemodified='$timecreated' WHERE wboardid='$boardid' and userid='$userid' ORDER BY timemodified DESC LIMIT 1 ");
	if($contentsid0!=NULL)
		{
		$contentsid=$contentsid0;
		$contentstype=$contentstype0;
		$userid=$userid0;
		}
	 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,step,context,url,userid,teacherid,contentsid,contentstype,timecreated ) VALUES('$feedbacktype','$inputtext','1','$contextid','$currenturl','$userid','$USER->id','$contentsid','$contentstype','$timecreated')");
	elseif($fb->step==1)$DB->execute("UPDATE {abessi_feedbacklog} SET  step=2, feedback2='$inputtext', context='$contextid',url='$currenturl',timecreated='$timecreated' WHERE userid='$userid' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");	
	elseif($fb->step==2)$DB->execute("UPDATE {abessi_feedbacklog} SET  step=3, feedback3='$inputtext', context='$contextid',url='$currenturl', timecreated='$timecreated' WHERE userid='$userid' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");	
	elseif($fb->step==3)$DB->execute("UPDATE {abessi_feedbacklog} SET  step=4, feedback4='$inputtext', context='$contextid',url='$currenturl', timecreated='$timecreated' WHERE userid='$userid' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");	
	elseif($fb->step>=4)$DB->execute("UPDATE {abessi_feedbacklog} SET  step=5, feedback5='$inputtext', context='$contextid',url='$currenturl', timecreated='$timecreated' WHERE userid='$userid' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");	 	
	} 
elseif($eventid==4) // avartar text 수정
	{
	$talkid = $_POST['talkid']; 
 	$DB->execute("UPDATE {abessi_cognitivetalk} SET text='$inputtext' WHERE id='$talkid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==5) // avartar text 삭제
	{
	$talkid = $_POST['talkid']; 
 	$DB->execute("UPDATE {abessi_cognitivetalk} SET standard=0 WHERE id='$talkid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 

?>

