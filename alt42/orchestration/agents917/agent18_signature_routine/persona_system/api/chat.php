<?php
/**
 * Agent18 Signature Routine - Chat API Endpoint
 * 시그너처 루틴 페르소나 시스템 API
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/api/chat.php
 *
 * API Endpoints:
 * POST /chat.php - 채팅 메시지 처리 및 응답 생성
 * GET /chat.php?action=status - 현재 루틴 분석 상태 조회
 * GET /chat.php?action=routine - 현재 시그너처 루틴 조회
 *
 * Required Tables:
 * - alt42_agent18_routine_patterns: 발견된 루틴 패턴 저장
 * - alt42_agent18_user_profiles: 사용자 루틴 프로필
 * - alt42_learning_sessions: 학습 세션 데이터 (공통 테이블)
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 핸들링 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS 및 JSON 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 엔진 파일들 로드
$basePath = dirname(__DIR__);
require_once($basePath . '/engine/DataContext.php');
require_once($basePath . '/engine/RuleParser.php');
require_once($basePath . '/engine/ConditionEvaluator.php');
require_once($basePath . '/engine/ActionExecutor.php');
require_once($basePath . '/engine/PersonaRuleEngine.php');
require_once($basePath . '/engine/RoutineAnalyzer.php');
require_once($basePath . '/engine/ResponseGenerator.php');

/**
 * 에러 응답 생성
 */
function errorResponse($message, $code = 400, $file = __FILE__, $line = __LINE__) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'debug' => [
            'file' => basename($file),
            'line' => $line
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 성공 응답 생성
 */
function successResponse($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $userId = $USER->id;

    if (!$userId) {
        errorResponse('로그인이 필요합니다.', 401, __FILE__, __LINE__);
    }

    // 요청 메소드에 따른 처리
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : 'chat';

    // DataContext 초기화
    $dataContext = new Agent18\DataContext($userId);

    switch ($method) {
        case 'GET':
            handleGetRequest($action, $dataContext, $userId);
            break;

        case 'POST':
            handlePostRequest($dataContext, $userId);
            break;

        default:
            errorResponse('지원하지 않는 HTTP 메소드입니다.', 405, __FILE__, __LINE__);
    }

} catch (Exception $e) {
    errorResponse(
        '서버 오류가 발생했습니다: ' . $e->getMessage(),
        500,
        $e->getFile(),
        $e->getLine()
    );
}

/**
 * GET 요청 처리
 */
function handleGetRequest($action, $dataContext, $userId) {
    global $DB, $basePath;

    switch ($action) {
        case 'status':
            // 현재 루틴 분석 상태 조회
            $analyzer = new Agent18\RoutineAnalyzer($dataContext);
            $status = $analyzer->getAnalysisStatus();
            successResponse([
                'status' => $status,
                'user_id' => $userId
            ]);
            break;

        case 'routine':
            // 현재 시그너처 루틴 조회
            $profile = $DB->get_record('alt42_agent18_user_profiles', ['userid' => $userId]);
            if ($profile) {
                $routineData = json_decode($profile->routine_data, true);
                successResponse([
                    'routine' => $routineData,
                    'confidence' => $profile->confidence,
                    'last_updated' => $profile->updated_at
                ]);
            } else {
                successResponse([
                    'routine' => null,
                    'message' => '아직 시그너처 루틴이 분석되지 않았습니다.'
                ]);
            }
            break;

        case 'patterns':
            // 발견된 패턴 목록 조회
            $patterns = $DB->get_records('alt42_agent18_routine_patterns',
                ['userid' => $userId],
                'created_at DESC',
                '*',
                0,
                10
            );
            $patternList = [];
            foreach ($patterns as $pattern) {
                $patternList[] = [
                    'id' => $pattern->id,
                    'type' => $pattern->pattern_type,
                    'data' => json_decode($pattern->pattern_data, true),
                    'confidence' => $pattern->confidence,
                    'created_at' => $pattern->created_at
                ];
            }
            successResponse(['patterns' => $patternList]);
            break;

        case 'sessions':
            // 학습 세션 통계 조회
            $stats = getSessionStats($userId);
            successResponse($stats);
            break;

        default:
            errorResponse('알 수 없는 액션입니다: ' . $action, 400, __FILE__, __LINE__);
    }
}

/**
 * POST 요청 처리 (채팅)
 */
function handlePostRequest($dataContext, $userId) {
    global $basePath;

    // 요청 본문 파싱
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // 폼 데이터 처리
        $data = $_POST;
    }

    $message = isset($data['message']) ? trim($data['message']) : '';
    $requestContext = isset($data['context']) ? $data['context'] : null;

    // 메시지 의도 분석
    $intent = analyzeIntent($message);

    // 페르소나 규칙 엔진 실행
    $ruleEngine = new Agent18\PersonaRuleEngine($basePath);
    $ruleResult = $ruleEngine->process($dataContext);

    // 루틴 분석기 실행
    $analyzer = new Agent18\RoutineAnalyzer($dataContext);
    $analysisResult = $analyzer->analyze();

    // 컨텍스트 결정
    $context = determineContext($intent, $analysisResult, $ruleResult, $requestContext);

    // 응답 생성
    $responseGenerator = new Agent18\ResponseGenerator($basePath . '/templates');
    $response = $responseGenerator->generate([
        'context' => $context,
        'tone' => $ruleResult['tone'] ?? 'friendly_exploratory',
        'routine_data' => $analysisResult,
        'recommendation' => $ruleResult['recommendation'] ?? null
    ]);

    // 대화 로그 저장
    saveConversationLog($userId, $message, $response, $context, $intent);

    successResponse([
        'response' => $response['content'],
        'context' => $context,
        'persona' => $ruleResult['persona'] ?? null,
        'analysis' => [
            'intent' => $intent,
            'routine_status' => $analysisResult['status'] ?? 'unknown',
            'confidence' => $analysisResult['confidence'] ?? 0
        ]
    ]);
}

/**
 * 메시지 의도 분석
 */
function analyzeIntent($message) {
    $message = mb_strtolower($message, 'UTF-8');

    $intents = [
        'analyze_routine' => ['분석', '루틴', '패턴', '분석해', '알려줘'],
        'find_golden_time' => ['골든타임', '최적', '언제', '시간대', '베스트'],
        'check_status' => ['상태', '현재', '지금', '어떻게'],
        'get_recommendation' => ['추천', '조언', '어떻게 해야', '도움'],
        'session_length' => ['세션', '길이', '얼마나', '시간'],
        'break_pattern' => ['휴식', '쉬어야', '휴식 패턴'],
        'weekday_pattern' => ['요일', '주간', '무슨 요일'],
        'greeting' => ['안녕', '하이', 'hello', '반가워'],
        'help' => ['도움말', '뭘 할 수', '어떤 기능']
    ];

    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return $intent;
            }
        }
    }

    return 'general';
}

/**
 * 컨텍스트 결정
 */
function determineContext($intent, $analysisResult, $ruleResult, $requestContext = null) {
    // 명시적 컨텍스트가 있으면 우선 사용
    if ($requestContext) {
        return $requestContext;
    }

    // 규칙 엔진의 컨텍스트가 있으면 사용
    if (!empty($ruleResult['context'])) {
        return $ruleResult['context'];
    }

    // 의도 기반 컨텍스트 결정
    $contextMap = [
        'analyze_routine' => 'SR01',
        'find_golden_time' => 'TP02',
        'check_status' => 'SR01',
        'get_recommendation' => 'FO01',
        'session_length' => 'TP01',
        'break_pattern' => 'TP01',
        'weekday_pattern' => 'TP01',
        'greeting' => 'DEFAULT',
        'help' => 'DEFAULT',
        'general' => 'DEFAULT'
    ];

    $baseContext = $contextMap[$intent] ?? 'DEFAULT';

    // 분석 상태에 따른 컨텍스트 조정
    if (isset($analysisResult['signature_routine_found']) && $analysisResult['signature_routine_found']) {
        if ($baseContext === 'SR01') {
            $baseContext = 'SR03'; // 루틴이 이미 발견됨
        }
    } elseif (isset($analysisResult['patterns_found']) && $analysisResult['patterns_found']) {
        if ($baseContext === 'SR01') {
            $baseContext = 'SR02'; // 패턴은 발견됨
        }
    }

    return $baseContext;
}

/**
 * 학습 세션 통계 조회
 */
function getSessionStats($userId) {
    global $DB;

    // 총 세션 수
    $totalSessions = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {alt42_learning_sessions} WHERE userid = ?",
        [$userId]
    );

    // 최근 7일 세션
    $weekAgo = time() - (7 * 24 * 60 * 60);
    $recentSessions = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {alt42_learning_sessions} WHERE userid = ? AND start_time >= ?",
        [$userId, $weekAgo]
    );

    // 평균 세션 길이
    $avgDuration = $DB->get_field_sql(
        "SELECT AVG(duration) FROM {alt42_learning_sessions} WHERE userid = ? AND duration > 0",
        [$userId]
    );

    return [
        'total_sessions' => (int)$totalSessions,
        'recent_sessions' => (int)$recentSessions,
        'avg_duration_minutes' => $avgDuration ? round($avgDuration / 60, 1) : 0,
        'has_enough_data' => $totalSessions >= 5
    ];
}

/**
 * 대화 로그 저장
 */
function saveConversationLog($userId, $message, $response, $context, $intent) {
    global $DB;

    try {
        // 대화 로그 테이블이 있는 경우에만 저장
        $tableExists = $DB->get_manager()->table_exists('alt42_agent18_chat_logs');

        if ($tableExists) {
            $record = new stdClass();
            $record->userid = $userId;
            $record->user_message = $message;
            $record->bot_response = is_array($response) ? $response['content'] : $response;
            $record->context = $context;
            $record->intent = $intent;
            $record->created_at = time();

            $DB->insert_record('alt42_agent18_chat_logs', $record);
        }
    } catch (Exception $e) {
        // 로그 저장 실패는 무시 (핵심 기능이 아님)
        error_log("Agent18 chat log error: " . $e->getMessage());
    }
}
