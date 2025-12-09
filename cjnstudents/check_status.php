<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
 
$status = $_POST['isactive'];
$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$type = $_POST['type'];
$prompt = $_POST['prompt'];
$trackingid = $_POST['trackingid'];
$attemptid = $_POST['attemptid'];
$checkboxid= $_POST['checkboxid'];

$thisrowid = $_POST['thisrowid'];
$itemtext= $_POST['itemtext'];
$rate = $_POST['rate'];
$resulttext = $_POST['resulttext'];
$checkimsi = $_POST['checkimsi']; 

$text = $_POST['text']; 
$inputtext = $_POST['inputtext']; 
$contentsid = $_POST['contentsid'];
$contentstype = $_POST['contentstype'];
$gid = $_POST['gid']; 
$wboardid =$_POST['wboardid'];
$timecreated=time();
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

 if($eventid==1) // gpt결과 저장
	{ 
	if($type==NULL)$type='drilling';
	$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE '$type' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '$gid'  ORDER BY id DESC LIMIT 1 ");
	if($thiscnt->id==NULL)
		{
		$newrecord = new stdClass();
		$newrecord->type = $type;
		$newrecord->contentsid = $contentsid;
		$newrecord->contentstype = $contentstype;
		$newrecord->outputtext = $text; // $gptresult 변수의 정의가 필요합니다.
		$newrecord->gid = $gid; 
		$newrecord->wboardid = $wboardid;
		$newrecord->timemodified = $timecreated;
		$newrecord->timecreated = $timecreated; // $timecreated 변수의 값 설정이 필요합니다.
		// 새 레코드를 mdl_abessi_messages 테이블에 삽입
		$DB->insert_record('abrainalignment_gptresults', $newrecord);
		}
	else
		{
		$record = new stdClass();
		$record->id = $thiscnt->id;
		$record->outputtext = $text;
		$record->type = $type;
		$record->contentsid = $contentsid;
		$record->contentstype = $contentstype;
		$record->gid = $gid; 
		$record->timemodified = $timecreated;
		$DB->update_record('abrainalignment_gptresults', $record);
		}
		echo json_encode( array("gid"=>$gid) );
	}
elseif($eventid==11) // gpt결과 저장
	{ 
	$thisrecord=$DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE trackingid LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");
	if($thisrecord->id==NULL)
		{
		$newrecord = new stdClass();
		$newrecord->userid =$userid;
		$newrecord->trackingid =$trackingid;
		$newrecord->type = 'activitylog';
		$newrecord->prompt = $prompt;
		$newrecord->resulttext = $resulttext;
		$newrecord->rate = $rate;
		$newrecord->timemodified = $timecreated;
		$newrecord->timecreated = $timecreated;  
		$DB->insert_record('abessi_reflections', $newrecord);
		}
	else
		{
		$record = new stdClass();
		$record->id = $thisrecord->id;
		$record->prompt = $prompt;
		$record->resulttext = $resulttext;
		$$record->rate = $rate;
		$record->timemodified = $timecreated;
		$DB->update_record('abessi_reflections', $record);
		}
		echo json_encode( array("thisuserid"=>$userid) );
	}
elseif($eventid==12) // attemptid로 퀴즈 결과 분석
	{ 
	$rate=0;
	$thisrecord=$DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE attemptid LIKE '$attemptid' ORDER BY id DESC LIMIT 1 ");
	if($thisrecord->id==NULL)
		{
		$newrecord = new stdClass();
		$newrecord->userid =$userid;
		$newrecord->attemptid =$attemptid;
		$newrecord->type = 'quizanalysis';
		$newrecord->prompt = $prompt;
		$newrecord->resulttext = $resulttext;
		$newrecord->rate = $rate;
		$newrecord->timemodified = $timecreated;
		$newrecord->timecreated = $timecreated;  
		$DB->insert_record('abessi_reflections', $newrecord);
		}
	else
		{
		$record = new stdClass();
		$record->id = $thisrecord->id;
		$record->prompt = $prompt;
		$record->resulttext = $resulttext;
		$$record->rate = $rate;
		$record->timemodified = $timecreated;
		$DB->update_record('abessi_reflections', $record);
		}

		$thisrecord2=$DB->get_record_sql("SELECT * FROM mdl_abessi_reflections WHERE attemptid LIKE '$attemptid' ORDER BY id DESC LIMIT 1 ");

		echo json_encode( array("thisTrackingid"=>$thisrecord2->id) );
	}
elseif($eventid==2) // gpt결과 저장
	{ 
	$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE 'pedagogy' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '$gid'  ORDER BY id DESC LIMIT 1 ");
	if($thiscnt->id==NULL)
		{
		$newrecord = new stdClass();
		$newrecord->type = 'pedagogy';
		$newrecord->contentsid = $contentsid;
		$newrecord->contentstype = $contentstype;
		$newrecord->outputtext = $text; // $gptresult 변수의 정의가 필요합니다.
		$newrecord->gid = $gid; 
		$newrecord->wboardid = $wboardid;
		$newrecord->timemodified = $timecreated;
		$newrecord->timecreated = $timecreated; // $timecreated 변수의 값 설정이 필요합니다.
		// 새 레코드를 mdl_abessi_messages 테이블에 삽입
		$DB->insert_record('abrainalignment_gptresults', $newrecord);
		}
	else
		{
		$record = new stdClass();
		$record->id = $thiscnt->id;
		$record->outputtext = $text;
		$record->contentsid = $contentsid;
		$record->contentstype = $contentstype;
		$record->gid = $gid; 
		$record->timemodified = $timecreated;
		$DB->update_record('abrainalignment_gptresults', $record);
		}
		echo json_encode( array("gid"=>$gid) );
	}
elseif($eventid==3) // gpt결과 저장 LIKE ''
	{ 
	//$DB->delete_records('abessi_reflections', ['trackingid' =>$attemptid]);
	//$DB->execute("DELETE FROM {abessi_reflections}  WHERE  id='$thisrowid' ");
	$DB->execute("DELETE FROM {abessi_reflections} WHERE id = ?", [$thisrowid]);
	echo json_encode( array("thisuserid"=>$USER->id) );
	}
elseif($eventid==4) // gpt결과 저장 LIKE ''
	{ 
	//$itemtext   $checkboxid
	$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_bhtracking WHERE trackingid LIKE '$trackingid' ORDER BY id DESC LIMIT 1 ");
    
 
	if($exist->id==NULL)
		{  //$checkboxid
		$newrecord = new stdClass();
		$newrecord->trackingid = $trackingid;
		$newrecord->{"list".$checkboxid} = $itemtext;
		$newrecord->{"check".$checkboxid} = $checkimsi;
		$newrecord->timemodified = $timecreated;
		$newrecord->timecreated = $timecreated;  
		$DB->insert_record('abessi_bhtracking', $newrecord);
		}
	else
		{
		$record = new stdClass();
		$record->id = $exist->id;
		$record->{"list".$checkboxid} = $itemtext;
		$record->{"check".$checkboxid} = $checkimsi;
		$record->timemodified = $timecreated;
		$DB->update_record('abessi_bhtracking', $record);
		}	
	//echo json_encode( array("thisuserid"=>$USER->id) );
	}
elseif($eventid==5) // 복습추가
	{ 
	$halfdayago=time()-43200;
	$exist=$DB->get_record_sql("SELECT id,text FROM mdl_abessi_tracking WHERE type LIKE 'weeklyreview' AND userid LIKE '$userid' AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1 ");
	$inputtext=$exist->text.'✏️ '.$inputtext.'  ';
	$DB->execute("UPDATE {abessi_tracking} SET text='$inputtext' WHERE id LIKE '$exist->id' ORDER BY id DESC LIMIT 1    ");
	echo json_encode( array("usrid"=>$USER->id) );
	} 
?>

