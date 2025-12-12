<?php
/**
 * Agent16 Chat API - 상호작용 준비 에이전트 채팅 API
 *
 * Agent16의 페르소나 엔진을 통한 채팅 처리 API 엔드포인트입니다.
 * 세계관 감지 및 페르소나 기반 응답을 생성합니다.
 *
 * @package AugmentedTeacher\Agent16\API
 * @version 1.0.0
 * @author ALT42 Orchestration System
 *
 * 사용 예시:
 * POST /agents/agent16_interaction_preparation/persona_system/api/chat.php
 * Content-Type: application/json
 * {
 *     "message": "시험이 다가오는데 어떻게 준비해야 할까요?",
 *     "session_data": {"course_id": 123}
 * }
 *
 * 응답 예시:
 * {
 *     "success": true,
 *     "agent_id": "agent16",
 *     "persona": {"persona_id": "A16_P3", "name": "시험 전략가"},
 *     "response": {"text": "..."},
 *     "worldview": "exam_prep"
 * }
 */

// 현재 파일 경로 (에러 로깅용)
define('CURRENT_FILE', __FILE__);

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 엔진 로드
require_once(__DIR__ . '/../engine/Agent16PersonaEngine.php');

/**
 * API 응답 생성
 *
 * @param bool $success 성공 여부
 * @param array $data 응답 데이터
 * @param string|null $error 에러 메시지
 * @param int $code HTTP 상태 코드
 * @return void
 */
function sendResponse(bool $success, array $data = [], ?string $error = null, int $code = 200): void {
    http_response_code($code);

    $response = [
        'success' => $success,
        'timestamp' => date('c'),
        'api_version' => '1.0.0'
    ];

    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['error'] = $error ?? 'Unknown error';
        $response['error_location'] = CURRENT_FILE . ':' . debug_backtrace()[0]['line'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 입력 데이터 검증
 *
 * @param array $data 입력 데이터
 * @return array|null 검증 오류 (없으면 null)
 */
function validateInput(array $data): ?array {
    $errors = [];

    if (empty($data['message'])) {
        $errors[] = 'message 필드는 필수입니다';
    } elseif (!is_string($data['message'])) {
        $errors[] = 'message는 문자열이어야 합니다';
    } elseif (mb_strlen($data['message']) > 2000) {
        $errors[] = 'message는 2000자 이내여야 합니다';
    }

    if (isset($data['session_data']) && !is_array($data['session_data'])) {
        $errors[] = 'session_data는 객체여야 합니다';
    }

    return empty($errors) ? null : $errors;
}

// ============================================
// 메인 API 처리
// ============================================

try {
    // 사용자 확인
    if (!isloggedin() || isguestuser()) {
        sendResponse(false, [], '로그인이 필요합니다', 401);
    }

    $userId = $USER->id;

    // 사용자 역할 조회
    $userRole = $DB->get_record_sql(
        "SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'",
        [$userId]
    );
    $role = $userRole ? $userRole->data : 'student';

    // 요청 메서드에 따른 처리
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGetRequest($userId);
            break;

        case 'POST':
            handlePostRequest($userId);
            break;

        default:
            sendResponse(false, [], '지원하지 않는 HTTP 메서드입니다', 405);
    }

} catch (Exception $e) {
    error_log("[Agent16 API] " . CURRENT_FILE . ":" . __LINE__ . " - " . $e->getMessage());
    sendResponse(false, [], '서버 내부 오류가 발생했습니다: ' . $e->getMessage(), 500);
}

// ============================================
// GET 요청 처리 - 상태 조회
// ============================================

/**
 * GET 요청 처리
 *
 * @param int $userId 사용자 ID
 * @return void
 */
function handleGetRequest(int $userId): void {
    $action = $_GET['action'] ?? 'status';

    switch ($action) {
        case 'status':
            getAgentStatus($userId);
            break;

        case 'worldviews':
            getWorldviews();
            break;

        case 'history':
            getInteractionHistory($userId);
            break;

        case 'state':
            getCurrentState($userId);
            break;

        default:
            sendResponse(false, [], '알 수 없는 action입니다', 400);
    }
}

/**
 * 에이전트 상태 조회
 *
 * @param int $userId 사용자 ID
 * @return void
 */
function getAgentStatus(int $userId): void {
    $engine = new Agent16PersonaEngine('agent16', ['debug_mode' => false]);

    sendResponse(true, [
        'agent_id' => 'agent16',
        'agent_name' => '상호작용 준비 에이전트',
        'status' => 'active',
        'user_id' => $userId,
        'debug_info' => $engine->getDebugInfo()
    ]);
}

/**
 * 세계관 목록 조회
 *
 * @return void
 */
function getWorldviews(): void {
    $engine = new Agent16PersonaEngine('agent16');

    $worldviews = $engine->getWorldviews();
    $formatted = [];

    foreach ($worldviews as $id => $worldview) {
        $formatted[] = [
            'id' => $id,
            'name' => $worldview['name_ko'],
            'description' => $worldview['description'],
            'triggers' => $worldview['triggers'],
            'priority' => $worldview['priority']
        ];
    }

    sendResponse(true, [
        'worldviews' => $formatted,
        'count' => count($formatted)
    ]);
}

/**
 * 상호작용 이력 조회
 *
 * @param int $userId 사용자 ID
 * @return void
 */
function getInteractionHistory(int $userId): void {
    global $DB;

    $limit = min(intval($_GET['limit'] ?? 20), 100);

    try {
        $records = $DB->get_records_sql(
            "SELECT * FROM {at_agent_persona_history}
             WHERE agent_id = 16 AND user_id = ?
             ORDER BY changed_at DESC
             LIMIT ?",
            [$userId, $limit]
        );

        $history = [];
        foreach ($records as $record) {
            $history[] = [
                'from_persona' => $record->from_persona,
                'to_persona' => $record->to_persona,
                'trigger_message' => $record->trigger_message,
                'changed_at' => $record->changed_at
            ];
        }

        sendResponse(true, [
            'user_id' => $userId,
            'history' => $history,
            'count' => count($history)
        ]);

    } catch (Exception $e) {
        error_log("[Agent16 API] " . CURRENT_FILE . ":" . __LINE__ . " - " . $e->getMessage());
        sendResponse(false, [], '이력 조회 실패: ' . $e->getMessage(), 500);
    }
}

/**
 * 현재 페르소나 상태 조회
 *
 * @param int $userId 사용자 ID
 * @return void
 */
function getCurrentState(int $userId): void {
    global $DB;

    require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentStateSync.php');

    $stateSync = new AgentStateSync($DB);
    $state = $stateSync->getState(16, $userId);

    if ($state) {
        sendResponse(true, [
            'user_id' => $userId,
            'current_persona' => $state['persona_id'],
            'context' => $state['context_data'],
            'last_interaction' => $state['last_interaction']
        ]);
    } else {
        sendResponse(true, [
            'user_id' => $userId,
            'current_persona' => null,
            'context' => null,
            'message' => '아직 상호작용 기록이 없습니다'
        ]);
    }
}

// ============================================
// POST 요청 처리 - 채팅
// ============================================

/**
 * POST 요청 처리
 *
 * @param int $userId 사용자 ID
 * @return void
 */
function handlePostRequest(int $userId): void {
    // JSON 입력 파싱
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, [], 'JSON 파싱 오류: ' . json_last_error_msg(), 400);
    }

    $action = $input['action'] ?? 'chat';

    switch ($action) {
        case 'chat':
            processChat($userId, $input);
            break;

        case 'detect_worldview':
            detectWorldview($input);
            break;

        case 'set_worldview':
            setWorldview($userId, $input);
            break;

        default:
            sendResponse(false, [], '알 수 없는 action입니다', 400);
    }
}

/**
 * 채팅 처리
 *
 * @param int $userId 사용자 ID
 * @param array $input 입력 데이터
 * @return void
 */
function processChat(int $userId, array $input): void {
    // 입력 검증
    $validationErrors = validateInput($input);
    if ($validationErrors) {
        sendResponse(false, [], implode(', ', $validationErrors), 400);
    }

    $message = trim($input['message']);
    $sessionData = $input['session_data'] ?? [];

    // 디버그 모드 설정
    $debugMode = isset($input['debug']) && $input['debug'] === true;

    try {
        // 엔진 초기화 및 규칙 로드
        $engine = new Agent16PersonaEngine('agent16', ['debug_mode' => $debugMode]);

        if (!$engine->loadRules()) {
            error_log("[Agent16 API] " . CURRENT_FILE . ":" . __LINE__ . " - 규칙 로드 실패");
            // 규칙 없이도 기본 동작 계속
        }

        // 메시지 처리
        $result = $engine->process($userId, $message, $sessionData);

        if ($result['success']) {
            $response = [
                'agent_id' => $result['agent_id'],
                'persona' => [
                    'id' => $result['persona']['persona_id'] ?? 'A16_P1',
                    'name' => $result['persona']['persona_name'] ?? '체계적 가이드',
                    'tone' => $result['persona']['tone'] ?? 'Professional',
                    'confidence' => $result['persona']['confidence'] ?? 0.5
                ],
                'response' => $result['response'],
                'analysis' => [
                    'detected_worldview' => $result['analysis']['detected_worldview'] ?? null,
                    'worldview_confidence' => $result['analysis']['worldview_confidence'] ?? 0,
                    'situation_group' => $result['analysis']['situation_group'] ?? 'S0',
                    'learning_stage' => $result['analysis']['learning_stage'] ?? 'exploration'
                ],
                'meta' => $result['meta'] ?? []
            ];

            // 디버그 정보 추가
            if ($debugMode) {
                $response['debug'] = [
                    'full_analysis' => $result['analysis'],
                    'actions' => $result['actions'] ?? [],
                    'engine_info' => $engine->getDebugInfo()
                ];
            }

            sendResponse(true, $response);

        } else {
            sendResponse(false, [], $result['error'] ?? '처리 실패', 500);
        }

    } catch (Exception $e) {
        error_log("[Agent16 API] " . CURRENT_FILE . ":" . __LINE__ . " - " . $e->getMessage());
        sendResponse(false, [], '채팅 처리 중 오류 발생: ' . $e->getMessage(), 500);
    }
}

/**
 * 세계관 감지 (메시지 분석만)
 *
 * @param array $input 입력 데이터
 * @return void
 */
function detectWorldview(array $input): void {
    if (empty($input['message'])) {
        sendResponse(false, [], 'message 필드가 필요합니다', 400);
    }

    $engine = new Agent16PersonaEngine('agent16');

    // 리플렉션을 사용하여 private 메서드 호출 (테스트용)
    $reflection = new ReflectionClass($engine);

    $detectMethod = $reflection->getMethod('detectWorldview');
    $detectMethod->setAccessible(true);
    $worldviewId = $detectMethod->invoke($engine, $input['message']);

    $confidenceMethod = $reflection->getMethod('calculateWorldviewConfidence');
    $confidenceMethod->setAccessible(true);
    $confidence = $confidenceMethod->invoke($engine, $input['message'], $worldviewId);

    $worldviewInfo = $worldviewId ? $engine->getWorldview($worldviewId) : null;

    sendResponse(true, [
        'message' => $input['message'],
        'detected_worldview' => $worldviewId,
        'worldview_name' => $worldviewInfo ? $worldviewInfo['name_ko'] : null,
        'confidence' => $confidence,
        'description' => $worldviewInfo ? $worldviewInfo['description'] : null
    ]);
}

/**
 * 세계관 수동 설정
 *
 * @param int $userId 사용자 ID
 * @param array $input 입력 데이터
 * @return void
 */
function setWorldview(int $userId, array $input): void {
    if (empty($input['worldview_id'])) {
        sendResponse(false, [], 'worldview_id 필드가 필요합니다', 400);
    }

    $worldviewId = $input['worldview_id'];

    $engine = new Agent16PersonaEngine('agent16');
    $worldview = $engine->getWorldview($worldviewId);

    if (!$worldview) {
        sendResponse(false, [], '유효하지 않은 세계관 ID입니다', 400);
    }

    // 상태 업데이트
    global $DB;
    require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentStateSync.php');

    $stateSync = new AgentStateSync($DB);
    $persona = $engine->selectPersonaByWorldview($worldviewId);

    $success = $stateSync->updateState(16, $userId, [
        'persona_id' => $persona['persona_id'],
        'context_data' => [
            'worldview' => $worldviewId,
            'worldview_confidence' => 1.0, // 수동 설정은 신뢰도 100%
            'manual_set' => true,
            'set_at' => date('Y-m-d H:i:s')
        ]
    ]);

    if ($success) {
        sendResponse(true, [
            'worldview_id' => $worldviewId,
            'worldview_name' => $worldview['name_ko'],
            'persona' => $persona,
            'message' => '세계관이 설정되었습니다'
        ]);
    } else {
        sendResponse(false, [], '세계관 설정 실패', 500);
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state (agent_id, user_id, persona_id, context_data)
 * - at_agent_persona_history (agent_id, user_id, from_persona, to_persona, trigger_message)
 * - mdl_user_info_data (userid, fieldid, data) - 사용자 역할
 *
 * API 엔드포인트:
 * - GET ?action=status - 에이전트 상태 조회
 * - GET ?action=worldviews - 세계관 목록 조회
 * - GET ?action=history - 상호작용 이력 조회
 * - GET ?action=state - 현재 페르소나 상태 조회
 * - POST {action: "chat", message: "..."} - 채팅 처리
 * - POST {action: "detect_worldview", message: "..."} - 세계관 감지
 * - POST {action: "set_worldview", worldview_id: "..."} - 세계관 수동 설정
 */
