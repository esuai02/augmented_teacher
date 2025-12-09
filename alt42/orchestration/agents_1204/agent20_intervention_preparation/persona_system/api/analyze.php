<?php
/**
 * Agent20 API - 학생 상태 분석 엔드포인트
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/api/analyze.php
 *
 * @package AugmentedTeacher\Agent20\API
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

$currentFile = __FILE__;

try {
    // 엔진 로드
    require_once(__DIR__ . '/../engine/Agent20PersonaEngine.php');

    // 요청 파라미터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_REQUEST;
    }

    $action = $input['action'] ?? 'analyze';
    $userId = intval($input['user_id'] ?? $USER->id);

    // 권한 확인 (관리자 또는 본인만 접근)
    if (!is_siteadmin() && $USER->id != $userId) {
        throw new Exception("권한이 없습니다. [{$currentFile}:" . __LINE__ . "]");
    }

    // 엔진 초기화
    $engine = new Agent20PersonaEngine();

    $result = [];

    switch ($action) {
        case 'analyze':
            // 학생 상태 분석
            $studentState = [
                'emotion' => $input['emotion'] ?? 'neutral',
                'cognitive_load' => floatval($input['cognitive_load'] ?? 0.5),
                'engagement' => floatval($input['engagement'] ?? 0.7),
                'error_rate' => floatval($input['error_rate'] ?? 0),
                'help_requests' => intval($input['help_requests'] ?? 0),
                'time_on_task' => intval($input['time_on_task'] ?? 0),
                'current_activity' => $input['current_activity'] ?? 'learning'
            ];

            $result = $engine->analyzeAndPrepare($userId, $studentState);
            break;

        case 'process':
            // 페르소나 기반 메시지 처리
            $message = $input['message'] ?? '';
            $sessionData = $input['session_data'] ?? [];

            $result = $engine->process($userId, $message, $sessionData);
            break;

        case 'messages':
            // 수신 메시지 처리
            $result = $engine->processIncomingMessages();
            break;

        case 'status':
            // 엔진 상태 확인
            $result = [
                'success' => true,
                'agent_id' => 'agent20',
                'status' => 'active',
                'timestamp' => time()
            ];
            break;

        default:
            throw new Exception("알 수 없는 액션: {$action} [{$currentFile}:" . __LINE__ . "]");
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $currentFile,
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/*
 * API 사용 예시:
 *
 * 1. 학생 상태 분석
 * POST /api/analyze.php
 * {
 *   "action": "analyze",
 *   "user_id": 123,
 *   "emotion": "frustration",
 *   "cognitive_load": 0.8,
 *   "engagement": 0.3
 * }
 *
 * 2. 메시지 처리
 * POST /api/analyze.php
 * {
 *   "action": "process",
 *   "user_id": 123,
 *   "message": "이 문제 어떻게 풀어요?"
 * }
 *
 * 3. 상태 확인
 * GET /api/analyze.php?action=status
 */
