<?php
/**
 * Agent19 Context Analyzer
 * 학습 상황 컨텍스트 분석 엔진
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Engine
 * @version     1.0.0
 * @author      System
 * @created     2025-12-02
 *
 * 관련 DB 테이블:
 * - mdl_agent19_context_history: id(BIGINT), userid(BIGINT), context_id(VARCHAR), context_type(ENUM), persona_applied(VARCHAR), trigger_reason(TEXT), duration_seconds(INT), effectiveness_score(DECIMAL), timecreated(BIGINT)
 * - mdl_agent19_context_rules: id(BIGINT), context_id(VARCHAR), rule_condition(TEXT/JSON), recommended_personas(VARCHAR), priority(INT), is_active(TINYINT)
 */

defined('MOODLE_INTERNAL') || die();

class Agent19ContextAnalyzer {

    /** @var object Moodle DB instance */
    private $db;

    /** @var int Current user ID */
    private $userid;

    /** @var array Situation contexts (S1-S7) */
    private $situationContexts;

    /** @var array Interaction contexts (I1-I7) */
    private $interactionContexts;

    /** @var array Current active context */
    private $currentContext;

    /**
     * Constructor
     *
     * @param int $userid User ID
     */
    public function __construct($userid) {
        global $DB;
        $this->db = $DB;
        $this->userid = $userid;
        $this->loadContextDefinitions();
    }

    /**
     * Load context definitions
     */
    private function loadContextDefinitions() {
        // 상황 컨텍스트 (S1-S7)
        $this->situationContexts = [
            'S1' => [
                'name' => '이탈 감지',
                'name_en' => 'Dropout Detection',
                'sub_contexts' => [
                    'S1_CTX_01' => ['name' => '갑작스러운 이탈', 'triggers' => ['activity_stop > 300', 'prev_activity_normal']],
                    'S1_CTX_02' => ['name' => '점진적 이탈', 'triggers' => ['response_delay_increase', 'engagement_decline']],
                    'S1_CTX_03' => ['name' => '기술적 이탈', 'triggers' => ['connection_lost', 'error_occurred']],
                    'S1_CTX_04' => ['name' => '관심 저하 이탈', 'triggers' => ['click_rate_low', 'skip_rate_high']]
                ],
                'recommended_personas' => ['C2-B2-E3', 'C1-B1-E4', 'C4-B3-E6']
            ],
            'S2' => [
                'name' => '지연',
                'name_en' => 'Delay',
                'sub_contexts' => [
                    'S2_CTX_01' => ['name' => '이해 지연', 'triggers' => ['response_time > avg*2']],
                    'S2_CTX_02' => ['name' => '주의 산만 지연', 'triggers' => ['irregular_pattern']],
                    'S2_CTX_03' => ['name' => '완벽주의 지연', 'triggers' => ['review_after_correct']],
                    'S2_CTX_04' => ['name' => '불안 기반 지연', 'triggers' => ['hesitation_before_start']]
                ],
                'recommended_personas' => ['C3-B4-E4', 'C2-B6-E3', 'C4-B4-E1', 'C5-B2-E2']
            ],
            'S3' => [
                'name' => '휴식 루틴',
                'name_en' => 'Rest Routine',
                'sub_contexts' => [
                    'S3_CTX_01' => ['name' => '예정된 휴식', 'triggers' => ['scheduled_break_time']],
                    'S3_CTX_02' => ['name' => '피로 기반 휴식', 'triggers' => ['accuracy_decline', 'response_slow']],
                    'S3_CTX_03' => ['name' => '목표 달성 휴식', 'triggers' => ['section_complete']],
                    'S3_CTX_04' => ['name' => '자발적 휴식 요청', 'triggers' => ['user_break_request']]
                ],
                'recommended_personas' => ['C1-B5-E6', 'C2-B6-E5', 'C4-B1-E1']
            ],
            'S4' => [
                'name' => '오류 패턴',
                'name_en' => 'Error Patterns',
                'sub_contexts' => [
                    'S4_CTX_01' => ['name' => '개념 오류 반복', 'triggers' => ['same_type_error >= 3']],
                    'S4_CTX_02' => ['name' => '부주의 오류', 'triggers' => ['easy_problem_wrong', 'fast_wrong']],
                    'S4_CTX_03' => ['name' => '응용 오류', 'triggers' => ['basic_correct', 'applied_wrong']],
                    'S4_CTX_04' => ['name' => '포기 패턴', 'triggers' => ['skip_without_try', 'empty_answer']]
                ],
                'recommended_personas' => ['C3-B4-E4', 'C2-B3-E3', 'C4-B4-E2', 'C2-B2-E5']
            ],
            'S5' => [
                'name' => '정서적 안정',
                'name_en' => 'Emotional Stability',
                'sub_contexts' => [
                    'S5_CTX_01' => ['name' => '자신감 상승', 'triggers' => ['consecutive_correct', 'fast_solve']],
                    'S5_CTX_02' => ['name' => '불안 감지', 'triggers' => ['response_delay', 'answer_changes']],
                    'S5_CTX_03' => ['name' => '좌절 감지', 'triggers' => ['errors_with_pause', 'interaction_decrease']],
                    'S5_CTX_04' => ['name' => '지루함 감지', 'triggers' => ['fast_skip', 'pattern_answers']]
                ],
                'recommended_personas' => ['C1-B1-E1', 'C5-B4-E2', 'C2-B2-E5', 'C1-B6-E3']
            ],
            'S6' => [
                'name' => '활동 불균형',
                'name_en' => 'Activity Imbalance',
                'sub_contexts' => [
                    'S6_CTX_01' => ['name' => '특정 활동 편중', 'triggers' => ['single_type_preference']],
                    'S6_CTX_02' => ['name' => '난이도 편중', 'triggers' => ['difficulty_bias']],
                    'S6_CTX_03' => ['name' => '학습-평가 불균형', 'triggers' => ['assessment_only']],
                    'S6_CTX_04' => ['name' => '시간 불균형', 'triggers' => ['time_concentration']]
                ],
                'recommended_personas' => ['C3-B4-E2', 'C4-B1-E1', 'C5-B1-E4', 'C2-B6-E6']
            ],
            'S7' => [
                'name' => '시그니처 루틴',
                'name_en' => 'Signature Routine',
                'sub_contexts' => [
                    'S7_CTX_01' => ['name' => '학습 시작 루틴', 'triggers' => ['session_start_pattern']],
                    'S7_CTX_02' => ['name' => '문제 풀이 루틴', 'triggers' => ['problem_approach_pattern']],
                    'S7_CTX_03' => ['name' => '복습 루틴', 'triggers' => ['review_pattern']],
                    'S7_CTX_04' => ['name' => '학습 종료 루틴', 'triggers' => ['session_end_pattern']]
                ],
                'recommended_personas' => ['C1-B1-E1', 'C4-B4-E4', 'C6-B5-E6', 'C3-B4-E6']
            ]
        ];

        // 상호작용 컨텍스트 (I1-I7)
        $this->interactionContexts = [
            'I1' => ['name' => '텍스트 기반', 'name_en' => 'Text-based', 'characteristics' => ['reading', 'q_and_a', 'conversational']],
            'I2' => ['name' => '인터랙티브 콘텐츠', 'name_en' => 'Interactive Content', 'characteristics' => ['drag_drop', 'simulation', 'gamification']],
            'I3' => ['name' => '루틴 개선', 'name_en' => 'Routine Improvement', 'characteristics' => ['planning', 'habit_tracking', 'reminders']],
            'I4' => ['name' => '타임시프팅', 'name_en' => 'Timeshifting', 'characteristics' => ['flexible_schedule', 'micro_learning', 'async_learning']],
            'I5' => ['name' => '활동 동반자', 'name_en' => 'Activity Companion', 'characteristics' => ['virtual_tutor', 'learning_mate', 'character_companion']],
            'I6' => ['name' => '다중 턴', 'name_en' => 'Multi-turn', 'characteristics' => ['step_by_step', 'socratic_dialogue', 'error_analysis']],
            'I7' => ['name' => '비선형', 'name_en' => 'Non-linear', 'characteristics' => ['free_exploration', 'adaptive_path', 'challenge_selection']]
        ];
    }

    /**
     * Analyze current learning context
     *
     * @param array $behaviorData User behavior data
     * @return array Current context analysis result
     */
    public function analyzeContext($behaviorData) {
        $result = [
            'situation' => $this->detectSituationContext($behaviorData),
            'interaction' => $this->detectInteractionContext($behaviorData),
            'environment' => $this->detectEnvironmentContext($behaviorData),
            'temporal' => $this->detectTemporalContext(),
            'timestamp' => time()
        ];

        $this->currentContext = $result;
        return $result;
    }

    /**
     * Detect situation context (S1-S7)
     *
     * @param array $data Behavior data
     * @return array Situation context detection result
     */
    private function detectSituationContext($data) {
        $detected = [];

        // S1: 이탈 감지
        if ($this->checkDropout($data)) {
            $detected['S1'] = $this->getDropoutSubContext($data);
        }

        // S2: 지연
        if ($this->checkDelay($data)) {
            $detected['S2'] = $this->getDelaySubContext($data);
        }

        // S3: 휴식 루틴
        if ($this->checkRestRoutine($data)) {
            $detected['S3'] = $this->getRestSubContext($data);
        }

        // S4: 오류 패턴
        if ($this->checkErrorPattern($data)) {
            $detected['S4'] = $this->getErrorSubContext($data);
        }

        // S5: 정서적 상태
        if (isset($data['emotional_indicators'])) {
            $detected['S5'] = $this->getEmotionalSubContext($data);
        }

        // S6: 활동 불균형
        if ($this->checkActivityImbalance($data)) {
            $detected['S6'] = $this->getActivitySubContext($data);
        }

        // S7: 시그니처 루틴
        if ($this->checkSignatureRoutine($data)) {
            $detected['S7'] = $this->getSignatureSubContext($data);
        }

        // Determine primary situation
        $primary = $this->determinePrimarySituation($detected, $data);

        return [
            'primary' => $primary,
            'all_detected' => $detected,
            'context_id' => $primary['context_id'] ?? 'S5_CTX_04', // Default to stable
            'recommended_personas' => $this->situationContexts[$primary['situation'] ?? 'S5']['recommended_personas']
        ];
    }

    /**
     * Check for dropout indicators
     */
    private function checkDropout($data) {
        $inactiveTime = $data['inactive_seconds'] ?? 0;
        $engagementDrop = ($data['engagement_trend'] ?? 0) < -0.2;
        return $inactiveTime > 300 || $engagementDrop;
    }

    /**
     * Get specific dropout sub-context
     */
    private function getDropoutSubContext($data) {
        $inactiveTime = $data['inactive_seconds'] ?? 0;
        $prevNormal = $data['previous_activity_normal'] ?? true;
        $connectionLost = $data['connection_lost'] ?? false;
        $engagementDecline = $data['engagement_trend'] ?? 0;

        if ($connectionLost || isset($data['error_occurred'])) {
            return ['context_id' => 'S1_CTX_03', 'name' => '기술적 이탈'];
        }
        if ($inactiveTime > 300 && $prevNormal) {
            return ['context_id' => 'S1_CTX_01', 'name' => '갑작스러운 이탈'];
        }
        if ($engagementDecline < -0.2) {
            return ['context_id' => 'S1_CTX_02', 'name' => '점진적 이탈'];
        }
        if (($data['skip_rate'] ?? 0) > 0.3) {
            return ['context_id' => 'S1_CTX_04', 'name' => '관심 저하 이탈'];
        }

        return ['context_id' => 'S1_CTX_01', 'name' => '이탈 감지'];
    }

    /**
     * Check for delay indicators
     */
    private function checkDelay($data) {
        $avgResponseTime = $data['avg_response_time'] ?? 0;
        $expectedTime = $data['expected_response_time'] ?? 15;
        return $avgResponseTime > $expectedTime * 2;
    }

    /**
     * Get specific delay sub-context
     */
    private function getDelaySubContext($data) {
        $responseTime = $data['avg_response_time'] ?? 0;
        $irregularPattern = $data['pattern_irregular'] ?? false;
        $reviewAfterCorrect = $data['review_after_correct'] ?? false;
        $hesitationBeforeStart = $data['hesitation_before_start'] ?? false;

        if ($hesitationBeforeStart) {
            return ['context_id' => 'S2_CTX_04', 'name' => '불안 기반 지연'];
        }
        if ($reviewAfterCorrect) {
            return ['context_id' => 'S2_CTX_03', 'name' => '완벽주의 지연'];
        }
        if ($irregularPattern) {
            return ['context_id' => 'S2_CTX_02', 'name' => '주의 산만 지연'];
        }

        return ['context_id' => 'S2_CTX_01', 'name' => '이해 지연'];
    }

    /**
     * Check for rest routine indicators
     */
    private function checkRestRoutine($data) {
        $sessionDuration = $data['session_duration'] ?? 0;
        $accuracyDecline = ($data['accuracy_trend'] ?? 0) < -0.15;
        $sectionComplete = $data['section_completed'] ?? false;

        return $sessionDuration > 2400 || $accuracyDecline || $sectionComplete;
    }

    /**
     * Get rest routine sub-context
     */
    private function getRestSubContext($data) {
        if ($data['user_break_request'] ?? false) {
            return ['context_id' => 'S3_CTX_04', 'name' => '자발적 휴식 요청'];
        }
        if ($data['section_completed'] ?? false) {
            return ['context_id' => 'S3_CTX_03', 'name' => '목표 달성 휴식'];
        }
        if (($data['accuracy_trend'] ?? 0) < -0.15) {
            return ['context_id' => 'S3_CTX_02', 'name' => '피로 기반 휴식'];
        }

        return ['context_id' => 'S3_CTX_01', 'name' => '예정된 휴식'];
    }

    /**
     * Check for error pattern indicators
     */
    private function checkErrorPattern($data) {
        $consecutiveErrors = $data['consecutive_errors'] ?? 0;
        $errorRate = $data['error_rate'] ?? 0;
        return $consecutiveErrors >= 3 || $errorRate > 0.4;
    }

    /**
     * Get error pattern sub-context
     */
    private function getErrorSubContext($data) {
        $sameTypeErrors = $data['same_type_errors'] ?? 0;
        $easyWrong = $data['easy_problem_wrong'] ?? false;
        $basicCorrect = $data['basic_correct'] ?? false;
        $appliedWrong = $data['applied_wrong'] ?? false;
        $skipWithoutTry = $data['skip_without_try'] ?? false;

        if ($skipWithoutTry || ($data['empty_answers'] ?? 0) > 0) {
            return ['context_id' => 'S4_CTX_04', 'name' => '포기 패턴'];
        }
        if ($basicCorrect && $appliedWrong) {
            return ['context_id' => 'S4_CTX_03', 'name' => '응용 오류'];
        }
        if ($easyWrong && ($data['response_time'] ?? 0) < 5) {
            return ['context_id' => 'S4_CTX_02', 'name' => '부주의 오류'];
        }
        if ($sameTypeErrors >= 3) {
            return ['context_id' => 'S4_CTX_01', 'name' => '개념 오류 반복'];
        }

        return ['context_id' => 'S4_CTX_01', 'name' => '오류 패턴'];
    }

    /**
     * Get emotional state sub-context
     */
    private function getEmotionalSubContext($data) {
        $correctStreak = $data['correct_streak'] ?? 0;
        $answerChanges = $data['answer_changes'] ?? 0;
        $errorsWithPause = $data['errors_with_pause'] ?? false;
        $skipRate = $data['skip_rate'] ?? 0;

        if ($correctStreak >= 5 && ($data['solve_speed'] ?? 0) > 0.7) {
            return ['context_id' => 'S5_CTX_01', 'name' => '자신감 상승'];
        }
        if ($answerChanges > 2 || ($data['hesitation'] ?? false)) {
            return ['context_id' => 'S5_CTX_02', 'name' => '불안 감지'];
        }
        if ($errorsWithPause || ($data['interaction_decrease'] ?? false)) {
            return ['context_id' => 'S5_CTX_03', 'name' => '좌절 감지'];
        }
        if ($skipRate > 0.3 || ($data['pattern_answers'] ?? false)) {
            return ['context_id' => 'S5_CTX_04', 'name' => '지루함 감지'];
        }

        return ['context_id' => 'S5_CTX_01', 'name' => '정서 안정'];
    }

    /**
     * Check for activity imbalance
     */
    private function checkActivityImbalance($data) {
        $activityDistribution = $data['activity_distribution'] ?? [];
        if (empty($activityDistribution)) return false;

        $max = max($activityDistribution);
        $total = array_sum($activityDistribution);
        return $total > 0 && ($max / $total) > 0.7;
    }

    /**
     * Get activity imbalance sub-context
     */
    private function getActivitySubContext($data) {
        $difficultyBias = $data['difficulty_bias'] ?? false;
        $assessmentOnly = $data['assessment_only'] ?? false;
        $timeConcentration = $data['time_concentration'] ?? false;

        if ($assessmentOnly) {
            return ['context_id' => 'S6_CTX_03', 'name' => '학습-평가 불균형'];
        }
        if ($difficultyBias) {
            return ['context_id' => 'S6_CTX_02', 'name' => '난이도 편중'];
        }
        if ($timeConcentration) {
            return ['context_id' => 'S6_CTX_04', 'name' => '시간 불균형'];
        }

        return ['context_id' => 'S6_CTX_01', 'name' => '특정 활동 편중'];
    }

    /**
     * Check for signature routine
     */
    private function checkSignatureRoutine($data) {
        return isset($data['routine_detected']) && $data['routine_detected'];
    }

    /**
     * Get signature routine sub-context
     */
    private function getSignatureSubContext($data) {
        $routineType = $data['routine_type'] ?? 'start';

        switch ($routineType) {
            case 'start':
                return ['context_id' => 'S7_CTX_01', 'name' => '학습 시작 루틴'];
            case 'problem':
                return ['context_id' => 'S7_CTX_02', 'name' => '문제 풀이 루틴'];
            case 'review':
                return ['context_id' => 'S7_CTX_03', 'name' => '복습 루틴'];
            case 'end':
                return ['context_id' => 'S7_CTX_04', 'name' => '학습 종료 루틴'];
            default:
                return ['context_id' => 'S7_CTX_01', 'name' => '시그니처 루틴'];
        }
    }

    /**
     * Determine primary situation based on priority
     */
    private function determinePrimarySituation($detected, $data) {
        // Priority: S4(오류) > S5(정서/좌절) > S1(이탈) > S2(지연) > S3(휴식) > S6(불균형) > S7(루틴)
        $priority = ['S4', 'S5', 'S1', 'S2', 'S3', 'S6', 'S7'];

        // Special case: 좌절 상태는 최우선
        if (isset($detected['S5']) && $detected['S5']['context_id'] === 'S5_CTX_03') {
            return array_merge($detected['S5'], ['situation' => 'S5']);
        }

        foreach ($priority as $situation) {
            if (isset($detected[$situation])) {
                return array_merge($detected[$situation], ['situation' => $situation]);
            }
        }

        // Default: stable emotional state
        return ['situation' => 'S5', 'context_id' => 'S5_CTX_01', 'name' => '안정 상태'];
    }

    /**
     * Detect interaction context
     */
    private function detectInteractionContext($data) {
        $preferredType = $data['preferred_interaction_type'] ?? 'I1';
        $currentActivity = $data['current_activity_type'] ?? 'text';

        $activityMap = [
            'text' => 'I1',
            'interactive' => 'I2',
            'routine' => 'I3',
            'flexible' => 'I4',
            'companion' => 'I5',
            'dialogue' => 'I6',
            'exploration' => 'I7'
        ];

        $detected = $activityMap[$currentActivity] ?? 'I1';

        return [
            'current' => $detected,
            'preferred' => $preferredType,
            'name' => $this->interactionContexts[$detected]['name'],
            'characteristics' => $this->interactionContexts[$detected]['characteristics']
        ];
    }

    /**
     * Detect environment context
     */
    private function detectEnvironmentContext($data) {
        $sessionDuration = $data['session_duration'] ?? 0;
        $deviceType = $data['device_type'] ?? 'desktop';
        $hour = (int)date('H');

        if ($deviceType === 'mobile' && $sessionDuration < 600) {
            return ['code' => 'E2_CTX', 'name' => '이동 환경'];
        }
        if ($hour >= 22 || $hour < 6) {
            return ['code' => 'E4_CTX', 'name' => '야간 환경'];
        }
        if ($sessionDuration > 1800) {
            return ['code' => 'E1_CTX', 'name' => '집중 환경'];
        }

        return ['code' => 'E3_CTX', 'name' => '일반 환경'];
    }

    /**
     * Detect temporal context
     */
    private function detectTemporalContext() {
        $hour = (int)date('H');

        if ($hour >= 6 && $hour < 9) {
            return ['code' => 'T1_CTX', 'name' => '아침 학습', 'recommended' => ['new_concept', 'challenging']];
        }
        if ($hour >= 9 && $hour < 12) {
            return ['code' => 'T2_CTX', 'name' => '오전 학습', 'recommended' => ['focus', 'complex']];
        }
        if ($hour >= 12 && $hour < 18) {
            return ['code' => 'T3_CTX', 'name' => '오후 학습', 'recommended' => ['practice', 'application']];
        }
        if ($hour >= 18 && $hour < 22) {
            return ['code' => 'T4_CTX', 'name' => '저녁 학습', 'recommended' => ['review', 'light_practice']];
        }

        return ['code' => 'T5_CTX', 'name' => '야간 학습', 'recommended' => ['quick_review', 'summary']];
    }

    /**
     * Save context to history
     *
     * @param array $context Context data
     * @param string $personaApplied Applied persona
     * @return bool Success status
     */
    public function saveContextHistory($context, $personaApplied) {
        try {
            $record = new stdClass();
            $record->userid = $this->userid;
            $record->context_id = $context['situation']['context_id'] ?? 'S5_CTX_01';
            $record->context_type = 'situation';
            $record->persona_applied = $personaApplied;
            $record->trigger_reason = json_encode($context['situation']['all_detected'] ?? []);
            $record->duration_seconds = 0; // Will be updated on context change
            $record->effectiveness_score = 0.00; // Will be calculated later
            $record->timecreated = time();

            $this->db->insert_record('agent19_context_history', $record);
            return true;
        } catch (Exception $e) {
            error_log("[Agent19ContextAnalyzer:saveContextHistory] Error at line " . __LINE__ . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get context definitions
     *
     * @param string|null $type 'situation' or 'interaction' or null for all
     * @return array Context definitions
     */
    public function getContextDefinitions($type = null) {
        if ($type === 'situation') {
            return $this->situationContexts;
        }
        if ($type === 'interaction') {
            return $this->interactionContexts;
        }
        return [
            'situation' => $this->situationContexts,
            'interaction' => $this->interactionContexts
        ];
    }
}
