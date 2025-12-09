<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 

$tsymbol=$teacher->symbol;

echo ' 

<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
///////////////// begin of table /////////////////// 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$sssskey= sesskey(); 
$hourAgo2=time()-7200;
$hoursago3=time()-10800;
$aweekAgo=time()-604800;
$monthsago2=time()-6048000; // 10주 전
 
$nratewb=0;
$totalgrade1=0;$totalgrade2=0;
$nstudents1=0;$nstudents2=0; 

$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' AND lastaccess >'$aweekAgo' ORDER BY id DESC ");
$userlist= json_decode(json_encode($mystudents), True);

$attendance='';
$timecreated=time();
$halfdayago=time()-43200;
unset($user);
foreach($userlist as $user)
	{
	$studentid=$user['id'];
	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
 
	$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
	$timeafter=(time()-$Timelastaccess->maxtc);
 	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND (timecreated < date OR date=0)ORDER BY id DESC LIMIT 1 ");

	$starttext='start'.$nday;
	if(empty($schedule->$starttext)==0)$todayon=1;
 	$wblist1='';
 	$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
 
  
	// 화이트보드 목록 가져오기

	$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND aion=1 AND tlaststroke>'$aweekAgo'  ORDER BY tlaststroke DESC LIMIT 10");
	$result= json_decode(json_encode($handwriting), True);
	unset($value);
	foreach($result as $value) 
		{
		$encryption_id=$value['wboardid'];

		$encryption_id2=substr($encryption_id, 0, strpos($encryption_id, '_user'));
		$encryption_id2=str_replace('_user' , '', $encryption_id2);
 
		$status=$value['status'];

		$timemodified=time()-$value['timemodified'];
		if($timemodified>60)$timemodifiedtext=(INT)($timemodified/60).'분전';
		else $timemodifiedtext=(time()-$value['timemodified']).'초전';

		$tlaststroke=time()-$value['tlaststroke'];
	
		if($tlaststroke>1000000)$tlaststroketext='비어있음';
		elseif($tlaststroke>60)$tlaststroketext=(INT)($tlaststroke/60).'분전  마지막 필기';
		else $tlaststroketext=(time()-$value['tlaststroke']).'초전  마지막 필기';
	
 		$contentsid=$value['contentsid'];
		if($value['contentstype']==2)
			{
			$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
			$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();

			foreach($imageTags as $imageTag)
				{
    				$questionimg = $imageTag->getAttribute('src');
				$questionimg = str_replace(' ', '%20', $questionimg); 
				if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
				}
			}
		$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
		$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
		
		if($value['contentstype']==1)
			{
			$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
			$ctext=$getimg->pageicontent;
			$htmlDom = new DOMDocument;
			@$htmlDom->loadHTML($ctext);
			$imageTags = $htmlDom->getElementsByTagName('img');
			$extractedImages = array();
			$nimg=0;
			foreach($imageTags as $imageTag)
				{
				$nimg++;
	    			$imgSrc = $imageTag->getAttribute('src');
				$imgSrc = str_replace(' ', '%20', $imgSrc); 
				if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
				}

			$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 			$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$studentid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
			}
	
 
		$imgstatus='';
		include("../whiteboard/status_icons.php");
		$wblist1.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip2">'.$imgstatus.'<span class="tooltiptext2"><table style="" align=center><tr><td align=center>'.$tlaststroketext.' | '.$timemodifiedtext.' 업데이트 <hr>'.$questiontext.'</td></tr></table></span></div></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id2.'" target="_blank"> <img src=https://media4.giphy.com/media/2uw4pRauXH8GBjBE1P/giphy.gif?cid=82a1493bccd13b98412a677a091b8eb3a316f507f016e33e&rid=giphy.gif width=30> &nbsp;&nbsp;';
  		}
 
	////////////////////////////////////////////////////////////// 2. Begin of active users list ///////////////////////////////////////////////////////////// 

		$compratio=$Ttime->totaltime/$weektotal*100;
		 
		/////////////////////////////// 2-1. prepare  whiteboard , contentpage, users who spent time over planned time for today,
 
		$mark5='<span class="" style="color: rgb(0, 0, 0);"> '.$firstname.$lastname.'</span>';
 

		// count the number of questions of today.
		$maxtime=time()-43200;
		$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
		WHERE mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
		$Qnum1=count($recentquestions);
		$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
		WHERE mdl_question_attempt_steps.userid='$studentid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
		$Qnum2=count($recentquestions);
		$Qnum2=$Qnum1+$Qnum2;
		// evaluate carefulness of user
 
 		$ratio1=$engagement3->todayscore;

		if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
		elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
		elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
		elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
		elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
		elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
		elseif($ratio1<=100) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus2.png';
		else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
		if(  $engagement3->todayscore==NULL ||  $engagement3->todayscore==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

		// for long term evaulation
		$maxtime=time()-604800*3;			 
		$today.= '<tr><td><img src="'.$imgtoday.'" width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$mark5.'</a> </td><td>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
			<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=20></a></td><td>&nbsp;&nbsp;&nbsp;</td><td align=left>'.$wblist1.'</td><td>&nbsp;&nbsp; </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
	}	 

 
echo '기여도 ( # % )<table style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$today.'</tbody></table><hr>';
  

/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
include("quicksidebar.php");
 
echo ' 
<style>
a:link {
  color : red;
}
a:visited {
  color :grey;

}
a:hover {
  color : blue;
}
a:active {
  color : purple;
}

.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
  background-color: #e1e2e6;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip1:hover .tooltiptext1 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}
 
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 500px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 

.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width:700px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 700px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
}
a.tooltips span:after {
  position: absolute;
  bottom: 100%;
  right: 1%;
  margin-left: -10px;
  width: 0;
  height: 0;
  border-bottom: 8px solid #23ad5f;
  border-right: 8px solid #0a5cf5;
  border-left: 8px solid #0a5cf5;
}
a:hover.tooltips span {
  visibility: visible;
  opacity: 1;
  top: 0px;
  right: 0%;
  margin-left: 10px;
  z-index: 999;
  border-bottom: 1px solid #15ff00;
  border-right: 1px solid #15ff00; 
  border-left: 1px solid #15ff00;
}

 

</style>';
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
	<script src="../assets/js/demo.js"></script>
 ';
?>