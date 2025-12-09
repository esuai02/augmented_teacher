<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
 
$userid = $_POST['userid'];
//$nreview=$_POST["inputtext"];
//$nreview=(INT)$nreview;
$reviewType= $_POST["type"];
$tbegin = time()-86400*3;         // 오늘 , 최근 1주일, 최근 2주일, 최근 1개월 ,  $_POST["tbegin"]; 
$teacherid = $USER->id;
$timecreated=time();
 
$wblist=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages  WHERE userid LIKE '$userid'  AND nstep>=0 AND ( status LIKE 'complete' OR  status LIKE 'review' ) AND wboardid LIKE '%nx4HQkXq%' AND contentstype='2' AND tlaststroke > '$tbegin'   AND star >=1 ORDER BY star ASC LIMIT 1 ");
  
/*
$result= json_decode(json_encode($wblist), True);
$range = range(0, 30);
shuffle($range);
$selected = array_slice($range, 0 , $nreview);
*/
 
$wboardid=$wblist->wboardid;	
if($reviewType==='present')$DB->execute("UPDATE {abessi_messages} SET status='present', star=star+1, userto='$teacherid', tlaststroke='$timecreated', timemodified='$timecreated'  WHERE wboardid = '$wboardid' ORDER BY id DESC LIMIT 1 ");
if($reviewType==='retry')$DB->execute("UPDATE {abessi_messages} SET status='retry',  star=star+1,  userto='$teacherid', tlaststroke='$timecreated', timemodified='$timecreated' WHERE wboardid = '$wboardid'  ORDER BY id DESC LIMIT 1");
 
    
?>