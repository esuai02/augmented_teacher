<?php 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
$timecreated=time(); 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$encryption_id = $wboardid;
 
$timecreated = time();
//$sql = "INSERT INTO  createdb(encryption_id,contentsid,creator,lockwb,timecreated) VALUES ('$encryption_id','$contentsid','$userid',0,$time)";
$sql = "INSERT INTO  createdb(encryption_id,contentstype,contentsid,creator,lockwb,timecreated) VALUES ('$encryption_id',1,'$contentsid','$userid',0,$timecreated)";

$sql1 = "INSERT INTO  boarddb(encryption_id,generate_id)  VALUES ('$encryption_id','0')";
$sql2 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','1','\[color black\]','$timecreated')";

$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
$ctext=$getimg->pageicontent;
$cmid=$getimg->cmid;
$ctitle=$getimg->title;
//Create a new DOMDocument object.
$htmlDom = new DOMDocument;
$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,status,contentstype,wboardid,contentstitle,cmid,contentsid,timemodified,timecreated) VALUES('$userid','2','$role','2','0','0','begin','1','$encryption_id','$ctitle','$cmid','$contentsid','$timecreated','$timecreated')");

//Load the HTML string into our DOMDocument object.
@$htmlDom->loadHTML($ctext);
 
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
	if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
	}
 
$imageurl ='\[image '.$imgSrc.' 50 50\]';
$sql3 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','2','[color black]','$timecreated')"; 
$sql3 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','2','$imageurl','$timecreated')"; 
$conn->query($sql);
$conn->query($sql1);
$conn->query($sql2);
$conn->query($sql3);
// 초기 생성인 경우와 추후 방문 인 경우 구분해서 적용.. 이경우 INSERT를 UPDATE로  
$conn->close();

?>

