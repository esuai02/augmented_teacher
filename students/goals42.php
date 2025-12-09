<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters
$studentid = $_GET["id"]; 
$mode = $_GET["mode"]; 
$gtype = $_GET["gtype"]; 
$inputtext = $_GET["cntinput"]; 
$nweek = $_GET["nweek"];  
$newdream = $_GET["newdream"]; 
// 주간목표 보기 모드가 입력모드 데이터와 동기화되도록 pid(분기/진행 ID) 지원
$pid = isset($_GET["pid"]) ? intval($_GET["pid"]) : 0;
if($studentid == NULL) $studentid = $USER->id;
  
// Check user role and permissions
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'"); 
$role = $userrole->data;

if($USER->id != $studentid && $role === 'student') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit; 
}
  
// Get user data
$timecreated = time();
$username = $DB->get_record_sql("SELECT id, hideinput, lastname, firstname, timezone FROM mdl_user WHERE id='$studentid' ORDER BY id DESC LIMIT 1");
$studentname = $username->firstname.$username->lastname;
$tabtitle = "목표관리 - ".$username->lastname;
$hideinput = $username->hideinput;
                      
// Time variables  
$timestart = $timecreated - 604800 * 2;
$aweekago = $timecreated - 604800;
$adayAgo = time() - 43200;

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','goals','$timecreated')");
}
                                             
// Set defaults
if($nweek == NULL) $nweek = 15;
if(strpos($gtype, '주간목표') !== false) $selectgtype2 = 'selected';
else $selectgtype1 = 'selected';
    
// ============================================================================
// ALL GOALS FROM mdl_abessi_weeklyplans (완전히 재작성)
// - plan1-7: 일별 목표 (월~일)
// - plan8-16: 주간 목표 (1~9주)
// ============================================================================

// Step 1: Determine progress ID (pid). URL로 전달되면 해당 pid 우선 사용
if ($pid > 0) {
    $progressid = $pid;
} else {
    $latestProgress = $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER BY id DESC LIMIT 1");
    $progressid = $latestProgress ? $latestProgress->id : 0;
}

// Step 2: Get all goals from weeklyplans
$weeklyPlansRecord = null;
$dailyGoals = array();
$weeklyGoals = array();

if($progressid > 0) {
    $weeklyPlansRecord = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid='$studentid' AND progressid='$progressid' ORDER BY id DESC LIMIT 1");

    // 지정 pid로 저장된 주간목표가 없거나 비어 있으면 최근 저장본으로 폴백
    $needFallback = false;
    if(!$weeklyPlansRecord) {
        $needFallback = true;
    }

    if(!$needFallback && $weeklyPlansRecord) {
        $allWeeklyEmpty = true;
        for($i = 8; $i <= 16; $i++) {
            $pf = 'plan'.$i;
            if(!empty($weeklyPlansRecord->$pf)) { $allWeeklyEmpty = false; break; }
        }
        if($allWeeklyEmpty) { $needFallback = true; }
    }

    if($needFallback) {
        $weeklyPlansRecord = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid='$studentid' ORDER BY timemodified DESC, id DESC LIMIT 1");
        if($weeklyPlansRecord && !empty($weeklyPlansRecord->progressid)) {
            $progressid = $weeklyPlansRecord->progressid; // 화면 컨텍스트 정합
        }
    }

    if($weeklyPlansRecord) {
        // Extract plan1-7 (daily goals: Mon-Sun)
        $dayNames = array(1 => '월요일', 2 => '화요일', 3 => '수요일', 4 => '목요일', 5 => '금요일', 6 => '토요일', 7 => '일요일');
        for($i = 1; $i <= 7; $i++) {
            $planField = 'plan' . $i;
            $dateField = 'date' . $i;

            $planText = isset($weeklyPlansRecord->$planField) ? $weeklyPlansRecord->$planField : '';
            $planDate = isset($weeklyPlansRecord->$dateField) ? $weeklyPlansRecord->$dateField : '';

            $dailyGoals[] = array(
                'day' => $i,
                'dayname' => $dayNames[$i],
                'plan' => $planText,
                'date' => $planDate,
                'field' => $planField
            );
        }

        // Extract plan8-16 (weekly goals: Week 1-9)
        for($i = 8; $i <= 16; $i++) {
            $planField = 'plan' . $i;
            $dateIndex = $i; // plan8 -> date8, plan9 -> date9, ...
            $dateField = 'date' . $dateIndex;

            $weekNumber = $i - 7; // Week 1-9
            $planText = isset($weeklyPlansRecord->$planField) ? $weeklyPlansRecord->$planField : '';
            $planDate = isset($weeklyPlansRecord->$dateField) ? $weeklyPlansRecord->$dateField : '';

            $weeklyGoals[] = array(
                'week' => $weekNumber,
                'plan' => $planText,
                'date' => $planDate,
                'field' => $planField
            );
        }
    }
}

// Step 2.5: Get math diary data from abessi_todayplans (12-hour filter)
$diaryPlans = array();
$diaryRecord = null;
$twelveHoursAgo = time() - 43200; // 12시간 = 43200초

try {
    // 최근 12시간 이내에 작성된 레코드 조회
    $diaryRecord = $DB->get_record_sql(
        "SELECT * FROM {abessi_todayplans}
         WHERE userid = ?
         AND timecreated >= ?
         ORDER BY timecreated DESC
         LIMIT 1",
        array($studentid, $twelveHoursAgo)
    );

    if ($diaryRecord) {
        // plan1-16 데이터 파싱
        for ($i = 1; $i <= 16; $i++) {
            $planField = 'plan' . $i;
            $dueField = 'due' . $i;
            $urlField = 'url' . $i;
            $fbackField = 'fback' . $i; // fback 필드 추가
            $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...

            $planText = isset($diaryRecord->$planField) ? $diaryRecord->$planField : '';
            $dueMinutes = isset($diaryRecord->$dueField) ? intval($diaryRecord->$dueField) : 0;
            $urlText = isset($diaryRecord->$urlField) ? $diaryRecord->$urlField : '';
            $fbackText = isset($diaryRecord->$fbackField) ? $diaryRecord->$fbackField : ''; // 피드백
            $statusText = isset($diaryRecord->$statusField) ? $diaryRecord->$statusField : ''; // 만족도

            // 내용이 있는 항목만 추가
            if (!empty($planText)) {
                $diaryPlans[] = array(
                    'index' => $i,
                    'plan' => $planText,
                    'duration' => $dueMinutes, // 분 단위
                    'url' => $urlText,
                    'fback' => $fbackText, // 피드백 추가
                    'status' => $statusText // 만족도 상태
                );
            }
        }
    }

    // 디버그 로깅
    error_log("=== DIARY DATA DEBUG ===");
    error_log("Student ID: " . $studentid);
    error_log("12시간 전 기준: " . $twelveHoursAgo . " (" . date('Y-m-d H:i:s', $twelveHoursAgo) . ")");
    if ($diaryRecord) {
        error_log("일기 레코드 발견 ID: " . $diaryRecord->id);
        error_log("작성시간: " . date('Y-m-d H:i:s', $diaryRecord->timecreated));
        error_log("수정시간: " . date('Y-m-d H:i:s', $diaryRecord->timemodified));
        error_log("일기 항목 수: " . count($diaryPlans));
    } else {
        error_log("12시간 이내 일기 레코드 없음");
    }
    error_log("=== END DIARY DEBUG ===");

} catch (Exception $e) {
    error_log("ERROR: 수학일기 데이터 로딩 실패 (student $studentid): " . $e->getMessage());
    error_log("File: " . __FILE__ . " Line: " . __LINE__);
    $diaryPlans = array(); // 에러 시 빈 배열
}

// Legacy variables for compatibility (기존 코드와의 호환성 유지)
$todayGoal = null;
if(!empty($dailyGoals) && !empty($dailyGoals[0]['plan'])) {
    $todayGoal = new stdClass();
    $todayGoal->text = $dailyGoals[0]['plan'];
    $todayGoal->timecreated = time();
    $todayGoal->id = $weeklyPlansRecord ? $weeklyPlansRecord->id : 0;
    $todayGoal->type = '오늘목표';
}

$weeklyGoal = null;
if(!empty($weeklyGoals) && !empty($weeklyGoals[0]['plan'])) {
    $weeklyGoal = new stdClass();
    $weeklyGoal->text = $weeklyGoals[0]['plan'];
    $weeklyGoal->timecreated = time();
    $weeklyGoal->id = $weeklyPlansRecord ? $weeklyPlansRecord->id : 0;
    $weeklyGoal->type = '주간목표';
}

// REMOVED: mdl_abessi_today 조회 완전 제거
// Old code that processed mdl_abessi_today has been removed
// Now using weeklyplans data (populated above from plan1-7 for daily, plan8-16 for weekly)

// Generate display for 7-day daily goals from weeklyplans (plan1-7)
$goalhistory0 = '';
foreach($dailyGoals as $goal) {
    // Date handling: database stores as 'Y-m-d' string format
    $dateDisplay = '-';
    if(!empty($goal['date'])) {
        // If it's already in Y-m-d format, use as is; otherwise convert
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $goal['date'])) {
            $dateDisplay = $goal['date'];
        } else if(is_numeric($goal['date'])) {
            $dateDisplay = date('Y-m-d', $goal['date']);
        } else {
            $timestamp = strtotime($goal['date']);
            if($timestamp !== false) {
                $dateDisplay = date('Y-m-d', $timestamp);
            }
        }
    }

    $planDisplay = !empty($goal['plan']) ? htmlspecialchars($goal['plan']) : '<span style="color: #999;">목표 없음</span>';

    $goalhistory0 .= '<tr>';
    $goalhistory0 .= '<td style="text-align: center; font-weight: bold; color: #3383ff;">' . htmlspecialchars($goal['dayname']) . '</td>';
    $goalhistory0 .= '<td style="text-align: center; color: #666;">' . $dateDisplay . '</td>';
    $goalhistory0 .= '<td>' . $planDisplay . '</td>';
    $goalhistory0 .= '</tr>';
}

// Generate weekly goals history - 입력모드에서 저장된 모든 주간 목표를 표시
$goalhistory1 = '';

foreach($weeklyGoals as $goal) {
    if(empty($goal['plan'])) {
        continue; // 내용이 비어 있으면 스킵
    }

    // 날짜 표시 가공
    $dateDisplay = '-';
    if(!empty($goal['date'])) {
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $goal['date'])) {
            $dateDisplay = $goal['date'];
        } else if(is_numeric($goal['date'])) {
            $dateDisplay = date('Y-m-d', intval($goal['date']));
        } else {
            $ts = strtotime($goal['date']);
            if($ts) { $dateDisplay = date('Y-m-d', $ts); }
        }
    }

    $weekLabel = $goal['week'] . '주차';
    $planText = htmlspecialchars($goal['plan']);

    $goalhistory1 .= '<tr>';
    $goalhistory1 .= '<td>' . $dateDisplay . '</td>';
    $goalhistory1 .= '<td><b style="color:#bf04e0;">' . $weekLabel . '</b></td>';
    $goalhistory1 .= '<td>' . $planText . '</td>';
    $goalhistory1 .= '</tr>';
}

// Get mission list (roadmap)
$missionlist = $DB->get_records_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER by id DESC LIMIT 3");
$missionresult = json_decode(json_encode($missionlist), True);

// Get quiz attempts
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz.sumgrades AS tgrades FROM mdl_quiz LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz WHERE (mdl_quiz_attempts.timefinish > '$aweekago' OR mdl_quiz_attempts.timestart > '$aweekago' OR (state='inprogress' AND mdl_quiz_attempts.timestart > '$aweekago')) AND mdl_quiz_attempts.userid='$studentid' ORDER BY mdl_quiz_attempts.timestart");
$quizresult = json_decode(json_encode($quizattempts), True);

// Process quiz data
$nquiz = count($quizresult);
$quizlist11 = '';
$quizlist12 = '';
$quizlist21 = '';
$quizlist22 = '';
$quizlist31 = '';
$quizlist32 = '';
$todayGrade = 0;
$ntodayquiz = 0;
$weekGrade = 0;
$nweekquiz = 0;

foreach(array_reverse($quizresult) as $value) {
    $qnum = substr_count($value['layout'],',')+1-substr_count($value['layout'],',0');
    $quizgrade = round($value['sumgrades']/$value['tgrades']*100,0);
    
    if($quizgrade > 89.99) {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
    } elseif($quizgrade > 69.99) {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/bluedot.png" width="15">';
    } else {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/reddot.png" width="15">';
    }
    
    $quizid = $value['quiz'];
    $moduleid = $DB->get_record_sql("SELECT id FROM mdl_course_modules where instance='$quizid'"); 
    $quizmoduleid = $moduleid->id;
    $attemptid = $value['id'];
    
    $quiztitle = $value['name'];
    if(strpos($value['name'], 'ifminteacher') !== false) {
        $quiztitle = strstr($value['name'], '{ifminteacher', true);
    }
    
    if(strpos($quiztitle, '내신') != false) {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist11 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $todayGrade += $quizgrade;
            $ntodayquiz++;
        } else {
            $quizlist12 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $weekGrade += $quizgrade;
            $nweekquiz++;
        }
    } elseif($qnum > 9) {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist21 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $todayGrade += $quizgrade;
            $ntodayquiz++;
        } else {
            $quizlist22 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
            $weekGrade += $quizgrade;
            $nweekquiz++;
        }
    } else {
        if($value['timestart'] > $adayAgo || $value['timefinish'] > $adayAgo) {
            $quizlist31 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
        } else {
            $quizlist32 .= '<tr><td>'.$imgstatus.'</td><td><a href="https://mathking.kr/moodle/mod/quiz/view.php?id='.$quizmoduleid.'" target="_blank">'.substr($quiztitle,0,30).'</a></td><td>'.$quizgrade.'점</td><td>'.date("m/d H:i",$value['timestart']).'</td></tr>';
        }
    }
}

// Calculate averages
$avgToday = ($ntodayquiz > 0) ? round($todayGrade / $ntodayquiz) : 0;
$avgWeek = ($nweekquiz > 0) ? round($weekGrade / $nweekquiz) : 0;

// Set placeholder text
$placeholder = 'placeholder="※ 최대한 구체적인 목표를 입력해 주세요"';
$presettext = '';

// Prepare JavaScript arrays for pagination
$dailyGoalsJS = json_encode($dailyGoals);
$weeklyGoalsJS = json_encode($weeklyGoals);
if($inputtext != NULL) {
    $presettext = 'value="'.$inputtext.'"';
}

$deadline = date("Y:m:d", time());

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo $tabtitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="https://granicus.com/wp-content/uploads/image/png/icon-granicus-300x300.png" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3383FF;
            --secondary-color: #E05D22;
            --accent-color: #0082D8;
            --success-color: #059669;
            --warning-color: #ea580c;
            --info-color: #0891b2;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --shadow: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', '맑은 고딕', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        /* Navigation */
        .nav-top {
            background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .nav-btn.active {
            background: rgba(255,255,255,0.95);
            color: #7C3AED;
            font-weight: 700;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .nav-btn.active:hover {
            background: rgba(255,255,255,1);
            color: #7C3AED;
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }
        
        /* Container */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Full-width iframe containers */
        .iframe-fullwidth {
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            width: 100vw;
            max-width: 100vw;
        }

        .iframe-fullwidth .card-body {
            padding: 0;
            max-width: 100%;
        }

        .iframe-fullwidth iframe {
            width: 100vw;
            max-width: 100vw;
        }

        /* Full-width card for edit mode */
        .card-fullwidth {
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            width: 100vw;
            max-width: 100vw;
            border-radius: 0;
        }
        
        
        .view-toggle-btn {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid transparent;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .view-toggle-btn.scroll-mode {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .view-toggle-btn.scroll-mode:hover {
            background: rgba(255,255,255,1);
            color: #667eea;
            transform: none;
        }
        
        .main-content {
            padding: 30px 20px 0;
        }
        
        .view-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .view-toggle-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        
        
        /* Tab View Styles */
        .tab-container {
            display: none;
        }
        
        .tab-container.active {
            display: block;
        }
        
        .tab-nav {
            display: flex;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
        }
        
        .tab-btn:hover {
            color: var(--text-primary);
            background: rgba(0,0,0,0.03);
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(51, 131, 255, 0.3);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Scroll View Styles */
        .scroll-container {
            display: none;
        }
        
        .scroll-container.active {
            display: block;
        }
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 0;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #dc2626 100%);
            color: white;
            padding: 16px 24px;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
        }
        
        .section-header.secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #dc2626 100%);
        }
        
        .section-header.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
        }
        
        .section-header.info {
            background: linear-gradient(135deg, var(--info-color) 0%, #0e7490 100%);
        }
        
        .section-header.warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #dc2626 100%);
        }
        
        .card-body {
            padding: 20px 24px;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: var(--bg-color);
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .data-table td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .data-table a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Form Styles */
        .goal-form {
            background: rgba(51, 131, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(51, 131, 255, 0.2);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(51, 131, 255, 0.1);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-row > * {
            flex: 1;
            min-width: 200px;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(51, 131, 255, 0.3);
        }
        
        /* Stats Box */
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: var(--bg-color);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        
        /* Inline Editing Styles */
        .editable-goal {
            position: relative;
            cursor: pointer;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: rgba(51, 131, 255, 0.05);
            min-height: 60px;
            display: flex;
            align-items: center;
        }
        
        .editable-goal:hover {
            background: rgba(51, 131, 255, 0.1);
            border-color: rgba(51, 131, 255, 0.3);
        }
        
        .editable-goal.editing {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(51, 131, 255, 0.1);
        }
        
        .editable-goal input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            outline: none;
            padding: 0;
            font-family: inherit;
        }
        
        .editable-goal .placeholder-text {
            color: var(--text-secondary);
            font-style: italic;
            font-size: 16px;
        }
        
        .editable-goal .goal-text {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            width: 100%;
        }
        
        .edit-hint {
            position: absolute;
            top: -8px;
            right: 8px;
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            opacity: 0;
            transform: translateY(5px);
            transition: all 0.3s ease;
        }
        
        .editable-goal:hover .edit-hint {
            opacity: 1;
            transform: translateY(0);
        }
        
        .saving-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            color: var(--success-color);
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .saving-indicator.show {
            opacity: 1;
        }
        
        .goal-input-edit {
            width: 100% !important;
            border: none !important;
            background: transparent !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            color: var(--text-primary) !important;
            outline: none !important;
            padding: 0 !important;
            font-family: inherit !important;
            margin: 0 !important;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content-container {
                padding: 15px;
            }
            
            .nav-menu li {
                margin: 0 10px;
            }
            
            .nav-menu a {
                font-size: 14px;
                padding: 6px 12px;
            }
            
            .tab-nav {
                padding: 5px;
            }
            
            .tab-btn {
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .section-header {
                font-size: 16px;
                padding: 14px 20px;
            }
            
            .view-controls {
                justify-content: center;
            }
            
            .stats-box {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .form-row > * {
                min-width: 100%;
            }
        }
        
        /* More Button Styles */
        .more-button-container {
            text-align: center;
            margin-top: 15px;
        }
        
        .more-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        
        .more-btn:active {
            transform: translateY(0);
        }
        
        /* Current Goal Highlight Styles */
        .current-goal {
            background: rgba(51, 131, 255, 0.1) !important;
            box-shadow: inset 0 0 0 2px var(--primary-color), 0 2px 8px rgba(51, 131, 255, 0.2);
            position: relative;
        }

        .current-goal td {
            padding: 12px 8px !important;
            vertical-align: middle !important;
            text-align: left !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .current-goal:hover {
            background: rgba(51, 131, 255, 0.15) !important;
            box-shadow: inset 0 0 0 2px var(--primary-color), 0 4px 12px rgba(51, 131, 255, 0.25);
        }

        .current-goal td:last-child {
            position: relative;
        }

        .current-goal td:last-child::after {
            content: "현재";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 8px;
            line-height: 1;
            z-index: 1;
        }
        
        /* Responsive current goal styles */
        @media (max-width: 768px) {
            .current-goal td:last-child::after {
                right: 4px;
                font-size: 9px;
                padding: 1px 4px;
            }
        }

        /* Quiet input toggle button */
        .quiet-input-btn {
            width: 100%;
            padding: 12px 20px;
            background: var(--bg-color);
            border: 1px dashed var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quiet-input-btn:hover {
            background: rgba(51, 131, 255, 0.05);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .quiet-input-btn i {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="nav-top">
        <div class="content-container">
            <div class="nav-controls">
                <div class="header-nav">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                🏠 홈
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php?id=<?php echo $studentid; ?>" class="nav-btn">
            👩🏻‍🎨‍ 내공부방
            </a>


            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id=<?php echo $studentid; ?>" class="nav-btn">
            📝 오늘
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id=<?php echo $studentid; ?>" class="nav-btn">
                📅 일정
            </a>

            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php?id=<?php echo $studentid; ?>" class="nav-btn  active">
                🎯 목표
            </a>

            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/student_inbox.php?studentid=<?php echo $studentid; ?>" class="nav-btn">
            📩 메세지
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>" class="nav-btn">
                📅 수학일기
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php" class="nav-btn">
                🚀 AI튜터
            </a>
                </div>
                <div class="view-controls">
                    <button class="view-toggle-btn" onclick="toggleView()" title="뷰 전환">
                        <i class="fas fa-folder" id="viewIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="content-container main-content">
        
        <!-- Tab View Container -->
        <div id="tabView" class="tab-container active">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="openTab('roadmap')">분기목표</button>
                <button class="tab-btn" onclick="openTab('weekly')">주간목표</button>
                <button class="tab-btn" onclick="openTab('today')">오늘목표</button>
                <button class="tab-btn" onclick="openTab('diary')">수학일기</button>
            </div>
            
            <!-- 오늘목표 Tab -->
            <div id="today" class="tab-content">
                <div class="card" id="todayCard">
                    <div class="section-header info" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>오늘 목표</span>
                        <div class="mode-toggle-buttons" style="display: flex; gap: 10px;">
                            <button class="mode-btn active" onclick="switchTodayMode('view')" id="todayViewBtn" style="padding: 5px 15px; border: 1px solid #fff; background: rgba(255,255,255,0.2); color: white; border-radius: 5px; cursor: pointer;">보기 모드</button>
                            <button class="mode-btn" onclick="switchTodayMode('edit')" id="todayEditBtn" style="padding: 5px 15px; border: 1px solid #fff; background: transparent; color: white; border-radius: 5px; cursor: pointer;">입력 모드</button>
                        </div>
                    </div>

                    <!-- 보기 모드 -->
                    <div id="todayViewMode">
                        <div class="card-body">
                            <h4>주간 일일 목표 (7일)</h4>
                        <?php if($goalhistory0): ?>
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(51, 131, 255, 0.1); border-bottom: 2px solid #3383ff;">
                                    <th style="padding: 12px; text-align: center; width: 15%;">요일</th>
                                    <th style="padding: 12px; text-align: center; width: 15%;">날짜</th>
                                    <th style="padding: 12px; text-align: left; width: 70%;">일일 목표</th>
                                </tr>
                            </thead>
                            <tbody id="dailyGoalsTable">
                                <?php echo $goalhistory0; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state" style="padding: 40px; text-align: center;">
                            <i class="fas fa-calendar-day" style="font-size: 48px; color: #3383ff; margin-bottom: 15px;"></i>
                            <p style="font-size: 16px; color: #666;">일일 목표가 설정되지 않았습니다.</p>
                            <p style="font-size: 14px; color: #999;">입력 모드에서 일일 목표를 작성해주세요.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                    <!-- 입력 모드 (iframe) -->
                    <div id="todayEditMode" class="iframe-fullwidth" style="display: none;">
                        <div class="card-body" style="padding: 0;">
                            <iframe id="today-iframe"
                                    src="dailygoals.php?id=<?php echo $studentid; ?>&cid=<?php echo $_GET['cid'] ?? 106; ?>&pid=<?php echo $studentid; ?>"
                                    style="width: 100%; min-height: 800px; border: none; display: block;"
                                    onload="resizeTodayIframe()">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 주간목표 Tab -->
            <div id="weekly" class="tab-content">
                <div class="card" id="weeklyCard">
                    <div class="section-header warning" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>주간 목표</span>
                        <div class="mode-toggle-buttons" style="display: flex; gap: 10px;">
                            <button class="mode-btn active" onclick="switchWeeklyMode('view')" id="weeklyViewBtn" style="padding: 5px 15px; border: 1px solid #fff; background: rgba(255,255,255,0.2); color: white; border-radius: 5px; cursor: pointer;">보기 모드</button>
                            <button class="mode-btn" onclick="switchWeeklyMode('edit')" id="weeklyEditBtn" style="padding: 5px 15px; border: 1px solid #fff; background: transparent; color: white; border-radius: 5px; cursor: pointer;">입력 모드</button>
                        </div>
                    </div>

                    <!-- 보기 모드 -->
                    <div id="weeklyViewMode">
                        <div class="card-body">
                            <h4>주간 목표 기록</h4>
                        <?php if($goalhistory1): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">날짜</th>
                                    <th width="20%">유형</th>
                                    <th width="60%">목표</th>
                                </tr>
                            </thead>
                            <tbody id="weeklyGoalsTable">
                                <?php echo $goalhistory1; ?>
                            </tbody>
                        </table>
                        <?php if(count($weeklyGoals) > 3): ?>
                        <div class="more-button-container">
                            <button type="button" class="more-btn" onclick="loadMoreGoals('weekly')" id="weeklyMoreBtn">
                                <i class="fas fa-chevron-down"></i> 더보기 (<?php echo count($weeklyGoals) - 3; ?>개)
                            </button>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>주간 목표 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                    <!-- 입력 모드 (iframe) -->
                    <div id="weeklyEditMode" class="iframe-fullwidth" style="display: none;">
                        <div class="card-body" style="padding: 0;">
                            <iframe id="weekly-iframe"
                                    src="weeklyplans.php?id=<?php echo $studentid; ?>&cid=<?php echo $_GET['cid'] ?? 106; ?>&pid=<?php echo $studentid; ?>"
                                    style="width: 100%; min-height: 800px; border: none; display: block;"
                                    onload="resizeWeeklyIframe()">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 로드맵 Tab -->
            <div id="roadmap" class="tab-content active">
                <div class="card">
                    <div class="section-header success" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>분기목표 설정</span>
                        <?php if($hideinput == 0 || $role !== 'student'): ?>
                        <?php
                        // 랜덤 꿈 데이터 준비
                        $savedDream = $DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");

                        if($savedDream && $savedDream->dreamchallenge !== NULL && $savedDream->dreamchallenge !== '') {
                            $randomDream = $savedDream->dreamchallenge;
                            $randomDreamUrl = $savedDream->dreamurl ? $savedDream->dreamurl : '';
                        } else {
                            $index = array_rand($randomDreamList);
                            $randomDream = $randomDreamList[$index];
                            $randomDreamUrl = $randomDreamUrlList[$index];
                        }
                        ?>
                        <div style="flex: 1; text-align: center; margin: 0 20px;">
                            <span style="font-size: 14px; color: white;">
                                🌟 <strong id="randomDreamText"><?php echo $randomDream; ?></strong>
                                <a href="<?php echo $randomDreamUrl; ?>" target="_blank" style="margin-left: 10px; font-size: 12px; color: white; text-decoration: underline;" id="dreamDetailLink">자세히 보기</a>
                                <button type="button" style="background: rgba(255,255,255,0.3); color: white; border: none; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; cursor: pointer;" onclick="changeRandomDream()">바꾸기</button>
                            </span>
                        </div>
                        <?php endif; ?>
                        <span><?php echo ($missionresult) ? 'D-'.round(($missionresult[0]['deadline'] - time()) / 86400 + 1).'일' : ''; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if(count($missionresult) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">유형</th>
                                    <th width="50%">목표</th>
                                    <th width="20%">데드라인</th>
                                    <th width="10%">D-Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach($missionresult as $mission) {
                                    $dday = round(($mission['deadline'] - time()) / 86400) + 1;
                                    $dateString = date("Y-m-d", $mission['deadline']);
                                    $plantype = ($mission['plantype'] === '분기목표') ? '<b style="color:purple;">분기목표</b>' : '<b style="color:red;">방향설정</b>';
                                    
                                    echo '<tr>';
                                    echo '<td>'.$plantype.'</td>';
                                    echo '<td>'.iconv_substr($mission['memo'], 0, 70, "utf-8").'</td>';
                                    echo '<td>'.$dateString.'</td>';
                                    echo '<td>D-'.$dday.'</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-route"></i>
                            <p>설정된 분기목표가 없습니다.</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($hideinput == 0 || $role !== 'student'): ?>
                        <?php
                        // Get random dream challenge
                                                
                        $randomDreamList = [
                            "인공지능 개발자",
                            "환경 보호 전문가",
                            "가상현실 게임 디자이너",
                            "우주 탐사자",
                            "유전공학 연구원",
                            "스마트팜 기술자",
                            "해양 생물학자",
                            "신재생 에너지 엔지니어",
                            "드론 파일럿",
                            "사이버 보안 전문가",
                            "데이터 과학자",
                            "로봇공학 기술자",
                            "콘텐츠 크리에이터",
                            "의료 기술 혁신가",
                            "지속 가능한 패션 디자이너",
                            "가상 교육자",
                            "우주 식민지 설계자",
                            "인공장기 개발자",
                            "디지털 마케터",
                            "바이오인포매틱스 전문가",
                            "청정 에너지 컨설턴트",
                            "증강 현실 경험 디자이너",
                            "암호화폐 분석가",
                            "미래학 연구원",
                            "나노기술 엔지니어",
                            "스마트 도시 계획가",
                            "인간-기계 인터페이스 디자이너",
                            "디지털 윤리학자",
                            "양자 컴퓨터 개발자",
                            "자율 주행 차량 엔지니어",
                            "생명공학 연구원",
                            "모바일 앱 개발자",
                            "인공지능 법률 고문",
                            "스페이스 호텔 매니저",
                            "디지털 복원 전문가",
                            "신경과학자",
                            "미생물 에너지 생산자",
                            "스마트 웨어러블 디자이너",
                            "3D 프린팅 전문가",
                            "무인 항공 교통 관리자",
                            "가상 현실 치료사",
                            "블록체인 개발자",
                            "음성 인식 기술 개발자",
                            "클라우드 컴퓨팅 전문가",
                            "인터넷 오브 싱스(IoT) 개발자",
                            "게임 이론 분석가",
                            "스마트 홈 시스템 디자이너",
                            "텔레프레즌스 로봇 조종사",
                            "웨어러블 헬스 기기 개발자",
                            "식품 과학자",
                            "디지털 아트 큐레이터",
                            "생태계 복원 전문가",
                            "미래 도시 건축가",
                            "인공지능 음악 작곡가",
                            "크립토 아트 작가",
                            "전염병 예방 전문가",
                            "심우주 통신 엔지니어",
                            "지속 가능한 관광 개발자",
                            "양자 암호화 전문가",
                            "빅 데이터 분석가",
                            "첨단 농업 기술자",
                            "가상 현실 아키텍트",
                            "뇌-컴퓨터 인터페이스 연구원",
                            "홀로그램 콘텐츠 제작자",
                            "인간 행동 연구원",
                            "테라포밍 엔지니어",
                            "초지능 시스템 디자이너",
                            "멸종 위기 동물 보호 전문가",
                            "스포츠 과학자",
                            "스마트 교통 시스템 개발자",
                            "도시 농업 전문가",
                            "신경 조직 공학자",
                            "모바일 헬스케어 서비스 개발자",
                            "핵융합 에너지 연구원",
                            "글로벌 웜링 해결 전략가",
                            "인터스텔라 메시지 디자이너",
                            "디지털 명상 지도자",
                            "우주 광물학자",
                            "스마트 그리드 기술자",
                            "환경 데이터 과학자",
                            "미래 학교 교육가",
                            "디지털 디톡스 전문가",
                            "가상 동물원 설계자",
                            "스마트 패션 기술자",
                            "항노화 연구원",
                            "비디오 게임 스토리텔러",
                            "지능형 건축 재료 개발자",
                            "마이크로바이옴 연구원",
                            "어반 에어 모빌리티 디자이너",
                            "소셜 미디어 심리학자",
                            "디지털 노마드 컨설턴트",
                            "인공지능 윤리위원",
                            "소리 치유사",
                            "우주 날씨 예보자",
                            "생체 모방 기술 개발자",
                            "디지털 인문학자",
                            "챗봇 스크립트 작가",
                            "스마트 재난 대응 시스템 개발자",
                            "가상 박물관 디자이너",
                            "우주 법률 전문가",
                            "스마트 재활 기기 개발자",
                            "언더워터 호텔 디자이너",
                            "증강 현실 교육 콘텐츠 제작자",
                            "마이크로그래비티 요리사",
                            "우주 쓰레기 관리 전문가",
                            "바이오센서 개발자",
                            "디지털 정신 건강 치료사",
                            "가상 현실 스포츠 코치",
                            "자율주행 자동차 디자이너",
                            "심해 탐사 장비 엔지니어",
                            "지능형 비즈니스 분석가",
                            "클라우드 베이스드 교육 플랫폼 개발자",
                            "소셜 임팩트 투자자",
                            "3D 생체 인쇄 전문가",
                            "스마트 패브릭 디자이너",
                            "어반 푸드 시스템 혁신가",
                            "디지털 저작권 관리자",
                            "글로벌 로지스틱스 최적화 전문가",
                            "공중 부양 교통 시스템 개발자",
                            "식물 기반 식품 과학자",
                            "지속 가능한 도시 농업 설계자",
                            "인간 확장 기술 연구원",
                            "사이버범죄 수사관",
                            "스마트 재난 경보 시스템 개발자",
                            "가상 현실 여행 에이전트",
                            "인공지능 조교",
                            "디지털 포렌식 전문가",
                            "스마트 에너지 저장 솔루션 개발자",
                            "초현실적 예술가",
                            "바이러스 억제 연구원",
                            "가상 인간 상호작용 디자이너",
                            "나노메디슨 연구원",
                            "생태계 기능 디자이너",
                            "양자 통신 전문가",
                            "디지털 아카이브 전문가",
                            "인터랙티브 도서관 컨설턴트",
                            "친환경 건축 자재 개발자",
                            "모바일 결제 시스템 혁신가",
                            "인공지능 기반 교육 컨텐츠 개발자",
                            "미래 의학 연구원",
                            "심리적 건강 모바일 앱 개발자",
                            "공기 정화 기술 개발자",
                            "디지털 농업 컨설턴트",
                            "스마트 헬멧 개발자",
                            "공간 데이터 분석가",
                            "의료용 로봇 기술자",
                            "가상 현실 치료 기기 개발자",
                            "자연어 처리 연구원",
                            "인공 지능 스타일리스트",
                            "우주 관광 가이드",
                            "퍼스널 데이터 프라이버시 어드바이저",
                            "스마트 컨트랙트 개발자",
                            "가상 아이돌 제작자",
                            "지속 가능한 수자원 관리 전문가",
                            "인공지능 기반 퍼스널 쇼퍼",
                            "로우코드 애플리케이션 개발자",
                            "지능형 교통 시스템 분석가",
                            "미세먼지 저감 기술 연구원",
                            "디지털 콘텐츠 권리 관리 전문가",
                            "가상 현실 영화 제작자",
                            "인공지능 화상 회의 퍼실리테이터",
                            "신경망 칩 설계자",
                            "언어학습 앱 개발자",
                            "에코 컨셔스 패션 브랜드 창립자",
                            "디지털 복원 기술자",
                            "소셜 미디어 인플루언서 전략가",
                            "양자 컴퓨팅 애플리케이션 개발자",
                            "스마트 물류 시스템 설계자",
                            "공중보건 위기 대응 전문가",
                            "에코테크 스타트업 창업가",
                            "디지털 이벤트 플래너",
                            "가상 스포츠 리그 관리자",
                            "인공지능 법률 분석가",
                            "심해 연구 및 탐사 전문가",
                            "우주 농업 연구원",
                            "공간정보 시스템 개발자",
                            "첨단 의료 이미징 기술자",
                            "자동화 테스트 엔지니어",
                            "스마트 시티 보안 전문가",
                            "가상 교실 교육 기획자",
                            "디지털 장례 서비스 제공자",
                            "우주 환경 엔지니어",
                            "스타트업 인큐베이터 멘토",
                            "가상 현실 기반 심리 치료사",
                            "에너지 효율성 컨설턴트",
                            "스마트 센서 네트워크 개발자",
                            "게이미피케이션 전략가",
                            "빛 오염 해결 전문가",
                            "디지털 노마드 커뮤니티 매니저",
                            "지속 가능한 에너지 솔루션 디자이너",
                            "인공지능 기반 식물 성장 모니터",
                            "무인 배송 시스템 운영자",
                            "디지털 감정 표현 연구원",
                            "핀테크 솔루션 개발자",
                            "스마트 건축물 에너지 관리자",
                            "가상 현실 컨텐츠 큐레이터",
                            "생체모방 로봇 디자이너",
                            "디지털 건강 모니터링 시스템 개발자",
                            "우주 관측 데이터 분석가",
                            "바이오디지털 콘텐츠 크리에이터",
                            "스마트 의복 제작자",
                            "가상 현실 테마파크 디자이너",
                            "디지털 웰빙 코치",
                            "지속 가능한 에코빌리지 개발자",
                            "식용 곤충 농장 운영자",
                            "해저 도시 건축가",
                            "인공지능 재난 대응 조정자",
                            "스페이스 데브리 클리너",
                            "스마트 도로 시스템 설계자",
                            "바이오필릭 디자인 컨설턴트",
                            "디지털 유산 컨설턴트",
                            "사이버펑크 소설가",
                            "미래식 식단 개발자",
                            "가상 패션 쇼 오거나이저",
                            "스마트 공기질 모니터",
                            "우주 식량 생산자",
                            "생체 적응형 게임 개발자",
                            "디지털 통화 디자이너",
                            "마이크로리빙 공간 디자이너",
                            "가상 현실 교육 컨텐츠 개발자",
                            "빛 기반 통신 기술자",
                            "디지털 유물 보존 전문가",
                            "인공지능 기반 작곡가",
                            "바이오메트릭 데이터 분석가",
                            "3D 프린트 의류 디자이너",
                            "윤리적 AI 개발자",
                            "스마트 약물 전달 시스템 디자이너",
                            "재생 가능 에너지 벤처 캐피털리스트",
                            "초연결 사회 분석가",
                            "스팀(STEM) 교육 콘텐츠 크리에이터",
                            "가상 현실 심리 치료 연구원",
                            "환경 데이터 비주얼라이제이션 전문가",
                            "나노봇 연구 개발자",
                            "스마트 교통 체계 해커",
                            "지속 가능한 관광 기획자",
                            "어린이를 위한 프로그래밍 교육가",
                            "증강 현실 쇼핑 어드바이저",
                            "인터랙티브 디지털 아트워크 크리에이터",
                            "모바일 건강 진단 개발자",
                            "디지털 콘텐츠 저작권 관리자",
                            "로봇 윤리 컨설턴트", 
                            "스마트 시티 데이터 분석가",
                            "퍼소널 브랜딩 전문가",
                            "가상 현실 피트니스 트레이너",
                            "홀로그래픽 데이터 시각화 전문가",
                            "사이버 안전 교육가",
                            "디지털 음악 배포자",
                            "클라우드 기반 팀워크 플랫폼 개발자",
                            "인공지능 패션 컨설턴트",
                            "미래 도시 생활 컨설턴트",
                            "디지털 인권 변호사",
                            "가상 실감 콘텐츠 프로듀서",
                            "친환경 건축 기술자",
                            "인공지능 기반 도시 계획가",
                            "식물 기반 식품 혁신가",
                            "스마트 장난감 개발자",
                            "지속 가능한 생활 스타일 코치",
                            "소셜 미디어 데이터 분석가",
                            "초소형 위성 개발자",
                            "디지털 북 큐레이터",
                            "가상 현실 미술관 큐레이터",
                            "스마트 환경 모니터링 시스템 개발자",
                            "바이오피드백 테라피스트",
                            "우주 여행 가이드",
                            "심해 탐사 기술 개발자",
                            "디지털 윤리 컨설턴트",
                            "가상 멘토링 서비스 개발자",
                            "스마트 시티 생활 실험가",
                            "에너지 하베스팅 기술 연구원",
                            "사이버펑크 게임 디자이너",
                            "가상 현실 치료 연구 개발자",
                            "인공지능 기반 개인 건강 조언가",
                            "지속 가능한 패션 블로거",
                            "디지털 보안 컨설턴트",
                            "3D 바이오 프린팅 연구원",
                            "자율주행 도시 버스 시스템 디자이너",
                            "가상 현실 역사 교육가",
                            "인터넷 사물(IoT) 장난감 디자이너",
                            "스마트 농업 컨설턴트",
                            "로봇 공학 교육 전문가",
                            "디지털 인문학 연구자",
                            "가상 현실 스포츠 분석가",
                            "스마트 워터 관리 시스템 엔지니어",
                            "인공지능 기반 아트 테라피스트",
                            "지구 외 생명체 연구원",
                            "디지털 정체성 보호 전문가",
                            "자연 언어 처리 기술 개발자",
                            "가상 현실 여행 기획자",
                            "바이오리듬 분석가",
                            "스마트 교육 플랫폼 개발자",
                            "디지털 푸드 디자이너",
                            "가상 현실 콘서트 기획자",
                            "실시간 데이터 분석가",
                            "스마트 건강 진단 키트 개발자",
                            "인공지능 기반 재난 경보 시스템 개발자",
                            "디지털 커뮤니티 매니저",
                            "친환경 도시 디자인 전문가",
                            "가상 현실 교통 시스템 설계자",
                            "디지털 자산 관리자",
                            "스마트 홈 인테리어 디자이너"
                        ];
                        $randomDreamUrlList = [
                        "https://gamma.app/docs/-5dvdwrou2385tda",
                        "https://gamma.app/docs/-57oe1106fexvovx",
                        "https://gamma.app/docs/-w060d7y8nzrq6z1",
                        "https://gamma.app/docs/-xl03qnlzbhw0l3d",
                        "https://gamma.app/docs/Untitled-ekp8hywee87lsw8",
                        "https://gamma.app/docs/-ggn6grxhpvp0tdj",
                        "https://gamma.app/docs/-xieocbvr1u6hyd0",
                        "https://gamma.app/docs/-lp6kn8pqg1aqmec",
                        "https://gamma.app/docs/-fsuhnwucw8546bj",
                        "https://gamma.app/docs/-t55yu127yjsi9fo",
                        "https://gamma.app/docs/-8sln8zzhe487myk",
                        "https://gamma.app/docs/-62mq1zcgmekj0xw",
                        "https://gamma.app/docs/-80707aa8tnf1d8u",
                        "https://gamma.app/docs/-kep6ua7le4tcsup",
                        "https://gamma.app/docs/-xhdx8mkbak325bj",
                        "https://gamma.app/docs/-x9nfq80il9glyiz",
                        "https://gamma.app/docs/-020t0h8i64qt3ji",
                        "https://gamma.app/docs/-m3j16vvgfw4c2c3",
                        "https://gamma.app/docs/-o6e5u148e9n3hy0",
                        "https://gamma.app/docs/-vf3my60eukzau3p",
                        "https://gamma.app/docs/-s7945kxk45fptap",
                        "https://gamma.app/docs/-eatbhq1xto25lmc",
                        "https://gamma.app/docs/-ar1ok42v4guq3gr",
                        "https://gamma.app/docs/-vmhpuzstpj6z9iv",
                        "https://gamma.app/docs/-0vp4rijjzmxr5lb",
                        "https://gamma.app/docs/-xp3lp0v1pldkxke",
                        "https://gamma.app/docs/-irf6r12mpq21jxw",
                        "https://gamma.app/docs/-7lcr5rezdf6k9br",
                        "https://gamma.app/docs/-8u0i6dikdcq7r8q",
                        "https://gamma.app/docs/-8gfvga11by9e2so",
                        "https://gamma.app/docs/-bjb3fkradx5emgg",
                        "https://gamma.app/docs/-786otp42dq41g6i",
                        "https://gamma.app/docs/-s8ls52dgg1afk60",
                        "https://gamma.app/docs/-l1sbevclt9fnm2g",
                        "https://gamma.app/docs/-ojj0fz3q639r666",
                        "https://gamma.app/docs/-2i5ufv5j73nw010",
                        "https://gamma.app/docs/-y89z5ysjvw5292q",
                        "https://gamma.app/docs/-yuie5rba52v21os",
                        "https://gamma.app/docs/3D--ogt66n18dhu18ug",
                        "https://gamma.app/docs/-85vj1hcg4t3gk5a",
                        "https://gamma.app/docs/-gaycqrijcv024kp",
                        "https://gamma.app/docs/-d9c1i0e27m95mgi",
                        "https://gamma.app/docs/-fues7156ylaywrl",
                        "https://gamma.app/docs/-lt5ywf8tlrtqy96",
                        "https://gamma.app/docs/IoT--k5eard364ar18s2",
                        "https://gamma.app/docs/-jpm4pqw09kavgmn",
                        "https://gamma.app/docs/-aglumil3f2fhsyr",
                        "https://gamma.app/docs/-kxaz0e1sdoa7v3o",
                        "https://gamma.app/docs/-woyqxqy2jslwpn5",
                        "https://gamma.app/docs/-76e8minqsvpg0cy",
                        "https://gamma.app/docs/-0ieun0b7ocwfbne",
                        "https://gamma.app/docs/-1f6svi6cdmz504q",
                        "https://gamma.app/docs/-vqfbi2u1hoji2el",
                        "https://gamma.app/docs/-im8xxfov6cnhihy",
                        "https://gamma.app/docs/-mibiqp8hcuu7awc",
                        "https://gamma.app/docs/-bmarhtojhahq1j1",
                        "https://gamma.app/docs/-p2hfkaafbsm16hl",
                        "https://gamma.app/docs/-y8kdy750rryglya",
                        "https://gamma.app/docs/-7xlekxf04ouvn0d",
                        "https://gamma.app/docs/-gy5salsqbe1aclw",
                        "https://gamma.app/docs/-yn0m1sxume2atmu",
                        "https://gamma.app/docs/-l9o8mxlxbxnd857",
                        "https://gamma.app/docs/-pfvjxxck7buzkb3",
                        "https://gamma.app/docs/-9ys3rl17dte5han",
                        "https://gamma.app/docs/-va3ahhi49o4zt1y",
                        "https://gamma.app/docs/-yjt635pommyqnjw",
                        "https://gamma.app/docs/-smo5bdqm2kiim3i",
                        "https://gamma.app/docs/-0ogmzeyq5nzsgmx",
                        "https://gamma.app/docs/-23cqvaztlrgmhet",
                        "https://gamma.app/docs/-c8yqn0opzp4sf1i",
                        "https://gamma.app/docs/-irvfx6onndwlzsf",
                        "https://gamma.app/docs/-gdu3cpvjsatdjui",
                        "https://gamma.app/docs/-ji0vwzrqkbikrmn",
                        "https://gamma.app/docs/-qa8mndk27l5aomo",
                        "https://gamma.app/docs/-bur9fxba6i1x8d1",
                        "https://gamma.app/docs/-hzvnowwvabccbwq",
                        "https://gamma.app/docs/-r1o4o6i2epbkqca",
                        "https://gamma.app/docs/-3sztxs20giuz113",
                        "https://gamma.app/docs/-dw9yjujsfyxc6nf",
                        "https://gamma.app/docs/-arxf1nb6oc3cd90",
                        "https://gamma.app/docs/-0xdhc2gct6w50ex",
                        "https://gamma.app/docs/-sdxz58fnmthdzne",
                        "https://gamma.app/docs/-ow67c0m0cc2hz9w",
                        "https://gamma.app/docs/-s9yyaztanyp8jmm",
                        "https://gamma.app/docs/-m1di07ecxkzaci9",
                        "https://gamma.app/docs/-9wjv8fwtckqlslo",
                        "https://gamma.app/docs/-qzw0tepi62lt9mw",
                        "https://gamma.app/docs/-ek53gbeha0ddxpt",
                        "https://gamma.app/docs/-pd2cmjyv0g1zgdn",
                        "https://gamma.app/docs/-jdk8ofesnbubh3x",
                        "https://gamma.app/docs/-5z90lqmihqelfee",
                        "https://gamma.app/docs/-z09uxt4wt06t0yj",
                        "https://gamma.app/docs/-hpudiex8evcard0",
                        "https://gamma.app/docs/-35w0x4e4sh6e1kj",
                        "https://gamma.app/docs/-99kuwwh41xp7ekb",
                        "https://gamma.app/docs/-n5m1rxp195f7i2g",
                        "https://gamma.app/docs/-sazybl9byoh1fyg",
                        "https://gamma.app/docs/-974u0unjy1rqelq",
                        "https://gamma.app/docs/-jvjeu9uwc0ftmkh",
                        "https://gamma.app/docs/-hpp1f3azv2r349x",
                        "https://gamma.app/docs/-4aqckebehpskl59",
                        "https://gamma.app/docs/-zrml04adt5wey73",
                        "https://gamma.app/docs/-kl1sb32tn0sxewh",
                        "https://gamma.app/docs/-zfwln3s9ugm0evt",
                        "https://gamma.app/docs/-uwgll8wuguxfbmw",
                        "https://gamma.app/docs/-5alqycnuvc19f6r",
                        "https://gamma.app/docs/-ok9kxdjxygn3rvc",
                        "https://gamma.app/docs/-gsrtc9l54d0pqnr",
                        "https://gamma.app/docs/-qi1vcxkpezgvke7",
                        "https://gamma.app/docs/-ov1qo1vsw4x8uui",
                        "https://gamma.app/docs/-zngnj3lpxotv04u",
                        "https://gamma.app/docs/-nwcwn0b225b7bca",
                        "https://gamma.app/docs/-furx4dgvbi4xf51",
                        "https://gamma.app/docs/3D--ean6ri9hgok5n95",
                        "https://gamma.app/docs/-ehs98d8rlqy8pmg",
                        "https://gamma.app/docs/-thv0e2qqiie28s9",
                        "https://gamma.app/docs/-sk1ylzw8j4l9l39",
                        "https://gamma.app/docs/-euslasa7gfuxrku",
                        "https://gamma.app/docs/-s4wtoj4o6rqnopc",
                        "https://gamma.app/docs/-780pgeei0qx25h8",
                        "https://gamma.app/docs/-44wyuyxioo7277f",
                        "https://gamma.app/docs/-w0e2gg0nvmecf0r",
                        "https://gamma.app/docs/-n0ecytk4ir2l3q0",
                        "https://gamma.app/docs/-tl4ev3qjscvno36",
                        "https://gamma.app/docs/-9o6p0jm95ma09rc",
                        "https://gamma.app/docs/-xr2qnk3sp6vajso",
                        "https://gamma.app/docs/-v5814mccretdisl",
                        "https://gamma.app/docs/-zm5sxdwve0dfy1w",
                        "https://gamma.app/docs/-tej2n6x0lrcn6jh",
                        "https://gamma.app/docs/-a9rti7t9r8ftoz8",
                        "https://gamma.app/docs/-g1fcwyjgqurig5p",
                        "https://gamma.app/docs/-cerh1y5s7ahqhb8",
                        "https://gamma.app/docs/-vigfsykbazobo0f",
                        "https://gamma.app/docs/-fbw4ghwx9ykckrs",
                        "https://gamma.app/docs/-y1np44iewv8dc3i",
                        "https://gamma.app/docs/-rbasvcsnn7ubb0n",
                        "https://gamma.app/docs/-eqk70dczaysywqm",
                        "https://gamma.app/docs/-zfq2iycdrlgi8ei",
                        "https://gamma.app/docs/AI--70up9dn6u4w2qif",
                        "https://gamma.app/docs/-av83z8lubexyvau",
                        "https://gamma.app/docs/-n3vbdyrqcwfgmr4",
                        "https://gamma.app/docs/-0pyfsqapoinpe5e",
                        "https://gamma.app/docs/-rcret9petbw6j4u",
                        "https://gamma.app/docs/-88y7o3m0tegcyaf",
                        "https://gamma.app/docs/-0dz3tdtve83hj9e",
                        "https://gamma.app/docs/-ar3wpbiecpqwt7t",
                        "https://gamma.app/docs/-1llco26yb7574s9",
                        "https://gamma.app/docs/-3jpj0s3zrbge35w",
                        "https://gamma.app/docs/-fo7aqkpv03my2h1",
                        "https://gamma.app/docs/-48o1dsqqg2tfzke",
                        "https://gamma.app/docs/-smrrs3k0xbb4f8c",
                        "https://gamma.app/docs/-40oys8w4o3iomcg",
                        "https://gamma.app/docs/-u42vb63744f7tbf",
                        "https://gamma.app/docs/-ayupuc51t4mqk8g",
                        "https://gamma.app/docs/-bwm6i1s2w4zoqy6",
                        "https://gamma.app/docs/-l8w49otlnl6op6m",
                        "https://gamma.app/docs/-wq5duc8l59bc3m4",
                        "https://gamma.app/docs/-no3473h2otca72v",
                        "https://gamma.app/docs/-tk01witpmfknxcs",
                        "https://gamma.app/docs/-zh0dqtrvekx5dgw",
                        "https://gamma.app/docs/-c0o5fptdmgb6qui",
                        "https://gamma.app/docs/-5wxo1qeix524i00",
                        "https://gamma.app/docs/-hgz318oy0i3z5py",
                        "https://gamma.app/docs/-5a7holiv5a8kots",
                        "https://gamma.app/docs/-s6by5uwvo4md71m",
                        "https://gamma.app/docs/-nfacws7qmo90whm",
                        "https://gamma.app/docs/-8yisry5lvbwa276",
                        "https://gamma.app/docs/-m0mvyrbgsp6i0id",
                        "https://gamma.app/docs/-zsqtr12lzs915bx",
                        "https://gamma.app/docs/-ddwaym785qvf7jz",
                        "https://gamma.app/docs/-1mz9nx0x3y5u71t",
                        "https://gamma.app/docs/-u8ofrmkyde0ywvg",
                        "https://gamma.app/docs/-ld75wf9wiurtivi",
                        "https://gamma.app/docs/-tht5mo8sebz6qoq",
                        "https://gamma.app/docs/-ku4hffhzfauxnr4",
                        "https://gamma.app/docs/-vrjfv2r6nhczroi",
                        "https://gamma.app/docs/-6oj9cd457ci3bbp",
                        "https://gamma.app/docs/-2dipwswg7b1ialm",
                        "https://gamma.app/docs/-l9wmjx25ra15uve",
                        "https://gamma.app/docs/-v1njqusg5df74iq",
                        "https://gamma.app/docs/-1p339xhawye47sk",
                        "https://gamma.app/docs/-0tn3lev1b2j53q0",
                        "https://gamma.app/docs/-wnaqow3l2w184y9",
                        "https://gamma.app/docs/-sirxql7pzrtjn0y",
                        "https://gamma.app/docs/-mqzjq5h0g6b0s4h",
                        "https://gamma.app/docs/-5o516w25he0czvm",
                        "https://gamma.app/docs/-x6dk4b3omffsu6s",
                        "https://gamma.app/docs/-j3442t7fphfkzes",
                        "https://gamma.app/docs/-2nbehuf6v0klncz",
                        "https://gamma.app/docs/-ukdtlnqska8shc6",
                        "https://gamma.app/docs/-7jtolc4vsruchqd",
                        "https://gamma.app/docs/-a0eahumuaiob698",
                        "https://gamma.app/docs/-f73jlwiaus04tw8",
                        "https://gamma.app/docs/-coably0qug18ude",
                        "https://gamma.app/docs/-hci0vqp1xpelbe2",
                        "https://gamma.app/docs/-otq04ruv3f5a05i",
                        "https://gamma.app/docs/-z7aggnyryk7x3tu",
                        "https://gamma.app/docs/-6f4p9sm2n9ztwiu",
                        "https://gamma.app/docs/-w4puioqbeub828a",
                        "https://gamma.app/docs/-2lcpwhk99phlw7g",
                        "https://gamma.app/docs/-f9z76ssyqhlizrj",
                        "https://gamma.app/docs/-0bsoaujm17p6dal",
                        "https://gamma.app/docs/-0prfrtnuwl0s9e0",
                        "https://gamma.app/docs/-8bee923pa12g5mj",
                        "https://gamma.app/docs/-3dw8qbzqww3zc0k",
                        "https://gamma.app/docs/-k5rcd050v4nta1h",
                        "https://gamma.app/docs/-t4j0ezy2u4dnhqr",
                        "https://gamma.app/docs/-soi31stkix1f7y3",
                        "https://gamma.app/docs/-o9wxhxm1nw9sma5",
                        "https://gamma.app/docs/-5z14zciln3u2b8h",
                        "https://gamma.app/docs/-5u8cv8qubldmoan",
                        "https://gamma.app/docs/-odj6m1jh5p76bah",
                        "https://gamma.app/docs/-ujm1q396y91mih8",
                        "https://gamma.app/docs/-jfgosssv4y92wg2",
                        "https://gamma.app/docs/-dtm0jtyflgnybmf",
                        "https://gamma.app/docs/-g8djh80xbasd2kq",
                        "https://gamma.app/docs/-mlse7lpwkmt1aga",
                        "https://gamma.app/docs/-drffurx6tt3sjtd",
                        "https://gamma.app/docs/-pmmly3etukq8eyy",
                        "https://gamma.app/docs/-sb73aoic39wpdev",
                        "https://gamma.app/docs/-37lc8t3ajx09xyq",
                        "https://gamma.app/docs/-0f6alctotc8kdg8",
                        "https://gamma.app/docs/-xtiil4tuhmynq73",
                        "https://gamma.app/docs/-z9s4904gru83euq",
                        "https://gamma.app/docs/3D--b78zrohehtx1soq",
                        "https://gamma.app/docs/AI--l0xk4jegi5zelfd",
                        "https://gamma.app/docs/-mcyeio63ohaaxc8",
                        "https://gamma.app/docs/-2bxqnz8sr2y6k7q",
                        "https://gamma.app/docs/-hk1d6usnb86kmur",
                        "https://gamma.app/docs/STEM--t0o671d8jcl7hh6",
                        "https://gamma.app/docs/-lq0e6hji6y0hf11",
                        "https://gamma.app/docs/-o59uc1nem7kdapn",
                        "https://gamma.app/docs/-fcplxerug5qktrb",
                        "https://gamma.app/docs/-vqz9xycvlct18hi",
                        "https://gamma.app/docs/-i1uhs4bhr3m8w52",
                        "https://gamma.app/docs/-dzjbyjipbck9xkd",
                        "https://gamma.app/docs/-t9pwhbcgef0ay0b",
                        "https://gamma.app/docs/-xz1jb3ndll6nwwm",
                        "https://gamma.app/docs/-klo0zim3gda2bkg",
                        "https://gamma.app/docs/-6zu5oowhwcjqyta",
                        "https://gamma.app/docs/-7r5wooqdp1lup83",
                        "https://gamma.app/docs/-r8fe3krrcirtbr2",
                        "https://gamma.app/docs/-5w47hzlhmksor8x",
                        "https://gamma.app/docs/-hea7rkt8c75xsz9",
                        "https://gamma.app/docs/-bidqj1suf8wjxxg",
                        "https://gamma.app/docs/-ea8qnwtiqzxkycd",
                        "https://gamma.app/docs/-c7z4xxrfk8nfsaa",
                        "https://gamma.app/docs/-fr84pplewqmq5y4",
                        "https://gamma.app/docs/-poan9q9ti03458y",
                        "https://gamma.app/docs/-jopl4d6mcjp96ng",
                        "https://gamma.app/docs/-czm4xyvwa8crhrt",
                        "https://gamma.app/docs/-ihfgzhbcarh10q0",
                        "https://gamma.app/docs/-ibaakdxo12f4u2b",
                        "https://gamma.app/docs/-yr9ubk8zgqxem7q",
                        "https://gamma.app/docs/-l9tovcnqzjaej07",
                        "https://gamma.app/docs/-y842ux8dzrdg3id",
                        "https://gamma.app/docs/-uit55beir3cz4p9",
                        "https://gamma.app/docs/-kub6tvvn0oerko2",
                        "https://gamma.app/docs/-p41y9kcg0yrq7wu",
                        "https://gamma.app/docs/-qthgzsigpuvryzb",
                        "https://gamma.app/docs/-piceqvzb261cii2",
                        "https://gamma.app/docs/-3hjhkq9r58mjfv5",
                        "https://gamma.app/docs/-k1yb8827tf7qmy2",
                        "https://gamma.app/docs/-n9zsk22ad7hts5j",
                        "https://gamma.app/docs/-0oo8x9wyg4vfchh",
                        "https://gamma.app/docs/-edvqzvsoyty1h0o",
                        "https://gamma.app/docs/-6iz6f0iix4psp9e",
                        "https://gamma.app/docs/-lo1n49f498u6sbm",
                        "https://gamma.app/docs/-im201p4ih10xfo2",
                        "https://gamma.app/docs/-taqkek9v5260m6d",
                        "https://gamma.app/docs/-m7eqz1zlf2sjo1r",
                        "https://gamma.app/docs/-rof8j779av6x6bg",
                        "https://gamma.app/docs/-4qnc6omk3d9k0au",
                        "https://gamma.app/docs/-5lemyq26jesegle",
                        "https://gamma.app/docs/3D--vredyazv3l3ixca",
                        "https://gamma.app/docs/-qgf9tnshsruhxtp",
                        "https://gamma.app/docs/-9w47pwn4dxdbkkb",
                        "https://gamma.app/docs/IoT--u8rh591u9o3oawd",
                        "https://gamma.app/docs/-ldsxn2i7r3z5koi",
                        "https://gamma.app/docs/-qij7be7s7fk0wgw",
                        "https://gamma.app/docs/-t6ihe80b0il2s2i",
                        "https://gamma.app/docs/-6gd9bw5reyff55x",
                        "https://gamma.app/docs/-pkovx47mw4di70k",
                        "https://gamma.app/docs/-rsq3538k9a3ke54",
                        "https://gamma.app/docs/-fwie4lhsndundlh",
                        "https://gamma.app/docs/-0quvqevx9znbthk",
                        "https://gamma.app/docs/-hks23k6es0smskr",
                        "https://gamma.app/docs/-pdyuwwav7huqhlr",
                        "https://gamma.app/docs/-oq70dh1r7uemiig",
                        "https://gamma.app/docs/-eq5k6uhrw786li0",
                        "https://gamma.app/docs/-iwj56vtf9h11ixg",
                        "https://gamma.app/docs/-7tsttez08fxgdpx",
                        "https://gamma.app/docs/-fym0tsusvwsnb42",
                        "https://gamma.app/docs/-3kuckp7o9dcgoxt",
                        "https://gamma.app/docs/-w2pmd490v9p8fq1",
                        "https://gamma.app/docs/-oyve39k43dddtkx",
                        "https://gamma.app/docs/-18uti4ah6wddwha",
                        "https://gamma.app/docs/-ksvto4dpib2ka5l",
                        "https://gamma.app/docs/-5pgvq8vxgdy7tmf",
                        "https://gamma.app/docs/-ea7j989edc17xrk"
                        ];
                        // 기존에 저장된 꿈 챌린지 확인
                        $savedDream = $DB->get_record_sql("SELECT * FROM mdl_abessi_progress WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");
                        
                        if($savedDream && $savedDream->dreamchallenge !== NULL && $savedDream->dreamchallenge !== '') {
                            // 저장된 꿈이 있으면 그것을 사용
                            $randomDream = $savedDream->dreamchallenge;
                            $randomDreamUrl = $savedDream->dreamurl ? $savedDream->dreamurl : $randomDreamUrlList[0];
                        } else {
                            // 저장된 꿈이 없으면 랜덤 선택
                            $index = array_rand($randomDreamList);
                            $randomDream = $randomDreamList[$index];
                            $randomDreamUrl = $randomDreamUrlList[$index];
                        }
                        ?>

                        <!-- 입력하기 버튼 -->
                        <div style="margin-top: 20px;" id="quarterGoalAddButton">
                            <button class="quiet-input-btn" onclick="toggleQuarterGoalForm()">
                                <i class="fas fa-edit"></i> 입력하기
                            </button>
                        </div>

                        <!-- 분기목표 입력 폼 (기본 숨김) -->
                        <div class="goal-form" id="quarterGoalForm" style="margin-top: 20px; display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4 style="margin: 0;">분기목표 설정</h4>
                                <button type="button" style="background: transparent; border: none; color: var(--text-secondary); font-size: 20px; cursor: pointer; padding: 0; line-height: 1;" onclick="toggleQuarterGoalForm()" title="닫기">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="form-row">
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" class="form-control" id="quarterGoalInput" placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요">
                                </div>
                                <div class="form-group">
                                    <input type="date" class="form-control" id="quarterDeadline" value="<?php echo date('Y-m-d', time() + 86400 * 90); ?>">
                                </div>
                            </div>
                            <div class="form-row" style="margin-top: 10px;">
                                <div class="form-group" style="width: 100%;">
                                    <button class="btn-submit" style="width: 100%;" onclick="if(!this.disabled && !savingStates['quarterGoal1']) { this.disabled=true; inputgoalstep(8, <?php echo $studentid; ?>, '분기목표', document.getElementById('quarterDeadline').value, document.getElementById('quarterGoalInput').value, document.getElementById('randomDreamText').innerText, currentDreamUrl); setTimeout(()=>this.disabled=false, 3000); }">저장</button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 수학일기 Tab -->
            <div id="diary" class="tab-content">
                <div class="card" id="diaryCard">
                    <div class="section-header primary" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>수학 일기</span>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div class="mode-toggle-buttons" style="display: flex; gap: 10px;">
                                <button class="mode-btn active" onclick="switchDiaryMode('view')" id="diaryViewBtn" style="padding: 5px 15px; border: 1px solid #fff; background: rgba(255,255,255,0.2); color: white; border-radius: 5px; cursor: pointer;">보기 모드</button>
                                <button class="mode-btn" onclick="switchDiaryMode('edit')" id="diaryEditBtn" style="padding: 5px 15px; border: 1px solid #fff; background: transparent; color: white; border-radius: 5px; cursor: pointer;">입력 모드</button>
                            </div>
                        </div>
                    </div>

                    <!-- 보기 모드 -->
                    <div class="card-body" id="diaryViewMode">
                        <?php if (!empty($diaryPlans)): ?>
                        <h4>최근 수학 일기 (<?php echo date('Y-m-d H:i', $diaryRecord->timecreated); ?> 작성)</h4>
                        <table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr style="background: rgba(51, 131, 255, 0.1); border-bottom: 2px solid #3383ff;">
                                    <th style="padding: 12px; text-align: center; width: 6%;">#</th>
                                    <th style="padding: 12px; text-align: center; width: 12%;">시간</th>
                                    <th style="padding: 12px; text-align: center; width: 8%;">소요시간</th>
                                    <th style="padding: 12px; text-align: left; width: 42%;">학습 내용</th>
                                    <th style="padding: 12px; text-align: center; width: 8%;">링크</th>
                                    <th style="padding: 12px; text-align: center; width: 8%;">상태</th>
                                    <th style="padding: 12px; text-align: center; width: 16%;">피드백</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 시작 시간 계산 - 5분 단위로 반올림
                                $createdTime = $diaryRecord->timecreated;
                                $minutes = (int)date('i', $createdTime);
                                $roundedMinutes = ceil($minutes / 5) * 5;

                                if ($roundedMinutes >= 60) {
                                    // 60분 이상이면 다음 시간 00분으로
                                    $currentTime = strtotime('+1 hour', strtotime(date('Y-m-d H:00:00', $createdTime)));
                                } else {
                                    // 현재 시간에서 분만 5의 배수로 조정
                                    $currentTime = strtotime(date('Y-m-d H:', $createdTime) . sprintf('%02d', $roundedMinutes) . ':00');
                                }

                                foreach ($diaryPlans as $entry):
                                    // 현재 항목의 시작 시간
                                    $startTime = $currentTime;
                                    $endTime = $startTime + ($entry['duration'] * 60); // 분을 초로 변환

                                    // 시간 포맷: HH:MM~HH:MM
                                    $timeRange = date('H:i', $startTime) . '~' . date('H:i', $endTime);

                                    // 다음 항목을 위해 현재 시간 업데이트
                                    $currentTime = $endTime;
                                ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 12px; text-align: center; font-weight: bold; color: #3383ff;">
                                        <?php echo $entry['index']; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: #666; font-size: 13px;">
                                        <?php echo $timeRange; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: #666;">
                                        <?php echo $entry['duration']; ?>분
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php echo htmlspecialchars($entry['plan'], ENT_QUOTES); ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if (!empty($entry['url'])): ?>
                                            <?php
                                            $urls = explode(',', $entry['url']);
                                            foreach ($urls as $url) {
                                                $url = trim($url);
                                                if (!empty($url)) {
                                                    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" style="margin-right: 5px;">🔗</a>';
                                                }
                                            }
                                            ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if (empty($entry['status'])): ?>
                                            <input type="checkbox" class="status-checkbox-diary" data-week="<?php echo $entry['index']; ?>" style="width: 20px; height: 20px; cursor: pointer;" title="만족도 선택">
                                        <?php else: ?>
                                            <?php
                                            // 만족도에 따른 배경색 설정
                                            $statusBgColor = '#e3f2fd'; // 기본값
                                            $statusTextColor = '#fff'; // 기본 흰색 텍스트
                                            if ($entry['status'] === '매우만족') {
                                                $statusBgColor = '#2196F3'; // 파란색
                                            } elseif ($entry['status'] === '만족') {
                                                $statusBgColor = '#4CAF50'; // 녹색
                                            } elseif ($entry['status'] === '불만족') {
                                                $statusBgColor = '#FF9800'; // 주황색
                                            }
                                            ?>
                                            <span class="status-text-diary" style="padding: 4px 8px; background: <?php echo $statusBgColor; ?>; border-radius: 4px; font-size: 14px; color: <?php echo $statusTextColor; ?>;"><?php echo htmlspecialchars($entry['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;" class="feedback-cell" data-week="<?php echo $entry['index']; ?>">
                                        <button type="button" class="feedback-btn-view"
                                                data-week="<?php echo $entry['index']; ?>"
                                                data-record-id="<?php echo $diaryRecord->id; ?>"
                                                data-student-id="<?php echo $studentid; ?>"
                                                data-current-fback="<?php echo htmlspecialchars($entry['fback'], ENT_QUOTES); ?>"
                                                style="background: none; border: none; font-size: 18px; cursor: pointer; padding: 5px;"
                                                title="피드백 <?php echo !empty($entry['fback']) ? '수정' : '입력'; ?>">
                                            ✏️
                                        </button>
                                        <?php if (!empty($entry['fback'])): ?>
                                            <div class="feedback-preview" style="margin-top: 5px; font-size: 12px; color: #666; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help;" title="<?php echo htmlspecialchars($entry['fback']); ?>">
                                                <?php echo htmlspecialchars(mb_substr($entry['fback'], 0, 12)); ?><?php echo mb_strlen($entry['fback']) > 12 ? '...' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="margin-top: 15px; padding: 10px; background: rgba(51, 131, 255, 0.05); border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <?php
                            // 총 소요시간 계산 - 시작 시간을 5분 단위로 반올림
                            $totalDuration = array_sum(array_column($diaryPlans, 'duration'));

                            $createdTime = $diaryRecord->timecreated;
                            $minutes = (int)date('i', $createdTime);
                            $roundedMinutes = ceil($minutes / 5) * 5;

                            if ($roundedMinutes >= 60) {
                                $summaryStartTime = strtotime('+1 hour', strtotime(date('Y-m-d H:00:00', $createdTime)));
                            } else {
                                $summaryStartTime = strtotime(date('Y-m-d H:', $createdTime) . sprintf('%02d', $roundedMinutes) . ':00');
                            }

                            $summaryEndTime = $summaryStartTime + ($totalDuration * 60);
                            ?>
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                <i class="fas fa-info-circle"></i>
                                총 <?php echo count($diaryPlans); ?>개 학습 항목 |
                                총 소요시간: <?php echo $totalDuration; ?>분 |
                                학습 시간: <?php echo date('H:i', $summaryStartTime); ?> ~ <?php echo date('H:i', $summaryEndTime); ?>
                            </p>
                            <a href="../alt42/orchestration/agents/agent14_current_position/ui/dashboard.php?id=<?php echo $studentid; ?>"
                               style="padding: 6px 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; box-shadow: 0 3px 12px rgba(102, 126, 234, 0.4); transition: all 0.3s; white-space: nowrap;"
                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 18px rgba(102, 126, 234, 0.6)';"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 12px rgba(102, 126, 234, 0.4)';"
                               target="_blank">
                                <i class="fas fa-map-marked-alt"></i>
                                현재 위치 평가
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <p>최근 12시간 이내 작성된 수학 일기가 없습니다.</p>
                            <p style="font-size: 14px; color: #999;">입력 모드로 전환하여 오늘의 학습 내용을 기록해보세요.</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 입력 모드 (iframe) -->
                    <div class="card-body iframe-fullwidth" id="diaryEditMode" style="display: none; padding: 0;">
                        <iframe id="diary-iframe"
                                src="todayplans.php?id=<?php echo $studentid; ?>&cid=<?php echo $_GET['cid'] ?? 106; ?>&pid=<?php echo $studentid; ?>&nch=9"
                                style="width: 100%; min-height: 800px; border: none; display: block;"
                                onload="resizeDiaryIframe()">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll View Container -->
        <div id="scrollView" class="scroll-container">
            <!-- 오늘 목표 섹션 -->
            <div class="card">
                <div class="section-header primary">
                    <span>오늘 목표</span>
                </div>
                <div class="card-body">
                    <?php if($hideinput == 0 || $role !== 'student'): ?>
                    <div class="editable-goal" onclick="editGoal('todayScroll')" id="todayScrollGoalEditor">
                        <?php if($todayGoal): ?>
                        <span class="goal-text"><?php echo htmlspecialchars($todayGoal->text); ?></span>
                        <?php else: ?>
                        <span class="placeholder-text">클릭해서 오늘의 목표를 입력하세요</span>
                        <?php endif; ?>
                        <span class="edit-hint">클릭하여 편집</span>
                        <span class="saving-indicator" id="todayScrollSaving">저장 중...</span>
                    </div>
                    <?php else: ?>
                    <?php if($todayGoal): ?>
                    <div class="goal-text" style="padding: 12px 16px; background: rgba(51, 131, 255, 0.05); border-radius: 8px;">
                        <?php echo htmlspecialchars($todayGoal->text); ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>오늘 목표가 설정되지 않았습니다.</p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 주간목표 섹션 (9주 계획) -->
            <div class="card">
                <div class="section-header secondary">
                    <span>주간 목표 (9주 계획)</span>
                    <span>Progress ID: <?php echo $progressid; ?></span>
                </div>
                <div class="card-body">
                    <?php if(!empty($weeklyGoals)): ?>
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(51, 131, 255, 0.1); border-bottom: 2px solid #3383ff;">
                                <th style="padding: 12px; text-align: center; width: 10%;">주차</th>
                                <th style="padding: 12px; text-align: center; width: 15%;">날짜</th>
                                <th style="padding: 12px; text-align: left; width: 75%;">주간 목표</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasData = false;
                            foreach($weeklyGoals as $goal):
                                if(!empty($goal['plan'])):
                                    $hasData = true;
                            ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: 12px; text-align: center; font-weight: bold; color: #3383ff;">
                                    <?php echo $goal['week']; ?>주차
                                </td>
                                <td style="padding: 12px; text-align: center; color: #666;">
                                    <?php
                                    if(!empty($goal['date'])) {
                                        $dateValue = is_numeric($goal['date']) ? $goal['date'] : strtotime($goal['date']);
                                        echo date('Y-m-d', $dateValue);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td style="padding: 12px; color: #333;">
                                    <?php echo htmlspecialchars($goal['plan']); ?>
                                </td>
                            </tr>
                            <?php
                                endif;
                            endforeach;

                            if(!$hasData):
                            ?>
                            <tr>
                                <td colspan="3" style="padding: 30px; text-align: center; color: #999;">
                                    <i class="fas fa-calendar-week" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    <p>주간 목표가 설정되지 않았습니다.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Debug: Display weeklyplans data -->
                    <script>
                    console.log('Weekly Plans Data:', {
                        progressid: <?php echo $progressid; ?>,
                        record_id: <?php echo $weeklyPlansRecord ? $weeklyPlansRecord->id : 0; ?>,
                        total_weeks: <?php echo count($weeklyGoals); ?>,
                        weeks_with_data: <?php echo count(array_filter($weeklyGoals, function($g) { return !empty($g['plan']); })); ?>,
                        data: <?php echo json_encode($weeklyGoals); ?>
                    });
                    </script>

                    <?php else: ?>
                    <div class="empty-state" style="padding: 40px; text-align: center;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ff6b6b; margin-bottom: 15px;"></i>
                        <p style="font-size: 16px; color: #666;">분기목표가 설정되지 않았습니다.</p>
                        <p style="font-size: 14px; color: #999;">먼저 분기목표를 설정해주세요.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($goalhistory1): ?>
                    <div style="margin-top: 30px;">
                        <h4 style="margin-bottom: 15px; color: var(--warning-color);">주간 목표 기록</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="20%">날짜</th>
                                    <th width="20%">유형</th>
                                    <th width="60%">목표</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $goalhistory1; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 분기목표 섹션 -->
            <div class="card">
                <div class="section-header success">
                    <span>분기목표</span>
                </div>
                <div class="card-body">
                    <?php if(count($missionresult) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="20%">유형</th>
                                <th width="50%">목표</th>
                                <th width="20%">데드라인</th>
                                <th width="10%">D-Day</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($missionresult as $mission) {
                                $dday = round(($mission['deadline'] - time()) / 86400) + 1;
                                $dateString = date("Y-m-d", $mission['deadline']);
                                $plantype = ($mission['plantype'] === '분기목표') ? '<b style="color:purple;">분기목표</b>' : '<b style="color:red;">방향설정</b>';
                                
                                echo '<tr>';
                                echo '<td>'.$plantype.'</td>';
                                echo '<td>'.iconv_substr($mission['memo'], 0, 70, "utf-8").'</td>';
                                echo '<td>'.$dateString.'</td>';
                                echo '<td>D-'.$dday.'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-route"></i>
                        <p>설정된 분기목표가 없습니다.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($hideinput == 0 || $role !== 'student'): ?>
                    <?php
                    // 스크롤 뷰에서도 같은 랜덤 꿈 데이터 사용
                    ?>
                    <div class="goal-form" style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px;">분기목표 설정</h4>
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" class="form-control" id="quarterGoalInput2" placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요">
                            </div>
                            <div class="form-group">
                                <input type="date" class="form-control" id="quarterDeadline2" value="<?php echo date('Y-m-d', time() + 86400 * 90); ?>">
                            </div>
                            <div class="form-group">
                                <button class="btn-submit" onclick="if(!this.disabled && !savingStates['quarterGoal1']) { this.disabled=true; inputgoalstep(8, <?php echo $studentid; ?>, '분기목표', document.getElementById('quarterDeadline2').value, document.getElementById('quarterGoalInput2').value, document.getElementById('randomDreamText2').innerText, currentDreamUrl); setTimeout(()=>this.disabled=false, 3000); }">저장</button>
                            </div>
                        </div>
                        <div style="margin-top: 10px; padding: 10px; background: rgba(51, 131, 255, 0.1); border-radius: 8px;">
                            <p style="margin: 0; font-size: 14px; color: var(--info-color); display: flex; align-items: center; justify-content: space-between;">
                                <span>
                                    🌟 랜덤 꿈 챌린지: <strong id="randomDreamText2"><?php echo $randomDream; ?></strong>
                                    <a href="<?php echo $randomDreamUrl; ?>" target="_blank" style="margin-left: 10px; font-size: 12px; color: var(--info-color);" id="dreamDetailLink2">자세히 보기</a>
                                </span>
                                <button type="button" class="btn btn-sm" style="background: var(--info-color); color: white; border: none; padding: 4px 12px; border-radius: 4px; font-size: 12px;" onclick="changeRandomDream2()">바꾸기</button>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 퀴즈 현황 섹션 -->
            <div class="card">
                <div class="section-header info">
                    <span>테스트 현황</span>
                </div>
                <div class="card-body">
                    <div class="stats-box">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $nquiz; ?></div>
                            <div class="stat-label">전체 퀴즈</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $ntodayquiz; ?></div>
                            <div class="stat-label">오늘 완료</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $avgToday; ?>점</div>
                            <div class="stat-label">오늘 평균</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $avgWeek; ?>점</div>
                            <div class="stat-label">주간 평균</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Core JS Files -->
<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Goal data for pagination
const dailyGoals = <?php echo $dailyGoalsJS; ?>;
const weeklyGoals = <?php echo $weeklyGoalsJS; ?>;

// Pagination state
let currentPages = {
    daily: 1,
    weekly: 1
};

const ITEMS_PER_PAGE = 3;

// Load more goals function
function loadMoreGoals(type) {
    const goals = type === 'daily' ? dailyGoals : weeklyGoals;
    const tableId = type === 'daily' ? 'dailyGoalsTable' : 'weeklyGoalsTable';
    const btnId = type === 'daily' ? 'dailyMoreBtn' : 'weeklyMoreBtn';
    const currentPage = currentPages[type];
    
    const startIndex = currentPage * ITEMS_PER_PAGE;
    const endIndex = Math.min(startIndex + ITEMS_PER_PAGE, goals.length);
    
    const table = document.getElementById(tableId);
    
    // Add new rows
    for(let i = startIndex; i < endIndex; i++) {
        const goal = goals[i];
        const row = table.insertRow();
        
        // Apply current goal highlighting if this is the current goal
        if(goal.isCurrent) {
            row.classList.add('current-goal');
        }
        
        row.innerHTML = `
            <td>${goal.date}</td>
            <td>${goal.type}</td>
            <td>${goal.text}</td>
        `;
    }
    
    // Update page counter
    currentPages[type]++;
    
    // Update or hide button
    const btn = document.getElementById(btnId);
    const remainingItems = goals.length - (endIndex);
    
    if(remainingItems <= 0) {
        btn.style.display = 'none';
    } else {
        btn.innerHTML = `<i class="fas fa-chevron-down"></i> 더보기 (${remainingItems}개)`;
    }
}

// Current view state
let currentView = 'tab';

// View Toggle Function
function toggleView() {
    const tabView = document.getElementById('tabView');
    const scrollView = document.getElementById('scrollView');
    const toggleBtn = document.querySelector('.view-toggle-btn');
    const viewIcon = document.getElementById('viewIcon');
    
    if(currentView === 'tab') {
        currentView = 'scroll';
        tabView.classList.remove('active');
        scrollView.classList.add('active');
        toggleBtn.classList.add('scroll-mode');
        toggleBtn.title = '탭 뷰로 전환';
        viewIcon.className = 'fas fa-stream';
        localStorage.setItem('goalsPreferredView', 'scroll');
    } else {
        currentView = 'tab';
        tabView.classList.add('active');
        scrollView.classList.remove('active');
        toggleBtn.classList.remove('scroll-mode');
        toggleBtn.title = '스크롤 뷰로 전환';
        viewIcon.className = 'fas fa-folder';
        localStorage.setItem('goalsPreferredView', 'tab');
    }
}

// Tab Switching Functions
function openTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabContents.forEach(content => content.classList.remove('active'));
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    
    const activeBtn = Array.from(tabBtns).find(btn => 
        btn.textContent.includes(getTabTitle(tabName))
    );
    if(activeBtn) activeBtn.classList.add('active');
    
    localStorage.setItem('goalsActiveTab', tabName);
}

function getTabTitle(tabName) {
    const titles = {
        'today': '오늘목표',
        'weekly': '주간목표',
        'roadmap': '분기목표',
        'diary': '수학일기'
    };
    return titles[tabName] || tabName;
}

// Mode switching functions for tabs
function switchTodayMode(mode) {
    const viewMode = document.getElementById('todayViewMode');
    const editMode = document.getElementById('todayEditMode');
    const viewBtn = document.getElementById('todayViewBtn');
    const editBtn = document.getElementById('todayEditBtn');
    const card = document.getElementById('todayCard');

    if (mode === 'view') {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        viewBtn.classList.add('active');
        editBtn.classList.remove('active');
        viewBtn.style.background = 'rgba(255,255,255,0.2)';
        editBtn.style.background = 'transparent';
        card.classList.remove('card-fullwidth');
    } else {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        viewBtn.classList.remove('active');
        editBtn.classList.add('active');
        viewBtn.style.background = 'transparent';
        editBtn.style.background = 'rgba(255,255,255,0.2)';
        card.classList.add('card-fullwidth');
    }
}

function switchWeeklyMode(mode) {
    const viewMode = document.getElementById('weeklyViewMode');
    const editMode = document.getElementById('weeklyEditMode');
    const viewBtn = document.getElementById('weeklyViewBtn');
    const editBtn = document.getElementById('weeklyEditBtn');
    const card = document.getElementById('weeklyCard');

    if (mode === 'view') {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        viewBtn.classList.add('active');
        editBtn.classList.remove('active');
        viewBtn.style.background = 'rgba(255,255,255,0.2)';
        editBtn.style.background = 'transparent';
        card.classList.remove('card-fullwidth');
    } else {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        viewBtn.classList.remove('active');
        editBtn.classList.add('active');
        viewBtn.style.background = 'transparent';
        editBtn.style.background = 'rgba(255,255,255,0.2)';
        card.classList.add('card-fullwidth');
    }
}

function switchDiaryMode(mode) {
    const viewMode = document.getElementById('diaryViewMode');
    const editMode = document.getElementById('diaryEditMode');
    const viewBtn = document.getElementById('diaryViewBtn');
    const editBtn = document.getElementById('diaryEditBtn');
    const card = document.getElementById('diaryCard');

    if (mode === 'view') {
        // 입력 모드에서 보기 모드로 전환 시 최신 데이터를 표시하기 위해 페이지 새로고침
        location.reload();
    } else {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        viewBtn.classList.remove('active');
        editBtn.classList.add('active');
        viewBtn.style.background = 'transparent';
        editBtn.style.background = 'rgba(255,255,255,0.2)';
        card.classList.add('card-fullwidth');
    }
}

// Iframe resize functions
function resizeTodayIframe() {
    const iframe = document.getElementById('today-iframe');
    if (iframe) {
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const height = iframeDoc.body.scrollHeight;
            iframe.style.height = (height + 50) + 'px';
        } catch (e) {
            console.log('Cannot access iframe content:', e);
            iframe.style.height = '800px';
        }
    }
}

function resizeWeeklyIframe() {
    const iframe = document.getElementById('weekly-iframe');
    if (iframe) {
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const height = iframeDoc.body.scrollHeight;
            iframe.style.height = (height + 50) + 'px';
        } catch (e) {
            console.log('Cannot access iframe content:', e);
            iframe.style.height = '800px';
        }
    }
}

function resizeDiaryIframe() {
    const iframe = document.getElementById('diary-iframe');
    if (iframe) {
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const height = iframeDoc.body.scrollHeight;
            iframe.style.height = (height + 50) + 'px';
        } catch (e) {
            console.log('Cannot access iframe content:', e);
            iframe.style.height = '800px';
        }
    }
}

// Inline editing functions
let currentlyEditing = null;

function editGoal(type) {
    // Prevent multiple editing sessions
    if(currentlyEditing && currentlyEditing !== type) {
        finishEditing(currentlyEditing);
    }
    
    currentlyEditing = type;
    const editor = document.getElementById(getEditorId(type));
    const currentText = editor.querySelector('.goal-text, .placeholder-text');
    const currentValue = currentText.classList.contains('placeholder-text') ? '' : currentText.textContent;
    
    // Create input element
    const input = document.createElement('input');
    input.type = 'text';
    input.value = currentValue;
    input.placeholder = getPlaceholder(type);
    input.className = 'goal-input-edit';
    
    // Replace text with input
    editor.classList.add('editing');
    currentText.style.display = 'none';
    editor.insertBefore(input, currentText);
    
    // Add editing state flag to prevent immediate saves
    let justFocused = true;
    let saveTimeout = null;
    let hasMadeChanges = false;
    
    // Track if user has made changes
    const originalValue = currentValue;
    
    // Focus and select text after small delay to prevent immediate blur
    setTimeout(() => {
        input.focus();
        input.select();
        justFocused = false; // Allow saves after brief delay
    }, 50);
    
    // Track changes to input
    input.addEventListener('input', () => {
        hasMadeChanges = input.value !== originalValue;
    });
    
    // Handle save on blur - only if user made changes and focus was stable
    input.addEventListener('blur', (e) => {
        // Don't save immediately after focus or if no changes made
        if (justFocused) {
            console.log('Ignoring blur - just focused');
            return;
        }
        
        if (saveTimeout) clearTimeout(saveTimeout);
        
        // Add delay to prevent accidental saves and check if still in DOM
        saveTimeout = setTimeout(() => {
            if (input.parentNode && !savingStates[type]) {
                console.log('Saving on blur after delay');
                saveGoalEdit(type);
            }
        }, 500); // Increased delay to 500ms
    });
    
    input.addEventListener('keydown', (e) => {
        if(e.key === 'Enter') {
            e.preventDefault();
            // Clear the blur timeout since we're saving via Enter
            if (saveTimeout) clearTimeout(saveTimeout);
            if (!savingStates[type]) {
                console.log('Saving on Enter key');
                saveGoalEdit(type);
            }
        } else if(e.key === 'Escape') {
            if (saveTimeout) clearTimeout(saveTimeout);
            console.log('Canceling edit on Escape');
            cancelEdit(type);
        }
    });
    
    // Cancel editing if clicking outside the editor area
    const handleOutsideClick = (e) => {
        if (!editor.contains(e.target) && input.parentNode) {
            if (saveTimeout) clearTimeout(saveTimeout);
            // Save if user made changes when clicking outside
            if (hasMadeChanges && !savingStates[type]) {
                console.log('Saving on outside click');
                saveGoalEdit(type);
            } else {
                console.log('Canceling edit - no changes or already saving');
                cancelEdit(type);
            }
            document.removeEventListener('click', handleOutsideClick);
        }
    };
    
    // Add outside click handler after small delay to prevent immediate trigger
    setTimeout(() => {
        document.addEventListener('click', handleOutsideClick);
    }, 100);
}

// 전역 저장 상태 관리
let savingStates = {};
let saveTimeouts = {};

// 디바운싱과 중복 방지를 위한 공통 함수
function debouncedSave(key, saveFunction, delay = 500) {
    // 이미 저장 중이면 무시
    if (savingStates[key]) {
        console.log(`Already saving ${key}, skipping duplicate request`);
        return;
    }
    
    // 기존 타이머가 있으면 취소
    if (saveTimeouts[key]) {
        clearTimeout(saveTimeouts[key]);
    }
    
    // 새로운 타이머 설정
    saveTimeouts[key] = setTimeout(() => {
        // 다시 한번 저장 중인지 확인
        if (!savingStates[key]) {
            saveFunction();
        }
        delete saveTimeouts[key];
    }, delay);
}

function saveGoalEdit(type) {
    const editor = document.getElementById(getEditorId(type));
    if (!editor) {
        console.log(`Editor not found for type: ${type}`);
        return;
    }
    
    const input = editor.querySelector('.goal-input-edit');
    if (!input) {
        console.log(`Input not found for type: ${type}`);
        return;
    }
    
    // 이미 이 타입에 대해 저장 중이면 중단 (더 강력한 체크)
    const saveKey = `save_${type}_${Date.now()}`;
    if (savingStates[type]) {
        console.log(`Already saving ${type}, skipping duplicate request`);
        return;
    }
    
    const newValue = input.value.trim();
    
    if(!newValue) {
        Swal.fire('알림', '목표를 입력해주세요.', 'warning');
        input.focus();
        return;
    }
    
    // 저장 상태 설정 (타임스탬프와 함께)
    savingStates[type] = {
        saving: true,
        timestamp: Date.now(),
        key: saveKey
    };
    
    // Show saving indicator
    const savingIndicator = document.getElementById(getSavingId(type));
    savingIndicator.classList.add('show');
    
    // Save to database
    const goalType = getGoalType(type);
    const deadline = goalType === '오늘목표' ?
        "<?php echo date('Y-m-d'); ?>" :
        "<?php echo date('Y-m-d', time() + 604800); ?>";

    // Calculate current week number (1-9) for weekly goals
    let weekNumber = 1; // Default to week 1
    if(goalType === '주간목표') {
        // TODO: Add UI to select week number (1-9)
        // For now, using week 1 as default
        weekNumber = 1;
    }

    const requestData = {
        "eventid": 2,
        "userid": <?php echo $studentid; ?>,
        "inputtext": newValue,
        "type": goalType,
        "weekNumber": weekNumber, // Added for weeklyplans (plan8-16)
        "level": 2,
        "deadline": deadline
    };
    
    console.log('Saving goal with data:', requestData);
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: requestData,
        success: function(data) {
            console.log('Save success:', data);

            // Check if server returned an error in success response
            if (data && data.error) {
                console.error('Server error in success response:', data.error);
                delete savingStates[type];

                Swal.fire({
                    title: '저장 오류',
                    text: data.error,
                    icon: 'error',
                    confirmButtonText: '확인'
                });
                return;
            }

            // Verify saved data from server response
            let displayText = newValue;
            if (data && data.saved_goal && data.saved_goal.saved_text) {
                displayText = data.saved_goal.saved_text;
                console.log('Using saved text from server:', displayText);
                console.log('Saved goal details:', data.saved_goal);
            } else {
                console.warn('No saved_goal data in response, using input value');
            }

            // 저장 상태 해제
            delete savingStates[type];

            // Update display with verified saved text
            finishEditing(type, displayText);

            // Hide saving indicator
            setTimeout(() => {
                savingIndicator.classList.remove('show');
            }, 1000);

            // Show success message
            Swal.fire({
                text: '목표가 저장되었습니다',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500
            });

            // Update header date if needed
            updateHeaderDate(type);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            console.error('Response text:', xhr.responseText);
            
            // 저장 상태 해제
            delete savingStates[type];
            
            // Hide saving indicator
            savingIndicator.classList.remove('show');
            
            let errorMessage = '저장 중 오류가 발생했습니다';
            
            // Try to parse server error message
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMessage = response.error;
                }
            } catch (e) {
                // If not JSON, use the status and error
                errorMessage += ': ' + status + ' - ' + error;
                if (xhr.responseText && xhr.responseText.length > 0 && xhr.responseText.length < 200) {
                    errorMessage += '\n서버 응답: ' + xhr.responseText;
                }
            }
            
            Swal.fire({
                title: '저장 오류',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: '확인'
            });
            
            input.focus();
        }
    });
}

function cancelEdit(type) {
    finishEditing(type);
}

function finishEditing(type, newValue = null) {
    const editor = document.getElementById(getEditorId(type));
    const input = editor.querySelector('.goal-input-edit');
    const textElement = editor.querySelector('.goal-text, .placeholder-text');
    
    if(input) {
        input.remove();
    }
    
    if(newValue) {
        // Update text content
        textElement.textContent = newValue;
        textElement.className = 'goal-text';
    }
    
    textElement.style.display = '';
    editor.classList.remove('editing');
    currentlyEditing = null;
}

function getEditorId(type) {
    const ids = {
        'today': 'todayGoalEditor',
        'todayScroll': 'todayScrollGoalEditor', 
        'weekly': 'weeklyGoalEditor',
        'weeklyScroll': 'weeklyScrollGoalEditor'
    };
    return ids[type];
}

function getSavingId(type) {
    const ids = {
        'today': 'todaySaving',
        'todayScroll': 'todayScrollSaving',
        'weekly': 'weeklySaving', 
        'weeklyScroll': 'weeklyScrollSaving'
    };
    return ids[type];
}

function getGoalType(type) {
    const types = {
        'today': '오늘목표',
        'todayScroll': '오늘목표',
        'weekly': '주간목표',
        'weeklyScroll': '주간목표'
    };
    return types[type];
}

function getPlaceholder(type) {
    const placeholders = {
        'today': '오늘의 목표를 입력하세요',
        'todayScroll': '오늘의 목표를 입력하세요',
        'weekly': '이번 주의 목표를 입력하세요',
        'weeklyScroll': '이번 주의 목표를 입력하세요'
    };
    return placeholders[type];
}

function updateHeaderDate(type) {
    // Update the header date to show that the goal was just set
    const now = new Date();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    const dateString = `${month}월 ${day}일`;
    
    if(type === 'today' || type === 'todayScroll') {
        // Update today goal header dates
        const headers = document.querySelectorAll('.section-header.primary span:last-child');
        headers.forEach(header => {
            if(header.textContent === '미설정') {
                header.textContent = dateString;
            }
        });
    } else if(type === 'weekly' || type === 'weeklyScroll') {
        // Update weekly goal header dates
        const headers = document.querySelectorAll('.section-header.secondary span:last-child');
        headers.forEach(header => {
            if(header.textContent === '미설정') {
                header.textContent = dateString;
            }
        });
    }
}

// Save Today Goal Function (legacy - keeping for compatibility)
function saveTodayGoal() {
    // 중복 저장 방지
    if (savingStates['todayGoalLegacy']) {
        console.log('Already saving today goal (legacy), skipping duplicate request');
        return;
    }
    
    const goalInput = document.getElementById('todayGoalInput').value;
    
    if(!goalInput) {
        Swal.fire('알림', '오늘의 목표를 입력해주세요.', 'warning');
        return;
    }
    
    savingStates['todayGoalLegacy'] = true;
    
    Swal.fire({
        text: '오늘 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "오늘목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d'); ?>"
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['todayGoalLegacy'];
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['todayGoalLegacy'];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}

// Save Weekly Goal Function
function saveWeeklyGoal() {
    // 중복 저장 방지
    if (savingStates['weeklyGoalLegacy']) {
        console.log('Already saving weekly goal (legacy), skipping duplicate request');
        return;
    }
    
    const goalInput = document.getElementById('weeklyGoalInput').value;
    
    if(!goalInput) {
        Swal.fire('알림', '주간 목표를 입력해주세요.', 'warning');
        return;
    }
    
    savingStates['weeklyGoalLegacy'] = true;
    
    Swal.fire({
        text: '주간 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "주간목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d', time() + 604800); ?>"
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['weeklyGoalLegacy'];
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['weeklyGoalLegacy'];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}

// Save Quarter Goal Function
function saveQuarterGoal(randomDream) {
    // 중복 저장 방지
    if (savingStates['quarterGoal1']) {
        console.log('Already saving quarter goal 1, skipping duplicate request');
        return;
    }
    
    const goalInput = document.getElementById('quarterGoalInput').value;
    const deadline = document.getElementById('quarterDeadline').value;
    
    if(!goalInput) {
        Swal.fire('알림', '분기 목표를 입력해주세요.', 'warning');
        return;
    }
    
    // 저장 상태 설정
    savingStates['quarterGoal1'] = true;
    savingStates[saveKey] = true;
    
    Swal.fire({
        text: '분기목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    // Save to mdl_abessi_progress table like roadmap.php does
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 8,  // Event ID for mission/progress
            "userid": <?php echo $studentid; ?>,
            "plantype": "분기목표",
            "deadline": deadline,
            "memo": goalInput,
            "dreamchallenge": randomDream
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['quarterGoal1'];
            delete savingStates[saveKey];
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['quarterGoal1'];
            delete savingStates[saveKey];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}


// Load user preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load preferred view
    const preferredView = localStorage.getItem('goalsPreferredView');
    if(preferredView === 'scroll') {
        toggleView();
    }
    
    // Load last active tab
    const activeTab = localStorage.getItem('goalsActiveTab');
    if(activeTab) {
        openTab(activeTab);
    }
    
    // Global keyboard event for editing
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape' && currentlyEditing) {
            cancelEdit(currentlyEditing);
        }
    });
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Add loading animation
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Enter key to save goals - 중복 등록 방지
    const todayInput = document.getElementById('todayGoalInput');
    if(todayInput && !todayInput.hasAttribute('data-listener-added')) {
        todayInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                if (!savingStates['todayGoalLegacy']) {
                    saveTodayGoal();
                }
            }
        });
        todayInput.setAttribute('data-listener-added', 'true');
    }
    
    const weeklyInput = document.getElementById('weeklyGoalInput');
    if(weeklyInput && !weeklyInput.hasAttribute('data-listener-added')) {
        weeklyInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                if (!savingStates['weeklyGoalLegacy']) {
                    saveWeeklyGoal();
                }
            }
        });
        weeklyInput.setAttribute('data-listener-added', 'true');
    }
    
    const quarterInput = document.getElementById('quarterGoalInput');
    if(quarterInput && !quarterInput.hasAttribute('data-listener-added')) {
        quarterInput.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                const btn = this.parentElement.parentElement.querySelector('.btn-submit');
                if (btn && !btn.disabled) {
                    btn.click();
                }
            }
        });
        quarterInput.setAttribute('data-listener-added', 'true');
    }
    
    // Enter key listeners for scroll view - 중복 등록 방지
    const todayInput2 = document.getElementById('todayGoalInput2');
    if(todayInput2 && !todayInput2.hasAttribute('data-listener-added')) {
        todayInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                if (!savingStates['todayGoalLegacy2']) {
                    saveTodayGoal2();
                }
            }
        });
        todayInput2.setAttribute('data-listener-added', 'true');
    }
    
    const weeklyInput2 = document.getElementById('weeklyGoalInput2');
    if(weeklyInput2 && !weeklyInput2.hasAttribute('data-listener-added')) {
        weeklyInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                if (!savingStates['weeklyGoalLegacy2']) {
                    saveWeeklyGoal2();
                }
            }
        });
        weeklyInput2.setAttribute('data-listener-added', 'true');
    }
    
    const quarterInput2 = document.getElementById('quarterGoalInput2');
    if(quarterInput2 && !quarterInput2.hasAttribute('data-listener-added')) {
        quarterInput2.addEventListener('keydown', function(event) {
            if(event.keyCode === 13) {
                event.preventDefault();
                const btn = this.parentElement.parentElement.querySelector('.btn-submit');
                if (btn && !btn.disabled) {
                    btn.click();
                }
            }
        });
        quarterInput2.setAttribute('data-listener-added', 'true');
    }
});

// Tab navigation with keyboard
document.addEventListener('keydown', function(e) {
    if(document.getElementById('tabView').classList.contains('active')) {
        const tabs = ['today', 'weekly', 'roadmap', 'quiz'];
        const currentTab = localStorage.getItem('goalsActiveTab') || 'today';
        const currentIndex = tabs.indexOf(currentTab);
        
        if(e.key === 'ArrowRight' && currentIndex < tabs.length - 1) {
            openTab(tabs[currentIndex + 1]);
        } else if(e.key === 'ArrowLeft' && currentIndex > 0) {
            openTab(tabs[currentIndex - 1]);
        }
    }
});

// Function from roadmap.php for saving quarterly goals
function inputgoalstep(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl) {
    const saveKey = `quarterGoal_${Date.now()}`;
    
    // 중복 저장 방지
    if (savingStates[saveKey] || savingStates['quarterGoal1']) {
        console.log('Already saving quarter goal, skipping duplicate request');
        return;
    }
    
    if(!inputtext) {
        Swal.fire('알림', '분기 목표를 입력해주세요.', 'warning');
        return;
    }
    
    // 첫 분기목표인지 확인
    const isFirstGoal = <?php echo ($savedDream ? 'false' : 'true'); ?>;
    
    if(isFirstGoal) {
        // 첫 분기목표 설정 시 특별한 환영 메시지
        const welcomeMessages = [
            "🎯 안녕! 당신의 특별한 랜덤 꿈을 전달합니다...",
            "✨ 짜잔! 운명이 선택한 당신의 꿈을 소개합니다...",
            "🌟 반가워요! 당신만을 위한 특별한 꿈이 도착했습니다...",
            "🎲 두구두구두구... 당신의 랜덤 꿈이 결정되었습니다!",
            "🚀 새로운 시작! 당신의 미래를 위한 꿈을 전달합니다..."
        ];
        
        const encourageMessages = [
            "앞으로 3개월 동안 이 소중한 꿈을 가꾸며 성장하시길 응원합니다! 💪",
            "다음 분기까지 이 꿈을 향해 한 걸음씩 나아가시길 바랍니다! 🌱",
            "이 꿈이 당신의 미래를 밝히는 등대가 되길 희망합니다! 🔦",
            "분기가 끝날 때쯤, 꿈에 한 발짝 더 가까워진 자신을 발견하실 거예요! 🎯",
            "이 꿈과 함께하는 여정이 즐겁고 의미있기를 바랍니다! ✨"
        ];
        
        const randomWelcome = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
        const randomEncourage = encourageMessages[Math.floor(Math.random() * encourageMessages.length)];
        
        Swal.fire({
            title: randomWelcome,
            html: `
                <div style="text-align: center; margin: 20px 0;">
                    <h2 style="color: #3383FF; margin: 20px 0; font-size: 28px;">
                        🌈 ${randomdream} 🌈
                    </h2>
                    <p style="color: #666; line-height: 1.6; margin-top: 20px;">
                        ${randomEncourage}
                    </p>
                    <p style="color: #999; font-size: 14px; margin-top: 15px;">
                        💡 언제든지 '바꾸기' 버튼으로 새로운 꿈을 선택할 수 있어요!
                    </p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: '네, 이 꿈으로 시작하겠습니다!',
            confirmButtonColor: '#3383FF',
            showCancelButton: true,
            cancelButtonText: '다른 꿈으로 바꿀래요',
            cancelButtonColor: '#E05D22'
        }).then((result) => {
            if (result.value) {
                // 현재 꿈으로 시작
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(randomdreamurl, "_blank");
                }, 100);
            } else {
                // 다른 꿈 선택
                changeRandomDream();
                Swal.fire({
                    title: '새로운 꿈을 선택해주세요!',
                    text: '바뀐 꿈을 확인하고 다시 저장 버튼을 눌러주세요.',
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    } else {
        // 기존 사용자의 경우 기존 로직 유지
        Swal.fire({
            title: '현재의 꿈을 유지하시겠습니까?',
            text: "현재 꿈 : " + document.getElementById('randomDreamText').innerText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '예',
            cancelButtonText: '아니요',
            confirmButtonClass: 'btn btn-success',
            cancelButtonClass: 'btn btn-danger'
        }).then((result) => {
            if (result.value) {
                // 현재 꿈 유지
                randomdream = "stay";
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(currentDreamUrl, "_blank");
                }, 100);
            } else {
                // 새로운 꿈으로 변경
                saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl);
                
                // 새로운 꿈의 자세히 보기 링크 열기
                setTimeout(function() {
                    window.open(randomdreamurl, "_blank");
                }, 100);
            }
        });
    }
}

function saveQuarterGoal(eventid, userid, plantype, deadline, inputtext, randomdream, randomdreamurl) {
    // 중복 저장 방지
    if (savingStates['quarterGoal2']) {
        console.log('Already saving quarter goal 2, skipping duplicate request');
        return;
    }
    
    savingStates['quarterGoal2'] = true;
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": eventid,
            "userid": userid,
            "plantype": plantype,
            "deadline": deadline,
            "inputtext": inputtext,
            "randomdream": randomdream,
            "randomdreamurl": randomdreamurl
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['quarterGoal2'];
            
            Swal.fire({
                text: '분기목표가 설정되었습니다',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500
            });
            
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['quarterGoal2'];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}

// 랜덤 꿈 챌린지 데이터
const dreamData = [
    {name: "인공지능 개발자", url: "https://gamma.app/docs/-5dvdwrou2385tda"},
    {name: "환경 보호 전문가", url: "https://gamma.app/docs/-57oe1106fexvovx"},
    {name: "가상현실 게임 디자이너", url: "https://gamma.app/docs/-w060d7y8nzrq6z1"},
    {name: "우주 탐사자", url: "https://gamma.app/docs/-xl03qnlzbhw0l3d"},
    {name: "유전공학 연구원", url: "https://gamma.app/docs/Untitled-ekp8hywee87lsw8"},
    {name: "스마트팜 기술자", url: "https://gamma.app/docs/-ggn6grxhpvp0tdj"},
    {name: "해양 생물학자", url: "https://gamma.app/docs/-xieocbvr1u6hyd0"},
    {name: "신재생 에너지 엔지니어", url: "https://gamma.app/docs/-lp6kn8pqg1aqmec"},
    {name: "드론 파일럿", url: "https://gamma.app/docs/-fsuhnwucw8546bj"},
    {name: "사이버 보안 전문가", url: "https://gamma.app/docs/-t55yu127yjsi9fo"},
    {name: "데이터 과학자", url: "https://gamma.app/docs/-8sln8zzhe487myk"},
    {name: "로봇공학 기술자", url: "https://gamma.app/docs/-62mq1zcgmekj0xw"},
    {name: "콘텐츠 크리에이터", url: "https://gamma.app/docs/-80707aa8tnf1d8u"},
    {name: "의료 기술 혁신가", url: "https://gamma.app/docs/-kep6ua7le4tcsup"},
    {name: "지속 가능한 패션 디자이너", url: "https://gamma.app/docs/-xhdx8mkbak325bj"}
];

// 현재 꿈의 URL 저장
let currentDreamUrl = '<?php echo $randomDreamUrl; ?>';

// Toggle quarter goal form
function toggleQuarterGoalForm() {
    const form = document.getElementById('quarterGoalForm');
    const button = document.getElementById('quarterGoalAddButton');

    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        button.style.display = 'none';
        // 폼이 열릴 때 입력 필드에 포커스
        setTimeout(() => {
            const input = document.getElementById('quarterGoalInput');
            if (input) input.focus();
        }, 100);
    } else {
        form.style.display = 'none';
        button.style.display = 'block';
    }
}

// Toggle today goal form
function toggleTodayGoalForm() {
    const form = document.getElementById('todayGoalForm');
    const button = document.getElementById('todayGoalAddButton');

    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        button.style.display = 'none';
    } else {
        form.style.display = 'none';
        button.style.display = 'block';
    }
}

// Toggle weekly goal form
function toggleWeeklyGoalForm() {
    const form = document.getElementById('weeklyGoalForm');
    const button = document.getElementById('weeklyGoalAddButton');

    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        button.style.display = 'none';
    } else {
        form.style.display = 'none';
        button.style.display = 'block';
    }
}

// Function to change random dream
function changeRandomDream() {
    const currentDream = document.getElementById('randomDreamText').innerText;
    let newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    
    // 현재와 다른 꿈이 나올 때까지 선택
    while(newDreamData.name === currentDream) {
        newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    }
    
    // 먼저 UI 업데이트
    document.getElementById('randomDreamText').innerText = newDreamData.name;
    currentDreamUrl = newDreamData.url;
    
    // 자세히 보기 링크 업데이트
    const detailLink = document.getElementById('dreamDetailLink');
    if(detailLink) {
        detailLink.href = newDreamData.url;
    }
    
    // Add a little animation effect
    const dreamElement = document.getElementById('randomDreamText');
    dreamElement.style.opacity = '0';
    setTimeout(() => {
        dreamElement.style.transition = 'opacity 0.3s ease';
        dreamElement.style.opacity = '1';
    }, 100);
    
    // 데이터베이스 업데이트 (백그라운드에서 처리)
    $.ajax({
        url: "database.php",
        type: "POST",
        data: {
            "eventid": 301, // 랜덤 꿈 챌린지 업데이트용 이벤트 ID
            "userid": <?php echo $studentid; ?>,
            "randomdream": newDreamData.name,
            "randomdreamurl": newDreamData.url
        },
        success: function(response) {
            console.log("Dream challenge updated successfully");
        },
        error: function(xhr, status, error) {
            console.error("Failed to update dream challenge:", error);
        }
    });
    
    Swal.fire({
        icon: 'success',
        title: '랜덤 꿈이 변경되었습니다!',
        text: newDreamData.name,
        timer: 1500,
        showConfirmButton: false
    });
}

// Function to change random dream for scroll view
function changeRandomDream2() {
    const currentDream = document.getElementById('randomDreamText2').innerText;
    let newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    
    // 현재와 다른 꿈이 나올 때까지 선택
    while(newDreamData.name === currentDream) {
        newDreamData = dreamData[Math.floor(Math.random() * dreamData.length)];
    }
    
    // 먼저 UI 업데이트
    document.getElementById('randomDreamText2').innerText = newDreamData.name;
    currentDreamUrl = newDreamData.url;
    
    // 자세히 보기 링크 업데이트
    const detailLink = document.getElementById('dreamDetailLink2');
    if(detailLink) {
        detailLink.href = newDreamData.url;
    }
    
    // Add a little animation effect
    const dreamElement = document.getElementById('randomDreamText2');
    dreamElement.style.opacity = '0';
    setTimeout(() => {
        dreamElement.style.transition = 'opacity 0.3s ease';
        dreamElement.style.opacity = '1';
    }, 100);
    
    // 데이터베이스 업데이트 (백그라운드에서 처리)
    $.ajax({
        url: "database.php",
        type: "POST",
        data: {
            "eventid": 301, // 랜덤 꿈 챌린지 업데이트용 이벤트 ID
            "userid": <?php echo $studentid; ?>,
            "randomdream": newDreamData.name,
            "randomdreamurl": newDreamData.url
        },
        success: function(response) {
            console.log("Dream challenge updated successfully");
        },
        error: function(xhr, status, error) {
            console.error("Failed to update dream challenge:", error);
        }
    });
    
    Swal.fire({
        icon: 'success',
        title: '랜덤 꿈이 변경되었습니다!',
        text: newDreamData.name,
        timer: 1500,
        showConfirmButton: false
    });
}

// Functions for scroll view goal saving
function saveTodayGoal2() {
    // 중복 저장 방지
    if (savingStates['todayGoal2']) {
        console.log('Already saving today goal 2, skipping duplicate request');
        return;
    }
    
    const goalInput = document.getElementById('todayGoalInput2').value;
    
    if(!goalInput) {
        Swal.fire('알림', '오늘의 목표를 입력해주세요.', 'warning');
        return;
    }
    
    savingStates['todayGoal2'] = true;
    
    Swal.fire({
        text: '오늘 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "오늘목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d'); ?>"
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['todayGoal2'];
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['todayGoal2'];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}

function saveWeeklyGoal2() {
    // 중복 저장 방지
    if (savingStates['weeklyGoal2']) {
        console.log('Already saving weekly goal 2, skipping duplicate request');
        return;
    }
    
    const goalInput = document.getElementById('weeklyGoalInput2').value;
    
    if(!goalInput) {
        Swal.fire('알림', '주간 목표를 입력해주세요.', 'warning');
        return;
    }
    
    savingStates['weeklyGoal2'] = true;
    
    Swal.fire({
        text: '주간 목표가 설정되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 2,
            "userid": <?php echo $studentid; ?>,
            "inputtext": goalInput,
            "type": "주간목표",
            "level": 2,
            "deadline": "<?php echo date('Y-m-d', time() + 604800); ?>"
        },
        success: function(data) {
            // 저장 상태 해제
            delete savingStates['weeklyGoal2'];
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Save error:', xhr, status, error);
            delete savingStates['weeklyGoal2'];
            
            Swal.fire({
                title: '저장 오류',
                text: '저장 중 오류가 발생했습니다.',
                icon: 'error',
                confirmButtonText: '확인'
            });
        }
    });
}

// 만족도 체크박스 클릭 이벤트 (수학일기 보기 모드)
$(document).on('click', '.status-checkbox-diary', function() {
    var $checkbox = $(this);
    var week = $checkbox.data('week');
    var statusField = 'status' + String(week).padStart(2, '0');

    console.log("수학일기 만족도 체크박스 클릭 - week:", week);

    Swal.fire({
        title: '만족도를 선택하세요',
        html: `
            <div style="display: flex; gap: 10px; justify-content: center; margin: 20px 0;">
                <button id="verySatisfiedBtn" class="swal2-styled" style="background-color: #2196F3; flex: 1; max-width: 120px;">매우만족</button>
                <button id="satisfiedBtn" class="swal2-styled" style="background-color: #4CAF50; flex: 1; max-width: 120px;">만족</button>
                <button id="dissatisfiedBtn" class="swal2-styled" style="background-color: #FF9800; flex: 1; max-width: 120px;">불만족</button>
            </div>
            <div style="margin-top: 10px;">
                <button id="cancelBtn" class="swal2-styled" style="background-color: #757575;">취소</button>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: false,
        showCloseButton: true
    });

    // 버튼 클릭 이벤트 핸들러
    function handleStatusSelection(selectedValue) {
        console.log("선택된 만족도:", selectedValue);

        // 만족도에 따른 배경색 설정
        var statusBgColor = '#e3f2fd'; // 기본값
        var statusTextColor = '#fff'; // 흰색 텍스트
        if (selectedValue === '매우만족') {
            statusBgColor = '#2196F3'; // 파란색
        } else if (selectedValue === '만족') {
            statusBgColor = '#4CAF50'; // 녹색
        } else if (selectedValue === '불만족') {
            statusBgColor = '#FF9800'; // 주황색
        }

        // AJAX로 저장
        var formData = new FormData();
        formData.append('studentid', <?php echo $studentid; ?>);
        formData.append(statusField, selectedValue);

        // 현재 시점의 unixtime을 tend 필드로 추가
        var tendField = 'tend' + String(week).padStart(2, '0');
        var currentUnixtime = Math.floor(Date.now() / 1000);
        formData.append(tendField, currentUnixtime);
        console.log("완료 시점 기록 - " + tendField + ": " + currentUnixtime + " (" + new Date().toLocaleString('ko-KR') + ")");

        $.ajax({
            url: 'save_todayplan.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log("만족도 저장 성공:", response);

                // 체크박스를 텍스트로 변경 (배경색 적용)
                $checkbox.replaceWith(
                    '<span class="status-text-diary" style="padding: 4px 8px; background: ' + statusBgColor + '; border-radius: 4px; font-size: 14px; color: ' + statusTextColor + ';">' +
                    selectedValue +
                    '</span>'
                );

                Swal.close();
                Swal.fire({
                    text: '만족도가 저장되었습니다.',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1500
                });
            },
            error: function(xhr, status, error) {
                console.error("만족도 저장 실패:", error);
                Swal.close();
                Swal.fire({
                    title: '오류',
                    text: '만족도 저장에 실패했습니다.',
                    icon: 'error'
                });
            }
        });
    }

    // 각 버튼 클릭 이벤트
    $(document).on('click', '#verySatisfiedBtn', function() {
        handleStatusSelection('매우만족');
    });

    $(document).on('click', '#satisfiedBtn', function() {
        handleStatusSelection('만족');
    });

    $(document).on('click', '#dissatisfiedBtn', function() {
        handleStatusSelection('불만족');
    });

    $(document).on('click', '#cancelBtn', function() {
        Swal.close();
    });
});

// 피드백 버튼 클릭 이벤트 (수학일기 보기 모드)
$(document).on('click', '.feedback-btn-view', function() {
    var $btn = $(this);
    var week = $btn.data('week');
    var recordId = $btn.data('record-id');
    var studentId = $btn.data('student-id');
    var currentFback = $btn.data('current-fback') || '';
    var fbackField = 'fback' + week;

    console.log("피드백 버튼 클릭 - week:", week, "recordId:", recordId, "현재 피드백:", currentFback);

    Swal.fire({
        title: '피드백 입력',
        html: `
            <div style="margin-bottom: 10px; text-align: left; color: #666;">
                학습 항목 #${week}에 대한 피드백을 입력하세요
            </div>
            <textarea id="feedbackInput"
                      style="width: 100%; min-height: 120px; padding: 10px;
                             border: 1px solid #ddd; border-radius: 4px;
                             font-size: 14px; resize: vertical;"
                      placeholder="피드백 내용을 입력하세요...">${currentFback}</textarea>
        `,
        showCancelButton: true,
        confirmButtonText: '저장',
        cancelButtonText: '취소',
        confirmButtonColor: '#3383ff',
        cancelButtonColor: '#999',
        preConfirm: () => {
            const feedback = document.getElementById('feedbackInput').value;
            return feedback;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            var feedbackText = result.value || '';

            console.log("저장할 피드백:", feedbackText);

            // AJAX로 부분 업데이트 (fback 필드만)
            var formData = new FormData();
            formData.append('studentid', studentId);
            formData.append(fbackField, feedbackText);

            $.ajax({
                url: 'save_todayplan.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log("피드백 저장 성공:", response);

                    // UI 업데이트 (페이지 새로고침 없이)
                    $btn.data('current-fback', feedbackText);

                    var $cell = $btn.closest('.feedback-cell');
                    var $preview = $cell.find('.feedback-preview');

                    if (feedbackText) {
                        var shortText = feedbackText.length > 12 ? feedbackText.substring(0, 12) + '...' : feedbackText;

                        if ($preview.length > 0) {
                            // 기존 미리보기 업데이트
                            $preview.text(shortText).attr('title', feedbackText);
                        } else {
                            // 새로운 미리보기 생성
                            $btn.after('<div class="feedback-preview" style="margin-top: 5px; font-size: 12px; color: #666; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help;" title="' + feedbackText.replace(/"/g, '&quot;') + '">' + shortText + '</div>');
                        }
                        $btn.attr('title', '피드백 수정');
                    } else {
                        // 피드백이 비어있으면 미리보기 제거
                        $preview.remove();
                        $btn.attr('title', '피드백 입력');
                    }

                    Swal.fire({
                        text: '피드백이 저장되었습니다.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                },
                error: function(xhr, status, error) {
                    console.error("피드백 저장 실패:", error);
                    console.error("응답:", xhr.responseText);
                    Swal.fire({
                        title: '오류',
                        text: '피드백 저장에 실패했습니다.',
                        icon: 'error'
                    });
                }
            });
        }
    });
});
</script>

<?php
$pagetype = 'goals';
include("../LLM/postit.php");
?>
</body>
</html>