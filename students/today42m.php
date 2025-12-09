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
$tabtitle = "오늘 - ".$username->lastname;

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','today_mobile','$timecreated')");
}

// Time variables
$adayAgo = time() - 43200; // 12 hours
$aweekAgo = time() - 604800;

// Get today's goal
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND (type='오늘목표' OR type='검사요청') ORDER BY id DESC LIMIT 1");

// Get schedule
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 ORDER BY id DESC LIMIT 1");

// Day of week
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);
if($nday == 0) $nday = 7;

// Calculate study time
$weektotal = 0;
$todayduration = 0;
if($schedule) {
    $weektotal = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + $schedule->duration7;
    $durationField = "duration" . $nday;
    $todayduration = $schedule->$durationField;
}

// Get recent quiz attempts
$quizattempts = $DB->get_records_sql("SELECT *, mdl_quiz_attempts.sumgrades AS sumgrades, mdl_quiz.sumgrades AS tgrades 
    FROM mdl_quiz LEFT JOIN mdl_quiz_attempts ON mdl_quiz.id=mdl_quiz_attempts.quiz  
    WHERE mdl_quiz_attempts.timemodified > '$adayAgo' AND mdl_quiz_attempts.userid='$studentid' 
    ORDER BY mdl_quiz_attempts.id DESC LIMIT 10");

// Get recent handwriting/whiteboard activities
$handwriting = $DB->get_records_sql("SELECT * FROM mdl_abessi_messages 
    WHERE userid='$studentid' AND status NOT LIKE 'attempt' AND tlaststroke>'$adayAgo' 
    AND contentstype=2 AND (active=1 OR status='flag') 
    ORDER BY tlaststroke DESC LIMIT 10");

$nquiz = count($quizattempts);
$nboard = count($handwriting);
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        .today-goal-box {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .today-goal-label { font-size: 12px; opacity: 0.9; margin-bottom: 8px; }
        .today-goal-text { font-size: 16px; font-weight: 600; line-height: 1.5; }
        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            flex: 1;
            background: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-icon { font-size: 24px; margin-bottom: 8px; }
        .stat-value { font-size: 20px; font-weight: 700; color: #333; }
        .stat-label { font-size: 11px; color: #666; margin-top: 4px; }
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            gap: 12px;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .activity-icon.quiz { background: #dbeafe; }
        .activity-icon.board { background: #dcfce7; }
        .activity-content { flex: 1; }
        .activity-title { font-size: 14px; font-weight: 500; color: #333; }
        .activity-meta { font-size: 12px; color: #666; margin-top: 2px; }
        .activity-score {
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .activity-score.good { background: #dcfce7; color: #16a34a; }
        .activity-score.warn { background: #fef3c7; color: #d97706; }
        .activity-score.bad { background: #fee2e2; color: #dc2626; }
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
        .nav-item.active { color: #f59e0b; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .today-goal-box {
                padding: 24px 20px;
            }
            .today-goal-label { font-size: 14px; }
            .today-goal-text { font-size: 18px; line-height: 1.6; }
            .stats-row {
                flex-direction: column;
                gap: 12px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-icon { font-size: 28px; }
            .stat-value { font-size: 24px; }
            .stat-label { font-size: 13px; }
            .header h1 { font-size: 20px; }
            .card { padding: 20px; }
            .card-title { font-size: 18px; margin-bottom: 16px; }
            .activity-item { padding: 14px 0; }
            .activity-title { font-size: 15px; }
            .activity-meta { font-size: 13px; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .today-goal-box { padding: 14px; }
            .today-goal-text { font-size: 15px; }
            .stats-row { flex-direction: row; gap: 10px; }
            .stat-card { padding: 12px; }
            .stat-icon { font-size: 20px; margin-bottom: 4px; }
            .stat-value { font-size: 18px; }
            .content { padding: 12px; }
            .card { padding: 14px; margin-bottom: 12px; }
            body { padding-bottom: 60px; }
            .bottom-nav { padding: 6px 0; }
            .nav-item { font-size: 9px; padding: 2px 6px; }
            .nav-item span { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📝 <?php echo $studentname; ?>님의 오늘</h1>
    </div>
    
    <div class="content">
        <!-- 오늘의 목표 -->
        <?php if($todayGoal): ?>
        <div class="today-goal-box">
            <div class="today-goal-label">🌟 오늘의 목표</div>
            <div class="today-goal-text"><?php echo htmlspecialchars($todayGoal->text); ?></div>
        </div>
        <?php endif; ?>
        
        <!-- 통계 요약 -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-value"><?php echo $todayduration; ?>시간</div>
                <div class="stat-label">오늘 학습시간</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo $nquiz; ?>개</div>
                <div class="stat-label">퀴즈 완료</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✏️</div>
                <div class="stat-value"><?php echo $nboard; ?>개</div>
                <div class="stat-label">오답노트</div>
            </div>
        </div>
        
        <!-- 최근 퀴즈 -->
        <div class="card">
            <div class="card-title">📋 최근 퀴즈 (12시간)</div>
            <?php if(count($quizattempts) > 0): ?>
                <?php foreach($quizattempts as $quiz): 
                    $quizgrade = $quiz->tgrades > 0 ? round($quiz->sumgrades / $quiz->tgrades * 100, 0) : 0;
                    $scoreClass = $quizgrade >= 80 ? 'good' : ($quizgrade >= 60 ? 'warn' : 'bad');
                    $quiztitle = mb_substr($quiz->name, 0, 20, 'UTF-8');
                ?>
                <div class="activity-item">
                    <div class="activity-icon quiz">📝</div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo htmlspecialchars($quiztitle); ?></div>
                        <div class="activity-meta"><?php echo date('H:i', $quiz->timestart); ?></div>
                    </div>
                    <div class="activity-score <?php echo $scoreClass; ?>"><?php echo $quizgrade; ?>점</div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">최근 퀴즈 기록이 없습니다.</div>
            <?php endif; ?>
        </div>
        
        <!-- 최근 오답노트 -->
        <div class="card">
            <div class="card-title">✏️ 최근 오답노트</div>
            <?php if(count($handwriting) > 0): ?>
                <?php foreach($handwriting as $hw): 
                    $hwtitle = mb_substr($hw->contentstitle ?: '오답노트', 0, 20, 'UTF-8');
                ?>
                <div class="activity-item">
                    <div class="activity-icon board">📓</div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo htmlspecialchars($hwtitle); ?></div>
                        <div class="activity-meta"><?php echo date('H:i', $hw->tlaststroke); ?> · <?php echo $hw->nstroke; ?>획</div>
                    </div>
                    <div class="activity-score good"><?php echo $hw->status; ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">최근 오답노트가 없습니다.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 하단 네비게이션 -->
    <div class="bottom-nav">
        <a href="index42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>🏠</span>홈
        </a>
        <a href="today42m.php?id=<?php echo $studentid; ?>" class="nav-item active">
            <span>📝</span>오늘
        </a>
        <a href="schedule42m.php?id=<?php echo $studentid; ?>" class="nav-item">
            <span>📅</span>일정
        </a>
        <a href="goals42m.php?id=<?php echo $studentid; ?>" class="nav-item">
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

