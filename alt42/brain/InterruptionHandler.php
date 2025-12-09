<?php
/**
 * InterruptionHandler.php - 튜터 끼어들기 핸들러
 * 
 * 학생이 잘못된 방향으로 가거나 오개념이 감지될 때
 * 자연스럽게 끼어들어 교정하는 시스템
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/InterruptionHandler.php
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 컴포넌트
require_once(__DIR__ . '/../shared/lib/LLMClient.php');
require_once(__DIR__ . '/../shared/lib/TTSClient.php');
require_once(__DIR__ . '/StateCollector.php');
require_once(__DIR__ . '/WavefunctionCalculator.php');

/**
 * Class InterruptionHandler
 * 
 * 긴급 개입(끼어들기) 핸들러
 * 오개념, 심각한 오류, 감정 위기 시 즉시 개입
 */
class InterruptionHandler
{
    /** @var InterruptionHandler|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var LLMClient LLM 클라이언트 */
    private $llmClient;
    
    /** @var TTSClient TTS 클라이언트 */
    private $ttsClient;
    
    /** @var StateCollector 상태 수집기 */
    private $stateCollector;
    
    /** @var WavefunctionCalculator 파동함수 계산기 */
    private $wavefunctionCalc;
    
    /** @var int 마지막 끼어들기 시간 */
    private $lastInterruptionTime = 0;
    
    /** @var int 쿨다운 (초) */
    private $cooldown = 30;
    
    /** @var array 끼어들기 로그 */
    private $interruptionLog = [];

    /**
     * 끼어들기 유형
     */
    const TYPE_MISCONCEPTION = 'misconception';      // 오개념
    const TYPE_CRITICAL_ERROR = 'critical_error';   // 심각한 오류
    const TYPE_EMOTIONAL = 'emotional';             // 감정 위기
    const TYPE_GIVING_UP = 'giving_up';             // 포기 조짐
    const TYPE_OFF_TRACK = 'off_track';             // 완전히 잘못된 방향

    /**
     * 끼어들기 임계값
     */
    const THRESHOLDS = [
        'misconception_confidence' => 0.8,    // 오개념 확신도
        'error_severity' => 0.7,              // 오류 심각도
        'emotional_crisis' => 0.75,           // 감정 위기
        'giving_up_risk' => 0.8,              // 포기 위험
        'off_track_distance' => 0.7           // 잘못된 방향 정도
    ];

    /**
     * 끼어들기 문구 (타입별)
     */
    const INTERRUPTION_PHRASES = [
        self::TYPE_MISCONCEPTION => [
            'openers' => ['잠깐!', '잠깐만~', '어, 잠깐...', '아, 그게...'],
            'connectors' => ['여기서', '이 부분에서', '지금'],
            'tone' => 'serious',
            'speed' => 0.95
        ],
        self::TYPE_CRITICAL_ERROR => [
            'openers' => ['잠깐!', '멈춰봐', '거기서 잠깐!', '스톱!'],
            'connectors' => ['다시 한번', '여기를', '이거'],
            'tone' => 'serious',
            'speed' => 0.9
        ],
        self::TYPE_EMOTIONAL => [
            'openers' => ['잠깐...', '야~', '있잖아...', '저기...'],
            'connectors' => ['괜찮아', '천천히 해도 돼', '같이 해보자'],
            'tone' => 'encouraging',
            'speed' => 0.9
        ],
        self::TYPE_GIVING_UP => [
            'openers' => ['잠깐만!', '기다려봐', '야, 잠깐!', '포기하지 마!'],
            'connectors' => ['거의 다 왔어', '조금만 더', '같이 하자'],
            'tone' => 'encouraging',
            'speed' => 1.0
        ],
        self::TYPE_OFF_TRACK => [
            'openers' => ['어...', '잠깐, 그게 아니라...', '음, 다시...', '아, 여기서...'],
            'connectors' => ['이쪽으로', '다른 방법으로', '처음부터'],
            'tone' => 'calm',
            'speed' => 0.95
        ]
    ];

    /**
     * Private 생성자
     */
    private function __construct()
    {
        $this->llmClient = LLMClient::getInstance();
        $this->ttsClient = TTSClient::getInstance();
        $this->stateCollector = StateCollector::getInstance();
        $this->wavefunctionCalc = WavefunctionCalculator::getInstance();
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
     * 끼어들기 필요 여부 체크
     * 
     * @param int $studentId 학생 ID
     * @param array $context 추가 컨텍스트
     * @return array|null ['should_interrupt' => bool, 'type' => string, 'confidence' => float, 'reason' => string]
     */
    public function shouldInterrupt(int $studentId, array $context = []): ?array
    {
        // 쿨다운 체크
        if (!$this->canInterrupt()) {
            return ['should_interrupt' => false, 'reason' => '쿨다운 중'];
        }
        
        // 상태 수집
        $state = $this->stateCollector->setStudent($studentId)->collectRealtime();
        $wavefunctions = $this->wavefunctionCalc->calculateAll($state);
        
        // 각 유형별 체크
        $checks = [
            $this->checkMisconception($context, $wavefunctions),
            $this->checkCriticalError($context, $wavefunctions),
            $this->checkEmotionalCrisis($state, $wavefunctions),
            $this->checkGivingUp($state, $wavefunctions),
            $this->checkOffTrack($context, $wavefunctions)
        ];
        
        // 가장 높은 점수의 유형 선택
        usort($checks, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        $highest = $checks[0];
        
        // 임계값 이상인지 확인
        $threshold = self::THRESHOLDS[$this->getThresholdKey($highest['type'])] ?? 0.7;
        
        if ($highest['confidence'] >= $threshold) {
            return [
                'should_interrupt' => true,
                'type' => $highest['type'],
                'confidence' => $highest['confidence'],
                'reason' => $highest['reason']
            ];
        }
        
        return ['should_interrupt' => false, 'reason' => '임계값 미달'];
    }

    /**
     * 끼어들기 실행
     * 
     * @param int $studentId 학생 ID
     * @param string $type 끼어들기 유형
     * @param array $context 컨텍스트
     * @return array ['text' => '...', 'audio' => '...', 'type' => '...']
     */
    public function interrupt(int $studentId, string $type, array $context = []): array
    {
        $startTime = microtime(true);
        
        // 1. 오프닝 문구 선택
        $phrases = self::INTERRUPTION_PHRASES[$type] ?? self::INTERRUPTION_PHRASES[self::TYPE_OFF_TRACK];
        $opener = $phrases['openers'][array_rand($phrases['openers'])];
        $connector = $phrases['connectors'][array_rand($phrases['connectors'])];
        
        // 2. LLM으로 교정 메시지 생성
        $prompt = $this->buildInterruptionPrompt($type, $context);
        $correctionText = $this->llmClient->quickResponse($prompt, 'tutor', [
            '끼어들기 유형' => $type,
            '학생 상태' => $context['student_state'] ?? '불명'
        ]);
        
        // 3. 전체 메시지 조합
        $fullText = "{$opener} {$connector}, {$correctionText}";
        
        // 4. TTS 생성
        $ttsResult = $this->ttsClient->synthesize($fullText, [
            'tone' => $phrases['tone'],
            'speed' => $phrases['speed']
        ]);
        
        // 5. 상태 업데이트
        $this->lastInterruptionTime = time();
        $this->logInterruption($studentId, $type, $fullText);
        
        $processingTime = (microtime(true) - $startTime) * 1000;
        
        return [
            'success' => $ttsResult['success'],
            'type' => $type,
            'text' => $fullText,
            'opener' => $opener,
            'correction' => $correctionText,
            'audio' => $ttsResult['success'] ? base64_encode($ttsResult['audio']) : null,
            'audio_format' => 'mp3',
            'tone' => $phrases['tone'],
            'processing_time_ms' => round($processingTime, 2),
            'timestamp' => time()
        ];
    }

    /**
     * 즉시 끼어들기 (체크 + 실행)
     */
    public function immediateInterrupt(int $studentId, array $context = []): ?array
    {
        $check = $this->shouldInterrupt($studentId, $context);
        
        if ($check && $check['should_interrupt']) {
            return $this->interrupt($studentId, $check['type'], $context);
        }
        
        return null;
    }

    /**
     * 오개념 체크
     */
    private function checkMisconception(array $context, array $wavefunctions): array
    {
        $confidence = 0.0;
        $reason = '';
        
        // 컨텍스트에서 오개념 정보 확인
        if (isset($context['misconception_detected'])) {
            $confidence = floatval($context['misconception_confidence'] ?? 0.8);
            $reason = $context['misconception_type'] ?? '오개념 감지';
        }
        
        // 파동함수 기반 추가 판단
        $confusion = $wavefunctions['psi_confusion'] ?? 0;
        if ($confusion > 0.6 && !empty($context['wrong_approach'])) {
            $confidence = max($confidence, 0.5 + $confusion * 0.4);
            $reason = $reason ?: '높은 혼란도 + 잘못된 접근';
        }
        
        return [
            'type' => self::TYPE_MISCONCEPTION,
            'confidence' => $confidence,
            'reason' => $reason
        ];
    }

    /**
     * 심각한 오류 체크
     */
    private function checkCriticalError(array $context, array $wavefunctions): array
    {
        $confidence = 0.0;
        $reason = '';
        
        if (isset($context['critical_error'])) {
            $confidence = floatval($context['error_severity'] ?? 0.7);
            $reason = $context['error_description'] ?? '심각한 오류';
        }
        
        return [
            'type' => self::TYPE_CRITICAL_ERROR,
            'confidence' => $confidence,
            'reason' => $reason
        ];
    }

    /**
     * 감정 위기 체크
     */
    private function checkEmotionalCrisis(array $state, array $wavefunctions): array
    {
        $emotion = $state['emotion'] ?? [];
        $affect = $wavefunctions['psi_affect'] ?? 0.5;
        
        $frustration = $emotion['frustration'] ?? 0;
        $anxiety = $emotion['anxiety'] ?? 0;
        
        // 감정 위기 점수 계산
        $crisisScore = (0.4 * $frustration + 0.3 * $anxiety + 0.3 * (1 - $affect));
        
        $reason = '';
        if ($frustration > 0.7) $reason = '높은 좌절감';
        elseif ($anxiety > 0.7) $reason = '높은 불안';
        elseif ($affect < 0.3) $reason = '부정적 감정 상태';
        
        return [
            'type' => self::TYPE_EMOTIONAL,
            'confidence' => $crisisScore,
            'reason' => $reason
        ];
    }

    /**
     * 포기 조짐 체크
     */
    private function checkGivingUp(array $state, array $wavefunctions): array
    {
        $dropout = $wavefunctions['psi_dropout'] ?? $state['dropout_risk'] ?? 0;
        $energy = $wavefunctions['psi_energy'] ?? 0.5;
        $idleSeconds = $state['behavior']['idle_seconds'] ?? 0;
        
        // 포기 위험 점수
        $givingUpScore = 0.5 * $dropout + 0.3 * (1 - $energy);
        
        // 장시간 비활성
        if ($idleSeconds > 60) {
            $givingUpScore += 0.2;
        }
        
        $reason = '';
        if ($dropout > 0.7) $reason = '높은 이탈 위험';
        elseif ($energy < 0.3) $reason = '에너지 고갈';
        elseif ($idleSeconds > 60) $reason = '장시간 비활성';
        
        return [
            'type' => self::TYPE_GIVING_UP,
            'confidence' => min(1.0, $givingUpScore),
            'reason' => $reason
        ];
    }

    /**
     * 잘못된 방향 체크
     */
    private function checkOffTrack(array $context, array $wavefunctions): array
    {
        $confidence = 0.0;
        $reason = '';
        
        if (isset($context['off_track'])) {
            $confidence = floatval($context['off_track_severity'] ?? 0.6);
            $reason = $context['off_track_reason'] ?? '잘못된 방향';
        }
        
        // 정렬도가 낮으면 추가
        $align = $wavefunctions['psi_align'] ?? 0.5;
        if ($align < 0.3) {
            $confidence = max($confidence, 0.4 + (0.3 - $align));
            $reason = $reason ?: '목표와의 정렬 불일치';
        }
        
        return [
            'type' => self::TYPE_OFF_TRACK,
            'confidence' => $confidence,
            'reason' => $reason
        ];
    }

    /**
     * 끼어들기 프롬프트 구성
     */
    private function buildInterruptionPrompt(string $type, array $context): string
    {
        $prompts = [
            self::TYPE_MISCONCEPTION => 
                "학생이 오개념을 가지고 있습니다. 부드럽게 교정하는 한 문장을 말해주세요. " .
                "직접적으로 '틀렸다'고 하지 말고, 다시 생각해보도록 유도하세요.",
            
            self::TYPE_CRITICAL_ERROR =>
                "학생이 심각한 실수를 하려고 합니다. 간결하게 멈추고 다시 확인하도록 유도하세요.",
            
            self::TYPE_EMOTIONAL =>
                "학생이 힘들어하고 있습니다. 따뜻하게 위로하고 격려하는 한 문장을 해주세요. " .
                "문제 자체보다 학생의 감정을 먼저 챙겨주세요.",
            
            self::TYPE_GIVING_UP =>
                "학생이 포기하려고 합니다. 거의 다 왔다고 격려하고, 함께 하자고 말해주세요. " .
                "짧고 힘이 나는 말로요.",
            
            self::TYPE_OFF_TRACK =>
                "학생이 완전히 잘못된 방향으로 가고 있습니다. 부드럽게 올바른 방향을 알려주세요. " .
                "힌트 수준으로 말해주세요."
        ];
        
        $basePrompt = $prompts[$type] ?? $prompts[self::TYPE_OFF_TRACK];
        
        // 컨텍스트 추가
        if (isset($context['current_problem'])) {
            $basePrompt .= "\n현재 문제: " . $context['current_problem'];
        }
        
        return $basePrompt;
    }

    /**
     * 임계값 키 반환
     */
    private function getThresholdKey(string $type): string
    {
        $map = [
            self::TYPE_MISCONCEPTION => 'misconception_confidence',
            self::TYPE_CRITICAL_ERROR => 'error_severity',
            self::TYPE_EMOTIONAL => 'emotional_crisis',
            self::TYPE_GIVING_UP => 'giving_up_risk',
            self::TYPE_OFF_TRACK => 'off_track_distance'
        ];
        
        return $map[$type] ?? 'misconception_confidence';
    }

    /**
     * 끼어들기 가능 여부
     */
    public function canInterrupt(): bool
    {
        return (time() - $this->lastInterruptionTime) >= $this->cooldown;
    }

    /**
     * 쿨다운 설정
     */
    public function setCooldown(int $seconds): self
    {
        $this->cooldown = max(5, $seconds);
        return $this;
    }

    /**
     * 끼어들기 로그 기록
     */
    private function logInterruption(int $studentId, string $type, string $text): void
    {
        $this->interruptionLog[] = [
            'student_id' => $studentId,
            'type' => $type,
            'text' => $text,
            'timestamp' => time()
        ];
        
        // 최근 20개만 유지
        if (count($this->interruptionLog) > 20) {
            array_shift($this->interruptionLog);
        }
    }

    /**
     * 로그 조회
     */
    public function getLog(): array
    {
        return $this->interruptionLog;
    }

    /**
     * 로그 초기화
     */
    public function clearLog(): self
    {
        $this->interruptionLog = [];
        return $this;
    }
}

