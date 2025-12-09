<?php
/**
 * LearningActivityDetector - 학습 활동 유형 감지기
 *
 * 현재 학생의 학습 활동 유형을 감지하는 전문 클래스
 * URL, 세션 상태, 메시지 내용, 사용자 상호작용 패턴 분석
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\Engine
 * @version 1.0
 * @author Claude Code
 *
 * 지원하는 학습 활동 유형 (8가지):
 * - concept_understanding: 개념 이해 학습
 * - type_learning: 유형별 문제 학습
 * - problem_solving: 문제 풀이
 * - error_note: 오답 노트 정리
 * - qa: 질의응답
 * - review: 복습
 * - pomodoro: 뽀모도로 집중 학습
 * - home_check: 홈 체크 (과제 확인)
 */

namespace AugmentedTeacher\Agent05\PersonaSystem\Engine;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class LearningActivityDetector {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 활동 유형별 URL 패턴 */
    protected $urlPatterns = [
        'concept_understanding' => [
            '/concept/',
            '/theory/',
            '/lesson/',
            '/개념/',
            '/이론/',
            '/study/concept'
        ],
        'type_learning' => [
            '/type/',
            '/pattern/',
            '/유형/',
            '/문제유형/',
            '/study/type'
        ],
        'problem_solving' => [
            '/problem/',
            '/solve/',
            '/quiz/',
            '/test/',
            '/문제/',
            '/풀이/',
            '/시험/',
            '/exam/'
        ],
        'error_note' => [
            '/error/',
            '/wrong/',
            '/mistake/',
            '/오답/',
            '/틀린문제/',
            '/review/wrong'
        ],
        'qa' => [
            '/qa/',
            '/question/',
            '/ask/',
            '/answer/',
            '/질문/',
            '/답변/',
            '/chat/'
        ],
        'review' => [
            '/review/',
            '/recap/',
            '/summary/',
            '/복습/',
            '/정리/',
            '/다시보기/'
        ],
        'pomodoro' => [
            '/pomodoro/',
            '/focus/',
            '/timer/',
            '/뽀모도로/',
            '/집중/',
            '/타이머/'
        ],
        'home_check' => [
            '/home/',
            '/homework/',
            '/assignment/',
            '/과제/',
            '/숙제/',
            '/check/'
        ]
    ];

    /** @var array 활동 유형별 메시지 키워드 */
    protected $messageKeywords = [
        'concept_understanding' => [
            'triggers' => [
                '개념', '이론', '정의', '공식', '원리', '법칙',
                '무엇', '뭐야', '뭔가요', '설명', '의미',
                'concept', 'theory', 'definition', 'formula'
            ],
            'patterns' => [
                '/(.+)(이|가)\s*(뭐|무엇)/',
                '/(.+)(을|를)\s*설명/',
                '/(.+)(의)\s*(정의|의미|뜻)/',
                '/(어떻게|왜)\s*(.+)(되|인지|하는)/'
            ]
        ],
        'type_learning' => [
            'triggers' => [
                '유형', '패턴', '종류', '분류', '타입',
                '이런 문제', '이 유형', '비슷한', '같은 종류',
                'type', 'pattern', 'similar'
            ],
            'patterns' => [
                '/이런.{0,5}(유형|종류)/',
                '/(.+)유형.{0,5}(문제|풀이)/',
                '/(비슷|같은).{0,5}문제/'
            ]
        ],
        'problem_solving' => [
            'triggers' => [
                '문제', '풀이', '답', '정답', '해결', '계산',
                '풀어', '구하', '찾아', '계산해', '어떻게 풀',
                'problem', 'solve', 'answer', 'calculate'
            ],
            'patterns' => [
                '/(이|이런)\s*문제/',
                '/(.+)(을|를)\s*(구해|풀어|계산)/',
                '/(어떻게|어디서)\s*시작/',
                '/답(이|은|을)\s*(뭐|무엇|어떻게)/'
            ]
        ],
        'error_note' => [
            'triggers' => [
                '틀린', '오답', '실수', '왜 틀렸', '잘못',
                '다시', '또 틀', '계속 틀', '어디가 틀',
                'wrong', 'mistake', 'error', 'incorrect'
            ],
            'patterns' => [
                '/왜.{0,5}틀렸/',
                '/어디(가|서).{0,5}(틀|잘못)/',
                '/(계속|또|자꾸).{0,5}틀/',
                '/실수.{0,5}(했|한)/'
            ]
        ],
        'qa' => [
            'triggers' => [
                '질문', '궁금', '물어볼', '여쭤볼', '알려줘',
                '가르쳐', '도와줘', '설명해줘', '왜', '어떻게',
                'question', 'curious', 'help', 'explain'
            ],
            'patterns' => [
                '/(.+)(해|줘|줄래|줄 수)/',
                '/(왜|어떻게|뭐가).{0,10}\?/',
                '/알려.{0,5}(줘|주세요)/',
                '/(.+)(인가요|인지|할까요)\?/'
            ]
        ],
        'review' => [
            'triggers' => [
                '복습', '다시', '정리', '요약', '되새김',
                '기억', '잊어버', '까먹', '헷갈',
                'review', 'recap', 'summary', 'remember'
            ],
            'patterns' => [
                '/다시.{0,5}(보|풀|해)/',
                '/(복습|정리).{0,5}(하|할|해)/',
                '/(잊|까먹|헷갈).{0,5}(어|아|였)/'
            ]
        ],
        'pomodoro' => [
            'triggers' => [
                '뽀모도로', '집중', '타이머', '시간', '휴식',
                '쉬는', '쉬고', '잠깐', '몇 분',
                'pomodoro', 'focus', 'timer', 'break'
            ],
            'patterns' => [
                '/(집중|공부).{0,5}(시간|타이머)/',
                '/(휴식|쉬).{0,5}(시간|필요)/',
                '/몇.{0,3}분.{0,5}(남|됐|했)/'
            ]
        ],
        'home_check' => [
            'triggers' => [
                '과제', '숙제', '해야 할', '할 것', '마감',
                '제출', '확인', '오늘', '내일',
                'homework', 'assignment', 'due', 'submit'
            ],
            'patterns' => [
                '/(오늘|내일).{0,5}(과제|숙제)/',
                '/(과제|숙제).{0,5}(뭐|무엇|있)/',
                '/(할|해야).{0,5}(것|거).{0,5}(뭐|있)/'
            ]
        ]
    ];

    /** @var array 활동 유형별 세션 상태 지표 */
    protected $sessionIndicators = [
        'concept_understanding' => [
            'page_type' => ['concept', 'theory', 'lesson'],
            'interaction_type' => ['read', 'view', 'highlight'],
            'time_on_page' => [60, 600] // 1분~10분
        ],
        'type_learning' => [
            'page_type' => ['type', 'pattern'],
            'interaction_type' => ['browse', 'select', 'compare'],
            'sequence_length' => [3, 10] // 3~10개 문제
        ],
        'problem_solving' => [
            'page_type' => ['problem', 'quiz', 'test'],
            'interaction_type' => ['solve', 'submit', 'check'],
            'active_problem' => true
        ],
        'error_note' => [
            'page_type' => ['error', 'wrong', 'review_wrong'],
            'interaction_type' => ['analyze', 'note', 'retry'],
            'has_wrong_answers' => true
        ],
        'qa' => [
            'page_type' => ['qa', 'chat', 'question'],
            'interaction_type' => ['ask', 'chat', 'wait'],
            'is_chat_active' => true
        ],
        'review' => [
            'page_type' => ['review', 'recap', 'summary'],
            'interaction_type' => ['reread', 'retake', 'review'],
            'is_repeated_content' => true
        ],
        'pomodoro' => [
            'page_type' => ['pomodoro', 'timer', 'focus'],
            'interaction_type' => ['timer', 'focus', 'break'],
            'timer_active' => true
        ],
        'home_check' => [
            'page_type' => ['home', 'homework', 'dashboard'],
            'interaction_type' => ['check', 'view', 'submit'],
            'has_pending_tasks' => true
        ]
    ];

    /** @var array 활동 유형 메타데이터 */
    protected $activityMetadata = [
        'concept_understanding' => [
            'label' => '개념 이해',
            'description' => '새로운 수학 개념을 학습하고 이해하는 활동',
            'typical_emotions' => ['curiosity', 'confusion', 'anxiety'],
            'recommended_personas' => ['정리형', '반복형', '탐색형', '저항형']
        ],
        'type_learning' => [
            'label' => '유형 학습',
            'description' => '문제 유형을 파악하고 패턴을 익히는 활동',
            'typical_emotions' => ['curiosity', 'confidence', 'boredom'],
            'recommended_personas' => ['분석형', '직관형', '체계형', '무관심형']
        ],
        'problem_solving' => [
            'label' => '문제 풀이',
            'description' => '수학 문제를 직접 풀어보는 활동',
            'typical_emotions' => ['anxiety', 'frustration', 'achievement'],
            'recommended_personas' => ['도전형', '보조형', '완벽형', '회피형']
        ],
        'error_note' => [
            'label' => '오답 노트',
            'description' => '틀린 문제를 분석하고 정리하는 활동',
            'typical_emotions' => ['frustration', 'confusion', 'curiosity'],
            'recommended_personas' => ['분석형', '수용형', '회피형', '방어형']
        ],
        'qa' => [
            'label' => '질의응답',
            'description' => 'AI 튜터와 대화하며 질문하는 활동',
            'typical_emotions' => ['curiosity', 'confusion', 'anxiety'],
            'recommended_personas' => ['적극형', '관찰형', '의존형', '독립형']
        ],
        'review' => [
            'label' => '복습',
            'description' => '배운 내용을 다시 확인하고 정리하는 활동',
            'typical_emotions' => ['boredom', 'confidence', 'fatigue'],
            'recommended_personas' => ['계획형', '즉흥형', '완벽형', '회피형']
        ],
        'pomodoro' => [
            'label' => '뽀모도로',
            'description' => '집중 시간과 휴식을 번갈아 하는 학습 방법',
            'typical_emotions' => ['fatigue', 'achievement', 'boredom'],
            'recommended_personas' => ['몰입형', '산만형', '균형형', '과몰입형']
        ],
        'home_check' => [
            'label' => '홈 체크',
            'description' => '과제와 할 일을 확인하는 활동',
            'typical_emotions' => ['anxiety', 'achievement', 'boredom'],
            'recommended_personas' => ['계획형', '즉흥형', '성실형', '무관심형']
        ]
    ];

    /** @var array 설정 */
    protected $config = [
        'enable_url_detection' => true,
        'enable_message_detection' => true,
        'enable_session_detection' => true,
        'confidence_threshold' => 0.3,
        'fallback_activity' => 'qa'
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
     * 학습 활동 유형 감지
     *
     * @param array $context 컨텍스트 정보
     * @return array 감지된 활동 정보
     */
    public function detect(array $context): array {
        try {
            $scores = [];

            // 1. URL 기반 감지
            if ($this->config['enable_url_detection']) {
                $urlScores = $this->detectByUrl($context['current_url'] ?? '');
                $scores = $this->mergeScores($scores, $urlScores, 0.4);
            }

            // 2. 메시지 기반 감지
            if ($this->config['enable_message_detection']) {
                $messageScores = $this->detectByMessage($context['message'] ?? '');
                $scores = $this->mergeScores($scores, $messageScores, 0.35);
            }

            // 3. 세션 상태 기반 감지
            if ($this->config['enable_session_detection']) {
                $sessionScores = $this->detectBySession($context);
                $scores = $this->mergeScores($scores, $sessionScores, 0.25);
            }

            // 4. 최고 점수 활동 선택
            $detectedActivity = $this->selectActivity($scores);

            // 5. 메타데이터 추가
            $metadata = $this->activityMetadata[$detectedActivity['type']] ?? [];

            return [
                'success' => true,
                'activity_type' => $detectedActivity['type'],
                'confidence' => $detectedActivity['confidence'],
                'label' => $metadata['label'] ?? $detectedActivity['type'],
                'description' => $metadata['description'] ?? '',
                'typical_emotions' => $metadata['typical_emotions'] ?? [],
                'recommended_personas' => $metadata['recommended_personas'] ?? [],
                'all_scores' => $scores,
                'detection_sources' => [
                    'url' => $this->config['enable_url_detection'],
                    'message' => $this->config['enable_message_detection'],
                    'session' => $this->config['enable_session_detection']
                ]
            ];

        } catch (\Exception $e) {
            $this->logError("활동 감지 실패: " . $e->getMessage(), __LINE__);

            return [
                'success' => false,
                'activity_type' => $this->config['fallback_activity'],
                'confidence' => 0.0,
                'label' => '질의응답',
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . __LINE__
            ];
        }
    }

    /**
     * URL 기반 활동 감지
     *
     * @param string $url 현재 URL
     * @return array 활동별 점수
     */
    protected function detectByUrl(string $url): array {
        $scores = [];

        if (empty($url)) {
            return $scores;
        }

        $normalizedUrl = mb_strtolower($url);

        foreach ($this->urlPatterns as $activity => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_strpos($normalizedUrl, $pattern) !== false) {
                    $scores[$activity] = ($scores[$activity] ?? 0) + 1.0;
                }
            }
        }

        // 정규화 (최대 1.0)
        foreach ($scores as $activity => &$score) {
            $score = min(1.0, $score);
        }

        return $scores;
    }

    /**
     * 메시지 기반 활동 감지
     *
     * @param string $message 사용자 메시지
     * @return array 활동별 점수
     */
    protected function detectByMessage(string $message): array {
        $scores = [];

        if (empty($message)) {
            return $scores;
        }

        $normalizedMessage = mb_strtolower($message);

        foreach ($this->messageKeywords as $activity => $data) {
            $score = 0.0;

            // 키워드 매칭
            foreach ($data['triggers'] as $keyword) {
                if (mb_strpos($normalizedMessage, $keyword) !== false) {
                    $score += 0.3;
                }
            }

            // 패턴 매칭
            foreach ($data['patterns'] as $pattern) {
                if (preg_match($pattern . 'u', $normalizedMessage)) {
                    $score += 0.5;
                }
            }

            if ($score > 0) {
                $scores[$activity] = min(1.0, $score);
            }
        }

        return $scores;
    }

    /**
     * 세션 상태 기반 활동 감지
     *
     * @param array $context 컨텍스트 정보
     * @return array 활동별 점수
     */
    protected function detectBySession(array $context): array {
        $scores = [];

        $pageType = $context['page_type'] ?? '';
        $interactionType = $context['interaction_type'] ?? '';
        $sessionData = $context['session'] ?? [];

        foreach ($this->sessionIndicators as $activity => $indicators) {
            $score = 0.0;
            $matchCount = 0;

            // 페이지 타입 체크
            if (!empty($pageType) && isset($indicators['page_type'])) {
                if (in_array($pageType, $indicators['page_type'])) {
                    $score += 0.4;
                    $matchCount++;
                }
            }

            // 상호작용 타입 체크
            if (!empty($interactionType) && isset($indicators['interaction_type'])) {
                if (in_array($interactionType, $indicators['interaction_type'])) {
                    $score += 0.3;
                    $matchCount++;
                }
            }

            // 특수 조건 체크
            if (isset($indicators['active_problem']) && !empty($sessionData['active_problem'])) {
                $score += 0.3;
                $matchCount++;
            }

            if (isset($indicators['has_wrong_answers']) && !empty($sessionData['wrong_count'])) {
                $score += 0.3;
                $matchCount++;
            }

            if (isset($indicators['timer_active']) && !empty($sessionData['timer_active'])) {
                $score += 0.4;
                $matchCount++;
            }

            if (isset($indicators['is_chat_active']) && !empty($context['is_chat'])) {
                $score += 0.3;
                $matchCount++;
            }

            if ($matchCount > 0) {
                $scores[$activity] = min(1.0, $score);
            }
        }

        return $scores;
    }

    /**
     * 점수 병합
     *
     * @param array $existing 기존 점수
     * @param array $new 새 점수
     * @param float $weight 가중치
     * @return array 병합된 점수
     */
    protected function mergeScores(array $existing, array $new, float $weight): array {
        foreach ($new as $activity => $score) {
            $weightedScore = $score * $weight;
            $existing[$activity] = ($existing[$activity] ?? 0) + $weightedScore;
        }

        return $existing;
    }

    /**
     * 최종 활동 선택
     *
     * @param array $scores 점수 배열
     * @return array 선택된 활동 정보
     */
    protected function selectActivity(array $scores): array {
        if (empty($scores)) {
            return [
                'type' => $this->config['fallback_activity'],
                'confidence' => 0.0
            ];
        }

        // 최고 점수 찾기
        $maxScore = max($scores);
        $maxActivity = array_search($maxScore, $scores);

        // 신뢰도 임계값 체크
        if ($maxScore < $this->config['confidence_threshold']) {
            return [
                'type' => $this->config['fallback_activity'],
                'confidence' => $maxScore
            ];
        }

        return [
            'type' => $maxActivity,
            'confidence' => round($maxScore, 2)
        ];
    }

    /**
     * 활동 유형 정보 반환
     *
     * @param string $activityType 활동 유형
     * @return array 활동 정보
     */
    public function getActivityInfo(string $activityType): array {
        if (!isset($this->activityMetadata[$activityType])) {
            return [
                'type' => $activityType,
                'label' => $activityType,
                'description' => 'Unknown activity type',
                'valid' => false
            ];
        }

        $metadata = $this->activityMetadata[$activityType];

        return [
            'type' => $activityType,
            'label' => $metadata['label'],
            'description' => $metadata['description'],
            'typical_emotions' => $metadata['typical_emotions'],
            'recommended_personas' => $metadata['recommended_personas'],
            'valid' => true
        ];
    }

    /**
     * 모든 활동 유형 목록 반환
     *
     * @return array 활동 유형 목록
     */
    public function getAllActivityTypes(): array {
        $types = [];

        foreach ($this->activityMetadata as $type => $metadata) {
            $types[] = [
                'type' => $type,
                'label' => $metadata['label'],
                'description' => $metadata['description']
            ];
        }

        return $types;
    }

    /**
     * 활동 전환 감지
     *
     * @param string $previousActivity 이전 활동
     * @param string $currentActivity 현재 활동
     * @return array 전환 정보
     */
    public function detectActivityTransition(string $previousActivity, string $currentActivity): array {
        $isTransition = ($previousActivity !== $currentActivity);

        if (!$isTransition) {
            return [
                'transition' => false,
                'type' => 'same_activity'
            ];
        }

        // 전환 유형 분류
        $transitionType = $this->classifyTransition($previousActivity, $currentActivity);

        return [
            'transition' => true,
            'from' => $previousActivity,
            'to' => $currentActivity,
            'from_label' => $this->activityMetadata[$previousActivity]['label'] ?? $previousActivity,
            'to_label' => $this->activityMetadata[$currentActivity]['label'] ?? $currentActivity,
            'type' => $transitionType,
            'suggested_response' => $this->getTransitionResponse($transitionType)
        ];
    }

    /**
     * 전환 유형 분류
     *
     * @param string $from 이전 활동
     * @param string $to 현재 활동
     * @return string 전환 유형
     */
    protected function classifyTransition(string $from, string $to): string {
        // 자연스러운 학습 흐름
        $naturalFlows = [
            'concept_understanding' => ['type_learning', 'problem_solving', 'qa'],
            'type_learning' => ['problem_solving', 'error_note'],
            'problem_solving' => ['error_note', 'review', 'qa'],
            'error_note' => ['review', 'problem_solving'],
            'review' => ['problem_solving', 'type_learning'],
            'pomodoro' => ['problem_solving', 'review', 'concept_understanding'],
            'home_check' => ['problem_solving', 'review', 'concept_understanding']
        ];

        if (isset($naturalFlows[$from]) && in_array($to, $naturalFlows[$from])) {
            return 'natural';
        }

        // 도움 요청
        if ($to === 'qa') {
            return 'help_seeking';
        }

        // 휴식 관련
        if ($to === 'pomodoro') {
            return 'break_needed';
        }

        // 확인 관련
        if ($to === 'home_check') {
            return 'task_checking';
        }

        return 'context_switch';
    }

    /**
     * 전환에 대한 응답 제안
     *
     * @param string $transitionType 전환 유형
     * @return string 제안 응답
     */
    protected function getTransitionResponse(string $transitionType): string {
        $responses = [
            'natural' => '좋아요! 자연스럽게 다음 단계로 넘어가셨네요.',
            'help_seeking' => '궁금한 점이 있으신가요? 편하게 질문해주세요!',
            'break_needed' => '잠시 휴식을 취하시는군요. 집중력 관리가 중요해요!',
            'task_checking' => '과제를 확인하시는군요. 계획적인 학습이 좋아요!',
            'context_switch' => '다른 활동으로 전환하셨네요. 필요한 것이 있으면 말씀해주세요.'
        ];

        return $responses[$transitionType] ?? $responses['context_switch'];
    }

    /**
     * 활동 지속 시간 분석
     *
     * @param string $activityType 활동 유형
     * @param int $durationSeconds 지속 시간 (초)
     * @return array 지속 시간 분석 결과
     */
    public function analyzeDuration(string $activityType, int $durationSeconds): array {
        // 활동별 권장 시간 (초)
        $recommendedDurations = [
            'concept_understanding' => ['min' => 180, 'optimal' => 600, 'max' => 1200],
            'type_learning' => ['min' => 300, 'optimal' => 900, 'max' => 1800],
            'problem_solving' => ['min' => 120, 'optimal' => 1200, 'max' => 2400],
            'error_note' => ['min' => 180, 'optimal' => 600, 'max' => 1200],
            'qa' => ['min' => 60, 'optimal' => 300, 'max' => 900],
            'review' => ['min' => 300, 'optimal' => 900, 'max' => 1800],
            'pomodoro' => ['min' => 1500, 'optimal' => 1500, 'max' => 1500], // 25분
            'home_check' => ['min' => 60, 'optimal' => 180, 'max' => 600]
        ];

        $recommended = $recommendedDurations[$activityType] ?? [
            'min' => 60, 'optimal' => 300, 'max' => 900
        ];

        // 상태 결정
        $status = 'normal';
        if ($durationSeconds < $recommended['min']) {
            $status = 'too_short';
        } elseif ($durationSeconds > $recommended['max']) {
            $status = 'too_long';
        } elseif ($durationSeconds >= $recommended['optimal'] * 0.8 &&
                  $durationSeconds <= $recommended['optimal'] * 1.2) {
            $status = 'optimal';
        }

        return [
            'activity_type' => $activityType,
            'duration_seconds' => $durationSeconds,
            'duration_formatted' => $this->formatDuration($durationSeconds),
            'status' => $status,
            'recommended' => $recommended,
            'suggestion' => $this->getDurationSuggestion($status, $activityType)
        ];
    }

    /**
     * 시간 포맷팅
     *
     * @param int $seconds 초
     * @return string 포맷된 시간
     */
    protected function formatDuration(int $seconds): string {
        if ($seconds < 60) {
            return $seconds . '초';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $minutes . '분' . ($secs > 0 ? ' ' . $secs . '초' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . '시간' . ($minutes > 0 ? ' ' . $minutes . '분' : '');
        }
    }

    /**
     * 지속 시간에 대한 제안
     *
     * @param string $status 상태
     * @param string $activityType 활동 유형
     * @return string 제안 메시지
     */
    protected function getDurationSuggestion(string $status, string $activityType): string {
        $suggestions = [
            'too_short' => [
                'concept_understanding' => '개념을 충분히 이해하려면 조금 더 시간을 들여보세요.',
                'problem_solving' => '문제를 너무 빨리 포기하지 마세요. 조금 더 생각해보면 어떨까요?',
                'default' => '조금 더 시간을 들여보면 좋을 것 같아요.'
            ],
            'too_long' => [
                'concept_understanding' => '개념 이해에 오래 걸리고 있네요. 다른 방식으로 접근해볼까요?',
                'problem_solving' => '오래 고민하셨네요. 잠시 쉬었다가 다시 도전해보는 건 어떨까요?',
                'default' => '오래 진행하셨네요. 잠시 휴식이 필요할 수도 있어요.'
            ],
            'optimal' => '좋은 페이스로 학습하고 계시네요!',
            'normal' => '학습을 잘 진행하고 계시네요.'
        ];

        if ($status === 'optimal' || $status === 'normal') {
            return $suggestions[$status];
        }

        return $suggestions[$status][$activityType] ?? $suggestions[$status]['default'];
    }

    /**
     * 에러 로깅
     *
     * @param string $message 에러 메시지
     * @param int $line 라인 번호
     */
    protected function logError(string $message, int $line): void {
        error_log("[LearningActivityDetector ERROR] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 관련 DB 테이블:
 * - at_learning_activity
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - activity_type: varchar(50) NOT NULL
 *   - page_url: varchar(500)
 *   - start_time: bigint(10) NOT NULL
 *   - end_time: bigint(10)
 *   - duration_seconds: int(10)
 *   - metadata: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *
 * - at_learning_session
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - session_token: varchar(255)
 *   - current_activity: varchar(50)
 *   - session_data: longtext (JSON)
 *   - started_at: bigint(10) NOT NULL
 *   - last_active: bigint(10) NOT NULL
 *
 * 참조 파일:
 * - agents/agent05_learning_emotion/persona_system/engine/Agent05PersonaEngine.php
 * - agents/agent05_learning_emotion/persona_system/engine/EmotionAnalyzer.php
 */
