<?php
/**
 * 패턴 수집 API
 * 사용자가 새로운 패턴을 발견했을 때 처리
 */

// Moodle 설정 포함
require_once(__DIR__ . '/../../../../../../../config.php');
global $DB, $USER, $CFG;

// 로그인 확인
require_login();

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$user_id = isset($input['user_id']) ? intval($input['user_id']) : $USER->id;
$pattern_id = isset($input['pattern_id']) ? intval($input['pattern_id']) : 0;

try {
    // pattern_id는 이미 DB의 id와 동일 (우리는 id=pattern_id로 삽입했음)
    $pattern = $DB->get_record('alt42i_math_patterns', ['id' => $pattern_id]);
    
    if (!$pattern) {
        throw new Exception('Pattern not found');
    }
    
    // 사용자 진행 상황 확인
    $progress = $DB->get_record('alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'pattern_id' => $pattern_id
    ]);
    
    if ($progress) {
        // 이미 수집된 경우 업데이트만
        if (!$progress->is_collected) {
            $progress->is_collected = 1;
            $progress->updated_at = date('Y-m-d H:i:s');
            $DB->update_record('alt42i_user_pattern_progress', $progress);
        }
    } else {
        // 새로 수집
        $progress = new stdClass();
        $progress->user_id = $user_id;
        $progress->pattern_id = $pattern_id;
        $progress->is_collected = 1;
        $progress->mastery_level = 0;
        $progress->practice_count = 0;
        $progress->created_at = date('Y-m-d H:i:s');
        $progress->updated_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('alt42i_user_pattern_progress', $progress);
    }
    
    // 전체 수집 개수 계산
    $collected_count = $DB->count_records('alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'is_collected' => 1
    ]);
    
    // 응답
    echo json_encode([
        'success' => true,
        'pattern_id' => $pattern_id,
        'collected_count' => $collected_count,
        'total_patterns' => 60
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>