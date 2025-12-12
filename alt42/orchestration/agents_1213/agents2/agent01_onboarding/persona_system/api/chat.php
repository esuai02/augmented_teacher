<?php
/**
 * 페르소나 채팅 API 엔드포인트
 *
 * POST /api/chat.php
 * - message: 사용자 메시지 (필수)
 * - user_id: 사용자 ID (선택, 미입력시 현재 로그인 사용자)
 * - ai_enabled: AI 사용 여부 (선택, 기본 true)
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// CORS 헤더 (필요시)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청 처리 (테스트용)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 파라미터 없으면 API 정보 표시
    if (empty($_GET['message'])) {
        echo json_encode([
            'success' => true,
            'api' => 'AlphaTutor42 Persona Chat API',
            'version' => '1.0',
            'usage' => [
                'POST' => '/api/chat.php with JSON body {"message": "텍스트", "ai_enabled": true}',
                'GET' => '/api/chat.php?message=텍스트&ai_enabled=1 (테스트용)'
            ],
            'test_page' => str_replace('/api/chat.php', '/test.php', $_SERVER['REQUEST_URI'])
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    // GET 파라미터를 input으로 변환
    $input = $_GET;
}

// 엔진 로드
require_once(__DIR__ . '/../engine/AIPersonaEngine.php');

try {
    // 입력 파싱 (GET이 아닌 경우만)
    if (!isset($input)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // form-data 폴백
            $input = $_POST;
        }
    }

    // 필수 파라미터 검증
    $message = trim($input['message'] ?? '');
    if (empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'message 파라미터가 필요합니다',
            'file' => __FILE__,
            'line' => __LINE__
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 사용자 ID 결정
    $userId = (int)($input['user_id'] ?? 0);
    if ($userId <= 0 && isset($USER->id)) {
        $userId = (int)$USER->id;
    }
    if ($userId <= 0) {
        $userId = 1; // 게스트 폴백
    }

    // AI 설정
    $aiEnabled = isset($input['ai_enabled']) ? (bool)$input['ai_enabled'] : true;

    // 엔진 초기화
    $engine = new AIPersonaEngine([
        'ai_enabled' => $aiEnabled,
        'ai_threshold' => 0.7,
        'ai_response_enabled' => $aiEnabled,
        'debug_mode' => false
    ]);

    // 규칙 로드
    $rulesPath = __DIR__ . '/../rules/rules.yaml';
    if (file_exists($rulesPath)) {
        $engine->loadRules($rulesPath);
    }

    // 세션 데이터 (있는 경우)
    $sessionData = [
        'current_situation' => $input['situation'] ?? 'S1',
        'current_persona' => $input['persona'] ?? null
    ];

    // 프로세스 실행
    $result = $engine->process($userId, $message, $sessionData);

    // 응답
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

/*
 * 사용 예시:
 *
 * curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/api/chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "수학이 너무 어려워요", "ai_enabled": true}'
 *
 * 응답:
 * {
 *   "success": true,
 *   "user_id": 123,
 *   "persona": {
 *     "persona_id": "E_P1",
 *     "persona_name": "수학 불안형 공포자",
 *     "confidence": 0.85,
 *     "tone": "Empathetic",
 *     "intervention": "EmotionalSupport"
 *   },
 *   "response": {
 *     "text": "어려움을 느끼시는 마음 충분히 이해해요...",
 *     "source": "ai",
 *     "tone": "Empathetic"
 *   },
 *   "context": {
 *     "intent": "frustration",
 *     "emotion": "anxiety",
 *     "topics": ["수학", "어려움"]
 *   },
 *   "meta": {
 *     "ai_used": true,
 *     "processing_time_ms": 1234.56
 *   }
 * }
 */
