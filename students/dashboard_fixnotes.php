<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid=$_GET['userid'];
$timecreated=time();
$halfdayago=$timecreated-43200;
$adayago=$timecreated-86400;
$aweekago=$timecreated-604800; 
$amonthago6=$timecreated-604800*30;

echo '<meta http-equiv="refresh" content="300">';
   
 
$fixnotes=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND ((status NOT LIKE 'flag' AND status NOT LIKE 'attempt' AND contentstype=2) ||  boardtype LIKE 'complementary') AND active=1 AND timemodified  >'$adayago'  ORDER BY timemodified DESC");

$result= json_decode(json_encode($fixnotes), True);
$ninspect=1;
unset($value);
foreach($result as $value)
	{
	$userid=$value['userid']; 

	$tmodified=$timecreated-$value['timemodified'];
    
    $std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
    $stdname=$std->firstname.$std->lastname;
    if($value['teacher_check']!=2)$wboardlist1.='<table width=49% align=center><tr style="background-color:#c2ecff;"><td align=center><b style="font-size:20;">'.$stdname.(int)(($tmodified)/60).'분 전</b>   &nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$value['wboardid'].'&mode=full"target="_blank">'.$value['wboardid'].'</a>  | '.$value['status'].' </td></tr><tr><td align=center><iframe  scrolling="no"; style="top: 0; left: 0; width: 100%; height:800px; border: 1px solid black;" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$value['wboardid'].'"></iframe></td></tr></table>';	 
    else $wboardlist2.='<table width=49% align=center><tr style="background-color:#c2ecff;"><td align=center><b style="font-size:20;">'.$stdname.(int)(($tmodified)/60).'분 전</b>   &nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$value['wboardid'].'&mode=full"target="_blank">'.$value['wboardid'].'</a>  | '.$value['status'].' </td></tr><tr><td align=center><iframe  scrolling="no"; style="top: 0; left: 0; width: 100%; height:800px; border: 1px solid black;" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$value['wboardid'].'"></iframe></td></tr></table>';
   
    $ninspect++;
    //if($ninspect>10)exit;
	} 
 if($wboardlist==NULL)$wboardlist='<table align=center width=80%><tr><td align=center><b style="font-size:20;">KAIST TOUCH MATH</b></td></tr><tr><td align=center><img src="https://cdn.pixabay.com/photo/2018/10/02/15/18/cloud-3719093_1280.png" width=100%></td></tr></table>';
echo '
		<div class="main-panel">
			<div class="content">  
				<div class="container-fluid">	
                <div class="row">
                    <div class="col-md-12">
                    <div class="table-wrapper"><div style="display: flex; flex-wrap: wrap;">'.$wboardlist1.'<table width=100%><tr><td><hr style="border: 2px dashed;color:green;"></td></tr></table>'.$wboardlist2.'</div></div>
                    </div></div></div></div></div><hr><table align=center><tr><td><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/refreshpage.png width=50 onclick="location.reload()"></td></tr></table>';
        
echo ' <!--   Core JS Files   -->
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

    <style>
	.table-wrapper {
		position: relative;
		height: 100% /* 테이블이 표시될 영역의 높이를 지정하세요 */
		overflow: auto;
	  }
	  
	.table-wrapper thead {
		position: sticky;
		top: 0;
		background-color: #FFE4C1; /* 첫 번째 행의 배경색을 지정하세요 */
		z-index: 1;
	  } 
    </style>
';
?>
