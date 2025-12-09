<?php 

	$cntitemid1=$curri->cntitem1;
	$cntitemid2=$curri->cntitem2;
	$cntitemid3=$curri->cntitem3;
	$cntitemid4=$curri->cntitem4;
 	$cntitemid5=$curri->cntitem5;
 	$cntitemid6=$curri->cntitem6;
	$prepexam=$curri->contentslist;  	
	$knowhow=$curri->knowhow;  
	if($role!=='student')$teachertext='&nbsp;&nbsp;&nbsp;<a href="'.$knowhow.'" target="_blank"> 커리큘럼</a>&nbsp; | &nbsp;<a href="https://docs.google.com/document/d/10mtwkRyQ6sGjSUDrN4c39FuGPoCWu3BovqI-Oibnyzo/edit" target="_blank">노하우</a>';
 	$getcntitems=$DB->get_records_sql("SELECT instance FROM mdl_course_modules WHERE id='$cntitemid1' OR  id='$cntitemid2' OR  id='$cntitemid3' OR  id='$cntitemid4' OR  id='$cntitemid5' OR  id='$cntitemid6' "); 
 	$getresult = json_decode(json_encode($getcntitems), True);
	$num=1; 
	unset($value);
	foreach($getresult as $value)
		{
		$instance=$value['instance'];
		$contents = $DB->get_records_sql("SELECT * FROM mdl_checklist_item LEFT JOIN mdl_checklist_check ON mdl_checklist_item.id=mdl_checklist_check.item  WHERE mdl_checklist_check.userid='$studentid' AND mdl_checklist_check.usertimestamp >10 AND mdl_checklist_item.checklist='$instance' ORDER BY usertimestamp ASC "); 
		$result2 = json_decode(json_encode($contents), True);
		
		unset($value2);
		foreach($result2 as $value2)
			{
			$name='quiz'.$num;
			$expl = explode('{if',$value2['displaytext']);
			$$name=$expl[0];
			$quizid='quizid'.$num;
			$$quizid=$value2['moduleid'];
			if($value2['moduleid']==0)$$quizid=str_replace("https://mathking.kr/moodle/mod/quiz/view.php?id=","",$value2['linkurl']);
			$num++;
			} 
		}
 

 
echo ' 		 <div class="col-md-12">              
		<div class="card"> 
			<div class="card-body">
				<!-- 탭 네비게이션 -->
				<div class="tab-navigation-wrapper">
					<ul class="nav nav-pills nav-secondary justify-content-center" id="pills-tab" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">
								<i class="fas fa-file-alt"></i> 모의시험 (D-'.$leftDays.')
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="pills-profile1-tab" data-toggle="pill" href="#pills-profile1" role="tab" aria-controls="pills-profile1" aria-selected="false">
								<i class="fas fa-clipboard-check"></i> 오답노트 <span class="badge badge-count badge-success">'.$count1.'</span>
							</a>											 	 
						</li>
					</ul>
				</div>
				
				<!-- 탭 콘텐츠 -->
				<div class="tab-content mb-3" id="pills-tabContent">
					<!-- 첫 번째 탭: 모의시험 -->
					<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">  ';
 
 	$nmax=$num-1;
	for($nch=1;$nch<=$nmax;$nch++)
		{
		$qname='quiz'.$nch;
		$quizname=$$qname;	
		$quizid='quizid'.$nch;
		$qid=$$quizid;
		if($qid==NULL)continue; 


		$passgrade='grade'.$nch;
		$hours='hours'.$nch;
		$dday='dday'.$nch;

		$$passgrade=$mission->$passgrade;
		$$hours=$mission->$hours;
		$$dday=$mission->$dday;
		$ddaystamp = strtotime($$dday);
		$tbegin=time()-$tbegin;
		$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
		FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.timefinish>'$tbegin' AND mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 
		$grade=round($quizattempt->sgrades/$quizattempt->tgrades*100,0); 
		$attemptdate=date("Y-m-d", $quizattempt->timestart);
 		if($quizattempt->timestart<time()-86400*10000)
			{
			//$qresult='<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">응시전</a></td>';
			}else
			{
			$timedue=$ddaystamp-$quizattempt->timestart;
			if($timedue>0)
				{
				$img_deadline='_<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=15>';
				$DB->execute("UPDATE {abessi_indicators} SET deadline='1' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
				}
			else 
				{
				$img_deadline='_<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=15>';
				$DB->execute("UPDATE {abessi_indicators} SET deadline='2' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");  
				}
			$qresult='<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$grade.'점 / '.$attemptdate.$img_deadline.'</a></td>';
			}		
		$mockingtests.= '<tr><td>&nbsp;&nbsp;&nbsp;'.$nch.'</td>	<td align=center><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$quizname.'</td><td>'.$$passgrade.'점</td>'.$qresult.'<td> </td><td>'.$$dday.'</td></tr>';
		}
		 
$cnt1=$curri->cnt1;
$cnt2=$curri->cnt2;
$cnt3=$curri->cnt3;
$cnt4=$curri->cnt4;
$cnt5=$curri->cnt5;
$cnt6=$curri->cnt6;
$cnt7=$curri->cnt7; 
$cnt8=$curri->cnt8;
$cnt9=$curri->cnt9;
//$cnt10=$curri->cnt10;
$cnt11=$curri->cnt11;
$cnt12=$curri->cnt12;

$tbegin=time()-7776000;
/*
$courses=$DB->get_records_sql("SELECT * FROM mdl_abessi_exam WHERE subject='$cid' AND userid='$studentid' AND timecreated> '$tbegin' AND status LIKE '0' "); 
$result = json_decode(json_encode($courses), True);
unset($value);
foreach($result as $value)
	{
	$inputid=$value['id'];
	if($value['type']==='교과개념')$contentslist1.='<tr><td width=10%></td><td><input type="checkbox" name="checkAccount"   onClick="Checkstatus(100,\''.$studentid.'\',\''.$inputid.'\', this.checked)"/> </td><td>교과개념 : '.$value['inputtext'].'</td><td>deadline</td></tr>'; 
	if($value['type']==='대표유형')$contentslist2.='<tr><td width=10%></td><td><input type="checkbox" name="checkAccount"   onClick="Checkstatus(100,\''.$studentid.'\',\''.$inputid.'\', this.checked)"/> </td><td>대표유형 : '.$value['inputtext'].'</td><td>deadline</td></tr>'; 
	if($value['type']==='유형단련')$contentslist3.='<tr><td width=10%></td><td><input type="checkbox" name="checkAccount"   onClick="Checkstatus(100,\''.$studentid.'\',\''.$inputid.'\', this.checked)"/> </td><td>유형단련 : '.$value['inputtext'].'</td><td>deadline</td></tr>'; 
	if($value['type']==='심화미션')$contentslist4.='<tr><td width=10%></td><td><input type="checkbox" name="checkAccount"   onClick="Checkstatus(100,\''.$studentid.'\',\''.$inputid.'\', this.checked)"/> </td><td>심화미션 : '.$value['inputtext'].'</td><td>deadline</td></tr>'; 
	if($value['type']==='인지촉진')$goaltext5=$value['inputtext'];
	if($value['type']==='보강학습')$goaltext6=$value['inputtext'];
	if($value['type']==='주제단련')$goaltext7=$value['inputtext'];
	}
 
 
/////////////////////////////////////  보충학습 추가하기/////////////////////////////////////////
$options=$DB->get_records_sql("SELECT instance FROM mdl_course_modules WHERE id='$cnt5' OR  id='$cnt6' OR  id='$cnt7' "); 
$result2 = json_decode(json_encode($options), True);
$num=1; 
unset($value);
foreach($result2 as $value)
	{
	$instance=$value['instance'];
	$contents = $DB->get_records_sql("SELECT * FROM mdl_checklist_item LEFT JOIN mdl_checklist_check ON mdl_checklist_item.id=mdl_checklist_check.item  WHERE mdl_checklist_check.userid='$studentid' AND mdl_checklist_check.usertimestamp >10 AND mdl_checklist_item.checklist='$instance' ORDER BY usertimestamp ASC "); 
	$result3 = json_decode(json_encode($contents), True);
		
	unset($value2);
	foreach($result3 as $value2)
		{
		$name='item'.$num;
		$expl = explode('{if',$value2['displaytext']);
		$$name=$expl[0];
		$itemid='itemid'.$num;
		$$itemid=$value2['moduleid'];
		if($value2['moduleid']==0)$$itemid=str_replace("https://mathking.kr/moodle/mod/checklist/view.php?id=","",$value2['linkurl']); 
		$num++;
		} 
	}

$contentslist5.='<tr style="background-color:skyblue;height:50px;"><td width=10% style="background-color:green;color:white;text-align:center;">인지촉진</td><td>  '.$goaltext5.'</td><td width=1%><span onClick="showChecklist(\''.$cnt5.'\')" accesskey="m"><i class="flaticon-plus"></i></a></span>  </td><td width=1%><button   type="button"   id="alert_planE"  style = "color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=20></button></td></tr> ';
$contentslist6.='<tr style="background-color:skyblue;height:50px;"><td width=10% style="background-color:green;color:white;text-align:center;">보강학습</td><td>  '.$goaltext6.'</td><td width=1%><span onClick="showChecklist(\''.$cnt6.'\')" accesskey="m"><i class="flaticon-plus"></i></a></span>  </td><td width=1%><button   type="button"   id="alert_planF"  style = "color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=20></button></td></tr> ';
$contentslist7.='<tr style="background-color:skyblue;height:50px;"><td width=10% style="background-color:green;color:white;text-align:center;">주제단련</td><td>  '.$goaltext7.'</td><td width=1%><span onClick="showChecklist(\''.$cnt7.'\')" accesskey="m"><i class="flaticon-plus"></i></a></span>   </a></td><td width=1%><button   type="button"   id="alert_planG"  style = "color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=20></button></td></tr> ';
*/
// 인지촉진, 보강학습, 주제단련 iframe 컨테이너 추가
$contentslist5.='<tr><td colspan="4" style="padding:0;"><div id="checklist-container-'.$cnt5.'" class="checklist-iframe-container inline-container" style="display:none;">
	<div class="loading-spinner">
		<div class="spinner"></div>
		<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
	</div>
	<iframe id="checklist-iframe-'.$cnt5.'" style="display:none;" src="" onload="hideLoading(\''.$cnt5.'\')"></iframe>
</div></td></tr>';

$contentslist6.='<tr><td colspan="4" style="padding:0;"><div id="checklist-container-'.$cnt6.'" class="checklist-iframe-container inline-container" style="display:none;">
	<div class="loading-spinner">
		<div class="spinner"></div>
		<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
	</div>
	<iframe id="checklist-iframe-'.$cnt6.'" style="display:none;" src="" onload="hideLoading(\''.$cnt6.'\')"></iframe>
</div></td></tr>';

$contentslist7.='<tr><td colspan="4" style="padding:0;"><div id="checklist-container-'.$cnt7.'" class="checklist-iframe-container inline-container" style="display:none;">
	<div class="loading-spinner">
		<div class="spinner"></div>
		<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
	</div>
	<iframe id="checklist-iframe-'.$cnt7.'" style="display:none;" src="" onload="hideLoading(\''.$cnt7.'\')"></iframe>
</div></td></tr>';

$nmax=$num-1;
for($nch=1;$nch<=$nmax;$nch++)
	{
	$itemname='item'.$nch;
	$cntname=$$itemname;	
	$itemid='itemid'.$nch;
	$listid=$$itemid;

	//$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$listid' ");
	$cntid=$listid; 
	//$cntid=$cmid->inst;
 
	$tbegin=time()-$tbegin;
	$quizattempt = $DB->get_record_sql("SELECT  mdl_quiz_attempts.timestart AS timestart, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades  
	FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.timefinish>'$tbegin' AND mdl_quiz_attempts.userid='$studentid' AND mdl_quiz.id='$quizid'   ORDER BY mdl_quiz_attempts.id DESC LIMIT 1"); 

	if(strpos($cntname, '인지촉진')!== false || strpos($cntname, '테스트')!== false)$contentslist5.= '<tr><td ></td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cntid.'" target="_blank">'.$cntname.'</td><td width=5%> </td></tr>';
	if(strpos($cntname, '보강학습')!== false)$contentslist6.= '<tr><td></td><td><a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cntid.'" target="_blank">'.$cntname.'</td><td width=5%> </td></tr>';
	if(strpos($cntname, '주제')!== false)$contentslist7.= '<tr><td></td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$cntid.'" target="_blank">'.$cntname.'</td><td width=5%> </td></tr>';
	}
 
	$timestart=time()-43200;
//	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' AND ( type LIKE '오늘목표' OR type LIKE '검사요청' OR type LIKE '미션부여' ) ORDER BY id DESC LIMIT 1 "); 
 
//	$nimg=mt_rand(1, 112);
//	$bessiArturl2='<img src="https://mathking.kr/Contents/IMAGES/BESSIArt/BESSIArt'.$nimg.'.jpg" width=100%>';  //<img src="https://mathking.kr/Contents/IMAGES/BESSIArt/BESSIArt3.jpg">	
	 
 

 echo       '<table width=100%><tr><th width=2.5% valign="top"></th>  <th  style="text-align:center; font-size:20;" valign=top>
	
	<table class="table table-hover"><tr style="background-color:skyblue;height:50px;"><th width=15% style="background-color:red;color:white;text-align:center;">모의시험</th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;">'.$curri->name.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">&nbsp;&nbsp;<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></a></th>
	<th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"></th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"></th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"></th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"></th></tr> 
	<tbody>'.$mockingtests.'<tr><td> </td><td>일주일에 '.$weekhours.'시간 공부 '.$$dday.' 까지 완료</td><td></td><td></td><td></td></tr></tbody></table>
	
	<!-- 시험 구분 버튼 영역 -->
	<div class="exam-section">
		<div class="exam-buttons-container">

			<!-- 중간고사 섹션 -->
			<table width=100%><tr><td><button class="exam-toggle-btn" onclick="toggleExamButtons(\'midterm\')" id="midterm-btn">
					<i class="fas fa-graduation-cap"></i> 기출문제 선택하기 (중간고사)
					<i class="fas fa-chevron-down toggle-icon"></i>
				</button></td><td><button class="exam-toggle-btn" onclick="toggleExamButtons(\'final\')" id="final-btn">
					<i class="fas fa-graduation-cap"></i> 기출문제 선택하기 (기말고사)
					<i class="fas fa-chevron-down toggle-icon"></i>
				</button></td></tr>
				<tr><td>
			<div class="exam-group">
				
				<div class="exam-level-buttons" id="midterm-buttons" style="display:none;">
					<span onClick="showChecklist(\''.$cntitemid1.'\')" class="level-btn">
						<b>기초</b> <i class="flaticon-plus"></i>
					</span>
					<span onClick="showChecklist(\''.$cntitemid2.'\')" class="level-btn">
						<b>기본</b> <i class="flaticon-plus"></i>
					</span>
					<span onClick="showChecklist(\''.$cntitemid3.'\')" class="level-btn">
						<b>심화</b> <i class="flaticon-plus"></i>
					</span>
				</div>
			</div>
			</td><td>
 			<div class="exam-group">
				
				<div class="exam-level-buttons" id="final-buttons" style="display:none;">
					<span onClick="showChecklist(\''.$cntitemid4.'\')" class="level-btn">
						<b>기초</b> <i class="flaticon-plus"></i>
					</span>
					<span onClick="showChecklist(\''.$cntitemid5.'\')" class="level-btn">
						<b>기본</b> <i class="flaticon-plus"></i>
					</span>
					<span onClick="showChecklist(\''.$cntitemid6.'\')" class="level-btn">
						<b>심화</b> <i class="flaticon-plus"></i>
					</span>
				</div>
			</div>
			</td></tr></table>
		</div>
	</div>
	
	<hr> </div> </div></th><th width=2.5% valign="top"></th></tr></table>';

	echo 'aaaa';
// 각 체크리스트를 위한 iframe 컨테이너 추가
echo '<div class="checklist-containers">
	<div id="checklist-container-'.$cntitemid1.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid1.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid1.'\')"></iframe>
	</div>
	<div id="checklist-container-'.$cntitemid2.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid2.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid2.'\')"></iframe>
	</div>
	<div id="checklist-container-'.$cntitemid3.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid3.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid3.'\')"></iframe>
	</div>
	<div id="checklist-container-'.$cntitemid4.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid4.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid4.'\')"></iframe>
	</div>
	<div id="checklist-container-'.$cntitemid5.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid5.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid5.'\')"></iframe>
	</div>
	<div id="checklist-container-'.$cntitemid6.'" class="checklist-iframe-container" style="display:none;">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>내신테스트 페이지가 로딩 중입니다.<br>학교시험 범위에 맞는 테스트 목록들을 클릭해 주세요.</p>
		</div>
		<iframe id="checklist-iframe-'.$cntitemid6.'" style="display:none;" src="" onload="hideLoading(\''.$cntitemid6.'\')"></iframe>
	</div>
</div>';

$chapters=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid' "); 

$solutionnote='';
for($nch=1;$nch<=20;$nch++)
	{
	$text='ch'.$nch;
	$chapter=$chapters->$text;

	if($chapter==NULL)break;
	$tag=$DB->get_record_sql("SELECT * FROM  mdl_tag  WHERE id='$chapter' ");
	$chaptername=$tag->name;
 	$solutionnote.= '<tr>
		<td class="text-center">'.$nch.'</td>
		<td><i class="fas fa-bookmark"></i> '.$chaptername.'</td>
		<td class="text-center">
			<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestormview.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank" class="btn btn-sm btn-info">
				<i class="fas fa-eye"></i> 첨삭노트 보기
			</a>
		</td>
		<td class="text-center">
			<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestorm.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank" class="btn btn-sm btn-success">
				<i class="fas fa-redo"></i> 오답 재시도
			</a>
		</td>
	</tr>';
	}
									
echo '					</div>';
									
echo '					<!-- 두 번째 탭: 오답노트 -->
					<div class="tab-pane fade" id="pills-profile1" role="tabpanel" aria-labelledby="pills-profile1-tab">
						<div class="row">
							<div class="col-md-12">
								<h4 class="mb-3"><i class="fas fa-book"></i> 챕터별 오답노트</h4>
								<div class="table-responsive">
									<table class="table table-hover">
										<thead>
											<tr>
												<th width="10%">번호</th>
												<th width="40%">챕터명</th>
												<th width="25%">첨삭노트</th>
												<th width="25%">오답 재시도</th>
											</tr>
										</thead>
										<tbody>
											'.$solutionnote.'
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>';

echo '<script>
	// 현재 열려있는 체크리스트 ID 저장
	let currentOpenChecklist = null;
	
	// 시험 구분 버튼 토글 함수
	function toggleExamButtons(examType) {
		const examBtn = document.getElementById(examType + "-btn");
		const examButtons = document.getElementById(examType + "-buttons");
		const toggleIcon = examBtn.querySelector(".toggle-icon");
		const allExamButtons = document.querySelectorAll(".exam-level-buttons");
		const allToggleIcons = document.querySelectorAll(".toggle-icon");
		
		// 다른 시험 구분 버튼들 닫기
		allExamButtons.forEach(function(buttons) {
			if (buttons.id !== examType + "-buttons") {
				buttons.style.display = "none";
			}
		});
		
		// 모든 토글 아이콘 초기화
		allToggleIcons.forEach(function(icon) {
			icon.classList.remove("rotate-180");
		});
		
		// 현재 시험 구분 버튼 토글
		if (examButtons.style.display === "none" || examButtons.style.display === "") {
			examButtons.style.display = "flex";
			toggleIcon.classList.add("rotate-180");
			// 부드러운 애니메이션을 위한 지연
			setTimeout(function() {
				examButtons.style.opacity = "1";
			}, 10);
		} else {
			examButtons.style.opacity = "0";
			setTimeout(function() {
				examButtons.style.display = "none";
			}, 300);
			toggleIcon.classList.remove("rotate-180");
		}
	}
	
	function showChecklist(Checklist) {
		const container = document.getElementById("checklist-container-" + Checklist);
		const iframe = document.getElementById("checklist-iframe-" + Checklist);
		const allContainers = document.querySelectorAll(".checklist-iframe-container");
		const clickedSpan = event.currentTarget;
		const allSpans = document.querySelectorAll("span[onClick*=showChecklist]");
		const allLevelBtns = document.querySelectorAll(".level-btn");
		
		// 모든 span에서 active 클래스 제거
		allSpans.forEach(span => span.classList.remove("active"));
		allLevelBtns.forEach(btn => btn.classList.remove("active"));
		
		// 다른 모든 컨테이너 닫기
		allContainers.forEach(function(el) {
			if (el.id !== "checklist-container-" + Checklist) {
				el.style.display = "none";
				el.querySelector("iframe").src = "";
			}
		});
		
		// 토글 기능
		if (container.style.display === "none" || container.style.display === "") {
			container.style.display = "block";
			clickedSpan.classList.add("active");
			currentOpenChecklist = Checklist;
			
			// 로딩 스피너 표시
			const loadingSpinner = container.querySelector(".loading-spinner");
			loadingSpinner.style.display = "flex";
			iframe.style.display = "none";
			
			// iframe src 설정
			iframe.src = "https://mathking.kr/moodle/mod/checklist/view.php?id=" + Checklist;
			
			// 부드러운 스크롤
			setTimeout(function() {
				container.scrollIntoView({ behavior: "smooth", block: "center" });
			}, 100);
			
			// 로딩 시작 시각 효과
			animateLoading(container);
		} else {
			container.style.display = "none";
			iframe.src = "";
			clickedSpan.classList.remove("active");
			currentOpenChecklist = null;
		}
	}
	
	function hideLoading(checklistId) {
		const container = document.getElementById("checklist-container-" + checklistId);
		const iframe = document.getElementById("checklist-iframe-" + checklistId);
		const loadingSpinner = container.querySelector(".loading-spinner");
		
		// 페이드 아웃 효과로 로딩 스피너 숨기기
		loadingSpinner.style.opacity = "0";
		setTimeout(function() {
			loadingSpinner.style.display = "none";
			loadingSpinner.style.opacity = "1";
			// 페이드 인 효과로 iframe 표시
			iframe.style.display = "block";
			iframe.style.opacity = "0";
			setTimeout(function() {
				iframe.style.transition = "opacity 0.3s ease";
				iframe.style.opacity = "1";
			}, 50);
		}, 300);
	}
	
	// 로딩 애니메이션 효과
	function animateLoading(container) {
		const spinner = container.querySelector(".spinner");
		spinner.style.animation = "none";
		setTimeout(function() {
			spinner.style.animation = "spin 1s linear infinite";
		}, 10);
	}
	
	// ESC 키로 닫기 기능
	document.addEventListener("keydown", function(event) {
		if (event.key === "Escape" && currentOpenChecklist) {
			const container = document.getElementById("checklist-container-" + currentOpenChecklist);
			const allSpans = document.querySelectorAll("span[onClick*=showChecklist]");
			
			if (container && container.style.display !== "none") {
				container.style.display = "none";
				container.querySelector("iframe").src = "";
				allSpans.forEach(span => span.classList.remove("active"));
				currentOpenChecklist = null;
			}
		}
	});
	
	// 페이지 로드 시 초기화
	window.addEventListener("load", function() {
		// 모든 iframe 컨테이너 숨기기
		const allContainers = document.querySelectorAll(".checklist-iframe-container");
		allContainers.forEach(function(container) {
			container.style.display = "none";
		});
	});
 	</script>';

// CSS 스타일 추가
echo '<style>
	/* 탭 네비게이션 중앙 정렬 스타일 */
	.tab-navigation-wrapper {
		display: flex;
		justify-content: center;
		margin-bottom: 30px;
	}
	
	.nav-pills.justify-content-center {
		display: flex;
		justify-content: center;
		gap: 10px;
	}
	
	/* 체크리스트 iframe 컨테이너 스타일 */
	.checklist-containers {
		margin-top: 20px;
	}
	
	.checklist-iframe-container {
		margin: 20px auto;
		max-width: 1200px;
		background-color: #f8f9fa;
		border-radius: 10px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		overflow: hidden;
		transition: all 0.3s ease;
	}
	
	/* 테이블 내부 inline 컨테이너 스타일 */
	.inline-container {
		margin: 10px 0;
		width: 100%;
		max-width: none;
	}
	
	.checklist-iframe-container iframe {
		width: 100%;
		height: 600px;
		border: none;
		display: block;
	}
	
	/* 로딩 스피너 스타일 */
	.loading-spinner {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		height: 200px;
		background-color: #f8f9fa;
		padding: 20px;
	}
	
	.spinner {
		width: 50px;
		height: 50px;
		border: 4px solid #f3f3f3;
		border-top: 4px solid #1572e8;
		border-radius: 50%;
		animation: spin 1s linear infinite;
		margin-bottom: 20px;
	}
	
	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	
	.loading-spinner p {
		color: #666;
		font-size: 16px;
		font-weight: 500;
		animation: fadeInOut 1.5s ease-in-out infinite;
		text-align: center;
		line-height: 1.6;
		max-width: 400px;
	}
	
	@keyframes fadeInOut {
		0%, 100% { opacity: 0.5; }
		50% { opacity: 1; }
	}
	
	/* 클릭 가능한 항목 스타일 개선 */
	span[onClick] {
		cursor: pointer;
		transition: all 0.2s ease;
		padding: 5px 10px;
		border-radius: 5px;
		display: inline-block;
		position: relative;
	}
	
	span[onClick]:hover {
		background-color: #e3f2fd;
		transform: translateY(-2px);
		box-shadow: 0 2px 5px rgba(0,0,0,0.1);
	}
	
	span[onClick] b {
		color: #1572e8;
		font-size: 14px;
		transition: color 0.3s ease;
	}
	
	span[onClick]:hover b {
		color: #0d47a1;
	}
	
	span[onClick] i {
		color: #1572e8;
		transition: all 0.3s ease;
		font-size: 14px;
		display: inline-block;
	}
	
	/* 열린 상태의 아이콘 회전 */
	.checklist-iframe-container:not([style*="display: none"]) ~ * span[onClick] i,
	span[onClick].active i {
		transform: rotate(180deg);
	}
	
	/* 반응형 디자인 */
	@media (max-width: 768px) {
		.checklist-iframe-container iframe {
			height: 400px;
		}
		
		.checklist-iframe-container {
			margin: 10px 5px;
			border-radius: 5px;
		}
		
		span[onClick] {
			padding: 3px 6px;
			font-size: 12px;
		}
		
		.exam-section {
			padding: 10px;
		}
		
		.exam-toggle-btn {
			padding: 12px 15px;
			font-size: 14px;
		}
		
		.level-btn {
			padding: 10px 20px;
			font-size: 14px;
		}
		
		.exam-level-buttons {
			padding: 15px 10px;
		}
	}
	
	/* 애니메이션 효과 */
	.checklist-iframe-container {
		animation: slideDown 0.3s ease-out;
	}
	
	@keyframes slideDown {
		from {
			opacity: 0;
			transform: translateY(-20px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}
	
	/* 테이블 스타일 개선 */
	.table td {
		vertical-align: middle;
	}
	
	tr[style*="background-color:skyblue"] {
		transition: background-color 0.3s ease;
	}
	
	tr[style*="background-color:skyblue"]:hover {
		background-color: #87ceeb !important;
		opacity: 0.9;
	}
	
	/* 버튼 스타일 개선 */
	button#alert_planE, button#alert_planF, button#alert_planG {
		transition: transform 0.2s ease;
	}
	
	button#alert_planE:hover, button#alert_planF:hover, button#alert_planG:hover {
		transform: scale(1.1);
	}
	
	/* 탭 스타일 개선 */
	.nav-pills .nav-link {
		border-radius: 20px;
		padding: 10px 20px;
		margin-right: 10px;
		transition: all 0.3s ease;
		font-weight: 500;
		background-color: #f0f0f0;
		color: #333;
		border: 2px solid transparent;
		min-width: 150px;
		text-align: center;
	}
	
	.nav-pills .nav-link:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0,0,0,0.1);
		background-color: #e0e0e0;
	}
	
	.nav-pills .nav-link.active {
		background-color: #1572e8;
		box-shadow: 0 4px 8px rgba(21,114,232,0.3);
		color: white;
		border-color: #1572e8;
	}
	
	.nav-pills .nav-link i {
		margin-right: 8px;
	}
	
	.tab-content {
		padding: 20px 0;
		animation: fadeIn 0.5s ease-in;
	}
	
	@keyframes fadeIn {
		from {
			opacity: 0;
			transform: translateY(10px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}
	
	/* 오답노트 테이블 스타일 */
	.table-hover tbody tr:hover {
		background-color: #f5f5f5;
		cursor: pointer;
	}
	
	/* 배지 스타일 */
	.badge-success {
		background-color: #31ce36;
		color: white;
		font-size: 12px;
		padding: 4px 8px;
		border-radius: 10px;
	}
	
	/* 카드 스타일 */
	.card {
		border: none;
		box-shadow: 0 0 20px rgba(0,0,0,0.08);
		border-radius: 10px;
	}
	
	.card-body {
		padding: 25px;
	}
	
	/* 아이콘 스타일 */
	.fas {
		margin-right: 5px;
	}
	
	/* 버튼 스타일 */
	.btn-sm {
		padding: 5px 15px;
		font-size: 13px;
		border-radius: 15px;
		transition: all 0.3s ease;
	}
	
	.btn-info {
		background-color: #48abf7;
		border-color: #48abf7;
		color: white;
	}
	
	.btn-info:hover {
		background-color: #3697e1;
		border-color: #3697e1;
		transform: translateY(-1px);
		box-shadow: 0 4px 8px rgba(72,171,247,0.3);
	}
	
	.btn-success {
		background-color: #31ce36;
		border-color: #31ce36;
		color: white;
	}
	
	.btn-success:hover {
		background-color: #2bb930;
		border-color: #2bb930;
		transform: translateY(-1px);
		box-shadow: 0 4px 8px rgba(49,206,54,0.3);
	}
	
	/* 텍스트 정렬 */
	.text-center {
		text-align: center;
	}
	
	/* 시험 구분 섹션 스타일 */
	.exam-section {
		margin: 20px 0;
		padding: 20px;
		background-color: #f8f9fa;
		border-radius: 10px;
	}
	
	.exam-buttons-container {
		display: flex;
		flex-direction: column;
		gap: 15px;
		max-width: 800px;
		margin: 0 auto;
	}
	
	.exam-group {
		background-color: white;
		border-radius: 8px;
		box-shadow: 0 2px 5px rgba(0,0,0,0.1);
		overflow: hidden;
		transition: all 0.3s ease;
	}
	
	.exam-toggle-btn {
		width: 100%;
		padding: 15px 20px;
		background-color: #1572e8;
		color: white;
		border: none;
		font-size: 16px;
		font-weight: 600;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: space-between;
		transition: all 0.3s ease;
	}
	
	.exam-toggle-btn:hover {
		background-color: #0d5db8;
		transform: translateY(-1px);
		box-shadow: 0 4px 8px rgba(21,114,232,0.3);
	}
	
	.exam-toggle-btn i {
		margin-right: 10px;
	}
	
	.toggle-icon {
		transition: transform 0.3s ease;
	}
	
	.toggle-icon.rotate-180 {
		transform: rotate(180deg);
	}
	
	.exam-level-buttons {
		padding: 20px;
		background-color: #f8f9fa;
		display: none;
		gap: 15px;
		justify-content: center;
		flex-wrap: wrap;
		opacity: 0;
		transition: opacity 0.3s ease;
	}
	
	.level-btn {
		background-color: white;
		padding: 12px 25px;
		border-radius: 25px;
		box-shadow: 0 2px 5px rgba(0,0,0,0.1);
		cursor: pointer;
		transition: all 0.3s ease;
		display: inline-flex;
		align-items: center;
		gap: 8px;
		border: 2px solid transparent;
	}
	
	.level-btn:hover {
		background-color: #e3f2fd;
		transform: translateY(-2px);
		box-shadow: 0 4px 10px rgba(0,0,0,0.15);
		border-color: #1572e8;
	}
	
	.level-btn b {
		color: #1572e8;
		font-size: 15px;
	}
	
	.level-btn i {
		color: #1572e8;
		font-size: 14px;
		transition: transform 0.3s ease;
	}
	
	.level-btn:hover i {
		transform: rotate(90deg);
	}
	
	.level-btn.active {
		background-color: #1572e8;
		border-color: #1572e8;
	}
	
	.level-btn.active b,
	.level-btn.active i {
		color: white;
	}
</style>';

	 $pagetype='popup';
	 $initialtalk='시험대비는 정해진 기간동안 진행이 되기 때문에 개념 공부나 심화학습과는 차이가 있습니다. 특히, 내용에 대한 이해나 주어진 공부를 충실히 하는 것이 전체 일정에서는 오히려 부족한 부분을 놓치게 되는 원인이 되기도 합니다. 즉, 같은 시간을 약한 부분에 투자를 하면 상대적으로 적은 시간으로 학교시험을 잘 볼 수 있는 기회가 되기도 합니다. 따라서 선생님과 상담을 통하여 현재 나의 시험 준비 상태를 점검받고 주간목표와 주간목표를 달성하기 위한 효과적인 활동을 계획하는 것이 매우 중요합니다. ';
//	 $finetuning='다음 문장의 의미를 쉽게 전달하도록 2배로 풀어서 구어체로 써줘. : ('.$username->lastname.'(학생이름)님 시험공부는 잘되고 계신가요 ?  시험대비는 정해진 기간동안 진행이 되기 때문에 개념 공부나 심화학습과는 차이가 있습니다. 특히, 내용에 대한 이해나 주어진 공부를 충실히 하는 것이 전체 일정에서는 오히려 부족한 부분을 놓치게 되는 원인이 되기도 합니다. 즉, 같은 시간은 약한 부분에 투자를 하면 상대적으로 적은 시간으로 학교시험을 잘 볼 수 있는 기회가 되기도 합니다. 따라서 선생님과 상담을 통하여 현재 나의 시험 준비 상태를 점검받고 주간목표와 주간목표를 달성하기 위한 효과적인 활동을 계획하는 것이 매우 중요합니다. 특히, 남은 시간 동안 어떤 우선순위로 공부를 할지 판단하기 위하여 내신테스트를 중심으로 진단하고 진단 결과에 따라 활동을 정하는 것은 매우 중요합니다. 진단 결과에 따라 문제 풀이 난이도를 높일지 아니면 풀이의 능숙도를 올릴지를 정하게 되고 그에 맞는 활동을 세팅하게 됩니다.) + (마지막으로 생각을 구체화할 수 있도록 3가지 성찰질문 만들어줘)';

$finetuning='다음 문장의 의미를 쉽게 전달하도록 2배로 풀어서 구어체로 써줘. : (시험대비 방법은 크게 다음과 같습니다. 1. 내신테스트 진단 후 활동 선택하여 시간분배 문제 해결 2. 약한 부분에 시간 집중투입하기 3. 개념은 주제별 테스트로 핀포인트 잡아서 극복 4. 능숙도는 내신테스트 시간 단축 응시 5. 심화학습은 인지촉진 컨텐츠 사용하기 6. 문제유형 확장은 보강학습 사용하기.) + (마지막으로 생각을 구체화할 수 있도록 3가지 성찰질문 만들어줘)';
	 include("../LLM/gptsnippet.php");
?>
	 