<?php
/**
 * 패턴 메모 저장 API
 */

require_once(__DIR__ . '/../../../../../../../config.php');

// 로그인 확인
require_login();

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// JSON 입력 받기
$input = json_decode(file_get_contents('php://input'), true);

$pattern_id = $input['pattern_id'] ?? 0;
$note = $input['note'] ?? '';
$user_id = $USER->id;

try {
    if (!$pattern_id) {
        throw new Exception('패턴 ID가 필요합니다.');
    }
    
    // 패턴 존재 확인
    if (!$DB->record_exists('mdl_alt42i_math_patterns', ['id' => $pattern_id])) {
        throw new Exception('존재하지 않는 패턴입니다.');
    }
    
    // 기존 진행 상황 확인
    $progress = $DB->get_record('mdl_alt42i_user_pattern_progress', [
        'user_id' => $user_id,
        'pattern_id' => $pattern_id
    ]);
    
    if ($progress) {
        // 업데이트
        $progress->notes = $note;
        $progress->updated_at = date('Y-m-d H:i:s');
        
        $DB->update_record('mdl_alt42i_user_pattern_progress', $progress);
    } else {
        // 새로 생성
        $progress = new stdClass();
        $progress->user_id = $user_id;
        $progress->pattern_id = $pattern_id;
        $progress->is_collected = 0;
        $progress->mastery_level = 0;
        $progress->practice_count = 0;
        $progress->notes = $note;
        $progress->created_at = date('Y-m-d H:i:s');
        $progress->updated_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('mdl_alt42i_user_pattern_progress', $progress);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '메모가 저장되었습니다.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}