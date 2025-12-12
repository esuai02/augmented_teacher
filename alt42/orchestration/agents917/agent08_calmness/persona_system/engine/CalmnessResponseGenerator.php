<?php
/**
 * CalmnessResponseGenerator - Agent08 침착성 특화 응답 생성기
 *
 * BaseResponseGenerator를 확장하여 침착성 레벨에 맞는 응답을 생성합니다.
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseResponseGenerator.php');

class CalmnessResponseGenerator extends BaseResponseGenerator {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    protected $agentId = 'agent08';

    /** @var array 침착성 레벨별 기본 템플릿 */
    private $calmnessTemplates = [];

    /** @var array 호흡 운동 가이드 */
    private $breathingGuides = [];

    /** @var array 그라운딩 운동 가이드 */
    private $groundingGuides = [];

    /**
     * 생성자
     *
     * @param string $templateDir 템플릿 디렉토리
     */
    public function __construct(string $templateDir = null) {
        parent::__construct($templateDir ?? __DIR__ . '/../templates');
        $this->initCalmnessTemplates();
        $this->initBreathingGuides();
        $this->initGroundingGuides();
        $this->registerCalmnessInterventions();
    }

    /**
     * 침착성 레벨별 기본 템플릿 초기화
     */
    private function initCalmnessTemplates(): void {
        $this->calmnessTemplates = [
            'C95' => [
                'greeting' => '안녕하세요! 오늘 집중력이 좋아 보이네요.',
                'acknowledgment' => '네, 잘 이해했어요.',
                'encouragement' => '지금 상태가 아주 좋아요. 이 컨디션을 유지하면서 해봐요!',
                'closing' => '궁금한 점이 있으면 언제든 물어보세요.'
            ],
            'C90' => [
                'greeting' => '안녕하세요! 함께 학습을 시작해 볼까요?',
                'acknowledgment' => '네, 말씀 잘 들었어요.',
                'encouragement' => '잘하고 있어요. 차근차근 해나가 봐요.',
                'closing' => '오늘도 좋은 시간 되길 바라요.'
            ],
            'C85' => [
                'greeting' => '안녕하세요. 오늘 어떻게 도와드릴까요?',
                'acknowledgment' => '이해했습니다.',
                'encouragement' => '함께 천천히 풀어나가 봐요.',
                'closing' => '편하게 질문해 주세요.'
            ],
            'C80' => [
                'greeting' => '안녕하세요. 오늘 기분은 어떠세요?',
                'acknowledgment' => '그런 마음이 드는 게 당연해요.',
                'encouragement' => '급하지 않아요. 천천히 해도 괜찮아요.',
                'closing' => '잠시 쉬어가며 해도 돼요.'
            ],
            'C75' => [
                'greeting' => '안녕하세요. 제가 함께할게요.',
                'acknowledgment' => '힘든 마음, 충분히 이해해요.',
                'encouragement' => '지금 느끼는 감정은 자연스러운 거예요. 천천히 함께해요.',
                'closing' => '언제든 이야기해도 괜찮아요.'
            ],
            'C_crisis' => [
                'greeting' => '안녕하세요. 제가 옆에 있을게요.',
                'acknowledgment' => '많이 힘드시죠. 충분히 이해해요.',
                'encouragement' => '지금 이 순간, 깊게 숨 한 번 쉬어봐요.',
                'closing' => '함께 천천히 이 순간을 지나가 봐요.'
            ]
        ];
    }

    /**
     * 호흡 운동 가이드 초기화
     */
    private function initBreathingGuides(): void {
        $this->breathingGuides = [
            '4-7-8' => [
                'name' => '4-7-8 호흡법',
                'steps' => [
                    '1. 편안하게 앉거나 누워요.',
                    '2. 4초 동안 코로 천천히 숨을 들이쉬어요.',
                    '3. 7초 동안 숨을 참아요.',
                    '4. 8초 동안 입으로 천천히 내쉬어요.',
                    '5. 3-4회 반복해 보세요.'
                ],
                'duration' => '2-3분',
                'effectiveness' => '불안과 긴장 완화에 효과적'
            ],
            'box' => [
                'name' => '박스 호흡법',
                'steps' => [
                    '1. 4초 동안 숨을 들이쉬어요.',
                    '2. 4초 동안 숨을 참아요.',
                    '3. 4초 동안 숨을 내쉬어요.',
                    '4. 4초 동안 숨을 참아요.',
                    '5. 4회 반복해 보세요.'
                ],
                'duration' => '2분',
                'effectiveness' => '집중력 향상과 마음 안정에 효과적'
            ],
            'deep' => [
                'name' => '깊은 복식 호흡',
                'steps' => [
                    '1. 한 손을 가슴에, 다른 손을 배에 올려요.',
                    '2. 코로 천천히 숨을 들이쉬며 배가 부풀어 오르게 해요.',
                    '3. 입으로 천천히 내쉬며 배가 들어가게 해요.',
                    '4. 가슴은 가능한 움직이지 않게 해요.',
                    '5. 5-10회 반복해요.'
                ],
                'duration' => '3-5분',
                'effectiveness' => '전반적인 긴장 완화에 효과적'
            ]
        ];
    }

    /**
     * 그라운딩 운동 가이드 초기화
     */
    private function initGroundingGuides(): void {
        $this->groundingGuides = [
            '5-4-3-2-1' => [
                'name' => '5-4-3-2-1 감각 집중',
                'steps' => [
                    '1. 지금 보이는 것 5가지를 찾아보세요.',
                    '2. 지금 만질 수 있는 것 4가지를 찾아보세요.',
                    '3. 지금 들리는 소리 3가지를 찾아보세요.',
                    '4. 지금 맡을 수 있는 냄새 2가지를 찾아보세요.',
                    '5. 지금 느낄 수 있는 맛 1가지를 찾아보세요.'
                ],
                'duration' => '3-5분',
                'effectiveness' => '현재 순간에 집중하여 불안 완화'
            ],
            'body_scan' => [
                'name' => '바디 스캔',
                'steps' => [
                    '1. 발끝부터 시작해요.',
                    '2. 천천히 발, 종아리, 무릎... 순서로 올라가요.',
                    '3. 각 부위의 감각을 느껴보세요.',
                    '4. 긴장된 부위가 있으면 의식적으로 이완해요.',
                    '5. 머리 끝까지 스캔해요.'
                ],
                'duration' => '5-10분',
                'effectiveness' => '신체 긴장 인식 및 이완'
            ],
            'safe_place' => [
                'name' => '안전한 장소 상상',
                'steps' => [
                    '1. 눈을 감고 편안한 자세를 취해요.',
                    '2. 가장 안전하고 편안한 장소를 떠올려요.',
                    '3. 그곳의 색깔, 소리, 향기를 상상해요.',
                    '4. 그곳에서 느끼는 평온함을 온몸으로 느껴요.',
                    '5. 천천히 현재로 돌아와요.'
                ],
                'duration' => '3-5분',
                'effectiveness' => '심리적 안정감 제공'
            ]
        ];
    }

    /**
     * 침착성 특화 개입 패턴 등록
     */
    private function registerCalmnessInterventions(): void {
        // Agent08 전용 개입 패턴 추가
        $this->addInterventionPattern('FocusGuidance', [
            'structure' => ['attention', 'simplify', 'step_by_step', 'encouragement'],
            'focus' => 'concentration',
            'pace' => 'normal',
            'question_style' => 'guided'
        ]);

        $this->addInterventionPattern('CalmnessCoaching', [
            'structure' => ['acknowledge_feeling', 'normalize', 'breathing', 'next_step'],
            'focus' => 'emotional_regulation',
            'pace' => 'slow',
            'question_style' => 'supportive'
        ]);

        $this->addInterventionPattern('MindfulnessSupport', [
            'structure' => ['present_moment', 'observation', 'acceptance', 'gentle_action'],
            'focus' => 'awareness',
            'pace' => 'very_slow',
            'question_style' => 'mindful'
        ]);

        $this->addInterventionPattern('CrisisIntervention', [
            'structure' => ['safety', 'stabilize', 'breathing', 'ground', 'connect'],
            'focus' => 'immediate_safety',
            'pace' => 'very_slow',
            'question_style' => 'direct_supportive'
        ]);
    }

    /**
     * 침착성 기반 응답 생성
     *
     * @param array $identification 페르소나 식별 결과
     * @param array $context 컨텍스트
     * @param string $templateKey 템플릿 키
     * @return string 생성된 응답
     */
    public function generate(array $identification, array $context, string $templateKey = 'default'): string {
        $calmnessLevel = $identification['calmness_level'] ?? 'C85';
        $tone = $identification['tone'] ?? 'Professional';
        $intervention = $identification['intervention'] ?? 'InformationProvision';

        // 템플릿 로드
        $template = $this->loadCalmnessTemplate($calmnessLevel, $templateKey);

        if (!$template) {
            $template = $this->getDefaultCalmnessTemplate($calmnessLevel, $templateKey);
        }

        // 변수 치환
        $variables = $this->prepareCalmnessVariables($identification, $context);
        $response = $this->replaceVariables($template, $variables);

        // 톤 스타일 적용
        $response = $this->applyTone($response, $tone);

        // 개입 패턴 적용
        $response = $this->applyIntervention($response, $intervention);

        // 침착성 레벨에 따른 추가 조정
        $response = $this->adjustForCalmnessLevel($response, $calmnessLevel);

        return $response;
    }

    /**
     * 침착성 템플릿 로드
     *
     * @param string $calmnessLevel 침착성 레벨
     * @param string $templateKey 템플릿 키
     * @return string|null 템플릿
     */
    private function loadCalmnessTemplate(string $calmnessLevel, string $templateKey): ?string {
        $paths = [
            "{$this->templateDir}/{$calmnessLevel}/{$templateKey}.txt",
            "{$this->templateDir}/default/{$templateKey}.txt"
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return file_get_contents($path);
            }
        }

        return null;
    }

    /**
     * 기본 침착성 템플릿 생성
     *
     * @param string $calmnessLevel 침착성 레벨
     * @param string $templateKey 템플릿 키
     * @return string 템플릿
     */
    private function getDefaultCalmnessTemplate(string $calmnessLevel, string $templateKey): string {
        $templates = $this->calmnessTemplates[$calmnessLevel] ?? $this->calmnessTemplates['C85'];

        $defaultTemplates = [
            'optimal_state' => "{$templates['greeting']}\n\n" .
                "현재 매우 안정된 상태예요. {$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'good_state' => "{$templates['greeting']}\n\n" .
                "{$templates['acknowledgment']} {$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'moderate_state' => "{$templates['greeting']}\n\n" .
                "{{student_name}}님, {$templates['acknowledgment']}\n" .
                "{$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'mild_anxiety_support' => "{$templates['greeting']}\n\n" .
                "{{student_name}}님, {$templates['acknowledgment']}\n" .
                "잠시 깊게 숨을 쉬어볼까요?\n\n" .
                "{$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'moderate_anxiety_support' => "{$templates['greeting']}\n\n" .
                "{{student_name}}님, {$templates['acknowledgment']}\n\n" .
                "[BREATHING_GUIDE]\n\n" .
                "{$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'crisis_support' => "{$templates['greeting']}\n\n" .
                "{{student_name}}님, {$templates['acknowledgment']}\n\n" .
                "[BREATHING_GUIDE]\n\n" .
                "[GROUNDING_GUIDE]\n\n" .
                "{$templates['encouragement']}\n\n" .
                "{$templates['closing']}",

            'breathing_exercise' => "잠시 호흡에 집중해 볼까요?\n\n[BREATHING_GUIDE]\n\n" .
                "어떠세요? 조금 나아졌나요?",

            'grounding_exercise' => "지금 이 순간에 집중해 볼게요.\n\n[GROUNDING_GUIDE]\n\n" .
                "천천히, 하나씩 해봐요.",

            'immediate_support' => "{{student_name}}님, 제가 여기 있어요.\n\n" .
                "지금 가장 중요한 건 숨을 쉬는 거예요.\n\n" .
                "[BREATHING_GUIDE]\n\n" .
                "함께 있을게요.",

            'default' => "{$templates['greeting']}\n\n" .
                "{{student_name}}님, {$templates['acknowledgment']}\n" .
                "{$templates['encouragement']}\n\n" .
                "{$templates['closing']}"
        ];

        return $defaultTemplates[$templateKey] ?? $defaultTemplates['default'];
    }

    /**
     * 침착성 관련 변수 준비
     *
     * @param array $identification 식별 결과
     * @param array $context 컨텍스트
     * @return array 변수
     */
    private function prepareCalmnessVariables(array $identification, array $context): array {
        return [
            'student_name' => $context['student_name'] ?? '학생',
            'calmness_level' => $identification['calmness_level'],
            'calmness_score' => $identification['calmness_score'],
            'situation' => $context['situation'] ?? '',
            'date' => date('Y년 m월 d일'),
            'time' => date('H:i'),
            'BREATHING_GUIDE' => $this->getBreathingGuideText('4-7-8'),
            'GROUNDING_GUIDE' => $this->getGroundingGuideText('5-4-3-2-1')
        ];
    }

    /**
     * 변수 치환
     *
     * @param string $template 템플릿
     * @param array $variables 변수
     * @return string 치환된 텍스트
     */
    private function replaceVariables(string $template, array $variables): string {
        foreach ($variables as $key => $value) {
            // {{변수명}} 형식
            $template = str_replace("{{{$key}}}", (string) $value, $template);
            // [VARIABLE] 형식
            $template = str_replace("[{$key}]", (string) $value, $template);
        }

        return trim($template);
    }

    /**
     * 침착성 레벨에 따른 응답 조정
     *
     * @param string $response 응답
     * @param string $calmnessLevel 침착성 레벨
     * @return string 조정된 응답
     */
    private function adjustForCalmnessLevel(string $response, string $calmnessLevel): string {
        switch ($calmnessLevel) {
            case 'C_crisis':
            case 'C75':
                // 문장 사이에 더 많은 공백, 짧은 문장
                $response = preg_replace('/(\. )/', ".\n\n", $response);
                $response = $this->shortenSentences($response);
                break;

            case 'C80':
                // 약간의 여유 공간
                $response = preg_replace('/(\. )/', ". \n", $response);
                break;

            default:
                // 일반적인 형식 유지
                break;
        }

        return $response;
    }

    /**
     * 문장 짧게 만들기
     *
     * @param string $text 텍스트
     * @return string 짧아진 텍스트
     */
    private function shortenSentences(string $text): string {
        // 20자 이상의 문장에서 쉼표 위치에서 줄바꿈
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            if (mb_strlen($line) > 30 && strpos($line, ',') !== false) {
                $line = str_replace(', ', ".\n", $line);
            }
            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * 호흡 가이드 텍스트 반환
     *
     * @param string $type 가이드 타입
     * @return string 가이드 텍스트
     */
    public function getBreathingGuideText(string $type = '4-7-8'): string {
        $guide = $this->breathingGuides[$type] ?? $this->breathingGuides['4-7-8'];

        $text = "🌬️ **{$guide['name']}**\n\n";
        $text .= implode("\n", $guide['steps']) . "\n\n";
        $text .= "⏱️ 소요 시간: {$guide['duration']}";

        return $text;
    }

    /**
     * 그라운딩 가이드 텍스트 반환
     *
     * @param string $type 가이드 타입
     * @return string 가이드 텍스트
     */
    public function getGroundingGuideText(string $type = '5-4-3-2-1'): string {
        $guide = $this->groundingGuides[$type] ?? $this->groundingGuides['5-4-3-2-1'];

        $text = "🌿 **{$guide['name']}**\n\n";
        $text .= implode("\n", $guide['steps']) . "\n\n";
        $text .= "⏱️ 소요 시간: {$guide['duration']}";

        return $text;
    }

    /**
     * 호흡 가이드 목록 반환
     *
     * @return array 가이드 목록
     */
    public function getBreathingGuides(): array {
        return $this->breathingGuides;
    }

    /**
     * 그라운딩 가이드 목록 반환
     *
     * @return array 가이드 목록
     */
    public function getGroundingGuides(): array {
        return $this->groundingGuides;
    }

    /**
     * 침착성 레벨별 템플릿 반환
     *
     * @return array 템플릿 목록
     */
    public function getCalmnessTemplates(): array {
        return $this->calmnessTemplates;
    }
}

/*
 * CalmnessResponseGenerator v1.0
 *
 * 주요 기능:
 * - 침착성 레벨별 맞춤 응답 생성
 * - 호흡 운동 가이드 (4-7-8, 박스, 복식)
 * - 그라운딩 운동 가이드 (5-4-3-2-1, 바디스캔, 안전한 장소)
 * - Agent08 전용 개입 패턴 (FocusGuidance, CalmnessCoaching, MindfulnessSupport, CrisisIntervention)
 *
 * 템플릿 디렉토리 구조:
 * templates/
 * ├── default/
 * │   └── default.txt
 * ├── C95/
 * │   └── optimal_state.txt
 * ├── C90/
 * │   └── good_state.txt
 * ├── C85/
 * │   └── moderate_state.txt
 * ├── C80/
 * │   └── mild_anxiety_support.txt
 * ├── C75/
 * │   └── moderate_anxiety_support.txt
 * └── C_crisis/
 *     └── crisis_support.txt
 */
