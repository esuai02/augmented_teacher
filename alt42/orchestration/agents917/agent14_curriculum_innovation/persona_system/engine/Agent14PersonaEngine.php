<?php
/**
 * Agent14PersonaEngine - 교육과정 혁신 에이전트 페르소나 엔진
 *
 * Agent14 (Curriculum Innovation) 전용 페르소나 엔진
 * AbstractPersonaEngine을 상속받아 교육과정 혁신 관련 로직 구현
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentCommunicator.php');

// 구현체 로드
require_once(__DIR__ . '/impl/Agent14RuleParser.php');
require_once(__DIR__ . '/impl/Agent14ConditionEvaluator.php');
require_once(__DIR__ . '/impl/Agent14ActionExecutor.php');
require_once(__DIR__ . '/impl/Agent14DataContext.php');
require_once(__DIR__ . '/impl/Agent14ResponseGenerator.php');

/**
 * Agent14 전용 페르소나 엔진
 *
 * 교육과정 혁신(Curriculum Innovation) 도메인에 특화된 페르소나 식별 및 응답 생성
 */
class Agent14PersonaEngine extends AbstractPersonaEngine {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var AgentCommunicator 에이전트 통신기 */
    protected $communicator;

    /**
     * Agent14 상황 코드 정의
     * C1: 교육과정 분석 (Curriculum Analysis)
     * C2: 콘텐츠 설계 (Content Design)
     * C3: 교수법 혁신 (Pedagogy Innovation)
     * C4: 평가 설계 (Assessment Design)
     * C5: 적용 및 피드백 (Application & Feedback)
     */
    const SITUATION_CODES = ['C1', 'C2', 'C3', 'C4', 'C5'];

    /**
     * 생성자
     *
     * @param array $config 설정 배열
     */
    public function __construct(array $config = []) {
        parent::__construct('agent14', $config);
        $this->communicator = new AgentCommunicator('agent14');
    }

    /**
     * 컴포넌트 초기화
     */
    protected function initializeComponents(): void {
        $this->ruleParser = new Agent14RuleParser();
        $this->conditionEvaluator = new Agent14ConditionEvaluator();
        $this->actionExecutor = new Agent14ActionExecutor();
        $this->dataContext = new Agent14DataContext();
        $this->responseGenerator = new Agent14ResponseGenerator($this->agentBasePath . '/templates');

        // Agent14 전용 액션 핸들러 등록
        $this->registerAgent14Handlers();
    }

    /**
     * Agent14 전용 액션 핸들러 등록
     */
    protected function registerAgent14Handlers(): void {
        // 교육과정 분석 트리거
        $this->actionExecutor->registerHandler('trigger_curriculum_analysis', function($params, $context) {
            return [
                'action' => 'curriculum_analysis',
                'target' => $params['target'] ?? 'current_module',
                'depth' => $params['depth'] ?? 'standard'
            ];
        });

        // 콘텐츠 추천
        $this->actionExecutor->registerHandler('recommend_content', function($params, $context) {
            return [
                'action' => 'content_recommendation',
                'type' => $params['type'] ?? 'all',
                'based_on' => $context['learning_style'] ?? 'general'
            ];
        });

        // 학습 경로 설계
        $this->actionExecutor->registerHandler('design_learning_path', function($params, $context) {
            return [
                'action' => 'learning_path_design',
                'student_level' => $context['student_level'] ?? 'intermediate',
                'goal' => $params['goal'] ?? 'mastery'
            ];
        });

        // 평가 설계
        $this->actionExecutor->registerHandler('design_assessment', function($params, $context) {
            return [
                'action' => 'assessment_design',
                'assessment_type' => $params['type'] ?? 'formative',
                'criteria' => $params['criteria'] ?? []
            ];
        });

        // 피드백 생성
        $this->actionExecutor->registerHandler('generate_feedback', function($params, $context) {
            return [
                'action' => 'feedback_generation',
                'style' => $params['style'] ?? 'constructive',
                'focus' => $params['focus'] ?? 'improvement'
            ];
        });
    }

    /**
     * 페르소나 로드
     *
     * @return array 페르소나 배열
     */
    protected function loadPersonas(): array {
        return [
            // C1: 교육과정 분석
            'C1_P1' => [
                'id' => 'C1_P1',
                'name' => '교육과정 분석가',
                'name_en' => 'Curriculum Analyst',
                'description' => '현행 교육과정을 체계적으로 분석하고 개선점을 도출하는 전문가',
                'situation' => 'C1',
                'default_tone' => 'Professional',
                'default_intervention' => 'GapAnalysis'
            ],
            'C1_P2' => [
                'id' => 'C1_P2',
                'name' => '학습 설계 컨설턴트',
                'name_en' => 'Learning Design Consultant',
                'description' => '학습 목표와 현재 상태 간의 차이를 진단하는 컨설턴트',
                'situation' => 'C1',
                'default_tone' => 'Warm',
                'default_intervention' => 'InformationProvision'
            ],

            // C2: 콘텐츠 설계
            'C2_P1' => [
                'id' => 'C2_P1',
                'name' => '콘텐츠 설계자',
                'name_en' => 'Content Designer',
                'description' => '효과적인 학습 콘텐츠를 설계하고 구조화하는 전문가',
                'situation' => 'C2',
                'default_tone' => 'Professional',
                'default_intervention' => 'PlanDesign'
            ],
            'C2_P2' => [
                'id' => 'C2_P2',
                'name' => '교육 자료 개발자',
                'name_en' => 'Educational Material Developer',
                'description' => '다양한 형태의 교육 자료를 개발하는 전문가',
                'situation' => 'C2',
                'default_tone' => 'Encouraging',
                'default_intervention' => 'SkillBuilding'
            ],

            // C3: 교수법 혁신
            'C3_P1' => [
                'id' => 'C3_P1',
                'name' => '혁신적 교수법 전문가',
                'name_en' => 'Innovative Pedagogy Expert',
                'description' => '새로운 교수법을 연구하고 적용하는 전문가',
                'situation' => 'C3',
                'default_tone' => 'Encouraging',
                'default_intervention' => 'SkillBuilding'
            ],
            'C3_P2' => [
                'id' => 'C3_P2',
                'name' => '학습 촉진자',
                'name_en' => 'Learning Facilitator',
                'description' => '학습자 중심의 활동을 설계하고 촉진하는 전문가',
                'situation' => 'C3',
                'default_tone' => 'Playful',
                'default_intervention' => 'BehaviorModification'
            ],

            // C4: 평가 설계
            'C4_P1' => [
                'id' => 'C4_P1',
                'name' => '평가 설계 전문가',
                'name_en' => 'Assessment Design Expert',
                'description' => '효과적인 학습 평가 도구를 설계하는 전문가',
                'situation' => 'C4',
                'default_tone' => 'Professional',
                'default_intervention' => 'AssessmentDesign'
            ],
            'C4_P2' => [
                'id' => 'C4_P2',
                'name' => '역량 평가 코치',
                'name_en' => 'Competency Assessment Coach',
                'description' => '학습자의 역량을 정확히 측정하고 피드백하는 코치',
                'situation' => 'C4',
                'default_tone' => 'Calm',
                'default_intervention' => 'InformationProvision'
            ],

            // C5: 적용 및 피드백
            'C5_P1' => [
                'id' => 'C5_P1',
                'name' => '교육과정 적용 가이드',
                'name_en' => 'Curriculum Implementation Guide',
                'description' => '새로운 교육과정의 현장 적용을 지원하는 가이드',
                'situation' => 'C5',
                'default_tone' => 'Warm',
                'default_intervention' => 'InformationProvision'
            ],
            'C5_P2' => [
                'id' => 'C5_P2',
                'name' => '피드백 분석가',
                'name_en' => 'Feedback Analyst',
                'description' => '적용 결과를 분석하고 개선 방향을 제시하는 분석가',
                'situation' => 'C5',
                'default_tone' => 'Professional',
                'default_intervention' => 'GapAnalysis'
            ]
        ];
    }

    /**
     * 상황 코드 목록 반환
     *
     * @return array 상황 코드 목록
     */
    public function getSituationCodes(): array {
        return self::SITUATION_CODES;
    }

    /**
     * 기본 페르소나 반환 (상황별)
     *
     * @param string $situation 상황 코드
     * @return string 기본 페르소나 ID
     */
    protected function getDefaultPersona(string $situation): string {
        $defaults = [
            'C1' => 'C1_P1',
            'C2' => 'C2_P1',
            'C3' => 'C3_P1',
            'C4' => 'C4_P1',
            'C5' => 'C5_P1'
        ];

        return $defaults[$situation] ?? 'C1_P1';
    }

    /**
     * 템플릿 키 선택 (Agent14 특화)
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return string 템플릿 키
     */
    protected function selectTemplateKey(array $identification, array $context): string {
        $situation = $identification['situation'] ?? 'C1';
        $intent = $context['intent'] ?? 'general';

        // 의도별 템플릿 매핑
        $intentTemplates = [
            'analyze' => 'analysis',
            'design' => 'design',
            'create' => 'creation',
            'evaluate' => 'evaluation',
            'improve' => 'improvement',
            'question' => 'explanation',
            'help' => 'guidance'
        ];

        $templateType = $intentTemplates[$intent] ?? 'default';
        return "{$situation}_{$templateType}";
    }

    /**
     * 프로세스 실행 (오버라이드 - 에이전트 통신 추가)
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        // 부모 프로세스 실행
        $result = parent::process($userId, $message, $sessionData);

        // 성공시 페르소나 상태 저장 및 브로드캐스트
        if ($result['success']) {
            $this->communicator->savePersonaState($userId, [
                'persona_id' => $result['persona']['persona_id'],
                'persona_name' => $result['persona']['persona_name'],
                'confidence' => $result['persona']['confidence'],
                'situation' => $result['persona']['situation'],
                'tone' => $result['persona']['tone'],
                'intervention' => $result['persona']['intervention'],
                'matched_rule' => $result['persona']['matched_rule'],
                'context' => $result['context']
            ]);

            // 다른 에이전트에게 상태 공유 (옵션)
            if ($this->config['broadcast_enabled'] ?? false) {
                $this->communicator->broadcastPersonaUpdate($userId, $result['persona']);
            }
        }

        return $result;
    }

    /**
     * 다른 에이전트의 사용자 상태 조회
     *
     * @param int $userId 사용자 ID
     * @param string $agentId 조회할 에이전트 ID
     * @return array|null 페르소나 상태
     */
    public function getOtherAgentState(int $userId, string $agentId): ?array {
        return $this->communicator->getPersonaState($userId, $agentId);
    }

    /**
     * 교육과정 혁신 관련 특수 처리
     *
     * @param int $userId 사용자 ID
     * @param string $innovationType 혁신 유형
     * @param array $params 파라미터
     * @return array 처리 결과
     */
    public function handleCurriculumInnovation(int $userId, string $innovationType, array $params = []): array {
        $results = [];

        switch ($innovationType) {
            case 'analyze':
                // 현재 교육과정 분석
                $results = $this->analyzeCurriculum($userId, $params);
                break;

            case 'design':
                // 새 교육과정 설계
                $results = $this->designCurriculum($userId, $params);
                break;

            case 'implement':
                // 교육과정 적용 가이드
                $results = $this->guideCurriculumImplementation($userId, $params);
                break;

            case 'evaluate':
                // 교육과정 평가
                $results = $this->evaluateCurriculum($userId, $params);
                break;

            default:
                $results = ['error' => 'Unknown innovation type'];
        }

        return $results;
    }

    /**
     * 교육과정 분석 (내부 메서드)
     */
    protected function analyzeCurriculum(int $userId, array $params): array {
        // 분석 로직 구현
        return [
            'status' => 'analyzed',
            'analysis_type' => $params['type'] ?? 'comprehensive',
            'timestamp' => time()
        ];
    }

    /**
     * 교육과정 설계 (내부 메서드)
     */
    protected function designCurriculum(int $userId, array $params): array {
        // 설계 로직 구현
        return [
            'status' => 'designed',
            'design_type' => $params['type'] ?? 'modular',
            'timestamp' => time()
        ];
    }

    /**
     * 교육과정 적용 가이드 (내부 메서드)
     */
    protected function guideCurriculumImplementation(int $userId, array $params): array {
        // 적용 가이드 로직 구현
        return [
            'status' => 'guided',
            'implementation_phase' => $params['phase'] ?? 'initial',
            'timestamp' => time()
        ];
    }

    /**
     * 교육과정 평가 (내부 메서드)
     */
    protected function evaluateCurriculum(int $userId, array $params): array {
        // 평가 로직 구현
        return [
            'status' => 'evaluated',
            'evaluation_type' => $params['type'] ?? 'formative',
            'timestamp' => time()
        ];
    }

    /**
     * 디버그 정보 (확장)
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        $baseInfo = parent::getDebugInfo();
        $commInfo = $this->communicator->getDebugInfo();

        return array_merge($baseInfo, [
            'situation_codes' => self::SITUATION_CODES,
            'communication' => $commInfo
        ]);
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state: 에이전트별 사용자 페르소나 상태
 * - at_agent_messages: 에이전트 간 메시지 통신
 * - at_agent_persona_history: 페르소나 전환 이력
 * - at_agent_config: 에이전트별 설정
 *
 * 사용 예시:
 *
 * $engine = new Agent14PersonaEngine([
 *     'debug_mode' => true,
 *     'broadcast_enabled' => true
 * ]);
 *
 * // 규칙 로드
 * $engine->loadRules();
 *
 * // 메시지 처리
 * $result = $engine->process($userId, "교육과정을 분석해주세요");
 *
 * // 결과 확인
 * if ($result['success']) {
 *     echo $result['response']['text'];
 *     echo "페르소나: " . $result['persona']['persona_name'];
 * }
 */
