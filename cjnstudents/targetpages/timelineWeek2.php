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

$Weekly=$DB->get_record_sql("SELECT min(timecreated) AS tmin FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated > '$initialT' AND type LIKE '주간목표'  ");  
$timeWeeklyGoal=$Weekly->tmin;
$WeekTimeline=$DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated >= '$timeWeeklyGoal' ORDER BY id  ");  
$result = json_decode(json_encode($WeekTimeline), True);

$timeline=NULL; 
unset($value);
 
foreach($result as $value)
{
$timecreated= date("20y년 m월 d일 h시i분 A", $value['timecreated']); 
$goalid=$value['id'];
$timeback=$timecreated-43200;
$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  id='$goalid' ");

$amountr=$checkgoal->amountr;
$amountn=$checkgoal->amountn;
$amountp=$checkgoal->amountp;
$rtext1='';$rtext2='';$rtext3='';$ntext1='';$ntext2='';$ntext3='';$ptext1='';$ptext2='';$ptext3='';
if($checkgoal->rtext1!=NULL)$rtext1='(복습) '.$checkgoal->rtext1;
if($checkgoal->rtext2!=NULL)$rtext2=' '.$checkgoal->rtext2;
if($checkgoal->rtext3!=NULL)$rtext3=' '.$checkgoal->rtext3;
if($checkgoal->ntext1!=NULL)$ntext1='(활동) '.$checkgoal->ntext1;
if($checkgoal->ntext2!=NULL)$ntext2='  '.$checkgoal->ntext2;
if($checkgoal->ntext3!=NULL)$ntext3=' '.$checkgoal->ntext3;
if($checkgoal->ptext1!=NULL)$ptext1='(발표) '.$checkgoal->ptext1;
if($checkgoal->ptext2!=NULL)$ptext2=' '.$checkgoal->ptext2;
if($checkgoal->ptext3!=NULL)$ptext3=' '.$checkgoal->ptext3;

$rtime0=$tgoal+$amountr*60;
$ntime0=$tgoal+$amountr*60+$amountn*60;
$ptime0=$tgoal+$amountr*60+$amountn*60+$amountp*60;

$rtime=date("h:i A", $rtime0);
$ntime=date("h:i A", $ntime0);
$ptime=date("h:i A", $ptime0);

if($value['type']==='오늘목표' || $value['type']==='검사요청') 
	{
	$todayplan='<div class="container"  > 
	 <div class="time">복습 (예상시간 '.$amountr.'분 | '.$rtime.'까지) : '.$rtext1.$rtext2.$rtext3.'</div><hr>
	<div class="time">활동 (예상시간 '.$amountn.'분 | '.$ntime.'까지) : '.$ntext1.$ntext2.$ntext3.'</div><hr>
	<div class="time">정리 (예상시간 '.$amountp.'분 | '.$ptime.'까지) : '.$ptext1.$ptext2.$ptext3.'</div><hr>
	</div>  ';
	$timeline.='<li class="timeline-inverted"><div class="timeline-badge primary"><i class="flaticon-stopwatch"></i></div><div class="timeline-panel"><class="timeline-title"><div class="timeline-body"><h4 class="timeline-title">'.$timecreated.'</h4></div><div class="timeline-heading"><class="timeline-title"><b># '.$value['type'].' : '.$value['text'].'</b><hr>'.$todayplan.'</h4></div></div></li>';
	}
if($value['type']==='주간목표') $timeline1.='<li class="timeline-inverted"><div class="timeline-badge danger"><i class="flaticon-alarm-1"></i></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">'.$value['type'].' : '.$value['text'].' <hr> 학생의견 : '.$value['result'].'</h4></div>
<div class="timeline-body"><p>'.$timecreated.'</p></div></div></li>';
if($value['type']==='미션부여') $timeline.='<li class="timeline-inverted"><div class="timeline-badge success"><i class="flaticon-user"></i></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">'.$value['type'].' : '.$value['text'].'</h4></div>
<div class="timeline-body"><p>'.$timecreated.'</p></div></div></li>';
      
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
		<div align=center class="main-header"> 
		<div  align=center class="photo">
 		</div> </div>
 
 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
 
					<!-- TimeLine -->
					<h4 align=center class="page-title"> '.$username->firstname.$username->lastname.' 의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800"><b><u>주간활동</u></b></a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200">  오늘활동  </a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWhiteboard.php?id='.$studentid.'&tb=604800">  풀이노트  </a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1">시간표</a> </h4>
					<div class="row">
						<div class="col-md-12">
							
							<ul>';

echo $timeline1.$timeline;
 
echo '

 </ul>
						</div>
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