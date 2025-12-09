<?PHP 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$userid = $_GET["userid"];
$cron = $_GET["cron"];
$timecreated=time();
$aweekago=$timecreated-604800;
$minutesago=$timecreated-600;
echo '<meta http-equiv="refresh" content="30">';  
$chat=$DB->get_record_sql("SELECT * FROM mdl_abessi_chat WHERE userto='$USER->id'   AND mark=1 AND t_trigger > '$minutesago' ORDER BY id DESC LIMIT 1");
 	//AND (mode LIKE 'psc' || mode LIKE 'detectwb' || mode LIKE 'checkknowledge')
if($chat->id!=NULL) // 선생님 처리 준비 상태 체크하여 조건 추가, 새로운 팝업 열기는 이부분으로 모두 옮기기 
	{
	$contextid=$chat->contextid;
	$currenturl=$chat->currenturl;
	 
	if($cron==NULL)echo '<script> 
	var Contextid= \''.$contextid.'\';
	var Currenturl= \''.$currenturl.'\';
	var Chatmode= \''.$chat->mode.'\'; 
	var yourWindow = window.open();
	yourWindow.opener = null;
	yourWindow.location =Contextid +"?"+Currenturl+"&chatmode="+Chatmode;
	yourWindow.target = "_blank";  </script>'; 

	//if($timecreated-$quizattempts->timefinish <30) 
		{
		$userinfo=$DB->get_record_sql("SELECT id, lastname, firstname FROM mdl_user WHERE id='$chat->userid' ");
		$component='quizattempt';
		if($chat->mode==='ask')$eventname='학생질문';
		elseif($chat->mode==='begin')$eventname='평가준비';  // 생성 제거 후 여기 제거.
		elseif($chat->mode==='studentreply')$eventname='학생답변';
		elseif($chat->mode==='delay')$eventname='활동지연';
		elseif($chat->mode==='today')$eventname='활동점검';
		elseif($chat->mode==='initiate')$eventname='초기설정';
		elseif($chat->mode==='checkknowledge')$eventname='개념체크';
		$userfrom=$chat->userid;
		$userto=$chat->userto;
		$sendername=$userinfo->firstname.$userinfo->lastname;
		$notificationtext='알림 '.$sendername.' '.$eventname.' '.$contextid.'?'.$currenturl.' 메세지 : https://mathking.kr/moodle/message/index.php?id='.$userfrom;
		include("notification.php"); 
		}
	$DB->execute("UPDATE {abessi_chat} SET mark=0 WHERE id='$chat->id' "); 

	} 
 
?>