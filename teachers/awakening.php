<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");

echo '<meta http-equiv="refresh" content="60">';
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");
 
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE contentstype=2 AND  boardtype LIKE 'prep'  AND (tracking=5 OR tracking=6) ORDER BY timecreated DESC LIMIT 100 ");
  
$result1 = json_decode(json_encode($handwriting), True);
unset($value);
$wboardlist.= '<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></d></tr>';
foreach($result1 as $value) 
	{
	if($value['synapselevel']>0)
 	  
	echo $tsymbol3;
	$nstroke=(int)($value['nstroke']);
	$studentid=$value['userid'];
	$ave_stroke=round($nstroke/(($value['tlast']-$value['tfirst'])/60),1);
	$contentstype=$value['contentstype'];
	$nstep=$value['nstep'];
	$status=$value['status'];
	$contentstitle=$value['contentstitle'];
	$user= $DB->get_record_sql("SELECT state,lesson,lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$user->firstname.$user->lastname;  //strpos($questionimg, 'HintIMG')!= false
	//$indicators= $DB->get_record_sql("SELECT teacherid,managerid FROM mdl_abessi_indicators WHERE id='$studentid' ORDER BY id DESC LIMIT 1");
	//if($indicators->teacherid==$USER->id || $indicators->managerid==$USER->id) echo '';
	//else continue;
	if(mb_strpos($user->firstname, $tsymbol)!==false || mb_strpos($user->firstname, $tsymbol1)!==false || mb_strpos($user->firstname, $tsymbol2)!==false || mb_strpos($user->firstname, $tsymbol3)!==false) echo '';
	else continue;
	$contentsid=$value['contentsid'];
	$cmid=$value['cmid']; 
	$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" height=15>';
	if($value['depth']==1)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
	if($value['depth']==2)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
	if($value['depth']==3)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
	if($value['depth']==4)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
	if($value['depth']==5)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';
	if($value['synapselevel']!=NULL)$resultValue='지속성 ('.$value['synapselevel'].'%)';

	$bstrate=$value['nfire']/($value['nmax']+0.01)*100;
	if($bstrate>99)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666457.png';
	elseif($bstrate>70)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666432.png';
	elseif($bstrate>40)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666363.png';
	elseif($bstrate>10)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666336.png';
	else $bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666304.png';

	if($value['appraise']!=NULL)
		{
		$nappraise++;
		$totalappraise=$totalappraise+$value['appraise'];
		}
	$checkstatus='';
	$encryption_id=$value['wboardid'];
	$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15></a>';
 	if($value['teacher_check']==1)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15></a>';
	elseif($value['teacher_check']==2 && $value['nstep']==0)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15></a>'; 
	elseif($value['teacher_check']==2 && $value['nstep']>0)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1620732184001.png" width=15></a>'; 
	if($value['student_check']==1)$checkstatus='checked'; 

	$seethiswb='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$studentid.'_'.date("Y_m_d", $value['timecreated']);
	$marktext='';
	$fb=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog  WHERE wboardid='$seethiswb' ORDER BY timecreated DESC LIMIT 1 ");
	if($fb->mark>=11)$marktext='#'.$fb->mark;
	if($value['tracking']==6){$resulttype='<a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>&nbsp;&nbsp;&nbsp;';$resulttype2='<span style="color:red;">지난</span>&nbsp;&nbsp;&nbsp;'; }
	elseif($value['tracking']==5){$resulttype='<a style="color:orange;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>&nbsp;&nbsp;&nbsp;';$resulttype2='<span style="color:orange;">지난</span>&nbsp;&nbsp;&nbsp;'; }
	else {$resulttype='<a style="color:#0c0d0d;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>&nbsp;&nbsp;&nbsp;';$resulttype2='<span style="color:#0c0d0d;">지난</span>&nbsp;&nbsp;&nbsp;'; }
 
	$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
	$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
	foreach($imageTags as $imageTag)
		{
	    	$questionimg = $imageTag->getAttribute('src');
		$questionimg = str_replace(' ', '%20', $questionimg); 
		if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
		}
	$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
		 
 
	include("../whiteboard/status_icons.php");
	$hidewb='';
	if($value['status']==='review' && $value['hide']==0)$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
	elseif($value['hide']==1 && $value['status']==='review' && $role!=='student' )$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width=20>';
	elseif($role!=='student')$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
	$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a>';
 	$eventtime=round(($timecreated-$value['timecreated'])/60,0);
	if($value['timemodified']>$adayAgo ) //&& $value['flag']!=1
		{
		if($status==='review' && $value['hide']==0 ) $reviewwb.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span> <span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;"> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
		elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td  sytle="font-weight: bold;"></td></tr> ';
		elseif($value['hide']==0) $wboardlist1.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
		}
	elseif($value['timemodified']<=$adayAgo && $value['status']!=='flag'  && $value['helptext']!=='해결') 
		{
		if($status==='review' && $value['hide']==0 )$reviewwb.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
		elseif($status==='review' && $value['hide']==1 && $role!=='student' )  $reviewwb2.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td></td></tr> ';
 		elseif($value['hide']==0) $wboardlist2.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a>'.$marktext.'</td><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.$eventtime.'분 전 <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';

		}
	}
  
echo ' <br><br><br> <br> <br> 
 <table align=center width=60%>  '.$wboardlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$reviewwb.$reviewwb2.' <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'</table> 
<br><hr><br>';
 
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