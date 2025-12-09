<?php 

/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
//$teacher1=$DB->get_record_sql("SELECT data AS name FROM mdl_user_info_data where userid='2' AND fieldid='61' "); 
//$teacher2=$DB->get_record_sql("SELECT data AS name FROM mdl_user_info_data where userid='$USER->id' AND fieldid='62' "); 
//$teacher3=$DB->get_record_sql("SELECT data AS name FROM mdl_user_info_data where userid='$USER->id' AND fieldid='63' "); 
 
 
//$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user LEFT JOIN mdl_user_info_data ON mdl_user.id=mdl_user_info_data.userid 
//WHERE mdl_user.id NOT LIKE '$USER->id' AND ((mdl_user_info_data.fieldid=61 AND mdl_user_info_data.data LIKE '$teacher1->name') OR (mdl_user_info_data.fieldid=62 AND mdl_user_info_data.data LIKE '$teacher2->name') OR (mdl_user_info_data.fieldid=63 AND mdl_user_info_data.data LIKE '$teacher3->name')) ");

//$result= json_decode(json_encode($mystudents), True);
 $username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$USER->id' ");
$tsymbol=substr($username->firstname,0, 3); 

$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
 
$result= json_decode(json_encode($mystudents), True);
 
/////////////////////////// end of code snippet ///////////////////////////
echo '<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>Ready PRO Bootstrap 4 Admin Dashboard</title>
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

	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>
<body>

 
	<div class="wrapper">
		<div class="main-header">
			<!-- Logo Header -->
			<div class="logo-header">
				<!--
					Tip 1: You can change the background color of the logo header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<a href="index.html" class="big-logo">
					<img src="../assets/img/logoresponsive.png" alt="logo img" class="logo-img">
				</a>
				<a href="index.html" class="logo">
					<img src="../assets/img/logoheader.png" alt="navbar brand" class="navbar-brand">
				</a>
				<button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon">
						<i class="la la-bars"></i>
					</span>
				</button>
				<button class="topbar-toggler more"><i class="la la-ellipsis-v"></i></button>
			</div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid">
					<div class="navbar-minimize">
						<button class="btn btn-minimize btn-rounded">
							<i class="la la-navicon"></i>
						</button>
					</div>
					<div class="collapse" id="search-nav">
						<form class="navbar-left navbar-form nav-search ml-md-3 mr-md-3">
							<div class="input-group">
								<input type="text" placeholder="Search ..." class="form-control">
								<div class="input-group-append">
									<button type="submit" class="btn btn-search">
										<i class="la la-search search-icon"></i>
									</button>
								</div>
							</div>
						</form>
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
						<li class="nav-item dropdown hidden-caret">
							<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="flaticon-envelope-1"></i>
							</a>
							<div class="dropdown-menu" aria-labelledby="navbarDropdown">
								<a class="dropdown-item" href="#">Action</a>
								<a class="dropdown-item" href="#">Another action</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#">Something else here</a>
							</div>
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
							<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false"> <img src="../assets/img/profile.jpg" alt="image profile" width="36" class="img-circle"></a>
							<ul class="dropdown-menu dropdown-user animated fadeIn">
								<li>
									<div class="user-box">
										<div class="u-img"><img src="../assets/img/profile.jpg" alt="image profile"></div>
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
							<img src="../assets/img/profile.jpg" alt="image profile">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
									Hizrian
									<span class="user-level">Administrator</span>
									<span class="caret"></span>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
									<li>
										<a href="#profile">
											<span class="link-collapse">My Profile</span>
										</a>
									</li>
									<li>
										<a href="#edit">
											<span class="link-collapse">Edit Profile</span>
										</a>
									</li>
									<li>
										<a href="#settings">
											<span class="link-collapse">Settings</span>
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<ul class="nav">
						<li class="nav-item active">
							<a href="index.html">
								<i class="flaticon-home"></i>
								<p>'.get_string('Dashboard', 'local_augmented_teacher').'</p>
								<span class="badge badge-count">5</span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">'.get_string('realtimedashboard', 'local_augmented_teacher').'</h4>
						</li>
						
						
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/activeusers.php">
								<i class="flaticon-graph"></i>
								<p>'.get_string('activeusers', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-primary">8</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flaggedquestions.php">
								<i class="flaticon-calendar"></i>
								<p>'.get_string('flaggedquestions', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/incorrectresponse.php">
								<i class="flaticon-web"></i>
								<p>'.get_string('incorrectresponses', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/mcdialogue.php">
								<i class="flaticon-web"></i>
								<p>'.get_string('mcdialogue', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/userstoday.php">
								<i class="flaticon-web"></i>
								<p>'.get_string('userstoday', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/allusers.php">
								<i class="flaticon-web"></i>
								<p>'.get_string('allusers', 'local_augmented_teacher').'</p>
								<span class="badge badge-count badge-success">4</span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
						<h4 class="text-section">Contents Authoring</h4>
						</li>
						<li class="nav-item">
							<a data-toggle="collapse" href="#email-nav">
								<i class="flaticon-mailbox"></i>
								<p>'.get_string('Rtalk', 'local_augmented_teacher').'</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="email-nav">
								<ul class="nav nav-collapse">
									<li>
										<a href="email-inbox.html">
											<span class="sub-item">Inbox</span>
										</a>
									</li>
									<li>
										<a href="email-compose.html">
											<span class="sub-item">Email Compose</span>
										</a>
									</li>
									<li>
										<a href="email-detail.html">
											<span class="sub-item">Email Detail</span>
										</a>
									</li>
								</ul>
							</div>
						</li>
				
						<li class="nav-item">
							<a data-toggle="collapse" href="#custompages">
								<i class="flaticon-placeholder"></i>
								<p>'.get_string('Stalk', 'local_augmented_teacher').'</p>
								<span class="caret"></span>
							</a>
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
						</li>
						<li class="nav-item">
							<a data-toggle="collapse" href="#submenu">
								<i class="flaticon-mailbox"></i>
								<p>'.get_string('Qtalk', 'local_augmented_teacher').'</p>
								<span class="caret"></span>
							</a>
							<div class="collapse" id="submenu">
								<ul class="nav nav-collapse">
									<li>
										<a data-toggle="collapse" href="#subnav1">
											<span class="sub-item">Level 1</span>
											<span class="caret"></span>
										</a>
										<div class="collapse" id="subnav1">
											<ul class="nav nav-collapse subnav">
												<li>
													<a href="#">
														<span class="sub-item">Level 2</span>
													</a>
												</li>
												<li>
													<a href="#">
														<span class="sub-item">Level 2</span>
													</a>
												</li>
											</ul>
										</div>
									</li>
									<li>
										<a data-toggle="collapse" href="#subnav2">
											<span class="sub-item">Level 1</span>
											<span class="caret"></span>
										</a>
										<div class="collapse" id="subnav2">
											<ul class="nav nav-collapse subnav">
												<li>
													<a href="#">
														<span class="sub-item">Level 2</span>
													</a>
												</li>
											</ul>
										</div>
									</li>
									<li>
										<a href="#">
											<span class="sub-item">Level 1</span>
										</a>
									</li>
								</ul>
							</div>
						</li>

						<li class="nav-item">
							<a href="invoice.html">
								<i class="flaticon-file-1"></i>
								<p>Invoices</p>
								<span class="badge badge-count">6</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="pricing.html">
								<i class="flaticon-price-tag"></i>
								<p>Pricing</p>
								<span class="badge badge-count">6</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="faqs.html">
								<i class="flaticon-round"></i>
								<p>Faqs</p>
								<span class="badge badge-count">6</span>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- End Sidebar -->

		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
							
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
  width: 400px;
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
// a:visited { color: blue; text-decoration: none;}
  
</style>';

echo '
<style>
.tooltip2 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 400px;
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
  

$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
$sssskey= sesskey(); 
//$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user LEFT JOIN mdl_user_info_data ON mdl_user.id=mdl_user_info_data.userid 
//WHERE mdl_user.id NOT LIKE '$USER->id' AND ((mdl_user_info_data.fieldid=61 AND mdl_user_info_data.data LIKE '$teacher1->name') OR (mdl_user_info_data.fieldid=62 AND mdl_user_info_data.data LIKE '$teacher2->name') OR (mdl_user_info_data.fieldid=63 AND mdl_user_info_data.data LIKE '$teacher3->name')) ");
//$userlist= json_decode(json_encode($mystudents), True);
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
 
$result= json_decode(json_encode($mystudents), True);
 
unset($user); 
foreach($userlist as $user)
{
$userid=$user['id'];
$lastname=$user['lastname'];

$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$userid));
$weektotal=$DB->get_record_sql("SELECT text FROM mdl_checklist_comment where userid='$userid' and itemid='113874' ");

$weektotal->text = preg_replace("/[^0-9.]/", "",$weektotal->text);
$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' ");  
$timeafter=(time()-$Timelastaccess->maxtc);
//if($timeafter<3600)
    {
$daily11=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111296' ");
$daily11->text = preg_replace("/[^0-9.]/", "",$daily11->text);

$daily21=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111297' ");
$daily21->text = preg_replace("/[^0-9.]/", "",$daily21->text);

$daily31=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111298' ");
$daily31->text = preg_replace("/[^0-9.]/", "",$daily31->text);

$daily41=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111299' ");
$daily41->text = preg_replace("/[^0-9.]/", "",$daily41->text);

$daily51=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111300' ");
$daily51->text = preg_replace("/[^0-9.]/", "",$daily51->text);

$daily61=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111301' ");
$daily61->text = preg_replace("/[^0-9.]/", "",$daily61->text);

$daily71=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111302' ");
$daily71->text = preg_replace("/[^0-9.]/", "",$daily71->text);


if($nday==1)
{
$untillastday=0;
$untiltoday=$daily11->text;
}
if($nday==2)
{
$untillastday=$daily11->text;
$untiltoday=$daily11->text+$daily21->text;
}
if($nday==3)
{
$untillastday=$daily11->text+$daily21->text;
$untiltoday=$daily11->text+$daily21->text+$daily31->text;
}
if($nday==4)
{
$untillastday=$daily11->text+$daily21->text+$daily31->text;
$untiltoday=$daily11->text+$daily21->text+$daily31->text+$daily41->text;
}
if($nday==5)
{
$untillastday=$daily11->text+$daily21->text+$daily31->text+$daily41->text;
$untiltoday=$daily11->text+$daily21->text+$daily31->text+$daily41->text+$daily51->text;
}
if($nday==6)
{
$untillastday=$daily11->text+$daily21->text+$daily31->text+$daily41->text+$daily51->text;
$untiltoday=$daily11->text+$daily21->text+$daily31->text+$daily41->text+$daily51->text+$daily61->text;
}
if($nday==0)
{
$untillastday=$daily11->text+$daily21->text+$daily31->text+$daily41->text+$daily51->text+$daily61->text;
$untiltoday=$daily11->text+$daily21->text+$daily31->text+$daily41->text+$daily51->text+$daily61->text+$daily71->text;
}
$compratio1=$Ttime->totaltime/$untillastday*100;
$compratio2=$Ttime->totaltime/$untiltoday*100;
	if($compratio1>90) {
	$mark='<span class="" style="color: rgb(0,0,255);">';
	}else{
	$mark='<span class="" style="color: rgb(255, 0, 0);">';
	} 
if($compratio2<90)echo '<a href="https://mathking.kr/moodle/report/log/user.php?mode=all&course=1&id='.$userid.' " target="_blank" >_'.$mark.$lastname.'</a>(<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&userid='.$userid.' " target="_blank" >'.round($Ttime->totaltime,1).'</a>/<a href=https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from=7&userid='.$userid.' " target="_blank" >'.$untiltoday.'</a>)</span>';
    }
}
echo '<hr 2px>';
*/
/////////////////////////////// /////////////////////////////// 2. Begin of active users list ///////////////////////////////////////////////////////////// 
echo '<table style="width:90%;"><tbody>';
unset($user); 
foreach($userlist as $user)
{
$userid=$user['id'];
$lastname=$user['lastname'];
 
//$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' ");  
$timeafter2=(time()-$Timelastaccess->maxtc);
//if( $timeafter2<3600)  // show row of user's activity for active users ( <3hours)
{
$daily11=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111296' ");
$daily11->text = preg_replace("/[^0-9.]/", "",$daily11->text);
$daily12=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115910' ");
$daily12->text = preg_replace("/[^0-9:]/", "",$daily12->text);
$daily13=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='120905' ");

$daily21=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111297' ");
$daily21->text = preg_replace("/[^0-9.]/", "",$daily21->text);
$daily22=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115914' ");
$daily22->text = preg_replace("/[^0-9:]/", "",$daily22->text);
$daily23=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115933' ");

$daily31=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111298' ");
$daily31->text = preg_replace("/[^0-9.]/", "",$daily31->text);
$daily32=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115917' ");
$daily32->text = preg_replace("/[^0-9:]/", "",$daily32->text);
$daily33=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115934' ");

$daily41=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111299' ");
$daily41->text = preg_replace("/[^0-9.]/", "",$daily41->text);
$daily42=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115918' ");
$daily42->text = preg_replace("/[^0-9:]/", "",$daily42->text);
$daily43=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115935' ");

$daily51=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111300' ");
$daily51->text = preg_replace("/[^0-9.]/", "",$daily51->text);
$daily52=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115919' ");
$daily52->text = preg_replace("/[^0-9:]/", "",$daily52->text);
$daily53=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115936' ");

$daily61=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111301' ");
$daily61->text = preg_replace("/[^0-9.]/", "",$daily61->text);
$daily62=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115921' ");
$daily62->text = preg_replace("/[^0-9:]/", "",$daily62->text);
$daily63=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115937' ");

$daily71=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='111302' ");
$daily71->text = preg_replace("/[^0-9.]/", "",$daily71->text);
$daily72=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115922' ");
$daily72->text = preg_replace("/[^0-9:]/", "",$daily72->text);
$daily73=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='115938' ");

$Ctext01=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='118717' ");
$Ctext02=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='118718' ");
$Ctext03=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='118719' ");
$Ctext04=$DB->get_record_sql("SELECT text, timestamp FROM mdl_checklist_comment where userid='$userid' and itemid='118726' ");
 
$Ttoday=$DB->get_record_sql("SELECT max(time) AS maxtime FROM mdl_hotquestion_questions where userid='$userid' "); 
$todaygoal=$DB->get_record_sql("SELECT content FROM mdl_hotquestion_questions where userid='$userid' AND time='$Ttoday->maxtime' "); 
//$hotq=$DB->get_record_sql("SELECT max(timecreated) AS maxtc, contextinstanceid FROM mdl_logstore_standard_log where userid='$userid' AND action='viewed' AND component='mod_hotquestion' ");  
$treview=round((time()-$hotq->maxtc)/60,0);
if($treview<100 &&$hotq->contextinstanceid==76943)$treview='GL'.$treview;
elseif($treview<100 &&$hotq->contextinstanceid==76968)$treview='RT'.$treview;
elseif($treview<100 &&$hotq->contextinstanceid==76997)$treview='RC'.$treview;
elseif($treview<100 &&$hotq->contextinstanceid==76996)$treview='RD'.$treview;
elseif($treview<100 &&$hotq->contextinstanceid==77088)$treview='RS'.$treview;
else $treview='Rev';
$Ttime =$DB->get_record('block_use_stats_totaltime', array('userid' =>$userid));
$weektotal=$DB->get_record_sql("SELECT text FROM mdl_checklist_comment where userid='$userid' and itemid='113874' ");
$weektotal->text = preg_replace("/[^0-9.]/", "",$weektotal->text);
$compratio=$Ttime->totaltime/$weektotal->text*100;

$lastlog=(time()-$Timelastaccess->maxtc);
 
//$ipageaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtime FROM mdl_logstore_standard_log where component='mod_icontent' AND userid='$userid' "); 
$ipageaccess->maxtime=(time()-$ipageaccess->maxtime)/60;

if($ipageaccess->maxtime>120) $ipageaccess->maxtime=888;
$hwtime= $DB->get_record_sql("SELECT max(created) AS maxtime FROM mdl_studentquiz_comment WHERE userid='$userid' "); 
$timeafter=(time()-$hwtime->maxtime)/60;
$handwriting= $DB->get_record_sql("SELECT comment FROM mdl_studentquiz_comment WHERE userid='$userid' AND created='$hwtime->maxtime' "); 

$lastcomment2=(time()-max($daily11->timestamp,$daily12->timestamp,$daily13->timestamp,$daily21->timestamp,$daily22->timestamp,$daily23->timestamp,$daily31->timestamp,$daily32->timestamp,$daily33->timestamp,
 $daily41->timestamp,$daily42->timestamp,$daily43->timestamp,$daily51->timestamp,$daily52->timestamp,$daily53->timestamp,$daily61->timestamp,$daily62->timestamp,$daily63->timestamp,$daily71->timestamp,$daily72->timestamp,$daily73->timestamp))/86400;
 $lastcomment=(time()-max($Ctext01->timestamp,$Ctext02->timestamp,$Ctext03->timestamp,$Ctext04->timestamp,$Ctext11->timestamp,$Ctext12->timestamp,$Ctext13->timestamp,$Ctext14->timestamp))/86400;

/////////////////////////////// 2-1. prepare  whiteboard , contentpage, users who spent time over planned time for today,
//if($timeafter>120) $timeafter=888;
//$wb='<td>|<a href="'.$handwriting->comment.'?replay&speed=100 " target="_blank"><img src=http://icon-park.com/imagefiles/movie_play_black.png width=20>'.round($timeafter,0).get_string('timeago', 'local_augmented_teacher').'</a></td>
//<td> <a href="'.$handwriting->comment.'  " target="_blank"><img src=https://cdn2.iconfinder.com/data/icons/flat-ui-icons-24-px/24/new-24-512.png width=15></a></td>';

//$ipage=$DB->get_record_sql("SELECT *,max(timecreated) FROM mdl_logstore_standard_log WHERE userid='$userid' AND component='mod_icontent' AND action='viewed' ");
 
$Ttoday=$DB->get_record_sql("SELECT max(time) AS maxtime FROM mdl_hotquestion_questions where userid='$userid' "); 
$approved=$DB->get_record_sql("SELECT approved FROM mdl_hotquestion_questions where userid='$userid' AND time='$Ttoday->maxtime' "); 
$todaygoal=$DB->get_record_sql("SELECT content FROM mdl_hotquestion_questions where userid='$userid' AND time='$Ttoday->maxtime' "); 
 
if(time()-$Ttoday->maxtime>43200 || $Ttoday->maxtime==NULL)
{
$todaygoal->content=NULL;
$mark5='<span class="" style="color: rgb(255, 0, 0);"><b> '.$lastname.'</b></span>';
}else
{
$mark5='<span class="" style="color: rgb(255, 0, 0);"><b> '.$lastname.'</b></span>';
} 
$mark5='<span class="" style="color: rgb(255, 0, 0);"><b> '.$lastname.'</b></span>';
$maxtime=time()-43200;
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$userid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum1=count($recentquestions);
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$userid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum2=count($recentquestions);
$Qnum2=$Qnum1+$Qnum2;


if($Qnum1/$Qnum2<0.7)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($Qnum1/$Qnum2<0.75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($Qnum1/$Qnum2<0.8)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($Qnum1/$Qnum2<0.85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($Qnum1/$Qnum2<0.9)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($Qnum1/$Qnum2<0.95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus2.png';
if($Qnum1/($Qnum2-0.0001)==0 && $Qnum2==0)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
$mark4='<span class="" style="color: rgb(0,0, 0);">'.$Qnum2.'</span>';
 
$maxtime=time()-604800*3;
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$userid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum11=count($recentquestions);
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$userid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum22=count($recentquestions);
$Qnum22=$Qnum11+$Qnum22;
 
if($Qnum11/$Qnum22<0.7)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($Qnum11/$Qnum22<0.75)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($Qnum11/$Qnum22<0.8)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($Qnum11/$Qnum22<0.85)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($Qnum11/$Qnum22<0.9)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($Qnum11/$Qnum22<0.95)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
if($Qnum11/($Qnum22-0.0001)==0 && $Qnum22==0)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png'; 
$mark7='<span class="" style="color: rgb(0,0, 0);">'.$Qnum22.'</span>';
////////// users who spent more time  than that of plan for today

if($nday==1)$timetoday=$daily11->text;
if($nday==2)$timetoday=$daily21->text;
if($nday==3)$timetoday=$daily31->text;
if($nday==4)$timetoday=$daily41->text;
if($nday==5)$timetoday=$daily51->text;
if($nday==6)$timetoday=$daily61->text;
if($nday==0)$timetoday=$daily71->text;
$tcomp=time()-43200;
//$logintoday=$DB->get_record_sql("SELECT min(timecreated) AS mintime FROM mdl_logstore_standard_log where userid='$userid' AND action='loggedin' AND timecreated > '$tcomp' "); 
//$location=$DB->get_record_sql("SELECT ip FROM mdl_logstore_standard_log where userid='$userid' AND action='loggedin'  ORDER BY timecreated DESC LIMIT 1"); 
if(strpos($location->ip, '254')!= false)
	{
	$location2='KTM';
	if(time()-$Ctext03->timestamp<3600*24*28)$location2='<span class="" style="color: rgb(0, 0, 255);">KTM</span>';
	}
else
	{	
	 $location2='OUT';
	if(time()-$Ctext03->timestamp<3600*24*28)$location2='<span class="" style="color: rgb(0, 0, 255);">OUT</span>';
	}

///////////////// ajax to fire popup in a real time by tslee ////////////////////////###################################
//<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
echo '
<script>

function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
    var checkimsi = 0;
    if(Checkvalue==true){
        checkimsi = 1;
    }
   $.ajax({
        url: "check.php",
        type: "POST",
        dataType: "json",
        data : {"userid":Userid,
                "questionid":Questionid,
                "attemptid":Attemptid,
                "checkimsi":checkimsi,
                 "eventid":Eventid,
               },
        success: function (data){  
        }
    });
}

</script>';
////////////////////////////////////////////end of ajax//////////////////////////////////////////////
  
 
/////////////////////////////// 2-3 prepare question icon array  
$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$USER->id' and fieldid='57' ");
if($subject->subject==='MATH')$contains='%MX%';
elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%';

$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$userid' AND  state !='todo' AND  state !='gaveup'  ORDER BY mdl_question_attempt_steps.timecreated DESC LIMIT 2");
$result1 = json_decode(json_encode($questionattempts), True);
 
$marks=NULL;
unset($value);
$ntry=0; 
$ninit=0;
foreach(array_reverse($result1) as $value)
{
$state=NULL;
$helplist=NULL;
$timediff=time()-$value['timecreated'];

if($timediff<3600 && $ninit==0)
{
//echo $timediff.'<br>';
$tperiod_init=$timediff/60;
$ninit=1;
}
$tperiod=$tperiod_init-$timediff/60;
$useridtmp=$userid;
$qidtmp=$value['questionid'];
$status='';
$attemptid=$value['id'];

if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
if(strpos($value['questiontext'], 'shutterstock')!= false)
{
$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '<p style="text-align: right;">'));   
$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
}
if($value['state']==gradedright && $timediff<3600)
{
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
$ntry++;
}
elseif($value['state']==gradedright && $timediff<259200)
{
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right2.png" width=20></a>&nbsp;';
}
elseif($value['state']==gradedpartial && $timediff<3600)
{  
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
$ntry++;
}
elseif($value['state']==gradedpartial && $timediff<259200)
{   
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial2.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
}
elseif($value['state']==gradedwrong && $timediff<3600)
{
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;'; 
$ntry++;
}
elseif($value['state']==gradedwrong && $timediff<259200)
{ 
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
}
elseif($value['state']==complete && $timediff<3600)
{
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
$ntry++;
}
elseif($value['state']==complete && $timediff<259200)
{
$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete2.png" width=20></a>&nbsp;';
}
//elseif($value['state']==gaveup && $timediff<3600)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><div class="tooltip2"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/gaveup1.png" width=20><span class="tooltiptext2">'.$value['questiontext'].'Student Response : '.$value['responsesummary'].'</div></a>&nbsp;';
//elseif($value['state']==gaveup && $timediff<259200)$state='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$value['questionid'].'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/gaveup2.png" width=20></a>&nbsp;';
$marks.=$state; 
}
$t_ave=$tperiod/$ntry;
if($t_ave<1)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed6.png width=25>';
elseif($t_ave<2)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed5.png width=25>';
elseif($t_ave<3)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed4.png width=25>';
elseif($t_ave<6)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed3.png width=25>';
elseif($t_ave<9)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed2.png width=25>';
elseif($t_ave<12)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed1.png width=25>';
elseif($t_ave>12)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png width=25>';
if($t_ave<0)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png width=25>';

//$v_quiz=$t_ave.'/'.$ntry.'<br>';

$quizattempts = $DB->get_record_sql("SELECT max(mdl_quiz_attempts.timestart) AS maxtime FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz WHERE mdl_quiz_attempts.userid='$userid' ");
$lastattempts = $DB->get_record_sql("SELECT  mdl_quiz.name AS name, mdl_quiz_attempts.id AS maxid,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz_attempts.timestart AS maxtime, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades,mdl_course_modules.id AS quizid FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$userid' AND timestart='$quizattempts->maxtime'  ");
$timeafter=(time()-$quizattempts->maxtime);
 
/////////////////////////////// 2-5. prepare tooltip for personal information

if($lastcomment2<21) $plan='<a href="https://mathking.kr/moodle/mod/checklist/report.php?id=65621&editcomments=on&viewall=Add+comments&studentid='.$userid.'" target="_blank">PLAN</a>';
else $plan='<a href="https://mathking.kr/moodle/mod/checklist/report.php?id=65621&editcomments=on&viewall=Add+comments&studentid='.$userid.'" target="_blank" ><span class="" style="color: rgb(239, 69, 64);">PLAN</span></a>';
$goal='GOAL';
if($lastcomment<14) $mission='<a href="https://mathking.kr/moodle/mod/checklist/report.php?id=75362&editcomments=on&viewall=Add+comments&studentid='.$userid.'" target="_blank" >MISSION</a>';
else $mission='<a href="https://mathking.kr/moodle/mod/checklist/report.php?id=75362&editcomments=on&viewall=Add+comments&studentid='.$userid.'" target="_blank" ><span class="" style="color: rgb(239, 69, 64);">MISSION</span></a>';

echo '<tr><td><img src="'.$imgtoday.'" width=20><a href="https://mathking.kr/moodle/mod/hotquestion/view.php?id=76943" target="_blank" ><img src='.$imgtoday2.' width=20></a><div class="tooltip1"><a href="https://mathking.kr/moodle/report/log/user.php?mode=all&course=1&id='.$userid.' " target="_blank" >'.$mark5.'</a><span class="tooltiptext1">    
<br><h5><span class="" align="right"  style="color: rgb(51, 51, 251);">KAIST TOUCH MATH ::: '.$mission.' + '.$plan.' + '.$goal.'  ('.round($Ttime->totaltime,0).' h / '.$weektotal->text.' h) </span></h5>
<hr>
<table align="center" style="width: 100%;">
                    <caption></caption>
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('mon', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left" >
                                <h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('tue', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('wed', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('thu', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">'.get_string('fri', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(42, 100, 211);">'.get_string('sat', 'report_log').'</span></b></h5>
                            </th>

                            <th scope="col" align="left">
                                <h5><b><span class="" align="right"  style="color: rgb(239, 69, 64);">'.get_string('sun', 'report_log').'</span></b></h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>

 		<tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('begin', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily12->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily22->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily32->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily42->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily52->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily62->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily72->text.'</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>'.get_string('time', 'report_log').'</b>&nbsp;&nbsp; &nbsp;</td>
                                <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><span style="font-size: 12.44px;">'.$daily11->text.'</span></td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily21->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily31->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily41->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily51->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily61->text.'</td>

                            <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$daily71->text.'</td>
                        </tr>
                    </tbody>
                </table><hr><h5><span class="" align="center"  style="color: rgb(51, 51, 251);">* '.get_string('Today', 'local_augmented_teacher').$todaygoal->content.' </span></h5>*'.$Ctext01->text.'*'.$Ctext02->text.'<br>*'.$Ctext03->text.'*'.$Ctext04->text.' <hr>';
      
  $gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='112' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='112' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';

	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='121' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='121' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';

	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='157' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='157' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
 
	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='98' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='98' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
  
	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='153' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='153' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a><br>';
 
	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='107' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='107' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
 
 	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='100' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='100' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
 
 	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='123' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='123' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
 
	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='104' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='104' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);
	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a>';
 
	$gettitle=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.pageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='152' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
        $missiontitle=$gettitle->title;  
	$getlink=$DB->get_record_sql("SELECT * FROM mdl_lesson_pages LEFT JOIN mdl_lesson_branch ON  mdl_lesson_pages.id=mdl_lesson_branch.nextpageid 
	WHERE mdl_lesson_branch.userid='$userid' and mdl_lesson_branch.lessonid='152' ORDER BY mdl_lesson_branch.timeseen DESC LIMIT 1");
	if(strpos($getlink->contents, '%')== false)$linkoutput=substr($getlink->contents, strpos($getlink->contents, 'id=',1));
	$linkoutput=str_replace('"}', '', $linkoutput);

	echo '*<a href="https://mathking.kr/moodle/mod/checklist/report.php?'.$linkoutput.'&studentid='.$userid.'" target="_blank">'.$missiontitle.'&nbsp;</a><br>&nbsp;</div>

      
</td><td>'.$mark4.'</td><td>'.$location2.'</td><td>'.round($lastlog/60,0).'"</td><td>'.$v_quiz.'</td><td style="text-align: right;">'.$marks.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >
<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td><td style="text-align: right;"> </td><td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$lastattempts->maxid.' " target="_blank">'.substr($lastattempts->name,0,35).'</a>('.$lastattempts->attempt.get_string('trial', 'local_augmented_teacher').')&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/report.php?mode=statistics&id='.$lastattempts->quizid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/focus-285-588290.png" width=15></a></td><td><span class="" style="color: rgb(239, 69, 64);"> '.round($lastattempts->sgrades/$lastattempts->tgrades*100,0).get_string('points', 'local_augmented_teacher').'</span></td><td>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_quiz%5Cevent%5Cattempt_started&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src="https://cdn3.iconfinder.com/data/icons/text/100/list-512.png" width=18>'.round($timeafter/60,0).get_string('timeago', 'local_augmented_teacher').'</a></td><td>'.$mark7.'</td><td>|<a href="https://mathking.kr/moodle/report/extendedlog/index.php?sesskey='.$sssskey.'&_qf__report_extendedlog_filter_form=1&mform_showmore_id_filter=0&mform_isexpanded_id_filter=0&logreader=logstore_standard&useremail=&relateduser=a&category=a&categoryoptions=category&coursefullname=a&courseshortname=a&component=0&eventname=%5Cmod_icontent%5Cevent%5Cpage_viewed&objecttable=0&objectid=&ip4=&ip6=&submitbutton=Show+events&user=a'.$userid.' " target="_blank" ><img src=https://www.freeiconspng.com/uploads/open-book-icon-32.png width=24></a><a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$ipage->contextinstanceid.'&pageid='.$ipage->objectid.' " target="_blank" >'.round($ipageaccess->maxtime,0).get_string('timeago', 'local_augmented_teacher').'</a></td></tr>';
}}
echo '</tbody></table><hr>'; 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?™t listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?™t take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						 
		<div class="quick-sidebar">
			<a href="#" class="close-quick-sidebar">
				<i class="flaticon-cross"></i>
			</a>
	
	</div>
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
</body>';
?>