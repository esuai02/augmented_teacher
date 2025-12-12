<?php
/**
 * 온톨로지 직접 테스트 파일 (로그인 불필요)
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_direct.php
 * 
 * 로그인 없이 직접 온톨로지 동작을 테스트하기 위한 파일
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
    
    // config.php 로드 전에 NO_MOODLE_COOKIES 설정 시도
    if (!defined('NO_MOODLE_COOKIES')) {
        define('NO_MOODLE_COOKIES', true);
    }
    
    // config.php 로드 (require_login 제외)
    $configPath = "/home/moodle/public_html/moodle/config.php";
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: {$configPath}");
    }
    
    // config.php를 로드하되, 출력을 버퍼링하여 리다이렉트 방지
    // require_login이 실행되면 리다이렉트가 발생하지만, 
    // 출력 버퍼링으로 이를 감지할 수 있음
    $oldOutput = ob_get_contents();
    ob_clean();
    
    // config.php 로드 시도
    include_once($configPath);
    
    // 리다이렉트가 발생했는지 확인 (Location 헤더 확인 불가, 하지만 출력 확인 가능)
    $newOutput = ob_get_contents();
    ob_clean();
    
    // HTML 에러가 출력되었는지 확인
    if (strpos($newOutput, '<html') !== false || strpos($newOutput, 'QuickForm Error') !== false) {
        // 에러가 발생했지만 계속 진행 시도
        error_log("[Test] Warning: HTML output detected after config.php load: " . substr($newOutput, 0, 200));
    }
    
    global $DB, $USER;

    // 테스트용 사용자 ID 설정
    $testUserId = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 810);

    // USER 객체 수동 설정
    if (!isset($USER) || !$USER->id) {
        $USER = new stdClass();
        $USER->id = $testUserId;
        $USER->username = 'test_user_' . $testUserId;
    }
    
    // DB 객체가 없으면 기본 설정
    if (!isset($DB)) {
        throw new Exception("Database connection not available. Please ensure config.php is loaded correctly.");
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

