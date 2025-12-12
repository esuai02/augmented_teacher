<?php
/**
 * Agent12PersonaEngine.php
 *
 * 휴식 루틴 에이전트 페르소나 엔진
 * 학생의 휴식 패턴 분석 및 최적화 전략 생성
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent12RestRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../engine_core/base/AbstractPersonaEngine.php');
require_once(__DIR__ . '/Agent12DataContext.php');

/**
 * Agent12 휴식 루틴 페르소나 엔진
 *
 * 4개 휴식 패턴 기반 페르소나 식별:
 * - regular_rest: 정기적 휴식형 (평균 ≤60분)
 * - activity_centered_rest: 활동 중심 휴식형 (60-90분)
 * - immersive_rest: 집중 몰입형/비계획형 (≥90분)
 * - no_rest: 휴식 없음형 (휴식 버튼 미클릭)
 */
class Agent12PersonaEngine extends AbstractPersonaEngine
{
    /** @var int 에이전트 번호 */
    protected $nagent = 12;

    /** @var string 에이전트 이름 */
    protected $agentName = 'rest_routine';

    /** @var string 에이전트 한글명 */
    protected $agentKrName = '휴식루틴';

    /** @var Agent12DataContext 데이터 컨텍스트 */
    protected $dataContext;

    /** @var array 휴식 패턴 레벨 정의 */
    protected $restPatternLevels = [
        'regular_rest' => [
            'label' => '정기적 휴식형',
            'avg_interval_max' => 60,
            'avg_interval_min' => 0,
            'priority' => 1,
            'fatigue_risk' => 'low'
        ],
        'activity_centered_rest' => [
            'label' => '활동 중심 휴식형',
            'avg_interval_max' => 90,
            'avg_interval_min' => 60,
            'priority' => 2,
            'fatigue_risk' => 'medium'
        ],
        'immersive_rest' => [
            'label' => '집중 몰입형',
            'avg_interval_max' => 9999,
            'avg_interval_min' => 90,
            'priority' => 3,
            'fatigue_risk' => 'high'
        ],
        'no_rest' => [
            'label' => '휴식 없음형',
            'avg_interval_max' => null,
            'avg_interval_min' => null,
            'priority' => 4,
            'fatigue_risk' => 'critical'
        ]
    ];

    /** @var array 휴식 전략 매핑 */
    protected $restStrategies = [
        'regular_rest' => [
            'interval_recommendation' => 50,
            'break_duration' => 10,
            'mode' => 'maintain',
            'description' => '현재 패턴 유지 - 규칙적 휴식 습관 강화',
            'coaching_tone' => 'supportive'
        ],
        'activity_centered_rest' => [
            'interval_recommendation' => 60,
            'break_duration' => 10,
            'mode' => 'optimize',
            'description' => '휴식 주기 최적화 - 집중력 유지 개선',
            'coaching_tone' => 'encouraging'
        ],
        'immersive_rest' => [
            'interval_recommendation' => 60,
            'break_duration' => 15,
            'mode' => 'intervene',
            'description' => '정기 휴식 유도 - 피로 누적 방지',
            'coaching_tone' => 'gentle_alert'
        ],
        'no_rest' => [
            'interval_recommendation' => 45,
            'break_duration' => 10,
            'mode' => 'urgent_intervene',
            'description' => '즉시 휴식 권장 - 번아웃 위험 경고',
            'coaching_tone' => 'urgent'
        ]
    ];

    /** @var array 피로도 임계값 */
    protected $fatigueThresholds = [
        'low' => 30,
        'medium' => 50,
        'high' => 70,
        'critical' => 85
    ];

    /**
     * 에이전트 초기화
     *
     * @return void
     */
    protected function onInitialize(): void
    {
        // DataContext 초기화
        if ($this->dataContext === null) {
            $this->dataContext = new Agent12DataContext($this->db);
        }

        // 로그
        $this->log('info', 'Agent12 RestRoutine PersonaEngine initialized', [
            'rest_patterns' => count($this->restPatternLevels)
        ]);
    }

    /**
     * 페르소나 식별 로직
     * 휴식 패턴 기반 페르소나 결정
     *
     * @param int $userId 사용자 ID
     * @param array $contextData 컨텍스트 데이터
     * @param array|null $currentState 현재 상태
     * @return array 식별된 페르소나 정보
     */
    protected function doIdentifyPersona(int $userId, array $contextData, ?array $currentState): array
    {
        // 휴식 데이터 조회
        $restData = $this->dataContext->getRestStatistics($userId);

        // 휴식 기록이 없는 경우
        if (!$restData || $restData['total_sessions'] == 0) {
            return [
                'persona_code' => 'no_rest',
                'confidence' => 0.8,
                'pattern_level' => 'no_rest',
                'rest_stats' => null,
                'fatigue_index' => $this->calculateFatigueIndex($userId, null),
                'strategy' => $this->restStrategies['no_rest']
            ];
        }

        // 평균 휴식 간격 기반 페르소나 결정
        $avgInterval = $restData['avg_interval_minutes'];
        $patternLevel = $this->determinePatternLevel($avgInterval, $restData);

        // 신뢰도 계산
        $confidence = $this->calculateConfidence($restData, $contextData);

        // 피로도 지수 계산
        $fatigueIndex = $this->calculateFatigueIndex($userId, $restData);

        // 휴식 전략 결정
        $strategy = $this->getRestStrategy($patternLevel, $fatigueIndex, $restData);

        return [
            'persona_code' => $patternLevel,
            'confidence' => $confidence,
            'pattern_level' => $patternLevel,
            'rest_stats' => [
                'total_sessions' => $restData['total_sessions'],
                'avg_interval' => $avgInterval,
                'avg_duration' => $restData['avg_duration_minutes'],
                'recent_rest_count' => isset($restData['recent_count']) ? $restData['recent_count'] : 0,
                'last_rest_time' => isset($restData['last_rest_time']) ? $restData['last_rest_time'] : null
            ],
            'fatigue_index' => $fatigueIndex,
            'strategy' => $strategy
        ];
    }

    /**
     * 응답 생성 로직
     *
     * @param int $userId 사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $userMessage 사용자 메시지
     * @param array $options 옵션
     * @return array 생성된 응답
     */
    protected function doGenerateResponse(int $userId, string $personaCode, string $userMessage, array $options): array
    {
        // 현재 상태 조회
        $currentState = $this->getPersonaState($userId);
        $restStats = isset($currentState['rest_stats']) ? $currentState['rest_stats'] : null;
        $strategy = isset($currentState['strategy']) ? $currentState['strategy'] : null;
        $fatigueIndex = isset($currentState['fatigue_index']) ? $currentState['fatigue_index'] : 0;

        // 메시지 의도 분석
        $intent = $this->analyzeIntent($userMessage);

        // 응답 템플릿 선택
        $template = $this->selectResponseTemplate($personaCode, $intent, $fatigueIndex);

        // 응답 생성
        $response = $this->buildResponse($template, [
            'user_id' => $userId,
            'persona_code' => $personaCode,
            'rest_stats' => $restStats,
            'strategy' => $strategy,
            'fatigue_index' => $fatigueIndex,
            'intent' => $intent,
            'user_message' => $userMessage
        ]);

        return [
            'message' => $response['message'],
            'suggestions' => isset($response['suggestions']) ? $response['suggestions'] : [],
            'actions' => isset($response['actions']) ? $response['actions'] : [],
            'metadata' => [
                'persona_code' => $personaCode,
                'intent' => $intent,
                'fatigue_index' => $fatigueIndex,
                'template_used' => isset($template['id']) ? $template['id'] : 'default'
            ]
        ];
    }

    /**
     * 페르소나 전환 후 처리
     *
     * @param int $userId 사용자 ID
     * @param string $fromPersona 이전 페르소나
     * @param string $toPersona 새 페르소나
     * @param array $triggerData 트리거 데이터
     * @return void
     */
    protected function onTransition(int $userId, string $fromPersona, string $toPersona, array $triggerData): void
    {
        // 전환 이벤트 발행 (다른 에이전트에 알림)
        if ($this->communicator) {
            $this->communicator->publish([
                'type' => 'rest_pattern_changed',
                'from_agent' => $this->nagent,
                'to_agent' => 0, // broadcast
                'payload' => [
                    'user_id' => $userId,
                    'from_pattern' => $fromPersona,
                    'to_pattern' => $toPersona,
                    'fatigue_risk' => $this->restPatternLevels[$toPersona]['fatigue_risk'],
                    'trigger' => $triggerData
                ]
            ]);
        }

        // 전환 로그
        $this->log('info', 'Rest pattern transition', [
            'user_id' => $userId,
            'from' => $fromPersona,
            'to' => $toPersona
        ]);

        // 위험 상태 전환 시 다른 에이전트에 알림
        if (in_array($toPersona, ['immersive_rest', 'no_rest'])) {
            $this->notifyHighFatigueRisk($userId, $toPersona, $triggerData);
        }
    }

    /**
     * 건강 체크 로직
     *
     * @return array 건강 상태 정보
     */
    protected function doHealthCheck(): array
    {
        $issues = [];

        // DataContext 체크
        if (!$this->dataContext) {
            $issues[] = 'DataContext not initialized';
        }

        // DB 연결 체크
        try {
            $testQuery = $this->dataContext->testConnection();
            if (!$testQuery) {
                $issues[] = 'Database connection failed';
            }
        } catch (Exception $e) {
            $issues[] = 'Database error: ' . $e->getMessage();
        }

        // Rules 파일 체크
        $rulesPath = $this->getRulesFilePath();
        if (!file_exists($rulesPath)) {
            $issues[] = 'Rules file not found: ' . $rulesPath;
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'metrics' => [
                'rest_patterns' => count($this->restPatternLevels),
                'strategies' => count($this->restStrategies)
            ]
        ];
    }

    /**
     * Rules 파일 경로 반환
     *
     * @return string
     */
    protected function getRulesFilePath(): string
    {
        return __DIR__ . '/../rules/rules.yaml';
    }

    // =========================================================================
    // Private Helper Methods
    // =========================================================================

    /**
     * 휴식 패턴 레벨 결정
     *
     * @param float $avgInterval 평균 휴식 간격 (분)
     * @param array $restData 휴식 데이터
     * @return string 패턴 레벨 코드
     */
    private function determinePatternLevel(float $avgInterval, array $restData): string
    {
        // 휴식 기록이 없거나 너무 적으면 no_rest
        if ($restData['total_sessions'] < 3) {
            return 'no_rest';
        }

        // 평균 간격 기반 분류
        if ($avgInterval <= 60) {
            return 'regular_rest';
        } elseif ($avgInterval <= 90) {
            return 'activity_centered_rest';
        } else {
            return 'immersive_rest';
        }
    }

    /**
     * 신뢰도 계산
     *
     * @param array $restData 휴식 데이터
     * @param array $contextData 컨텍스트 데이터
     * @return float 신뢰도 (0.0 - 1.0)
     */
    private function calculateConfidence(array $restData, array $contextData): float
    {
        $confidence = 0.5; // 기본값

        // 세션 수에 따른 신뢰도 증가
        $sessions = $restData['total_sessions'];
        if ($sessions >= 10) {
            $confidence += 0.2;
        } elseif ($sessions >= 5) {
            $confidence += 0.1;
        }

        // 최근 데이터 존재 시 +0.1
        if (isset($restData['recent_count']) && $restData['recent_count'] > 0) {
            $confidence += 0.1;
        }

        // 감정 데이터 연동 시 +0.1
        if (isset($contextData['has_emotion_data']) && $contextData['has_emotion_data']) {
            $confidence += 0.1;
        }

        // 바이오 데이터 연동 시 +0.1
        if (isset($contextData['has_bio_data']) && $contextData['has_bio_data']) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }

    /**
     * 피로도 지수 계산
     *
     * @param int $userId 사용자 ID
     * @param array|null $restData 휴식 데이터
     * @return float 피로도 지수 (0-100)
     */
    private function calculateFatigueIndex(int $userId, ?array $restData): float
    {
        $baseIndex = 30; // 기본 피로도

        if (!$restData) {
            return 80; // 데이터 없으면 높은 피로도 가정
        }

        // 마지막 휴식으로부터 경과 시간
        if (isset($restData['last_rest_time'])) {
            $lastRest = strtotime($restData['last_rest_time']);
            $minutesSinceRest = (time() - $lastRest) / 60;

            // 60분마다 피로도 10 증가
            $baseIndex += min(50, ($minutesSinceRest / 60) * 10);
        } else {
            $baseIndex += 40;
        }

        // 평균 간격이 길수록 피로도 증가
        if (isset($restData['avg_interval_minutes'])) {
            $avgInterval = $restData['avg_interval_minutes'];
            if ($avgInterval > 90) {
                $baseIndex += 15;
            } elseif ($avgInterval > 60) {
                $baseIndex += 10;
            }
        }

        // 최근 학습 세션 연속 시간 고려 (다른 에이전트 데이터)
        $sessionData = $this->dataContext->getActiveSessionDuration($userId);
        if ($sessionData && $sessionData > 120) {
            $baseIndex += min(20, ($sessionData - 120) / 30 * 5);
        }

        return min(100, $baseIndex);
    }

    /**
     * 휴식 전략 결정
     *
     * @param string $patternLevel 패턴 레벨
     * @param float $fatigueIndex 피로도 지수
     * @param array $restData 휴식 데이터
     * @return array 휴식 전략
     */
    private function getRestStrategy(string $patternLevel, float $fatigueIndex, array $restData): array
    {
        $baseStrategy = $this->restStrategies[$patternLevel];

        // 피로도에 따른 조정
        if ($fatigueIndex >= $this->fatigueThresholds['critical']) {
            $baseStrategy['urgent_rest'] = true;
            $baseStrategy['break_duration'] = 20;
            $baseStrategy['coaching_tone'] = 'urgent';
            $baseStrategy['adjustment'] = 'critical_fatigue';
        } elseif ($fatigueIndex >= $this->fatigueThresholds['high']) {
            $baseStrategy['break_duration'] = 15;
            $baseStrategy['coaching_tone'] = 'gentle_alert';
            $baseStrategy['adjustment'] = 'high_fatigue';
        }

        // 휴식 효과성 데이터 반영
        if (isset($restData['rest_effectiveness']) && $restData['rest_effectiveness'] < 0.5) {
            $baseStrategy['quality_focus'] = true;
            $baseStrategy['suggestion'] = '휴식의 질을 높이세요';
        }

        return $baseStrategy;
    }

    /**
     * 메시지 의도 분석
     *
     * @param string $message 사용자 메시지
     * @return string 의도 코드
     */
    private function analyzeIntent(string $message): string
    {
        $intents = [
            'fatigue_query' => ['피곤', '지쳐', '힘들', '졸려', '피로', '눈'],
            'rest_request' => ['쉬고', '휴식', '쉴래', '잠깐', '멈춰'],
            'status_query' => ['언제', '얼마나', '몇 번', '휴식 기록', '패턴'],
            'strategy_query' => ['어떻게', '방법', '추천', '언제 쉬어'],
            'efficiency_query' => ['효과', '효율', '집중', '회복'],
            'schedule_query' => ['다음', '예정', '계획']
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * 응답 템플릿 선택
     *
     * @param string $personaCode 페르소나 코드
     * @param string $intent 의도
     * @param float $fatigueIndex 피로도 지수
     * @return array 템플릿
     */
    private function selectResponseTemplate(string $personaCode, string $intent, float $fatigueIndex): array
    {
        // 긴급 피로 상태 템플릿
        if ($fatigueIndex >= $this->fatigueThresholds['critical']) {
            return [
                'id' => 'critical_fatigue',
                'tone' => 'urgent',
                'prefix' => '🔴 피로도가 매우 높아요!'
            ];
        }

        // 페르소나별 기본 템플릿
        $templates = [
            'regular_rest' => [
                'fatigue_query' => [
                    'id' => 'regular_fatigue',
                    'tone' => 'supportive',
                    'prefix' => '✅ 규칙적인 휴식 습관이 좋아요!'
                ],
                'status_query' => [
                    'id' => 'regular_status',
                    'tone' => 'encouraging',
                    'prefix' => '📊 휴식 패턴을 분석해봤어요.'
                ]
            ],
            'activity_centered_rest' => [
                'fatigue_query' => [
                    'id' => 'activity_fatigue',
                    'tone' => 'encouraging',
                    'prefix' => '💡 조금 더 자주 쉬어도 좋아요.'
                ],
                'rest_request' => [
                    'id' => 'activity_rest',
                    'tone' => 'supportive',
                    'prefix' => '👍 휴식하기 좋은 타이밍이에요!'
                ]
            ],
            'immersive_rest' => [
                'fatigue_query' => [
                    'id' => 'immersive_fatigue',
                    'tone' => 'gentle_alert',
                    'prefix' => '⚠️ 집중은 좋지만, 휴식도 중요해요.'
                ],
                'rest_request' => [
                    'id' => 'immersive_rest',
                    'tone' => 'encouraging',
                    'prefix' => '🌟 잘했어요! 휴식을 취해봐요.'
                ]
            ],
            'no_rest' => [
                'fatigue_query' => [
                    'id' => 'norest_fatigue',
                    'tone' => 'urgent',
                    'prefix' => '🚨 휴식 없이 공부 중이네요!'
                ],
                'rest_request' => [
                    'id' => 'norest_rest',
                    'tone' => 'supportive',
                    'prefix' => '💪 지금 쉬는 건 정말 좋은 선택이에요!'
                ]
            ]
        ];

        // 해당 템플릿 반환
        if (isset($templates[$personaCode][$intent])) {
            return $templates[$personaCode][$intent];
        }

        // 기본 템플릿
        return [
            'id' => 'default',
            'tone' => 'neutral',
            'prefix' => ''
        ];
    }

    /**
     * 응답 빌드
     *
     * @param array $template 템플릿
     * @param array $data 데이터
     * @return array 응답
     */
    private function buildResponse(array $template, array $data): array
    {
        $message = '';
        $suggestions = [];
        $actions = [];

        // 프리픽스 추가
        if (!empty($template['prefix'])) {
            $message = $template['prefix'] . "\n\n";
        }

        // 피로도 표시
        if ($data['fatigue_index'] !== null) {
            $fatigueLevel = $this->getFatigueLevel($data['fatigue_index']);
            $message .= "😰 피로도: {$fatigueLevel} ({$data['fatigue_index']}%)\n";
        }

        // 휴식 통계 표시
        if ($data['rest_stats']) {
            $stats = $data['rest_stats'];
            $message .= "📈 평균 휴식 간격: {$stats['avg_interval']}분\n";

            if ($stats['last_rest_time']) {
                $lastRestAgo = $this->getTimeAgo($stats['last_rest_time']);
                $message .= "⏱️ 마지막 휴식: {$lastRestAgo}\n";
            }
        }

        // 전략 정보 표시
        if ($data['strategy'] && $data['intent'] !== 'status_query') {
            $strategy = $data['strategy'];
            $message .= "\n💡 권장 사항: {$strategy['description']}\n";
            $message .= "   휴식 권장 주기: {$strategy['interval_recommendation']}분\n";
            $message .= "   휴식 시간: {$strategy['break_duration']}분\n";
        }

        // 의도별 추가 응답
        switch ($data['intent']) {
            case 'rest_request':
                $suggestions = ['5분 휴식', '10분 휴식', '15분 휴식'];
                $actions = ['start_rest_timer'];
                break;

            case 'strategy_query':
                $suggestions = ['휴식 패턴 분석', '최적 휴식 시간', '피로도 추적'];
                break;

            case 'efficiency_query':
                $suggestions = ['휴식 효과 보기', '집중력 변화', '회복 패턴'];
                break;

            case 'fatigue_query':
                if ($data['fatigue_index'] >= $this->fatigueThresholds['high']) {
                    $suggestions = ['지금 휴식하기', '가벼운 스트레칭', '눈 휴식'];
                    $actions = ['suggest_rest'];
                }
                break;
        }

        return [
            'message' => trim($message),
            'suggestions' => $suggestions,
            'actions' => $actions
        ];
    }

    /**
     * 피로도 레벨 텍스트 반환
     *
     * @param float $fatigueIndex 피로도 지수
     * @return string 레벨 텍스트
     */
    private function getFatigueLevel(float $fatigueIndex): string
    {
        if ($fatigueIndex >= $this->fatigueThresholds['critical']) {
            return '매우 높음 🔴';
        } elseif ($fatigueIndex >= $this->fatigueThresholds['high']) {
            return '높음 🟠';
        } elseif ($fatigueIndex >= $this->fatigueThresholds['medium']) {
            return '보통 🟡';
        } else {
            return '낮음 🟢';
        }
    }

    /**
     * 시간 경과 텍스트 반환
     *
     * @param string $datetime 시간
     * @return string 경과 텍스트
     */
    private function getTimeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return '방금 전';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "{$mins}분 전";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "{$hours}시간 전";
        } else {
            $days = floor($diff / 86400);
            return "{$days}일 전";
        }
    }

    /**
     * 높은 피로도 위험 알림
     *
     * @param int $userId 사용자 ID
     * @param string $pattern 패턴
     * @param array $triggerData 트리거 데이터
     * @return void
     */
    private function notifyHighFatigueRisk(int $userId, string $pattern, array $triggerData): void
    {
        if (!$this->communicator) {
            return;
        }

        // Agent08 (평온도)에 피로 알림
        $this->communicator->publish([
            'type' => 'fatigue_risk_alert',
            'from_agent' => $this->nagent,
            'to_agent' => 8,
            'priority' => 1,
            'payload' => [
                'user_id' => $userId,
                'rest_pattern' => $pattern,
                'risk_level' => $pattern === 'no_rest' ? 'critical' : 'high',
                'recommended_actions' => [
                    'suggest_calming_activity',
                    'reduce_workload',
                    'enable_break_reminder'
                ],
                'trigger' => $triggerData
            ]
        ]);

        // Agent05 (학습감정)에도 알림
        $this->communicator->publish([
            'type' => 'fatigue_emotion_alert',
            'from_agent' => $this->nagent,
            'to_agent' => 5,
            'priority' => 2,
            'payload' => [
                'user_id' => $userId,
                'rest_pattern' => $pattern,
                'monitor_emotion' => true
            ]
        ]);
    }

    // =========================================================================
    // Public API Methods
    // =========================================================================

    /**
     * 휴식 세션 기록
     *
     * @param int $userId 사용자 ID
     * @param array $restData 휴식 데이터
     * @return array 결과
     */
    public function recordRestSession(int $userId, array $restData): array
    {
        try {
            $result = $this->dataContext->addRestSession($userId, $restData);

            // 휴식 기록 후 페르소나 재평가
            $this->identifyPersona($userId, [
                'trigger' => 'rest_recorded',
                'session_id' => $result['id']
            ]);

            return [
                'success' => true,
                'session_id' => $result['id'],
                'message' => '휴식이 기록되었습니다.'
            ];
        } catch (Exception $e) {
            $this->log('error', 'Failed to record rest session', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => __FILE__,
                'line' => __LINE__
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 휴식 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param array $options 옵션
     * @return array 통계 데이터
     */
    public function getRestStatistics(int $userId, array $options = []): array
    {
        return $this->dataContext->getRestStatistics($userId, $options);
    }

    /**
     * 현재 피로도 조회
     *
     * @param int $userId 사용자 ID
     * @return array 피로도 정보
     */
    public function getCurrentFatigue(int $userId): array
    {
        $restData = $this->dataContext->getRestStatistics($userId);
        $fatigueIndex = $this->calculateFatigueIndex($userId, $restData);

        return [
            'fatigue_index' => $fatigueIndex,
            'level' => $this->getFatigueLevel($fatigueIndex),
            'threshold' => $this->fatigueThresholds,
            'needs_rest' => $fatigueIndex >= $this->fatigueThresholds['high']
        ];
    }

    /**
     * 휴식 권장 사항 조회
     *
     * @param int $userId 사용자 ID
     * @return array 권장 사항
     */
    public function getRestRecommendation(int $userId): array
    {
        $state = $this->getPersonaState($userId);

        if (!$state || !isset($state['strategy'])) {
            $identified = $this->identifyPersona($userId, []);
            return $identified['strategy'];
        }

        return $state['strategy'];
    }
}

/*
 * =========================================================================
 * 관련 DB 테이블
 * =========================================================================
 *
 * mdl_at_agent12_rest_sessions (휴식 세션)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - userid: BIGINT NOT NULL (FK to mdl_user)
 * - start_time: DATETIME NOT NULL
 * - end_time: DATETIME
 * - duration_minutes: INT
 * - rest_type: VARCHAR(50) (short_break|medium_break|long_break|stretch|walk)
 * - emotional_state_before: VARCHAR(50)
 * - emotional_state_after: VARCHAR(50)
 * - effectiveness_score: DECIMAL(3,2)
 * - notes: TEXT
 * - timecreated: INT
 * - timemodified: INT
 *
 * mdl_at_agent12_routine_history (루틴 히스토리)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - userid: BIGINT NOT NULL
 * - date: DATE NOT NULL
 * - total_study_minutes: INT
 * - total_rest_minutes: INT
 * - rest_count: INT
 * - avg_interval: INT
 * - fatigue_peak: DECIMAL(3,2)
 * - persona_code: VARCHAR(20)
 * - timecreated: INT
 *
 * mdl_at_agent_persona_state (공통 테이블 사용)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - nagent: TINYINT NOT NULL (=12)
 * - persona_code: VARCHAR(20)
 * - confidence: DECIMAL(3,2)
 * - context_data: JSON
 * - timecreated: INT
 * - timemodified: INT
 * =========================================================================
 */
