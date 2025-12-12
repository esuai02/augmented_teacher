<?php
/**
 * Agent15 Problem Redefinition - Chat API Endpoint
 *
 * 문제 재정의 페르소나 기반 채팅 API
 * 학생이 문제를 새로운 시각에서 바라보도록 유도하는 대화형 인터페이스
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Request (POST JSON):
 * {
 *   "message": "사용자 메시지",
 *   "context": {
 *     "problem_type": "academic|behavioral|emotional",
 *     "current_understanding": "현재 이해 수준",
 *     "attempts_made": [],
 *     ...
 *   }
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "response": "AI 응답",
 *     "persona_id": "P1_REFRAME",
 *     "situation_id": "S1",
 *     "reframing_strategy": "strategy_name",
 *     "confidence": 0.85
 *   }
 * }
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/api/chat.php
 */

// ==========================================
// 1. Moodle 설정 및 인증
// ==========================================
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 인증 확인
require_login();

// ==========================================
// 2. 헤더 및 에러 설정
// ==========================================
header('Content-Type: application/json; charset=utf-8');
header('X-Agent-Version: agent15-v1.0');
header('X-Powered-By: Problem-Redefinition-Persona-System');

// 에러 핸들링
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 에러 로그 경로 설정
$logDir = dirname(__FILE__) . '/../logs/';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . 'chat_errors.log');

// ==========================================
// 3. 헬퍼 함수들
// ==========================================

/**
 * JSON 응답 반환
 *
 * @param bool $success 성공 여부
 * @param mixed $data 응답 데이터
 * @param string $error 에러 메시지 (실패시)
 * @param int $statusCode HTTP 상태 코드
 * @param string $file 파일명 (에러 추적용)
 * @param int $line 라인 번호 (에러 추적용)
 */
function sendResponse($success, $data = null, $error = null, $statusCode = 200, $file = __FILE__, $line = __LINE__) {
    http_response_code($statusCode);

    $response = array(
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'agent' => 'agent15_problem_redefinition'
    );

    if ($success) {
        $response['data'] = $data;
    } else {
        $response['error'] = $error;
        $response['debug'] = array(
            'file' => basename($file),
            'line' => $line
        );
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 로그 기록
 *
 * @param string $level 로그 레벨 (INFO, WARNING, ERROR)
 * @param string $message 로그 메시지
 * @param array $context 추가 컨텍스트
 */
function logMessage($level, $message, $context = array()) {
    $logEntry = sprintf(
        "[%s] [%s] %s | Context: %s",
        date('Y-m-d H:i:s'),
        $level,
        $message,
        json_encode($context, JSON_UNESCAPED_UNICODE)
    );
    error_log($logEntry);
}

// ==========================================
// 4. 메인 처리 로직
// ==========================================

try {
    // 4.1 요청 메서드 확인
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // GET 요청: API 상태 확인
        sendResponse(true, array(
            'status' => 'ready',
            'endpoint' => 'agent15_problem_redefinition_chat',
            'user_id' => $USER->id,
            'version' => '1.0',
            'capabilities' => array(
                'reframing_strategies' => array(
                    'perspective_shift',
                    'strength_based',
                    'growth_mindset',
                    'solution_focused',
                    'systemic_view'
                ),
                'supported_problem_types' => array(
                    'academic',
                    'behavioral',
                    'emotional',
                    'social',
                    'motivational'
                )
            ),
            'description' => '문제 재정의 페르소나 시스템 - 학생이 문제를 새로운 시각으로 바라보도록 유도'
        ));
    }

    if ($method !== 'POST') {
        sendResponse(false, null, 'Method not allowed. Use POST for chat, GET for status.', 405, __FILE__, __LINE__);
    }

    // 4.2 요청 데이터 파싱
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, null, 'Invalid JSON: ' . json_last_error_msg(), 400, __FILE__, __LINE__);
    }

    logMessage('INFO', 'Chat request received', array(
        'user_id' => $USER->id,
        'has_message' => isset($input['message']),
        'has_context' => isset($input['context'])
    ));

    // 4.3 입력 데이터 구성
    $message = isset($input['message']) ? trim($input['message']) : '';
    $context = isset($input['context']) ? $input['context'] : array();

    $inputData = array(
        'message' => $message,
        'user_id' => $USER->id
    );

    // 추가 컨텍스트 병합
    if (!empty($context)) {
        $inputData = array_merge($inputData, $context);
    }

    // 4.4 문제 재정의 특화 컨텍스트 추출
    $problemContext = array(
        'problem_type' => isset($context['problem_type']) ? $context['problem_type'] : 'academic',
        'current_understanding' => isset($context['current_understanding']) ? $context['current_understanding'] : null,
        'attempts_made' => isset($context['attempts_made']) ? $context['attempts_made'] : array(),
        'emotional_state' => isset($context['emotional_state']) ? $context['emotional_state'] : null,
        'stuck_duration' => isset($context['stuck_duration']) ? $context['stuck_duration'] : null
    );

    $inputData['problem_context'] = $problemContext;

    // 4.5 엔진 파일 로드
    $enginePath = dirname(__FILE__) . '/../engine/';

    // 필수 엔진 파일 확인 및 로드
    $requiredEngines = array(
        'PersonaRuleEngine.php',
        'ResponseGenerator.php'
    );

    foreach ($requiredEngines as $engineFile) {
        $fullPath = $enginePath . $engineFile;
        if (!file_exists($fullPath)) {
            sendResponse(false, null, "Required engine file not found: {$engineFile}", 500, __FILE__, __LINE__);
        }
        require_once $fullPath;
    }

    // 4.6 페르소나 식별
    $ruleEngine = new PersonaRuleEngine($DB, $USER->id);
    $identificationResult = $ruleEngine->identifyPersona($inputData);

    if (!$identificationResult['success']) {
        logMessage('WARNING', 'Persona identification failed', array(
            'error' => isset($identificationResult['error']) ? $identificationResult['error'] : 'Unknown'
        ));

        sendResponse(false, null, 'Persona identification failed: ' .
            (isset($identificationResult['error']) ? $identificationResult['error'] : 'Unknown error'),
            500, __FILE__, __LINE__
        );
    }

    logMessage('INFO', 'Persona identified', array(
        'persona_id' => $identificationResult['persona']['id'],
        'situation_id' => $identificationResult['situation']['id']
    ));

    // 4.7 응답 생성
    $responseGenerator = new ResponseGenerator($DB, $USER->id);
    $responseResult = $responseGenerator->generate($identificationResult, $inputData);

    if (!$responseResult['success']) {
        logMessage('WARNING', 'Response generation failed', array(
            'error' => isset($responseResult['error']) ? $responseResult['error'] : 'Unknown'
        ));

        sendResponse(false, null, 'Response generation failed: ' .
            (isset($responseResult['error']) ? $responseResult['error'] : 'Unknown error'),
            500, __FILE__, __LINE__
        );
    }

    // 4.8 문제 재정의 특화 응답 데이터 구성
    $reframingData = array(
        'strategy_used' => isset($responseResult['reframing_strategy'])
            ? $responseResult['reframing_strategy']
            : 'perspective_shift',
        'original_frame' => isset($problemContext['current_understanding'])
            ? $problemContext['current_understanding']
            : null,
        'suggested_reframe' => isset($responseResult['suggested_reframe'])
            ? $responseResult['suggested_reframe']
            : null,
        'next_steps' => isset($responseResult['next_steps'])
            ? $responseResult['next_steps']
            : array()
    );

    // 4.9 성공 응답 반환
    sendResponse(true, array(
        // 기본 응답 정보
        'response' => $responseResult['response_text'],
        'persona_id' => $responseResult['persona_id'],
        'persona_name' => $identificationResult['persona']['name'],
        'situation_id' => $responseResult['situation_id'],
        'situation_name' => $identificationResult['situation']['name'],
        'confidence' => $responseResult['confidence'],
        'tone' => isset($responseResult['tone']) ? $responseResult['tone'] : 'supportive',

        // 문제 재정의 특화 정보
        'reframing' => $reframingData,

        // 인사/안내 메시지
        'greeting' => $responseGenerator->getGreeting($responseResult['persona_id']),

        // 디버그 정보 (개발용)
        'debug' => array(
            'matched_conditions' => isset($identificationResult['persona']['matched_conditions'])
                ? $identificationResult['persona']['matched_conditions']
                : array(),
            'context_snapshot' => isset($identificationResult['context_snapshot'])
                ? $identificationResult['context_snapshot']
                : array(),
            'template_used' => isset($responseResult['template_used'])
                ? $responseResult['template_used']
                : 'default',
            'problem_context' => $problemContext
        )
    ));

} catch (Exception $e) {
    // 예외 로깅
    logMessage('ERROR', 'Unhandled exception in chat API', array(
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ));

    sendResponse(false, null, sprintf(
        "Server error: %s",
        $e->getMessage()
    ), 500, $e->getFile(), $e->getLine());
}

/**
 * ==========================================
 * Database Tables Used (참조용)
 * ==========================================
 *
 * 이 API는 다음 테이블들을 PersonaRuleEngine/ResponseGenerator를 통해 간접 참조:
 *
 * 1. mdl_alt42_persona_logs
 *    - id: INT (PK)
 *    - userid: INT (FK to mdl_user)
 *    - nagent: INT (에이전트 번호, 15)
 *    - persona_id: VARCHAR(50)
 *    - situation_id: VARCHAR(50)
 *    - input_message: TEXT
 *    - response_text: TEXT
 *    - confidence: DECIMAL(3,2)
 *    - context_data: JSON
 *    - timecreated: INT
 *
 * 2. mdl_alt42_user_context
 *    - userid: INT
 *    - context_key: VARCHAR(100)
 *    - context_value: TEXT
 *    - timemodified: INT
 *
 * ==========================================
 * API Endpoints Summary
 * ==========================================
 *
 * GET  /chat.php
 *   - Returns API status and capabilities
 *   - No authentication required (basic info only)
 *
 * POST /chat.php
 *   - Process chat message and return AI response
 *   - Requires: message (optional), context (optional)
 *   - Returns: response, persona info, reframing strategy
 *
 * ==========================================
 */
