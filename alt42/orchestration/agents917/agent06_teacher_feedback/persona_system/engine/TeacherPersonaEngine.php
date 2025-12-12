<?php
/**
 * TeacherPersonaEngine - 선생님 피드백 에이전트(Agent06)의 페르소나 엔진
 *
 * AbstractPersonaEngine을 상속하여 Agent06 특화 기능 구현
 * 학생 페르소나(Agent01)를 기반으로 최적의 선생님 페르소나를 매칭하고
 * 상황별(T1-T5) 피드백 생성
 *
 * @package AugmentedTeacher\Agents\Agent06\PersonaEngine
 * @version 1.0.0
 * @author Claude Code
 *
 * 주요 기능:
 * - 학생 페르소나 기반 선생님 페르소나 매칭
 * - T1(격려), T2(교정), T3(학습설계), T4(감정지원), T5(성과평가) 피드백
 * - Agent01과의 DB 기반 데이터 연동
 * - 실시간 페르소나 전환 (긴급 상황 대응)
 *
 * 실행 URL:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/engine/TeacherPersonaEngine.php
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 및 컴포넌트 로드
$basePath = __DIR__ . '/../../../../ontology_engineering/persona_engine';

// 코어 클래스
require_once($basePath . '/core/AbstractPersonaEngine.php');
require_once($basePath . '/core/IConditionEvaluator.php');
require_once($basePath . '/core/IActionExecutor.php');
require_once($basePath . '/core/IRuleParser.php');
require_once($basePath . '/core/IDataContext.php');
require_once($basePath . '/core/IResponseGenerator.php');

// 구현체 클래스
require_once($basePath . '/impl/BaseConditionEvaluator.php');
require_once($basePath . '/impl/BaseActionExecutor.php');
require_once($basePath . '/impl/YamlRuleParser.php');
require_once($basePath . '/impl/MoodleDataContext.php');
require_once($basePath . '/impl/TemplateResponseGenerator.php');

// 통신 모듈
require_once($basePath . '/communication/AgentMessenger.php');
require_once($basePath . '/communication/AgentStateSync.php');

use AugmentedTeacher\PersonaEngine\Core\AbstractPersonaEngine;
use AugmentedTeacher\PersonaEngine\Impl\BaseConditionEvaluator;
use AugmentedTeacher\PersonaEngine\Impl\BaseActionExecutor;
use AugmentedTeacher\PersonaEngine\Impl\YamlRuleParser;
use AugmentedTeacher\PersonaEngine\Impl\MoodleDataContext;
use AugmentedTeacher\PersonaEngine\Impl\TemplateResponseGenerator;

class TeacherPersonaEngine extends AbstractPersonaEngine {

    /** @var string 에이전트 ID (고정값) */
    protected $agentId = '06';

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var AgentMessenger 에이전트 간 메시징 */
    private $messenger;

    /** @var AgentStateSync 상태 동기화 */
    private $stateSync;

    /** @var array 선생님 페르소나 정의 */
    private $teacherPersonas = [];

    /** @var array 학생-선생님 페르소나 매칭 규칙 */
    private $matchingRules = [];

    /** @var array 상황별 피드백 유형 */
    private $situationTypes = [
        'T1' => '격려/칭찬',
        'T2' => '오류 교정/안내',
        'T3' => '학습 설계/추천',
        'T4' => '감정 지원/상담',
        'T5' => '성과 평가/리포트'
    ];

    /** @var array 톤 수정자 (선생님 특화) */
    private $teacherToneModifiers = [
        'Warm' => [
            'suffix' => '요',
            'greeting' => '안녕하세요!',
            'closing' => '잘하고 있어요. 화이팅!'
        ],
        'Encouraging' => [
            'suffix' => '요',
            'greeting' => '안녕!',
            'closing' => '넌 할 수 있어! 믿어!'
        ],
        'Professional' => [
            'suffix' => '습니다',
            'greeting' => '안녕하세요.',
            'closing' => '궁금한 점이 있으시면 말씀해 주세요.'
        ],
        'Empathetic' => [
            'suffix' => '요',
            'greeting' => '안녕하세요...',
            'closing' => '힘들면 언제든 얘기해요.'
        ],
        'Reassuring' => [
            'suffix' => '요',
            'greeting' => '안녕하세요.',
            'closing' => '괜찮아요, 천천히 해도 돼요.'
        ],
        'Analytical' => [
            'suffix' => '습니다',
            'greeting' => '안녕하세요.',
            'closing' => '분석 결과를 바탕으로 개선점을 찾아봅시다.'
        ]
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 배열
     */
    public function __construct(array $config = []) {
        parent::__construct('06', array_merge([
            'debug_mode' => false,
            'log_enabled' => true,
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'auto_sync_with_agent01' => true
        ], $config));

        $this->loadTeacherPersonaDefinitions();
    }

    /**
     * 컴포넌트 초기화
     */
    protected function initializeComponents(): void {
        global $DB;

        // 기본 컴포넌트 초기화
        $this->conditionEvaluator = new BaseConditionEvaluator($this->config['debug_mode']);
        $this->actionExecutor = new BaseActionExecutor($this->config['debug_mode']);
        $this->ruleParser = new YamlRuleParser($this->config['debug_mode'], $this->config['cache_enabled']);
        $this->dataContext = new MoodleDataContext($this->config['debug_mode']);
        $this->responseGenerator = new TemplateResponseGenerator($this->config['debug_mode']);

        // 에이전트 통신 모듈 초기화
        $this->messenger = new AgentMessenger($DB);
        $this->stateSync = new AgentStateSync($DB);

        // 규칙 파일 로드
        $rulesPath = __DIR__ . '/../rules.yaml';
        if (file_exists($rulesPath)) {
            $this->loadRules($rulesPath);
            $this->extractMatchingRules();
        }

        // 선생님 특화 톤 추가
        foreach ($this->teacherToneModifiers as $tone => $modifiers) {
            $this->responseGenerator->addToneModifier($tone, $modifiers);
        }

        // 선생님 특화 기본 응답 추가
        $this->initializeTeacherResponses();
    }

    /**
     * 선생님 페르소나 정의 로드
     */
    private function loadTeacherPersonaDefinitions(): void {
        $this->teacherPersonas = [
            // T0 - 일반 상황
            'T0_P1' => [
                'id' => 'T0_P1',
                'name' => '따뜻한 격려자',
                'description' => '따뜻하고 격려하는 선생님',
                'tone' => 'Warm',
                'intervention_style' => 'PositiveReinforcement',
                'priority' => 50
            ],
            'T0_P2' => [
                'id' => 'T0_P2',
                'name' => '차분한 안내자',
                'description' => '차분하고 체계적인 선생님',
                'tone' => 'Professional',
                'intervention_style' => 'InformationProvision',
                'priority' => 50
            ],
            'T0_P3' => [
                'id' => 'T0_P3',
                'name' => '열정적 코치',
                'description' => '열정적이고 적극적인 선생님',
                'tone' => 'Encouraging',
                'intervention_style' => 'Motivation',
                'priority' => 50
            ],

            // T1 - 격려 상황
            'T1_P1' => [
                'id' => 'T1_P1',
                'name' => '칭찬 마스터',
                'description' => '구체적인 칭찬을 통한 동기 부여',
                'tone' => 'Encouraging',
                'intervention_style' => 'SpecificPraise',
                'priority' => 70
            ],
            'T1_P2' => [
                'id' => 'T1_P2',
                'name' => '성취 인정자',
                'description' => '작은 성취도 인정하는 선생님',
                'tone' => 'Warm',
                'intervention_style' => 'AchievementRecognition',
                'priority' => 70
            ],

            // T2 - 오류 교정 상황
            'T2_P1' => [
                'id' => 'T2_P1',
                'name' => '부드러운 교정자',
                'description' => '부드럽게 오류를 짚어주는 선생님',
                'tone' => 'Warm',
                'intervention_style' => 'GentleCorrection',
                'priority' => 75
            ],
            'T2_P2' => [
                'id' => 'T2_P2',
                'name' => '체계적 분석가',
                'description' => '왜 틀렸는지 체계적으로 설명',
                'tone' => 'Professional',
                'intervention_style' => 'AnalyticalFeedback',
                'priority' => 75
            ],

            // T3 - 학습 설계 상황
            'T3_P1' => [
                'id' => 'T3_P1',
                'name' => '학습 설계자',
                'description' => '맞춤형 학습 경로 설계',
                'tone' => 'Professional',
                'intervention_style' => 'LearningDesign',
                'priority' => 65
            ],
            'T3_P2' => [
                'id' => 'T3_P2',
                'name' => '문제 추천자',
                'description' => '수준에 맞는 문제 추천',
                'tone' => 'Warm',
                'intervention_style' => 'ContentRecommendation',
                'priority' => 65
            ],

            // T4 - 감정 지원 상황
            'T4_P1' => [
                'id' => 'T4_P1',
                'name' => '공감적 상담자',
                'description' => '감정을 공감하고 지지',
                'tone' => 'Empathetic',
                'intervention_style' => 'EmotionalSupport',
                'priority' => 85
            ],
            'T4_P2' => [
                'id' => 'T4_P2',
                'name' => '에너지 회복 코치',
                'description' => '번아웃 회복 및 동기 재충전',
                'tone' => 'Reassuring',
                'intervention_style' => 'EnergyRecovery',
                'priority' => 85
            ],

            // T5 - 성과 평가 상황
            'T5_P1' => [
                'id' => 'T5_P1',
                'name' => '균형적 평가자',
                'description' => '강점과 약점을 균형있게 피드백',
                'tone' => 'Professional',
                'intervention_style' => 'BalancedAssessment',
                'priority' => 60
            ],
            'T5_P2' => [
                'id' => 'T5_P2',
                'name' => '데이터 분석가',
                'description' => '학습 데이터 기반 분석',
                'tone' => 'Analytical',
                'intervention_style' => 'DataDrivenFeedback',
                'priority' => 60
            ],

            // E-Series - 긴급 상황
            'E_CRISIS' => [
                'id' => 'E_CRISIS',
                'name' => '위기 대응자',
                'description' => '긴급 심리 지원 및 전문가 연결',
                'tone' => 'Empathetic',
                'intervention_style' => 'CrisisIntervention',
                'priority' => 100
            ],
            'E_BURNOUT' => [
                'id' => 'E_BURNOUT',
                'name' => '회복 지원자',
                'description' => '심각한 번아웃 상태 회복 지원',
                'tone' => 'Reassuring',
                'intervention_style' => 'RecoverySupport',
                'priority' => 95
            ]
        ];
    }

    /**
     * 매칭 규칙 추출
     */
    private function extractMatchingRules(): void {
        // rules.yaml에서 persona_matching_rules 추출
        if (isset($this->rules['persona_matching_rules'])) {
            $this->matchingRules = $this->rules['persona_matching_rules'];
        }

        // 기본 매칭 규칙 (rules.yaml에 없을 경우)
        if (empty($this->matchingRules)) {
            $this->matchingRules = [
                // 적극적인 학생 → 따뜻한 격려자
                ['student_persona' => 'S0_P1', 'teacher_persona' => 'T0_P1', 'priority' => 80],
                // 불안한 학생 → 공감적 상담자
                ['student_persona' => 'S0_P4', 'teacher_persona' => 'T4_P1', 'priority' => 90],
                // 번아웃 학생 → 에너지 회복 코치
                ['student_persona' => 'S4_P1', 'teacher_persona' => 'T4_P2', 'priority' => 95],
                // 좌절한 학생 → 부드러운 교정자
                ['student_persona' => 'S2_P1', 'teacher_persona' => 'T2_P1', 'priority' => 85],
                // 기본값
                ['student_persona' => '*', 'teacher_persona' => 'T0_P2', 'priority' => 10]
            ];
        }
    }

    /**
     * 선생님 특화 기본 응답 초기화
     */
    private function initializeTeacherResponses(): void {
        // T1 - 격려 상황 응답
        $this->responseGenerator->addDefaultResponses('encouragement', [
            '{{firstname}}님, 정말 잘했어요! 이 문제를 풀 수 있는 실력이 있어요.',
            '멋져요! {{firstname}}님의 노력이 결실을 맺고 있어요.',
            '와, 대단해요! 정확하게 풀었네요. 이 기세로 계속 가봐요!'
        ]);

        // T2 - 오류 교정 응답
        $this->responseGenerator->addDefaultResponses('correction', [
            '아쉽지만 한 번 더 생각해볼까요? {{firstname}}님이라면 분명 찾을 수 있어요.',
            '조금 아쉬워요. 이 부분을 다시 한 번 살펴보면 어떨까요?',
            '틀린 건 괜찮아요! 왜 틀렸는지 함께 알아보면 더 잘 이해할 수 있어요.'
        ]);

        // T3 - 학습 설계 응답
        $this->responseGenerator->addDefaultResponses('learning_design', [
            '{{firstname}}님의 학습 패턴을 분석해봤어요. 다음 단계로 이 문제들을 추천해 드릴게요.',
            '지금까지의 진도를 보면, {{firstname}}님에게는 이런 유형의 문제가 도움이 될 것 같아요.',
            '{{firstname}}님의 강점을 살려서 학습 계획을 세워봤어요.'
        ]);

        // T4 - 감정 지원 응답
        $this->responseGenerator->addDefaultResponses('emotional_support', [
            '{{firstname}}님, 힘드시죠? 괜찮아요, 잠시 쉬어가도 좋아요.',
            '지치셨군요. 오늘은 여기까지 하고, 내일 다시 시작해도 괜찮아요.',
            '{{firstname}}님의 노력을 알아요. 천천히 해도 결국 목표에 도달할 거예요.'
        ]);

        // T5 - 성과 평가 응답
        $this->responseGenerator->addDefaultResponses('performance_review', [
            '{{firstname}}님의 이번 주 학습 분석 결과입니다. 강점과 개선점을 함께 확인해 보세요.',
            '{{firstname}}님은 특히 이 영역에서 크게 발전했어요. 그 비결이 뭘까요?',
            '지난 달 대비 정답률이 향상되었어요. {{firstname}}님의 꾸준한 노력 덕분이에요!'
        ]);

        // 긴급 상황 응답
        $this->responseGenerator->addDefaultResponses('crisis', [
            '{{firstname}}님, 지금 많이 힘드시죠? 제가 여기 있을게요. 천천히 이야기해 주세요.',
            '걱정돼요. 지금 상태가 어떠신가요? 전문 상담사와 연결해 드릴 수도 있어요.',
            '{{firstname}}님의 안전이 가장 중요해요. 함께 도움을 받을 수 있는 방법을 찾아볼까요?'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPersonaDefinitions(): array {
        return $this->teacherPersonas;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultPersonaId(): string {
        return 'T0_P2'; // 차분한 안내자
    }

    /**
     * 메인 프로세스 오버라이드 - Agent01 데이터 연동 추가
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지 (또는 상황 트리거)
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $startTime = microtime(true);

        try {
            // 1. 컨텍스트 로드
            $context = $this->dataContext->loadContext($userId, $sessionData);

            // 2. Agent01에서 학생 페르소나 가져오기
            $studentPersona = $this->getStudentPersonaFromAgent01($userId);
            $context['student_persona'] = $studentPersona;

            // 3. 컨텍스트 보강 (선생님 에이전트 특화)
            $context = $this->enrichTeacherContext($context, $message);

            // 4. 상황 분석 (T1-T5 또는 E-Series)
            $situation = $this->analyzeSituation($message, $context);
            $context['current_situation'] = $situation;

            // 5. 선생님 페르소나 매칭
            $teacherPersona = $this->matchTeacherPersona($studentPersona, $situation, $context);

            // 6. 피드백 응답 생성
            $response = $this->generateTeacherFeedback($message, $teacherPersona, $context);

            // 7. 상태 저장 및 Agent01에 동기화
            $this->saveState($userId, $teacherPersona);
            $this->syncToAgent01($userId, $teacherPersona, $situation);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'user_id' => $userId,
                'agent_id' => $this->agentId,
                'student_persona' => $studentPersona,
                'teacher_persona' => $teacherPersona,
                'situation' => $situation,
                'response' => $response,
                'meta' => [
                    'processing_time_ms' => $processingTime,
                    'timestamp' => time()
                ]
            ];

        } catch (\Exception $e) {
            $this->logError("프로세스 실행 실패: " . $e->getMessage(), __LINE__);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . __LINE__,
                'user_id' => $userId,
                'agent_id' => $this->agentId
            ];
        }
    }

    /**
     * Agent01에서 학생 페르소나 조회
     *
     * @param int $userId 사용자 ID
     * @return array 학생 페르소나 정보
     */
    private function getStudentPersonaFromAgent01(int $userId): array {
        global $DB;

        try {
            // DB에서 Agent01의 상태 조회
            $state = $this->stateSync->getState($userId, '01');

            if ($state) {
                return [
                    'persona_id' => $state['persona_id'] ?? 'S0_P2',
                    'situation' => $state['state_data']['current_situation'] ?? 'S1',
                    'emotion' => $state['state_data']['detected_emotion'] ?? 'neutral',
                    'emotion_intensity' => $state['state_data']['emotion_intensity'] ?? 0.5,
                    'confidence_level' => $state['state_data']['confidence_level'] ?? 'medium',
                    'last_updated' => $state['timemodified'] ?? time()
                ];
            }

            // 상태 없으면 Agent01에 요청 메시지 전송
            if ($this->config['auto_sync_with_agent01']) {
                $this->messenger->send(6, 1, 'data_request', [
                    'request_type' => 'persona_state',
                    'user_id' => $userId
                ], $userId, 2);
            }

            // 기본값 반환
            return [
                'persona_id' => 'S0_P2',
                'situation' => 'S1',
                'emotion' => 'neutral',
                'emotion_intensity' => 0.5,
                'confidence_level' => 'medium',
                'last_updated' => 0
            ];

        } catch (\Exception $e) {
            $this->logError("Agent01 페르소나 조회 실패: " . $e->getMessage(), __LINE__);
            return [
                'persona_id' => 'S0_P2',
                'situation' => 'S1',
                'emotion' => 'neutral',
                'emotion_intensity' => 0.5,
                'confidence_level' => 'medium'
            ];
        }
    }

    /**
     * 선생님 에이전트 특화 컨텍스트 보강
     *
     * @param array $context 기본 컨텍스트
     * @param string $message 메시지
     * @return array 보강된 컨텍스트
     */
    private function enrichTeacherContext(array $context, string $message): array {
        // 기본 보강
        $context = $this->enrichContext($context);

        // 메시지에서 감정 감지
        $emotionData = $this->dataContext->detectEmotionFromMessage($message);
        $context['message_emotion'] = $emotionData['emotion'];
        $context['message_emotion_intensity'] = $emotionData['intensity'];

        // 학습 이벤트 유형 감지
        $context['learning_event'] = $this->detectLearningEvent($message, $context);

        // 최근 피드백 이력
        $context['recent_feedback_count'] = $this->getRecentFeedbackCount($context['user']['id'] ?? 0);

        return $context;
    }

    /**
     * 상황 분석 (T1-T5 또는 E-Series 판별)
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return array 상황 정보
     */
    private function analyzeSituation(string $message, array $context): array {
        $studentPersona = $context['student_persona'] ?? [];
        $learningEvent = $context['learning_event'] ?? 'unknown';
        $emotion = $studentPersona['emotion'] ?? 'neutral';
        $emotionIntensity = $studentPersona['emotion_intensity'] ?? 0.5;

        // 긴급 상황 체크 (최우선)
        if ($this->isEmergencySituation($emotion, $emotionIntensity, $message)) {
            return [
                'type' => 'E_CRISIS',
                'name' => '위기 상황',
                'priority' => 100,
                'requires_escalation' => true
            ];
        }

        // 심각한 번아웃 체크
        if ($this->isSevereBurnout($studentPersona, $context)) {
            return [
                'type' => 'E_BURNOUT',
                'name' => '심각한 번아웃',
                'priority' => 95,
                'requires_escalation' => false
            ];
        }

        // 학습 이벤트 기반 상황 판별
        switch ($learningEvent) {
            case 'correct_answer':
            case 'achievement':
            case 'streak':
                return [
                    'type' => 'T1',
                    'name' => $this->situationTypes['T1'],
                    'priority' => 70,
                    'sub_type' => $learningEvent
                ];

            case 'wrong_answer':
            case 'repeated_error':
            case 'misconception':
                return [
                    'type' => 'T2',
                    'name' => $this->situationTypes['T2'],
                    'priority' => 75,
                    'sub_type' => $learningEvent
                ];

            case 'new_topic':
            case 'level_up':
            case 'recommendation_request':
                return [
                    'type' => 'T3',
                    'name' => $this->situationTypes['T3'],
                    'priority' => 65,
                    'sub_type' => $learningEvent
                ];

            case 'emotional_expression':
            case 'help_request':
            case 'frustration':
                return [
                    'type' => 'T4',
                    'name' => $this->situationTypes['T4'],
                    'priority' => 85,
                    'sub_type' => $learningEvent
                ];

            case 'session_end':
            case 'progress_check':
            case 'report_request':
                return [
                    'type' => 'T5',
                    'name' => $this->situationTypes['T5'],
                    'priority' => 60,
                    'sub_type' => $learningEvent
                ];

            default:
                return [
                    'type' => 'T0',
                    'name' => '일반 상황',
                    'priority' => 50,
                    'sub_type' => 'general'
                ];
        }
    }

    /**
     * 선생님 페르소나 매칭
     *
     * @param array $studentPersona 학생 페르소나
     * @param array $situation 상황 정보
     * @param array $context 컨텍스트
     * @return array 매칭된 선생님 페르소나
     */
    private function matchTeacherPersona(array $studentPersona, array $situation, array $context): array {
        $situationType = $situation['type'];
        $studentPersonaId = $studentPersona['persona_id'] ?? 'S0_P2';
        $bestMatch = null;
        $highestPriority = -1;

        // 1. 긴급 상황 최우선 처리
        if (strpos($situationType, 'E_') === 0) {
            $emergencyPersona = $this->teacherPersonas[$situationType] ?? $this->teacherPersonas['E_CRISIS'];
            return array_merge($emergencyPersona, [
                'matched_rule' => 'emergency_override',
                'confidence' => 1.0,
                'match_reason' => "긴급 상황 ({$situationType}) 자동 매칭"
            ]);
        }

        // 2. rules.yaml 매칭 규칙 적용
        foreach ($this->matchingRules as $rule) {
            $ruleStudentPersona = $rule['student_persona'] ?? '*';
            $rulePriority = $rule['priority'] ?? 0;

            // 조건 추가 체크 (상황 타입 등)
            $situationMatch = true;
            if (isset($rule['situation_type']) && $rule['situation_type'] !== $situationType) {
                $situationMatch = false;
            }

            // 학생 페르소나 매칭 (* = 와일드카드)
            $personaMatch = ($ruleStudentPersona === '*' || $ruleStudentPersona === $studentPersonaId);

            if ($personaMatch && $situationMatch && $rulePriority > $highestPriority) {
                $teacherPersonaId = $rule['teacher_persona'] ?? 'T0_P2';
                $bestMatch = $this->teacherPersonas[$teacherPersonaId] ?? null;
                $highestPriority = $rulePriority;
            }
        }

        // 3. 상황 기반 기본 매칭 (규칙 매칭 실패 시)
        if (!$bestMatch) {
            $bestMatch = $this->getDefaultPersonaForSituation($situationType, $studentPersona);
        }

        if (!$bestMatch) {
            $bestMatch = $this->teacherPersonas[$this->getDefaultPersonaId()];
        }

        return array_merge($bestMatch, [
            'matched_rule' => 'situation_based',
            'confidence' => 0.8,
            'match_reason' => "상황({$situationType}) + 학생 페르소나({$studentPersonaId}) 기반 매칭"
        ]);
    }

    /**
     * 상황별 기본 페르소나 반환
     *
     * @param string $situationType 상황 타입
     * @param array $studentPersona 학생 페르소나
     * @return array|null 페르소나 또는 null
     */
    private function getDefaultPersonaForSituation(string $situationType, array $studentPersona): ?array {
        $emotion = $studentPersona['emotion'] ?? 'neutral';

        $situationDefaults = [
            'T0' => 'T0_P2',
            'T1' => 'T1_P1',
            'T2' => ($emotion === 'frustration' || $emotion === 'anxiety') ? 'T2_P1' : 'T2_P2',
            'T3' => 'T3_P1',
            'T4' => 'T4_P1',
            'T5' => 'T5_P1'
        ];

        $personaId = $situationDefaults[$situationType] ?? 'T0_P2';
        return $this->teacherPersonas[$personaId] ?? null;
    }

    /**
     * 선생님 피드백 응답 생성
     *
     * @param string $message 원본 메시지
     * @param array $teacherPersona 선생님 페르소나
     * @param array $context 컨텍스트
     * @return array 응답
     */
    private function generateTeacherFeedback(string $message, array $teacherPersona, array $context): array {
        $situation = $context['current_situation'] ?? [];
        $situationType = $situation['type'] ?? 'T0';

        // 상황별 응답 유형 결정
        $responseType = $this->mapSituationToResponseType($situationType);

        // 응답 생성
        $response = $this->responseGenerator->generate($teacherPersona, array_merge($context, [
            'message' => $message,
            'response_type' => $responseType
        ]));

        // 선생님 특화 후처리
        $response['intervention_style'] = $teacherPersona['intervention_style'] ?? 'InformationProvision';
        $response['situation_type'] = $situationType;
        $response['feedback_category'] = $this->situationTypes[$situationType] ?? '일반';

        return $response;
    }

    /**
     * 상황 타입을 응답 유형으로 매핑
     *
     * @param string $situationType 상황 타입
     * @return string 응답 유형
     */
    private function mapSituationToResponseType(string $situationType): string {
        $mapping = [
            'T1' => 'encouragement',
            'T2' => 'correction',
            'T3' => 'learning_design',
            'T4' => 'emotional_support',
            'T5' => 'performance_review',
            'E_CRISIS' => 'crisis',
            'E_BURNOUT' => 'emotional_support'
        ];

        return $mapping[$situationType] ?? 'neutral';
    }

    /**
     * 학습 이벤트 감지
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return string 학습 이벤트 유형
     */
    private function detectLearningEvent(string $message, array $context): string {
        // 세션 데이터에서 이벤트 타입 확인
        if (isset($context['event_type'])) {
            return $context['event_type'];
        }

        // 메시지 키워드 기반 감지
        $keywords = [
            'correct_answer' => ['정답', '맞았', '맞음', 'correct'],
            'wrong_answer' => ['오답', '틀렸', '틀림', 'wrong', 'incorrect'],
            'achievement' => ['달성', '완료', '클리어', 'achieved', 'completed'],
            'frustration' => ['모르겠', '어려워', '힘들어', '포기', '짜증'],
            'help_request' => ['도와줘', '알려줘', '설명해', '이해가 안', 'help'],
            'progress_check' => ['진도', '얼마나', '성적', '성과', 'progress']
        ];

        foreach ($keywords as $event => $words) {
            foreach ($words as $word) {
                if (mb_strpos($message, $word) !== false) {
                    return $event;
                }
            }
        }

        return 'general';
    }

    /**
     * 긴급 상황 체크
     *
     * @param string $emotion 감정
     * @param float $intensity 감정 강도
     * @param string $message 메시지
     * @return bool 긴급 여부
     */
    private function isEmergencySituation(string $emotion, float $intensity, string $message): bool {
        // 심각한 감정 상태
        if (in_array($emotion, ['crisis', 'despair', 'extreme_anxiety']) && $intensity > 0.8) {
            return true;
        }

        // 위기 키워드 체크
        $crisisKeywords = ['죽고싶', '포기', '더이상', '무의미', '끝내고싶', '자해', '자살'];
        foreach ($crisisKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 심각한 번아웃 체크
     *
     * @param array $studentPersona 학생 페르소나
     * @param array $context 컨텍스트
     * @return bool 심각한 번아웃 여부
     */
    private function isSevereBurnout(array $studentPersona, array $context): bool {
        $personaId = $studentPersona['persona_id'] ?? '';
        $emotion = $studentPersona['emotion'] ?? 'neutral';
        $intensity = $studentPersona['emotion_intensity'] ?? 0.5;

        // 번아웃 페르소나 + 높은 강도
        if (strpos($personaId, 'S4') !== false && $intensity > 0.7) {
            return true;
        }

        // 지속적인 부정적 감정
        if ($emotion === 'burnout' || $emotion === 'exhaustion') {
            return true;
        }

        return false;
    }

    /**
     * 최근 피드백 횟수 조회
     *
     * @param int $userId 사용자 ID
     * @return int 피드백 횟수
     */
    private function getRecentFeedbackCount(int $userId): int {
        global $DB;

        if ($userId <= 0) {
            return 0;
        }

        try {
            $today = strtotime('today');
            return $DB->count_records_sql(
                "SELECT COUNT(*) FROM {at_persona_action_log}
                 WHERE user_id = ? AND agent_id = '06' AND created_at >= ?",
                [$userId, $today]
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Agent01에 상태 동기화
     *
     * @param int $userId 사용자 ID
     * @param array $teacherPersona 선생님 페르소나
     * @param array $situation 상황 정보
     */
    private function syncToAgent01(int $userId, array $teacherPersona, array $situation): void {
        if (!$this->config['auto_sync_with_agent01']) {
            return;
        }

        try {
            $this->messenger->send(6, 1, 'persona_update', [
                'teacher_persona_id' => $teacherPersona['persona_id'] ?? 'T0_P2',
                'teacher_persona_name' => $teacherPersona['name'] ?? '',
                'situation_type' => $situation['type'] ?? 'T0',
                'feedback_given' => true,
                'timestamp' => time()
            ], $userId, 3);

        } catch (\Exception $e) {
            $this->logError("Agent01 동기화 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * Agent01로부터 메시지 처리
     *
     * @param int $userId 사용자 ID
     * @return array 처리된 메시지 목록
     */
    public function processMessagesFromAgent01(int $userId): array {
        $messages = $this->messenger->receive(6, [
            'status' => 'pending',
            'user_id' => $userId,
            'message_type' => 'persona_update'
        ]);

        $results = [];
        foreach ($messages as $msg) {
            // 학생 페르소나 업데이트 반영
            $payload = $msg->payload ?? [];
            if (isset($payload['student_persona_id'])) {
                $results[] = [
                    'message_id' => $msg->id,
                    'action' => 'student_persona_updated',
                    'data' => $payload
                ];
            }

            // 처리 완료 표시
            $this->messenger->markAsProcessed($msg->id, ['processed_by' => 'agent06']);
        }

        return $results;
    }

    /**
     * 디버그 정보 확장
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        $baseInfo = parent::getDebugInfo();

        return array_merge($baseInfo, [
            'teacher_personas_count' => count($this->teacherPersonas),
            'matching_rules_count' => count($this->matchingRules),
            'situation_types' => $this->situationTypes,
            'available_tones' => array_keys($this->teacherToneModifiers)
        ]);
    }

    /**
     * 특정 페르소나 정보 조회
     *
     * @param string $personaId 페르소나 ID
     * @return array|null 페르소나 정보
     */
    public function getPersona(string $personaId): ?array {
        return $this->teacherPersonas[$personaId] ?? null;
    }

    /**
     * 상황별 사용 가능한 페르소나 목록
     *
     * @param string $situationType 상황 타입
     * @return array 페르소나 목록
     */
    public function getPersonasForSituation(string $situationType): array {
        $result = [];

        foreach ($this->teacherPersonas as $id => $persona) {
            if (strpos($id, $situationType) === 0 || strpos($id, 'T0') === 0) {
                $result[$id] = $persona;
            }
        }

        return $result;
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state
 *   - id (bigint): PK
 *   - userid (bigint): 사용자 ID
 *   - agent_id (varchar 20): 에이전트 ID ('06')
 *   - persona_id (varchar 50): 페르소나 ID (T0_P1, T1_P1 등)
 *   - state_data (longtext): JSON 상태 데이터
 *   - version (int): 버전
 *   - timecreated (bigint): 생성 시간
 *   - timemodified (bigint): 수정 시간
 *
 * - at_agent_messages
 *   - id (bigint): PK
 *   - from_agent (int): 발신 에이전트 (6)
 *   - to_agent (int): 수신 에이전트 (1)
 *   - user_id (int): 사용자 ID
 *   - message_type (varchar): 메시지 유형
 *   - payload (text): JSON 페이로드
 *   - priority (int): 우선순위
 *   - status (varchar): 상태
 *   - created_at (datetime): 생성 시간
 *
 * - at_persona_action_log
 *   - id (bigint): PK
 *   - agent_id (varchar 20): 에이전트 ID
 *   - action_type (varchar 50): 액션 유형
 *   - user_id (bigint): 사용자 ID
 *   - created_at (bigint): 실행 시간
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/core/AbstractPersonaEngine.php
 * - ontology_engineering/persona_engine/communication/AgentMessenger.php
 * - ontology_engineering/persona_engine/communication/AgentStateSync.php
 * - agents/agent06_teacher_feedback/persona_system/rules.yaml
 * - agents/agent06_teacher_feedback/persona_system/personas.md
 * - agents/agent06_teacher_feedback/persona_system/contextlist.md
 */
