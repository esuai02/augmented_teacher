<?php
/**
 * TTSClient.php - TTS API 통합 클라이언트
 * 
 * 파동함수 상태에 따른 동적 음성 생성
 * 실시간 튜터 Mouth Layer에서 사용
 * 
 * @package     AugmentedTeacher
 * @subpackage  Shared\Lib
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/shared/lib/TTSClient.php
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
 * Class TTSClient
 * 
 * Singleton 패턴으로 구현된 TTS API 클라이언트
 * 
 * 주요 기능:
 * - 텍스트 → 음성 변환 (synthesize)
 * - 파동함수 → 음성 스타일 매핑
 * - 추임새 빠른 생성 (quickFiller)
 * - 음성 캐싱 (자주 사용되는 추임새)
 */
class TTSClient
{
    /** @var TTSClient|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var string OpenAI API 키 */
    private $apiKey;
    
    /** @var string TTS 모델 */
    private $model;
    
    /** @var string 기본 음성 */
    private $defaultVoice;
    
    /** @var float 기본 속도 */
    private $defaultSpeed;
    
    /** @var int 타임아웃 (초) */
    private $timeout;
    
    /** @var array 추임새 캐시 */
    private $fillerCache = [];
    
    /** @var string 캐시 디렉토리 */
    private $cacheDir;

    /**
     * 파동함수 → 음성 스타일 매핑
     * 감정 상태에 따라 목소리 톤과 속도가 변함
     */
    const VOICE_STYLES = [
        'calm' => [
            'voice' => 'alloy',
            'speed' => 0.9,
            'description' => '차분하고 안정적인 톤'
        ],
        'excited' => [
            'voice' => 'nova',
            'speed' => 1.15,
            'description' => '신나고 밝은 톤'
        ],
        'encouraging' => [
            'voice' => 'shimmer',
            'speed' => 1.0,
            'description' => '따뜻하고 격려하는 톤'
        ],
        'serious' => [
            'voice' => 'onyx',
            'speed' => 0.95,
            'description' => '진지하고 무게감 있는 톤'
        ],
        'curious' => [
            'voice' => 'echo',
            'speed' => 1.05,
            'description' => '호기심 어린 톤'
        ],
        'neutral' => [
            'voice' => 'alloy',
            'speed' => 1.0,
            'description' => '중립적인 톤'
        ]
    ];

    /**
     * 추임새 (Back-channeling) 정의
     * 학생 행동에 따른 자연스러운 반응
     */
    const FILLERS = [
        'positive' => ['그렇지~', '좋아', '오호~', '잘했어', '맞아'],
        'thinking' => ['음...', '흠...', '글쎄...'],
        'curious' => ['오?', '어?', '응?', '뭐지?'],
        'warning' => ['잠깐...', '에이~', '아...', '음, 그게...'],
        'agreement' => ['응', '그래', '맞아', '그렇지'],
        'surprise' => ['오!', '와!', '대박!', '진짜?']
    ];

    /**
     * Private 생성자 (Singleton)
     */
    private function __construct()
    {
        // API 키 설정
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        
        // TTS 설정 로드
        if (defined('TTS_CONFIG')) {
            $config = TTS_CONFIG;
            $this->model = $config['model'] ?? 'tts-1';
            $this->defaultVoice = $config['default_voice'] ?? 'alloy';
            $this->defaultSpeed = $config['default_speed'] ?? 1.0;
            $this->timeout = $config['timeout_seconds'] ?? 30;
        } else {
            $this->model = 'tts-1';
            $this->defaultVoice = 'alloy';
            $this->defaultSpeed = 1.0;
            $this->timeout = 30;
        }
        
        // 캐시 디렉토리 설정
        $this->cacheDir = __DIR__ . '/../../tmp/tts_cache/';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Singleton 인스턴스 반환
     * 
     * @return TTSClient
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 텍스트를 음성으로 변환
     * 
     * @param string $text 변환할 텍스트
     * @param array $style ['tone' => 'calm', 'speed' => 1.0, 'emotion' => 'neutral']
     * @return array ['success' => true, 'audio' => binary, 'format' => 'mp3']
     */
    public function synthesize(string $text, array $style = []): array
    {
        if (empty($text)) {
            return [
                'success' => false,
                'error' => '[TTSClient:' . __LINE__ . '] 텍스트가 비어있습니다'
            ];
        }
        
        try {
            // 톤에 따른 음성 스타일 결정
            $tone = $style['tone'] ?? 'neutral';
            $voiceConfig = self::VOICE_STYLES[$tone] ?? self::VOICE_STYLES['neutral'];
            
            // 속도 오버라이드 가능
            $speed = $style['speed'] ?? $voiceConfig['speed'];
            $voice = $style['voice'] ?? $voiceConfig['voice'];
            
            // 속도 범위 제한 (0.25 ~ 4.0)
            $speed = max(0.25, min(4.0, $speed));
            
            $audioData = $this->callTTSAPI($text, $voice, $speed);
            
            return [
                'success' => true,
                'audio' => $audioData,
                'format' => 'mp3',
                'voice' => $voice,
                'speed' => $speed,
                'text_length' => mb_strlen($text)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 양자 상태 기반 음성 생성
     * Brain Layer에서 전달받은 파동함수 값으로 음성 스타일 결정
     * 
     * @param string $text 변환할 텍스트
     * @param array $quantumState ['affect' => 0.7, 'energy' => 0.5, ...]
     * @return array ['success' => true, 'audio' => binary, ...]
     */
    public function synthesizeWithQuantumState(string $text, array $quantumState): array
    {
        $style = $this->mapQuantumStateToStyle($quantumState);
        return $this->synthesize($text, $style);
    }

    /**
     * 추임새 (Back-channeling) 빠른 생성
     * 
     * @param string $type 추임새 타입 ('positive', 'thinking', 'curious', 'warning', 'agreement', 'surprise')
     * @param bool $useCache 캐시 사용 여부
     * @return array ['success' => true, 'audio' => binary, 'text' => '그렇지~']
     */
    public function quickFiller(string $type, bool $useCache = true): array
    {
        $fillers = self::FILLERS[$type] ?? self::FILLERS['agreement'];
        $text = $fillers[array_rand($fillers)];
        
        // 캐시 체크
        $cacheKey = md5($type . '_' . $text);
        if ($useCache && isset($this->fillerCache[$cacheKey])) {
            return [
                'success' => true,
                'audio' => $this->fillerCache[$cacheKey],
                'text' => $text,
                'cached' => true
            ];
        }
        
        // 파일 캐시 체크
        $cacheFile = $this->cacheDir . $cacheKey . '.mp3';
        if ($useCache && file_exists($cacheFile)) {
            $audio = file_get_contents($cacheFile);
            $this->fillerCache[$cacheKey] = $audio;
            return [
                'success' => true,
                'audio' => $audio,
                'text' => $text,
                'cached' => true
            ];
        }
        
        // 추임새용 스타일
        $style = [
            'tone' => $this->getFillerTone($type),
            'speed' => 1.1
        ];
        
        $result = $this->synthesize($text, $style);
        
        // 캐시 저장
        if ($result['success'] && $useCache) {
            $this->fillerCache[$cacheKey] = $result['audio'];
            @file_put_contents($cacheFile, $result['audio']);
        }
        
        $result['text'] = $text;
        return $result;
    }

    /**
     * 학생 행동에 따른 추임새 자동 선택 및 생성
     * 
     * @param string $studentAction 학생 행동 ('correct', 'wrong', 'thinking', 'confused', 'breakthrough')
     * @return array
     */
    public function generateBackchannel(string $studentAction): array
    {
        $typeMapping = [
            'correct' => 'positive',
            'wrong' => 'warning',
            'thinking' => 'thinking',
            'confused' => 'curious',
            'breakthrough' => 'surprise',
            'progress' => 'agreement'
        ];
        
        $type = $typeMapping[$studentAction] ?? 'agreement';
        return $this->quickFiller($type);
    }

    /**
     * Base64 인코딩된 오디오 반환 (브라우저 직접 재생용)
     * 
     * @param string $text 텍스트
     * @param array $style 스타일
     * @return string Base64 인코딩된 오디오 (data URI)
     */
    public function synthesizeBase64(string $text, array $style = []): string
    {
        $result = $this->synthesize($text, $style);
        
        if (!$result['success']) {
            return '';
        }
        
        return 'data:audio/mp3;base64,' . base64_encode($result['audio']);
    }

    /**
     * 사용 가능한 음성 목록 반환
     * 
     * @return array
     */
    public function getAvailableVoices(): array
    {
        return [
            'alloy' => '중성적이고 균형 잡힌 음성',
            'echo' => '남성적이고 따뜻한 음성',
            'fable' => '영국 억양의 남성 음성',
            'onyx' => '깊고 권위 있는 남성 음성',
            'nova' => '젊고 활기찬 여성 음성',
            'shimmer' => '따뜻하고 부드러운 여성 음성'
        ];
    }

    /**
     * 스타일 프리셋 반환
     * 
     * @return array
     */
    public function getStylePresets(): array
    {
        return self::VOICE_STYLES;
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void
    {
        $this->fillerCache = [];
        
        $files = glob($this->cacheDir . '*.mp3');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * OpenAI TTS API 호출
     * 
     * @param string $text 텍스트
     * @param string $voice 음성
     * @param float $speed 속도
     * @return string MP3 바이너리 데이터
     * @throws Exception
     */
    private function callTTSAPI(string $text, string $voice, float $speed): string
    {
        if (empty($this->apiKey)) {
            throw new Exception("[TTSClient:" . __LINE__ . "] API 키가 설정되지 않았습니다");
        }
        
        $data = [
            'model' => $this->model,
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
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("[TTSClient:" . __LINE__ . "] CURL 오류: {$curlError}");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($audioData, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("[TTSClient:" . __LINE__ . "] TTS 생성 실패 (HTTP {$httpCode}): {$errorMsg}");
        }
        
        return $audioData;
    }

    /**
     * 양자 상태를 음성 스타일로 매핑
     * 
     * @param array $quantumState
     * @return array
     */
    private function mapQuantumStateToStyle(array $quantumState): array
    {
        $affect = $quantumState['affect'] ?? 0.5;
        $energy = $quantumState['energy'] ?? 0.5;
        $confusion = $quantumState['confusion'] ?? 0;
        
        // ψ_Affect (감정 파동) 기반 톤 결정
        if ($affect < 0.3) {
            // 부정적 감정 → 따뜻하고 격려하는 톤
            $tone = 'encouraging';
            $speed = 0.95;
        } elseif ($affect > 0.8) {
            // 매우 긍정적 → 신나는 톤
            $tone = 'excited';
            $speed = 1.1;
        } elseif ($confusion > 0.6) {
            // 혼란 상태 → 차분하고 명확한 톤
            $tone = 'calm';
            $speed = 0.9;
        } elseif ($energy > 0.7) {
            // 높은 에너지 → 호기심 어린 톤
            $tone = 'curious';
            $speed = 1.05;
        } else {
            // 기본 → 중립
            $tone = 'neutral';
            $speed = 1.0;
        }
        
        return [
            'tone' => $tone,
            'speed' => $speed
        ];
    }

    /**
     * 추임새 타입에 따른 톤 결정
     */
    private function getFillerTone(string $type): string
    {
        $toneMapping = [
            'positive' => 'excited',
            'thinking' => 'calm',
            'curious' => 'curious',
            'warning' => 'serious',
            'agreement' => 'neutral',
            'surprise' => 'excited'
        ];
        
        return $toneMapping[$type] ?? 'neutral';
    }
}

