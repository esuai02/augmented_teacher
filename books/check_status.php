<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$cid =$_POST['cid'];
$domainid = $_POST['domainid'];
$chapterid = $_POST['chapterid'];
$topicid = $_POST['topicid'];
$createmode=$_POST['createmode'];
$cntid = $_POST['cntid'];

$contextid = $_POST['contextid'];
$srcid =$_POST['srcid'];
$wboardid =$_POST['wboardid'];
$contentsid =$_POST['contentsid'];
$contentstitle =$_POST['contentstitle'];

$contentstype =$_POST['contentstype'];
 
$ncnt=$_POST['ncnt'];
$nstep=$_POST['nstep'];
$title=$_POST['title'];
$studentid = $_POST['studentid'];
$userid = $_POST['userid'];
$context = $_POST['context'];
$url = $_POST['url'];
$inputtext = $_POST['inputtext'];
$step1 = $_POST['step1'];
$step2 = $_POST['step2'];
$step3 = $_POST['step3'];
$step4 = $_POST['step4'];
$step5 = $_POST['step5'];
$step6 = $_POST['step6'];
$step7 = $_POST['step7'];
      
$checkimsi = $_POST['checkimsi']; 
$timecreated=time();
$halfdayago=time()-43200;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
// 일반적인 입력상황은 제일 마지막 else 부분

if($eventid==0) // 일반적인 text 입력상황
	{
	$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_gptultratalk} (creator,studentid,role,gpttalk,contextid,context,url,status,timecreated) VALUES('$USER->id','$studentid','$role','$inputtext','$contextid','$context','$url','connected','$timecreated')");
	else $DB->execute("UPDATE {abessi_gptultratalk} SET gpttalk='$inputtext', timemodified='$timecreated' WHERE contextid='$contextid' "); 
	
	$exist2=$DB->get_record_sql("SELECT id FROM mdl_abessi_gptultratalk where contextid='$contextid' ORDER BY id DESC LIMIT 1 "); 
	echo json_encode( array("cntid" =>$exist2->id) );
	}
elseif($eventid==1) // gpttalk update
	{
	$DB->execute("UPDATE {abessi_gptultratalk} SET gpttalk='$inputtext', timemodified='$timecreated' WHERE id='$cntid' "); 	
	echo json_encode( array("cntid" =>$cntid) );
	}
elseif($eventid==2) // chapter progress check
	{
	$exist=$DB->get_record_sql("SELECT id FROM mdl_checklist_check  WHERE item='$cntid' AND userid='$userid' ORDER BY id DESC LIMIT 1  "); 
	if($checkimsi==1)$usertimestamp=$timecreated;
	elseif($checkimsi==0)$usertimestamp=0;

	if($USER->id==$userid)
		{
		if($exist->id==NULL)$DB->execute("INSERT INTO {checklist_check} (userid,item,usertimestamp) VALUES('$userid','$cntid','$timecreated')");
		else $DB->execute("UPDATE {checklist_check} SET usertimestamp='$usertimestamp' WHERE item='$cntid' AND userid='$userid' ORDER BY id DESC LIMIT 1 ");  
		}
	if($role!=='student')
		{
		$topic=$DB->get_record_sql("SELECT * FROM mdl_checklist_item where id='$cntid' ");  //AND  title NOT LIKE '%Approach%' 

		if($checkimsi==1) 
			{
			//$escaped_text = addslashes($topic->displaytext);
			//$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,text,duration,timecreated) VALUES('$userid','instruction','$USER->id','waiting','$escaped_text','$timecreated','$timecreated')"); 
			$record = new stdClass();
			$record->userid = $userid;
			$record->type = 'instruction';
			$record->teacherid = $USER->id;
			$record->status = 'waiting';
			$record->text = $topic->displaytext;
			$record->duration = $timecreated;
			$record->timecreated = $timecreated;

			$DB->insert_record('abessi_tracking', $record);

			}
		elseif($checkimsi==0)
			{
				$exist2=$DB->get_record_sql("SELECT id FROM mdl_abessi_tracking WHERE userid='$userid' AND displaytext LIKE '$topic->displaytext' AND status='waiting' AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1    ");
			if($exist2->id!=NULL)$DB->execute("UPDATE {abessi_tracking} SET hide=1 WHERE id='$exist2->id' ORDER BY id DESC LIMIT 1 "); 
			}
		}
	}
elseif($eventid==3) // micro-consolidation
	{
	$exist=$DB->get_record_sql("SELECT id FROM mdl_abessi_cognitiveassessment  WHERE contentsid='$contentsid' AND ncnt='$ncnt' ORDER BY id DESC LIMIT 1  "); 

	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_cognitiveassessment} (title,srcid,wboardid,contentsid,contentstype,ncnt,teacherid,step1,step2,step3,step4,step5,step6,step7,timemodified,timecreated) VALUES('$title','$srcid','$wboardid','$contentsid','$contentstype','$ncnt','$USER->id','$step1','$step2','$step3','$step4','$step5','$step6','$step7','$timecreated','$timecreated')");
	else $DB->execute("UPDATE {abessi_cognitiveassessment} SET ncnt='$ncnt', title='$title', step1='$step1',step2='$step2',step3='$step3',step4='$step4',step5='$step5',step6='$step6',step7='$step7', timemodified='$timecreated' WHERE wboardid='$wboardid' AND ncnt='$ncnt' ORDER BY id DESC LIMIT 1  ");   
	$url='https://mathking.kr/moodle/local/augmented_teacher/books/input.php?wboardid='.$srcid.'ncnt'.$ncnt.'&srcid='.$srcid.'&ncnt='.$ncnt.'&contentsid='.$contentsid;
	echo json_encode( array("url" =>$url) );
	}

elseif($eventid==4 && $role!=='student') // micro-consolidation에서 다음화이트보드로 이동
	{
	//$indic= $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
	if($createmode==7) 
		{
		$DB->execute("UPDATE {abessi_indicators} SET aistep=0 WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 "); 
		$DB->execute("UPDATE {abessi_immersive} SET status='complete' WHERE status LIKE 'begin' AND userid LIKE '$USER->id' ORDER BY id DESC LIMIT 1 "); 	
		}
	else 
		{  
		//$showthis='cid'.$cid.'userid'.$userid.'domainid'.$domainid.'chapter'.$chapterid.'topicid'.$topicid;
		//include("../showthis.php");
		//abessi_immersive
		$DB->execute("UPDATE {abessi_indicators} SET aistep=7 WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 "); 
		$DB->execute("INSERT INTO {abessi_immersive} (userid,status,cid,domainid,chapterid,topicid,timecreated) VALUES('$USER->id','begin','$cid','$domainid','$chapterid','$topicid','$timecreated')");	
		}
	//echo json_encode( array("url" =>$url) );
	}
elseif($eventid==5) // tts 용 text 저장 (기존 대사를 덮어쓰기)
	{ 
	//$DB->execute("UPDATE {abrainalignment_gptresults} SET outputtext='$inputtext' WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 "); 
	$exist=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid='$contentsid' AND contentstype='$contentstype' ORDER BY id DESC LIMIT 1  "); 
	$thisid=$exist->id;
 
	// 기존 대사를 새 대사로 덮어쓰기 (UPDATE)
	$record = new stdClass();
	$record->id = $thisid;
	$record->outputtext = $inputtext;
	$record->timemodified = $timecreated;
	$DB->update_record('abrainalignment_gptresults', $record);
		
	echo json_encode( array("thisuserid"=>$USER->id) );
 	}	 
elseif($eventid==51) // tts 용 text 저장, pmemory
	 { 
	 //$DB->execute("UPDATE {abrainalignment_gptresults} SET outputtext='$inputtext' WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 "); 
	 $exist=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE 'pmemory' AND contentsid='$contentsid' AND contentstype='$contentstype' ORDER BY id DESC LIMIT 1  "); 
	 $thisid=$exist->id;
  
	 $record = new stdClass();
	 $record->id = $thisid;
	 $record->outputtext = $inputtext;
	 $record->timemodified = $timecreated;
	 $DB->update_record('abrainalignment_gptresults', $record);
		 
	 echo json_encode( array("thisuserid"=>$USER->id) );
	  }	
elseif($eventid==6) //audio 재생 기록
	 { 
     $DB->execute("UPDATE {abessi_messages} SET nreview=nreview+1,contentstitle='$contentstitle', mtype='audio', timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");  
	 echo json_encode( array("thisuserid"=>$USER->id) );
	 }	 
?>

   