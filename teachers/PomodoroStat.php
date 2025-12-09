<?php 
/////////////////////////////// 수학일기 반 통계 ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$teacherid = $_GET['teacherid'] ?? $USER->id;
$selectedWeek = $_GET['week'] ?? null; // 선택된 주 (0=지난주, 1=2주전, ...)

// 담당 학생 목록 가져오기 (dashboard_fixnotes.php 로직 사용)
$collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol = $teacher->symbol ?? '##';
$teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1 = $teacher1->symbol ?? '##';
$teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2 = $teacher2->symbol ?? '##';
$teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3 = $teacher3->symbol ?? '##';

$timecreated = time();
$aweekago = $timecreated - 604800;

$students = $DB->get_records_sql("SELECT * FROM mdl_user WHERE suspended='0' AND lastaccess> '$aweekago' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%' ) ORDER BY id DESC ");  

$result = json_decode(json_encode($students), True);

// 주 단위 계산 함수 (월요일~일요일 기준)
function getWeekRange($weekOffset) {
    $now = time();
    $today = date('w', $now); // 0=일요일, 1=월요일, ..., 6=토요일
    
    // 오늘의 시작 시간 (00:00:00)
    $todayStart = mktime(0, 0, 0, date('n', $now), date('j', $now), date('Y', $now));
    
    // 오늘이 일요일이면 지난 주 일요일, 아니면 이번 주 일요일 찾기
    if ($today == 0) {
        // 오늘이 일요일이면 지난 주 일요일
        $lastSunday = $todayStart;
    } else {
        // 오늘이 일요일이 아니면 이번 주 일요일 (오늘에서 $today일 전)
        $thisSunday = $todayStart - ($today * 86400);
        // 지난 주 일요일
        $lastSunday = $thisSunday - (7 * 86400);
    }
    
    // 주 오프셋만큼 빼기
    $targetSunday = $lastSunday - ($weekOffset * 7 * 86400);
    
    // 해당 주의 월요일 (일요일에서 6일 전, 즉 지난 월요일)
    $monday = $targetSunday - (6 * 86400);
    
    // 해당 주의 일요일 (월요일에서 6일 후, 23:59:59)
    $sunday = $monday + (6 * 86400) + 86399;
    
    return [
        'start' => $monday,
        'end' => $sunday,
        'label' => date('Y-m-d', $monday) . ' ~ ' . date('Y-m-d', $sunday)
    ];
}

// 주별 통계 계산 함수
function calculateWeekStats($DB, $studentIds, $weekStart, $weekEnd) {
    $stats = [
        'pomodoro_count' => 0,
        'satisfaction_sum' => 0,
        'satisfaction_count' => 0,
        'plan_time_sum' => 0,
        'plan_time_count' => 0,
        'actual_time_sum' => 0,
        'actual_time_count' => 0,
        'teacher_notes_count' => 0,
        'student_notes' => [] // 학생별 메모 저장
    ];
    
    if (empty($studentIds)) {
        return $stats;
    }
    
    // SQL 인젝션 방지를 위해 정수 배열로 변환
    $studentIdsInt = array_map('intval', $studentIds);
    $studentIdsStr = implode(',', $studentIdsInt);
    
    try {
        // 포모도르 기록 조회 (완료된 활동만)
        $pomodoros = $DB->get_records_sql(
            "SELECT * FROM mdl_abessi_tracking 
             WHERE userid IN ($studentIdsStr) 
             AND status = 'complete' 
             AND timefinished > 0 
             AND timecreated >= ? 
             AND timecreated <= ? 
             AND hide = 0",
            [$weekStart, $weekEnd]
        );
        
        if ($pomodoros) {
            foreach ($pomodoros as $pomo) {
                $stats['pomodoro_count']++;
                
                // 만족도 (result: 3=매우만족, 2=만족, 1=불만족)
                if (isset($pomo->result) && $pomo->result > 0) {
                    $stats['satisfaction_sum'] += (int)$pomo->result;
                    $stats['satisfaction_count']++;
                }
                
                // 계획 시간 (duration - timecreated, 분 단위)
                if (isset($pomo->duration) && isset($pomo->timecreated) && $pomo->duration > $pomo->timecreated) {
                    $planMinutes = ($pomo->duration - $pomo->timecreated) / 60;
                    $stats['plan_time_sum'] += $planMinutes;
                    $stats['plan_time_count']++;
                }
                
                // 실행 시간 (timefinished - timecreated, 분 단위)
                if (isset($pomo->timefinished) && isset($pomo->timecreated) && $pomo->timefinished > $pomo->timecreated) {
                    $actualMinutes = ($pomo->timefinished - $pomo->timecreated) / 60;
                    $stats['actual_time_sum'] += $actualMinutes;
                    $stats['actual_time_count']++;
                }
            }
        }
        
        // 선생님 메모 조회
        $notes = $DB->get_records_sql(
            "SELECT sn.*, u.lastname, u.firstname, uid.data AS author_role 
             FROM mdl_abessi_stickynotes sn 
             LEFT JOIN mdl_user u ON sn.userid = u.id
             LEFT JOIN mdl_user_info_data uid ON sn.authorid = uid.userid AND uid.fieldid = 22
             WHERE sn.userid IN ($studentIdsStr) 
             AND sn.hide = 0 
             AND sn.type = 'timescaffolding' 
             AND sn.created_at >= ? 
             AND sn.created_at <= ? 
             AND (uid.data IS NULL OR uid.data != 'student')
             ORDER BY sn.created_at DESC",
            [$weekStart, $weekEnd]
        );
        
        if ($notes) {
            foreach ($notes as $note) {
                $stats['teacher_notes_count']++;
                
                $studentName = (isset($note->lastname) ? $note->lastname : '') . (isset($note->firstname) ? $note->firstname : '');
                $userId = isset($note->userid) ? (int)$note->userid : 0;
                
                if ($userId > 0) {
                    if (!isset($stats['student_notes'][$userId])) {
                        $stats['student_notes'][$userId] = [
                            'name' => $studentName,
                            'notes' => []
                        ];
                    }
                    
                    $noteCreatedAt = isset($note->created_at) ? (int)$note->created_at : 0;
                    $stats['student_notes'][$userId]['notes'][] = [
                        'content' => isset($note->content) ? $note->content : '',
                        'created_at' => $noteCreatedAt,
                        'date' => $noteCreatedAt > 0 ? date('Y-m-d H:i', $noteCreatedAt) : ''
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log("PomodoroStat.php - calculateWeekStats 오류 (라인: " . __LINE__ . "): " . $e->getMessage());
    }
    
    return $stats;
}

// 학생별 포모도르 기록 수 집계 함수
function getStudentPomodoroCounts($DB, $studentIds, $weekStart, $weekEnd) {
    $studentCounts = [];
    
    if (empty($studentIds)) {
        return $studentCounts;
    }
    
    // SQL 인젝션 방지를 위해 정수 배열로 변환
    $studentIdsInt = array_map('intval', $studentIds);
    $studentIdsStr = implode(',', $studentIdsInt);
    
    try {
        // 학생별 포모도르 기록 수 조회 (완료된 활동만)
        $pomodoros = $DB->get_records_sql(
            "SELECT userid, COUNT(*) as count 
             FROM mdl_abessi_tracking 
             WHERE userid IN ($studentIdsStr) 
             AND status = 'complete' 
             AND timefinished > 0 
             AND timecreated >= ? 
             AND timecreated <= ? 
             AND hide = 0
             GROUP BY userid",
            [$weekStart, $weekEnd]
        );
        
        if ($pomodoros) {
            foreach ($pomodoros as $pomo) {
                $userId = isset($pomo->userid) ? (int)$pomo->userid : 0;
                $count = isset($pomo->count) ? (int)$pomo->count : 0;
                if ($userId > 0) {
                    $studentCounts[$userId] = $count;
                }
            }
        }
        
        // 기록이 없는 학생은 0으로 설정
        foreach ($studentIdsInt as $studentId) {
            if (!isset($studentCounts[$studentId])) {
                $studentCounts[$studentId] = 0;
            }
        }
    } catch (Exception $e) {
        error_log("PomodoroStat.php - getStudentPomodoroCounts 오류 (라인: " . __LINE__ . "): " . $e->getMessage());
    }
    
    return $studentCounts;
}

// 학생 ID 배열 생성
$studentIds = [];
$studentNames = [];
if (!empty($result)) {
    foreach ($result as $value) {
        if (isset($value['id'])) {
            $userid = (int)$value['id'];
            $studentIds[] = $userid;
            try {
                $std = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id=?", [$userid]);
                if ($std) {
                    $studentNames[$userid] = (isset($std->lastname) ? $std->lastname : '') . (isset($std->firstname) ? $std->firstname : '');
                } else {
                    $studentNames[$userid] = '학생 #' . $userid;
                }
            } catch (Exception $e) {
                error_log("PomodoroStat.php - 학생 정보 조회 오류 (라인: " . __LINE__ . "): " . $e->getMessage());
                $studentNames[$userid] = '학생 #' . $userid;
            }
        }
    }
}

// 최근 12주 데이터 수집
$weeksData = [];
if (!empty($studentIds)) {
    for ($i = 0; $i < 12; $i++) {
        try {
            $weekRange = getWeekRange($i);
            $weekStats = calculateWeekStats($DB, $studentIds, $weekRange['start'], $weekRange['end']);
            
            // 평균 계산
            $avgSatisfaction = $weekStats['satisfaction_count'] > 0 
                ? round($weekStats['satisfaction_sum'] / $weekStats['satisfaction_count'], 2) 
                : 0;
            $avgPlanTime = $weekStats['plan_time_count'] > 0 
                ? round($weekStats['plan_time_sum'] / $weekStats['plan_time_count'], 1) 
                : 0;
            $avgActualTime = $weekStats['actual_time_count'] > 0 
                ? round($weekStats['actual_time_sum'] / $weekStats['actual_time_count'], 1) 
                : 0;
            
            $weeksData[$i] = [
                'label' => $weekRange['label'],
                'start' => $weekRange['start'],
                'end' => $weekRange['end'],
                'pomodoro_count' => $weekStats['pomodoro_count'],
                'avg_satisfaction' => $avgSatisfaction,
                'avg_plan_time' => $avgPlanTime,
                'avg_actual_time' => $avgActualTime,
                'teacher_notes_count' => $weekStats['teacher_notes_count'],
                'student_notes' => $weekStats['student_notes']
            ];
        } catch (Exception $e) {
            error_log("PomodoroStat.php - 주별 데이터 수집 오류 (주: $i, 라인: " . __LINE__ . "): " . $e->getMessage());
            $weekRange = getWeekRange($i);
            $weeksData[$i] = [
                'label' => $weekRange['label'],
                'start' => $weekRange['start'],
                'end' => $weekRange['end'],
                'pomodoro_count' => 0,
                'avg_satisfaction' => 0,
                'avg_plan_time' => 0,
                'avg_actual_time' => 0,
                'teacher_notes_count' => 0,
                'student_notes' => []
            ];
        }
    }
} else {
    // 학생이 없는 경우 빈 데이터 생성
    for ($i = 0; $i < 12; $i++) {
        $weekRange = getWeekRange($i);
        $weeksData[$i] = [
            'label' => $weekRange['label'],
            'start' => $weekRange['start'],
            'end' => $weekRange['end'],
            'pomodoro_count' => 0,
            'avg_satisfaction' => 0,
            'avg_plan_time' => 0,
            'avg_actual_time' => 0,
            'teacher_notes_count' => 0,
            'student_notes' => []
        ];
    }
}

// 선택된 주 결정 (기본값: 지난 주 = 0)
$selectedWeekIndex = isset($selectedWeek) ? (int)$selectedWeek : 0;
if ($selectedWeekIndex < 0 || $selectedWeekIndex >= 12) {
    $selectedWeekIndex = 0;
}

$selectedWeekData = $weeksData[$selectedWeekIndex];

// 선택된 주의 학생별 포모도르 기록 수 계산
$selectedWeekRange = getWeekRange($selectedWeekIndex);
$studentPomodoroCounts = getStudentPomodoroCounts($DB, $studentIds, $selectedWeekRange['start'], $selectedWeekRange['end']);

// 포모도르 기록이 3개 이하인 학생 필터링
$lowActivityStudents = [];
foreach ($studentPomodoroCounts as $userId => $count) {
    if ($count <= 3) {
        $lowActivityStudents[$userId] = [
            'name' => isset($studentNames[$userId]) ? $studentNames[$userId] : '학생 #' . $userId,
            'count' => $count
        ];
    }
}

// 전체 학생 데이터 준비 (포모도르 수 기준 정렬)
$allStudentsData = [];
foreach ($studentPomodoroCounts as $userId => $count) {
    $allStudentsData[] = [
        'id' => $userId,
        'name' => isset($studentNames[$userId]) ? $studentNames[$userId] : '학생 #' . $userId,
        'count' => $count
    ];
}
// 포모도르 수 기준으로 오름차순 정렬 (우측으로 갈수록 커지게)
usort($allStudentsData, function($a, $b) {
    return $a['count'] - $b['count'];
});

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학일기 반 통계</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: hidden;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .week-selector {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .week-selector select {
            padding: 8px 15px;
            font-size: 16px;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            cursor: pointer;
        }
        .stats-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        .stat-card {
            flex: 1 1 auto;
            min-width: 150px;
            padding: 12px 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-card-value {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
        }
        .notes-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .student-notes {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-left: 4px solid #4CAF50;
            border-radius: 3px;
        }
        .student-notes h3 {
            margin-top: 0;
            color: #4CAF50;
        }
        .note-item {
            padding: 10px;
            margin: 8px 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
        }
        .note-date {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }
        .note-content {
            color: #333;
        }
        .empty-notes {
            color: #999;
            font-style: italic;
            padding: 10px;
        }
        .stat-value {
            font-weight: bold;
            font-size: 1.1em;
        }
        .low-activity-section {
            margin-top: 30px;
            padding: 15px;
            background: #fff3e0;
            border-radius: 5px;
            border-left: 4px solid #ff9800;
        }
        .low-activity-section h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #e65100;
            font-size: 1.1em;
        }
        .low-activity-list {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .low-activity-item {
            padding: 6px 12px;
            background: white;
            border: 1px solid #ffcc80;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9em;
            min-width: fit-content;
        }
        .low-activity-item a {
            color: #e65100;
            text-decoration: none;
            font-weight: 500;
        }
        .low-activity-item a:hover {
            text-decoration: underline;
        }
        .low-activity-count {
            color: #ff6f00;
            font-weight: bold;
            font-size: 0.9em;
        }
        .histogram-section {
            margin-top: 30px;
            padding: 20px 0 20px 20px;
            background: #f9f9f9;
            border-radius: 5px;
            display: block;
            width: calc(100% + 20px);
            margin-left: -20px;
            margin-right: -20px;
            box-sizing: border-box;
        }
        .histogram-container {
            margin-top: 20px;
            padding: 20px 0 20px 20px;
            padding-right: 20px;
            background: white;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        .histogram-title {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2em;
            font-weight: bold;
        }
        .histogram-bar-container {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            margin-bottom: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .histogram-bar-wrapper {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 15px;
            max-width: 30px;
            margin: 0 1px;
        }
        .histogram-bar {
            width: 100%;
            min-height: 20px;
            border-radius: 3px 3px 0 0;
            transition: opacity 0.3s;
            cursor: pointer;
            position: relative;
        }
        .histogram-bar:hover {
            opacity: 0.8;
        }
        .histogram-bar.red {
            background-color: #f44336;
        }
        .histogram-bar.orange {
            background-color: #ff9800;
        }
        .histogram-bar.green {
            background-color: #4CAF50;
        }
        .histogram-label {
            margin-top: 5px;
            font-size: 0.75em;
            color: #666;
            text-align: center;
            white-space: nowrap;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .histogram-label a {
            display: inline-block;
            transform: rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
        }
        .histogram-count {
            font-size: 0.9em;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        .histogram-y-axis {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-right: 10px;
            min-width: 50px;
            text-align: right;
            color: #666;
            font-size: 0.85em;
        }
        .histogram-content {
            display: flex;
            flex: 1;
            overflow-x: hidden;
            padding-bottom: 10px;
            padding-right: 0;
            width: 100%;
        }
        .histogram-wrapper {
            display: flex;
            width: 100%;
            justify-content: flex-start;
        }
        .histogram-main-wrapper {
            display: flex;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 수학일기 반 통계</h1>
        
        <?php if (empty($studentIds)): ?>
            <div style="padding: 20px; background: #fff3e0; border-radius: 5px; margin: 20px 0;">
                <p style="color: #e65100; margin: 0;">담당 학생이 없습니다. (파일: PomodoroStat.php, 라인: <?php echo __LINE__; ?>)</p>
            </div>
        <?php else: ?>
        
        <div class="week-selector">
            <label for="weekSelect"><strong>주 선택:</strong></label>
            <select id="weekSelect" onchange="changeWeek(this.value)">
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $i == $selectedWeekIndex ? 'selected' : ''; ?>>
                        <?php echo $weeksData[$i]['label']; ?>
                        <?php if ($i == 0) echo ' (지난 주)'; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <h2>선택된 주 통계: <?php echo $selectedWeekData['label']; ?></h2>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-label">포모도르 기록 수</div>
                <div class="stat-card-value"><?php echo $selectedWeekData['pomodoro_count']; ?>개</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">만족도 평점</div>
                <div class="stat-card-value"><?php echo $selectedWeekData['avg_satisfaction'] > 0 ? number_format($selectedWeekData['avg_satisfaction'], 2) : '-'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">평균 계획 시간</div>
                <div class="stat-card-value"><?php echo $selectedWeekData['avg_plan_time']; ?>분</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">평균 실행 시간</div>
                <div class="stat-card-value"><?php echo $selectedWeekData['avg_actual_time']; ?>분</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">선생님 메모 수</div>
                <div class="stat-card-value"><?php echo $selectedWeekData['teacher_notes_count']; ?>개</div>
            </div>
        </div>
        
        <!-- 포모도르 기록이 3개 이하인 학생 목록 -->
        <div class="low-activity-section">
            <h2>⚠️ 포모도르 기록이 3개 이하인 학생 <span style="font-size: 0.85em; font-weight: normal; color: #666;">(총 <?php echo count($lowActivityStudents); ?>명)</span></h2>
            
            <?php if (!empty($lowActivityStudents)): ?>
                <div class="low-activity-list">
                    <?php foreach ($lowActivityStudents as $userId => $student): ?>
                        <div class="low-activity-item">
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $userId; ?>&tb=604800" 
                               target="_blank">
                                <?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <span class="low-activity-count"><?php echo $student['count']; ?>개</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-notes" style="padding: 10px; background: white; border-radius: 3px; font-size: 0.9em;">
                    포모도르 기록이 3개 이하인 학생이 없습니다. 👍
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 전체 학생 히스토그램 -->
        <?php if (!empty($allStudentsData)): ?>
            <div class="histogram-section">
                <div class="histogram-container">
                    <div class="histogram-title">📊 전체 학생 포모도르 기록 히스토그램</div>
                    
                    <?php
                    // 최대 포모도르 수 찾기 (Y축 범위 설정용)
                    $maxCount = 0;
                    foreach ($allStudentsData as $student) {
                        if ($student['count'] > $maxCount) {
                            $maxCount = $student['count'];
                        }
                    }
                    $maxHeight = 300; // 최대 높이 (px)
                    ?>
                    
                    <div class="histogram-main-wrapper">
                        <!-- Y축 -->
                        <div class="histogram-y-axis">
                            <div><?php echo $maxCount; ?></div>
                            <div><?php echo round($maxCount * 0.75); ?></div>
                            <div><?php echo round($maxCount * 0.5); ?></div>
                            <div><?php echo round($maxCount * 0.25); ?></div>
                            <div>0</div>
                        </div>
                        
                        <!-- 히스토그램 바 -->
                        <div class="histogram-content">
                            <div class="histogram-wrapper" style="display: flex; align-items: flex-end; gap: 2px; padding-left: 10px; padding-right: 0; width: 100%;">
                                <?php foreach ($allStudentsData as $student): ?>
                                    <?php
                                    $height = $maxCount > 0 ? ($student['count'] / $maxCount) * $maxHeight : 0;
                                    $colorClass = 'red';
                                    if ($student['count'] > 3 && $student['count'] <= 10) {
                                        $colorClass = 'orange';
                                    } elseif ($student['count'] > 10) {
                                        $colorClass = 'green';
                                    }
                                    ?>
                                    <div class="histogram-bar-wrapper">
                                        <div class="histogram-count"><?php echo $student['count']; ?></div>
                                        <div class="histogram-bar <?php echo $colorClass; ?>" 
                                             style="height: <?php echo max($height, 5); ?>px;"
                                             title="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $student['count']; ?>개"
                                             onclick="window.open('https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>', '_blank');">
                                        </div>
                                        <div class="histogram-label">
                                            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" 
                                               target="_blank" 
                                               style="color: #333; text-decoration: none;">
                                                <?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="notes-section">
            <h2>📝 선생님 메모 (학생별)</h2>
            
            <?php if (!empty($selectedWeekData['student_notes'])): ?>
                <?php foreach ($selectedWeekData['student_notes'] as $userId => $studentNote): ?>
                    <div class="student-notes">
                        <h3>
                            <?php echo htmlspecialchars($studentNote['name'], ENT_QUOTES, 'UTF-8'); ?>
                            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $userId; ?>" 
                               target="_blank" style="font-size: 0.8em; color: #666; text-decoration: none;">
                                (상세보기)
                            </a>
                        </h3>
                        
                        <?php if (!empty($studentNote['notes'])): ?>
                            <?php foreach ($studentNote['notes'] as $note): ?>
                                <div class="note-item">
                                    <div class="note-date"><?php echo htmlspecialchars($note['date'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="note-content">
                                        <?php 
                                        // 이미지 태그가 있으면 HTML 그대로 사용
                                        if (strpos($note['content'], '<img') !== false) {
                                            echo $note['content'];
                                        } else {
                                            echo htmlspecialchars($note['content'], ENT_QUOTES, 'UTF-8');
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-notes">메모가 없습니다.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-notes">선택된 주에 선생님 메모가 없습니다.</div>
            <?php endif; ?>
        </div>
        
        <?php endif; // 학생이 있는 경우 종료 ?>
    </div>
    
    <script>
        function changeWeek(weekIndex) {
            const url = new URL(window.location.href);
            url.searchParams.set('week', weekIndex);
            url.searchParams.set('teacherid', '<?php echo $teacherid; ?>');
            window.location.href = url.toString();
        }
        
    </script>
</body>
</html>

