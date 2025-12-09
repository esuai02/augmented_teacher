<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$wbcreator = $_POST['wbcreator'];
$schid = $_POST['schid'];
$attemptid = $_POST['attemptid'];
$itemid = $_POST['itemid'];
$talkid = $_POST['talkid'];
$wboardid = $_POST['wboardid'];
$noteurl = $_POST['noteurl'];
$duration = $_POST['duration'];
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$questionid = $_POST['questionid'];
$pageid = $_POST['pageid'];
$cmid = $_POST['cmid'];
$checkimsi = $_POST['checkimsi'];
$inputtext = $_POST['inputtext'];
$value= $_POST['value'];
$type= $_POST['type'];


$missionid = $_POST['missionid'];
$logid = $_POST['logid'];
$timecreated=time();
$aweekago=$timecreated-604800;

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;

$goalid=$_POST['goalid'];
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($eventid==1) // 미션목록
$DB->execute("UPDATE {abessi_mission} SET complete='$checkimsi', checktime='$timecreated' WHERE userid='$userid' AND id='$missionid' ");  // mission list  
elseif($eventid==13) // 추천강좌 선택하기
	{
	if($checkimsi==1)$checkimsi=0;
	else $checkimsi=1;
	$DB->execute("UPDATE {abessi_mission} SET complete='$checkimsi', checktime='$timecreated' WHERE userid='$userid' AND id='$missionid' ");  // mission list  	
	}
elseif($eventid==7) // 선생님 화이트보드 제거
$DB->execute("UPDATE {abessi_messages} SET status='complete' WHERE wboardid='$wboardid' ");  // mission list  
 
elseif($eventid==2) // 오늘의 목표 숨기기
$DB->execute("UPDATE {abessi_today} SET hide='$checkimsi', timemodified='$timecreated' WHERE id='$goalid' ");  // mission list  

elseif($eventid==3) // 오늘목표 검사결과 기록
	{
	$DB->execute("UPDATE {abessi_today} SET inspect='$checkimsi', teacherid='$USER->id', result='$inputtext', timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  
	$halfdayago=time()-43200;
 
	$DB->execute("UPDATE {abessi_indicators} SET aion='$checkimsi' WHERE userid='$userid' AND timecreated > '$halfdayago' ORDER BY id DESC LIMIT 1 ");
	}
elseif($eventid==33) // 휴식시간 설정 해제
	{
	if($checkimsi==1)
		{
		// 휴식 시작
		$statusvalue=2;
		$tbegin = (int)$timecreated;  // 시작 시간
		
		// missionlog에 휴식 시작 기록 (하위 호환성)
		$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,eventid,text,timecreated) VALUES('$userid','beginbreak','7129','휴식시작','$timecreated')");
		
		// breaktimelog에 휴식 시작 레코드 생성 (tbegin만, tend는 NULL)
		try {
			$DB->execute("INSERT INTO {abessi_breaktimelog} (userid, tbegin, tend, duration, timecreated) VALUES('$userid', '$tbegin', NULL, NULL, '$timecreated')");
		} catch (Exception $e) {
			// 에러 로그 기록 (check.php:70)
			error_log("Error in check.php:70 - breaktimelog insert failed: " . $e->getMessage());
		}
		}
	else
		{
		// 휴식 종료
		$statusvalue=0;
		$tend = (int)$timecreated;  // 종료 시간

		// 진행 중인 휴식 레코드 조회 (tend가 NULL인 가장 최근 레코드)
		$activeBreak = $DB->get_record_sql(
			"SELECT id, tbegin FROM {abessi_breaktimelog}
			 WHERE userid='$userid' AND tend IS NULL
			 ORDER BY id DESC LIMIT 1"
		);

		// 휴식 시간 계산 및 기록 (check.php:85)
		if($activeBreak && $activeBreak->tbegin) {
			$tbegin = (int)$activeBreak->tbegin;
			$duration = $tend - $tbegin;  // 휴식 시간(초)
			$breaklog_id = $activeBreak->id;

			// 120초(2분) 이상인 경우만 업데이트 (check.php:98)
			if($duration >= 120) {
				try {
					// 진행 중인 레코드를 UPDATE하여 종료 시간과 duration 기록
					$updateSql = "UPDATE {abessi_breaktimelog} SET tend='$tend', duration='$duration', timecreated='$timecreated'";
					
					// 휴식 시간이 10분(600초) 이상이면 초과분을 timedelayed에 기록
					if($duration >= 600) {
						$timedelayed = $duration - 600;  // 초과분 계산 (초 단위)
						
						// 마지막 휴식 종료 시간 확인 (12시간 = 43200초)
						$lastBreakEnd = $DB->get_record_sql(
							"SELECT timecreated, timedelayed FROM {abessi_breaktimelog}
							 WHERE userid='$userid' AND tend IS NOT NULL
							 ORDER BY timecreated DESC LIMIT 1 OFFSET 1"
						);
						
						if($lastBreakEnd && $lastBreakEnd->timecreated) {
							$timeSinceLastBreak = $timecreated - $lastBreakEnd->timecreated;
							
							if($timeSinceLastBreak <= 43200) {
								// 12시간 이내: 기존 timedelayed 값에 플러스
								$existingDelayed = (int)($lastBreakEnd->timedelayed ?? 0);
								$timedelayed = $existingDelayed + $timedelayed;
							}
							// 12시간 이상: 초과분만 입력 (이미 계산된 $timedelayed 사용)
						}
						
						$updateSql .= ", timedelayed='$timedelayed'";
					}
					else {
						// 휴식 시간이 10분(600초) 이하인 경우
						// 마지막 휴식 종료 시간 확인 (12시간 = 43200초)
						$lastBreakEnd = $DB->get_record_sql(
							"SELECT timecreated, timedelayed FROM {abessi_breaktimelog}
							 WHERE userid='$userid' AND tend IS NOT NULL
							 ORDER BY timecreated DESC LIMIT 1 OFFSET 1"
						);
						
						if($lastBreakEnd && $lastBreakEnd->timecreated) {
							$timeSinceLastBreak = $timecreated - $lastBreakEnd->timecreated;
							
							if($timeSinceLastBreak <= 43200) {
								// 12시간 이내: 이전 마지막 휴식의 timedelayed 값을 복사
								$timedelayed = (int)($lastBreakEnd->timedelayed ?? 0);
								$updateSql .= ", timedelayed='$timedelayed'";
							}
							// 12시간 이상: timedelayed 기록하지 않음 (NULL)
						}
					}
					
					$updateSql .= " WHERE id='$breaklog_id'";
					$DB->execute($updateSql);
				} catch (Exception $e) {
					// 에러 로그 기록 (check.php:125)
					error_log("Error in check.php:125 - breaktimelog update failed: " . $e->getMessage());
				}
			}
			else {
				// 2분 미만인 경우 레코드 삭제
				try {
					$DB->execute("DELETE FROM {abessi_breaktimelog} WHERE id='$breaklog_id'");
				} catch (Exception $e) {
					error_log("Error in check.php:100 - breaktimelog delete failed: " . $e->getMessage());
				}
			}
		}

		// 기존 missionlog 기록 유지 (하위 호환성) (check.php:103)
		$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,eventid,text,timecreated) VALUES('$userid','finishbreak','7128','휴식종료','$timecreated')");
		}
	$DB->execute("UPDATE {abessi_today} SET inspect='$statusvalue', teacherid='$userid',timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list
	}
elseif($eventid==331) // 공부시간 중 휴식 해제 시, 종룟시간 미 업데이트
	{
	$statusvalue=0; 
	$DB->execute("UPDATE {abessi_today} SET inspect='$statusvalue', teacherid='$userid',timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  
	}
elseif($eventid==333) // 책공부
	{
	if($checkimsi==1 || $checkimsi==2) 
		{
		$statusvalue=3;
		$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$userid','beginbreak','책공부','$timecreated')");
		}
	else 
		{
		$statusvalue=0;
		$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$userid','finishbreak','책종료','$timecreated')");	
		}
	$DB->execute("UPDATE {abessi_today} SET inspect='$statusvalue', teacherid='$userid', timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  
	}
elseif($eventid==3333) // 오늘목표 검사결과 기록
	{
	if($checkimsi==1)
		{
		$statusvalue=0; 
		$gettext= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE  id='$goalid' ");
		if(strpos($gettext->text, '보충활동')!= true)$text=$gettext->text.'(<b>보충활동</b>)';
		$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$userid','continue','귀가보류','$timecreated')");
		}
	else $statusvalue=1; 
	$DB->execute("UPDATE {abessi_today} SET type='오늘목표', inspect='$statusvalue',text='$text', submit='$statusvalue', teacherid='$userid', timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  
	}
elseif($eventid==30) // 주간목표 검사결과 기록
$DB->execute("UPDATE {abessi_today} SET inspect='$checkimsi', teacherid='$USER->id', result='$inputtext', timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  
 
elseif($eventid==4) // 몰입이탈 조치, 상호작용하여 활동을 발생시키면 5분 이내의 활동이 발생하여 저절로 목록에서 사라짐, 모든 문제들을 화이트보드에서 풀도록 해야함
{
$checkimsi=$checkimsi+1;
$DB->execute("UPDATE {abessi_today} SET submit='$checkimsi',  timemodified='$timecreated' WHERE  id='$goalid' ");  // submit=2 이면 그날 학습 종료로 봄
}
elseif($eventid==5) // 초기점검 완료
$DB->execute("UPDATE {abessi_today} SET submit='1',  timemodified='$timecreated' WHERE  id='$goalid' ");  // mission list  

elseif($eventid==6) // 일정 수정 요청하기
$DB->execute("UPDATE {abessi_schedule} SET  editnew='$checkimsi' WHERE  id='$schid' ");  // mission list  
 
elseif($eventid==8) //화이트보드에서 서술평 출제 & 제출
	{
 	$DB->execute("UPDATE {abessi_messages} SET feedback='$checkimsi', timemodified='$timecreated' WHERE wboardid='$wboardid' ");  // mission list  
	// $checkimsi =1 이면 학생에게 출제, $checkimsi=2 이면 선생님에게 전달, $checkimsi=0이면 완료
	}
elseif($eventid==9) // 개념노트에서 개념공부 첫 출제. // $checkimsi =1 이면 학생에게 출제, $checkimsi=2 이면 선생님에게 전달, $checkimsi=0이면 완료
	{ 
	$contentsid=$pageid;
	$book= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid LIKE '$wboardid' ");
	if($book->id!=NULL) $DB->execute("UPDATE {abessi_messages} SET feedback='$checkimsi', timemodified='$timecreated' WHERE wboardid='$wboardid' ");  // mission list  
	else  
		{
		include("createdb.php");
		$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,feedback,status,contentstype,wboardid,contentstitle,cmid,contentsid,timemodified,timecreated) VALUES('$userid','2','$role','2','0','0','$checkimsi','complete','concept','$wboardid','$ctitle','$cmid','$pageid','$timecreated','$timecreated')");
		} 
	}
/*
elseif($eventid==10) //문제풀이 서술형 평가 출제.   $checkimsi =1 이면 학생에게 출제, $checkimsi=2 이면 선생님에게 전달, $checkimsi=0이면 완료
	{ 
	$contentsid=$questionid;
	 $qmessage= $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid LIKE '$wboardid' ");  
	 if($qmessage->id!=NULL) $DB->execute("UPDATE {abessi_messages} SET feedback='$checkimsi', timemodified='$timecreated' WHERE wboardid='$wboardid' ");   
	 else 
	 	{
		include("createdb_q.php");
		$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,feedback,status,contentstype,wboardid,contentsid,timemodified,timecreated) VALUES('$userid','2','$role','2','0','0','$checkimsi','complete','2','$wboardid','$questionid','$timecreated','$timecreated')");
		}	 
	}
*/
elseif($eventid==11 && $role!=='student') // 귀가평가 출제
	{
	$DB->execute("UPDATE {abessi_messages} SET student_check='$checkimsi',timemodified='$timecreated'  WHERE wboardid='$wboardid' ");  // mission list  
	if($checkimsi==1)$DB->execute("UPDATE {abessi_today} SET alerttime3='$timecreated'  WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");  
	else $DB->execute("UPDATE {abessi_today} SET alerttime3='$timecreated'  WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 	
	}
elseif($eventid==213 ) // 지면평가, 확인평가, 귀가평가 검사, 출제 dashboard && $role!=='student'
	{
	$indic= $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
	if($indic->aistep==7)
		{  
		$imr= $DB->get_record_sql("SELECT * FROM mdl_abessi_immersive WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
		$wb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid ='$wboardid'  ORDER BY id DESC LIMIT 1 "); 
		if(strpos($wboardid, 'jnrsorksqcrark')!==false)$type='topic';
		elseif(strpos($wboardid, 'quiz')!==false)$type='quiz';
		else $type='whiteboard';
		 
		if($wb->url!==NULL)$noteurl=$wb->url;
		for($ncnt=1;$ncnt<=12;$ncnt++)
			{
			$thistype='type'.$ncnt;
			$thisurl='url'.$ncnt;
			$thiswbid='wbid'.$ncnt;
			if($imr->$thistype==NULL)
				{
				$DB->execute("UPDATE {abessi_immersive} SET {$thistype}='$type',{$thiswbid}='$wboardid',{$thisurl}='$noteurl' WHERE userid='$USER->id' AND status LIKE 'begin' ORDER BY id DESC LIMIT 1 "); 
				exit();
				}
			}
		}
	else
		{
		$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
		if($checkimsi==1)
			{
			$DB->execute("UPDATE {abessi_messages} SET turn='0', student_check='1',active='1',feedback=feedback+1,timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 
			if($role==='student')
				{
				$ninactive=$goal->ninactive-1;
				if($ninactive<=0)$ninactive=0;
				$DB->execute("UPDATE {abessi_today} SET alerttime3='$timecreated',ninactive='$ninactive',assess=assess+1 WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");  	
				}
			else $DB->execute("UPDATE {abessi_today} SET alerttime3='$timecreated',assess=assess+1 WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");  	
			} 
		elseif($checkimsi==0)  // timereviewed='$timecreated' 출제 후 평가 시간
			{				
			$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1");
			if($role==='student')$DB->execute("UPDATE {abessi_messages} SET turn='0', student_check='0',timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 
			else $DB->execute("UPDATE {abessi_messages} SET turn='0', student_check='0',timereviewed='$timecreated' ,timemodified='$timecreated'  WHERE wboardid='$wboardid' "); 
			//else $DB->execute("UPDATE {abessi_messages} SET turn='0', student_check='0'  ,timemodified='$timecreated'  WHERE wboardid='$wboardid' "); //이라인 삭제?

			if($goal->type==='검사요청')$DB->execute("UPDATE {abessi_today} SET status='normal', inspect=1, alerttime4='$timecreated' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
			else $DB->execute("UPDATE {abessi_today} SET status='normal', inspect=0,alerttime4='$timecreated' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
			}
		}
	}
elseif($eventid==214) // 개념연습 출제 ==> 하나로 합치기, 이미 부분동작.
	{	
	$indic= $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
	if($indic->aistep==7)
		{
		$imr= $DB->get_record_sql("SELECT * FROM mdl_abessi_immersive WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
		$wb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid ='$wboardid'  ORDER BY id DESC LIMIT 1 "); 
		if(strpos($wboardid, 'jnrsorksqcrark')!==false)$type='topic';
		elseif(strpos($wboardid, 'quiz')!==false)$type='quiz';
		else $type='whiteboard';
			
		if($wb->url!==NULL)$noteurl=$wb->url;
		for($ncnt=1;$ncnt<=12;$ncnt++)
			{
			$thistype='type'.$ncnt;
			$thisurl='url'.$ncnt;
			$thiswbid='wbid'.$ncnt;
			if($imr->$thistype==NULL)
				{
				$DB->execute("UPDATE {abessi_immersive} SET {$thistype}='$type',{$thiswbid}='$wboardid',{$thisurl}='$noteurl' WHERE userid='$USER->id' AND status LIKE 'begin' ORDER BY id DESC LIMIT 1 "); 
				exit();
				}
			}	
		}
	else 
		{ 
		$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid ='$wboardid'  ORDER BY id DESC LIMIT 1 "); 
		if($exist->id==NULL) $DB->execute("INSERT INTO {abessi_messages} (userid,userto,status,student_check,active,feedback,contentstype,wboardid,contentstitle,contentsid,url,tlaststroke,timemodified,timecreated) VALUES('$userid','2','begintopic','1','1','1','1','$wboardid','topicnote','$contentsid','$noteurl','$timecreated','$timecreated','$timecreated')");	
		else $DB->execute("UPDATE {abessi_messages} SET student_check='1',feedback=feedback+1,timemodified='$timecreated',timecreated='$timecreated'  WHERE wboardid='$wboardid' ");  
	
		$DB->execute("UPDATE {abessi_today} assess=assess+1 WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
		}
	}
elseif($eventid==215  && $role!=='student') // 지시사항 입력
	{  
	$DB->execute("UPDATE {abessi_messages} SET instruction='$inputtext' WHERE wboardid='$wboardid' ");  
	echo json_encode( array("wbid"=>$wboardid) );
	}
elseif($eventid==216) // 퀴즈출제
	{ 
	$indic= $DB->get_record_sql("SELECT aistep FROM mdl_abessi_indicators WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
	if($indic->aistep==7)
		{
		$imr= $DB->get_record_sql("SELECT * FROM mdl_abessi_immersive WHERE userid='$USER->id' ORDER BY id DESC LIMIT 1 ");
		$wb=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid ='$wboardid'  ORDER BY id DESC LIMIT 1 "); 
		if(strpos($wboardid, 'jnrsorksqcrark')!==false)$type='topic';
		elseif(strpos($wboardid, 'quiz')!==false)$type='quiz';
		else $type='whiteboard';
			
		if($wb->url!==NULL)$noteurl=$wb->url;
		for($ncnt=1;$ncnt<=12;$ncnt++)
			{
			$thistype='type'.$ncnt;
			$thisurl='url'.$ncnt;
			$thiswbid='wbid'.$ncnt;
			if($imr->$thistype==NULL)
				{
				$DB->execute("UPDATE {abessi_immersive} SET {$thistype}='$type',{$thiswbid}='$wboardid',{$thisurl}='$noteurl' WHERE userid='$USER->id' AND status LIKE 'begin' ORDER BY id DESC LIMIT 1 "); 
				exit();
				}
			}
		}
	else
		{ 
		$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$contentsid'  ");
		$attemptlog=$DB->get_record_sql("SELECT id,quiz,attempt,sumgrades,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$userid' AND timemodified>'$aweekago' ORDER BY id DESC LIMIT 1 ");
		$quiz=$DB->get_record_sql("SELECT * FROM mdl_quiz where id='$moduleid->instance'  ");
		$contentstitle=$quiz->name;


		$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' AND timecreated>'$aweekago' ORDER BY timemodified DESC LIMIT 1"); 
		$url='id='.$contentsid;
		if($thisboard->id==NULL)$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,status,active,url,contentstype,contentstitle,wboardid,contentsid,student_check,timemodified,timecreated) VALUES('$userid','$USER->id','$role','commitquiz','1','$url','3','$contentstitle','$wboardid','$contentsid','1','$timecreated','$timecreated')");
		elseif($thisboard->timecreated<$timecreated-43200) 
			{
			$url='attempt='.$attemptlog->id.'&studentid='.$userid;
			$DB->execute("UPDATE {abessi_messages} SET status='commitquiz',url='$url',timemodified='$timecreated',timecreated='$timecreated', active='1' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");
			}
		else 
			{
			$url='attempt='.$attemptlog->id.'&studentid='.$userid;
			$DB->execute("UPDATE {abessi_messages} SET status='commitquiz',url='$url', timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");
			}
		}
	//$showthis=$timecreated.$wboardid.$contentsid; include("../showthis.php");
	}

elseif($eventid==111) $DB->execute("UPDATE {abessi_messages} SET hide='$checkimsi'  WHERE wboardid='$wboardid' ");  // mission list  
elseif($eventid==1111)
	{
	if($checkimsi==1)$DB->execute("UPDATE {quiz_attempts} SET review=3,  timemodified='$timecreated'  WHERE id='$attemptid' ");  // mission list  
	else $DB->execute("UPDATE {quiz_attempts} SET review=2,  timemodified='$timecreated'  WHERE id='$attemptid' ");  // mission list 
	} 
elseif($eventid==11111)
	{
	if($checkimsi==1)$DB->execute("UPDATE {quiz_attempts} SET review=2,  timemodified='$timecreated'  WHERE id='$attemptid' ");  // mission list  
	else $DB->execute("UPDATE {quiz_attempts} SET review=3,  timemodified='$timecreated'  WHERE id='$attemptid' ");  // mission list 
	} 
elseif($eventid==12) // 메니저 화면에서 체크박스 (선생님)
$DB->execute("UPDATE {abessi_messages} SET teacher_check='$checkimsi' WHERE wboardid='$wboardid' ");  // mission list  
// 활동설계 완료체크
elseif($eventid==91)$DB->execute("UPDATE {abessi_today} SET rcomplete='$checkimsi' WHERE  userid='$userid' ORDER BY id DESC LIMIT 1 "); // 복습완료
elseif($eventid==92)$DB->execute("UPDATE {abessi_today} SET ncomplete='$checkimsi' WHERE  userid='$userid' ORDER BY id DESC LIMIT 1 "); // 활동완료
elseif($eventid==93)$DB->execute("UPDATE {abessi_today} SET pcomplete='$checkimsi' WHERE  userid='$userid' ORDER BY id DESC LIMIT 1 "); // 발표완료

elseif($eventid==100) 
	{
	$wtimestart=$timecreated-86400*($nday+1);
	$wtimestart2=$timecreated-86400*($nday+8);  

	$DB->execute("UPDATE {abessi_today} SET planscore='$value', timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '주간목표' AND timecreated < '$wtimestart' AND timecreated > '$wtimestart2'  ORDER BY id DESC LIMIT 1 ");  // mission list  
 
	$feedbacktype='목표연결';
	$inputtext='<b>상위 단계 목표와의 연결상태가 업데이트 됨</b>';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','','','$userid','$USER->id','1','$timecreated','$timecreated')");
	}

elseif($eventid==101 && $checkimsi==1) //기본
	{
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='1' WHERE  userid='$userid'  AND type LIKE '기본'   ORDER BY id DESC LIMIT 1 ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '특강'   ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '임시'   ");  // mission list  	
	}
 
elseif($eventid==102 && $checkimsi==1) //특강
	{
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '기본'   ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='1' WHERE  userid='$userid'  AND type LIKE '특강'   ORDER BY id DESC LIMIT 1 ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '임시'   ");  // mission list  	
	}

elseif($eventid==103 && $checkimsi==1) //임시
	{
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '기본'  ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='0' WHERE  userid='$userid'  AND type LIKE '특강'  ");  // mission list  	
	$DB->execute("UPDATE {abessi_schedule} SET  pinned='1' WHERE  userid='$userid'  AND type LIKE '임시'   ORDER BY id DESC LIMIT 1 ");  // mission list  	
	} 
elseif($eventid==21) // 관심공유 today.php
{
$DB->execute("UPDATE {user} SET  lesson='$inputtext', state=1 WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");	
}
elseif($eventid==22) // 위험군 등록 today.php
{
$DB->execute("UPDATE {user} SET  lesson='$inputtext', state=2 WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");	
}
elseif($eventid==23) // 학부모 상담 공유하기 today.php ... 이부분은 today db 부분에 별도 입력
{
$DB->execute("UPDATE {user} SET  state=0 WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");	
}
elseif($eventid==24) // 예외로 설정. today.php ... 이부분은 today db 부분에 별도 입력
{
$DB->execute("UPDATE {user} SET  state=29 WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");	
}
/*
elseif($eventid==25) // 목표입력 숨김  1
{
$DB->execute("UPDATE {user} SET  hideinput='$checkimsi'  WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");	
}*/
elseif($eventid==26) // 가능성 점수 증감
	{
	$quizgrade = $_POST['quizgrade'];
	$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ");
	//$uniqueid=$attemptinfo->uniqueid;
	$maxgrade =$attemptinfo->maxgrade;

	$qnum=substr_count($attemptinfo->layout,',')+1-substr_count($attemptinfo->layout,',0'); 

	if($checkimsi==0)$addscore=-100/$qnum;
	else $addscore=100/$qnum;

	if($attemptinfo->maxgrade==NULL)$maxgrade=$quizgrade+$addscore;
	else $maxgrade=$maxgrade+$addscore;

	$DB->execute("UPDATE {question_attempts} SET checkflag='$checkimsi'  WHERE id='$questionid'  ");	
	$DB->execute("UPDATE {quiz_attempts} SET  maxgrade='$maxgrade' WHERE id='$attemptid' "); 

	}
elseif($eventid==27) // 가능성 점수 업데이트
	{
	$quizgrade = $_POST['quizgrade'];
	$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' "); 
	$maxgrade =$attemptinfo->maxgrade;
   	if($maxgrade==NULL)
		{
		$maxgrade=$quizgrade;
		$DB->execute("UPDATE {quiz_attempts} SET  maxgrade='$maxgrade' WHERE id='$attemptid' "); 
		}
	echo json_encode( array("maxgrade" =>$maxgrade) );
	}
elseif($eventid==150) $DB->execute("UPDATE {abessi_progress} SET complete='$checkimsi', checktime='$timecreated' WHERE id='$missionid'  ORDER BY id DESC LIMIT 1 ");  // 완료체크
elseif($eventid==200) $DB->execute("UPDATE {abessi_progress} SET hide='$checkimsi', checktime='$timecreated' WHERE id='$missionid'  ORDER BY id DESC LIMIT 1 ");  // 분기목표 숨기기

 

elseif($eventid==151) $DB->execute("UPDATE {abessi_attendance} SET complete='$checkimsi' WHERE id='$logid'  ORDER BY id DESC LIMIT 1 ");  // 완료체크
elseif($eventid==201)    // 숨기기
	{  
	if($checkimsi==0)  // 여러번 체크하는 경우 고려하도록 추후 변경
		{
		$DB->execute("UPDATE {abessi_attendance} SET hide='$checkimsi' WHERE id='$logid'  ORDER BY id DESC LIMIT 1 ");
		} 
	elseif($checkimsi==1)  // 여러번 체크하는 경우 고려하도록 추후 변경
		{
		$getid=$DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE id NOT LIKE '$logid' AND userid='$userid' AND hide=0 ORDER BY id DESC LIMIT 1");
		$lastid=$getid->id;
		$exitlog0=$DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  userid='$userid' AND hide=0 ORDER BY id DESC LIMIT 1"); //
		$tupdate=$exitlog0->tupdate;
	
		$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE id='$logid' ORDER BY id DESC LIMIT 1");
		$type=$exitlog->type; $selecttime=$exitlog->tamount;
	 
		if($type!=='수강료 변경' && $exitlog->reason !=='addperiod')$tupdate=$tupdate-$selecttime;
 
		$DB->execute("UPDATE {abessi_attendance} SET hide='$checkimsi' WHERE id='$logid'  ORDER BY id DESC LIMIT 1 ");
		$DB->execute("UPDATE {abessi_attendance} SET tupdate='$tupdate' WHERE id='$lastid'  ORDER BY id DESC LIMIT 1 ");
		} 
	}
elseif($eventid==250) $DB->execute("UPDATE {abessi_orchestration} SET hide='$checkimsi', timemodified='$timecreated' WHERE id='$missionid'  ORDER BY id DESC LIMIT 1 ");  // 삭제 ?
elseif($eventid==300) $DB->delete_records('quiz_attempts', ['id' =>$attemptid]);
elseif($eventid==301) 
	{ 
	$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' "); 
 	$timeupdated=$attemptinfo->timestart+$inputtext*60;
	if($attemptinfo->timeadded==NULL)$DB->execute("UPDATE {quiz_attempts} SET modified='addtime',addtime=addtime+'$inputtext', timeadded='$timecreated', timestart='$timeupdated' WHERE id='$attemptid' "); 
	else $DB->execute("UPDATE {quiz_attempts} SET modified='addtime',addtime=addtime+'$inputtext', timestart='$timeupdated' WHERE id='$attemptid' "); 
	//if($timeupdated<$timecreated)	$DB->execute("UPDATE {quiz_attempts} SET  timestart='$timeupdated' WHERE id='$attemptid' "); 
	//else $DB->execute("UPDATE {quiz_attempts} SET  timestart='$timecreated-1' WHERE id='$attemptid' "); 
	}
elseif($eventid==310) // 부스터 학습 출제 
	{  
	$item= $DB->get_record_sql("SELECT * FROM mdl_checklist_item WHERE id='$itemid' ORDER BY id DESC LIMIT 1");
	$displaytext=$item->displaytext;
	$query =urldecode($item->redirect); //, PHP_URL_QUERY);
	parse_str($query, $params);
	$contentsid = $params['pageid'];

	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
	$ctext=$getimg->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctext);
	$imageTags = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags as $imageTag)
		{
		$nimg++;
    		$imgSrc = $imageTag->getAttribute('src');
		$imgSrc = str_replace(' ', '%20', $imgSrc); 
		if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
		}	
	echo json_encode( array("Imgurl" =>$imgSrc) );

	if(strpos($displaytext,'단원')!==false) 
		{
		$url=strstr($item->linkurl, 'id=');  //before
		$contentstitle=$displaytext;
		$wboardid='quiz_'.$itemid.'_user'.$userid;
		}
	elseif(strpos($displaytext,'유형')!==false) 
		{
		$url=strstr($item->linkurl, 'id=');  //before
		$contentstitle=$displaytext;
		$wboardid='quiz_'.$itemid.'_user'.$userid;
		}
 	elseif(strpos($displaytext,'개념')!==false)  
		{
		//$url=$item->redirect;//jnrsorksqcrark1966_user2
		$url='jnrsorksqcrark'.$contentsid.'_user'.$userid;
		$wboardid='jnrsorksqcrark'.$contentsid.'_user'.$userid;
		$contentstitle=$displaytext;
		$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE contentsid LIKE '$contentsid' AND contentstype LIKE '1' ORDER BY timemodified DESC LIMIT 1"); 
		if($exist->url !=NULL)$url=$exist->url;
		}		 
 	else 
		{
		$url=$item->redirect;
		//$url=str_replace('jnrsorksqcrark','jnrsorksqcrark_user'.$userid,$url);   
		//$url=$url.'&studentid='.$userid;
		$url='jnrsorksqcrark'.$contentsid.'_user'.$userid;
		//if($item->redirect==NULL)$url=strstr($item->linkurl, 'id=');  //before
		$contentstitle=$displaytext;
		}		 

 
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY timemodified DESC LIMIT 1"); 
	 
	if($thisboard->id==NULL)$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,status,active,contentstype,contentstitle,wboardid,contentsid,url,timemodified,timecreated) VALUES('$userid','$USER->id','$role','commit','1','3','$contentstitle','$wboardid','$itemid','$url','$timecreated','$timecreated')");
	elseif($thisboard->timecreated<$timecreated-43200) $DB->execute("UPDATE {abessi_messages} SET status='commit', timemodified='$timecreated',timecreated='$timecreated', active='1' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");
	else $DB->execute("UPDATE {abessi_messages} SET status='commit', timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");
 	}
elseif($eventid==311) //실시간 지도 종료 (화면잠금화면에서 타임아웃 자동해제)
	{
	$beingsullivan=$eventid;  include("../whiteboard/debug.php");
	$timediff=time()-604800;
	$DB->execute("UPDATE {abessi_feedbacklog} SET forced=0 WHERE userid='$USER->id' AND timemodified > '$timediff' ");  // 강제모드 해제

	echo json_encode( array("eventid" =>$eventid) );
	}
/*
elseif($eventid==312) // 부스터 학습 출제 mdl_checklist_item  displaytext 개념도약,유형 (링크없음), 유형정복,  단원 마무리 테스트, linkurl, redirect (개념도약만)
	{  
	$item= $DB->get_record_sql("SELECT * FROM mdl_checklist_item WHERE id='$itemid' ORDER BY id DESC LIMIT 1");
	$displaytext=$item->displaytext;
 	//$parts = parse_url($item->redirect, PHP_URL_QUERY);
 	//parse_str($parts, $query);
  	//$contentsid=$query['pageid'];
	$query =urldecode($item->redirect); //, PHP_URL_QUERY);
	parse_str($query, $params);
	$contentsid = $params['pageid'];
 
	if(strpos($displaytext,'단원')!==false) 
		{
		$url=strstr($item->linkurl, 'id=');  //before
		$type='단원연습';
		}
	elseif(strpos($displaytext,'유형')!==false) 
		{
		$url=strstr($item->linkurl, 'id=');  //before
		$type='유형연습';
		}
 	elseif(strpos($displaytext,'개념')!==false)  
		{
 
		$url=$item->redirect;
		$url='cjnNote'.$url.'&srcid=pageid'.$contentsid.'jnrsorksqcrark_user'.$userid.'&studentid='.$userid;
		//if($item->redirect==NULL)$url=strstr($item->linkurl, 'id=');  //before
		$type='기억저장';
		}		 
 	else 
		{
 
		$url=$item->redirect;
		$url='cjnNote'.$url.'&srcid=pageid'.$contentsid.'jnrsorksqcrark_user'.$userid.'&studentid='.$userid;
		//if($item->redirect==NULL)$url=strstr($item->linkurl, 'id=');  //before
		$type='기억저장';
		}		 
	$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,sender,wboardid,mark,t_trigger) VALUES('refresh','$userid','$USER->id','$USER->id','$wboardid','1','$timecreated')");
	 
	$exist= $DB->get_record_sql("SELECT * FROM mdl_abessi_resurrection WHERE itemid='$itemid' AND userid='$userid' ORDER BY id DESC LIMIT 1");
	if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_resurrection} (type,title,userid,teacherid,itemid,contentsid,contentstype,nretry,status,url,active,timemodified,timecreated) VALUES('$type','$displaytext','$userid','$USER->id','$itemid','$contentsid','$contentstype','1','begin','$url','$checkimsi','$timecreated','$timecreated')");
	else $DB->execute("UPDATE {abessi_resurrection} SET active='$checkimsi',status='begin' ,nretry=nretry+1, timemodified='$timecreated' WHERE id='$exist->id'  ORDER BY id DESC LIMIT 1 ");
	$DB->execute("UPDATE {abessi_today} SET drilling=1  WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");

	$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
	$fname=$feedername->lastname;
 	$inputtext=$type.'('.$displaytext.')';
	$inputtext ='※ '.$fname.' : '.$inputtext;
	$cnturl=$url;

	$exist2=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1    ");
	if($exist2->id==NULL)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('기억전달','$inputtext','$cnturl','1','1','$userid','$USER->id','$contentsid','$contentstype','$wboardid','$timecreated')");
	else 
		{
		$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");	
		$fbstep=$fb->step+1;
		
		if($fbstep==11)$fbstep=10;
		$column='feedback'.$fbstep;
		$cnt='cnt'.$fbstep;
		if($fb->step==0)$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,cnt1,step,mark,userid,teacherid,contentsid,contentstype,wboardid,timecreated ) VALUES('기억전달','$inputtext','$cnturl','1','1','$userid','$USER->id','$contentsid','$contentstype','$wboardid','$timecreated')");
		else $DB->execute("UPDATE {abessi_feedbacklog} SET  step='$fbstep', ".$column."='$inputtext',".$cnt."='$cnturl', mark=1, teacherid='$USER->id',timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY timecreated DESC LIMIT 1 ");	
 		}
	// TAG 발생 시키기.  userid  3    timemodified

 
	$tagname='topic'.$itemid;
	if($contentstype==1)
		{
		$component='core';
		$itemtype='topic'; 
		$tagitemid=$cmid;
		$contextid='196124';
		}
	else			    			 
		{
		$component='core_question';
		$itemtype='question';
		$tagitemid=$contentsid;
		$contextid='196124';
		}

	$checkexist=$DB->get_record_sql("SELECT *  FROM mdl_tag WHERE name LIKE '$tagname'  ORDER BY id DESC LIMIT 1 ");
	if($checkexist->id==NULL || $checkexist->displayname==NULL)$DB->execute("INSERT INTO {tag} (userid,tagcollid,name,rawname,displayname,isstandard,description,timemodified) VALUES('$USER->id','3','$tagname','$tagname','$displaytext','0','auto','$timecreated')");
	else 
		{
		$checkexist2=$DB->get_record_sql("SELECT *  FROM mdl_tag_instance WHERE tagid LIKE '$checkexist->id' AND itemid LIKE '$tagitemid'  AND itemtype LIKE '$itemtype' ORDER BY id DESC LIMIT 1 ");
		if($checkexist2->id==NULL)$DB->execute("INSERT INTO {tag_instance} (tagid,userid,component,itemtype,contextid,itemid,tiuserid,timemodified,timecreated ) VALUES('$checkexist->id','$USER->id','$component','$itemtype','$contextid','$tagitemid','0','$timecreated','$timecreated')");
 		else $DB->execute("UPDATE {tag_instance} SET  lastuser='$USER->id',naccess=naccess+1,timemodified='$timecreated' WHERE id LIKE '$checkexist2->id'  ORDER BY id DESC LIMIT 1 ");	
		}
    	$DB->execute("UPDATE {abessi_messages} SET tracking=1, timemodified='$timecreated' WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1 ");  
 	}*/
 elseif($eventid==313)  // 오늘활동 페이지.. 빠른 질문-답변 // bessiboard check_status eventid=21과 연결
	{ 
	$gettime=$DB->get_record_sql("SELECT *  FROM mdl_abessi_today WHERE id='$goalid' ");
	if($gettime->alerttime==0)$timestamp=$timecreated;
	else $timestamp=0;
	
	if($timestamp==0)
		{
		$askstatus='complete';
		$ninspect=0;
		}
	else 
		{
		$askstatus='ask';
		$ninspect=3;
		}
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'  ORDER BY tlaststroke DESC LIMIT 1"); 
	$wboardid=$thisboard->wboardid;
	$DB->execute("UPDATE {abessi_today} SET asktype='todaypage',inspect='$ninspect',askstatus='$askstatus',wboardid='$wboardid', alerttime='$timestamp'  WHERE  id='$goalid' ");
	}
elseif($eventid==314)  // 도움요청
	{ 
	$gettime=$DB->get_record_sql("SELECT *  FROM mdl_abessi_today WHERE id='$goalid' ");
	if($gettime->alerttime2==0)$timestamp=$timecreated;
	else $timestamp=0;
	
	if($timestamp==0)
		{
		$askstatus='complete';
		$ninspect=0;
		}
	else 
		{
		$askstatus='ask';
		$ninspect=3;
		}
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'  ORDER BY tlaststroke DESC LIMIT 1"); 
	$wboardid=$thisboard->wboardid;
	$DB->execute("UPDATE {abessi_today} SET asktype='todaypage',inspect='$ninspect',askstatus2='$askstatus',wboardid='$wboardid', alerttime2='$timestamp'  WHERE  id='$goalid' ");
	}
elseif($eventid==40)  // 화이트보드 상에서 몰입피드백 내용입력
	{  
	$talkid=7;
	$type = $_POST['type'];
	$checkid = $_POST['checkid'];
	if($type==NULL)$type='comment';
	$bessi=$wboardid.$inputtext.$userid.$timecreated; include("../whiteboard/debug.php");
	$DB->execute("INSERT INTO {abessi_cognitivetalk} (wboardid,creator,talkid,userid,type,checkid,hide,text,timemodified,timecreated ) VALUES('$wboardid','$wbcreator','$talkid','$userid','$type','$checkid','0','$inputtext','$timecreated','$timecreated')");
	echo json_encode( array("talkid" =>$talkid) );
	}
elseif($eventid==41) // 몰입피드백에서 내용입력
	{  
	$talkid=77;
	$type = $_POST['type'];
	$checkid = $_POST['checkid'];
	if($checkid==NULL)$checkid = 0;
	if($type==NULL)$type='comment';
	$bessi=$wboardid.$inputtext.$userid.$timecreated; include("../whiteboard/debug.php");
	$DB->execute("INSERT INTO {abessi_cognitivetalk} (wboardid,creator,talkid,userid,type,checkid,hide,text,timemodified,timecreated ) VALUES('$wboardid','$wbcreator','$talkid','$userid','$type','$checkid','0','$inputtext','$timecreated','$timecreated')");
	echo json_encode( array("talkid" =>$talkid) );
	}
elseif($eventid==42) // 피드백 추적하기
	{  
	$fbid = $_POST['fbid'];
	$checkid = $_POST['checkid'];
	if($checkid==100) // 표현 추가
		{
		$DB->execute("UPDATE {abessi_cognitivetalk} SET standard=1  WHERE id='$fbid' ORDER BY id DESC LIMIT 1");   
		}
	elseif($checkid==200) // 일정 추가
		{
		$DB->execute("UPDATE {abessi_cognitivetalk} SET pinned='$checkimsi', timemodified='$timecreated' WHERE id='$fbid' ORDER BY id DESC LIMIT 1");    
		} //pinned='$checkimsi'
	elseif($checkid>=1 && $checkid<=5)
		{
	 	$DB->execute("UPDATE {abessi_cognitivetalk} SET checkid='$checkid' WHERE id='$fbid' ORDER BY id DESC LIMIT 1");   
		}
	elseif($checkimsi==1)
		{
	 	$DB->execute("UPDATE {abessi_cognitivetalk} SET checkid='$checkid' , pinned='$checkimsi' WHERE id='$fbid' ORDER BY id DESC LIMIT 1");   
		}
	elseif($checkimsi==0)
		{
	 	$DB->execute("UPDATE {abessi_cognitivetalk} SET pinned='$checkimsi' WHERE id='$fbid' ORDER BY id DESC LIMIT 1");   
		}
	echo json_encode( array("fbid2" =>$fbid) );
	}
elseif($eventid==43) // 숨기기, 보이기
	{  
	$fbid = $_POST['fbid'];
	$DB->execute("UPDATE {abessi_cognitivetalk} SET hide='$checkimsi' WHERE id='$fbid' ORDER BY id DESC LIMIT 1");   
	echo json_encode( array("fbid2" =>$fbid) );
	}
elseif($eventid==44) // 좋아요
	{  
	$item= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE id='$talkid' ORDER BY id DESC LIMIT 1");
	$exist= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE srcid='$talkid' AND userid='$USER->id' ORDER BY id DESC LIMIT 1");
	if($exist->id==NULL)
		{
		$DB->execute("INSERT INTO {abessi_cognitivetalk} (srcid,wboardid,creator,talkid,userid,type,standard,checkid,hide,text,timemodified,timecreated ) VALUES('$item->id','$item->wboardid','$USER->id','$item->talkid','$USER->id','$item->type','1','$item->checkid','0','$item->text','$timecreated','$timecreated')");
		$DB->execute("UPDATE {abessi_cognitivetalk} SET srcid=1  WHERE id='$talkid' ORDER BY id DESC LIMIT 1");  // 표현 원본 보존
		}
	else $DB->execute("UPDATE {abessi_cognitivetalk} SET standard=1  WHERE id='$exist->id' AND userid='$USER->id' ORDER BY id DESC LIMIT 1");  
	echo json_encode( array("talkid2" =>$talkid) );
	}
elseif($eventid==45) // 선생님 변경
	{   
	$exist= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$userid'  ORDER BY id DESC LIMIT 1");
	$name=$exist->firstname;
    $teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$USER->id' AND fieldid='79' "); 
	$newSymbol=$teacher->symbol;
    // 첫 글자 가져오기
    $firstChar = mb_substr($name, 0, 1, "UTF-8");
    
    // 첫 글자가 특수문자인지 확인
    if (preg_match('/[^\p{L}\p{N}]/u', $firstChar)) { 
        // 특수문자일 경우 새로운 기호로 교체
        $name = $newSymbol . mb_substr($name, 1, null, "UTF-8");
    } else {
        // 특수문자가 아니고 한글일 경우, 앞에 새로운 기호 추가
        $name = $newSymbol . $name;
    }
 
	$DB->execute("UPDATE {user} SET firstname='$name',teacherid='$USER->id'  WHERE id='$userid'   ORDER BY id DESC LIMIT 1");  
	}
elseif($eventid==46) // 선생님 변경
	{   
	$inputtext='주간복습 : '.$inputtext; 	 
	$wboardid='user'.$USER->id.'weeklyreview'.$timecreated;
	$duration=$timecreated+$duration*60;
	$DB->execute("INSERT INTO {abessi_tracking} (userid,type,teacherid,status,wboardid,duration,text,timecreated) VALUES('$userid','weeklyreview','$USER->id','weeklyreview','$wboardid','$duration','$inputtext','$timecreated')"); 	 
	echo json_encode( array("usrid"=>$USER->id) );
	}
?>

