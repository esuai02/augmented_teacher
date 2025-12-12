<?php
/**
 * AbstractPersonaEngine.php
 *
 * 모든 에이전트 Persona Engine의 추상 기반 클래스
 * PersonaEngineInterface를 구현하고 공통 기능을 제공
 *
 * 이 클래스를 상속받아 각 에이전트(Agent01~Agent21)별 PersonaEngine을 구현
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/base/AbstractPersonaEngine.php
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../interfaces/PersonaEngineInterface.php');
require_once(__DIR__ . '/../interfaces/DataContextInterface.php');
require_once(__DIR__ . '/../interfaces/CommunicatorInterface.php');
require_once(__DIR__ . '/../config/engine_config.php');

abstract class AbstractPersonaEngine implements PersonaEngineInterface
{
    /** @var int 에이전트 번호 (1-21) */
    protected $agentNumber;

    /** @var string 에이전트 이름 */
    protected $agentName;

    /** @var DataContextInterface 데이터 컨텍스트 */
    protected $dataContext;

    /** @var CommunicatorInterface 에이전트 간 통신 */
    protected $communicator;

    /** @var array 로드된 규칙 */
    protected $rules = [];

    /** @var array 규칙 캐시 */
    protected $ruleCache = [];

    /** @var int 캐시 만료 시간 */
    protected $cacheExpiry = 0;

    /** @var bool 초기화 상태 */
    protected $initialized = false;

    /** @var array 설정 */
    protected $config = [];

    /** @var object Moodle DB 객체 */
    protected $db;

    /** @var object 현재 사용자 */
    protected $user;

    /**
     * 생성자
     *
     * @param int                        $agentNumber  에이전트 번호 (1-21)
     * @param DataContextInterface|null  $dataContext  데이터 컨텍스트 (선택적)
     * @param CommunicatorInterface|null $communicator 통신 인터페이스 (선택적)
     */
    public function __construct(
        int $agentNumber,
        DataContextInterface $dataContext = null,
        CommunicatorInterface $communicator = null
    ) {
        global $DB, $USER;

        $this->db = $DB;
        $this->user = $USER;

        // 에이전트 번호 유효성 검사
        if ($agentNumber < 1 || $agentNumber > 21) {
            throw new InvalidArgumentException(
                "[AbstractPersonaEngine:" . __LINE__ . "] Invalid agent number: {$agentNumber}. Must be between 1 and 21."
            );
        }

        $this->agentNumber = $agentNumber;

        // 에이전트 정보 로드
        $agentInfo = get_agent_info($agentNumber);
        if (!$agentInfo) {
            throw new RuntimeException(
                "[AbstractPersonaEngine:" . __LINE__ . "] Agent config not found for agent: {$agentNumber}"
            );
        }
        $this->agentName = $agentInfo['name'];

        // 의존성 주입 또는 기본 생성
        $this->dataContext = $dataContext;
        $this->communicator = $communicator;

        // 기본 설정 로드
        $this->config = PERSONA_ENGINE_CONFIG;
    }

    // =========================================================================
    // PersonaEngineInterface 구현 - 기본 메서드
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getAgentNumber(): int
    {
        return $this->agentNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentName(): string
    {
        return $this->agentName;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $config = []): bool
    {
        try {
            // 설정 병합
            $this->config = array_merge($this->config, $config);

            // 규칙 로드
            $this->rules = $this->loadRules();

            // 에이전트별 초기화 (추상 메서드)
            $this->onInitialize();

            $this->initialized = true;
            $this->logActivity('initialize', 'Agent initialized successfully');

            return true;
        } catch (Exception $e) {
            $this->logError('initialize', $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function identifyPersona(int $userId, array $contextData = []): array
    {
        $this->ensureInitialized();

        try {
            // 컨텍스트 데이터 빌드
            if (empty($contextData) && $this->dataContext) {
                $contextData = $this->dataContext->buildContext($userId);
            }

            // 현재 페르소나 상태 조회
            $currentState = $this->getPersonaState($userId);

            // 규칙 기반 페르소나 식별 (추상 메서드 호출)
            $identified = $this->doIdentifyPersona($userId, $contextData, $currentState);

            // 신뢰도 검증
            if ($identified['confidence'] < $this->config['min_confidence']) {
                $identified['persona_code'] = $this->config['default_persona'];
                $identified['metadata']['low_confidence'] = true;
            }

            // 페르소나 전환 체크
            if ($currentState && $currentState['persona_code'] !== $identified['persona_code']) {
                $canTransition = $this->canTransition($userId, $currentState['persona_code']);
                if (!$canTransition) {
                    // 쿨다운 중이면 현재 페르소나 유지
                    $identified = [
                        'persona_code' => $currentState['persona_code'],
                        'confidence'   => $currentState['confidence'],
                        'metadata'     => array_merge($identified['metadata'] ?? [], ['cooldown_active' => true])
                    ];
                }
            }

            // 상태 저장
            $this->savePersonaState(
                $userId,
                $identified['persona_code'],
                $identified['confidence'],
                $contextData
            );

            return $identified;

        } catch (Exception $e) {
            $this->logError('identifyPersona', $e->getMessage(), ['userId' => $userId]);
            return [
                'persona_code' => $this->config['default_persona'],
                'confidence'   => 0.0,
                'metadata'     => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateResponse(int $userId, string $personaCode, string $userMessage, array $options = []): array
    {
        $this->ensureInitialized();

        try {
            // 응답 생성 (추상 메서드 호출)
            $response = $this->doGenerateResponse($userId, $personaCode, $userMessage, $options);

            // 액션 실행
            if (!empty($response['actions'])) {
                $context = $this->dataContext ? $this->dataContext->buildContext($userId) : [];
                $this->executeActions($response['actions'], $userId, $context);
            }

            $this->logActivity('generateResponse', 'Response generated', [
                'userId'      => $userId,
                'personaCode' => $personaCode,
                'templateId'  => $response['template_id'] ?? 'unknown'
            ]);

            return $response;

        } catch (Exception $e) {
            $this->logError('generateResponse', $e->getMessage(), [
                'userId'      => $userId,
                'personaCode' => $personaCode
            ]);
            return [
                'response'    => '죄송합니다. 응답을 생성하는 중 오류가 발생했습니다.',
                'template_id' => 'error',
                'actions'     => [],
                'error'       => $e->getMessage()
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleTransition(int $userId, string $fromPersona, string $toPersona, array $triggerData = []): bool
    {
        try {
            // 전환 가능 여부 확인
            if (!$this->canTransition($userId, $fromPersona)) {
                return false;
            }

            // 전환 이력 저장
            $transitionRecord = [
                'user_id'          => $userId,
                'nagent'           => $this->agentNumber,
                'from_persona'     => $fromPersona,
                'to_persona'       => $toPersona,
                'trigger_type'     => $triggerData['trigger_type'] ?? 'manual',
                'confidence'       => $triggerData['confidence'] ?? 1.0,
                'context_snapshot' => json_encode($triggerData),
                'timecreated'      => time()
            ];

            $this->db->insert_record('at_agent_transitions', (object)$transitionRecord);

            // 다른 에이전트에 알림 (필요시)
            if ($this->communicator && !empty($triggerData['notify_agents'])) {
                foreach ($triggerData['notify_agents'] as $targetAgent) {
                    $this->communicator->send($targetAgent, 'persona_transition', [
                        'user_id'     => $userId,
                        'from'        => $fromPersona,
                        'to'          => $toPersona,
                        'source_agent'=> $this->agentNumber
                    ]);
                }
            }

            // 에이전트별 전환 후 처리 (추상 메서드)
            $this->onTransition($userId, $fromPersona, $toPersona, $triggerData);

            $this->logActivity('handleTransition', 'Persona transitioned', [
                'userId' => $userId,
                'from'   => $fromPersona,
                'to'     => $toPersona
            ]);

            return true;

        } catch (Exception $e) {
            $this->logError('handleTransition', $e->getMessage(), [
                'userId' => $userId,
                'from'   => $fromPersona,
                'to'     => $toPersona
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadRules(): array
    {
        // 캐시 확인
        if (!empty($this->ruleCache) && time() < $this->cacheExpiry) {
            return $this->ruleCache;
        }

        try {
            // 규칙 파일 경로 (에이전트별 오버라이드 가능)
            $rulesPath = $this->getRulesFilePath();

            if (!file_exists($rulesPath)) {
                throw new RuntimeException(
                    "[AbstractPersonaEngine:" . __LINE__ . "] Rules file not found: {$rulesPath}"
                );
            }

            // YAML 파싱
            $content = file_get_contents($rulesPath);
            $rules = $this->parseYaml($content);

            // 캐시 저장
            $this->ruleCache = $rules;
            $this->cacheExpiry = time() + $this->config['cache_ttl'];

            return $rules;

        } catch (Exception $e) {
            $this->logError('loadRules', $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateConditions(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluateSingleCondition($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function executeActions(array $actions, int $userId, array $context): array
    {
        $results = [];

        foreach ($actions as $action) {
            try {
                $result = $this->executeSingleAction($action, $userId, $context);
                $results[] = [
                    'action'  => $action['type'] ?? 'unknown',
                    'success' => true,
                    'result'  => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'action'  => $action['type'] ?? 'unknown',
                    'success' => false,
                    'error'   => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function savePersonaState(int $userId, string $personaCode, float $confidence, array $contextData = []): bool
    {
        try {
            $tableName = DB_TABLES['common']['persona_state'];
            $now = time();

            // 기존 상태 확인
            $existing = $this->db->get_record($tableName, [
                'user_id' => $userId,
                'nagent'  => $this->agentNumber
            ]);

            $data = [
                'persona_code'  => $personaCode,
                'confidence'    => $confidence,
                'context_data'  => json_encode($contextData),
                'timemodified'  => $now
            ];

            if ($existing) {
                $data['id'] = $existing->id;
                $this->db->update_record($tableName, (object)$data);
            } else {
                $data['user_id']     = $userId;
                $data['nagent']      = $this->agentNumber;
                $data['timecreated'] = $now;
                $this->db->insert_record($tableName, (object)$data);
            }

            return true;

        } catch (Exception $e) {
            $this->logError('savePersonaState', $e->getMessage(), ['userId' => $userId]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPersonaState(int $userId): ?array
    {
        try {
            $tableName = DB_TABLES['common']['persona_state'];

            $record = $this->db->get_record($tableName, [
                'user_id' => $userId,
                'nagent'  => $this->agentNumber
            ]);

            if (!$record) {
                return null;
            }

            return [
                'persona_code'  => $record->persona_code,
                'confidence'    => (float)$record->confidence,
                'context_data'  => json_decode($record->context_data, true) ?? [],
                'timecreated'   => (int)$record->timecreated,
                'timemodified'  => (int)$record->timemodified
            ];

        } catch (Exception $e) {
            $this->logError('getPersonaState', $e->getMessage(), ['userId' => $userId]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function healthCheck(): array
    {
        $healthy = true;
        $details = [];
        $warnings = [];

        // 초기화 상태 확인
        if (!$this->initialized) {
            $healthy = false;
            $details['initialization'] = 'Not initialized';
        } else {
            $details['initialization'] = 'OK';
        }

        // 규칙 로드 상태 확인
        if (empty($this->rules)) {
            $warnings[] = 'No rules loaded';
        } else {
            $details['rules_count'] = count($this->rules);
        }

        // DB 연결 확인
        try {
            $this->db->get_record_sql('SELECT 1');
            $details['database'] = 'Connected';
        } catch (Exception $e) {
            $healthy = false;
            $details['database'] = 'Disconnected: ' . $e->getMessage();
        }

        // DataContext 상태 확인
        if ($this->dataContext) {
            $sourceStatus = $this->dataContext->checkDataSources();
            $details['data_sources'] = $sourceStatus['available'] ? 'Available' : 'Unavailable';
        }

        // Communicator 상태 확인
        if ($this->communicator) {
            $queueStatus = $this->communicator->getQueueStatus();
            $details['message_queue'] = $queueStatus;
            if ($queueStatus['failed'] > 10) {
                $warnings[] = 'High number of failed messages: ' . $queueStatus['failed'];
            }
        }

        // 에이전트별 추가 헬스체크 (추상 메서드)
        $agentHealth = $this->doHealthCheck();
        $details = array_merge($details, $agentHealth['details'] ?? []);
        $warnings = array_merge($warnings, $agentHealth['warnings'] ?? []);
        if (!($agentHealth['healthy'] ?? true)) {
            $healthy = false;
        }

        return [
            'healthy'  => $healthy,
            'details'  => $details,
            'warnings' => $warnings,
            'agent'    => [
                'number' => $this->agentNumber,
                'name'   => $this->agentName
            ]
        ];
    }

    // =========================================================================
    // 추상 메서드 - 각 에이전트에서 구현 필요
    // =========================================================================

    /**
     * 에이전트별 초기화 로직
     * initialize() 에서 호출됨
     */
    abstract protected function onInitialize(): void;

    /**
     * 에이전트별 페르소나 식별 로직
     *
     * @param int        $userId       사용자 ID
     * @param array      $contextData  컨텍스트 데이터
     * @param array|null $currentState 현재 페르소나 상태
     * @return array ['persona_code' => string, 'confidence' => float, 'metadata' => array]
     */
    abstract protected function doIdentifyPersona(int $userId, array $contextData, ?array $currentState): array;

    /**
     * 에이전트별 응답 생성 로직
     *
     * @param int    $userId      사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $userMessage 사용자 메시지
     * @param array  $options     추가 옵션
     * @return array ['response' => string, 'template_id' => string, 'actions' => array]
     */
    abstract protected function doGenerateResponse(int $userId, string $personaCode, string $userMessage, array $options): array;

    /**
     * 페르소나 전환 후 처리 로직
     *
     * @param int    $userId      사용자 ID
     * @param string $fromPersona 이전 페르소나
     * @param string $toPersona   새 페르소나
     * @param array  $triggerData 트리거 데이터
     */
    abstract protected function onTransition(int $userId, string $fromPersona, string $toPersona, array $triggerData): void;

    /**
     * 에이전트별 헬스체크 로직
     *
     * @return array ['healthy' => bool, 'details' => array, 'warnings' => array]
     */
    abstract protected function doHealthCheck(): array;

    /**
     * 규칙 파일 경로 반환
     *
     * @return string rules.yaml 파일 경로
     */
    abstract protected function getRulesFilePath(): string;

    // =========================================================================
    // Protected 헬퍼 메서드
    // =========================================================================

    /**
     * 초기화 상태 확인
     *
     * @throws RuntimeException 초기화되지 않은 경우
     */
    protected function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new RuntimeException(
                "[AbstractPersonaEngine:" . __LINE__ . "] Engine not initialized. Call initialize() first."
            );
        }
    }

    /**
     * 페르소나 전환 가능 여부 확인 (쿨다운 체크)
     *
     * @param int    $userId      사용자 ID
     * @param string $fromPersona 현재 페르소나
     * @return bool 전환 가능 여부
     */
    protected function canTransition(int $userId, string $fromPersona): bool
    {
        try {
            $tableName = DB_TABLES['common']['transitions'];
            $cooldown = $this->config['transition_cooldown'];

            $lastTransition = $this->db->get_record_sql(
                "SELECT MAX(timecreated) as last_time FROM {{$tableName}}
                 WHERE user_id = ? AND nagent = ? AND from_persona = ?",
                [$userId, $this->agentNumber, $fromPersona]
            );

            if (!$lastTransition || !$lastTransition->last_time) {
                return true;
            }

            return (time() - $lastTransition->last_time) >= $cooldown;

        } catch (Exception $e) {
            // 에러 시 전환 허용
            return true;
        }
    }

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 정의
     * @param array $context   컨텍스트 데이터
     * @return bool 조건 충족 여부
     */
    protected function evaluateSingleCondition(array $condition, array $context): bool
    {
        $field    = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'eq';
        $value    = $condition['value'] ?? null;

        if (!$field) {
            return false;
        }

        // 중첩 필드 지원 (예: 'emotion.level')
        $actualValue = $this->getNestedValue($context, $field);

        switch ($operator) {
            case 'eq':
            case '==':
                return $actualValue == $value;
            case 'neq':
            case '!=':
                return $actualValue != $value;
            case 'gt':
            case '>':
                return $actualValue > $value;
            case 'gte':
            case '>=':
                return $actualValue >= $value;
            case 'lt':
            case '<':
                return $actualValue < $value;
            case 'lte':
            case '<=':
                return $actualValue <= $value;
            case 'in':
                return in_array($actualValue, (array)$value);
            case 'not_in':
                return !in_array($actualValue, (array)$value);
            case 'contains':
                return strpos((string)$actualValue, (string)$value) !== false;
            case 'regex':
                return preg_match($value, (string)$actualValue) === 1;
            case 'exists':
                return $actualValue !== null;
            case 'not_exists':
                return $actualValue === null;
            default:
                return false;
        }
    }

    /**
     * 단일 액션 실행
     *
     * @param array $action  액션 정의
     * @param int   $userId  사용자 ID
     * @param array $context 컨텍스트 데이터
     * @return mixed 액션 결과
     */
    protected function executeSingleAction(array $action, int $userId, array $context)
    {
        $type = $action['type'] ?? 'unknown';

        switch ($type) {
            case 'notify_agent':
                return $this->executeNotifyAction($action, $userId, $context);
            case 'save_data':
                return $this->executeSaveDataAction($action, $userId, $context);
            case 'trigger_event':
                return $this->executeTriggerEventAction($action, $userId, $context);
            case 'log':
                return $this->executeLogAction($action, $userId, $context);
            default:
                // 에이전트별 커스텀 액션 처리
                return $this->executeCustomAction($action, $userId, $context);
        }
    }

    /**
     * 에이전트 알림 액션 실행
     */
    protected function executeNotifyAction(array $action, int $userId, array $context): bool
    {
        if (!$this->communicator) {
            return false;
        }

        $targetAgent = $action['target_agent'] ?? 0;
        $messageType = $action['message_type'] ?? 'notification';
        $payload     = $action['payload'] ?? [];
        $payload['user_id'] = $userId;
        $payload['context'] = $context;

        $result = $this->communicator->send($targetAgent, $messageType, $payload);
        return $result['success'] ?? false;
    }

    /**
     * 데이터 저장 액션 실행
     */
    protected function executeSaveDataAction(array $action, int $userId, array $context): bool
    {
        if (!$this->dataContext) {
            return false;
        }

        $dataType = $action['data_type'] ?? 'generic';
        $data     = $action['data'] ?? [];

        return $this->dataContext->saveAgentData($userId, $dataType, $data);
    }

    /**
     * 이벤트 트리거 액션 실행
     */
    protected function executeTriggerEventAction(array $action, int $userId, array $context): bool
    {
        $eventName = $action['event_name'] ?? '';
        $eventData = $action['event_data'] ?? [];
        $eventData['user_id'] = $userId;
        $eventData['agent']   = $this->agentNumber;

        // Moodle 이벤트 트리거 (구현 필요시 확장)
        // events_trigger($eventName, $eventData);

        return true;
    }

    /**
     * 로그 액션 실행
     */
    protected function executeLogAction(array $action, int $userId, array $context): bool
    {
        $message = $action['message'] ?? '';
        $level   = $action['level'] ?? 'INFO';

        $this->logActivity($level, $message, ['userId' => $userId]);
        return true;
    }

    /**
     * 커스텀 액션 실행 (에이전트별 오버라이드 가능)
     */
    protected function executeCustomAction(array $action, int $userId, array $context)
    {
        // 기본 구현: 아무것도 하지 않음
        return null;
    }

    /**
     * 중첩 배열에서 값 추출
     *
     * @param array  $array 배열
     * @param string $path  점(.) 구분 경로
     * @return mixed 값 또는 null
     */
    protected function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * 간단한 YAML 파서
     * 외부 라이브러리 없이 기본적인 YAML 구문 지원
     *
     * @param string $content YAML 내용
     * @return array 파싱된 배열
     */
    protected function parseYaml(string $content): array
    {
        // PHP의 기본 YAML 확장이 있으면 사용
        if (function_exists('yaml_parse')) {
            return yaml_parse($content) ?: [];
        }

        // 간단한 YAML 파서 구현
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        $indents = [0];

        foreach ($lines as $line) {
            // 주석 및 빈 줄 무시
            if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
                continue;
            }

            // 들여쓰기 계산
            $indent = strlen($line) - strlen(ltrim($line));
            $line = trim($line);

            // 키-값 쌍 파싱
            if (strpos($line, ':') !== false) {
                list($key, $value) = array_map('trim', explode(':', $line, 2));

                // 들여쓰기 레벨 조정
                while (count($indents) > 1 && $indent <= end($indents)) {
                    array_pop($stack);
                    array_pop($indents);
                }

                if ($value === '') {
                    // 중첩 객체
                    $stack[count($stack) - 1][$key] = [];
                    $stack[] = &$stack[count($stack) - 1][$key];
                    $indents[] = $indent;
                } else {
                    // 단순 값
                    $stack[count($stack) - 1][$key] = $this->parseYamlValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * YAML 값 파싱
     */
    protected function parseYamlValue(string $value)
    {
        $value = trim($value);

        // Boolean
        if ($value === 'true' || $value === 'yes') return true;
        if ($value === 'false' || $value === 'no') return false;

        // Null
        if ($value === 'null' || $value === '~') return null;

        // Number
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // String (remove quotes)
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * 활동 로그 기록
     */
    protected function logActivity(string $action, string $message, array $data = []): void
    {
        if (!LOGGING_CONFIG['enabled']) {
            return;
        }

        try {
            $logEntry = [
                'nagent'      => $this->agentNumber,
                'action'      => $action,
                'message'     => $message,
                'data'        => json_encode($data),
                'level'       => 'INFO',
                'timecreated' => time()
            ];

            // DB 로그 테이블이 있으면 저장
            if (isset(DB_TABLES['common']['agent_logs'])) {
                $this->db->insert_record(DB_TABLES['common']['agent_logs'], (object)$logEntry);
            }
        } catch (Exception $e) {
            // 로깅 실패는 무시
        }
    }

    /**
     * 에러 로그 기록
     */
    protected function logError(string $action, string $message, array $data = []): void
    {
        if (!LOGGING_CONFIG['enabled']) {
            return;
        }

        try {
            $logEntry = [
                'nagent'      => $this->agentNumber,
                'action'      => $action,
                'message'     => $message,
                'data'        => json_encode($data),
                'level'       => 'ERROR',
                'timecreated' => time()
            ];

            if (isset(DB_TABLES['common']['agent_logs'])) {
                $this->db->insert_record(DB_TABLES['common']['agent_logs'], (object)$logEntry);
            }

            // 에러는 PHP 에러 로그에도 기록
            error_log("[Agent{$this->agentNumber}:{$action}] {$message} - " . json_encode($data));
        } catch (Exception $e) {
            error_log("[Agent{$this->agentNumber}:logError] Failed to log error: " . $e->getMessage());
        }
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 이 클래스가 사용하는 DB 테이블:
 *
 * 테이블명: mdl_at_agent_persona_state
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key, Auto Increment        │
 * │ user_id         │ BIGINT           │ 사용자 ID                           │
 * │ nagent          │ TINYINT          │ 에이전트 번호 (1-21)                 │
 * │ persona_code    │ VARCHAR(20)      │ 페르소나 코드                        │
 * │ confidence      │ DECIMAL(3,2)     │ 신뢰도 (0.00-1.00)                  │
 * │ context_data    │ JSON             │ 컨텍스트 데이터                      │
 * │ timecreated     │ INT              │ 생성 시간 (Unix timestamp)          │
 * │ timemodified    │ INT              │ 수정 시간 (Unix timestamp)          │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 테이블명: mdl_at_agent_transitions
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key, Auto Increment        │
 * │ user_id         │ BIGINT           │ 사용자 ID                           │
 * │ nagent          │ TINYINT          │ 에이전트 번호 (1-21)                 │
 * │ from_persona    │ VARCHAR(20)      │ 이전 페르소나                        │
 * │ to_persona      │ VARCHAR(20)      │ 새 페르소나                          │
 * │ trigger_type    │ VARCHAR(50)      │ 전환 트리거 유형                     │
 * │ confidence      │ DECIMAL(3,2)     │ 전환 신뢰도                          │
 * │ context_snapshot│ JSON             │ 전환 시점 컨텍스트 스냅샷            │
 * │ timecreated     │ INT              │ 전환 시간 (Unix timestamp)          │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 테이블명: mdl_at_agent_logs
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key, Auto Increment        │
 * │ nagent          │ TINYINT          │ 에이전트 번호 (1-21)                 │
 * │ action          │ VARCHAR(50)      │ 수행 액션                            │
 * │ message         │ TEXT             │ 로그 메시지                          │
 * │ data            │ JSON             │ 추가 데이터                          │
 * │ level           │ VARCHAR(10)      │ 로그 레벨 (INFO, ERROR 등)          │
 * │ timecreated     │ INT              │ 생성 시간 (Unix timestamp)          │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
