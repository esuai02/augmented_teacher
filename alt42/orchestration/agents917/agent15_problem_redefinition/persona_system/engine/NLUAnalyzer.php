<?php
/**
 * NLUAnalyzer - 자연어 분석기
 *
 * 학생 메시지의 의도, 감정, 키워드 분석
 * 문제 재정의를 위한 NLU 기능 제공
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class NLUAnalyzer {

    /** @var array AI 설정 */
    private $aiConfig;

    /** @var bool AI 사용 가능 여부 */
    private $aiEnabled = false;

    /** @var array 감정 키워드 사전 */
    private $emotionKeywords = [];

    /** @var array 의도 패턴 */
    private $intentPatterns = [];

    /** @var array 분석 캐시 */
    private $analysisCache = [];

    /**
     * 생성자
     *
     * @param array $aiConfig AI 설정
     */
    public function __construct($aiConfig = []) {
        $this->aiConfig = $aiConfig;
        $this->aiEnabled = !empty($aiConfig['openai_api_key']);
        $this->initKeywords();
        $this->initIntentPatterns();
    }

    /**
     * 감정 키워드 초기화
     */
    private function initKeywords() {
        $this->emotionKeywords = [
            'frustration' => [
                '짜증', '화나', '답답', '힘들', '못하겠', '안되', '어려워',
                '포기', '싫어', '지겨워', '미치겠', '열받'
            ],
            'anxiety' => [
                '걱정', '불안', '두려워', '무서워', '떨려', '긴장',
                '초조', '겁나', '망할', '어떡해'
            ],
            'boredom' => [
                '지루', '재미없', '따분', '심심', '흥미없', '관심없'
            ],
            'hopelessness' => [
                '희망없', '소용없', '의미없', '무의미', '왜해', '안될것'
            ],
            'confusion' => [
                '모르겠', '이해안', '헷갈', '복잡', '뭐가뭔지', '어떻게'
            ],
            'motivation' => [
                '해볼게', '노력할게', '열심히', '잘하고싶', '성공하고',
                '할수있', '해낼수', '도전'
            ],
            'relief' => [
                '다행', '안심', '휴', '살것같', '나아졌', '괜찮'
            ]
        ];
    }

    /**
     * 의도 패턴 초기화
     */
    private function initIntentPatterns() {
        $this->intentPatterns = [
            'help_request' => [
                'patterns' => ['도와', '알려', '가르쳐', '설명해', '어떻게 해', '방법'],
                'priority' => 1
            ],
            'problem_report' => [
                'patterns' => ['안돼', '못해', '실패', '오류', '문제가', '잘못'],
                'priority' => 2
            ],
            'clarification' => [
                'patterns' => ['무슨 말', '뭐야', '이게 뭐', '왜', '어째서'],
                'priority' => 3
            ],
            'confirmation' => [
                'patterns' => ['맞아', '그래', '응', '네', '알겠', '이해했'],
                'priority' => 4
            ],
            'denial' => [
                'patterns' => ['아니', '아닌데', '그게 아니', '틀렸', '잘못'],
                'priority' => 2
            ],
            'avoidance' => [
                'patterns' => ['나중에', '다음에', '지금은', '싫어', '안할래', '귀찮'],
                'priority' => 2
            ],
            'expression' => [
                'patterns' => ['느껴', '생각', '것 같', '인것같', '-고 싶'],
                'priority' => 5
            ]
        ];
    }

    /**
     * 메시지 분석
     *
     * @param string $message 사용자 메시지
     * @param array $context 컨텍스트
     * @return array 분석 결과
     */
    public function analyze($message, $context = []) {
        // 캐시 확인
        $cacheKey = md5($message);
        if (isset($this->analysisCache[$cacheKey])) {
            return $this->analysisCache[$cacheKey];
        }

        $result = [
            'original_message' => $message,
            'intent' => $this->detectIntent($message),
            'emotions' => $this->detectEmotions($message),
            'keywords' => $this->extractKeywords($message),
            'sentiment' => $this->analyzeSentiment($message),
            'problem_indicators' => $this->detectProblemIndicators($message),
            'engagement_level' => $this->assessEngagement($message),
            'confidence' => 0.0
        ];

        // 신뢰도 계산
        $result['confidence'] = $this->calculateConfidence($result);

        // AI 분석 보강 (활성화된 경우)
        if ($this->aiEnabled && $result['confidence'] < 0.7) {
            $aiResult = $this->analyzeWithAI($message, $context);
            $result = $this->mergeResults($result, $aiResult);
        }

        // 캐시 저장
        $this->analysisCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 의도 감지
     *
     * @param string $message 메시지
     * @return array 감지된 의도들
     */
    private function detectIntent($message) {
        $detectedIntents = [];

        foreach ($this->intentPatterns as $intent => $data) {
            $matchCount = 0;
            foreach ($data['patterns'] as $pattern) {
                if (mb_strpos($message, $pattern) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $detectedIntents[] = [
                    'intent' => $intent,
                    'confidence' => min($matchCount / count($data['patterns']), 1.0),
                    'priority' => $data['priority']
                ];
            }
        }

        // 우선순위와 신뢰도로 정렬
        usort($detectedIntents, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] - $b['priority'];
            }
            return $b['confidence'] <=> $a['confidence'];
        });

        return [
            'primary' => !empty($detectedIntents) ? $detectedIntents[0]['intent'] : 'unknown',
            'all' => $detectedIntents
        ];
    }

    /**
     * 감정 감지
     *
     * @param string $message 메시지
     * @return array 감지된 감정들
     */
    private function detectEmotions($message) {
        $detectedEmotions = [];

        foreach ($this->emotionKeywords as $emotion => $keywords) {
            $matchCount = 0;
            $matchedKeywords = [];

            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $matchCount++;
                    $matchedKeywords[] = $keyword;
                }
            }

            if ($matchCount > 0) {
                $intensity = min($matchCount / 3, 1.0); // 최대 3개 매칭시 100%
                $detectedEmotions[] = [
                    'emotion' => $emotion,
                    'intensity' => $intensity,
                    'keywords' => $matchedKeywords
                ];
            }
        }

        // 강도순 정렬
        usort($detectedEmotions, function($a, $b) {
            return $b['intensity'] <=> $a['intensity'];
        });

        return [
            'primary' => !empty($detectedEmotions) ? $detectedEmotions[0]['emotion'] : 'neutral',
            'all' => $detectedEmotions
        ];
    }

    /**
     * 키워드 추출
     *
     * @param string $message 메시지
     * @return array 추출된 키워드
     */
    private function extractKeywords($message) {
        $keywords = [];

        // 학습 관련 키워드
        $learningKeywords = [
            '수학', '문제', '공부', '시험', '과제', '숙제', '점수', '성적',
            '개념', '공식', '풀이', '답', '오답', '복습', '예습', '단원'
        ];

        foreach ($learningKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $keywords[] = [
                    'word' => $keyword,
                    'category' => 'learning'
                ];
            }
        }

        // 시간 관련 키워드
        $timeKeywords = [
            '오늘', '어제', '내일', '이번주', '저번주', '최근', '요즘',
            '시간', '분', '시간없', '바빠'
        ];

        foreach ($timeKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $keywords[] = [
                    'word' => $keyword,
                    'category' => 'time'
                ];
            }
        }

        return $keywords;
    }

    /**
     * 감성 분석
     *
     * @param string $message 메시지
     * @return array 감성 분석 결과
     */
    private function analyzeSentiment($message) {
        $positiveCount = 0;
        $negativeCount = 0;

        $positiveWords = ['좋', '잘', '성공', '해냈', '기뻐', '행복', '감사', '최고'];
        $negativeWords = ['나쁘', '실패', '못', '안되', '싫', '슬프', '힘들', '최악'];

        foreach ($positiveWords as $word) {
            if (mb_strpos($message, $word) !== false) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (mb_strpos($message, $word) !== false) {
                $negativeCount++;
            }
        }

        $total = $positiveCount + $negativeCount;
        if ($total === 0) {
            return ['label' => 'neutral', 'score' => 0];
        }

        $score = ($positiveCount - $negativeCount) / $total;

        if ($score > 0.3) {
            $label = 'positive';
        } elseif ($score < -0.3) {
            $label = 'negative';
        } else {
            $label = 'neutral';
        }

        return [
            'label' => $label,
            'score' => $score,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount
        ];
    }

    /**
     * 문제 지표 감지
     *
     * @param string $message 메시지
     * @return array 문제 지표
     */
    private function detectProblemIndicators($message) {
        $indicators = [];

        // 학습 어려움 지표
        $difficultyPatterns = [
            '이해가 안' => 'comprehension',
            '모르겠' => 'comprehension',
            '어려워' => 'difficulty',
            '복잡해' => 'complexity',
            '헷갈려' => 'confusion'
        ];

        foreach ($difficultyPatterns as $pattern => $type) {
            if (mb_strpos($message, $pattern) !== false) {
                $indicators[] = [
                    'type' => $type,
                    'pattern' => $pattern
                ];
            }
        }

        // 동기 저하 지표
        $motivationPatterns = [
            '하기 싫' => 'low_motivation',
            '의미 없' => 'meaninglessness',
            '포기' => 'giving_up',
            '그만' => 'stopping'
        ];

        foreach ($motivationPatterns as $pattern => $type) {
            if (mb_strpos($message, $pattern) !== false) {
                $indicators[] = [
                    'type' => $type,
                    'pattern' => $pattern
                ];
            }
        }

        return $indicators;
    }

    /**
     * 참여도 평가
     *
     * @param string $message 메시지
     * @return array 참여도 평가
     */
    private function assessEngagement($message) {
        $messageLength = mb_strlen($message);
        $questionMarks = substr_count($message, '?');
        $exclamationMarks = substr_count($message, '!');

        // 참여도 점수 계산
        $score = 0;

        // 메시지 길이 기반
        if ($messageLength > 50) {
            $score += 0.3;
        } elseif ($messageLength > 20) {
            $score += 0.2;
        } elseif ($messageLength > 5) {
            $score += 0.1;
        }

        // 질문 포함
        if ($questionMarks > 0) {
            $score += min($questionMarks * 0.15, 0.3);
        }

        // 표현력
        if ($exclamationMarks > 0) {
            $score += 0.1;
        }

        // 구체적 언급 확인
        if (preg_match('/[0-9]+/', $message)) {
            $score += 0.1; // 숫자 언급 (점수, 시간 등)
        }

        $level = 'low';
        if ($score >= 0.6) {
            $level = 'high';
        } elseif ($score >= 0.3) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'score' => min($score, 1.0),
            'indicators' => [
                'message_length' => $messageLength,
                'questions' => $questionMarks,
                'exclamations' => $exclamationMarks
            ]
        ];
    }

    /**
     * 신뢰도 계산
     *
     * @param array $result 분석 결과
     * @return float 신뢰도
     */
    private function calculateConfidence($result) {
        $confidence = 0;
        $factors = 0;

        // 의도 감지 신뢰도
        if (!empty($result['intent']['all'])) {
            $confidence += $result['intent']['all'][0]['confidence'] ?? 0;
            $factors++;
        }

        // 감정 감지 신뢰도
        if (!empty($result['emotions']['all'])) {
            $confidence += $result['emotions']['all'][0]['intensity'] ?? 0;
            $factors++;
        }

        // 키워드 추출 수
        if (count($result['keywords']) > 0) {
            $confidence += min(count($result['keywords']) / 5, 1.0);
            $factors++;
        }

        // 참여도
        $confidence += $result['engagement_level']['score'];
        $factors++;

        return $factors > 0 ? $confidence / $factors : 0;
    }

    /**
     * AI 분석 (OpenAI API 사용)
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return array AI 분석 결과
     */
    private function analyzeWithAI($message, $context) {
        if (!$this->aiEnabled) {
            return [];
        }

        try {
            $apiKey = $this->aiConfig['openai_api_key'];
            $model = $this->aiConfig['models']['nlu'] ?? 'gpt-4o-mini';

            $prompt = $this->buildAnalysisPrompt($message, $context);

            $response = $this->callOpenAI($apiKey, $model, $prompt);

            if ($response) {
                return json_decode($response, true) ?? [];
            }

        } catch (Exception $e) {
            error_log("AI analysis failed: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
        }

        return [];
    }

    /**
     * 분석 프롬프트 생성
     */
    private function buildAnalysisPrompt($message, $context) {
        return <<<PROMPT
다음 학생 메시지를 분석하여 JSON 형식으로 응답해주세요.

학생 메시지: "{$message}"

분석 항목:
1. intent: 주요 의도 (help_request, problem_report, clarification, expression 중 하나)
2. primary_emotion: 주요 감정 (frustration, anxiety, confusion, motivation 등)
3. problem_indicators: 학습 문제 지표 배열
4. suggested_approach: 권장 접근 방식

JSON 형식으로만 응답:
PROMPT;
    }

    /**
     * OpenAI API 호출
     */
    private function callOpenAI($apiKey, $model, $prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a student message analyzer. Respond only in JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? null;
        }

        return null;
    }

    /**
     * 결과 병합
     */
    private function mergeResults($ruleResult, $aiResult) {
        if (empty($aiResult)) {
            return $ruleResult;
        }

        // AI 결과로 보강
        if (!empty($aiResult['intent'])) {
            $ruleResult['ai_intent'] = $aiResult['intent'];
        }

        if (!empty($aiResult['primary_emotion'])) {
            $ruleResult['ai_emotion'] = $aiResult['primary_emotion'];
        }

        if (!empty($aiResult['suggested_approach'])) {
            $ruleResult['suggested_approach'] = $aiResult['suggested_approach'];
        }

        // 신뢰도 상향 조정
        $ruleResult['confidence'] = min($ruleResult['confidence'] + 0.2, 1.0);

        return $ruleResult;
    }

    /**
     * 캐시 초기화
     */
    public function clearCache() {
        $this->analysisCache = [];
    }
}
