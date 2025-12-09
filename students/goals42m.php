<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters - URL에 id가 없으면 로그인 사용자 정보 사용
$studentid = isset($_GET["id"]) && !empty($_GET["id"]) ? intval($_GET["id"]) : $USER->id;

// Check user role and permissions
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'"); 
$role = $userrole->data;

if($USER->id != $studentid && $role === 'student') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit;
}

// Get user data
$timecreated = time();
$username = $DB->get_record_sql("SELECT id, lastname, firstname FROM mdl_user WHERE id='$studentid'");
$studentname = $username->firstname.$username->lastname;
$tabtitle = "목표관리 - ".$username->lastname;

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','goals_mobile','$timecreated')");
}

// Get progress ID
$latestProgress = $DB->get_record_sql("SELECT id FROM mdl_abessi_progress WHERE userid='$studentid' AND hide=0 ORDER BY id DESC LIMIT 1");
$progressid = $latestProgress ? $latestProgress->id : 0;

// Get weekly plans
$weeklyPlansRecord = null;
$dailyGoals = array();
$weeklyGoals = array();

if($progressid > 0) {
    $weeklyPlansRecord = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid='$studentid' AND progressid='$progressid' ORDER BY id DESC LIMIT 1");
    
    if(!$weeklyPlansRecord) {
        $weeklyPlansRecord = $DB->get_record_sql("SELECT * FROM mdl_abessi_weeklyplans WHERE userid='$studentid' ORDER BY timemodified DESC LIMIT 1");
    }
    
    if($weeklyPlansRecord) {
        $dayNames = array(1 => '월', 2 => '화', 3 => '수', 4 => '목', 5 => '금', 6 => '토', 7 => '일');
        for($i = 1; $i <= 7; $i++) {
            $planField = 'plan' . $i;
            $planText = isset($weeklyPlansRecord->$planField) ? $weeklyPlansRecord->$planField : '';
            if(!empty($planText)) {
                $dailyGoals[] = array(
                    'day' => $i,
                    'dayname' => $dayNames[$i],
                    'plan' => $planText
                );
            }
        }
        
        for($i = 8; $i <= 16; $i++) {
            $planField = 'plan' . $i;
            $weekNumber = $i - 7;
            $planText = isset($weeklyPlansRecord->$planField) ? $weeklyPlansRecord->$planField : '';
            if(!empty($planText)) {
                $weeklyGoals[] = array(
                    'week' => $weekNumber,
                    'plan' => $planText
                );
            }
        }
    }
}

// Get today's goal
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type='오늘목표' ORDER BY id DESC LIMIT 1");

// Current day of week
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);
if($nday == 0) $nday = 7;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $tabtitle; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 16px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 { font-size: 18px; font-weight: 600; }
        .content { padding: 16px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .today-goal {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .today-goal-label { font-size: 12px; opacity: 0.9; margin-bottom: 8px; }
        .today-goal-text { font-size: 16px; font-weight: 600; line-height: 1.5; }
        .goal-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            gap: 12px;
        }
        .goal-item:last-child { border-bottom: none; }
        .goal-badge {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            min-width: 36px;
            text-align: center;
        }
        .goal-badge.weekly { background: #667eea; }
        .goal-badge.today { background: #f59e0b; }
        .goal-text {
            flex: 1;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        .empty-state {
            text-align: center;
            padding: 32px;
            color: #999;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 10px;
            padding: 4px 8px;
        }
        .nav-item.active { color: #10b981; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .today-goal {
                padding: 24px 20px;
            }
            .today-goal-label { font-size: 14px; }
            .today-goal-text { font-size: 18px; line-height: 1.6; }
            .header h1 { font-size: 20px; }
            .card { padding: 20px; }
            .card-title { font-size: 18px; margin-bottom: 16px; }
            .goal-item { padding: 16px 0; }
            .goal-badge { padding: 6px 12px; font-size: 14px; }
            .goal-text { font-size: 15px; line-height: 1.6; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .today-goal { padding: 14px; }
            .today-goal-text { font-size: 15px; }
            .content { padding: 12px; }
            .card { padding: 14px; margin-bottom: 12px; }
            .goal-item { padding: 10px 0; }
            body { padding-bottom: 60px; }
            .bottom-nav { padding: 6px 0; }
            .nav-item { font-size: 9px; padding: 2px 6px; }
            .nav-item span { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 <?php echo $studentname; ?>님의 목표</h1>
    </div>
    
    <div class="content">
        <!-- 오늘의 목표 -->
        <?php if($todayGoal): ?>
        <div class="today-goal">
            <div class="today-goal-label">🌟 오늘의 목표</div>
            <div class="today-goal-text"><?php echo htmlspecialchars($todayGoal->text); ?></div>
        </div>
        <?php endif; ?>
        
        <!-- 일별 목표 -->
        <div class="card">
            <div class="card-title">📅 일별 목표</div>
            <?php if(count($dailyGoals) > 0): ?>
                <?php foreach($dailyGoals as $goal): ?>
                <div class="goal-item">
                    <div class="goal-badge <?php echo $goal['day'] == $nday ? 'today' : ''; ?>">
                        <?php echo $goal['dayname']; ?>
                    </div>
                    <div class="goal-text"><?php echo htmlspecialchars($goal['plan']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">등록된 일별 목표가 없습니다.</div>
            <?php endif; ?>
        </div>
        
        <!-- 주간 목표 -->
        <div class="card">
            <div class="card-title">📊 주간 목표</div>
            <?php if(count($weeklyGoals) > 0): ?>
                <?php foreach($weeklyGoals as $goal): ?>
                <div class="goal-item">
                    <div class="goal-badge weekly"><?php echo $goal['week']; ?>주</div>
                    <div class="goal-text"><?php echo htmlspecialchars($goal['plan']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">등록된 주간 목표가 없습니다.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 하단 네비게이션 -->
    <div class="bottom-nav">
        <a href="index42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🏠</span>홈
        </a>
        <a href="today42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>📝</span>오늘
        </a>
        <a href="schedule42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>📅</span>일정
        </a>
        <a href="goals42m.php?id=<?php echo $studentid; ?>" class="nav-item active">
            <span>🎯</span>목표
        </a>
        <a href="../alt42/teachingsupport/student_inboxm.php?studentid=<?php echo $studentid; ?>" class="nav-item">
            <span>📩</span>메세지
        </a>
        <a href="../alt42/teachingsupport/AItutor/ui/math-persona-systemm.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🤖</span>AI
        </a>
    </div>
</body>
</html>

