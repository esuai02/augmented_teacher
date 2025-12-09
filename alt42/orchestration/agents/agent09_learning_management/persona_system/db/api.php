<?php
/**
 * Agent09 Persona System DB API
 *
 * 페르소나 시스템 데이터베이스 REST API 엔드포인트
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/db/api.php
 *
 * @package AugmentedTeacher\Agent09\PersonaSystem\DB
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/PersonaDataRepository.php');

// 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 현재 파일 정보
$currentFile = __FILE__;

/**
 * JSON 응답 반환
 */
function sendResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 에러 응답 반환
 */
function sendError(string $message, int $statusCode = 400, string $file = '', int $line = 0): void {
    $error = [
        'success' => false,
        'error' => $message,
        'file' => $file,
        'line' => $line,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    sendResponse($error, $statusCode);
}

// 요청 메서드 및 액션 파싱
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $USER->id;

// 저장소 인스턴스
$repo = new PersonaDataRepository('agent09');

try {
    switch ($action) {

        // ==========================================
        // 페르소나 상태 API
        // ==========================================

        case 'get_persona_state':
            // GET: 현재 활성 페르소나 상태 조회
            $state = $repo->getActivePersonaState($userId);

            if ($state) {
                sendResponse([
                    'success' => true,
                    'data' => $state
                ]);
            } else {
                sendResponse([
                    'success' => true,
                    'data' => null,
                    'message' => '활성 페르소나 상태가 없습니다.'
                ]);
            }
            break;

        case 'save_persona_state':
            // POST: 페르소나 상태 저장
            if ($method !== 'POST') {
                sendError('POST 메서드가 필요합니다.', 405, $currentFile, __LINE__);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['persona_code'])) {
                sendError('persona_code는 필수입니다.', 400, $currentFile, __LINE__);
            }

            $result = $repo->savePersonaState($userId, $input);

            if ($result) {
                sendResponse([
                    'success' => true,
                    'data' => ['id' => $result],
                    'message' => '페르소나 상태가 저장되었습니다.'
                ]);
            } else {
                sendError('저장에 실패했습니다.', 500, $currentFile, __LINE__);
            }
            break;

        // ==========================================
        // 전환 기록 API
        // ==========================================

        case 'log_transition':
            // POST: 페르소나 전환 기록
            if ($method !== 'POST') {
                sendError('POST 메서드가 필요합니다.', 405, $currentFile, __LINE__);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['to_persona'])) {
                sendError('to_persona는 필수입니다.', 400, $currentFile, __LINE__);
            }

            $result = $repo->logPersonaTransition(
                $userId,
                $input['from_persona'] ?? null,
                $input['to_persona'],
                [
                    'trigger_rule_id' => $input['trigger_rule_id'] ?? null,
                    'trigger_reason' => $input['trigger_reason'] ?? null,
                    'confidence_before' => $input['confidence_before'] ?? null,
                    'confidence_after' => $input['confidence_after'] ?? null
                ]
            );

            if ($result) {
                sendResponse([
                    'success' => true,
                    'data' => ['id' => $result],
                    'message' => '전환이 기록되었습니다.'
                ]);
            } else {
                sendError('기록에 실패했습니다.', 500, $currentFile, __LINE__);
            }
            break;

        case 'get_transition_history':
            // GET: 전환 이력 조회
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $history = $repo->getTransitionHistory($userId, $limit);

            sendResponse([
                'success' => true,
                'data' => $history,
                'count' => count($history)
            ]);
            break;

        // ==========================================
        // 개입 기록 API
        // ==========================================

        case 'log_intervention':
            // POST: 개입 기록
            if ($method !== 'POST') {
                sendError('POST 메서드가 필요합니다.', 405, $currentFile, __LINE__);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $requiredFields = ['persona_code', 'intervention_type', 'intervention_level', 'indicator_type'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    sendError("{$field}는 필수입니다.", 400, $currentFile, __LINE__);
                }
            }

            $result = $repo->logIntervention($userId, $input);

            if ($result) {
                sendResponse([
                    'success' => true,
                    'data' => ['id' => $result],
                    'message' => '개입이 기록되었습니다.'
                ]);
            } else {
                sendError('기록에 실패했습니다.', 500, $currentFile, __LINE__);
            }
            break;

        case 'record_response':
            // POST: 개입 응답 기록
            if ($method !== 'POST') {
                sendError('POST 메서드가 필요합니다.', 405, $currentFile, __LINE__);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['intervention_id']) || empty($input['response_content'])) {
                sendError('intervention_id와 response_content는 필수입니다.', 400, $currentFile, __LINE__);
            }

            $result = $repo->recordInterventionResponse(
                (int)$input['intervention_id'],
                $input['response_content'],
                $input['effectiveness_score'] ?? null
            );

            if ($result) {
                sendResponse([
                    'success' => true,
                    'message' => '응답이 기록되었습니다.'
                ]);
            } else {
                sendError('기록에 실패했습니다.', 500, $currentFile, __LINE__);
            }
            break;

        case 'get_interventions':
            // GET: 개입 기록 조회
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $interventions = $repo->getRecentInterventions($userId, $days);

            sendResponse([
                'success' => true,
                'data' => $interventions,
                'count' => count($interventions)
            ]);
            break;

        case 'get_pending_followups':
            // GET: 후속 조치 필요 목록 조회
            $followUps = $repo->getPendingFollowUps();

            sendResponse([
                'success' => true,
                'data' => $followUps,
                'count' => count($followUps)
            ]);
            break;

        // ==========================================
        // 통계 및 분석 API
        // ==========================================

        case 'get_statistics':
            // GET: 페르소나 통계 조회
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $stats = $repo->getPersonaStatistics($userId, $days);

            sendResponse([
                'success' => true,
                'data' => $stats,
                'period_days' => $days
            ]);
            break;

        case 'get_at_risk_students':
            // GET: 위험 학생 목록 조회
            $riskLevel = $_GET['risk_level'] ?? 'high';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

            $students = $repo->getAtRiskStudents($riskLevel, $limit);

            sendResponse([
                'success' => true,
                'data' => $students,
                'count' => count($students),
                'risk_level' => $riskLevel
            ]);
            break;

        // ==========================================
        // 헬스 체크 및 디버그
        // ==========================================

        case 'health':
            // GET: 헬스 체크
            sendResponse([
                'success' => true,
                'status' => 'healthy',
                'agent_id' => 'agent09',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $USER->id,
                'debug' => $repo->getDebugInfo()
            ]);
            break;

        case 'test':
            // GET: 테스트 데이터 생성 (개발용)
            $testUserId = $userId;

            // 테스트 페르소나 상태 저장
            $stateId = $repo->savePersonaState($testUserId, [
                'persona_code' => 'P-SPARSE',
                'persona_series' => 'P',
                'confidence_score' => 0.75,
                'data_density_score' => 0.25,
                'dropout_risk_score' => 0.3,
                'intervention_level' => 'low',
                'recommended_tone' => 'Gentle',
                'recommended_pace' => 'slow'
            ]);

            // 테스트 전환 기록
            $transitionId = $repo->logPersonaTransition(
                $testUserId,
                null,
                'P-SPARSE',
                [
                    'trigger_rule_id' => 'PI_P001_data_sparse',
                    'trigger_reason' => 'data_density_score < 0.3',
                    'confidence_after' => 0.75
                ]
            );

            // 테스트 개입 기록
            $interventionId = $repo->logIntervention($testUserId, [
                'persona_code' => 'P-SPARSE',
                'intervention_type' => 'encouragement',
                'intervention_level' => '주의',
                'indicator_type' => 'composite',
                'message' => '최근 학습 데이터가 부족해요. 오늘 포모도로 한 세션 시작해볼까요?',
                'follow_up_needed' => 1,
                'follow_up_date' => date('Y-m-d', strtotime('+3 days'))
            ]);

            sendResponse([
                'success' => true,
                'message' => '테스트 데이터가 생성되었습니다.',
                'data' => [
                    'persona_state_id' => $stateId,
                    'transition_id' => $transitionId,
                    'intervention_id' => $interventionId
                ]
            ]);
            break;

        default:
            // API 문서
            sendResponse([
                'success' => true,
                'api' => 'Agent09 Persona System DB API',
                'version' => '1.0',
                'endpoints' => [
                    'GET  ?action=health' => '헬스 체크',
                    'GET  ?action=get_persona_state&user_id={id}' => '현재 페르소나 상태 조회',
                    'POST ?action=save_persona_state&user_id={id}' => '페르소나 상태 저장',
                    'POST ?action=log_transition&user_id={id}' => '페르소나 전환 기록',
                    'GET  ?action=get_transition_history&user_id={id}&limit={n}' => '전환 이력 조회',
                    'POST ?action=log_intervention&user_id={id}' => '개입 기록',
                    'POST ?action=record_response' => '개입 응답 기록',
                    'GET  ?action=get_interventions&user_id={id}&days={n}' => '개입 기록 조회',
                    'GET  ?action=get_pending_followups' => '후속 조치 필요 목록',
                    'GET  ?action=get_statistics&user_id={id}&days={n}' => '통계 조회',
                    'GET  ?action=get_at_risk_students&risk_level={level}&limit={n}' => '위험 학생 목록',
                    'GET  ?action=test&user_id={id}' => '테스트 데이터 생성 (개발용)'
                ],
                'file' => $currentFile
            ]);
    }

} catch (Exception $e) {
    sendError($e->getMessage(), 500, $currentFile, __LINE__);
}

/*
 * API 사용 예시:
 *
 * 1. 헬스 체크
 *    GET /api.php?action=health
 *
 * 2. 페르소나 상태 저장
 *    POST /api.php?action=save_persona_state&user_id=123
 *    Body: {
 *        "persona_code": "D-ALERT",
 *        "confidence_score": 0.85,
 *        "dropout_risk_score": 0.65,
 *        "intervention_level": "high",
 *        "recommended_tone": "Warm"
 *    }
 *
 * 3. 개입 기록
 *    POST /api.php?action=log_intervention&user_id=123
 *    Body: {
 *        "persona_code": "D-ALERT",
 *        "intervention_type": "encouragement",
 *        "intervention_level": "경고",
 *        "indicator_type": "attendance",
 *        "message": "최근 출석이 불규칙해요..."
 *    }
 *
 * 4. 위험 학생 조회
 *    GET /api.php?action=get_at_risk_students&risk_level=critical&limit=20
 */
