<?php
/**
 * Agent19 Persona System Chat API
 *
 * 학습자 행동 데이터를 분석하여 개인화된 응답 생성
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  API
 * @version     1.0.0
 * @created     2025-12-02
 *
 * 엔드포인트: POST /api/agent19/chat
 * 요청 형식: JSON
 * 응답 형식: JSON
 */

// Moodle 환경 초기화
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// API 요청에 대한 인증 확인 (리다이렉트 없이 JSON 응답)
// AJAX/API 요청 감지
$isAjaxRequest = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (
    isset($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) || (
    isset($_SERVER['HTTP_ACCEPT']) &&
    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
);

// 로그인 상태 확인
if (!isloggedin() || isguestuser()) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'AUTH_REQUIRED',
            'message' => 'Authentication required. Please login first.',
            'file' => __FILE__,
            'line' => __LINE__
        ],
        'timestamp' => time(),
        'agent' => 'agent19_interaction_content'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 세션 유지를 위한 require_login (이미 로그인된 상태이므로 리다이렉트 없음)
require_login();

// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('X-Agent: Agent19-PersonaSystem');

// 엔진 컴포넌트 로드
require_once(__DIR__ . '/../engine/PersonaEngine.php');
require_once(__DIR__ . '/../engine/ContextAnalyzer.php');
require_once(__DIR__ . '/../engine/ResponseGenerator.php');
require_once(__DIR__ . '/../templates/TemplateManager.php');

/**
 * API 응답 생성
 *
 * @param bool $success 성공 여부
 * @param mixed $data 응답 데이터
 * @param string $error 에러 메시지
 * @param int $code HTTP 상태 코드
 */
function sendResponse($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => time(),
        'agent' => 'agent19_interaction_content'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 요청 검증
 *
 * @param array $data 요청 데이터
 * @return array|null 검증 에러 또는 null
 */
function validateRequest($data) {
    $required = ['user_id', 'content_id'];
    $missing = [];

    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        return ['code' => 'MISSING_FIELDS', 'fields' => $missing];
    }

    // user_id 검증 (현재 로그인한 사용자와 일치 확인)
    global $USER;
    if ((int)$data['user_id'] !== (int)$USER->id) {
        return ['code' => 'USER_MISMATCH', 'message' => 'User ID does not match logged in user'];
    }

    return null;
}

/**
 * Rate limiting 체크
 *
 * @param int $userId 사용자 ID
 * @return bool 제한 초과 여부
 */
function checkRateLimit($userId) {
    global $DB;

    $config = include(__DIR__ . '/../engine/config/config.php');
    $rateLimit = $config['api']['rate_limit'] ?? 100;

    // 최근 1분간 요청 수 확인
    $oneMinuteAgo = time() - 60;
    $requestCount = $DB->count_records_select(
        'mdl_agent19_response_log',
        'user_id = :userid AND created_at > :time',
        ['userid' => $userId, 'time' => $oneMinuteAgo]
    );

    return $requestCount >= $rateLimit;
}

// ============================================================
// 메인 처리 로직
// ============================================================

try {
    // HTTP 메서드 확인
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, null, [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Only POST method is allowed'
        ], 405);
    }

    // 요청 데이터 파싱
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, null, [
            'code' => 'INVALID_JSON',
            'message' => 'Invalid JSON format',
            'detail' => json_last_error_msg()
        ], 400);
    }

    // 요청 검증
    $validationError = validateRequest($input);
    if ($validationError) {
        sendResponse(false, null, $validationError, 400);
    }

    // Rate limiting 체크
    if (checkRateLimit($USER->id)) {
        sendResponse(false, null, [
            'code' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Too many requests. Please try again later.'
        ], 429);
    }

    // 행동 데이터 구성
    $behaviorData = [
        'user_id' => (int)$input['user_id'],
        'content_id' => (int)$input['content_id'],
        'session_duration' => $input['session_duration'] ?? 0,
        'last_activity_time' => $input['last_activity_time'] ?? time(),
        'current_time' => time(),

        // 정확도 및 응답 데이터
        'accuracy_rate' => $input['accuracy_rate'] ?? null,
        'avg_response_time' => $input['avg_response_time'] ?? null,
        'response_time' => $input['response_time'] ?? null,

        // 오류 데이터
        'error_count' => $input['error_count'] ?? 0,
        'consecutive_errors' => $input['consecutive_errors'] ?? 0,
        'same_type_errors' => $input['same_type_errors'] ?? 0,
        'error_rate' => $input['error_rate'] ?? 0,

        // 참여도 데이터
        'engagement_change' => $input['engagement_change'] ?? 0,
        'skip_rate' => $input['skip_rate'] ?? 0,
        'correct_streak' => $input['correct_streak'] ?? 0,
        'pause_duration' => $input['pause_duration'] ?? 0,

        // 활동 데이터
        'activity_distribution' => $input['activity_distribution'] ?? [],
        'difficulty_distribution' => $input['difficulty_distribution'] ?? [],
        'exploration_score' => $input['exploration_score'] ?? 0.5,

        // 추가 컨텍스트
        'activity_type' => $input['activity_type'] ?? 'general',
        'device_type' => $input['device_type'] ?? 'desktop',
        'environment' => $input['environment'] ?? 'web'
    ];

    // ========================================
    // 1. 페르소나 감지
    // ========================================
    $personaEngine = new Agent19_PersonaEngine();
    $personaResult = $personaEngine->detectPersona($behaviorData);

    // AI 강화 필요 여부 확인
    $needsAIEnhancement = $personaResult['needs_ai_enhancement'] ?? false;

    // ========================================
    // 2. 컨텍스트 분석
    // ========================================
    $contextAnalyzer = new Agent19_ContextAnalyzer();
    $contextResult = $contextAnalyzer->analyzeContext($behaviorData);

    // ========================================
    // 3. 응답 생성
    // ========================================
    $responseGenerator = new Agent19_ResponseGenerator();
    $response = $responseGenerator->generateResponse(
        $personaResult,
        $contextResult,
        $behaviorData
    );

    // ========================================
    // 4. 템플릿 기반 응답 보강
    // ========================================
    $templateManager = new Agent19_TemplateManager();
    $primarySituation = $contextResult['primary_situation']['code'] ?? 'S7';

    $templateResponse = $templateManager->selectResponse(
        $primarySituation,
        [
            'cognitive' => $personaResult['cognitive']['code'] ?? 'C1',
            'behavioral' => $personaResult['behavioral']['code'] ?? 'B1',
            'emotional' => $personaResult['emotional']['code'] ?? 'E6'
        ],
        array_merge($behaviorData, [
            'error_type' => $contextResult['primary_situation']['sub_context'] ?? null,
            'temporal' => $contextResult['temporal']['code'] ?? 'T2_CTX'
        ])
    );

    // 템플릿 응답으로 보강
    if (!empty($templateResponse['message'])) {
        $response['template_message'] = $templateResponse['message'];
        $response['cta'] = $templateResponse['cta'] ?? $response['cta'];
    }

    // ========================================
    // 5. 응답 로깅 (비동기 권장)
    // ========================================
    try {
        $logRecord = new stdClass();
        $logRecord->user_id = $USER->id;
        $logRecord->content_id = (int)$input['content_id'];
        $logRecord->persona_code = $personaResult['composite'] ?? 'C1-B1-E6';
        $logRecord->context_code = $primarySituation;
        $logRecord->response_data = json_encode($response, JSON_UNESCAPED_UNICODE);
        $logRecord->behavior_data = json_encode($behaviorData, JSON_UNESCAPED_UNICODE);
        $logRecord->confidence = $personaResult['confidence'] ?? 0;
        $logRecord->created_at = time();

        $DB->insert_record('mdl_agent19_response_log', $logRecord);
    } catch (Exception $logEx) {
        // 로깅 실패해도 응답은 반환
        error_log("[Agent19_API:chat] Logging failed: " . $logEx->getMessage());
    }

    // ========================================
    // 6. 최종 응답 구성
    // ========================================
    $finalResponse = [
        // 핵심 응답
        'message' => $response['message'] ?? $templateResponse['message'],
        'cta' => $response['cta'] ?? $templateResponse['cta'],
        'tone' => $response['tone'] ?? 'neutral',

        // 페르소나 정보
        'persona' => [
            'composite' => $personaResult['composite'] ?? 'C1-B1-E6',
            'cognitive' => $personaResult['cognitive'] ?? null,
            'behavioral' => $personaResult['behavioral'] ?? null,
            'emotional' => $personaResult['emotional'] ?? null,
            'confidence' => round($personaResult['confidence'] ?? 0, 2)
        ],

        // 컨텍스트 정보
        'context' => [
            'situation' => $contextResult['primary_situation'] ?? null,
            'interaction' => $contextResult['interaction'] ?? null,
            'environment' => $contextResult['environment'] ?? null,
            'temporal' => $contextResult['temporal'] ?? null
        ],

        // 추가 액션
        'follow_up_actions' => $response['follow_up_actions'] ?? [],
        'suggested_activities' => $response['suggested_activities'] ?? [],

        // 메타데이터
        'meta' => [
            'needs_ai_enhancement' => $needsAIEnhancement,
            'template_used' => $templateResponse['source'] ?? 'default',
            'processing_time_ms' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2)
        ]
    ];

    sendResponse(true, $finalResponse);

} catch (Exception $e) {
    // 예외 처리
    error_log("[Agent19_API:chat] Exception at " . $e->getFile() . ":" . $e->getLine() . " - " . $e->getMessage());

    sendResponse(false, null, [
        'code' => 'INTERNAL_ERROR',
        'message' => 'An internal error occurred',
        'debug' => defined('DEBUG') && DEBUG ? $e->getMessage() : null
    ], 500);
}

/*
 * API 사용 예시:
 *
 * POST /api/agent19/chat
 * Content-Type: application/json
 *
 * {
 *   "user_id": 123,
 *   "content_id": 456,
 *   "session_duration": 1800,
 *   "accuracy_rate": 0.75,
 *   "error_count": 2,
 *   "consecutive_errors": 2,
 *   "engagement_change": -0.1,
 *   "activity_type": "quiz"
 * }
 *
 * 응답 예시:
 * {
 *   "success": true,
 *   "data": {
 *     "message": "연속으로 어려움을 겪고 계시네요. 다른 방식으로 설명해드릴까요?",
 *     "cta": "다른 설명 보기",
 *     "tone": "supportive",
 *     "persona": {
 *       "composite": "C2-B1-E2",
 *       "confidence": 0.72
 *     },
 *     "context": {
 *       "situation": {"code": "S4", "type": "consecutive_error"}
 *     }
 *   },
 *   "timestamp": 1733097600,
 *   "agent": "agent19_interaction_content"
 * }
 *
 * 관련 데이터베이스 테이블:
 * - mdl_agent19_response_log (id, user_id, content_id, persona_code, context_code, response_data, behavior_data, confidence, created_at)
 */
