<?php
/**
 * PersonaRuleEngine - Agent15 문제 재정의 페르소나 엔진
 *
 * 학생의 문제 인식 및 재정의 상황에서 페르소나를 식별하고
 * 맞춤형 개선방안을 생성하는 핵심 엔진
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 *
 * 관련 DB 테이블:
 * - augmented_teacher_personas: 페르소나 이력 저장
 * - augmented_teacher_sessions: 세션 데이터
 * - at_agent_persona_state: 에이전트 간 페르소나 상태 공유
 * - at_agent_messages: 에이전트 간 메시지 교환
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 컴포넌트 로드
require_once(__DIR__ . '/RuleParser.php');
require_once(__DIR__ . '/ConditionEvaluator.php');
require_once(__DIR__ . '/ActionExecutor.php');
require_once(__DIR__ . '/DataContext.php');
require_once(__DIR__ . '/ResponseGenerator.php');
require_once(__DIR__ . '/RuleCache.php');
require_once(__DIR__ . '/NLUAnalyzer.php');
require_once(__DIR__ . '/PersonaTransitionManager.php');

class PersonaRuleEngine {

    /** @var int 에이전트 번호 */
    const AGENT_NUMBER = 15;

    /** @var string 에이전트 도메인 */
    const AGENT_DOMAIN = 'problem_redefinition';

    /** @var array 트리거 시나리오 목록 (S1-S10) */
    const TRIGGER_SCENARIOS = [
        'S1' => '학습 성과 하락 탐지',
        'S2' => '학습이탈 경고 감지',
        'S3' => '동일 오답 반복',
        'S4' => '루틴 불안정',
        'S5' => '시간관리 실패',
        'S6' => '정서/동기 저하',
        'S7' => '개념 이해 부진',
        'S8' => '교사 피드백 경고',
        'S9' => '전략 불일치',
        'S10' => '회복 실패'
    ];

    /** @var array 원인 분석 레이어 */
    const CAUSE_LAYERS = [
        'cognitive' => '인지적',
        'behavioral' => '행동적',
        'motivational' => '동기적',
        'environmental' => '환경적'
    ];

    /** @var RuleParser */
    private $ruleParser;

    /** @var ConditionEvaluator */
    private $conditionEvaluator;

    /** @var ActionExecutor */
    private $actionExecutor;

    /** @var DataContext */
    private $dataContext;

    /** @var ResponseGenerator */
    private $responseGenerator;

    /** @var RuleCache */
    private $ruleCache;

    /** @var NLUAnalyzer */
    private $nluAnalyzer;

    /** @var PersonaTransitionManager */
    private $transitionManager;

    /** @var array 로드된 규칙 */
    private $rules = [];

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var array AI 설정 */
    private $aiConfig = [];

    /**
     * 생성자
     *
     * @param bool $debugMode 디버그 모드 활성화
     */
    public function __construct($debugMode = false) {
        $this->debugMode = $debugMode;

        // AI 설정 로드
        $this->loadAIConfig();

        // 컴포넌트 초기화
        $this->ruleParser = new RuleParser();
        $this->conditionEvaluator = new ConditionEvaluator();
        $this->actionExecutor = new ActionExecutor($this->aiConfig);
        $this->dataContext = new DataContext();
        $this->responseGenerator = new ResponseGenerator($this->aiConfig);
        $this->ruleCache = new RuleCache();
        $this->nluAnalyzer = new NLUAnalyzer($this->aiConfig);
        $this->transitionManager = new PersonaTransitionManager();

        $this->log("PersonaRuleEngine initialized for Agent15 (Problem Redefinition)");
    }

    /**
     * AI 설정 로드
     */
    private function loadAIConfig() {
        $configPath = __DIR__ . '/config/ai_config.php';
        if (file_exists($configPath)) {
            $this->aiConfig = include($configPath);
        } else {
            $this->aiConfig = [
                'openai_api_key' => '',
                'models' => [
                    'nlu' => 'gpt-4-1106-preview',
                    'reasoning' => 'gpt-4-1106-preview',
                    'chat' => 'gpt-4o-mini'
                ],
                'daily_token_limit' => 100000,
                'cache_enabled' => true,
                'debug_mode' => false
            ];
            $this->log("Warning: AI config not found, using defaults", 'warning');
        }
    }

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath 규칙 파일 경로
     * @return bool 성공 여부
     */
    public function loadRules($rulesPath) {
        try {
            // 캐시 확인
            $cachedRules = $this->ruleCache->get($rulesPath);
            if ($cachedRules !== null) {
                $this->rules = $cachedRules;
                $this->log("Rules loaded from cache: $rulesPath");
                return true;
            }

            // 파일에서 로드
            $this->rules = $this->ruleParser->parseFile($rulesPath);

            // 캐시에 저장
            $this->ruleCache->set($rulesPath, $this->rules);

            $this->log("Rules loaded from file: $rulesPath, " . count($this->rules) . " rules");
            return true;

        } catch (Exception $e) {
            $this->log("Failed to load rules: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 학생 컨텍스트 로드
     *
     * @param int $userId 사용자 ID
     * @return array 컨텍스트 데이터
     */
    public function loadStudentContext($userId) {
        return $this->dataContext->loadStudentData($userId);
    }

    /**
     * 메인 처리 함수 - 문제 재정의 프로세스 실행
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process($userId, $message, $sessionData = []) {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'persona_id' => null,
            'persona_name' => null,
            'trigger_scenario' => null,
            'redefined_problem' => null,
            'cause_analysis' => [],
            'action_plan' => [],
            'priority_items' => [],
            'response' => null,
            'debug' => []
        ];

        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->loadStudentContext($userId);
            $context['user_message'] = $message;
            $context['session_data'] = $sessionData;

            // 2. 트리거 시나리오 감지
            $triggerScenario = $this->detectTriggerScenario($context);
            $result['trigger_scenario'] = $triggerScenario;
            $this->log("Detected trigger scenario: $triggerScenario");

            // 3. NLU 분석
            $nluResult = $this->nluAnalyzer->analyze($message, $context);
            $context['nlu_result'] = $nluResult;

            // 4. 페르소나 식별
            $persona = $this->identifyPersona($context, $triggerScenario);
            $result['persona_id'] = $persona['id'];
            $result['persona_name'] = $persona['name'];
            $this->log("Identified persona: {$persona['id']} - {$persona['name']}");

            // 5. 다층 원인 분석
            $causeAnalysis = $this->analyzeCauses($context, $triggerScenario);
            $result['cause_analysis'] = $causeAnalysis;

            // 6. 문제 재정의
            $redefinedProblem = $this->redefineProblem($context, $causeAnalysis);
            $result['redefined_problem'] = $redefinedProblem;

            // 7. 조치안 생성
            $actionPlan = $this->generateActionPlan($context, $persona, $causeAnalysis);
            $result['action_plan'] = $actionPlan;
            $result['priority_items'] = $this->prioritizeActions($actionPlan);

            // 8. 응답 생성
            $response = $this->responseGenerator->generate(
                $persona,
                $triggerScenario,
                $context,
                $actionPlan
            );
            $result['response'] = $response;

            // 9. 페르소나 상태 저장
            $this->savePersonaState($userId, $persona, $triggerScenario);

            // 10. 페르소나 전환 관리
            $this->transitionManager->recordTransition($userId, $persona['id'], $context);

            $result['success'] = true;

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['error_location'] = __FILE__ . ':' . $e->getLine();
            $this->log("Process error: " . $e->getMessage(), 'error');
        }

        // 처리 시간 기록
        $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

        if ($this->debugMode) {
            $result['debug'] = [
                'context' => $context ?? null,
                'nlu_result' => $nluResult ?? null,
                'rules_count' => count($this->rules)
            ];
        }

        return $result;
    }

    /**
     * 트리거 시나리오 감지
     *
     * @param array $context 컨텍스트 데이터
     * @return string 트리거 시나리오 코드
     */
    private function detectTriggerScenario($context) {
        // 세션에서 명시적 트리거가 있는 경우
        if (!empty($context['session_data']['trigger_scenario'])) {
            return $context['session_data']['trigger_scenario'];
        }

        // 에이전트 데이터에서 트리거 감지
        $agentData = $context['agent_data'] ?? [];

        // S1: 학습 성과 하락 탐지
        if ($this->checkPerformanceDecline($agentData)) {
            return 'S1';
        }

        // S2: 학습이탈 경고 감지
        if ($this->checkDropoutWarning($agentData)) {
            return 'S2';
        }

        // S3: 동일 오답 반복
        if ($this->checkRepeatedErrors($agentData)) {
            return 'S3';
        }

        // S4: 루틴 불안정
        if ($this->checkRoutineInstability($agentData)) {
            return 'S4';
        }

        // S5: 시간관리 실패
        if ($this->checkTimeManagementFailure($agentData)) {
            return 'S5';
        }

        // S6: 정서/동기 저하
        if ($this->checkEmotionalDecline($agentData)) {
            return 'S6';
        }

        // S7: 개념 이해 부진
        if ($this->checkConceptUnderstanding($agentData)) {
            return 'S7';
        }

        // S8: 교사 피드백 경고
        if ($this->checkTeacherFeedback($agentData)) {
            return 'S8';
        }

        // S9: 전략 불일치
        if ($this->checkStrategyMismatch($agentData)) {
            return 'S9';
        }

        // S10: 회복 실패
        if ($this->checkRecoveryFailure($agentData)) {
            return 'S10';
        }

        // 기본값: S1 (학습 성과 하락)
        return 'S1';
    }

    /**
     * 페르소나 식별
     *
     * @param array $context 컨텍스트 데이터
     * @param string $triggerScenario 트리거 시나리오
     * @return array 식별된 페르소나
     */
    private function identifyPersona($context, $triggerScenario) {
        $matchedPersonas = [];

        foreach ($this->rules as $rule) {
            if (!isset($rule['conditions'])) {
                continue;
            }

            // 조건 평가
            $score = $this->conditionEvaluator->evaluate($rule['conditions'], $context);

            if ($score > 0) {
                $matchedPersonas[] = [
                    'rule' => $rule,
                    'score' => $score
                ];
            }
        }

        // 점수로 정렬
        usort($matchedPersonas, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // 최고 점수 페르소나 반환
        if (!empty($matchedPersonas)) {
            $bestMatch = $matchedPersonas[0]['rule'];
            return [
                'id' => $bestMatch['persona_id'] ?? 'R1_P1',
                'name' => $bestMatch['persona_name'] ?? '기본 문제 인식자',
                'category' => $bestMatch['category'] ?? 'R',
                'characteristics' => $bestMatch['characteristics'] ?? [],
                'score' => $matchedPersonas[0]['score']
            ];
        }

        // 기본 페르소나
        return [
            'id' => 'R1_P1',
            'name' => '기본 문제 인식자',
            'category' => 'R',
            'characteristics' => ['neutral', 'analytical'],
            'score' => 0.5
        ];
    }

    /**
     * 다층 원인 분석
     *
     * @param array $context 컨텍스트 데이터
     * @param string $triggerScenario 트리거 시나리오
     * @return array 원인 분석 결과
     */
    private function analyzeCauses($context, $triggerScenario) {
        $causes = [];

        foreach (self::CAUSE_LAYERS as $layer => $label) {
            $causes[$layer] = [
                'label' => $label,
                'factors' => $this->analyzeLayerFactors($context, $layer, $triggerScenario),
                'confidence' => 0.0
            ];

            // 신뢰도 계산
            if (!empty($causes[$layer]['factors'])) {
                $causes[$layer]['confidence'] = $this->calculateConfidence($causes[$layer]['factors']);
            }
        }

        return $causes;
    }

    /**
     * 레이어별 요인 분석
     */
    private function analyzeLayerFactors($context, $layer, $triggerScenario) {
        $factors = [];
        $agentData = $context['agent_data'] ?? [];

        switch ($layer) {
            case 'cognitive':
                // 인지적 요인 분석
                if (!empty($agentData['concept_scores'])) {
                    foreach ($agentData['concept_scores'] as $concept => $score) {
                        if ($score < 60) {
                            $factors[] = [
                                'type' => 'concept_gap',
                                'description' => "{$concept} 개념 이해 부족",
                                'severity' => (60 - $score) / 60
                            ];
                        }
                    }
                }
                break;

            case 'behavioral':
                // 행동적 요인 분석
                if (!empty($agentData['study_patterns'])) {
                    $patterns = $agentData['study_patterns'];
                    if (isset($patterns['pomodoro_completion']) && $patterns['pomodoro_completion'] < 50) {
                        $factors[] = [
                            'type' => 'routine_issue',
                            'description' => '학습 루틴 불안정',
                            'severity' => (50 - $patterns['pomodoro_completion']) / 50
                        ];
                    }
                }
                break;

            case 'motivational':
                // 동기적 요인 분석
                if (!empty($agentData['emotion_logs'])) {
                    $negativeCount = 0;
                    foreach ($agentData['emotion_logs'] as $log) {
                        if (in_array($log['emotion'], ['frustration', 'boredom', 'anxiety'])) {
                            $negativeCount++;
                        }
                    }
                    if ($negativeCount > 2) {
                        $factors[] = [
                            'type' => 'motivation_decline',
                            'description' => '학습 동기 저하 징후',
                            'severity' => min($negativeCount / 5, 1.0)
                        ];
                    }
                }
                break;

            case 'environmental':
                // 환경적 요인 분석
                if (!empty($agentData['environment'])) {
                    $env = $agentData['environment'];
                    if (isset($env['noise_level']) && $env['noise_level'] > 60) {
                        $factors[] = [
                            'type' => 'environment_issue',
                            'description' => '학습 환경 산만함',
                            'severity' => ($env['noise_level'] - 60) / 40
                        ];
                    }
                }
                break;
        }

        return $factors;
    }

    /**
     * 문제 재정의
     *
     * @param array $context 컨텍스트 데이터
     * @param array $causeAnalysis 원인 분석 결과
     * @return string 재정의된 문제 설명
     */
    private function redefineProblem($context, $causeAnalysis) {
        // 주요 원인 추출
        $primaryCauses = [];
        foreach ($causeAnalysis as $layer => $data) {
            if ($data['confidence'] > 0.5 && !empty($data['factors'])) {
                $primaryCauses[$layer] = $data;
            }
        }

        // 재정의 문장 생성
        if (empty($primaryCauses)) {
            return "현재 학습 상황에서 명확한 문제 원인이 식별되지 않았습니다. 추가 데이터 수집이 필요합니다.";
        }

        $parts = [];
        foreach ($primaryCauses as $layer => $data) {
            $label = $data['label'];
            $topFactor = $data['factors'][0] ?? null;
            if ($topFactor) {
                $parts[] = "{$label} 측면에서 {$topFactor['description']}이(가) 주요 요인으로 분석됩니다";
            }
        }

        return implode('. ', $parts) . '.';
    }

    /**
     * 조치안 생성
     *
     * @param array $context 컨텍스트 데이터
     * @param array $persona 식별된 페르소나
     * @param array $causeAnalysis 원인 분석 결과
     * @return array 조치안 목록
     */
    private function generateActionPlan($context, $persona, $causeAnalysis) {
        $actions = [];

        foreach ($causeAnalysis as $layer => $data) {
            if (empty($data['factors'])) {
                continue;
            }

            foreach ($data['factors'] as $factor) {
                $action = $this->actionExecutor->generateAction(
                    $factor,
                    $persona,
                    $context
                );

                if ($action) {
                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }

    /**
     * 조치안 우선순위 지정
     *
     * @param array $actionPlan 조치안 목록
     * @return array 우선순위 인덱스 배열
     */
    private function prioritizeActions($actionPlan) {
        if (empty($actionPlan)) {
            return [];
        }

        // 긴급도와 영향도 기준 정렬
        $indexed = [];
        foreach ($actionPlan as $idx => $action) {
            $urgency = $action['urgency'] ?? 0.5;
            $impact = $action['impact'] ?? 0.5;
            $score = ($urgency * 0.6) + ($impact * 0.4);
            $indexed[] = ['index' => $idx, 'score' => $score];
        }

        usort($indexed, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 상위 3개 반환
        return array_slice(array_column($indexed, 'index'), 0, 3);
    }

    /**
     * 페르소나 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param array $persona 페르소나 데이터
     * @param string $triggerScenario 트리거 시나리오
     */
    private function savePersonaState($userId, $persona, $triggerScenario) {
        global $DB;

        try {
            $record = new stdClass();
            $record->userid = $userId;
            $record->nagent = self::AGENT_NUMBER;
            $record->persona_id = $persona['id'];
            $record->persona_name = $persona['name'];
            $record->trigger_scenario = $triggerScenario;
            $record->context_data = json_encode([
                'category' => $persona['category'],
                'characteristics' => $persona['characteristics'],
                'score' => $persona['score']
            ]);
            $record->timecreated = time();
            $record->timemodified = time();

            // at_agent_persona_state 테이블에 저장
            $existing = $DB->get_record('at_agent_persona_state', [
                'userid' => $userId,
                'nagent' => self::AGENT_NUMBER
            ]);

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('at_agent_persona_state', $record);
            } else {
                $DB->insert_record('at_agent_persona_state', $record);
            }

            $this->log("Persona state saved for user $userId");

        } catch (Exception $e) {
            $this->log("Failed to save persona state: " . $e->getMessage(), 'error');
        }
    }

    // === 트리거 감지 헬퍼 메서드 ===

    private function checkPerformanceDecline($data) {
        if (empty($data['performance'])) return false;
        $perf = $data['performance'];
        return isset($perf['score_trend']) && $perf['score_trend'] < -10;
    }

    private function checkDropoutWarning($data) {
        if (empty($data['dropout_events'])) return false;
        return count($data['dropout_events']) >= 2;
    }

    private function checkRepeatedErrors($data) {
        if (empty($data['error_patterns'])) return false;
        foreach ($data['error_patterns'] as $pattern) {
            if ($pattern['count'] >= 3) return true;
        }
        return false;
    }

    private function checkRoutineInstability($data) {
        if (empty($data['study_patterns'])) return false;
        return isset($data['study_patterns']['pomodoro_completion'])
            && $data['study_patterns']['pomodoro_completion'] < 50;
    }

    private function checkTimeManagementFailure($data) {
        if (empty($data['time_management'])) return false;
        $tm = $data['time_management'];
        return isset($tm['plan_vs_actual_diff']) && $tm['plan_vs_actual_diff'] > 30;
    }

    private function checkEmotionalDecline($data) {
        if (empty($data['emotion_logs'])) return false;
        $negative = 0;
        foreach ($data['emotion_logs'] as $log) {
            if (in_array($log['emotion'] ?? '', ['frustration', 'boredom', 'anxiety', 'hopelessness'])) {
                $negative++;
            }
        }
        return $negative >= 3;
    }

    private function checkConceptUnderstanding($data) {
        if (empty($data['concept_test_scores'])) return false;
        foreach ($data['concept_test_scores'] as $score) {
            if ($score < 60) return true;
        }
        return false;
    }

    private function checkTeacherFeedback($data) {
        if (empty($data['teacher_feedback'])) return false;
        foreach ($data['teacher_feedback'] as $feedback) {
            if (in_array($feedback['type'] ?? '', ['concentration_low', 'basics_weak'])) {
                return true;
            }
        }
        return false;
    }

    private function checkStrategyMismatch($data) {
        if (empty($data['strategy_data'])) return false;
        $strategy = $data['strategy_data'];
        return isset($strategy['mode_vs_behavior_match'])
            && $strategy['mode_vs_behavior_match'] < 50;
    }

    private function checkRecoveryFailure($data) {
        if (empty($data['recovery_data'])) return false;
        $recovery = $data['recovery_data'];
        return isset($recovery['post_break_focus'])
            && $recovery['post_break_focus'] < 50;
    }

    /**
     * 신뢰도 계산
     */
    private function calculateConfidence($factors) {
        if (empty($factors)) return 0.0;

        $totalSeverity = 0;
        foreach ($factors as $factor) {
            $totalSeverity += $factor['severity'] ?? 0.5;
        }

        return min($totalSeverity / count($factors), 1.0);
    }

    /**
     * 로그 기록
     *
     * @param string $message 로그 메시지
     * @param string $level 로그 레벨 (info, warning, error)
     */
    private function log($message, $level = 'info') {
        if (!$this->debugMode && $level === 'info') {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [Agent15] [$level] $message\n";

        if ($this->debugMode) {
            error_log($logEntry);
        }
    }

    /**
     * 현재 에이전트 정보 반환
     *
     * @return array 에이전트 정보
     */
    public function getAgentInfo() {
        return [
            'agent_number' => self::AGENT_NUMBER,
            'domain' => self::AGENT_DOMAIN,
            'trigger_scenarios' => self::TRIGGER_SCENARIOS,
            'cause_layers' => self::CAUSE_LAYERS,
            'version' => '1.0',
            'created' => '2025-12-02'
        ];
    }
}
