<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
include("navbar.php");
 

if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentedittoday','$timecreated')");
  
$nweek= $_GET["nweek"]; 
$mode= $_GET["mode"]; 
$gtype= $_GET["gtype"]; 
$inputtext= $_GET["cntinput"]; 
if(strpos($gtype, '주간목표')!==false) $selectgtype2='selected';
else $selectgtype1='selected';

if($nweek==NULL)$nweek=15;
$timestart=$timecreated-604800*2;

$aweekago=$timecreated-604800;  
if($timecreated-$username->lastaccess>43200)$DB->execute("UPDATE {user} SET lastlogin='$timecreated' WHERE id LIKE '$studentid' ORDER BY id DESC LIMIT 1 ");  

// 최근 3주 기간 계산
$wtimestart1 = strtotime('monday this week 00:00:00'); // 이번 주 시작
$wtimestart2 = strtotime('monday last week 00:00:00'); // 지난 주 시작
$wtimestart3 = strtotime('monday -2 weeks 00:00:00'); // 2주 전 시작
$threeWeeksAgo = strtotime('-3 weeks 00:00:00'); // 최근 3주 전

$adayAgo=time()-43200; 
$goalhistory0 = '';
$goalhistory1 = '';
$goalhistory = ''; // 시간순으로 정렬된 모든 목표
$gptprep = '';
$recentactivities1 = '';
$recentactivities2 = '';

$newwbid='_user'.$studentid.'_date'.date('Y_m_d', $timecreated);
//echo '주시작: '.$wtimestart1.'<br>';
// 오늘목표: 최근 3주 동안 하루에 하나씩 (나중 입력)
// 최근 3주 동안의 모든 오늘목표를 날짜별로 그룹화하여 각 날짜의 마지막 항목만 가져오기
$todayGoalsRaw = $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type='오늘목표' OR type='검사요청') AND timecreated>='$threeWeeksAgo' ORDER BY timecreated DESC");
$todayGoalsByDate = array(); // 날짜별로 그룹화

// 오늘목표가 표시되는 날짜 범위 확인
$earliestDate = null;
$latestDate = null;
if($todayGoalsRaw) {
	foreach($todayGoalsRaw as $goal) {
		$dateKey = date('Y-m-d', $goal->timecreated); // 날짜를 키로 사용
		// 각 날짜의 첫 번째(가장 최근) 항목만 저장
		if(!isset($todayGoalsByDate[$dateKey])) {
			$todayGoalsByDate[$dateKey] = $goal;
		}
		// 날짜 범위 확인
		$goalDate = strtotime(date('Y-m-d', $goal->timecreated) . ' 00:00:00');
		if($earliestDate === null || $goalDate < $earliestDate) {
			$earliestDate = $goalDate;
		}
		if($latestDate === null || $goalDate > $latestDate) {
			$latestDate = $goalDate;
		}
	}
}

// 주간목표: 표시된 오늘목표 날짜 범위 내의 모든 주간목표 (날짜별로 나중 입력한 것만)
if($earliestDate !== null && $latestDate !== null) {
	// 날짜 범위를 약간 확장하여 경계 날짜의 주간목표도 포함
	$earliestDateStart = $earliestDate - 86400; // 하루 전부터
	$latestDateEnd = $latestDate + 86400; // 하루 후까지
} else {
	// 오늘목표가 없으면 최근 3주 범위 사용
	$earliestDateStart = $threeWeeksAgo;
	$latestDateEnd = time();
}

// 표시 범위 내의 모든 주간목표 가져오기
$weeklyGoalsRaw = $DB->get_records_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type='주간목표' AND timecreated>='$earliestDateStart' AND timecreated<='$latestDateEnd' ORDER BY timecreated DESC");

// 날짜별로 그룹화하여 각 날짜의 마지막(가장 최근) 주간목표만 저장
$weeklyGoalsByDate = array();
if($weeklyGoalsRaw) {
	foreach($weeklyGoalsRaw as $goal) {
		$dateKey = date('Y-m-d', $goal->timecreated);
		// 각 날짜의 첫 번째(가장 최근) 항목만 저장
		if(!isset($weeklyGoalsByDate[$dateKey])) {
			$weeklyGoalsByDate[$dateKey] = $goal;
		}
	}
}
$weeklyGoals = array_values($weeklyGoalsByDate);

// 주간목표와 오늘목표를 시간순으로 정렬하여 표시
// 위에서부터 아래 방향이 시간 순서가 되도록: 주간목표 이전의 오늘목표 -> 주간목표와 그 이후의 오늘목표

// 주간목표를 시간순으로 정렬 (오래된 것부터)
usort($weeklyGoals, function($a, $b) {
	return $a->timecreated - $b->timecreated;
});

// 먼저 주간목표 이전의 오늘목표들을 표시 (시간순)
if(!empty($weeklyGoals)) {
	$oldestWeeklyDateStart = strtotime(date('Y-m-d', $weeklyGoals[0]->timecreated) . ' 00:00:00');
	$todayGoalsBeforeWeekly = array();
	foreach($todayGoalsByDate as $todayGoal) {
		$todayGoalDateStart = strtotime(date('Y-m-d', $todayGoal->timecreated) . ' 00:00:00');
		if($todayGoalDateStart < $oldestWeeklyDateStart) {
			$todayGoalsBeforeWeekly[] = $todayGoal;
		}
	}
	// 시간순으로 정렬
	usort($todayGoalsBeforeWeekly, function($a, $b) {
		return $a->timecreated - $b->timecreated;
	});
	foreach($todayGoalsBeforeWeekly as $todayGoal) {
		$att = gmdate("m월 d일 ", $todayGoal->timecreated+32400);
		$daterecord = date('Y_m_d', $todayGoal->timecreated);
		$tend = $todayGoal->timecreated;
		$tfinish0 = date('m/d/Y', $todayGoal->timecreated+86400); 
		$tfinish = strtotime($tfinish0);
		$planwboardid = '_user'.$studentid.'_date'.$daterecord;
		$notetype = 'summary';
		$goaltype = '<b style="color:#333;">📌 오늘목표</b>';
		$rowHtml = '<tr style="border-bottom:1px solid #e0e0e0;background-color:#fff;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
		<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td style="text-align:center;width:20px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#333;"></span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($todayGoal->text,0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>'; 
		$goalhistory .= $rowHtml;
		$goalhistory0 .= $rowHtml;
		$gptprep .= $todayGoal->text.',';
		$recentactivities1 .= $todayGoal->text.'|';
	}
}

// 주간목표별로 그 이후의 오늘목표들을 그룹화하여 표시
foreach($weeklyGoals as $weeklyGoal) {
	// 주간목표 날짜의 시작 시간 (00:00:00)
	$weeklyGoalDateStart = strtotime(date('Y-m-d', $weeklyGoal->timecreated) . ' 00:00:00');
	
	// 주간목표 표시
	$att = gmdate("m월 d일 ", $weeklyGoal->timecreated+32400);
	$daterecord = date('Y_m_d', $weeklyGoal->timecreated);
	$tend = $weeklyGoal->timecreated;
	$tfinish0 = date('m/d/Y', $weeklyGoal->timecreated+86400); 
	$tfinish = strtotime($tfinish0);
	$planwboardid = '_user'.$studentid.'_date'.$daterecord;
	$notetype = 'weekly';
	$goaltype = '<b style="color:#bf04e0;">📅 주간목표</b>';
	$rowHtml = '<tr style="border-bottom:1px solid #e0e0e0;background-color:#f0e6ff;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
	<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td style="text-align:center;width:20px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#bf04e0;"></span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($weeklyGoal->text,0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>'; 
	$goalhistory .= $rowHtml;
	$goalhistory1 .= $rowHtml; // 기존 변수도 유지 (하위 호환성)
	$recentactivities2 .= $weeklyGoal->text.'|';
	
	// 다음 주간목표 날짜의 시작 시간 찾기
	$nextWeeklyDateStart = PHP_INT_MAX;
	foreach($weeklyGoals as $nextWeekly) {
		if($nextWeekly->timecreated > $weeklyGoal->timecreated) {
			$nextWeeklyDateStart_temp = strtotime(date('Y-m-d', $nextWeekly->timecreated) . ' 00:00:00');
			if($nextWeeklyDateStart_temp < $nextWeeklyDateStart) {
				$nextWeeklyDateStart = $nextWeeklyDateStart_temp;
			}
		}
	}
	
	// 이 주간목표 날짜(그날 00:00:00) 이후 ~ 다음 주간목표 날짜 이전의 오늘목표들
	$todayGoalsForThisWeek = array();
	foreach($todayGoalsByDate as $todayGoal) {
		$todayGoalDateStart = strtotime(date('Y-m-d', $todayGoal->timecreated) . ' 00:00:00');
		// 주간목표 날짜와 같은 날 또는 그 이후, 그리고 다음 주간목표 날짜 이전
		if($todayGoalDateStart >= $weeklyGoalDateStart && ($nextWeeklyDateStart == PHP_INT_MAX || $todayGoalDateStart < $nextWeeklyDateStart)) {
			$todayGoalsForThisWeek[] = $todayGoal;
		}
	}
	
	// 오늘목표들을 시간순으로 정렬
	usort($todayGoalsForThisWeek, function($a, $b) {
		return $a->timecreated - $b->timecreated;
	});
	
	// 오늘목표들 표시
	foreach($todayGoalsForThisWeek as $todayGoal) {
		$att = gmdate("m월 d일 ", $todayGoal->timecreated+32400);
		$daterecord = date('Y_m_d', $todayGoal->timecreated);
		$tend = $todayGoal->timecreated;
		$tfinish0 = date('m/d/Y', $todayGoal->timecreated+86400); 
		$tfinish = strtotime($tfinish0);
		$planwboardid = '_user'.$studentid.'_date'.$daterecord;
		$notetype = 'summary';
		$goaltype = '<b style="color:#333;">📌 오늘목표</b>';
		$rowHtml = '<tr style="border-bottom:1px solid #e0e0e0;background-color:#fff;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
		<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td style="text-align:center;width:20px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#333;"></span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($todayGoal->text,0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>'; 
		$goalhistory .= $rowHtml;
		$goalhistory0 .= $rowHtml; // 기존 변수도 유지 (하위 호환성)
		$gptprep .= $todayGoal->text.',';
		$recentactivities1 .= $todayGoal->text.'|';
	}
}

// 주간목표가 없으면 오늘목표만 시간순으로 표시
if(empty($weeklyGoals)) {
	$todayGoalsSorted = array_values($todayGoalsByDate);
	usort($todayGoalsSorted, function($a, $b) {
		return $a->timecreated - $b->timecreated;
	});
	foreach($todayGoalsSorted as $todayGoal) {
		$att = gmdate("m월 d일 ", $todayGoal->timecreated+32400);
		$daterecord = date('Y_m_d', $todayGoal->timecreated);
		$tend = $todayGoal->timecreated;
		$tfinish0 = date('m/d/Y', $todayGoal->timecreated+86400); 
		$tfinish = strtotime($tfinish0);
		$planwboardid = '_user'.$studentid.'_date'.$daterecord;
		$notetype = 'summary';
		$goaltype = '<b style="color:#333;">📌 오늘목표</b>';
		$rowHtml = '<tr style="border-bottom:1px solid #e0e0e0;background-color:#fff;"><td>&nbsp;&nbsp;</td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200&tf='.$tend.'" target=_blank">'.$goaltype.'</a></td><td>&nbsp;&nbsp;&nbsp; </td>
		<td>'.$att.'&nbsp;&nbsp;&nbsp;</td><td style="text-align:center;width:20px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#333;"></span></td><td><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_brainstorm.php?id='.$notetype.$planwboardid.'" target="_blank">'.substr($todayGoal->text,0,40).'</a></td><td>| <a href="https://mathking.kr/moodle/local/augmented_teacher/students/viewreplays.php?id='.$studentid.'&tfinish='.$tfinish.'&wboardid=today_user1087_date'.$daterecord.'&mode=mathtown" target=_blank">습관분석</a></td> </tr>'; 
		$goalhistory .= $rowHtml;
		$goalhistory0 .= $rowHtml;
		$gptprep .= $todayGoal->text.',';
		$recentactivities1 .= $todayGoal->text.'|';
	}
}

// $weeklyGoal과 $todayGoal 변수는 다른 곳에서도 사용되므로 최신 값으로 설정
// 가장 최근 주간목표 찾기 (timecreated가 가장 큰 것)
$weeklyGoal = null;
$latestWeeklyTime = 0;
foreach($weeklyGoals as $wg) {
	if($wg->timecreated > $latestWeeklyTime) {
		$latestWeeklyTime = $wg->timecreated;
		$weeklyGoal = $wg;
	}
}
// 가장 최근 오늘목표 찾기 (timecreated가 가장 큰 것)
$todayGoal = null;
$latestTodayTime = 0;
foreach($todayGoalsByDate as $tg) {
	if($tg->timecreated > $latestTodayTime) {
		$latestTodayTime = $tg->timecreated;
		$todayGoal = $tg;
	}
}
$recentactivities='주간목표들 : '.$recentactivities2.' <br> 실제 실행내용 : '.$recentactivities2;

$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz  LEFT JOIN mdl_quiz_attempts ON  mdl_quiz.id=mdl_quiz_attempts.quiz  
WHERE (mdl_quiz_attempts.timefinish > '$aweekago' OR mdl_quiz_attempts.timestart > '$aweekago' OR (state='inprogress' AND mdl_quiz_attempts.timestart > '$aweekago') ) AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.timestart ");
$quizresult = json_decode(json_encode($quizattempts), True);
$nquiz=count($quizresult);
$quizlist='<hr>';
$todayGrade=0;  $ntodayquiz=0;  $weekGrade=0;  $nweekquiz=0;
unset($value); 	
foreach(array_reverse($quizresult) as $value) 
{
$comment='';
$qnum=substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');   //if($role!=='student')
$comment= '&nbsp;|&nbsp;<a href="https://mathking.kr/moodle/local/augmented_teacher/students/cognitivism.php?id='.$studentid.'&attemptid='.$value['id'].'" target="_blank">결과분석</a>';
$quizgrade=round($value['sumgrades']/$value['tgrades']*100,0);
	 
	if($quizgrade>89.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
		}
	elseif($quizgrade>69.99)
		{
		$imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
		}
	else $imgstatus='<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
	$quizid=$value['quiz'];
	$moduleid=$DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'  "); 
	$quizmoduleid=$moduleid->id;

	$attemptid=$value['id'];
	$quizattempt= $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid'");
	$maxgrade=$quizattempt->maxgrade;
	if(strpos($value['name'], '내신')!= false)  
	{
	if(strpos($value['name'], 'ifminteacher')!= false) $value['name']=strstr($value['name'], '{ifminteacher',true);
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
		{
		$quizlist11.= '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$value['name'].'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'  </span> <br>';
		$todayGrade=$todayGrade+$quizgrade;
		$ntodayquiz++;
		}
	else 
		{
		$quizlist12.= '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.$value['name'].'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').' </span><br>';
		$weekGrade=$weekGrade+$quizgrade;
		$nweekquiz++;
		}
 	}elseif($qnum>9)  //$todayGrade  $ntodayquiz  $weekGrade  $nweekquiz
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)
		{
		$quizlist21.=  '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').' </span><br>';
		$todayGrade=$todayGrade+$quizgrade;
		$ntodayquiz++;
		}
	else 
		{
		$quizlist22.=  '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'  </span><br>';
		$weekGrade=$weekGrade+$quizgrade;
		$nweekquiz++;
		}
	}else
	{
	if($value['timestart']>$adayAgo || $value['timefinish']>$adayAgo)$quizlist31.= '오늘 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span><br>';
	else $quizlist32.= '지난 '.$imgstatus.'&nbsp;'.date("m/d | H:i",$value['timestart']).' |<a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.' " target="_blank">'.substr($value['name'],0,40).'</a>...('.$value['attempt'].get_string('trial', 'local_augmented_teacher').') <span class="" style="color: rgb(239, 69, 64);">...'.$quizgrade.get_string('points', 'local_augmented_teacher').'</span><br>';
	}
}

 
/*$todayplan=' <div class="col-md-7"><table width=90% align=center><tr><td><h6>시간분배</h6></td><td><h6><button   type="button"   id="alert_timeA"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(복습 '.$amountr.'분) <button   type="button"   id="alert_timeB"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(활동 '.$amountn.'분)   <button   type="button"   id="alert_timeC"  style = "background-color:white;color:grey;border:0;outline:0;" >⏰</button>(정리 '.$amountp.'분) </td>
<td><button   type="button"   id="alert_flywheel"  style = "background-color:white;color:black;border:0;outline:0;" ><h6>개선 <img style="padding-bottom:5px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/BESSI1578550323.png" width=15></h6></button>&nbsp;&nbsp;&nbsp;</td><td  style="padding-bottom:10px;"><button   type="button"   id="alert_gonextB"  style = "background-color:white;border:0;outline:0; " ><b  style="font-size:16;">중간점검 <img style="padding-bottom:10px;" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1633703416.png" width=25></b></button></td>  </tr></table>
<table width=90% align=center><tr><td>&nbsp;</td></tr></table><table width=90% align=center><tr><td>'.$ntext1.'</td></tr></table>';
*/


$fbtalk=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitivetalk where creator='$studentid' ORDER BY id DESC LIMIT 1 ");
$fbtype=$fbtalk->type;
$fburl='https://mathking.kr/moodle/local/augmented_teacher/students/wbfeedback.php?studentid='.$studentid.'&type='.$fbtype;
$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' ORDER BY id DESC LIMIT 1 ");
$lastday=$schedule->lastday;
$drawing=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='weekly' ORDER BY id DESC LIMIT 1 ");
$drawingid=$drawing->wboardid;
$lastday=$schedule->lastday;  
// $thistime: 최근 목표 (오늘목표 또는 주간목표) - 위에서 정의한 변수 사용
$thistime = $todayGoal ? $todayGoal : $weeklyGoal;
$lastGoal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND timecreated>='$wtimestart1' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1 ");
if($lastGoal) {
	$wgoaldate = date('Y-m-d', $lastGoal->timecreated);
	$weeklyGoalText='<span style="color:white;font-size=15;"><img src="http://mathking.kr/Contents/IMAGES/warning.png" width=40> &nbsp;지난 주 목표 : '.$lastGoal->text.'('.$wgoaldate.')(새로운 목표를 입력해 주세요)</span>';
} else {
	$weeklyGoalText = '';
}
// 위에서 이미 정의한 $weeklyGoal 변수 사용 (중복 쿼리 제거)
if($weeklyGoal && !empty($weeklyGoal->id)) {
	$weeklyGoalText='<h6> &nbsp;📅 주간목표 : '.$weeklyGoal->text.' ('.$lastday.')</h6>';
}
echo '<style>
#wrapper {
    border-style:solid;
    height:20px;
    width:200px;
    display:table-cell;
    vertical-align:bottom;
}
#dropdown { 
   width:80px;
} 
</style>';
if($hideinput==1)$status='checked';
// $checkgoal은 navbar.php에서 정의됨. null 체크 추가
if($checkgoal && isset($checkgoal->timecreated) && time()-$checkgoal->timecreated > 43200 && isset($checkgoal->comment) && $checkgoal->comment==NULL)
	{
	$placeholder='placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
	$presettext='';
	}
elseif($checkgoal && isset($checkgoal->timecreated) && time()-$checkgoal->timecreated > 43200 && isset($checkgoal->comment) && $checkgoal->comment!=NULL) 
	{
	$placeholder='';
	$presettext='value="'.$checkgoal->comment.'"';
	}
elseif($checkgoal && isset($checkgoal->text))
	{
	$placeholder='';
	$presettext='value="'.$checkgoal->text.'"';
	}
else
	{
	$placeholder='placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
	$presettext='';
	} 

if($inputtext!=NULL)$presettext='value="'.$inputtext.'"';
 
 
//$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND contentstitle='today' ORDER BY id DESC LIMIT 1 ");
//$summary=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where userid='$studentid' AND status='summary' AND timecreated>'$timeback' ORDER BY id DESC LIMIT 1 ");
$fullplan='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/weeklyplans.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.$termplan->id.'"target="_blank"><span style="color:white;font-size:16px;">🟦 전체계획</span></a>';
 
$deadline=date("Y:m:d",time());

$conditions=$DB->get_records_sql("SELECT * FROM mdl_abessi_knowhowlog WHERE studentid='$studentid' AND active='1' ORDER BY timemodified ");  
$conditionslist= json_decode(json_encode($conditions), True);
 
unset($value3);  
foreach($conditionslist as $value3)
	{
	$srcid=$value3['srcid']; 
	$item1=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE id='$srcid' ORDER BY id DESC LIMIT 1"); //선택유형
	$course=$item1->course; $type=$item1->type; $text=$item1->text; 
	$item2=$DB->get_record_sql("SELECT * FROM mdl_abessi_knowhow WHERE srcid='$srcid' AND active='1' ORDER BY id DESC LIMIT 1"); // 선택메뉴
	$text2=$item2->text; 

	if($mode==='CA' && $course==='개념미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CB' && $course==='심화미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CC' && $course==='내신미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	elseif($mode==='CD' && $course==='수능미션')$chosenitems.='<td><img src=https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1647313195.png width=15></td><td></td><td><b style="color:blue;">'.$type.'</b> | </td><td>'.$text.' | </td><td>'.$text2.' &nbsp;&nbsp;&nbsp;</td>';
	}

/*
$currentAnswer='SMART 목표 설정 방법에서 각 이니셜은 다음과 같은 의미를 갖습니다:

1. S - Specific (구체적인): 목표가 분명하고 명확해야 하며, 무엇을 달성하고자 하는지 구체적으로 정의해야 합니다.
2. M - Measurable (측정 가능한): 목표 진행 상황과 성과를 측정할 수 있어야 합니다. 이를 통해 목표 달성 여부를 확인하고 필요한 조정을 할 수 있습니다.
3. A - Achievable (달성 가능한): 목표는 도전적이면서도 실현 가능해야 합니다. 너무 쉽거나 불가능한 목표는 피해야 합니다.
4. R - Relevant (관련 있는): 목표는 당사자의 전반적인 목적이나 필요와 관련이 있어야 합니다. 이는 목표가 중요하고 의미 있는 것임을 보장합니다.
5. T - Time-bound (시간 제한이 있는): 목표에는 구체적인 기한이 설정되어야 합니다. 이를 통해 우선순위를 정하고, 목표 달성을 위한 계획을 세울 수 있습니다.
 ';
 HARD 목표는 다음과 같다:
-Heartfelt(마음에서 진심으로 우러난 목표)
: 나의 목표는 내 주변 사람들의 삶을 충족하게 할 것이다
(고객, 동료, 가족 등).
-Animated(생기 넘치는 목표)
: 나는 나의 목적을 성취하였을 때, 그 성취감이 얼마나 클지
상상할 수 있다.
-Required(필수 목표)
: 나의 목표는 회사의 발젂에 기여하는데 젃대적으로 필요하다.
-Difficult(힘겨운 목표)
: 나는 올해의 목표를 성취하기 위해서 새로운 기술을 익히고
내 자싞의 한계를 넘어서야 할 것이다.

 */
 
if($thistime && isset($thistime->type) && $thistime->type==='주간목표')
	{
	$displaytext= '🌟 랜덤 드림 챌린지 : '.$termplan->dreamchallenge.'!  당신의 꿈을 응원합니다 !  (D-'.$dreamdday.'일)';
	$currentAnswer=$displaytext;
	$rolea='💎 드림챌린지';
	$roleb='💎 GPT도우미';
	$talka1='';
	$talkb1='';
	$talka2='';
	$talkb2='';
	$tone1='';
	$tone2='';
	}
else
	{//$recentactivities.' <hr>
	$weeklyGoalText_display = ($weeklyGoal && isset($weeklyGoal->text)) ? $weeklyGoal->text : '';
	$todayGoalText_display = ($todayGoal && isset($todayGoal->text)) ? $todayGoal->text : '';
	$displaytext= '📅 주간목표 : '.$weeklyGoalText_display.' 📌 오늘목표 : '.$todayGoalText_display.'입니다. (🏳️분기목표 : '.$EGinputtime.'까지 '.$termMission.')';
	$currentAnswer='주간목표 : '.$weeklyGoalText_display.'를 위해 오늘목표 : '.$todayGoalText_display.'로 설정하였습니다. 과정에 대해 이해를 돕기 위해 학생입장에서 예상되는 과정에 대한 상세한 설명이 필요합니다.';
	$rolea='💎 마이 플랜';
	$roleb='💎 GPT도우미';
	$talka1='';
	$talkb1='';
	$talka2='';
	$talkb2='';
	$tone1='';
	$tone2='';
	}	

if($thistime && isset($thistime->timecreated) && isset($thistime->type) && $thistime->timecreated>time()-10 && $thistime->type==='주간목표') {
	$showreflection='<iframe  class="foo"  style="border: 0px none; z-index:2; width:85vw; height:20vh;margin-left:0px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/brainalignment.php?userid='.$studentid.'&answerShort=true&count=5&currentAnswer='.$currentAnswer.'&rolea='.$rolea.'&roleb='.$roleb.'&talka1='.$talka1.'&talkb1='.$talkb1.'&talka2='.$talka2.'&talkb2='.$talkb2.'&tone1='.$tone1.'&tone2='.$tone2.'" ></iframe>';
} elseif($thistime && isset($thistime->timecreated) && isset($thistime->type) && $thistime->timecreated>time()-1800 && $thistime->type==='오늘목표') {
	$showreflection='<iframe  class="foo"  style="border: 0px none; z-index:2; width:85vw; height:20vh;margin-left:0px;margin-top:0px; overflow-x: hidden;overflow-y: hidden;"    src="https://mathking.kr/moodle/local/augmented_teacher/LLM/brainalignment.php?userid='.$studentid.'&answerShort=true&count=5&currentAnswer='.$currentAnswer.'&rolea='.$rolea.'&roleb='.$roleb.'&talka1='.$talka1.'&talkb1='.$talkb1.'&talka2='.$talka2.'&talkb2='.$talkb2.'&tone1='.$tone1.'&tone2='.$tone2.'" ></iframe>';
} else {
	$showreflection='<p align=center><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/headerimg1119.png" width=100%></p>';
}
//else $showreflection='<p align=center><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/BESSI1617694317001.png" width=100%></p>';
 //https://mathking.kr/moodle/local/augmented_teacher/IMAGES/activelearning.png
 echo ' 
					<div class="row">
						<div class="col-md-12">
							 
							<div class="card">							
								<div class="card-body"><!--user foreach to show recent 20 inputs-->
								'.$showreflection.'
								';
if($hideinput==0 || $role!=='student') echo '<table><tr>'.$chosenitems.'</tr></table><table class="table table-head-bg-primary mt-4">
										<thead>
											<tr>
												<th width=10% scope="col">'.$fullplan.' </th><th width=50% scope="col" >'.$weeklyGoalText.'</th><th   width=10%  scope="col"></th><th   width=10%   scope="col"><button style="font-size:16px;width:80%;height:40px;background-color:#d6e6ff;color:black;" onclick="remindMath('.$studentid.');">복습계획</button></th>
											</tr>
										</thead></table>										
										<table class="table table-head-bg-primary mt-4">
										<tbody>
											<tr>
												<td style="width:10%;"><a style="color:black;font-size:16px;" href="https://mathking.kr/moodle/local/augmented_teacher/students/dailygoals.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.($weeklyGoal && isset($weeklyGoal->id) ? $weeklyGoal->id : 0).'">🟪목표</a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/todayplans.php?id='.$studentid.'&cid='.$chapterlog->cid.'&pid='.($thistime && isset($thistime->id) ? $thistime->id : ($todayGoal && isset($todayGoal->id) ? $todayGoal->id : 0)).'&nch='.$chapterlog->nch.'"target="_blank"><span style="color:black;font-size:16px;">🟩활동</span></a></td>
												<td style="width:40%;">
													<input type="text" class="form-control input-square" id="squareInput" name="squareInput" style="width:100%;" '.$placeholder.' '.$presettext.'>
												</td>
												<td style="width:15%;">
													<div class="select2-input" style="font-size: 2.0em; padding-top:15px;">
														<select id="basic1" name="basic" class="form-control">
															<option value="오늘목표" '.$selectgtype1.'>오늘목표</option>
															<option value="주간목표" '.$selectgtype2.'>주간목표</option>
															<option value="시간접기" '.$selectgtype3.'>시간접기</option>
														</select>
													</div>
												</td>
												<td style="width:15%;">
													<div class="select2-input" style="font-size: 2.0em; padding-top:1px;">
														<select id="basic2" name="basic2" class="form-control">
															<option value="1">개념공부</option>
															<option value="2" selected>심화학습</option>
															<option value="3">내신대비</option>
															<option value="4">기타</option>
														</select>
													</div>
												</td>
												<td style="width:10%;">
													<input type="text" class="form-control" id="datepicker" name="datepicker" placeholder="데드라인" value="'.$deadline.'">
												</td>
												<td style="width:10%;" valign="bottom">
													<button type="button" id="update" style="width:100px; height:40px;" onclick="edittoday(2,'.$studentid.',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val());">저장하기</button>
												</td>
											 
											</tr>
										</tbody>
									</table>';
else echo '<table align=center><tr><td style="color:red;font-size:20;text-align:center;">담당 선생님과 함께 계획을 입력해 주세요 ! </td></tr></table>';

if($hideinput==0 || $role!=='student') echo '									
								</div>
							</div>
						</div><div class="col-md-7"><table width=80% align=center><tr><td align=center><b style="font-size:16px;">최근 목표들</b></td></tr><tr><td><br><br>';

echo '<table width=100% style="border-collapse:collapse;">'.$goalhistory.'</table>';
include("index_embed.php");

echo '</td></tr></table></div>
<div class="col-md-5">
<table><tr><td></td><td><h6>지난 시간 요약 내용을 발표 후 오늘 목표를 입력해 주세요 ! &nbsp;&nbsp;&nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/students/dailylog.php?id='.$studentid.'&nweek=16" target="_blank"><img src="https://mathking.kr/Contents/MATH MATRIX/MATH images/IMG/MXBESSI1621861054001.png" width=15></a></h6></td></tr></table><table width=100%ss><tr><td valign=top>';

echo '</td></tr></table><table width=100%><tr><td>내신테스트<br><br>'.$quizlist11.''.$quizlist12.'<hr>표준테스트<br><br>'.$quizlist21.''.$quizlist22.'<hr>인지촉진<br><br>'.$quizlist31.''.$quizlist32.'</td></tr><tr><td>';
include("schedule_embed.php");
echo '</td></tr></table> </div> ';

echo ' 
				</div>
			</div>
		 </div>';
$nextgoal= $DB->get_record_sql("SELECT id,comment FROM  mdl_abessi_today Where userid='$studentid' AND timecreated<'$timeback' AND timecreated>'$aweekago' ORDER BY id DESC LIMIT 1 ");
$nextplan=$nextgoal->comment;
/*
if($nextplan!=NULL && $checkgoal->id==NULL)echo '<script>
				{
				var Plan=\''.$nextplan.'\';
				const Toast = Swal.mixin({
				  toast: true,
				  position: "top",
				
				  showConfirmButton: true,
				  timer: 50000,
				  timerProgressBar: true,
				  didOpen: (toast) => {
				    toast.addEventListener("mouseenter", Swal.stopTimer)
				    toast.addEventListener("mouseleave", Swal.resumeTimer)

					// 클립보드에 Plan 변수의 값을 복사
					navigator.clipboard.writeText(Plan).then(function() {
						console.log("클립보드에 복사 성공");
					}, function(err) {
						console.error("클립보드에 복사 실패: ", err);
					});
					swal("", "오늘 목표가 복사되었습니다", {buttons: false,timer: 1000});
				  }
				})

				Toast.fire({
				
				  title: " 다음 계획 : " + Plan,
				  icon: "success"
				            }) 
				}



			</script>';
*/
if($inputtext!==NULL)echo '<script>setTimeout(function(){document.getElementById("update").click();}, 1000);</script>';
include("quicksidebar.php");
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

 
	<script>
	window.onbeforeunload = function () {
		window.scrollTo(0, 0);
		}
	  document.getElementById("squareInput").addEventListener("keydown", function(event) {
		if (event.keyCode === 13) {
		  document.getElementById("update").click();
		}
	  });
 $(\'#alert_flywheel\').click(function(e) {
					var Userid= \''.$studentid.'\';	 
					var text1="평가준비 1단계 (오답은 직접 풀기, 보류문제는 해설지 참고 <a href=https://docs.google.com/presentation/d/1NrNmjFLBgSxAMrTLJPnUQtmrkOPySc6C4hbMmh3BLeg/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text2="평가준비 2단계 (순서도로 풀이 계획 수립 후 단계별 풀이 <a href=https://docs.google.com/presentation/d/1TsWdvyEIL4624Xlu2VEJzv8QPqF1ypVcb_1hNX_Xp_Y/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text3="평가준비 3단계 (탭을 열어 두고 알 때까지 생각하기 <a href=https://docs.google.com/presentation/d/1IupopPHUA5wueb1lsh92alpID0uc1yBIzWg3gyPk9zQ/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text4="서술평가 1단계 (평가준비 과정에서 발견된 약점 쓰기 <a href=https://docs.google.com/presentation/d/1y87eWTnFvp0xjJF0xpq1R_nGty0kke7klxcFlHS2t_4/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text5="서술평가 2단계 (막힘없이 풀기 <a href=https://docs.google.com/presentation/d/1BT8lvfsxc_IuTkzvx4fzyEsy8VjhL4KEJ8KXhGNodAE/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text6="서술평가 3단계 (발상촉진 유형 선택 연습하기 <a href=https://docs.google.com/presentation/d/16qVgQRd82vSb_DkzE87XXpzMFUfc4SGIfKoDtg1HKfU/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text7="부스 1단계 논리훈련 (발상촉진 내용 작성하기 <a href=https://docs.google.com/presentation/d/11OkF_76XATgrxA-n3EVzagf_TqTifxr_vG5kNAlf4IU/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text8="부스 2단계 단계형성 (발상촉진 내용 체화하기 <a href=https://docs.google.com/presentation/d/1XehaiVxMtDGh969OnBF8wd4BzM8VFIDLSCgO_IddgfQ/present#slide=id.p target=_blank>자세히</a>) <hr>";
					var text9="부스 3단계 생각계단 (발상촉진 내용과 연관 논리요소 연결하기 <a href=https://docs.google.com/presentation/d/1I3uRJMkx-nq7WJXF54mcoyYftv2t3mcE22s4AB4xEOk/present#slide=id.p target=_blank>자세히</a>) <hr>";
				 
			swal("공부법 단계 선택하기",  "현재 자신의 단계를 선택하고 방법을 익힌 다음 실천해 보세요.",{
				
			  buttons: {
			    catch1: {
			      text: "평가준비 1단계 : 오답은 직접 풀기, 보류문제는 해설지 참고",
			      value: "catch1",className : \'btn btn-default\'
				
			    },
			    catch2: {
			      text: "평가준비 2단계 : 순서도로 풀이 계획 수립 후 단계별 풀이",
			      value: "catch2",className : \'btn btn-default\'
			    },
			    catch3: {
			      text: "평가준비 3단계 : 탭을 열어 두고 알 때까지 생각하기",
			      value: "catch3",className : \'btn btn-default\'
			    },
			    catch4: {
			      text: "서술평가 1단계 : 평가준비 과정을 참고하여 오답원인 쓰기",
			      value: "catch4",className : \'btn btn-default\'
			    },
			    catch5: {
			      text: "서술평가 2단계 : 도움없이 막히지 않고 풀이 완성하기",
			      value: "catch5",className : \'btn btn-default\'
			    },
			    catch6: {
			      text: "서술평가 3단계 : 풀이과정 중 부스터 스탭 유형 선택하기",
			      value: "catch6",className : \'btn btn-default\'
			    },
			    catch7: {
			      text: "부스터 스텝 1단계 : 선택한 논리요소 작성하기",
			      value: "catch7",className : \'btn btn-default\'
			    },
			    catch8: {
			      text: "부스터 스텝 2단계 : 논리요소 반복훈련 실행하기",
			      value: "catch8",className : \'btn btn-default\'
			    },
			    catch9: {
			      text: "부스터 스텝 3단계 : 단원의 연관 논리요소와 연결하기",
			      value: "catch9",className : \'btn btn-default\'
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
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'1\',
					"inputtext":text1,	
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch2":
 			     swal("OK !","안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'2\',
					"inputtext":text2,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch3":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'3\',
					"inputtext":text3,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 		
 			   case "catch4":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'4\',
					"inputtext":text4,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch5":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'5\',
					"inputtext":text5,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break;
 			   case "catch6":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'6\',
					"inputtext":text6,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch7":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'7\',
					"inputtext":text7,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch8":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'8\',
					"inputtext":text8,
					"userid":Userid,
					},
					success:function(data){
					 }
					 })
			    location.reload();
 			    break; 	
 			   case "catch9":
 			     swal("OK !", "안전하게 전달되었습니다.", "success");
					$.ajax({
					url:"check_today.php",
					type: "POST",
					dataType:"json",
				 	data : {
					"eventid":\'31\',
					"feedbackid":\'9\',
					"inputtext":text9,
					"userid":Userid,
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
	 
		function edittoday(Eventid,Userid,Inputtext,Type,Level,Deadline)
				{
				var Planwboardid = \'' .$newwbid. '\';
				var Ptype;

				if (Type === "오늘목표") {
					Ptype = "today";
				} else if (Type === "주간목표") {
					Ptype = "weekly";
				}
 
				swal({	text: \'목표가 설정되었습니다\',buttons: false,})
			     	     $.ajax({
		     		            url:"database.php",
				     	     type: "POST",
		            		     dataType:"json",
 			  		     data : {
					     "eventid":Eventid,
					     "userid":Userid,
		      		             "inputtext":Inputtext,
		       		             "type":Type,
		       		             "level":Level,
					     "deadline":Deadline,
		         		     },
		            	        success:function(data){  		
								var Cntid = data.cntid;			
			       			      }
		  		      }) 
				 
					setTimeout(function() {
						window.open("https://mathking.kr/moodle/local/augmented_teacher/students/edittoday.php?id="+Userid, "_self");
					}, 1000);
 
 
				} 
		function remindMath(Userid) {
			var Eventid = 46;
			Swal.fire({
				title: "주간복습",
				html: `
				<div>
					<button id="chapter1" class="swal2-confirm swal2-styled" style="margin:5px; font-size:14px;">한 단원</button>
					<button id="chapter2" class="swal2-confirm swal2-styled" style="margin:5px; font-size:14px;">두 단원</button>
					<button id="chapter3" class="swal2-confirm swal2-styled" style="margin:5px; font-size:14px;">세 단원</button>
					<button id="directInput" class="swal2-confirm swal2-styled" style="margin:5px; font-size:14px;">입력</button>
				</div>
				<div style="margin-top:10px;">
					<input type="range" min="10" max="180" step="5" value="10" id="duration-slider" style="width:100%;">
					<p>시간: <span id="duration-value">10</span> 분</p>
				</div>
			`,
			showConfirmButton: false,
			showCancelButton: false,
			didOpen: () => {
				const slider = Swal.getPopup().querySelector("#duration-slider");
				const output = Swal.getPopup().querySelector("#duration-value");
				output.textContent = slider.value;
				slider.addEventListener("input", function() {
					output.textContent = this.value;
				});

				const chapter1Btn = Swal.getPopup().querySelector("#chapter1");
				const chapter2Btn = Swal.getPopup().querySelector("#chapter2");
				const chapter3Btn = Swal.getPopup().querySelector("#chapter3");
				const directInputBtn = Swal.getPopup().querySelector("#directInput");

				chapter1Btn.addEventListener("click", () => {
					const duration = slider.value;
					if (duration < 10) {
						Swal.fire("", "최소 10분 이상의 시간을 선택해 주세요.", { showConfirmButton: false, timer: 1500 });
					} else {
						Swal.close();
						sendData("한 단원", duration);
					}
				});

				chapter2Btn.addEventListener("click", () => {
					const duration = slider.value;
					if (duration < 10) {
						Swal.fire("", "최소 10분 이상의 시간을 선택해 주세요.", { showConfirmButton: false, timer: 1500 });
					} else {
						Swal.close();
						sendData("두 단원", duration);
					}
				});

				chapter3Btn.addEventListener("click", () => {
					const duration = slider.value;
					if (duration < 10) {
						Swal.fire("", "최소 10분 이상의 시간을 선택해 주세요.", { showConfirmButton: false, timer: 1500 });
					} else {
						Swal.close();
						sendData("세 단원", duration);
					}
				});

					directInputBtn.addEventListener("click", () => {
						Swal.close();
						Swal.fire({
							title: "직접입력하기",
							input: "text",
							inputAttributes: {
								placeholder: "내용을 입력해 주세요",
								id: "input-field",
								class: "form-control"
							},
							showCancelButton: true,
							confirmButtonText: "확인",
							cancelButtonText: "취소",
						}).then((result) => {
							if (result.isConfirmed) {
								const inputText = result.value;
								sendData(inputText);
							} else {
								Swal.fire("취소되었습니다.", { showConfirmButton: false, timer: 1500 });
							}
						});
					});
				}
			});

			function sendData(inputText,duration) {
			 
				$.ajax({
					url: "check.php",
					type: "POST",
					dataType: "json",
					data: {
						"eventid": Eventid,
						"userid": Userid,
						"inputtext": inputText,
						"duration": duration
					},
					success: function (data) {
						Swal.fire("저장되었습니다.", "", "success");
					},
					error: function () {
						Swal.fire("오류가 발생했습니다.", "", "error");
					}
				});
			}
		}

		$("#datetime").datetimepicker({
			format: "MM/DD/YYYY H:mm",
		});
		$("#datepicker").datetimepicker({
			format: "YYYY/MM/DD",
		});
		 
		$("#timepicker").datetimepicker({
			format: "h:mm A", 
		});

		$("#basic").select2({
			theme: "bootstrap"
		});

		$("#basic1").select2({
			theme: "bootstrap"
		});

		$("#multiple").select2({
			theme: "bootstrap"
		});

		$("#multiple-states").select2({
			theme: "bootstrap"
		});

		$("#tagsinput").tagsinput({
			tagClass: "badge-info"
		});

		$( function() {
			$( "#slider" ).slider({
				range: "min",
				max: 100,
				value: 40,
			});
			$( "#slider-range" ).slider({
				range: true,
				min: 0,
				max: 500,
				values: [ 75, 300 ]
			});
		} );
	</script>


</body>';
$pagetype='edittoday';
include("../LLM/postit.php");
?>
