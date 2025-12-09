<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");

$timecreated=time();
$contentstype=$_GET["contentstype"];
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','matacontents','$timecreated')");
 
if($contentstype==3)$cnttype='Mathking 사용법';
elseif($contentstype==4)$cnttype='Mathking 커리큘럼 촉진';
elseif($contentstype==5)$cnttype='Mathking 공부법';
elseif($contentstype==6)$cnttype='Mathking 성찰하기';
elseif($contentstype==8)$cnttype='마인드 빌드업';
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_orchestration WHERE  hide=0 AND contentstype='$contentstype' ORDER by id DESC");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$synid=$value['id'];
 	$title=$value['title'];
	$text=$value['instruction'];
	$wboardid=$value['wboardid'];
	$checkbox='';
	$checkbox='<div class="form-check"><label class="form-check-label"><input type="checkbox"  onclick="hideschedule(250,'.$studentid.','.$synid.',  this.checked)"/><span class="form-check-sign"></span></label></div>';
	$goalsteps.= '<tr> <td width=5%></td> <td width=3%>'.$checkbox.'</td><td width=15% align=left style="font-size:12pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$wboardid.'&contentid='.$contentstype.'">제목 : '.$title.'</a></td> <td width=30% align=left style="font-size:12pt">'.$text.'</td> <td width=5%></td></tr>';
	}
echo '<div class="row"><div class="col-md-12"><div class="card"><div class="card-header"><div class="card-title"><table width=100%><tr><td width=5%></td><td><h5>'.$cnttype.'</h5> </td><td width=30%></td><td><img style="padding-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641245056.png width=40></td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/meta_howto.php"target="_blank">사용법</a> '.$create1.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/meta_curriculum.php"target="_blank">커리큘럼</a> '.$create2.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/meta_studycode.php"target="_blank">공부법</a> '.$create3.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/meta_reflection.php"target="_blank">성찰하기</a> '.$create4.'</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/meta_mindbuildup.php"target="_blank">마인드 빌드업</a> '.$create5.'</td></tr></table></div></div><div class="card-body">
<table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$goalsteps.'</table></div></div></div></div></div></div></div>';
 
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
 
 
		function hideschedule(Eventid,Userid,Missionid, Checkvalue){
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