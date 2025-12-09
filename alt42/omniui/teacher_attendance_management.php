<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Moodle 로그인 확인
require_login();

// 현재 로그인한 사용자 정보
$teacher_id = $USER->id;
$teacher_name = $USER->firstname . ' ' . $USER->lastname;

// 교사 권한 확인
// 이름에 T가 포함되어 있으면 교사로 판단
$isTeacher = false;
if (strpos(strtoupper($USER->firstname), 'T') !== false || strpos(strtoupper($USER->lastname), 'T') !== false) {
    $isTeacher = true;
} else {
    // 이름에 T가 없으면 기존 방식으로 권한 확인
    $userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = 22", array($teacher_id));
    if ($userrole && $userrole->role !== 'student') {
        $isTeacher = true;
    }
}

// 교사가 아닌 경우 차단
if (!$isTeacher) {
    die("접근 권한이 없습니다. 교사 계정으로 로그인해주세요.");
}

// 선택된 학생 ID (URL 파라미터)
$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

// 날짜 필터링
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('Y-m');

// 교사가 담당하는 학생 목록 조회
function getStudentList($DB, $teacher_id) {
    // 교사와 연결된 학생 목록 조회
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
            FROM {user} u
            INNER JOIN {user_info_data} uid ON u.id = uid.userid
            WHERE uid.fieldid = 22 AND uid.data = 'student'
            ORDER BY u.firstname, u.lastname";
    
    return $DB->get_records_sql($sql);
}

// 특정 날짜의 학습 기록 조회
function getStudyRecordsByDate($DB, $student_id, $date) {
    $start_time = strtotime($date . ' 00:00:00');
    $end_time = strtotime($date . ' 23:59:59');
    
    $sql = "SELECT userid, page, timecreated 
            FROM {abessi_missionlog} 
            WHERE userid = ? AND timecreated BETWEEN ? AND ?
            ORDER BY timecreated ASC";
    
    return $DB->get_records_sql($sql, array($student_id, $start_time, $end_time));
}

// 공부시간 계산
function calculateStudyTime($records) {
    if (!$records || count($records) < 2) {
        return 0;
    }
    
    $records_array = array_values($records);
    $first_time = $records_array[0]->timecreated;
    $last_time = $records_array[count($records_array) - 1]->timecreated;
    
    // 시간 차이를 시간 단위로 계산
    $time_diff = ($last_time - $first_time) / 3600;
    
    return round($time_diff, 2);
}

// 시간표 정보 조회
function getScheduleInfo($DB, $student_id) {
    $sql = "SELECT * FROM {abessi_schedule} 
            WHERE userid = ? AND pinned = 1 
            ORDER BY id DESC LIMIT 1";
    
    return $DB->get_record_sql($sql, array($student_id));
}

// 출결 기록 조회
function getAttendanceRecords($DB, $student_id, $month = null) {
    if (!$month) {
        $month = date('Y-m');
    }
    
    $start_date = strtotime($month . '-01');
    $end_date = strtotime(date('Y-m-t', $start_date) . ' 23:59:59');
    
    $sql = "SELECT * FROM {abessi_classtimemanagement} 
            WHERE userid = ? AND hide = 0 AND timecreated BETWEEN ? AND ?
            ORDER BY due DESC";
    
    return $DB->get_records_sql($sql, array($student_id, $start_date, $end_date));
}

// 월별 통계 계산
function calculateMonthlyStats($DB, $student_id, $month) {
    $start_date = strtotime($month . '-01');
    $end_date = strtotime(date('Y-m-t', $start_date) . ' 23:59:59');
    
    // 총 공부 시간 계산
    $total_study_time = 0;
    $study_days = 0;
    
    for ($date = $start_date; $date <= $end_date; $date += 86400) {
        $current_date = date('Y-m-d', $date);
        $records = getStudyRecordsByDate($DB, $student_id, $current_date);
        $study_time = calculateStudyTime($records);
        
        if ($study_time > 0) {
            $total_study_time += $study_time;
            $study_days++;
        }
    }
    
    // 휴강/보강 통계
    $attendance_records = getAttendanceRecords($DB, $student_id, $month);
    $absence_hours = 0;
    $makeup_hours = 0;
    
    foreach ($attendance_records as $record) {
        if ($record->event === 'absence') {
            $absence_hours += $record->amount;
        } elseif ($record->event === 'makeup') {
            $makeup_hours += $record->amount;
        }
    }
    
    return [
        'total_study_time' => $total_study_time,
        'study_days' => $study_days,
        'absence_hours' => $absence_hours,
        'makeup_hours' => $makeup_hours,
        'needed_makeup' => max(0, $absence_hours - $makeup_hours)
    ];
}

// 학생 목록 가져오기
$students = getStudentList($DB, $teacher_id);

// 선택된 학생의 정보 가져오기
$selected_student = null;
$study_records = [];
$attendance_records = [];
$schedule_info = null;
$monthly_stats = null;

if ($selected_student_id) {
    // 학생 정보 조회
    foreach ($students as $student) {
        if ($student->id == $selected_student_id) {
            $selected_student = $student;
            break;
        }
    }
    
    if ($selected_student) {
        // 학습 기록 조회
        $study_records = getStudyRecordsByDate($DB, $selected_student_id, $filter_date);
        
        // 출결 기록 조회
        $attendance_records = getAttendanceRecords($DB, $selected_student_id, $filter_month);
        
        // 시간표 정보 조회
        $schedule_info = getScheduleInfo($DB, $selected_student_id);
        
        // 월별 통계 계산
        $monthly_stats = calculateMonthlyStats($DB, $selected_student_id, $filter_month);
    }
}

// POST 요청 처리 (출결 기록 추가)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $student_id = intval($_POST['student_id']);
    
    if ($action === 'add_attendance') {
        $event_type = $_POST['event_type']; // absence or makeup
        $date = $_POST['date'];
        $hours = floatval($_POST['hours']);
        $memo = $_POST['memo'] ?? '';
        
        $record = new stdClass();
        $record->userid = $student_id;
        $record->event = $event_type;
        $record->hide = 0;
        $record->amount = $hours;
        $record->text = $memo;
        $record->due = strtotime($date);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        if ($DB->insert_record('abessi_classtimemanagement', $record)) {
            $_SESSION['success_message'] = "출결 기록이 추가되었습니다.";
        } else {
            $_SESSION['error_message'] = "출결 기록 추가 중 오류가 발생했습니다.";
        }
        
        header("Location: teacher_attendance_management.php?student_id=" . $student_id);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>교사용 출결관리 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 24px;
        }
        
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #4a5568;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .student-list {
            list-style: none;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .student-list h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .student-item {
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .student-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }
        
        .student-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        
        .content-area {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #718096;
            font-size: 16px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab-button:hover {
            color: #2d3748;
        }
        
        .tab-button.active {
            color: #667eea;
            font-weight: 600;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-unit {
            font-size: 14px;
            opacity: 0.9;
            margin-left: 5px;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
        }
        
        .filter-controls input[type="date"],
        .filter-controls input[type="month"] {
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-controls button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .filter-controls button:hover {
            background: #5a67d8;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .attendance-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .attendance-table tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-absence {
            background: #fed7d7;
            color: #c53030;
        }
        
        .badge-makeup {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .schedule-day {
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            text-align: center;
        }
        
        .schedule-day.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .day-name {
            font-size: 12px;
            margin-bottom: 8px;
            opacity: 0.8;
        }
        
        .day-time {
            font-size: 20px;
            font-weight: bold;
        }
        
        .add-record-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }
        
        .success-message,
        .error-message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 교사용 출결관리 시스템</h1>
            <div class="teacher-info">
                <span>👨‍🏫 <?php echo htmlspecialchars($teacher_name); ?> 선생님</span>
                <a href="/moodle/login/logout.php" style="color: #667eea; text-decoration: none;">로그아웃</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="main-content">
            <div class="sidebar">
                <h3>학생 목록</h3>
                <ul class="student-list">
                    <?php foreach ($students as $student): ?>
                        <li class="student-item <?php echo $selected_student_id == $student->id ? 'active' : ''; ?>"
                            onclick="location.href='?student_id=<?php echo $student->id; ?>'">
                            <div style="font-weight: 600; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?>
                            </div>
                            <div style="font-size: 12px; opacity: 0.8;">
                                <?php echo htmlspecialchars($student->email); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="content-area">
                <?php if ($selected_student): ?>
                    <h2 style="margin-bottom: 20px; color: #2d3748;">
                        <?php echo htmlspecialchars($selected_student->firstname . ' ' . $selected_student->lastname); ?> 학생 출결 관리
                    </h2>
                    
                    <div class="tabs">
                        <button class="tab-button active" onclick="showTab('overview')">전체 현황</button>
                        <button class="tab-button" onclick="showTab('daily')">일일 학습</button>
                        <button class="tab-button" onclick="showTab('attendance')">출결 기록</button>
                        <button class="tab-button" onclick="showTab('schedule')">시간표</button>
                        <button class="tab-button" onclick="showTab('add')">기록 추가</button>
                    </div>
                    
                    <!-- 전체 현황 탭 -->
                    <div id="overview" class="tab-content active">
                        <div class="filter-controls">
                            <label>월 선택:</label>
                            <input type="month" id="month-filter" value="<?php echo $filter_month; ?>" 
                                   onchange="updateMonthFilter(this.value)">
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-label">총 학습시간</div>
                                <div class="stat-value">
                                    <?php echo $monthly_stats ? number_format($monthly_stats['total_study_time'], 1) : '0'; ?>
                                    <span class="stat-unit">시간</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">학습일수</div>
                                <div class="stat-value">
                                    <?php echo $monthly_stats ? $monthly_stats['study_days'] : '0'; ?>
                                    <span class="stat-unit">일</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">휴강시간</div>
                                <div class="stat-value">
                                    <?php echo $monthly_stats ? number_format($monthly_stats['absence_hours'], 1) : '0'; ?>
                                    <span class="stat-unit">시간</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-label">보강필요</div>
                                <div class="stat-value">
                                    <?php echo $monthly_stats ? number_format($monthly_stats['needed_makeup'], 1) : '0'; ?>
                                    <span class="stat-unit">시간</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 일일 학습 탭 -->
                    <div id="daily" class="tab-content">
                        <div class="filter-controls">
                            <label>날짜 선택:</label>
                            <input type="date" value="<?php echo $filter_date; ?>" 
                                   onchange="updateDateFilter(this.value)">
                        </div>
                        
                        <?php if ($study_records): ?>
                            <h3>학습 기록 (<?php echo $filter_date; ?>)</h3>
                            <p>총 학습시간: <?php echo calculateStudyTime($study_records); ?>시간</p>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>시간</th>
                                        <th>페이지</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($study_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('H:i:s', $record->timecreated); ?></td>
                                            <td><?php echo htmlspecialchars($record->page); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <p>선택한 날짜에 학습 기록이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 출결 기록 탭 -->
                    <div id="attendance" class="tab-content">
                        <?php if ($attendance_records): ?>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>날짜</th>
                                        <th>구분</th>
                                        <th>시간</th>
                                        <th>상태</th>
                                        <th>메모</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', $record->due); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $record->event; ?>">
                                                    <?php echo $record->event === 'absence' ? '휴강' : '보강'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $record->amount; ?>시간</td>
                                            <td><?php echo $record->status; ?></td>
                                            <td><?php echo htmlspecialchars($record->text); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <p>출결 기록이 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 시간표 탭 -->
                    <div id="schedule" class="tab-content">
                        <?php if ($schedule_info): ?>
                            <h3>현재 시간표</h3>
                            <div class="schedule-grid">
                                <?php 
                                $days = ['월', '화', '수', '목', '금', '토', '일'];
                                for ($i = 1; $i <= 7; $i++): 
                                    $duration_field = 'duration' . $i;
                                    $start_field = 'start' . $i;
                                    $room_field = 'room' . $i;
                                    $is_active = $schedule_info->$duration_field > 0;
                                ?>
                                    <div class="schedule-day <?php echo $is_active ? 'active' : ''; ?>">
                                        <div class="day-name"><?php echo $days[$i-1]; ?>요일</div>
                                        <div class="day-time">
                                            <?php echo $schedule_info->$duration_field ?: '-'; ?>시간
                                        </div>
                                        <?php if ($is_active && $schedule_info->$start_field): ?>
                                            <div style="font-size: 12px; margin-top: 5px;">
                                                <?php echo $schedule_info->$start_field; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>시간표 정보가 없습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 기록 추가 탭 -->
                    <div id="add" class="tab-content">
                        <form method="POST" class="add-record-form">
                            <input type="hidden" name="action" value="add_attendance">
                            <input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>">
                            
                            <div class="form-group">
                                <label>구분</label>
                                <select name="event_type" required>
                                    <option value="">선택하세요</option>
                                    <option value="absence">휴강</option>
                                    <option value="makeup">보강</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>날짜</label>
                                <input type="date" name="date" required>
                            </div>
                            
                            <div class="form-group">
                                <label>시간</label>
                                <select name="hours" required>
                                    <option value="">선택하세요</option>
                                    <?php for ($i = 0.5; $i <= 6; $i += 0.5): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?>시간</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>메모 (선택사항)</label>
                                <textarea name="memo" rows="3"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">초기화</button>
                                <button type="submit" class="btn btn-primary">추가하기</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <h2>학생을 선택해주세요</h2>
                        <p>왼쪽 목록에서 관리할 학생을 선택하면 상세 정보를 확인할 수 있습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // 모든 탭 내용 숨기기
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // 모든 탭 버튼 비활성화
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // 선택된 탭 활성화
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function updateDateFilter(date) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('filter_date', date);
            window.location.search = urlParams.toString();
        }
        
        function updateMonthFilter(month) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('filter_month', month);
            window.location.search = urlParams.toString();
        }
        
        function resetForm() {
            document.querySelector('.add-record-form').reset();
        }
    </script>
</body>
</html>