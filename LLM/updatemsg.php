<?php 
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$tutorid = $_POST['tutorid'];

$contextid = $_POST['contextid'];
$currenturl = $_POST['currenturl'];
$wboardid =$_POST['wboardid'];

$timecreated=time();
 
if($eventid==1) // 새로운 메세지 확인, 화이트보드
	{
	$minutesago=$timecreated-3600;
	$exit= $DB->get_record_sql("SELECT * FROM mdl_abessi_chat WHERE userid='$userid' AND mark='1' AND t_trigger>'$minutesago' AND wboardid='$wboardid' ORDER BY id DESC LIMIT 1"); 
	$mid=1; // 화이트보드 새로고침
	if($exit->id!=NULL)echo json_encode( array("mid"=>$mid) );	
	}
elseif($eventid==2) // 새로고침 DB write
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];
	 
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,t_trigger) VALUES('refresh','$userid','$tutorid','$chatid','$wboardid','$timecreated')");	
	}  
?>

