<?php
/**
 * 빠른 요약 저장 AJAX 핸들러
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$chapter = $input['chapter'] ?? '';
$content = $input['content'] ?? '';

if (empty($chapter) || empty($content)) {
    echo json_encode(['success' => false, 'error' => '챕터와 내용을 모두 입력해주세요.']);
    exit;
}

// 설정 파일 포함
$config_path = '../config.php';
if (file_exists($config_path)) {
    require_once($config_path);
} else {
    echo json_encode(['success' => false, 'error' => '설정 파일을 찾을 수 없습니다.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // MathKing DB 연결
    $pdo = get_mathking_db_connection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }

    // mdl_abessi_today 테이블에 요약 저장
    $today = date('Y-m-d');
    $current_time = time();
    
    // 오늘 데이터가 있는지 확인
    $stmt = $pdo->prepare("
        SELECT id, goals 
        FROM mdl_abessi_today 
        WHERE userid = ? AND date = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $today]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // 기존 데이터에 요약 추가
        $goals = json_decode($existing['goals'], true) ?: [];
        if (!isset($goals['summaries'])) {
            $goals['summaries'] = [];
        }
        
        $goals['summaries'][] = [
            'chapter' => $chapter,
            'content' => $content,
            'time' => date('H:i'),
            'timestamp' => $current_time
        ];
        
        $stmt = $pdo->prepare("
            UPDATE mdl_abessi_today 
            SET goals = ?, timemodified = ? 
            WHERE id = ?
        ");
        $stmt->execute([json_encode($goals, JSON_UNESCAPED_UNICODE), $current_time, $existing['id']]);
        
    } else {
        // 새 데이터 생성
        $goals = [
            'summaries' => [[
                'chapter' => $chapter,
                'content' => $content,
                'time' => date('H:i'),
                'timestamp' => $current_time
            ]]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_today (userid, date, goals, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $today, json_encode($goals, JSON_UNESCAPED_UNICODE), $current_time, $current_time]);
    }

    echo json_encode([
        'success' => true,
        'message' => '요약이 저장되었습니다.',
        'time' => date('H:i')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Quick save summary error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '저장 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
?>