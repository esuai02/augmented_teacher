<?PHP 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$wboardid = $_GET["wboardid"];
$userid = $_GET["userid"];
$aweekago=time()-604800;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
 
if($userrole->role==='student')
	{
	echo '<meta http-equiv="refresh" content="3">;';
	$chat=$DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_chat WHERE mode LIKE 'refresh' AND wboardid='$wboardid'  AND mark=1 AND t_trigger > '$aweekago' ");
 	$timecreated=time();
	if($chat->id!=NULL)
		{
		$DB->execute("UPDATE {abessi_chat} SET mark=0,t_trigger='$timecreated' WHERE wboardid='$wboardid'  AND t_trigger > '$aweekago' "); 
		//echo '<script> setTimeout(function(){top.location.reload(); },500);  </script>';
		echo '<script> setTimeout(function(){top.location.href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'&nalert=1&triggered='.$timecreated.'"},500);  </script>';
		}
	}

/*
echo '<script>  
		var Chatid=\''.$chat_id.'\';
		$.ajax({
		url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
		type: "POST",
		dataType:"json",
		data : {
		"eventid":\'5\',
		"id":Chatid,	
		},
		success:function(data){
		 
		 }
		 }); 
	
	</script>';
*/
 
?>