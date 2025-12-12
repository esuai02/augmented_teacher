<?php
/**
 * DataContext - 데이터 컨텍스트 관리자
 *
 * 학생 데이터 및 에이전트 데이터를 로드하고 관리
 * 문제 재정의에 필요한 다양한 데이터 소스 통합
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 *
 * 관련 DB 테이블:
 * - mdl_user: 사용자 기본 정보
 * - augmented_teacher_sessions: 세션 데이터
 * - at_agent_persona_state: 페르소나 상태
 * - at_agent_data: 에이전트 데이터
 * - at_student_performance: 학습 성과
 * - at_emotion_logs: 감정 로그
 * - at_study_patterns: 학습 패턴
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class DataContext {

    /** @var int 캐시 유효 시간 (초) */
    const CACHE_TTL = 300;

    /** @var array 데이터 캐시 */
    private $cache = [];

    /** @var array 캐시 타임스탬프 */
    private $cacheTimestamps = [];

    /**
     * 학생 데이터 로드
     *
     * @param int $userId 사용자 ID
     * @return array 컨텍스트 데이터
     */
    public function loadStudentData($userId) {
        // 캐시 확인
        $cacheKey = "student_$userId";
        if ($this->isCacheValid($cacheKey)) {
            return $this->cache[$cacheKey];
        }

        global $DB;

        $context = [
            'user_id' => $userId,
            'user_info' => $this->loadUserInfo($userId),
            'student_level' => $this->determineStudentLevel($userId),
            'agent_data' => $this->loadAgentData($userId),
            'math_unit_vulnerability' => $this->loadMathVulnerability($userId),
            'previous_personas' => $this->loadPreviousPersonas($userId),
            'session_history' => $this->loadSessionHistory($userId)
        ];

        // 캐시 저장
        $this->setCache($cacheKey, $context);

        return $context;
    }

    /**
     * 사용자 기본 정보 로드
     *
     * @param int $userId 사용자 ID
     * @return array 사용자 정보
     */
    private function loadUserInfo($userId) {
        global $DB;

        try {
            $user = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname, email');

            if (!$user) {
                return ['id' => $userId, 'name' => 'Unknown'];
            }

            // 사용자 역할 조회
            $roleData = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );

            return [
                'id' => $user->id,
                'name' => $user->firstname . ' ' . $user->lastname,
                'email' => $user->email,
                'role' => $roleData ? $roleData->data : 'student'
            ];

        } catch (Exception $e) {
            error_log("Failed to load user info: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return ['id' => $userId, 'name' => 'Unknown'];
        }
    }

    /**
     * 학생 수준 결정
     *
     * @param int $userId 사용자 ID
     * @return string 수준 (low, mid, high)
     */
    private function determineStudentLevel($userId) {
        global $DB;

        try {
            // 최근 성과 데이터 조회
            $performance = $DB->get_record_sql(
                "SELECT AVG(score) as avg_score
                 FROM {at_student_performance}
                 WHERE userid = ?
                 AND timecreated > ?",
                [$userId, time() - (30 * 24 * 60 * 60)] // 최근 30일
            );

            if ($performance && $performance->avg_score !== null) {
                $avgScore = floatval($performance->avg_score);
                if ($avgScore >= 80) return 'high';
                if ($avgScore >= 50) return 'mid';
                return 'low';
            }

            return 'mid'; // 기본값

        } catch (Exception $e) {
            return 'mid';
        }
    }

    /**
     * 에이전트 데이터 로드
     *
     * @param int $userId 사용자 ID
     * @return array 에이전트 데이터
     */
    private function loadAgentData($userId) {
        global $DB;

        $agentData = [
            'performance' => $this->loadPerformanceData($userId),
            'dropout_events' => $this->loadDropoutEvents($userId),
            'error_patterns' => $this->loadErrorPatterns($userId),
            'study_patterns' => $this->loadStudyPatterns($userId),
            'time_management' => $this->loadTimeManagement($userId),
            'emotion_logs' => $this->loadEmotionLogs($userId),
            'concept_test_scores' => $this->loadConceptScores($userId),
            'teacher_feedback' => $this->loadTeacherFeedback($userId),
            'strategy_data' => $this->loadStrategyData($userId),
            'recovery_data' => $this->loadRecoveryData($userId)
        ];

        return $agentData;
    }

    /**
     * 성과 데이터 로드
     */
    private function loadPerformanceData($userId) {
        global $DB;

        try {
            // 최근 2주간 점수 추이
            $twoWeeksAgo = time() - (14 * 24 * 60 * 60);

            $scores = $DB->get_records_sql(
                "SELECT score, timecreated
                 FROM {at_student_performance}
                 WHERE userid = ?
                 AND timecreated > ?
                 ORDER BY timecreated ASC",
                [$userId, $twoWeeksAgo]
            );

            if (count($scores) < 2) {
                return ['score_trend' => 0, 'average' => 0];
            }

            $scoreArray = array_column($scores, 'score');
            $firstHalf = array_slice($scoreArray, 0, floor(count($scoreArray) / 2));
            $secondHalf = array_slice($scoreArray, floor(count($scoreArray) / 2));

            $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
            $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;

            return [
                'score_trend' => $secondAvg - $firstAvg,
                'average' => array_sum($scoreArray) / count($scoreArray),
                'recent_scores' => array_slice($scoreArray, -5)
            ];

        } catch (Exception $e) {
            return ['score_trend' => 0, 'average' => 0];
        }
    }

    /**
     * 이탈 이벤트 로드
     */
    private function loadDropoutEvents($userId) {
        global $DB;

        try {
            $events = $DB->get_records_sql(
                "SELECT id, event_type, timecreated
                 FROM {at_dropout_events}
                 WHERE userid = ?
                 AND timecreated > ?
                 ORDER BY timecreated DESC",
                [$userId, time() - (24 * 60 * 60)] // 최근 24시간
            );

            return array_values($events);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 오답 패턴 로드
     */
    private function loadErrorPatterns($userId) {
        global $DB;

        try {
            $patterns = $DB->get_records_sql(
                "SELECT error_type, COUNT(*) as count
                 FROM {at_error_logs}
                 WHERE userid = ?
                 AND timecreated > ?
                 GROUP BY error_type
                 HAVING COUNT(*) >= 2",
                [$userId, time() - (7 * 24 * 60 * 60)] // 최근 7일
            );

            $result = [];
            foreach ($patterns as $p) {
                $result[] = [
                    'type' => $p->error_type,
                    'count' => intval($p->count)
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 학습 패턴 로드
     */
    private function loadStudyPatterns($userId) {
        global $DB;

        try {
            $patterns = $DB->get_record_sql(
                "SELECT
                    AVG(pomodoro_completed / pomodoro_planned * 100) as pomodoro_completion,
                    AVG(focus_score) as avg_focus
                 FROM {at_study_sessions}
                 WHERE userid = ?
                 AND timecreated > ?",
                [$userId, time() - (7 * 24 * 60 * 60)]
            );

            return [
                'pomodoro_completion' => $patterns ? floatval($patterns->pomodoro_completion) : 100,
                'avg_focus' => $patterns ? floatval($patterns->avg_focus) : 70
            ];

        } catch (Exception $e) {
            return ['pomodoro_completion' => 100, 'avg_focus' => 70];
        }
    }

    /**
     * 시간 관리 데이터 로드
     */
    private function loadTimeManagement($userId) {
        global $DB;

        try {
            $data = $DB->get_record_sql(
                "SELECT
                    AVG(ABS(actual_time - planned_time) / planned_time * 100) as plan_vs_actual_diff
                 FROM {at_time_logs}
                 WHERE userid = ?
                 AND timecreated > ?
                 AND planned_time > 0",
                [$userId, time() - (7 * 24 * 60 * 60)]
            );

            return [
                'plan_vs_actual_diff' => $data ? floatval($data->plan_vs_actual_diff) : 0
            ];

        } catch (Exception $e) {
            return ['plan_vs_actual_diff' => 0];
        }
    }

    /**
     * 감정 로그 로드
     */
    private function loadEmotionLogs($userId) {
        global $DB;

        try {
            $logs = $DB->get_records_sql(
                "SELECT emotion, intensity, timecreated
                 FROM {at_emotion_logs}
                 WHERE userid = ?
                 AND timecreated > ?
                 ORDER BY timecreated DESC
                 LIMIT 10",
                [$userId, time() - (7 * 24 * 60 * 60)]
            );

            $result = [];
            foreach ($logs as $log) {
                $result[] = [
                    'emotion' => $log->emotion,
                    'intensity' => $log->intensity,
                    'time' => $log->timecreated
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 개념 테스트 점수 로드
     */
    private function loadConceptScores($userId) {
        global $DB;

        try {
            $scores = $DB->get_records_sql(
                "SELECT concept_name, score
                 FROM {at_concept_tests}
                 WHERE userid = ?
                 AND timecreated > ?
                 ORDER BY timecreated DESC",
                [$userId, time() - (30 * 24 * 60 * 60)]
            );

            $result = [];
            foreach ($scores as $s) {
                $result[$s->concept_name] = floatval($s->score);
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 교사 피드백 로드
     */
    private function loadTeacherFeedback($userId) {
        global $DB;

        try {
            $feedback = $DB->get_records_sql(
                "SELECT type, content, timecreated
                 FROM {at_teacher_feedback}
                 WHERE userid = ?
                 AND timecreated > ?
                 ORDER BY timecreated DESC
                 LIMIT 5",
                [$userId, time() - (14 * 24 * 60 * 60)]
            );

            $result = [];
            foreach ($feedback as $fb) {
                $result[] = [
                    'type' => $fb->type,
                    'content' => $fb->content,
                    'time' => $fb->timecreated
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 전략 데이터 로드
     */
    private function loadStrategyData($userId) {
        global $DB;

        try {
            $data = $DB->get_record_sql(
                "SELECT guidance_mode, behavior_alignment
                 FROM {at_strategy_settings}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$userId]
            );

            return [
                'mode_vs_behavior_match' => $data ? floatval($data->behavior_alignment) : 100
            ];

        } catch (Exception $e) {
            return ['mode_vs_behavior_match' => 100];
        }
    }

    /**
     * 회복 데이터 로드
     */
    private function loadRecoveryData($userId) {
        global $DB;

        try {
            $data = $DB->get_record_sql(
                "SELECT AVG(post_break_focus) as post_break_focus
                 FROM {at_recovery_logs}
                 WHERE userid = ?
                 AND timecreated > ?",
                [$userId, time() - (7 * 24 * 60 * 60)]
            );

            return [
                'post_break_focus' => $data && $data->post_break_focus ? floatval($data->post_break_focus) : 70
            ];

        } catch (Exception $e) {
            return ['post_break_focus' => 70];
        }
    }

    /**
     * 수학 취약점 로드
     */
    private function loadMathVulnerability($userId) {
        global $DB;

        try {
            $vulnerabilities = $DB->get_records_sql(
                "SELECT unit_name, vulnerability_score
                 FROM {at_math_vulnerability}
                 WHERE userid = ?
                 ORDER BY vulnerability_score DESC
                 LIMIT 5",
                [$userId]
            );

            $result = [];
            foreach ($vulnerabilities as $v) {
                $result[$v->unit_name] = floatval($v->vulnerability_score);
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 이전 페르소나 로드
     */
    private function loadPreviousPersonas($userId) {
        global $DB;

        try {
            $personas = $DB->get_records_sql(
                "SELECT persona_id, persona_name, trigger_scenario, timecreated
                 FROM {at_agent_persona_state}
                 WHERE userid = ?
                 AND nagent = 15
                 ORDER BY timecreated DESC
                 LIMIT 5",
                [$userId]
            );

            return array_values($personas);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 세션 히스토리 로드
     */
    private function loadSessionHistory($userId) {
        global $DB;

        try {
            $sessions = $DB->get_records_sql(
                "SELECT id, session_data, timecreated
                 FROM {augmented_teacher_sessions}
                 WHERE userid = ?
                 AND nagent = 15
                 ORDER BY timecreated DESC
                 LIMIT 3",
                [$userId]
            );

            $result = [];
            foreach ($sessions as $s) {
                $result[] = [
                    'id' => $s->id,
                    'data' => json_decode($s->session_data, true),
                    'time' => $s->timecreated
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 캐시 유효성 확인
     */
    private function isCacheValid($key) {
        if (!isset($this->cache[$key]) || !isset($this->cacheTimestamps[$key])) {
            return false;
        }
        return (time() - $this->cacheTimestamps[$key]) < self::CACHE_TTL;
    }

    /**
     * 캐시 저장
     */
    private function setCache($key, $data) {
        $this->cache[$key] = $data;
        $this->cacheTimestamps[$key] = time();
    }

    /**
     * 캐시 초기화
     */
    public function clearCache() {
        $this->cache = [];
        $this->cacheTimestamps = [];
    }

    /**
     * 특정 데이터 새로고침
     *
     * @param int $userId 사용자 ID
     * @param string $dataType 데이터 타입
     * @return array 새로고침된 데이터
     */
    public function refreshData($userId, $dataType) {
        $cacheKey = "student_$userId";
        unset($this->cache[$cacheKey]);
        unset($this->cacheTimestamps[$cacheKey]);

        return $this->loadStudentData($userId);
    }
}
