<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$inputvalue = $_POST['inputvalue'];
$type =$_POST['type']; 
$type =(INT)$type ;
$tbegin=time()-604800*4;
$users= $DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended=0 AND id LIKE '$inputvalue' OR lastname LIKE '$inputvalue' AND lastaccess > '$tbegin'  ORDER BY id DESC ");

$nuser=1;
$timecreated=time();
$result = json_decode(json_encode($users), True);
unset($value);										
foreach($result as $value)										
	{
	$usertext='userid'.$nuser;
	$$usertext=$value['id'];
	$usernametext='username'.$nuser;
	$$usernametext=$value['firstname'].$value['lastname'];
	$nuser++;
	
	}
 
if($userid1==NULL)$userid1=0;if($userid2==NULL)$userid2=0;if($userid3==NULL)$userid3=0;if($userid4==NULL)$userid4=0;if($userid5==NULL)$userid5=0;
echo json_encode(array("userid1"=>$userid1,"userid2"=>$userid2,"userid3"=>$userid3,"userid4"=>$userid4,"userid5"=>$userid5,"username1"=>$username1,"username2"=>$username2,"username3"=>$username3,"username4"=>$username4,"username5"=>$username5) );    

?>

