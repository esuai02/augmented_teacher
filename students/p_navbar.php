<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB;
$studentid= $_GET["id"]; 

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$studentid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;
$instantmessage='&nbsp;&nbsp;&nbsp;현재 특별한 학습상태 메세지가 없습니다.';
include("../teachers/shortcuts.php");

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
 

.tooltip3:hover .tooltiptext1 {
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
  left:5%;
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
 



.tooltip6:hover .tooltiptext6 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip6 {
 position: relative;
 
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip6 .tooltiptext6 {
    
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
.tooltip6 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip6:hover .tooltiptext6 {
  visibility: visible;
}


   


.tooltip7 {
 position: relative;
  
  border-bottom: 0px solid black;
font-size: 14px;
}
.tooltip7:hover .tooltiptext7 {
  visibility: visible;
 
}
a:hover { color: green; text-decoration: underline;}

.tooltip7 .tooltiptext7 {
    
  visibility: hidden;
  width: 80%;
  word-break: keep-all;
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

   
 
a.tooltips {
  position: relative;
  
}
a.tooltips span {
  position: fixed;
  width: 800px;
 height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;
 
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
 box-shadow: 10px 10px 10px #10120f;*/
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

 /* sweet alert 부분 */
 .myDiv {
  width:100%
  background-color: white;    
  color: purple;
  text-align: center;
  right: 50px;
  top: 30px;
  position: fixed;  
}
</style>';
  
 echo ' <head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	 
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

  

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
 
<style>
table {
    display: block;
    overflow-x: scroll;
    white-space: nowrap;
}

 
</style>
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
				-->'.$username->firstname.$username->lastname.'
			<a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=604800">(오늘활동)</a></div>
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

 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row">
 
					</div>';

?>
