<?php
/**
 * 실제 데이터 저장 AJAX 핸들러
 * mdl_abessi_today 테이블에 요약 저장
 */

header('Content-Type: application/json; charset=utf-8');

// 세션 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// 설정 파일
require_once('../config.php');

try {
    $pdo = get_confidence_db_connection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // POST 데이터
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'];
    $chapter = $input['chapter'] ?? '';
    $summary = $input['summary'] ?? '';
    $confidence = intval($input['confidence'] ?? 50);
    
    if (empty($chapter) || empty($summary)) {
        throw new Exception('필수 정보를 입력해주세요.');
    }
    
    // 품질 점수 계산
    $quality = 50; // 기본 점수
    $length = mb_strlen($summary);
    if ($length >= 200) $quality += 30;
    elseif ($length >= 100) $quality += 20;
    
    // 문장 수 체크
    preg_match_all('/[.!?]/', $summary, $matches);
    $sentences = count($matches[0]);
    if ($sentences >= 3) $quality += 20;
    
    $quality = min(100, $quality);
    
    // 데이터 구성
    $goals_data = [
        'chapter' => $chapter,
        'summary' => $summary,
        'quality' => $quality,
        'confidence' => $confidence,
        'date' => date('Y-m-d H:i:s')
    ];
    
    // mdl_abessi_today에 저장 (UPSERT)
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_today (userid, goals, timecreated, timemodified)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        goals = VALUES(goals),
        timemodified = VALUES(timemodified)
    ");
    
    $current_time = time();
    $stmt->execute([
        $user_id,
        json_encode($goals_data, JSON_UNESCAPED_UNICODE),
        $current_time,
        $current_time
    ]);
    
    // mdl_abessi_chapterlog에도 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_chapterlog (userid, chapter, progress, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $progress_data = [
        'summary' => $summary,
        'quality' => $quality,
        'confidence' => $confidence
    ];
    
    $stmt->execute([
        $user_id,
        $chapter,
        json_encode($progress_data, JSON_UNESCAPED_UNICODE),
        $current_time
    ]);
    
    // 활동 로그
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$user_id, 'summary_writing', $current_time]);
    
    // mdl_abessi_progress 업데이트
    $stmt = $pdo->prepare("SELECT progress_data FROM mdl_abessi_progress WHERE userid = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    $progress = $existing ? json_decode($existing['progress_data'], true) : [];
    if (!isset($progress['summaries'])) {
        $progress['summaries'] = [];
    }
    
    $progress['summaries'][] = [
        'date' => date('Y-m-d'),
        'quality' => $quality,
        'timestamp' => $current_time
    ];
    
    // 최근 30개만 유지
    if (count($progress['summaries']) > 30) {
        $progress['summaries'] = array_slice($progress['summaries'], -30);
    }
    
    // 평균 계산
    $total = array_sum(array_column($progress['summaries'], 'quality'));
    $progress['average_quality'] = round($total / count($progress['summaries']), 1);
    
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE mdl_abessi_progress 
            SET progress_data = ?, timemodified = ?
            WHERE userid = ?
        ");
        $stmt->execute([
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            $current_time,
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_progress (userid, progress_data, timecreated, timemodified)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            $current_time,
            $current_time
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '요약이 저장되었습니다!',
        'data' => [
            'quality' => $quality,
            'average_quality' => $progress['average_quality']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>