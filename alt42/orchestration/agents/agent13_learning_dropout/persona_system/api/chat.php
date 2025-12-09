<?php
/**
 * chat.php
 *
 * Agent13 학습 이탈 채팅 API
 * 페르소나 기반 대화 및 이탈 위험 분석 처리
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent13LearningDropout
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/api/chat.php
 *
 * API Endpoints:
 * - POST /chat.php?action=chat           : 채팅 메시지 처리
 * - POST /chat.php?action=identify       : 페르소나 식별
 * - GET  /chat.php?action=status         : 현재 상태 조회
 * - GET  /chat.php?action=risk           : 이탈 위험 분석
 * - POST /chat.php?action=intervene      : 개입 실행
 * - GET  /chat.php?action=stats          : 이탈 통계 조회
 * - GET  /chat.php?action=trend          : 위험도 추세 조회
 * - POST /chat.php?action=log_response   : 사용자 반응 기록
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
require_once(__DIR__ . '/../Agent13PersonaEngine.php');

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
        'agent' => 13
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
    $engine = new Agent13PersonaEngine($DB);
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

        case 'risk':
            handleRiskAnalysis($engine, $userId);
            break;

        case 'intervene':
            handleIntervene($engine, $userId, $data);
            break;

        case 'stats':
            handleGetStats($engine, $userId, $data);
            break;

        case 'trend':
            handleGetTrend($engine, $userId, $data);
            break;

        case 'log_response':
            handleLogResponse($engine, $userId, $data);
            break;

        case 'indicators':
            handleGetIndicators($engine, $userId);
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
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleChat(Agent13PersonaEngine $engine, int $userId, array $data): void
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
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleIdentify(Agent13PersonaEngine $engine, int $userId, array $data): void
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
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleStatus(Agent13PersonaEngine $engine, int $userId): void
{
    // 페르소나 식별
    $persona = $engine->identifyPersona($userId, []);

    // 현재 전략
    $currentStrategy = $engine->getCurrentStrategy($userId);

    // 위험도 정보
    $riskInfo = $engine->getCurrentRiskInfo($userId);

    sendResponse(true, [
        'persona' => $persona,
        'strategy' => $currentStrategy,
        'risk' => $riskInfo,
        'user_id' => $userId
    ]);
}

/**
 * 이탈 위험 분석
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleRiskAnalysis(Agent13PersonaEngine $engine, int $userId): void
{
    // 이탈 위험 분석 실행
    $analysis = $engine->analyzeDropoutRisk($userId);

    // 페르소나 식별
    $persona = $engine->identifyPersona($userId, ['trigger' => 'risk_analysis']);

    sendResponse(true, [
        'analysis' => $analysis,
        'persona' => $persona,
        'user_id' => $userId
    ]);
}

/**
 * 개입 실행
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleIntervene(Agent13PersonaEngine $engine, int $userId, array $data): void
{
    $interventionType = isset($data['intervention_type']) ? $data['intervention_type'] : 'auto';
    $options = isset($data['options']) ? $data['options'] : [];

    // 현재 페르소나 식별
    $persona = $engine->identifyPersona($userId, ['trigger' => 'intervention_request']);

    // 개입 실행
    $result = $engine->executeIntervention($userId, $persona['persona_code'], $interventionType, $options);

    if ($result['success']) {
        sendResponse(true, [
            'intervention' => $result,
            'persona' => $persona,
            'user_id' => $userId
        ]);
    } else {
        sendResponse(false, null, $result['error'], 400);
    }
}

/**
 * 이탈 통계 조회
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleGetStats(Agent13PersonaEngine $engine, int $userId, array $data): void
{
    $days = isset($data['days']) ? (int)$data['days'] : 7;
    $days = max(1, min(90, $days)); // 1-90일 범위

    $stats = $engine->getDropoutStats($userId, $days);

    sendResponse(true, [
        'stats' => $stats,
        'period_days' => $days,
        'user_id' => $userId
    ]);
}

/**
 * 위험도 추세 조회
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleGetTrend(Agent13PersonaEngine $engine, int $userId, array $data): void
{
    $days = isset($data['days']) ? (int)$data['days'] : 7;
    $days = max(1, min(30, $days)); // 1-30일 범위

    $trend = $engine->getRiskTrend($userId, $days);

    sendResponse(true, [
        'trend' => $trend,
        'period_days' => $days,
        'user_id' => $userId
    ]);
}

/**
 * 사용자 반응 기록
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleLogResponse(Agent13PersonaEngine $engine, int $userId, array $data): void
{
    // 필수 필드 검증
    if (empty($data['intervention_id'])) {
        sendResponse(false, null, 'Intervention ID is required. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

    $interventionId = (int)$data['intervention_id'];
    $responseType = isset($data['response_type']) ? $data['response_type'] : 'acknowledged';
    $feedback = isset($data['feedback']) ? $data['feedback'] : null;

    // 반응 기록
    $result = $engine->logUserResponse($userId, $interventionId, $responseType, $feedback);

    if ($result['success']) {
        sendResponse(true, [
            'logged' => true,
            'intervention_id' => $interventionId,
            'response_type' => $responseType
        ]);
    } else {
        sendResponse(false, null, $result['error'], 400);
    }
}

/**
 * 이탈 지표 조회
 *
 * @param Agent13PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleGetIndicators(Agent13PersonaEngine $engine, int $userId): void
{
    $indicators = $engine->getDropoutIndicators($userId);

    sendResponse(true, [
        'indicators' => $indicators,
        'window' => '24h_rolling',
        'user_id' => $userId
    ]);
}

/**
 * 헬스 체크
 *
 * @param Agent13PersonaEngine $engine
 * @return void
 */
function handleHealthCheck(Agent13PersonaEngine $engine): void
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
 *     "message": "학습에 집중이 잘 안돼요",
 *     "context": {}
 * }
 *
 * 2. 위험 분석:
 * GET /chat.php?action=risk
 *
 * 3. 개입 실행:
 * POST /chat.php?action=intervene
 * {
 *     "intervention_type": "gentle_reminder",
 *     "options": {"include_suggestion": true}
 * }
 *
 * 4. 상태 조회:
 * GET /chat.php?action=status
 *
 * 5. 통계 조회:
 * GET /chat.php?action=stats&days=7
 *
 * 6. 추세 조회:
 * GET /chat.php?action=trend&days=7
 *
 * 7. 지표 조회:
 * GET /chat.php?action=indicators
 *
 * 8. 사용자 반응 기록:
 * POST /chat.php?action=log_response
 * {
 *     "intervention_id": 123,
 *     "response_type": "positive",
 *     "feedback": "도움이 됐어요"
 * }
 *
 * 9. 헬스 체크:
 * GET /chat.php?action=health
 * =========================================================================
 */
