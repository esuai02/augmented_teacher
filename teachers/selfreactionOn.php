<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($USER->id==$teacherid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES('$USER->id',73,'selfreaction','$timecreated')");

$tlastaccess=time()-604800*30;
 
$halfdayago=time()-43200;
$aweekago=time()-604800;
$weeksago3=time()-604800*3;

$amonthago6=time()-604800*30;
$timestart=date("Y-m-d", time());
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($role==='student')
{
echo '권한이 없습니다.';
exit();
}

$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended=0 AND institution LIKE '$academy' AND lastaccess> '$amonthago6' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
 
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$studentname=$username->firstname.$username->lastname;
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND ( type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
	$ninactive=$goal->ninactive;
	
	$thisuser= $DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog WHERE  userid='$userid' ORDER  BY id DESC LIMIT 1 ");
	$flowrate=$thisuser->flow1+$thisuser->flow2+$thisuser->flow3+$thisuser->flow4+$thisuser->flow5+$thisuser->flow6+$thisuser->flow7+$thisuser->flow8;
 
  	$daterecord=date('Y_m_d', $goal->timecreated);  	 
	$tend=$goal->timecreated;
	 
	$tfinish0=date('m/d/Y', $goal->timecreated +86400); 
 	$tfinish=strtotime($tfinish0);
	$rowcolor='';
	if($ninactive==0)$rowcolor='green';
	$insertrow='<tr style="color:'.$rowcolor.';"><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a></td><td valign=top>WM붕괴 '.$ninactive.'회</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=목표"target="_blank">목표</a>'.$thisuser->flow1.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=순서"target="_blank">순서</a>'.$thisuser->flow2.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=기억"target="_blank">기억</a>'.$thisuser->flow3.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=몰입"target="_blank">몰입</a>'.$thisuser->flow4.'</td>
	<td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=발상"target="_blank">발상</a>'.$thisuser->flow5.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=해석"target="_blank">해석</a>'.$thisuser->flow6.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=숙달"target="_blank">숙달</a>'.$thisuser->flow7.'</td><td valign=top><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$userid.'&type=효율"target="_blank">효율</a>'.$thisuser->flow8.'</td><td valign=top>('.$flowrate.'/40)</td></tr>';

	if($ninactive==0)$list0.=$insertrow;
	elseif($ninactive==1)$list1.=$insertrow;
	elseif($ninactive==2)$list2.=$insertrow;
	elseif($ninactive==3)$list3.=$insertrow;
	elseif($ninactive==4)$list4.=$insertrow;
	elseif($ninactive==5)$list5.=$insertrow;
	elseif($ninactive==6)$list6.=$insertrow;
	elseif($ninactive==7)$list7.=$insertrow;
	elseif($ninactive==8)$list8.=$insertrow;
	elseif($ninactive==9)$list9.=$insertrow;
	elseif($ninactive==10)$list10.=$insertrow;
	elseif($ninactive>10)$list11.=$insertrow;	
	
	}
echo '
		<div class="main-panel">
			<div class="content"> 
				<div class="container-fluid"><table align=center><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$teacherid.'" accesskey="r"><b>동작기억 연쇄작용 일으키기</b></a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/selfreactionOn.php?id='.$teacherid.'">자가피드백 현황</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flowwins.php?id='.$teacherid.'">메타인지</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id=2&tb=7"target="_blank">시간표</a></td></tr></table><hr>							 												
					<table align=center width=100%>
					'.$list0.$list1.$list2.$list3.$list4.$list5.$list6.$list7.$list8.$list9.$list10.$list11.'
					</table> 
					</div>
				</div>
			</div>
			
		</div>
 ';
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
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
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
