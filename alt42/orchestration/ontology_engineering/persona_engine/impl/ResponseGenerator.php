<?php
/**
 * ResponseGenerator - 응답 생성기 구현
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IResponseGenerator.php');

class ResponseGenerator implements IResponseGenerator {

    /** @var array 응답 템플릿 */
    private $templates = [];

    /** @var array 톤 수정자 */
    private $toneModifiers = [];

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 생성자 - 기본 템플릿과 톤 설정
     */
    public function __construct() {
        $this->initializeDefaultTemplates();
        $this->initializeToneModifiers();
    }

    /**
     * 기본 템플릿 초기화
     */
    private function initializeDefaultTemplates(): void {
        $this->templates = [
            'greeting' => "안녕하세요, {name}님! {message}",
            'encouragement' => "{name}님, 잘하고 계세요! {message}",
            'support' => "괜찮아요, {name}님. {message}",
            'information' => "{message}",
            'question' => "{name}님, {message}",
            'guidance' => "{name}님, 다음과 같이 해보세요: {message}",
            'confirmation' => "네, {name}님. {message}",
            'default' => "{message}"
        ];
    }

    /**
     * 톤 수정자 초기화
     */
    private function initializeToneModifiers(): void {
        $this->toneModifiers = [
            'Professional' => [
                'prefix' => '',
                'suffix' => '',
                'style' => 'formal'
            ],
            'Friendly' => [
                'prefix' => '',
                'suffix' => ' 😊',
                'style' => 'casual'
            ],
            'Supportive' => [
                'prefix' => '',
                'suffix' => ' 화이팅!',
                'style' => 'encouraging'
            ],
            'Directive' => [
                'prefix' => '',
                'suffix' => '',
                'style' => 'direct'
            ],
            'Motivational' => [
                'prefix' => '',
                'suffix' => ' 할 수 있어요!',
                'style' => 'inspiring'
            ]
        ];
    }

    /**
     * 응답 생성
     */
    public function generate(array $identification, array $context): array {
        $personaId = $identification['persona_id'] ?? 'default';
        $tone = $identification['tone'] ?? 'Professional';
        $intervention = $identification['intervention'] ?? 'InformationProvision';
        $emotion = $identification['detected_emotion'] ?? 'neutral';

        // 감정 기반 기본 응답 선택
        $baseResponse = $this->getEmotionBasedResponse($emotion, $context);

        // 톤 적용
        $responseText = $this->applyTone($baseResponse, $tone);

        // 개입 전략 적용
        $responseText = $this->applyIntervention($responseText, $intervention);

        // 변수 치환
        $responseText = $this->substituteVariables($responseText, $context);

        return [
            'text' => $responseText,
            'tone' => $tone,
            'intervention' => $intervention,
            'persona_id' => $personaId,
            'source' => 'local',
            'confidence' => $identification['confidence'] ?? 0.5
        ];
    }

    /**
     * 감정 기반 기본 응답 생성
     */
    private function getEmotionBasedResponse(string $emotion, array $context): string {
        $name = $context['firstname'] ?? '학생';

        $responses = [
            'anxiety' => [
                "{$name}님, 불안한 마음이 느껴지네요. 천천히 함께 해결해 나가요.",
                "괜찮아요, {$name}님. 어려운 부분이 있으면 말씀해 주세요.",
                "{$name}님, 걱정하지 마세요. 하나씩 차근차근 해볼까요?"
            ],
            'frustration' => [
                "{$name}님, 답답한 마음 이해해요. 어떤 부분이 가장 어려우신가요?",
                "힘들어하시는 게 느껴져요. 다른 방법으로 접근해 볼까요?",
                "{$name}님, 잠시 쉬었다가 다시 도전해 보는 건 어떨까요?"
            ],
            'confusion' => [
                "{$name}님, 헷갈리는 부분을 자세히 설명해 드릴게요.",
                "이해가 어려우신 것 같아요. 다시 한 번 설명해 드릴까요?",
                "{$name}님, 어떤 부분이 헷갈리시는지 말씀해 주세요."
            ],
            'joy' => [
                "좋아요, {$name}님! 기분이 좋으시네요!",
                "{$name}님, 즐거워하시니 저도 기뻐요!",
                "와, 정말 잘하셨어요! {$name}님!"
            ],
            'curiosity' => [
                "좋은 질문이에요, {$name}님! 함께 알아볼까요?",
                "{$name}님의 호기심이 느껴져요. 자세히 알아보겠습니다.",
                "궁금한 점이 있으시군요! 설명해 드릴게요."
            ],
            'neutral' => [
                "네, {$name}님. 어떻게 도와드릴까요?",
                "{$name}님, 무엇을 도와드릴까요?",
                "말씀해 주세요, {$name}님."
            ]
        ];

        $emotionResponses = $responses[$emotion] ?? $responses['neutral'];
        return $emotionResponses[array_rand($emotionResponses)];
    }

    /**
     * 템플릿 기반 응답 생성
     */
    public function fromTemplate(string $templateId, array $variables): string {
        $template = $this->templates[$templateId] ?? $this->templates['default'];
        return $this->substituteVariables($template, $variables);
    }

    /**
     * 응답 톤 적용
     */
    public function applyTone(string $text, string $tone): string {
        $modifier = $this->toneModifiers[$tone] ?? $this->toneModifiers['Professional'];

        $result = $modifier['prefix'] . $text . $modifier['suffix'];

        return trim($result);
    }

    /**
     * 개입 전략 적용
     */
    public function applyIntervention(string $text, string $intervention): string {
        switch ($intervention) {
            case 'EmotionalSupport':
                // 감정적 지지 강화
                if (strpos($text, '괜찮') === false) {
                    $text = "먼저, 괜찮아요. " . $text;
                }
                break;

            case 'MotivationalEncouragement':
                // 동기 부여 강화
                if (strpos($text, '할 수') === false && strpos($text, '잘') === false) {
                    $text .= " 분명 잘 해내실 수 있어요!";
                }
                break;

            case 'CognitiveScaffolding':
                // 인지적 비계 제공
                $text .= " 단계별로 함께 해볼까요?";
                break;

            case 'BehavioralGuidance':
                // 행동 안내 추가
                $text = "다음과 같이 해보세요: " . $text;
                break;

            case 'InformationProvision':
            default:
                // 기본 정보 제공
                break;
        }

        return $text;
    }

    /**
     * 템플릿 등록
     */
    public function registerTemplate(string $templateId, string $template): void {
        $this->templates[$templateId] = $template;
    }

    /**
     * 톤 수정자 등록
     */
    public function registerToneModifier(string $tone, array $modifier): void {
        $this->toneModifiers[$tone] = $modifier;
    }

    /**
     * 변수 치환
     */
    private function substituteVariables(string $text, array $variables): string {
        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }

        // 치환되지 않은 변수 제거
        $text = preg_replace('/\{[a-zA-Z_]+\}/', '', $text);

        return trim($text);
    }
}

/*
 * 톤 유형:
 * - Professional : 전문적, 객관적
 * - Friendly : 친근한, 격려하는
 * - Supportive : 지지하는, 공감하는
 * - Directive : 지시적, 명확한
 * - Motivational : 동기부여, 고무하는
 *
 * 개입 유형:
 * - InformationProvision : 정보 제공
 * - EmotionalSupport : 정서적 지원
 * - MotivationalEncouragement : 동기 부여
 * - CognitiveScaffolding : 인지적 비계
 * - BehavioralGuidance : 행동 안내
 *
 * 관련 DB 테이블:
 * - mdl_at_response_templates (응답 템플릿) - 선택적
 * - mdl_at_persona_responses (페르소나별 응답 설정) - 선택적
 */
