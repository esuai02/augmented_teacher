<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$studentid=$_GET["id"]; 
$cid = $_GET["cid"]; 
$access = $_GET["access"];
if($studentid==NULL)$studentid=$USER->id;
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
//$teacherid=$myteacher->userid;
//$tname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
//$teachername=$tname->firstname.$tname->lastname;
$username= $DB->get_record_sql("SELECT * FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
if($access==='my' && $role!=='student')header('Location: https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$USER->id.'&tb=7');

$halfdayago=time()-43200;
$aweekago=time()-604800;
$reducetime=0;
$indic= $DB->get_record_sql("SELECT id,nforce,teacherid,weekquizave,ntodo,appraise FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$tabtitle=$username->lastname;
 
$readtime= $DB->get_record_sql("SELECT max(id) AS id,teacherid FROM mdl_abessi_indicators WHERE userid='$studentid' AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1 ");
$teacherid0=$indic->teacherid;
if($teacherid0==NULL)$teacherid0=$USER->id;
$mbtilog= $DB->get_record_sql("SELECT * FROM mdl_abessi_mbtilog WHERE userid='$studentid' AND type='present' ORDER BY id DESC LIMIT 1"); 
if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,teacherid,timemodified,timecreated) VALUES('$studentid','$teacherid0','$timecreated','$timecreated')");
 
//$alert_id='alert_ask';
//alert_index  alert_fullengagement  alert_schedule alert_edittoday  alert_today  alert_missionhome alert_selectmission  alert_editschedule  alert_cognitivism  alert_roadmap
/*
if(strpos($url, 'index.php')!= false){$ailink='ai_index.html';$alert_id='alert_index';$tabtitle=$username->lastname.'H';}
elseif(strpos($url, 'fullengagement.php')!= false){$ailink='ai_fullengagement.html';$alert_id='alert_fullengagement';$tabtitle=$username->lastname.'R';}
elseif(strpos($url, 'schedule.php')!= false){$ailink='ai_schedule.html';$alert_id='alert_schedule';$tabtitle=$username->lastname.'S';}
elseif(strpos($url, 'edittoday.php')!= false){$ailink='ai_edittoday.html';$alert_id='alert_edittoday';$tabtitle=$username->lastname.'E';}
elseif(strpos($url, 'today.php')!= false||strpos($url, 'today_agamotto.php')!= false){$ailink='ai_today.html';$alert_id='alert_today';$tabtitle=$username->lastname.'D';}
elseif(strpos($url, 'missionhome.php')!= false){$ailink='ai_missionhome.html';$alert_id='alert_missionhome';$tabtitle=$username->lastname.'M';}
elseif(strpos($url, 'selectmission.php')!= false){$ailink='ai_selecthome.html';$alert_id='alert_selectmission';$tabtitle=$username->lastname.'C';}
elseif(strpos($url, 'editschedule.php')!= false){$ailink='ai_editschedule.html';$alert_id='alert_editschedule';$tabtitle=$username->lastname.'E';}
elseif(strpos($url, 'timeline')!= false){$tabtitle=$username->lastname.'H';}

elseif(strpos($url, 'cognitivism.php')!= false){$ailink='ai_cognitivism.html';$alert_id='alert_cognitivism';$tabtitle=$username->lastname.'Q';}
elseif(strpos($url, 'roadmap.php')!= false){$ailink='ai_roadmap.html';$alert_id='alert_roadmap';$tabtitle=$username->lastname.'G';}

*/
$examplan=$DB->get_record_sql("SELECT id, wboardid FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='period' ORDER BY id DESC LIMIT 1");
$examplanid=$examplan->wboardid;
 
 
$weeklyquizave=$indic->weekquizave;
$ntodo=$indic->ntodo;
$nforce=$indic->nforce;
/*
if($AutopilotMode==='AI' && $role!=='student') // 새로운 관찰학생 onair 열기 , 닫는 것은 onair page에서 수행
		{
 		echo '<script>setTimeout(function() {window.close(); },3000000);  </script>';
		}  
*/
$curl1=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$curl2=strstr($url, '?');  //before
$curl2=str_replace("?","",$curl2);

// 호출 OR Onair 실시간 지도
$timediff=time()-1800;
/*
$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$USER->id' AND (forced='1' OR forced='2') AND timemodified >'$timediff'  ORDER BY id DESC LIMIT 1  ");
if($exist->id!=NULL && $role==='student' && $exist->forced==1 )header('Location:https://mathking.kr/moodle/local/augmented_teacher/students/cometome.php?id='.$studentid.'');
elseif($exist->id!=NULL && $role==='student' && $exist->forced==2)header('Location:https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$exist->wboardid.'');
*/
// 시험목표
 
$engagement1 = $DB->get_record_sql("SELECT max(id),url,timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  AND eventid=17   ");  // missionlog
$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
$engagement3 = $DB->get_record_sql("SELECT id,todayscore,speed, tlaststroke,timecreated FROM  mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1  "); 

$tlastinput=$checkgoal->timecreated;
$tcomplete0=$engagement3->timecreated+$hours*3600;
$tcomplete=date("h:i A", $tcomplete0);

//$ratio1=$engagement3->todayscore; 

$teng1=$engagement1->timecreated;
$teng2=$engagement2->timecreated;
$teng3=$engagement3->tlaststroke;  

$teng1=(INT)((time()-$teng1)/60);
$teng2=(INT)((time()-$teng2)/60);
$teng3=(INT)((time()-$teng3)/60);

$lastaccess=min($teng1,$teng2,$teng3);


$termplan= $DB->get_record_sql("SELECT max(id),deadline,memo FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated' ");
	{
	$EGinputtime=date("m/d",$termplan->deadline);
	$termMission=$termplan->memo;
	}
 

// 오늘 일정 모니터링 
	 
	$timeback=time()-43200;
	  
	$checkgoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
	  
	$ratio1=$checkgoal->score;
	 
 
	$tgoal=$checkgoal->timecreated;
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
	
	$wgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
	 
	$ratio2=$wgoal->score;
	$wtimestart1=$timecreated-86400*($nday+1);
	$wtimestart2=$timecreated-86400*($nday+8);  
	 
	if($role==='student')$tabtitle=$wgoal->text;
	else $tabtitle=$username->lastname.'('.$wgoal->text.')';

	$lastwgoal= $DB->get_record_sql("SELECT id,planscore FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated < '$wtimestart1' AND timecreated > '$wtimestart2' ORDER BY id DESC LIMIT 1");
	$lastWeekPlanScore=$lastwgoal->planscore;

	if($usrdata!=='신규')include_once("intervention.php");

 
	$schedule=$DB->get_record_sql("SELECT id,editnew, lastday, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid' AND pinned='1' ORDER BY id DESC LIMIT 1 ");
 	if($nday==1){$tstart=$schedule->start1; $hours=$schedule->duration1;} 
	if($nday==2){$tstart=$schedule->start2; $hours=$schedule->duration2;} 
	if($nday==3){$tstart=$schedule->start3; $hours=$schedule->duration3;} 
	if($nday==4){$tstart=$schedule->start4; $hours=$schedule->duration4;} 
	if($nday==5){$tstart=$schedule->start5; $hours=$schedule->duration5;} 
	if($nday==6){$tstart=$schedule->start6; $hours=$schedule->duration6;} 
	if($nday==7){$tstart=$schedule->start7; $hours=$schedule->duration7;} 
	$lastday=$schedule->lastday;
 
	$id=$finish->id;
	$mark=$finish->mark;
	if($tstart!=NULL && $hours!=NULL)
		{
		$mid=7;
		$tremain=(INT)((($engagement3->timecreated+$hours*3600)-time())/60);
		$context='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
		$url='id='.$USER->id.'&tb=604800';
		if($tremain>0)$tleft=$hours.'시간 중 '.$tremain.'분 남았습니다. '; 
		else $tleft=$hours.'시간 중 '.(-$tremain).'분 보충수업 진행하였습니다. '; //$tleft='활동계획이 없습니다.';  	
		}
	else	{
		$tleft='오늘은 쉬는 날입니다.'; 
		$timesettingtext='오늘은 쉬는 날입니다.'; 
		}
 



$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468762.png" height=110>';
if($indic->appraise<20)$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469906.png" height=110>';
elseif($indic->appraise<40)$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468940.png" height=110>';
elseif($indic->appraise<60)$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469042.png" height=110>';
elseif($indic->appraise<80)$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469126.png" height=110>';
elseif($indic->appraise<100)$stability='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469222.png" height=110>';

if($mbtilog->mbti==NULL)$mbtiimg='entp';
$mbtiimg='<img loading="lazy" src="https://mathking.kr/Contents/IMAGES/'.$mbtilog->mbti.'icon.png" height=110>';
if($mbtilog->id==NULL)$mbtiimg='<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/cjn1668044829.png" width=80>';

$lastScore='<img loading="lazy" style="max-width:75%;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png">';
if($ntodo==0)$lastScore='<img loading="lazy" style="max-width:75%;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637055855.png">';
elseif($ntodo==7 || $ntodo==8)$lastScore='<img loading="lazy" style="max-width:75%;" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/dosomething.gif">';
 
echo '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script> 

<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
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

 
 
//if($role!=='student')
	{

	$lastdetection=$examPlan;
	if($role!=='student')
		{
		if($lastaccess<60)$lastdetection.='('.$lastaccess.'m)'; 
		else $lastdetection.='('.round($lastaccess/60,0).'h)'; 
		//$lastdetection.='('.$lastaccess.'분전) <button   type="button"   id="'.$alert_id.'" accesskey="k"  >요청</button>';  
		//$lastdetection.='('.$lastaccess.'분전) <button   type="button"   id="flowfeedback" accesskey="k"  >요청</button>';   
		include("../teachers/shortcuts.php");
		}
$predict=$DB->get_record_sql("SELECT * FROM mdl_abessi_forecast where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
 
$progresstext2='성공확률 예측하기 !(%) ___ '.round(($tcomplete0-$timecreated)/3600,1).'시간 남았습니다.  ('.round(($timecreated-$tgoal)/$hours/3600*100,0).'% 지점)';
	echo ' <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script>
function PredictResult()
	{
 				var Userid=\''.$studentid.'\';
				 
				var Progresstext2=\''.$progresstext2.'\'; 
				 Swal.fire({
				  position: "bottom-end",
	 	 		  backdrop:false,
				  width: 600,
				  height:100,				 
				  text: Progresstext2,
				  icon: \'range\',
				  input: \'range\',
				  confirmButtonText : "입력완료", 
				  showCancelButton:"취소", 
				  inputAttributes: {
				    min: 0,
  				    max: 100,
 				    step: 5
 				 },
 				 inputValue: 50,
				}).then((result) => {
				if (result.isConfirmed) {
					if(result.value>70)
						{
				    		Swal.fire( \'입력되었습니다.\', );
						
						$.ajax({
						url:"check_today.php",
						type: "POST",
						dataType:"json",
				 		data : {
						"eventid":\'40\',
						"userid":Userid,
						"inputvalue":result.value,
					  	       },
					    	    })
						}
					else
						{
						
						$.ajax({
						url:"check_today.php",
						type: "POST",
						dataType:"json",
				 		data : {
						"eventid":\'40\',
						"userid":Userid,
						"inputvalue":result.value,
					  	       },
					    	    })
						 document.getElementById("'.$alert_id.'").click(); 
						}
					}
				  })
	}
  

function CheckStep(Eventid,Userid,Checkvalue)
	{
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	swal({title: \'적용되었습니다.\',});	
 	$.ajax({
	url:"check.php",
	type: "POST",
	dataType:"json",
	data : {
	 "eventid":Eventid,
	"userid":Userid,       
	"checkimsi":checkimsi,
	},
	})
	location.reload();
	 		 				 
	}
function Checkstatus(Eventid,Userid, Inputid, Checkvalue){
	alert("해당 일정을 완료처리하였습니다.");
		    var checkimsi = 0;
		    if(Checkvalue==true){
		        checkimsi = 1;
		    }
		   $.ajax({
		        url: "create_course.php",
  		      type: "POST",
		        dataType: "json",
		        data : {"userid":Userid,       
		                "inputid":Inputid,
		                "checkimsi":checkimsi,
		                 "eventid":Eventid,
		               },
		        success: function (data){  
		        }
		    });

		}


 		var SweetAlert2Demo = function() {

		//== Demos  
		var initDemos = function() {
 
			$(\'#alert_updateuserinfo\').click(function(e){
				swal({
					text: \'내 정보 페이지로 이동합니다.\',buttons: false,
				})		 
			});
			$(\'#alert_search\').click(function(e){
				swal({
					text: \'내 수학노트를 검색하고 있습니다..\',buttons: false,
				})		 
			});
			$(\'#alert_updatemission\').click(function(e){
				swal({
					text: \'미션이 설정되었습니다.\',buttons: false,
				})
					 
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'3\',
					 
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			});
 
$(\'#alert_updatemissionschedule\').click(function(e){
				swal({
					text: \'미션 데드라인이 설정되었습니다.\',buttons: false,
				})
					 
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'4\',
					 
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			});
$(\'#alert_updategoal\').click(function(e){
				swal({
					text: \'목표가 설정되었습니다.\',buttons: false,
				})
					 
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';
					var Goaltype=$(\'#basic1\').val();
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'5\',
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"inputtext":Goaltype,	
					},
					success:function(data){
					
					 }
					 })

			
			});
$(\'#alert_weeklyReflection\').click(function(e){
				swal({
					title: \'주간성찰이 입력되었습니다.\',buttons: false,
				})
					 
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'6\',
					 
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			}); 
$(\'#alert_addtime\').click(function(e){
					var Userid=\''.$studentid.'\';
					 
					 
				swal({
					title: \'당일 즉석보강 또는 부분휴강을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "시간입력 (분)",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {		
						confirm: {
							className : \'btn btn-success\'
						}
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("","이번 주 보충학습 시간이 " + Inputtext+"분 추가 되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'60\',
					"inputvalue":Inputtext,	
					"userid":Userid,					 
					},
					success:function(data){
					 }
					 })
				}
				);
			}); 
		 
			$(\'#alert_nextpage\').click(function(e) {
				var Userid= \''.$studentid.'\'; 
				var Username=\''.$studentname.'\';
				var Fbtype;
				var Fbtext;
				var Contextid;
				var Fburl;
			 	swal({text: \'활동 데이터를 분석 중입니다. \',buttons: false,})
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
						swal({
								title: Username+\'의 \' + Fbtype ,
								text: Fbtext,
								type: \'warning\',
								buttons:{
									confirm: {
										text : \'확인하기\',
										className : \'btn btn-primary\'
									},
								}
							}).then((willDelete) => {
								if (willDelete) {
								 window.location.href =Contextid+"?"+Fburl;	 					 
								} 
							});
						}
            	   		  	      });
				}); 	
 		 
  		};
		return {
			//== Init
			init: function() {
				initDemos();
				},
			};
		}();

	//== Class Initialization
	jQuery(document).ready(function() 
		{
		SweetAlert2Demo.init();
		});
	</script> 
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';
	}
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

//$mark5='<span class="" style="font-size: 10pt; color: rgb(255,255, 255);">('.$ratio2.'%)</span>';
if($ratio1-$ratio2>=20)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji1.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji2.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji3.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji4.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=0)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji5.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji6.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji7.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji8.png" width=60 ></a></span>';
else $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji9.png" width=60 ></a></span>';
if($ratio1<0.001) $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji0.gif" width=60 ></a></span>';
if($ratio2==0  && $Qnum22==0) $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

$todayscore=$engagement3->todayscore;
 
 
	 // 풀이 속도 관리
 
	if($tspeed<10)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed6.png';
	elseif($tspeed<20)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed5.png';
	elseif($tspeed<30)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed4.png';
	elseif($tspeed<60)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed3.png';
	elseif($tspeed<90)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed2.png';
	elseif($tspeed<120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed1.png';
	elseif($tspeed>120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
	if($tspeed<0)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
 
$recentmessage='';
 
$imgstatus=$lastdetection.' '.$recentmessage.'&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1" accesskey="w"><img loading="lazy" src='.$v_quiz.' width=50></a>&nbsp;&nbsp;&nbsp;&nbsp;'.$mark4.'&nbsp;<img loading="lazy" src='.$imgtoday.' width=35><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/Preloader2.gif" width=60 ><img loading="lazy" src='.$imgtoday2.' width=35>&nbsp;&nbsp;'.$mark5. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$timeplan = $DB->get_record_sql("SELECT max(id),memo8,memo9 FROM mdl_abessi_schedule WHERE userid='$studentid' AND pinned=1 ");
 
$messageicon1='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-bottom:0px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=50></a>';



//$messageicon1='<span onClick="dragChatbox(\''.$studentid.'\')"  ><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1657195665.png height=30></span>';
if($role!=='student')$messageicon2='<span onClick="showMoment(\''.$studentid.'\')" accesskey="m"><img loading="lazy" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1636252058.png height=30></span>';

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

$thisyear=date("Y",time());
$ngrade=$thisyear-$birthyear-6;
if($ngrade<=6 )$ngrade=$ngrade; 		 
elseif($ngrade<=9 )$ngrade=$ngrade-6; 
elseif($ngrade<=13 )$ngrade=$ngrade-9; 
if($institute==NULL || $birthyear==NULL) $schinfo='정보 미입력';
else $schinfo=$institute.' '.$ngrade;
	
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

$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart1' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
$weeklyGoalText='<span style="color:white;font-size=15;"><img loading="lazy" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612786844001.png" width=40> 이번 주 목표가 설정되지 않았습니다. </span>';
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

	<div class="wrapper  sidebar_minimize">
		<div class="main-header" style="background-color:#177dff;color:white;">
			<!-- Logo Header -->
			<div align="center" class="logo-header" style="white-space:nowrap;">
			'.$userinfo.'</div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid ">
					 <table width=100%><tr><td width=2%></td><td style=" font-size:16;color:yellow;overflow: hidden;text-overflow: ellipsis;" width=85%><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$examplanid.'"target="_blank"><img loading="lazy" src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=25> </a>'.$goaldisplay.'<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'"><img loading="lazy" style="margin-bottom:5px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/keyboard.png width=35></a></td>
					<td style=" font-size:16;color:white;white-space: nowrap;" align=right>'.$imgstatus.' </td><td align=right>'.$messageicon2.'</td><td align=right></td></tr></table><span align=right>	'.$messageicon1.'	</span>			 
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
				<div class="sidebar-content "><br> 
					<div class="user">
						<div><a href="https://mathking.kr/moodle/local/augmented_teacher/students/mbti_types.php?studentid='.$studentid.'">'.$mbtiimg.'</a></div>
						<div class="info">
							<a  style="margin-top:10px;" data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
								<h6>'.$schinfo.'</h6>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
<li><a href="https://mathking.kr/moodle/user/profile.php?id='.$studentid.'" target="_blank">기본정보</a></li>
<li><a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'" target="_blank">수정하기</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=12"  accesskey="n">출결정보</a></li>
<li><a href="https://mathking.kr/moodle/report/log/user.php?mode=today&course=1&&id='.$studentid.'" target="_blank">활동로그</a></li>
								</ul>
							</div>
						</div>
					
					</div>
					<ul class="nav">
						<li class="nav-item active">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$studentid.'" accesskey="s">
								<i class="flaticon-desk"></i>
								<p>내 공부방</p>
								 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/connectmemories.php?domain=1&studentid='.$studentid.'&contentstype=2" target="_blank">
							<i class="flaticon-search-1"></i>
							<p>개념탐색</p>							
						 	</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/thinkAloud.php?id='.$studentid.'&tb=604800">
								<i class="flaticon-users"></i>
								<p>발표 게시판</p>
							
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/peer_whiteboards.php?id='.$studentid.'&tb=86400">
								<i class="flaticon-users"></i>
								<p>응원합니다 !</p>
								<span class="badge badge-count"></span>
							</a>
						</li>
						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h3 class="text-section">활동관리</h3>
						</li>

			 

						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id='.$studentid.'&mode=CA"  accesskey="g">
									<i class="flaticon-chat-8"></i>
									<p>목표설정</p>									
									</a>	
						</li>

						<li class="nav-item">
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"  accesskey="o">
									<i class="flaticon-star"></i>
									<p>오늘활동</p>
									 <span class="badge badge-count badge-success">11</span>
									</a>	
						</li>	
						<li class="nav-item">
					        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=12"  accesskey="."><i class="flaticon-calendar"></i><p>시간표</p></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&nweek=12&eid='.$timeplan->id.'"  accesskey="/"></a>					
						</li>	
						<li class="nav-item">
							<a href="timeline.php?id='.$studentid.'&tb=604800"  accesskey="l">
								<i class="flaticon-analytics"></i>
								<p>타임라인</p>
								<span class="badge badge-count badge-success">부모님용</span>
							</a>
						</li>
						<!-- 
						<li class="nav-item">
									<a href="mentors.html">
									<i class="flaticon-chat-8"></i>
									<p>미션멘토</p>
									<span class="badge badge-count">준비중</span>
									</a>	
						</li> -->
						'.$teachermenu.' 
					</li> </ul>';

 
 
$flowlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_flowlog where userid='$studentid'   ORDER BY id DESC LIMIT 1"); 
$totalflow=$flowlog->flow1+$flowlog->flow2+$flowlog->flow3+$flowlog->flow4+$flowlog->flow5+$flowlog->flow6+$flowlog->flow7+$flowlog->flow8;
if($totalflow==0 || $totalflow==NULL)$totalflow=1;
if($usersex==='여')$cjnimg='<img loading="lazy" style="max-width:68%;" src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/woman/'.$totalflow.'.png">';
else $cjnimg='<img loading="lazy" style="max-width:68%;" src="https://mathking.kr/Contents/IMAGES/cjn2ndbrain/man/'.$totalflow.'.png">';
/*

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
	 
//include("flowexpressions.php");
if($ncur==NULL)$ncur=1;

$DB->execute("UPDATE {abessi_mcupdate} SET ncur='$ncur' WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");   
 
$itmid=$DB->get_record_sql("SELECT * FROM mdl_abessi_mcpreset where userid LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");

$colstr1='c'.($ncur-1);
$colstr2='c'.$ncur;
$nmc1=$itmid->$colstr1;
$nmc2=$itmid->$colstr2;

$lasttrystr='todoitem'.$nmc1;
$nexttrystr='todoitem'.$nmc2;
 
//$lastcfeedback1='';//$$lasttrystr;
//$fbtime1=date("m/d",$current->timemodified);
$lastcfeedback2=$$nexttrystr;
//$fbtime2=date("m/d",$current->timemodified+604800*2);

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

if($fbtype==='목표')$style1='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;border-left: 3px solid #88c2fc;"';
elseif($fbtype==='순서')$style2='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='기억')$style3='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='몰입')$style4='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='발상')$style5='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='해석')$style6='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='숙달')$style7='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;"';
elseif($fbtype==='효율')$style8='style=" border-bottom: 6px solid #f74336;border-top: 3px solid #88c2fc;border-right: 3px solid #88c2fc;"';
 */
echo '
				</div>
			</div>
		</div> 
		<!-- End Sidebar --> 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row" style="background-color:white">
					 <div id="navbar">
					 <table width=100%><tr><td  width=2%></td>					 
							<td valign=top>
										<table  valign=top width=100%><tr valign=top><td  style=" border-bottom:3px solid #88c2fc;border-top: 3px solid #88c2fc;border-left: 3px solid #88c2fc;" valign=top align=center  ><button id="alert_nextpage" style="border:none;background: none;" onclick="" accesskey="u">'.$lastScore.'</button></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=목표"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;" src="https://mathking.kr/Contents/IMAGES/thinking%20hats/red.png" ><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext1.'</td></tr></table></span></div></a></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=순서"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;" src="https://mathking.kr/Contents/IMAGES/thinking%20hats/orange.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext2.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=기억"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/yellow.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext3.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=몰입"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/white.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext4.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=발상"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/green.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext5.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=해석"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/blue.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext6.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=숙달"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/black.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext7.'</td></tr></table></span></div></td>
										<td valign=top style=" border: 3px solid #88c2fc; " width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type=효율"><div class="tooltip7"><img loading="lazy" style="max-width: 100%;"  src="https://mathking.kr/Contents/IMAGES/thinking%20hats/bluem.png" ></a><span class="tooltiptext7"><table style="" align=center><tr><td>'.$flowtext8.'</td></tr></table></span></div></td>
										<td style=" border-bottom: 3px solid #88c2fc;border-top: 3px solid #88c2fc;border-right: 3px solid #88c2fc;" valign=top align=center   width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$studentid.'" >'.$cjnimg.'</a></td>
										</tr>
										<tr><td align=center>도움필요? 클릭 !</td><td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/목표%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/순서%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/기억%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/몰입%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/발상%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/해석%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/숙달%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center style="font-size:12px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/twinery/효율%20메타인지.html"target="_blank"><img loading="lazy" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/detail.png width=20></a></td>
										<td align=center>MC LEVEL UP!</td></tr></table></td><td  width=2%></td></tr></table>
										 </div><table align=center width=95%><tr><td><hr style="border: solid 0.5px lightblue;"> </td></tr></table> </div>';

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

?>
