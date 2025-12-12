<?php
/**
 * Agent14 Persona System API Endpoint
 *
 * 교육과정 혁신 에이전트 페르소나 시스템 API
 *
 * @package AugmentedTeacher\Agent14\PersonaSystem\API
 * @version 1.0
 *
 * API Endpoints:
 * - POST /process.php?action=identify : 페르소나 식별
 * - POST /process.php?action=respond : 응답 생성
 * - GET /process.php?action=status : 현재 페르소나 상태
 * - GET /process.php?action=personas : 페르소나 목록
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 현재 파일 정보
define('CURRENT_FILE', __FILE__);
define('API_VERSION', '1.0');
define('AGENT_ID', 'agent14');

// 의존성 로드
require_once(__DIR__ . '/../engine/Agent14PersonaEngine.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentCommunicator.php');

// CORS 헤더 (필요시)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * API 응답 생성
 */
function jsonResponse(array $data, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 에러 응답 생성
 */
function errorResponse(string $message, int $httpCode = 400, string $errorCode = 'ERROR'): void {
    jsonResponse([
        'success' => false,
        'error' => [
            'code' => $errorCode,
            'message' => $message,
            'file' => CURRENT_FILE,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], $httpCode);
}

/**
 * 성공 응답 생성
 */
function successResponse(array $data, string $message = 'Success'): void {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'meta' => [
            'agent_id' => AGENT_ID,
            'api_version' => API_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * 입력 데이터 가져오기
 */
function getInputData(): array {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
        errorResponse('Invalid JSON input: ' . json_last_error_msg(), 400, 'INVALID_JSON');
    }

    return is_array($data) ? $data : [];
}

/**
 * 사용자 ID 가져오기 (로그인 또는 파라미터)
 */
function getUserId(): int {
    global $USER;

    // POST 데이터에서 user_id 확인
    $input = getInputData();
    if (!empty($input['user_id'])) {
        return (int)$input['user_id'];
    }

    // GET 파라미터 확인
    if (!empty($_GET['user_id'])) {
        return (int)$_GET['user_id'];
    }

    // 로그인 사용자
    if (!empty($USER->id)) {
        return (int)$USER->id;
    }

    // 테스트용 기본값
    return 0;
}

// 메인 처리
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $userId = getUserId();

    // 엔진 초기화
    $rulesPath = __DIR__ . '/../engine/config/rules.yaml';
    $engine = new Agent14PersonaEngine($rulesPath);

    switch ($action) {

        /**
         * 페르소나 식별
         * POST /process.php?action=identify
         * Body: { "user_message": "...", "context": { ... } }
         */
        case 'identify':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('POST method required', 405, 'METHOD_NOT_ALLOWED');
            }

            $input = getInputData();
            $userMessage = $input['user_message'] ?? '';
            $context = $input['context'] ?? [];

            if (empty($userMessage)) {
                errorResponse('user_message is required', 400, 'MISSING_PARAM');
            }

            // 컨텍스트에 사용자 메시지 추가
            $context['user_message'] = $userMessage;
            $context['user_id'] = $userId;

            // 페르소나 식별
            $result = $engine->identify($userId, $context);

            successResponse([
                'identification' => $result,
                'user_id' => $userId
            ], 'Persona identified successfully');
            break;

        /**
         * 응답 생성
         * POST /process.php?action=respond
         * Body: { "user_message": "...", "context": { ... }, "template_key": "..." }
         */
        case 'respond':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('POST method required', 405, 'METHOD_NOT_ALLOWED');
            }

            $input = getInputData();
            $userMessage = $input['user_message'] ?? '';
            $context = $input['context'] ?? [];
            $templateKey = $input['template_key'] ?? '';

            if (empty($userMessage)) {
                errorResponse('user_message is required', 400, 'MISSING_PARAM');
            }

            // 컨텍스트 설정
            $context['user_message'] = $userMessage;
            $context['user_id'] = $userId;

            // 페르소나 식별 후 응답 생성
            $identification = $engine->identify($userId, $context);
            $response = $engine->respond($identification, $context, $templateKey);

            successResponse([
                'response' => $response,
                'identification' => $identification,
                'user_id' => $userId
            ], 'Response generated successfully');
            break;

        /**
         * 현재 페르소나 상태 조회
         * GET /process.php?action=status
         */
        case 'status':
            if ($userId <= 0) {
                errorResponse('User ID required', 400, 'MISSING_USER');
            }

            // AgentCommunicator로 상태 조회
            $communicator = new AgentCommunicator(AGENT_ID);
            $states = $communicator->getPersonaStates($userId);

            successResponse([
                'user_id' => $userId,
                'agent_states' => $states,
                'current_agent' => $states[AGENT_ID] ?? null
            ], 'Status retrieved successfully');
            break;

        /**
         * 페르소나 목록 조회
         * GET /process.php?action=personas
         */
        case 'personas':
            $personas = $engine->getPersonas();
            $situations = $engine->getSituations();

            successResponse([
                'personas' => $personas,
                'situations' => $situations,
                'total_count' => count($personas)
            ], 'Personas retrieved successfully');
            break;

        /**
         * 메시지 분석
         * POST /process.php?action=analyze
         * Body: { "user_message": "..." }
         */
        case 'analyze':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('POST method required', 405, 'METHOD_NOT_ALLOWED');
            }

            $input = getInputData();
            $userMessage = $input['user_message'] ?? '';

            if (empty($userMessage)) {
                errorResponse('user_message is required', 400, 'MISSING_PARAM');
            }

            // DataContext로 메시지 분석
            require_once(__DIR__ . '/../engine/impl/Agent14DataContext.php');
            $dataContext = new Agent14DataContext();
            $analysis = $dataContext->analyzeMessage($userMessage);
            $situation = $dataContext->determineSituation(['user_message' => $userMessage]);

            successResponse([
                'analysis' => $analysis,
                'suggested_situation' => $situation,
                'message_length' => mb_strlen($userMessage)
            ], 'Message analyzed successfully');
            break;

        /**
         * 상태 동기화 (에이전트 간)
         * POST /process.php?action=sync
         * Body: { "state_data": { ... } }
         */
        case 'sync':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                errorResponse('POST method required', 405, 'METHOD_NOT_ALLOWED');
            }

            if ($userId <= 0) {
                errorResponse('User ID required', 400, 'MISSING_USER');
            }

            $input = getInputData();
            $stateData = $input['state_data'] ?? [];

            $communicator = new AgentCommunicator(AGENT_ID);
            $success = $communicator->updateMyPersonaState($userId, $stateData);

            if ($success) {
                successResponse([
                    'synced' => true,
                    'user_id' => $userId,
                    'state_data' => $stateData
                ], 'State synchronized successfully');
            } else {
                errorResponse('Failed to sync state', 500, 'SYNC_FAILED');
            }
            break;

        /**
         * 대기 메시지 조회 (에이전트 간)
         * GET /process.php?action=messages
         */
        case 'messages':
            $limit = (int)($_GET['limit'] ?? 50);

            $communicator = new AgentCommunicator(AGENT_ID);
            $messages = $communicator->getPendingMessages($limit);

            successResponse([
                'messages' => $messages,
                'count' => count($messages)
            ], 'Messages retrieved successfully');
            break;

        /**
         * 헬스 체크
         * GET /process.php?action=health
         */
        case 'health':
            successResponse([
                'status' => 'healthy',
                'agent_id' => AGENT_ID,
                'api_version' => API_VERSION,
                'php_version' => PHP_VERSION,
                'engine_ready' => true
            ], 'Agent14 Persona System is healthy');
            break;

        /**
         * 기본 (액션 없음)
         */
        default:
            jsonResponse([
                'success' => true,
                'message' => 'Agent14 Persona System API',
                'agent_id' => AGENT_ID,
                'api_version' => API_VERSION,
                'endpoints' => [
                    'POST /process.php?action=identify' => '페르소나 식별',
                    'POST /process.php?action=respond' => '응답 생성',
                    'POST /process.php?action=analyze' => '메시지 분석',
                    'POST /process.php?action=sync' => '상태 동기화',
                    'GET /process.php?action=status' => '현재 상태 조회',
                    'GET /process.php?action=personas' => '페르소나 목록',
                    'GET /process.php?action=messages' => '대기 메시지 조회',
                    'GET /process.php?action=health' => '헬스 체크'
                ],
                'documentation' => 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/'
            ]);
            break;
    }

} catch (Exception $e) {
    errorResponse(
        'Internal error: ' . $e->getMessage() . ' at line ' . $e->getLine(),
        500,
        'INTERNAL_ERROR'
    );
}

/*
 * API 테스트 URL:
 * - https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/api/process.php?action=health
 * - https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/api/process.php?action=personas
 *
 * 관련 DB 테이블:
 * - mdl_at_agent_persona_state: 에이전트별 페르소나 상태
 * - mdl_at_agent_messages: 에이전트 간 메시지
 * - mdl_at_persona_log: 처리 로그
 * - mdl_at_agent_config: 에이전트 설정
 */
