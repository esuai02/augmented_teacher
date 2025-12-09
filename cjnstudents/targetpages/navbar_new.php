<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$cid = $_GET["cid"]; 
$access = $_GET["access"]; 
if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
$nmobile=1;

if($nmobile!=1)require_login();
$url= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];    
if($USER->id==NULL)header('Location: https://mathking.kr/moodle/my/');
if(strpos($url, 'php?id')!= false)$studentid=required_param('id', PARAM_INT); 
else $studentid=$USER->id;
$timecreated=time();
$useinfo=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where  userid='$USER->id' AND fieldid='90' "); 
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
$reducetime=0;$eventtext=' * ';
$indic= $DB->get_record_sql("SELECT id,nforce,teacherid,weekquizave,ntodo,appraise FROM mdl_abessi_indicators WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$readtime= $DB->get_record_sql("SELECT max(id) AS id,teacherid FROM mdl_abessi_indicators WHERE userid='$studentid' AND timecreated>'$halfdayago' ");
//if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,teacherid,timemodified,timecreated) VALUES('$studentid','$indic->teacherid','$timecreated','$timecreated')");
if($readtime->id==NULL && $USER->id==$studentid) $DB->execute("INSERT INTO {abessi_indicators} (userid,timemodified,timecreated) VALUES('$studentid','$timecreated','$timecreated')");
$alert_id='alert_ask';

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
$examplan=$DB->get_record_sql("SELECT id, wboardid FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='period' ORDER BY id DESC LIMIT 1");
$examplanid=$examplan->wboardid;
 
if($role==='student')$tabtitle=$studentname.'&nbsp;'.get_string('mydashboard', 'local_augmented_teacher');

$weeklyquizave=$indic->weekquizave;
$ntodo=$indic->ntodo;
$nforce=$indic->nforce;
	
$Autopilot=$DB->get_record_sql("SELECT data   FROM mdl_user_info_data where userid='$USER->id' and fieldid='82' ");
$AutopilotMode=$Autopilot->data;
if($AutopilotMode==='AI' && $role!=='student') // 새로운 관찰학생 onair 열기 , 닫는 것은 onair page에서 수행
		{
 		echo '<script>setTimeout(function() {window.close(); },3000000);  </script>';
		}  
 

$curl1=substr($url, 0, strpos($url, '?')); // 문자 이후 삭제
$curl2=strstr($url, '?');  //before
$curl2=str_replace("?","",$curl2);

// 호출 OR Onair 실시간 지도
$timediff=time()-1800;
$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE userid='$USER->id' AND (forced='1' OR forced='2') AND timemodified >'$timediff'  ORDER BY id DESC LIMIT 1  ");
if($exist->id!=NULL && $role==='student' && $exist->forced==1 )header('Location:https://mathking.kr/moodle/local/augmented_teacher/students/cometome.php?id='.$studentid.'');
elseif($exist->id!=NULL && $role==='student' && $exist->forced==2)header('Location:https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$exist->wboardid.'');

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
	//$prevgoal= $DB->get_record_sql("SELECT  max(id) FROM  mdl_abessi_today WHERE userid='$studentid' AND (rtext1 NOT LIKE 'NULL' OR ntext1 NOT LIKE 'NULL') AND timecreated < '$timeback' AND (type LIKE '오늘목표' OR type LIKE '검사요청' )");
	$checkgoal_maxid= $DB->get_record_sql("SELECT max(id) AS id FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ");
	$checkgoal= $DB->get_record_sql("SELECT id, text, score,comment,amountr,amountn,amountp,rtext1, ntext1, ptext1, timecreated FROM  mdl_abessi_today Where id ='$checkgoal_maxid->id'  AND timecreated>'$timeback' ");
	//$checkgoal= $DB->get_record_sql("SELECT id, text, score,comment,amountr,amountn,amountp,rtext1, ntext1, ptext1, timecreated FROM  mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");
	//$checkWeekgoal= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$timeback' AND type LIKE '주간목표'  ORDER BY id DESC LIMIT 1 ");
	$ratio1=$checkgoal->score;
	$nextplan=$checkgoal->comment;

	$amountr=$checkgoal->amountr;
	$amountn=$checkgoal->amountn;
	$amountp=$checkgoal->amountp;
	$rtext1=$checkgoal->rtext1;
 
	$ntext1=$checkgoal->ntext1;
 
	$ptext1=$checkgoal->ptext1;
 
if($useinfo->data!=='신규')include_once("intervention.php");


	$tgoal=$checkgoal->timecreated;
	$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
	$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
 
	$schedule=$DB->get_record_sql("SELECT id,editnew, start1,start2,start3,start4,start5,start6,start7,duration1,duration2,duration3,duration4,duration5,duration6,duration7 FROM mdl_abessi_schedule where userid='$studentid' AND pinned='1' ORDER BY id DESC LIMIT 1 ");
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
 
if($steptext!==NULL)$steptext.='<br><span style="font-size:16;color:red;text-align:center;">단계를 완료하거나 약속된 시간이 되면 선생님에게 검사를 받아주세요 !</span>';
// 오늘일정 끝
 

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$studentid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

$wtimestart=time()-864000;
$wgoal= $DB->get_record_sql("SELECT max(id),score  FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ");
$ratio2=$wgoal->score;
$wtimestart1=$timecreated-86400*($nday+1);
$wtimestart2=$timecreated-86400*($nday+8);  
 
$lastwgoal= $DB->get_record_sql("SELECT max(id),planscore FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' AND timecreated < '$wtimestart1' AND timecreated > '$wtimestart2' ");
$lastWeekPlanScore=$lastwgoal->planscore;

$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468762.png" height=110>';
if($indic->appraise<20)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469906.png" height=110>';
elseif($indic->appraise<40)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642468940.png" height=110>';
elseif($indic->appraise<60)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469042.png" height=110>';
elseif($indic->appraise<80)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469126.png" height=110>';
elseif($indic->appraise<100)$stability='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1642469222.png" height=110>';

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
';

/*
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
</script>
*/

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
  border-bottom: 1px solid black;
font-size: 14px;
}

.tooltip2 .tooltiptext2 {
    
  visibility: hidden;
  width: 400px; //height:300px;
  background-color: white;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 1px;
   border-color: grey;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  right:100%; // top:100%;
 // bottom:50%;

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
$predict=$DB->get_record_sql("SELECT * FROM mdl_abessi_forecast where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
//if($predict->id==NULL)$progresstext='';
//$progresstext='';
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
			  
			$(\'#alert_roadmap\').click(function(e) {
					
					var Userid=\''.$studentid.'\';
					var Contextid=\''.$curl1.'\';
					var Currenturl=\''.$curl2.'\';		
					var Attemptid=\''.$attemptid.'\';	
					var Quiztitle=\''.$quiztitle.'\';	
					var text1="전달하고자 하는 내용을 직접 입력하겠습니다.";
					var text2="현재 진행상황을 고려하여 분기목표를 변경할 필요가 있는지 체크해 주세요";
					var text3="분기목표를 성공적으로 수행하기 위한 중간목표를 설정해 주세요";
					var text4="학기 중에는 학교시험 목표점수를 분기목표로 설정하는 것이 효과적입니다.";
					var text5="방학 때는 개학 전까지 목표로하는 내용을 분기목표에 입력해 주세요";
					var text6="두개 이상의 강좌를 진행 중일 때는 각각의 분기목표를 모두 입력해 주세요";
					var text7="분기목표 설정관련하여 대화가 필요해 보입니다. 선생님에게 와주세요 !";
					
			swal("분기목표 설정하기",  "분기목표와 연관된 메세지 내용을 선택해 주세요.",{
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
elseif($ratio1<95)
	{
	$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	$reducetime=$reducetime+5;
 	$eventtext.='평점A 5분 | ';
	}
else 
	{
	$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	$reducetime=$reducetime+10;
	$eventtext.='평점Aplus 10분 | ';
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
 
$recentmessage='';
 
$imgstatus=$lastdetection.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$recentmessage.'&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1" accesskey="w"><img src='.$v_quiz.' width=50></a>&nbsp;&nbsp;&nbsp;&nbsp;'.$mark4.'&nbsp;<img src='.$imgtoday.' width=35><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/Preloader2.gif" width=60 ><img src='.$imgtoday2.' width=35>&nbsp;&nbsp;'.$mark5. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$timeplan = $DB->get_record_sql("SELECT max(id) FROM mdl_abessi_schedule WHERE userid='$studentid' AND pinned=1 ");
 

$messageicon1='<span onClick="dragChatbox(\''.$studentid.'\')"  ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1657195665.png height=30></span>';
if($role!=='student')$messageicon2='<span onClick="showMoment(\''.$studentid.'\')" accesskey="m"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1636252058.png height=30></span>';

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

$institute = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='88' ");// 학교 
$birthyear = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='89' ");//출생년도 
$thisyear=date("Y",time());
$ngrade=$thisyear-$birthyear->data-6;
if($ngrade<=6 )$ngrade=$ngrade; 		 
elseif($ngrade<=9 )$ngrade=$ngrade-6; 
elseif($ngrade<=13 )$ngrade=$ngrade-9; 
if($institute->data==NULL || $birthyear->data==NULL) $schinfo='정보 미입력';
else $schinfo=$institute->data.' '.$ngrade;
	
if($role==='student')$userinfo='<b style="font-size:20;">'.$studentname.'</b> ('.$schinfo.')';
else $userinfo='<b style="font-size:20;"><a  style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'" accesskey="v">'.$studentname.'</a></b> (<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/userlist.php?id='.$studentid.'&info=institute">'.$schinfo.'</a>)';

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
					 
					<div  style=" font-size:20;color:white;white-space: nowrap;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$userinfo.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$examplanid.'"target="_blank"><img src="http://mathking.kr/Contents/IMAGES/whiteboardicon.png" width=30></a>&nbsp;&nbsp; '.$termMission.'......<a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/students/roadmap.php?id='.$studentid.'">'.$EGinputtime.'</a> </div>  
					 
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="flaticon-search-1"></i>
							</a>
						</li>
						 '.$imgstatus.' 		 
						<li class="nav-item">
							'.$messageicon1.' '.$messageicon2.'	
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
							<a  style="margin-top:10px;" data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
								<h4>&nbsp;</h4>
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
							<a href="fullengagement.php?id='.$studentid.'">
								<i class="flaticon-idea"></i>
								<p>기억연장</p> 
							</a>
						</li>
						<li class="nav-item">
							<a href="https://mathking.kr/moodle/local/augmented_teacher/students/connectboosters.php?domain=1&studentid='.$studentid.'&contentstype=1" target="_blank">
							<i class="flaticon-search-1"></i>
							<p>장기기억 활동</p>							
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
								<div class="card-body"  style="background-color:white;">
									<div class="row">
										<div class="col-12" col-stats>
										<table><tr>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/red.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CA"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/orange.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/yellow.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CB"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/white.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/green.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CC"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/blue.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/black.png" height=80></a></td>
										<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb=604800&mode=CD"target="_blank"><img src="https://mathking.kr/Contents/IMAGES/thinking%20hats/bluem.png" height=80></a></td>
										</tr></table>
										 </div>
									</div>
								</div>
							</div></td><td  valign=top align=center  width=8%> <span onClick="PredictResult()" accesskey="4">'.$stability.'</a></td><td  valign=top width=8% align=center  ><button id="alert_nextpage" style="border:none;background: none;" onclick="" accesskey="u">'.$lastScore.'</button></td><td  width=2%></td></tr></table><table align=center><tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom:50px;" ><td><img style="margin-botton:30;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1661136121.png" width=20> 개념집착 노트 </td>
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=66&nch=1&mode=domain&domain=120&studentid='.$studentid.'"target="_blank">수체계</a> | </td>
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
<td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/obsession.php?cid=67&nch=1&mode=domain&domain=136&studentid='.$studentid.'"target="_blank">통계</a></td></tr></table></div>';

$ts = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);  // 월요일 0시에 대한 time stamp
$timefrom=round((time()-$ts)/86400,3);
echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/blocks/use_stats/detail.php?id=152359&course=1&ts_from='.$timefrom.'&userid='.$studentid.'" ></iframe></div>';
echo '<div style="display:none;"><iframe  src="https://mathking.kr/moodle/local/augmented_teacher/students/stdmarrival.php?userid='.$USER->id.'"></iframe></div>';
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

if($useinfo->data==='신규')echo '<script>secondbrain2();</script>';
else echo '<script>secondbrain();</script>';
//echo '<script>secondbrain();</script>';

?>