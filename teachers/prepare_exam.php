<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
 
echo ' 		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 	$timestart=time()- 86400;
	$timestart2=time()- 604800;
	$quizattempts = $DB->get_records_sql("SELECT *,mdl_quiz_attempts.comment AS comment, mdl_quiz.sumgrades AS tgrades, mdl_quiz.course AS courseid FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
	WHERE  mdl_quiz_attempts.timestart > '$timestart' OR mdl_quiz_attempts.timefinish > '$timestart'  OR (mdl_quiz_attempts.timefinish < '$timestart' AND mdl_quiz_attempts.timefinish > '$timestart2' AND state LIKE 'inprogress')  ORDER BY mdl_quiz_attempts.timestart");      
	$quizresult = json_decode(json_encode($quizattempts), True);
	$nattempt=count($quizattempts);
	$quizlist1='<table width=90%><tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr><tr><th>내신테스트<hr></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>';
	$quizlist2='<table width=90%><tr><th></th><th></th><th></th><th></th><th></th></tr>';
	unset($value); 	
	foreach(array_reverse($quizresult) as $value)
		{
		$studentid=$value['userid'];
		$thisstudent=$DB->get_record_sql("SELECT  *  FROM mdl_user WHERE id='$studentid' AND firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol2%'");
		$name=$thisstudent->firstname.$thisstudent->lastname;
		$quizname= $value['name'];  // ord($value['name']);  && strpos($quizname,$quizstring)!=false
		$quizstring= ord('미니 테스트');
		 
		if($thisstudent->id!=NULL && $value['comment']==NULL && $value['courseid']!=239 )  
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
		if(strpos($quizname, '내신')!= false) 
			{
			$inspect='<img src=https://mathking.kr/IMG/HintIMG/BESSI1591095175001.png height=17>'.$imgstatus;
			if($value['state']===inprogress){$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>'; $value['timefinish']='시도중';}
			$comment= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">'.$inspect.'</a>';
			$quizlist1.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank">'.$name.'</a></td><td>'.date("m/d | H:i",$value['timestart']).'</td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">'.substr($value['name'],0,40).'</a></td><td>'.$quizgrade.get_string('points', 'local_augmented_teacher').' </td><td>'.$qnum.'문항</td><td>'.$value['attempt'].' 회 </td><td>'.date("H:i",$value['timefinish']).'</td><td>'.$comment.'</td></tr>';
			}
		elseif($qnum>=10)
			{
			$inspect='<img src=https://mathking.kr/IMG/HintIMG/BESSI1591095175001.png height=17>'.$imgstatus;
			if($value['state']===inprogress){$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>'; $value['timefinish']='시도중';}
			$comment= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">'.$inspect.'</a>';
			$quizlist2.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank">'.$name.'</a></td><td>'.date("m/d | H:i",$value['timestart']).'</td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">'.substr($value['name'],0,40).'</a></td><td>'.$quizgrade.get_string('points', 'local_augmented_teacher').' </td><td>'.$qnum.'문항</td><td>'.$value['attempt'].' 회 </td><td>'.date("H:i",$value['timefinish']).'</td><td>'.$comment.'</td></tr>';
 			}
		elseif($value['timefinish']>$tfinish)
			{
			$inspect='결과분석'.$imgstatus;
			if($value['state']===inprogress)$inspect='<img src=http://mathking.kr/Contents/IMAGES/inprogress.png height=17>';
			$comment= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">'.$inspect.'</a>';
			$quizlist3.=  '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" target="_blank">'.$name.'</a></td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].' " target="_blank">보기</a></td><td>('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);"></td><td>'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span></td><td>'.$comment.'</td><tr>';
	 		}
		}
		} 
echo '<table width=100% align=center><tr><th width=70%>일반퀴즈 ........................ (결과 분석 후 커리큘럼 개선)</th> <th width=30%>인지촉진</th></tr> <tr><td valign="top"><hr> </td><td  valign="top"><hr> </td></tr>
			           <tr><td valign="top">'.$quizlist1.'
<tr><th><br>표준테스트<hr></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>'.$quizlist2.'</table></td><td  valign="top">'.$quizlist3.'</table></td></tr></table>

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