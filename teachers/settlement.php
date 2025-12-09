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
	if($user['suspended']==0)
		{
		$nactiveusers++;
		$deposit0=0;$deposit1=0;$depositp1=0;$depositp2=0;$depositp3=0;$depositp4=0;$fee=0;
		$record0='';$record1='';$recordp1='';$recordp2='';$recordp3='';$recordp4=''; 
		$enrollments = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND teacherid='$teacherid' AND type LIKE 'enrol' AND timecreated>'$monthsago6' AND hide LIKE '0' ORDER by id DESC " );
		$result2= json_decode(json_encode($enrollments), True);
		unset($value);
		foreach($result2 as $value)
			{
			$nthismonth=floor(($value['doriginal']-$timecreated)/2419200)+1;
			$fee=$value['fee'];
			$deposit= $value['deposit'];
			$subject= $value['subject'];
			$begindate=date('Y-m-d', $value['doriginal']);
			$enddate=date('Y-m-d', $value['dchanged']);
			$status=$value['status'];
			if(strpos($status, '납부')!== false)$statuscolor='blue';
			elseif(strpos($status, '정산')!== false)$statuscolor='green';
			else $statuscolor='red';			

			if($nthismonth==1)//다음달
				{
				$deposit1=$deposit1+$deposit;
				$totaldeposit1=$$totaldeposit1+$deposit;
				if($deposit==NULL)$deposit=$fee;
				$record1.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';				
				}
			elseif($nthismonth==0)//진행중인 수업
				{
				$deposit0=$deposit0+$deposit;
				$totaldeposit0=$totaldeposit0+$deposit;
				if($deposit==NULL)$deposit=$fee;
				$record0.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';				 
				}
			elseif($nthismonth==-1)
				{ 
				$depositp1=$depositp1+$deposit;
				$totaldepositp1=$totaldepositp1+$deposit;
				if($deposit==NULL)$deposit=$fee;
				$recordp1.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';				
				}
			elseif($nthismonth==-2)
				{
				$depositp2=$depositp2+$deposit;
				$totaldepositp2=$totaldepositp2+$deposit;
				if($deposit==NULL)$deposit=$fee;
				$recordp2.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';
				}
			elseif($nthismonth==-3)
				{
				$depositp3=$depositp3+$deposit;
				$totaldepositp3=$totaldepositp3+$deposit;
				if($deposit==NULL)$deposit=$fee;
				$recordp3.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';
				}
			elseif($nthismonth==-4)
				{
				$depositp4=$depositp4+$deposit;
				$totaldepositp4=$totaldepositp4+$deposit;

				if($deposit==NULL)$deposit=$fee;
				$recordp4.='<span style="color:'.$statuscolor.';">'.$subject.' : '.$deposit.'원 <br>';
				}
			}
		 
		$activeusers.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12" target="_blank" ><b>'.$thisuser->firstname.$thisuser->lastname.'</b></a>&nbsp;</td><td>'.$record1.'</td><td>'.$record0.'</td><td>'.$recordp1.'</td><td>'.$recordp2.'</td><td>'.$recordp3.'</td><td>'.$recordp4.'</td></tr>';   
		}
	else
		{
		$suspendedusers.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12" target="_blank" ><b>'.$thisuser->firstname.$thisuser->lastname.'</b></a>&nbsp;</td><td>'.$record1.'</td><td>'.$record0.'</td><td>'.$recordp1.'</td><td>'.$recordp2.'</td><td>'.$recordp3.'</td><td>'.$recordp4.'</td></tr>';   
		}
	}

	//$DB->execute("UPDATE {abessi_teacher_setting} SET nenergy='$nenergy', usedtime='$usedtime' WHERE userid='$teacherid' ORDER BY id DESC LIMIT 1 ");  
 
	 
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
	<tbody><tr><td>학생</td><td>다음달</td><td>이달</td><td>지난달</td><td>지지난달</td><td>지지지난달</td><td>지지지지난달</td></tr>
	'.$activeusers.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$suspendedusers.'<tbody>
	<tr><td>합계</td><td>'.$totaldeposit1.'</td><td>'.$totaldeposit0.'</td><td>'.$totaldeposit1.'</td><td>'.$totaldeposit2.'</td><td>'.$totaldeposit3.'</td><td>'.$totaldeposit4.'</td></tr>
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
