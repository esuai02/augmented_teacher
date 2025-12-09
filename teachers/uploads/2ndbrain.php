<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role==='student')
	{
	echo '접근권한이 없습니다.';
	exit();
	}
$teacherid=$USER->id;
$inputsrc= $_GET["inputsrc"]; 
if($inputsrc==1)
	{
	echo '';
	}
else
 	{
	$source= $_POST['source'];
	$userfrom= $_POST['userid'];
	}


$studentid= $_GET['userid'];

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;

$timecreated=time();

$wtimestart=$timecreated-86400*($nday+3);
$wtimestart2=$timecreated-86400*($nday+10);  // 주간목표 평가 시점
 
$minutes5=$timecreated-300;
$hoursago1=$timecreated-3600;
$hoursago2=$timecreated-7200;
$hoursago3=$timecreated-10800;

$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$monthsago2=$timecreated-604800*8; 

 
$today = $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  userid='$studentid'  AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");  // missionlog
 
		 // 10. 분기목표 설정
 
 			$checkgoal10= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND deadline > '$timecreated' AND  type LIKE '시험목표' ORDER BY id DESC LIMIT 1 ");
			if($checkgoal10->id==NULL)
				{
				$bsuserid= $studentid;
				$feedbackmode=10;
				$fbtype='분기목표 설정';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='분기목표를 설정하여 장기적인 학습의 흐름을 발생시킬 수 있습니다.';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$bsuserid;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='분기목표를 설정하여 장기적인 학습의 흐름을 발생시킬 수 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}			
				}
/* 
 
 			$checkgoal9= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated < '$wtimestart' AND timecreated > '$wtimestart2' AND type LIKE '주간목표'  ORDER BY id DESC LIMIT 1 ");
			if($checkgoal9->score==0 && $checkgoal9->score!=NULL)
				{
				$bsuserid= $studentid;
				$feedbackmode=9;
				$fbtype='Booster step 점검 및 평가하기';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='주간목표를 평가하고 주별로 연속적인 목표를 제시해 보세요';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$bsuserid.'&tb=604800';
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='지난 주 부스터 스텝에 대한 평점이 부과 되었습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
 
 */
		 // 8. 주간목표 설정
 
 			$checkgoal8= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$wtimestart' AND type LIKE '주간목표'  ORDER BY id DESC LIMIT 1 ");
			if($checkgoal8->id==NULL)
				{
				$bsuserid= $studentid;
				$feedbackmode=8;
				$fbtype='주간목표 설정';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='주간목표를 설정하여 오늘 활동의 몰입감을 높일 수 있습니다.';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$bsuserid;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='주간목표를 설정하여 활용하면 분기목표의 성공확률을 올릴 수 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
			 
		 // 7. 오늘목표 설정
 
			$missionlog = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
 			$checkgoal7= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$halfdayago' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");
			if($checkgoal7->id==NULL && $missionlog->timecreated >$halfdayago)
				{
				$bsuserid= $studentid;
				$feedbackmode=7;
				$fbtype='오늘목표 설정';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='오늘목표를 설정하여 활동의 흐름의 촉진시킬 수 있습니다.';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$bsuserid;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='오늘목표를 설정하여 활동의 흐름의 촉진시킬 수 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
		 
		// 6. 귀가검사  
 
			if($today->type==='검사요청')
				{
				$bsuserid= $studentid;
				$feedbackmode=6;
				$fbtype='귀가검사 확인';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='귀가검사를 통하여 미흡한 흐름을 촉진시키고 학습에 필요한 긴장감을 회복할 수 있습니다.';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$bsuserid.'&tb=604800';
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='선생님이 귀가검사 상태를 확인하고 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
			 
			
		// 5. 활동지연 (60초 컷)
 
			$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
			$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid' AND courseid NOT LIKE '239' AND component NOT LIKE 'core' AND  component NOT LIKE 'local_webhooks'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
			$engagement3 = $DB->get_record_sql("SELECT tlaststroke FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators  
			//$tlastaction=time()-max($engagement1->timecreated,$engagement3->tlaststroke);	
			$tlastaction=time()-max($engagement1->timecreated,$engagement2->timecreated,$engagement3->tlaststroke);	
			if($tlastaction>$tlastaction_prev && $tlastaction <1800 && $tlastaction > 60)
				{
				$bsuserid= $studentid;
				$feedbackmode=5;	
				$tlastaction_prev=$tlastaction;	
				$fbtype='활동지연 발견';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='활동이 주춤합니다. 온라인 피드백을 통하여 흐름을 유지시킬 수 있습니다.';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$bsuserid.'&mode=1';
				$DB->execute("UPDATE {abessi_indicators} SET tlaststroke='$timecreated' WHERE userid='$bsuserid'  ORDER BY id DESC LIMIT 1 "); // ### 이부분 사용해서 지각 점검 delay 등 사용 가능
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='잠시 후 선생님이 현재공부 내용에 대해 설명요청을 할 수 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
	 


		// 4. 온라인 질문
 
			$wbd=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND (status LIKE 'ask' OR status LIKE 'studentreply')  AND tlaststroke >'$halfdayago' ORDER BY tlaststroke DESC LIMIT 1");
			if($wbd->id!=NULL)
				{
				$bsuserid= $studentid;
				$feedbackmode=4;
				$fbtype='질문 답변하기';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='온라인 질문이 있습니다. 학생의 이해도를 예측한 다음 답변해 주세요';
				$fburl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wbd->wboardid;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='선생님이 온라인 질문을 확인하고 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
		 
		// 3. 평가가 필요한 학생
 
			$topicnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%jnrsorksqcrark_user%' AND (status LIKE 'begintopic' OR status LIKE 'complete' OR status LIKE 'review') AND timemodified >'$halfdayago'  ORDER BY timemodified ASC LIMIT 1");
			$questionnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%nx4HQkXq_user%' AND (status LIKE 'complete' OR status LIKE 'review') AND timemodified >'$halfdayago'  ORDER BY timemodified ASC LIMIT 1");
			if($topicnote->id!=NULL || $questionnote->id!=NULL) 
				{
				$bsuserid= $studentid;
				$feedbackmode=3;
				if($topicnote->id!=NULL)$bsurl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$topicnote->wboardid;
				elseif($questionnote->id!=NULL) $bsurl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$questionnote->wboardid;
				$fbtype='학습결과 평가';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='선생님이 완료된 오답노트에 대한 평가를 진행하고 있습니다.';
				$fburl=$bsurl;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='분기목표를 설정하여 장기적인 학습의 흐름을 발생시킬 수 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
		 
/*
		// 2. 최소간격 피드백 (30초 컷)------>필기 지연 기록... 숨은 질문으로 포착 !!!!!!!!!
 
			$engagement4 = $DB->get_record_sql("SELECT timemodified FROM  mdl_abessi_feedbacklog WHERE userid='$studentid'  ORDER BY timemodified DESC LIMIT 1 ");  // feedbacklog 
			$engagement5 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_instructionlog WHERE studentid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // instructionlog
	
			$tlastfeedback=time()-max($engagement4->timemodified,$engagement5->timecreated);	
			if($tlastfeedback>$tlastfeedback_prev && $tlastfeedback>3600 && $tlastfeedback<10800)
				{
				$bsuserid= $studentid;
				$feedbackmode=2;
				$tlastfeedback_prev=$tlastfeedback;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='선생님이 최근 진행된 공부내용들을 분석하고 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
			 
		// 1. 사전 피드백
 
			$gradedWrong=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND status LIKE 'begin' AND contentstype=2  AND timemodified >'$hoursago3'   ORDER BY tlaststroke ASC LIMIT 1");
			if($gradedWrong->id!=NULL)
				{
				$bsuserid= $studentid;
				$feedbackmode=1;			
				$bsurl='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$gradedWrong->wboardid;
				$fbtype='사전 피드백';
				$fbgoal='목적은 ... 입니다.';
				$fbtext='평가준비에 대한 사전 피드백으로 학습에 활력을 줄 수 있습니다.';
				$fburl=$bsurl;
				$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE type='7128' AND userid='$studentid' AND  timecreated>'$minutes10' ORDER BY id DESC LIMIT 1    ");
				if($exist->id==NULL)
					{
					$inputtext='선생님이 작성할 오답노트의 풀이노트를 점검하고 있습니다.';
					$currentpage=$DB->get_record_sql("SELECT max(id) AS id, currenturl FROM mdl_abessi_stayfocused WHERE  userid='$studentid'");
					$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,step,url,mark,userid,teacherid,timecreated ) VALUES('7128','$inputtext','1','$currentpage->currenturl','10','$studentid','$USER->id','$timecreated')");
					$DB->execute("UPDATE {abessi_indicators} SET nreply=nreply+1  WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");  // 선생님 응답 카운트
					}
				}
		 
*/
	 
 
if($bsuserid==0)
	{
	$bsuserid= $USER->id;
	$feedbackmode=0;
	$bsurl='https://mathking.kr/moodle/local/augmented_teacher/teachers/psclass.php?id='.$USER->id.'&tb=7&mode=today';
	//break;
	} 

if($feedbackmode==0) // 출결확인 (30분단위로), 상담추천, 교수법 업그레이드 (평점 기준으로 사용자 추천 후 교수법 컨텐츠 전달 후 감상평 받기)
	{
	$username='KTM';
	$fbtype='관심학생 관리';
	$fbgoal='목적은 ... 입니다.';
	$fbtext='평점이 낮은 학생들을 관리해 주세요';
	$fburl=$bsurl;
	}
 
$prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623709525001.png';
 
$thisuser=$DB->get_record_sql("SELECT id, lastname, firstname FROM mdl_user WHERE id LIKE '$bsuserid' ORDER BY id DESC LIMIT 1");  
$username=$thisuser->firstname.$thisuser->lastname;

$summary='오늘 활동 요약';
if($source==1)
	{
	$fbmsg=preg_replace("/\s+/", "", $username.'의'.$fbtype);
	$fburl=$fburl.'&bbbmsg='.$fbmsg;
	echo json_encode( array("username"=>$username,"fbtype"=>$fbtype,"fbgoal"=>$fbgoal,"fbtext"=>$fbtext,"fburl"=>$fburl,"prepareimg"=>$prepareimg,"summary"=>$summary) );
	}
else 
	{
	$fbmsg=preg_replace("/\s+/", "", $username.'의'.$fbtype);
	$fburl=$fburl.'&.....msg='.$fbmsg;
	header('Location:'.$fburl);
	}
 
?>