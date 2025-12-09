<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 현재 로그인한 사용자의 역할 조회
// lastname이 'T'이거나 lastname에 'T'가 포함되어 있으면 교사로 판단
$isTeacher = false;

// lastname이 정확히 'T'이거나 'T '로 끝나는 경우 교사로 인식
if ($USER->lastname === 'T' || 
    trim($USER->lastname) === 'T' || 
    strpos($USER->lastname, 'T') !== false) {
    $isTeacher = true;
} else {
    // lastname에 T가 없으면 기존 방식으로 권한 확인
    $userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"); 
    $role = $userrole ? $userrole->role : 'student';
    if ($role !== 'student') {
        $isTeacher = true;
    }
}

// 교사가 아닌 경우 차단
if (!$isTeacher) {
    die("접근 권한이 없습니다. 교사 계정으로 로그인해주세요. (성: " . htmlspecialchars($USER->lastname) . ", 이름: " . htmlspecialchars($USER->firstname) . ")");
}

// URL로부터 학생 ID 받아오기
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

// 학생 목록 조회
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.email 
    FROM mdl_user u
    INNER JOIN mdl_user_info_data uid ON u.id = uid.userid
    WHERE uid.fieldid = 22 AND uid.data = 'student'
    ORDER BY u.firstname, u.lastname
");

// 선택된 학생 정보
$selected_student = null;
if ($studentid > 0) {
    $selected_student = $DB->get_record_sql("SELECT * FROM mdl_user WHERE id = ?", array($studentid));
}

// 최근 3주 기준
$threeWeeksAgo = strtotime("-3 weeks");

// 출결 시간 계산 함수
function calculateAttendanceHours($DB, $studentid, $threeWeeksAgo) {
    // 결석 시간 계산
    $sqlAbsence = "SELECT SUM(amount) as total_absence 
                   FROM mdl_abessi_classtimemanagement 
                   WHERE userid = ? AND event = 'absence' AND hide = 0 AND due >= ?";
    $absenceRecord = $DB->get_record_sql($sqlAbsence, array($studentid, $threeWeeksAgo));
    $totalAbsence = $absenceRecord ? floatval($absenceRecord->total_absence) : 0;
    
    // 보강 시간 계산
    $sqlMakeupAll = "SELECT amount, due 
                     FROM mdl_abessi_classtimemanagement 
                     WHERE userid = ? AND event = 'makeup' AND hide = 0 AND due >= ?";
    $makeupRecords = $DB->get_records_sql($sqlMakeupAll, array($studentid, $threeWeeksAgo));
    
    $pastMakeup = 0;
    $futureMakeup = 0;
    if ($makeupRecords) {
        foreach ($makeupRecords as $rec) {
            if (date("Y-m-d", $rec->due) < date("Y-m-d")) {
                $pastMakeup += floatval($rec->amount);
            } else {
                $futureMakeup += floatval($rec->amount);
            }
        }
    }
    
    $neededMakeup = $totalAbsence - ($pastMakeup + $futureMakeup);
    if ($neededMakeup < 0) { $neededMakeup = 0; }
    
    return array(
        'totalAbsence' => $totalAbsence,
        'pastMakeup' => $pastMakeup,
        'futureMakeup' => $futureMakeup,
        'neededMakeup' => $neededMakeup
    );
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add_record' && $studentid > 0) {
        $event_type = $_POST['event_type'];
        $date = $_POST['date'];
        $hours = floatval($_POST['hours']);
        $memo = $_POST['memo'] ?? '';
        
        $record = new stdClass();
        $record->userid = $studentid;
        $record->event = $event_type;
        $record->hide = 0;
        $record->amount = $hours;
        $record->text = $memo;
        $record->due = strtotime($date);
        $record->timecreated = time();
        $record->status = 'done';
        $record->role = 'teacher';
        
        $DB->insert_record('abessi_classtimemanagement', $record);
        
        header("Location: teacher_attendance.php?userid=" . $studentid);
        exit;
    }
}

// 출결 데이터 가져오기
$attendanceData = array('totalAbsence' => 0, 'pastMakeup' => 0, 'futureMakeup' => 0, 'neededMakeup' => 0);
$attendance_records = array();
$schedule = null;

if ($studentid > 0) {
    $attendanceData = calculateAttendanceHours($DB, $studentid, $threeWeeksAgo);
    
    // 출결 기록 조회
    $attendance_records = $DB->get_records_sql(
        "SELECT * FROM mdl_abessi_classtimemanagement 
         WHERE userid = ? AND hide = 0 
         ORDER BY due DESC LIMIT 20", 
        array($studentid)
    );
    
    // 시간표 조회
    $schedule = $DB->get_record_sql(
        "SELECT * FROM mdl_abessi_schedule 
         WHERE userid = ? AND pinned = 1 
         ORDER BY id DESC LIMIT 1", 
        array($studentid)
    );
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>교사용 출결관리</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .student-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .student-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .student-item:hover {
            background-color: #f3f4f6;
        }
        .student-item.active {
            background-color: #93c5fd;
            color: white;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .stat-label {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-absence {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .badge-makeup {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .schedule-day {
            padding: 10px;
            background: #f3f4f6;
            border-radius: 4px;
            text-align: center;
        }
        .schedule-day.active {
            background: #93c5fd;
            color: white;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            color: #6b7280;
        }
        .tab.active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            margin-bottom: -2px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>교사용 출결관리 시스템</h1>
            <div>
                <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> 선생님 | 
                <a href="/moodle/login/logout.php">로그아웃</a>
            </div>
        </div>
        
        <div class="main-grid">
            <div class="sidebar">
                <h3>학생 목록</h3>
                <ul class="student-list">
                    <?php foreach ($students as $student): ?>
                        <li class="student-item <?php echo $studentid == $student->id ? 'active' : ''; ?>"
                            onclick="location.href='?userid=<?php echo $student->id; ?>'">
                            <?php echo htmlspecialchars($student->firstname . ' ' . $student->lastname); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="content">
                <?php if ($selected_student): ?>
                    <h2><?php echo htmlspecialchars($selected_student->firstname . ' ' . $selected_student->lastname); ?> 학생</h2>
                    
                    <div class="tabs">
                        <button class="tab active" onclick="showTab('overview')">전체 현황</button>
                        <button class="tab" onclick="showTab('records')">출결 기록</button>
                        <button class="tab" onclick="showTab('schedule')">시간표</button>
                        <button class="tab" onclick="showTab('add')">기록 추가</button>
                    </div>
                    
                    <div id="overview" class="tab-content active">
                        <div class="stats-container">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($attendanceData['totalAbsence'], 1); ?></div>
                                <div class="stat-label">총 휴강 시간</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($attendanceData['pastMakeup'], 1); ?></div>
                                <div class="stat-label">완료된 보강</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($attendanceData['futureMakeup'], 1); ?></div>
                                <div class="stat-label">예정된 보강</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($attendanceData['neededMakeup'], 1); ?></div>
                                <div class="stat-label">보강 필요</div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="records" class="tab-content">
                        <h3>최근 출결 기록</h3>
                        <table>
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
                    </div>
                    
                    <div id="schedule" class="tab-content">
                        <h3>주간 시간표</h3>
                        <?php if ($schedule): ?>
                            <div class="schedule-grid">
                                <?php 
                                $days = array('월', '화', '수', '목', '금', '토', '일');
                                for ($i = 1; $i <= 7; $i++):
                                    $duration_field = 'duration' . $i;
                                    $start_field = 'start' . $i;
                                    $is_active = isset($schedule->$duration_field) && $schedule->$duration_field > 0;
                                ?>
                                    <div class="schedule-day <?php echo $is_active ? 'active' : ''; ?>">
                                        <strong><?php echo $days[$i-1]; ?>요일</strong><br>
                                        <?php if ($is_active): ?>
                                            <?php echo $schedule->$start_field; ?><br>
                                            <?php echo $schedule->$duration_field; ?>시간
                                        <?php else: ?>
                                            휴무
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div style="margin-top: 20px;">
                                <strong>주간 총 시간:</strong> 
                                <?php 
                                $total = 0;
                                for ($i = 1; $i <= 7; $i++) {
                                    $field = 'duration' . $i;
                                    if (isset($schedule->$field)) {
                                        $total += $schedule->$field;
                                    }
                                }
                                echo $total . '시간';
                                ?>
                            </div>
                        <?php else: ?>
                            <p>시간표 정보가 없습니다.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div id="add" class="tab-content">
                        <h3>출결 기록 추가</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_record">
                            
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
                                <label>메모</label>
                                <input type="text" name="memo" placeholder="선택사항">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">추가하기</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p>학생을 선택해주세요.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // 모든 탭과 내용 숨기기
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // 선택된 탭 활성화
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</body>
</html>