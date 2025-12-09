<?php
header('Content-Type: text/html; charset=utf-8');

// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

// 학생 정보 가져오기
$userid = optional_param('userid', 0, PARAM_INT);
$studentid = $userid ? $userid : $USER->id;

// 학생 정보 조회
if ($userid && $userid != $USER->id) {
    $student = $DB->get_record('user', array('id' => $studentid));
    $studentName = $student ? $student->firstname . ' ' . $student->lastname : '학생';
} else {
    $studentName = $USER->firstname . ' ' . $USER->lastname;
}

// 시간 계산
$now = time();
$today_start = strtotime('today 00:00:00');
$halfdayago = $now - (12 * 60 * 60);
$wtimestart1 = strtotime('monday this week 00:00:00');

// 분기 목표 확인
$termplan = $DB->get_record_sql("SELECT id, deadline, memo, dreamchallenge, dreamtext, dreamurl 
                                  FROM mdl_abessi_progress 
                                  WHERE userid='$studentid' AND plantype='분기목표' AND hide=0 
                                  ORDER BY id DESC LIMIT 1");

$termMission = '';
$needTermPlan = false;
if ($termplan && $termplan->deadline > $now) {
    $termMission = $termplan->memo;
} else {
    $needTermPlan = true;
}

// 주간 목표 확인
$weeklyGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                   WHERE userid='$studentid' AND timecreated>'$wtimestart1' 
                                   AND type LIKE '주간목표' 
                                   ORDER BY id DESC LIMIT 1");

$weeklyMission = '';
$needWeeklyGoal = false;
if ($weeklyGoal) {
    $weeklyMission = $weeklyGoal->text;
} else {
    $needWeeklyGoal = true;
}

// 오늘 목표 확인
$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                  WHERE userid='$studentid' 
                                  AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
                                  AND timecreated>'$halfdayago' 
                                  ORDER BY id DESC LIMIT 1");

$todayMission = '';
$needTodayGoal = false;
if ($checkgoal) {
    $todayMission = $checkgoal->text;
} else {
    $needTodayGoal = true;
}

// 목표 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = optional_param('action', '', PARAM_TEXT);
    $goal_text = optional_param('goal_text', '', PARAM_TEXT);
    
    if ($action === 'save_term_goal') {
        // 분기 목표 저장
        $record = new stdClass();
        $record->userid = $studentid;
        $record->plantype = '분기목표';
        $record->memo = $goal_text;
        $record->deadline = time() + (90 * 24 * 60 * 60); // 90일 후
        $record->timecreated = time();
        $record->hide = 0;
        
        $DB->insert_record('abessi_progress', $record);
        echo json_encode(['success' => true]);
        exit;
    }
     
    if ($action === 'save_weekly_goal' || $action === 'save_today_goal') {
        // 주간/오늘 목표 저장
        $record = new stdClass();
        $record->userid = $studentid;
        $record->type = ($action === 'save_weekly_goal') ? '주간목표' : '오늘목표';
        $record->text = $goal_text;
        $record->timecreated = time();
        
        $DB->insert_record('abessi_today', $record);
        echo json_encode(['success' => true]);
        exit;
    }
}

// 시간 표시 함수
function getTimeAgo($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return '방금';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . '분 전';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . '시간 전';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . '일 전';
    } else {
        $weeks = floor($diff / 604800);
        return $weeks . '주 전';
    }
}

// 학생 고민 키워드 30개
$worryKeywords = [
    '시험불안', '성적압박', '시간부족', '집중력저하', '암기어려움',
    '문제이해부족', '계산실수', '개념혼동', '응용력부족', '자신감결여',
    '부모님기대', '친구비교', '선생님눈치', '진로고민', '공부방법',
    '체력부족', '수면부족', '스트레스', '미루기습관', '목표불명확',
    '동기부여부족', '외로움', '경쟁심리', '완벽주의', '실패두려움',
    '학원피로', '숙제부담', '시험공포', '성과압박', '미래불안'
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학학원 우주적 몰입 - <?php echo htmlspecialchars($studentName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', 'Arial', sans-serif;
            overflow: hidden;
            background: #000;
            color: #fff;
            position: relative;
            height: 100vh;
        }

        /* 우주 배경 */
        #cosmos {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, #0a0e27 0%, #000 100%);
            overflow: hidden;
        }

        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 4s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        /* 메인 컨테이너 */
        .container {
            position: relative;
            z-index: 10;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ===== 개선된 목표 설정 화면 ===== */
        .goal-setting {
            text-align: center;
            max-width: 900px;
            animation: fadeIn 1s forwards;
            width: 100%;
        }

        .goal-setting h1 {
            font-size: 2.2em;
            margin-bottom: 40px;
            color: #4a9eff;
            font-weight: 300;
            letter-spacing: 0.1em;
        }

        /* 통합된 목표 섹션 스타일 */
        .goal-section {
            margin: 25px 0;
            padding: 30px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(74, 158, 255, 0.2);
            border-radius: 15px;
            transition: all 0.4s ease;
            position: relative;
            min-height: 120px;
        }

        .goal-section:hover {
            border-color: rgba(74, 158, 255, 0.5);
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 158, 255, 0.15);
        }

        .goal-section.empty {
            border-color: rgba(255, 100, 100, 0.4);
            background: rgba(255, 50, 50, 0.05);
            border-style: dashed;
        }

        .goal-section.empty:hover {
            border-color: rgba(255, 150, 150, 0.6);
            background: rgba(255, 50, 50, 0.08);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .goal-section h2 {
            font-size: 1.6em;
            color: #4a9eff;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .goal-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #4a9eff, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
        }

        /* 인라인 편집 가능한 목표 내용 */
        .goal-content-editable {
            min-height: 80px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            cursor: text;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            color: #ddd;
            line-height: 1.6;
            font-size: 1.1em;
            text-align: left;
            display: flex;
            align-items: center;
            position: relative;
        }

        .goal-content-editable:hover {
            border-color: rgba(74, 158, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 15px rgba(74, 158, 255, 0.1);
        }

        .goal-content-editable.editing {
            display: none;
        }

        .empty-message {
            color: rgba(255, 150, 150, 0.7);
            font-style: italic;
            opacity: 0.8;
        }

        .empty-message::before {
            content: "✨ ";
            opacity: 0.5;
        }

        /* 인라인 텍스트 에리어 */
        .goal-input-inline {
            width: 100%;
            padding: 20px;
            border: 3px solid #4a9eff;
            border-radius: 12px;
            font-size: 1.1em;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            box-shadow: 0 0 20px rgba(74, 158, 255, 0.3);
            display: none;
        }

        .goal-input-inline:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 25px rgba(102, 126, 234, 0.4);
            background: rgba(0, 0, 0, 0.4);
        }

        .goal-input-inline::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* 타임스탬프 */
        .goal-timestamp {
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.4);
            opacity: 0.7;
        }

        /* 진행 상황 표시 */
        .progress-indicators {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 40px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .progress-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .progress-complete {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 222, 128, 0.3);
        }

        .progress-incomplete {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .progress-label {
            font-size: 0.85em;
            color: rgba(255, 255, 255, 0.6);
        }

        /* 계속하기 버튼 */
        .continue-btn {
            padding: 18px 40px;
            margin-top: 35px;
            background: linear-gradient(135deg, #4a9eff, #667eea);
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.3em;
            font-weight: 500;
            letter-spacing: 0.05em;
            box-shadow: 0 5px 20px rgba(74, 158, 255, 0.3);
        }

        .continue-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 158, 255, 0.4);
            background: linear-gradient(135deg, #667eea, #4a9eff);
        }

        .continue-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* 저장 알림 */
        .save-notification {
            position: fixed;
            top: 30px;
            right: 30px;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(74, 222, 128, 0.4);
            display: none;
            animation: slideIn 0.4s ease;
            z-index: 1000;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* 키워드 선택 화면 */
        .keyword-selection {
            display: none;
            text-align: center;
            max-width: 900px;
            animation: fadeIn 1s forwards;
            transition: opacity 1s ease;
        }

        .keyword-selection h2 {
            font-size: 2em;
            margin-bottom: 30px;
            color: #4a9eff;
        }

        .keyword-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }

        .keyword-btn {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #aaa;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            border-radius: 8px;
        }

        .keyword-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: scale(1.05);
        }

        .keyword-btn.selected {
            background: rgba(74, 158, 255, 0.3);
            border-color: #4a9eff;
            color: #4a9eff;
        }

        .selection-counter {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #4a9eff;
        }

        /* 초기 화면들 */
        .intro {
            display: none;
            text-align: center;
            transition: all 1s ease;
        }

        .intro h1 {
            font-size: 2.5em;
            margin-bottom: 30px;
            opacity: 0;
            animation: fadeIn 2s forwards;
            font-weight: 300;
            letter-spacing: 0.1em;
        }

        .intro p {
            font-size: 1.1em;
            opacity: 0;
            animation: fadeIn 2s 0.5s forwards;
            line-height: 1.8;
            margin-bottom: 15px;
            color: #aaa;
        }

        /* 몰입 단계들... (기존과 동일) */
        .immersion-phase {
            display: none;
            text-align: center;
            max-width: 800px;
        }

        .thought {
            font-size: 1.8em;
            margin: 35px 0;
            opacity: 0;
            transition: opacity 2s ease;
            line-height: 1.6;
            font-weight: 300;
        }

        .thought.visible {
            opacity: 1;
        }

        .thought.fading {
            opacity: 0.3;
            font-size: 1.4em;
            transition: all 3s ease;
        }

        /* 최종 몰입 상태 */
        .focus-state {
            display: none;
            text-align: center;
        }

        .problem-space {
            font-size: 3.5em;
            letter-spacing: 0.2em;
            margin-bottom: 50px;
            animation: pulse 4s infinite;
            position: relative;
            display: inline-block;
            padding: 60px 80px;
        }

        .problem-space::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120%;
            height: 200%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: pulse-border 4s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        @keyframes pulse-border {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.05); }
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        .start-btn {
            margin-top: 40px;
            padding: 20px 40px;
            font-size: 1.3em;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            cursor: pointer;
            transition: all 0.5s ease;
            opacity: 0;
            animation: fadeIn 2s 1s forwards;
            letter-spacing: 0.15em;
            border-radius: 8px;
        }

        .start-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
            transform: scale(1.05);
        }

        /* 블랙홀 효과들 */
        .blackhole-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            z-index: 100;
        }

        .blackhole {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle at center, #000 0%, transparent 70%);
            border-radius: 50%;
            box-shadow: 0 0 100px 50px rgba(0, 0, 0, 0.8);
        }

        .blackhole-swirl {
            position: absolute;
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: swirl 3s linear infinite;
        }

        @keyframes swirl {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes suck-in {
            to {
                transform: translate(calc(50vw - 50%), calc(50vh - 50%)) scale(0);
                opacity: 0;
            }
        }

        .sucked-element {
            animation: suck-in 2s ease-in forwards;
        }

        /* 잡념 제거 효과 */
        .distraction {
            position: absolute;
            padding: 8px 16px;
            background: rgba(255, 50, 50, 0.1);
            border: 1px solid rgba(255, 50, 50, 0.3);
            color: rgba(255, 120, 120, 0.9);
            font-size: 0.85em;
            animation: float-away 3s forwards;
            pointer-events: none;
            white-space: nowrap;
        }

        @keyframes float-away {
            0% { opacity: 1; transform: translateY(0) scale(1) rotate(0deg); }
            100% { opacity: 0; transform: translateY(-120px) scale(0.4) rotate(15deg); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        /* 기타 UI 요소들 */
        .student-name {
            position: absolute;
            top: 30px;
            left: 30px;
            font-size: 1em;
            color: #666;
            opacity: 0.7;
        }

        .home-btn {
            display: none;
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.5s ease;
            font-size: 0.95em;
            letter-spacing: 0.05em;
            font-weight: 300;
        }

        .home-btn:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .reset-btn {
            position: absolute;
            bottom: 30px;
            right: 30px;
            padding: 10px 20px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .reset-btn:hover {
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
        }

        /* D-day 표시 */
        .dday-timer {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 1.2em;
            opacity: 0.5;
            font-family: 'Courier New', monospace;
        }

        /* 환경 알림 */
        .environment-notice {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 0.8em;
            color: #666;
            opacity: 0;
            animation: fadeIn 2s forwards;
        }

        .academy-environment {
            position: absolute;
            bottom: 20px;
            left: 20px;
            font-size: 0.75em;
            color: #444;
            line-height: 1.5;
        }

        .academy-status {
            font-size: 0.9em;
            color: #666;
            margin-top: 20px;
            opacity: 0;
            animation: fadeIn 2s 1s forwards;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .goal-setting {
                max-width: 95%;
            }
            
            .goal-section {
                padding: 20px;
                margin: 20px 0;
            }
            
            .keyword-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .progress-indicators {
                gap: 15px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div id="cosmos"></div>
     
    <!-- 블랙홀 효과 -->
    <div class="blackhole-container" id="blackholeContainer">
        <div class="blackhole-swirl"></div>
        <div class="blackhole"></div>
    </div>
     
    <!-- 학생 이름 표시 -->
    <div class="student-name">
        <?php echo htmlspecialchars($studentName); ?>님의 몰입 공간
    </div>
    
    <div class="container">
        <!-- 개선된 목표 설정 화면 -->
        <div class="goal-setting" id="goalSetting">
            <h1>목표를 명확히 하는 시간</h1>
            
            <!-- 분기 목표 -->
            <div class="goal-section <?php echo $needTermPlan ? 'empty' : ''; ?>" id="termGoalSection">
                <div class="goal-header">
                    <h2>
                        <div class="goal-icon">분</div>
                        분기 목표
                    </h2>
                    <?php if ($termplan && $termplan->deadline > $now): ?>
                        <div class="goal-timestamp">
                            <span id="termGoalTime"><?php echo getTimeAgo($termplan->timecreated ?? time()); ?></span> 입력
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="goal-content-editable" onclick="startInlineEdit('term')" id="termGoalContent">
                    <?php if ($termMission): ?>
                        <?php echo htmlspecialchars($termMission); ?>
                    <?php else: ?>
                        <span class="empty-message">분기 목표를 클릭하여 입력하세요</span>
                    <?php endif; ?>
                </div>
                
                <textarea class="goal-input-inline" id="termGoalInput" 
                          placeholder="이번 분기에 달성하고 싶은 목표를 입력하세요..."
                          onblur="saveInlineGoal('term')"
                          onkeydown="handleInlineKeydown(event, 'term')"><?php echo $termMission; ?></textarea>
            </div>
            
            <!-- 주간 목표 -->
            <div class="goal-section <?php echo $needWeeklyGoal ? 'empty' : ''; ?>" id="weeklyGoalSection">
                <div class="goal-header">
                    <h2>
                        <div class="goal-icon">주</div>
                        주간 목표
                    </h2>
                    <?php if ($weeklyGoal): ?>
                        <div class="goal-timestamp">
                            <span id="weeklyGoalTime"><?php echo getTimeAgo($weeklyGoal->timecreated ?? time()); ?></span> 입력
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="goal-content-editable" onclick="startInlineEdit('weekly')" id="weeklyGoalContent">
                    <?php if ($weeklyMission): ?>
                        <?php echo htmlspecialchars($weeklyMission); ?>
                    <?php else: ?>
                        <span class="empty-message">주간 목표를 클릭하여 입력하세요</span>
                    <?php endif; ?>
                </div>
                
                <textarea class="goal-input-inline" id="weeklyGoalInput" 
                          placeholder="이번 주에 집중할 목표를 입력하세요..."
                          onblur="saveInlineGoal('weekly')"
                          onkeydown="handleInlineKeydown(event, 'weekly')"><?php echo $weeklyMission; ?></textarea>
            </div>
            
            <!-- 오늘 목표 -->
            <div class="goal-section <?php echo $needTodayGoal ? 'empty' : ''; ?>" id="todayGoalSection">
                <div class="goal-header">
                    <h2>
                        <div class="goal-icon">오</div>
                        오늘 목표
                    </h2>
                    <?php if ($checkgoal): ?>
                        <div class="goal-timestamp">
                            <span id="todayGoalTime"><?php echo getTimeAgo($checkgoal->timecreated ?? time()); ?></span> 입력
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="goal-content-editable" onclick="startInlineEdit('today')" id="todayGoalContent">
                    <?php if ($todayMission): ?>
                        <?php echo htmlspecialchars($todayMission); ?>
                    <?php else: ?>
                        <span class="empty-message">오늘 목표를 클릭하여 입력하세요</span>
                    <?php endif; ?>
                </div>
                
                <textarea class="goal-input-inline" id="todayGoalInput" 
                          placeholder="오늘 반드시 달성할 목표를 입력하세요..."
                          onblur="saveInlineGoal('today')"
                          onkeydown="handleInlineKeydown(event, 'today')"><?php echo $todayMission; ?></textarea>
            </div>
            
            <!-- 진행 상황 표시 -->
            <div class="progress-indicators">
                <div class="progress-item">
                    <div class="progress-circle <?php echo ($termplan && $termplan->deadline > $now) ? 'progress-complete' : 'progress-incomplete'; ?>" id="termProgress">
                        <?php echo ($termplan && $termplan->deadline > $now) ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">분기 목표</div>
                </div>
                <div class="progress-item">
                    <div class="progress-circle <?php echo $weeklyGoal ? 'progress-complete' : 'progress-incomplete'; ?>" id="weeklyProgress">
                        <?php echo $weeklyGoal ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">주간 목표</div>
                </div>
                <div class="progress-item">
                    <div class="progress-circle <?php echo $checkgoal ? 'progress-complete' : 'progress-incomplete'; ?>" id="todayProgress">
                        <?php echo $checkgoal ? '✓' : '○'; ?>
                    </div>
                    <div class="progress-label">오늘 목표</div>
                </div>
            </div>
            
            <!-- 계속하기 버튼 -->
            <button class="continue-btn" id="continueBtn" onclick="proceedToNext()" 
                    <?php if ($needTermPlan || $needWeeklyGoal || $needTodayGoal): ?>disabled<?php endif; ?>>
                계속하기
            </button>
        </div>

        <!-- 키워드 선택 화면 -->
        <div class="keyword-selection" id="keywordSelection">
            <h2>지금 나를 방해하는 것들을 선택하세요 (3개)</h2>
            <div class="selection-counter">
                <span id="selectedCount">0</span> / 3 선택됨
            </div>
            <div class="keyword-grid">
                <?php foreach ($worryKeywords as $keyword): ?>
                    <button class="keyword-btn" onclick="toggleKeyword(this, '<?php echo $keyword; ?>')">
                        <?php echo $keyword; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <p style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); font-size: 0.75em; color: rgba(255, 255, 255, 0.3); text-align: center;">
                키워드들은 당신의 활동 정보와 기록들을 활용하여 제시됩니다.
            </p>
        </div>

        <!-- 초기 화면 -->
        <div class="intro" id="intro">
            <h1>작업기억의 이중생활을 끝낼 시간</h1>
            <p>너의 뇌는 지금 수많은 세계에서 살고 있다</p>
            <p>문제를 푸는 너와, <span id="distraction-preview"></span>에 시달리는 너</p>
            <p>이제 하나가 될 시간이다</p>
            <div class="academy-status" id="academyStatus"></div>
            <button class="start-btn" onclick="startImmersion()">수학문제 속으로 GO !</button>
        </div>

        <!-- 몰입 유도 단계 -->
        <div class="immersion-phase" id="immersionPhase">
            <div class="thought" id="thought1">지금 이 순간</div>
            <div class="thought" id="thought2">다른 모든 것은 사라진다</div>
            <div class="thought" id="thought3"></div>
            <div class="thought" id="thought4"></div>
            <div class="thought" id="thought5">오직 너와 문제만이 존재한다</div>
        </div>

        <!-- 최종 몰입 상태 -->
        <div class="focus-state" id="focusState">
            <div class="problem-space">너 = 수학문제</div>
            <button class="home-btn" id="homeBtn" onclick="goToStudentHome()">시작하기</button>
        </div>

        <!-- D-day 표시 -->
        <div class="dday-timer" id="ddayTimer" style="display: none;"></div>

        <!-- 환경 표시 -->
        <div class="academy-environment" id="environmentInfo" style="display: none;"></div>

        <!-- 리셋 버튼 -->
        <button class="reset-btn" onclick="resetUniverse()" style="display: none;">우주 리셋</button>
    </div>

    <!-- 저장 알림 -->
    <div class="save-notification" id="saveNotification">
        ✓ 목표가 저장되었습니다!
    </div>

    <script>
        // PHP에서 전달된 학생 정보
        const studentName = <?php echo json_encode($studentName); ?>;
        const studentId = <?php echo $studentid; ?>;
        
        // 선택된 키워드 저장
        let selectedKeywords = [];
        let currentEditingType = null;
        let completedGoals = {
            term: <?php echo ($termplan && $termplan->deadline > $now) ? 'true' : 'false'; ?>,
            weekly: <?php echo $weeklyGoal ? 'true' : 'false'; ?>,
            today: <?php echo $checkgoal ? 'true' : 'false'; ?>
        };

        // ===== 인라인 편집 기능 =====
        
        // 인라인 편집 시작
        function startInlineEdit(type) {
            // 다른 편집중인 요소가 있다면 저장
            if (currentEditingType && currentEditingType !== type) {
                saveInlineGoal(currentEditingType);
            }
            
            currentEditingType = type;
            
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            
            // 현재 텍스트를 textarea에 설정 (empty-message 제외)
            const emptyMsg = contentDiv.querySelector('.empty-message');
            const currentText = emptyMsg ? '' : contentDiv.textContent.trim();
            textArea.value = currentText;
            
            // 요소 전환 - 부드러운 애니메이션
            contentDiv.style.opacity = '0';
            setTimeout(() => {
                contentDiv.style.display = 'none';
                textArea.style.display = 'block';
                textArea.focus();
                textArea.select();
                
                // 텍스트에리어 페이드 인
                setTimeout(() => {
                    textArea.style.opacity = '1';
                }, 50);
            }, 200);
        }
        
        // 키보드 이벤트 처리
        function handleInlineKeydown(event, type) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                saveInlineGoal(type);
            } else if (event.key === 'Escape') {
                cancelInlineEdit(type);
            }
        }
        
        // 인라인 편집 취소
        function cancelInlineEdit(type) {
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            
            textArea.style.display = 'none';
            contentDiv.style.display = 'flex';
            contentDiv.style.opacity = '1';
            
            currentEditingType = null;
        }
        
        // 인라인 목표 저장
        function saveInlineGoal(type) {
            const textArea = document.getElementById(type + 'GoalInput');
            const contentDiv = document.getElementById(type + 'GoalContent');
            const goalText = textArea.value.trim();
            
            // 빈 값이면 취소
            if (!goalText) {
                cancelInlineEdit(type);
                return;
            }
            
            // AJAX로 저장
            const formData = new FormData();
            formData.append('action', 'save_' + type + '_goal');
            formData.append('goal_text', goalText);
            formData.append('userid', studentId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공시 UI 업데이트
                    updateGoalContent(type, goalText);
                    updateProgressIndicator(type, true);
                    showNotification();
                    
                    // 완료 상태 업데이트
                    completedGoals[type] = true;
                    checkAllGoalsComplete();
                    
                } else {
                    alert('저장 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('저장 중 오류가 발생했습니다.');
            });
        }
        
        // 목표 내용 업데이트
        function updateGoalContent(type, goalText) {
            const contentDiv = document.getElementById(type + 'GoalContent');
            const textArea = document.getElementById(type + 'GoalInput');
            const timestampSpan = document.getElementById(type + 'GoalTime');
            const section = document.getElementById(type + 'GoalSection');
            
            // 내용 업데이트
            contentDiv.innerHTML = goalText;
            
            // 섹션 스타일 업데이트 (empty 클래스 제거)
            section.classList.remove('empty');
            
            // 타임스탬프 업데이트
            if (timestampSpan) {
                timestampSpan.textContent = '방금';
            } else {
                // 타임스탬프 생성
                const goalHeader = section.querySelector('.goal-header');
                const timestampDiv = document.createElement('div');
                timestampDiv.className = 'goal-timestamp';
                timestampDiv.innerHTML = '<span id="' + type + 'GoalTime">방금</span> 입력';
                goalHeader.appendChild(timestampDiv);
            }
            
            // 요소 전환 - 부드러운 애니메이션
            textArea.style.display = 'none';
            contentDiv.style.display = 'flex';
            contentDiv.style.opacity = '1';
            
            currentEditingType = null;
        }
        
        // 진행 표시기 업데이트
        function updateProgressIndicator(type, completed) {
            const progressCircle = document.getElementById(type + 'Progress');
            if (progressCircle) {
                if (completed) {
                    progressCircle.classList.remove('progress-incomplete');
                    progressCircle.classList.add('progress-complete');
                    progressCircle.textContent = '✓';
                } else {
                    progressCircle.classList.remove('progress-complete');
                    progressCircle.classList.add('progress-incomplete');
                    progressCircle.textContent = '○';
                }
            }
        }
        
        // 모든 목표 완료 체크
        function checkAllGoalsComplete() {
            const allComplete = completedGoals.term && completedGoals.weekly && completedGoals.today;
            const continueBtn = document.getElementById('continueBtn');
            
            if (allComplete) {
                continueBtn.disabled = false;
                continueBtn.style.opacity = '1';
                continueBtn.style.cursor = 'pointer';
                
                // 자동으로 5초 후 다음 단계로 진행
                setTimeout(() => {
                    if (document.getElementById('goalSetting').style.display !== 'none') {
                        // 버튼에 자동 진행 알림 표시
                        const originalText = continueBtn.textContent;
                        continueBtn.textContent = '자동으로 계속합니다... (3)';
                        
                        let countdown = 3;
                        const countdownInterval = setInterval(() => {
                            countdown--;
                            if (countdown > 0) {
                                continueBtn.textContent = `자동으로 계속합니다... (${countdown})`;
                            } else {
                                clearInterval(countdownInterval);
                                continueBtn.textContent = originalText;
                                proceedToNext();
                            }
                        }, 1000);
                    }
                }, 2000);
            }
        }
        
        // 다음 단계로 진행
        function proceedToNext() {
            if (!completedGoals.term || !completedGoals.weekly || !completedGoals.today) {
                alert('모든 목표를 입력해주세요.');
                return;
            }
            
            // 목표 설정 화면 숨기고 키워드 선택 화면 표시
            const goalSetting = document.getElementById('goalSetting');
            const keywordSelection = document.getElementById('keywordSelection');
            
            goalSetting.style.opacity = '0';
            setTimeout(() => {
                goalSetting.style.display = 'none';
                keywordSelection.style.display = 'block';
                keywordSelection.style.opacity = '1';
            }, 500);
        }
        
        function showNotification() {
            const notification = document.getElementById('saveNotification');
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // ===== 기존 키워드 선택 및 몰입 기능들 =====
        
        // 키워드 토글
        function toggleKeyword(btn, keyword) {
            if (btn.classList.contains('selected')) {
                btn.classList.remove('selected');
                selectedKeywords = selectedKeywords.filter(k => k !== keyword);
            } else {
                if (selectedKeywords.length < 3) {
                    btn.classList.add('selected');
                    selectedKeywords.push(keyword);
                }
            }
            
            document.getElementById('selectedCount').textContent = selectedKeywords.length;
            
            // 3개 선택되면 자동으로 다음 단계로
            if (selectedKeywords.length === 3) {
                // 모든 버튼 비활성화
                document.querySelectorAll('.keyword-btn').forEach(b => b.disabled = true);
                
                // 전환 효과 시작
                const transitionOverlay = document.createElement('div');
                transitionOverlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: radial-gradient(circle at center, rgba(138, 43, 226, 0.3), rgba(0, 0, 0, 0.9));
                    opacity: 0;
                    transition: opacity 0.8s ease;
                    z-index: 9999;
                    pointer-events: none;
                `;
                document.body.appendChild(transitionOverlay);
                
                // 선택 완료 메시지
                const completeMessage = document.createElement('div');
                completeMessage.style.cssText = `
                    position: fixed;
                    top: 75%;
                    left: 50%;
                    transform: translate(-50%, -50%) scale(0.8);
                    color: white;
                    font-size: 1.5em;
                    opacity: 0;
                    transition: all 0.5s ease;
                    z-index: 10000;
                    text-align: center;
                    text-shadow: 0 0 20px rgba(138, 43, 226, 0.8);
                `;
                completeMessage.textContent = '준비 완료';
                document.body.appendChild(completeMessage);
                
                // 애니메이션 시작
                setTimeout(() => {
                    transitionOverlay.style.opacity = '1';
                    completeMessage.style.opacity = '1';
                    completeMessage.style.transform = 'translate(-50%, -50%) scale(1)';
                }, 50);
                
                // 1.5초 후 전환
                setTimeout(() => {
                    startWithKeywords();
                    // 오버레이 제거
                    setTimeout(() => {
                        transitionOverlay.remove();
                        completeMessage.remove();
                    }, 1000);
                }, 1500);
            }
        }
        
        // 선택된 키워드로 몰입 시작
        function startWithKeywords() {
            if (selectedKeywords.length !== 3) {
                return;
            }
            
            // 페이드 아웃 효과
            const keywordSelection = document.getElementById('keywordSelection');
            keywordSelection.style.opacity = '0';
            
            setTimeout(() => {
                keywordSelection.style.display = 'none';
                document.getElementById('intro').style.display = 'block';
                
                // 선택된 키워드를 기반으로 환경 설정
                setupEnvironmentWithKeywords();
            }, 1000);
        }
        
        // 선택된 키워드로 환경 설정
        function setupEnvironmentWithKeywords() {
            // 기본 키워드 + 선택된 키워드 조합
            physicalEnv = ['교실온도 28도', '환기 안됨', '책상 너무 낮음', '의자 딱딱함', '형광등 깜박임'];
            distractions = ['옆자리 필기소음', '복도 발걸음', '스마트폰 진동', ...selectedKeywords];
            mentalPressure = ['틀릴까봐 무서움', '내신 3등급 위기', '모의고사 D-7', ...selectedKeywords];
            physicalDiscomfort = ['허리 아픔', '목 결림', '손목 시큰', '배고픔'];
            academySpecific = ['설명 너무 빠름', '질문하기 눈치', '오답노트 밀림', ...selectedKeywords];
            
            // 초기화 환경 호출
            initializeEnvironment();
        }

        // 학원 환경 키워드 (기본값)
        let physicalEnv = ['교실온도 28도', '환기 안됨', '책상 너무 낮음', '의자 딱딱함', '형광등 깜박임', '교실 곰팡이 냄새', '에어컨 소음'];
        let distractions = ['옆자리 필기소음', '복도 발걸음', '스마트폰 진동', 'SNS 알림 궁금', '친구가 쳐다봄', '선생님 시선', '카톡 울림'];
        let mentalPressure = ['틀릴까봐 무서움', '내신 3등급 위기', '모의고사 D-7', '경쟁자들 시선', '부모님 실망', '다음 시험 걱정', '진도 못따라감'];
        let physicalDiscomfort = ['허리 아픔', '목 결림', '손목 시큰', '배고픔', '화장실 급함', '졸음', '갈증'];
        let academySpecific = ['설명 너무 빠름', '질문하기 눈치', '오답노트 밀림', '숙제 3개 남음', '학원버스 10분 후', '야자 남음', '개념구멍 발견'];
        
        // 랜덤 선택 함수
        function getRandomFrom(arr) {
            return arr[Math.floor(Math.random() * arr.length)];
        }

        // 별 생성
        function createStars() {
            const cosmos = document.getElementById('cosmos');
            for (let i = 0; i < 200; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.width = Math.random() * 3 + 'px';
                star.style.height = star.style.width;
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 4 + 's';
                cosmos.appendChild(star);
            }
        }

        // 잡념 생성 및 제거
        function createDistraction(text, x, y) {
            const distraction = document.createElement('div');
            distraction.className = 'distraction';
            distraction.textContent = text;
            distraction.style.left = x + 'px';
            distraction.style.top = y + 'px';
            document.body.appendChild(distraction);
            
            setTimeout(() => distraction.remove(), 3000);
        }

        // 초기 설정
        function initializeEnvironment() {
            // 미리보기 잡념
            document.getElementById('distraction-preview').textContent = getRandomFrom([...distractions, ...mentalPressure]);
            
            // 학원 상태
            const status = [
                `${getRandomFrom(physicalEnv)}`,
                `${getRandomFrom(academySpecific)}`,
                `시험 D-${Math.floor(Math.random() * 30 + 1)}`
            ];
            document.getElementById('academyStatus').innerHTML = status.join(' | ');
            
            // 생각 3, 4 설정
            document.getElementById('thought3').innerHTML = 
                `${getRandomFrom(mentalPressure)}?<br><span style="font-size: 0.6em; opacity: 0.5;">...중요하지 않아</span>`;
            document.getElementById('thought4').innerHTML = 
                `${getRandomFrom(physicalDiscomfort)}?<br><span style="font-size: 0.6em; opacity: 0.5;">...존재하지 않아</span>`;
                
            // D-day 설정
            document.getElementById('ddayTimer').textContent = `D-${Math.floor(Math.random() * 30 + 1)}`;
        }

        // 몰입 시작
        function startImmersion() {
            document.getElementById('intro').style.display = 'none';
            document.getElementById('immersionPhase').style.display = 'block';
            
            // 순차적으로 생각 표시
            const thoughts = ['thought1', 'thought2', 'thought3', 'thought4', 'thought5'];
            thoughts.forEach((id, index) => {
                setTimeout(() => {
                    const thought = document.getElementById(id);
                    thought.classList.add('visible');
                    
                    // 이전 생각들 페이드
                    if (index > 0) {
                        document.getElementById(thoughts[index - 1]).classList.add('fading');
                    }
                    
                    // 잡념 효과 (매 단계마다 다른 잡념)
                    if (index >= 1 && index <= 3) {
                        setTimeout(() => {
                            // 다양한 카테고리에서 랜덤 선택
                            const allDistractions = [
                                ...distractions,
                                ...mentalPressure,
                                ...physicalDiscomfort,
                                ...academySpecific,
                                ...physicalEnv
                            ];
                            
                            for (let i = 0; i < 2 + index; i++) {
                                setTimeout(() => {
                                    createDistraction(
                                        getRandomFrom(allDistractions),
                                        Math.random() * (window.innerWidth - 200) + 100,
                                        Math.random() * (window.innerHeight - 200) + 100
                                    );
                                }, i * 500);
                            }
                        }, 1000);
                    }
                }, index * 3000);
            });
            
            // 블랙홀 효과 시작 (15초 후)
            setTimeout(() => {
                // 모든 요소에 빨려들어가는 효과 추가
                document.querySelectorAll('.thought').forEach(el => {
                    el.classList.add('sucked-element');
                });
                
                // 떠다니는 잡념들도 빨려들어가게
                document.querySelectorAll('.distraction').forEach(el => {
                    el.style.animation = 'suck-in 2s ease-in forwards';
                });
                
                // 별들도 빨려들어가게
                document.querySelectorAll('.star').forEach((star, index) => {
                    setTimeout(() => {
                        star.style.animation = 'suck-in 2.5s ease-in forwards';
                    }, index * 10);
                });
                
                // 블랙홀 표시
                document.getElementById('blackholeContainer').style.display = 'block';
                
                // 블랙홀 소리 효과를 위한 시각적 진동
                document.body.style.animation = 'shake 0.5s infinite';
            }, 15000);
            
            // 최종 상태로 전환 (18초 후)
            setTimeout(() => {
                document.getElementById('blackholeContainer').style.display = 'none';
                document.body.style.animation = 'none';
                document.getElementById('immersionPhase').style.display = 'none';
                document.getElementById('focusState').style.display = 'block';
                document.getElementById('ddayTimer').style.display = 'block';
                document.querySelector('.reset-btn').style.display = 'block';
                
                // 환경 정보 표시
                const envInfo = [
                    `현재: ${getRandomFrom(physicalEnv)}`,
                    `상태: 완전 몰입`,
                    `잡념: 0`
                ];
                document.getElementById('environmentInfo').innerHTML = envInfo.join('<br>');
                document.getElementById('environmentInfo').style.display = 'block';
                
                // 배경 어두워짐
                document.getElementById('cosmos').style.background = 'radial-gradient(ellipse at center, #050510 0%, #000 100%)';
                
                // 몰입 상태 로그 (선택적)
                logImmersionState();
                
                // 5초 후 홈 버튼 표시
                setTimeout(() => {
                    document.getElementById('homeBtn').style.display = 'block';
                }, 5000);
            }, 18000);
        }

        // 몰입 상태 로그 기록 (선택적)
        function logImmersionState() {
            // AJAX를 통해 서버에 몰입 상태 기록
            const data = new FormData();
            data.append('student_id', studentId);
            data.append('action', 'immersion_complete');
            data.append('timestamp', new Date().toISOString());
            data.append('keywords', selectedKeywords.join(','));
            
            fetch('log_immersion.php', {
                method: 'POST',
                body: data
            }).catch(err => console.log('로그 기록 실패:', err));
        }
        
        // 학생 홈으로 이동
        function goToStudentHome() {
            // 알림 메시지 컨테이너 생성
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 65%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 30px 60px;
                border-radius: 10px;
                font-size: 1.2em;
                opacity: 0;
                transition: opacity 0.5s ease;
                z-index: 10000;
                text-align: center;
                box-shadow: 0 4px 20px rgba(255, 255, 255, 0.2);
            `;
            notification.textContent = '선생님에게 당신의 시작을 알립니다.';
            document.body.appendChild(notification);
            
            // 페이드 인 효과
            setTimeout(() => {
                notification.style.opacity = '1'; 
            }, 10);
            
            // 2초 후 페이지 이동
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    window.location.href = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=' + studentId;
                }, 500);
            }, 2000);
        }

        // 우주 리셋
        function resetUniverse() {
            // 화면 플래시 효과
            document.body.style.transition = 'opacity 0.5s';
            document.body.style.opacity = '0';
            
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        // 초기화
        createStars();
        
        // 페이지 떠날 때 편집중인 내용 저장
        window.addEventListener('beforeunload', function() {
            if (currentEditingType) {
                saveInlineGoal(currentEditingType);
            }
        });

        // 마우스 움직임 감지 (몰입 상태에서 잡념 생성)
        let lastMouseMove = Date.now();
        document.addEventListener('mousemove', (e) => {
            if (document.getElementById('focusState').style.display === 'block') {
                if (Date.now() - lastMouseMove > 3000) {
                    const randomDistraction = getRandomFrom([
                        '집중!',
                        '문제만 봐',
                        '다른 생각 금지',
                        '너=수학문제',
                        ...distractions.map(d => `${d} (무시)`)
                    ]);
                    createDistraction(randomDistraction, e.clientX, e.clientY);
                }
                lastMouseMove = Date.now();
            }
        });

        // 주기적인 환경 잡념 (몰입 전)
        setInterval(() => {
            if (document.getElementById('immersionPhase').style.display === 'block') {
                const randomEnv = getRandomFrom([...physicalEnv, ...academySpecific]);
                createDistraction(
                    randomEnv,
                    Math.random() * window.innerWidth,
                    Math.random() * window.innerHeight
                );
            }
        }, 4000);

        // 초기 목표 완료 상태 체크
        checkAllGoalsComplete();
    </script>
</body>
</html>