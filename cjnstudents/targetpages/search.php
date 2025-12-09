<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$inputvalue1 = $_POST['inputvalue1'];
$inputvalue2 = $_POST['inputvalue2'];
$inputvalue3 = $_POST['inputvalue3'];
$courseid = $_POST['courseid'];
 
$courseid  =(INT)$courseid;

$userid=$USER->id; 
$timecreated=time();
//if($courseid==='NULL')$courseid=NULL;

$DB->execute("INSERT INTO {abessi_search} (userid,text1,text2,text3,courseid,timecreated ) VALUES('$userid','$inputvalue1','$inputvalue2','$inputvalue3','$courseid','$timecreated')");  
//echo json_encode(array("userid"=>$userid,"mid"=>"1") );    

?>

