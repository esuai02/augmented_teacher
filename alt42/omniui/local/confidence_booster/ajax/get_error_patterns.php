<?php
/**
 * 오답 패턴 분석 조회 AJAX 핸들러
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
    
    // mdl_abessi_mathtalk에서 오답 분석 데이터 조회
    $stmt = $pdo->prepare("
        SELECT content, timecreated
        FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
        ORDER BY timecreated DESC
        LIMIT 100
    ");
    $stmt->execute([$user_id]);
    $errors = $stmt->fetchAll();
    
    // 오류 유형별 통계
    $patterns = [
        'calculation' => 0,
        'concept' => 0,
        'application' => 0,
        'careless' => 0
    ];
    
    $recent_errors = [];
    $count = 0;
    
    foreach ($errors as $row) {
        $content = json_decode($row['content'], true);
        if ($content && isset($content['error_type'])) {
            $type = $content['error_type'];
            if (isset($patterns[$type])) {
                $patterns[$type]++;
            }
            
            // 최근 오답 5개
            if ($count < 5) {
                $recent_errors[] = [
                    'problem' => $content['problem'] ?? '',
                    'chapter' => $content['chapter'] ?? '',
                    'error_type' => $type,
                    'description' => $content['description'] ?? '',
                    'date' => date('Y-m-d H:i', $row['timecreated'])
                ];
                $count++;
            }
        }
    }
    
    // mdl_abessi_progress에서도 오류 패턴 조회
    $stmt = $pdo->prepare("
        SELECT progress_data
        FROM mdl_abessi_progress
        WHERE userid = ?
    ");
    $stmt->execute([$user_id]);
    $progress = $stmt->fetch();
    
    if ($progress) {
        $progress_data = json_decode($progress['progress_data'], true);
        if (isset($progress_data['error_patterns'])) {
            foreach ($progress_data['error_patterns'] as $type => $count) {
                if (isset($patterns[$type])) {
                    $patterns[$type] += $count;
                }
            }
        }
    }
    
    $total = array_sum($patterns);
    
    echo json_encode([
        'success' => true,
        'patterns' => $patterns,
        'total' => $total,
        'recent_errors' => $recent_errors
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>