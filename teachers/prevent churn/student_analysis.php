<?php
/**
 * 학생별 목표설정 분석 상세 페이지
 * 파일: teachers/prevent churn/student_analysis.php
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// OpenAI API 설정 파일 포함
require_once(__DIR__ . '/../openai_config.php');

$teacherid = isset($_GET["userid"]) ? (int)$_GET["userid"] : $USER->id;
$studentid = isset($_GET["studentid"]) ? (int)$_GET["studentid"] : null;

// 에러 발생 시 파일과 라인 정보 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "에러 발생: $errstr (파일: $errfile, 라인: $errline)";
    return true;
});

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'", [$USER->id]);
$role = $userrole ? $userrole->role : '';

// 접근 권한 확인
if ($USER->id == NULL) {
    header('Location: https://mathking.kr/moodle/login/index.php');
    exit();
}

if ($USER->id != $teacherid && $role !== 'manager') {
    die('접근권한이 없습니다. (파일: student_analysis.php, 라인: ' . __LINE__ . ')');
}

// 교사 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$teacherid]);
if (!$username) {
    die('교사 정보를 찾을 수 없습니다. (파일: student_analysis.php, 라인: ' . __LINE__ . ')');
}

// 주 단위 날짜 계산 함수 (일요일 기준)
function getWeekRange($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $dayOfWeek = (int)date('w', $timestamp);
    $sundayOffset = $dayOfWeek == 0 ? 0 : $dayOfWeek;
    
    $weekStart = $timestamp - ($sundayOffset * 24 * 60 * 60);
    $weekEnd = $weekStart + (6 * 24 * 60 * 60);
    
    return [
        'start' => $weekStart,
        'end' => $weekEnd,
        'start_date' => date('Y-m-d', $weekStart),
        'end_date' => date('Y-m-d', $weekEnd)
    ];
}

// 담당 학생 목록 가져오기
$collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid = ?", [$teacherid]);
$teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid = ? AND fieldid = '79'", [$teacherid]);
$tsymbol = $teacher ? $teacher->symbol : '##';
$tsymbol1 = '##';
$tsymbol2 = '##';
$tsymbol3 = '##';

if ($collegues) {
    if ($collegues->mntr1) {
        $teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid = ? AND fieldid = '79'", [$collegues->mntr1]);
        $tsymbol1 = $teacher1 ? $teacher1->symbol : '##';
    }
    if ($collegues->mntr2) {
        $teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid = ? AND fieldid = '79'", [$collegues->mntr2]);
        $tsymbol2 = $teacher2 ? $teacher2->symbol : '##';
    }
    if ($collegues->mntr3) {
        $teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data WHERE userid = ? AND fieldid = '79'", [$collegues->mntr3]);
        $tsymbol3 = $teacher3 ? $teacher3->symbol : '##';
    }
}

$aweekago = time() - 604800;
$students = $DB->get_records_sql(
    "SELECT * FROM mdl_user 
     WHERE suspended = '0' 
     AND lastaccess > ? 
     AND (firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ?) 
     ORDER BY id DESC",
    [$aweekago, "%$tsymbol%", "%$tsymbol1%", "%$tsymbol2%", "%$tsymbol3%"]
);

$studentIds = [];
$studentNames = [];
foreach ($students as $student) {
    $studentIds[] = (int)$student->id;
    $studentNames[$student->id] = ($student->lastname ?? '') . ($student->firstname ?? '');
}

// 특정 학생 선택 시 해당 학생만, 아니면 전체 학생
$targetStudentIds = $studentid ? [$studentid] : $studentIds;

// 검토 시점 기준 (현재 시점)
$reviewTime = time();

// 최근 일요일부터 4주간 계산
$currentDayOfWeek = (int)date('w', $reviewTime); // 0=일요일, 6=토요일
$sundayOffset = $currentDayOfWeek == 0 ? 0 : $currentDayOfWeek;
$lastSunday = $reviewTime - ($sundayOffset * 24 * 60 * 60); // 가장 최근 일요일
$fourWeeksAgoSunday = $lastSunday - (3 * 7 * 24 * 60 * 60); // 4주 전 일요일 (총 4주간)

// 4주간의 주별 날짜 범위 계산
$weekRanges = [];
for ($weekOffset = 0; $weekOffset < 4; $weekOffset++) {
    $weekStartTimestamp = $lastSunday - ($weekOffset * 7 * 24 * 60 * 60);
    $weekEndTimestamp = $weekStartTimestamp + (6 * 24 * 60 * 60);
    $weekRange = getWeekRange($weekStartTimestamp);
    $weekRanges[] = [
        'start_date' => $weekRange['start_date'],
        'end_date' => $weekRange['end_date']
    ];
}

// 학생별 분석 결과 조회 (최근 4주간)
$studentAnalysisData = [];

if (!empty($targetStudentIds)) {
    $targetStudentIdsStr = implode(',', array_map('intval', $targetStudentIds));
    $weekStartDates = array_column($weekRanges, 'start_date');
    $weekEndDates = array_column($weekRanges, 'end_date');
    
    // 모든 학생의 4주간 분석 결과를 한 번에 조회 (성능 최적화)
    $datePlaceholders = array_fill(0, count($weekStartDates), '?');
    $params = array_merge($weekStartDates, $weekEndDates);
    
    $allAnalysesQuery = "SELECT * FROM mdl_abessi_goal_analysis 
                         WHERE userid IN ($targetStudentIdsStr) 
                         AND week_start_date IN (" . implode(',', $datePlaceholders) . ")
                         AND week_end_date IN (" . implode(',', $datePlaceholders) . ")
                         ORDER BY userid, week_start_date DESC";
    $allAnalyses = $DB->get_records_sql($allAnalysesQuery, $params);
    
    // 학생별로 정리
    foreach ($targetStudentIds as $sid) {
        if (!in_array($sid, $studentIds)) continue;
        
        $weeks = [];
        foreach ($allAnalyses as $analysis) {
            if ($analysis->userid == $sid) {
                $weeks[] = [
                    'week_start' => $analysis->week_start_date,
                    'week_end' => $analysis->week_end_date,
                    'term_score' => $analysis->term_goal_score,
                    'weekly_score' => $analysis->weekly_goal_score,
                    'today_score' => $analysis->today_goal_score,
                    'correlation_score' => $analysis->correlation_score,
                    'term_goal' => $analysis->term_goal_text,
                    'weekly_goal' => $analysis->weekly_goal_text,
                    'today_goal' => $analysis->today_goal_text,
                    'correlation_analysis' => $analysis->correlation_analysis,
                    'timecreated' => $analysis->timecreated
                ];
            }
        }
        
        // 주별로 정렬 (최신 주가 먼저 오도록)
        usort($weeks, function($a, $b) {
            return strcmp($b['week_start'], $a['week_start']);
        });
        
        // 4주간 데이터가 있으면 추가
        if (!empty($weeks)) {
            $studentAnalysisData[$sid] = [
                'name' => $studentNames[$sid] ?? '학생 #' . $sid,
                'weeks' => $weeks
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학생별 목표설정 분석</title>
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
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.6em;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 0.9em;
            opacity: 0.9;
        }

        .back-link:hover {
            opacity: 1;
        }

        .student-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .student-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }

        .week-analysis {
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .week-header {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .goal-row {
            display: grid;
            grid-template-columns: 80px 1fr 60px;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.85em;
        }

        .goal-row:last-child {
            border-bottom: none;
        }

        .goal-type {
            font-weight: bold;
            color: #666;
        }

        .goal-text {
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .goal-score {
            text-align: center;
            font-weight: bold;
        }

        .correlation-row {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .correlation-label {
            font-weight: bold;
            color: #667eea;
        }

        .score-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .score-high {
            background: #d4edda;
            color: #155724;
        }

        .score-medium {
            background: #fff3cd;
            color: #856404;
        }

        .score-low {
            background: #f8d7da;
            color: #721c24;
        }

        .score-none {
            color: #999;
        }

        .correlation-analysis {
            font-size: 0.8em;
            color: #666;
            margin-top: 4px;
            font-style: italic;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php?userid=<?php echo $teacherid; ?>" class="back-link">← 대시보드로 돌아가기</a>
        <h1>📊 학생별 목표설정 분석 (최근 4주간)</h1>
        <div style="text-align: center; color: white; margin-bottom: 15px; font-size: 0.9em;">
            분석 기간: <?php echo date('Y-m-d', $fourWeeksAgoSunday); ?> ~ <?php echo date('Y-m-d', $lastSunday); ?>
        </div>

        <?php if (empty($studentAnalysisData)): ?>
        <div class="student-section">
            <div class="no-data">분석 데이터가 없습니다.</div>
        </div>
        <?php else: ?>
            <?php foreach ($studentAnalysisData as $studentId => $studentData): ?>
            <div class="student-section">
                <div class="student-name"><?php echo htmlspecialchars($studentData['name']); ?></div>
                
                <?php foreach ($studentData['weeks'] as $week): ?>
                <div class="week-analysis">
                    <div class="week-header">
                        주간: <?php echo htmlspecialchars($week['week_start']); ?> ~ <?php echo htmlspecialchars($week['week_end']); ?>
                    </div>
                    
                    <?php 
                    $getScoreClass = function($score) {
                        if ($score === null) return 'score-none';
                        return $score >= 4 ? 'score-high' : ($score >= 3 ? 'score-medium' : 'score-low');
                    };
                    ?>
                    
                    <?php if ($week['term_score'] !== null || !empty($week['term_goal'])): ?>
                    <div class="goal-row">
                        <div class="goal-type">분기목표</div>
                        <div class="goal-text"><?php echo htmlspecialchars($week['term_goal'] ?: '(없음)'); ?></div>
                        <div class="goal-score">
                            <?php if ($week['term_score'] !== null): ?>
                            <span class="score-badge <?php echo $getScoreClass($week['term_score']); ?>">
                                <?php echo $week['term_score']; ?>/5
                            </span>
                            <?php else: ?>
                            <span class="score-none">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($week['weekly_score'] !== null || !empty($week['weekly_goal'])): ?>
                    <div class="goal-row">
                        <div class="goal-type">주간목표</div>
                        <div class="goal-text"><?php echo htmlspecialchars($week['weekly_goal'] ?: '(없음)'); ?></div>
                        <div class="goal-score">
                            <?php if ($week['weekly_score'] !== null): ?>
                            <span class="score-badge <?php echo $getScoreClass($week['weekly_score']); ?>">
                                <?php echo $week['weekly_score']; ?>/5
                            </span>
                            <?php else: ?>
                            <span class="score-none">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($week['today_score'] !== null || !empty($week['today_goal'])): ?>
                    <div class="goal-row">
                        <div class="goal-type">오늘목표</div>
                        <div class="goal-text"><?php echo htmlspecialchars($week['today_goal'] ?: '(없음)'); ?></div>
                        <div class="goal-score">
                            <?php if ($week['today_score'] !== null): ?>
                            <span class="score-badge <?php echo $getScoreClass($week['today_score']); ?>">
                                <?php echo $week['today_score']; ?>/5
                            </span>
                            <?php else: ?>
                            <span class="score-none">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($week['correlation_score'] !== null): ?>
                    <div class="correlation-row">
                        <div>
                            <div class="correlation-label">상관관계 점수</div>
                            <?php if (!empty($week['correlation_analysis'])): ?>
                            <div class="correlation-analysis"><?php echo htmlspecialchars($week['correlation_analysis']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="score-badge <?php echo $getScoreClass($week['correlation_score']); ?>" style="font-size: 1em;">
                                <?php echo $week['correlation_score']; ?>/5
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

