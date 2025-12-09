<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
require_login();
$studentid=$_GET["id"]; 
$cid = $_GET["cid"]; 
$access = $_GET["access"];
if($studentid==NULL)$studentid=$USER->id;
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
if($USER->id==NULL)header('Location: https://mathking.kr/moodle/my/');

$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

if($USER->id!=$studentid && $role==='student')
	{
	echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
	exit;
	}
if($USER->id==$studentid)include("../message.php");
$userdata=$DB->get_record_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND  fieldid='111' ORDER BY id DESC LIMIT 1 "); 

$userdata2=$DB->get_records_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND (fieldid='107' OR fieldid='88' OR fieldid='89' OR fieldid='82' OR fieldid='90' OR fieldid='64') "); 
$thisuser = json_decode(json_encode($userdata2), True);
unset($value);
$instruction='';
foreach($thisuser as $value)
	{
	if($value['fieldid']==107)$usersex=$value['data'];
	if($value['fieldid']==88)$institute=$value['data'];
	if($value['fieldid']==89)$birthyear=$value['data'];
	if($value['fieldid']==82)$AutopilotMode=$value['data'];
	if($value['fieldid']==90)$usrdata=$value['data'];
	if($value['fieldid']==64)$tsymbol=$value['data'];			
	} 
$timecreated=time(); 
$mentorid=$userdata->data;
$username= $DB->get_record_sql("SELECT id,hideinput,lastname, firstname,timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1 ");
$hideinput=$username->hideinput;
$symbol=substr($username->firstname,0, 3); 
$studentname=$username->firstname.$username->lastname;
if($access==='my' && $role!=='student')header('Location: https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$USER->id.'&tb=7');

// Set current page status for today.php
if(strpos($url, 'today.php')!= false)
	{
	$nexturl='https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid;
	$currentpage4='active';
	}

$halfdayago=time()-43200;
$aweekago=time()-604800;
$reducetime=0;
 
$tabtitle=$username->lastname;
 
$mbtilog= $DB->get_record_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='present' ORDER BY id DESC LIMIT 1"); 

// Basic styling for today page
echo '
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f8f9fa;
}

.today-container {
    max-width: 1200px;
    margin: 0 auto;
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-header {
    text-align: center;
    padding: 15px 0;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
}

.page-header h1 {
    color: #495057;
    margin: 0;
    font-size: 24px;
}

.user-info {
    text-align: right;
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 10px;
}

img {
    user-drag: none;
    user-select: none;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

a {
    user-drag: none;
    user-select: none;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Tooltip CSS for today42.php content images */
.tooltip3 {
    position: relative;
    display: inline;
    border-bottom: 0px solid black;
    font-size: 14px;
}

.tooltip3 .tooltiptext3 {
    visibility: hidden;
    width: 40%;
    background-color: #ffffff;
    color: #e1e2e6;
    text-align: center;
    font-size: 14px;
    border-radius: 10px;
    border-style: solid;
    border-color: #0aa1bf;
    padding: 20px 1;
    /* Position the tooltip */
    top: 50;
    left: 5%;
    position: fixed;
    z-index: 1000; /* Increased from 1 to prevent overlap with other elements */
}

.tooltip3 img {
    max-width: 600px;
    max-height: 1200px;
}

.tooltip3:hover .tooltiptext3 {
    visibility: visible;
}
</style>
';

// Simple page header without complex navigation
echo '
<div class="today-container">
    <div class="user-info">
        학생: '.$studentname.' | 접속시간: '.date("Y-m-d H:i").'
    </div>
    <div class="page-header">
        <h1>📊 공부 결과</h1>
    </div>
    <!-- Main content will be inserted here by today.php -->
';

// Basic JavaScript for page functionality
echo '
<script>
$(document).ready(function() {
    // Basic page initialization
    console.log("Today page loaded for student: '.$studentid.'");
});
</script>
';
?>