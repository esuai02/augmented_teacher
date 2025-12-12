<?php
/**
 * Agent04 인지관성 페르소나 시스템 API
 *
 * 60가지 인지관성(Cognitive Inertia) 패턴 분석 및 조회 API
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 2.0.0
 * @since 2025-12-03
 *
 * 기본 URL: /agents/agent04_inspect_weakpoints/persona_system/api.php
 *
 * 사용법:
 * POST 또는 GET 요청
 * Content-Type: application/json (POST의 경우)
 *
 * Request Body (POST) 또는 Query Params (GET):
 * {
 *   "action": "analyze|get_persona|get_all|get_by_category|...",
 *   "user_id": 123,
 *   "message": "분석할 메시지",
 *   ...
 * }
 */

// 파일 경로 상수
define('CURRENT_FILE', __FILE__);
define('CURRENT_LINE', __LINE__);

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * 에러 응답
 */
function apiError(string $message, int $code = 400, int $line = 0): void
{
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'error_code' => $code,
        'location' => CURRENT_FILE . ':' . ($line ?: CURRENT_LINE),
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 성공 응답
 */
function apiSuccess($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ==========================================
// 메인 처리
// ==========================================

try {
    // 요청 데이터 파싱
    $rawInput = file_get_contents('php://input');
    $request = json_decode($rawInput, true);

    // JSON 파싱 실패시 GET/POST 파라미터 사용
    if (json_last_error() !== JSON_ERROR_NONE) {
        $request = array_merge($_GET, $_POST);
    }

    // 액션 확인
    $action = trim($request['action'] ?? '');
    if (empty($action)) {
        apiError('action 파라미터가 필요합니다. 사용 가능: analyze, get_persona, get_all, get_by_category, get_by_priority, get_solution, get_categories, get_conquest_order, get_history, get_stats, health', 400, __LINE__);
    }

    // 사용자 ID (선택적)
    $userId = intval($request['user_id'] ?? ($USER->id ?? 0));

    // 페르소나 엔진 로드
    require_once __DIR__ . '/Agent04PersonaEngine.php';
    $engine = new Agent04PersonaEngine();

    // 액션별 처리
    switch ($action) {

        // ==========================================
        // 메시지 분석 (핵심 기능)
        // ==========================================
        case 'analyze':
            $message = trim($request['message'] ?? '');

            if (empty($message)) {
                apiError('message 파라미터가 필요합니다.', 400, __LINE__);
            }

            $context = $request['context'] ?? [];
            $result = $engine->analyze($userId, $message, $context);

            apiSuccess($result);
            break;

        // ==========================================
        // 빠른 테스트 (user_id 없이)
        // ==========================================
        case 'quick_test':
            $message = trim($request['message'] ?? '');

            if (empty($message)) {
                apiError('message 파라미터가 필요합니다.', 400, __LINE__);
            }

            $result = $engine->quickTest($message);
            apiSuccess($result);
            break;

        // ==========================================
        // 특정 페르소나 조회
        // ==========================================
        case 'get_persona':
            $personaId = intval($request['id'] ?? $request['persona_id'] ?? 0);

            if ($personaId <= 0 || $personaId > 60) {
                apiError('유효한 id (1-60)가 필요합니다.', 400, __LINE__);
            }

            $persona = $engine->getPersona($personaId);

            if (!$persona) {
                apiError("페르소나 ID {$personaId}를 찾을 수 없습니다.", 404, __LINE__);
            }

            apiSuccess([
                'persona' => $persona,
                'audio_url' => $engine->getSolution($personaId)['audio_url'] ?? null,
            ]);
            break;

        // ==========================================
        // 전체 페르소나 목록
        // ==========================================
        case 'get_all':
            $page = max(1, intval($request['page'] ?? 1));
            $perPage = min(60, max(1, intval($request['per_page'] ?? 60)));

            $allPersonas = $engine->getAllPersonas();
            $total = count($allPersonas);

            // 페이지네이션
            $offset = ($page - 1) * $perPage;
            $pagedPersonas = array_slice($allPersonas, $offset, $perPage, true);

            apiSuccess([
                'personas' => array_values($pagedPersonas),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                ],
            ]);
            break;

        // ==========================================
        // 카테고리별 페르소나 조회
        // ==========================================
        case 'get_by_category':
            $category = trim($request['category'] ?? '');

            if (empty($category)) {
                apiError('category 파라미터가 필요합니다. 예: cognitive_overload, confidence_distortion, mistake_pattern, approach_error, learning_habit, time_pressure, verification_absence, other_障害', 400, __LINE__);
            }

            $personas = $engine->getPersonasByCategory($category);

            apiSuccess([
                'category' => $category,
                'count' => count($personas),
                'personas' => array_values($personas),
            ]);
            break;

        // ==========================================
        // 우선순위별 페르소나 조회
        // ==========================================
        case 'get_by_priority':
            $priority = trim($request['priority'] ?? '');

            if (!in_array($priority, ['high', 'medium', 'low'])) {
                apiError('priority 파라미터가 필요합니다. 가능한 값: high, medium, low', 400, __LINE__);
            }

            $personas = $engine->getPersonasByPriority($priority);

            apiSuccess([
                'priority' => $priority,
                'count' => count($personas),
                'personas' => array_values($personas),
            ]);
            break;

        // ==========================================
        // 솔루션 조회
        // ==========================================
        case 'get_solution':
            $personaId = intval($request['id'] ?? $request['persona_id'] ?? 0);

            if ($personaId <= 0 || $personaId > 60) {
                apiError('유효한 id (1-60)가 필요합니다.', 400, __LINE__);
            }

            $solution = $engine->getSolution($personaId);

            if (!$solution) {
                apiError("페르소나 ID {$personaId}의 솔루션을 찾을 수 없습니다.", 404, __LINE__);
            }

            apiSuccess($solution);
            break;

        // ==========================================
        // 카테고리별 솔루션 목록
        // ==========================================
        case 'get_solutions_by_category':
            $category = trim($request['category'] ?? '');

            if (empty($category)) {
                apiError('category 파라미터가 필요합니다.', 400, __LINE__);
            }

            $solutions = $engine->getSolutionsByCategory($category);

            apiSuccess([
                'category' => $category,
                'count' => count($solutions),
                'solutions' => $solutions,
            ]);
            break;

        // ==========================================
        // 카테고리 목록 조회
        // ==========================================
        case 'get_categories':
            $categories = $engine->getCategories();

            apiSuccess([
                'count' => count($categories),
                'categories' => $categories,
            ]);
            break;

        // ==========================================
        // 추천 정복 순서 조회
        // ==========================================
        case 'get_conquest_order':
            $conquestOrder = $engine->getConquestOrder();

            apiSuccess([
                'conquest_order' => $conquestOrder,
            ]);
            break;

        // ==========================================
        // 사용자 히스토리 조회
        // ==========================================
        case 'get_history':
            if ($userId <= 0) {
                apiError('유효한 user_id가 필요합니다.', 400, __LINE__);
            }

            $limit = min(100, max(1, intval($request['limit'] ?? 10)));
            $history = $engine->getUserHistory($userId, $limit);

            apiSuccess([
                'user_id' => $userId,
                'count' => count($history),
                'history' => $history,
            ]);
            break;

        // ==========================================
        // 사용자 패턴 통계 조회
        // ==========================================
        case 'get_stats':
            if ($userId <= 0) {
                apiError('유효한 user_id가 필요합니다.', 400, __LINE__);
            }

            $stats = $engine->getUserPatternStats($userId);

            apiSuccess([
                'user_id' => $userId,
                'stats' => $stats,
            ]);
            break;

        // ==========================================
        // 엔진 정보 조회
        // ==========================================
        case 'get_engine_info':
            $info = $engine->getEngineInfo();
            apiSuccess($info);
            break;

        // ==========================================
        // 디버그 정보 (개발용)
        // ==========================================
        case 'debug':
            $debug = $engine->getDebugInfo();
            apiSuccess($debug);
            break;

        // ==========================================
        // 헬스 체크
        // ==========================================
        case 'health':
            $health = [
                'status' => 'ok',
                'agent_id' => 'agent04',
                'version' => '2.0.0',
                'engine_loaded' => true,
                'total_personas' => 60,
                'db_connected' => false,
                'php_version' => phpversion(),
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            // DB 연결 테스트
            try {
                $DB->get_record_sql("SELECT 1 AS test");
                $health['db_connected'] = true;
                $health['db_status'] = 'connected';
            } catch (Exception $e) {
                $health['db_status'] = 'error: ' . $e->getMessage();
            }

            apiSuccess($health);
            break;

        // ==========================================
        // API 도움말
        // ==========================================
        case 'help':
            apiSuccess([
                'api_version' => '2.0.0',
                'description' => 'Agent04 인지관성 페르소나 시스템 API',
                'endpoints' => [
                    'analyze' => [
                        'method' => 'POST',
                        'params' => ['message' => '(필수) 분석할 메시지', 'user_id' => '(선택) 사용자 ID'],
                        'description' => '메시지를 분석하여 인지관성 패턴 감지',
                    ],
                    'quick_test' => [
                        'method' => 'GET/POST',
                        'params' => ['message' => '(필수) 테스트 메시지'],
                        'description' => '빠른 테스트 (user_id 없이)',
                    ],
                    'get_persona' => [
                        'method' => 'GET',
                        'params' => ['id' => '(필수) 페르소나 ID (1-60)'],
                        'description' => '특정 페르소나 정보 조회',
                    ],
                    'get_all' => [
                        'method' => 'GET',
                        'params' => ['page' => '(선택) 페이지 번호', 'per_page' => '(선택) 페이지당 개수'],
                        'description' => '전체 페르소나 목록 조회',
                    ],
                    'get_by_category' => [
                        'method' => 'GET',
                        'params' => ['category' => '(필수) 카테고리 키'],
                        'description' => '카테고리별 페르소나 조회',
                    ],
                    'get_by_priority' => [
                        'method' => 'GET',
                        'params' => ['priority' => '(필수) high|medium|low'],
                        'description' => '우선순위별 페르소나 조회',
                    ],
                    'get_solution' => [
                        'method' => 'GET',
                        'params' => ['id' => '(필수) 페르소나 ID (1-60)'],
                        'description' => '특정 페르소나의 솔루션 조회',
                    ],
                    'get_categories' => [
                        'method' => 'GET',
                        'params' => [],
                        'description' => '8개 카테고리 목록 조회',
                    ],
                    'get_conquest_order' => [
                        'method' => 'GET',
                        'params' => [],
                        'description' => '추천 정복 순서 조회',
                    ],
                    'get_history' => [
                        'method' => 'GET',
                        'params' => ['user_id' => '(필수) 사용자 ID', 'limit' => '(선택) 조회 개수'],
                        'description' => '사용자 분석 히스토리 조회',
                    ],
                    'get_stats' => [
                        'method' => 'GET',
                        'params' => ['user_id' => '(필수) 사용자 ID'],
                        'description' => '사용자 패턴 통계 조회',
                    ],
                    'get_engine_info' => [
                        'method' => 'GET',
                        'params' => [],
                        'description' => '엔진 정보 조회',
                    ],
                    'health' => [
                        'method' => 'GET',
                        'params' => [],
                        'description' => 'API 헬스 체크',
                    ],
                ],
                'categories' => [
                    'cognitive_overload' => '인지 과부하 (1-9)',
                    'confidence_distortion' => '자신감 왜곡 (10-17)',
                    'mistake_pattern' => '실수 패턴 (18-25)',
                    'approach_error' => '접근 전략 오류 (26-33)',
                    'learning_habit' => '학습 습관 (34-41)',
                    'time_pressure' => '시간/압박 관리 (42-49)',
                    'verification_absence' => '검증/확인 부재 (50-56)',
                    'other_障害' => '기타 장애 (57-60)',
                ],
            ]);
            break;

        // ==========================================
        // 알 수 없는 액션
        // ==========================================
        default:
            apiError("알 수 없는 action: {$action}. action=help로 API 도움말을 확인하세요.", 400, __LINE__);
    }

} catch (Exception $e) {
    apiError('서버 오류: ' . $e->getMessage(), 500, $e->getLine());
}

/**
 * API 엔드포인트 요약:
 *
 * 핵심 기능:
 * - action=analyze: 메시지 분석하여 인지관성 패턴 감지
 * - action=quick_test: 빠른 테스트 (user_id 없이)
 *
 * 페르소나 조회:
 * - action=get_persona&id=N: 특정 페르소나 조회
 * - action=get_all: 전체 목록 (페이지네이션)
 * - action=get_by_category&category=X: 카테고리별 조회
 * - action=get_by_priority&priority=X: 우선순위별 조회
 *
 * 솔루션 조회:
 * - action=get_solution&id=N: 특정 솔루션 조회
 * - action=get_solutions_by_category&category=X: 카테고리별 솔루션
 *
 * 메타 정보:
 * - action=get_categories: 8개 카테고리 목록
 * - action=get_conquest_order: 추천 정복 순서
 *
 * 사용자 데이터:
 * - action=get_history&user_id=N: 분석 히스토리
 * - action=get_stats&user_id=N: 패턴 통계
 *
 * 시스템:
 * - action=get_engine_info: 엔진 정보
 * - action=debug: 디버그 정보
 * - action=health: 헬스 체크
 * - action=help: API 도움말
 *
 * 관련 DB 테이블:
 * - mdl_at_persona_log: 분석 로그
 */
