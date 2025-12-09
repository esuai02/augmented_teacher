<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

try {
    $interactionId = (int)$input['interactionId'];
    $reason = trim($input['reason']);
    $studentId = (int)$input['studentId'];
    
    if (!$interactionId || !$reason) {
        throw new Exception('필수 파라미터가 없습니다.');
    }
    
    // 기존 interaction 조회
    $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interactionId));
    
    if (!$interaction) {
        throw new Exception('해당 문제를 찾을 수 없습니다.');
    }
    
    // 권한 확인 (학생 본인인지)
    if ($interaction->userid != $USER->id && $interaction->userid != $studentId) {
        throw new Exception('권한이 없습니다.');
    }
    
    // 재요청 정보 업데이트
    $update = new stdClass();
    $update->id = $interactionId;
    $update->modification_prompt = $reason;
    $update->status = 'pending';  // 상태를 다시 pending으로 변경
    $update->timemodified = time();
    
    $result = $DB->update_record('ktm_teaching_interactions', $update);
    
    if ($result) {
        // 성공 로그
        error_log("Re-request submitted for interaction $interactionId by student $studentId");
        
        echo json_encode([
            'success' => true,
            'message' => '재요청이 성공적으로 전송되었습니다.',
            'interactionId' => $interactionId
        ]);
    } else {
        throw new Exception('재요청 업데이트에 실패했습니다.');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>