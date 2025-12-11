<?php
/**
 * DB 쿼리 API
 * FileDB를 사용한 데이터 조회 API
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

// 출력 버퍼링 시작
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
}
global $DB, $USER;
require_login();

// 출력 버퍼 비우기
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 에러 출력 비활성화
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once(__DIR__ . '/../includes/db_manager.php');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input', 400);
    }

    $action = $input['action'] ?? '';
    $dbManager = new DBManager();
    
    switch ($action) {
        case 'get_rule_contents':
            $ruleId = $input['rule_id'] ?? null;
            $type = $input['type'] ?? null;
            $result = $dbManager->getRuleContents($ruleId, $type);
            break;
            
        case 'get_interactions':
            $studentId = $input['student_id'] ?? null;
            $limit = $input['limit'] ?? 50;
            $result = $dbManager->getInteractions($studentId, $limit);
            break;
            
        case 'get_ontology_data':
            $nodeId = $input['node_id'] ?? null;
            $class = $input['class'] ?? null;
            $result = $dbManager->getOntologyData($nodeId, $class);
            break;
            
        case 'get_student_context':
            $studentId = $input['student_id'] ?? null;
            if (!$studentId) {
                throw new Exception('student_id is required', 400);
            }
            $result = $dbManager->getStudentContext($studentId);
            break;
            
        case 'get_generated_rules':
            $ruleId = $input['rule_id'] ?? null;
            $priority = $input['priority'] ?? null;
            $result = $dbManager->getGeneratedRules($ruleId, $priority);
            break;
            
        case 'get_stats':
            $result = $dbManager->getStats();
            break;
            
        case 'query':
            $tableName = $input['table'] ?? null;
            $query = $input['query'] ?? [];
            
            if (!$tableName) {
                throw new Exception('table name is required', 400);
            }
            
            $db = $dbManager->getDB();
            $result = $db->query($tableName, $query);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action, 400);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // 출력 버퍼 비우기
    ob_clean();
    
    error_log("DB Query Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    
    // JSON 헤더 재설정
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
}

// 출력 버퍼 종료
ob_end_flush();

