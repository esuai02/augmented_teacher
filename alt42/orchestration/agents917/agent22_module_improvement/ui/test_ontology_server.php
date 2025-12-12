<?php
/**
 * 서버에서 직접 실행하는 온톨로지 테스트
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_server.php
 * 
 * 서버에서 직접 실행: php test_ontology_server.php
 * 또는 브라우저에서: test_ontology_server.php?request=첫 수업을 어떻게 시작해야 할지 알려주세요
 */

// CLI에서 실행되는 경우와 웹에서 실행되는 경우 구분
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    ob_start();
}

// config.php 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 테스트용 사용자 ID 설정
if ($isCli) {
    $testUserId = isset($argv[1]) ? intval($argv[1]) : 810;
    $testRequest = isset($argv[2]) ? $argv[2] : '첫 수업을 어떻게 시작해야 할지 알려주세요';
} else {
    $testUserId = isset($_GET['userid']) ? intval($_GET['userid']) : 810;
    $testRequest = isset($_GET['request']) ? $_GET['request'] : '첫 수업을 어떻게 시작해야 할지 알려주세요';
}

// USER 객체 설정 (CLI에서는 수동 설정)
if ($isCli || !isset($USER) || !$USER->id) {
    $USER = new stdClass();
    $USER->id = $testUserId;
    $USER->username = 'test_user_' . $testUserId;
}

try {
    // Service 파일 로드
    require_once(__DIR__ . '/agent_garden.service.php');
    
    $service = new AgentGardenService();
    
    error_log("[Test] Testing agent: agent01, request: {$testRequest}, user_id: {$testUserId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    
    // 에이전트 실행
    $result = $service->executeAgent('agent01', $testRequest, $testUserId);
    
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
    
    $output = [
        'success' => true,
        'test_info' => [
            'agent_id' => 'agent01',
            'request' => $testRequest,
            'user_id' => $testUserId
        ],
        'result' => $result,
        'ontology_check' => [
            'has_ontology_results' => $hasOntologyResults,
            'ontology_results' => $ontologyResults,
            'ontology_results_count' => $hasOntologyResults ? count($ontologyResults) : 0
        ]
    ];
    
    if ($isCli) {
        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        ob_end_clean();
        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    
    if ($isCli) {
        echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

