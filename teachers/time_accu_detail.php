<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");


 
$period=required_param('tb', PARAM_INT); // get_record from $period ago
$periodp=$period+7;
$periodm=$period-7;
//$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$tlastaccess=time()-604800*30;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE   institution LIKE '$academy' AND suspended=0 AND  (lastaccess> '$amonthago' OR timecreated> '$amonthago' ) AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
  
$size=count($mystudents); 
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$tafter=time()-86400*$period;
	$stdlist= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$stdname=$stdlist->lastname.'|';
	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' AND pinned=1 ORDER BY id DESC LIMIT 1 ");
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0);
	if($nday==0)$nday=7;
 	for($n1=0;$n1<8;$n1++)
		{ 
		$var='start'.$n1;
		$var2=$schedule->$var;
		$var3='duration'.$n1;
		$var4=$schedule->$var3;
		$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
		$n2=(int)(($minutes-530)/30);	
		$ntimemax=(INT)($var4*2);
  		if($var2!=NULL && $var4!=NULL &&  $var4!=0)
			{
	 		for($ntime=0;$ntime<$ntimemax;$ntime++)
				{
				$naccupancy[$n1][$n2+$ntime]++;
				$usrname[$n1][$n2+$ntime].='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$stdname.'</a>';
 				if($naccupancy[$n1][$n2+$ntime]>=8)$name[$n1][$n2+$ntime]='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width=15> '.$naccupancy[$n1][$n2+$ntime].'_';
				elseif($naccupancy[$n1][$n2+$ntime]<=4)$name[$n1][$n2+$ntime]='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width=15> '.$naccupancy[$n1][$n2+$ntime].'_';
				else $name[$n1][$n2+$ntime]='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width=15> '.$naccupancy[$n1][$n2+$ntime].'_';
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
										<div class="col-md-12">
											<div class="invoice-detail"> 
												<div class="invoice-item">
													<div class="table-responsive">
														<table width=100% class="table table-striped">
<thead><tr><td></td><td></td><td></td><td></td><td></td><td align=right>총 '.$size.'명</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$teacherid.'&tb=7">시간표</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/time_accupancy.php?id='.$teacherid.'&tb=7">점유현황</a></td></tr></thead>
															<tbody>
<tr><td width=4.5%></td><td  width=12%>월</td><td width=13%>화</td><td width=13%>수</td><td width=13%>목</td><td width=13%>금</td><td width=4.5%></td><td width=13%>토</td><td width=13%>일</td></tr>
<tr><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >~10시</td><td>'.$name[1][0].$usrname[1][0].'</td><td>'.$name[2][0].$usrname[2][0].'</td><td>'.$name[3][0].$usrname[3][0].'</td><td>'.$name[4][0].$usrname[4][0].'</td><td>'.$name[5][0].$usrname[5][0].'</td><td>~10시</td><td>'.$name[6][0].$usrname[6][0].'</td><td>'.$name[7][0].$usrname[7][0].'</td></tr>															
<tr><td>10:00</td><td>'.$name[1][1].$usrname[1][1].'</td><td>'.$name[2][1].$usrname[2][1].'</td><td>'.$name[3][1].$usrname[3][1].'</td><td>'.$name[4][1].$usrname[4][1].'</td><td>'.$name[5][1].$usrname[5][1].'</td><td>10:00</td><td>'.$name[6][1].$usrname[6][1].'</td><td>'.$name[7][1].$usrname[7][1].'</td></tr>
<tr><td>10:30</td><td>'.$name[1][2].$usrname[1][2].'</td><td>'.$name[2][2].$usrname[2][2].'</td><td>'.$name[3][2].$usrname[3][2].'</td><td>'.$name[4][2].$usrname[4][2].'</td><td>'.$name[5][2].$usrname[5][2].'</td><td>10:30</td><td>'.$name[6][2].$usrname[6][2].'</td><td>'.$name[7][2].$usrname[7][2].'</td></tr>
<tr><td>11:00</td><td>'.$name[1][3].$usrname[1][3].'</td><td>'.$name[2][3].$usrname[2][3].'</td><td>'.$name[3][3].$usrname[3][3].'</td><td>'.$name[4][3].$usrname[4][3].'</td><td>'.$name[5][3].$usrname[5][3].'</td><td>11:00</td><td>'.$name[6][3].$usrname[6][3].'</td><td>'.$name[7][3].$usrname[7][3].'</td></tr>

<tr><td>11:30</td><td>'.$name[1][4].$usrname[1][4].'</td><td>'.$name[2][4].$usrname[2][4].'</td><td>'.$name[3][4].$usrname[3][4].'</td><td>'.$name[4][4].$usrname[4][4].'</td><td>'.$name[5][4].$usrname[5][4].'</td><td>11:30</td><td>'.$name[6][4].$usrname[6][4].'</td><td>'.$name[7][4].$usrname[7][4].'</td></tr>
<tr><td>12:00</td><td>'.$name[1][5].$usrname[1][5].'</td><td>'.$name[2][5].$usrname[2][5].'</td><td>'.$name[3][5].$usrname[3][5].'</td><td>'.$name[4][5].$usrname[4][5].'</td><td>'.$name[5][5].$usrname[5][5].'</td><td>12:00</td><td>'.$name[6][5].$usrname[6][5].'</td><td>'.$name[7][5].$usrname[7][5].'</td></tr>
<tr><td>12:30</td><td>'.$name[1][6].$usrname[1][6].'</td><td>'.$name[2][6].$usrname[2][6].'</td><td>'.$name[3][6].$usrname[3][6].'</td><td>'.$name[4][6].$usrname[4][6].'</td><td>'.$name[5][6].$usrname[5][6].'</td><td>12:30</td><td>'.$name[6][6].$usrname[6][6].'</td><td>'.$name[7][6].$usrname[7][6].'</td></tr>
<tr><td> 1:00</td><td>'.$name[1][7].$usrname[1][7].'</td><td>'.$name[2][7].$usrname[2][7].'</td><td>'.$name[3][7].$usrname[3][7].'</td><td>'.$name[4][7].$usrname[4][7].'</td><td>'.$name[5][7].$usrname[5][7].'</td><td> 1:00</td><td>'.$name[6][7].$usrname[6][7].'</td><td>'.$name[7][7].$usrname[7][7].'</td></tr>
<tr><td> 1:30</td><td>'.$name[1][8].$usrname[1][8].'</td><td>'.$name[2][8].$usrname[2][8].'</td><td>'.$name[3][8].$usrname[3][8].'</td><td>'.$name[4][8].$usrname[4][8].'</td><td>'.$name[5][8].$usrname[5][8].'</td><td> 1:30</td><td>'.$name[6][8].$usrname[6][8].'</td><td>'.$name[7][8].$usrname[7][8].'</td></tr>
<tr><td> 2:00</td><td>'.$name[1][9].$usrname[1][9].'</td><td>'.$name[2][9].$usrname[2][9].'</td><td>'.$name[3][9].$usrname[3][9].'</td><td>'.$name[4][9].$usrname[4][9].'</td><td>'.$name[5][9].$usrname[5][9].'</td><td> 2:00</td><td>'.$name[6][9].$usrname[6][9].'</td><td>'.$name[7][9].$usrname[7][9].'</td></tr>
<tr><td> 2:30</td><td>'.$name[1][10].$usrname[1][10].'</td><td>'.$name[2][10].$usrname[2][10].'</td><td>'.$name[3][10].$usrname[3][10].'</td><td>'.$name[4][10].$usrname[4][10].'</td><td>'.$name[5][10].$usrname[5][10].'</td><td> 2:30</td><td>'.$name[6][10].$usrname[6][10].'</td><td>'.$name[7][10].$usrname[7][10].'</td></tr>
<tr><td> 3:00</td><td>'.$name[1][11].$usrname[1][11].'</td><td>'.$name[2][11].$usrname[2][11].'</td><td>'.$name[3][11].$usrname[3][11].'</td><td>'.$name[4][11].$usrname[4][11].'</td><td>'.$name[5][11].$usrname[5][11].'</td><td> 3:00</td><td>'.$name[6][11].$usrname[6][11].'</td><td>'.$name[7][11].$usrname[7][11].'</td></tr>
<tr><td> 3:30</td><td>'.$name[1][12].$usrname[1][12].'</td><td>'.$name[2][12].$usrname[2][12].'</td><td>'.$name[3][12].$usrname[3][12].'</td><td>'.$name[4][12].$usrname[4][12].'</td><td>'.$name[5][12].$usrname[5][12].'</td><td> 3:30</td><td>'.$name[6][12].$usrname[6][12].'</td><td>'.$name[7][12].$usrname[7][12].'</td></tr>
<tr><td> 4:00</td><td>'.$name[1][13].$usrname[1][13].'</td><td>'.$name[2][13].$usrname[2][13].'</td><td>'.$name[3][13].$usrname[3][13].'</td><td>'.$name[4][13].$usrname[4][13].'</td><td>'.$name[5][13].$usrname[5][13].'</td><td> 4:00</td><td>'.$name[6][13].$usrname[6][13].'</td><td>'.$name[7][13].$usrname[7][13].'</td></tr>
<tr><td> 4:30</td><td>'.$name[1][14].$usrname[1][14].'</td><td>'.$name[2][14].$usrname[2][14].'</td><td>'.$name[3][14].$usrname[3][14].'</td><td>'.$name[4][14].$usrname[4][14].'</td><td>'.$name[5][14].$usrname[5][14].'</td><td> 4:30</td><td>'.$name[6][14].$usrname[6][14].'</td><td>'.$name[7][14].$usrname[7][14].'</td></tr>
<tr><td> 5:00</td><td>'.$name[1][15].$usrname[1][15].'</td><td>'.$name[2][15].$usrname[2][15].'</td><td>'.$name[3][15].$usrname[3][15].'</td><td>'.$name[4][15].$usrname[4][15].'</td><td>'.$name[5][15].$usrname[5][15].'</td><td> 5:00</td><td>'.$name[6][15].$usrname[6][15].'</td><td>'.$name[7][15].$usrname[7][15].'</td></tr>
<tr><td> 5:30</td><td>'.$name[1][16].$usrname[1][16].'</td><td>'.$name[2][16].$usrname[2][16].'</td><td>'.$name[3][16].$usrname[3][16].'</td><td>'.$name[4][16].$usrname[4][16].'</td><td>'.$name[5][16].$usrname[5][16].'</td><td> 5:30</td><td>'.$name[6][16].$usrname[6][16].'</td><td>'.$name[7][16].$usrname[7][16].'</td></tr>
<tr><td> 6:00</td><td>'.$name[1][17].$usrname[1][17].'</td><td>'.$name[2][17].$usrname[2][17].'</td><td>'.$name[3][17].$usrname[3][17].'</td><td>'.$name[4][17].$usrname[4][17].'</td><td>'.$name[5][17].$usrname[5][17].'</td><td> 6:00</td><td>'.$name[6][17].$usrname[6][17].'</td><td>'.$name[7][17].$usrname[7][17].'</td></tr>
<tr><td> 6:30</td><td>'.$name[1][18].$usrname[1][18].'</td><td>'.$name[2][18].$usrname[2][18].'</td><td>'.$name[3][18].$usrname[3][18].'</td><td>'.$name[4][18].$usrname[4][18].'</td><td>'.$name[5][18].$usrname[5][18].'</td><td> 6:30</td><td>'.$name[6][18].$usrname[6][18].'</td><td>'.$name[7][18].$usrname[7][18].'</td></tr>
<tr><td> 7:00</td><td>'.$name[1][19].$usrname[1][19].'</td><td>'.$name[2][19].$usrname[2][19].'</td><td>'.$name[3][19].$usrname[3][19].'</td><td>'.$name[4][19].$usrname[4][19].'</td><td>'.$name[5][19].$usrname[5][19].'</td><td> 7:00</td><td>'.$name[6][19].$usrname[6][19].'</td><td>'.$name[7][19].$usrname[7][19].'</td></tr>
<tr><td> 7:30</td><td>'.$name[1][20].$usrname[1][20].'</td><td>'.$name[2][20].$usrname[2][20].'</td><td>'.$name[3][20].$usrname[3][20].'</td><td>'.$name[4][20].$usrname[4][20].'</td><td>'.$name[5][20].$usrname[5][20].'</td><td> 7:30</td><td>'.$name[6][20].$usrname[6][20].'</td><td>'.$name[7][20].$usrname[7][20].'</td></tr>
<tr><td> 8:00</td><td>'.$name[1][21].$usrname[1][21].'</td><td>'.$name[2][21].$usrname[2][21].'</td><td>'.$name[3][21].$usrname[3][21].'</td><td>'.$name[4][21].$usrname[4][21].'</td><td>'.$name[5][21].$usrname[5][21].'</td><td> 8:00</td><td>'.$name[6][21].$usrname[6][21].'</td><td>'.$name[7][21].$usrname[7][21].'</td></tr>
<tr><td> 8:30</td><td>'.$name[1][22].$usrname[1][22].'</td><td>'.$name[2][22].$usrname[2][22].'</td><td>'.$name[3][22].$usrname[3][22].'</td><td>'.$name[4][22].$usrname[4][22].'</td><td>'.$name[5][22].$usrname[5][22].'</td><td> 8:30</td><td>'.$name[6][22].$usrname[6][22].'</td><td>'.$name[7][22].$usrname[7][22].'</td></tr>
<tr><td> 9:00</td><td>'.$name[1][23].$usrname[1][23].'</td><td>'.$name[2][23].$usrname[2][23].'</td><td>'.$name[3][23].$usrname[3][23].'</td><td>'.$name[4][23].$usrname[4][23].'</td><td>'.$name[5][23].$usrname[5][23].'</td><td> 9:00</td><td>'.$name[6][23].$usrname[6][23].'</td><td>'.$name[7][23].$usrname[7][23].'</td></tr>
<tr><td> 9:30</td><td>'.$name[1][24].$usrname[1][24].'</td><td>'.$name[2][24].$usrname[2][24].'</td><td>'.$name[3][24].$usrname[3][24].'</td><td>'.$name[4][24].$usrname[4][24].'</td><td>'.$name[5][24].$usrname[5][24].'</td><td> 9:30</td><td>'.$name[6][24].$usrname[6][24].'</td><td>'.$name[7][24].$usrname[7][24].'</td></tr>
<tr><td>10:00</td><td>'.$name[1][25].$usrname[1][25].'</td><td>'.$name[2][25].$usrname[2][25].'</td><td>'.$name[3][25].$usrname[3][25].'</td><td>'.$name[4][25].$usrname[4][25].'</td><td>'.$name[5][25].$usrname[5][25].'</td><td>10:00</td><td>'.$name[6][25].$usrname[6][25].'</td><td>'.$name[7][25].$usrname[7][25].'</td></tr>
<tr><td>10:30</td><td>'.$name[1][26].$usrname[1][26].'</td><td>'.$name[2][26].$usrname[2][26].'</td><td>'.$name[3][26].$usrname[3][26].'</td><td>'.$name[4][26].$usrname[4][26].'</td><td>'.$name[5][26].$usrname[5][26].'</td><td>10:30</td><td>'.$name[6][26].$usrname[6][26].'</td><td>'.$name[7][26].$usrname[7][26].'</td></tr>
<tr><td>11:00</td><td>'.$name[1][27].$usrname[1][27].'</td><td>'.$name[2][27].$usrname[2][27].'</td><td>'.$name[3][27].$usrname[3][27].'</td><td>'.$name[4][27].$usrname[4][27].'</td><td>'.$name[5][27].$usrname[5][27].'</td><td>11:00</td><td>'.$name[6][27].$usrname[6][27].'</td><td>'.$name[7][27].$usrname[7][27].'</td></tr>
<tr><td>11:30</td><td>'.$name[1][28].$usrname[1][28].'</td><td>'.$name[2][28].$usrname[2][28].'</td><td>'.$name[3][28].$usrname[3][28].'</td><td>'.$name[4][28].$usrname[4][28].'</td><td>'.$name[5][28].$usrname[5][28].'</td><td>11:30</td><td>'.$name[6][28].$usrname[6][28].'</td><td>'.$name[7][28].$usrname[7][28].'</td></tr>
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