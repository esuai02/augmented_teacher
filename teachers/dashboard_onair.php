

<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
include_once("/home/moodle/public_html/moodle/configwhiteboard.php"); 
global $DB, $USER;

$studentid=required_param('userid', PARAM_INT); 

$period= $_GET["period"];  
$mode= $_GET["mode"];  
if($period==NULL)$period=10;

$stdtname= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$studentname=$stdtname->firstname.$stdtname->lastname;
$timecreated=time();

$aweekago=$timecreated-604800;
$halfdayago=$timecreated-21600;
$adayago=$timecreated-86400;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
$userrole2=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$studentid' AND fieldid='22' "); 
$role2=$userrole2->role;

$autogradeOn=$DB->get_record_sql("SELECT data   FROM mdl_user_info_data where userid='$USER->id' and fieldid='82' ");
$autoGradeState=$autogradeOn->data;
 
if($mode==2) // 오답노트 실험
	{
	$id= $_GET["wboardid"];  
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  wboardid='$id'  ORDER BY tlaststroke DESC LIMIT 1"); 
	}
else 
	{
	$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND tlaststroke >'$aweekago'  ORDER BY tlaststroke DESC LIMIT 1"); 
	$id =$thisboard->wboardid;
	}

$prevPage = $_SERVER["HTTP_REFERER"];

$gidmax = $_GET["gidmax"];
if($gidmax==NULL)$gidmax=10000;
/*
if($gidmax==10000)
	{
 	$switch='발상이전';
	$shownextstrokes='<tr><td><button type="button"   style = "z-index:3; width:80;height:40px;text-align-last:center;" onclick=""><a href="'.$prevPage.'">'.$switch.'</a></button></td></tr>';
	}
else 
	{
	$switch='발상이후';
	$shownextstrokes='<tr><td><button type="button"   style = "z-index:3; width:80;height:40px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=2&wboardid='.$id.'&gidmax=10000">'.$switch.'</a></button></td></tr>';
	}
*/
$teacherid=$thisboard->userto;
$DB->execute("UPDATE {abessi_indicators} SET ninspect=0, tlastview='$timecreated' WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
$timespent=0;
if($thisboard->timemodified<$timecreated-3600)$DB->execute("UPDATE {abessi_indicators} SET timemodified='$timecreated' WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
if($thisboard->timemodified-$thisboard->timecreated>10)$timespent= time()-$thisboard->timemodified;

$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
$ntodo=$engagement3->ntodo;
/*
N=1   새로운 커리큘럼 생성
N=2   분기목표
N=3   주간목표
N=4   오늘목표
N=5   목표개선
N=6   퀴즈분석
N=7   재시도
N=8   발표요청
N=9   답변도착
N=10  학습완료, 복습예약
N=11  고민지점
N=12  요약하기
N=13  주간활동 설계
*/
if($ntodo==1)$tabalert='커리';
elseif($ntodo==2)$tabalert='분기';
elseif($ntodo==3)$tabalert='주간';
elseif($ntodo==4)$tabalert='오늘';
elseif($ntodo==5)$tabalert='개선';
elseif($ntodo==6)$tabalert='분석';
elseif($ntodo==8)$tabalert='발표';
elseif($ntodo==9)$tabalert='답변'; 
elseif($ntodo==11)$tabalert='고민';

$todaygoal= $DB->get_record_sql("SELECT * FROM  mdl_abessi_today WHERE userid='$studentid'  AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
$ratio1=$engagement3->todayscore;	
if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
elseif($ratio1<=100) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus2.png';
else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
 
if($engagement3->todayscore==NULL ||  $engagement3->todayscore==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';

$focused='';
if($mode==1)
	{
	$period=180;
 	}
if($mode==2 || $mode==3)
	{
	$period=600000; 
 	}

echo '<meta http-equiv="refresh" content="'.$period.'">';

$wbmsg=$DB->get_record_sql("SELECT *  FROM mdl_abessi_messages  WHERE wboardid='$id' ORDER BY timecreated DESC LIMIT 1 ");
$notetype='note';
if($wbmsg->contentstitle==='realtime')
	{
	$notetype='attempt';
	$pedagogicaltalk='questionsolving1';
	}
elseif(strpos($id, '0tsDoHfRT')!== false )
	{
	$notetype='prepare';
	$pedagogicaltalk='questionsolving2';
	}
elseif(strpos($id, 'nx4HQkXq')!== false )
	{
	$notetype='exam';
	$pedagogicaltalk='questionsolving3';
	}
elseif($wbmsg->contentstype==1)
	{
	$notetype='topic';
	$topicstatus='개념공부';
	$pedagogicaltalk='topictalk1';
	if(strpos($thisboard->instruction, 'Approach')!==false){$pedagogicaltalk='topictalk1';$topicstatus='개념이해';}
	elseif($thisboard->pagenum==0){$pedagogicaltalk='topictalk2';$topicstatus='개념평가';}
	elseif(strpos($thisboard->instruction, '대표유형')!==false){$pedagogicaltalk='topictalk3';$topicstatus='대표유형';}
	} 
elseif(strpos($id, 'booststep')!== false )$pedagogicaltalk='boosterstep'; 

if($mode==0) $pedagogicaltalk='quickmessage';
	  
$tperiod=$timecreated-604800;
$feedback= $DB->get_record_sql("SELECT * FROM mdl_abessi_feedbacklog WHERE  wboardid='$id' ORDER BY id DESC LIMIT 1"); // 과목정보 가져오기  url 부분 삭제하기
$color1='#F91408';$color2='#F91408';$color3='#F91408';$color4='#F91408';$color5='#F91408';$color6='#F91408';$color7='#F91408';$color8='#F91408';$color9='#F91408';$color10='#F91408';
if($feedback->feedback2!==NULL)$color1='#0572f7';if($feedback->feedback3!==NULL)$color2='#0572f7';if($feedback->feedback4!==NULL)$color3='#0572f7';if($feedback->feedback5!==NULL)$color4='#0572f7';
if($feedback->feedback6!==NULL)$color5='#0572f7';if($feedback->feedback7!==NULL)$color6='#0572f7';if($feedback->feedback8!==NULL)$color7='#0572f7';if($feedback->feedback9!==NULL)$color8='#0572f7';if($feedback->feedback10!==NULL)$color9='#0572f7';
 
$toolong='';
if($timespent>180 && $timecreated-$thisboard->tlaststroke<300)$toolong=' | 총'.round($timespent/60,0).'분';
$timediff=($timecreated-$thisboard->tlaststroke).'"';

if($mode>0)$tabtitle=$tabalert.'★ '.$stdtname->lastname.' '.$timediff.$toolong;
elseif($timecreated-$thisboard->tlaststroke<60)$tabtitle=$tabalert.'●'.$stdtname->lastname.''.$timediff.$toolong;
else $tabtitle=$tabalert.'○ '.$stdtname->lastname.' '.$timediff.$toolong;

$status=$thisboard->status;  
include("status_icons.php");

$thisurl='id='.$id;  //
if(strpos($id, 'jnrsorksqcrark_user')!==false)$thisurl=$thisboard->url;


//$DB->execute("UPDATE {abessi_indicators} SET pagemode=0 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  
if($mode==0) // 간단버전
	{
	// 이부분을 displaymode로 제어하여 깜빡임을 없앨 수 있다.
	//$DB->execute("UPDATE {abessi_indicators} SET pagemode=0 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
	if($role==='student')
		{
		if($timecreated-$thisboard->tlaststroke<60)$livestate=$studentname.' <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png width=100>';
		else $livestate=$studentname.' <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721477001.png width=100>';
		}
	else
		{
		if($timecreated-$thisboard->tlaststroke<60)$livestate='<div  style ="z-index:3;" class="myDiv3"><img src="'.$imgtoday.'" width=25><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank">'.$studentname.'</a>&nbsp; &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=today"><img src="https://mathking.kr/Contents/IMAGES/improveimg.png" width=25></a>&nbsp;  목표 : '.$todaygoal->text.' &nbsp; <a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' "target="_blank"  ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=25></a>&nbsp;  &nbsp; &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png width=100></a></div>';
		else $livestate='<div  style ="z-index:3;" class="myDiv3"> <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0&period=3"><img src="'.$imgtoday.'" width=25><a> <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank">'.$studentname.'</a>&nbsp; &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=today"><img src="https://mathking.kr/Contents/IMAGES/improveimg.png" width=25></a>&nbsp;   목표 : '.$todaygoal->text.'   &nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" ><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=25></a>&nbsp;  &nbsp; &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=1"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721477001.png width=100></a></div>';
		}
	if(time()-$engagement3->tlaststroke<30 && $autoGradeState==='AI') // 새로운 관찰학생 onair 열기 , 닫는 것은 onair page에서 수행
		{
 		//echo ' <script>window.close(); </script>';
		}
	echo '<table align=right><tr><td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$livestate.' </b></td></tr></table>';
	}
if($mode==1 || $mode==2 || $mode==3) // 상세버전
	{
	//if($engagement3->pagemode==1 && $mode==1)header('Location:https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0');
	//$DB->execute("UPDATE {abessi_indicators} SET pagemode=1 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1");
	if($role==='student')
		{
		if($timecreated-$thisboard->tlaststroke<60)$livestate=$studentname.' <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png width=100>';
		else $livestate=$studentname.' <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721477001.png width=100>';
		}
	else
		{
		$aweekAgo=$timecreated-604800;
		$monthsago2=$timecreated-6048000; // 10주 전
		$afewminutesago=$timecreated-10800;
		$handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid'  AND timemodified>'$halfdayago' AND flag=1 AND teacher_check NOT LIKE 2 ORDER BY timemodified DESC LIMIT 20 "); 
		$result1 = json_decode(json_encode($handwriting), True);
		unset($value);
		 
		foreach($result1 as $value) 
			{
			$flags.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$value['wboardid'].'"><img src=https://mathking.kr/Contents/IMAGES/bookmark.png width=15></a> ';
			}

		$stealth=$DB->get_records_sql("SELECT * FROM mdl_question_attempt_steps WHERE  userid='$studentid'  AND timecreated>'$afewminutesago' AND state='complete'  ORDER BY id DESC LIMIT 20 "); 
		$result2 = json_decode(json_encode($stealth), True);
		unset($value2); 
		foreach($result2 as $value2) 
			{
			$stepid=$value2['id'];
			$qstnatmptid=$value2['questionattemptid'];
			$finalresult=$DB->get_record_sql("SELECT * FROM mdl_question_attempt_steps WHERE  questionattemptid='$qstnatmptid'  ORDER BY id DESC LIMIT 1"); 
			if($finalresult->state==='complete')
				{
				$atmptid=$DB->get_record_sql("SELECT * FROM mdl_question_attempt_step_data WHERE  attemptstepid='$stepid' AND name LIKE 'p1' ORDER BY id DESC LIMIT 1 "); 
				$ans=$atmptid->value;
				$atmpt=$DB->get_record_sql("SELECT * FROM mdl_question_attempts  WHERE id='$qstnatmptid' ORDER BY id DESC LIMIT 1 "); 
				$rightanswer=substr($atmpt->rightanswer, 0, strrpos($atmpt->rightanswer, ' '));
				$nslot=$atmpt->slot;
				$atmptboard='Q7MQFA'.$atmpt->questionid.'0tsDoHfRT_user'.$studentid.'_'.date("Y_m_d", time());
				if(strpos($rightanswer,$ans)===false)
					{
 					$assess.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$atmptboard.' ">'.$nslot.'<img style="margin-bottom:4px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658394210.png width=15> | </a> ';
					$checkexit=$DB->get_record_sql("SELECT * FROM mdl_abessi_chat WHERE  wboardid='$atmptboard'  ORDER BY id DESC LIMIT 1"); 
					if($checkexit->id==NULL)$DB->execute("INSERT INTO {abessi_chat} (mode,userid,userto,wboardid,mark,t_trigger) VALUES('retrywb','$studentid','2','$atmptboard','1','$timecreated')");
					}
				}
			}
 

		$bro=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='44' "); 
		$aca=$DB->get_record_sql("SELECT data  FROM mdl_user_info_data where userid='$studentid' AND fieldid='46' "); 
		$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524995001.png';
		if(empty($bro->data)==1 && empty($aca->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png';
		elseif(empty($bro->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624525057001.png';
		elseif(empty($aca->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624525057001.png';
  	 
		if($stayfocused->status==1)$statusmark='○';if($stayfocused->status==2)$statusmark='◎';if($stayfocused->status==3)$statusmark='●';

		$topicnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%jnrsorksqcrark_user%' AND (status LIKE 'complete' OR status LIKE 'review') AND timemodified >'$halfdayago'  ORDER BY timemodified DESC LIMIT 1");
		$questionnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%nx4HQkXq_user%' AND (status LIKE 'complete' OR status LIKE 'review' OR status LIKE 'begintopic') AND timemodified >'$halfdayago'  ORDER BY timemodified DESC LIMIT 1");

		$lastwbUrl2='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$topicnote->wboardid;
		$lastwbUrl3='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$questionnote->wboardid;
		
		$wblist0='<td><a href="'.$lastUrl.'" >'.$statusmark.'</a></td>';
	 	if($topicnote->id==NULL)$wblist0.='<td>□</td>';   
		else $wblist0.='<td><a href="'.$lastwbUrl2.'" >▣</a></td>';
 		if($questionnote->id==NULL)$wblist0.='<td>□</td>';   
		else $wblist0.='<td><a href="'.$lastwbUrl3.'" >▣</a>&nbsp;&nbsp;&nbsp; </td>';
 
		// 화이트보드 목록 가져오기

		$subject=$DB->get_record_sql("SELECT data AS subject FROM mdl_user_info_data where userid='$teacherid' and fieldid='57' ");
		if($subject->subject==='MATH')$contains='%MX%';
		elseif($subject->subject==='SCIENCE') $contains='%SCIENCE%';

		// recent quiz list ********************************************************************************************** 
		 
		$recentquiz = $DB->get_records_sql("SELECT mdl_quiz_attempts.timestart AS timestart, mdl_quiz.name AS name, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades,mdl_course_modules.id AS quizid FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid'   ORDER BY mdl_quiz_attempts.timestart DESC LIMIT 3");
		$quizrslt= json_decode(json_encode($recentquiz), True);
		$quizinfo='';
		 
		unset($value);
		foreach($quizrslt as $value)
			{ 
			$qzid=$value['id'];
			$qzname=$value['name'];
			$qzgrade=round($value['sgrades']/$value['tgrades']*100,0); 
			$stateimg=$value['state'];
			if($value['sgrades']==NULL)
				{
				$stateimg=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$qzid.'"target="_blank" ><b style="color:red;">분석</b><img style="margin-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1622020119001.png" width=18></a>';
				//$DB->execute("UPDATE {abessi_today} SET skip1=NULL, skip2=NULL, skip3=NULL, skip4=NULL, skip5=NULL WHERE userid='$studentid'  AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1");  
				$atmpt=$DB->get_record_sql("SELECT uniqueid FROM mdl_quiz_attempts where id='$qzid' ORDER BY id DESC LIMIT 1    ");
				$maxslot = $DB->get_record_sql("SELECT  slot FROM mdl_question_attempts  INNER JOIN mdl_question_attempt_steps ON mdl_question_attempts.id = mdl_question_attempt_steps.questionattemptid  WHERE mdl_question_attempts.questionusageid='$atmpt->uniqueid'  AND  (mdl_question_attempt_steps.state NOT LIKE 'todo') ORDER BY mdl_question_attempt_steps.id DESC LIMIT 1");
				$skip=$DB->get_records_sql("SELECT * FROM mdl_question_attempts where questionusageid='$atmpt->uniqueid' AND responsesummary IS NULL  AND slot < '$maxslot->slot' ORDER BY id ASC   ");
				$results=json_decode(json_encode($skip),True);
				$nslot=1;
				 
				unset($value);
				foreach($results as $value)
					{
					if($nslot>5)break;
					$questionid=$value['questionid'];  
					$slot=$value['slot'];  
					$wboardid='Q7MQFA'.$questionid.'0tsDoHfRT_user'.$studentid.'_'.date("Y_m_d", time()); 
					$qstnatmptid=$value['id'];
					$finalresult=$DB->get_record_sql("SELECT * FROM mdl_question_attempt_steps WHERE  questionattemptid='$qstnatmptid'  ORDER BY id DESC LIMIT 1"); 
					if($finalresult->state==='todo')$flags.='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=2&wboardid='.$wboardid.'">'.$slot.'<img style="margin-bottom:5px;" src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1658365071.png width=15></a> ';
			 		$nslot++;
					}		
				}
			else $stateimg=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$qzid.'" target="_blank" ><b style="color:red;">분석</b></a>';
 			$quizinfo.='<td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$qzid.' "target="_blank"  >'.substr($qzname,0,20).''.$qzgrade.'점)</a>'.$stateimg.'&nbsp;&nbsp;&nbsp; </td>';
			}
		} 
	if($timecreated-$thisboard->tlaststroke<60)$livestate='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png width=100>';
	else $livestate='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721477001.png width=100>';
	echo '<div  style ="z-index:3;" class="myDiv3"><table><tr><td><span onClick="showMoment2(\''.$studentid.'\')" accesskey="o"><img src="'.$imgtoday.'" width=25></span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank">'.$studentname.'</a>  &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&mode=today"><img src="https://mathking.kr/Contents/IMAGES/improveimg.png" width=25></a>&nbsp; '.$flags.' &nbsp; '.$assess.'</td><td> <span onClick="showMoment(\''.$studentid.'\')" accesskey="m"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627200629001.png" width=25></span></td><td>&nbsp;&nbsp; </td>'.$quizinfo.'  <td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/scaffolding.php?id='.$studentid.'" ><img src='.$editicon.' width=20></a> &nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/print_papertest.php?userid='.$studentid.'&wboardid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'" target="_blank"><img loading="lazy" style="margin-bottom:3px;" src="https://www.mathking.kr/moodle/local/augmented_teacher/IMAGES/printer.png" width=25></a> &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$USER->id.'&userid='.$studentid.'"><img style="margin-bottom:0px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png width=40></a>   &nbsp; </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0" accesskey="r">'.$livestate.'</a></td></tr></table></div>';
	}
 echo 'max='.$maxslot->slot;
$pw = $_GET["pw"]; 
$thispageid = $_GET["cntpageid"];
$cntpageid0 = $_GET["cntpageid"];
$originalid = $_GET["originalid"];
$pageid = $_GET["pageid"];
 
$access= $_GET["access"]; 

$contentsid=0; // 퀴즈와 연동할 때 이 부분에 퀴즈 아이디를 입력한다. 
 
 
$conn = new mysqli($servername, $username, $password, $dbname);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if($stepnum!=0)  $sql = "SELECT * FROM boarddb where encryption_id='$id' AND nstep<='$stepnum' ";
else $sql = "SELECT * FROM boarddb where encryption_id='$id' AND generate_id <'$gidmax' ";
$sql2 = "SELECT * FROM createdb where encryption_id='$id'";
// sql excute;

$rs = mysqli_query($conn, $sql);
$rs2 = mysqli_query($conn, $sql2);

$point=array();
$len=array();
// sql query data binding
while($info = mysqli_fetch_array($rs)){
  array_push($point,$info['shape_data']);
  array_push($len,$info['generate_id']);
}
//$nstroke=count($len);
//$DB->execute("UPDATE {abessi_messages} SET nstroke='$nstroke' WHERE wboardid='$id' ORDER BY id DESC LIMIT 1 ");
 
if($USER->id==$studentid)$DB->execute("UPDATE {abessi_messages} SET timemodified='$timecreated', userto='$USER->id'  WHERE wboardid='$id' ORDER BY id DESC LIMIT 1 ");
$info2 = mysqli_fetch_array($rs2);
$creator=$info2['creator'];          // 창작자 아이디
  

$state = $info2['lockwb'];
$contentsid=$info2['contentsid'];
$contentstype=$info2['contentstype']; 
$boardname = $info2['boardname'];
$boardtype='question';   // url을 보고 이곳 boardtype을 설정하도록 추후 변경
$category = $info2['tag'];
$index_max=max($len);
$test = str_replace( "\"","", $point );
$userfrom=$creator;

echo ' 
 

<script src="https://code.jquery.com/pep/0.4.3/pep.js"></script> 
<style>  
 .myDiv {
  max-width: 40%;
  width:50%
  background-color: white;    
  color: purple;
  text-align: left;
  right: 2%;
  bottom: 3px;
 position:fixed;
 
}

 .myDiv2 {
  max-width: 100%;
  width:100%
  background-color: white;    
  color: purple;
  text-align:center;
  left: 10%;
  top: 5%;
 position:fixed;
 
} 
 .myDiv3 {
  background-color: white;    
  color: purple;
  text-align:center;
  right:0%;
  top: 0%;
  position:fixed;
} 
img {
    max-width: 100%;
    max-height: 100%;
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
      width: 100px; /* Set the width of the sidebar */
      position: fixed; /* Fixed Sidebar (stay in place on scroll) */
      z-index: 2; /* Stay on top */
      top: 0; /* Stay at the top */
      left: 0;
      background-color: white; /* Black */
      overflow-x: hidden; /* Disable horizontal scroll */
      overflow-y: hidden; /* Disable horizontal scroll */
      padding-top:-50px;
    }
 
    #jb {
				width:90%;
				height: 30px;
        position: fixed; /* Fixed Sidebar (stay in place on scroll) */
        top:0%; /* Stay at the top */
        left: 0%;
        z-index: 2; /* Stay on top */
			}

#btn1{ border-top-left-radius: 5px; border-bottom-left-radius: 5px; margin-right:-4px; } 
#btn2{ border-top-right-radius: 5px; border-bottom-right-radius: 5px; margin-left:-3px; } 
#btn_group2 button{ border: 1px solid skyblue; background-color:  rgb(75,0,130); color: skyblue; padding: 5px; } 
#btn_group2 button:hover{ color:white; background-color: skyblue; }
#btn_group button{ border: 1px solid skyblue; background-color: rgb(0,100,155); color: skyblue; padding: 5px; } 
#btn_group button:hover{ color:white; background-color: skyblue; }
  
      canvas {
        border: 0px dashed grey;
        width=100%;
      margin-top:-18px;
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
  font-family: "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", Helvetica, Arial, sans-serif; 
}
 
 
</style>
 

<script type="text/javascript">
	// Optional
	Prism.plugins.NormalizeWhitespace.setDefaults({
		\'remove-trailing\': true,
		\'remove-indent\': true,
		\'left-trim\': true,
		\'right-trim\': true,
	});
	
	// handle links with @href started with \'#\' only
	$(document).on(\'click\', \'a[href^="#"]\', function(e) {
		// target element id
		var id = $(this).attr(\'href\');

		// target element
		var $id = $(id);
		if ($id.length === 0) {
			return;
		}

		// prevent standard hash navigation (avoid blinking in IE)
		e.preventDefault();

		// top position relative to the document
		var pos = $id.offset().top - 80;

		// animated top scrolling
		$(\'body, html\').animate({scrollTop: pos});
	});

function showMoment(Studentid)
	{
	Swal.fire({
	position:"top-end",showCloseButton: true,width:500,
	  html:
	   \'<iframe scrolling="no"  style="border: 1px none; z-index:3; width:400; height:800;  margin-left: 0px;margin-right: 0px;  margin-top: -0px; "  src="https://mathking.kr/moodle/message/index.php?id=\'+Studentid+\'" ></iframe>\',
	  showConfirmButton: false,
  	   })
	}
function showMoment2(Studentid)
	{
	Swal.fire({
	position:"top-end",showCloseButton: true,width:1600,
	  html:
	   \'<iframe style="border: 1px none; z-index:3; width:1550; height:100vh;  margin-left: -10px;margin-right: 0px;  margin-top: -0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=\'+Studentid+\'&tb=604800" ></iframe>\',
	  showConfirmButton: false,
  	   })
	}
</script>
 

 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"  />
<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
  
<link rel="stylesheet" href="../assets/css/ready.min.css">

';
/*


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>


if($role!=='student')
	{
	echo '<script>
 	function delay(ms) {
	    var start = +new Date;
	    while ((+new Date - start) < ms);
	}
	var show_close_alert = true;
	$(window).bind("beforeunload", function() {					 
	var Userid= \''.$studentid.'\';					 
 			 
 	$.ajax({
		url:"check_algorithm.php",
		type: "POST", 
		dataType:"json",
		data : {
		"eventid":\'102\',
		"userid":Userid,
		},
		success:function(data){					 
		 }
	        })
	delay(1000);		 
	});
	</script>';
	}
*/
?>  
  <head>
    <title><?php echo $tabtitle; ?></title>
  <meta http-equiv="refresh" content="<?php echo $sec?>;URL='<?php echo $page?>'">
 
    <script language="JavaScript">

    <script>



      var state = <?php echo $state;?>;
      var unlock = "<?php echo $unlock;?>";
      var db_category = "<?php echo $category;?>";
      var db_boardname = "<?php echo $boardname;?>";
      // console.log(state)

      var textareaList = ["history"];

      function clearText(idOfTextArea) {
        document.getElementById(idOfTextArea).value = "";
      }

      function SaveAsTxt() {
        var fileName = document.getElementById("title").value;
        if (fileName.length == 0) {
          fileName = "image";
        }
        fileName += ".txt";

        var preData = 'version: V0.617a1\n';
        var postData =  preData + document.getElementById("history").value;

        var link = document.createElement("a");
        link.setAttribute("download", fileName);
        link.setAttribute(
          "href",
          "data:" +
            "application/[txt]" +
            ";charset=utf-8," +
            encodeURIComponent(postData)
        );
        link.click();
      }

      function SaveAsJson() {
        console.log("SaveAsJson");
        var fileName = document.getElementById("title").value;
        if (fileName.length == 0) {
          fileName = "imsge";
        }

        fileName += ".json";

        var preData = {'version':'V0.617a1'};
        textareaList.forEach(function(e) {
          preData[e] = document.getElementById(e).value;
        });

        var jsonData = JSON.stringify(preData);

        var link = document.createElement("a");
        var file = new Blob([jsonData], { type: "text/plain" });
        link.href = URL.createObjectURL(file);
        link.download = fileName;
        link.click();
      }

      function isJsonFile(filename) {
        var ridx = filename.lastIndexOf(".");
        var extension = filename.substring(ridx + 1);

        console.log(extension);

        if (extension.length != 4 || extension.toLowerCase() != "json") {
          return false;
        }
        return true;
      }

      function isTextFile(filename) {
        var ridx = filename.lastIndexOf(".");
        var extension = filename.substring(ridx + 1);

        console.log(extension);

        if (extension.length != 3 || extension.toLowerCase() != "txt") {
          return false;
        }
        return true;
      }


      function loadFile() {
        var loadFile = document.getElementById("load_filename");
        var file = loadFile.files[0];
          
        if (!file) {
          return;
        }

        var fileName = document.getElementById("load_filename").value;
        var ridx = fileName.lastIndexOf("\\");

        fileName = fileName.substring(ridx + 1);

        if (isJsonFile(fileName)) {
          LoadJson(file, fileName);
        } else if(isTextFile(fileName)) {
          LoadText(file, fileName);
        } 
      }

      function LoadJson(file, fileName) {
        document.getElementById("title").value = fileName;

        var reader = new FileReader();
        reader.onload = function(e) {
          var contents = e.target.result;
          displayLoadJsonData(contents);
        };
        reader.readAsText(file);
      }

      function displayLoadJsonData(contents) {
        var noteData = JSON.parse(contents);

        var version = noteData['version'];
        console.log(version);
        document.getElementById('history').value = noteData['history'];
        reDrawCanvas();
      }

      function LoadText(file, fileName) {
        document.getElementById("title").value = fileName;

        var reader = new FileReader();
        reader.onload = function(e) {
          var contents = e.target.result;
          displayLoadTextData(contents);
        };
        reader.readAsText(file);
      }

      function displayLoadTextData(contents) {
        var noteData = contents.split('\n');
        var history = "";
         
        noteData.forEach(function (e){
          if (e[0] != 'v') {
            history += e + "\n";
          }
        }); 
        document.getElementById('history').value = history;
        reDrawCanvas();
        
      }
 
      
    </script>

 
  </head>

  <body>
  <html>
      <script language="JavaScript">
      // Date: 2019.04.24

      var textareaList = ["history"];

      function clearText(idOfTextArea) {
        document.getElementById(idOfTextArea).value = "";
      }

      function SaveAsTxt() {
        var fileName = document.getElementById("title").value;
        if (fileName.length == 0) {
          fileName = "image";
        }
        fileName += ".txt";

        var preData = 'version: V0.617a1\n';
        var postData =  preData + document.getElementById("history").value;

        var link = document.createElement("a");
        link.setAttribute("download", fileName);
        link.setAttribute(
          "href",
          "data:" +
            "application/[txt]" +
            ";charset=utf-8," +
            encodeURIComponent(postData)
        );
        link.click();
      }

      function SaveAsJson() {
        console.log("SaveAsJson");
        var fileName = document.getElementById("title").value;
        if (fileName.length == 0) {
          fileName = "imsge";
        }

        fileName += ".json";

        var preData = {'version':'V0.617a1'};
        textareaList.forEach(function(e) {
          preData[e] = document.getElementById(e).value;
        });

        var jsonData = JSON.stringify(preData);

        var link = document.createElement("a");
        var file = new Blob([jsonData], { type: "text/plain" });
        link.href = URL.createObjectURL(file);
        link.download = fileName;
        link.click();
      }

      function isJsonFile(filename) {
        var ridx = filename.lastIndexOf(".");
        var extension = filename.substring(ridx + 1);

        console.log(extension);

        if (extension.length != 4 || extension.toLowerCase() != "json") {
          return false;
        }
        return true;
      }

      function isTextFile(filename) {
        var ridx = filename.lastIndexOf(".");
        var extension = filename.substring(ridx + 1);

        console.log(extension);

        if (extension.length != 3 || extension.toLowerCase() != "txt") {
          return false;
        }
        return true;
      }


      function loadFile() {
        var loadFile = document.getElementById("load_filename");
        var file = loadFile.files[0];
          
        if (!file) {
          return;
        }

        var fileName = document.getElementById("load_filename").value;
        var ridx = fileName.lastIndexOf("\\");

        fileName = fileName.substring(ridx + 1);

        if (isJsonFile(fileName)) {
          LoadJson(file, fileName);
        } else if(isTextFile(fileName)) {
          LoadText(file, fileName);
        } 
      }

      function LoadJson(file, fileName) {
        document.getElementById("title").value = fileName;

        var reader = new FileReader();
        reader.onload = function(e) {
          var contents = e.target.result;
          displayLoadJsonData(contents);
        };
        reader.readAsText(file);
      }

      function displayLoadJsonData(contents) {
        var noteData = JSON.parse(contents);

        var version = noteData['version'];
        console.log(version);
        document.getElementById('history').value = noteData['history'];
        reDrawCanvas();
      }

      function LoadText(file, fileName) {
        document.getElementById("title").value = fileName;

        var reader = new FileReader();
        reader.onload = function(e) {
          var contents = e.target.result;
          displayLoadTextData(contents);
        };
        reader.readAsText(file);
      }

      function displayLoadTextData(contents) {
        var noteData = contents.split('\n');
        var history = "";
         
        noteData.forEach(function (e){
          if (e[0] != 'v') {
            history += e + "\n";
          }
        }); 
        document.getElementById('history').value = history;
        reDrawCanvas();
      }
    
    </script>
  </head>
<!--이곳에 소요시간 기반 알고리즘 적용하기 if(counter>300)... 랜덤함수. swal 띄우기.. check_status.. 303 이 기록..-->
  <body >
    <div class="jb_table" id = "test123">
      <div class="row">
        <span class="cell" width="88">
          <div>
            <div class="sidenav">
             <div align="center" class="row"> 
<span class="cell">
<?php  

// 이모지 적용  
$emoid=$thisboard->emoji;
$tidEmoji=$thisboard->userto;
  
$exist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE wboardid='$id'  ORDER BY id DESC LIMIT 1  ");

if($exist->id!=NULL) $contentsready=$teacherpic; // 실시간 지도 
elseif(strpos($id, 'fromT')!== false)$contentsready=' <img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617510928001.png" width=88>'; // 복사하여 질문하기
elseif($thisboard->present==1)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$id.'&tb=43200"><img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617238913001.png" width=88></a>'; // 발표평가
elseif($thisboard->present==2)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$id.'&tb=43200"><img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617272891001.png" width=88></a>'; // 발표완료
elseif(strpos($id, 'hint')!== false)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$creator.'&tb=43200"  target="_blank"><img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1612030169001.png" width=88></a>'; // 힌트 아이콘
elseif(strpos($contentstitle, 'realtime')!== false)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$creator.'&tb=43200"  target="_blank"><img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1607272181001.png" width=88></a>'; // 풀이노트
elseif(strpos($id, 'jnrsorksqcrark')!== false)
	{
	if(strpos($id, 'user')!== true)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1615326239001.png" width=88>'; //아인슈타인, 선생님 답변
	elseif($stepnum==1)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267504001.png" width=88>';
	elseif($stepnum==2)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267580001.png" width=88>';
	elseif($stepnum==3)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267624001.png" width=88>';
	elseif($stepnum==4)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267660001.png" width=88>';
	elseif($stepnum==5)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267697001.png" width=88>';
	elseif($stepnum==6)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267740001.png" width=88>';
	elseif($stepnum==7)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614267776001.png" width=88>';
	elseif($thispageid!=NULL) $contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614484737001.png" width=88>';
	else $contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1614268374001.png" width=88>';
	}
elseif(strpos($id, 'tsDoHfRT')!== false)
	{
	if(strpos($id, 'user')!==false)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id=OVc4lRh'.$contentsid.'nx4HQkXq'.$creator.'" ><img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1607272144001.png" width=88></a>'; // 평가준비
	else $contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1615326239001.png" width=88>';
	}
elseif(strpos($contentstitle, 'incorrect')!== false && strpos($id, 'nx4HQkXq')!== false)$contentsready='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$creator.'&tb=43200"  target="_blank"><img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1607272110001.png" width=88></a>'; // 서술평가
// 선생님별 이모지     
 
if($thisboard->status==='reply' ||$thisboard->status==='solutionreply' ||$thisboard->status==='acceleration' ||$thisboard->status==='retry')
	{
	if($emoid==NULL)$contentsready='<img align="middle" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1615326239001.png" width=88>'; 
       	elseif($thisboard->emotype==0)$contentsready='<img align="middle" src="https://mathking.kr/Contents/IMAGES/emoji/'.$tidEmoji.'/'.$emoid.'.gif" width=88>';  
	elseif($thisboard->emotype>0)$contentsready='<img align="middle" src="https://mathking.kr/Contents/IMAGES/EMOJI/'.$emoid.'.gif" width=88>';
	}
else $DB->execute("UPDATE {abessi_messages} SET  emotype=0 WHERE  wboardid='$id' ");
//$contentsready;
echo $statusimg; 
echo '</span></div></span>';

//if($USER->id==2)include("AItrigger.php"); // AI trigger


if($role==='student')
	{
	 echo '<div align="center" class="row"> <span class="cell"  style="color:white;"><a align=center href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800" target=_blank"><?php echo $cfirstname.$clastname; ?></a></span></div>
    	    <table align="center" style="width:100%;">'.$shownextstrokes.'</table><br> ';
	}
 elseif($mode==1 ||  $mode==2)
	{
	$realtimewb=substr($id, 0, strpos($id, '_user')); // 문자 이후 삭제
	$realtimewb=str_replace("_user","",$realtimewb);

	$instance=$DB->get_records_sql("SELECT * FROM mdl_tag_instance  WHERE itemid='$contentsid'  ");
	$tags= json_decode(json_encode($instance), True);   
	unset($value2); 
	foreach($tags as $value2)
		{
		$tagid=$value2['tagid'];
		$tag=$DB->get_record_sql("SELECT * FROM mdl_tag WHERE id='$tagid' ORDER BY id DESC LIMIT 1 ");
		$tagname=$tag->name;	
		if(strpos($tagname, 'mx')!==false)$mathnoteId=$tag->contents;
		if($tagid>=120 && $tagid<=136)
			{
			$domainboardid=$tag->contents;
			$ndomain=$tagid-119;
			}
		}
	$notetext='개념노트';
	if(strpos($id, 'jnrsorksqcrark')!== false)
		{
		$bessiboard='cjnNote'.$realtimewb;
		$bessiboard2='cjnInterpret'.$realtimewb;
		}
	else 
		{
		$bessiboard='cjnNote'.$contentsid.'type2';
		$bessiboard2='cjnInterpret'.$contentsid.'type2';
		}

	if($thisboard->tracking==1) $trackingbutton= '<tr><td><button type="button"  style = "z-index:3;font-size:16; background-color:red;color:white;width:80;height:30px;text-align-last:center;" onclick="SetTracking(2,\''.$studentid.'\',\''.$id.'\')">추적완료</button></td></tr>'; 
	else  $trackingbutton= '<tr><td><button type="button"  style = "z-index:3;font-size:16;background-color:#035afc;color:white;width:80;height:30px;text-align-last:center;" onclick="SetTracking(1,\''.$studentid.'\',\''.$id.'\')">인지추적</button></td></tr>'; 
	$hoursago3=time()-10800;
	$cexist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE userid='$studentid' AND forced=1 AND timemodified > '$hoursago3' ORDER BY timemodified DESC LIMIT 1    ");
	if($cexist->id==NULL)
		{
		$comecolor='';  
		$ceventid=2;
		}
	else 	{
		$comecolor='red'; 
		$ceventid=22;
		}
	$airexist=$DB->get_record_sql("SELECT *  FROM mdl_abessi_feedbacklog WHERE userid='$studentid' AND forced=2 AND timemodified > '$hoursago3' ORDER BY timemodified DESC LIMIT 1    ");
	if($airexist->id==NULL)
		{
		$aircolor='';  
		$oeventid=222;
		}
	else 	{
		$aircolor='red'; 
		$oeventid=22;
		}
	 echo '<div align="center" class="row"> <span class="cell"  style="color:white;"><a align=center href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800" target=_blank"><?php echo $cfirstname.$clastname; ?></a></span></div>
    	    <table align="center" style="width:100%;"><tr><td><img id = "alert_pen" height="80"  width="80" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1579570583.png"  onclick="selectTool(\'pencil\')" /></td></tr>
	    <tr><td><select name="linewidth" id = "linewidth" style = "width:80;height=80;text-align-last:center;">
                  <option value="1" selected="selected">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  </select></td></tr>
	    <tr><td><img align="middle" id = "alert_eraser" height="80" width="80" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1579570654.png" onclick="selectTool(\'eraser\')"/></td></tr>
                  <tr><td><select name="eraserwidth" id = "eraserwidth" style = "width:80;height=80;text-align-last:center;">
                  <option value="10">1</option>
                  <option value="30">2</option>
                  <option value="50" selected="selected">3</option>
                  <option value="70">4</option>
                  <option value="400">5</option>
                  </select></td></tr>
	    <tr><td><button type="button"  id="alert_demo_reset" style = "z-index:3; width:80;height:30px;text-align-last:center;" onclick="">삭제</button></td></tr>
	    <tr><td><button type="button"  id="alert_refresh" style = "z-index:3; width:80;height:30px;text-align-last:center;" onclick="beginchat(201,0,\''.$studentid.'\',\''.$teacherid.'\',\''.$id.'\')" accesskey="1">전달</button></td></tr>
  	    <tr><td><button type="button"  id="alert_refresh" style = "background-color:'.$comecolor.';color:black;z-index:3; width:80;height:30px;text-align-last:center;" onclick="Realtime(\''.$ceventid.'\',\'강제지도\',\'3분간 실시간 지도가 진행됩니다.\',\''.$studentid.'\',\''.$USER->id.'\',\''.$contentstype.'\',\''.$contentsid.'\',\''.$contextid.'\',\''.$currenturl.'\',\''.$id.'\')" accesskey="2">호출</button></td></tr>
 	    <tr><td><button type="button"  id="alert_refresh" style = "background-color:'.$aircolor.';color:black;z-index:3; width:80;height:30px;text-align-last:center;" onclick="Realtime(\''.$oeventid.'\',\'강제지도\',\'3분간 실시간 지도가 진행됩니다.\',\''.$studentid.'\',\''.$USER->id.'\',\''.$contentstype.'\',\''.$contentsid.'\',\''.$contextid.'\',\''.$currenturl.'\',\''.$id.'\')" accesskey="2">ONAIR</button></td></tr>

  	 
	   '.$shownextstrokes.'
	   <tr><td><button type="button"  id="'.$pedagogicaltalk.'" style = "background-color:#31bd56;color:white;width:80;height:30px"  onclick="" >인지촉진</button></td></tr>'. $trackingbutton.'
	   <td><button type="button"  style = "background-color:#035afc; width:80;height:30px"><a style="color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/replay.php?id='.$id.'&speed=+9" target="_blank">풀이관찰</a></button></td></tr>
	   </table><br>';

	// 페이지 유형별 초반 중반 후반 완료후 및 정체상황 식별 후 trigger 방식
	$feedback1=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=1 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  // 데이터 제시 *
 	$feedback2=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=2 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  // 접근방법 *
	$feedback3=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=3 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  // 전체풀이 *
	$feedback4=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=4 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //사고확장 *
	$feedback5=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=5 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //논리촉진 - contents bank
	$feedback6=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=6 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //오답원인 
	$feedback7=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=7 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //복습지도 - contents bank
	$feedback8=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=8 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //토픽맵 *
	$feedback9=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=9 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //생각계단 - contents bank
	$feedback10=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=10 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //유형맵 - contents bank
	$feedback11=$DB->get_records_sql("SELECT * FROM mdl_abessi_instruction  WHERE feedbacktype=11 AND contentsid='$contentsid' AND contentstype='$contentstype' ");  //음성해설 *
	// 12 AI 추천 
	// 13 생각계단 (단계별 풀이)

	$btncolor1='#f2f3f5';  $btncolor2='#f2f3f5';  $btncolor3='#f2f3f5';  $btncolor4='#b8ffb8';  $btncolor5='#b8ffb8';  $btncolor6='#b8ffb8';  $btncolor7='#ffb8b8';  
	$btncolor8='#f2f3f5';  $btncolor9='#f2f3f5';  $btncolor10='#f2f3f5';  $btncolor11='#f2f3f5';  $btncolor12='#f2f3f5';  $btncolor13='#f2f3f5';  
	if(count($feedback1)>0)$btncolor1='#c4f5ff';  if(count($feedback2)>0)$btncolor2='#c4f5ff';  if(count($feedback3)>0)$btncolor3='#c4f5ff';  if(count($feedback4)>0)$btncolor4='#f9c7ff';  if(count($feedback5)>0)$btncolor5='#f9c7ff';  if(count($feedback6)>0)$btncolor6='#f9c7ff';  if($mathnoteId!=NULL)$btncolor7='#c4f5ff';  
	if(count($feedback8)>0)$btncolor8='#c4f5ff';  if(count($feedback9)>0)$btncolor9='#c4f5ff';  if(count($feedback10)>0)$btncolor10='#c4f5ff';  if(count($feedback11)>0)$btncolor11='#c4f5ff';  

//$DB->execute("UPDATE {abessi_messages} SET mark=1,nstep=nstep+1, tracking=1, nfeedback=nfeedback+1,teacher_check='1', status='reply', timemodified='$timecreated' WHERE  userid='$userid' AND wboardid='$wboardid'   ORDER BY timecreated DESC LIMIT 1 ");

	echo '<table align="center" style="width:100%;">';


	$currenturl='contentsid='.$contentsid.'&contentstype='.$contentstype.'&wboardid='.$id.'&studentid='.$studentid;

 	if($thisboard->contentstitle==='realtime') // 풀이노트
		{	
		echo '<tr><td><button type="button"  style="background-color:#f4cccc;color:white;width:80px;height:50px;"><a style="background-color:#f4cccc;color: style="background-color:#f4cccc;color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$thisurl.'">풀이노트</a></button></td></tr>';	
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard2.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">문제설명</a></button></td></tr>'; //키워드로 제목입력
		//echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor11.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',11)">문제해설</button></td></tr>';	
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor2.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',2)">접근방법</button></td></tr>'; 
		 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor5.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',5)">논리부스터</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor6.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',6)">풀이유형+</button></td></tr>'; 
 		
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/mod/page/view.php?id=73367"target="_blank">개념추가</a></button></td></tr>'; //개념추가
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">풀이해설</a></button></td></tr>'; //키워드로 제목입력
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories(\''.$currenturl.'\',7)">개념노트</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories2(\''.$currenturl.'\',7)">기억촉진</button></td></tr>';
		 }
	elseif(strpos($id, 'tsDoHfRT')!==false && $thisboard->contentstitle==='incorrect')    //평가준비
		{		
		echo '<tr><td><button type="button"  style="background-color:#068b06;color:white;width:80px;height:50px;"><a style="background-color:#068b06;color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$thisurl.'">평가준비</a></button></td></tr>';	

 		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor2.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',2)">접근방법</button></td></tr>';
		
		 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor5.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',5)">논리부스터</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor6.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',6)">풀이유형+</button></td></tr>'; 
 		 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/mod/page/view.php?id=73367"target="_blank">개념추가</a></button></td></tr>'; //개념추가
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories(\''.$currenturl.'\',7)">개념노트</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories2(\''.$currenturl.'\',7)">기억촉진</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">풀이해설</a></button></td></tr>'; //키워드로 제목입력
		//echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',3)">풀이해설</button></td></tr>'; //키워드로 제목입력
		 
		//echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor10.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',10)">유형맵T</button></td></tr>';	
		}
	elseif(strpos($id, 'nx4HQkXq')!== false ) // 서술평가
		{	
		echo '<tr><td><button type="button"  style="background-color:#fc2d2d;color:white;width:80px;height:50px;"><a style="background-color:#fc2d2d;color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?'.$thisurl.'">서술평가</a></button></td></tr>';	
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor5.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',5)">논리부스터</button></td></tr>'; 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor6.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',6)">풀이유형+</button></td></tr>'; 
 		 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/mod/page/view.php?id=73367"target="_blank">개념추가</a></button></td></tr>'; //개념추가
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories(\''.$currenturl.'\',7)">개념노트</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories2(\''.$currenturl.'\',7)">기억촉진</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">풀이해설</a></button></td></tr>'; //키워드로 제목입력
		}
	elseif($thisboard->contentstype==1) // 개념노트
		{ 	
		echo '<tr><td><button type="button"  style="background-color:#000000;color:white;width:80px;height:50px;"><a style="background-color:#000000;color:white;" href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url.'">'.$topicstatus.'</a></button></td></tr>';	
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard2.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">학습목표</a></button></td></tr>'; //키워드로 제목입력	 
		 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor5.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',5)">논리부스터</button></td></tr>'; 
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor6.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',6)">풀이유형+</button></td></tr>'; 
 		
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/mod/page/view.php?id=73367"target="_blank">개념추가</a></button></td></tr>'; //개념추가
 		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories(\''.$currenturl.'\',7)">개념노트</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor7.';width:80;height:30px;text-align-last:center;" onClick="ConnectMemories2(\''.$currenturl.'\',7)">기억촉진</button></td></tr>';
		echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor3.';width:80;height:30px;text-align-last:center;" onclick=""><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id='.$bessiboard.'&srcid='.$id.'&contentsid='.$contentsid.'&contentstype='.$contentstype.'&studentid='.$studentid.'"target="_blank">개념해설</a></button></td></tr>'; //키워드로 제목입력
		//echo '<tr><td><button type="button"  id="  " style = "z-index:3;font-size:12; background-color:'.$btncolor8.';width:80;height:30px;text-align-last:center;" onclick="giveFeedback(\''.$currenturl.'\',8)">토픽맵전달</button></td></tr>';   
		}
 
	echo '<tr><td><button type="button"  id="alert_nextpage" style="background-color:#4287f5;color:white;width:80px;height:30px;">NEXT</button></td></tr></table>';
  	}
 
	if($airexist->id!=NULL)
		{
		 echo '
		    <script src="https://code.jquery.com/pep/0.4.3/pep.js"></script>
		    <script type="module" src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/index.js"></script>
		    <video id="localVideo" autoplay="true" muted="muted"></video>
		    <video id="remoteVideo" autoplay="true" style="display:none"></video>
		    	<script src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/dist/RTCMultiConnection.min.js"></script>
 		   	<script src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/node_modules/webrtc-adapter/out/adapter.js"></script>
  		  	<script src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/node_modules/socket.io/client-dist/socket.io.js"></script>

			<script>
	 
		 	  var chatroomid= \''.$id.'\';
		                connection = new RTCMultiConnection();
 		               connection.socketURL = \'https://mathking.kr:9559/\';
  		              connection.socketMessageEvent = \'audio-conference-demo\';

  		              connection.session = {
     		           		    audio: true,
    		             		   video: false
      		        		  };

       		         connection.mediaConstraints = {
              				      audio: true,
               				     video: false
             					   };

                		connection.sdpConstraints.mandatory = {
               				     OfferToReceiveAudio: true,
                				    OfferToReceiveVideo: false
                				};

           		     // https://www.rtcmulticonnection.org/docs/iceServers/
           		     // use your own TURN-server here!
           		     connection.iceServers = [{
             		       \'urls\': [
              		          \'stun:stun.l.google.com:19302\',
                		        \'stun:stun1.l.google.com:19302\',
                 		       \'stun:stun2.l.google.com:19302\',
                 		       \'stun:stun.l.google.com:19302?transport=udp\',
               			     ]
           				     }];

          		      connection.audiosContainer = document.getElementById(\'audios-container\');
          		      connection.onstream = function(event)
				 {
                  				  var width = parseInt(connection.audiosContainer.clientWidth / 2) - 20;
                				    var mediaElement = getHTMLMediaElement(event.mediaElement, {
                				        title: event.userid,
                 				       buttons: [\'full-screen\'],
                 		 		      width: width,
                 		 		      showOnMouseEnter: false
                				    });
                				    connection.audiosContainer.appendChild(mediaElement);

               		   		  setTimeout(function() {
               		  		       mediaElement.media.play();
                		 		   }, 5000);

              		   		   mediaElement.id = event.streamid;
             		 		  };

              		  connection.onstreamended = function(event) 
				{
                   			   var mediaElement = document.getElementById(event.streamid);
                 			   if (mediaElement) {
                 		   		    mediaElement.parentNode.removeChild(mediaElement);
                 					}
 	     		          };

           		     // ktm 방 만들기 혹은 참가
           		     connection.openOrJoin(chatroomid, function(isRoomExist, roomid)
			 	{
                			   console.log(isRoomExist, roomid);
                			   btnElement.innerText = \'연결해제\';
                  			   btnElement.disabled = false;
               			 });  
			</script>
		   <script src="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/webRtc.js"></script>
 		  <input type="hidden" id="copyInput" />'; 

		}

	echo '
	<script>
	function SetTracking(Eventid,Userid,Wboardid){
	
		swal("적용되었습니다.", {buttons: false,timer: 500,});
		   $.ajax({
		        url: "check.php",
		        type: "POST",
		        dataType: "json",
		        data : {
			    "eventid":Eventid,
			    "userid":Userid,
			    "wboardid":Wboardid,    		       
 		             },
		            success:function(data){
			            }
		        });
		 
		location.reload();
		 
		}

	function Realtime(Eventid,Feedbacktype,Text,Userid,Tutorid,Contentstype,Contentsid,Contextid,Currenturl,Wboardid)
	{
		$.ajax({
		url:"check_status.php",
		type: "POST",
		dataType:"json",
 		data : {
		"eventid":Eventid,
		"feedbacktype":Feedbacktype,
		"inputtext":Text,	
		"userid":Userid,
		"tutorid":Tutorid,
		"contentstype":Contentstype,
		"contentsid":Contentsid,
		"contextid":Contextid,
		"currenturl":Currenturl,
		"wboardid":Wboardid,
		},
		success:function(data){
		 }
		 })
		setTimeout(function(){
		location.reload();
		},1000);  
	}
 
	function giveFeedback(Currenturl,Fbtype)
		{
		Swal.fire({
		backdrop: true,position:"top-right",showCloseButton: true,width:1200,
		  html:
		    \'<iframe style="border: 1px none; z-index:3; width:1000; height:1200;  margin-left: 0px; margin-top: -0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/cognitiveFeedback.php?\'+Currenturl+\'&fbtype=\'+Fbtype+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}	
	function ConnectMemories(Currenturl,Fbtype)
		{
		Swal.fire({
		backdrop: false,position:"top-right",showCloseButton: true,width:1000,
		  html:                                                                                                                                                                                                                                         
		    \'<iframe style="border: 1px none; z-index:3; width:50vw;height:90vh; margin-left: -30px;margin-top: 0px;"   src="https://mathking.kr/moodle/local/augmented_teacher/students/connectmemories.php?wboardid='.$id.'&domain='.$ndomain.'&studentid='.$studentid.'&checklist='.$mathnoteId.'&contentstype='.$contentstype.'&contentsid='.$contentsid.'&cmid='.$cmid.'\' +Currenturl+\'&fbtype=\'+Fbtype+\'"></iframe>\',
		        })
		}
	function ConnectMemories2(Currenturl,Fbtype)
		{
		Swal.fire({
		backdrop: false,position:"top-right",showCloseButton: true,width:1000,
		  html:                                                                                                                                                                                                                                         
		    \'<iframe style="border: 1px none; z-index:3; width:50vw;height:90vh; margin-left: -30px;margin-top: 0px;"   src="https://mathking.kr/moodle/local/augmented_teacher/students/connectboosters.php?wboardid='.$id.'&domain='.$ndomain.'&studentid='.$studentid.'&checklist='.$mathnoteId.'&contentstype='.$contentstype.'&contentsid='.$contentsid.'&cmid='.$cmid.'\' +Currenturl+\'&fbtype=\'+Fbtype+\'"></iframe>\',
		        })
		}
	</script>';


if($role!=='student')include("../teachers/shortcuts.php");
 
include("styleFeedback.php");

?>
                </span> 
              </div>
            </div>
          </div>
        </span>
<div  style ="z-index:3;" class="myDiv2"> <table align=left width=100%>
<tr><td></td></tr>
</table></div>
<div  style ="z-index:3;" class="myDiv">  
<tr><td><span style="font-size:14px;text-align:left;color:<?php echo $color1?>"><?php echo $thisboard->instruction;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color1?>"><?php echo $feedback->feedback1;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color2?>"><?php echo $feedback->feedback2;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color3?>"><?php echo $feedback->feedback3;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color4?>"><?php echo $feedback->feedback4;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color5?>"><?php echo $feedback->feedback5;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color6?>"><?php echo $feedback->feedback6;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color7?>"><?php echo $feedback->feedback7;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color8?>"><?php echo $feedback->feedback8;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color9?>"><?php echo $feedback->feedback9;?> </span></td></tr>
<tr><td><span style="font-size:14px;color:<?php echo $color10?>"><?php echo $feedback->feedback10;?> </span></td></tr>
</table><br><br><br></div>
        <span class="cell">
          <div id="wboard">
	 <meta name="viewport" content="width=device-width, initial-scale=1">
          <canvas id="canvas"  width=2000 height="6000" touch-action="none"></canvas>
          <canvas id="canvas2" style="z-index:1" width=2000 height="6000" touch-action="none"></canvas>
       </div>
        </span>
        <span class="cell">
          <div>
            <textarea id="history" cols="40" rows="37" style="display: none;"></textarea>
          </div>
          </div>
        </span>
      </div>
    </div>
<script>var nstep = "<?php echo $stepnum;?>";</script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>-->
    <script src="painter_test.php"></script>
    <script src="drawengine_test.js"></script>
    <script > 
    var testid = "<?php echo $id;?>";
    var points1 = <?= json_encode($test) ?>;
    var index_len = Number(<?= json_encode($index_max) ?>);
//    var recommend =  "<?php echo $recommend;?>";
 //   var nstep = "<?php echo $nstep;?>";
    // console.log(index_len)
    var points = new Array;
    var points2 = new Array;
    var points3 = new Array;
    var regExp = /\[/g;
    var regExp2 = /\]/g;
    for(i=0;i<points1.length;i++)
    {
      points1[i]=points1[i].replace(regExp, "");
      points.push(points1[i].replace(regExp2, ""));
      points2=points[i].split(",");
      for(a=0; a<points2.length; a++)
      {
        if(a==points2.legth)
        {
          points3 = points3+points2[a]
        }
        else{
        points3 = points3+points2[a]+'\n'
        }
      }
      points2 = [];
      // points = points1[2].replace(/[{}]/g, "");
    }
    // console.log(points3)
    document.getElementById('history').value=points3;
    

    reDrawCanvas();
    var newColor = drwaCommand();
    newColor.mode = "color";
    newColor.color = "black";
    color_state=commandHistory[commandHistory.length-2]
    if(color_state=="color black"){

    }
    else{
      commandHistory.push(newColor.toCommand());
      addHistory(newColor.toCommand());
      var data = new Array();
      data.push(newColor.toCommand());
      index_len+=1;
      $.ajax({
      type: 'POST',
      url: './dbsend.php',
      data : {id:testid,data:data,nstep:nstep,index:index_len},     
      dataType: 'json'
    });
    }
    // var s = document.getElementById("speed");
    // var speed = s.options[s.selectedIndex].value;
    jQuery('#speed').change(function() {
      speed = jQuery('#speed option:selected').val();
  });
  function CopyUrlToClipboard()
	{	
	obShareUrl.value =  "<?php echo $id;?>";  	 
	obShareUrl.select();  // 해당 값이 선택되도록 select() 합니다
	document.execCommand("copy"); // 클립보드에 복사합니다.
	}
<?php
if($mode==1 || $mode==2) 
{
 

echo ' 
$(\'#alert_nextpage\').click(function(e) {
				var Username= \''.$studentname.'\';
				var Userid= \''.$studentid.'\'; 
				var Username;
				var Fbtype;
				var Fbgoal;
				var Fbtext;
				var Fburl;
				var Prepareimg;
				var Summary;
				var Source=1;
              			 $.ajax({
					url: "../whiteboard/almtyroutine.php",
					type: "POST",
					dataType:"json",
              				data : {	 
				        	"userid":Userid,
					"source":Source,
               			        	}, 
                				success:function(data) 
						{
						
						Username=data.username;
						Fbtype=data.fbtype;
						Fbgoal=data.fbgoal;
						Fbtext=data.fbtext;
						Fburl=data.fburl;	
						Prepareimg=data.prepareimg;
						Summary=data.summary;	
						 
						swal({
								title: Username+\'의 \' + Fbtype ,
								text: Fbtext,
								type: \'warning\',
								buttons:{
									confirm: {
										text : \'NEXT\',
										className : \'btn btn-success\'
									},
						   			

								}
							}).then((willDelete) => {
								if (willDelete) {
						 
								 window.location.href =Fburl;	 					 
								}
							});
						}
            	   		  	      });
			}); 
$(\'#alert_demo_reset\').click(function(e) {

				var Wboardid= \''.$id.'\'; 
				var Userid= \''.$studentid.'\'; 
				swal({
					title: \'필기 내용을 삭제하시겠습니까 ?\',
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
						url:"delete.php",
						type: "POST",
						dataType:"json",
					 	data : {
						"wboardid":Wboardid,
						"userid":Userid,	
					 		},
						 });
					location.reload();					 
					} else {
						swal("취소되었습니다.", {
							buttons : {
								confirm : {
									className: \'btn btn-success\'
								}
							}
						});
					}
				});
			});
   
			$(\'#alert_pen\').click(function(e) {
				swal("펜이 선택되었습니다.", {
					buttons: false,
					timer: 1000,
				});
			});
			$(\'#alert_eraser\').click(function(e) {
				swal("지우개가 선택되었습니다.", {
					buttons: false,
					timer: 1000,
				});
			});
			$(\'#alert_refresh\').click(function(e) {
				swal("내용이 전달되었습니다.", "이후 학습을 관찰해 주세요", {
					buttons: {        			
						confirm: {
							className : \'btn btn-success\'
						}
					},
					
				});
			location.reload();
			});
 
 $(\'#boosterstep\').click(function(e) {
					var Username= \''.$studentname.'\';
					var Userid= \''.$creator.'\';
					var Tutorid= \''.$teacherid.'\';
					var Contentstype= \''.$contentstype.'\';
					var Contentsid= \''.$contentsid.'\';
					var Contentstype0= \''.$contentstype0.'\';
					var Contentsid0= \''.$contentsid0.'\';
					var Contextid= \''.$contextid.'\';
					var Currenturl= \''.$currenturl.'\'; 
					var Wboardid= \''.$id.'\'; 

					var text1="강조하고자 하는 부분이 무엇인지 설명해 주세요";
					var text2="논리적인 단계가 적절하게 표현되도록 보충해 주세요";
					var text3="쓰여진 논리의 제목을 정해 써 보세요.";
					var text4="쓰여진 내용을 좀 더 자세하게 설명해 주세요.";
					var text5="사용된 논리를 천천히 느끼면서 3회 반복해서 써보세요.";
					var text6="이전 논리, 현재 논리, 다음 논리로 분리하여 작성해 주세요";
					var text7="사용된 논리가 얼마나 자주 반복해서 사용되는지 생각해 보세요";
					var text8="사용된 논리를 논리적인 순서를 지키며 5번 정도 반복해 보세요";
					var text9="사용된 논리를 현재까지 몇 번정도 생각한 적이 있는지 써 봅시다";
					var text10="순서도를 그려서 논리적인 순서를 표현해 보세요";

			swal(Username + " Booster Step",  "부스터 스텝 활동을 촉진시켜 주세요",{
				
			  buttons: {
			    catch1: {
			      text: "강조하고자 하는 부분이 무엇인지 설명해 주세요",
			      value: "catch1",className : \'btn btn-primary\'
				
			    },
			    catch2: {
			      text: "논리적인 단계가 적절하게 표현되도록 보충해 주세요.",
			      value: "catch2",className : \'btn btn-primary\'
			    },
			    catch3: {
			      text: "쓰여진 논리의 제목을 정해 써 보세요.",
			      value: "catch3",className : \'btn btn-primary\'
			    },
			    catch4: {
			      text: "쓰여진 내용을 좀 더 자세하게 설명해 주세요.",
			      value: "catch4",className : \'btn btn-primary\'
			    },
			    catch5: {
			      text: "사용된 논리를 천천히 느끼면서 3회 반복해서 써보세요.",
			      value: "catch5",className : \'btn btn-primary\'
			    },
			    catch6: {
			      text: "이전 논리, 현재 논리, 다음 논리로 분리하여 작성해 주세요",
			      value: "catch6",className : \'btn btn-primary\'
			    },
			    catch7: {
			      text: "사용된 논리가 얼마나 자주 반복해서 사용되는지 생각해 보세요",
			      value: "catch7",className : \'btn btn-primary\'
			    },
			    catch8: {
			      text: "사용된 논리를 논리적인 순서를 지키며 5번 정도 반복해 보세요",
			      value: "catch8",className : \'btn btn-primary\'
			    },
			    catch9: {
			      text: "사용된 논리를 현재까지 몇 번정도 생각한 적이 있는지 써 봅시다",
			      value: "catch9",className : \'btn btn-primary\'
			    },
			    catch10: {
			      text: "순서도를 그려서 논리적인 순서를 표현해 보세요",
			      value: "catch10",className : \'btn btn-primary\'
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
			      swal("취소되었습니다");
			      break;
			 
 			   case "catch1":
  			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'1\',
					"inputtext":text1,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
					"contextid":Contextid,
					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'2\',
					"inputtext":text2,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'3\',
					"inputtext":text3,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'4\',
					"inputtext":text4,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'5\',
					"inputtext":text5,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'6\',
					"inputtext":text6,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"inputtext":text7,	
					"feedbackid":\'7\',
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
					"contextid":Contextid,
					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch8":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'8\',
					"inputtext":text8,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
					"contextid":Contextid,
					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch9":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'9\',
					"inputtext":text9,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
					"contextid":Contextid,
					"currenturl":Currenturl,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch10":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_status.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'304\',
					"feedbackid":\'10\',
					"inputtext":text10,	
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"contentstype0":Contentstype0,
					"contentsid0":Contentsid0,
					"wboardid":Wboardid,
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
 
	'; 
}
if($mode==0)echo '	$(\'#quickmessage\').click(function(e) {
 				//var inputOptions =  { "1": " 학습목표", "2": " 접근방법",   "3": " 키워드", "4": " 사고확장", "5": " 인지훈련",   "6": " 오답원인", "7": " 복습추천", "8": " 토픽맵",   "9": " 풀이맵",   "10": " 유형맵",   "11": " 음성해설",   "12": " AI 추천 피드백"}; 
 				var inputOptions1 =  {"2": " 접근방법",   "3": " 키워드"}; 
				var inputOptions2 =  { "1": "1", "2": "2",   "3": "3", "4": "4", "5": "5",   "6": "6", "7": "7", "8": "8",   "9": "9"}; 
			 	Swal.mixin({
			 	 input: \'text\',
				  confirmButtonText: \'다음 &rarr;\',
				  showCancelButton: true,
				  cancelButtonText: \'취소\',
				  progressSteps: [\'1\', \'2\', \'3\']
				}).queue([
				  {
				  html: \'<table align=center width=30%><tr  align=center><td align=center><img src=https://mathking.kr/Contents/IMAGES/aiicon.gif></td></tr></table>\',
				  width: 800,
 				  title: "개념공부 피드백 유형선택",
				  text: " 노트유형, 난이도, 경과시간, DELAY 시간은 ... 학생 수준... , ... 입니다. 적합한 피드백을 선택해주세요",
				  input: "radio",
				  inputOptions:  inputOptions1,
				 
				  },
				  {
   				  html: \'<table align=center width=30%><tr  align=center><td align=center><img src=https://mathking.kr/Contents/IMAGES/chaticon.gif></td></tr></table>\',
 				  width: 600,
				  title: \'메세지 입력\',
				  text: \'전달하고자 하는 내용을 입력해 주세요\'
				  },
				  {
				  html: \'<table width=100% align=center><tr><td align=center> </td><td align=center>&nbsp; <br> &nbsp; </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td></tr><tr><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/1.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/2.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/3.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/4.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/5.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/6.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/7.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/8.gif></td><td><img src=https://mathking.kr/Contents/IMAGES/cognitive/9.gif></td></tr><tr><td align=center>1</td><td align=center>2</td><td align=center>3</td><td align=center>4</td><td align=center>5</td><td align=center>6</td><td align=center>7</td><td align=center>8</td><td align=center>9</td></tr><tr><td align=center>&nbsp; <br> &nbsp;  </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td><td align=center> </td></tr></table><br>\',
   				  width: 600,
 				  title: "이모지 선택",
				  input: "radio",
				  inputOptions:  inputOptions2,  
				
				  }
				]).then((result) => {
				  if (result.value) {	
				    const answers = JSON.stringify(result.value) 
				 
 				    var Userid= \''.$studentid.'\';
				    var Tutorid= \''.$teacherid.'\';
				    var Contentstype= \''.$contentstype.'\';
				    var Contentsid= \''.$contentsid.'\';
				    var Contextid= \''.$contextid.'\';
				    var Currenturl= \''.$currenturl.'\'; 
				    var Wboardid= \''.$id.'\'; 
				    var Status= \''.$status.'\'; 

				    var Type=result.value[0];
				    var Inputtext=result.value[1];
				    var Emoji=result.value[2];
				    var Titletext="메세지가 발송되었습니다.";
				    var Guidetext="";

				    if(Type>=8 && Type<=11) 
					{
					Guidetext="링크를 클릭하여 화이트보드에 전달할 내용을 입력해주세요. 완료버튼과 함께 최종 메세지가 발송됩니다.";
					Titletext="화이트보드를 활용한 메세지 발송을 선택하였습니다.";
					}
				    Swal.fire({
				      width: 600,
				      html: \'<table align=center width=50%><tr  align=center><td align=center><img src=https://mathking.kr/Contents/IMAGES/sentmessage.jpg></td></tr><tr><td>\'+Guidetext+\'</td></tr></table>\',
				      title: Titletext,
			 
				      confirmButtonText: \'확인\'
				    })	   
					$.ajax({
					url:"check_algorithm.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'1\',
					"type":Type,
					"status":Status,	
					"inputtext":Inputtext,
					"emoji":Emoji,
					"userid":Userid,
					"tutorid":Tutorid,
					"contentstype":Contentstype,
					"contentsid":Contentsid,
					"wboardid":Wboardid,
					"contextid":Contextid,
					"currenturl":Currenturl,
					},
					success:function(data){
					 
					 }
					 })
				  }
location.reload();	
				})
			});  
';



 ?> 
function beginchat(Eventid,Chatid,Userid,Tutorid,Wboardid)
{
	$.ajax({
    	url: "check_status.php",
	type: "POST",
	dataType:"json",
 	data : {
	"eventid":Eventid,
	"chatid":Chatid,	
	"userid":Userid,
	"tutorid":Tutorid,
	"wboardid":Wboardid,
	},
	success:function(data){
	 }
	 })
}

function checkstate() {
    if(unlock==1)
    {
      state = 0
      $.ajax({
        type: 'POST',
        url: './state.php',
        data : {id:testid,state:state},     
        dataType: 'json',
        success:function(data,status,er) {
          location.href = "./board.php?id="+testid;
        },
        error:function(data,status,er) {
          location.href = "./board.php?id="+testid;
        }
      });
      
    }
    else if(state==1)
  {
    lock();
  }
  }
  function db_nametagcheck() {
    var boardname = document.getElementById("boardname");
    var category = document.getElementById("category");
    if(db_category!='' || db_boardname!='')
    {
      category.value = db_category
      boardname.value = db_boardname
      boardname.disabled = true;
      category.disabled = true;
    }
  }
  var obShareUrl = document.getElementById("ShareUrl");


obShareUrl.value = window.document.location.href;  // 현재 URL 을 세팅해 줍니다.

  checkstate();
  db_nametagcheck();
    </script>
  </body>
  <style>

.feel {
  margin: 5px 5px;
  background-color: white;
  height:50px;
}
</style>

</html>

