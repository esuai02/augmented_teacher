<?php 
 /////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");

$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='64' "); 
$tsymbol=$teacher->symbol;

//
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$teacherid' AND fieldid='22' "); 
$role=$userrole->role;
  
if($role=='teacher');
	{
	$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
	$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE firstname LIKE '%$tsymbol%' ");
	$size=count($mystudents); 
	$result= json_decode(json_encode($mystudents), True);
	unset($user);
	foreach($result as $user)
		{
		$userid=$user['id'];
		$userinfo= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$userid' ");
		$username=$userinfo->firstname.$userinfo->lastname;
		$timestart2=time()-86400*7;
		$quizattempt = $DB->get_record_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
		WHERE mdl_quiz.name LIKE '%내신%' AND mdl_quiz_attempts.timestart > '$timestart2'  AND mdl_quiz_attempts.userid='$userid' ORDER BY mdl_quiz_attempts.id  DESC LIMIT 1 ");
		$attemptid=$quizattempt->id;
		$attemptstate=$quizattempt->state;
		$timestart=$quizattempt->timestart;
		$timefinish=$quizattempt->timefinish;
		$quizid=$quizattempt->quiz;
		$quizname=$quizattempt->name;
		$attempt=$quizattempt->attempt;
		$moduleid=$DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'  "); 
		$quizmoduleid=$moduleid->id;
		$beginmission=0;
		$quizgrade=round($quizattempt->sumgrades/$quizattempt->tgrades*100,0);

		if($quizgrade>89.99)	$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
		elseif($quizgrade>69.99)$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
		else $imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';

		$comment= '&nbsp;|&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$attemptid.'" target="_blank">결과분석</a>';
	   	if(strpos($quizname, '기초')!= false )  
			{
			$beginmission=1;
			$quizlist1.= '<b>'.$imgstatus.'&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=864000  target="_blank">'.$username.'</a>&nbsp;'.date("m/d | H:i",$timestart).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quizname,0,40).'</a>...('.$attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.' " target="_blank">'.$attemptstate.'</a>...'.date("H:i",$timefinish).'</b>'.$comment.'<br>';
 			}
	   	if(strpos($quizname, '기본')!= false )  
			{
			$beginmission=1;
			$quizlist2.= '<b>'.$imgstatus.'&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=864000  target="_blank">'.$username.'</a>&nbsp;'.date("m/d | H:i",$timestart).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quizname,0,40).'</a>...('.$attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.' " target="_blank">'.$attemptstate.'</a>...'.date("H:i",$timefinish).'</b>'.$comment.'<br>';
 			}
	   	if(strpos($quizname, '심화')!= false )  
			{
			$beginmission=1;
			$quizlist3.= '<b>'.$imgstatus.'&nbsp;<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=864000  target="_blank">'.$username.'</a>&nbsp;'.date("m/d | H:i",$timestart).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($quizname,0,40).'</a>...('.$attempt.get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span>&nbsp;&nbsp;<a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.' " target="_blank">'.$attemptstate.'</a>...'.date("H:i",$timefinish).'</b>'.$comment.'<br>';
 			}
			$engagement3 = $DB->get_record_sql("SELECT nask,nreply,speed,todayscore, tlaststroke FROM  mdl_abessi_indicators WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
			$ratio1=$engagement3->todayscore; 
			if($ratio1<70)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png" width=20>';
			elseif($ratio1<75)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png" width=20>';
			elseif($ratio1<80)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png" width=20>';
			elseif($ratio1<85)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png" width=20>';
			elseif($ratio1<90)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png" width=20>';
			elseif($ratio1<95)$imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png" width=20>';
			else $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus.png" width=20>';
			if($ratio1==0 && $Qnum2==0) $imgtoday='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png" width=20>';

		if($beginmission==0)
			{
			$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$userid'   ORDER BY id DESC LIMIT 1 ");  // missionlog
			$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$userid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog

			$teng1=time()-$engagement1->timecreated;
			$teng2=time()-$engagement2->timecreated;
			$teng3=time()-$engagement3->tlaststroke;  
  
			$tlastaction=(INT)(min($teng1,$teng2,$teng3)/86400);

			$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125230001.png" width=20>';
			if($tlastaction>1)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125316001.png" width=20>';
			if($tlastaction>3)$statusimg='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606125191001.png" width=20>';
			
 
			// 현재 페이지 포착 
			$stayfocused1=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=1 ORDER BY id DESC LIMIT 1 ");
			$lastaction1= ((time()-$stayfocused1->timecreated)/60);
			$url1=$stayfocused1->context.'?'.$stayfocused1->currenturl;
			$stayfocused2=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=2 ORDER BY id DESC LIMIT 1 ");
			$lastaction2=((time()-$stayfocused2->timecreated)/60);
			$url2=$stayfocused2->context.'?'.$stayfocused2->currenturl;
			$stayfocused3=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$userid' AND status=3 ORDER BY id DESC LIMIT 1 ");
			$lastaction3=((time()-$stayfocused3->timecreated)/60);
			$url3=$stayfocused3->context.'?'.$stayfocused3->currenturl;

			$currentpage1='<a href="'.$url1.'" target="_blank">관리</a>';
			$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank">학습</a>';
			$currentpage3='<a href="'.$url3.'" target="_blank">노트</a>';

			if($lastaction1<=$lastaction2 && $lastaction1<=$lastaction3)$currentpage1='<a href="'.$url1.'" target="_blank"><b>관리</b></a>';
			if($lastaction2<=$lastaction1 && $lastaction2<=$lastaction3)$currentpage2='<a href="'.$url2.'&userid='.$userid.'" target="_blank"><b>학습</b></a>';
			if($lastaction3<=$lastaction1 && $lastaction3<=$lastaction2)$currentpage3='<a href="'.$url3.'" target="_blank"><b><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1606742593001.png" width=25></b></a>'; 
 
			$currentpage=$currentpage1.'|'.$currentpage2.'|'.$currentpage3;
			$tlastaction2=min($lastaction1,$lastaction2,$lastaction3);
			$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$userid' AND fieldid='22' "); 
			$role=$userrole->role;
			$unprepared.='<tr><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$username.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a></td><td>('.$tlastaction.'시간) </td></tr>';
 			}
		else $preparing.='<tr><td>'.$imgtoday.'<a href=https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=43200  target="_blank">'.$username.'</a></td><td>'.$engagement3->nask.'</td><td>'.$engagement3->nreply.'</td><td>'.$statusimg.'</td><td>'.$currentpage.'</td><td><a href="https://mathking.kr/moodle/message/index.php?id='.$userid.' " target="_blank" ><img src="https://cdn.iconscout.com/icon/premium/png-256-thumb/group-chat-5-751639.png" width=17></a></td><td>초기|정체|도전|극복</td></tr>';
		}
	}
echo '<br><br><br><br>
<table align=right valign=top style="width: 80%" ><thead>
<tr><th scope="col" style="width: 60%;"> 내신테스트 현황  </th><th scope="col" style="width: 5%;"> </th><th scope="col" style="width: 35%;"> 내신테스트 시작 & 미시작 </th></tr><tr ><td  style=" vertical-align: top;"><br><br>심화 내신테스트<hr>'.$quizlist3.'<br><br><hr>기본 내신테스트<hr>'.$quizlist2.'<br><br><hr>기초 내신테스트<hr>'.$quizlist1.'</td> <td></td> <td  style=" vertical-align: top; "><br><br><table>'.$preparing.'<tr><td><hr></td></tr>'.$unprepared.'</table></td></tr></tbody></table>
<br><hr><br>';
?>