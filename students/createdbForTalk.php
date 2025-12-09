<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
require_login();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
  
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
 
$time = time();
 
if($role==='teacher' )$nstar=1;
if($role==='student' )$nstar=0;

 
$date='';
$encryption_id=$boardtype.'_user'.$userid.'_date'.date("Y_m_d", time());

$sql = "INSERT INTO  createdb(encryption_id,creator,starred,lockwb,timecreated) VALUES ('$encryption_id','$userid',$nstar,0,'$time')";

if($boardtype==='today')$srcid='today_template';
elseif($boardtype==='weekly')$srcid='weekly_template';
elseif($boardtype==='period')$srcid='period_template';

/*
$sql2 = "INSERT INTO boarddb (encryption_id,authorid, shape_data, generate_id, timecreated) SELECT '$encryption_id', '$USER->id', shape_data, generate_id, timecreated FROM boarddb WHERE encryption_id = '$srcid' ";
$conn->query($sql2); 
*/
if ($conn->query($sql) === TRUE) 
	{

    	//$sql = "INSERT INTO  boarddb(encryption_id,generate_id)  VALUES ('$encryption_id','0')";
   	if ($conn->query($sql) === TRUE) 
		{
    		echo("<script>location.href='./board.php?id=$encryption_id';</script>"); 
  		}

	} 
else 
	{
    	echo "Error: " . $sql . "<br>" . $conn->error;
	}
 
$conn->close();


?>

