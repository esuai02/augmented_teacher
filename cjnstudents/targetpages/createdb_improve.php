<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$userid=$USER->id;
 
//$encryption_id2 = GenerateString(15);
//$encryption_id2 = 'Q7MQFA'.$keyquestionid.'0tsDoHfRT_user'.$data->userid.'_'.date("Y_m_d", time());
$time = time();

if($role==='student' )
{
$nstar=0;
$authlevel=$DB->get_record_sql("SELECT data AS level FROM mdl_user_info_data where userid='$USER->id' AND fieldid='60' "); 
if($authlevel->level>2)$nstar=1;
}
if($role==='teacher' )$nstar=3; 

$getimg=$DB->get_record_sql("SELECT questiontext,name,generalfeedback FROM mdl_question WHERE id ='$keyquestionid' ");
$qtext=$getimg->questiontext;
$questionname=$getimg->name;
$generalfeedback=$getimg->generalfeedback; 

	$sql5 = "INSERT INTO  createdb(encryption_id,contentstype,contentsid,creator,starred,lockwb,timecreated) VALUES ('$encryption_id2',2,'$keyquestionid','$userid',$nstar,0,$time)";
 
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
	if(strpos($imgSrc, 'mathking.kr/Contents/MATH%20MATRIX/MATH%20images')!= false)break;
 	}
$imageurl ='\[image '.$imgSrc.' 50 50\]';
$sql9 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id2','2','$imageurl','$time')"; 
$conn->query($sql5);
$conn->query($sql9);
 
//Create a new DOMDocument object.
$htmlDom2 = new DOMDocument;
 
//Load the HTML string into our DOMDocument object.
@$htmlDom2->loadHTML($generalfeedback);
 
//Extract all img elements / tags from the HTML.
$imageTags2 = $htmlDom2->getElementsByTagName('img');
 
//Create an array to add extracted images to.
$extractedImages = array();

 $nimg=0;
unset($imageTag);
foreach($imageTags2 as $imageTag)
	{
	$nimg++;
    	$imgSrc2 = $imageTag->getAttribute('src');
	$imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
	if((strpos($imgSrc2, 'MATRIX')!= false || strpos($imgSrc2, 'HintIMG')!= false) && strpos($imgSrc2, 'hintimages')== false )break;
	}   

$sql6 = "INSERT INTO  boarddb(encryption_id,generate_id)  VALUES ('$encryption_id2','0')";
$sql6 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id2','1','\[color black\]','$time')";
 

$imageurl2 ='\[image '.$imgSrc2.' 50 1500\]';
$sql7 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id2','2','[color black]','$time')"; 
$sql7 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id2','3','$imageurl2','$time')"; 

$conn->query($sql6);
 
$conn->query($sql7);
 
 
$conn->close();
?>

