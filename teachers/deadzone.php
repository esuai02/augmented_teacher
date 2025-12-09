<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");
 
//$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$timecreated=time();
$tlastaccess=$timecreated-604800*30;
 
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$amonthago6=$timecreated-604800*30;
$timestart=date("Y-m-d", $timecreated);

 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended=0 AND institution LIKE '$academy' AND lastaccess> '$amonthago6' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
 
$result= json_decode(json_encode($mystudents), True);
unset($user);
if($mode==='goodgoal') 
	{
	foreach($result as $user)
		{
		$userid=$user['id'];
	  
		$std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$studentname=$std->firstname.$std->lastname;
		
	 
		if($tlaststroke<43200)
			{
			if($goal->inspect==1)
				{
				$userlist0.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a></td><td width=10% style="color:blue;font-size:16px;"></td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">메타인지</a>&nbsp;&nbsp;&nbsp;'.$mcstatus.'</td></tr>';		
				}
			}
		}
	} 
elseif($mode==='monthlyratio') //보강
	{
	foreach($result as $user)
		{
		$userid=$user['id'];
	  
		$std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$studentname=$std->firstname.$std->lastname;
		
	 
		if($tlaststroke<43200)
			{
			if($goal->inspect==1)
				{
				$userlist0.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a></td><td width=10% style="color:blue;font-size:16px;"></td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">메타인지</a>&nbsp;&nbsp;&nbsp;'.$mcstatus.'</td></tr>';		
				}
			}
		}
	} 
elseif($mode==='late') //지각 - 시간표와 로그인 시간 기준
	{
	foreach($result as $user)
		{
		$userid=$user['id'];
	  
		$std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$studentname=$std->firstname.$std->lastname;
	 
		if($tlaststroke<43200)
			{
			if($goal->inspect==1)
				{
				$userlist0.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a></td><td width=10% style="color:blue;font-size:16px;"></td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">메타인지</a>&nbsp;&nbsp;&nbsp;'.$mcstatus.'</td></tr>';		
				}
			}
		}
	}  
echo '
		<div class="main-panel">
			<div class="content">  
				<div class="container-fluid"><table align=center><tr><td><span sytle="color:grey; font-size:20;"><b>자가 동작기억 피드백 가능한 학습자 만들기</b> (메타인지 촉진, 질문하기, 성찰하기, 시스템 사용법 익히기)</span><br></td></tr></table>
					<div class="row">
						<div class="col-md-12">
							<div class="card card-invoice">
 
								<div class="card-body">

									<div class="row">
									
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="invoice-detail"> 
												<div class="invoice-item"><br><br>		
													<div class="table-responsive"> 												
														 <table width=80%>'.$userlist4.$userlist3.'<tr><td><hr style="border: dashed 1px red;"></td><td><hr style="border: dashed 1px red;"></td><td><hr style="border: dashed 1px red;"></td><td><hr style="border: dashed 1px red;"></td><td><hr style="border: dashed 1px red;"></td></tr>'.$userlist2.$userlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$userlist5.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$userlist0.'</table>
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
