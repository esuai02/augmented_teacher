<?php
header('Content-Type: application/json; charset=utf-8');

// 세션 시작
session_start();

// 데이터베이스 연결
require_once 'config.php';

try {
    // PDO 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // 파라미터 가져오기 (GET 또는 POST)
    $user_id = $_REQUEST['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('사용자 ID가 필요합니다.');
    }
    
    $response = [];
    
    // 1. 사용자 기본 정보
    $stmt = $pdo->prepare("
        SELECT id, username, firstname, lastname, email
        FROM mdl_user 
        WHERE id = ? AND deleted = 0
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('사용자를 찾을 수 없습니다.');
    }
    
    $response['user'] = [
        'id' => $user['id'],
        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        'username' => $user['username']
    ];
    
    // 2. 최근 활동 (mdl_abessi_missionlog)
    $stmt = $pdo->prepare("
        SELECT page, COUNT(*) as count, MAX(timecreated) as last_activity
        FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated > ?
        GROUP BY page
        ORDER BY last_activity DESC
        LIMIT 10
    ");
    
    $seven_days_ago = time() - (7 * 24 * 60 * 60);
    $stmt->execute([$user_id, $seven_days_ago]);
    $recent_activities = $stmt->fetchAll();
    
    $response['recent_activities'] = $recent_activities;
    
    // 3. 출결 통계 (mdl_abessi_attendance_record)
    $stmt = $pdo->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(hours) as total_hours
        FROM mdl_abessi_attendance_record
        WHERE userid = ? AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY type
    ");
    $stmt->execute([$user_id]);
    $attendance = $stmt->fetchAll();
    
    $response['attendance'] = $attendance;
    
    // 4. 학습 진행 상황 (mdl_abessi_progress)
    $stmt = $pdo->prepare("
        SELECT progress_data, timemodified
        FROM mdl_abessi_progress
        WHERE userid = ?
        ORDER BY timemodified DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $progress = $stmt->fetch();
    
    if ($progress) {
        $progress_data = json_decode($progress['progress_data'], true);
        $response['progress'] = $progress_data;
    } else {
        $response['progress'] = [
            'summaries' => [],
            'error_patterns' => [],
            'average_quality' => 0
        ];
    }
    
    // 5. 최근 요약 (mdl_abessi_today)
    $stmt = $pdo->prepare("
        SELECT goals, timecreated, timemodified
        FROM mdl_abessi_today
        WHERE userid = ?
        ORDER BY timemodified DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_summaries = [];
    
    while ($row = $stmt->fetch()) {
        $goals = json_decode($row['goals'], true);
        if ($goals && isset($goals['summary'])) {
            $recent_summaries[] = [
                'chapter' => $goals['chapter'] ?? '제목 없음',
                'summary' => $goals['summary'],
                'quality' => $goals['quality'] ?? 0,
                'confidence' => $goals['confidence'] ?? 0,
                'date' => date('Y-m-d H:i', $row['timemodified'])
            ];
        }
    }
    
    $response['recent_summaries'] = $recent_summaries;
    
    // 6. 오답 분석 (mdl_abessi_mathtalk)
    $stmt = $pdo->prepare("
        SELECT content, timecreated
        FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
        ORDER BY timecreated DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_errors = [];
    
    while ($row = $stmt->fetch()) {
        $content = json_decode($row['content'], true);
        if ($content) {
            $recent_errors[] = [
                'problem' => $content['problem'] ?? '',
                'error_type' => $content['error_type'] ?? '',
                'chapter' => $content['chapter'] ?? '',
                'analysis_score' => $content['analysis_score'] ?? 0,
                'date' => date('Y-m-d H:i', $row['timecreated'])
            ];
        }
    }
    
    $response['recent_errors'] = $recent_errors;
    
    // 7. 챕터별 진행 상황 (mdl_abessi_chapterlog)
    $stmt = $pdo->prepare("
        SELECT 
            chapter,
            COUNT(*) as attempts,
            MAX(timecreated) as last_study
        FROM mdl_abessi_chapterlog
        WHERE userid = ? AND timecreated > ?
        GROUP BY chapter
        ORDER BY last_study DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $seven_days_ago]);
    $chapter_progress = $stmt->fetchAll();
    
    $response['chapter_progress'] = $chapter_progress;
    
    // 8. 학습 통계 계산
    $stats = calculateLearningStats($pdo, $user_id);
    $response['stats'] = $stats;
    
    // 9. 주간 트렌드
    $weekly_trend = getWeeklyTrend($pdo, $user_id);
    $response['weekly_trend'] = $weekly_trend;
    
    // 10. 배지 및 성취
    $achievements = getAchievements($pdo, $user_id);
    $response['achievements'] = $achievements;
    
    // 성공 응답
    $response['success'] = true;
    $response['timestamp'] = time();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Get learning data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// 학습 통계 계산 함수
function calculateLearningStats($pdo, $user_id) {
    $stats = [];
    
    // 총 학습 시간 (활동 로그 기반)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) * 5 as total_minutes
        FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated > ?
    ");
    
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    $stmt->execute([$user_id, $thirty_days_ago]);
    $study_time = $stmt->fetchColumn();
    
    $stats['total_study_time'] = round($study_time / 60, 1); // 시간으로 변환
    
    // 요약 작성 수
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND page = 'summary_writing'
        AND timecreated > ?
    ");
    $stmt->execute([$user_id, $thirty_days_ago]);
    $stats['total_summaries'] = $stmt->fetchColumn();
    
    // 오답 분석 수
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
        AND timecreated > ?
    ");
    $stmt->execute([$user_id, $thirty_days_ago]);
    $stats['total_error_analyses'] = $stmt->fetchColumn();
    
    // 연속 학습 일수
    $streak = calculateStreak($pdo, $user_id);
    $stats['study_streak'] = $streak;
    
    // 평균 품질 점수 (진행 상황에서)
    $stmt = $pdo->prepare("
        SELECT progress_data FROM mdl_abessi_progress
        WHERE userid = ?
    ");
    $stmt->execute([$user_id]);
    $progress_data = $stmt->fetchColumn();
    
    if ($progress_data) {
        $progress = json_decode($progress_data, true);
        $stats['average_quality'] = $progress['average_quality'] ?? 0;
    } else {
        $stats['average_quality'] = 0;
    }
    
    return $stats;
}

// 연속 학습 일수 계산
function calculateStreak($pdo, $user_id) {
    $streak = 0;
    $current_date = date('Y-m-d');
    
    for ($i = 0; $i < 365; $i++) {
        $check_date = date('Y-m-d', strtotime("-{$i} days"));
        $start_time = strtotime($check_date . ' 00:00:00');
        $end_time = strtotime($check_date . ' 23:59:59');
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM mdl_abessi_missionlog
            WHERE userid = ? 
            AND timecreated BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start_time, $end_time]);
        
        if ($stmt->fetchColumn() > 0) {
            $streak++;
        } else {
            if ($i > 0) break; // 오늘이 아닌 날에 활동이 없으면 중단
        }
    }
    
    return $streak;
}

// 주간 트렌드 데이터
function getWeeklyTrend($pdo, $user_id) {
    $trend = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $start_time = strtotime($date . ' 00:00:00');
        $end_time = strtotime($date . ' 23:59:59');
        
        // 해당 날짜의 활동 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as activities
            FROM mdl_abessi_missionlog
            WHERE userid = ? 
            AND timecreated BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start_time, $end_time]);
        $activities = $stmt->fetchColumn();
        
        // 해당 날짜의 요약 수
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as summaries
            FROM mdl_abessi_missionlog
            WHERE userid = ? 
            AND page = 'summary_writing'
            AND timecreated BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start_time, $end_time]);
        $summaries = $stmt->fetchColumn();
        
        $trend[] = [
            'date' => $date,
            'day' => date('D', strtotime($date)),
            'activities' => $activities,
            'summaries' => $summaries,
            'study_time' => $activities * 5 // 추정 학습 시간 (분)
        ];
    }
    
    return $trend;
}

// 성취 및 배지
function getAchievements($pdo, $user_id) {
    $achievements = [];
    
    // 총 요약 수 기반 배지
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND page = 'summary_writing'
    ");
    $stmt->execute([$user_id]);
    $total_summaries = $stmt->fetchColumn();
    
    if ($total_summaries >= 100) {
        $achievements[] = ['name' => '요약 마스터', 'icon' => '🏆', 'description' => '100개 이상 요약 작성'];
    } elseif ($total_summaries >= 50) {
        $achievements[] = ['name' => '요약 전문가', 'icon' => '⭐', 'description' => '50개 이상 요약 작성'];
    } elseif ($total_summaries >= 10) {
        $achievements[] = ['name' => '요약 입문', 'icon' => '🌟', 'description' => '10개 이상 요약 작성'];
    }
    
    // 연속 학습 기반 배지
    $streak = calculateStreak($pdo, $user_id);
    if ($streak >= 30) {
        $achievements[] = ['name' => '한 달 연속', 'icon' => '🔥', 'description' => '30일 연속 학습'];
    } elseif ($streak >= 7) {
        $achievements[] = ['name' => '일주일 연속', 'icon' => '💪', 'description' => '7일 연속 학습'];
    } elseif ($streak >= 3) {
        $achievements[] = ['name' => '3일 연속', 'icon' => '✨', 'description' => '3일 연속 학습'];
    }
    
    // 오답 분석 기반 배지
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
    ");
    $stmt->execute([$user_id]);
    $total_errors = $stmt->fetchColumn();
    
    if ($total_errors >= 50) {
        $achievements[] = ['name' => '분석가', 'icon' => '🔍', 'description' => '50개 이상 오답 분석'];
    } elseif ($total_errors >= 20) {
        $achievements[] = ['name' => '성실한 학습자', 'icon' => '📝', 'description' => '20개 이상 오답 분석'];
    }
    
    return $achievements;
}
?>