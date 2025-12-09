<?php
/**
 * 빠른 오답노트 저장 AJAX 핸들러
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
$problem = $input['problem'] ?? '';
$error_reason = $input['error_reason'] ?? '';
$correct_solution = $input['correct_solution'] ?? '';

if (empty($problem) || empty($error_reason)) {
    echo json_encode(['success' => false, 'error' => '문제와 오답 이유를 모두 입력해주세요.']);
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

    // 오답 유형 자동 분류
    $error_type = 'careless'; // 기본값
    if (strpos($error_reason, '개념') !== false || strpos($error_reason, '이해') !== false) {
        $error_type = 'concept';
    } elseif (strpos($error_reason, '계산') !== false || strpos($error_reason, '연산') !== false) {
        $error_type = 'calculation';
    } elseif (strpos($error_reason, '응용') !== false || strpos($error_reason, '적용') !== false) {
        $error_type = 'application';
    }

    // mdl_abessi_mathtalk 테이블에 오답노트 저장
    $current_time = time();
    $content = json_encode([
        'problem' => $problem,
        'error_reason' => $error_reason,
        'correct_solution' => $correct_solution,
        'error_type' => $error_type,
        'created_time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_mathtalk (userid, type, content, timecreated)
        VALUES (?, 'error_analysis', ?, ?)
    ");
    $stmt->execute([$user_id, $content, $current_time]);

    // 오답 유형별 한글 라벨
    $type_labels = [
        'calculation' => '계산 실수',
        'concept' => '개념 부족',
        'application' => '응용 부족',
        'careless' => '부주의'
    ];

    echo json_encode([
        'success' => true,
        'message' => '오답노트가 저장되었습니다.',
        'error_type' => $type_labels[$error_type],
        'time' => date('H:i')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Quick save error note error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '저장 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
?>