<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

if($USER->id==$teacherid || $role==='manager')echo '';
else
	{
	echo '볼수없는 페이지입니다.';
	exit();
	}
 
$halfdayago=time()-43200;
$aweekago=time()-604800;
$amonthago6=time()-604800*30;
 
$nshift=$_GET["nshift"];

if($nshift==NULL)$nshift=0;
$nshift_next=$nshift+1;
$nshift_prev=$nshift-1;
$thistimestampt=$timecreated+$nshift*604800*4;
$numdays=date("t", $thistimestampt);
$thismonth=date("Y-m", $thistimestampt);

$mend=date("Y-m-t", $thistimestampt);
$mend =(INT) strtotime($mend)+86400;
//echo  '<br><br><br><br><br><br><br><br><br><br>...................................................................................mstart'.$mstart.'mend'.$mend.'numday'.$numdays;
$mstart=date("Y-m-d",$mend-86400*$numdays-86400);
$mstart =(INT) strtotime($mstart);
// echo  '<br><br><br><br><br><br><br><br><br><br>...................................................................................mstart'.$mstart.'mend'.$mend.'numday'.$numdays;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$teachername=$username->firstname.$username->lastname;
$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE institution LIKE '$academy' AND lastaccess> '$amonthago6' AND firstname LIKE '%$tsymbol%' ORDER BY id DESC ");  
$pageheader= '<table width=100%><tr><td>'.$teachername.'<b style="font-size:20px;" '.$thismonth.'월</b> 수강등록 & 정산 현황</td><td  width=30%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/enrollments.php?id='.$teacherid.'&nshift='.$nshift_prev.'"><< 이전으로</a>&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/enrollments.php?id='.$teacherid.'">현재</a> &nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/enrollments.php?id='.$teacherid.'&nshift='.$nshift_next.'">다음으로 >></a></td></tr></table>';
$feelist.='<tr><td>학생이름</td><td>기간</td><td>수강료</td><td>입금액 (-카드수수료)</td><td>입금일자</td></tr>';
$result= json_decode(json_encode($mystudents), True);
$nusers=0;
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
 
	$nusers++;
	$thisuser= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$studentname=$thisuser->firstname.$thisuser->lastname;
 	$enrollments= $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide NOT LIKE '1' AND type LIKE 'enrol' AND dchanged > '$mstart' AND dchanged < '$mend'   ORDER BY id ");
	$fresult= json_decode(json_encode($enrollments), True);

	//재원생 미발생 명단
	if(count($fresult)==0 && $user['suspended']==0 && $role==='manager')$feelist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$studentname.'</a></td><td>수강기간</td><td>수강료</td><td>입금액</td><td>입금일</td></tr>';
 	else // 발생
		{
		unset($fvalue);
		foreach($fresult as $fvalue)
			{
			$doriginal=date("Y-m/d",$fvalue['doriginal']);  
			$dfinish=date("m/d",$fvalue['doriginal']+604800*4-86400); 
			$dayin=date("m/d h:m:s",$fvalue['dchanged']); 
			if($fvalue['complete']==1) // 납부
				{
				$income1.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$studentname.'</a></td><td>'.$doriginal.' ~ '.$dfinish.' </td><td>'.$fvalue['fee'].'만원</td><td>'.$fvalue['deposit'].'만원</td><td> '.$dayin.'</td></tr>';
				$totalmoney=$totalmoney+$fvalue['deposit'];
				}
			elseif($fvalue['complete']==0 && $role==='manager') // 미납
				{
				$income0.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$studentname.'</a></td><td>'.$doriginal.' ~ '.$dfinish.' </td><td>'.$fvalue['fee'].'만원</td><td>'.$fvalue['deposit'].'만원</td><td> '.$dayin.'</td></tr>';
				}
			}
		}
	 }
$ratio=0.5;
$userfee=1;
$income1.='<tr><td><hr> </td><td><hr> </td><td><hr></td><td><hr></td><td><hr></td></tr>
		<tr><td>총 매출액</td><td>'.$totalmoney.'만원</td><td>입금총액</td><td></td><td></td></tr>
		<tr><td>서비스 사용료 적용</td><td>'.($totalmoney-$nusers*$userfee).'만원</td><td>사용자별 '.$userfee.'만원</td><td></td><td></td></tr>
		<tr><td>지급비율 적용</td><td>'.$totalmoney*$ratio.'만원</td><td>'.$ratio.'</td><td></td><td> </td><td></td></tr> 
		<tr><td>사업소세 적용</td><td>'.$totalmoney*$ratio*0.967.'만원</td><td>3.3%</td><td></td><td></td></tr>';
		echo '
		<div class="main-panel">
			<div class="content"><table align=center><tr style="background-color:light-grey;"> <td></td></tr></table>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="card card-invoice">
 
								<div class="card-body">

									<div class="row">
								
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="invoice-detail"> 
												<div class="invoice-item">
													<div class="table-responsive">	'.$pageheader.'	<hr>입금목록											
														<table class="table table-striped"><tbody>'.$income1.'</tbody></table><hr>미납<table class="table table-striped"><tbody>'.$income0.'</tbody></table><hr>수강 미생성<table class="table table-striped"><tbody>'.$feelist.'</tbody></table><hr> 
													</div>
												</div>
											</div>	
											<div class="seperator-solid  mb-3"></div>
										</div>	
									</div>
								</div>
							
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div>';
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
