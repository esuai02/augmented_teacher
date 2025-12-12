<?php
/**
 * Agent17ResponseGenerator - 응답 생성기 Fallback 구현체
 *
 * BaseResponseGenerator가 없을 경우 사용되는 Agent17 전용 응답 생성기
 * 페르소나 기반 응답을 템플릿 또는 AI를 통해 생성합니다.
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine\Fallback
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

// 인터페이스 로드
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IResponseGenerator.php');

use AugmentedTeacher\PersonaEngine\Core\IResponseGenerator;

/**
 * Agent17 전용 응답 생성기
 */
class Agent17ResponseGenerator implements IResponseGenerator {
    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var string 템플릿 디렉토리 경로 */
    protected $templatesPath;

    /** @var array 로드된 템플릿 */
    protected $templates = [];

    /** @var array 상황별 기본 응답 */
    protected $defaultResponses = [
        'R1' => '훌륭해요! 지금 학습 리듬이 아주 좋습니다. 이대로 계속 진행해볼까요?',
        'R2' => '좋은 진행입니다. 혹시 도움이 필요한 부분이 있으신가요?',
        'R3' => '조금 어려운 부분이 있는 것 같네요. 함께 해결해볼까요?',
        'R4' => '제가 옆에서 도와드릴게요. 천천히 하나씩 해봅시다.',
        'R5' => '잠시 쉬어도 괜찮아요. 준비되면 다시 시작해봐요.'
    ];

    /** @var array 감정별 기본 응답 */
    protected $emotionResponses = [
        'neutral' => '무엇을 도와드릴까요?',
        'confused' => '어떤 부분이 헷갈리시나요? 함께 해결해봐요.',
        'frustrated' => '조금 힘드시죠? 천천히 해도 괜찮아요.',
        'positive' => '잘하고 계시네요! 계속해볼까요?',
        'anxious' => '걱정 마세요, 제가 도와드릴게요.'
    ];

    /**
     * 생성자
     *
     * @param string|null $templatesPath 템플릿 디렉토리 경로
     */
    public function __construct(string $templatesPath = null) {
        $this->templatesPath = $templatesPath ?? dirname(__DIR__) . '/templates';
    }

    /**
     * 응답 생성
     *
     * @param array $persona 페르소나 정보
     * @param array $context 컨텍스트 데이터
     * @param string $mode 생성 모드 (template/ai/hybrid)
     * @return array 생성된 응답 ['text', 'tone', 'source']
     */
    public function generate(array $persona, array $context, string $mode = 'template'): array {
        $personaId = $persona['id'] ?? $persona['persona_id'] ?? 'R2_P1';
        $situation = substr($personaId, 0, 2);
        $tone = $persona['tone'] ?? 'Professional';

        $text = '';
        $source = $mode;

        switch ($mode) {
            case 'ai':
                $message = $context['message'] ?? '';
                $aiResult = $this->generateAIResponse($message, $persona, $context);
                $text = $aiResult['text'];
                $source = 'ai';
                break;

            case 'hybrid':
                // 템플릿 기반 + AI 보강
                $text = $this->generateFromTemplate($situation, $context);
                // AI 보강은 실제 구현 시 OpenAI 연동
                $source = 'hybrid';
                break;

            case 'template':
            default:
                $text = $this->generateFromTemplate($situation, $context);
                $source = 'template';
                break;
        }

        // 톤 조정 적용
        $text = $this->adjustTone($text, $tone);

        return [
            'text' => $text,
            'tone' => $tone,
            'source' => $source
        ];
    }

    /**
     * 템플릿 로드
     *
     * @param string $templatePath 템플릿 디렉토리 경로
     * @return bool 로드 성공 여부
     */
    public function loadTemplates(string $templatePath): bool {
        if (!is_dir($templatePath)) {
            error_log("[Agent17ResponseGenerator] {$this->currentFile}:" . __LINE__ .
                " - 템플릿 디렉토리 없음: {$templatePath}");
            return false;
        }

        $files = glob($templatePath . '/*.php');
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->templates[$key] = $file;
        }

        return count($this->templates) > 0;
    }

    /**
     * 템플릿 렌더링
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 치환할 변수들
     * @return string 렌더링된 문자열
     */
    public function renderTemplate(string $template, array $variables): string {
        // 변수 치환 {{variable}}
        $rendered = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $template);

        return $rendered;
    }

    /**
     * AI 응답 생성
     *
     * @param string $message 사용자 메시지
     * @param array $persona 페르소나 정보
     * @param array $context 컨텍스트 데이터
     * @return array AI 응답 ['text', 'model', 'tokens']
     */
    public function generateAIResponse(string $message, array $persona, array $context): array {
        // AI 응답 생성 (실제로는 OpenAI API 호출)
        // 현재는 폴백으로 템플릿 기반 응답 반환
        $situation = $context['situation'] ?? 'R2';
        $emotion = $context['emotional_state'] ?? 'neutral';

        $text = $this->getDefaultResponse($emotion);

        return [
            'text' => $text,
            'model' => 'fallback',
            'tokens' => 0
        ];
    }

    /**
     * 톤 조정
     *
     * @param string $text 원본 텍스트
     * @param string $tone 적용할 톤
     * @return string 톤 조정된 텍스트
     */
    public function adjustTone(string $text, string $tone): string {
        // 톤별 어미/표현 조정
        switch ($tone) {
            case 'Friendly':
                // 친근한 톤: ~요 형태 유지
                break;

            case 'Professional':
                // 전문적 톤: ~습니다 형태
                $text = preg_replace('/해볼까요\?/', '해보시겠습니까?', $text);
                $text = preg_replace('/괜찮아요/', '괜찮습니다', $text);
                break;

            case 'Encouraging':
                // 격려 톤: 긍정적 표현 추가
                if (strpos($text, '!') === false) {
                    $text .= ' 화이팅!';
                }
                break;

            case 'Patient':
                // 인내심 있는 톤: 천천히 강조
                if (strpos($text, '천천히') === false) {
                    $text = str_replace('해봐요', '천천히 해봐요', $text);
                }
                break;

            default:
                // 기본 톤 유지
                break;
        }

        return $text;
    }

    /**
     * 감정별 기본 응답 조회
     *
     * @param string $emotion 감정 상태
     * @return string 기본 응답 텍스트
     */
    public function getDefaultResponse(string $emotion = 'neutral'): string {
        return $this->emotionResponses[$emotion] ?? $this->emotionResponses['neutral'];
    }

    /**
     * 템플릿 기반 응답 생성 (Agent17 확장 기능)
     *
     * @param string $situation 상황 코드 (R1-R5)
     * @param array $context 컨텍스트 데이터
     * @return string 생성된 응답 텍스트
     */
    protected function generateFromTemplate(string $situation, array $context): string {
        $templateKey = $situation . '_default';
        $templateFile = $this->templatesPath . "/default/{$templateKey}.php";

        if (file_exists($templateFile)) {
            ob_start();
            extract($context);
            include $templateFile;
            $content = ob_get_clean();

            if (!empty(trim($content))) {
                return $content;
            }
        }

        // 폴백: 기본 응답 반환
        return $this->defaultResponses[$situation] ?? $this->defaultResponses['R2'];
    }

    /**
     * 결과 기반 응답 생성 (Agent17 확장 기능 - 하위 호환성)
     *
     * @param array $result 처리 결과
     * @param string $templateKey 템플릿 키
     * @param array $context 컨텍스트 데이터
     * @return string 생성된 응답 텍스트
     */
    public function generateFromResult(array $result, string $templateKey, array $context): string {
        $persona = [
            'id' => $result['persona_id'] ?? 'R2_P1',
            'tone' => $result['tone'] ?? 'Professional'
        ];

        $response = $this->generate($persona, $context, 'template');
        return $response['text'];
    }

    /**
     * 상황별 기본 응답 조회
     *
     * @param string $situation 상황 코드 (R1-R5)
     * @return string 기본 응답 텍스트
     */
    public function getSituationResponse(string $situation): string {
        return $this->defaultResponses[$situation] ?? $this->defaultResponses['R2'];
    }
}

/*
 * 관련 인터페이스: IResponseGenerator
 * 위치: /ontology_engineering/persona_engine/core/IResponseGenerator.php
 *
 * 메서드:
 * - generate(array $persona, array $context, string $mode): array
 * - loadTemplates(string $templatePath): bool
 * - renderTemplate(string $template, array $variables): string
 * - generateAIResponse(string $message, array $persona, array $context): array
 * - adjustTone(string $text, string $tone): string
 *
 * Agent17 확장 메서드:
 * - getDefaultResponse(string $emotion): string
 * - generateFromTemplate(string $situation, array $context): string
 * - generateFromResult(array $result, string $templateKey, array $context): string
 * - getSituationResponse(string $situation): string
 *
 * 지원 모드:
 * - template: 템플릿 기반 응답 생성
 * - ai: AI(OpenAI) 기반 응답 생성
 * - hybrid: 템플릿 + AI 혼합 응답 생성
 *
 * 지원 톤:
 * - Friendly: 친근한 톤 (~요 형태)
 * - Professional: 전문적 톤 (~습니다 형태)
 * - Encouraging: 격려 톤 (긍정적 표현 추가)
 * - Patient: 인내심 있는 톤 (천천히 강조)
 */
