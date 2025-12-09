<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$eventid = $_POST['eventid'];

$msntype = $_POST['msntype'];
$idcreated = $_POST['idcreated'];  
$subject = $_POST['subject'];  
$chstart = $_POST['chstart'];   
$startdate = $_POST['startdate'];  
$date=$_POST['date']; 
$grade = $_POST['grade'];
$hours = $_POST['hours'];
$weekhours = $_POST['weekhours'];
$inputtext = $_POST['inputtext'];
$contentsid = $_POST['contentsid'];
$mindset = $_POST['mindset'];
$deadline = $_POST['deadline'];
$userid = $_POST['userid'];
$timecreated=time();

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($eventid==1) // use this for a user defined mission
{
$DB->execute("INSERT INTO {abessi_mission} (userid,msntype,text,deadline,complete,timecreated) VALUES('$userid','8','$inputtext','$deadline','0','$timecreated')");
}

if($eventid==11 || $eventid==12 || $eventid==13 || $eventid==14|| $eventid==15|| $eventid==16|| $eventid==17) 
	{
	$dday1= $_POST['dday1'];
	$dday2= $_POST['dday2'];
	$dday3= $_POST['dday3'];
	$dday4= $_POST['dday4'];
	$dday5= $_POST['dday5'];
	$dday6= $_POST['dday6'];
	$dday7= $_POST['dday7'];
	$dday8= $_POST['dday8'];
	$dday9= $_POST['dday9'];
	$dday10= $_POST['dday10'];
	 
	$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject='$subject' ORDER BY id DESC LIMIT 1");
	if($eventid==11) //input mission & insert row for initial curriculum settting
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		if($msntype==4) $DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,startdate,userid,deadline,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$weekhours','$startdate','$userid','$deadline','0','$timecreated')");
		else $DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,chstart,weekhours,startdate,userid,deadline,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$hours','$chstart','$weekhours','$startdate','$userid','$deadline','0','$timecreated')");
		}
	 
	if($eventid==12) // 개념미션, 심화미션을 위한 일정 자동생성
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];

		$Date =$mission->startdate;
		$hours=$mission->hours;
		$weekhours2=$mission->weekhours;
		$chstart=$mission->chstart;
		$totalch=$DB->get_record_sql("SELECT nch FROM mdl_abessi_curriculum WHERE id='$subject' ");
		$chend=$totalch->nch;
		if($weekhours2==0 ||$weekhours2==NULL)$ndays=0;
		else $ndays=$hours/($weekhours2) *7;
		$ndays=$hours/($weekhours2) *7;	 
		for($num=$chstart;$num<=$chend;$num++)
		{	
		$dayplus=(int)($ndays*($num-$chstart+1));
		$daynum='dday'.$num;
		$$daynum=date('Y-m-d', strtotime($Date. ' + '.$dayplus.' days'));	
		if($num==$chend)$deadline=$$daynum;
		}
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("UPDATE {abessi_mission} SET  timemodified='$timecreated', deadline='$deadline', dday1='$dday1', dday2='$dday2', dday3='$dday3', dday4='$dday4', dday5='$dday5', dday6='$dday6', dday7='$dday7', dday8='$dday8', dday9='$dday9', dday10='$dday10', dday11='$dday11', dday12='$dday12', dday13='$dday13', dday14='$dday14', dday15='$dday15' WHERE complete='0' AND userid='$userid' AND subject='$subject' ORDER BY id DESC LIMIT 1  ");  
		}
		 
	if($eventid==13) // 개념미션, 심화미션 단원별 데드라인 저장하기
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];

		$nchange=$mission->nchange+1;
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("UPDATE {abessi_mission} SET deadline='$deadline', timemodified='$timecreated', nchange='$nchange', dday1='$dday1', dday2='$dday2', dday3='$dday3', dday4='$dday4', dday5='$dday5', dday6='$dday6', dday7='$dday7', dday8='$dday8', dday9='$dday9', dday10='$dday10', dday11='$dday11', dday12='$dday12', dday13='$dday13', dday14='$dday14', dday15='$dday15' WHERE userid='$userid' AND subject='$subject' ORDER BY id DESC LIMIT 1  "); 
		}
	if($eventid==14) // 미션 조건 변경하기
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];

		$nchange=$mission->nchange+1;
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("UPDATE {abessi_mission} SET  timemodified='$timecreated', hours='$hours', weekhours='$weekhours', grade='$grade', chstart='$chstart', startdate='$startdate'  WHERE id='$idcreated' "); 
		}
	if($eventid==15) //내신 미션 초기설정
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];

		$hours=0;
		$chstart=1;
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,chstart,weekhours,startdate,userid,deadline,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$hours','$chstart','$weekhours','$startdate','$userid','$deadline','0','$timecreated')");
	 
		}
	if($eventid==16) //내신 미션 수정
		{
		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
		$dday14= $_POST['dday14'];
		$dday15= $_POST['dday15'];

		$nchange=$mission->nchange+1;
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("UPDATE {abessi_mission} SET  timemodified='$timecreated', weekhours='$weekhours', grade='$grade', deadline='$deadline', startdate='$startdate'  WHERE id='$idcreated' "); 
		}

	if($eventid==17) // 내신미션, 수능미션 단계별 데드라인 저장하기
		{
		$grade1= $_POST['grade1'];
		$grade2= $_POST['grade2'];
		$grade3= $_POST['grade3'];
		$grade4= $_POST['grade4'];
		$grade5= $_POST['grade5'];
		$grade6= $_POST['grade6'];
		$grade7= $_POST['grade7'];
		$grade8= $_POST['grade8'];
		$grade9= $_POST['grade9'];
		$grade10= $_POST['grade10'];
		$grade11= $_POST['grade11'];
		$grade12= $_POST['grade12'];
		$grade13= $_POST['grade13'];

		$dday11= $_POST['dday11'];
		$dday12= $_POST['dday12'];
		$dday13= $_POST['dday13'];
	 
		$nchange=$mission->nchange+1;
		if($deadline==NULL)$deadline=date("Y/m/d", $timecreated+604800*12);
		$DB->execute("UPDATE {abessi_mission} SET deadline='$deadline', timemodified='$timecreated', nchange='$nchange', grade1='$grade1', grade2='$grade2', grade3='$grade3', grade4='$grade4', grade5='$grade5', grade6='$grade6', grade7='$grade7', grade8='$grade8', grade9='$grade9', grade10='$grade10',grade11='$grade11',grade12='$grade12',grade13='$grade13',  dday1='$dday1', dday2='$dday2', dday3='$dday3', dday4='$dday4', dday5='$dday5', dday6='$dday6', dday7='$dday7', dday8='$dday8', dday9='$dday9', dday10='$dday10' , dday11='$dday11', dday12='$dday12', dday13='$dday13'  WHERE userid='$userid' AND subject='$subject'  "); 
		}
	}

if($eventid==2) // input today's goal, weekly goal & mission
{
$type= $_POST['type'];
$level= $_POST['level'];
$deadline=strtotime($deadline); 

$checkgoal1= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");
$checkgoal2= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$userid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");

if($type==='오늘목표')$score=$checkgoal1->score;
if($type==='주간목표')$score=$checkgoal2->score;

if($score==NULL)$score=0;
$DB->execute("INSERT INTO {abessi_today} (text,userid,type,goallevel,score,complete,mindset,timemodified,timecreated,deadline) VALUES('$inputtext','$userid','$type','$level','$score','0','$mindset','$timecreated','$timecreated','$deadline')");

if($type==='오늘목표')
	{	
	$boardtype='today';
	include("createdbForTalk.php");
	$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,status,contentstype,wboardid,contentstitle,contentsid,timemodified,timecreated) VALUES('$userid','2','student','2','0','0','$boardtype','5','$encryption_id','$boardtype','111','$timecreated','$timecreated')");

	$dchanged=date("m/d/Y",$timecreated);
	$tbegintoday= strtotime($dchanged);
	
	$attend=$DB->get_record_sql("SELECT * FROM mdl_abessi_attendance  WHERE hide=0 AND userid='$userid' AND dchanged LIKE '$tbegintoday' AND (type LIKE '보강' OR type LIKE '날짜이동'  OR type LIKE '시간이동') ORDER BY id DESC LIMIT 1  ");
 	if($attend->id!=NULL)$DB->execute("UPDATE {abessi_attendance} SET complete='1'  WHERE id LIKE '$attend->id' ORDER BY id DESC LIMIT 1 "); 
	}
elseif($type==='주간목표')
	{	
	$boardtype='weekly';
	include("createdbForTalk.php");
	$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,status,contentstype,wboardid,contentstitle,contentsid,timemodified,timecreated) VALUES('$userid','2','student','2','0','0','$boardtype','5','$encryption_id','$boardtype','111','$timecreated','$timecreated')");
	if($role!=='student')$DB->execute("UPDATE {abessi_indicators} SET teacherid='$USER->id'  WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	}
	
 echo json_encode( array("passedeventid"=>$eventid) );
 
} 

if($eventid==21) // 귀가 요청을 위한 활동 자가진단 제출
{
$time=time();
$confident=$_POST['confident'];
$pcomplete=$_POST['pcomplete'];
//$ask = $_POST['ask'];
$review = $_POST['review'];
$DB->execute("UPDATE {abessi_today} SET type='검사요청', result='$confident',pcomplete='$pcomplete',comment='$inputtext',inspect='1',checktime='$time',submit='1', inspect='1', timemodified='$timecreated'   WHERE userid='$userid' AND ( type LIKE '오늘목표' OR  type='검사요청' ) ORDER BY id DESC LIMIT 1  "); 

}
 


if($eventid==3) // input weekly schedule
{
$start1= $_POST['start1'];
$start2= $_POST['start2'];
$start3= $_POST['start3'];
$start4= $_POST['start4'];
$start5= $_POST['start5'];
$start6= $_POST['start6'];
$start7= $_POST['start7'];

$start11= $_POST['start11'];
$start12= $_POST['start12'];
$start13= $_POST['start13'];
$start14= $_POST['start14'];
$start15= $_POST['start15'];
$start16= $_POST['start16'];
$start17= $_POST['start17'];

$duration1= $_POST['duration1'];
$duration2= $_POST['duration2'];
$duration3= $_POST['duration3'];
$duration4= $_POST['duration4'];
$duration5= $_POST['duration5'];
$duration6= $_POST['duration6'];
$duration7= $_POST['duration7'];

$room1= $_POST['room1'];
$room2= $_POST['room2'];
$room3= $_POST['room3'];
$room4= $_POST['room4'];
$room5= $_POST['room5'];
$room6= $_POST['room6'];
$room7= $_POST['room7'];

$memo1= $_POST['memo1'];
$memo2= $_POST['memo2'];
$memo3= $_POST['memo3'];
$memo4= $_POST['memo4'];
$memo5= $_POST['memo5'];
$memo6= $_POST['memo6'];
$memo7= $_POST['memo7'];
$memo8= $_POST['memo8'];
$memo9= $_POST['memo9'];
$date0=$_POST['date'];
$schtype=$_POST['schtype'];
$date=strtotime($date0);
if(empty($date)==1)$date=0;
if($duration1>0.1)$lastday='월요일';
if($duration2>0.1)$lastday='화요일';
if($duration3>0.1)$lastday='수요일';
if($duration4>0.1)$lastday='목요일';
if($duration5>0.1)$lastday='금요일';
if($duration6>0.1)$lastday='토요일';
if($duration7>0.1)$lastday='일요일';
 
$DB->execute("INSERT INTO {abessi_schedule} (editnew,lastday,start1,start2,start3,start4,start5,start6,start7,start11,start12,start13,start14,start15,start16,start17,duration1,duration2,duration3,duration4,duration5,duration6,duration7,room1,room2,room3,room4,room5,room6,room7,memo1,memo2,memo3,memo4,memo5,memo6,memo7,memo8,memo9,type,complete1,complete2,complete3,complete4,complete5,complete6,complete7,userid,pinned,timecreated) VALUES('0','$lastday','$start1','$start2','$start3','$start4','$start5','$start6','$start7','$start11','$start12','$start13','$start14','$start15','$start16','$start17','$duration1','$duration2','$duration3','$duration4','$duration5','$duration6','$duration7','$room1','$room2','$room3','$room4','$room5','$room6','$room7','$memo1','$memo2','$memo3','$memo4','$memo5','$memo6','$memo7','$memo8','$memo9','$schtype','0','0','0','0','0','0','0','$userid','1','$timecreated')");
} 


if($eventid==33) // input weekly schedule (mobile)
	{
	$schtype=$_POST['schtype'];
	$start1= $_POST['start1'];
	$start2= $_POST['start2'];
	$start3= $_POST['start3'];
	$start4= $_POST['start4'];
	$start5= $_POST['start5'];
	$start6= $_POST['start6'];
	$start7= $_POST['start7'];
	 
	$duration1= $_POST['duration1'];
	$duration2= $_POST['duration2'];
	$duration3= $_POST['duration3'];
	$duration4= $_POST['duration4'];
	$duration5= $_POST['duration5'];
	$duration6= $_POST['duration6'];
	$duration7= $_POST['duration7'];


	 
	if($duration1>0.1)$lastday='월요일';
	if($duration2>0.1)$lastday='화요일';
	if($duration3>0.1)$lastday='수요일';
	if($duration4>0.1)$lastday='목요일';
	if($duration5>0.1)$lastday='금요일';
	if($duration6>0.1)$lastday='토요일';
	if($duration7>0.1)$lastday='일요일';

	//echo '<script>console.log("aaaa")</script>;';
	 
	$DB->execute("INSERT INTO {abessi_schedule} (editnew,lastday,start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7,type,userid,pinned,timecreated) VALUES('0','$lastday','$start1','$start2','$start3','$start4','$start5','$start6','$start7','$duration1','$duration2','$duration3','$duration4','$duration5','$duration6','$duration7','$schtype','$userid','1','$timecreated')"); 	
	} 

 
if($eventid==7128) // input weekly schedule
{
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$userid'   ORDER BY id DESC LIMIT 1");

$start1= $_POST['start1'];
$start2= $_POST['start2'];
$start3= $_POST['start3'];
$start4= $_POST['start4'];
$start5= $_POST['start5'];
$start6= $_POST['start6'];
$start7= $_POST['start7'];

$start11= $schedule->start11;
$start12= $schedule->start12;
$start13= $schedule->start13;
$start14= $schedule->start14;
$start15= $schedule->start15;
$start16= $schedule->start16;
$start17= $schedule->start17;

$duration1= $_POST['duration1'];
$duration2= $_POST['duration2'];
$duration3= $_POST['duration3'];
$duration4= $_POST['duration4'];
$duration5= $_POST['duration5'];
$duration6= $_POST['duration6'];
$duration7= $_POST['duration7'];

$room1= $_POST['room1'];
$room2= $_POST['room2'];
$room3= $_POST['room3'];
$room4= $_POST['room4'];
$room5= $_POST['room5'];
$room6= $_POST['room6'];
$room7= $_POST['room7'];

$memo1= $_POST['memo1'];
$memo2= $_POST['memo2'];
$memo3= $_POST['memo3'];
$memo4= $_POST['memo4'];
$memo5= $_POST['memo5'];
$memo6= $_POST['memo6'];
$memo7= $_POST['memo7'];
$memo8= $_POST['memo8'];
$memo9= $_POST['memo9'];
$date0=$_POST['date'];
$date=strtotime($date0);
$schtype=$_POST['schtype'];
if(empty($date)==1)$date=0;
if($duration1>0.1)$lastday='월요일';
if($duration2>0.1)$lastday='화요일';
if($duration3>0.1)$lastday='수요일';
if($duration4>0.1)$lastday='목요일';
if($duration5>0.1)$lastday='금요일';
if($duration6>0.1)$lastday='토요일';
if($duration7>0.1)$lastday='일요일';
 

$DB->execute("INSERT INTO {abessi_schedule} (editnew,lastday,start1,start2,start3,start4,start5,start6,start7,start11,start12,start13,start14,start15,start16,start17,duration1,duration2,duration3,duration4,duration5,duration6,duration7,room1,room2,room3,room4,room5,room6,room7,memo1,memo2,memo3,memo4,memo5,memo6,memo7,memo8,memo9,type,complete1,complete2,complete3,complete4,complete5,complete6,complete7,userid,pinned,timecreated) VALUES('0','$lastday','$start1','$start2','$start3','$start4','$start5','$start6','$start7','$start11','$start12','$start13','$start14','$start15','$start16','$start17','$duration1','$duration2','$duration3','$duration4','$duration5','$duration6','$duration7','$room1','$room2','$room3','$room4','$room5','$room6','$room7','$memo1','$memo2','$memo3','$memo4','$memo5','$memo6','$memo7','$memo8','$memo9','$schtype','0','0','0','0','0','0','0','$userid','1','$timecreated')");
 		 
}
if($eventid==30) // payment 신규등록
{
$day1 =  $_POST['startdate'];
$numweek = $_POST['times'];
$nextday= $day1;
$cid1 = $_POST['subject'];
  
 for($n=2;$n<=28;$n++)
 	{
 	$nextday = date('Y/m/d', strtotime($nextday. ' +1 day'));
 	$daytitle='day'.$n;
 	$$daytitle=$nextday;
 	}
$userinfo = $DB->get_record_sql("SELECT ncreated FROM mdl_abessi_payment WHERE userid='$userid' ORDER BY id DESC LIMIT 1");
$ncreated=$userinfo->ncreated+3;
//if($ncreated==NULL)$ncreated=1;

$DB->execute("INSERT INTO {abessi_payment} (userid,ncreated,num,day1,day2,day3,day4,day5,day6,day7,day8,day9,day10,day11,day12,day13,day14,day15,day16,day17,day18,day19,
day20,day21,day22,day23,day24,day25,day26,day27,day28,cid1) VALUES('$userid','$ncreated','$numweek','$day1','$day2','$day3','$day4','$day5','$day6','$day7','$day8','$day9','$day10','$day11','$day12','$day13','$day14','$day15',
'$day16','$day17','$day18','$day19','$day20','$day21','$day22','$day23','$day24','$day25','$day26','$day27','$day28','$cid1')");
}
 
if($eventid==31) // 출결 수동 입력 & 삭제
{ 
$DB->execute("INSERT INTO {abessi_missionlog} (userid,text,eventid,date,timecreated) VALUES('$userid','$inputtext','$eventid','$date','$timecreated')"); 
}
   

if($eventid==5) // 내신테스트 예상시간
{
$start1= $_POST['start1'];
$start2= $_POST['start2'];
$start3= $_POST['start3'];
$start4= $_POST['start4'];
$start5= $_POST['start5'];
$start6= $_POST['start6'];
$start7= $_POST['start7'];

$start11= $_POST['start11'];
$start12= $_POST['start12'];
$start13= $_POST['start13'];
$start14= $_POST['start14'];
$start15= $_POST['start15'];
$start16= $_POST['start16'];
$start17= $_POST['start17'];

$duration1= $_POST['duration1'];
$duration2= $_POST['duration2'];
$duration3= $_POST['duration3'];
$duration4= $_POST['duration4'];
$duration5= $_POST['duration5'];
$duration6= $_POST['duration6'];
$duration7= $_POST['duration7'];

$memo1= $_POST['memo1'];
$memo2= $_POST['memo2'];
$memo3= $_POST['memo3'];
$memo4= $_POST['memo4'];
$memo5= $_POST['memo5'];
$memo6= $_POST['memo6'];
$memo7= $_POST['memo7'];
$memo8= $_POST['memo8'];
$memo9= $_POST['memo9'];
$date=$_POST['date'];
  
$DB->execute("INSERT INTO {abessi_schedule} (start1,start2,start3,start4,start5,start6,start7,start11,start12,start13,start14,start15,start16,start17,duration1,duration2,duration3,duration4,duration5,duration6,duration7,memo1,memo2,memo3,memo4,memo5,memo6,memo7,memo8,memo9,date,complete1,complete2,complete3,complete4,complete5,complete6,complete7,userid,timecreated) VALUES('$start1','$start2','$start3','$start4','$start5','$start6','$start7','$start11','$start12','$start13','$start14','$start15','$start16','$start17','$duration1','$duration2','$duration3','$duration4','$duration5','$duration6','$duration7','$memo1','$memo2','$memo3','$memo4','$memo5','$memo6','$memo7','$memo8','$memo9','$date','0','0','0','0','0','0','0','$userid','$timecreated')");


}

if($eventid==6) // 퀴즈 결과에 대한 의견 전달
{ 
$DB->execute("UPDATE {quiz_attempts} SET  comment='$inputtext', timemodified='$timecreated'  WHERE  id='$contentsid'  "); 
}
if($eventid==7) // 문항별 접근법에 대한 정보 전달
{ 
$inputtext=str_replace('.php?','.php/',$inputtext);
$DB->execute("UPDATE {question} SET  comment='$inputtext'  WHERE  id='$contentsid'  "); 
}
 
if($eventid==8) // 분기목표 촉진
{ 

$plantype = $_POST['plantype'];
$timestamp = strtotime($deadline);
$beingsullivan=$userid.$goaltype.$plantype.$deadline.$inputtext.$timecreated;  include("../whiteboard/debug.php");
$DB->execute("INSERT INTO {abessi_progress} (userid,plantype,deadline,memo,hide,timecreated) VALUES('$userid','$plantype','$timestamp','$inputtext','0','$timecreated')"); 

if($plantype==='분기목표')
	{
	$boardtype='period';
	include("createdbForTalk.php");	
	$DB->execute("INSERT INTO {abessi_messages} (userid,userto,userrole,talkid,nstep,turn,status,contentstype,wboardid,contentstitle,contentsid,timemodified,timecreated) VALUES('$userid','2','student','2','0','0','$boardtype','5','$encryption_id','$boardtype','222','$timecreated','$timecreated')");
	}
} 
 
if($eventid==9) // 출결처리, 수강유형 처리
{ 
$type = $_POST['type'];
$reason = $_POST['reason'];
$doriginal = $_POST['doriginal'];
$dchanged = $_POST['dchanged'];
$selecttime = $_POST['selecttime'];

$timestamp1 =(INT) strtotime($doriginal);
$timestamp2 =(INT) strtotime($dchanged);

//$beingsullivan=$timestamp2.' | '.$type.$reason.$timecreated;  include("../whiteboard/debug.php");
$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 ORDER BY id DESC LIMIT 1");
$tupdate=$exitlog->tupdate;

 
if($type==='보강' || $type==='최종휴강' || $type==='추가수업'  )$selecttime=$selecttime;  //|| $type==='수강료 변경'
elseif($type==='휴강')$selecttime=-$selecttime*0.6;
elseif($type==='4주보강'){$selecttime=$selecttime*4; $reason='addperiod';}
elseif($type==='8주보강'){$selecttime=$selecttime*8; $reason='addperiod';}
elseif($type==='12주보강'){$selecttime=$selecttime*12; $reason='addperiod';}
 
if($exitlog->id==NULL)$tupdate=0;
if($type==='휴강' || $type==='최종휴강' || $type==='보강' )$tupdate=$tupdate+$selecttime; 
if($type==='퇴원'  || $type==='휴원'|| $type==='기기대여'|| $type==='기기반납' || $type==='신규'||$type==='날짜이동' ||$type==='시간이동' || $type==='온라인수업' ){$reason=$type; $selecttime=0; } 

$DB->execute("INSERT INTO {abessi_attendance} (userid,teacherid,type,reason,text,tamount,tupdate,doriginal,dchanged,complete,hide,timecreated) VALUES('$userid','$USER->id','$type','$reason','$inputtext','$selecttime','$tupdate','$timestamp1','$timestamp2','0','0','$timecreated')"); 
} 

if($eventid==91) // 수납정보
	{  		 
	$type = $_POST['type'];
	$reason = $_POST['reason'];
	$fee = $_POST['fee'];
	$begintime = $_POST['begintime'];
	$selecttime = $_POST['selecttime'];
	$bessi='aaaa'.$type.$reason.$timecreated;  include("../whiteboard/debug.php");
 
	//$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 AND type LIKE 'enrol' ORDER BY id DESC LIMIT 1");
	$status='시작';
	$timestamp =(INT) strtotime($selecttime);
	$begintime =(INT) strtotime($begintime);
	$DB->execute("INSERT INTO {abessi_attendance} (userid,teacherid,type,reason,status,fee,text,complete,hide,doriginal,dchanged,timecreated) VALUES('$userid','$USER->id','$type','$reason','$status','$fee','$inputtext','0','0','$begintime','$timestamp','$timecreated')"); 
	 
	} 

if($eventid==92) // 수납정보 업데이트
	{  
	$logid = $_POST['logid'];	 $deposit = $_POST['deposit'];
	
	$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE id='$logid' ORDER BY id DESC LIMIT 1");
	$tenrol=$exitlog->dchanged;
	 
	if($inputtext==='미납')$complete=0;
	elseif($inputtext==='납부')$complete=1;	
	elseif($inputtext==='전월납부'){$complete=1;$tenrol=$tenrol-604800;}
	elseif($inputtext==='익월납부'){$complete=1;$tenrol=$tenrol+604800;}

	if($inputtext==='납부')$DB->execute("UPDATE {abessi_attendance} SET complete='$complete', deposit='$deposit', status='$inputtext', dchanged='$tenrol'  WHERE id LIKE '$logid' ORDER BY id DESC LIMIT 1 ");  
	else $DB->execute("UPDATE {abessi_attendance} SET complete='$complete', status='$inputtext', dchanged='$tenrol'  WHERE id LIKE '$logid' ORDER BY id DESC LIMIT 1 ");  
	} 
if($eventid==93) // 활동보상
{ 
$type = '활동보상';
$reason = '활동보상';
$doriginal = $timecreated;
$dchanged =$timecreated;
$selecttime = round($_POST['selecttime']/60,1);
 
$timestamp1 = $timecreated;
$timestamp2 = $timecreated;
$halfdayago=$timecreated-43200;
//$beingsullivan=$timestamp2.' | '.$type.$reason.$timecreated;  include("../whiteboard/debug.php");
$exitlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 ORDER BY id DESC LIMIT 1");
if($exitlog->id==NULL)$tupdate=0;
else $tupdate=$exitlog->tupdate;

$exitlog2= $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 AND type LIKE '활동보상' AND timecreated > '$halfdayago' ORDER BY id DESC LIMIT 1");
 
if($exitlog2->id==NULL)
	{
	$tupdate=$tupdate+$selecttime; 
	$DB->execute("INSERT INTO {abessi_attendance} (userid,teacherid,type,reason,text,tamount,tupdate,doriginal,dchanged,complete,hide,timecreated) VALUES('$userid','$USER->id','$type','$reason','$inputtext','$selecttime','$tupdate','$timestamp1','$timestamp2','0','0','$timecreated')"); 
	}
} 

if($eventid==40) // 학생 개인정보 변경
	{ 
	$institute = $_POST['institute']; // 학교 88
	$birthdate = $_POST['birthdate']; //출생년도 89
	$phone1 = $_POST['phone1']; //학생 연락처 54
	$phone2 = $_POST['phone2']; //아버지 연락처 85
	$phone3 = $_POST['phone3']; //어머니 연락처 55
	$brotherhood = $_POST['brotherhood']; //형제관계 44
	$academy = $_POST['academy']; //학원명 46
	$location = $_POST['location']; //지역 68
	$addcourse = $_POST['addcourse']; // 코스추천 83
	$fluency = $_POST['fluency']; // 사용법 능숙도 60
	$goalstability = $_POST['goalstability']; //목표설정 안정도 80
	$efficiency = $_POST['efficiency'];  // 공부효율 81
	$lmode = $_POST['lmode']; // 맞춤모드 선택 90	
	$evaluate = $_POST['evaluate']; //  92
	$curriculum = $_POST['curriculum'];  //커리큘럼 속성 70
	$nboosters = $_POST['nboosters']; //부스터 활동 횟수 86
 	$inspecttime= $_POST['inspecttime']; //부스터 활동 횟수 72
 	$roleinfo= $_POST['userrole']; // 사용자 유형 22
 	$termhours= $_POST['termhours']; // 대학유형 107
	$vachours= $_POST['vachours']; // 대학유형 108

 	$univ= $_POST['univ']; // 대학유형 105
	$pathtype= $_POST['pathtype']; // 대학유형 106
	$preseta = $_POST['preseta']; // 개념미션 preset 93
	$presetb = $_POST['presetb']; // 심화미션 preset 94
	$presetc = $_POST['presetc']; // 내신미션 preset 95
	$presetd = $_POST['presetd']; // 수능미션 preset 96

 
 	$DB->execute("UPDATE {user} SET institution='$academy'  WHERE id='$userid'  ");  

 	$exit46= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=46 ORDER BY id DESC LIMIT 1"); 
 	if($exit46->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','46','$academy')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$academy'  WHERE userid='$userid' AND fieldid=46 ");  

 	$exit88= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=88 ORDER BY id DESC LIMIT 1"); 
 	if($exit88->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','88','$institute')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$institute'  WHERE userid='$userid' AND fieldid=88 ");  
 	 
 	$exit89= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=89 ORDER BY id DESC LIMIT 1"); 
 	if($exit89->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','89','$birthdate')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$birthdate'  WHERE userid='$userid' AND fieldid=89 ");  

 	$exit54= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=54 ORDER BY id DESC LIMIT 1"); 
 	if($exit54->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','54','$phone1')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$phone1'  WHERE userid='$userid' AND fieldid=54 ");  

 	$exit54= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=54 ORDER BY id DESC LIMIT 1"); 
 	if($exit54->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','54','$phone2')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$phone2'  WHERE userid='$userid' AND fieldid=85 ");  

 	$exit55= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=55 ORDER BY id DESC LIMIT 1"); 
 	if($exit55->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','55','$phone3')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$phone3'  WHERE userid='$userid' AND fieldid=55 ");  

 	$exit44= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=44 ORDER BY id DESC LIMIT 1"); 
 	if($exit44->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','44','$brotherhood')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$brotherhood'  WHERE userid='$userid' AND fieldid=44 ");  

 	$exit68= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=68 ORDER BY id DESC LIMIT 1"); 
 	if($exit68->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','68','$location')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$location'  WHERE userid='$userid' AND fieldid=68 ");  

 	$exit83= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=83 ORDER BY id DESC LIMIT 1"); 
 	if($exit83->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','83','$addcourse')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$addcourse'  WHERE userid='$userid' AND fieldid=83 ");  


 	$exit60= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=60 ORDER BY id DESC LIMIT 1"); 
 	if($exit60->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','60','$fluency')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$fluency'  WHERE userid='$userid' AND fieldid=60 ");  

 	$exit80= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=80 ORDER BY id DESC LIMIT 1"); 
 	if($exit80->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','80','$goalstability')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$goalstability'  WHERE userid='$userid' AND fieldid=80 ");  

 	$exit81= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=81 ORDER BY id DESC LIMIT 1"); 
 	if($exit81->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','81','$efficiency')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$efficiency'  WHERE userid='$userid' AND fieldid=81 ");  

 	$exit90= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=90 ORDER BY id DESC LIMIT 1"); 
 	if($exit90->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','90','$institute')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$lmode'  WHERE userid='$userid' AND fieldid=90 ");  

 	$exit92= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=92 ORDER BY id DESC LIMIT 1"); 
 	if($exit92->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','92','$institute')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$evaluate'  WHERE userid='$userid' AND fieldid=92 ");  

 	$exit70= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=70 ORDER BY id DESC LIMIT 1"); 
 	if($exit70->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','70','$curriculum')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$curriculum'  WHERE userid='$userid' AND fieldid=70 "); 

 	$exit86= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=86 ORDER BY id DESC LIMIT 1"); 
 	if($exit86->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','86','$nboosters')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$nboosters'  WHERE userid='$userid' AND fieldid=86 ");  

 	$exit72= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=72 ORDER BY id DESC LIMIT 1"); 
 	if($exit72->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','72','$inspecttime')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$inspecttime'  WHERE userid='$userid' AND fieldid=72 ");  


	if($role!=='teacher' && $role!=='student' &&  $role!=='assistant' )
		{
 		$exit22= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=22 ORDER BY id DESC LIMIT 1"); 
 		if($exit22->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','22','$roleinfo')"); 
		else $DB->execute("UPDATE {user_info_data} SET data='$roleinfo'  WHERE userid='$userid' AND fieldid=22 "); 
		}


 	$exit93= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=93 ORDER BY id DESC LIMIT 1"); 
 	if($exit93->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','93','$preseta')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$preseta'  WHERE userid='$userid' AND fieldid=93 ");    

 	$exit94= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=94 ORDER BY id DESC LIMIT 1"); 
 	if($exit94->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','94','$presetb')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$presetb'  WHERE userid='$userid' AND fieldid=94 ");   

 	$exit95= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=95 ORDER BY id DESC LIMIT 1"); 
 	if($exit95->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','95','$presetc')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$presetc'  WHERE userid='$userid' AND fieldid=95 ");   

 	$exit96= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=96 ORDER BY id DESC LIMIT 1"); 
 	if($exit96->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','96','$presetd')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$presetd'  WHERE userid='$userid' AND fieldid=96 ");   	
 
 	$exit105= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=105 ORDER BY id DESC LIMIT 1"); 
 	if($exit105->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','105','$univ')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$univ'  WHERE userid='$userid' AND fieldid=105 ");  

 	$exit106= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=106 ORDER BY id DESC LIMIT 1"); 
 	if($exit106->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','106','$pathtype')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$pathtype'  WHERE userid='$userid' AND fieldid=106 ");  


 	$exit107= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=107 ORDER BY id DESC LIMIT 1"); 
 	if($exit107->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','107','$termhours')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$termhours'  WHERE userid='$userid' AND fieldid=107 ");  

 	$exit108= $DB->get_record_sql("SELECT * FROM mdl_user_info_data WHERE userid='$userid' AND fieldid=108 ORDER BY id DESC LIMIT 1"); 
 	if($exit108->id==NULL) $DB->execute("INSERT INTO {user_info_data} (userid,fieldid,data) VALUES('$userid','108','$vachours')"); 
	else $DB->execute("UPDATE {user_info_data} SET data='$vachours'  WHERE userid='$userid' AND fieldid=108 ");  

	}

if($eventid==41) // 학생 개인정보 변경
	{ 
	 
 	$date103=$_POST['date103'];  
 	$date104=$_POST['date104'];  
 	$date105=$_POST['date105'];  
 	$date106=$_POST['date106'];  
 	$date107=$_POST['date107'];  
 	$date108=$_POST['date108'];  
 	$date109=$_POST['date109'];  
 	$date110=$_POST['date110'];  
 	$date111=$_POST['date111'];  
 	$date112=$_POST['date112'];  
 	$date113=$_POST['date113'];  
 	$date114=$_POST['date114'];  
 	$date115=$_POST['date115'];  
 	$date116=$_POST['date116'];  
 	$date117=$_POST['date117'];  
 	$date118=$_POST['date118'];  
 	$date119=$_POST['date119'];  
 	$date120=$_POST['date120'];  
 	$date121=$_POST['date121'];  

 	$date203=$_POST['date203'];  
 	$date204=$_POST['date204'];  
 	$date205=$_POST['date205'];  
 	$date206=$_POST['date206'];  
 	$date207=$_POST['date207'];  
 	$date208=$_POST['date208'];  
 	$date209=$_POST['date209'];  
 	$date210=$_POST['date210'];  
 	$date211=$_POST['date211'];  
 	$date212=$_POST['date212'];  
 	$date213=$_POST['date213'];  
 	$date214=$_POST['date214'];  
 	$date215=$_POST['date215'];  
 	$date216=$_POST['date216'];  
 	$date217=$_POST['date217'];  
 	$date218=$_POST['date218'];  
 	$date219=$_POST['date219'];  
 	$date220=$_POST['date220'];  
 	$date221=$_POST['date221'];  

 	$date309=$_POST['date309'];  
 	$date310=$_POST['date310'];  
 	$date311=$_POST['date311'];  
 	$date312=$_POST['date312'];  
 	$date313=$_POST['date313'];  
 	$date314=$_POST['date314'];  
 	$date315=$_POST['date315'];  
 	$date316=$_POST['date316'];  
 	$date317=$_POST['date317'];  
 	$date318=$_POST['date318'];  
 	$date319=$_POST['date319'];  
 	$date320=$_POST['date320'];  
 	$date321=$_POST['date321'];  

 	$date401=$_POST['date401'];  
 	$date402=$_POST['date402'];  
 	$date403=$_POST['date403'];  
  
// 개념미션

	$subject103= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '103' ORDER BY id DESC LIMIT 1");$norder=$subject103->norder;
 	$exit103= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject103->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit103->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject103->id','$norder','$date103')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date103'  WHERE id='$exit103->id' ");  

	$subject104= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '104' ORDER BY id DESC LIMIT 1");$norder=$subject104->norder; 
 	$exit104= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject104->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit104->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject104->id','$norder','$date104')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date104'  WHERE id='$exit104->id' ");  

	$subject105= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '105' ORDER BY id DESC LIMIT 1");$norder=$subject105->norder; 
 	$exit105= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject105->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit105->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject105->id','$norder','$date105')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date105'  WHERE id='$exit105->id' ");  

	$subject106= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '106' ORDER BY id DESC LIMIT 1");$norder=$subject106->norder; 
 	$exit106= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject106->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit106->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject106->id','$norder','$date106')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date106'  WHERE id='$exit106->id' ");  

	$subject107= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '107' ORDER BY id DESC LIMIT 1");$norder=$subject107->norder; 
 	$exit107= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject107->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit107->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject107->id','$norder','$date107')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date107'  WHERE id='$exit107->id' ");  

	$subject108= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '108' ORDER BY id DESC LIMIT 1");$norder=$subject108->norder; 
 	$exit108= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject108->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit108->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject108->id','$norder','$date108')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date108'  WHERE id='$exit108->id' ");  

	$subject109= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '109' ORDER BY id DESC LIMIT 1");$norder=$subject109->norder; 
 	$exit109= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject109->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit109->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject109->id','$norder','$date109')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date109'  WHERE id='$exit109->id' ");  

	$subject110= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '110' ORDER BY id DESC LIMIT 1");$norder=$subject110->norder; 
 	$exit110= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject110->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit110->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject110->id','$norder','$date110')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date110'  WHERE id='$exit110->id' ");  

	$subject111= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '111' ORDER BY id DESC LIMIT 1");$norder=$subject111->norder; 
 	$exit111= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject111->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit111->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject111->id','$norder','$date111')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date111'  WHERE id='$exit111->id' ");  

	$subject112= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '112' ORDER BY id DESC LIMIT 1");$norder=$subject112->norder; 
 	$exit112= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject112->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit112->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject112->id','$norder','$date112')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date112'  WHERE id='$exit112->id' ");  

	$subject113= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '113' ORDER BY id DESC LIMIT 1");$norder=$subject113->norder; 
 	$exit113= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject113->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit113->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject113->id','$norder','$date113')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date113'  WHERE id='$exit113->id' ");  

	$subject114= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '114' ORDER BY id DESC LIMIT 1");$norder=$subject114->norder; 
 	$exit114= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject114->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit114->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject114->id','$norder','$date114')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date114'  WHERE id='$exit114->id' ");  

	$subject115= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '115' ORDER BY id DESC LIMIT 1");$norder=$subject115->norder; 
 	$exit115= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject115->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit115->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject115->id','$norder','$date115')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date115'  WHERE id='$exit115->id' ");  

	$subject116= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '116' ORDER BY id DESC LIMIT 1");$norder=$subject116->norder; 
 	$exit116= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject116->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit116->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject116->id','$norder','$date116')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date116'  WHERE id='$exit116->id' ");  

	$subject117= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '117' ORDER BY id DESC LIMIT 1");$norder=$subject117->norder; 
 	$exit117= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject117->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit117->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject117->id','$norder','$date117')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date117'  WHERE id='$exit117->id' ");  

	$subject118= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '118' ORDER BY id DESC LIMIT 1");$norder=$subject118->norder; 
 	$exit118= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject118->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit118->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject118->id','$norder','$date118')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date118'  WHERE id='$exit118->id' ");  

	$subject119= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '119' ORDER BY id DESC LIMIT 1");$norder=$subject119->norder; 
 	$exit119= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject119->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit119->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject119->id','$norder','$date119')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date119'  WHERE id='$exit119->id' ");  

	$subject120= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '120' ORDER BY id DESC LIMIT 1");$norder=$subject120->norder; 
 	$exit120= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject120->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit120->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject120->id','$norder','$date120')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date120'  WHERE id='$exit120->id' ");  

	$subject121= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '121' ORDER BY id DESC LIMIT 1");$norder=$subject121->norder; 
 	$exit121= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject121->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit121->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','7','$subject121->id','$norder','$date121')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date121'  WHERE id='$exit121->id' ");  

 

// 심화미션

	$subject203= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '203' ORDER BY id DESC LIMIT 1");$norder=$subject203->norder; 
 	$exit203= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject203->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit203->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject203->id','$norder','$date203')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date203'  WHERE id='$exit203->id' ");  

	$subject204= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '204' ORDER BY id DESC LIMIT 1");$norder=$subject204->norder; 
 	$exit204= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject204->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit204->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject204->id','$norder','$date204')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date204'  WHERE id='$exit204->id' ");  

	$subject205= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '205' ORDER BY id DESC LIMIT 1");$norder=$subject205->norder; 
 	$exit205= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject205->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit205->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject205->id','$norder','$date205')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date205'  WHERE id='$exit205->id' ");  

	$subject206= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '206' ORDER BY id DESC LIMIT 1");$norder=$subject206->norder; 
 	$exit206= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject206->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit206->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject206->id','$norder','$date206')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date206'  WHERE id='$exit206->id' ");  

	$subject207= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '207' ORDER BY id DESC LIMIT 1");$norder=$subject207->norder; 
 	$exit207= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject207->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit207->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject207->id','$norder','$date207')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date207'  WHERE id='$exit207->id' ");  

	$subject208= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '208' ORDER BY id DESC LIMIT 1");$norder=$subject208->norder; 
 	$exit208= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject208->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit208->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject208->id','$norder','$date208')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date208'  WHERE id='$exit208->id' ");  

	$subject209= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '209' ORDER BY id DESC LIMIT 1");$norder=$subject209->norder; 
 	$exit209= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject209->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit209->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject209->id','$norder','$date209')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date209'  WHERE id='$exit209->id' ");  

	$subject210= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '210' ORDER BY id DESC LIMIT 1");$norder=$subject210->norder; 
 	$exit210= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject210->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit210->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject210->id','$norder','$date210')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date210'  WHERE id='$exit210->id' ");  

	$subject211= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '211' ORDER BY id DESC LIMIT 1");$norder=$subject211->norder; 
 	$exit211= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject211->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit211->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject211->id','$norder','$date211')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date211'  WHERE id='$exit211->id' ");  

	$subject212= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '212' ORDER BY id DESC LIMIT 1");$norder=$subject212->norder; 
 	$exit212= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject212->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit212->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject212->id','$norder','$date212')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date212'  WHERE id='$exit212->id' ");  

	$subject213= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '213' ORDER BY id DESC LIMIT 1");$norder=$subject213->norder; 
 	$exit213= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject213->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit213->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject213->id','$norder','$date213')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date213'  WHERE id='$exit213->id' ");  

	$subject214= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '214' ORDER BY id DESC LIMIT 1");$norder=$subject214->norder; 
 	$exit214= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject214->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit214->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject214->id','$norder','$date214')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date214'  WHERE id='$exit214->id' ");  

	$subject215= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '215' ORDER BY id DESC LIMIT 1");$norder=$subject215->norder; 
 	$exit215= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject215->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit215->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject215->id','$norder','$date215')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date215'  WHERE id='$exit215->id' ");  

	$subject216= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '216' ORDER BY id DESC LIMIT 1");$norder=$subject216->norder; 
 	$exit216= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject216->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit216->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject216->id','$norder','$date216')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date216'  WHERE id='$exit216->id' ");  

	$subject217= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '217' ORDER BY id DESC LIMIT 1");$norder=$subject217->norder; 
 	$exit217= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject217->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit217->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject217->id','$norder','$date217')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date217'  WHERE id='$exit217->id' ");  

	$subject218= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '218' ORDER BY id DESC LIMIT 1");$norder=$subject218->norder; 
 	$exit218= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject218->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit218->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject218->id','$norder','$date218')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date218'  WHERE id='$exit218->id' ");  

	$subject219= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '219' ORDER BY id DESC LIMIT 1");$norder=$subject219->norder; 
 	$exit219= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject219->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit219->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject219->id','$norder','$date219')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date219'  WHERE id='$exit219->id' ");  

	$subject220= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '220' ORDER BY id DESC LIMIT 1");$norder=$subject220->norder; 
 	$exit220= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject220->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit220->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject220->id','$norder','$date220')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date220'  WHERE id='$exit220->id' ");  

	$subject221= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '221' ORDER BY id DESC LIMIT 1");$norder=$subject221->norder; 
 	$exit221= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject221->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit221->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','2','$subject221->id','$norder','$date221')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date221'  WHERE id='$exit221->id' ");  

 
// 내신미션

 
	$subject309= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '309' ORDER BY id DESC LIMIT 1");$norder=$subject309->norder; 
 	$exit309= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject309->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit309->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject309->id','$norder','$date309')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date309'  WHERE id='$exit309->id' ");  

	$subject310= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '310' ORDER BY id DESC LIMIT 1");$norder=$subject310->norder; 
 	$exit310= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject310->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit310->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject310->id','$norder','$date310')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date310'  WHERE id='$exit310->id' ");  

	$subject311= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '311' ORDER BY id DESC LIMIT 1");$norder=$subject311->norder; 
 	$exit311= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject311->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit311->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject311->id','$norder','$date311')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date311'  WHERE id='$exit311->id' ");  

	$subject312= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '312' ORDER BY id DESC LIMIT 1");$norder=$subject312->norder; 
 	$exit312= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject312->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit312->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject312->id','$norder','$date312')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date312'  WHERE id='$exit312->id' ");  

	$subject313= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '313' ORDER BY id DESC LIMIT 1");$norder=$subject313->norder; 
 	$exit313= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject313->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit313->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject313->id','$norder','$date313')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date313'  WHERE id='$exit313->id' ");  

	$subject314= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '314' ORDER BY id DESC LIMIT 1");$norder=$subject314->norder; 
 	$exit314= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject314->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit314->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject314->id','$norder','$date314')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date314'  WHERE id='$exit314->id' ");  

	$subject315= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '315' ORDER BY id DESC LIMIT 1");$norder=$subject315->norder; 
 	$exit315= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject315->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit315->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject315->id','$norder','$date315')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date315'  WHERE id='$exit315->id' ");  

	$subject316= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '316' ORDER BY id DESC LIMIT 1");$norder=$subject316->norder; 
 	$exit316= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject316->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit316->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject316->id','$norder','$date316')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date316'  WHERE id='$exit316->id' ");  

	$subject317= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '317' ORDER BY id DESC LIMIT 1");$norder=$subject317->norder; 
 	$exit317= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject317->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit317->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject317->id','$norder','$date317')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date317'  WHERE id='$exit317->id' ");  

	$subject318= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '318' ORDER BY id DESC LIMIT 1");$norder=$subject318->norder; 
 	$exit318= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject318->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit318->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject318->id','$norder','$date318')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date318'  WHERE id='$exit318->id' ");  

	$subject319= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '319' ORDER BY id DESC LIMIT 1");$norder=$subject319->norder; 
 	$exit319= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject319->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit319->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject319->id','$norder','$date319')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date319'  WHERE id='$exit319->id' ");  

	$subject320= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '320' ORDER BY id DESC LIMIT 1");$norder=$subject320->norder; 
 	$exit320= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject320->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit320->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject320->id','$norder','$date320')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date320'  WHERE id='$exit320->id' ");  

	$subject321= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '321' ORDER BY id DESC LIMIT 1");$norder=$subject321->norder; 
 	$exit321= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject321->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit321->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','3','$subject321->id','$norder','$date321')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date321'  WHERE id='$exit321->id' ");  

//수능미션

	$subject401= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '401' ORDER BY id DESC LIMIT 1");$norder=$subject401->norder; 
 	$exit401= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject401->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit401->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','4','$subject401->id','$norder','$date401')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date401'  WHERE id='$exit401->id' ");  

	$subject402= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '402' ORDER BY id DESC LIMIT 1");$norder=$subject402->norder; 
 	$exit402= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject402->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit402->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','4','$subject402->id','$norder','$date402')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date402'  WHERE id='$exit402->id' ");  

	$subject403= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '403' ORDER BY id DESC LIMIT 1");$norder=$subject403->norder; 
 	$exit403= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject403->id' ORDER BY id DESC LIMIT 1"); 
 	if($exit403->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline) VALUES('$userid','4','$subject403->id','$norder','$date403')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$date403'  WHERE id='$exit403->id' ");  

	}
 
if($eventid==42) // 학생 개인정보 변경, preset 적용
	{ 
	$univ=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='105' ");// 학교 
	$nuniv=$univ->data;
	$pathtype=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='106' ");// 커리큘럼 유형
	$npath=$pathtype->data;
	$daystr='day'.$npath.$nuniv;
 	$birthyear = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='89' ");//출생년도 

 	$bias103=$_POST['bias103'];  
 	$bias104=$_POST['bias104'];  
 	$bias105=$_POST['bias105'];  
 	$bias106=$_POST['bias106'];  
 	$bias107=$_POST['bias107'];  
 	$bias108=$_POST['bias108'];  
 	$bias109=$_POST['bias109'];  
 	$bias110=$_POST['bias110'];  
 	$bias111=$_POST['bias111'];  
 	$bias112=$_POST['bias112'];  
 	$bias113=$_POST['bias113'];  
 	$bias114=$_POST['bias114'];  
 	$bias115=$_POST['bias115'];  
 	$bias116=$_POST['bias116'];  
 	$bias117=$_POST['bias117'];  
 	$bias118=$_POST['bias118'];  
 	$bias119=$_POST['bias119'];  
 	$bias120=$_POST['bias120'];  
 	$bias121=$_POST['bias121'];  

 	$bias203=$_POST['bias203'];  
 	$bias204=$_POST['bias204'];  
 	$bias205=$_POST['bias205'];  
 	$bias206=$_POST['bias206'];  
 	$bias207=$_POST['bias207'];  
 	$bias208=$_POST['bias208'];  
 	$bias209=$_POST['bias209'];  
 	$bias210=$_POST['bias210'];  
 	$bias211=$_POST['bias211'];  
 	$bias212=$_POST['bias212'];  
 	$bias213=$_POST['bias213'];  
 	$bias214=$_POST['bias214'];  
 	$bias215=$_POST['bias215'];  
 	$bias216=$_POST['bias216'];  
 	$bias217=$_POST['bias217'];  
 	$bias218=$_POST['bias218'];  
 	$bias219=$_POST['bias219'];  
 	$bias220=$_POST['bias220'];  
 	$bias221=$_POST['bias221'];  

 	$bias309=$_POST['bias309'];  
 	$bias310=$_POST['bias310'];  
 	$bias311=$_POST['bias311'];  
 	$bias312=$_POST['bias312'];  
 	$bias313=$_POST['bias313'];  
 	$bias314=$_POST['bias314'];  
 	$bias315=$_POST['bias315'];  
 	$bias316=$_POST['bias316'];  
 	$bias317=$_POST['bias317'];  
 	$bias318=$_POST['bias318'];  
 	$bias319=$_POST['bias319'];  
 	$bias320=$_POST['bias320'];  
 	$bias321=$_POST['bias321'];  

 	$bias401=$_POST['bias401'];  
 	$bias402=$_POST['bias402'];  
 	$bias403=$_POST['bias403'];  

	$DB->execute("UPDATE {abessi_preset} SET e41='$bias103',e42='$bias104',e51='$bias105',e52='$bias106',e61='$bias107',e62='$bias108',m11='$bias109',m12='$bias110',m21='$bias111',m22='$bias112',m31='$bias113',m32='$bias114',h11='$bias115',h12='$bias116',h21='$bias117',h22='$bias118',h31='$bias119',h32='$bias120',h33='$bias121',timemodified='$timecreated' WHERE userid='$userid' AND mtid=7 ");  
	$DB->execute("UPDATE {abessi_preset} SET e41='$bias203',e42='$bias204',e51='$bias205',e52='$bias206',e61='$bias207',e62='$bias208',m11='$bias209',m12='$bias210',m21='$bias211',m22='$bias212',m31='$bias213',m32='$bias214',h11='$bias215',h12='$bias216',h21='$bias217',h22='$bias218',h31='$bias219',h32='$bias220',h33='$bias221',timemodified='$timecreated' WHERE userid='$userid' AND mtid=2 ");  
	$DB->execute("UPDATE {abessi_preset} SET m11='$bias309',m12='$bias310',m21='$bias311',m22='$bias312',m31='$bias313',m32='$bias314',h11='$bias315',h12='$bias316',h21='$bias317',h22='$bias318',h31='$bias319',h32='$bias320',h33='$bias321',timemodified='$timecreated' WHERE userid='$userid' AND mtid=3 ");  
	$DB->execute("UPDATE {abessi_preset} SET h11='$bias401',h21='$bias402',h31='$bias403', timemodified='$timecreated' WHERE userid='$userid' AND mtid=4 ");  

// 개념미션

	$subject103= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '103' ORDER BY id DESC LIMIT 1");$norder=$subject103->norder; 
 	$exit103= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject103->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject103->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias103+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit103->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject103->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit103->id' ");  


	$subject104= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '104' ORDER BY id DESC LIMIT 1");$norder=$subject014->norder; 
 	$exit104= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject104->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject104->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias104+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit104->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject104->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit104->id' ");  

	$subject105= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '105' ORDER BY id DESC LIMIT 1");$norder=$subject105->norder; 
 	$exit105= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject105->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject105->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias105+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit105->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject105->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit105->id' ");  

	$subject106= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '106' ORDER BY id DESC LIMIT 1");$norder=$subject106->norder; 
 	$exit106= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject106->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject106->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias106+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit106->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject106->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit106->id' ");  
 
	$subject107= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '107' ORDER BY id DESC LIMIT 1");$norder=$subject107->norder; 
 	$exit107= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject107->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject107->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias107+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit107->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject107->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit107->id' ");  

	$subject108= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '108' ORDER BY id DESC LIMIT 1");$norder=$subject108->norder; 
 	$exit108= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject108->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject108->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias108+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit108->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject108->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit108->id' ");  

	$subject109= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '109' ORDER BY id DESC LIMIT 1");$norder=$subject109->norder; 
 	$exit109= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject109->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject109->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias109+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit109->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject109->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit109->id' ");  

	$subject110= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '110' ORDER BY id DESC LIMIT 1");$norder=$subject110->norder; 
 	$exit110= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject110->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject110->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias110+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit110->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject110->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit110->id' ");  

	$subject111= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '111' ORDER BY id DESC LIMIT 1");$norder=$subject111->norder; 
 	$exit111= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject111->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject111->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias111+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit111->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject111->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit111->id' ");  

	$subject112= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '112' ORDER BY id DESC LIMIT 1");$norder=$subject112->norder; 
 	$exit112= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject112->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject112->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias112+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit112->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject112->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit112->id' ");  

	$subject113= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '113' ORDER BY id DESC LIMIT 1");$norder=$subject113->norder; 
 	$exit113= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject113->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject113->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias113+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit113->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject113->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit113->id' ");  

	$subject114= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '114' ORDER BY id DESC LIMIT 1");$norder=$subject114->norder; 
 	$exit114= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject114->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject114->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias114+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit114->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject114->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit114->id' ");  

	$subject115= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '115' ORDER BY id DESC LIMIT 1");$norder=$subject115->norder; 
 	$exit115= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject115->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject115->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias115+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit115->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject115->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit115->id' ");  

	$subject116= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '116' ORDER BY id DESC LIMIT 1");$norder=$subject116->norder; 
 	$exit116= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject116->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject116->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias116+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit116->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject116->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit116->id' ");  

	$subject117= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '117' ORDER BY id DESC LIMIT 1");$norder=$subject117->norder; 
 	$exit117= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject117->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject117->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias117+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit117->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject117->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit117->id' ");  

	$subject118= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '118' ORDER BY id DESC LIMIT 1");$norder=$subject118->norder; 
 	$exit118= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject118->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject118->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias118+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit118->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject118->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit118->id' ");  

	$subject119= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '119' ORDER BY id DESC LIMIT 1");$norder=$subject119->norder; 
 	$exit119= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject119->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject119->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias119+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit119->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject119->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit119->id' ");  

	$subject120= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '120' ORDER BY id DESC LIMIT 1");$norder=$subject120->norder; 
 	$exit120= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject120->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject120->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias120+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit120->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject120->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit120->id' ");  

	$subject121= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '121' ORDER BY id DESC LIMIT 1");$norder=$subject121->norder; 
 	$exit121= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject121->id' ORDER BY id DESC LIMIT 1"); 
	$numgrade=$subject121->$daystr;
	$nyears=round($numgrade/100,0);
	$unixtime=($birthyear->data-$bias121+6+$nyears+($numgrade-$nyears*100)/12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit121->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','7','$subject121->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit121->id' ");  


// 심화미션

	$subject203= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '203' ORDER BY id DESC LIMIT 1");$norder=$subject203->norder; 
 	$exit203= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject203->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias203+10.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit203->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject203->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit203->id' ");  

	$subject204= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '204' ORDER BY id DESC LIMIT 1");$norder=$subject204->norder; 
 	$exit204= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject204->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias204+11 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit204->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject204->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit204->id' ");  

	$subject205= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '205' ORDER BY id DESC LIMIT 1");$norder=$subject205->norder; 
 	$exit205= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject205->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias205+11.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit205->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject205->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit205->id' ");  

	$subject206= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '206' ORDER BY id DESC LIMIT 1");$norder=$subject206->norder; 
 	$exit206= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject206->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias206+12 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit206->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject206->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit206->id' ");  

	$subject207= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '207' ORDER BY id DESC LIMIT 1");$norder=$subject207->norder; 
 	$exit207= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject207->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias207+12.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit207->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject207->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit207->id' ");  

	$subject208= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '208' ORDER BY id DESC LIMIT 1");$norder=$subject208->norder; 
 	$exit208= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject208->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias208+13 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit208->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject208->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit208->id' ");  

	$subject209= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '209' ORDER BY id DESC LIMIT 1");$norder=$subject209->norder; 
 	$exit209= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject209->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias209+13.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit209->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject209->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit209->id' ");  

	$subject210= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '210' ORDER BY id DESC LIMIT 1");$norder=$subject210->norder; 
 	$exit210= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject210->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias210+14 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit210->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject210->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit210->id' ");  

	$subject211= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '211' ORDER BY id DESC LIMIT 1");$norder=$subject211->norder; 
 	$exit211= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject211->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias211+14.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit211->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject211->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit211->id' ");  

	$subject212= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '212' ORDER BY id DESC LIMIT 1");$norder=$subject212->norder; 
 	$exit212= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject212->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias212+15 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit212->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject212->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit212->id' ");  

	$subject213= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '213' ORDER BY id DESC LIMIT 1");$norder=$subject213->norder; 
 	$exit213= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject213->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias213+15.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit213->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject213->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit213->id' ");  

	$subject214= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '214' ORDER BY id DESC LIMIT 1");$norder=$subject214->norder; 
 	$exit214= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject214->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias214+16 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit214->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject214->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit214->id' ");  

	$subject215= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '215' ORDER BY id DESC LIMIT 1");$norder=$subject215->norder; 
 	$exit215= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject215->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias215+16.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit215->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject215->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit215->id' ");  

	$subject216= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '216' ORDER BY id DESC LIMIT 1");$norder=$subject216->norder; 
 	$exit216= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject216->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias216+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit216->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject216->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit216->id' ");  

	$subject217= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '217' ORDER BY id DESC LIMIT 1");$norder=$subject217->norder; 
 	$exit217= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject217->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias217+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit217->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject217->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit217->id' ");  

	$subject218= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '218' ORDER BY id DESC LIMIT 1");$norder=$subject218->norder; 
 	$exit218= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject218->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias218+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit218->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject218->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit218->id' ");  

	$subject219= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '219' ORDER BY id DESC LIMIT 1");$norder=$subject219->norder; 
 	$exit219= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject219->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias219+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit219->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject219->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit219->id' ");  

	$subject220= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '220' ORDER BY id DESC LIMIT 1");$norder=$subject220->norder; 
 	$exit220= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject220->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias220+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit220->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject220->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit220->id' ");  

	$subject221= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '221' ORDER BY id DESC LIMIT 1");$norder=$subject221->norder; 
 	$exit221= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject221->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias221+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit221->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','2','$subject221->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit221->id' ");  


 
// 내신미션

 
	$subject309= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '309' ORDER BY id DESC LIMIT 1");$norder=$subject309->norder; 
 	$exit309= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject309->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias309+13.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit309->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject309->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit309->id' ");  

	$subject310= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '310' ORDER BY id DESC LIMIT 1");$norder=$subject310->norder; 
 	$exit310= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject310->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias310+14 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit310->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject310->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit310->id' ");  

	$subject311= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '311' ORDER BY id DESC LIMIT 1");$norder=$subject311->norder; 
 	$exit311= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject311->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias311+14.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit311->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject311->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit311->id' ");  

	$subject312= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '312' ORDER BY id DESC LIMIT 1");$norder=$subject312->norder; 
 	$exit312= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject312->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias312+15 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit312->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject312->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit312->id' ");  

	$subject313= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '313' ORDER BY id DESC LIMIT 1");$norder=$subject313->norder; 
 	$exit313= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject313->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias313+15.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit313->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject313->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit313->id' ");  

	$subject314= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '314' ORDER BY id DESC LIMIT 1");$norder=$subject314->norder; 
 	$exit314= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject314->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias314+16 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit314->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject314->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit314->id' ");  

	$subject315= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '315' ORDER BY id DESC LIMIT 1");$norder=$subject315->norder; 
 	$exit315= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject315->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias315+16.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit315->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject315->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit315->id' ");  

	$subject316= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '316' ORDER BY id DESC LIMIT 1");$norder=$subject316->norder; 
 	$exit316= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject316->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias316+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit316->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject316->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit316->id' ");  

	$subject317= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '317' ORDER BY id DESC LIMIT 1");$norder=$subject317->norder; 
 	$exit317= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject317->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias317+17.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit317->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject317->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit317->id' ");  

	$subject318= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '318' ORDER BY id DESC LIMIT 1");$norder=$subject318->norder; 
 	$exit318= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject318->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias318+17.5 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime+604800*2); 
 	if($exit318->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject318->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit318->id' ");  

	$subject319= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '319' ORDER BY id DESC LIMIT 1");$norder=$subject319->norder; 
 	$exit319= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject319->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias319+18 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit319->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject319->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit319->id' ");  

	$subject320= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '320' ORDER BY id DESC LIMIT 1");$norder=$subject320->norder; 
 	$exit320= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject320->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias320+18 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit320->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject320->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit320->id' ");  

	$subject321= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '321' ORDER BY id DESC LIMIT 1");$norder=$subject321->norder; 
 	$exit321= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject321->id' ORDER BY id DESC LIMIT 1"); 

	$unixtime=($birthyear->data-$bias321+18 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime); 
 	if($exit321->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','3','$subject321->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit321->id' ");  

//수능미션

	$subject401= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '401' ORDER BY id DESC LIMIT 1");$norder=$subject401->norder; 
 	$exit401= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject401->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias401+17 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime-604800*3); 
 	if($exit401->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','4','$subject401->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit401->id' ");  

	$subject402= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '402' ORDER BY id DESC LIMIT 1");$norder=$subject402->norder; 
 	$exit402= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject402->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias402+18 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime-604800*3); 
	
 	if($exit402->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','4','$subject402->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated', complete=1 WHERE id='$exit402->id' ");  

	$subject403= $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE norder LIKE '403' ORDER BY id DESC LIMIT 1");$norder=$subject403->norder; 
 	$exit403= $DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$userid' AND subject LIKE '$subject403->id' ORDER BY id DESC LIMIT 1"); 
	$unixtime=($birthyear->data-$bias403+19 -1970)*86400*365;  // school years
	$strdeadline=date("Y/m/d", $unixtime-604800*3); 
	
 	if($exit403->id==NULL) $DB->execute("INSERT INTO {abessi_mission} (userid,msntype,subject,norder,deadline,complete,timecreated) VALUES('$userid','4','$subject403->id','$norder','$strdeadline','1','$timecreated')"); 
	else $DB->execute("UPDATE {abessi_mission} SET deadline='$strdeadline',timecreated='$timecreated' ,complete=1 WHERE id='$exit403->id' ");  

	}
 
if($eventid==43) // 점수, 등급 업데이트
	{ 
	//$univ=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='105' ");// 학교 
	//$nuniv=$univ->data;
	//$pathtype=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='106' ");// 커리큘럼 유형
	//$npath=$pathtype->data;
	//$daystr='day'.$npath.$nuniv;
 	//$birthyear = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='89' ");//출생년도 
	//내신중간고사, 모의 1학기
 	$biasp309=$_POST['biasp309'];  
 	$biasp310=$_POST['biasp310'];  
 	$biasp311=$_POST['biasp311'];  
 	$biasp312=$_POST['biasp312'];  
 	$biasp313=$_POST['biasp313'];  
 	$biasp314=$_POST['biasp314'];  
 	$biasp315=$_POST['biasp315'];  
 	$biasp316=$_POST['biasp316'];  
 	$biasp317=$_POST['biasp317'];  
 	$biasp318=$_POST['biasp318'];  
 	$biasp319=$_POST['biasp319'];  
 	$biasp320=$_POST['biasp320'];  
 	$biasp321=$_POST['biasp321'];  
 	$biasp401=$_POST['biasp401'];  
 	$biasp402=$_POST['biasp402'];  
 	$biasp403=$_POST['biasp403'];  
	//내신기말고사, 모의 2학기
 	$biasq309=$_POST['biasq309'];  
 	$biasq310=$_POST['biasq310'];  
 	$biasq311=$_POST['biasq311'];  
 	$biasq312=$_POST['biasq312'];  
 	$biasq313=$_POST['biasq313'];  
 	$biasq314=$_POST['biasq314'];  
 	$biasq315=$_POST['biasq315'];  
 	$biasq316=$_POST['biasq316'];  
 	$biasq317=$_POST['biasq317'];  
 	$biasq318=$_POST['biasq318'];  
 	$biasq319=$_POST['biasq319'];  
 	$biasq320=$_POST['biasq320'];  
 	$biasq321=$_POST['biasq321'];  
 	$biasq401=$_POST['biasq401'];  
 	$biasq402=$_POST['biasq402'];  
 	$biasq403=$_POST['biasq403']; 

 	$DB->execute("UPDATE {abessi_preset} SET m11='$biasp309',m12='$biasp310',m21='$biasp311',m22='$biasp312',m31='$biasp313',m32='$biasp314',h11='$biasp315',h12='$biasp316',h21='$biasp317',h22='$biasp318',h31='$biasp319',h32='$biasp320',h33='$biasp321',timemodified='$timecreated' WHERE userid='$userid' AND mtid=5 ");  
 	$DB->execute("UPDATE {abessi_preset} SET m11='$biasq309',m12='$biasq310',m21='$biasq311',m22='$biasq312',m31='$biasq313',m32='$biasq314',h11='$biasq315',h12='$biasq316',h21='$biasq317',h22='$biasq318',h31='$biasq319',h32='$biasq320',h33='$biasq321',timemodified='$timecreated' WHERE userid='$userid' AND mtid=6 ");  

	$DB->execute("UPDATE {abessi_preset} SET h11='$biasp401',h12='$biasq402',h21='$biasp403',h22='$biasq401',h31='$biasp402',h32='$biasq403', timemodified='$timecreated' WHERE userid='$userid' AND mtid=8 ");  

	// 내신미션 촉진 커리큘럼 자동 생성 알고리즘 적용

  
	}
?>

