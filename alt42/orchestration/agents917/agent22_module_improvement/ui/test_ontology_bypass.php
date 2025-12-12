<?php
/**
 * 온톨로지 테스트 파일 (require_login 우회)
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_bypass.php
 * 
 * require_login을 우회하여 테스트하기 위한 파일
 * 실제 로그인된 브라우저에서 사용
 */

// ⚡ CRITICAL: Set JSON header FIRST before ANY output
header('Content-Type: application/json; charset=utf-8');

// Start output buffering
ob_start();

try {
    // 세션 시작
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // report_generator.php가 직접 실행되지 않도록 가드 설정 (config.php 로드 전)
    if (!defined('ALT42_DISABLE_DIRECT_ACTION')) {
        define('ALT42_DISABLE_DIRECT_ACTION', true);
    }
    
    // config.php 로드 전에 USER 객체를 세션에 설정 시도
    $testUserId = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 810);
    
    // config.php 로드
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    
    // USER 객체가 없거나 로그인되지 않은 경우 수동 설정
    if (!isset($USER) || !$USER->id || $USER->id <= 0) {
        // 실제 DB에서 사용자 정보 가져오기 시도
        if (isset($DB)) {
            try {
                $userRecord = $DB->get_record('user', ['id' => $testUserId], '*', IGNORE_MISSING);
                if ($userRecord) {
                    $USER = $userRecord;
                } else {
                    // 사용자가 없으면 기본 객체 생성
                    $USER = new stdClass();
                    $USER->id = $testUserId;
                    $USER->username = 'test_user_' . $testUserId;
                }
            } catch (Exception $e) {
                error_log("[Test] Could not load user from DB: " . $e->getMessage());
                $USER = new stdClass();
                $USER->id = $testUserId;
                $USER->username = 'test_user_' . $testUserId;
            }
        } else {
            $USER = new stdClass();
            $USER->id = $testUserId;
            $USER->username = 'test_user_' . $testUserId;
        }
    }
    
    // 세션에 사용자 정보 저장 (require_login 우회 시도)
    $_SESSION['USER'] = $USER;
    
    error_log("[Test] Starting ontology API test with user_id: {$testUserId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    
    // Clear any output
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
    
    // 디버그: result 구조 확인
    error_log("[Test] Result keys: " . implode(', ', array_keys($result)) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    
    if (isset($result['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['ontology_results'];
        error_log("[Test] Found ontology_results in result [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    } elseif (isset($result['response']['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['response']['ontology_results'];
        error_log("[Test] Found ontology_results in result[response] [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    } elseif (isset($result['response']) && is_array($result['response'])) {
        error_log("[Test] Response keys: " . implode(', ', array_keys($result['response'])) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    // response 내부에서도 확인
    if (isset($result['response']) && is_array($result['response'])) {
        if (isset($result['response']['ontology_results'])) {
            $hasOntologyResults = true;
            $ontologyResults = $result['response']['ontology_results'];
        }
    }
    
    ob_end_clean();
    
    // 디버그 정보 추출
    $debugInfo = null;
    if (isset($result['_debug'])) {
        $debugInfo = $result['_debug'];
        // result에서 _debug 제거 (실제 응답에는 포함하지 않음)
        unset($result['_debug']);
    }
    
    echo json_encode([
        'success' => true,
        'test_info' => [
            'agent_id' => $testAgentId,
            'request' => $testRequest,
            'user_id' => $testUserId,
            'user_authenticated' => isset($USER->id) && $USER->id > 0
        ],
        'result' => $result,
        'ontology_check' => [
            'has_ontology_results' => $hasOntologyResults,
            'ontology_results' => $ontologyResults,
            'ontology_results_count' => $hasOntologyResults ? count($ontologyResults) : 0
        ],
        'debug' => $debugInfo // decision 구조 디버그 정보
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    ob_end_clean();
    
    error_log("[Test] Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    ob_end_clean();
    
    error_log("[Test] Fatal Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

