<?php
/**
 * 페르소나 극복 상태 조회 API
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $studentId = $_GET['student_id'] ?? $USER->id;
    $contentId = $_GET['content_id'] ?? null;
    $personaId = $_GET['persona_id'] ?? null;
    $limit = intval($_GET['limit'] ?? 10);
    
    // 테이블 존재 확인
    $tableExists = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_aitutor_overcome_history'");
    
    if (!$tableExists) {
        // 테이블이 없으면 빈 결과 반환
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No history found'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 조회 쿼리 구성
    $sql = "SELECT 
                id,
                persona_id,
                persona_name,
                overcome_level as level,
                notes,
                step_name as step,
                created_at as timestamp
            FROM mdl_aitutor_overcome_history
            WHERE student_id = ?";
    
    $params = [$studentId];
    
    if ($contentId) {
        $sql .= " AND content_id = ?";
        $params[] = $contentId;
    }
    
    if ($personaId) {
        $sql .= " AND persona_id = ?";
        $params[] = $personaId;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT " . $limit;
    
    $records = $DB->get_records_sql($sql, $params);
    
    // 결과 포맷팅
    $data = [];
    foreach ($records as $record) {
        $data[] = [
            'id' => intval($record->id),
            'persona_id' => intval($record->persona_id),
            'persona_name' => $record->persona_name,
            'level' => intval($record->level),
            'notes' => $record->notes,
            'step' => $record->step,
            'timestamp' => $record->timestamp
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '[get_overcome.php:85] ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

