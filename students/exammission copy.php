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
 

 
echo ' 		 <div class="col-md-12">              <div class="card"> <div class="card-title"><div class="card-body">
									<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true"> 학교시험 D-'.$leftDays.' ('.$exmission->startdate.') )</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-profile1-tab" data-toggle="pill" href="#pills-profile1" role="tab" aria-controls="pills-profile1" aria-selected="false">오답노트 &nbsp;&nbsp;<span class="badge badge-count badge-Success">'.$count1.'</span></a>											 	 
										</li>
									</ul>
		<div class="tab-content mb-3" id="pills-tabContent">
		<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">  ';

 	$nmax=$num-1;
	for($nch=1;$nch<=$nmax;$nch++)
		{
		$qname='quiz'.$nch;
		$quizname=$$qname;	
		$quizid='quizid'.$nch;
		$qid=$$quizid;

		$cmid=$DB->get_record_sql("SELECT instance AS inst FROM mdl_course_modules WHERE id='$qid' ");
		$quizid=$cmid->inst;
 		
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
			$qresult='<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">응시전</a></td>';
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
		$mockingtests.= '<tr><td>&nbsp;&nbsp;&nbsp;'.$nch.'</td>	<td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$qid.'" target="_blank">'.$quizname.'</td><td>'.$$passgrade.'점</td>'.$qresult.'<td> </td><td>'.$$dday.'</td></tr>';
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
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$timestart' AND ( type LIKE '오늘목표' OR type LIKE '검사요청' OR type LIKE '미션부여' ) ORDER BY id DESC LIMIT 1 "); 
	$todayplan='<table width=100%><tr style="background-color:#e6e6e6;height:50px;"><th width=15% style="background-color:lightgrey;color:black;text-align:center;font-size:15;">오늘 목표</th><th width=2%></th><th>  '.$goal->text.'</th></tr></table>';
	$nimg=mt_rand(1, 112);
//	$bessiArturl2='<img src="https://mathking.kr/Contents/IMAGES/BESSIArt/BESSIArt'.$nimg.'.jpg" width=100%>';  //<img src="https://mathking.kr/Contents/IMAGES/BESSIArt/BESSIArt3.jpg">	
	 
 

 echo       '<table width=100%><tr><th width=2.5% valign="top"></th><th width=45%  style="text-align:center; font-size:20;"  valign=top>
	<table class="table table-hover"><tr style="background-color:skyblue;height:50px;"><th width=10% style="background-color:green;color:white;text-align:center;">유형정복</th><th>
	 <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cnt2.'" target="_blank">대표유형</a> <button   type="button"   id="alert_planB"  style = "background-color:white;color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
	 <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cnt3.'" target="_blank">유형단련</a> <button   type="button"   id="alert_planC"  style = "background-color:white;color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	 <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cnt11.'" target="_blank">중급노트</a> | <a href="https://mathking.kr/moodle/mod/checklist/view.php?id='.$cnt12.'" target="_blank">심화노트</a> <button   type="button"   id="alert_planD"  style = "background-color:white;color:grey;border:0;outline:0;" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></button>
	</th><th><a href="https://mathking.kr/moodle/mod/book/view.php?id=60736&chapterid='.$cnt8.'" target="_blank">개념 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647805249.png width=20></a></th><th><a href="https://mathking.kr/moodle/mod/book/view.php?id=60666&chapterid='.$cnt9.'" target="_blank">유형 <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647805249.png width=20></a></th></tr></table>
	<table class="table table-hover" width=100%>'.$contentslist1.$contentslist2.$contentslist3.$contentslist4.'</table>

	 <table class="table table-hover" width=100%> 
	<tbody>'.$contentslist5.$contentslist6.$contentslist7.'</tbody> 
	 </table></th><th width=5% valign="top"></th>

	<th width=40% style="text-align:center; font-size:20;" valign=top>
	
	<table class="table table-hover"><tr style="background-color:skyblue;height:50px;"><th width=15% style="background-color:red;color:white;text-align:center;">모의시험</th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;">'.$curri->name.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">&nbsp;&nbsp;<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></a></th>
	<th scope="col" style="text-align:center; background-color:skyblue;font-size:15;">목표</th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;">결과</th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"></th><th scope="col" style="text-align:center; background-color:skyblue;font-size:15;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editmissionhome.php?id='.$studentid.'&cid='.$cid.'&mtid='.$mtid.'">
	완료일<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></a></th></tr> 
	<tbody>'.$mockingtests.'<tr><td> </td><td>일주일에 '.$weekhours.'시간 공부 '.$$dday.' 까지 완료</td><td></td><td></td><td></td></tr></tbody></table>
	<table width=100%  class="table table-hover" align=center><tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><th width=3%> </th><th align=center> | 중간고사 </th><th><span onClick="showChecklist(\''.$cntitemid1.'\')" accesskey="m"><b>기초</b> <i class="flaticon-plus"></i></a></span>   </th><th><span onClick="showChecklist(\''.$cntitemid2.'\')" accesskey="m"><b>기본</b> <i class="flaticon-plus"></i></a></span>  </th><th> <span onClick="showChecklist(\''.$cntitemid3.'\')" accesskey="m"><b>심화</b> <i class="flaticon-plus"></i></a></span>  </th>
	<th width=2%> </th><th  align=center>|  기말고사 </th><th><span onClick="showChecklist(\''.$cntitemid4.'\')" accesskey="m"><b>기초</b> <i class="flaticon-plus"></i></a></span>  </th><th> <span onClick="showChecklist(\''.$cntitemid5.'\')" accesskey="m"><b>기본</b> <i class="flaticon-plus"></i></a></span>  </th><th><span onClick="showChecklist(\''.$cntitemid6.'\')" accesskey="m"><b>심화</b> <i class="flaticon-plus"></i></a></span>  </th></tr>
	</table><hr> <table width=100% align=center><tr><td>'.$todayplan.$bessiArturl2.'</td></tr></table> </div> </div></th><th width=2.5% valign="top"></th></tr></table>';

$chapters=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid' "); 

$solutionnote='';
for($nch=1;$nch<=20;$nch++)
	{
	$text='ch'.$nch;
	$chapter=$chapters->$text;

	if($chapter==NULL)break;
	$tag=$DB->get_record_sql("SELECT * FROM  mdl_tag  WHERE id='$chapter' ");
	$chaptername=$tag->name;
 	$solutionnote.= '<tr><td width=10%> </td><td >'.$nch.'-'.$chaptername.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestormview.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank">첨삭노트 보기</a></td><td width=5%> </td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestorm.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank">오답 재시도 선택하기</a></td></tr>
	<tr><td style="background-color:skyblue;color:white;"  > <br> </td><td style="background-color:skyblue;color:white;"  > <br> </td><td style="background-color:skyblue;color:white;" > </td><td width=5%> </td><td style="background-color:skyblue;color:white;" > </td></tr>';
	}
									
echo '<div class="tab-pane fade" id="pills-profile1" role="tabpanel" aria-labelledby="pills-profile1-tab">
	<table width=80%>'.$solutionnote.'</table>
	</div></div></div></div></div>
	</div> </div> </div>';

echo '<script>
	function showChecklist(Checklist)
		{
		Swal.fire({
		position:"top-end",showCloseButton: true, width:1200,
		  html:
		    \'<iframe  style="border: 1px none; z-index:2; width:80vw; height:80vw;  margin-left: -50px;margin-right: -50px;  margin-top: -200px; "  src="https://mathking.kr/moodle/mod/checklist/view.php?id=\'+Checklist+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}
 	</script>';

	 $pagetype='popup';
	 $initialtalk='시험대비는 정해진 기간동안 진행이 되기 때문에 개념 공부나 심화학습과는 차이가 있습니다. 특히, 내용에 대한 이해나 주어진 공부를 충실히 하는 것이 전체 일정에서는 오히려 부족한 부분을 놓치게 되는 원인이 되기도 합니다. 즉, 같은 시간을 약한 부분에 투자를 하면 상대적으로 적은 시간으로 학교시험을 잘 볼 수 있는 기회가 되기도 합니다. 따라서 선생님과 상담을 통하여 현재 나의 시험 준비 상태를 점검받고 주간목표와 주간목표를 달성하기 위한 효과적인 활동을 계획하는 것이 매우 중요합니다. ';
//	 $finetuning='다음 문장의 의미를 쉽게 전달하도록 2배로 풀어서 구어체로 써줘. : ('.$username->lastname.'(학생이름)님 시험공부는 잘되고 계신가요 ?  시험대비는 정해진 기간동안 진행이 되기 때문에 개념 공부나 심화학습과는 차이가 있습니다. 특히, 내용에 대한 이해나 주어진 공부를 충실히 하는 것이 전체 일정에서는 오히려 부족한 부분을 놓치게 되는 원인이 되기도 합니다. 즉, 같은 시간은 약한 부분에 투자를 하면 상대적으로 적은 시간으로 학교시험을 잘 볼 수 있는 기회가 되기도 합니다. 따라서 선생님과 상담을 통하여 현재 나의 시험 준비 상태를 점검받고 주간목표와 주간목표를 달성하기 위한 효과적인 활동을 계획하는 것이 매우 중요합니다. 특히, 남은 시간 동안 어떤 우선순위로 공부를 할지 판단하기 위하여 내신테스트를 중심으로 진단하고 진단 결과에 따라 활동을 정하는 것은 매우 중요합니다. 진단 결과에 따라 문제 풀이 난이도를 높일지 아니면 풀이의 능숙도를 올릴지를 정하게 되고 그에 맞는 활동을 세팅하게 됩니다.) + (마지막으로 생각을 구체화할 수 있도록 3가지 성찰질문 만들어줘)';

$finetuning='다음 문장의 의미를 쉽게 전달하도록 2배로 풀어서 구어체로 써줘. : (시험대비 방법은 크게 다음과 같습니다. 1. 내신테스트 진단 후 활동 선택하여 시간분배 문제 해결 2. 약한 부분에 시간 집중투입하기 3. 개념은 주제별 테스트로 핀포인트 잡아서 극복 4. 능숙도는 내신테스트 시간 단축 응시 5. 심화학습은 인지촉진 컨텐츠 사용하기 6. 문제유형 확장은 보강학습 사용하기.) + (마지막으로 생각을 구체화할 수 있도록 3가지 성찰질문 만들어줘)';
	 include("../LLM/gptsnippet.php");
?>
	 