<?php
/**
 * Agent05ResponseGenerator - 학습 감정 기반 응답 생성기
 *
 * 감정 상태와 학습 활동에 맞춤화된 응답을 생성하는 클래스
 * BaseResponseGenerator를 확장하여 학습 감정 특화 기능 추가
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\Engine
 * @version 1.0
 * @author Claude Code
 *
 * 응답 생성 모드:
 * - template: 감정/활동별 사전 정의된 템플릿
 * - ai: OpenAI API 활용 동적 생성
 * - hybrid: 템플릿 + AI 보강
 */

namespace AugmentedTeacher\Agent05\PersonaSystem\Engine;

// 베이스 클래스 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/base/BaseResponseGenerator.php');

use AugmentedTeacher\PersonaEngine\Base\BaseResponseGenerator;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class Agent05ResponseGenerator extends BaseResponseGenerator {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 감정별 응답 템플릿 */
    protected $emotionTemplates = [
        'anxiety' => [
            'high' => [
                '걱정하지 마세요, {{name}}님! 차근차근 함께 해결해 나가요.',
                '불안한 마음 이해해요. 하나씩 천천히 살펴볼게요.',
                '괜찮아요, {{name}}님. 어려운 부분이 있으면 제가 도와드릴게요.'
            ],
            'medium' => [
                '조금 걱정되시는 것 같네요. 같이 확인해볼까요?',
                '천천히 진행해도 괜찮아요. 궁금한 점 있으시면 말씀해주세요.',
                '{{name}}님, 긴장하지 마세요. 함께 풀어봐요.'
            ],
            'low' => [
                '살짝 긴장되시나요? 할 수 있어요!',
                '조금 걱정되실 수 있어요. 하지만 잘 해내실 거예요.'
            ]
        ],
        'frustration' => [
            'high' => [
                '많이 답답하시죠? 잠시 쉬었다가 다시 해볼까요?',
                '화가 나실 수 있어요. 다른 방법으로 접근해볼게요.',
                '어려운 부분이네요. 제가 더 쉽게 설명해드릴게요.'
            ],
            'medium' => [
                '조금 막히셨군요. 다른 관점에서 살펴볼까요?',
                '힘드시죠? 한 단계씩 나눠서 해봐요.',
                '이 부분이 헷갈리실 수 있어요. 다시 정리해드릴게요.'
            ],
            'low' => [
                '조금 답답하시죠? 곧 풀릴 거예요.',
                '어려운 문제네요. 같이 생각해봐요.'
            ]
        ],
        'confidence' => [
            'high' => [
                '정말 잘 하고 계세요, {{name}}님! 👏',
                '완벽해요! 이해력이 정말 뛰어나시네요.',
                '대단해요! 이 페이스 그대로 가면 돼요.'
            ],
            'medium' => [
                '좋아요! 잘 따라오고 계시네요.',
                '이해가 잘 되고 있는 것 같아요!',
                '좋은 방향으로 가고 있어요, {{name}}님.'
            ],
            'low' => [
                '조금씩 감이 오시는 것 같아요!',
                '점점 나아지고 있어요.'
            ]
        ],
        'curiosity' => [
            'high' => [
                '좋은 질문이에요! 더 깊이 알아볼까요?',
                '호기심이 넘치시네요! 자세히 설명해드릴게요.',
                '궁금한 게 많으시군요! 하나씩 알아가 봐요.'
            ],
            'medium' => [
                '궁금하신 부분을 설명해드릴게요.',
                '재미있는 부분이죠? 더 알아볼까요?',
                '좋은 관심이에요. 같이 살펴봐요.'
            ],
            'low' => [
                '관심을 가져주셨네요.',
                '이 부분이 궁금하셨군요.'
            ]
        ],
        'boredom' => [
            'high' => [
                '지루하시죠? 좀 더 재미있는 방식으로 해볼까요?',
                '잠시 다른 활동을 해보는 건 어때요?',
                '{{name}}님, 조금 쉬었다가 할까요?'
            ],
            'medium' => [
                '조금 지루하실 수 있어요. 방식을 바꿔볼까요?',
                '새로운 유형으로 넘어가볼까요?',
                '흥미로운 문제로 바꿔볼게요.'
            ],
            'low' => [
                '살짝 반복되는 느낌이죠?',
                '비슷한 유형이지만, 조금 다른 점이 있어요.'
            ]
        ],
        'fatigue' => [
            'high' => [
                '많이 피곤하시네요. 오늘은 여기까지 하고 쉬세요.',
                '충분히 노력하셨어요. 휴식이 필요해 보여요.',
                '{{name}}님, 건강이 제일 중요해요. 쉬어가세요.'
            ],
            'medium' => [
                '조금 쉬었다가 하면 더 잘 될 거예요.',
                '5분 휴식 후에 다시 해볼까요?',
                '잠깐 스트레칭 하고 오세요!'
            ],
            'low' => [
                '살짝 피곤하시죠? 조금만 더 힘내봐요.',
                '거의 다 왔어요. 조금만 더!'
            ]
        ],
        'achievement' => [
            'high' => [
                '축하해요, {{name}}님! 정말 대단해요! 🎉',
                '완벽하게 해내셨어요! 정말 자랑스러워요!',
                '드디어 해냈네요! 노력한 보람이 있죠?'
            ],
            'medium' => [
                '잘하셨어요! 좋은 진전이에요.',
                '성공했네요! 이 조자로 계속 가봐요.',
                '맞았어요! 잘하고 계세요.'
            ],
            'low' => [
                '조금씩 나아지고 있어요.',
                '진전이 있네요. 좋아요!'
            ]
        ],
        'confusion' => [
            'high' => [
                '많이 헷갈리시죠? 처음부터 차근차근 다시 설명해드릴게요.',
                '복잡하죠? 핵심만 간단히 정리해드릴게요.',
                '어디서부터 막히셨는지 같이 찾아볼까요?'
            ],
            'medium' => [
                '조금 헷갈리시는 부분이 있으시군요.',
                '이 부분을 다시 설명해드릴게요.',
                '어떤 부분이 헷갈리시나요?'
            ],
            'low' => [
                '살짝 헷갈리시죠? 다시 정리해볼게요.',
                '이 부분이 조금 복잡하죠.'
            ]
        ],
        'neutral' => [
            'default' => [
                '{{name}}님, 무엇을 도와드릴까요?',
                '어떤 것이 궁금하신가요?',
                '함께 학습해봐요!'
            ]
        ]
    ];

    /** @var array 활동별 응답 접두사 */
    protected $activityPrefixes = [
        'concept_understanding' => [
            '이 개념을 설명해드리자면요, ',
            '간단히 정리하면, ',
            '핵심 포인트는 '
        ],
        'type_learning' => [
            '이 유형의 핵심은 ',
            '이런 문제는 보통 ',
            '유형별로 보면, '
        ],
        'problem_solving' => [
            '이 문제는 ',
            '풀이 방법을 알려드리면, ',
            '단계별로 풀어보면 '
        ],
        'error_note' => [
            '틀린 이유를 살펴보면, ',
            '실수 포인트는 ',
            '이 부분에서 주의할 점은 '
        ],
        'qa' => [
            '질문 주셨군요! ',
            '좋은 질문이에요. ',
            '답변해드릴게요. '
        ],
        'review' => [
            '복습하면서 확인해보면, ',
            '다시 정리하자면, ',
            '기억해야 할 것은 '
        ],
        'pomodoro' => [
            '집중 시간이네요! ',
            '타이머가 돌아가고 있어요. ',
            '휴식 시간이에요. '
        ],
        'home_check' => [
            '오늘 할 것을 확인해볼게요. ',
            '과제 현황은 ',
            '체크리스트를 보면 '
        ]
    ];

    /** @var array 페르소나별 톤 조정 */
    protected $personaToneAdjustments = [
        '정리형' => ['formal' => 0.7, 'structured' => 0.9, 'detailed' => 0.8],
        '반복형' => ['patient' => 0.9, 'repetitive' => 0.8, 'encouraging' => 0.7],
        '탐색형' => ['curious' => 0.8, 'expansive' => 0.7, 'engaging' => 0.8],
        '저항형' => ['gentle' => 0.9, 'non_confrontational' => 0.8, 'motivational' => 0.7],
        '도전형' => ['encouraging' => 0.9, 'progressive' => 0.8, 'ambitious' => 0.7],
        '보조형' => ['supportive' => 0.9, 'patient' => 0.8, 'step_by_step' => 0.9],
        '완벽형' => ['precise' => 0.9, 'thorough' => 0.8, 'validating' => 0.7],
        '회피형' => ['gentle' => 0.9, 'encouraging' => 0.8, 'low_pressure' => 0.9],
        '분석형' => ['logical' => 0.9, 'structured' => 0.8, 'detailed' => 0.8],
        '직관형' => ['intuitive' => 0.8, 'visual' => 0.7, 'analogical' => 0.8],
        '체계형' => ['systematic' => 0.9, 'organized' => 0.9, 'methodical' => 0.8],
        '적극형' => ['responsive' => 0.9, 'interactive' => 0.8, 'proactive' => 0.7],
        '관찰형' => ['observant' => 0.8, 'patient' => 0.7, 'insightful' => 0.8],
        '의존형' => ['supportive' => 0.9, 'guiding' => 0.9, 'reassuring' => 0.8],
        '독립형' => ['respectful' => 0.8, 'minimal' => 0.7, 'resource_oriented' => 0.8],
        '계획형' => ['organized' => 0.9, 'goal_oriented' => 0.8, 'structured' => 0.8],
        '즉흥형' => ['flexible' => 0.8, 'adaptable' => 0.8, 'spontaneous' => 0.7],
        '몰입형' => ['focused' => 0.9, 'minimal_interruption' => 0.8, 'flow_supporting' => 0.8],
        '산만형' => ['refocusing' => 0.8, 'engaging' => 0.9, 'varied' => 0.7],
        '균형형' => ['balanced' => 0.9, 'moderate' => 0.8, 'steady' => 0.8],
        '과몰입형' => ['boundary_setting' => 0.9, 'health_conscious' => 0.8, 'break_reminding' => 0.8],
        '성실형' => ['appreciative' => 0.8, 'encouraging' => 0.8, 'progress_tracking' => 0.7],
        '무관심형' => ['engaging' => 0.9, 'motivational' => 0.9, 'relevance_showing' => 0.8]
    ];

    /** @var array 설정 */
    protected $config = [
        'include_emoji' => true,
        'max_response_length' => 500,
        'use_name_placeholder' => true,
        'default_tone' => 'friendly'
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 오버라이드
     */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 응답 생성 (오버라이드)
     *
     * @param array $persona 페르소나 정보
     * @param array $context 컨텍스트
     * @param string $mode 생성 모드
     * @return array 응답 결과
     */
    public function generate(array $persona, array $context, string $mode = 'template'): array {
        try {
            // 감정 정보 추출
            $emotion = $context['emotion'] ?? 'neutral';
            $emotionIntensity = $context['emotion_intensity'] ?? 0.5;
            $activityType = $context['activity_type'] ?? 'qa';

            // 모드별 응답 생성
            switch ($mode) {
                case 'template':
                    $response = $this->generateFromTemplate($persona, $context, $emotion, $emotionIntensity, $activityType);
                    break;

                case 'ai':
                    $response = $this->generateAIResponse($context['message'] ?? '', $persona, $context);
                    break;

                case 'hybrid':
                    $templateResponse = $this->generateFromTemplate($persona, $context, $emotion, $emotionIntensity, $activityType);
                    $aiResponse = $this->generateAIResponse($context['message'] ?? '', $persona, $context);
                    $response = $this->mergeResponses($templateResponse, $aiResponse);
                    break;

                default:
                    $response = $this->generateFromTemplate($persona, $context, $emotion, $emotionIntensity, $activityType);
            }

            // 톤 조정
            $personaId = $persona['persona_id'] ?? $persona['persona_name'] ?? '';
            $response['text'] = $this->adjustToneForPersona($response['text'], $personaId);

            // 변수 치환
            $response['text'] = $this->renderTemplate($response['text'], [
                'name' => $context['user']['firstname'] ?? '학생'
            ]);

            return [
                'success' => true,
                'text' => $response['text'],
                'tone' => $response['tone'] ?? $persona['tone'] ?? 'friendly',
                'source' => $mode,
                'emotion_addressed' => $emotion,
                'activity_type' => $activityType,
                'persona_applied' => $personaId
            ];

        } catch (\Exception $e) {
            $this->logError("응답 생성 실패: " . $e->getMessage(), __LINE__);

            return [
                'success' => false,
                'text' => $this->getDefaultResponse($emotion ?? 'neutral'),
                'tone' => 'neutral',
                'source' => 'fallback',
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . __LINE__
            ];
        }
    }

    /**
     * 템플릿 기반 응답 생성
     *
     * @param array $persona 페르소나
     * @param array $context 컨텍스트
     * @param string $emotion 감정
     * @param float $intensity 강도
     * @param string $activityType 활동 유형
     * @return array 응답
     */
    protected function generateFromTemplate(
        array $persona,
        array $context,
        string $emotion,
        float $intensity,
        string $activityType
    ): array {
        // 감정 강도 레벨 결정
        $intensityLevel = $this->getIntensityLevel($intensity);

        // 감정 템플릿 선택
        $emotionTemplate = $this->selectEmotionTemplate($emotion, $intensityLevel);

        // 활동 접두사 선택
        $activityPrefix = $this->selectActivityPrefix($activityType);

        // 응답 조합
        $responseText = $emotionTemplate;

        // 메시지에 직접적인 질문/요청이 있으면 활동 접두사 추가
        $message = $context['message'] ?? '';
        if ($this->containsQuestion($message)) {
            $responseText = $emotionTemplate . ' ' . $activityPrefix;
        }

        return [
            'text' => $responseText,
            'tone' => $this->determineToneFromEmotion($emotion),
            'template_used' => true
        ];
    }

    /**
     * 감정 템플릿 선택
     *
     * @param string $emotion 감정
     * @param string $level 강도 레벨
     * @return string 템플릿
     */
    protected function selectEmotionTemplate(string $emotion, string $level): string {
        // 감정 템플릿 존재 확인
        if (!isset($this->emotionTemplates[$emotion])) {
            $emotion = 'neutral';
        }

        $templates = $this->emotionTemplates[$emotion];

        // 레벨별 템플릿
        if (isset($templates[$level]) && !empty($templates[$level])) {
            $levelTemplates = $templates[$level];
        } elseif (isset($templates['default'])) {
            $levelTemplates = $templates['default'];
        } else {
            // 중간 레벨로 폴백
            $levelTemplates = $templates['medium'] ?? $templates['default'] ?? ['무엇을 도와드릴까요?'];
        }

        // 랜덤 선택
        return $levelTemplates[array_rand($levelTemplates)];
    }

    /**
     * 활동 접두사 선택
     *
     * @param string $activityType 활동 유형
     * @return string 접두사
     */
    protected function selectActivityPrefix(string $activityType): string {
        if (!isset($this->activityPrefixes[$activityType])) {
            $activityType = 'qa';
        }

        $prefixes = $this->activityPrefixes[$activityType];

        return $prefixes[array_rand($prefixes)];
    }

    /**
     * 강도 레벨 결정
     *
     * @param float $intensity 강도 값
     * @return string 레벨
     */
    protected function getIntensityLevel(float $intensity): string {
        if ($intensity >= 0.7) return 'high';
        if ($intensity >= 0.4) return 'medium';
        return 'low';
    }

    /**
     * 감정에서 톤 결정
     *
     * @param string $emotion 감정
     * @return string 톤
     */
    protected function determineToneFromEmotion(string $emotion): string {
        $emotionToTone = [
            'anxiety' => 'reassuring',
            'frustration' => 'patient',
            'confidence' => 'encouraging',
            'curiosity' => 'enthusiastic',
            'boredom' => 'engaging',
            'fatigue' => 'caring',
            'achievement' => 'celebratory',
            'confusion' => 'clarifying',
            'neutral' => 'friendly'
        ];

        return $emotionToTone[$emotion] ?? 'friendly';
    }

    /**
     * 페르소나별 톤 조정
     *
     * @param string $text 원본 텍스트
     * @param string $personaId 페르소나 ID
     * @return string 조정된 텍스트
     */
    protected function adjustToneForPersona(string $text, string $personaId): string {
        if (!isset($this->personaToneAdjustments[$personaId])) {
            return $text;
        }

        $adjustments = $this->personaToneAdjustments[$personaId];

        // 저항형/회피형: 더 부드럽게
        if (isset($adjustments['gentle']) && $adjustments['gentle'] > 0.8) {
            $text = $this->makeGentler($text);
        }

        // 도전형: 더 적극적으로
        if (isset($adjustments['ambitious']) && $adjustments['ambitious'] > 0.6) {
            $text = $this->makeMoreAmbitious($text);
        }

        // 체계형/분석형: 더 구조화
        if (isset($adjustments['structured']) && $adjustments['structured'] > 0.8) {
            $text = $this->makeMoreStructured($text);
        }

        // 과몰입형: 휴식 알림 추가
        if (isset($adjustments['break_reminding']) && $adjustments['break_reminding'] > 0.7) {
            $text = $this->addBreakReminder($text);
        }

        return $text;
    }

    /**
     * 텍스트를 더 부드럽게
     *
     * @param string $text 원본 텍스트
     * @return string 조정된 텍스트
     */
    protected function makeGentler(string $text): string {
        // 강한 표현 완화
        $replacements = [
            '해야 해요' => '해볼까요',
            '하세요' => '해보시겠어요?',
            '안 돼요' => '어려울 수 있어요',
            '틀렸어요' => '다시 확인해볼까요'
        ];

        foreach ($replacements as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        return $text;
    }

    /**
     * 텍스트를 더 적극적으로
     *
     * @param string $text 원본 텍스트
     * @return string 조정된 텍스트
     */
    protected function makeMoreAmbitious(string $text): string {
        // 격려 표현 강화
        $suffixes = [
            ' 더 어려운 문제도 도전해볼까요?',
            ' 실력이 늘고 있어요!',
            ' 다음 레벨로 가볼까요?'
        ];

        // 랜덤으로 접미사 추가 (30% 확률)
        if (mt_rand(1, 100) <= 30) {
            $text .= $suffixes[array_rand($suffixes)];
        }

        return $text;
    }

    /**
     * 텍스트를 더 구조화
     *
     * @param string $text 원본 텍스트
     * @return string 조정된 텍스트
     */
    protected function makeMoreStructured(string $text): string {
        // 이미 구조화된 경우 스킵
        if (strpos($text, '첫째') !== false || strpos($text, '1.') !== false) {
            return $text;
        }

        return $text;
    }

    /**
     * 휴식 알림 추가
     *
     * @param string $text 원본 텍스트
     * @return string 조정된 텍스트
     */
    protected function addBreakReminder(string $text): string {
        $reminders = [
            ' (적절한 휴식도 잊지 마세요!)',
            ' (눈 건강을 위해 잠시 먼 곳을 바라보세요.)',
            ' (물 한 잔 마시는 건 어때요?)'
        ];

        // 20% 확률로 휴식 알림 추가
        if (mt_rand(1, 100) <= 20) {
            $text .= $reminders[array_rand($reminders)];
        }

        return $text;
    }

    /**
     * 질문 포함 여부 확인
     *
     * @param string $message 메시지
     * @return bool 질문 포함 여부
     */
    protected function containsQuestion(string $message): bool {
        $questionIndicators = [
            '?', '뭐', '무엇', '어떻게', '왜', '언제', '어디',
            '알려줘', '설명해줘', '도와줘', '가르쳐', '모르겠'
        ];

        foreach ($questionIndicators as $indicator) {
            if (mb_strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 응답 병합 (hybrid 모드용)
     *
     * @param array $templateResponse 템플릿 응답
     * @param array $aiResponse AI 응답
     * @return array 병합된 응답
     */
    protected function mergeResponses(array $templateResponse, array $aiResponse): array {
        // 템플릿의 감정 응대 + AI의 내용 응답 결합
        $mergedText = $templateResponse['text'];

        if (isset($aiResponse['text']) && !empty($aiResponse['text'])) {
            $mergedText .= ' ' . $aiResponse['text'];
        }

        return [
            'text' => $mergedText,
            'tone' => $templateResponse['tone'],
            'merged' => true
        ];
    }

    /**
     * 기본 응답 반환 (오버라이드)
     *
     * @param string $emotion 감정
     * @return string 기본 응답
     */
    public function getDefaultResponse(string $emotion = 'neutral'): string {
        $defaults = [
            'anxiety' => '괜찮아요, 천천히 해봐요. 제가 도와드릴게요.',
            'frustration' => '어려우시죠? 다른 방법으로 시도해볼까요?',
            'confidence' => '잘 하고 계세요! 계속 그렇게 해봐요.',
            'curiosity' => '좋은 질문이에요! 알아봐요.',
            'boredom' => '조금 지루하시죠? 새로운 것을 해볼까요?',
            'fatigue' => '피곤하시면 잠시 쉬어도 괜찮아요.',
            'achievement' => '잘했어요! 대단해요!',
            'confusion' => '헷갈리시죠? 다시 설명해드릴게요.',
            'neutral' => '무엇을 도와드릴까요?'
        ];

        return $defaults[$emotion] ?? $defaults['neutral'];
    }

    /**
     * 학습 격려 메시지 생성
     *
     * @param array $learningData 학습 데이터
     * @return string 격려 메시지
     */
    public function generateEncouragement(array $learningData): string {
        $correctRate = $learningData['correct_rate'] ?? 0;
        $streak = $learningData['correct_streak'] ?? 0;
        $totalProblems = $learningData['total_problems'] ?? 0;

        if ($streak >= 5) {
            return "와! {$streak}문제 연속 정답이에요! 대단해요! 🔥";
        }

        if ($correctRate >= 80) {
            return "정답률 " . round($correctRate) . "%! 정말 잘 하고 있어요! ⭐";
        }

        if ($totalProblems >= 10 && $correctRate >= 60) {
            return "{$totalProblems}문제나 풀었어요! 꾸준히 노력하는 모습이 멋져요!";
        }

        if ($correctRate < 50 && $totalProblems >= 5) {
            return "조금 어려워도 괜찮아요. 함께 더 연습해봐요!";
        }

        return "좋아요! 계속 힘내봐요!";
    }

    /**
     * 학습 진도 피드백 생성
     *
     * @param array $progressData 진도 데이터
     * @return string 피드백 메시지
     */
    public function generateProgressFeedback(array $progressData): string {
        $completionRate = $progressData['completion_rate'] ?? 0;
        $conceptMastery = $progressData['concept_mastery'] ?? 0;
        $dailyGoal = $progressData['daily_goal'] ?? 0;
        $currentProgress = $progressData['current_progress'] ?? 0;

        $messages = [];

        // 일일 목표 진행 상황
        if ($dailyGoal > 0) {
            $goalProgress = ($currentProgress / $dailyGoal) * 100;
            if ($goalProgress >= 100) {
                $messages[] = "오늘 목표 달성! 🎯";
            } elseif ($goalProgress >= 70) {
                $remaining = $dailyGoal - $currentProgress;
                $messages[] = "목표까지 {$remaining}개 남았어요!";
            }
        }

        // 개념 숙달도
        if ($conceptMastery >= 80) {
            $messages[] = "이 개념은 거의 완벽하게 이해했어요!";
        } elseif ($conceptMastery >= 50) {
            $messages[] = "개념 이해가 점점 늘고 있어요!";
        }

        // 전체 완료율
        if ($completionRate >= 90) {
            $messages[] = "거의 다 완료했어요! 조금만 더!";
        }

        if (empty($messages)) {
            return "학습을 잘 진행하고 계시네요!";
        }

        return implode(' ', $messages);
    }

    /**
     * 에러 로깅 (오버라이드)
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    protected function logError(string $message, int $line): void {
        error_log("[Agent05ResponseGenerator ERROR] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_response_log
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - agent_id: varchar(50) NOT NULL
 *   - persona_id: varchar(50)
 *   - emotion_type: varchar(50)
 *   - activity_type: varchar(50)
 *   - response_text: text
 *   - response_mode: varchar(20) (template/ai/hybrid)
 *   - timecreated: bigint(10) NOT NULL
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/base/BaseResponseGenerator.php
 * - agents/agent05_learning_emotion/persona_system/engine/EmotionAnalyzer.php
 * - agents/agent05_learning_emotion/persona_system/engine/LearningActivityDetector.php
 */
