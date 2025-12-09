<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='66' "); 
$tsymbol2=$teacher2->symbol;
 
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol2%'");
$userlist= json_decode(json_encode($mystudents), True);

$time4=time()-28800;
unset($user);
foreach($userlist as $user)
	{
	$userid=$user['id'];
	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' ORDER BY id DESC LIMIT 1 ");
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0);

	$attend=$DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog where userid='$userid' AND timecreated<'$time4' AND event='attendance' ORDER BY id DESC LIMIT 1 ");
	$atnd_result=$attend->text;

	if($nday==0)$nday=7;

	$trecent2=time()-16000000;  // 1year ago
	$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND timecreated>'$trecent2'  AND userid='$userid'  ");
	$mresult = json_decode(json_encode($missionlist), True);
	$recentmission='<table>';
	unset($value);
	foreach($mresult as $value)
		{
		$mid=$value['id'];
		$subject=$value['subject'];
		$mtname=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$subject' ");
		$subjectname=$mtname->name;
		$mtid=$mtname->mtid;
     
		$text=$value['text'];
		$deadline= $value['deadline']; 

		if($subject!=NULL)
			{
			$recentmission.= '<tr><td><div class="form-check"><label class="form-check-label">
			<input type="checkbox"  onclick="changecheckbox(1,'.$userid.','.$mid.', this.checked)"/>
			<span class="form-check-sign"></span></label></div></td>
			<td style="font-size:18pt"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?id='.$userid.'&mtid='.$mtid.'&cid='.$subject.'&tb=90">'.$subjectname.'</td><td>'.$deadline.'</td></tr>';
			}
		else
			{
			$recentmission.= '<tr><td><div class="form-check"><label class="form-check-label">
			<input type="checkbox"  onclick="changecheckbox(1,'.$userid.','.$mid.', this.checked)"/>
			<span class="form-check-sign"></span></label></div></td><td>'.$text.'</td><td>'.$deadline.'</td></tr>';
			}
		} 
	$recentmission.='</table>';
	for($n1=0;$n1<8;$n1++)
		{ 
		$var='start'.$n1;
		$var2=$schedule->$var;
		$var3='duration'.$n1;
		$var4=$schedule->$var3;
		$tbegin=date("H:i",strtotime($var2));
		$time    = explode(':', $tbegin);
		$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
 		if($var2!=NULL && $var4!=NULL )
			{	 
			$n2=(int)(($minutes-530)/30);	
 		 	
			$date=date(" h:i A");
			$date2=date("H:i",strtotime($date));
			$time2    = explode(':', $date2);
			$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
			$npresent=(int)(($minutes2-530)/30);	

			if($minutes<500)$n2=0;
			if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5||$npresent==$n2+6) && $nday==$n1)
				{	
				$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
				$lastaction=$lastaction->maxtc;
				$lastaccess=time()-$lastaction;

			 
				if($lastaccess>36000)
					{
					$tinput=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_abessi_today where userid='$userid' "); 
					$inputtime=time()-$tinput->maxtc;    
					if( $npresent>=$n2 && $inputtime > 36000 ) $today .='&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13>'.$user['lastname'].$atnd_result.'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				else 
					{
					$tinput=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_abessi_today where userid='$userid' "); 
					$inputtime=time()-$tinput->maxtc;
					if( $npresent>=$n2 && $inputtime > 36000 ) $today .='&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13>'.$user['lastname'].$atnd_result.'</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
 					}
				}
		 
			} 
		}

	// 이곳에 몰입이탈 알고리즘 배치 (연속 RED, 속도 저하, 화이트보드 포함 현재 시간과 5분 이상 연장된 경우, $userid 로 검색
	include("detecteng.php");

	$checktoday = $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");
	$goalid=$checktoday->id; 
	$timestart2=time()-300; //(5분간 활동없는 경우)

	if($timestart2 >$tlast  && $checktoday->submit==1)  // 몰입이탈 표시
	$confirm.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$firstname.$lastname.'</a>&nbsp;<input type="checkbox" name="checkAccount"  onClick="ChangeCheckBox2(4,\''.$userid.'\',\''.$goalid.'\', this.checked)"/>&nbsp;&nbsp;&nbsp;';

	$timestart3 =time()-30000;
	$tspent=time() - $checktoday->timemodified;
	//$inspect=$DB->get_record_sql("SELECT data AS time FROM mdl_user_info_data where userid='$userid' AND fieldid='56' "); 
	$tinspect=1800;  //$inspect->time*60;
	if($checktoday->timemodified>$timestart3 && $tspent > $tinspect && $checktoday->submit==0 )  // 목표설정 후 30분후에 상황점검
		{
		$detectedusers.=$firstname.$lastname.'&nbsp;<input type="checkbox" name="checkAccount"  onClick="ChangeCheckBox2(5,\''.$userid.'\',\''.$goalid.'\', this.checked)"/>&nbsp;&nbsp;&nbsp;';
		}

	//$inspect=$DB->get_record_sql("SELECT data AS time FROM mdl_user_info_data where userid='$userid' AND fieldid='56' "); 
	//$tinspect=$inspect->time*60;

	$timestart=time()- 64800;
	$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
	WHERE ( mdl_quiz_attempts.timestart > '$timestart' OR mdl_quiz_attempts.timefinish > '$timestart' ) AND mdl_quiz_attempts.userid='$userid' AND mdl_quiz_attempts.comment IS NULL ORDER BY mdl_quiz_attempts.timestart");      
	$quizresult = json_decode(json_encode($quizattempts), True);
	$nattempt=count($quizattempts);
	$quizlist1=NULL;
	$quizlist2=NULL;
	unset($value); 	
	foreach(array_reverse($quizresult) as $value)
		{
		$comment='';
		$qnum=substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');
		$tfinish=time()-3600;

		$quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);	 
		if($quizgrade>89.99)
			{
			$imgstatus='&nbsp;<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
			}
		elseif($quizgrade>69.99)
			{
			$imgstatus='&nbsp;<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
			}
		else $imgstatus='&nbsp;<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">'; 
		if($value['state']===inprogress)$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>';		
		if($qnum>=10)
			{
			$inspect='<img src=https://mathking.kr/IMG/HintIMG/BESSI1591095175001.png height=17>'.$imgstatus;
			if($value['state']===inprogress)$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>';
			$comment= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank">'.$inspect.'</a>';
			$quizlist1.='<div class="tooltip2">'.$comment.'<span class="tooltiptext2">'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">'.substr($value['name'],0,40).'</a>('.$qnum.'문항 | '.$value['attempt'].get_string('trial', 'local_augmented_teacher').') 
			<span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$value['state'].'...'.date("H:i",$value['timefinish']).'</span></div>&nbsp;&nbsp;&nbsp;'; 	 
			}
		elseif($value['timefinish']>$tfinish)
			{
			$inspect='결과분석'.$imgstatus;
			if($value['state']===inprogress)$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>';
			$comment= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank">'.$inspect.'</a>';
			$quizlist2.='<div class="tooltip2">'.$comment.'<span class="tooltiptext2">'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">'.substr($value['name'],0,40).'</a>('.$qnum.'문항 | '.$value['attempt'].get_string('trial', 'local_augmented_teacher').') 
			<span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$value['state'].'...'.date("H:i",$value['timefinish']).'</span></div>&nbsp;&nbsp;&nbsp;'; 	 
			}

		} 

	// 결과 출력하기
 	if($nattempt!=0)
		{
			// 서술형 평가 가져오기
		$timeafter=time()-43200;
		$wboards=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid='$userid' AND (feedback='2' OR feedback='1' OR feedback='0') AND timemodified > '$timeafter' ");
		$waitinglist= json_decode(json_encode($wboards), True);
		unset($value2);
		$feedback='';
		foreach( $waitinglist as $value2)
			{	
			$boardid=$value2['wboardid'];
			$questionid=$value2['contentsid'];
			if($value2['feedback']==0)
				{				 
				$feedback.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_feedback.php?id='.$boardid.'" target="_blank" ><img src="https://mathking.kr/IMG/HintIMG/BESSI1595236244001.png" width=30></a> | ';  
				}
			if($value2['feedback']==1)
				{				 
				$feedback.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_feedback.php?id='.$boardid.'" target="_blank" ><img src="https://mathking.kr/IMG/HintIMG/BESSI1594815811001.png" width=30></a> | ';  
				}
			if($value2['feedback']==2)
				{				 
				$feedback.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_feedback.php?id='.$boardid.'" target="_blank" ><img src="https://mathking.kr/IMG/HintIMG/BESSI1594814914001.png" width=30></a> | ';
				}
  			}  
		$mystudent1.='<b><div class="tooltip2"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$firstname.$lastname.'</a><span class="tooltiptext2">'.$recentmission.'</span></div></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$feedback.$quizlist1.$quizlist2.'<hr>';
		}
 	else  $mystudent2.='<b><div class="tooltip2"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$firstname.$lastname.'</a><span class="tooltiptext2">'.$recentmission.'</span></div></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<hr>';
	}

echo ' 	<table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td align="left">지각방지</td><td align="left">'.$tardy.'</td></tr></table>
<hr style="border: dashed 2px sky3blue;">
	<table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td align="left">목표입력</td><td align="left">'.$today.'</td></tr></table>
<hr style="border: dashed 2px skyblue;">
	 <table style="width: 100%;"><tr><th width=90%>'.$mystudent1.'</th></tr></table> 	
 	 <table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td>초기정비</td><td>&nbsp;&nbsp;&nbsp;'.$detectedusers.'</td></tr></table>
<hr style="border: dashed 2px skyblue;">
	 <table style="width: 100%;"><tr><th width=5%></th><th width=95%></th></tr><tr align="left"><td>몰입이탈</td><td align="left">&nbsp;&nbsp;&nbsp;'.$confirm.'</td></tr></table> 
<hr style="border: dashed 2px skyblue;">
	<table style="width: 100%;"><tr> <th width=90%>'.$mystudent2.'</th></tr></table><hr>	

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
	function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
   	             "questionid":Questionid,
   	             "attemptid":Attemptid,
     	           "checkimsi":checkimsi,
    	             "eventid":Eventid,
    	           },
  	      success: function (data){  
    	    }
	    });
	}


	function ChangeCheckBox2(Eventid,Userid, Goalid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
	checkimsi = 1;
	}
	$.ajax({
	url: "../students/check.php",
	type: "POST",
	dataType: "json",
	data : {"userid":Userid,       
	"goalid":Goalid,
	"checkimsi":checkimsi,
	"eventid":Eventid,
	},
	success: function (data){  
	}
	});
	 window.open("https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id="+Userid+"&tb=86400");
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