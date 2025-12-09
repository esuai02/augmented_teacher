<?php 
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
 
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    

if($USER->id==NULL)header('Location: https://mathking.kr/moodle/login/index.php');
$teacherid= $_GET["id"];
if($USER->id==$teacherid || $role==='manager' ||  strpos($url, 'time_accupancy')!=false )echo '';
else 
	{
	echo '접근권한이 없습니다.';
	 exit();
	}

$result= json_decode(json_encode($mystudents), True);
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
 
/////////////////////////// end of code snippet ///////////////////////////

$networkinglevel=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='71' "); 
$Nlevel=$networkinglevel->data;
$networking=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$teacherid' AND fieldid='69' "); 
$netstatus=$networking->data;
//$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='66' "); 
//$tsymbol2=$teacher2->symbol;

$indicatorsClass=$DB->get_record_sql("SELECT *  FROM mdl_abessi_indicators_class  WHERE timecreated >'$tbegin' AND teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");
$indicatorsSite=$DB->get_record_sql("SELECT *  FROM mdl_abessi_indicators_site  WHERE timecreated >'$tbegin' ORDER BY id DESC LIMIT 1 ");
$ncare=$indicatorsClass->ncare;
$ndanger=$indicatorsClass->ndanger;

//$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='66' "); 
//$tsymbol2=$teacher2->symbol;
include("shortcuts.php");
$timecreated=time(); 
$aweekago=time()-604800;

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
$nenergy_class=$collegues->nenergy;
if($tsymbol==NULL)$tsymbol='KTM';
if($tsymbol1==NULL)$tsymbol1='KTM';
if($tsymbol2==NULL)$tsymbol2='KTM';
if($tsymbol3==NULL)$tsymbol3='KTM';
 
$mngid=$USER->id;
if($role==='manager')$mngid=$teacherid;
 
$info=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$mngid' AND fieldid='46' "); 
$academy=$info->data;

$teachersetting=$DB->get_record_sql("SELECT *  FROM mdl_abessi_teacher_setting  WHERE userid='$teacherid' ORDER BY id DESC LIMIT 1 ");
if($teachersetting->id==NULL)$DB->execute("INSERT INTO {abessi_teacher_setting} (userid, timemodified) VALUES('$teacherid','$timecreated')");
 
if($role==='assistant' || $role==='practitioner' || $role==='manager')$practitionertimetable='<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/settlement.php?id='.$teacherid.'">
								<i class="flaticon-calendar"></i>
								<p>납부현황</p></a></li>';
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

$finduser='<span onClick="showMoment(\''.$teacherid.'\')" accesskey="m"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1602906836001.png width=40></span>';

// 선생님을 위한 instant message
$pagetitle='(주)초지능';
if(strpos($url, 'timetable.php')!= false)$pagetitle='시간표'; 
elseif(strpos($url, 'chainreactionOn.php')!= false)$pagetitle='UL/WM_DMN'; 
elseif(strpos($url, 'selfreactionOn.php')!= false)$pagetitle='자가 피드백';
elseif(strpos($url, 'flowwins.php')!= false)$pagetitle='메타인지';

elseif(strpos($url, 'time_accu_detail.php')!= false)$pagetitle='점유명단';
elseif(strpos($url, 'time_accupancy.php')!= false)$pagetitle='점유분포';
elseif(strpos($url, 'restore_hp.php')!= false)$pagetitle='Restore_HP';

$imgnetworking='https://mathking.kr/Contents/IMAGES/Networking/L'.$Nlevel.'N'.$netstatus.'.jpg';
echo '<head>  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">  
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>'.$pagetitle.'</title>
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
	<div class="wrapper  sidebar_minimize">
		<div class="main-header" style="background-color:#05b4d8;color:white;"> 
			<!-- Logo Header -->

			<div align="center" class="logo-header">	 
			<b><a style="color:white;" href="https://mathking.kr/moodle/my" target="_blank">초지능 T</a></b></div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="light-blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid">
					<!--<div class="navbar-minimize">
						<button class="btn btn-minimize btn-rounded">
							<i class="la la-navicon"></i>
						</button>
					</div>-->
					<div style="white-space:nowrap;color: white;">
				               <h6> &nbsp;&nbsp;&nbsp;<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&tb=604800">Talk2us</a>  &nbsp;&nbsp;<a href="https://www.geogebra.org/calculator"target="_blank">Geogebra</a>&nbsp;&nbsp;&nbsp;자동응답 <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/autoreply.php?id='.$teacherid.'&tb=604800">('.$indicatorsClass->nauto.'건)</a>
&nbsp;&nbsp;&nbsp;&nbsp;침착도&nbsp;<span style="color:#00ffe5;">'.$indicatorsClass->quizscoretoday.' %</span>&nbsp; 전체 <span style="color:#00ffe5;">'.$indicatorsSite->quizscore.' %</span>
&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp; &nbsp;논리 <span style="color:#00ffe5;">'.$indicatorsClass->weeklywbscore.' % </span> &nbsp; 성취 <span style="color:#00ffe5;">'.$indicatorsClass->weeklygrade.' % </span> &nbsp; WAU <span style="color:#00ffe5;">'.$nwau.' </span>
&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;  관심군 <span style="color:#07fc03;"> '.$ncare.'명 </span> | 위험군 <span style="color:#07fc03;"> '.$ndanger.'명 </span> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis.php?id='.$teacherid.'&timefrom=180&timeto=5" target="_blank"><img src=https://cdn1.iconfinder.com/data/icons/color-bold-style/21/43-512.png width=20></a>&nbsp;&nbsp;&nbsp; 파트너사 현황 <a href="https://docs.google.com/spreadsheets/d/1JCHhmNftwtldg9bCcqB5kVOQ2nd_T4Tpdx5JLcAehMw/edit?resourcekey#gid=105206676" target="_blank"><img src=https://cdn1.iconfinder.com/data/icons/color-bold-style/21/43-512.png width=20></a> &nbsp;&nbsp;<a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$teacherid.'" target="_blank"><img src="'.$imgnetworking.'" height=25></a>  </h6></div>
					 
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
    
				 
						<li class="nav-item dropdown hidden-caret">
							<a class="dropdown-toggle profile-pic">'.$finduser.'</a>
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
							<a href="#" class="nav-link quick-sidebar-toggler" accesskey="q">
								<i class="flaticon-envelope-1"></i>
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
<li><a href="https://mathking.kr/moodle/report/log/user.php?course=1&mode=today&id='.$teacherid.'" target="_blank">활동로그</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/teachingroutine.php?id='.$teacherid.'" target="_blank">수업설정</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$teacherid.'" target="_blank">학생모드</a></li>
								</ul>
							</div>
						</div>
					</div>';

if($USER->id==2)
	{
	$settlemeents='
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/classdashboard.php?userid='.$teacherid.'">
								<i class="flaticon-share-1"></i>
								<p>귀가검사</p></a>
						</li>';
	}
if($role!=='student') echo'

					<ul class="nav">
					<!--
						<li class="nav-item active">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/psclass.php?id='.$teacherid.'&tb=7&mode=today">
								<i class="flaticon-home"></i>
								<p>실시간현황판</p>
								 
							</a>
						</li> -->

						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/allusers.php?id='.$teacherid.'">
								<i class="flaticon-user"></i>
								<p>학생들</p>
								 
							</a>
						</li>
						<li class="nav-item"> 
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$teacherid.'&tb=7">
								<i class="flaticon-calendar"></i>
								<p>수업시간표</p>
								 
							</a> 
						</li> 
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$teacherid.'" accesskey="r">
								<i class="flaticon-symbol-1"></i>
								<p>울트라러닝</p>
								 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/parental_appvisit_stat.php?id='.$teacherid.'" accesskey="r">
								<i class="flaticon-symbol-1"></i>
								<p>학부모 앱사용</p>								 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/reduceentropy.php?id='.$teacherid.'">
								<i class="flaticon-user"></i>
								<p>엔트로피 관리</p>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">초연결촉진</h4>
						</li>

 						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/softlanding.php?id='.$teacherid.'">
								<i class="flaticon-search-1"></i>
								<p>신규관리</p> 
								 
							</a>
						</li> 
						<li class="nav-item"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/superagents.php?id='.$teacherid.'">
			<i class="flaticon-symbol-1"></i><p>전파관리</p></a></li>
						'.$settlemeents.'
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/prevent%20churn/index.php?userid='.$teacherid.'">
								<i class="flaticon-symbol-1"></i>
								<p>이탈방지</p>
								 
							</a>
						</li>	
						<!--
 
 						<li class="nav-item">
							<a href="https://mathking.kr/moodle/mod/checklist/view.php?id=89471">
								<i class="flaticon-search-1"></i>
								<p>수업관리</p> 
							  
							</a>
						</li>  
					
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/restore_hp.php?id='.$teacherid.'">
								<i class="flaticon-symbol-1"></i>
								<p>RestoreHP</p>
							 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/questionstamps.php?id='.$teacherid.'">
								<i class="flaticon-symbol-1"></i>
								<p>질의응답</p>
								 
							</a>
						</li>  
						 
						<li class="nav-item"> 
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/selfreactionOn.php?id='.$teacherid.'">
								<i class="flaticon-share-1"></i>
								<p>자가피드백</p>
								 
							</a>
						</li> 
						<li class="nav-item"> 
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flowwins.php?id='.$teacherid.'">
								<i class="flaticon-share-1"></i>
								<p>메타인지</p>
								 
							</a>
						</li> <!--
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">활동현황판</h4>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis.php?id='.$teacherid.'&timefrom=180&timeto=5">
								<i class="flaticon-user"></i>
								<p>운영피드백</p>
								 
							</a>
						</li>
						</li><li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/inspectusers.php?id='.$teacherid.'&tb=604800">
								<i class="flaticon-symbol-1"></i>
								<p>랜덤사용자</p>
							 
							</a>
						</li>

						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/checktoday.php?id='.$teacherid.'">
								<i class="flaticon-symbol-1"></i>
								<p>귀가검사</p>
								 
							</a>
						</li>						
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/schoolexam.php?id='.$teacherid.'">
								<i class="flaticon-symbol-1"></i>
								<p>내신대비</p>
							 
							</a>
						</li>						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/contents_authoring.php?id='.$teacherid.'&tb=604800">
								<i class="flaticon-symbol-1"></i>
								<p>컨텐츠제작</p>
							 
							</a>
						</li> 
						-->

						 		 

						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">초지능 학습지원</h4>
 						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav2">
							<i class="flaticon-pencil"></i><p>온라인 광고</p>
							<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav2">
							<ul class="nav nav-collapse">
							<li><a href="https://blog.naver.com/esuai" target="_blank">
							<i class="flaticon-graph"></i>
							<p>네이버 블로그</p>
							</a>
							</li>
							<li><a href="https://kaisttouch.modoo.at/" target="_blank">
							<i class="flaticon-graph"></i>
							<p>네이버 모두</p>
							</a>
							</li>
									<li>
							<a href="http://blog.daum.net/ktm2008" target="_blank">
								<i class="flaticon-graph"></i>
								<p>다음 블로그</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
									</li>
									<li>
							<a href="https://ads.google.com/aw/express/dashboard?campaignId=10327864539&ocid=518405905&authuser=0&uscid=518405905&__c=4537638345&euid=415656915&__u=8481795835" target="_blank">
								<i class="flaticon-graph"></i>
								<p>구글 광고</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
									</li>
								</ul>
							</div>
						</li>
						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav">
								<i class="flaticon-pencil"></i>
								<p>Mobile</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav">
								<ul class="nav nav-collapse">
									<li>
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/attendance.php?id='.$teacherid.'">
								<i class="flaticon-profile-1"></i>
								<p>출결입력</p>
								<span class="badge badge-count badge-primary">8</span>
							</a>
									</li>
									<li>
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/todayVIPs.php?id='.$teacherid.'">
								<i class="flaticon-graph"></i>
								<p>질의응답</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
									</li>
								</ul>
							</div>
						</li> -->

				  
  					</ul>';
echo '
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
	<script>
	function showMoment(Studentid)
		{
		Swal.fire({
		position:"top-end",showCloseButton: true,
		  html:
		    \'<iframe scrolling="no"  style="border: 1px none; z-index:2; width:400px; height:100vh;  margin-left:0px;margin-right:0px;  margin-top: -0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/selfupdateinfo_mobile.php?id=\'+Studentid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}	
	</script>';
echo '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<style>

@mixin tablet-and-up {
    @media screen and (min-width: 769px) { @content; }
}
@mixin mobile-and-up {
    @media screen and (min-width: 601px) { @content; }
}
@mixin tablet-and-down  {
    @media screen and (max-width: 100%) { @content; }
}
@mixin mobile-only {
    @media screen and (max-width: 100%) { @content; }
}



.sessions{
  margin-top: 2rem;
  border-radius: 12px;
  position: relative;
}

.time{
  color: #2a2839;
  font-family: \'Poppins\', sans-serif;
  font-weight: 500;
  @include mobile-and-up{
    font-size: .9rem;
  }
  @include mobile-only{
    margin-bottom: .3rem;
    font-size: 0.85rem;
  }

}
 
</style> 
'; 
 
echo ' 
<style>
img {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }  
  a {
	user-drag: none; /* for WebKit browsers including Chrome */
	user-select: none; /* for standard-compliant browsers */
	-webkit-user-drag: none; /* for Safari and Chrome */
	-webkit-user-select: none; /* for Safari */
	-moz-user-select: none; /* for Firefox */
	-ms-user-select: none; /* for Internet Explorer/Edge */
  }
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
  top:30;
  left:20%;
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




.tooltip6:hover .tooltiptext6 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip6 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip6 .tooltiptext6 {
    
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
  left:20%;
  position: fixed;
z-index: 1;
 
} 
.tooltip6 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip6:hover .tooltiptext6 {
  visibility: visible;
}


.tooltip7:hover .tooltiptext7 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip7 {
 position: relative;
 
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip7 .tooltiptext7 {
    
  visibility: hidden;
  width: 30%;
  
  background-color: #ffffff;
  color: #e1e2e6;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  border-style: solid;
  border-color: #0aa1bf;
  padding: 20px 1;

  /* Position the tooltip */
  top:100;
  left:20%;
  position: fixed;
  z-index: 1;
 
} 
.tooltip7 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip7:hover .tooltiptext7 {
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
