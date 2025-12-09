<?php 

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid=required_param('id', PARAM_INT); 
$nedit=required_param('eid', PARAM_INT); 
$nprev=$nedit+1;
$nnext=$nedit-1;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");

$displaymode= $_GET["mode"];
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

//$schedule=$DB->get_records_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT  1 ");
 

//$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$timeplan = $DB->get_records_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' ORDER BY timecreated DESC LIMIT 20 ");
$result = json_decode(json_encode($timeplan), True);
$index=0;
foreach($result as $value)
{
$index++;
if($index==$nedit)
	{
	$weektotal=$value['duration1']+$value['duration2']+$value['duration3']+$value['duration4']+$value['duration5']+$value['duration6']+$value['duration7'];
	$edittime=date('m/d',$value['timecreated']);
	$startdate=$value['date'];
	$start1=$value['start1'];
	$start2=$value['start2'];
	$start3=$value['start3'];
	$start4=$value['start4'];
	$start5=$value['start5'];
	$start6=$value['start6'];
	$start7=$value['start7'];

	$start11=$value['start11'];
	$start12=$value['start12'];
	$start13=$value['start13'];
	$start14=$value['start14'];
	$start15=$value['start15'];
	$start16=$value['start16'];
	$start17=$value['start17'];

	$schtype=$value['type'];
	if($schtype==NULL)$schtype='기본';
	if($start1=='12:00 AM')$start1=NULL;
	if($start2=='12:00 AM')$start2=NULL;
	if($start3=='12:00 AM')$start3=NULL;
	if($start4=='12:00 AM')$start4=NULL;
	if($start5=='12:00 AM')$start5=NULL;
	if($start6=='12:00 AM')$start6=NULL;
	if($start7=='12:00 AM')$start7=NULL; 

	if($start11=='12:00 AM')$start11=NULL;
	if($start12=='12:00 AM')$start12=NULL;
	if($start13=='12:00 AM')$start13=NULL;
	if($start14=='12:00 AM')$start14=NULL;
	if($start15=='12:00 AM')$start15=NULL;
	if($start16=='12:00 AM')$start16=NULL;
	if($start17=='12:00 AM')$start17=NULL; 

	$duration1=$value['duration1'];
	$duration2=$value['duration2'];
	$duration3=$value['duration3'];
	$duration4=$value['duration4'];
	$duration5=$value['duration5'];
	$duration6=$value['duration6'];
	$duration7=$value['duration7'];

	if($duration1==0)$duration1=NULL;
	if($duration2==0)$duration2=NULL;
	if($duration3==0)$duration3=NULL;
	if($duration4==0)$duration4=NULL;
	if($duration5==0)$duration5=NULL;
	if($duration6==0)$duration6=NULL;
	if($duration7==0)$duration7=NULL;

	$memo1=$value['memo1'];
	$memo2=$value['memo2'];
	$memo3=$value['memo3'];
	$memo4=$value['memo4'];
	$memo5=$value['memo5'];
	$memo6=$value['memo6'];
	$memo7=$value['memo7'];
	$memo8=$value['memo8'];
	$memo9=$value['memo9'];
	}
}
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0);
	$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));
	$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	if($nday==1)$untiltoday=$schedule->duration1;
	if($nday==2)$untiltoday=$schedule->duration1+$schedule->duration2;
	if($nday==3)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;
	if($nday==4)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;
	if($nday==5)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;
	if($nday==6)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;
	if($nday==0)$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
echo '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon"/>
	  <link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />

	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Montserrat:100,200,300,400,500,600,700,800,900"]},
			custom: {"families":["Flaticon", "LineAwesome"], urls: ["../assets/css/fonts.css"]},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">
	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>
<body>
	<div class="wrapper">
			<div class="content">
				<div class="container-fluid"> 
					<h4 align=center class="page-title">시간표</h4>
					 
					<div class="row">
						<div class="col-md-12">
						';
if($displaymode==='edit')
	{
	$savebutton='<button type="button" onclick="editschedule(33,'.$studentid.', $(\'#timepicker1\').val(),$(\'#timepicker2\').val(),$(\'#timepicker3\').val(),$(\'#timepicker4\').val(),$(\'#timepicker5\').val(),$(\'#timepicker6\').val(),$(\'#timepicker7\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#basic3\').val(),$(\'#basic4\').val(),$(\'#basic5\').val(),$(\'#basic6\').val(),$(\'#basic7\').val())"> 저장하기 </button>';
 //<a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1">
	echo '<table class="table table-head-bg-primary mt-12" style="width=100%"><tbody>								
	<tr><td scope="col" style="width: 12.5%;"></td><td>시작<td>공부시간</td></tr>
	<tr><td scope="col" style="width: 12.5%;">월</td><td><input type="text" class="form-control" id="timepicker1" value="'.$start1.'"></td><td><div class="select2-input"><select id="basic1" name="basic1" class="form-control" ><option value="'.$duration1.'">'.$duration1.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">화</td><td><input type="text" class="form-control" id="timepicker2" value="'.$start2.'"></td><td><div class="select2-input"><select id="basic2" name="basic2" class="form-control" ><option value="'.$duration2.'">'.$duration2.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">수</td><td><input type="text" class="form-control" id="timepicker3" value="'.$start3.'"></td><td><div class="select2-input"><select id="basic3" name="basic3" class="form-control" ><option value="'.$duration3.'">'.$duration3.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">목</td><td><input type="text" class="form-control" id="timepicker4" value="'.$start4.'"></td><td><div class="select2-input"><select id="basic4" name="basic4" class="form-control" ><option value="'.$duration4.'">'.$duration4.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">금</td><td><input type="text" class="form-control" id="timepicker5" value="'.$start5.'"></td><td><div class="select2-input"><select id="basic5" name="basic5" class="form-control" ><option value="'.$duration5.'">'.$duration5.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">토</td><td><input type="text" class="form-control" id="timepicker6" value="'.$start6.'"></td><td><div class="select2-input"><select id="basic6" name="basic6" class="form-control" ><option value="'.$duration6.'">'.$duration6.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td scope="col" style="width: 12.5%;">일</td><td><input type="text" class="form-control" id="timepicker7" value="'.$start7.'"></td><td><div class="select2-input"><select id="basic7" name="basic7" class="form-control" ><option value="'.$duration7.'">'.$duration7.'</option><option value="0">0</option><option value="1">1</option><option value="1.5">1.5</option><option value="2">2</option><option value="2.5">2.5</option><option value="3">3</option><option value="3.5">3.5</option><option value="4">4</option><option value="4.5">4.5</option><option value="5">5</option><option value="5.5">5.5</option><option value="6">6</option><option value="6.5">6.5</option><option value="7">7</option><option value="7.5">7.5</option><option value="8">8</option><option value="8.5">8.5</option><option value="9">9</option><option value="9.5">9.5</option><option value="10">10</option></select></div></td></tr>
	<tr><td></td><td>'.round($Ttime->totaltime,1).' 시간 공부</td><td> 총 '.$weektotal.'시간</td></tr>	<tr><td></td><td></td><td>'.$savebutton.'</td></tr></tbody></table>';
	}
else
	{
	echo '							<table class="table table-head-bg-primary mt-12" style="width=100%">
										 	<tbody>								
												<tr><td scope="col" style="width: 12.5%;"></td><td>시작<td>공부시간</td></tr>';
												if($duration1>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">월</td><td style="font-size:18px;">'.$start1.'</td><td style="font-size:18px;">'.$duration1.'</td></tr>';
												if($duration2>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">화</td><td style="font-size:18px;">'.$start2.'</td><td style="font-size:18px;">'.$duration2.'</td></tr>';
												if($duration3>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">수</td><td style="font-size:18px;">'.$start3.'</td><td style="font-size:18px;">'.$duration3.'</td></tr>';
												if($duration4>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">목</td><td style="font-size:18px;">'.$start4.'</td><td style="font-size:18px;">'.$duration4.'</td></tr>';
												if($duration5>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">금</td><td style="font-size:18px;">'.$start5.'</td><td style="font-size:18px;">'.$duration5.'</td></tr>';
												if($duration6>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">토</td><td style="font-size:18px;">'.$start6.'</td><td style="font-size:18px;">'.$duration6.'</td></tr>';
												if($duration7>0)echo'<tr><td scope="col" style="font-size:18px;width: 12.5%;">일</td><td style="font-size:18px;">'.$start7.'</td><td style="font-size:18px;">'.$duration7.'</td></tr>';
												echo '<tr><td></td><td>'.round($Ttime->totaltime,1).' 시간 공부</td><td> 총 '.$weektotal.'시간</td></tr>		
												<tr><td></td><td></td><td> <button><a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1&mode=edit">변경하기</a></button> </td></tr></tbody></table>';		
	}
	 
echo '

 
						</div>
					</div>
				</div>
			</div>		
	</div>

</body>
</html>';

echo '
   
	  	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	
	

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

 
	<script>
	function editschedule(Eventid,Userid,Start1,Start2,Start3,Start4,Start5,Start6,Start7,Duration1,Duration2,Duration3,Duration4,Duration5,Duration6,Duration7)
		{		 
		 var Schtype= \''.$schtype.'\';	 
		  alert(Schtype);
		    $.ajax({
		            url:"database.php",
					type: "POST",
		            dataType:"json",
					data : {
					"userid":Userid,
		            "eventid":Eventid,
					"start1":Start1,
					"start2":Start2,
					"start3":Start3,
					"start4":Start4,
					"start5":Start5,
					"start6":Start6,
					"start7":Start7,
				 
					"duration1":Duration1,
					"duration2":Duration2,
					"duration3":Duration3,
					"duration4":Duration4,
					"duration5":Duration5,
					"duration6":Duration6,
					"duration7":Duration7, 
					"schtype":Schtype,	
		             },
		
		            success:function(data){ 
					},
					 
					})			
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
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
 
		$("#timepicker1").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker2").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker3").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker4").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker5").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker6").datetimepicker({
			format: "h:mm A", 
		});
		$("#timepicker7").datetimepicker({
			format: "h:mm A", 
		});
		
		$("#basic").select2({
			theme: "bootstrap"
		});
		$("#basic1").select2({
			theme: "bootstrap"
	 
		});
		$("#basic2").select2({
			theme: "bootstrap"
		});
		$("#basic3").select2({
			theme: "bootstrap"
		});
		$("#basic4").select2({
			theme: "bootstrap"
		});
		$("#basic5").select2({
			theme: "bootstrap"
		});
		$("#basic6").select2({
			theme: "bootstrap"
		});
		$("#basic7").select2({
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
	</script>';

?>





 






 

?>
