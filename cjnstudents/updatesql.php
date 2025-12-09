<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
  
$id=$_GET["id"]; 
$type=$_GET["type"]; 
$tablename=$_GET["tablename"];  

$timecreated=time();
$minutesago=$timecreated-600;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800; 

  
$exist= $DB->get_record_sql("SELECT  * FROM mdl_$tablename WHERE timecreated>'$halfdayago' "); 
$sqldata=$DB->get_records_sql("SELECT * FROM mdl_$tablename where timecreated>'$aweekago' ORDER BY id DESC LIMIT 1 ");  
$result = json_decode(json_encode($gptlogs), True);
unset($value);
foreach($result as $value)
	{
    $qstnlist.='<tr><td>'.$value['question'].'</td><td></td></tr>';
	}  

foreach(array_reverse($result) as $value)
	{
    }

 // 아이디별로 여러개의 url 페이지 링크 현성
?>