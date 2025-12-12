<?php
/**
 * Agent07 Persona System - Chat API Endpoint
 *
 * 페르소나 기반 채팅 API
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Request (POST JSON):
 * {
 *   "message": "사용자 메시지",
 *   "context": {
 *     "current_activity": "learning",
 *     "pomodoro_active": true,
 *     ...
 *   }
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "response": "AI 응답",
 *     "persona_id": "S1_P1",
 *     "situation_id": "S1",
 *     "confidence": 0.85
 *   }
 * }
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/api/chat.php
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 인증 확인
require_login();

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 에러 핸들링
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * JSON 응답 반환
 *
 * @param bool $success 성공 여부
 * @param mixed $data 응답 데이터
 * @param string $error 에러 메시지 (실패시)
 * @param int $statusCode HTTP 상태 코드
 */
function sendResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);

    $response = array(
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    );

    if ($success) {
        $response['data'] = $data;
    } else {
        $response['error'] = $error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // 1. 요청 메서드 확인
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        // GET 요청은 상태 확인용
        if ($method === 'GET') {
            sendResponse(true, array(
                'status' => 'ready',
                'endpoint' => 'agent07_persona_chat',
                'user_id' => $USER->id,
                'version' => '1.0'
            ));
        }
        sendResponse(false, null, 'Method not allowed. Use POST.', 405);
    }

    // 2. 요청 데이터 파싱
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, null, 'Invalid JSON: ' . json_last_error_msg(), 400);
    }

    // 3. 필수 파라미터 검증
    $message = isset($input['message']) ? trim($input['message']) : '';
    $context = isset($input['context']) ? $input['context'] : array();

    // 메시지가 없어도 페르소나 식별은 가능 (인사/시작 시점)
    $inputData = array(
        'message' => $message
    );

    // 추가 컨텍스트 병합
    if (!empty($context)) {
        $inputData = array_merge($inputData, $context);
    }

    // 4. 엔진 파일 로드
    $enginePath = dirname(__FILE__) . '/../engine/';

    require_once $enginePath . 'PersonaRuleEngine.php';
    require_once $enginePath . 'ResponseGenerator.php';

    // 5. 페르소나 식별
    $ruleEngine = new PersonaRuleEngine($DB, $USER->id);
    $identificationResult = $ruleEngine->identifyPersona($inputData);

    if (!$identificationResult['success']) {
        sendResponse(false, null, 'Persona identification failed: ' .
            (isset($identificationResult['error']) ? $identificationResult['error'] : 'Unknown error'),
            500
        );
    }

    // 6. 응답 생성
    $responseGenerator = new ResponseGenerator($DB, $USER->id);
    $responseResult = $responseGenerator->generate($identificationResult, $inputData);

    if (!$responseResult['success']) {
        sendResponse(false, null, 'Response generation failed: ' .
            (isset($responseResult['error']) ? $responseResult['error'] : 'Unknown error'),
            500
        );
    }

    // 7. 성공 응답
    sendResponse(true, array(
        'response' => $responseResult['response_text'],
        'persona_id' => $responseResult['persona_id'],
        'persona_name' => $identificationResult['persona']['name'],
        'situation_id' => $responseResult['situation_id'],
        'situation_name' => $identificationResult['situation']['name'],
        'confidence' => $responseResult['confidence'],
        'tone' => $responseResult['tone'],
        'greeting' => $responseGenerator->getGreeting($responseResult['persona_id']),
        'debug' => array(
            'matched_conditions' => $identificationResult['persona']['matched_conditions'],
            'context_snapshot' => $identificationResult['context_snapshot'],
            'template_used' => $responseResult['template_used']
        )
    ));

} catch (Exception $e) {
    error_log(sprintf(
        "[Agent07 Chat API] Error: %s (File: %s, Line: %d)",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    sendResponse(false, null, sprintf(
        "Server error: %s (File: %s, Line: %d)",
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    ), 500);
}
