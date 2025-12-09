<?php
/**
 * TemplateResponseGenerator - 템플릿 기반 응답 생성기 구현
 *
 * IResponseGenerator 인터페이스의 템플릿 기반 구현체
 * 미리 정의된 템플릿과 변수 치환으로 응답 생성
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @author Claude Code
 */

require_once(__DIR__ . '/../core/IResponseGenerator.php');

class TemplateResponseGenerator implements IResponseGenerator {

    /** @var array 로드된 템플릿 */
    private $templates = [];

    /** @var array 기본 응답 */
    private $defaultResponses = [];

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 톤별 어미/표현 */
    private $toneModifiers = [
        'Professional' => [
            'suffix' => '습니다',
            'greeting' => '안녕하세요',
            'closing' => '도움이 필요하시면 말씀해 주세요.'
        ],
        'Friendly' => [
            'suffix' => '요',
            'greeting' => '안녕!',
            'closing' => '언제든 물어봐!'
        ],
        'Encouraging' => [
            'suffix' => '요',
            'greeting' => '안녕하세요!',
            'closing' => '잘 할 수 있어요! 화이팅!'
        ],
        'Empathetic' => [
            'suffix' => '요',
            'greeting' => '안녕하세요',
            'closing' => '어려우시면 천천히 해도 괜찮아요.'
        ],
        'Supportive' => [
            'suffix' => '요',
            'greeting' => '안녕하세요',
            'closing' => '함께 해결해 나가요.'
        ]
    ];

    /**
     * 생성자
     */
    public function __construct(bool $debugMode = false) {
        $this->debugMode = $debugMode;
        $this->initializeDefaultResponses();
    }

    /**
     * 기본 응답 초기화
     */
    private function initializeDefaultResponses(): void {
        $this->defaultResponses = [
            'neutral' => [
                '네, 말씀해 주세요. 어떻게 도와드릴까요?',
                '무엇을 도와드릴까요?',
                '네, 듣고 있어요.'
            ],
            'anxiety' => [
                '괜찮아요, 천천히 함께 해결해 나가요. 어떤 부분이 걱정되시나요?',
                '걱정하지 마세요. 차근차근 알려드릴게요.',
                '어렵게 느껴지시죠? 제가 도와드릴게요.'
            ],
            'frustration' => [
                '힘드시죠. 잠시 쉬어가면서 해도 괜찮아요.',
                '어려운 부분이 있으시군요. 어디가 막히셨는지 알려주시면 함께 해결해 볼게요.',
                '포기하지 마세요. 조금씩 나아가고 있어요.'
            ],
            'confusion' => [
                '헷갈리실 수 있어요. 다시 한번 설명해 드릴까요?',
                '복잡하게 느껴지시나요? 좀 더 쉽게 설명해 드릴게요.',
                '이해가 안 되는 부분이 있으시면 말씀해 주세요.'
            ],
            'confidence' => [
                '잘하고 계세요! 계속 이렇게 하시면 됩니다.',
                '훌륭해요! 이해를 잘 하고 계시네요.',
                '맞아요, 정확합니다!'
            ],
            'boredom' => [
                '조금 더 재미있는 문제를 찾아볼까요?',
                '다른 방식으로 접근해 볼까요?',
                '새로운 도전을 해보시겠어요?'
            ],
            'joy' => [
                '정말 잘하고 계시네요! 기쁘네요.',
                '즐거우시죠? 이 기세로 계속 가봐요!',
                '훌륭해요! 앞으로도 화이팅!'
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function generate(array $persona, array $context, string $mode = 'template'): array {
        $tone = $persona['tone'] ?? 'Professional';
        $emotion = $context['detected_emotion'] ?? 'neutral';

        try {
            // 템플릿 모드
            if ($mode === 'template' || $mode === 'hybrid') {
                $personaId = $persona['persona_id'] ?? 'default';
                
                // 페르소나별 템플릿 확인
                if (isset($this->templates[$personaId])) {
                    $template = $this->selectTemplate($this->templates[$personaId], $context);
                    $text = $this->renderTemplate($template, $context);
                } else {
                    // 기본 응답 사용
                    $text = $this->getDefaultResponse($emotion);
                }

                // 톤 조정
                $text = $this->adjustTone($text, $tone);

                return [
                    'text' => $text,
                    'tone' => $tone,
                    'source' => 'template',
                    'persona_id' => $personaId,
                    'emotion_detected' => $emotion
                ];
            }

            // AI 모드 (별도 구현 필요)
            if ($mode === 'ai') {
                return $this->generateAIResponse(
                    $context['message'] ?? '',
                    $persona,
                    $context
                );
            }

            // 기본값
            return [
                'text' => $this->getDefaultResponse($emotion),
                'tone' => $tone,
                'source' => 'default'
            ];

        } catch (\Exception $e) {
            error_log("[TemplateResponseGenerator ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 응답 생성 실패: " . $e->getMessage());
            
            return [
                'text' => $this->getDefaultResponse('neutral'),
                'tone' => 'Professional',
                'source' => 'fallback',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function loadTemplates(string $templatePath): bool {
        if (!is_dir($templatePath)) {
            error_log("[TemplateResponseGenerator ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 템플릿 경로를 찾을 수 없습니다: {$templatePath}");
            return false;
        }

        try {
            $files = glob($templatePath . '/*.{php,json,yaml}', GLOB_BRACE);
            
            foreach ($files as $file) {
                $personaId = pathinfo($file, PATHINFO_FILENAME);
                $extension = pathinfo($file, PATHINFO_EXTENSION);

                if ($extension === 'json') {
                    $this->templates[$personaId] = json_decode(file_get_contents($file), true);
                } elseif ($extension === 'php') {
                    $this->templates[$personaId] = include $file;
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("[TemplateResponseGenerator ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 템플릿 로드 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function renderTemplate(string $template, array $variables): string {
        // {{variable}} 형식의 변수 치환
        return preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function($matches) use ($variables) {
            $key = $matches[1];
            return $this->getNestedValue($variables, $key, $matches[0]);
        }, $template);
    }

    /**
     * @inheritDoc
     */
    public function generateAIResponse(string $message, array $persona, array $context): array {
        // AI 응답은 AIGateway를 통해 처리되어야 함
        // 여기서는 폴백 응답 반환
        return [
            'text' => $this->getDefaultResponse($context['detected_emotion'] ?? 'neutral'),
            'model' => 'none',
            'tokens' => 0,
            'source' => 'fallback'
        ];
    }

    /**
     * @inheritDoc
     */
    public function adjustTone(string $text, string $tone): string {
        if (!isset($this->toneModifiers[$tone])) {
            return $text;
        }

        $modifiers = $this->toneModifiers[$tone];

        // 간단한 톤 조정 (실제로는 더 복잡한 NLP 필요)
        // 여기서는 기본적인 어미 변경만 수행
        
        return $text;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultResponse(string $emotion = 'neutral'): string {
        $responses = $this->defaultResponses[$emotion] ?? $this->defaultResponses['neutral'];
        return $responses[array_rand($responses)];
    }

    /**
     * 템플릿 선택 (컨텍스트 기반)
     *
     * @param array $templates 템플릿 배열
     * @param array $context 컨텍스트
     * @return string 선택된 템플릿
     */
    private function selectTemplate(array $templates, array $context): string {
        // 감정 기반 선택
        $emotion = $context['detected_emotion'] ?? 'neutral';
        if (isset($templates[$emotion])) {
            $options = $templates[$emotion];
            return is_array($options) ? $options[array_rand($options)] : $options;
        }

        // 기본 템플릿
        if (isset($templates['default'])) {
            $options = $templates['default'];
            return is_array($options) ? $options[array_rand($options)] : $options;
        }

        return $this->getDefaultResponse($emotion);
    }

    /**
     * 중첩 값 가져오기
     */
    private function getNestedValue(array $data, string $key, $default = null) {
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 커스텀 기본 응답 추가
     *
     * @param string $emotion 감정
     * @param array $responses 응답 배열
     */
    public function addDefaultResponses(string $emotion, array $responses): void {
        $this->defaultResponses[$emotion] = $responses;
    }

    /**
     * 커스텀 톤 수정자 추가
     *
     * @param string $tone 톤 이름
     * @param array $modifiers 수정자 배열
     */
    public function addToneModifier(string $tone, array $modifiers): void {
        $this->toneModifiers[$tone] = $modifiers;
    }
}

/*
 * 관련 DB 테이블: 없음
 *
 * 참조 파일:
 * - core/IResponseGenerator.php (인터페이스)
 * - agents/agent01_onboarding/persona_system/templates/ (템플릿 예시)
 */
