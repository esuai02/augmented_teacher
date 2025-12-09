<?php
/**
 * CalmnessNLUAnalyzer - Agent08 침착성 전용 자연어 이해 분석기
 *
 * 학생 메시지의 침착성 관련 의도, 감정, 불안 지표를 분석하여
 * 침착성 기반 페르소나 식별 정확도를 향상시킵니다.
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
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

class CalmnessNLUAnalyzer {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    private $agentId = 'agent08';

    /** @var array 침착성 관련 의도 패턴 */
    private $calmnessIntentPatterns = [];

    /** @var array 침착성 관련 감정 사전 */
    private $calmnessEmotionLexicon = [];

    /** @var array 불안 트리거 키워드 */
    private $anxietyTriggers = [];

    /** @var array 위기 상황 지표 */
    private $crisisIndicators = [];

    /** @var array 분석 캐시 */
    private $analysisCache = [];

    /**
     * 생성자
     */
    public function __construct() {
        $this->initCalmnessIntentPatterns();
        $this->initCalmnessEmotionLexicon();
        $this->initAnxietyTriggers();
        $this->initCrisisIndicators();
    }

    /**
     * 침착성 관련 의도 패턴 초기화
     */
    private function initCalmnessIntentPatterns(): void {
        $this->calmnessIntentPatterns = [
            // 진정 요청
            'calming_request' => [
                'patterns' => [
                    '/진정\s*(시켜|해)\s*(주세요|줘)/u',
                    '/마음\s*(좀|을)\s*가라앉히/u',
                    '/안정\s*(이|을)\s*(필요|원해)/u',
                    '/호흡\s*(법|운동|도움)/u',
                    '/긴장\s*(풀|완화)/u'
                ],
                'keywords' => ['진정', '안정', '평온', '차분', '호흡', '이완'],
                'confidence_boost' => 0.15
            ],

            // 불안 표현
            'anxiety_expression' => [
                'patterns' => [
                    '/불안해요/u',
                    '/긴장\s*(돼요|되요|이에요)/u',
                    '/떨려요/u',
                    '/두근거려요/u',
                    '/무서워요/u',
                    '/걱정\s*(돼요|되요|이에요)/u'
                ],
                'keywords' => ['불안', '긴장', '떨려', '두근', '무서', '걱정', '초조'],
                'confidence_boost' => 0.18
            ],

            // 스트레스 표현
            'stress_expression' => [
                'patterns' => [
                    '/스트레스\s*(받아요|많아요)/u',
                    '/힘들어요/u',
                    '/지쳐요/u',
                    '/피곤해요/u',
                    '/버거워요/u',
                    '/벅차요/u'
                ],
                'keywords' => ['스트레스', '힘들', '지쳐', '피곤', '버거', '벅차', '부담'],
                'confidence_boost' => 0.15
            ],

            // 좌절/분노 표현
            'frustration_expression' => [
                'patterns' => [
                    '/짜증나요/u',
                    '/화나요/u',
                    '/답답해요/u',
                    '/속상해요/u',
                    '/열받아요/u',
                    '/빡쳐요/u'
                ],
                'keywords' => ['짜증', '화나', '답답', '속상', '열받', '빡치', '억울'],
                'confidence_boost' => 0.16
            ],

            // 압도감 표현
            'overwhelmed_expression' => [
                'patterns' => [
                    '/막막해요/u',
                    '/어지러워요/u',
                    '/모르겠어요/u',
                    '/어떻게\s*해야\s*(할지|될지)/u',
                    '/감당이\s*안/u',
                    '/정신이\s*없/u'
                ],
                'keywords' => ['막막', '어지러', '혼란', '모르겠', '감당', '버거', '정신없'],
                'confidence_boost' => 0.20
            ],

            // 집중력 관련
            'focus_related' => [
                'patterns' => [
                    '/집중\s*(이|을)\s*(안|못)/u',
                    '/산만해요/u',
                    '/멍해요/u',
                    '/머리가\s*(안|복잡)/u',
                    '/생각이\s*많/u'
                ],
                'keywords' => ['집중', '산만', '멍', '머리', '생각', '복잡'],
                'confidence_boost' => 0.12
            ],

            // 신체 증상 표현
            'physical_symptoms' => [
                'patterns' => [
                    '/숨\s*(이|을)\s*(못|안)/u',
                    '/가슴\s*(이|이)\s*(답답|아파|두근)/u',
                    '/심장\s*(이|이)\s*(빨리|두근)/u',
                    '/손\s*(이|이)\s*떨/u',
                    '/땀\s*(이|이)\s*나/u',
                    '/어깨\s*(가|가)\s*(뻣뻣|무거)/u'
                ],
                'keywords' => ['숨', '가슴', '심장', '떨림', '땀', '두통', '어깨'],
                'confidence_boost' => 0.17
            ],

            // 평온함 표현
            'calmness_expression' => [
                'patterns' => [
                    '/괜찮아요/u',
                    '/평온해요/u',
                    '/편안해요/u',
                    '/안정됐어요/u',
                    '/차분해요/u'
                ],
                'keywords' => ['괜찮', '평온', '편안', '안정', '차분', '여유', '고요'],
                'confidence_boost' => 0.10
            ],

            // 도움 요청
            'help_request' => [
                'patterns' => [
                    '/도와\s*(주세요|줘)/u',
                    '/어떻게\s*해야/u',
                    '/방법\s*(이|을)\s*(있|알려)/u',
                    '/조언\s*(을|이)\s*(해|부탁)/u'
                ],
                'keywords' => ['도와', '방법', '조언', '어떻게', '가르쳐'],
                'confidence_boost' => 0.12
            ],

            // 시간 압박 표현
            'time_pressure' => [
                'patterns' => [
                    '/시간\s*(이|이)\s*(없|부족)/u',
                    '/급해요/u',
                    '/빨리\s*해야/u',
                    '/마감\s*(이|까지)/u',
                    '/늦었어요/u'
                ],
                'keywords' => ['시간', '급해', '빨리', '마감', '늦', '서둘러'],
                'confidence_boost' => 0.14
            ]
        ];
    }

    /**
     * 침착성 관련 감정 사전 초기화
     */
    private function initCalmnessEmotionLexicon(): void {
        $this->calmnessEmotionLexicon = [
            // 불안 (Anxiety)
            'anxiety' => [
                'keywords' => ['불안', '걱정', '두려', '무서', '긴장', '떨려', '초조', '조마조마', '불안정'],
                'intensity' => [
                    'high' => ['극도로', '너무', '정말', '완전', '엄청', '심하게'],
                    'medium' => ['좀', '조금', '약간', '꽤'],
                    'low' => ['살짝', '가끔', '때때로']
                ],
                'weight' => 0.95,
                'calmness_impact' => -20
            ],

            // 공황 (Panic)
            'panic' => [
                'keywords' => ['공황', '패닉', '숨막혀', '죽을것같', '미칠것같', '터질것같'],
                'intensity' => [
                    'high' => ['완전', '정말', '진짜'],
                    'medium' => ['조금'],
                    'low' => []
                ],
                'weight' => 1.0,
                'calmness_impact' => -35,
                'crisis_flag' => true
            ],

            // 스트레스 (Stress)
            'stress' => [
                'keywords' => ['스트레스', '힘들', '지쳐', '피곤', '압박', '부담', '벅차'],
                'intensity' => [
                    'high' => ['너무', '정말', '완전', '극도로'],
                    'medium' => ['좀', '꽤'],
                    'low' => ['조금', '살짝']
                ],
                'weight' => 0.85,
                'calmness_impact' => -15
            ],

            // 좌절 (Frustration)
            'frustration' => [
                'keywords' => ['짜증', '답답', '화나', '열받', '빡치', '진짜', '대체'],
                'intensity' => [
                    'high' => ['진짜', '완전', '너무'],
                    'medium' => ['좀', '조금'],
                    'low' => ['살짝']
                ],
                'weight' => 0.90,
                'calmness_impact' => -18
            ],

            // 압도감 (Overwhelmed)
            'overwhelmed' => [
                'keywords' => ['막막', '어지러', '혼란', '복잡', '감당안됨', '버거워'],
                'intensity' => [
                    'high' => ['완전', '너무', '진짜'],
                    'medium' => ['좀', '꽤'],
                    'low' => ['약간']
                ],
                'weight' => 0.92,
                'calmness_impact' => -22
            ],

            // 무력감 (Helplessness)
            'helplessness' => [
                'keywords' => ['못하겠', '포기', '안돼', '소용없', '의미없', '할 수 없', '끝났어'],
                'intensity' => [
                    'high' => ['완전히', '절대', '도저히'],
                    'medium' => ['거의', '대부분'],
                    'low' => ['조금']
                ],
                'weight' => 0.98,
                'calmness_impact' => -25,
                'crisis_flag' => true
            ],

            // 평온 (Calm)
            'calm' => [
                'keywords' => ['차분', '평온', '안정', '여유', '편안', '고요', '평화', '잔잔'],
                'intensity' => [
                    'high' => ['완전', '정말', '매우'],
                    'medium' => ['좀', '꽤'],
                    'low' => ['약간', '조금']
                ],
                'weight' => 0.70,
                'calmness_impact' => +15
            ],

            // 집중 (Focused)
            'focused' => [
                'keywords' => ['집중', '몰입', '명확', '또렷', '깨끗', '맑은'],
                'intensity' => [
                    'high' => ['완전', '정말'],
                    'medium' => ['꽤', '좀'],
                    'low' => ['약간']
                ],
                'weight' => 0.65,
                'calmness_impact' => +10
            ],

            // 긍정적 (Positive)
            'positive' => [
                'keywords' => ['괜찮', '좋아', '기뻐', '행복', '감사', '다행'],
                'intensity' => [
                    'high' => ['정말', '너무', '완전'],
                    'medium' => ['꽤', '좀'],
                    'low' => ['조금']
                ],
                'weight' => 0.60,
                'calmness_impact' => +12
            ]
        ];
    }

    /**
     * 불안 트리거 키워드 초기화
     */
    private function initAnxietyTriggers(): void {
        $this->anxietyTriggers = [
            // 시험/평가 관련
            'exam' => [
                'keywords' => ['시험', '테스트', '평가', '발표', '면접'],
                'anxiety_boost' => 0.15
            ],
            // 시간 관련
            'time' => [
                'keywords' => ['마감', '데드라인', '늦', '급해', '서둘러', '시간없'],
                'anxiety_boost' => 0.12
            ],
            // 실패 관련
            'failure' => [
                'keywords' => ['실패', '틀려', '못해', '안돼', '떨어져'],
                'anxiety_boost' => 0.18
            ],
            // 타인 평가 관련
            'social' => [
                'keywords' => ['창피', '부끄러', '눈치', '비교', '남들'],
                'anxiety_boost' => 0.14
            ],
            // 미래 불확실성
            'uncertainty' => [
                'keywords' => ['모르겠', '어떡해', '어쩌지', '앞으로', '미래'],
                'anxiety_boost' => 0.13
            ]
        ];
    }

    /**
     * 위기 상황 지표 초기화
     */
    private function initCrisisIndicators(): void {
        $this->crisisIndicators = [
            // 자해/자살 관련 (최고 우선순위)
            'self_harm' => [
                'keywords' => ['죽고싶', '사라지고싶', '없어지고싶', '끝내고싶', '자해'],
                'severity' => 'critical',
                'action' => 'immediate_intervention'
            ],
            // 극심한 공황
            'severe_panic' => [
                'keywords' => ['숨을못쉬겠', '죽을것같', '쓰러질것같', '미칠것같'],
                'severity' => 'high',
                'action' => 'crisis_support'
            ],
            // 급성 스트레스
            'acute_stress' => [
                'keywords' => ['더이상못버텨', '한계야', '포기할래', '그만둘래'],
                'severity' => 'moderate',
                'action' => 'immediate_support'
            ],
            // 심한 좌절
            'severe_frustration' => [
                'keywords' => ['다때려치고싶', '다부숴버리고싶', '폭발할것같'],
                'severity' => 'moderate',
                'action' => 'de_escalation'
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
            'intent' => $this->detectCalmnessIntent($message),
            'emotion' => $this->detectCalmnessEmotion($message),
            'calmness_indicators' => $this->analyzeCalmnessIndicators($message),
            'anxiety_triggers' => $this->detectAnxietyTriggers($message),
            'crisis_check' => $this->checkCrisisIndicators($message),
            'linguistic_features' => $this->extractLinguisticFeatures($message),
            'recommended_intervention' => null,
            'confidence_modifiers' => [],
            'flags' => [],
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];

        // 추천 개입 전략 결정
        $result['recommended_intervention'] = $this->determineRecommendedIntervention($result);

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

        // 반복 문자 정리 (예: "힘들어어어" → "힘들어어")
        $normalized = preg_replace('/(.)\1{2,}/u', '$1$1', $normalized);

        // 이모티콘 제거 (분석용)
        $normalized = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $normalized);

        return $normalized;
    }

    /**
     * 침착성 관련 의도 감지
     *
     * @param string $message 메시지
     * @return array 의도 분석 결과
     */
    public function detectCalmnessIntent(string $message): array {
        $detectedIntents = [];
        $normalized = $this->normalizeMessage($message);

        foreach ($this->calmnessIntentPatterns as $intentName => $intentData) {
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
     * 침착성 관련 감정 감지
     *
     * @param string $message 메시지
     * @return array 감정 분석 결과
     */
    public function detectCalmnessEmotion(string $message): array {
        $detectedEmotions = [];
        $normalized = $this->normalizeMessage($message);
        $totalCalmnessImpact = 0;
        $hasCrisisFlag = false;

        foreach ($this->calmnessEmotionLexicon as $emotionName => $emotionData) {
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
                    'calmness_impact' => $emotionData['calmness_impact'],
                    'matched_keywords' => $matchedKeywords
                ];

                // 침착성 영향 누적
                $intensityMultiplier = $detectedIntensity === 'high' ? 1.5 : ($detectedIntensity === 'low' ? 0.5 : 1.0);
                $totalCalmnessImpact += $emotionData['calmness_impact'] * $score * $intensityMultiplier;

                // 위기 플래그 확인
                if (!empty($emotionData['crisis_flag'])) {
                    $hasCrisisFlag = true;
                }
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
            'valence' => $overallValence,
            'all_emotions' => $detectedEmotions,
            'emotional_intensity' => $this->calculateOverallIntensity($detectedEmotions),
            'calmness_impact' => $totalCalmnessImpact,
            'has_crisis_flag' => $hasCrisisFlag
        ];
    }

    /**
     * 침착성 지표 분석
     *
     * @param string $message 메시지
     * @return array 침착성 지표
     */
    public function analyzeCalmnessIndicators(string $message): array {
        $normalized = $this->normalizeMessage($message);

        $indicators = [
            'calm_words' => 0,
            'anxiety_words' => 0,
            'stress_words' => 0,
            'focus_words' => 0,
            'physical_symptoms' => 0,
            'time_pressure' => 0
        ];

        // 차분함 키워드
        $calmKeywords = ['차분', '평온', '안정', '여유', '편안', '고요', '평화', '잔잔'];
        foreach ($calmKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['calm_words']++;
            }
        }

        // 불안 키워드
        $anxietyKeywords = ['불안', '초조', '긴장', '떨려', '두근', '조급', '급해', '걱정'];
        foreach ($anxietyKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['anxiety_words']++;
            }
        }

        // 스트레스 키워드
        $stressKeywords = ['스트레스', '힘들', '지쳐', '피곤', '압박', '부담', '벅차'];
        foreach ($stressKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['stress_words']++;
            }
        }

        // 집중 키워드
        $focusKeywords = ['집중', '몰입', '명확', '또렷', '깨끗', '맑은'];
        foreach ($focusKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['focus_words']++;
            }
        }

        // 신체 증상 키워드
        $physicalKeywords = ['숨', '가슴', '심장', '떨림', '땀', '두통', '어깨', '목'];
        foreach ($physicalKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['physical_symptoms']++;
            }
        }

        // 시간 압박 키워드
        $timeKeywords = ['시간', '급해', '빨리', '마감', '늦', '서둘러'];
        foreach ($timeKeywords as $word) {
            if (mb_strpos($normalized, $word) !== false) {
                $indicators['time_pressure']++;
            }
        }

        // 침착성 점수 추정
        $calmnessScore = 85;  // 기본 점수
        $calmnessScore += $indicators['calm_words'] * 5;
        $calmnessScore += $indicators['focus_words'] * 3;
        $calmnessScore -= $indicators['anxiety_words'] * 8;
        $calmnessScore -= $indicators['stress_words'] * 6;
        $calmnessScore -= $indicators['physical_symptoms'] * 5;
        $calmnessScore -= $indicators['time_pressure'] * 4;

        $indicators['estimated_calmness_score'] = max(0, min(100, $calmnessScore));

        return $indicators;
    }

    /**
     * 불안 트리거 감지
     *
     * @param string $message 메시지
     * @return array 감지된 불안 트리거
     */
    public function detectAnxietyTriggers(string $message): array {
        $detectedTriggers = [];
        $normalized = $this->normalizeMessage($message);
        $totalAnxietyBoost = 0;

        foreach ($this->anxietyTriggers as $triggerName => $triggerData) {
            $matchedKeywords = [];

            foreach ($triggerData['keywords'] as $keyword) {
                if (mb_strpos($normalized, $keyword) !== false) {
                    $matchedKeywords[] = $keyword;
                }
            }

            if (!empty($matchedKeywords)) {
                $detectedTriggers[$triggerName] = [
                    'matched_keywords' => $matchedKeywords,
                    'anxiety_boost' => $triggerData['anxiety_boost']
                ];
                $totalAnxietyBoost += $triggerData['anxiety_boost'];
            }
        }

        return [
            'triggers' => $detectedTriggers,
            'trigger_count' => count($detectedTriggers),
            'total_anxiety_boost' => $totalAnxietyBoost
        ];
    }

    /**
     * 위기 상황 지표 확인
     *
     * @param string $message 메시지
     * @return array 위기 상황 확인 결과
     */
    public function checkCrisisIndicators(string $message): array {
        $normalized = $this->normalizeMessage($message);
        $detectedCrisis = [];
        $maxSeverity = null;
        $recommendedAction = null;

        foreach ($this->crisisIndicators as $crisisType => $crisisData) {
            $matchedKeywords = [];

            foreach ($crisisData['keywords'] as $keyword) {
                if (mb_strpos($normalized, $keyword) !== false) {
                    $matchedKeywords[] = $keyword;
                }
            }

            if (!empty($matchedKeywords)) {
                $detectedCrisis[$crisisType] = [
                    'matched_keywords' => $matchedKeywords,
                    'severity' => $crisisData['severity'],
                    'action' => $crisisData['action']
                ];

                // 최고 심각도 업데이트
                $severityOrder = ['critical' => 3, 'high' => 2, 'moderate' => 1];
                $currentSeverity = $severityOrder[$crisisData['severity']] ?? 0;
                $maxSeverityValue = $severityOrder[$maxSeverity] ?? 0;

                if ($currentSeverity > $maxSeverityValue) {
                    $maxSeverity = $crisisData['severity'];
                    $recommendedAction = $crisisData['action'];
                }
            }
        }

        return [
            'is_crisis' => !empty($detectedCrisis),
            'crisis_indicators' => $detectedCrisis,
            'max_severity' => $maxSeverity,
            'recommended_action' => $recommendedAction
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
            'sentence_count' => max(1, preg_match_all('/[.!?。！？]/u', $message)),
            'urgency_level' => $this->detectUrgency($message),
            'formality_level' => $this->detectFormality($message),
            'breathing_suggestion' => $this->needsBreathingExercise($message),
            'grounding_needed' => $this->needsGroundingExercise($message)
        ];
    }

    /**
     * 추천 개입 전략 결정
     *
     * @param array $analysisResult 분석 결과
     * @return array 추천 개입 전략
     */
    private function determineRecommendedIntervention(array $analysisResult): array {
        $crisisCheck = $analysisResult['crisis_check'];
        $emotion = $analysisResult['emotion'];
        $intent = $analysisResult['intent'];
        $calmnessIndicators = $analysisResult['calmness_indicators'];

        // 위기 상황 우선 처리
        if ($crisisCheck['is_crisis']) {
            return [
                'type' => 'CrisisIntervention',
                'urgency' => 'immediate',
                'action' => $crisisCheck['recommended_action'],
                'severity' => $crisisCheck['max_severity']
            ];
        }

        // 침착성 점수 기반 결정
        $calmnessScore = $calmnessIndicators['estimated_calmness_score'];
        $primaryEmotion = $emotion['primary_emotion'];

        if ($calmnessScore < 60) {
            return [
                'type' => 'CrisisSupport',
                'urgency' => 'high',
                'techniques' => ['breathing', 'grounding'],
                'tone' => 'Calm'
            ];
        } elseif ($calmnessScore < 75) {
            return [
                'type' => 'CalmnessCoaching',
                'urgency' => 'medium',
                'techniques' => ['breathing', 'reframing'],
                'tone' => 'Empathetic'
            ];
        } elseif ($calmnessScore < 85) {
            return [
                'type' => 'EmotionalSupport',
                'urgency' => 'normal',
                'techniques' => ['validation', 'encouragement'],
                'tone' => 'Supportive'
            ];
        } else {
            return [
                'type' => 'FocusGuidance',
                'urgency' => 'low',
                'techniques' => ['skill_building', 'goal_setting'],
                'tone' => 'Professional'
            ];
        }
    }

    /**
     * 감정 극성 계산
     */
    private function calculateValence(array $emotions): string {
        $positiveEmotions = ['calm', 'focused', 'positive'];
        $negativeEmotions = ['anxiety', 'panic', 'stress', 'frustration', 'overwhelmed', 'helplessness'];

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
     * 긴급도 감지
     */
    private function detectUrgency(string $message): string {
        $urgentPatterns = ['급해', '빨리', '지금', '바로', '당장', '즉시'];
        $normalized = $this->normalizeMessage($message);

        foreach ($urgentPatterns as $pattern) {
            if (mb_strpos($normalized, $pattern) !== false) {
                return 'urgent';
            }
        }
        return 'normal';
    }

    /**
     * 격식 수준 감지
     */
    private function detectFormality(string $message): string {
        $formalPatterns = ['/요$/u', '/습니다/u', '/입니다/u', '/세요/u'];
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
     * 호흡 운동 필요 여부 판단
     */
    private function needsBreathingExercise(string $message): bool {
        $breathingTriggers = ['숨', '호흡', '심장', '두근', '떨려', '긴장', '공황', '패닉'];
        $normalized = $this->normalizeMessage($message);

        foreach ($breathingTriggers as $trigger) {
            if (mb_strpos($normalized, $trigger) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 그라운딩 운동 필요 여부 판단
     */
    private function needsGroundingExercise(string $message): bool {
        $groundingTriggers = ['막막', '혼란', '어지러', '현실', '분리', '멍', '띵'];
        $normalized = $this->normalizeMessage($message);

        foreach ($groundingTriggers as $trigger) {
            if (mb_strpos($normalized, $trigger) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 신뢰도 수정자 계산
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

        // 감정 강도 기반 수정자
        if ($analysisResult['emotion']['emotional_intensity'] === 'high') {
            $modifiers['emotion_intensity_boost'] = 0.15;
        }

        // 위기 상황 기반 수정자
        if ($analysisResult['crisis_check']['is_crisis']) {
            $modifiers['crisis_boost'] = 0.20;
        }

        // 불안 트리거 기반 수정자
        $modifiers['anxiety_trigger_boost'] = $analysisResult['anxiety_triggers']['total_anxiety_boost'];

        return $modifiers;
    }

    /**
     * 특수 플래그 설정
     */
    private function setFlags(array $analysisResult): array {
        $flags = [];

        // 위기 상황 플래그
        if ($analysisResult['crisis_check']['is_crisis']) {
            $flags[] = 'crisis_alert';
            $flags[] = 'priority_intervention';
        }

        // 호흡 운동 필요 플래그
        if ($analysisResult['linguistic_features']['breathing_suggestion']) {
            $flags[] = 'breathing_needed';
        }

        // 그라운딩 필요 플래그
        if ($analysisResult['linguistic_features']['grounding_needed']) {
            $flags[] = 'grounding_needed';
        }

        // 정서 지원 필요 플래그
        if ($analysisResult['emotion']['valence'] === 'negative' &&
            $analysisResult['emotion']['emotional_intensity'] === 'high') {
            $flags[] = 'emotional_support_needed';
        }

        // 낮은 침착성 플래그
        if ($analysisResult['calmness_indicators']['estimated_calmness_score'] < 70) {
            $flags[] = 'low_calmness';
        }

        // 긴급 상황 플래그
        if ($analysisResult['linguistic_features']['urgency_level'] === 'urgent') {
            $flags[] = 'urgent_request';
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
 * 관련 DB 테이블:
 * - at_calmness_scores (침착성 점수 이력)
 * - at_agent_persona_state (에이전트별 페르소나 상태)
 * - at_persona_log (처리 로그)
 *
 * NLU 분석 결과 구조:
 * [
 *   'original_message' => '원본 메시지',
 *   'normalized_message' => '정규화된 메시지',
 *   'intent' => [
 *     'primary_intent' => 'anxiety_expression',
 *     'all_intents' => [...],
 *     'intent_count' => 3
 *   ],
 *   'emotion' => [
 *     'primary_emotion' => 'anxiety',
 *     'valence' => 'negative',
 *     'all_emotions' => [...],
 *     'emotional_intensity' => 'high',
 *     'calmness_impact' => -25,
 *     'has_crisis_flag' => false
 *   ],
 *   'calmness_indicators' => [
 *     'calm_words' => 0,
 *     'anxiety_words' => 3,
 *     'estimated_calmness_score' => 65
 *   ],
 *   'anxiety_triggers' => [...],
 *   'crisis_check' => [...],
 *   'linguistic_features' => [...],
 *   'recommended_intervention' => [...],
 *   'flags' => ['emotional_support_needed', 'low_calmness']
 * ]
 */
