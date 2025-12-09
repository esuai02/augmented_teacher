<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$USER->id','studentfullengagement','$timecreated')");

include("navbar.php");
$userid=$studentid;
$nweek=required_param('nw', PARAM_INT); 
$nweekprev=$nweek-1;
$nweeknext=$nweek+1;
$getperiod=$DB->get_record_sql("SELECT data AS period FROM mdl_user_info_data where userid='$studentid' AND fieldid='67' "); 

if($nweek>0)
	{	 
	$timeafter=time()-86400*7*$nweek;
	$wboard=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student' AND status LIKE 'complete' AND contentstype LIKE '2' AND  turn LIKE '0' AND timemodified > '$timeafter' ORDER BY id DESC ");
	$waitinglist= json_decode(json_encode($wboard), True);
	$ncomplete=count($wboard);
	$count=0;
	$nrand=mt_rand(1, $ncomplete);
	unset($value);
	foreach($waitinglist as $value)
		{	
		$boardid=$value['wboardid'];
		$count++;
		$timemodified=date("m-d h:i A", $value['timemodified']);
	 	$reviewperiod=time()-$value['timereviewed']+43200;   
		$nreviewed=$value['nreview'];	 	
		if($count==$nrand)
			{
			$questionid=$value['contentsid'];
			$complete.= '<div align=center style="border: 0px solid rgb(201, 0, 1); overflow: hidden; margin: 15px auto; max-width: 1200px; max-height: 2800px;">
	    		<iframe src="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'"   style="border: 0px none; margin-left: 0% ;margin-right: 0%; height: 2800px; overflow:hidden;  margin-top: -210px; margin-bottom: 0px; width: 1200px;">
			</iframe></div>'; 
			}
		}
	echo '<div class="row"><div class="col-md-12"><div class="card"><div align="center" class="card-header">내 휴지통에 최근 '.$nweek.'주간 총 '.$ncomplete.' 문항이 추가되었습니다. 학습완료 선택에 대해 성찰해 보세요. &nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw='.$nweekprev.'"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591009001.png" width="20"></a>
	<a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw='.$nweek.'"><img src="https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcTKRLknvRAWU74Lm76pu2-UZU6WB3oTEmmQMVkjPltm0rGHA1w6&usqp=CAU" width="20"></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw='.$nweeknext.'">
	<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1587591105001.png" width="20"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw=-10">오답노트</a></div><div class="card-body">';
	echo $complete.'</div></div></div></div>';
	}
if($nweek==-10)
	{
	$timeafter=time()-86400*7*4;
	$wboard=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student' AND status LIKE 'complete' AND contentstype LIKE '2' AND  turn LIKE '0' AND timemodified > '$timeafter' ORDER BY id DESC ");
	$result2= json_decode(json_encode($wboard), True);
	$complete='<table width=100% align=center><tr><th><h3>최근 오답노트 (4주)</h3> <hr></th> <th><hr></th></tr>';
	unset($value);
	foreach($result2 as $value) 
 		{
 		$questionid=$value['contentsid'];
		$question=$DB->get_record_sql("SELECT questiontext AS text FROM mdl_question WHERE  id LIKE '$questionid'");
		$questiontext=$question->text;
		if(strpos($questiontext, 'ifminassistant')!= false)$questiontext=substr($questiontext, 0, strpos($questiontext, "<p>{ifminassistant}"));  
		if(strpos($questiontext, '/MY')!= false&&strpos($questiontext, 'slowhw')!= false)$questiontext='<p> MY A step </p>';
 		$complete.='<tr><td align=center>'.$questiontext.'<br><table width=100%><tr><th width=70% align=right><font color="red"></font></th><th align=right width=30%>&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=OVc4lRh'.$questionid.'nx4HQkXq'.$studentid.'" target="_blank">오답노트</a>
		</th></tr></table><hr></td></tr>';
		}
	echo $complete.'</tabe>';
	}




 

echo '	 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script><!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don\'t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		$("#datepicker").datetimepicker({
			format: "MM/DD/YYYY",
		});
	</script>
 ';
include("quicksidebar.php");
?>