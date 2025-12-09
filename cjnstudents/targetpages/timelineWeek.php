<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
// include("navbar.php");
$studentid=required_param('id', PARAM_INT); 
$tbegin=required_param('tb', PARAM_INT); 
$initialT=time()-$tbegin;
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')$url = "https://";   
else $url = "http://";   
$url.= $_SERVER['HTTP_HOST'];   
$url.= $_SERVER['REQUEST_URI'];    
if(strpos($url, 'tbegin')!= false)$tbegin=required_param('tbegin', PARAM_INT); 
else $tbegin=time();
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");




$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by deadline DESC LIMIT 20");										
$result = json_decode(json_encode($missionlist), True);
unset($value);										
foreach($result as $value)										
	{	
	$missionid=$value['id'];
	$plantype=$value['plantype'];
	$text=$value['memo'];										
	$deadline= $value['deadline'];    
	$dateString = date("m-d",$deadline);
	$checkbox='';
	if($value['complete']==1)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png width=30>';
	elseif($timecreated>$deadline)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png width=30>';
	elseif($timecreated<=$deadline && $deadline - $timecreated < 604800)$checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png width=30>';
	else $checkdeadline='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png width=30>';

	if($plantype==='분기목표')$plantype='<b style="color:red;">분기목표</b>  : ';
	elseif($plantype==='방향설정')$plantype='<b style="color:green;">진행순서</b>  : ';
	 
	if($value['plantype']==='장기계획')$timeline1.='<h4 class="timeline-title">'.$plantype.''.$text.''.$dateString.'</h4>';
	else $timeline1.='<h4 class="timeline-title">'.$plantype.''.$text.''.$dateString.'</h4>';
 
	} 

  

$Weekly=$DB->get_record_sql("SELECT min(timecreated) AS tmin FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated > '$initialT' AND type LIKE '주간목표'  ");  
$amonthago=time()-604800*4;
$WeekTimeline=$DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated >= '$amonthago' ORDER BY id  ");  
$result = json_decode(json_encode($WeekTimeline), True);
include("../teachers/shortcuts.php");
$timeline=NULL; 
unset($value);
 
foreach($result as $value)
{
$timecreated= date("m월 d일", $value['timecreated']); 
$showdate=date("m_d", $value['timecreated']); 
$goalid=$value['id'];
 
if($value['type']==='오늘목표' || $value['type']==='검사요청') 
	{
	$timeline.='# '.$value['type'].' : '.$value['text'].'  '.$timecreated.'<hr>';
	}
if($value['type']==='주간목표') $timeline.='<h6><b style="color:blue;"># '.$value['type'].' : '.$value['text'].' </b> '.$timecreated.'</h6><hr>';     
}

echo '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>KAIST TOUCH MATH</title>
	<meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
	<link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon"/>
	 
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
			<div class="content">
				<div class="container-fluid"> <br>
					<!-- TimeLine -->
					<h4 align=center class="page-title">'.$username->firstname.$username->lastname.'의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800"><u>학습목표</u></a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200"><b>오늘활동</b> </a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistorym.php?studentid='.$studentid.'">  메타인지  </a> |<a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1">시간표</a></h4>
					<div class="row">
						<div class="col-md-12"> ';

echo $timeline1.'<hr style="border: solid 1.5px orange;"> '.$timeline;
 

echo ' 
						</div>
					</div>
				</div>
			</div>
	</div>
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>
	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
</body>
</html>';

?>
