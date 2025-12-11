<?php
/**
 * 개입 활동 API
 * AlphaTutor42 개입 시스템 API
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
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once(__DIR__ . '/../includes/intervention_manager.php');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input', 400);
    }

    $action = $input['action'] ?? '';
    $interventionManager = new InterventionManager();
    
    switch ($action) {
        case 'get_all_interventions':
            $result = $interventionManager->getAllInterventions();
            break;
            
        case 'get_interventions_by_category':
            $category = $input['category'] ?? null;
            if (!$category) {
                throw new Exception('category is required', 400);
            }
            $result = $interventionManager->getInterventionsByCategory($category);
            break;
            
        case 'get_interventions_by_persona':
            $personaId = $input['persona_id'] ?? null;
            if (!$personaId) {
                throw new Exception('persona_id is required', 400);
            }
            $result = $interventionManager->getInterventionsByPersona($personaId);
            break;
            
        case 'select_intervention':
            $signals = $input['signals'] ?? [];
            $personaId = $input['persona_id'] ?? null;
            $result = $interventionManager->selectInterventionBySignals($signals, $personaId);
            break;
            
        case 'get_intervention':
            $activityId = $input['activity_id'] ?? null;
            if (!$activityId) {
                throw new Exception('activity_id is required', 400);
            }
            $result = $interventionManager->getIntervention($activityId);
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
    
    error_log("Intervention API Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
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
if (ob_get_level() > 0) {
    ob_end_flush();
}

