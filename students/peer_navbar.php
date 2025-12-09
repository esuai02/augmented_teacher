<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$teacherid=required_param('id', PARAM_INT);
$result= json_decode(json_encode($mystudents), True);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
/////////////////////////// end of code snippet ///////////////////////////
// 선생님과 보조 선생님의 심볼을 이용하여 담당 학생을 선택할 수 있도록 한다.  추후 학생을 역할별 선생님에 할당할 수 있도록 한다.  선생님별 role 선택 메뉴가 있고, 학생들 중 집중관리 내용을 메뉴에서 선택하여 매칭시키는 방식
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='66' "); 
$tsymbol2=$teacher2->symbol;
echo '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
 
<script>
var statusIntervalId = window.setInterval(update, 5000);
var isonfocus=0;  
function update() {

var Contextid=\''.$curl1.'\';
var Currenturl=\''.$curl2.'\';

window.onfocus = function(){  
  isonfocus=1;  
} 
window.onblur = function(){  
  isonfocus=0;  
}  
 
$.ajax({
    url: "/moodle/theme/adaptable/layout/includes/check_status.php",
    type: "POST",
    dataType: "json",
    data : {
	"isactive":isonfocus,	
 	"contextid":Contextid,	
 	"currenturl":Currenturl,	
             },
    success: function (data){
	if(data.mid=="1" && isonfocus =="1")  // 
	{
	var url=data.context+"?"+data.url;
 				swal({
					title: \'메세지가 도착하였습니다.\',
					text: data.feedback,
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'바로가기\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'새창으로\',
							className: \'btn btn-danger\'
						}      			

					},
				}).then((willDelete) => {
					if (willDelete) {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.location.href =url;

					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open(url);
					}
				});
	}
	if(data.mid=="2" && isonfocus == "1")
	{

 				swal("메세지가 도착하였습니다.", data.message, {
					buttons:{
						confirm: {
							text : \'확인완료\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'메세지함\',
							className: \'btn btn-danger\'
						}      			
					},
				}).then((willDelete) => {
					if (willDelete) {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'2\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'2\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open("https://mathking.kr/moodle/message/index.php?id="+data.sender);
					}
				});			
	}
	if(data.mid=="3" && isonfocus =="1")
	{
				swal("화이트보드 첨삭이 도착하였습니다.", "확인하시겠습니까 ?", {
					buttons:{
						confirm: {
							text : \'확인하기\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'새창으로\',
							className: \'btn btn-danger\'
						}      			

					},
				}).then((willDelete) => {
					if (willDelete) {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'3\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.location.href ="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+data.wboardid;
					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'3\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+data.wboardid);
					}
				});
	}      
	if(data.mid=="4" && isonfocus =="1")
	{
				swal("퀴즈 의견이 도착하였습니다.",data.comment, {
					buttons:{
						confirm: {
							text : \'확인하기\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'새창으로\',
							className: \'btn btn-danger\'
						}      			

					},
				}).then((willDelete) => {
					if (willDelete) {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'4\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.location.href ="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+data.userid;
					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'4\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open("https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+data.userid);
					}
				});
	}
	else if(data.mid=="5" && isonfocus == "1") // 채팅시작
	{
				swal("대화요청이 있습니다.","이동하시겠습니까?", {
					buttons:{
						confirm: {
							text : \'시작하기\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'새창으로\',
							className: \'btn btn-danger\'
						}      			

					},
				}).then((willDelete) => {
					if (willDelete) {   
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'5\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.location.href ="https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?id="+data.chatid+"&theme=bubble";
					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'5\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open("https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?id="+data.chatid+"&theme=bubble");
					}
				});
	}
	else if(data.mid=="7" && isonfocus == "1") // 귀가검사
	{
	var url=data.context+"?"+data.url;
 				swal({
					title: \'귀가검사 준비\',
					text: data.feedback,
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'바로가기\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'새창으로\',
							className: \'btn btn-danger\'
						}      			

					},
					 
				}).then((willDelete) => {
					if (willDelete) {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.location.href =url;

					} else {
					$.ajax({
					url: "/moodle/theme/adaptable/layout/includes/check_msg.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"id":data.id,	
					},
					success:function(data){
					alert("success");
					 }
					 });
						window.open(url);
					}
				});
	}
}	 
});
}
</script>';

// 선생님을 위한 instant message
$instantmessage='<div style="color: white; vertical-align: bottom;"><h6 style="vertical-align:bottom;"><b>&nbsp; &nbsp;&nbsp;&nbsp;Message to teacher : 강의실에서 답변완료 목록 20분 내 대응완료하기 ! </b></h6></div>';  // 개인별 instant message 페이지 링크 연결 .. 오늘의 목표 페이지 변형
 
echo '<head>  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">  
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>(주) 초지능의 초연결 학습환경</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon"/>

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
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>
	<div class="wrapper">
		<div class="main-header">
			<!-- Logo Header -->

			<div align="center" class="logo-header">	 
			<b><a href="https://mathking.kr/moodle/my" target="_blank">카이스트 터치수학 T</a></b></div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="purple">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid">
					<div class="navbar-minimize">
						<button class="btn btn-minimize btn-rounded">
							<i class="la la-navicon"></i>
						</button>
					</div>
					<div style="color: white;">
				               '.$instantmessage.'
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
    				  		<li class="nav-item dropdown hidden-caret">
							<a class="nav-link dropdown-toggle" href="https://mathking.kr/moodle/message/index.php?id='.$teacherid.'" target="_blank"  role="button" aria-haspopup="true" aria-expanded="false">
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
												<img src="../assets/img/profile2.jpg" alt="Img Profile">
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
							<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false"> <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1602906836001.png" alt="image profile" width="36" class="img-circle"></a>
							<ul class="dropdown-menu dropdown-user animated fadeIn">
								<li>
									<div class="user-box">
										<div class="u-img"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1602906836001.png" alt="image profile"></div>
										<div class="u-text">
											<h4>Hizrian</h4>
											<p class="text-muted">hello@themekita.com</p><a href="profile.html" class="btn btn-rounded btn-danger btn-sm">View Profile</a>
										</div>
									</div>
								</li>
								<li>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">내 프로파일</a>
									<a class="dropdown-item" href="#">My Balance</a>
									<a class="dropdown-item" href="#">받은 메세지</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">계정 설정</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#">로그아웃</a>
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
							<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1602906836001.png" alt="image profile">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span><span class="user-level"><h5>'.$username->firstname.$username->lastname.'</h5></span>
									<span class="caret"></span>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
<li><a href="https://mathking.kr/moodle/user/profile.php?id='.$teacherid.'" target="_blank">사용자 정보</a></li>
<li><a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$teacherid.'" target="_blank">정보수정</a></li>
<li><a href="https://mathking.kr/moodle/report/log/user.php?course=1&mode=all&id='.$teacherid.'" target="_blank">활동로그</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$teacherid.'" target="_blank">학생모드</a></li>
								</ul>
							</div>
						</div>
					</div>
					<ul class="nav">
						<li class="nav-item active">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$teacherid.'">
								<i class="flaticon-home"></i>
								<p>내 공부방</p>
								<span class="badge badge-count">5</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/peer_allusers.php?id='.$teacherid.'">
								<i class="flaticon-user"></i>
								<p>학생들</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/peer_timetable.php?id='.$teacherid.'&tb=7">
								<i class="flaticon-calendar"></i>
								<p>시간표</p>
								<span class="badge badge-count">6</span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">초연결 학습촉진</h4>
						</li>
						
 
 						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/peer_whiteboards.php?id='.$teacherid.'&tb=172800">
								<i class="flaticon-search-1"></i>
								<p>화이트보드</p> 
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
    

				  
  					</ul>
				</div>
				<div class="collapse" id="custompages">
								<ul class="nav nav-collapse">
									<li>
										<a href="login.html">
											<span class="sub-item">Login</span>
										</a>
									</li>
									<li>
										<a href="userprofile.html">
											<span class="sub-item">User Profile</span>
										</a>
									</li>
									<li>
										<a href="404.html">
											<span class="sub-item">404</span>
										</a>
									</li>
								</ul>
  
				</div>
			</div>
		</div>
		 
	
';
echo ' 
<style>
.tooltip1 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip1 .tooltiptext1 {
    
  visibility: hidden;
  width: 800px;
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
 

.tooltip3:hover .tooltiptext4 {
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
  left:10%;
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





.tooltip4:hover .tooltiptext4 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip4 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip4 .tooltiptext4 {
    
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
  right:10%;
  position: fixed;
z-index: 1;
 
} 
.tooltip4 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip4:hover .tooltiptext4 {
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
  width: 600px;
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
  width: 800px;
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