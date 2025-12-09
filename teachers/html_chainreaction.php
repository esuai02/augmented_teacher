<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
if($USER->id==$teacherid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES('$USER->id',71,'chainreaction','$timecreated')");
$tlastaccess=$timecreated-604800*30;
 
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$amonthago6=$timecreated-604800*30;
$timestart=date("Y-m-d", $timecreated);
$minutes10=$timecreated-600;
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); if($nday==0)$nday=7;

$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$lastreply= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE userid='$teacherid' AND event LIKE '질문알림' ORDER BY id DESC LIMIT 1 ");
$mystudents=$DB->get_records_sql("SELECT id,firstname,lastname FROM mdl_user WHERE suspended=0 AND institution LIKE '$academy' AND lastaccess> '$amonthago6' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
$eventtime_prev=0;
$result= json_decode(json_encode($mystudents), True);
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$userid' AND pinned=1 ORDER BY id DESC LIMIT 1");
	$daystr='duration'.$nday;
	$hours=$schedule->$daystr;
	

	$thisboard=$DB->get_record_sql("SELECT timemodified,tlaststroke FROM mdl_abessi_messages WHERE  userid='$userid'  AND tlaststroke >'$halfdayago'  ORDER BY tlaststroke DESC LIMIT 1");
	$timespent= round(($timecreated-$thisboard->timemodified)/60,0);//현재 활동 경과시간
	//$engagement3 = $DB->get_record_sql("SELECT tlaststroke  FROM  mdl_abessi_indicators WHERE userid='$userid' ORDER BY id DESC LIMIT 1"); 
	$tlaststroke=$timecreated-$thisboard->tlaststroke;
	
	//$std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ORDER BY id DESC LIMIT 1");
	$studentname=$user['firstname'].$user['lastname'];
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated  >'$halfdayago'  AND ( type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
	$usedtime=round(($timecreated-$goal->timecreated)/3600,1);
	$triggered=($timecreated-$goal->alerttime3)/60;
	if($goal->alerttime4>$goal->alerttime3)$ptcomplete='';
	else $ptcomplete='*';
	if($triggered>720 && $usedtime<0.5)$triggered='<b style="color:#969696;">60분</b>'.$ptcomplete;
	elseif($triggered>60)$triggered='<b style="color:#e85f35;">60분</b>'.$ptcomplete;
	elseif($triggered>30)$triggered='<b style="color:blue;">'.round(($timecreated-$goal->alerttime3)/60,0).'분</b>'.$ptcomplete;
	else $triggered=round(($timecreated-$goal->alerttime3)/60,0).'분'.$ptcomplete;
	if($timecreated-$goal->timecreated>43200)continue;
	$quizave=0;
	$nextgoal='';
	if($usedtime>$hours)$nextgoal=$goal->comment;
	if($usedtime>1000)$usedtime=0;
	$ninactive=$goal->ninactive;
	$ninactivestr=$ninactive;
	if($ninactive>=1)
		{
		$ninactivestr='<b style="color:red;">'.$ninactive.'</b>';
		}
	if($goal->nqstn!=0)
		{
		$quizave=(INT)($goal->nright/$goal->nqstn*100);
		if($quizave<70)$quizave='<b style="color:orange;">'.$quizave.'</b>';
		}
	$mcactive=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE talkid=17 AND creator LIKE '$userid' ORDER BY id DESC LIMIT 1 ");  

	$ratio=$goal->score;  
	if($ratio==0)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20> &nbsp;';
	elseif($ratio<70)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20> &nbsp;';
	elseif($ratio<75)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20> &nbsp;';
	elseif($ratio<80)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20> &nbsp;';
	elseif($ratio<85)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20> &nbsp;';
	elseif($ratio<90)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20> &nbsp;';
	elseif($ratio<95)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20> &nbsp;';
	else $imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20> &nbsp;';

	$statustext='';
	 
	
	if($timespent>=5)$timespent='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><b style="color:red;font-size:18px;">'.$timespent.'</b></a>';
	else $timespent='<span style="color:grey;font-size:15px;">'.$timespent.'</span>';

	$checkstepquestion=null;$checkswbquestion=null;
	
	$stepquestion= $DB->get_record_sql("SELECT * FROM mdl_abessi_questionstamp WHERE userid='$userid' AND status LIKE '질문' AND timemodified >'$halfdayago'  ORDER BY id ASC LIMIT 1 ");
	if($stepquestion->id==NULL)$wbquestion=$DB->get_record_sql("SELECT id,url,tlaststroke FROM mdl_abessi_messages WHERE  userid='$userid' AND boardtype LIKE 'gpttopic' AND status LIKE 'studentreply'  ORDER BY tlaststroke ASC LIMIT 1");
	$checkstepquestion=$stepquestion->id;$checkswbquestion=$wbquestion->id;
	if($tlaststroke<43200)
		{
		if($goal->alerttime2>$halfdayago)
			{
			/*
			$eventtime=round(($timecreated-$goal->alerttime2)/60,0);
			$helpstamp.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';

			if($eventtime> $eventtime_prev)$replyfirst='<span style="font-size:70px;">'.$studentname.'</span> (도움)<button   type="button"  style = "font-size:16;background-color:grey;color:orange;border:0;outline:0;" onClick="quickReply2(314,\''.$userid.'\',\''.$goal->id.'\')" >해제</button>';
			$eventtime_prev=$eventtime;
			*/
			}
		elseif($goal->alerttime>$halfdayago)
			{
			$eventtime=round(($timecreated-$goal->alerttime)/60,0);
			$questionstamp1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';

			if($eventtime> $eventtime_prev)$replyfirst='<span style="font-size:70px;">'.$studentname.'</span> (직접)<button   type="button"  style = "font-size:16;background-color:grey;color:orange;border:0;outline:0;" onClick="quickReply(313,\''.$userid.'\',\''.$goal->id.'\')" >해제</button>';
			$eventtime_prev=$eventtime;
			}
		elseif($checkwbquestion!=NULL)
			{
			$eventtime=round(($timecreated-$wbquestion->tlaststroke)/60,0);
			 
			$currenturl=strstr($wbquestion->url, 'cid');  //before
			
			$questionstamp2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$currenturl.'"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';
	 
			if($eventtime> $eventtime_prev)$replyfirst='<span style="font-size:70px;">'.$studentname.'</span> (개념)<button   type="button"  style = "font-size:16;background-color:'.$bgcolor.';color:white;border:0;outline:0;" onClick="quickReply(313,\''.$userid.'\',\''.$goal->id.'\')" >해제하기</button>';
			$eventtime_prev=$eventtime;
			}
		elseif($checkstepquestion!=NULL) 
			{
			$eventtime=round(($timecreated-$stepquestion->timemodified)/60,0);
			$questionstamp3.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/replay.php?id='.$stepquestion->wboardid.'&gid='.$stepquestion->gid.'&playindex='.$stepquestion->playindex.'&playstate=0&sketchstate=0&speed=3&mode=qstamp&studentid='.$userid.'"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';
		 
			if($eventtime> $eventtime_prev)$replyfirst='<span style="font-size:70px;">'.$studentname.'</span> (부분)<button   type="button"  style = "font-size:16;background-color:'.$bgcolor.';color:white;border:0;outline:0;" onClick="quickReply(313,\''.$userid.'\',\''.$goal->id.'\')" >해제하기</button>';
			$eventtime_prev=$eventtime;
			}


		
		if($usedtime/$hours>0.7 && $ratio<80)$mcstatus='<b style="color:red;">메타인지 ###</b>';
		elseif($mcactive->timemodified<$timecreated-43200)$mcstatus='<b style="color:blue;">메타인지</b>';
		else $mcstatus='<span style="color:green;">메타인지</span>';

		if($goal->inspect==1 && $timecreated-$goal->timemodified>1800)
			{
			if($ratio>=90 && $ninactive==0)$nbreak='<b>프리패스</b>';
			else $nbreak='<b>이탈</b>';
			$tsubmitted=(INT)(($timecreated-$goal->timemodified)/60).'분';
			if($tsubmitted>60)$tsubmitted=round($tsubmitted/60,0).'시간';
			if($ninactive==0)$statustext='<span style="color:green;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$nbreak.'</a> '.$ninactivestr.'</span><b> ('.$tsubmitted.')</b>';
			else $statustext='<span style="color:red;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$nbreak.'</a> '.$ninactivestr.'</span><b> ('.$tsubmitted.')</b>';	
			$goalresult=$goal->result;
			$goalpcomplete=$goal->pcomplete;
			if($goalresult/$goalpcomplete<0.8)$goalresult='<b style="color:red;">'.$goalresult.'</b>';
			
			$userlist01.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td style="color:blue;font-size:16px;">'.$statustext.' </td><td> 주간목표 '.$goalresult.'%</td><td> '.$goalpcomplete.'% ('.$schedule->weektotal.'h)</td><td>'.$quizave.'점</td><td >'.$goal->nqstn.'문항</td><td>   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a></td><td width=6%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$hours.'</a></td><td width=30%>'.$goal->text.'  ('.$nextgoal.') </td></tr>';
			}
		elseif($goal->inspect==1)
			{
			if($schedule->start2!=NULL && $nday<2)$nextday='<b>화 '.$schedule->start2.'</b> | '.$schedule->duration2.'h';
			elseif($schedule->start3!=NULL && $nday<3)$nextday='<b>수 '.$schedule->start3.'</b> | '.$schedule->duration3.'h';
			elseif($schedule->start4!=NULL && $nday<4)$nextday='<b>목 '.$schedule->start4.'</b> | '.$schedule->duration4.'h';
			elseif($schedule->start5!=NULL && $nday<5)$nextday='<b>금 '.$schedule->start5.'</b> | '.$schedule->duration5.'h';
			elseif($schedule->start6!=NULL && $nday<6)$nextday='<b>토 '.$schedule->start6.'</b> | '.$schedule->duration6.'h';
			elseif($schedule->start7!=NULL && $nday<7)$nextday='<b>일 '.$schedule->start7.'</b> | '.$schedule->duration7.'h';
			if($ratio>=90 && $ninactive==0)$nbreak='<b>프리패스</b>';
			else $nbreak='<b>이탈</b>';
			$tsubmitted=(INT)(($timecreated-$goal->timemodified)/60).'분';
			if($tsubmitted>60)$tsubmitted=round($tsubmitted/60,0).'시간';
			if($ninactive==0)$statustext='<span style="color:green;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$nbreak.'</a> '.$ninactivestr.'</span><b> ('.$tsubmitted.')</b>';
			else $statustext='<span style="color:red;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$nbreak.'</a> '.$ninactivestr.'</span><b> ('.$tsubmitted.')</b>';	
			$goalresult=$goal->result;
			$goalpcomplete=$goal->pcomplete;
			if($goalresult/$goalpcomplete<0.8)$goalresult='<b style="color:red;">'.$goalresult.'</b>';

			$userlist02.='<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td style="color:blue;font-size:16px;">'.$statustext.' </td><td> 주간목표 '.$goalresult.'%</td><td> '.$goalpcomplete.'% ('.$schedule->weektotal.'h)</td><td>'.$quizave.'점</td><td >'.$goal->nqstn.'문항</td><td>   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> </td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a></td><td width=6%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$hours.'</a> </td><td width=30%>'.$goal->text.' | '.$nextgoal.' | '.$nextday.'  </td></tr>';
			}
		elseif($goal->inspect==2 || $goal->inspect==3)
			{
			$resttime=round($tlaststroke/60,0);
			if($resttime>=11 && $goal->inspect==2)$DB->execute("UPDATE {abessi_today} SET inspect='0' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 
			else $statustext='휴식 ('.round($tlaststroke/60,0).'분)';
			//$statustext='휴식 (<b style="color:red;">'.round($tlaststroke/60,0).'</b>분)';
			if($goal->inspect==3)$statustext='책공부 ('.round($tlaststroke/60,0).'분)';

			$non_onlineusers.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;"><b style="color:orange;">'.$statustext.'</b> </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a></td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';
			}
		elseif($tlaststroke<30)
			{
			$userlist1.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초  </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a></td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';	
			}
		elseif($tlaststroke<60)
			{
			$userlist2.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a></td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';	
			}	
		elseif($tlaststroke<180)
			{
			$userlist3.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a> </td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';
			}		
		elseif($tlaststroke<300)
			{
			$userlist4.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a> </td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';
			}	
		elseif($tlaststroke<43200) 
			{
			$userlist5.='<tr><td width=20%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800"target="_blank">'.$imgtoday.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/cjnteachers/pages/preset.php?userid='.$userid.'&type=submittoday " target="_blank" ><img loading="lazy" src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/chaticon.png" width=20></a> </td><td width=10% style="color:blue;font-size:16px;"><b style="color:red;">활동없음</b> </td><td> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$triggered.'</a></td><td>('.$timespent.'분 | '.$ninactivestr.'회)</td><td>'.$quizave.'점</td><td>'.$goal->nqstn.'문항</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank">Onair</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/improveimg.png width=30></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/flowhistory.php?studentid='.$userid.'"target="_blank">'.$mcstatus.'</a>&nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$usedtime.'/'.$hours.'</a></td><td width=15%>'.$goal->text.'</td></tr>';
			if($teacherid==$USER->id && $goal->checktime<$minutes10 && $goal->inspect!=2)$DB->execute("UPDATE {abessi_today} SET ninactive=ninactive+1, checktime='$timecreated' WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");	 
			}	
		}
	} 

if(($timecreated-$lastreply->timecreated>120 || $lastreply->timecreated==NULL) && $replyfirst!=NULL)
	{
	$DB->execute("INSERT INTO {abessi_teacher} (userid,event,timecreated) VALUES('$teacherid','질문알림','$timecreated')");
	echo '<script> var mp3_url = \'https://mathking.kr/moodle/local/augmented_teacher/teachers/alarm1.mp3\';(new Audio(mp3_url)).play()</script>';
	}
echo '
		<div class="main-panel">
			<div class="content">  
				<div class="container-fluid"><table align=center><tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/chainreactionOn.php?id='.$teacherid.'" accesskey="r"><b>동작기억 연쇄작용 일으키기</b></a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/selfreactionOn.php?id='.$teacherid.'">자가피드백 현황</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/flowwins.php?id='.$teacherid.'">메타인지</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$teacherid.'&tb=7"target="_blank">시간표</a></td><td width=10%></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/papertest.php?id='.$teacherid.'"target="_blank">Paper test</a></td></tr></table><hr>		
					
									<div class="row">
										<div class="col-md-12">
											<div class="invoice-detail"> 
												<div class="invoice-item"><br>	
													<div class="table-responsive">
														<table width=100%><tr><td><table><tr><td>#도움요청</td><td>&nbsp;</td>'.$helpstamp.'</tr></table> </td><td><table><tr><td>#직접질문</td><td>&nbsp;</td>'.$questionstamp1.'</tr></table> </td><td><table><tr><td>#개념질문</td><td>&nbsp;</td>'.$questionstamp2.'</tr></table></td><td><table><tr><td>#부분질문</td><td>&nbsp;</td>'.$questionstamp3.'</tr></table></td></tr></table><hr><table align=center><tr><td>'.$replyfirst.'</td></tr></table>
														 <hr style="border: dashed 1px red;"><table width=90%><tr><td><b>선 넘은 아이들</b></td></tr></table><hr><table  style="background-color:#F9E9EB;"  width=100%><tr><td><table  width=90% align=center>'.$userlist5.''.$userlist4.$userlist3.'</table></td></tr></table><hr style="border: dashed 2px red;"><table width=90% align=center >'.$userlist2.$userlist1.'</table><hr style="border: solid 2px green;"><table  style="background-color:#D9F9D5;"  width=100%><tr><td><table width=90% align=center >'.$non_onlineusers.'</table></td></tr></table><hr style="border: solid 2px green;"><table  style="background-color:#e8ffe3;"  width=100%><tr><td><table width=90% align=center >'.$userlist02.'</table></td></tr></table>	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><hr style="border: solid 2px green;"><table  style="background-color:#e8ffe3;"  width=100%><tr><td><table width=90% align=center >'.$userlist01.'</table></td></tr></table>										
														 
													</div>
												</div>
											</div>	
											
								
						</div>
					</div>
				</div>
			</div>
			
		</div>
 '; 
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
	<script src="../assets/js/plugin/moment/moment-locale-ko.js"></script>
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
