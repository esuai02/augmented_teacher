<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
$role=$userrole->role;
 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
// $encryption_id = GenerateString(15);
$encryption_id = 'OVc4lRh'.$questionid.'nx4HQkXq'.$userid;

$timecreated = time();

if($role==='student' )
{
$nstar=0;
$authlevel=$DB->get_record_sql("SELECT data AS level FROM mdl_user_info_data where userid='$userid' AND fieldid='60' "); 
if($authlevel->level>2)$nstar=1;
}
if($role==='teacher' )$nstar=3; 

//$sql = "INSERT INTO  createdb(encryption_id,creator,starred,lockwb,timecreated) VALUES ('$encryption_id','$userid',$nstar,0,'$timecreated')";
//$sql = "INSERT INTO  createdb(encryption_id,contentsid,creator,starred,lockwb,timecreated) VALUES ('$encryption_id','$questionid','$userid',$nstar,0,$timecreated)";

$sql = "INSERT INTO  createdb(encryption_id,contentstype,contentsid,creator,lockwb,timecreated) VALUES ('$encryption_id',2,'$questionid','$userid',0,$timecreated)";
$sql1 = "INSERT INTO  boarddb(encryption_id,generate_id)  VALUES ('$encryption_id','0')";
$sql2 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','1','\[color black\]','$timecreated')";

$getimg=$DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id ='$questionid' ");
$qtext=$getimg->questiontext;
 
 
 
//Create a new DOMDocument object.
$htmlDom = new DOMDocument;
 
//Load the HTML string into our DOMDocument object.
@$htmlDom->loadHTML($qtext);
 
//Extract all img elements / tags from the HTML.
$imageTags = $htmlDom->getElementsByTagName('img');
 
//Create an array to add extracted images to.
$extractedImages = array();

$nimg=0;
foreach($imageTags as $imageTag)
	{
	$nimg++;
    	$imgSrc = $imageTag->getAttribute('src');
	$imgSrc = str_replace(' ', '%20', $imgSrc); 
	if(strpos($imgSrc, 'MATRIX')!= false)break;
	}
 
 
$imageurl ='\[image '.$imgSrc.' 50 50\]';
$sql3 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','2','[color black]','$timecreated')"; 
$sql3 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','2','$imageurl','$timecreated')"; 
$conn->query($sql);
$conn->query($sql1);
$conn->query($sql2);
$conn->query($sql3);
 
 
$conn->close();
?>

