<?php
/**
 * PersonaRuleEngine - 페르소나 규칙 실행 엔진
 *
 * 학생의 발화와 행동 패턴을 분석하여 페르소나를 식별하고
 * 맞춤형 대응 전략을 생성합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 * @author AI Agent
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/RuleParser.php');
require_once(__DIR__ . '/ConditionEvaluator.php');
require_once(__DIR__ . '/ActionExecutor.php');
require_once(__DIR__ . '/DataContext.php');
require_once(__DIR__ . '/RuleCache.php');
require_once(__DIR__ . '/NLUAnalyzer.php');
require_once(__DIR__ . '/PersonaTransitionManager.php');
require_once(__DIR__ . '/ResponseGenerator.php');

class PersonaRuleEngine {

    /** @var array 로드된 규칙 */
    private $rules = [];

    /** @var RuleParser 규칙 파서 */
    private $parser;

    /** @var ConditionEvaluator 조건 평가기 */
    private $evaluator;

    /** @var ActionExecutor 액션 실행기 */
    private $executor;

    /** @var DataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var RuleCache 규칙 캐시 */
    private $cache;

    /** @var NLUAnalyzer NLU 분석기 */
    private $nluAnalyzer;

    /** @var PersonaTransitionManager 전환 관리자 */
    private $transitionManager;

    /** @var ResponseGenerator 응답 생성기 */
    private $responseGenerator;

    /** @var string 현재 파일 경로 (디버깅용) */
    private $currentFile = __FILE__;

    /** @var array 설정 */
    private $config = [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'debug_mode' => false,
        'log_enabled' => true,
        'nlu_enabled' => true,
        'transition_enabled' => true
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 옵션
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);

        // 핵심 컴포넌트 초기화
        $this->parser = new RuleParser();
        $this->evaluator = new ConditionEvaluator();
        $this->executor = new ActionExecutor();
        $this->dataContext = new DataContext();
        $this->cache = new RuleCache($this->config['cache_ttl']);

        // NLU 및 고급 컴포넌트 초기화
        $this->nluAnalyzer = new NLUAnalyzer();
        $this->transitionManager = new PersonaTransitionManager();
        $this->responseGenerator = new ResponseGenerator();
    }

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath rules.yaml 파일 경로
     * @return bool 로드 성공 여부
     * @throws Exception 파일 로드 실패 시
     */
    public function loadRules(string $rulesPath): bool {
        try {
            // 캐시 확인
            if ($this->config['cache_enabled']) {
                $cached = $this->cache->get($rulesPath);
                if ($cached !== null) {
                    $this->rules = $cached;
                    return true;
                }
            }

            // 규칙 파싱
            $this->rules = $this->parser->parseRules($rulesPath);

            // 우선순위 정렬
            if (isset($this->rules['persona_identification_rules'])) {
                $this->rules['persona_identification_rules'] =
                    $this->parser->sortByPriority($this->rules['persona_identification_rules']);
            }

            // 캐시 저장
            if ($this->config['cache_enabled']) {
                $this->cache->set($rulesPath, $this->rules);
            }

            return true;

        } catch (Exception $e) {
            $this->logError("규칙 로드 실패: " . $e->getMessage(), __LINE__);
            throw $e;
        }
    }

    /**
     * 학생 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 현재 세션 데이터
     * @return array 학생 컨텍스트
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array {
        return $this->dataContext->loadByUserId($userId, $sessionData);
    }

    /**
     * 메시지 분석 및 컨텍스트 업데이트 (NLU 통합)
     *
     * @param array $context 현재 컨텍스트
     * @param string $message 사용자 메시지
     * @return array 업데이트된 컨텍스트
     */
    public function analyzeMessage(array $context, string $message): array {
        // 기본 분석 (DataContext)
        $basicAnalysis = $this->dataContext->analyzeMessage($message);

        // NLU 심층 분석
        $nluResult = [];
        if ($this->config['nlu_enabled']) {
            $nluResult = $this->nluAnalyzer->analyze($message);
        }

        // 컨텍스트 병합 (우선순위: NLU > 기본 > 기존)
        $mergedContext = array_merge(
            $context,
            $basicAnalysis,
            [
                'user_message' => $message,
                'nlu_analysis' => $nluResult,
                'detected_intent' => $nluResult['intent']['type'] ?? null,
                'detected_emotion' => $nluResult['emotion']['primary'] ?? null,
                'emotion_intensity' => $nluResult['emotion']['intensity'] ?? 0,
                'detected_topics' => $nluResult['topics'] ?? []
            ]
        );

        // 전환 트리거 감지
        if ($this->config['transition_enabled'] && isset($context['current_persona'])) {
            $transitionTrigger = $this->transitionManager->detectMessageTrigger($message, $mergedContext);
            if ($transitionTrigger) {
                $mergedContext['transition_trigger'] = $transitionTrigger;
            }
        }

        return $mergedContext;
    }

    /**
     * 전체 프로세스 실행 (분석 → 식별 → 응답 생성)
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과 (페르소나 + 응답)
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->loadStudentContext($userId, $sessionData);

            // 2. 메시지 분석 (NLU 포함)
            $context = $this->analyzeMessage($context, $message);

            // 3. 페르소나 식별
            $identification = $this->identifyPersona($context);

            // 4. 전환 처리 (필요시)
            if ($this->config['transition_enabled'] && isset($context['transition_trigger'])) {
                $this->handleTransition($userId, $context, $identification);
            }

            // 5. 응답 생성
            $response = $this->generateResponse($identification, $context);

            return [
                'success' => true,
                'user_id' => $userId,
                'persona' => $identification,
                'response' => $response,
                'context' => [
                    'intent' => $context['detected_intent'],
                    'emotion' => $context['detected_emotion'],
                    'topics' => $context['detected_topics']
                ]
            ];

        } catch (Exception $e) {
            $this->logError("프로세스 실행 실패: " . $e->getMessage(), __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ];
        }
    }

    /**
     * 응답 생성
     *
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트
     * @param string $templateKey 템플릿 키 (옵션)
     * @return array 생성된 응답
     */
    public function generateResponse(array $identification, array $context, string $templateKey = 'default'): array {
        // 템플릿 키 자동 결정
        if ($templateKey === 'default') {
            $templateKey = $this->determineTemplateKey($identification, $context);
        }

        // 템플릿 변수 준비
        $variables = [
            'student_name' => $context['student_name'] ?? $context['firstname'] ?? '학생',
            'persona_name' => $identification['persona_name'] ?? '미식별',
            'situation' => $this->getSituationName($identification['persona_id'] ?? 'default'),
            'date' => date('Y년 m월 d일'),
            'time' => date('H:i')
        ];

        // 응답 옵션 설정
        $options = [
            'tone' => $identification['tone'] ?? 'Professional',
            'intervention' => $identification['intervention'] ?? 'InformationProvision'
        ];

        // 응답 생성
        $responseText = $this->responseGenerator->generateFromResult(
            $identification,
            $templateKey,
            array_merge($context, $variables)
        );

        return [
            'text' => $responseText,
            'template_key' => $templateKey,
            'tone' => $options['tone'],
            'intervention' => $options['intervention'],
            'persona_id' => $identification['persona_id'],
            'confidence' => $identification['confidence']
        ];
    }

    /**
     * 템플릿 키 자동 결정
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return string 템플릿 키
     */
    private function determineTemplateKey(array $identification, array $context): string {
        $personaId = $identification['persona_id'] ?? 'default';
        $intent = $context['detected_intent'] ?? null;
        $emotion = $context['detected_emotion'] ?? null;

        // 정서적 지원이 필요한 경우
        if ($emotion && in_array($emotion, ['anxiety', 'frustration', 'sadness', 'fear'])) {
            return 'emotional_support';
        }

        // 상황별 기본 템플릿
        $situation = substr($personaId, 0, 2);
        switch ($situation) {
            case 'S0':
                return 'assessment_intro';
            case 'S1':
                return 'registration_welcome';
            case 'S2':
                return 'learning_plan';
            case 'S3':
                return 'progress_check';
            case 'S4':
                return 'parent_consultation';
            case 'S5':
                return 'goal_setting';
            case 'C_':
                return 'complex_support';
            case 'Q_':
                return 'question_response';
            case 'E_':
                return 'emotional_support';
            default:
                return 'welcome';
        }
    }

    /**
     * 상황 이름 조회
     *
     * @param string $personaId 페르소나 ID
     * @return string 상황 이름
     */
    private function getSituationName(string $personaId): string {
        $situation = substr($personaId, 0, 2);
        $names = [
            'S0' => '수학 특화 정보 수집',
            'S1' => '신규 학생 등록',
            'S2' => '수업 전 학습 설계',
            'S3' => '진도 판단',
            'S4' => '학부모 상담',
            'S5' => '장기 목표 설정',
            'C_' => '복합 상황',
            'Q_' => '포괄형 질문',
            'E_' => '정서적 지원'
        ];
        return $names[$situation] ?? '일반 상담';
    }

    /**
     * 페르소나 전환 처리
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트
     * @param array $identification 식별 결과
     */
    private function handleTransition(int $userId, array $context, array $identification): void {
        $trigger = $context['transition_trigger'] ?? null;
        if (!$trigger) {
            return;
        }

        $fromPersona = $context['current_persona'] ?? 'default';
        $toPersona = $identification['persona_id'] ?? 'default';

        // 전환 가능 여부 확인
        $canTransition = $this->transitionManager->canTransition(
            $fromPersona,
            $toPersona,
            $identification['confidence'] ?? 0.5
        );

        if ($canTransition['allowed']) {
            // 전환 실행
            $this->transitionManager->executeTransition(
                $userId,
                $fromPersona,
                $toPersona,
                $trigger['type'] ?? 'unknown',
                $context
            );
        }
    }

    /**
     * NLU 분석 결과만 반환 (디버깅/테스트용)
     *
     * @param string $message 메시지
     * @return array NLU 분석 결과
     */
    public function analyzeNLU(string $message): array {
        return $this->nluAnalyzer->analyze($message);
    }

    /**
     * 전환 패턴 분석 (디버깅/통계용)
     *
     * @param int $userId 사용자 ID
     * @return array 전환 패턴 분석 결과
     */
    public function analyzeTransitionPatterns(int $userId): array {
        return $this->transitionManager->analyzeTransitionPatterns($userId);
    }

    /**
     * 페르소나 식별
     *
     * @param array $context 학생 컨텍스트
     * @return array 식별 결과
     */
    public function identifyPersona(array $context): array {
        $result = [
            'persona_id' => null,
            'persona_name' => null,
            'confidence' => 0,
            'matched_rule' => null,
            'tone' => 'Professional',
            'pace' => 'normal',
            'intervention' => null,
            'actions' => []
        ];

        if (empty($this->rules['persona_identification_rules'])) {
            $this->logWarning("페르소나 식별 규칙이 로드되지 않음", __LINE__);
            return $this->getDefaultResult();
        }

        // 우선순위 순서로 규칙 매칭
        foreach ($this->rules['persona_identification_rules'] as $rule) {
            if ($this->evaluateRule($rule, $context)) {
                $result = $this->applyRule($rule, $context);

                // 로깅
                $this->logPersonaMatch($context['user_id'] ?? 0, $result);

                return $result;
            }
        }

        // 매칭 규칙 없음 - 기본값 반환
        return $this->getDefaultResult();
    }

    /**
     * 규칙 평가
     *
     * @param array $rule 규칙
     * @param array $context 컨텍스트
     * @return bool 매칭 여부
     */
    private function evaluateRule(array $rule, array $context): bool {
        if (!isset($rule['conditions'])) {
            return false;
        }

        foreach ($rule['conditions'] as $condition) {
            // OR 조건
            if (isset($condition['OR'])) {
                if (!$this->evaluator->evaluateOr($condition['OR'], $context)) {
                    return false;
                }
            }
            // AND 조건
            elseif (isset($condition['AND'])) {
                if (!$this->evaluator->evaluateAnd($condition['AND'], $context)) {
                    return false;
                }
            }
            // 단일 조건
            else {
                if (!$this->evaluator->evaluate($condition, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 규칙 적용
     *
     * @param array $rule 매칭된 규칙
     * @param array $context 컨텍스트
     * @return array 적용 결과
     */
    private function applyRule(array $rule, array $context): array {
        $result = [
            'matched_rule' => $rule['rule_id'],
            'confidence' => $rule['confidence'] ?? 0.5,
            'actions' => []
        ];

        // 액션 실행
        if (isset($rule['action'])) {
            $result['actions'] = $this->executor->execute($rule['action'], $context);

            // 결과에서 주요 값 추출
            foreach ($result['actions'] as $action) {
                if (strpos($action, 'identify_persona:') === 0) {
                    $personaId = trim(str_replace(['identify_persona:', "'", '"'], '', $action));
                    $result['persona_id'] = $personaId;
                    $result['persona_name'] = $this->getPersonaName($personaId);
                }
                if (strpos($action, 'set_tone:') === 0) {
                    $result['tone'] = trim(str_replace(['set_tone:', "'", '"'], '', $action));
                }
                if (strpos($action, 'set_pace:') === 0) {
                    $result['pace'] = trim(str_replace(['set_pace:', "'", '"'], '', $action));
                }
                if (strpos($action, 'prioritize_intervention:') === 0) {
                    $result['intervention'] = trim(str_replace(['prioritize_intervention:', "'", '"'], '', $action));
                }
            }
        }

        return $result;
    }

    /**
     * 페르소나 이름 조회
     *
     * @param string $personaId 페르소나 ID
     * @return string 페르소나 이름
     */
    private function getPersonaName(string $personaId): string {
        // 페르소나 ID → 이름 매핑
        $names = [
            // S0: 수학 특화 정보 수집
            'S0_P1' => '솔직한 자기 분석가',
            'S0_P2' => '방어적 최소 응답자',
            'S0_P3' => '과대 포장형 자신감',
            'S0_P4' => '불안한 완벽주의자',
            'S0_P5' => '무관심한 수동적 참여자',
            'S0_P6' => '호기심 많은 탐색자',

            // S1: 신규 학생 등록
            'S1_P1' => '기대에 찬 새출발형',
            'S1_P2' => '과거 트라우마형 긴장자',
            'S1_P3' => '부모 눈치형 의무 참여자',
            'S1_P4' => '테스트 경계형',
            'S1_P5' => '목표 명확형 실용주의자',
            'S1_P6' => '사교적 관계 지향형',

            // S2: 수업 전 학습 설계
            'S2_P1' => '계획 수용형 따르는 학습자',
            'S2_P2' => '자기주도형 협상가',
            'S2_P3' => '과부하 회피형 최소주의자',
            'S2_P4' => '완벽주의 과다 계획형',
            'S2_P5' => '시험 중심 전략가',
            'S2_P6' => '유연한 적응형',

            // S3: 진도 판단
            'S3_P1' => '진도 불안형 조급자',
            'S3_P2' => '기초 회피형 점프러',
            'S3_P3' => '겸손한 과소평가형',
            'S3_P4' => '갭 인정 수용형',
            'S3_P5' => '방어적 합리화형',
            'S3_P6' => '분석적 이해 추구형',

            // S4: 학부모 상담
            'S4_P1' => '투명성 선호형 공개자',
            'S4_P2' => '프라이버시 수호형',
            'S4_P3' => '부모 눈치형 긴장자',
            'S4_P4' => '부모-자녀 갈등형 중재 요청자',
            'S4_P5' => '무관심형 단절자',
            'S4_P6' => '부모 기대 부응형 성취자',

            // S5: 장기 목표
            'S5_P1' => '야망찬 꿈꾸는 자',
            'S5_P2' => '현실적 계획가',
            'S5_P3' => '목표 미정형 탐색자',
            'S5_P4' => '외압형 목표 수용자',
            'S5_P5' => '목표-현실 괴리 인식자',
            'S5_P6' => '성장 마인드셋 보유자',

            // C: 복합 상황
            'C_P1' => '다중 어려움 압도형',
            'C_P2' => '저항적 복합 문제 보유자',
            'C_P3' => '적극적 해결 추구형',
            'C_P4' => '외부 귀인형 책임 회피자',
            'C_P5' => '무기력 학습 포기자',
            'C_P6' => '상황적 일시 저조형',

            // Q: 포괄형 질문
            'Q_P1' => '전체 그림 파악형',
            'Q_P2' => '세부 사항 집중형',
            'Q_P3' => '관계 연결형',
            'Q_P4' => '즉각 실행형',
            'Q_P5' => '비교 분석형',
            'Q_P6' => '피드백 수용형',

            // E: 정서적 UX
            'E_P1' => '수학 불안형 공포자',
            'E_P2' => '자신감 회복 중인 도전자',
            'E_P3' => '좌절 직전 위기형',
            'E_P4' => '안정적 균형형',
            'E_P5' => '흥미 기반 동기형',
            'E_P6' => '외적 인정 추구형'
        ];

        return $names[$personaId] ?? '미식별';
    }

    /**
     * 기본 결과 반환
     *
     * @return array 기본 결과
     */
    private function getDefaultResult(): array {
        return [
            'persona_id' => 'default',
            'persona_name' => '미식별 (관찰 중)',
            'confidence' => 0.5,
            'matched_rule' => 'default',
            'tone' => 'Professional',
            'pace' => 'normal',
            'intervention' => 'InformationProvision',
            'actions' => ['use_balanced_tone', 'maintain_supportive_environment', 'continue_persona_observation']
        ];
    }

    /**
     * 페르소나 매칭 로깅
     *
     * @param int $userId 사용자 ID
     * @param array $result 매칭 결과
     */
    private function logPersonaMatch(int $userId, array $result): void {
        if (!$this->config['log_enabled']) {
            return;
        }

        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = 'agent01';
            $record->persona_id = $result['persona_id'];
            $record->situation = substr($result['persona_id'], 0, 2);
            $record->confidence = $result['confidence'];
            $record->matched_rule = $result['matched_rule'];
            $record->created_at = date('Y-m-d H:i:s');

            // augmented_teacher_personas 테이블이 존재하면 로깅
            if ($DB->get_manager()->table_exists('augmented_teacher_personas')) {
                $DB->insert_record('augmented_teacher_personas', $record);
            }
        } catch (Exception $e) {
            // 로깅 실패는 무시 (핵심 기능에 영향 없음)
            $this->logWarning("페르소나 로깅 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    private function logError(string $message, int $line): void {
        error_log("[PersonaRuleEngine ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 경고 로깅
     *
     * @param string $message 경고 메시지
     * @param int $line 라인 번호
     */
    private function logWarning(string $message, int $line): void {
        if ($this->config['debug_mode']) {
            error_log("[PersonaRuleEngine WARNING] {$this->currentFile}:{$line} - {$message}");
        }
    }

    /**
     * 디버그 정보 출력
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        return [
            'rules_loaded' => count($this->rules['persona_identification_rules'] ?? []),
            'config' => $this->config,
            'cache_status' => $this->cache->getStatus(),
            'components' => [
                'nlu_analyzer' => $this->config['nlu_enabled'] ? 'enabled' : 'disabled',
                'transition_manager' => $this->config['transition_enabled'] ? 'enabled' : 'disabled',
                'response_generator' => 'enabled'
            ],
            'version' => '2.0'
        ];
    }

    /**
     * 컴포넌트 상태 확인
     *
     * @return array 컴포넌트 상태
     */
    public function getComponentStatus(): array {
        return [
            'parser' => isset($this->parser) ? 'initialized' : 'not_initialized',
            'evaluator' => isset($this->evaluator) ? 'initialized' : 'not_initialized',
            'executor' => isset($this->executor) ? 'initialized' : 'not_initialized',
            'dataContext' => isset($this->dataContext) ? 'initialized' : 'not_initialized',
            'cache' => isset($this->cache) ? 'initialized' : 'not_initialized',
            'nluAnalyzer' => isset($this->nluAnalyzer) ? 'initialized' : 'not_initialized',
            'transitionManager' => isset($this->transitionManager) ? 'initialized' : 'not_initialized',
            'responseGenerator' => isset($this->responseGenerator) ? 'initialized' : 'not_initialized'
        ];
    }
}

/*
 * PersonaRuleEngine v2.0 - NLU 통합 버전
 *
 * 주요 기능:
 * - YAML 기반 규칙 로드 및 파싱
 * - 조건 평가 (AND/OR 복합 조건 지원)
 * - NLU 기반 메시지 분석 (의도, 감정, 주제)
 * - 페르소나 식별 및 전환 관리
 * - 동적 응답 생성 (톤, 개입 전략 적용)
 *
 * 관련 DB 테이블:
 * - augmented_teacher_personas: user_id(INT), agent_id(VARCHAR), persona_id(VARCHAR), situation(VARCHAR), confidence(DECIMAL), matched_rule(VARCHAR), created_at(TIMESTAMP)
 * - augmented_teacher_sessions: user_id(INT), agent_id(VARCHAR), session_key(VARCHAR), current_situation(VARCHAR), current_persona(VARCHAR), context_data(JSON), last_activity(TIMESTAMP)
 * - augmented_teacher_persona_transitions: id(INT), user_id(INT), from_persona(VARCHAR), to_persona(VARCHAR), trigger_type(VARCHAR), confidence(DECIMAL), context_snapshot(JSON), created_at(TIMESTAMP)
 *
 * 사용 예시:
 * $engine = new PersonaRuleEngine(['debug_mode' => true]);
 * $engine->loadRules(__DIR__ . '/../rules/rules.yaml');
 * $result = $engine->process($userId, '수학이 너무 어려워요');
 * echo $result['response']['text'];
 */
