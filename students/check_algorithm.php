<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
 
$status = $_POST['isactive'];
$eventid = $_POST['eventid'];
$wboardid =$_POST['wboardid'];
$pagenum = $_POST['pagenum'];
$cmid = $_POST['cmid']; 
$userid = $_POST['userid'];
$tutorid = $_POST['tutorid'];
$feedbacktype = $_POST['feedbacktype'];
$feedbackid = $_POST['feedbackid'];
$contextid = $_POST['contextid'];
$currenturl = $_POST['currenturl'];
$answerid=$_POST['answerid'];
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$userid0 = $_POST['userid0'];
$contentsid0 = $_POST['contentsid0'];
$contentstype0 = $_POST['contentstype0'];
$timecreated=time();
$eventname='\mod_hotquestion\event\course_module_viewed';
$DB->execute("INSERT INTO {logstore_standard_log} (eventname,component,action,target,objecttable,objectid,crud,edulevel,contextid,contextlevel,contextinstanceid,userid,courseid,relateduserid,anonymous,other,timecreated,origin) 
          SELECT   eventname,component,action,target,objecttable,objectid,crud,edulevel,contextid,contextlevel,contextinstanceid,'$USER->id',courseid,'$tutorid','0','7128','$timecreated','web' FROM  {logstore_standard_log} WHERE id ='21671604' ");
//VALUES('$eventname','mod_whiteboard','submitted','course_module','whiteboard','41017128','c','2','41017128','900','41017128','$USER->id','193','$tutorid','0','7128','$timecreated','web')");
if($eventid==2) // 강제 지도 모드
	{
	$inputtext = $_POST['inputtext'];
	$timediff=time()-180;  // 3분간 구속 --> 메세지에 표시
	$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1    ");
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,context,url,forced,mark,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('강제지도','$inputtext','1','$contextid','$currenturl','1','1','$userid','$USER->id','$contentsid','$contentstype','$wboardid','$timecreated')");
	else 
		{
		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");	
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,forced,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('강제지도','$inputtext','1','1','1','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', mark=1, teacherid='$USER->id', forced=1,context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
 		}

	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','동기화','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timemodified='$timecreated' WHERE wboardid='$wboardid'  ");
	} 

if($eventid==3) // 학생선택 전달하기 
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext;
	$updatestatus='studentreply';
	if(strpos($inputtext, '이해했습니다')!= false )$updatestatus='complete';

	if($answerid==6)$updatestatus='askcorrection';
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==NULL || $fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('학생응답','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', mark=10, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	$DB->execute("UPDATE {abessi_messages} SET status='$updatestatus', timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
 
	} 
if($eventid==30) // stepbystep 도제학습 모드에서 학생 답변
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	if($inputtext==NULL)$inputtext='검토해 주세요.';
	else $inputtext ='※ '.$fname.' : '.$inputtext.'(도제학습)';
	$DB->execute("UPDATE {abessi_indicators} SET nask=nask+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 학생질문 카운트

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;

	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('학생응답','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', mark=10,  teacherid='$USER->id',context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	$DB->execute("UPDATE {abessi_messages} SET status='submitstepbystep', teacher_check=1, timemodified='$timecreated' WHERE userid='$userid' AND wboardid='$wboardid'  ORDER BY timecreated DESC LIMIT 1 ");
	} 
if($eventid==31) // 학생 질문 전달
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	if($inputtext==NULL)$inputtext='질문을	표시하였습니다.';
	else $inputtext ='※ '.$fname.' : '.$inputtext;
	$DB->execute("UPDATE {abessi_indicators} SET nask=nask+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 학생질문 카운트

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==NULL || $fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('학생응답','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', mark=10, teacherid='$USER->id', context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
 
	$DB->execute("UPDATE {abessi_messages} SET status='ask', teacher_check=1, timemodified='$timecreated' WHERE userid='$userid' AND wboardid='$wboardid'  ORDER BY timecreated DESC LIMIT 1 ");
	} 



/*********** 선생님 답변부 시작 ************/
if($eventid==32) // 선생님 선택 전달하기
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	 
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext;

	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

// 선생님 피드백에 따른 ... 고도의 교수법 알고리즘을 적용한다. 사실상 .. 메뉴에.. 메뉴로 자동 응답하는 구조를 설계한다.
// 메타퍼블리싱... 대화기반 필기... 완료 후 meta-pubishing.. / 대화 + 필기 또는 입력 내용 방식으로 재배열한다.
// 대화기반.. 대화 후 다음 대화 전까지 필기 또는 입력 내용을 딥러닝으로 다음 대응 내용과 연결시킨다.
 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	 
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('선생님선택','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', mark=1,  teacherid='$USER->id',context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	$DB->execute("UPDATE {abessi_messages} SET  status='reply', mark=1,nstep=nstep+1, nfeedback=nfeedback+1,teacher_check=1, timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");

	if($feedbackid==5) // 발표평가 출제 set status = 'present'
		{
		$turn=0;
		$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
		$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
		if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
		else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
  		$DB->execute("UPDATE {abessi_messages} SET  teacher_check='1', mark=1,nstep=nstep+1, nfeedback=nfeedback+1,  turn=0, present=1,status='present' , timemodified='$timecreated' WHERE wboardid='$wboardid' AND userid='$userid' "); 
		}
	elseif($feedbackid==7)$DB->execute("UPDATE {abessi_messages} SET status='review', treview=3, teacher_check=2, timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'  ");
	elseif($feedbackid==8)$DB->execute("UPDATE {abessi_messages} SET aion=1, status='reply', timemodified='$timecreated' WHERE wboardid='$wboardid'  ");  // userid='$userid' AND 
	elseif($feedbackid==10)$DB->execute("UPDATE {abessi_messages} SET status='classroom', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'  ");

	} 

if($eventid==36) // 선생님 선택 전달하기 - 개념노트
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	 
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext;

	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

// 선생님 피드백에 따른 ... 고도의 교수법 알고리즘을 적용한다. 사실상 .. 메뉴에.. 메뉴로 자동 응답하는 구조를 설계한다.
// 메타퍼블리싱... 대화기반 필기... 완료 후 meta-pubishing.. / 대화 + 필기 또는 입력 내용 방식으로 재배열한다.
// 대화기반.. 대화 후 다음 대화 전까지 필기 또는 입력 내용을 딥러닝으로 다음 대응 내용과 연결시킨다.
 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	 
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('선생님선택','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET   step='$fbstep', ".$column."='$inputtext', mark=1,  teacherid='$USER->id',context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	$DB->execute("UPDATE {abessi_messages} SET mark=1,nstep=nstep+1, nfeedback=nfeedback+1,teacher_check='1', status='reply', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");

	/*
	if($feedbackid==5) // 발표평가 출제 set status = 'present'
		{
		$turn=0;
		$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
		$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
		if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
		else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
  		$DB->execute("UPDATE {abessi_messages} SET  teacher_check='1', mark=1,nstep=nstep+1, nfeedback=nfeedback+1,  turn=0, present=1,status='present' , timemodified='$timecreated' WHERE wboardid='$wboardid' AND userid='$userid' "); 
		}
	elseif($feedbackid==7)$DB->execute("UPDATE {abessi_messages} SET status='review', treview=3, teacher_check=2, timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'  ");
	elseif($feedbackid==8)$DB->execute("UPDATE {abessi_messages} SET aion=1, status='reply', timemodified='$timecreated' WHERE wboardid='$wboardid'  ");  // userid='$userid' AND 
	elseif($feedbackid==10)$DB->execute("UPDATE {abessi_messages} SET status='classroom', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'  ");
	*/

	} 

if($eventid==33) // 직접답변 + 화이트보드로 답변 ::: 선생님 직접 입력한 내용 전달 & 항목별로 연결된 wb에서 피드백 (topic & cognitive feedback), 보충화이트보드 연결  
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$radioinput= $_POST['radioinput'];
	$inputtext0=$inputtext; 
	
	$dateString = date("Y-m-d",$timecreated); 
	if($inputtext==NULL)$inputtext='';
	else $inputtext ='※ '.$fname.' : '.$inputtext;
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 학생질문 카운트
 	
 	$DB->execute("UPDATE {abessi_messages} SET  teacher_check=1, mark=1, emoji='$radioinput', userto='$USER->id', emotype=0, nstep=nstep+1, nfeedback=nfeedback+1,  turn=0, status='reply', timemodified='$timecreated' WHERE  wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	if($feedbackid==10)$contentsid0=NULL;	
	 
	if($contentsid0==NULL || strpos($wboardid, 'jnrsorksqcrark_user')!= false ) // 직접 답변
		{
		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cntcolumn='cnt'.$fbstep;	

		if($fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('피드백','$inputtext','1','1','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext',  teacherid='$USER->id',mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
		}
	elseif(strpos($wboardid, 'qstn')!= false)   //   해석피드백에 연결된 화이트보드에서 답장 
		{
		$contentsid=$contentsid0;
		$contentstype=$contentstype0;
		$nntopic = $_POST['nstep'];
		
		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid0' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cntcolumn='cnt'.$fbstep;	
		$wblink='type'.$contentstype.'cid'.$contentsid.'_qstn_hint'.$nntopic;


		// 화이트보드 첨부 기록
		$text='wb'.$fbstep;
		$wbname='wb'.$fbstep; 
 
		if($fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,context,url,userid,teacherid,contentsid,contentstype,timecreated ) VALUES('해석','$inputtext','$wblink','1','1','$contextid','$currenturl','$userid0','$tutorid','$contentsid0','$contentstype0','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wblink', teacherid='$USER->id', mark=1, ".$wbname."='1',  context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");	
		}
	elseif(strpos($wboardid, 'cognitive')!= false)   //  풀이피드백에 연결된 화이트보드에서 답장 
		{
		$contentsid=$contentsid0;
		$contentstype=$contentstype0;
		$nncog = $_POST['nstep'];

		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid0' AND contentsid='$contentsid' AND contentstype ='$contentstype' ORDER BY timecreated DESC LIMIT 1 ");
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cntcolumn='cnt'.$fbstep;	
		$wblink='type'.$contentstype.'cid'.$contentsid.'_cognitive_hint'.$nncog;

		// 화이트보드 첨부 기록
		$text='wb'.$fbstep;
		$wbname='wb'.$fbstep; 

		if($fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,context,url,userid,teacherid,contentsid,contentstype,timecreated ) VALUES('지시문','$inputtext','$wblink','1','1','$contextid','$currenturl','$userid0','$tutorid','$contentsid0','$contentstype0','$timecreated')");
		else  $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wblink',  teacherid='$USER->id',mark=1,  ".$wbname."='1',  context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");	
		}
	elseif(strpos($wboardid, 'nx4HQkXq')!= false)   //  다른사람의 풀이를 전달
		{

		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cntcolumn='cnt'.$fbstep;	
		 
		if($fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,context,url,userid,teacherid,contentsid,contentstype,timecreated ) VALUES('풀이참조','$inputtext','$wboardid','1','1','$contextid','$currenturl','$userid0','$tutorid','$contentsid0','$contentstype0','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET   step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wboardid',  teacherid='$USER->id',mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");	
		}
	elseif(strpos($wboardid, 'jnrsorksqcrark')!= false)  // 개념노트를 해당 화이트보드 상에서 전달
		{
		//$contentsid=$contentsid0;
		//$contentstype=$contentstype0;
		 
		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");
		$fbstep=$fb->step+1;
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cntcolumn='cnt'.$fbstep;	

		$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_cognitivetalk WHERE contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND cnt_ref LIKE '$contentsid0'  AND cnttype_ref LIKE '$contentstype0'     ");
		if($exist->id==NULL)
			{
			//선생님 환경에 노트연결
			$DB->execute("INSERT INTO {abessi_cognitivetalk} (eventid,userid,teacherid,instruction,active,wboardid,contentsid,contentstype,cnt_ref,cnttype_ref,timecreated) VALUES('$eventid','$userid','$tutorid','$instruction','1','$wboardid','$contentsid','$contentstype','$contentsid0','$contentstype0','$timecreated')  ");
			$DB->execute("UPDATE {abessi_messages} SET   appraise=6  WHERE wboardid='$wboardid'  ");  // 사용된 화이트보드로 등록
			}
		//학생에게 노트 전달
		if($fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,context,url,userid,teacherid,contentsid,contentstype,timecreated ) VALUES('개념','$inputtext','$wboardid','1','1','$contextid','$currenturl','$userid0','$tutorid','$contentsid0','$contentstype0','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wboardid',  teacherid='$USER->id',mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE userid='$userid0' AND contentsid='$contentsid0' AND contentstype ='$contentstype0' ORDER BY timecreated DESC LIMIT 1 ");	
		}
 
	// 선생님 활동 기록


	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
	} 
if($eventid==37) // 이모지 대화 초기버전. 이곳에 상황별 자동대화 알고리즘 적용 (인정, 실망 등.., 가속 부분등 적재 적소에 발송)
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$radioinput= $_POST['radioinput'];
	$inputtext0=$inputtext; 
	$radioinput=$radioinput+($feedbackid-1)*10;

	$dateString = date("Y-m-d",$timecreated); 
	if($inputtext==NULL)$inputtext='';
	else $inputtext ='※ '.$fname.' : '.$inputtext;

  	$DB->execute("UPDATE {abessi_messages} SET  teacher_check=1, nstep=nstep+1, nfeedback=nfeedback+1, mark=1, emoji='$radioinput', userto='$USER->id', emotype='$feedbackid',nstep=nstep+1, nfeedback=nfeedback+1,  turn=0, status='reply', timemodified='$timecreated' WHERE  wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	 
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('선생님선택','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET   step='$fbstep', ".$column."='$inputtext', mark=1,  teacherid='$USER->id',context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	// 선생님 활동 기록


	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
	} 
if($eventid==38) // cognitive talk, data input for machine learning
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$radioinput= $_POST['radioinput'];
	$inputtext0=$inputtext; 
	$radioinput=$radioinput+($feedbackid-1)*10;

	$dateString = date("Y-m-d",$timecreated); 
	if($inputtext==NULL)$inputtext='';
	else $inputtext ='※ '.$fname.' : '.$inputtext;

  	$DB->execute("UPDATE {abessi_messages} SET  teacher_check=1, nstep=nstep+1, nfeedback=nfeedback+1, mark=1, emoji='$radioinput', userto='$USER->id', emotype='$feedbackid',nstep=nstep+1, nfeedback=nfeedback+1,  turn=0, status='reply', timemodified='$timecreated' WHERE  wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	 
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('선생님선택','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET   step='$fbstep', ".$column."='$inputtext', mark=1,  teacherid='$USER->id',context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	// 선생님 활동 기록


	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
	} 

if($eventid==300) //  해석 피드백 답변 (버튼)
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$nntopic = $_POST['nstep'];
	$recommend = $_POST['recommend'];

	if($nntopic==1)$status='first';
	elseif($nntopic==2)$status='how';
	elseif($nntopic==3)$status='topics';
	elseif($nntopic>=4)$status='expand';

	if($inputtext==NULL)$inputtext='';
	else $inputtext ='※ '.$fname.' : '.$inputtext;
	$inputtext0=$inputtext; 
	$msglast=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid'  ORDER BY id DESC LIMIT 1 ");
	$boardid =$msglast->wboardid;  // 사용자의 화이트보드 아이디
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	
	$DB->execute("UPDATE {abessi_messages} SET status='$status',  sent1=1, teacher_check=1,  mark=1, timemodified='$timecreated' WHERE wboardid='$boardid' and userid='$userid' ORDER BY timemodified DESC LIMIT 1 ");
 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	 
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep; 
	$cntcolumn='cnt'.$fbstep;

	$wblink='type'.$contentstype.'cid'.$contentsid.'_qstn_hint'.$nntopic;

	// begin of whiteboard info
	$conn = new mysqli($servername, $username, $password, $dbname); 
	$sql = "SELECT * FROM boarddb where encryption_id='$wblink' ORDER BY id DESC LIMIT 1";
	 
	$result = mysqli_query($conn,$sql);
	$value= mysqli_fetch_array($result);
	$text='wb'.$fbstep;
	$$text=0;
	$wbname='wb'.$fbstep;
	if($value['generate_id']>5) $$text=1;
	$nwb=$$text;

	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,wb1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('길잡이','$inputtext','$wblink','$nwb','1','1','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wblink',  teacherid='$USER->id',mark=1, ".$wbname."='$nwb', context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	// 선생님 활동 기록
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");
	} 

if($eventid==400) //  풀이 피드백 답변 (버튼)
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$nncog = $_POST['nstep'];
	$recommend = $_POST['recommend'];

	$inputtext0=$inputtext; 
	$inputtext ='※ '.$fname.' : '.$inputtext;
 	$msglast=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid'  ORDER BY id DESC LIMIT 1 ");
	$boardid =$msglast->wboardid;  // 사용자의 화이트보드 아이디
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	$DB->execute("UPDATE {abessi_messages} SET sent2=1 WHERE wboardid='$boardid'  ");
	$DB->execute("UPDATE {abessi_messages} SET status='steps', mark=1, teacher_check=1, timemodified='$timecreated' WHERE wboardid='$boardid' and userid='$userid' ORDER BY timemodified DESC LIMIT 1 ");

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	 
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	$cntcolumn='cnt'.$fbstep;
 	 
	$wblink= 'type'.$contentstype.'cid'.$contentsid.'_cognitivesol'.$recommend.'_hint'.$nncog;
 	           
	// begin of whiteboard info
	$conn = new mysqli($servername, $username, $password, $dbname); 
	$sql = "SELECT * FROM boarddb where encryption_id='$wblink' ORDER BY id DESC LIMIT 1";
	 
	$result = mysqli_query($conn,$sql);
	$value= mysqli_fetch_array($result);
	$text='wb'.$fbstep;
	$$text=0;
	$wbname='wb'.$fbstep;
	if($value['generate_id']>5) $$text=1;
	$nwb=$$text;

              // end of whiteboard info

	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,wb1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('지시문','$inputtext','$wblink','$nwb','1','1','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wblink', teacherid='$USER->id', mark=1,  ".$wbname."='$nwb', context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

   	// 선생님 활동 기록
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");



} 
if($eventid==401) //  마인드맵 & 오답원인 의견 전달
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->firstname.$feedername->lastname;
	$recommend = $_POST['recommend'];
	$inputtext = $_POST['inputtext'];
	$nncog = $_POST['nstep'];
	$inputtext0=$inputtext; 
	$inputtext ='※ '.$fname.' : '.$inputtext;
 	$msglast=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid'  ORDER BY id DESC LIMIT 1 ");
	$boardid =$msglast->wboardid;  // 사용자의 화이트보드 아이디
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
	$DB->execute("UPDATE {abessi_messages} SET sent2=1 WHERE wboardid='$boardid'  ");
	$DB->execute("UPDATE {abessi_messages} SET status='analysis', mark=1, teacher_check=1, timemodified='$timecreated' WHERE wboardid='$boardid' and userid='$userid' ORDER BY timemodified DESC LIMIT 1 ");

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	 
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	$cntcolumn='cnt'.$fbstep;
	$wblink= 'type'.$contentstype.'cid'.$contentsid.'_cognitivesol'.$recommend.'_hint'.$nncog;
     	 
	// begin of whiteboard info
	$conn = new mysqli($servername, $username, $password, $dbname); 
	$sql = "SELECT * FROM boarddb where encryption_id='$wblink' ORDER BY id DESC LIMIT 1";
	 
	$result = mysqli_query($conn,$sql);
	$value= mysqli_fetch_array($result);
	$text='wb'.$nncog;
	$$text=0;
	$wbname='wb'.$fbstep;
	if($value['generate_id']>5) $$text=1;
	$nwb=$$text;
 
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('지시문','$inputtext','$wblink','1','1','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', ".$cntcolumn."='$wblink', teacherid='$USER->id', mark=1, ".$wbname."='$nwb',  context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

   	// 선생님 활동 기록
	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

} 
/*********** 선생님 답변부 끝 ************/

if($eventid==34) // 인지적 도제학습
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	if($inputtext==NULL)$inputtext='다음으로 해야 할 일을 떠오르는대로 하나만 표현해 보세요.';
	 
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext.' (•́ᴗ•́)و  ';  

	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('도제학습','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext',  teacherid='$USER->id', mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
	if($feedbackid==8)$DB->execute("UPDATE {abessi_messages} SET aion=1, timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'  ");
	$DB->execute("UPDATE {abessi_messages} SET mark=1,nstep=nstep+1, nfeedback=nfeedback+1,teacher_check='1', status='stepbystep', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");
	} 
if($eventid==35) // 동료학습 (peer learning) - 학생이 서술형 평가 채점하는 방식.
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	if($inputtext==NULL)$inputtext='다음으로 해야 할 일을 떠오르는대로 하나만 표현해 보세요.';
	 
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext.' ✦‿✦  ';  

	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

 
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('동료학습','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', teacherid='$USER->id', mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	

	$DB->execute("UPDATE {abessi_messages} SET mark=1,nstep=nstep+1, nfeedback=nfeedback+1,teacher_check='1', status='stepbystep', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");
	} 
if($eventid==4) // 오답원인 선택 후 평가 시작하기
	{
	$DB->execute("UPDATE {abessi_messages} SET active=0 WHERE contentsid='$contentsid' AND contentstype='$contentstype' AND wboardid NOT LIKE '$wboardid' ");
    	$DB->execute("UPDATE {abessi_messages} SET active=1,status='exam', timemodified='$timecreated' WHERE wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");

	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext ='※ '.$fname.' : '.$inputtext;

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('오답원인','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext',  teacherid='$USER->id', mark=10, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
	} 
if($eventid==200) // 채팅 요청 DB write
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];

	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트	 
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,mark,t_trigger) VALUES('chat','$userid','$tutorid','$chatid','$wboardid','1','$timecreated')");	
	}
if($eventid==201) // 새로고침 DB write
	{
	$chatid = $_POST['chatid'];
	$userid = $_POST['userid'];
	$tutorid = $_POST['tutorid'];
	$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트	 
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,chatid,wboardid,mark,t_trigger) VALUES('refresh','$userid','$tutorid','$chatid','$wboardid','1','$timecreated')");	
	$DB->execute("UPDATE {abessi_messages} SET emoji=0, emotype=0, status='reply', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");


	$wboard= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid' "); 
	if($wboard->id==NULL)$DB->execute("INSERT INTO {abessi_teacher} (userid,event,wboardid,timecreated) VALUES('$USER->id','화이트보드','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_teacher} SET status='업데이트', timecreated='$timecreated' WHERE wboardid='$wboardid'  ");

	}
 

if($eventid==301) // 복습예약 상태표시
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$inputtext0=$inputtext; 
	$dateString = date("Y-m-d",$timecreated); 
	$inputtext='※ '.$fname.' : '.$inputtext.'일 후 복습이 예약되었습니다. ('.$dateString.')';

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timemodified DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0 || $fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('복습예약','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', teacherid='$USER->id', mark=10, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid'   ");	

    	$DB->execute("UPDATE {abessi_messages} SET status='review', treview='$inputtext0', timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 "); //AND wboardid LIKE '%nx4HQkXq%' 
	} 
if($eventid==302) // 학습완료 상태표시
	{
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$depth= $_POST['depth'];
	$inputtext0=$inputtext; 
	if($inputtext0==NULL)$inputtext0='none';

	$dateString = date("Y-m-d",$timecreated); 
	$inputtext='※ 학습완료  :  '.$inputtext.'  ('.$dateString.')';
	// 개념노트 평점 업데이트
	if($contentstype==1)
		{
		// 현재 화이트보드 평점 업데이트
		if($pagenum!=1 || $pagenum==NULL) $DB->execute("UPDATE {abessi_messages} SET status='complete', depth='$depth', tremember='$inputtext0', pagenum='$pagenum', cmid='$cmid', timemodified='$timecreated' WHERE wboardid='$wboardid' ");  


		// 현재 주제 progress 업데이트
		$icnts=$DB->get_record_sql("SELECT max(pagenum) AS maxnum  FROM mdl_icontent_pages  WHERE  cmid='$cmid'  ");
		
		$getdepth1=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages  WHERE  userid='$userid' AND  cmid='$cmid' AND depth>1  ");
		$getdepthresult1 = json_decode(json_encode($getdepth1), True);
		$npass1=count($getdepthresult1);
		$getdepth2=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages  WHERE  userid='$userid' AND cmid='$cmid' AND depth>2  ");
		$getdepthresult2 = json_decode(json_encode($getdepth2), True);
		$npass2=count($getdepthresult2);

		$getstepNumber=$DB->get_record_sql("SELECT *  FROM mdl_abessi_cognitivesteps  WHERE contentstype=1 AND contentstid='$contentsid' ORDER BY id DESC LIMIT 1  ");
		
		if($getstepNumber->step1==NULL)$stepnum=0;
		elseif($getstepNumber->step2==NULL)$stepnum=1;
		elseif($getstepNumber->step3==NULL)$stepnum=2;
		elseif($getstepNumber->step4==NULL)$stepnum=3;
		elseif($getstepNumber->step5==NULL)$stepnum=4;
		elseif($getstepNumber->step6==NULL)$stepnum=5;
		elseif($getstepNumber->step7==NULL)$stepnum=6;
 		else $stepnum=7;
		$progress1=$npass1/($icnts->maxnum-1+$stepnum)*100;
		$progress2=$npass2/($icnts->maxnum-1+$stepnum)*100;   // progress1 (기본 진행) progress2 (심화 진행)
    		$DB->execute("UPDATE {abessi_messages} SET status='complete', star='$progress1',depth='$progress2',  timemodified='$timecreated' WHERE contentstype=1 AND userid='$userid' AND cmid='$cmid' AND pagenum=1 "); 
		}
	elseif($contentstype==2) $DB->execute("UPDATE {abessi_messages} SET status='complete', depth='$depth', tremember='$inputtext0', timemodified='$timecreated' WHERE wboardid='$wboardid' "); 

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0 || $fb->step==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,wboardid,contentstype,timecreated ) VALUES('학습완료','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$wboardid','$contentstype','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', teacherid='$USER->id', mark=10, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	                         
//$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,mark,context,url,userid,teacherid,contentsid,wboardid,contentstype,timecreated ) VALUES('학습완료','$inputtext','1','10','$contextid','$currenturl','$userid','$tutorid','$contentsid','$wboardid','$contentstype','$timecreated')");
	} 
if($eventid==303) // 자동 질문 팝업, 마지막 필기시간과 연동한 알고리즘.
	{
	$userid =$USER->id;
	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$fname=$feedername->lastname;
	$inputtext = $_POST['inputtext'];
	$stepid = $_POST['stepid'];
	$inputtext0=$inputtext; 
	$inputtext='※ '.$fname.' : '.$inputtext.'(질문)';
    	$DB->execute("UPDATE {abessi_messages} SET status='ask',  timemodified='$timecreated' WHERE wboardid='$wboardid'  ORDER BY timecreated DESC LIMIT 1 ");

	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");
	$fbstep=$fb->step+1;
	if($fbstep==11)$fbstep=10;
	$column='feedback'.$fbstep;
	if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,question,mark,userid,contentsid,contentstype,wboardid,timecreated ) VALUES('질문발송','$inputtext','1','$stepid','10','$userid','$contentsid','$contentstype','$wboardid','$timecreated')");
	else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext', teacherid='$USER->id', mark=1, context='$contextid',url='$currenturl',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
	} 

  
?>

