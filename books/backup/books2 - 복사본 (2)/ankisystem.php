<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$domain=$_GET["dmn"];  
$sbjt=$_GET["sbjt"];   
$studentid=$_GET["studentid"]; 
$nch=$_GET["nch"];
$tid=$_GET["tid"];
//$topicthread=$_GET["tid"];
$viewmode=$_GET["vm"];
if($studentid==NULL)$studentid=$USER->id;
$timecreated=time(); 
$minutesago=$timecreated-300;
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$chnum0=$chnum;
$lastchapter=$DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
if($cid==NULL)$cid=$lastchapter->cid;
require_login();
if($studentid==NULL)$studentid=$USER->id;
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
echo ' <head><title>'.$studentname.' ANKI</title></head><body>';  

$record = new stdClass();
$lastthread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where id>1 ORDER BY id DESC LIMIT 1");  
$mythread=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankithread where studentid='$studentid' AND status LIKE 'begin' AND timecreated > '$minutesago' ORDER BY id DESC LIMIT 1");  

$nextid=$lastthread->id+1;
$topicthread=$nextid;

if($mythread->id!=NULL)$topicthread=$mythread->id;
else
	{
	$record->userid = $USER->id; 
	$record->studentid = $studentid; 
	$record->type = 'topic'; 
	$record->status = 'begin';
	$record->timemodified = $timecreated;
	$record->timecreated = $timecreated;

	$DB->insert_record('abessi_ankithread', $record);	
	}
 
if($topicthread!=NULL)$efts='&tid='.$topicthread;
if($role!=='student') 
	{
	$threadcnt.='<hr><table><tr><td><span  style="cursor:pointer;"><img src="https://mathking.kr/Contents/IMAGES/efts.png" width=30%></span></td></tr></table>';
	}

$chaptertitle='<a style="font-size:20px;text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'">'.$studentname.'</a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cntid=0&nch=6&studentid='.$studentid.'&type=init"><img style="margin-bottom:10px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a>';

if($domain==='science')
	{
	$sname = ['ms11', 'ms12', 'ms21', 'ms22', 'ms31', 'ms32', 'ixh1', 'pxh1', 'pxh2', 'cxh1', 'cxh2', 'bxh1', 'bxh2', 'exh1', 'exh2'];
	$sbjcttitle = ['중등과학 1-1', '중등과학 1-2', '중등과학 2-1', '중등과학 2-2', '중등과학 3-1', '중등과학 3-2', '통합과학', '물리1', '물리2', '화학1', '화학2', '생명과학1', '생명과학2', '지구과학1', '지구과학2'];		
	}
else
	{ 
	$sname = ['e41', 'e42', 'e51', 'e52', 'e61', 'e62', 'm1', 'm2', 'm3', 'm4', 'm5', 'm6', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7'];
	$sbjcttitle = ['초등수학 4-1', '초등수학 4-2', '초등수학 5-1', '초등수학 5-2', '초등수학 6-1', '초등수학 6-2', '중등수학 1-1', '중등수학 1-2', '중등수학 2-1', '중등수학 2-2', '중등수학 3-1', '중등수학 3-2', '고등수학 상', '고등수학 하', '수학 1', '수학 2', '확률과 통계','미분과 적분','기하'];
	} 
$narray=0;
foreach ($sname as $sbjtname) 
	{
	$thissubject=$sbjcttitle[$narray];
	if($sbjtname===$sbjt)$thissubject='<b>'.$thissubject.'</b>';
	$ankisbjtlist.='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjtname.'&nch='.$nch.'&tid='.$tid.'&vm='.$viewmode.'&studentid='.$studentid.$efts.'">'.$thissubject.'</a><br>';

	if($sbjtname===$sbjt)
		{
		$subjectname=$sbjcttitle[$narray];
		
		//if($viewmode=='all')$viewchange='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjtname.'&nch='.$nch.'&vm=collapse&studentid='.$studentid.$efts.'">모두 접기</a>';
		//else $viewchange='<a href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjtname.'&nch='.$nch.'&vm=all&studentid='.$studentid.$efts.'">모두 펼치기</a>';

		for($nchapter=1;$nchapter<30;$nchapter++)
			{
			if($nch==$nchapter)$classname='collapse show';
			else $classname='collapse';
			$curri=$DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE sbjt LIKE '$sbjt'  ");
			$chname='ch'.$nchapter;
			$chaptername=$curri->$chname;
			if($chaptername==NULL)continue;
			if($nch==$nchapter)
				{ 
				$ncolap=$nchapter;
				$scriptontopic1='';	$scriptontopic2='';
				$topics=$DB->get_records_sql("SELECT * FROM mdl_abessi_ankiquiz where contentstype=1 AND subject LIKE '$sbjtname' AND hide LIKE '0' AND chapter LIKE '$nchapter' ORDER BY ntopic ASC   ");  //AND  title NOT LIKE '%Approach%' 
				$result = json_decode(json_encode($topics), True);
				if(count($topics)==0)continue;
				unset($value);
				foreach($result as $value)
					{ 
					$thisquizid=$value['id'];
					if($topicthread!=NULL)
						{
						$threadcheck='<input type="checkbox"  onclick="AddThread(13,'.$studentid.','.$topicthread.','.$thisquizid.',this.checked)"/>';
						}
					$ankilog=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquizlog WHERE quizid='$thisquizid' AND userid='$studentid' ORDER BY id DESC LIMIT 1");
					$nretry=$ankilog->nretry;
					if($nretry==NULL)$nretry=0;

					if($role!=='student')$duplicate=' <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/addanki.php?cntid='.$value['contentsid'].'&cnttype='.$value['contentstype'].'">✜</a>';
					
					if($value['type']!=='original')$scriptontopic1.='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?dmn='.$domain.'&sbjt='.$sbjt.'&qid='.$thisquizid.'&studentid='.$studentid.'"target="_blank">'.$value['topictitle'].'</a> ['.$nretry.']'.$threadcheck.'<br>';
					else $scriptontopic2.='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?dmn='.$domain.'&sbjt='.$sbjt.'&cntid='.$value['contentsid'].'&cnttype='.$value['contentstype'].'&studentid='.$studentid.'"target="_blank">'.$value['topictitle'].'</a> ['.$nretry.'] '.$threadcheck.''.$duplicate.'<br>';
					}
				
				$scriptontopic=$scriptontopic2.'<table><tr><td>------------------------------------------------------------------</td></tr></table>'.$scriptontopic1;
				$chaptercnt='<input   type="checkbox" name="checkAccount" '.$checkstatus.'  onClick="CheckProgress(2,\''.$studentid.'\',\''.$chkitemid.'\', this.checked)"/> <a class="collapsed card-link" style="color:#4287f5;" data-toggle="collapse" href="#collapse'.$ncolap.'"> <span style="color:black;font-size:18;">'.$value['chapter'].$chaptername.'</span></a> ';
				}
			else
				{
				$scriptontopic='';
				$chaptercnt='<input   type="checkbox" name="checkAccount" '.$checkstatus.'  onClick="CheckProgress(2,\''.$studentid.'\',\''.$chkitemid.'\', this.checked)"/> <a class="collapsed card-link" style="color:#4287f5;" href="https://mathking.kr/moodle/local/augmented_teacher/books/ankisystem.php?dmn='.$domain.'&sbjt='.$sbjt.'&nch='.$nchapter.'&tid='.$tid.'&vm=collapse&studentid='.$studentid.'"> <span style="color:black;font-size:18;">'.$nchapter.$chaptername.'</span></a> ';
				} 

			
			$topiclist.='
				<div class="card"  style="font-size:16;">
				  <div class="card-header">
					'.$chaptercnt.'
				  </div>
				  <div id="collapse'.$ncolap.'" class="'.$classname.'" data-parent="#accordion">
					<div class="card-body">
					 '.$scriptontopic.'<br>'.$todoitem.'<br>
					</div>
				  </div>
				</div> ';
			} 
		}
		$narray++;	
	}

 
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
  
.stylish-button {
	background-color: #FF69B4; /* 네온 핑크 색상 */
	color: white;
	padding: 5px 5px;
	width:6vw;
	border: none;
	cursor: pointer;
	font-family: "Arial Rounded MT Bold", sans-serif;
	font-size: 16px;
	transition: background-color 0.3s ease;
  }
  
  .stylish-button:hover {
	background-color: #FF1493; /* 색상을 조금 더 진하게 */
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
  width: 15%;
  padding: 16px;
}
.right-column {
  width: 85%;
  padding: 0px;
}
    /* Left sidebar */
    .left-sidebar {
      width: 15%;
      height: 100%;
      position: fixed; 
      left: 0;
      top: 0;
      background-color: #f1f1f1;
      padding: 20px;
    }

    /* Main body */
    .main-body {
      width: 15%;
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
			<div class="left-sidebar">'.$chaptertitle.'
			<h4><b>KTM ANKI</b></h4>'.$ankisbjtlist.'<br>'.$viewchange.' <br>'.$threadcnt.' 
			</div>
		</div>
		<div class="right-column">
		<div class="colsection"><br>
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
