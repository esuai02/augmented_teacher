<?php
/**
 * BaseResponseGenerator - 기본 응답 생성기 구현
 *
 * IResponseGenerator 인터페이스의 기본 구현체
 * 페르소나 기반 응답 템플릿 관리 및 생성
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 2.0
 */

require_once(__DIR__ . '/../core/IResponseGenerator.php');

class BaseResponseGenerator implements IResponseGenerator {

    protected $currentFile = __FILE__;
    protected $agentId;
    protected $templateDir;
    protected $templates = [];

    /**
     * 톤 스타일 정의
     * @var array
     */
    protected $toneStyles = [
        'Professional' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '님',
            'formality' => 'formal',
            'emotion_level' => 'low'
        ],
        'Warm' => [
            'prefix' => '안녕하세요! ',
            'suffix' => ' 😊',
            'honorific' => '님',
            'formality' => 'semi-formal',
            'emotion_level' => 'medium'
        ],
        'Encouraging' => [
            'prefix' => '잘하고 있어요! ',
            'suffix' => ' 파이팅!',
            'honorific' => '',
            'formality' => 'casual',
            'emotion_level' => 'high'
        ],
        'Calm' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '님',
            'formality' => 'formal',
            'emotion_level' => 'low'
        ],
        'Playful' => [
            'prefix' => '헤이! ',
            'suffix' => ' ㅋㅋ',
            'honorific' => '',
            'formality' => 'casual',
            'emotion_level' => 'high'
        ],
        'Direct' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '',
            'formality' => 'neutral',
            'emotion_level' => 'low'
        ],
        'Empathetic' => [
            'prefix' => '그런 마음이 드셨군요. ',
            'suffix' => ' 걱정하지 마세요.',
            'honorific' => '님',
            'formality' => 'formal',
            'emotion_level' => 'medium'
        ],
        'Supportive' => [
            'prefix' => '함께 해결해 봐요. ',
            'suffix' => ' 제가 도와드릴게요.',
            'honorific' => '님',
            'formality' => 'semi-formal',
            'emotion_level' => 'medium'
        ],
        'Reassuring' => [
            'prefix' => '괜찮아요. ',
            'suffix' => ' 천천히 해도 돼요.',
            'honorific' => '님',
            'formality' => 'semi-formal',
            'emotion_level' => 'medium'
        ]
    ];

    /**
     * 개입 패턴 정의
     * @var array
     */
    protected $interventionPatterns = [
        'EmotionalSupport' => [
            'template_suffix' => '_emotional',
            'priority' => 1,
            'requires_empathy' => true,
            'focus' => 'feelings',
            'approach' => 'validating'
        ],
        'InformationProvision' => [
            'template_suffix' => '_info',
            'priority' => 2,
            'requires_empathy' => false,
            'focus' => 'knowledge',
            'approach' => 'explaining'
        ],
        'SkillBuilding' => [
            'template_suffix' => '_skill',
            'priority' => 3,
            'requires_empathy' => false,
            'focus' => 'ability',
            'approach' => 'practicing'
        ],
        'BehaviorModification' => [
            'template_suffix' => '_behavior',
            'priority' => 4,
            'requires_empathy' => true,
            'focus' => 'actions',
            'approach' => 'guiding'
        ],
        'SafetyNet' => [
            'template_suffix' => '_safety',
            'priority' => 0,
            'requires_empathy' => true,
            'focus' => 'protection',
            'approach' => 'securing'
        ],
        'PlanDesign' => [
            'template_suffix' => '_plan',
            'priority' => 3,
            'requires_empathy' => false,
            'focus' => 'strategy',
            'approach' => 'planning'
        ],
        'AssessmentDesign' => [
            'template_suffix' => '_assess',
            'priority' => 4,
            'requires_empathy' => false,
            'focus' => 'evaluation',
            'approach' => 'measuring'
        ],
        'GapAnalysis' => [
            'template_suffix' => '_gap',
            'priority' => 3,
            'requires_empathy' => false,
            'focus' => 'diagnosis',
            'approach' => 'analyzing'
        ],
        'GoalSetting' => [
            'template_suffix' => '_goal',
            'priority' => 2,
            'requires_empathy' => false,
            'focus' => 'objectives',
            'approach' => 'targeting'
        ],
        'CrisisIntervention' => [
            'template_suffix' => '_crisis',
            'priority' => 0,
            'requires_empathy' => true,
            'focus' => 'stabilization',
            'approach' => 'de-escalating'
        ],
        // Agent08 전용 패턴
        'FocusGuidance' => [
            'template_suffix' => '_focus',
            'priority' => 2,
            'requires_empathy' => false,
            'focus' => 'concentration',
            'approach' => 'centering'
        ],
        'CalmnessCoaching' => [
            'template_suffix' => '_calm',
            'priority' => 1,
            'requires_empathy' => true,
            'focus' => 'tranquility',
            'approach' => 'soothing'
        ],
        'MindfulnessSupport' => [
            'template_suffix' => '_mindful',
            'priority' => 2,
            'requires_empathy' => true,
            'focus' => 'awareness',
            'approach' => 'grounding'
        ]
    ];

    /**
     * 기본 템플릿 정의
     * @var array
     */
    protected $defaultTemplates = [
        'default' => '안녕하세요{{honorific}}! {{message}}',
        'greeting' => '반갑습니다{{honorific}}! 무엇을 도와드릴까요?',
        'farewell' => '좋은 하루 되세요{{honorific}}! 다음에 또 만나요.',
        'error' => '죄송합니다{{honorific}}, 일시적인 오류가 발생했습니다.',
        'unknown' => '이해하지 못했습니다{{honorific}}. 다시 말씀해 주시겠어요?',
        'encouragement' => '잘하고 있어요{{honorific}}! {{message}}',
        'support' => '{{honorific}}, 제가 함께 할게요. {{message}}'
    ];

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID
     * @param string|null $templateDir 템플릿 디렉토리 경로
     */
    public function __construct(string $agentId = 'default', string $templateDir = null) {
        $this->agentId = $agentId;
        $this->templateDir = $templateDir;
        $this->templates = $this->defaultTemplates;
    }

    /**
     * 응답 생성 (인터페이스 구현)
     *
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트 데이터
     * @return array 생성된 응답
     */
    public function generate(array $identification, array $context): array {
        try {
            $personaId = $identification['persona_id'] ?? 'default';
            $tone = $identification['tone'] ?? 'Professional';
            $intervention = $identification['intervention'] ?? 'InformationProvision';
            $templateKey = $context['template_key'] ?? 'default';
            $message = $context['message'] ?? '';

            // 개입 유형에 따른 템플릿 키 조정
            $adjustedKey = $this->adjustTemplateKeyByIntervention($templateKey, $intervention);

            // 템플릿 로드
            $template = $this->loadTemplate($adjustedKey, $personaId);

            // 변수 준비
            $variables = array_merge($context, [
                'user_name' => $context['user']['firstname'] ?? '학생',
                'message' => $message,
                'persona_id' => $personaId,
                'honorific' => $this->toneStyles[$tone]['honorific'] ?? '님'
            ]);

            // 템플릿 변수 치환
            $responseText = $this->substituteVariables($template, $variables);

            // 톤 적용
            $responseText = $this->applyTone($responseText, $tone);

            // 개입 전략 적용
            $responseText = $this->applyIntervention($responseText, $intervention);

            return [
                'success' => true,
                'text' => $responseText,
                'persona_id' => $personaId,
                'tone' => $tone,
                'intervention' => $intervention,
                'template_used' => $adjustedKey
            ];

        } catch (Exception $e) {
            error_log("[BaseResponseGenerator] {$this->currentFile}:" . __LINE__ .
                " - 응답 생성 실패: " . $e->getMessage());
            return [
                'success' => false,
                'text' => '죄송합니다. 응답 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 템플릿 기반 응답 생성 (인터페이스 구현)
     *
     * @param string $templateId 템플릿 ID
     * @param array $variables 템플릿 변수
     * @return string 생성된 응답 텍스트
     */
    public function fromTemplate(string $templateId, array $variables): string {
        try {
            $template = $this->loadTemplate($templateId);
            return $this->substituteVariables($template, $variables);
        } catch (Exception $e) {
            error_log("[BaseResponseGenerator] {$this->currentFile}:" . __LINE__ .
                " - 템플릿 응답 생성 실패: " . $e->getMessage());
            return $variables['message'] ?? '';
        }
    }

    /**
     * 응답 톤 적용 (인터페이스 구현)
     *
     * @param string $text 원본 텍스트
     * @param string $tone 톤 (Professional, Friendly, Supportive 등)
     * @return string 톤이 적용된 텍스트
     */
    public function applyTone(string $text, string $tone): string {
        $style = $this->toneStyles[$tone] ?? $this->toneStyles['Professional'];
        return $style['prefix'] . $text . $style['suffix'];
    }

    /**
     * 개입 전략 적용 (인터페이스 구현)
     *
     * @param string $text 원본 텍스트
     * @param string $intervention 개입 유형
     * @return string 개입 전략이 적용된 텍스트
     */
    public function applyIntervention(string $text, string $intervention): string {
        $pattern = $this->interventionPatterns[$intervention] ?? null;

        if (!$pattern) {
            return $text;
        }

        // 공감이 필요한 개입인 경우 공감 표현 추가
        if ($pattern['requires_empathy']) {
            $empathyPhrases = [
                'feelings' => '그런 마음이 드셨군요. ',
                'protection' => '걱정되셨겠어요. ',
                'stabilization' => '많이 힘드셨죠. ',
                'tranquility' => '마음이 불안하셨군요. ',
                'awareness' => '지금 이 순간에 집중해봐요. '
            ];

            $focus = $pattern['focus'];
            if (isset($empathyPhrases[$focus])) {
                $text = $empathyPhrases[$focus] . $text;
            }
        }

        return $text;
    }

    /**
     * 템플릿 등록 (인터페이스 구현)
     *
     * @param string $templateId 템플릿 ID
     * @param string $template 템플릿 문자열
     * @return void
     */
    public function registerTemplate(string $templateId, string $template): void {
        $this->templates[$templateId] = $template;
    }

    /**
     * 개입 유형에 따른 템플릿 키 조정
     *
     * @param string $templateKey 원본 템플릿 키
     * @param string $intervention 개입 유형
     * @return string 조정된 템플릿 키
     */
    protected function adjustTemplateKeyByIntervention(string $templateKey, string $intervention): string {
        $pattern = $this->interventionPatterns[$intervention] ?? null;
        if ($pattern) {
            $adjustedKey = $templateKey . $pattern['template_suffix'];
            if (isset($this->templates[$adjustedKey]) ||
                ($this->templateDir && file_exists($this->templateDir . '/' . $adjustedKey . '.txt'))) {
                return $adjustedKey;
            }
        }
        return $templateKey;
    }

    /**
     * 템플릿 로드
     *
     * @param string $templateKey 템플릿 키
     * @param string|null $personaId 페르소나 ID
     * @return string 템플릿 내용
     */
    protected function loadTemplate(string $templateKey, string $personaId = null): string {
        // 페르소나별 템플릿 확인
        if ($personaId) {
            $personaKey = "{$personaId}_{$templateKey}";
            if (isset($this->templates[$personaKey])) {
                return $this->templates[$personaKey];
            }
        }

        // 기본 템플릿 확인
        if (isset($this->templates[$templateKey])) {
            return $this->templates[$templateKey];
        }

        // 파일 기반 템플릿 로드
        if ($this->templateDir) {
            $filePath = $this->templateDir . '/' . $templateKey . '.txt';
            if (file_exists($filePath)) {
                $template = file_get_contents($filePath);
                $this->templates[$templateKey] = $template;
                return $template;
            }
        }

        // 폴백: 기본 템플릿
        return $this->templates['default'] ?? '{{message}}';
    }

    /**
     * 템플릿 변수 치환
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 변수 배열
     * @return string 치환된 문자열
     */
    protected function substituteVariables(string $template, array $variables): string {
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        // 미치환 변수 제거
        return preg_replace('/\{\{[^}]+\}\}/', '', $template);
    }

    // ===== 유틸리티 메서드 =====

    /**
     * 템플릿 일괄 등록
     *
     * @param array $templates 템플릿 배열
     */
    public function addTemplates(array $templates): void {
        $this->templates = array_merge($this->templates, $templates);
    }

    /**
     * 사용 가능한 톤 스타일 목록 반환
     *
     * @return array 톤 스타일 키 목록
     */
    public function getToneStyles(): array {
        return array_keys($this->toneStyles);
    }

    /**
     * 사용 가능한 개입 패턴 목록 반환
     *
     * @return array 개입 패턴 키 목록
     */
    public function getInterventionPatterns(): array {
        return array_keys($this->interventionPatterns);
    }

    /**
     * 템플릿 목록 반환
     *
     * @param string|null $prefix 필터링할 접두사
     * @return array 템플릿 목록
     */
    public function listTemplates(string $prefix = null): array {
        if ($prefix) {
            $filtered = [];
            foreach ($this->templates as $key => $template) {
                if (strpos($key, $prefix) === 0) {
                    $filtered[$key] = $template;
                }
            }
            return $filtered;
        }
        return $this->templates;
    }

    /**
     * 톤 스타일 추가
     *
     * @param string $name 톤 이름
     * @param array $style 스타일 정의
     */
    public function addToneStyle(string $name, array $style): void {
        $this->toneStyles[$name] = array_merge(
            ['prefix' => '', 'suffix' => '', 'honorific' => '', 'formality' => 'neutral', 'emotion_level' => 'medium'],
            $style
        );
    }

    /**
     * 개입 패턴 추가
     *
     * @param string $name 패턴 이름
     * @param array $pattern 패턴 정의
     */
    public function addInterventionPattern(string $name, array $pattern): void {
        $this->interventionPatterns[$name] = array_merge(
            ['template_suffix' => '', 'priority' => 5, 'requires_empathy' => false, 'focus' => 'general', 'approach' => 'neutral'],
            $pattern
        );
    }

    /**
     * 에이전트 ID 설정
     *
     * @param string $agentId 에이전트 ID
     */
    public function setAgentId(string $agentId): void {
        $this->agentId = $agentId;
    }

    /**
     * 템플릿 디렉토리 설정
     *
     * @param string $templateDir 템플릿 디렉토리 경로
     */
    public function setTemplateDir(string $templateDir): void {
        $this->templateDir = $templateDir;
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_at_response_templates (응답 템플릿 저장)
 *   - id: bigint(10), PRIMARY KEY
 *   - agent_id: varchar(50), 에이전트 식별자
 *   - template_key: varchar(100), 템플릿 키
 *   - template_content: text, 템플릿 내용
 *   - persona_id: varchar(50), 페르소나 식별자 (nullable)
 *   - created_at: datetime
 *   - updated_at: datetime
 *
 * - mdl_at_persona_responses (페르소나별 응답 설정)
 *   - id: bigint(10), PRIMARY KEY
 *   - persona_id: varchar(50), 페르소나 식별자
 *   - tone_style: varchar(50), 톤 스타일
 *   - intervention_pattern: varchar(50), 개입 패턴
 *   - is_active: tinyint(1), 활성화 여부
 */
