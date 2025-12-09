<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
 
include("p_navbar.php");
echo ' <div class="col-md-12">';

$tbegin=$_GET["tb"]; 
$tbegin=time()-$tbegin;  
//$handwriting=$DB->get_records_sql("SELECT  *  FROM mdl_abessi_messages WHERE  userrole LIKE 'student' AND timemodified>'$tbegin' AND ( present=1 OR present=2 ) AND active=1 ORDER BY timemodified DESC LIMIT 100 ");
$handwriting=$DB->get_records_sql("SELECT  *  FROM mdl_abessi_messages WHERE  userrole LIKE 'student' AND timemodified>'$tbegin' AND  status='present' ORDER BY timemodified DESC LIMIT 100 ");
$result= json_decode(json_encode($handwriting), True);
 
unset($value);
foreach($result as $value) 
	{
	$userid=$value['userid'];
	$reviewer=$value['userto'];
	 
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");  // for teachers
	$studentname=$username->firstname.$username->lastname; // for teachers
	$timestart=time()-43200;
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");
	if($goal->submit==1)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20>';
	$nfeedback=$value['nfeedback'];
	$Q_id=$value['contentsid'];
	$contentstype=$value['contentstype'];
	$encryption_id=$value['wboardid'];
	$source= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$userid' AND contentsid='$Q_id' AND contentstype='$contentstype' AND wboardid LIKE '%nx4HQkXq%' ORDER BY id DESC LIMIT 1 ");
	$tlastfeedback=(INT)((time()-$source->timemodified)/60);
	$feedbacktext='<table align=left style="font-size=14;color:blue;">
	<tr><td>'.$source->feedback1.'<br></td></tr>
	<tr><td>'.$source->feedback2.'<br></td></tr>
	<tr><td>'.$source->feedback3.'<br></td></tr>
	<tr><td>'.$source->feedback4.'<br></td></tr>
	<tr><td>'.$source->feedback5.'<br></td></tr>
	<tr><td>'.$source->feedback6.'<br></td></tr>
	<tr><td>'.$source->feedback7.'<br></td></tr>
	<tr><td>'.$source->feedback8.'<br></td></tr>
	<tr><td>'.$source->feedback9.'<br></td></tr>
	<tr><td>'.$source->feedback10.'<br></td></tr>
	<tr><td>('.$tlastfeedback.' 분전)</td></tr>
	</table>';

	//if($source->id!=NULL)$encryption_id=$source->url;
	$nstroke=(int)($value['nstroke']/2);
	$timeused=round((($value['tlast']-$value['tfirst'])/60),0);
	$tmodified=round((time()-$value['timemodified'])/60,0);

	$myreview='';
	if($USER->id==$reviewer && $tmodified<20)$myreview='*';
	if($tmodified<120)$tmodified=$tmodified.'분'.$myreview;
	else $tmodified=round($tmodified/60,0).'시간'.$myreview;
		
	$status=$value['status'];
	$contentsid=$value['contentsid'];
	$cmid=$value['cmid'];

	$stepexist=$DB->get_record_sql("SELECT * FROM  mdl_abessi_cognitivesteps WHERE contentsid='$contentsid'  AND contentstype='$contentstype'  ");
	$hintexist=$DB->get_record_sql("SELECT * FROM  mdl_abessi_questions WHERE contentsid='$contentsid'  AND contentstype='$contentstype'  ");

	$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608438281001.png" width=15>';
	if($value['sent1']==1 && $value['sent2']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608443019001.png" width=15>';  // 모두 발송됨
	elseif($value['sent1']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441128001.png" width=15>';// 해석발송
	elseif($value['sent2']==1)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441153001.png" width=15>';// 풀이발송
	elseif($hintexist->id!=NULL && $stepexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441373001.png" width=15>'; //모두존재
	elseif($hintexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441298001.png" width=15>'; //해석존재
	elseif($stepexist->id!=NULL)$contentsready='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1608441336001.png" width=15>'; //풀이존재

	$checkstatus='';
 	$engagement3 = $DB->get_record_sql("SELECT speed,todayscore, tlaststroke FROM  mdl_abessi_indicators WHERE userid='$userid'   AND timecreated > '$timestart'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
	
 	$teng3=time()-$engagement3->tlaststroke;  
 	if($teng3<180)$teng3=$teng3.'초';
	else $teng3=(INT)($teng3/60).'분';
 	$ratio1=$engagement3->todayscore; 
 	if($teng3 >720)$teng3='활동이탈';
	// 현재 페이지 포착 
 
	if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
	elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
	elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
	elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
	elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
	elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
	else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
	if(($ratio1==0 && $Qnum2==0) || $engagement3->todayscore==NULL) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';

	// 현재 페이지 포착 

	if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
	elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
	elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
	elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
	elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
	elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
	else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
	if($ratio1==0 && $Qnum2==0) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';

	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$studentname=$username->firstname.$username->lastname;
	if($value['contentstype']==2)
		{
		$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
    			$questionimg = $imageTag->getAttribute('src');
			$questionimg = str_replace(' ', '%20', $questionimg); 
			if(strpos($questionimg, 'MATRIX')!= false || strpos($questionimg, 'HintIMG')!= false)break;
			}
		$questiontext=$questionimg; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
		$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
		}
	else
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
		$questiontext=$imgSrc; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
		$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$userid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
		}
	if($value['userid']==$studentid)
		{
		$myRecording.='<tr><td></td><td></td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank"><h6>'.$studentname.'</h6></a></td><td>|</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank"><div class="tooltip3"><h6>발표하기</h6><span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td></td></tr></table></span></div></a></td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank"><h6>재생</h6></a></td><td></td></tr> ';  
 		}
	elseif($value['present']==1)
		{
		$readyforRecording.='<tr><td>'.$contentslink.'</td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$teng3.'<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank">재생</a></td><td></td></tr> ';  
 		}
	elseif($value['present']==2)
		{
		$finishedRecording.='<tr><td>'.$contentslink.'</td><td>| '.$nfeedback.' </td><td>| '.$nfeedback.' </td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
				<td>'.$imgtoday.'</td><td>'.$nstroke.'획 </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank"><div class="tooltip3">'.$contentsready.'&nbsp;&nbsp;'.$teng3.'<span class="tooltiptext3"><table align=center><tr><td><img src="'.$questiontext.'" width=400></td></tr><tr><td><hr></td></tr><tr><td>'.$feedbacktext.'</td></tr></table></span></div></a></td><td> | '.$tmodified.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=bessi'.$encryption_id.'&srcid='.$encryption_id.'" target="_blank">재생</a></td><td></td></tr> ';  
 		}

	}
echo '
 
<table align=center valign=top width=100%>  <thead>
<tr><th scope="col" style="width: 5%;"></th>  <th scope="col" style="width: 28%;">발표준비 중 화이트보드</th>  <th scope="col" style="width: 5%;"></th><th scope="col" style="width: 28%;">발표완료 된 화이트보드</th><th scope="col" style="width: 5%;"></th>  <th scope="col" style="width: 28%;">나의 최근 발표</th></tr>
<tr><td><hr></td>  <td><hr></td>  <td><hr></td>  <td><hr></td>  <td><hr></td>  <td><hr></td></tr>
<tr ><td  style="vertical-align: top;"></td><td  style="vertical-align: top;"><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><tr><td><img src="http://mathking.kr/Contents/IMAGES/thinkAloud.gif" width=200></td><td></td><td align=left style="font-size:20;"><br></td></tr></table>
<hr><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$readyforRecording.'<tr><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th><th><hr></th></tr></table>
</td><td  style="vertical-align: top;"></td>
<td  style="vertical-align: top; "><img src="http://mathking.kr/Contents/IMAGES/mic.gif" width=200><hr> <table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$finishedRecording.'</table></td> <td  style="vertical-align: top;"></td>
<td  style="vertical-align: top;"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617714626001.png" width=200><hr> <table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$myRecording.'</table></td></tr></tbody></table>  
 '; 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
echo '</div>
<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
</p></div><div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"><p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>
<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
</div></div></div></div>
 


<script>
function ChangeCheckBox(Eventid,Userid,Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
    	             "eventid":Eventid,
     	           "checkimsi":checkimsi,
    	           },
  	      success: function (data){  
    	    }
	    });
	}
function askstudent(Eventid,Studentid,Teacherid,Questionid)
	{
    	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"studentid":Studentid,
		"teacherid":Teacherid,
		"contentsid":Questionid,       	   
		      },
	 	success:function(data){
		}
	})
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
 
echo '
<style>
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
  width: 600px;
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
 

.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
 
 
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 800px;
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
include("quicksidebar.php");
?>