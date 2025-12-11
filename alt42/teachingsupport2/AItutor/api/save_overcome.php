<?php
/**
 * 페르소나 극복 상태 저장 API
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // POST 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('[save_overcome.php:30] Invalid JSON input');
    }
    
    $studentId = $input['student_id'] ?? $USER->id;
    $contentId = $input['content_id'] ?? null;
    $analysisId = $input['analysis_id'] ?? null;
    $personaId = $input['persona_id'] ?? null;
    $personaName = $input['persona_name'] ?? '';
    $level = intval($input['level'] ?? 0);
    $notes = $input['notes'] ?? '';
    $step = $input['step'] ?? '';
    $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
    
    if (!$personaId || !$level) {
        throw new Exception('[save_overcome.php:45] Missing required fields: persona_id or level');
    }
    
    // 테이블 존재 확인 및 생성
    $tableExists = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_aitutor_overcome_history'");
    
    if (!$tableExists) {
        // 테이블 생성
        $createSql = "CREATE TABLE IF NOT EXISTS mdl_aitutor_overcome_history (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            student_id BIGINT NOT NULL,
            content_id VARCHAR(100),
            analysis_id VARCHAR(100),
            persona_id INT NOT NULL,
            persona_name VARCHAR(255),
            overcome_level INT NOT NULL DEFAULT 0,
            notes TEXT,
            step_name VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_student (student_id),
            INDEX idx_content (content_id),
            INDEX idx_persona (persona_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $DB->execute($createSql);
    }
    
    // 레코드 삽입
    $record = new stdClass();
    $record->student_id = $studentId;
    $record->content_id = $contentId;
    $record->analysis_id = $analysisId;
    $record->persona_id = $personaId;
    $record->persona_name = $personaName;
    $record->overcome_level = $level;
    $record->notes = $notes;
    $record->step_name = $step;
    $record->created_at = date('Y-m-d H:i:s', strtotime($timestamp));
    
    $insertId = $DB->insert_record('aitutor_overcome_history', $record);
    
    echo json_encode([
        'success' => true,
        'message' => '극복 상태가 저장되었습니다',
        'id' => $insertId,
        'data' => [
            'persona_id' => $personaId,
            'level' => $level,
            'timestamp' => $record->created_at
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

