<?php
/**
 * Agent01PersonaEngine - 온보딩 에이전트 페르소나 엔진
 *
 * AbstractPersonaEngine을 상속하여 표준화된 페르소나 시스템 구현
 * 48개 페르소나 (S0-S5, C, Q, E × P1-P6) 지원
 * NLU 분석 통합으로 고급 의도/감정 분석 제공
 *
 * @package     AugmentedTeacher\Agent01\PersonaSystem
 * @subpackage  Engine
 * @version     2.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/engine/Agent01PersonaEngine.php
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// AbstractPersonaEngine 로드
require_once(__DIR__ . '/../../../engine_core/base/AbstractPersonaEngine.php');

// Agent01 전용 컴포넌트 로드
require_once(__DIR__ . '/NLUAnalyzer.php');
require_once(__DIR__ . '/PersonaTransitionManager.php');
require_once(__DIR__ . '/ResponseGenerator.php');
require_once(__DIR__ . '/DataContext.php');

use ALT42\EngineCore\Base\AbstractPersonaEngine;

/**
 * Agent01PersonaEngine
 *
 * 온보딩 에이전트 전용 페르소나 엔진
 * 신규 학생 등록부터 학습 설계까지 담당
 */
class Agent01PersonaEngine extends AbstractPersonaEngine
{
    /**
     * 48개 페르소나 정의 (S0-S5, C, Q, E × P1-P6)
     */
    public const PERSONA_NAMES = [
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

    /**
     * 상황 코드 정의
     */
    public const SITUATION_CODES = [
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

    /** @var NLUAnalyzer NLU 분석기 */
    protected $nluAnalyzer;

    /** @var PersonaTransitionManager 전환 관리자 */
    protected $transitionManager;

    /** @var ResponseGenerator 응답 생성기 */
    protected $responseGenerator;

    /** @var DataContext 데이터 컨텍스트 */
    protected $dataContext;

    /** @var array Agent01 전용 설정 */
    protected $agent01Config = [
        'nlu_enabled' => true,
        'transition_enabled' => true,
        'response_templates_enabled' => true,
        'emotion_analysis_enabled' => true
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 옵션
     */
    public function __construct(array $config = [])
    {
        // 부모 클래스 초기화 (nagent = 1)
        parent::__construct(1, $config);

        // Agent01 전용 설정 병합
        $this->agent01Config = array_merge($this->agent01Config, $config);
    }

    /**
     * 초기화 훅 (AbstractPersonaEngine)
     * Agent01 전용 컴포넌트 초기화
     */
    protected function onInitialize(): void
    {
        try {
            // NLU 분석기 초기화
            $this->nluAnalyzer = new NLUAnalyzer();

            // 전환 관리자 초기화
            $this->transitionManager = new PersonaTransitionManager();

            // 응답 생성기 초기화
            $this->responseGenerator = new ResponseGenerator();

            // 데이터 컨텍스트 초기화
            $this->dataContext = new DataContext();

            $this->log('info', 'Agent01 컴포넌트 초기화 완료', [
                'nlu_enabled' => $this->agent01Config['nlu_enabled'],
                'transition_enabled' => $this->agent01Config['transition_enabled']
            ]);

        } catch (Exception $e) {
            $this->log('error', 'Agent01 초기화 실패: ' . $e->getMessage(), [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            throw $e;
        }
    }

    /**
     * 페르소나 식별 구현 (AbstractPersonaEngine)
     *
     * @param int $userId 사용자 ID
     * @param array $contextData 컨텍스트 데이터
     * @param array|null $currentState 현재 상태
     * @return array 식별 결과
     */
    protected function doIdentifyPersona(int $userId, array $contextData, ?array $currentState): array
    {
        $result = [
            'persona_code' => 'default',
            'persona_name' => '미식별 (관찰 중)',
            'confidence' => 0.5,
            'situation' => null,
            'matched_rule' => null,
            'tone' => 'Professional',
            'pace' => 'normal',
            'intervention' => 'InformationProvision',
            'nlu_analysis' => null
        ];

        try {
            // 1. NLU 분석 수행 (Agent01 고유 기능)
            if ($this->agent01Config['nlu_enabled'] && isset($contextData['user_message'])) {
                $nluResult = $this->nluAnalyzer->analyze($contextData['user_message']);
                $result['nlu_analysis'] = $nluResult;

                // NLU 결과를 컨텍스트에 병합
                $contextData = array_merge($contextData, [
                    'detected_intent' => $nluResult['intent']['type'] ?? null,
                    'detected_emotion' => $nluResult['emotion']['primary'] ?? null,
                    'emotion_intensity' => $nluResult['emotion']['intensity'] ?? 0,
                    'detected_topics' => $nluResult['topics'] ?? []
                ]);
            }

            // 2. 규칙 기반 페르소나 식별
            $rules = $this->getRules();
            if (empty($rules['persona_identification_rules'])) {
                $this->log('warning', '페르소나 식별 규칙이 로드되지 않음', [
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                return $result;
            }

            // 우선순위 순서로 규칙 매칭
            foreach ($rules['persona_identification_rules'] as $rule) {
                if ($this->evaluateRuleConditions($rule, $contextData)) {
                    $result = $this->applyIdentifiedRule($rule, $contextData, $result);
                    break;
                }
            }

            // 3. 전환 트리거 감지
            if ($this->agent01Config['transition_enabled'] && $currentState) {
                $trigger = $this->transitionManager->detectMessageTrigger(
                    $contextData['user_message'] ?? '',
                    array_merge($contextData, ['current_persona' => $currentState['persona_code'] ?? null])
                );
                if ($trigger) {
                    $result['transition_trigger'] = $trigger;
                }
            }

        } catch (Exception $e) {
            $this->log('error', '페르소나 식별 실패: ' . $e->getMessage(), [
                'user_id' => $userId,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }

        return $result;
    }

    /**
     * 규칙 조건 평가
     *
     * @param array $rule 규칙
     * @param array $context 컨텍스트
     * @return bool 매칭 여부
     */
    private function evaluateRuleConditions(array $rule, array $context): bool
    {
        if (!isset($rule['conditions'])) {
            return false;
        }

        foreach ($rule['conditions'] as $condition) {
            // OR 조건
            if (isset($condition['OR'])) {
                $orResult = false;
                foreach ($condition['OR'] as $orCondition) {
                    if ($this->evaluateSingleCondition($orCondition, $context)) {
                        $orResult = true;
                        break;
                    }
                }
                if (!$orResult) {
                    return false;
                }
            }
            // AND 조건
            elseif (isset($condition['AND'])) {
                foreach ($condition['AND'] as $andCondition) {
                    if (!$this->evaluateSingleCondition($andCondition, $context)) {
                        return false;
                    }
                }
            }
            // 단일 조건
            else {
                if (!$this->evaluateSingleCondition($condition, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건
     * @param array $context 컨텍스트
     * @return bool 평가 결과
     */
    private function evaluateSingleCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (!$field) {
            return false;
        }

        // 중첩 필드 접근 (예: nlu_analysis.intent.type)
        $contextValue = $this->getNestedValue($context, $field);

        switch ($operator) {
            case '==':
            case 'equals':
                return $contextValue == $value;
            case '!=':
            case 'not_equals':
                return $contextValue != $value;
            case '>':
            case 'greater_than':
                return $contextValue > $value;
            case '<':
            case 'less_than':
                return $contextValue < $value;
            case '>=':
                return $contextValue >= $value;
            case '<=':
                return $contextValue <= $value;
            case 'contains':
                return is_string($contextValue) && strpos($contextValue, $value) !== false;
            case 'in':
                return is_array($value) && in_array($contextValue, $value);
            case 'exists':
                return $contextValue !== null;
            case 'not_exists':
                return $contextValue === null;
            default:
                return false;
        }
    }

    /**
     * 중첩 배열 값 접근
     *
     * @param array $array 배열
     * @param string $path 점 구분 경로
     * @return mixed 값
     */
    private function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 식별된 규칙 적용
     *
     * @param array $rule 매칭된 규칙
     * @param array $context 컨텍스트
     * @param array $result 기본 결과
     * @return array 적용된 결과
     */
    private function applyIdentifiedRule(array $rule, array $context, array $result): array
    {
        $result['matched_rule'] = $rule['rule_id'] ?? 'unknown';
        $result['confidence'] = $rule['confidence'] ?? 0.7;

        // 액션에서 페르소나 정보 추출
        if (isset($rule['action'])) {
            foreach ($rule['action'] as $action) {
                if (is_string($action)) {
                    // identify_persona 액션
                    if (strpos($action, 'identify_persona:') === 0) {
                        $personaId = trim(str_replace(['identify_persona:', "'", '"'], '', $action));
                        $result['persona_code'] = $personaId;
                        $result['persona_name'] = $this->getPersonaName($personaId);
                        $result['situation'] = $this->getSituationFromPersonaId($personaId);
                    }
                    // set_tone 액션
                    elseif (strpos($action, 'set_tone:') === 0) {
                        $result['tone'] = trim(str_replace(['set_tone:', "'", '"'], '', $action));
                    }
                    // set_pace 액션
                    elseif (strpos($action, 'set_pace:') === 0) {
                        $result['pace'] = trim(str_replace(['set_pace:', "'", '"'], '', $action));
                    }
                    // prioritize_intervention 액션
                    elseif (strpos($action, 'prioritize_intervention:') === 0) {
                        $result['intervention'] = trim(str_replace(['prioritize_intervention:', "'", '"'], '', $action));
                    }
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
    public function getPersonaName(string $personaId): string
    {
        return self::PERSONA_NAMES[$personaId] ?? '미식별';
    }

    /**
     * 페르소나 ID에서 상황 코드 추출
     *
     * @param string $personaId 페르소나 ID (예: S0_P1)
     * @return string|null 상황 코드 (예: S0)
     */
    private function getSituationFromPersonaId(string $personaId): ?string
    {
        if (preg_match('/^([A-Z]\d?_?)/', $personaId, $matches)) {
            $code = rtrim($matches[1], '_');
            return self::SITUATION_CODES[$code] ?? self::SITUATION_CODES[$code . '_'] ?? null;
        }
        return null;
    }

    /**
     * 응답 생성 구현 (AbstractPersonaEngine)
     *
     * @param int $userId 사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $userMessage 사용자 메시지
     * @param array $options 옵션
     * @return array 응답 결과
     */
    protected function doGenerateResponse(int $userId, string $personaCode, string $userMessage, array $options): array
    {
        try {
            // 템플릿 키 결정
            $templateKey = $options['template_key'] ?? $this->determineTemplateKey($personaCode, $options);

            // 변수 준비
            $variables = [
                'student_name' => $options['student_name'] ?? $options['firstname'] ?? '학생',
                'persona_name' => $this->getPersonaName($personaCode),
                'situation' => $this->getSituationFromPersonaId($personaCode),
                'date' => date('Y년 m월 d일'),
                'time' => date('H:i'),
                'user_message' => $userMessage
            ];

            // 응답 생성
            $responseText = $this->responseGenerator->generateFromResult(
                [
                    'persona_id' => $personaCode,
                    'tone' => $options['tone'] ?? 'Professional',
                    'intervention' => $options['intervention'] ?? 'InformationProvision'
                ],
                $templateKey,
                array_merge($options, $variables)
            );

            return [
                'text' => $responseText,
                'template_key' => $templateKey,
                'tone' => $options['tone'] ?? 'Professional',
                'intervention' => $options['intervention'] ?? 'InformationProvision',
                'persona_code' => $personaCode,
                'persona_name' => $this->getPersonaName($personaCode)
            ];

        } catch (Exception $e) {
            $this->log('error', '응답 생성 실패: ' . $e->getMessage(), [
                'user_id' => $userId,
                'persona_code' => $personaCode,
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            return [
                'text' => '죄송합니다. 잠시 후 다시 시도해 주세요.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 템플릿 키 자동 결정
     *
     * @param string $personaCode 페르소나 코드
     * @param array $options 옵션
     * @return string 템플릿 키
     */
    private function determineTemplateKey(string $personaCode, array $options): string
    {
        $emotion = $options['detected_emotion'] ?? null;

        // 정서적 지원이 필요한 경우
        if ($emotion && in_array($emotion, ['anxiety', 'frustration', 'sadness', 'fear'])) {
            return 'emotional_support';
        }

        // 상황별 기본 템플릿
        $situation = substr($personaCode, 0, 2);
        $templateMap = [
            'S0' => 'assessment_intro',
            'S1' => 'registration_welcome',
            'S2' => 'learning_plan',
            'S3' => 'progress_check',
            'S4' => 'parent_consultation',
            'S5' => 'goal_setting',
            'C_' => 'complex_support',
            'Q_' => 'question_response',
            'E_' => 'emotional_support'
        ];

        return $templateMap[$situation] ?? 'welcome';
    }

    /**
     * 전환 훅 구현 (AbstractPersonaEngine)
     *
     * @param int $userId 사용자 ID
     * @param string $fromPersona 이전 페르소나
     * @param string $toPersona 새 페르소나
     * @param array $triggerData 트리거 데이터
     */
    protected function onTransition(int $userId, string $fromPersona, string $toPersona, array $triggerData): void
    {
        if (!$this->agent01Config['transition_enabled']) {
            return;
        }

        try {
            // 전환 가능 여부 확인
            $canTransition = $this->transitionManager->canTransition(
                $fromPersona,
                $toPersona,
                $triggerData['confidence'] ?? 0.5
            );

            if ($canTransition['allowed']) {
                // 전환 실행
                $this->transitionManager->executeTransition(
                    $userId,
                    $fromPersona,
                    $toPersona,
                    $triggerData['type'] ?? 'system',
                    $triggerData['context'] ?? []
                );

                $this->log('info', '페르소나 전환 완료', [
                    'user_id' => $userId,
                    'from' => $fromPersona,
                    'to' => $toPersona
                ]);
            } else {
                $this->log('info', '페르소나 전환 거부', [
                    'user_id' => $userId,
                    'reason' => $canTransition['reason'] ?? 'unknown'
                ]);
            }

        } catch (Exception $e) {
            $this->log('error', '전환 처리 실패: ' . $e->getMessage(), [
                'user_id' => $userId,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
        }
    }

    /**
     * 헬스체크 구현 (AbstractPersonaEngine)
     *
     * @return array 상태 정보
     */
    protected function doHealthCheck(): array
    {
        $status = [
            'agent' => 'agent01_onboarding',
            'version' => '2.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'components' => [],
            'personas_count' => count(self::PERSONA_NAMES),
            'situations_count' => count(self::SITUATION_CODES)
        ];

        // 컴포넌트 상태 확인
        $status['components']['nlu_analyzer'] = isset($this->nluAnalyzer) ? 'initialized' : 'not_initialized';
        $status['components']['transition_manager'] = isset($this->transitionManager) ? 'initialized' : 'not_initialized';
        $status['components']['response_generator'] = isset($this->responseGenerator) ? 'initialized' : 'not_initialized';
        $status['components']['data_context'] = isset($this->dataContext) ? 'initialized' : 'not_initialized';

        // 규칙 로드 상태
        $rules = $this->getRules();
        $status['rules_loaded'] = !empty($rules['persona_identification_rules']);
        $status['rules_count'] = count($rules['persona_identification_rules'] ?? []);

        // 전체 상태 판정
        $allInitialized = !in_array('not_initialized', $status['components']);
        $status['healthy'] = $allInitialized && $status['rules_loaded'];

        return $status;
    }

    /**
     * 규칙 파일 경로 반환 (AbstractPersonaEngine)
     *
     * @return string 규칙 파일 절대 경로
     */
    protected function getRulesFilePath(): string
    {
        return __DIR__ . '/../rules.yaml';
    }

    // =========================================================================
    // Agent01 고유 메서드 (NLU, 전환 분석 등)
    // =========================================================================

    /**
     * 전체 프로세스 실행 (레거시 호환 + 기능 확장)
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array
    {
        try {
            // 1. 학생 컨텍스트 로드
            $context = $this->dataContext->loadByUserId($userId, $sessionData);
            $context['user_message'] = $message;

            // 2. 현재 상태 조회
            $currentState = $this->getState($userId);

            // 3. 페르소나 식별 (AbstractPersonaEngine 메서드)
            $identification = $this->identifyPersona($userId, $context);

            // 4. 응답 생성
            $response = $this->generateResponse(
                $userId,
                $identification['persona_code'],
                $message,
                array_merge($context, $identification)
            );

            return [
                'success' => true,
                'user_id' => $userId,
                'persona' => $identification,
                'response' => $response,
                'context' => [
                    'intent' => $identification['nlu_analysis']['intent']['type'] ?? null,
                    'emotion' => $identification['nlu_analysis']['emotion']['primary'] ?? null,
                    'topics' => $identification['nlu_analysis']['topics'] ?? []
                ]
            ];

        } catch (Exception $e) {
            $this->log('error', '프로세스 실행 실패: ' . $e->getMessage(), [
                'user_id' => $userId,
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ];
        }
    }

    /**
     * NLU 분석 결과만 반환 (디버깅/테스트용)
     *
     * @param string $message 메시지
     * @return array NLU 분석 결과
     */
    public function analyzeNLU(string $message): array
    {
        if (!$this->agent01Config['nlu_enabled'] || !$this->nluAnalyzer) {
            return ['error' => 'NLU analyzer not enabled'];
        }
        return $this->nluAnalyzer->analyze($message);
    }

    /**
     * 전환 패턴 분석 (통계용)
     *
     * @param int $userId 사용자 ID
     * @return array 전환 패턴 분석 결과
     */
    public function analyzeTransitionPatterns(int $userId): array
    {
        if (!$this->agent01Config['transition_enabled'] || !$this->transitionManager) {
            return ['error' => 'Transition manager not enabled'];
        }
        return $this->transitionManager->analyzeTransitionPatterns($userId);
    }

    /**
     * 전체 페르소나 목록 반환
     *
     * @return array 페르소나 목록
     */
    public static function getAllPersonas(): array
    {
        return self::PERSONA_NAMES;
    }

    /**
     * 상황별 페르소나 목록 반환
     *
     * @param string $situation 상황 코드 (S0, S1, ... E)
     * @return array 해당 상황의 페르소나 목록
     */
    public static function getPersonasBySituation(string $situation): array
    {
        $result = [];
        foreach (self::PERSONA_NAMES as $id => $name) {
            if (strpos($id, $situation) === 0) {
                $result[$id] = $name;
            }
        }
        return $result;
    }
}

/*
 * =========================================================================
 * Agent01PersonaEngine v2.0.0 - AbstractPersonaEngine 상속 버전
 * =========================================================================
 *
 * 주요 변경사항:
 * - AbstractPersonaEngine 상속으로 표준화된 인터페이스 구현
 * - 6개 추상 메서드 구현 (onInitialize, doIdentifyPersona, doGenerateResponse, onTransition, doHealthCheck, getRulesFilePath)
 * - 48개 페르소나 PERSONA_NAMES 상수로 정의
 * - NLUAnalyzer, PersonaTransitionManager, ResponseGenerator 통합 유지
 *
 * DB 테이블 (AbstractPersonaEngine 표준):
 * - mdl_at_agent_persona_state: user_id, nagent, persona_code, confidence, context_data, timecreated, timemodified
 * - mdl_at_agent_transitions: user_id, nagent, from_persona, to_persona, trigger_type, confidence, context_snapshot, timecreated
 * - mdl_at_agent_logs: nagent, level, message, context, timecreated
 *
 * 레거시 호환성:
 * - process() 메서드는 기존 PersonaRuleEngine과 동일한 인터페이스 유지
 * - analyzeNLU(), analyzeTransitionPatterns() 메서드 유지
 *
 * 사용 예시:
 * $engine = new Agent01PersonaEngine(['nlu_enabled' => true]);
 * $result = $engine->process($userId, '수학이 너무 어려워요');
 * echo $result['response']['text'];
 *
 * =========================================================================
 */
