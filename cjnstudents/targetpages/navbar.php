<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$studentid=$_GET["id"]; 
$cid = $_GET["cid"]; 
$access = $_GET["access"];

$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
if($USER->id==NULL)header('Location: https://mathking.kr/moodle/my/');

$userdata=$DB->get_records_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$studentid' AND (fieldid='107' OR fieldid='88' OR fieldid='89' OR fieldid='82' OR fieldid='90' OR fieldid='64') "); 
$thisuser = json_decode(json_encode($userdata), True);
unset($value);
$instruction='';
foreach($thisuser as $value)
	{
	if($value['fieldid']==107)$usersex=$value['data'];
	if($value['fieldid']==88)$institute=$value['data'];
	if($value['fieldid']==89)$birthyear=$value['data'];
	if($value['fieldid']==82)$AutopilotMode=$value['data'];
	if($value['fieldid']==90)$usrdata=$value['data'];
	if($value['fieldid']==64)$tsymbol=$value['data'];			
	} 
  
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;   
$timecreated=time();




$username= $DB->get_record_sql("SELECT hideinput,lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$hideinput=$username->hideinput;
$symbol=substr($username->firstname,0, 3); 
$myteacher=$DB->get_record_sql("SELECT max(id) AS id,userid FROM mdl_user_info_data where fieldid=64 AND data LIKE '%$symbol%' "); // 이 주변 삭제
$teacherid=$myteacher->userid;
$tname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$teachername=$tname->firstname.$tname->lastname;
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
if($access==='my' && $role!=='student')header('Location: https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$USER->id.'&tb=7');

$halfdayago=time()-43200;
$aweekago=time()-604800;
$reducetime=0;
$indic= $DB->get_record_sql("SELECT id,nforce,teacherid,weekquizave,ntodo,appraise FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$readtime= $DB->get_record_sql("SELECT max(id) AS id,teacherid FROM mdl_abessi_indicators WHERE userid='$studentid' AND timecreated>'$halfdayago' ");
$mbtilog= $DB->get_record_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='present' ORDER BY id DESC LIMIT 1");
//if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,teacherid,timemodified,timecreated) VALUES('$studentid','$indic->teacherid','$timecreated','$timecreated')");
if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,timemodified,timecreated) VALUES('$studentid','$timecreated','$timecreated')");
$alert_id='alert_ask';  
 

$curl1=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$curl2=strstr($url, '?');  //before
$curl2=str_replace("?","",$curl2);

// 호출 OR Onair 실시간 지도
$timediff=time()-1800;
$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$USER->id' AND (forced='1' OR forced='2') AND timemodified >'$timediff'  ORDER BY id DESC LIMIT 1  ");
if($exist->id!=NULL && $role==='student' && $exist->forced==1 )header('Location:https://mathking.kr/moodle/local/augmented_teacher/students/cometome.php?id='.$studentid.'');
elseif($exist->id!=NULL && $role==='student' && $exist->forced==2)header('Location:https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$exist->wboardid.'');

// 시험목표
 
  
	$tgoal=$checkgoal->timecreated;
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
	
	$wgoal= $DB->get_record_sql("SELECT *  FROM mdl_abessi_today WHERE userid='$studentid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
	 
	$ratio2=$wgoal->score;
	$wtimestart1=$timecreated-86400*($nday+1);
	$wtimestart2=$timecreated-86400*($nday+8);  
	 
	$lastwgoal= $DB->get_record_sql("SELECT id,planscore FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated < '$wtimestart1' AND timecreated > '$wtimestart2' ORDER BY id DESC LIMIT 1");
	$lastWeekPlanScore=$lastwgoal->planscore;

	if($usrdata!=='신규')include_once("intervention.php");

    
$mbtiimg='<img src="https://mathking.kr/Contents/IMAGES/'.$mbtilog->mbti.'icon.png" height=110>';
if($mbtilog->id==NULL)$mbtiimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1668044829.png" width=80>';

$lastScore='<img style="max-width:75%;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png">';
if($ntodo==0)$lastScore='<img style="max-width:75%;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637055855.png">';
elseif($ntodo==7 || $ntodo==8)$lastScore='<img style="max-width:75%;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/dosomething.gif">'; 

echo '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

<script>
var statusIntervalId = window.setInterval(update, 3000);
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
if(isonfocus==1)
{
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
	if(data.mid=="1" )   
	{
	var url=data.context+"?"+data.url;
 				swal({
					title: \'메세지가 도착하였습니다.\',
					text: data.feedback,
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'내용보기\',
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
	if(data.mid=="2")
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
	if(data.mid=="3" )
	{
				swal("화이트보드 첨삭이 도착하였습니다.", "확인하시겠습니까 ?", {
					buttons:{
						confirm: {
							text : \'확인\',
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
	if(data.mid=="4")
	{
				swal("퀴즈 의견이 도착하였습니다.",data.comment, {
					buttons:{
						confirm: {
							text : \'확인\',
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
	else if(data.mid=="5") // 채팅시작
	{
				swal("대화요청이 있습니다.","이동하시겠습니까?", {
					buttons:{
						confirm: {
							text : \'확인\',
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
	else if(data.mid=="7") // 귀가검사
	{
	var url=data.context+"?"+data.url;
 				swal({
					title: \'귀가검사 준비\',
					text: data.feedback,
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
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
}
</script>';

//////////////////////// 오늘의 활동 현황판 //////////////////


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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 
 
<script>
	//== Class definition
	var nremain='.$nremain.';	
	var personalperiod='.$personalperiod.';

	var SweetAlert2Demo = function() {

		//== Demos
		var initDemos = function() {
 

			$(\'#alert_waitamoment\').click(function(e) {
				swal({
					title: \'잠시만요 !\',
					text: "질문이 있습니다.",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'무슨일이니 ?\',
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							text : \'계속진행\',
							className: \'btn btn-danger\'
						}      			

					}
				}).then((willDelete) => {
					if (willDelete) {
						swal("학생에게 필기 시간을 주겠습니다.", {
							icon: "success",
							buttons : {
								confirm : {
									className: \'btn btn-success\'
								}
							}
						});
					} else {
						swal("학생은 30초 동안 필기를 할 수 없습니다.", {
							buttons : {
								confirm : {
									className: \'btn btn-success\'
								}
							}
						});
					}
				});
			})

		};

		return {
			//== Init
			init: function() {
				initDemos();
			},
		};
	}();

	//== Class Initialization
	jQuery(document).ready(function() {
		SweetAlert2Demo.init();
	});
</script>
'; 
//////////////////////// 오늘의 활동 현황판 ////////////////// 
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



.tooltip2:hover .tooltiptext2 {
  visibility: visible;
}
a:hover { color: green; text-decoration: underline;}

.tooltip2 {
 position: relative;
 
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
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
  top:40%;
  right:20%;
  position: fixed;
  z-index: 1;
 
} 
.tooltip2 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip2:hover .tooltiptext6 {
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
  width: 50%;
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
  top:25%;
  left:25%;
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
 
  .paste_image {
  resize: none; /* 사용자 임의 변경 불가 */
  width: 90px;
  height:30px;
  background-color:skyblue;
}
  #canvas,#canvas2
  {

    position:absolute;
  }
      #canvas{
      z-index: 2;
      margin-left:90px;
    }
    #canvas2{
      z-index: 1;
      margin-left:90px;
    }
    .sidenav {
      height: 100%; /* Full-height: remove this if you want "auto" height */
      width: 90px; /* Set the width of the sidebar */
      position: fixed; /* Fixed Sidebar (stay in place on scroll) */
      z-index: 3; /* Stay on top */
      top: 0; /* Stay at the top */
      left: 0;
      background-color: black; /* Black */
      overflow-x: hidden; /* Disable horizontal scroll */
      overflow-y: hidden; /* Disable horizontal scroll */
      padding-top: 5px;
    }
    #jb {
				width: 90%;
				height: 30px;
        position: fixed; /* Fixed Sidebar (stay in place on scroll) */
        top: 0; /* Stay at the top */
        left: 0%;
        z-index: 3; /* Stay on top */
			}

#btn1{ border-top-left-radius: 5px; border-bottom-left-radius: 5px; margin-right:-4px; } 
#btn2{ border-top-right-radius: 5px; border-bottom-right-radius: 5px; margin-left:-3px; } 
#btn_group button{ border: 1px solid skyblue; background-color: rgba(0,0,0,0); color: skyblue; padding: 5px; } 
#btn_group button:hover{ color:white; background-color: skyblue; }
 
      canvas {
        border: 5px dashed grey;
        width=100%;
      }

      .jb_table {
        display: table;
      }

      .row {
        border-radius: 50px;
        display: table-row;
      }

      .cell {
        display: table-cell;
        vertical-align: top;
      }

      textarea {
	width:10px;  
	height:10px;      
	resize:none;
        	background-color: #99ff99;
      }

   <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 

.font-roboto {
  font-family: "roboto condensed";
}

* {
  box-sizing: border-box;
}

body {
  .font-roboto();
}

.modal {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  overflow: hidden;
}

.modal1-dialog {
  position: fixed;
  width: 50%;
  height: 100%;
  top: 0%;
  right: 0%;
}

.modal2-dialog {
position: fixed;
  width:30%;
  height: 98%;
  top: 1%;
  left: 70%;
  border: 5px solid grey;
}
.modal-backdrop {
  display: none !important;
}
.modal-open .modal {
    width: 0%;
    margin: 0 auto;
}
.modal3-dialog {
  position: fixed;
  width:50%;
  height: 100%;
  top: 0%;
  right: 0%;
}
.modal4-dialog {
  position: fixed;
  width: 50%;
  height: 100%;
  top: 0%;
  left: 50%;
}
.modal-content {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  border: 2px solid #3c7dcf;
  border-radius: 0;
  box-shadow: none;
}

.modal-header {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  height: 50px;
  padding: 10px;
  background: #6598d9;
  border: 1;
}
.modal-header2 {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  height: 50px;
  padding: 10px;
  background: #6598d9;
  border: 1;
}
.modal-title {
  font-weight: 300;
  font-size: 2em;
  color: #fff;
  line-height: 30px;
}

.modal-body {
  position: absolute;
  top: 50px;
  bottom: 60px;
  width: 100%;
  font-weight: 300;
  overflow: auto;
}
.modal-body2 {
  position: absolute;
  top: 50px;
  bottom: 60px;
  width: 100%;
  font-weight: 300;
  overflow: auto;
  background: white;
}
.modal-footer {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 60px;
  padding: 10px;
  background: #f1f3f5;
}

.btn {
  height: 40px;
  border-radius: 0;

  // focus
  &:focus,
  &:active,
  &:active:focus {
    box-shadow: none;
    outline: none;
  }
}

.btn-modal {
  position: absolute;
  top: 50%;
  left: 50%;
  margin-top: -20px;
  margin-left: -100px;
  width: 200px;
}

.btn-primary,
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
  font-weight: 300;
  font-size: 0.78rem;
  color: #fff;
  color: lighten(#484b5b, 20%);
  color: #fff;
  text-align: center;
  background: #60cc69;
  border: 1px solid #36a940;
  border-bottom: 3px solid #36a940;
  box-shadow: 0 2px 4px rgba(0,0,0,0.15);

  // active
  &:active {
    border-bottom: 1px solid #36a940;
  }
}

.btn-default,
.btn-default:hover,
.btn-default:focus,
.btn-default:active {
  font-weight: 300;
  font-size: 1.0rem;
  color: #fff;
  text-align: center;
  background: darken(#dcdfe4, 10%);
  border: 1px solid darken(#dcdfe4, 20%);
  border-bottom: 3px solid darken(#dcdfe4, 20%);

  // active
  &:active {
    border-bottom: 1px solid darken(#dcdfe4, 20%);
  }
}

.btn-secondary,
.btn-secondary:hover,
.btn-secondary:focus,
.btn-secondary:active {
  color: #cc7272;
  background: transparent;
  border: 0;
}

h1,
h2,
h3 {
  color: #60cc69;
  line-height: 1.5;

  // first
  &:first-child {
    margin-top: 0;
  }
}

p {
  font-size: 1.4em;
  line-height: 1.5;
  color: lighten(#5f6377, 20%);

  // last
  &:last-child {
    margin-bottom: 0;
  }
}


 input[type=text]{
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}
 
</style>';
 

$userid=$studentid;

  
if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio1<95)
	{
	$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	$reducetime=$reducetime+10;
 	$eventtext.='<tr><td>평점A 10분 </td></tr> ';
	}
else 
	{
	$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	$reducetime=$reducetime+30;
	$eventtext.='<tr><td>평점Aplus 30분</td></tr> ';
	}
if($ratio1==0 && $Qnum2==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

$mark4='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"> '.$ratio1.'% </span>';
 
 

if($ratio2<70)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio2<75)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio2<80)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio2<85)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio2<90)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio2<95)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
    
echo '<script>
function dragChatbox(Studentid)
		{
 		Swal.fire({
		backdrop:false,position:"top-end",showCloseButton: true,width:700,height:800,
		   showClass: {
   		 popup: "animate__animated animate__fadeInRight"
		  },
		  hideClass: {
		   popup: "animate__animated animate__fadeOutRight"
		  },
		  html:
		    \'<iframe  class="foo"  style="border: 0px none; z-index:2; width:680; height:790;margin-left: -40px;margin-top:-10px; overflow-x: hidden; "    src="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid=\'+Studentid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		} 
	</script>';

	echo '
	<script>
	function showMoment(Studentid)
		{
		Swal.fire({
		position:"top-end",showCloseButton: true,
		  html:
		    \'<iframe scrolling="no"  style="border: 1px none; z-index:2; width:400; height:800;  margin-left: -50px;margin-right: -50px;  margin-top: -0px; "  src="https://mathking.kr/moodle/message/index.php?id=\'+Studentid+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}	
	</script>'; 

if($role==='student') // 목순기개 발해숙효
	{
	$userinfo='<b style="font-size:18;">'.$studentname.'</b><br><span style="color:white;font-size:12px;" ></span>';
	$flowtext1='<b style="font-size:20;">목표(目標) : 달성하려고 하는 바람직한 미래의 상태</b><br><br>목표 메타인지가 향상되면 공부에 활력이 생기고 스트레스가 줄어듭니다.';
	$flowtext2='<b style="font-size:20;">순서(順序) : 먼저와 나중, 앞과 뒤 등의 비교를 나타내는 관계</b><br><br>순서 메타인지가 향상되면 공부 흐름이 원활해지고 자신감이 상승합니다.';
	$flowtext3='<b style="font-size:20;">기억(記憶): 뇌에 받아들인 인상, 경험 등 정보를 간직한 것</b><br><br>기억 메타인지가 향상되면 장기기억 루틴이 체화되어 공부의 효율이 올라갑니다.';
	$flowtext4='<b style="font-size:20;">몰입(沒入): 흥미를 가지면서 집중할 수 있는가를 의미</b><br><br>몰입 메타인지가 향상되면 오래된 기억을 쉽고 자세히 떠올릴 수 있습니다.';
	$flowtext5='<b style="font-size:20;">발상(發想) :  어떠한 생각을 해내는 것</b><br><br>발상 메타인지가 향상되면 자신의 경험과 지식을 최대치로 활용할 수 있습니다.';
	$flowtext6='<b style="font-size:20;">해석(解釋): 여러 가지 현상이나 혹은 그 언어에 의한 표현이 지니는 의미를 명확히 함</b><br><br>해석 메타인지가 향상되면 숨어 있는 기억들을 빠르게 찾을 수 있습니다.';
	$flowtext7='<b style="font-size:20;">숙달(熟達) : 기술이나 하는 일을 익숙하게 통달하는 것</b><br><br>숙달 메타인지가 향상되면 기억이 장기화되고 세밀해집니다.';
	$flowtext8='<b style="font-size:20;">효율(效率) : 애쓴 노력과 얻어진 결과의 비율</b><br><br>효율 메타인지가 향상되면 당신은 공부에서 적은 시간으로도 진전을 이룰 수 있습니다.';
	}
else 
	{
	$userinfo='<b style="font-size:18;"><a  style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'" accesskey="v">'.$studentname.'</a></b>';
	$flowtext1='<b style="font-size:20;">목표(目標) : 달성하려고 하는 바람직한 미래의 상태</b><br><br>목표 메타인지가 향상되면 공부에 활력이 생기고 스트레스가 줄어듭니다.<hr>&nbsp;&nbsp;# 적용 : 목표설정 | 귀가검사  ';
	$flowtext2='<b style="font-size:20;">순서(順序) : 먼저와 나중, 앞과 뒤 등의 비교를 나타내는 관계</b><br><br>순서 메타인지가 향상되면 공부 흐름이 원활해지고 자신감이 상승합니다.<hr>&nbsp;&nbsp;# 적용 : 평점하락 | 학습지연 | 표준테스트 점수하락 ';
	$flowtext3='<b style="font-size:20;">기억(記憶): 뇌에 받아들인 인상, 경험 등 정보를 간직한 것</b><br><br>기억 메타인지가 향상되면 장기기억 루틴이 체화되어 공부의 효율이 올라갑니다.<hr>&nbsp;&nbsp;# 적용 : 귀가검사 ';
	$flowtext4='<b style="font-size:20;">몰입(沒入): 흥미를 가지면서 집중할 수 있는가를 의미</b><br><br>몰입 메타인지가 향상되면 오래된 기억을 쉽고 자세히 떠올릴 수 있습니다.<hr>&nbsp;&nbsp;# 적용 : 활동루틴 점검 ';
	$flowtext5='<b style="font-size:20;">발상(發想) :  어떠한 생각을 해내는 것</b><br><br>발상 메타인지가 향상되면 자신의 경험과 지식을 최대치로 활용할 수 있습니다.<hr>&nbsp;&nbsp;# 적용 : 수학내용 피드백 과정 또는 직후 | 온라인 상호작용 ';
	$flowtext6='<b style="font-size:20;">해석(解釋): 여러 가지 현상이나 혹은 그 언어에 의한 표현이 지니는 의미를 명확히 함</b><br><br>해석 메타인지가 향상되면 숨어 있는 기억들을 빠르게 찾을 수 있습니다.<hr>&nbsp;&nbsp;# 적용 : 수학내용 피드백 과정 또는 직후 | 온라인 상호작용';
	$flowtext7='<b style="font-size:20;">숙달(熟達) : 기술이나 하는 일을 익숙하게 통달하는 것</b><br><br>숙달 메타인지가 향상되면 기억이 장기화되고 세밀해집니다.<hr>&nbsp;&nbsp;# 적용 : 습관분석 | 귀가검사 ';
	$flowtext8='<b style="font-size:20;">효율(效率) : 애쓴 노력과 얻어진 결과의 비율</b><br><br>효율 메타인지가 향상되면 당신은 공부에서 적은 시간으로도 진전을 이룰 수 있습니다. <hr>&nbsp;&nbsp;# 적용 : 퀴즈분석 | 습관분석';
	}
$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$weeklyGoalText='<span style="color:white;font-size=15;"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612786844001.png" width=40> 이번 주 목표가 설정되지 않았습니다. </span>';

if($termMission==NULL)$goaldisplay='분기목표를 설정해 주세요';
elseif($weeklyGoal->id==NULL)$goaldisplay= $EGinputtime.'까지 계획이 "<span style="color:#f58d42;">'.$termMission.'</span>"입니다. <span style="color:#f58d42;">주간목표</span>를 입력해 주세요 !';
else $goaldisplay= $EGinputtime.'까지 계획이 "<span style="color:#f58d42;">'.$termMission.'</span>" 이어서 이번 주는 <span style="color:#f58d42;">"'.$weeklyGoal->text.'"</span>(을)를 목표로 정진 중입니다. ';
 
 echo ' 
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 	<!--tslee for korean lang -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>'.$tabtitle.'</title>
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

	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/ready.min.css">

	<!-- CSS Just for demo purpose, don"t include it in your project -->
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>

 
				 
				 ';

  

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
	 
include("flowexpressions.php");
if($ncur==NULL)$ncur=1;

$DB->execute("UPDATE {abessi_mcupdate} SET ncur='$ncur' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");   
 
$itmid=$DB->get_record_sql("SELECT * FROM mdl_abessi_mcpreset where userid LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");

$colstr1='c'.($ncur-1);
$colstr2='c'.$ncur;
$nmc1=$itmid->$colstr1;
$nmc2=$itmid->$colstr2;

$lasttrystr='todoitem'.$nmc1;
$nexttrystr='todoitem'.$nmc2;
  
$mcactive=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE (talkid=17) AND creator LIKE '$userid' ORDER BY id DESC LIMIT 1 ");  
if($mcactive->timemodified<$timecreated-43200)$mcstatus='<b style="color:red;">수학일기 미작성</b>';
else $mcstatus='<span style="color:green;">업데이트</span>';
$ctalk=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE talkid=17 AND creator LIKE '$userid'    ORDER BY id DESC LIMIT 1 ");  
$fbtype=$ctalk->type;

$lastcfeedback1=$mcactive->text.' &nbsp;(<a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$mcactive->type.'"target="_blank">'.$mcactive->type.'</a>)';


$lastcfeedback3=$ctalk->text.' &nbsp;(<a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$fbtype.'"target="_blank">'.$ctalk->type.'</a>)';
$fbtime3=date("m/d",$ctalk->timecreated);
$style1='style=" border: 3px solid #88c2fc; "';
$style2='style=" border: 3px solid #88c2fc; "';
$style3='style=" border: 3px solid #88c2fc; "';
$style4='style=" border: 3px solid #88c2fc; "';
$style5='style=" border: 3px solid #88c2fc; "';
$style6='style=" border: 3px solid #88c2fc; "';
$style7='style=" border: 3px solid #88c2fc; "';
$style8='style=" border: 3px solid #88c2fc; "';

   

echo '
			 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row" style="background-color:white">
					 <div id="navbar">
					 <table align=center><tr><td  width=2%></td>					 
							<td valign=top>
										<table  valign=top align=center><tr valign=top>
										<td valign=top '.$style1.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=목표"><div class="tooltip7"><img style="max-width: 100%;" src="https://mathking.kr/Contents/IMAGES/thinking%20hats/red.png" ><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext1.'</td></tr></table></span></div></a></td>
										<td valign=top '.$style2.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=순서"><div class="tooltip7"><img style="max-width: 100%;" src="https://mathking.kr/Contents/IMAGES/thinking%20hats/orange.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext2.'</td></tr></table></span></div></td>
										<td valign=top '.$style3.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=기억"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/yellow.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext3.'</td></tr></table></span></div></td>
										<td valign=top '.$style4.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=몰입"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/white.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext4.'</td></tr></table></span></div></td></tr>
										<tr><td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/목표%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/순서%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/기억%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/몰입%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td></tr>
										
										<tr valign=top>
										<td valign=top '.$style5.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=발상"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/green.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext5.'</td></tr></table></span></div></td>
										<td valign=top '.$style6.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=해석"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/blue.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext6.'</td></tr></table></span></div></td>
										<td valign=top '.$style7.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=숙달"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/black.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext7.'</td></tr></table></span></div></td>
										<td valign=top '.$style8.' width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=효율"><div class="tooltip7"><img style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/bluem.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext8.'</td></tr></table></span></div></td>
										<td valign=top align=center   width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'" >'.$cjnimg.'</a></td>
										</tr>
										<tr>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/발상%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/해석%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/숙달%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/효율%20메타인지.html"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										</tr></table></td></tr></table>
										 </div>
										<table align=center><tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" ><td><b style="font-size:16px;color:#3498eb;"><기억접속> </b> &nbsp;</td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=66&nch=1&mode=domain&domain=120&studentid='.$studentid.'"target="_blank">수체계</a> | '.$nsetgoal.' </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=61&nch=1&mode=domain&domain=121&studentid='.$studentid.'"target="_blank">지수로그</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=61&nch=8&mode=domain&domain=122&studentid='.$studentid.'"target="_blank">수열</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=66&nch=5&mode=domain&domain=123&studentid='.$studentid.'"target="_blank">식의계산</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=60&nch=1&mode=domain&domain=124&studentid='.$studentid.'"target="_blank">집합명제</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=66&nch=6&mode=domain&domain=125&studentid='.$studentid.'"target="_blank">방정식</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=68&nch=4&mode=domain&domain=126&studentid='.$studentid.'"target="_blank">부등식</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=66&nch=8&mode=domain&domain=127&studentid='.$studentid.'"target="_blank">함수</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=62&nch=1&mode=domain&domain=128&studentid='.$studentid.'"target="_blank">미분</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=62&nch=7&mode=domain&domain=129&studentid='.$studentid.'"target="_blank">적분</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=67&nch=3&mode=domain&domain=130&studentid='.$studentid.'"target="_blank">평면도형</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=59&nch=10&mode=domain&domain=131&studentid='.$studentid.'"target="_blank">평면좌표</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=67&nch=9&mode=domain&domain=132&studentid='.$studentid.'"target="_blank">공간도형</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=65&nch=6&mode=domain&domain=133&studentid='.$studentid.'"target="_blank">공간좌표</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=65&nch=3&mode=domain&domain=134&studentid='.$studentid.'"target="_blank">벡터</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=69&nch=9&mode=domain&domain=135&studentid='.$studentid.'"target="_blank">확률</a> | </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=67&nch=1&mode=domain&domain=136&studentid='.$studentid.'"target="_blank">통계</a></td><td></td></td><td><span onClick="PredictResult()" accesskey="4">(P)</span></td></tr></table>
<table align=center width=95%><tr><td><hr style="border: solid 0.5px lightblue;"> </td></tr></table> 
</div>';

$ts = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);  // 월요일 0시에 대한 time stamp
$timefrom=round((time()-$ts)/86400,3);
echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.'" ></iframe></div>';
echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/local/augmented_teacher/students/stdmarrival.php?userid='.$USER->id.'"></iframe></div>';
echo '
<script>
window.onscroll = function() {myFunction()};

var navbar = document.getElementById("navbar");
var sticky = navbar.offsetTop;

function myFunction() {
  if (window.pageYOffset >= sticky) {
    navbar.classList.add("sticky")
  } else {
    navbar.classList.remove("sticky");
  }
}
</script>
<style>


#navbar {
  overflow: hidden;
  background-color: white;
  z-index:1;
}

.sticky {
  position: fixed;
  top:60px;
  width: 93.8%;
}

.sticky + .content {
  padding-top: 60px;
}
</style>
<script>  
function secondbrain(){
				var Fbtype;
				var Fbtext;
				var Contextid;
				var Fburl;
				var Userid= \''.$studentid.'\'; 	 
              			 $.ajax({
					url: "../students/2ndbrain.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
               			        	}, 
                				success:function(data) 
						{
						Fbtype=data.fbtype;
						Fbtext=data.fbtext;
						Contextid=data.contextid;	
						Fburl=data.fburl;	 
						}
            	   		  	      });
				}	
function secondbrain2()
		{
				var Userid= \''.$studentid.'\'; 
				var Username=\''.$studentname.'\';
				var Fbtype;
				var Fbtext;
				var Contextid;
				var Pagecontext=\''.$curl1.'\';
				var Fburl;
			 	//swal("활동 데이터를 분석 중입니다.", {buttons: false,timer: 800}); 
				if(Pagecontext.indexOf("index")==-1 && Pagecontext.indexOf("selectmission") ==-1  && Pagecontext.indexOf("missionhome") ==-1   )
				{
              			 $.ajax({
					url: "2ndbrain.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
               			        	}, 
                				success:function(data) 
						{
						Fbtype=data.fbtype;
						Fbtext=data.fbtext;
						Contextid=data.contextid;	
						Fburl=data.fburl;	
						Ntodo=data.ntodo;	
						if(Contextid!==Pagecontext && Ntodo<=9 && Ntodo!=6)swal({
								title: Username+\'의 \' + Fbtype ,
								text: Fbtext,
								type: \'warning\',
								buttons:{
								 

									confirm: {
										text : \'확인하기\',
										className : \'btn btn-success\'
									},
									cancel: {
										visible: true,
										text : \'취소\',
										className: \'btn btn-danger\'
									}    


								}
							}).then((willDelete) => {
								if (willDelete) {
								 window.location.href =Contextid+"?"+Fburl;	 					 
								} 
								else {
								 
								}
				
							});
						}
            	   		  	      });
				}
		}; 
</script>';

if($usrdata==='신규')echo '<script>secondbrain2();</script>';
else echo '<script>secondbrain();</script>';
//echo '<script>secondbrain();</script>';

?>
