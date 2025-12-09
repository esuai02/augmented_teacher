<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
if($studentid==NULL)$studentid=$USER->id;
require_login();
$timecreated=time(); 
$hoursago=$timecreated-14400;
$aweekago=$timecreated-604800;
$thisuser= $DB->get_record_sql("SELECT  lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;
   
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");

//$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND (timemodified > '$hoursago' || (student_check=1 AND timemodified > '$aweekago')) ORDER BY timecreated DESC LIMIT 300");
$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND timemodified > '$hoursago'   ORDER BY timecreated DESC LIMIT 100");
$result = json_decode(json_encode($handwriting), True);
unset($value);
$quizstatus=0;
$eventspaceanalysis='<a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid='.$studentid.'">📊</a>';
$ForDeepLearning='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid='.$studentid.'"> <img loading="lazy"  src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651023487.png" width=40></a>';

foreach(array_reverse($result) as $value) 
	{
	$wboardid=$value['wboardid'];
	$contentstype=$value['contentstype'];
	$contentsid=$value['contentsid'];
	$contentstitle=$value['contentstitle'];
	$instruction=$value['instruction'];
	$nstroke=$value['nstroke'];
	$ncommit=$value['feedback'];
	if($ncommit!=0)$ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';
	$usedtime=round($value['usedtime']/60,1).'분';
	$tinterval=round(($tprev-$value['timemodified'])/60,0).'분';
	$tprev=$value['timemodified'];
	$status=$value['status'];
	if($tinterval<0)$tinterval=round(($timecreated-$value['timemodified'])/60,0).'분';
 
	$timestamp=$timecreated-$value['timemodified'];
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';

	$instructionBtn='';
	
	if($value['student_check']==1)$checkstatus='Checked';
	else $checkstatus='';

	if($role!=='student' || $timestamp>7200)$checkout='<input type="checkbox" name="checkAccount"  '.$checkstatus.'  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
	else $checkout='▶ ';

	
	if($value['status']==='commitquiz')
		{  
		$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$contentsid'  ");
		$attemptlog=$DB->get_record_sql("SELECT id,quiz,attempt,sumgrades,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' AND timemodified>'$aweekago' ORDER BY id DESC LIMIT 1 ");
		 
		if($attemptlog->id!=NULL)
			{ 
			$moduleid=$DB->get_record_sql("SELECT instance FROM mdl_course_modules where id='$contentsid'  ");
			$attemptlog=$DB->get_record_sql("SELECT id,quiz,attempt,sumgrades,timefinish FROM mdl_quiz_attempts where quiz='$moduleid->instance' AND userid='$studentid' AND timemodified>'$aweekago' ORDER BY id DESC LIMIT 1 ");
		 
			$timefinish=date("m/d | H:i",$attemptlog->timefinish); 
			$quizgrade=round($attemptlog->sumgrades/$quiz->sumgrades*100,0);
			$contentstitle=$contentstitle.'(최근점수:'.$quizgrade.'점, 최근시험:'.$timefinish.')';
			$cnturl='https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptlog->id.'&studentid='.$studentid;
			}
		else $cnturl='https://mathking.kr/moodle/mod/quiz/view.php?id='.$contentsid;

		if($value['student_check']==1)$submitted.='<tr><td width=8%  style="background-color:#FFA389;white-space: nowrap; color:black;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | 퀴즈 독립세션  </td><td width=10% align=center> <b><a style="color:#000000;text-decoration:none;" href="'.$cnturl.'"target="_blank">'.$contentstitle.'</a></b> </td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		else $papertest.='<tr><td width=8%  style="background-color:#E2BAFF;white-space: nowrap; color:black;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | 퀴즈 독립세션  </td><td width=10% align=center> <b><a style="color:#000000;text-decoration:none;" href="'.$cnturl.'"target="_blank">'.$contentstitle.'</a></b> </td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		}
	elseif(strpos($wboardid, 'jnrsorksqcrark')!== false)
		{
		$noteurl=$value['url'];
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		if(strpos($getimg->reflections,'지시사항')!==false)$instructionBtn='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a><br><br>';
		if($getimg->reflections!=NULL)$reflections=$getimg->reflections.'<hr>';
		$htmlDom = new DOMDocument;
		if($studentid==NULL)$studentid=2;
		
		@$htmlDom->loadHTML($ctext);
		$imageTags = $htmlDom->getElementsByTagName('img');
		$extractedImages = array();
		$nimg=0;
		foreach($imageTags as $imageTag)
			{
			$nimg++;
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false)break;
			}

		if($value['boardtype']==='thinkaloud') // 문제풀이 발표
			{ 
			$recordlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_solutionlog WHERE wboardid LIKE '$wboardid'  ORDER BY id DESC LIMIT 1");
			$mathexpression=$recordlog->mathexpression;
			//$mathexpression = htmlspecialchars($recordlog->mathexpression, ENT_QUOTES, 'UTF-8');
			if($value['student_check']==1)$submitted.='<tr><td width=8%  style="background-color:#FFA389;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번| <hr> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_record.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/thinkaloud.jpg"  width=50%></a>  </td><td width=10% align=center><div class="tooltip3">  <b><a style="color:#1080E9;text-decoration:none;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/LLM_feedback.php?contentstype='.$contentstype.'&contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'"target="_blank">씽크 얼라우드</a></b> <span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div> <br><br><span style="overflow-wrap: break-word;">'.$mathexpression.'</span></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$recordlog->gptresult.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			else $papertest.='<tr><td width=8%  style="background-color:#1080E9;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  <hr> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_record.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/thinkaloud.jpg"  width=50%></a></td><td width=10% align=center  style="overflow-wrap: break-word; "><div class="tooltip3">  <b><a style="color:#1080E9;text-decoration:none;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/LLM_feedback.php?contentstype='.$contentstype.'&contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'"target="_blank">씽크 얼라우드</a></b><span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div> <br><br> <span style="overflow-wrap: break-word;">'.$mathexpression.'</span></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$recordlog->gptresult.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			}
		else
			{ 
			if($value['student_check']==1)$submitted.='<tr><td valign=top style="background-color:#71D4EF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><br>'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br>'.$instructionBtn.'<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 360, "utf-8").''.$instruction.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			else $papertest.='<tr><td valign=top style="background-color:#d2eff7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><br>'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...<br><br>'.$instruction.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			}
		}
	elseif($value['status']==='reflect')
		{  
		if($value['student_check']==1)$submitted.='<tr><td width=8% style="background-color:#FFA389;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">...</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		else $papertest.='<tr><td width=8%  style="background-color:#1080E9;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  </td><td width=10% align=center> <b><a style="color:#1080E9;text-decoration:none;"href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$contentsid.'"target="_blank">'.$contentstitle.'테스트 결과 성찰</a></b> </td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...<br><br> </td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
		} 
	/*elseif($value['status']==='examplenote')
		{  
 		$examplenote.='<td align=center style="backgroud-color:#D2D2D2;"> <b><a style="color:#080809;text-decoration:none;"href="https://mathking.kr/moodle/local/augmented_teacher/students/examplenote.php?userid='.$studentid.'&cntid='.$contentsid.'&title='.$contentstitle.'"target="_blank">'.$contentstitle.'</a></b> | </td>';
		}*/
	else
		{
		$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");
		if(strpos($qtext->reflections1,'지시사항')!==false)$instructionBtn='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a><br><br>';

		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			}
		if($value['result']=='right')$result='<b style="color:#009c10;">정답</b>';
		elseif($value['result']=='wrong')$result='오답';
		else 
			{
			$result='시도중';
			if($value['status']==='attempt')$quizstatus=1;
			}
		$complementary='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/complementary.php?id='.$contentsid.'&userid='.$studentid.'"target="_blank"> <img loading="lazy"  style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/plus.png" width=15></a><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/createSmallQuestions.php?cntid='.$contentsid.'&cnttype=2"target="_blank"> <img loading="lazy"  style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/plus.png" width=15></a>';
		$reflections=$qtext->reflections1;
		if($value['boardtype']==='thinkaloud') // 문제풀이 발표
			{
			$recordlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_solutionlog WHERE wboardid LIKE '$wboardid'  ORDER BY id DESC LIMIT 1");
			$mathexpression=$recordlog->mathexpression;
			//$mathexpression = htmlspecialchars($recordlog->mathexpression, ENT_QUOTES, 'UTF-8');
			if($value['student_check']==1)$submitted.='<tr><td width=8%  style="background-color:#FFA389;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번| <hr> <table align=center><tr><td  align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_record.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/thinkaloud.jpg"  width=50%></a></td></tr></table>  </td><td width=10% align=center><div class="tooltip3">  <b><a style="color:#1080E9;text-decoration:none;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/LLM_feedback.php?contentstype='.$contentstype.'&contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'"target="_blank">씽크 얼라우드</a></b> <span class="tooltiptext3"><table><tr><td style="overflow-wrap: break-word; "> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div><br> <br>'.$mathexpression.'</td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$recordlog->gptresult.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			else $papertest.='<tr><td width=8%  style="background-color:#1080E9;white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|   <hr><table align=center><tr><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_record.php?id='.$wboardid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/thinkaloud.jpg"  width=50%></a></td></tr></table></td><td width=10% align=center><div class="tooltip3">  <b><a style="color:#1080E9;text-decoration:none;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/LLM_feedback.php?contentstype='.$contentstype.'&contentsid='.$contentsid.'&wboardid='.$wboardid.'&studentid='.$studentid.'"target="_blank">씽크 얼라우드</a></b> <span class="tooltiptext3"><table><tr><td  style="overflow-wrap: break-word; "> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div> <br><br>'.$mathexpression.'</td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.$recordlog->gptresult.'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			}
		else
			{ 
			if($value['student_check']==1)$submitted.='<tr><td width=8% style="background-color:#FFA389;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번| <table ><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br>'.$instructionBtn.'<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 360, "utf-8").'</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';

			elseif($status==='attempt' && $value['nretry']==0)$papertest.='<tr><td width=8%  style="background-color:#DBFFD0;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  <table ><tr><td>'.$complementary.' '.$status.'</td></tr></table> </td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			elseif($status==='flag')$papertest.='<tr><td width=8%  style="background-color:#ffe5de;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번| <table><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'; // 고민지점
			elseif($status==='attempt' && $value['nretry']>0)$papertest.='<tr><td width=8%  style="background-color:#92EA78;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" >'.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번| <table ><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% align=center><div class="tooltip3"> '.$result.' <span class="tooltiptext3"><table><tr><td> <img loading="lazy"  src='.$imgSrc.' width=100%></td></tr></table></span></div></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($reflections, 0, 60, "utf-8").'...</td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			elseif($status==='complete' || $status==='review') $papertest.='<tr><td width=8% style="background-color:#08BA4A;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  <table><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($qtext->reflections1, 0, 80, "utf-8").'...</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			elseif($status==='begin' || $status==='exam')  $papertest.='<tr><td width=8% style="background-color:#ffe5de;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  <table><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($qtext->reflections1, 0, 80, "utf-8").'...</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			else $papertest.='<tr><td width=8% style="background-color:#965745;white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" valign=top><br> '.$checkout.' '.$timestamp.' | '.$nstroke.'획 / '.$usedtime.' | '.$ncommit.'번|  <table><tr><td>'.$complementary.' '.$status.'</td></tr></table>  </td><td width=10% >  <img loading="lazy"  src="'.$imgSrc.'" width=400></td><td width=3% style="white-space:nowrap;" align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"> 📝</a> <br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a><br><br><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/submit_speech.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"> <img loading="lazy"  src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/mic.png" width=20></a></td><td> <span style="font-size:16px;">'.iconv_substr($qtext->reflections1, 0, 80, "utf-8").'...</span></td></tr><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>';
			}
		}
	}
	
	if($quizstatus==1)$currentstatus='테스트 응시중';
	else $currentstatus='자유활동';
 

	$subjectnav= '<div id="tableContainer" style="background-color:#F0F1F4;"><table  width=100%><tr><td><img style="margin-top:5px;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/createtimefolding.png" width=40></td><td  style="color:black">&nbsp; 
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=95&nch=1&studentid='.$studentid.'&type=init">초등3-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=96&nch=1&studentid='.$studentid.'&type=init">초등3-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=73&nch=1&studentid='.$studentid.'&type=init">초등4-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=74&nch=1&studentid='.$studentid.'&type=init">초등4-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=75&nch=1&studentid='.$studentid.'&type=init">초등5-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=76&nch=1&studentid='.$studentid.'&type=init">초등5-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=78&nch=1&studentid='.$studentid.'&type=init">초등6-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=79&nch=1&studentid='.$studentid.'&type=init">초등6-2</a> <hr>
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=66&nch=1&studentid='.$studentid.'&type=init">중1-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=67&nch=1&studentid='.$studentid.'&type=init">중1-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=68&nch=1&studentid='.$studentid.'&type=init">중2-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=69&nch=1&studentid='.$studentid.'&type=init">중2-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=71&nch=1&studentid='.$studentid.'&type=init">중3-1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=72&nch=1&studentid='.$studentid.'&type=init">중3-2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=59&nch=1&studentid='.$studentid.'&type=init">수상</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=60&nch=1&studentid='.$studentid.'&type=init">수하</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=61&nch=1&studentid='.$studentid.'&type=init">수1</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=62&nch=1&studentid='.$studentid.'&type=init">수2</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=64&nch=1&studentid='.$studentid.'&type=init">확통</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=63&nch=1&studentid='.$studentid.'&type=init">미적</a> |
	<a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=65&nch=1&studentid='.$studentid.'&type=init">기하</a></td></tr></table></div>';
	  
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

	<table align=center width=90%><tr><th valign=top><div class="table-wrapper"><table width=100%><thead><tr><th style="white-space: nowrap;" width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$studentid.'&type=init"> <img loading="lazy"  src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=70></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/fixnote.php?userid='.$studentid.'"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/fixnote.png" width=40></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/activelearningnote.php?userid='.$studentid.'"> <img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/commitnote.png" width=40></a>
	
	</th><th style="color:#1956FF;font-size:20px;" width=30%><a style="text-decoration:none;color:#08090B;font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">'.$stdname.'</a> '.$eventspaceanalysis.' ('.$currentstatus.')</th><th width=5% style="white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;">'.$ForDeepLearning.'</th><th style="white-space: nowrap; color:white;overflow: hidden; text-overflow: ellipsis;">'.$subjectnav.'</th></tr></thead><tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>'.$papertest.'<hr>'.$submitted.'</table></div></th></tr></table>'; 
  
if($USER->id==2)echo '<br><br><br><br><br><table width=90% align=center>
<tr><td># 의심활동 선택 >> 공부순서 교정 >> 비효율적 피드백 감소 >> 학습루틴 고도화 >> 인지리듬 개선 >> 학습 기울기 상승 >> 성적향상</td></tr></table>
<hr>
<table width=90% align=center><tr><td># 순서교정 :  개념요약 >> 개념이해 >> 개념체크 >> 개념퀴즈 >> 대표유형 >> 기억인출 >> 대표유형 확인 테스트 >> 단원별 테스트</td></tr>
<tr><td># 자동추천 알고리즘 적용. preset 제공 후 업데이트 환경. 1. 학습상황 구조화,   2. 학생정보 구조화  3. 추천 컨텐츠 구조화</td></tr>
</table> ';

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
	document.addEventListener("DOMContentLoaded", function() {
		const tableContainer = document.getElementById("tableContainer");
		
		document.addEventListener("mousemove", function(event) {
		  const rect = tableContainer.getBoundingClientRect();
		  const x = event.clientX, y = event.clientY;
	
		  if (x > rect.left && x < rect.right && y > rect.top && y < rect.bottom) {
			tableContainer.classList.add("active");
		  } else {
			tableContainer.classList.remove("active");
		  }
		});
	  });

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
	a {
		user-drag: none; /* for WebKit browsers including Chrome */
		user-select: none; /* for standard-compliant browsers */
		-webkit-user-drag: none; /* for Safari and Chrome */
		-webkit-user-select: none; /* for Safari */
		-moz-user-select: none; /* for Firefox */
		-ms-user-select: none; /* for Internet Explorer/Edge */
	}
	img {
		user-drag: none; /* for WebKit browsers including Chrome */
		user-select: none; /* for standard-compliant browsers */
		-webkit-user-drag: none; /* for Safari and Chrome */
		-webkit-user-select: none; /* for Safari */
		-moz-user-select: none; /* for Firefox */
		-ms-user-select: none; /* for Internet Explorer/Edge */
	}
	a, a:visited {
		color: black;
	  }
	#tableContainer {
		opacity: 0;
		transition: opacity 0.5s ease;
	  }
	  #tableContainer.active {
		opacity: 1;
	  } 
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
	</>
';
?>
