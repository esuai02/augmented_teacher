<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$nedit=1 ;
$nprev=$nedit+1;
$nnext=$nedit-1;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
$timecreated=time();
//$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");
$timeplan = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' AND pinned=1  ORDER BY timecreated DESC LIMIT 1 ");
$sch_id=$timeplan->id;

$typetext1='기본';
$typetext2='특강';
$typetext3='임시';
if($timeplan->type==='기본')$typetext1='<b style="color:red;">기본</b>';
elseif($timeplan->type==='특강')$typetext2='<b style="color:red;">특강</b>';
elseif($timeplan->type==='임시')$typetext3='<b style="color:red;">임시</b>';


	$sch_id=$timeplan->id;
	$weektotal=$timeplan->duration1+$timeplan->duration2+$timeplan->duration3+$timeplan->duration4+$timeplan->duration5+$timeplan->duration6+$timeplan->duration7;
	$edittime=date('m/d',$timeplan->timecreated);
	$startdate=$timeplan->date;
	$start1=$timeplan->start1;
	$start2=$timeplan->start2;
	$start3=$timeplan->start3;
	$start4=$timeplan->start4;
	$start5=$timeplan->start5;
	$start6=$timeplan->start6;
	$start7=$timeplan->start7;

	$start11=$timeplan->start11;
	$start12=$timeplan->start12;
	$start13=$timeplan->start13;
	$start14=$timeplan->start14;
	$start15=$timeplan->start15;
	$start16=$timeplan->start16;
	$start17=$timeplan->start17;

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

	$duration1=$timeplan->duration1;
	$duration2=$timeplan->duration2;
	$duration3=$timeplan->duration3;
	$duration4=$timeplan->duration4;
	$duration5=$timeplan->duration5;
	$duration6=$timeplan->duration6;
	$duration7=$timeplan->duration7;

	if($duration1==0)$duration1=NULL;
	if($duration2==0)$duration2=NULL;
	if($duration3==0)$duration3=NULL;
	if($duration4==0)$duration4=NULL;
	if($duration5==0)$duration5=NULL;
	if($duration6==0)$duration6=NULL;
	if($duration7==0)$duration7=NULL;

	$memo1=$timeplan->memo1;
	$memo2=$timeplan->memo2;
	$memo3=$timeplan->memo3;
	$memo4=$timeplan->memo4;
	$memo5=$timeplan->memo5;
	$memo6=$timeplan->memo6;
	$memo7=$timeplan->memo7;
	$memo8=$timeplan->memo8;
	$memo9=$timeplan->memo9;
 
 
 echo ' 

					<div class="row">
						<div class="col-md-12">
							 
							<div class="card" style="width: 100%;">
								<div class="card-header">
									<div class="card-title"> </div>
								</div>
								<div class="card-body" style="padding: 15px; width: 100%;">
									<table style="width: 100%; table-layout: fixed;" class="table table-head-bg-primary mt-12" >
										<thead>
											<tr style="background-color:#4287f5;color:white;"> 
												 <td></td>
												<td align=center>월</td>
												<td align=center>화</td>
												<td align=center>수</td>
												<td align=center>목</td>
												<td align=center>금</td>
												<td align=center>토</td>
												<td align=center>일</td>
											</tr>
										</thead>
										<tbody>';
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
/*  
echo '
<tr><td align=center>'.$start1.'</td><td align=center>'.$start2.'</td><td align=center>'.$start3.'</td><td align=center>'.$start4.'</td><td align=center>'.$start5.'</td><td align=center>'.$start6.'</td><td align=center>'.$start7.'</td></tr>
<tr><td align=center>'.$duration1.'</td><td align=center>'.$duration2.'</td><td align=center>'.$duration3.'</td><td align=center>'.$duration4.'</td><td align=center>'.$duration5.'</td><td align=center>'.$duration6.'</td><td align=center>'.$duration7.'</td></tr>
<tr><td align=center> </td>
<td align=center>'.$typetext1.'<input type="checkbox" name="checkAccount"  onclick="changemode(101,'.$studentid.','.$sch_id.', this.checked)"/></td><td align=center> '.$typetext2.'<input type="checkbox" name="checkAccount"  onclick="changemode(102,'.$studentid.','.$sch_id.', this.checked)"/></td><td align=center>'.$typetext3.'<input type="checkbox" name="checkAccount" onclick="changemode(103,'.$studentid.','.$sch_id.', this.checked)"/></td>
<td align=center>현재 : '.round($Ttime->totaltime,1).' 시간 /<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$studentid.' " target="_blank" > '.$untiltoday.'시간</a> </td><td align=center>(총 '.$weektotal.'시간 / '.$memo9.'시간)</td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&eid='.$sch_id.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png" width=20></a></td></tr>			
';
*/
echo '
<tr><td align=center>시작시간</td><td align=center>'.$start1.'</td><td align=center>'.$start2.'</td><td align=center>'.$start3.'</td><td align=center>'.$start4.'</td><td align=center>'.$start5.'</td><td align=center>'.$start6.'</td><td align=center>'.$start7.'</td></tr>
<tr><td align=center>공부시간</td><td align=center>'.$duration1.'</td><td align=center>'.$duration2.'</td><td align=center>'.$duration3.'</td><td align=center>'.$duration4.'</td><td align=center>'.$duration5.'</td><td align=center>'.$duration6.'</td><td align=center>'.$duration7.'</td></tr>
<tr><td align=center>학습코스</td><td align=center>'.$memo1.'</td><td align=center>'.$memo2.'</td><td align=center>'.$memo3.'</td><td align=center>'.$memo4.'</td><td align=center>'.$memo5.'</td><td align=center>'.$memo6.'</td><td align=center>'.$memo7.'</td></tr>	
<tr><td align=center>상담시간</td><td align=center>'.$start11.'</td><td align=center>'.$start12.'</td><td align=center>'.$start13.'</td><td align=center>'.$start14.'</td><td align=center>'.$start15.'</td><td align=center>'.$start16.'</td><td align=center>'.$start17.'</td></tr>
<tr><td align=center> </td><td align=center> </td>
<td align=center>'.$typetext1.'<input type="checkbox" name="checkAccount"  onclick="changemode(101,'.$studentid.','.$sch_id.', this.checked)"/></td><td align=center> '.$typetext2.'<input type="checkbox" name="checkAccount"  onclick="changemode(102,'.$studentid.','.$sch_id.', this.checked)"/></td><td align=center>'.$typetext3.'<input type="checkbox" name="checkAccount" onclick="changemode(103,'.$studentid.','.$sch_id.', this.checked)"/></td>
<td align=center>현재 : '.round($Ttime->totaltime,1).' 시간 /<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$studentid.' " target="_blank" > '.$untiltoday.'시간</a> </td><td align=center>(총 '.$weektotal.'시간 / '.$memo9.'시간)</td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&eid='.$sch_id.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png" width=20></a></td></tr>			
';


echo '
										</tbody>
									</table>
								<hr> <table align=center><tr><td>'.$timeplan->memo8.'</td></tr></table><hr>
									
								</div>
							</div>
						</div>
					 </div>
				</div>
			</div>
			
		</div>';
 
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


	<script>
 function changemode(Eventid,Userid, Schid, Checkvalue){
		    var checkimsi = 0;
		 swal("시간표가 이동됩니다.", {buttons: false,timer: 1000}); 
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,
		                  "schid":Schid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		setTimeout(function(){location.reload();},1000); 
		}

function inputmission(userid,inputtext,deadline){
		   //tslee

		      
		        $.ajax({
		            url:"./databasewrite.php",
		            dataType:"json",
		            success:function(data){
			            }
		        })
		setTimeout(function(){
		location.reload();
		},1000); // 3000밀리초 = 3초
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
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
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


</body>';

?>
