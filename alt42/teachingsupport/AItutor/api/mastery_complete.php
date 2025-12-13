<?php
/**
 * 집중숙련 완료 기록 API
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../includes/question_persona_generator.php');

$wboardId = $_POST['wboard_id'] ?? $_GET['wboard_id'] ?? null;
$studentId = $_POST['student_id'] ?? $_GET['student_id'] ?? $USER->id;
$recommendationId = $_POST['recommendation_id'] ?? $_GET['recommendation_id'] ?? null;

try {
    if (!$wboardId || !$recommendationId) {
        throw new Exception("[mastery_complete.php] wboard_id와 recommendation_id가 필요합니다.");
    }
    
    $generator = new QuestionPersonaGenerator();
    $result = $generator->markMasteryCompleted($wboardId, $studentId, $recommendationId);
    
    if ($result) {
        // 집중숙련 기록 테이블에도 저장
        $record = new stdClass();
        $record->student_id = $studentId;
        $record->recommendation_id = $recommendationId;
        $record->is_completed = 1;
        $record->completed_at = date('Y-m-d H:i:s');
        $record->created_at = date('Y-m-d H:i:s');
        $record->updated_at = date('Y-m-d H:i:s');
        
        // question_persona_id 조회
        $qp = $DB->get_record_sql(
            "SELECT id FROM {alt42_question_personas} WHERE wboard_id = ? AND student_id = ?",
            [$wboardId, $studentId]
        );
        
        if ($qp) {
            $record->question_persona_id = $qp->id;
            
            // 기존 기록 확인
            $existing = $DB->get_record_sql(
                "SELECT id FROM {alt42_mastery_records} 
                 WHERE question_persona_id = ? AND recommendation_id = ?",
                [$qp->id, $recommendationId]
            );
            
            if ($existing) {
                $record->id = $existing->id;
                $record->repetition_completed = ($existing->repetition_completed ?? 0) + 1;
                $DB->update_record('alt42_mastery_records', $record);
            } else {
                $record->concept = '';  // 추후 업데이트
                $record->repetition_target = 3;
                $record->repetition_completed = 1;
                $DB->insert_record('alt42_mastery_records', $record);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => '집중숙련 완료 기록됨'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception("[mastery_complete.php] 완료 기록 실패");
    }
    
} catch (Exception $e) {
    error_log("[mastery_complete.php] 오류: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

