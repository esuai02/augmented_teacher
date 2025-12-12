<?php
/**
 * Agent09DataContext - 학습관리 에이전트 데이터 컨텍스트
 *
 * Agent09(학습관리)에 특화된 데이터 컨텍스트 구현
 * 출결, 목표, 포모도로, 오답노트, 시험 데이터 접근 제공
 *
 * @package AugmentedTeacher\Agent09\PersonaSystem
 * @version 1.0
 */

// 공통 데이터 컨텍스트 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseDataContext.php');

class Agent09DataContext extends BaseDataContext {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var int 데이터 조회 기본 일수 */
    protected $defaultDays = 30;

    /** @var array 학습관리 특화 감정 키워드 */
    protected $learningEmotionKeywords = [
        'motivated' => ['열심히', '화이팅', '할 수 있어', '잘하고', '신나', '기대'],
        'frustrated_learning' => ['왜 안돼', '이해가 안', '어려워', '못하겠', '포기'],
        'anxious_exam' => ['시험', '걱정', '불안', '긴장', '두려워'],
        'tired' => ['피곤', '지침', '힘들어', '졸려', '쉬고싶']
    ];

    /** @var array 학습관리 특화 의도 패턴 */
    protected $learningIntentPatterns = [
        'progress_check' => ['얼마나', '진행', '달성률', '몇 %', '현황'],
        'routine_inquiry' => ['루틴', '스케줄', '일정', '언제', '계획'],
        'motivation_request' => ['동기', '의욕', '힘내', '격려', '응원'],
        'review_request' => ['복습', '오답', '틀린 문제', '다시', '정리']
    ];

    /**
     * 생성자
     */
    public function __construct(string $agentId = 'agent09') {
        parent::__construct($agentId);

        // 학습관리 특화 키워드 추가
        foreach ($this->learningEmotionKeywords as $emotion => $keywords) {
            $this->addEmotionKeywords($emotion, $keywords);
        }

        foreach ($this->learningIntentPatterns as $intent => $patterns) {
            $this->addIntentPatterns($intent, $patterns);
        }
    }

    /**
     * 출결 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 일수 (기본 30일)
     * @return array 출결 데이터
     */
    public function getAttendanceData(int $userId, int $days = null): array {
        global $DB;

        $days = $days ?? $this->defaultDays;
        $cacheKey = "attendance_{$userId}_{$days}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 출결 로그 조회
            $sql = "SELECT
                        DATE(attendance_date) as date,
                        status,
                        check_in_time,
                        check_out_time,
                        TIMESTAMPDIFF(MINUTE, check_in_time, check_out_time) as duration_minutes
                    FROM mdl_at_attendance_log
                    WHERE user_id = ?
                    AND attendance_date >= ?
                    ORDER BY attendance_date DESC";

            $records = $DB->get_records_sql($sql, [$userId, $startDate]);

            // 통계 계산
            $totalDays = count($records);
            $presentDays = 0;
            $absentDays = 0;
            $lateDays = 0;
            $recentAbsenceCount = 0;
            $consecutiveAbsence = 0;
            $maxConsecutiveAbsence = 0;
            $totalDuration = 0;

            $prevDate = null;
            foreach ($records as $record) {
                switch ($record->status) {
                    case 'present':
                        $presentDays++;
                        $consecutiveAbsence = 0;
                        break;
                    case 'absent':
                        $absentDays++;
                        $consecutiveAbsence++;
                        $maxConsecutiveAbsence = max($maxConsecutiveAbsence, $consecutiveAbsence);
                        // 최근 7일 결석 카운트
                        if (strtotime($record->date) >= strtotime('-7 days')) {
                            $recentAbsenceCount++;
                        }
                        break;
                    case 'late':
                        $lateDays++;
                        $presentDays++; // 지각도 출석으로 카운트
                        $consecutiveAbsence = 0;
                        break;
                }

                if ($record->duration_minutes) {
                    $totalDuration += $record->duration_minutes;
                }
            }

            $attendanceRate = $totalDays > 0 ? $presentDays / $totalDays : 0;
            $avgDuration = $presentDays > 0 ? $totalDuration / $presentDays : 0;

            $data = [
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'attendance_rate' => round($attendanceRate, 2),
                'recent_absence_count' => $recentAbsenceCount,
                'max_consecutive_absence' => $maxConsecutiveAbsence,
                'avg_duration_minutes' => round($avgDuration, 0),
                'trend' => $this->calculateAttendanceTrend($records),
                'last_attendance_date' => !empty($records) ? reset($records)->date : null
            ];

            $this->cache[$cacheKey] = $data;
            return $data;

        } catch (Exception $e) {
            error_log("[Agent09DataContext] {$this->currentFile}:" . __LINE__ .
                " - 출결 데이터 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 목표 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @return array 목표 데이터
     */
    public function getGoalData(int $userId): array {
        global $DB;

        $cacheKey = "goals_{$userId}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // 활성 목표 조회
            $sql = "SELECT
                        id, goal_type, goal_title, target_value, current_value,
                        start_date, due_date, status, priority,
                        DATEDIFF(due_date, CURDATE()) as d_day
                    FROM mdl_at_student_goals
                    WHERE user_id = ?
                    AND (status = 'active' OR status = 'completed')
                    ORDER BY priority DESC, due_date ASC";

            $goals = $DB->get_records_sql($sql, [$userId]);

            $activeGoals = [];
            $completedGoals = [];
            $totalAchievement = 0;
            $activeCount = 0;

            foreach ($goals as $goal) {
                $achievement = $goal->target_value > 0
                    ? min(1, $goal->current_value / $goal->target_value)
                    : 0;

                $goalData = [
                    'id' => $goal->id,
                    'type' => $goal->goal_type,
                    'title' => $goal->goal_title,
                    'target' => $goal->target_value,
                    'current' => $goal->current_value,
                    'achievement_rate' => round($achievement, 2),
                    'd_day' => $goal->d_day,
                    'priority' => $goal->priority
                ];

                if ($goal->status === 'active') {
                    $activeGoals[] = $goalData;
                    $totalAchievement += $achievement;
                    $activeCount++;
                } else {
                    $completedGoals[] = $goalData;
                }
            }

            // D-Day 임박 목표 (3일 이내)
            $urgentGoals = array_filter($activeGoals, function($g) {
                return isset($g['d_day']) && $g['d_day'] <= 3 && $g['d_day'] >= 0;
            });

            $data = [
                'active_goals' => $activeGoals,
                'completed_goals' => array_slice($completedGoals, 0, 5), // 최근 5개만
                'active_count' => $activeCount,
                'achievement_rate' => $activeCount > 0 ? round($totalAchievement / $activeCount, 2) : 0,
                'urgent_goals' => array_values($urgentGoals),
                'has_overdue' => !empty(array_filter($activeGoals, function($g) {
                    return isset($g['d_day']) && $g['d_day'] < 0;
                }))
            ];

            $this->cache[$cacheKey] = $data;
            return $data;

        } catch (Exception $e) {
            error_log("[Agent09DataContext] {$this->currentFile}:" . __LINE__ .
                " - 목표 데이터 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 포모도로 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 일수 (기본 30일)
     * @return array 포모도로 데이터
     */
    public function getPomodoroData(int $userId, int $days = null): array {
        global $DB;

        $days = $days ?? $this->defaultDays;
        $cacheKey = "pomodoro_{$userId}_{$days}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 포모도로 세션 조회
            $sql = "SELECT
                        DATE(session_date) as date,
                        planned_sessions,
                        completed_sessions,
                        focus_duration_minutes,
                        break_duration_minutes,
                        interruption_count
                    FROM mdl_at_pomodoro_sessions
                    WHERE user_id = ?
                    AND session_date >= ?
                    ORDER BY session_date DESC";

            $sessions = $DB->get_records_sql($sql, [$userId, $startDate]);

            $totalPlanned = 0;
            $totalCompleted = 0;
            $totalFocusMinutes = 0;
            $totalInterruptions = 0;
            $dailyCompletions = [];

            foreach ($sessions as $session) {
                $totalPlanned += $session->planned_sessions;
                $totalCompleted += $session->completed_sessions;
                $totalFocusMinutes += $session->focus_duration_minutes;
                $totalInterruptions += $session->interruption_count;
                $dailyCompletions[] = $session->planned_sessions > 0
                    ? $session->completed_sessions / $session->planned_sessions
                    : 0;
            }

            // 일관성 점수 계산 (표준편차 기반)
            $consistencyScore = $this->calculateConsistencyScore($dailyCompletions);

            $data = [
                'total_planned' => $totalPlanned,
                'total_completed' => $totalCompleted,
                'completion_rate' => $totalPlanned > 0 ? round($totalCompleted / $totalPlanned, 2) : 0,
                'total_focus_minutes' => $totalFocusMinutes,
                'avg_daily_focus' => count($sessions) > 0 ? round($totalFocusMinutes / count($sessions), 0) : 0,
                'total_interruptions' => $totalInterruptions,
                'avg_interruptions' => count($sessions) > 0 ? round($totalInterruptions / count($sessions), 1) : 0,
                'consistency_score' => $consistencyScore,
                'session_count' => count($sessions),
                'best_streak' => $this->calculateBestStreak($sessions),
                'recent_trend' => $this->calculatePomodoroTrend($sessions)
            ];

            $this->cache[$cacheKey] = $data;
            return $data;

        } catch (Exception $e) {
            error_log("[Agent09DataContext] {$this->currentFile}:" . __LINE__ .
                " - 포모도로 데이터 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 오답노트 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 일수 (기본 30일)
     * @return array 오답노트 데이터
     */
    public function getWrongNoteData(int $userId, int $days = null): array {
        global $DB;

        $days = $days ?? $this->defaultDays;
        $cacheKey = "wrong_notes_{$userId}_{$days}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 오답노트 조회
            $sql = "SELECT
                        id, concept_name, concept_category, error_type,
                        error_count, review_count, mastery_level,
                        created_at, last_review_date,
                        DATEDIFF(CURDATE(), last_review_date) as days_since_review
                    FROM mdl_at_wrong_notes
                    WHERE user_id = ?
                    AND created_at >= ?
                    ORDER BY error_count DESC, last_review_date ASC";

            $notes = $DB->get_records_sql($sql, [$userId, $startDate]);

            $totalErrors = 0;
            $totalReviews = 0;
            $needsReview = [];
            $masteredConcepts = [];
            $conceptCategories = [];

            foreach ($notes as $note) {
                $totalErrors += $note->error_count;
                $totalReviews += $note->review_count;

                // 복습 필요 (7일 이상 미복습 또는 숙달도 낮음)
                if ($note->days_since_review >= 7 || $note->mastery_level < 0.5) {
                    $needsReview[] = [
                        'id' => $note->id,
                        'concept' => $note->concept_name,
                        'category' => $note->concept_category,
                        'error_count' => $note->error_count,
                        'days_since_review' => $note->days_since_review,
                        'mastery_level' => $note->mastery_level
                    ];
                }

                // 숙달된 개념
                if ($note->mastery_level >= 0.8) {
                    $masteredConcepts[] = $note->concept_name;
                }

                // 카테고리별 집계
                $category = $note->concept_category;
                if (!isset($conceptCategories[$category])) {
                    $conceptCategories[$category] = ['count' => 0, 'errors' => 0];
                }
                $conceptCategories[$category]['count']++;
                $conceptCategories[$category]['errors'] += $note->error_count;
            }

            // 취약 카테고리 식별 (에러 수 기준 상위 3개)
            arsort($conceptCategories);
            $weakCategories = array_slice(array_keys($conceptCategories), 0, 3);

            $data = [
                'total_notes' => count($notes),
                'total_errors' => $totalErrors,
                'total_reviews' => $totalReviews,
                'avg_review_per_note' => count($notes) > 0 ? round($totalReviews / count($notes), 1) : 0,
                'needs_review' => array_slice($needsReview, 0, 10), // 상위 10개
                'needs_review_count' => count($needsReview),
                'mastered_count' => count($masteredConcepts),
                'weak_categories' => $weakCategories,
                'category_breakdown' => $conceptCategories
            ];

            $this->cache[$cacheKey] = $data;
            return $data;

        } catch (Exception $e) {
            error_log("[Agent09DataContext] {$this->currentFile}:" . __LINE__ .
                " - 오답노트 데이터 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 시험 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수 (기본 10개)
     * @return array 시험 데이터
     */
    public function getTestData(int $userId, int $limit = 10): array {
        global $DB;

        $cacheKey = "tests_{$userId}_{$limit}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // 시험 결과 조회
            $sql = "SELECT
                        id, test_name, test_type, test_date,
                        score, max_score, percentile,
                        weak_areas, strong_areas
                    FROM mdl_at_test_results
                    WHERE user_id = ?
                    ORDER BY test_date DESC
                    LIMIT ?";

            $tests = $DB->get_records_sql($sql, [$userId, $limit]);

            $scores = [];
            $testHistory = [];
            $allWeakAreas = [];
            $allStrongAreas = [];

            foreach ($tests as $test) {
                $scorePercent = $test->max_score > 0
                    ? round(($test->score / $test->max_score) * 100, 1)
                    : 0;
                $scores[] = $scorePercent;

                $testHistory[] = [
                    'id' => $test->id,
                    'name' => $test->test_name,
                    'type' => $test->test_type,
                    'date' => $test->test_date,
                    'score' => $test->score,
                    'max_score' => $test->max_score,
                    'score_percent' => $scorePercent,
                    'percentile' => $test->percentile
                ];

                // 취약/강점 영역 집계
                if ($test->weak_areas) {
                    $weak = json_decode($test->weak_areas, true) ?: [];
                    $allWeakAreas = array_merge($allWeakAreas, $weak);
                }
                if ($test->strong_areas) {
                    $strong = json_decode($test->strong_areas, true) ?: [];
                    $allStrongAreas = array_merge($allStrongAreas, $strong);
                }
            }

            // 성적 추이 계산
            $scoreTrend = $this->calculateScoreTrend($scores);

            // 빈도 기준 취약/강점 영역
            $weakAreaCounts = array_count_values($allWeakAreas);
            $strongAreaCounts = array_count_values($allStrongAreas);
            arsort($weakAreaCounts);
            arsort($strongAreaCounts);

            $data = [
                'test_count' => count($tests),
                'test_history' => $testHistory,
                'average_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0,
                'highest_score' => count($scores) > 0 ? max($scores) : 0,
                'lowest_score' => count($scores) > 0 ? min($scores) : 0,
                'score_trend' => $scoreTrend,
                'weak_areas' => array_slice(array_keys($weakAreaCounts), 0, 5),
                'strong_areas' => array_slice(array_keys($strongAreaCounts), 0, 5),
                'last_test_date' => !empty($testHistory) ? $testHistory[0]['date'] : null
            ];

            $this->cache[$cacheKey] = $data;
            return $data;

        } catch (Exception $e) {
            error_log("[Agent09DataContext] {$this->currentFile}:" . __LINE__ .
                " - 시험 데이터 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 출결 추이 계산
     */
    protected function calculateAttendanceTrend(array $records): string {
        if (count($records) < 7) {
            return 'insufficient_data';
        }

        // 최근 7일 vs 이전 7일 비교
        $recent = array_slice($records, 0, 7);
        $previous = array_slice($records, 7, 7);

        $recentPresent = count(array_filter($recent, function($r) {
            return $r->status === 'present' || $r->status === 'late';
        }));

        $previousPresent = count(array_filter($previous, function($r) {
            return $r->status === 'present' || $r->status === 'late';
        }));

        if ($recentPresent > $previousPresent) return 'improving';
        if ($recentPresent < $previousPresent) return 'declining';
        return 'stable';
    }

    /**
     * 일관성 점수 계산 (표준편차 기반)
     */
    protected function calculateConsistencyScore(array $values): float {
        if (count($values) < 2) {
            return 0.5;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        $stdDev = sqrt($variance / count($values));

        // 표준편차가 낮을수록 일관성 높음 (0~1 점수로 변환)
        $consistencyScore = max(0, 1 - ($stdDev * 2));

        return round($consistencyScore, 2);
    }

    /**
     * 최고 연속 기록 계산
     */
    protected function calculateBestStreak(array $sessions): int {
        $currentStreak = 0;
        $bestStreak = 0;

        foreach ($sessions as $session) {
            if ($session->completed_sessions >= $session->planned_sessions * 0.8) {
                $currentStreak++;
                $bestStreak = max($bestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return $bestStreak;
    }

    /**
     * 포모도로 추이 계산
     */
    protected function calculatePomodoroTrend(array $sessions): string {
        if (count($sessions) < 7) {
            return 'insufficient_data';
        }

        $recent = array_slice($sessions, 0, 7);
        $previous = array_slice($sessions, 7, 7);

        $recentAvg = 0;
        $previousAvg = 0;

        foreach ($recent as $s) {
            $recentAvg += $s->completed_sessions;
        }
        foreach ($previous as $s) {
            $previousAvg += $s->completed_sessions;
        }

        $recentAvg /= max(1, count($recent));
        $previousAvg /= max(1, count($previous));

        if ($recentAvg > $previousAvg * 1.1) return 'improving';
        if ($recentAvg < $previousAvg * 0.9) return 'declining';
        return 'stable';
    }

    /**
     * 성적 추이 계산
     */
    protected function calculateScoreTrend(array $scores): string {
        if (count($scores) < 3) {
            return 'insufficient_data';
        }

        // 최근 3개 vs 이전 3개 비교
        $recent = array_slice($scores, 0, 3);
        $previous = array_slice($scores, 3, 3);

        if (empty($previous)) {
            return 'insufficient_data';
        }

        $recentAvg = array_sum($recent) / count($recent);
        $previousAvg = array_sum($previous) / count($previous);

        $diff = $recentAvg - $previousAvg;

        if ($diff > 5) return 'improving';
        if ($diff < -5) return 'declining';
        return 'stable';
    }

    /**
     * 학습 상황 결정 (오버라이드)
     */
    public function determineSituation(array $sessionData): string {
        // 이탈 위험 체크
        $dropoutRisk = $sessionData['dropout_risk'] ?? [];
        if (isset($dropoutRisk['level'])) {
            switch ($dropoutRisk['level']) {
                case 'high': return 'dropout_risk_high';
                case 'medium': return 'dropout_risk_medium';
                case 'low': return 'dropout_risk_low';
            }
        }

        // 데이터 밀도 체크
        $patternType = $sessionData['pattern_type'] ?? '';
        if ($patternType === 'data_sparse') {
            return 'data_collection';
        }

        // 감정 상태 체크
        $emotion = $sessionData['emotional_state'] ?? 'neutral';
        if (in_array($emotion, ['frustrated_learning', 'anxious_exam', 'tired'])) {
            return 'emotional_support';
        }

        // 학습 의도 기반
        $intent = $sessionData['learning_intent'] ?? '';
        switch ($intent) {
            case 'progress_check': return 'progress_inquiry';
            case 'routine_inquiry': return 'routine_adjustment';
            case 'review_request': return 'concept_review';
        }

        // 기본 상황 결정
        return parent::determineSituation($sessionData);
    }

    /**
     * 종합 학습 상태 요약 생성
     *
     * @param int $userId 사용자 ID
     * @return array 학습 상태 요약
     */
    public function getLearningStatusSummary(int $userId): array {
        $attendance = $this->getAttendanceData($userId);
        $goals = $this->getGoalData($userId);
        $pomodoro = $this->getPomodoroData($userId);
        $wrongNotes = $this->getWrongNoteData($userId);
        $tests = $this->getTestData($userId);

        // 종합 건강도 점수 (0~100)
        $healthScore = 0;
        $factors = 0;

        if (!empty($attendance)) {
            $healthScore += ($attendance['attendance_rate'] ?? 0) * 25;
            $factors++;
        }
        if (!empty($goals)) {
            $healthScore += ($goals['achievement_rate'] ?? 0) * 25;
            $factors++;
        }
        if (!empty($pomodoro)) {
            $healthScore += ($pomodoro['completion_rate'] ?? 0) * 25;
            $factors++;
        }
        if (!empty($tests) && $tests['average_score'] > 0) {
            $healthScore += min(1, $tests['average_score'] / 100) * 25;
            $factors++;
        }

        $normalizedHealth = $factors > 0 ? round($healthScore / $factors * 4, 0) : 50;

        return [
            'health_score' => $normalizedHealth,
            'attendance_summary' => [
                'rate' => $attendance['attendance_rate'] ?? 0,
                'trend' => $attendance['trend'] ?? 'unknown'
            ],
            'goal_summary' => [
                'achievement_rate' => $goals['achievement_rate'] ?? 0,
                'active_count' => $goals['active_count'] ?? 0,
                'urgent_count' => count($goals['urgent_goals'] ?? [])
            ],
            'pomodoro_summary' => [
                'completion_rate' => $pomodoro['completion_rate'] ?? 0,
                'consistency' => $pomodoro['consistency_score'] ?? 0
            ],
            'review_summary' => [
                'needs_review_count' => $wrongNotes['needs_review_count'] ?? 0,
                'weak_categories' => $wrongNotes['weak_categories'] ?? []
            ],
            'test_summary' => [
                'average_score' => $tests['average_score'] ?? 0,
                'trend' => $tests['score_trend'] ?? 'unknown'
            ],
            'recommendations' => $this->generateRecommendations($attendance, $goals, $pomodoro, $wrongNotes, $tests)
        ];
    }

    /**
     * 개선 권장사항 생성
     */
    protected function generateRecommendations(
        array $attendance,
        array $goals,
        array $pomodoro,
        array $wrongNotes,
        array $tests
    ): array {
        $recommendations = [];

        // 출결 관련
        if (isset($attendance['recent_absence_count']) && $attendance['recent_absence_count'] >= 2) {
            $recommendations[] = [
                'type' => 'attendance',
                'priority' => 'high',
                'message' => '최근 결석이 잦아요. 출석 습관을 되찾아볼까요?'
            ];
        }

        // 목표 관련
        if (isset($goals['has_overdue']) && $goals['has_overdue']) {
            $recommendations[] = [
                'type' => 'goal',
                'priority' => 'high',
                'message' => '기한이 지난 목표가 있어요. 목표를 조정해볼까요?'
            ];
        }

        // 포모도로 관련
        if (isset($pomodoro['completion_rate']) && $pomodoro['completion_rate'] < 0.5) {
            $recommendations[] = [
                'type' => 'pomodoro',
                'priority' => 'medium',
                'message' => '포모도로 완료율이 낮아요. 목표 세션 수를 조절해볼까요?'
            ];
        }

        // 복습 관련
        if (isset($wrongNotes['needs_review_count']) && $wrongNotes['needs_review_count'] >= 5) {
            $recommendations[] = [
                'type' => 'review',
                'priority' => 'medium',
                'message' => '복습이 필요한 개념이 ' . $wrongNotes['needs_review_count'] . '개 있어요.'
            ];
        }

        // 성적 관련
        if (isset($tests['score_trend']) && $tests['score_trend'] === 'declining') {
            $recommendations[] = [
                'type' => 'test',
                'priority' => 'high',
                'message' => '최근 성적이 하락 추세예요. 취약 영역을 집중 복습해볼까요?'
            ];
        }

        // 우선순위로 정렬
        usort($recommendations, function($a, $b) {
            $priority = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($priority[$b['priority']] ?? 0) - ($priority[$a['priority']] ?? 0);
        });

        return array_slice($recommendations, 0, 5);
    }
}

/*
 * 관련 DB 테이블:
 *
 * mdl_at_attendance_log (출결 로그)
 * - id: INT (PK)
 * - user_id: INT (FK -> mdl_user)
 * - attendance_date: DATE
 * - status: ENUM('present', 'absent', 'late', 'excused')
 * - check_in_time: DATETIME
 * - check_out_time: DATETIME
 * - created_at: DATETIME
 *
 * mdl_at_student_goals (학생 목표)
 * - id: INT (PK)
 * - user_id: INT (FK -> mdl_user)
 * - goal_type: VARCHAR(50) (daily, weekly, monthly, custom)
 * - goal_title: VARCHAR(255)
 * - target_value: INT
 * - current_value: INT
 * - start_date: DATE
 * - due_date: DATE
 * - status: ENUM('active', 'completed', 'cancelled')
 * - priority: INT (1-5)
 * - created_at: DATETIME
 * - updated_at: DATETIME
 *
 * mdl_at_pomodoro_sessions (포모도로 세션)
 * - id: INT (PK)
 * - user_id: INT (FK -> mdl_user)
 * - session_date: DATE
 * - planned_sessions: INT
 * - completed_sessions: INT
 * - focus_duration_minutes: INT
 * - break_duration_minutes: INT
 * - interruption_count: INT
 * - created_at: DATETIME
 *
 * mdl_at_wrong_notes (오답노트)
 * - id: INT (PK)
 * - user_id: INT (FK -> mdl_user)
 * - concept_name: VARCHAR(100)
 * - concept_category: VARCHAR(50)
 * - error_type: VARCHAR(50)
 * - error_count: INT
 * - review_count: INT
 * - mastery_level: DECIMAL(3,2)
 * - created_at: DATETIME
 * - last_review_date: DATE
 *
 * mdl_at_test_results (시험 결과)
 * - id: INT (PK)
 * - user_id: INT (FK -> mdl_user)
 * - test_name: VARCHAR(100)
 * - test_type: VARCHAR(50)
 * - test_date: DATE
 * - score: DECIMAL(5,2)
 * - max_score: DECIMAL(5,2)
 * - percentile: INT
 * - weak_areas: JSON
 * - strong_areas: JSON
 * - created_at: DATETIME
 */
