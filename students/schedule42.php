<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// Get parameters
$studentid = $_GET["id"]; 
$nedit = $_GET['eid'] ?? 1;
$nprev = $nedit + 1;
$nnext = $nedit - 1;
$nweek = $_GET["nweek"] ?? 12;  
$mode = $_GET["mode"] ?? '';  
 
if($studentid == NULL) $studentid = $USER->id;

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

// Time variables
$timecreated = time();

// Log access for students
if($role === 'student') {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) VALUES('$studentid','studentschedule','$timecreated')");
}

// Get schedule data
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule where userid='$studentid' AND pinned=1 ORDER BY id DESC LIMIT 1");

// Day of week calculation
$jd = cal_to_jd(CAL_GREGORIAN,date("m"),date("d"),date("Y"));
$nday = jddayofweek($jd, 0);

// Calculate today's duration and total
$todayduration = 0;
$untiltoday = 0;
if($schedule) {
    if($nday==1){$untiltoday=$schedule->duration1; $todayduration=$schedule->duration1;}
    if($nday==2){$untiltoday=$schedule->duration1+$schedule->duration2;$todayduration=$schedule->duration2;}
    if($nday==3){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3;$todayduration=$schedule->duration3;}
    if($nday==4){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;$todayduration=$schedule->duration4;}
    if($nday==5){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;$todayduration=$schedule->duration5;}
    if($nday==6){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;$todayduration=$schedule->duration6;}
    if($nday==0){$untiltoday=$schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;$todayduration=$schedule->duration7;}
}

// Timezone handling
date_default_timezone_set('Asia/Seoul');
$timezone = $username->timezone;
$timezone_str = '';

if (!is_numeric($timezone)) {
    date_default_timezone_set($timezone);
    $seoulTZ = new DateTimeZone('Asia/Seoul');
    $userTZ = new DateTimeZone($timezone);
    $now = new DateTime("now", $seoulTZ);
    $offsetSeoul = $seoulTZ->getOffset($now);
    $offsetUser = $userTZ->getOffset($now);
    $timeDifferenceSeconds = $offsetUser - $offsetSeoul;
    $absDiff = abs($timeDifferenceSeconds);
    $hours = floor($absDiff / 3600);
    $minutes = floor(($absDiff % 3600) / 60);
    
    if ($timeDifferenceSeconds == 0) {
        $timeDifferenceStr = "0시간";
    } else {
        $timeDifferenceStr = ($hours > 0 ? $hours . '시간 ' : '') . ($minutes > 0 ? $minutes . '분' : '');
    }
    $timezone_str = $timezone . ' 시차 서울시간 + (' . $timeDifferenceStr.')';
} else {
    $timezone_str = 'Asia/Seoul 시차 0 시간';
}

// Get attendance records
$attendanceRecords = $DB->get_records_sql("SELECT * FROM mdl_abessi_attendance WHERE userid='$studentid' AND hide=0 ORDER BY timecreated DESC LIMIT 20");

// Process schedule data if exists
$scheduleData = [];
if($schedule) {
    $scheduleData = [
        'type' => $schedule->type,
        'total' => $schedule->duration1 + $schedule->duration2 + $schedule->duration3 + $schedule->duration4 + $schedule->duration5 + $schedule->duration6 + $schedule->duration7,
        'edittime' => date('m/d', $schedule->timecreated),
        'memo8' => $schedule->memo8,
        'memo9' => $schedule->memo9,
        'days' => []
    ];
    
    for($i = 1; $i <= 7; $i++) {
        $dayNum = $i == 7 ? 0 : $i;
        $scheduleData['days'][$dayNum] = [
            'start' => $schedule->{"start$i"} == '12:00 AM' ? '' : $schedule->{"start$i"},
            'duration' => $schedule->{"duration$i"} == 0 ? '' : $schedule->{"duration$i"},
            'room' => $schedule->{"room$i"},
            'memo' => $schedule->{"memo$i"},
            'consultation' => $schedule->{"start1$i"} == '12:00 AM' ? '' : $schedule->{"start1$i"}
        ];
    }
}

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
        
        .main-content {
            padding: 30px 20px 0;
        }
        
        .view-controls {
            display: flex;
            align-items: center;
            gap: 15px;
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
            transform: scale(1.1);
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #2563eb 100%);
            color: white;
            padding: 16px 24px;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Schedule Table */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .schedule-table th {
            background: var(--bg-color);
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .schedule-table td {
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .schedule-table .today {
            background-color: rgba(51, 131, 255, 0.1);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .schedule-table .label-cell {
            text-align: left;
            font-weight: 600;
            background: var(--bg-color);
        }
        
        /* Attendance Records */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table th {
            background: var(--bg-color);
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .attendance-table td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .attendance-table tr:last-child td {
            border-bottom: none;
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
        
        /* Buttons */
        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-link:hover {
            color: var(--secondary-color);
            transform: translateX(2px);
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
            
            .header-nav {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .nav-btn {
                padding: 8px 16px;
                font-size: 13px;
            }
            
            .content-container {
                padding: 0 15px;
            }
            
            .stats-box {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .schedule-table {
                font-size: 14px;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 8px 4px;
            }
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
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id=<?php echo $studentid; ?>" class="nav-btn  active">
                📅 일정
            </a>

            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php?id=<?php echo $studentid; ?>" class="nav-btn">
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
                <button class="tab-btn active" onclick="openTab('schedule')">시간표</button>
                <button class="tab-btn" onclick="openTab('attendance')">출결현황</button>
                <button class="tab-btn" onclick="openTab('stats')">통계</button>
            </div>
            
            <!-- 시간표 Tab -->
            <div id="schedule" class="tab-content active">
                <div class="card">
                    <div class="section-header">
                        <span>주간 시간표</span>
                        <span>
                            <?php if($schedule): ?>
                            총 <?php echo $scheduleData['total']; ?>시간 | 
                            수정: <?php echo $scheduleData['edittime']; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if($schedule): ?>
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th width="15%"></th>
                                    <th class="<?php echo $nday == 1 ? 'today' : ''; ?>">월</th>
                                    <th class="<?php echo $nday == 2 ? 'today' : ''; ?>">화</th>
                                    <th class="<?php echo $nday == 3 ? 'today' : ''; ?>">수</th>
                                    <th class="<?php echo $nday == 4 ? 'today' : ''; ?>">목</th>
                                    <th class="<?php echo $nday == 5 ? 'today' : ''; ?>">금</th>
                                    <th class="<?php echo $nday == 6 ? 'today' : ''; ?>">토</th>
                                    <th class="<?php echo $nday == 0 ? 'today' : ''; ?>">일</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="label-cell">시작시간</td>
                                    <?php for($i = 1; $i <= 7; $i++): 
                                        $dayNum = $i == 7 ? 0 : $i;
                                        $isToday = $nday == $dayNum;
                                    ?>
                                    <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                        <?php echo $scheduleData['days'][$dayNum]['start']; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <tr>
                                    <td class="label-cell">공부시간</td>
                                    <?php for($i = 1; $i <= 7; $i++): 
                                        $dayNum = $i == 7 ? 0 : $i;
                                        $isToday = $nday == $dayNum;
                                    ?>
                                    <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                        <?php echo $scheduleData['days'][$dayNum]['duration']; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <tr>
                                    <td class="label-cell">공부장소</td>
                                    <?php for($i = 1; $i <= 7; $i++): 
                                        $dayNum = $i == 7 ? 0 : $i;
                                        $isToday = $nday == $dayNum;
                                    ?>
                                    <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                        <?php echo $scheduleData['days'][$dayNum]['room']; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <tr>
                                    <td class="label-cell">참고사항</td>
                                    <?php for($i = 1; $i <= 7; $i++): 
                                        $dayNum = $i == 7 ? 0 : $i;
                                        $isToday = $nday == $dayNum;
                                    ?>
                                    <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                        <?php echo $scheduleData['days'][$dayNum]['memo']; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <tr>
                                    <td class="label-cell">상담시간</td>
                                    <?php for($i = 1; $i <= 7; $i++): 
                                        $dayNum = $i == 7 ? 0 : $i;
                                        $isToday = $nday == $dayNum;
                                    ?>
                                    <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                        <?php echo $scheduleData['days'][$dayNum]['consultation']; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                            <div>
                                <span style="color: var(--info-color);"><?php echo $timezone_str; ?></span>
                            </div>
                            <?php if($role !== 'student'): ?>
                            <a href="editschedule.php?id=<?php echo $studentid; ?>&eid=<?php echo $schedule->id; ?>" class="btn-link">
                                <i class="fas fa-edit"></i> 시간표 수정
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <p>등록된 시간표가 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 출결현황 Tab -->
            <div id="attendance" class="tab-content">
                <div class="card">
                    <div class="section-header secondary">
                        <span>최근 출결 현황</span>
                    </div>
                    <div class="card-body">
                        <?php if(count($attendanceRecords) > 0): ?>
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th width="15%">날짜</th>
                                    <th width="15%">상태</th>
                                    <th width="20%">유형</th>
                                    <th width="20%">사유</th>
                                    <th width="30%">메모</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($attendanceRecords as $record): ?>
                                <tr>
                                    <td><?php echo date('m/d', $record->timecreated); ?></td>
                                    <td>
                                        <?php if($record->status == 1): ?>
                                        <span style="color: var(--success-color);">정상</span>
                                        <?php elseif($record->status == 2): ?>
                                        <span style="color: var(--warning-color);">지각</span>
                                        <?php else: ?>
                                        <span style="color: var(--secondary-color);">결석</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $record->type; ?></td>
                                    <td><?php echo $record->reason; ?></td>
                                    <td><?php echo $record->memo; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <p>출결 기록이 없습니다.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if($role !== 'student'): ?>
                <div class="card">
                    <div class="section-header info">
                        <span>출결 입력</span>
                    </div>
                    <div class="card-body">
                        <form id="attendanceForm">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">상태</label>
                                    <select class="form-control" id="attendanceStatus" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px;">
                                        <option value="1">정상</option>
                                        <option value="2">지각</option>
                                        <option value="3">결석</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">유형</label>
                                    <input type="text" class="form-control" id="attendanceType" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">사유</label>
                                    <input type="text" class="form-control" id="attendanceReason" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">메모</label>
                                    <input type="text" class="form-control" id="attendanceMemo" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px;">
                                </div>
                            </div>
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="button" class="btn-submit" onclick="saveAttendance()" style="background: var(--primary-color); color: white; border: none; padding: 10px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                                    출결 저장
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 통계 Tab -->
            <div id="stats" class="tab-content">
                <div class="stats-box">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $scheduleData['total'] ?? 0; ?>시간</div>
                        <div class="stat-label">주간 총 학습시간</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $todayduration; ?>시간</div>
                        <div class="stat-label">오늘 학습시간</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $untiltoday; ?>시간</div>
                        <div class="stat-label">누적 학습시간</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($attendanceRecords); ?>회</div>
                        <div class="stat-label">출석 기록</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="section-header success">
                        <span>주간 학습 그래프</span>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll View Container -->
        <div id="scrollView" class="scroll-container">
            <!-- 시간표 섹션 -->
            <div class="card">
                <div class="section-header">
                    <span>주간 시간표</span>
                    <span>
                        <?php if($schedule): ?>
                        총 <?php echo $scheduleData['total']; ?>시간
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if($schedule): ?>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th width="15%"></th>
                                <th class="<?php echo $nday == 1 ? 'today' : ''; ?>">월</th>
                                <th class="<?php echo $nday == 2 ? 'today' : ''; ?>">화</th>
                                <th class="<?php echo $nday == 3 ? 'today' : ''; ?>">수</th>
                                <th class="<?php echo $nday == 4 ? 'today' : ''; ?>">목</th>
                                <th class="<?php echo $nday == 5 ? 'today' : ''; ?>">금</th>
                                <th class="<?php echo $nday == 6 ? 'today' : ''; ?>">토</th>
                                <th class="<?php echo $nday == 0 ? 'today' : ''; ?>">일</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">시작시간</td>
                                <?php for($i = 1; $i <= 7; $i++): 
                                    $dayNum = $i == 7 ? 0 : $i;
                                    $isToday = $nday == $dayNum;
                                ?>
                                <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                    <?php echo $scheduleData['days'][$dayNum]['start']; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td class="label-cell">공부시간</td>
                                <?php for($i = 1; $i <= 7; $i++): 
                                    $dayNum = $i == 7 ? 0 : $i;
                                    $isToday = $nday == $dayNum;
                                ?>
                                <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                    <?php echo $scheduleData['days'][$dayNum]['duration']; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td class="label-cell">공부장소</td>
                                <?php for($i = 1; $i <= 7; $i++): 
                                    $dayNum = $i == 7 ? 0 : $i;
                                    $isToday = $nday == $dayNum;
                                ?>
                                <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                    <?php echo $scheduleData['days'][$dayNum]['room']; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <tr>
                                <td class="label-cell">참고사항</td>
                                <?php for($i = 1; $i <= 7; $i++): 
                                    $dayNum = $i == 7 ? 0 : $i;
                                    $isToday = $nday == $dayNum;
                                ?>
                                <td class="<?php echo $isToday ? 'today' : ''; ?>">
                                    <?php echo $scheduleData['days'][$dayNum]['memo']; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <p>등록된 시간표가 없습니다.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 통계 섹션 -->
            <div class="card">
                <div class="section-header success">
                    <span>학습 통계</span>
                </div>
                <div class="card-body">
                    <div class="stats-box">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $scheduleData['total'] ?? 0; ?>시간</div>
                            <div class="stat-label">주간 총 학습시간</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $todayduration; ?>시간</div>
                            <div class="stat-label">오늘 학습시간</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $untiltoday; ?>시간</div>
                            <div class="stat-label">누적 학습시간</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($attendanceRecords); ?>회</div>
                            <div class="stat-label">출석 기록</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 출결현황 섹션 -->
            <div class="card">
                <div class="section-header secondary">
                    <span>최근 출결 현황</span>
                </div>
                <div class="card-body">
                    <?php if(count($attendanceRecords) > 0): ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th width="15%">날짜</th>
                                <th width="15%">상태</th>
                                <th width="20%">유형</th>
                                <th width="20%">사유</th>
                                <th width="30%">메모</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($attendanceRecords as $record): ?>
                            <tr>
                                <td><?php echo date('m/d', $record->timecreated); ?></td>
                                <td>
                                    <?php if($record->status == 1): ?>
                                    <span style="color: var(--success-color);">정상</span>
                                    <?php elseif($record->status == 2): ?>
                                    <span style="color: var(--warning-color);">지각</span>
                                    <?php else: ?>
                                    <span style="color: var(--secondary-color);">결석</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $record->type; ?></td>
                                <td><?php echo $record->reason; ?></td>
                                <td><?php echo $record->memo; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <p>출결 기록이 없습니다.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Core JS Files -->
<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
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
        localStorage.setItem('schedulePreferredView', 'scroll');
    } else {
        currentView = 'tab';
        tabView.classList.add('active');
        scrollView.classList.remove('active');
        toggleBtn.classList.remove('scroll-mode');
        toggleBtn.title = '스크롤 뷰로 전환';
        viewIcon.className = 'fas fa-folder';
        localStorage.setItem('schedulePreferredView', 'tab');
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
    
    localStorage.setItem('scheduleActiveTab', tabName);
}

function getTabTitle(tabName) {
    const titles = {
        'schedule': '시간표',
        'attendance': '출결현황',
        'stats': '통계'
    };
    return titles[tabName] || tabName;
}

// Save Attendance Function
function saveAttendance() {
    const status = document.getElementById('attendanceStatus').value;
    const type = document.getElementById('attendanceType').value;
    const reason = document.getElementById('attendanceReason').value;
    const memo = document.getElementById('attendanceMemo').value;
    
    if(!type || !reason) {
        Swal.fire('알림', '유형과 사유를 입력해주세요.', 'warning');
        return;
    }
    
    Swal.fire({
        text: '출결이 저장되었습니다',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    
    $.ajax({
        url: "database.php",
        type: "POST",
        dataType: "json",
        data: {
            "eventid": 95,
            "userid": <?php echo $studentid; ?>,
            "status": status,
            "type": type,
            "reason": reason,
            "memo": memo
        },
        success: function(data) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

// Load user preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load preferred view
    const preferredView = localStorage.getItem('schedulePreferredView');
    if(preferredView === 'scroll') {
        toggleView();
    }
    
    // Load last active tab
    const activeTab = localStorage.getItem('scheduleActiveTab');
    if(activeTab) {
        openTab(activeTab);
    }
    
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
    
    // Draw weekly chart if data exists
    <?php if($schedule): ?>
    const ctx = document.getElementById('weeklyChart');
    if(ctx) {
        const weeklyChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['월', '화', '수', '목', '금', '토', '일'],
                datasets: [{
                    label: '학습시간',
                    data: [
                        <?php echo $schedule->duration1 ?? 0; ?>,
                        <?php echo $schedule->duration2 ?? 0; ?>,
                        <?php echo $schedule->duration3 ?? 0; ?>,
                        <?php echo $schedule->duration4 ?? 0; ?>,
                        <?php echo $schedule->duration5 ?? 0; ?>,
                        <?php echo $schedule->duration6 ?? 0; ?>,
                        <?php echo $schedule->duration7 ?? 0; ?>
                    ],
                    backgroundColor: 'rgba(51, 131, 255, 0.5)',
                    borderColor: 'rgba(51, 131, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});

// Tab navigation with keyboard
document.addEventListener('keydown', function(e) {
    if(document.getElementById('tabView').classList.contains('active')) {
        const tabs = ['schedule', 'attendance', 'stats'];
        const currentTab = localStorage.getItem('scheduleActiveTab') || 'schedule';
        const currentIndex = tabs.indexOf(currentTab);
        
        if(e.key === 'ArrowRight' && currentIndex < tabs.length - 1) {
            openTab(tabs[currentIndex + 1]);
        } else if(e.key === 'ArrowLeft' && currentIndex > 0) {
            openTab(tabs[currentIndex - 1]);
        }
    }
});
</script>

<?php
$pagetype = 'schedule';
include("../LLM/postit.php");
?>
</body>
</html>