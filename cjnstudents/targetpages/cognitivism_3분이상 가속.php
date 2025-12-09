<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;

$conn = new mysqli($servername, $username, $password, $dbname);

// 퀴즈 분석 및 성찰적 피드백 환경
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
include("navbar.php");
//$studentid=required_param('id', PARAM_INT); 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$attemptid=required_param('attemptid', PARAM_INT); 
$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ");
$uniqueid=$attemptinfo->uniqueid;
 
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid WHERE mdl_question_attempts.questionusageid='$uniqueid'
 AND mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright'  ");
$Qnum1=count($recentquestions);

$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempts.questionusageid='$uniqueid'   AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial')  ");
 
$Qnum2=count($recentquestions);
$Qnum2=$Qnum1+$Qnum2;
$ratio1= round($Qnum1/($Qnum2-0.0001)*100,1);  // 정답률
$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$attemptid'   ");
$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
$timelimit =$quizattempts->timelimit/60;  // 시간활용

$quizname='<br><table align=center><tr><th>'.date("m/d | H:i",$quizattempts->timestart).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$quizattempts->id.' " target="_blank">'.$quizattempts->name.'</a>&nbsp;('.$quizattempts->attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$quizattempts->state.'...'.date("H:i",$quizattempts->timefinish).'</th></tr></table><br><br>';
 
$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE    (state='gaveup' OR state='gradedwrong' OR state ='gradedpartial' OR state ='gradedright' )   AND mdl_question_attempts.questionusageid='$quizattempts->uniqueid'  ");
$maxtime=0;
$result1 = json_decode(json_encode($questionattempts), True); 
$ntry=0;
$ncon=0;
$timeforsuccess=0;
$marks=$quizname.'<table width=100% align=center><tr><th width=35%><hr></th><th width=35%><hr></th> <th width=30%><hr></th></tr>';
unset($value);
foreach( $result1 as $value)
	{
$state=NULL;
$questionid=$value['questionid']; 
$questiontext=$value['questiontext'];
$ncon++;
//Create a new DOMDocument object.
$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); 	$extractedImages = array();
$nimg=0;
foreach($imageTags as $imageTag)
	{
	$nimg++;
    	$questionimg = $imageTag->getAttribute('src');
	$questionimg = str_replace(' ', '%20', $questionimg); 
	 
//	$questionimg=str_replace("%2F", "/", urlencode($questionimg));
 
	if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
	}

$htmlDom2 = new DOMDocument; @$htmlDom2->loadHTML($value['generalfeedback']); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages2 = array();
$nimg=0;
foreach($imageTags2 as $imageTag2)
	{
	$nimg++;
    	$solutionimg = $imageTag2->getAttribute('src');
	$solutionimg = str_replace(' ', '%20', $solutionimg); 
	if(strpos($solutionimg, 'MATRIX/MATH')!= false && strpos($solutionimg, 'hintimages') == false)break;
	}
	$qcomment=$value['comment'];
	if($value['state']==='gradedwrong')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882870001.png" width=30>';
	if($value['state']==='gradedpartial')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882934001.png" width=30>';
	if($value['state']==='gaveup')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882809001.png" width=30>';
 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2'   ORDER BY id DESC LIMIT 1 ");
	$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
	if($handwriting->teacher_check==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
	if($handwriting->teacher_check==2)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
	$encryption_id=$handwriting->wboardid;
	$nstroke=(int)($handwriting->nstroke/2);
	$ave_stroke=round($nstroke/(($handwriting->tlast-$handwriting->tfirst)/60),1);
	$timeused=round((($handwriting->tlast-$handwriting->tfirst)/60),0);	 
	$tmodified=round((time()-$handwriting->timemodified)/60,0);
	$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 
 
	if($handwriting->contentstitle==='realtime')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605616024001.png" width="15"> 시도완료'; 
	if($handwriting->status!=='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204763001.png" width="15"> 노트작성';
	if($handwriting->status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603251593001.png" width="15"><span style="color: rgb(233, 33, 33);"> 질문발송</span>';
	if($handwriting->status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
	if($handwriting->status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> 답변수신</a></span>';  
	if($handwriting->status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이수신</u></a></span>';   
	if($handwriting->status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이질문</u></a></span>';   
	if($handwriting->status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이답변</u></a></span>';   
	$wboardlist= $imgstatus.'&nbsp;&nbsp;'.$contentslink.' &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank">'.date("m월d일 | H:i",$value['timemodified']).' &nbsp;&nbsp;총'.$nstroke.'획 &nbsp; '.$ave_stroke.'획/분 '.$fixhistory;

	if($value['state']==='gradedright') // 풀이노트
	       {         
	$solutionnote='Q7MQFA'.$handwriting->contentsid.'0tsDoHfRT_user'.$handwriting->userid;   
	//$solutionnote='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$userid.'_'.date("Y_m_d", $handwriting->timemodified);
	// 화이트보드 DB 정보 
	$recenttime=time()-43200;
	$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '%$solutionnote%' ORDER BY id DESC LIMIT 1 "); 
	$solutionnote=$note->wboardid;
	$pen_nameb='penb'.$ncon; 
	$$pen_nameb= "SELECT min(timecreated) AS timecreated FROM boarddb where encryption_id LIKE '$solutionnote' AND  shape_data  LIKE '%pencil%'  ORDER BY id  ";
	$resultb='resultb'.$ncon;
	$$resultb =mysqli_query($conn, $$pen_nameb);  
	$rowb='rowb'.$ncon; 
	$$rowb= mysqli_fetch_assoc($$resultb);
	$tfirst2=$$rowb['timecreated']; // 마지막 필기 후 경과시간
	 
	$pen_name='pen'.$ncon;
	$timediff=$tfirst2+3600;
	$$pen_name= "SELECT * FROM boarddb where encryption_id LIKE '$solutionnote' AND timecreated < '$timediff'  ORDER BY id  DESC  LIMIT 1  ";
	$result='result'.$ncon; 
	$$result =mysqli_query($conn, $$pen_name);  
	$row='row'.$ncon; 
	$$row= mysqli_fetch_assoc($$result);
	$tlast2=$$row['timecreated']; // 마지막 필기 후 경과시간
	$nstroke2=(INT)($$row['generate_id']/2); // 총 필기량
 
	$minutes=(INT)(($tlast2-$tfirst2)/60);
	$seconds=$tlast2-$tfirst2-$minutes*60;	 
	$timeforsuccess=$timeforsuccess+($tlast2-$tfirst2)/60;
	$tmodified2=round((time()-$handwriting->timemodified)/60,0);
	$timeused2=$tlast2-$tfirst2;
	 
	if($timeused2>180 && $timeused2 < 600)
		{
		$timecreated=time();
		$encryption_id2=$solutionnote;
		include("createdb_improve.php");
		$DB->execute("UPDATE {abessi_messages} SET  status='accelerate', timemodified='$timecreated' WHERE wboardid='$encryption_id2' ");  
 		}

	$speed=(INT)($nstroke2/$timeused2);
	if($speed==1000)$speed='##';
	
	if($tfirst2!=NULL && $tlast2!=NULL)$DB->execute("UPDATE {abessi_messages} SET nstroke='$nstroke2', tlast='$tlast2', tfirst='$tfirst2' WHERE wboardid='$solutionnote' ");  
	$repeat='';
	//if($timeused2>=4 && $nstroke2<50)$repeat='능숙도가 부족합니다. 재시도 해주세요.'; 
 	$realtimewb.='<tr><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
	<td></td><td>'.$nstroke2.'획 | '.$speed.'획/분 | </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$minutes.'분 '.$seconds.'초 사용<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questionimg.'" width=500><hr><img src="'.$solutionimg.'" width=500></td></tr></table></span></div></a></td><td> | '.$tmodified2.'분</td><td>'.$repeat.'</td>  </tr> ';
	       }
	else	// 평가준비, 서술평가
	       {
                     $mathcompetency=NULL;
	       if($value['comment']!=NULL)
		{
		$mathcompetency=str_replace('.php/','.php?',$value['comment']);
		$pattern = '@(http(s)?://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
		$mathcompetency = preg_replace($pattern, '<a href="http$2://$3" target="_blank"><img src=http://mathking.kr/Contents/IMAGES/external-link.png width=15></a>', $mathcompetency);	
		}
	// 평가정보 가져오기
	$assess= $DB->get_record_sql("SELECT * FROM mdl_abessi_cognitiveassessment WHERE wboardid='$encryption_id'  ORDER BY id  DESC  LIMIT 1"); // 과목정보 가져오기
 
	if($assess->graded==1)  //<hr style="border: double 3px red;">
 		{
		$text_assess='  # 서술형 평가결과 : OO 점<hr align="center" style="border: solid 2px red; ">';
		if(isset($assess->step1))$text_assess.='감점요인 : '.$assess->step1.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step2))$text_assess.='감점요인 : '.$assess->step2.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step3))$text_assess.='감점요인 : '.$assess->step3.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step4))$text_assess.='감점요인 : '.$assess->step4.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step5))$text_assess.='감점요인 : '.$assess->step5.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step6))$text_assess.='감점요인 : '.$assess->step6.'<hr align="center" style="border: solid 1px red; ">';
		if(isset($assess->step7))$text_assess.='감점요인 : '.$assess->step7.'<hr align="center" style="border: solid 1px red; ">';
		}

	$feedback= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2' ORDER BY id DESC LIMIT 1"); // 퀴즈 대화정보

	$color1='#F91408';$color2='#F91408';$color3='#F91408';$color4='#F91408';$color5='#F91408';$color6='#F91408';$color7='#F91408';$color8='#F91408';$color9='#F91408';$color10='#F91408';

	if($feedback->feedback2!==NULL)$color1='#0572f7';if($feedback->feedback3!==NULL)$color2='#0572f7';if($feedback->feedback4!==NULL)$color3='#0572f7';if($feedback->feedback5!==NULL)$color4='#0572f7';
	if($feedback->feedback6!==NULL)$color5='#0572f7';if($feedback->feedback7!==NULL)$color6='#0572f7';if($feedback->feedback8!==NULL)$color7='#0572f7';if($feedback->feedback9!==NULL)$color8='#0572f7';if($feedback->feedback10!==NULL)$color9='#0572f7'; 

	$dialogue='<table align=left>
	<tr><td><h6><span style="color:'.$color1.'">'.$feedback->feedback1.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color2.'">'.$feedback->feedback2.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color3.'">'.$feedback->feedback3.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color4.'">'.$feedback->feedback4.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color5.'">'.$feedback->feedback5.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color6.'">'.$feedback->feedback6.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color7.'">'.$feedback->feedback7.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color8.'">'.$feedback->feedback8.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color9.'">'.$feedback->feedback9.' </span></h6></td></tr>
	<tr><td><h6><span style="color:'.$color10.'">'.$feedback->feedback10.' </span></h6></td></tr>
	<tr><td><h6><span style="color:black">'.$text_assess.' </span></h6></td></tr>
	</table>';
 	$marks.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr><tr><td align=center></td><td align=center>
               '.$reason.'  | '.$wboardlist.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$questionid.'" target="_blank" >&nbsp;&nbsp;<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=20></a></td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
	}
$nattempts=$nattempts-$ngaveup;
}
/*
// 풀이방법 개선 문항 출제
$encryption_id2=$wboardid;
var_dump($encryption_id2);
$timecreated=time();
if($maxtime>60 && $maxtime < 600)
	{
	include("createdb_improve.php");
	$DB->execute("UPDATE {abessi_messages} SET  status='accelerate', timemodified='$timecreated' WHERE wboardid='$encryption_id2' ");  
 	}
*/
$propertimeusage=(INT)($timeforsuccess/$timelimit*100);
echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$thisusername.'<br><p align=left>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;최근 '.$period.'일 동안 서술평가 응시문항 총 '.$nattempts.'문제</p><table width=100% align=center><tr><th width=35%><hr></th><th width=35%><hr></th> <th width=30%><hr></th></tr>'.$marks.'</table>
<br><br><table width=95%><tr><th width="48%">학습루브릭</th><th width="2%"></th><th width="50%">풀이노트</th></tr>
<tr><td valign="top"><hr> </td><td ></td><td valign="top"> <hr> </td></tr>		   
<tr><td valign="top"><table>'.$feedbacklog1.'</table></td><td ></td><td valign="top"><table>'.$realtimewb.'</table><hr> 정답을 위해 사용된 총시간 : '.round($timeforsuccess,1).' 분 ('.$propertimeusage.'%)<hr>점수 '.$quizgrade.'점 | 정답률 '.$ratio1.'% <hr><button onClick="window.location.reload();">필기정보 업데이트</button></td></tr></table>';

echo '<br><table width="100%"><tr><td>난이도</td><td><img  src="https://play-lh.googleusercontent.com/PkNdm5zWBQoe7JVYWu_b3fyw8SxkeeF8EkZiGKc71LOAj1-BNaWREVkUf_Asqfq4_Co" width=50 ></td><td>상태</td><td><img   src="https://i.gifer.com/JFi.gif" width=200 ></td><td><img   src="https://i.pinimg.com/originals/04/8c/8e/048c8e251c1a6a1a9f8b35f68dcd8b52.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://s.wsj.net/public/resources/images/OG-DG972_201910_M_20191009103200.gif"  width=200 ></td><td><img   src="https://cdn5.vectorstock.com/i/1000x1000/13/59/airplane-is-landing-or-taking-off-on-runway-vector-25911359.jpg" width=200  ></td></tr></table>
<hr><p align=center>KTM의 경쟁상대는 대한항공</p><hr>';
	

$conn->close(); 
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
 

.tooltip3:hover .tooltiptext4 {
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
  left:10%;
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





.tooltip4:hover .tooltiptext4 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip4 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip4 .tooltiptext4 {
    
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
  top:40;
  left:15%;
  position: fixed;
z-index: 1;
 
} 
.tooltip4 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip4:hover .tooltiptext4 {
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

//include("quicksidebar.php");
?>