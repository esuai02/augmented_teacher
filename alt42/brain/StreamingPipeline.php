<?php
/**
 * StreamingPipeline.php - 실시간 스트리밍 파이프라인
 * 
 * LLM 응답을 스트리밍으로 받아 실시간으로 TTS 변환 및 전송
 * "지연 제로" 실현을 위한 핵심 컴포넌트
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/StreamingPipeline.php
 * 
 * 아키텍처:
 * ┌─────────────────────────────────────────────────────────────┐
 * │  Brain 판단 → LLM Streaming → 청크별 TTS → SSE 전송         │
 * │     (50ms)      (토큰별)       (문장별)     (즉시)           │
 * └─────────────────────────────────────────────────────────────┘
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 설정
require_once(__DIR__ . '/../config/ai_services.config.php');

/**
 * Class StreamingPipeline
 * 
 * LLM 스트리밍 응답을 처리하고 실시간으로 TTS 변환
 */
class StreamingPipeline
{
    /** @var StreamingPipeline|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var string OpenAI API 키 */
    private $apiKey;
    
    /** @var string 스트리밍 모델 */
    private $model;
    
    /** @var array 설정 */
    private $config;
    
    /** @var callable|null SSE 콜백 */
    private $sseCallback;
    
    /** @var string 현재 버퍼 */
    private $buffer = '';
    
    /** @var int 전송된 청크 수 */
    private $chunkCount = 0;

    /**
     * Private 생성자
     */
    private function __construct()
    {
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $this->model = defined('LLM_CONFIG') ? LLM_CONFIG['realtime_model'] : 'gpt-4o-mini';
        
        $this->config = [
            'chunk_delimiter' => ['.', '!', '?', '。', '！', '？', '\n'],  // 문장 구분자
            'min_chunk_length' => 10,    // 최소 청크 길이
            'max_chunk_length' => 100,   // 최대 청크 길이
            'tts_enabled' => true,       // TTS 활성화 여부
            'tts_voice' => 'alloy',
            'tts_speed' => 1.0
        ];
    }

    /**
     * Singleton 인스턴스 반환
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * SSE 콜백 설정
     * 
     * @param callable $callback function($eventType, $data)
     */
    public function setSSECallback(callable $callback): self
    {
        $this->sseCallback = $callback;
        return $this;
    }

    /**
     * 스트리밍 응답 생성
     * 
     * @param string $prompt 프롬프트
     * @param string $systemPrompt 시스템 프롬프트
     * @param array $style TTS 스타일
     * @return Generator 청크 생성기
     */
    public function stream(string $prompt, string $systemPrompt = '', array $style = []): \Generator
    {
        $this->buffer = '';
        $this->chunkCount = 0;
        
        $messages = [];
        
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];
        
        // 스트리밍 시작 이벤트
        yield $this->createEvent('start', [
            'timestamp' => time(),
            'model' => $this->model
        ]);
        
        // OpenAI 스트리밍 호출
        $ch = $this->initStreamingCurl($messages);
        
        $fullResponse = '';
        
        // 스트리밍 응답 처리
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$fullResponse, $style) {
            $lines = explode("\n", $data);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line) || $line === 'data: [DONE]') {
                    continue;
                }
                
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);
                    $decoded = json_decode($json, true);
                    
                    if (isset($decoded['choices'][0]['delta']['content'])) {
                        $token = $decoded['choices'][0]['delta']['content'];
                        $this->buffer .= $token;
                        $fullResponse .= $token;
                        
                        // 토큰 이벤트 (실시간)
                        $this->emitSSE('token', ['content' => $token]);
                        
                        // 청크 완성 체크
                        $this->processBuffer($style);
                    }
                }
            }
            
            return strlen($data);
        });
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 남은 버퍼 처리
        if (!empty(trim($this->buffer))) {
            $chunk = $this->createChunk($this->buffer, $style);
            yield $chunk;
            $this->emitSSE('chunk', $chunk);
        }
        
        // 완료 이벤트
        yield $this->createEvent('complete', [
            'full_text' => $fullResponse,
            'chunk_count' => $this->chunkCount,
            'timestamp' => time()
        ]);
    }

    /**
     * 버퍼 처리 (청크 분리)
     */
    private function processBuffer(array $style): void
    {
        // 구분자 체크
        foreach ($this->config['chunk_delimiter'] as $delimiter) {
            $pos = strrpos($this->buffer, $delimiter);
            
            if ($pos !== false && $pos >= $this->config['min_chunk_length'] - 1) {
                $chunk = substr($this->buffer, 0, $pos + 1);
                $this->buffer = substr($this->buffer, $pos + 1);
                
                if (strlen(trim($chunk)) >= $this->config['min_chunk_length']) {
                    $chunkData = $this->createChunk($chunk, $style);
                    $this->emitSSE('chunk', $chunkData);
                    $this->chunkCount++;
                }
                
                break;
            }
        }
        
        // 최대 길이 초과 시 강제 분리
        if (strlen($this->buffer) > $this->config['max_chunk_length']) {
            $chunk = substr($this->buffer, 0, $this->config['max_chunk_length']);
            $this->buffer = substr($this->buffer, $this->config['max_chunk_length']);
            
            $chunkData = $this->createChunk($chunk, $style);
            $this->emitSSE('chunk', $chunkData);
            $this->chunkCount++;
        }
    }

    /**
     * 청크 데이터 생성 (TTS 포함)
     */
    private function createChunk(string $text, array $style): array
    {
        $chunk = [
            'type' => 'chunk',
            'index' => $this->chunkCount,
            'text' => trim($text),
            'timestamp' => microtime(true)
        ];
        
        // TTS 생성 (활성화된 경우)
        if ($this->config['tts_enabled'] && !empty(trim($text))) {
            $ttsResult = $this->generateTTS($text, $style);
            if ($ttsResult) {
                $chunk['audio'] = base64_encode($ttsResult);
                $chunk['audio_format'] = 'mp3';
            }
        }
        
        return $chunk;
    }

    /**
     * TTS 생성
     */
    private function generateTTS(string $text, array $style): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }
        
        $voice = $style['voice'] ?? $this->config['tts_voice'];
        $speed = $style['speed'] ?? $this->config['tts_speed'];
        
        $data = [
            'model' => 'tts-1',
            'voice' => $voice,
            'input' => $text,
            'response_format' => 'mp3',
            'speed' => $speed
        ];
        
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200 ? $response : null;
    }

    /**
     * 이벤트 생성
     */
    private function createEvent(string $type, array $data): array
    {
        return array_merge(['type' => $type], $data);
    }

    /**
     * SSE 이벤트 발송
     */
    private function emitSSE(string $eventType, array $data): void
    {
        if ($this->sseCallback) {
            call_user_func($this->sseCallback, $eventType, $data);
        }
    }

    /**
     * 스트리밍 cURL 초기화
     */
    private function initStreamingCurl(array $messages): \CurlHandle
    {
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
            'max_tokens' => 200,
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: text/event-stream'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        return $ch;
    }

    /**
     * 동기식 스트리밍 (전체 응답 반환)
     * 
     * @param string $prompt 프롬프트
     * @param string $systemPrompt 시스템 프롬프트
     * @param array $style TTS 스타일
     * @return array ['text' => '전체 텍스트', 'chunks' => [...], 'audio_chunks' => [...]]
     */
    public function streamSync(string $prompt, string $systemPrompt = '', array $style = []): array
    {
        $result = [
            'text' => '',
            'chunks' => [],
            'audio_chunks' => []
        ];
        
        foreach ($this->stream($prompt, $systemPrompt, $style) as $event) {
            if ($event['type'] === 'chunk') {
                $result['chunks'][] = $event;
                if (isset($event['audio'])) {
                    $result['audio_chunks'][] = $event['audio'];
                }
            } elseif ($event['type'] === 'complete') {
                $result['text'] = $event['full_text'];
            }
        }
        
        return $result;
    }

    /**
     * TTS 활성화/비활성화
     */
    public function setTTSEnabled(bool $enabled): self
    {
        $this->config['tts_enabled'] = $enabled;
        return $this;
    }

    /**
     * 설정 변경
     */
    public function configure(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}

