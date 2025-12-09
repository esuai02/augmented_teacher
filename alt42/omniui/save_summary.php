<?php
header('Content-Type: application/json; charset=utf-8');

// 세션 시작
session_start();

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// 데이터베이스 연결
require_once 'config.php';

try {
    // PDO 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // POST 데이터 받기
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('잘못된 요청입니다.');
    }
    
    $user_id = $_SESSION['user_id'];
    $chapter_title = $data['chapter_title'] ?? '';
    $summary_text = $data['summary_text'] ?? '';
    $key_concepts = $data['key_concepts'] ?? [];
    $difficulty_level = intval($data['difficulty_level'] ?? 3);
    $confidence_score = intval($data['confidence_score'] ?? 50);
    $study_time = intval($data['study_time'] ?? 0);
    
    // 입력 검증
    if (empty($chapter_title) || empty($summary_text)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }
    
    // 요약 품질 점수 계산
    $quality_score = calculateSummaryQuality($summary_text, $key_concepts);
    
    // 기존 mdl_abessi_today 테이블에 저장 (오늘의 목표/요약)
    $today_data = [
        'chapter' => $chapter_title,
        'summary' => $summary_text,
        'concepts' => json_encode($key_concepts, JSON_UNESCAPED_UNICODE),
        'quality' => $quality_score,
        'confidence' => $confidence_score,
        'difficulty' => $difficulty_level,
        'study_time' => $study_time
    ];
    
    // mdl_abessi_today 업데이트 또는 삽입
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_today (userid, goals, timecreated, timemodified)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        goals = ?, timemodified = ?
    ");
    
    $goals_json = json_encode($today_data, JSON_UNESCAPED_UNICODE);
    $current_time = time();
    
    $stmt->execute([
        $user_id,
        $goals_json,
        $current_time,
        $current_time,
        $goals_json,
        $current_time
    ]);
    
    // mdl_abessi_chapterlog에도 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_chapterlog 
        (userid, chapter, progress, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $progress_data = [
        'summary' => $summary_text,
        'quality' => $quality_score,
        'confidence' => $confidence_score
    ];
    
    $stmt->execute([
        $user_id,
        $chapter_title,
        json_encode($progress_data, JSON_UNESCAPED_UNICODE),
        $current_time
    ]);
    
    // 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog 
        (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        'summary_writing',
        $current_time
    ]);
    
    // 진행 상황 업데이트
    updateStudentProgress($pdo, $user_id, $quality_score);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '요약이 성공적으로 저장되었습니다.',
        'data' => [
            'quality_score' => $quality_score,
            'badge_earned' => checkBadgeEarned($pdo, $user_id, $quality_score),
            'streak_count' => getStreakCount($pdo, $user_id),
            'total_summaries' => getTotalSummaries($pdo, $user_id)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Save summary error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// 요약 품질 점수 계산 함수
function calculateSummaryQuality($text, $concepts) {
    $score = 50; // 기본 점수
    
    // 텍스트 길이 체크 (200-800자 적정)
    $length = mb_strlen($text);
    if ($length >= 200 && $length <= 800) {
        $score += 20;
    } elseif ($length >= 100 && $length <= 1000) {
        $score += 10;
    }
    
    // 핵심 개념 포함 여부
    if (count($concepts) >= 3) {
        $score += 15;
    }
    
    // 문장 구조 체크 (마침표 개수)
    $sentences = preg_match_all('/[.!?]/', $text);
    if ($sentences >= 3 && $sentences <= 10) {
        $score += 15;
    }
    
    return min(100, $score);
}

// 학생 진행 상황 업데이트
function updateStudentProgress($pdo, $user_id, $quality_score) {
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_progress 
        (userid, progress_data, timecreated, timemodified)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        progress_data = ?,
        timemodified = ?
    ");
    
    // 기존 진행 상황 가져오기
    $existing = $pdo->prepare("SELECT progress_data FROM mdl_abessi_progress WHERE userid = ?");
    $existing->execute([$user_id]);
    $current_progress = $existing->fetchColumn();
    
    $progress = $current_progress ? json_decode($current_progress, true) : [];
    
    // 진행 상황 업데이트
    if (!isset($progress['summaries'])) {
        $progress['summaries'] = [];
    }
    
    $progress['summaries'][] = [
        'date' => date('Y-m-d'),
        'quality' => $quality_score,
        'timestamp' => time()
    ];
    
    // 평균 품질 계산
    $total_quality = array_sum(array_column($progress['summaries'], 'quality'));
    $progress['average_quality'] = $total_quality / count($progress['summaries']);
    
    $progress_json = json_encode($progress, JSON_UNESCAPED_UNICODE);
    $current_time = time();
    
    $stmt->execute([
        $user_id,
        $progress_json,
        $current_time,
        $current_time,
        $progress_json,
        $current_time
    ]);
}

// 배지 획득 체크
function checkBadgeEarned($pdo, $user_id, $quality_score) {
    $badges = [];
    
    if ($quality_score >= 90) {
        $badges[] = ['name' => '완벽한 요약', 'icon' => '🏆'];
    }
    
    if ($quality_score >= 75) {
        $badges[] = ['name' => '우수 요약', 'icon' => '⭐'];
    }
    
    // 연속 요약 체크
    $streak = getStreakCount($pdo, $user_id);
    if ($streak >= 7) {
        $badges[] = ['name' => '일주일 연속', 'icon' => '🔥'];
    }
    
    return $badges;
}

// 연속 일수 계산
function getStreakCount($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timecreated))) as days
        FROM mdl_abessi_missionlog
        WHERE userid = ? 
        AND page = 'summary_writing'
        AND timecreated >= ?
    ");
    
    $seven_days_ago = time() - (7 * 24 * 60 * 60);
    $stmt->execute([$user_id, $seven_days_ago]);
    
    return $stmt->fetchColumn();
}

// 총 요약 개수
function getTotalSummaries($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_chapterlog
        WHERE userid = ? 
        AND progress LIKE '%summary%'
    ");
    
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>