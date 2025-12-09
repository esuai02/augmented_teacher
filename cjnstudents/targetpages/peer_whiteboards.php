<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
 
include("navbar.php");
echo ' 

						<div class="col-md-12">';

$tbegin=required_param('tb', PARAM_INT);

$totalgrade1=0;$totalgrade2=0;
$nstudents1=0;$nstudents2=0; 
$todayGrade1=0;$todayGrade2=0;
$ncheer=0;
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE suspended=0 ");
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
{
$userid=$user['id'];
$timestart=time()-43200;
$tbegin2=time()-604800;

$today1 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$userid' AND todayscore > 0  AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");   // 오늘 수업 평점
if($today1->id!=NULL)
	{
	$totalgrade1=$totalgrade1+$today1->todayscore; 
	 $nstudents1++;
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

$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");
 
$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$userid'   ORDER BY id DESC LIMIT 1 ");  // missionlog
$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
$engagement3 = $DB->get_record_sql("SELECT urgent, deadline,nask,nreply,speed,todayscore, tlaststroke FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
$deadline=$engagement3->deadline; //1 이상, 0 정상

if($deadline==0)$deadlineicon='N';
if($deadline==1)$deadlineicon='B';
if($deadline==2)$deadlineicon='G';
$teng1=time()-$engagement1->timecreated;
$teng2=time()-$engagement2->timecreated;
$teng3=time()-$engagement3->tlaststroke;  
$tlastseconds= min($teng1,$teng2,$teng3);
$tlastaction=(INT)(min($teng1,$teng2,$teng3)/60);
if($tlastaction<30)
{
$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20>';
if($tlastaction>1)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125316001.png" width=20>';
if($tlastaction>3)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125191001.png" width=20>';
if($goal->submit==1)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20>';
$ratio1=$engagement3->todayscore; 
 
// 현재 페이지 포착 
$stayfocused1=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=1 ORDER BY id DESC LIMIT 1 ");
$lastaction1= ((time()-$stayfocused1->timecreated)/60);
$url1=$stayfocused1->context.'?'.$stayfocused1->currenturl;
$stayfocused2=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=2 ORDER BY id DESC LIMIT 1 ");
$lastaction2=((time()-$stayfocused2->timecreated)/60);
$url2=$stayfocused2->context.'?'.$stayfocused2->currenturl;
$stayfocused3=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=3 ORDER BY id DESC LIMIT 1 ");
$lastaction3=((time()-$stayfocused3->timecreated)/60);
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
elseif(strpos($url3, 'pageid')!= false)$statetext3='개념'; 
else $statetext3='힌트'; 

$currentpage1='<a href="'.$url1.'" target="_blank">'.$statetext1.'</a>';
$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank">'.$statetext2.'</a>';
$currentpage3='<a href="'.$url3.'" target="_blank">'.$statetext3.'</a>';

if($lastaction1<=$lastaction2 && $lastaction1<=$lastaction3)$currentpage1='<a href="'.$url1.'" target="_blank"><b>'.$statetext1.'</b></a>';
if($lastaction2<=$lastaction1 && $lastaction2<=$lastaction3)$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank"><b>'.$statetext2.'</b></a>';
if($lastaction3<=$lastaction1 && $lastaction3<=$lastaction2)$currentpage3='<a href="'.$url3.'" target="_blank"><b>'.$statetext3.'</b></a>'; 
 
$currentpage=$currentpage1.'|'.$currentpage2.'|'.$currentpage3;
$cheerupText='<a href="'.$url3.'" target="_blank"><b>도와 주세요 !</b></a>'; 
$tlastaction2=min($lastaction1,$lastaction2,$lastaction3);
 
if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
if($ratio1==0 && $Qnum2==0) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';


//$statusimg  $currentpage $imgtoday
$timediff=time()-43200;
$goback='';
$finish=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE userid='$userid' AND timecreated > '$timediff'  AND type LIKE '귀가검사' ORDER BY id DESC LIMIT 1 "); 
if($finish->id!=NULL)$goback='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613279073001.png" width=20>';  // 귀가검사 준비

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
$role=$userrole->role;

$setgoal = $DB->get_record_sql("SELECT submit, min(timecreated) AS tmin FROM  mdl_abessi_today WHERE  userid='$userid' AND timecreated > '$timediff'  ");  // 목표입력 시간
$tgoal=$setgoal->tmin;
$tstudy=(INT)((time()-$tgoal)/3600);
if($tstudy>20)$tstudy='##';
if($setgoal->submit==1)$goback='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1613278711001.png" width=20>';  // 귀가검사 제출완료

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$wtimestart=time()-86400*($nday+3);
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$wtimestart' AND mindset LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
 
if($nday==1)$daystr='월요일';
if($nday==2)$daystr='화요일';
if($nday==3)$daystr='수요일';
if($nday==4)$daystr='목요일';
if($nday==5)$daystr='금요일';
if($nday==6)$daystr='토요일';
if($nday==7)$daystr='일요일';
$daytype='';
if($daystr===$schedule->lastday)$daytype='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1615957471001.png" width=25">';
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
$studentname=$username->firstname.$username->lastname.$daytype;

if($ratio1<90 && $ratio1>1 && $role==='student')
	{
	$ncheer++;
	$cheerup.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>....'.$cheerupText.'....</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
	$name='yourname'.$ncheer;
	$$name=$studentname;
	}
elseif((($interval<21 &&  $interval!=NULL ) || $engagement3->urgent==1 || ($ratio1<80 && $ratio1>1 ))  && $role==='student')
	{
	$onlineusers0.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
	}
elseif($tlastaction>30 && $role==='student')  // 30분 이상 활동없는 학생들
	{
 	$onlineusers1.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
 	}
elseif($role==='student' && $tlastseconds<20) $onlineusers2.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<40) $onlineusers3.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<60) $onlineusers4.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<120) $onlineusers5.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<180) $onlineusers6.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastseconds.'초|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<300) $onlineusers7.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<600) $onlineusers8.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds<900) $onlineusers9.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';
elseif($role==='student' && $tlastseconds>=1200) $onlineusers10.='<tr ><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >'.$msgicon.'</a></td><td>('.$tlastaction.'분|'.$tstudy.'h|'.$location.')'.$goback.'</td><td><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(1,\''.$userid.'\',this.checked)"/></td></tr>';

}
}
if($nstudents1>0 && $nstudents1 <300)$todayGrade1=(INT)($totalgrade1/$nstudents1);
if($nstudents2>0 && $nstudents2 <300)$todayGrade2=(INT)($totalgrade2/$nstudents2);		
$cheerNum=rand(1,$ncheer);
$name_tmp='yourname'.$cheerNum;
$yourname=$$name_tmp.' !';

if($todayGrade1>=90)$cheerupimg='<img src="http://mathking.kr/Contents/IMAGES/cheerup2.gif" width=250>';
else $cheerupimg='<img src="http://mathking.kr/Contents/IMAGES/cheerup1.gif" width=200>';


echo '
 
<table align=left valign=top width=100%>  <thead>
<tr><th scope="col" style="width: 10%;"></th>
<th scope="col" style="width: 40%;">We transfer intelligence  - KAIST TOUCH MATH (5분마다 새로고침)</th><th scope="col" style="width: 5%;"></th>
<th scope="col" style="width: 40%;">오늘 '.$todayGrade1.' %  | 주간 '.$todayGrade2.' %</th></tr><tr><td><hr></td><td><hr></td><td><hr></td></tr><tr >
<td  style="vertical-align: top;">
 
</td>

 <td  style="vertical-align: top; " align=left> 
 <table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$onlineusers0.''.$onlineusers2.''.$onlineusers3.$onlineusers4.''.$onlineusers5.''.$onlineusers6.''.$onlineusers7.''.$onlineusers8.''.$onlineusers9.''.$onlineusers10.''.$onlineusers1.'</table>
</td>
<td  style="vertical-align: top;">
 
</td>
<td  style="vertical-align: top;">
<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><tr><td>'.$cheerupimg.'</td><td  width=5%></td><td align=left style="font-size:20;">'.$yourname.' ! 힘내세요 ~<hr>다 함께 응원합니다 ^^</td></tr></table>
<hr><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$cheerup.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr></table>

</td> </tr></tbody></table>  
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