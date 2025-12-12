<?php
/**
 * NLUAnalyzer - 자연어 이해 분석기
 *
 * 학생 메시지의 의도, 감정, 주제를 분석하여
 * 페르소나 식별 정확도를 향상시킵니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// PHP 7.1 호환: array_key_first() 폴리필
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}

// PHP 7.1 호환: array_key_last() 폴리필
if (!function_exists('array_key_last')) {
    function array_key_last(array $arr) {
        if (empty($arr)) {
            return null;
        }
        return array_keys($arr)[count($arr) - 1];
    }
}

class NLUAnalyzer {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 의도 패턴 */
    private $intentPatterns = [];

    /** @var array 감정 사전 */
    private $emotionLexicon = [];

    /** @var array 주제 키워드 */
    private $topicKeywords = [];

    /** @var array 분석 캐시 */
    private $analysisCache = [];

    /**
     * 생성자
     */
    public function __construct() {
        $this->initIntentPatterns();
        $this->initEmotionLexicon();
        $this->initTopicKeywords();
    }

    /**
     * 의도 패턴 초기화
     */
    private function initIntentPatterns(): void {
        $this->intentPatterns = [
            // 정보 요청 의도
            'information_request' => [
                'patterns' => [
                    '/뭐예요\?$/u',
                    '/무엇인가요\?$/u',
                    '/알려\s*(주세요|줘)/u',
                    '/설명\s*(해|해주세요)/u',
                    '/어떻게\s*(하|되|해야)/u',
                    '/왜\s*(그런|이런|그래)/u',
                    '/언제.*(하|되|해야)/u'
                ],
                'keywords' => ['뭐', '무엇', '어떻게', '왜', '언제', '어디', '누가', '몇'],
                'confidence_boost' => 0.10
            ],

            // 도움 요청 의도
            'help_request' => [
                'patterns' => [
                    '/도와\s*(주세요|줘)/u',
                    '/모르겠어요/u',
                    '/이해\s*(가|를)\s*(안|못)/u',
                    '/어려워요/u',
                    '/힘들어요/u',
                    '/할\s*수\s*없/u'
                ],
                'keywords' => ['도와', '모르', '어려', '힘들', '못하겠', '안돼'],
                'confidence_boost' => 0.15
            ],

            // 확인/동의 의도
            'confirmation' => [
                'patterns' => [
                    '/^네$/u',
                    '/^예$/u',
                    '/^응$/u',
                    '/알겠어요/u',
                    '/이해했어요/u',
                    '/그래요/u',
                    '/맞아요/u'
                ],
                'keywords' => ['네', '예', '응', '알겠', '이해', '그래', '맞아', '좋아'],
                'confidence_boost' => 0.05
            ],

            // 거부/부정 의도
            'rejection' => [
                'patterns' => [
                    '/^아니요$/u',
                    '/^아니$/u',
                    '/싫어요/u',
                    '/안\s*할래요/u',
                    '/못\s*해요/u',
                    '/됐어요/u'
                ],
                'keywords' => ['아니', '싫', '안해', '못해', '됐어', '그만'],
                'confidence_boost' => 0.08
            ],

            // 감정 표현 의도
            'emotional_expression' => [
                'patterns' => [
                    '/불안해요/u',
                    '/걱정\s*(돼요|이에요)/u',
                    '/무서워요/u',
                    '/화나요/u',
                    '/슬퍼요/u',
                    '/기뻐요/u',
                    '/좋아요/u'
                ],
                'keywords' => ['불안', '걱정', '무서', '화나', '슬퍼', '기뻐', '좋아', '싫어'],
                'confidence_boost' => 0.12
            ],

            // 목표/계획 관련 의도
            'goal_related' => [
                'patterns' => [
                    '/하고\s*싶어요/u',
                    '/되고\s*싶어요/u',
                    '/목표는/u',
                    '/계획은/u',
                    '/원해요/u'
                ],
                'keywords' => ['목표', '계획', '하고 싶', '되고 싶', '원해', '바라'],
                'confidence_boost' => 0.08
            ],

            // 자기 평가 의도
            'self_assessment' => [
                'patterns' => [
                    '/잘\s*(못|안)\s*(해요|하는)/u',
                    '/자신\s*(없|있)/u',
                    '/실력이/u',
                    '/수준이/u',
                    '/제가\s*(잘|못)/u'
                ],
                'keywords' => ['잘', '못', '자신', '실력', '수준', '능력'],
                'confidence_boost' => 0.10
            ],

            // 비교 의도
            'comparison' => [
                'patterns' => [
                    '/다른\s*애들/u',
                    '/친구들은/u',
                    '/보다\s*(더|덜)/u',
                    '/비교하면/u'
                ],
                'keywords' => ['다른', '친구', '비교', '보다', '차이'],
                'confidence_boost' => 0.06
            ],

            // 피드백 요청 의도
            'feedback_request' => [
                'patterns' => [
                    '/어땠어요\?$/u',
                    '/맞나요\?$/u',
                    '/이게\s*맞/u',
                    '/잘\s*하고\s*있/u',
                    '/괜찮나요/u'
                ],
                'keywords' => ['어때', '맞나', '괜찮', '잘하고', '제대로'],
                'confidence_boost' => 0.07
            ],

            // 시간/일정 관련 의도
            'scheduling' => [
                'patterns' => [
                    '/언제\s*(해요|까지)/u',
                    '/시간이\s*(없|부족)/u',
                    '/바빠요/u',
                    '/오늘|내일|이번주/u'
                ],
                'keywords' => ['언제', '시간', '바빠', '오늘', '내일', '주말'],
                'confidence_boost' => 0.05
            ]
        ];
    }

    /**
     * 감정 사전 초기화
     */
    private function initEmotionLexicon(): void {
        $this->emotionLexicon = [
            // 불안 (Anxiety)
            'anxiety' => [
                'keywords' => ['불안', '걱정', '두려', '무서', '긴장', '떨려', '초조', '조마조마'],
                'intensity' => [
                    'high' => ['극도로', '너무', '정말', '완전'],
                    'medium' => ['좀', '조금', '약간'],
                    'low' => ['살짝', '가끔']
                ],
                'weight' => 0.85
            ],

            // 좌절 (Frustration)
            'frustration' => [
                'keywords' => ['짜증', '답답', '화나', '열받', '빡치', '진짜', '대체'],
                'intensity' => [
                    'high' => ['진짜', '완전', '너무'],
                    'medium' => ['좀', '조금'],
                    'low' => ['살짝']
                ],
                'weight' => 0.90
            ],

            // 슬픔 (Sadness)
            'sadness' => [
                'keywords' => ['슬퍼', '우울', '눈물', '서러', '속상', '마음이 아파'],
                'intensity' => [
                    'high' => ['너무', '정말', '많이'],
                    'medium' => ['좀', '조금'],
                    'low' => ['약간']
                ],
                'weight' => 0.80
            ],

            // 무력감 (Helplessness)
            'helplessness' => [
                'keywords' => ['못하겠', '포기', '안돼', '소용없', '의미없', '할 수 없'],
                'intensity' => [
                    'high' => ['완전히', '절대', '도저히'],
                    'medium' => ['거의', '대부분'],
                    'low' => ['조금']
                ],
                'weight' => 0.95  // 위기 개입 필요 가능성
            ],

            // 자신감 (Confidence)
            'confidence' => [
                'keywords' => ['할 수 있', '자신있', '해볼게', '잘 할', '할게요'],
                'intensity' => [
                    'high' => ['완전', '확실히', '분명히'],
                    'medium' => ['어느 정도', '좀'],
                    'low' => ['조금', '약간']
                ],
                'weight' => 0.70
            ],

            // 기대/흥분 (Excitement)
            'excitement' => [
                'keywords' => ['기대', '신나', '재밌', '좋겠', '기뻐', '설레'],
                'intensity' => [
                    'high' => ['정말', '너무', '완전'],
                    'medium' => ['좀', '꽤'],
                    'low' => ['약간']
                ],
                'weight' => 0.65
            ],

            // 무관심 (Indifference)
            'indifference' => [
                'keywords' => ['상관없', '그냥', '몰라', '별로', '아무거나', '그럭저럭'],
                'intensity' => [
                    'high' => ['완전', '전혀'],
                    'medium' => ['대체로'],
                    'low' => ['좀']
                ],
                'weight' => 0.75
            ],

            // 방어적 (Defensive)
            'defensive' => [
                'keywords' => ['왜요', '그게 왜', '안 그래요', '아니에요', '그런 거 아니'],
                'intensity' => [
                    'high' => ['절대', '전혀'],
                    'medium' => ['그렇게'],
                    'low' => ['좀']
                ],
                'weight' => 0.80
            ]
        ];
    }

    /**
     * 주제 키워드 초기화
     */
    private function initTopicKeywords(): void {
        $this->topicKeywords = [
            'math_difficulty' => [
                'keywords' => ['수학', '계산', '공식', '문제', '풀이', '답', '오답'],
                'subtopics' => [
                    'algebra' => ['방정식', '함수', '변수', '미지수', '대수'],
                    'geometry' => ['도형', '각도', '삼각형', '원', '기하'],
                    'calculus' => ['미분', '적분', '극한', '미적분'],
                    'statistics' => ['확률', '통계', '평균', '분산']
                ]
            ],
            'study_habits' => [
                'keywords' => ['공부', '학습', '복습', '예습', '숙제', '과제'],
                'subtopics' => [
                    'time_management' => ['시간', '계획', '일정'],
                    'concentration' => ['집중', '방해', '산만'],
                    'methods' => ['방법', '요령', '팁', '노하우']
                ]
            ],
            'exam_related' => [
                'keywords' => ['시험', '테스트', '점수', '성적', '등급'],
                'subtopics' => [
                    'preparation' => ['준비', '대비', '공부법'],
                    'anxiety' => ['긴장', '불안', '걱정'],
                    'results' => ['결과', '점수', '등수']
                ]
            ],
            'motivation' => [
                'keywords' => ['동기', '의욕', '목표', '꿈', '진로'],
                'subtopics' => [
                    'goals' => ['목표', '꿈', '희망'],
                    'career' => ['진로', '직업', '대학'],
                    'interest' => ['흥미', '관심', '재미']
                ]
            ],
            'relationships' => [
                'keywords' => ['친구', '선생님', '부모님', '엄마', '아빠'],
                'subtopics' => [
                    'peer' => ['친구', '반 애들', '같이'],
                    'teacher' => ['선생님', '학교', '수업'],
                    'family' => ['부모님', '엄마', '아빠', '집']
                ]
            ]
        ];
    }

    /**
     * 종합 메시지 분석
     *
     * @param string $message 분석할 메시지
     * @return array 분석 결과
     */
    public function analyze(string $message): array {
        // 캐시 확인
        $cacheKey = md5($message);
        if (isset($this->analysisCache[$cacheKey])) {
            return $this->analysisCache[$cacheKey];
        }

        $result = [
            'original_message' => $message,
            'normalized_message' => $this->normalizeMessage($message),
            'intent' => $this->detectIntent($message),
            'emotion' => $this->detectEmotion($message),
            'topics' => $this->extractTopics($message),
            'linguistic_features' => $this->extractLinguisticFeatures($message),
            'confidence_modifiers' => [],
            'flags' => [],
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];

        // 신뢰도 수정자 계산
        $result['confidence_modifiers'] = $this->calculateConfidenceModifiers($result);

        // 특수 플래그 설정
        $result['flags'] = $this->setFlags($result);

        // 캐시 저장
        $this->analysisCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 메시지 정규화
     *
     * @param string $message 메시지
     * @return string 정규화된 메시지
     */
    private function normalizeMessage(string $message): string {
        // 공백 정리
        $normalized = preg_replace('/\s+/', ' ', trim($message));

        // 반복 문자 정리 (예: "진짜짜짜" → "진짜")
        $normalized = preg_replace('/(.)\1{2,}/u', '$1$1', $normalized);

        // 이모티콘 제거 (분석용)
        $normalized = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $normalized);

        return $normalized;
    }

    /**
     * 의도 감지
     *
     * @param string $message 메시지
     * @return array 의도 분석 결과
     */
    public function detectIntent(string $message): array {
        $detectedIntents = [];
        $normalized = $this->normalizeMessage($message);

        foreach ($this->intentPatterns as $intentName => $intentData) {
            $score = 0;
            $matchedPatterns = [];
            $matchedKeywords = [];

            // 패턴 매칭
            foreach ($intentData['patterns'] as $pattern) {
                if (preg_match($pattern, $normalized)) {
                    $score += 0.4;
                    $matchedPatterns[] = $pattern;
                }
            }

            // 키워드 매칭
            foreach ($intentData['keywords'] as $keyword) {
                if (mb_strpos($normalized, $keyword) !== false) {
                    $score += 0.2;
                    $matchedKeywords[] = $keyword;
                }
            }

            if ($score > 0) {
                $detectedIntents[$intentName] = [
                    'score' => min($score, 1.0),
                    'confidence_boost' => $intentData['confidence_boost'],
                    'matched_patterns' => $matchedPatterns,
                    'matched_keywords' => $matchedKeywords
                ];
            }
        }

        // 점수 기준 정렬
        uasort($detectedIntents, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'primary_intent' => !empty($detectedIntents) ? array_key_first($detectedIntents) : 'unknown',
            'all_intents' => $detectedIntents,
            'intent_count' => count($detectedIntents)
        ];
    }

    /**
     * 감정 감지
     *
     * @param string $message 메시지
     * @return array 감정 분석 결과
     */
    public function detectEmotion(string $message): array {
        $detectedEmotions = [];
        $normalized = $this->normalizeMessage($message);

        foreach ($this->emotionLexicon as $emotionName => $emotionData) {
            $score = 0;
            $matchedKeywords = [];
            $detectedIntensity = 'medium';

            // 키워드 매칭
            foreach ($emotionData['keywords'] as $keyword) {
                if (mb_strpos($normalized, $keyword) !== false) {
                    $score += 0.3;
                    $matchedKeywords[] = $keyword;
                }
            }

            // 강도 감지
            foreach ($emotionData['intensity'] as $level => $intensityWords) {
                foreach ($intensityWords as $word) {
                    if (mb_strpos($normalized, $word) !== false) {
                        $detectedIntensity = $level;
                        if ($level === 'high') $score += 0.2;
                        elseif ($level === 'low') $score -= 0.1;
                        break 2;
                    }
                }
            }

            if ($score > 0) {
                $detectedEmotions[$emotionName] = [
                    'score' => min($score, 1.0),
                    'intensity' => $detectedIntensity,
                    'weight' => $emotionData['weight'],
                    'matched_keywords' => $matchedKeywords
                ];
            }
        }

        // 가중치 적용 점수로 정렬
        uasort($detectedEmotions, function($a, $b) {
            return ($b['score'] * $b['weight']) <=> ($a['score'] * $a['weight']);
        });

        // 전체 감정 상태 결정
        $primaryEmotion = !empty($detectedEmotions) ? array_key_first($detectedEmotions) : 'neutral';
        $overallValence = $this->calculateValence($detectedEmotions);

        return [
            'primary_emotion' => $primaryEmotion,
            'valence' => $overallValence,  // positive, negative, neutral
            'all_emotions' => $detectedEmotions,
            'emotional_intensity' => $this->calculateOverallIntensity($detectedEmotions)
        ];
    }

    /**
     * 주제 추출
     *
     * @param string $message 메시지
     * @return array 주제 분석 결과
     */
    public function extractTopics(string $message): array {
        $detectedTopics = [];
        $normalized = $this->normalizeMessage($message);

        foreach ($this->topicKeywords as $topicName => $topicData) {
            $score = 0;
            $matchedKeywords = [];
            $matchedSubtopics = [];

            // 메인 키워드 매칭
            foreach ($topicData['keywords'] as $keyword) {
                if (mb_strpos($normalized, $keyword) !== false) {
                    $score += 0.25;
                    $matchedKeywords[] = $keyword;
                }
            }

            // 서브토픽 매칭
            foreach ($topicData['subtopics'] as $subtopicName => $subtopicKeywords) {
                foreach ($subtopicKeywords as $keyword) {
                    if (mb_strpos($normalized, $keyword) !== false) {
                        $score += 0.15;
                        $matchedSubtopics[$subtopicName][] = $keyword;
                    }
                }
            }

            if ($score > 0) {
                $detectedTopics[$topicName] = [
                    'score' => min($score, 1.0),
                    'matched_keywords' => $matchedKeywords,
                    'subtopics' => $matchedSubtopics
                ];
            }
        }

        uasort($detectedTopics, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'primary_topic' => !empty($detectedTopics) ? array_key_first($detectedTopics) : 'general',
            'all_topics' => $detectedTopics
        ];
    }

    /**
     * 언어적 특성 추출
     *
     * @param string $message 메시지
     * @return array 언어적 특성
     */
    public function extractLinguisticFeatures(string $message): array {
        return [
            'length' => mb_strlen($message),
            'word_count' => count(preg_split('/\s+/', trim($message))),
            'has_question_mark' => strpos($message, '?') !== false,
            'has_exclamation' => strpos($message, '!') !== false,
            'has_ellipsis' => strpos($message, '...') !== false || strpos($message, '…') !== false,
            'sentence_count' => preg_match_all('/[.!?。！？]/u', $message),
            'formality_level' => $this->detectFormality($message),
            'complexity_score' => $this->calculateComplexity($message)
        ];
    }

    /**
     * 감정 극성 계산
     *
     * @param array $emotions 감정 배열
     * @return string 극성 (positive, negative, neutral, mixed)
     */
    private function calculateValence(array $emotions): string {
        $positiveEmotions = ['confidence', 'excitement'];
        $negativeEmotions = ['anxiety', 'frustration', 'sadness', 'helplessness', 'defensive'];

        $positiveScore = 0;
        $negativeScore = 0;

        foreach ($emotions as $emotion => $data) {
            if (in_array($emotion, $positiveEmotions)) {
                $positiveScore += $data['score'];
            } elseif (in_array($emotion, $negativeEmotions)) {
                $negativeScore += $data['score'];
            }
        }

        if ($positiveScore > $negativeScore + 0.2) return 'positive';
        if ($negativeScore > $positiveScore + 0.2) return 'negative';
        if ($positiveScore > 0 && $negativeScore > 0) return 'mixed';
        return 'neutral';
    }

    /**
     * 전체 감정 강도 계산
     *
     * @param array $emotions 감정 배열
     * @return string 강도 (high, medium, low)
     */
    private function calculateOverallIntensity(array $emotions): string {
        $highCount = 0;
        foreach ($emotions as $emotion) {
            if ($emotion['intensity'] === 'high') $highCount++;
        }

        if ($highCount >= 1) return 'high';
        if (count($emotions) >= 2) return 'medium';
        return 'low';
    }

    /**
     * 격식 수준 감지
     *
     * @param string $message 메시지
     * @return string 격식 수준
     */
    private function detectFormality(string $message): string {
        // 존댓말 패턴
        $formalPatterns = ['/요$/u', '/습니다/u', '/입니다/u', '/세요/u'];
        // 반말 패턴
        $casualPatterns = ['/야$/u', '/어$/u', '/지$/u', '/냐$/u'];

        $formalScore = 0;
        $casualScore = 0;

        foreach ($formalPatterns as $pattern) {
            if (preg_match($pattern, $message)) $formalScore++;
        }
        foreach ($casualPatterns as $pattern) {
            if (preg_match($pattern, $message)) $casualScore++;
        }

        if ($formalScore > $casualScore) return 'formal';
        if ($casualScore > $formalScore) return 'casual';
        return 'neutral';
    }

    /**
     * 복잡도 계산
     *
     * @param string $message 메시지
     * @return float 복잡도 점수 (0-1)
     */
    private function calculateComplexity(string $message): float {
        $length = mb_strlen($message);
        $wordCount = count(preg_split('/\s+/', trim($message)));

        if ($length === 0) return 0;

        // 평균 단어 길이
        $avgWordLength = $length / max($wordCount, 1);

        // 문장 수
        $sentenceCount = max(preg_match_all('/[.!?。！？]/u', $message), 1);

        // 복잡도 점수 (정규화)
        $score = ($avgWordLength / 10) + ($wordCount / 50) + (1 / $sentenceCount);

        return min(max($score / 3, 0), 1);
    }

    /**
     * 신뢰도 수정자 계산
     *
     * @param array $analysisResult 분석 결과
     * @return array 수정자 배열
     */
    private function calculateConfidenceModifiers(array $analysisResult): array {
        $modifiers = [];

        // 의도 기반 수정자
        if (!empty($analysisResult['intent']['primary_intent'])) {
            $primaryIntent = $analysisResult['intent']['primary_intent'];
            $intentData = $analysisResult['intent']['all_intents'][$primaryIntent] ?? null;
            if ($intentData) {
                $modifiers['intent_boost'] = $intentData['confidence_boost'];
            }
        }

        // 감정 기반 수정자
        if (!empty($analysisResult['emotion']['all_emotions'])) {
            $primaryEmotion = $analysisResult['emotion']['primary_emotion'];
            $emotionData = $analysisResult['emotion']['all_emotions'][$primaryEmotion] ?? null;
            if ($emotionData && $emotionData['intensity'] === 'high') {
                $modifiers['emotion_intensity_boost'] = 0.10;
            }
        }

        // 언어적 특성 기반 수정자
        $features = $analysisResult['linguistic_features'];
        if ($features['length'] < 5) {
            $modifiers['short_response_penalty'] = -0.10;
        }
        if ($features['has_question_mark']) {
            $modifiers['question_intent_boost'] = 0.05;
        }

        return $modifiers;
    }

    /**
     * 특수 플래그 설정
     *
     * @param array $analysisResult 분석 결과
     * @return array 플래그 배열
     */
    private function setFlags(array $analysisResult): array {
        $flags = [];

        // 위기 상황 플래그
        $emotions = $analysisResult['emotion']['all_emotions'] ?? [];
        if (isset($emotions['helplessness']) && $emotions['helplessness']['intensity'] === 'high') {
            $flags[] = 'crisis_alert';
        }

        // 이탈 위험 플래그
        if ($analysisResult['emotion']['primary_emotion'] === 'indifference') {
            $flags[] = 'disengagement_risk';
        }

        // 정서 지원 필요 플래그
        if ($analysisResult['emotion']['valence'] === 'negative' &&
            $analysisResult['emotion']['emotional_intensity'] === 'high') {
            $flags[] = 'emotional_support_needed';
        }

        // 긍정적 진전 플래그
        if ($analysisResult['emotion']['primary_emotion'] === 'confidence') {
            $flags[] = 'positive_progress';
        }

        return $flags;
    }

    /**
     * 분석 캐시 초기화
     */
    public function clearCache(): void {
        $this->analysisCache = [];
    }
}

/*
 * NLU 분석 결과 구조:
 * [
 *   'original_message' => '원본 메시지',
 *   'normalized_message' => '정규화된 메시지',
 *   'intent' => [
 *     'primary_intent' => 'help_request',
 *     'all_intents' => [...],
 *     'intent_count' => 2
 *   ],
 *   'emotion' => [
 *     'primary_emotion' => 'anxiety',
 *     'valence' => 'negative',
 *     'all_emotions' => [...],
 *     'emotional_intensity' => 'high'
 *   ],
 *   'topics' => [
 *     'primary_topic' => 'math_difficulty',
 *     'all_topics' => [...]
 *   ],
 *   'linguistic_features' => [...],
 *   'confidence_modifiers' => [...],
 *   'flags' => ['emotional_support_needed']
 * ]
 */
