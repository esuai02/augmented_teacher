<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$checkimsi = $_POST['checkimsi'];
$wboardid = $_POST['wboardid'];
$gid = $_POST['gid'];
$contentsid = $_POST['contentsid'];
$contentstype = $_POST['contentstype'];
$inputtext = $_POST['inputtext']; 
$timecreated=time();  

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
if($eventid==1) // 직접 클릭하여 추가
	{
    $exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_comments WHERE  wboardid='$wboardid' AND generate_id='$thisgid' ORDER BY id DESC LIMIT 1    ");
    
    if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_comments} (userid,wboardid,wboardid0,generate_id,text,rate,nvote,timemodified,timecreated ) VALUES('$userid','$wboardid','$wboardid0','$thisgid','$inputtext','0','0','$timecreated','$timecreated')");
    else  $DB->execute("UPDATE {abessi_comments} SET  text='$inputtext',timemodified='$timecreated' WHERE  wboardid='$wboardid' AND generate_id='$thisgid' ORDER BY id DESC LIMIT 1  ");	
       
	}
  
	 
?>

