<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$USER->id','studentfullengagement','$timecreated')");

include("navbar.php");
$userid=$studentid;

$getperiod=$DB->get_record_sql("SELECT data AS period FROM mdl_user_info_data where userid='$studentid' AND fieldid='67' "); 
$personalperiod=$getperiod->period;
$timeafter=time()-86400*14;
$wboard=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid LIKE '$studentid'  AND userrole LIKE 'student' AND ( (wboardid NOT LIKE '%tsDoHfRT%') OR (wboardid  LIKE '%tsDoHfRT%' AND status NOT LIKE 'begin' )) AND  turn LIKE '0' AND timemodified > '$timeafter' ORDER BY id DESC ");
$waitinglist= json_decode(json_encode($wboard), True);
$tab1=NULL;$tab2=NULL;$tab3=NULL;
$count=0;
$nreturned=0;
$ncomplete=0;
$count0=0;
$count1=0;
$count2=0;
$count3=0;
$count4=0;
$count5=0;
$userperiod=$personalperiod*86400;
$nn_review=0;
$nn_complete=0;
unset($value);
foreach($waitinglist as $value)
	{	
	$count++;
	$boardid=$value['wboardid'];
	$contentsid=$value['contentsid'];
	$cmid=$value['cmid'];
	$contentstype=$value['contentstype'];
	$timemodified=date("m-d h:i A", $value['timemodified']);
 	$reviewperiod=time()-$value['timereviewed']+43200;   
	$author='내';
	if($value['status']==='reply')$author='선생님';
	$nreviewed=$value['nreview'];	
/*	$gettags=$DB->get_records_sql("SELECT * FROM mdl_tag_instance WHERE itemid='$contentsid' ");
	$tags= json_decode(json_encode($gettags), True);
	$tagtitles=NULL;
	unset($value2);
	foreach($tags as $value2)
		{
		$tagid=$value2['tagid'];	
			if($tagid>136)
				{
				$tagname=$DB->get_record_sql("SELECT * FROM mdl_tag WHERE id='$tagid' ");
				if($tagname->description!=='book')$tagtitles.=$tagname->name.'|';
				}
		}
*/	 
	$tagtitles=$value['contentstitle'].'보기';
	$question = $DB->get_record_sql("SELECT questiontext AS text FROM mdl_question WHERE id='$contentsid' ");
	$questiontext=$question->text;
	if(strpos($questiontext, 'ifminassistant')!= false)$questiontext=substr($questiontext, 0, strpos($questiontext, "<p>{ifminassistant}"));  
	if(strpos($questiontext, '/MY')!= false&&strpos($questiontext, 'slowhw')!= false)$questiontext='<p> MY A step </p>';
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
	if($value['status']==='returned')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
 		$returned.= '<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$boardid.'&speed=9" target="_blank" >'.$tagtitles.' 개선요청</a></td><td> '.$contentstype.' </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" >편집하기</a></td><td> '.$timemodified.'</td><tr>'; 
		// 반송함... 버튼으로 개선내용을 첨부한다. (계산과정 자세히, 글씨를 .. , 줄맞춰쓰기, 그래프를 그려서 설명해 주세요, 기타 지시사항 추가하기..)
		$nreturned++;
		}
	if($value['status']==='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$complete.= '<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.'('.$nreviewed.'회 복습)</td><td>'.$tagtitles.'</td><td>'.$contentstype.'</td> <td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$boardid.'&speed=9" target="_blank" >재생▶</a></td><td>복원</td><td> '.$timemodified.'</td><tr>'; 
		 //휴지통
		$ncomplete++;
		}
	if($value['nreview']==0 && $value['status']!=='returned' && $value['status']!=='complete' && $value['status']!=='attempt' && $value['turn']==0 )
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$new.= '<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.'</td><td>'.$tagtitles.'</td><td>'.$contentstype.'</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$boardid.'" target="_blank" >'.$author.' 풀이</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$boardid.'&speed=9" target="_blank" >재생▶</a></td><td> '.$timemodified.'</td><tr>'; 
		// 오답노트
		$count0++;
		}
	elseif($value['nreview']==1 && $reviewperiod>$userperiod && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review1.= '<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.' 다시풀기</td><td>'.$tagtitles.'</td><td> '.$contentstype.' </td><td>보충학습</td><td> '.$timemodified.'</td><tr>'; 
 		// 복습1
		$count1++;
		}
	elseif($value['nreview']==2 && $reviewperiod>$userperiod && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review2.='<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.' 다시풀기</td><td>'.$tagtitles.'</td><td> '.$contentstype.' </td><td> 보충학습 </td><td> '.$timemodified.'</td><tr>'; 
		 // 복습2		
		$count2++;
		}
	elseif($value['nreview']==3 && $reviewperiod>$userperiod && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review3.= '<tr><td align="cetner"><input type="checkbox"></td><td> '.$contentstype.' 다시풀기</td><td>'.$tagtitles.'</td><td>'.$contentslink.'</td><td> 보충학습</td><td> '.$timemodified.'</td><tr>'; 
		 // 복습3
		$count3++;
		}
	elseif($value['nreview']==4 && $reviewperiod>$userperiod && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review4.= '<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.' 다시풀기</td><td>'.$tagtitles.'</td><td> '.$contentstype.' </td><td> 보충학습 </td><td> '.$timemodified.'</td><tr>'; 
 		// 복습4
		$count4++;
		}
	elseif($value['nreview']==5 && $reviewperiod>$userperiod && $value['status']!=='complete')
		{
		if($contentstype==='question')$contentstype='문제';
		if($contentstype==='concept')$contentstype='개념';
		$review5.='<tr><td align="cetner"><input type="checkbox"></td><td>'.$contentslink.' 다시풀기</td><td>'.$tagtitles.'</td><td> '.$contentstype.' </td><td> 보충학습 </td><td> '.$timemodified.'</td><tr>'; 
		// 복습5
		$count5++;
		}

	if(time()-$value['timereviewed']<86400*7 && $value['status']!=='complete')$nn_review++;
	if(time()-$value['timereviewed']<86400*7 && $value['status']==='complete')$nn_complete++;	
	}

if($count0!=0)$badgeA='<span class="badge badge-count badge-Success">'.$count0.'</span>';
if($nreturned!=0)$badgeB='<span class="badge badge-count badge-Success">'.$nreturned.'</span>';
if($count1!=0)$badgeC='<span class="badge badge-count badge-Success">'.$count1.'</span>';
if($count2!=0)$badgeD='<span class="badge badge-count badge-Success">'.$count2.'</span>';
if($count3!=0)$badgeE='<span class="badge badge-count badge-Success">'.$count3.'</span>';
if($count4!=0)$badgeF='<span class="badge badge-count badge-Success">'.$count4.'</span>';
if($count5!=0)$badgeG='<span class="badge badge-count badge-Success">'.$count5.'</span>';
if($ncomplete!=0)$badgeH='<span class="badge badge-count badge-Success">'.$ncomplete.'</span>';


echo '
 					 <div class="row">
						<div class="col-md-3">
							<div class="card card-profile card-secondary">
								<div class="card-header" style="background-image: url("../assets/img/blogpost.jpg")">
									<div class="profile-picture">
										<img src="https://mathking.kr/IMG/HintIMG/BESSI1580314759.png" alt="Profile Picture">
									</div>
								</div>
								<div class="card-body">
									<div class="user-profile text-center">
										<div class="name">황선화, 22세</div>
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
									<table width="100%" align=center><tr><th width="50%"> 기억 연장하기 ( 복습주기 : '.$personalperiod.'일 ) </th><th width="30%">복습예약 '.$nn_review.'회 | 학습완료 '.$nn_complete.' 회</th><th width="20%"> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/randomtest.php?id='.$studentid.'&nw=3"><img src="https://prepinsta.com/wp-content/uploads/2019/07/Quiz-icon-for-level-03.png" target="_blank" width=25></a> </th></tr></table>
								</div>
								<div class="card-body">
									<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">오답노트 '.$badgeA.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">반송함 '.$badgeB.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact1-tab" data-toggle="pill" href="#pills-contact1" role="tab" aria-controls="pills-contact1" aria-selected="false">1회 복습 '.$badgeC.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact2-tab" data-toggle="pill" href="#pills-contact2" role="tab" aria-controls="pills-contact2" aria-selected="false">2회 복습 '.$badgeD.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact3-tab" data-toggle="pill" href="#pills-contact3" role="tab" aria-controls="pills-contact3" aria-selected="false">3회 복습 '.$badgeE.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact4-tab" data-toggle="pill" href="#pills-contact4" role="tab" aria-controls="pills-contact4" aria-selected="false">4회 복습 '.$badgeF.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact5-tab" data-toggle="pill" href="#pills-contact5" role="tab" aria-controls="pills-contact5" aria-selected="false">5회 복습 '.$badgeG.'</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact6-tab" data-toggle="pill" href="#pills-contact6" role="tab" aria-controls="pills-contact6" aria-selected="false">휴지통 '.$badgeH.'</a>
										</li>
									</ul>
									<div class="tab-content mb-3" id="pills-tabContent">
										<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"><table style="width: 100%;"><th width="2%"></th><th width="7%"></th><th width="40%"></th><th width="8%"></th><th width="10%"></th><th width="15%"></th><th width="20%"></th>
										'.$new.'<br>
	 									</table></div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"></th><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$returned.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact1" role="tabpanel" aria-labelledby="pills-contact1-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$review1.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact2" role="tabpanel" aria-labelledby="pills-contact2-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"></th><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$review2.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact3" role="tabpanel" aria-labelledby="pills-contact3-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"></th><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$review3.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact4" role="tabpanel" aria-labelledby="pills-contact4-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"></th><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$review4.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact5" role="tabpanel" aria-labelledby="pills-contact5-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"></th><th width="40%"></th><th width="10%"></th><th width="10%"></th><th width="20%"></th>
										'.$review5.'<br>
 										</table></div>
										<div class="tab-pane fade" id="pills-contact6" role="tabpanel" aria-labelledby="pills-contact6-tab"><table style="width: 100%;"><th width="2%"></th><th width="15%"><th width="40%"></th><th width="5%"></th></th><th width="10%"></th><th width="5%"></th><th width="20%"></th>
										'.$complete.'<br>
 										</table></div>
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