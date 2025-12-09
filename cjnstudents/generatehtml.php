<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid=$_POST['eventid'];
$pagetype=$_POST['pagetype'];
$userid = $_POST['userid'];
$timecreated=time();

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;

if($pagetype==='welcome' || $pagetype==='prepare')
  {
  if($eventid==1)// 퀴즈결과
    {
    $timestart=$timecreated-604800;
     $quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.maxgrade AS maxgrade, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.timemodified > '$timestart' AND mdl_quiz_attempts.userid='$userid' ORDER BY mdl_quiz_attempts.id DESC LIMIT 200 ");
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
		if($value['maxgrade']==NULL) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:blue">분석</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		elseif(strpos($value['comment'], '최선을 다한 결과')!== false) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:green">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		elseif($value['comment']==NULL) $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:grey">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
		else $comment= '&nbsp;<div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$userid.'&attemptid='.$value['id'].'" target="_blank"><b style="color:red">완료</b></a><span class="tooltiptext3"><table style="" align=center><tr><td>'.$quizinstruction.'</td></tr></table></span></div>';
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
	  		$quizlist00.='<tr><td> <a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.' <input type="checkbox" name="checkAccount"    onClick="AddReview(11111,\''.$userid.'\',\''.$value['id'].'\', this.checked)"/> </a></td></tr> ';
			}
		elseif(strpos($quiztitle, '내신')!= false)   
			{
			//if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)  //<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/>
				{
				if($quizgrade>89.99){$reducetime=$reducetime+30; $eventtext.='<tr><td>퀴즈성공 30분</td></tr>';}
				elseif($quizgrade>79.99){$reducetime=$reducetime+10; $eventtext.='<tr><td>퀴즈노력 10분</td></tr>';}
				$quizlist11.='<tr><td>'.$imgstatus.'&nbsp;'.$quizstart.' </td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td><td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
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
				$quizlist12.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
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

				$quizlist21.= '<tr><td>'.$imgstatus.'</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td><td>'.$quizstart.'</td>  <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
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
				$quizlist22.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
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
			if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)$quizlist31.='<tr><td>'.$imgstatus.'</td> <td><b><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</b></td> <td>'.$quizstart.'</td> <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
			else $quizlist32.='<tr><td>'.$imgstatus.'</td> <td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$quiztitle.'</a>...('.$value['attempt'].'회)</td><td>'.$quizstart.'</td>  <td><span class="" style="color: rgb(239, 69, 64);">'.$quizgrade.'점</span> </td> <td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'&userid='.$userid.' " target="_blank">'.$value['state'].'</a></td> <td>'.$timefinish.'</td><td>'.$comment.'</td> </tr>';
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
if($tbegin==604800)$DB->execute("UPDATE {abessi_indicators} SET todayquizave='$todayqAve', ngrowth='$ngrowth', weekquizave='$weekqAve' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");  
$amonthago=$timecreated-604800*4;
$html='<hr><table align=center width=90%><tr><td><b>내신테스트</b>.....분석'.$nmaxgrade1.'.....'.round(($totalmaxgrade1-$totalquizgrade1)/(100*$nmaxgrade1+0.01)*100,0).'% 향상 <br><br></td></tr></table><table width=90% align=center>'.$quizlist11.$quizlist12.'</table><hr><table align=center width=90%><tr><td><b>표준테스트</b>.....분석'.$nmaxgrade2.'.....'.round(($totalmaxgrade2-$totalquizgrade2)/(100*$nmaxgrade2+0.01)*100,0).'% 향상 <br><br></td></tr></table><table width=90% align=center>'.$quizlist21.$quizlist22.'</table><hr><table align=center width=90%><tr><td><b>인지촉진</b>.....분석'.$nmaxgrade3.'.....'.round(($totalmaxgrade3-$totalquizgrade3)/(100*$nmaxgrade3+0.01)*100,0).'% 향상 <br><br></td></tr></table><table width=90% align=center>'.$quizlist31.$quizlist32.'</table>';
     }
  elseif($eventid==2)//오답노트
    {
	$eventtime=$timecreated-604800;
	$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$userid'  AND tlaststroke>'$eventtime' AND contentstype=2 AND  (active=1 OR status='flag' )  ORDER BY tlaststroke DESC LIMIT 300 ");

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
		$seethiswb='Q7MQFA'.$contentsid.'0tsDoHfRT_user'.$userid.'_'.date("Y_m_d", $value['timemodified']);
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
 
		 
		$wbidbooster='booststep'.$contentsid.'_user'.$userid;
		
		$hidewb='';
		if($value['status']==='review' && $value['hide']==0)$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/>';
		elseif($value['hide']==1 && $value['status']==='review' && $role!=='student' )$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/>  <img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width=20>';
		elseif($role!=='student')$hidewb='<input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/>';
		$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a>';
		if($value['status']==='flag' && $value['timemodified']>$adayAgo && $value['contentstitle']!=='incorrect' )
			{
			$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$contentsid' AND contentstype='2' AND userid='$userid' ORDER BY id DESC LIMIT 1 ");
			$nstroke=$bstep->nstroke;
		
			if($value['helptext']==='OK' || ($nstroke>15 && $bstep->nthink==0)){ $nthinktext='OK'; $imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 책갈피';  }
			elseif($bstep->nthink>=3)$nthinktext='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>';
			elseif($bstep->nthink>=1)$nthinktext='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</b>';
			elseif($bstep->nthink==0) $nthinktext='<b style="color:red;">check !</b>';
		
			if($status==='review' && $value['hide']==0 )  $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
			<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$userid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
			elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td>
			<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$userid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td>  '.$hidewb.' </td><td  sytle="font-weight: bold;"></td></tr> ';
			elseif($value['hide']==0 ) $wboardlist0.= '<tr><td  sytle="font-weight: bold;">'.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).' <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div><span  onClick="showWboard(\''.$encryption_id.'\')">('.$nstroke.'획)</span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=cjnNote'.$encryption_id.'&srcid='.$encryption_id.'&studentid='.$userid.'&mode=addexp"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1656744014.png width=25></a></td><td></td><td  sytle="font-weight: bold;"> '.$nthinktext.' </td><td>  '.$hidewb.' </td></tr> ';
			$nflag++;
		 
			}
		elseif($value['timemodified']>$adayAgo && $value['status']!=='flag')  
			{
			if($status==='review' && $value['hide']==0 ) $reviewwb.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/>  <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span> <span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;"> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
			elseif($status==='review' && $value['hide']==1 && $role!=='student' )$reviewwb2.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.' '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td  sytle="font-weight: bold;"></td></tr> ';
			elseif($value['hide']==0) $wboardlist1.= '<tr><td  sytle="font-weight: bold;">'.$resulttype.$imgstatus.'  '.$fixhistory.'</td><td  sytle="font-weight: bold;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></span><span  onClick="showWboard(\''.$encryption_id.'\')">'.$cntinside.'</span></td><td  sytle="font-weight: bold;">&nbsp;&nbsp;</td><td  sytle="font-weight: bold;">  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
			}
		elseif($value['timemodified']<=$adayAgo && $value['status']!=='flag'  && $value['helptext']!=='해결') 
			{
			if($status==='review' && $value['hide']==0 )
				{
				if($value['status']==='review' && time()> $value['treview'])
					{
					$nreview2++;
					$imgstatus='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1626450444001.png" width="15">';  // 복습예약 활동문항
					$reviewwb0.= $imgstatus.' <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$userid.'" target="_blank" >복습예약 </a> ('.$value['nreview'].'회) | ';
					}
				else
					$reviewwb.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$userid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td>  '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
				}
			elseif($status==='review' && $value['hide']==1 && $role!=='student' )  $reviewwb2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$userid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$hidewb.'  </td><td></td></tr> ';
			elseif($value['hide']==0) $wboardlist2.= '<tr><td>'.$resulttype2.$imgstatus.' '.$fixhistory.'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox2(11,\''.$userid.'\',\''.$encryption_id.'\', this.checked)"/> <a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'&studentid='.$userid.'" target="_blank" ><div class="tooltip3">'.substr($tagtitles,0,40).'&nbsp;&nbsp;'.date("m/d | H:i",$value['timemodified']).'  <span class="tooltiptext3"><table style="" align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a><span  onClick="showWboard(\''.$encryption_id.'\')"> '.$cntinside.'</span></td><td>&nbsp;&nbsp;</td><td> '.$resultValue.' '.$grader.' </td><td> '.$hidewb.'  </td></tr> ';
			}
		}
	$html='<table align=center width=90%>'.$wboardlist1.'<tr><td><hr></td><td><hr></td><td><hr></td><td align=center><hr></td><td><hr></td></tr>'.$reviewwb.$reviewwb2.' <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$wboardlist2.'</table></td></tr></tbody></table><br><hr><br>';		
	 		
	}
  }  
echo json_encode( array("html" =>$html) );
?>

   