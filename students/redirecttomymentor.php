<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid = $_GET["studentid"];
if($studentid==NULL)$studentid=$USER->id; 
$userdata=$DB->get_record_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND  fieldid='111' ORDER BY id DESC LIMIT 1 "); 
$mentorid=$userdata->data;

header('Location:https://chat.openai.com/g/'.$mentorid.' '); 
?>