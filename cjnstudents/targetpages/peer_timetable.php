<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("peer_navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

//
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$teacherid' AND fieldid='22' "); 
$role=$userrole->role;
 
$period=required_param('tb', PARAM_INT); // get_record from $period ago
$periodp=$period+7;
$periodm=$period-7;
//$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;

if($role=='teacher');
{
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
$size=count($mystudents); 
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
{
$userid=$user['id'];
$tafter=time()-86400*$period;
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
if($schedule->timecreated==NULL || $schedule->timecreated > $tafter )$newschedule.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a>';
for($n1=0;$n1<8;$n1++)
	{ 
	$var='start'.$n1;
	$var2=$schedule->$var;
	$var3='duration'.$n1;
	$var4=$schedule->$var3;
	$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
 		if($var2!=NULL && $var4!=NULL && (time()- $schedule->timecreated)<86400000)
		{	 
		$n2=(int)(($minutes-530)/30);	
 		 	
		$date=date(" h:i A");
		$date2=date("H:i",strtotime($date));
		$time2    = explode(':', $date2);
		$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
		$npresent=(int)(($minutes2-530)/30);	

		if($minutes<500)$n2=0;
		if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5|$npresent==$n2+6||$npresent==$n2+7||$npresent==$n2+8||$npresent==$n2+9||$npresent==$n2+10||$npresent==$n2) && $nday==$n1)
			{	
			$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
			$lastaction=$lastaction->maxtc;
			$lastaccess=time()-$lastaction;
			$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");
			$tgoal=time()-$goal->timecreated;
			if($lastaccess>36000)
				{
				if($tgoal >43200 )$name[$n1][$n2].='<a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >&nbsp;&nbsp;'.$user['lastname'].'</a> </br>';
				else 
					{
					$today .=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13>'.$user['lastname'].'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
 					$name[$n1][$n2].='<a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >&nbsp;&nbsp;'.$user['lastname'].'</a> </br>';
					}
				}
			else 
				{
				$name[$n1][$n2].='<a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >&nbsp;&nbsp;'.$user['lastname'].'</a> </br>';
				}
			}
		else 
			{ 
			 $name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
 			}
		 
		
		// $name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
		}elseif($var4!=0&&(time()- $schedule->timecreated)<86400000)
		{	 
		$name[$n1][29].='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
		}
	}
}
   
}

echo '
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="card card-invoice">
 
								<div class="card-body">

									<div class="row">
										<div class="col-md-10 info-invoice">
											<h6 class="sub">출결변경: '.$newschedule.'</h6>
											<h6 class="sub">출결현황: '.$today.'</h6>
										</div>
										<div class="col-md-2 info-invoice">
											<p><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id=2&tb='.$periodp.'"><span class="sidebar-mini-icon"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1577620068.png width=30></a>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id=2&tb='.$periodm.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1577620154.png width=30></a>&nbsp;('.$period.'일)</p>
											
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="invoice-detail"> 
												<div class="invoice-item">
													<div class="table-responsive">
														<table class="table table-striped">
															<thead>
																<tr>
<td>총 '.$size.'명</td><td>월</td><td>화</td><td>수</td><td>목</td><td>금</td><td>#</td><td>토</td><td>일</td>
																</tr>
															</thead>
															<tbody>	
<tr><td>~10시</td><td>'.$name[1][0].'</td><td>'.$name[2][0].'</td><td>'.$name[3][0].'</td><td>'.$name[4][0].'</td><td>'.$name[5][0].'</td><td>~10시</td><td>'.$name[6][0].'</td><td>'.$name[7][0].'</td></tr>															
<tr><td>10:00</td><td>'.$name[1][1].'</td><td>'.$name[2][1].'</td><td>'.$name[3][1].'</td><td>'.$name[4][1].'</td><td>'.$name[5][1].'</td><td>10:00</td><td>'.$name[6][1].'</td><td>'.$name[7][1].'</td></tr>
<tr><td>10:30</td><td>'.$name[1][2].'</td><td>'.$name[2][2].'</td><td>'.$name[3][2].'</td><td>'.$name[4][2].'</td><td>'.$name[5][2].'</td><td>10:30</td><td>'.$name[6][2].'</td><td>'.$name[7][2].'</td></tr>
<tr><td>11:00</td><td>'.$name[1][3].'</td><td>'.$name[2][3].'</td><td>'.$name[3][3].'</td><td>'.$name[4][3].'</td><td>'.$name[5][3].'</td><td>11:00</td><td>'.$name[6][3].'</td><td>'.$name[7][3].'</td></tr>

<tr><td>11:30</td><td>'.$name[1][4].'</td><td>'.$name[2][4].'</td><td>'.$name[3][4].'</td><td>'.$name[4][4].'</td><td>'.$name[5][4].'</td><td>11:30</td><td>'.$name[6][4].'</td><td>'.$name[7][4].'</td></tr>
<tr><td>12:00</td><td>'.$name[1][5].'</td><td>'.$name[2][5].'</td><td>'.$name[3][5].'</td><td>'.$name[4][5].'</td><td>'.$name[5][5].'</td><td>12:00</td><td>'.$name[6][5].'</td><td>'.$name[7][5].'</td></tr>
<tr><td>12:30</td><td>'.$name[1][6].'</td><td>'.$name[2][6].'</td><td>'.$name[3][6].'</td><td>'.$name[4][6].'</td><td>'.$name[5][6].'</td><td>12:30</td><td>'.$name[6][6].'</td><td>'.$name[7][6].'</td></tr>
<tr><td> 1:00</td><td>'.$name[1][7].'</td><td>'.$name[2][7].'</td><td>'.$name[3][7].'</td><td>'.$name[4][7].'</td><td>'.$name[5][7].'</td><td> 1:00</td><td>'.$name[6][7].'</td><td>'.$name[7][7].'</td></tr>
<tr><td> 1:30</td><td>'.$name[1][8].'</td><td>'.$name[2][8].'</td><td>'.$name[3][8].'</td><td>'.$name[4][8].'</td><td>'.$name[5][8].'</td><td> 1:30</td><td>'.$name[6][8].'</td><td>'.$name[7][8].'</td></tr>
<tr><td> 2:00</td><td>'.$name[1][9].'</td><td>'.$name[2][9].'</td><td>'.$name[3][9].'</td><td>'.$name[4][9].'</td><td>'.$name[5][9].'</td><td> 2:00</td><td>'.$name[6][9].'</td><td>'.$name[7][9].'</td></tr>
<tr><td> 2:30</td><td>'.$name[1][10].'</td><td>'.$name[2][10].'</td><td>'.$name[3][10].'</td><td>'.$name[4][10].'</td><td>'.$name[5][10].'</td><td> 2:30</td><td>'.$name[6][10].'</td><td>'.$name[7][10].'</td></tr>
<tr><td> 3:00</td><td>'.$name[1][11].'</td><td>'.$name[2][11].'</td><td>'.$name[3][11].'</td><td>'.$name[4][11].'</td><td>'.$name[5][11].'</td><td> 3:00</td><td>'.$name[6][11].'</td><td>'.$name[7][11].'</td></tr>
<tr><td> 3:30</td><td>'.$name[1][12].'</td><td>'.$name[2][12].'</td><td>'.$name[3][12].'</td><td>'.$name[4][12].'</td><td>'.$name[5][12].'</td><td> 3:30</td><td>'.$name[6][12].'</td><td>'.$name[7][12].'</td></tr>
<tr><td> 4:00</td><td>'.$name[1][13].'</td><td>'.$name[2][13].'</td><td>'.$name[3][13].'</td><td>'.$name[4][13].'</td><td>'.$name[5][13].'</td><td> 4:00</td><td>'.$name[6][13].'</td><td>'.$name[7][13].'</td></tr>
<tr><td> 4:30</td><td>'.$name[1][14].'</td><td>'.$name[2][14].'</td><td>'.$name[3][14].'</td><td>'.$name[4][14].'</td><td>'.$name[5][14].'</td><td> 4:30</td><td>'.$name[6][14].'</td><td>'.$name[7][14].'</td></tr>
<tr><td> 5:00</td><td>'.$name[1][15].'</td><td>'.$name[2][15].'</td><td>'.$name[3][15].'</td><td>'.$name[4][15].'</td><td>'.$name[5][15].'</td><td> 5:00</td><td>'.$name[6][15].'</td><td>'.$name[7][15].'</td></tr>
<tr><td> 5:30</td><td>'.$name[1][16].'</td><td>'.$name[2][16].'</td><td>'.$name[3][16].'</td><td>'.$name[4][16].'</td><td>'.$name[5][16].'</td><td> 5:30</td><td>'.$name[6][16].'</td><td>'.$name[7][16].'</td></tr>
<tr><td> 6:00</td><td>'.$name[1][17].'</td><td>'.$name[2][17].'</td><td>'.$name[3][17].'</td><td>'.$name[4][17].'</td><td>'.$name[5][17].'</td><td> 6:00</td><td>'.$name[6][17].'</td><td>'.$name[7][17].'</td></tr>
<tr><td> 6:30</td><td>'.$name[1][18].'</td><td>'.$name[2][18].'</td><td>'.$name[3][18].'</td><td>'.$name[4][18].'</td><td>'.$name[5][18].'</td><td> 6:30</td><td>'.$name[6][18].'</td><td>'.$name[7][18].'</td></tr>
<tr><td> 7:00</td><td>'.$name[1][19].'</td><td>'.$name[2][19].'</td><td>'.$name[3][19].'</td><td>'.$name[4][19].'</td><td>'.$name[5][19].'</td><td> 7:00</td><td>'.$name[6][19].'</td><td>'.$name[7][19].'</td></tr>
<tr><td> 7:30</td><td>'.$name[1][20].'</td><td>'.$name[2][20].'</td><td>'.$name[3][20].'</td><td>'.$name[4][20].'</td><td>'.$name[5][20].'</td><td> 7:30</td><td>'.$name[6][20].'</td><td>'.$name[7][20].'</td></tr>
<tr><td> 8:00</td><td>'.$name[1][21].'</td><td>'.$name[2][21].'</td><td>'.$name[3][21].'</td><td>'.$name[4][21].'</td><td>'.$name[5][21].'</td><td> 8:00</td><td>'.$name[6][21].'</td><td>'.$name[7][21].'</td></tr>
<tr><td> 8:30</td><td>'.$name[1][22].'</td><td>'.$name[2][22].'</td><td>'.$name[3][22].'</td><td>'.$name[4][22].'</td><td>'.$name[5][22].'</td><td> 8:30</td><td>'.$name[6][22].'</td><td>'.$name[7][22].'</td></tr>
<tr><td> 9:00</td><td>'.$name[1][23].'</td><td>'.$name[2][23].'</td><td>'.$name[3][23].'</td><td>'.$name[4][23].'</td><td>'.$name[5][23].'</td><td> 9:00</td><td>'.$name[6][23].'</td><td>'.$name[7][23].'</td></tr>
<tr><td> 9:30</td><td>'.$name[1][24].'</td><td>'.$name[2][24].'</td><td>'.$name[3][24].'</td><td>'.$name[4][24].'</td><td>'.$name[5][24].'</td><td> 9:30</td><td>'.$name[6][24].'</td><td>'.$name[7][24].'</td></tr>
<tr><td>10:00</td><td>'.$name[1][25].'</td><td>'.$name[2][25].'</td><td>'.$name[3][25].'</td><td>'.$name[4][25].'</td><td>'.$name[5][25].'</td><td>10:00</td><td>'.$name[6][25].'</td><td>'.$name[7][25].'</td></tr>
<tr><td>10:30</td><td>'.$name[1][26].'</td><td>'.$name[2][26].'</td><td>'.$name[3][26].'</td><td>'.$name[4][26].'</td><td>'.$name[5][26].'</td><td>10:30</td><td>'.$name[6][26].'</td><td>'.$name[7][26].'</td></tr>
<tr><td>11:00</td><td>'.$name[1][27].'</td><td>'.$name[2][27].'</td><td>'.$name[3][27].'</td><td>'.$name[4][27].'</td><td>'.$name[5][27].'</td><td>11:00</td><td>'.$name[6][27].'</td><td>'.$name[7][27].'</td></tr>
<tr><td>11:30</td><td>'.$name[1][28].'</td><td>'.$name[2][28].'</td><td>'.$name[3][28].'</td><td>'.$name[4][28].'</td><td>'.$name[5][28].'</td><td>11:30</td><td>'.$name[6][28].'</td><td>'.$name[7][28].'</td></tr>
<tr><td>시작<br>미정</td><td>'.$name[1][29].'</td><td>'.$name[2][29].'</td><td>'.$name[3][29].'</td><td>'.$name[4][29].'</td><td>'.$name[5][29].'</td><td>시작<br>미정</td><td>'.$name[6][29].'</td><td>'.$name[7][29].'</td></tr>
															</tbody>
														</table>
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