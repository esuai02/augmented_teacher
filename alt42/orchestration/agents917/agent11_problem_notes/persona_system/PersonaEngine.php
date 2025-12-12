<?php
/**
 * Agent11 PersonaEngine - 문제노트 에이전트 페르소나 엔진
 *
 * 공통 AbstractPersonaEngine을 상속하여 agent11 특화 기능 구현
 * 학생의 오답 분석과 학습 코칭에 최적화된 페르소나 전환
 *
 * @package AugmentedTeacher\Agent11\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\Agent11\PersonaSystem;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/impl/BaseActionExecutor.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/impl/YamlRuleParser.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/impl/MoodleDataContext.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/impl/TemplateResponseGenerator.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/communication/PersonaStateSync.php');
require_once(__DIR__ . '/../../ontology_engineering/persona_engine/config/persona_engine.config.php');

use AugmentedTeacher\PersonaEngine\Core\AbstractPersonaEngine;
use AugmentedTeacher\PersonaEngine\Impl\BaseConditionEvaluator;
use AugmentedTeacher\PersonaEngine\Impl\BaseActionExecutor;
use AugmentedTeacher\PersonaEngine\Impl\YamlRuleParser;
use AugmentedTeacher\PersonaEngine\Impl\MoodleDataContext;
use AugmentedTeacher\PersonaEngine\Impl\TemplateResponseGenerator;
use AugmentedTeacher\PersonaEngine\Communication\PersonaStateSync;
use AugmentedTeacher\PersonaEngine\Config\PersonaEngineConfig;

/**
 * Agent11PersonaEngine - 문제노트 특화 페르소나 엔진
 *
 * 페르소나 종류:
 * - AnalyticalHelper (분석적 조력자): 오답 원인 분석에 집중
 * - EncouragingCoach (격려형 코치): 동기부여와 정서적 지원
 * - PatientGuide (차분한 안내자): 단계별 설명과 이해 확인
 * - PracticeLeader (연습 리더): 유사 문제 풀이 유도
 */
class Agent11PersonaEngine extends AbstractPersonaEngine {

    /** @var string 에이전트 ID */
    protected $agentId = 'agent11';

    /** @var string 기본 페르소나 */
    protected $defaultPersona = 'AnalyticalHelper';

    /** @var PersonaStateSync 상태 동기화 */
    private $stateSync;

    /** @var array 페르소나별 특성 */
    private $personaCharacteristics = [
        'AnalyticalHelper' => [
            'name' => '분석적 조력자',
            'tone' => 'Professional',
            'focus' => 'error_analysis',
            'approach' => '논리적 분석과 원인 규명',
            'triggers' => ['반복 오류', '개념 혼동', '계산 실수']
        ],
        'EncouragingCoach' => [
            'name' => '격려형 코치',
            'tone' => 'Encouraging',
            'focus' => 'motivation',
            'approach' => '정서적 지원과 성장 강조',
            'triggers' => ['좌절감', '자신감 부족', '학습 포기 징후']
        ],
        'PatientGuide' => [
            'name' => '차분한 안내자',
            'tone' => 'Supportive',
            'focus' => 'understanding',
            'approach' => '단계별 설명과 확인',
            'triggers' => ['기초 개념 부족', '이해도 낮음', '천천히 진행 필요']
        ],
        'PracticeLeader' => [
            'name' => '연습 리더',
            'tone' => 'Directive',
            'focus' => 'practice',
            'approach' => '반복 연습과 숙달 유도',
            'triggers' => ['유사 문제 필요', '숙련도 향상', '패턴 익힘']
        ]
    ];

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 생성자
     *
     * @param bool $debugMode 디버그 모드
     */
    public function __construct(bool $debugMode = false) {
        // 에이전트별 설정 오버라이드
        PersonaEngineConfig::setAgentOverrides($this->agentId, [
            'response_generator.default_tone' => 'Professional',
            'cache.state_ttl' => 120  // 문제노트는 상태 갱신이 빈번
        ]);

        // 부모 생성자 호출
        parent::__construct(
            new BaseConditionEvaluator($debugMode),
            new BaseActionExecutor($debugMode),
            new YamlRuleParser(),
            new MoodleDataContext($debugMode),
            new TemplateResponseGenerator(__DIR__ . '/templates', $debugMode),
            $debugMode
        );

        // 상태 동기화 초기화
        $this->stateSync = new PersonaStateSync($this->agentId, $debugMode);

        // 에이전트별 규칙 로드
        $this->loadAgentRules();

        if ($this->debugMode) {
            error_log("[Agent11PersonaEngine DEBUG] 초기화 완료");
        }
    }

    /**
     * 에이전트별 규칙 로드
     */
    private function loadAgentRules(): void {
        $rulesPath = __DIR__ . '/rules/rules.yaml';
        if (file_exists($rulesPath)) {
            $this->rules = $this->ruleParser->parse($rulesPath);
        }
    }

    /**
     * 에이전트 ID 반환
     *
     * @return string 에이전트 ID
     */
    public function getAgentId(): string {
        return $this->agentId;
    }

    /**
     * 사용자에 대한 현재 페르소나 결정
     *
     * @param int $userId 사용자 ID
     * @param array $context 추가 컨텍스트
     * @return string 페르소나 ID
     */
    public function determinePersona(int $userId, array $context = []): string {
        // 1. 동기화된 상태에서 페르소나 확인
        $currentState = $this->stateSync->getState($userId);
        if ($currentState && isset($currentState['persona_id'])) {
            // 컨텍스트 기반 전환 필요성 평가
            if (!$this->shouldTransition($userId, $currentState['persona_id'], $context)) {
                return $currentState['persona_id'];
            }
        }

        // 2. 새로운 페르소나 결정
        $newPersona = $this->evaluatePersonaFromContext($userId, $context);

        // 3. 상태 저장 및 동기화
        $this->stateSync->saveState($userId, $newPersona, [
            'context' => $context,
            'determined_at' => time()
        ]);

        return $newPersona;
    }

    /**
     * 컨텍스트 기반 페르소나 평가
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트
     * @return string 결정된 페르소나
     */
    private function evaluatePersonaFromContext(int $userId, array $context): string {
        // 감정 상태 우선 처리
        $emotionalState = $context['emotional_state'] ?? null;
        if ($emotionalState) {
            if (in_array($emotionalState, ['frustrated', 'anxious', 'discouraged'])) {
                return 'EncouragingCoach';
            }
        }

        // 오류 유형에 따른 페르소나 결정
        $errorType = $context['error_type'] ?? null;
        if ($errorType) {
            switch ($errorType) {
                case 'concept_confusion':
                case 'repeated_error':
                    return 'AnalyticalHelper';
                case 'basic_gap':
                case 'slow_understanding':
                    return 'PatientGuide';
                case 'needs_practice':
                case 'pattern_recognition':
                    return 'PracticeLeader';
            }
        }

        // 학습 진도에 따른 결정
        $learningProgress = $context['learning_progress'] ?? 50;
        if ($learningProgress < 30) {
            return 'PatientGuide';
        } elseif ($learningProgress > 70) {
            return 'PracticeLeader';
        }

        return $this->defaultPersona;
    }

    /**
     * 페르소나 전환 필요성 평가
     *
     * @param int $userId 사용자 ID
     * @param string $currentPersona 현재 페르소나
     * @param array $context 새 컨텍스트
     * @return bool 전환 필요 여부
     */
    private function shouldTransition(int $userId, string $currentPersona, array $context): bool {
        // 강제 전환 플래그
        if (!empty($context['force_transition'])) {
            return true;
        }

        // 감정 상태 급변
        $emotionalState = $context['emotional_state'] ?? null;
        if ($emotionalState && in_array($emotionalState, ['frustrated', 'anxious', 'discouraged'])) {
            if ($currentPersona !== 'EncouragingCoach') {
                return true;
            }
        }

        // 세션 내 전환 빈도 제한 (과도한 전환 방지)
        $lastTransition = $context['last_transition_time'] ?? 0;
        if (time() - $lastTransition < 300) {  // 5분 이내 재전환 방지
            return false;
        }

        return false;
    }

    /**
     * 문제노트 분석 응답 생성
     *
     * @param int $userId 사용자 ID
     * @param array $noteData 문제노트 데이터
     * @return array 응답 데이터
     */
    public function generateNoteAnalysisResponse(int $userId, array $noteData): array {
        $persona = $this->determinePersona($userId, [
            'error_type' => $noteData['error_type'] ?? null,
            'emotional_state' => $noteData['emotional_state'] ?? null,
            'learning_progress' => $noteData['learning_progress'] ?? 50
        ]);

        $characteristics = $this->personaCharacteristics[$persona] ?? $this->personaCharacteristics[$this->defaultPersona];

        // 응답 생성
        $response = $this->responseGenerator->generate(
            $persona,
            'note_analysis',
            array_merge($noteData, [
                'persona_name' => $characteristics['name'],
                'approach' => $characteristics['approach']
            ]),
            $characteristics['tone']
        );

        return [
            'persona' => $persona,
            'persona_name' => $characteristics['name'],
            'tone' => $characteristics['tone'],
            'response' => $response,
            'focus' => $characteristics['focus']
        ];
    }

    /**
     * 다른 에이전트에게 감정 상태 브로드캐스트
     *
     * @param int $userId 사용자 ID
     * @param string $emotion 감정 상태
     * @param float $intensity 강도
     */
    public function broadcastEmotionalState(int $userId, string $emotion, float $intensity): void {
        $this->stateSync->getMessageBus()->send(
            'emotion_detected',
            'broadcast',
            [
                'user_id' => $userId,
                'emotion' => $emotion,
                'intensity' => $intensity,
                'detected_by' => $this->agentId,
                'context' => 'problem_note_analysis'
            ],
            $intensity > 0.7 ? 2 : 3  // 높은 강도는 높은 우선순위
        );
    }

    /**
     * 페르소나 특성 조회
     *
     * @param string|null $personaId 페르소나 ID (null이면 전체)
     * @return array 페르소나 특성
     */
    public function getPersonaCharacteristics(?string $personaId = null): array {
        if ($personaId) {
            return $this->personaCharacteristics[$personaId] ?? [];
        }
        return $this->personaCharacteristics;
    }

    /**
     * 상태 동기화 인스턴스 반환
     *
     * @return PersonaStateSync 상태 동기화
     */
    public function getStateSync(): PersonaStateSync {
        return $this->stateSync;
    }

    /**
     * 수신 메시지 처리 (다른 에이전트로부터)
     *
     * @param int $limit 처리할 메시지 수
     * @return array 처리 결과
     */
    public function processIncomingMessages(int $limit = 10): array {
        return $this->stateSync->processIncomingMessages($limit);
    }
}

/*
 * 사용 예시:
 *
 * $engine = new Agent11PersonaEngine(true); // 디버그 모드
 *
 * // 페르소나 결정
 * $persona = $engine->determinePersona($userId, [
 *     'error_type' => 'concept_confusion',
 *     'emotional_state' => 'frustrated'
 * ]);
 *
 * // 문제노트 분석 응답 생성
 * $response = $engine->generateNoteAnalysisResponse($userId, [
 *     'problem_id' => 123,
 *     'error_type' => 'calculation_mistake',
 *     'student_answer' => '25',
 *     'correct_answer' => '35'
 * ]);
 *
 * // 감정 상태 브로드캐스트
 * $engine->broadcastEmotionalState($userId, 'frustrated', 0.8);
 *
 * 관련 DB 테이블:
 * - at_agent_persona_state: 페르소나 상태 저장
 * - at_agent_messages: 에이전트 간 메시지
 * - at_persona_history: 페르소나 변경 이력
 */

