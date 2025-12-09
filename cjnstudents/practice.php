<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 버튼 클릭 >> 좌측에는 클릭 후 상황에 대한 문맥 텍스트. 우측은 해당 페이지. 우측 링크는 팝업 또는 현재 페이지에서 열기. 현재 페이지에서 활동페이지 열리는 경우는 채팅 아이콘.. 
$userid=$_GET["userid"]; 
$cid=$_GET["cid"];   
$domainid=$_GET["domainid"]; 
$chapterid=$_GET["chapterid"]; 
$topicid=$_GET["topicid"]; 

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

$timecreated=time();
$minutesago=$timecreated-600;
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;  


$imrsv=$DB->get_records_sql("SELECT * FROM mdl_abessi_immersive WHERE cid LIKE '$cid' AND domainid LIKE '$domainid' AND chapterid LIKE '$chapterid' ORDER BY id DESC LIMIT 30");
 
$result = json_decode(json_encode($imrsv), True);
 
unset($value);
foreach($result as $value)
	{ 
    }

echo '<table width=90%>'.$todolist.'</table>';
?>