<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$nmonth=$_GET['nmonth']; 
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");
echo '<meta http-equiv="refresh" content="600">';
 
require_login(); 
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$monthsago6=$timecreated-604800*30;
$timestart=date("Y-m-d", time());
$dayunixtime=strtotime($timestart)-100;
$dayunixtime2=strtotime($timestart)+86400-100;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' "); 
 
$mystudents=$DB->get_records_sql("SELECT * FROM mdl_abessi_mystudents WHERE teacherid LIKE '$teacherid'  ORDER BY id DESC ");  
$nactiveusers=0; 
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
	{
	$userid=$user['studentid']; 
	$thisuser= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id LIKE '$userid' ORDER BY id DESC LIMIT 1"); 


	$em2 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='112' ");//이메일 (부)
	$em3 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='113' "); //이메일 (모)

	$ph1 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='54' ");//연락처 
	$ph2 =$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='85' "); //아버지 연락처 
	$ph3 = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='55' "); //어머니 연락처 

	$email1 = $thisuser->email;
	$email2 = $em2->data;
	$email3 = $em3->data;

	$phone1 = $ph1->data;
	$phone2 = $ph2->data;
	$phone3 = $ph3->data;

	if($user['suspended']==0)
		{
		$nactiveusers++;		 
		$activeusers.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'" target="_blank" ><b>'.$thisuser->firstname.$thisuser->lastname.'</b></a></td><td>'.$phone1.'</td><td>'.$phone2.'</td><td>'.$phone3.'</td><td>'.$email1.'</td><td>'.$email2.'</td><td>'.$email3.'</td></tr>';   
		} 
	else	 
		{			
		$suspendedusers.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$userid.'" target="_blank" ><b>'.$thisuser->firstname.$thisuser->lastname.'</b></a></td><td>'.$phone1.'</td><td>'.$phone2.'</td><td>'.$phone3.'</td><td>'.$email1.'</td><td>'.$email2.'</td><td>'.$email3.'</td></tr>';    
		} 
	} 
echo '
		<div class="main-panel">
			<div class="content">';
		 
			echo '
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
													<div class="table-responsive">														
														<table width=100% class="table table-striped">
	<thead><tr><td></td><td style="font-size:18; color:blue;">Active Users : '.$nactiveusers.'</td><td style="font-size:18; color:blue;"><button onclick="Settle(20,\'' . $teacherid . '\')">정산하기</button></td> </tr></thead>											 
	<tbody><tr><td>학생</td><td>전화번호</td><td>전화번호(부)</td><td>전화번호(모)</td><td>이메일</td><td>이메일(부)</td><td>이메일(모)</td></tr>
	'.$activeusers.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$suspendedusers.' 
	 
	</tbody></table><hr>
														
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
<script>
function Settle(Eventid,Teacherid)
	{  
	swal({
		title: \'정산을 실행하시겠습니까?\',
		text: "실행 전 충분한 검토를 하시기 바랍니다.",
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
				url:"database.php",
				type: "POST",
				dataType:"json",
				data : {
						"eventid":Eventid,
						"teacherid":Teacherid, 
						},
					success:function(data)
						{

						}
				});
		setTimeout(function() {location.reload(); },500);
		} else {
			swal("취소되었습니다.", {buttons: false,timer: 500});
		}
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
