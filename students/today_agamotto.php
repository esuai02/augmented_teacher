<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
 
global $DB, $USER;
include("navbar.php");

//$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
//$role=$userrole->role;

 
$username= $DB->get_record_sql("SELECT state,lesson,lastname, firstname FROM mdl_user WHERE id='$studentid' ");
 
$tend= $_GET["tend"];  
 
$agmtid = $_GET["agmtid"]; 
$tbegin=$_GET["tbegin"];
$timecreated=time();
if($tbegin==NULL)$tbegin=$timecreated;
if($tend==NULL)$tend=$timecreated;
$jumpspot=$predict;
if($agmtid!=NULL)
	{
	$jumpspot= $DB->get_record_sql("SELECT * FROM mdl_abessi_forecast WHERE  id='$agmtid' ORDER BY id DESC LIMIT 1");
	$tbegin=$jumpspot->tbegin;
	$tend=$jumpspot->tbegin+43200;
	}

/*
$gradedright = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.questiontext LIKE '%Contents/MATH%' AND mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$tbegin'  ORDER BY mdl_question_attempt_steps.id DESC ");
$gradedwrong = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.questiontext LIKE '%Contents/MATH%' AND  mdl_question_attempt_steps.userid='$studentid' AND  (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial')   AND mdl_question_attempt_steps.timecreated > '$tbegin'  ORDER BY mdl_question_attempt_steps.id DESC ");
$gaveup = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
 LEFT JOIN mdl_question ON mdl_question_attempts.questionid=mdl_question.id  WHERE mdl_question.questiontext LIKE '%Contents/MATH%' AND  mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gaveup' AND mdl_question_attempt_steps.timecreated > '$tbegin'  ORDER BY mdl_question_attempt_steps.id DESC ");
$nright=count($gradedright);
$nwrong=count($gradedwrong);
$ngaveup=count($gaveup);
*/



// get mission list
 
$adayAgo=$tbegin-43200; 
$aweekAgo=$tbegin-604800;
   
$inspect=$DB->get_record_sql("SELECT data AS time FROM mdl_user_info_data where userid='$studentid' AND fieldid='56' "); 
 
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE mdl_quiz_attempts.timefinish<'$tend' AND (mdl_quiz_attempts.timefinish > '$aweekAgo' OR mdl_quiz_attempts.timestart > '$aweekAgo' OR (state='inprogress' AND mdl_quiz_attempts.timestart > '$aweekAgo') ) AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.timestart ");
$quizresult = json_decode(json_encode($quizattempts), True);

$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0;  $ntodayquiz=0;  $weekGrade=0;  $nweekquiz=0;$totalquizgrade1=0;$totalmaxgrade1=0;$nmaxgrade1=0; $totalquizgrade2=0;$totalmaxgrade2=0;$nmaxgrade2=0; $totalquizgrade3=0;$totalmaxgrade3=0;$nmaxgrade3=0; 
unset($value); 	
foreach(array_reverse($quizresult) as $value) 
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
	if(strpos($value['name'], 'ifmin')!== false)$quiztitle=substr($value['name'], 0, strpos($value['name'], '{'));
	else $quiztitle=$value['name'];
	$quizinstruction='<b>'.$quiztitle.' </b><br><br> '.$value['instruction'].'<hr>'.$value['comment'];
	if($value['maxgrade']==NULL) $comment= '&nbsp;|&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:blue">분석안함</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
	elseif(strpos($value['comment'], '최선을 다한 결과')!== false) $comment= '&nbsp;|&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:green">분석결과</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
	elseif($value['comment']==NULL) $comment= '&nbsp;|&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:grey">분석결과</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
	else $comment= '&nbsp;|&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:red">분석결과</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
	$attemptid=$value['id'];
	if($role!=='student')$deletequiz='<span onclick="deletequiz(\''.$attemptid.'\')"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641497146.png width=15></span>';
	//$quizattempt= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid'");
	$maxgrade=$value['maxgrade'];
	if(strpos($quiztitle, '내신')!= false)  
	{
	//if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)  
		{
		$quizlist11.='<b>'.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).'</b>'.$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
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
		$quizlist12.=''.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
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
 	}elseif($qnum>9)  //$todayGrade  $ntodayquiz  $weekGrade  $nweekquiz
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
		{
		$quizlist21.=  '<b>'.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).'</b>'.$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
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
		$quizlist22.=''.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
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
	}else
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)$quizlist31.= '<b>'.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' | <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).'</b>'.$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
	else $quizlist32.=''.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quiztitle,0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&studentid='.$studentid.' " target="_blank">'.$value['state'].'</a>...'.date("H:i",$value['timefinish']).$comment.'&nbsp;&nbsp;&nbsp;'.$deletequiz.'<br>';
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
 
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid'  AND  tlaststroke<'$tend' AND   hide=0 AND status NOT LIKE 'boost' AND tlaststroke>'$aweekAgo' AND contentstype=2   ORDER BY tlaststroke DESC LIMIT 300 ");

$nsynapse=0;
$sumSynapse=0;
$nreview=0;
$ncomplete=0;
$nappraise=0;
$totalappraise=0;
$wboardScore=0;  
$nwboard=0;
$nrecovery=0;
$nask=0;
$nflag=0;
$ntodayNote=0;
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
if($value['timecreated']>$tbegin)$ntodayNote++;
if($value['status']==='review')$nreview++;
if($value['status']==='complete')$ncomplete++;
if($value['depth']>2)$nrecovery++;
if($value['status']==='begin')$nask++;
$Q_id=$value['contentsid'];
// 화이트보드 평점 계산
if($value['timemodified']>$tbegin && $value['star'] > 0)
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
$resultValue='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1652034774.png" height=15>';
if($value['depth']==1)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030610001.png" height=15>';
if($value['depth']==2)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030673001.png" height=15>';
if($value['depth']==3)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030714001.png" height=15>';
if($value['depth']==4)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030746001.png" height=15>';
if($value['depth']==5)$resultValue='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621030771001.png" height=15>';
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
$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/createnote.png" width=15>';
if($value['flag']==1)$fixhistory='<img src="https://mathking.kr/Contents/IMAGES/bookmark2.png" width=15>';
elseif($value['teacher_check']==1)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1609582681001.png" width=15>';
elseif($value['teacher_check']==2 && $value['nstep']==0)$fixhistory='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603795456001.png" width=15>'; 
elseif($value['teacher_check']==2 && $value['nstep']>0)$fixhistory='<img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1620732184001.png" width=15>'; 
if($value['student_check']==1)$checkstatus='checked'; 
 
if($value['contentstype']==2)
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
	$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';  
	}
else
	{
	$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
	$ctext=$getimg->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctext);
	$imageTags = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags as $imageTag)
		{
		$nimg++;
	    	$imgSrc = $imageTag->getAttribute('src');
		$imgSrc = str_replace(' ', '%20', $imgSrc); 
		if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
		}

	$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 	$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$userid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
	}


if($nstroke<3)
	{
	$ave_stroke='##';
	$nstroke='##';
	}
 
include("../whiteboard/status_icons.php");
 
$wbidbooster='booststep'.$contentsid.'_user'.$studentid;
if($value['status']==='review' && time()-$value['timemodified']+43200 > $value['treview']*86400)$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15"> 예약';

if($role!=='student')$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> (<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'"target="_blank">재생</a>)';
if($value['status']==='flag' && $value['timecreated']>$adayAgo)
	{
	$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$contentsid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
	$nstroke=$bstep->nstroke;

	if($bstep->nthink>=3)$nthinktext='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($bstep->nthink>=1)$nthinktext='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</b>';
	elseif($nstroke>15 && $bstep->nthink==0) { $nthinktext='OK'; $imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 책갈피';  }
	elseif($bstep->nthink==0) $nthinktext='<b style="color:red;">check !</b>';
	if($status==='review')  $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.' '.$contentslink.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td><td>'.$value['helptext'].'</td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	else $wboardlist0.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.' '.$contentslink.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/> '.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td><td>'.$value['helptext'].'</td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
	$nflag++;
 
	}
elseif($value['timecreated']>$adayAgo ) 
	{
	if($status==='review') $reviewwb.= '<tr><td  sytle="font-weight: bold;">오늘 '.$imgstatus.'&nbsp;'.$contentslink.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span></a><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">'.$fixhistory.' '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	else $wboardlist1.= '<tr><td  sytle="font-weight: bold;">오늘 '.$imgstatus.'&nbsp;'.$contentslink.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span></a><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">'.$fixhistory.' '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	}
elseif($value['timemodified']<=$adayAgo && $value['status']!=='flag'  && $value['helptext']!=='해결') 
	{
	if($status==='review') $reviewwb.= '<tr><td>지난 '.$imgstatus.'&nbsp;'.$contentslink.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>'.$fixhistory.' '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	else $wboardlist2.= '<tr><td>지난 '.$imgstatus.'&nbsp;'.$contentslink.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$encryption_id.'" target="_blank"><div class="tooltip3"> <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m월d일 | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>'.$fixhistory.' '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
	}
}
 

if($tbegin==604800)$wboardScoreAve=(INT)($wboardScore/$nwboard/5*100);
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$wtimestart=$tbegin-86400*($nday+3);
$wtimestart2=$wtimestart+604800;
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
$lastaction=time()-$Timelastaccess->maxtc;
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart'  AND timecreated<'$wtimestart2'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$inputtime=date("m/d", $weeklyGoal->timecreated);
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND timecreated < '$tbegin'  ORDER BY id DESC LIMIT 1 ");
$lastday=$schedule->lastday;
   
$weeklyGoalText='<span style="color:white;font-size=15;"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612786844001.png" width=40> 이번 주 목표가 설정되지 않았습니다. </span>';

if($weeklyGoal->id!=NULL)$weeklyGoalText=$weeklyGoal->text;
if($weeklyGoal->penalty>0)$addtime='<b style="color:red;"> (보충 '.$weeklyGoal->penalty.'분) </b> ';
$drawing2=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='weekly' ORDER BY id DESC LIMIT 1 ");
$drawingid=$drawing2->wboardid;


$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='summary' ORDER BY id DESC LIMIT 1 ");
$summaryid=$summary->wboardid;

 
// get mission list 

 
$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated<'$tbegin' AND ( type LIKE '오늘목표' OR type LIKE '검사요청' OR type LIKE '미션부여' ) ORDER BY id DESC LIMIT 1 ");
if($goal->drilling==1)$alertimg='(<img style="margin-bottom:5px;" src=https://mathking.kr/Contents/IMAGES/exist.gif width=30>)';
else $alertimg='';
$goalid=$goal->id; 
$text= '<span  style="font-size:20;">'.$goal->text.'</span>';
 
$date= date("m월d일", $tbegin);
 

 echo ' <div class="row"><div class="col-md-12"><div class="card"><div class="card-body"><hr style="border: solid 1px #2db4d6;">
<table><tr><td width=3%></td><td><h6 align=center># '.$date.'</h6></td><td width=5%></td><td><h6 align=center> # 주간목표 : '.$weeklyGoal->text.'   &nbsp;&nbsp;&nbsp; # 오늘목표 : '.$text.' </h6></td></tr></table> <hr style="border: solid 1px #2db4d6;">';

 
$mission=$DB->get_record_sql("SELECT * FROM mdl_abessi_mission WHERE userid='$studentid' AND msntype=3 ORDER BY id DESC LIMIT 1 ");
$examGrade=$mission->grade;
$examDday=strtotime($mission->startdate);
 
$diff =$examDday-time();
 
$leftDays =(INT)($diff/86400)+1;
$leftRate=(INT)($leftDays/60*100);
  
if($leftRate<30)$bgtypeDday='danger';
elseif($leftRate<60)$bgtypeDday='warning';
else $bgtypeDday='success';


if($checkgoal->rcomplete==1)$status1="checked";
if($checkgoal->ncomplete==1)$status2="checked";
if($checkgoal->pcomplete==1)$status3="checked";

 
// 현재 상태 시각화 부 
//if($role!=='student')$teacherButton1='<button   type="button"   id="alert_learnmore1" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >복습출제</button> <button   type="button"   id="alert_learnmore2" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >발표출제</button> <button  style = "font-size:16;background-color:green;color:white;border:0;outline:0;" onClick="showList(\''.$studentid.'\')">선택출제</button> <button   type="button"   id="alert_addtime" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >시간조절</button>  </b>';
//<button  style = "font-size:16;background-color:green;color:white;border:0;outline:0;" onClick="showList(\''.$studentid.'\')">보충학습</button>
if($role!=='student')$teacherButton1=' <button   type="button"   id="alert_addtime" style = "font-size:16;background-color:green;color:white;border:0;outline:0;" >즉석보강 입력</button>  </b>';
$synapsePower=$sumSynapse/(100*$nsynapse)*100;
$siprogress='<div class="demo"><div class="progress-card"><div class="progress-status"><span class="text-muted">기억 회복력</span><span class="text-muted"> '.round($synapsePower,1).'%</span></div>
<div class="progress"><div class="progress-bar bg-info" role="progressbar" style="width: '.$synapsePower.'%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="65%"></div>
</div></div></div> ';
$instructionToday='<div class="container" ><table width=100%><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/logicalstairway.php?id='.$studentid.'&tb=43200"target="_blank"><b>▶ 보충학습 '.$alertimg.' </b></a></td><td>'.$teacherButton1.'</td></tr></table><hr><table width=100% valign=top>'.$instruction1.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr>'.$instruction2.'<tr><td valign=top><hr></td><td><hr></td><td valign=top><hr></td></tr>'.$breakinfo.'</table> </div>';
if($checkgoal->comment==NULL)$placeholder='다음 시간 목표를 입력해 주세요';
else $placeholder='다음 목표 : '.$checkgoal->comment;
 
 $progresstext='총 '.round($jumpspot->totaltime/3600,1).'시간 ';
if($jumpspot->prob1!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time1.'">'.$jumpspot->prob1.'%/'.round(($jumpspot->time1-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text1.'</td></tr></table></span></div>';
if($jumpspot->prob2!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time2.'">'.$jumpspot->prob2.'%/'.round(($jumpspot->time2-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text2.'</td></tr></table></span></div>';
if($jumpspot->prob3!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time3.'">'.$jumpspot->prob3.'%/'.round(($jumpspot->time3-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text3.'</td></tr></table></span></div>';
if($jumpspot->prob4!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time4.'">'.$jumpspot->prob4.'%/'.round(($jumpspot->time4-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text4.'</td></tr></table></span></div>';
if($jumpspot->prob5!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time5.'">'.$jumpspot->prob5.'%/'.round(($jumpspot->time5-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text5.'</td></tr></table></span></div>';
if($jumpspot->prob6!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time6.'">'.$jumpspot->prob6.'%/'.round(($jumpspot->time6-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text6.'</td></tr></table></span></div>';
if($jumpspot->prob7!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time7.'">'.$jumpspot->prob7.'%/'.round(($jumpspot->time7-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text7.'</td></tr></table></span></div>';
if($jumpspot->prob8!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time8.'">'.$jumpspot->prob8.'%/'.round(($jumpspot->time8-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text8.'</td></tr></table></span></div>';
if($jumpspot->prob9!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time9.'">'.$jumpspot->prob9.'%/'.round(($jumpspot->time9-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text9.'</td></tr></table></span></div>';
if($jumpspot->prob10!=NULL)$progresstext.='<img src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30><div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&tbegin='.$jumpspot->tbegin.'&tend='.$jumpspot->time10.'">'.$jumpspot->prob10.'%/'.round(($jumpspot->time10-$jumpspot->tbegin)/3600,1).'H </a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$jumpspot->text10.'</td></tr></table></span></div>';

 
echo '<table align=center><tr><td><img src=https://mathking.kr/Contents/IMAGES/agamotto.gif width=50>&nbsp;&nbsp;&nbsp;</td><td>'.$progresstext.' </td></tr></table><br>';

// 시험결과 섹션
echo '<table align=center width=100%><tr><td  style="color:white;background-color:#0373fc; font-size:20;" align=center><b>📊 시험결과</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table> <br>  
<table align=center valign=top width=100%><thead> 
<tr>
<th scope="col"><a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200>오늘 테스트 결과 </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800>최근 1주일</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=2592000>최근 1개월</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=7776000>최근 3개월</a></th>
</tr><tr ><td  style=" vertical-align: top;"><hr><b>내신테스트</b>.....분석'.$nmaxgrade1.'.....'.round(($totalmaxgrade1-$totalquizgrade1)/(100*$nmaxgrade1+0.01)*100,0).'% 향상 <br><br>'.$quizlist11.''.$quizlist12.'<hr><b>표준테스트</b>.....분석'.$nmaxgrade2.'.....'.round(($totalmaxgrade2-$totalquizgrade2)/(100*$nmaxgrade2+0.01)*100,0).'% 향상 <br><br>'.$quizlist21.''.$quizlist22.'<hr><b>인지촉진</b>.....분석'.$nmaxgrade3.'.....'.round(($totalmaxgrade3-$totalquizgrade3)/(100*$nmaxgrade3+0.01)*100,0).'% 향상 <br><br>'.$quizlist31.''.$quizlist32.'</td></tr></tbody></table>
<br><br>';

// 오답노트 섹션
echo '<table align=center width=100%><tr><td  style="color:white;background-color:#dc3545; font-size:20;" align=center><b>📝 오답노트 현황</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;오답노트 후 자신감이 생긴 문항이 '.round($recoveryrate,0).'% 입니다.</td></tr></table> <br>  
<table align=center valign=top width=100%><thead> 
<tr>
<th scope="col"> 총 오답 '.($nwrong+$ngaveup).' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;오답노트 '.$ntodayNote.' | 예약 '.$nreview.' | 완료 '.$ncomplete.'  &nbsp; &nbsp;<b style="color:red;"> 도전지수 '.$appraise_result.'</b>  &nbsp; &nbsp; &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/imagegrid.php?id='.$studentid.'&ndays=7"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624791079001.png width=25></a></th>
</tr><tr ><td  style=" vertical-align: top; "><table  style=""><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist0.' <tr><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$wboardlist1.'<tr><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$reviewwb.' <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'</table></td></tr></tbody></table>
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
 
$jumplist= $DB->get_records_sql("SELECT * FROM mdl_abessi_forecast WHERE  userid='$studentid' ORDER BY id DESC LIMIT 20");
$jumpresult = json_decode(json_encode($jumplist), True);
unset($value);
foreach($jumpresult as $value)
	{
	$jumpdate=date("m월d일", $value['timecreated']); 
	$timelines.='<img style="margin-bottom:3px;" src=https://mathking.kr/Contents/IMAGES/agamotto.png width=30> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today_agamotto.php?id='.$studentid.'&agmtid='.$value['id'].'">'.$jumpdate.'</a>&nbsp;&nbsp;';
	}
																	   
echo '<table align=center><tr><td>'.$timelines.'</tr></table></div> </div></div></div></div></div></div>

';
 		
include("quicksidebar.php");
echo '<script>  
		  
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

.feel {
  margin: 0px 5px;
  background-color: white;
  height:30px;
}
</style>
</html>