<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
global $DB, $USER;
include("navbar.php");
  
$tbegin= $_GET["tb"]; 
$maxtime=time()-$tbegin; 

$timecreated=time();

// get mission list
$timestart2=time()-$tbegin;
$adayAgo=time()-43200;
$aweekAgo=time()-604800;
$timestart3=time()-86400*14;
  
if($ntodayquiz!=0)$todayqAve=$todayGrade/($ntodayquiz);
else $todayqAve=-1;
if($nweekquizall!=0)$weekqAve=$weekGrade/($nweekquizall);
else $weekqAve=-1; 
$ngrowth=$nweekquiz+$ntodayquiz;
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET todayquizave='$todayqAve', ngrowth='$ngrowth', weekquizave='$weekqAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
$amonthago=$timecreated-604800*4;

 
 
$reviewwb0.= '<a href="https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&mode=remind" target=_blank">기억인출 훈련 (1개월 전)</a> &nbsp;';
	 
 

if($tbegin==604800)$wboardScoreAve=(INT)($wboardScore/$nwboard/5*100);
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;

$wtimestart=time()-86400*($nday+3);
$Timelastaccess=$DB->get_record_sql("SELECT timecreated AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
$lastaction=time()-$Timelastaccess->maxtc;
$weeklyGoal2= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");

$inputtime=date("m/d", $weeklyGoal2->timecreated); 

$lastday=$schedule->lastday;
$weekdays = array(
    'Sun' => '7',
    'Mon' => '1',
    'Tue' => '2',
    'Wed' => '3',
    'Thu' => '4',
    'Fri' => '5',
    'Sat' => '6'
);
 
$time2=time()-43200;  
$attendtoday = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page='studenttoday' ORDER BY id DESC LIMIT 1");
if($attendtoday->timecreated < time2)
	{
	$start='start'.$nday;
	$timestart=$schedule->$start;

	$todaybegin=strtotime($timestart);
	if($todaybegin<$timecreated && $USER->id==$studentid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$studentid','attendance','지각가능','$timecreated')");
	elseif($USER->id==$studentid) $DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$studentid','attendance','ontime','$timecreated')");
	}

	$timeToday=time()-$todaybegin;
	
	$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7+$weeklyGoal->penalty/60;
	if($nday==1)  {if($timeToday/3600>$schedule->duration2)$timeToday=$schedule->duration2*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1;}
	if($nday==2) {if($timeToday/3600>$schedule->duration2)$timeToday=$schedule->duration2*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==3) {if($timeToday/3600>$schedule->duration3)$timeToday=$schedule->duration3*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==4) {if($timeToday/3600>$schedule->duration4)$timeToday=$schedule->duration4*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==5) {if($timeToday/3600>$schedule->duration5)$timeToday=$schedule->duration5*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==6) {if($timeToday/3600>$schedule->duration6)$timeToday=$schedule->duration6*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==7) {if($timeToday/3600>$schedule->duration7)$timeToday=$schedule->duration7*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$timeToday/3600+$weeklyGoal->penalty/60;}
 
$untiltoday=round($untiltoday,1);	
if($untiltoday>1000)$untiltoday=1;

$ncompleteratio=$ncomplete/($nreview+$ncomplete)*100;
$nquestion=$engagement3->nask/10*100;
$nreply=$engagement3->nreply/10*100;

$timefilled=round($engagement3->totaltime/($untiltoday+0.0001)*100,0);
$timefilled2=round($engagement3->totaltime/($weektotal+0.0001)*100,0);
if($timefilled>20000)$timefilled=100;

$appraise_result=round($totalappraise/($nappraise*5+0.001)*100,0);
	 
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET appraise='$appraise_result', usedtime='$timefilled', wbscore='$wboardScoreAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
else $DB->execute("UPDATE {abessi_indicators} SET tinspect='$timecreated' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

$tbegin2=time()-604800;
$tbegin3=time()-86400; 

if($weeklyGoal->id!=NULL)$weeklyGoalText=$weeklyGoal->text; 
if($weeklyGoal->penalty>0)$addtime='<b style="color:red;"> (보충 '.$weeklyGoal->penalty.'분) </b> ';
$drawing2=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='weekly' ORDER BY id DESC LIMIT 1 ");
$drawingid=$drawing2->wboardid;

$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
$summaryid=$summary->wboardid;

if($checkgoal->drilling==1)$alertimg='(<img loading="lazy" style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/exist.gif width=20>)';
else $alertimg='';
$goalid=$checkgoal->id; 
 
$goaldisplay= '<b style="font-size:16px;">'.$lastday.'</b>까지 목표가 "<span style="color:red;font-size:16px;">'.$weeklyGoal->text.'</span>" 이어서 오늘은 <span style="color:red;font-size:16px;">"'.$checkgoal->text.'"</span>(을)를 목표로 정진 중입니다. ';
$mindset=$checkgoal->mindset; 
//$inspector=$checkgoal->teacherid;
$Confidence=$checkgoal->complete;
 

if($checkgoal->result/$checkgoal->pcomplete>1)$evaluateResult='<span sytle="color:green;">주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 수고하셨습니다 ! </span>';
elseif($checkgoal->result/$checkgoal->pcomplete>0.7)$evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다.  당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 학습속도를 향상시킬 수 있는 방법에 대해 고민해 보시기바랍니다.';
else $evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 계획이 위태롭습니다. 선생님과 주간목표 수정에 대해 상의해 주세요 !';

if($checkgoal->submit==1)$text='<span  style="font-size:16;"> <b>※ 계획</b> : '.$checkgoal->text.'</span>';

$hide=$checkgoal->hide;
$inspectToday =$checkgoal->inspect;
$date=gmdate("h:i A", $checkgoal->timecreated+32400);
 
if($inspectToday==1)$status='checked';    
elseif($inspectToday==2)$status4='checked';    
elseif($inspectToday==3)$status5='checked';  
if($role!==student)$editgoal='<a href="https://mathking.kr/moodle/local/augmented_teacher/student/edittoday.php?id='.$studentid.'&mode=CA">입력</a>';

$btnname='질문하기';$bgcolor='green';
if($timecreated-$checkgoal->alerttime<43200)
	{
	$btnname='답변 대기중';
	$bgcolor='orange';
	}
$btnname2='도움요청';$bgcolor2='green';
if($timecreated-$checkgoal->alerttime2<43200)
	{
	$btnname2='도움 대기중';
	$bgcolor2='orange';
	}
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
if($checkgoal->type==='오늘목표')$todolist='<tr style=" border-top:5px solid #88c2fc;border-bottom:5px solid #88c2fc;"><td></td><td><b style="font-size:20;">오늘목표</b></td><td><div><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summaryid.'"target="_blank"><img loading="lazy" src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>&nbsp;&nbsp;'.$goaldisplay.' &nbsp;</div></td><td align=center></td><td>'.$date.'</td><td></td><td style="color:green;" width=7%>DMN휴식<input type="checkbox" name="checkAccount"  '.$status4.' onClick="Resttime(33,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/></td> <td style="color:green;" width=7%> 책/프린트<input type="checkbox" name="checkAccount"  '.$status5.' onClick="ChangeCheckBox(333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/>  </td><td width=7%></td></tr>';
elseif($checkgoal->type==='검사요청')$todolist='<tr style=" border-top:5px solid #88c2fc;border-bottom:5px solid #88c2fc;"><td></td><td><b style="font-size:16;"><a href="https://mathking.kr/moodle/local/augmented_teacher/student/goinghome.php?id='.$studentid.'&period=1" target="_blank">활동결과</a></b></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summaryid.'"target="_blank"><img loading="lazy" src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=20></a>&nbsp;&nbsp;'.$goaldisplay.' </td><td align=center></td><td width=40%>'.$evaluateResult.'  ※ 질문수('.$checkgoal->ask.')</td><td>  귀가보류 <input type="checkbox" name="checkAccount"  '.$status5.' onClick="ContinueLearn(3333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/>  </td></tr>';
else $todolist='<tr style=" border-top:3px solid #88c2fc;"><td></td><td style="font-size:16;" align=center> 오늘 목표가 설정되지 않았습니다. <span style="color:white;font-size:16;">'.$editgoal.'</span></td><td></td></tr>';
$wgoalid=$wgoal->id;
$wstatus=''; 
if($wgoal->inspect==1)
	{
	$wstatus='checked';
	}
 
 echo ' <div class="row"><div class="col-md-12"><div class="card"><div class="card-body"> 
<table  align=center  width=100% class="table table-head-bg-primary mt-8"><tbody>  '.$todolist.' </tbody> </table>   ';
// 귀가요청 표시부

if($timefilled<60)$bgtype='danger';
elseif($timefilled<80)$bgtype='warning';
else $bgtype='success';

if($timefilled<60)$bgtype='danger';
elseif($timefilled<80)$bgtype='warning';
else $bgtype='success';

if($timefilled2<60)$bgtype2='danger';
elseif($timefilled2<80)$bgtype2='warning';
else $bgtype2='success';

$stateColor1='primary'; 
$stateColor2='primary'; 
$stateColor3='primary'; 
if($username->state==1)$stateColor1='Default'; 
if($username->state==2)$stateColor2='Default'; 
if($username->state==0)$stateColor3='Default'; 
 
if($timefilled>=100)$result_time='충분히';
elseif($timefilled>=80)$result_time='대부분';
else  $result_time='부족함';

if($engagement3->nask>=5)$result_question='충분히';
elseif($engagement3->nask>=1)$result_question='필요한 만큼';
else $result_question='부족함';
 
$check_reply=$nwrong+$ngaveup-$ncomplete-$nreview;
if($check_reply<=0)$result_reply='완료';
else $result_reply='미완료';

$NNnask=$engagement3->nask;
$NNreview=$nreview;
$Ncheckreply=$check_reply;
 
 
$totaltime=$engagement3->totaltime;

$topicrate=round($engagement3->topictime/$totaltime*100,0);
$solrate=round(($engagement3->soltime+$engagement3->quiztime)/$totaltime*100,0);
$fixrate=round($engagement3->fixtime/$totaltime*100,0);
$fixexamtime=round($engagement3->fixexamtime/$totaltime*100,0);
$memorytime=round($engagement3->memorytime/$totaltime*100,0);

$totalfixrate=$fixrate+$fixexamtime+$memorytime;

$topicrate2=30;
$solrate2=40;
$fixrate2=30;
/*
$stepquestion= $DB->get_records_sql("SELECT * FROM mdl_abessi_questionstamp WHERE userid='$studentid' AND (status LIKE '질문' || status LIKE '답변')  AND timemodified >'$halfdayago'  ORDER BY id DESC LIMIT 10");

$qstamps = json_decode(json_encode($stepquestion), True);
unset($value);
 
foreach($qstamps as $value)
{
$qstatus=$value['status']; $qwbid=$value['wboardid'];  $qplayindex=$value['playindex']; $qgid=$value['gid']; $eventtime=round(($timecreated-$value['timemodified'])/60,0);
$qlist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$qwbid.'&gid='.$qgid.'&playindex='.$qplayindex.'&playstate=0&sketchstate=0&speed=3&mode=qstamp&studentid='.$studentid.'"target="_blank">'.$qwbid.'</a>('.$eventtime.'분)</td><td></td><td>'.$qstatus.'</td></tr>';
}
*/
// 활동 설계부
$todayplan='<table width=100%><tr><td><br> <br> ▶ 질의응답</b></td><td><br> <br> <button   type="button"  style = "font-size:16;background-color:'.$bgcolor.';color:white;border:0;outline:0;" onClick="quickReply(313,\''.$studentid.'\',\''.$goalid.'\')" >'.$btnname.'</button></td><td><br> <br> <button   type="button"  style = "font-size:16;background-color:'.$bgcolor2.';color:white;border:0;outline:0;" onClick="quickReply2(314,\''.$studentid.'\',\''.$goalid.'\')" >'.$btnname2.'</button></td></tr>'.$qlist.'</table><br>';
//ㄹㅇㅁㄹ

if($role!=='student')$teacherButton1='<b> <button   type="button"   id="alert_addtime" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >보강추가</button>  </b>';
$synapsePower=$sumSynapse/(100*$nsynapse)*100;
$goalprogress=' 
<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">분기목표 성취도</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
$siprogress='<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">기억 회복력</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
//$instructionToday='<div class="container" ><table width=100%><tr><td><b style="color:#216feb;">▶ 몰입피드백 </b> &nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/student/flowhistory.php?studentid='.$studentid.'"target="_blank"> ('.$mcstatus.' | '.$fbtime3.' )</a></td> </tr><tr><td><br><b><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/drilling.png width=20> 오늘집중</b> : '.$lastcfeedback1.'<hr> &nbsp; <img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667730907.png width=25> &nbsp; '.$lastcfeedback3.'<hr style="border: solid 2px skyblue;"> <b>추천</b> : '.$lastcfeedback2.'</td></tr></table>';

if($nday>=$weekdays[date('D')] && $checkgoal->comment==NULL)$placeholder='새로운 주간 목표와 다음 목표를 입력해 주세요';
elseif($checkgoal->comment==NULL)$placeholder='다음 시간 목표를 입력해 주세요';
else $placeholder=$checkgoal->comment;

if($checkgoal->comment!=NULL)$commenttext=$checkgoal->comment;
if($tremain<0)$plustime='<button type="button"   onclick="updatetime2(94,'.$studentid.','.$tremain.')">적용</button>';
else $plustime='';
echo '<table  width=100% valign=top style="border: 3px solid skyblue;"><tr><td width=5%></td>	<td width=35% valign=top><br> <br> <b>▶ 활동시간 데이터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;학교시험 D -'.$leftDays.'일 <span class="text-muted">'.$mission->grade.'점 목표</span>('.$mission->startdate.')</b> 	<br>					 
<div class="card-body"><div class="row"><div class="col-md-12"><div class="progress-card"> 
<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">오늘까지 (개념 '.round(($untiltoday-$engagement3->quiztime)/$untiltoday*100,0).'%)&nbsp;<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width=15></a> </span>
<span class="text-muted fw-bold">총 '.$untiltoday.'시간 '.$addtime.'</span></div><div class="progress"><div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar" style="width: '.$timefilled.'%" aria-valuenow="'.$timefilled.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.round($timefilled,1).'%"></div>
</div></div></div><div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">이번 주 &nbsp;<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width=15></a> </span>
<span class="text-muted fw-bold">총 '.round($weektotal,1).'시간 </span></div><div class="progress"><div class="progress-bar progress-bar-striped bg-'.$bgtype2.'" role="progressbar" style="width: '.$timefilled2.'%" aria-valuenow="'.$timefilled2.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.round($timefilled2,1).'%"></div>
</div></div></div></div></div>
<hr><b>▶ 활동 유형별 시간분포 </b>
<table width=100%><tr><td><div class="demo"><div class="progress-card"><div class="container" width=100% ><div class="progress" style="height:0px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate2.'%;">  </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate2.'%;">   </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$fixrate2.'%;">     </div>  </div>
</div> </div>
<div class="container" width=100% ><div class="progress" style="height:15px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate2.'%;"> 개념 </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate2.'%;">  풀이 </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$fixrate2.'%;">   오답  </div>  </div>
</div> </div> 

<div class="container" width=100% ><div class="progress" style="height:30px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate.'%;">  '.$topicrate.'% </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate.'%;">   '.$solrate.'% </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$fixrate.'%  </div> 
<div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$fixexamtime.'%  </div>
<div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$memorytime.'%  </div>
 </div></div> </div></div> </td> </tr></table></td>
  
<td width=10%><td width=40% valign=top>'.$todayplan.' <hr><table width=100%><tr><td width=36%><a href="https://mathking.kr/moodle/local/augmented_teacher/student/logicalstairway.php?id='.$studentid.'&tb=2419200"target="_blank"><b style="color:black;">▶ 보충학습 '.$alertimg.' </b></a></td><td width=33%>'.$teacherButton1.'</td><td width=33%></td></tr></table><hr><table width=100% valign=top>'.$instruction1.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr>'.$instruction2.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr></table><br><table><tr><td><b>▶ 사용 가능시간 <span style="color:blue;"> '.$tleft.' </span></b>'.$plustime.'</td></tr><tr><td> <br> <br> </td></tr></table></td></td><td width=10%></td>
</tr></table><br> '; //: '.$tcomplete.'

$pcomplete=round($untiltoday/$weektotal*100,0);

$recentlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason  LIKE 'addperiod'  ORDER by id DESC LIMIT 1 " );
if($recentlog->id!=NULL)$passedhours=round($recentlog->tamount*($timecreated-$recentlog->doriginal)/($recentlog->dchanged-$recentlog->doriginal),1);

$attendlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason NOT LIKE 'addperiod' ORDER BY id DESC LIMIT 1  " );
$doriginal=date("Y-m-d",$attendlog->doriginal); $dchanged=date("Y-m-d",$attendlog->dchanged);

$tamounttotal=$attendlog->tupdate+$passedhours;
$attendancetext='&nbsp; # 남은 보강 <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style="color:black; font-size:14pt" href="https://mathking.kr/moodle/local/augmented_teacher/student/schedule.php?id='.$studentid.'&eid=1&nweek=4">예기치 못한 휴강상황을 위하여 + 5시간 이상을 권합니다.</a> ';
if($tamounttotal<=-5 && $attendlog->id !=NULL )$attendancetext='&nbsp; <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;<a style="color:red; font-size:16pt" href="https://mathking.kr/moodle/local/augmented_teacher/student/schedule.php?id='.$studentid.'&eid=1&nweek=4">보강시간을 정해주세요 !</a> <img loading="lazy" src="https://mathking.kr/Contents/IMAGES/exist.gif" width=40>';

$todayrecord='<table width=100%><tr style="background-color:#96c7ff;"> <td width=3%></td>  <td width=10% align=left style="font-size:12pt">'.$attendlog->type.'</td><td width=10%  align=left style="font-size:12pt">'.$attendlog->reason.'</td><td  width=10% align=left style="font-size:12pt">계획  '.$doriginal.'</td><td width=10%  align=left style="font-size:12pt">변경 '.$dchanged.'</td> <td align=left style="font-size:12pt"><table>'.$attendlog->text.'</table></td>  <td align=right>'.$attendancetext.'</td></tr></table>';

$Rach='rate'.$checkgoal->result;
$$Rach='selected';

echo '<div class="card-header" style="background-color:#96c7ff">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center >';

$achrate='<td  style="width: 3%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"></td></td>
<td style="width: 10%;height:18px;"><div class="select2-input"><select id="basic1" name="basic" class="form-control" > <option>주간목표 진행율</option><option value="10" '.$rate10.'>10%</option> <option value="20" '.$rate20.'>20%</option> <option value="30" '.$rate30.'>30%</option> <option value="40" '.$rate40.'>40%</option> <option value="50" '.$rate50.'>50%</option> <option value="60" '.$rate60.'>60%</option> <option value="70" '.$rate70.'>70%</option> <option value="80" '.$rate80.'>80%</option> <option value="90" '.$rate90.'>90%</option> <option value="100" '.$rate100.'>100%</option></select></div></td>
<td style="width: 2%;"></td>'; 

echo '<td style="width: 30%;height:16px;"><div  style="font-size:16px;border-radius: 12px;"><input type="text"  style="font-size:16px;border-radius: 12px;" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'" value="'.$commenttext.'" ></div></td>'.$achrate.'<td style="width:5%;font-size: 20px; "><button id="clicksubmit" type="button" onclick="submittoday(21,'.$studentid.','.$pcomplete.',$(\'#basic1\').val(),$(\'#squareInput\').val())">귀가검사</button></td><td style="font-size: 20px;width: 2%; text-align:center;"></td> <td style="text-align:center;font-size: 16px; width: 15%;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><div style="text-align:center;font-size: 20px; " class="tooltip2">보강차감 '.$reducetime.' 분 <span class="tooltiptext2"><table style="" align=center>'.$eventtext.'</table></span><button type="button"   onclick="updatetime(93,'.$studentid.','.$reducetime.','.$tamounttotal.')">적용</button></div></td></tr></table><hr style="background:white;height:2px;">'.$todayrecord.'</div></div>  <br>';
 
echo '<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>시험결과 및 오답노트 현황</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table> <br> <br>  
<table align=center valign=top width=100%><thead> 
<tr>
<th scope="col"><a href=https://mathking.kr/moodle/local/augmented_teacher/student/today.php?id='.$studentid.'&tb=43200>오늘 테스트 결과 </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/student/today.php?id='.$studentid.'&tb=604800>최근 1주일</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/student/today.php?id='.$studentid.'&tb=2592000>최근 1개월</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/student/today.php?id='.$studentid.'&tb=7776000>최근 3개월</a></th>
<th scope="col" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">오답 '.($nwrong+$ngaveup).' &nbsp; &nbsp; 예약 '.$nreview.' | 완료 '.$ncomplete.'  &nbsp; &nbsp;<b style="color:red;"> 도전 '.$appraise_result.'</b>  &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/student/imagegrid.php?id='.$studentid.'&ndays=7"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624791079001.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&mode=sol"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656132615.png width=25></a>
 &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655957315.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&mode=ltm"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1657015275.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replaycjn.php?studentid='.$studentid.'"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658012742.png width=25></a>&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&studentid='.$studentid.'&mode=retry"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658042078.png width=25></a></th>
</tr><tr ><td  style=" vertical-align: top;"><hr><b>준비학습</b> <br><br>'.$reviewwb0.$quizlist00.'<hr><b>내신테스트</b>.....분석'.$nmaxgrade1.'.....'.round(($totalmaxgrade1-$totalquizgrade1)/(100*$nmaxgrade1+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist11.$quizlist12.'</table><hr><b>표준테스트</b>.....분석'.$nmaxgrade2.'.....'.round(($totalmaxgrade2-$totalquizgrade2)/(100*$nmaxgrade2+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist21.$quizlist22.'</table><hr><b>인지촉진</b>.....분석'.$nmaxgrade3.'.....'.round(($totalmaxgrade3-$totalquizgrade3)/(100*$nmaxgrade3+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist31.$quizlist32.'</table></td>  <td  style=" vertical-align: top; "><hr><table align=center width=90%><tr><td>고민지점 점검 </td><td> 3곳 이상인 경우 삭제 후 다시 풀기 <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?userid='.$studentid.'"target="_blank">분석</a></td> </tr></table><hr><table  style="">'.$wboardlist0.' <tr><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$reviewwb.$reviewwb2.' <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'</table></td></tr></tbody></table>
<br><hr><br>';

echo '
	<script>

	function showList(Studentid)
		{
		Swal.fire({
		  position:"top",showCloseButton: true,width:900,
		  html:  \'<iframe style="border: 1px none; z-index:2; width:900; height:600;  margin-left: -50px; margin-top: -10px; "  src="https://mathking.kr/moodle/local/augmented_teacher/student/cognitiveRecent.php?userid=\'+Studentid+\'&tb=43200"></iframe>\',
		  showConfirmButton: false,
		        })
		}	
	function showWboard(Wbid)
		{
		Swal.fire({
		backdrop: false,position:"top-left",showCloseButton: true,width:800,
		  html:
		    \'<iframe style="border: 1px none; z-index:2; width:1200; height:900;  margin-left: -100px; margin-top: -130px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_review.php?id=\'+Wbid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
	</script>';

 

if($role!=='student')
{
  
echo '
<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>코스 및 스케줄 정보 (선생님용)</b> </td></tr></table>
<table  style="" width="100%" valign="top"><tr><th valign="top" width="70%">';
include("schedule_embed.php"); 

echo '</th><th valign="top"  width="30%">';
include("index_embed.php"); 
echo '</th></tr></table>';

}
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/student/imagegrid.php?id='.$studentid.'&ndays=1'; 
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today';																	   
echo '</div> </div></div></div></div></div></div>';
	
include("quicksidebar.php");
echo '<script>
function deletequiz(Attemptid)
	{
		swal({
					title: \'시도된 퀴즈를 삭제하시겠습니까 ?\',
					text: "원하지 않으시면 취소 버튼을 눌러주세요",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'취소\',
							className: \'btn btn-danger\'
						}      			

					}
		}).then((willDelete) => {
					if (willDelete) {
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"eventid":\'300\',
						"attemptid":Attemptid,
					 		},
						 });
					setTimeout(function() {location.reload(); },100);
					} else {
					swal("취소되었습니다.", {buttons: false,timer: 500});
					}
				});	 				 
	}
function addquiztime(Attemptid)
	{		 
 
			var text1="-30";
			var text2="-20";
			var text3="-10";
			var text4="-5";
			var text5="+5";
			var text6="+10";
			var text7="+20";
			var text8="+30";
			var text9="입력";

			swal("퀴즈 시간변경",  "응시시간을 적절히 늘리거나 줄이면 집중력이 향상됩니다.",{
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-success\'
			    },
			    catch6: {
			      text: text6,
			      value: "catch6",className : \'btn btn-success\'
			    },
			    catch7: {
			      text: text7,
			      value: "catch7",className : \'btn btn-success\'
			    },
			    catch8: {
				text: text8,
				value: "catch8",className : \'btn btn-success\'
				  },
				catch9: {
				text: text9,
				value: "catch9",className : \'btn btn-secondary\'
				  },
			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-alert\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
		 
 			   case "catch1":
				swal("","퀴즈종료 시간이 " + text1+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text1,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
				swal("","퀴즈종료 시간이 " + text2+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text2,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
				swal("","퀴즈종료 시간이 " + text3+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text3,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch4":
				swal("","퀴즈종료 시간이 " + text4+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text4,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
				 case "catch5":
					swal("","퀴즈종료 시간이 " + text5+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text5,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch6":
					swal("","퀴즈종료 시간이 " + text6+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text6,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
				 case "catch7":
					swal("","퀴즈종료 시간이 " + text7+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text7,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch8":
					swal("","퀴즈종료 시간이 " + text8+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text8,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
 			   case "catch9":
				swal({
					title: \'퀴즈 추가시간을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "시간입력 (분)",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("","퀴즈종료 시간이 " + Inputtext+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'301\',
					"inputtext":Inputtext,	
					"attemptid":Attemptid,				 
					},
					success:function(data){
					 }
					 })
				});	 
				 
				break;
 			   
			}
		})
	}
function updatetime(Eventid,Userid,Selecttime,Totaltime)
	{   
	if(Totaltime>=5)
		{
		alert("차감 가능한 보강시간이 없습니다. 대신 10분 일찍 귀가 가능합니다." );
		}
	else
		{
		var Inputtext= \''.$eventtext.'\';
		alert("총" + Selecttime + "분이 보강시간에서 차감됩니다. 내역은 다음과 같습니다. (" + Inputtext + ")" );
		swal("적용되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
				type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "selecttime":Selecttime,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		}
	}
	function updatetime2(Eventid,Userid,Tremain)
	{   
	 
	swal("보강시간이 " + Tremain + "분 추가되었습니다", {buttons: false,timer: 1000});
			$.ajax({
				url:"database.php",
			type: "POST",
				dataType:"json",
			data : {
			"eventid":Eventid,
			"userid":Userid,
			"tremain":Tremain,
					},
				success:function(data){
	
							}
			})
   		 
	}


function ChangeCheckSteps(Eventid, Userid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	swal({title: \'안전하게 전달하였습니다.\',});	
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
	data : {
	 "eventid":Eventid,
	"userid":Userid,       
	"checkimsi":checkimsi,
	},
	})
	location.reload();
	 		 				 
	}
function submittoday(Eventid,Userid,Pcomplete,Confident,Inputtext)
	{  
	swal("귀가검사 제출","수고하셨습니다. 귀가검사 및 지면 평가를 진행해 주세요", {buttons: false,timer: 2000});
	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
		data : {
				"userid":Userid,
				"eventid":Eventid,
				"pcomplete":Pcomplete, // 시간사용
				"confident":Confident, // 달성율
				"inputtext":Inputtext,
				},
			success:function(data)
				{

				}
		})

	}




		function RubricCheckBox(Eventid,Userid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 
		   $.ajax({
		        url: "checkrubric.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		}	 

		function ChangeCheckBox(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
		    var Nextgoal=\''.$checkgoal->comment.'\';
		    if(Eventid==3 && Nextgoal=="" && Checkvalue==true)
				{
				swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
				location.reload(); 
				}
		    else
				{
				if(Checkvalue==true){
					checkimsi = 1;
					}
					swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
					});
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
									"goalid":Goalid,
									"checkimsi":checkimsi,
									"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	 
				} 
		 
		}
		function ContinueLearn(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
			if(Checkvalue==true){
				checkimsi = 1;
				}
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"checkimsi":checkimsi,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
				swal("귀가검사 결과 보충활동이 발견되었습니다.", {buttons: false,timer: 3000});
				 location.reload(); 
		 
		}
		function quickReply(Eventid,Userid,Goalid){
		 
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
					
					var Alerttime= \''.$checkgoal->alerttime.'\';
					if(Alerttime==0)swal("질문이 전달되었습니다.","기다리는 동안 후속 학습을 진행해 주세요.", {buttons: false,timer: 3000});
					else swal("피드백을 시작합니다.","충분히 이해가 될 수 있도록 유연하게 대화해 보세요", {buttons: false,timer: 3000});
					location.reload(); 
		 
		}
		function quickReply2(Eventid,Userid,Goalid){
		 
			$.ajax({
				url:"check.php",
				type: "POST",
				dataType:"json",
				data : {
				"userid":Userid,       
				"goalid":Goalid,
				"eventid":Eventid,
							 
				},
				success:function(data){
				 }
			})	
			
			var Alerttime= \''.$checkgoal->alerttime2.'\';
			if(Alerttime==0)swal("메세지가 전달되었습니다.","기다리는 동안 다른 활동을 진행해 주세요.", {buttons: false,timer: 3000});
			else swal("요청을 완료합니다.","안내받은 내용에 따라 활동을 계속해주세요", {buttons: false,timer: 3000});
			location.reload(); 
 
		}
		function Resttime(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    var Timeleft= \''.$beforebreak.'\';
			var TimebeforeFinish= \''.$tremain.'\';
			
		    if(Checkvalue==true)
				{
				checkimsi = 1;
				if(Timeleft<0)
					{
					Swal.fire({
					backdrop: true,position:"top-center",width:1200,
					  customClass: {
									container: "my-background-color"
								   },
					html:
					\'<table align=center ><tr><td align=center><br><h5><b>정보입력이 멈춘 상태의 DMN 휴식</b>을 취하면 공부가 가속화됩니다 ! (<a href="https://brunch.co.kr/@kissfmdj/1"target="_blank">자세히</a>) </h5><br></td></tr><tr><td><iframe style="border: 1px none; z-index:2; width:60vw;height:50vh; margin-left: -30px;margin-top: 0px;"   src="https://e.ggtimer.com/10minutes" ></iframe></td></tr></table>\',
					})
					
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
							data : {
							"userid":Userid,       
										"goalid":Goalid,
										"checkimsi":checkimsi,
										"eventid":Eventid,
										 
							},
							success:function(data){
							 }
						})	 
				
					}
				else if(TimebeforeFinish<30)
					{
					swal("귀가시간이 다가 오고 있어요. 마무리 활동 후 귀가검사를 준비해 주세요 ^^", {buttons: false,timer: 3000});
					setTimeout(function() {location.reload(); },3000);
					}
				else 
					{
					swal("힘내세요 ! " + Timeleft + "분 더 공부하시면 휴식을 취하실 수 있습니다.", {buttons: false,timer: 3000});
					setTimeout(function() {location.reload(); },1000);
					}				
	 			}
	 		else
				{
				swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
						});
				if(Timeleft<0)
						{
						$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":Eventid,
											 
								},
								success:function(data){
								 }
							})
						}
					else
							{
							
							$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":\'331\',
											 
								},
								success:function(data){
								 }
							})	
						}
				}
				
		}
		function ChangeCheckBoxWeek(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    if(Checkvalue==true)
				{
		        checkimsi = 1;
				swal({
					title: \'한 주간 공부과정에 대한 한줄 평을 남겨주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
  					swal("", "입력된 내용 : " + Inputtext, "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"userid":Userid,       
		                		"goalid":Goalid,
		                		"checkimsi":checkimsi,
		                 		"eventid":Eventid,
		                 		"inputtext":Inputtext,
					},
					success:function(data){
					 }
					 })
				 
				}
				);
			  }
		}
		function ChangeCheckBox2(Eventid,Userid, Wboardid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "wboardid":Wboardid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}
		function AddReview(Eventid,Userid,Attemptid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "attemptid":Attemptid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}		
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});

		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );
	</script>
'; 
?>
<html>
 
<style>
.my-background-color .swal2-container {
  background-color: black;
}

.feel {
  margin: 0px 5px;
  background-color: white;
  height:30px;
}
</style>
</html>
