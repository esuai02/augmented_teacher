<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$teacherid=$_GET['teacherid'];

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
$timecreated=time();
$halfdayago=$timecreated-43200;
$adayago=$timecreated-86400;
$aweekago=$timecreated-604800; 
$amonthago6=$timecreated-604800*30;

echo '<meta http-equiv="refresh" content="180">';
  
$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$assistantid1=$collegues->mntr1;
$assistantid2=$collegues->mntr2;
$assistantid3=$collegues->mntr3; 

$teachername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");

if($tsymbol==NULL)$tsymbol='##';
if($tsymbol1==NULL)$tsymbol1='##';
if($tsymbol2==NULL)$tsymbol2='##';
if($tsymbol3==NULL)$tsymbol3='##';

$students=$DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended='0' AND lastaccess> '$aweekago' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  

//$students=$DB->get_records_sql("SELECT * FROM mdl_abessi_indicators WHERE (teacherid LIKE '$teacherid' OR teacherid LIKE '$assistantid1' OR teacherid LIKE '$assistantid2' OR teacherid LIKE '$assistantid3' )  AND userid NOT LIKE '$teacherid' AND userid NOT LIKE '$assistantid1' AND userid NOT LIKE '$assistantid2' AND userid NOT LIKE '$assistantid3' AND timecreated > '$halfdayago' ");  
 
$result= json_decode(json_encode($students), True);
$ninspect=1;
unset($value);
foreach($result as $value)
	{
	$userid=$value['id']; 
 
	$thisboard=$DB->get_record_sql("SELECT id,wboardid,status,timemodified FROM mdl_abessi_messages WHERE  userid='$userid'  AND teacher_check NOT LIKE '2' AND status NOT LIKE 'flag' AND status NOT LIKE 'attempt' AND status NOT LIKE 'begin' AND status NOT LIKE 'exam' AND status NOT LIKE 'sequence' AND status NOT LIKE 'evidence' AND status NOT LIKE 'modify' AND status NOT LIKE 'explain' AND status NOT LIKE 'direct' AND contentstype=2 AND active=1 AND timemodified  >'$aweekago'  ORDER BY timemodified ASC LIMIT 1");
	$tmodified=$timecreated-$thisboard->timemodified;
    if($thisboard->id==NULL)continue;

    $std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
    $stdname=$std->firstname.$std->lastname;  
 		 
    if($ninspect<=12)$wboardlist.='<table width=49% align=center><tr style="background-color:#c2ecff;"><td align=center><b style="font-size:20;"><a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"target="_blank">'.$stdname.'</a>'.(int)(($tmodified)/60).'분 전</b>   &nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$thisboard->wboardid.'&mode=full"target="_blank">'.$thisboard->wboardid.'</a>  | '.$thisboard->status.' </td></tr><tr><td align=center><iframe  scrolling="no"; style="top: 0; left: 0; width: 100%; height:800px; border: 1px solid black;" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_grade.php?id='.$thisboard->wboardid.'"></iframe></td></tr></table>';
   
        
    if($thisboard->id!=NULL)$ninspect++;
    //if($ninspect>10)exit;
	} 
 if($wboardlist==NULL)$wboardlist='<table align=center width=80%><tr><td align=center><b style="font-size:20;">KAIST TOUCH MATH</b></td></tr><tr><td align=center><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cleared.png" width=100%></td></tr></table>';
echo '
		<div class="main-panel">
			<div class="content">  
				<div class="container-fluid">	
                <div class="row">
                    <div class="col-md-12">
                    <div class="table-wrapper"><div style="display: flex; flex-wrap: wrap;">'.$wboardlist.'</div></div>
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
