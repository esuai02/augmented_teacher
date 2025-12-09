<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$studentid=required_param('id', PARAM_INT);
$cid=required_param('cid', PARAM_INT);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid'  ");
$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE subject='$cid' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$deadline=$mission->deadline;
$hours=$mission->hours;
$weekhours=$mission->weekhours;
 
 echo ' 
				<div class="row"> 
						<div class="col-md-8">
							<div class="card">
								<div class="card-header">
								<div class="card-title">'.$curri->name.' . . . . . ✐</div>
								<div class="card-title">
</div></div><div class="card-body">

<table class="table table-hover"><thead><tr>
<th scope="col" style="width: 15%;">#</th>
<th scope="col" style="width: 30%;">단원명</th>
<th scope="col" style="width: 20%;">인증시험 (통과 : '.$mission->grade.' 점)</th>
<th scope="col" style="width: 10%;">난이도</th>
<th scope="col" style="width: 30%;">&nbsp;&nbsp;<button type="button" onclick="inputmission2(12,'.$studentid.','.$cid.')"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmissionhome.php?id='.$studentid.'&cid='.$cid.'">일정 만들기</a></button>

</th>
</tr></thead><tbody>';
 
for($nch=1;$nch<=$curri->nch;$nch++)
{
$ch='ch'.$nch;
$ch=$curri->$ch;
$cnt='cnt'.$nch;
$cnt=$curri->$cnt;
$qid='qid'.$nch;
$qid=$curri->$qid;
$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$qid' ");
$quizid=$cmid->inst;
$datepicker='datepicker'.$nch;
$dday2='dday'.$nch;

$getdate2=$mission->$dday2;
//echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------'.$dday2.'----------------s'.$mission->timecreated.'-----'.$datepicker.'-----'.$getdate2.'---<br>';
$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 
$grade=round($quizattempt->sgrades/$quizattempt->tgrades*100,0); 
//$check=0
//if($grade>=passgrade)$check=1;

echo'<tr><td>'.$nch.'</td><td><a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cnt.'" target="_blank">'.$ch.'</td>
<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">인증시험 ('.$grade.'점)</a></td><td>보통</td><td><input type="text" class="form-control" id="'.$datepicker.'" value="'.$getdate2.'" name="'.$datepicker.'"  placeholder="'.$getdate2.'"></td></tr>';
}
echo '
<tr><td> </td><td></td><td></td><td></td><td></td></tr></tbody></table>

<table><thead><tr>
 
<th scope="col" style="width: 20%;"></th>
<th scope="col" style="width: 30%;">&nbsp;&nbsp;&nbsp;시간 / 한단원</th>
<th scope="col" style="width: 5%;"></th>
<th scope="col" style="width: 30%;">&nbsp;&nbsp;&nbsp;시간 / 1주일</th> 
<th scope="col" style="width: 15%;"></th>
</tr><tr>
<td></td>
<td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value="'.$hours.'">'.$hours.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10">10시간</option> <option value="11">11시간</option> 
<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option>  </select></div></td>
<td></td>
<td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="'.$weekhours.'">'.$weekhours.'시간</option><option value="3">3시간</option><option value="4">4시간</option><option value="5">5시간</option><option value="6">6시간</option><option value="7">7시간</option><option value="8">8시간</option> <option value="9">9시간</option> <option value="10">10시간</option> <option value="11">11시간</option> 
<option value="12">12시간</option> <option value="13">13시간</option> <option value="14">14시간</option><option value="15">15시간</option> <option value="16">16시간</option> <option value="17">17시간</option> <option value="18">18시간</option> <option value="19">19시간</option> <option value="20">20시간</option> <option value="21">21시간</option> <option value="22">22시간</option> <option value="23">23시간</option> <option value="24">24시간</option> <option value="25">25시간</option> <option value="26">26시간</option> <option value="27">27시간</option> <option value="28">28시간</option> <option value="29">29시간</option> <option value="30">30시간</option> </select></div></td>
<td><button type="button" onclick="editschedule(13,$studentid,$cid,$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker1\').val(),$(\'#datepicker2\').val(),$(\'#datepicker3\').val(),
$(\'#datepicker4\').val(),$(\'#datepicker5\').val(),$(\'#datepicker6\').val(),$(\'#datepicker7\').val(),$(\'#datepicker8\').val(),$(\'#datepicker9\').val(),$(\'#datepicker10\').val(),$(\'#datepicker11\').val(),
$(\'#datepicker12\').val(),$(\'#datepicker13\').val(),$(\'#datepicker14\').val(),$(\'#datepicker15\').val())"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&cid='.$cid.'">저장하기</a></button></td>
<td></td></tr>

</table>
</div></div></div>
								<div class="card-body">
									<div class="row">
										<div class="col-md-12">
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">오늘학습량</span>
														<span class="text-muted"> $3K</span>
													</div>
													<div class="progress" style="height: 2px;">
														<div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="78%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">주간학습량</span>
														<span class="text-muted"> 576</span>
													</div>
													<div class="progress" style="height: 4px;">
														<div class="progress-bar bg-info" role="progressbar" style="width: 65%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">완료율</span>
														<span class="text-muted fw-bold"> 70%</span>
													</div>
													<div class="progress" style="height: 6px;">
														<div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="70%"></div>
													</div>
												</div>
											</div>
											<div class="demo">
												<div class="progress-card">
													<div class="progress-status">
														<span class="text-muted">잔여시간</span>
														<span class="text-muted fw-bold"> 60%</span>
													</div>
													<div class="progress">
														<div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="60%"></div>
													</div>
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
 
		function editschedule(Eventid,Userid,Subject,Hours,Weekhours,Day1,Day2,Day3,Day4,Day5,Day6,Day7,Day8,Day9,Day10,Day11,Day12,Day13,Day14,Day15)
		{		
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			  data : {
		             "eventid":Eventid,
			"userid":Userid,
			"subject":Subject,
			"hours":Hours,
			"weekhours":Weekhours,
			"day1":Day1,
			"day2":Day2,
			"day3":Day3,
			"day4":Day4,
			"day5":Day5,
			"day6":Day6,
			"day7":Day7,
			"day8":Day8,
			"day9":Day9,
			"day10":Day10,
			"day11":Day11,
			"day12":Day12,
			"day13":Day13,
			"day14":Day14,
			"day15":Day15,
		               },
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},3000); 
		}
		function inputmission2(Eventid,Userid,Subject){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,   
			  "subject":Subject,	     
		               },
		            success:function(data){
			            }
		        })
		 
		setTimeout(function(){
		location.reload();
		},3000);  
		}
 
		function ChangeCheckBox(Eventid,Userid, Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                "missionid":Questionid,
		                "attemptid":Missionid,
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
		 
		$("#datepicker1").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker2").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker3").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker4").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker5").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker6").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker7").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker8").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker9").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker10").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker11").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker12").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker13").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker14").datetimepicker({
			format: "YYYY/MM/DD",
		});
		$("#datepicker15").datetimepicker({
			format: "YYYY/MM/DD",
		});

		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic1").select2({
			theme: "bootstrap"
		});
		$("#basic2").select2({
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
	
';

echo '
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don\'t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		var lineChart = document.getElementById(\'lineChart\').getContext(\'2d\'),
		barChart = document.getElementById(\'barChart\').getContext(\'2d\'),
		pieChart = document.getElementById(\'pieChart\').getContext(\'2d\'),
		doughnutChart = document.getElementById(\'doughnutChart\').getContext(\'2d\'),
		radarChart = document.getElementById(\'radarChart\').getContext(\'2d\'),
		bubbleChart = document.getElementById(\'bubbleChart\').getContext(\'2d\'),
		multipleLineChart = document.getElementById(\'multipleLineChart\').getContext(\'2d\'),
		multipleBarChart = document.getElementById(\'multipleBarChart\').getContext(\'2d\'),
		htmlLegendsChart = document.getElementById(\'htmlLegendsChart\').getContext(\'2d\');

		var myLineChart = new Chart(lineChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [{
					label: "Active Users",
					borderColor: "#1d7af3",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#1d7af3",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [542, 480, 430, 550, 530, 453, 380, 434, 568, 610, 700, 900]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'bottom\',
					labels : {
						padding: 10,
						fontColor: \'#1d7af3\',
					}
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				}
			}
		});

		var myBarChart = new Chart(barChart, {
			type: \'bar\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets : [{
					label: "Sales",
					backgroundColor: \'rgb(23, 125, 255)\',
					borderColor: \'rgb(23, 125, 255)\',
					data: [3, 2, 9, 5, 4, 6, 4, 6, 7, 8, 7, 4],
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				},
			}
		});

		var myPieChart = new Chart(pieChart, {
			type: \'pie\',
			data: {
				datasets: [{
					data: [50, 35, 15],
					backgroundColor :["#1d7af3","#f3545d","#fdaf4b"],
					borderWidth: 0
				}],
				labels: [\'New Visitors\', \'Subscribers\', \'Active Users\'] 
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position : \'bottom\',
					labels : {
						fontColor: \'rgb(154, 154, 154)\',
						fontSize: 11,
						usePointStyle : true,
						padding: 20
					}
				},
				pieceLabel: {
					render: \'percentage\',
					fontColor: \'white\',
					fontSize: 14,
				},
				tooltips: false,
				layout: {
					padding: {
						left: 20,
						right: 20,
						top: 20,
						bottom: 20
					}
				}
			}
		})

		var myDoughnutChart = new Chart(doughnutChart, {
			type: \'doughnut\',
			data: {
				datasets: [{
					data: [10, 20, 30],
					backgroundColor: [\'#f3545d\',\'#fdaf4b\',\'#1d7af3\']
				}],

				labels: [
				\'Red\',
				\'Yellow\',
				\'Blue\'
				]
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: \'bottom\'
				},
				layout: {
					padding: {
						left: 20,
						right: 20,
						top: 20,
						bottom: 20
					}
				}
			}
		});

		var myRadarChart = new Chart(radarChart, {
			type: \'radar\',
			data: {
				labels: [\'Running\', \'Swimming\', \'Eating\', \'Cycling\', \'Jumping\'],
				datasets: [{
					data: [20, 10, 30, 2, 30],
					borderColor: \'#1d7af3\',
					backgroundColor : \'rgba(29, 122, 243, 0.25)\',
					pointBackgroundColor: "#1d7af3",
					pointHoverRadius: 4,
					pointRadius: 3,
					label: \'Team 1\'
				}, {
					data: [10, 20, 15, 30, 22],
					borderColor: \'#716aca\',
					backgroundColor: \'rgba(113, 106, 202, 0.25)\',
					pointBackgroundColor: "#716aca",
					pointHoverRadius: 4,
					pointRadius: 3,
					label: \'Team 2\'
				},
				]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend : {
					position: \'bottom\'
				}
			}
		});

		var myBubbleChart = new Chart(bubbleChart,{
			type: \'bubble\',
			data: {
				datasets:[{
					label: "Car", 
					data:[{x:25,y:17,r:25},{x:30,y:25,r:28}, {x:35,y:30,r:8}], 
					backgroundColor:"#716aca"
				},
				{
					label: "Motorcycles", 
					data:[{x:10,y:17,r:20},{x:30,y:10,r:7}, {x:35,y:20,r:10}], 
					backgroundColor:"#1d7af3"
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'bottom\'
				},
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero:true
						}
					}],
					xAxes: [{
						ticks: {
							beginAtZero:true
						}
					}]
				},
			}
		});

		var myMultipleLineChart = new Chart(multipleLineChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [{
					label: "Python",
					borderColor: "#1d7af3",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#1d7af3",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [30, 45, 45, 68, 69, 90, 100, 158, 177, 200, 245, 256]
				},{
					label: "PHP",
					borderColor: "#59d05d",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#59d05d",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [10, 20, 55, 75, 80, 48, 59, 55, 23, 107, 60, 87]
				}, {
					label: "Ruby",
					borderColor: "#f3545d",
					pointBorderColor: "#FFF",
					pointBackgroundColor: "#f3545d",
					pointBorderWidth: 2,
					pointHoverRadius: 4,
					pointHoverBorderWidth: 1,
					pointRadius: 4,
					backgroundColor: \'transparent\',
					fill: true,
					borderWidth: 2,
					data: [10, 30, 58, 79, 90, 105, 117, 160, 185, 210, 185, 194]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position: \'top\',
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				}
			}
		});

		var myMultipleBarChart = new Chart(multipleBarChart, {
			type: \'bar\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets : [{
					label: "First time visitors",
					backgroundColor: \'#59d05d\',
					borderColor: \'#59d05d\',
					data: [95, 100, 112, 101, 144, 159, 178, 156, 188, 190, 210, 245],
				},{
					label: "Visitors",
					backgroundColor: \'#fdaf4b\',
					borderColor: \'#fdaf4b\',
					data: [145, 256, 244, 233, 210, 279, 287, 253, 287, 299, 312,356],
				}, {
					label: "Pageview",
					backgroundColor: \'#177dff\',
					borderColor: \'#177dff\',
					data: [185, 279, 273, 287, 234, 312, 322, 286, 301, 320, 346, 399],
				}],
			},
			options: {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					position : \'bottom\'
				},
				title: {
					display: true,
					text: \'Traffic Stats\'
				},
				tooltips: {
					mode: \'index\',
					intersect: false
				},
				responsive: true,
				scales: {
					xAxes: [{
						stacked: true,
					}],
					yAxes: [{
						stacked: true
					}]
				}
			}
		});

		// Chart with HTML Legends

		var gradientStroke = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke.addColorStop(0, \'#177dff\');
		gradientStroke.addColorStop(1, \'#80b6f4\');

		var gradientFill = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill.addColorStop(0, "rgba(23, 125, 255, 0.7)");
		gradientFill.addColorStop(1, "rgba(128, 182, 244, 0.3)");

		var gradientStroke2 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke2.addColorStop(0, \'#f3545d\');
		gradientStroke2.addColorStop(1, \'#ff8990\');

		var gradientFill2 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill2.addColorStop(0, "rgba(243, 84, 93, 0.7)");
		gradientFill2.addColorStop(1, "rgba(255, 137, 144, 0.3)");

		var gradientStroke3 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientStroke3.addColorStop(0, \'#fdaf4b\');
		gradientStroke3.addColorStop(1, \'#ffc478\');

		var gradientFill3 = htmlLegendsChart.createLinearGradient(500, 0, 100, 0);
		gradientFill3.addColorStop(0, "rgba(253, 175, 75, 0.7)");
		gradientFill3.addColorStop(1, "rgba(255, 196, 120, 0.3)");

		var myHtmlLegendsChart = new Chart(htmlLegendsChart, {
			type: \'line\',
			data: {
				labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
				datasets: [ {
					label: "Subscribers",
					borderColor: gradientStroke2,
					pointBackgroundColor: gradientStroke2,
					pointRadius: 0,
					backgroundColor: gradientFill2,
					legendColor: \'#f3545d\',
					fill: true,
					borderWidth: 1,
					data: [154, 184, 175, 203, 210, 231, 240, 278, 252, 312, 320, 374]
				}, {
					label: "New Visitors",
					borderColor: gradientStroke3,
					pointBackgroundColor: gradientStroke3,
					pointRadius: 0,
					backgroundColor: gradientFill3,
					legendColor: \'#fdaf4b\',
					fill: true,
					borderWidth: 1,
					data: [256, 230, 245, 287, 240, 250, 230, 295, 331, 431, 456, 521]
				}, {
					label: "Active Users",
					borderColor: gradientStroke,
					pointBackgroundColor: gradientStroke,
					pointRadius: 0,
					backgroundColor: gradientFill,
					legendColor: \'#177dff\',
					fill: true,
					borderWidth: 1,
					data: [542, 480, 430, 550, 530, 453, 380, 434, 568, 610, 700, 900]
				}]
			},
			options : {
				responsive: true, 
				maintainAspectRatio: false,
				legend: {
					display: false
				},
				tooltips: {
					bodySpacing: 4,
					mode:"nearest",
					intersect: 0,
					position:"nearest",
					xPadding:10,
					yPadding:10,
					caretPadding:10
				},
				layout:{
					padding:{left:15,right:15,top:15,bottom:15}
				},
				scales: {
					yAxes: [{
						ticks: {
							fontColor: "rgba(0,0,0,0.5)",
							fontStyle: "500",
							beginAtZero: false,
							maxTicksLimit: 5,
							padding: 20
						},
						gridLines: {
							drawTicks: false,
							display: false
						}
					}],
					xAxes: [{
						gridLines: {
							zeroLineColor: "transparent"
						},
						ticks: {
							padding: 20,
							fontColor: "rgba(0,0,0,0.5)",
							fontStyle: "500"
						}
					}]
				}, 
				legendCallback: function(chart) { 
					var text = []; 
					text.push(\'<ul class="\' + chart.id + \'-legend html-legend">\'); 
					for (var i = 0; i < chart.data.datasets.length; i++) { 
						text.push(\'<li><span style="background-color:\' + chart.data.datasets[i].legendColor + \'"></span>\'); 
						if (chart.data.datasets[i].label) { 
							text.push(chart.data.datasets[i].label); 
						} 
						text.push(\'</li>\'); 
					} 
					text.push(\'</ul>\'); 
					return text.join(\'\'); 
				}  
			}
		});

		var myLegendContainer = document.getElementById("myChartLegend");

		// generate HTML legend
		myLegendContainer.innerHTML = myHtmlLegendsChart.generateLegend();

		// bind onClick event to all LI-tags of the legend
		var legendItems = myLegendContainer.getElementsByTagName(\'li\');
		for (var i = 0; i < legendItems.length; i += 1) {
			legendItems[i].addEventListener("click", legendClickCallback, false);
		}

	</script>

';

?>