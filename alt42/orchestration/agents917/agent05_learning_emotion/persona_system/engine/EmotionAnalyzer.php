<?php
/**
 * EmotionAnalyzer - 학습 감정 분석기
 *
 * 학생 메시지에서 학습 관련 감정을 분석하는 전문 클래스
 * Korean NLU 패턴을 활용한 감정 감지 및 강도 측정
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\Engine
 * @version 1.0
 * @author Claude Code
 *
 * 분석 가능한 감정 유형:
 * - anxiety (불안): 시험, 평가, 실패에 대한 불안
 * - frustration (좌절): 반복 실패, 이해 불가에 대한 좌절
 * - confidence (자신감): 문제 해결, 이해에 대한 자신감
 * - curiosity (호기심): 새로운 개념, 심화 학습에 대한 호기심
 * - boredom (지루함): 반복, 쉬운 문제에 대한 지루함
 * - fatigue (피로): 장시간 학습, 집중력 저하
 * - achievement (성취감): 문제 해결, 목표 달성의 기쁨
 * - confusion (혼란): 개념 이해 어려움, 방향 상실
 */

namespace AugmentedTeacher\Agent05\PersonaSystem\Engine;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class EmotionAnalyzer {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 감정 키워드 사전 (한국어) */
    protected $emotionKeywords = [
        'anxiety' => [
            'high' => [
                '너무 불안', '시험 망할', '진짜 걱정', '죽을것같', '어떡해',
                '안될것같', '포기할까', '무서워', '두려워', '겁나',
                '망했', '큰일났', '끝났', '못할것같'
            ],
            'medium' => [
                '불안', '걱정', '긴장', '떨려', '초조',
                '자신없', '될까', '맞을까', '틀릴까', '어렵',
                '힘들', '괜찮을까'
            ],
            'low' => [
                '조금 걱정', '약간 긴장', '살짝 불안', '좀 떨려'
            ]
        ],
        'frustration' => [
            'high' => [
                '진짜 짜증', '너무 화나', '미치겠', '답답해 죽겠',
                '왜 안돼', '모르겠어', '이해 안돼', '포기', '그만',
                '싫어', '짜증나', '열받', '빡쳐', '할 수 없'
            ],
            'medium' => [
                '답답', '짜증', '화나', '안풀려', '막혀',
                '헷갈려', '어려워', '못하겠', '잘 안돼', '계속 틀려'
            ],
            'low' => [
                '좀 답답', '약간 짜증', '조금 어려워'
            ]
        ],
        'confidence' => [
            'high' => [
                '완벽해', '자신있', '할 수 있어', '쉬워', '간단해',
                '이해했', '알겠어', '확실해', '맞아', '당연하지',
                '문제없', '걱정없', '다 알아'
            ],
            'medium' => [
                '이해가', '알것같', '할수있', '가능할것같', '이제 알',
                '점점 이해', '감이 와', '느낌 와'
            ],
            'low' => [
                '조금 이해', '약간 알것같', '살짝 자신'
            ]
        ],
        'curiosity' => [
            'high' => [
                '정말 궁금', '너무 알고싶', '더 배우고싶', '흥미로워',
                '재미있', '신기해', '왜 그런지', '어떻게 되는', '알려줘'
            ],
            'medium' => [
                '궁금', '알고싶', '배우고싶', '관심', '흥미',
                '왜', '어떻게', '무엇', '원리가'
            ],
            'low' => [
                '좀 궁금', '약간 관심', '그냥 물어보는'
            ]
        ],
        'boredom' => [
            'high' => [
                '너무 지루', '재미없', '하기싫', '그만하고싶', '졸려',
                '시간낭비', '의미없', '왜 해야', '언제 끝나'
            ],
            'medium' => [
                '지루', '심심', '반복', '또야', '같은거',
                '쉬워서', '별로', '재미가'
            ],
            'low' => [
                '좀 지루', '약간 심심'
            ]
        ],
        'fatigue' => [
            'high' => [
                '너무 피곤', '지쳤', '힘들어 죽겠', '못하겠', '쉬고싶',
                '머리 아파', '집중 안돼', '졸려 죽겠', '한계'
            ],
            'medium' => [
                '피곤', '지쳐', '힘들', '졸려', '집중이',
                '쉬고', '잠깐', '휴식'
            ],
            'low' => [
                '좀 피곤', '약간 지쳐'
            ]
        ],
        'achievement' => [
            'high' => [
                '드디어', '해냈', '성공', '맞았', '풀었',
                '완료', '끝났', '해결', '정답', '완벽'
            ],
            'medium' => [
                '됐', '풀렸', '맞은것같', '이해됐', '알았',
                '진전', '나아졌'
            ],
            'low' => [
                '좀 나아', '조금 진전'
            ]
        ],
        'confusion' => [
            'high' => [
                '전혀 모르', '뭔소리', '이게 뭐', '하나도 모르',
                '완전 헷갈', '어디서부터', '뭘 해야'
            ],
            'medium' => [
                '헷갈', '혼란', '모르겠', '이해 못', '복잡',
                '어떻게 해야', '뭐가 뭔지'
            ],
            'low' => [
                '좀 헷갈', '약간 혼란'
            ]
        ]
    ];

    /** @var array 감정 패턴 (정규식) */
    protected $emotionPatterns = [
        'anxiety' => [
            '/시험.{0,10}(걱정|불안|두렵)/',
            '/평가.{0,10}(무섭|걱정)/',
            '/(못|안).{0,5}(할것|될것)같/',
            '/어떡(해|하지)/'
        ],
        'frustration' => [
            '/왜.{0,5}(안|못)(돼|풀|되)/',
            '/계속.{0,5}틀/',
            '/(이해|모르).{0,5}(안|못)/',
            '/몇.{0,3}번.{0,5}(했|풀|시도)/'
        ],
        'confidence' => [
            '/(할|풀).{0,5}수.{0,3}있/',
            '/이해.{0,5}(했|됐|완료)/',
            '/(쉬워|간단|문제없)/'
        ],
        'curiosity' => [
            '/왜.{0,10}(그런|되|인지)/',
            '/어떻게.{0,10}(되|하|작동)/',
            '/(원리|이유|근거).{0,5}(가|를|이)/'
        ],
        'boredom' => [
            '/또.{0,5}(같은|똑같)/',
            '/언제.{0,5}끝/',
            '/(반복|계속).{0,5}(같은|똑같)/'
        ],
        'fatigue' => [
            '/머리.{0,5}(아파|터질|안돌아)/',
            '/집중.{0,5}(안|못)/',
            '/(오래|계속).{0,5}(했|공부|풀)/'
        ],
        'achievement' => [
            '/드디어.{0,5}(풀|맞|성공|해결)/',
            '/(성공|해결|완료)/',
            '/(맞|정답).{0,5}(았|었)/'
        ],
        'confusion' => [
            '/(뭐|무엇).{0,5}(부터|해야|인지)/',
            '/어디.{0,5}(부터|서)/',
            '/(뭔|무슨).{0,5}(소리|말)/'
        ]
    ];

    /** @var array 감정 이모티콘 매핑 */
    protected $emotionEmoticons = [
        'anxiety' => ['😰', '😨', '😱', '😥', '🥺', '😢', 'ㅠㅠ', 'ㅜㅜ', 'ㅠ.ㅠ'],
        'frustration' => ['😤', '😠', '🤬', '💢', '😡', 'ㅡㅡ', '-_-', ';;;'],
        'confidence' => ['😊', '😎', '💪', '✌️', '👍', '^^', '^_^', ':)'],
        'curiosity' => ['🤔', '❓', '🧐', '??', '?!'],
        'boredom' => ['😴', '🥱', '😑', '😶', '-.-', 'ㅎ'],
        'fatigue' => ['😫', '😩', '🥵', '😵', '💤', 'ㅠㅠ'],
        'achievement' => ['🎉', '🎊', '✨', '💯', '🏆', '!!', '♥'],
        'confusion' => ['😵', '🤯', '❓', '???', '...', ';;']
    ];

    /** @var array 문맥 수정자 */
    protected $contextModifiers = [
        'negation' => ['안', '못', '없', '아니'],
        'intensifier' => ['너무', '진짜', '정말', '완전', '매우', '엄청'],
        'diminisher' => ['좀', '약간', '살짝', '조금', '그냥']
    ];

    /** @var array 설정 */
    protected $config = [
        'min_intensity' => 0.1,
        'max_emotions' => 3,
        'context_window' => 50,
        'enable_pattern_matching' => true,
        'enable_emoticon_detection' => true
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 오버라이드
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 메시지에서 감정 분석
     *
     * @param string $message 분석할 메시지
     * @param array $context 추가 컨텍스트 (이전 대화, 학습 상태 등)
     * @return array 감정 분석 결과
     */
    public function analyze(string $message, array $context = []): array {
        try {
            $normalizedMessage = $this->normalizeMessage($message);

            // 1. 키워드 기반 감정 감지
            $keywordEmotions = $this->detectByKeywords($normalizedMessage);

            // 2. 패턴 기반 감정 감지
            $patternEmotions = [];
            if ($this->config['enable_pattern_matching']) {
                $patternEmotions = $this->detectByPatterns($normalizedMessage);
            }

            // 3. 이모티콘 기반 감정 감지
            $emoticonEmotions = [];
            if ($this->config['enable_emoticon_detection']) {
                $emoticonEmotions = $this->detectByEmoticons($message);
            }

            // 4. 결과 병합 및 점수 조정
            $mergedEmotions = $this->mergeEmotions([
                $keywordEmotions,
                $patternEmotions,
                $emoticonEmotions
            ]);

            // 5. 문맥 수정자 적용
            $adjustedEmotions = $this->applyContextModifiers($mergedEmotions, $normalizedMessage);

            // 6. 학습 컨텍스트 반영
            $contextAdjusted = $this->applyLearningContext($adjustedEmotions, $context);

            // 7. 상위 감정 선택
            $topEmotions = $this->selectTopEmotions($contextAdjusted);

            // 8. 주요 감정 결정
            $primaryEmotion = $this->determinePrimaryEmotion($topEmotions);

            return [
                'success' => true,
                'primary_emotion' => $primaryEmotion['emotion'],
                'primary_intensity' => $primaryEmotion['intensity'],
                'all_emotions' => $topEmotions,
                'emotion_count' => count($topEmotions),
                'raw_scores' => $contextAdjusted,
                'analysis_details' => [
                    'keyword_matches' => $keywordEmotions,
                    'pattern_matches' => $patternEmotions,
                    'emoticon_matches' => $emoticonEmotions,
                    'normalized_message' => $normalizedMessage
                ]
            ];

        } catch (\Exception $e) {
            $this->logError("감정 분석 실패: " . $e->getMessage(), __LINE__);

            return [
                'success' => false,
                'primary_emotion' => 'neutral',
                'primary_intensity' => 0.0,
                'all_emotions' => [],
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . __LINE__
            ];
        }
    }

    /**
     * 메시지 정규화
     *
     * @param string $message 원본 메시지
     * @return string 정규화된 메시지
     */
    protected function normalizeMessage(string $message): string {
        // 소문자 변환 (영어)
        $normalized = mb_strtolower($message);

        // 반복 문자 축소 (ㅋㅋㅋㅋㅋ → ㅋㅋ)
        $normalized = preg_replace('/([\x{3131}-\x{3163}])\1{2,}/u', '$1$1', $normalized);

        // 연속 공백 제거
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * 키워드 기반 감정 감지
     *
     * @param string $message 정규화된 메시지
     * @return array 감정별 점수
     */
    protected function detectByKeywords(string $message): array {
        $scores = [];

        foreach ($this->emotionKeywords as $emotion => $levels) {
            $score = 0.0;
            $matchCount = 0;

            // 높은 강도 키워드
            foreach ($levels['high'] as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $score += 0.9;
                    $matchCount++;
                }
            }

            // 중간 강도 키워드
            foreach ($levels['medium'] as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $score += 0.6;
                    $matchCount++;
                }
            }

            // 낮은 강도 키워드
            foreach ($levels['low'] as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $score += 0.3;
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                // 복수 매칭 시 점수 증폭 (최대 1.0)
                $amplifiedScore = min(1.0, $score / max(1, $matchCount) + ($matchCount - 1) * 0.1);
                $scores[$emotion] = $amplifiedScore;
            }
        }

        return $scores;
    }

    /**
     * 패턴 기반 감정 감지
     *
     * @param string $message 정규화된 메시지
     * @return array 감정별 점수
     */
    protected function detectByPatterns(string $message): array {
        $scores = [];

        foreach ($this->emotionPatterns as $emotion => $patterns) {
            $matchCount = 0;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern . 'u', $message)) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                // 패턴 매칭은 0.5 기본, 복수 매칭 시 증가
                $scores[$emotion] = min(1.0, 0.5 + ($matchCount - 1) * 0.2);
            }
        }

        return $scores;
    }

    /**
     * 이모티콘 기반 감정 감지
     *
     * @param string $message 원본 메시지 (이모티콘 포함)
     * @return array 감정별 점수
     */
    protected function detectByEmoticons(string $message): array {
        $scores = [];

        foreach ($this->emotionEmoticons as $emotion => $emoticons) {
            $matchCount = 0;

            foreach ($emoticons as $emoticon) {
                $count = mb_substr_count($message, $emoticon);
                $matchCount += $count;
            }

            if ($matchCount > 0) {
                // 이모티콘은 0.4 기본, 반복 시 증가
                $scores[$emotion] = min(1.0, 0.4 + ($matchCount - 1) * 0.15);
            }
        }

        return $scores;
    }

    /**
     * 여러 소스의 감정 점수 병합
     *
     * @param array $emotionSources 감정 점수 배열들
     * @return array 병합된 감정 점수
     */
    protected function mergeEmotions(array $emotionSources): array {
        $merged = [];

        foreach ($emotionSources as $source) {
            foreach ($source as $emotion => $score) {
                if (!isset($merged[$emotion])) {
                    $merged[$emotion] = 0.0;
                }
                // 가장 높은 점수 + 추가 소스 보너스
                if ($score > $merged[$emotion]) {
                    $bonus = $merged[$emotion] * 0.3;
                    $merged[$emotion] = min(1.0, $score + $bonus);
                } else {
                    $merged[$emotion] = min(1.0, $merged[$emotion] + $score * 0.3);
                }
            }
        }

        return $merged;
    }

    /**
     * 문맥 수정자 적용
     *
     * @param array $emotions 감정 점수
     * @param string $message 메시지
     * @return array 조정된 감정 점수
     */
    protected function applyContextModifiers(array $emotions, string $message): array {
        $adjusted = $emotions;

        // 강조어 확인
        $hasIntensifier = false;
        foreach ($this->contextModifiers['intensifier'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $hasIntensifier = true;
                break;
            }
        }

        // 약화어 확인
        $hasDiminisher = false;
        foreach ($this->contextModifiers['diminisher'] as $word) {
            if (mb_strpos($message, $word) !== false) {
                $hasDiminisher = true;
                break;
            }
        }

        // 점수 조정
        foreach ($adjusted as $emotion => &$score) {
            if ($hasIntensifier) {
                $score = min(1.0, $score * 1.3);
            }
            if ($hasDiminisher) {
                $score = $score * 0.7;
            }
        }

        return $adjusted;
    }

    /**
     * 학습 컨텍스트 반영
     *
     * @param array $emotions 감정 점수
     * @param array $context 학습 컨텍스트
     * @return array 컨텍스트 조정된 감정 점수
     */
    protected function applyLearningContext(array $emotions, array $context): array {
        $adjusted = $emotions;

        // 연속 실패 시 좌절감 증폭
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        if ($consecutiveFailures >= 3 && isset($adjusted['frustration'])) {
            $adjusted['frustration'] = min(1.0, $adjusted['frustration'] * 1.2);
        }

        // 장시간 학습 시 피로감 증폭
        $sessionDuration = $context['session_duration'] ?? 0;
        if ($sessionDuration > 3600 && isset($adjusted['fatigue'])) { // 1시간 이상
            $adjusted['fatigue'] = min(1.0, $adjusted['fatigue'] * 1.2);
        }

        // 최근 성공 시 자신감 증폭
        $recentSuccess = $context['recent_success'] ?? false;
        if ($recentSuccess && isset($adjusted['confidence'])) {
            $adjusted['confidence'] = min(1.0, $adjusted['confidence'] * 1.2);
        }

        // 새로운 개념 학습 시 혼란 또는 호기심 증폭
        $isNewConcept = $context['is_new_concept'] ?? false;
        if ($isNewConcept) {
            if (isset($adjusted['confusion'])) {
                $adjusted['confusion'] = min(1.0, $adjusted['confusion'] * 1.15);
            }
            if (isset($adjusted['curiosity'])) {
                $adjusted['curiosity'] = min(1.0, $adjusted['curiosity'] * 1.15);
            }
        }

        // 난이도 높은 문제 시 불안 증폭
        $problemDifficulty = $context['problem_difficulty'] ?? 'medium';
        if ($problemDifficulty === 'high' && isset($adjusted['anxiety'])) {
            $adjusted['anxiety'] = min(1.0, $adjusted['anxiety'] * 1.15);
        }

        return $adjusted;
    }

    /**
     * 상위 감정 선택
     *
     * @param array $emotions 감정 점수
     * @return array 상위 감정 목록
     */
    protected function selectTopEmotions(array $emotions): array {
        // 최소 강도 이상만 필터링
        $filtered = array_filter($emotions, function($score) {
            return $score >= $this->config['min_intensity'];
        });

        // 점수 내림차순 정렬
        arsort($filtered);

        // 상위 N개 선택
        $top = array_slice($filtered, 0, $this->config['max_emotions'], true);

        // 배열 형식으로 변환
        $result = [];
        foreach ($top as $emotion => $intensity) {
            $result[] = [
                'emotion' => $emotion,
                'intensity' => round($intensity, 2),
                'level' => $this->getIntensityLevel($intensity),
                'label' => $this->getEmotionLabel($emotion)
            ];
        }

        return $result;
    }

    /**
     * 주요 감정 결정
     *
     * @param array $topEmotions 상위 감정 목록
     * @return array 주요 감정
     */
    protected function determinePrimaryEmotion(array $topEmotions): array {
        if (empty($topEmotions)) {
            return [
                'emotion' => 'neutral',
                'intensity' => 0.0,
                'level' => 'none',
                'label' => '중립'
            ];
        }

        return $topEmotions[0];
    }

    /**
     * 강도 레벨 반환
     *
     * @param float $intensity 강도 값
     * @return string 강도 레벨
     */
    protected function getIntensityLevel(float $intensity): string {
        if ($intensity >= 0.8) return 'very_high';
        if ($intensity >= 0.6) return 'high';
        if ($intensity >= 0.4) return 'medium';
        if ($intensity >= 0.2) return 'low';
        return 'very_low';
    }

    /**
     * 감정 레이블 반환 (한국어)
     *
     * @param string $emotion 감정 코드
     * @return string 한국어 레이블
     */
    protected function getEmotionLabel(string $emotion): string {
        $labels = [
            'anxiety' => '불안',
            'frustration' => '좌절',
            'confidence' => '자신감',
            'curiosity' => '호기심',
            'boredom' => '지루함',
            'fatigue' => '피로',
            'achievement' => '성취감',
            'confusion' => '혼란',
            'neutral' => '중립'
        ];

        return $labels[$emotion] ?? $emotion;
    }

    /**
     * 감정 변화 추적
     *
     * @param array $currentEmotion 현재 감정
     * @param array $previousEmotions 이전 감정 기록
     * @return array 감정 변화 분석
     */
    public function trackEmotionChange(array $currentEmotion, array $previousEmotions): array {
        if (empty($previousEmotions)) {
            return [
                'trend' => 'neutral',
                'change_magnitude' => 0,
                'emotion_shift' => null
            ];
        }

        // 최근 감정과 비교
        $recentEmotion = end($previousEmotions);
        $currentPrimary = $currentEmotion['primary_emotion'];
        $currentIntensity = $currentEmotion['primary_intensity'];
        $previousPrimary = $recentEmotion['primary_emotion'] ?? 'neutral';
        $previousIntensity = $recentEmotion['primary_intensity'] ?? 0.0;

        // 감정 종류 변화
        $emotionShift = ($currentPrimary !== $previousPrimary);

        // 강도 변화
        $intensityChange = $currentIntensity - $previousIntensity;

        // 트렌드 결정
        $trend = 'stable';
        if (abs($intensityChange) > 0.2) {
            $trend = $intensityChange > 0 ? 'increasing' : 'decreasing';
        }

        // 감정 패턴 분석 (최근 5개)
        $recentEmotions = array_slice($previousEmotions, -5);
        $emotionPattern = $this->analyzeEmotionPattern($recentEmotions);

        return [
            'trend' => $trend,
            'change_magnitude' => round(abs($intensityChange), 2),
            'emotion_shift' => $emotionShift,
            'previous_emotion' => $previousPrimary,
            'current_emotion' => $currentPrimary,
            'intensity_delta' => round($intensityChange, 2),
            'pattern' => $emotionPattern
        ];
    }

    /**
     * 감정 패턴 분석
     *
     * @param array $emotions 최근 감정 기록
     * @return string 패턴 유형
     */
    protected function analyzeEmotionPattern(array $emotions): string {
        if (count($emotions) < 3) {
            return 'insufficient_data';
        }

        $negativeCount = 0;
        $positiveCount = 0;

        foreach ($emotions as $emotion) {
            $primary = $emotion['primary_emotion'] ?? 'neutral';
            if (in_array($primary, ['anxiety', 'frustration', 'confusion', 'fatigue'])) {
                $negativeCount++;
            } elseif (in_array($primary, ['confidence', 'curiosity', 'achievement'])) {
                $positiveCount++;
            }
        }

        if ($negativeCount >= 3) return 'persistent_negative';
        if ($positiveCount >= 3) return 'persistent_positive';
        if ($negativeCount >= 2 && $positiveCount >= 2) return 'volatile';

        return 'mixed';
    }

    /**
     * 복합 감정 감지
     *
     * @param array $emotions 감정 점수
     * @return array|null 복합 감정 정보
     */
    public function detectComplexEmotion(array $emotions): ?array {
        // 복합 감정 패턴 정의
        $complexPatterns = [
            'anxious_curiosity' => ['anxiety', 'curiosity'],
            'frustrated_determination' => ['frustration', 'confidence'],
            'confused_interest' => ['confusion', 'curiosity'],
            'tired_achievement' => ['fatigue', 'achievement'],
            'bored_frustration' => ['boredom', 'frustration']
        ];

        $allEmotions = $emotions['all_emotions'] ?? [];
        $emotionCodes = array_column($allEmotions, 'emotion');

        foreach ($complexPatterns as $complex => $components) {
            $hasAll = true;
            foreach ($components as $component) {
                if (!in_array($component, $emotionCodes)) {
                    $hasAll = false;
                    break;
                }
            }

            if ($hasAll) {
                return [
                    'complex_emotion' => $complex,
                    'components' => $components,
                    'label' => $this->getComplexEmotionLabel($complex)
                ];
            }
        }

        return null;
    }

    /**
     * 복합 감정 레이블 반환
     *
     * @param string $complex 복합 감정 코드
     * @return string 한국어 레이블
     */
    protected function getComplexEmotionLabel(string $complex): string {
        $labels = [
            'anxious_curiosity' => '불안 속 호기심',
            'frustrated_determination' => '좌절 속 의지',
            'confused_interest' => '혼란 속 관심',
            'tired_achievement' => '피로 속 성취감',
            'bored_frustration' => '지루함과 답답함'
        ];

        return $labels[$complex] ?? $complex;
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    protected function logError(string $message, int $line): void {
        error_log("[EmotionAnalyzer ERROR] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 관련 DB 테이블:
 * - at_learning_emotion_log
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - emotion_type: varchar(50) NOT NULL
 *   - emotion_intensity: decimal(3,2) NOT NULL
 *   - activity_type: varchar(50)
 *   - message: text
 *   - context_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *
 * 참조 파일:
 * - agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php
 * - agents/agent05_learning_emotion/persona_system/engine/Agent05DataContext.php
 */
