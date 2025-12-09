<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$eventid = $_POST['eventid']; 
$userid = $_POST['userid'];
$tutorid = $_POST['tutorid'];
$mtid=$_POST['mtid'];
$cid=$_POST['cid'];
$feedbackid=$_POST['feedbackid'];
$inputvalue=$_POST['inputvalue'];
$inputtext=$_POST['inputtext'];
$timecreated=time();
$halfdayago= $timecreated-43200;
/*
if($eventid==11) 
	{ 
	$DB->execute("UPDATE {abessi_today} SET  amountr='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid' AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");  //복습시간 입력
	} 
elseif($eventid==12)  
	{ 
	$DB->execute("UPDATE {abessi_today} SET  amountn='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'   AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");  //나아가기 시간 입력
	}  
elseif($eventid==13)  
	{ 
	$DB->execute("UPDATE {abessi_today} SET  amountp='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1 ");  //발표활동 시간 입력
	}   

if($eventid==21) 
	{ 
	$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	if($role=='student'  || $checkgoal->rtext1==NULL )
		{
		$inputvalue=' 계획 : '.$inputvalue;
		$DB->execute("UPDATE {abessi_today} SET  rtext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");  //복습활동 계획 입력
		}
	else 
		{
		$inputvalue=$checkgoal->rtext1.'보충 : '.$inputvalue;
		$DB->execute("UPDATE {abessi_today} SET  rtext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1 ");  
		}
	} 
 */
if($eventid==31)  // 공부법 업그레이드
	{ 
	$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	if($role=='student'  || $checkgoal->ntext1==NULL )
		{
		$inputvalue=''.$inputtext;
		$DB->execute("UPDATE {abessi_today} SET  ntext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1 ");  
		} 
	else 
		{
		$inputvalue=$checkgoal->ntext1.'보충 : '.$inputtext;
		$DB->execute("UPDATE {abessi_today} SET  ntext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");  
		}	
	} 

if($eventid==32) 
	{ 
	$inputtext1=$_POST['inputtext1'];
	$inputtext2=$_POST['inputtext2'];
	$inputtext3=$_POST['inputtext3'];
	$inputtext4=$_POST['inputtext4'];
	$inputtext5=$_POST['inputtext5'];
 	$inputtext6=$_POST['inputtext6'];  // 알림 간격 (분)
	$DB->execute("UPDATE {abessi_today} SET step1='$inputtext1',step2='$inputtext2',step3='$inputtext3',step4='$inputtext4',step5='$inputtext5',alerttime='$inputtext6',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1 ");  
	} 
if($eventid==40)  // 성공확률 예측
	{ 

	$probability=$DB->get_record_sql("SELECT * FROM  mdl_abessi_forecast WHERE userid='$userid' AND timecreated > '$halfdayago'  ORDER BY id DESC LIMIT 1 ");
	$np=1;	

	$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) AND timecreated > '$halfdayago'  ORDER BY id DESC LIMIT 1");
	$tgoal=$checkgoal->timecreated;
	
	//if($checkgoal->id==NULL || $role==='student' )exit();  //|| $timecreated-$probability->timemodified<590
	 
	$pvalue=$inputvalue;
	$tvalue=$timecreated;

	if($probability->id==NULL) 
		{
		$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
		$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
		$schedule=$DB->get_record_sql("SELECT id,editnew, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	 
		if($nday==1){$tstart=$schedule->start1; $hours=$schedule->duration1;} 
		if($nday==2){$tstart=$schedule->start2; $hours=$schedule->duration2;} 
		if($nday==3){$tstart=$schedule->start3; $hours=$schedule->duration3;} 
		if($nday==4){$tstart=$schedule->start4; $hours=$schedule->duration4;} 
		if($nday==5){$tstart=$schedule->start5; $hours=$schedule->duration5;} 
		if($nday==6){$tstart=$schedule->start6; $hours=$schedule->duration6;} 
		if($nday==7){$tstart=$schedule->start7; $hours=$schedule->duration7;} 
 
		$tcomplete0=$tgoal+$hours*3600;
		$tcomplete=date("h:i A", $tcomplete0);
		$timestart=date("m월d일 h:i A", $tgoal);
		$totaltime=$hours*3600;

		$progresstext='총 '.$hours.'시간 | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';
		$DB->execute("INSERT INTO {abessi_forecast} (userid,teacherid,totaltime,tbegin,text,prob1,time1,timemodified,timecreated ) VALUES('$userid','$USER->id','$totaltime','$tgoal','$progresstext','$pvalue','$tvalue','$timecreated','$timecreated')");
		}
	else
		{
		$hours=$probability->totaltime/3600;
		$progresstext=$probability->text;
		if($probability->prob2==NULL){$np=2;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob3==NULL){$np=3;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob4==NULL){$np=4;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob5==NULL){$np=5;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob6==NULL){$np=6;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob7==NULL){$np=7;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob8==NULL){$np=8;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob9==NULL){$np=9;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}
		elseif($probability->prob10==NULL){$np=10;$progresstext.=' | '.$pvalue.'%/'.round(($timecreated-$tgoal)/3600,1).'H';}

		$pstr='prob'.$np;
		$tstr='time'.$np;
		$DB->execute("UPDATE {abessi_forecast} SET  ".$pstr."='$pvalue',".$tstr."='$tvalue',text='$progresstext', timemodified='$timecreated' WHERE  userid='$userid'   ORDER BY id DESC LIMIT 1 ");
		}
	}
/*
if($eventid==41) 
	{ 
	$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	if($role=='student' || $checkgoal->ptext1==NULL )
		{
		$inputvalue=' 계획 : '.$inputvalue;
		$DB->execute("UPDATE {abessi_today} SET  ptext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1 ");  //발표하기
		}
	else 
		{
		$inputvalue=$checkgoal->ptext1.'보충 : '.$inputvalue;
		$DB->execute("UPDATE {abessi_today} SET  ptext1='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1 ");  
		}
	}
*/
if($eventid==51) 
	{ 
	$adayAgo=time()-86400;
	$DB->execute("UPDATE {abessi_today} SET  result='$inputvalue',  timemodified='$timecreated' WHERE  userid='$userid' AND type LIKE '주간목표' AND timecreated < '$adayAgo' ORDER BY id DESC LIMIT 1 ");  //학생이 주간 평가 입력

	// 변경사항 기로
	$feedbacktype='변경사항';
	$inputtext='최근에 주간성찰을 입력하였습니다.';
	$contextid='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
	$currenturl='id='.$userid.'&tb=604800';
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback1,context,url,userid,teacherid,mark,timemodified,timecreated ) VALUES('$feedbacktype','$inputtext','$contextid','$currenturl','$userid','$USER->id','1','$timecreated','$timecreated')");
	}
if($eventid==60) // 즉석 보강시간 추가
	{ 
	$DB->execute("UPDATE {abessi_today} SET penalty=penalty+'$inputvalue' WHERE  userid='$userid'  AND type LIKE '주간목표'  ORDER BY id DESC LIMIT 1 ");  //발표하기
	$inputvalue=round($inputvalue/60,1);
	if($inputvalue>=0)
		{
		$type = '보강';
		$reason = '당일보강';
		}
	else	
		{
		$type = '휴강';
		$reason = '부분휴강';
		}
	$selecttime = $inputvalue;
	$timestamp1 =$timecreated;
	$timestamp2 =$timecreated;

	//$beingsullivan=$timestamp2.' | '.$type.$reason.$timecreated;  include("../whiteboard/debug.php");
	$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 ORDER BY id DESC LIMIT 1");
	$tupdate=$exitlog->tupdate;
	$tupdate=$tupdate+$selecttime; 
	$DB->execute("INSERT INTO {abessi_attendance} (userid,teacherid,type,reason,text,tamount,tupdate,doriginal,dchanged,complete,hide,timecreated) VALUES('$userid','$USER->id','$type','$reason','당일','$selecttime','$tupdate','$timestamp1','$timestamp2','0','0','$timecreated')"); 
 	}
?>

