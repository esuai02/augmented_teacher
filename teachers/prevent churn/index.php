<?php
/**
 * KTM 학생관리 프로세스 & 이탈방지 대시보드
 * 파일: teachers/prevent churn/index.php
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// OpenAI API 설정 파일 포함
require_once(__DIR__ . '/../openai_config.php');

$teacherid = isset($_GET["userid"]) ? (int)$_GET["userid"] : $USER->id;

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
    die('접근권한이 없습니다. (파일: index.php, 라인: ' . __LINE__ . ')');
}

// 교사 이름 가져오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", [$teacherid]);
if (!$username) {
    die('교사 정보를 찾을 수 없습니다. (파일: index.php, 라인: ' . __LINE__ . ')');
}

// 검토 시점 기준 (현재 시점)
$reviewTime = time();
$oneWeekAgo = $reviewTime - (7 * 24 * 60 * 60); // 1주 전

// 최근 일요일부터 4주간 계산
$currentDayOfWeek = (int)date('w', $reviewTime); // 0=일요일, 6=토요일
$sundayOffset = $currentDayOfWeek == 0 ? 0 : $currentDayOfWeek;
$lastSunday = $reviewTime - ($sundayOffset * 24 * 60 * 60); // 가장 최근 일요일
$fourWeeksAgoSunday = $lastSunday - (3 * 7 * 24 * 60 * 60); // 4주 전 일요일 (총 4주간)
$fourWeeksAgo = $fourWeeksAgoSunday; // 4주 전 일요일부터 시작

// 담당 학생 목록 가져오기 (PomodoroStat.php 로직 참고)
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

$aweekago = $reviewTime - 604800;
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

// 1. 포모도르 데이터 분석 (5개 이하인 학생, 4주간 메모 없음)
$pomodoroStudents = [];
$pomodoroConcernStudents = [];

if (!empty($studentIds)) {
    $studentIdsStr = implode(',', array_map('intval', $studentIds));
    
    // 최근 4주간 포모도르 개수 조회
    $pomodoroCounts = $DB->get_records_sql(
        "SELECT userid, COUNT(*) as count 
         FROM mdl_abessi_tracking 
         WHERE userid IN ($studentIdsStr) 
         AND status = 'complete' 
         AND timefinished > 0 
         AND timecreated >= ? 
         AND timecreated <= ? 
         AND hide = 0
         GROUP BY userid",
        [$fourWeeksAgo, $reviewTime]
    );
    
    // 학생별 포모도르 개수 저장
    $studentPomodoroCounts = [];
    foreach ($pomodoroCounts as $pomo) {
        $studentPomodoroCounts[$pomo->userid] = (int)$pomo->count;
    }
    
    // 포모도르 5개 이하인 학생 찾기
    foreach ($studentIds as $studentId) {
        $count = isset($studentPomodoroCounts[$studentId]) ? $studentPomodoroCounts[$studentId] : 0;
        if ($count <= 5) {
            $pomodoroStudents[] = [
                'id' => $studentId,
                'name' => $studentNames[$studentId] ?? '학생 #' . $studentId,
                'count' => $count
            ];
            
            // 4주간 메모 확인
            $hasMemo = $DB->get_record_sql(
                "SELECT COUNT(*) as cnt 
                 FROM mdl_abessi_stickynotes 
                 WHERE userid = ? 
                 AND type = 'timescaffolding' 
                 AND created_at >= ? 
                 AND created_at <= ? 
                 AND hide = 0",
                [$studentId, $fourWeeksAgo, $reviewTime]
            );
            
            if (!$hasMemo || (int)$hasMemo->cnt == 0) {
                $pomodoroConcernStudents[] = [
                    'id' => $studentId,
                    'name' => $studentNames[$studentId] ?? '학생 #' . $studentId,
                    'count' => $count,
                    'reason' => '포모도르 ' . $count . '개, 4주간 메모 없음'
                ];
            }
        }
    }
}

// 2. 목표설정 구체성 평가 함수 (OpenAI API)
function evaluateGoalSpecificity($goalText, $goalType = 'general') {
    if (empty($goalText)) {
        return 0;
    }
    
    // OpenAI API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'your_api_key_here') {
        error_log("prevent churn/index.php - OpenAI API 키가 설정되지 않았습니다. (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
    }
    
    $typeLabels = [
        'term' => '분기목표',
        'weekly' => '주간목표',
        'today' => '오늘목표',
        'general' => '목표'
    ];
    $typeLabel = $typeLabels[$goalType] ?? '목표';
    
    $systemMessage = "너의 역할은 '{$typeLabel} 문장'의 구체성을 0~5 사이의 숫자로 평가하는 채점기이다.\n\n";
    $systemMessage .= "- 오직 '내용의 완결성과 구체성'만 평가한다.\n";
    $systemMessage .= "- 동기, 감정, 태도는 점수에 반영하지 않는다.\n";
    $systemMessage .= "- 점수 기준:\n";
    $systemMessage .= "  - 0점: 의미를 파악하기 어려운 수준. 목표로 보기 힘듦.\n";
    $systemMessage .= "  - 1점: 매우 추상적. 방향만 있고, 끝난 상태가 없음.\n";
    $systemMessage .= "  - 2점: 주제와 방향은 있으나, 완료 상태나 기준이 거의 드러나지 않음.\n";
    $systemMessage .= "  - 3점: 결과 표현은 있으나, '좋은 점수' 같은 모호한 표현이 포함되어 있어 완료 기준이 애매함.\n";
    $systemMessage .= "  - 4점: 언제/어디서/얼마나 등의 조건 중 대부분이 포함되어 있으며, 달성 여부를 대체로 판단 가능함.\n";
    $systemMessage .= "  - 5점: 언제까지, 무엇을, 어느 수준까지, 어떤 기준으로 완료인지가 모두 포함되어 있어 제3자도 논쟁의 여지 없이 달성 여부를 판단할 수 있음.\n\n";
    $systemMessage .= "출력 형식 (JSON):\n";
    $systemMessage .= '{"score": <0에서 5 사이의 정수>}' . "\n\n";
    $systemMessage .= "설명 문장은 출력하지 않는다.";
    
    $prompt = "평가할 {$typeLabel}: " . $goalText;
    
    try {
        // openai_config.php의 callOpenAI 함수 사용
        $response = callOpenAI($prompt, $systemMessage, 0.0, 100);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            error_log("prevent churn/index.php - OpenAI API 응답 형식 오류 (파일: index.php, 라인: " . __LINE__ . ")");
            return null;
        }
        
        $content = trim($response['choices'][0]['message']['content']);
        
        // JSON 파싱 시도
        $json = json_decode($content, true);
        if (isset($json['score'])) {
            return (int)$json['score'];
        }
        
        // JSON 파싱 실패 시 숫자만 추출 시도
        if (preg_match('/\b([0-5])\b/', $content, $matches)) {
            return (int)$matches[1];
        }
        
        error_log("prevent churn/index.php - 점수 추출 실패: " . substr($content, 0, 100) . " (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
        
    } catch (Exception $e) {
        error_log("prevent churn/index.php - OpenAI API 호출 오류: " . $e->getMessage() . " (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
    }
}

// 목표 간 상관관계 평가 함수
function evaluateGoalCorrelation($termGoal, $weeklyGoal, $todayGoal) {
    // 세 목표가 모두 없으면 상관관계 평가 불가
    if (empty($termGoal) && empty($weeklyGoal) && empty($todayGoal)) {
        return ['score' => 0, 'analysis' => '목표가 없어 상관관계를 평가할 수 없습니다.'];
    }
    
    // OpenAI API 키 확인
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'your_api_key_here') {
        error_log("prevent churn/index.php - OpenAI API 키가 설정되지 않았습니다. (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
    }
    
    $systemMessage = "너의 역할은 분기목표, 주간목표, 오늘목표 간의 상관관계를 0~5 사이의 숫자로 평가하는 분석가이다.\n\n";
    $systemMessage .= "평가 기준:\n";
    $systemMessage .= "- 0점: 세 목표 간 전혀 연관성이 없거나 모두 없음\n";
    $systemMessage .= "- 1점: 목표들이 서로 무관하거나 모순됨\n";
    $systemMessage .= "- 2점: 약간의 연관성은 있으나 일관성이 부족함\n";
    $systemMessage .= "- 3점: 기본적인 연관성은 있으나 단계적 연결이 약함\n";
    $systemMessage .= "- 4점: 분기목표에서 주간목표, 주간목표에서 오늘목표로의 단계적 연결이 대체로 잘 되어 있음\n";
    $systemMessage .= "- 5점: 분기목표 → 주간목표 → 오늘목표로의 단계적 분해와 연결이 매우 명확하고 일관성 있게 이루어짐\n\n";
    $systemMessage .= "출력 형식 (JSON):\n";
    $systemMessage .= '{"score": <0에서 5 사이의 정수>, "analysis": "<간단한 분석 설명>"}' . "\n\n";
    $systemMessage .= "설명은 한 문장으로 간결하게 작성한다.";
    
    $prompt = "분기목표: " . ($termGoal ?: '(없음)') . "\n";
    $prompt .= "주간목표: " . ($weeklyGoal ?: '(없음)') . "\n";
    $prompt .= "오늘목표: " . ($todayGoal ?: '(없음)') . "\n\n";
    $prompt .= "위 세 목표 간의 상관관계를 평가해주세요.";
    
    try {
        $response = callOpenAI($prompt, $systemMessage, 0.0, 150);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            error_log("prevent churn/index.php - OpenAI API 응답 형식 오류 (파일: index.php, 라인: " . __LINE__ . ")");
            return null;
        }
        
        $content = trim($response['choices'][0]['message']['content']);
        
        // JSON 파싱 시도
        $json = json_decode($content, true);
        if (isset($json['score'])) {
            return [
                'score' => (int)$json['score'],
                'analysis' => isset($json['analysis']) ? $json['analysis'] : ''
            ];
        }
        
        // JSON 파싱 실패 시 점수만 추출
        if (preg_match('/\b([0-5])\b/', $content, $matches)) {
            return [
                'score' => (int)$matches[1],
                'analysis' => '자동 분석 완료'
            ];
        }
        
        error_log("prevent churn/index.php - 상관관계 점수 추출 실패: " . substr($content, 0, 100) . " (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
        
    } catch (Exception $e) {
        error_log("prevent churn/index.php - OpenAI API 호출 오류: " . $e->getMessage() . " (파일: index.php, 라인: " . __LINE__ . ")");
        return null;
    }
}

// 주 단위 날짜 계산 함수 (일요일 기준)
function getWeekRange($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $dayOfWeek = (int)date('w', $timestamp); // 0=일요일, 6=토요일
    $sundayOffset = $dayOfWeek == 0 ? 0 : $dayOfWeek;
    
    $weekStart = $timestamp - ($sundayOffset * 24 * 60 * 60);
    $weekEnd = $weekStart + (6 * 24 * 60 * 60); // 토요일까지
    
    return [
        'start' => $weekStart,
        'end' => $weekEnd,
        'start_date' => date('Y-m-d', $weekStart),
        'end_date' => date('Y-m-d', $weekEnd)
    ];
}

// 재분석 요청 확인
$reanalyze = isset($_GET['reanalyze']) && $_GET['reanalyze'] == '1';

// 목표설정 구체성 평가 및 저장 (최근 4주간)
$goalSpecificityData = [];
$currentWeek = getWeekRange($reviewTime);
$oneWeekAgo = $reviewTime - (7 * 24 * 60 * 60);

// 4주간의 주별 날짜 범위 계산
$weekRanges = [];
for ($weekOffset = 0; $weekOffset < 4; $weekOffset++) {
    $weekStartTimestamp = $lastSunday - ($weekOffset * 7 * 24 * 60 * 60);
    $weekEndTimestamp = $weekStartTimestamp + (6 * 24 * 60 * 60);
    $weekRanges[] = [
        'start' => $weekStartTimestamp,
        'end' => $weekEndTimestamp,
        'range' => getWeekRange($weekStartTimestamp)
    ];
}

if (!empty($studentIds)) {
    $studentIdsStr = implode(',', array_map('intval', $studentIds));
    
    // 먼저 모든 학생의 4주간 분석 결과를 한 번에 조회 (성능 최적화)
    $weekStartDates = array_column(array_column($weekRanges, 'range'), 'start_date');
    $weekEndDates = array_column(array_column($weekRanges, 'range'), 'end_date');
    
    $allAnalyses = [];
    if (!$reanalyze && !empty($weekStartDates)) {
        // 재분석이 아닌 경우에만 기존 분석 결과 조회
        // SQL 인젝션 방지를 위해 파라미터화된 쿼리 사용
        $datePlaceholders = array_fill(0, count($weekStartDates), '?');
        $params = array_merge($weekStartDates, $weekEndDates);
        
        // $studentIdsStr은 이미 intval로 정수 변환되어 안전함
        $existingAnalysesQuery = "SELECT * FROM mdl_abessi_goal_analysis 
                                   WHERE userid IN ($studentIdsStr) 
                                   AND week_start_date IN (" . implode(',', $datePlaceholders) . ")
                                   AND week_end_date IN (" . implode(',', $datePlaceholders) . ")";
        $allExistingAnalyses = $DB->get_records_sql($existingAnalysesQuery, $params);
        
        // 학생별, 주별로 정리
        foreach ($allExistingAnalyses as $analysis) {
            $allAnalyses[$analysis->userid][$analysis->week_start_date] = $analysis;
        }
    }
    
    foreach ($studentIds as $studentId) {
        $weekAnalyses = [];
        
        foreach ($weekRanges as $weekInfo) {
            $weekRange = $weekInfo['range'];
            $weekStartTimestamp = $weekInfo['start'];
            $weekEndTimestamp = $weekInfo['end'];
            
            // 기존 분석 결과 확인 (1주일 이내 분석이면 재사용)
            $existingAnalysis = null;
            if (!$reanalyze && isset($allAnalyses[$studentId][$weekRange['start_date']])) {
                $existingAnalysis = $allAnalyses[$studentId][$weekRange['start_date']];
            }
            
            $shouldAnalyze = false;
            if ($reanalyze) {
                // 재분석 요청 시 무조건 분석
                $shouldAnalyze = true;
            } elseif (!$existingAnalysis) {
                // 분석 결과가 없으면 분석
                $shouldAnalyze = true;
            } elseif ($existingAnalysis->analysis_status !== 'completed') {
                // 분석 상태가 완료가 아니면 분석
                $shouldAnalyze = true;
            } elseif ($existingAnalysis->timemodified < $oneWeekAgo) {
                // 마지막 분석이 1주일 이상 지났으면 재분석
                $shouldAnalyze = true;
            }
            
            if ($shouldAnalyze) {
                // 새로운 분석이 필요한 경우에만 목표 데이터 조회
                // 분기목표 조회 (현재 적용중인 것만)
                $termGoal = $DB->get_record_sql(
                    "SELECT memo, deadline, timecreated 
                     FROM mdl_abessi_progress 
                     WHERE userid = ? 
                     AND plantype = '분기목표' 
                     AND hide = 0 
                     AND deadline > ? 
                     ORDER BY id DESC 
                     LIMIT 1",
                    [$studentId, $weekStartTimestamp]
                );
                
                // 주간목표 조회 (해당 주 내)
                $weeklyGoal = $DB->get_record_sql(
                    "SELECT text, timecreated 
                     FROM mdl_abessi_today 
                     WHERE userid = ? 
                     AND type = '주간목표' 
                     AND timecreated >= ? 
                     AND timecreated <= ? 
                     ORDER BY timecreated DESC 
                     LIMIT 1",
                    [$studentId, $weekStartTimestamp, $weekEndTimestamp]
                );
                
                // 오늘목표 조회 (해당 주 내, 검사요청 포함)
                $todayGoal = $DB->get_record_sql(
                    "SELECT text, timecreated 
                     FROM mdl_abessi_today 
                     WHERE userid = ? 
                     AND (type = '오늘목표' OR type = '검사요청') 
                     AND timecreated >= ? 
                     AND timecreated <= ? 
                     ORDER BY timecreated DESC 
                     LIMIT 1",
                    [$studentId, $weekStartTimestamp, $weekEndTimestamp]
                );
                
                $termGoalText = $termGoal && !empty($termGoal->memo) ? $termGoal->memo : '';
                $weeklyGoalText = $weeklyGoal && !empty($weeklyGoal->text) ? $weeklyGoal->text : '';
                $todayGoalText = $todayGoal && !empty($todayGoal->text) ? $todayGoal->text : '';
                
                // 새로운 분석 수행
                $termScore = null;
                $weeklyScore = null;
                $todayScore = null;
                $correlationResult = null;
                
                // 분기목표 평가
                if (!empty($termGoalText)) {
                    $termScore = evaluateGoalSpecificity($termGoalText, 'term');
                }
                
                // 주간목표 평가
                if (!empty($weeklyGoalText)) {
                    $weeklyScore = evaluateGoalSpecificity($weeklyGoalText, 'weekly');
                }
                
                // 오늘목표 평가
                if (!empty($todayGoalText)) {
                    $todayScore = evaluateGoalSpecificity($todayGoalText, 'today');
                }
                
                // 상관관계 평가
                if (!empty($termGoalText) || !empty($weeklyGoalText) || !empty($todayGoalText)) {
                    $correlationResult = evaluateGoalCorrelation($termGoalText, $weeklyGoalText, $todayGoalText);
                }
                
                // 분석 결과 저장
                $now = time();
                $analysisData = new stdClass();
                $analysisData->userid = $studentId;
                $analysisData->week_start_date = $weekRange['start_date'];
                $analysisData->week_end_date = $weekRange['end_date'];
                $analysisData->term_goal_text = $termGoalText;
                $analysisData->term_goal_score = $termScore;
                $analysisData->term_goal_deadline = $termGoal ? $termGoal->deadline : null;
                $analysisData->weekly_goal_text = $weeklyGoalText;
                $analysisData->weekly_goal_score = $weeklyScore;
                $analysisData->weekly_goal_created = $weeklyGoal ? $weeklyGoal->timecreated : null;
                $analysisData->today_goal_text = $todayGoalText;
                $analysisData->today_goal_score = $todayScore;
                $analysisData->today_goal_created = $todayGoal ? $todayGoal->timecreated : null;
                $analysisData->correlation_score = $correlationResult ? $correlationResult['score'] : null;
                $analysisData->correlation_analysis = $correlationResult ? $correlationResult['analysis'] : null;
                $analysisData->analysis_status = ($termScore !== null || $weeklyScore !== null || $todayScore !== null) ? 'completed' : 'pending';
                $analysisData->timecreated = $now;
                $analysisData->timemodified = $now;
                
                try {
                    if ($existingAnalysis) {
                        // 기존 레코드 업데이트
                        $analysisData->id = $existingAnalysis->id;
                        $DB->update_record('abessi_goal_analysis', $analysisData);
                    } else {
                        // 새 레코드 삽입
                        $DB->insert_record('abessi_goal_analysis', $analysisData);
                    }
                } catch (Exception $e) {
                    error_log("prevent churn/index.php - 분석 결과 저장 오류: " . $e->getMessage() . " (파일: index.php, 라인: " . __LINE__ . ")");
                }
                
                // 분석 결과 저장
                $weekAnalyses[] = [
                    'week_start' => $weekRange['start_date'],
                    'week_end' => $weekRange['end_date'],
                    'term_score' => $termScore,
                    'weekly_score' => $weeklyScore,
                    'today_score' => $todayScore,
                    'correlation_score' => $correlationResult ? $correlationResult['score'] : null,
                    'term_goal' => mb_substr($termGoalText, 0, 30),
                    'weekly_goal' => mb_substr($weeklyGoalText, 0, 30),
                    'today_goal' => mb_substr($todayGoalText, 0, 30),
                    'from_cache' => false
                ];
            } else {
                // 기존 분석 결과 사용 (DB에서 이미 조회됨)
                $weekAnalyses[] = [
                    'week_start' => $weekRange['start_date'],
                    'week_end' => $weekRange['end_date'],
                    'term_score' => $existingAnalysis->term_goal_score,
                    'weekly_score' => $existingAnalysis->weekly_goal_score,
                    'today_score' => $existingAnalysis->today_goal_score,
                    'correlation_score' => $existingAnalysis->correlation_score,
                    'term_goal' => mb_substr($existingAnalysis->term_goal_text ?? '', 0, 30),
                    'weekly_goal' => mb_substr($existingAnalysis->weekly_goal_text ?? '', 0, 30),
                    'today_goal' => mb_substr($existingAnalysis->today_goal_text ?? '', 0, 30),
                    'from_cache' => true
                ];
            }
        }
        
        // 4주간 분석 결과를 종합하여 최신 주(또는 평균)로 표시
        if (!empty($weekAnalyses)) {
            // 가장 최근 주의 분석 결과 사용 (또는 평균 계산 가능)
            $latestWeek = $weekAnalyses[0];
            
            // 평균 점수 계산 (선택사항)
            $avgTermScore = null;
            $avgWeeklyScore = null;
            $avgTodayScore = null;
            $avgCorrelationScore = null;
            
            $termScores = array_filter(array_column($weekAnalyses, 'term_score'), function($v) { return $v !== null; });
            $weeklyScores = array_filter(array_column($weekAnalyses, 'weekly_score'), function($v) { return $v !== null; });
            $todayScores = array_filter(array_column($weekAnalyses, 'today_score'), function($v) { return $v !== null; });
            $correlationScores = array_filter(array_column($weekAnalyses, 'correlation_score'), function($v) { return $v !== null; });
            
            if (!empty($termScores)) {
                $avgTermScore = round(array_sum($termScores) / count($termScores), 1);
            }
            if (!empty($weeklyScores)) {
                $avgWeeklyScore = round(array_sum($weeklyScores) / count($weeklyScores), 1);
            }
            if (!empty($todayScores)) {
                $avgTodayScore = round(array_sum($todayScores) / count($todayScores), 1);
            }
            if (!empty($correlationScores)) {
                $avgCorrelationScore = round(array_sum($correlationScores) / count($correlationScores), 1);
            }
            
            // 최신 주 결과 사용 (또는 평균 사용 가능)
            $goalSpecificityData[$studentId] = [
                'name' => $studentNames[$studentId] ?? '학생 #' . $studentId,
                'term_score' => $latestWeek['term_score'],
                'weekly_score' => $latestWeek['weekly_score'],
                'today_score' => $latestWeek['today_score'],
                'correlation_score' => $latestWeek['correlation_score'],
                'term_goal' => $latestWeek['term_goal'],
                'weekly_goal' => $latestWeek['weekly_goal'],
                'today_goal' => $latestWeek['today_goal'],
                'avg_term_score' => $avgTermScore,
                'avg_weekly_score' => $avgWeeklyScore,
                'avg_today_score' => $avgTodayScore,
                'avg_correlation_score' => $avgCorrelationScore,
                'weeks_analyzed' => count($weekAnalyses),
                'from_cache' => $latestWeek['from_cache']
            ];
        }
    }
}

// 3. 출결이상 감지 (지각 30% 이상)
$attendanceRiskStudents = [];
if (!empty($studentIds)) {
    $studentIdsStr = implode(',', array_map('intval', $studentIds));
    
    // 최근 4주간 출결 데이터 조회 (mdl_abessi_today의 attendstatus 필드 사용)
    // 참고: 실제 테이블 구조에 따라 조정 필요
    $attendanceData = $DB->get_records_sql(
        "SELECT userid, 
                SUM(CASE WHEN attendstatus = '지각' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN attendstatus = '정상' THEN 1 ELSE 0 END) as normal_count,
                COUNT(*) as total_count
         FROM mdl_abessi_today 
         WHERE userid IN ($studentIdsStr) 
         AND timecreated >= ? 
         AND timecreated <= ?
         AND attendstatus IS NOT NULL
         GROUP BY userid",
        [$fourWeeksAgo, $reviewTime]
    );
    
    foreach ($attendanceData as $att) {
        $total = (int)$att->total_count;
        $late = (int)$att->late_count;
        
        if ($total > 0) {
            $lateRate = ($late / $total) * 100;
            if ($lateRate >= 30) {
                $attendanceRiskStudents[] = [
                    'id' => $att->userid,
                    'name' => $studentNames[$att->userid] ?? '학생 #' . $att->userid,
                    'late_rate' => round($lateRate, 1),
                    'late_count' => $late,
                    'total_count' => $total
                ];
            }
        }
    }
}

// 4. 학습 체계성 데이터 조회 (mdl_abessi_detectexception)
$detectExceptionData = [];
$detectExceptionRiskStudents = [];

if (!empty($studentIds)) {
    $studentIdsStr = implode(',', array_map('intval', $studentIds));
    
    // 일요일 기준 최근 4주 계산
    $currentDayOfWeek = (int)date('w', $reviewTime); // 0=일요일, 6=토요일
    $sundayOffset = $currentDayOfWeek == 0 ? 0 : $currentDayOfWeek;
    $lastSunday = $reviewTime - ($sundayOffset * 24 * 60 * 60);
    $fourWeeksAgoSunday = $lastSunday - (3 * 7 * 24 * 60 * 60); // 4주 전 일요일
    
    // 테이블 존재 여부 확인
    try {
        $exceptionData = $DB->get_records_sql(
            "SELECT userid, type, SUM(ncount) as total_count
             FROM mdl_abessi_detectexception 
             WHERE userid IN ($studentIdsStr) 
             AND timecreated >= ? 
             AND timecreated <= ?
             GROUP BY userid, type",
            [$fourWeeksAgoSunday, $reviewTime]
        );
        
        $studentExceptionCounts = [];
        foreach ($exceptionData as $ex) {
            $userId = (int)$ex->userid;
            $type = $ex->type;
            $count = (int)$ex->total_count;
            
            if (!isset($studentExceptionCounts[$userId])) {
                $studentExceptionCounts[$userId] = [];
            }
            $studentExceptionCounts[$userId][$type] = $count;
        }
        
        // 유형별 집계
        $typeTotals = [];
        foreach ($studentExceptionCounts as $userId => $types) {
            foreach ($types as $type => $count) {
                if (!isset($typeTotals[$type])) {
                    $typeTotals[$type] = 0;
                }
                $typeTotals[$type] += $count;
            }
        }
        
        $detectExceptionData = [
            'by_student' => $studentExceptionCounts,
            'by_type' => $typeTotals
        ];
        
        // 상위 15% 위험군 분류
        $allCounts = [];
        foreach ($studentExceptionCounts as $userId => $types) {
            $total = array_sum($types);
            $allCounts[$userId] = $total;
        }
        
        if (!empty($allCounts)) {
            arsort($allCounts);
            $totalStudents = count($allCounts);
            $riskThreshold = max(1, (int)ceil($totalStudents * 0.15)); // 상위 15%
            
            $rank = 0;
            foreach ($allCounts as $userId => $total) {
                $rank++;
                if ($rank <= $riskThreshold) {
                    $detectExceptionRiskStudents[] = [
                        'id' => $userId,
                        'name' => $studentNames[$userId] ?? '학생 #' . $userId,
                        'total_count' => $total,
                        'details' => $studentExceptionCounts[$userId]
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log("prevent churn/index.php - detectexception 테이블 조회 오류: " . $e->getMessage() . " (파일: index.php, 라인: " . __LINE__ . ")");
    }
}

// 통계 데이터 계산
$totalStudents = count($studentIds);
$pomodoroConcernCount = count($pomodoroConcernStudents);
$attendanceRiskCount = count($attendanceRiskStudents);
$detectExceptionRiskCount = count($detectExceptionRiskStudents);
$totalRiskStudents = count(array_unique(array_merge(
    array_column($pomodoroConcernStudents, 'id'),
    array_column($attendanceRiskStudents, 'id'),
    array_column($detectExceptionRiskStudents, 'id')
)));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM 학생관리 프로세스 & 이탈방지 대시보드</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.8em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        /* 프로세스 플로우 섹션 - 컴팩트 버전 */
        .process-flow {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: none; /* 숨김 처리 */
        }

        .process-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
            position: relative;
        }

        .process-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .process-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            position: relative;
        }

        .step {
            flex: 1;
            min-width: 100px;
            margin: 5px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
            color: white;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .step-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        .step-title {
            font-size: 0.85em;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }

        .step-desc {
            font-size: 0.75em;
            color: #666;
            line-height: 1.3;
        }

        .arrow {
            position: absolute;
            top: 25px;
            right: -20px;
            font-size: 1.2em;
            color: #667eea;
        }

        /* 이탈 상황 대응 섹션 */
        .monitoring-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .monitoring-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .metric-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            border-left: 3px solid #667eea;
            transition: all 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .metric-label {
            font-size: 0.75em;
            color: #666;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .metric-value {
            font-size: 1.8em;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .metric-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: bold;
        }

        .status-safe {
            background: #d4edda;
            color: #155724;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
        }

        .status-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* 알림 카드 */
        .alert-card {
            background: linear-gradient(135deg, #fff5f5, #ffe3e3);
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
            border: 2px solid #ff6b6b;
        }

        .alert-title {
            font-size: 0.95em;
            color: #c92a2a;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alert-item {
            background: white;
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-student {
            font-weight: bold;
            color: #333;
            font-size: 0.85em;
        }

        .alert-reason {
            color: #666;
            font-size: 0.75em;
            margin-top: 2px;
        }

        .action-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.75em;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        /* 실시간 모니터링 */
        .live-monitor {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
        }

        .monitor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .live-dot {
            width: 10px;
            height: 10px;
            background: #40c057;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .activity-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .activity-item {
            background: white;
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 6px;
            border-left: 3px solid #667eea;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .activity-time {
            font-size: 0.7em;
            color: #999;
        }

        .activity-text {
            margin-top: 3px;
            color: #333;
            font-size: 0.8em;
        }

        /* 차트 컨테이너 */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .chart-title {
            font-size: 1em;
            color: #333;
            margin-bottom: 10px;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            padding: 20px 0;
        }

        .bar {
            width: 60px;
            background: linear-gradient(to top, #667eea, #764ba2);
            border-radius: 5px 5px 0 0;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bar:hover {
            transform: scaleY(1.1);
            box-shadow: 0 -5px 15px rgba(102, 126, 234, 0.3);
        }

        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.9em;
            color: #666;
            white-space: nowrap;
        }

        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-weight: bold;
            color: #333;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            h1 {
                font-size: 1.4em;
                margin-bottom: 10px;
            }

            .monitoring-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .metric-card {
                padding: 10px;
            }

            .metric-value {
                font-size: 1.5em;
            }

            .monitoring-section {
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            .monitoring-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 KTM 학생관리 프로세스 & 이탈방지 시스템</h1>

        <!-- 이탈 상황 대응 대시보드 -->
        <div class="monitoring-section">
            <h2 class="process-title" style="margin-bottom: 15px;">🛡️ 이탈 방지 모니터링</h2>
            
            <div class="monitoring-grid">
                <!-- 주요 지표 카드들 -->
                <div class="metric-card">
                    <div class="metric-label">전체 등록 학생</div>
                    <div class="metric-value"><?php echo $totalStudents; ?>명</div>
                    <span class="metric-status status-safe">정상 운영</span>
                </div>

                <div class="metric-card">
                    <div class="metric-label">포모도르 관심필요</div>
                    <div class="metric-value"><?php echo $pomodoroConcernCount; ?>명</div>
                    <span class="metric-status <?php echo $pomodoroConcernCount > 0 ? 'status-warning' : 'status-safe'; ?>">
                        <?php echo $pomodoroConcernCount > 0 ? '주의 필요' : '양호'; ?>
                    </span>
                </div>

                <div class="metric-card">
                    <div class="metric-label">출결이상 위험</div>
                    <div class="metric-value"><?php echo $attendanceRiskCount; ?>명</div>
                    <span class="metric-status <?php echo $attendanceRiskCount > 0 ? 'status-warning' : 'status-safe'; ?>">
                        <?php echo $attendanceRiskCount > 0 ? '주의 필요' : '양호'; ?>
                    </span>
                </div>

                <div class="metric-card">
                    <div class="metric-label">학습 체계성 위험</div>
                    <div class="metric-value"><?php echo $detectExceptionRiskCount; ?>명</div>
                    <span class="metric-status <?php echo $detectExceptionRiskCount > 0 ? 'status-warning' : 'status-safe'; ?>">
                        <?php echo $detectExceptionRiskCount > 0 ? '주의 필요' : '양호'; ?>
                    </span>
                </div>

                <div class="metric-card">
                    <div class="metric-label">이탈 위험 학생</div>
                    <div class="metric-value"><?php echo $totalRiskStudents; ?>명</div>
                    <span class="metric-status <?php echo $totalRiskStudents > 0 ? 'status-danger' : 'status-safe'; ?>">
                        <?php echo $totalRiskStudents > 0 ? '즉시 대응' : '정상'; ?>
                    </span>
                </div>

                <div class="metric-card">
                    <div class="metric-label">목표설정 평가 완료</div>
                    <div class="metric-value"><?php echo count($goalSpecificityData); ?>명</div>
                    <span class="metric-status status-safe">평가 중</span>
                </div>
            </div>

            <!-- 요약 정보 -->
            <div class="live-monitor" style="margin-top: 12px;">
                <div class="monitor-header">
                    <h3 style="color: #333; font-size: 0.95em; margin: 0;">📋 분석 요약 (최근 4주간)</h3>
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        <span style="color: #40c057; font-weight: bold; font-size: 0.75em;">최신</span>
                    </div>
                </div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-time">검토 기간</div>
                        <div class="activity-text"><?php echo date('Y-m-d', $fourWeeksAgoSunday); ?> ~ <?php echo date('Y-m-d', $lastSunday); ?> (최근 일요일부터 4주간)</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-time">포모도르 분석</div>
                        <div class="activity-text">5개 이하 학생: <?php echo count($pomodoroStudents); ?>명, 관심필요: <?php echo $pomodoroConcernCount; ?>명</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-time">출결 분석</div>
                        <div class="activity-text">지각률 30% 이상 위험 학생: <?php echo $attendanceRiskCount; ?>명</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-time">학습 체계성</div>
                        <div class="activity-text">상위 15% 위험군: <?php echo $detectExceptionRiskCount; ?>명</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-time">목표설정 평가</div>
                        <div class="activity-text">평가 완료: <?php echo count($goalSpecificityData); ?>명</div>
                    </div>
                </div>
            </div>

            <!-- 포모도르 관심필요 학생 -->
            <?php if (!empty($pomodoroConcernStudents)): ?>
            <div class="alert-card" style="margin-top: 12px;">
                <div class="alert-title">
                    🍅 포모도르 관심필요 (5개 이하 + 4주간 메모 없음)
                </div>
                <?php foreach ($pomodoroConcernStudents as $student): ?>
                <div class="alert-item">
                    <div>
                        <div class="alert-student"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div class="alert-reason"><?php echo htmlspecialchars($student['reason']); ?></div>
                    </div>
                    <button class="action-btn" onclick="sendKakao('<?php echo htmlspecialchars($student['name']); ?>', <?php echo $student['id']; ?>)">관심필요</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 출결이상 위험 학생 -->
            <?php if (!empty($attendanceRiskStudents)): ?>
            <div class="alert-card" style="margin-top: 12px; border-color: #ff9800;">
                <div class="alert-title" style="color: #e65100;">
                    ⏰ 출결이상 위험 (지각률 30% 이상)
                </div>
                <?php foreach ($attendanceRiskStudents as $student): ?>
                <div class="alert-item">
                    <div>
                        <div class="alert-student"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div class="alert-reason">지각률 <?php echo $student['late_rate']; ?>% (<?php echo $student['late_count']; ?>/<?php echo $student['total_count']; ?>)</div>
                    </div>
                    <button class="action-btn" onclick="sendKakao('<?php echo htmlspecialchars($student['name']); ?>', <?php echo $student['id']; ?>)">상담 예약</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 학습 체계성 위험 학생 -->
            <?php if (!empty($detectExceptionRiskStudents)): ?>
            <div class="alert-card" style="margin-top: 12px; border-color: #f44336;">
                <div class="alert-title" style="color: #c62828;">
                    📊 학습 체계성 위험 (상위 15%)
                </div>
                <?php foreach ($detectExceptionRiskStudents as $student): ?>
                <div class="alert-item">
                    <div>
                        <div class="alert-student"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div class="alert-reason">
                            총 <?php echo $student['total_count']; ?>회 
                            <?php if (!empty($student['details'])): ?>
                                (<?php 
                                $detailParts = [];
                                foreach ($student['details'] as $type => $count) {
                                    $detailParts[] = htmlspecialchars($type) . ': ' . $count;
                                }
                                echo implode(', ', $detailParts);
                                ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="action-btn" onclick="sendKakao('<?php echo htmlspecialchars($student['name']); ?>', <?php echo $student['id']; ?>)">상담 예약</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 목표설정 구체성 평가 결과 -->
            <?php if (!empty($goalSpecificityData)): ?>
            <div class="chart-container" style="margin-top: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <h3 class="chart-title" style="margin: 0;">🎯 목표설정 구체성 평가 (최근 4주)</h3>
                    <div style="display: flex; gap: 8px;">
                        <a href="student_analysis.php?userid=<?php echo $teacherid; ?>" 
                           style="font-size: 0.8em; color: #667eea; text-decoration: none; padding: 5px 10px; border: 1px solid #667eea; border-radius: 5px; transition: all 0.2s;">
                            학생별 분석보기 →
                        </a>
                        <button onclick="reanalyzeGoals()" 
                                style="font-size: 0.8em; color: white; background: #667eea; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; transition: all 0.2s;">
                            🔄 재분석 실행
                        </button>
                    </div>
                </div>
                <?php if ($reanalyze): ?>
                <div style="background: #d4edda; color: #155724; padding: 8px; border-radius: 5px; margin-bottom: 10px; font-size: 0.85em;">
                    ✅ 재분석이 완료되었습니다.
                </div>
                <?php endif; ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.8em;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #667eea;">
                            <th style="padding: 6px; text-align: left;">학생명</th>
                            <th style="padding: 6px; text-align: center;">분기</th>
                            <th style="padding: 6px; text-align: center;">주간</th>
                            <th style="padding: 6px; text-align: center;">오늘</th>
                            <th style="padding: 6px; text-align: center;">상관관계</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 상관관계 점수 순으로 정렬 (낮은 점수부터)
                        uasort($goalSpecificityData, function($a, $b) {
                            $scoreA = $a['correlation_score'] ?? 0;
                            $scoreB = $b['correlation_score'] ?? 0;
                            return $scoreA - $scoreB;
                        });
                        foreach ($goalSpecificityData as $data): 
                            $getScoreColor = function($score) {
                                if ($score === null) return '#999';
                                return $score >= 4 ? '#4caf50' : ($score >= 3 ? '#ff9800' : '#f44336');
                            };
                        ?>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 6px; font-weight: 500;"><?php echo htmlspecialchars($data['name']); ?></td>
                            <td style="padding: 6px; text-align: center;">
                                <?php if ($data['term_score'] !== null): ?>
                                <span style="font-weight: bold; color: <?php echo $getScoreColor($data['term_score']); ?>;">
                                    <?php echo $data['term_score']; ?>/5
                                </span>
                                <?php else: ?>
                                <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <?php if ($data['weekly_score'] !== null): ?>
                                <span style="font-weight: bold; color: <?php echo $getScoreColor($data['weekly_score']); ?>;">
                                    <?php echo $data['weekly_score']; ?>/5
                                </span>
                                <?php else: ?>
                                <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <?php if ($data['today_score'] !== null): ?>
                                <span style="font-weight: bold; color: <?php echo $getScoreColor($data['today_score']); ?>;">
                                    <?php echo $data['today_score']; ?>/5
                                </span>
                                <?php else: ?>
                                <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <?php if ($data['correlation_score'] !== null): ?>
                                <span style="font-weight: bold; color: <?php echo $getScoreColor($data['correlation_score']); ?>; font-size: 1.05em;">
                                    <?php echo $data['correlation_score']; ?>/5
                                </span>
                                <?php else: ?>
                                <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- 학습 체계성 유형별 통계 -->
            <?php if (!empty($detectExceptionData['by_type'])): ?>
            <div class="chart-container" style="margin-top: 12px;">
                <h3 class="chart-title">📈 학습 체계성 유형별 통계</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.85em;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #667eea;">
                            <th style="padding: 6px; text-align: left;">유형</th>
                            <th style="padding: 6px; text-align: right;">횟수</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        arsort($detectExceptionData['by_type']);
                        foreach ($detectExceptionData['by_type'] as $type => $count): 
                        ?>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 6px;"><?php echo htmlspecialchars($type); ?></td>
                            <td style="padding: 6px; text-align: right; font-weight: bold;"><?php echo number_format($count); ?>회</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        // 재분석 실행 함수
        function reanalyzeGoals() {
            if (confirm('목표설정 구체성 분석을 다시 수행하시겠습니까?\n\n이 작업은 시간이 걸릴 수 있습니다.')) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('reanalyze', '1');
                window.location.href = currentUrl.toString();
            }
        }
        
        // 카톡 발송 시뮬레이션
        function sendKakao(studentName, studentId) {
            const btn = event.target;
            const originalText = btn.textContent;
            
            btn.textContent = '처리 중...';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            
            // 실제로는 여기서 AJAX 호출하여 서버에 알림
            setTimeout(() => {
                btn.textContent = '✓ 처리완료';
                btn.style.background = 'linear-gradient(135deg, #51cf66, #37b24d)';
                
                // 알림 표시
                showNotification(studentName + ' 학생에 대한 조치가 기록되었습니다.');
                
                // 활동 로그 추가
                addActivityLog('📨 ' + studentName + ' 학생 - 조치 기록 완료');
            }, 1500);
        }

        // 알림 표시 함수
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); z-index: 1000; animation: slideInRight 0.5s ease; display: flex; align-items: center; gap: 10px;';
            notification.innerHTML = '<span style="font-size: 1.5em;">✅</span><span>' + message + '</span>';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // 활동 로그 추가 함수
        function addActivityLog(message) {
            const activityList = document.querySelector('.activity-list');
            const newItem = document.createElement('div');
            newItem.className = 'activity-item';
            
            const now = new Date();
            const timeString = now.toLocaleTimeString('ko-KR', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            newItem.innerHTML = '<div class="activity-time">' + timeString + '</div><div class="activity-text">' + message + '</div>';
            
            activityList.insertBefore(newItem, activityList.firstChild);
            
            // 오래된 항목 제거 (최대 10개 유지)
            while (activityList.children.length > 10) {
                activityList.removeChild(activityList.lastChild);
            }
        }

        // 애니메이션 스타일 추가
        const style = document.createElement('style');
        style.textContent = '@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }';
        document.head.appendChild(style);

        // 페이지 새로고침으로 데이터 업데이트 (실시간 업데이트는 서버 부하를 고려하여 제거)
        // 필요시 AJAX로 주기적 업데이트 가능

        // 프로세스 단계 클릭 이벤트
        document.querySelectorAll('.step-icon').forEach((icon, index) => {
            icon.addEventListener('click', () => {
                const steps = [
                    '신규등록 프로세스가 시작되었습니다.',
                    '첫수업 진단평가를 진행합니다.',
                    'KTM앱 카톡 공지 설정을 확인합니다.',
                    '학습 데이터와 지도 데이터를 생성합니다.',
                    '학부모앱 방문 모니터링을 시작합니다.',
                    '무방문 학생을 발견하고 대응합니다.'
                ];
                
                showNotification(steps[index]);
            });
        });

        // 페이지 로드 시 초기 애니메이션
        window.addEventListener('load', () => {
            document.querySelectorAll('.metric-card, .activity-item').forEach((el, i) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    el.style.transition = 'all 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, i * 100);
            });
        });
    </script>
</body>
</html>

