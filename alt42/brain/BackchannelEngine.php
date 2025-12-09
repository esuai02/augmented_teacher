<?php
/**
 * BackchannelEngine.php - 자연스러운 추임새 엔진
 * 
 * 학생의 행동에 따라 자연스러운 추임새를 생성
 * "음", "그렇지", "오?" 같은 반응으로 실재감 극대화
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/BackchannelEngine.php
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 컴포넌트
require_once(__DIR__ . '/../shared/lib/TTSClient.php');
require_once(__DIR__ . '/StateCollector.php');

/**
 * Class BackchannelEngine
 * 
 * 자연스러운 추임새 생성 엔진
 * 학생 행동 패턴에 따라 적절한 반응 선택
 */
class BackchannelEngine
{
    /** @var BackchannelEngine|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var TTSClient TTS 클라이언트 */
    private $ttsClient;
    
    /** @var StateCollector 상태 수집기 */
    private $stateCollector;
    
    /** @var int 마지막 추임새 시간 */
    private $lastBackchannelTime = 0;
    
    /** @var int 최소 간격 (초) */
    private $minInterval = 10;
    
    /** @var string 마지막 추임새 타입 */
    private $lastType = '';
    
    /** @var array 추임새 히스토리 */
    private $history = [];

    /**
     * 추임새 정의 (상황별)
     */
    const BACKCHANNELS = [
        // 긍정적 반응 (학생이 잘하고 있을 때)
        'positive' => [
            'fillers' => ['그렇지~', '좋아', '오호~', '맞아', '잘했어', '그래그래', '응응'],
            'tone' => 'excited',
            'speed' => 1.1,
            'conditions' => ['correct_answer', 'good_progress', 'breakthrough']
        ],
        
        // 생각 중 (학생이 고민 중일 때)
        'thinking' => [
            'fillers' => ['음...', '흠...', '글쎄...', '어디 보자...'],
            'tone' => 'calm',
            'speed' => 0.9,
            'conditions' => ['paused', 'typing', 'hesitating']
        ],
        
        // 호기심 (학생이 뭔가 시도할 때)
        'curious' => [
            'fillers' => ['오?', '어?', '응?', '뭐지?', '오잉?'],
            'tone' => 'curious',
            'speed' => 1.05,
            'conditions' => ['unexpected_input', 'creative_approach', 'question']
        ],
        
        // 경고/주의 (학생이 실수할 것 같을 때)
        'warning' => [
            'fillers' => ['잠깐...', '에이~', '아...', '음, 그게...', '다시 한번...'],
            'tone' => 'serious',
            'speed' => 0.95,
            'conditions' => ['potential_mistake', 'wrong_direction', 'confusion']
        ],
        
        // 동의 (학생의 진행에 동의)
        'agreement' => [
            'fillers' => ['응', '그래', '맞아', '그렇지', '응응', '좋아좋아'],
            'tone' => 'neutral',
            'speed' => 1.0,
            'conditions' => ['continuing', 'on_track', 'making_progress']
        ],
        
        // 놀람 (예상치 못한 좋은 결과)
        'surprise' => [
            'fillers' => ['오!', '와!', '대박!', '진짜?', '오~', '우와~'],
            'tone' => 'excited',
            'speed' => 1.15,
            'conditions' => ['aha_moment', 'fast_solution', 'creative_solution']
        ],
        
        // 격려 (학생이 힘들어할 때)
        'encourage' => [
            'fillers' => ['괜찮아', '천천히~', '할 수 있어', '조금만 더~', '거의 다 왔어'],
            'tone' => 'encouraging',
            'speed' => 0.95,
            'conditions' => ['frustrated', 'struggling', 'giving_up']
        ],
        
        // 집중 유도 (산만할 때)
        'refocus' => [
            'fillers' => ['자, 다시~', '어디 보자', '여기 봐봐', '이거 봐', '집중~'],
            'tone' => 'calm',
            'speed' => 1.0,
            'conditions' => ['distracted', 'long_pause', 'off_topic']
        ]
    ];

    /**
     * Private 생성자
     */
    private function __construct()
    {
        $this->ttsClient = TTSClient::getInstance();
        $this->stateCollector = StateCollector::getInstance();
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
     * 추임새 생성 (자동 선택)
     * 
     * @param int $studentId 학생 ID
     * @param string|null $studentAction 학생 행동 (null이면 자동 감지)
     * @return array|null ['text' => '...', 'audio' => '...', 'type' => '...']
     */
    public function generate(int $studentId, ?string $studentAction = null): ?array
    {
        // 최소 간격 체크
        if (!$this->canGenerate()) {
            return null;
        }
        
        // 학생 행동 감지 (제공되지 않은 경우)
        if ($studentAction === null) {
            $studentAction = $this->detectStudentAction($studentId);
        }
        
        // 추임새 타입 선택
        $type = $this->selectType($studentAction, $studentId);
        
        // 같은 타입 연속 방지
        if ($type === $this->lastType && rand(0, 100) < 50) {
            $type = $this->getAlternativeType($type);
        }
        
        // 추임새 텍스트 선택
        $text = $this->selectFiller($type);
        
        // TTS 생성
        $config = self::BACKCHANNELS[$type];
        $ttsResult = $this->ttsClient->synthesize($text, [
            'tone' => $config['tone'],
            'speed' => $config['speed']
        ]);
        
        if (!$ttsResult['success']) {
            return null;
        }
        
        // 상태 업데이트
        $this->lastBackchannelTime = time();
        $this->lastType = $type;
        $this->addToHistory($type, $text, $studentAction);
        
        return [
            'text' => $text,
            'audio' => base64_encode($ttsResult['audio']),
            'audio_format' => 'mp3',
            'type' => $type,
            'student_action' => $studentAction,
            'timestamp' => time()
        ];
    }

    /**
     * 특정 타입의 추임새 생성
     */
    public function generateByType(string $type): ?array
    {
        if (!isset(self::BACKCHANNELS[$type])) {
            return null;
        }
        
        $text = $this->selectFiller($type);
        $config = self::BACKCHANNELS[$type];
        
        $ttsResult = $this->ttsClient->synthesize($text, [
            'tone' => $config['tone'],
            'speed' => $config['speed']
        ]);
        
        if (!$ttsResult['success']) {
            return null;
        }
        
        $this->lastBackchannelTime = time();
        $this->lastType = $type;
        
        return [
            'text' => $text,
            'audio' => base64_encode($ttsResult['audio']),
            'audio_format' => 'mp3',
            'type' => $type,
            'timestamp' => time()
        ];
    }

    /**
     * 학생 행동 감지
     */
    private function detectStudentAction(int $studentId): string
    {
        $state = $this->stateCollector->setStudent($studentId)->collectRealtime();
        
        $emotion = $state['emotion'] ?? [];
        $behavior = $state['behavior'] ?? [];
        $cognitive = $state['cognitive'] ?? [];
        
        // 감정 기반 판단
        if (($emotion['frustration'] ?? 0) > 0.6) {
            return 'struggling';
        }
        
        if (($emotion['confidence'] ?? 0.5) > 0.8) {
            return 'good_progress';
        }
        
        // 행동 기반 판단
        $idleSeconds = $behavior['idle_seconds'] ?? 0;
        
        if ($idleSeconds > 30) {
            return 'long_pause';
        }
        
        if ($idleSeconds > 10) {
            return 'paused';
        }
        
        // 인지 기반 판단
        $accuracy = $cognitive['recent_accuracy'] ?? 0.5;
        
        if ($accuracy > 0.8) {
            return 'on_track';
        }
        
        if ($accuracy < 0.3) {
            return 'confusion';
        }
        
        return 'continuing';  // 기본값
    }

    /**
     * 추임새 타입 선택
     */
    private function selectType(string $studentAction, int $studentId): string
    {
        // 행동 → 타입 매핑
        $actionTypeMap = [
            // 긍정적
            'correct_answer' => 'positive',
            'good_progress' => 'positive',
            'breakthrough' => 'surprise',
            'aha_moment' => 'surprise',
            'fast_solution' => 'surprise',
            'creative_solution' => 'surprise',
            
            // 진행 중
            'continuing' => 'agreement',
            'on_track' => 'agreement',
            'making_progress' => 'agreement',
            
            // 생각 중
            'paused' => 'thinking',
            'typing' => 'thinking',
            'hesitating' => 'thinking',
            
            // 호기심
            'unexpected_input' => 'curious',
            'creative_approach' => 'curious',
            'question' => 'curious',
            
            // 경고
            'potential_mistake' => 'warning',
            'wrong_direction' => 'warning',
            'confusion' => 'warning',
            
            // 격려
            'frustrated' => 'encourage',
            'struggling' => 'encourage',
            'giving_up' => 'encourage',
            
            // 집중
            'distracted' => 'refocus',
            'long_pause' => 'refocus',
            'off_topic' => 'refocus'
        ];
        
        return $actionTypeMap[$studentAction] ?? 'agreement';
    }

    /**
     * 대안 타입 선택 (연속 방지)
     */
    private function getAlternativeType(string $currentType): string
    {
        $alternatives = [
            'positive' => 'agreement',
            'thinking' => 'curious',
            'curious' => 'thinking',
            'warning' => 'encourage',
            'agreement' => 'positive',
            'surprise' => 'positive',
            'encourage' => 'agreement',
            'refocus' => 'thinking'
        ];
        
        return $alternatives[$currentType] ?? 'agreement';
    }

    /**
     * 추임새 텍스트 선택 (랜덤 + 히스토리 고려)
     */
    private function selectFiller(string $type): string
    {
        $fillers = self::BACKCHANNELS[$type]['fillers'];
        
        // 최근 사용한 것 제외
        $recentFillers = array_column(
            array_filter($this->history, fn($h) => $h['type'] === $type),
            'text'
        );
        $recentFillers = array_slice($recentFillers, -3);  // 최근 3개
        
        $available = array_diff($fillers, $recentFillers);
        
        if (empty($available)) {
            $available = $fillers;
        }
        
        return $available[array_rand($available)];
    }

    /**
     * 히스토리에 추가
     */
    private function addToHistory(string $type, string $text, string $action): void
    {
        $this->history[] = [
            'type' => $type,
            'text' => $text,
            'action' => $action,
            'timestamp' => time()
        ];
        
        // 최근 20개만 유지
        if (count($this->history) > 20) {
            array_shift($this->history);
        }
    }

    /**
     * 생성 가능 여부 체크
     */
    public function canGenerate(): bool
    {
        return (time() - $this->lastBackchannelTime) >= $this->minInterval;
    }

    /**
     * 최소 간격 설정
     */
    public function setMinInterval(int $seconds): self
    {
        $this->minInterval = max(1, $seconds);
        return $this;
    }

    /**
     * 다음 가능 시간까지 남은 초
     */
    public function getSecondsUntilNext(): int
    {
        $elapsed = time() - $this->lastBackchannelTime;
        return max(0, $this->minInterval - $elapsed);
    }

    /**
     * 히스토리 조회
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * 히스토리 초기화
     */
    public function clearHistory(): self
    {
        $this->history = [];
        return $this;
    }

    /**
     * 사용 가능한 타입 목록
     */
    public function getAvailableTypes(): array
    {
        return array_keys(self::BACKCHANNELS);
    }

    /**
     * 타입 정보 조회
     */
    public function getTypeInfo(string $type): ?array
    {
        return self::BACKCHANNELS[$type] ?? null;
    }
}

