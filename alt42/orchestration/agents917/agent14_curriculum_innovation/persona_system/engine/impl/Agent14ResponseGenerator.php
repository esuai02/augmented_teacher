<?php
/**
 * Agent14ResponseGenerator - Agent14 응답 생성기
 *
 * Agent14 전용 페르소나 기반 응답 생성
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine\Impl
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

require_once(__DIR__ . '/../../../../../ontology_engineering/persona_engine/core/IResponseGenerator.php');

class Agent14ResponseGenerator implements IResponseGenerator {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var string 템플릿 기본 경로 */
    protected $templateBasePath;

    /** @var array 로드된 템플릿 */
    protected $templates = [];

    /** @var array 톤 스타일 정의 */
    protected $toneStyles = [
        'Professional' => [
            'prefix' => '',
            'suffix' => '',
            'style' => '전문적이고 명확한 어조'
        ],
        'Warm' => [
            'prefix' => '',
            'suffix' => ' 함께 해결해 나가요!',
            'style' => '따뜻하고 친근한 어조'
        ],
        'Encouraging' => [
            'prefix' => '좋은 질문이에요! ',
            'suffix' => ' 잘 하고 계세요!',
            'style' => '격려하는 어조'
        ],
        'Calm' => [
            'prefix' => '',
            'suffix' => ' 차근차근 진행해 봅시다.',
            'style' => '차분하고 안정적인 어조'
        ],
        'Playful' => [
            'prefix' => '자, 그럼 ',
            'suffix' => ' 재미있게 해봐요!',
            'style' => '활기차고 유쾌한 어조'
        ],
        'Empathetic' => [
            'prefix' => '이해해요. ',
            'suffix' => '',
            'style' => '공감하는 어조'
        ],
        'Direct' => [
            'prefix' => '',
            'suffix' => '',
            'style' => '직접적이고 간결한 어조'
        ]
    ];

    /** @var array 개입 패턴 정의 */
    protected $interventionPatterns = [
        'InformationProvision' => '정보 제공 및 설명',
        'GapAnalysis' => '격차 분석 및 진단',
        'PlanDesign' => '계획 수립 및 설계',
        'SkillBuilding' => '역량 개발 지원',
        'AssessmentDesign' => '평가 설계 지원',
        'BehaviorModification' => '행동 변화 촉진',
        'EmotionalSupport' => '정서적 지지',
        'SafetyNet' => '안전망 제공',
        'GoalSetting' => '목표 설정 지원',
        'CrisisIntervention' => '위기 개입'
    ];

    /**
     * 생성자
     *
     * @param string $templateBasePath 템플릿 기본 경로
     */
    public function __construct(string $templateBasePath = '') {
        $this->templateBasePath = $templateBasePath ?: __DIR__ . '/../../templates';
        $this->loadDefaultTemplates();
    }

    /**
     * 기본 템플릿 로드
     */
    protected function loadDefaultTemplates(): void {
        // C1: 교육과정 분석 템플릿
        $this->templates['C1_default'] = "현재 교육과정을 분석해 드리겠습니다. {content}";
        $this->templates['C1_analysis'] = "분석 결과입니다:\n{content}\n\n추가 분석이 필요하시면 말씀해 주세요.";
        $this->templates['C1_guidance'] = "교육과정 분석 방법을 안내해 드릴게요. {content}";

        // C2: 콘텐츠 설계 템플릿
        $this->templates['C2_default'] = "학습 콘텐츠 설계를 도와드리겠습니다. {content}";
        $this->templates['C2_design'] = "설계 제안입니다:\n{content}\n\n수정 사항이 있으시면 알려주세요.";
        $this->templates['C2_creation'] = "다음과 같이 콘텐츠를 구성해 보세요:\n{content}";

        // C3: 교수법 혁신 템플릿
        $this->templates['C3_default'] = "혁신적인 교수법을 제안해 드릴게요. {content}";
        $this->templates['C3_innovation'] = "새로운 교수법 아이디어입니다:\n{content}\n\n적용 시 피드백을 주시면 더 좋은 방법을 찾을 수 있어요!";
        $this->templates['C3_guidance'] = "교수법 적용 가이드입니다:\n{content}";

        // C4: 평가 설계 템플릿
        $this->templates['C4_default'] = "학습 평가 설계를 도와드리겠습니다. {content}";
        $this->templates['C4_evaluation'] = "평가 설계 제안입니다:\n{content}\n\n평가 목적에 맞게 조정해 주세요.";
        $this->templates['C4_assessment'] = "다음 평가 방법을 고려해 보세요:\n{content}";

        // C5: 적용 및 피드백 템플릿
        $this->templates['C5_default'] = "적용 결과를 분석하고 피드백을 드릴게요. {content}";
        $this->templates['C5_improvement'] = "개선 방향 제안입니다:\n{content}\n\n지속적인 개선을 함께 해나가요!";
        $this->templates['C5_explanation'] = "다음 사항을 확인해 보세요:\n{content}";

        // 공통 템플릿
        $this->templates['greeting'] = "안녕하세요! 교육과정 혁신을 위한 도움을 드릴 수 있어 기쁩니다. {content}";
        $this->templates['error'] = "죄송합니다. 요청을 처리하는 중 문제가 발생했습니다. 다시 한번 말씀해 주시겠어요?";
    }

    /**
     * 페르소나 기반 응답 생성
     *
     * @param string $personaId 페르소나 ID
     * @param string $templateKey 템플릿 키
     * @param array $variables 치환 변수
     * @param array $options 추가 옵션
     * @return string 생성된 응답
     */
    public function generate(
        string $personaId,
        string $templateKey,
        array $variables = [],
        array $options = []
    ): string {
        // 템플릿 로드
        $template = $this->getTemplate($templateKey);
        if (!$template) {
            $template = $this->templates[$this->getDefaultTemplateKey($personaId)] ?? '{content}';
        }

        // 변수 치환
        $response = $this->replaceVariables($template, $variables);

        // 톤 적용
        $tone = $options['tone'] ?? 'Professional';
        $response = $this->applyTone($response, $tone);

        return $response;
    }

    /**
     * 식별 결과 기반 응답 생성
     *
     * @param array $identificationResult 페르소나 식별 결과
     * @param string $templateKey 템플릿 키
     * @param array $context 컨텍스트
     * @return string 생성된 응답
     */
    public function generateFromResult(array $identificationResult, string $templateKey, array $context = []): string {
        $personaId = $identificationResult['persona_id'] ?? '';
        $tone = $identificationResult['tone'] ?? 'Professional';
        $intervention = $identificationResult['intervention'] ?? 'InformationProvision';

        // 컨텍스트에서 변수 추출
        $variables = $this->extractVariables($context, $identificationResult);

        // 기본 콘텐츠 생성
        $variables['content'] = $this->generateContent($identificationResult, $context);

        return $this->generate($personaId, $templateKey, $variables, [
            'tone' => $tone,
            'intervention' => $intervention
        ]);
    }

    /**
     * 컨텍스트 기반 콘텐츠 생성
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return string 생성된 콘텐츠
     */
    protected function generateContent(array $identification, array $context): string {
        $situation = $identification['situation'] ?? 'C1';
        $intent = $context['intent'] ?? 'general';
        $message = $context['user_message'] ?? '';

        // 상황별 기본 콘텐츠
        $contentMap = [
            'C1' => '교육과정 분석에 대해 도움을 드릴 수 있습니다.',
            'C2' => '효과적인 학습 콘텐츠 설계를 함께 진행해 보겠습니다.',
            'C3' => '혁신적인 교수법을 탐색하고 적용해 보겠습니다.',
            'C4' => '학습 성과를 측정하기 위한 평가 방법을 설계해 보겠습니다.',
            'C5' => '적용 결과를 분석하고 개선 방향을 제시해 드리겠습니다.'
        ];

        return $contentMap[$situation] ?? '무엇을 도와드릴까요?';
    }

    /**
     * 변수 추출
     *
     * @param array $context 컨텍스트
     * @param array $identification 식별 결과
     * @return array 변수 배열
     */
    protected function extractVariables(array $context, array $identification): array {
        return [
            'user_name' => $context['user']['fullname'] ?? $context['user']['firstname'] ?? '선생님',
            'situation' => $identification['situation'] ?? '',
            'persona_name' => $identification['persona_name'] ?? '',
            'confidence' => $identification['confidence'] ?? 0,
            'message' => $context['user_message'] ?? '',
            'intent' => $context['intent'] ?? ''
        ];
    }

    /**
     * 템플릿 가져오기
     *
     * @param string $templateKey 템플릿 키
     * @return string|null 템플릿 또는 null
     */
    protected function getTemplate(string $templateKey): ?string {
        // 메모리 캐시 확인
        if (isset($this->templates[$templateKey])) {
            return $this->templates[$templateKey];
        }

        // 파일에서 로드 시도
        $filePath = $this->templateBasePath . '/default/' . $templateKey . '.txt';
        if (file_exists($filePath)) {
            $this->templates[$templateKey] = file_get_contents($filePath);
            return $this->templates[$templateKey];
        }

        return null;
    }

    /**
     * 기본 템플릿 키 결정
     *
     * @param string $personaId 페르소나 ID
     * @return string 템플릿 키
     */
    protected function getDefaultTemplateKey(string $personaId): string {
        // 페르소나 ID에서 상황 코드 추출 (예: C1_P1 -> C1)
        $parts = explode('_', $personaId);
        $situation = $parts[0] ?? 'C1';

        return $situation . '_default';
    }

    /**
     * 변수 치환
     *
     * @param string $template 템플릿
     * @param array $variables 변수 배열
     * @return string 치환된 문자열
     */
    protected function replaceVariables(string $template, array $variables): string {
        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{' . $key . '}', (string)$value, $template);
            }
        }
        return $template;
    }

    /**
     * 톤 적용
     *
     * @param string $response 응답 텍스트
     * @param string $tone 톤
     * @return string 톤이 적용된 응답
     */
    protected function applyTone(string $response, string $tone): string {
        $toneStyle = $this->toneStyles[$tone] ?? $this->toneStyles['Professional'];

        $prefix = $toneStyle['prefix'] ?? '';
        $suffix = $toneStyle['suffix'] ?? '';

        return $prefix . $response . $suffix;
    }

    /**
     * 톤 스타일 목록 반환
     *
     * @return array 톤 스타일 목록
     */
    public function getToneStyles(): array {
        return array_keys($this->toneStyles);
    }

    /**
     * 개입 패턴 목록 반환
     *
     * @return array 개입 패턴 목록
     */
    public function getInterventionPatterns(): array {
        return $this->interventionPatterns;
    }

    /**
     * 템플릿 목록 조회
     *
     * @param string $situation 상황 코드 (선택)
     * @return array 템플릿 목록
     */
    public function listTemplates(string $situation = null): array {
        if ($situation) {
            return array_filter(array_keys($this->templates), function($key) use ($situation) {
                return strpos($key, $situation . '_') === 0;
            });
        }
        return array_keys($this->templates);
    }

    /**
     * 템플릿 추가/업데이트
     *
     * @param string $key 템플릿 키
     * @param string $template 템플릿 내용
     */
    public function setTemplate(string $key, string $template): void {
        $this->templates[$key] = $template;
    }
}

/*
 * 지원 톤:
 * - Professional: 전문적이고 명확한 어조
 * - Warm: 따뜻하고 친근한 어조
 * - Encouraging: 격려하는 어조
 * - Calm: 차분하고 안정적인 어조
 * - Playful: 활기차고 유쾌한 어조
 * - Empathetic: 공감하는 어조
 * - Direct: 직접적이고 간결한 어조
 *
 * 지원 개입 유형:
 * - InformationProvision: 정보 제공
 * - GapAnalysis: 격차 분석
 * - PlanDesign: 계획 설계
 * - SkillBuilding: 역량 개발
 * - AssessmentDesign: 평가 설계
 * - BehaviorModification: 행동 변화
 */
