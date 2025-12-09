<?php 

require_once("/home/moodle/public_html/moodle/config_abessi.php"); 
 
global $DB,$USER;
$studentid=$_GET["id"]; 
if($studentid==NULL)$studentid=$USER->id;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
  
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1");
$tgoal=$checkgoal->timecreated;

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
 
$schedule=$DB->get_record_sql("SELECT id,editnew, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
	 
if($nday==1){$tstart=$schedule->start1; $hours=$schedule->duration1;} 
if($nday==2){$tstart=$schedule->start2; $hours=$schedule->duration2;} 
if($nday==3){$tstart=$schedule->start3; $hours=$schedule->duration3;} 
if($nday==4){$tstart=$schedule->start4; $hours=$schedule->duration4;} 
if($nday==5){$tstart=$schedule->start5; $hours=$schedule->duration5;} 
if($nday==6){$tstart=$schedule->start6; $hours=$schedule->duration6;} 
if($nday==7){$tstart=$schedule->start7; $hours=$schedule->duration7;} 
 
$tcomplete0=$tgoal+$hours*3600;
$tcomplete=date("h:i ", $tcomplete0);
$timestart=date("h:i ", $tgoal);

$activitylog=$DB->get_records_sql("SELECT * FROM mdl_logstore_standard_log WHERE  userid='$studentid' AND  component NOT LIKE 'core' AND timecreated > '$initialT' AND   timecreated < '$finalT'  AND courseid NOT LIKE '239' ORDER BY id DESC ");  
$result = json_decode(json_encode($activitylog), True);
//include("../teachers/shortcuts.php");
$timeline=NULL; 
unset($value);
$n10=0;  
foreach( $result  as $value)
{
$tdiff=$value['timecreated']-$tprev;
 
if($tdiff>600 && $tdiff<43200) // 5분이상 부터 측정
	{
	$n10++;   
	}

$mark='';
$timecreated= date("h시i분 ", $value['timecreated']); 

if($value['action']==='loggedin') $timeline.='<li><div class="timeline-badge success"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">로그인</h4></div>
<div class="timeline-body"><p>'.$timecreated.'</p></div></div></li>';

if($value['component']==='mod_quiz' &&  $value['action']==='started'  )
{
$attemptid=$value['objectid'];
$atmptinfo=$DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ORDER BY id DESC ");  
$quizid=$atmptinfo->quiz;
$quizinfo=$DB->get_record_sql("SELECT * FROM mdl_quiz WHERE id='$quizid' ORDER BY id DESC ");  
$quiztitle=$quizinfo->name;

 $timeline.='<li><div class="timeline-badge  primary"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title"><b style="color:red;">'.$quiztitle.' 시작 </b></h4> '.$timecreated.' </div></div></li>';

}
if($value['component']==='mod_quiz' &&  $value['action']==='reviewed'  )
{
$attemptid=$value['objectid'];
 $timeline.='<li><div class="timeline-badge  primary"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title"><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'"target="_blank">퀴즈검토</a></h4> '.$timecreated.' </div></div></li>';
}
if($value['component']==='mod_quiz' && $value['action']==='viewed' && $value['target']==='attempt')
{

 $timeline.='<li class="timeline-inverted"><div class="timeline-badge info"></div><div class="timeline-panel"><div class="timeline-body"><h4 class="timeline-title">제출'.$mark.'</h4> '.$timecreated.'</div></div></li>';
 
} 
if($value['component']==='mod_quiz' && $value['action']==='submitted') 
{
$timeline.='<li><div class="timeline-badge warning"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">시험종료'.$mark.'</h4></div><div class="timeline-body"><p> '.$timecreated.'</p></div></div></li>';
}
if($value['component']==='mod_icontent' && $value['action']==='viewed' ) 
{
$timeline.='<li class="timeline-inverted"><div class="timeline-badge light"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title"> 개념공부'.$mark.'</h4>  '.$timecreated.' </div></div></li>';
 
}
if($value['component']==='mod_hotquestion' ) 
{
 $timeline.='<li class="timeline-inverted"><div class="timeline-badge info"></div><div class="timeline-panel"><div class="timeline-body"><h4 class="timeline-title">노트필기 활동'.$mark.'</h4> '.$timecreated.'</div></div></li>';
  
}
if($value['action']==='loggedout')
{
 $timeline.='<li><div class="timeline-badge success"></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">로그아웃'.$mark.'</h4></div><div class="timeline-body"><p> '.$timecreated.' 이번 주 공부시간 : </p></div></div></li>';
 
}
$tprev=$value['timecreated'];
}
 
$amonthago=time()-604800*4;
 $quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.timefinish > '$amonthago' AND mdl_quiz_attempts.userid='$studentid' ORDER BY  mdl_quiz_attempts.id DESC ");
$quizresult = json_decode(json_encode($quizattempts), True);
 
unset($value2); 	
foreach($quizresult as $value2) 
	{
	$comment='';
	$qnum=substr_count($value2['layout'],',')+1-substr_count($value2['layout'],',0');   //if($role!=='student')
	$quizgrade=round($value2['sumgrades']/$value2['tgrades']*100,0);
	if(strpos($value2['name'], 'ifmin')!== false)$quiztitle=substr($value2['name'], 0, strpos($value2['name'], '{'));
	else $quiztitle=$value2['name'];
	if($quizgrade>85)
		{
		$imgstatus='O';
		}
	else continue;
  
 	if($qnum>9)  //$todayGrade  $ntodayquiz  $weekGrade  $nweekquiz
		{
		$quizlist.='<tr><td valign=top> '.$imgstatus.'&nbsp;'.date("m/d",$value2['timestart']).' </td><td valign=top> '.substr($quiztitle,0,60).'</a>  ('.$value2['attempt'].get_string('trial', 'local_augmented_teacher').')  </td><td valign=top><span class="" style="color: rgb(239, 69, 64);"> '.$quizgrade.'점</span></td><td valign=top> 통과 !</td></tr>';
		} 
	}
 
if($tstart==NULL || $hours==NULL)$beginendtext='오늘은 수업이 없는 날입니다. 시간표를 클릭하시면 주간 일정을 확인하실 수 있습니다.';
else $beginendtext='시작 ('.$timestart.') | 귀가시간 ('.$tcomplete.') ';

$introtext='<h4 align=center class="page-title">'.$username->firstname.$username->lastname.'의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800">학습목표</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200"><b><u>오늘활동</b></u> </a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistorym.php?studentid='.$studentid.'">  메타인지  </a> |<a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1">시간표</a></h4>
<h4 align=center class="page-title">'.$beginendtext.'</h4>
<table align=center>'. $quizlist.'</td></table>';




$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by deadline DESC LIMIT 20");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text=$value['memo'];										
	$deadline= $value['deadline'];    
	$dateString = date("m-d",$deadline);
	$checkbox='';
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';

	if($plantype==='분기목표')$plantype='<b style="color:red;">분기목표</b>  : ';
	elseif($plantype==='방향설정')$plantype='<b style="color:green;">진행순서</b>  : ';
	 
	if($value['plantype']==='장기계획')$timeline1.='<h4 class="timeline-title">'.$plantype.''.$text.''.$dateString.'</h4>';
	else $timeline1.='<h4 class="timeline-title">'.$plantype.''.$text.''.$dateString.'</h4>';
 
	} 

 ///////////////////////////////////////////////// 오늘 목표 

$Weekly=$DB->get_record_sql("SELECT min(timecreated) AS tmin FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated > '$initialT' AND type LIKE '주간목표'  ");  
$amonthago=time()-604800*4;
$WeekTimeline=$DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated >= '$amonthago' ORDER BY id  ");  
$result = json_decode(json_encode($WeekTimeline), True);
include("../teachers/shortcuts.php");
$timeline=NULL; 
unset($value);
 
foreach($result as $value)
{
$timecreated= date("m월 d일", $value['timecreated']); 
$showdate=date("m_d", $value['timecreated']); 
$goalid=$value['id'];
 
if($value['type']==='오늘목표' || $value['type']==='검사요청') 
	{
	$timeline.='# '.$value['type'].' : '.$value['text'].'  '.$timecreated.'<hr>';
	}
if($value['type']==='주간목표') $timeline.='<h6><b style="color:blue;"># '.$value['type'].' : '.$value['text'].' </b> '.$timecreated.'</h6><hr>';     


}
echo $timeline; // <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWhiteboard.php?id='.$studentid.'&tb=604800">  풀이노트  </a> |
 
 
/////////////// 메타인지


//////////////// 시간표

    
$timeplan = $DB->get_records_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' AND pinned=1 ORDER BY timecreated DESC LIMIT 1 ");
$result = json_decode(json_encode($timeplan), True);
$index=0;
foreach($result as $value)
{
$index++;
if($index==$nedit)
	{
	$weektotal=$value['duration1']+$value['duration2']+$value['duration3']+$value['duration4']+$value['duration5']+$value['duration6']+$value['duration7'];
	$edittime=date('m/d',$value['timecreated']);
	$startdate=$value['date'];
	$start1=$value['start1'];
	$start2=$value['start2'];
	$start3=$value['start3'];
	$start4=$value['start4'];
	$start5=$value['start5'];
	$start6=$value['start6'];
	$start7=$value['start7'];

	$start11=$value['start11'];
	$start12=$value['start12'];
	$start13=$value['start13'];
	$start14=$value['start14'];
	$start15=$value['start15'];
	$start16=$value['start16'];
	$start17=$value['start17'];

	$schtype=$value['type'];
	if($schtype==NULL)$schtype='기본';
	if($start1=='12:00 AM')$start1=NULL;
	if($start2=='12:00 AM')$start2=NULL;
	if($start3=='12:00 AM')$start3=NULL;
	if($start4=='12:00 AM')$start4=NULL;
	if($start5=='12:00 AM')$start5=NULL;
	if($start6=='12:00 AM')$start6=NULL;
	if($start7=='12:00 AM')$start7=NULL; 

	if($start11=='12:00 AM')$start11=NULL;
	if($start12=='12:00 AM')$start12=NULL;
	if($start13=='12:00 AM')$start13=NULL;
	if($start14=='12:00 AM')$start14=NULL;
	if($start15=='12:00 AM')$start15=NULL;
	if($start16=='12:00 AM')$start16=NULL;
	if($start17=='12:00 AM')$start17=NULL; 

	$duration1=$value['duration1'];
	$duration2=$value['duration2'];
	$duration3=$value['duration3'];
	$duration4=$value['duration4'];
	$duration5=$value['duration5'];
	$duration6=$value['duration6'];
	$duration7=$value['duration7'];

	if($duration1==0)$duration1=NULL;
	if($duration2==0)$duration2=NULL;
	if($duration3==0)$duration3=NULL;
	if($duration4==0)$duration4=NULL;
	if($duration5==0)$duration5=NULL;
	if($duration6==0)$duration6=NULL;
	if($duration7==0)$duration7=NULL;

	$memo1=$value['memo1'];
	$memo2=$value['memo2'];
	$memo3=$value['memo3'];
	$memo4=$value['memo4'];
	$memo5=$value['memo5'];
	$memo6=$value['memo6'];
	$memo7=$value['memo7'];
	$memo8=$value['memo8'];
	$memo9=$value['memo9'];
	}
}
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0);
	$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));
	$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 ORDER BY timecreated DESC LIMIT 1 ");
	if($nday==1)$untiltoday=$schedule->duration1;
	if($nday==2)$untiltoday=$schedule->duration1+$schedule->duration2;
	if($nday==3)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;
	if($nday==4)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;
	if($nday==5)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;
	if($nday==6)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;
	if($nday==0)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
  
	if($duration1>0)echo'월요일 '.$start1.'에 시작하여 '.$duration1.'동안 수업이 있습니다.';
    if($duration2>0)echo'화요일 '.$start2.'에 시작하여 '.$duration2.'동안 수업이 있습니다.';
    if($duration3>0)echo'수요일 '.$start3.'에 시작하여 '.$duration3.'동안 수업이 있습니다.';
    if($duration4>0)echo'목요일 '.$start4.'에 시작하여 '.$duration4.'동안 수업이 있습니다.';
    if($duration5>0)echo'금요일 '.$start5.'에 시작하여 '.$duration5.'동안 수업이 있습니다.';
    if($duration6>0)echo'토요일 '.$start6.'에 시작하여 '.$duration6.'동안 수업이 있습니다.';
    if($duration7>0)echo'일요일 '.$start1.'에 시작하여 '.$duration7.'동안 수업이 있습니다.';
    echo '일주일에 총 '.$weektotal.'시간 공부 중이며 현재 '.round($Ttime->totaltime,1).'만큼 공부하였습니다.';  
	 
?>
