<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

include("navbar.php");

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$USER->id' "); 
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
if($tsymbol1==NULL)$tsymbol1='KTM';
if($tsymbol2==NULL)$tsymbol2='KTM';
if($tsymbol3==NULL)$tsymbol3='KTM';
 

echo ' 

<div class="main-panel"><div class="content"  style="overflow-x: hidden" ><div class="row"><div class="col-md-12">';
///////////////// begin of table /////////////////// 
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
$wtimestart=$timecreated-86400*($nday+3);

 
$sssskey= sesskey(); 
$hourAgo2=time()-7200;
$hoursago3=time()-10800;
$aweekAgo=time()-604800;
$monthsago2=time()-6048000; // 10주 전
$wblist2='';
$nratewb=0;$nratewb2=0;
$nview=0;
$totalgrade1=0;$totalgrade2=0;
$nstudents1=0;$nstudents2=0; 

$mystudents=$DB->get_records_sql("SELECT id, lastname, firstname FROM mdl_user WHERE  suspended=0 AND  lastaccess> '$amonthago'  AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
$userlist= json_decode(json_encode($mystudents), True);

$attendance='';
$timecreated=time();
$halfdayago=time()-43200;
$timedelayed1=time()-30;
$timedelayed2=time()-300;
unset($user);
foreach($userlist as $user)
	{
	$studentid=$user['id'];
	$todayon=0;
 	$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 AND (timecreated < date OR date=0) ORDER BY id DESC LIMIT 1 ");
	$starttext='start'.$nday;
	if(empty($schedule->$starttext)==0)$todayon=1;
 
if($todayon==1) 
	{
	$Timelastaccess=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$studentid' ");  
	$timeafter=time()-$Timelastaccess->maxtc;

	$firstname=$user['firstname'];
	$lastname=$user['lastname'];
 	$studentname=$firstname.$lastname;

	$tbegin1=date("H:i",strtotime($schedule->$starttext)); // 시작 시간
	$time1    = explode(':', $tbegin1);
	$minutes1 = ($time1[0] * 60.0 + $time1[1] * 1.0)-30;

	$tbegin2=date("H:i",$timecreated);  // 현재 시간
	$time2    = explode(':', $tbegin2);
	$minutes2 = ($time2[0] * 60.0 + $time2[1] * 1.0)-30;

	$wtimestart=$timecreated-86400*($nday+3);
	$examGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$monthsago2' AND type LIKE '시험목표' ORDER BY id DESC LIMIT 1 ");
	$weeklyGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>'$wtimestart' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
	$checkgoal= $DB->get_record_sql("SELECT  * FROM  mdl_abessi_today WHERE userid='$studentid' AND timecreated > '$halfdayago' AND (type LIKE '오늘목표' OR type LIKE '검사요청' ) ORDER BY id DESC LIMIT 1 ");
 
	if($examGoal->id==NULL)$prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623709525001.png';
	elseif($weeklyGoal->id==NULL){$prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623743285001.png'; $studentname='주간목표X';}
	elseif($checkgoal->id==NULL)$prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623709553001.png';
	elseif($checkgoal->rtext1==NULL && $checkgoal->ntext1==NULL && $checkgoal->ptext1==NULL )$prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623744971001.png';
	else $prepareimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623709595001.png';

	if($minutes1<$minutes2 && $timeafter>=43200)$attendance1.='<b>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.' <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623742228001.png width=15></a></b>'; 
	elseif($timeafter < 7200) $attendance2.='&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$studentname.' <img src='.$prepareimg.' width=25></a>'; 
	elseif($timeafter>=43200)$attendance3.='<b>&nbsp;&nbsp;&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$lastname.' <img src='.$prepareimg.' width=25></a></b>'; 
	elseif($timeafter < 43200 && $timeafter>=7200) $attendance4.=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$lastname.' <img src='.$prepareimg.' width=25></a>'; 
		


	if($timeafter<7200)  // active user 들에 대한 정보만 가지고 오기
		{
	$bro=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='44' "); 
	$aca=$DB->get_record_sql("SELECT data  FROM mdl_user_info_data where userid='$studentid' AND fieldid='46' "); 
	$moreclass=$DB->get_record_sql("SELECT data  FROM mdl_user_info_data where userid='$studentid' AND fieldid='75' "); 
	$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524995001.png';
	if(empty($bro->data)==1 && empty($aca->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624524941001.png';
	elseif(empty($bro->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624525057001.png';
	elseif(empty($aca->data)==1)$editicon='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624525057001.png';
	$classimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1627359032001.png';
	if(empty($moreclass->data)==1)$classimg='https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596870001.png';
	$missionlog= $DB->get_record_sql("SELECT * FROM  mdl_abessi_missionlog WHERE userid='$studentid' AND eventid=17 ORDER BY id DESC LIMIT 1 ");
	$recentcurl='https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?'.$missionlog->url;

	 $gtoday1 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid' AND todayscore > 0  AND timecreated>'$halfdayago' ORDER BY id DESC LIMIT 1 ");   // 오늘 수업 평점
	if($gtoday1->id!=NULL)
		{
		$totalgrade1=$totalgrade1+$gtoday1->todayscore; 
		 $nstudents1++;
		//if($gtoday1->usedtime<80)$daytype='<td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1621591765001.png" width=20"></td>';
		}

	$gtoday2 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid' AND todayscore > 0 AND timecreated>'$aweekAgo' ORDER BY id DESC LIMIT 1 ");   // 주간 수업 평점
	if($gtoday2->id!=NULL)
		{
		$totalgrade2=$totalgrade2+$gtoday2->todayscore; 
		$nstudents2++;
		}


 

		$todayplan='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# <b>주간목표 : '.$weeklyGoal->text.'</b><hr>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; # 오늘목표 : '.$checkgoal->text.' <hr>
		<div class="container"  > 귀가시간 : '.$tcomplete.'  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$tstudy.' 시간 경과
	   	<hr>
	    	<h6> 복습하기 ... (예상시간 '.$amountr.'분 | '.$rtime.'까지)</h6>
	    	<ul class="sessions">
	     	<li>
	        	<div class="time">'.$rtext1.$rtext2.$rtext3.'</div>
	      	<p></p>
	    	</li>
	     	</ul><hr>
	    	<h6> 나아가기 ... (예상시간 '.$amountn.'분 | '.$ntime.'까지)</h6>
	    	<ul class="sessions">
	     	 <li>
	        	<div class="time">'.$ntext1.$ntext2.$ntext3.'</div>
	   	<p></p>
	      	</li>
	   	 </ul><hr>
	    	<h6> 정리하기 ... (예상시간 '.$amountp.'분 | '.$ptime.'까지)</h6>
	    	<ul class="sessions">
	      	<li>
	        	<div class="time">'.$ptext1.$ptext2.$ptext3.'</div>
	     	 </li>
	   	 </ul><hr>
		<table width=100%><tr><td># 형제관계 : '.$bro->data.' </td><td> # 다른 과목 : '.$aca->data.'</td></tr></table>
		</div>  ';
 
 		$wblist1='';
		$engagement1 = $DB->get_record_sql("SELECT timecreated FROM  mdl_abessi_missionlog WHERE  userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // missionlog
		$engagement2 = $DB->get_record_sql("SELECT timecreated FROM  mdl_logstore_standard_log WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // mathkinglog
		$engagement3 = $DB->get_record_sql("SELECT * FROM  mdl_abessi_indicators WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");  // abessi_indicators 
		
		$tlastaction=time()-max($engagement1->timecreated,$engagement2->timecreated,$engagement3->tlaststroke);		 

		// AI 알고리즘 적용 LEVEL 1 ON/OFF는 이곳에서 LEVEL 1, 2, 3, 4, 5 간의 switching은 board_onair.php에서.. '이태상 원장 강의실'을 분원마다 만들고 튜터, 상담사와 함께 운영한다. 상담사는 근무지를 옮기면서 근무한다.
		// 학습지도를 위한 창을 열고 닫는 것이 핵심 trigger 알고리즘. 
		/*
 		if($USER->id==2)	
			{
			$aistep=$engagement3->aistep;
			$aion=$engagement3->aion;
			
			if($tlastaction<1800 )$DB->execute("UPDATE {abessi_indicators} SET aistep=1 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
			elseif($tlastaction>1800 && $aistep==1)$DB->execute("UPDATE {abessi_indicators} SET aistep=0 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
		
			if(($aistep==1 && $aion==0) || ($aion==1 && time()-$engagement3->tlastview >600 ) && $checkgoal->submit==0) // 새로운 관찰학생 onair 열기 , 닫는 것은 onair page에서 수행
				{
				$DB->execute("UPDATE {abessi_indicators} SET aion=1 WHERE userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
				echo '<script>
					 var Userid= \''.$studentid.'\';
 					 $(document).ready(function(){
    					 window.open("https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid="+Userid+"&mode=0", "_blank"); // will open new tab on document ready
					 });
				        </script>';
				}
			}
		*/
		$stayfocused=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$studentid'  ORDER BY id DESC LIMIT 1 ");
		$lastUrl=$stayfocused->context.'?'.$stayfocused->currenturl;

		$stayfocused2=$DB->get_record_sql("SELECT * FROM mdl_abessi_stayfocused where userid='$studentid' AND status=3 ORDER BY id DESC LIMIT 1 ");
		$lastwbUrl=$stayfocused2->context.'?'.$stayfocused2->currenturl;

		if($stayfocused->status==1)$statusmark='○';if($stayfocused->status==2)$statusmark='◎';if($stayfocused->status==3)$statusmark='●';

		$topicnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%jnrsorksqcrark_user%' AND (status LIKE 'begintopic' OR status LIKE 'complete' OR status LIKE 'review') AND timemodified >'$halfdayago'  ORDER BY timemodified ASC LIMIT 1");
		$questionnote=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND teacher_check NOT LIKE 2 AND wboardid LIKE '%nx4HQkXq_user%' AND (status LIKE 'complete' OR status LIKE 'review') AND timemodified >'$halfdayago'  ORDER BY timemodified ASC LIMIT 1");

		$lastwbUrl2='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$topicnote->wboardid;
		$lastwbUrl3='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$questionnote->wboardid;
	
 		$prevNview=$nview;
		if($tlastaction<180)$nview++;
		$viewname='view'.$nview;
		if($prevNview!=$nview)$$viewname='https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0';		 

		$wblist0='<td><a href="'.$lastUrl.'" target="_blank">'.$statusmark.'</a></td>';
 		if($topicnote->id==NULL)$wblist0.='<td>□</td>';   
		elseif($topicnote->status==='begintopic')$wblist0.='<td><a href="'.$lastwbUrl2.'" target="_blank">▣</a></td>';   
		else 
			{	
			$wblist0.='<td><a href="'.$lastwbUrl2.'" target="_blank">■</a></td>';
 			$nratewb2=$nratewb2+1;
			if($nratewb2==1)$open6=$lastwbUrl2;
			if($nratewb2==2)$open7=$lastwbUrl2;
			if($nratewb2==3)$open8=$lastwbUrl2;
			if($nratewb2==4)$open9=$lastwbUrl2;
			if($nratewb2==5)$open10=$lastwbUrl2;
			//  자동평점 알고리즘 >> 학생의 최근 1주일 평균 필기수 중 30획이상인 화이트보드들의 평균을 기준으로 이상과 이하로 나누어서 알고리즘에 반영

 			if($USER->id==2 && ($topicnote->status==='complete' || $topicnote->status==='review') ) 
				{
				$strokeNum=(INT)($topicnote->nstroke/2);
				$attitude=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='60' ");  // 자세 성숙도
				$nattitude=$attitude->data; // 이부분을 주간목표 성취도 평균값으로 업데이트

				$responsetime=300;

				if($strokeNum>50 && time()-$topicnote->tlaststroke>$responsetime)
					{ 
					$randnum=mt_rand(0, 1);
					if($topicnote->depth<3)$randnum=mt_rand(1, 2);
					$nstar=$topicnote->depth + $randnum;
					//if($topicnote->status==complete)
					//elseif($topicnote->status==review)$nstar=4 + $randnum; 
					if($nstar>5)$nstar=5;
					$DB->execute("UPDATE {abessi_messages} SET userto='2', teacher_check=2, star='$nstar'  WHERE wboardid='$topicnote->wboardid' "); 
					}
				else
					{
					$randnum=mt_rand(-1, 0);
					if($topicnote->depth<3)$randnum=mt_rand(0, 1);
					$nstar=$topicnote->depth + $randnum; 
					$DB->execute("UPDATE {abessi_messages} SET userto='2', teacher_check=2, star='$nstar'  WHERE wboardid='$topicnote->wboardid' "); 
					}
				}
			}
 		if($questionnote->id==NULL)$wblist0.='<td>□</td>';   
		else 
			{
			$wblist0.='<td><a href="'.$lastwbUrl3.'" target="_blank">■</a></td>';
 			$nratewb=$nratewb+1;
			if($nratewb==1)$open1=$lastwbUrl3;
			if($nratewb==2)$open2=$lastwbUrl3;
			if($nratewb==3)$open3=$lastwbUrl3;
			if($nratewb==4)$open4=$lastwbUrl3;
			if($nratewb==5)$open5=$lastwbUrl3;

			
			//  자동평점 알고리즘 >> 학생의 최근 1주일 평균 필기수 중 30획이상인 화이트보드들의 평균을 기준으로 이상과 이하로 나누어서 알고리즘에 반영

 			if($USER->id==2) 
				{
				$strokeNum=(INT)($questionnote->nstroke/2);
				$attitude=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$studentid' AND fieldid='60' ");  // 자세 성숙도
				$nattitude=$attitude->data; // 이부분을 주간목표 성취도 평균값으로 업데이트

				$responsetime=300;

				if($strokeNum>50 && time()-$questionnote->tlaststroke>$responsetime)
					{ 
					$randnum=mt_rand(0, 1);
					if($questionnote->depth<3)$randnum=mt_rand(1, 2);
					$nstar=$questionnote->depth + $randnum;
					//if($questionnote->status==complete)
					//elseif($questionnote->status==review)$nstar=4 + $randnum; 
					if($nstar>5)$nstar=5;
					$DB->execute("UPDATE {abessi_messages} SET userto='2', teacher_check=2, star='$nstar'  WHERE wboardid='$questionnote->wboardid' "); 
					}
				else
					{
					$randnum=mt_rand(-1, 0);
					if($questionnote->depth<3)$randnum=mt_rand(0, 1);
					$nstar=$questionnote->depth + $randnum; 
					$DB->execute("UPDATE {abessi_messages} SET userto='2', teacher_check=2, star='$nstar'  WHERE wboardid='$questionnote->wboardid' "); 
					}
				 
				}
			}
		// 화이트보드 목록 가져오기

		$wbd=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE  userid='$studentid' AND (status LIKE 'ask' OR status LIKE 'studentreply')  ORDER BY tlaststroke DESC LIMIT 1");
 
			$encryption_id=$wbd->wboardid;
			$status=$wbd->status;

			$timemodified=time()-$wbd->timemodified;
			if($timemodified>60)$timemodifiedtext=(INT)($timemodified/60).'분전';
			else $timemodifiedtext=(time()-$wbd->timemodified).'초전';

			$tlaststroke=time()-$wbd->tlaststroke;
	
			if($tlaststroke>1000000)$tlaststroketext='비어있음';
			elseif($tlaststroke>60)$tlaststroketext=(INT)($tlaststroke/60).'분전  마지막 필기';
			else $tlaststroketext=(time()-$wbd->tlaststroke).'초전  마지막 필기';

	
 			$contentsid=$wbd->contentsid;
			if($wbd->contentstype==2)
				{
				$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
				$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();

				foreach($imageTags as $imageTag)
					{
    					$questionimg = $imageTag->getAttribute('src');
					$questionimg = str_replace(' ', '%20', $questionimg); 
					if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
					}
				$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
				$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
				}
			if($wbd->contentstype==1)
				{
				$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
				$ctext=$getimg->pageicontent;
				$htmlDom = new DOMDocument;
				@$htmlDom->loadHTML($ctext);
				$imageTags = $htmlDom->getElementsByTagName('img');
				$extractedImages = array();
				$nimg=0;
				foreach($imageTags as $imageTag)
					{
					$nimg++;
	    				$imgSrc = $imageTag->getAttribute('src');
					$imgSrc = str_replace(' ', '%20', $imgSrc); 
					if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
					}

				$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 				$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$studentid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
				}
 
			$thisattempt = $DB->get_record_sql("SELECT * FROM  mdl_question_attempts LEFT JOIN mdl_question_attempt_steps ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
			WHERE mdl_question_attempt_steps.timecreated > '$hoursago3' AND  mdl_question_attempts.questionid='$contentsid' AND mdl_question_attempt_steps.userid='$studentid'   ORDER BY mdl_question_attempt_steps.timecreated DESC LIMIT 1");

 			if($thisattempt->state==='todo' )$attemptstep=' ▷'; 	 
 			elseif($thisattempt->state==='complete' )$attemptstep=' <img src="https://mathking.kr/Contents/Moodle/Visual%20arts/complete2.png" width=15>'; 
 			elseif($thisattempt->state==='gradedpartial' )$attemptstep=' <img src="https://mathking.kr/Contents/Moodle/Visual%20arts/partial2.png" width=15>';	 
 			elseif($thisattempt->state==='gradedwrong' )$attemptstep=' <img src="https://mathking.kr/Contents/Moodle/Visual%20arts/wrong2.png" width=15>'; 
 			elseif($thisattempt->state==='gaveup' )$attemptstep=' <img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1622530052001.png" width=15>';
 			elseif($thisattempt->state==='gradedright' )$attemptstep=' <img src="https://mathking.kr/Contents/Moodle/Visual%20arts/right2.png" width=15>';
			//elseif($thisattempt->state==NULL)$attemptstep='&nbsp;○&nbsp;';
 
			 $imgstatus='';
			include("../whiteboard/status_icons.php");
	
			if($tlaststroke<60)$imgstatus='<img src="https://c.tenor.com/UyX1ahhXlh0AAAAC/study-assignment.gif" width=25>◆'; 
			$wbtype='board.php';
 
			if($status!=NULL)$wblist1='&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/'.$wbtype.'?id='.$encryption_id.'" target="_blank"><div class="tooltip2">'.$attemptstep.' '.$imgstatus.'<span class="tooltiptext2"><table style="" align=center><tr><td align=center>'.$tlaststroketext.' | '.$timemodifiedtext.' 업데이트 <hr>'.$questiontext.'</td></tr></table></span></div></a> | ';
 		 	 
		// onair 가져오기
 
		$helpme=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE teacher_check NOT LIKE 2 AND userid='$studentid'  AND tlaststroke > '$aweekAgo' AND (onair='1' && teacher_check NOT LIKE '2') ORDER BY tlaststroke ASC LIMIT 1");
		if($helpme->id!=NULL)
			{
 			$contentsid=$helpme->contentsid;
			if($helpme->contentstype==2)
				{ 
				$qtext = $DB->get_record_sql("SELECT questiontext FROM mdl_question WHERE id='$contentsid' ");
				$htmlDom = new DOMDocument; @$htmlDom->loadHTML($qtext->questiontext); $imageTags = $htmlDom->getElementsByTagName('img'); $extractedImages = array();

				foreach($imageTags as $imageTag)
					{
    					$questionimg = $imageTag->getAttribute('src');
					$questionimg = str_replace(' ', '%20', $questionimg); 
					if(strpos($questionimg, 'MATRIX/MATH')!= false || strpos($questionimg, 'HintIMG')!= false)break;
					}
				$questiontext='<img src="'.$questionimg.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
				$contentslink='<a href="https://mathking.kr/moodle/question/preview.php?courseid=9&id='.$contentsid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603205245001.png width=15></a>';
			 	}
			if($helpme->contentstype==1)
				{
				$getimg=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
				$ctext=$getimg->pageicontent;
				$htmlDom = new DOMDocument;
				@$htmlDom->loadHTML($ctext);
				$imageTags = $htmlDom->getElementsByTagName('img');
				$extractedImages = array();
				$nimg=0;
				foreach($imageTags as $imageTag)
					{
					$nimg++;
	    				$imgSrc = $imageTag->getAttribute('src');
					$imgSrc = str_replace(' ', '%20', $imgSrc); 
					if(strpos($imgSrc, 'MATRIX')!= false || strpos($imgSrc, 'MATH')!= false || strpos($imgSrc, 'imgur')!= false || strpos($imgSrc, 'HintIMG')!= false)break;
					}

				$questiontext='<img src="'.$imgSrc.'" width=500>'; //substr($qtext->questiontext, 0, strpos($qtext->questiontext, "답선택"));
 				$contentslink='<a href="https://mathking.kr/moodle/mod/icontent/view.php?id='.$cmid.'&pageid='.$contentsid.'&userid='.$studentid.'" target="_blank" ><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1603204904001.png width=15></a>';
				} 
			$wblist2.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/bessiboard/board.php?id=bessi'.$helpme->wboardid.'" target="_blank"><div class="tooltip2"> <b>'.$lastname.'</b>의 질문<span class="tooltiptext2"><table style="" align=center><tr><td align=center>'.$tlaststroketext.' | '.$timemodifiedtext.' 업데이트 <hr>'.$questiontext.'</td></tr></table></span></div></a> &nbsp;</td>';
			}
  	 
 
		$mark5='<span class="" style="color: rgb(0, 0, 0);"><b> '.$studentname.'</b></span>';

		// count the number of questions of today.
		$maxtime=time()-10800;
		$recentquestions = $DB->get_records_sql("SELECT mdl_question_attempt_steps.id FROM mdl_question_attempt_steps  LEFT JOIN mdl_question_attempts ON mdl_question_attempts.id=mdl_question_attempt_steps.questionattemptid 
		WHERE mdl_question_attempt_steps.userid='$studentid' AND (mdl_question_attempt_steps.state='gradedwrong' OR mdl_question_attempt_steps.state='gradedpartial' OR  mdl_question_attempt_steps.state='gradedright')  AND mdl_question_attempt_steps.timecreated > '$maxtime'  ORDER BY mdl_question_attempt_steps.id DESC ");
		$Qnum1=count($recentquestions);
 
		 
		// evaluate carefulness of user
 
 		$ratio1=$engagement3->todayscore;
	
		if($ratio1<70)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayD.png';
		elseif($ratio1<75)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayC.png';
		elseif($ratio1<80)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayCplus.png';
		elseif($ratio1<85)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayB.png';
		elseif($ratio1<90)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayBplus.png';
		elseif($ratio1<95)$imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayA.png';
		elseif($ratio1<=100) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/todayAplus2.png';
		else $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
		if(  $engagement3->todayscore==NULL ||  $engagement3->todayscore==0) $imgtoday='https://mathking.kr/Contents/Moodle/Visual%20arts/noattempt.png';
		  
	 
	 
		$location=$DB->get_record_sql("SELECT ip FROM mdl_logstore_standard_log where userid='$studentid' AND action='loggedin'  ORDER BY timecreated DESC LIMIT 1"); 
		if(strpos($location->ip, '254')!= false)
 			{
			$location2='KTM';
			if(time()-$Ctext03->timestamp<3600*24*28)$location2='<span class="" style="color: rgb(0, 0, 255);">KTM</span>';
			}
		else
			{	
			 $location2='외부';
			if(time()-$Ctext03->timestamp<3600*24*28)$location2='<span class="" style="color: rgb(0, 0, 255);">OUT</span>';
			}

 
	 

	
		// recent quiz list ********************************************************************************************** 
		 
		$recentquiz = $DB->get_records_sql("SELECT mdl_quiz_attempts.timestart AS timestart, mdl_quiz.name AS name, mdl_quiz_attempts.id AS id,  mdl_quiz_attempts.attempt AS attempt, mdl_quiz.sumgrades AS tgrades,mdl_quiz_attempts.sumgrades AS sgrades,mdl_course_modules.id AS quizid FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz LEFT JOIN mdl_course_modules ON mdl_course_modules.instance=mdl_quiz.id  WHERE mdl_quiz_attempts.userid='$studentid'  AND mdl_quiz_attempts.timestart>'$maxtime' ORDER BY mdl_quiz_attempts.timestart DESC LIMIT 2");
		$quizrslt= json_decode(json_encode($recentquiz), True);
		$quizinfo='';
		 
		unset($value);
		foreach($quizrslt as $value)
			{
			$qzid=$value['id'];
			$qzname=$value['name'];
			$qzgrade=round($value['sgrades']/$value['tgrades']*100,0); 
			$stateimg=$value['state'];
			if($value['sgrades']==NULL)$stateimg=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$qzid.'" target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1622020119001.png" width=18></a>';
			else $stateimg=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$qzid.'" target="_blank"><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623493957001.png" width=18></a>';
 			$quizinfo.='<td><a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$qzid.' " target="_blank">'.substr($qzname,0,17).$qzgrade.'점)</a>'.$stateimg.'</td>';
			}
		/////////////////////////////// 2-5. prepare tooltip for personal information
 	
		if($tlastaction<60)$num=1;
		elseif($tlastaction<120)$num=2;
		elseif($tlastaction<180)$num=3;
		elseif($tlastaction<240)$num=4;			
		elseif($tlastaction<300)$num=5;
		elseif($tlastaction<420)$num=6;
		elseif($tlastaction<600)$num=7;
		elseif($tlastaction<900)$num=8;
		elseif($tlastaction<1200)$num=9;
		elseif($tlastaction<1500)$num=10;
		elseif($tlastaction<1800)$num=11;
		elseif($tlastaction<2400)$num=12;
		elseif($tlastaction<3000)$num=13;
		elseif($tlastaction<3600)$num=14;
		elseif($tlastaction<5400)$num=15;
		elseif($tlastaction<7200)$num=16;
		elseif($tlastaction<9000)$num=17;
		elseif($tlastaction<10800)$num=18;
		else $num=19;

		$today='today'.$num;
		$$today.= '<tr><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$studentid.'&mode=0" target="_blank"><img src="'.$imgtoday.'" width=20></a> <div class="tooltip3"><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$studentid.'&tb=604800" target="_blank" >'.$mark5.'</a><span class="tooltiptext3">    
		<table align="center" style="width: 100%;">
      	                        
             		 <tr>
           		   <th scope="col"></th>
              	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">월요일</span></b></h5></th>
             		 <th scope="col" align="left" ><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">화요일</span></b></h5></th>
               	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">수요일</span></b></h5></th>
               	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">목요일</span></b></h5></th>
               	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(51, 51, 51);">금요일</span></b></h5></th>
               	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(42, 100, 211);">토요일</span></b></h5></th>
               	<th scope="col" align="left"><h5><b><span class="" align="right"  style="color: rgb(239, 69, 64);">일요일</span></b></h5></th>
                	</tr>
                    		 
 		<tr>
             		 <td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>시작</b>&nbsp;&nbsp; &nbsp;</td>
           	   	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start1.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start2.'</td>
             		 <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start3.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start4.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start5.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start6.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->start7.'</td>
             		</tr>
              	<tr>
              	<td style="text-align: right; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><b>시간</b>&nbsp;&nbsp; &nbsp;</td>
             		 <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);"><span style="font-size: 12.44px;">'.$schedule->duration1.'</span></td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration2.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration3.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration4.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration5.'</td>
              	<td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration6.'</td>
             		  <td style="text-align: left; border-width: 1px; border-style: none; border-color: rgb(255, 255, 255);">'.$schedule->duration7.'</td>
              	 </tr>                    		
              	 <tr><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td><td><hr></td></tr>
             		  </table> <table width=80%><tr><td>'.$todayplan.' </td></tr></table></span> </div> </td><td> '.round($tlastaction/60,0).'"</td><td width=5%><a href="https://mathking.kr/moodle/message/index.php?id='.$studentid.' " target="_blank" >
		<img src="https://images-eu.ssl-images-amazon.com/images/I/51o-qd2E1PL.png" width=25></a></td>'.$wblist0.'<td><a href="'.$recentcurl.'"target="_blank"><img src='.$classimg.' width=20></a> &nbsp; &nbsp;</td><td align=left >'.$wblist1.'</td><td>&nbsp;&nbsp; </td><td style="text-align: left;"><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629598213001.png width=20>  ('.$Qnum1.') 목표, 오답, 미응답, 요약, 시간, 시간표, 상담, 공부법</td><td> | '.$quizinfo.'</td><td> 오늘 '.$gtoday1->todayquizave.'점 </td><td> 최근 '.$gtoday1->weekquizave.'점</td><td>'.$v_quiz.'</td><td>'.$location2.'</td><td>  <a href="https://mathking.kr/moodle/user/editadvanced.php?id='.$studentid.'" target="_blank"><img src='.$editicon.' width=15></a></td>
		</tr>';
		}	
	}
}  // end of student loop

if($nstudents1>0 && $nstudents1 <300)
	{
	$todayGrade1=(INT)($totalgrade1/$nstudents1);	
 	$DB->execute("UPDATE {abessi_indicators_class} SET quizscoretoday='$todayGrade1' WHERE id> 0 AND teacherid='$teacherid'  ORDER BY id DESC LIMIT 1 ");	
	}
if($nstudents2>0 && $nstudents2 <300)
	{
	$todayGrade2=(INT)($totalgrade2/$nstudents2);		
 	$DB->execute("UPDATE {abessi_indicators_class} SET quizscore='$todayGrade2'  WHERE id> 0 AND teacherid='$teacherid' ORDER BY id DESC LIMIT 1 ");	
	}



// 오답노트 평가
$evaltext='서술평가<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623835512001.png height=30>';
if(empty($open1)==1){$openurls='';$evaltext='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623590412001.png" height=15>'; }  // COMPLETE
elseif(empty($open2)==1)$openurls='window.open(Open1);';
elseif(empty($open3)==1)$openurls='window.open(Open1);window.open(Open2);';
elseif(empty($open4)==1)$openurls='window.open(Open1);window.open(Open2);window.open(Open3);';
elseif(empty($open5)==1)$openurls='window.open(Open1);window.open(Open2);window.open(Open3);window.open(Open4);';
else $openurls='window.open(Open1);window.open(Open2);window.open(Open3);window.open(Open4); window.open(Open5);';

$viewallimg='<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1624791079001.png height=30>'; 
if(empty($view1)==1){$viewurls='';}
elseif(empty($view2)==1)$viewurls='window.open(View1);';
elseif(empty($view3)==1)$viewurls='window.open(View1);window.open(View2);';
elseif(empty($view4)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);';
elseif(empty($view5)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4);';
elseif(empty($view6)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);';
elseif(empty($view7)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);window.open(View6);';
elseif(empty($view8)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);window.open(View6);window.open(View7);';
elseif(empty($view9)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);window.open(View6);window.open(View7);window.open(View8);';
elseif(empty($view10)==1)$viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);window.open(View6);window.open(View7);window.open(View8);window.open(View9);';
else $viewurls='window.open(View1);window.open(View2);window.open(View3);window.open(View4); window.open(View5);window.open(View6);window.open(View7);window.open(View8);window.open(View9); window.open(View10);';
 
// 개념평가
$evaltext2='개념평가<img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623835512001.png height=30>';
if(empty($open6)==1){$openurls2='';$evaltext2='<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623590412001.png" height=15>'; }  // COMPLETE
elseif(empty($open7)==1)$openurls2='window.open(Open6);';
elseif(empty($open8)==1)$openurls2='window.open(Open6);window.open(Open7);';
elseif(empty($open9)==1)$openurls2='window.open(Open6);window.open(Open7);window.open(Open8);';
elseif(empty($open10)==1)$openurls2='window.open(Open6);window.open(Open7);window.open(Open8);window.open(Open9);';
else $openurls2='window.open(Open6);window.open(Open7);window.open(Open8);window.open(Open9); window.open(Open10);';
 
echo '<script>
var Open1= \''.$open1.'\'; 
var Open2= \''.$open2.'\'; 
var Open3= \''.$open3.'\'; 
var Open4= \''.$open4.'\'; 
var Open5= \''.$open5.'\'; 

var Open6= \''.$open6.'\'; 
var Open7= \''.$open7.'\'; 
var Open8= \''.$open8.'\'; 
var Open9= \''.$open9.'\'; 
var Open10= \''.$open10.'\'; 

var View1= \''.$view1.'\'; 
var View2= \''.$view2.'\'; 
var View3= \''.$view3.'\'; 
var View4= \''.$view4.'\'; 
var View5= \''.$view5.'\'; 
var View6= \''.$view6.'\'; 
var View7= \''.$view7.'\'; 
var View8= \''.$view8.'\'; 
var View9= \''.$view9.'\'; 
var View10= \''.$view10.'\'; 
</script>';
 
$afewminutesago=time()-1800;
$assess='';
 /*
$stealth=$DB->get_records_sql("SELECT * FROM mdl_question_attempt_steps WHERE timecreated>'$afewminutesago' AND state='complete'  ORDER BY id "); 
$result2 = json_decode(json_encode($stealth), True);
unset($value2);
foreach($result2 as $value2) 
	{
	$stepid=$value2['id'];
	$userid=$value2['userid'];
	$qstnatmptid=$value2['questionattemptid'];
	$finalresult=$DB->get_record_sql("SELECT * FROM mdl_question_attempt_steps WHERE  questionattemptid='$qstnatmptid'  ORDER BY id DESC LIMIT 1"); 
	if($finalresult->state==='complete')
		{
		$atmptid=$DB->get_record_sql("SELECT * FROM mdl_question_attempt_step_data WHERE  attemptstepid='$stepid' AND name LIKE 'p1' ORDER BY id DESC LIMIT 1 "); 
		$ans=$atmptid->value;
		$atmpt=$DB->get_record_sql("SELECT * FROM mdl_question_attempts  WHERE id='$qstnatmptid' ORDER BY id DESC LIMIT 1 "); 
		$rightanswer=substr($atmpt->rightanswer, 0, strrpos($atmpt->rightanswer, ' '));


		if(strpos($rightanswer,$ans)===false)		
			{		
			$nslot=$atmpt->slot;
			$atmptboard='Q7MQFA'.$atmpt->questionid.'0tsDoHfRT_user'.$userid.'_'.date("Y_m_d", time());
			$std=$DB->get_record_sql("SELECT id, lastname, firstname FROM mdl_user WHERE id='$userid' ORDER BY id DESC LIMIT 1 ");
			$stdname=$std->lastname;
		  	if(strpos($std->firstname,$tsymbol)!= false)$assess.=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800" target="_blank" >'.$stdname.'</a> &nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$atmptboard.' "target="_blank">(<img src=https://mathking.kr/Contents/Moodle/Visual%20arts/pill3.png width=15> '.$nslot.'번)</a> | ';
			}
		}
	}
 */
echo '<table width=100% style="white-space: nowrap; text-overflow: ellipsis;"><tbody>'.$today1.$today2.$today3.$today4.$today5.$today6.$today7.$today8.$today9.$today10.$today11.$today12.$today13.$today14.$today15.$today16.$today17.$today18.$today19;	
echo '</tbody></table><hr><table width=100%><tr><td width=2.5%><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596540001.png" height=28></td><td align=left><table><tr><td> &nbsp;&nbsp;&nbsp;</td>'.$assess.'</tr></table></td><td width=10%><a href="#"  onclick="'.$viewurls.'" >'.$viewallimg.'</a></td><td width=10%><a href="#"  onclick="'.$openurls2.'" >'.$evaltext2.'</a></td><td width=10%><a href="#"  onclick="'.$openurls.'" >'.$evaltext.'</a></td></tr></table>
<hr><table width=100%><tr><td width=2.5%><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1629596326001.png" height=28></td><td align=left><table><tr><td> &nbsp;&nbsp;&nbsp;</td>'.$wblist2.'</tr></table></td> </tr></table>
<hr> <img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623825420001.png height=25>'.$attendance3.' <hr><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623829638001.png height=28>'.$attendance1.$attendance2.'<hr><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/MXBESSI1623829771001.png width=28> &nbsp;'.$attendance4.'<hr>';

/////////////////////////////// /////////////////////////////// End of active users /////////////////////////////// /////////////////////////////// 
 			echo '</div>
										<div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
											<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>
											<p>The Big Oxmox advised her not to do so, because there were thousands of bad Commas, wild Question Marks and devious Semikoli, but the Little Blind Text didn?셳 listen. She packed her seven versalia, put her initial into the belt and made herself on the way.
											</p>
										</div>
										<div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
											<p>Pityful a rethoric question ran over her cheek, then she continued her way. On her way she met a copy. The copy warned the Little Blind Text, that where it came from it would have been rewritten a thousand times and everything that was left from its origin would be the word "and" and the Little Blind Text should turn around and return to its own, safe country.</p>

											<p> But nothing the copy said could convince her and so it didn?셳 take long until a few insidious Copy Writers ambushed her, made her drunk with Longe and Parole and dragged her into their agency, where they abused her for their</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
	include("quicksidebar.php");
 
echo ' 
 
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