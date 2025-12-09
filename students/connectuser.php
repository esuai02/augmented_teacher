<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$inputvalue = $_POST['inputvalue'];
$user= $DB->get_record_sql("SELECT * FROM mdl_user WHERE username LIKE '$inputvalue'  ORDER BY id DESC LIMIT 1");
$studentid=$user->id;
$userdata=$DB->get_record_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND  fieldid='111' ORDER BY id DESC LIMIT 1 "); 
$mentorid=$userdata->data;
echo json_encode(array("mentorid"=>$mentorid));    
?>