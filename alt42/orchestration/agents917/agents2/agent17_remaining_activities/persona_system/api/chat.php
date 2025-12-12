<?php
/**
 * Agent17 Chat API 엔드포인트
 *
 * 잔여 활동 조정 에이전트의 메인 대화 API
 *
 * 엔드포인트: POST /agents/agent17_remaining_activities/persona_system/api/chat.php
 *
 * 요청 파라미터:
 * - user_id: int (필수) - 사용자 ID
 * - message: string (필수) - 사용자 메시지
 * - session_data: object (선택) - 추가 세션 데이터
 *
 * 응답:
 * - success: bool
 * - response: object - 응답 데이터
 * - meta: object - 메타 정보
 *
 * @package AugmentedTeacher\Agent17\API
 * @version 1.0
 */

// 에러 리포팅 설정 (개발 중)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 현재 파일 경로 (에러 로깅용)
$currentFile = __FILE__;

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed',
        'message' => 'POST 요청만 허용됩니다.',
        'location' => "{$currentFile}:" . __LINE__
    ]);
    exit;
}

try {
    // Moodle 환경 로드
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
    require_login();

    // 엔진 로드
    require_once(dirname(__DIR__) . '/engine/Agent17PersonaEngine.php');

    // 요청 데이터 파싱
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON 파싱 오류: " . json_last_error_msg(), 400);
    }

    // 필수 파라미터 검증
    if (empty($data['user_id'])) {
        throw new Exception("user_id는 필수 파라미터입니다.", 400);
    }

    if (empty($data['message'])) {
        throw new Exception("message는 필수 파라미터입니다.", 400);
    }

    $userId = (int)$data['user_id'];
    $message = trim($data['message']);
    $sessionData = $data['session_data'] ?? [];

    // 권한 검증 (본인 또는 교사)
    $userRole = $DB->get_record_sql(
        "SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = '22'",
        [$USER->id]
    );
    $role = $userRole->data ?? 'student';

    // 본인이 아니고 교사도 아닌 경우 접근 거부
    if ($userId !== $USER->id && !in_array($role, ['teacher', 'admin', 'manager'])) {
        throw new Exception("해당 사용자에 대한 접근 권한이 없습니다.", 403);
    }

    // 엔진 초기화 및 처리
    $engine = new Agent17PersonaEngine([
        'debug_mode' => isset($data['debug']) && $data['debug'] === true,
        'log_enabled' => true,
        'cache_enabled' => true
    ]);

    // 메시지 처리
    $result = $engine->process($userId, $message, $sessionData);

    // 응답 생성
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response' => [
                'text' => $result['response']['text'],
                'persona' => [
                    'id' => $result['persona']['persona_id'],
                    'name' => $result['persona']['persona_name'],
                    'confidence' => $result['persona']['confidence']
                ],
                'context' => [
                    'situation' => $result['context']['situation'],
                    'intent' => $result['context']['intent'],
                    'emotion' => $result['context']['emotion']
                ]
            ],
            'meta' => [
                'agent_id' => $result['agent_id'],
                'processing_time_ms' => $result['meta']['processing_time_ms'],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        throw new Exception($result['error'] ?? '처리 중 오류가 발생했습니다.', 500);
    }

} catch (Exception $e) {
    $statusCode = $e->getCode();
    if ($statusCode < 400 || $statusCode >= 600) {
        $statusCode = 500;
    }

    http_response_code($statusCode);

    error_log("[Agent17 Chat API] {$currentFile}:" . __LINE__ .
        " - 오류: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'location' => "{$currentFile}:" . $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

/*
 * API 사용 예시:
 *
 * 요청:
 * POST /agents/agent17_remaining_activities/persona_system/api/chat.php
 * Content-Type: application/json
 *
 * {
 *     "user_id": 12345,
 *     "message": "이 활동이 너무 어려워요, 어떻게 해야 해요?",
 *     "session_data": {
 *         "course_id": 101,
 *         "current_activity_id": 456
 *     }
 * }
 *
 * 응답:
 * {
 *     "success": true,
 *     "response": {
 *         "text": "함께 천천히 해볼게요. 제가 먼저 보여드릴게요.",
 *         "persona": {
 *             "id": "R3_P1",
 *             "name": "인내심 있는 멘토",
 *             "confidence": 0.85
 *         },
 *         "context": {
 *             "situation": "R3",
 *             "intent": "help_request",
 *             "emotion": "confused"
 *         }
 *     },
 *     "meta": {
 *         "agent_id": "agent17",
 *         "processing_time_ms": 125.5,
 *         "timestamp": "2025-12-02 10:30:00"
 *     }
 * }
 */
