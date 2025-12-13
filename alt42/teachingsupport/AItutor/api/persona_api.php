<?php
/**
 * 페르소나 API
 * 학습자 페르소나 관련 API
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
    require_once(__DIR__ . '/../includes/persona_manager.php');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input', 400);
    }

    $action = $input['action'] ?? '';
    $personaManager = new PersonaManager();
    
    switch ($action) {
        case 'get_all_personas':
            $result = $personaManager->getAllPersonas();
            break;
            
        case 'get_persona':
            $personaId = $input['persona_id'] ?? null;
            if (!$personaId) {
                throw new Exception('persona_id is required', 400);
            }
            $result = $personaManager->getPersona($personaId);
            break;
            
        case 'get_persona_by_name':
            $name = $input['name'] ?? null;
            if (!$name) {
                throw new Exception('name is required', 400);
            }
            $result = $personaManager->getPersonaByName($name);
            break;
            
        case 'get_personas_by_category':
            $category = $input['category'] ?? null;
            if (!$category) {
                throw new Exception('category is required', 400);
            }
            $result = $personaManager->getPersonasByCategory($category);
            break;
            
        case 'match_student_persona':
            $studentId = $input['student_id'] ?? null;
            $interactionData = $input['interaction_data'] ?? [];
            
            if (!$studentId) {
                throw new Exception('student_id is required', 400);
            }
            
            $matches = $personaManager->matchStudentPersona($studentId, $interactionData);
            
            // 가장 높은 점수 페르소나 저장
            if (!empty($matches) && $matches[0]['score'] > 5) {
                $topMatch = $matches[0];
                $personaManager->saveStudentPersona(
                    $studentId,
                    $topMatch['persona']['persona_id'],
                    $topMatch['match_percentage'] / 100
                );
            }
            
            $result = $matches;
            break;
            
        case 'get_student_personas':
            $studentId = $input['student_id'] ?? null;
            if (!$studentId) {
                throw new Exception('student_id is required', 400);
            }
            $result = $personaManager->getStudentPersonas($studentId);
            break;
            
        case 'get_intervention_strategy':
            $personaId = $input['persona_id'] ?? null;
            if (!$personaId) {
                throw new Exception('persona_id is required', 400);
            }
            $result = $personaManager->getInterventionStrategy($personaId);
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
    
    error_log("Persona API Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
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

