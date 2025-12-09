<?php
/**
 * Agent02PersonaEngine.php
 *
 * 시험 일정 에이전트 페르소나 엔진
 * D-Day 기반 학습 긴급도 산정 및 전략 생성
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent02ExamSchedule
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../engine_core/base/AbstractPersonaEngine.php');
require_once(__DIR__ . '/Agent02DataContext.php');

/**
 * Agent02 시험 일정 페르소나 엔진
 *
 * 8개 타임라인 레벨 기반 페르소나 식별:
 * - vacation: 방학 기간
 * - d_2month: D-2개월
 * - d_1month: D-1개월
 * - d_2week: D-2주
 * - d_1week: D-1주
 * - d_3day: D-3일
 * - d_1day: D-1일
 * - no_exam: 시험 없음
 */
class Agent02PersonaEngine extends AbstractPersonaEngine
{
    /** @var int 에이전트 번호 */
    protected $nagent = 2;

    /** @var string 에이전트 이름 */
    protected $agentName = 'exam_schedule';

    /** @var string 에이전트 한글명 */
    protected $agentKrName = '시험일정';

    /** @var Agent02DataContext 데이터 컨텍스트 */
    protected $dataContext;

    /** @var array 타임라인 레벨 정의 */
    protected $timelineLevels = [
        'vacation'  => ['label' => '방학', 'priority' => 0, 'focus' => 'review'],
        'd_2month'  => ['label' => 'D-2개월', 'priority' => 1, 'focus' => 'foundation'],
        'd_1month'  => ['label' => 'D-1개월', 'priority' => 2, 'focus' => 'concept'],
        'd_2week'   => ['label' => 'D-2주', 'priority' => 3, 'focus' => 'practice'],
        'd_1week'   => ['label' => 'D-1주', 'priority' => 4, 'focus' => 'intensive'],
        'd_3day'    => ['label' => 'D-3일', 'priority' => 5, 'focus' => 'core'],
        'd_1day'    => ['label' => 'D-1일', 'priority' => 6, 'focus' => 'final'],
        'no_exam'   => ['label' => '시험없음', 'priority' => -1, 'focus' => 'general']
    ];

    /** @var array 학습 전략 매핑 */
    protected $studyStrategies = [
        'd_3day' => [
            'ratio' => ['concept' => 30, 'problem' => 70],
            'mode' => 'urgent_focus',
            'description' => '핵심 개념 + 기출 변형 집중'
        ],
        'd_1week' => [
            'ratio' => ['concept' => 40, 'problem' => 60],
            'mode' => 'intensive',
            'description' => '집중 학습 - 취약점 보완'
        ],
        'd_2week' => [
            'ratio' => ['concept' => 50, 'problem' => 50],
            'mode' => 'balanced',
            'description' => '균형형 학습 - 개념+문제'
        ],
        'd_1month' => [
            'ratio' => ['concept' => 60, 'problem' => 40],
            'mode' => 'concept_first',
            'description' => '개념 정립 후 유형 확장'
        ],
        'd_2month' => [
            'ratio' => ['concept' => 70, 'problem' => 30],
            'mode' => 'foundation',
            'description' => '기초 개념 정립 중심'
        ],
        'vacation' => [
            'ratio' => ['concept' => 80, 'problem' => 20],
            'mode' => 'preview',
            'description' => '예습 및 기본기 다지기'
        ],
        'd_1day' => [
            'ratio' => ['concept' => 20, 'problem' => 80],
            'mode' => 'final_review',
            'description' => '최종 점검 - 핵심만 복습'
        ],
        'no_exam' => [
            'ratio' => ['concept' => 50, 'problem' => 50],
            'mode' => 'general',
            'description' => '일반 학습 모드'
        ]
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
            $this->dataContext = new Agent02DataContext($this->db);
        }

        // 로그
        $this->log('info', 'Agent02 ExamSchedule PersonaEngine initialized', [
            'timeline_levels' => count($this->timelineLevels)
        ]);
    }

    /**
     * 페르소나 식별 로직
     * D-Day 기반 타임라인 레벨 결정
     *
     * @param int $userId 사용자 ID
     * @param array $contextData 컨텍스트 데이터
     * @param array|null $currentState 현재 상태
     * @return array 식별된 페르소나 정보
     */
    protected function doIdentifyPersona(int $userId, array $contextData, ?array $currentState): array
    {
        // 다음 시험 일정 조회
        $nextExam = $this->dataContext->getNextExam($userId);

        if (!$nextExam) {
            return [
                'persona_code' => 'no_exam',
                'confidence' => 1.0,
                'timeline_level' => 'no_exam',
                'd_day' => null,
                'exam_info' => null,
                'strategy' => $this->studyStrategies['no_exam']
            ];
        }

        // D-Day 계산
        $dDay = $this->calculateDDay($nextExam['exam_date']);

        // 타임라인 레벨 결정
        $timelineLevel = $this->determineTimelineLevel($dDay, $contextData);

        // 신뢰도 계산 (시험 정보 완성도 기반)
        $confidence = $this->calculateConfidence($nextExam, $contextData);

        // 학습 전략 결정
        $strategy = $this->getStudyStrategy($timelineLevel, $nextExam);

        return [
            'persona_code' => $timelineLevel,
            'confidence' => $confidence,
            'timeline_level' => $timelineLevel,
            'd_day' => $dDay,
            'exam_info' => [
                'id' => $nextExam['id'],
                'name' => $nextExam['exam_name'],
                'date' => $nextExam['exam_date'],
                'target_score' => isset($nextExam['target_score']) ? $nextExam['target_score'] : null,
                'subjects' => isset($nextExam['subjects']) ? $nextExam['subjects'] : []
            ],
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
        $examInfo = isset($currentState['exam_info']) ? $currentState['exam_info'] : null;
        $strategy = isset($currentState['strategy']) ? $currentState['strategy'] : null;

        // 메시지 의도 분석
        $intent = $this->analyzeIntent($userMessage);

        // 응답 템플릿 선택
        $template = $this->selectResponseTemplate($personaCode, $intent);

        // 응답 생성
        $response = $this->buildResponse($template, [
            'user_id' => $userId,
            'persona_code' => $personaCode,
            'exam_info' => $examInfo,
            'strategy' => $strategy,
            'd_day' => isset($currentState['d_day']) ? $currentState['d_day'] : null,
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
                'type' => 'exam_timeline_changed',
                'from_agent' => $this->nagent,
                'to_agent' => 0, // broadcast
                'payload' => [
                    'user_id' => $userId,
                    'from_timeline' => $fromPersona,
                    'to_timeline' => $toPersona,
                    'priority' => $this->timelineLevels[$toPersona]['priority'],
                    'focus' => $this->timelineLevels[$toPersona]['focus'],
                    'trigger' => $triggerData
                ]
            ]);
        }

        // 전환 로그
        $this->log('info', 'Timeline transition', [
            'user_id' => $userId,
            'from' => $fromPersona,
            'to' => $toPersona
        ]);

        // Agent09(학습관리)에 긴급도 변경 알림
        if (in_array($toPersona, ['d_3day', 'd_1day'])) {
            $this->notifyUrgentMode($userId, $toPersona, $triggerData);
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
                'timeline_levels' => count($this->timelineLevels),
                'strategies' => count($this->studyStrategies)
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
     * D-Day 계산
     *
     * @param string $examDate 시험 날짜 (Y-m-d)
     * @return int D-Day (음수: 지남, 양수: 남음)
     */
    private function calculateDDay(string $examDate): int
    {
        $exam = new DateTime($examDate);
        $today = new DateTime('today');
        $diff = $today->diff($exam);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * 타임라인 레벨 결정
     *
     * @param int $dDay D-Day 값
     * @param array $contextData 컨텍스트 데이터
     * @return string 타임라인 레벨 코드
     */
    private function determineTimelineLevel(int $dDay, array $contextData): string
    {
        // 방학 체크
        if (isset($contextData['is_vacation']) && $contextData['is_vacation']) {
            return 'vacation';
        }

        // D-Day 기반 레벨 결정
        if ($dDay < 0) {
            return 'no_exam'; // 시험 지남
        } elseif ($dDay <= 1) {
            return 'd_1day';
        } elseif ($dDay <= 3) {
            return 'd_3day';
        } elseif ($dDay <= 7) {
            return 'd_1week';
        } elseif ($dDay <= 14) {
            return 'd_2week';
        } elseif ($dDay <= 30) {
            return 'd_1month';
        } elseif ($dDay <= 60) {
            return 'd_2month';
        } else {
            return 'vacation'; // 2개월 이상은 방학/장기로 간주
        }
    }

    /**
     * 신뢰도 계산
     *
     * @param array $examInfo 시험 정보
     * @param array $contextData 컨텍스트 데이터
     * @return float 신뢰도 (0.0 - 1.0)
     */
    private function calculateConfidence(array $examInfo, array $contextData): float
    {
        $confidence = 0.5; // 기본값

        // 시험 이름이 있으면 +0.1
        if (!empty($examInfo['exam_name'])) {
            $confidence += 0.1;
        }

        // 목표 점수가 있으면 +0.1
        if (!empty($examInfo['target_score'])) {
            $confidence += 0.1;
        }

        // 시험 범위가 있으면 +0.2
        if (!empty($examInfo['subjects'])) {
            $confidence += 0.2;
        }

        // 학습 이력이 있으면 +0.1
        if (isset($contextData['has_study_history']) && $contextData['has_study_history']) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }

    /**
     * 학습 전략 결정
     *
     * @param string $timelineLevel 타임라인 레벨
     * @param array $examInfo 시험 정보
     * @return array 학습 전략
     */
    private function getStudyStrategy(string $timelineLevel, array $examInfo): array
    {
        $baseStrategy = $this->studyStrategies[$timelineLevel];

        // 목표 점수에 따른 조정
        if (!empty($examInfo['target_score'])) {
            $targetScore = (int)$examInfo['target_score'];

            if ($targetScore >= 90) {
                // 고득점 목표: 문제 비중 증가
                $baseStrategy['ratio']['problem'] = min(90, $baseStrategy['ratio']['problem'] + 10);
                $baseStrategy['ratio']['concept'] = 100 - $baseStrategy['ratio']['problem'];
                $baseStrategy['adjustment'] = 'high_target';
            }
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
            'schedule_query' => ['시험', '일정', '언제', 'D-day', '며칠'],
            'strategy_query' => ['어떻게', '전략', '계획', '방법', '공부'],
            'progress_query' => ['진도', '얼마나', '완료', '진행'],
            'scope_query' => ['범위', '어디까지', '단원', '챕터'],
            'add_exam' => ['추가', '등록', '시험있어', '시험이야'],
            'modify_exam' => ['수정', '변경', '바꿔'],
            'delete_exam' => ['삭제', '취소', '없어']
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
     * @return array 템플릿
     */
    private function selectResponseTemplate(string $personaCode, string $intent): array
    {
        // 기본 템플릿
        $templates = [
            'd_1day' => [
                'schedule_query' => [
                    'id' => 'd1_schedule',
                    'tone' => 'urgent',
                    'prefix' => '🔴 시험이 내일이에요!'
                ],
                'strategy_query' => [
                    'id' => 'd1_strategy',
                    'tone' => 'focused',
                    'prefix' => '⚡ 마지막 점검 시간!'
                ]
            ],
            'd_3day' => [
                'schedule_query' => [
                    'id' => 'd3_schedule',
                    'tone' => 'alert',
                    'prefix' => '🟠 시험 D-3!'
                ],
                'strategy_query' => [
                    'id' => 'd3_strategy',
                    'tone' => 'intensive',
                    'prefix' => '💪 집중 모드 돌입!'
                ]
            ],
            'no_exam' => [
                'schedule_query' => [
                    'id' => 'noexam_schedule',
                    'tone' => 'relaxed',
                    'prefix' => '📅 등록된 시험이 없어요.'
                ],
                'add_exam' => [
                    'id' => 'noexam_add',
                    'tone' => 'helpful',
                    'prefix' => '✏️ 시험을 등록해볼까요?'
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

        // 시험 정보 표시
        if ($data['exam_info']) {
            $examInfo = $data['exam_info'];
            $message .= "📝 시험: {$examInfo['name']}\n";
            $message .= "📆 날짜: {$examInfo['date']}";

            if ($data['d_day'] !== null) {
                $dDayText = $data['d_day'] == 0 ? 'D-Day!' :
                           ($data['d_day'] > 0 ? "D-{$data['d_day']}" : "D+".abs($data['d_day']));
                $message .= " ({$dDayText})";
            }
            $message .= "\n";

            if (!empty($examInfo['target_score'])) {
                $message .= "🎯 목표: {$examInfo['target_score']}점\n";
            }
        }

        // 전략 정보 표시
        if ($data['strategy'] && $data['intent'] !== 'schedule_query') {
            $strategy = $data['strategy'];
            $message .= "\n📊 학습 전략: {$strategy['description']}\n";
            $message .= "   개념 {$strategy['ratio']['concept']}% / 문제 {$strategy['ratio']['problem']}%\n";
        }

        // 의도별 추가 응답
        switch ($data['intent']) {
            case 'add_exam':
                $suggestions = ['시험 이름 입력', '시험 날짜 선택', '목표 점수 설정'];
                $actions = ['open_exam_form'];
                break;

            case 'strategy_query':
                $suggestions = ['오늘 할 일 보기', '취약점 분석', '문제 추천'];
                break;

            case 'progress_query':
                $suggestions = ['학습 현황 보기', '목표 대비 진도', '남은 범위'];
                break;
        }

        return [
            'message' => trim($message),
            'suggestions' => $suggestions,
            'actions' => $actions
        ];
    }

    /**
     * 긴급 모드 알림 (Agent09 등에 전달)
     *
     * @param int $userId 사용자 ID
     * @param string $timeline 타임라인
     * @param array $triggerData 트리거 데이터
     * @return void
     */
    private function notifyUrgentMode(int $userId, string $timeline, array $triggerData): void
    {
        if (!$this->communicator) {
            return;
        }

        // Agent09 (학습관리)에 긴급 알림
        $this->communicator->publish([
            'type' => 'urgent_study_mode',
            'from_agent' => $this->nagent,
            'to_agent' => 9,
            'priority' => 1, // 최우선
            'payload' => [
                'user_id' => $userId,
                'timeline' => $timeline,
                'urgency_level' => $timeline === 'd_1day' ? 'critical' : 'high',
                'recommended_actions' => [
                    'pause_non_essential',
                    'focus_core_concepts',
                    'enable_quick_review_mode'
                ],
                'trigger' => $triggerData
            ]
        ]);

        // Agent05 (학습감정)에도 알림 - 스트레스 모니터링 요청
        $this->communicator->publish([
            'type' => 'exam_stress_alert',
            'from_agent' => $this->nagent,
            'to_agent' => 5,
            'priority' => 2,
            'payload' => [
                'user_id' => $userId,
                'timeline' => $timeline,
                'monitor_stress' => true
            ]
        ]);
    }

    // =========================================================================
    // Public API Methods
    // =========================================================================

    /**
     * 시험 일정 추가
     *
     * @param int $userId 사용자 ID
     * @param array $examData 시험 데이터
     * @return array 결과
     */
    public function addExam(int $userId, array $examData): array
    {
        try {
            $result = $this->dataContext->addExam($userId, $examData);

            // 새 시험 추가 시 페르소나 재평가
            $this->identifyPersona($userId, [
                'trigger' => 'exam_added',
                'exam_id' => $result['id']
            ]);

            return [
                'success' => true,
                'exam_id' => $result['id'],
                'message' => '시험이 등록되었습니다.'
            ];
        } catch (Exception $e) {
            $this->log('error', 'Failed to add exam', [
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
     * 시험 목록 조회
     *
     * @param int $userId 사용자 ID
     * @param array $options 옵션
     * @return array 시험 목록
     */
    public function getExams(int $userId, array $options = []): array
    {
        return $this->dataContext->getExams($userId, $options);
    }

    /**
     * 현재 학습 전략 조회
     *
     * @param int $userId 사용자 ID
     * @return array 학습 전략
     */
    public function getCurrentStrategy(int $userId): array
    {
        $state = $this->getPersonaState($userId);

        if (!$state || !isset($state['strategy'])) {
            // 페르소나 재식별
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
 * mdl_alt42_exam_schedule
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - userid: BIGINT NOT NULL (FK to mdl_user)
 * - exam_name: VARCHAR(255) NOT NULL
 * - exam_date: DATE NOT NULL
 * - target_score: INT
 * - subjects: TEXT (JSON)
 * - d_day: INT (계산 필드)
 * - status: ENUM('active','completed','cancelled')
 * - timecreated: INT
 * - timemodified: INT
 *
 * mdl_at_agent02_persona_state (공통 테이블 사용)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - nagent: TINYINT NOT NULL (=2)
 * - persona_code: VARCHAR(20)
 * - confidence: DECIMAL(3,2)
 * - context_data: JSON
 * - timecreated: INT
 * - timemodified: INT
 *
 * mdl_at_agent02_transitions (공통 테이블 사용)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - nagent: TINYINT NOT NULL (=2)
 * - from_persona: VARCHAR(20)
 * - to_persona: VARCHAR(20)
 * - trigger_type: VARCHAR(50)
 * - confidence: DECIMAL(3,2)
 * - context_snapshot: JSON
 * - timecreated: INT
 * =========================================================================
 */
