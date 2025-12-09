<?php
/**
 * 온톨로지 API POST 테스트 파일
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_post.php
 * 
 * POST 방식으로 온톨로지 동작을 테스트하기 위한 파일
 */

// ⚡ CRITICAL: Set JSON header FIRST before ANY output
header('Content-Type: application/json; charset=utf-8');

// Start output buffering to catch any errors
ob_start();

try {
    // 세션 시작 (config.php 로드 전)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 테스트용 사용자 ID 설정 (config.php 로드 전)
    $testUserId = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 810);
    
    // 세션에 사용자 정보 설정 (require_login 우회 시도)
    $_SESSION['USER'] = (object)[
        'id' => $testUserId,
        'username' => 'test_user_' . $testUserId
    ];
    
    // config.php 로드 (require_login 제외)
    // NO_MOODLE_COOKIES 설정 시도 (작동하지 않을 수 있음)
    if (!defined('NO_MOODLE_COOKIES')) {
        define('NO_MOODLE_COOKIES', true);
    }
    
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;

    // USER 객체 수동 설정 (config.php 로드 후)
    if (!isset($USER) || !$USER->id) {
        $USER = new stdClass();
        $USER->id = $testUserId;
        $USER->username = 'test_user_' . $testUserId;
    } else {
        // USER 객체가 있지만 다른 사용자인 경우 덮어쓰기
        $USER->id = $testUserId;
        $USER->username = 'test_user_' . $testUserId;
    }

    error_log("[Test] Starting ontology API POST test with user_id: {$testUserId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");

    // Clear any output that might have been generated
    ob_clean();

    // Service 파일 로드
    require_once(__DIR__ . '/agent_garden.service.php');

    $service = new AgentGardenService();
    
    // 테스트할 에이전트와 요청
    $testAgentId = isset($_POST['agent_id']) ? $_POST['agent_id'] : 'agent01';
    $testRequest = isset($_POST['request']) ? $_POST['request'] : '첫 수업을 어떻게 시작해야 할지 알려주세요';
    
    error_log("[Test] Testing agent: {$testAgentId}, request: {$testRequest} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    
    // 에이전트 실행
    $result = $service->executeAgent($testAgentId, $testRequest, $testUserId);
    
    // 온톨로지 결과 확인
    $hasOntologyResults = false;
    $ontologyResults = null;
    
    if (isset($result['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['ontology_results'];
    } elseif (isset($result['response']['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['response']['ontology_results'];
    }
    
    // Clear output buffer before sending JSON
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'test_info' => [
            'agent_id' => $testAgentId,
            'request' => $testRequest,
            'user_id' => $testUserId
        ],
        'result' => $result,
        'ontology_check' => [
            'has_ontology_results' => $hasOntologyResults,
            'ontology_results' => $ontologyResults,
            'ontology_results_count' => $hasOntologyResults ? count($ontologyResults) : 0
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Clear output buffer before sending error
    ob_end_clean();
    
    error_log("[Test] Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    // Catch any other errors (including fatal errors)
    ob_end_clean();
    
    error_log("[Test] Fatal Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

