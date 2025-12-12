<?php
/**
 * Agent02PersonaRuleEngine - 시험일정 페르소나 규칙 엔진
 *
 * D-Day 기반 상황과 학생 유형을 조합하여 33개 페르소나 중 하나를 식별하고
 * 맞춤형 학습 전략과 응답을 생성합니다.
 *
 * 페르소나 구조:
 * - 4개 D-Day 상황: D_URGENT(≤3), D_BALANCED(4-10), D_CONCEPT(11-30), D_FOUNDATION(31+)
 * - 6개 학생 유형: P1(계획형), P2(불안형), P3(회피형), P4(자신감과잉), P5(혼란형), P6(외부의존)
 * - 9개 특수 상황: C_P1~P3(복합), E_P1~P3(정서), Q_P1~P3(질문)
 * - 총 33개 = 4×6 + 9
 *
 * @package AugmentedTeacher\Agent02\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/Agent02DataContext.php');
require_once(__DIR__ . '/RuleParser.php');
require_once(__DIR__ . '/ConditionEvaluator.php');
require_once(__DIR__ . '/ActionExecutor.php');
require_once(__DIR__ . '/RuleCache.php');

class Agent02PersonaRuleEngine {

    /** @var string Agent ID */
    private $agentId = 'agent02';

    /** @var array 로드된 규칙 */
    private $rules = [];

    /** @var RuleParser 규칙 파서 */
    private $parser;

    /** @var ConditionEvaluator 조건 평가기 */
    private $evaluator;

    /** @var ActionExecutor 액션 실행기 */
    private $executor;

    /** @var Agent02DataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var RuleCache 규칙 캐시 */
    private $cache;

    /** @var string 현재 파일 경로 (디버깅용) */
    private $currentFile = __FILE__;

    /** @var array 설정 */
    private $config = [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'debug_mode' => false,
        'log_enabled' => true
    ];

    /**
     * 33개 페르소나 정의
     * 형식: situation_studentType => [name, description, strategy]
     */
    private $personas = [
        // D_URGENT (D-3 이하) × 6 학생유형
        'D_URGENT_P1' => ['name' => '긴급 계획형', 'desc' => '체계적으로 남은 시간 활용', 'strategy' => 'structured_sprint'],
        'D_URGENT_P2' => ['name' => '긴급 불안형', 'desc' => '불안 관리와 핵심 집중', 'strategy' => 'calm_focus'],
        'D_URGENT_P3' => ['name' => '긴급 회피형', 'desc' => '즉시 실행 유도', 'strategy' => 'immediate_action'],
        'D_URGENT_P4' => ['name' => '긴급 과신형', 'desc' => '현실 직시와 빠른 점검', 'strategy' => 'reality_check'],
        'D_URGENT_P5' => ['name' => '긴급 혼란형', 'desc' => '명확한 단계별 안내', 'strategy' => 'clear_steps'],
        'D_URGENT_P6' => ['name' => '긴급 의존형', 'desc' => '주도적 실행 연습', 'strategy' => 'guided_independence'],

        // D_BALANCED (D-4~10) × 6 학생유형
        'D_BALANCED_P1' => ['name' => '균형 계획형', 'desc' => '최적 계획 수립', 'strategy' => 'optimal_planning'],
        'D_BALANCED_P2' => ['name' => '균형 불안형', 'desc' => '점진적 자신감 구축', 'strategy' => 'confidence_building'],
        'D_BALANCED_P3' => ['name' => '균형 회피형', 'desc' => '동기부여와 습관 형성', 'strategy' => 'motivation_habit'],
        'D_BALANCED_P4' => ['name' => '균형 과신형', 'desc' => '객관적 진단과 보완', 'strategy' => 'objective_diagnosis'],
        'D_BALANCED_P5' => ['name' => '균형 혼란형', 'desc' => '학습법 정립', 'strategy' => 'method_establishment'],
        'D_BALANCED_P6' => ['name' => '균형 의존형', 'desc' => '자기주도 전환', 'strategy' => 'self_direction'],

        // D_CONCEPT (D-11~30) × 6 학생유형
        'D_CONCEPT_P1' => ['name' => '개념 계획형', 'desc' => '심화 개념 확장', 'strategy' => 'deep_concept'],
        'D_CONCEPT_P2' => ['name' => '개념 불안형', 'desc' => '기초부터 차근차근', 'strategy' => 'steady_progress'],
        'D_CONCEPT_P3' => ['name' => '개념 회피형', 'desc' => '흥미 유발과 작은 성취', 'strategy' => 'interest_achievement'],
        'D_CONCEPT_P4' => ['name' => '개념 과신형', 'desc' => '취약점 발견과 보완', 'strategy' => 'weakness_improvement'],
        'D_CONCEPT_P5' => ['name' => '개념 혼란형', 'desc' => '체계적 개념 정리', 'strategy' => 'concept_organization'],
        'D_CONCEPT_P6' => ['name' => '개념 의존형', 'desc' => '독립적 학습 훈련', 'strategy' => 'independence_training'],

        // D_FOUNDATION (D-31+) × 6 학생유형
        'D_FOUNDATION_P1' => ['name' => '기초 계획형', 'desc' => '장기 로드맵 설계', 'strategy' => 'long_term_roadmap'],
        'D_FOUNDATION_P2' => ['name' => '기초 불안형', 'desc' => '수학 자신감 형성', 'strategy' => 'math_confidence'],
        'D_FOUNDATION_P3' => ['name' => '기초 회피형', 'desc' => '수학에 대한 태도 전환', 'strategy' => 'attitude_change'],
        'D_FOUNDATION_P4' => ['name' => '기초 과신형', 'desc' => '근본적 이해 점검', 'strategy' => 'fundamental_check'],
        'D_FOUNDATION_P5' => ['name' => '기초 혼란형', 'desc' => '학습 습관 기초 확립', 'strategy' => 'habit_foundation'],
        'D_FOUNDATION_P6' => ['name' => '기초 의존형', 'desc' => '자기 학습 역량 개발', 'strategy' => 'self_learning'],

        // 특수 상황: C (복합)
        'C_P1' => ['name' => '복합 적극해결형', 'desc' => '다중 문제 해결 추구', 'strategy' => 'active_resolution'],
        'C_P2' => ['name' => '복합 압도형', 'desc' => '문제 우선순위화 지원', 'strategy' => 'priority_support'],
        'C_P3' => ['name' => '복합 저항형', 'desc' => '신뢰 구축 후 접근', 'strategy' => 'trust_building'],

        // 특수 상황: E (정서)
        'E_P1' => ['name' => '정서 회복도전형', 'desc' => '자신감 회복 지원', 'strategy' => 'confidence_recovery'],
        'E_P2' => ['name' => '정서 불안공포형', 'desc' => '불안/공포 완화', 'strategy' => 'anxiety_relief'],
        'E_P3' => ['name' => '정서 위기형', 'desc' => '정서적 안정 우선', 'strategy' => 'emotional_stability'],

        // 특수 상황: Q (질문)
        'Q_P1' => ['name' => '질문 전체파악형', 'desc' => '큰 그림 설명', 'strategy' => 'big_picture'],
        'Q_P2' => ['name' => '질문 세부집중형', 'desc' => '세부 정보 제공', 'strategy' => 'detailed_info'],
        'Q_P3' => ['name' => '질문 즉각실행형', 'desc' => '바로 실행 가능한 답변', 'strategy' => 'actionable_answer']
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
        $this->dataContext = new Agent02DataContext();
        $this->cache = new RuleCache($this->config['cache_ttl']);
    }

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath rules.yaml 파일 경로
     * @return bool 로드 성공 여부
     */
    public function loadRules(string $rulesPath): bool {
        try {
            if ($this->config['cache_enabled']) {
                $cached = $this->cache->get($rulesPath);
                if ($cached !== null) {
                    $this->rules = $cached;
                    return true;
                }
            }

            $this->rules = $this->parser->parseRules($rulesPath);

            if (isset($this->rules['persona_identification_rules'])) {
                $this->rules['persona_identification_rules'] =
                    $this->parser->sortByPriority($this->rules['persona_identification_rules']);
            }

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
     * 전체 프로세스 실행
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        try {
            // 1. 학생 컨텍스트 로드 (시험일정/D-Day 포함)
            $context = $this->dataContext->loadByUserId($userId, $sessionData);

            // 2. 메시지 분석 (학생 유형 판단)
            $messageAnalysis = $this->dataContext->analyzeMessage($message);
            $context = array_merge($context, $messageAnalysis, ['user_message' => $message]);

            // 3. 페르소나 식별
            $identification = $this->identifyPersona($context);

            // 4. 응답 생성
            $response = $this->generateResponse($identification, $context);

            // 5. 컨텍스트 저장
            $context['persona_id'] = $identification['persona_id'];
            $this->dataContext->saveContext($userId, $context);

            // 6. 페르소나 기록 저장
            $this->logPersonaMatch($userId, $identification, $context);

            return [
                'success' => true,
                'user_id' => $userId,
                'persona' => $identification,
                'response' => $response,
                'exam_info' => $context['exam_info'] ?? null,
                'd_day' => $context['d_day'] ?? null,
                'situation' => $context['situation'] ?? 'NO_EXAM'
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
     * 페르소나 식별
     *
     * @param array $context 학생 컨텍스트
     * @return array 식별 결과
     */
    public function identifyPersona(array $context): array {
        $result = [
            'persona_id' => 'default',
            'persona_name' => '미식별',
            'confidence' => 0.5,
            'matched_rule' => null,
            'situation' => $context['situation'] ?? 'NO_EXAM',
            'student_type' => null,
            'strategy' => null,
            'd_day' => $context['d_day'] ?? null
        ];

        // 1. D-Day 기반 상황 결정
        $situation = $context['situation'] ?? 'NO_EXAM';

        // 2. 학생 유형 결정 (메시지 분석 또는 이전 기록)
        $studentType = $this->determineStudentType($context);

        // 3. 특수 상황 체크 (복합/정서/질문)
        $specialSituation = $this->checkSpecialSituation($context);

        // 4. 페르소나 ID 결정
        if ($specialSituation) {
            $personaId = $specialSituation . '_' . $this->getSpecialSubType($studentType);
        } elseif ($situation !== 'NO_EXAM' && $studentType) {
            $personaId = $situation . '_' . $studentType;
        } else {
            $personaId = 'default';
        }

        // 5. 페르소나 정보 조회
        if (isset($this->personas[$personaId])) {
            $persona = $this->personas[$personaId];
            $result['persona_id'] = $personaId;
            $result['persona_name'] = $persona['name'];
            $result['strategy'] = $persona['strategy'];
            $result['confidence'] = $this->calculateConfidence($context, $studentType);
            $result['student_type'] = $studentType;
        }

        // 6. YAML 규칙 기반 추가 조정 (있는 경우)
        if (!empty($this->rules['persona_identification_rules'])) {
            $ruleResult = $this->evaluateRules($context);
            if ($ruleResult && $ruleResult['confidence'] > $result['confidence']) {
                $result = array_merge($result, $ruleResult);
            }
        }

        return $result;
    }

    /**
     * 학생 유형 결정
     *
     * @param array $context 컨텍스트
     * @return string|null 학생 유형 (P1~P6)
     */
    private function determineStudentType(array $context): ?string {
        // 1. 메시지 분석 결과 우선 (높은 신뢰도)
        if (!empty($context['detected_student_type']) && ($context['type_confidence'] ?? 0) > 0.5) {
            return $context['detected_student_type'];
        }

        // 2. 직접 전달된 student_type (API 호출 시)
        if (!empty($context['student_type']) && preg_match('/^P[1-6]$/', $context['student_type'])) {
            return $context['student_type'];
        }

        // 3. 이전 기록에서 추론
        if (!empty($context['inferred_student_type'])) {
            return $context['inferred_student_type'];
        }

        // 4. 기본값: P5 (혼란형) - 가장 중립적
        return 'P5';
    }

    /**
     * 특수 상황 체크 (C/E/Q)
     *
     * @param array $context 컨텍스트
     * @return string|null 특수 상황 코드
     */
    private function checkSpecialSituation(array $context): ?string {
        $message = $context['user_message'] ?? '';
        $emotionalIndicators = $context['emotional_indicators'] ?? [];

        // E: 정서적 상황 (불안/공포 수준이 높을 때)
        if (($emotionalIndicators['anxiety_level'] ?? 0) > 0.7) {
            return 'E';
        }

        // C: 복합 상황 (여러 문제 동시 언급)
        $complexKeywords = ['그리고', '또', '게다가', '뿐만 아니라', '심지어'];
        $complexCount = 0;
        foreach ($complexKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $complexCount++;
            }
        }
        if ($complexCount >= 2) {
            return 'C';
        }

        // Q: 질문 상황 (명확한 질문)
        if ($context['has_question'] ?? false) {
            $questionKeywords = ['어떻게', '뭐', '무엇', '왜', '언제', '어디'];
            foreach ($questionKeywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return 'Q';
                }
            }
        }

        return null;
    }

    /**
     * 특수 상황용 서브타입 결정
     *
     * @param string|null $studentType 학생 유형
     * @return string 서브타입 (P1~P3)
     */
    private function getSpecialSubType(?string $studentType): string {
        // 학생 유형을 3그룹으로 매핑
        switch ($studentType) {
            case 'P1':
            case 'P4':
                return 'P1'; // 적극/과신 → 적극해결/전체파악/회복도전
            case 'P2':
            case 'P5':
                return 'P2'; // 불안/혼란 → 압도/세부집중/불안공포
            case 'P3':
            case 'P6':
                return 'P3'; // 회피/의존 → 저항/즉각실행/위기
            default:
                return 'P2';
        }
    }

    /**
     * 신뢰도 계산
     *
     * @param array $context 컨텍스트
     * @param string|null $studentType 학생 유형
     * @return float 신뢰도 (0.0 ~ 1.0)
     */
    private function calculateConfidence(array $context, ?string $studentType): float {
        $confidence = 0.5; // 기본값

        // D-Day 정보가 있으면 +0.2
        if (isset($context['d_day'])) {
            $confidence += 0.2;
        }

        // 메시지 분석으로 유형 판단됐으면 +유형신뢰도
        if (!empty($context['type_confidence'])) {
            $confidence += $context['type_confidence'] * 0.2;
        }

        // 이전 기록과 일치하면 +0.1
        if (!empty($context['inferred_student_type']) &&
            $context['inferred_student_type'] === $studentType) {
            $confidence += 0.1;
        }

        return min($confidence, 1.0);
    }

    /**
     * YAML 규칙 평가
     *
     * @param array $context 컨텍스트
     * @return array|null 매칭 결과
     */
    private function evaluateRules(array $context): ?array {
        foreach ($this->rules['persona_identification_rules'] ?? [] as $rule) {
            if ($this->evaluateRule($rule, $context)) {
                return $this->applyRule($rule, $context);
            }
        }
        return null;
    }

    /**
     * 단일 규칙 평가
     */
    private function evaluateRule(array $rule, array $context): bool {
        if (!isset($rule['conditions'])) {
            return false;
        }

        foreach ($rule['conditions'] as $condition) {
            if (isset($condition['OR'])) {
                if (!$this->evaluator->evaluateOr($condition['OR'], $context)) {
                    return false;
                }
            } elseif (isset($condition['AND'])) {
                if (!$this->evaluator->evaluateAnd($condition['AND'], $context)) {
                    return false;
                }
            } else {
                if (!$this->evaluator->evaluate($condition, $context)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 규칙 적용
     */
    private function applyRule(array $rule, array $context): array {
        $result = [
            'matched_rule' => $rule['rule_id'] ?? 'unknown',
            'confidence' => $rule['confidence'] ?? 0.5,
            'actions' => []
        ];

        if (isset($rule['action'])) {
            $result['actions'] = $this->executor->execute($rule['action'], $context);
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
        $personaId = $identification['persona_id'];
        $persona = $this->personas[$personaId] ?? null;

        // 톤 결정
        $tone = $this->determineTone($identification, $context);

        // 기본 응답 템플릿
        $templateKey = $this->getTemplateKey($identification, $context);
        $responseText = $this->buildResponseText($identification, $context, $templateKey);

        return [
            'text' => $responseText,
            'tone' => $tone,
            'strategy' => $identification['strategy'],
            'template_key' => $templateKey,
            'persona_id' => $personaId,
            'confidence' => $identification['confidence'],
            'suggestions' => $this->getSuggestions($identification, $context)
        ];
    }

    /**
     * 톤 결정
     */
    private function determineTone(array $identification, array $context): string {
        $situation = $identification['situation'] ?? 'NO_EXAM';
        $studentType = $identification['student_type'] ?? 'P5';

        // 상황별 기본 톤
        $situationTones = [
            'D_URGENT' => 'Direct',      // 긴급 → 직접적
            'D_BALANCED' => 'Warm',      // 균형 → 따뜻한
            'D_CONCEPT' => 'Professional', // 개념 → 전문적
            'D_FOUNDATION' => 'Encouraging', // 기초 → 격려
            'NO_EXAM' => 'Friendly'      // 시험없음 → 친근
        ];

        // 학생 유형별 조정
        $typeAdjustments = [
            'P1' => 'Professional', // 계획형 → 전문적
            'P2' => 'Calm',         // 불안형 → 차분한
            'P3' => 'Encouraging',  // 회피형 → 격려
            'P4' => 'Direct',       // 과신형 → 직접적
            'P5' => 'Warm',         // 혼란형 → 따뜻한
            'P6' => 'Empathetic'    // 의존형 → 공감적
        ];

        // 불안 수준이 높으면 Calm 우선
        if (($context['emotional_indicators']['anxiety_level'] ?? 0) > 0.5) {
            return 'Calm';
        }

        return $typeAdjustments[$studentType] ?? $situationTones[$situation] ?? 'Professional';
    }

    /**
     * 템플릿 키 결정
     */
    private function getTemplateKey(array $identification, array $context): string {
        $situation = $identification['situation'] ?? 'NO_EXAM';

        if (strpos($identification['persona_id'], 'E_') === 0) {
            return 'emotional_support';
        }
        if (strpos($identification['persona_id'], 'Q_') === 0) {
            return 'question_response';
        }
        if (strpos($identification['persona_id'], 'C_') === 0) {
            return 'complex_support';
        }

        switch ($situation) {
            case 'D_URGENT':
                return 'urgent_study';
            case 'D_BALANCED':
                return 'balanced_plan';
            case 'D_CONCEPT':
                return 'concept_focus';
            case 'D_FOUNDATION':
                return 'foundation_building';
            default:
                return 'exam_registration';
        }
    }

    /**
     * 응답 텍스트 생성
     */
    private function buildResponseText(array $identification, array $context, string $templateKey): string {
        $studentName = $context['student_name'] ?? '학생';
        $dDay = $context['d_day'] ?? null;
        $examName = $context['exam_info']['exam_name'] ?? '시험';
        $persona = $this->personas[$identification['persona_id']] ?? null;

        $templates = [
            'urgent_study' => "🔴 {$studentName}님, {$examName}이 D-{$dDay}이에요! 지금 가장 중요한 건 핵심만 집중하는 거예요. ",
            'balanced_plan' => "📊 {$studentName}님, {$examName}까지 D-{$dDay}! 균형 잡힌 학습이 가능한 시간이에요. ",
            'concept_focus' => "📚 {$studentName}님, {$examName}까지 {$dDay}일 남았어요. 개념을 탄탄히 다질 좋은 시간이에요! ",
            'foundation_building' => "🌱 {$studentName}님, 시험까지 충분한 시간이 있어요. 기초부터 차근차근 준비해볼까요? ",
            'emotional_support' => "💙 {$studentName}님, 힘드시죠. 먼저 마음을 진정시키고 함께 방법을 찾아봐요. ",
            'question_response' => "💡 좋은 질문이에요! ",
            'complex_support' => "🤝 여러 가지가 겹쳐서 힘드시겠어요. 하나씩 정리해볼게요. ",
            'exam_registration' => "📅 {$studentName}님, 시험 일정을 등록하시면 맞춤 학습 전략을 세울 수 있어요! "
        ];

        $baseText = $templates[$templateKey] ?? $templates['exam_registration'];

        // 페르소나별 추가 메시지
        if ($persona) {
            $strategyMessages = [
                'structured_sprint' => "남은 시간을 체계적으로 나눠서 핵심 단원별로 정리해볼까요?",
                'calm_focus' => "깊게 숨 쉬고, 가장 자신 있는 부분부터 점검해봐요.",
                'immediate_action' => "지금 바로 시작해야 해요! 가장 중요한 한 가지부터 해볼까요?",
                'reality_check' => "실제로 풀어보면서 어디가 약한지 확인해볼까요?",
                'clear_steps' => "1단계부터 차근차근 알려드릴게요. 따라오시면 돼요!",
                'guided_independence' => "제가 옆에서 도와드릴 테니, 직접 해보실 수 있어요!"
            ];

            $strategy = $persona['strategy'];
            if (isset($strategyMessages[$strategy])) {
                $baseText .= $strategyMessages[$strategy];
            }
        }

        return $baseText;
    }

    /**
     * 학습 제안 생성
     */
    private function getSuggestions(array $identification, array $context): array {
        $suggestions = [];
        $dDay = $context['d_day'] ?? null;

        if ($dDay !== null && $dDay <= 3) {
            $suggestions[] = '핵심 개념 요약 복습';
            $suggestions[] = '자주 틀리는 문제 유형 점검';
            $suggestions[] = '시험 시간 배분 연습';
        } elseif ($dDay !== null && $dDay <= 10) {
            $suggestions[] = '단원별 문제 풀이';
            $suggestions[] = '취약점 집중 보완';
            $suggestions[] = '실전 모의고사';
        } elseif ($dDay !== null && $dDay <= 30) {
            $suggestions[] = '개념 정리 노트 작성';
            $suggestions[] = '유형별 문제 분석';
            $suggestions[] = '오답 노트 정리';
        } else {
            $suggestions[] = '기초 개념 학습';
            $suggestions[] = '학습 습관 형성';
            $suggestions[] = '시험 일정 등록';
        }

        return $suggestions;
    }

    /**
     * 페르소나 매칭 로깅
     */
    private function logPersonaMatch(int $userId, array $result, array $context): void {
        if (!$this->config['log_enabled']) {
            return;
        }

        global $DB;

        try {
            $tableExists = $DB->get_manager()->table_exists('augmented_teacher_personas');
            if (!$tableExists) {
                return;
            }

            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->persona_id = $result['persona_id'];
            $record->situation = $result['situation'] ?? substr($result['persona_id'], 0, strpos($result['persona_id'], '_') ?: 2);
            $record->confidence = $result['confidence'];
            $record->matched_rule = $result['matched_rule'];
            $record->context_snapshot = json_encode([
                'd_day' => $context['d_day'] ?? null,
                'student_type' => $result['student_type'] ?? null,
                'exam_name' => $context['exam_info']['exam_name'] ?? null
            ]);
            $record->created_at = date('Y-m-d H:i:s');

            $DB->insert_record('augmented_teacher_personas', $record);

        } catch (Exception $e) {
            $this->logError("페르소나 로깅 실패: " . $e->getMessage(), __LINE__);
        }
    }

    /**
     * 에러 로깅
     */
    private function logError(string $message, int $line): void {
        error_log("[Agent02PersonaRuleEngine] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 디버그 정보 반환
     */
    public function getDebugInfo(): array {
        return [
            'agent_id' => $this->agentId,
            'personas_count' => count($this->personas),
            'rules_loaded' => count($this->rules['persona_identification_rules'] ?? []),
            'config' => $this->config,
            'version' => '1.0'
        ];
    }

    /**
     * 페르소나 목록 반환
     */
    public function getPersonaList(): array {
        return $this->personas;
    }
}

/*
 * Agent02PersonaRuleEngine v1.0 - 시험일정 페르소나 엔진
 *
 * 33개 페르소나:
 * - D_URGENT × P1~P6 = 6개
 * - D_BALANCED × P1~P6 = 6개
 * - D_CONCEPT × P1~P6 = 6개
 * - D_FOUNDATION × P1~P6 = 6개
 * - C_P1~P3 (복합) = 3개
 * - E_P1~P3 (정서) = 3개
 * - Q_P1~P3 (질문) = 3개
 * 총 = 24 + 9 = 33개
 *
 * 관련 DB:
 * - at_exam_schedules: 시험 일정
 * - augmented_teacher_personas: 페르소나 이력 (agent_id='agent02')
 * - augmented_teacher_sessions: 세션 데이터
 */
