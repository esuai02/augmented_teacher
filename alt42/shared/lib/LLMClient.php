<?php
/**
 * LLMClient.php - OpenAI API 통합 클라이언트
 * 
 * 모든 LLM 호출을 중앙화하여 일관성 확보
 * 실시간 튜터 Brain Layer에서 사용
 * 
 * @package     AugmentedTeacher
 * @subpackage  Shared\Lib
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/shared/lib/LLMClient.php
 */

// Moodle 환경 체크
if (!defined('MOODLE_INTERNAL')) {
    // 독립 실행 시 config 로드
    if (file_exists(__DIR__ . '/../../config.php')) {
        require_once(__DIR__ . '/../../config.php');
    }
}

// AI 서비스 설정 로드
if (file_exists(__DIR__ . '/../../config/ai_services.config.php')) {
    require_once(__DIR__ . '/../../config/ai_services.config.php');
}

/**
 * Class LLMClient
 * 
 * Singleton 패턴으로 구현된 OpenAI API 클라이언트
 * 
 * 주요 기능:
 * - 일반 완성 요청 (complete)
 * - Streaming 완성 요청 (completeStream) - Phase 3에서 확장
 * - 실시간 튜터용 빠른 응답 (quickResponse)
 * - 페르소나별 프롬프트 관리
 */
class LLMClient
{
    /** @var LLMClient|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var string OpenAI API 키 */
    private $apiKey;
    
    /** @var string 기본 모델 */
    private $defaultModel;
    
    /** @var string 빠른 응답용 모델 */
    private $realtimeModel;
    
    /** @var int 기본 최대 토큰 */
    private $maxTokensDefault;
    
    /** @var int 빠른 응답 최대 토큰 */
    private $maxTokensQuick;
    
    /** @var float 기본 temperature */
    private $temperatureDefault;
    
    /** @var int 타임아웃 (초) */
    private $timeout;
    
    /** @var array 페르소나별 시스템 프롬프트 */
    private $personaPrompts = [];
    
    /** @var array 최근 API 호출 통계 */
    private $stats = [
        'total_calls' => 0,
        'total_tokens' => 0,
        'errors' => 0
    ];

    /**
     * Private 생성자 (Singleton)
     */
    private function __construct()
    {
        // API 키 설정
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        
        // LLM 설정 로드
        if (defined('LLM_CONFIG')) {
            $config = LLM_CONFIG;
            $this->defaultModel = $config['default_model'] ?? 'gpt-4o';
            $this->realtimeModel = $config['realtime_model'] ?? 'gpt-4o-mini';
            $this->maxTokensDefault = $config['max_tokens_default'] ?? 300;
            $this->maxTokensQuick = $config['max_tokens_quick'] ?? 100;
            $this->temperatureDefault = $config['temperature_default'] ?? 0.7;
            $this->timeout = $config['timeout_seconds'] ?? 30;
        } else {
            // 기본값 사용
            $this->defaultModel = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
            $this->realtimeModel = 'gpt-4o-mini';
            $this->maxTokensDefault = 300;
            $this->maxTokensQuick = 100;
            $this->temperatureDefault = 0.7;
            $this->timeout = 30;
        }
        
        // 페르소나 프롬프트 초기화
        $this->initPersonaPrompts();
    }

    /**
     * Singleton 인스턴스 반환
     * 
     * @return LLMClient
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 페르소나별 시스템 프롬프트 초기화
     */
    private function initPersonaPrompts(): void
    {
        $this->personaPrompts = [
            'tutor' => "당신은 따뜻하고 격려적인 수학 튜터입니다. 학생의 감정 상태를 고려하여 적절한 힌트를 제공하고, 
                        스스로 깨달을 수 있도록 유도합니다. 짧고 간결하게 응답하세요.",
            
            'encourager' => "당신은 학생을 격려하는 멘토입니다. 학생이 좌절하거나 포기하려 할 때 
                            따뜻한 말로 용기를 북돋아 주세요. 칭찬을 아끼지 마세요.",
            
            'explainer' => "당신은 명확하고 단계적인 설명을 제공하는 교사입니다. 
                           복잡한 개념을 쉬운 말로 풀어서 설명하세요.",
            
            'challenger' => "당신은 학생에게 적절한 도전을 제시하는 코치입니다. 
                            학생의 수준보다 약간 높은 질문으로 성장을 유도하세요.",
            
            'backchannel' => "당신은 대화 중 자연스러운 추임새를 넣는 역할입니다. 
                             '음', '그렇지', '오?' 같은 짧은 반응만 하세요. 한 단어로 응답하세요."
        ];
    }

    /**
     * 일반 완성 요청
     * 
     * @param array $messages 메시지 배열 [['role' => 'user', 'content' => '...'], ...]
     * @param array $options 옵션 ['model', 'max_tokens', 'temperature']
     * @return array ['content' => '응답', 'usage' => [...], 'success' => true/false]
     */
    public function complete(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? $this->maxTokensDefault;
        $temperature = $options['temperature'] ?? $this->temperatureDefault;
        
        try {
            $result = $this->callAPI([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature
            ]);
            
            $this->stats['total_calls']++;
            if (isset($result['usage']['total_tokens'])) {
                $this->stats['total_tokens'] += $result['usage']['total_tokens'];
            }
            
            return [
                'success' => true,
                'content' => $result['content'],
                'usage' => $result['usage']
            ];
        } catch (Exception $e) {
            $this->stats['errors']++;
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => '',
                'usage' => []
            ];
        }
    }

    /**
     * Streaming 완성 요청 (Phase 3에서 구현)
     * 
     * @param array $messages 메시지 배열
     * @param callable $onChunk 청크 수신 시 콜백
     * @return void
     */
    public function completeStream(array $messages, callable $onChunk): void
    {
        // TODO: Phase 3에서 SSE 기반 streaming 구현
        // 현재는 일반 완성 후 전체 응답 전달
        $result = $this->complete($messages);
        if ($result['success']) {
            $onChunk($result['content'], true); // isComplete = true
        }
    }

    /**
     * 실시간 튜터용 빠른 응답
     * 
     * @param string $prompt 사용자 프롬프트
     * @param string $persona 페르소나 타입 ('tutor', 'encourager', 'explainer', 'challenger', 'backchannel')
     * @param array $context 추가 컨텍스트 정보
     * @return string 응답 텍스트 (실패 시 빈 문자열)
     */
    public function quickResponse(string $prompt, string $persona = 'tutor', array $context = []): string
    {
        $systemPrompt = $this->getPersonaPrompt($persona);
        
        // 컨텍스트가 있으면 시스템 프롬프트에 추가
        if (!empty($context)) {
            $contextStr = "\n\n현재 상황:\n";
            foreach ($context as $key => $value) {
                $contextStr .= "- {$key}: {$value}\n";
            }
            $systemPrompt .= $contextStr;
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $result = $this->complete($messages, [
            'model' => $this->realtimeModel,
            'max_tokens' => $this->maxTokensQuick,
            'temperature' => 0.8
        ]);
        
        return $result['content'] ?? '';
    }

    /**
     * 추임새 생성 (Back-channeling)
     * 
     * @param string $studentAction 학생 행동 설명
     * @return string 추임새 텍스트
     */
    public function generateBackchannel(string $studentAction): string
    {
        $prompt = "학생이 다음 행동을 했습니다: {$studentAction}\n적절한 추임새 한 마디만 응답하세요.";
        return $this->quickResponse($prompt, 'backchannel');
    }

    /**
     * 양자 상태 기반 응답 생성 (Brain Layer 연동)
     * 
     * @param array $quantumState ['affect' => 0.7, 'confusion' => 0.3, ...]
     * @param string $situation 현재 상황
     * @return array ['text' => '응답', 'tone' => 'encouraging', 'speed' => 1.0]
     */
    public function generateQuantumResponse(array $quantumState, string $situation): array
    {
        // 양자 상태에 따른 페르소나 선택
        $persona = $this->selectPersonaByState($quantumState);
        
        // 톤과 속도 결정
        $style = $this->determineStyleByState($quantumState);
        
        // 응답 생성
        $context = [
            '감정 상태' => $this->describeAffect($quantumState['affect'] ?? 0.5),
            '혼란도' => ($quantumState['confusion'] ?? 0) * 100 . '%',
            '에너지' => ($quantumState['energy'] ?? 0.5) * 100 . '%'
        ];
        
        $text = $this->quickResponse($situation, $persona, $context);
        
        return [
            'text' => $text,
            'tone' => $style['tone'],
            'speed' => $style['speed'],
            'emotion' => $style['emotion']
        ];
    }

    /**
     * 페르소나 프롬프트 반환
     * 
     * @param string $persona 페르소나 타입
     * @return string 시스템 프롬프트
     */
    public function getPersonaPrompt(string $persona): string
    {
        return $this->personaPrompts[$persona] ?? $this->personaPrompts['tutor'];
    }

    /**
     * 커스텀 페르소나 등록
     * 
     * @param string $name 페르소나 이름
     * @param string $prompt 시스템 프롬프트
     */
    public function registerPersona(string $name, string $prompt): void
    {
        $this->personaPrompts[$name] = $prompt;
    }

    /**
     * API 호출 통계 반환
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * OpenAI API 호출
     * 
     * @param array $data 요청 데이터
     * @return array 응답 데이터
     * @throws Exception API 오류 시
     */
    private function callAPI(array $data): array
    {
        if (empty($this->apiKey)) {
            throw new Exception("[LLMClient:" . __LINE__ . "] API 키가 설정되지 않았습니다");
        }
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("[LLMClient:" . __LINE__ . "] CURL 오류: {$curlError}");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("[LLMClient:" . __LINE__ . "] API 오류 (HTTP {$httpCode}): {$errorMsg}");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception("[LLMClient:" . __LINE__ . "] 응답 파싱 오류");
        }
        
        return [
            'content' => $result['choices'][0]['message']['content'],
            'usage' => $result['usage'] ?? []
        ];
    }

    /**
     * 양자 상태에 따른 페르소나 선택
     */
    private function selectPersonaByState(array $state): string
    {
        $affect = $state['affect'] ?? 0.5;
        $confusion = $state['confusion'] ?? 0;
        $energy = $state['energy'] ?? 0.5;
        
        if ($affect < 0.3) {
            return 'encourager';  // 부정적 감정 → 격려
        }
        if ($confusion > 0.7) {
            return 'explainer';   // 높은 혼란 → 설명
        }
        if ($energy > 0.7 && $affect > 0.7) {
            return 'challenger';  // 높은 에너지 & 긍정 → 도전
        }
        return 'tutor';
    }

    /**
     * 양자 상태에 따른 스타일 결정
     */
    private function determineStyleByState(array $state): array
    {
        $affect = $state['affect'] ?? 0.5;
        
        if ($affect < 0.3) {
            return ['tone' => 'encouraging', 'speed' => 0.95, 'emotion' => 'warm'];
        }
        if ($affect > 0.8) {
            return ['tone' => 'excited', 'speed' => 1.1, 'emotion' => 'happy'];
        }
        return ['tone' => 'calm', 'speed' => 1.0, 'emotion' => 'neutral'];
    }

    /**
     * 감정 수치를 설명으로 변환
     */
    private function describeAffect(float $affect): string
    {
        if ($affect < 0.2) return '매우 부정적 (좌절/포기)';
        if ($affect < 0.4) return '부정적 (불안/걱정)';
        if ($affect < 0.6) return '중립';
        if ($affect < 0.8) return '긍정적 (집중/관심)';
        return '매우 긍정적 (흥미/열정)';
    }
}

