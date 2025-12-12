<?php
/**
 * Agent21 개입 실행 페르소나 채팅 API 엔드포인트
 *
 * POST /api/chat.php
 * - message: 사용자 메시지 (필수)
 * - user_id: 사용자 ID (선택, 미입력시 현재 로그인 사용자)
 * - intervention_id: 현재 개입 ID (선택)
 * - response_type: 이전 반응 유형 A/R/N/D (선택)
 * - ai_enabled: AI 사용 여부 (선택, 기본 true)
 *
 * @package AugmentedTeacher\Agent21\PersonaSystem
 * @version 1.0
 */

// 현재 파일 경로 (에러 로깅용)
$currentFile = __FILE__;

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// CORS 헤더
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
            'api' => 'Agent21 Intervention Execution Persona Chat API',
            'version' => '1.0',
            'agent' => 'agent21_intervention_execution',
            'response_types' => [
                'A' => 'Acceptance (수용)',
                'R' => 'Resistance (저항)',
                'N' => 'No Response (무응답)',
                'D' => 'Delayed (지연반응)'
            ],
            'usage' => [
                'POST' => '/api/chat.php with JSON body',
                'GET' => '/api/chat.php?message=텍스트&response_type=A (테스트용)'
            ],
            'parameters' => [
                'message' => '사용자 메시지 (필수)',
                'user_id' => '사용자 ID (선택)',
                'intervention_id' => '개입 ID (선택)',
                'response_type' => '이전 반응 유형 A/R/N/D (선택)',
                'ai_enabled' => 'AI 사용 여부 (선택, 기본 true)'
            ],
            'test_page' => str_replace('/api/chat.php', '/test.php', $_SERVER['REQUEST_URI'])
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    // GET 파라미터를 input으로 변환
    $input = $_GET;
}

// 엔진 로드
require_once(__DIR__ . '/../engine/PersonaEngine.php');
require_once(__DIR__ . '/../engine/ContextManager.php');
require_once(__DIR__ . '/../engine/ResponseGenerator.php');

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
            'file' => $currentFile,
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

    // 개입 파라미터
    $interventionId = (int)($input['intervention_id'] ?? 0);
    $previousResponseType = strtoupper(trim($input['response_type'] ?? ''));

    // 유효한 반응 유형 검증
    $validResponseTypes = ['A', 'R', 'N', 'D', ''];
    if (!in_array($previousResponseType, $validResponseTypes)) {
        $previousResponseType = '';
    }

    // AI 설정
    $aiEnabled = isset($input['ai_enabled']) ? (bool)$input['ai_enabled'] : true;

    // 시작 시간 기록
    $startTime = microtime(true);

    // 엔진 초기화
    $engine = new PersonaEngine([
        'ai_enabled' => $aiEnabled,
        'ai_threshold' => 0.7,
        'debug_mode' => false
    ]);

    // 규칙 로드
    $rulesPath = __DIR__ . '/../rules.yaml';
    if (file_exists($rulesPath)) {
        $engine->loadRules($rulesPath);
    }

    // 세션 데이터 구성 (개입 실행 특화)
    $sessionData = [
        'previous_response_type' => $previousResponseType,
        'intervention_id' => $interventionId,
        'current_persona' => $input['persona'] ?? null,
        'response_time' => $input['response_time'] ?? null,  // 응답까지 걸린 시간
        'interaction_count' => (int)($input['interaction_count'] ?? 0),
        'last_activity' => $input['last_activity'] ?? null
    ];

    // DB에서 학생 컨텍스트 조회 (가능한 경우)
    $studentContext = [];
    try {
        $student = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname');
        if ($student) {
            $studentContext['student_name'] = $student->firstname;
            $studentContext['student_id'] = $student->id;
        }

        // 이전 개입 기록 조회 (있는 경우)
        if ($interventionId > 0) {
            $intervention = $DB->get_record('alt42_interventions', ['id' => $interventionId]);
            if ($intervention) {
                $sessionData['intervention_type'] = $intervention->type ?? null;
                $sessionData['intervention_status'] = $intervention->status ?? null;
            }
        }
    } catch (Exception $dbException) {
        // DB 오류 무시 (컨텍스트 없이 진행)
        error_log("[Agent21 Chat API] {$currentFile}:" . __LINE__ . " - DB 조회 오류: " . $dbException->getMessage());
    }

    // 메시지와 컨텍스트 병합
    $fullContext = array_merge($sessionData, $studentContext, ['user_message' => $message]);

    // 프로세스 실행
    $result = $engine->process($userId, $message, $fullContext);

    // 처리 시간 계산
    $processingTime = (microtime(true) - $startTime) * 1000;

    // 결과에 Agent21 특화 정보 추가
    $result['agent'] = 'agent21_intervention_execution';
    $result['meta'] = array_merge($result['meta'] ?? [], [
        'processing_time_ms' => round($processingTime, 2),
        'ai_used' => $aiEnabled,
        'intervention_id' => $interventionId ?: null,
        'previous_response_type' => $previousResponseType ?: null
    ]);

    // 반응 유형 전환 감지 결과 추가
    if (isset($result['detected_response_type']) && !empty($previousResponseType)) {
        $newType = $result['detected_response_type'];
        if ($newType !== $previousResponseType) {
            $result['transition'] = [
                'from' => $previousResponseType,
                'to' => $newType,
                'type' => isPositiveTransition($previousResponseType, $newType) ? 'positive' : 'negative'
            ];
        }
    }

    // 응답
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $currentFile,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 긍정적 전환 여부 판단
 *
 * @param string $from 이전 반응 유형
 * @param string $to 현재 반응 유형
 * @return bool 긍정적 전환 여부
 */
function isPositiveTransition(string $from, string $to): bool {
    $positiveTransitions = [
        'R_A' => true,  // 저항 → 수용
        'N_A' => true,  // 무응답 → 수용
        'N_D' => true,  // 무응답 → 지연반응 (일부 긍정적)
        'D_A' => true,  // 지연반응 → 수용
    ];

    $key = "{$from}_{$to}";
    return isset($positiveTransitions[$key]);
}

/*
 * 사용 예시:
 *
 * curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent21_intervention_execution/persona_system/api/chat.php \
 *   -H "Content-Type: application/json" \
 *   -d '{"message": "네, 알겠습니다. 해볼게요.", "response_type": "R", "ai_enabled": true}'
 *
 * 응답:
 * {
 *   "success": true,
 *   "user_id": 123,
 *   "agent": "agent21_intervention_execution",
 *   "persona": {
 *     "response_type": "A",
 *     "persona_id": "A_P1",
 *     "persona_name": "즉각적 순응자",
 *     "confidence": 0.88,
 *     "tone": "Encouraging"
 *   },
 *   "response": {
 *     "text": "정말 멋져요! 적극적으로 참여해 주셔서 감사해요...",
 *     "source": "template",
 *     "template": "A/enthusiastic_response.txt"
 *   },
 *   "transition": {
 *     "from": "R",
 *     "to": "A",
 *     "type": "positive"
 *   },
 *   "meta": {
 *     "processing_time_ms": 45.23,
 *     "ai_used": true,
 *     "intervention_id": null,
 *     "previous_response_type": "R"
 *   }
 * }
 *
 * 관련 DB 테이블:
 * - mdl_user: id, firstname, lastname (학생 정보)
 * - alt42_interventions: id, type, status (개입 기록, 미래 확장용)
 */
