<?php
/**
 * chat.php
 *
 * Agent02 시험일정 채팅 API
 * 페르소나 기반 대화 처리
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent02ExamSchedule
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/api/chat.php
 *
 * API Endpoints:
 * - POST /chat.php?action=chat      : 채팅 메시지 처리
 * - POST /chat.php?action=identify  : 페르소나 식별
 * - GET  /chat.php?action=status    : 현재 상태 조회
 * - POST /chat.php?action=add_exam  : 시험 추가
 * - GET  /chat.php?action=exams     : 시험 목록 조회
 * - POST /chat.php?action=strategy  : 학습 전략 조회
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
require_once(__DIR__ . '/../Agent02PersonaEngine.php');

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
        'agent' => 2
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
    $engine = new Agent02PersonaEngine($DB);
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

        case 'add_exam':
            handleAddExam($engine, $userId, $data);
            break;

        case 'exams':
            handleGetExams($engine, $userId, $data);
            break;

        case 'strategy':
            handleGetStrategy($engine, $userId);
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
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleChat(Agent02PersonaEngine $engine, int $userId, array $data): void
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
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleIdentify(Agent02PersonaEngine $engine, int $userId, array $data): void
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
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleStatus(Agent02PersonaEngine $engine, int $userId): void
{
    // 페르소나 식별
    $persona = $engine->identifyPersona($userId, []);

    // 시험 목록
    $exams = $engine->getExams($userId, ['status' => 'active', 'limit' => 5]);

    // 현재 전략
    $strategy = $engine->getCurrentStrategy($userId);

    sendResponse(true, [
        'persona' => $persona,
        'exams' => $exams,
        'strategy' => $strategy,
        'user_id' => $userId
    ]);
}

/**
 * 시험 추가
 *
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleAddExam(Agent02PersonaEngine $engine, int $userId, array $data): void
{
    // 필수 필드 검증
    if (empty($data['exam_name'])) {
        sendResponse(false, null, 'exam_name is required. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }
    if (empty($data['exam_date'])) {
        sendResponse(false, null, 'exam_date is required. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

    // 날짜 형식 검증
    $examDate = $data['exam_date'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate)) {
        sendResponse(false, null, 'exam_date must be in YYYY-MM-DD format. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

    // 과거 날짜 체크
    if (strtotime($examDate) < strtotime('today')) {
        sendResponse(false, null, 'exam_date cannot be in the past. File: ' . __FILE__ . ' Line: ' . __LINE__, 400);
    }

    // 시험 추가
    $result = $engine->addExam($userId, [
        'exam_name' => $data['exam_name'],
        'exam_date' => $examDate,
        'target_score' => isset($data['target_score']) ? $data['target_score'] : null,
        'subjects' => isset($data['subjects']) ? $data['subjects'] : [],
        'exam_scope' => isset($data['exam_scope']) ? $data['exam_scope'] : null
    ]);

    if ($result['success']) {
        // 페르소나 재식별
        $persona = $engine->identifyPersona($userId, ['trigger' => 'exam_added']);

        sendResponse(true, [
            'exam_id' => $result['exam_id'],
            'message' => $result['message'],
            'new_persona' => $persona
        ]);
    } else {
        sendResponse(false, null, $result['error'] . ' File: ' . __FILE__ . ' Line: ' . __LINE__, 500);
    }
}

/**
 * 시험 목록 조회
 *
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @param array $data
 * @return void
 */
function handleGetExams(Agent02PersonaEngine $engine, int $userId, array $data): void
{
    $options = [
        'status' => isset($data['status']) ? $data['status'] : 'active',
        'limit' => isset($data['limit']) ? (int)$data['limit'] : 10,
        'offset' => isset($data['offset']) ? (int)$data['offset'] : 0
    ];

    $exams = $engine->getExams($userId, $options);

    sendResponse(true, [
        'exams' => $exams,
        'count' => count($exams),
        'user_id' => $userId
    ]);
}

/**
 * 학습 전략 조회
 *
 * @param Agent02PersonaEngine $engine
 * @param int $userId
 * @return void
 */
function handleGetStrategy(Agent02PersonaEngine $engine, int $userId): void
{
    $strategy = $engine->getCurrentStrategy($userId);

    sendResponse(true, [
        'strategy' => $strategy,
        'user_id' => $userId
    ]);
}

/**
 * 헬스 체크
 *
 * @param Agent02PersonaEngine $engine
 * @return void
 */
function handleHealthCheck(Agent02PersonaEngine $engine): void
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
 *     "message": "시험 언제야?",
 *     "context": {}
 * }
 *
 * 2. 시험 추가:
 * POST /chat.php?action=add_exam
 * {
 *     "exam_name": "중간고사",
 *     "exam_date": "2025-12-20",
 *     "target_score": 90,
 *     "subjects": ["수학", "영어"]
 * }
 *
 * 3. 상태 조회:
 * GET /chat.php?action=status
 *
 * 4. 시험 목록:
 * GET /chat.php?action=exams&status=active&limit=5
 *
 * 5. 전략 조회:
 * GET /chat.php?action=strategy
 *
 * 6. 헬스 체크:
 * GET /chat.php?action=health
 * =========================================================================
 */
