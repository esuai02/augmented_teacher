<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$USER->id','studentfullengagement','$timecreated')");

include("navbar.php");
$userid=$studentid;

$getperiod=$DB->get_record_sql("SELECT data AS period FROM mdl_user_info_data where userid='$studentid' AND fieldid='67' "); 
$personalperiod=$getperiod->period;
$userperiod=time()-$personalperiod*86400;
$timeafter=time()-86400*14; // 오답노트 점검 기간 
$timeafter2=time()-43200; // 오답노트 점검 기간  
//$wboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student'   AND contentstitle NOT LIKE 'realtime' AND  tlaststroke > '$timeafter'   AND contentstype=2 AND status NOT LIKE 'attempt' AND active=1  ORDER BY tlaststroke DESC ");
$wboards=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student'  AND (status  LIKE 'review'  OR status  LIKE 'complete')  AND  tlaststroke > '$timeafter'   AND contentstype=2  AND active=1  ORDER BY tlaststroke DESC ");

$waitinglist= json_decode(json_encode($wboards), True);
$tab1=NULL;$tab2=NULL;$tab3=NULL;
$count=0;
$retrycount=0;
$pcount=0;
$nreturned=0;
$ncomplete=0;
$count0=0;
$count1=0;
$count2=0;
$count3=0;
 
$userperiod=$personalperiod*86400;  // 삭제.. 시스템에서 사용자 속성도 삭제
$nn_review=0;
$nn_complete=0;

unset($value);
foreach($waitinglist as $value)
	{	
	$count++;
	$boardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
	$cmid=$value['cmid'];
	$status=$value['status'];
	$tquestionreview=$value['treview']*86400;  // 문항별 예약기간
	$contentstype=$value['contentstype'];
	$timemodified=date("m-d h:i A", $value['timemodified']);
 	$reviewperiod=time()-$value['timereviewed']+43200;   
	$author='내';


	$nstroke=(int)($value['nstroke']/2);
	$ave_stroke=round($nstroke/(($value['tlast']-$value['tfirst'])/60),1);
	if($nstroke<10)
		{
		$ave_stroke='###';
		$nstroke='###';
		}
	if($value['status']==='reply')$author='선생님';
	$nreviewed=$value['nreview'];	
 	 
	$tagtitles=$value['contentstitle'].' &nbsp;';
	$question = $DB->get_record_sql("SELECT questiontext AS text FROM mdl_question WHERE id='$contentsid' ");
	$questiontext=$question->text;
	if(strpos($questiontext, 'ifminassistant')!= false)$questiontext=substr($questiontext, 0, strpos($questiontext, "<p>{ifminassistant}"));  
	if(strpos($questiontext, '/MY')!= false&&strpos($questiontext, 'slowhw')!= false)$questiontext='<p> MY A step </p>';
	include("../whiteboard/status_icons.php");
 
	if(strpos($questiontext, 'shutterstock')!= false)
		{
		$questiontext=substr($questiontext, 0, strpos($questiontext, '{ifminassistant}'));   
		$questiontext=strstr($questiontext, '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
		}
	if($value['contentstype']==2)$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><div class="tooltip2">컨텐츠<span class="tooltiptext2">'.$questiontext.'</span></div></a>';
	else 
		{
		$tagtitles=$value['contentstitle'];
		$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'" target="_blank" >컨텐츠</a>';
		}
/*
 	if( $value['status']==='begin' || $value['status']==='exam' || $value['status']==='reply' || $value['status']==='analysis'|| $value['status']==='first'|| $value['status']==='how'|| $value['status']==='topics'|| $value['status']==='expand' ||  $value['status']==='classroom' ||$value['status']==='steps' || $value['status']==='solution' || $value['status']==='solutionreply' )
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
 		$returned.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		$nreturned++;
		}
*/
	if($value['status']==='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$complete.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		 //휴지통
		$ncomplete++;
		}
/*	elseif($value['status']==='retry') // 복습출제
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review0.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		$retrycount++;
		}

	elseif($value['status']==='present') // 발표평가
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$presenation.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		$pcount++;
		}
*/
	elseif($value['nreview']==1 && $reviewperiod>$tquestionreview && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review1.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
 		// 복습1
		$count1++;
		}
	elseif($value['nreview']==2 && $reviewperiod>$tquestionreview && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review2.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		 // 복습2		
		$count2++;
		}
	elseif($value['nreview']==3 && $reviewperiod>$tquestionreview && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review3.= '<tr><td>'.$imgstatus.'&nbsp;'.$contentslink.'</td><td></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'&access=mynote" target="_blank"><div class="tooltip3"> 서술평가<span class="tooltiptext3"><table align=center><tr><td>'.$questiontext.'</td></tr></table></span></div></a></td><td>총'.$nstroke.'획 </td><td> '.$ave_stroke.'획/분</td><td>'.date("m월d일 | H:i",$value['timemodified']).' </td></tr>';  		
		 // 복습3
		$count3++;
		}
 
	if(time()-$value['timemodified']<86400*7 && $value['status']!=='complete')$nn_review++;
	if(time()-$value['timemodified']<86400*7 && $value['status']==='complete')$nn_complete++;	
	$ntotal=$retrycount+$count1+$count2+$count3+$count4+$count5;
	 
	$statetext='복습문항: '.$ntotal.'개  | 응답대기 : '.$nreturned.'개';
	if($ntotal==0)$DB->execute("UPDATE {abessi_today} SET rcomplete='1' WHERE  userid='$studentid' ORDER BY id DESC LIMIT 1 "); // 복습완료
	if($ntotal+$nreturned==0)
		{
		$statetext='모두 클리어된 상태입니다. 다음 공부를 시작해 주세요 !';
		// $homeurl='https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid;
		// header('Location: '.$homeurl);
		// header('Location: https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid);
		}
	}

if($count0!=0)$badgeA='<span class="badge badge-count badge-Success">'.$count0.'</span>';
if($nreturned!=0)$badgeB='<span class="badge badge-count badge-Success">'.$nreturned.'</span>';
if($count1!=0)$badgeC='<span class="badge badge-count badge-Success">'.$count1.'</span>';
if($count2!=0)$badgeD='<span class="badge badge-count badge-Success">'.$count2.'</span>';
if($count3!=0)$badgeE='<span class="badge badge-count badge-Success">'.$count3.'</span>';
if($count4!=0)$badgeF='<span class="badge badge-count badge-Success">'.$count4.'</span>';
if($count5!=0)$badgeG='<span class="badge badge-count badge-Success">'.$count5.'</span>';
if($ncomplete!=0)$badgeH='<span class="badge badge-count badge-Success">'.$ncomplete.'</span>';
 
$subjects=$DB->get_records_sql("SELECT * FROM mdl_abessi_curriculum WHERE shortname LIKE '%math%' ORDER BY id "); 
 
$subjectList= json_decode(json_encode($subjects), True);
unset($value);
foreach($subjectList as $value)
{
$cid=$value['id'];
$chapters=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id='$cid' "); 

$solutionnote='';
for($nch=1;$nch<=20;$nch++)
	{
	$text='ch'.$nch;   // tag column명
	$chapter=$chapters->$text;

	if($chapter==NULL)break;
	$tag=$DB->get_record_sql("SELECT * FROM  mdl_tag  WHERE id='$chapter' ");
	$chaptername=$tag->name;
 	$solutionnote.= '<tr><td width=10%> </td><td >'.$nch.'-'.$chaptername.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestormview.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank">첨삭노트 보기</a></td><td width=5%> </td><td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivestorm.php?userid='.$studentid.'&tagid='.$chapter.'" target="_blank">오답 재시도 선택하기</a></td></tr>
	<tr><td style="background-color:skyblue;color:white;"  > <br> </td><td style="background-color:skyblue;color:white;"  > <br> </td><td style="background-color:skyblue;color:white;" > </td><td width=5%> </td><td style="background-color:skyblue;color:white;" > </td></tr>';
	}
if($cid>41) $mynotes1.= '<table width=80%>'.$solutionnote.'</table>';
else $mynotes2.= '<table width=80%>'.$solutionnote.'</table>';
}

echo '
 					 <div class="row">
						<div class="col-md-3">
							<div class="card card-profile card-secondary">
								<div class="card-header" style="background-image: url("../assets/img/blogpost.jpg")">
									<div class="profile-picture">
										<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658132883.png" alt="Profile Picture">
									</div>
								</div>
								<div class="card-body">
									<div class="user-profile text-center">
										<div class="name">김도현, 22세</div>
										<div class="job">온라인 선생님</div>
										<div class="desc">카이스트 물리학과 3학년</div>
										<div class="social-media">
											<a class="btn btn-info btn-twitter btn-sm btn-link" href="#"> 
												<span class="btn-label just-icon"><i class="flaticon-twitter"></i> </span>
											</a>
											<a class="btn btn-danger btn-sm btn-link" rel="publisher" href="#"> 
												<span class="btn-label just-icon"><i class="flaticon-google-plus"></i> </span> 
											</a>
											<a class="btn btn-primary btn-sm btn-link" rel="publisher" href="#"> 
												<span class="btn-label just-icon"><i class="flaticon-facebook"></i> </span> 
											</a>
											<a class="btn btn-danger btn-sm btn-link" rel="publisher" href="#"> 
												<span class="btn-label just-icon"><i class="flaticon-dribbble"></i> </span> 
											</a>
										</div>
										<div class="view-profile">
											<a href="#" class="btn btn-secondary btn-block">View Full Profile</a>
										</div>
									</div>
								</div>
								<div class="card-footer">
									<div class="row user-stats text-center">
										<div class="col">
											<div class="number">125</div>
											<div class="title">Post</div>
										</div>
										<div class="col">
											<div class="number">25K</div>
											<div class="title">Followers</div>
										</div>
										<div class="col">
											<div class="number">134</div>
											<div class="title">Following</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="col-md-9">
							<div class="card">
								<div class="card-header">
									<table width="100%" align=center><tr><th width="50%">기억연장 ('.$statetext.')</th><th width="30%">복습예약 '.$nn_review.'회 | 학습완료 '.$nn_complete.' 회</th><th width="20%"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw=3">완료문항 풀어보기<img src="https://prepinsta.com/wp-content/uploads/2019/07/Quiz-icon-for-level-03.png" target="_blank" width=25></a> </th></tr></table>
								</div>
								<div class="card-body">
									<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">기억 연장하기 '.$badgeA.'</a>
										</li>
							 			<li class="nav-item">
											<a class="nav-link" id="pills-contact1-tab" data-toggle="pill" href="#pills-contact1" role="tab" aria-controls="pills-contact1" aria-selected="false">휴지통 '.$badgeH.'</a>
										</li>
							 			<li class="nav-item">
											<a class="nav-link" id="pills-contact2-tab" data-toggle="pill" href="#pills-contact2" role="tab" aria-controls="pills-contact2" aria-selected="false">중등수학 '.$badgeH.'</a>
										</li>
							 			<li class="nav-item">
											<a class="nav-link" id="pills-contact3-tab" data-toggle="pill" href="#pills-contact3" role="tab" aria-controls="pills-contact3" aria-selected="false">고등수학 '.$badgeH.'</a>
										</li>
									</ul>
									<div class="tab-content mb-3" id="pills-tabContent">
										<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
										<table style="width: 100%;"><tr><th>발표평가<br><br><table width=90%>'.$presenation.'</table><hr>보완요청 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  ※ 답변하기 어려운 요청의 경우 선생님에게 도움을 요청해 주세요 ^___^<br>&nbsp;&nbsp;  </th></tr></table><table width=90%>'.$returned.'</table><hr>복습출제<br><br><table width=90%>'.$review0.'</table><hr>1회복습<br><br><table width=90%>'.$review1.'</table><hr>2회복습<br><br><table width=90%>'.$review2.'</table><hr>3회복습<br><br><table width=90%>'.$review3.'</table></div>			 
										<div class="tab-pane fade" id="pills-contact1" role="tabpanel" aria-labelledby="pills-contact1-tab">
										<table style="width: 100%;"><tr><th> '.$complete.'</th></tr></table></div>
										<div class="tab-pane fade" id="pills-contact2" role="tabpanel" aria-labelledby="pills-contact2-tab">
										<table style="width: 100%;"><tr><th> '.	$mynotes1.'</th></tr></table></div>
										<div class="tab-pane fade" id="pills-contact3" role="tabpanel" aria-labelledby="pills-contact3-tab">
										<table style="width: 100%;"><tr><th> '.	$mynotes2.'</th></tr></table></div>
									</div>
  
 </div></div></div></div>	 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script><!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don\'t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script>
		$("#datepicker").datetimepicker({
			format: "MM/DD/YYYY",
		});
	</script>
 ';
include("quicksidebar.php");
?>