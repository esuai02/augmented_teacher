<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
 
include("navbar.php");
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';

$tbegin=time()-required_param('tb', PARAM_INT);  
$handwriting=$DB->get_records_sql("SELECT  *  FROM mdl_abessi_teacher WHERE userid='$teacherid' AND timecreated >'$tbegin'   ORDER BY timecreated DESC"); 
$result= json_decode(json_encode($handwriting), True);
$wboardlist1=''; $wboardlist2=''; $wboardlist3=''; $wboardlist4=''; $wboardlist5=''; $wboardlist6=''; $wboardlist7=''; 
 
$wbnum=0;
$ncreate=0;
$nauto=0;
$totalDelay=0;
unset($value);
foreach($result as $value) 
	{
	$wboardid=$value['wboardid'];
	$status=$value['status'];
	 
     	$tcreated=(INT)((time()-$value['timecreated'])/60);
	$message=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid'  ORDER BY id DESC LIMIT 1 ");
	$userid=$message->userid;
	$aistate=$message->aion;
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
	$studentname=$username->firstname.$username->lastname;
 	$tdelay=round(($message->timereviewed-$message->timemodified)/60,0);
	if($tdelay<0)$tdelay='###';
 	
	$resultValue=$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1623817278001.png" height=15> 자세히';
	if($message->star==1)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15> 자세히';
	if($message->star==2)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15> 자세히';
	if($message->star==3)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15> 자세히';
	if($message->star==4)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15> 자세히';
	if($message->star==5)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15> 자세히';

 	if(time()-$message->timemodified<86400)$wboardlist1.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>';   
 	elseif(time()-$message->timemodified<86400*2)$wboardlist2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
	elseif(time()-$message->timemodified<86400*3)$wboardlist3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
	elseif(time()-$message->timemodified<86400*4)$wboardlist4.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
	elseif(time()-$message->timemodified<86400*5)$wboardlist5.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
	elseif(time()-$message->timemodified<86400*6)$wboardlist6.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
	elseif(time()-$message->timemodified<86400*7)$wboardlist7.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank">'.$studentname.'</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank">'.$resultValue.'</a></td><td>상호작용 '.$message->nstep.' </td><td>응답시간 '.$tdelay.'분 </td><td> ('.date("m월 d일 | H시 i분", $value['timecreated']).')</td></tr>'; 
 	 
	if($aistate==1)$nauto=$nauto+1;
	if($tdelay>0 && $tdelay<10800)
		{
		$totalDelay=$totalDelay+$message->timereviewed-$message->timemodified;
		$wbnum++;
		}
	$teacherWb=$DB->get_record_sql("SELECT  *  FROM mdl_abessi_teacher WHERE wboardid LIKE '$wboardid'  ORDER BY id DESC LIMIT 1 ");
	if($teacherWb->type==1)$ncreate=$ncreate+1;
	}
$responsetime=round($totalDelay/60/$wbnum,1);

$DB->execute("UPDATE {abessi_indicators_class} SET timedelayed='$responsetime', nauto='$nauto', ncreate='$ncreate'  WHERE timecreated >'$tbegin' AND teacherid='$teacherid' ORDER BY id DESC LIMIT 1 "); 
 
echo '
<table width=60% ><tr><th>첨삭 결과</th><th></th><th></th><th></th></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
'.$wboardlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist3.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist4.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.
$wboardlist5.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist6.'<tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist7.'</table>';

 
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
	 window.open("https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id="+Userid+"&tb=43200");
	}
function ChangeCheckBox3(Eventid,Userid, Wboardid, Checkvalue)
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
	"wboardid":Wboardid,
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