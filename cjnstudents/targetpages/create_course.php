<?php 
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$userid = $_POST['userid'];
$cid =  $_POST['cid'];
$eventid=$_POST['eventid'];
$type = $_POST['type'];
$creator = $USER->id;
$moduleid = $_POST['moduleid'];
$inputid = $_POST['inputid'];
$inputtext = $_POST['inputtext'];
$timecreated=time();
if($eventid==1)
	{
	$DB->execute("INSERT INTO {abessi_exam} (userid,subject,type,moduleid,inputtext,timecreated) VALUES('$userid','$cid','$type','$moduleid','$inputtext','$timecreated')");
	}
if($eventid==100)
	{
	$DB->execute("UPDATE {abessi_exam} SET  status='1' WHERE id='$inputid' ");	 	
	}
?>

