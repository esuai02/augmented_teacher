<?php
/**
 * Agent12DataContext.php
 *
 * Agent12 휴식 루틴 데이터 컨텍스트 클래스
 * DataContextInterface 구현
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent12RestRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/Agent12DataContext.php
 *
 * 관련 DB 테이블:
 * - mdl_at_agent12_rest_sessions: 휴식 세션 기록
 * - mdl_at_agent12_routine_history: 루틴 히스토리
 * - mdl_at_agent_persona_state: 공통 페르소나 상태
 */

// MOODLE_INTERNAL 체크
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// 인터페이스 로드
require_once(__DIR__ . '/../../engine_core/interfaces/DataContextInterface.php');

/**
 * Agent12DataContext
 *
 * 휴식 루틴 에이전트 데이터 접근 클래스
 */
class Agent12DataContext implements DataContextInterface
{
    /** @var object Moodle DB 객체 */
    protected $db;

    /** @var int 에이전트 번호 */
    protected $nagent = 12;

    /** @var string 휴식 세션 테이블명 */
    protected $tableRestSessions = 'mdl_at_agent12_rest_sessions';

    /** @var string 루틴 히스토리 테이블명 */
    protected $tableRoutineHistory = 'mdl_at_agent12_routine_history';

    /** @var string 공통 페르소나 상태 테이블명 */
    protected $tablePersonaState = 'mdl_at_agent_persona_state';

    /**
     * 생성자
     *
     * @param object $db Moodle DB 객체
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureTablesExist();
    }

    // =========================================================================
    // DataContextInterface 구현
    // =========================================================================

    /**
     * 사용자 컨텍스트 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param array $options 옵션
     * @return array 컨텍스트 데이터
     */
    public function getUserContext(int $userId, array $options = []): array
    {
        $context = [
            'user_id' => $userId,
            'nagent' => $this->nagent,
            'rest_stats' => $this->getRestStats($userId),
            'recent_sessions' => $this->getRecentRestSessions($userId, 10),
            'current_persona' => $this->getCurrentPersonaState($userId),
            'fatigue_trend' => $this->getFatigueTrend($userId)
        ];

        // 추가 옵션 처리
        if (isset($options['include_history']) && $options['include_history']) {
            $context['routine_history'] = $this->getRoutineHistory($userId, 30);
        }

        return $context;
    }

    /**
     * 컨텍스트 저장
     *
     * @param int $userId 사용자 ID
     * @param array $contextData 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function saveContext(int $userId, array $contextData): bool
    {
        try {
            // 페르소나 상태 저장
            if (isset($contextData['persona_code'])) {
                $this->savePersonaState($userId, $contextData);
            }

            return true;
        } catch (Exception $e) {
            error_log("[Agent12DataContext] saveContext 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 테이블 존재 확인 및 생성
     *
     * @return bool 성공 여부
     */
    public function ensureTablesExist(): bool
    {
        try {
            // mdl_at_agent12_rest_sessions 테이블
            $sql1 = "CREATE TABLE IF NOT EXISTS {$this->tableRestSessions} (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                session_start INT NOT NULL COMMENT '휴식 시작 시간',
                session_end INT DEFAULT NULL COMMENT '휴식 종료 시간',
                duration_minutes INT DEFAULT 0 COMMENT '휴식 시간(분)',
                rest_type VARCHAR(20) DEFAULT 'break' COMMENT '휴식 타입(break, stretch, walk, nap)',
                trigger_source VARCHAR(30) DEFAULT 'button' COMMENT '트리거 소스(button, timer, system)',
                study_duration_before INT DEFAULT 0 COMMENT '휴식 전 학습 시간(분)',
                fatigue_level_before DECIMAL(3,2) DEFAULT 0.00 COMMENT '휴식 전 피로도',
                fatigue_level_after DECIMAL(3,2) DEFAULT NULL COMMENT '휴식 후 피로도',
                activity_type VARCHAR(50) DEFAULT NULL COMMENT '휴식 중 활동',
                notes TEXT DEFAULT NULL COMMENT '메모',
                timecreated INT NOT NULL,
                timemodified INT NOT NULL,
                INDEX idx_user_time (user_id, session_start),
                INDEX idx_user_type (user_id, rest_type),
                INDEX idx_timecreated (timecreated)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->db->execute($sql1);

            // mdl_at_agent12_routine_history 테이블
            $sql2 = "CREATE TABLE IF NOT EXISTS {$this->tableRoutineHistory} (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                date_key VARCHAR(10) NOT NULL COMMENT '날짜(YYYY-MM-DD)',
                total_study_minutes INT DEFAULT 0 COMMENT '총 학습 시간(분)',
                total_rest_minutes INT DEFAULT 0 COMMENT '총 휴식 시간(분)',
                rest_count INT DEFAULT 0 COMMENT '휴식 횟수',
                avg_rest_interval INT DEFAULT 0 COMMENT '평균 휴식 간격(분)',
                avg_rest_duration INT DEFAULT 0 COMMENT '평균 휴식 시간(분)',
                fatigue_index DECIMAL(5,2) DEFAULT 0.00 COMMENT '피로도 지수(0-100)',
                persona_code VARCHAR(30) DEFAULT NULL COMMENT '해당 일자 페르소나',
                rest_quality_score DECIMAL(3,2) DEFAULT NULL COMMENT '휴식 품질 점수(0-1)',
                recommendations TEXT DEFAULT NULL COMMENT '추천 사항',
                timecreated INT NOT NULL,
                timemodified INT NOT NULL,
                UNIQUE KEY uk_user_date (user_id, date_key),
                INDEX idx_user_fatigue (user_id, fatigue_index),
                INDEX idx_date (date_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->db->execute($sql2);

            return true;
        } catch (Exception $e) {
            error_log("[Agent12DataContext] ensureTablesExist 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    // =========================================================================
    // 휴식 세션 관리
    // =========================================================================

    /**
     * 휴식 세션 시작
     *
     * @param int $userId 사용자 ID
     * @param array $data 세션 데이터
     * @return array 결과
     */
    public function startRestSession(int $userId, array $data = []): array
    {
        $now = time();

        try {
            // 진행 중인 세션이 있는지 확인
            $activeSession = $this->getActiveRestSession($userId);
            if ($activeSession) {
                return [
                    'success' => false,
                    'error' => '이미 진행 중인 휴식 세션이 있습니다. File: ' . __FILE__ . ' Line: ' . __LINE__,
                    'session_id' => $activeSession->id
                ];
            }

            $record = new stdClass();
            $record->user_id = $userId;
            $record->session_start = $now;
            $record->session_end = null;
            $record->duration_minutes = 0;
            $record->rest_type = isset($data['rest_type']) ? $data['rest_type'] : 'break';
            $record->trigger_source = isset($data['trigger_source']) ? $data['trigger_source'] : 'button';
            $record->study_duration_before = isset($data['study_duration']) ? (int)$data['study_duration'] : 0;
            $record->fatigue_level_before = isset($data['fatigue_level']) ? (float)$data['fatigue_level'] : 0.0;
            $record->activity_type = isset($data['activity_type']) ? $data['activity_type'] : null;
            $record->notes = isset($data['notes']) ? $data['notes'] : null;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $id = $this->db->insert_record($this->tableRestSessions, $record);

            return [
                'success' => true,
                'session_id' => $id,
                'message' => '휴식 세션이 시작되었습니다.',
                'started_at' => $now
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage() . ' File: ' . __FILE__ . ' Line: ' . __LINE__
            ];
        }
    }

    /**
     * 휴식 세션 종료
     *
     * @param int $userId 사용자 ID
     * @param int|null $sessionId 세션 ID (없으면 활성 세션 종료)
     * @param array $data 추가 데이터
     * @return array 결과
     */
    public function endRestSession(int $userId, ?int $sessionId = null, array $data = []): array
    {
        $now = time();

        try {
            // 세션 조회
            if ($sessionId) {
                $session = $this->db->get_record($this->tableRestSessions, [
                    'id' => $sessionId,
                    'user_id' => $userId
                ]);
            } else {
                $session = $this->getActiveRestSession($userId);
            }

            if (!$session) {
                return [
                    'success' => false,
                    'error' => '활성 휴식 세션을 찾을 수 없습니다. File: ' . __FILE__ . ' Line: ' . __LINE__
                ];
            }

            if ($session->session_end !== null) {
                return [
                    'success' => false,
                    'error' => '이미 종료된 세션입니다. File: ' . __FILE__ . ' Line: ' . __LINE__
                ];
            }

            // 세션 종료 처리
            $duration = (int)(($now - $session->session_start) / 60);

            $update = new stdClass();
            $update->id = $session->id;
            $update->session_end = $now;
            $update->duration_minutes = $duration;
            $update->fatigue_level_after = isset($data['fatigue_level']) ? (float)$data['fatigue_level'] : null;
            $update->notes = isset($data['notes']) ? $data['notes'] : $session->notes;
            $update->timemodified = $now;

            $this->db->update_record($this->tableRestSessions, $update);

            // 일일 루틴 히스토리 업데이트
            $this->updateDailyRoutine($userId);

            return [
                'success' => true,
                'session_id' => $session->id,
                'duration_minutes' => $duration,
                'message' => $duration . '분간 휴식을 완료했습니다!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage() . ' File: ' . __FILE__ . ' Line: ' . __LINE__
            ];
        }
    }

    /**
     * 활성 휴식 세션 조회
     *
     * @param int $userId 사용자 ID
     * @return object|null 활성 세션
     */
    public function getActiveRestSession(int $userId)
    {
        $sql = "SELECT * FROM {$this->tableRestSessions}
                WHERE user_id = ? AND session_end IS NULL
                ORDER BY session_start DESC
                LIMIT 1";

        return $this->db->get_record_sql($sql, [$userId]);
    }

    /**
     * 최근 휴식 세션 목록 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 세션 목록
     */
    public function getRecentRestSessions(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->tableRestSessions}
                WHERE user_id = ?
                ORDER BY session_start DESC
                LIMIT ?";

        $records = $this->db->get_records_sql($sql, [$userId, $limit]);

        return $records ? array_values($records) : [];
    }

    // =========================================================================
    // 통계 및 분석
    // =========================================================================

    /**
     * 휴식 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간(일)
     * @return array 통계
     */
    public function getRestStats(int $userId, int $days = 7): array
    {
        $startTime = strtotime("-{$days} days");

        // 완료된 휴식 세션 통계
        $sql = "SELECT
                    COUNT(*) as total_sessions,
                    COALESCE(SUM(duration_minutes), 0) as total_rest_minutes,
                    COALESCE(AVG(duration_minutes), 0) as avg_rest_duration,
                    COALESCE(AVG(study_duration_before), 0) as avg_study_before_rest,
                    COALESCE(AVG(fatigue_level_before), 0) as avg_fatigue_before,
                    COALESCE(AVG(fatigue_level_after), 0) as avg_fatigue_after
                FROM {$this->tableRestSessions}
                WHERE user_id = ?
                  AND session_end IS NOT NULL
                  AND timecreated >= ?";

        $stats = $this->db->get_record_sql($sql, [$userId, $startTime]);

        // 평균 휴식 간격 계산
        $avgInterval = $this->calculateAverageRestInterval($userId, $days);

        // 휴식 타입별 분포
        $typeDistribution = $this->getRestTypeDistribution($userId, $days);

        return [
            'period_days' => $days,
            'total_sessions' => (int)($stats->total_sessions ?? 0),
            'total_rest_minutes' => (int)($stats->total_rest_minutes ?? 0),
            'avg_rest_duration' => round((float)($stats->avg_rest_duration ?? 0), 1),
            'avg_study_before_rest' => round((float)($stats->avg_study_before_rest ?? 0), 1),
            'avg_rest_interval' => round($avgInterval, 1),
            'avg_fatigue_before' => round((float)($stats->avg_fatigue_before ?? 0), 2),
            'avg_fatigue_after' => round((float)($stats->avg_fatigue_after ?? 0), 2),
            'fatigue_reduction' => round((float)($stats->avg_fatigue_before ?? 0) - (float)($stats->avg_fatigue_after ?? 0), 2),
            'type_distribution' => $typeDistribution
        ];
    }

    /**
     * 평균 휴식 간격 계산
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간
     * @return float 평균 간격(분)
     */
    public function calculateAverageRestInterval(int $userId, int $days = 7): float
    {
        $startTime = strtotime("-{$days} days");

        $sql = "SELECT session_start, session_end
                FROM {$this->tableRestSessions}
                WHERE user_id = ?
                  AND session_end IS NOT NULL
                  AND timecreated >= ?
                ORDER BY session_start ASC";

        $sessions = $this->db->get_records_sql($sql, [$userId, $startTime]);

        if (!$sessions || count($sessions) < 2) {
            return 0.0;
        }

        $sessions = array_values($sessions);
        $intervals = [];

        for ($i = 1; $i < count($sessions); $i++) {
            // 이전 세션 종료 ~ 현재 세션 시작까지의 간격
            $interval = ($sessions[$i]->session_start - $sessions[$i - 1]->session_end) / 60;
            if ($interval > 0 && $interval < 480) { // 8시간 이내만 유효
                $intervals[] = $interval;
            }
        }

        if (empty($intervals)) {
            return 0.0;
        }

        return array_sum($intervals) / count($intervals);
    }

    /**
     * 휴식 타입별 분포 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간
     * @return array 타입별 분포
     */
    public function getRestTypeDistribution(int $userId, int $days = 7): array
    {
        $startTime = strtotime("-{$days} days");

        $sql = "SELECT rest_type, COUNT(*) as count, SUM(duration_minutes) as total_minutes
                FROM {$this->tableRestSessions}
                WHERE user_id = ?
                  AND session_end IS NOT NULL
                  AND timecreated >= ?
                GROUP BY rest_type";

        $records = $this->db->get_records_sql($sql, [$userId, $startTime]);

        $distribution = [];
        foreach ($records as $record) {
            $distribution[$record->rest_type] = [
                'count' => (int)$record->count,
                'total_minutes' => (int)$record->total_minutes
            ];
        }

        return $distribution;
    }

    /**
     * 피로도 추세 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간
     * @return array 피로도 추세
     */
    public function getFatigueTrend(int $userId, int $days = 7): array
    {
        $sql = "SELECT date_key, fatigue_index, rest_count, avg_rest_interval
                FROM {$this->tableRoutineHistory}
                WHERE user_id = ?
                ORDER BY date_key DESC
                LIMIT ?";

        $records = $this->db->get_records_sql($sql, [$userId, $days]);

        if (!$records) {
            return [
                'trend' => 'unknown',
                'data' => [],
                'avg_fatigue' => 0
            ];
        }

        $data = [];
        $fatigueSum = 0;

        foreach ($records as $record) {
            $data[] = [
                'date' => $record->date_key,
                'fatigue_index' => (float)$record->fatigue_index,
                'rest_count' => (int)$record->rest_count,
                'avg_rest_interval' => (int)$record->avg_rest_interval
            ];
            $fatigueSum += (float)$record->fatigue_index;
        }

        $avgFatigue = $fatigueSum / count($records);

        // 추세 판단 (최근 3일 vs 이전)
        $trend = 'stable';
        if (count($data) >= 4) {
            $recent = array_slice($data, 0, 3);
            $older = array_slice($data, 3);

            $recentAvg = array_sum(array_column($recent, 'fatigue_index')) / count($recent);
            $olderAvg = array_sum(array_column($older, 'fatigue_index')) / count($older);

            if ($recentAvg > $olderAvg + 10) {
                $trend = 'increasing';
            } elseif ($recentAvg < $olderAvg - 10) {
                $trend = 'decreasing';
            }
        }

        return [
            'trend' => $trend,
            'data' => $data,
            'avg_fatigue' => round($avgFatigue, 1)
        ];
    }

    // =========================================================================
    // 일일 루틴 관리
    // =========================================================================

    /**
     * 일일 루틴 히스토리 업데이트
     *
     * @param int $userId 사용자 ID
     * @param string|null $dateKey 날짜 키
     * @return bool 성공 여부
     */
    public function updateDailyRoutine(int $userId, ?string $dateKey = null): bool
    {
        $now = time();
        $dateKey = $dateKey ?: date('Y-m-d');

        try {
            // 해당 일자 통계 계산
            $dayStart = strtotime($dateKey . ' 00:00:00');
            $dayEnd = strtotime($dateKey . ' 23:59:59');

            $sql = "SELECT
                        COUNT(*) as rest_count,
                        COALESCE(SUM(duration_minutes), 0) as total_rest,
                        COALESCE(AVG(duration_minutes), 0) as avg_duration
                    FROM {$this->tableRestSessions}
                    WHERE user_id = ?
                      AND session_start >= ?
                      AND session_start <= ?
                      AND session_end IS NOT NULL";

            $stats = $this->db->get_record_sql($sql, [$userId, $dayStart, $dayEnd]);

            // 평균 휴식 간격 계산 (당일)
            $avgInterval = $this->calculateAverageRestInterval($userId, 1);

            // 피로도 지수 계산
            $fatigueIndex = $this->calculateDailyFatigueIndex(
                (int)$stats->rest_count,
                (float)$avgInterval
            );

            // 기존 레코드 확인
            $existing = $this->db->get_record($this->tableRoutineHistory, [
                'user_id' => $userId,
                'date_key' => $dateKey
            ]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->date_key = $dateKey;
            $record->total_rest_minutes = (int)$stats->total_rest;
            $record->rest_count = (int)$stats->rest_count;
            $record->avg_rest_interval = (int)$avgInterval;
            $record->avg_rest_duration = (int)$stats->avg_duration;
            $record->fatigue_index = $fatigueIndex;
            $record->timemodified = $now;

            if ($existing) {
                $record->id = $existing->id;
                $this->db->update_record($this->tableRoutineHistory, $record);
            } else {
                $record->timecreated = $now;
                $this->db->insert_record($this->tableRoutineHistory, $record);
            }

            return true;
        } catch (Exception $e) {
            error_log("[Agent12DataContext] updateDailyRoutine 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 일일 피로도 지수 계산
     *
     * @param int $restCount 휴식 횟수
     * @param float $avgInterval 평균 휴식 간격
     * @return float 피로도 지수 (0-100)
     */
    protected function calculateDailyFatigueIndex(int $restCount, float $avgInterval): float
    {
        // 기본 피로도
        $fatigue = 50.0;

        // 휴식 횟수에 따른 조정
        if ($restCount === 0) {
            $fatigue = 85.0; // 휴식 없음 = 높은 피로도
        } elseif ($restCount >= 5) {
            $fatigue -= 15; // 충분한 휴식
        } elseif ($restCount >= 3) {
            $fatigue -= 5;
        }

        // 평균 간격에 따른 조정
        if ($avgInterval > 0) {
            if ($avgInterval <= 45) {
                $fatigue -= 20; // 규칙적인 휴식
            } elseif ($avgInterval <= 60) {
                $fatigue -= 10;
            } elseif ($avgInterval <= 90) {
                // 중립
            } elseif ($avgInterval <= 120) {
                $fatigue += 10;
            } else {
                $fatigue += 20; // 불규칙한 휴식
            }
        }

        return max(0, min(100, $fatigue));
    }

    /**
     * 루틴 히스토리 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간
     * @return array 히스토리
     */
    public function getRoutineHistory(int $userId, int $days = 30): array
    {
        $sql = "SELECT * FROM {$this->tableRoutineHistory}
                WHERE user_id = ?
                ORDER BY date_key DESC
                LIMIT ?";

        $records = $this->db->get_records_sql($sql, [$userId, $days]);

        return $records ? array_values($records) : [];
    }

    // =========================================================================
    // 페르소나 상태 관리
    // =========================================================================

    /**
     * 현재 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 페르소나 상태
     */
    public function getCurrentPersonaState(int $userId)
    {
        $record = $this->db->get_record($this->tablePersonaState, [
            'user_id' => $userId,
            'nagent' => $this->nagent
        ]);

        if (!$record) {
            return null;
        }

        return [
            'persona_code' => $record->persona_code,
            'confidence' => (float)$record->confidence,
            'context_data' => json_decode($record->context_data, true),
            'timemodified' => $record->timemodified
        ];
    }

    /**
     * 페르소나 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param array $data 데이터
     * @return bool 성공 여부
     */
    public function savePersonaState(int $userId, array $data): bool
    {
        $now = time();

        try {
            $existing = $this->db->get_record($this->tablePersonaState, [
                'user_id' => $userId,
                'nagent' => $this->nagent
            ]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->nagent = $this->nagent;
            $record->persona_code = isset($data['persona_code']) ? $data['persona_code'] : 'regular_rest';
            $record->confidence = isset($data['confidence']) ? (float)$data['confidence'] : 0.8;
            $record->context_data = isset($data['context_data']) ? json_encode($data['context_data']) : '{}';
            $record->timemodified = $now;

            if ($existing) {
                $record->id = $existing->id;
                $this->db->update_record($this->tablePersonaState, $record);
            } else {
                $record->timecreated = $now;
                $this->db->insert_record($this->tablePersonaState, $record);
            }

            return true;
        } catch (Exception $e) {
            error_log("[Agent12DataContext] savePersonaState 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    // =========================================================================
    // 추천 및 분석
    // =========================================================================

    /**
     * 휴식 필요성 판단
     *
     * @param int $userId 사용자 ID
     * @param int $currentStudyMinutes 현재 연속 학습 시간
     * @return array 휴식 필요 여부 및 추천
     */
    public function analyzeRestNeed(int $userId, int $currentStudyMinutes): array
    {
        $stats = $this->getRestStats($userId, 7);
        $avgInterval = $stats['avg_rest_interval'] ?: 60;

        // 개인화된 휴식 권장 간격 (과거 패턴 기반)
        $recommendedInterval = min(90, max(30, $avgInterval * 0.9)); // 10% 단축 목표

        $needsRest = $currentStudyMinutes >= $recommendedInterval;
        $urgency = 'low';

        if ($currentStudyMinutes >= $recommendedInterval * 1.5) {
            $urgency = 'high';
        } elseif ($currentStudyMinutes >= $recommendedInterval) {
            $urgency = 'medium';
        }

        $recommendations = [];

        if ($needsRest) {
            // 과거 선호 휴식 타입 기반 추천
            $typeDistribution = $stats['type_distribution'];
            $preferredType = 'break';
            $maxCount = 0;

            foreach ($typeDistribution as $type => $data) {
                if ($data['count'] > $maxCount) {
                    $maxCount = $data['count'];
                    $preferredType = $type;
                }
            }

            $recommendations[] = [
                'type' => 'rest_suggestion',
                'rest_type' => $preferredType,
                'duration' => min(15, (int)$stats['avg_rest_duration'] ?: 10),
                'message' => $this->getRestMessage($urgency, $currentStudyMinutes)
            ];
        }

        return [
            'needs_rest' => $needsRest,
            'urgency' => $urgency,
            'current_study_minutes' => $currentStudyMinutes,
            'recommended_interval' => (int)$recommendedInterval,
            'time_until_recommended' => max(0, (int)$recommendedInterval - $currentStudyMinutes),
            'recommendations' => $recommendations
        ];
    }

    /**
     * 휴식 메시지 생성
     *
     * @param string $urgency 긴급도
     * @param int $studyMinutes 학습 시간
     * @return string 메시지
     */
    protected function getRestMessage(string $urgency, int $studyMinutes): string
    {
        $messages = [
            'high' => [
                "🚨 {$studyMinutes}분 동안 쉬지 않고 공부했어요! 지금 바로 휴식이 필요해요.",
                "⚠️ 집중력이 떨어지고 있을 거예요. 잠깐 스트레칭 어때요?",
                "🔴 피로가 누적되면 오히려 효율이 떨어져요. 지금 쉬어요!"
            ],
            'medium' => [
                "⏰ 슬슬 휴식 시간이에요! 5-10분 쉬고 올까요?",
                "☕ 물 한 잔 마시면서 잠깐 쉬어볼까요?",
                "🌿 {$studyMinutes}분 열심히 했어요! 휴식으로 리프레시해요."
            ],
            'low' => [
                "💡 휴식을 취하면 더 오래 집중할 수 있어요.",
                "📚 꾸준한 휴식이 장기 학습에 도움이 돼요."
            ]
        ];

        $pool = isset($messages[$urgency]) ? $messages[$urgency] : $messages['low'];
        return $pool[array_rand($pool)];
    }

    // =========================================================================
    // 헬스 체크
    // =========================================================================

    /**
     * 데이터베이스 연결 상태 확인
     *
     * @return array 상태 정보
     */
    public function healthCheck(): array
    {
        $checks = [
            'database' => false,
            'rest_sessions_table' => false,
            'routine_history_table' => false,
            'persona_state_table' => false
        ];

        try {
            // DB 연결 체크
            $this->db->get_record_sql("SELECT 1");
            $checks['database'] = true;

            // 테이블 존재 확인
            $tables = [
                'rest_sessions_table' => $this->tableRestSessions,
                'routine_history_table' => $this->tableRoutineHistory,
                'persona_state_table' => $this->tablePersonaState
            ];

            foreach ($tables as $key => $table) {
                try {
                    $this->db->get_record_sql("SELECT 1 FROM {$table} LIMIT 1");
                    $checks[$key] = true;
                } catch (Exception $e) {
                    $checks[$key] = false;
                }
            }
        } catch (Exception $e) {
            error_log("[Agent12DataContext] healthCheck 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
        }

        $allHealthy = !in_array(false, $checks, true);

        return [
            'healthy' => $allHealthy,
            'checks' => $checks,
            'timestamp' => time()
        ];
    }
}

/*
 * =========================================================================
 * DB 테이블 정보
 * =========================================================================
 *
 * mdl_at_agent12_rest_sessions
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - session_start: INT NOT NULL (휴식 시작 시간)
 * - session_end: INT (휴식 종료 시간)
 * - duration_minutes: INT (휴식 시간(분))
 * - rest_type: VARCHAR(20) (break, stretch, walk, nap)
 * - trigger_source: VARCHAR(30) (button, timer, system)
 * - study_duration_before: INT (휴식 전 학습 시간)
 * - fatigue_level_before: DECIMAL(3,2)
 * - fatigue_level_after: DECIMAL(3,2)
 * - activity_type: VARCHAR(50)
 * - notes: TEXT
 * - timecreated: INT
 * - timemodified: INT
 *
 * mdl_at_agent12_routine_history
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - date_key: VARCHAR(10) (YYYY-MM-DD)
 * - total_study_minutes: INT
 * - total_rest_minutes: INT
 * - rest_count: INT
 * - avg_rest_interval: INT
 * - avg_rest_duration: INT
 * - fatigue_index: DECIMAL(5,2)
 * - persona_code: VARCHAR(30)
 * - rest_quality_score: DECIMAL(3,2)
 * - recommendations: TEXT
 * - timecreated: INT
 * - timemodified: INT
 *
 * =========================================================================
 */
