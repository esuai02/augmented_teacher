<?php
/**
 * Dashboard Goals Data Extraction
 * Extracts learning goals and quiz data for dashboard display
 * Excludes mdl_alt42t_* tables and student personal info
 */

function getDashboardGoals($userid) {
    global $DB;
    
    $dashboardData = array();
    $timecreated = time();
    $twoWeeksAgo = $timecreated - (604800 * 2);
    $oneWeekAgo = $timecreated - 604800;
    $todayStart = strtotime('today');
    
    // 기본 응답 구조
    $defaultResponse = array(
        'success' => true,
        'today_goal' => null,
        'weekly_goal' => null,
        'recent_goals' => array(),
        'quarter_goals' => array(),
        'quiz_data' => array(
            'internal_tests' => array(),
            'standard_tests' => array()
        ),
        'roadmap_missions' => array()
    );
    
    try {
        // 테이블 존재 여부 확인
        $dbman = $DB->get_manager();
        
        // abessi_today 테이블 확인
        $table_today = new xmldb_table('abessi_today');
        if (!$dbman->table_exists($table_today)) {
            return $defaultResponse;
        }
        
        // 1. 오늘 목표 (최신 1건) - 안전하게 처리
        $todayGoal = null;
        try {
            $todayGoal = $DB->get_record_sql(
                "SELECT * FROM {abessi_today} 
                 WHERE userid = ? AND type LIKE '오늘목표' 
                 ORDER BY id DESC LIMIT 1", 
                array($userid)
            );
        } catch (Exception $e) {
            // 오류 무시하고 null 유지
        }
        
        // 2. 주간 목표 (최신 1건)
        $weeklyGoal = $DB->get_record_sql(
            "SELECT * FROM {abessi_today} 
             WHERE userid = ? AND type LIKE '주간목표' 
             ORDER BY id DESC LIMIT 1", 
            array($userid)
        );
        
        // 3. 최근 목표 기록 (최근 2주간)
        $recentGoals = $DB->get_records_sql(
            "SELECT * FROM {abessi_today} 
             WHERE userid = ? AND timecreated > ? 
             AND (type LIKE '오늘목표' OR type LIKE '주간목표')
             ORDER BY timecreated DESC 
             LIMIT 10", 
            array($userid, $twoWeeksAgo)
        );
        
        // 4. 분기목표/로드맵 (최근 3건)
        $quarterGoals = $DB->get_records_sql(
            "SELECT id, title, content AS memo, plandate AS deadline, 
                    plantype, dream AS dreamchallenge, dreamurl,
                    timecreated
             FROM {abessi_progress} 
             WHERE userid = ? AND hide = 0 
             AND (plantype = '분기목표' OR plantype = '방향설정')
             ORDER BY id DESC 
             LIMIT 3", 
            array($userid)
        );
        
        // D-Day 계산을 PHP에서 처리
        foreach ($quarterGoals as &$goal) {
            if (!empty($goal->deadline)) {
                $deadline_time = strtotime($goal->deadline);
                $today_time = strtotime('today');
                $goal->dday = floor(($deadline_time - $today_time) / (60 * 60 * 24));
            } else {
                $goal->dday = null;
            }
        }
        
        // 5. 퀴즈 데이터 (최근 1주간)
        $quizData = array();
        
        // 전체 퀴즈 시도 (1주간)
        $weeklyQuizAttempts = $DB->get_records_sql(
            "SELECT qa.*, q.name AS quiz_name, q.sumgrades AS total_grades,
                    c.fullname AS course_name,
                    CASE 
                        WHEN q.name LIKE '%내신%' THEN 'internal'
                        WHEN q.name LIKE '%표준%' THEN 'standard'
                        ELSE 'other'
                    END AS quiz_type
             FROM {quiz_attempts} qa
             JOIN {quiz} q ON qa.quiz = q.id
             JOIN {course} c ON q.course = c.id
             WHERE qa.userid = ? 
             AND qa.timestart > ?
             AND qa.state = 'finished'
             ORDER BY qa.timestart DESC", 
            array($userid, $oneWeekAgo)
        );
        
        // 퀴즈 통계 계산
        $totalQuizzes = count($weeklyQuizAttempts);
        $todayQuizzes = 0;
        $todayScores = array();
        $weeklyScores = array();
        $internalTests = array();
        $standardTests = array();
        
        foreach ($weeklyQuizAttempts as $attempt) {
            // 점수 계산 (백분율)
            if ($attempt->sumgrades && $attempt->total_grades > 0) {
                $score = round(($attempt->sumgrades / $attempt->total_grades) * 100, 1);
                $weeklyScores[] = $score;
                
                // 오늘 시도한 퀴즈
                if ($attempt->timestart >= $todayStart) {
                    $todayQuizzes++;
                    $todayScores[] = $score;
                }
                
                // 퀴즈 유형별 분류
                $quizInfo = array(
                    'name' => $attempt->quiz_name,
                    'course' => $attempt->course_name,
                    'score' => $score,
                    'date' => date('Y-m-d H:i', $attempt->timestart)
                );
                
                if ($attempt->quiz_type == 'internal') {
                    $internalTests[] = $quizInfo;
                } elseif ($attempt->quiz_type == 'standard') {
                    $standardTests[] = $quizInfo;
                }
            }
        }
        
        // 평균 계산
        $todayAverage = !empty($todayScores) ? round(array_sum($todayScores) / count($todayScores), 1) : 0;
        $weeklyAverage = !empty($weeklyScores) ? round(array_sum($weeklyScores) / count($weeklyScores), 1) : 0;
        
        // 최근 5개씩만 유지
        $internalTests = array_slice($internalTests, 0, 5);
        $standardTests = array_slice($standardTests, 0, 5);
        
        $quizData = array(
            'total_count' => $totalQuizzes,
            'today_count' => $todayQuizzes,
            'today_average' => $todayAverage,
            'weekly_average' => $weeklyAverage,
            'internal_tests' => $internalTests,
            'standard_tests' => $standardTests
        );
        
        // 결과 조합
        $dashboardData = array(
            'success' => true,
            'today_goal' => $todayGoal,
            'weekly_goal' => $weeklyGoal,
            'recent_goals' => $recentGoals,
            'quarter_goals' => $quarterGoals,
            'quiz_data' => $quizData
        );
        
    } catch (Exception $e) {
        // 오류 발생 시 기본 응답 반환
        error_log('get_dashboard_goals.php 오류: ' . $e->getMessage());
        return $defaultResponse;
    }
    
    return $dashboardData;
}

// API 엔드포인트로 사용 시
if (basename($_SERVER['PHP_SELF']) == 'get_dashboard_goals.php') {
    header('Content-Type: application/json; charset=utf-8');
    
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
    require_login();
    
    $userid = optional_param('userid', $USER->id, PARAM_INT);
    
    $data = getDashboardGoals($userid);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
?>