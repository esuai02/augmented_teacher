<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");

$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");

$plantypes='<div class="select2-input" style="font-size: 2.5em;padding-top:1px;"> <select id="basic1" name="basic" class="form-control"  ><h3><option value="개념미션">개념미션</option><option value="심화미션">심화미션</option><option value="수능미션">수능미션</option><option value="내신미션 : 개념공부">내신미션 : 개념공부</option><option value="내신미션 : 유형공부">내신미션 : 유형공부</option><option value="내신미션 : 심화학습">내신미션 : 심화학습</option><option value="내신미션 : 모의시험">내신미션 : 모의시험</option><option value="내신미션 : 복습활동">내신미션 : 복습활동</option><option value="내신미션 : 시험전날">내신미션 : 시험전날</option>
</h3></select> </div>';
if($role!=='student')
	{
	$create1='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=3"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create2='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=4"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create3='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=5"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create4='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/createdb.php?contentstype=6"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	$create5='<a href="http://twinery.org/2/#!/stories"target="_blank"><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641246221.png width=20></a>';
	}
$thisyear = date("Y",time());
//<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=6"target="_blank">성찰하기</a> '.$create4.'</td>
echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"><table width=100%><tr><td width=5%></td><td width=20%><h5 style="padding-top:10px;">'.$username->lastname.' 천재의 '.$thisyear.'년 계획</h5> </td><td width=30%></td><td><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641245056.png width=40></td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=3"target="_blank">사용법</a> '.$create1.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=4"target="_blank">커리큘럼</a> '.$create2.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=5"target="_blank">공부법</a> '.$create3.'</td>

<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/metacognition_synapse.php?contentstype=8"target="_blank">마인드 빌드업</a> '.$create5.'</td></tr></table></div></div><div class="card-body"> ';
 
$attendlog = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND hide=0 ORDER by deadline");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text=$value['memo'];										
	$deadline= $value['deadline'];    
	$dateString = date("Y-m-d",$deadline);
	$checkbox='';
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';

	$checkcomplete='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck(150,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$checkhide='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="updatecheck2(200,'.$studentid.','.$missionid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$goalsteps.= '<tr> <td width=5%></td> <td width=3%>'.$checkcomplete.'</td><td width=15% align=left style="font-size:12pt">'.$plantype.'</td> <td width=30% align=left style="font-size:12pt">'.$text.'</td> <td width=5%></td> <td width=10% style="font-size:10pt">'.$dateString.'</td><td width=5%>'.$checkdeadline.'</td><td width=10% style="font-size:10pt">도움말 (컨텐츠 은행에서 선택/<a href="https://docs.google.com/document/d/10mtwkRyQ6sGjSUDrN4c39FuGPoCWu3BovqI-Oibnyzo/edit" target="_blank">메뉴얼</a>)</td><td width=3%>'.$checkhide.'</td></tr>';		 									
	}
	echo '<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$goalsteps.'</table>											
	<table class="table" align=center><thead><tr><th scope="col" style="width: 2%; font-size:12pt" ></th><th scope="col" style="width: 15%; font-size:15pt" >'.$plantypes.'</th><th  style="width:15%; font-size:18pt"><input type="text" class="form-control" id="datepicker" name="datepicker"  placeholder="데드라인"></th><th scope="col" style="width: 40%; font-size:18pt" ><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="학습방향"></th><th scope="col" >
	<span  onclick="inputgoalstep(8,'.$studentid.',$(\'#basic1\').val(),$(\'#datepicker\').val(),$(\'#squareInput\').val()) "><img src="http://mathking.kr/Contents/Moodle/save.gif" width=40></a></span></th><th><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id=827"><h6 style="padding-top:15px;">목표입력 <img style="padding-bottom:7px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641435389.png width=35"></h6></a></th>
	</tr></thead></table> 
				
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
	<script>
		function inputgoalstep(Eventid,Userid,Plantype,Deadline,Inputtext){   
		swal("입력이 완료되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
			type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			
			  "plantype":Plantype,
			  "deadline":Deadline,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		setTimeout(function() {location.reload(); },100);
		}
 
		function updatecheck(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		swal("완료상태가 변경되었습니다.", {buttons: false,timer: 1000});
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

		function updatecheck2(Eventid,Userid,Missionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 alert("체크 상태에서 새로고침하면 목록에서 사라집니다");
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


</body>';

?>