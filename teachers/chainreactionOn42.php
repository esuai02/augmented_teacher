<?php 
include("navbar.php");
$timecreated=time();
if($USER->id==$teacherid)$DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES('$USER->id',71,'chainreaction','$timecreated')");
$tlastaccess=$timecreated-604800*30;
require_login();
$halfdayago=$timecreated-43200;
$aweekago=$timecreated-604800;
$amonthago6=$timecreated-604800*30;
$timestart=date("Y-m-d", $timecreated);
$minutes5=$timecreated-300;
 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0); if($nday==0)$nday=7;
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$lastreply= $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher WHERE userid='$teacherid' AND event LIKE '질문알림' ORDER BY id DESC LIMIT 1 ");  //institution LIKE '$academy' AND 
$mystudents=$DB->get_records_sql("SELECT id,firstname,lastname FROM mdl_user WHERE suspended=0 AND lastaccess> '$halfdayago' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%') ORDER BY id DESC ");  

$eventtime_prev=0;
$result= json_decode(json_encode($mystudents), True);
 
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$userlastaccess=$user['lastaccess'];
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated  >'$halfdayago'  AND ( type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");
 
	//$engagement = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog

	$tracking= $DB->get_record_sql("SELECT  * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='begin' AND timecreated >'$aweekago' ORDER BY id ASC LIMIT 1 ");
	$tracktime=round((($tracking->duration-$timecreated)/60));

	$goaltext=iconv_substr($goal->text,0,15, "utf-8");
	if($goal->id==NULL)$goaltext='<span style="color:red;">목표 미입력</span>';
	//if($timecreated-$goal->timecreated>43200)continue;

	$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$userid' AND pinned=1 ORDER BY id DESC LIMIT 1");
	$daystr='duration'.$nday;
	$hours=$schedule->$daystr; 
	$thisboard=$DB->get_record_sql("SELECT timemodified,tlaststroke FROM mdl_abessi_messages WHERE  userid='$userid'  AND tlaststroke >'$halfdayago'  ORDER BY tlaststroke DESC LIMIT 1");
	//if($goal->id==NULL)continue;

	$indicators = $DB->get_record_sql("SELECT nalt,kpomodoro,npomodoro,pmresult,timecreated  FROM  mdl_abessi_indicators WHERE userid='$userid' ORDER BY id DESC LIMIT 1"); 
	$kpomodoro='';

	if($indicators->kpomodoro==NULL)$kpomodoro='<b style="color:red;">💚</b>';
	elseif($indicators->kpomodoro>180)$kpomodoro='<b style="color:red;">💔</b>';
	elseif($indicators->kpomodoro>60)$kpomodoro='<b style="color:orange;">'.$indicators->kpomodoro.'</b>';
	elseif($indicators->kpomodoro>40)$kpomodoro='<b style="color:blue;">'.$indicators->kpomodoro.'</b>';
	else $kpomodoro='<b style="color:green;">'.$indicators->kpomodoro.'</b>';

	if($indicators->pmresult>8)$kpomodoro2='<b style="color:red;">🌕</b>';
	elseif($indicators->pmresult>6)$kpomodoro2='<b style="color:red;">🌓</b>';
	elseif($indicators->pmresult>4)$kpomodoro2='<b style="color:red;">🌒</b>';
	else $kpomodoro2='<b style="color:red;">🌑</b>';
	 
	$npomodoro=$indicators->npomodoro;
	if($npomodoro==NULL || $npomodoro<5)$kpomodoro='<b style="color:red;">Alert</b>';

	$kpomodoro=$kpomodoro2.$kpomodoro;
	$seenalt='('.round($indicators->nalt/3,1).'회/일)';
 	if($indicators->nalt/3<2)$ALTstatus='<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly letter.php?userid='.$userid.'" target="_blank"><span style="color:white;width:100px;">✉️</span></a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid='.$userid.'" target="_blank"><span style="color:white;width:100px;">🏁</span></a>';
	else $ALTstatus='<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/mentoring/weekly letter.php?userid='.$userid.'" target="_blank"><span style="color:white;width:100px;">✉️</span></a><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid='.$userid.'" target="_blank"><span style="background-color:green;color:white;width:100px;">✳️</span></a>';
	$timespent= round(($timecreated-$thisboard->timemodified)/60,0);//현재 활동 경과시간
	
	$tlaststroke=$timecreated-$thisboard->tlaststroke;
 
	$tlaststroke=min($tlaststroke,$timecreated-$goal->timecreated,$timecreated-$tracking->timecreated);
	//$userdata=$DB->get_record_sql("SELECT data,fieldid FROM mdl_user_info_data where userid='$userid' AND  fieldid='111' ORDER BY id DESC LIMIT 1 "); 
	//$mentorid=$userdata->data;
	//$std= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ORDER BY id DESC LIMIT 1");
	$studentname=$user['firstname'].$user['lastname'];

	$usedtime=round(($timecreated-$goal->timecreated)/3600,1);
	$triggered=($timecreated-$goal->alerttime3)/60;
	
  
	if($usedtime>1000)$usedtime=0;
	$ninactive=$goal->ninactive;
	$nlazy=round($goal->nlazy/20,0); // 누적 20분
	$ninactivestr=$ninactive;
	if($ninactive>=1)
		{
		$ninactivestr='<b style="color:red;">'.$ninactive.'</b>';
		}
	/*
	if($goal->nqstn!=0)
		{
		$quizave=(INT)($goal->nright/$goal->nqstn*100);
		if($quizave<70)$quizave='<b style="color:orange;">'.$quizave.'</b>';
		}
	*/
	//$mcactive=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk WHERE talkid=17 AND creator LIKE '$userid' ORDER BY id DESC LIMIT 1 ");  

	$ratio=$goal->score;  
	if($ratio==0)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png width=20>';
	elseif($ratio<70)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png width=20>';
	elseif($ratio<75)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png width=20>';
	elseif($ratio<80)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png width=20>';
	elseif($ratio<85)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png width=20>';
	elseif($ratio<90)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png width=20>';
	elseif($ratio<95)$imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png width=20>';
	else $imgtoday='<img loading="lazy" src=https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png width=20>';

	$statustext='';
	
	if($timespent>=5)$timespent='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><b style="color:#FE9900;font-size:25px;">👀</b></a>';
	else $timespent='<span style="color:grey;font-size:15px;">'.$timespent.'분</span>';

	$checkstepquestion=null;$checkswbquestion=null;
	
	$stepquestion= $DB->get_record_sql("SELECT * FROM mdl_abessi_questionstamp WHERE userid='$userid' AND status LIKE '질문' AND timemodified >'$halfdayago'  ORDER BY id ASC LIMIT 1 ");
	if($stepquestion->id==NULL)$wbquestion=$DB->get_record_sql("SELECT id,url,tlaststroke FROM mdl_abessi_messages WHERE  userid='$userid' AND boardtype LIKE 'gpttopic' AND status LIKE 'studentreply'  ORDER BY tlaststroke ASC LIMIT 1");
	$checkstepquestion=$stepquestion->id;$checkswbquestion=$wbquestion->id;
	
	if($goal->status==='warning' && $goal->alerttime3 <= $goal->alerttime4  && $goal->type!=='검사요청' || $ptcomplete==='*')
		{
		if($triggered>30 && $ptcomplete==='*') $caretheseusers.='<a style="font-size:20px; color:red;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$studentname.'</a>   |';
		elseif($ptcomplete!=='*')  $caretheseusers.='<a style="font-size:16px;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank">'.$studentname.'</a>   |';
		if($USER->id==827)$DB->execute("INSERT INTO {abessi_chat} (mode,text,userid,userto,wboardid,mark,t_trigger) VALUES('refocus','are you ok ?','$userid','$USER->id','refocus','0','$timecreated')"); 
		}

		if($goal->alerttime>$halfdayago)
		{
		$eventtime=round(($timecreated-$goal->alerttime)/60,0);
		$questionstamp1.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';

		if($eventtime> $eventtime_prev)$replyfirst='<span style="font-size:70px;">'.$studentname.'</span> (직접)<button   type="button"  style = "font-size:16;background-color:grey;color:orange;border:0;outline:0;" onClick="quickReply(313,\''.$userid.'\',\''.$goal->id.'\')" >해제</button>';
		$eventtime_prev=$eventtime;
		}
	elseif($checkwbquestion!=NULL)
		{
		$eventtime=round(($timecreated-$wbquestion->tlaststroke)/60,0);
			
		$currenturl=strstr($wbquestion->url, 'cid');  //before
		
		$questionstamp2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote42.php?'.$currenturl.'"target="_blank">'.$studentname.'</a>('.$eventtime.'분)</td> ';
	
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
 
	$chapterlog= $DB->get_record_sql("SELECT  * FROM mdl_abessi_chapterlog WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");
	$termplan= $DB->get_record_sql("SELECT  id FROM mdl_abessi_progress WHERE userid='$userid' AND plantype ='분기목표' AND hide=0 AND deadline > '$timecreated'  ORDER BY id DESC LIMIT 1  ");
	$userinfo='<td width=15%> <input type="checkbox" onClick="submittoday(21,\''.$userid.'\',this.checked)"/> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid='.$userid.'"target="_blank">'.$imgtoday.'</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a>  </td>';


	if($tracking->status==='begin' && $tracktime>0)$tracktime='<SPAN><b style="color:blue;display: inline-block; width: 30px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid='.$userid.'"target="_blank">'.$tracktime.'</a></b></SPAN>';
	elseif($tracking->status==='begin' && $tracktime<=0 && $tracking->timecreated > $timecreated - 43200)
		{
		$tracktime='<SPAN><b style="color:red;display: inline-block; width: 30px;"><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid='.$userid.'"target="_blank">'.$tracktime.'</a></b></SPAN>';
		$DB->execute("UPDATE {abessi_today} SET nlazy=nlazy+1 WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 
		}
	else 
		{
		$tracking2= $DB->get_record_sql("SELECT  * FROM mdl_abessi_tracking WHERE userid='$userid' AND status='complete'  ORDER BY id DESC LIMIT 1 ");
		if($tracking2->duration-$timecreated<-36000)$tracktime='<a style="display: inline-block; width: 30px;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid='.$userid.'"target="_blank"><b style="color:red;display: inline-block; width: 30px;">#</b></a>';
		else $tracktime='<a style="display: inline-block; width: 30px;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding_stat.php?userid='.$userid.'"target="_blank"><b style="color:#ec4ef5;display: inline-block; width: 30px;">'.round(($timecreated-$tracking2->duration)/60,0).'</b></a>';

		$DB->execute("UPDATE {abessi_today} SET nlazy=nlazy+1 WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 
		}
	$instructiontext='<div class="tooltip3"><img src=https://mathking.kr/Contents/IMAGES/pomodorologo.png width=25ss>&nbsp; <span class="tooltiptext3"><table style="" align=center width=90%><tr><td width=20%>'.date("h:i", $tracking->timecreated).'</td><td>'.$tracking->text.'</td><td align=right width=20%>'.round(($tracking->duration-$tracking->timecreated)/60,0).'분</td> <td align=center> 만족도 : '.$indicators->pmresult.'  (np :'.$indicators->npomodoro.')</td> </tr></table></span></div>';
	$fixnote='<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_memo.php?studentid='.$userid.'" target="_blank"><img src="https://mathking.kr/Contents/Moodle/Visual%20arts/fixnote.png" width=20></a>';
	$lefttime='📅 '.(($hours-$usedtime)*60).'분';
	$wgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");

	if($goal->type=='검사요청' && $timecreated-$goal->timemodified>1800) // 귀가검사 완료
		{
		$wgoaltext=iconv_substr($wgoal->text, 0,20, "utf-8");
		if($ratio>=90 && $ninactive==0)$nbreak='<b>프리패스</b><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a>';
		else $nbreak='<b>귀가검사</b><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a>';
		$tsubmitted=(INT)(($timecreated-$goal->timemodified)/60).'분';
		if($tsubmitted>60)$tsubmitted=round($tsubmitted/60,0).'시간';
		if($ninactive==0)$statustext='<span style="color:green;">'.$nbreak.' '.$ninactivestr.'</span> </td><td> &nbsp;&nbsp;<b> ('.$tsubmitted.')</b>';
		else $statustext='<span style="color:green;">'.$nbreak.' '.$ninactivestr.'</span> </td><td> &nbsp;&nbsp;<b> ('.$tsubmitted.')</b>';	
		$goalresult=$goal->result;
		$goalpcomplete=$goal->pcomplete;
		if($goalresult/$goalpcomplete<0.8)$goalresult='<b style="color:green;">'.$goalresult.'</b>';
		
		$userlist01.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid='.$userid.'"target="_blank">'.$imgtoday.'</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> </td><td style="color:blue;font-size:16px;">'.$statustext.' </td><td> '.$goalpcomplete.'% ('.$schedule->weektotal.'h)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime
		.'</td><td>   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> </td><td></td><td width=6%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$hours.'</a></td><td width=30%> <b>이번 주</b>  '.$wgoaltext.'  ('.$goalresult.'%) | </td><td  width=30%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank"><b>오늘</b> '.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}
	elseif($goal->type=='검사요청') // 귀가검사 요청
		{
		$wgoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid'  AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
		$wgoaltext=iconv_substr($wgoal->text, 0, 20, "utf-8");
		if($usedtime<$hours-0.16)$nextgoal= '<b style="color:red">'.$nextgoal.'</b>';
		if($schedule->start2!=NULL && $nday<2)$nextday='<b>화 '.$schedule->start2.'</b> | '.$schedule->duration2.'h';
		elseif($schedule->start3!=NULL && $nday<3)$nextday='<b>수 '.$schedule->start3.'</b> | '.$schedule->duration3.'h';
		elseif($schedule->start4!=NULL && $nday<4)$nextday='<b>목 '.$schedule->start4.'</b> | '.$schedule->duration4.'h';
		elseif($schedule->start5!=NULL && $nday<5)$nextday='<b>금 '.$schedule->start5.'</b> | '.$schedule->duration5.'h';
		elseif($schedule->start6!=NULL && $nday<6)$nextday='<b>토 '.$schedule->start6.'</b> | '.$schedule->duration6.'h';
		elseif($schedule->start7!=NULL && $nday<7)$nextday='<b>일 '.$schedule->start7.'</b> | '.$schedule->duration7.'h';
		if($ratio>=90 && $ninactive==0)$nbreak='<b>프리패스</b>';
		else $nbreak='<b>귀가검사</b>';
		$tsubmitted=(INT)(($timecreated-$goal->timemodified)/60).'분';
		if($tsubmitted>60)$tsubmitted=round($tsubmitted/60,0).'시간';
		if($ninactive==0)$statustext='<span style="color:green;">'.$nbreak.'<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a>'.$ninactivestr.'</span></td><td> &nbsp;&nbsp;<b> ('.$tsubmitted.')</b>';
		else $statustext='<span style="color:green;">'.$nbreak.'<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a> '.$ninactivestr.'</span></td><td> &nbsp;&nbsp;<b> ('.$tsubmitted.')</b>';	
		$goalresult=$goal->result;
		$goalpcomplete=$goal->pcomplete;
		if($goalresult/$goalpcomplete<0.8)$goalresult='<b style="color:red;">'.$goalresult.'</b>';

		$userlist02.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><td width=15%><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/analysis/user_analysis.php?userid='.$userid.'"target="_blank">'.$imgtoday.'</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id='.$userid.'&tb=604800"target="_blank">'.$studentname.'</a> <a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627202411001.png" width=20></a> </td><td style="color:blue;font-size:16px;">'.$statustext.' </td><td> '.$goalpcomplete.'% ('.$schedule->weektotal.'h)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.')</b></td><td>   <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> </td><td></td><td width=6%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$hours.'</a></td><td width=30%> <b>이번 주 </b> '.$wgoaltext.'  ('.$goalresult.'%) | </td><td width=30%>  <a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><b>오늘</b> '.$goaltext.'  </td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}
	elseif($goal->inspect==2) // 오프라인
		{
		$resttime=round($tlaststroke/60,0);
		if($resttime>=300 && $goal->inspect==2)$DB->execute("UPDATE {abessi_today} SET inspect='0' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 
		else $statustext='<span style="color:lightgreen;" Onclick="quickReply(331,\''.$userid.'\',\''.$goal->id.'\');">휴식 ('.round($tlaststroke/60,0).'분)</span>';
		//$statustext='휴식 (<b style="color:red;">'.round($tlaststroke/60,0).'</b>분)';
		if($timecreated-$userlastaccess<$tlaststroke)$tlaststroke=$timecreated-$userlastaccess;
		$passedtime=round($tlaststroke/60,0).'분';	
		if($tlaststroke>1000000)$passedtime='#';
		if($goal->inspect==3)$statustext='<span Onclick="quickReply(331,\''.$userid.'\',\''.$goal->id.'\');">지면('.$passedtime.')</span>';

		$non_onlineusers.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;"><b style="color:black;">'.$statustext.'</b> </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a></td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}
	elseif($goal->inspect==3) // 오프라인
		{
		$resttime=round($tlaststroke/60,0);
		if($resttime>=15 && $goal->inspect==2)$DB->execute("UPDATE {abessi_today} SET inspect='0' WHERE  userid='$userid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 "); 
		else $statustext='<span  style="color:lightgreen;"  Onclick="quickReply(331,\''.$userid.'\',\''.$goal->id.'\');">휴식 ('.round($tlaststroke/60,0).'분)</span>';
		//$statustext='휴식 (<b style="color:red;">'.round($tlaststroke/60,0).'</b>분)';
		if($timecreated-$userlastaccess<$tlaststroke)$tlaststroke=$timecreated-$userlastaccess;
		$passedtime=round($tlaststroke/60,0).'분';	
		if($tlaststroke>1000000)$passedtime='#';
		if($goal->inspect==3)$statustext='<span Onclick="quickReply(331,\''.$userid.'\',\''.$goal->id.'\');">지면('.$passedtime.')</span>';

		$non_onlineusers2.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;"><b style="color:black;">'.$statustext.'</b> </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a></td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}
	elseif($tlaststroke<30)
		{
		$userlist1.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a> </td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';	
		}
	elseif($tlaststroke<60) 
		{
		$userlist2.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a> </td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';	
		}	
	elseif($tlaststroke<180)
		{
		$userlist3.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a> </td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}		
	elseif($tlaststroke<300)
		{
		$userlist4.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;">'.$tlaststroke.'초 </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a> </td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		}	
	elseif($tlaststroke<43200) 
		{
		$userlist5.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;"><span style="color:red;" Onclick="quickReply2(333,\''.$userid.'\',\''.$goal->id.'\',2);" >이탈</span>('.round($tlaststroke/60,0).'분) </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a></td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		if($goal->checktime<$minutes5 && $goal->inspect==0)$DB->execute("UPDATE {abessi_today} SET status='warning',ninactive=ninactive+1, checktime='$timecreated' WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	 
		else $DB->execute("UPDATE {abessi_today} SET activetime=activetime+300 WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	

		if($goal->activetime>3600)$DB->execute("UPDATE {abessi_today} SET  activetime=0,ninactive=ninactive-1 WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	 
		}	
	else 
		{
		$userlist6.='<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">'.$userinfo.'<td width=5%>'.$fixnote.' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dashboard_fixnotes.php?userid='.$userid.'"target="_blank"><b style="color:blue;">NOTE</b></a>&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/ktmcopilot.php?tab=tab4&userid='.$userid.'&mode=directions" target="_blank" ><img loading="lazy" src="https://mathking.kr/Contents/Moodle/Visual%20arts/gatetosubconsciousness.png" width=20></a>&nbsp;&nbsp;</td><td width=10% style="color:blue;font-size:16px;"><span style="color:lightgrey;" Onclick="quickReply2(333,\''.$userid.'\',\''.$goal->id.'\',2);" >대기</span> </td><td width=10%> <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id='.$teacherid.'&userid='.$userid.'"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" width=25></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?cid='.$chapterlog->cid.'&cntid='.$chapterlog->cntid.'&nch='.$chapterlog->nch.'&studentid='.$userid.'&type=init"target="_blank"><img src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/timefolding.png" width=25></a></td><td width=10%>('.$timespent.' | '.$ninactivestr.'회)</td><td width=12%><a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/cjnstudents/mypersonas.php?userid='.$userid.'"target="_blank">🎭</a>  <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid='.$userid.'"target="_blank">'.$instructiontext.'</a> &nbsp; '.$tracktime.' <b style="display: inline-block; width: 30px;">('.$nlazy.') </b></td><td width=10%>  <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1"target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624721323001.png" width=50px></a>('.$kpomodoro.')&nbsp;&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$userid.'&mode=today"target="_blank"><img loading="lazy" src=https://mathking.kr/Contents/IMAGES/replay.png width=15></a> &nbsp;&nbsp;&nbsp;'.$statustext.'</td><td width=10%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=12"target="_blank">'.$lefttime.'</a></td><td width=22%><a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank">🟦</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$wgoal->id.'"target="_blank">🟪</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$userid.'&cid='.$chapterlog->cid.'&pid='.$goal->id.'&nch='.$chapterlog->nch.'"target="_blank">🟩</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/edittoday42.php?id='.$userid.'"target="_blank">'.$goaltext.'</a></td><td align=right>'.$ALTstatus.'</td><td>'.$seenalt.'</td></tr>';
		if($goal->checktime<$minutes5 && $goal->inspect==0)$DB->execute("UPDATE {abessi_today} SET status='warning',ninactive=ninactive+1, checktime='$timecreated' WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	 
		else $DB->execute("UPDATE {abessi_today} SET activetime=activetime+300 WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	
		if($goal->activetime>3600)$DB->execute("UPDATE {abessi_today} SET  activetime=0,ninactive=ninactive-1 WHERE userid='$userid'  AND type LIKE '오늘목표'  ORDER BY id DESC LIMIT 1");	 
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
				<div class="container-fluid"><table align=center width=90%><tr>  <td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/papertest.php?id='.$teacherid.'"target="_blank">✍🏻</a> &nbsp;&nbsp;&nbsp;	</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-elB1Niwrc-hagseub-pereusona-saengseonggi" accesskey="r"><b>학습 페르소나</b></a></td><td>&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://claude.ai/public/artifacts/edd658d6-aeb2-48f6-86ae-978660350f22?fullscreen=true"target="_blank">🧑수업준비</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/applymeetingresults.php?userid='.$teacherid.'"target="_blank">🧑회의</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/view_reports.php?userid='.$teacherid.'"target="_blank">🧑귀가검사</a>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/Goclassroomgame.php?userid='.$teacherid.'"target="_blank">🤖</a></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/teachingagent.php?userid='.$teacherid.'">풀이 요청</a></td>  <td><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timetable.php?id='.$teacherid.'&tb=7"target="_blank">시간표</a></td><td width=5%><audio controls style="width:200px;height:20px;" 
                       src="https://mathking.kr/Contents/Management/KTM%20%ED%95%99%EC%8A%B5%20%EC%8B%9C%EC%8A%A4%ED%85%9C%20%EB%A7%A4%EB%89%B4%EC%96%BC%20%28%EC%84%A0%EC%83%9D%EB%8B%98%29.wav">
                </audio></td><td>| &nbsp;&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_cjncrews.php"target="_blank">TTS</a>&nbsp; | <a href="https://claude.ai/project/ff45438b-526d-4c21-8462-4da09377bceb"target="_blank">이미지</a></td></tr></table><hr>		
					<div class="row">
						<div class="col-md-12">
							<div class="invoice-detail"> 
								<div class="invoice-item"><br>	
									<div class="table-responsive">
										<table width=100%><tr><td><table width=90%><tr><td>#도움요청</td><td>&nbsp;</td>'.$helpstamp.'</tr></table> </td><td><table width=90%><tr><td>#직접질문</td><td>&nbsp;</td>'.$questionstamp1.'</tr></table></td><td><table width=90%><tr><td>#개념질문</td><td>&nbsp;</td>'.$questionstamp2.'</tr></table></td><td><table width=90%><tr><td>#부분질문</td><td>&nbsp;</td>'.$questionstamp3.'</tr></table></td></tr></table><hr><table align=center width=90%><tr><td>'.$replyfirst.'</td></tr></table>
											<hr style="border: dashed 1px red;"><table width=90%><tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/dashboard_whiteboard.php?teacherid='.$teacherid.'"target="_blank">💫 선 넘은 아이들</a></b> &nbsp; &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/dashboard_fixnotes.php?teacherid='.$teacherid.'"target="_blank">💫 오답노트 검사</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$caretheseusers.'</td></tr></table><hr>
											
											<table width=100%><tr><td><table width=90% align=center>'.$userlist6.$userlist5.$userlist4.$userlist3.'<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td><td><hr style="border: dashed 1px red;">	</td></tr>'.$userlist2.$userlist1.'<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td></tr>'.$non_onlineusers2.'<tr style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td><td><hr style="border: solid 1px green;">	</td></tr>'.$non_onlineusers.'</table></td></tr></table>													 
											<hr style="border: solid 2px green;">
											
											<table  style="background-color:#e8ffe3;"   width=100%><tr><td><table width=90% align=center>'.$userlist02.'</table></td></tr></table>	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><hr style="border: solid 2px green;"><table  style="background-color:#e8ffe3;"  width=100%><tr><td><table width=90% align=center>'.$userlist01.'</table></td></tr></table>												   
									</div>
								</div>
							</div>			
						</div>
					</div>
				</div>
			</div>
			
		</div>
 ';//  style="background-color:#F9E9EB;"  
include("quicksidebar.php");
echo '<meta http-equiv="refresh" content="60">'; 
echo '
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
top:60;
left:50%;
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
</style>
	<script>
	function quickReply(Eventid,Userid,Goalid){
		//alert("질문이 전달되었습니다. 기다리는 동안 후속 학습을 진행해 주세요.");
		$.ajax({ 
			url:"../students/check.php",
			type: "POST",
			dataType:"json",
			data : {
			"userid":Userid,       
			"goalid":Goalid,
			"eventid":Eventid,
						 
			},
			success:function(data){
			 }
		})	
		
 		swal("","적용되었습니다", {buttons: false,timer: 3000});
 		location.reload(); 
	}
	function quickReply2(Eventid,Userid,Goalid,Checkimsi){
		//alert("질문이 전달되었습니다. 기다리는 동안 후속 학습을 진행해 주세요.");
		$.ajax({ 
			url:"../students/check.php",
			type: "POST",
			dataType:"json",
			data : {
			"userid":Userid,       
			"goalid":Goalid,
			"eventid":Eventid,
			"checkimsi":Checkimsi,			 
			},
			success:function(data){
			 }
		})	
		
 		swal("","적용되었습니다", {buttons: false,timer: 3000});
 		location.reload(); 
	}   


			
	function submittoday(Eventid,Userid,Checkvalue){
		 
	var checkimsi = 0;
	if(Checkvalue==true){
		checkimsi = 1;
		}
	$.ajax({
		url:"../students/database.php",
		type: "POST",
		dataType:"json",
		data : {
				"userid":Userid,
				"eventid":Eventid,
				"checkimsi":checkimsi,
				},
			success:function(data)
				{

				}
		})

 
 		swal("","귀가검사가 제출됩니다", {buttons: false,timer: 3000});
 	//	location.reload(); 
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

/*

function evaluateResult(Studentid)
		{		 
 
			var text1="몰입 상";
			var text2="몰입 중";
			var text3="몰입 하";

			swal("이번 구간 공부는 어떠셨나요 ?",  "",{
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
			cancel: {
				text: "취소",
				visible: false,
				className: \'btn btn-alert\'
				}, 
			  },
			})
			.then((value) => {
			  switch (value) {
			 
			    case "defeat":
			     swal("취소되었습니다.", {buttons: false,timer: 500});
			      break;
		 
 			   case "catch1":
				swal("",text1+"을 선택하였습니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'26\',
					"userid":Studentid,
					"result":\'3\',	 		 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				 
				break;
				
 			   case "catch2":
				swal("",text2+"을 선택하였습니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'26\',
					"userid":Studentid,
					"result":\'2\',	 		 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				 
				break;
				 
 			   case "catch3":
				swal("",text3+"을 선택하였습니다.",{buttons: false,timer: 500});
					$.ajax({
					url:"check.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'26\',
					"userid":Studentid,
					"result":\'1\',	 		 
					},
					success:function(data){
					location.reload();  
					 }
					 })
				 
				break;
				   
			}
		})
	}
*/
?>
