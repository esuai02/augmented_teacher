<?php
/**
 * CalmnessPersonaRuleEngine - Agent08 침착성 기반 페르소나 규칙 엔진
 *
 * 학생의 침착성 상태를 분석하여 적절한 개입 전략과 응답을 생성합니다.
 * 침착성 점수 범위: C95(95+), C90(90-94), C85(85-89), C80(80-84), C75(75-79), C_crisis(<75)
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseResponseGenerator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/communication/AgentCommunicator.php');
require_once(__DIR__ . '/CalmnessDataContext.php');
require_once(__DIR__ . '/CalmnessResponseGenerator.php');
require_once(__DIR__ . '/CalmnessNLUAnalyzer.php');
require_once(__DIR__ . '/CalmnessRuleParser.php');
require_once(__DIR__ . '/CalmnessConditionEvaluator.php');
require_once(__DIR__ . '/CalmnessActionExecutor.php');

class CalmnessPersonaRuleEngine {

    /** @var string 에이전트 ID */
    private $agentId = 'agent08';

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 로드된 규칙 */
    private $rules = [];

    /** @var CalmnessDataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var CalmnessResponseGenerator 응답 생성기 */
    private $responseGenerator;

    /** @var CalmnessNLUAnalyzer NLU 분석기 */
    private $nluAnalyzer;

    /** @var CalmnessRuleParser 규칙 파서 */
    private $ruleParser;

    /** @var CalmnessConditionEvaluator 조건 평가기 */
    private $conditionEvaluator;

    /** @var CalmnessActionExecutor 액션 실행기 */
    private $actionExecutor;

    /** @var AgentCommunicator 에이전트 통신 */
    private $communicator;

    /** @var array 설정 */
    private $config = [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'debug_mode' => false,
        'log_enabled' => true,
        'nlu_enabled' => true,
        'breathing_exercise_enabled' => true,
        'grounding_exercise_enabled' => true,
        'crisis_alert_threshold' => 60
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 옵션
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);

        // 핵심 컴포넌트 초기화
        $this->dataContext = new CalmnessDataContext();
        $this->responseGenerator = new CalmnessResponseGenerator();
        $this->nluAnalyzer = new CalmnessNLUAnalyzer();
        $this->ruleParser = new CalmnessRuleParser();
        $this->conditionEvaluator = new CalmnessConditionEvaluator();
        $this->actionExecutor = new CalmnessActionExecutor();

        // 에이전트 통신 초기화
        try {
            $this->communicator = new AgentCommunicator($this->agentId);
        } catch (Exception $e) {
            $this->logWarning("에이전트 통신 초기화 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath rules.yaml 파일 경로
     * @return bool 로드 성공 여부
     */
    public function loadRules(string $rulesPath): bool {
        try {
            $this->rules = $this->ruleParser->parseRules($rulesPath);

            // 우선순위 정렬
            if (isset($this->rules['calmness_rules'])) {
                $this->rules['calmness_rules'] = $this->ruleParser->sortByPriority(
                    $this->rules['calmness_rules']
                );
            }

            return true;

        } catch (Exception $e) {
            $this->logError("규칙 로드 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 학생 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 세션 데이터
     * @return array 학생 컨텍스트
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array {
        $baseContext = $this->dataContext->loadByUserId($userId, $sessionData);
        return $this->dataContext->loadAgentSpecificData($userId, $baseContext);
    }

    /**
     * 메시지 분석 (침착성 특화)
     *
     * @param array $context 현재 컨텍스트
     * @param string $message 사용자 메시지
     * @return array 업데이트된 컨텍스트
     */
    public function analyzeMessage(array $context, string $message): array {
        // 기본 분석
        $basicAnalysis = $this->dataContext->analyzeMessage($message);

        // 침착성 특화 NLU 분석
        $nluResult = [];
        if ($this->config['nlu_enabled']) {
            $nluResult = $this->nluAnalyzer->analyze($message);
        }

        // 컨텍스트 병합
        $mergedContext = array_merge(
            $context,
            $basicAnalysis,
            [
                'user_message' => $message,
                'nlu_analysis' => $nluResult,
                'detected_intent' => $nluResult['intent']['type'] ?? null,
                'detected_emotion' => $nluResult['emotion']['primary'] ?? null,
                'emotion_intensity' => $nluResult['emotion']['intensity'] ?? 0,
                'anxiety_indicators' => $nluResult['anxiety_indicators'] ?? [],
                'stress_level' => $nluResult['stress_level'] ?? 'normal',
                'needs_immediate_support' => $nluResult['needs_immediate_support'] ?? false
            ]
        );

        // 침착성 점수 재계산
        $mergedContext['current_calmness_score'] = $this->dataContext->calculateCurrentCalmnessScore($mergedContext);
        $mergedContext['calmness_level'] = $this->dataContext->determineCalmnessLevel(
            $mergedContext['current_calmness_score']
        );

        return $mergedContext;
    }

    /**
     * 전체 프로세스 실행
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $startTime = microtime(true);

        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->loadStudentContext($userId, $sessionData);

            // 2. 메시지 분석
            $context = $this->analyzeMessage($context, $message);

            // 3. 침착성 기반 페르소나 식별
            $identification = $this->identifyCalmnessPersona($context);

            // 4. 위기 상태 확인 및 알림
            if ($this->isInCrisis($context)) {
                $this->handleCrisisAlert($userId, $context, $identification);
            }

            // 5. 응답 생성
            $response = $this->generateResponse($identification, $context);

            // 6. 침착성 점수 저장
            $this->dataContext->saveCalmnessScore(
                $userId,
                $context['current_calmness_score'],
                [
                    'message_length' => mb_strlen($message),
                    'emotion' => $context['detected_emotion'],
                    'intent' => $context['detected_intent']
                ]
            );

            // 7. 로그 기록
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->logProcess($userId, $context, $identification, $processingTime);

            return [
                'success' => true,
                'user_id' => $userId,
                'agent_id' => $this->agentId,
                'calmness' => [
                    'score' => $context['current_calmness_score'],
                    'level' => $context['calmness_level'],
                    'trend' => $context['calmness_trend'] ?? null
                ],
                'persona' => $identification,
                'response' => $response,
                'context' => [
                    'intent' => $context['detected_intent'],
                    'emotion' => $context['detected_emotion'],
                    'stress_level' => $context['stress_level']
                ],
                'exercises' => $this->determineExercises($context),
                'processing_time_ms' => round($processingTime, 2)
            ];

        } catch (Exception $e) {
            $this->logError("프로세스 실행 실패: " . $e->getMessage(), __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'agent_id' => $this->agentId
            ];
        }
    }

    /**
     * 침착성 기반 페르소나 식별
     *
     * @param array $context 컨텍스트
     * @return array 식별 결과
     */
    public function identifyCalmnessPersona(array $context): array {
        $calmnessLevel = $context['calmness_level'] ?? 'C85';
        $calmnessScore = $context['current_calmness_score'] ?? 85;

        // 기본 결과 구조
        $result = [
            'persona_id' => $calmnessLevel,
            'persona_name' => $this->getCalmnessPersonaName($calmnessLevel),
            'calmness_score' => $calmnessScore,
            'calmness_level' => $calmnessLevel,
            'confidence' => $this->calculateConfidence($context),
            'matched_rule' => null,
            'tone' => $this->determineOptimalTone($calmnessLevel),
            'pace' => $this->determineOptimalPace($calmnessLevel),
            'intervention' => $this->determineOptimalIntervention($calmnessLevel, $context),
            'actions' => []
        ];

        // 규칙 기반 매칭
        if (!empty($this->rules['calmness_rules'])) {
            foreach ($this->rules['calmness_rules'] as $rule) {
                if ($this->conditionEvaluator->evaluate($rule['conditions'] ?? [], $context)) {
                    $result['matched_rule'] = $rule['rule_id'] ?? 'unknown';
                    $result['confidence'] = $rule['confidence'] ?? $result['confidence'];

                    if (isset($rule['actions'])) {
                        $result['actions'] = $this->actionExecutor->execute($rule['actions'], $context);
                    }

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * 응답 생성
     *
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트
     * @return array 생성된 응답
     */
    public function generateResponse(array $identification, array $context): array {
        $templateKey = $this->determineTemplateKey($identification, $context);

        // 변수 준비
        $variables = [
            'student_name' => $context['student_name'] ?? $context['firstname'] ?? '학생',
            'calmness_level' => $identification['calmness_level'],
            'calmness_score' => $identification['calmness_score'],
            'situation' => $this->getCalmnessLevelDescription($identification['calmness_level']),
            'date' => date('Y년 m월 d일'),
            'time' => date('H:i')
        ];

        // 응답 생성
        $responseText = $this->responseGenerator->generate(
            $identification,
            array_merge($context, $variables),
            $templateKey
        );

        return [
            'text' => $responseText,
            'template_key' => $templateKey,
            'tone' => $identification['tone'],
            'intervention' => $identification['intervention'],
            'calmness_level' => $identification['calmness_level'],
            'confidence' => $identification['confidence']
        ];
    }

    /**
     * 침착성 레벨별 최적 톤 결정
     *
     * @param string $calmnessLevel 침착성 레벨
     * @return string 톤
     */
    private function determineOptimalTone(string $calmnessLevel): string {
        $toneMap = [
            'C95' => 'Professional',      // 최적: 일반적 대화
            'C90' => 'Warm',              // 양호: 따뜻한 지지
            'C85' => 'Encouraging',       // 적정: 격려
            'C80' => 'Calm',              // 경미한 불안: 차분함
            'C75' => 'Empathetic',        // 중간 불안: 공감
            'C_crisis' => 'Calm'          // 위기: 매우 차분하게
        ];

        return $toneMap[$calmnessLevel] ?? 'Professional';
    }

    /**
     * 침착성 레벨별 최적 페이스 결정
     *
     * @param string $calmnessLevel 침착성 레벨
     * @return string 페이스
     */
    private function determineOptimalPace(string $calmnessLevel): string {
        $paceMap = [
            'C95' => 'normal',
            'C90' => 'normal',
            'C85' => 'normal',
            'C80' => 'slow',
            'C75' => 'slow',
            'C_crisis' => 'very_slow'
        ];

        return $paceMap[$calmnessLevel] ?? 'normal';
    }

    /**
     * 침착성 레벨 및 컨텍스트 기반 최적 개입 전략 결정
     *
     * @param string $calmnessLevel 침착성 레벨
     * @param array $context 컨텍스트
     * @return string 개입 전략
     */
    private function determineOptimalIntervention(string $calmnessLevel, array $context): string {
        // 추천 개입이 있으면 사용
        if (isset($context['recommended_intervention']['primary'])) {
            return $context['recommended_intervention']['primary'];
        }

        // 레벨별 기본 개입
        $interventionMap = [
            'C95' => 'MindfulnessSupport',
            'C90' => 'FocusGuidance',
            'C85' => 'InformationProvision',
            'C80' => 'CalmnessCoaching',
            'C75' => 'EmotionalSupport',
            'C_crisis' => 'CrisisIntervention'
        ];

        return $interventionMap[$calmnessLevel] ?? 'InformationProvision';
    }

    /**
     * 템플릿 키 결정
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return string 템플릿 키
     */
    private function determineTemplateKey(array $identification, array $context): string {
        $calmnessLevel = $identification['calmness_level'] ?? 'C85';
        $intent = $context['detected_intent'] ?? null;
        $emotion = $context['detected_emotion'] ?? null;

        // 특수 상황 처리
        if ($context['needs_immediate_support'] ?? false) {
            return 'immediate_support';
        }

        if ($context['message_analysis']['breathing_suggestion'] ?? false) {
            return 'breathing_exercise';
        }

        if ($context['message_analysis']['grounding_needed'] ?? false) {
            return 'grounding_exercise';
        }

        // 레벨별 기본 템플릿
        $templateMap = [
            'C95' => 'optimal_state',
            'C90' => 'good_state',
            'C85' => 'moderate_state',
            'C80' => 'mild_anxiety_support',
            'C75' => 'moderate_anxiety_support',
            'C_crisis' => 'crisis_support'
        ];

        return $templateMap[$calmnessLevel] ?? 'default';
    }

    /**
     * 필요한 운동 결정
     *
     * @param array $context 컨텍스트
     * @return array 추천 운동
     */
    private function determineExercises(array $context): array {
        $exercises = [];

        // 호흡 운동 필요 여부
        if ($this->config['breathing_exercise_enabled'] &&
            ($context['message_analysis']['breathing_suggestion'] ?? false)) {
            $exercises[] = [
                'type' => 'breathing',
                'name' => '4-7-8 호흡법',
                'description' => '4초 들이쉬고, 7초 멈추고, 8초 내쉬기'
            ];
        }

        // 그라운딩 운동 필요 여부
        if ($this->config['grounding_exercise_enabled'] &&
            ($context['message_analysis']['grounding_needed'] ?? false)) {
            $exercises[] = [
                'type' => 'grounding',
                'name' => '5-4-3-2-1 기법',
                'description' => '5가지 보이는 것, 4가지 만질 수 있는 것, 3가지 들리는 것...'
            ];
        }

        // 높은 불안 시 추가 운동
        if (($context['calmness_level'] ?? 'C85') === 'C_crisis' ||
            ($context['calmness_level'] ?? 'C85') === 'C75') {
            $exercises[] = [
                'type' => 'progressive_relaxation',
                'name' => '점진적 근육 이완',
                'description' => '손부터 시작해서 전신 근육을 긴장했다 이완하기'
            ];
        }

        return $exercises;
    }

    /**
     * 위기 상태 확인
     *
     * @param array $context 컨텍스트
     * @return bool 위기 상태 여부
     */
    private function isInCrisis(array $context): bool {
        $score = $context['current_calmness_score'] ?? 85;
        return $score < $this->config['crisis_alert_threshold'];
    }

    /**
     * 위기 알림 처리
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트
     * @param array $identification 식별 결과
     */
    private function handleCrisisAlert(int $userId, array $context, array $identification): void {
        // 다른 에이전트에게 알림
        if ($this->communicator) {
            try {
                $this->communicator->broadcastEvent('crisis_detected', [
                    'user_id' => $userId,
                    'calmness_score' => $context['current_calmness_score'],
                    'emotion' => $context['detected_emotion'],
                    'stress_level' => $context['stress_level'],
                    'timestamp' => time()
                ]);
            } catch (Exception $e) {
                $this->logWarning("위기 알림 전송 실패: " . $e->getMessage(), __LINE__);
            }
        }

        // 위기 로그 기록
        $this->logCrisis($userId, $context);
    }

    /**
     * 침착성 레벨별 페르소나 이름
     *
     * @param string $level 침착성 레벨
     * @return string 페르소나 이름
     */
    private function getCalmnessPersonaName(string $level): string {
        $names = [
            'C95' => '안정적 집중 상태',
            'C90' => '양호한 학습 상태',
            'C85' => '일반적 상태',
            'C80' => '경미한 불안 징후',
            'C75' => '중간 수준 불안',
            'C_crisis' => '즉각적 지원 필요'
        ];

        return $names[$level] ?? '관찰 중';
    }

    /**
     * 침착성 레벨 설명
     *
     * @param string $level 침착성 레벨
     * @return string 설명
     */
    private function getCalmnessLevelDescription(string $level): string {
        $descriptions = [
            'C95' => '최적의 침착 상태로 학습에 집중할 수 있습니다',
            'C90' => '양호한 침착 상태입니다',
            'C85' => '적정 수준의 침착성을 유지하고 있습니다',
            'C80' => '약간의 불안감이 감지됩니다',
            'C75' => '불안감이 높아지고 있습니다',
            'C_crisis' => '긴장 완화가 필요한 상태입니다'
        ];

        return $descriptions[$level] ?? '상태 확인 중';
    }

    /**
     * 신뢰도 계산
     *
     * @param array $context 컨텍스트
     * @return float 신뢰도
     */
    private function calculateConfidence(array $context): float {
        $confidence = 0.5;

        // 이력 데이터가 있으면 신뢰도 증가
        if (!empty($context['calmness_history'])) {
            $confidence += 0.1;
        }

        // NLU 분석 결과가 있으면 신뢰도 증가
        if (!empty($context['nlu_analysis'])) {
            $confidence += 0.15;
        }

        // 감정 강도가 명확하면 신뢰도 증가
        if (($context['emotion_intensity'] ?? 0) > 0.7) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }

    /**
     * 프로세스 로깅
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트
     * @param array $identification 식별 결과
     * @param float $processingTime 처리 시간 (ms)
     */
    private function logProcess(int $userId, array $context, array $identification, float $processingTime): void {
        if (!$this->config['log_enabled']) {
            return;
        }

        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->session_id = $context['session_id'] ?? null;
            $record->request_type = 'calmness_process';
            $record->input_data = json_encode([
                'message_length' => mb_strlen($context['user_message'] ?? ''),
                'calmness_score' => $context['current_calmness_score']
            ]);
            $record->persona_identified = $identification['calmness_level'];
            $record->confidence = $identification['confidence'];
            $record->processing_time_ms = (int) $processingTime;
            $record->output_data = json_encode([
                'tone' => $identification['tone'],
                'intervention' => $identification['intervention']
            ]);
            $record->success = 1;
            $record->created_at = date('Y-m-d H:i:s');

            if ($DB->get_manager()->table_exists('at_persona_log')) {
                $DB->insert_record('at_persona_log', $record);
            }

        } catch (Exception $e) {
            $this->logWarning("프로세스 로깅 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 위기 로그 기록
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트
     */
    private function logCrisis(int $userId, array $context): void {
        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->event_type = 'crisis_detected';
            $record->event_data = json_encode([
                'calmness_score' => $context['current_calmness_score'],
                'emotion' => $context['detected_emotion'],
                'stress_level' => $context['stress_level']
            ]);
            $record->created_at = time();

            if ($DB->get_manager()->table_exists('at_persona_events')) {
                $DB->insert_record('at_persona_events', $record);
            }

        } catch (Exception $e) {
            $this->logWarning("위기 로깅 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    private function logError(string $message, int $line): void {
        error_log("[CalmnessPersonaRuleEngine ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 경고 로깅
     *
     * @param string $message 경고 메시지
     * @param int $line 라인 번호
     */
    private function logWarning(string $message, int $line): void {
        if ($this->config['debug_mode']) {
            error_log("[CalmnessPersonaRuleEngine WARNING] {$this->currentFile}:{$line} - {$message}");
        }
    }

    /**
     * NLU 분석 결과만 반환 (테스트용)
     *
     * @param string $message 메시지
     * @return array NLU 분석 결과
     */
    public function analyzeNLU(string $message): array {
        return $this->nluAnalyzer->analyze($message);
    }

    /**
     * 디버그 정보
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        return [
            'agent_id' => $this->agentId,
            'rules_loaded' => count($this->rules['calmness_rules'] ?? []),
            'config' => $this->config,
            'components' => [
                'data_context' => isset($this->dataContext) ? 'initialized' : 'not_initialized',
                'response_generator' => isset($this->responseGenerator) ? 'initialized' : 'not_initialized',
                'nlu_analyzer' => $this->config['nlu_enabled'] ? 'enabled' : 'disabled',
                'communicator' => isset($this->communicator) ? 'initialized' : 'not_initialized'
            ],
            'version' => '1.0'
        ];
    }
}

/*
 * CalmnessPersonaRuleEngine v1.0 - Agent08 침착성 특화 엔진
 *
 * 주요 기능:
 * - 침착성 점수 기반 페르소나 식별
 * - 불안/스트레스 감지 및 분석
 * - 호흡 운동, 그라운딩 기법 추천
 * - 위기 상태 감지 및 알림
 * - 에이전트 간 위기 상황 공유
 *
 * 침착성 레벨:
 * - C95 (95-100): 최적의 침착 상태
 * - C90 (90-94): 양호한 침착 상태
 * - C85 (85-89): 적정 침착 상태
 * - C80 (80-84): 경미한 불안
 * - C75 (75-79): 중간 불안
 * - C_crisis (<75): 높은 불안/위기
 *
 * 관련 DB 테이블:
 * - at_calmness_scores: 침착성 점수 이력
 * - at_persona_log: 처리 로그
 * - at_persona_events: 이벤트 로그
 * - at_agent_messages: 에이전트 간 메시지
 *
 * 사용 예시:
 * $engine = new CalmnessPersonaRuleEngine(['debug_mode' => true]);
 * $engine->loadRules(__DIR__ . '/../rules/rules.yaml');
 * $result = $engine->process($userId, '시험이 너무 긴장돼요');
 * echo $result['response']['text'];
 */
