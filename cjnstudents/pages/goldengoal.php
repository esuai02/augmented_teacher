<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include("navbar.php");
 

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentedittoday','$timecreated')");
 
//if($role!=='student')$stepbutton='';

 
$nweek= $_GET["nweek"]; 
$mode= $_GET["mode"]; 
$gtype= $_GET["gtype"]; 
 
if(strpos($gtype, '주간목표')!==false) $selectgtype2='selected';
else $selectgtype1='selected';

if($nweek==NULL)$nweek=15;
$timestart=$timecreated-604800*2;

$aweekago=$timecreated-604800; 
//$shine=$DB->get_record_sql("SELECT *  FROM mdl_abessi_reflection  WHERE userid='$studentid' AND timecreated > '$aweekAgo' ORDER BY id DESC LIMIT 1 ");
//if($shine->id==NULL)$DB->execute("INSERT INTO {abessi_reflection} (userid, timemodified, timecreated) VALUES('$studentid','$timecreated','$timecreated')  ");
if($timecreated-$username->lastaccess>43200)$DB->execute("UPDATE {user} SET lastlogin='$timecreated' WHERE id LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");  

$nnn=1;
$goals= $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' ORDER BY id DESC ");
$adayAgo=time()-43200;
$result2 = json_decode(json_encode($goals), True);
unset($value);
 
foreach($result2 as $value)
	{
	$date_pre=$date;
	$att=gmdate("m월 d일 ", $value['timecreated']+32400);
	$date=gmdate("d", $value['timecreated']+32400);
	$goaltype=$value['type'];
  	if($goaltype==='오늘목표' || $goaltype==='검사요청'){$goaltype='<span style="color:black;">오늘목표</span>';$notetype='summary';}
	elseif($goaltype==='주간목표'){$goaltype='<b style="color:#bf04e0;">주간목표</b>';$notetype='weekly';}
	elseif($goaltype==='시험목표'){$goaltype='<b style="color:blue;">분기목표</b>';$notetype='examplan';}
 

	$daterecord=date('Y_m_d', $value['timecreated']);  	 
	$tend=$value['timecreated'];
	 
	$tfinish0=date('m/d/Y', $value['timecreated']+86400); 
 	$tfinish=strtotime($tfinish0);

	if($nnn==1 && ($value['type']==='오늘목표' || $value['type']==='검사요청'))
		{
		$goaltype='<b style="color:red;">지난시간</b>';
		$goalhistory0.= '<tr height=30 style="background-color:#b8fcfc;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
		<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>';
		$nnn++;	 
		}
	else $goalhistory1.= '<tr><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
	<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641865738.png" width=20></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.'_user'.$studentid.'_date'.$daterecord.'" target="_blank">'.substr($value['text'],0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>';
	}
 
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE (mdl_quiz_attempts.timefinish > '$aweekago' OR mdl_quiz_attempts.timestart > '$aweekago' OR (state='inprogress' AND mdl_quiz_attempts.timestart > '$aweekago') ) AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.timestart ");
$quizresult = json_decode(json_encode($quizattempts), True);
$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0;  $ntodayquiz=0;  $weekGrade=0;  $nweekquiz=0;
unset($value); 	
foreach(array_reverse($quizresult) as $value) 
{
$comment='';
$qnum=substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');   //if($role!=='student')
$comment= '&nbsp;|&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">결과분석</a>';
	$quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);
	 
	if($quizgrade>89.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
		}
	elseif($quizgrade>69.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
		}
	else $imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
	$quizid=$value['quiz'];
	$moduleid=$DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'  "); 
	$quizmoduleid=$moduleid->id;

	$attemptid=$value['id'];
	$quizattempt= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid'");
	$maxgrade=$quizattempt->maxgrade;
	if(strpos($value['name'], '내신')!= false)  
	{
	if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
		{
		$quizlist11.= '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$value['name'].'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'  </span> <br>';
		$todayGrade=$todayGrade+$quizgrade;
		$ntodayquiz++;
		}
	else 
		{
		$quizlist12.= '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$value['name'].'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').' </span><br>';
		$weekGrade=$weekGrade+$quizgrade;
		$nweekquiz++;
		}
 	}elseif($qnum>9)  //$todayGrade  $ntodayquiz  $weekGrade  $nweekquiz
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
		{
		$quizlist21.=  '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').' </span><br>';
		$todayGrade=$todayGrade+$quizgrade;
		$ntodayquiz++;
		}
	else 
		{
		$quizlist22.=  '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'  </span><br>';
		$weekGrade=$weekGrade+$quizgrade;
		$nweekquiz++;
		}
	}else
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)$quizlist31.= '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span><br>';
	else $quizlist32.= '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span><br>';
	}
}

 
/*$todayplan=' <div class="col-md-7"><table width=90% align=center><tr><td><h6>시간분배</h6></td><td><h6><button   type="button"   id="alert_timeA"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(복습 '.$amountr.'분) <button   type="button"   id="alert_timeB"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(활동 '.$amountn.'분)   <button   type="button"   id="alert_timeC"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(정리 '.$amountp.'분) </td>
<td><button   type="button"   id="alert_flywheel"  style = "background-color:white;color:black;border:0;outline:0;" ><h6>개선 <img style="padding-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></h6></button>&nbsp;&nbsp;&nbsp;</td><td  style="padding-bottom:10px;"><button   type="button"   id="alert_gonextB"  style = "background-color:white;border:0;outline:0; " ><b  style="font-size:16;">중간점검 <img style="padding-bottom:10px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1633703416.png" width=25></b></button></td>  </tr></table>
<table width=90% align=center><tr><td>&nbsp;</td></tr></table><table width=90% align=center><tr><td>'.$ntext1.'</td></tr></table>';
*/


$fbtalk=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk where creator='$studentid' ORDER BY id DESC LIMIT 1 ");
$fbtype=$fbtalk->type;
$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$fbtype;
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$lastday=$schedule->lastday;
$drawing=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='weekly' ORDER BY id DESC LIMIT 1 ");
$drawingid=$drawing->wboardid;
$lastday=$schedule->lastday;

$lastGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated<='$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$weeklyGoalText='<span style="color:white;font-size=15;"><img src="http://mathking.kr/Contents/IMAGES/warning.png" width=40> &nbsp;지난 주 목표 : '.$lastGoal->text.' (새로운 목표를 입력해 주세요)</span>';
$wtimestart=time()-86400*($nday+3);
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
if(empty($weeklyGoal->id)==0)$weeklyGoalText='<h6> &nbsp;주간목표 : '.$weeklyGoal->text.' ('.$lastday.'까지)</h6>';
echo '<style>
#wrapper {
    border-style:solid;
    height:20px;
    width:200px;
    display:table-cell;
    vertical-align:bottom;
}
#dropdown { 
   width:80px;
} 
</style>';
if($hideinput==1)$status='checked';
if( time()-$checkgoal->timecreated > 43200 && $checkgoal->comment==NULL)
	{
	$placeholder='placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
	$presettext='';
	}
elseif( time()-$checkgoal->timecreated > 43200 && $checkgoal->comment!=NULL) 
	{
	$placeholder='';
	$presettext='value="'.$checkgoal->comment.'"';
	}
else 
	{
	$placeholder='';
	$presettext='value="'.$checkgoal->text.'"';
	} 
if($role!=='student') $checkstudentinput='<div class="form-check"><label class="form-check-label">학생입력 숨김 &nbsp; <input type="checkbox" name="checkAccount" '.$status.'  onclick="ChangeCheckBox(25,'.$studentid.', this.checked)"/><span class="form-check-sign"></span></label></div>';

$summarywb=''; 
$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
//$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='summary' AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
if($summary->id!=NULL)$summarywb='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summary->wboardid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>';
 
$deadline=date("Y:m:d",time());

$conditions=$DB->get_records_sql("SELECT * FROM mdl_abessi_knowhowlog WHERE studentid='$studentid' AND active='1' ORDER BY timemodified ");  
$conditionslist= json_decode(json_encode($conditions), True);
 
unset($value3);  
foreach($conditionslist as $value3)
	{
	$srcid=$value3['srcid']; 
	$item1=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE id='$srcid' ORDER BY id DESC LIMIT 1"); //선택유형
	$course=$item1->course; $type=$item1->type; $text=$item1->text; 
	$item2=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE srcid='$srcid' AND active='1' ORDER BY id DESC LIMIT 1"); // 선택메뉴
	$text2=$item2->text; 

	if($mode==='CA' && $course==='개념미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CB' && $course==='심화미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CC' && $course==='내신미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CD' && $course==='수능미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	}

$displaymemo='<br><table width=100%><tr><td><h5 align=center style="color:#4287f5;"> # <b style="color:#f7c305;">Golden Goal</b>(우상향 주간목표)은 수학실력 향상의 첫 걸음입니다.   </h5></td><td><h6><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&nweek=4&eid='.$schedule->id.'"><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></a> '.$schedule->memo8.' </h6></td></tr></table>';

 echo ' 
					<div class="row">
						<div class="col-md-12">
							 
							<div class="card">							
								<div class="card-body"><!--user foreach to show recent 20 inputs-->
								<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=100%></p>';
if($hideinput==0 || $role!=='student') echo '<table><tr>'.$chosenitems.'</tr></table>'.$displaymemo.'<table class="table table-head-bg-primary mt-4">
										<thead>
											<tr>
												<th><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$drawingid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a> </th><th width=50% scope="col" style="text-align:left;">'.$weeklyGoalText.'</th><th   width=10%  scope="col"></th><th   width=10%  scope="col"></th>
												<th   width=10%  scope="col"></th><th  width=10% scope="col"></th><th   width=10%   scope="col"></th>
											</tr>
										</thead>
										<tbody>
										<tr><td>'.$summarywb.' </td> <td><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  '.$placeholder.' '.$presettext.'></td><td><div class="select2-input" style="font-size: 2.0em;padding-top:15px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="오늘목표" '.$selectgtype1.'>오늘목표</option><option value="주간목표"  '.$selectgtype2.'>주간목표</option></h3></select> </div></td>	
										<td><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="데드라인" value="'.$deadline.'"></td><td><div class="select2-input" style="font-size: 2.0em;padding-top:1px;"><select id="basic2" name="basic2" class="form-control"  ><h3><option value="1">1 쉬운</option><option value="2" selected>2 보통</option><option value="3">3 도전</option><option value="4">4 열공</option><option value="5">5 몰입</option></h3></select> </div></td><td valign=bottom><button type="button" id="update" style="width:100;height:40;" onclick="edittoday(2,'.$studentid.',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val()); "> 업데이트</a></button></td>
										<td>'.$checkstudentinput.'</td>
										</tr> 
										</tbody>
									</table>';
else echo '<table align=center><tr><td style="color:red;font-size:20;text-align:center;">담당 선생님과 함께 계획을 입력해 주세요 ! </td></tr></table>';

if($hideinput==0 || $role!=='student') echo '									
								</div>
							</div>
						</div><div class="col-md-7"><table width=90% align=center><tr><td><b style="font-size:16px;">1.</b> 목차 및 최근 계획 <b style="font-size:16px;">2.</b> 주간 시간표 <b style="font-size:16px;">3.</b> 테스트 결과를 토대로 <b style="font-size:16px;">공부의 범위와 양을 정할 수 있습니다.</b></td></tr><tr><td>';
include("index_embed.php");
echo '<table width=100%>'.$goalhistory0.$goalhistory1.'</table>';
include("schedule_embed.php");
echo '</td></tr></table></div>
<div class="col-md-5">
<table><tr><td></td><td><h6>지난 시간 요약 내용을 발표 후 오늘 목표를 입력해 주세요 ! &nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=16" target="_blank"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621861054001.png" width=15></a></h6></td></tr></table><table width=100%ss><tr><td valign=top>';

echo '</td></tr></table><table width=100%><tr><td>내신테스트<br><br>'.$quizlist11.''.$quizlist12.'<hr>표준테스트<br><br>'.$quizlist21.''.$quizlist22.'<hr>인지촉진<br><br>'.$quizlist31.''.$quizlist32.'</td></tr></table> </div> ';
echo ' 
				</div>
			</div>
		 </div>';
$nextgoal= $DB->get_record_sql("SELECT id,comment FROM  mdl_abessi_today Where userid='$studentid' AND timecreated<'$timeback' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 1 ");
$nextplan=$nextgoal->comment;

if($nextplan!=NULL && $checkgoal->id==NULL)echo '<script>
				{
				var Plan=\''.$nextplan.'\';
				const Toast = Swal.mixin({
				  toast: true,
				  position: "top",
				
				  showConfirmButton: true,
				  timer: 50000,
				  timerProgressBar: true,
				  didOpen: (toast) => {
				    toast.addEventListener("mouseenter", Swal.stopTimer)
				    toast.addEventListener("mouseleave", Swal.resumeTimer)
				  }
				})

				Toast.fire({
				
				  title: " 다음 계획 : " + Plan,
				  icon: "success"
				            }) 
				}
			</script>';
include("quicksidebar.php");
echo '	 
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
	<!--<script src="../assets/js/demo.js"></script> -->

	<script>
	  document.getElementById("squareInput").addEventListener("keydown", function(event) {
		if (event.keyCode === 13) {
		  document.getElementById("update").click();
		}
	  });
 $(\'#alert_flywheel\').click(function(e) {
					var Userid= \''.$studentid.'\';	 
					var text1="평가준비 1단계 (오답은 직접 풀기, 보류문제는 해설지 참고 <a href=https://docs.google.com/presentation/d/1NrNmjFLBgSxAMrTLJPnUQtmrkOPySc6C4hbMmh3BLeg/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text2="평가준비 2단계 (순서도로 풀이 계획 수립 후 단계별 풀이 <a href=https://docs.google.com/presentation/d/1TsWdvyEIL4624Xlu2VEJzv8QPqF1ypVcb_1hNX_Xp_Y/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text3="평가준비 3단계 (탭을 열어 두고 알 때까지 생각하기 <a href=https://docs.google.com/presentation/d/1IupopPHUA5wueb1lsh92alpID0uc1yBIzWg3gyPk9zQ/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text4="서술평가 1단계 (평가준비 과정에서 발견된 약점 쓰기 <a href=https://docs.google.com/presentation/d/1y87eWTnFvp0xjJF0xpq1R_nGty0kke7klxcFlHS2t_4/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text5="서술평가 2단계 (막힘없이 풀기 <a href=https://docs.google.com/presentation/d/1BT8lvfsxc_IuTkzvx4fzyEsy8VjhL4KEJ8KXhGNodAE/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text6="서술평가 3단계 (발상촉진 유형 선택 연습하기 <a href=https://docs.google.com/presentation/d/16qVgQRd82vSb_DkzE87XXpzMFUfc4SGIfKoDtg1HKfU/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text7="부스 1단계 논리훈련 (발상촉진 내용 작성하기 <a href=https://docs.google.com/presentation/d/11OkF_76XATgrxA-n3EVzagf_TqTifxr_vG5kNAlf4IU/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text8="부스 2단계 단계형성 (발상촉진 내용 체화하기 <a href=https://docs.google.com/presentation/d/1XehaiVxMtDGh969OnBF8wd4BzM8VFIDLSCgO_IddgfQ/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text9="부스 3단계 생각계단 (발상촉진 내용과 연관 논리요소 연결하기 <a href=https://docs.google.com/presentation/d/1I3uRJMkx-nq7WJXF54mcoyYftv2t3mcE22s4AB4xEOk/present#slide=id.p target=_blank>자세히</a>) <hr>";
				 
			swal("공부법 단계 선택하기",  "현재 자신의 단계를 선택하고 방법을 익힌 다음 실천해 보세요.",{
				
			  buttons: {
			    catch1: {
			      text: "평가준비 1단계 : 오답은 직접 풀기, 보류문제는 해설지 참고",
			      value: "catch1",className : \'btn btn-default\'
				
			    },
			    catch2: {
			      text: "평가준비 2단계 : 순서도로 풀이 계획 수립 후 단계별 풀이",
			      value: "catch2",className : \'btn btn-default\'
			    },
			    catch3: {
			      text: "평가준비 3단계 : 탭을 열어 두고 알 때까지 생각하기",
			      value: "catch3",className : \'btn btn-default\'
			    },
			    catch4: {
			      text: "서술평가 1단계 : 평가준비 과정을 참고하여 오답원인 쓰기",
			      value: "catch4",className : \'btn btn-default\'
			    },
			    catch5: {
			      text: "서술평가 2단계 : 도움없이 막히지 않고 풀이 완성하기",
			      value: "catch5",className : \'btn btn-default\'
			    },
			    catch6: {
			      text: "서술평가 3단계 : 풀이과정 중 부스터 스탭 유형 선택하기",
			      value: "catch6",className : \'btn btn-default\'
			    },
			    catch7: {
			      text: "부스터 스텝 1단계 : 선택한 논리요소 작성하기",
			      value: "catch7",className : \'btn btn-default\'
			    },
			    catch8: {
			      text: "부스터 스텝 2단계 : 논리요소 반복훈련 실행하기",
			      value: "catch8",className : \'btn btn-default\'
			    },
			    catch9: {
			      text: "부스터 스텝 3단계 : 단원의 연관 논리요소와 연결하기",
			      value: "catch9",className : \'btn btn-default\'
			    },
 
			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("취소되었습니다");
			      break;
			 
 			   case "catch1":
  			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'1\',
					"inputtext":text1,	
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'2\',
					"inputtext":text2,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'3\',
					"inputtext":text3,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'4\',
					"inputtext":text4,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'5\',
					"inputtext":text5,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'6\',
					"inputtext":text6,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'7\',
					"inputtext":text7,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch8":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'8\',
					"inputtext":text8,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch9":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'9\',
					"inputtext":text9,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
			 	 }
				});			 		
			});
	 
		function edittoday(Eventid,Userid,Inputtext,Type,Level,Deadline)
				{
				swal({	text: \'목표가 설정되었습니다\',buttons: false,})
			     	     $.ajax({
		     		            url:"database.php",
				     	     type: "POST",
		            		     dataType:"json",
 			  		     data : {
					     "eventid":Eventid,
					     "userid":Userid,
		      		             "inputtext":Inputtext,
		       		             "type":Type,
		       		             "level":Level,
					     "deadline":Deadline,
		         		     },
		            	        success:function(data){  					
			       			      }
		  		      }) 
				setTimeout(function() {location.reload(); },1000);
				}
		function ChangeCheckBox(Eventid,Userid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
 		 
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
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

		$("#basic1").select2({
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


</body>';

?>
