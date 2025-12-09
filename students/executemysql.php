<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
 
global $DB, $USER;

echo 'begin';
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role==='teacher')
	{
	$select=$DB->get_records_sql("SELECT * FROM mdl_tag_instance LEFT JOIN mdl_question on mdl_tag_instance.itemid=mdl_question.id 
	WHERE  mdl_tag_instance.tagid=257 AND mdl_question.name LIKE '%MP 수1%'  AND mdl_tag_instance.itemtype LIKE 'question'    ");
 
	$result = json_decode(json_encode($select), True);
	unset($value);
	foreach($result as $value) 
		{
		$itemid=$value['itemid'];
		$tagid=$value['tagid'];
 		$DB->execute("UPDATE {tag_instance} SET  tagid='258' WHERE itemid='$itemid' AND tagid='257'   ");  	
		echo 'itmeid='.$itemid.'....tagid='.$tagid.'<br>';
		}
	}
 
?>