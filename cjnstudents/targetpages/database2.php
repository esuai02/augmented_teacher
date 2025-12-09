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
$DB->execute("INSERT INTO {abessi_mission} (userid,text,deadline,complete,timecreated) VALUES('$userid','$inputtext','$deadline','0','$timecreated')");
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
	if($msntype==4) $DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,startdate,userid,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$weekhours','$startdate','$userid','0','$timecreated')");
	else $DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,chstart,weekhours,startdate,userid,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$hours','$chstart','$weekhours','$startdate','$userid','0','$timecreated')");
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
	$DB->execute("UPDATE {abessi_mission} SET timemodified='$timecreated', nchange='$nchange', dday1='$dday1', dday2='$dday2', dday3='$dday3', dday4='$dday4', dday5='$dday5', dday6='$dday6', dday7='$dday7', dday8='$dday8', dday9='$dday9', dday10='$dday10', dday11='$dday11', dday12='$dday12', dday13='$dday13', dday14='$dday14', dday15='$dday15' WHERE userid='$userid' AND subject='$subject' ORDER BY id DESC LIMIT 1  "); 
	}
if($eventid==14) // 미션 조건 변경하기
	{
	$dday11= $_POST['dday11'];
	$dday12= $_POST['dday12'];
	$dday13= $_POST['dday13'];
	$dday14= $_POST['dday14'];
	$dday15= $_POST['dday15'];

	$nchange=$mission->nchange+1;
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
	$DB->execute("INSERT INTO {abessi_mission} (msntype,subject,grade,text,hours,chstart,weekhours,startdate,userid,complete,timecreated) VALUES('$msntype','$subject','$grade','$inputtext','$hours','$chstart','$weekhours','$startdate','$userid','0','$timecreated')");
 
	}
if($eventid==16) //내신 미션 수정
	{
	$dday11= $_POST['dday11'];
	$dday12= $_POST['dday12'];
	$dday13= $_POST['dday13'];
	$dday14= $_POST['dday14'];
	$dday15= $_POST['dday15'];

	$nchange=$mission->nchange+1;
	$DB->execute("UPDATE {abessi_mission} SET  timemodified='$timecreated', weekhours='$weekhours', grade='$grade', startdate='$startdate'  WHERE id='$idcreated' "); 
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
	$DB->execute("UPDATE {abessi_mission} SET timemodified='$timecreated', nchange='$nchange', grade1='$grade1', grade2='$grade2', grade3='$grade3', grade4='$grade4', grade5='$grade5', grade6='$grade6', grade7='$grade7', grade8='$grade8', grade9='$grade9', grade10='$grade10',grade11='$grade11',grade12='$grade12',grade13='$grade13',  dday1='$dday1', dday2='$dday2', dday3='$dday3', dday4='$dday4', dday5='$dday5', dday6='$dday6', dday7='$dday7', dday8='$dday8', dday9='$dday9', dday10='$dday10' , dday11='$dday11', dday12='$dday12', dday13='$dday13'  WHERE userid='$userid' AND subject='$subject'  "); 
	}
}

if($eventid==2) // input today's goal, weekly goal & mission
{
//$savetime = $_POST['savetime'];
//$DB->execute("INSERT INTO {abessi_today} (text,userid,complete,mindset,timemodified,timecreated) VALUES('$inputtext','$userid','0','$mindset','$timecreated','$timecreated')");
$type= $_POST['type'];
$DB->execute("INSERT INTO {abessi_today} (text,userid,type,complete,mindset,timemodified,timecreated) VALUES('$inputtext','$userid','$type','0','$mindset','$timecreated','$timecreated')");
} 

if($eventid==21) // 귀가 요청을 위한 활동 자가진단 제출
{
$complete=$_POST['complete'];
$time=$_POST['time'];
$ask = $_POST['ask'];
$reply = $_POST['reply'];
$DB->execute("UPDATE {abessi_today} SET type='검사', comment='$inputtext',complete='$complete',checktime='$time',ask='$ask',reply='$reply', submit='1', timemodified='$timecreated'   WHERE userid='$userid' AND mindset LIKE '오늘목표'  ORDER BY id DESC LIMIT 1  "); 
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

if($duration1>0.1)$lastday='월요일';
if($duration2>0.1)$lastday='화요일';
if($duration3>0.1)$lastday='수요일';
if($duration4>0.1)$lastday='목요일';
if($duration5>0.1)$lastday='금요일';
if($duration6>0.1)$lastday='토요일';
if($duration7>0.1)$lastday='일요일';
 
$DB->execute("INSERT INTO {abessi_schedule} (editnew,lastday,start1,start2,start3,start4,start5,start6,start7,start11,start12,start13,start14,start15,start16,start17,duration1,duration2,duration3,duration4,duration5,duration6,duration7,memo1,memo2,memo3,memo4,memo5,memo6,memo7,memo8,memo9,date,complete1,complete2,complete3,complete4,complete5,complete6,complete7,userid,timecreated) VALUES('0','$lastday','$start1','$start2','$start3','$start4','$start5','$start6','$start7','$start11','$start12','$start13','$start14','$start15','$start16','$start17','$duration1','$duration2','$duration3','$duration4','$duration5','$duration6','$duration7','$memo1','$memo2','$memo3','$memo4','$memo5','$memo6','$memo7','$memo8','$memo9','$date','0','0','0','0','0','0','0','$userid','$timecreated')");
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
?>

