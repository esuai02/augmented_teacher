<?php
/**
 * 일일 리포트 - 담당 학생 목록
 * 파일: dailyreport.php
 * 목적: 선생님의 담당 학생들의 ID와 이름을 나열
 */

// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// UTF-8mb4 연결 설정
try {
    $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Failed to set connection charset to utf8mb4 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
}

// 선생님 정보 가져오기
$teacherid = optional_param('userid', 0, PARAM_INT);
if (!$teacherid) {
    $teacherid = $USER->id;
}

// 선생님 정보 조회
$teacher = $DB->get_record('user', array('id' => $teacherid));
$teacherName = $teacher ? $teacher->firstname . ' ' . $teacher->lastname : '선생님';

// 선생님의 기호(symbol) 가져오기 (fieldid='79')
$teacherSymbol = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$teacherid]);
$tsymbol = $teacherSymbol && $teacherSymbol->symbol ? $teacherSymbol->symbol : 'KTM';

// 협력 선생님들의 기호 가져오기
$colleagues = $DB->get_record_sql("SELECT * FROM {abessi_teacher_setting} WHERE userid = ?", [$teacherid]);
$tsymbol1 = 'KTM';
$tsymbol2 = 'KTM';
$tsymbol3 = 'KTM';

if ($colleagues) {
    if ($colleagues->mntr1) {
        $teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr1]);
        $tsymbol1 = $teacher1 && $teacher1->symbol ? $teacher1->symbol : 'KTM';
    }
    if ($colleagues->mntr2) {
        $teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr2]);
        $tsymbol2 = $teacher2 && $teacher2->symbol ? $teacher2->symbol : 'KTM';
    }
    if ($colleagues->mntr3) {
        $teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr3]);
        $tsymbol3 = $teacher3 && $teacher3->symbol ? $teacher3->symbol : 'KTM';
    }
}

// 학원 정보 가져오기 (fieldid='46')
$academyInfo = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = '46'", [$teacherid]);
$academy = $academyInfo && $academyInfo->data ? $academyInfo->data : '';

// 반 학생 목록 가져오기 (기호로 식별)
$amonthago6 = time() - (30 * 24 * 60 * 60); // 30일 전
$studentList = [];

try {
    $students = $DB->get_records_sql("
        SELECT id, firstname, lastname 
        FROM {user} 
        WHERE institution LIKE ? 
        AND lastaccess > ? 
        AND (firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ?) 
        AND suspended = 0
        ORDER BY id DESC
    ", [
        $academy ? '%' . $academy . '%' : '%',
        $amonthago6,
        '%' . $tsymbol . '%',
        '%' . $tsymbol1 . '%',
        '%' . $tsymbol2 . '%',
        '%' . $tsymbol3 . '%'
    ]);
    
    foreach ($students as $student) {
        $studentList[] = [
            'id' => $student->id,
            'name' => $student->firstname . ' ' . $student->lastname
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching student list at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    $studentList = [];
}

$totalStudents = count($studentList);

// 각 학생의 마지막 활동 정보 조회
$oneWeekAgo = time() - (7 * 24 * 60 * 60);
$oneMonthAgo = time() - (30 * 24 * 60 * 60);
$studentDataList = [];

foreach ($studentList as $student) {
    $studentId = $student['id'];
    $studentData = [
        'id' => $studentId,
        'name' => $student['name'],
        'weekly_goal' => null,
        'today_goal' => null,
        'last_class_date' => null,
        'calmness_level' => null,
        'calmness_grade' => '',
        'error_note_backlog' => 0,
        'math_diary_count' => 0
    ];
    
    // 마지막 수업 날짜 조회 (mdl_abessi_today 테이블의 가장 최근 timecreated)
    try {
        $lastClassRecord = $DB->get_record_sql("
            SELECT MAX(timecreated) AS last_time
            FROM {abessi_today}
            WHERE userid = ?
        ", [$studentId]);
        
        if ($lastClassRecord && $lastClassRecord->last_time) {
            $studentData['last_class_date'] = $lastClassRecord->last_time;
            
            // 주간목표 조회 (가장 최근 것)
            $weeklyGoal = $DB->get_record_sql("
                SELECT text, timecreated
                FROM {abessi_today}
                WHERE userid = ? AND type LIKE '주간목표'
                ORDER BY id DESC
                LIMIT 1
            ", [$studentId]);
            
            if ($weeklyGoal) {
                $studentData['weekly_goal'] = $weeklyGoal->text;
            }
            
            // 오늘목표 조회 (가장 최근 것)
            $todayGoal = $DB->get_record_sql("
                SELECT text, timecreated
                FROM {abessi_today}
                WHERE userid = ? AND (type LIKE '오늘목표' OR type LIKE '검사요청')
                ORDER BY id DESC
                LIMIT 1
            ", [$studentId]);
            
            if ($todayGoal) {
                $studentData['today_goal'] = $todayGoal->text;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching goals for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    // 침착도 데이터 (가장 최근 값)
    try {
        $calmnessData = $DB->get_record_sql("
            SELECT level, timecreated
            FROM {alt42_calmness}
            WHERE userid = ? AND (hide IS NULL OR hide = 0)
            ORDER BY timecreated DESC
            LIMIT 1
        ", [$studentId]);
        
        if ($calmnessData) {
            $studentData['calmness_level'] = (int)$calmnessData->level;
            
            // 침착도 등급 계산
            $level = $studentData['calmness_level'];
            if ($level >= 95) $studentData['calmness_grade'] = 'A+';
            elseif ($level >= 90) $studentData['calmness_grade'] = 'A';
            elseif ($level >= 85) $studentData['calmness_grade'] = 'B+';
            elseif ($level >= 80) $studentData['calmness_grade'] = 'B';
            elseif ($level >= 75) $studentData['calmness_grade'] = 'C+';
            elseif ($level >= 70) $studentData['calmness_grade'] = 'C';
            else $studentData['calmness_grade'] = 'F';
        }
    } catch (Exception $e) {
        error_log("Error fetching calmness data for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    // 오답노트 밀림 확인
    try {
        // 최근 퀴즈 시작 지점 확인
        $latestQuizStart = $DB->get_record_sql("
            SELECT timestart
            FROM {quiz_attempts}
            WHERE userid = ? AND timestart > ?
            ORDER BY timestart DESC
            LIMIT 1
        ", [$studentId, $oneWeekAgo]);
        
        $latestQuizStartTime = $latestQuizStart ? $latestQuizStart->timestart : null;
        
        if ($latestQuizStartTime) {
            $studentData['error_note_backlog'] = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM {abessi_messages}
                WHERE userid = ?
                AND active = 1
                AND (status = 'begin' OR status = 'exam')
                AND contentstype = 2
                AND hide = 0
                AND timecreated < ?
                AND timecreated > ?
            ", [$studentId, $latestQuizStartTime, $oneWeekAgo]);
        } else {
            $studentData['error_note_backlog'] = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM {abessi_messages}
                WHERE userid = ?
                AND active = 1
                AND (status = 'begin' OR status = 'exam')
                AND contentstype = 2
                AND hide = 0
                AND timecreated > ?
            ", [$studentId, $oneWeekAgo]);
        }
    } catch (Exception $e) {
        error_log("Error checking error note backlog for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    // 수학일기 작성 개수 (마지막 수업 당일 기준) - index.php 방식 참고
    try {
        // 마지막 수업 날짜 기준으로 조회 (해당 날의 00:00:00 ~ 23:59:59)
        $diaryStartTime = null;
        $diaryEndTime = null;
        
        if ($studentData['last_class_date']) {
            // 마지막 수업 날짜의 시작 시간 (00:00:00)
            $diaryStartTime = strtotime(date('Y-m-d 00:00:00', $studentData['last_class_date']));
            // 마지막 수업 날짜의 종료 시간 (23:59:59)
            $diaryEndTime = strtotime(date('Y-m-d 23:59:59', $studentData['last_class_date']));
        } else {
            // 마지막 수업 날짜가 없으면 최근 1개월로 fallback
            $diaryStartTime = $oneMonthAgo;
            $diaryEndTime = time();
        }
        
        // 먼저 mdl_abessi_todayplans 테이블에서 조회
        $mathDiaryRecords = $DB->get_records_sql("
            SELECT 
                status01, status02, status03, status04, status05, status06, status07, status08,
                status09, status10, status11, status12, status13, status14, status15, status16,
                timecreated, id
            FROM mdl_abessi_todayplans
            WHERE userid = ? AND timecreated >= ? AND timecreated <= ?
            ORDER BY timecreated DESC
            LIMIT 100
        ", [$studentId, $diaryStartTime, $diaryEndTime]);
        
        $totalDiaryCount = 0;
        foreach ($mathDiaryRecords as $record) {
            for ($i = 1; $i <= 16; $i++) {
                $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!empty($record->$statusField)) {
                    $totalDiaryCount++;
                }
            }
        }
        
        // todayplans에 데이터가 없거나 부족한 경우 tracking 테이블도 확인 (fallback)
        if ($totalDiaryCount == 0) {
            $trackingRecords = $DB->get_records_sql("
                SELECT id, text, result, timecreated
                FROM mdl_abessi_tracking
                WHERE userid = ?
                AND timecreated >= ? AND timecreated <= ?
                AND hide = 0
                AND status = 'complete'
                AND result IS NOT NULL
                ORDER BY timecreated DESC
                LIMIT 100
            ", [$studentId, $diaryStartTime, $diaryEndTime]);
            
            if (!empty($trackingRecords)) {
                // tracking 레코드도 수학일기로 카운트
                $totalDiaryCount = count($trackingRecords);
            }
        }
        
        $studentData['math_diary_count'] = $totalDiaryCount;
    } catch (Exception $e) {
        error_log("Error fetching math diary count for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $studentData['math_diary_count'] = 0;
    }
    
    // 메모 관리 (최근 1개월)
    try {
        $memoCount = $DB->count_records_sql("
            SELECT COUNT(*)
            FROM {abessi_messages} 
            WHERE userid = ? 
            AND status = 'usernotebook' 
            AND timecreated >= ?
            AND hide = 0
        ", [$studentId, $oneMonthAgo]);
        
        $studentData['memo_count'] = (int)$memoCount;
    } catch (Exception $e) {
        error_log("Error fetching memo count for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $studentData['memo_count'] = 0;
    }
    
    $studentDataList[] = $studentData;
}

// 각 항목별로 문제 학생 식별 및 정렬

// 1. 침착도 낮은 학생 (level이 낮을수록 문제, 80 미만)
$calmnessIssues = array_filter($studentDataList, function($s) {
    return $s['calmness_level'] !== null && $s['calmness_level'] < 80;
});
usort($calmnessIssues, function($a, $b) {
    return $a['calmness_level'] - $b['calmness_level'];
});
$calmnessTop3 = array_slice($calmnessIssues, 0, 3);
$calmnessAll = $calmnessIssues;

// 2. 오답노트 밀린 학생 (backlog가 많을수록 문제)
$errorNoteIssues = array_filter($studentDataList, function($s) {
    return $s['error_note_backlog'] > 0;
});
usort($errorNoteIssues, function($a, $b) {
    return $b['error_note_backlog'] - $a['error_note_backlog'];
});
$errorNoteTop3 = array_slice($errorNoteIssues, 0, 3);
$errorNoteAll = $errorNoteIssues;

// 3. 수학일기 작성 개수 낮은 학생 (2개 이하)
$mathDiaryIssues = array_filter($studentDataList, function($s) {
    return $s['math_diary_count'] <= 2;
});
usort($mathDiaryIssues, function($a, $b) {
    return $a['math_diary_count'] - $b['math_diary_count'];
});
$mathDiaryTop3 = array_slice($mathDiaryIssues, 0, 3);
$mathDiaryAll = $mathDiaryIssues;

// 4. 메모 관리 (최근 1개월 메모가 없는 학생)
$memoIssues = array_filter($studentDataList, function($s) {
    return $s['memo_count'] == 0;
});
usort($memoIssues, function($a, $b) {
    $aTime = $a['last_class_date'] ?? 0;
    $bTime = $b['last_class_date'] ?? 0;
    return $aTime - $bTime; // 최근 활동이 적은 순
});
$memoTop3 = array_slice($memoIssues, 0, 3);
$memoAll = $memoIssues;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>일일 리포트 - 담당 학생 목록 - <?php echo htmlspecialchars($teacherName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .student-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .student-list {
            list-style: none;
        }
        
        .student-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-item:hover {
            background: #f8f9fa;
        }
        
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .student-name {
            font-weight: 600;
            color: #333;
            font-size: 18px;
        }
        
        .student-id {
            color: #666;
            font-size: 14px;
            font-family: monospace;
        }
        
        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
            margin-top: 8px;
        }
        
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
            margin-right: 8px;
            font-size: 13px;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .info-value.empty {
            color: #999;
            font-style: italic;
        }
        
        .calmness-grade {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .calmness-grade.A-plus {
            background: #d4edda;
            color: #155724;
        }
        
        .calmness-grade.A {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .calmness-grade.B-plus {
            background: #fff3cd;
            color: #856404;
        }
        
        .calmness-grade.B {
            background: #ffeaa7;
            color: #6c5ce7;
        }
        
        .calmness-grade.C-plus {
            background: #f8d7da;
            color: #721c24;
        }
        
        .calmness-grade.C {
            background: #f5c6cb;
            color: #721c24;
        }
        
        .calmness-grade.F {
            background: #f5c6cb;
            color: #721c24;
        }
        
        .calmness-grade.danger-highlight {
            animation: pulse 2s infinite;
            border: 2px solid #dc3545;
        }
        
        .goal-text {
            color: #333;
            line-height: 1.5;
            max-width: 100%;
            word-wrap: break-word;
        }
        
        .student-item.attention-needed {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
        }
        
        .student-item.attention-needed:hover {
            background: #ffe5e5;
        }
        
        .attention-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #dc3545;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        .info-item.warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        
        .info-item.danger {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }
        
        .last-class-date {
            color: #666;
            font-size: 14px;
            font-weight: normal;
            margin-left: 8px;
        }
        
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            padding: 10px 15px;
            background: #e8f4f8;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .stat-label {
            color: #666;
            margin-right: 5px;
        }
        
        .stat-value {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .message-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .message-card {
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .message-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .message-student-list {
            list-style: none;
            margin-bottom: 15px;
        }
        
        .message-student-item {
            padding: 12px 15px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        
        .message-student-name {
            font-weight: 500;
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .message-student-name:hover {
            color: #4a90e2;
        }
        
        .message-student-info {
            font-size: 14px;
            color: #666;
            margin-left: 8px;
        }
        
        .message-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .message-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 8px;
        }
        
        .message-text {
            color: #856404;
            font-size: 14px;
            margin-bottom: 10px;
            white-space: pre-line;
        }
        
        .copy-btn {
            padding: 8px 16px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .copy-btn:hover {
            background: #2d6cb0;
        }
        
        .more-btn {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
            transition: background 0.2s;
        }
        
        .more-btn:hover {
            background: #5a6268;
        }
        
        .hidden {
            display: none;
        }
        
        .copy-icon {
            display: inline-block;
            margin-left: 8px;
            cursor: pointer;
            color: #4a90e2;
            font-size: 16px;
            transition: color 0.2s;
        }
        
        .copy-icon:hover {
            color: #2d6cb0;
        }
        
        .custom-message-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            margin-top: 15px;
            box-sizing: border-box;
        }
        
        .custom-message-input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .popup-overlay.show {
            display: flex;
        }
        
        .popup-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .popup-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .popup-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .popup-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .popup-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .popup-btn-primary {
            background: #4a90e2;
            color: white;
        }
        
        .popup-btn-primary:hover {
            background: #2d6cb0;
        }
        
        .popup-btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .popup-btn-secondary:hover {
            background: #5a6268;
        }
        
        .kakao-guide {
            background: #FEE500;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 14px;
            color: #3C1E1E;
            line-height: 1.6;
        }
        
        .kakao-guide strong {
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .message-column {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .message-item {
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .message-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        
        .message-item-content {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #333;
        }
        
        .message-copy-btn {
            width: 100%;
            padding: 10px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .message-copy-btn:hover {
            background: #2d6cb0;
        }
        
        .message-copy-btn:active {
            background: #1e4d7a;
        }
        
        .student-column {
            min-width: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">일일 리포트 - 담당 학생 목록</h1>
            <p class="subtitle"><?php echo htmlspecialchars($teacherName); ?> 선생님의 담당 학생 목록</p>
            <div class="info">
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-label">담당 학생 수:</span>
                        <span class="stat-value"><?php echo $totalStudents; ?>명</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">기호:</span>
                        <span class="stat-value"><?php 
                            $symbols = [];
                            if ($tsymbol != 'KTM') {
                                $symbols[] = $tsymbol;
                            }
                            if ($tsymbol1 != 'KTM' && !in_array($tsymbol1, $symbols)) {
                                $symbols[] = $tsymbol1;
                            }
                            if ($tsymbol2 != 'KTM' && !in_array($tsymbol2, $symbols)) {
                                $symbols[] = $tsymbol2;
                            }
                            if ($tsymbol3 != 'KTM' && !in_array($tsymbol3, $symbols)) {
                                $symbols[] = $tsymbol3;
                            }
                            echo htmlspecialchars(implode(', ', $symbols));
                        ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <!-- 좌측 컬럼: 학생 목록 -->
            <div class="student-column">
                <div class="student-section">
            <h2 class="section-title">담당 학생 목록 및 활동 정보</h2>
            <?php if (count($studentDataList) > 0): ?>
                <ul class="student-list">
                    <?php foreach ($studentDataList as $student): 
                        // 주의 필요 여부 확인 (수학일기 2개 이하 또는 침착도 80% 미만)
                        $needsAttention = false;
                        $attentionReasons = [];
                        
                        if ($student['math_diary_count'] <= 2) {
                            $needsAttention = true;
                            $attentionReasons[] = '수학일기 부족';
                        }
                        
                        if ($student['calmness_level'] !== null && $student['calmness_level'] < 80) {
                            $needsAttention = true;
                            $attentionReasons[] = '침착도 낮음';
                        }
                    ?>
                        <li class="student-item <?php echo $needsAttention ? 'attention-needed' : ''; ?>">
                            <div class="student-header">
                                <span class="student-name">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                    <?php if ($student['last_class_date']): ?>
                                        <span class="last-class-date">(마지막 수업: <?php echo date('Y-m-d', $student['last_class_date']); ?>)</span>
                                    <?php endif; ?>
                                    <?php if ($needsAttention): ?>
                                        <span class="attention-badge">⚠ 주의 필요</span>
                                    <?php endif; ?>
                                </span>
                                <span class="student-id">ID: <?php echo $student['id']; ?></span>
                            </div>
                            <div class="student-info">
                                <!-- 주간목표 -->
                                <div class="info-item">
                                    <span class="info-label">📅 주간목표:</span>
                                    <span class="info-value <?php echo $student['weekly_goal'] ? '' : 'empty'; ?>">
                                        <?php echo $student['weekly_goal'] ? htmlspecialchars($student['weekly_goal']) : '없음'; ?>
                                    </span>
                                </div>
                                
                                <!-- 오늘목표 -->
                                <div class="info-item">
                                    <span class="info-label">🎯 오늘목표:</span>
                                    <span class="info-value <?php echo $student['today_goal'] ? '' : 'empty'; ?>">
                                        <?php echo $student['today_goal'] ? htmlspecialchars($student['today_goal']) : '없음'; ?>
                                    </span>
                                </div>
                                
                                <!-- 침착도 -->
                                <div class="info-item <?php echo ($student['calmness_level'] !== null && $student['calmness_level'] < 80) ? 'danger' : ''; ?>">
                                    <span class="info-label">😌 침착도:</span>
                                    <span class="info-value">
                                        <?php 
                                        if ($student['calmness_level'] !== null) {
                                            $gradeClass = str_replace('+', '-plus', $student['calmness_grade']);
                                            $isLow = $student['calmness_level'] < 80;
                                            echo '<span class="calmness-grade ' . htmlspecialchars($gradeClass) . ($isLow ? ' danger-highlight' : '') . '">';
                                            echo htmlspecialchars($student['calmness_grade']) . ' (' . $student['calmness_level'] . '%)';
                                            if ($isLow) {
                                                echo ' ⚠';
                                            }
                                            echo '</span>';
                                        } else {
                                            echo '<span class="empty">없음</span>';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <!-- 오답노트 밀림 수 -->
                                <div class="info-item">
                                    <span class="info-label">📝 오답노트 밀림:</span>
                                    <span class="info-value <?php echo $student['error_note_backlog'] > 0 ? '' : 'empty'; ?>">
                                        <?php echo $student['error_note_backlog']; ?>개
                                    </span>
                                </div>
                                
                                <!-- 수학일기 작성 수 -->
                                <div class="info-item <?php echo $student['math_diary_count'] <= 2 ? 'warning' : ''; ?>">
                                    <span class="info-label">📖 수학일기 작성:</span>
                                    <span class="info-value <?php echo $student['math_diary_count'] > 0 ? '' : 'empty'; ?>">
                                        <?php 
                                        $isLow = $student['math_diary_count'] <= 2;
                                        echo $student['math_diary_count']; 
                                        if ($student['last_class_date']) {
                                            echo '개 (마지막 수업 당일: ' . date('Y-m-d', $student['last_class_date']) . ')';
                                        } else {
                                            echo '개 (전체 기간)';
                                        }
                                        if ($isLow) {
                                            echo ' ⚠';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty">
                    <div class="empty-icon">👥</div>
                    <p>담당 학생이 없습니다.</p>
                </div>
            <?php endif; ?>
                </div>
            </div>
            
            <!-- 우측 컬럼: 상황별 메시지 발송 -->
            <div class="message-column">
                <h2 class="section-title">상황별 메시지 발송하기</h2>
                
                <!-- 오답노트 -->
                <div class="message-item">
                    <div class="message-item-title">📝 오답노트</div>
                    <div class="message-item-content" id="error-note-template">오답노트를 정리하는 것은 단순히 틀린 문제를 다시 풀어보는 것이 아닙니다. 자신의 사고 과정을 되돌아보고, 왜 그런 실수를 했는지 깊이 있게 분석하는 과정입니다. 

오답노트를 통해 같은 실수를 반복하지 않게 되고, 문제 해결 능력이 한 단계 성장합니다. 꾸준히 오답노트를 작성하는 학생일수록 실력 향상이 빠릅니다.

https://claude.ai/public/artifacts/4d45787d-a0cf-4bb3-9c39-82397b4d1e91?fullscreen=true</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('error-note-template')">📋 복사하기</button>
                </div>
                
                <!-- 목표작성 -->
                <div class="message-item">
                    <div class="message-item-title">🎯 목표작성</div>
                    <div class="message-item-content" id="goal-template">명확한 목표가 있을 때 우리의 뇌는 그 목표를 달성하기 위한 방법을 자동으로 찾아냅니다. 

목표를 구체적으로 작성하면 할수록, 그 목표를 달성할 가능성이 높아집니다. 단순히 "공부를 열심히 하겠다"가 아니라 "이번 주에는 이 단원의 기본 문제 20개를 완벽하게 풀겠다"처럼 구체적으로 작성해보세요.

https://claude.ai/public/artifacts/d09cd25d-308c-4c87-b125-a16177f8b6f6?fullscreen=true</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('goal-template')">📋 복사하기</button>
                </div>
                
                <!-- 침착도 관리 -->
                <div class="message-item">
                    <div class="message-item-title">😌 침착도 관리</div>
                    <div class="message-item-content" id="calmness-template">조급한 마음은 수학 공부의 가장 큰 적입니다. 문제를 풀 때 마음이 급하면 실수를 하기 쉽고, 집중력이 떨어지게 됩니다.

침착하게 문제를 읽고, 차근차근 풀이 과정을 따라가며, 한 번에 정확하게 풀어내는 습관이 중요합니다. 침착도가 높을수록 문제 해결 능력도 함께 향상됩니다.

https://claude.ai/public/artifacts/252aa428-b04c-4290-9cd4-013bce64f4da?fullscreen=true</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('calmness-template')">📋 복사하기</button>
                </div>
                
                <!-- 수학일기 -->
                <div class="message-item">
                    <div class="message-item-title">📔 수학일기</div>
                    <div class="message-item-content" id="math-diary-template">수학일기를 작성하는 것은 단순히 공부한 내용을 기록하는 것이 아닙니다. 자신의 학습 과정을 되돌아보고, 무엇을 배웠는지, 어떤 부분이 어려웠는지, 어떻게 개선할 수 있을지 생각해보는 시간입니다.

시간을 다루는 학생이 성적을 만듭니다. 수학일기를 통해 자신의 학습 패턴을 파악하고, 더 효율적인 공부 방법을 찾아가세요.

https://claude.ai/public/artifacts/b75b7f55-5ebd-44b6-b015-eebbf91d7b3e?fullscreen=true</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('math-diary-template')">📋 복사하기</button>
                </div>
                
                <!-- 자기설명 -->
                <div class="message-item">
                    <div class="message-item-title">💭 자기설명</div>
                    <div class="message-item-content" id="self-explanation-template">문제를 풀 때 자신에게 설명하듯이 풀이 과정을 말해보세요. "왜 이 공식을 사용하는가?", "이 단계에서 무엇을 해야 하는가?"를 스스로에게 설명하다 보면 개념이 더 명확해집니다.

자기설명을 통해 단순 암기가 아닌 진정한 이해를 할 수 있습니다. 문제를 풀 때마다 "왜?"를 스스로에게 물어보는 습관을 기르세요.

https://claude.ai/public/artifacts/e549dd41-9650-4982-8d59-82e45801d7ab</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('self-explanation-template')">📋 복사하기</button>
                </div>
                
                <!-- 자기인정 -->
                <div class="message-item">
                    <div class="message-item-title">✨ 자기인정</div>
                    <div class="message-item-content" id="self-recognition-template">작은 성취도 인정하고 칭찬하는 것이 중요합니다. 오늘 한 문제를 더 풀었거나, 어제보다 조금 더 집중했다면 그것도 충분히 자랑스러운 일입니다.

자기인정을 통해 자신감을 키우고, 그 자신감이 더 큰 도전을 할 수 있는 힘이 됩니다. 자신의 노력과 성장을 인정하는 것, 이것이 지속적인 학습 동기의 원동력입니다.

https://claude.ai/public/artifacts/018db32d-6041-4740-b630-6cb8d6f354e8?fullscreen=true</div>
                    <button class="message-copy-btn" onclick="copyTemplateMessage('self-recognition-template')">📋 복사하기</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 팝업 오버레이 -->
    <div id="message-popup" class="popup-overlay">
        <div class="popup-content">
            <div class="popup-title">메시지 복사 완료</div>
            <div class="popup-message" id="popup-message-content"></div>
            <div class="kakao-guide">
                <strong>📱 카카오톡으로 전송하기</strong>
                1. 카카오톡 앱을 열어주세요<br>
                2. 해당 학생과의 채팅방으로 이동하세요<br>
                3. 입력창을 길게 눌러 "붙여넣기"를 선택하세요<br>
                4. 메시지를 확인하고 전송하세요
            </div>
            <div class="popup-buttons">
                <button class="popup-btn popup-btn-secondary" onclick="closePopup()">닫기</button>
                <button class="popup-btn popup-btn-primary" onclick="copyPopupMessage()">다시 복사</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentMessageText = '';
        
        function copyTemplateMessage(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            currentMessageText = text;
            
            // 클립보드에 복사
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showPopup(text);
                }).catch(function(err) {
                    console.error('복사 실패:', err);
                    fallbackCopyTextToClipboard(text);
                    showPopup(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
                showPopup(text);
            }
        }
        
        function showPopup(message) {
            const popup = document.getElementById('message-popup');
            const popupContent = document.getElementById('popup-message-content');
            popupContent.textContent = message;
            popup.classList.add('show');
        }
        
        function closePopup() {
            const popup = document.getElementById('message-popup');
            popup.classList.remove('show');
        }
        
        function copyPopupMessage() {
            if (currentMessageText) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(currentMessageText).then(function() {
                        alert('메시지가 다시 클립보드에 복사되었습니다!');
                    }).catch(function(err) {
                        console.error('복사 실패:', err);
                        fallbackCopyTextToClipboard(currentMessageText);
                    });
                } else {
                    fallbackCopyTextToClipboard(currentMessageText);
                }
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    // 성공 메시지는 showPopup에서 처리
                } else {
                    alert('복사에 실패했습니다. 수동으로 복사해주세요.');
                }
            } catch (err) {
                console.error('복사 실패:', err);
                alert('복사에 실패했습니다. 수동으로 복사해주세요.');
            }
            
            document.body.removeChild(textArea);
        }
        
        // 팝업 외부 클릭 시 닫기
        document.getElementById('message-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    </script>
</body>
</html>

