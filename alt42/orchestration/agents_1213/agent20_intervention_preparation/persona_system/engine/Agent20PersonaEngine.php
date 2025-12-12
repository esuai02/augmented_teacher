<?php
/**
 * Agent20PersonaEngine - 개입 준비 에이전트 페르소나 엔진
 *
 * Agent20 (Intervention Preparation)의 페르소나 기반 행동을 관리합니다.
 * 학생 상태에 따른 적절한 개입 전략을 준비하고 다른 에이전트와 협력합니다.
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/engine/Agent20PersonaEngine.php
 *
 * @package AugmentedTeacher\Agent20\PersonaSystem
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 컴포넌트 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/RuleParser.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/ConditionEvaluator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/ActionExecutor.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/DataContext.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/ResponseGenerator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/comm/AgentCommunicator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/comm/PersonaEventPublisher.php');

class Agent20PersonaEngine extends AbstractPersonaEngine {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var AgentCommunicator 에이전트 통신기 */
    private $communicator;

    /** @var PersonaEventPublisher 이벤트 발행기 */
    private $eventPublisher;

    /** @var array 개입 전략 정의 */
    private $interventionStrategies = [];

    /**
     * Agent20 전용 설정으로 초기화
     */
    public function __construct(array $config = []) {
        // Agent20 기본 설정
        $defaultConfig = [
            'debug_mode' => false,
            'log_enabled' => true,
            'cache_enabled' => true,
            'intervention_threshold' => 0.6,
            'max_intervention_per_session' => 5,
            'cooldown_minutes' => 10
        ];

        parent::__construct('agent20', array_merge($defaultConfig, $config));

        // 통신 모듈 초기화
        $this->communicator = new AgentCommunicator('agent20');
        $this->eventPublisher = new PersonaEventPublisher('agent20');

        // 개입 전략 초기화
        $this->initializeInterventionStrategies();
    }

    /**
     * 컴포넌트 초기화 (AbstractPersonaEngine 추상 메서드 구현)
     */
    protected function initializeComponents(): void {
        $this->ruleParser = new RuleParser();
        $this->conditionEvaluator = new ConditionEvaluator();
        $this->actionExecutor = new ActionExecutor();
        $this->dataContext = new DataContext($this->agentId);
        $this->responseGenerator = new ResponseGenerator();

        // Agent20 전용 액션 핸들러 등록
        $this->registerAgent20ActionHandlers();
    }

    /**
     * 규칙 파일 경로 반환 (AbstractPersonaEngine 추상 메서드 구현)
     */
    protected function getRulesPath(): string {
        return __DIR__ . '/../rules/rules.yaml';
    }

    /**
     * Agent20 전용 액션 핸들러 등록
     */
    private function registerAgent20ActionHandlers(): void {
        // 개입 준비 액션
        $this->actionExecutor->registerHandler('prepare_intervention', function($params, $context) {
            return $this->prepareIntervention($params, $context);
        });

        // 다른 에이전트에 개입 요청
        $this->actionExecutor->registerHandler('request_intervention', function($params, $context) {
            return $this->requestIntervention($params, $context);
        });

        // 개입 전략 선택
        $this->actionExecutor->registerHandler('select_strategy', function($params, $context) {
            return $this->selectStrategy($params, $context);
        });

        // 에이전트 협력 요청
        $this->actionExecutor->registerHandler('collaborate', function($params, $context) {
            return $this->requestCollaboration($params, $context);
        });

        // 개입 우선순위 조정
        $this->actionExecutor->registerHandler('adjust_priority', function($params, $context) {
            return $this->adjustPriority($params, $context);
        });
    }

    /**
     * 개입 전략 초기화
     */
    private function initializeInterventionStrategies(): void {
        $this->interventionStrategies = [
            'emotional_support' => [
                'description' => '정서적 지원이 필요한 학생을 위한 전략',
                'triggers' => ['anxiety', 'frustration', 'low_confidence'],
                'priority' => 8,
                'target_agent' => 'agent21',
                'actions' => ['send_encouragement', 'reduce_pressure', 'offer_break']
            ],
            'cognitive_scaffolding' => [
                'description' => '학습 지원이 필요한 학생을 위한 전략',
                'triggers' => ['confusion', 'repeated_errors', 'slow_progress'],
                'priority' => 7,
                'target_agent' => 'agent19',
                'actions' => ['provide_hints', 'simplify_problem', 'offer_examples']
            ],
            'motivation_boost' => [
                'description' => '동기 부여가 필요한 학생을 위한 전략',
                'triggers' => ['boredom', 'disengagement', 'low_effort'],
                'priority' => 6,
                'target_agent' => 'agent18',
                'actions' => ['gamify_task', 'set_challenge', 'show_progress']
            ],
            'behavior_guidance' => [
                'description' => '행동 안내가 필요한 학생을 위한 전략',
                'triggers' => ['off_task', 'rushing', 'skipping_steps'],
                'priority' => 5,
                'target_agent' => 'agent17',
                'actions' => ['redirect_attention', 'slow_down', 'review_steps']
            ],
            'immediate_help' => [
                'description' => '즉각적인 도움이 필요한 학생을 위한 전략',
                'triggers' => ['stuck', 'help_request', 'critical_error'],
                'priority' => 9,
                'target_agent' => 'agent21',
                'actions' => ['provide_solution', 'connect_teacher', 'offer_alternative']
            ]
        ];
    }

    /**
     * 학생 상태 분석 및 개입 준비
     *
     * @param int $userId 사용자 ID
     * @param array $studentState 학생 현재 상태
     * @return array 개입 준비 결과
     */
    public function analyzeAndPrepare(int $userId, array $studentState): array {
        try {
            // 학생 컨텍스트 로드
            $context = $this->dataContext->loadStudentContext($userId);
            $context = array_merge($context, $studentState);

            // 규칙 기반 분석
            $analysis = $this->analyzeStudentState($context);

            // 개입 필요성 판단
            $needsIntervention = $this->evaluateInterventionNeed($analysis);

            if ($needsIntervention) {
                // 적절한 전략 선택
                $strategy = $this->selectBestStrategy($analysis);

                // 개입 준비
                $intervention = $this->prepareInterventionPlan($userId, $strategy, $context);

                // 이벤트 발행
                $this->eventPublisher->publish('intervention_prepared', [
                    'user_id' => $userId,
                    'strategy' => $strategy['name'] ?? 'default',
                    'priority' => $strategy['priority'] ?? 5
                ], [$strategy['target_agent'] ?? 'agent21']);

                return [
                    'success' => true,
                    'needs_intervention' => true,
                    'strategy' => $strategy,
                    'intervention' => $intervention,
                    'analysis' => $analysis
                ];
            }

            return [
                'success' => true,
                'needs_intervention' => false,
                'analysis' => $analysis,
                'reason' => 'No intervention needed at this time'
            ];

        } catch (Exception $e) {
            $this->logError("analyzeAndPrepare 오류: " . $e->getMessage(), __LINE__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 학생 상태 분석
     */
    private function analyzeStudentState(array $context): array {
        $analysis = [
            'emotional_state' => $context['emotion'] ?? 'neutral',
            'cognitive_load' => $context['cognitive_load'] ?? 0.5,
            'engagement_level' => $context['engagement'] ?? 0.7,
            'performance_trend' => $context['performance_trend'] ?? 'stable',
            'error_rate' => $context['error_rate'] ?? 0,
            'time_on_task' => $context['time_on_task'] ?? 0,
            'help_requests' => $context['help_requests'] ?? 0
        ];

        // 위험 신호 감지
        $analysis['risk_signals'] = [];

        if ($analysis['emotional_state'] === 'frustration' || $analysis['emotional_state'] === 'anxiety') {
            $analysis['risk_signals'][] = $analysis['emotional_state'];
        }

        if ($analysis['cognitive_load'] > 0.8) {
            $analysis['risk_signals'][] = 'high_cognitive_load';
        }

        if ($analysis['engagement_level'] < 0.3) {
            $analysis['risk_signals'][] = 'low_engagement';
        }

        if ($analysis['error_rate'] > 0.5) {
            $analysis['risk_signals'][] = 'high_error_rate';
        }

        // 종합 점수 계산
        $analysis['intervention_score'] = $this->calculateInterventionScore($analysis);

        return $analysis;
    }

    /**
     * 개입 필요성 점수 계산
     */
    private function calculateInterventionScore(array $analysis): float {
        $score = 0.0;

        // 감정 상태 가중치
        $emotionWeights = [
            'frustration' => 0.3,
            'anxiety' => 0.25,
            'confusion' => 0.2,
            'boredom' => 0.15,
            'neutral' => 0
        ];
        $score += $emotionWeights[$analysis['emotional_state']] ?? 0;

        // 인지 부하 가중치
        $score += ($analysis['cognitive_load'] - 0.5) * 0.3;

        // 참여도 가중치 (낮을수록 높은 점수)
        $score += (1 - $analysis['engagement_level']) * 0.25;

        // 오류율 가중치
        $score += $analysis['error_rate'] * 0.15;

        return min(1.0, max(0.0, $score));
    }

    /**
     * 개입 필요성 평가
     */
    private function evaluateInterventionNeed(array $analysis): bool {
        $threshold = $this->config['intervention_threshold'] ?? 0.6;
        return $analysis['intervention_score'] >= $threshold;
    }

    /**
     * 최적 전략 선택
     */
    private function selectBestStrategy(array $analysis): array {
        $bestStrategy = null;
        $highestMatch = 0;

        foreach ($this->interventionStrategies as $name => $strategy) {
            $matchScore = 0;

            foreach ($strategy['triggers'] as $trigger) {
                if (in_array($trigger, $analysis['risk_signals'])) {
                    $matchScore += 1;
                }
                if ($analysis['emotional_state'] === $trigger) {
                    $matchScore += 2;
                }
            }

            if ($matchScore > $highestMatch) {
                $highestMatch = $matchScore;
                $bestStrategy = array_merge(['name' => $name], $strategy);
            }
        }

        // 기본 전략
        if (!$bestStrategy) {
            $bestStrategy = [
                'name' => 'general_support',
                'description' => '일반적인 학습 지원',
                'priority' => 5,
                'target_agent' => 'agent21',
                'actions' => ['monitor', 'encourage']
            ];
        }

        return $bestStrategy;
    }

    /**
     * 개입 계획 준비
     */
    private function prepareInterventionPlan(int $userId, array $strategy, array $context): array {
        return [
            'user_id' => $userId,
            'strategy_name' => $strategy['name'],
            'target_agent' => $strategy['target_agent'],
            'priority' => $strategy['priority'],
            'actions' => $strategy['actions'],
            'context_summary' => [
                'emotional_state' => $context['emotion'] ?? 'neutral',
                'current_activity' => $context['current_activity'] ?? 'unknown'
            ],
            'prepared_at' => time(),
            'expires_at' => time() + 600 // 10분 후 만료
        ];
    }

    /**
     * 개입 준비 액션 핸들러
     */
    private function prepareIntervention(array $params, array $context): array {
        $userId = $params['user_id'] ?? $context['user_id'] ?? 0;
        $strategyName = $params['strategy'] ?? 'general_support';

        if (!$userId) {
            return ['success' => false, 'error' => 'User ID required'];
        }

        $strategy = $this->interventionStrategies[$strategyName] ?? $this->interventionStrategies['emotional_support'];
        $strategy['name'] = $strategyName;

        return $this->prepareInterventionPlan($userId, $strategy, $context);
    }

    /**
     * 개입 요청 액션 핸들러
     */
    private function requestIntervention(array $params, array $context): array {
        $targetAgent = $params['target_agent'] ?? 'agent21';
        $interventionType = $params['type'] ?? 'general';
        $userId = $params['user_id'] ?? $context['user_id'] ?? 0;

        return $this->eventPublisher->publishInterventionRequest($userId, $interventionType, [
            'source' => 'agent20',
            'priority' => $params['priority'] ?? 5,
            'context' => $context
        ]);
    }

    /**
     * 전략 선택 액션 핸들러
     */
    private function selectStrategy(array $params, array $context): array {
        $analysis = $this->analyzeStudentState($context);
        return $this->selectBestStrategy($analysis);
    }

    /**
     * 협력 요청 액션 핸들러
     */
    private function requestCollaboration(array $params, array $context): array {
        $targetAgents = $params['agents'] ?? ['agent19', 'agent21'];
        $collaborationType = $params['type'] ?? 'intervention_support';

        return $this->communicator->multicast($targetAgents, 'collaboration_request', [
            'type' => $collaborationType,
            'source_agent' => 'agent20',
            'context' => $context,
            'requested_at' => time()
        ]);
    }

    /**
     * 우선순위 조정 액션 핸들러
     */
    private function adjustPriority(array $params, array $context): array {
        $currentPriority = $params['current'] ?? 5;
        $adjustment = $params['adjustment'] ?? 0;
        $reason = $params['reason'] ?? 'manual_adjustment';

        $newPriority = max(1, min(10, $currentPriority + $adjustment));

        return [
            'success' => true,
            'previous_priority' => $currentPriority,
            'new_priority' => $newPriority,
            'reason' => $reason
        ];
    }

    /**
     * 다른 에이전트로부터 메시지 수신 처리
     */
    public function processIncomingMessages(): array {
        $messages = $this->communicator->receiveMessages();
        $processed = [];

        foreach ($messages as $message) {
            try {
                $result = $this->handleMessage($message);
                $this->communicator->acknowledgeMessage($message['id'], 'processed', $result);
                $processed[] = [
                    'id' => $message['id'],
                    'type' => $message['message_type'],
                    'result' => $result
                ];
            } catch (Exception $e) {
                $this->communicator->acknowledgeMessage($message['id'], 'failed', ['error' => $e->getMessage()]);
            }
        }

        return $processed;
    }

    /**
     * 메시지 처리
     */
    private function handleMessage(array $message): array {
        $type = $message['message_type'] ?? '';
        $payload = $message['payload'] ?? [];

        switch ($type) {
            case 'intervention_request':
                return $this->analyzeAndPrepare($payload['user_id'] ?? 0, $payload);

            case 'status_update':
                return ['acknowledged' => true, 'type' => 'status_update'];

            case 'collaboration_response':
                return ['acknowledged' => true, 'type' => 'collaboration_response'];

            default:
                return ['acknowledged' => true, 'type' => 'unknown'];
        }
    }

    /**
     * 에러 로깅
     */
    private function logError(string $message, int $line): void {
        if ($this->config['log_enabled'] ?? true) {
            error_log("[Agent20PersonaEngine ERROR] {$this->currentFile}:{$line} - {$message}");
        }
    }
}

/*
 * Agent20 역할:
 * - 학생 상태 모니터링 및 분석
 * - 적절한 개입 전략 선택
 * - 다른 에이전트(특히 agent19, agent21)와 협력하여 개입 실행
 * - 개입 결과 추적 및 전략 조정
 *
 * 관련 에이전트:
 * - agent17: 행동 안내
 * - agent18: 동기 부여
 * - agent19: 학습 지원
 * - agent21: 개입 실행
 *
 * 관련 DB 테이블:
 * - mdl_at_agent_messages (에이전트 간 통신)
 * - mdl_at_persona_events (이벤트 기록)
 * - mdl_at_persona_session (세션 관리)
 * - mdl_at_persona_action_log (액션 로그)
 */
