<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid']; 
$checkimsi = $_POST['checkimsi']; 
$timecreated=time();

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($eventid==1) //아이디 비밀번호 생성
	{
      
    $newname = $_POST['username']; 
    $newfirstname = $_POST['firstname']; 
    $newlastname = $_POST['lastname']; 
    $newpw = $_POST['password'];
    //$hashed_password=password_hash($newpw, PASSWORD_DEFAULT);
   // $hashed_password = str_replace('$2y$10$', '', $hashed_password);
    $newemail=$username.'@mathking.kr';
	$exist=$DB->get_record_sql("SELECT id FROM mdl_user where username='$newname' ORDER BY id DESC LIMIT 1 "); 
	if($exist->id==NULL)
        {
 
             
        $DB->execute("INSERT INTO {user} (username,firstname,lastname,confirmed,mnethostid,email,timecreated) VALUES('$newname','$newfirstname','$newlastname','1','1','newemail','$timecreated')");
        echo json_encode( array("result" =>"아이디가 생성되었습니다.","mode"=>"1") );
        }
	else 
        {
        echo json_encode( array("result" =>"중복된 아이디가 있습니다.") );
        }
	} 
?>

   