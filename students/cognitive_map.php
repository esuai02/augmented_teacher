<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
$studentid=required_param('id', PARAM_INT); 
$period=required_param('period', PARAM_INT); 
$tbegin=time()-86400*$period;

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");

		$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempt_steps.timecreated AS timecreated, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
		LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
		WHERE mdl_question.name LIKE '%MX%' AND mdl_question_attempt_steps.userid='$studentid' AND  state NOT LIKE 'todo' AND  state NOT LIKE 'complete' AND  mdl_question_attempt_steps.timecreated > '$tbegin' ORDER BY mdl_question_attempt_steps.timecreated DESC ");
		$result1 = json_decode(json_encode($questionattempts), True);
		$nattempts=count($questionattempts);
		$marks=NULL;
		unset($value);
		$ntry=0; 
		$ninit=0;
		$ngaveup=0;
		foreach(array_reverse($result1) as $value)
			{
			$state='';
 			$timediff=time()-$value['timecreated'];
			 
			$status='';
			$attemptid=$value['id'];
 
			$htmlDom = new DOMDocument; @$htmlDom->loadHTML($value['questiontext']); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();
			foreach($imageTags as $imageTag)
				{
    				$questionimg = $imageTag->getAttribute('src');$questionimg = str_replace(' ', '%20', $questionimg); if(strpos($questionimg, 'MATRIX/MATH')!= false)break;
				}
			$questionimg='<img src="'.$questionimg.'" width=500>';
			if($value['state']==gradedright && $timediff<86400) $state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right1.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
			elseif($value['state']==gradedright)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right2.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
			elseif($value['state']==gradedpartial && $timediff<86400)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial1.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
			elseif($value['state']==gradedpartial)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial2.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
			elseif($value['state']==gradedwrong && $timediff<86400)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong1.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;'; 
			elseif($value['state']==gradedwrong)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
			elseif($value['state']==gaveup && $timediff<86400)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/gaveup2.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;'; 
				$ngaveup++;
				}
			elseif($value['state']==gaveup)
				{
				$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="http://mathking.kr/Contents/IMAGES/gaveup1.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';		
				$ngaveup++;
				}
			//elseif($value['state']==complete && $timediff<86400)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete1.png" width=20><span class="tooltiptext2">'.$questionimg.'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
		 	//elseif($value['state']==complete)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete2.png" width=20></a>&nbsp;';
			$marks.=$state; 
			}
		$nattempts=$nattempts-$ngaveup;
 
		$feedback = $DB->get_records_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$studentid' AND timecreated > '$tbegin' ORDER BY id DESC ");
		$result2 = json_decode(json_encode($feedback), True);
		unset($value);
		foreach($result2 as $value)
			{
			if($value['type']==='개선요청') $feedbacklog2.='<tr><td width=90%>'.$value['feedback1'].'</td><td width=10%><a href="'.$value['context'].'?'.$value['url'].'" target="_blank">보기</a></td><td width=10%>'.date("m/d",$value['timecreated']).'</td></tr>';
			else $feedbacklog1.='<tr><td width=90%>'.$value['feedback1'].'</td><td></td><td width=10%>'.date("m/d",$value['timecreated']).'</td></tr><tr><td><hr></td><td><hr></td><td><hr></td></tr>';
			}
 
		$cmtquiz = $DB->get_records_sql("SELECT id, comment, timemodified FROM mdl_quiz_attempts WHERE userid='$studentid' AND comment NOT LIKE 'NULL' AND timemodified>'$tbegin' ORDER BY id DESC ");
		$result3 = json_decode(json_encode($cmtquiz), True);
		unset($value);
		foreach($result3 as $value)
			{
			$feedbacklog3.='<tr><td width=80%>'.$value['comment'].'</td><td width=10%><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$value['id'].'" target="_blank">보기</a></td><td width=10%>'.date("m/d",$value['timemodified']).'</td></tr>';
			}

		echo $marks.'<hr><p align=center>  최근 '.$period.'일 동안 총 '.$nattempts.'문제 시도</p><hr>';
 

echo '<div class="card-header" style="background-color:limegreen">
<div class="card-title" ><table style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" align=center ><td  style="width: 7%; padding-left: 1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><table align=center style="1px;padding-bottom:3px; font-size: 20px; color:#ffffff;"><tr><td><b><a style="color:#0066cc;" href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"target="_blank">'.$studentname.'</a></b>주요 학습 데이터 (상담자료) </td><td  width=5% ></td><td style="font-size:14px;"> <b> We transfer intelligence  - KTM powered by CJN</b> </td></tr></table></td></tr></table></div></div>  ';
  
 echo ' <table align=center> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">진행 상황</td><td width=5%>cid/topic_id</td><td>최근 진행 중인 강좌와 다음으로 진행할 강좌에 대한 정보를 토대로 중기학습 계획에 대해 안내해 드릴 수 있습니다.<td width=2%></td></td></tr>
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">활동 결과</td><td width=5%>quid data</td><td>최근 푼 문제의 난이도, 유형, 점수 및 원활도에 대한 정보를 토대로 공부의 원활도에 대해 안내해 드릴 수 있습니다.<td width=2%></td></td></tr>
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">오늘 평점</td><td width=5%>백분율</td><td>오늘 답을 구한 문제의 정답률을 의미합니다. 만약 90%라면 정답을 선택한 문제 중 90%가 정답임을 의미합니다. 정답을 선택하지 않으면 평점이 떨어지지 않습니다. 따라서 판단한 내용이 얼마나 정확한지를 나타내게 되고 이것이 우리가 평점을 침착도라고 부르는 이유입니다.<td width=2%></td></td></tr>
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">최근 평점</td><td width=5%>백분율</td><td>최근 한달 동안의 평점(침착도)를 의미합니다. 학생의 공부습관이 얼마나 안정적으로 형성되었는지를 판단하기 위한 근거 중 하나로 사용됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">공부시간</td><td width=5%>시간표 데이터</td><td>주간 공부시간이 시스템에 의하여 측정이 됩니다. 만약 집중을 하지 않는다면 머신러닝 알고리즘에 의하여 해당 시간이 측정 결과에 제외될 수 있습니다. 치팅이 힘들므로 <b>순공시간</b>으로 볼 수 있습니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">풀이양</td><td width=5%>n_attempts</td><td>문제를 얼마나 많이 풀었는지를 나타냅니다. 문항의 수가 너무 적다면 공부의 흐름에 문제가 발생한 것으로 볼 수 있으므로 학생상담을 통하여 자세한 원인을 분석하고 동기부여, 활동방법 등을 다각적으로 검토하여 개선활동을 진행합니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">풀이효율</td><td width=5%>quiz_data</td><td>최근 1주일 동안 시간당 풀었던 문항수에 대한 시간 평균값으로 계산됩니다. 이 값이 너무 작다면 시간이 낭비되고 있거나 혼자 비효율적으로 공부가 진행되고 있을 가능성이 있습니다. 이상 데이터 발견시 조치를 취하게 됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10%>오늘목표</td><td width=5%>contentslist,text</td><td>주간목표를 토대로 오늘목표를 설정합니다. 효과적인 배분인지 검토한 다음 결과가 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>주간목표</td><td width=5%>contentslist,text</td><td>중간목표를 토대로 주간목표를 설정합니다. 효과적인 배분인지 검토한 다음 결과가 표시됩니다.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>분기목표</td><td width=5%>chapter,text</td><td>장기계획에 쓰여있는 코스의 순서를 토대로 지정된 기간 동안 성취할 목표를 정합니다. 주어진 시간과 현재의 학년, 학교성적 목표 등을 토대로 적합성 여부를 판단할 수 있습니다. 그 결과가 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>장기계획</td><td width=5%>curriculum</td><td>학습코스(개념, 내신, 심화 등)의 순서를 정합니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>시간계획</td><td width=5%>timetable</td><td>주별 시간 계획입니다. 수학공부는 집중력이 유지가 된다면 연속적으로 진행되는 것이 효과적일 수 있습니다. 시간표가 효과적으로 짜여져 있는지를 검토해 볼 수 있습니다. 요일별 데이터 상에서 저조한 성취도가 도드라진다면 시간표를 변경하는 것을 고려할 수 있습니다.<td width=2%></td></td></tr>  
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 


 <tr><td width=2%></td><td width=10% style="color:blue;">공부방법 체화</td><td width=5%>선택데이터</td><td>개념공부, 향상노트, 고민지점 극복, 발표활동, 부스터 활동 등에 대한 전반적은 활용정도에 대한 평가지표<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">개념공부</td><td width=5%>topic</td><td>개념공부는 내용을 이해한 다음 충분히 체화될 수 있도록 부스터 활동을 진행하게 되어 있습니다. 또한 노트검색을 통하여 능동적인 개념 복습활동이 이루어지는지도 확인이 되어져야 합니다. 또한 대표유형 수준의 개념적용 단계까지 잘 진행되는지도 점검의 대상입니다. 평가 결과가 데이터로 표시됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">향상노트 활용</td><td width=5%>whiteboard</td><td>풀이노트, 평가준비, 서술평가가 원활하게 진행되고 있는지 여부. 처리시간에 대한 관성이 아니라 논리훈련 중심으로 확실히 전환되어 있는지 여부 등을 토대로 평가.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">고민지점 활용</td><td width=5%>t_interval</td><td>풀이과정에서 능숙도가 낮은 지점을 찾아서 반복훈련을 통하여 실질적은 논리훈련을 하고 있는지 여부를 토대로 판단. 풀이시간 단축의 루틴을 체득하였는지를 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">발표활동 활용</td><td width=5%>지면평가</td><td>Think Alound 방식의 인지촉진, 인지성장 활동을 효과적으로 활용하고 있는지에 대한 평가. 학습정체 구간에 대한 효과적인 해소 및 취약지점에 대한 인지적 상태변화의 수단으로 잘 활용되는지 여부를 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">부스터 활동 활용</td><td width=5%>retention</td><td>논리훈련의 수단으로 부스터 활동을 얼마나 잘 활용하고 있는지에 대한 평가. 개념, 공식 체화, 논리훈련 등 필요한 부분마다 효과적으로 자발적 부스터 활동을 실행하고 있는지에 여부에 대한 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">피어러닝 활용</td><td width=5%>npeer</td><td>피어러닝 환경을 셋업하여 Social learning을 통한 동기부여 및 몰입향상을 경험하고 루틴화되어 있는지 여부에 대한 평가<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10%>내신테스트 현황</td><td width=5%>nmocking</td><td>설정된 분기목표와 중간목표에 맞추어 효과적인 일정으로 내신테스트가 진행되고 있는지에 대한 평가. <td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>심화학습 현황</td><td width=5%>ndifficulty</td><td>학습의 단계에 맞게 심화문제를 적절히 배합한 방식의 커리큘럼이 운영되고 있는지 여부에 대한 평가. 심화문제에 대한 학생의 도전 특성에 대한 평가.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%>문항난이도 분포</td><td width=5%>ldifficulty</td><td>최근 다루고 있는 문항들의 난이도에 대한 데이터를 표시합니다. 학생의 현재 시험대비 정도, 학교시험 난이도와의 적합성 등의 판단 근거로 사용됩니다.<td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 

 <tr><td width=2%></td><td width=10% style="color:blue;">학생 상담내용</td><td  width=5%>feebacktext</td><td>최근 학생상담 중 특이사항이 있는 내용을 표시합니다. 부모님과의 소통 및 의견 제시를 위해 활용될 수 있습니다.<td width=2%></td></td></tr>   
 <tr><td width=2%></td><td width=10%><hr></td><td width=5%><hr></td><td><hr><td width=2%></td></td></tr> 
 <tr><td width=2%></td><td width=10% style="color:blue;">향후 전망</td><td width=5%>text</td><td>최근 1년동안의 학습의 추이, 습관형성, 몰입(flow) 단계 변화 등을 토대로 현재 학생의 학습 흐름이 하락, 유지, 상승인지를 판단할 수 있습니다. 학부모 상담에 활용될 수 있습니다.<td width=2%></td></td></tr>   

</table>';
 
echo '<hr><table width="100%"><tr><td>난이도</td><td><img  src="https://play-lh.googleusercontent.com/PkNdm5zWBQoe7JVYWu_b3fyw8SxkeeF8EkZiGKc71LOAj1-BNaWREVkUf_Asqfq4_Co" width=50 ></td><td>상태</td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/departure.gif" width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flying.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1604216426001.png"   width=200 ></td><td><img   src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/flyingthroughfield.gif"  width=200 ></td><td><img   src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646909102.png" width=200  ></td></tr></table><hr>
<table width=95% align="center"><tr><th width="33%">학습루브릭</th><th width="3%"></th><th width="28%">개선요청</th><th width="3%"></th><th width="28%">퀴즈 및 오답노트 (CogTalk)</th></tr>
<tr><td valign="top"><hr> </td><td ></td><td valign="top"> <hr> </td><td ></td><td><hr> </td></tr>		   
<tr><td valign="top"><table>'.$feedbacklog1.'</table></td><td ></td><td valign="top"><table>'.$feedbacklog2.'</table></td><td ></td><td valign="top"><table>'.$feedbacklog3.'</table></td></tr></table>';

echo '  <hr>
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 500px;
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
 

.tooltip1:hover .tooltiptext1 {
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
  width: 500px;
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
  width: 500px;
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