<?php
/**
 * AbstractPersonaEngine - 추상 페르소나 엔진
 *
 * 모든 에이전트의 페르소나 엔진이 상속받는 기본 클래스
 * 공통 로직을 제공하고 에이전트별 확장점 정의
 *
 * @package AugmentedTeacher\PersonaEngine\Core
 * @version 1.0
 * @author Claude Code
 *
 * 상속 구조:
 * AbstractPersonaEngine
 *   ├── Agent01PersonaEngine (온보딩)
 *   ├── Agent11PersonaEngine (문제노트)
 *   └── Agent##PersonaEngine (기타 에이전트)
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

abstract class AbstractPersonaEngine {

    /** @var IConditionEvaluator 조건 평가기 */
    protected $conditionEvaluator;

    /** @var IActionExecutor 액션 실행기 */
    protected $actionExecutor;

    /** @var IRuleParser 규칙 파서 */
    protected $ruleParser;

    /** @var IDataContext 데이터 컨텍스트 */
    protected $dataContext;

    /** @var IResponseGenerator 응답 생성기 */
    protected $responseGenerator;

    /** @var array 로드된 규칙 */
    protected $rules = [];

    /** @var array 설정 */
    protected $config = [
        'debug_mode' => false,
        'log_enabled' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600
    ];

    /** @var string 에이전트 ID */
    protected $agentId;

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID
     * @param array $config 설정 배열
     */
    public function __construct(string $agentId, array $config = []) {
        $this->agentId = $agentId;
        $this->config = array_merge($this->config, $config);
        $this->initializeComponents();
    }

    /**
     * 컴포넌트 초기화 - 서브클래스에서 구현
     *
     * @return void
     */
    abstract protected function initializeComponents(): void;

    /**
     * 에이전트별 맞춤 페르소나 목록 반환
     *
     * @return array 페르소나 정의 배열
     */
    abstract public function getPersonaDefinitions(): array;

    /**
     * 에이전트별 기본 페르소나 ID 반환
     *
     * @return string 기본 페르소나 ID
     */
    abstract public function getDefaultPersonaId(): string;

    /**
     * 메인 프로세스 - 메시지 분석부터 응답까지
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $startTime = microtime(true);

        try {
            // 1. 컨텍스트 로드
            $context = $this->dataContext->loadContext($userId, $sessionData);
            $context = $this->enrichContext($context);

            // 2. 메시지 분석
            $analysis = $this->analyzeMessage($message, $context);

            // 3. 페르소나 식별
            $persona = $this->identifyPersona($analysis, $context);

            // 4. 응답 생성
            $response = $this->generateResponse($message, $persona, $context);

            // 5. 상태 저장
            $this->saveState($userId, $persona);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'user_id' => $userId,
                'agent_id' => $this->agentId,
                'persona' => $persona,
                'response' => $response,
                'analysis' => $analysis,
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
     * 규칙 로드
     *
     * @param string $rulesPath 규칙 파일 경로
     * @return bool 성공 여부
     */
    public function loadRules(string $rulesPath): bool {
        try {
            $this->rules = $this->ruleParser->parse($rulesPath);
            return true;
        } catch (\Exception $e) {
            $this->logError("규칙 로드 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 메시지 분석 - 서브클래스에서 확장 가능
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return array 분석 결과
     */
    protected function analyzeMessage(string $message, array $context): array {
        return [
            'message' => $message,
            'length' => mb_strlen($message),
            'detected_intent' => 'unknown',
            'detected_emotion' => 'neutral',
            'emotion_intensity' => 0,
            'detected_topics' => []
        ];
    }

    /**
     * 페르소나 식별
     *
     * @param array $analysis 분석 결과
     * @param array $context 컨텍스트
     * @return array 식별된 페르소나
     */
    protected function identifyPersona(array $analysis, array $context): array {
        $matchedPersona = null;
        $highestPriority = -1;

        foreach ($this->rules['personas'] ?? [] as $persona) {
            $conditions = $persona['conditions'] ?? [];

            if ($this->conditionEvaluator->evaluateAll($conditions, array_merge($context, $analysis))) {
                $priority = $persona['priority'] ?? 0;
                if ($priority > $highestPriority) {
                    $matchedPersona = $persona;
                    $highestPriority = $priority;
                }
            }
        }

        if (!$matchedPersona) {
            return $this->getDefaultPersona();
        }

        // 액션 실행
        $actions = $matchedPersona['actions'] ?? [];
        $actionResults = $this->actionExecutor->executeAll($actions, $context);

        return [
            'persona_id' => $matchedPersona['id'],
            'persona_name' => $matchedPersona['name'],
            'description' => $matchedPersona['description'] ?? '',
            'tone' => $actionResults['tone'] ?? 'Professional',
            'intervention' => $actionResults['intervention'] ?? 'InformationProvision',
            'matched_rule' => $matchedPersona['id'],
            'confidence' => 0.8
        ];
    }

    /**
     * 응답 생성
     *
     * @param string $message 원본 메시지
     * @param array $persona 페르소나
     * @param array $context 컨텍스트
     * @return array 응답
     */
    protected function generateResponse(string $message, array $persona, array $context): array {
        return $this->responseGenerator->generate($persona, array_merge($context, [
            'message' => $message
        ]));
    }

    /**
     * 기본 페르소나 반환
     *
     * @return array 기본 페르소나
     */
    protected function getDefaultPersona(): array {
        return [
            'persona_id' => $this->getDefaultPersonaId(),
            'persona_name' => 'Default',
            'tone' => 'Professional',
            'intervention' => 'InformationProvision',
            'matched_rule' => 'default',
            'confidence' => 0.5
        ];
    }

    /**
     * 컨텍스트 보강 - 서브클래스에서 확장
     *
     * @param array $context 기본 컨텍스트
     * @return array 보강된 컨텍스트
     */
    protected function enrichContext(array $context): array {
        $context['agent_id'] = $this->agentId;
        $context['agent_data'] = $this->dataContext->loadAgentData($this->agentId);
        return $context;
    }

    /**
     * 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param array $persona 페르소나
     * @return void
     */
    protected function saveState(int $userId, array $persona): void {
        global $DB;

        try {
            $record = new \stdClass();
            $record->userid = $userId;
            $record->agent_id = $this->agentId;
            $record->persona_id = $persona['persona_id'];
            $record->state_data = json_encode($persona);
            $record->timecreated = time();
            $record->timemodified = time();

            // 기존 레코드 확인
            $existing = $DB->get_record('at_agent_persona_state', [
                'userid' => $userId,
                'agent_id' => $this->agentId
            ]);

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('at_agent_persona_state', $record);
            } else {
                $DB->insert_record('at_agent_persona_state', $record);
            }
        } catch (\Exception $e) {
            $this->logError("상태 저장 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 디버그 정보 반환
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        return [
            'agent_id' => $this->agentId,
            'config' => $this->config,
            'rules_loaded' => !empty($this->rules),
            'personas_count' => count($this->rules['personas'] ?? [])
        ];
    }

    /**
     * 설정 변경
     *
     * @param string $key 키
     * @param mixed $value 값
     * @return void
     */
    public function setConfig(string $key, $value): void {
        $this->config[$key] = $value;
    }

    /**
     * 에러 로깅
     *
     * @param string $message 메시지
     * @param int $line 라인 번호
     * @return void
     */
    protected function logError(string $message, int $line): void {
        error_log("[{$this->agentId} PersonaEngine ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 디버그 로깅
     *
     * @param string $message 메시지
     * @return void
     */
    protected function logDebug(string $message): void {
        if ($this->config['debug_mode']) {
            error_log("[{$this->agentId} PersonaEngine DEBUG] {$message}");
        }
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - agent_id: varchar(50) NOT NULL
 *   - persona_id: varchar(50) NOT NULL
 *   - state_data: longtext
 *   - timecreated: bigint(10) NOT NULL
 *   - timemodified: bigint(10) NOT NULL
 *
 * 참조 파일:
 * - agents/agent01_onboarding/persona_system/engine/PersonaRuleEngine.php
 * - agents/agent01_onboarding/persona_system/engine/AIPersonaEngine.php
 */
