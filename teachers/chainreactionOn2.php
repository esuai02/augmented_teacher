<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($USER->id==$teacherid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES('$USER->id',71,'chainreaction','$timecreated')");
$tlastaccess=$timecreated-604800*30;
 
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$amonthago6=$timecreated-604800*30;
$timestart=date("Y-m-d", $timecreated);
$minutes10=$timecreated-600;
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); if($nday==0)$nday=7;

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$lastreply= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE userid='$teacherid' AND event LIKE '질문알림' ORDER BY id DESC LIMIT 1 "); 
 
echo '<div id="content"> </div>';
include("quicksidebar.php");
echo '
<script>
function updateContent() {
	$.ajax({
		url: "html_chainreaction.php",
		method: "GET",
		success: function(response) {
			$("#content").html(response);// 서버로부터 가져온 내용을 div에 삽입
		},
		error: function(xhr, status, error) {
			console.error("Ajax 요청 에러:", error);
		}
	});
}
updateContent();
setInterval(updateContent, 5000);
</script>';
 
echo '
	<script>
	function quickReply(Eventid,Userid,Goalid){
		 
		$.ajax({
			url:"../students/check.php",
			type: "POST",
			dataType:"json",
			data : {
			"userid":Userid,       
			"goalid":Goalid,
			"eventid":Eventid,
						 
			},
			success:function(data){
			 }
		})	
		
 		swal("질문이 전달되었습니다.","기다리는 동안 후속 학습을 진행해 주세요.", {buttons: false,timer: 3000});
 		location.reload(); 
	}
	function quickReply2(Eventid,Userid,Goalid){
		 
		$.ajax({
			url:"../students/check.php",
			type: "POST",
			dataType:"json",
			data : {
			"userid":Userid,       
			"goalid":Goalid,
			"eventid":Eventid,
						 
			},
			success:function(data){
			 }
		})	
		
 		swal("질문이 전달되었습니다.","기다리는 동안 후속 학습을 진행해 주세요.", {buttons: false,timer: 3000});
 		location.reload(); 
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
