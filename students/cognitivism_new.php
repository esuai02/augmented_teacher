<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
 
global $DB, $USER;

$conn = new mysqli($servername, $username, $password, $dbname);

// 퀴즈 분석 및 성찰적 피드백 환경
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$attemptid=required_param('attemptid', PARAM_INT); 
include("navbar.php");
//$studentid=required_param('id', PARAM_INT); 
$attemptinfo= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ORDER BY id DESC LIMIT 1");
$uniqueid=$attemptinfo->uniqueid;
 
$qnum=substr_count($attemptinfo->layout,',')+1-substr_count($attemptinfo->layout,',0'); 
/*
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
*/
$quizattempts = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades, mdl_quiz.timelimit AS timelimit FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.id='$attemptid' ORDER BY id DESC LIMIT 1  ");
/*
$quizgrade=round($quizattempts->sumgrades/$quizattempts->tgrades*100,0);  // 점수
$timelimit =$quizattempts->timelimit/60;  // 시간활용
if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
if($ratio1==0 && $Qnum2==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
*/
$diagnosistext='퀴즈 진단 결과 : ';
/* 
 - 지도모드가 필요한 경우 --> 단기목표 설정 - 직접입력으로 단기목표 제시방식

# 자습모드/지도모드/도제학습모드
 - 데이터 진단 : 1자습/2지도/3도제 d_mode = 1,2,3    - 선생님 판단 : 1자습/2지도/3도제 t_mode = 1,2,3

 d_mode=1 --> 평점 OK      d_mode=2 --> 퀴즈 피드백 OK (30분 이내)     d_mode=3 --> 현재 페이지 체류시간 10분 이상 발견, 퀴즈 풀이 양 이상 등.

d_mode-t_mode<0 이면 '조치요청'으로 psc에 자동표시
 
	if(strpos($quiztitle, '단원-주제')!= false)$quizgoal='최소 시도로 연속 3회 100점을 맞는 것이 목적입니다';
	elseif(strpos($quiztitle, '개념도약')!= false)$quizgoal='개념을 정확히 익히고 연습을 하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '내신')!= false)$quizgoal='최소 시도로 커트라인을 통과 후 레벨업 하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '인지촉진')!= false)$quizgoal='최소 시도로 연속 3회 100점을 맞는 것이 목적입니다.';
	elseif(strpos($quiztitle, '보강학습')!= false)$quizgoal='문제지를 풀 듯이 한 문제씩 정확히 풀고 이해하는 것이 목적입니다.';
	elseif(strpos($quiztitle, '인증시험')!= false)$quizgoal='최소 시도로 커트라인을 통과 후 다음 단원으로 넘어가는 것이 목적입니다.';
	elseif(strpos($quiztitle, '모의고사')!= false)$quizgoal='데드라인까지 목표점수를 통과하는 것이 목표입니다.';

*/

$timecreated=time();
$adayago=$timecreated-86400; 
$quiztitle=$quizattempts->name;
$DB->execute("UPDATE {quiz_attempts} SET ratio='$ratio1' WHERE id='$attemptid' ORDER BY id DESC LIMIT 1");  


 /*
$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND (status LIKE  'begin'   OR status LIKE  'flag'   OR status LIKE 'reply' OR status LIKE 'retry' OR status LIKE 'present' ) AND hide=0 AND tlaststroke>'$adayago' AND contentstype=2  ORDER BY tlaststroke DESC LIMIT 1");
echo '<script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>';
 

// 우선순위 고려하여 출력
if($quizattempts->timelimit-($quizattempts->timefinish-$quizattempts->timestart) > 60 && $quizgrade <90)$diagnosistext.='🧑🏻주어진 시간을 최대한 활용해 주세요. ';
 
if($ratio1 < 80 )$diagnosistext.='🧑🏻좀 더 침착하게 퀴즈에 응시해 주시기 바랍니다. ';

if($ratio1 >= 90 && $quizgrade <= 85)$diagnosistext.='🧑🏻제한시간 안에 문제를 푸는 것에 어려움이 있어 보입니다. 오답노트 및 부스터 활동 비중을 높여 보세요 ';

if($note->id!=NULL && $quizgrade <= 90)$diagnosistext.='🧑🏻새로운 테스트를 시작하기 전 향상노트나 고민지점에 대한 학습을 완료한 다음 진행하는 것이 가장 효율적입니다.';
 
if($quizattempts->attempt>5 && (strpos($quiztitle, '단원-주제')!= false || strpos($quiztitle, '기초완전학습')!= false || strpos($quiztitle, '인지촉진')!= false) && $quizgrade < 90 )$diagnosistext.='🧑🏻같은 종류의 테스트를 너무 많이 보고 있습니다. 응시한 문항들에 복습을 진행해 주세요 ';
elseif($quizattempts->attempt>3 && strpos($quiztitle, '개념도약')!= false && $quizgrade < 90 )$diagnosistext.='🧑🏻부스터활동의 비중을 높이면 효과적인 개념공부가 가능하고 오래 기억할 수 있습니다. |';
elseif($quizattempts->attempt>5 && strpos($quiztitle, '보강학습')!= false && $quizgrade < 90 )$diagnosistext.='🧑🏻같은 종류의 테스트를 너무 많이 보고 있습니다. 오답노트 복습과 부스터 활동을 통하여 정체구간을 돌파할 수 있습니다. ';
elseif($quizattempts->attempt>5 && strpos($quiztitle, '인증시험')!= false && $quizgrade < 90 )$diagnosistext.='🧑🏻같은 종류의 테스트를 너무 많이 보고 있습니다. 오답노트 복습과 부스터 활동을 통하여 정체구간을 돌파할 수 있습니다. ';
elseif($quizattempts->attempt>5 && strpos($quiztitle, '모의고사')!= false && $quizgrade < 80 )$diagnosistext.='🧑🏻같은 종류의 테스트를 응시 빈도가 많습니다. 오답노트 복습과 부스터 활동을 통하여 정체구간을 돌파할 수 있습니다. ';

if($quizgrade < 65  && (strpos($quiztitle, '내신')!= false || strpos($quiztitle, '보강학습')!= false || strpos($quiztitle, '인증시험')!= false  || strpos($quiztitle, '모의고사')!= false  || strpos($quiztitle, '기초표준테스트')!= false  || strpos($quiztitle, '기본 대단원 T')!= false) )$diagnosistext.='🧑🏻잠깐 활동을 멈추고 오답원인을 함께 고민해 봅시다. 선생님에게 와주세요 ';

if($diagnosistext==NULL)$diagnosistext.='🧑🏻최선을 다한 결과로 보입니다.';
 */
$quizname='<br><table align=center><tr><th>'.date("m/d | H:i",$quizattempts->timestart).' | <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$quizattempts->id.' " target="_blank">'.$quiztitle.'</a>&nbsp;('.$quizattempts->attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;'.$quizattempts->state.'...'.date("H:i",$quizattempts->timefinish).' <img src='.$imgtoday.' width=25></th></tr></table><br>'.$diagnosistext.'<br>';


 
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

	$qcomment=$value['comment'];
	//if($value['state']==='gradedwrong')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882870001.png" width=30>';
	//if($value['state']==='gradedpartial')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882934001.png" width=30>';
	//if($value['state']==='gaveup')$reason='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605882809001.png" width=30>';
 	if($value['state']==='gradedwrong')$reason='<b style="color:red;">오답문항</b>';
	if($value['state']==='gradedpartial')$reason='<b style="color:orange;">부분오답</b>';
	if($value['state']==='gaveup')$reason='<b style="color:#3483eb;">보류문항</b>';
 
	$handwriting=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  active=1 AND userid='$studentid' AND contentsid='$questionid' AND contentstype='2'   ORDER BY id DESC LIMIT 1 ");
	$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
	if($handwriting->teacher_check==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
	if($handwriting->teacher_check==2)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
	$encryption_id=$handwriting->wboardid;
	$nstroke=(int)($handwriting->nstroke/2);
	$ave_stroke=round($nstroke/(($handwriting->tlast-$handwriting->tfirst)/60),1);
	$timeused=round((($handwriting->tlast-$handwriting->tfirst)/60),0);	 
	$tmodified=round((time()-$handwriting->timemodified)/60,0);
	$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 학습완료'; 
 
	$solutionnote='Q7MQFA'.$handwriting->contentsid.'0tsDoHfRT_user'.$handwriting->userid;   
	 
	$recenttime=time()-43200;
	$note=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$solutionnote' ORDER BY id DESC LIMIT 1 "); 
	$solutionnote=$note->wboardid;
	$status=$handwriting->status;
	include("../whiteboard/status_icons.php");
/*
	if($handwriting->contentstitle==='realtime')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1605616024001.png" width="15"> 시도완료'; 
	if($handwriting->status==='begin')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204763001.png" width="15"> 평가준비';
	if($handwriting->status==='ask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603251593001.png" width="15"><span style="color: rgb(233, 33, 33);"> 질문발송</span>';
	if($handwriting->status==='review')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204225001.png" width="15"> 복습예약';  
	if($handwriting->status==='reply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204129001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$encryption_id.'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> 답변수신</a></span>';  
	if($handwriting->status==='solution')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186545001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/reply.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이수신</u></a></span>';   
	if($handwriting->status==='solutionask')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603040404001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이질문</u></a></span>';   
	if($handwriting->status==='solutionreply')$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603186950001.png" width="15"><span style="color: rgb(233, 33, 33);"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replyto.php?id='.$value['wbfeedback'].'&originalid=OVc4lRh'.$questionid.'nx4HQkXq_user'.$studentid.'" target="_blank"> <u>풀이답변</u></a></span>';   
*/
	$wboardlist= $imgstatus.'&nbsp;&nbsp;'.$contentslink.' &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank">'.date("m월d일 | H:i",$value['timemodified']).' &nbsp;&nbsp;총'.$nstroke.'획 &nbsp; '.$ave_stroke.'획/분 '.$fixhistory;

	if($value['state']==='gradedright') // 풀이노트
	       {      
/*   
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
	 
	if($timeused2>$maxtime)
		{
		$maxtime=$timeused2;
		$keyquestionid=$questionid;
		$wboardid=$solutionnote;
 		}

	$speed=(INT)($nstroke2/$timeused2);
	if($speed==1000)$speed='##';
	
	if($tfirst2!=NULL && $tlast2!=NULL)$DB->execute("UPDATE {abessi_messages} SET nstroke='$nstroke2', tlast='$tlast2', tfirst='$tfirst2' WHERE wboardid='$solutionnote' ");  
	$repeat='';
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$questionid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");

	if($bstep->nthink==0)$repeat='<b style="color:green;">OK</span>';
	elseif($bstep->nthink<=2)$repeat='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</span>'; 
	elseif($bstep->nthink>=3)$repeat='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>'; 
 	$realtimewb.='<tr><td>'. $imgstatus.'&nbsp;'.$contentslink.'</td><td><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$studentname.'</a></td>
	<td></td><td>'.$nstroke2.'획 | '.$speed.'획/분 | </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$solutionnote.'" target="_blank">'.$contentsready.'&nbsp;&nbsp;'.$minutes.'분 '.$seconds.'초 사용</a></td><td> | '.$tmodified2.'분</td><td>'.$repeat.'</td>  </tr> ';
	*/
	       }
	else	// 평가준비, 서술평가
	       {
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

	$thisattempt = $DB->get_record_sql("SELECT * FROM mdl_question_attempts WHERE  id='$questionid'  ");
	if($thisattempt->checkflag==1)$checkstatus='checked';
	else $checkstatus='';

 	$marks.='<tr><td align=center valign=top><img src="'.$questionimg.'" width=500></td><td align=center valign=top><img src="'.$solutionimg.'"  width=500> </td><td valign=top><b>▣ 공부방향 ▣<br><br>'.$mathcompetency.' </b><br><br><b>▣ 대화내용 ▣</b> <br><br> '.$dialogue.' <br><br></td></tr>
	<tr><td align=center><hr><b style="color:#3483eb; font-size:18px;"> 풀 수 있었던 문제라고 생각한다면 체크 </b><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(26,\''.$studentid.'\',\''.$attemptid.'\', \''.$quizgrade.'\',  \''.$questionid.'\', this.checked)"/></td><td align=center><hr> '.$reason.' &nbsp;&nbsp; '.$wboardlist.' </td>  </tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';    
	}
$nattempts=$nattempts-$ngaveup;
}
 
 
// 풀이방법 개선 문항 출제
$encryption_id2=$wboardid;
//var_dump($encryption_id2);
$timecreated=time();
if($maxtime>60 && $maxtime <900)
	{
	include("createdb_improve.php");
	$DB->execute("UPDATE {abessi_messages} SET  status='accelerate', timemodified='$timecreated' WHERE wboardid='$encryption_id2' ");  
 	}
 
$propertimeusage=(INT)($timeforsuccess/$timelimit*100);

if($attemptinfo->maxgrade!=NULL)
	{
	$seeanalysis='<table><tr><td><img src="https://mathking.kr/Contents/IMAGES/chaticon.gif" width=50> </td><td width=3%></td><td>당신은 이 시험에서 <b style="color:#3483eb;font-size:20;">'.$quizgrade.'점</b>을 받았지만 제대로 실력을 발휘하였다면 최대 <b style="color:red; font-size:20;">'.$attemptinfo->maxgrade.'점</b>을 받을 수 있었다고 느끼고 있습니다.</td></tr></table>';
	}
else $alerttext='<table align=center width=100%><tr style="background-color:lightgreen;"><td></td><td align=center><b style="font-size:24;color:orange;">향상노트를 완료한 다음 응시결과 분석을 진행해 주세요 </b></td><td></td></tr></table>';

if($quizgrade>99.9)$analysistext='만점을 받았습니다. 하지만 풀이과정에서 혹시 미흡한 부분이 없었는지 점검해 주세요';
else $analysistext='<table><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649851781.png" width=50> </td><td width=3%></td><td>오답노트를 완료한 다음 <b> 풀 수 있었다고 생각하는 문제 </b> (학교시험에서 유사문제를 실제 풀 수 있을지를 기준으로)에 체크한 다음 현재 상태를 성찰하고 다음 시도의 결과를 예측해 보세요 ! <span onclick="window.location.reload(true);"><b style="color:#eb6134;">결과보기 클릭</b><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1649929463.png width=25></span></td></tr></table><hr>'.$seeanalysis;

echo '<br>'.$alerttext.'<table width=100% align=center><tr><th width=35%><hr></th><th width=35%><hr></th> <th width=30%><hr></th></tr>'.$marks.'</table>
<br><br><table width=95%><tr><th width="48%"><b>시험결과 분석하기</b></th><th width="2%"></th><th width="50%">풀이노트</th></tr>
<tr><td valign="top"><hr> </td><td ></td><td valign="top"> <hr> </td></tr>		   
<tr><td valign="top">'.$analysistext.' </td><td ></td><td valign="top"><table>'.$realtimewb.'</table><hr> 정답을 위해 사용된 총시간 : '.round($timeforsuccess,1).' 분 ('.$propertimeusage.'%)<hr>점수 '.$quizgrade.'점 | 정답률 '.$ratio1.'% <hr><button onClick="window.location.reload();">필기정보 업데이트</button></td></tr></table>';

echo '<br><table width="100%"><tr><td>난이도</td><td><img  src="https://play-lh.googleusercontent.com/PkNdm5zWBQoe7JVYWu_b3fyw8SxkeeF8EkZiGKc71LOAj1-BNaWREVkUf_Asqfq4_Co" width=50 ></td><td>상태</td><td><img   src="https://i.gifer.com/JFi.gif" width=200 ></td><td><img   src="https://i.pinimg.com/originals/04/8c/8e/048c8e251c1a6a1a9f8b35f68dcd8b52.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://s.wsj.net/public/resources/images/OG-DG972_201910_M_20191009103200.gif"  width=200 ></td><td><img   src="https://cdn5.vectorstock.com/i/1000x1000/13/59/airplane-is-landing-or-taking-off-on-runway-vector-25911359.jpg" width=200  ></td></tr></table>
<hr><p align=center>KTM의 경쟁상대는 대한항공</p><hr>';
	
$conn->close(); 
echo ' 
<script>
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

//include("quicksidebar.php");
?>