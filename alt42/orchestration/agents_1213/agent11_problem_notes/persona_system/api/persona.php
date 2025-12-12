<?php
/**
 * Agent11 페르소나 API 엔드포인트
 *
 * 페르소나 조회, 결정, 응답 생성 API
 *
 * @package AugmentedTeacher\Agent11\PersonaSystem\API
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$currentFile = __FILE__;

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../PersonaEngine.php');
require_once(__DIR__ . '/../config.php');

use AugmentedTeacher\Agent11\PersonaSystem\Agent11PersonaEngine;
use AugmentedTeacher\Agent11\PersonaSystem\Agent11Config;

/**
 * JSON 응답 출력
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 에러 응답
 */
function errorResponse($message, $code = 400, $file = null, $line = null) {
    $response = [
        'success' => false,
        'error' => $message
    ];
    if ($file && $line) {
        $response['location'] = basename($file) . ':' . $line;
    }
    jsonResponse($response, $code);
}

// 요청 파싱
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = (int)($_GET['user_id'] ?? $USER->id);

// 권한 확인: 본인 또는 교사/관리자만
if ($userId !== $USER->id) {
    $context = context_system::instance();
    if (!has_capability('moodle/course:manageactivities', $context)) {
        errorResponse('권한이 없습니다', 403, $currentFile, __LINE__);
    }
}

try {
    $engine = new Agent11PersonaEngine(Agent11Config::isDebugMode());

    switch ($action) {
        // =====================================================
        // 현재 페르소나 조회
        // GET /api/persona.php?action=current&user_id=123
        // =====================================================
        case 'current':
            $state = $engine->getStateSync()->getState($userId);
            
            if (!$state) {
                jsonResponse([
                    'success' => true,
                    'persona' => null,
                    'message' => '저장된 페르소나 상태 없음'
                ]);
            }
            
            $characteristics = $engine->getPersonaCharacteristics($state['persona_id']);
            
            jsonResponse([
                'success' => true,
                'persona' => [
                    'id' => $state['persona_id'],
                    'name' => $characteristics['name'] ?? $state['persona_id'],
                    'tone' => $characteristics['tone'] ?? 'Professional',
                    'focus' => $characteristics['focus'] ?? null
                ],
                'version' => $state['version'] ?? 1,
                'updated_at' => $state['timemodified'] ?? null
            ]);
            break;

        // =====================================================
        // 페르소나 결정
        // POST /api/persona.php?action=determine
        // Body: { "error_type": "concept_confusion", "emotional_state": "neutral" }
        // =====================================================
        case 'determine':
            if ($method !== 'POST') {
                errorResponse('POST 요청만 허용', 405, $currentFile, __LINE__);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $context = [
                'error_type' => $input['error_type'] ?? null,
                'emotional_state' => $input['emotional_state'] ?? null,
                'learning_progress' => (int)($input['learning_progress'] ?? 50),
                'force_transition' => !empty($input['force'])
            ];
            
            $personaId = $engine->determinePersona($userId, $context);
            $characteristics = $engine->getPersonaCharacteristics($personaId);
            
            jsonResponse([
                'success' => true,
                'persona' => [
                    'id' => $personaId,
                    'name' => $characteristics['name'],
                    'tone' => $characteristics['tone'],
                    'focus' => $characteristics['focus'],
                    'approach' => $characteristics['approach']
                ],
                'context' => $context
            ]);
            break;

        // =====================================================
        // 문제노트 분석 응답 생성
        // POST /api/persona.php?action=analyze
        // Body: { "problem_id": 123, "error_type": "calculation_mistake", ... }
        // =====================================================
        case 'analyze':
            if ($method !== 'POST') {
                errorResponse('POST 요청만 허용', 405, $currentFile, __LINE__);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $noteData = [
                'problem_id' => $input['problem_id'] ?? null,
                'error_type' => $input['error_type'] ?? null,
                'student_answer' => $input['student_answer'] ?? null,
                'correct_answer' => $input['correct_answer'] ?? null,
                'emotional_state' => $input['emotional_state'] ?? null,
                'learning_progress' => (int)($input['learning_progress'] ?? 50)
            ];
            
            $response = $engine->generateNoteAnalysisResponse($userId, $noteData);
            
            jsonResponse([
                'success' => true,
                'analysis' => $response
            ]);
            break;

        // =====================================================
        // 모든 페르소나 목록
        // GET /api/persona.php?action=list
        // =====================================================
        case 'list':
            $personas = $engine->getPersonaCharacteristics();
            
            jsonResponse([
                'success' => true,
                'personas' => $personas,
                'default' => Agent11Config::get('personas.default')
            ]);
            break;

        // =====================================================
        // 감정 상태 브로드캐스트
        // POST /api/persona.php?action=broadcast_emotion
        // Body: { "emotion": "frustrated", "intensity": 0.8 }
        // =====================================================
        case 'broadcast_emotion':
            if ($method !== 'POST') {
                errorResponse('POST 요청만 허용', 405, $currentFile, __LINE__);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $emotion = $input['emotion'] ?? null;
            $intensity = (float)($input['intensity'] ?? 0.5);
            
            if (!$emotion) {
                errorResponse('emotion 필드 필수', 400, $currentFile, __LINE__);
            }
            
            $engine->broadcastEmotionalState($userId, $emotion, $intensity);
            
            jsonResponse([
                'success' => true,
                'message' => '감정 상태 브로드캐스트 완료',
                'emotion' => $emotion,
                'intensity' => $intensity
            ]);
            break;

        // =====================================================
        // 설정 조회
        // GET /api/persona.php?action=config
        // =====================================================
        case 'config':
            // 민감 정보 제외
            $config = [
                'personas' => Agent11Config::get('personas'),
                'response' => Agent11Config::get('response'),
                'analysis' => Agent11Config::get('analysis')
            ];
            
            jsonResponse([
                'success' => true,
                'config' => $config
            ]);
            break;

        default:
            errorResponse('알 수 없는 action: ' . $action, 400, $currentFile, __LINE__);
    }

} catch (Exception $e) {
    error_log("[Agent11 API ERROR] {$currentFile}:" . __LINE__ . " - " . $e->getMessage());
    errorResponse('서버 오류: ' . $e->getMessage(), 500, $currentFile, __LINE__);
}

/*
 * API 사용 예시:
 *
 * // 현재 페르소나 조회
 * GET /api/persona.php?action=current&user_id=123
 *
 * // 페르소나 결정
 * POST /api/persona.php?action=determine
 * { "error_type": "concept_confusion", "emotional_state": "frustrated" }
 *
 * // 문제노트 분석
 * POST /api/persona.php?action=analyze
 * { "problem_id": 123, "error_type": "calculation_mistake", "student_answer": "25" }
 *
 * // 모든 페르소나 목록
 * GET /api/persona.php?action=list
 *
 * // 감정 상태 브로드캐스트
 * POST /api/persona.php?action=broadcast_emotion
 * { "emotion": "frustrated", "intensity": 0.8 }
 */

