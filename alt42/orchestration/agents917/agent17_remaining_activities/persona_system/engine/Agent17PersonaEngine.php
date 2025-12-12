<?php
/**
 * Agent17PersonaEngine - 잔여 활동 조정 에이전트 페르소나 엔진
 *
 * Agent17(Remaining Activities)의 페르소나 식별 및 응답 생성 엔진
 * AbstractPersonaEngine을 상속하여 agent17 전용 로직 구현
 *
 * 핵심 전략:
 * - R1: 질문하기 (Questioning)
 * - R2: 도제학습 전환 (Apprenticeship Transition)
 * - R3: 활동축소 (Activity Reduction)
 * - R4: 하이튜터링 (High Tutoring)
 * - R5: 징검다리 활동 (Stepping Stone Activity)
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 및 인터페이스 로드
$corePath = dirname(__DIR__, 3) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IRuleParser.php');
require_once($corePath . 'IConditionEvaluator.php');
require_once($corePath . 'IActionExecutor.php');
require_once($corePath . 'IDataContext.php');
require_once($corePath . 'IResponseGenerator.php');
require_once($corePath . 'AbstractPersonaEngine.php');

// namespace 사용
use AugmentedTeacher\PersonaEngine\Core\AbstractPersonaEngine;
use AugmentedTeacher\PersonaEngine\Core\IRuleParser;
use AugmentedTeacher\PersonaEngine\Core\IConditionEvaluator;
use AugmentedTeacher\PersonaEngine\Core\IActionExecutor;
use AugmentedTeacher\PersonaEngine\Core\IDataContext;
use AugmentedTeacher\PersonaEngine\Core\IResponseGenerator;

// 공통 구현체 로드 (존재하는 경우)
$implPath = dirname(__DIR__, 3) . '/ontology_engineering/persona_engine/impl/';
if (file_exists($implPath . 'BaseRuleParser.php')) {
    require_once($implPath . 'BaseRuleParser.php');
}
if (file_exists($implPath . 'BaseConditionEvaluator.php')) {
    require_once($implPath . 'BaseConditionEvaluator.php');
}
if (file_exists($implPath . 'BaseActionExecutor.php')) {
    require_once($implPath . 'BaseActionExecutor.php');
}
if (file_exists($implPath . 'BaseDataContext.php')) {
    require_once($implPath . 'BaseDataContext.php');
}
if (file_exists($implPath . 'BaseResponseGenerator.php')) {
    require_once($implPath . 'BaseResponseGenerator.php');
}

// Agent17 전용 Fallback 구현체 로드
$fallbackPath = __DIR__ . '/fallback/';
require_once($fallbackPath . 'Agent17RuleParser.php');
require_once($fallbackPath . 'Agent17ConditionEvaluator.php');
require_once($fallbackPath . 'Agent17ActionExecutor.php');
require_once($fallbackPath . 'Agent17DataContext.php');
require_once($fallbackPath . 'Agent17ResponseGenerator.php');

class Agent17PersonaEngine extends AbstractPersonaEngine {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 상황 코드 - 학습 진행 상태 기반 */
    protected $situationCodes = [
        'R1' => '원활_진행',     // Smooth Progress - 학습 리듬이 양호한 상태
        'R2' => '적절_진행',     // Adequate Progress - 약간의 조정 필요
        'R3' => '지연_진행',     // Delayed Progress - 리듬 회복 필요
        'R4' => '정체_진행',     // Stagnant Progress - 적극적 개입 필요
        'R5' => '리듬_붕괴'      // Rhythm Breakdown - 긴급 재조정 필요
    ];

    /** @var array 전략 코드 */
    protected $strategyCodes = [
        'ST1' => '질문하기',           // Questioning
        'ST2' => '도제학습_전환',       // Apprenticeship Transition
        'ST3' => '활동축소',           // Activity Reduction
        'ST4' => '하이튜터링',         // High Tutoring
        'ST5' => '징검다리_활동'        // Stepping Stone Activity
    ];

    /** @var array 페르소나 캐시 */
    private $personaCache = [];

    /** @var string 에이전트 기본 경로 */
    protected $agentBasePath;

    /**
     * 생성자
     *
     * @param array $config 설정
     */
    public function __construct(array $config = []) {
        $defaultConfig = [
            'debug_mode' => false,
            'log_enabled' => true,
            'cache_enabled' => true,
            'ai_enabled' => false,
            'ai_threshold' => 0.7,
            'rhythm_check_interval' => 300, // 5분
            'max_retry_attempts' => 3
        ];

        // 에이전트 기본 경로 설정
        $this->agentBasePath = dirname(__DIR__);

        parent::__construct('agent17', array_merge($defaultConfig, $config));
    }

    /**
     * 컴포넌트 초기화
     */
    protected function initializeComponents(): void {
        try {
            // 규칙 파서 초기화
            if (class_exists('BaseRuleParser')) {
                $this->ruleParser = new BaseRuleParser();
            } else {
                $this->ruleParser = new Agent17RuleParser();
            }

            // 조건 평가기 초기화
            if (class_exists('BaseConditionEvaluator')) {
                $this->conditionEvaluator = new BaseConditionEvaluator();
            } else {
                $this->conditionEvaluator = new Agent17ConditionEvaluator();
            }

            // 액션 실행기 초기화
            if (class_exists('BaseActionExecutor')) {
                $this->actionExecutor = new BaseActionExecutor();
            } else {
                $this->actionExecutor = new Agent17ActionExecutor();
            }

            // 데이터 컨텍스트 초기화
            if (class_exists('BaseDataContext')) {
                $this->dataContext = new BaseDataContext($this->agentId);
            } else {
                $this->dataContext = new Agent17DataContext();
            }

            // 응답 생성기 초기화
            if (class_exists('BaseResponseGenerator')) {
                $this->responseGenerator = new BaseResponseGenerator($this->agentBasePath . '/templates');
            } else {
                $this->responseGenerator = new Agent17ResponseGenerator();
            }

            // 규칙 로드
            $this->loadRules();

            // 페르소나 로드
            $this->personas = $this->loadPersonas();

        } catch (Exception $e) {
            error_log("[Agent17PersonaEngine] {$this->currentFile}:" . __LINE__ .
                " - 컴포넌트 초기화 실패: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Agent17 전용 페르소나 로드
     *
     * @return array 페르소나 배열
     */
    protected function loadPersonas(): array {
        if (!empty($this->personaCache)) {
            return $this->personaCache;
        }

        // 페르소나 정의 - 상황(R1-R5) x 전략(ST1-ST5) 조합
        $personas = [
            // R1: 원활 진행 상태 페르소나
            'R1_P1' => [
                'id' => 'R1_P1',
                'name' => '격려하는 안내자',
                'alias' => 'Encouraging Guide',
                'situation' => 'R1',
                'strategy' => 'ST1',
                'psychological' => '학습에 몰입하여 긍정적인 상태',
                'behavioral' => '적극적으로 질문하고 참여함',
                'typical_utterance' => '잘하고 있어요! 다음 단계로 넘어갈 준비가 되셨나요?',
                'response_strategy' => '격려와 함께 다음 목표 제시'
            ],
            'R1_P2' => [
                'id' => 'R1_P2',
                'name' => '도전 제안자',
                'alias' => 'Challenge Proposer',
                'situation' => 'R1',
                'strategy' => 'ST5',
                'psychological' => '자신감이 높고 새로운 도전에 열린 상태',
                'behavioral' => '빠르게 과제를 완료하고 더 원함',
                'typical_utterance' => '좀 더 도전적인 활동을 해볼까요?',
                'response_strategy' => '심화 학습 경로 제안'
            ],

            // R2: 적절 진행 상태 페르소나
            'R2_P1' => [
                'id' => 'R2_P1',
                'name' => '세심한 조력자',
                'alias' => 'Attentive Helper',
                'situation' => 'R2',
                'strategy' => 'ST1',
                'psychological' => '학습에 집중하나 간헐적 어려움 느낌',
                'behavioral' => '때때로 멈추거나 확인 요청',
                'typical_utterance' => '여기서 어떤 부분이 헷갈리셨나요?',
                'response_strategy' => '구체적 질문으로 어려움 파악'
            ],
            'R2_P2' => [
                'id' => 'R2_P2',
                'name' => '리듬 조정자',
                'alias' => 'Rhythm Adjuster',
                'situation' => 'R2',
                'strategy' => 'ST3',
                'psychological' => '부담을 느끼지만 지속 의지 있음',
                'behavioral' => '속도가 느려지거나 쉬는 시간 증가',
                'typical_utterance' => '잠시 핵심 내용만 정리해볼까요?',
                'response_strategy' => '활동 범위 조정 제안'
            ],

            // R3: 지연 진행 상태 페르소나
            'R3_P1' => [
                'id' => 'R3_P1',
                'name' => '인내심 있는 멘토',
                'alias' => 'Patient Mentor',
                'situation' => 'R3',
                'strategy' => 'ST2',
                'psychological' => '좌절감을 느끼지만 포기하지 않음',
                'behavioral' => '같은 부분에서 반복적으로 막힘',
                'typical_utterance' => '함께 천천히 해볼게요. 제가 먼저 보여드릴게요.',
                'response_strategy' => '도제학습 방식으로 시범 제시'
            ],
            'R3_P2' => [
                'id' => 'R3_P2',
                'name' => '단계 분해자',
                'alias' => 'Step Decomposer',
                'situation' => 'R3',
                'strategy' => 'ST3',
                'psychological' => '압도당한 느낌, 무엇부터 해야 할지 모름',
                'behavioral' => '과제 회피하거나 다른 활동으로 전환',
                'typical_utterance' => '이 부분만 먼저 집중해볼까요?',
                'response_strategy' => '과제 단순화 및 우선순위 재설정'
            ],
            'R3_P3' => [
                'id' => 'R3_P3',
                'name' => '징검다리 안내자',
                'alias' => 'Stepping Stone Guide',
                'situation' => 'R3',
                'strategy' => 'ST5',
                'psychological' => '현재 과제와 능력 사이 간극 인식',
                'behavioral' => '시도하지만 완성도 낮음',
                'typical_utterance' => '이 준비 활동을 먼저 해보면 어떨까요?',
                'response_strategy' => '선수 학습 활동 제안'
            ],

            // R4: 정체 진행 상태 페르소나
            'R4_P1' => [
                'id' => 'R4_P1',
                'name' => '집중 튜터',
                'alias' => 'Intensive Tutor',
                'situation' => 'R4',
                'strategy' => 'ST4',
                'psychological' => '학습 의욕 저하, 무기력함',
                'behavioral' => '최소한의 상호작용만 하거나 무반응',
                'typical_utterance' => '제가 옆에서 하나하나 도와드릴게요.',
                'response_strategy' => '1:1 집중 튜터링 제공'
            ],
            'R4_P2' => [
                'id' => 'R4_P2',
                'name' => '동기 촉진자',
                'alias' => 'Motivation Facilitator',
                'situation' => 'R4',
                'strategy' => 'ST2',
                'psychological' => '자신감 상실, 실패에 대한 두려움',
                'behavioral' => '새로운 시도 회피, 안전한 것만 선택',
                'typical_utterance' => '제가 하는 것을 보면서 따라해 보세요.',
                'response_strategy' => '성공 경험을 통한 자신감 회복'
            ],
            'R4_P3' => [
                'id' => 'R4_P3',
                'name' => '목표 재설정자',
                'alias' => 'Goal Resetter',
                'situation' => 'R4',
                'strategy' => 'ST3',
                'psychological' => '목표와 현실의 괴리로 좌절',
                'behavioral' => '계획 수정 없이 같은 방식 반복',
                'typical_utterance' => '지금 상황에 맞게 목표를 조정해볼까요?',
                'response_strategy' => '현실적 목표 재설정'
            ],

            // R5: 리듬 붕괴 상태 페르소나
            'R5_P1' => [
                'id' => 'R5_P1',
                'name' => '위기 대응자',
                'alias' => 'Crisis Responder',
                'situation' => 'R5',
                'strategy' => 'ST4',
                'psychological' => '극심한 스트레스, 포기 직전',
                'behavioral' => '학습 완전 중단 또는 이탈 시도',
                'typical_utterance' => '잠시 쉬어도 괜찮아요. 천천히 다시 시작해봐요.',
                'response_strategy' => '긴급 지원 및 정서적 안정화'
            ],
            'R5_P2' => [
                'id' => 'R5_P2',
                'name' => '재시작 도우미',
                'alias' => 'Restart Helper',
                'situation' => 'R5',
                'strategy' => 'ST5',
                'psychological' => '완전히 막힌 느낌, 어디서부터 해야 할지 모름',
                'behavioral' => '아무것도 하지 않거나 무작위 행동',
                'typical_utterance' => '가장 기본적인 것부터 다시 시작해볼까요?',
                'response_strategy' => '기초부터 단계적 재구축'
            ],
            'R5_P3' => [
                'id' => 'R5_P3',
                'name' => '대안 제시자',
                'alias' => 'Alternative Suggester',
                'situation' => 'R5',
                'strategy' => 'ST2',
                'psychological' => '현재 방식에 대한 거부감',
                'behavioral' => '기존 학습 방식에 저항',
                'typical_utterance' => '다른 방식으로 접근해볼까요? 제가 새로운 방법을 보여드릴게요.',
                'response_strategy' => '완전히 다른 학습 경로 제안'
            ]
        ];

        $this->personaCache = $personas;
        return $personas;
    }

    /**
     * 상황 코드 목록 반환
     *
     * @return array 상황 코드 배열
     */
    public function getSituationCodes(): array {
        return array_keys($this->situationCodes);
    }

    /**
     * 전략 코드 목록 반환
     *
     * @return array 전략 코드 배열
     */
    public function getStrategyCodes(): array {
        return array_keys($this->strategyCodes);
    }

    /**
     * 학습 진행 상태 판단
     *
     * @param array $context 컨텍스트
     * @return string 상황 코드 (R1-R5)
     */
    protected function determineProgressState(array $context): string {
        $completionRate = $context['completion_rate'] ?? 0;
        $onTimeRate = $context['on_time_rate'] ?? 100;
        $attemptCount = $context['attempt_count'] ?? 0;
        $lastActivityGap = $context['last_activity_gap_minutes'] ?? 0;
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;

        // R5: 리듬 붕괴 - 긴급 상황
        if ($lastActivityGap > 60 || $consecutiveFailures >= 5) {
            return 'R5';
        }

        // R4: 정체 진행
        if ($completionRate < 30 || $onTimeRate < 50 || $consecutiveFailures >= 3) {
            return 'R4';
        }

        // R3: 지연 진행
        if ($completionRate < 50 || $onTimeRate < 70 || $attemptCount > 5) {
            return 'R3';
        }

        // R2: 적절 진행
        if ($completionRate < 80 || $onTimeRate < 90) {
            return 'R2';
        }

        // R1: 원활 진행
        return 'R1';
    }

    /**
     * 최적 전략 선택
     *
     * @param string $situation 상황 코드
     * @param array $context 컨텍스트
     * @return string 전략 코드 (ST1-ST5)
     */
    protected function selectOptimalStrategy(string $situation, array $context): string {
        $learningStyle = $context['learning_style'] ?? 'visual';
        $preferenceLevel = $context['autonomy_preference'] ?? 'medium';
        $emotionalState = $context['emotional_state'] ?? 'neutral';

        // 상황별 기본 전략 매핑
        $defaultStrategies = [
            'R1' => 'ST1', // 원활 → 질문하기
            'R2' => 'ST3', // 적절 → 활동축소
            'R3' => 'ST2', // 지연 → 도제학습
            'R4' => 'ST4', // 정체 → 하이튜터링
            'R5' => 'ST4'  // 리듬붕괴 → 하이튜터링
        ];

        $strategy = $defaultStrategies[$situation] ?? 'ST1';

        // 컨텍스트에 따른 전략 조정
        if ($emotionalState === 'frustrated' || $emotionalState === 'anxious') {
            // 감정적으로 불안정한 경우 -> 징검다리 활동으로 부담 감소
            if ($situation === 'R3' || $situation === 'R4') {
                $strategy = 'ST5';
            }
        }

        if ($preferenceLevel === 'high' && $situation !== 'R5') {
            // 자율성 선호가 높은 경우 -> 질문하기로 스스로 탐색 유도
            $strategy = 'ST1';
        }

        if ($learningStyle === 'kinesthetic' && in_array($situation, ['R3', 'R4'])) {
            // 체험형 학습자가 어려움을 겪는 경우 -> 도제학습
            $strategy = 'ST2';
        }

        return $strategy;
    }

    /**
     * 기본 페르소나 반환 (오버라이드)
     *
     * @param string $situation 상황 코드
     * @return string 기본 페르소나 ID
     */
    protected function getDefaultPersona(string $situation): string {
        $defaultMap = [
            'R1' => 'R1_P1',
            'R2' => 'R2_P1',
            'R3' => 'R3_P1',
            'R4' => 'R4_P1',
            'R5' => 'R5_P1'
        ];

        return $defaultMap[$situation] ?? 'R2_P1';
    }

    /**
     * 잔여 활동 분석
     *
     * @param int $userId 사용자 ID
     * @return array 잔여 활동 분석 결과
     */
    public function analyzeRemainingActivities(int $userId): array {
        global $DB;

        try {
            // 사용자의 미완료 활동 조회
            $sql = "SELECT
                        COUNT(*) as total_remaining,
                        SUM(CASE WHEN due_date < NOW() THEN 1 ELSE 0 END) as overdue_count,
                        SUM(CASE WHEN due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as due_soon,
                        AVG(estimated_time) as avg_time_needed
                    FROM mdl_user_activities
                    WHERE userid = ? AND status != 'completed'";

            $result = $DB->get_record_sql($sql, [$userId]);

            return [
                'total_remaining' => (int)($result->total_remaining ?? 0),
                'overdue_count' => (int)($result->overdue_count ?? 0),
                'due_soon' => (int)($result->due_soon ?? 0),
                'avg_time_needed' => (float)($result->avg_time_needed ?? 0),
                'urgency_level' => $this->calculateUrgencyLevel($result)
            ];

        } catch (Exception $e) {
            error_log("[Agent17PersonaEngine] {$this->currentFile}:" . __LINE__ .
                " - 잔여 활동 분석 실패: " . $e->getMessage());

            return [
                'total_remaining' => 0,
                'overdue_count' => 0,
                'due_soon' => 0,
                'avg_time_needed' => 0,
                'urgency_level' => 'unknown'
            ];
        }
    }

    /**
     * 긴급도 레벨 계산
     *
     * @param object $activityStats 활동 통계
     * @return string 긴급도 레벨
     */
    private function calculateUrgencyLevel($activityStats): string {
        if (!$activityStats) {
            return 'low';
        }

        $overdueRatio = $activityStats->total_remaining > 0
            ? $activityStats->overdue_count / $activityStats->total_remaining
            : 0;

        if ($overdueRatio >= 0.5) {
            return 'critical';
        } elseif ($overdueRatio >= 0.3 || $activityStats->due_soon > 5) {
            return 'high';
        } elseif ($overdueRatio >= 0.1 || $activityStats->due_soon > 2) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * 활동 재배분 계획 생성
     *
     * @param int $userId 사용자 ID
     * @param string $strategy 전략 코드
     * @return array 재배분 계획
     */
    public function generateReallocationPlan(int $userId, string $strategy): array {
        $activities = $this->analyzeRemainingActivities($userId);

        $plan = [
            'strategy' => $strategy,
            'strategy_name' => $this->strategyCodes[$strategy] ?? '알 수 없음',
            'total_activities' => $activities['total_remaining'],
            'recommendations' => []
        ];

        switch ($strategy) {
            case 'ST1': // 질문하기
                $plan['recommendations'][] = [
                    'action' => 'clarify_priorities',
                    'description' => '가장 중요한 활동이 무엇인지 질문으로 파악',
                    'expected_outcome' => '우선순위 명확화'
                ];
                break;

            case 'ST2': // 도제학습 전환
                $plan['recommendations'][] = [
                    'action' => 'demonstrate_process',
                    'description' => '어려운 활동의 해결 과정을 시범으로 보여줌',
                    'expected_outcome' => '문제 해결 방법 학습'
                ];
                break;

            case 'ST3': // 활동축소
                $plan['recommendations'][] = [
                    'action' => 'reduce_scope',
                    'description' => '핵심 활동만 선별하여 부담 감소',
                    'expected_outcome' => '완료 가능한 범위로 조정'
                ];
                break;

            case 'ST4': // 하이튜터링
                $plan['recommendations'][] = [
                    'action' => 'intensive_support',
                    'description' => '1:1 집중 지원으로 막힌 부분 해결',
                    'expected_outcome' => '즉각적인 문제 해결'
                ];
                break;

            case 'ST5': // 징검다리 활동
                $plan['recommendations'][] = [
                    'action' => 'bridge_activity',
                    'description' => '현재 수준과 목표 사이의 중간 활동 제공',
                    'expected_outcome' => '점진적 역량 향상'
                ];
                break;
        }

        return $plan;
    }

    /**
     * 에이전트별 맞춤 페르소나 목록 반환 (추상 메서드 구현)
     *
     * @return array 페르소나 정의 배열
     */
    public function getPersonaDefinitions(): array {
        return $this->personas;
    }

    /**
     * 에이전트별 기본 페르소나 ID 반환 (추상 메서드 구현)
     *
     * @return string 기본 페르소나 ID
     */
    public function getDefaultPersonaId(): string {
        return 'R2_P1'; // 기본: 세심한 조력자
    }

    /**
     * 디버그 정보 (확장)
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        $baseInfo = parent::getDebugInfo();

        return array_merge($baseInfo, [
            'situation_codes' => $this->situationCodes,
            'strategy_codes' => $this->strategyCodes,
            'persona_count' => count($this->personas),
            'engine_file' => $this->currentFile
        ]);
    }
}

/*
 * 사용 예시:
 *
 * $engine = new Agent17PersonaEngine([
 *     'debug_mode' => true,
 *     'ai_enabled' => false
 * ]);
 *
 * $result = $engine->process($userId, "이 활동 너무 어려워요, 어떻게 해야 해요?", [
 *     'course_id' => 123,
 *     'current_activity_id' => 456
 * ]);
 *
 * echo $result['response']['text'];
 */

/*
 * 관련 DB 테이블:
 *
 * - at_user_learning_state (사용자 학습 상태)
 *   - id: int, primary key
 *   - userid: int, foreign key to mdl_user
 *   - completion_rate: float
 *   - on_time_rate: float
 *   - learning_style: varchar(50)
 *   - emotional_state: varchar(50)
 *   - created_at: datetime
 *   - updated_at: datetime
 *
 * - at_agent_context (에이전트 컨텍스트)
 *   - id: int, primary key
 *   - userid: int
 *   - agent_id: varchar(50)
 *   - context_data: text (JSON)
 *   - created_at: datetime
 *   - updated_at: datetime
 *
 * - at_user_activities (사용자 활동)
 *   - id: int, primary key
 *   - userid: int
 *   - activity_type: varchar(50)
 *   - status: varchar(20)
 *   - due_date: datetime
 *   - estimated_time: int (minutes)
 *   - created_at: datetime
 */
