<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB;

$studentid=required_param('id', PARAM_INT); 
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$studentid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;
require_login();


$instantmessage='&nbsp;&nbsp;&nbsp;현재 특별한 학습상태 메세지가 없습니다.';
// 상태 메세지 개인화 부분
/*
우선순위대로 if else로 배치해서 filtering 되어 메세지가 나타나게 만든다.

목표입력
점검받기 : 접속시간 후 경과시간을 기준으로 단순하게 접근
공부시간
시험대비..

이부분은 기존의 tw 문서를 보고 정교한 결론에 도달하기 위한 brainstorm이 먼저 종결되어야 한다.

*/
 



 echo ' <head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>'.$username->firstname.$username->lastname.'&nbsp;'.get_string('mydashboard', 'local_augmented_teacher').'</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>

	<!-- Fonts and icons -->
	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Montserrat:100,200,300,400,500,600,700,800,900"]},
			custom: {"families":["Flaticon", "LineAwesome"], urls: ["../assets/css/fonts.css"]},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">

	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>

	<div class="wrapper">
		<div class="main-header">
			<!-- Logo Header -->
			<div class="logo-header">
				<!--
					Tip 1: You can change the background color of the logo header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				--><!--
				<a href="https://mathking.kr/moodle/local/augmented_teacher/mergedmessages.php?page=0&perpage=50&mode&accesssince=0&search='.$tsymbol.'&roleid=0&contextid=0&id=268" target="_blank" class="big-logo">
				 <img src="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" alt="logo img" class="logo-img"> 
				</a>
				<a href="index.html" class="logo">
					 <img src="https://mathking.kr/IMG/HintIMG/BESSI1576728469.png" width=150 alt="navbar brand" class="navbar-brand"> 
				</a>
				<button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon">
						<i class="la la-bars"></i>
					</span>
				</button>
				<button class="topbar-toggler more"><i class="la la-ellipsis-v"></i></button>
				-->
			<a href="https://mathking.kr/moodle/my" target="_blank">KAIST TOUCH MATH</a></div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="light-blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid">
					<div class="navbar-minimize">
						<button class="btn btn-minimize btn-rounded">
							<i class="la la-navicon"></i>
						</button>
					</div>
					<div>
						<span style="color:yellow;float:center;">'.$instantmessage.'</span>
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
						<li class="nav-item dropdown hidden-caret">
							<a class="nav-link dropdown-toggle" href="https://mathking.kr/moodle/message/index.php?id='.$studentid.'" target="_blank"  role="button" aria-haspopup="true" aria-expanded="false">
								<i class="flaticon-envelope-1"></i>
							</a>
	
						</li>
						 
						<li class="nav-item dropdown hidden-caret">
							<a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="flaticon-alarm"></i>
								<span class="notification">3</span>
							</a>
							<ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
								<li>
									<div class="dropdown-title">You have 4 new notification</div>
								</li>
								<li>
									<div class="notif-center">
										<a href="#">
											<div class="notif-icon notif-primary"> <i class="la la-user-plus"></i> </div>
											<div class="notif-content">
												<span class="block">
													New user registered
												</span>
												<span class="time">5 minutes ago</span> 
											</div>
										</a>
										<a href="#">
											<div class="notif-icon notif-success"> <i class="la la-comment"></i> </div>
											<div class="notif-content">
												<span class="block">
													Rahmad commented on Admin
												</span>
												<span class="time">12 minutes ago</span> 
											</div>
										</a>
										<a href="#">
											<div class="notif-img"> 
												<img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg" alt="Img Profile">
											</div>
											<div class="notif-content">
												<span class="block">
													Reza send messages to you
												</span>
												<span class="time">12 minutes ago</span> 
											</div>
										</a>
										<a href="#">
											<div class="notif-icon notif-danger"> <i class="la la-heart"></i> </div>
											<div class="notif-content">
												<span class="block">
													Farrah liked Admin
												</span>
												<span class="time">17 minutes ago</span> 
											</div>
										</a>
									</div>
								</li>
								<li>
									<a class="see-all" href="javascript:void(0);">See all notifications<i class="la la-angle-right"></i> </a>
								</li>
							</ul>
						</li>
						<li class="nav-item dropdown hidden-caret">
							<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false"> <img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg" alt="image profile" width="36" class="img-circle"></a>
							<ul class="dropdown-menu dropdown-user animated fadeIn">
								<li>
									<div class="user-box">
										<div class="u-img"><img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg"  alt="image profile"></div>
										<div class="u-text">
											<h4>Hizrian</h4>
											<p class="text-muted">hello@themekita.com</p><a href="profile.html" class="btn btn-rounded btn-danger btn-sm">View Profile</a>
										</div>
									</div>
								</li>
								<li>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">My Profile</a>
									<a class="dropdown-item" href="#">My Balance</a>
									<a class="dropdown-item" href="#">Inbox</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">Account Setting</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">Logout</a>
								</li>
							</ul>
						</li>
						<li class="nav-item">
							<a href="#" class="nav-link quick-sidebar-toggler">
								<i class="flaticon-shapes-1"></i>
							</a>
						</li>
					</ul>
				</div>
			</nav>
			<!-- End Navbar -->
		</div>

		<!-- Sidebar -->
		<div class="sidebar">
			<!--
				Tip 1: You can change the background color of the sidebar using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				Tip 2: you can also add an image using data-image attribute
			-->
			<div class="sidebar-background"></div>
			<div class="sidebar-wrapper scrollbar-inner">
				<div class="sidebar-content">
					<div class="user">
						<div class="photo">
							<img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg"  alt="image profile">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
								
									<span class="user-level"><h5>'.$username->firstname.$username->lastname.'</h5></span>
									<span class="caret"></span>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
<li><a href="https://mathking.kr/moodle/user/profile.php?id='.$studentid.'" target="_blank">사용자 정보</a></li>
<li><a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'" target="_blank">정보수정</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/payment.php?id='.$studentid.'" target="_blank">출결정보</a></li>
<li><a href="https://mathking.kr/moodle/report/log/user.php?mode=today&course=1&&id='.$studentid.'" target="_blank">활동로그</a></li>
								</ul>
							</div>
						</div>
					</div>
					<ul class="nav">
						<li class="nav-item active">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">
								<i class="flaticon-home"></i>
								<p>내 공부방</p>
								<span class="badge badge-count">5</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="fullengagement.php?id='.$studentid.'">
								<i class="flaticon-users"></i>
								<p>기억 연장하기</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="cognitivekick.php?id='.$studentid.'">
								<i class="flaticon-agenda"></i>
								<p>인공지능 추천</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="calendar.html">
								<i class="flaticon-share-1"></i>
								<p>멘토링 활동</p>
								<span class="badge badge-count">6</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/createdb.php" target="_blank">
								<i class="flaticon-pencil"></i>
								<p>화이트 보드</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						 
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h4 class="text-section">'.get_string('mentoring', 'local_augmented_teacher').'</h4>
						</li>			 
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=43200">
									<i class="flaticon-star"></i>
									<p>'.get_string('todaygoal', 'local_augmented_teacher').'</p>
									<span class="badge badge-count badge-primary">8</span>
									</a>	
						</li>	

						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1">
								<i class="flaticon-calendar"></i>
								<p>'.get_string('schedule', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-primary">8</span>
							</a>
						</li>
						<li class="nav-item">
									<a href="charts.html">
									<i class="flaticon-chat-8"></i>
									<p>'.get_string('missionadvisors', 'local_augmented_teacher').'</p>
									<span class="badge badge-count badge-primary">8</span>
									</a>	
						</li>	
						
						
						<li class="nav-item">
							<a href="timeline.php?id='.$studentid.'">
								<i class="flaticon-analytics"></i>
								<p>'.get_string('timeline', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h4 class="text-section">'.get_string('Mission', 'local_augmented_teacher').'</h4>
						</li>
						<li class="nav-item"> 
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=1&cid=0">
									<i class="flaticon-layers-1"></i>
									<p>'.get_string('Cmission', 'local_augmented_teacher').'</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=2&cid=0">
									<i class="flaticon-agenda-1"></i>
									<p>'.get_string('Dmission', 'local_augmented_teacher').'</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/selectmission.php?id='.$studentid.'&mtid=3&cid=0" target="_blank" >
									<i class="flaticon-desk"></i>
									<p>'.get_string('Xmission', 'local_augmented_teacher').'</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/mod/lesson/view.php?id=65208&pageid=186336&startlastseen=no" target="_blank" >
									<i class="flaticon-symbol-1"></i>
									<p>'.get_string('Mmission', 'local_augmented_teacher').'</p>									
									</a>	
						</li>
						<li class="nav-item">
									<a href="https://mathking.kr/moodle/mod/lesson/view.php?id=66557&pageid=219579&startlastseen=no" target="_blank" >
									<i class="flaticon-list"></i>
									<p>'.get_string('Rmission', 'local_augmented_teacher').'</p>									
									</a>	
						</li>
 
					</ul>
				</div>
			</div>
		</div> 
		<!-- End Sidebar --> 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row">
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1579344522.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">물리학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>										
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1575978058.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">기계공학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>										
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					 	<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<img src="https://mathking.kr/IMG/HintIMG/BESSI1575978150.png" height=80>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">전산학과</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>
												
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<img src="https://mathking.kr/IMG/HintIMG/BESSI1575983518.png" height=80> 
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">KAIST</h6>
												<p class="card-category">바이오 및 뇌</p>
												<a href="" ><img src="https://cdn4.iconfinder.com/data/icons/vectory-bonus-2/40/mail_send_4-512.png" width=30></a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>';

?>