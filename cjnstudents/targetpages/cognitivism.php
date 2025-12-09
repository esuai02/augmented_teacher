<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;

$conn = new mysqli($servername, $username, $password, $dbname);
$useragent=$_SERVER['HTTP_USER_AGENT'];

// 퀴즈 분석 및 성찰적 피드백 환경
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
include("navbar.php");

 

//$studentid=required_param('id', PARAM_INT); 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$attemptid=required_param('attemptid', PARAM_INT); 
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
 
$indic= $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");

$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$attemptid'   ");
$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
$timelimit =$quizattempts->timelimit/60;  // 시간활용
$instructiontext='🧑🏻 '.$quizattempts->instruction.' 🧑🏻 '.$attemptinfo->comment;
if(strpos($quizattempts->name, 'ifmin')!== false)$quiztitle=substr($quizattempts->name, 0, strpos($quizattempts->name, '{')); 
else $quiztitle=$quizattempts->name;
$quizname='<br><table align=center width=100%><tr><th align=center>'.date("m/d | H:i",$quizattempts->timestart).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$quizattempts->id.' " target="_blank">'.$quiztitle.'</a>&nbsp;('.$quizattempts->attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$quizattempts->state.'...'.date("H:i",$quizattempts->timefinish).' <img src='.$imgtoday.' width=25></th></tr></table><hr style="border: 1px solid grey;"><table width=100% align=center><tr><th>'.$instructiontext.'</th></tr></table>';
 
  
if($role!=='student' && ($attemptinfo->maxgrade!=NULL || $quizgrade>99) ) // 선생님 검토 후 목록 해제
	{
	$DB->execute("UPDATE {quiz_attempts} SET review=0 WHERE id='$attemptid'");    
	}

$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.questionid AS questionid,mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE   state NOT LIKE 'todo'  AND mdl_question_attempts.questionusageid='$quizattempts->uniqueid' ORDER BY mdl_question_attempt_steps.timecreated DESC");
$maxtime=0;
$result1 = json_decode(json_encode($questionattempts), True); 
$ntry=0;
$ncon=0;
$nwrong=0;
$nafter=0;
$timeforsuccess=0;

if($nmobile==1)
{
$marks=$quizname.'<table width=100% align=center><tr><th width=100%><hr style="border: 1px solid grey;"></th></tr>';
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
	 
		//$questionimg=str_replace("%2F", "/", urlencode($questionimg));
 
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
	if($value['state']==='gradedwrong')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651975727.png" width=20>';
	if($value['state']==='gradedpartial')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651976018.png" width=20>';
	if($value['state']==='gaveup')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651975887.png" width=20>';
 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2' AND boardtype NOT LIKE 'duplicate'  ORDER BY id DESC LIMIT 1 ");
	$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
	if($handwriting->teacher_check==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
	if($handwriting->teacher_check==2)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
	$encryption_id=$handwriting->wboardid;
 
	$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 
 
	$solutionnote='Q7MQFA'.$handwriting->contentsid.'0tsDoHfRT_user'.$handwriting->userid;   
	 
	$recenttime=time()-43200;
	$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '%$solutionnote%' AND boardtype NOT LIKE 'duplicate' ORDER BY id DESC LIMIT 1 "); 
	$solutionnote=$note->wboardid;

	$repeat='';
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE  contentsid='$questionid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$minutes=(INT)($bstep->tamount/60);
	$seconds=$bstep->tamount-$minutes*60;	 	 
	$timeused2=$bstep->tamount;


	if($handwriting->contentstitle==='realtime')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605616024001.png" width="15"> 시도완료'; 
	if($handwriting->status!=='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651974901.png" width="15"> 노트작성';
	if($handwriting->status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603251593001.png" width="15"><span style="color: rgb(233, 33, 33);"> 질문발송</span>';
	if($handwriting->status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
	if($handwriting->status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> 답변수신</a></span>';  
	if($handwriting->status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이수신</u></a></span>';   
	if($handwriting->status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이질문</u></a></span>';   
	if($handwriting->status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이답변</u></a></span>';   
	$wboardlist= $imgstatus.'&nbsp;&nbsp;'.$contentslink.' &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank">'.date("m월d일 | H:i",$value['timemodified']).' &nbsp;&nbsp;총'.$handwriting->nstroke.'획 &nbsp; | 지우개 '.$handwriting->neraser.'회 | '.$minutes.'분 '.$seconds.'초  '.$fixhistory;
 	 
	if($timeused2>$maxtime)
		{
		$maxtime=$timeused2;
		$keyquestionid=$questionid;
		$wboardid=$solutionnote;
 		}

	$speed=(INT)($handwriting->nstroke/$timeused2);
	if($speed==1000)$speed='##';

	if($value['state']==='gradedright') // 풀이노트
		{        

		if($handwriting->nstroke>4 && $bstep->tamount<2)
			{
			echo '<script>
 					 
			var Userid= \''.$studentid.'\';					 
			var Contentstype= \'2\',
			var Contentsid= \''.$questionid.'\';					
			var Currenturl= \''.$handwriting->wboardid.'\'; 
			var Nrepeat= \'100\',
			var Contentstitle= \''.$handwriting->contentstitle.'\'; 
			var Flag= \''.$handwriting->flag.'\'; 
				 
 			$.ajax({
				url:"check_synapse.php",
				type: "POST", 
				dataType:"json",
				data : {
				"eventid":\'1\',
				"userid":Userid,
				"contentstype":Contentstype,
				"contentsid":Contentsid,
				"nrepeat":Nrepeat,
				"currenturl":Currenturl,
				"contentstitle":Contentstitle,
				"flag":Flag,
				},
				success:function(data){					 
				 }
	  		      })
					 
			});
			</script>';
			}
		$timeforsuccess=$timeforsuccess+($bstep->tamount)/60;

		if($bstep->nthink==0)$repeat='<b style="color:green;">OK</span>';
		elseif($bstep->nthink<=2)$repeat='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</span>'; 
		elseif($bstep->nthink>=3)$repeat='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>'; 
 		$realtimewb.='<tr><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
		<td></td><td>'.$handwriting->nstroke.'획 | </td><td>지우개 '.$handwriting->neraser.'회 |</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$minutes.'분 '.$seconds.'초 사용<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questionimg.'" width=500><hr style="border: 1px solid grey;"><img src="'.$solutionimg.'" width=500></td></tr></table></span></div></a></td><td>'.$repeat.'</td><td><span onClick="showMoment(\''.$handwriting->wboardid.'\')"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655884442.png width=25> </span></td> </tr> ';

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

		$feedback= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE  userid='$studentid' AND contentsid='$questionid' AND wboardid LIKE '%nx4HQkXq_user%' ORDER BY id DESC LIMIT 1"); // 퀴즈 대화정보

		$color1='#F91408';$color2='#F91408';$color3='#F91408';$color4='#F91408';$color5='#F91408';$color6='#F91408';$color7='#F91408';$color8='#F91408';$color9='#F91408';$color10='#F91408';

		if($feedback->feedback2!==NULL)$color1='#0572f7';if($feedback->feedback3!==NULL)$color2='#0572f7';if($feedback->feedback4!==NULL)$color3='#0572f7';if($feedback->feedback5!==NULL)$color4='#0572f7';
		if($feedback->feedback6!==NULL)$color5='#0572f7';if($feedback->feedback7!==NULL)$color6='#0572f7';if($feedback->feedback8!==NULL)$color7='#0572f7';if($feedback->feedback9!==NULL)$color8='#0572f7';if($feedback->feedback10!==NULL)$color9='#0572f7'; 

		$dialogue.='<table align=left>';
		if($feedback->feedback1!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color1.'">'.$feedback->feedback1.' </span></h6></td></tr>';
		elseif($feedback->feedback2!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color2.'">'.$feedback->feedback2.' </span></h6></td></tr>';
		elseif($feedback->feedback3!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color3.'">'.$feedback->feedback3.' </span></h6></td></tr>';
		elseif($feedback->feedback4!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color4.'">'.$feedback->feedback4.' </span></h6></td></tr>';
		elseif($feedback->feedback5!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color5.'">'.$feedback->feedback5.' </span></h6></td></tr>';
		elseif($feedback->feedback6!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color6.'">'.$feedback->feedback6.' </span></h6></td></tr>';
		elseif($feedback->feedback7!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color7.'">'.$feedback->feedback7.' </span></h6></td></tr>';
		elseif($feedback->feedback8!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color8.'">'.$feedback->feedback8.' </span></h6></td></tr>';
		elseif($feedback->feedback9!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color9.'">'.$feedback->feedback9.' </span></h6></td></tr>';
		elseif($feedback->feedback10!=NULL)$dialogue.='<tr><td><h6><span style="color:'.$color10.'">'.$feedback->feedback10.' </span></h6></td></tr>';
		elseif($text_assess!=NULL)$dialogue.='<tr><td><h6><span style="color:black">'.$text_assess.' </span></h6></td></tr>';
		$dialogue.='</table>';

		$thisattempt = $DB->get_record_sql("SELECT * FROM mdl_question_attempts WHERE  id='$questionid'  ");
		if($thisattempt->checkflag==1)$checkstatus='checked';
		else $checkstatus='';

 		$marks.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=100%><p align=center> '.$mathcompetency.'  '.$dialogue.' </p><img src="'.$solutionimg.'"  width=100%> </td></tr>
		<tr><td align=center>< 풀 수 있었던 문제라고 생각한다면 체크 <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(26,\''.$studentid.'\',\''.$attemptid.'\', \''.$quizgrade.'\',  \''.$questionid.'\', this.checked)"/><hr style="border: 1px solid grey;"> '.$reason.'  | '.$wboardlist.' </td>  </tr><tr><td><hr style="border: 1px solid grey;"></td></tr>';    
		}
	$nattempts=$nattempts-$ngaveup;
	}
$propertimeusage=(INT)($timeforsuccess/$timelimit*100);
//$DB->execute("UPDATE {quiz_attempts} SET review=1 WHERE id='$attemptid'");  


//if($role==='student')
	{
	if($attemptinfo->maxgrade!=NULL)
		{
		$seeanalysis='<table align=center><tr><td><img src="https://mathking.kr/Contents/IMAGES/chaticon.gif" width=50> </td><td width=3%></td><td>당신은 이 시험에서 <b style="color:#3483eb;font-size:20;">'.$quizgrade.'점</b>을 받았지만 제대로 실력을 발휘하였다면 최대 <b style="color:red; font-size:20;">'.$attemptinfo->maxgrade.'점</b>을 받을 수 있었다고 느끼고 있습니다.</td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=0 WHERE id='$attemptid'");  
		}
	elseif($role==='student' && ($quizgrade < 90 || $ratio1<90) && $timecreated-$quizattempts->timefinish <30)
		{
		$alerttext='<table align=center width=100%><tr style="background-color:lightgreen;"><td align=center width=100%><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651916368.png width=100%></td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=1 WHERE id='$attemptid'");  
		echo '<script>setTimeout(function() {location.reload(); },30000);	</script>';
		}
	elseif($quizgrade>99.99 && $timecreated-$quizattempts->timefinish <30) 
		{
		$alerttext='<table align=center width=100%><tr style="background-color:lightgreen;"><td></td><td align=center><b style="font-size:24;color:orange;"> Very Good !. 정답인 문제 중에 확신이 부족하거나 비효율적인 풀이가 없는지 체크 후 다음 공부를 진행해 주세요. </b></td><td></td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=0 WHERE id='$attemptid'");  
		}
	else 
		{
		$alerttext='<table align=center width=100%><tr style="background-color:lightgreen;"><td></td><td align=center><b style="font-size:24;color:orange;">향상노트를 완료한 다음 응시결과 분석을 진행해 주세요 </b></td><td></td></tr></table>';
		}
	}
if($quizgrade>99.9)$analysistext='만점을 받았습니다. 하지만 풀이과정에서 혹시 미흡한 부분이 없었는지 점검해 주세요';
elseif($attemptinfo->maxgrade!=NULL) $analysistext='<table><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649851781.png" width=50> </td><td width=3%></td><td>오답노트를 완료한 다음 <b> 풀 수 있었다고 생각하는 문제 </b> (학교시험에서 유사문제를 실제 풀 수 있을지를 기준으로)에 체크한 다음 현재 상태를 성찰하고 다음 시도의 결과를 예측해 보세요 ! <button onClick="UpdateGrade(27,\''.$attemptid.'\',\''.$quizgrade.'\')">결과보기 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649929463.png width=25></button></td></tr></table><hr style="border: 1px solid grey;">'.$seeanalysis;
else $analysistext='<table><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649851781.png" width=50> </td><td width=3%></td><td>오답노트를 완료한 다음 <b> 풀 수 있었다고 생각하는 문제 </b> (학교시험에서 유사문제를 실제 풀 수 있을지를 기준으로)에 체크한 다음 현재 상태를 성찰하고 다음 시도의 결과를 예측해 보세요 ! <button onClick="UpdateGrade(27,\''.$attemptid.'\',\''.$quizgrade.'\')">결과보기 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649929463.png width=25></button></td></tr></table><hr style="border: 1px solid grey;">'.$seeanalysis;

echo '<br>'.$alerttext.'<table width=100% align=center>'.$marks.'</table>
<br><br><table width=95%><tr><th width="100%"><b>시험결과 분석하기</b></th></tr>
<tr><td valign="top"><hr style="border: 1px solid grey;"> </td></tr>		   
<tr><td valign="top">'.$analysistext.' </td></tr></table> <table width="100%"><tr> <th width="100%">풀이노트</th></tr><tr><td valign="top"><table>'.$realtimewb.'</table></td></table><hr style="border: 1px solid grey;"> 정답을 위해 사용된 총시간 : '.round($timeforsuccess,1).' 분 ('.$propertimeusage.'%)<hr style="border: 1px solid grey;">점수 '.$quizgrade.'점 | 정답률 '.$ratio1.'% <hr style="border: 1px solid grey;"><button onClick="UpdateGrade(27,\''.$attemptid.'\',\''.$quizgrade.'\')">필기정보 업데이트</button>';
}
else  // desktop
{
$marks0=$quizname.'<br>';
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
	 
		//$questionimg=str_replace("%2F", "/", urlencode($questionimg));
 
		if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
		}
/*
	$htmlDom2 = new DOMDocument; @$htmlDom2->loadHTML($value['generalfeedback']); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages2 = array();
	$nimg=0;
	foreach($imageTags2 as $imageTag2)
		{
		$nimg++;
    		$solutionimg = $imageTag2->getAttribute('src');
		$solutionimg = str_replace(' ', '%20', $solutionimg); 
		if(strpos($solutionimg, 'MATRIX/MATH')!= false && strpos($solutionimg, 'hintimages') == false)break;
		}
*/
	$qcomment=$value['comment'];
	if($value['state']==='gradedwrong')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651975727.png" width=20>';
	if($value['state']==='gradedpartial')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651976018.png" width=20>';
	if($value['state']==='gaveup')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651975887.png" width=20>';
 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND contentsid='$questionid' AND contentstype='2' AND boardtype NOT LIKE 'duplicate'  ORDER BY id DESC LIMIT 1 ");
	$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
	if($handwriting->teacher_check==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
	if($handwriting->teacher_check==2)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
	$encryption_id=$handwriting->wboardid;
 
	$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 
 
	$solutionnote='Q7MQFA'.$handwriting->contentsid.'0tsDoHfRT_user'.$handwriting->userid;   
	 
	$recenttime=time()-43200;
	$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '%$solutionnote%' AND boardtype NOT LIKE 'duplicate' ORDER BY id DESC LIMIT 1 "); 
	$solutionnote=$note->wboardid;

	$repeat='';
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE  contentsid='$questionid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$minutes=(INT)($bstep->tamount/60);
	$seconds=$bstep->tamount-$minutes*60;	 	 
	$timeused2=$bstep->tamount;

	if($handwriting->contentstitle==='realtime')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605616024001.png" width="15"> 시도완료'; 
	if($handwriting->status!=='complete')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204763001.png" width="15"> 노트작성';
	if($handwriting->status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603251593001.png" width="15"><span style="color: rgb(233, 33, 33);"> 질문발송</span>';
	if($handwriting->status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
	if($handwriting->status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> 답변수신</a></span>';  
	if($handwriting->status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이수신</u></a></span>';   
	if($handwriting->status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이질문</u></a></span>';   
	if($handwriting->status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이답변</u></a></span>';   
	$wboardlist= $imgstatus.'&nbsp;&nbsp;'.$contentslink.' &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank">'.date("m월d일 | H:i",$value['timemodified']).' &nbsp;&nbsp;총'.$handwriting->nstroke.'획 &nbsp; | 지우개 '.$handwriting->neraser.'회 | '.$minutes.'분 '.$seconds.'초  '.$fixhistory;
 	 
	if($timeused2>$maxtime)
		{
		$maxtime=$timeused2;
		$keyquestionid=$questionid;
		$wboardid=$solutionnote;
 		}

	$speed=(INT)($handwriting->nstroke/$timeused2);
	if($speed==1000)$speed='##';

	if($value['state']==='gradedright') // 풀이노트
		{        
		if($handwriting->nstroke>4 && $bstep->tamount<2)
			{ 
			// echo '<script> var Currenturl= \''.$handwriting->wboardid.'\'; window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_review.php?id="+Currenturl+"&action=close", \'_blank\');    </script>';
			}
		$timeforsuccess=$timeforsuccess+($bstep->tamount)/60;

		if($bstep->nthink==0)$repeat='<b style="color:green;">OK</span>';
		elseif($bstep->nthink<=2)$repeat='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</span>'; 
		elseif($bstep->nthink>=3)$repeat='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>'; 
 		$realtimewb.='<tr><td> '.$contentslink.'</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
		<td></td><td>'.$handwriting->nstroke.'획 | </td><td>지우개 '.$handwriting->neraser.'회 |</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank"><div class="tooltip4">'.$contentsready.'&nbsp;&nbsp;'.$minutes.'분 '.$seconds.'초 사용<span class="tooltiptext4"><table align=center><tr><td><img src="'.$questionimg.'" width=500></td></tr></table></span></div></a></td><td>'.$repeat.'</td> <td><span onClick="showMoment(\''.$handwriting->wboardid.'\')"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655884442.png width=25> </span></td> </tr> ';
		}
	elseif($value['state']==='complete' && $value['timecreated']>$attemptinfo->timeadded && $attemptinfo->timeadded!=NULL)
		{   
		$attempt_delayed.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=80%>'.$mathcompetency.'  '.$dialogue.' </td></tr><tr><td align=center>'.$reason.'  | '.$wboardlist.'<hr style="border: 1px solid grey;"> </td></tr>';    
			$nafter++;
		}
	elseif($value['state']!=='complete')	// 평가준비, 서술평가
		{ 	
		$nwrong++;
		$thisattempt = $DB->get_record_sql("SELECT * FROM mdl_question_attempts WHERE  id='$questionid'  ");
		if($thisattempt->checkflag==1)$checkstatus='checked';
		else $checkstatus='';

 		$marks.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=80%></td></tr><tr><td align=center><br> 풀 수 있었던 문제라고 생각한다면 체크 <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(26,\''.$studentid.'\',\''.$attemptid.'\', \''.$quizgrade.'\',  \''.$questionid.'\', this.checked)"/><br> '.$reason.'  | '.$wboardlist.' <hr style="border: 1px solid grey;"></td>  </tr>';    
		}
	$nattempts=$nattempts-$ngaveup;
	}
$propertimeusage=(INT)($timeforsuccess/$timelimit*100);

$checkgoal_maxid= $DB->get_record_sql("SELECT max(id) AS id FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) AND timecreated < '$quizattempts->timefinish' ");
$checkgoal= $DB->get_record_sql("SELECT id, text,timecreated FROM  mdl_abessi_today Where id ='$checkgoal_maxid->id' ");


$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
 
$schedule=$DB->get_record_sql("SELECT id,editnew, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
	 
if($nday==1){$tstart=$schedule->start1; $hours=$schedule->duration1;} 
if($nday==2){$tstart=$schedule->start2; $hours=$schedule->duration2;} 
if($nday==3){$tstart=$schedule->start3; $hours=$schedule->duration3;} 
if($nday==4){$tstart=$schedule->start4; $hours=$schedule->duration4;} 
if($nday==5){$tstart=$schedule->start5; $hours=$schedule->duration5;} 
if($nday==6){$tstart=$schedule->start6; $hours=$schedule->duration6;} 
if($nday==7){$tstart=$schedule->start7; $hours=$schedule->duration7;} 
 
//$jumpspot= $DB->get_record_sql("SELECT * FROM mdl_abessi_forecast WHERE userid='$studentid' AND timecreated < '$quizattempts->timefinish' ORDER BY id DESC LIMIT 1");
if($timecreated-$quizattempts->timefinish >43200)$progresstext='활동완료';
else $progresstext='<hr style="border: 1px solid grey;"> # 오늘의 목표 : '.$checkgoal->text.'        <br><br> # 종료 '.round(($checkgoal->timecreated+$hours*3600-$timecreated)/3600,1).'시간 전입니다.<hr style="border: 1px solid grey;">';

//$DB->execute("UPDATE {quiz_attempts} SET review=1 WHERE id='$attemptid'");  
//if($role==='student')
	{
	if($attemptinfo->maxgrade!=NULL)
		{
		$seeanalysis='<table align=center><tr><td><img src="https://mathking.kr/Contents/IMAGES/chaticon.gif" width=50> </td><td width=3%></td><td>당신은 이 시험에서 <b style="color:#3483eb;font-size:20;">'.$quizgrade.'점</b>을 받았지만 제대로 실력을 발휘하였다면 최대 <b style="color:red; font-size:20;">'.$attemptinfo->maxgrade.'점</b>을 받을 수 있었다고 느끼고 있습니다.</td></tr></table><table  align=left><tr><td align=left><img src="https://mathking.kr/Contents/IMAGES/chaticon.gif" width=50></td><td width=3%><td>'.$progresstext.'</td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=0 WHERE id='$attemptid'");  
		}
	elseif($role==='student' && ($quizgrade < 90 || $ratio1<90) && $timecreated-$quizattempts->timefinish <30)
		{
		$alerttext='<table align=center width=100%><tr style="background-color:lightgreen;"><td align=center width=100%><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651916368.png width=100%></td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=1 WHERE id='$attemptid'");  
		echo '<script>setTimeout(function() {location.reload(); },30000);	</script>';
		}
	elseif($quizgrade>99.99 && $timecreated-$quizattempts->timefinish <30) 
		{
		$alerttext='<table align=center width=80%><tr style="background-color:lightgreen;"><td></td><td align=center><b style="font-size:18;color:orange;"> Very Good !. 시간을 초과한 다음 푼 문제 중 고민지점인 있는 문항들을 숙달시켜 주세요 !</b></td><td></td></tr></table>';
		$DB->execute("UPDATE {quiz_attempts} SET review=0 WHERE id='$attemptid'");  
		}
	else 
		{
		$alerttext='<table align=center width=80%><tr style="background-color:lightgreen;"><td></td><td align=center><b style="font-size:18;color:orange;">향상노트를 완료한 다음 응시결과 분석을 진행해 주세요 </b></td><td></td></tr></table>';
		}

	}
if($quizgrade>99.9)$analysistext='만점을 받았습니다. 하지만 풀이과정에서 혹시 미흡한 부분이 없었는지 점검해 주세요';
elseif($attemptinfo->maxgrade!=NULL) $analysistext='<table><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649851781.png" width=50> </td><td width=3%></td><td>오답노트를 완료한 다음 <b> 풀 수 있었다고 생각하는 문제 </b> (학교시험에서 유사문제를 실제 풀 수 있을지를 기준으로)에 체크한 다음 현재 상태를 성찰하고 다음 시도의 결과를 예측해 보세요 ! <button onClick="UpdateGrade(27,\''.$attemptid.'\',\''.$quizgrade.'\')">결과보기 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649929463.png width=25></button></td></tr></table><hr style="border: 1px solid grey;">'.$seeanalysis;
else $analysistext='<table><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649851781.png" width=50> </td><td width=3%></td><td>오답노트를 완료한 다음 <b> 풀 수 있었다고 생각하는 문제 </b> (학교시험에서 유사문제를 실제 풀 수 있을지를 기준으로)에 체크한 다음 현재 상태를 성찰하고 다음 시도의 결과를 예측해 보세요 ! <button onClick="UpdateGrade(27,\''.$attemptid.'\',\''.$quizgrade.'\')">결과보기 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649929463.png width=25></button></td></tr></table><hr style="border: 1px solid grey;">'.$seeanalysis;

echo '<br><table width=80% align=center><tr><th>'.$alerttext.'</th></tr></table><table align=center><tr><td>'.$marks0.'</td></tr></table><table width=90% align=center><tr><td valign=top width=48%><table width=100% style="border: 1px solid black;"><tr><td align=center style="font-size:24;background-color:lightgrey;"><br><b>오답노트 ('.$nwrong.')</b><br> <br> </td></tr>'.$marks.'</table></td><td width=4%></td><td width=48% valign=top><table style="border: 1px solid black;"width=100%><tr><td align=center style="font-size:24;background-color:lightgrey;"><br><b>시간추가 후 풀이 ('.$nafter.')</b><br> <br> </td></tr>'.$attempt_delayed.'</table></td></tr></table>
<br><br><table width=80% align=center><tr><th width="48%"><b>시험결과 분석하기</b></th><th width="2%"></th><th width="50%">풀이노트</th></tr>
<tr><td valign="top"><hr style="border: 1px solid grey;"> </td><td ></td><td valign="top"> <hr style="border: 1px solid grey;"> </td></tr>		   
<tr><td valign="top">'.$analysistext.' </td><td ></td><td valign="top"><table>'.$realtimewb.'</table><hr style="border: 1px solid grey;"> 정답을 위해 사용된 총시간 : '.round($timeforsuccess,1).' 분 ('.$propertimeusage.'%)<hr style="border: 1px solid grey;">점수 '.$quizgrade.'점 | 정답률 '.$ratio1.'% <hr style="border: 1px solid grey;"><button onClick="window.location.reload();">필기정보 업데이트</button></td></tr></table>';
}
if($role==='student' && $timecreated-$quizattempts->timefinish <60 && ($quizgrade < 98 || $ratio1<98)  ) 
		{
		$component='quizattempt';
		$eventname='quizfinished';
		$userfrom=$studentid;
		$userto=$indic->teacherid;
		$sendername=$studentname;
		$notificationtext= $sendername.'_'.$quiztitle.'https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'   '.$quizgrade.'점 결과분석https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$attemptid.' 메세지https://mathking.kr/moodle/message/index.php?id='.$studentid;
		include("../teachers/notification.php"); 
		}
echo '<hr style="border: 1px solid grey;"><table width="100%"><tr><td>난이도</td><td><img  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1654452243.png" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr style="border: 1px solid grey;">
<hr style="border: 1px solid grey;"><p align=center>오늘도 즐거운 비행이 되고 계신가요 ?  추락의 위기가 오더라도 슬기롭게 극복하여 마지막까지 안전한 착륙을 기원합니다. ^_____^</p><hr style="border: 1px solid grey;">';
	
$conn->close(); 
echo ' 
<style>
<a:hover {
  background-color: yellow;
}
</style>
<script>
function showMoment(Wboardid)
	{
	Swal.fire({
	position:"top-left",showCloseButton: true,width:1200,
	  html:
	   \'<iframe style="border: 1px none; z-index:3; width:1190; height:100vh;  margin-left: -50px;margin-right: 0px;  margin-top: -0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=\'+Wboardid+\'" ></iframe>\',
	  showConfirmButton: false,
  	   })
	}
function ChangeCheckBox(Eventid,Userid, Attemptid, Quizgrade, Questionid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "attemptid":Attemptid,
		                "questionid":Questionid,
		                "quizgrade":Quizgrade,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}
function UpdateGrade(Eventid,Attemptid,Quizgrade){
		    
		   $.ajax({
		        url: "check.php",
  		      type: "POST",
		        dataType: "json",
		        data : {
		                "attemptid":Attemptid,
			    "quizgrade":Quizgrade,
		                "eventid":Eventid,
		               },
		        success: function (data){  
			var Maxgrade=data.maxgrade;
			var Gradeup=Maxgrade-Quizgrade;
			 swal("향상노트 후 + "  + Gradeup + "점 상승 !" ,"당신은 이 시험에서 " + Quizgrade + "점을 받았지만 제대로 실력을 발휘하였다면 " + Maxgrade + "점을 받을 수 있다고 느끼고 있습니다.", {buttons: false,timer: 5000});
			setTimeout(function() {location.reload(); },3000);	
		        }
		    });		
		}
</script>
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

?>
