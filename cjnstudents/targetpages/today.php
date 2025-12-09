<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
global $DB, $USER;
include("navbar.php");

//$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
//$role=$userrole->role;
 
$tbegin= $_GET["tb"]; 
$maxtime=time()-$tbegin;
$indicator= $DB->get_record_sql("SELECT * FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
/*
$gradedright = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.subject LIKE 'math' AND mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$gradedwrong = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.subject LIKE 'math' AND  mdl_question_attempt_steps.userid='$studentid' AND  (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial')   AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$gaveup = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.subject LIKE 'math' AND  mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gaveup' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");

$graded_attempts = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id, mdl_question_attempt_steps.state FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.subject LIKE 'math' AND mdl_question_attempt_steps.userid='$studentid' AND mdl_question_attempt_steps.timecreated > '$maxtime' ORDER BY mdl_question_attempt_steps.id DESC ");
 */
 $graded_attempts = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id, mdl_question_attempt_steps.state FROM mdl_question_attempt_steps 
LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  
WHERE mdl_question.subject LIKE 'math' 
AND mdl_question_attempt_steps.userid='$studentid' 
AND mdl_question_attempt_steps.timecreated > '$maxtime' 
ORDER BY mdl_question_attempt_steps.id DESC ",null,0,0,false,'userid_timecreated');
 
/*
$nright=count($gradedright);
$nwrong=count($gradedwrong);
$ngaveup=count($gaveup);
*/

$gradedright = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gradedright';});
$nright = count($gradedright);
$gradedwrong = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gradedwrong' || $attempt->state == 'gradedpartial';});
$nwrong = count($gradedwrong);
$gaveup = array_filter($graded_attempts, function($attempt) {return $attempt->state == 'gaveup';});
$ngaveup = count($gaveup);


$timecreated=time();

// get mission list
$timestart2=time()-$tbegin;
$adayAgo=time()-43200;
$aweekAgo=time()-604800;
$timestart3=time()-86400*14;

/*
$activitylog=$DB->get_records_sql("SELECT * FROM mdl_logstore_standard_log WHERE  userid='$studentid' AND  component NOT LIKE 'core' AND timecreated > '$adayAgo'  AND courseid NOT LIKE '239' ORDER BY id ASC ");  
$breaklog = json_decode(json_encode($activitylog), True);
 
unset($valuebrk);
$n10=0;  
foreach( $breaklog  as $valuebrk)
{
$tdiff=$valuebrk['timecreated']-$tprev;
 
if($tdiff>600 && $tdiff<43200) // 5분이상 부터 측정
	{
	$n10++;   
	$breakinfo.='<input type="checkbox" name="checkAccount" /> 활동감소 : '. date("h 시 i 분", $tprev).' ('.round($tdiff/60,0).'분) &nbsp;&nbsp;&nbsp;&nbsp;<br> ';
	}
$tprev=$valuebrk['timecreated'];
}
*/

$inspectwboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_firesynapse WHERE  userid='$studentid' AND tamount>0 AND wbtype=1 AND timecreated > '$adayAgo'   ORDER BY nstroke ASC LIMIT 5");  
$delayevents = json_decode(json_encode($inspectwboards), True);
 
unset($value);
 
foreach( $delayevents  as $value)
	{
	$tamount=$value['tamount'];
	$checkwb=$value['wboardid'];
	$nstrokewb=$value['nstroke'];
	$breakinfo.='<input type="checkbox" name="checkAccount" /> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$checkwb.'"target="_blank"> 풀이노트 ('.$tamount.'분 | '.$nstrokewb.'획)</a>   &nbsp;&nbsp;&nbsp;&nbsp;<br> '; 
	}
$inspect=$DB->get_record_sql("SELECT data AS time FROM mdl_user_info_data where userid='$studentid' AND fieldid='56' "); 
// mdl_quiz.id AS quizid, mdl_quiz_attempts.state AS state, mdl_quiz_attempts.attempt AS attempt, mdl_quiz_attempts.layout AS layout, mdl_quiz_attempts.id AS id, mdl_quiz.name AS name,
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.timemodified > '$timestart2' AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.id DESC LIMIT 200 ");
$quizresult = json_decode(json_encode($quizattempts), True);

$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0;  $ntodayquiz=0;  $weekGrade=0;  $nweekquiz=0;$totalquizgrade1=0;$totalmaxgrade1=0;$nmaxgrade1=0; $totalquizgrade2=0;$totalmaxgrade2=0;$nmaxgrade2=0; $totalquizgrade3=0;$totalmaxgrade3=0;$nmaxgrade3=0; 
unset($value); 	
foreach($quizresult as $value) 
	{
		$comment='';
		$qnum=substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');   //if($role!=='student')
		$quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);

		if($quizgrade>79.99)
			{
			$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
			}
		elseif($quizgrade>69.99)
			{
			$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
			}
		else $imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
		$quizid=$value['quiz'];
		$moduleid=$DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'  "); 
		$quizmoduleid=$moduleid->id;
		if(strpos($value['name'], 'ifmin')!== false)$quiztitle0=substr($value['name'], 0, strpos($value['name'], '{'));
		else $quiztitle0=$value['name'];
		$quiztitle=iconv_substr($quiztitle0, 0, 30, "utf-8");
		$quizinstruction='<b>'.$quiztitle0.' </b><br><br> '.$value['instruction'].'<hr>'.$value['comment'];
		if($value['maxgrade']==NULL) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:blue">분석</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		elseif(strpos($value['comment'], '최선을 다한 결과')!== false) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:green">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		elseif($value['comment']==NULL) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:grey">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		else $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:red">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		$attemptid=$value['id'];
		$modifyquiz='';

		if($value['state']==='inprogress' && $role==='student')$modifyquiz='<span onclick="addquiztime(\''.$attemptid.'\')"><img src=https://mathking.kr/Contents/IMAGES/addtime.png width=25></span>'; 
		elseif($value['state']==='inprogress' && $role!=='student')$modifyquiz='<span onclick="deletequiz(\''.$attemptid.'\')"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span> <span onclick="addquiztime(\''.$attemptid.'\')"><img src=https://mathking.kr/Contents/IMAGES/addtime.png width=25></span>';
		elseif($role!=='student')$modifyquiz='<span onclick="deletequiz(\''.$attemptid.'\')"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span>';
		
		$quizstart=date("H:i",$value['timestart']);
		$timefinish=date("m/d | H:i",$value['timefinish']);
		if($value['modified']==='addtime')$timefinish=date("m/d",$value['timefinish']).' | <b style="color:blue;">'.$value['addtime'].'분+</b>';
		
		//$quizattempt= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid'");
		$maxgrade=$value['maxgrade'];
		if($value['review']==3)  // 워밍업 활동
			{
	  		$quizlist00.=' <hr> <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">퀴즈예약</a> | '.$quiztitle.' <input type="checkbox" name="checkAccount"    onClick="AddReview(11111,\''.$studentid.'\',\''.$value['id'].'\', this.checked)"/>';
			}
		elseif(strpos($quiztitle, '내신')!= false)   
			{
			//if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)  //<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>
				{
				if($quizgrade>89.99){$reducetime=$reducetime+30; $eventtext.='<tr><td>퀴즈성공 30분</td></tr>';}
				elseif($quizgrade>79.99){$reducetime=$reducetime+10; $eventtext.='<tr><td>퀴즈노력 10분</td></tr>';}
				$quizlist11.='<tr><td>'.$imgstatus.'&nbsp;'.$quizstart.' </td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td><td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td>'.$modifyquiz.'</td></tr>';
				$todayGrade=$todayGrade+$quizgrade;
				if($quizgrade>79.99)$ntodayquiz++;
				if($value['maxgrade']!=NULL)
					{
					$totalmaxgrade1=$totalmaxgrade1+$value['maxgrade'];
					$nmaxgrade1++;
					$totalquizgrade1=$totalquizgrade1+$quizgrade;
					}
				}
			else 
				{
				$quizlist12.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td>'.$modifyquiz.'</td></tr>';
				$todayGrade=$todayGrade+$quizgrade;
				$weekGrade=$weekGrade+$quizgrade;
				$nweekquizall++;
				if($quizgrade>79.99)$nweekquiz++;
				if($value['maxgrade']!=NULL)
					{
					$totalmaxgrade1=$totalmaxgrade1+$value['maxgrade'];
					$nmaxgrade1++;
					$totalquizgrade1=$totalquizgrade1+$quizgrade;
					}
				}
			}
		elseif($qnum>9)  //$todayGrade  $ntodayquiz  $weekGrade  $nweekquiz
			{
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
				{
				if($quizgrade>89.99){$reducetime=$reducetime+30; $eventtext.='<tr><td>퀴즈성공 30분 </td></tr> ';}
				elseif($quizgrade>79.99){$reducetime=$reducetime+10;$eventtext.='<tr><td>퀴즈노력 10분</td></tr>';}

				$quizlist21.= '<tr><td>'.$imgstatus.'</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td><td>'.$quizstart.'</td>  <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td>'.$modifyquiz.'</td></tr>';
				$todayGrade=$todayGrade+$quizgrade;
				$nweekquizall++;
				if($quizgrade>79.99)$ntodayquiz++;
				if($value['maxgrade']!=NULL)
					{
					$totalmaxgrade2=$totalmaxgrade2+$value['maxgrade'];
					$nmaxgrade2++;
					$totalquizgrade2=$totalquizgrade2+$quizgrade;
					}
				}
			else 
				{
				$quizlist22.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td>'.$modifyquiz.'</td></tr>';
				$weekGrade=$weekGrade+$quizgrade;
				$nweekquizall++;
				if($quizgrade>79.99)$nweekquiz++;
				if($value['maxgrade']!=NULL)
					{
					$totalmaxgrade2=$totalmaxgrade2+$value['maxgrade'];
					$nmaxgrade2++;
					$totalquizgrade2=$totalquizgrade2+$quizgrade;
					}
				}
			}
		else
			{
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)$quizlist31.='<tr><td>'.$imgstatus.'</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td><input type="checkbox" name="checkAccount"    onClick="AddReview(1111,\''.$studentid.'\',\''.$value['id'].'\', this.checked)"/></td><td>'.$modifyquiz.'</td></tr>';
			else $quizlist32.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td><td>'.$quizstart.'</td>  <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td><input type="checkbox" name="checkAccount"    onClick="AddReview(1111,\''.$studentid.'\',\''.$value['id'].'\', this.checked)"/></td><td>'.$modifyquiz.'</td></tr>';
			if($value['maxgrade']!=NULL)
				{
				$totalmaxgrade3=$totalmaxgrade3+$value['maxgrade'];
				$nmaxgrade3++;
				$totalquizgrade3=$totalquizgrade3+$quizgrade;
				}
			}
	}
if($ntodayquiz!=0)$todayqAve=$todayGrade/($ntodayquiz);
else $todayqAve=-1;
if($nweekquizall!=0)$weekqAve=$weekGrade/($nweekquizall);
else $weekqAve=-1; 
$ngrowth=$nweekquiz+$ntodayquiz;
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET todayquizave='$todayqAve', ngrowth='$ngrowth', weekquizave='$weekqAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
$amonthago=$timecreated-604800*4;

 
 
$reviewwb0.= '<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=remind" target=_blank">기억인출 훈련 (1개월 전)</a> &nbsp;';
	 
 
 

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND tlaststroke>'$timestart2' AND contentstype=2 AND  (active=1 OR status='flag' )  ORDER BY tlaststroke DESC LIMIT 300 ");

$nsynapse=0;
$sumSynapse=0;
$nreview=0;
$nreview2=0;
$ncomplete=0;
$nappraise=0;
$totalappraise=0;
$wboardScore=0;  
$nwboard=0;
$nrecovery=0;
$nask=0;
$nflag=0; 
$ntotal=$nright+$nwrong+$ngaveup;
$result1 = json_decode(json_encode($handwriting), True);
unset($value);
$wboardlist.= '<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></d></tr>';
foreach($result1 as $value) 
{
if($value['synapselevel']>0)
	{
	$nsynapse++;
	$sumSynapse=$sumSynapse+$value['synapselevel'];
	}
 
if($value['status']==='review')$nreview++;
if($value['status']==='complete')$ncomplete++;
if($value['depth']>2)$nrecovery++;
if($value['status']==='begin')$nask++;
$Q_id=$value['contentsid'];
// 화이트보드 평점 계산
if($value['timemodified']>$aweekAgo && $tbegin==604800 && $value['star'] > 0)
	{ 
	$wboardScore=$wboardScore+$value['star'];
	$nwboard++;
	}
$encryption_id=$value['wboardid'];
$nstroke=(int)($value['nstroke']);
$ave_stroke=round($nstroke/(($value['tlast']-$value['tfirst'])/60),1);
$contentstype=$value['contentstype'];
$nstep=$value['nstep'];
$status=$value['status'];
$contentstitle=$value['contentstitle'];
if($role!=='student')
	{
	$graderId=$value['userto'];
	$tname= $DB->get_record_sql("SELECT state,lesson,lastname, firstname FROM mdl_user WHERE id='$graderId' ");
	$grader=$tname->firstname.$tname->lastname;
	}
$contentsid=$value['contentsid'];
$cmid=$value['cmid']; 
$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" height=15>';
if($value['depth']==1)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
if($value['depth']==2)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
if($value['depth']==3)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
if($value['depth']==4)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
if($value['depth']==5)$resultValue='<img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';
if($value['synapselevel']!=NULL)$resultValue='지속성 ('.$value['synapselevel'].'%)';

$bstrate=$value['nfire']/($value['nmax']+0.01)*100;
if($bstrate>99)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666457.png';
elseif($bstrate>70)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666432.png';
elseif($bstrate>40)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666363.png';
elseif($bstrate>10)$bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666336.png';
else $bstrateimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666304.png';

if($value['appraise']!=NULL)
	{
	$nappraise++;
	$totalappraise=$totalappraise+$value['appraise'];
	}
$checkstatus='';
$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15></a>';
//if($value['flag']==1)$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/bookmark2.png" width=15>';
if($value['teacher_check']==1)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15></a>';
elseif($value['teacher_check']==2 && $value['nstep']==0)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15></a>'; 
elseif($value['teacher_check']==2 && $value['nstep']>0)$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 <img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1620732184001.png" width=15></a>'; 
if($value['student_check']==1)$checkstatus='checked'; 
$seethiswb='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$studentid.'_'.date("Y_m_d", $value['timemodified']);
if($value['tracking']==6){$resulttype='<a style="color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>';$resulttype2='<span style="color:red;">지난</span>'; }
elseif($value['tracking']==5){$resulttype='<a style="color:orange;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>';$resulttype2='<span style="color:orange;">지난</span>'; }
else {$resulttype='<a style="color:#0c0d0d;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$seethiswb.'"target="_blank">오늘</a>';$resulttype2='<span style="color:#0c0d0d;">지난</span>'; }
//if($value['contentstype']==2)
	{
	$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
	$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
	foreach($imageTags as $imageTag)
		{
    		$questionimg = $imageTag->getAttribute('src');
		$questionimg = str_replace(' ', '%20', $questionimg); 
		if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
		}
	$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
	//$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245210.png width=15></a>';  
	}
 
if($nstroke<3)
	{
	$ave_stroke='##';
	$nstroke='##';
	}
 
include("../whiteboard/status_icons.php");
 


 
 
$wbidbooster='booststep'.$contentsid.'_user'.$studentid;

$hidewb='';
if($value['status']==='review' && $value['hide']==0)$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
elseif($value['hide']==1 && $value['status']==='review' && $role!=='student' )$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width=20>';
elseif($role!=='student')$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a>';
if($value['status']==='flag' && $value['timemodified']>$adayAgo && $value['contentstitle']!=='incorrect' )
	{
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$contentsid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$nstroke=$bstep->nstroke;

	if($value['helptext']==='OK' || ($nstroke>15 && $bstep->nthink==0)){ $nthinktext='OK'; $imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 책갈피';  }
	elseif($bstep->nthink>=3)$nthinktext='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($bstep->nthink>=1)$nthinktext='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($bstep->nthink==0) $nthinktext='<b style="color:red;">check !</b>';

	if($status==='review' && $value['hide']==0 )  $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
	<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$studentid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
	<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$studentid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td>  '.$hidewb.' </td><td  sytle="font-weight: bold;"></td></tr> ';
	elseif($value['hide']==0 ) $wboardlist0.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$studentid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	$nflag++;
 
	}
elseif($value['timemodified']>$adayAgo && $value['status']!=='flag')  
	{
	if($status==='review' && $value['hide']==0 ) $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span> <span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;"> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td  sytle="font-weight: bold;"></td></tr> ';
	elseif($value['hide']==0) $wboardlist1.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	}
elseif($value['timemodified']<=$adayAgo && $value['status']!=='flag'  && $value['helptext']!=='해결') 
	{
	if($status==='review' && $value['hide']==0 )
		{
		if($value['status']==='review' && time()> $value['treview'])
			{
			$nreview2++;
			$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15">';  // 복습예약 활동문항
			$reviewwb0.= $imgstatus.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" >복습예약 </a> ('.$value['nreview'].'회)';
			}
		else
			$reviewwb.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
		}
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )  $reviewwb2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td></td></tr> ';
	elseif($value['hide']==0) $wboardlist2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	}
}
 

if($tbegin==604800)$wboardScoreAve=(INT)($wboardScore/$nwboard/5*100);
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;

$wtimestart=time()-86400*($nday+3);
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
$lastaction=time()-$Timelastaccess->maxtc;
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$inputtime=date("m/d", $weeklyGoal->timecreated);
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid'  AND pinned=1  ORDER BY id DESC LIMIT 1 ");


$lastday=$schedule->lastday;
$weekdays = array(
    'Sun' => '7',
    'Mon' => '1',
    'Tue' => '2',
    'Wed' => '3',
    'Thu' => '4',
    'Fri' => '5',
    'Sat' => '6'
);
 
$time2=time()-43200;  
$attendtoday = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page='studenttoday' ORDER BY id DESC LIMIT 1");
if($attendtoday->timecreated < time2)
	{
	$start='start'.$nday;
	$timestart=$schedule->$start;

	$todaybegin=strtotime($timestart);
	if($todaybegin<$timecreated && $USER->id==$studentid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$studentid','attendance','지각가능','$timecreated')");
	elseif($USER->id==$studentid) $DB->execute("INSERT INTO {abessi_missionlog} (userid,event,text,timecreated) VALUES('$studentid','attendance','ontime','$timecreated')");
	}

	$timeToday=time()-$todaybegin;
	
	$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7+$weeklyGoal->penalty/60;
	if($nday==1)  {if($timeToday/3600>$schedule->duration2)$timeToday=$schedule->duration2*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1;}
	if($nday==2) {if($timeToday/3600>$schedule->duration2)$timeToday=$schedule->duration2*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==3) {if($timeToday/3600>$schedule->duration3)$timeToday=$schedule->duration3*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==4) {if($timeToday/3600>$schedule->duration4)$timeToday=$schedule->duration4*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==5) {if($timeToday/3600>$schedule->duration5)$timeToday=$schedule->duration5*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==6) {if($timeToday/3600>$schedule->duration6)$timeToday=$schedule->duration6*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$timeToday/3600+$weeklyGoal->penalty/60;}
	if($nday==7) {if($timeToday/3600>$schedule->duration7)$timeToday=$schedule->duration7*3600; elseif($timeToday<0)$timeToday=0;  $untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$timeToday/3600+$weeklyGoal->penalty/60;}
 
$untiltoday=round($untiltoday,1);	
if($untiltoday>1000)$untiltoday=1;
//$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$studentid));

$ncompleteratio=$ncomplete/($nreview+$ncomplete)*100;
$nquestion=$indicator->nask/10*100;
$nreply=$indicator->nreply/10*100;

$timefilled=round($indicator->totaltime/($untiltoday+0.0001)*100,0);
$timefilled2=round($indicator->totaltime/($weektotal+0.0001)*100,0);
if($timefilled>20000)$timefilled=100;

$appraise_result=round($totalappraise/($nappraise*5+0.001)*100,0);
	 
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET appraise='$appraise_result', usedtime='$timefilled', wbscore='$wboardScoreAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
else $DB->execute("UPDATE {abessi_indicators} SET tinspect='$timecreated' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

$tbegin2=time()-604800;
$tbegin3=time()-86400;
$feedbacklist= $DB->get_records_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE  userid='$studentid'   AND (((type LIKE '개선요청' OR type LIKE '변경사항' OR type LIKE '활동평가') AND  timecreated > '$tbegin3') OR ((type LIKE '학습완료' OR type LIKE '복습예약' OR type LIKE '오답원인' OR type LIKE '학생응답')  AND (timemodified > '$tbegin3' || timecreated > '$tbegin3'))) ORDER BY id DESC LIMIT 10"); // 과목정보 가져오기

$fblist = json_decode(json_encode($feedbacklist), True);
unset($value);
$instruction='';
foreach($fblist as $value)
{
$feeder=$value['teacherid']; 
$feedername= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feeder' ");  // timecreated>'$tbegin2' AND
$fname=$feedername->firstname.$feedername->lastname;
$comment='<span style="color:#0394fc;">'.$value['feedback1'].''.$value['feedback2'].'  '.$value['feedback3'].'  '.$value['feedback4'].'  '.$value['feedback5'].'  '.$value['feedback6'].'  '.$value['feedback7'].'  '.$value['feedback8'].'  '.$value['feedback9'].'  '.$value['feedback10'].'</span>'; 	
 
if($value['type']==='개선요청' || $value['type']==='활동평가' )$instruction1.='<tr><td valign=top>▶ <a href="'.$value['context'].'?'.$value['url'].'"target="_blank">'.$comment.'</a></td><td></td><td valign=top><b> </td></tr>';
if($value['type']==='변경사항') $instruction2.='<tr><td valign=top># <a href="'.$value['context'].'?'.$value['url'].'"target="_blank">'.$comment.'</a></td><td></td><td valign=top><b> </td></tr>';
}  								
//$pschedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE complete=0 AND msntype=8 AND userid='$studentid'  ORDER BY id DESC LIMIT 1");
//$pscheduletext=$pschedule->text;

 
$weeklyGoalText='<span style="color:white;font-size=15;"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612786844001.png" width=40> 이번 주 목표가 설정되지 않았습니다. </span>';

if($weeklyGoal->id!=NULL)$weeklyGoalText=$weeklyGoal->text; 
if($weeklyGoal->penalty>0)$addtime='<b style="color:red;"> (보충 '.$weeklyGoal->penalty.'분) </b> ';
$drawing2=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='weekly' ORDER BY id DESC LIMIT 1 ");
$drawingid=$drawing2->wboardid;


$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
$summaryid=$summary->wboardid;

if($checkgoal->drilling==1)$alertimg='(<img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/exist.gif width=30>)';
else $alertimg='';
$goalid=$checkgoal->id; 
 
$goaldisplay= '<b style="font-size:16px;">'.$lastday.'</b>까지 목표가 "<span style="color:red;font-size:16px;">'.$weeklyGoal->text.'</span>" 이어서 오늘은 <span style="color:red;font-size:16px;">"'.$checkgoal->text.'"</span>(을)를 목표로 정진 중입니다. ';
$mindset=$checkgoal->mindset; 
$inspector=$checkgoal->teacherid;
$Confidence=$checkgoal->complete;
$whoInspect= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$inspector' ");

if($inspector!==NULL)$inspectorName=$whoInspect->firstname.$whoInspect->lastname;
else $inspectorName='귀가';

if($checkgoal->result/$checkgoal->pcomplete>1)$evaluateResult='<span sytle="color:green;">주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 수고하셨습니다 ! </span>';
elseif($checkgoal->result/$checkgoal->pcomplete>0.7)$evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다.  당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 학습속도를 향상시킬 수 있는 방법에 대해 고민해 보시기바랍니다.';
else $evaluateResult='주간목표의 '.$checkgoal->result.'%를 진행하였습니다. 당신이 사용한 시간은 '.$checkgoal->pcomplete.'%이므로 계획이 위태롭습니다. 선생님과 주간목표 수정에 대해 상의해 주세요 !';

if($checkgoal->submit==1)$text='<span  style="font-size:16;"> <b>※ 계획</b> : '.$checkgoal->text.'</span>';

$hide=$checkgoal->hide;
$inspectToday =$checkgoal->inspect;
$date=gmdate("h:i A", $checkgoal->timecreated+32400);
 
if($inspectToday==1)$status='checked';    
elseif($inspectToday==2)$status4='checked';    
elseif($inspectToday==3)$status5='checked';  
if($role!==student)$editgoal='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid.'&mode=CA">입력</a>';

$btnname='질문하기';$bgcolor='green';
if($timecreated-$checkgoal->alerttime<43200)
	{
	$btnname='대기중';
	$bgcolor='orange';
	}
$lastbreak= $DB->get_record_sql("SELECT timecreated FROM mdl_abessi_missionlog WHERE userid='$studentid' AND text LIKE '휴식종료' ORDER BY id DESC LIMIT 1 ");
$beforebreak=60-round(($timecreated-$lastbreak->timecreated)/60,0);
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
if($checkgoal->type==='오늘목표')$todolist='<tr style=" border-top:5px solid #88c2fc;border-bottom:5px solid #88c2fc;"><td></td><td><b style="font-size:20;">오늘목표</b></td><td><div><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summaryid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>&nbsp;&nbsp;'.$goaldisplay.' &nbsp;</div></td><td align=center></td><td>'.$date.'</td><td></td><td style="color:green;" width=7%>DMN휴식<input type="checkbox" name="checkAccount"  '.$status4.' onClick="Resttime(33,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/></td> <td style="color:green;" width=7%> 책공부<input type="checkbox" name="checkAccount"  '.$status5.' onClick="ChangeCheckBox(333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/>  </td><td width=7%><button   type="button"  style = "font-size:16;background-color:'.$bgcolor.';color:white;border:0;outline:0;" onClick="quickReply(313,\''.$studentid.'\',\''.$goalid.'\')" >'.$btnname.'</button></td></tr>';
elseif($checkgoal->type==='검사요청')$todolist='<tr style=" border-top:5px solid #88c2fc;border-bottom:5px solid #88c2fc;"><td></td><td><b style="font-size:16;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/goinghome.php?id='.$studentid.'&period=1" target="_blank">활동결과</a></b></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summaryid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=20></a>&nbsp;&nbsp;'.$goaldisplay.' </td><td align=center></td><td width=40%>'.$evaluateResult.'  ※ 질문수('.$checkgoal->ask.')</td><td>  귀가보류 <input type="checkbox" name="checkAccount"  '.$status5.' onClick="ContinueLearn(3333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/>  </td></tr>';
else $todolist='<tr style=" border-top:3px solid #88c2fc;"><td></td><td style="font-size:16;" align=center> 오늘 목표가 설정되지 않았습니다. <span style="color:white;font-size:16;">'.$editgoal.'</span></td><td></td></tr>';
$wgoalid=$wgoal->id;
$wstatus=''; 
if($wgoal->inspect==1)
	{
	$wstatus='checked';
	}
$wwhoInspect= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$wgoal->teacherid' ");
if($wgoal->teacherid!=NULL)$winspectorName=$wwhoInspect->firstname.$wwhoInspect->lastname;
else $winspectorName='의견';

 echo ' <div class="row"><div class="col-md-12"><div class="card"><div class="card-body">
<table  align=center  width=100% class="table table-head-bg-primary mt-8"><tbody>'.$todolist.'</tbody></table>';
// 귀가요청 표시부

if($timefilled<60)$bgtype='danger';
elseif($timefilled<80)$bgtype='warning';
else $bgtype='success';

if($timefilled<60)$bgtype='danger';
elseif($timefilled<80)$bgtype='warning';
else $bgtype='success';

if($timefilled2<60)$bgtype2='danger';
elseif($timefilled2<80)$bgtype2='warning';
else $bgtype2='success';

$stateColor1='primary'; 
$stateColor2='primary'; 
$stateColor3='primary'; 
if($username->state==1)$stateColor1='Default'; 
if($username->state==2)$stateColor2='Default'; 
if($username->state==0)$stateColor3='Default'; 
 
if($timefilled>=100)$result_time='충분히';
elseif($timefilled>=80)$result_time='대부분';
else  $result_time='부족함';

if($indicator->nask>=5)$result_question='충분히';
elseif($indicator->nask>=1)$result_question='필요한 만큼';
else $result_question='부족함';
 
//$result_review='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today"target="_blank"><b style="color:white;">습관분석 <img src=https://mathkin.kgr/moodle/local/augmented_teacher/students/improve.png width=50></b></a>';
 
$check_reply=$nwrong+$ngaveup-$ncomplete-$nreview;
if($check_reply<=0)$result_reply='완료';
else $result_reply='미완료';

$NNnask=$indicator->nask;
$NNreview=$nreview;
$Ncheckreply=$check_reply;
 
$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$studentid' AND msntype=3 ORDER BY id DESC LIMIT 1 ");
$examGrade=$mission->grade;
$examDday=strtotime($mission->startdate);
 
$diff =$examDday-time();
 
$leftDays =(INT)($diff/86400)+1;
$leftRate=(INT)($leftDays/60*100);
  
if($leftRate<30)$bgtypeDday='danger';
elseif($leftRate<60)$bgtypeDday='warning';
else $bgtypeDday='success';

 
$totaltime=$indicator->totaltime;

$topicrate=round($indicator->topictime/$totaltime*100,0);
$solrate=round(($indicator->soltime+$indicator->quiztime)/$totaltime*100,0);
$fixrate=round($indicator->fixtime/$totaltime*100,0);
$fixexamtime=round($indicator->fixexamtime/$totaltime*100,0);
$memorytime=round($indicator->memorytime/$totaltime*100,0);

 
$totalfixrate=$fixrate+$fixexamtime+$memorytime;


$topicrate2=30;
$solrate2=40;
$fixrate2=30;

$stepquestion= $DB->get_records_sql("SELECT * FROM mdl_abessi_questionstamp WHERE userid='$studentid' AND (status LIKE '질문' || status LIKE '답변')  AND timemodified >'$halfdayago'  ORDER BY id DESC ");

$qstamps = json_decode(json_encode($stepquestion), True);
unset($value);
 
foreach($qstamps as $value)
{
$qstatus=$value['status']; $qwbid=$value['wboardid'];  $qplayindex=$value['playindex']; $qgid=$value['gid']; $eventtime=round(($timecreated-$value['timemodified'])/60,0);
$qlist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$qwbid.'&gid='.$qgid.'&playindex='.$qplayindex.'&playstate=0&sketchstate=0&speed=3&mode=qstamp&studentid='.$studentid.'"target="_blank">'.$qwbid.'</a>('.$eventtime.'분)</td><td>'.$qstatus.'</td></tr>';
}
// 활동 설계부
$todayplan='<table><tr><td><b>▶ 질의응답</td><td></td></TD></tr>'.$qlist.'</table><br>';
//ㄹㅇㅁㄹ

if($role!=='student')$teacherButton1=' <button   type="button"   id="alert_addtime" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >즉석보강 입력</button>  </b>';
$synapsePower=$sumSynapse/(100*$nsynapse)*100;
$goalprogress=' 
<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">분기목표 성취도</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
$siprogress='<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">기억 회복력</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
$instructionToday='<div class="container" ><table width=100%><tr><td><b style="color:#216feb;">▶ 몰입피드백 </b> &nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'"target="_blank"> ('.$mcstatus.' | '.$fbtime3.' )</a></td> </tr><tr><td><br><b><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/drilling.png width=20> 오늘집중</b> : '.$lastcfeedback1.'<hr> &nbsp; <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1667730907.png width=25> &nbsp; '.$lastcfeedback3.'<hr style="border: solid 2px skyblue;"> <b>추천</b> : '.$lastcfeedback2.'</td></tr></table>';

if($nday>=$weekdays[date('D')] && $checkgoal->comment==NULL)$placeholder='새로운 주간 목표와 다음 목표를 입력해 주세요';
elseif($checkgoal->comment==NULL)$placeholder='다음 시간 목표를 입력해 주세요';
else $placeholder='다음 목표 : '.$checkgoal->comment;


echo '<table  width=100% valign=top><tr><td width=35% valign=top>'.$instructionToday.'</td><td width=2%></td>	<td width=33% valign=top><b>▶ 활동시간 데이터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;학교시험 D -'.$leftDays.'일 <span class="text-muted">'.$mission->grade.'점 목표</span>('.$mission->startdate.')</b> 	<br>					 
<div class="card-body"><div class="row"><div class="col-md-12"><div class="progress-card"> 
<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">오늘까지 (개념 '.round(($untiltoday-$indicator->quiztime)/$untiltoday*100,0).'%)&nbsp;<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width=15></a> </span>
<span class="text-muted fw-bold">총 '.$untiltoday.'시간 '.$addtime.'</span></div><div class="progress"><div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar" style="width: '.$timefilled.'%" aria-valuenow="'.$timefilled.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.round($timefilled,1).'%"></div>
</div></div></div><div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">이번 주 &nbsp;<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width=15></a> </span>
<span class="text-muted fw-bold">총 '.round($weektotal,1).'시간 </span></div><div class="progress"><div class="progress-bar progress-bar-striped bg-'.$bgtype2.'" role="progressbar" style="width: '.$timefilled2.'%" aria-valuenow="'.$timefilled2.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.round($timefilled2,1).'%"></div>
</div></div></div></div></div>
<hr><b>▶ 활동 유형별 시간분포 </b>
<table width=100%><tr><td><div class="demo"><div class="progress-card"><div class="container" width=100% ><div class="progress" style="height:0px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate2.'%;">  </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate2.'%;">   </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$fixrate2.'%;">     </div>  </div>
</div> </div>
<div class="container" width=100% ><div class="progress" style="height:15px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate2.'%;"> 개념 </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate2.'%;">  풀이 </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$fixrate2.'%;">   오답  </div>  </div>
</div> </div> 

<div class="container" width=100% ><div class="progress" style="height:30px;" > 
    <div class="progress-bar bg-info" role="progressbar" style="width:'.$topicrate.'%;">  '.$topicrate.'% </div>
    <div class="progress-bar bg-warning" role="progressbar" style="width:'.$solrate.'%;">   '.$solrate.'% </div>
    <div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$fixrate.'%  </div> 
<div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$fixexamtime.'%  </div>
<div class="progress-bar bg-primary" role="progressbar" style="width:'.$totalfixrate.'%;">    '.$memorytime.'%  </div>
 </div></div> </div></div> </td> </tr></table>
  
<td width=2%><td width=30% valign=top>'.$todayplan.' <hr><table width=100%><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/logicalstairway.php?id='.$studentid.'&tb=2419200"target="_blank"><b style="color:black;">▶ 보충학습 '.$alertimg.' </b></a></td><td>'.$teacherButton1.'</td></tr></table><hr><table width=100% valign=top>'.$instruction1.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr>'.$instruction2.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr>'.$breakinfo.'</table><br><table><tr><td><b>▶ 귀가시간 : '.$tcomplete.'</b> | '.$tleft.'</td></tr></table></td></td><td width=2%></td>
</tr></table><br> '; 

$pcomplete=round($untiltoday/$weektotal*100,0);

$recentlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason  LIKE 'addperiod'  ORDER by id DESC LIMIT 1 " );
if($recentlog->id!=NULL)$passedhours=round($recentlog->tamount*($timecreated-$recentlog->doriginal)/($recentlog->dchanged-$recentlog->doriginal),1);

$attendlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason NOT LIKE 'addperiod' ORDER BY id DESC LIMIT 1  " );
$doriginal=date("Y-m-d",$attendlog->doriginal); $dchanged=date("Y-m-d",$attendlog->dchanged);

$tamounttotal=$attendlog->tupdate+$passedhours;
$attendancetext='&nbsp; &nbsp; &nbsp; &nbsp; <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;<a style="color:green; font-size:14pt" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4">예기치 못한 휴강상황을 위하여 + 5시간 이상을 권합니다.</a> ';
if($tamounttotal<=-5 && $attendlog->id !=NULL )$attendancetext='&nbsp; <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;<a style="color:red; font-size:20pt" href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4">보강시간을 정해주세요 !</a> <img src="https://mathking.kr/Contents/IMAGES/exist.gif" width=60>';
$todayrecord='<table width=100%><tr style="background-color:#ccffff;"> <td width=3%></td>  <td width=10% align=left style="font-size:12pt">'.$attendlog->type.'</td><td width=10%  align=left style="font-size:12pt">'.$attendlog->reason.'</td><td  width=10% align=left style="font-size:12pt">계획  '.$doriginal.'</td><td width=10%  align=left style="font-size:12pt">변경 '.$dchanged.'</td>  <td align=left>'.$attendancetext.'</td><td align=left style="font-size:12pt"><table>'.$attendlog->text.'</table></td></tr></table>';

 
$Rach='rate'.$checkgoal->result;
$$Rach='selected';

echo $todayrecord.'
<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center >
<td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><b style="color:black;">귀가검사 &nbsp;&nbsp;</b></td></td>
<td style="width: 15%;"><div class="select2-input"><select id="basic1" name="basic" class="form-control" > <option value="10" '.$rate10.'>주간목표 10%완료</option> <option value="20" '.$rate20.'>주간목표 20%완료</option> <option value="30" '.$rate30.'>주간목표 30%완료</option> <option value="40" '.$rate40.'>주간목표 40%완료</option> <option value="50" '.$rate50.'>주간목표 50%완료</option> <option value="60" '.$rate60.'>주간목표 60%완료</option> <option value="70" '.$rate70.'>주간목표 70%완료</option> <option value="80" '.$rate80.'>주간목표 80%완료</option> <option value="90" '.$rate90.'>주간목표 90%완료</option> <option value="100" '.$rate100.'>주간목표 100%완료</option></select></div></td>
<td style="width: 2%;"></td>'; 

if($checkgoal->type==='검사요청') echo '<td style="width: 25%;height:20px;"><div><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'" ></div></td>  <td style="width:5%;font-size: 20px; "><button id="clicksubmit" type="image" onclick="submittoday(21,'.$studentid.','.$pcomplete.',$(\'#basic1\').val(),$(\'#basic3\').val(),$(\'#basic5\').val(),$(\'#squareInput\').val())">제출</button></td><td style="font-size: 20px;width: 10%; text-align:center;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today"><img src=https://mathking.kr/Contents/IMAGES/improve.png width=80></a></td> <td style="text-align:center;font-size: 20px; width: 10%;"><div style="text-align:center;font-size: 20px; " class="tooltip2">보강차감 (총 : '.$reducetime.' 분) 가능 <span class="tooltiptext2"><table style="" align=center>'.$eventtext.'</table></span></div>
<button type="image" onclick="updatetime(93,'.$studentid.','.$reducetime.','.$tamounttotal.')">적용</button></td></tr></table></div></div>  <br>';
else echo '<td style="height:20px;"><div><input type="text" class="form-control input-square" id="squareInput" name="squareInput"  placeholder="'.$placeholder.'" ></div></td> <td width=2%></td> <td style="width:10%;font-size: 20px; "><button id="clicksubmit" type="image" onclick="submittoday(21,'.$studentid.','.$pcomplete.',$(\'#basic1\').val(),$(\'#basic3\').val(),$(\'#basic5\').val(),$(\'#squareInput\').val()) ">제출</button></td> <td style="font-size: 20px;width: 10%; text-align:center;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today"><img src=https://mathking.kr/Contents/IMAGES/improve.png width=80></a></td><td style="text-align:center;font-size: 20px; width: 10%;"><div style="text-align:center;font-size: 20px; " class="tooltip2">보강차감 (총 : '.$reducetime.' 분) 가능<span class="tooltiptext2"><table style="" align=center>'.$eventtext.'</table></span></div></td> </tr></table></div></div>  <br>';

// 퀴즈 및 화이트보드 출력부
$recoveryrate=$nrecovery/($ncomplete+$nreview)*100;
 
 
$progresstext='총 '.round($predict->totaltime/3600,1).'시간 ';
if($predict->prob1!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time1.'">'.$predict->prob1.'%/'.round(($predict->time1-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text1.'</td></tr></table></span></div>';
if($predict->prob2!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time2.'">'.$predict->prob2.'%/'.round(($predict->time2-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text2.'</td></tr></table></span></div>';
if($predict->prob3!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time3.'">'.$predict->prob3.'%/'.round(($predict->time3-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text3.'</td></tr></table></span></div>';
if($predict->prob4!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time4.'">'.$predict->prob4.'%/'.round(($predict->time4-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text4.'</td></tr></table></span></div>';
if($predict->prob5!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time5.'">'.$predict->prob5.'%/'.round(($predict->time5-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text5.'</td></tr></table></span></div>';
if($predict->prob6!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time6.'">'.$predict->prob6.'%/'.round(($predict->time6-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text6.'</td></tr></table></span></div>';
if($predict->prob7!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time7.'">'.$predict->prob7.'%/'.round(($predict->time7-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text7.'</td></tr></table></span></div>';
if($predict->prob8!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time8.'">'.$predict->prob8.'%/'.round(($predict->time8-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text8.'</td></tr></table></span></div>';
if($predict->prob9!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time9.'">'.$predict->prob9.'%/'.round(($predict->time9-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text9.'</td></tr></table></span></div>';
if($predict->prob10!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$predict->tbegin.'&tend='.$predict->time10.'">'.$predict->prob10.'%/'.round(($predict->time10-$predict->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$predict->text10.'</td></tr></table></span></div>';
  
  

	
	
  
echo '<table align=center><tr><td><img src=https://mathking.kr/Contents/IMAGES/agamotto.gif width=50>&nbsp;&nbsp;&nbsp;</td><td>'.$progresstext.' (<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'">history</a>)</td></tr></table><br><table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>시험결과 및 오답노트 현황</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;오답노트 후 자신감이 생긴 문항이 '.round($recoveryrate,0).'% 입니다.</td></tr></table> <br> <br>  
<table align=center valign=top width=100%><thead> 
<tr>
<th scope="col"><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200>오늘 테스트 결과 </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800>최근 1주일</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=2592000>최근 1개월</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=7776000>최근 3개월</a></th>
<th scope="col" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">오답 '.($nwrong+$ngaveup).' &nbsp; &nbsp; 예약 '.$nreview.' | 완료 '.$ncomplete.'  &nbsp; &nbsp;<b style="color:red;"> 도전 '.$appraise_result.'</b>  &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays=7"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624791079001.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=sol"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656132615.png width=25></a>
 &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1655957315.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=ltm"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1657015275.png width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replaycjn.php?studentid='.$studentid.'"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658012742.png width=25></a>&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&studentid='.$studentid.'&mode=retry"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658042078.png width=25></a></th>
</tr><tr ><td  style=" vertical-align: top;"><hr><b>준비학습</b> <br><br>'.$reviewwb0.$quizlist00.'<hr><b>내신테스트</b>.....분석'.$nmaxgrade1.'.....'.round(($totalmaxgrade1-$totalquizgrade1)/(100*$nmaxgrade1+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist11.$quizlist12.'</table><hr><b>표준테스트</b>.....분석'.$nmaxgrade2.'.....'.round(($totalmaxgrade2-$totalquizgrade2)/(100*$nmaxgrade2+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist21.$quizlist22.'</table><hr><b>인지촉진</b>.....분석'.$nmaxgrade3.'.....'.round(($totalmaxgrade3-$totalquizgrade3)/(100*$nmaxgrade3+0.01)*100,0).'% 향상 <br><br><table>'.$quizlist31.$quizlist32.'</table></td>  <td  style=" vertical-align: top; "><hr><table align=center width=90%><tr><td>고민지점 점검 </td><td> 3곳 이상인 경우 삭제 후 다시 풀기</td> </tr></table><hr><table  style="">'.$wboardlist0.' <tr><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$reviewwb.$reviewwb2.' <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'</table></td></tr></tbody></table>
<br><hr><br>';

echo '
	<script>

	function showList(Studentid)
		{
		Swal.fire({
		  position:"top",showCloseButton: true,width:900,
		  html:  \'<iframe style="border: 1px none; z-index:2; width:900; height:600;  margin-left: -50px; margin-top: -10px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/cognitiveRecent.php?userid=\'+Studentid+\'&tb=43200"></iframe>\',
		  showConfirmButton: false,
		        })
		}	
	function showWboard(Wbid)
		{
		Swal.fire({
		backdrop: false,position:"top-left",showCloseButton: true,width:800,
		  html:
		    \'<iframe style="border: 1px none; z-index:2; width:1200; height:900;  margin-left: -100px; margin-top: -130px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_review.php?id=\'+Wbid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
	</script>';

if(  $role!=='student' && ($timecreated - $predict->timemodified>1800 && $tcomplete0 - $timecreated > 0 ))
	{
	echo '<script>PredictResult();</script>';
	}


if($role!=='student')
{
console.log('');


echo '
<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>코스 및 스케줄 정보 (선생님용)</b> </td></tr></table>
<table  style="" width="100%" valign="top"><tr><th valign="top" width="70%">';
include("schedule_embed.php"); 

echo '</th><th valign="top"  width="30%">';
include("index_embed.php"); 
echo '</th></tr></table>';

}
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays=1'; 
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today';																	   
echo '</div> </div></div></div></div></div></div>';
	
include("quicksidebar.php");
echo '<script>
function deletequiz(Attemptid)
	{
		swal({
					title: \'시도된 퀴즈를 삭제하시겠습니까 ?\',
					text: "원하지 않으시면 취소 버튼을 눌러주세요",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'취소\',
							className: \'btn btn-danger\'
						}      			

					}
		}).then((willDelete) => {
					if (willDelete) {
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"eventid":\'300\',
						"attemptid":Attemptid,
					 		},
						 });
					setTimeout(function() {location.reload(); },100);
					} else {
					swal("취소되었습니다.", {buttons: false,timer: 500});
					}
				});	 				 
	}
function addquiztime(Attemptid)
	{		 
 
			var text1="-30";
			var text2="-20";
			var text3="-10";
			var text4="-5";
			var text5="+5";
			var text6="+10";
			var text7="+20";
			var text8="+30";
			var text9="입력";

			swal("퀴즈 시간변경",  "응시시간을 적절히 늘리거나 줄이면 집중력이 향상됩니다.",{
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: text5,
			      value: "catch5",className : \'btn btn-success\'
			    },
			    catch6: {
			      text: text6,
			      value: "catch6",className : \'btn btn-success\'
			    },
			    catch7: {
			      text: text7,
			      value: "catch7",className : \'btn btn-success\'
			    },
			    catch8: {
				text: text8,
				value: "catch8",className : \'btn btn-success\'
				  },
				catch9: {
				text: text9,
				value: "catch9",className : \'btn btn-secondary\'
				  },
			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-alert\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
		 
 			   case "catch1":
				swal("","퀴즈종료 시간이 " + text1+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text1,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
				swal("","퀴즈종료 시간이 " + text2+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text2,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
				swal("","퀴즈종료 시간이 " + text3+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text3,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch4":
				swal("","퀴즈종료 시간이 " + text4+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
						"eventid":\'301\',
						"inputtext":text4,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
				 case "catch5":
					swal("","퀴즈종료 시간이 " + text5+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text5,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch6":
					swal("","퀴즈종료 시간이 " + text6+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text6,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
				 case "catch7":
					swal("","퀴즈종료 시간이 " + text7+"분 연장되었습니다.", "success");
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						 data : {
							"eventid":\'301\',
							"inputtext":text7,	
							"attemptid":Attemptid,	
						},
						success:function(data){
						 }
						 })
					location.reload();
					 break;
				case "catch8":
					swal("","퀴즈종료 시간이 " + text8+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
					 data : {
						"eventid":\'301\',
						"inputtext":text8,	
						"attemptid":Attemptid,	
					},
					success:function(data){
					 }
					 })
				location.reload();
				 break;
 			   case "catch9":
				swal({
					title: \'퀴즈 추가시간을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "시간입력 (분)",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("","퀴즈종료 시간이 " + Inputtext+"분 연장되었습니다.", "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'301\',
					"inputtext":Inputtext,	
					"attemptid":Attemptid,				 
					},
					success:function(data){
					 }
					 })
				});	 
				location.reload();
				break;
 			   
			}
		})
	}
function updatetime(Eventid,Userid,Selecttime,Totaltime)
	{   
	if(Totaltime>=5)
		{
		alert("차감 가능한 보강시간이 없습니다. 대신 10분 일찍 귀가 가능합니다." );
		}
	else
		{
		var Inputtext= \''.$eventtext.'\';
		alert("총" + Selecttime + "분이 보강시간에서 차감됩니다. 내역은 다음과 같습니다. (" + Inputtext + ")" );
		swal("적용되었습니다.", {buttons: false,timer: 1000});
		        $.ajax({
		            url:"database.php",
				type: "POST",
		            dataType:"json",
 			data : {
			  "eventid":Eventid,
			  "userid":Userid,
			  "selecttime":Selecttime,
			  "inputtext":Inputtext,		 
		               },
		            success:function(data){
		
				             }
		        })
   		}
	}


function ChangeCheckSteps(Eventid, Userid, Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	swal({title: \'안전하게 전달하였습니다.\',});	
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
	data : {
	 "eventid":Eventid,
	"userid":Userid,       
	"checkimsi":checkimsi,
	},
	})
	location.reload();
	 		 				 
	}
function submittoday(Eventid,Userid,Pcomplete,Confident,Ask,Review,Inputtext)
	{ 
	var Timefilled= \''.$timefilled.'\';
	var Nask= \''.$NNnask.'\';
	var Nreview= \''.$NNreview.'\';
	var Check_reply= \''.$Ncheckreply.'\'; 
	
	 
	if(Inputtext=="")swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
	else 
		{  
		swal({		 
		title: "시간 : " + Timefilled +  "% | 질문 : " + Nask +  "개 | 응답 : " + Check_reply + "개",
			type: \'warning\',
			buttons:{
				confirm: {
					text : \'제출하기\',
					className : \'btn btn-primary\'
				},
				cancel: {
					visible: true,
					text : \'취소\',
					className: \'btn btn-danger\'
				}      			

			}
		}).then((willDelete) => {
					if (willDelete) {
							$.ajax({
								url:"database.php",
								type: "POST",
								dataType:"json",
								data : {"userid":Userid,
								"eventid":Eventid,
								"pcomplete":Pcomplete,
								"confident":Confident,
								"ask":Ask,
								"review":Review,
								"inputtext":Inputtext,
								},
								success:function(data){}
							})
						location.reload(); 
						} else
				 		{
						swal("취소되었습니다.", {buttons: false,timer: 500});
						}
				});	 
		}

	}




		function RubricCheckBox(Eventid,Userid,Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		 
		   $.ajax({
		        url: "checkrubric.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
    		                "checkimsi":checkimsi,               
 		             },
		            success:function(data){
			            }
		        });
		}	 

		function ChangeCheckBox(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
		    var Nextgoal=\''.$checkgoal->comment.'\';
		    if(Eventid==3 && Nextgoal=="" && Checkvalue==true)
				{
				swal("잠깐 !","다음 시간 활동목표를 미리 입력후 귀가검사를 제출해 주세요 !", {buttons: false,timer: 5000});
				location.reload(); 
				}
		    else
				{
				if(Checkvalue==true){
					checkimsi = 1;
					}
					swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
					});
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
									"goalid":Goalid,
									"checkimsi":checkimsi,
									"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	 
				} 
		 
		}
		function ContinueLearn(Eventid,Userid, Goalid,Checkvalue){
		    var checkimsi = 0;
			if(Checkvalue==true){
				checkimsi = 1;
				}
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"checkimsi":checkimsi,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
				swal("귀가검사 결과 보충활동이 발견되었습니다.", {buttons: false,timer: 3000});
				 location.reload(); 
		 
		}
		function quickReply(Eventid,Userid,Goalid){
		 
					$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
						data : {
						"userid":Userid,       
						"goalid":Goalid,
						"eventid":Eventid,
									 
						},
						success:function(data){
						 }
					})	
					
					var Alerttime= \''.$checkgoal->alerttime.'\';
					if(Alerttime==0)swal("질문이 전달되었습니다.","기다리는 동안 후속 학습을 진행해 주세요.", {buttons: false,timer: 3000});
					else swal("피드백을 시작합니다.","충분히 이해가 될 수 있도록 유연하게 대화해 보세요", {buttons: false,timer: 3000});
					location.reload(); 
		 
		}
		function Resttime(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    var Timeleft= \''.$beforebreak.'\';
		    if(Checkvalue==true)
				{
				checkimsi = 1;
				if(Timeleft<0)
					{
					Swal.fire({
					backdrop: true,position:"top-center",width:1200,
					  customClass: {
									container: "my-background-color"
								   },
					html:
					\'<table align=center ><tr><td align=center><br><h5><b>정보입력이 멈춘 상태의 DMN 휴식</b>을 취하면 공부가 가속화됩니다 ! (<a href="https://brunch.co.kr/@kissfmdj/1"target="_blank">자세히</a>) </h5><br></td></tr><tr><td><iframe style="border: 1px none; z-index:2; width:60vw;height:50vh; margin-left: -30px;margin-top: 0px;"   src="https://e.ggtimer.com/10minutes" ></iframe></td></tr></table>\',
					})
					
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
							data : {
							"userid":Userid,       
										"goalid":Goalid,
										"checkimsi":checkimsi,
										"eventid":Eventid,
										 
							},
							success:function(data){
							 }
						})	 
				
					}
				else
					{
					swal("힘내세요 ! " + Timeleft + "분 더 공부하시면 휴식을 취하실 수 있습니다.", {buttons: false,timer: 3000});
					setTimeout(function() {location.reload(); },3000);
					}
					
	 			}
	 		else
				{
				swal("처리되었습니다.", {
						buttons: false,
						timer: 500,
						});
				if(Timeleft<0)
						{
						$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":Eventid,
											 
								},
								success:function(data){
								 }
							})
						}
					else
							{
							
							$.ajax({
								url:"check.php",
								type: "POST",
								dataType:"json",
								data : {
								"userid":Userid,       
								"goalid":Goalid,
								"checkimsi":checkimsi,
								"eventid":\'331\',
											 
								},
								success:function(data){
								 }
							})	
						}
				}
				
		}
		function ChangeCheckBoxWeek(Eventid,Userid, Goalid,Checkvalue)
			{
		    var checkimsi = 0;
		    if(Checkvalue==true)
				{
		        checkimsi = 1;
				swal({
					title: \'한 주간 공부과정에 대한 한줄 평을 남겨주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
  					swal("", "입력된 내용 : " + Inputtext, "success");
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"userid":Userid,       
		                		"goalid":Goalid,
		                		"checkimsi":checkimsi,
		                 		"eventid":Eventid,
		                 		"inputtext":Inputtext,
					},
					success:function(data){
					 }
					 })
				 
				}
				);
			  }
		}
		function ChangeCheckBox2(Eventid,Userid, Wboardid, Checkvalue){
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "check.php",
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
		function AddReview(Eventid,Userid,Attemptid, Checkvalue){
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
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });
		}		
		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});

		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );
	</script>
';


?>
<html>
 
<style>
.my-background-color .swal2-container {
  background-color: black;
}

.feel {
  margin: 0px 5px;
  background-color: white;
  height:30px;
}
</style>
</html>
