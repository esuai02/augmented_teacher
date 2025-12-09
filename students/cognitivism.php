 

<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;
 
$studentid= $_GET["id"];
$attemptid= $_GET["attemptid"];

$timecreated=time(); 
$hoursago=$timecreated-14400;

$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 
 
//$studentid=required_param('id', PARAM_INT); 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ");
$uniqueid=$attemptinfo->uniqueid;
$ratio1=$attemptinfo->ratio;
$comment=$attemptinfo->comment;
$qnum=substr_count($attemptinfo->layout,',')+1-substr_count($attemptinfo->layout,',0'); 
if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
if($ratio1==0 && $Qnum2==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
 
$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$attemptid'    ");
$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
$timelimit =$quizattempts->timelimit;  // 시간활용
$instructiontext='🧑🏻 '.$quizattempts->instruction.' 🧑🏻 '.$attemptinfo->comment;
if(strpos($quizattempts->name, 'ifmin')!== false)$quiztitle=substr($quizattempts->name, 0, strpos($quizattempts->name, '{')); 
else $quiztitle=$quizattempts->name;
$quizresult='<table align=center width=100%><tr><th align=center><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$quizattempts->id.' " target="_blank">'.$quiztitle.'</a> <br>'.date("m/d | H:i",$quizattempts->timestart).' | &nbsp;('.$quizattempts->attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$quizattempts->state.'...'.date("H:i",$quizattempts->timefinish).' <img src='.$imgtoday.' width=25></th></tr></table>'; 
$commentonresult='<table width=100%><tr><th align=left>'.$instructiontext.'</th></tr></table>';

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE attemptid LIKE '$attemptid' AND status NOT LIKE 'boost' AND active='1' ");
$result2 = json_decode(json_encode($handwriting), True);
unset($value);
$righttimeused=0; 
foreach(array_reverse($result2) as $value) 
	{
	$wboardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
	$contentstitle=$value['contentstitle'];
	$instruction=$value['instruction'];
	$nstroke=$value['nstroke'];
	$ncommit=$value['feedback'];
	if($ncommit!=0)$ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';
	$timeused_tmp=$value['usedtime'];
	$usedtime=round($timeused_tmp/60,1).'분';
	$tinterval=round(($tprev-$value['timemodified'])/60,0).'분';
	$tprev=$value['timemodified'];
	$status=$value['status'];
	if($tinterval<0)$tinterval=round(($timecreated-$value['timemodified'])/60,0).'분';

	$timestamp=$timecreated-$value['timemodified'];
	
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';

	$checkout='';
	if($value['student_check']==1)$checkstatus='Checked';
	else $checkstatus='';

	if($role!=='student')$checkout='<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
		
	if($value['status']==='reflect')
		{  
		if($value['student_check']==1)$submitted.='<tr><td width=8% style="background-color:#FFA389;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번</td><td width=10% > <img src="'.$imgSrc.'" width=400></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editcontents.php?cntid='.$contentsid.'&cnttype=2"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width=20></a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></td><td> <span style="font-size:16px;">'.$qtext->reflections1.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		else $papertest.='<tr><td width=8%  style="background-color:#1080E9;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번</td><td width=10% align=center> <b><a style="color:#1080E9;text-decoration:none;"href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$contentsid.'"target="_blank">'.$contentstitle.'테스트 결과 성찰</a></b> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	else
		{
		$totaltime=$totaltime+$timeused_tmp;
		$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");
		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		$result='';
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			} 

		if($value['result']==='right')
			{
			$result='<b style="color:#009c10;">정답</b>';
			$righttimeused=$righttimeused+$timeused_tmp;
			}
		elseif($value['result']==='wrong')$result='오답';
		elseif($value['status']==='attempt')$result='진행중';
		else $result='오답노트';
		$statustext='<br><table align=center><tr><td>'.$status.'</td></tr></table>';
		$reflections=$qtext->reflections1;
		if($value['student_check']==1)$submitted.='<tr><td width=8% style="background-color:#FFA389;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% > <img src="'.$imgSrc.'" width=400></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editcontents.php?cntid='.$contentsid.'&cnttype=2"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width=20></a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></td><td> <span style="font-size:16px;">'.$qtext->reflections1.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		elseif($status==='complete' || $status==='review') $papertest.='<tr><td width=8%  style="background-color:#45E670;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		elseif($status==='begin' || $status==='exam') $papertest.='<tr><td width=8%  style="background-color:#EE9D9D;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		elseif($status==='attempt' && $value['nretry']==0)$papertest.='<tr><td width=8%  style="background-color:#E6F4E2;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		elseif($status==='attempt' && $value['nretry']>0)$papertest.='<tr><td width=8%  style="background-color:#92EA78;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		elseif($status==='flag')$papertest.='<tr><td width=8%  style="background-color:#FFD4D4;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td><img src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$reflections.'<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		
		else $papertest.='<tr><td width=8% style="background-color:#4E4CD0;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번'.$statustext.'</td><td width=10% > <img src="'.$imgSrc.'" width=400></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editcontents.php?cntid='.$contentsid.'&cnttype=2"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png" width=20></a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></td><td> <span style="font-size:16px;">'.$qtext->reflections1.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	}

	$righttimeratio=$righttimeused/$totaltime*100;
 
	echo ' <script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
	  
	<script type="text/x-mathjax-config">
	MathJax.Hub.Config({
	  tex2jax: {
		inlineMath:[ ["$","$"], ["\\[","\\]"] ],
	   // displayMath: [ ["$","$"], ["\\[","\\]"] ]
	  }
	});
	</script>
	<script type="text/javascript" async
	  src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML">
	</script>
	
	<table align=center width=90%><tr><td valign=top><div class="table-wrapper"><table width=100%><thead><tr><th><a style="text-decoration:none;color:#1956FF;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$stdname.'</a></th><th style="color:#1956FF;font-size:20px;"> '.$quizresult.'</th><th width=5%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/reflective_feedback.php?userid='.$studentid.'&attemptid='.$attemptid.'"target="_blank">체크리스트</a></th><th align=left> '.$commentonresult.'&nbsp;🧑🏻 정답에 사용된 시간 '.round($righttimeused/60,1).'분 ('.round($righttimeratio,0).'%) </th></tr></thead><tr><td><hr></td><td><hr></td><td><hr></td></tr>'.$papertest.'<hr>'.$submitted.'</table></div></td></tr></table>';
	  

echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

	<script> 
	window.onload = function() {
		window.scrollTo(0, document.body.scrollHeight);
	  };
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
 
	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<style>
	.table-wrapper {
		position: relative;
		height: 100% /* 테이블이 표시될 영역의 높이를 지정하세요 */
		overflow: auto;
	  }
	  
	  .table-wrapper thead {
		position: sticky;
		top: 0;
		background-color: #BCD5FF; /* 첫 번째 행의 배경색을 지정하세요 */
		z-index: 1;
	  } 

	
.tooltip3:hover .tooltiptext1 {
	visibility: visible;
  }
  a:hover { color: green; text-decoration: underline;}
  
  .tooltip3 {
   position: relative;
	display: inline;
	border-bottom: 0px solid black;
  font-size: 14px;
  }
  
  .tooltip3 .tooltiptext3 {
	  
	visibility: hidden;
	width: 40%;
   
	background-color: #ffffff;
	color: #e1e2e6;
	text-align: center;
	font-size: 14px;
	border-radius: 10px;
	border-style: solid;
	border-color: #0aa1bf;
	padding: 20px 1;
  
	/* Position the tooltip */
	top:50;
	right:5%;
	position: fixed;
  z-index: 1;
   
  } 
  .tooltip3 img {
	max-width: 600px;
	max-height: 1200px;
  }
  .tooltip3:hover .tooltiptext3 {
	visibility: visible;
  }
	</style>
';
?>
