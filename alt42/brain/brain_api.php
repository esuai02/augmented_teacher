<?php
/**
 * brain_api.php - 실시간 AI 튜터 HTTP API
 * 
 * Brain Layer의 모든 기능을 HTTP API로 노출
 * 프론트엔드에서 실시간 튜터와 통신하는 진입점
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/brain_api.php
 * 
 * 엔드포인트:
 * - POST /brain_api.php?action=start        - 세션 시작
 * - POST /brain_api.php?action=tick         - 실시간 판단 (폴링)
 * - POST /brain_api.php?action=event        - 이벤트 전송
 * - POST /brain_api.php?action=speak        - 수동 발화
 * - GET  /brain_api.php?action=state        - 현재 상태 조회
 * - GET  /brain_api.php?action=debug        - 디버그 정보
 * - POST /brain_api.php?action=stop         - 세션 종료
 */

// CORS 헤더
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 요청 처리 (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Brain Layer 컴포넌트
require_once(__DIR__ . '/RealtimeTutor.php');
require_once(__DIR__ . '/BrainAgentBridge.php');

// 액션 파라미터
$action = $_REQUEST['action'] ?? '';

// 요청 데이터 (JSON 또는 POST)
$rawInput = file_get_contents('php://input');
$inputData = [];

if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $inputData = $decoded;
    }
}

// POST/GET 파라미터 병합
$inputData = array_merge($_REQUEST, $inputData);

// 학생 ID (기본값: 현재 사용자)
$studentId = isset($inputData['student_id']) ? (int)$inputData['student_id'] : (int)$USER->id;

try {
    $tutor = RealtimeTutor::getInstance();
    $bridge = BrainAgentBridge::getInstance();
    
    switch ($action) {
        // =========================================================================
        // 세션 시작
        // =========================================================================
        case 'start':
            $mode = $inputData['mode'] ?? 'guide';
            
            $tutor->start($studentId, ['mode' => $mode]);
            
            respondSuccess([
                'message' => '실시간 튜터 세션 시작됨',
                'student_id' => $studentId,
                'mode' => $mode,
                'system_status' => $bridge->getSystemStatus()
            ]);
            break;
        
        // =========================================================================
        // 실시간 판단 (폴링)
        // =========================================================================
        case 'tick':
            $event = $inputData['event'] ?? [];
            
            // 세션이 없으면 자동 시작
            $state = $tutor->getState();
            if (isset($state['error'])) {
                $tutor->start($studentId);
            }
            
            $result = $tutor->tick($event);
            respondSuccess($result);
            break;
        
        // =========================================================================
        // 이벤트 전송 (에이전트 또는 프론트엔드에서)
        // =========================================================================
        case 'event':
            $eventType = $inputData['event_type'] ?? '';
            $payload = $inputData['payload'] ?? [];
            $fromAgent = (int)($inputData['from_agent'] ?? 0);
            
            if (empty($eventType)) {
                respondError('event_type 필요', 400);
            }
            
            $payload['student_id'] = $studentId;
            
            $result = $bridge->handleAgentEvent($fromAgent, $eventType, $payload);
            respondSuccess($result);
            break;
        
        // =========================================================================
        // 수동 발화
        // =========================================================================
        case 'speak':
            $text = $inputData['text'] ?? '';
            $style = $inputData['style'] ?? [];
            
            if (empty($text)) {
                respondError('text 필요', 400);
            }
            
            // 세션이 없으면 자동 시작
            $state = $tutor->getState();
            if (isset($state['error'])) {
                $tutor->start($studentId);
            }
            
            $result = $tutor->speak($text, $style);
            respondSuccess($result);
            break;
        
        // =========================================================================
        // 현재 상태 조회
        // =========================================================================
        case 'state':
            // 세션이 없으면 기본 상태만 반환
            $state = $tutor->getState();
            
            if (isset($state['error'])) {
                // 세션 없이 상태만 조회
                $collector = StateCollector::getInstance();
                $collector->setStudent($studentId);
                $rawState = $collector->collectRealtime();
                
                $wavefunctionCalc = WavefunctionCalculator::getInstance();
                $wavefunctions = $wavefunctionCalc->calculateAll($rawState);
                
                respondSuccess([
                    'session' => null,
                    'student_state' => $rawState,
                    'wavefunctions' => $wavefunctions,
                    'system_status' => $bridge->getSystemStatus()
                ]);
            } else {
                respondSuccess($state);
            }
            break;
        
        // =========================================================================
        // 디버그 정보
        // =========================================================================
        case 'debug':
            $debug = $bridge->getDebugInfo($studentId);
            respondSuccess($debug);
            break;
        
        // =========================================================================
        // Brain 단일 판단 (세션 없이)
        // =========================================================================
        case 'decide':
            $context = $inputData['context'] ?? [];
            
            $decisionEngine = QuantumDecisionEngine::getInstance();
            $decision = $decisionEngine->decide($studentId, $context);
            
            respondSuccess([
                'decision' => $decision->toArray(),
                'student_id' => $studentId
            ]);
            break;
        
        // =========================================================================
        // 파동함수 조회
        // =========================================================================
        case 'wavefunctions':
            $collector = StateCollector::getInstance();
            $collector->setStudent($studentId);
            $state = $collector->collectRealtime();
            
            $wavefunctionCalc = WavefunctionCalculator::getInstance();
            $wavefunctions = $wavefunctionCalc->calculateAll($state);
            
            respondSuccess([
                'wavefunctions' => $wavefunctions,
                'descriptions' => array_map(
                    fn($name) => $wavefunctionCalc->getWavefunctionDescription($name),
                    $wavefunctionCalc->getWavefunctionNames()
                )
            ]);
            break;
        
        // =========================================================================
        // 모드 변경
        // =========================================================================
        case 'set_mode':
            $mode = $inputData['mode'] ?? '';
            $validModes = ['active', 'guide', 'observe', 'silent'];
            
            if (!in_array($mode, $validModes)) {
                respondError("유효하지 않은 모드: {$mode}. 가능한 값: " . implode(', ', $validModes), 400);
            }
            
            $tutor->setMode($mode);
            respondSuccess(['mode' => $mode, 'message' => '모드 변경됨']);
            break;
        
        // =========================================================================
        // 세션 종료
        // =========================================================================
        case 'stop':
            $summary = $tutor->stop();
            respondSuccess([
                'message' => '세션 종료됨',
                'summary' => $summary
            ]);
            break;
        
        // =========================================================================
        // 시스템 상태
        // =========================================================================
        case 'status':
            respondSuccess([
                'brain_api' => 'online',
                'version' => '1.0.0',
                'system_status' => $bridge->getSystemStatus(),
                'config' => defined('REALTIME_TUTOR_CONFIG') ? REALTIME_TUTOR_CONFIG : null
            ]);
            break;
        
        // =========================================================================
        // TTS 테스트
        // =========================================================================
        case 'test_tts':
            $text = $inputData['text'] ?? '안녕하세요, 저는 AI 튜터입니다.';
            $tone = $inputData['tone'] ?? 'calm';
            
            $ttsClient = TTSClient::getInstance();
            $result = $ttsClient->synthesize($text, ['tone' => $tone]);
            
            if ($result['success']) {
                respondSuccess([
                    'text' => $text,
                    'tone' => $tone,
                    'audio' => base64_encode($result['audio']),
                    'audio_format' => 'mp3'
                ]);
            } else {
                respondError($result['error'] ?? 'TTS 생성 실패', 500);
            }
            break;
        
        // =========================================================================
        // LLM 테스트
        // =========================================================================
        case 'test_llm':
            $prompt = $inputData['prompt'] ?? '학생에게 간단한 격려의 말을 해주세요.';
            $persona = $inputData['persona'] ?? 'tutor';
            
            $llmClient = LLMClient::getInstance();
            $response = $llmClient->quickResponse($prompt, $persona);
            
            respondSuccess([
                'prompt' => $prompt,
                'persona' => $persona,
                'response' => $response
            ]);
            break;
        
        // =========================================================================
        // 알 수 없는 액션
        // =========================================================================
        default:
            respondError("알 수 없는 액션: {$action}. 사용 가능한 액션: start, tick, event, speak, state, debug, decide, wavefunctions, set_mode, stop, status, test_tts, test_llm", 400);
    }
    
} catch (Exception $e) {
    respondError("[brain_api.php:" . $e->getLine() . "] " . $e->getMessage(), 500);
}

// =========================================================================
// 헬퍼 함수
// =========================================================================

/**
 * 성공 응답
 */
function respondSuccess(array $data): void
{
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 에러 응답
 */
function respondError(string $message, int $httpCode = 400): void
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

