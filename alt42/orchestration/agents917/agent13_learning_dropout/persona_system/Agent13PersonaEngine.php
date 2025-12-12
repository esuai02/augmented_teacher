<?php
/**
 * Agent13PersonaEngine.php
 *
 * Agent13 학습 이탈 페르소나 엔진
 * AbstractPersonaEngine 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent13LearningDropout
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/Agent13PersonaEngine.php
 */

// MOODLE_INTERNAL 체크
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// AbstractPersonaEngine 로드
require_once(__DIR__ . '/../../engine_core/base/AbstractPersonaEngine.php');
require_once(__DIR__ . '/Agent13DataContext.php');

/**
 * Agent13PersonaEngine
 *
 * 학습 이탈 예측 및 방지를 위한 페르소나 엔진
 * 24시간 롤링 윈도우 기반 이탈 감지
 */
class Agent13PersonaEngine extends AbstractPersonaEngine
{
    /**
     * 이탈 위험 레벨 정의 (기존 4개 - 하위 호환성 유지)
     *
     * @var array
     */
    protected $dropoutRiskLevels = [
        'proactive' => [
            'name' => '사전 예방형',
            'name_en' => 'Proactive',
            'risk_tier' => 'Low',
            'risk_code' => 'L',
            'priority' => 0,
            'ninactive_max' => 1,
            'npomodoro_min' => 5,
            'description' => '안정적인 학습 패턴, 이탈 위험 낮음'
        ],
        'occasional' => [
            'name' => '간헐적 이탈형',
            'name_en' => 'Occasional',
            'risk_tier' => 'Medium',
            'risk_code' => 'M',
            'priority' => 1,
            'ninactive_range' => [2, 3],
            'npomodoro_range' => [2, 4],
            'eye_count_threshold' => 2,
            'description' => '간헐적 이탈 발생, 주의 필요'
        ],
        'chronic' => [
            'name' => '만성 이탈형',
            'name_en' => 'Chronic',
            'risk_tier' => 'High',
            'risk_code' => 'H',
            'priority' => 2,
            'ninactive_min' => 4,
            'npomodoro_max' => 2,
            'tlaststroke_min' => 30,
            'description' => '빈번한 이탈, 적극적 개입 필요'
        ],
        'critical' => [
            'name' => '위기 상태형',
            'name_en' => 'Critical',
            'risk_tier' => 'Critical',
            'risk_code' => 'C',
            'priority' => 3,
            'consecutive_high_days' => 2,
            'description' => '연속 고위험 상태, 즉각적 개입 필요'
        ]
    ];

    /**
     * 이탈 원인 정의 (혼합형 페르소나용)
     * M: Motivation (동기저하), R: Routine (루틴붕괴), S: Start barrier (시작장벽), E: External (외부요인)
     *
     * @var array
     */
    protected $dropoutCauses = [
        'M' => [
            'name' => '동기저하형',
            'name_en' => 'Motivation Decline',
            'description' => '학습 의욕 감소, 감정적 저항',
            'indicators' => ['emotion_keywords', 'npomodoro_trend', 'emotion_score'],
            'keywords' => ['싫어요', '하기 싫어', '재미없어', '지루해', '왜 해야', '의미 없어', '포기'],
            'priority' => 1
        ],
        'R' => [
            'name' => '루틴붕괴형',
            'name_en' => 'Routine Collapse',
            'description' => '학습 습관 붕괴, 불규칙한 패턴',
            'indicators' => ['nlazy_blocks', 'session_time_variance', 'routine_break_count'],
            'thresholds' => ['nlazy_blocks' => 3, 'session_time_variance' => 60],
            'priority' => 3
        ],
        'S' => [
            'name' => '시작장벽형',
            'name_en' => 'Start Barrier',
            'description' => '시작 자체가 어려움, 회피 행동',
            'indicators' => ['tlaststroke_min', 'login_without_activity', 'first_stroke_delay'],
            'thresholds' => ['tlaststroke_min' => 15, 'first_stroke_delay' => 20],
            'priority' => 2
        ],
        'E' => [
            'name' => '외부요인형',
            'name_en' => 'External Factor',
            'description' => '외부 환경 요인, 시간 제약',
            'indicators' => ['session_hour', 'academy_homework_burden', 'external_schedule'],
            'keywords' => ['학원', '시험', '바빠서', '시간이 없', '피곤해', '잠이'],
            'priority' => 4
        ]
    ];

    /**
     * 혼합형 페르소나 정의 (12개 = 3 Risk × 4 Causes)
     * 형식: {Risk_Code}_{Cause_Code} (예: L_M = Low + Motivation)
     *
     * @var array
     */
    protected $hybridPersonas = [
        // Low Risk (L_*) - 예방적 개입
        'L_M' => [
            'name' => '저위험-동기저하형',
            'name_en' => 'Low Risk - Motivation',
            'risk_tier' => 'Low',
            'cause' => 'M',
            'tone' => 'Encouraging',
            'pace' => 'Gentle',
            'intervention_mode' => 'Preventive',
            'message' => '오늘 기분이 별로인 것 같네요. 재미있는 퀴즈 하나 풀어볼까요? 🎯',
            'agent_collaboration' => null
        ],
        'L_R' => [
            'name' => '저위험-루틴붕괴형',
            'name_en' => 'Low Risk - Routine',
            'risk_tier' => 'Low',
            'cause' => 'R',
            'tone' => 'Supportive',
            'pace' => 'Steady',
            'intervention_mode' => 'Preventive',
            'message' => '오늘 학습 시간이 좀 불규칙했네요. 내일은 같은 시간에 시작해볼까요? ⏰',
            'agent_collaboration' => null
        ],
        'L_S' => [
            'name' => '저위험-시작장벽형',
            'name_en' => 'Low Risk - Start Barrier',
            'risk_tier' => 'Low',
            'cause' => 'S',
            'tone' => 'Warm',
            'pace' => 'Quick',
            'intervention_mode' => 'Preventive',
            'message' => '시작이 조금 어려웠나요? 딱 1문제만 풀어보면 어떨까요? 🚀',
            'agent_collaboration' => null
        ],
        'L_E' => [
            'name' => '저위험-외부요인형',
            'name_en' => 'Low Risk - External',
            'risk_tier' => 'Low',
            'cause' => 'E',
            'tone' => 'Understanding',
            'pace' => 'Flexible',
            'intervention_mode' => 'Preventive',
            'message' => '바쁜 하루였나 보네요. 오늘은 짧게 10분만 해볼까요? 📚',
            'agent_collaboration' => null
        ],
        // Medium Risk (M_*) - 적극적 개입
        'M_M' => [
            'name' => '중위험-동기저하형',
            'name_en' => 'Medium Risk - Motivation',
            'risk_tier' => 'Medium',
            'cause' => 'M',
            'tone' => 'Empathetic',
            'pace' => 'Moderate',
            'intervention_mode' => 'Active',
            'message' => '학습이 힘들게 느껴지나요? 지난주 잘 풀었던 문제 유형부터 다시 시작해볼까요? 💪',
            'agent_collaboration' => 5
        ],
        'M_R' => [
            'name' => '중위험-루틴붕괴형',
            'name_en' => 'Medium Risk - Routine',
            'risk_tier' => 'Medium',
            'cause' => 'R',
            'tone' => 'Structured',
            'pace' => 'Guided',
            'intervention_mode' => 'Active',
            'message' => '학습 패턴이 흐트러진 것 같아요. 오늘부터 3일간 짧은 미니 루틴으로 재건해볼까요? 📋',
            'agent_collaboration' => 12
        ],
        'M_S' => [
            'name' => '중위험-시작장벽형',
            'name_en' => 'Medium Risk - Start Barrier',
            'risk_tier' => 'Medium',
            'cause' => 'S',
            'tone' => 'Encouraging',
            'pace' => 'Step-by-step',
            'intervention_mode' => 'Active',
            'message' => '시작하기가 많이 어렵나요? 제가 단계별로 안내해드릴게요. 먼저 책상 정리부터! 📖',
            'agent_collaboration' => null
        ],
        'M_E' => [
            'name' => '중위험-외부요인형',
            'name_en' => 'Medium Risk - External',
            'risk_tier' => 'Medium',
            'cause' => 'E',
            'tone' => 'Practical',
            'pace' => 'Adaptive',
            'intervention_mode' => 'Active',
            'message' => '외부 일정이 많은 것 같아요. 학습 시간을 조정해서 현실적인 계획을 세워볼까요? 🗓️',
            'agent_collaboration' => null
        ],
        // High Risk (H_*) - 긴급 개입
        'H_M' => [
            'name' => '고위험-동기저하형',
            'name_en' => 'High Risk - Motivation',
            'risk_tier' => 'High',
            'cause' => 'M',
            'tone' => 'Caring',
            'pace' => 'Patient',
            'intervention_mode' => 'Urgent',
            'message' => '많이 지쳤나요? 오늘은 학습보다 잠시 쉬어가도 괜찮아요. 내일 다시 시작해볼까요? 🌙',
            'agent_collaboration' => 5
        ],
        'H_R' => [
            'name' => '고위험-루틴붕괴형',
            'name_en' => 'High Risk - Routine',
            'risk_tier' => 'High',
            'cause' => 'R',
            'tone' => 'Supportive',
            'pace' => 'Minimal',
            'intervention_mode' => 'Urgent',
            'message' => '루틴이 많이 흐트러졌네요. 오늘은 딱 5분만! 작은 성공부터 쌓아가요. 🔄',
            'agent_collaboration' => 12
        ],
        'H_S' => [
            'name' => '고위험-시작장벽형',
            'name_en' => 'High Risk - Start Barrier',
            'risk_tier' => 'High',
            'cause' => 'S',
            'tone' => 'Gentle',
            'pace' => 'Micro',
            'intervention_mode' => 'Urgent',
            'message' => '시작이 정말 어렵죠? 오늘은 로그인한 것만으로도 대단해요! 딱 1문제만 볼까요? ✨',
            'agent_collaboration' => null
        ],
        'H_E' => [
            'name' => '고위험-외부요인형',
            'name_en' => 'High Risk - External',
            'risk_tier' => 'High',
            'cause' => 'E',
            'tone' => 'Understanding',
            'pace' => 'Flexible',
            'intervention_mode' => 'Urgent',
            'message' => '지금은 외부 상황이 많이 어려운 것 같아요. 최소한만 유지하면서 상황이 나아지길 기다려봐요. 🤗',
            'agent_collaboration' => null,
            'notify_teacher' => true
        ]
    ];

    /**
     * 개입 전략 매핑
     *
     * @var array
     */
    protected $interventionStrategies = [
        'proactive' => [
            'mode' => 'maintain',
            'coaching_tone' => 'supportive',
            'intervention_level' => 'minimal',
            'focus' => ['motivation_maintenance', 'habit_reinforcement'],
            'recommended_actions' => ['praise', 'goal_reminder']
        ],
        'occasional' => [
            'mode' => 'guide',
            'coaching_tone' => 'encouraging',
            'intervention_level' => 'moderate',
            'focus' => ['pattern_awareness', 'refocus_support'],
            'recommended_actions' => ['gentle_reminder', 'short_task', 'break_suggestion']
        ],
        'chronic' => [
            'mode' => 'intervene',
            'coaching_tone' => 'coaching',
            'intervention_level' => 'active',
            'focus' => ['routine_restructure', 'barrier_removal'],
            'recommended_actions' => ['direct_intervention', 'session_restart', 'goal_simplification']
        ],
        'critical' => [
            'mode' => 'escalate',
            'coaching_tone' => 'urgent',
            'intervention_level' => 'intensive',
            'focus' => ['immediate_support', 'external_help'],
            'recommended_actions' => ['immediate_contact', 'parent_notification', 'counselor_referral']
        ]
    ];

    /**
     * 코칭 메시지 템플릿
     *
     * @var array
     */
    protected $coachingTemplates = [
        'proactive' => [
            'greeting' => '👏 훌륭해요! 꾸준한 학습 습관이 멋지네요.',
            'feedback' => '지금처럼 유지하면 목표 달성이 눈앞이에요!',
            'action' => '오늘의 목표를 확인하고 계속 진행해볼까요?'
        ],
        'occasional' => [
            'greeting' => '💪 잠깐 쉬어갔네요. 괜찮아요!',
            'feedback' => '짧은 휴식 후 다시 집중해볼까요?',
            'action' => '10분만 집중해서 한 문제 풀어볼까요?'
        ],
        'chronic' => [
            'greeting' => '⚠️ 오늘 집중이 어려운 것 같아요.',
            'feedback' => '짧은 목표부터 시작해보는 건 어떨까요?',
            'action' => '타이머 5분 설정하고 쉬운 문제 1개만 도전해봐요!'
        ],
        'critical' => [
            'greeting' => '🚨 지금 많이 힘든 것 같아요.',
            'feedback' => '혼자 힘들면 도움을 요청해도 괜찮아요.',
            'action' => '잠깐 쉬고, 선생님께 이야기해볼까요?'
        ]
    ];

    /**
     * 24시간 롤링 윈도우 (초)
     */
    const ROLLING_WINDOW = 86400;

    /**
     * 이탈 판정 임계값 (초)
     */
    const DROPOUT_THRESHOLD_MIN = 300;   // 5분
    const DROPOUT_THRESHOLD_MAX = 43200; // 12시간

    /**
     * DataContext 인스턴스
     *
     * @var Agent13DataContext
     */
    protected $dataContext;

    /**
     * 생성자
     *
     * @param object $db Moodle DB 인스턴스
     */
    public function __construct($db)
    {
        parent::__construct($db, 13, 'learning_dropout');
        $this->dataContext = new Agent13DataContext($db);
    }

    /**
     * 엔진 초기화
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->config = [
            'agent_number' => 13,
            'agent_name' => 'learning_dropout',
            'agent_name_ko' => '학습 이탈',
            'version' => '1.0.0',
            'rolling_window' => self::ROLLING_WINDOW,
            'dropout_threshold_min' => self::DROPOUT_THRESHOLD_MIN,
            'dropout_threshold_max' => self::DROPOUT_THRESHOLD_MAX
        ];
    }

    /**
     * 페르소나 식별 (기본 + 혼합형)
     *
     * @param int $userId 사용자 ID
     * @param array $contextData 추가 컨텍스트 (userMessage 포함 가능)
     * @return array 페르소나 정보 (기본 + 혼합형 포함)
     */
    public function identifyPersona(int $userId, array $contextData = []): array
    {
        // 이탈 데이터 수집
        $dropoutData = $this->dataContext->getDropoutIndicators($userId);

        // 위험 등급 계산
        $riskTier = $this->calculateRiskTier($dropoutData);

        // 기본 페르소나 코드 결정 (하위 호환성)
        $personaCode = $this->mapRiskTierToPersona($riskTier, $dropoutData);

        // 신뢰도 계산
        $confidence = $this->calculateConfidence($dropoutData, $personaCode);

        // ====== 혼합형 페르소나 식별 ======
        // 사용자 메시지 추출 (있는 경우)
        $userMessage = isset($contextData['userMessage']) ? $contextData['userMessage'] : '';

        // 이탈 원인 감지
        $dropoutCause = $this->detectDropoutCause($dropoutData, $userMessage);

        // 혼합형 페르소나 ID 생성
        $hybridPersonaId = $this->getHybridPersonaId($riskTier, $dropoutCause);

        // 혼합형 페르소나 정보 조회
        $hybridInfo = $this->getHybridPersonaInfo($hybridPersonaId);

        // Critical 상태 체크
        $isCritical = ($riskTier === 'Critical');

        // 페르소나 상태 저장 (혼합형 정보 포함)
        $this->savePersonaState($userId, $personaCode, $confidence, array_merge($dropoutData, [
            'hybrid_persona_id' => $hybridPersonaId,
            'dropout_cause' => $dropoutCause
        ]));

        return [
            // 기본 페르소나 (하위 호환성)
            'persona_code' => $personaCode,
            'persona_name' => $this->dropoutRiskLevels[$personaCode]['name'],
            'persona_name_en' => $this->dropoutRiskLevels[$personaCode]['name_en'],
            'risk_tier' => $riskTier,
            'confidence' => $confidence,

            // 혼합형 페르소나 (신규)
            'hybrid_persona_id' => $hybridPersonaId,
            'hybrid_persona_name' => $hybridInfo ? $hybridInfo['name'] : null,
            'hybrid_persona_name_en' => $hybridInfo ? $hybridInfo['name_en'] : null,
            'dropout_cause' => $dropoutCause,
            'dropout_cause_name' => $this->dropoutCauses[$dropoutCause]['name'],
            'dropout_cause_name_en' => $this->dropoutCauses[$dropoutCause]['name_en'],

            // 혼합형 개입 정보
            'hybrid_intervention' => $hybridInfo ? [
                'tone' => $hybridInfo['tone'],
                'pace' => $hybridInfo['pace'],
                'mode' => $hybridInfo['intervention_mode'],
                'message' => $hybridInfo['message'],
                'agent_collaboration' => $hybridInfo['agent_collaboration'] ?? null,
                'notify_teacher' => $hybridInfo['notify_teacher'] ?? false
            ] : null,

            // Critical 상태 정보
            'is_critical' => $isCritical,
            'escalation_needed' => $isCritical || ($riskTier === 'High' && isset($hybridInfo['notify_teacher']) && $hybridInfo['notify_teacher']),

            // 지표
            'indicators' => [
                'ninactive' => $dropoutData['ninactive'],
                'eye_count' => $dropoutData['eye_count'],
                'tlaststroke_min' => $dropoutData['tlaststroke_min'],
                'npomodoro' => $dropoutData['npomodoro'],
                'kpomodoro' => $dropoutData['kpomodoro'],
                'nlazy_blocks' => $dropoutData['nlazy_blocks']
            ],

            // 전략 (기본)
            'strategy' => $this->interventionStrategies[$personaCode],
            'timestamp' => time()
        ];
    }

    /**
     * 위험 등급 계산
     *
     * @param array $data 이탈 지표 데이터
     * @return string 위험 등급 (Low, Medium, High, Critical)
     */
    protected function calculateRiskTier(array $data): string
    {
        // Critical 체크 (연속 고위험)
        if ($this->checkConsecutiveHighRisk($data)) {
            return 'Critical';
        }

        // High 조건: ninactive >= 4 OR npomodoro < 2 OR tlaststroke_min >= 30
        if ($data['ninactive'] >= 4 ||
            $data['npomodoro'] < 2 ||
            ($data['tlaststroke_min'] !== null && $data['tlaststroke_min'] >= 30)) {
            return 'High';
        }

        // Medium 조건: ninactive in [2,3] OR npomodoro in [2,4] OR eye_count >= 2
        if (($data['ninactive'] >= 2 && $data['ninactive'] <= 3) ||
            ($data['npomodoro'] >= 2 && $data['npomodoro'] <= 4) ||
            $data['eye_count'] >= 2) {
            return 'Medium';
        }

        // Low 조건: ninactive <= 1 AND npomodoro >= 5
        if ($data['ninactive'] <= 1 && $data['npomodoro'] >= 5) {
            return 'Low';
        }

        // 기본값 (데이터 부족 시)
        return 'Medium';
    }

    /**
     * 연속 고위험 상태 체크
     *
     * @param array $data 이탈 지표
     * @return bool
     */
    protected function checkConsecutiveHighRisk(array $data): bool
    {
        if (!isset($data['consecutive_high_days'])) {
            return false;
        }
        return $data['consecutive_high_days'] >= 2;
    }

    /**
     * 위험 등급을 페르소나로 매핑
     *
     * @param string $riskTier 위험 등급
     * @param array $data 이탈 지표
     * @return string 페르소나 코드
     */
    protected function mapRiskTierToPersona(string $riskTier, array $data): string
    {
        switch ($riskTier) {
            case 'Low':
                return 'proactive';
            case 'Medium':
                return 'occasional';
            case 'High':
                return 'chronic';
            case 'Critical':
                return 'critical';
            default:
                return 'occasional';
        }
    }

    /**
     * 이탈 원인 감지 (혼합형 페르소나용)
     * 우선순위: M(동기저하) > S(시작장벽) > R(루틴붕괴) > E(외부요인)
     *
     * @param array $data 이탈 지표 데이터
     * @param string $userMessage 사용자 메시지 (감정 키워드 분석용)
     * @return string 원인 코드 (M, R, S, E)
     */
    protected function detectDropoutCause(array $data, string $userMessage = ''): string
    {
        $causeScores = [
            'M' => 0.0,  // Motivation
            'S' => 0.0,  // Start barrier
            'R' => 0.0,  // Routine
            'E' => 0.0   // External
        ];

        // 1. 동기저하(M) 감지 - 키워드 + 포모도로 추세
        $motivationKeywords = $this->dropoutCauses['M']['keywords'];
        foreach ($motivationKeywords as $keyword) {
            if (mb_stripos($userMessage, $keyword) !== false) {
                $causeScores['M'] += 0.3;
            }
        }
        // 포모도로 감소 추세
        if (isset($data['npomodoro_trend']) && $data['npomodoro_trend'] < 0) {
            $causeScores['M'] += 0.2;
        }
        // 감정 점수 기반
        if (isset($data['emotion_score']) && $data['emotion_score'] < -0.3) {
            $causeScores['M'] += 0.25;
        }
        // 포모도로가 매우 적음
        if (isset($data['npomodoro']) && $data['npomodoro'] < 2) {
            $causeScores['M'] += 0.15;
        }

        // 2. 시작장벽(S) 감지 - 로그인 후 무활동
        if (isset($data['tlaststroke_min']) && $data['tlaststroke_min'] !== null) {
            if ($data['tlaststroke_min'] >= 15) {
                $causeScores['S'] += 0.35;
            } elseif ($data['tlaststroke_min'] >= 10) {
                $causeScores['S'] += 0.2;
            }
        }
        // 첫 필기까지 지연 시간
        if (isset($data['first_stroke_delay']) && $data['first_stroke_delay'] > 20) {
            $causeScores['S'] += 0.25;
        }
        // 로그인만 하고 활동 없음
        if (isset($data['login_without_activity']) && $data['login_without_activity'] > 0) {
            $causeScores['S'] += 0.2;
        }

        // 3. 루틴붕괴(R) 감지 - 게으름 블록 + 불규칙 패턴
        if (isset($data['nlazy_blocks']) && $data['nlazy_blocks'] >= 3) {
            $causeScores['R'] += 0.35;
        } elseif (isset($data['nlazy_blocks']) && $data['nlazy_blocks'] >= 2) {
            $causeScores['R'] += 0.2;
        }
        // 세션 시간 분산
        if (isset($data['session_time_variance']) && $data['session_time_variance'] > 60) {
            $causeScores['R'] += 0.25;
        }
        // 루틴 이탈 횟수
        if (isset($data['routine_break_count']) && $data['routine_break_count'] >= 2) {
            $causeScores['R'] += 0.2;
        }

        // 4. 외부요인(E) 감지 - 키워드 + 학원 부담
        $externalKeywords = $this->dropoutCauses['E']['keywords'];
        foreach ($externalKeywords as $keyword) {
            if (mb_stripos($userMessage, $keyword) !== false) {
                $causeScores['E'] += 0.3;
            }
        }
        // 학원 숙제 부담
        if (isset($data['academy_homework_burden']) && $data['academy_homework_burden'] > 0.5) {
            $causeScores['E'] += 0.25;
        }
        // 특정 시간대 집중 어려움 (저녁 늦은 시간)
        if (isset($data['session_hour']) && ($data['session_hour'] >= 22 || $data['session_hour'] <= 6)) {
            $causeScores['E'] += 0.15;
        }

        // 5. 우선순위 기반 원인 결정 (점수 동점 시 M > S > R > E)
        $priorityOrder = ['M', 'S', 'R', 'E'];
        $maxScore = 0.0;
        $detectedCause = 'R'; // 기본값: 루틴붕괴

        foreach ($priorityOrder as $cause) {
            if ($causeScores[$cause] > $maxScore) {
                $maxScore = $causeScores[$cause];
                $detectedCause = $cause;
            }
        }

        // 최소 임계값 체크 (0.2 미만이면 기본값 사용)
        if ($maxScore < 0.2) {
            return 'R'; // 데이터 부족 시 루틴붕괴로 기본 처리
        }

        return $detectedCause;
    }

    /**
     * 혼합형 페르소나 ID 생성
     * 형식: {Risk_Code}_{Cause_Code} (예: L_M, M_R, H_S)
     *
     * @param string $riskTier 위험 등급 (Low, Medium, High, Critical)
     * @param string $causeCode 원인 코드 (M, R, S, E)
     * @return string 혼합형 페르소나 ID
     */
    protected function getHybridPersonaId(string $riskTier, string $causeCode): string
    {
        // Critical은 특수 처리 (H_* 기반으로 escalate)
        if ($riskTier === 'Critical') {
            return 'H_' . $causeCode;
        }

        // Risk 코드 매핑
        $riskCodeMap = [
            'Low' => 'L',
            'Medium' => 'M',
            'High' => 'H'
        ];

        $riskCode = isset($riskCodeMap[$riskTier]) ? $riskCodeMap[$riskTier] : 'M';
        $hybridId = $riskCode . '_' . $causeCode;

        // 유효성 검증
        if (!isset($this->hybridPersonas[$hybridId])) {
            error_log("[Agent13PersonaEngine WARN] " . __FILE__ . ":" . __LINE__ .
                      " - Invalid hybrid persona ID: {$hybridId}, falling back to M_R");
            return 'M_R';
        }

        return $hybridId;
    }

    /**
     * 혼합형 페르소나 정보 조회
     *
     * @param string $hybridId 혼합형 페르소나 ID
     * @return array|null 페르소나 정보
     */
    protected function getHybridPersonaInfo(string $hybridId): ?array
    {
        if (!isset($this->hybridPersonas[$hybridId])) {
            return null;
        }

        $persona = $this->hybridPersonas[$hybridId];
        $causeCode = $persona['cause'];
        $causeInfo = $this->dropoutCauses[$causeCode] ?? null;

        return array_merge($persona, [
            'hybrid_id' => $hybridId,
            'cause_info' => $causeInfo
        ]);
    }

    /**
     * 신뢰도 계산
     *
     * @param array $data 이탈 지표
     * @param string $personaCode 페르소나 코드
     * @return float 신뢰도 (0.0 ~ 1.0)
     */
    protected function calculateConfidence(array $data, string $personaCode): float
    {
        $confidence = 0.5; // 기본값

        // 데이터 완전성 기반 조정
        $hasNinactive = isset($data['ninactive']);
        $hasNpomodoro = isset($data['npomodoro']) && $data['npomodoro'] !== null;
        $hasTlaststroke = isset($data['tlaststroke_min']) && $data['tlaststroke_min'] !== null;
        $hasEyeCount = isset($data['eye_count']);

        $dataCompleteness = ($hasNinactive ? 0.25 : 0) +
                           ($hasNpomodoro ? 0.25 : 0) +
                           ($hasTlaststroke ? 0.25 : 0) +
                           ($hasEyeCount ? 0.25 : 0);

        $confidence += $dataCompleteness * 0.3;

        // 지표 명확성 기반 조정
        switch ($personaCode) {
            case 'proactive':
                // Low 조건이 명확히 충족될수록 높음
                if ($data['ninactive'] === 0 && $data['npomodoro'] >= 7) {
                    $confidence += 0.15;
                }
                break;

            case 'chronic':
                // High 조건이 복합적으로 충족될수록 높음
                $highIndicators = 0;
                if ($data['ninactive'] >= 4) $highIndicators++;
                if ($data['npomodoro'] < 2) $highIndicators++;
                if ($data['tlaststroke_min'] !== null && $data['tlaststroke_min'] >= 30) $highIndicators++;
                $confidence += $highIndicators * 0.05;
                break;

            case 'critical':
                // 연속 고위험은 높은 신뢰도
                $confidence += 0.2;
                break;
        }

        return min(0.98, max(0.5, $confidence));
    }

    /**
     * 응답 생성 (기본 + 혼합형 지원)
     *
     * @param int $userId 사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $userMessage 사용자 메시지
     * @param array $options 추가 옵션 (use_hybrid: bool 포함 가능)
     * @return array 응답 데이터
     */
    public function generateResponse(int $userId, string $personaCode, string $userMessage, array $options = []): array
    {
        // 현재 이탈 상태 조회
        $dropoutData = $this->dataContext->getDropoutIndicators($userId);
        $strategy = $this->interventionStrategies[$personaCode];
        $templates = $this->coachingTemplates[$personaCode];

        // 혼합형 사용 여부 (옵션 또는 자동 감지)
        $useHybrid = isset($options['use_hybrid']) ? $options['use_hybrid'] : true;

        // 혼합형 페르소나 정보 수집
        $hybridResponse = null;
        $hybridPersonaId = null;
        $dropoutCause = null;

        if ($useHybrid) {
            // 이탈 원인 감지
            $dropoutCause = $this->detectDropoutCause($dropoutData, $userMessage);

            // 위험 등급 계산
            $riskTier = $this->dropoutRiskLevels[$personaCode]['risk_tier'];

            // 혼합형 페르소나 ID 생성
            $hybridPersonaId = $this->getHybridPersonaId($riskTier, $dropoutCause);

            // 혼합형 페르소나 정보 조회
            $hybridInfo = $this->getHybridPersonaInfo($hybridPersonaId);

            if ($hybridInfo) {
                // 혼합형 응답 생성
                $hybridResponse = $this->buildHybridResponse($hybridInfo, $dropoutData, $userMessage);
            }
        }

        // 맥락 기반 응답 생성 (기본)
        $response = $this->buildContextualResponse($personaCode, $dropoutData, $userMessage, $templates);

        // 혼합형 응답이 있으면 통합
        if ($hybridResponse) {
            $response['message'] = $hybridResponse['message'];
            $response['prompts'] = array_merge($response['prompts'], $hybridResponse['prompts']);
        }

        // 권장 액션 생성
        $recommendedActions = $this->generateRecommendedActions($personaCode, $dropoutData);

        // 혼합형 에이전트 협업 추가
        if ($hybridResponse && isset($hybridResponse['agent_collaboration'])) {
            $recommendedActions[] = [
                'type' => 'agent_collaboration',
                'action' => 'Agent' . $hybridResponse['agent_collaboration'] . ' 협업 요청',
                'priority' => 'high',
                'linked_agent' => $hybridResponse['agent_collaboration']
            ];
        }

        // 개입 기록 저장
        $this->logIntervention($userId, $personaCode, $response['message'], $recommendedActions);

        return [
            'message' => $response['message'],
            'coaching_tone' => $hybridResponse ? $hybridResponse['tone'] : $strategy['coaching_tone'],
            'intervention_level' => $strategy['intervention_level'],
            'recommended_actions' => $recommendedActions,
            'prompts' => $response['prompts'],
            'metadata' => [
                'persona_code' => $personaCode,
                'risk_tier' => $this->dropoutRiskLevels[$personaCode]['risk_tier'],
                'hybrid_persona_id' => $hybridPersonaId,
                'dropout_cause' => $dropoutCause,
                'use_hybrid' => $useHybrid,
                'generated_at' => time()
            ]
        ];
    }

    /**
     * 혼합형 페르소나 기반 응답 생성
     *
     * @param array $hybridInfo 혼합형 페르소나 정보
     * @param array $dropoutData 이탈 데이터
     * @param string $userMessage 사용자 메시지
     * @return array
     */
    protected function buildHybridResponse(array $hybridInfo, array $dropoutData, string $userMessage): array
    {
        $message = '';
        $prompts = [];

        // 페르소나별 인사말
        $message .= $hybridInfo['message'] . "\n\n";

        // 원인별 맞춤 피드백
        $cause = $hybridInfo['cause'];
        switch ($cause) {
            case 'M': // 동기저하
                if ($dropoutData['npomodoro'] < 3) {
                    $message .= "📊 오늘 포모도로가 " . $dropoutData['npomodoro'] . "개예요. ";
                    $message .= "지난주 잘 풀었던 유형으로 가볍게 시작해볼까요?\n";
                }
                $prompts[] = "지금 기분을 1~10점으로 표현해볼까요?";
                $prompts[] = "어떤 문제 유형이 가장 재미있었어요?";
                break;

            case 'R': // 루틴붕괴
                if (isset($dropoutData['nlazy_blocks']) && $dropoutData['nlazy_blocks'] > 0) {
                    $message .= "⏰ 학습 패턴이 조금 불규칙해졌어요. ";
                    $message .= "오늘부터 짧은 루틴으로 다시 시작해봐요!\n";
                }
                $prompts[] = "매일 같은 시간에 시작하는 건 어떨까요?";
                $prompts[] = "3일간 미니 루틴 도전해볼까요? (15분씩)";
                break;

            case 'S': // 시작장벽
                if ($dropoutData['tlaststroke_min'] !== null && $dropoutData['tlaststroke_min'] >= 10) {
                    $message .= "🚀 시작이 어려웠나요? 괜찮아요! ";
                    $message .= "딱 1문제만 풀어보면 탄력이 붙을 거예요.\n";
                }
                $prompts[] = "지금 바로 첫 문제 시작해볼까요?";
                $prompts[] = "시작 전 1분 심호흡 하고 시작해요!";
                break;

            case 'E': // 외부요인
                $message .= "💼 외부 일정이 많나요? ";
                $message .= "오늘은 가볍게 유지만 해도 충분해요.\n";
                $prompts[] = "학원/시험 일정 때문에 힘드셨나요?";
                $prompts[] = "오늘 가능한 시간이 얼마나 될까요?";
                break;
        }

        // 페이스별 권장 시간
        $paceRecommendation = $this->getPaceRecommendation($hybridInfo['pace']);
        if ($paceRecommendation) {
            $message .= "\n💡 " . $paceRecommendation;
        }

        return [
            'message' => $message,
            'prompts' => $prompts,
            'tone' => $hybridInfo['tone'],
            'agent_collaboration' => $hybridInfo['agent_collaboration'] ?? null
        ];
    }

    /**
     * 페이스별 권장 시간 조회
     *
     * @param string $pace 페이스 유형
     * @return string|null
     */
    protected function getPaceRecommendation(string $pace): ?string
    {
        $recommendations = [
            'Quick' => '오늘은 5분 퀵 세션으로 시작해요!',
            'Gentle' => '천천히 10분 정도 진행해볼까요?',
            'Steady' => '15분 꾸준한 페이스로 가볼까요?',
            'Guided' => '제가 단계별로 안내해드릴게요.',
            'Step-by-step' => '한 단계씩 차근차근 진행해요.',
            'Moderate' => '20분 정도 집중해볼까요?',
            'Adaptive' => '오늘 컨디션에 맞게 조절해요.',
            'Flexible' => '가능한 시간만큼만 해요.',
            'Patient' => '급하지 않아요. 천천히 시작해요.',
            'Minimal' => '딱 5분만! 최소한으로 유지해요.',
            'Micro' => '1문제만 풀어봐요. 그것만으로도 충분해요.'
        ];

        return isset($recommendations[$pace]) ? $recommendations[$pace] : null;
    }

    /**
     * 맥락 기반 응답 구성
     *
     * @param string $personaCode 페르소나 코드
     * @param array $dropoutData 이탈 데이터
     * @param string $userMessage 사용자 메시지
     * @param array $templates 템플릿
     * @return array
     */
    protected function buildContextualResponse(string $personaCode, array $dropoutData, string $userMessage, array $templates): array
    {
        $message = $templates['greeting'] . "\n\n";

        // 상황별 피드백 추가
        if ($dropoutData['ninactive'] > 0) {
            $message .= "오늘 " . $dropoutData['ninactive'] . "번의 이탈이 있었어요. ";
        }
        if ($dropoutData['npomodoro'] < 5) {
            $message .= "포모도로 " . $dropoutData['npomodoro'] . "개 완료했네요. ";
        }
        if ($dropoutData['tlaststroke_min'] !== null && $dropoutData['tlaststroke_min'] >= 5) {
            $message .= "마지막 활동이 " . round($dropoutData['tlaststroke_min']) . "분 전이에요. ";
        }

        $message .= "\n\n" . $templates['feedback'];
        $message .= "\n\n💡 " . $templates['action'];

        // 코칭 프롬프트 생성
        $prompts = $this->generateCoachingPrompts($personaCode, $dropoutData);

        return [
            'message' => $message,
            'prompts' => $prompts
        ];
    }

    /**
     * 코칭 프롬프트 생성
     *
     * @param string $personaCode 페르소나 코드
     * @param array $dropoutData 이탈 데이터
     * @return array
     */
    protected function generateCoachingPrompts(string $personaCode, array $dropoutData): array
    {
        $prompts = [];

        switch ($personaCode) {
            case 'proactive':
                $prompts[] = "오늘 목표를 확인하고 다음 단계로 넘어가요!";
                $prompts[] = "지금 집중력이 좋을 때 어려운 문제에 도전해볼까요?";
                break;

            case 'occasional':
                $prompts[] = "지금부터 10분만 문제 풀이에만 집중해요. 끝나면 1분 휴식!";
                $prompts[] = "쉬운 문제 하나부터 시작해볼까요?";
                $prompts[] = "타이머 10분 시작하고 한 번 도전해봐요!";
                break;

            case 'chronic':
                $prompts[] = "오늘 목표를 한 줄로 적고, 10분 세션을 시작해볼까요?";
                $prompts[] = "타이머 10분 시작 → 문제 1개 → 체크리스트 1줄 기록까지 완료!";
                $prompts[] = "5분만 집중! 딱 한 문제만 풀어봐요.";
                break;

            case 'critical':
                $prompts[] = "지금은 쉬어도 괜찮아요. 컨디션이 안 좋으면 쉬는 것도 학습이에요.";
                $prompts[] = "선생님께 지금 상황을 말씀드려볼까요?";
                $prompts[] = "오늘은 여기까지만 하고, 내일 다시 시작해요.";
                break;
        }

        return $prompts;
    }

    /**
     * 권장 액션 생성
     *
     * @param string $personaCode 페르소나 코드
     * @param array $dropoutData 이탈 데이터
     * @return array
     */
    protected function generateRecommendedActions(string $personaCode, array $dropoutData): array
    {
        $actions = [];
        $strategy = $this->interventionStrategies[$personaCode];

        foreach ($strategy['recommended_actions'] as $actionType) {
            switch ($actionType) {
                case 'praise':
                    $actions[] = [
                        'type' => 'praise',
                        'action' => '칭찬 메시지 전송',
                        'priority' => 'low'
                    ];
                    break;

                case 'goal_reminder':
                    $actions[] = [
                        'type' => 'goal_reminder',
                        'action' => '오늘 목표 리마인더',
                        'priority' => 'low'
                    ];
                    break;

                case 'gentle_reminder':
                    $actions[] = [
                        'type' => 'gentle_reminder',
                        'action' => '부드러운 리포커스 메시지',
                        'priority' => 'medium'
                    ];
                    break;

                case 'short_task':
                    $actions[] = [
                        'type' => 'short_task',
                        'action' => '5-10분 짧은 태스크 제시',
                        'priority' => 'medium'
                    ];
                    break;

                case 'break_suggestion':
                    $actions[] = [
                        'type' => 'break_suggestion',
                        'action' => '휴식 권유 (Agent12 연계)',
                        'priority' => 'medium',
                        'linked_agent' => 12
                    ];
                    break;

                case 'direct_intervention':
                    $actions[] = [
                        'type' => 'direct_intervention',
                        'action' => '직접 개입 메시지',
                        'priority' => 'high'
                    ];
                    break;

                case 'session_restart':
                    $actions[] = [
                        'type' => 'session_restart',
                        'action' => '세션 재시작 유도',
                        'priority' => 'high'
                    ];
                    break;

                case 'goal_simplification':
                    $actions[] = [
                        'type' => 'goal_simplification',
                        'action' => '목표 단순화 제안',
                        'priority' => 'high'
                    ];
                    break;

                case 'immediate_contact':
                    $actions[] = [
                        'type' => 'immediate_contact',
                        'action' => '즉각 연락 시도',
                        'priority' => 'critical'
                    ];
                    break;

                case 'parent_notification':
                    $actions[] = [
                        'type' => 'parent_notification',
                        'action' => '보호자 알림 (운영정책 준수)',
                        'priority' => 'critical'
                    ];
                    break;

                case 'counselor_referral':
                    $actions[] = [
                        'type' => 'counselor_referral',
                        'action' => '상담사/담임 연계',
                        'priority' => 'critical'
                    ];
                    break;
            }
        }

        return $actions;
    }

    /**
     * 개입 기록 저장
     *
     * @param int $userId 사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $message 메시지
     * @param array $actions 액션 목록
     * @return void
     */
    protected function logIntervention(int $userId, string $personaCode, string $message, array $actions): void
    {
        $this->dataContext->logIntervention($userId, [
            'persona_code' => $personaCode,
            'risk_tier' => $this->dropoutRiskLevels[$personaCode]['risk_tier'],
            'message_preview' => mb_substr($message, 0, 200),
            'actions' => json_encode($actions),
            'timecreated' => time()
        ]);
    }

    /**
     * 휴식 필요성 분석
     *
     * @param int $userId 사용자 ID
     * @param int $currentStudyMinutes 현재 학습 시간(분)
     * @return array
     */
    public function analyzeDropoutNeed(int $userId, int $currentStudyMinutes = 0): array
    {
        $dropoutData = $this->dataContext->getDropoutIndicators($userId);
        $riskTier = $this->calculateRiskTier($dropoutData);

        // 이탈 위험도 점수 (0-100)
        $riskScore = $this->calculateRiskScore($dropoutData);

        // 권장 사항
        $recommendations = $this->generateDropoutRecommendations($riskTier, $dropoutData, $currentStudyMinutes);

        return [
            'risk_tier' => $riskTier,
            'risk_score' => $riskScore,
            'indicators' => $dropoutData,
            'recommendations' => $recommendations,
            'need_immediate_intervention' => $riskTier === 'Critical' || $riskScore >= 80,
            'suggested_actions' => $this->interventionStrategies[$this->mapRiskTierToPersona($riskTier, $dropoutData)]['recommended_actions']
        ];
    }

    /**
     * 위험도 점수 계산 (0-100)
     *
     * @param array $data 이탈 지표
     * @return float
     */
    protected function calculateRiskScore(array $data): float
    {
        $score = 30.0; // 기본값

        // ninactive 기반 점수 (최대 +30)
        $score += min(30, $data['ninactive'] * 7.5);

        // npomodoro 기반 점수 (최대 +25)
        if ($data['npomodoro'] < 5) {
            $score += (5 - $data['npomodoro']) * 5;
        } else {
            $score -= min(15, ($data['npomodoro'] - 5) * 3);
        }

        // tlaststroke 기반 점수 (최대 +25)
        if ($data['tlaststroke_min'] !== null) {
            if ($data['tlaststroke_min'] >= 30) {
                $score += 25;
            } elseif ($data['tlaststroke_min'] >= 15) {
                $score += 15;
            } elseif ($data['tlaststroke_min'] >= 5) {
                $score += 5;
            }
        }

        // eye_count 기반 점수 (최대 +10)
        $score += min(10, $data['eye_count'] * 3);

        // 연속 고위험 보너스
        if (isset($data['consecutive_high_days']) && $data['consecutive_high_days'] >= 2) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    /**
     * 이탈 방지 권장사항 생성
     *
     * @param string $riskTier 위험 등급
     * @param array $data 이탈 데이터
     * @param int $studyMinutes 학습 시간
     * @return array
     */
    protected function generateDropoutRecommendations(string $riskTier, array $data, int $studyMinutes): array
    {
        $recommendations = [];

        switch ($riskTier) {
            case 'Low':
                $recommendations[] = '현재 학습 패턴이 안정적입니다.';
                $recommendations[] = '목표 달성률을 유지하세요.';
                break;

            case 'Medium':
                $recommendations[] = '짧은 집중 세션(10-15분)으로 리듬을 회복하세요.';
                if ($data['eye_count'] >= 2) {
                    $recommendations[] = '노트를 열어만 두지 말고 적극적으로 활용해보세요.';
                }
                break;

            case 'High':
                $recommendations[] = '지금 바로 5분 타이머를 설정하고 한 문제만 풀어보세요.';
                $recommendations[] = '오늘 목표를 더 작게 나눠보는 건 어떨까요?';
                if ($data['tlaststroke_min'] !== null && $data['tlaststroke_min'] >= 30) {
                    $recommendations[] = '휴식이 필요하면 타이머로 휴식 시간을 정해보세요.';
                }
                break;

            case 'Critical':
                $recommendations[] = '지금은 컨디션 회복이 우선입니다.';
                $recommendations[] = '선생님이나 부모님께 도움을 요청해도 괜찮아요.';
                $recommendations[] = '오늘은 작은 것 하나만 해도 충분해요.';
                break;
        }

        return $recommendations;
    }

    /**
     * 이탈 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 기간 (일)
     * @return array
     */
    public function getDropoutStats(int $userId, int $days = 7): array
    {
        return $this->dataContext->getDropoutStats($userId, $days);
    }

    /**
     * 현재 위험 등급 조회
     *
     * @param int $userId 사용자 ID
     * @return array
     */
    public function getCurrentRiskInfo(int $userId): array
    {
        $dropoutData = $this->dataContext->getDropoutIndicators($userId);
        $riskTier = $this->calculateRiskTier($dropoutData);
        $riskScore = $this->calculateRiskScore($dropoutData);

        return [
            'risk_tier' => $riskTier,
            'risk_score' => $riskScore,
            'indicators' => $dropoutData,
            'last_activity' => $dropoutData['tlaststroke_min'] !== null
                ? time() - ($dropoutData['tlaststroke_min'] * 60)
                : null
        ];
    }

    /**
     * 위험 추세 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 기간 (일)
     * @return array
     */
    public function getRiskTrend(int $userId, int $days = 7): array
    {
        return $this->dataContext->getRiskTrend($userId, $days);
    }

    /**
     * 현재 개입 전략 조회
     *
     * @param int $userId 사용자 ID
     * @return array
     */
    public function getCurrentStrategy(int $userId): array
    {
        $persona = $this->identifyPersona($userId);
        return [
            'persona' => $persona,
            'strategy' => $this->interventionStrategies[$persona['persona_code']],
            'templates' => $this->coachingTemplates[$persona['persona_code']]
        ];
    }

    /**
     * 에이전트 간 통신: 이탈 위험 알림
     *
     * @param int $userId 사용자 ID
     * @param string $riskTier 위험 등급
     * @return void
     */
    public function broadcastDropoutRisk(int $userId, string $riskTier): void
    {
        if ($riskTier === 'High' || $riskTier === 'Critical') {
            // Agent05 (학습 감정)에 알림
            $this->sendInterAgentMessage(5, 'dropout_risk_detected', [
                'user_id' => $userId,
                'risk_tier' => $riskTier,
                'timestamp' => time()
            ]);

            // Agent12 (휴식 루틴)에 알림
            $this->sendInterAgentMessage(12, 'dropout_risk_detected', [
                'user_id' => $userId,
                'risk_tier' => $riskTier,
                'suggest_rest' => $riskTier === 'Critical',
                'timestamp' => time()
            ]);

            // Broadcast (전체)
            $this->broadcastMessage('dropout_risk_alert', [
                'user_id' => $userId,
                'risk_tier' => $riskTier,
                'agent_source' => 13,
                'timestamp' => time()
            ]);
        }
    }

    /**
     * 헬스 체크
     *
     * @return array
     */
    public function healthCheck(): array
    {
        $healthy = true;
        $checks = [];

        // DB 연결 체크
        try {
            $dbOk = $this->dataContext->checkConnection();
            $checks['database'] = $dbOk ? 'OK' : 'FAIL';
            if (!$dbOk) $healthy = false;
        } catch (Exception $e) {
            $checks['database'] = 'ERROR: ' . $e->getMessage();
            $healthy = false;
        }

        // 필수 테이블 체크
        $requiredTables = [
            'mdl_abessi_today',
            'mdl_abessi_messages',
            'mdl_abessi_tracking',
            'mdl_abessi_indicators'
        ];

        foreach ($requiredTables as $table) {
            try {
                $exists = $this->dataContext->tableExists($table);
                $checks['table_' . $table] = $exists ? 'OK' : 'MISSING';
                if (!$exists) $healthy = false;
            } catch (Exception $e) {
                $checks['table_' . $table] = 'ERROR';
                $healthy = false;
            }
        }

        // 설정 체크
        $checks['config'] = !empty($this->config) ? 'OK' : 'MISSING';
        $checks['risk_levels'] = count($this->dropoutRiskLevels) === 4 ? 'OK' : 'INCOMPLETE';
        $checks['strategies'] = count($this->interventionStrategies) === 4 ? 'OK' : 'INCOMPLETE';

        // 혼합형 페르소나 체크
        $checks['dropout_causes'] = count($this->dropoutCauses) === 4 ? 'OK' : 'INCOMPLETE';
        $checks['hybrid_personas'] = count($this->hybridPersonas) === 12 ? 'OK' : 'INCOMPLETE';

        return [
            'healthy' => $healthy,
            'agent' => 13,
            'agent_name' => 'learning_dropout',
            'version' => '2.0.0', // 혼합형 페르소나 지원 버전
            'checks' => $checks,
            'hybrid_support' => true,
            'timestamp' => time()
        ];
    }

    /**
     * 혼합형 페르소나 목록 조회 (API용)
     *
     * @return array
     */
    public function getHybridPersonasList(): array
    {
        $result = [];
        foreach ($this->hybridPersonas as $id => $persona) {
            $result[$id] = [
                'id' => $id,
                'name' => $persona['name'],
                'name_en' => $persona['name_en'],
                'risk_tier' => $persona['risk_tier'],
                'cause' => $persona['cause'],
                'cause_name' => $this->dropoutCauses[$persona['cause']]['name'],
                'intervention_mode' => $persona['intervention_mode']
            ];
        }
        return $result;
    }

    /**
     * 이탈 원인 목록 조회 (API용)
     *
     * @return array
     */
    public function getDropoutCausesList(): array
    {
        $result = [];
        foreach ($this->dropoutCauses as $code => $cause) {
            $result[$code] = [
                'code' => $code,
                'name' => $cause['name'],
                'name_en' => $cause['name_en'],
                'description' => $cause['description'],
                'priority' => $cause['priority']
            ];
        }
        return $result;
    }
}

/*
 * =========================================================================
 * 관련 DB 테이블 (읽기 전용)
 * =========================================================================
 *
 * 1. mdl_abessi_today
 *    - userid: BIGINT - 사용자 ID
 *    - ninactive: INT - 비활성 횟수
 *    - nlazy: INT - 게으름 횟수
 *    - activetime: INT - 활동 시간
 *    - checktime: INT - 확인 시간
 *    - status: VARCHAR - 상태
 *    - type: VARCHAR - 유형
 *    - timecreated: INT - 생성 시간
 *
 * 2. mdl_abessi_messages
 *    - userid: BIGINT - 사용자 ID
 *    - timemodified: INT - 수정 시간
 *    - tlaststroke: INT - 마지막 필기 시점
 *
 * 3. mdl_abessi_tracking
 *    - userid: BIGINT - 사용자 ID
 *    - status: VARCHAR - 상태
 *    - timecreated: INT - 생성 시간
 *    - duration: INT - 지속 시간
 *
 * 4. mdl_abessi_indicators
 *    - userid: BIGINT - 사용자 ID
 *    - npomodoro: INT - 포모도로 횟수
 *    - kpomodoro: INT - 완료 포모도로 횟수
 *    - pmresult: VARCHAR - 포모도로 결과
 *    - timecreated: INT - 생성 시간
 *
 * 신규 테이블 (쓰기)
 * =========================================================================
 *
 * 5. mdl_at_agent13_dropout_risk
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - user_id: BIGINT - 사용자 ID
 *    - risk_tier: VARCHAR(20) - 위험 등급
 *    - risk_score: DECIMAL(5,2) - 위험 점수
 *    - ninactive: INT - 비활성 횟수
 *    - eye_count: INT - 지연 시청 횟수
 *    - tlaststroke_min: INT - 무입력 시간(분)
 *    - npomodoro: INT - 포모도로 횟수
 *    - timecreated: INT - 생성 시간
 *
 * 6. mdl_at_agent13_intervention_log
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - user_id: BIGINT - 사용자 ID
 *    - persona_code: VARCHAR(30) - 페르소나 코드
 *    - risk_tier: VARCHAR(20) - 위험 등급
 *    - intervention_type: VARCHAR(50) - 개입 유형
 *    - message_preview: TEXT - 메시지 미리보기
 *    - actions: JSON - 실행 액션
 *    - timecreated: INT - 생성 시간
 *
 * =========================================================================
 */
