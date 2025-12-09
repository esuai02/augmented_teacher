<?php
/**
 * 최근 요약 목록 조회 AJAX 핸들러
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
    
    $user_id = $_SESSION['user_id'];
    
    // mdl_abessi_today에서 최근 요약 조회
    $stmt = $pdo->prepare("
        SELECT goals, timecreated, timemodified
        FROM mdl_abessi_today
        WHERE userid = ?
        ORDER BY timemodified DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $summaries_raw = $stmt->fetchAll();
    
    $summaries = [];
    foreach ($summaries_raw as $row) {
        $goals = json_decode($row['goals'], true);
        if ($goals && isset($goals['summary'])) {
            $summaries[] = [
                'chapter' => $goals['chapter'] ?? '제목 없음',
                'summary' => $goals['summary'],
                'quality' => $goals['quality'] ?? 0,
                'confidence' => $goals['confidence'] ?? 0,
                'date' => date('Y-m-d H:i', $row['timemodified'])
            ];
        }
    }
    
    // mdl_abessi_mathtalk에서도 요약 데이터 조회
    $stmt = $pdo->prepare("
        SELECT content, timecreated
        FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'summary'
        ORDER BY timecreated DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $mathtalk_summaries = $stmt->fetchAll();
    
    foreach ($mathtalk_summaries as $row) {
        $content = json_decode($row['content'], true);
        if ($content && isset($content['summary'])) {
            $summaries[] = [
                'chapter' => $content['chapter'] ?? '제목 없음',
                'summary' => $content['summary'],
                'quality' => $content['quality'] ?? 0,
                'confidence' => $content['confidence'] ?? 0,
                'date' => date('Y-m-d H:i', $row['timecreated'])
            ];
        }
    }
    
    // 날짜순 정렬
    usort($summaries, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // 최대 10개만 반환
    $summaries = array_slice($summaries, 0, 10);
    
    echo json_encode([
        'success' => true,
        'summaries' => $summaries
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>