<?php
/**
 * Agent18 Signature Routine - Persona Rule Engine
 *
 * 학습자의 시그너처 루틴 발견을 위한 페르소나 식별 및 응답 생성 엔진.
 * Agent01의 PersonaRuleEngine 구조를 기반으로 시그너처 루틴에 특화.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/PersonaRuleEngine.php
 */

// Moodle 환경 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관련 클래스 로드
require_once(__DIR__ . '/RuleParser.php');
require_once(__DIR__ . '/ConditionEvaluator.php');
require_once(__DIR__ . '/ActionExecutor.php');
require_once(__DIR__ . '/DataContext.php');
require_once(__DIR__ . '/RoutineAnalyzer.php');
require_once(__DIR__ . '/ResponseGenerator.php');

class PersonaRuleEngine {

    /** @var array 로드된 규칙 목록 */
    private $rules = [];

    /** @var DataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var ConditionEvaluator 조건 평가기 */
    private $conditionEvaluator;

    /** @var ActionExecutor 액션 실행기 */
    private $actionExecutor;

    /** @var RoutineAnalyzer 루틴 분석기 */
    private $routineAnalyzer;

    /** @var ResponseGenerator 응답 생성기 */
    private $responseGenerator;

    /** @var int 현재 사용자 ID */
    private $userId;

    /** @var string 현재 세션 ID */
    private $sessionId;

    /** @var array 식별된 페르소나 목록 */
    private $identifiedPersonas = [];

    /** @var string 현재 컨텍스트 */
    private $currentContext = '';

    /** @var array 처리 결과 */
    private $processingResult = [];

    /** @var string 규칙 파일 경로 */
    private $rulesPath;

    /**
     * 생성자
     *
     * @param int $userId 사용자 ID
     * @param string|null $sessionId 세션 ID (없으면 새로 생성)
     */
    public function __construct($userId, $sessionId = null) {
        $this->userId = $userId;
        $this->sessionId = $sessionId ?? $this->generateSessionId();
        $this->rulesPath = __DIR__ . '/../rules.yaml';

        $this->initialize();
    }

    /**
     * 엔진 초기화
     */
    private function initialize() {
        try {
            // 컴포넌트 초기화
            $this->dataContext = new DataContext($this->userId, $this->sessionId);
            $this->conditionEvaluator = new ConditionEvaluator($this->dataContext);
            $this->actionExecutor = new ActionExecutor($this->dataContext);
            $this->routineAnalyzer = new RoutineAnalyzer($this->userId);
            $this->responseGenerator = new ResponseGenerator(__DIR__ . '/../templates/');

            // 규칙 로드
            $this->loadRules();

            // 이전 페르소나 기록 로드
            $this->loadPreviousPersonas();

        } catch (Exception $e) {
            error_log("[Agent18 PersonaRuleEngine] 초기화 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            throw $e;
        }
    }

    /**
     * 규칙 파일 로드
     */
    private function loadRules() {
        if (!file_exists($this->rulesPath)) {
            throw new Exception("규칙 파일을 찾을 수 없습니다: {$this->rulesPath}");
        }

        $ruleParser = new RuleParser();
        $this->rules = $ruleParser->parse($this->rulesPath);

        // 우선순위순 정렬
        usort($this->rules, function($a, $b) {
            return ($b['priority'] ?? 50) - ($a['priority'] ?? 50);
        });
    }

    /**
     * 이전 페르소나 기록 로드
     */
    private function loadPreviousPersonas() {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT persona_id, confidence, identified_at
             FROM {alt42_agent18_persona_records}
             WHERE userid = ?
             ORDER BY identified_at DESC
             LIMIT 10",
            [$this->userId]
        );

        foreach ($records as $record) {
            $this->identifiedPersonas[$record->persona_id] = [
                'confidence' => $record->confidence,
                'identified_at' => $record->identified_at
            ];
        }
    }

    /**
     * 메시지 처리 메인 함수
     *
     * @param string $message 사용자 메시지
     * @param array $additionalContext 추가 컨텍스트 데이터
     * @return array 처리 결과
     */
    public function process($message, $additionalContext = []) {
        $startTime = microtime(true);

        try {
            // 1. 데이터 컨텍스트 업데이트
            $this->dataContext->setMessage($message);
            $this->dataContext->setAdditionalContext($additionalContext);
            $this->dataContext->loadRoutineData();

            // 2. 루틴 분석 데이터 수집
            $routineData = $this->routineAnalyzer->analyze();
            $this->dataContext->setRoutineAnalysis($routineData);

            // 3. 규칙 평가 및 페르소나 식별
            $matchedRules = $this->evaluateRules();

            // 4. 액션 실행
            $actions = $this->executeActions($matchedRules);

            // 5. 컨텍스트 결정
            $context = $this->determineContext();

            // 6. 응답 생성
            $response = $this->generateResponse($context, $actions);

            // 7. 결과 저장
            $this->saveProcessingResult($matchedRules, $actions, $response);

            $processingTime = microtime(true) - $startTime;

            return [
                'success' => true,
                'response' => $response,
                'personas' => $this->identifiedPersonas,
                'context' => $context,
                'routine_analysis' => $routineData,
                'processing_time' => $processingTime,
                'session_id' => $this->sessionId
            ];

        } catch (Exception $e) {
            error_log("[Agent18 PersonaRuleEngine] 처리 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_location' => __FILE__ . ":" . __LINE__
            ];
        }
    }

    /**
     * 규칙 평가
     *
     * @return array 매칭된 규칙 목록
     */
    private function evaluateRules() {
        $matchedRules = [];

        foreach ($this->rules as $rule) {
            if (!isset($rule['conditions'])) {
                continue;
            }

            $result = $this->conditionEvaluator->evaluate($rule['conditions']);

            if ($result['matched']) {
                $matchedRules[] = [
                    'rule' => $rule,
                    'match_score' => $result['score'],
                    'matched_conditions' => $result['matched_conditions']
                ];
            }
        }

        return $matchedRules;
    }

    /**
     * 액션 실행
     *
     * @param array $matchedRules 매칭된 규칙들
     * @return array 실행된 액션 결과
     */
    private function executeActions($matchedRules) {
        $executedActions = [];

        foreach ($matchedRules as $match) {
            if (!isset($match['rule']['actions'])) {
                continue;
            }

            foreach ($match['rule']['actions'] as $action) {
                $result = $this->actionExecutor->execute($action);

                // 페르소나 식별 액션 처리
                if ($action['type'] === 'identify_persona') {
                    $this->identifiedPersonas[$action['persona']] = [
                        'confidence' => $action['confidence'] ?? 0.5,
                        'identified_at' => time(),
                        'rule_id' => $match['rule']['id'] ?? 'unknown'
                    ];
                }

                // 컨텍스트 설정 액션 처리
                if ($action['type'] === 'set_context') {
                    $this->currentContext = $action['context'];
                }

                $executedActions[] = [
                    'action' => $action,
                    'result' => $result,
                    'rule_id' => $match['rule']['id'] ?? 'unknown'
                ];
            }
        }

        return $executedActions;
    }

    /**
     * 컨텍스트 결정
     *
     * @return string 결정된 컨텍스트
     */
    private function determineContext() {
        // 명시적으로 설정된 컨텍스트가 있으면 사용
        if (!empty($this->currentContext)) {
            return $this->currentContext;
        }

        // 루틴 분석 데이터 기반 컨텍스트 결정
        $routineData = $this->dataContext->getRoutineAnalysis();

        // 첫 분석 단계
        if (($routineData['routine_analysis_count'] ?? 0) == 0) {
            return 'SR01'; // 첫 루틴 분석 시작
        }

        // 패턴 발견 단계
        if (($routineData['pattern_confidence'] ?? 0) >= 0.7 &&
            !($routineData['pattern_notified'] ?? false)) {
            return 'SR02'; // 루틴 패턴 발견
        }

        // 골든타임 발견
        if (($routineData['time_performance_ratio'] ?? 0) >= 1.3 &&
            !($routineData['golden_time_notified'] ?? false)) {
            return 'TP02'; // 골든타임 발견
        }

        // 기본 컨텍스트
        return 'SR01';
    }

    /**
     * 응답 생성
     *
     * @param string $context 컨텍스트
     * @param array $actions 실행된 액션들
     * @return array 생성된 응답
     */
    private function generateResponse($context, $actions) {
        // 톤 결정
        $tone = $this->determineTone($actions);

        // 주요 페르소나 결정
        $primaryPersona = $this->getPrimaryPersona();

        // 루틴 추천 데이터
        $routineRecommendation = $this->routineAnalyzer->getRecommendation();

        return $this->responseGenerator->generate([
            'context' => $context,
            'tone' => $tone,
            'persona' => $primaryPersona,
            'routine_data' => $this->dataContext->getRoutineAnalysis(),
            'recommendation' => $routineRecommendation,
            'user_message' => $this->dataContext->getMessage()
        ]);
    }

    /**
     * 톤 결정
     *
     * @param array $actions 실행된 액션들
     * @return string 결정된 톤
     */
    private function determineTone($actions) {
        foreach ($actions as $action) {
            if ($action['action']['type'] === 'set_tone') {
                return $action['action']['value'];
            }
        }
        return 'friendly_exploratory'; // 기본 톤
    }

    /**
     * 주요 페르소나 반환
     *
     * @return string|null 주요 페르소나 ID
     */
    private function getPrimaryPersona() {
        if (empty($this->identifiedPersonas)) {
            return null;
        }

        // 가장 높은 신뢰도의 페르소나 반환
        $primary = null;
        $maxConfidence = 0;

        foreach ($this->identifiedPersonas as $personaId => $data) {
            if ($data['confidence'] > $maxConfidence) {
                $maxConfidence = $data['confidence'];
                $primary = $personaId;
            }
        }

        return $primary;
    }

    /**
     * 처리 결과 저장
     *
     * @param array $matchedRules 매칭된 규칙들
     * @param array $actions 실행된 액션들
     * @param array $response 생성된 응답
     */
    private function saveProcessingResult($matchedRules, $actions, $response) {
        global $DB;

        // 페르소나 기록 저장
        foreach ($this->identifiedPersonas as $personaId => $data) {
            $existing = $DB->get_record('alt42_agent18_persona_records', [
                'userid' => $this->userId,
                'persona_id' => $personaId
            ]);

            $record = new stdClass();
            $record->userid = $this->userId;
            $record->persona_id = $personaId;
            $record->confidence = $data['confidence'];
            $record->identified_at = time();
            $record->rule_id = $data['rule_id'] ?? 'unknown';
            $record->session_id = $this->sessionId;

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('alt42_agent18_persona_records', $record);
            } else {
                $DB->insert_record('alt42_agent18_persona_records', $record);
            }
        }

        // 세션 로그 저장
        $sessionLog = new stdClass();
        $sessionLog->userid = $this->userId;
        $sessionLog->session_id = $this->sessionId;
        $sessionLog->context = $this->currentContext;
        $sessionLog->matched_rules = json_encode(array_column($matchedRules, 'rule'));
        $sessionLog->personas = json_encode($this->identifiedPersonas);
        $sessionLog->created_at = time();

        $DB->insert_record('alt42_agent18_session_logs', $sessionLog);
    }

    /**
     * 세션 ID 생성
     *
     * @return string 생성된 세션 ID
     */
    private function generateSessionId() {
        return 'SR18_' . uniqid() . '_' . time();
    }

    /**
     * 현재 식별된 페르소나 반환
     *
     * @return array 식별된 페르소나 목록
     */
    public function getIdentifiedPersonas() {
        return $this->identifiedPersonas;
    }

    /**
     * 루틴 추천 가져오기
     *
     * @return array 루틴 추천 데이터
     */
    public function getRoutineRecommendation() {
        return $this->routineAnalyzer->getRecommendation();
    }

    /**
     * 세션 ID 반환
     *
     * @return string 세션 ID
     */
    public function getSessionId() {
        return $this->sessionId;
    }
}

/*
 * DB 테이블 정보:
 *
 * 1. mdl_alt42_agent18_persona_records
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - persona_id: varchar(50) NOT NULL
 *    - confidence: decimal(3,2) NOT NULL
 *    - identified_at: bigint(10) NOT NULL
 *    - rule_id: varchar(100)
 *    - session_id: varchar(100)
 *
 * 2. mdl_alt42_agent18_session_logs
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - session_id: varchar(100) NOT NULL
 *    - context: varchar(50)
 *    - matched_rules: text
 *    - personas: text
 *    - created_at: bigint(10) NOT NULL
 *
 * 3. mdl_alt42_agent18_routine_patterns
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - pattern_type: varchar(50) NOT NULL
 *    - pattern_data: text NOT NULL
 *    - confidence: decimal(3,2) NOT NULL
 *    - created_at: bigint(10) NOT NULL
 *    - updated_at: bigint(10)
 */
