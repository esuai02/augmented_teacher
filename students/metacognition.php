<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
include("navbar.php");
 
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentcognitivekick','$timecreated')");

$userid=$studentid;
$timeafter=time()-86400*30;
$wboard=$DB->get_records_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$userid' AND turn LIKE '0' AND timemodified > '$timeafter' AND  status NOT LIKE 'complete' ");
$waitinglist= json_decode(json_encode($wboard), True);
$tab1=NULL;$tab2=NULL;$tab3=NULL;
$count=0;
unset($value);
foreach($waitinglist as $value)
	{	
	$count++;
	$boardid=$value['wboardid'];
	$timemodified=date("Y-m-d", $value['timemodified']);
/*
	if($value['nreview']==0 && $value['status']!=='returned')
		{
		$new.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >새로운 메세지입니다.</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>'; 
		// 새로운 메세지
		}
	elseif($value['status']=='returned')
		{
		$returned.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >오답노트 개선요청이 있습니다.</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>';
		// 반송함... 버튼으로 개선내용을 첨부한다. (계산과정 자세히, 글씨를 .. , 줄맞춰쓰기, 그래프를 그려서 설명해 주세요, 기타 지시사항 추가하기..)
		$nreturned++;
		}
	elseif($value['status']=='complete')
		{
		$complete.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >이해가 완료된 문제입니다.</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>';
		 //휴지통
		$ncomplete++;
		}
	elseif($value['nreview']==1)
		{
		$review1.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >예약된 복습입니다 (1회).</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>'; 
		// 복습1
		$count1++;
		}
	elseif($value['nreview']==2)
		{
		$review2.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >예약된 복습입니다 (2회)</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>';
		 // 복습2		
		$count2++;
		}
	elseif($value['nreview']==3)
		{
		$review3.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >예약된 복습입니다 (3회)</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>';
		 // 복습3
		$count3++;
		}
	elseif($value['nreview']==4)
		{
		$review4.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >예약된 복습입니다 (4회)</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>';
 		// 복습4
		$count4++;
		}
	elseif($value['nreview']==5)
		{
		$review5.= '<tr><td>* </td><td>발송자/발송시간</td><td><a href="http://moreleap.com/replay.php?id='.$boardid.'&speed=9" target="_blank" >예약된 복습입니다 (5회)</a></td><td>내용 정보</td><td>수정일 : '.$timemodified.'</td><tr>'; 
		// 복습5
		$count5++;
		}
*/
	}

echo '
 					 <div class="row">
						<div class="col-md-3">
							<div class="card card-profile card-secondary">
								<div class="card-header" style="background-image: url("../assets/img/blogpost.jpg")">
									<div class="profile-picture">
										<img src="http://mathking.kr/Contents/IMAGES/airecommand.gif" alt="Profile Picture">
									</div>
								</div>
								<div class="card-body">
									<div class="user-profile text-center">
										<div class="name">인공지능 학습도우미</div>
										<div class="job">사용자의 선택정보와 학습결과를 토대로 인공지능이 다음 공부내용을 추천해 드립니다</div>
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
									<h4 class="card-title">인공지능 추천학습 비율이 40%로 설정되었습니다.<h4>
								</div>
								<div class="card-body">
									<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
										<li class="nav-item">
											<a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">단원별</a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">시간별<span class="badge badge-count badge-Danger">'.$nreturned.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact1-tab" data-toggle="pill" href="#pills-contact1" role="tab" aria-controls="pills-contact1" aria-selected="false">난이도별<span class="badge badge-count badge-Success">'.$count1.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact2-tab" data-toggle="pill" href="#pills-contact2" role="tab" aria-controls="pills-contact2" aria-selected="false">최다오답<span class="badge badge-count badge-Success">'.$count2.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact3-tab" data-toggle="pill" href="#pills-contact3" role="tab" aria-controls="pills-contact3" aria-selected="false">복습빈도별<span class="badge badge-count badge-Success">'.$count3.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact4-tab" data-toggle="pill" href="#pills-contact4" role="tab" aria-controls="pills-contact4" aria-selected="false">빈출유형<span class="badge badge-count badge-Success">'.$count4.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact5-tab" data-toggle="pill" href="#pills-contact5" role="tab" aria-controls="pills-contact5" aria-selected="false">킬러유형<span class="badge badge-count badge-Success">'.$count5.'</span></a>
										</li>
										<li class="nav-item">
											<a class="nav-link" id="pills-contact6-tab" data-toggle="pill" href="#pills-contact6" role="tab" aria-controls="pills-contact6" aria-selected="false">휴지통</a>
										</li>
									</ul>
									<div class="tab-content mb-3" id="pills-tabContent">
										<div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"><table style="width: 90%;">
										'.$new.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
	 									</table></div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"><table style="width: 100%;">
										'.$returned.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact1" role="tabpanel" aria-labelledby="pills-contact1-tab"><table style="width: 100%;">
										'.$review1.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact2" role="tabpanel" aria-labelledby="pills-contact2-tab"><table style="width: 100%;">
										'.$review2.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact3" role="tabpanel" aria-labelledby="pills-contact3-tab"><table style="width: 100%;">
										'.$review3.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact4" role="tabpanel" aria-labelledby="pills-contact4-tab"><table style="width: 100%;">
										'.$review4.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact5" role="tabpanel" aria-labelledby="pills-contact5-tab"><table style="width: 100%;">
										'.$review5.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
 										</table></div>
										<div class="tab-pane fade" id="pills-contact6" role="tabpanel" aria-labelledby="pills-contact6-tab"><table style="width: 100%;">
										'.$complete.'인공지능이 기능 활성화를 위하여 사용자 정보를 학습하고 있습니다.
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