<?php
/**
 * Agent03PersonaEngine - 목표분석 에이전트 페르소나 엔진
 *
 * 공통 AbstractPersonaEngine을 확장하여 Agent03(목표분석)에 특화된
 * 페르소나 식별 및 응답 생성 기능 구현
 *
 * @package AugmentedTeacher\Agent03\PersonaSystem
 * @version 1.0
 * @author Augmented Teacher Team
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 페르소나 엔진 컴포넌트 로드
// 경로: agents/agent03_goals_analysis/persona_system/engine → orchestration/ontology_engineering/persona_engine
$engineBasePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine';

// 필수 파일 존재 여부 확인 후 로드
$requiredFiles = [
    '/core/AbstractPersonaEngine.php',
    '/impl/BaseRuleParser.php',
    '/impl/BaseConditionEvaluator.php',
    '/impl/BaseActionExecutor.php',
    '/impl/BaseDataContext.php',
    '/impl/BaseResponseGenerator.php',
    '/config/persona_engine.config.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = $engineBasePath . $file;
    if (!file_exists($fullPath)) {
        throw new Exception("Required engine file not found: {$fullPath} (File: " . __FILE__ . ", Line: " . __LINE__ . ")");
    }
    require_once($fullPath);
}

/**
 * Agent03 전용 데이터 컨텍스트
 * 목표 분석에 필요한 데이터 로드 및 상황 판단
 */
class Agent03DataContext extends BaseDataContext {

    protected $currentFile = __FILE__;

    /** @var array 목표 분석 전용 감정 키워드 */
    protected $goalEmotionKeywords = [
        'motivated' => ['할 수 있', '해보자', '도전', '시작', '열심히', '노력'],
        'overwhelmed' => ['너무 많', '감당', '벅차', '힘들', '불가능', '못 할'],
        'frustrated' => ['왜 안', '진전이 없', '포기', '실패', '안 돼'],
        'satisfied' => ['달성', '성공', '해냈', '완료', '뿌듯'],
        'confused' => ['어떻게', '모르겠', '방향', '뭐부터', '계획']
    ];

    /**
     * 상황 판단 - 목표 분석 전용
     */
    public function determineSituation(array $sessionData): string {
        // 목표 진행률 기반 상황 판단
        $progressRate = $sessionData['goal_progress_rate'] ?? 0;
        $goalCount = $sessionData['active_goal_count'] ?? 0;
        $emotion = $sessionData['emotional_state'] ?? 'neutral';

        // 위기 상황: 달성률 30% 미만 + 부정적 감정
        if ($progressRate < 30 && in_array($emotion, ['overwhelmed', 'frustrated', 'negative'])) {
            return 'goal_crisis';
        }

        // 목표 과부하: 활성 목표가 너무 많음
        if ($goalCount > 5) {
            return 'goal_overload';
        }

        // 목표 부재: 활성 목표가 없음
        if ($goalCount == 0) {
            return 'no_goals';
        }

        // 달성률 낮음 (60% 미만)
        if ($progressRate < 60) {
            return 'low_progress';
        }

        // 높은 달성률 (80% 이상)
        if ($progressRate >= 80) {
            return 'high_progress';
        }

        // 혼란/방향 상실
        if ($emotion === 'confused') {
            return 'direction_needed';
        }

        return 'normal_progress';
    }

    /**
     * 사용자 ID로 목표 분석 컨텍스트 로드
     */
    public function loadByUserId(int $userId, array $sessionData = []): array {
        global $DB;

        $context = parent::loadByUserId($userId, $sessionData);

        try {
            // 목표 관련 데이터 로드
            $goalData = $this->loadGoalData($userId);
            $context = array_merge($context, $goalData);

            // 최근 목표 활동 로드
            $recentActivity = $this->loadRecentGoalActivity($userId);
            $context['recent_goal_activity'] = $recentActivity;

            // 목표 카테고리 균형 분석
            $categoryBalance = $this->analyzeGoalCategoryBalance($userId);
            $context['category_balance'] = $categoryBalance;

        } catch (Exception $e) {
            error_log("[Agent03DataContext] {$this->currentFile}:" . __LINE__ .
                " - 목표 데이터 로드 실패: " . $e->getMessage());
        }

        return $context;
    }

    /**
     * 목표 데이터 로드
     */
    protected function loadGoalData(int $userId): array {
        global $DB;

        $goalData = [
            'active_goal_count' => 0,
            'completed_goal_count' => 0,
            'goal_progress_rate' => 0,
            'goals' => []
        ];

        try {
            // 활성 목표 수
            $activeGoals = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {at_user_goals} WHERE userid = ? AND status = 'active'",
                [$userId]
            );
            $goalData['active_goal_count'] = (int)$activeGoals;

            // 완료된 목표 수
            $completedGoals = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {at_user_goals} WHERE userid = ? AND status = 'completed'",
                [$userId]
            );
            $goalData['completed_goal_count'] = (int)$completedGoals;

            // 전체 진행률 계산
            $totalGoals = $activeGoals + $completedGoals;
            if ($totalGoals > 0) {
                $goalData['goal_progress_rate'] = round(($completedGoals / $totalGoals) * 100, 1);
            }

            // 최근 활성 목표 목록
            $goals = $DB->get_records_sql(
                "SELECT id, title, category, progress, deadline, created_at
                 FROM {at_user_goals}
                 WHERE userid = ? AND status = 'active'
                 ORDER BY deadline ASC
                 LIMIT 10",
                [$userId]
            );
            $goalData['goals'] = array_values((array)$goals);

        } catch (Exception $e) {
            error_log("[Agent03DataContext] {$this->currentFile}:" . __LINE__ .
                " - 목표 데이터 쿼리 실패: " . $e->getMessage());
        }

        return $goalData;
    }

    /**
     * 최근 목표 활동 로드
     */
    protected function loadRecentGoalActivity(int $userId): array {
        global $DB;

        try {
            $activities = $DB->get_records_sql(
                "SELECT ga.id, ga.goal_id, ga.activity_type, ga.details, ga.created_at,
                        g.title as goal_title
                 FROM {at_goal_activities} ga
                 JOIN {at_user_goals} g ON ga.goal_id = g.id
                 WHERE g.userid = ?
                 ORDER BY ga.created_at DESC
                 LIMIT 5",
                [$userId]
            );
            return array_values((array)$activities);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 목표 카테고리 균형 분석
     */
    protected function analyzeGoalCategoryBalance(int $userId): array {
        global $DB;

        $balance = [
            'academic' => 0,
            'skill' => 0,
            'growth' => 0,
            'health' => 0,
            'other' => 0,
            'is_balanced' => true,
            'dominant_category' => null
        ];

        try {
            $categories = $DB->get_records_sql(
                "SELECT category, COUNT(*) as count
                 FROM {at_user_goals}
                 WHERE userid = ? AND status IN ('active', 'completed')
                 GROUP BY category",
                [$userId]
            );

            $total = 0;
            $max = 0;
            $maxCategory = null;

            foreach ($categories as $cat) {
                $key = strtolower($cat->category);
                if (!isset($balance[$key])) $key = 'other';
                $balance[$key] = (int)$cat->count;
                $total += $cat->count;

                if ($cat->count > $max) {
                    $max = $cat->count;
                    $maxCategory = $cat->category;
                }
            }

            // 균형 판단: 한 카테고리가 60% 이상이면 불균형
            if ($total > 0 && $max > 0) {
                $dominantRatio = $max / $total;
                $balance['is_balanced'] = $dominantRatio < 0.6;
                $balance['dominant_category'] = $maxCategory;
            }

        } catch (Exception $e) {
            error_log("[Agent03DataContext] {$this->currentFile}:" . __LINE__ .
                " - 카테고리 분석 실패: " . $e->getMessage());
        }

        return $balance;
    }

    /**
     * 메시지 분석 (목표 분석 전용 확장)
     */
    public function analyzeMessage(string $message): array {
        $baseAnalysis = parent::analyzeMessage($message);

        // 목표 관련 의도 분석
        $goalIntent = $this->detectGoalIntent($message);
        $baseAnalysis['goal_intent'] = $goalIntent;

        // 목표 관련 감정 분석
        $goalEmotion = $this->detectGoalEmotion($message);
        if ($goalEmotion !== 'neutral') {
            $baseAnalysis['emotional_state'] = $goalEmotion;
        }

        return $baseAnalysis;
    }

    /**
     * 목표 관련 의도 감지
     */
    protected function detectGoalIntent(string $message): string {
        $intents = [
            'set_goal' => ['목표', '세우', '설정', '만들', '새로운'],
            'check_progress' => ['진행', '얼마나', '달성률', '현재', '상태'],
            'modify_goal' => ['수정', '변경', '바꾸', '조정'],
            'delete_goal' => ['삭제', '취소', '지우', '없애'],
            'need_help' => ['도움', '어떻게', '모르겠', '방법'],
            'celebrate' => ['달성', '완료', '해냈', '성공']
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
     * 목표 관련 감정 감지
     */
    protected function detectGoalEmotion(string $message): string {
        foreach ($this->goalEmotionKeywords as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return $emotion;
                }
            }
        }
        return 'neutral';
    }
}

/**
 * Agent03PersonaEngine - 목표분석 전용 페르소나 엔진
 */
class Agent03PersonaEngine extends AbstractPersonaEngine {

    protected $currentFile = __FILE__;

    /**
     * Agent03 페르소나 정의 반환 (AbstractPersonaEngine 추상 메서드 구현)
     *
     * @return array 페르소나 정의 배열
     */
    public function getPersonaDefinitions(): array {
        return [
            'P1' => [
                'id' => 'P1',
                'name' => '전략적 코치',
                'description' => '목표 설정과 계획 수립을 도와주는 코치',
                'tone' => 'Professional',
                'emoji_style' => 'minimal',
                'speaking_style' => '논리적이고 체계적인 안내',
                'situations' => ['no_goals', 'goal_setting', 'direction_needed']
            ],
            'P2' => [
                'id' => 'P2',
                'name' => '동기부여 멘토',
                'description' => '격려와 칭찬으로 동기를 부여하는 멘토',
                'tone' => 'Encouraging',
                'emoji_style' => 'moderate',
                'speaking_style' => '따뜻하고 격려하는 말투',
                'situations' => ['high_progress', 'goal_achieved', 'celebration']
            ],
            'P3' => [
                'id' => 'P3',
                'name' => '위기관리 상담사',
                'description' => '어려움에 처한 학생을 돕는 상담사',
                'tone' => 'Empathetic',
                'emoji_style' => 'minimal',
                'speaking_style' => '공감하고 지지하는 말투',
                'situations' => ['goal_crisis', 'overwhelmed', 'frustrated']
            ],
            'P4' => [
                'id' => 'P4',
                'name' => '균형 조언자',
                'description' => '목표 균형과 우선순위를 조언하는 조언자',
                'tone' => 'Supportive',
                'emoji_style' => 'moderate',
                'speaking_style' => '차분하고 분석적인 조언',
                'situations' => ['goal_overload', 'category_imbalance', 'prioritization']
            ],
            'P5' => [
                'id' => 'P5',
                'name' => '진행 분석가',
                'description' => '목표 진행 상황을 분석하고 피드백하는 분석가',
                'tone' => 'Professional',
                'emoji_style' => 'minimal',
                'speaking_style' => '객관적이고 데이터 기반 피드백',
                'situations' => ['progress_check', 'low_progress', 'normal_progress']
            ]
        ];
    }

    /**
     * Agent03 기본 페르소나 ID 반환 (AbstractPersonaEngine 추상 메서드 구현)
     *
     * @return string 기본 페르소나 ID
     */
    public function getDefaultPersonaId(): string {
        return 'P1'; // 전략적 코치가 기본 페르소나
    }

    /**
     * 컴포넌트 초기화
     */
    protected function initializeComponents(): void {
        $this->ruleParser = new BaseRuleParser();
        $this->conditionEvaluator = new BaseConditionEvaluator();
        $this->actionExecutor = new BaseActionExecutor();
        $this->dataContext = new Agent03DataContext($this->agentId);
        $this->responseGenerator = new BaseResponseGenerator($this->agentId);

        // Agent03 전용 템플릿 추가
        $this->initializeAgent03Templates();

        // Agent03 전용 액션 핸들러 등록
        $this->registerAgent03ActionHandlers();
    }

    /**
     * 규칙 파일 경로 반환
     */
    protected function getRulesPath(): string {
        return __DIR__ . '/../rules.yaml';
    }

    /**
     * Agent03 전용 컨텍스트 확장
     */
    protected function extendContext(array $context): array {
        // 목표 분석 관련 추가 컨텍스트
        $context['agent_type'] = 'goals_analysis';
        $context['agent_id'] = $this->agentId;

        // 목표 상태 요약
        $goalCount = $context['active_goal_count'] ?? 0;
        $progressRate = $context['goal_progress_rate'] ?? 0;

        $context['goal_status_summary'] = [
            'level' => $this->determineGoalStatusLevel($goalCount, $progressRate),
            'needs_attention' => $progressRate < 60 || $goalCount == 0 || $goalCount > 5
        ];

        return $context;
    }

    /**
     * 목표 상태 레벨 판단
     */
    protected function determineGoalStatusLevel(int $goalCount, float $progressRate): string {
        if ($goalCount == 0) return 'no_goals';
        if ($progressRate >= 80) return 'excellent';
        if ($progressRate >= 60) return 'good';
        if ($progressRate >= 40) return 'needs_improvement';
        return 'critical';
    }

    /**
     * Agent03 전용 템플릿 초기화
     */
    protected function initializeAgent03Templates(): void {
        $templates = [
            // 목표 설정 관련
            'goal_set' => '{{user_name}}{{honorific}}, 새로운 목표를 설정해볼까요? 먼저 어떤 분야의 목표인지 알려주세요.',
            'goal_set_emotional' => '{{user_name}}{{honorific}}, 목표를 세우는 것 자체가 멋진 시작이에요! 함께 현실적인 목표를 만들어봐요.',

            // 진행 상황 관련
            'progress_check' => '{{user_name}}{{honorific}}, 현재 {{active_goal_count}}개의 목표가 진행 중이에요. 전체 달성률은 {{goal_progress_rate}}%입니다.',
            'progress_low' => '{{user_name}}{{honorific}}, 달성률이 {{goal_progress_rate}}%로 조금 낮네요. 목표를 더 작은 단계로 나눠볼까요?',
            'progress_high' => '{{user_name}}{{honorific}}, 훌륭해요! {{goal_progress_rate}}% 달성률이에요. 이 기세를 유지해봐요!',

            // 목표 과부하
            'goal_overload' => '{{user_name}}{{honorific}}, 현재 {{active_goal_count}}개의 목표를 진행 중이시네요. 우선순위를 정해 3-5개로 집중해보는 건 어떨까요?',

            // 목표 부재
            'no_goals' => '{{user_name}}{{honorific}}, 아직 설정된 목표가 없어요. 작은 것부터 시작해볼까요? 이번 주에 달성하고 싶은 것이 있나요?',

            // 카테고리 불균형
            'category_imbalance' => '{{user_name}}{{honorific}}, {{dominant_category}} 분야에 목표가 집중되어 있어요. 다른 영역의 균형도 생각해보면 어떨까요?',

            // 격려
            'encouragement' => '{{user_name}}{{honorific}}, 목표를 향해 한 걸음씩 나아가고 있어요. 작은 성취도 축하받을 자격이 있어요!',

            // 에러
            'error' => '{{user_name}}{{honorific}}, 잠시 문제가 발생했어요. 다시 시도해주시겠어요?'
        ];

        $this->responseGenerator->addTemplates($templates);
    }

    /**
     * Agent03 전용 액션 핸들러 등록
     */
    protected function registerAgent03ActionHandlers(): void {
        // 목표 생성 액션
        $this->actionExecutor->registerHandler('create_goal', function($value, $context) {
            return "create_goal: " . json_encode(['type' => $value, 'user_id' => $context['user']['id'] ?? 0]);
        });

        // 목표 분석 액션
        $this->actionExecutor->registerHandler('analyze_goals', function($value, $context) {
            return "analyze_goals: " . ($value ?: 'all');
        });

        // 목표 조정 추천 액션
        $this->actionExecutor->registerHandler('recommend_adjustment', function($value, $context) {
            return "recommend_adjustment: " . $value;
        });

        // 알림 설정 액션
        $this->actionExecutor->registerHandler('set_reminder', function($value, $context) {
            return "set_reminder: " . $value;
        });
    }

    /**
     * 메시지 분석 (Agent03 확장)
     */
    protected function analyzeMessage(array $context, string $message): array {
        $analysis = $this->dataContext->analyzeMessage($message);

        // 목표 관련 토픽 감지
        $analysis['detected_topics'] = $this->detectGoalTopics($message);

        // 감정 강도 계산
        $analysis['emotion_intensity'] = $this->calculateEmotionIntensity($message, $analysis['emotional_state']);

        return $analysis;
    }

    /**
     * 목표 관련 토픽 감지
     */
    protected function detectGoalTopics(string $message): array {
        $topics = [];
        $topicKeywords = [
            'deadline' => ['기한', '마감', '언제까지', 'D-'],
            'priority' => ['중요', '우선', '먼저', '급한'],
            'category' => ['학업', '운동', '건강', '취미', '기술'],
            'breakdown' => ['나누', '분해', '단계', '세분화'],
            'review' => ['검토', '확인', '체크', '평가']
        ];

        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }

        return array_unique($topics);
    }

    /**
     * 감정 강도 계산
     */
    protected function calculateEmotionIntensity(string $message, string $emotion): float {
        $intensifiers = ['너무', '정말', '매우', '진짜', '완전', '엄청'];
        $intensity = 0.5;

        foreach ($intensifiers as $intensifier) {
            if (mb_strpos($message, $intensifier) !== false) {
                $intensity += 0.1;
            }
        }

        // 느낌표 수에 따른 강도 증가
        $exclamationCount = substr_count($message, '!');
        $intensity += min($exclamationCount * 0.05, 0.2);

        return min($intensity, 1.0);
    }
}

/*
 * 사용 예시:
 *
 * $engine = new Agent03PersonaEngine('agent03');
 * $engine->loadRules();
 * $result = $engine->process($userId, '목표 달성률이 어떻게 되나요?');
 *
 * 관련 DB 테이블:
 * - at_user_goals: 사용자 목표 정보
 * - at_goal_activities: 목표 활동 로그
 * - at_agent_persona_state: 페르소나 상태
 * - at_persona_log: 페르소나 처리 로그
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/engine/Agent03PersonaEngine.php:428
 */
