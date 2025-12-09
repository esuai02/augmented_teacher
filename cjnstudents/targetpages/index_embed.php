<?php 
 
$timecreated=time();
//$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentindex','$timecreated')");

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
// get mission list
$trecent2=time()-31104000;  // 1year ago
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND msntype <8 AND timecreated>'$trecent2'  AND userid='$studentid'  ");
$result = json_decode(json_encode($missionlist), True);



 echo ' 

					<div class="row">
						<div class="col-md-12">
							 
							<div class="card">
								<div class="card-header">
									<div class="card-title"><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; "><tr><td align=center>    </td><td align=center> </td><td> </td> </tr></table></div>
								</div>
								<div class="card-body">
									<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; " class="table table-head-bg-primary mt-12" style="width=100%">
										<thead>
											<tr>
												<th scope="col" style="width: 0%;"></th>
												<th scope="col" style="width: 50%; font-size:16px;"> 코스별 목차보기</th>
												<th scope="col" style="width: 20%; font-size:16px;"> 시작일  </th>
												<th scope="col" style="width: 30%; font-size:16px;"> 마지막 활동 </th>
												 
											</tr>
										</thead>
										<tbody>';
  
unset($value);
foreach($result as $value)
	{
	$mid=$value['id'];
	$subject=$value['subject'];
	$mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
	$passgrade=$value['grade'];
	$subjectname=$mtname->name;
	$mtid=$mtname->mtid;
     
	$text=$value['text'];
	$deadline= $value['deadline']; 
 	echo '<tr><td> <input type="checkbox"  onclick="changecheckbox(1,'.$studentid.','.$mid.', this.checked)"/><br></td><td><div class="tooltip4"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$studentid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90" target=_blank>'.$subjectname.$text.'<span class="tooltiptext4"><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; " align=center><tr><td>진행현황<hr>데드라인<hr>점수</td></tr></table></span></div> <br></td><td>'.$passgrade.'점</td><td>'.$deadline.'</td></tr>';

	//$info= $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE event='$subject' AND userid='$studentid' AND eventid=8 ORDER BY id DESC LIMIT 1 ");
	//if(time()-$info->timecreated<864000)
	} 
echo ' 
										</tbody>
									</table>
									
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
		function inputmission(Eventid,Userid,Inputtext,Deadline){   
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "inputtext":Inputtext,
			  "deadline":Deadline,		 
		               },
		            success:function(data){
			            }
		        })

		}
		function changecheckbox(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "missionid":Missionid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
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
';

?>
