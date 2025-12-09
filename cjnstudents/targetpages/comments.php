<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$studentid=$_GET["studentid"];  
$mode=$_GET["mode"];   
$tb=$_GET["tb"];   
$ncomments=20;
 
echo '<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
 
// 학생 맞춤형 환경 설정

$conditions=$DB->get_records_sql("SELECT * FROM mdl_abessi_knowhowlog WHERE studentid='$studentid' AND active='1' ORDER BY timemodified ");  
$conditionslist= json_decode(json_encode($conditions), True);
 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

unset($value3);  
foreach($conditionslist as $value3)
	{
	$srcid=$value3['srcid']; 
	$item1=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE id='$srcid' ORDER BY id DESC LIMIT 1"); //선택유형
	$course=$item1->course; $type=$item1->type; $text=$item1->text; 
	$item2=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE srcid='$srcid' AND active='1' ORDER BY id DESC LIMIT 1"); // 선택메뉴
	$text2=$item2->text; 

	if($mode==='CA' && $course==='개념미션')$chosenitems.='<tr><td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.'</td></tr>';
	elseif($mode==='CB' && $course==='심화미션')$chosenitems.='<tr><td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.'</td></tr>';
	elseif($mode==='CC' && $course==='내신미션')$chosenitems.='<tr><td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.'</td></tr>';
	elseif($mode==='CD' && $course==='수능미션')$chosenitems.='<tr><td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.'</td></tr>';
	}

// 선생님들 talk2us
 
$tbegin=time()-$tb; //1주 전
$share=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE studentid='$studentid' AND eventid='7128'  ORDER BY timemodified DESC LIMIT 20");  
$talklist= json_decode(json_encode($share), True);
 
unset($value);  
foreach($talklist as $value)
	{
	$sid=$value['id'];
	 
	$teacherid=$value['teacherid'];
	$sharetext=$value['text'];
	$stdname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
	$studentname=$stdname->firstname.$stdname->lastname;
	$tchname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
	$teachername=$tchname->firstname.$tchname->lastname;
 
	$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
	$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid' AND courseid NOT LIKE '239' AND component NOT LIKE 'core' AND  component NOT LIKE 'local_webhooks'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog		 
	$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 

	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
	$tgoal=time()-$goal->timecreated;

	$ratio1=$engagement3->todayscore;  $ngrowth=$engagement3->ngrowth; $usedtime=$engagement3->usedtime; $totaltime=$engagement3->totaltime; $nattempts=$engagement3->nattempts; 
	$attemptefficiency=$nattempts/$totaltime;
	 
	$weekdata= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE  type LIKE '주간목표' AND userid='$studentid' ORDER BY id DESC LIMIT 1  ");  // abessi_indicators 
	$ratio2= $weekdata->score; $daysetgoal=(time()-$weekdata->timecreated)/86400;
  	$analysistext='';
	if($usedtime<70)$analysistext='출결이상';
	elseif($nattempts<30)$analysistext='풀이이상';
	elseif($attemptefficiency<5)$analysistext='효율이상';
		
		
	$useinfo=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where  userid='$studentid' AND fieldid='90' "); 
	if($useinfo->data==NULL)$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646447390.png" width=30>'; 
	elseif($useinfo->data==='자습')
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30>'; 
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646435924.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436175.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}
	elseif($useinfo->data==='지도')
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30>'; 
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436605.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436540.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}
 	 elseif($useinfo->data==='도제') 
		{
		$modebyt='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30>';
		if($indicators->aion==1)
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436775.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646436824.png" width=30> '; 
			}
		else
			{
			if(($ratio1>=80 && $indicators->ngrowth>=1) || ($goal->status==='stable'))$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646731085.png" width=30> ';  // 회복됨
			else $modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646737119.png" width=30> '; 
			}
		}

	if($tlastaction>36000)$modebydata='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646616360.png" width=30> '; 

	if($ratio2<70)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
	elseif($ratio2<75)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
	elseif($ratio2<80)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
	elseif($ratio2<85)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
	elseif($ratio2<90)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
	elseif($ratio2<95)$imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	else $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png';
	if($ratio2==0) $imgmonth='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

 	if($ratio1<70)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<75)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<80)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<85)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<90)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	elseif($ratio1<95)$imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	else $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
	if($ratio1==0 && $Qnum2==0) $imgtoday='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1 "><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20></a><img src='.$imgmonth.'  width=20> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'"target="_blank">'.$modebyt.'</a> <span type="button"  onClick="">'.$modebydata.'</span>';
 
	$sharelist.='<table width=100% ><tbody><tr><td width=1%></td><td width=7% style="white-space: nowrap; text-overflow: ellipsis;"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800"><b style="color:black;">'.$studentname.'</b></a></td><td width=5%><a style="color:#3399ff;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/talk2us.php?id='.$teacherid.'&tb=604800&mode=my">'.$teachername.'</a></td><td style="color:#3399ff;">'.$sharetext.' <span type="button"  onClick="reportData(\''.$studentid.'\',\''.$sid.'\',\''.$studentname.'\')"><img style="padding-bottom:3px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1646873784.png width=25></span></td><td width=5%></td><td width=10%>'.date("Y-m-d", $value['timecreated']).'</td></tr></tbody></table>';

	$feedback=$DB->get_records_sql("SELECT * FROM mdl_abessi_talk2us WHERE eventid='8217' AND talkid='$sid'    ORDER BY id DESC ");  
	$feedbacklist= json_decode(json_encode($feedback), True);
	$fbname='fb'.$sid;
	unset($value2);  
	foreach($feedbacklist as $value2)
		{
		$feederid=$value2['teacherid'];
		$feedertext=$value2['text'];
		$tcreated=round((time()-$value2['timecreated'])/60,0);
		$feeder= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$feederid' ");
		$feedername=$feeder->firstname.$feeder->lastname;

			if($value2['hide']==1) $visibility='<img src=https://mathking.kr/Contents/IMAGES/hide.png width=20>';
			else $visibility.='<img src=https://mathking.kr/Contents/IMAGES/view.png width=20>';

		if(($value2['hide']!=1 || $role!=='student') && $role!=NULL)$$fbname.='<tr><td width=3%></td><td width=5% ></td><td width=5%>'.$feedername.'</td><td>'.$feedertext.' ('.$tcreated.'분) </td><td  style="margin-top:6px;">'.$visibility.'</td><td width=15%></td></tr><tr><td width=3%></td><td width=5% ></td><td width=5%></td><td><hr></td><td width=15%><hr></td></tr>';
		}
	$sharelist.='<table width=100%><tbody>'.$$fbname.'</tbody></table>';
		 
	}
 
$ndays=(INT)($tb/86400);

$talkexist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_talk2us WHERE studentid='$studentid' AND status='alert' AND timemodified >'$aweekago' ORDER BY id DESC LIMIT 1    ");
 
$btncolor='white';
if($talkexist->id!=NULL)$btncolor='red';


if($mode==='CA')$routinetype='개념루틴';if($mode==='CB')$routinetype='심화루틴';if($mode==='CC')$routinetype='내신루틴';if($mode==='CD')$routinetype='수능루틴';
echo '<table width=100%><tr><td align=center width=7%></td><td width=10%></td><td><b style="font-size:28;"> &nbsp;We transfer intelligence </b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-size:16;"> powered by Chojineung Inc. </span> <button type="button"  style="background-color:'.$btncolor.';"  onClick="sendmessage(17,\''.$studentid.'\',\''.$mode.'\')">알림설정</button></td><td>    ✎ <a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/setconditions.php?id='.$USER->id.'&studentid='.$studentid.'&mode='.$mode.'">나의 '.$routinetype.'</a> & <a style="color:#ff3333;" href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitive_map.php?id='.$studentid.'&period=30">상담 데이터</a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb='.($tb+604800).'&mode='.$mode.'">더보기</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/comments.php?studentid='.$studentid.'&tb='.($tb-604800).'&mode='.$mode.'">덜보기</a></td><td>('.$ndays.'일)</td><td width=5%></td></tr></table>';
echo '<hr><table align=center>'.$chosenitems.'</table>';
echo '<hr><table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$sharelist.'</tbody></table> ';
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
										 
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											 
										</div>
									</div>
								</div>
							</div>
						</div>
			 ';
 
 

echo '
<script>	
function reportData(Userid,Sid,Username)
	{
	(async () => {
	const { value: text } = await  Swal.fire({
	title: "Talk2us (" + Username +")",
 	input: "textarea",
	confirmButtonText: "저장",
	cancelButtonText: "취소",
 	inputPlaceholder: "공유된 의견과 데이터를 토대로 의견을 입력해 주세요",
  	inputAttributes: {
   	 "aria-label": "Type your message here"
	  },
          showCancelButton: true,
	})

	if (text) {
	  	Swal.fire(text);
		$.ajax({
		url:"check.php",
		type: "POST",
		dataType:"json",
		data : {
 		"eventid":\'11\',
		"inputtext":text,	
		"userid":Userid,
		"sid":Sid,
		},
		success:function(data){
		var talkid=data.talkid;
		setTimeout(function() {location.reload(); },100);		
				   }
			 })
	      	 }
		})()
	
	}
function sendmessage(Eventid,Userid,Mode){	 
 		swal("알림이 설정되었습니다.", {buttons: false,timer: 1000});
  		 $.ajax({
       		 url: "../teachers/check.php",
        		type: "POST",
        		dataType: "json",
        		data : { 
		"eventid":Eventid,
            		"mode":Mode,
            	 	"userid":Userid,
            	 	  },
 	 	      success: function (data){  
  	   	   }
		  });
		}
</script> 


<style>
a:link {
  color : red;
}
a:visited {
  color :grey;

}
a:hover {
  color : blue;
}
a:active {
  color : purple;
}

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
 

.tooltip1:hover .tooltiptext1 {
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
  width: 500px;
  background-color: #ffffff;
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
 

.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;

}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width:700px;
  background-color: #ffffff;
  color: #000000;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  padding: 20px 1;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}
 

.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}
a.tooltips {
  position: relative;
  display: inline;
}
a.tooltips span {
  position: fixed;
  width: 700px;
/*height: 100px;  */
  color: #FFFFFF;
  background: #FFFFFF;

  line-height: 96px;
  text-align: center;
  visibility: hidden;
  border-radius: 8px;
  z-index:9999;
  top:50px;
/*  box-shadow: 10px 10px 10px #10120f;*/
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

 

</style>';
echo ' 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
 ';
?>