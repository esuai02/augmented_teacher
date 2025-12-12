<?php
/**
 * 온톨로지 API 테스트 파일 (로그인 없이 테스트)
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_api.php
 * 
 * 로그인 없이 온톨로지 동작을 테스트하기 위한 파일
 */

// config.php 로드 (require_login 제외)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 테스트용 사용자 ID 설정 (실제 DB에 있는 사용자 ID 사용)
// 또는 환경변수나 파라미터로 받을 수 있음
$testUserId = isset($_GET['userid']) ? intval($_GET['userid']) : 810; // 기본값: 810

// USER 객체 수동 설정 (require_login 없이)
if (!isset($USER) || !$USER->id) {
    $USER = new stdClass();
    $USER->id = $testUserId;
    $USER->username = 'test_user_' . $testUserId;
}

error_log("[Test] Starting ontology API test with user_id: {$testUserId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");

// Service 파일 로드
require_once(__DIR__ . '/agent_garden.service.php');

// 테스트 실행
header('Content-Type: application/json; charset=utf-8');

try {
    $service = new AgentGardenService();
    
    // 테스트할 에이전트와 요청 (POST 우선, 없으면 GET)
    $testAgentId = isset($_POST['agent_id']) ? $_POST['agent_id'] : (isset($_GET['agent_id']) ? $_GET['agent_id'] : 'agent01');
    $testRequest = isset($_POST['request']) ? $_POST['request'] : (isset($_GET['request']) ? $_GET['request'] : '첫 수업을 어떻게 시작해야 할지 알려주세요');
    
    error_log("[Test] Testing agent: {$testAgentId}, request: {$testRequest} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    
    // 에이전트 실행
    $result = $service->executeAgent($testAgentId, $testRequest, $testUserId);
    
    echo json_encode([
        'success' => true,
        'test_info' => [
            'agent_id' => $testAgentId,
            'request' => $testRequest,
            'user_id' => $testUserId
        ],
        'result' => $result,
        'ontology_check' => [
            'has_ontology_results' => isset($result['response']['ontology_results']) || isset($result['ontology_results']),
            'ontology_results' => $result['ontology_results'] ?? $result['response']['ontology_results'] ?? null
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("[Test] Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

