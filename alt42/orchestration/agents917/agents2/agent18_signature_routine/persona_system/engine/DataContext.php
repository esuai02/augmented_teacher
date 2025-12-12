<?php
/**
 * Agent18 Signature Routine - Data Context
 *
 * 학습자의 루틴 분석을 위한 데이터 컨텍스트 관리.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/DataContext.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class DataContext {

    /** @var int 사용자 ID */
    private $userId;

    /** @var string 세션 ID */
    private $sessionId;

    /** @var string 사용자 메시지 */
    private $message = '';

    /** @var array 추가 컨텍스트 */
    private $additionalContext = [];

    /** @var array 루틴 분석 데이터 */
    private $routineAnalysis = [];

    /** @var array 학생 프로필 */
    private $studentProfile = [];

    /** @var array 학습 히스토리 */
    private $learningHistory = [];

    /** @var array 세션 데이터 */
    private $sessionData = [];

    /**
     * 생성자
     *
     * @param int $userId 사용자 ID
     * @param string $sessionId 세션 ID
     */
    public function __construct($userId, $sessionId) {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->loadStudentProfile();
    }

    /**
     * 학생 프로필 로드
     */
    private function loadStudentProfile() {
        global $DB;

        try {
            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $this->userId]);
            if ($user) {
                $this->studentProfile = [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email
                ];
            }

            // 사용자 역할 정보
            $userrole = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = '22'",
                [$this->userId]
            );
            if ($userrole) {
                $this->studentProfile['role'] = $userrole->data;
            }

        } catch (Exception $e) {
            error_log("[Agent18 DataContext] 프로필 로드 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
        }
    }

    /**
     * 루틴 데이터 로드
     */
    public function loadRoutineData() {
        global $DB;

        try {
            // 1. 학습 세션 히스토리 로드
            $this->loadLearningHistory();

            // 2. 시간대별 성과 분석
            $this->analyzeTimePerformance();

            // 3. 세션 길이 분석
            $this->analyzeSessionLength();

            // 4. 휴식 패턴 분석
            $this->analyzeBreakPatterns();

            // 5. 이전 루틴 분석 기록
            $this->loadPreviousAnalysis();

        } catch (Exception $e) {
            error_log("[Agent18 DataContext] 루틴 데이터 로드 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
        }
    }

    /**
     * 학습 히스토리 로드
     */
    private function loadLearningHistory() {
        global $DB;

        // 최근 30일간의 학습 기록
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

        $sessions = $DB->get_records_sql(
            "SELECT *
             FROM {alt42_learning_sessions}
             WHERE userid = ? AND started_at >= ?
             ORDER BY started_at DESC",
            [$this->userId, $thirtyDaysAgo]
        );

        $this->learningHistory = [];
        foreach ($sessions as $session) {
            $this->learningHistory[] = [
                'id' => $session->id,
                'started_at' => $session->started_at,
                'ended_at' => $session->ended_at ?? null,
                'duration' => $session->duration ?? 0,
                'subject' => $session->subject ?? 'unknown',
                'performance_score' => $session->performance_score ?? 0,
                'break_count' => $session->break_count ?? 0
            ];
        }

        $this->sessionData['session_count'] = count($this->learningHistory);
    }

    /**
     * 시간대별 성과 분석
     */
    private function analyzeTimePerformance() {
        $timeSlots = [
            'morning' => ['start' => 6, 'end' => 12, 'sessions' => [], 'total_score' => 0],
            'afternoon' => ['start' => 12, 'end' => 18, 'sessions' => [], 'total_score' => 0],
            'evening' => ['start' => 18, 'end' => 22, 'sessions' => [], 'total_score' => 0],
            'night' => ['start' => 22, 'end' => 6, 'sessions' => [], 'total_score' => 0]
        ];

        foreach ($this->learningHistory as $session) {
            $hour = (int)date('H', $session['started_at']);

            foreach ($timeSlots as $slotName => &$slot) {
                $inSlot = false;
                if ($slotName === 'night') {
                    $inSlot = ($hour >= 22 || $hour < 6);
                } else {
                    $inSlot = ($hour >= $slot['start'] && $hour < $slot['end']);
                }

                if ($inSlot) {
                    $slot['sessions'][] = $session;
                    $slot['total_score'] += $session['performance_score'];
                    break;
                }
            }
        }

        // 최고 성과 시간대 결정
        $bestSlot = null;
        $bestAvg = 0;
        $avgScores = [];

        foreach ($timeSlots as $slotName => $slot) {
            $count = count($slot['sessions']);
            if ($count > 0) {
                $avg = $slot['total_score'] / $count;
                $avgScores[$slotName] = $avg;

                if ($avg > $bestAvg) {
                    $bestAvg = $avg;
                    $bestSlot = $slotName;
                }
            }
        }

        $this->sessionData['time_performance'] = $avgScores;
        $this->sessionData['best_performance_time'] = $bestSlot;
        $this->sessionData['morning_session_count'] = count($timeSlots['morning']['sessions']);
        $this->sessionData['afternoon_session_count'] = count($timeSlots['afternoon']['sessions']);
        $this->sessionData['evening_session_count'] = count($timeSlots['evening']['sessions']);
        $this->sessionData['night_session_count'] = count($timeSlots['night']['sessions']);

        // 성과 비율 계산
        if (!empty($avgScores)) {
            $overallAvg = array_sum($avgScores) / count($avgScores);
            if ($overallAvg > 0 && $bestSlot) {
                $this->sessionData['time_performance_ratio'] = $avgScores[$bestSlot] / $overallAvg;
            }
        }
    }

    /**
     * 세션 길이 분석
     */
    private function analyzeSessionLength() {
        if (empty($this->learningHistory)) {
            $this->sessionData['avg_session_length'] = 0;
            return;
        }

        $durations = array_column($this->learningHistory, 'duration');
        $avgDuration = array_sum($durations) / count($durations);

        $this->sessionData['avg_session_length'] = $avgDuration / 60; // 분 단위

        // 세션 길이 분포
        $shortSessions = 0; // < 30분
        $mediumSessions = 0; // 30-60분
        $longSessions = 0; // > 60분

        foreach ($durations as $duration) {
            $minutes = $duration / 60;
            if ($minutes < 30) {
                $shortSessions++;
            } elseif ($minutes <= 60) {
                $mediumSessions++;
            } else {
                $longSessions++;
            }
        }

        $this->sessionData['session_length_distribution'] = [
            'short' => $shortSessions,
            'medium' => $mediumSessions,
            'long' => $longSessions
        ];
    }

    /**
     * 휴식 패턴 분석
     */
    private function analyzeBreakPatterns() {
        $totalBreaks = 0;
        $sessionCount = count($this->learningHistory);

        foreach ($this->learningHistory as $session) {
            $totalBreaks += $session['break_count'];
        }

        $this->sessionData['break_frequency'] = $sessionCount > 0 ?
            $totalBreaks / $sessionCount : 0;

        // 하루 평균 세션 수 계산
        if (!empty($this->learningHistory)) {
            $firstSession = end($this->learningHistory);
            $lastSession = reset($this->learningHistory);
            $daySpan = max(1, ($lastSession['started_at'] - $firstSession['started_at']) / 86400);
            $this->sessionData['daily_session_count'] = $sessionCount / $daySpan;
        }
    }

    /**
     * 이전 분석 기록 로드
     */
    private function loadPreviousAnalysis() {
        global $DB;

        $analysisCount = $DB->count_records('alt42_agent18_routine_patterns', [
            'userid' => $this->userId
        ]);

        $this->sessionData['routine_analysis_count'] = $analysisCount;

        // 이전 패턴 발견 여부
        $pattern = $DB->get_record_sql(
            "SELECT * FROM {alt42_agent18_routine_patterns}
             WHERE userid = ?
             ORDER BY created_at DESC
             LIMIT 1",
            [$this->userId]
        );

        if ($pattern) {
            $this->sessionData['pattern_confidence'] = $pattern->confidence;
            $this->sessionData['pattern_notified'] = (bool)$pattern->notified;
            $this->sessionData['golden_time_notified'] =
                (bool)($pattern->golden_time_notified ?? false);
        }
    }

    // === Getters and Setters ===

    public function setMessage($message) {
        $this->message = $message;
        $this->sessionData['user_message'] = $message;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setAdditionalContext($context) {
        $this->additionalContext = $context;
    }

    public function setRoutineAnalysis($analysis) {
        $this->routineAnalysis = $analysis;
    }

    public function getRoutineAnalysis() {
        return array_merge($this->sessionData, $this->routineAnalysis);
    }

    public function getStudentProfile() {
        return $this->studentProfile;
    }

    public function getLearningHistory() {
        return $this->learningHistory;
    }

    /**
     * 특정 필드 값 가져오기
     *
     * @param string $field 필드명
     * @return mixed 필드 값
     */
    public function getField($field) {
        // 세션 데이터에서 먼저 검색
        if (isset($this->sessionData[$field])) {
            return $this->sessionData[$field];
        }

        // 루틴 분석 데이터에서 검색
        if (isset($this->routineAnalysis[$field])) {
            return $this->routineAnalysis[$field];
        }

        // 학생 프로필에서 검색
        if (isset($this->studentProfile[$field])) {
            return $this->studentProfile[$field];
        }

        // 추가 컨텍스트에서 검색
        if (isset($this->additionalContext[$field])) {
            return $this->additionalContext[$field];
        }

        return null;
    }

    /**
     * 식별된 페르소나 목록 설정
     *
     * @param array $personas 페르소나 목록
     */
    public function setIdentifiedPersonas($personas) {
        $this->sessionData['identified_personas'] = array_keys($personas);
    }
}

/*
 * DB 테이블 정보:
 *
 * 1. mdl_alt42_learning_sessions
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - started_at: bigint(10) NOT NULL
 *    - ended_at: bigint(10)
 *    - duration: int(10) (초 단위)
 *    - subject: varchar(100)
 *    - performance_score: decimal(5,2)
 *    - break_count: int(5)
 */
