<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','flowwins','$timecreated')");

$tlastaccess=time()-604800*30;
 
$halfdayago=time()-43200;
$aweekago=time()-604800;

$questionstamps=$DB->get_records_sql("SELECT * FROM mdl_abessi_questionstamp where (status LIKE '질문전달' OR status LIKE '답변전달')  AND timemodified>'$aweekago'  ORDER BY id ASC LIMIT 30");
$stamps= json_decode(json_encode($questionstamps), True);
unset($value);  
foreach($stamps as $value)
	{
		$playindex=$value['playindex'];
		if($value['status']==='질문전달')$statusbtn='<img src="https://mathking.kr/Contents/IMAGES/helpme.png" width=30>';
		elseif($value['status']==='답변전달')$statusbtn='<img src="https://mathking.kr/Contents/IMAGES/replyicon.png" width=30>';
		$qstampstr=' 질의응답';
		$wboardid=$value['wboardid'];
		$updatestr='';
		$eventtime=date("m/d_h:m",$value['timemodified']);

 		$thisuser=$DB->get_record_sql("SELECT * FROM mdl_user WHERE firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ORDER BY id DESC LIMIT 1 ");  
		if($thisuser->id!=NULL)
			{
			$studentname='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$thisuser->id.'">'.$thisuser->firstname.$thisuser->lastname.'</a>';
			$bmklist.='<tr><td> </td><td align=left valign=top style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$studentname.$qstampstr.' </td><td><a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$wboardid.'&playindex='.$playindex.'&playstate=0&sketchstate=0&speed=3&mode=qstamp"target="_blank"> '.$statusbtn.'</a></td><td>'.$eventtime.'</td> </tr>
			<tr><td><hr></td><td><hr></td><td ><hr></td></tr>'; 
			}
		$nstamp++;
	} 
$toptitle='나누어 정복하기';
echo '<br><table  align=center><tr><td><b>'.$toptitle.' (<span onclick="playAll()"> 초기화 </span>)</b></td></tr></table><hr><table align=center width=50%>'.$bmklist.'</table>';

 
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