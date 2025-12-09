<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$inputvalue = $_POST['inputvalue'];
$type =$_POST['type']; 
$type =(INT)$type ;
$tbegin=time()-604800*4;
//$user= $DB->get_record_sql("SELECT * FROM mdl_user WHERE  username LIKE '$inputvalue' OR id LIKE '$inputvalue' ORDER BY id DESC LIMIT 1");
$user= $DB->get_record_sql("SELECT * FROM mdl_user WHERE  username LIKE '$inputvalue' OR id LIKE '$inputvalue' OR lastname LIKE '$inputvalue' AND lastaccess > '$tbegin'  ORDER BY id DESC LIMIT 1");

$userid=$user->id;

$timecreated=time();

if($type==1)$DB->execute("INSERT INTO {abessi_missionlog} (event,eventid,userid,text,timecreated ) VALUES('활동 설계','33','$userid','1','$timecreated')");  
if($type==2)$DB->execute("INSERT INTO {abessi_missionlog} (event,eventid,userid,text,timecreated ) VALUES('일정 변경','33','$userid','2','$timecreated')"); 
//"type":Type,
echo json_encode(array("userid"=>$userid,"mid"=>"1") );    

?>

