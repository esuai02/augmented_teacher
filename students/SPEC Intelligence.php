<?php 
$hoursago=$timecreated-604800;
$nnote=0;
$lastthread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where studentid LIKE '$studentid' AND timecreated>'$timestart2' ORDER BY id DESC LIMIT 1 ");  

if($lastthread->id==NULL)echo '';
elseif($lastthread->status==='complete') 
	{ 
	$ankithread='<b>ANKI 퀴즈<b><br> <table align=center width=100%><tr>';
	if($lastthread->quiz1!=NULL)$ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz1.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz2!=NULL)$ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz2.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz3!=NULL)$ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz3.'&studentid='.$studentid.'"target="_blank">완료</a></td></tr>';
	if($lastthread->quiz4!=NULL)$ankithread2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz4.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz5!=NULL)$ankithread2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz5.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz6!=NULL)$ankithread2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz6.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz7!=NULL)$ankithread3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz7.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz8!=NULL)$ankithread3.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz8.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz9!=NULL)$ankithread3.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz9.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz10!=NULL)$ankithread4.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz10.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz11!=NULL)$ankithread4.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz11.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	if($lastthread->quiz12!=NULL)$ankithread4.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz12.'&studentid='.$studentid.'"target="_blank">완료</a></td>';
	$ankithread=$ankithread1.$ankithread2.$ankithread3.$ankithread4;
	$ankithread.='</table>';	
	}
else
	{ 
	$ankithread='<b>ANKI 퀴즈<b><br> <table align=center width=100%><tr>';
	if($lastthread->quiz1!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz1' ORDER BY id DESC LIMIT 1 ");  $ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz1.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz2!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz2' ORDER BY id DESC LIMIT 1 "); $ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz2.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz3!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz3' ORDER BY id DESC LIMIT 1 "); $ankithread1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz3.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td></tr>';}
	if($lastthread->quiz4!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz4' ORDER BY id DESC LIMIT 1 "); $ankithread2.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz4.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz5!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz5' ORDER BY id DESC LIMIT 1 "); $ankithread2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz5.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz6!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz6' ORDER BY id DESC LIMIT 1 "); $ankithread2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz6.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td></tr>';}
	if($lastthread->quiz7!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz7' ORDER BY id DESC LIMIT 1 "); $ankithread3.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz7.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz8!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz8' ORDER BY id DESC LIMIT 1 "); $ankithread3.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz8.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz9!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz9' ORDER BY id DESC LIMIT 1 "); $ankithread3.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz9.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td></tr>';}
	if($lastthread->quiz10!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz10' ORDER BY id DESC LIMIT 1 "); $ankithread4.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz10.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz11!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz11' ORDER BY id DESC LIMIT 1 "); $ankithread4.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz11.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td>';}
	if($lastthread->quiz12!=NULL){$ankiname=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where id='$lastthread->quiz12' ORDER BY id DESC LIMIT 1 "); $ankithread4.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$lastthread->quiz12.'&studentid='.$studentid.'"target="_blank">'.substr($ankiname->title,0,40).'</a></td></tr>';}
	$ankithread.=$ankithread1.$ankithread2.$ankithread3.$ankithread4;
	$ankithread.='</table>';	
	}

$handwriting1=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND (student_check=1 OR turn=1) AND hide=0 AND timemodified > '$hoursago' ORDER BY timemodified DESC LIMIT 100");

$result = json_decode(json_encode($handwriting1), True);
unset($value);

// Initialize variables for ground evaluation with show more functionality
$papertest_concept = '';  // 개념확인 items (first 5)
$papertest_concept_more = '';  // Additional 개념확인 items
$papertest_ground = '';   // 지면평가 items (first 5)
$papertest_ground_more = '';  // Additional 지면평가 items
$concept_count = 0;
$ground_eval_count = 0;
foreach($result as $value) 
	{
	if($value['boardtype']==='complementary' && $value['status']==='complete')continue;

	$wboardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
	$instruction=$value['instruction'];
	$noteurl=$value['url'];
	$status=$value['status'];
	$timestamp=$timecreated-$value['timemodified'];
	if($timestamp<=60)$timestamp=$timestamp.'초 전';
	elseif($timestamp<=3600)$timestamp=round($timestamp/60,0).'분 전';
	elseif($timestamp<=86400)$timestamp=round($timestamp/3600,0).'시간 전';
	elseif($timestamp<=2592000)$timestamp=round($timestamp/86400,0).'일 전';
	$nnote++;
	$instructionBtn='';
	if($instruction!==NULL)$instruction=' ('.$instruction.')';
	$checkout='<input type="checkbox" name="checkAccount"  Checked  onClick="ChangeCheckBox(213,\''.$studentid.'\',\''.$wboardid.'\', this.checked)"/>';
	if(strpos($wboardid, 'jnrsorksqcrark')!== false)
		{
		$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' "); // 전자책에서 가져오기
		$ctext=$getimg->pageicontent;
		$htmlDom = new DOMDocument;
		if($studentid==NULL)$studentid=2;
		$cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");  
 
		if(strpos($cnttext->reflections,'지시사항')!==false)$instructionBtn='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a>';
	
		
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
		$concept_count++;
		$concept_row = '<tr><td width=1% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$checkout.' '.$timestamp.'</td><td width=60% align=center> <div class="tooltip3"><b style="color:green;font-size:16;">개념확인</b><span class="tooltiptext3"><table style="" align=center><tr><td><img src="'.$imgSrc.'" width=300></td></tr></table></span></div></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a>  '.$instructionBtn.' </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a></td><td>  <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid.'"target="_blank">📝</a></td></tr>';
		
		// Limit concept check display to 5 items initially
		if($concept_count <= 5) {
			$papertest_concept .= $concept_row;
		} else {
			$papertest_concept_more .= $concept_row;
		}
		}
	elseif(strpos($wboardid, 'SPEC')!== false)
		{
		$papertest2.='<tr><td width=1% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$checkout.' '.$timestamp.'</td><td  align=center width=60% ><b>보충학습</b> '.$instruction.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_confirm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a>  </td><td> </td><td> </td></tr>';
		}
	else
		{
		$qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid' ");
		if(strpos($qtext->reflections1,'지시사항')!==false)$instructionBtn='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=20></a>';

		$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
		foreach($imageTags as $imageTag)
			{
			$imgSrc = $imageTag->getAttribute('src');
			$imgSrc = str_replace(' ', '%20', $imgSrc); 
			if(strpos($imgSrc, 'MATRIX/MATH')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
			} 
		$ground_eval_count++;
		$ground_eval_row = '<tr><td width=1% style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$checkout.' '.$timestamp.'</td><td align=center  width=60%> <div class="tooltip3"> <b style="color:orange;">지면평가</b><span class="tooltiptext3"><table style="" align=center><tr><td><img src="'.$imgSrc.'" width=300></td></tr></table></span></div></td><td width=2%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_confirm.php?id='.$wboardid.'"target="_blank"> <img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/createnote.png" width=20></a> '.$instructionBtn.' </td><td width=2%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$wboardid.'&contentsid='.$contentsid.'&contentstype=2"target="_blank"><img loading="lazy"  src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=20></a> </td><td  width=2%><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank">📝</a></td></tr>';
		
		// Limit ground evaluation display to 5 items initially
		if($ground_eval_count <= 5) {
			$papertest_ground .= $ground_eval_row;
		} else {
			$papertest_ground_more .= $ground_eval_row;
		}
		} 
	}
	$timestart2=time()-604800;
	$adayAgo=time()-43200;
	$aweekAgo=time()-604800;


	$handwriting2=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND  status NOT LIKE 'attempt'  AND  status NOT LIKE 'complete'  AND tlaststroke>'$timestart2' AND contentstype=2 AND  (active=1 OR status='flag' )  ORDER BY tlaststroke DESC LIMIT 100 ");

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
	$result1 = json_decode(json_encode($handwriting2), True);
	unset($value);
	$wboardlist.= '<tr><td></d><td></d><td></d><td></d></tr>';
	foreach($result1 as $value) 
	{
	if($value['synapselevel']>0)
		{
		$nsynapse++;
		$sumSynapse=$sumSynapse+$value['synapselevel'];
		}
	$nnote++;
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
		//$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245210.png width=15></a>';  
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
	if($value['status']==='review' && $value['hide']==0)$hidewb=' <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank">📝</a> <input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
	elseif($value['hide']==1 && $value['status']==='review' && $role!=='student' )$hidewb=' <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank">📝</a> <input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>  <img loading="lazy" style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659836193.png" width=20>';
	elseif($role!=='student')$hidewb=' <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=2&studentid='.$studentid.'"target="_blank">📝</a> <input type="checkbox" name="checkAccount"    onClick="ChangeCheckBox2(111,\''.$studentid.'\',\''.$encryption_id.'\', this.checked)"/>';
	$cntinside=' ('.$nstroke.'획) </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?id='.$encryption_id.'&tb=604800" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="'.$bstrateimg.'" width=15></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'&speed=+9"target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1659245794.png" width=15></a>';
	if($value['status']==='flag' && $value['timemodified']>$adayAgo && $value['contentstitle']!=='incorrect' )
		{
		$bstep=$DB->get_record_sql("SELECT * FROM mdl_abessi_firesynapse WHERE wbtype=1 AND contentsid='$contentsid' AND contentstype='2' AND userid='$studentid' ORDER BY id DESC LIMIT 1 ");
		$nstroke=$bstep->nstroke;
	
		if($value['helptext']==='OK' || ($nstroke>15 && $bstep->nthink==0)){ $nthinktext='OK'; $imgstatus='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1600204469001.png" width="15"> 책갈피';  }
		elseif($bstep->nthink>=3)$nthinktext='<b style="color:red;">고민지점 '.$bstep->nthink.'곳</b>';
		elseif($bstep->nthink>=1)$nthinktext='<b style="color:blue;">고민지점 '.$bstep->nthink.'곳</b>';
		elseif($bstep->nthink==0) $nthinktext='<b style="color:red;">check !</b>';
	
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
	  
 
echo '<br><table align=center width=90%><tr><td valign=top>'.$ankithread.'<table id="main-content-table">';

// Display concept check section with its show more content right after
echo $papertest_concept;
if($concept_count > 5) {
    echo '<tbody id="concept-more" style="display: none;">'.$papertest_concept_more.'</tbody>';
    echo '<tr id="concept-buttons-row"><td colspan="5" align="center" style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
        <button id="concept-show-more-btn" onclick="toggleConceptShowMore()" style="background: linear-gradient(135deg, #10B981 0%, #059669 50%, #047857 100%); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px;">
            개념확인 더보기 ('.(($concept_count - 5)).'개 더)
        </button>
        <button id="concept-show-less-btn" onclick="toggleConceptShowMore()" style="display: none; background: linear-gradient(135deg, #6B7280 0%, #9CA3AF 50%, #6B7280 100%); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px;">
            개념확인 접기
        </button>
    </td></tr>';
}

// Display ground evaluation section with its show more content right after
echo $papertest_ground;
if($ground_eval_count > 5) {
    echo '<tbody id="ground-eval-more" style="display: none;">'.$papertest_ground_more.'</tbody>';
    echo '<tr id="ground-buttons-row"><td colspan="5" align="center" style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
        <button id="ground-show-more-btn" onclick="toggleGroundShowMore()" style="background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px;">
            지면평가 더보기 ('.(($ground_eval_count - 5)).'개 더)
        </button>
        <button id="ground-show-less-btn" onclick="toggleGroundShowMore()" style="display: none; background: linear-gradient(135deg, #6B7280 0%, #9CA3AF 50%, #6B7280 100%); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px;">
            지면평가 접기
        </button>
    </td></tr>';
}

echo '<tr><td></td><td></td><td></td><td></td><td></td></tr>';

echo $papertest2.'</table>
<table width=90%>'.$wboardlist0.''.$wboardlist1.''.$reviewwb.$reviewwb2.''.$wboardlist2.'</table></td></tr></table>';

if($nnote==0) echo '<table align=center width=50%><tr><td align=center><b style="font-size:20;">KAIST TOUCH MATH</b></td></tr><tr><td align=center><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/cleared.png" width=100%></td></tr></table>';

// Add JavaScript for show more functionality
if($concept_count > 5 || $ground_eval_count > 5) {
    echo '
    <script>
    function toggleConceptShowMore() {
        var moreSection = document.getElementById("concept-more");
        var showMoreBtn = document.getElementById("concept-show-more-btn");
        var showLessBtn = document.getElementById("concept-show-less-btn");
        
        if(moreSection.style.display === "none" || moreSection.style.display === "") {
            // Show the additional content seamlessly after concept check items
            moreSection.style.display = "table-row-group";
            showMoreBtn.style.display = "none";
            showLessBtn.style.display = "inline-block";
        } else {
            // Hide the additional content
            moreSection.style.display = "none";
            showMoreBtn.style.display = "inline-block";
            showLessBtn.style.display = "none";
        }
    }
    
    function toggleGroundShowMore() {
        var moreSection = document.getElementById("ground-eval-more");
        var showMoreBtn = document.getElementById("ground-show-more-btn");
        var showLessBtn = document.getElementById("ground-show-less-btn");
        
        if(moreSection.style.display === "none" || moreSection.style.display === "") {
            // Show the additional content seamlessly after ground evaluation items
            moreSection.style.display = "table-row-group";
            showMoreBtn.style.display = "none";
            showLessBtn.style.display = "inline-block";
        } else {
            // Hide the additional content
            moreSection.style.display = "none";
            showMoreBtn.style.display = "inline-block";
            showLessBtn.style.display = "none";
        }
    }
    </script>';
}

echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
	 
echo '
	<script> 
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
	}
	</script>
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
';
?>
