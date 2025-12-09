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

//$Weekly=$DB->get_record_sql("SELECT min(timecreated) AS tmin FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated > '$initialT' AND type LIKE '주간목표'  ");  
//$timeWeeklyGoal=$Weekly->tmin;
$Aweekago=time()-604800;
$WeekTimeline=$DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE  userid='$studentid' AND timecreated >= '$Aweekago' ORDER BY id  ");  
$result = json_decode(json_encode($WeekTimeline), True);

$timeline=NULL; 
unset($value);
foreach($result as $value)
{
$timecreated= date("20y년 m월 d일 h시i분 A", $value['timecreated']); 
if($value['type']==='오늘목표' || $value['type']==='검사요청') 
	{
	$nday=date( "w", $value['timemodified']);
	$timeline='timeline'.$nday;
/* 
	$amountr=$value['amountr'];
	$amountn=$value['amountn'];
	$amountp=$value['amountp'];
	$rtext1='';$rtext2='';$rtext3='';$ntext1='';$ntext2='';$ntext3='';$ptext1='';$ptext2='';$ptext3='';
	if($value['rtext1']!=NULL)$rtext1='(복습) '.$value['rtext1'];
	if($value['rtext2']!=NULL)$rtext2=' '.$value['rtext2'];
	if($value['rtext3']!=NULL)$rtext3=' '.$value['rtext3'];
	if($value['ntext1']!=NULL)$ntext1='(활동) '.$value['ntext1'];
	if($value['ntext2']!=NULL)$ntext2='  '.$value['ntext2'];
	if($value['ntext3']!=NULL)$ntext3=' '.$value['ntext3'];
	if($value['ptext1']!=NULL)$ptext1='(발표) '.$value['ptext1'];
	if($value['ptext2']!=NULL)$ptext2=' '.$value['ptext2'];
	if($value['ptext3']!=NULL)$ptext3=' '.$value['ptext3'];

	$rtime0=$tgoal+$amountr*60;
	$ntime0=$tgoal+$amountr*60+$amountn*60;
	$ptime0=$tgoal+$amountr*60+$amountn*60+$amountp*60;

	$rtime=date("h:i A", $rtime0);
	$ntime=date("h:i A", $ntime0);
	$ptime=date("h:i A", $ptime0);

$todayplan='<div class="container"  > 귀가시간 : '.$tcomplete.'  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$tstudy.' 시간 경과
   <hr>
    <h6> 복습하기 ... (예상시간 '.$amountr.'분 | '.$rtime.'까지)</h6>
    <ul class="sessions">
     <li>
        <div class="time">'.$rtext1.$rtext2.$rtext3.'</div>
      <p></p>
    </li>
     </ul><hr>
    <h6> 나아가기 ... (예상시간 '.$amountn.'분 | '.$ntime.'까지)</h6>
    <ul class="sessions">
      <li>
        <div class="time">'.$ntext1.$ntext2.$ntext3.'</div>
   <p></p>
      </li>
    </ul><hr>
    <h6> 발표하기 ... (예상시간 '.$amountp.'분 | '.$ptime.'까지)</h6>
    <ul class="sessions">
      <li>
        <div class="time">'.$ptext1.$ptext2.$ptext3.'</div>
      </li>
    </ul>
</div>  ';
*/
	$$timeline.='<li class="timeline-inverted"><div class="timeline-badge primary"><i class="flaticon-stopwatch"></i></div><div class="timeline-panel"><class="timeline-title"><div class="timeline-body"><h4 class="timeline-title">'.$timecreated.' 시작'.$value['text'].'</h4></div> </div></li>';
	}

if($value['type']==='주간목표') $timeline1.='<li><div class="timeline-badge danger"><i class="flaticon-alarm-1"></i></div><div class="timeline-panel"><div class="timeline-heading"><h4 class="timeline-title">'.$value['type'].' : '.$value['text'].' <hr> 학생의견 : '.$value['result'].'</h4></div>
<div class="timeline-body"><p>'.$timecreated.'</p></div></div></li>';
 
}

$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND contentstype=2  AND timemodified>'$Aweekago' AND active=1  ORDER BY timemodified DESC LIMIT 200 ");
$result2 = json_decode(json_encode($handwriting), True);
unset($value);
foreach($result2 as $value)
{
$ndate=date( "w", $value['timemodified']);
$wblist='wblist'.$ndate;
$timecreated= date("20y년 m월 d일 h시i분 A", $value['timemodified']); 
$encryption_id=$value['wboardid'];
$$wblist.='<li class="timeline-inverted"><div class="timeline-badge success"><i class="flaticon-pencil"></i></div><div class="timeline-panel"><class="timeline-title"><div class="timeline-body"><h4 class="timeline-title"><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$encryption_id.'" target="_blank">오답노트 보기</a> </h4> ('.$timecreated.')</div> </div></li>';    
}

$wboardTimeline=$timeline1.$wblist1.$timeline2.$wblist2.$timeline3.$wblist3.$timeline4.$wblist4.$timeline5.$wblist5.$timeline6.$wblist6.$timeline0.$wblist0; 



  
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
					<h4 align=center class="page-title"> '.$username->firstname.$username->lastname.' 의 <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800">공부 계획&목표</a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200">오늘활동</a> |  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWhiteboard.php?id='.$studentid.'&tb=604800"><b><u>풀이노트</b></u></a> | <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1">시간표</a> </h4>
					<div class="row">
						<div class="col-md-12">
							
							<ul class="timeline">';

echo $wboardTimeline;
 
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