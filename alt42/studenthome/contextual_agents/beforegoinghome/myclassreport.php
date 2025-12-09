<?php
/**
 * 선생님용 일일 리포트 생성 페이지
 * 파일: myclassreport.php
 * 목적: 반 전체 학생의 학습 데이터를 분석하여 문제가 있는 학생들을 식별하고 개선 방안 제시
 */

// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

// 실행 시간 제한 증가 (학생 수가 많을 경우 대비)
set_time_limit(300); // 5분

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
$amonthago = time() - (30 * 24 * 60 * 60); // 30일 전
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
        $amonthago,
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

$studentIds = array_column($studentList, 'id');
$totalStudents = count($studentIds);

// 시간 범위 설정
$now = time();
$oneWeekAgo = $now - (7 * 24 * 60 * 60);
$oneMonthAgo = $now - (30 * 24 * 60 * 60);
$aweekago = $now - (7 * 24 * 60 * 60);

// 학생별 데이터 분석
$studentData = [];

// 진행 상황 로깅 (디버깅용)
$totalStudents = count($studentList);
error_log("Starting data analysis for {$totalStudents} students at " . __FILE__ . ":" . __LINE__);

$processedCount = 0;
foreach ($studentList as $student) {
    $processedCount++;
    if ($processedCount % 10 == 0) {
        error_log("Processed {$processedCount}/{$totalStudents} students at " . __FILE__ . ":" . __LINE__);
    }
    $studentId = $student['id'];
    $data = [
        'id' => $studentId,
        'name' => $student['name'],
        'calmness_level' => null,
        'calmness_timecreated' => null,
        'error_note_backlog' => 0,
        'math_diary_count' => 0,
        'memo_count' => 0,
        'last_learning_time' => null
    ];
    
    // 1. 침착도 데이터 (가장 최근 값)
    try {
        $calmnessData = $DB->get_record_sql("
            SELECT level, timecreated
            FROM mdl_alt42_calmness 
            WHERE userid = ? AND (hide IS NULL OR hide = 0)
            ORDER BY timecreated DESC 
            LIMIT 1
        ", [$studentId]);
        
        if ($calmnessData) {
            $data['calmness_level'] = (int)$calmnessData->level;
            $data['calmness_timecreated'] = $calmnessData->timecreated;
        }
    } catch (Exception $e) {
        error_log("Error fetching calmness data for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    // 2. 오답노트 밀림 확인
    try {
        // 최근 퀴즈 시작 지점 확인
        $latestQuizStart = $DB->get_record_sql("
            SELECT timestart 
            FROM mdl_quiz_attempts 
            WHERE userid = ? AND timestart > ?
            ORDER BY timestart DESC 
            LIMIT 1
        ", [$studentId, $oneWeekAgo]);
        
        $latestQuizStartTime = $latestQuizStart ? $latestQuizStart->timestart : null;
        
        if ($latestQuizStartTime) {
            // COUNT 쿼리로 최적화
            $data['error_note_backlog'] = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM mdl_abessi_messages 
                WHERE userid = ? 
                AND active = 1
                AND (status = 'begin' OR status = 'exam')
                AND contentstype = 2
                AND hide = 0 
                AND timecreated < ?
                AND timecreated > ?
            ", [$studentId, $latestQuizStartTime, $oneWeekAgo]);
        } else {
            // 퀴즈 시작 지점이 없으면 최근 일주일 내 active=1이고 status='begin' 또는 status='exam'인 것만 카운트
            // COUNT 쿼리로 최적화
            $data['error_note_backlog'] = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM mdl_abessi_messages 
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
    
    // 3. 수학일기 작성 개수 (최근 1개월) - LIMIT 추가하여 최적화
    try {
        $mathDiaryRecords = $DB->get_records_sql("
            SELECT 
                status01, status02, status03, status04, status05, status06, status07, status08,
                status09, status10, status11, status12, status13, status14, status15, status16
            FROM mdl_abessi_todayplans 
            WHERE userid = ? AND timecreated >= ?
            ORDER BY timecreated DESC
            LIMIT 100
        ", [$studentId, $oneMonthAgo]);
        
        $totalDiaryCount = 0;
        foreach ($mathDiaryRecords as $record) {
            for ($i = 1; $i <= 16; $i++) {
                $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!empty($record->$statusField)) {
                    $totalDiaryCount++;
                }
            }
        }
        $data['math_diary_count'] = $totalDiaryCount;
    } catch (Exception $e) {
        error_log("Error fetching math diary count for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $data['math_diary_count'] = 0;
    }
    
    // 4. 메모 관리 (최근 1개월) - COUNT 쿼리 최적화
    try {
        $memoCount = $DB->count_records_sql("
            SELECT COUNT(*)
            FROM mdl_abessi_messages 
            WHERE userid = ? 
            AND status = 'usernotebook' 
            AND timecreated >= ?
            AND hide = 0
        ", [$studentId, $oneMonthAgo]);
        
        $data['memo_count'] = (int)$memoCount;
    } catch (Exception $e) {
        error_log("Error fetching memo count for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $data['memo_count'] = 0;
    }
    
    // 5. 마지막 학습 시점 확인 - 개별 쿼리로 분리하여 최적화
    try {
        $lastMissionLog = $DB->get_field_sql("
            SELECT MAX(timecreated) 
            FROM mdl_abessi_missionlog 
            WHERE userid = ?
        ", [$studentId]);
        
        $lastTracking = $DB->get_field_sql("
            SELECT MAX(timecreated) 
            FROM mdl_abessi_tracking 
            WHERE userid = ? AND hide = 0
        ", [$studentId]);
        
        $lastMessage = $DB->get_field_sql("
            SELECT MAX(timecreated) 
            FROM mdl_abessi_messages 
            WHERE userid = ? AND hide = 0
        ", [$studentId]);
        
        $maxTime = max(
            $lastMissionLog ? (int)$lastMissionLog : 0,
            $lastTracking ? (int)$lastTracking : 0,
            $lastMessage ? (int)$lastMessage : 0
        );
        
        if ($maxTime > 0) {
            $data['last_learning_time'] = $maxTime;
        }
    } catch (Exception $e) {
        error_log("Error fetching last learning time for student {$studentId} at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    }
    
    $studentData[] = $data;
}

error_log("Completed data analysis for all students at " . __FILE__ . ":" . __LINE__);

// 각 항목별로 문제 학생 식별 및 정렬

// 1. 침착도 낮은 학생 (level이 낮을수록 문제, 70 미만)
$calmnessIssues = array_filter($studentData, function($s) {
    return $s['calmness_level'] !== null && $s['calmness_level'] < 70;
});
usort($calmnessIssues, function($a, $b) {
    return $a['calmness_level'] - $b['calmness_level'];
});
$calmnessTop3 = array_slice($calmnessIssues, 0, 3);
$calmnessAll = $calmnessIssues;

// 2. 오답노트 밀린 학생 (backlog가 많을수록 문제)
$errorNoteIssues = array_filter($studentData, function($s) {
    return $s['error_note_backlog'] > 0;
});
usort($errorNoteIssues, function($a, $b) {
    return $b['error_note_backlog'] - $a['error_note_backlog'];
});
$errorNoteTop3 = array_slice($errorNoteIssues, 0, 3);
$errorNoteAll = $errorNoteIssues;

// 3. 수학일기 작성 개수 낮은 학생 (최근 1개월 기준, 평균보다 낮은 학생)
$avgDiaryCount = count($studentData) > 0 ? array_sum(array_column($studentData, 'math_diary_count')) / count($studentData) : 0;
$mathDiaryIssues = array_filter($studentData, function($s) use ($avgDiaryCount) {
    return $s['math_diary_count'] < max(5, $avgDiaryCount * 0.5); // 최소 5개 또는 평균의 50% 미만
});
usort($mathDiaryIssues, function($a, $b) {
    return $a['math_diary_count'] - $b['math_diary_count'];
});
$mathDiaryTop3 = array_slice($mathDiaryIssues, 0, 3);
$mathDiaryAll = $mathDiaryIssues;

// 4. 메모 관리 (최근 1개월 메모가 없는 학생)
$memoIssues = array_filter($studentData, function($s) {
    return $s['memo_count'] == 0;
});
usort($memoIssues, function($a, $b) {
    $aTime = $a['last_learning_time'] ?? 0;
    $bTime = $b['last_learning_time'] ?? 0;
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
    <title>일일 리포트 - <?php echo htmlspecialchars($teacherName); ?></title>
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
        
        .report__container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .report__header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .report__title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .report__subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .report__date {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .report__section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .report__section-title {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report__card {
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .report__card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .report__student-list {
            list-style: none;
            margin-bottom: 15px;
        }
        
        .report__student-item {
            padding: 12px 15px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report__student-name {
            font-weight: 500;
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .report__student-name:hover {
            color: #4a90e2;
        }
        
        .report__student-info {
            font-size: 14px;
            color: #666;
        }
        
        .report__message-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .report__message-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 8px;
        }
        
        .report__message-text {
            color: #856404;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .report__copy-btn {
            padding: 8px 16px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        
        .report__copy-btn:hover {
            background: #2d6cb0;
        }
        
        .report__more-btn {
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
        
        .report__more-btn:hover {
            background: #5a6268;
        }
        
        .report__hidden {
            display: none;
        }
        
        .report__back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .report__back-link:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="report__container">
        <a href="classdashboard.php?userid=<?php echo $teacherid; ?>" class="report__back-link">← 대시보드로 돌아가기</a>
        
        <div class="report__header">
            <h1 class="report__title">일일 리포트</h1>
            <p class="report__subtitle"><?php echo htmlspecialchars($teacherName); ?> 선생님 반 전체 학습 현황 분석</p>
            <div class="report__date">
                <strong>생성일:</strong> <?php echo date('Y년 n월 j일 H:i'); ?> | 
                <strong>반 학생 수:</strong> <?php echo $totalStudents; ?>명
            </div>
        </div>
        
        <!-- 침착도 낮은 학생 -->
        <div class="report__section">
            <h2 class="report__section-title">
                <span>😰 침착도 낮은 학생</span>
                <span style="font-size: 14px; font-weight: normal; color: #666;">총 <?php echo count($calmnessAll); ?>명</span>
            </h2>
            <?php if (count($calmnessTop3) > 0): ?>
                <div class="report__card">
                    <div class="report__card-title">주요 관찰 대상 (상위 3명)</div>
                    <ul class="report__student-list">
                        <?php foreach ($calmnessTop3 as $student): ?>
                            <li class="report__student-item">
                                <div>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $student['id']; ?>&tb=604800" 
                                       class="report__student-name" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                    <span class="report__student-info">
                                        (침착도: <?php echo $student['calmness_level']; ?>점)
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="report__message-box">
                        <div class="report__message-title">💡 개선 도움 문구</div>
                        <div class="report__message-text" id="calmness-message">
                            조급한 마음이 수학공부에 미치는 영향
                            
                            https://claude.ai/public/artifacts/b75b7f55-5ebd-44b6-b015-eebbf91d7b3e?fullscreen=true
                        </div>
                        <button class="report__copy-btn" onclick="copyMessage('calmness-message')">📋 메시지 복사</button>
                    </div>
                    
                    <?php if (count($calmnessAll) > 3): ?>
                        <button class="report__more-btn" onclick="toggleMore('calmness-more')">더보기 (<?php echo count($calmnessAll) - 3; ?>명)</button>
                        <div id="calmness-more" class="report__hidden">
                            <ul class="report__student-list" style="margin-top: 15px;">
                                <?php foreach (array_slice($calmnessAll, 3) as $student): ?>
                                    <li class="report__student-item">
                                        <div>
                                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $student['id']; ?>&tb=604800" 
                                               class="report__student-name" 
                                               target="_blank">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </a>
                                            <span class="report__student-info">
                                                (침착도: <?php echo $student['calmness_level']; ?>점)
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    침착도 문제가 있는 학생이 없습니다. 👍
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 오답노트 밀린 학생 -->
        <div class="report__section">
            <h2 class="report__section-title">
                <span>📝 오답노트 밀린 학생</span>
                <span style="font-size: 14px; font-weight: normal; color: #666;">총 <?php echo count($errorNoteAll); ?>명</span>
            </h2>
            <?php if (count($errorNoteTop3) > 0): ?>
                <div class="report__card">
                    <div class="report__card-title">주요 관찰 대상 (상위 3명)</div>
                    <ul class="report__student-list">
                        <?php foreach ($errorNoteTop3 as $student): ?>
                            <li class="report__student-item">
                                <div>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $student['id']; ?>&tb=604800" 
                                       class="report__student-name" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                    <span class="report__student-info">
                                        (미완료 오답노트: <?php echo $student['error_note_backlog']; ?>개)
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="report__message-box">
                        <div class="report__message-title">💡 개선 도움 문구</div>
                        <div class="report__message-text" id="error-note-message">
                            순서를 바꾸면 뇌가 혼란을 겪고 지치게 되는 이유
                            
                            https://claude.ai/public/artifacts/b75b7f55-5ebd-44b6-b015-eebbf91d7b3e?fullscreen=true
                        </div>
                        <button class="report__copy-btn" onclick="copyMessage('error-note-message')">📋 메시지 복사</button>
                    </div>
                    
                    <?php if (count($errorNoteAll) > 3): ?>
                        <button class="report__more-btn" onclick="toggleMore('error-note-more')">더보기 (<?php echo count($errorNoteAll) - 3; ?>명)</button>
                        <div id="error-note-more" class="report__hidden">
                            <ul class="report__student-list" style="margin-top: 15px;">
                                <?php foreach (array_slice($errorNoteAll, 3) as $student): ?>
                                    <li class="report__student-item">
                                        <div>
                                            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $student['id']; ?>&tb=604800" 
                                               class="report__student-name" 
                                               target="_blank">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </a>
                                            <span class="report__student-info">
                                                (미완료 오답노트: <?php echo $student['error_note_backlog']; ?>개)
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    오답노트 밀림 문제가 있는 학생이 없습니다. 👍
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 수학일기 작성 개수 낮은 학생 -->
        <div class="report__section">
            <h2 class="report__section-title">
                <span>📔 수학일기 작성 개수 낮은 학생</span>
                <span style="font-size: 14px; font-weight: normal; color: #666;">총 <?php echo count($mathDiaryAll); ?>명</span>
            </h2>
            <?php if (count($mathDiaryTop3) > 0): ?>
                <div class="report__card">
                    <div class="report__card-title">주요 관찰 대상 (상위 3명)</div>
                    <ul class="report__student-list">
                        <?php foreach ($mathDiaryTop3 as $student): ?>
                            <li class="report__student-item">
                                <div>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" 
                                       class="report__student-name" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                    <span class="report__student-info">
                                        (최근 1개월 수학일기: <?php echo $student['math_diary_count']; ?>개)
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="report__message-box">
                        <div class="report__message-title">💡 개선 도움 문구</div>
                        <div class="report__message-text" id="math-diary-message">
                            시간을 다루는 학생이 성적을 만든다.
                            
                            https://claude.ai/public/artifacts/b75b7f55-5ebd-44b6-b015-eebbf91d7b3e?fullscreen=true
                        </div>
                        <button class="report__copy-btn" onclick="copyMessage('math-diary-message')">📋 메시지 복사</button>
                    </div>
                    
                    <?php if (count($mathDiaryAll) > 3): ?>
                        <button class="report__more-btn" onclick="toggleMore('math-diary-more')">더보기 (<?php echo count($mathDiaryAll) - 3; ?>명)</button>
                        <div id="math-diary-more" class="report__hidden">
                            <ul class="report__student-list" style="margin-top: 15px;">
                                <?php foreach (array_slice($mathDiaryAll, 3) as $student): ?>
                                    <li class="report__student-item">
                                        <div>
                                            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" 
                                               class="report__student-name" 
                                               target="_blank">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </a>
                                            <span class="report__student-info">
                                                (최근 1개월 수학일기: <?php echo $student['math_diary_count']; ?>개)
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    수학일기 작성 문제가 있는 학생이 없습니다. 👍
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 메모 관리 문제 학생 -->
        <div class="report__section">
            <h2 class="report__section-title">
                <span>📌 메모 관리 문제 학생</span>
                <span style="font-size: 14px; font-weight: normal; color: #666;">총 <?php echo count($memoAll); ?>명</span>
            </h2>
            <?php if (count($memoTop3) > 0): ?>
                <div class="report__card">
                    <div class="report__card-title">주요 관찰 대상 (상위 3명)</div>
                    <ul class="report__student-list">
                        <?php foreach ($memoTop3 as $student): ?>
                            <li class="report__student-item">
                                <div>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" 
                                       class="report__student-name" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </a>
                                    <span class="report__student-info">
                                        (최근 1개월 메모: <?php echo $student['memo_count']; ?>개)
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="report__message-box">
                        <div class="report__message-title">💡 개선 도움 문구</div>
                        <div class="report__message-text" id="memo-message">
                            수학공부를 춤추게 만드는 나만의 루틴은 ?
                            
                            https://claude.ai/public/artifacts/b75b7f55-5ebd-44b6-b015-eebbf91d7b3e?fullscreen=true
                        </div>
                        <button class="report__copy-btn" onclick="copyMessage('memo-message')">📋 메시지 복사</button>
                    </div>
                    
                    <?php if (count($memoAll) > 3): ?>
                        <button class="report__more-btn" onclick="toggleMore('memo-more')">더보기 (<?php echo count($memoAll) - 3; ?>명)</button>
                        <div id="memo-more" class="report__hidden">
                            <ul class="report__student-list" style="margin-top: 15px;">
                                <?php foreach (array_slice($memoAll, 3) as $student): ?>
                                    <li class="report__student-item">
                                        <div>
                                            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" 
                                               class="report__student-name" 
                                               target="_blank">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </a>
                                            <span class="report__student-info">
                                                (최근 1개월 메모: <?php echo $student['memo_count']; ?>개)
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    메모 관리 문제가 있는 학생이 없습니다. 👍
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyMessage(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();
            
            // 클립보드에 복사
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('메시지가 클립보드에 복사되었습니다!');
                }).catch(function(err) {
                    console.error('복사 실패:', err);
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
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
                    alert('메시지가 클립보드에 복사되었습니다!');
                } else {
                    alert('복사에 실패했습니다. 수동으로 복사해주세요.');
                }
            } catch (err) {
                console.error('복사 실패:', err);
                alert('복사에 실패했습니다. 수동으로 복사해주세요.');
            }
            
            document.body.removeChild(textArea);
        }
        
        function toggleMore(elementId) {
            const element = document.getElementById(elementId);
            if (element.classList.contains('report__hidden')) {
                element.classList.remove('report__hidden');
            } else {
                element.classList.add('report__hidden');
            }
        }
    </script>
</body>
</html>

