<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teacherhelpfortopics','$timecreated')");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

$tbegin=0;
 
$sssskey= sesskey(); 
 
$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
$userlist= json_decode(json_encode($mystudents), True);
$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$USER->id' and fieldid='57' ");
if($subject->subject==='MATH')$contains='%MX%';
elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%'; 

/////////////////////////// end of code snippet ///////////////////////////
echo ' 
		<div class="main-panel">
			<div class="content">
				<div class="row">
						<div class="col-md-12">';
 
$star1='';
$star2='';
$star3='';
if($tbegin==1800)$star1='*';
if($tbegin==3600)$star2='*';
if($tbegin==43200)$star3='*';
echo  '
<table align="center" style="width: 100%;"><tbody><tr>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/incorrectresponse.php?id='.$teacherid.'&tb=1800">30분전'.$star1.' </a> </td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/incorrectresponse.php?id='.$teacherid.'&tb=3600">1시간 전'.$star2.'</a> </td>
<td align=center><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/incorrectresponse.php?id='.$teacherid.'&tb=43200">오늘'.$star3.'</a><td><tr></tbody></table><hr>
 개념과제 출제 > 개념화이트 보드 작성 > 개념확인문제 풀기 > 제출 > 검사 (개념 노트를 제작하고 필기양 등으로 노트 완성정도를 시각화)<hr>
개념 컨텐츠 협업 환경 배경화 <a href="https://mathking.kr/moodle/mod/page/view.php?id=82590&forceview=1" target="_blank"> 영역별 개념</a>';

echo ' <table style="width: 100%;"><tbody>';
unset($user);
$nowhiteboard='&nbsp;&nbsp; 오답노트 미작성 ';
foreach($userlist as $user)
	{
	$userid=$user['id'];
	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
	$maxtime=time()-43200;

	///////////////////// 오답노트 미제출 /////////////////////////
	$quizattempts = $DB->get_records_sql("SELECT * ,mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  WHERE mdl_quiz_attempts.userid='$userid' ORDER BY mdl_quiz_attempts.timestart DESC LIMIT 2");
	$result = json_decode(json_encode($quizattempts), True);
 
	unset($value);
	$index=0;
	
	foreach($result as $value)
		{
		$index++;
		if ($index==1) continue;
		$timeafter=(time()-$value['timestart']);
		if( $timeafter<7200)
			{
			$shot1=$DB->get_record_sql("SELECT max(timestart) AS shotstart FROM mdl_quiz_attempts where userid='$userid' "); 
			$shot2=$DB->get_record_sql("SELECT max(timefinish) AS shotfinish FROM mdl_quiz_attempts where userid='$userid' "); 
			$shot3=$DB->get_record_sql("SELECT max(created) AS shotcreated FROM mdl_studentquiz_comment where userid='$userid' "); 

			$shot1=$shot1->shotstart;
			$shot2=$shot2->shotfinish;
			$shot3=$shot3->shotcreated;

			if(($shot3 < $shot1)&&($shot2 < $shot1)&&($shot3 < $shot2)&& $value['sumgrades'] / $value['tgrades'] < 0.99)  
				{ 
	      			$nowhiteboard.='<a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" >... ('.$lastname.'&nbsp;<img src="https://cdn.pixabay.com/photo/2019/06/08/09/14/high-4259761_960_720.png" width=15>)</a> '; 
				}
 			}
		}
	///////////////////// speed of question attempts /////////////////////////
	$questionattempts = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$userid' AND  state !='todo' AND  state !='gaveup'  ORDER BY mdl_question_attempt_steps.timecreated DESC LIMIT 10");
	$result1 = json_decode(json_encode($questionattempts), True);
 
	$marks=NULL;
	unset($value);
	$ntry=0; 
	$ninit=0;
	foreach(array_reverse($result1) as $value)
		{
		$state=NULL;
		$helplist=NULL;
		$timediff=time()-$value['timecreated'];

		if($timediff<3600 && $ninit==0)
			{
			//echo $timediff.'<br>';
			$tperiod_init=$timediff/60;
			$ninit=1;
			}
		$tperiod=$tperiod_init-$timediff/60;
		$useridtmp=$userid;
		$qidtmp=$value['questionid'];
		$status='';
		$attemptid=$value['id'];

		if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
		if(strpos($value['questiontext'], 'shutterstock')!= false)
			{
			$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '<p style="text-align: right;">'));   
			$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}
		if($value['state']==gradedright && $timediff<3600)$ntry++;
		elseif($value['state']==gradedpartial && $timediff<3600)$ntry++;
		elseif($value['state']==gradedwrong && $timediff<3600)$ntry++;
		elseif($value['state']==complete && $timediff<3600)$ntry++;
		}
	$t_ave=$tperiod/$ntry;
	if($t_ave<1)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed6.png width=23>';
	elseif($t_ave<2)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed5.png width=23>';
	elseif($t_ave<3)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed4.png width=23>';
	elseif($t_ave<6)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed3.png width=23>';
	elseif($t_ave<9)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed2.png width=23>';
	elseif($t_ave<12)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed1.png width=23>';
	elseif($t_ave>12)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png width=23>';
	if($t_ave<=0)$v_quiz='<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/speed0.png width=23>';

	$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid'  ");  
	$lastaction=(time()-$Timelastaccess->maxtc);

	$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question_attempt_steps.userid='$userid' AND  mdl_question_attempt_steps.state='gradedright' AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
	$Qnum1=count($recentquestions);
	$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question_attempt_steps.userid='$userid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial') AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
	$Qnum2=count($recentquestions);
	$Qnum2=$Qnum1+$Qnum2;
 
	if($Qnum1/$Qnum2<0.7)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
	elseif($Qnum1/$Qnum2<0.75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
	elseif($Qnum1/$Qnum2<0.8)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
	elseif($Qnum1/$Qnum2<0.85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
	elseif($Qnum1/$Qnum2<0.9)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
	elseif($Qnum1/$Qnum2<0.95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
	else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus2.png';
	if($Qnum1/($Qnum2-0.0001)==0 && $Qnum2==0)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
	$mark4='<span class="" style="color: rgb(0,0, 0);">'.$Qnum2.'</span>';
 
	/////////////////////////////// 2-4 question list for incorrect response
 
	$time1=time()-$tbegin; 
	$incorrect = $DB->get_records_sql("SELECT *, mdl_question_attempts.id AS id, mdl_question_attempts.questionid AS questionid, mdl_question_attempts.feedback AS feedback FROM mdl_question LEFT JOIN mdl_question_attempts  ON mdl_question.id = mdl_question_attempts.questionid 
	LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
	WHERE mdl_question.name LIKE '$contains' AND mdl_question_attempt_steps.userid='$userid' AND (state='gradedwrong' OR state='gradedpartial')  AND mdl_question_attempts.feedback = 0  AND mdl_question_attempt_steps.timecreated > '$time1'  ORDER BY mdl_question_attempt_steps.timecreated DESC LIMIT 10");

	$nincorrect=count($incorrect);
	$result1 = json_decode(json_encode($incorrect), True);
 
	unset($value);
	foreach($result1 as $value)
		{  
		$timediff2=time()-$value['timecreated']; 
		if(strpos($value['questiontext'], 'ifminassistant')!= false)$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], "<p>{ifminassistant}"));  
		//if(strpos($value['questiontext'], 'shutterstock')!= false)$value['questiontext']='<p> A step </p>';
		if(strpos($value['questiontext'], 'shutterstock')!= false)
			{
			$value['questiontext']=substr($value['questiontext'], 0, strpos($value['questiontext'], '<p style="text-align: right;">'));   
			$value['questiontext']=strstr($value['questiontext'], '<img src="https://mathking.kr/Contents/MATH%20MATRIX/');
			}
		$useridtmp=$userid;
		$qidtmp=$value['questionid'];
		$status='';
		$attemptid=$value['id'];

		$handwriting= $DB->get_records_sql("SELECT created, comment, userid AS id FROM mdl_studentquiz_comment WHERE (comment LIKE '%whiteboardfox%' OR comment LIKE '%ouwiki%') and questionid ='$qidtmp' ORDER BY created DESC LIMIT 4"); 
		$cmt_result = json_decode(json_encode($handwriting), True);  
		unset($cmt_value);
		$nhw=count($handwriting);
		$cmt=null;
		$athlevel=-1;
		$ncount=0;
		foreach(array_reverse($cmt_result) as $cmt_value)
			{
			$comment=$cmt_value['comment'];
			$userid2=$cmt_value['id'];
			//<a href='.$comment.' " target="_blank" >
			$author=$DB->get_record_sql("SELECT data AS level FROM mdl_user_info_data where userid='$userid2' and fieldid='60' ");
			$cntlevel='https://mathking.kr/Contents/Moodle/Visual%20arts/wboard'.$author->level.'.png';
			$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid2' ");
			if($ncount<1)$cmt.=' <a  href="'.$comment.'" target="_blank"><img src='.$cntlevel.' width=20></a> <a href='.$comment.'?replay&speed=100 " target="_blank" ><img src=https://jp.seaicons.com/wp-content/uploads/2016/03/Media-wmp-icon.png width=20></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid2.' " target="_blank" >'.$username->lastname.'</a>&nbsp;|&nbsp;';
			$ncount++;

			if($author->level>$athlevel || $author->level==null)
				{
				$solpick=$comment;
				$authid=$userid2;
				}
			$athlevel=$author->level;
			} 
		if($nhw>0.5)
			{
			$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$authid' ");
 			}   
 		$reply='reply'.$qidtmp;
		$mid='mymodal'.$qidtmp;
		$btntitle='보기';
		$feedback='<td><button type="text" style="background-color:green;color:white;width:100px;" data-toggle="modal" data-target="#'.$reply.'">풀이검색</button></td>';
		if($timediff2/60<10) $mark1='<td><button type="text" style="background-color:red;color:white;width:50px;" data-toggle="modal" data-target="#'.$mid.'">'.$btntitle.'</button></td>';
		else  $mark1='<td><button type="text" style="background-color:skyblue;color:white;width:50px;" data-toggle="modal" data-target="#'.$mid.'">보기</button></td>';


		echo '
		<!-- 질의응답을 위한 의사소통을 위한 modal -->
  		<div class="modal fade"  id="'.$mid.'" role="dialog"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header">
          		<button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">'.$lastname.'의 오답문항</h4></div><div class="modal-body">
		<p>
		'.$value['questiontext'].'</p><p align="right"><button type="button" onclick="askstudent(11,'.$studentid.','.$teacherid.','.$questionid.')">질문의사 확인하기</button></p>
		</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>

		<!-- 풀이 검색 후 응답을 위한 modal -->
  		<div class="modal fade"  id="'.$reply.'" role="dialog"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header">
          		<button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">'.$lastname.'를 위한 피드백 메세지 보내기</h4></div><div class="modal-body">
      		<p>
		'.$value['questiontext'].'<br>이곳에 화이트보드 및 발송 버튼 배치 / (시간 제한 테스트 응시 중일 경우 표시.. 이 경우 팝업이 아닌 일반 메세지로 발송)
		</p>
        		</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>';

		if($timediff2<60*1)$num=1;elseif($timediff2<60*2)$num=2;elseif($timediff2<60*3)$num=3;elseif($timediff2<60*4)$num=4;elseif($timediff2<60*5)$num=5;elseif($timediff2<60*6)$num=6;
		elseif($timediff2<60*7)$num=7;elseif($timediff2<60*8)$num=8;elseif($timediff2<60*9)$num=9;elseif($timediff2<60*10)$num=10;elseif($timediff2<60*11)$num=11;elseif($timediff2<60*12)$num=12;
		elseif($timediff2<60*13)$num=13;elseif($timediff2<60*14)$num=14;elseif($timediff2<60*15)$num=15;elseif($timediff2<60*20)$num=16;elseif($timediff2<60*25)$num=17;elseif($timediff2<60*30)$num=18;
		else $num=19;$today='today'.$num;
			
		$$today.='<tr><td>&nbsp;&nbsp;</td><td><img src="'.$imgtoday.'" width=25></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.' "mo target="_blank" >'.$firstname.$lastname.'</a></td><td>('.round($lastaction/60,0).'분)</td>
		<td>'.$v_quiz.'</td><td>'.$Qnum1.'/'.$mark4.'</td> <td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></td><td>'.round($timediff2/60,0).'분&nbsp;('.$nincorrect.')&nbsp;</a></td> '.$mark1.'<td><input type="checkbox" name="checkAccount" '.$status.' onClick="ChangeCheckBox(3,'.$useridtmp.','.$qidtmp.','.$attemptid.', this.checked)"/></td>
		<td>&nbsp;&nbsp;</td><td>'.$feedback.'</td></tr>';
		$marks10.=$helplist;  
		break;
		} 
	
	}
echo '
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
'; 
 
 echo $today1.$today2.$today3.$today4.$today5.$today6.$today7.$today8.$today9.$today10.$today11.$today12.$today13.$today14.$today15.$today16.$today17.$today18.$today19.'</tbody></table><hr>'.$nowhiteboard.'<hr>'; 
 
/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
echo '</div>
<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
</p></div><div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"><p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>
<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
</div></div></div></div>

	<script>
	function ChangeCheckBox(Eventid,Userid, Questionid, Attemptid, Checkvalue){
	    var checkimsi = 0;
	    if(Checkvalue==true){
	       checkimsi = 1;
 	   }
  	 $.ajax({
  	      url: "check.php",
   	     type: "POST",
   	     dataType: "json",
   	     data : {"userid":Userid,
   	             "questionid":Questionid,
   	             "attemptid":Attemptid,
     	           "checkimsi":checkimsi,
    	             "eventid":Eventid,
    	           },
  	      success: function (data){  
    	    }
	    });
	}

	function AskStudent(Eventid,Studentid,Teacherid,Questionid)
	{
    	$.ajax({
		url:"database.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"studentid":Studentid,
		"teacherid":Teacherid,
		"contentsid":Questionid,       	   
		      },
	 	success:function(data){
		}
	})
	}
	</script> 
  
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
include("quicksidebar.php");
?>