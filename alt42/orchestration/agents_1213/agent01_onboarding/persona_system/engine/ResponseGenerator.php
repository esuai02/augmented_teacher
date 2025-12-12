<?php
/**
 * ResponseGenerator - 페르소나 기반 응답 생성기
 *
 * 식별된 페르소나에 맞는 동적 응답을 생성합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

class ResponseGenerator {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var string 템플릿 디렉토리 */
    private $templateDir;

    /** @var array 로드된 템플릿 캐시 */
    private $templateCache = [];

    /** @var array 톤별 언어 스타일 */
    private $toneStyles = [];

    /** @var array 개입 유형별 응답 패턴 */
    private $interventionPatterns = [];

    /**
     * 생성자
     *
     * @param string $templateDir 템플릿 디렉토리 경로
     */
    public function __construct(string $templateDir = null) {
        $this->templateDir = $templateDir ?? __DIR__ . '/../templates';
        $this->initToneStyles();
        $this->initInterventionPatterns();
    }

    /**
     * 톤별 언어 스타일 초기화
     */
    private function initToneStyles(): void {
        $this->toneStyles = [
            'Professional' => [
                'honorific' => '~입니다',
                'greeting' => '안녕하세요',
                'acknowledgment' => '말씀 잘 들었습니다',
                'encouragement' => '함께 해결해 나가겠습니다',
                'closing' => '다음 단계를 안내해 드리겠습니다',
                'emoji_level' => 0,
                'formality' => 'formal'
            ],
            'Warm' => [
                'honorific' => '~해요',
                'greeting' => '반가워요',
                'acknowledgment' => '그렇군요, 이해해요',
                'encouragement' => '함께 차근차근 해봐요',
                'closing' => '언제든 물어봐도 돼요',
                'emoji_level' => 1,
                'formality' => 'semi_formal'
            ],
            'Encouraging' => [
                'honorific' => '~해요',
                'greeting' => '잘 왔어요!',
                'acknowledgment' => '정말 잘하고 있어요',
                'encouragement' => '할 수 있어요! 믿어요',
                'closing' => '조금씩 성장하고 있어요',
                'emoji_level' => 2,
                'formality' => 'casual'
            ],
            'Calm' => [
                'honorific' => '~해요',
                'greeting' => '천천히 이야기해 봐요',
                'acknowledgment' => '괜찮아요, 들을게요',
                'encouragement' => '급하지 않아요, 천천히요',
                'closing' => '충분히 시간을 가져도 돼요',
                'emoji_level' => 0,
                'formality' => 'soft'
            ],
            'Empathetic' => [
                'honorific' => '~네요',
                'greeting' => '오늘 기분은 어때요?',
                'acknowledgment' => '그런 마음이 드는 게 당연해요',
                'encouragement' => '함께 있어 줄게요',
                'closing' => '언제든 이야기해 줘요',
                'emoji_level' => 1,
                'formality' => 'empathetic'
            ],
            'Direct' => [
                'honorific' => '~입니다',
                'greeting' => '시작하겠습니다',
                'acknowledgment' => '확인했습니다',
                'encouragement' => '집중해서 진행합시다',
                'closing' => '다음으로 넘어가겠습니다',
                'emoji_level' => 0,
                'formality' => 'direct'
            ],
            'Playful' => [
                'honorific' => '~야/~이야',
                'greeting' => '안녕! 오늘도 화이팅!',
                'acknowledgment' => '오! 그렇구나~',
                'encouragement' => '대박! 잘하고 있어!',
                'closing' => '다음에 또 만나자!',
                'emoji_level' => 3,
                'formality' => 'playful'
            ]
        ];
    }

    /**
     * 개입 유형별 응답 패턴 초기화
     */
    private function initInterventionPatterns(): void {
        $this->interventionPatterns = [
            'EmotionalSupport' => [
                'structure' => ['empathy', 'validation', 'support', 'next_step'],
                'focus' => 'feelings',
                'pace' => 'slow',
                'question_style' => 'open'
            ],
            'InformationProvision' => [
                'structure' => ['context', 'explanation', 'example', 'check_understanding'],
                'focus' => 'knowledge',
                'pace' => 'normal',
                'question_style' => 'closed'
            ],
            'SkillBuilding' => [
                'structure' => ['concept', 'demonstration', 'practice', 'feedback'],
                'focus' => 'competence',
                'pace' => 'adaptive',
                'question_style' => 'guided'
            ],
            'BehaviorModification' => [
                'structure' => ['observation', 'reflection', 'alternative', 'commitment'],
                'focus' => 'habits',
                'pace' => 'slow',
                'question_style' => 'socratic'
            ],
            'SafetyNet' => [
                'structure' => ['concern', 'resource', 'plan', 'follow_up'],
                'focus' => 'safety',
                'pace' => 'careful',
                'question_style' => 'direct'
            ],
            'PlanDesign' => [
                'structure' => ['goal', 'steps', 'timeline', 'checkpoint'],
                'focus' => 'action',
                'pace' => 'normal',
                'question_style' => 'structured'
            ],
            'AssessmentDesign' => [
                'structure' => ['current_state', 'target', 'gap', 'strategy'],
                'focus' => 'evaluation',
                'pace' => 'methodical',
                'question_style' => 'analytical'
            ],
            'GapAnalysis' => [
                'structure' => ['strength', 'weakness', 'priority', 'action'],
                'focus' => 'improvement',
                'pace' => 'normal',
                'question_style' => 'diagnostic'
            ],
            'GoalSetting' => [
                'structure' => ['aspiration', 'reality', 'options', 'will'],
                'focus' => 'motivation',
                'pace' => 'adaptive',
                'question_style' => 'coaching'
            ],
            'CrisisIntervention' => [
                'structure' => ['stabilize', 'assess', 'connect', 'plan'],
                'focus' => 'immediate_safety',
                'pace' => 'urgent',
                'question_style' => 'direct'
            ]
        ];
    }

    /**
     * 페르소나 기반 응답 생성
     *
     * @param string $personaId 페르소나 ID
     * @param string $templateKey 템플릿 키
     * @param array $variables 치환 변수
     * @param array $options 추가 옵션 (tone, pace, intervention)
     * @return string 생성된 응답
     */
    public function generate(
        string $personaId,
        string $templateKey,
        array $variables = [],
        array $options = []
    ): string {
        // 기본 옵션 설정
        $tone = $options['tone'] ?? 'Professional';
        $intervention = $options['intervention'] ?? 'InformationProvision';
        $pace = $options['pace'] ?? 'normal';

        // 템플릿 로드
        $template = $this->loadTemplate($personaId, $templateKey);

        if (!$template) {
            // 폴백: 기본 템플릿 사용
            $template = $this->loadTemplate('default', $templateKey);
        }

        if (!$template) {
            error_log("[ResponseGenerator] {$this->currentFile}:" . __LINE__ .
                " - 템플릿을 찾을 수 없음: {$personaId}/{$templateKey}");
            return $this->generateFallbackResponse($templateKey, $variables, $tone);
        }

        // 변수 치환
        $response = $this->replaceVariables($template, $variables);

        // 톤 스타일 적용
        $response = $this->applyToneStyle($response, $tone);

        // 개입 패턴 적용
        $response = $this->applyInterventionPattern($response, $intervention);

        // 페이스 조정
        $response = $this->adjustPace($response, $pace);

        return $response;
    }

    /**
     * 템플릿 로드
     *
     * @param string $personaId 페르소나 ID
     * @param string $templateKey 템플릿 키
     * @return string|null 템플릿 내용
     */
    private function loadTemplate(string $personaId, string $templateKey): ?string {
        // 캐시 확인
        $cacheKey = "{$personaId}_{$templateKey}";
        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }

        // 상황 코드 추출 (예: S1_P1 → S1)
        $situation = substr($personaId, 0, 2);

        // 템플릿 파일 경로 시도
        $paths = [
            "{$this->templateDir}/{$situation}/{$personaId}/{$templateKey}.txt",
            "{$this->templateDir}/{$situation}/{$templateKey}.txt",
            "{$this->templateDir}/default/{$templateKey}.txt"
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $this->templateCache[$cacheKey] = $content;
                return $content;
            }
        }

        return null;
    }

    /**
     * 변수 치환
     *
     * @param string $template 템플릿
     * @param array $variables 변수
     * @return string 치환된 템플릿
     */
    private function replaceVariables(string $template, array $variables): string {
        foreach ($variables as $key => $value) {
            // {{변수명}} 형식
            $template = str_replace("{{{$key}}}", (string) $value, $template);
            // %변수명% 형식
            $template = str_replace("%{$key}%", (string) $value, $template);
        }

        // 치환되지 않은 변수 제거 (선택적)
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        $template = preg_replace('/%[^%]+%/', '', $template);

        return trim($template);
    }

    /**
     * 톤 스타일 적용
     *
     * @param string $response 응답
     * @param string $tone 톤
     * @return string 스타일 적용된 응답
     */
    private function applyToneStyle(string $response, string $tone): string {
        if (!isset($this->toneStyles[$tone])) {
            return $response;
        }

        $style = $this->toneStyles[$tone];

        // [GREETING] 등의 플레이스홀더 치환
        $response = str_replace('[GREETING]', $style['greeting'], $response);
        $response = str_replace('[ACKNOWLEDGMENT]', $style['acknowledgment'], $response);
        $response = str_replace('[ENCOURAGEMENT]', $style['encouragement'], $response);
        $response = str_replace('[CLOSING]', $style['closing'], $response);

        // 이모지 레벨 적용
        if ($style['emoji_level'] >= 2) {
            $response = $this->addEmojis($response, 'high');
        } elseif ($style['emoji_level'] >= 1) {
            $response = $this->addEmojis($response, 'low');
        }

        return $response;
    }

    /**
     * 개입 패턴 적용
     *
     * @param string $response 응답
     * @param string $intervention 개입 유형
     * @return string 패턴 적용된 응답
     */
    private function applyInterventionPattern(string $response, string $intervention): string {
        if (!isset($this->interventionPatterns[$intervention])) {
            return $response;
        }

        $pattern = $this->interventionPatterns[$intervention];

        // [STRUCTURE_STEP] 플레이스홀더 처리
        foreach ($pattern['structure'] as $index => $step) {
            $placeholder = "[STEP_" . ($index + 1) . "]";
            // 구조 단계 마커 추가 (필요시)
        }

        return $response;
    }

    /**
     * 페이스 조정
     *
     * @param string $response 응답
     * @param string $pace 페이스
     * @return string 조정된 응답
     */
    private function adjustPace(string $response, string $pace): string {
        switch ($pace) {
            case 'slow':
                // 문장 사이에 더 많은 공백
                $response = preg_replace('/(\. )/', ".\n\n", $response);
                break;
            case 'fast':
                // 간결하게
                $response = preg_replace('/\n{2,}/', "\n", $response);
                break;
            case 'adaptive':
            case 'normal':
            default:
                // 기본 유지
                break;
        }
        return $response;
    }

    /**
     * 이모지 추가
     *
     * @param string $response 응답
     * @param string $level 레벨 (low, high)
     * @return string 이모지 추가된 응답
     */
    private function addEmojis(string $response, string $level): string {
        $emojis = [
            'encouragement' => ['💪', '✨', '🌟', '👏'],
            'empathy' => ['🤗', '💙', '🙏'],
            'success' => ['🎉', '🎊', '✅'],
            'thinking' => ['🤔', '💭', '📚']
        ];

        if ($level === 'high') {
            // 문장 끝에 랜덤 이모지 추가
            $lines = explode("\n", $response);
            foreach ($lines as &$line) {
                if (!empty(trim($line)) && rand(0, 2) === 0) {
                    $category = array_rand($emojis);
                    $emoji = $emojis[$category][array_rand($emojis[$category])];
                    $line = rtrim($line) . ' ' . $emoji;
                }
            }
            $response = implode("\n", $lines);
        }

        return $response;
    }

    /**
     * 폴백 응답 생성
     *
     * @param string $templateKey 템플릿 키
     * @param array $variables 변수
     * @param string $tone 톤
     * @return string 폴백 응답
     */
    private function generateFallbackResponse(string $templateKey, array $variables, string $tone): string {
        $style = $this->toneStyles[$tone] ?? $this->toneStyles['Professional'];

        $fallbacks = [
            'welcome' => "{$style['greeting']}! 만나서 반갑습니다. 오늘 어떤 것을 도와드릴까요?",
            'acknowledgment' => "{$style['acknowledgment']}. {$style['encouragement']}",
            'next_step' => "{$style['closing']}",
            'encouragement' => "{$style['encouragement']}",
            'error' => "죄송합니다. 다시 한 번 말씀해 주시겠어요?"
        ];

        return $fallbacks[$templateKey] ?? $fallbacks['error'];
    }

    /**
     * 상황별 맞춤 응답 생성
     *
     * @param array $identificationResult 페르소나 식별 결과
     * @param string $templateKey 템플릿 키
     * @param array $context 컨텍스트
     * @return string 생성된 응답
     */
    public function generateFromResult(array $identificationResult, string $templateKey, array $context = []): string {
        $personaId = $identificationResult['persona_id'] ?? 'default';
        $tone = $identificationResult['tone'] ?? 'Professional';
        $pace = $identificationResult['pace'] ?? 'normal';
        $intervention = $identificationResult['intervention'] ?? 'InformationProvision';

        // 컨텍스트에서 변수 추출
        $variables = [
            'student_name' => $context['moodle_data']['user']['firstname'] ?? '학생',
            'situation' => $identificationResult['persona_id'] ? substr($identificationResult['persona_id'], 0, 2) : 'S0',
            'confidence' => round(($identificationResult['confidence'] ?? 0.5) * 100) . '%'
        ];

        return $this->generate($personaId, $templateKey, $variables, [
            'tone' => $tone,
            'pace' => $pace,
            'intervention' => $intervention
        ]);
    }

    /**
     * 템플릿 목록 조회
     *
     * @param string $situation 상황 코드 (선택)
     * @return array 템플릿 목록
     */
    public function listTemplates(string $situation = null): array {
        $templates = [];

        if ($situation) {
            $path = "{$this->templateDir}/{$situation}";
        } else {
            $path = $this->templateDir;
        }

        if (!is_dir($path)) {
            return $templates;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'txt') {
                $relativePath = str_replace($this->templateDir . '/', '', $file->getPathname());
                $templates[] = [
                    'path' => $relativePath,
                    'key' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'situation' => explode('/', $relativePath)[0]
                ];
            }
        }

        return $templates;
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
        return array_keys($this->interventionPatterns);
    }
}

/*
 * 지원 톤:
 * - Professional: 공식적, 격식체
 * - Warm: 따뜻한, 반말체
 * - Encouraging: 격려하는, 친근한
 * - Calm: 차분한, 안정적
 * - Empathetic: 공감적, 감정 중심
 * - Direct: 직접적, 간결한
 * - Playful: 장난스러운, 재미있는
 *
 * 지원 개입 유형:
 * - EmotionalSupport: 정서적 지지
 * - InformationProvision: 정보 제공
 * - SkillBuilding: 기술 구축
 * - BehaviorModification: 행동 수정
 * - SafetyNet: 안전망
 * - PlanDesign: 계획 설계
 * - AssessmentDesign: 평가 설계
 * - GapAnalysis: 갭 분석
 * - GoalSetting: 목표 설정
 * - CrisisIntervention: 위기 개입
 *
 * 템플릿 디렉토리 구조:
 * templates/
 * ├── default/
 * │   ├── welcome.txt
 * │   ├── acknowledgment.txt
 * │   └── ...
 * ├── S0/
 * │   ├── S0_P1/
 * │   │   └── assessment_intro.txt
 * │   └── ...
 * ├── S1/
 * │   └── ...
 * └── ...
 */
