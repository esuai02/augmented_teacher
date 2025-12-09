<?php 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$encryption_id = $wboardid;

$timecreated = time();
//$sql = "INSERT INTO  createdb(encryption_id,contentsid,creator,lockwb,timecreated) VALUES ('$encryption_id','$contentsid','$userid',0,$timecreated)";

$sql = "INSERT INTO  createdb(encryption_id,contentstype,contentsid,creator,lockwb,timecreated) VALUES ('$encryption_id',1,'$contentsid','$userid',0,$timecreated)";
$sql1 = "INSERT INTO  boarddb(encryption_id,generate_id)  VALUES ('$encryption_id','0')";
$sql2 = "INSERT INTO  boarddb(encryption_id,generate_id,shape_data,timecreated)  VALUES ('$encryption_id','1','\[color black\]','$timecreated')";
$role='role';
 
$conn->query($sql);
$conn->query($sql1);
$conn->query($sql2);
 
// 초기 생성인 경우와 추후 방문 인 경우 구분해서 적용.. 이경우 INSERT를 UPDATE로  
$conn->close();

?>

