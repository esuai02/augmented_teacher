<?php
/**
 * Teacher Persona API Endpoint
 *
 * 선생님 페르소나 기반 피드백 생성 API
 * 학생 메시지를 받아 적절한 선생님 응답 반환
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/api/process.php
 *
 * @package AugmentedTeacher\Agent06\API
 * @version 1.0
 * @author Claude Code
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 현재 파일 정보
$currentFile = __FILE__;

// CORS 및 Content-Type 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 엔진 및 템플릿 로드
require_once(__DIR__ . '/../engine/TeacherPersonaEngine.php');
require_once(__DIR__ . '/../templates/teacher_templates.php');

use AugmentedTeacher\Agent06\Engine\TeacherPersonaEngine;
use AugmentedTeacher\Agent06\Templates\TeacherTemplates;

/**
 * API 응답 함수
 *
 * @param bool $success 성공 여부
 * @param mixed $data 응답 데이터
 * @param string|null $error 에러 메시지
 * @param int $statusCode HTTP 상태 코드
 */
function sendResponse(bool $success, $data = null, ?string $error = null, int $statusCode = 200): void {
    global $currentFile;

    http_response_code($statusCode);

    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'api_version' => '1.0'
    ];

    if ($success) {
        $response['data'] = $data;
    } else {
        $response['error'] = $error;
        $response['debug'] = [
            'file' => $currentFile,
            'line' => debug_backtrace()[0]['line'] ?? 0
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 요청 파라미터 가져오기
 *
 * @return array
 */
function getRequestParams(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $params = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            sendResponse(false, null, 'Invalid JSON: ' . json_last_error_msg(), 400);
        }

        return $params ?? [];
    }

    // Form data 또는 GET 파라미터
    return array_merge($_GET, $_POST);
}

// ============================================
// API 엔드포인트 라우팅
// ============================================

try {
    $params = getRequestParams();
    $action = $params['action'] ?? 'process';

    switch ($action) {

        // --------------------------------------------
        // 1. 피드백 처리 (메인 기능)
        // --------------------------------------------
        case 'process':
            $message = $params['message'] ?? '';
            $userId = $params['user_id'] ?? $USER->id;
            $sessionData = $params['session_data'] ?? [];

            if (empty($message)) {
                sendResponse(false, null, '메시지가 필요합니다.', 400);
            }

            // TeacherPersonaEngine 인스턴스 생성
            $engine = new TeacherPersonaEngine(true); // debug mode

            // 피드백 처리
            $result = $engine->process((int)$userId, $message, $sessionData);

            if (!$result['success']) {
                sendResponse(false, null, $result['error'] ?? '처리 실패', 500);
            }

            sendResponse(true, [
                'feedback' => $result['response'],
                'persona' => [
                    'id' => $result['persona_id'] ?? 'unknown',
                    'name' => $result['persona_name'] ?? '',
                    'situation' => $result['situation'] ?? 'T0'
                ],
                'student_persona' => $result['student_persona'] ?? null,
                'metadata' => [
                    'processing_time_ms' => $result['processing_time'] ?? 0,
                    'emotion_detected' => $result['emotion'] ?? 'neutral'
                ]
            ]);
            break;

        // --------------------------------------------
        // 2. 페르소나 목록 조회
        // --------------------------------------------
        case 'personas':
            $engine = new TeacherPersonaEngine();
            $personas = $engine->getPersonaDefinitions();

            sendResponse(true, [
                'count' => count($personas),
                'personas' => $personas
            ]);
            break;

        // --------------------------------------------
        // 3. 특정 페르소나 조회
        // --------------------------------------------
        case 'persona':
            $personaId = $params['persona_id'] ?? '';

            if (empty($personaId)) {
                sendResponse(false, null, 'persona_id가 필요합니다.', 400);
            }

            $engine = new TeacherPersonaEngine();
            $personas = $engine->getPersonaDefinitions();

            if (!isset($personas[$personaId])) {
                sendResponse(false, null, "페르소나를 찾을 수 없습니다: {$personaId}", 404);
            }

            // 템플릿도 함께 반환
            $templates = TeacherTemplates::getTemplatesByPersona($personaId);

            sendResponse(true, [
                'persona' => $personas[$personaId],
                'templates' => $templates
            ]);
            break;

        // --------------------------------------------
        // 4. 템플릿 렌더링 테스트
        // --------------------------------------------
        case 'render':
            $personaId = $params['persona_id'] ?? 'T0_P2';
            $category = $params['category'] ?? 'greeting';
            $variables = $params['variables'] ?? ['student_name' => '학생'];

            $rendered = TeacherTemplates::getRandomTemplate($personaId, $category, $variables);

            if ($rendered === null) {
                sendResponse(false, null, '템플릿 렌더링 실패', 404);
            }

            sendResponse(true, [
                'persona_id' => $personaId,
                'category' => $category,
                'rendered' => $rendered
            ]);
            break;

        // --------------------------------------------
        // 5. 학생-선생님 매칭 조회
        // --------------------------------------------
        case 'matching':
            $studentPersonaId = $params['student_persona'] ?? '';
            $situation = $params['situation'] ?? 'T0';

            if (empty($studentPersonaId)) {
                sendResponse(false, null, 'student_persona가 필요합니다.', 400);
            }

            // 매칭 규칙 로드
            $rulesFile = __DIR__ . '/../rules.yaml';
            if (!file_exists($rulesFile)) {
                sendResponse(false, null, 'rules.yaml 파일을 찾을 수 없습니다.', 500);
            }

            // 간단한 YAML 파싱 (IRuleParser 사용 가능)
            require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/YamlRuleParser.php');
            $parser = new \AugmentedTeacher\PersonaEngine\Impl\YamlRuleParser();
            $rules = $parser->parse($rulesFile);

            $matchingRules = $rules['matching_rules'] ?? [];
            $recommendedPersona = 'T0_P2'; // 기본값

            // 매칭 규칙 적용
            foreach ($matchingRules as $rule) {
                if (isset($rule['student_persona']) && $rule['student_persona'] === $studentPersonaId) {
                    if (isset($rule['situations'][$situation])) {
                        $recommendedPersona = $rule['situations'][$situation]['primary'] ?? 'T0_P2';
                        break;
                    }
                }
            }

            sendResponse(true, [
                'student_persona' => $studentPersonaId,
                'situation' => $situation,
                'recommended_teacher_persona' => $recommendedPersona
            ]);
            break;

        // --------------------------------------------
        // 6. 상태 조회
        // --------------------------------------------
        case 'state':
            $userId = $params['user_id'] ?? $USER->id;

            $state = $DB->get_record('at_agent_persona_state', [
                'userid' => (int)$userId,
                'agent_id' => '06'
            ]);

            if (!$state) {
                sendResponse(true, [
                    'exists' => false,
                    'message' => '저장된 상태가 없습니다.'
                ]);
            }

            sendResponse(true, [
                'exists' => true,
                'persona_id' => $state->persona_id,
                'state_data' => json_decode($state->state_data, true),
                'version' => $state->version,
                'last_modified' => date('Y-m-d H:i:s', $state->timemodified)
            ]);
            break;

        // --------------------------------------------
        // 7. 상태 초기화
        // --------------------------------------------
        case 'reset':
            $userId = $params['user_id'] ?? $USER->id;

            $deleted = $DB->delete_records('at_agent_persona_state', [
                'userid' => (int)$userId,
                'agent_id' => '06'
            ]);

            sendResponse(true, [
                'reset' => true,
                'user_id' => (int)$userId
            ]);
            break;

        // --------------------------------------------
        // 8. API 상태 확인
        // --------------------------------------------
        case 'health':
            // DB 연결 확인
            $dbOk = false;
            try {
                $DB->get_record('user', ['id' => $USER->id]);
                $dbOk = true;
            } catch (Exception $e) {
                $dbOk = false;
            }

            // 필수 파일 확인
            $requiredFiles = [
                'engine' => __DIR__ . '/../engine/TeacherPersonaEngine.php',
                'templates' => __DIR__ . '/../templates/teacher_templates.php',
                'rules' => __DIR__ . '/../rules.yaml',
                'personas' => __DIR__ . '/../personas.md'
            ];

            $fileStatus = [];
            foreach ($requiredFiles as $name => $path) {
                $fileStatus[$name] = file_exists($path);
            }

            sendResponse(true, [
                'status' => 'ok',
                'database' => $dbOk,
                'files' => $fileStatus,
                'user' => [
                    'id' => $USER->id,
                    'logged_in' => isloggedin()
                ],
                'server_time' => date('Y-m-d H:i:s')
            ]);
            break;

        // --------------------------------------------
        // 알 수 없는 액션
        // --------------------------------------------
        default:
            sendResponse(false, null, "알 수 없는 액션: {$action}", 400);
    }

} catch (Exception $e) {
    error_log("[Agent06 API ERROR] {$currentFile}:" . __LINE__ . " - " . $e->getMessage());
    sendResponse(false, null, '서버 오류: ' . $e->getMessage(), 500);
}

/*
 * API 엔드포인트 요약:
 *
 * 1. POST ?action=process
 *    - message: 학생 메시지 (필수)
 *    - user_id: 사용자 ID (선택, 기본값: 현재 로그인 사용자)
 *    - session_data: 세션 데이터 (선택)
 *    → 선생님 피드백 응답 반환
 *
 * 2. GET ?action=personas
 *    → 전체 페르소나 목록 반환
 *
 * 3. GET ?action=persona&persona_id=T1_P1
 *    → 특정 페르소나 정보 및 템플릿 반환
 *
 * 4. GET ?action=render&persona_id=T1_P1&category=praise
 *    → 템플릿 렌더링 테스트
 *
 * 5. GET ?action=matching&student_persona=S1_P1&situation=T1
 *    → 학생-선생님 페르소나 매칭 조회
 *
 * 6. GET ?action=state&user_id=123
 *    → 사용자의 현재 페르소나 상태 조회
 *
 * 7. POST ?action=reset&user_id=123
 *    → 사용자의 페르소나 상태 초기화
 *
 * 8. GET ?action=health
 *    → API 상태 확인
 *
 * 관련 DB 테이블:
 * - at_agent_persona_state (페르소나 상태)
 * - at_agent_messages (에이전트 메시지)
 * - mdl_user (사용자 정보)
 */
