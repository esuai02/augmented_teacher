<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$cid = $_GET["cid"]; 
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
if($USER->id==NULL)header('Location: https://mathking.kr/moodle/my/');
if(strpos($url, 'php?id')!= false)$studentid=required_param('id', PARAM_INT); 
else $studentid=$USER->id;
$timecreated=time();

$username= $DB->get_record_sql("SELECT hideinput,lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$hideinput=$username->hideinput;
$symbol=substr($username->firstname,0, 3); 
$myteacher=$DB->get_record_sql("SELECT max(id) AS id,userid FROM mdl_user_info_data where fieldid=64 AND data LIKE '%$symbol%' ");
$teacherid=$myteacher->userid;
$tname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$teachername=$tname->firstname.$tname->lastname;
 
$halfdayago=time()-43200;
$aweekago=time()-604800;
$readtime= $DB->get_record_sql("SELECT max(id) AS id FROM mdl_abessi_indicators WHERE userid='$studentid' AND timecreated>'$halfdayago' ");
if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,timemodified,timecreated) VALUES('$studentid','$timecreated','$timecreated')");

$alert_id='alert_ask';
if(strpos($url, 'index.php')!= false){$ailink='ai_index.html';$alert_id='alert_index';}
if(strpos($url, 'fullengagement.php')!= false){$ailink='ai_fullengagement.html';$alert_id='alert_fullengagement';}
if(strpos($url, 'schedule.php')!= false){$ailink='ai_schedule.html';$alert_id='alert_schedule';}
if(strpos($url, 'today.php')!= false){$ailink='ai_today.html';$alert_id='alert_today';}
if(strpos($url, 'missionhome.php')!= false){$ailink='ai_missionhome.html';$alert_id='alert_missionhome';}
if(strpos($url, 'selectmission.php')!= false){$ailink='ai_selecthome.html';$alert_id='alert_selectmission';}
if(strpos($url, 'editschedule.php')!= false){$ailink='ai_editschedule.html';$alert_id='alert_editschedule';}
if(strpos($url, 'edittoday.php')!= false){$ailink='ai_edittoday.html';$alert_id='alert_edittoday';}
if(strpos($url, 'cognitivism.php')!= false){$ailink='ai_cognitivism.html';$alert_id='alert_cognitivism';}
$examplan=$DB->get_record_sql("SELECT max(id), wboardid FROM mdl_abessi_messages where userid='$studentid' AND status='examplan' ");
$examplanid=$examplan->wboardid;
 
$indic= $DB->get_record_sql("SELECT max(id),weekquizave,ntodo FROM mdl_abessi_indicators WHERE userid='$studentid'  ");
$weeklyquizave=$indic->weekquizave;
$ntodo=$indic->ntodo;
	  


//include_once("intervention.php");

$curl1=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$curl2=strstr($url, '?');  //before
$curl2=str_replace("?","",$curl2);

// 강제지도 모드
$timediff=time()-300;
$exist=$DB->get_record_sql("SELECT max(id),url  FROM mdl_abessi_feedbacklog WHERE userid='$studentid' AND forced='1' AND timecreated >'$timediff'    ");
if($exist->id!=NULL && $curl2!==$exist->url)
	{
	$redirect=$exist->url;
	header('Location:https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$redirect);
	}
// 시험목표
 

$termplan= $DB->get_record_sql("SELECT max(id),deadline,memo FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated' ");
	{
	$EGinputtime=date("m/d",$termplan->deadline);
	$termMission=$termplan->memo;
	}

if($termMission==NULL) // 추후삭제
	{
	$examGoal= $DB->get_record_sql("SELECT max(id),deadline,text FROM mdl_abessi_today WHERE userid='$studentid' AND deadline>'$timecreated' AND type LIKE '시험목표' ");
	$EGinputtime=date("m/d", $examGoal->deadline);
	$termMission=$examGoal->text;
	}

if($termMission==NULL)  $termMission ='분기목표를 설정해 주세요';

// 오늘 일정 모니터링 
	 
	$timeback=time()-43200;
	$prevgoal= $DB->get_record_sql("SELECT  max(id) FROM  mdl_abessi_today WHERE userid='$studentid' AND (rtext1 NOT LIKE 'NULL' OR ntext1 NOT LIKE 'NULL'  )AND timecreated < '$timeback' AND (type LIKE '오늘목표' OR type LIKE '검사요청' )");
	 
	$checkgoal= $DB->get_record_sql("SELECT max(id),text, score,comment,amountr,amountn,amountp,rtext1, ntext1, ptext1, timecreated FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ");
	//$checkWeekgoal= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$timeback' AND type LIKE '주간목표'  ORDER BY id DESC LIMIT 1 ");
	$ratio1=$checkgoal->score;
	$nextplan=$checkgoal->comment;

	$amountr=$checkgoal->amountr;
	$amountn=$checkgoal->amountn;
	$amountp=$checkgoal->amountp;
	$rtext1=$checkgoal->rtext1;
 
	$ntext1=$checkgoal->ntext1;
 
	$ptext1=$checkgoal->ptext1;
 


	$tgoal=$checkgoal->timecreated;
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
 
	$schedule=$DB->get_record_sql("SELECT max(id),editnew, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid'   ");
	//$schedule=$DB->get_record_sql("SELECT max(id),start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid'   ");
	if($nday==1){$tstart=$schedule->start1; $hours=$schedule->duration1;} 
	if($nday==2){$tstart=$schedule->start2; $hours=$schedule->duration2;} 
	if($nday==3){$tstart=$schedule->start3; $hours=$schedule->duration3;} 
	if($nday==4){$tstart=$schedule->start4; $hours=$schedule->duration4;} 
	if($nday==5){$tstart=$schedule->start5; $hours=$schedule->duration5;} 
	if($nday==6){$tstart=$schedule->start6; $hours=$schedule->duration6;} 
	if($nday==7){$tstart=$schedule->start7; $hours=$schedule->duration7;} 
	if($amountr+$amountn+$amountp >= $hours*60) $timesettingtext=' ※ 공부시간이 적절하게 계획되었습니다';  
	else  $timesettingtext=' <span style="color:red;">※ 계획된 공부시간이 예정된 오늘의 공부시간보다 적습니다. <span>';

	$id=$finish->id;
	$mark=$finish->mark;
	if($tstart!=NULL && $hours!=NULL)
		{
		$mid=7;
		$tremain=(INT)((($tgoal+$hours*3600)-time())/60);
		$context='https://mathking.kr/moodle/local/augmented_teacher/students/today.php';
		$url='id='.$USER->id.'&tb=604800';
		if($tremain>0)$tleft=$hours.'시간 중 '.$tremain.'분 남았습니다. '; 
		else $tleft='활동계획이 없습니다.';  	
		}
	else	{
		$tleft='오늘은 쉬는 날입니다.'; 
		$timesettingtext='오늘은 쉬는 날입니다.'; 
		}
 

$rtime0=$tgoal+$amountr*60;
$ntime0=$tgoal+$amountr*60+$amountn*60;
$ptime0=$tgoal+$amountr*60+$amountn*60+$amountp*60;

$rtime=date("h:i A", $rtime0);
$ntime=date("h:i A", $ntime0);
$ptime=date("h:i A", $ptime0);

$tlastinput=$checkgoal->timecreated;
$tcomplete0=$tgoal+$hours*3600;
$tcomplete=date("h:i A", $tcomplete0);
 
			
if(!empty($checkgoal->step1)){if($checkgoal->complete1==1)$checkstatus1='checked'; $steptext.='<br><input type="checkbox" name="checkAccount"  '.$checkstatus1.'  onClick="CheckStep(91,\''.$studentid.'\', this.checked)"/>&nbsp; 첫 번째 휴식 : '.$checkgoal->step1.'<br>';}
if(!empty($checkgoal->step2)){if($checkgoal->complete2==1)$checkstatus2='checked';$steptext.='<input type="checkbox" name="checkAccount"  '.$checkstatus2.'  onClick="CheckStep(92,\''.$studentid.'\', this.checked)"/>&nbsp; 두 번째 휴식 : '.$checkgoal->step2.'<br>';}
if(!empty($checkgoal->step3)){if($checkgoal->complete3==1)$checkstatus3='checked';$steptext.='<input type="checkbox" name="checkAccount"  '.$checkstatus3.'  onClick="CheckStep(93,\''.$studentid.'\', this.checked)"/>&nbsp; 세 번째 휴식 : '.$checkgoal->step3.'<br>';}
if(!empty($checkgoal->step4)){if($checkgoal->complete4==1)$checkstatus4='checked';$steptext.='<input type="checkbox" name="checkAccount"  '.$checkstatus4.'  onClick="CheckStep(94,\''.$studentid.'\', this.checked)"/>&nbsp; 네 번째 휴식 : '.$checkgoal->step4.'<br>';}
if(!empty($checkgoal->step5)){if($checkgoal->complete5==1)$checkstatus5='checked';$steptext.='<input type="checkbox" name="checkAccount"  '.$checkstatus5.'  onClick="CheckStep(95,\''.$studentid.'\', this.checked)"/>&nbsp; 다섯 번째 번째 휴식 : '.$checkgoal->step5.'<hr>';}
if($steptext!==NULL)$steptext.='<br><span style="font-size:16;color:red;text-align:center;">단계를 완료하거나 약속된 시간이 되면 선생님에게 검사를 받아주세요 !</span>';
// 오늘일정 끝
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$username->firstname.$username->lastname;
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$studentid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;
require_login();
 
$wtimestart=time()-864000;
$wgoal= $DB->get_record_sql("SELECT max(id),score  FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ");
$ratio2=$wgoal->score;
$wtimestart1=$timecreated-86400*($nday+1);
$wtimestart2=$timecreated-86400*($nday+8);  
 
$lastwgoal= $DB->get_record_sql("SELECT max(id),planscore FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated < '$wtimestart1' AND timecreated > '$wtimestart2' ");
$lastWeekPlanScore=$lastwgoal->planscore;

$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468762.png" height=110>';
if($lastWeekPlanScore==1)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469906.png" height=110>';
elseif($lastWeekPlanScore==2)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468940.png" height=110>';
elseif($lastWeekPlanScore==3)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469042.png" height=110>';
elseif($lastWeekPlanScore==4)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469126.png" height=110>';
elseif($lastWeekPlanScore==5)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469222.png" height=110>';

/*
if($lastWeekPlanScore==1)$stability='<img src="https://mathking.kr/Contents/IMAGES/Aqualine/seed.png" height=110>';
elseif($lastWeekPlanScore==2)$stability='<img src="https://mathking.kr/Contents/IMAGES/Aqualine/seedling.png" height=110>';
elseif($lastWeekPlanScore==3)$stability='<img src="https://mathking.kr/Contents/IMAGES/Aqualine/sapling.png" height=110>';
elseif($lastWeekPlanScore==4)$stability='<img src="https://mathking.kr/Contents/IMAGES/Aqualine/tree.png" height=110>';
elseif($lastWeekPlanScore==5)$stability='<img src="https://mathking.kr/Contents/IMAGES/Aqualine/ultimate.png" height=110>';
*/

$lastScore='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png" height=110>';
if($ntodo==0)$lastScore='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637055855.png" height=110>';
elseif($ntodo==7 || $ntodo==8)$lastScore='<img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/dosomething.gif" height=110>';

if($weeklyquizave>90)$lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve100.png" height=110>';
elseif($weeklyquizave>70)$lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve80.png" height=110>';
elseif($weeklyquizave>50)$lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve60.png" height=110>';
elseif($weeklyquizave>30)$lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve40.png" height=110>';
elseif($weeklyquizave>10)$lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve20.png" height=110>';
else  $lastPlanScore='<img src="https://mathking.kr/Contents/IMAGES/achieve0.png" height=110>';
//$img=$DB->get_record_sql("SELECT data AS name FROM mdl_user_info_data where userid='$USER->id' and fieldid='59' ");
//$userpic=$img->name;
 
 //<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 //<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
							text : \'확인하기\',
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
						window.location.href ="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id="+data.wboardid;
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
							text : \'확인하기\',
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
						window.location.href ="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id="+data.userid;
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
							text : \'시작하기\',
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
						window.location.href ="https://mathking.kr/moodle/mod/chat/gui_ajax/index.php?id="+data.chatid+"&theme=bubble";
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
							text : \'바로가기\',
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
	$engagement1 = $DB->get_record_sql("SELECT max(id),url,timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  AND eventid=17   ");  // missionlog
	$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
	$engagement3 = $DB->get_record_sql("SELECT max(id),todayscore,speed, tlaststroke,timecreated FROM  mdl_abessi_indicators WHERE userid='$studentid'   "); 
	 
	//$ratio1=$engagement3->todayscore; 

	$teng1=$engagement1->timecreated;
	$teng2=$engagement2->timecreated;
	$teng3=$engagement3->tlaststroke;  

	$teng1=(INT)((time()-$teng1)/60);
	$teng2=(INT)((time()-$teng2)/60);
	$teng3=(INT)((time()-$teng3)/60);

	$lastaccess=min($teng1,$teng2,$teng3);

/*
	$stayfocused1=$DB->get_record_sql("SELECT max(id),context,currenturl,timecreated FROM mdl_abessi_stayfocused where userid='$studentid' AND status=1 ");
	$lastaction1= ((time()-$stayfocused1->timecreated)/60);
	$url1=$stayfocused1->context.'?'.$stayfocused1->currenturl;
	$stayfocused2=$DB->get_record_sql("SELECT max(id),context,currenturl,timecreated  FROM mdl_abessi_stayfocused where userid='$studentid' AND status=2 ");
	$lastaction2=((time()-$stayfocused2->timecreated)/60);
	$url2=$stayfocused2->context.'?'.$stayfocused2->currenturl;
	$stayfocused3=$DB->get_record_sql("SELECT max(id),context,currenturl,timecreated  FROM mdl_abessi_stayfocused where userid='$studentid' AND status=3  ");
	$lastaction3=((time()-$stayfocused3->timecreated)/60);
	$url3=$stayfocused3->context.'?'.$stayfocused3->currenturl;

	if($lastaction1<=$lastaction2 && $lastaction1<=$lastaction3)$currentpage=$url1;
	if($lastaction2<=$lastaction1 && $lastaction2<=$lastaction3)$currentpage=$url2;
	if($lastaction3<=$lastaction1 && $lastaction3<=$lastaction2)$currentpage=$url3;
	
	echo '<a href='.$currentpage.' accesskey="q"></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?'.$engagement1->url.'" accesskey="r"></a>';
*/
	$lastdetection=$examPlan;
	if($role!=='student')
		{
		$lastdetection.=$lastaccess.'분전&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button   type="button"   id="'.$alert_id.'" accesskey="k"  >요청</button>';  
		include("../teachers/shortcuts.php");
		}
 
	echo ' <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script>
	function deletequiz(Attemptid)
		{
		swal({
					title: \'시도된 퀴즈를 삭제하시겠습니까 ?\',
					text: "원하지 않으시면 취소 버튼을 눌러주세요",
					type: \'warning\',
					buttons:{
						confirm: {
							text : \'확인\',
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
						$.ajax({
						url:"check.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"eventid":\'300\',
						"attemptid":Attemptid,
					 		},
						 });
					setTimeout(function() {location.reload(); },100);
					} else {
					swal("취소되었습니다.", {buttons: false,timer: 500});
					}
				});	 				 
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
			$(\'#alert_planA\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "교과개념에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'교과개념\',
							"moduleid":Moduleid,	
							},
						success:function(data){
								location.reload();
								 }
					                    })
					}
				);
			});
	
			$(\'#alert_planB\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "대표유형에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'대표유형\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			});

			$(\'#alert_planC\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "유형단련에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext": Inputtext,	
							"type":\'유형단련\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			});

			$(\'#alert_planD\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "심화학습에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'심화미션\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			});
 
			$(\'#alert_planE\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "인지촉진에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'인지촉진\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			});
			$(\'#alert_planF\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "보강학습에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'보강학습\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			});
			$(\'#alert_planG\').click(function(e){
				swal({
					title: \'학습계획을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "주제단련에 대한 학습계획을 입력해 주세요",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					var Userid=\''.$studentid.'\';
					var Cid=\''.$cid.'\';
					var Moduleid=0;
					 
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					            $.ajax(
					                    {
							url:"create_course.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'1\',
							"userid":Userid,
							"cid":Cid,
							"inputtext":Inputtext,	
							"type":\'주제단련\',
							"moduleid":Moduleid,	
							},
						success:function(data){

								 }
					                    })
					}
				);
			}); 
			$(\'#alert_learnmore1\').click(function(e){
					var Userid=\''.$studentid.'\';
					var Reviewtype="retry";
					 
 					swal("복습문항이 출제되었습니다.", {buttons: false, timer: 1000, });
					$.ajax({
					url:"cognitiveReview.php",
					type: "POST",
					dataType:"json",
				 	data : {
					//"inputtext":Inputtext,	
					"userid":Userid,
					"type": Reviewtype,
					},
					success:function(data){ 
					 }
					 })
				setTimeout(function(){
				location.reload();
				},1000);  
			});
			$(\'#alert_learnmore2\').click(function(e){
					var Userid=\''.$studentid.'\';
					var Reviewtype="present";
					 
 					swal("발표문항이 출제되었습니다.", {buttons: false, timer: 1000, });
					$.ajax({
					url:"cognitiveReview.php",
					type: "POST",
					dataType:"json",
				 	data : {
					//"inputtext":Inputtext,	
					"userid":Userid,
					"type": Reviewtype,
					},
					success:function(data){
					 }
					 })
				setTimeout(function(){
				location.reload();
				},1000);  			
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
			$(\'#alert_sharecare\').click(function(e){
			var Userid=\''.$studentid.'\';
			      swal({
				title: \'관심 공유하기\',text: \'공유하고자 하는 학생의 상황과 특이점 및 개선 목표 등을 입력해주세요\',
				html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'21\',
							"userid":Userid,
							"inputtext":Inputtext,
							},
					success:function(data){
					  location.reload();
					 }
				});
			       });
			});

			$(\'#alert_sharedanger\').click(function(e){
			var Userid=\''.$studentid.'\';
			      swal({
				title: \'위험군 등록하기\',text: \'현재 상황과 특이점 및 원하는 피드백 방향을 입력해 주세요\',
				html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력",
							type: "text",
							id: "input-field",
							className: "form-control"
						},
					},
					buttons: {
						confirm: {
							className : \'btn btn-success\'
						},
						cancel: {
							visible: true,
							className: \'btn btn-danger\'
						}   			
					},
				}).then(
				function() {
					var Inputtext=$(\'#input-field\').val();
					swal("", "입력된 내용 : " + $(\'#input-field\').val(), "success");
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'22\',
							"userid":Userid,
							"inputtext":Inputtext,
							},
					success:function(data){
					  location.reload();
					 }
				});
			       });
			});
			$(\'#alert_normalcondition\').click(function(e){
			var Userid=\''.$studentid.'\';
			      swal({
				title: \'상황해제\',text: \'상황을 해제하고 일상모드로 돌아갑니다.\',
				}).then(
				function() {		
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'23\',
							"userid":Userid,
							},
					success:function(data){
					  location.reload();
					 }
				});
			       });
			});
			$(\'#alert_exception\').click(function(e){
			var Userid=\''.$studentid.'\';
			      swal({
				title: \'예외설정\',text: \'예외경우로 설정됩니다.\',
				}).then(
				function() {		
					$.ajax({
							url:"check.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'24\',
							"userid":Userid,
							},
					success:function(data){
					  location.reload();
					 }
				});
			       });
			});
			$(\'#alert_today\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="학생에게 전달할 내용을 직접 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "학생에게 전달할 내용을 직접 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			$(\'#alert_reviewA\').click(function(e){
				var amountr=\''.$amountr.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\']
				}).queue([
				  {
				    title: \'복습유형 선택\',
				    text: \'유형 : 개념복습 ,오답복습 ,선택복습\'
				  },
				  {
				    title: amountr+\'분 동안의 복습개수\',
				    text: \'복습할 주제의 개수 또는 문항의 개수를 입력\'
				  } 
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var Mtid=\''.$mtid.'\';
					var Cid=\''.$cid.'\'; 
					var studentinput=result.value[0]+"   "+result.value[1]+" | ";
					setTimeout("location.reload()", 1000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'21\',
							"userid":Userid,
							"mtid":Mtid,	
							"cid":Cid,	
							"inputvalue":studentinput,	
							},
					                    })
				    Swal.fire({
				      title: \'적용되었습니다.\',
				      html: `
				       입력내용 : 
				        <pre>${answers}</pre>
				      `,
				      confirmButtonText: \'완료\'
				    })

				  }
				})
			});   
			
			$(\'#alert_gonextA\').click(function(e){
				var amountn=\''.$amountn.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\', \'3\']
				}).queue([
				  {
				    title: \'집중유형 선택\',
				    text: \'평가준비, 서술평가, 부스터 스텝 중 하나 입력\'
				  },
				  {
				    title: amountn+\'분 동안의 테스트 개수\',
				    text: \'진행할 테스트의 종류와 개수를 입력\'
				  },				  
				  {
				    title: \'활동개선\',
				    text: \'선택한 집중유형을 개선하기 위한 방법입력\'
				  }
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var Mtid=\''.$mtid.'\';
					var Cid=\''.$cid.'\'; 
					var studentinput=result.value[0]+"  "+result.value[1]+"   "+result.value[2]+" | ";
					setTimeout("location.reload()", 1000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'31\',
							"userid":Userid,
							"mtid":Mtid,	
							"cid":Cid,	
							"inputvalue":studentinput,	
							},
					                    })
				    Swal.fire({
				      title: \'수고하셨습니다 !\',
				      html: `
				       입력내용 : 
				        <pre>${answers}</pre>
				      `,
				      confirmButtonText: \'완료\'
				    })

				  }
				})
			});  			
			$(\'#alert_gonextB\').click(function(e){
				var amountn=\''.$amountn.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\', \'3\']
				}).queue([
				  {
				    title: \'휴식시간 1\',
				    text: \'선생님의 중간점검 후 휴식을 취할 수 있습니다.\'
				  },				  
				  {
				    title: \'휴식시간 2\',
				    text: \'선생님의 중간점검 후 휴식을 취할 수 있습니다.\'
				  },
				  {
				    title: \'휴식시간 3\',
				    text: \'선생님의 중간점검 후 휴식을 취할 수 있습니다.\'
				  } 
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var Mtid=\''.$mtid.'\';
					var Cid=\''.$cid.'\'; 
					var Step1=result.value[0];
					var Step2=result.value[1];
					var Step3=result.value[2];
					 
				 
					setTimeout("location.reload()", 10000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'32\',
							"userid":Userid,
							"mtid":Mtid,	
							"cid":Cid,	
							"inputtext1":Step1,	
							"inputtext2":Step2,	
							"inputtext3":Step3,	
							 
							 
							},
					                    })
				    Swal.fire({
				      title: \'입력완료\',
				    
				      html: `최대 휴식시간은 10분입니다. <hr>휴식은 공부시간에 반영되지 않습니다..`,
				      confirmButtonText: \'확인\'
				    })

				  }
				})
			});  

			$(\'#alert_index\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			  
			
			$(\'#alert_cognitivism\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		
					var Attemptid=\''.$attemptid.'\';	
					var Quiztitle=\''.$quiztitle.'\';	
					var text1="퀴즈 결과 분석이나 전달사항을 직접 입력하겠습니다.";
					var text2="퀴즈 응시 속도가 좀 빠른 거 같습니다. 오답노트 및 약점 분석 등의 시간을 늘려 보세요";
					var text3="응시준비가 부족해 보입니다. 오답복습, 개념복습, 보강학습 등 준비 비중을 늘려 보세요";
					var text4="제한시간 안에 문제를 푸는 것에 어려움이 있어 보입니다. 부스터 활동 비중을 높여 보세요";
					var text5="같은 종류의 테스트를 너무 많이 보고 있습니다. 응시한 문항들에 복습을 진행해 주세요";
					var text6="평점이 위태롭습니다. 풀이과정과 정답도출 과정에 조금 더 침착함을 발휘해 주세요";
					var text7="잠깐 활동을 멈추고 오답원인을 함께 고민해 봅시다. 선생님에게 와주세요 !";
					
			swal("퀴즈결과 분석하기",  "퀴즈결과 분석 후 학생에게 전달할 내용을 선택해 주세요.",{
			  buttons: {
			    catch1: {
			      text: text1,
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			     text: text2,
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			       text: text3,
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			        text: text4,
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			        text: text5,
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			       text: text6,
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: text7,
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					"attemptid":Attemptid,
					"quiztitle":Quiztitle,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			 			

			$(\'#alert_fullengagement\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			$(\'#alert_reviewA\').click(function(e){
				var amountr=\''.$amountr.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\', \'3\']
				}).queue([
				  {
				    title: \'복습유형 선택\',
				    text: \'유형 : 개념복습 ,오답복습 ,선택복습\'
				  },
				  {
				    title: amountr+\'분 동안의 복습개수\',
				    text: \'복습할 주제의 개수 또는 문항의 개수를 입력\'
				  },				  
				  {
				    title: \'복습방법\',
				    text: \'복습방법 상의 강조사항을 입력\'
				  }
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var Mtid=\''.$mtid.'\';
					var Cid=\''.$cid.'\'; 
					var studentinput=result.value[0]+"   "+result.value[1]+"   "+result.value[2]+" | ";
					setTimeout("location.reload()", 1000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'21\',
							"userid":Userid,
							"mtid":Mtid,	
							"cid":Cid,	
							"inputvalue":studentinput,	
							},
					                    })
				    Swal.fire({
				      title: \'적용되었습니다.\',
				      html: `
				       입력내용 : 
				        <pre>${answers}</pre>
				      `,
				      confirmButtonText: \'완료\'
				    })

				  }
				})
			});   
			
			 		
 
			$(\'#alert_schedule\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			 
			$(\'#alert_missionhome\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			 
			$(\'#alert_selectmission\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			 
			
			$(\'#alert_gonextA\').click(function(e){
				var amountn=\''.$amountn.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\']
				}).queue([
				  {
				    title: \'집중유형 선택\',
				    text: \'평가준비, 서술평가, 부스터스텝 중 선택\'
				  },
			 				  
				  {
				    title: \'개선방법\',
				    text: \'선택한 노트유형 작성 시 주의사항을 입력해 주세요.\'
				  }
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var Mtid=\''.$mtid.'\';
					var Cid=\''.$cid.'\'; 
					var studentinput=result.value[0]+"  "+result.value[1]+"   ";
					setTimeout("location.reload()", 1000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'31\',
							"userid":Userid,
							"mtid":Mtid,	
							"cid":Cid,	
							"inputvalue":studentinput,	
							},
					                    })
				    Swal.fire({
				      title: \'수고하셨습니다 !\',
				      html: `
				       입력내용 : 
				        <pre>${answers}</pre>
				      `,
				      confirmButtonText: \'완료\'
				    })

				  }
				})
			});  			
		 
			$(\'#alert_editschedule\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="공부시간이 부족합니다. 이번 주 보충 공부계획을 입력해 주세요";
					var text3="평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요";
					var text4="시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다.";
					var text5="계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요";
					var text6="제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요";
					var text7="학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "공부시간이 부족합니다. 나머지 계획을 수정해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "평점관리이 어려움이 있어보입니다. 선생님과 상담해주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "시간에 비하여 활동양이 다소 부족해 보입니다. 귀가검사 시 보충학습이 발생할 수 있습니다..",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "계획이 주간목표와 맞지 않습니다. 선생님과 상의해주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "제대로 이해를 했는지 알기 힘든 오답노트가 다수 발견되었습니다. 선생님과 상의해 주세요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "학습을 완료한 후 귀가검사를 제출해 주시기 바랍니다.",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
	 
			$(\'#alert_edittoday\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		

					var text1="전달사항을 입력해 주세요.";
					var text2="시험목표를 고려하여 주간목표를 입력해 주세요";
					var text3="주간목표를 고려하여 오늘목표를 입력해 주세요";
					var text4="오늘목표를 고려하여 구체적인 활동설계를 해주세요";
					var text5="진도가 늦어 시험대비 시간이 부족해 보입니다. 선생님과 상의해 주세요";
					var text6="목표에 대한 집중도를 올릴 수 있는 나만의 활동설계 방법을 찾아보아요";
					var text7="목표의 난이도가 낮으면 집중력이 생기지 않아 스트레스의 원인이 됩니다.";

			swal("지시사항을 선택해 주세요",  "전달된 지시사항은 귀가검사 시 체크됩니다.",{
			  buttons: {
			    catch1: {
			      text: "전달사항을 입력해 주세요.",
			      value: "catch1",className : \'btn btn-primary\'
			    },
			    catch2: {
			      text: "시험목표를 고려하여 주간목표를 입력해 주세요",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "주간목표를 고려하여 오늘목표를 입력해 주세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "오늘목표를 고려하여 구체적인 활동설계를 해주세요.",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "진도가 늦어 시험대비 시간이 부족해 보입니다. 선생님과 상의해 주세요",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "목표에 대한 집중도를 올릴 수 있는 나만의 활동설계 방법을 찾아보아요.",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "목표의 난이도가 낮으면 집중력이 생기지 않아 스트레스의 원인이 됩니다",
			      value: "catch7",className : \'btn btn-primary\'
			    },

			cancel: {
				text: "취소",
				visible: true,
				className: \'btn btn-Success\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			      swal("Pikachu fainted! You gained 500 XP!");
			      break;
			 
 			   case "catch1":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
				swal({
					title: \'전달사항을 입력해 주세요\',
					html: \'<br><input class="form-control" placeholder="Input Something" id="input-field">\',
					content: {
						element: "input",
						attributes: {
							placeholder: "내용입력 (기본 : 전달사항을 입력해 주세요.)",
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
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":Inputtext,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					
					 }
					 })
				  location.reload();
				}
				);
			   
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text2,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text3,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text4,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text5,
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text6,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"checkfeedback.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"inputtext":text7,	
					"userid":Userid,
					"contextid":Contextid,	
 					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   default:
			      swal("OK !", "취소되었습니다.", "success");
				  }
				});			 		
			});   
			$(\'#alert_timeA\').click(function(e){
				var Userid=\''.$studentid.'\';
				var Mtid=\''.$mtid.'\';
				var Cid=\''.$cid.'\'; 

				 Swal.fire({
				  title: \'복습하기 시간 (분)\',
				  icon: \'range\',
				  input: \'range\',
				  inputLabel: \'복습활동을 위한 시간을 입력해 주세요.\',
				  inputAttributes: {
				    min: 10,
  				    max: 300,
 				    step: 10
 				 },
 				 inputValue: 30
				}).then((result) => {
				if (result.isConfirmed) {
				    		Swal.fire(
				     		 \'적용되었습니다.\',
				     		 \'업데이트 버튼을 누르면 화면에 표시됩니다.\',
				   		 \'success\'
				   		 )
					setTimeout("location.reload()", 1000);;
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'11\',
					"userid":Userid,
					"mtid":Mtid,	
					"cid":Cid,	
					"inputvalue":result.value,
					         },
					        })
					}
				  })
			  
			});  
			$(\'#alert_timeB\').click(function(e){
				var Userid=\''.$studentid.'\';
				var Mtid=\''.$mtid.'\';
				var Cid=\''.$cid.'\'; 

				 Swal.fire({
				  title: \'나아가기 시간 (분)\',
				  icon: \'range\',
				  input: \'range\',
				  inputLabel: \'나아기기 활동을 위한 시간을 입력해 주세요.\',
				  inputAttributes: {
				    min: 10,
  				    max: 300,
 				    step: 10
 				 },
 				 inputValue: 30
				}).then((result) => {
				if (result.isConfirmed) {
				    		Swal.fire(
				     		 \'적용되었습니다.\',
				     		 \'업데이트 버튼을 누르면 화면에 표시됩니다.\',
				   		 \'success\'
				   		 )
					setTimeout("location.reload()", 1000);;
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'12\',
					"userid":Userid,
					"mtid":Mtid,	
					"cid":Cid,	
					"inputvalue":result.value,
					         },
 
					        })
					}
				  })
			  
			});  
			$(\'#alert_timeC\').click(function(e){
				var Userid=\''.$studentid.'\';
				var Mtid=\''.$mtid.'\';
				var Cid=\''.$cid.'\'; 

				 Swal.fire({
				  title: \'정리하기 시간 (분)\',
				  icon: \'range\',
				  input: \'range\',
				  inputLabel: \'정리하기 활동을 위한 시간을 입력해 주세요\',
				  inputAttributes: {
				    min: 10,
  				    max: 300,
 				    step: 10
 				 },
 				 inputValue: 30
				}).then((result) => {
				if (result.isConfirmed) {
				    		Swal.fire(
				     		 \'적용되었습니다.\',
				     		 \'업데이트 버튼을 누르면 화면에 표시됩니다.\',
				   		 \'success\'
				   		 )
					setTimeout("location.reload()", 1000);;
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'13\',
					"userid":Userid,
					"mtid":Mtid,	
					"cid":Cid,	
					"inputvalue":result.value,
					         },
 
					        })
					}
				  })
			  
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
 		
			$(\'#alert_commentWeek\').click(function(e){
				var amountn=\''.$amountn.'\';
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\', \'3\', \'4\']
				}).queue([
				  {
				    title: \'피드백 만족도\',
				    text: \'선생님의 설명이 도움이 된 정도 (0 ~ 100)\'
				  },
				  {
				    title: \'개념공부 원활도\',
				    text: \'개념공부의 원활도 (0 ~ 100)\'
				  },				  
				  {
				    title: \'문제풀이 원활도\',
				    text: \'문제풀이의 원활도를 (0 ~ 100)\'
				  },
				  {
				    title: \'나의 상태를 표현해 주세요 !\',
				    text: \'그 밖에 공부를 하는 동안 불편함이나 도움이 필요한 부분을 편안하게 표현해 주세요 ~\'
				  }
				]).then((result) => {
				  if (result.value) {
				    	
				    const answers = JSON.stringify(result.value)
					 
					var Userid=\''.$studentid.'\';
					var studentinput=" 피드백 : "+result.value[0]+"  개념공부 : "+result.value[1]+" 문제풀이 : "+result.value[2]+"  메모 : "+result.value[3];
					setTimeout("location.reload()", 1000);
					            $.ajax(
					                    {
							url:"check_today.php",
							type: "POST",
							dataType:"json",
				 			data : {
							"eventid":\'51\',
							"userid":Userid,
							"inputvalue":studentinput,	
							},
					                    })
				    Swal.fire({
				      title: \'수고하셨습니다 !\',
				      html: `
				       입력내용 : 
				        <pre>${answers}</pre>
				      `,
				      confirmButtonText: \'완료\'
				    })

				  }
				})
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
elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
if($ratio1==0 && $Qnum2==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

$mark4='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"> '.$ratio1.'% </span>';
 
/*
$maxtime=time()-604800*3;
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum11=count($recentquestions);
$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
WHERE mdl_question_attempt_steps.userid='$studentid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
$Qnum22=count($recentquestions);
$Qnum22=$Qnum11+$Qnum22;

$ratio2=round($Qnum11/($Qnum22-0.0001)*100,0);
*/

if($ratio2<70)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio2<75)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio2<80)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio2<85)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio2<90)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio2<95)$imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
else $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';

//$mark5='<span class="" style="font-size: 10pt; color: rgb(255,255, 255);">('.$ratio2.'%)</span>';
if($ratio1-$ratio2>=20)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji1.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji2.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji3.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji4.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=0)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji5.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-5)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji6.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-10)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji7.png" width=60 ></a></span>';
elseif($ratio1-$ratio2>=-15)$mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji8.png" width=60 ></a></span>';
else $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji9.png" width=60 ></a></span>';
if($ratio1<0.001) $mark5='<span class="" style="font-size: 12pt; color: rgb(255,255, 255);"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/emoji0.gif" width=60 ></a></span>';
if($ratio2==0  && $Qnum22==0) $imgtoday2='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

$todayscore=$engagement3->todayscore;
$tspeed=$engagement3->speed;
 
	 // 풀이 속도 관리
 
	if($tspeed<10)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed6.png';
	elseif($tspeed<20)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed5.png';
	elseif($tspeed<30)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed4.png';
	elseif($tspeed<60)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed3.png';
	elseif($tspeed<90)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed2.png';
	elseif($tspeed<120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed1.png';
	elseif($tspeed>120)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
	if($tspeed<0)$v_quiz='https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png';
 /*
$wboard1=$DB->get_record_sql("SELECT max(timemodified) FROM mdl_abessi_messages WHERE userid LIKE '$studentid' AND  userrole LIKE 'teacher' AND wboardid NOT LIKE '%tsDoHfRT%'  AND turn LIKE '0'   AND  status NOT LIKE 'complete' ORDER BY timemodified DESC LIMIT 1 ");
$time1 = $wboard1->timemodified;
$wboard2=$DB->get_record_sql("SELECT *  FROM mdl_abessi_messages WHERE userid LIKE '$studentid' AND  userrole LIKE 'student'  AND wboardid NOT LIKE '%tsDoHfRT%' AND turn LIKE '0'   AND  status NOT LIKE 'complete' ORDER BY timemodified DESC LIMIT 1 ");
$time2 = $wboard2->timemodified;
$quizattempt = $DB->get_record_sql("SELECT mdl_quiz.id AS qid, mdl_quiz_attempts.timemodified AS timemodified, mdl_quiz_attempts.timefinish AS timefinish, mdl_quiz_attempts.comment AS comment FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE  mdl_quiz_attempts.userid='$studentid' AND mdl_quiz_attempts.comment !='NULL' ORDER BY mdl_quiz_attempts.timemodified DESC LIMIT 1 ");
$time3 =$quizattempt->timemodified;
$getlog1 = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page LIKE 'studentindex' ORDER BY id DESC LIMIT 1");
$timevisited1 = $getlog1->timecreated;
$getlog2 = $DB->get_record_sql("SELECT * FROM mdl_abessi_missionlog WHERE userid='$studentid' AND page LIKE 'studentindex' ORDER BY id DESC LIMIT 1");
$timevisited2 = $getlog2->timecreated;
*/
$recentmessage='';
 
$imgstatus=$lastdetection.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$recentmessage.'&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1" accesskey="w"><img src='.$v_quiz.' width=50></a>&nbsp;&nbsp;&nbsp;&nbsp;'.$mark4.'&nbsp;<img src='.$imgtoday.' width=35><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/Preloader2.gif" width=60 ><img src='.$imgtoday2.' width=35>&nbsp;&nbsp;'.$mark5. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$timeplan = $DB->get_record_sql("SELECT max(id) FROM mdl_abessi_schedule WHERE userid='$studentid'  ");
$tabtitle=$goal->text;
if($goal->text==NULL)$tabtitle=$studentname.'&nbsp;'.get_string('mydashboard', 'local_augmented_teacher');
if($role==='student')$messageicon='<a href="#" class="nav-link quick-sidebar-toggler"><i class="flaticon-envelope-1"></i></a>';
else $messageicon='<span onClick="showMoment(\''.$studentid.'\')" accesskey="m"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1636252058.png width=40></span>';

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
			<div align="center" class="logo-header" style="white-space: nowrap; ">
			<b><a  style="color:white;" href="https://mathking.kr/moodle/my" target="_blank">(주)초지능</a></b></div>
			<!-- End Logo Header -->

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue">
				<!--
					Tip 1: You can change the background color of the navbar header using: data-background-color="black | dark | blue | purple | light-blue | green | orange | red"
				-->
				<div class="container-fluid ">
					<!--<div class="navbar-minimize toggled">
						<button class="btn btn-minimize btn-rounded toggled">
							<i class="la la-navicon"></i>
						</button>
					</div>-->
					 
					<div  style=" font-size:20;color:white;white-space: nowrap;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b style="font-size:20;">분기목표</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$examplanid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>&nbsp;&nbsp; '.$termMission.'......<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'"target="_blank">'.$EGinputtime.'</a> </div>  
					 
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
						 '.$imgstatus.' 		 
						<li class="nav-item">
							'.$messageicon.'	
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
				<div class="sidebar-content "><br> 
					<div class="user">
						<div class="photo">
							 <img src="https://mathking.kr/moodle/user/pix.php/'.$studentid.'/f1.jpg"  height=20 alt="image profile">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
									<span class="user-level" style="margin-top:15;"><h4>'.$username->firstname.$username->lastname.'</h4></span>
								</span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
<li><a href="https://mathking.kr/moodle/user/profile.php?id='.$studentid.'" target="_blank">사용자 정보</a></li>
<li><a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'" target="_blank">기본정보</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'" target="_blank"  accesskey="v">비계설정</a></li>
<li><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=4"  accesskey="n">출결정보</a></li>
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
							<a href="fullengagement.php?id='.$studentid.'">
								<i class="flaticon-idea"></i>
								<p>기억연장</p> 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/searchmynote.php" target="_blank">
							<i class="flaticon-search-1"></i>
							<p>개념검색</p>							
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
									<a href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'"  accesskey="g">
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
					        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$studentid.'&eid=1&nweek=4"  accesskey="."><i class="flaticon-calendar"></i><p>시간표</p></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/editschedule.php?id='.$studentid.'&nweek=4&eid='.$timeplan->id.'"  accesskey="/"></a>					
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

						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="la la-ellipsis-h"></i>
							</span>
							<h4 class="text-section">나는 선생님</h4>
						</li>
						<li class="nav-item"> 
							<a href="https://mathking.kr/moodle/local/augmented_teacher/beateacher/iteach.php?id='.$studentid.'&tb=86400">
								<i class="flaticon-share-1"></i>
								<p>과외관리</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li> 
						<li class="nav-item"> 
							<a href="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603114164001.png">
								<i class="flaticon-share-1"></i>
								<p>기부설정</p>
								<span class="badge badge-count badge-info">1</span>
							</a>
						</li> 
					</li> </ul>';

 
$imgdefault='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647161635.png';
$imgalert='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647430692.png';
$imgCA=$imgdefault; $imgCB=$imgdefault; $imgCC=$imgdefault; $imgCD=$imgdefault;

$talkexist=$DB->get_record_sql("SELECT max(timemodified),context  FROM mdl_abessi_talk2us WHERE studentid='$studentid' AND status='alert' AND timemodified >'$aweekago'  ");
 
if($talkexist->context==='CA')$imgCA=$imgalert;
else $imgCA=$imgdefault;

if($talkexist->context==='CB')$imgCB=$imgalert;
else $imgCB=$imgdefault;

if($talkexist->context==='CC')$imgCC=$imgalert;
else $imgCC=$imgdefault;

if($talkexist->context==='CD')$imgCD=$imgalert;
else $imgCD=$imgdefault;
 
echo '
 
					
				</div>
			</div>
		</div> 
		<!-- End Sidebar --> 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					 <div class="row" style="background-color:white"><table width=100%><tr><td  width=2%></td>
						<td  valign=top width=8%  align=center><a href="https://app.gather.town/app/0bwIAlhyu6Z7ynWK/KAIST%20TOUCH%20MATH"target="_blank"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1648457660.png width=110></a></td>						
						<td  valign=top width=9%  align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/almtyroutine.php?inputsrc=1" accesskey="a">'.$lastPlanScore.'</a> </td>
							<td align=center width=14%><div class="card card-stats card-info card-round">
								<div class="card-body"  style="background-color:#1a75ff;">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank"><img src="'.$imgCA.'" height=80></a>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title" style="text-align:center">개념미션</h6>
												<h6 class="card-title" style="text-align:center"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/setconditions.php?id='.$USER->id.'&studentid='.$studentid.'&mode=CA"target="_blank">학습루틴</a></h6> 	
												
											</div>
										</div>
									</div>
								</div>
							</div></td><td  width=14%><div class="card card-stats card-info card-round">
								<div class="card-body"  style="background-color:#1a75ff;">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank"><img src="'.$imgCB.'" height=80></a>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">심화미션</h6>
												<h6 class="card-title"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/setconditions.php?id='.$USER->id.'&studentid='.$studentid.'&mode=CB"target="_blank">학습루틴</a></h6> 	
												
											</div>
										</div>
									</div>
								</div>
							</div></td><td  width=14%><div class="card card-stats card-info card-round">
								<div class="card-body "  style="background-color:#1a75ff;">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank"><img src="'.$imgCC.'" height=80></a>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">내신미션</h6>
												<h6 class="card-title"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/setconditions.php?id='.$USER->id.'&studentid='.$studentid.'&mode=CC"target="_blank">학습루틴</a></h6> 	
												
											</div>
										</div>
									</div>
								</div>
							</div></td><td  width=14%><div class="card card-stats card-info card-round">
								<div class="card-body "  style="background-color:#1a75ff;">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
											<a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank"><img src="'.$imgCD.'" height=80></a>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<h6 class="card-title">수능미션</h6>
												<h6 class="card-title"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/setconditions.php?id='.$USER->id.'&studentid='.$studentid.'&mode=CD"target="_blank">학습루틴</a></h6> 	
												
											</div>
										</div>
									</div>
								</div>
							</div></td><td  valign=top align=center  width=8%> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/brainactivations.php?studentid='.$studentid.'&tb=604800" target="_blank">'.$stability.'</a></td><td  valign=top width=8% align=center  ><button id="alert_nextpage" style="border:none;background: none;" onclick="" accesskey="u">'.$lastScore.'</button>
							 </td><td  width=2%></td></tr></table><table align=center><tr style="margin-bottom:50px;"><td>KAIST TOUCH MATH PEER LEARNING</td></tr></table></div>';

$ts = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);  // 월요일 0시에 대한 time stamp
$timefrom=round((time()-$ts)/86400,3);
echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.'" ></iframe></div>';

echo '<script>  
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
</script>';
echo '<script>secondbrain();</script>';

?>