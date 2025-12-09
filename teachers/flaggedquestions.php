<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$tb=required_param('tb', PARAM_INT);
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
//$names=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol2%'");
 
 	$telapsed=time()-86400;
	$Qflag = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid,  mdl_question_attempt_steps.userid AS userid, mdl_question_attempts.checkflag AS checkflag, mdl_question_attempts.timemodified AS timemodified FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question.name LIKE '$contains'  AND flagged ='1'  AND mdl_question_attempts.timemodified > '$telapsed' ");
	  
	$result = json_decode(json_encode($Qflag), True);
	$nflag=count($result);
	$flaglist=NULL;
	unset($value);
	foreach($result as $value)
		{
		$questionid=$value['questionid'];
		$userid=$value['userid'];
		$name=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$firstname=$name->firstname;
		$lastname=$name->lastname;
		$tpassed=round((time()-$value['timemodified'])/60,0);

		if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
		if(strpos($value['questiontext'], '/MY')!= false&&strpos($value['questiontext'], 'slowhw')!= false)$value['questiontext']='<p> MY A step </p>';
		if(strpos($value['questiontext'], 'shutterstock')!= false)
			{
			$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '{ifminassistant}'));   
			$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}
	 
		$message=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$userid'   AND contentsid='$questionid'  ORDER BY id DESC LIMIT 1  ");
		if($message->timemodified > $telapsed && $message->turn==0 )
			{
			if($message->status==='review' || $message->status==='complete')$flaglist.='';
			if($message->status==='begin')$flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/pinkflag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			if($message->status==='reply')$flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/greenflag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
			$nflag=$nflag-1;
			}
		else $flaglist.='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/Moodle/flag.png" width=15><span class="tooltiptext2"><h3>'.$tpassed.'분전</h3>'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</span></div></a>&nbsp;';
		}
	if($nflag==0)$flaglist.='';
	elseif($count==0 && $nflag!=NULL) $Qlist.='<tr  align="left"><td>질의응답</td><td>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a></td><td align="left">'.$flaglist;	 
	if($count>0 &&  $nflag!=NULL)  $Qlist.=$flaglist;
 
	if($count==0 && $nflag==0 && $nleft!=0) $Qlist.='<tr  align="left"><td>질의응답</td><td>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=86400" target="_blank">'.$lastname.'</a></td><td align="left">'.$marks.'('.$nleft.')';	
	else $Qlist.=$marks.'</td></tr>';
 
 
 

echo ' 	 
	<table style="width: 100%;"><tr><th width=5%></th><th width=5%></th><th width=85%></th></tr>'.$Qlist.'</table><hr>	

 	 

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