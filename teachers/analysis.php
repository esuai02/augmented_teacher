<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
//$teacherid=required_param('id', PARAM_INT);
 
include("navbar.php");
$timefrom=$_GET["timefrom"]; 
$timeto= $_GET["timeto"]; 

	$userrole0=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
	$role0=$userrole0->role;

if($role0!=='student');
{
$tnow=time();
$tendaysAgo=time()-864000;  //10일전
$aweekAgo=time()-604800-43200;  // 1주일 전
$tbegin=$tnow-18748800;
$timefrom1=$tnow-86400*$timefrom;
$timeto1=$tnow-86400*$timeto;

$ndanger=0;
$ndrop=0;
$ncare=0;
$ndropThismonth++;
$ifcare=0;
$number=0;
$weeklyGrade=0;
$howChallenging=0;
$nthisweek=0;
$totalwbscore=0;
$nwbscore=0;

 
$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE lastaccess > '$timefrom1' AND firstname LIKE '%$tsymbol%' ORDER BY lastaccess DESC ");
$userlist= json_decode(json_encode($mystudents), True);
unset($user);
foreach($userlist as $user)
	{
	if($user['lastaccess']>$aweekAgo)$number++;
	$userid=$user['id'];
	$studentname=$user['firstname'].$user['lastname'];
	$dayinactive=(INT)(($tnow-$user['currentlogin'])/86400);
	$dayBegin = date("Y-m-d", $user['firstaccess']);
	$dayDrop = date("Y-m-d", $user['lastaccess']);
	$nmonths=round(($user['lastaccess']-$user['firstaccess'])/(86400*31),0);
	$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='68' "); 
	$location=$info->data;
	$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
	$role=$userrole->role;
	$ifdanger=0; $ifcare=0;
	$weeklyScore=$DB->get_record_sql("SELECT * FROM mdl_abessi_today where userid='$userid' AND type='주간목표' AND timemodified>'$aweekAgo' AND score>0 ORDER BY id DESC LIMIT 1 "); 
	$wboardscore=$DB->get_record_sql("SELECT * FROM mdl_abessi_indicators where userid='$userid' AND wbscore>0 AND timecreated>'$aweekAgo' ORDER BY id DESC LIMIT 1 "); 
	if($wboardscore->wbscore>0)
		{
		$totalwbscore=$totalwbscore+$wboardscore->wbscore;
		$nwbscore++;   
		}
	$status='';
	$factors='';

	if($user['state']==2 && $user['suspended']==0)
		{
		$status='<b style="color:red;">위험</b>';	
		$factors='담당의견';
		$ifdanger=1;
		}
	elseif($user['lastaccess'] < $timeto1 && $user['suspended']==0)
		{
		$factors='활동감소';
		$status='<b style="color:red;">위험</b>';	
		$ifdanger=1;
		}
	elseif($user['state']==1)
		{
		$status='<b style="color:blue;">관심</b>';	
		$factors='담당의견';
		if($weeklyScore->score==NULL)$factors='평가누락';
		$ifcare=1;
		}
	elseif($weeklyScore->score==NULL)
		{
		$status='<b style="color:blue;">관심</b>';	
		$factors='평가누락';
		$ifcare=1;
		}
 
	if($user['suspended']==1)
		{
		$status='퇴원';	
		$factors='활동감소';
		$ndrop++;
		}

	if($user['lesson']==NULL)$lessontext='';
	else $lessontext='<table width=100%><tr><td><div class="tooltip7">'.substr($user['lesson'],0,120).'<span style="color:black;" class="tooltiptext7">'.$user['lesson'].'</span></div></td></tr></table>';
	$lessonid='alert_lesson'.$userid;
	$lessons='<table><tr><td><button   type="button"   id="'.$lessonid.'"  style = "background-color:white;color:grey;border:0;outline:0;"   onclick="leaveLesson(2,\''.$userid.'\')" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></button></td><td>'.$lessontext.'</td></tr></table>';

	if($role==='student' && $user['state'] <29)
		{
		if($dayinactive<30)
			{ 
			if($user['suspended']==1)$userlist1.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td> <td>'.$lessons.'</td></tr>';
			elseif($ifcare==1)
				{
				$userlist2.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td> <td>'.$lessons.'</td></tr>';
				$ncare++;
				}
			elseif($ifdanger==1)
				{
				$userlist3.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td> <td>'.$lessons.'</td></tr>';
				$ndanger++;
				}
			if($user['suspended']==1)$ndropThismonth++;
			if($weeklyScore->score!=NULL)
				{
				$weeklyGrade=$weeklyGrade+$weeklyScore->score;
				$howChallenging=$howChallenging+$weeklyScore->planscore;
				$nthisweek++;
				}
			}
		elseif($dayinactive<60)$dropoutlist2.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td><td>'.$lessons.'</td> </tr>';
		elseif($dayinactive<90)$dropoutlist3.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td><td>'.$lessons.'</td> </tr>';
		elseif($dayinactive<120)$dropoutlist4.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td><td>'.$lessons.'</td> </tr>';
		elseif($dayinactive<150)$dropoutlist5.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td><td>'.$lessons.'</td> </tr>';
		elseif($dayinactive<180)$dropoutlist6.='<tr ><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.' </a></td><td> '.$status.'</td><td> '.$factors.'</td><td>'.$dayinactive.'</td><td>'.$nmonths.'</td><td> '.$location.'</td> <td>'.$lessons.'</td></tr>';
		}
	}

$weeklyGradeAve=(INT)($weeklyGrade/$nthisweek/5*100);
$weeklyPlanAve=round($howChallenging/$nthisweek/5*10,3);
$ndropAve=(INT)($ndrop/6);
$nMinorityReport=$ndropAve-$ndropThismonth;

$totalwbscoreAve=(INT)($totalwbscore/$nwbscore);

$indicatorsClass=$DB->get_record_sql("SELECT *  FROM mdl_abessi_indicators_class  WHERE id > 0 AND teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");
if($indicatorsClass->timecreated < $aweekAgo ||  $indicatorsClass->id==NULL)$DB->execute("INSERT INTO {abessi_indicators_class} (number,teacherid,ndanger,ncare, weeklygrade,planscore, weeklywbscore, timecreated) VALUES('$number','$teacherid','$ndanger','$ncare','$weeklyGradeAve','$weeklyPlanAve','$totalwbscoreAve','$tnow')  ");
else $DB->execute("UPDATE {abessi_indicators_class} SET  number='$number',  ndanger='$ndanger', ncare='$ncare', weeklygrade='$weeklyGradeAve' , planscore='$weeklyPlanAve' , weeklywbscore='$totalwbscoreAve' WHERE teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");	
 
if($nMinorityReport+1>=0)$dropoutText='<br> <hr> <br><p align=center style="font-size:20;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;데이터에 의하면 이번 달에 <b style="color:red;"> '.($nMinorityReport+1).'명</b> 이상의 추가 퇴원생이 발생할 수 있습니다. ';
else $dropoutText='<br> <hr> <br><p align=center style="font-size:20;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;데이터에 의하면 이번 달에 <b style="color:red;">'.$ndropAve.'명+'.(-$nMinorityReport-1).'명</b>의 초과 퇴원생이 발생하였습니다. ';
echo $dropoutText.' 퇴원생이 없다면 매월 <b style="color:red;"> '.$ndropAve.'명</b>이 증가 가능합니다.</p><hr>
<table align=center width=100%  style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><tr><th width=20%></th><th width=10% align=center>이름</th><th width=5%>상태</th><th width=7%>원인</th><th width=5%>경과 (일)</th><th width=5%>기간 (개월)</th><th width=5%>지역</th><th width=53%>교훈 및 근거 데이터</th></tr>
<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$userlist2.$userlist3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$userlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$dropoutlist2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$dropoutlist3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$dropoutlist4.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$dropoutlist5.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$dropoutlist6.'</table>';


 

/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
include("quicksidebar.php");
}
echo ' 

<script>
 function leaveLesson(Eventid,Userid)
{ 
			swal({
				title: \'교훈 및 근거 데이터 입력\',
				html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "본 사례로부터 얻게된 교훈과 근거 데이터를 기록해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					$.ajax({
							url:"../managers/check.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'2\',
							"userid":Userid,
							"inputtext":Inputtext,
							},
					success:function(data){
					
					 }
				
				});
				  location.reload();
			});
 
 
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
?>