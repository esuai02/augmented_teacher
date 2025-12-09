<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 교사 권한 확인 - lastname에 T가 있는 경우
$isTeacher = false;
if (strpos($USER->lastname, 'T') !== false || $USER->lastname === 'T' || trim($USER->lastname) === 'T') {
    $isTeacher = true;
}

if (!$isTeacher) {
    die("<h2>접근 권한이 없습니다. 교사 계정으로 로그인해주세요.</h2>");
}

// URL 파라미터
$studentId = isset($_GET['studentId']) ? intval($_GET['studentId']) : null;
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'list';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// 학생 목록 조회
$params = array();
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1 as phone
        FROM mdl_user u
        INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
        WHERE uid.fieldid = 22 AND uid.data = 'student'
        AND u.deleted = 0 
        AND u.suspended = 0";

if ($searchQuery) {
    // 검색어에서 공백 제거한 버전도 준비
    $searchTerm = '%' . $searchQuery . '%';
    $searchTermNoSpace = '%' . str_replace(' ', '', $searchQuery) . '%';
    
    // 띄어쓰기 있는 버전과 없는 버전 모두 검색
    $sql .= " AND (
        CONCAT(u.firstname, ' ', u.lastname) LIKE ? 
        OR CONCAT(u.firstname, u.lastname) LIKE ? 
        OR REPLACE(CONCAT(u.firstname, ' ', u.lastname), ' ', '') LIKE ?
        OR CONCAT(u.lastname, u.firstname) LIKE ?
        OR u.firstname LIKE ? 
        OR u.lastname LIKE ? 
        OR u.email LIKE ?
    )";
    $params[] = $searchTerm;           // 이름 그대로 검색
    $params[] = $searchTerm;           // 붙여쓴 이름 검색
    $params[] = $searchTermNoSpace;    // 공백 제거한 검색어로 검색
    $params[] = $searchTermNoSpace;    // 성+이름 순서로도 검색
    $params[] = $searchTerm;           // firstname만 검색
    $params[] = $searchTerm;           // lastname만 검색
    $params[] = $searchTerm;           // 이메일 검색
}

// LIMIT 제거하여 모든 학생 가져오기
$sql .= " ORDER BY u.firstname ASC, u.lastname ASC";

if (empty($params)) {
    $students = $DB->get_records_sql($sql);
} else {
    $students = $DB->get_records_sql($sql, $params);
}

// 학생이 없으면 빈 배열로 초기화
if (!$students) {
    $students = array();
}

// 선택된 학생 정보
$selectedStudent = null;
if ($studentId) {
    $selectedStudent = $DB->get_record('user', array('id' => $studentId));
}

// 실시간 알림 데이터 조회
function getAlerts($DB) {
    $alerts = [];
    $now = time();
    $fifteenMinutesAgo = $now - 900;
    
    // 최근 15분 이내 접속 기록 확인
    $recentLogs = $DB->get_records_sql("
        SELECT DISTINCT userid, MAX(timecreated) as lasttime 
        FROM mdl_abessi_missionlog 
        WHERE timecreated > ? 
        GROUP BY userid", array($fifteenMinutesAgo));
    
    foreach ($recentLogs as $log) {
        $user = $DB->get_record('user', array('id' => $log->userid));
        if ($user) {
            // 현재 스케줄 확인
            $schedule = $DB->get_record_sql("
                SELECT * FROM mdl_abessi_schedule 
                WHERE userid = ? AND pinned = 1 
                ORDER BY id DESC LIMIT 1", array($log->userid));
            
            if (!$schedule) {
                // 예정외 접속
                $alerts[] = array(
                    'id' => $log->userid,
                    'type' => 'unscheduled',
                    'priority' => 'normal',
                    'studentName' => $user->firstname . ' ' . $user->lastname,
                    'message' => '예정외 접속 감지',
                    'time' => date('H:i', $log->lasttime)
                );
            }
        }
    }
    
    return $alerts;
}

// 출결 데이터 계산
function calculateAttendanceStats($DB, $studentId) {
    $threeWeeksAgo = strtotime("-3 weeks");
    
    // 휴강 시간
    $absenceRecord = $DB->get_record_sql(
        "SELECT SUM(amount) as total FROM mdl_abessi_classtimemanagement 
         WHERE userid = ? AND event = 'absence' AND hide = 0 AND due >= ?",
        array($studentId, $threeWeeksAgo)
    );
    $totalAbsence = $absenceRecord ? floatval($absenceRecord->total) : 0;
    
    // 보강 시간
    $makeupRecord = $DB->get_record_sql(
        "SELECT SUM(amount) as total FROM mdl_abessi_classtimemanagement 
         WHERE userid = ? AND event = 'makeup' AND hide = 0 AND due >= ?",
        array($studentId, $threeWeeksAgo)
    );
    $totalMakeup = $makeupRecord ? floatval($makeupRecord->total) : 0;
    
    return array(
        'absenceHours' => $totalAbsence,
        'makeupHours' => $totalMakeup,
        'remainingHours' => max(0, $totalAbsence - $totalMakeup)
    );
}

// 캘린더 데이터 조회
function getCalendarData($DB, $studentId, $month) {
    $startDate = strtotime($month . '-01');
    $endDate = strtotime($month . '-' . date('t', $startDate) . ' 23:59:59');
    
    $records = $DB->get_records_sql(
        "SELECT * FROM mdl_abessi_classtimemanagement 
         WHERE userid = ? AND hide = 0 AND due BETWEEN ? AND ?
         ORDER BY due ASC",
        array($studentId, $startDate, $endDate)
    );
    
    $calendarData = array();
    foreach ($records as $record) {
        $date = date('Y-m-d', $record->due);
        if (!isset($calendarData[$date])) {
            $calendarData[$date] = array();
        }
        $calendarData[$date][] = array(
            'type' => $record->event,
            'hours' => $record->amount,
            'note' => $record->text
        );
    }
    
    return $calendarData;
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'addAbsence' && $studentId) {
        $record = new stdClass();
        $record->userid = $studentId;
        $record->event = 'absence';
        $record->hide = 0;
        $record->amount = floatval($_POST['hours']);
        $record->text = $_POST['reason'] ?? '';
        $record->due = strtotime($_POST['date']);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        $DB->insert_record('abessi_classtimemanagement', $record);
        header("Location: ?studentId=$studentId&view=calendar");
        exit;
    }
    
    if ($action === 'addMakeup' && $studentId) {
        $record = new stdClass();
        $record->userid = $studentId;
        $record->event = 'makeup';
        $record->hide = 0;
        $record->amount = floatval($_POST['hours']);
        $record->text = $_POST['note'] ?? '';
        $record->due = strtotime($_POST['date']);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        $DB->insert_record('abessi_classtimemanagement', $record);
        header("Location: ?studentId=$studentId&view=calendar");
        exit;
    }
}

$alerts = getAlerts($DB);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>출결관리 시스템 - MathKing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px 32px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .teacher-name {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .current-time {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Alerts Bar */
        .alerts-container {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            max-height: 60px;
            overflow: hidden;
        }
        
        .alert-icon {
            font-size: 20px;
        }
        
        .alert-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            background: white;
            border-radius: 20px;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .alert-type-absence {
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .alert-type-unscheduled {
            color: #fd7e14;
            border: 1px solid #fd7e14;
        }
        
        /* Navigation */
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .nav-tab {
            padding: 16px 32px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tab.active {
            color: #667eea;
            background: white;
            border-bottom: 3px solid #667eea;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
        }
        
        /* Main Content */
        .main-content {
            padding: 32px;
        }
        
        /* Search and Filters */
        .controls {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .search-form {
            flex: 1;
            display: flex;
            gap: 8px;
        }
        
        .search-box {
            flex: 1;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        
        .search-btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .clear-btn {
            padding: 12px 20px;
            background: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .clear-btn:hover {
            background: #dee2e6;
        }
        
        .filter-select {
            padding: 12px 24px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Student List */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 16px;
            color: #6c757d;
        }
        
        .no-results h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #495057;
        }
        
        .no-results p {
            font-size: 16px;
            margin-bottom: 24px;
        }
        
        .student-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .student-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.1);
        }
        
        .student-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        .student-info h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .student-email {
            font-size: 14px;
            color: #6c757d;
        }
        
        .student-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        
        .stat-item {
            text-align: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .stat-remaining { color: #dc3545; }
        .stat-makeup { color: #28a745; }
        .stat-absence { color: #6c757d; }
        
        /* Calendar View */
        .calendar-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .calendar-nav button {
            padding: 8px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calendar-nav button:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .calendar-month {
            font-size: 20px;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #667eea;
            color: white;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .calendar-day-header {
            padding: 12px;
            text-align: center;
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }
        
        .calendar-day {
            min-height: 100px;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            background: white;
            position: relative;
        }
        
        .calendar-day-number {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .calendar-day.today {
            background: #f0f4ff;
            border-color: #667eea;
        }
        
        .calendar-day.today .calendar-day-number {
            color: #667eea;
        }
        
        .calendar-event {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .event-absence {
            background: #ffe5e5;
            color: #dc3545;
            border-left: 3px solid #dc3545;
        }
        
        .event-makeup {
            background: #e5ffe5;
            color: #28a745;
            border-left: 3px solid #28a745;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        .action-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 24px;
        }
        
        .action-card h3 {
            margin-bottom: 16px;
            color: #495057;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-absence {
            background: #dc3545;
            color: white;
        }
        
        .btn-absence:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .btn-makeup {
            background: #28a745;
            color: white;
        }
        
        .btn-makeup:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-normal {
            background: #e5ffe5;
            color: #28a745;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-alert {
            background: #ffe5e5;
            color: #dc3545;
        }
        
        /* Summary Stats */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .summary-label {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📚 MathKing 출결관리 시스템</h1>
            <div class="header-info">
                <span class="teacher-name">👤 <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> 선생님</span>
                <span class="current-time">🕐 <span id="currentTime"><?php echo date('Y-m-d H:i:s'); ?></span></span>
            </div>
        </div>
        
        <!-- Alerts Bar -->
        <?php if (!empty($alerts)): ?>
        <div class="alerts-container">
            <span class="alert-icon">⚠️</span>
            <?php foreach (array_slice($alerts, 0, 3) as $alert): ?>
            <div class="alert-item alert-type-<?php echo $alert['type']; ?>">
                <span><?php echo htmlspecialchars($alert['studentName']); ?></span>
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
                <span><?php echo htmlspecialchars($alert['time']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (count($alerts) > 3): ?>
            <div class="alert-item">
                <span>+<?php echo count($alerts) - 3; ?>개 더보기</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="nav-tabs">
            <button class="nav-tab <?php echo !$studentId ? 'active' : ''; ?>" onclick="location.href='?view=list'">
                📋 학생 목록
            </button>
            <?php if ($studentId): ?>
            <button class="nav-tab active">
                📅 <?php echo htmlspecialchars($selectedStudent->firstname . ' ' . $selectedStudent->lastname); ?> 캘린더
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (!$studentId): ?>
            <!-- Student List View -->
            <div class="controls">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" 
                               name="search" 
                               id="searchInput"
                               placeholder="학생 이름으로 검색 (예: 김철수, 박민수)..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               autocomplete="off">
                    </div>
                    <button type="submit" class="search-btn">검색</button>
                    <?php if ($searchQuery): ?>
                    <button type="button" class="clear-btn" onclick="location.href='?view=list'">초기화</button>
                    <?php endif; ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filterStatus); ?>">
                </form>
                <select class="filter-select" onchange="location.href='?search=<?php echo urlencode($searchQuery); ?>&filter=' + this.value">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>모든 상태</option>
                    <option value="normal" <?php echo $filterStatus === 'normal' ? 'selected' : ''; ?>>정상</option>
                    <option value="makeup" <?php echo $filterStatus === 'makeup' ? 'selected' : ''; ?>>보강 필요</option>
                </select>
            </div>
            
            <?php 
            // 필터링 전 총 학생 수 계산
            $totalStudents = count($students);
            ?>
            
            <?php if ($searchQuery || $filterStatus !== 'all'): ?>
            <div style="margin-bottom: 20px; padding: 12px; background: #f0f4ff; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <?php if ($searchQuery): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>🔍</span>
                        <span>검색어: <strong><?php echo htmlspecialchars($searchQuery); ?></strong></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($filterStatus !== 'all'): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>🏷️</span>
                        <span>필터: <strong><?php echo $filterStatus === 'normal' ? '정상' : '보강 필요'; ?></strong></span>
                    </div>
                    <?php endif; ?>
                    <div style="margin-left: auto;">
                        <span id="searchResultInfo" style="color: #667eea; font-weight: 600;">
                            결과: <?php echo $totalStudents; ?>명
                        </span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="margin-bottom: 20px; padding: 12px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                <span>📋</span>
                <span>전체 학생 목록</span>
                <span id="searchResultInfo" style="color: #6c757d;">(총 <?php echo $totalStudents; ?>명)</span>
            </div>
            <?php endif; ?>
            
            <div class="student-grid">
                <?php 
                $displayedStudents = 0;
                $filteredStudents = array();
                
                // 먼저 필터링된 학생 목록을 생성
                foreach ($students as $student) {
                    $stats = calculateAttendanceStats($DB, $student->id);
                    $status = $stats['remainingHours'] > 0 ? 'warning' : 'normal';
                    
                    // Apply filter
                    $shouldDisplay = true;
                    if ($filterStatus !== 'all') {
                        if ($filterStatus === 'normal' && $stats['remainingHours'] > 0) {
                            $shouldDisplay = false;
                        }
                        if ($filterStatus === 'makeup' && $stats['remainingHours'] == 0) {
                            $shouldDisplay = false;
                        }
                    }
                    
                    if ($shouldDisplay) {
                        $student->stats = $stats;
                        $student->status = $status;
                        $filteredStudents[] = $student;
                        $displayedStudents++;
                    }
                }
                
                // 필터링된 학생들을 표시
                foreach ($filteredStudents as $student): 
                    $stats = $student->stats;
                    $status = $student->status;
                ?>
                <div class="student-card" onclick="location.href='?studentId=<?php echo $student->id; ?>&view=calendar'">
                    <div class="student-header">
                        <div class="student-avatar">
                            <?php echo strtoupper(substr($student->firstname, 0, 1) . substr($student->lastname, 0, 1)); ?>
                        </div>
                        <div class="student-info">
                            <h3><?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?></h3>
                            <div class="student-email"><?php echo htmlspecialchars($student->email); ?></div>
                        </div>
                    </div>
                    <div class="status-badge status-<?php echo $status; ?>">
                        <?php echo $stats['remainingHours'] > 0 ? '⚠️ 보강 필요' : '✅ 정상'; ?>
                    </div>
                    <div class="student-stats">
                        <div class="stat-item">
                            <div class="stat-value stat-remaining"><?php echo number_format($stats['remainingHours'], 1); ?></div>
                            <div class="stat-label">보강필요</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value stat-makeup"><?php echo number_format($stats['makeupHours'], 1); ?></div>
                            <div class="stat-label">보강완료</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value stat-absence"><?php echo number_format($stats['absenceHours'], 1); ?></div>
                            <div class="stat-label">총휴강</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($displayedStudents == 0): ?>
                <div class="no-results">
                    <h3>😔 검색 결과가 없습니다</h3>
                    <p>
                        <?php if ($searchQuery): ?>
                            "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" 검색어와 일치하는 학생이 없습니다.
                        <?php else: ?>
                            현재 필터 조건에 맞는 학생이 없습니다.
                        <?php endif; ?>
                    </p>
                    <button class="btn" style="background: #667eea; color: white; width: auto; padding: 12px 32px;" 
                            onclick="location.href='?view=list'">전체 목록 보기</button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php else: ?>
            <!-- Calendar View -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <a href="?view=list" class="back-button">
                        ← 목록으로
                    </a>
                    <div class="calendar-nav">
                        <button onclick="location.href='?studentId=<?php echo $studentId; ?>&view=calendar&month=<?php echo date('Y-m', strtotime($selectedMonth . ' -1 month')); ?>'">
                            ◀
                        </button>
                        <div class="calendar-month">
                            <?php echo date('Y년 n월', strtotime($selectedMonth)); ?>
                        </div>
                        <button onclick="location.href='?studentId=<?php echo $studentId; ?>&view=calendar&month=<?php echo date('Y-m', strtotime($selectedMonth . ' +1 month')); ?>'">
                            ▶
                        </button>
                    </div>
                </div>
                
                <?php 
                $stats = calculateAttendanceStats($DB, $studentId);
                ?>
                <div class="summary-stats">
                    <div class="summary-card">
                        <div class="summary-value"><?php echo number_format($stats['remainingHours'], 1); ?>h</div>
                        <div class="summary-label">보강 필요</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo number_format($stats['makeupHours'], 1); ?>h</div>
                        <div class="summary-label">보강 완료</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value"><?php echo number_format($stats['absenceHours'], 1); ?>h</div>
                        <div class="summary-label">총 휴강</div>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <?php
                    $days = array('일', '월', '화', '수', '목', '금', '토');
                    foreach ($days as $day): ?>
                    <div class="calendar-day-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>
                    
                    <?php
                    $firstDay = date('w', strtotime($selectedMonth . '-01'));
                    $lastDay = date('t', strtotime($selectedMonth . '-01'));
                    $today = date('Y-m-d');
                    $calendarData = getCalendarData($DB, $studentId, $selectedMonth);
                    
                    // Empty cells before first day
                    for ($i = 0; $i < $firstDay; $i++): ?>
                    <div class="calendar-day" style="background: #fafafa;"></div>
                    <?php endfor; ?>
                    
                    <?php for ($day = 1; $day <= $lastDay; $day++):
                        $currentDate = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $isToday = $currentDate === $today;
                        $dayEvents = $calendarData[$currentDate] ?? array();
                    ?>
                    <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?>">
                        <div class="calendar-day-number"><?php echo $day; ?></div>
                        <?php foreach ($dayEvents as $event): ?>
                        <div class="calendar-event event-<?php echo $event['type']; ?>">
                            <?php echo $event['type'] === 'absence' ? '휴강' : '보강'; ?> <?php echo $event['hours']; ?>h
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="action-card">
                        <h3>🚫 휴강 추가</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="addAbsence">
                            <div class="form-group">
                                <label>날짜</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>시간</label>
                                <select name="hours" class="form-control" required>
                                    <option value="">선택</option>
                                    <?php for ($h = 0.5; $h <= 6; $h += 0.5): ?>
                                    <option value="<?php echo $h; ?>"><?php echo $h; ?>시간</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>사유</label>
                                <input type="text" name="reason" class="form-control" placeholder="선택사항">
                            </div>
                            <button type="submit" class="btn btn-absence">휴강 추가</button>
                        </form>
                    </div>
                    
                    <div class="action-card">
                        <h3>✅ 보강 추가</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="addMakeup">
                            <div class="form-group">
                                <label>날짜</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>시간</label>
                                <select name="hours" class="form-control" required>
                                    <option value="">선택</option>
                                    <?php for ($h = 0.5; $h <= 6; $h += 0.5): ?>
                                    <option value="<?php echo $h; ?>"><?php echo $h; ?>시간</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>메모</label>
                                <input type="text" name="note" class="form-control" placeholder="선택사항">
                            </div>
                            <button type="submit" class="btn btn-makeup">보강 추가</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.getFullYear() + '-' + 
                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                String(now.getDate()).padStart(2, '0') + ' ' + 
                String(now.getHours()).padStart(2, '0') + ':' + 
                String(now.getMinutes()).padStart(2, '0') + ':' + 
                String(now.getSeconds()).padStart(2, '0');
            document.getElementById('currentTime').textContent = timeString;
        }
        
        setInterval(updateTime, 1000);
        
        // Real-time search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const studentGrid = document.querySelector('.student-grid');
            
            if (searchInput && studentGrid) {
                // Real-time filtering
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const searchTermNoSpace = searchTerm.replace(/\s+/g, ''); // 공백 제거
                    const studentCards = studentGrid.querySelectorAll('.student-card');
                    
                    let visibleCount = 0;
                    let hasNoResults = true;
                    
                    studentCards.forEach(card => {
                        const studentName = card.querySelector('.student-info h3');
                        if (studentName) {
                            const name = studentName.textContent.toLowerCase();
                            const nameNoSpace = name.replace(/\s+/g, ''); // 공백 제거
                            
                            // 원본 이름이나 공백 제거한 이름에서 검색
                            if (searchTerm === '' || 
                                name.includes(searchTerm) || 
                                nameNoSpace.includes(searchTermNoSpace) ||
                                nameNoSpace.includes(searchTerm) ||
                                name.includes(searchTermNoSpace)) {
                                card.style.display = '';
                                visibleCount++;
                                hasNoResults = false;
                            } else {
                                card.style.display = 'none';
                            }
                        }
                    });
                    
                    // Update result count
                    updateResultCount(visibleCount);
                    
                    // Show/hide no results message
                    let noResultsDiv = studentGrid.querySelector('.no-results-live');
                    if (hasNoResults && searchTerm !== '') {
                        if (!noResultsDiv) {
                            noResultsDiv = document.createElement('div');
                            noResultsDiv.className = 'no-results-live';
                            noResultsDiv.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 40px; background: #f8f9fa; border-radius: 16px;';
                            noResultsDiv.innerHTML = `
                                <h3 style="color: #495057; margin-bottom: 12px;">🔍 검색 결과가 없습니다</h3>
                                <p style="color: #6c757d;">입력한 이름과 일치하는 학생이 없습니다.</p>
                            `;
                            studentGrid.appendChild(noResultsDiv);
                        }
                        noResultsDiv.style.display = 'block';
                    } else if (noResultsDiv) {
                        noResultsDiv.style.display = 'none';
                    }
                });
                
                // Enter key to submit search
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }
        });
        
        function updateResultCount(count) {
            // Update search result count dynamically
            const resultInfo = document.getElementById('searchResultInfo');
            if (resultInfo) {
                if (count !== undefined) {
                    resultInfo.innerHTML = `결과: ${count}명`;
                }
            }
        }
    </script>
</body>
</html>