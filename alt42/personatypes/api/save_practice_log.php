<?php
/**
 * 연습 기록 저장 API
 * 사용자의 패턴 연습 기록을 DB에 저장
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
$practice_type = isset($input['practice_type']) ? $input['practice_type'] : 'self';
$answer = isset($input['answer']) ? $input['answer'] : '';
$duration_seconds = isset($input['duration_seconds']) ? intval($input['duration_seconds']) : 180;

try {
    // pattern_id는 이미 DB의 id와 동일
    $pattern = $DB->get_record('alt42i_math_patterns', ['id' => $pattern_id]);
    
    if (!$pattern) {
        throw new Exception('Pattern not found');
    }
    
    // 연습 기록 저장 테이블이 존재하는 경우
    $log_id = null;
    if ($DB->get_manager()->table_exists('alt42i_pattern_practice_logs')) {
        $log = new stdClass();
        $log->user_id = $user_id;
        $log->pattern_id = $pattern_id;
        $log->practice_type = $practice_type;
        $log->duration_seconds = $duration_seconds;
        $log->feedback = $answer;
        $log->is_completed = 1;
        $log->created_at = date('Y-m-d H:i:s');
        
        $log_id = $DB->insert_record('alt42i_pattern_practice_logs', $log);
    }
    
    // 사용자 진행 상황 업데이트
    $progress = $DB->get_record('alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'pattern_id' => $pattern_id
    ]);
    
    if ($progress) {
        // 기존 진행 상황 업데이트
        $progress->practice_count++;
        $progress->last_practice_at = date('Y-m-d H:i:s');
        $progress->updated_at = date('Y-m-d H:i:s');
        
        // 숙달도 증가 (최대 100)
        if ($progress->mastery_level < 100) {
            $progress->mastery_level = min(100, $progress->mastery_level + 5);
        }
        
        $DB->update_record('alt42i_user_pattern_progress', $progress);
    } else {
        // 새로운 진행 상황 생성
        $progress = new stdClass();
        $progress->user_id = $user_id;
        $progress->pattern_id = $pattern_id;
        $progress->is_collected = 1;
        $progress->mastery_level = 5;
        $progress->practice_count = 1;
        $progress->last_practice_at = date('Y-m-d H:i:s');
        $progress->created_at = date('Y-m-d H:i:s');
        $progress->updated_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('alt42i_user_pattern_progress', $progress);
    }
    
    // 응답
    echo json_encode([
        'success' => true,
        'log_id' => $log_id,
        'mastery_level' => $progress->mastery_level,
        'practice_count' => $progress->practice_count
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>