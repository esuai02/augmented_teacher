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
$username = $DB->get_record_sql("SELECT id, lastname, firstname, timezone FROM mdl_user WHERE id='$studentid'");
$studentname = $username->firstname.$username->lastname;
$tabtitle = "스케줄 - ".$username->lastname;
$timecreated = time();

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule_mobile','$timecreated')");
}

// Get schedule data
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 ORDER BY id DESC LIMIT 1");

// Day of week calculation
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);

// Calculate today's duration and total
$todayduration = 0;
$totalweek = 0;
if($schedule) {
    $totalweek = $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + $schedule->duration7;
    if($nday==1) $todayduration = $schedule->duration1;
    if($nday==2) $todayduration = $schedule->duration2;
    if($nday==3) $todayduration = $schedule->duration3;
    if($nday==4) $todayduration = $schedule->duration4;
    if($nday==5) $todayduration = $schedule->duration5;
    if($nday==6) $todayduration = $schedule->duration6;
    if($nday==0) $todayduration = $schedule->duration7;
}

// Get attendance records
$attendanceRecords = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND hide=0 ORDER BY timecreated DESC LIMIT 10");

// Day names
$dayNames = ['일', '월', '화', '수', '목', '금', '토'];
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-value { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 12px; opacity: 0.9; margin-top: 4px; }
        .schedule-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .schedule-row:last-child { border-bottom: none; }
        .schedule-day {
            font-weight: 600;
            color: #333;
            min-width: 40px;
        }
        .schedule-day.today {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
        }
        .schedule-time { color: #666; font-size: 14px; }
        .schedule-duration {
            font-weight: 600;
            color: #667eea;
        }
        .attendance-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .attendance-item:last-child { border-bottom: none; }
        .status-ok { color: #10b981; }
        .status-late { color: #f59e0b; }
        .status-absent { color: #ef4444; }
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
        .nav-item.active { color: #667eea; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        .empty-state {
            text-align: center;
            padding: 32px;
            color: #999;
        }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .stat-box {
                padding: 20px;
            }
            .stat-value { font-size: 28px; }
            .stat-label { font-size: 14px; }
            .header h1 { font-size: 20px; }
            .card { padding: 20px; }
            .card-title { font-size: 18px; margin-bottom: 16px; }
            .schedule-row { padding: 14px 0; }
            .schedule-day { font-size: 16px; min-width: 50px; }
            .schedule-time { font-size: 15px; }
            .schedule-duration { font-size: 16px; }
            .attendance-item { padding: 14px 0; font-size: 15px; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .stat-box { padding: 14px; }
            .stat-value { font-size: 22px; }
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
        <h1>📅 <?php echo $studentname; ?>님의 스케줄</h1>
    </div>
    
    <div class="content">
        <!-- 통계 -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $todayduration; ?>시간</div>
                <div class="stat-label">오늘 학습시간</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalweek; ?>시간</div>
                <div class="stat-label">주간 총 학습시간</div>
            </div>
        </div>
        
        <!-- 주간 시간표 -->
        <div class="card">
            <div class="card-title">📋 주간 시간표</div>
            <?php if($schedule): ?>
                <?php for($i = 1; $i <= 7; $i++): 
                    $dayNum = $i == 7 ? 0 : $i;
                    $isToday = $nday == $dayNum;
                    $startField = "start$i";
                    $durationField = "duration$i";
                    $startTime = $schedule->$startField == '12:00 AM' ? '-' : $schedule->$startField;
                    $duration = $schedule->$durationField == 0 ? '-' : $schedule->$durationField . '시간';
                ?>
                <div class="schedule-row">
                    <div class="schedule-day <?php echo $isToday ? 'today' : ''; ?>">
                        <?php echo $dayNames[$dayNum]; ?>
                    </div>
                    <div class="schedule-time"><?php echo $startTime; ?></div>
                    <div class="schedule-duration"><?php echo $duration; ?></div>
                </div>
                <?php endfor; ?>
            <?php else: ?>
                <div class="empty-state">등록된 시간표가 없습니다.</div>
            <?php endif; ?>
        </div>
        
        <!-- 최근 출결 -->
        <div class="card">
            <div class="card-title">✅ 최근 출결</div>
            <?php if(count($attendanceRecords) > 0): ?>
                <?php foreach($attendanceRecords as $record): ?>
                <div class="attendance-item">
                    <span><?php echo date('m/d', $record->timecreated); ?></span>
                    <span>
                        <?php if($record->status == 1): ?>
                            <span class="status-ok">정상</span>
                        <?php elseif($record->status == 2): ?>
                            <span class="status-late">지각</span>
                        <?php else: ?>
                            <span class="status-absent">결석</span>
                        <?php endif; ?>
                    </span>
                    <span><?php echo $record->type; ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">출결 기록이 없습니다.</div>
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
        <a href="schedule42m.php?id=<?php echo $studentid; ?>" class="nav-item active">
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

