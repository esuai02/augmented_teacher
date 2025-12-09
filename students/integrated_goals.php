<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$studentid = required_param('id', PARAM_INT);
$timecreated = time();

$cid = $_GET["cid"] ?? null; 
$nch = $_GET["nch"] ?? null; 
$pid = $_GET["pid"] ?? null; 

$wgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
$goal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1");
$chapterlog = $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1");

if($cid == NULL) $cid = $chapterlog->cid;
if($nch == NULL) $nch = $chapterlog->nch;  
if($pid == NULL) $pid = $wgoal->id ?? 1;

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", array($USER->id));
$role = isset($userrole->role) ? $userrole->role : '';

// 학생 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
$firstname = isset($username->firstname) ? $username->firstname : '';
$lastname = isset($username->lastname) ? $username->lastname : '';
$studentname = htmlspecialchars($firstname, ENT_QUOTES) . ' ' . htmlspecialchars($lastname, ENT_QUOTES);

// 분기 목표 가져오기
$termplan2 = $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype='분기목표' AND hide=0 AND deadline > '$timecreated' ORDER BY id DESC LIMIT 1");
$termplan = $DB->get_record_sql("SELECT id, deadline, memo, dreamchallenge, dreamtext, dreamurl FROM mdl_abessi_progress WHERE userid='$studentid' AND plantype='분기목표' AND hide=0 AND deadline > '$timecreated' ORDER BY id DESC LIMIT 1");

// 최근 12개월간의 모든 분기목표 가져오기
$twelveMonthsAgo = $timecreated - (365 * 24 * 60 * 60); // 12개월 전 타임스탬프
$allTermPlans = $DB->get_records_sql("SELECT id, deadline, memo, dreamchallenge, dreamurl, timecreated FROM mdl_abessi_progress WHERE userid=? AND plantype='분기목표' AND hide=0 AND timecreated > ? ORDER BY deadline DESC", array($studentid, $twelveMonthsAgo));

if ($termplan) {
    $dreamdday = round(($termplan->deadline - $timecreated) / 86400 + 1, 0);
    $EGinputtime = date("m/d", $termplan->deadline);
    $termMission = htmlspecialchars($termplan->memo, ENT_QUOTES);
} else {
    $dreamdday = 0;
    $EGinputtime = '';
    $termMission = '분기목표를 설정해주세요';
}

// 8주차 주간 목표 가져오기 (분기 시작일 기준)
$weeklyGoals = array();
$currentWeek = 1;

if ($termplan) {
    // 분기 시작일 계산 (분기목표 생성일 기준, 월요일 기준)
    $termStartTime = $DB->get_field_sql("SELECT timecreated FROM mdl_abessi_progress WHERE id=?", array($termplan->id));
    
    // 분기 시작일을 월요일로 조정
    $termStartDate = date('Y-m-d', $termStartTime);
    $termStartDayOfWeek = date('N', $termStartTime); // 1=월요일, 7=일요일
    $daysToMonday = ($termStartDayOfWeek - 1); // 월요일까지의 일수
    $mondayStartTime = $termStartTime - ($daysToMonday * 24 * 60 * 60);
    
    // 현재 주차 계산 (월요일 기준)
    $weeksSinceStart = floor(($timecreated - $mondayStartTime) / (7 * 24 * 60 * 60)) + 1;
    $currentWeek = min(max($weeksSinceStart, 1), 8);
    
    // 8주간의 주간목표 가져오기 (기존 구조 활용)
    for ($week = 1; $week <= 8; $week++) {
        // text 필드에서 주차 정보가 포함된 목표 찾기
        $weekGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid=? AND type LIKE '주간목표' AND text LIKE ? ORDER BY id DESC LIMIT 1", 
            array($studentid, $week . '주차:%'));
        
        if ($weekGoal) {
            // 주차 정보를 제거한 실제 목표 텍스트 추출
            $goalText = preg_replace('/^\d+주차:\s*/', '', $weekGoal->text);
            $weeklyGoals[$week] = $goalText;
        } else {
            $weeklyGoals[$week] = '';
        }
    }
    
    // 현재 주차 목표가 없는 경우 일반 주간목표 확인
    if (empty($weeklyGoals[$currentWeek])) {
        $generalWeekGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid=? AND type LIKE '주간목표' AND text NOT LIKE '%주차:%' ORDER BY id DESC LIMIT 1", 
            array($studentid));
        if ($generalWeekGoal) {
            $weeklyGoals[$currentWeek] = $generalWeekGoal->text;
        }
    }
} else {
    $termStartTime = $timecreated; // 기본값 설정
    $mondayStartTime = $timecreated; // 기본값 설정
    for ($week = 1; $week <= 8; $week++) {
        $weeklyGoals[$week] = '';
    }
}

// 주간 목표 가져오기 (기존 코드 수정)
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid=? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1", array($studentid));
$weeklyGoalText = $weeklyGoals[$currentWeek] ?? (isset($weeklyGoal->text) ? htmlspecialchars($weeklyGoal->text, ENT_QUOTES) : '');

// 주간 계획 가져오기
$weeklyPlanInfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid=? AND progressid=? ORDER BY id DESC LIMIT 1", array($studentid, $termplan2->id ?? 1));

$weeklyPlans = array();
$weeklyDates = array();

for ($i = 1; $i <= 7; $i++) {
    $planField = 'plan' . $i;
    $dateField = 'date' . $i;
    $weeklyPlans[] = isset($weeklyPlanInfo->$planField) ? $weeklyPlanInfo->$planField : '';
    $weeklyDates[] = isset($weeklyPlanInfo->$dateField) ? $weeklyPlanInfo->$dateField : date('Y-m-d', strtotime('+' . ($i-1) . ' days'));
}

// 오늘 포모도르 계획 가져오기
$todayPlanInfo = $DB->get_record_sql("SELECT * FROM mdl_abessi_todayplans WHERE userid=? AND progressid=? ORDER BY id DESC LIMIT 1", array($studentid, $goal->id ?? 1));

$todayPlans = array();
$todayTimes = array();
$todayUrls = array();
$todayStatuses = array(); // 만족도 상태

// 현재 시간 기준으로 30분 간격 시간 초기값 설정
$currentTime = time();
$currentHour = date('H', $currentTime);
$currentMinute = date('i', $currentTime);

// 현재 시간을 30분 단위로 올림
$nextSlot = $currentMinute < 30 ? 30 : 60;
$startTime = mktime($currentHour, $nextSlot, 0);
if ($nextSlot == 60) {
    $startTime = mktime($currentHour + 1, 0, 0);
}

for ($i = 1; $i <= 10; $i++) {
    $planField = 'plan' . $i;
    $timeField = 'due' . $i;
    $urlField = 'url' . $i;
    $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT); // status01, status02, ...

    $todayPlans[] = isset($todayPlanInfo->$planField) ? $todayPlanInfo->$planField : '';

    // 기존 시간이 있으면 사용, 없으면 30분 간격으로 설정
    if (isset($todayPlanInfo->$timeField) && !empty($todayPlanInfo->$timeField)) {
        $timeValue = date('H:i', $todayPlanInfo->$timeField);
    } else {
        $timeValue = date('H:i', $startTime + (($i-1) * 30 * 60));
    }
    $todayTimes[] = $timeValue;
    $todayUrls[] = isset($todayPlanInfo->$urlField) ? $todayPlanInfo->$urlField : '';
    $todayStatuses[] = isset($todayPlanInfo->$statusField) ? $todayPlanInfo->$statusField : ''; // 만족도 로드
}

// 포모도르 계획에 입력된 값이 있는지 확인
$hasPomodoroPlans = false;
foreach ($todayPlans as $plan) {
    if (!empty(trim($plan))) {
        $hasPomodoroPlans = true;
        break;
    }
}

// 시간표 정보 가져오기
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid=? AND pinned=1 ORDER BY id DESC LIMIT 1", array($studentid));

$scheduleData = array();
$dayNames = array('월', '화', '수', '목', '금', '토', '일');
$activeDays = array();

if ($schedule) {
    for ($i = 1; $i <= 7; $i++) {
        $startField = 'start' . $i;
        $durationField = 'duration' . $i;
        $roomField = 'room' . $i;
        
        $startTime = isset($schedule->$startField) ? $schedule->$startField : '';
        $duration = isset($schedule->$durationField) ? $schedule->$durationField : 0;
        $room = isset($schedule->$roomField) ? $schedule->$roomField : '';
        
        // 12:00 AM은 NULL로 처리
        if ($startTime === '12:00 AM') $startTime = '';
        
        $scheduleData[$i] = array(
            'day' => $dayNames[$i-1],
            'start_time' => $startTime,
            'duration' => $duration,
            'room' => $room,
            'has_class' => ($duration > 0)
        );
        
        if ($duration > 0) {
            $activeDays[] = $i;
        }
    }
}

// 챕터 목록 생성
$chapterlist = '';
if ($cid) {
    $curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_curriculum WHERE id=?", array($cid));
    
    if ($curri && $nch) {
        $cntstr = 'cnt' . $nch;
        $chname = 'ch' . $nch;
        $thischtitle = $curri->$chname;
        $checklistid = $curri->$cntstr;

        if ($checklistid) {
            $chklist = $DB->get_record_sql("SELECT instance FROM mdl_course_modules WHERE id=? ORDER BY id DESC LIMIT 1", array($checklistid));
            if ($chklist) {
                $topics = $DB->get_records_sql("SELECT * FROM mdl_checklist_item WHERE checklist=? ORDER BY position ASC", array($chklist->instance));
                
                $phrases = array(
                    '개념도약' => '🟢',
                    '유형정복' => '🟦',
                    '단원 마무리' => '☑️',
                    '대표유형' => '✳️',
                    '심화수업' => '🏆',
                );

                $chapter_num = 1;
                foreach ($topics as $topic) {
                    $displaytext = $topic->displaytext;
                    $linkurl = $topic->linkurl;

                    $include_topic = false;
                    $icon = '';
                    foreach ($phrases as $phrase => $icon_symbol) {
                        if (strpos($displaytext, $phrase) !== false) {
                            $include_topic = true;
                            $icon = $icon_symbol;
                            break;
                        }
                    }
                    if (!$include_topic) continue;

                    $copyButton = '<span class="copy-button" data-clipboard-text="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '">' . $icon . ' ' . $displaytext . '</span>';
                    $insertButton = '<button class="insert-button" data-title="' . htmlspecialchars($displaytext, ENT_QUOTES, 'UTF-8') . '" data-linkurl="' . htmlspecialchars($linkurl, ENT_QUOTES, 'UTF-8') . '">➕</button>';
                    $linkIcon = '<a href="' . $linkurl . '" target="_blank">🔗</a>';
                    $chapterlist .= '<div class="chapter-item">' . $insertButton . ' ' . $copyButton . ' ' . $linkIcon . '</div>';
                    $chapter_num++;
                }
            }
        }
    }
}

// 오늘 요일 계산 (1=월요일, 7=일요일)
$todayDayOfWeek = date('N');
$dayNamesKorean = ['월', '화', '수', '목', '금', '토', '일'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>통합 목표 관리</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/i18n/datepicker-ko.js"></script>
    
    <style>
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 16px 24px;
            font-weight: 600;
            font-size: 18px;
            color: white;
        }
        
        .section-content {
            padding: 24px;
            overflow: hidden;
            transition: all 0.3s ease-in-out;
            transform-origin: top;
        }
        
        .section-content.collapsed {
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            opacity: 0;
        }
        
        .section-content.expanded {
            opacity: 1;
        }
        
        .section-toggle {
            transition: transform 0.2s ease-in-out;
        }
        
        .section-toggle.rotated {
            transform: rotate(90deg);
        }
        
        .term-goal { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .weekly-goal { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .daily-goal { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .pomodoro-goal { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .chapter-list { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .goal-item {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            align-items: center;
        }
        
        .goal-item input[type="date"] {
            flex: 0 0 140px;
        }
        
        .goal-item input[type="time"] {
            flex: 0 0 100px;
        }
        
        .goal-item input[type="text"] {
            flex: 1;
        }
        
        .chapter-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .chapter-item:last-child {
            border-bottom: none;
        }
        
        .copy-button, .insert-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
            font-size: 12px;
        }
        
        .copy-button:hover, .insert-button:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .schedule-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        
        .schedule-day {
            font-weight: 600;
            color: #007bff;
        }
        
        .schedule-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .container-custom {
                padding: 12px;
            }
            
            .section-content {
                padding: 16px;
            }
            
            .goal-item {
                flex-direction: column;
                align-items: stretch;
            }
            
            .goal-item input {
                flex: none !important;
            }
        }

        /* 포모도르 타임라인 스타일 */
        .pomodoro-timeline-container {
            user-select: none;
        }

        .timeline-mark {
            position: absolute;
            right: 0;
            width: 100%;
            height: 1px;
            background: #1976d2;
            font-size: 12px;
            color: #1976d2;
            padding-right: 8px;
            text-align: right;
            line-height: 1;
            font-weight: 500;
        }
        
        .timeline-mark::after {
            content: attr(data-time);
            position: absolute;
            right: 8px;
            top: -8px;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            backdrop-filter: blur(4px);
        }

        .timeline-mark.major {
            background: #0d47a1;
            height: 2px;
            font-weight: bold;
        }
        
        .timeline-mark.major::after {
            font-weight: bold;
            font-size: 12px;
            background: rgba(255, 255, 255, 0.95);
            padding: 3px 8px;
            top: -10px;
        }

        .timeline-mark.minor {
            background: #64b5f6;
            opacity: 0.6;
        }
        
        .timeline-mark.minor::after {
            font-size: 10px;
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.8);
            padding: 1px 4px;
            top: -7px;
        }

        .activity-item {
            position: absolute;
            left: 8px;
            right: 8px;
            background: white;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: move;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .activity-item:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            transform: translateX(4px);
        }

        .activity-item.dragging {
            z-index: 1000;
            transform: rotate(2deg);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .activity-item.over-average {
            background: #ffebee;
            border-color: #f44336;
        }

        .activity-item.under-average {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        .activity-content {
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .activity-title {
            font-weight: 500;
            color: #333;
            font-size: 13px;
            line-height: 1.2;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .activity-duration {
            display: none;
        }

        .activity-controls {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .activity-time-badge {
            background: #1976d2;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            min-width: 45px;
            text-align: center;
        }

        .activity-complete {
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 11px;
            cursor: pointer;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-complete:hover {
            background: #45a049;
        }

        .activity-delete {
            background: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 11px;
            cursor: pointer;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-delete:hover {
            background: #d32f2f;
        }

        .timeline-drop-zone {
            position: absolute;
            left: 0;
            right: 0;
            height: 4px;
            background: #4caf50;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .timeline-drop-zone.active {
            opacity
        }
         
        .activity-item.completed .activity-time-badge {
            background: #81c784 !important;
            color: white;
        }
        
        /* 사이드 패널 스타일 */
        .side-panel {
            position: fixed;
            top: 0;
            right: -33.33vw;
            width: 33.33vw;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: right 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            overflow-y: auto;
            border-left: 3px solid #fa709a;
        }

        .side-panel.open {
            right: 0;
        }

        .side-panel-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .side-panel-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .side-panel-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .side-panel-content {
            padding: 20px;
        }

        .side-panel-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }

        .side-panel-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .side-panel {
                width: 100vw;
                right: -100vw;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container-custom">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo $studentname; ?>님의 학습 계획</h1>
            <p class="text-gray-600">체계적인 목표 설정으로 성공적인 학습을 이어가세요</p>
        </div>

        <!-- 분기 목표 -->
        <div class="section-card" id="termGoalCard">
            <div class="section-header term-goal" style="cursor: default;">
                <div class="flex items-center justify-between">
                    <!-- 좌측 아이콘 & 토글 버튼 -->
                    <div class="flex items-center gap-3" onclick="toggleSection('termGoal')" style="cursor: pointer;">
                        <span>🎯 분기 목표</span>
                        <span id="termGoalToggle" class="text-white opacity-75 section-toggle">▼</span>
                    </div>

                    <!-- 중앙 분기 목표 텍스트 & D-day -->
                    <div class="flex items-center gap-2 mx-auto">
                    <button onclick="openTermGoalModal(); event.stopPropagation();" class="text-white opacity-75 hover:opacity-100 transition-opacity p-1 rounded hover:bg-white hover:bg-opacity-20" title="목표 수정">📝</button><span class="font-semibold text-white text-sm md:text-base truncate max-w-xs md:max-w-md text-center" style="max-width: 60vw;"><?php echo $termMission; ?></span>
                        <span class="text-sm text-white opacity-90">D-<?php echo $dreamdday; ?></span>  
                    </div>

                    <!-- 우측 랜덤꿈 & 목록 버튼 -->
                    <div class="flex items-center gap-3">
                        <?php if (!empty($termplan->dreamchallenge)): ?>
                            <button onclick="openDreamViewer('<?php echo htmlspecialchars($termplan->dreamurl ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($termplan->dreamchallenge, ENT_QUOTES); ?>'); event.stopPropagation();" class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full hover:bg-opacity-30 transition-all cursor-pointer" title="꿈의 세계 보기">
                                🌟 <?php echo htmlspecialchars($termplan->dreamchallenge, ENT_QUOTES); ?>
                            </button>
                        <?php endif; ?>
                        <button onclick="openGoalHistory(); event.stopPropagation();" class="text-xs text-white opacity-75 hover:opacity-100 bg-white bg-opacity-20 px-2 py-1 rounded-full transition-all" title="전체 목록 보기">📋 목록</button>
                    </div>
                </div>
            </div>
            <div class="section-content" id="termGoalContent" style="display: block;">
                <!-- 분기 목표 텍스트는 헤더로 이동했으므로 초기 설명 블록 제거 -->

                <!-- 주간 목표 섹션 -->
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-700">📅 주간 목표 (<?php echo $currentWeek; ?>주차)</h4>
                        <div class="flex gap-2">
                            <?php if (empty($weeklyGoalText)): ?>
                                <button onclick="addWeeklyGoalPlan()" class="btn-primary text-sm">+ 주간목표 추가</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 현재 주차 목표 -->
                    <div id="currentWeekGoal" class="mb-4">
                        <?php if (!empty($weeklyGoalText)): ?>
                            <div class="bg-blue-50 p-3 rounded-lg mb-2">
                                <div class="font-medium text-blue-800">이번 주 목표 (<?php echo $currentWeek; ?>주차)</div>
                                <div class="flex items-center justify-between text-blue-700">
                                    <span><?php echo $weeklyGoalText; ?></span>
                                    <button onclick="editCurrentWeekGoal()" class="text-blue-600 hover:text-blue-800 ml-2" title="목표 수정">📝</button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-600 text-sm">이번 주 목표가 설정되지 않았습니다.</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 현재 주차 목표 입력 -->
                    <div id="currentWeekInput" style="display: none;" class="mb-4">
                        <input type="text" id="currentWeekText" class="input-field mb-2" placeholder="<?php echo $currentWeek; ?>주차 목표를 입력하세요">
                        <div class="flex gap-2">
                            <button onclick="saveCurrentWeekGoal()" class="btn-primary">저장</button>
                            <button onclick="cancelCurrentWeekGoal()" class="btn-secondary">취소</button>
                        </div>
                    </div>
                    
                    <!-- Brain Dump 영역 -->
                    <div class="brain-dump-container">
                        <div class="brain-dump-title">
                            🧠 Brain Dump - 떠오르는 키워드들
                        </div>
                        
                        <div class="tag-cloud" id="tagCloud">
                            <!-- 태그들이 동적으로 추가됩니다 -->
                        </div>
                        
                        <div class="empty-brain-dump" id="emptyBrainDump" style="display: block;">
                            아직 추가된 키워드가 없습니다. 학습과 관련된 키워드를 자유롭게 추가해보세요!
                        </div>
                        
                        <div class="tag-input-container">
                            <input type="text" id="tagInput" class="tag-input" placeholder="키워드를 입력하세요 (예: 미분, 적분, 함수의극한...)" maxlength="20">
                            <button onclick="addTag()" class="tag-add-btn">+ 추가</button>
                        </div>
                    </div>
                    
                    <!-- 전체 8주차 목표 (기본으로 표시) -->
                    <div id="allWeeksSection" style="display: block;">
                        <h5 class="font-medium text-gray-700 mb-3">전체 8주차 계획</h5>
                        <form id="allWeeksForm">
                            <div id="weekInputs">
                                <?php for ($week = 1; $week <= 8; $week++): ?>
                                    <div class="goal-item border rounded-lg p-3 mb-2 <?php echo $week == $currentWeek ? 'bg-blue-50 border-blue-200' : 'bg-gray-50'; ?>" id="week-<?php echo $week; ?>">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="flex-shrink-0 w-12 text-center font-bold <?php echo $week == $currentWeek ? 'text-blue-600' : 'text-gray-600'; ?>">
                                                <?php echo $week; ?>주차
                                                <?php if ($week == $currentWeek): ?>
                                                    <span class="text-xs">(현재)</span>
                                                <?php endif; ?>
                                            </span>
                                            <div class="text-sm text-gray-600">
                                                <?php 
                                                $weekStartDate = date('m/d', $mondayStartTime + (($week-1) * 7 * 24 * 60 * 60));
                                                $weekEndDate = date('m/d', $mondayStartTime + (($week-1) * 7 * 24 * 60 * 60) + (6 * 24 * 60 * 60));
                                                echo $weekStartDate . ' ~ ' . $weekEndDate;
                                                ?>
                                            </div>
                                        </div>
                                        <input type="text" name="week_<?php echo $week; ?>" value="<?php echo htmlspecialchars($weeklyGoals[$week], ENT_QUOTES); ?>" class="input-field" placeholder="<?php echo $week; ?>주차 목표를 입력하세요">
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="flex gap-2 mt-3">
                                <button type="button" onclick="addMoreWeeks()" class="btn-secondary">+ 주차 추가</button>
                                <button type="button" onclick="saveAllWeekGoals()" class="btn-primary">모든 주간 목표 저장</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 주간 계획 상세 (시간표 기반) -->
        <div class="section-card" id="weeklyPlansCard">
            <div class="section-header weekly-goal" style="cursor: default;">
                <div class="flex items-center relative">
                    <!-- 좌측 영역 -->
                    <div class="flex items-center gap-3 flex-1" onclick="toggleSection('weeklyPlans')" style="cursor: pointer;">
                        <span>📅 주간 계획</span>
                        <span id="weeklyPlansToggle" class="text-white opacity-75 section-toggle">▼</span>
                    </div>
                    
                    <!-- 중앙 영역 (절대 중앙 정렬) -->
                    <div class="absolute left-1/2 transform -translate-x-1/2">
                        <?php if (!empty($weeklyGoalText)): ?>
                            <span class="text-base font-medium text-center opacity-90 whitespace-nowrap"><?php echo htmlspecialchars($weeklyGoalText, ENT_QUOTES); ?></span>
                        <?php else: ?>
                            <span class="text-base font-medium text-center opacity-60 whitespace-nowrap">주간 목표를 설정해주세요</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 우측 영역 -->
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <!-- 요일 하이라이트 바 -->
                        <div class="flex gap-0">
                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                <span class="px-1 py-1 text-xs rounded-sm <?php echo $i == $todayDayOfWeek ? 'bg-white text-blue-600 font-bold' : 'text-white opacity-70'; ?>">
                                    <?php echo $dayNamesKorean[$i-1]; ?>
                                </span>
                            <?php endfor; ?>
                        </div>
                        
                      
                    </div>
                </div>
            </div>
            <div class="section-content" id="weeklyPlansContent" style="display: none;">
                <?php if (!empty($activeDays)): ?>
                    <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                        <div class="text-sm text-blue-800 mb-2">📅 이번 주 시간표</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                            <?php foreach ($activeDays as $dayIndex): ?>
                                <?php $dayData = $scheduleData[$dayIndex]; ?>
                                <div class="text-blue-700">
                                    <span class="font-medium"><?php echo $dayData['day']; ?>요일:</span>
                                    <?php echo $dayData['start_time'] ? $dayData['start_time'] : '시간 미정'; ?> 
                                    (<?php echo $dayData['duration']; ?>시간)
                                    <?php if (!empty($dayData['room'])): ?>
                                        - <?php echo $dayData['room']; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-medium text-gray-700">📝 요일별 학습 계획</h4>
                    <button type="button" onclick="toggleWeeklyPlansForm()" class="btn-secondary">편집</button>
                </div>
                
                <form id="weeklyPlansForm">
                    <?php if (!empty($activeDays)): ?>
                        <?php foreach ($activeDays as $dayIndex): ?>
                            <?php 
                            $dayData = $scheduleData[$dayIndex];
                            $planValue = isset($weeklyPlans[$dayIndex-1]) ? $weeklyPlans[$dayIndex-1] : '';
                            ?>
                            <div class="goal-item border rounded-lg p-3 mb-3 bg-white">
                                <div class="flex items-center gap-3">
                                    <span class="flex-shrink-0 w-8 text-center font-bold text-blue-600"><?php echo $dayData['day']; ?></span>
                                    <input type="text" name="week<?php echo $dayIndex; ?>" value="<?php echo $planValue; ?>" class="input-field" placeholder="<?php echo $dayData['day']; ?>요일 학습 계획을 입력하세요">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <div class="text-4xl mb-4">📅</div>
                            <div class="text-lg mb-2">등록된 시간표가 없습니다</div>
                            <div class="text-sm">
                                <a href="schedule.php?id=<?php echo $studentid; ?>" class="text-blue-600 hover:text-blue-800">시간표를 먼저 설정해주세요</a>
                            </div>
                        </div>
                        
                        <!-- 시간표가 없는 경우 기본 7일 표시 (달력 제거) -->
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <?php 
                            $dayNames = ['월', '화', '수', '목', '금', '토', '일'];
                            $planValue = isset($weeklyPlans[$i-1]) ? $weeklyPlans[$i-1] : '';
                            ?>
                            <div class="goal-item">
                                <span class="flex-shrink-0 w-8 text-center font-medium"><?php echo $dayNames[$i-1]; ?></span>
                                <input type="text" name="week<?php echo $i; ?>" value="<?php echo $planValue; ?>" class="input-field" placeholder="<?php echo $dayNames[$i-1]; ?>요일 계획을 입력하세요">
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                    
                    <button type="button" onclick="saveWeeklyPlans()" class="btn-primary mt-3">주간 계획 저장</button>
                </form>
            </div>
        </div>

        <!-- 오늘 목표 -->
        <div class="section-card" id="dailyGoalCard">
            <div class="section-header daily-goal" style="cursor: default;">
                <div class="flex items-center relative">
                    <!-- 좌측 영역 -->
                    <div class="flex items-center gap-3 flex-1" onclick="toggleSection('dailyGoal')" style="cursor: pointer;">
                        <span>📝 오늘 목표</span>
                        <span id="dailyGoalToggle" class="text-white opacity-75 section-toggle">▼</span>
                    </div>
                    
                    <!-- 중앙 영역 (오늘 날짜의 주간 계획 표시) -->
                    <div class="absolute left-1/2 transform -translate-x-1/2">
                        <?php 
                        // 오늘 날짜의 주간 계획 가져오기
                        $todayPlan = '';
                        if (!empty($activeDays)) {
                            foreach ($activeDays as $dayIndex) {
                                if ($dayIndex == $todayDayOfWeek) {
                                    $todayPlan = isset($weeklyPlans[$dayIndex-1]) ? $weeklyPlans[$dayIndex-1] : '';
                                    break;
                                }
                            }
                        } else {
                            // 시간표가 없는 경우에도 오늘 요일의 주간 계획 가져오기
                            $todayPlan = isset($weeklyPlans[$todayDayOfWeek-1]) ? $weeklyPlans[$todayDayOfWeek-1] : '';
                        }
                        ?>
                        <span class="text-base font-medium text-center opacity-90 whitespace-nowrap">
                            <?php echo !empty($todayPlan) ? htmlspecialchars($todayPlan, ENT_QUOTES) : '오늘의 계획을 입력해주세요'; ?>
                        </span>
                    </div>
                 
                </div>
            </div>
            <div class="section-content" id="dailyGoalContent" style="display: none;">
                <div id="dailyGoalDisplay" style="display: none;">
                    <p class="text-gray-600 mb-3">주간 목표를 바탕으로 오늘 하루 집중할 구체적인 목표를 정하세요.</p>
                    <button onclick="addDailyGoal()" class="btn-primary mb-4">+ 오늘 목표 추가</button>
                </div>
                
                <div id="dailyGoalInput" style="display: none;" class="mb-4">
                    <input type="text" id="dailyGoalText" class="input-field mb-2" placeholder="오늘의 목표를 입력하세요">
                    <div class="flex gap-2">
                        <button onclick="saveDailyGoal()" class="btn-primary">저장</button>
                        <button onclick="cancelDailyGoal()" class="btn-secondary">취소</button>
                    </div>
                </div>

                <!-- 포모도르 계획 (헤더 없이 통합) -->
                <div id="pomodoroSection" style="display: block;">
                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-700">🍅 포모도르 계획</h4>
                            <button type="button" onclick="toggleChapterList()" class="btn-secondary">📚 목차보기</button>
                        </div>
                        <p class="text-gray-600 mb-4">25분 집중, 5분 휴식의 포모도르 기법으로 오늘 목표를 세분화하여 실행하세요.</p>
                        
                        <!-- 타임라인 기반 포모도르 플래너 -->
                        <div class="pomodoro-timeline-container" style="display: flex; height: 600px; background: #f8f9fa; border-radius: 12px; overflow: hidden;">
                            <!-- 좌측 타임라인 바 -->
                            <div class="timeline-sidebar" style="width: 120px; background: linear-gradient(180deg, #e3f2fd 0%, #bbdefb 100%); position: relative; border-right: 2px solid #1976d2;">
                                <div class="timeline-header" style="padding: 12px; text-align: center; font-weight: bold; color: #1976d2; border-bottom: 1px solid #1976d2;">
                                    시간표
                                </div>
                                <div id="timeline-scale" style="height: 580px; position: relative;">
                                    <!-- 시간 눈금이 여기에 동적으로 생성됩니다 -->
                                </div>
                            </div>
                            
                            <!-- 우측 활동 영역 -->
                            <div class="activities-area" style="flex: 1; padding: 16px; position: relative;">
                                <div id="pomodoroActivities" style="height: 100%; position: relative;">
                                    <!-- 활동 아이템들이 여기에 배치됩니다 -->
                                </div>
                                
                                <!-- 하단 컨트롤 -->
                                <div class="timeline-controls" style="position: absolute; bottom: 16px; left: 16px; right: 16px;">
                                    <div class="flex gap-2">
                                        <button type="button" onclick="addTimelineActivity()" class="btn-secondary">+ 활동 추가</button>
                                        <button type="button" onclick="resetTimeline()" class="btn-secondary">초기화</button>
                                        <div class="ml-auto text-sm text-gray-600">
                                            총 시간: <span id="totalTimeDisplay">6시간</span>
                                            <span id="saveStatus" style="margin-left: 15px; padding: 4px 8px; border-radius: 4px; font-size: 11px; display: none;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 숨겨진 기존 폼 (데이터 저장용) -->
                        <form id="pomodoroForm" style="display: none;">
                            <div id="pomodoroPlans">
                                <?php 
                                // 입력된 값이 있는 행의 개수 계산
                                $displayRows = 3; // 기본 3개
                                for ($i = 0; $i < 10; $i++) {
                                    if (!empty(trim($todayPlans[$i]))) {
                                        $displayRows = max($displayRows, $i + 1);
                                    }
                                }
                                
                                for ($i = 0; $i < $displayRows; $i++): 
                                ?>
                                    <?php
                                    $planValue = $todayPlans[$i] ?? '';
                                    $timeValue = $todayTimes[$i] ?? '';
                                    $urlValue = $todayUrls[$i] ?? '';
                                    $statusValue = $todayStatuses[$i] ?? ''; // 만족도 상태
                                    ?>
                                    <div class="goal-item">
                                        <input type="time" name="pomodoro_time<?php echo $i+1; ?>" value="<?php echo $timeValue; ?>" class="input-field">
                                        <input type="text" name="pomodoro_plan<?php echo $i+1; ?>" value="<?php echo $planValue; ?>" class="input-field" placeholder="활동 내용을 입력하세요">
                                        <input type="hidden" name="pomodoro_url<?php echo $i+1; ?>" value="<?php echo $urlValue; ?>">
                                        <button type="button" onclick="completePlan(<?php echo $i+1; ?>)" class="btn-secondary">완료</button>
                                        <?php if (empty($statusValue)): ?>
                                            <input type="checkbox" class="status-checkbox" data-week="<?php echo $i+1; ?>" style="width: 20px; height: 20px; margin-left: 10px; cursor: pointer;" title="만족도 선택">
                                        <?php else: ?>
                                            <span class="status-text" style="margin-left: 10px; padding: 4px 8px; background: #e3f2fd; border-radius: 4px; font-size: 14px;"><?php echo htmlspecialchars($statusValue); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 챕터 목록 -->
        <?php if (!empty($chapterlist)): ?>
        <div class="section-card" id="chapterSection" style="display: none;">
            <div class="section-header chapter-list">
                📚 학습 챕터
            </div>
            <div class="section-content">
                <?php echo $chapterlist; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 분기목표 전체 목록 모달 -->
    <div id="goalHistoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 12px; padding: 24px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">📋 분기목표 전체 목록 (최근 12개월)</h3>
                <button onclick="closeGoalHistory()" class="text-gray-500 hover:text-gray-700" style="font-size: 24px;">&times;</button>
            </div>
            
            <div id="goalHistoryContent">
                <?php if (!empty($allTermPlans)): ?>
                    <div class="space-y-4">
                        <?php foreach ($allTermPlans as $plan): ?>
                            <?php 
                            $planDeadline = date("Y년 m월 d일", $plan->deadline);
                            $planCreated = date("m/d", $plan->timecreated);
                            $daysLeft = round(($plan->deadline - $timecreated) / 86400);
                            $isActive = $plan->deadline > $timecreated;
                            $statusClass = $isActive ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200';
                            $statusText = $isActive ? '진행중' : '완료';
                            $statusColor = $isActive ? 'text-blue-600' : 'text-gray-500';
                            ?>
                            <div class="border rounded-lg p-4 <?php echo $statusClass; ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-800 mb-1">
                                            <?php echo htmlspecialchars($plan->memo, ENT_QUOTES); ?>
                                        </div>
                                        <?php if (!empty($plan->dreamchallenge)): ?>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">
                                                    🌟 <?php echo htmlspecialchars($plan->dreamchallenge, ENT_QUOTES); ?>
                                                </span>
                                                <?php if (!empty($plan->dreamurl)): ?>
                                                    <button onclick="openDreamViewer('<?php echo htmlspecialchars($plan->dreamurl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($plan->dreamchallenge, ENT_QUOTES); ?>')" class="text-xs text-purple-600 hover:text-purple-800">
                                                        🔗 자료보기
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-sm text-gray-600">
                                            목표일: <?php echo $planDeadline; ?> | 생성일: <?php echo $planCreated; ?>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs px-2 py-1 rounded-full <?php echo $isActive ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                        <?php if ($isActive): ?>
                                            <span class="text-xs <?php echo $statusColor; ?>">
                                                D-<?php echo $daysLeft; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-4xl mb-4">📝</div>
                        <div class="text-lg mb-2">아직 설정된 분기목표가 없습니다</div>
                        <div class="text-sm">첫 번째 분기목표를 설정해보세요!</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-6 text-center">
                <button onclick="closeGoalHistory()" class="btn-secondary">닫기</button>
            </div>
        </div>
    </div>

    <!-- 3초 알림 팝업 -->
    <div id="dreamNotification" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 32px; border-radius: 12px; z-index: 2000; box-shadow: 0 8px 32px rgba(0,0,0,0.3); text-align: center; min-width: 300px;">
        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
            🌟 꿈의 세계로 이동 중...
        </div>
        <div id="dreamNotificationText" style="font-size: 14px; opacity: 0.9; margin-bottom: 16px;">
            <!-- 동적으로 채워짐 -->
        </div>
        <div style="font-size: 12px; opacity: 0.7;">
            <span id="countdown">3</span>초 후 새 탭으로 열립니다
        </div>
    </div>

    <!-- 분기목표 입력 모달 -->
    <div id="termGoalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">🎯 분기 목표 설정</h3>
                <button onclick="closeTermGoalModal()" class="text-gray-500 hover:text-gray-700" style="font-size: 24px;">&times;</button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">목표 유형</label>
                <select id="termGoalType" class="input-field">
                    <option value="분기목표">분기목표</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">데드라인</label>
                <input type="date" id="termGoalDeadline" class="input-field" placeholder="데드라인을 선택하세요">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">분기 목표</label>
                <input type="text" id="termGoalText" class="input-field" placeholder="선생님과 상의하여 다음 분기까지의 목표를 입력해 주세요">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">랜덤꿈 챌린지</label>
                <div class="bg-gray-50 p-3 rounded-lg mb-2">
                    <span id="currentRandomDream" class="text-gray-800"></span>
                </div>
                <button type="button" onclick="changeRandomDream()" class="btn-secondary text-sm">다른 꿈으로 변경</button>
            </div>
            
            <div class="flex gap-3">
                <button id="termGoalSaveBtn" type="button" class="btn-primary flex-1">저장하기</button>
                <button onclick="closeTermGoalModal()" class="btn-secondary">취소</button>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수 설정
        var studentid = <?php echo $studentid; ?>;
        var dailyGoalId = <?php echo $goal->id ?? $pid ?? 1; ?>; // goal->id 우선 사용, 없으면 pid, 최종적으로 1
        var termplanId = <?php echo isset($termplan2->id) ? $termplan2->id : 1; ?>;
        var weeklyGoalId = <?php echo $wgoal->id ?? 0; ?>;
        var currentPomodoroRows = <?php echo $displayRows; ?>;
        var lastFocusedInput = null;
        var weeklyGoalTextData = <?php echo json_encode($weeklyGoalText); ?>;
        var dailyGoalTextData = <?php echo json_encode($goal->text ?? ''); ?>;
        var activeDays = <?php echo json_encode($activeDays); ?>;
        var scheduleData = <?php echo json_encode($scheduleData); ?>;
        var currentWeek = <?php echo $currentWeek; ?>;
        var weeklyGoals = <?php echo json_encode($weeklyGoals); ?>;
        var maxWeeks = 8; // 최대 주차 수
        var mondayStartTime = <?php echo isset($mondayStartTime) ? $mondayStartTime : $timecreated; ?>;
        var hasPomodoroPlans = <?php echo $hasPomodoroPlans ? 'true' : 'false'; ?>;

        // 포모도르 타임라인 관련 변수
        var timelineData = {
            totalHours: 6, // 기본 6시간
            activities: [],
            pixelsPerHour: 96, // 1시간당 픽셀 수
            currentDragItem: null,
            startY: 0,
            startTime: null
        };

        // 기존 포모도르 데이터를 타임라인으로 변환
        var existingPlans = <?php echo json_encode($todayPlans); ?>;
        var existingTimes = <?php echo json_encode($todayTimes); ?>;
        var existingUrls = <?php echo json_encode($todayUrls); ?>;
        var existingStatuses = <?php echo json_encode($todayStatuses); ?>; // 만족도 상태

        // 랜덤꿈 리스트
        var randomDreamList = [
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
        
        var randomDreamUrlList = [
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

        var currentRandomDream = "";
        var currentRandomDreamUrl = "";

        // 섹션 접기/펼치기 상태 관리
        var sectionStates = {
            termGoal: false,
            weeklyPlans: false,
            dailyGoal: true
        };

        // 섹션 토글 함수 (하나만 펼쳐지도록, 애니메이션 포함)
        function toggleSection(sectionName) {
            // 모든 섹션 접기
            Object.keys(sectionStates).forEach(function(key) {
                if (key !== sectionName) {
                    var wasExpanded = sectionStates[key];
                    sectionStates[key] = false;
                    var content = document.getElementById(key + 'Content');
                    var toggle = document.getElementById(key + 'Toggle');
                    
                    if (content && wasExpanded) {
                        // 접기 애니메이션
                        content.classList.remove('expanded');
                        content.classList.add('collapsed');
                        setTimeout(function() {
                            content.style.display = 'none';
                        }, 300);
                    }
                    
                    if (toggle) {
                        toggle.textContent = '▶';
                        toggle.classList.remove('rotated');
                    }
                }
            });
            
            // 클릭된 섹션 토글
            var wasExpanded = sectionStates[sectionName];
            sectionStates[sectionName] = !sectionStates[sectionName];
            var content = document.getElementById(sectionName + 'Content');
            var toggle = document.getElementById(sectionName + 'Toggle');
            
            if (content) {
                if (sectionStates[sectionName]) {
                    // 펼치기 애니메이션
                    content.style.display = 'block';
                    content.classList.remove('collapsed');
                    // 약간의 지연을 주어 display:block이 적용된 후 애니메이션 시작
                    setTimeout(function() {
                        content.classList.add('expanded');
                    }, 10);
                } else {
                    // 접기 애니메이션
                    content.classList.remove('expanded');
                    content.classList.add('collapsed');
                    setTimeout(function() {
                        content.style.display = 'none';
                    }, 300);
                }
            }
            
            if (toggle) {
                toggle.textContent = sectionStates[sectionName] ? '▼' : '▶';
                if (sectionStates[sectionName]) {
                    toggle.classList.remove('rotated');
                } else {
                    toggle.classList.add('rotated');
                }
            }
        }

        // 분기목표 모달 관련 함수
        function openTermGoalModal() {
            // 현재 분기목표가 있으면 모달에 채움
            <?php if (!empty($termMission) && $termMission !== '분기목표를 설정해주세요'): ?>
                document.getElementById('termGoalText').value = <?php echo json_encode($termMission); ?>;
                document.getElementById('termGoalDeadline').value = <?php echo json_encode(date('Y-m-d', $termplan->deadline)); ?>;
                currentRandomDream = <?php echo json_encode($termplan->dreamchallenge ?? ''); ?>;
                currentRandomDreamUrl = <?php echo json_encode($termplan->dreamurl ?? ''); ?>;
            <?php else: ?>
                // 새로운 목표 설정 시 기본값 설정
                // 기본 날짜: 현재로부터 한 달 후
                var today = new Date();
                var oneMonthLater = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
                var defaultDate = oneMonthLater.toISOString().split('T')[0];
                document.getElementById('termGoalDeadline').value = defaultDate;
                
                // 새로운 랜덤꿈 선택
                changeRandomDream();
            <?php endif; ?>
            
            if (!currentRandomDream) {
                changeRandomDream();
            }
            
            document.getElementById('currentRandomDream').textContent = currentRandomDream;
            document.getElementById('termGoalModal').style.display = 'flex';
        }

        function closeTermGoalModal() {
            document.getElementById('termGoalModal').style.display = 'none';
            document.getElementById('termGoalText').value = '';
            document.getElementById('termGoalDeadline').value = '';
        }

        function changeRandomDream() {
            var index = Math.floor(Math.random() * randomDreamList.length);
            currentRandomDream = randomDreamList[index];
            currentRandomDreamUrl = randomDreamUrlList[index];
            document.getElementById('currentRandomDream').textContent = currentRandomDream;
        }

        function saveTermGoal() {
            console.log("saveTermGoal 함수 호출됨");
            
            var goalText = document.getElementById('termGoalText').value.trim();
            var deadline = document.getElementById('termGoalDeadline').value;
            var planType = document.getElementById('termGoalType').value;
            
            console.log("입력값:", {goalText, deadline, planType, currentRandomDream, currentRandomDreamUrl});
            
            if (!goalText) {
                console.log("목표 텍스트가 비어있음");
                alert("목표를 입력해주세요.");
                return;
            }
            
            if (!deadline) {
                console.log("데드라인이 비어있음");
                alert("데드라인을 선택해주세요.");
                return;
            }

            // 팝업 없이 바로 저장 (기존 꿈 유지)
            var randomDreamParam = "stay";
            
            console.log("AJAX 요청 시작", {
                eventid: 8,
                userid: studentid,
                plantype: planType,
                deadline: deadline,
                inputtext: goalText,
                randomdream: randomDreamParam,
                randomdreamurl: currentRandomDreamUrl
            });
            
            $.ajax({
                url: "database.php",
                type: "POST",
                data: {
                    "eventid": 8,
                    "userid": studentid,
                    "plantype": planType,
                    "deadline": deadline,
                    "inputtext": goalText,
                    "randomdream": randomDreamParam,
                    "randomdreamurl": currentRandomDreamUrl
                },
                success: function(data) {
                    console.log("AJAX 성공:", data);
                    alert("분기 목표가 저장되었습니다!");
                    closeTermGoalModal();
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", error);
                    console.log("Status:", status);
                    console.log("Response:", xhr.responseText);
                    alert("저장에 실패했습니다. 오류: " + error + "\n응답: " + xhr.responseText);
                }
            });
        }

        // 주간 목표 관련 함수
        function addWeeklyGoal() {
            document.getElementById('weeklyGoalInput').style.display = 'block';
            document.getElementById('weeklyGoalText').focus();
        }

        function editWeeklyGoal() {
            document.getElementById('weeklyGoalInput').style.display = 'block';
            document.getElementById('weeklyGoalText').value = weeklyGoalTextData;
            document.getElementById('weeklyGoalText').focus();
        }

        function saveWeeklyGoal() {
            var goalText = document.getElementById('weeklyGoalText').value.trim();
            if (!goalText) {
                swal("", "목표를 입력해주세요.", {buttons: false, timer: 2000});
                return;
            }

            $.ajax({
                url: "database2.php",
                type: "POST",
                data: {
                    eventid: 2,
                    inputtext: goalText,
                    type: '주간목표',
                    mindset: '주간목표',
                    userid: studentid
                },
                success: function(response) {
                    swal("", "저장되었습니다.", {buttons: false, timer: 1500});
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                }
            });
        }

        function cancelWeeklyGoal() {
            document.getElementById('weeklyGoalInput').style.display = 'none';
            document.getElementById('weeklyGoalText').value = '';
        }

        // 새로운 8주차 주간목표 관련 함수들
        function addWeeklyGoalPlan() {
            document.getElementById('currentWeekInput').style.display = 'block';
            document.getElementById('currentWeekText').focus();
        }

        function editCurrentWeekGoal() {
            document.getElementById('currentWeekInput').style.display = 'block';
            document.getElementById('currentWeekText').value = weeklyGoals[currentWeek] || '';
            document.getElementById('currentWeekText').focus();
        }

        function saveCurrentWeekGoal() {
            var goalText = document.getElementById('currentWeekText').value.trim();
            if (!goalText) {
                swal("", "목표를 입력해주세요.", {buttons: false, timer: 2000});
                return;
            }

            // 주차 정보를 포함한 텍스트로 저장
            var textWithWeek = currentWeek + '주차: ' + goalText;

            $.ajax({
                url: "database2.php",
                type: "POST",
                data: {
                    eventid: 2,
                    inputtext: textWithWeek,
                    type: '주간목표',
                    mindset: '주간목표',
                    userid: studentid
                },
                success: function(response) {
                    // 저장 성공 시 바로 표시 업데이트
                    document.getElementById('currentWeekGoal').innerHTML = 
                        '<div class="bg-blue-50 p-3 rounded-lg mb-2">' +
                            '<div class="font-medium text-blue-800">이번 주 목표 (' + currentWeek + '주차)</div>' +
                            '<div class="flex items-center justify-between text-blue-700">' +
                                '<span>' + goalText + '</span>' +
                                '<button onclick="editCurrentWeekGoal()" class="text-blue-600 hover:text-blue-800 ml-2" title="목표 수정">📝</button>' +
                            '</div>' +
                        '</div>';
                    
                    // 입력창 숨기기
                    document.getElementById('currentWeekInput').style.display = 'none';
                    
                    // 전역 변수 업데이트
                    weeklyGoals[currentWeek] = goalText;
                    
                    // 하위 섹션들 표시
                    document.getElementById('weeklyPlansCard').style.display = 'block';
                    document.getElementById('dailyGoalCard').style.display = 'block';
                    
                    swal("", "저장되었습니다.", {buttons: false, timer: 1500});
                },
                error: function() {
                    swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                }
            });
        }

        function cancelCurrentWeekGoal() {
            document.getElementById('currentWeekInput').style.display = 'none';
            document.getElementById('currentWeekText').value = '';
        }

        function toggleWeeklyGoalsExpand() {
            // 이 함수는 더 이상 사용되지 않습니다
            console.log('toggleWeeklyGoalsExpand 함수가 비활성화되었습니다');
        }

        function saveAllWeekGoals() {
            var formData = $("#allWeeksForm").serializeArray();
            var weekGoals = {};
            
            formData.forEach(function(item) {
                if (item.name.startsWith('week_')) {
                    var weekNum = item.name.replace('week_', '');
                    weekGoals[weekNum] = item.value;
                }
            });

            // 각 주차별로 개별 저장 (주차 정보를 텍스트에 포함)
            var promises = [];
            for (var week = 1; week <= maxWeeks; week++) {
                if (weekGoals[week] && weekGoals[week].trim()) {
                    var textWithWeek = week + '주차: ' + weekGoals[week].trim();
                    promises.push(
                        $.ajax({
                            url: "database2.php",
                            type: "POST",
                            data: {
                                eventid: 2,
                                inputtext: textWithWeek,
                                type: '주간목표',
                                mindset: '주간목표',
                                userid: studentid
                            }
                        })
                    );
                }
            }

            if (promises.length === 0) {
                swal("", "저장할 목표가 없습니다.", {buttons: false, timer: 1500});
                return;
            }

            Promise.all(promises).then(function() {
                // 저장 성공 시 현재 주차 목표 업데이트
                var currentWeekGoal = weekGoals[currentWeek];
                if (currentWeekGoal && currentWeekGoal.trim()) {
                    // 현재 주차 목표 표시 업데이트
                    document.getElementById('currentWeekGoal').innerHTML = 
                        '<div class="bg-blue-50 p-3 rounded-lg mb-2">' +
                            '<div class="font-medium text-blue-800">이번 주 목표 (' + currentWeek + '주차)</div>' +
                            '<div class="flex items-center justify-between text-blue-700">' +
                                '<span>' + currentWeekGoal.trim() + '</span>' +
                                '<button onclick="editCurrentWeekGoal()" class="text-blue-600 hover:text-blue-800 ml-2" title="목표 수정">📝</button>' +
                            '</div>' +
                        '</div>';
                    
                    // 전역 변수 업데이트
                    weeklyGoals[currentWeek] = currentWeekGoal.trim();
                }
                
                swal("", "저장되었습니다.", {buttons: false, timer: 1500});
            }).catch(function() {
                swal("", "일부 저장에 실패했습니다.", {buttons: false, timer: 2000});
            });
        }

        // 일별 목표 관련 함수
        function addDailyGoal() {
            document.getElementById('dailyGoalInput').style.display = 'block';
            document.getElementById('dailyGoalText').focus();
        }

        function saveDailyGoal() {
            var goalText = document.getElementById('dailyGoalText').value.trim();
            if (!goalText) {
                swal("", "목표를 입력해주세요.", {buttons: false, timer: 2000});
                return;
            }

            $.ajax({
                url: "database2.php",
                type: "POST",
                data: {
                    eventid: 2,
                    inputtext: goalText,
                    type: '오늘목표',
                    mindset: '오늘목표',
                    userid: studentid
                },
                success: function(response) {
                    swal("", "저장되었습니다.", {buttons: false, timer: 1500});
                    document.getElementById('pomodoroSection').style.display = 'block';
                    document.getElementById('dailyGoalInput').style.display = 'none';
                    document.getElementById('dailyGoalDisplay').innerHTML = 
                        '<div class="text-lg font-medium text-gray-800 mb-4">' + goalText + '</div>' +
                        '<button onclick="editDailyGoal()" class="btn-secondary">목표 수정</button>';
                },
                error: function() {
                    swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                }
            });
        }

        function cancelDailyGoal() {
            document.getElementById('dailyGoalInput').style.display = 'none';
            document.getElementById('dailyGoalText').value = '';
        }

        function editDailyGoal() {
            document.getElementById('dailyGoalInput').style.display = 'block';
            var currentGoalElement = document.querySelector('#dailyGoalDisplay .text-lg');
            if (currentGoalElement) {
                document.getElementById('dailyGoalText').value = currentGoalElement.textContent;
            } else {
                document.getElementById('dailyGoalText').value = dailyGoalTextData;
            }
            document.getElementById('dailyGoalText').focus();
        }

        // 포모도르 관련 함수
        function addMorePomodoro() {
            if (currentPomodoroRows >= 16) {
                swal("", "더 이상 추가할 수 없습니다.", {buttons: false, timer: 2000});
                return;
            }

            currentPomodoroRows++;
            var container = document.getElementById('pomodoroPlans');
            
            // 이전 시간에서 30분 간격으로 자동 계산
            var nextTime = '';
            var allTimeInputs = container.querySelectorAll('input[type="time"]');
            
            if (allTimeInputs.length > 0) {
                // 마지막 시간 입력 필드에서 시간 가져오기
                var lastTimeInput = allTimeInputs[allTimeInputs.length - 1];
                if (lastTimeInput.value) {
                    // 마지막 시간에서 30분 추가
                    var timeParts = lastTimeInput.value.split(':');
                    var hours = parseInt(timeParts[0]);
                    var minutes = parseInt(timeParts[1]) + 30;
                    
                    // 60분 이상이면 시간 증가
                    if (minutes >= 60) {
                        hours += 1;
                        minutes -= 60;
                    }
                    
                    // 24시간 초과 시 0시로 순환
                    if (hours >= 24) {
                        hours = 0;
                    }
                    
                    nextTime = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
                } else {
                    // 마지막 입력이 비어있으면 현재 시간 기준
                    nextTime = getCurrentTimeSlot();
                }
            } else {
                // 첫 번째 입력이면 현재 시간 기준
                nextTime = getCurrentTimeSlot();
            }
            
            var newRow = document.createElement('div');
            newRow.className = 'goal-item';
            newRow.innerHTML = 
                '<input type="time" name="pomodoro_time' + currentPomodoroRows + '" value="' + nextTime + '" class="input-field">' +
                '<input type="text" name="pomodoro_plan' + currentPomodoroRows + '" class="input-field" placeholder="활동 내용을 입력하세요">' +
                '<input type="hidden" name="pomodoro_url' + currentPomodoroRows + '" value="">' +
                '<button type="button" onclick="completePlan(' + currentPomodoroRows + ')" class="btn-secondary">완료</button>';
            container.appendChild(newRow);
            
            // 추가 후 자동 저장
            setTimeout(function() {
                autoSavePomodoroPlans();
            }, 100);
        }

        // 현재 시간 기준 다음 30분 슬롯 계산
        function getCurrentTimeSlot() {
            var now = new Date();
            var currentMinutes = now.getMinutes();
            var nextSlot = currentMinutes < 30 ? 30 : 0;
            var nextHour = nextSlot === 0 ? now.getHours() + 1 : now.getHours();
            
            // 24시간 형식 처리
            if (nextHour >= 24) {
                nextHour = 0;
            }
            
            return String(nextHour).padStart(2, '0') + ':' + String(nextSlot).padStart(2, '0');
        }

        function savePomodoroPlans() {
            var formData = $("#pomodoroForm").serializeArray();
            var postData = {};
            
            // 폼 데이터를 변환
            formData.forEach(function(item) {
                if (item.name.startsWith('pomodoro_time')) {
                    var num = item.name.replace('pomodoro_time', '');
                    postData['time' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_plan')) {
                    var num = item.name.replace('pomodoro_plan', '');
                    postData['week' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_url')) {
                    var num = item.name.replace('pomodoro_url', '');
                    postData['url' + num] = item.value;
                }
            });
            
            postData.studentid = studentid;
            postData.pid = dailyGoalId;

            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: postData,
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        swal("", "포모도르 계획이 저장되었습니다.", {buttons: false, timer: 1500});
                    } else {
                        swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                    }
                },
                error: function() {
                    swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                }
            });
        }

        function completePlan(index) {
            var planValue = $('input[name="pomodoro_plan' + index + '"]').val();
            var redirectUrl = 'https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=' + studentid + '&cntinput=' + encodeURIComponent(planValue);
            window.location.href = redirectUrl;
        }

        // 챕터 관련 함수
        $(document).on('focus', 'input[name^="pomodoro_plan"], input[name^="week"]', function() {
            lastFocusedInput = this;
        });

        $(document).on('click', '.insert-button', function() {
            var chapterTitle = $(this).data('title');
            var linkurl = $(this).data('linkurl');
            
            console.log("목차에서 플러스 버튼 클릭:", chapterTitle);
            
            // 1. 먼저 dailyGoal 섹션이 펼쳐져 있는지 확인하고, 접혀있으면 펼치기
            if (!sectionStates.dailyGoal) {
                toggleSection('dailyGoal');
            }
            
            // 2. 포모도르 섹션 활성화
            var pomodoroSection = document.getElementById('pomodoroSection');
            if (pomodoroSection && (pomodoroSection.style.display === 'none' || pomodoroSection.style.display === '')) {
                pomodoroSection.style.display = 'block';
                
                // timelineData 확인 및 초기화
                if (!window.timelineData) {
                    window.timelineData = {
                        totalHours: 6,
                        activities: [],
                        pixelsPerHour: 96,
                        currentDragItem: null,
                        startY: 0,
                        startTime: null
                    };
                }
                
                // 타임라인 초기화
                setTimeout(function() {
                    console.log("목차 플러스 버튼 - 타임라인 초기화");
                    initializePomodoroTimeline();
                }, 100);
            }
            
            // 3. 타임라인이 초기화되지 않은 경우 초기화
            if (!timelineData.activities) {
                timelineData.activities = [];
            }
            
            // 4. 포모도르 타임라인에 활동 추가
            var now = new Date();
            var currentTime = now.getHours() + (now.getMinutes() / 60);
            
            // 마지막 활동이 있으면 그 다음 시간으로 설정
            if (timelineData.activities.length > 0) {
                var lastActivity = timelineData.activities[timelineData.activities.length - 1];
                currentTime = lastActivity.startTime + lastActivity.duration;
            }
            
            var newActivity = {
                id: 'activity_' + Date.now(),
                title: chapterTitle,
                startTime: currentTime,
                duration: 0.5, // 기본 30분
                url: linkurl
            };
            
            console.log("새 활동 추가:", newActivity);
            
            timelineData.activities.push(newActivity);
            
            // 5. 타임라인 다시 그리기 및 즉시 저장
            setTimeout(function() {
                console.log("타임라인 업데이트 및 저장 시작");
                
                // 저장 상태 표시
                showSaveStatus('saving', '저장 중...');
                
                drawTimeline();
                drawActivities();
                calculateTimeAverages();
                
                // 저장 및 완료 확인
                savePomodoroTimeline();
                
                // 저장 완료 메시지
                setTimeout(function() {
                    swal("", "'" + chapterTitle + "'이(가) 포모도르 타임라인에 추가되고 저장되었습니다.", {buttons: false, timer: 2000});
                }, 1000);
                
                console.log("목차에서 활동 추가 완료");
            }, 300);
            
            return;
        });

        $(document).on('click', '.copy-button', function() {
            const textToCopy = $(this).attr("data-clipboard-text");
            navigator.clipboard.writeText(textToCopy).then(function() {
                swal("", "텍스트가 복사되었습니다", {buttons: false, timer: 500});
            }, function(err) {
                console.error("텍스트 복사 실패", err);
            });
        });

        // 만족도 체크박스 클릭 이벤트 (숨겨진 폼용)
        $(document).on('click', '.status-checkbox', function() {
            var $checkbox = $(this);
            var week = $checkbox.data('week');
            var statusField = 'status' + String(week).padStart(2, '0');

            console.log("만족도 체크박스 클릭 - week:", week);

            swal({
                title: "만족도를 선택하세요",
                buttons: {
                    cancel: {
                        text: "취소",
                        value: null,
                        visible: true,
                        closeModal: true
                    },
                    satisfied: {
                        text: "만족",
                        value: "만족"
                    },
                    verySatisfied: {
                        text: "매우만족",
                        value: "매우만족"
                    },
                    dissatisfied: {
                        text: "불만족",
                        value: "불만족"
                    }
                }
            }).then(function(value) {
                if (value) {
                    console.log("선택된 만족도:", value);

                    // existingStatuses 배열 업데이트
                    existingStatuses[week - 1] = value;

                    // AJAX로 저장
                    var formData = new FormData();
                    formData.append('studentid', studentid);
                    formData.append(statusField, value);

                    $.ajax({
                        url: 'save_todayplan.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log("만족도 저장 성공:", response);

                            // 체크박스를 텍스트로 변경
                            $checkbox.replaceWith(
                                '<span class="status-text" style="margin-left: 10px; padding: 4px 8px; background: #e3f2fd; border-radius: 4px; font-size: 14px;">' +
                                value +
                                '</span>'
                            );

                            swal("", "만족도가 저장되었습니다.", {buttons: false, timer: 1500});
                        },
                        error: function(xhr, status, error) {
                            console.error("만족도 저장 실패:", error);
                            swal("오류", "만족도 저장에 실패했습니다.", "error");
                        }
                    });
                }
            });
        });

        // 만족도 체크박스 클릭 이벤트 (타임라인 활동용)
        $(document).on('click', '.status-checkbox-timeline', function(e) {
            e.stopPropagation(); // 이벤트 버블링 방지
            var $checkbox = $(this);
            var index = $checkbox.data('index');
            var week = index + 1; // index는 0부터 시작, week는 1부터 시작
            var statusField = 'status' + String(week).padStart(2, '0');

            console.log("타임라인 만족도 체크박스 클릭 - index:", index, "week:", week);

            swal({
                title: "만족도를 선택하세요",
                buttons: {
                    cancel: {
                        text: "취소",
                        value: null,
                        visible: true,
                        closeModal: true
                    },
                    satisfied: {
                        text: "만족",
                        value: "만족"
                    },
                    verySatisfied: {
                        text: "매우만족",
                        value: "매우만족"
                    },
                    dissatisfied: {
                        text: "불만족",
                        value: "불만족"
                    }
                }
            }).then(function(value) {
                if (value) {
                    console.log("선택된 만족도:", value);

                    // existingStatuses 배열 업데이트
                    existingStatuses[index] = value;

                    // AJAX로 저장
                    var formData = new FormData();
                    formData.append('studentid', studentid);
                    formData.append(statusField, value);

                    $.ajax({
                        url: 'save_todayplan.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log("만족도 저장 성공:", response);

                            // 타임라인 다시 그리기 (체크박스가 텍스트로 변경됨)
                            drawActivities();

                            swal("", "만족도가 저장되었습니다.", {buttons: false, timer: 1500});
                        },
                        error: function(xhr, status, error) {
                            console.error("만족도 저장 실패:", error);
                            swal("오류", "만족도 저장에 실패했습니다.", "error");
                        }
                    });
                }
            });
        });

        // 꿈의 세계 뷰어 관련 함수
        function openDreamViewer(dreamUrl, dreamTitle) {
            if (!dreamUrl) {
                alert("꿈의 세계 링크가 없습니다.");
                return;
            }
            
            console.log("꿈의 세계 열기:", dreamUrl, dreamTitle);
            
            document.getElementById('dreamNotificationText').textContent = dreamTitle + " 에 대한 자료가 새탭으로 열립니다";
            document.getElementById('dreamNotification').style.display = 'block';
            
            // 카운트다운 함수
            let countdown = 3;
            const countdownElement = document.getElementById('countdown');
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(timer);
                    window.open(dreamUrl, '_blank');
                    document.getElementById('dreamNotification').style.display = 'none';
                }
            }, 1000);
        }

        // 분기목표 전체 목록 관련 함수
        function openGoalHistory() {
            console.log("분기목표 전체 목록 열기");
            document.getElementById('goalHistoryModal').style.display = 'flex';
        }

        function closeGoalHistory() {
            document.getElementById('goalHistoryModal').style.display = 'none';
        }

        // 초기화
        $(document).ready(function() {
            console.log("=== 페이지 로드 완료 ===");
            console.log("Student ID:", studentid);
            console.log("Daily Goal ID:", dailyGoalId);
            console.log("Termplan ID:", termplanId);
            
            // 포모도르 타임라인 무조건 초기화
            if (!window.timelineData) {
                window.timelineData = {
                    totalHours: 6,
                    activities: [],
                    pixelsPerHour: 96,
                    currentDragItem: null,
                    startY: 0,
                    startTime: null
                };
                console.log("timelineData 초기화됨");
            }
            
            // 기존 포모도르 데이터 로드
            initializePomodoroTimeline();
            
            // 섹션 상태 초기화
            initializeSectionStates();
            
            // 브레인 덤프 태그 로드
            loadTagsFromServer();
            
            // 태그 입력 이벤트 설정
            setupTagInput();
            
            // 저장 상태 요소가 없으면 생성
            if (!document.getElementById('saveStatus')) {
                var saveStatusDiv = document.createElement('div');
                saveStatusDiv.id = 'saveStatus';
                saveStatusDiv.style.cssText = 'display: none; position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; border-radius: 4px; font-size: 12px; z-index: 1001;';
                document.body.appendChild(saveStatusDiv);
                console.log("저장 상태 표시 요소 생성됨");
            }
            
            console.log("=== 초기화 완료 ===");
        });

        function saveWeeklyPlans() {
            var formData = $("#weeklyPlansForm").serialize();
            $.ajax({
                url: "save_weekly_goals.php",
                type: "POST",
                data: formData + "&studentid=" + studentid + "&pid=" + termPlanId,
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        swal("", "저장되었습니다.", {buttons: false, timer: 1500});
                    } else {
                        swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                    }
                },
                error: function() {
                    swal("", "저장에 실패했습니다.", {buttons: false, timer: 2000});
                }
            });
        }

        // 주간 계획 폼 토글
        function toggleWeeklyPlansForm() {
            var form = document.getElementById('weeklyPlansForm');
            var inputs = form.querySelectorAll('input[type="text"]');
            var saveButton = form.querySelector('button[onclick="saveWeeklyPlans()"]');
            var toggleButton = document.querySelector('button[onclick="toggleWeeklyPlansForm()"]');
            
            if (toggleButton.textContent === '편집') {
                // 편집 모드로 전환
                inputs.forEach(function(input) {
                    input.disabled = false;
                    input.style.background = 'white';
                });
                saveButton.style.display = 'block';
                toggleButton.textContent = '완료';
                toggleButton.className = 'btn-primary';
            } else {
                // 읽기 모드로 전환
                inputs.forEach(function(input) {
                    input.disabled = true;
                    input.style.background = '#f8f9fa';
                });
                saveButton.style.display = 'none';
                toggleButton.textContent = '편집';
                toggleButton.className = 'btn-secondary';
            }
        }

        // 포모도르 섹션 토글
        function togglePomodoroSection() {
            console.log("포모도르 섹션 토글");
            var section = document.getElementById('pomodoroSection');
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                
                // timelineData 확인 및 초기화
                if (!window.timelineData) {
                    window.timelineData = {
                        totalHours: 6,
                        activities: [],
                        pixelsPerHour: 96,
                        currentDragItem: null,
                        startY: 0,
                        startTime: null
                    };
                }
                
                // 타임라인 초기화
                setTimeout(function() {
                    console.log("포모도르 섹션 열기 - 타임라인 초기화");
                    initializePomodoroTimeline();
                }, 100);
            } else {
                section.style.display = 'none';
            }
        }

        // 챕터 목록 토글
        function toggleChapterList() {
            var section = document.getElementById('chapterSection');
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        // 다음 빈 포모도르 입력칸 찾기
        function findNextEmptyPomodoroInput() {
            var inputs = document.querySelectorAll('input[name^="pomodoro_plan"]');
            for (var i = 0; i < inputs.length; i++) {
                if (!inputs[i].value.trim()) {
                    return inputs[i];
                }
            }
            return null;
        }

        // 포모도르 자동 저장
        function autoSavePomodoroPlans() {
            var formData = $("#pomodoroForm").serializeArray();
            var postData = {};
            
            // 폼 데이터를 변환
            formData.forEach(function(item) {
                if (item.name.startsWith('pomodoro_time')) {
                    var num = item.name.replace('pomodoro_time', '');
                    postData['time' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_plan')) {
                    var num = item.name.replace('pomodoro_plan', '');
                    postData['week' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_url')) {
                    var num = item.name.replace('pomodoro_url', '');
                    postData['url' + num] = item.value;
                }
            });
            
            postData.studentid = studentid;
            postData.pid = dailyGoalId;

            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: postData,
                dataType: "json",
                success: function(response) {
                    console.log("자동 저장 완료");
                },
                error: function() {
                    console.log("자동 저장 실패");
                }
            });
        }

        // 주차 추가 기능
        function addMoreWeeks() {
            if (maxWeeks >= 16) {
                swal("", "더 이상 추가할 수 없습니다. (최대 16주차)", {buttons: false, timer: 2000});
                return;
            }

            maxWeeks++;
            var weekInputsContainer = document.getElementById('weekInputs');
            
            // 새로운 주차 입력 필드 생성
            var newWeekDiv = document.createElement('div');
            newWeekDiv.className = 'goal-item border rounded-lg p-3 mb-2 bg-gray-50';
            newWeekDiv.id = 'week-' + maxWeeks;
            
            // 날짜 계산 (월요일 기준)
            var weekStartTimestamp = mondayStartTime + ((maxWeeks-1) * 7 * 24 * 60 * 60);
            var weekEndTimestamp = weekStartTimestamp + (6 * 24 * 60 * 60);
            var weekStartDate = new Date(weekStartTimestamp * 1000).toLocaleDateString('ko-KR', {month: 'numeric', day: 'numeric'});
            var weekEndDate = new Date(weekEndTimestamp * 1000).toLocaleDateString('ko-KR', {month: 'numeric', day: 'numeric'});
            
            newWeekDiv.innerHTML = 
                '<div class="flex items-center gap-3 mb-2">' +
                    '<span class="flex-shrink-0 w-12 text-center font-bold text-gray-600">' +
                        maxWeeks + '주차' +
                    '</span>' +
                    '<div class="text-sm text-gray-600">' +
                        weekStartDate + ' ~ ' + weekEndDate +
                    '</div>' +
                '</div>' +
                '<input type="text" name="week_' + maxWeeks + '" value="" class="input-field" placeholder="' + maxWeeks + '주차 목표를 입력하세요">';
            
            weekInputsContainer.appendChild(newWeekDiv);
            
            swal("", maxWeeks + "주차가 추가되었습니다.", {buttons: false, timer: 1000});
        }

        // 포모도르 타임라인 초기화
        function initializePomodoroTimeline() {
            console.log("포모도르 타임라인 초기화 시작");
            console.log("기존 플랜 데이터:", existingPlans);
            console.log("기존 시간 데이터:", existingTimes);
            console.log("기존 URL 데이터:", existingUrls);
            
            // 기존 데이터를 타임라인 형식으로 변환
            timelineData.activities = [];
            
            // 저장된 포모도르 데이터에서 활동 생성
            for (var i = 0; i < existingPlans.length; i++) {
                if (existingPlans[i] && existingPlans[i].trim()) {
                    var timeValue = existingTimes[i];
                    var startTime = 9; // 기본값
                    
                    if (timeValue && timeValue.includes(':')) {
                        var timeParts = timeValue.split(':');
                        startTime = parseInt(timeParts[0]) + (parseInt(timeParts[1]) / 60);
                    }
                    
                    var activity = {
                        id: 'activity_' + (Date.now() + i), // 고유 ID 생성
                        title: existingPlans[i],
                        startTime: startTime,
                        duration: 0.5, // 30분 = 0.5시간
                        url: existingUrls[i] || ''
                    };
                    
                    timelineData.activities.push(activity);
                    console.log("활동 복원:", activity);
                }
            }
            
            // 활동이 있으면 시간순으로 정렬
            if (timelineData.activities.length > 0) {
                timelineData.activities.sort((a, b) => a.startTime - b.startTime);
                console.log("복원된 활동 수:", timelineData.activities.length);
                console.log("정렬된 활동 목록:", timelineData.activities);
            } else {
                console.log("복원할 활동이 없음");
            }
            
            // UI 업데이트
            drawTimeline();
            drawActivities();
            calculateTimeAverages();
            
            console.log("포모도르 타임라인 초기화 완료");
        }

        // 타임라인 그리기
        function drawTimeline() {
            var scale = document.getElementById('timeline-scale');
            if (!scale) {
                console.log("timeline-scale 요소를 찾을 수 없습니다");
                return;
            }
            
            scale.innerHTML = '';
            
            var startHour, endHour;
            
            if (!timelineData.activities || timelineData.activities.length === 0) {
                // 활동이 없으면 현재 시간 기준으로 6시간 표시
                var currentHour = new Date().getHours();
                startHour = Math.max(0, currentHour - 1);
                endHour = Math.min(24, currentHour + 6);
            } else {
                startHour = Math.min(...timelineData.activities.map(a => a.startTime)) - 1;
                startHour = Math.max(0, Math.floor(startHour));
                
                endHour = Math.max(...timelineData.activities.map(a => a.startTime + a.duration)) + 1;
                endHour = Math.min(24, Math.ceil(endHour));
            }
            
            timelineData.totalHours = endHour - startHour;
            
            // 시간 눈금 생성
            for (var hour = startHour; hour <= endHour; hour++) {
                for (var quarter = 0; quarter < 4; quarter++) {
                    var time = hour + (quarter * 0.25);
                    var y = (time - startHour) * timelineData.pixelsPerHour;
                    
                    var mark = document.createElement('div');
                    mark.className = 'timeline-mark';
                    mark.style.top = y + 'px';
                    
                    if (quarter === 0) {
                        mark.className += ' major';
                        var timeStr = String(hour % 24).padStart(2, '0') + ':00';
                        mark.setAttribute('data-time', timeStr);
                    } else if (quarter === 2) {
                        mark.className += ' minor';
                        var timeStr = String(hour % 24).padStart(2, '0') + ':30';
                        mark.setAttribute('data-time', timeStr);
                    } else {
                        mark.className += ' minor';
                        mark.style.opacity = '0.3';
                        mark.setAttribute('data-time', '');
                    }
                    
                    scale.appendChild(mark);
                }
            }
            
            // 총 시간 표시 업데이트
            var totalTimeElement = document.getElementById('totalTimeDisplay');
            if (totalTimeElement) {
                totalTimeElement.textContent = Math.round(timelineData.totalHours * 10) / 10 + '시간';
            }
        }

        // 활동 그리기
        function drawActivities() {
            var container = document.getElementById('pomodoroActivities');
            container.innerHTML = '';
            
            if (timelineData.activities.length === 0) {
                // 활동이 없을 때 안내 메시지와 추가 버튼 표시
                var emptyMessage = document.createElement('div');
                emptyMessage.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666; font-size: 14px; width: 80%;';
                emptyMessage.innerHTML = 
                    '<div style="margin-bottom: 20px; font-size: 16px;">📅</div>' +
                    '<div style="margin-bottom: 15px; line-height: 1.5;">아직 활동이 없습니다</div>' +
                    '<div style="margin-bottom: 20px; font-size: 12px; color: #999;">활동을 추가하거나 학습 챕터에서 선택해주세요</div>' +
                    '<button onclick="addTimelineActivity()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 12px;">+ 첫 활동 추가</button>';
                container.appendChild(emptyMessage);
                return;
            }
            
            var startHour = Math.min(...timelineData.activities.map(a => a.startTime)) - 1;
            startHour = Math.max(0, Math.floor(startHour));
            
            timelineData.activities.forEach(function(activity, index) {
                var activityDiv = document.createElement('div');
                activityDiv.className = 'activity-item';
                activityDiv.id = activity.id;
                activityDiv.draggable = false; // HTML5 드래그 비활성화
                
                var top = (activity.startTime - startHour) * timelineData.pixelsPerHour;
                // 고정 높이 사용 (40px)
                
                activityDiv.style.top = top + 'px';
                
                var startTimeStr = Math.floor(activity.startTime) + ':' + 
                    String(Math.round((activity.startTime % 1) * 60)).padStart(2, '0');
                var durationStr = Math.round(activity.duration * 60) + '분';
                
                // 만족도 상태 가져오기 (activity index + 1이 plan 번호)
                var statusValue = existingStatuses[index] || '';
                var statusHtml = '';

                if (statusValue) {
                    // 이미 만족도가 입력된 경우 텍스트로 표시
                    statusHtml = '<span class="status-text-timeline" data-index="' + index + '" style="margin-left: 6px; padding: 3px 8px; background: #e3f2fd; border-radius: 4px; font-size: 11px; color: #1976d2;">' + statusValue + '</span>';
                } else {
                    // 만족도가 없는 경우 체크박스 표시
                    statusHtml = '<input type="checkbox" class="status-checkbox-timeline" data-index="' + index + '" style="width: 16px; height: 16px; margin-left: 6px; cursor: pointer;" title="만족도 선택">';
                }

                activityDiv.innerHTML =
                    '<div class="activity-content">' +
                        '<div class="activity-title" title="' + activity.title + '">' + activity.title + '</div>' +
                    '</div>' +
                    '<div class="activity-controls">' +
                        '<span class="activity-time-badge">' + startTimeStr + '</span>' +
                        statusHtml +
                        '<button class="activity-complete" onclick="completeActivity(\'' + activity.id + '\')" title="완료">✓</button>' +
                        '<button class="activity-delete" onclick="deleteActivity(\'' + activity.id + '\')" title="삭제">×</button>' +
                    '</div>';
                
                // 드래그 이벤트 추가
                activityDiv.addEventListener('mousedown', startDrag);
                activityDiv.addEventListener('dblclick', function() {
                    editActivity(activity.id);
                });
                
                container.appendChild(activityDiv);
            });
        }

        // 드래그 시작
        function startDrag(e) {
            e.preventDefault();
            timelineData.currentDragItem = e.currentTarget;
            timelineData.startY = e.clientY;
            timelineData.startTime = parseFloat(timelineData.currentDragItem.style.top) / timelineData.pixelsPerHour;
            
            timelineData.currentDragItem.classList.add('dragging');
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', endDrag);
        }

        // 드래그 중
        function drag(e) {
            if (!timelineData.currentDragItem) return;
            
            var deltaY = e.clientY - timelineData.startY;
            var deltaTime = deltaY / timelineData.pixelsPerHour;
            var newTime = timelineData.startTime + deltaTime;
            
            // 0시 이전이나 24시 이후로 가지 않도록 제한
            newTime = Math.max(0, Math.min(23.75, newTime));
            
            var newTop = newTime * timelineData.pixelsPerHour;
            timelineData.currentDragItem.style.top = newTop + 'px';
            
            // 시간 배지 업데이트
            var timeStr = Math.floor(newTime) + ':' + 
                String(Math.round((newTime % 1) * 60)).padStart(2, '0');
            timelineData.currentDragItem.querySelector('.activity-time-badge').textContent = timeStr;
        }

        // 드래그 종료
        function endDrag(e) {
            if (!timelineData.currentDragItem) return;
            
            var activityId = timelineData.currentDragItem.id;
            var newTop = parseFloat(timelineData.currentDragItem.style.top);
            var startHour = Math.min(...timelineData.activities.map(a => a.startTime)) - 1;
            startHour = Math.max(0, Math.floor(startHour));
            var newTime = (newTop / timelineData.pixelsPerHour) + startHour;
            
            // 활동 데이터 업데이트
            var activity = timelineData.activities.find(a => a.id === activityId);
            if (activity) {
                activity.startTime = newTime;
            }
            
            timelineData.currentDragItem.classList.remove('dragging');
            timelineData.currentDragItem = null;
            
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', endDrag);
            
            // 시간 재계산 및 색상 업데이트
            calculateTimeAverages();
            
            // 즉시 저장
            savePomodoroTimeline();
        }

        // 시간 평균 계산 및 색상 업데이트
        function calculateTimeAverages() {
            if (timelineData.activities.length === 0) return;
            
            // 활동들을 시간순으로 정렬
            timelineData.activities.sort((a, b) => a.startTime - b.startTime);
            
            // 각 활동 간의 실제 소요시간 계산
            for (var i = 0; i < timelineData.activities.length; i++) {
                var activity = timelineData.activities[i];
                var nextActivity = timelineData.activities[i + 1];
                
                if (nextActivity) {
                    activity.duration = nextActivity.startTime - activity.startTime;
                } else {
                    // 마지막 활동은 기본 30분
                    activity.duration = 0.5;
                }
            }
            
            // 전체 평균 시간 계산
            var totalDuration = timelineData.activities.reduce((sum, a) => sum + a.duration, 0);
            var averageDuration = totalDuration / timelineData.activities.length;
            
            // 각 활동의 색상 업데이트
            timelineData.activities.forEach(function(activity, index) {
                var element = document.getElementById(activity.id);
                if (!element) return;
                
                // 남은 활동들의 평균 시간 계산
                var remainingActivities = timelineData.activities.slice(index + 1);
                var remainingAverage = remainingActivities.length > 0 ? 
                    remainingActivities.reduce((sum, a) => sum + a.duration, 0) / remainingActivities.length : 0;
                
                // 색상 결정
                element.classList.remove('over-average', 'under-average');
                if (remainingAverage > averageDuration) {
                    element.classList.add('over-average'); // 빨간색
                } else if (remainingAverage < averageDuration && remainingActivities.length > 0) {
                    element.classList.add('under-average'); // 파란색
                }
                
                // 기간 표시 업데이트
                var durationStr = Math.round(activity.duration * 60) + '분';
                element.querySelector('.activity-duration').textContent = durationStr;
            });
        }

        // 새 활동 추가
        function addTimelineActivity() {
            console.log("새 활동 추가 시작");
            
            // timelineData가 초기화되지 않았다면 초기화
            if (!window.timelineData || !timelineData.activities) {
                window.timelineData = {
                    totalHours: 6,
                    activities: [],
                    pixelsPerHour: 96,
                    currentDragItem: null,
                    startY: 0,
                    startTime: null
                };
                console.log("timelineData 초기화됨");
            }
            
            // 포모도르 섹션이 숨겨져 있으면 표시
            var pomodoroSection = document.getElementById('pomodoroSection');
            if (pomodoroSection && (pomodoroSection.style.display === 'none' || pomodoroSection.style.display === '')) {
                pomodoroSection.style.display = 'block';
                console.log("포모도르 섹션 표시됨");
            }
            
            // 오늘 목표 섹션이 접혀있으면 펼치기
            if (!sectionStates.dailyGoal) {
                toggleSection('dailyGoal');
                console.log("오늘 목표 섹션 펼쳐짐");
            }
            
            var now = new Date();
            var currentTime = now.getHours() + (now.getMinutes() / 60);
            
            // 마지막 활동이 있으면 그 다음 시간으로 설정
            if (timelineData.activities && timelineData.activities.length > 0) {
                var lastActivity = timelineData.activities[timelineData.activities.length - 1];
                currentTime = lastActivity.startTime + lastActivity.duration;
                console.log("마지막 활동 시간 기준으로 설정:", currentTime);
            }
            
            var newActivity = {
                id: 'activity_' + Date.now(),
                title: '새 활동',
                startTime: currentTime,
                duration: 0.5,
                url: ''
            };
            
            console.log("새 활동 생성:", newActivity);
            
            timelineData.activities.push(newActivity);
            
            console.log("현재 모든 활동 수:", timelineData.activities.length);
            console.log("현재 모든 활동:", timelineData.activities);
            
            // UI 업데이트
            drawTimeline();
            drawActivities();
            calculateTimeAverages();
            
            // 저장 상태 즉시 표시
            showSaveStatus('saving', '저장 중...');
            
            // 즉시 저장 (약간의 지연 후 실행하여 UI 업데이트 완료 후 저장)
            setTimeout(function() {
                console.log("활동 추가 후 저장 시작");
                savePomodoroTimeline();
                
                // 저장 완료 확인을 위한 추가 피드백
                setTimeout(function() {
                    swal("", "새 활동이 추가되고 저장되었습니다.", {buttons: false, timer: 1500});
                }, 1000);
            }, 100);
            
            // 새 활동 편집 모드로 전환 (저장 후)
            setTimeout(() => {
                console.log("편집 모드로 전환");
                editActivity(newActivity.id);
            }, 1500);
        }

        // 활동 완료 (현재 시간으로 이동)
        function completeActivity(activityId) {
            var activity = timelineData.activities.find(a => a.id === activityId);
            if (!activity) return;
            
            var now = new Date();
            var currentTime = now.getHours() + (now.getMinutes() / 60);
            
            activity.startTime = currentTime;
            
            drawTimeline();
            drawActivities();
            calculateTimeAverages();
            savePomodoroTimeline(); // 즉시 저장
            
            swal("", "활동이 현재 시간으로 이동되고 저장되었습니다.", {buttons: false, timer: 1500});
        }

        // 활동 편집
        function editActivity(activityId) {
            var activity = timelineData.activities.find(a => a.id === activityId);
            if (!activity) return;
            
            var newTitle = prompt('활동 내용을 입력하세요:', activity.title);
            if (newTitle !== null && newTitle.trim()) {
                activity.title = newTitle.trim();
                drawActivities();
                calculateTimeAverages();
                savePomodoroTimeline(); // 즉시 저장
                
                swal("", "활동이 수정되고 저장되었습니다.", {buttons: false, timer: 1000});
            }
        }

        // 활동 삭제
        function deleteActivity(activityId) {
            if (confirm('이 활동을 삭제하시겠습니까?')) {
                timelineData.activities = timelineData.activities.filter(a => a.id !== activityId);
                drawTimeline();
                drawActivities();
                calculateTimeAverages();
                savePomodoroTimeline(); // 즉시 저장
                
                swal("", "활동이 삭제되고 저장되었습니다.", {buttons: false, timer: 1000});
            }
        }

        // 타임라인 초기화
        function resetTimeline() {
            if (confirm('모든 활동을 초기화하시겠습니까?')) {
                console.log("타임라인 초기화 시작");
                
                // 활동 배열 완전 초기화
                timelineData.activities = [];
                
                // timelineData 객체 재설정
                timelineData.totalHours = 6;
                timelineData.pixelsPerHour = 96;
                timelineData.currentDragItem = null;
                timelineData.startY = 0;
                timelineData.startTime = null;
                
                // 포모도르 섹션이 숨겨져 있으면 표시
                var pomodoroSection = document.getElementById('pomodoroSection');
                if (pomodoroSection && (pomodoroSection.style.display === 'none' || pomodoroSection.style.display === '')) {
                    pomodoroSection.style.display = 'block';
                    console.log("포모도르 섹션 표시됨");
                }
                
                // 오늘 목표 섹션이 접혀있으면 펼치기
                if (!sectionStates.dailyGoal) {
                    toggleSection('dailyGoal');
                }
                
                // 타임라인 다시 그리기
                drawTimeline();
                drawActivities();
                calculateTimeAverages();
                
                // 초기화 후 자동 저장
                savePomodoroTimeline();
                
                swal("", "모든 활동이 초기화되고 저장되었습니다.", {buttons: false, timer: 1500});
                
                console.log("타임라인 초기화 완료");
            }
        }

        // 저장 상태 표시 함수들
        function showSaveStatus(status, message) {
            var saveStatusElement = document.getElementById('saveStatus');
            if (!saveStatusElement) return;
            
            saveStatusElement.style.display = 'inline-block';
            saveStatusElement.textContent = message;
            
            if (status === 'success') {
                saveStatusElement.style.backgroundColor = '#d4edda';
                saveStatusElement.style.color = '#155724';
                saveStatusElement.style.border = '1px solid #c3e6cb';
            } else if (status === 'error') {
                saveStatusElement.style.backgroundColor = '#f8d7da';
                saveStatusElement.style.color = '#721c24';
                saveStatusElement.style.border = '1px solid #f5c6cb';
            } else if (status === 'saving') {
                saveStatusElement.style.backgroundColor = '#fff3cd';
                saveStatusElement.style.color = '#856404';
                saveStatusElement.style.border = '1px solid #ffeaa7';
            }
            
            // 성공이나 에러 메시지는 3초 후 자동으로 사라짐
            if (status !== 'saving') {
                setTimeout(function() {
                    saveStatusElement.style.display = 'none';
                }, 3000);
            }
        }

        // 타임라인 저장
        function savePomodoroTimeline() {
            console.log("=== 포모도르 타임라인 저장 시작 ===");
            console.log("활동 데이터:", timelineData.activities);
            console.log("Student ID:", studentid);
            console.log("Daily Goal ID:", dailyGoalId);
            
            if (!timelineData.activities || timelineData.activities.length === 0) {
                console.log("저장할 활동이 없음");
                showSaveStatus('error', '저장할 활동이 없습니다');
                return;
            }
            
            // 저장 중 표시
            showSaveStatus('saving', '저장 중...');
            
            // dailyGoalId가 유효하지 않으면 studentid 사용
            var validPid = dailyGoalId && dailyGoalId > 0 ? dailyGoalId : studentid;
            console.log("사용할 PID:", validPid);
            
            // 기존 폼 데이터 업데이트
            var formContainer = document.getElementById('pomodoroPlans');
            if (!formContainer) {
                console.log("ERROR: pomodoroPlans 폼 컨테이너를 찾을 수 없음");
                showSaveStatus('error', '폼 오류');
                return;
            }
            
            formContainer.innerHTML = '';
            
            // 활동들을 시간순으로 정렬
            timelineData.activities.sort((a, b) => a.startTime - b.startTime);
            
            timelineData.activities.forEach(function(activity, index) {
                var timeValue = Math.floor(activity.startTime) + ':' + 
                    String(Math.round((activity.startTime % 1) * 60)).padStart(2, '0');
                
                var itemHtml = 
                    '<input type="time" name="pomodoro_time' + (index + 1) + '" value="' + timeValue + '">' +
                    '<input type="text" name="pomodoro_plan' + (index + 1) + '" value="' + escapeHtml(activity.title) + '">' +
                    '<input type="hidden" name="pomodoro_url' + (index + 1) + '" value="' + escapeHtml(activity.url) + '">';
                
                var div = document.createElement('div');
                div.className = 'goal-item';
                div.innerHTML = itemHtml;
                formContainer.appendChild(div);
                
                console.log("활동 " + (index + 1) + " 추가:", {
                    time: timeValue,
                    title: activity.title,
                    url: activity.url
                });
            });
            
            // 최대 10개까지 빈 항목으로 채우기 (기존 시스템 호환)
            for (var i = timelineData.activities.length; i < 10; i++) {
                var div = document.createElement('div');
                div.className = 'goal-item';
                div.innerHTML = 
                    '<input type="time" name="pomodoro_time' + (i + 1) + '" value="">' +
                    '<input type="text" name="pomodoro_plan' + (i + 1) + '" value="">' +
                    '<input type="hidden" name="pomodoro_url' + (i + 1) + '" value="">';
                formContainer.appendChild(div);
            }
            
            // 서버에 저장 - 직접 데이터 구성 방식 변경
            var postData = {
                studentid: studentid,
                pid: validPid
            };
            
            // 타임라인 데이터를 직접 POST 데이터로 변환
            timelineData.activities.forEach(function(activity, index) {
                var timeValue = Math.floor(activity.startTime) + ':' + 
                    String(Math.round((activity.startTime % 1) * 60)).padStart(2, '0');
                
                postData['time' + (index + 1)] = timeValue;
                postData['week' + (index + 1)] = activity.title;
                postData['url' + (index + 1)] = activity.url || '';
            });
            
            // 나머지 빈 슬롯 채우기
            for (var i = timelineData.activities.length + 1; i <= 10; i++) {
                postData['time' + i] = '';
                postData['week' + i] = '';
                postData['url' + i] = '';
            }
            
            console.log("=== 최종 저장 데이터 ===");
            console.log(JSON.stringify(postData, null, 2));

            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: postData,
                dataType: "json",
                timeout: 10000, // 10초 타임아웃
                beforeSend: function() {
                    console.log("=== AJAX 요청 시작 ===");
                    console.log("URL: save_todayplan.php");
                    console.log("요청 데이터:", postData);
                },
                success: function(response) {
                    console.log("=== 서버 응답 성공 ===");
                    console.log("Raw Response:", response);
                    console.log("Response Type:", typeof response);
                    
                    // existingPlans 배열도 업데이트하여 동기화
                    existingPlans = [];
                    existingTimes = [];
                    existingUrls = [];
                    
                    timelineData.activities.forEach(function(activity, index) {
                        var timeValue = Math.floor(activity.startTime) + ':' + 
                            String(Math.round((activity.startTime % 1) * 60)).padStart(2, '0');
                        existingPlans[index] = activity.title;
                        existingTimes[index] = timeValue;
                        existingUrls[index] = activity.url;
                    });
                    
                    console.log("existingPlans 업데이트:", existingPlans);
                    
                    // 성공 메시지 표시
                    if (response && response.status === 'success') {
                        console.log("✅ 저장 성공!");
                        showSaveStatus('success', '저장됨 ✓');
                        
                        // 추가: 저장 성공 알림
                        swal("", "포모도르 활동이 저장되었습니다!", {buttons: false, timer: 2000});
                        
                        // 추가: 저장 확인을 위한 서버 검증
                        setTimeout(function() {
                            console.log("저장 확인 중...");
                            verifyPomodoroSave();
                        }, 1000);
                    } else {
                        console.log("⚠️ 저장 응답 상태:", response ? response.status : 'undefined');
                        console.log("⚠️ 저장 응답 메시지:", response ? response.message : 'undefined');
                        showSaveStatus('error', '저장 실패');
                        
                        // 에러 상세 정보 표시
                        if (response && response.message) {
                            swal("", "저장 실패: " + response.message, {buttons: false, timer: 3000});
                        } else {
                            swal("", "저장 응답이 올바르지 않습니다.", {buttons: false, timer: 3000});
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log("=== AJAX 에러 ===");
                    console.log("포모도르 타임라인 저장 실패:", error);
                    console.log("Status:", status);
                    console.log("Response Text:", xhr.responseText);
                    console.log("Ready State:", xhr.readyState);
                    console.log("HTTP Status:", xhr.status);
                    
                    showSaveStatus('error', '저장 오류');
                    
                    // 사용자에게 에러 알림
                    if (status === 'timeout') {
                        swal("", "저장 시간이 초과되었습니다. 다시 시도해주세요.", {buttons: false, timer: 3000});
                    } else if (xhr.status === 404) {
                        swal("", "저장 파일을 찾을 수 없습니다. (save_todayplan.php)", {buttons: false, timer: 3000});
                    } else if (xhr.status === 500) {
                        swal("", "서버 오류가 발생했습니다.", {buttons: false, timer: 3000});
                    } else {
                        swal("", "저장 중 오류가 발생했습니다: " + error, {buttons: false, timer: 3000});
                    }
                    
                    // 콘솔에 상세 에러 출력
                    console.log("=== 상세 에러 정보 ===");
                    console.log("XHR 객체 전체:", xhr);
                    
                    // 에러 상세 정보 표시
                    if (xhr.responseText) {
                        console.log("서버 응답 원문:", xhr.responseText);
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            console.log("파싱된 에러 응답:", errorResponse);
                        } catch (e) {
                            console.log("JSON 파싱 실패, 원본 응답:", xhr.responseText);
                            
                            // PHP 에러가 포함되어 있는지 확인
                            if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                                swal("", "PHP 스크립트 오류가 발생했습니다. 개발자 콘솔을 확인해주세요.", {buttons: false, timer: 4000});
                            }
                        }
                    }
                }
            });
        }
        
        // 저장 확인 함수 추가
        function verifyPomodoroSave() {
            console.log("=== 저장 확인 요청 ===");
            
            // AJAX로 저장된 데이터 확인
            $.ajax({
                url: "check_todayplan.php", // 저장 확인용 별도 파일
                type: "POST",
                data: {
                    studentid: studentid,
                    pid: dailyGoalId
                },
                dataType: "json",
                success: function(response) {
                    console.log("저장 확인 응답:", response);
                    
                    if (response && response.status === 'success') {
                        console.log("✅ 저장 확인됨");
                        showSaveStatus('success', '저장 확인됨 ✓');
                    } else {
                        console.log("⚠️ 저장 확인 실패 - 재시도");
                        showSaveStatus('error', '저장 확인 실패 - 재시도 중...');
                        
                        // 3초 후 재시도
                        setTimeout(function() {
                            console.log("포모도르 저장 재시도");
                            savePomodoroTimeline();
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.log("저장 확인 오류:", error);
                    
                    // 확인 파일이 없어도 일단 성공으로 간주
                    if (xhr.status === 404) {
                        console.log("확인 파일 없음 - 저장 성공으로 간주");
                        showSaveStatus('success', '저장됨 ✓');
                    } else {
                        showSaveStatus('error', '저장 확인 불가');
                    }
                }
            });
        }
        
        // 강제 저장 함수 (사용자가 직접 호출할 수 있도록)
        function forceSavePomodoroTimeline() {
            console.log("=== 강제 저장 시작 ===");
            
            if (!timelineData.activities || timelineData.activities.length === 0) {
                swal("", "저장할 활동이 없습니다.", {buttons: false, timer: 2000});
                return;
            }
            
            // 사용자에게 저장 확인
            swal({
                title: "포모도르 활동 저장",
                text: timelineData.activities.length + "개의 활동을 저장하시겠습니까?",
                icon: "info",
                buttons: ["취소", "저장"],
                dangerMode: false,
            }).then((willSave) => {
                if (willSave) {
                    savePomodoroTimeline();
                }
            });
        }
        
        // 데이터 동기화 강화 함수
        function syncPomodoroData() {
            console.log("=== 데이터 동기화 시작 ===");
            
            // existingPlans 배열과 timelineData 동기화
            if (timelineData.activities && timelineData.activities.length > 0) {
                existingPlans = [];
                existingTimes = [];
                existingUrls = [];
                
                timelineData.activities.forEach(function(activity, index) {
                    var timeValue = Math.floor(activity.startTime) + ':' + 
                        String(Math.round((activity.startTime % 1) * 60)).padStart(2, '0');
                    existingPlans[index] = activity.title;
                    existingTimes[index] = timeValue;
                    existingUrls[index] = activity.url || '';
                });
                
                console.log("데이터 동기화 완료:");
                console.log("Plans:", existingPlans);
                console.log("Times:", existingTimes);
                console.log("URLs:", existingUrls);
            }
        }

        // HTML 이스케이프 함수 추가
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 서버에 태그 저장
        function saveTagsToServer() {
            $.ajax({
                url: "save_brain_dump.php",
                type: "POST",
                data: {
                    userid: studentid,
                    tags: JSON.stringify(userTags)
                },
                success: function(response) {
                    console.log("태그 저장 완료");
                },
                error: function() {
                    console.log("태그 저장 실패");
                }
            });
        }
        
        // 서버에서 태그 로드
        function loadTagsFromServer() {
            $.ajax({
                url: "load_brain_dump.php",
                type: "POST",
                data: {
                    userid: studentid
                },
                dataType: "json",
                success: function(response) {
                    if (response.tags) {
                        userTags = JSON.parse(response.tags);
                        renderTagCloud();
                    }
                },
                error: function() {
                    console.log("태그 로드 실패");
                }
            });
        }
        
        // Enter 키 이벤트
        function setupTagInput() {
            document.getElementById('tagInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addTag();
                }
            });
        }
        
        // Brain Dump Tag Cloud 관련 변수
        var userTags = [];
        
        // 태그 추가 함수 (자동 저장 포함)
        function addTag() {
            var input = document.getElementById('tagInput');
            var tagText = input.value.trim();
            
            if (!tagText) {
                swal("", "키워드를 입력해주세요.", {buttons: false, timer: 1500});
                return;
            }
            
            if (tagText.length > 20) {
                swal("", "키워드는 20자 이하로 입력해주세요.", {buttons: false, timer: 1500});
                return;
            }
            
            // 중복 체크
            if (userTags.includes(tagText)) {
                swal("", "이미 추가된 키워드입니다.", {buttons: false, timer: 1500});
                return;
            }
            
            userTags.push(tagText);
            input.value = '';
            renderTagCloud();
            saveTagsToServer(); // 자동 저장
        }
        
        // 태그 클라우드 렌더링
        function renderTagCloud() {
            var tagCloud = document.getElementById('tagCloud');
            var emptyMessage = document.getElementById('emptyBrainDump');
            
            if (userTags.length === 0) {
                tagCloud.innerHTML = '';
                emptyMessage.style.display = 'block';
                return;
            }
            
            emptyMessage.style.display = 'none';
            
            // 태그 빈도를 시뮬레이션 (실제로는 클릭 횟수나 사용 빈도로 계산 가능)
            var shuffledTags = [...userTags].sort(() => Math.random() - 0.5);
            
            tagCloud.innerHTML = '';
            shuffledTags.forEach(function(tag, index) {
                var tagElement = document.createElement('div');
                tagElement.className = 'tag-item ' + getTagSize(index);
                tagElement.innerHTML = 
                    '<span>' + tag + '</span>' +
                    '<button class="tag-delete" onclick="removeTag(\'' + tag + '\')" title="삭제">×</button>';
                tagElement.onclick = function(e) {
                    if (e.target.classList.contains('tag-delete')) return;
                    onTagClick(tag);
                };
                tagCloud.appendChild(tagElement);
            });
        }
        
        // 태그 크기 결정 (클라우드 효과)
        function getTagSize(index) {
            var total = userTags.length;
            if (index < total * 0.2) return 'size-large';
            if (index < total * 0.5) return 'size-medium';
            return 'size-small';
        }
        
        // 태그 제거 (자동 저장 포함)
        function removeTag(tagText) {
            userTags = userTags.filter(tag => tag !== tagText);
            renderTagCloud();
            saveTagsToServer(); // 자동 저장
        }
        
        // 태그 클릭 이벤트
        function onTagClick(tagText) {
            // 태그 클릭 시 해당 키워드를 포모도르에 추가하거나 검색 등의 동작 수행
            var confirmMessage = "'" + tagText + "' 키워드를 포모도르 계획에 추가하시겠습니까?";
            if (confirm(confirmMessage)) {
                // 포모도르 섹션 활성화 및 태그 추가
                if (!sectionStates.dailyGoal) {
                    toggleSection('dailyGoal');
                }
                
                var pomodoroSection = document.getElementById('pomodoroSection');
                if (pomodoroSection && (pomodoroSection.style.display === 'none' || pomodoroSection.style.display === '')) {
                    pomodoroSection.style.display = 'block';
                    setTimeout(function() {
                        initializePomodoroTimeline();
                    }, 100);
                }
                
                // 새 활동 추가
                var now = new Date();
                var currentTime = now.getHours() + (now.getMinutes() / 60);
                
                if (timelineData.activities && timelineData.activities.length > 0) {
                    var lastActivity = timelineData.activities[timelineData.activities.length - 1];
                    currentTime = lastActivity.startTime + lastActivity.duration;
                }
                
                var newActivity = {
                    id: 'activity_' + Date.now(),
                    title: tagText + ' 학습',
                    startTime: currentTime,
                    duration: 0.5,
                    url: ''
                };
                
                if (!timelineData.activities) {
                    timelineData.activities = [];
                }
                
                timelineData.activities.push(newActivity);
                
                setTimeout(function() {
                    drawTimeline();
                    drawActivities();
                    calculateTimeAverages();
                    savePomodoroTimeline(); // 자동 저장
                }, 200);
                
                swal("", "'" + tagText + "' 학습이 포모도르에 추가되었습니다.", {buttons: false, timer: 1500});
            }
        }
        
        // 포모도르 자동 저장
        function autoSavePomodoroPlans() {
            var formData = $("#pomodoroForm").serializeArray();
            var postData = {};
            
            // 폼 데이터를 변환
            formData.forEach(function(item) {
                if (item.name.startsWith('pomodoro_time')) {
                    var num = item.name.replace('pomodoro_time', '');
                    postData['time' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_plan')) {
                    var num = item.name.replace('pomodoro_plan', '');
                    postData['week' + num] = item.value;
                } else if (item.name.startsWith('pomodoro_url')) {
                    var num = item.name.replace('pomodoro_url', '');
                    postData['url' + num] = item.value;
                }
            });
            
            postData.studentid = studentid;
            postData.pid = dailyGoalId;

            $.ajax({
                url: "save_todayplan.php",
                type: "POST",
                data: postData,
                dataType: "json",
                success: function(response) {
                    console.log("자동 저장 완료");
                },
                error: function() {
                    console.log("자동 저장 실패");
                }
            });
        }
        
        // 주간계획 자동 저장
        function autoSaveWeeklyPlans() {
            console.log("주간계획 자동 저장 시작");
            var formData = $("#weeklyPlansForm").serialize();
            $.ajax({
                url: "save_weekly_goals.php",
                type: "POST",
                data: formData + "&studentid=" + studentid + "&pid=" + termPlanId,
                dataType: "json",
                success: function(response) {
                    console.log("주간계획 자동 저장 완료:", response);
                    showSaveStatus('success', '저장됨 ✓');
                },
                error: function() {
                    console.log("주간계획 자동 저장 실패");
                    showSaveStatus('error', '저장 실패');
                }
            });
        }
        
        // 분기목표 자동 저장
        function autoSaveTermGoal() {
            console.log("분기목표 자동 저장 시작");
            var goalText = document.getElementById('termGoalText').value;
            var dreamText = document.getElementById('dreamChallengeText').value;
            var dreamUrl = document.getElementById('dreamUrl').value;
            var deadline = document.getElementById('termDeadline').value;
            
            if (!goalText.trim()) {
                console.log("분기목표가 비어있어 자동 저장 취소");
                return;
            }
            
            $.ajax({
                url: "save_goals.php",
                type: "POST",
                data: {
                    studentid: studentid,
                    goal: goalText,
                    dreamchallenge: dreamText,
                    dreamurl: dreamUrl,
                    deadline: deadline
                },
                dataType: "json",
                success: function(response) {
                    console.log("분기목표 자동 저장 완료:", response);
                    showSaveStatus('success', '저장됨 ✓');
                },
                error: function() {
                    console.log("분기목표 자동 저장 실패");
                    showSaveStatus('error', '저장 실패');
                }
            });
        }
        
        // 주간목표 전체 자동 저장
        function autoSaveAllWeekGoals() {
            console.log("주간목표 전체 자동 저장 시작");
            var formData = $("#allWeeksForm").serialize();
            $.ajax({
                url: "save_all_week_goals.php",
                type: "POST",
                data: formData + "&studentid=" + studentid,
                dataType: "json",
                success: function(response) {
                    console.log("주간목표 전체 자동 저장 완료:", response);
                    showSaveStatus('success', '저장됨 ✓');
                },
                error: function() {
                    console.log("주간목표 전체 자동 저장 실패");
                    showSaveStatus('error', '저장 실패');
                }
            });
        }
        
        // 오늘 목표 자동 저장
        function autoSaveDailyGoal() {
            console.log("오늘 목표 자동 저장 시작");
            var goalText = document.getElementById('dailyGoalText').value;
            
            if (!goalText.trim()) {
                console.log("오늘 목표가 비어있어 자동 저장 취소");
                return;
            }
            
            $.ajax({
                url: "save_daily_goal.php",
                type: "POST",
                data: {
                    studentid: studentid,
                    goal: goalText,
                    pid: termPlanId
                },
                dataType: "json",
                success: function(response) {
                    console.log("오늘 목표 자동 저장 완료:", response);
                    showSaveStatus('success', '저장됨 ✓');
                },
                error: function() {
                    console.log("오늘 목표 자동 저장 실패");
                    showSaveStatus('error', '저장 실패');
                }
            });
        }
        
        // 자동 저장 이벤트 리스너 설정 함수
        function setupAutoSaveListeners() {
            console.log("자동 저장 이벤트 리스너 설정");
            
            // 포모도르 입력 필드 자동 저장
            $(document).on('input', 'input[name^="pomodoro_plan"]', function() {
                clearTimeout(window.pomodoroAutoSaveTimer);
                window.pomodoroAutoSaveTimer = setTimeout(function() {
                    autoSavePomodoroPlans();
                }, 500);
            });

            // 포모도르 시간 필드 자동 저장
            $(document).on('change', 'input[name^="pomodoro_time"]', function() {
                autoSavePomodoroPlans();
            });
            
            // 주간계획 입력 필드 자동 저장
            $(document).on('input', 'input[name^="week"]', function() {
                // name이 "week_"로 시작하는 것은 제외 (주간목표용)
                if (!this.name.startsWith('week_')) {
                    clearTimeout(window.weeklyAutoSaveTimer);
                    window.weeklyAutoSaveTimer = setTimeout(function() {
                        autoSaveWeeklyPlans();
                    }, 500);
                }
            });
            
            // 주간목표 입력 필드 자동 저장  
            $(document).on('input', 'input[name^="week_"]', function() {
                clearTimeout(window.weeklyGoalAutoSaveTimer);
                window.weeklyGoalAutoSaveTimer = setTimeout(function() {
                    autoSaveAllWeekGoals();
                }, 500);
            });
            
            // 분기목표 입력 필드 자동 저장
            $(document).on('input', '#termGoalText', function() {
                clearTimeout(window.termGoalAutoSaveTimer);
                window.termGoalAutoSaveTimer = setTimeout(function() {
                    autoSaveTermGoal();
                }, 1000);
            });
            
            // 꿈의 도전 입력 필드 자동 저장
            $(document).on('input', '#dreamChallengeText', function() {
                clearTimeout(window.dreamAutoSaveTimer);
                window.dreamAutoSaveTimer = setTimeout(function() {
                    autoSaveTermGoal();
                }, 1000);
            });
            
            // 꿈의 URL 입력 필드 자동 저장
            $(document).on('input', '#dreamUrl', function() {
                clearTimeout(window.dreamUrlAutoSaveTimer);
                window.dreamUrlAutoSaveTimer = setTimeout(function() {
                    autoSaveTermGoal();
                }, 1000);
            });
            
            // 분기 마감일 변경 시 자동 저장
            $(document).on('change', '#termDeadline', function() {
                autoSaveTermGoal();
            });
            
            // 오늘 목표 텍스트 자동 저장
            $(document).on('input', '#dailyGoalText', function() {
                clearTimeout(window.dailyGoalAutoSaveTimer);
                window.dailyGoalAutoSaveTimer = setTimeout(function() {
                    autoSaveDailyGoal();
                }, 500);
            });
            
            console.log("모든 자동 저장 이벤트 리스너 설정 완료");
        }
        
        // 섹션 상태 초기화
        function initializeSectionStates() {
            console.log("섹션 상태 초기화");
            
            // 초기 섹션 상태 설정 (포모도르 영역만 펼쳐짐)
            Object.keys(sectionStates).forEach(function(key) {
                var content = document.getElementById(key + 'Content');
                var toggle = document.getElementById(key + 'Toggle');
                if (content) {
                    if (sectionStates[key]) {
                        content.style.display = 'block';
                        content.classList.add('expanded');
                        content.classList.remove('collapsed');
                    } else {
                        content.style.display = 'none';
                        content.classList.add('collapsed');
                        content.classList.remove('expanded');
                    }
                }
                if (toggle) {
                    toggle.textContent = sectionStates[key] ? '▼' : '▶';
                    if (sectionStates[key]) {
                        toggle.classList.remove('rotated');
                    } else {
                        toggle.classList.add('rotated');
                    }
                }
            });
            
            // 주간계획 폼 초기화 (읽기 전용 모드)
            initializeWeeklyPlansForm();
            
            // 사이드 패널 키보드 단축키 (ESC로 닫기)
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidePanel();
                }
            });
            
            // 자동 저장 이벤트 리스너들 추가
            setupAutoSaveListeners();
            
            console.log("섹션 상태 초기화 완료");
        }
        
        // 자동 저장 리스너 설정
        function setupAutoSaveListeners() {
            console.log("자동 저장 리스너 설정 시작");
            
            // 목차에서 + 버튼 클릭 이벤트 (기존 코드에 저장 추가)
            $(document).on('click', '.insert-button', function() {
                var chapterTitle = $(this).data('chapter');
                var timeSlot = $(this).data('time');
                
                console.log("목차에서 활동 추가:", chapterTitle, timeSlot);
                
                // timelineData가 없으면 초기화
                if (!window.timelineData || !timelineData.activities) {
                    window.timelineData = {
                        totalHours: 6,
                        activities: [],
                        pixelsPerHour: 96,
                        currentDragItem: null,
                        startY: 0,
                        startTime: null
                    };
                }
                
                var newActivity = {
                    id: 'activity_' + Date.now(),
                    title: chapterTitle,
                    startTime: parseFloat(timeSlot),
                    duration: 0.5,
                    url: ''
                };
                
                timelineData.activities.push(newActivity);
                
                // 포모도르 섹션 표시
                var pomodoroSection = document.getElementById('pomodoroSection');
                if (pomodoroSection) {
                    pomodoroSection.style.display = 'block';
                }
                
                // 오늘 목표 섹션 펼치기
                if (!sectionStates.dailyGoal) {
                    toggleSection('dailyGoal');
                }
                
                // UI 업데이트
                drawTimeline();
                drawActivities();
                calculateTimeAverages();
                
                // 즉시 저장
                setTimeout(function() {
                    savePomodoroTimeline();
                }, 100);
                
                // 사이드 패널 닫기
                closeSidePanel();
            });
            
            // 포모도르 입력 필드 변경 시 자동 저장 (기존 방식과 호환)
            $(document).on('blur', 'input[name^="pomodoro_plan"], input[name^="pomodoro_time"]', function() {
                console.log("포모도르 입력 필드 변경 감지");
                setTimeout(function() {
                    savePomodoroTimeline();
                }, 500);
            });
            
            console.log("자동 저장 리스너 설정 완료");
        }
    </script>

    <!-- 사이드 패널 오버레이 -->
    <div id="sidePanelOverlay" class="side-panel-overlay" onclick="closeSidePanel()"></div>

    <!-- 사이드 패널 -->
    <?php if (!empty($chapterlist)): ?>
    <div id="sidePanel" class="side-panel">
        <div class="side-panel-header">
            <span>📚 학습 챕터</span>
            <button class="side-panel-close" onclick="closeSidePanel()">&times;</button>
        </div>
        <div class="side-panel-content">
            <?php echo $chapterlist; ?>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // 사이드 패널 관련 JavaScript 함수들
        function toggleChapterList() {
            document.getElementById('sidePanel').classList.add('open');
            document.getElementById('sidePanelOverlay').classList.add('show');
        }

        function closeSidePanel() {
            document.getElementById('sidePanel').classList.remove('open');
            document.getElementById('sidePanelOverlay').classList.remove('show');
        }

        $(document).ready(function() {
            // 기존 초기화 코드...
            
            // 포모도르 타임라인 항상 초기화
            console.log("페이지 로드 시 포모도르 타임라인 초기화 시작");
            initializePomodoroTimeline();
            
            // 주간계획 폼 초기화 (읽기 전용 모드)
            initializeWeeklyPlansForm();
            
            // 태그 시스템 초기화
            setupTagInput();
            loadTagsFromServer();

            // 사이드 패널 키보드 단축키 (ESC로 닫기)
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidePanel();
                }
            });
            
            // 오늘 목표 섹션을 기본으로 펼치기
            setTimeout(function() {
                if (!sectionStates.dailyGoal) {
                    toggleSection('dailyGoal');
                }
            }, 500);
            
            // 자동 저장 이벤트 리스너들 추가
            setupAutoSaveListeners();
        });
    </script>
</body>
</html> 