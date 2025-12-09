<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$teacherid = $_POST['teacherid'];
$attemptid = $_POST['attemptid'];
$talkid = $_POST['sid'];
$trackingid = $_POST['trackingid'];
$threadid = $_POST['threadid'];
$date = $_POST['date'];
$duration = $_POST['duration'];
$inputtext = $_POST['inputtext'];
$result = $_POST['result'];
$status = $_POST['status'];
$course = $_POST['course'];
$type = $_POST['type'];
$questionid = $_POST['questionid'];
$checkimsi = $_POST['checkimsi'];
$timecreated=time();
$aweekago=$timecreated-604800;
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

if($eventid==1) //check_comment confirm
{
$DB->execute("UPDATE {studentquiz_comment} SET confirm='$checkimsi' WHERE userid='$userid' AND questionid='$questionid' ");
}
elseif($eventid==2) //checkflag
{
$DB->execute("UPDATE {question_attempts} SET checkflag='$checkimsi' WHERE id='$attemptid'");
}
elseif($eventid==3) //checkfeedback
{
$DB->execute("UPDATE {question_attempts} SET feedback='$checkimsi' WHERE id='$attemptid'");
}
elseif($eventid==4) // review
{
$DB->execute("UPDATE {studentquiz_comment} SET confirm='$checkimsi' WHERE userid='$userid' AND questionid='$questionid' ");
}
elseif($eventid==5) // reask
{
$DB->execute("UPDATE {studentquiz_comment} SET confirm='$checkimsi' WHERE userid='$userid' AND questionid='$questionid' ");
}
elseif($eventid==6) // complete
{
$DB->execute("UPDATE {studentquiz_comment} SET confirm='$checkimsi' WHERE userid='$userid' AND questionid='$questionid' ");
}
elseif($eventid==7) // okgood
{
$DB->execute("UPDATE {studentquiz_comment} SET confirm='$checkimsi' WHERE userid='$userid' AND questionid='$questionid' ");
} 

// 2021.11.28
elseif($eventid==8) // 오늘 활동 완료 표시
	{
	$halfdayago=time()-43200;
	$DB->execute("UPDATE {abessi_indicators} SET aion='$checkimsi' WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	} 

// 2022.1.5
elseif($eventid==9) // 오늘 활동 완료 표시
	{
	$user=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
	if($checkimsi==1)
		{
		if($user->mntr1==0)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr1='$userid' WHERE userid='$teacherid' ");
	  	elseif($user->mntr2==0)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr2='$userid' WHERE userid='$teacherid' ");
		elseif($user->mntr3==0)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr3='$userid' WHERE userid='$teacherid' ");
		}
	else 
		{
		if($user->mntr1==$userid)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr1='0' WHERE userid='$teacherid' ");
		if($user->mntr2==$userid)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr2='0' WHERE userid='$teacherid' ");
		if($user->mntr3==$userid)$DB->execute("UPDATE {abessi_teacher_setting} SET mntr3='0' WHERE userid='$teacherid' ");
		}
	}
elseif($eventid==10) // psclass talk2us
	{
	$DB->execute("INSERT INTO {abessi_talk2us} (eventid,studentid,teacherid,context,status,text,timemodified,timecreated) VALUES('7128','$userid','$USER->id','share','begin','$inputtext','$timecreated','$timecreated')");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==11) // psclass talk2us 에 대한 reply
	{
	$DB->execute("INSERT INTO {abessi_talk2us} (talkid,eventid,studentid,teacherid,context,status,text,timemodified,timecreated) VALUES('$talkid','8217','$userid','$USER->id','feedback','begin','$inputtext','$timecreated','$timecreated')");
	$DB->execute("UPDATE {abessi_talk2us} SET timemodified='$timecreated' WHERE id='$talkid' ORDER BY id DESC LIMIT 1 ");
	echo json_encode( array("talkid"=>$talkid) );
	} 
elseif($eventid==12) // 목표기준 입력  eventid,editor,course,type,srcid,text,num,timemodified,timecreated
	{
	$DB->execute("INSERT INTO {abessi_knowhow} (eventid,editor,course,type,text,active,timemodified,timecreated) VALUES('7128','$USER->id','$course','$type','$inputtext','1','$timecreated','$timecreated')");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==13) // 하위기준 입력
	{
	$srcid = $_POST['srcid'];
	$DB->execute("INSERT INTO {abessi_knowhow} (eventid,editor,text,srcid,active,timemodified,timecreated) VALUES('8217','$USER->id','$inputtext','$srcid','1','$timecreated','$timecreated')");
	echo json_encode( array("talkid"=>$talkid) );
	} 

//kid active studentid teacherid timemodified timecreated

elseif($eventid==14) // 학생 맞춤형 체크선택
	{
	$srcid = $_POST['srcid']; $itemid = $_POST['itemid']; 
	$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_knowhowlog WHERE studentid='$userid' AND itemid='$itemid' ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_knowhowlog} (srcid,itemid,active,studentid,teacherid,timemodified,timecreated) VALUES('$srcid','$itemid','1','$userid','$USER->id','$timecreated','$timecreated')");
	else $DB->execute("UPDATE {abessi_knowhowlog} SET active='$checkimsi', timemodified='$timecreated' WHERE id='$exist->id' ORDER BY id DESC LIMIT 1    ");

	echo json_encode( array("teacherid"=>$USER->id) );
	} 

elseif($eventid==15) // 항목 숨기기
	{
	$itemid = $_POST['itemid']; 
 	$DB->execute("UPDATE {abessi_knowhow} SET active='$checkimsi', timemodified='$timecreated' WHERE id='$itemid' ORDER BY id DESC LIMIT 1    ");
 	$DB->execute("UPDATE {abessi_knowhowlog} SET active='0', timemodified='$timecreated' WHERE itemid='$itemid'       ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==16) // 학생에게 숨기기
	{
	$fbid = $_POST['fbid']; 
 	$DB->execute("UPDATE {abessi_talk2us} SET hide='$checkimsi', timemodified='$timecreated' WHERE id='$fbid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==17) // CA, CB, CC, CD setconditions에서 변동사항 알림 클릭.
	{
	$mode = $_POST['mode']; 
 	$DB->execute("UPDATE {abessi_talk2us} SET status='alert', context='$mode', timemodified='$timecreated' WHERE studentid='$userid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==18) // Edittext
	{
	$itemid = $_POST['itemid']; 
 	$DB->execute("UPDATE {abessi_knowhow} SET text='$inputtext', timemodified='$timecreated' WHERE id='$itemid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 
elseif($eventid==19) // Edittext
	{
	$itemid = $_POST['itemid']; 
 	$DB->execute("UPDATE {abessi_talk2us} SET text='$inputtext', timemodified='$timecreated' WHERE id='$itemid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("teacherid"=>$USER->id) );
	} 

	/*
elseif($eventid==20) // time tracking
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
	$inputtext='활동 구간 설정 진행 중';
	
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)$duration=$timecreated+$duration*60;
	else $duration=$exist->duration+$duration*60;
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	$DB->execute("INSERT INTO {abessi_tracking} (userid,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','$USER->id','begin','$wboardid','$duration','$inputtext','$timecreated')"); 
	} */
elseif($eventid==21) // inputtext tracking
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
 
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago'  ORDER BY id DESC LIMIT 1    ");

	if($status==='waiting')	
		{
		$duration=$timecreated+$duration*60;
		//$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,duration,text,timecreated) VALUES('$userid','instruction','$USER->id','waiting','$duration','$inputtext','$timecreated')"); 
		$record = new stdClass();
		$record->userid = $userid;
		$record->type = 'instruction';
		$record->teacherid = $USER->id;
		$record->status = 'waiting';
		$record->duration = $duration;
		$record->text = $inputtext;
		$record->timecreated = $timecreated;

		$DB->insert_record('abessi_tracking', $record);
		}
	elseif($exist->id==NULL && $inputtext!=NULL)
		{
		$duration=$timecreated+$duration*60;
		//$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','task','$USER->id','begin','$wboardid','$duration','$inputtext','$timecreated')"); 
		$record = new stdClass();
		$record->userid = $userid;
		$record->type = 'task';
		$record->teacherid = $USER->id;
		$record->status = 'begin';
		$record->wboardid = $wboardid;
		$record->duration = $duration;
		$record->text = $inputtext;
		$record->timecreated = $timecreated;

		$DB->insert_record('abessi_tracking', $record);
		}
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	echo json_encode( array("usrid"=>$USER->id) );
	}
/*
elseif($eventid==22) // finish tracking
	{
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago' ORDER BY id ASC LIMIT 1    ");
	$DB->execute("UPDATE {abessi_tracking} SET status='complete' WHERE id='$exist->id' ORDER BY id DESC LIMIT 1    ");
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	} 
*/
elseif($eventid==23) // update text
	{
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1    ");
	$duration=$exist->timecreated+$duration*60; 
	$DB->execute("UPDATE {abessi_tracking} SET text='$inputtext',duration='$duration' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1    ");
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==24) // 10분 추가
	{
	$DB->execute("UPDATE {abessi_tracking} SET duration=duration+600, ndisengagement=ndisengagement+1 WHERE userid LIKE '$userid' AND status LIKE 'begin'   ");
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==244) // 항목 숨기기
	{
	$DB->execute("UPDATE {abessi_tracking} SET status='complete', hide='1', timemodified='$timecreated' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");
//	$DB->execute("UPDATE {user} SET lastaccess='$timecreated' WHERE id =''$userid' ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==25) // complete ankithread
	{
	
	$DB->execute("UPDATE {abessi_ankithread} SET status='complete' WHERE id LIKE '$threadid' AND status LIKE 'begin' ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==26) // complete this activity
	{
	$DB->execute("UPDATE {abessi_tracking} SET result='$result', status='complete',timefinished='$timecreated' WHERE userid LIKE '$userid' AND status LIKE 'begin' ORDER BY id DESC LIMIT 1 ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==27) //  
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
	
	$dateObject = new DateTime($date);
	$duration = $dateObject->getTimestamp();
	if($inputtext!=NULL)$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','homework','$USER->id','homework','$wboardid','$duration','$inputtext','$timecreated')"); 
		
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==277)  
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
 
	$dateObject = new DateTime($date);
	$duration = $dateObject->getTimestamp();
	if($inputtext!=NULL)$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','schedule','$USER->id','schedule','$wboardid','$duration','$inputtext','$timecreated')"); 
	 
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==28) //  user context 입력
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
	
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='context'   ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)
		{
		$duration=$timecreated+604800*60;
		$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','context','$USER->id','context','$wboardid','$duration','$inputtext','$timecreated')"); 
		} 
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==29) // 클릭하여 다음 활동 입력
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'   ORDER BY timemodified DESC LIMIT 1"); 
	$wboardid =$thisboard->wboardid;
 
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago'  ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)
		{
		$duration=$timecreated+$duration*60;
		$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','task','$USER->id','begin','$wboardid','$duration','$inputtext','$timecreated')"); 
		} 
	echo json_encode( array("usrid"=>$USER->id) );
	}
elseif($eventid==291) // 클릭하여 다음 활동 입력
	{
		 
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated>'$aweekago'  ORDER BY id DESC LIMIT 1    ");
	if($exist->id==NULL)
		{
		$duration=$timecreated+$duration*60;
		//$DB->execute("UPDATE {abessi_tracking} SET text='$inputtext', status='begin', duration='$duration', timecreated='$timecreated' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");
		$record = new stdClass();
		$record->id = $trackingid;
  		$record->status = 'begin';
 		$record->duration = $duration;
 		$record->timecreated = $timecreated;

		$DB->update_record('abessi_tracking', $record);
 		}
	echo json_encode( array("usrid"=>$USER->id) );
	}
elseif($eventid==30) // add comment
	{
	$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_tracking WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1    ");
	if($role==='student')$DB->execute("UPDATE {abessi_tracking} SET comment='$inputtext' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1    ");
	else $DB->execute("UPDATE {abessi_tracking} SET feedback='$inputtext' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==301) // add comment
	{
	$DB->execute("UPDATE {abessi_tracking} SET text='$inputtext' WHERE id LIKE '$trackingid' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==31) // 메시지로 시간알림
	{
	$checkexist=$DB->get_record_sql("SELECT * FROM mdl_abessi_chat WHERE userid='$userid' AND mark=0 AND mode LIKE 'alertdue' ORDER BY id DESC LIMIT 1"); 
	if($checkexist->id==NULL)$DB->execute("INSERT INTO {abessi_chat} (mode,text,userid,userto,wboardid,mark,t_trigger) VALUES('alertdue','$inputtext','$userid','2','none','0','$timecreated')");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
elseif($eventid==32) //  주기적 메세지 알림, 뇌과학 정보
	{
	$checkexist=$DB->get_record_sql("SELECT * FROM mdl_abessi_chat WHERE userid='$userid' AND mark=0 AND mode LIKE 'periodic' ORDER BY id DESC LIMIT 1");
	if($checkexist->id==NULL)$DB->execute("INSERT INTO {abessi_chat} (mode,text,userid,userto,wboardid,mark,t_trigger) VALUES('periodic','$inputtext','$userid','2','none','0','$timecreated')");
	echo json_encode( array("usrid"=>$USER->id) );
	}
elseif($eventid==401) // 오늘 평가 내용 추가 - reflection2 (1~5: 길을 잃음, 산만함, 성실함, 매우 성실, 열정적)
	{
	try {
		$reflection2 = $_POST['reflection2'];

		// reflection2 값 검증 (1-5 범위)
		if ($reflection2 < 1 || $reflection2 > 5) {
			throw new Exception("Invalid reflection2 value: $reflection2 (check.php:line " . __LINE__ . ")");
		}

		// 평가 텍스트 매핑
		$reflectionTexts = array(
			1 => '길을 잃음',
			2 => '산만함',
			3 => '성실함',
			4 => '매우 성실',
			5 => '열정적'
		);
		$reflectionText = $reflectionTexts[$reflection2];

		// 기존 오늘목표 또는 검사요청 레코드 확인 (timescaffolding.php의 $checkgoal 쿼리와 동일)
		$today = $DB->get_record_sql("SELECT * FROM {abessi_today} WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1");

		if ($today && $today->id) {
			// 기존 레코드가 있으면 reflection2 필드 업데이트
			$DB->execute("UPDATE {abessi_today} SET reflection2='$reflection2' WHERE id='$today->id'");
		} else {
			// 기존 레코드가 없으면 새로운 레코드 생성
			$record = new stdClass();
			$record->userid = $userid;
			$record->type = '오늘평가';
			$record->text = $reflectionText;
			$record->reflection2 = $reflection2;
			$record->timecreated = $timecreated;

			$DB->insert_record('abessi_today', $record);
		}

		echo json_encode(array("success" => true, "reflection2" => $reflection2, "text" => $reflectionText));
	} catch (Exception $e) {
		echo json_encode(array("error" => $e->getMessage(), "file" => "check.php", "line" => $e->getLine()));
	}
	}
?>

