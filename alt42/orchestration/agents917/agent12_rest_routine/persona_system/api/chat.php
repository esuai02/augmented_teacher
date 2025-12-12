<?php
/**
 * chat.php
 *
 * Agent12 휴식 루틴 채팅 API
 * 페르소나 기반 대화 처리
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent12RestRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/api/chat.php
 *
 * API Endpoints:
 * - POST /chat.php?action=chat           : 채팅 메시지 처리
 * - POST /chat.php?action=identify       : 페르소나 식별
 * - GET  /chat.php?action=status         : 현재 상태 조회
 * - POST /chat.php?action=start_rest     : 휴식 세션 시작
 * - POST /chat.php?action=end_rest       : 휴식 세션 종료
 * - GET  /chat.php?action=stats          : 휴식 통계 조회
 * - GET  /chat.php?action=analyze_need   : 휴식 필요성 분석
 * - GET  /chat.php?action=health         : 헬스 체크
 */

// Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 리포팅
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS 및 JSON 헤더
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// MOODLE_INTERNAL 정의
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// PersonaEngine 로드
require_once(__DIR__ . '/../Agent12PersonaEngine.php');

/**
 * API 응답 생성
 *
 * @param bool $success 성공 여부
 * @param mixed $data 데이터
 * @param string|null $error 에러 메시지
 * @param int $code HTTP 코드
 * @return void
 */
function sendResponse(bool $success, $data = null, ?string $error = null, int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => time(),
        'file' => __FILE__,
        'agent' => 12
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 요청 데이터 파싱
 *
 * @return array
 */
function getRequestData(): array
{
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    return array_merge($_GET, $_POST);
}

// =========================================================================
// 메인 로직
// =========================================================================

try {
    // 사용자 ID 확인
    $userId = $USER->id;
    if (!$userId) {
        sendResponse(false, null, 'User not authenticated. File: ' . __FILE__ . ' Line: ' . __LINE__, 401);
    }

    // 액션 파싱
    $action = isset($_GET['action']) ? $_GET['action'] : 'chat';
    $data = getRequestData();

    // PersonaEngine 초기화
    $engine = new Agent12PersonaEngine($DB);
    $engine->initialize();

    // 액션별 처리
    switch ($action) {
        case 'chat':
            handleChat($engine, $userId, $data);
            break;

        case 'identify':
            handleIdentify($engine, $userId, $data);
            break;

        case 'status':
            handleStatus($engine, $userId);
            break;

        case 'start_rest':
            handleStartRest($engine, $userId, $data);
            break;

        case 'end_rest':
            handleEndRest($engine, $userId, $data);
            break;

        case 'stats':
            handleGetStats($engine, $userId, $data);
            break;

        case 'analyze_need':
            handleAnalyzeNeed($engine, $userId, $data);
            break;

        case 'fatigue':
            handleGetFatigue($engine, $userId);
            break;

        case 'health':
            handleHealthCheck($engine);
            break;

        default:
            sendResponse(false, null, 'Unknown action: ' . $action . ' File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

} catch (Throwable $e) {
    sendResponse(false, null, $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine(), 500);
}

// =========================================================================
// 액션 핸들러
// =========================================================================

/**
 * 채팅 메시지 처리
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleChat(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    // 메시지 검증
    if (empty($data['message'])) {
        sendResponse(false, null, 'Message is required. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

    $message = trim($data['message']);
    $options = isset($data['options']) ? $data['options'] : [];

    // 페르소나 식별 (자동)
    $contextData = isset($data['context']) ? $data['context'] : [];
    $persona = $engine->identifyPersona($userId, $contextData);

    // 응답 생성
    $response = $engine->generateResponse($userId, $persona['persona_code'], $message, $options);

    sendResponse(true, [
        'response' => $response,
        'persona' => $persona,
        'user_id' => $userId
    ]);
}

/**
 * 페르소나 식별
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleIdentify(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    $contextData = isset($data['context']) ? $data['context'] : [];

    $persona = $engine->identifyPersona($userId, $contextData);

    sendResponse(true, [
        'persona' => $persona,
        'user_id' => $userId
    ]);
}

/**
 * 현재 상태 조회
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleStatus(Agent12PersonaEngine $engine, int $userId): void
{
    // 페르소나 식별
    $persona = $engine->identifyPersona($userId, []);

    // 현재 휴식 상태
    $currentStrategy = $engine->getCurrentStrategy($userId);

    // 피로도 정보
    $fatigueInfo = $engine->getCurrentFatigueInfo($userId);

    sendResponse(true, [
        'persona' => $persona,
        'strategy' => $currentStrategy,
        'fatigue' => $fatigueInfo,
        'user_id' => $userId
    ]);
}

/**
 * 휴식 세션 시작
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleStartRest(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    $restData = [
        'rest_type' => isset($data['rest_type']) ? $data['rest_type'] : 'break',
        'trigger_source' => isset($data['trigger_source']) ? $data['trigger_source'] : 'button',
        'study_duration' => isset($data['study_duration']) ? (int)$data['study_duration'] : 0,
        'fatigue_level' => isset($data['fatigue_level']) ? (float)$data['fatigue_level'] : null,
        'activity_type' => isset($data['activity_type']) ? $data['activity_type'] : null,
        'notes' => isset($data['notes']) ? $data['notes'] : null
    ];

    $result = $engine->startRestSession($userId, $restData);

    if ($result['success']) {
        // 페르소나 재식별
        $persona = $engine->identifyPersona($userId, ['trigger' => 'rest_started']);

        sendResponse(true, [
            'session_id' => $result['session_id'],
            'message' => $result['message'],
            'started_at' => $result['started_at'],
            'persona' => $persona
        ]);
    } else {
        sendResponse(false, null, $result['error'], 400);
    }
}

/**
 * 휴식 세션 종료
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleEndRest(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    $sessionId = isset($data['session_id']) ? (int)$data['session_id'] : null;
    $endData = [
        'fatigue_level' => isset($data['fatigue_level']) ? (float)$data['fatigue_level'] : null,
        'notes' => isset($data['notes']) ? $data['notes'] : null
    ];

    $result = $engine->endRestSession($userId, $sessionId, $endData);

    if ($result['success']) {
        // 페르소나 재식별
        $persona = $engine->identifyPersona($userId, ['trigger' => 'rest_ended']);

        // 휴식 후 피로도 정보
        $fatigueInfo = $engine->getCurrentFatigueInfo($userId);

        sendResponse(true, [
            'session_id' => $result['session_id'],
            'duration_minutes' => $result['duration_minutes'],
            'message' => $result['message'],
            'persona' => $persona,
            'fatigue' => $fatigueInfo
        ]);
    } else {
        sendResponse(false, null, $result['error'], 400);
    }
}

/**
 * 휴식 통계 조회
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleGetStats(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    $days = isset($data['days']) ? (int)$data['days'] : 7;
    $days = max(1, min(90, $days)); // 1-90일 범위

    $stats = $engine->getRestStats($userId, $days);

    sendResponse(true, [
        'stats' => $stats,
        'user_id' => $userId
    ]);
}

/**
 * 휴식 필요성 분석
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleAnalyzeNeed(Agent12PersonaEngine $engine, int $userId, array $data): void
{
    $currentStudyMinutes = isset($data['study_minutes']) ? (int)$data['study_minutes'] : 0;

    $analysis = $engine->analyzeRestNeed($userId, $currentStudyMinutes);

    sendResponse(true, [
        'analysis' => $analysis,
        'user_id' => $userId
    ]);
}

/**
 * 피로도 조회
 *
 * @param Agent12PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleGetFatigue(Agent12PersonaEngine $engine, int $userId): void
{
    $fatigueInfo = $engine->getCurrentFatigueInfo($userId);
    $fatigueTrend = $engine->getFatigueTrend($userId);

    sendResponse(true, [
        'current' => $fatigueInfo,
        'trend' => $fatigueTrend,
        'user_id' => $userId
    ]);
}

/**
 * 헬스 체크
 *
 * @param Agent12PersonaEngine $engine
 * @return void
 */
function handleHealthCheck(Agent12PersonaEngine $engine): void
{
    $health = $engine->healthCheck();

    $code = $health['healthy'] ? 200 : 503;
    sendResponse($health['healthy'], $health, null, $code);
}

/*
 * =========================================================================
 * API 사용 예시
 * =========================================================================
 *
 * 1. 채팅:
 * POST /chat.php?action=chat
 * {
 *     "message": "지금 쉬어야 할까?",
 *     "context": {}
 * }
 *
 * 2. 휴식 시작:
 * POST /chat.php?action=start_rest
 * {
 *     "rest_type": "stretch",
 *     "study_duration": 45,
 *     "fatigue_level": 0.6
 * }
 *
 * 3. 휴식 종료:
 * POST /chat.php?action=end_rest
 * {
 *     "session_id": 123,
 *     "fatigue_level": 0.3
 * }
 *
 * 4. 상태 조회:
 * GET /chat.php?action=status
 *
 * 5. 통계 조회:
 * GET /chat.php?action=stats&days=7
 *
 * 6. 휴식 필요성 분석:
 * GET /chat.php?action=analyze_need&study_minutes=50
 *
 * 7. 피로도 조회:
 * GET /chat.php?action=fatigue
 *
 * 8. 헬스 체크:
 * GET /chat.php?action=health
 * =========================================================================
 */
