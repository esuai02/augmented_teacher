<?php 
 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
include("navbar.php");
echo ' 
		<div  class="main-panel" >
			<div class="content"  style="white-space:nowrap;overflow-x: scroll;" >
				<div class="row">
						<div class="col-md-12">';

$tbegin=required_param('tb', PARAM_INT);
$timecreated=time();
$totalgrade1=0;$totalgrade2=0;
$nstudents1=0;$nstudents2=0; 
$todayGrade1=0;$todayGrade2=0;
$timestart=$timecreated-43200;
$tdayago=$timecreated-86400;
$tbegin2=$timecreated-604800;

$stepimg1='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1618882038001.png" width=20>'; //white
$stepimg2='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1618975534001.png" width=20>'; //blue
$stepimg3='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1618882121001.png" width=20>'; //red
$stepimg4='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1618882175001.png" width=20>'; //green


$pstepimg4=$stepimg4;
$pstepimg3=$stepimg3;
$pstepimg2=$stepimg2;
$pstepimg1=$stepimg1;

$pWboard=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_messages WHERE  userrole LIKE 'student' AND timemodified>'$tbegin2' AND ( present=1 OR present=2 ) ORDER BY timemodified DESC LIMIT 1 ");

if($pWboard->id!=NULL)
	{
	//$pstepimg4='';
	//$pstepimg3='';
	//$pstepimg2='';
	$pstepimg1='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1620134788001.png" width=20>';
	}

$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE  lastaccess > '$tdayago' AND firstname LIKE '%$tsymbol%' AND suspended=0 ");   // manager 화면과 다른 부분
$result= json_decode(json_encode($mystudents), True);
$onlineusers='<table><tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>';
unset($user);
foreach($result as $user)
{
$userid=$user['id'];

$timeback=$timecreated-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  AND timecreated > $timeback AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");

$amountr=$checkgoal->amountr;
$amountn=$checkgoal->amountn;
$amountp=$checkgoal->amountp;
$rtext1='';$rtext2='';$rtext3='';$ntext1='';$ntext2='';$ntext3='';$ptext1='';$ptext2='';$ptext3='';
if($checkgoal->rtext1!=NULL)$rtext1='(복습) '.$checkgoal->rtext1;
if($checkgoal->rtext2!=NULL)$rtext2=' '.$checkgoal->rtext2;
if($checkgoal->rtext3!=NULL)$rtext3=' '.$checkgoal->rtext3;
if($checkgoal->ntext1!=NULL)$ntext1='(활동) '.$checkgoal->ntext1;
if($checkgoal->ntext2!=NULL)$ntext2='  '.$checkgoal->ntext2;
if($checkgoal->ntext3!=NULL)$ntext3=' '.$checkgoal->ntext3;
if($checkgoal->ptext1!=NULL)$ptext1='(발표) '.$checkgoal->ptext1;
if($checkgoal->ptext2!=NULL)$ptext2=' '.$checkgoal->ptext2;
if($checkgoal->ptext3!=NULL)$ptext3=' '.$checkgoal->ptext3;

$rtime0=$tgoal+$amountr*60;
$ntime0=$tgoal+$amountr*60+$amountn*60;
$ptime0=$tgoal+$amountr*60+$amountn*60+$amountp*60;

$rtime=date("h:i A", $rtime0);
$ntime=date("h:i A", $ntime0);
$ptime=date("h:i A", $ptime0);

$stepimage1='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$userid.'"target="_blank">'.$stepimg1.'</a>';
$stepimage2='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank">'.$stepimg1.'</a>';
$stepimage3='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$userid.'&tb=604800"target="_blank">'.$pstepimg1.'</a>';

if($amountr>0 && $checkgoal->rcomplete==1)$stepimage1='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$userid.'"target="_blank">'.$stepimg4.'</a>';
elseif($amountr>0 && $checkgoal->rcomplete==9)$stepimage1='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$userid.'"target="_blank">'.$stepimg3.'</a>';
elseif($timecreated>$rtime0 && $amountr>0 && $checkgoal->rcomplete==NULL)$stepimage1='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/fullengagement.php?id='.$userid.'"target="_blank">'.$stepimg2.'</a>';

if($amountn>0 && $checkgoal->ncomplete==1)$stepimage2='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank">'.$stepimg4.'</a>';
elseif($amountn>0 && $checkgoal->ncomplete==9)$stepimage2='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank">'.$stepimg3.'</a>';
elseif($timecreated>$ntime0 && $amountn>0 && $checkgoal->ncomplete==NULL)$stepimage2='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank">'.$stepimg2.'</a>';


if($amountp>0 && $checkgoal->pcomplete==1)$stepimage3='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$userid.'&tb=604800"target="_blank">'.$pstepimg4.'</a>';
elseif($amountp>0 && $checkgoal->pcomplete==9)$stepimage3='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$userid.'&tb=604800"target="_blank">'.$pstepimg3.'</a>';
elseif($timecreated>$ptime0 && $amountp>0 && $checkgoal->pcomplete==NULL)$stepimage3='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$userid.'&tb=604800"target="_blank">'.$pstepimg2.'</a>';
 
$tlastinput=$checkgoal->timecreated;
$tcomplete0=$ptime0;
$tcomplete=date("h:i A", $ptime0);
$setgoal = $DB->get_record_sql("SELECT submit, min(timecreated) AS tmin FROM  mdl_abessi_today WHERE  userid='$userid' AND timecreated > '$timediff'  ");  // 목표입력 시간
$tgoal=$setgoal->tmin;
$tstudy=round(($timecreated-$tgoal)/3600,1);

$daytype='<td></td>';
$today1 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid' AND todayscore > 0  AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");   // 오늘 수업 평점
if($today1->id!=NULL)
	{
	$totalgrade1=$totalgrade1+$today1->todayscore; 
	 $nstudents1++;
	if($today1->usedtime<80)$daytype='<td><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621591765001.png" width=20"></td>';
	}
  
$today2 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid' AND todayscore > 0 AND timecreated>'$tbegin2' ORDER BY id DESC LIMIT 1 ");   // 주간 수업 평점
	if($today2->id!=NULL)
	{
	$totalgrade2=$totalgrade2+$today2->todayscore; 
	$nstudents2++;
	}

$access=$DB->get_record_sql("SELECT ip FROM mdl_logstore_standard_log where userid='$userid' AND action='loggedin'  ORDER BY timecreated DESC LIMIT 1"); 
if(strpos($access->ip, '254.174')!= false || strpos($access->ip, '47.145')!= false || strpos($access->ip, '254.174')!= false)$location='KTM';
else  $location='외부';
 
$getinterval=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='72' "); 
$msgicon='<img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17>';
$interval=(INT)$getinterval->data;
if($interval<21 &&  $interval!=NULL)$msgicon='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1611914372001.png" width=19>';

$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
 
$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid' AND timecreated > '$timestart'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
$deadline=$engagement3->deadline; //1 이상, 0 정상

if($deadline==0)$deadlineicon='N';
if($deadline==1)$deadlineicon='B';
if($deadline==2)$deadlineicon='G';
$teng1=$timecreated-$engagement1->timecreated;
$teng2=$timecreated-$engagement2->timecreated;
$teng3=$timecreated-$engagement3->tlaststroke;  
$tlastseconds= min($teng1,$teng2,$teng3);
$tlastaction=(INT)(min($teng1,$teng2,$teng3)/60);
if($tlastaction<30)
{//
$statusimg='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$userid.'&tb=604800"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20></a></td>';
if($tlastaction>1)$statusimg='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$userid.'&tb=604800"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125316001.png" width=20></a></td>';
if($tlastaction>3)$statusimg='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$userid.'&tb=604800"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125191001.png" width=20></a></td>';
if($goal->submit==1)$statusimg='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$userid.'&tb=604800"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20></a></td>';
$ratio1=$engagement3->todayscore; 
 
// 현재 페이지 포착 
$stayfocused1=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=1 ORDER BY id DESC LIMIT 1 ");
$lastaction1= (($timecreated-$stayfocused1->timecreated)/60);
$url1=$stayfocused1->context.'?'.$stayfocused1->currenturl;
$stayfocused2=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=2 ORDER BY id DESC LIMIT 1 ");
$lastaction2=(($timecreated-$stayfocused2->timecreated)/60);
$url2=$stayfocused2->context.'?'.$stayfocused2->currenturl;
$stayfocused3=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=3 AND currenturl LIKE '%jnrsorksqcrark_user%'  ORDER BY id DESC LIMIT 1 ");
$lastaction3=(($timecreated-$stayfocused3->timecreated)/60);
$url3=$stayfocused3->context.'?'.$stayfocused3->currenturl;

if(strpos($url1, 'index')!= false)$statetext1='시작'.$deadlineicon; 
elseif(strpos($url1, 'schedule')!= false)$statetext1='일정'.$deadlineicon; 
elseif(strpos($url1, 'engagement')!= false)$statetext1='기억'.$deadlineicon; 
elseif(strpos($url1, 'today')!= false)$statetext1='오늘'.$deadlineicon;  
elseif(strpos($url1, 'mission')!= false)$statetext1='미션'.$deadlineicon; 
else $statetext1='관리'.$deadlineicon; 

if(strpos($url2, 'review')!= false)$statetext2='검토'; 
elseif(strpos($url2, 'attempt')!= false)$statetext2='응시'; 
elseif(strpos($url2, 'icontent')!= false)$statetext2='개념'; 
elseif(strpos($url2, 'checklist')!= false)$statetext2='목차'; 
else $statetext2='활동'; 

if(strpos($url3, 'nx4HQkXq')!= false)$statetext3='평가'; 
elseif(strpos($url3, 'realtime')!= false)$statetext3='풀이'; 
elseif(strpos($url3, 'tsDoHfRT')!= false)$statetext3='준비'; 
elseif(strpos($url3, 'cognitivesteps')!= false)$statetext3='단계'; 
elseif(strpos($url3, 'jnrsorksqcrark_user')!= false)$statetext3='개념'; 
else $statetext3='XX'; 

$currentpage1='<a href="'.$url1.'" target="_blank">'.$statetext1.'</a>';
$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank">'.$statetext2.'</a>';
$currentpage3='<a href="'.$url3.'" target="_blank">'.$statetext3.'</a>';

if($lastaction1<=$lastaction2 && $lastaction1<=$lastaction3)$currentpage1='<a href="'.$url1.'" target="_blank"><b>'.$statetext1.'</b></a>';
if($lastaction2<=$lastaction1 && $lastaction2<=$lastaction3)$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank"><b>'.$statetext2.'</b></a>';
if($lastaction3<=$lastaction1 && $lastaction3<=$lastaction2)$currentpage3='<a href="'.$url3.'" target="_blank"><b>'.$statetext3.'</b></a>'; 
 
$currentpage=$currentpage1.'|'.$currentpage2.'|'.$currentpage3;

$tlastaction2=min($lastaction1,$lastaction2,$lastaction3);
 
if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
 
if(  $engagement3->todayscore==NULL ||  $engagement3->todayscore==0) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';


//$statusimg  $currentpage $imgtoday
$timediff=$timecreated-43200;
$goback='';
$finish=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid' AND timecreated > '$timediff'  AND type LIKE '귀가검사' ORDER BY id DESC LIMIT 1 "); 
if($finish->id!=NULL)$goback='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613279073001.png" width=20>';  // 귀가검사 준비

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
$role=$userrole->role;


if($tstudy>20)$tstudy='##';
if($setgoal->submit==1)$goback='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613278711001.png" width=20>';  // 귀가검사 제출완료

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$wtimestart=$timecreated-86400*($nday+3);
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
 
if($nday==1)$daystr='월요일';
if($nday==2)$daystr='화요일';
if($nday==3)$daystr='수요일';
if($nday==4)$daystr='목요일';
if($nday==5)$daystr='금요일';
if($nday==6)$daystr='토요일';
if($nday==7)$daystr='일요일';





$todayplan='# <b>주간목표 : '.$weeklyGoal->text.'</b><hr> # 오늘목표 : '.$goal->text.' <hr>
<div class="container"  > 귀가시간 : '.$tcomplete.'  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$tstudy.' 시간 경과
   <hr>
    <h6> 복습하기 ... (예상시간 '.$amountr.'분 | '.$rtime.'까지)</h6>
    <ul class="sessions">
     <li>
        <div class="time">'.$rtext1.$rtext2.$rtext3.'</div>
      <p></p>
    </li>
     </ul><hr>
    <h6> 나아가기 ... (예상시간 '.$amountn.'분 | '.$ntime.'까지)</h6>
    <ul class="sessions">
      <li>
        <div class="time">'.$ntext1.$ntext2.$ntext3.'</div>
   <p></p>
      </li>
    </ul><hr>
    <h6> 정리하기 ... (예상시간 '.$amountp.'분 | '.$ptime.'까지)</h6>
    <ul class="sessions">
      <li>
        <div class="time">'.$ptext1.$ptext2.$ptext3.'</div>
      </li>
    </ul>
</div>  ';

if($daystr===$schedule->lastday)$daytype='<td><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1615957471001.png" width=20"></td>';
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
$studentname=$username->firstname.$username->lastname;

if($amountr>1 || $amountn>1 || $amountp>1) $onlineuser='<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a>';
else $onlineuser='<b><a href=https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.' target="_blank"><span style="color:red;">'.$studentname.'</span></a></b>';
if($weeklyGoal->id==NULL && $role==='student')
	{
	$setgoalfirst.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></a></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$userid.'"target="_blank"><u>주간목표 미설정</u></a></td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
	}
elseif((($interval<21 &&  $interval!=NULL ) || $engagement3->urgent==1 || ($ratio1<80 && $ratio1>1 ))  && $role==='student')
	{
	$onlineusers0.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
	}
elseif($tlastaction>30 && $role==='student')  // 30분 이상 활동없는 학생들
	{
 	$onlineusers1.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank"><div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></a></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
 	}
elseif($role==='student' && $tlastseconds<20) $onlineusers2.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td><td></td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<40) $onlineusers3.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td><td></td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<60) $onlineusers4.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<120) $onlineusers5.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<180) $onlineusers6.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<300) $onlineusers7.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<600) $onlineusers8.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<900) $onlineusers9.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds>=1200) $onlineusers10.='<tr ><td>'.$imgtoday.'<div class="tooltip6">'.$onlineuser.'<span class="tooltiptext6"><table align=center><tr><td>'.$todayplan.'<hr>질문수 : '.$engagement3->nask.' 응답수 :  '.$engagement3->nreply.'</td></tr></table></span></div></td>'.$daytype.'<td>'.$stepimage1.'</td><td>'.$stepimage2.'</td><td>'.$stepimage3.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';

}
}
if($nstudents1>0 && $nstudents1 <300)
	{
	$todayGrade1=(INT)($totalgrade1/$nstudents1);	
 	$DB->execute("UPDATE {abessi_indicators_class} SET quizscoretoday='$todayGrade1' WHERE id> 0 AND teacherid='$teacherid'  ORDER BY id DESC LIMIT 1 ");	
	}
if($nstudents2>0 && $nstudents2 <300)
	{
	$todayGrade2=(INT)($totalgrade2/$nstudents2);		
 	$DB->execute("UPDATE {abessi_indicators_class} SET quizscore='$todayGrade2'  WHERE id> 0 AND teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");	
	}
$tbegin=$timecreated-$tbegin;  
// 오래된 내용 포함
$handwriting=$DB->get_records_sql("SELECT  *  FROM mdl_abessi_messages WHERE userrole LIKE 'student' AND contentstype=2 AND  (teacher_check=0 OR teacher_check=1) AND timemodified>'$tbegin' AND active=1 AND status NOT LIKE 'attempt' ORDER BY timemodified DESC LIMIT 100 ");
 
$result= json_decode(json_encode($handwriting), True);
$wboardlist='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
$tracking='<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
$n1open=0;
$n2open=0; 
$n3open=0;
unset($value);
foreach($result as $value) 
{
//if($encryption_id!==$value['wboardid'] || ($encryption_id===$value['wboardid'] && $userid!==$value['userid']))
	{
	$userid=$value['userid'];
	$reviewer=$value['userto'];
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");  // for teachers
	$studentname=$username->firstname.$username->lastname; // for teachers
	if(strpos($username->firstname, $tsymbol)!== false)
		{ 
 
	$timestart=$timecreated-43200;
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");
	if($goal->submit==1)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20>';
		$nfeedback=$value['nfeedback'];
		$Q_id=$value['contentsid'];
		$contentstype=$value['contentstype'];
		$encryption_id='id='.$value['wboardid'];
		$source= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$userid' AND contentsid='$Q_id' AND contentstype='$contentstype' ORDER BY id DESC LIMIT 1 ");
		$tlastfeedback=(INT)(($timecreated-$source->timemodified)/60);
		$feedbacktext='<table align=left style="font-size=14;color:blue;">
		<tr><td>'.$source->feedback1.'<br></td></tr>
		<tr><td>'.$source->feedback2.'<br></td></tr>
		<tr><td>'.$source->feedback3.'<br></td></tr>
		<tr><td>'.$source->feedback4.'<br></td></tr>
		<tr><td>'.$source->feedback5.'<br></td></tr>
		<tr><td>'.$source->feedback6.'<br></td></tr>
		<tr><td>'.$source->feedback7.'<br></td></tr>
		<tr><td>'.$source->feedback8.'<br></td></tr>
		<tr><td>'.$source->feedback9.'<br></td></tr>
		<tr><td>'.$source->feedback10.'<br></td></tr>
		<tr><td>('.$tlastfeedback.' 분전)</td></tr>
		</table>';

		if($source->id!=NULL)$encryption_id=$source->url;
		$nstroke=(int)($value['nstroke']/2);
		$timeused=round((($value['tlast']-$value['tfirst'])/60),0);
		$tmodified=round(($timecreated-$value['timemodified'])/60,0);

		$myreview='';
		if($USER->id==$reviewer && $tmodified<20)$myreview='*';
		if($tmodified<120)$tmodified=$tmodified.'분'.$myreview;
		else $tmodified=round($tmodified/60,0).'시간'.$myreview;
		
		$status=$value['status'];
		$contentsid=$value['contentsid'];
		$cmid=$value['cmid'];

		$stepexist=$DB->get_record_sql("SELECT * FROM  mdl_abessi_cognitivesteps WHERE contentsid='$contentsid'  AND contentstype='$contentstype'  ");
		/*
		$hintexist=$DB->get_record_sql("SELECT * FROM  mdl_abessi_questions WHERE contentsid='$contentsid'  AND contentstype='$contentstype'  ");

		$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608438281001.png" width=15>';
		if($value['sent1']==1 && $value['sent2']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608443019001.png" width=15>';  // 모두 발송됨
		elseif($value['sent1']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441128001.png" width=15>';// 해석발송
		elseif($value['sent2']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441153001.png" width=15>';// 풀이발송
		elseif($hintexist->id!=NULL && $stepexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441373001.png" width=15>'; //모두존재
		elseif($hintexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441298001.png" width=15>'; //해석존재
		elseif($stepexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441336001.png" width=15>'; //풀이존재
		*/
		$checkstatus='';
		if($value['teacher_check']==1)
			{
			$checkstatus='checked'; 
		//	$engrate=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_indicators WHERE userid='$userid' ORDER BY id DESC LIMIT 1");  
		//  	$badnoterate=100-$engrate->engagement;
			}
 		$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid'   AND timecreated > '$timestart'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
	
 		$teng3=$timecreated-$engagement3->tlaststroke;  
 		if($teng3<180)$teng3=$teng3.'초';
		else $teng3=(INT)($teng3/60).'분';
 		$ratio1=$engagement3->todayscore; 
 		if($teng3 >720)$teng3='활동이탈';
		// 현재 페이지 포착 
 
		if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
		elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
		elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
		elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
		elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
		elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
		else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
		if($ratio1==0 || $engagement3->todayscore==NULL ||  $engagement3->todayscore==0) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';

		$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$studentname=$username->firstname.$username->lastname;
		if($value['contentstype']==2)
			{
			$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
			$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
			foreach($imageTags as $imageTag)
				{
    				$questionimg = $imageTag->getAttribute('src');
				$questionimg = str_replace(' ', '%20', $questionimg); 
				if(strpos($questionimg, 'MATRIX')!= false || strpos($questionimg, 'HintIMG')!= false)break;
				}
			$questiontext=$questionimg; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
			$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
			}
		else
			{
			$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
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
				if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
				} 
			$questiontext=$imgSrc; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
		 	$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$userid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
			}
		if($nstroke<3)
			{
			$timeused='#';
			$nstroke='#';
			}
	include("../whiteboard/status_icons.php");
	if(strpos($questiontext, "Contents/MATH")!=false)  
			{
			/*if($status==='attempt' && $nstroke > 10 && $nstroke < 500)    
				{
				$wboardid_prep='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$userid.'_'.date("Y_m_d", $value['timemodified']);
				$realtimewb.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$wboardid_prep.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$teng3.'<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td>'.$statusimg.' '.$currentpage.'</td><td><span style="color:skyblue;">'.$instruction.'</span></td></tr> ';
				}
			*/
			if($value['teacher_check']==0)
				{
				if($status==='begin' && $value['timemodified'] > $timestart)
					{
					$wboardid_prep='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$userid.'_'.date("Y_m_d", $value['timemodified']);
					$n0open++;
					$n0click=$n0open%5+1;
					if($n0click==1)$open01='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep;
					if($n0click==2)$open02='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep;
					if($n0click==3)$open03='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep;
					if($n0click==4)$open04='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep;
					if($n0click==5)$open05='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep;
					$wboardlist0.='<tr  ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
					<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid_prep.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$teng3.'<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';
					}
				elseif($status==='exam')$wboardlist1.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$teng3.'<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';
				elseif($status==='ask' || $status==='solutionask'|| $status==='studentreply' || $status==='askcorrection' ||$status==='submitstepbystep')$wboardlist2.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></span></td></tr> ';
				elseif($status==='complete' ||$status==='review')
					{
					$n1open++;
					$n1click=$n1open%5+1;
					if($n1click==1)$open1='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n1click==2)$open2='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n1click==3)$open3='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n1click==4)$open4='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n1click==5)$open5='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					 
					$wboardlist3.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
					<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';			
					}
			//	elseif($status==='reply' || $status==='solution'|| $status==='retry'||$status==='steps'||$status==='submitstepbystep'|| $status==='analysis'||$status==='first'||$status==='how'||$status==='topics'||$status==='expand'||$status==='solutionreply'||$status==='classroom' ||$status==='stepbystep')$wboardlist4.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
			//	<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';
				}
			elseif($value['teacher_check']==1 )
				{
				if($status==='begin' ||$status==='exam')$tracking1.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip3">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분<span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';

				if($status==='ask' || $status==='solutionask'|| $status==='studentreply' ||$status==='askcorrection' || $status==='submitstepbystep')$tracking2.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip3">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분'.$intervalicon.'<span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';

				if($status==='complete' ||$status==='review')
					{
					$n2open++;
					$n2click=$n2open%5+1;
					if($n2click==1)$open6='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n2click==2)$open7='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n2click==3)$open8='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n2click==4)$open9='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n2click==5)$open10='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;

					$tracking3.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
					<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip3">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분'.$intervalicon.'<span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';
					}
				elseif($status==='reply' || $status==='solution'|| $status==='retry'||$status==='steps'||$status==='submitstepbystep'|| $status==='analysis'||$status==='first'||$status==='how'||$status==='topics'||$status==='expand'||$status==='solutionreply'||$status==='classroom' ||$status==='stepbystep')
					{
					$n3open++;
					$n3click=$n3open%5+1;
					if($n3click==1)$open11='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n3click==2)$open12='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n3click==3)$open13='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n3click==4)$open14='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					if($n3click==5)$open15='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id;
					$tracking4.='<tr ><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800  target="_blank">'.$studentname.'</a></td>
					<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$encryption_id.'" target="_blank"><div class="tooltip3">'.$contentsready.'&nbsp;&nbsp;'.$timeused.'분'.$intervalicon.'<span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td></td><td></td></tr> ';
					}
 				}
			}
		} 
	}
}
echo '

 
<script>
var Open01= \''.$open01.'\'; 
var Open02= \''.$open02.'\'; 
var Open03= \''.$open03.'\'; 
var Open04= \''.$open04.'\'; 
var Open05= \''.$open05.'\'; 
var Open1= \''.$open1.'\'; 
var Open2= \''.$open2.'\'; 
var Open3= \''.$open3.'\'; 
var Open4= \''.$open4.'\'; 
var Open5= \''.$open5.'\'; 
var Open6= \''.$open6.'\'; 
var Open7= \''.$open7.'\'; 
var Open8= \''.$open8.'\'; 
var Open9= \''.$open9.'\'; 
var Open10= \''.$open10.'\'; 
var Open11= \''.$open11.'\'; 
var Open12= \''.$open12.'\'; 
var Open13= \''.$open13.'\'; 
var Open14= \''.$open14.'\'; 
var Open15= \''.$open15.'\'; 

</script>
<table align=center valign=top >  <thead>
<tr><th scope="col" style="width: 33%;">KTM 순공유도 시스템  (오늘 접속자 : '.$nstudents1.'명) </th>
<th scope="col" style="width: 33%;"> 학습추적 </th>
<th scope="col" style="width: 34%;">온라인 (최근 30분)| 상호작용 | 마지막 활동 | 공부시간</th></tr><tr><td><hr></td><td><hr></td><td><hr></td></tr><tr > <td  style="vertical-align: top; "><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$wboardlist0.$wboardlist1.$realtimewb.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td></td></tr>'.$wboardlist4.'</table>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="#"  onclick="window.open(Open01);window.open(Open02);window.open(Open03);window.open(Open04); window.open(Open05); " >여러 개 열기 0</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="#"  onclick="window.open(Open1);window.open(Open2);window.open(Open3);window.open(Open4); window.open(Open5); " >여러 개 열기 1</a></td><td  style="vertical-align: top;">
<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$tracking1.$tracking2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$tracking3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$tracking4.'</table><hr>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#"  onclick="window.open(Open6);window.open(Open7);window.open(Open8);window.open(Open9); window.open(Open10); " >여러 개 열기 2</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#"  onclick="window.open(Open11);window.open(Open12);window.open(Open13);window.open(Open14); window.open(Open15); " >여러 개 열기 3</a></td><td  style="vertical-align: top;"><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$setgoalfirst.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers0.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers2.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers3.$onlineusers4.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers5.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers6.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers7.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers8.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers9.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers10.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr>'.$onlineusers1.'</table></td> </tr></tbody></table>  
 '; 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
echo '</div>
<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
</p></div><div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"><p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>
<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
</div></div></div></div>
 


<script>
function ChangeCheckBox(Eventid,Userid,Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
    	             "eventid":Eventid,
     	           "checkimsi":checkimsi,
    	           },
  	      success: function (data){  
    	    }
	    });
	}
function askstudent(Eventid,Studentid,Teacherid,Questionid)
	{
    	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"studentid":Studentid,
		"teacherid":Teacherid,
		"contentsid":Questionid,       	   
		      },
	 	success:function(data){
		}
	})
	}
</script> 
  
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
';
 
echo '
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 600px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 800px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
}
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}

 

</style>';
include("quicksidebar.php");
?>