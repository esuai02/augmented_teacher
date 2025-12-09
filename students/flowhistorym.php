<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid = $_GET["studentid"]; 
$fid = $_GET["fid"]; 

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

// 자세 피드백을 통하여 사용법을 교정

//include("flowexpressions.php");

$sex=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='107' "); 
$usersex=$sex->data;
if($usersex==='여')$usersex='woman';
else $usersex='man';
 
$current=$DB->get_record_sql("SELECT * FROM mdl_abessi_mcupdate where userid LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");
for($nchk=1;$nchk<=40;$nchk++)
	{
	$colstr='c'.$nchk;
	if($current->$colstr==0)
		{
		$ncur=$nchk; 
		break;
		}
	}
	
 
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE  creator LIKE '$studentid'  AND  text !='' ORDER BY id DESC LIMIT 30");  
$talklist= json_decode(json_encode($share), True);

unset($value);  
foreach($talklist as $value)
	{
	$sharetext=$value['text'];
	$type=$value['type'];
	$talkcreator=$value['userid'];
	$wboardid=$value['wboardid'];
	$crname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$talkcreator' ");	
	$creatorname='<a style="text-decoration: none; color:black;" href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$talkcreator.'&fid='.$history->id.'"target="_blank">'.$crname->firstname.$crname->lastname.'</a>';

	$tcreated1=date("m월d일 h:i A", $value['timecreated']);   

	

	$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$talkcreator' AND fieldid='22' "); 
	$role2=$userrole2->role;
	if($role2==='student')$bubblestr='bubble';
	else $bubblestr='bubble2';

	$getauthor=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid LIKE '$wboardid' ORDER BY id DESC LIMIT 1 "); 
	$contentsid=$getauthor->contentsid;
 
	$sharelist.='<tr><td valign=top><b style="color:blue;">'.$creatorname.'&nbsp;&nbsp;&nbsp;'.$type.'메타인지 피드백&nbsp;&nbsp;&nbsp;'.date("m월d일 h시m분", $value['timecreated']).'</b><br><br><div class="'.$bubblestr.'">'.$sharetext.' </div><hr></td></tr>';
	} 
echo '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
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
	<div class="wrapper" style"text-align:center;">
			<div class="content" style"text-align:center;">
				<div class="container-fluid" style"text-align:center;"> <br>
					<h4 align=center class="page-title"><table align=center><tr><td style="background-color:#4287f5;color:white;" width=5%></td><td style="background-color:#4287f5;color:white;">KTM 학습지능향상 프로그램 &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=604800"> <img style="position:relative;max-height:100%;top:0;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/main.png width=40></a></td><td style="background-color:#4287f5;color:white;" width=5%></td></tr></table></h4>
					<div class="row" style"text-align:center;">
						<div class="col-md-12" style"text-align:center;">
						';

echo '<table width=100% align=center>'.$sharelist.'</table>';
 
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





 


