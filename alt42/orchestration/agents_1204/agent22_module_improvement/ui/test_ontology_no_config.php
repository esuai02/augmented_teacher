<?php
/**
 * 온톨로지 직접 테스트 파일 (config.php 없이)
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/test_ontology_no_config.php
 * 
 * config.php를 로드하지 않고 직접 테스트 (서버에서 직접 실행)
 */

// ⚡ CRITICAL: Set JSON header FIRST before ANY output
header('Content-Type: application/json; charset=utf-8');

// Start output buffering
ob_start();

try {
    // config.php를 로드하지 않고 직접 필요한 부분만 처리
    // 대신 서버에서 직접 실행되는 경우를 가정
    
    // 테스트용 사용자 ID 설정
    $testUserId = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 810);
    
    // 테스트할 에이전트와 요청
    $testAgentId = isset($_POST['agent_id']) ? $_POST['agent_id'] : 'agent01';
    $testRequest = isset($_POST['request']) ? $_POST['request'] : '첫 수업을 어떻게 시작해야 할지 알려주세요';
    
    // 실제 서버에서 실행되는 경우를 위해 curl을 사용하여 로그인된 세션으로 요청
    // 또는 직접 agent_garden.controller.php를 호출
    
    // 대안: agent_garden.controller.php를 직접 호출하되, 세션 쿠키를 포함
    $ch = curl_init();
    $url = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.controller.php?action=execute';
    
    $postData = json_encode([
        'agent_id' => $testAgentId,
        'request' => $testRequest,
        'student_id' => $testUserId
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8'
        ],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => '/tmp/test_cookies.txt',
        CURLOPT_COOKIEFILE => '/tmp/test_cookies.txt'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 303 || $httpCode === 302) {
        throw new Exception("Login required. HTTP Code: {$httpCode}");
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON response: " . substr($response, 0, 200));
    }
    
    // 온톨로지 결과 확인
    $hasOntologyResults = false;
    $ontologyResults = null;
    $result = $data['data'] ?? [];
    
    if (isset($result['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['ontology_results'];
    } elseif (isset($result['response']['ontology_results'])) {
        $hasOntologyResults = true;
        $ontologyResults = $result['response']['ontology_results'];
    }
    
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
    ob_end_clean();
    
    error_log("[Test] Error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

