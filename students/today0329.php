<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
global $DB, $USER;
include("navbar.php");
  
$tbegin= $_GET["tb"]; 
$maxtime=time()-$tbegin; 

$timecreated=time();

// get mission list
$timestart2=time()-$tbegin;
$adayAgo=time()-43200;
$aweekAgo=time()-604800;
$timestart3=time()-86400*14;
 
 
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
			$imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
			}
		elseif($quizgrade>69.99)
			{
			$imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
			}
		else $imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
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

		if($value['state']==='inprogress' && $role==='student')$modifyquiz='<span onclick="addquiztime(\''.$attemptid.'\')"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/addtime.png width=25></span>'; 
		elseif($value['state']==='inprogress' && $role!=='student')$modifyquiz='<span onclick="deletequiz(\''.$attemptid.'\')"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span> <span onclick="addquiztime(\''.$attemptid.'\')"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/addtime.png width=25></span>';
		elseif($role!=='student')$modifyquiz='<span onclick="deletequiz(\''.$attemptid.'\')"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span>';
		
		$quizstart=date("H:i",$value['timestart']);
		$timefinish=date("m/d | H:i",$value['timefinish']);
		if($value['modified']==='addtime')$timefinish=date("m/d",$value['timefinish']).' | <b style="color:blue;">'.$value['addtime'].'분+</b>';
 
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
					$gptprep.=$quiztitle.'('.$quizgrade.'점.) &';
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
					$gptprep.=$quiztitle.'('.$quizgrade.'점.) &';
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
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
				{
				$quizlist31.='<tr><td>'.$imgstatus.'</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> <td><input type="checkbox" name="checkAccount"    onClick="AddReview(1111,\''.$studentid.'\',\''.$value['id'].'\', this.checked)"/></td><td>'.$modifyquiz.'</td></tr>';
				$gptprep.=$quiztitle.'('.$quizgrade.'점.) &';		
				}
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

$amonthago=$timecreated-604800*4;

 
 
//$reviewwb0.= '<a href="https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&mode=remind" target=_blank">기억인출 훈련 (1개월 전)</a> &nbsp;';
	 
 
 

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND  status NOT LIKE 'attempt'  AND tlaststroke>'$timestart2' AND contentstype=2 AND  (active=1 OR status='flag')  ORDER BY tlaststroke DESC LIMIT 100 ");

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
//$wboardlist.= '<tr><td><hr></d><td><hr></d><td><hr></d><td><hr></d></tr>';
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
 
$contentsid=$value['contentsid'];
$cmid=$value['cmid']; 
if($value['status']!=='complete' && $value['status']!=='review')$resultValue='<b style="color:orange;">검토 중입니다.</b>';
elseif($value['teacher_check']==2)$resultValue='검토완료';
else $resultValue='<span style="color:orange;">검토 중입니다.</span>';
 
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
$fixhistory='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank">노트 </a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$encryption_id.'&contentsid='.$contentsid.'&contentstype=2" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=15></a>';
 
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
	$questiontext='<img loading="lazy" src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 
	}
 
if($nstroke<3)
	{
	$ave_stroke='##';
	$nstroke='##';
	}
 
include("../whiteboard/status_icons.php");
if($status==='exam' && $timecreated-$value['timereviewed']>600)$imgstatus='<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/exam2.png" width="15"><span style="color: rgb(33, 33, 233);"> 시작</span>';
elseif($status==='sequence')$imgstatus='<img src="https://mathking.kr/Contents/IMAGES/sequence.png" width="15"><span style="color: rgb(33, 33, 233);"> 순서</span>';
elseif($status==='evidence' || $status==='modify' || $status==='explain' || $status==='direct')$imgstatus='<img src="https://mathking.kr/Contents/IMAGES/logic.png" width="15"><span style="color: rgb(33, 33, 233);"> 논리</span>';
elseif($status==='fixsol')$imgstatus='<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/fixsol.png" width="15"><span style="color: rgb(33, 33, 233);"> 다시</span>';
$wbidbooster='booststep'.$contentsid.'_user'.$studentid;

$hidewb='';
if($value['status']==='review' && $value['hide']==0)$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
elseif($value['hide']==1 && $value['status']==='review' && $role!=='student' )$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width=20>';
elseif($role!=='student')$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a>';

if ($value['status'] === 'begin') {
    $hidewb = '<input type="checkbox" class="beginCheckbox" name="checkAccount" onClick="ChangeCheckBox2(111,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/>';
} else {
    // 기존 로직에 따른 다른 체크박스 처리...
    $hidewb = '<input type="checkbox" name="checkAccount" onClick="ChangeCheckBox2(111,\'' . $studentid . '\',\'' . $encryption_id . '\', this.checked)"/>';
}



if($value['status']==='flag' && $value['timemodified']>$adayAgo && $value['contentstitle']!=='incorrect' )
	{
	/*
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$contentsid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$nstroke=$bstep->nstroke;

	if($value['helptext']==='OK' || ($nstroke>15 && $bstep->nthink==0)){ $nthinktext='OK'; $imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 책갈피';  }
	elseif($bstep->nthink>=3)$nthinktext='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($bstep->nthink>=1)$nthinktext='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($bstep->nthink==0) $nthinktext='<b style="color:red;">check !</b>';
	*/
	if($status==='review' && $value['hide']==0 )  $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"><input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
	<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$studentid.'&mode=addexp"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
	<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$studentid.'&mode=addexp"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td>  '.$hidewb.' </td><td  sytle="font-weight: bold;"></td></tr> ';
	elseif($value['hide']==0 ) $wboardlist0.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div>('.$nstroke.'획)</td><td> <span  onClick="showWboard(\''.$encryption_id.'\')"><img loading="lazy" style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652666304.png" width=15></span> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	$nflag++;
 
	}
elseif($value['timemodified']>$adayAgo && $value['status']!=='flag')  
	{
	if($status==='review' && $value['hide']==0 ) $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;  <div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span> <span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;"> '.$resultValue.'   </td><td> '.$hidewb.'  </td></tr> ';
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td  sytle="font-weight: bold;"></td></tr> ';
	elseif($value['hide']==0) $wboardlist1.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">  '.$resultValue.'  </td><td> '.$hidewb.'  </td></tr> ';
	}
elseif($value['timemodified']<=$adayAgo && $value['status']!=='flag'  && $value['helptext']!=='해결') 
	{
	if($status==='review' && $value['hide']==0 )
		{
		if($value['status']==='review' && time()> $value['treview'])
			{
			$nreview2++;
			$imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15">';  // 복습예약 활동문항
			$reviewwb0.= $imgstatus.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" >복습예약 </a> ('.$value['nreview'].'회)';
			}
		else
			$reviewwb.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>  '.$resultValue.' </td><td> '.$hidewb.'  </td></tr> ';
		}
	elseif($status==='review' && $value['hide']==1 && $role!=='student' )  $reviewwb2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td></td></tr> ';
	elseif($value['hide']==0) $wboardlist2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$studentid.'" target="_blank" ><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$resultValue.' </td><td> '.$hidewb.'  </td></tr> ';
	}
}
 
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET todayquizave='$todayqAve', ngrowth='$ngrowth',weekquizave='$weekqAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
if($tbegin==604800)$wboardScoreAve=(INT)($wboardScore/$nwboard/5*100);
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;

$wtimestart=time()-86400*($nday+3);
$Timelastaccess=$DB->get_record_sql("SELECT timecreated AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
$lastaction=time()-$Timelastaccess->maxtc;
$weeklyGoal2= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");

$inputtime=date("m/d", $weeklyGoal2->timecreated); 

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

$ncompleteratio=$ncomplete/($nreview+$ncomplete)*100;
$nquestion=$engagement3->nask/10*100;
$nreply=$engagement3->nreply/10*100;

$timefilled=round($engagement3->totaltime/($untiltoday+0.0001)*100,0);
$timefilled2=round($engagement3->totaltime/($weektotal+0.0001)*100,0);
if($timefilled>20000)$timefilled=100;

$appraise_result=round($totalappraise/($nappraise*5+0.001)*100,0);
	 
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET appraise='$appraise_result', usedtime='$timefilled', wbscore='$wboardScoreAve' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
else $DB->execute("UPDATE {abessi_indicators} SET tinspect='$timecreated' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  

$tbegin2=time()-604800;
$tbegin3=time()-86400; 

if($weeklyGoal->id!=NULL)$weeklyGoalText=$weeklyGoal->text; 
if($weeklyGoal->penalty>0)$addtime='<b style="color:red;"> (보충 '.$weeklyGoal->penalty.'분) </b> ';
//$drawing2=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='weekly' ORDER BY id DESC LIMIT 1 ");
//$drawingid=$drawing2->wboardid;

//$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
//$summaryid=$summary->wboardid;
$summaryid='todaynote'.date('Y-m-d',$timecreated).'user'.$studentid;
if($checkgoal->drilling==1)$alertimg='(<img loading="lazy" style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/exist.gif width=20>)';
else $alertimg='';
$goalid=$checkgoal->id; 
 
$goaldisplay= '<b style="font-size:16px;">'.$lastday.'</b>까지 목표가 "<a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$studentid.'&pid='.$termplan->id.'"target="_blank"><span style="color:red;font-size:16px;">'.$weeklyGoal->text.'</span></a>" 이어서 오늘은 <span style="color:red;font-size:16px;">"'.$checkgoal->text.'"</span>(을)를 목표로 정진 중입니다. ';
$mindset=$checkgoal->mindset; 
//$inspector=$checkgoal->teacherid;
$Confidence=$checkgoal->complete;
 

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
if($role!==student)$editgoal='<a href="https://mathking.kr/moodle/local/augmented_teacher/student/edittoday.php?id='.$studentid.'&mode=CA">입력</a>';

$btnname='질문하기';$bgcolor='green';
if($timecreated-$checkgoal->alerttime<43200)
	{
	$btnname='답변 대기중';
	$bgcolor='orange';
	}
$btnname2='도움요청';$bgcolor2='green';
if($timecreated-$checkgoal->alerttime2<43200)
	{
	$btnname2='도움 대기중'; 
	$bgcolor2='orange';
	}

$lastbreak= $DB->get_record_sql("SELECT id,timecreated FROM mdl_abessi_missionlog WHERE userid='$studentid' AND timecreated>'$adayAgo' AND eventid='7128'  ORDER BY id DESC LIMIT 1 ");
$beforebreak=60-($timecreated-$lastbreak->timecreated)/60;


if($role!=='student')$teacherButton1='<b> <button   type="button"   id="alert_addtime" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >보강추가</button>  </b>';
$todayplan='<table width=100%><tr><td></td><td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"> <button   type="button"  style = "font-size:16;background-color:'.$bgcolor.';color:white;border:0;outline:0;" onClick="quickReply(313,\''.$studentid.'\',\''.$goalid.'\')" >'.$btnname.'</button> <button   type="button"  style = "font-size:16;background-color:'.$bgcolor2.';color:white;border:0;outline:0;" onClick="quickReply2(314,\''.$studentid.'\',\''.$goalid.'\')" >'.$btnname2.'</button> '.$teacherButton1.'</td></tr></table>';
//ㄹㅇㅁㄹ


if($lastbreak->id!=NULL)$beforebreak=-1;

$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studenttoday','$timecreated')");
if($checkgoal->type==='오늘목표' ||$checkgoal->type==='검사요청')$todolist='<tr style=" border-top:5px solid #88c2fc;border-bottom:5px solid #88c2fc;"><td></td><td><b style="font-size:20;">오늘목표</b></td><td><div><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$summaryid.'"target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>&nbsp;&nbsp;'.$goaldisplay.' &nbsp;</div></td><td align=center></td><td>'.$date.'</td><td >'.$todayplan.'</td></tr>';
else $todolist='<tr style=" border-top:3px solid #88c2fc;"><td></td><td style="font-size:16;" align=center> 오늘 목표가 설정되지 않았습니다. <span style="color:white;font-size:16;">'.$editgoal.'</span></td><td> '.$todayplan.'</td></tr>'; 

$wgoalid=$wgoal->id;
$wstatus=''; 
if($wgoal->inspect==1)  
	{
	$wstatus='checked';
	}
 
 echo ' <div class="row"><div class="col-md-12"><div class="card"><div class="card-body"> 
<table  align=center  width=100% class="table table-head-bg-primary mt-8"><tbody>  '.$todolist.' </tbody> </table>   ';
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

if($engagement3->nask>=5)$result_question='충분히';
elseif($engagement3->nask>=1)$result_question='필요한 만큼';
else $result_question='부족함';
 
$check_reply=$nwrong+$ngaveup-$ncomplete-$nreview;
if($check_reply<=0)$result_reply='완료';
else $result_reply='미완료';

$NNnask=$engagement3->nask;
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

 
$totaltime=$engagement3->totaltime;
/*
$topicrate=round($engagement3->topictime/$totaltime*100,0);
$solrate=round(($engagement3->soltime+$engagement3->quiztime)/$totaltime*100,0);
$fixrate=round($engagement3->fixtime/$totaltime*100,0);
$fixexamtime=round($engagement3->fixexamtime/$totaltime*100,0);
$memorytime=round($engagement3->memorytime/$totaltime*100,0);

$totalfixrate=$fixrate+$fixexamtime+$memorytime;

$topicrate2=30;
$solrate2=40;
$fixrate2=30;

$stepquestion= $DB->get_records_sql("SELECT * FROM mdl_abessi_questionstamp WHERE userid='$studentid' AND (status LIKE '질문' || status LIKE '답변')  AND timemodified >'$halfdayago'  ORDER BY id DESC LIMIT 10");

$qstamps = json_decode(json_encode($stepquestion), True);
unset($value);
 
foreach($qstamps as $value)
{
$qstatus=$value['status']; $qwbid=$value['wboardid'];  $qplayindex=$value['playindex']; $qgid=$value['gid']; $eventtime=round(($timecreated-$value['timemodified'])/60,0);
$qlist.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$qwbid.'&gid='.$qgid.'&playindex='.$qplayindex.'&playstate=0&sketchstate=0&speed=3&mode=qstamp&studentid='.$studentid.'"target="_blank">'.$qwbid.'</a>('.$eventtime.'분)</td><td></td><td>'.$qstatus.'</td></tr>';
}
*/
// 활동 설계부


$synapsePower=$sumSynapse/(100*$nsynapse)*100;
$goalprogress=' 
<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">분기목표 성취도</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
$siprogress='<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">기억 회복력</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> '; 

if($nday>=$weekdays[date('D')] && $checkgoal->comment==NULL)$placeholder='새로운 주간 목표와 다음 목표를 입력해 주세요';
elseif($checkgoal->comment==NULL)$placeholder='다음 시간 목표를 입력해 주세요';
else $placeholder=$checkgoal->comment;

if($checkgoal->comment!=NULL)$commenttext=$checkgoal->comment;
//if($tremain<0)$plustime='<button type="button"   onclick="updatetime2(94,'.$studentid.','.$tremain.')">적용</button>';
//else $plustime=''; 

$goalcomplete=' <b>귀가검사</b>&nbsp;<input type="checkbox" name="checkAccount" '.$status5.'  onclick="submittoday(21,'.$studentid.',this.checked)"/>';


echo '
<table width="100%" style="border: 3px solid skyblue; border-collapse: collapse;" cellspacing="0" cellpadding="0">
  <tr valign="top">
    <!-- 왼쪽 여백 -->
    <td width="5%"></td><td width="45%" style="vertical-align: top;">
     <!-- 높이를 고정하고 싶으면 min-height를 주는 편이 안전 -->
      <iframe 
         src="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid='.$studentid.'&tbegin='.($timecreated-604800*12).'&tend='.($timecreated).'"
         width="100%" 
         height="400" 
         style="border: none;" 
         scrolling="yes"
         frameborder="0">
      </iframe>
    </td><!-- 왼쪽 본문 끝 -->
    
    <!-- 가운데 구분용 공간 -->
    <td width="3%"></td>
    
    <!-- 오른쪽 iframe 영역 -->
    <td width="45%" style="vertical-align: top;">
      <!-- 높이를 고정하고 싶으면 min-height를 주는 편이 안전 -->
      <iframe 
         src="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid='.$studentid.'"
         width="100%" 
         height="400" 
         style="border: none;" 
         scrolling="yes"
         frameborder="0">
      </iframe>
    </td>
    
    <!-- 오른쪽 여백 -->
    <td width="2%"></td>
  </tr>
</table>

  <br>
  <div class="card-body">
	<div class="row">
	  <div class="col-md-12">
		<div class="progress-card">
		<table width=100%><tr>
		<td width=25%>
		  <div class="demo">
			<div class="progress-card">
			  <div class="progress-status">
				 
				<span class="text-muted fw-bold">
				  <b>주간 : </b> 총 '.round($weektotal,1).'시간 <a href="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.'" target="_blank">
					<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width="15">
				  </a>
				</span> 
			  </div>
			  <div class="progress">
				<div class="progress-bar progress-bar-striped bg-'.$bgtype2.'" role="progressbar"
					 style="width: '.$timefilled2.'%"
					 aria-valuenow="'.$timefilled2.'" aria-valuemin="0" aria-valuemax="100"
					 data-toggle="tooltip" data-placement="top"
					 title="" data-original-title="'.round($timefilled2,1).'%">
				</div>
			  </div>
			</div>
		  </div></td>
		  <td width=2%>  </td>
		  
		<td style="vertical-align: top;" width=25%>  
 		  <div class="demo">
			<div class="progress-card">
			  <div class="progress-status">
				<span class="text-muted">
				    <b>오늘까지</b> : <b> 총 '.$untiltoday.'시간  <a href="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.'" target="_blank">
					<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1601225605001.png" width="15">
				  </a>'.$addtime.' &nbsp;
				  </span>   
			  </div>
			  <div class="progress">
				<div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar"
					 style="width: '.$timefilled.'%"
					 aria-valuenow="'.$timefilled.'" aria-valuemin="0" aria-valuemax="100"
					 data-toggle="tooltip" data-placement="top"
					 title="" data-original-title="'.round($timefilled,1).'%">
				</div>
			  </div>
			</div>
		  </div>
		  </td>
		  <td width=2%>  </td>
		  <td width=46%>   		  
		  <div class="demo" style="margin-top:3px;"> 
			&nbsp; DMN휴식</b> <input type="checkbox" name="checkAccount"  '.$status4.' onClick="Resttime(33,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/> &nbsp; <b>오프라인</b> <input type="checkbox" name="checkAccount"  '.$status5.' onClick="ChangeCheckBox(333,\''.$studentid.'\',\''.$goalid.'\', this.checked)"/>   &nbsp; '.$goalcomplete.'  <b> &nbsp;&nbsp;&nbsp; <span style="color:blue;">'.$tleft.'</span></b>  
		   </div>
			</td>
		  </tr></table>
		  
		</div><!-- ./progress-card -->
	  </div><!-- ./col-md-12 -->
	</div><!-- ./row -->
  </div><!-- ./card-body --> 

</td></tr></table> 
';


$pcomplete=round($untiltoday/$weektotal*100,0);

$recentlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason  LIKE 'addperiod'  ORDER by id DESC LIMIT 1 " );
if($recentlog->id!=NULL)$passedhours=round($recentlog->tamount*($timecreated-$recentlog->doriginal)/($recentlog->dchanged-$recentlog->doriginal),1);

$attendlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE  type NOT LIKE 'enrol' AND userid LIKE '$studentid' AND hide NOT LIKE '1' AND complete NOT LIKE '1' AND reason NOT LIKE 'addperiod' ORDER BY id DESC LIMIT 1  " );
$doriginal=date("Y-m-d",$attendlog->doriginal); $dchanged=date("Y-m-d",$attendlog->dchanged);

$tamounttotal=$attendlog->tupdate+$passedhours;
$attendancetext='&nbsp; # 남은 보강 <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style="color:black; font-size:14pt" href="https://mathking.kr/moodle/local/augmented_teacher/student/schedule.php?id='.$studentid.'&eid=1&nweek=4">예기치 못한 휴강상황을 위하여 + 5시간 이상을 권합니다.</a> ';
if($tamounttotal<=-5 && $attendlog->id !=NULL )$attendancetext='&nbsp; <b>'.$tamounttotal.'시간 </b>&nbsp;&nbsp;<a style="color:red; font-size:16pt" href="https://mathking.kr/moodle/local/augmented_teacher/student/schedule.php?id='.$studentid.'&eid=1&nweek=4">보강시간을 정해주세요 !</a> <img loading="lazy" src="https://mathking.kr/Contents/IMAGES/exist.gif" width=40>';

//$todayrecord='<table width=100%><tr style="background-color:#96c7ff;"> <td width=3%></td>  <td width=10% align=left style="font-size:12pt">'.$attendlog->type.'</td><td width=10%  align=left style="font-size:12pt">'.$attendlog->reason.'</td><td  width=10% align=left style="font-size:12pt">계획  '.$doriginal.'</td><td width=10%  align=left style="font-size:12pt">변경 '.$dchanged.'</td> <td align=left style="font-size:12pt"><table>'.$attendlog->text.'</table></td>  <td align=right>'.$attendancetext.'</td></tr></table>';

if($role!=='student')$clearfixnotes='<button type="button" onclick="checkAllBeginCheckboxes()">오답 클리어</button>';

echo '<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>시험결과 및 오답노트 현황</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table> <br> <br>  
<table align=center valign=top width=100%><thead> 
<tr>
<th scope="col"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800>1주일</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=2592000>1개월</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=7776000>3개월</a></th>
<th scope="col" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewallreplay.php?id='.$studentid.'&mode=today"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656132615.png width=25> KTM 화이트보드</a> 
 &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/wboards_stat.php?userid='.$studentid.'">📈 필기분석</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$clearfixnotes.' </th>
</tr><tr ><td  style=" vertical-align: top;"><br><b>준비학습</b> <br><br>'.$reviewwb0.$quizlist00.'<br><b>내신테스트</b>  <br><br><table>'.$quizlist11.$quizlist12.'</table><br><b>표준테스트</b>  <br><br><table>'.$quizlist21.$quizlist22.'</table><br><b>인지촉진</b>  <br><br><table>'.$quizlist31.$quizlist32.'</table></td>  <td  style=" vertical-align: top; ">
<table  style="">'.$wboardlist0.'<tr><td><br></td><td><br></td><td><br></td><td align=center><br></td><td><br></td></tr>  '.$wboardlist1.'<tr><td><br></td><td><br></td><td><br></td><td align=center><br></td><td><br></td></tr>'.$reviewwb.$reviewwb2.' '.$wboardlist2.'</table></td></tr></tbody></table>
<br><br>';

echo '
	<script>
function checkAllBeginCheckboxes() {
    var checkboxes = document.querySelectorAll("input.beginCheckbox");
    checkboxes.forEach(function(cb) {
        cb.checked = true;
        // 필요 시, 체크박스의 onClick 이벤트 핸들러를 수동으로 호출할 수도 있습니다.
        if (typeof cb.onclick === "function") {
            cb.onclick();
        }
    });
}
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

 

if($role!=='student')
{
 

echo '
<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>코스 및 스케줄 정보 (선생님용)</b> </td></tr></table>
<table  style="" width="100%" valign="top"><tr><th valign="top" width="70%">';
include("schedule_embed.php"); 

echo '</th><th valign="top"  width="30%">';
include("index_embed.php"); 
echo '</th></tr></table>';

}
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/student/imagegrid.php?id='.$studentid.'&ndays=1'; 
//$todayhighlight='https://mathking.kr/moodle/local/augmented_teacher/student/viewreplays.php?id='.$studentid.'&wboardid='.$summaryid.'&mode=today';																	   
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
	function updatetime2(Eventid,Userid,Tremain)
	{   
	 
	swal("보강시간이 " + Tremain + "분 추가되었습니다", {buttons: false,timer: 1000});
			$.ajax({
				url:"database.php",
			type: "POST",
				dataType:"json",
			data : {
			"eventid":Eventid,
			"userid":Userid,
			"tremain":Tremain,
					},
				success:function(data){
	
							}
			})
   		 
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
function submittoday(Eventid,Userid,Checkvalue)
	{  
	swal("업데이트 되었습니다.", {buttons: false,timer: 2000});
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
		data : {
				"userid":Userid,
				"eventid":Eventid,
				"checkimsi":checkimsi,
				},
			success:function(data)
				{

				}
		})
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
		function quickReply2(Eventid,Userid,Goalid){
		 
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
			
			var Alerttime= \''.$checkgoal->alerttime2.'\';
			if(Alerttime==0)swal("메세지가 전달되었습니다.","기다리는 동안 다른 활동을 진행해 주세요.", {buttons: false,timer: 3000});
			else swal("요청을 완료합니다.","안내받은 내용에 따라 활동을 계속해주세요", {buttons: false,timer: 3000});
			location.reload(); 
 
		}
function Resttime(Eventid,Userid,Goalid,Checkvalue)
{
    var checkimsi = 0;
    var Timeleft= \''.$beforebreak.'\';
    var TimebeforeFinish= 40;
    if(Checkvalue==true)
    {
        checkimsi = 1;
        if(Timeleft<0)
        {
            Swal.fire({
                backdrop: true,
                position:"top-center",
                showConfirmButton: false,
                customClass: {
                    container: "my-background-color"
                },
                html:
                \'<table align="center" style="width:100%; height:100%; margin:0; padding:0;"><tr><td style="width:100%; height:100%; margin:0; padding:0;"><iframe style="border: none; width:100%; height:100%; margin:0; padding:0; position:fixed; top:0; left:0;" src="https://mathking.kr/moodle/local/augmented_teacher/students/Alphi/growthmindset.php?id='.$studentid.'&mode=autoclick" ></iframe></td></tr></table>\',
            });
            
            $.ajax({
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":Eventid,
                },
                success:function(data){}
            });
        }
        else if(TimebeforeFinish<30)
        {
            swal("귀가시간이 다가 오고 있어요. 마무리 활동 후 귀가검사를 준비해 주세요 ^^", {buttons: false,timer: 3000});
            setTimeout(function() {location.reload(); },3000);
        }
        else 
        {
            swal("힘내세요 ! " + Timeleft + "분 더 공부하시면 휴식을 취하실 수 있습니다.", {buttons: false,timer: 3000});
            setTimeout(function() {location.reload(); },1000);
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
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":Eventid,
                },
                success:function(data){}
            });
        }
        else
        {
            $.ajax({
                url:"../students/check.php",
                type: "POST",
                dataType:"json",
                data : {
                    "userid":Userid,       
                    "goalid":Goalid,
                    "checkimsi":checkimsi,
                    "eventid":\'331\',
                },
                success:function(data){}
            });
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
