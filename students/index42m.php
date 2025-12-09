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
$tabtitle = $username->lastname . "의 공부방";

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','index_mobile','$timecreated')");
}

// Time variables
$trecent = time() - 31104000; // 1 year ago
$aweekago = time() - 604800;

// Get active missions
$activeMissions = $DB->get_records_sql("SELECT m.*, c.name as currname FROM mdl_abessi_mission m 
    LEFT JOIN mdl_abessi_curriculum c ON m.subject = c.id
    WHERE m.timecreated > '$trecent' AND m.userid='$studentid' AND m.complete = 0 
    ORDER BY m.norder ASC LIMIT 6");

// Get completed missions (recent)
$completedMissions = $DB->get_records_sql("SELECT m.*, c.name as currname FROM mdl_abessi_mission m 
    LEFT JOIN mdl_abessi_curriculum c ON m.subject = c.id
    WHERE m.timecreated > '$trecent' AND m.userid='$studentid' AND m.complete = 1 
    ORDER BY m.id DESC LIMIT 3");

// Get schedule
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid='$studentid' AND pinned=1 ORDER BY id DESC LIMIT 1");

// Calculate today's study time
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);
if($nday == 0) $nday = 7;

$todayduration = 0;
$weektotal = 0;
if($schedule) {
    $weektotal = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + $schedule->duration7;
    $durationField = "duration" . $nday;
    $todayduration = $schedule->$durationField;
}

// Get today's goal
$todayGoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$studentid' AND type='오늘목표' ORDER BY id DESC LIMIT 1");
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
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 20px 16px;
            text-align: center;
        }
        .header h1 { font-size: 20px; font-weight: 600; }
        .header-sub { font-size: 14px; opacity: 0.9; margin-top: 4px; }
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
        .quick-menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .quick-btn {
            background: white;
            border-radius: 12px;
            padding: 16px 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .quick-btn:active { transform: scale(0.95); }
        .quick-btn-icon { font-size: 28px; margin-bottom: 8px; }
        .quick-btn-label { font-size: 12px; font-weight: 500; }
        .stats-box {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-item {}
        .stat-value { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 12px; opacity: 0.9; margin-top: 4px; }
        .today-goal {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .today-goal-label { font-size: 12px; opacity: 0.9; }
        .today-goal-text { font-size: 15px; font-weight: 600; margin-top: 6px; }
        .mission-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        .mission-item:last-child { border-bottom: none; }
        .mission-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: #f3f4f6;
        }
        .mission-content { flex: 1; }
        .mission-title { font-size: 14px; font-weight: 500; color: #333; }
        .mission-meta { font-size: 12px; color: #666; margin-top: 2px; }
        .mission-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .mission-badge.active { background: #dbeafe; color: #2563eb; }
        .mission-badge.done { background: #dcfce7; color: #16a34a; }
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
        .nav-item.active { color: #8b5cf6; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .quick-menu {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .quick-btn {
                padding: 20px 12px;
            }
            .quick-btn-icon { font-size: 32px; margin-bottom: 10px; }
            .quick-btn-label { font-size: 13px; }
            .stats-box {
                flex-direction: column;
                gap: 16px;
                padding: 24px 20px;
            }
            .stat-value { font-size: 28px; }
            .stat-label { font-size: 13px; }
            .header h1 { font-size: 22px; }
            .header-sub { font-size: 15px; }
            .card { padding: 20px; }
            .card-title { font-size: 18px; }
            .mission-title { font-size: 15px; }
            .today-goal { padding: 20px; }
            .today-goal-text { font-size: 16px; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .quick-menu {
                grid-template-columns: repeat(6, 1fr);
                gap: 8px;
            }
            .quick-btn {
                padding: 12px 6px;
            }
            .quick-btn-icon { font-size: 24px; margin-bottom: 6px; }
            .quick-btn-label { font-size: 11px; }
            .stats-box {
                flex-direction: row;
                padding: 16px;
            }
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
        <h1>👩‍🎓 <?php echo $studentname; ?>님의 공부방</h1>
        <div class="header-sub"><?php echo date('Y년 m월 d일'); ?></div>
    </div>
    
    <div class="content">
        <!-- 빠른 메뉴 -->
        <div class="quick-menu">
            <a href="today42m.php?id=<?php echo $studentid; ?>" class="quick-btn">
                <div class="quick-btn-icon">📝</div>
                <div class="quick-btn-label">오늘</div>
            </a>
            <a href="schedule42m.php?id=<?php echo $studentid; ?>" class="quick-btn">
                <div class="quick-btn-icon">📅</div>
                <div class="quick-btn-label">일정</div>
            </a>
            <a href="goals42m.php?id=<?php echo $studentid; ?>" class="quick-btn">
                <div class="quick-btn-icon">🎯</div>
                <div class="quick-btn-label">목표</div>
            </a>
            <a href="../alt42/teachingsupport/student_inboxm.php?studentid=<?php echo $studentid; ?>" class="quick-btn">
                <div class="quick-btn-icon">📩</div>
                <div class="quick-btn-label">메세지</div>
            </a>
            <a href="../alt42/teachingsupport/AItutor/ui/math-persona-systemm.php?id=<?php echo $studentid; ?>" class="quick-btn">
                <div class="quick-btn-icon">🤖</div>
                <div class="quick-btn-label">AI튜터</div>
            </a>
            <a href="../alt42/orchestration/index.php" class="quick-btn">
                <div class="quick-btn-icon">🚀</div>
                <div class="quick-btn-label">학습시작</div>
            </a>
        </div>
        
        <!-- 학습 시간 통계 -->
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-value"><?php echo $todayduration; ?>시간</div>
                <div class="stat-label">오늘 학습</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $weektotal; ?>시간</div>
                <div class="stat-label">주간 총</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo count($activeMissions); ?>개</div>
                <div class="stat-label">진행 미션</div>
            </div>
        </div>
        
        <!-- 오늘의 목표 -->
        <?php if($todayGoal): ?>
        <div class="today-goal">
            <div class="today-goal-label">🌟 오늘의 목표</div>
            <div class="today-goal-text"><?php echo htmlspecialchars($todayGoal->text); ?></div>
        </div>
        <?php endif; ?>
        
        <!-- 진행 중인 미션 -->
        <div class="card">
            <div class="card-title">📚 진행 중인 미션</div>
            <?php if(count($activeMissions) > 0): ?>
                <?php foreach($activeMissions as $mission): 
                    $missionName = $mission->currname ?: '미션';
                    $missionName = str_replace(['개념 :', '심화 :', '내신 :', '수능 :'], '', $missionName);
                    $missionName = mb_substr(trim($missionName), 0, 25, 'UTF-8');
                ?>
                <a href="missionhome.php?id=<?php echo $studentid; ?>&cid=<?php echo $mission->subject; ?>" class="mission-item">
                    <div class="mission-icon">📖</div>
                    <div class="mission-content">
                        <div class="mission-title"><?php echo htmlspecialchars($missionName); ?></div>
                        <div class="mission-meta">합격: <?php echo $mission->grade; ?>점</div>
                    </div>
                    <div class="mission-badge active">진행중</div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">진행 중인 미션이 없습니다.</div>
            <?php endif; ?>
        </div>
        
        <!-- 완료된 미션 -->
        <?php if(count($completedMissions) > 0): ?>
        <div class="card">
            <div class="card-title">✅ 최근 완료 미션</div>
            <?php foreach($completedMissions as $mission): 
                $missionName = $mission->currname ?: '미션';
                $missionName = str_replace(['개념 :', '심화 :', '내신 :', '수능 :'], '', $missionName);
                $missionName = mb_substr(trim($missionName), 0, 25, 'UTF-8');
            ?>
            <div class="mission-item">
                <div class="mission-icon">✅</div>
                <div class="mission-content">
                    <div class="mission-title"><?php echo htmlspecialchars($missionName); ?></div>
                    <div class="mission-meta">합격: <?php echo $mission->grade; ?>점</div>
                </div>
                <div class="mission-badge done">완료</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 하단 네비게이션 -->
    <div class="bottom-nav">
        <a href="index42m.php?id=<?php echo $studentid; ?>" class="nav-item active">
            <span>🏠</span>홈
        </a>
        <a href="today42m.php?id=<?php echo $studentid; ?>" class="nav-item">
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

