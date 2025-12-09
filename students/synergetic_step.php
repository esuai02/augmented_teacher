<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
require_login();
if($studentid==NULL)$studentid=$USER->id;

$timecreated=time(); 
$hoursago=$timecreated-604800;
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND student_check=1 AND timemodified > '$hoursago' ORDER BY timemodified DESC LIMIT 300");

$result = json_decode(json_encode($handwriting), True);
unset($value);
 
foreach($result as $value) 
	{
	$wboardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
	$instruction=$value['instruction'];
	$noteurl=$value['url'];
	$timestamp=$timecreated-$value['timemodified'];
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';

 
	//if($role!=='student' || $timecreated-$value['timecreated']>10800)$checkout='<input type="checkbox" name="checkAccount"  Checked  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
	//else $checkout='▶ ';
	$checkout='<input type="checkbox" name="checkAccount"  Checked  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
	if(strpos($wboardid, 'jnrsorksqcrark')!== false)
		{
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		$htmlDom = new DOMDocument;
		if($studentid==NULL)$studentid=2;
			
		@$htmlDom->loadHTML($ctext);
		$imageTags = $htmlDom->getElementsByTagName('img');
		$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
			}
		$papertest.='<tr><td width=1% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$checkout.' '.$timestamp.'</td><td width=10% > <img src="'.$imgSrc.'" width=400></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a>&nbsp; <span style="font-size:20px;">'.$instruction.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	else
		{
		$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			} 
			$papertest.='<tr><td width=1% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$checkout.' '.$timestamp.'</td><td width=10% > <img src="'.$imgSrc.'" width=400></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a>&nbsp; <span style="font-size:20px;">'.$instruction.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		} 
	}
 
	echo '<br><table align=center width=90%>
	<tr><td>'.$stdname.'의 독립세션 활동 현황판 <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-bottom:-5px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a> 인쇄용 컴퓨터에서 출력, 작성 후 선생님에게 제출해 주세요.</td><td><hr></td></tr>
	<tr><td><hr></td><td><hr></td><td><hr></td></tr></table><br>
	<table align=center width=90%><tr><td valign=top><table>'.$papertest.'</table></td></tr></table>';
	 
echo '
	<script> 
	function ChangeCheckBox(Eventid,Userid, Wboardid, Checkvalue){
		var checkimsi = 0;
		if(Checkvalue==true){
			checkimsi = 1;
		}
		swal("적용되었습니다.", {buttons: false,timer: 100});
	   $.ajax({
			url: "../students/check.php",
			type: "POST",
			dataType: "json",
			data : {"userid":Userid,       
					"wboardid":Wboardid,
					"checkimsi":checkimsi,
					 "eventid":Eventid,
				   },
			success: function (data){  
			}
		});
		setTimeout(function(){
 		 location.reload();
		}, 200);
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
