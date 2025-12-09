<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
$timecreated=time();
$tablemode= $_GET['tablemode']; 
$viewmode=$_GET['viewmode']; 
require_login();

// tb 파라미터가 없으면 기본값 7일 사용
$period=optional_param('tb', 7, PARAM_INT); // get_record from $period ago
$periodp=$period+7;
$periodm=$period-7;

$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$teacherid','teachertimetable','$timecreated')");
echo '<meta http-equiv="refresh" content="600">';
$nusers=0;$allusedtime=0;
//$weektotal=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;
$tlastaccess=time()-604800*30;
$halfdayago=time()-43200;
$aweekago=time()-604800;
$amonthago6=time()-604800*30;
$timestart=date("Y-m-d", time());
$dayunixtime=strtotime($timestart)-100;
$dayunixtime2=strtotime($timestart)+86400-100;
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday=jddayofweek($jd,0);
if($nday==0)$nday=7;
if($viewmode==='change' && ($teacherid=='$USER->id' || $teacherid==2))$DB->execute("UPDATE {abessi_mystudents} SET suspended='1' WHERE teacherid LIKE '$teacherid' ");

// 주 시작일 파라미터 처리 (없으면 현재 주)
$week_param = optional_param('week', '', PARAM_TEXT);
if (!empty($week_param)) {
    $week_start_date = $week_param;
} else {
    // 현재 주의 시작일(월요일) 계산
    $current_date = date('Y-m-d');
    $day_of_week = date('w', strtotime($current_date));
    if ($day_of_week == 0) {
        $day_of_week = 7;
    }
    $days_to_monday = $day_of_week - 1;
    $week_start_date = date('Y-m-d', strtotime("-$days_to_monday days", strtotime($current_date)));
}

// 주별 이동 링크 생성 (지난주부터 최근 12주, 이번 주 제외) - 버튼 스타일, 한 행에 표시
$week_links = '';
$current_week_start = strtotime($week_start_date);
// 지난주(1)부터 최근 12주(12주) = 총 12주, 이번 주(0)는 제외
for ($i = 1; $i <= 12; $i++) {
    $target_week = date('Y-m-d', strtotime("-$i weeks", $current_week_start));
    $week_display = date('m/d', strtotime($target_week));
    $is_selected = ($target_week == $week_start_date);
    
    // 버튼 스타일 설정
    if ($is_selected) {
        $button_style = 'background-color: #2196F3; color: white; border: 2px solid #1976D2; box-shadow: 0 2px 4px rgba(0,0,0,0.2);';
    } else {
        $button_style = 'background-color: #f5f5f5; color: #333; border: 1px solid #ddd;';
    }
    
    $week_links .= '<a href="?id=' . $teacherid . '&week=' . $target_week . '" style="' . $button_style . ' display: inline-block; padding: 8px 12px; margin: 3px; text-decoration: none; border-radius: 5px; font-size: 13px; font-weight: 500; transition: all 0.3s ease; cursor: pointer; white-space: nowrap;" onmouseover="this.style.backgroundColor=\'' . ($is_selected ? '#1976D2' : '#e0e0e0') . '\'; this.style.transform=\'scale(1.05)\';" onmouseout="this.style.backgroundColor=\'' . ($is_selected ? '#2196F3' : '#f5f5f5') . '\'; this.style.transform=\'scale(1)\';">' . $week_display . '</a>';
}

if($tablemode==NULL)
	{
	// academy 변수 확인 및 설정 (navbar.php에서 설정되지만, 없을 경우를 대비)
	if (!isset($academy) || empty($academy)) {
		$mngid = ($role === 'manager') ? $teacherid : $USER->id;
		$academy_info = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = '46'", array($mngid));
		$academy = $academy_info ? $academy_info->data : '';
	}
	
	$newuser = null;
	if (isset($academy) && !empty($academy) && isset($tsymbol) && !empty($tsymbol)) {
		$newuser= $DB->get_record_sql("SELECT * FROM mdl_user WHERE  institution='$academy' AND timecreated>'$halfdayago' AND firstname LIKE '%$tsymbol%' ORDER BY id DESC LIMIT 1"); 
		if($newuser && $newuser->id!=NULL)$newuserinfo='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$newuser->id.'&eid=1&nweek=4">'.$newuser->firstname.$newuser->lastname.'</a>';
	}
 
	// 학생 목록 조회
	$mystudents = array();
	if (isset($academy) && !empty($academy) && isset($tsymbol) && !empty($tsymbol)) {
		$mystudents=$DB->get_records_sql("SELECT * FROM mdl_user WHERE institution LIKE '$academy' AND lastaccess> '$amonthago6' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  
	}
	
	$size=0;
	$nwau=0;
	$result = $mystudents ? json_decode(json_encode($mystudents), True) : array();
	unset($user);
	
	// 페이지 방문 통계 표 시작
	echo '<div style="padding-top: 100px; padding-bottom: 20px;">'; // 상단 헤더를 피하기 위해 padding-top 추가
	echo '<div style="width: 70%; margin: 0 auto; text-align: center;">';
	echo '<h2>학생별 주간 페이지 방문 통계 (주 시작일: ' . htmlspecialchars($week_start_date) . ')</h2>';
	
	// 주별 이동 링크 (버튼 스타일, 한 행에 표시)
	echo '<div style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
	echo '<div style="color: white; font-size: 16px; font-weight: bold; margin-bottom: 12px; text-align: center;">📅 주별 이동</div>';
	echo '<div style="text-align: center; white-space: nowrap; overflow-x: auto; padding: 5px 0;">' . $week_links . '</div>';
	echo '</div>';
	
	echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 20px;">';
	echo '<thead>';
	echo '<tr style="background-color: #f0f0f0;">';
	echo '<th style="text-align: center; white-space: nowrap; width: auto;">학생명</th>';
	echo '<th style="text-align: center;">일정<br>(schedule)</th>';
	echo '<th style="text-align: center;">계획<br>(plan)</th>';
	echo '<th style="text-align: center;">일지<br>(diary)</th>';
	echo '<th style="text-align: center;">오늘<br>(today)</th>';
	echo '<th style="text-align: center;">귀가검사<br>(daily_report)</th>';
	echo '<th style="text-align: center;">합계</th>';
	echo '<th style="text-align: center;">복사</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	
	// 학생이 없을 때 메시지 표시
	if (empty($result)) {
		echo '<tr><td colspan="8" style="text-align: center; padding: 20px;">조회된 학생이 없습니다.</td></tr>';
	}
	
	foreach($result as $user)
		{
		$userid=$user['id']; 
		$lastaccesstime=round((time()-$user['lastaccess'])/86400,0);
		if($user['suspended']==0)
			{
			$size++;
			if($viewmode==='change')
				{
				$DB->execute("UPDATE {abessi_indicators} SET teacherid='$teacherid' WHERE userid='$userid' ORDER BY id DESC LIMIT 1 ");  
				$DB->execute("UPDATE {user} SET teacherid='$USER->id'  WHERE id='$userid'   ORDER BY id DESC LIMIT 1");  
				$exist=$DB->get_record_sql("SELECT * FROM mdl_abessi_mystudents where teacherid LIKE '$teacherid' AND studentid LIKE '$userid' ORDER BY id DESC LIMIT 1");  
				if($exist->id==NULL)$DB->execute("INSERT INTO {abessi_mystudents} (teacherid,studentid,timemodified,timecreated) VALUES('$teacherid','$userid','$timecreated','$timecreated')");
				else $DB->execute("UPDATE {abessi_mystudents} SET suspended='0', timemodified='$timecreated' WHERE teacherid LIKE '$teacherid' AND studentid LIKE '$userid' ORDER BY id DESC LIMIT 1");  
				}
			if($user['lastlogin']>$aweekago)$nwau++;
			else 
				{
				$pinnedusers.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >&nbsp;&nbsp;'.$user['firstname'].$user['lastname'].'</a> |';
				}
			if(strpos($user['firstname'],$tsymbol)!==false)$nusers++;
			
			// 해당 학생의 주간 페이지 방문 통계 조회
			$page_stats = $DB->get_record_sql(
				"SELECT * FROM mdl_abessi_weekly_page_stats 
				 WHERE user_id = ? AND week_start_date = ?",
				array($userid, $week_start_date)
			);
			
			// 통계 값 설정 (없으면 0)
			$schedule_count = $page_stats ? intval($page_stats->schedule_count) : 0;
			$plan_count = $page_stats ? intval($page_stats->plan_count) : 0;
			$diary_count = $page_stats ? intval($page_stats->diary_count) : 0;
			$today_count = $page_stats ? intval($page_stats->today_count) : 0;
			$daily_report_count = $page_stats ? intval($page_stats->daily_report_count) : 0;
			$total_count = $schedule_count + $plan_count + $diary_count + $today_count + $daily_report_count;
			
			// 학생명 표시
			$student_name = $user['firstname'] . $user['lastname'];
			$student_lastname = $user['lastname'];
			$student_url = 'https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id=' . $userid . '&tb=43200';
			
			// 짧은 URL 생성 또는 가져오기
			$short_url_hash = '';
			try {
				// 기존 짧은 URL이 있는지 확인
				$existing_short = $DB->get_record_sql(
					"SELECT hash FROM mdl_short_urls 
					 WHERE original_url = ? 
					 AND (expired_at IS NULL OR expired_at > NOW())
					 ORDER BY id DESC LIMIT 1",
					array($student_url)
				);
				
				if ($existing_short) {
					$short_url_hash = $existing_short->hash;
				} else {
					// 새로운 해시 생성 (4자리)
					$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$max_attempts = 50;
					$attempt = 0;
					$hash = '';
					
					do {
						$hash = '';
						for ($i = 0; $i < 4; $i++) {
							$hash .= $characters[mt_rand(0, strlen($characters) - 1)];
						}
						$duplicate = $DB->get_record_sql("SELECT id FROM mdl_short_urls WHERE hash = ?", array($hash));
						$attempt++;
					} while ($duplicate && $attempt < $max_attempts);
					
					if (!$duplicate) {
						// 데이터베이스에 저장
						$record = new stdClass();
						$record->hash = $hash;
						$record->original_url = $student_url;
						$record->created_at = date('Y-m-d H:i:s');
						$record->expired_at = null;
						$record->click_count = 0;
						
						$insert_id = $DB->insert_record('short_urls', $record);
						if ($insert_id) {
							$short_url_hash = $hash;
						}
					}
				}
			} catch (Exception $e) {
				// 에러 발생 시 원본 URL 사용
				error_log("짧은 URL 생성 실패 (파일: " . __FILE__ . ", 라인: " . __LINE__ . "): " . $e->getMessage());
			}
			
			// 메시지에 사용할 URL (짧은 URL이 있으면 사용, 없으면 원본 URL)
			$message_url = $short_url_hash ? 'https://mathking.kr/moodle/s.php?h=' . $short_url_hash : $student_url;
			
			// 메시지 템플릿 (URL 포함)
			$message_template = "안녕하세요, 카이스트 터치수학학원입니다. 최근 {$student_lastname}의 학습일지입니다. 참고 해주시고 더 자세한 내용은 학부모앱({$message_url})에서 확인 가능하며 위 카톡공지글에서 항상 확인하실 수 있습니다. 풀이과정 등 학생의 전반적인 학습데이터를 보고 싶으신 경우 안내 데스크(☎ 042 489 7447)로 상담신청 해 주시면 상세히 안내드리겠습니다.";
			
			// 테이블 행 출력
			echo '<tr>';
			echo '<td style="white-space: nowrap; text-align: left; min-width: 80px;">' . htmlspecialchars($student_name) . '</td>';
			echo '<td style="text-align: center;">' . $schedule_count . '</td>';
			echo '<td style="text-align: center;">' . $plan_count . '</td>';
			echo '<td style="text-align: center;">' . $diary_count . '</td>';
			echo '<td style="text-align: center;">' . $today_count . '</td>';
			echo '<td style="text-align: center;">' . $daily_report_count . '</td>';
			echo '<td style="text-align: center; font-weight: bold;">' . $total_count . '</td>';
			echo '<td style="text-align: center;">';
			// 합계가 0이면 파란색, 그 외는 녹색
			$button_color = ($total_count == 0) ? '#2196F3' : '#4CAF50';
			echo '<button onclick="copyMessage_' . $userid . '()" style="padding: 5px 10px; cursor: pointer; background-color: ' . $button_color . '; color: white; border: none; border-radius: 3px; font-size: 12px;">복사</button>';
			echo '<script>';
			echo 'function copyMessage_' . $userid . '() {';
			echo '  var message = ' . json_encode($message_template) . ';';
			echo '  var textarea = document.createElement("textarea");';
			echo '  textarea.value = message;';
			echo '  document.body.appendChild(textarea);';
			echo '  textarea.select();';
			echo '  try {';
			echo '    document.execCommand("copy");';
			echo '    document.body.removeChild(textarea);';
			echo '    alert("메시지가 클립보드에 복사되었습니다.");';
			echo '  } catch (err) {';
			echo '    document.body.removeChild(textarea);';
			echo '    alert("복사에 실패했습니다. 메시지를 수동으로 복사해주세요.");';
			echo '  }';
			echo '}';
			echo '</script>';
			echo '</td>';
			echo '</tr>';
			
			$tafter=time()-86400*$period;
			$schedule=$DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$userid' AND pinned=1 ORDER BY id DESC LIMIT 1 "); 
			$nstatus=1; 
			for($n1=0;$n1<8;$n1++)
				{ 
				$var='start'.$n1;
				$var2=$schedule->$var;
				$var3='duration'.$n1;
				$var4=$schedule->$var3;

				if(strpos($user['firstname'],$tsymbol)!==false)$allhours=$allhours+$var4;
				
				$tbegin=date("H:i",strtotime($var2));
				$time    = explode(':', $tbegin);
				$minutes = ($time[0] * 60.0 + $time[1] * 1.0)-30;
				if($var2!=NULL && $var4!=NULL &&  $var4>0 && (time()- $schedule->timecreated)<86400000)
					{	 
					$n2=(int)(($minutes-530)/30);	
				
					$date=date(" h:i A");
					$date2=date("H:i",strtotime($date));
					$time2    = explode(':', $date2);
					$minutes2 =(int)( ($time2[0] * 60.0 + $time2[1] * 1.0)-30);
					$npresent=(int)(($minutes2-530)/30);	 

					if($minutes<500)$n2=0;
					if(($npresent==$n2+1||$npresent==$n2+2||$npresent==$n2+3||$npresent==$n2+4||$npresent==$n2+5|$npresent==$n2+6||$npresent==$n2+7||$npresent==$n2+8||$npresent==$n2+9||$npresent==$n2+10||$npresent==$n2) && $nday==$n1)
						{
						$nstatus=0;	
						$lastaction=$DB->get_record_sql("SELECT max(timecreated) AS maxtc FROM mdl_logstore_standard_log where userid='$userid' "); 
						$lastaction=$lastaction->maxtc;
						$lastaccess=time()-$lastaction;
						$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated>'$timestart' ORDER BY id DESC LIMIT 1 ");
						$tgoal=time()-$goal->timecreated;
						if($lastaccess>3600)
							{
							if($tgoal >43200 )
								{
								$checkattendance=''; 
								$attendstr='attendlog'.$userid;  
								$$attendstr = $DB->get_record_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$userid' AND hide=0 AND (  (doriginal> '$dayunixtime' AND doriginal < '$dayunixtime2')  OR (dchanged > '$dayunixtime' && dchanged < '$dayunixtime2') ) ORDER by id DESC LIMIT 1 " );
								if($$attendstr->id==NULL)$checkattendance='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=4" target="_blank" ><b style="color:red;">체크</b></a> </br>';
								else $checkattendance='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule.php?id='.$userid.'&eid=1&nweek=4" target="_blank" ><b style="color:blue;"><div class="tooltip3">'.$$attendstr->type.'<span class="tooltiptext3"><table style="" align=center><tr><td>'.$$attendstr->text.'</td></tr></table></span></div></b></a> </br>'; 
								$name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width=12></a><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1" target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >&nbsp;&nbsp;'.$user['firstname'].$user['lastname'].''.$checkattendance.'</a> </br>';
								}
							else 
								{
								$today .=' <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png width=13>'.$user['firstname'].$user['lastname'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
								$name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width=12></a><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1" target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >&nbsp;&nbsp;'.$user['firstname'].$user['lastname'].'</a></br>';
								}
							}
						else 
							{
							$name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width=12></a><a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?userid='.$userid.'&mode=1" target="_blank" ><img src=https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png width=13></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >&nbsp;&nbsp;'.$user['firstname'].$user['lastname'].'</a></br>';
							}
						}
					else 
						{ 
						$nstatus=0;
						$name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width=12></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
						}
				

					// $name[$n1][$n2].='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
					}
				elseif($var4!=0)
					{	
					$nstatus=0; 
					$name[$n1][29].='<a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid='.$userid.'"target="_blank"><img style="margin-bottom:3px;" src="https://mathking.kr/Contents/IMAGES/pomodorologo.png" width=12></a><a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a><br>';
					}
				}
			if($nstatus==1)$checkusers.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a> &nbsp;&nbsp;&nbsp;';
			}
		else
			{
			if($lastaccesstime<60)$suspendedusers1.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" ><b>'.$user['firstname'].$user['lastname'].'</b></a> (<a href="https://claude.site/artifacts/a02114e7-0cbd-4dcc-9282-8802a8d63278" target="_blank" >'.$lastaccesstime.'일전)</a>&nbsp; | ';
			else $suspendedusers2.='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id='.$userid.'&tb=604800 " target="_blank" >'.$user['firstname'].$user['lastname'].'</a> (<a href="https://claude.site/artifacts/a02114e7-0cbd-4dcc-9282-8802a8d63278" target="_blank" >'.$lastaccesstime.'일전)</a>&nbsp; | ';
			}
		}
	
	// 테이블 종료
	echo '</tbody>';
	echo '</table>';
	echo '</div>'; // 테이블 래퍼 div 종료
	echo '</div>'; // 전체 컨테이너 div 종료
	
	$nenergy=round($allhours/(5*($nusers+0.0001)),2);
	$usedtime=round($allusedtime/($nusers+0.0001),2);
	$DB->execute("UPDATE {abessi_teacher_setting} SET nenergy='$nenergy', usedtime='$usedtime' WHERE userid='$teacherid' ORDER BY id DESC LIMIT 1 ");  
	}
else
	{
	// tablemode가 설정된 경우에도 기본 통계 표시
	echo '<div style="padding: 20px;">';
	echo '<h2>학생별 주간 페이지 방문 통계 (주 시작일: ' . htmlspecialchars($week_start_date) . ')</h2>';
	echo '<p>통계를 보려면 기본 모드로 접근해주세요.</p>';
	echo '</div>';
	}
