<?php
/**
 * 음성 재생 기록 API
 * 패턴 음성 가이드 재생을 추적
 */

// Moodle 설정 포함
require_once(__DIR__ . '/../../../../../../../config.php');
global $DB, $USER, $CFG;

// 로그인 확인
require_login();

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$user_id = isset($input['user_id']) ? intval($input['user_id']) : $USER->id;
$pattern_id = isset($input['pattern_id']) ? intval($input['pattern_id']) : 0;

try {
    // 패턴 존재 확인
    if (!$DB->record_exists('alt42i_math_patterns', ['id' => $pattern_id])) {
        throw new Exception('Pattern not found');
    }
    
    // 음성 재생 로그 테이블이 존재하는 경우에만 기록
    if ($DB->get_manager()->table_exists('alt42i_audio_play_logs')) {
        $log = new stdClass();
        $log->user_id = $user_id;
        $log->pattern_id = $pattern_id;
        $log->played_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('alt42i_audio_play_logs', $log);
    }
    
    // 사용자 진행 상황에도 반영 (선택적)
    $progress = $DB->get_record('alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'pattern_id' => $pattern_id
    ]);
    
    if ($progress) {
        // 연습 횟수는 증가시키지 않고 마지막 활동 시간만 업데이트
        $progress->updated_at = date('Y-m-d H:i:s');
        $DB->update_record('alt42i_user_pattern_progress', $progress);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '음성 재생이 기록되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}