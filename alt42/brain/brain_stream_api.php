<?php
/**
 * brain_stream_api.php - 실시간 스트리밍 API (Server-Sent Events)
 * 
 * LLM 응답을 실시간으로 스트리밍하여 "지연 제로" 경험 제공
 * SSE를 통해 토큰별, 청크별 실시간 전송
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/brain_stream_api.php
 * 
 * 사용법:
 * ```javascript
 * const eventSource = new EventSource('/brain_stream_api.php?action=stream&student_id=123');
 * eventSource.addEventListener('token', (e) => console.log('토큰:', JSON.parse(e.data)));
 * eventSource.addEventListener('chunk', (e) => playAudio(JSON.parse(e.data).audio));
 * eventSource.addEventListener('complete', (e) => eventSource.close());
 * ```
 */

// 출력 버퍼링 비활성화 (스트리밍 필수)
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (@ob_end_flush());
ob_implicit_flush(true);

// CORS 헤더
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Cache-Control');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 컴포넌트 로드
require_once(__DIR__ . '/StreamingPipeline.php');
require_once(__DIR__ . '/QuantumDecisionEngine.php');
require_once(__DIR__ . '/StateCollector.php');

// 액션 파라미터
$action = $_REQUEST['action'] ?? 'stream';

// 학생 ID
$studentId = isset($_REQUEST['student_id']) ? (int)$_REQUEST['student_id'] : (int)$USER->id;

try {
    switch ($action) {
        // =========================================================================
        // 실시간 스트리밍 (SSE)
        // =========================================================================
        case 'stream':
            startSSE();
            
            // 파라미터 추출
            $prompt = $_REQUEST['prompt'] ?? '';
            $systemPrompt = $_REQUEST['system_prompt'] ?? getDefaultSystemPrompt();
            $style = [];
            
            if (isset($_REQUEST['tone'])) {
                $style['tone'] = $_REQUEST['tone'];
            }
            if (isset($_REQUEST['speed'])) {
                $style['speed'] = (float)$_REQUEST['speed'];
            }
            
            // 프롬프트가 없으면 자동 생성 (Brain 기반)
            if (empty($prompt)) {
                $prompt = generateBrainPrompt($studentId);
            }
            
            // 스트리밍 파이프라인 실행
            $pipeline = StreamingPipeline::getInstance();
            $pipeline->setSSECallback(function($eventType, $data) {
                sendSSE($eventType, $data);
            });
            
            // 스트리밍 시작
            foreach ($pipeline->stream($prompt, $systemPrompt, $style) as $event) {
                sendSSE($event['type'], $event);
                
                // 완료 시 종료
                if ($event['type'] === 'complete') {
                    break;
                }
            }
            
            // SSE 종료
            sendSSE('done', ['message' => 'Stream completed']);
            break;
        
        // =========================================================================
        // Brain 기반 실시간 개입 스트리밍
        // =========================================================================
        case 'intervene':
            startSSE();
            
            // Brain 판단
            $engine = QuantumDecisionEngine::getInstance();
            $decision = $engine->decide($studentId);
            
            // 개입 필요 여부 체크
            if (!in_array($decision->type, ['intervene', 'micro_hint'])) {
                sendSSE('skip', [
                    'message' => '개입 불필요',
                    'decision_type' => $decision->type,
                    'collapse_probability' => $decision->collapseProb
                ]);
                sendSSE('done', []);
                break;
            }
            
            // 프롬프트 생성
            $state = StateCollector::getInstance()->setStudent($studentId)->collectRealtime();
            $prompt = buildInterventionPrompt($decision, $state);
            $systemPrompt = getInterventionSystemPrompt($decision);
            
            // 스타일 결정
            $style = $decision->style;
            
            // 결정 정보 전송
            sendSSE('decision', $decision->toArray());
            
            // 스트리밍 시작
            $pipeline = StreamingPipeline::getInstance();
            $pipeline->setSSECallback(function($eventType, $data) {
                sendSSE($eventType, $data);
            });
            
            foreach ($pipeline->stream($prompt, $systemPrompt, $style) as $event) {
                sendSSE($event['type'], $event);
                
                if ($event['type'] === 'complete') {
                    break;
                }
            }
            
            sendSSE('done', ['message' => 'Intervention completed']);
            break;
        
        // =========================================================================
        // 테스트 스트리밍
        // =========================================================================
        case 'test':
            startSSE();
            
            $text = $_REQUEST['text'] ?? '안녕하세요, 저는 AI 튜터입니다. 오늘 수학 공부를 도와드릴게요.';
            
            // 단어 단위로 스트리밍 시뮬레이션
            $words = explode(' ', $text);
            
            sendSSE('start', ['timestamp' => time()]);
            
            foreach ($words as $index => $word) {
                usleep(100000);  // 100ms 딜레이
                
                sendSSE('token', [
                    'content' => $word . ' ',
                    'index' => $index
                ]);
            }
            
            sendSSE('complete', [
                'full_text' => $text,
                'word_count' => count($words)
            ]);
            
            sendSSE('done', []);
            break;
        
        // =========================================================================
        // TTS만 스트리밍
        // =========================================================================
        case 'tts_stream':
            startSSE();
            
            $text = $_REQUEST['text'] ?? '';
            if (empty($text)) {
                sendSSE('error', ['message' => 'text 필요']);
                sendSSE('done', []);
                break;
            }
            
            $tone = $_REQUEST['tone'] ?? 'calm';
            $style = ['tone' => $tone];
            
            // 문장 단위로 분리
            $sentences = preg_split('/(?<=[.!?。！？])\s*/', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            sendSSE('start', ['sentence_count' => count($sentences)]);
            
            $ttsClient = TTSClient::getInstance();
            
            foreach ($sentences as $index => $sentence) {
                $result = $ttsClient->synthesize(trim($sentence), $style);
                
                if ($result['success']) {
                    sendSSE('chunk', [
                        'index' => $index,
                        'text' => trim($sentence),
                        'audio' => base64_encode($result['audio']),
                        'audio_format' => 'mp3'
                    ]);
                }
            }
            
            sendSSE('complete', ['total_sentences' => count($sentences)]);
            sendSSE('done', []);
            break;
        
        // =========================================================================
        // 알 수 없는 액션
        // =========================================================================
        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "알 수 없는 액션: {$action}. 사용 가능: stream, intervene, test, tts_stream"
            ]);
    }
    
} catch (Exception $e) {
    if (headers_sent()) {
        sendSSE('error', ['message' => $e->getMessage()]);
        sendSSE('done', []);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => "[brain_stream_api.php:" . $e->getLine() . "] " . $e->getMessage()
        ]);
    }
}

// =========================================================================
// 헬퍼 함수
// =========================================================================

/**
 * SSE 헤더 시작
 */
function startSSE(): void
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');  // Nginx 버퍼링 비활성화
}

/**
 * SSE 이벤트 전송
 */
function sendSSE(string $event, array $data): void
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

/**
 * 기본 시스템 프롬프트
 */
function getDefaultSystemPrompt(): string
{
    return "당신은 따뜻하고 격려적인 수학 튜터입니다. 
학생의 감정 상태를 고려하여 적절한 힌트를 제공하고, 스스로 깨달을 수 있도록 유도합니다.
짧고 간결하게 응답하세요. 한국어로 답변하세요.";
}

/**
 * 개입용 시스템 프롬프트
 */
function getInterventionSystemPrompt(InterventionDecision $decision): string
{
    $base = getDefaultSystemPrompt();
    
    if ($decision->urgency >= 4) {
        $base .= "\n\n현재 학생이 힘들어하고 있습니다. 따뜻하게 격려해주세요.";
    }
    
    if ($decision->type === 'micro_hint') {
        $base .= "\n\n직접적인 답은 주지 말고, 방향만 살짝 알려주세요.";
    }
    
    return $base;
}

/**
 * Brain 기반 프롬프트 생성
 */
function generateBrainPrompt(int $studentId): string
{
    $state = StateCollector::getInstance()->setStudent($studentId)->collectRealtime();
    
    $prompt = "학생이 수학 문제를 풀고 있습니다. ";
    
    if (($state['emotion']['frustration'] ?? 0) > 0.5) {
        $prompt .= "좌절감을 느끼고 있어서 격려가 필요합니다. ";
    } elseif (($state['behavior']['idle_seconds'] ?? 0) > 30) {
        $prompt .= "30초 이상 멈춰있습니다. ";
    }
    
    $prompt .= "짧게 응답해주세요.";
    
    return $prompt;
}

/**
 * 개입 프롬프트 생성
 */
function buildInterventionPrompt(InterventionDecision $decision, array $state): string
{
    $prompt = "학생이 수학 문제를 풀고 있습니다.\n";
    $prompt .= "현재 상태: " . $decision->reason . "\n";
    $prompt .= "붕괴 확률: " . round($decision->collapseProb * 100) . "%\n";
    
    if ($decision->type === 'micro_hint') {
        $prompt .= "\n문제 해결의 방향만 살짝 알려주는 한 문장 힌트를 주세요.";
    } else {
        $prompt .= "\n적절한 격려와 함께 도움을 주세요.";
    }
    
    return $prompt;
}

