<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$studentid=$_GET["studentid"]; 
 
$viewmode=$_GET["vm"];
 
$timecreated=time(); 
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$chnum0=$chnum;
$lastchapter=$DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
if($cid==NULL)$cid=$lastchapter->cid;
require_login();
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
echo ' <head><title>'.$studentname.' 노트필기</title></head><body>';  

$begintime=$timecreated-604800*52;
$monthsago=$timecreated-604800*12; //3개월전
 
$notepages=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND status='usernotebook' AND timecreated > '$begintime' ORDER BY id DESC LIMIT 52"); 
$result = json_decode(json_encode($notepages), True);

unset($value);
foreach($result as $value)
	{  
	$wboardid=$value['wboardid'];
	$thismonth=date('Y년 m월', $value['timecreated']);
	if($monthprev!==$thismonth)
		{
		if($cntlist==NULL)$cntlist='<br>성취의 기쁨을 맛보는 공간, 카이스트 터치수학 학원';
		$topiclist.='<div class="card"  style="font-size:16;"><table><tr><td width=3%></td><td>'.$cntlist.'</td></tr></table><br></div> ';
		$cntlist='<br>'.$thismonth.' : '; 
		}
	$cntlist.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_memo.php?id='.$wboardid.'&studentid='.$studentid.'"target="_blank"> 노트 '.date('Y-m-d', $value['timecreated']).'</a> &nbsp;&nbsp;';
	$monthprev=date('Y년 m월', $value['timecreated']);
	}

 
$topiclist.='<div class="card"  style="font-size:16;"><table>'.$cntlist.'</table><br></div> ';

$progressbar='<div class="progress-card">
<div class="demo">
  <div class="progress-card">
	<div class="progress-status"></div>
	<div class="progress" style="background-color:#bdbdbd; height:15px;">
	  <div class="progress-bar progress-bar-striped bg-'.$bgtype.'" role="progressbar" style="width: '.$progressfilled.'%; height: 15px;" aria-valuenow="'.$progressfilled.'" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="" data-original-title="'.$progressfilled.'%"></div>
	</div>
  </div>
</div>
</div>
';

echo '<!DOCTYPE html>
<html>
<head>
  <title>Bootstrap Example</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
 
  <style>
  

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



.stylish-button {
	background-color: #f1f1f1; /* 네온 핑크 색상 */
	color: white;
	padding: 5px 5px;
	width:6vw;
	text-align: right;
	border: none;
	cursor: pointer;
	font-family: "Arial Rounded MT Bold", sans-serif;
	font-size: 16px;
	transition: background-color 0.3s ease;
  }
  
  .stylish-button:hover {
	background-color:#91ff93; /* 색상을 조금 더 진하게 */
  }
  
  .stylish-button:active {
	transform: translateY(2px);
  }
  
  .stylish-button:focus {
	outline: none;
  }

  
  #tableContainer {
	opacity: 0;
	transition: opacity 0.5s ease;
  }
  #tableContainer.active {
	opacity: 1;
  } 
  
  .container {
  display: flex;
}

.left-column{
  width: 20%;
  padding: 16px;
}
.right-column {
  width: 80%;
  padding: 0px;
}
    /* Left sidebar */
    .left-sidebar {
      width: 20%;
      height: 100%;
      position: fixed; 
      left: 0;
      top: 0;
      background-color: #f1f1f1;
      padding: 20px;
    }

    /* Main body */
    .main-body {
      width: 20%;
      height: 100%;
    } 

    /* Collapsible button */
    .collapsible {
      background-color: #eee;
      color: #444;
      cursor: pointer;
      padding: -0px;
      width: 85%;
      border: none;
      text-align: left;
      outline: none;
      font-family: Arial, sans-serif;
      font-size: 16px;
    }
    .colsection {
      width: 79%;
      height: 100%;
      position: absolute;
      left: 10%;
      top: 0;
      background-color: #f1f1f1;
      padding: 0px;
    }
    /* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
    .active, .collapsible:hover {
      background-color: #ccc;
    }

    /* Style the collapsible content */
    .content {
      padding: 0 18px;
      display: none; 
      overflow: hidden;
      background-color: #f1f1f1;
      font-family: Arial, sans-serif;
      font-size: 16px;
    }
  </style>
 
</head>
<body>
 
	<div class="container">
		<div class="left-column">
			<div class="left-sidebar"> 
			<table><tr><td style="text-align:right;"><b>'.$studentname.'의<br></b><br>'.$btnlist.'</td></tr></table>
			</div>
		</div>
		<div class="right-column">
		<div class="colsection">
		<table width=80%><tr><td width=5%></td><td> </td><td></td> <td width=1%> </td> <td align=center></td></tr></table><table><tr><td> </td></tr></table>
			<div id="accordion">
			'.$topiclist.'
			</div>
		 
		</div>
		</div>
	</div>

</body>
</html>
';
 
echo '	
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<script>
document.addEventListener("DOMContentLoaded", function() {
	const tableContainer = document.getElementById("tableContainer");
	
	document.addEventListener("mousemove", function(event) {
	  const rect = tableContainer.getBoundingClientRect();
	  const x = event.clientX, y = event.clientY;

	  if (x > rect.left && x < rect.right && y > rect.top && y < rect.bottom) {
		tableContainer.classList.add("active");
	  } else {
		tableContainer.classList.remove("active");
	  }
	});
  });

 // //(Eventid,Userid,Cid,Domainid,Chapterid,Topicid)
function ImmersiveSession(Eventid,Userid,Cid,Domainid,Chapterid,Topicid)
	{
	var Createmode= \''.$createmode.'\';
	if(Createmode==7)
		{
		swal("독립세션 설계모드가 종료됩니다.", {buttons: false,timer: 2000}); 
		$.ajax({
			url: "check_status.php", 
			type: "POST",
			dataType: "json",
			data : {
					"eventid":Eventid,
					"createmode":Createmode,
					"userid":Userid,       
					"cid":Cid,
					"domainid":Domainid,
					"chapterid":Chapterid,
					"topicid":Topicid,
					},
			success: function (data){  
			}
			});
		setTimeout(function() {location.reload(); },1000);
		}
	else
		{
		swal("독립세션 설계모드가 시작됩니다.", {buttons: false,timer: 2000}); 
		$.ajax({
			url: "check_status.php", 
			type: "POST",
			dataType: "json",
			data : {
					"eventid":Eventid,
					"createmode":Createmode,
					"userid":Userid,       
					"cid":Cid,
					"domainid":Domainid,
					"chapterid":Chapterid,
					"topicid":Topicid,
					},
			success: function (data){  
			}
			});
		setTimeout(function() {location.reload(); },1000);
		}
	}
function createThread(Studentid)
	{ 
	var Eventid=14;
   	$.ajax({
		url: "../LLM/check_status.php",
		type: "POST",
		dataType: "json",
		data : {
				"eventid":Eventid,
				"studentid":Studentid
			   },
			success:function(data)
				{
				swal("맞춤형 ANKI를 추가하실 수 있습니다.", {buttons: false,timer: 2000}); 
				var Nextid=data.nextid;
				setTimeout(function() {
					//location.reload();
					window.open("https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjt.'&studentid='.$studentid.'&nch='.$nch.'&vm=all&tid="+Nextid, "_self");
					}, 1000);
				}
		});
  
	}
function AddThread(Eventid,Userid,Threadid,Quizid,Checkvalue)
	{

	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
	}
   	$.ajax({
		url: "../LLM/check_status.php",
		type: "POST",
		dataType: "json",
		data : {
		"eventid":Eventid,
		"userid":Userid,
		"threadid":Threadid,
		"quizid":Quizid,
		"checkimsi":checkimsi,               
			  },
			success:function(data){
				swal("선택되었습니다.", {buttons: false,timer: 100}); 
				//setTimeout(function() {
					//location.reload();
				//	window.open("https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjt.'&nch='.$nch.'&studentid='.$studentid.'&tid="+Threadid, "_self");
				//	}, 1000);
				
				}
		});
  
	}
 
 
function CheckProgress(Eventid,Userid,Itemid, Checkvalue){
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
	}
	
   $.ajax({
		url: "check_status.php", 
		type: "POST",
		dataType: "json",
		data : {"userid":Userid,       
				"cntid":Itemid,
				"checkimsi":checkimsi,
				"eventid":Eventid,
			   },
		success: function (data){  
		}
	});
	setTimeout(function() {location.reload(); },100);
}	
</script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
'; 
?>
