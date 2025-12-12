<?php
/**
 * Agent09PersonaEngine - 학습관리 에이전트 페르소나 엔진
 *
 * Agent09(학습관리)에 특화된 페르소나 엔진 구현
 * 출결, 목표, 포모도로, 오답노트, 시험 패턴 기반 개인화 응답 생성
 *
 * @package AugmentedTeacher\Agent09\PersonaSystem
 * @version 1.0
 */

// 공통 엔진 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseRuleParser.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseActionExecutor.php');
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseResponseGenerator.php');
require_once(__DIR__ . '/Agent09DataContext.php');

class Agent09PersonaEngine extends AbstractPersonaEngine {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array 학습관리 특화 상황 코드 */
    protected $situationCodes = [
        'data_collection' => '데이터 수집 필요',
        'dropout_risk_low' => '이탈 위험 낮음',
        'dropout_risk_medium' => '이탈 위험 중간',
        'dropout_risk_high' => '이탈 위험 높음',
        'routine_stable' => '루틴 안정화',
        'routine_adjustment' => '루틴 조정 필요',
        'math_weakness' => '수학 취약점 발견',
        'goal_achievement_low' => '목표 달성률 저조',
        'pomodoro_incomplete' => '포모도로 미완료',
        'attendance_decline' => '출결 저하',
        'test_performance_drop' => '시험 성적 하락',
        'positive_progress' => '긍정적 진행',
        'default' => '기본 상태'
    ];

    /** @var array 학습 패턴 유형 */
    protected $patternTypes = [
        'data_sparse' => '활동데이터 희박형',
        'data_imbalanced' => '불균형형',
        'pattern_unstable' => '패턴불안정형',
        'automation_resistant' => '자동화 저항형',
        'dropout_risk' => '퇴원위험형',
        'high_achiever' => '고성취 지속형'
    ];

    /**
     * 생성자
     */
    public function __construct(array $config = []) {
        parent::__construct('agent09', $config);
    }

    /**
     * 컴포넌트 초기화
     */
    protected function initializeComponents(): void {
        $this->ruleParser = new BaseRuleParser();
        $this->conditionEvaluator = new BaseConditionEvaluator();
        $this->actionExecutor = new BaseActionExecutor();
        $this->responseGenerator = new BaseResponseGenerator($this->agentId);
        $this->dataContext = new Agent09DataContext($this->agentId);

        // 학습관리 특화 액션 핸들러 등록
        $this->registerLearningManagementActions();

        // 학습관리 특화 응답 템플릿 등록
        $this->registerLearningManagementTemplates();
    }

    /**
     * 규칙 파일 경로 반환
     */
    protected function getRulesPath(): string {
        return __DIR__ . '/../rules.yaml';
    }

    /**
     * 컨텍스트 확장 - 학습관리 데이터 추가
     */
    protected function extendContext(array $context): array {
        // 학습관리 5대 지표 데이터 로드
        if ($this->dataContext instanceof Agent09DataContext) {
            $userId = $context['user']['id'] ?? 0;

            // 출결 데이터
            $context['attendance'] = $this->dataContext->getAttendanceData($userId);

            // 목표 데이터
            $context['goals'] = $this->dataContext->getGoalData($userId);

            // 포모도로 데이터
            $context['pomodoro'] = $this->dataContext->getPomodoroData($userId);

            // 오답노트 데이터
            $context['wrong_notes'] = $this->dataContext->getWrongNoteData($userId);

            // 시험 데이터
            $context['tests'] = $this->dataContext->getTestData($userId);

            // 이탈 위험도 계산
            $context['dropout_risk'] = $this->calculateDropoutRisk($context);

            // 학습 패턴 유형 분류
            $context['pattern_type'] = $this->classifyPatternType($context);
        }

        return $context;
    }

    /**
     * 메시지 분석 - 학습관리 특화
     */
    protected function analyzeMessage(array $context, string $message): array {
        $baseAnalysis = parent::analyzeMessage($context, $message);

        // 학습관리 키워드 감지
        $learningKeywords = $this->detectLearningKeywords($message);
        $baseAnalysis['learning_keywords'] = $learningKeywords;

        // 학습 관련 의도 감지
        $baseAnalysis['learning_intent'] = $this->detectLearningIntent($message);

        // 감정 강도 분석 (학습 스트레스, 동기 저하 등)
        $baseAnalysis['learning_emotion'] = $this->analyzeLearningEmotion($message, $context);

        return $baseAnalysis;
    }

    /**
     * 학습 키워드 감지
     */
    protected function detectLearningKeywords(string $message): array {
        $keywords = [];

        $keywordPatterns = [
            'attendance' => ['출석', '결석', '지각', '출결', '등원', '하원'],
            'goal' => ['목표', '계획', '달성', '완료', '진행'],
            'pomodoro' => ['포모도로', '집중', '휴식', '타이머', '뽀모'],
            'wrong_note' => ['오답', '틀린', '실수', '복습', '오류'],
            'test' => ['시험', '테스트', '평가', '점수', '성적'],
            'routine' => ['루틴', '습관', '일정', '스케줄'],
            'dropout' => ['그만', '힘들', '포기', '지침', '못하겠']
        ];

        foreach ($keywordPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_strpos($message, $pattern) !== false) {
                    $keywords[] = $category;
                    break;
                }
            }
        }

        return array_unique($keywords);
    }

    /**
     * 학습 의도 감지
     */
    protected function detectLearningIntent(string $message): string {
        $intentPatterns = [
            'check_progress' => ['어떻게', '진행', '확인', '상태'],
            'request_help' => ['도움', '도와', '어떻게', '모르겠'],
            'report_difficulty' => ['어려', '힘들', '못하겠', '안돼'],
            'set_goal' => ['목표', '계획', '하고싶', '할래'],
            'review_request' => ['복습', '다시', '오답', '틀린'],
            'schedule_inquiry' => ['일정', '언제', '시간', '스케줄']
        ];

        foreach ($intentPatterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_strpos($message, $pattern) !== false) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * 학습 감정 분석
     */
    protected function analyzeLearningEmotion(string $message, array $context): array {
        $emotions = [
            'stress_level' => 'normal',
            'motivation_level' => 'normal',
            'confidence_level' => 'normal'
        ];

        // 스트레스 키워드
        $stressKeywords = ['스트레스', '힘들', '지침', '피곤', '어려', '못하겠'];
        foreach ($stressKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $emotions['stress_level'] = 'high';
                break;
            }
        }

        // 동기 저하 키워드
        $lowMotivationKeywords = ['그만', '포기', '귀찮', '싫', '하기싫'];
        foreach ($lowMotivationKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $emotions['motivation_level'] = 'low';
                break;
            }
        }

        // 자신감 키워드
        $lowConfidenceKeywords = ['못해', '안돼', '자신없', '걱정', '불안'];
        foreach ($lowConfidenceKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $emotions['confidence_level'] = 'low';
                break;
            }
        }

        return $emotions;
    }

    /**
     * 이탈 위험도 계산
     */
    protected function calculateDropoutRisk(array $context): array {
        $riskScore = 0;
        $riskFactors = [];

        // 출결 데이터 기반 위험도
        $attendance = $context['attendance'] ?? [];
        if (isset($attendance['recent_absence_count']) && $attendance['recent_absence_count'] >= 3) {
            $riskScore += 25;
            $riskFactors[] = 'consecutive_absence';
        }

        // 포모도로 완료율 기반 위험도
        $pomodoro = $context['pomodoro'] ?? [];
        if (isset($pomodoro['completion_rate']) && $pomodoro['completion_rate'] < 0.5) {
            $riskScore += 20;
            $riskFactors[] = 'low_pomodoro_completion';
        }

        // 목표 달성률 기반 위험도
        $goals = $context['goals'] ?? [];
        if (isset($goals['achievement_rate']) && $goals['achievement_rate'] < 0.3) {
            $riskScore += 20;
            $riskFactors[] = 'low_goal_achievement';
        }

        // 시험 성적 하락 기반 위험도
        $tests = $context['tests'] ?? [];
        if (isset($tests['score_trend']) && $tests['score_trend'] === 'declining') {
            $riskScore += 15;
            $riskFactors[] = 'declining_test_scores';
        }

        // 데이터 밀도 기반 위험도
        $dataDensity = $this->calculateDataDensity($context);
        if ($dataDensity < 0.3) {
            $riskScore += 20;
            $riskFactors[] = 'sparse_activity_data';
        }

        // 위험 등급 결정
        $riskLevel = 'low';
        if ($riskScore >= 60) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 40) {
            $riskLevel = 'medium';
        }

        return [
            'score' => $riskScore,
            'level' => $riskLevel,
            'factors' => $riskFactors
        ];
    }

    /**
     * 데이터 밀도 계산
     */
    protected function calculateDataDensity(array $context): float {
        $totalFields = 5;
        $availableFields = 0;

        if (!empty($context['attendance'])) $availableFields++;
        if (!empty($context['goals'])) $availableFields++;
        if (!empty($context['pomodoro'])) $availableFields++;
        if (!empty($context['wrong_notes'])) $availableFields++;
        if (!empty($context['tests'])) $availableFields++;

        return $availableFields / $totalFields;
    }

    /**
     * 학습 패턴 유형 분류
     */
    protected function classifyPatternType(array $context): string {
        $dataDensity = $this->calculateDataDensity($context);
        $dropoutRisk = $context['dropout_risk'] ?? [];

        // 데이터 희박형
        if ($dataDensity < 0.3) {
            return 'data_sparse';
        }

        // 퇴원 위험형
        if (isset($dropoutRisk['level']) && $dropoutRisk['level'] === 'high') {
            return 'dropout_risk';
        }

        // 불균형형 (일부 데이터만 많음)
        $dataBalance = $this->checkDataBalance($context);
        if (!$dataBalance) {
            return 'data_imbalanced';
        }

        // 고성취 지속형
        $goals = $context['goals'] ?? [];
        $tests = $context['tests'] ?? [];
        if (isset($goals['achievement_rate']) && $goals['achievement_rate'] > 0.8 &&
            isset($tests['average_score']) && $tests['average_score'] > 80) {
            return 'high_achiever';
        }

        // 패턴 불안정형
        $pomodoro = $context['pomodoro'] ?? [];
        if (isset($pomodoro['consistency_score']) && $pomodoro['consistency_score'] < 0.5) {
            return 'pattern_unstable';
        }

        return 'normal';
    }

    /**
     * 데이터 균형 체크
     */
    protected function checkDataBalance(array $context): bool {
        $counts = [];

        $counts[] = count($context['attendance'] ?? []);
        $counts[] = count($context['goals'] ?? []);
        $counts[] = count($context['pomodoro'] ?? []);
        $counts[] = count($context['wrong_notes'] ?? []);
        $counts[] = count($context['tests'] ?? []);

        if (empty($counts)) return true;

        $max = max($counts);
        $min = min($counts);

        // 최대값과 최소값 차이가 3배 이상이면 불균형
        return $max <= ($min * 3);
    }

    /**
     * 학습관리 특화 액션 핸들러 등록
     */
    protected function registerLearningManagementActions(): void {
        // 데이터 수집 액션
        $this->actionExecutor->registerHandler('collect_learning_data', function($action, $context) {
            return [
                'action' => 'collect_learning_data',
                'status' => 'triggered',
                'target_data' => $action['data_type'] ?? 'all'
            ];
        });

        // 루틴 재설계 액션
        $this->actionExecutor->registerHandler('redesign_routine', function($action, $context) {
            return [
                'action' => 'redesign_routine',
                'status' => 'triggered',
                'reason' => $action['reason'] ?? 'pattern_change'
            ];
        });

        // 경고 발송 액션
        $this->actionExecutor->registerHandler('send_dropout_warning', function($action, $context) {
            return [
                'action' => 'send_dropout_warning',
                'status' => 'triggered',
                'level' => $action['level'] ?? 'low',
                'user_id' => $context['user']['id'] ?? 0
            ];
        });

        // 개입 제안 액션
        $this->actionExecutor->registerHandler('suggest_intervention', function($action, $context) {
            return [
                'action' => 'suggest_intervention',
                'status' => 'triggered',
                'intervention_type' => $action['type'] ?? 'encouragement'
            ];
        });
    }

    /**
     * 학습관리 특화 응답 템플릿 등록
     */
    protected function registerLearningManagementTemplates(): void {
        $templates = [
            // 기본 인사
            'greeting' => '안녕하세요{{honorific}}! 학습관리 도우미입니다. 오늘도 함께 목표를 향해 나아가볼까요?',

            // 출결 관련
            'attendance_good' => '{{user_name}}님{{honorific}}, 최근 출결이 아주 좋아요! 꾸준함이 실력이 됩니다.',
            'attendance_concern' => '{{user_name}}님{{honorific}}, 최근 {{absence_count}}일 결석이 있네요. 무슨 어려움이 있으셨나요?',

            // 목표 관련
            'goal_achieved' => '축하해요{{honorific}}! {{goal_name}} 목표를 달성했어요. 정말 대단해요!',
            'goal_behind' => '{{user_name}}님{{honorific}}, {{goal_name}} 목표가 조금 뒤처져 있어요. 함께 계획을 조정해볼까요?',

            // 포모도로 관련
            'pomodoro_good' => '오늘 포모도로 {{completed_count}}세션 완료! 집중력이 정말 좋아지고 있어요{{honorific}}.',
            'pomodoro_encourage' => '{{user_name}}님{{honorific}}, 오늘 포모도로 한 세션 시작해볼까요? 25분만 집중해봐요!',

            // 오답 관련
            'wrong_note_review' => '{{user_name}}님{{honorific}}, {{concept_name}} 개념에서 반복 오류가 보여요. 함께 복습해볼까요?',
            'wrong_note_improved' => '{{concept_name}} 오답률이 줄어들고 있어요! 복습 효과가 나타나고 있네요{{honorific}}.',

            // 이탈 위험 관련
            'dropout_warning_low' => '{{user_name}}님{{honorific}}, 요즘 학습 리듬이 조금 불규칙해졌어요. 괜찮으세요?',
            'dropout_warning_medium' => '{{user_name}}님{{honorific}}, 걱정이 돼요. 무슨 어려움이 있으시면 말씀해주세요.',
            'dropout_warning_high' => '{{user_name}}님{{honorific}}, 잠시 쉬어가도 괜찮아요. 함께 새로운 시작을 해볼까요?',

            // 격려 메시지
            'encouragement' => '{{user_name}}님{{honorific}}, 오늘도 한 걸음씩 나아가고 있어요. 응원할게요!',
            'progress_positive' => '{{user_name}}님{{honorific}}, {{metric_name}}이(가) {{improvement_rate}}% 향상됐어요! 대단해요!'
        ];

        $this->responseGenerator->addTemplates($templates);
    }

    /**
     * 상황 코드 목록 반환
     */
    public function getSituationCodes(): array {
        return $this->situationCodes;
    }

    /**
     * 패턴 유형 목록 반환
     */
    public function getPatternTypes(): array {
        return $this->patternTypes;
    }

    /**
     * 디버그 정보 확장
     */
    public function getDebugInfo(): array {
        $baseInfo = parent::getDebugInfo();
        $baseInfo['situation_codes'] = array_keys($this->situationCodes);
        $baseInfo['pattern_types'] = array_keys($this->patternTypes);
        return $baseInfo;
    }
}

/*
 * 사용 예시:
 *
 * $engine = new Agent09PersonaEngine(['debug_mode' => true]);
 * $engine->loadRules();
 *
 * $result = $engine->process(
 *     userId: 12345,
 *     message: "오늘 공부하기 싫어요",
 *     sessionData: ['current_activity' => 'pomodoro']
 * );
 *
 * 관련 DB 테이블:
 * - mdl_at_agent_persona_state (페르소나 상태)
 * - mdl_at_student_goals (학생 목표)
 * - mdl_at_pomodoro_sessions (포모도로 세션)
 * - mdl_at_attendance_log (출결 로그)
 * - mdl_at_wrong_notes (오답노트)
 * - mdl_at_test_results (시험 결과)
 */
