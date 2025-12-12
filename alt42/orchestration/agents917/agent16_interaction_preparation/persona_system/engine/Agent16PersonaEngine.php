<?php
/**
 * Agent16PersonaEngine - 상호작용 준비 에이전트 페르소나 엔진
 *
 * Agent16(Interaction Preparation)의 핵심 페르소나 엔진입니다.
 * 9가지 세계관 선택 및 스토리텔링 테마 설정을 담당합니다.
 *
 * @package AugmentedTeacher\Agent16\PersonaEngine
 * @version 1.0.0
 * @author ALT42 Orchestration System
 *
 * 사용 예시:
 * ```php
 * $engine = new Agent16PersonaEngine('agent16', ['debug_mode' => true]);
 * $engine->loadRules();
 * $result = $engine->process($userId, $message, $sessionData);
 * ```
 *
 * 세계관 (Worldviews):
 * - 커리큘럼: 체계적인 학습 과정 기반
 * - 맞춤학습: 개인화된 학습 경험
 * - 시험대비: 시험 준비 집중 모드
 * - 단기미션: 단기 목표 달성 중심
 * - 자기성찰: 메타인지 및 자기 분석
 * - 자기주도: 학습자 주도적 학습
 * - 도제학습: 멘토-학습자 관계 기반
 * - 시간성찰: 시간 관리 및 회고
 * - 탐구학습: 탐구 기반 학습
 *
 * 관련 DB 테이블:
 * - at_agent_persona_state: 에이전트별 페르소나 상태
 * - at_agent_messages: 에이전트 간 메시지
 * - at_agent_persona_history: 페르소나 변경 이력
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseActionExecutor.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseRuleParser.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseDataContext.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseResponseGenerator.php');

// 통신 모듈 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentCommunicator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentStateSync.php');

class Agent16PersonaEngine extends AbstractPersonaEngine {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var int 에이전트 번호 */
    private $agentNumber = 16;

    /** @var AgentCommunicator 에이전트 통신 모듈 */
    private $communicator;

    /** @var AgentStateSync 상태 동기화 모듈 */
    private $stateSync;

    /** @var array 세계관 정의 */
    private $worldviews = [
        'curriculum' => [
            'id' => 'curriculum',
            'name_ko' => '커리큘럼',
            'description' => '체계적인 학습 과정 기반',
            'triggers' => ['정규과정', '단원', '학기', '교과서'],
            'priority' => 1
        ],
        'personalized' => [
            'id' => 'personalized',
            'name_ko' => '맞춤학습',
            'description' => '개인화된 학습 경험',
            'triggers' => ['내 수준', '맞춤', '개인화', '나에게'],
            'priority' => 2
        ],
        'exam_prep' => [
            'id' => 'exam_prep',
            'name_ko' => '시험대비',
            'description' => '시험 준비 집중 모드',
            'triggers' => ['시험', '테스트', '평가', '중간고사', '기말고사'],
            'priority' => 3
        ],
        'short_mission' => [
            'id' => 'short_mission',
            'name_ko' => '단기미션',
            'description' => '단기 목표 달성 중심',
            'triggers' => ['오늘', '지금', '빠르게', '미션', '즉시'],
            'priority' => 4
        ],
        'self_reflection' => [
            'id' => 'self_reflection',
            'name_ko' => '자기성찰',
            'description' => '메타인지 및 자기 분석',
            'triggers' => ['왜', '이유', '생각해보면', '돌아보면'],
            'priority' => 5
        ],
        'self_directed' => [
            'id' => 'self_directed',
            'name_ko' => '자기주도',
            'description' => '학습자 주도적 학습',
            'triggers' => ['내가', '스스로', '직접', '계획'],
            'priority' => 6
        ],
        'apprenticeship' => [
            'id' => 'apprenticeship',
            'name_ko' => '도제학습',
            'description' => '멘토-학습자 관계 기반',
            'triggers' => ['선생님', '가르쳐', '알려주세요', '도와주세요'],
            'priority' => 7
        ],
        'time_reflection' => [
            'id' => 'time_reflection',
            'name_ko' => '시간성찰',
            'description' => '시간 관리 및 회고',
            'triggers' => ['시간', '일정', '관리', '언제'],
            'priority' => 8
        ],
        'inquiry' => [
            'id' => 'inquiry',
            'name_ko' => '탐구학습',
            'description' => '탐구 기반 학습',
            'triggers' => ['궁금', '탐구', '조사', '실험', '왜 그럴까'],
            'priority' => 9
        ]
    ];

    /** @var array 상황 그룹 정의 (S0-S6) */
    private $situationGroups = [
        'S0' => '상태 감지 - 학습자 현재 상태 파악',
        'S1' => '세계관 매핑 - 적절한 세계관 선택',
        'S2' => '학원 맥락 - 학원 환경 특성 반영',
        'S3' => '실시간 상호작용 - 즉각적 반응',
        'S4' => '취약점 기반 준비 - 약점 보완 전략',
        'S5' => '수준 차별화 - 학습 수준 맞춤',
        'S6' => '연속성 - 학습 흐름 유지'
    ];

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID (기본값: 'agent16')
     * @param array $config 설정
     */
    public function __construct(string $agentId = 'agent16', array $config = []) {
        parent::__construct($agentId, $config);
    }

    /**
     * 컴포넌트 초기화
     *
     * @return void
     */
    protected function initializeComponents(): void {
        global $DB;

        // 기본 구현체 초기화
        $this->ruleParser = new BaseRuleParser();
        $this->conditionEvaluator = new BaseConditionEvaluator();
        $this->actionExecutor = new BaseActionExecutor();
        $this->dataContext = new BaseDataContext($DB);
        $this->responseGenerator = new BaseResponseGenerator();

        // 통신 모듈 초기화
        $this->communicator = new AgentCommunicator($DB, $this->agentNumber);
        $this->stateSync = new AgentStateSync($DB);

        $this->logDebug("Agent16 컴포넌트 초기화 완료");
    }

    /**
     * 규칙 파일 경로 반환
     *
     * @return string 규칙 파일 경로
     */
    protected function getRulesPath(): string {
        return __DIR__ . '/../../rules/rules.yaml';
    }

    /**
     * 컨텍스트 확장 - Agent16 특화 데이터 추가
     *
     * @param array $context 기본 컨텍스트
     * @return array 확장된 컨텍스트
     */
    protected function extendContext(array $context): array {
        $userId = $context['user_id'] ?? 0;

        // 이전 세계관 상태 로드
        $previousState = $this->stateSync->getState($this->agentNumber, $userId);
        $context['previous_worldview'] = $previousState['context_data']['worldview'] ?? null;
        $context['interaction_count'] = ($previousState['context_data']['interaction_count'] ?? 0) + 1;

        // 세계관 목록 추가
        $context['available_worldviews'] = array_keys($this->worldviews);

        // 학원 컨텍스트 추가
        $context['academy_context'] = $this->loadAcademyContext($userId);

        // 상황 그룹 정보 추가
        $context['situation_groups'] = $this->situationGroups;

        return $context;
    }

    /**
     * 메시지 분석 - Agent16 특화 분석
     *
     * @param array $context 컨텍스트
     * @param string $message 사용자 메시지
     * @return array 분석 결과
     */
    protected function analyzeMessage(array $context, string $message): array {
        $analysis = parent::analyzeMessage($context, $message);

        // 세계관 감지
        $detectedWorldview = $this->detectWorldview($message);
        $analysis['detected_worldview'] = $detectedWorldview;
        $analysis['worldview_confidence'] = $this->calculateWorldviewConfidence($message, $detectedWorldview);

        // 상황 그룹 감지
        $analysis['situation_group'] = $this->detectSituationGroup($message, $context);

        // 학습 단계 감지
        $analysis['learning_stage'] = $this->detectLearningStage($message, $context);

        // 긴급도 평가
        $analysis['urgency_level'] = $this->evaluateUrgency($message);

        return $analysis;
    }

    /**
     * 세계관 감지
     *
     * @param string $message 사용자 메시지
     * @return string|null 감지된 세계관 ID
     */
    private function detectWorldview(string $message): ?string {
        $messageLower = mb_strtolower($message);
        $scores = [];

        foreach ($this->worldviews as $id => $worldview) {
            $score = 0;
            foreach ($worldview['triggers'] as $trigger) {
                if (mb_strpos($messageLower, mb_strtolower($trigger)) !== false) {
                    $score += 10;
                }
            }
            if ($score > 0) {
                $scores[$id] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // 가장 높은 점수의 세계관 반환
        arsort($scores);
        return array_key_first($scores);
    }

    /**
     * 세계관 신뢰도 계산
     *
     * @param string $message 메시지
     * @param string|null $worldview 세계관
     * @return float 신뢰도 (0.0 - 1.0)
     */
    private function calculateWorldviewConfidence(string $message, ?string $worldview): float {
        if ($worldview === null) {
            return 0.0;
        }

        $triggers = $this->worldviews[$worldview]['triggers'] ?? [];
        $matchCount = 0;
        $messageLower = mb_strtolower($message);

        foreach ($triggers as $trigger) {
            if (mb_strpos($messageLower, mb_strtolower($trigger)) !== false) {
                $matchCount++;
            }
        }

        // 매칭 수에 따른 신뢰도 계산
        $confidence = min(1.0, 0.3 + ($matchCount * 0.2));
        return round($confidence, 2);
    }

    /**
     * 상황 그룹 감지 (S0-S6)
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return string 상황 그룹 ID
     */
    private function detectSituationGroup(string $message, array $context): string {
        // S0: 상태 감지 - 첫 상호작용 또는 상태 질문
        if (($context['interaction_count'] ?? 0) <= 1) {
            return 'S0';
        }

        // S3: 실시간 상호작용 - 즉각적 응답 필요
        if ($this->evaluateUrgency($message) > 0.7) {
            return 'S3';
        }

        // S1: 세계관 매핑 - 세계관 관련 키워드
        if ($this->detectWorldview($message) !== null) {
            return 'S1';
        }

        // S4: 취약점 기반 - 어려움/약점 언급
        if (preg_match('/(어려워|모르겠|헷갈려|실수|틀린|약점)/', $message)) {
            return 'S4';
        }

        // S5: 수준 차별화 - 수준 관련
        if (preg_match('/(쉬운|어려운|기초|심화|중급)/', $message)) {
            return 'S5';
        }

        // S6: 연속성 - 이전 학습 언급
        if (preg_match('/(지난번|이전|계속|다음|연속)/', $message)) {
            return 'S6';
        }

        // S2: 기본 학원 맥락
        return 'S2';
    }

    /**
     * 학습 단계 감지
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return string 학습 단계
     */
    private function detectLearningStage(string $message, array $context): string {
        if (preg_match('/(처음|시작|새로|입문)/', $message)) {
            return 'introduction';
        }
        if (preg_match('/(연습|복습|반복)/', $message)) {
            return 'practice';
        }
        if (preg_match('/(심화|응용|확장)/', $message)) {
            return 'advanced';
        }
        if (preg_match('/(정리|마무리|총정리)/', $message)) {
            return 'consolidation';
        }

        return 'exploration';
    }

    /**
     * 긴급도 평가
     *
     * @param string $message 메시지
     * @return float 긴급도 (0.0 - 1.0)
     */
    private function evaluateUrgency(string $message): float {
        $urgentKeywords = ['급해', '빨리', '지금', '당장', '시험 곧', '내일'];
        $urgency = 0.0;

        foreach ($urgentKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $urgency += 0.2;
            }
        }

        return min(1.0, $urgency);
    }

    /**
     * 학원 컨텍스트 로드
     *
     * @param int $userId 사용자 ID
     * @return array 학원 컨텍스트
     */
    private function loadAcademyContext(int $userId): array {
        // 기본 학원 컨텍스트 반환
        // 실제 구현에서는 DB에서 학원별 설정을 로드
        return [
            'academy_id' => null,
            'curriculum_type' => 'standard',
            'focus_areas' => [],
            'special_programs' => []
        ];
    }

    /**
     * 세계관 기반 페르소나 선택
     *
     * @param string $worldviewId 세계관 ID
     * @param array $context 컨텍스트
     * @return array 페르소나 설정
     */
    public function selectPersonaByWorldview(string $worldviewId, array $context = []): array {
        $worldview = $this->worldviews[$worldviewId] ?? $this->worldviews['curriculum'];

        // 세계관별 페르소나 매핑
        $personaMapping = [
            'curriculum' => [
                'persona_id' => 'A16_P1',
                'name' => '체계적 가이드',
                'tone' => 'Professional',
                'intervention' => 'GuidedDiscovery'
            ],
            'personalized' => [
                'persona_id' => 'A16_P2',
                'name' => '맞춤 코치',
                'tone' => 'Supportive',
                'intervention' => 'PersonalizedGuidance'
            ],
            'exam_prep' => [
                'persona_id' => 'A16_P3',
                'name' => '시험 전략가',
                'tone' => 'Analytical',
                'intervention' => 'StrategicSupport'
            ],
            'short_mission' => [
                'persona_id' => 'A16_P4',
                'name' => '미션 매니저',
                'tone' => 'Motivational',
                'intervention' => 'DirectGuidance'
            ],
            'self_reflection' => [
                'persona_id' => 'A16_P5',
                'name' => '성찰 촉진자',
                'tone' => 'Reflective',
                'intervention' => 'SocraticQuestioning'
            ],
            'self_directed' => [
                'persona_id' => 'A16_P6',
                'name' => '자율 지원자',
                'tone' => 'Empowering',
                'intervention' => 'ScaffoldedSupport'
            ],
            'apprenticeship' => [
                'persona_id' => 'A16_P7',
                'name' => '멘토',
                'tone' => 'Mentoring',
                'intervention' => 'ModelingAndExplanation'
            ],
            'time_reflection' => [
                'persona_id' => 'A16_P8',
                'name' => '시간 관리자',
                'tone' => 'Practical',
                'intervention' => 'TimeAwareness'
            ],
            'inquiry' => [
                'persona_id' => 'A16_P9',
                'name' => '탐구 안내자',
                'tone' => 'Curious',
                'intervention' => 'InquiryBased'
            ]
        ];

        return $personaMapping[$worldviewId] ?? $personaMapping['curriculum'];
    }

    /**
     * 프로세스 실행 후 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param string $message 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $result = parent::process($userId, $message, $sessionData);

        if ($result['success']) {
            // 페르소나 상태 저장
            $this->savePersonaState($userId, $result);
        }

        return $result;
    }

    /**
     * 페르소나 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param array $result 처리 결과
     * @return bool 성공 여부
     */
    private function savePersonaState(int $userId, array $result): bool {
        try {
            $analysis = $result['analysis'] ?? [];
            $persona = $result['persona'] ?? [];

            $stateData = [
                'persona_id' => $persona['persona_id'] ?? 'A16_P1',
                'context_data' => [
                    'worldview' => $analysis['detected_worldview'] ?? 'curriculum',
                    'worldview_confidence' => $analysis['worldview_confidence'] ?? 0.5,
                    'situation_group' => $analysis['situation_group'] ?? 'S0',
                    'learning_stage' => $analysis['learning_stage'] ?? 'exploration',
                    'interaction_count' => $analysis['interaction_count'] ?? 1,
                    'last_message_preview' => mb_substr($result['response']['text'] ?? '', 0, 100)
                ]
            ];

            return $this->stateSync->updateState($this->agentNumber, $userId, $stateData);

        } catch (Exception $e) {
            error_log("[Agent16PersonaEngine] {$this->currentFile}:" . __LINE__ .
                " - 상태 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 세계관 목록 반환
     *
     * @return array 세계관 목록
     */
    public function getWorldviews(): array {
        return $this->worldviews;
    }

    /**
     * 특정 세계관 정보 반환
     *
     * @param string $worldviewId 세계관 ID
     * @return array|null 세계관 정보
     */
    public function getWorldview(string $worldviewId): ?array {
        return $this->worldviews[$worldviewId] ?? null;
    }

    /**
     * 상황 그룹 목록 반환
     *
     * @return array 상황 그룹 목록
     */
    public function getSituationGroups(): array {
        return $this->situationGroups;
    }

    /**
     * 다른 에이전트에게 세계관 정보 전달
     *
     * @param int $targetAgentId 대상 에이전트 ID
     * @param int $userId 사용자 ID
     * @param string $worldviewId 세계관 ID
     * @return bool 성공 여부
     */
    public function shareWorldviewWithAgent(int $targetAgentId, int $userId, string $worldviewId): bool {
        try {
            $worldview = $this->getWorldview($worldviewId);
            if (!$worldview) {
                return false;
            }

            return $this->communicator->sendMessage(
                $targetAgentId,
                'worldview_update',
                [
                    'worldview_id' => $worldviewId,
                    'worldview_name' => $worldview['name_ko'],
                    'description' => $worldview['description']
                ],
                $userId
            );

        } catch (Exception $e) {
            error_log("[Agent16PersonaEngine] {$this->currentFile}:" . __LINE__ .
                " - 세계관 공유 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 디버그 정보 반환 (확장)
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        $baseInfo = parent::getDebugInfo();

        return array_merge($baseInfo, [
            'agent_number' => $this->agentNumber,
            'worldviews_count' => count($this->worldviews),
            'situation_groups' => array_keys($this->situationGroups),
            'communicator_ready' => $this->communicator !== null,
            'state_sync_ready' => $this->stateSync !== null
        ]);
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state (agent_id, user_id, persona_id, context_data, last_interaction)
 * - at_agent_messages (from_agent, to_agent, user_id, message_type, payload, status)
 * - at_agent_persona_history (agent_id, user_id, from_persona, to_persona)
 * - at_agent_config (agent_id, config_key, config_value)
 */
