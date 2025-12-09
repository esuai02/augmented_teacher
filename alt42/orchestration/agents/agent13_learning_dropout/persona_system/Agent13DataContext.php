<?php
/**
 * Agent13DataContext.php
 *
 * Agent13 학습 이탈 데이터 컨텍스트
 * DataContextInterface 구현
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent13LearningDropout
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/Agent13DataContext.php
 */

// MOODLE_INTERNAL 체크
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// DataContextInterface 로드
require_once(__DIR__ . '/../../engine_core/interfaces/DataContextInterface.php');

/**
 * Agent13DataContext
 *
 * 학습 이탈 관련 데이터 접근 레이어
 * 24시간 롤링 윈도우 기반 데이터 조회
 */
class Agent13DataContext implements DataContextInterface
{
    /**
     * Moodle DB 인스턴스
     *
     * @var object
     */
    protected $db;

    /**
     * 24시간 롤링 윈도우 (초)
     */
    const ROLLING_WINDOW = 86400;

    /**
     * 캐시 TTL (초)
     */
    const CACHE_TTL = 300;

    /**
     * 캐시 저장소
     *
     * @var array
     */
    protected $cache = [];

    /**
     * 생성자
     *
     * @param object $db Moodle DB 인스턴스
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * 이탈 지표 조회
     *
     * @param int $userId 사용자 ID
     * @return array 이탈 지표 데이터
     */
    public function getDropoutIndicators(int $userId): array
    {
        $cacheKey = "dropout_indicators_{$userId}";
        if ($this->isCacheValid($cacheKey)) {
            return $this->cache[$cacheKey]['data'];
        }

        $now = time();
        $windowStart = $now - self::ROLLING_WINDOW;

        // 기본 지표 초기화
        $indicators = [
            'ninactive' => 0,
            'nlazy' => 0,
            'nlazy_blocks' => 0,
            'eye_count' => 0,
            'tlaststroke' => null,
            'tlaststroke_min' => null,
            'npomodoro' => 0,
            'kpomodoro' => 0,
            'pmresult' => null,
            'activetime' => 0,
            'consecutive_high_days' => 0,
            'last_activity' => null
        ];

        // 1. mdl_abessi_today에서 기본 지표 조회
        $todayData = $this->getTodayIndicators($userId, $windowStart);
        if ($todayData) {
            $indicators['ninactive'] = (int)$todayData->ninactive;
            $indicators['nlazy'] = (int)$todayData->nlazy;
            $indicators['nlazy_blocks'] = (int)round($todayData->nlazy / 20);
            $indicators['activetime'] = (int)$todayData->activetime;
        }

        // 2. mdl_abessi_messages에서 eye_count 및 tlaststroke 조회
        $messageData = $this->getMessageIndicators($userId, $windowStart, $now);
        if ($messageData) {
            $indicators['eye_count'] = $messageData['eye_count'];
            $indicators['tlaststroke'] = $messageData['tlaststroke'];
            $indicators['tlaststroke_min'] = $messageData['tlaststroke_min'];
            $indicators['last_activity'] = $messageData['last_activity'];
        }

        // 3. mdl_abessi_indicators에서 포모도로 지표 조회
        $pomodoroData = $this->getPomodoroIndicators($userId, $windowStart);
        if ($pomodoroData) {
            $indicators['npomodoro'] = (int)$pomodoroData->npomodoro;
            $indicators['kpomodoro'] = (int)$pomodoroData->kpomodoro;
            $indicators['pmresult'] = $pomodoroData->pmresult;
        }

        // 4. 연속 고위험 일수 계산
        $indicators['consecutive_high_days'] = $this->getConsecutiveHighRiskDays($userId);

        // 캐시 저장
        $this->setCache($cacheKey, $indicators);

        return $indicators;
    }

    /**
     * 오늘 목표/검사 지표 조회
     *
     * @param int $userId 사용자 ID
     * @param int $windowStart 윈도우 시작 시간
     * @return object|null
     */
    protected function getTodayIndicators(int $userId, int $windowStart)
    {
        try {
            $sql = "SELECT ninactive, nlazy, activetime, checktime, status
                    FROM {abessi_today}
                    WHERE userid = :userid
                    AND timecreated >= :windowstart
                    ORDER BY timecreated DESC
                    LIMIT 1";

            return $this->db->get_record_sql($sql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);
        } catch (Exception $e) {
            error_log("Agent13DataContext::getTodayIndicators Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
            return null;
        }
    }

    /**
     * 메시지 지표 조회 (eye_count, tlaststroke)
     *
     * @param int $userId 사용자 ID
     * @param int $windowStart 윈도우 시작 시간
     * @param int $now 현재 시간
     * @return array
     */
    protected function getMessageIndicators(int $userId, int $windowStart, int $now): array
    {
        $result = [
            'eye_count' => 0,
            'tlaststroke' => null,
            'tlaststroke_min' => null,
            'last_activity' => null
        ];

        try {
            // eye_count: timespent >= 5분인 이벤트 수
            $eyeSql = "SELECT COUNT(*) as eye_count
                       FROM {abessi_messages}
                       WHERE userid = :userid
                       AND timemodified >= :windowstart
                       AND ((:now - timemodified) / 60) >= 5";

            $eyeResult = $this->db->get_record_sql($eyeSql, [
                'userid' => $userId,
                'windowstart' => $windowStart,
                'now' => $now
            ]);

            if ($eyeResult) {
                $result['eye_count'] = (int)$eyeResult->eye_count;
            }

            // tlaststroke: 최근 필기 시점
            $strokeSql = "SELECT tlaststroke, timemodified
                          FROM {abessi_messages}
                          WHERE userid = :userid
                          AND tlaststroke > 0
                          ORDER BY tlaststroke DESC
                          LIMIT 1";

            $strokeResult = $this->db->get_record_sql($strokeSql, [
                'userid' => $userId
            ]);

            if ($strokeResult && $strokeResult->tlaststroke > 0) {
                $result['tlaststroke'] = (int)$strokeResult->tlaststroke;
                $result['tlaststroke_min'] = ($now - $strokeResult->tlaststroke) / 60;
                $result['last_activity'] = max($strokeResult->tlaststroke, $strokeResult->timemodified);
            }

        } catch (Exception $e) {
            error_log("Agent13DataContext::getMessageIndicators Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
        }

        return $result;
    }

    /**
     * 포모도로 지표 조회
     *
     * @param int $userId 사용자 ID
     * @param int $windowStart 윈도우 시작 시간
     * @return object|null
     */
    protected function getPomodoroIndicators(int $userId, int $windowStart)
    {
        try {
            $sql = "SELECT npomodoro, kpomodoro, pmresult
                    FROM {abessi_indicators}
                    WHERE userid = :userid
                    AND timecreated >= :windowstart
                    ORDER BY timecreated DESC
                    LIMIT 1";

            return $this->db->get_record_sql($sql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);
        } catch (Exception $e) {
            error_log("Agent13DataContext::getPomodoroIndicators Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
            return null;
        }
    }

    /**
     * 연속 고위험 일수 계산
     *
     * @param int $userId 사용자 ID
     * @return int
     */
    protected function getConsecutiveHighRiskDays(int $userId): int
    {
        try {
            // 최근 7일간의 위험 기록 조회
            $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key, risk_tier
                    FROM {at_agent13_dropout_risk}
                    WHERE user_id = :userid
                    AND timecreated >= :weekago
                    ORDER BY timecreated DESC";

            $records = $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'weekago' => time() - (7 * 86400)
            ]);

            if (empty($records)) {
                return 0;
            }

            // 연속 High/Critical 일수 계산
            $consecutiveDays = 0;
            $prevDate = null;

            foreach ($records as $record) {
                if ($record->risk_tier === 'High' || $record->risk_tier === 'Critical') {
                    if ($prevDate === null || $this->isConsecutiveDay($prevDate, $record->date_key)) {
                        $consecutiveDays++;
                        $prevDate = $record->date_key;
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }

            return $consecutiveDays;

        } catch (Exception $e) {
            // 테이블이 없을 수 있음 - 무시
            return 0;
        }
    }

    /**
     * 연속 날짜 체크
     *
     * @param string $date1 날짜1
     * @param string $date2 날짜2
     * @return bool
     */
    protected function isConsecutiveDay(string $date1, string $date2): bool
    {
        $d1 = strtotime($date1);
        $d2 = strtotime($date2);
        return abs($d1 - $d2) <= 86400;
    }

    /**
     * 이탈 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 기간 (일)
     * @return array
     */
    public function getDropoutStats(int $userId, int $days = 7): array
    {
        $windowStart = time() - ($days * 86400);

        $stats = [
            'period_days' => $days,
            'total_inactive_events' => 0,
            'avg_ninactive_per_day' => 0,
            'total_pomodoros' => 0,
            'completed_pomodoros' => 0,
            'pomodoro_completion_rate' => 0,
            'avg_active_time_min' => 0,
            'risk_distribution' => [
                'Low' => 0,
                'Medium' => 0,
                'High' => 0,
                'Critical' => 0
            ],
            'daily_breakdown' => []
        ];

        try {
            // 일별 통계 집계
            $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                           SUM(ninactive) as total_ninactive,
                           SUM(activetime) as total_activetime,
                           COUNT(*) as record_count
                    FROM {abessi_today}
                    WHERE userid = :userid
                    AND timecreated >= :windowstart
                    GROUP BY DATE(FROM_UNIXTIME(timecreated))
                    ORDER BY date_key DESC";

            $dailyData = $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);

            $totalInactive = 0;
            $totalActiveTime = 0;
            $dayCount = 0;

            foreach ($dailyData as $day) {
                $totalInactive += (int)$day->total_ninactive;
                $totalActiveTime += (int)$day->total_activetime;
                $dayCount++;

                $stats['daily_breakdown'][] = [
                    'date' => $day->date_key,
                    'ninactive' => (int)$day->total_ninactive,
                    'active_time_min' => round($day->total_activetime / 60, 1)
                ];
            }

            $stats['total_inactive_events'] = $totalInactive;
            $stats['avg_ninactive_per_day'] = $dayCount > 0 ? round($totalInactive / $dayCount, 2) : 0;
            $stats['avg_active_time_min'] = $dayCount > 0 ? round(($totalActiveTime / $dayCount) / 60, 1) : 0;

            // 포모도로 통계
            $pomodoroSql = "SELECT SUM(npomodoro) as total_pomo, SUM(kpomodoro) as completed_pomo
                           FROM {abessi_indicators}
                           WHERE userid = :userid
                           AND timecreated >= :windowstart";

            $pomodoroStats = $this->db->get_record_sql($pomodoroSql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);

            if ($pomodoroStats) {
                $stats['total_pomodoros'] = (int)$pomodoroStats->total_pomo;
                $stats['completed_pomodoros'] = (int)$pomodoroStats->completed_pomo;
                $stats['pomodoro_completion_rate'] = $stats['total_pomodoros'] > 0
                    ? round(($stats['completed_pomodoros'] / $stats['total_pomodoros']) * 100, 1)
                    : 0;
            }

            // 위험 등급 분포
            $riskSql = "SELECT risk_tier, COUNT(*) as cnt
                        FROM {at_agent13_dropout_risk}
                        WHERE user_id = :userid
                        AND timecreated >= :windowstart
                        GROUP BY risk_tier";

            $riskData = $this->db->get_records_sql($riskSql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);

            foreach ($riskData as $risk) {
                if (isset($stats['risk_distribution'][$risk->risk_tier])) {
                    $stats['risk_distribution'][$risk->risk_tier] = (int)$risk->cnt;
                }
            }

        } catch (Exception $e) {
            error_log("Agent13DataContext::getDropoutStats Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
        }

        return $stats;
    }

    /**
     * 위험 추세 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 기간 (일)
     * @return array
     */
    public function getRiskTrend(int $userId, int $days = 7): array
    {
        $windowStart = time() - ($days * 86400);
        $trend = [
            'direction' => 'stable',
            'change_rate' => 0,
            'data_points' => []
        ];

        try {
            $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                           AVG(risk_score) as avg_score,
                           risk_tier
                    FROM {at_agent13_dropout_risk}
                    WHERE user_id = :userid
                    AND timecreated >= :windowstart
                    GROUP BY DATE(FROM_UNIXTIME(timecreated))
                    ORDER BY date_key ASC";

            $records = $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'windowstart' => $windowStart
            ]);

            $scores = [];
            foreach ($records as $record) {
                $scores[] = (float)$record->avg_score;
                $trend['data_points'][] = [
                    'date' => $record->date_key,
                    'score' => round($record->avg_score, 2),
                    'tier' => $record->risk_tier
                ];
            }

            // 추세 계산
            if (count($scores) >= 2) {
                $firstHalf = array_slice($scores, 0, (int)(count($scores) / 2));
                $secondHalf = array_slice($scores, (int)(count($scores) / 2));

                $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
                $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;

                $change = $secondAvg - $firstAvg;
                $trend['change_rate'] = round($change, 2);

                if ($change > 5) {
                    $trend['direction'] = 'increasing';
                } elseif ($change < -5) {
                    $trend['direction'] = 'decreasing';
                } else {
                    $trend['direction'] = 'stable';
                }
            }

        } catch (Exception $e) {
            // 테이블이 없을 수 있음
        }

        return $trend;
    }

    /**
     * 개입 기록 저장
     *
     * @param int $userId 사용자 ID
     * @param array $data 개입 데이터
     * @return int|bool 삽입된 ID 또는 false
     */
    public function logIntervention(int $userId, array $data)
    {
        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->persona_code = $data['persona_code'];
            $record->risk_tier = $data['risk_tier'];
            $record->intervention_type = isset($data['intervention_type']) ? $data['intervention_type'] : 'chat';
            $record->message_preview = $data['message_preview'];
            $record->actions = $data['actions'];
            $record->timecreated = time();

            return $this->db->insert_record('at_agent13_intervention_log', $record);
        } catch (Exception $e) {
            error_log("Agent13DataContext::logIntervention Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 위험 기록 저장
     *
     * @param int $userId 사용자 ID
     * @param array $data 위험 데이터
     * @return int|bool
     */
    public function saveRiskRecord(int $userId, array $data)
    {
        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->risk_tier = $data['risk_tier'];
            $record->risk_score = $data['risk_score'];
            $record->ninactive = isset($data['ninactive']) ? $data['ninactive'] : 0;
            $record->eye_count = isset($data['eye_count']) ? $data['eye_count'] : 0;
            $record->tlaststroke_min = isset($data['tlaststroke_min']) ? $data['tlaststroke_min'] : null;
            $record->npomodoro = isset($data['npomodoro']) ? $data['npomodoro'] : 0;
            $record->timecreated = time();

            return $this->db->insert_record('at_agent13_dropout_risk', $record);
        } catch (Exception $e) {
            error_log("Agent13DataContext::saveRiskRecord Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 사용자 컨텍스트 조회 (DataContextInterface 구현)
     *
     * @param int $userId 사용자 ID
     * @return array
     */
    public function getUserContext(int $userId): array
    {
        return $this->getDropoutIndicators($userId);
    }

    /**
     * 페르소나 상태 저장 (DataContextInterface 구현)
     *
     * @param int $userId 사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param float $confidence 신뢰도
     * @param array $contextData 컨텍스트 데이터
     * @return bool
     */
    public function savePersonaState(int $userId, string $personaCode, float $confidence, array $contextData): bool
    {
        try {
            // 기존 레코드 확인
            $existing = $this->db->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'nagent' => 13
            ]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->nagent = 13;
            $record->persona_code = $personaCode;
            $record->confidence = $confidence;
            $record->context_data = json_encode($contextData);
            $record->timemodified = time();

            if ($existing) {
                $record->id = $existing->id;
                return $this->db->update_record('at_agent_persona_state', $record);
            } else {
                $record->timecreated = time();
                return $this->db->insert_record('at_agent_persona_state', $record) !== false;
            }
        } catch (Exception $e) {
            error_log("Agent13DataContext::savePersonaState Error: " . $e->getMessage() .
                      " [File: " . __FILE__ . " Line: " . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 페르소나 상태 조회 (DataContextInterface 구현)
     *
     * @param int $userId 사용자 ID
     * @return array|null
     */
    public function getPersonaState(int $userId): ?array
    {
        try {
            $record = $this->db->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'nagent' => 13
            ]);

            if ($record) {
                return [
                    'persona_code' => $record->persona_code,
                    'confidence' => (float)$record->confidence,
                    'context_data' => json_decode($record->context_data, true),
                    'timemodified' => (int)$record->timemodified
                ];
            }
        } catch (Exception $e) {
            // 테이블이 없을 수 있음
        }

        return null;
    }

    /**
     * DB 연결 체크
     *
     * @return bool
     */
    public function checkConnection(): bool
    {
        try {
            $this->db->get_record_sql("SELECT 1 as test");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 테이블 존재 여부 확인
     *
     * @param string $tableName 테이블명
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $dbman = $this->db->get_manager();
            return $dbman->table_exists($tableName);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 캐시 유효성 확인
     *
     * @param string $key 캐시 키
     * @return bool
     */
    protected function isCacheValid(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        return (time() - $this->cache[$key]['time']) < self::CACHE_TTL;
    }

    /**
     * 캐시 설정
     *
     * @param string $key 캐시 키
     * @param mixed $data 데이터
     * @return void
     */
    protected function setCache(string $key, $data): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }

    /**
     * 캐시 초기화
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}

/*
 * =========================================================================
 * 관련 DB 테이블
 * =========================================================================
 *
 * 읽기 전용 테이블 (mdl_abessi_*)
 * =========================================================================
 *
 * 1. mdl_abessi_today
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - userid: BIGINT - 사용자 ID
 *    - ninactive: INT - 비활성 횟수
 *    - nlazy: INT - 게으름 횟수
 *    - activetime: INT - 활동 시간(초)
 *    - checktime: INT - 확인 시간
 *    - status: VARCHAR - 상태
 *    - type: VARCHAR - 유형 (오늘목표, 검사요청)
 *    - timecreated: INT - 생성 시간
 *    - timemodified: INT - 수정 시간
 *
 * 2. mdl_abessi_messages
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - userid: BIGINT - 사용자 ID
 *    - tlaststroke: INT - 마지막 필기 시점 (epoch)
 *    - timemodified: INT - 수정 시간
 *
 * 3. mdl_abessi_tracking
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - userid: BIGINT - 사용자 ID
 *    - status: VARCHAR - 상태
 *    - timecreated: INT - 생성 시간
 *    - duration: INT - 지속 시간
 *    - text: TEXT - 텍스트 내용
 *
 * 4. mdl_abessi_indicators
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - userid: BIGINT - 사용자 ID
 *    - npomodoro: INT - 포모도로 횟수
 *    - kpomodoro: INT - 완료 포모도로 횟수
 *    - pmresult: VARCHAR - 포모도로 결과 (만족도)
 *    - nalt: INT - ALT 횟수
 *    - timecreated: INT - 생성 시간
 *
 * 쓰기 테이블 (mdl_at_agent13_*)
 * =========================================================================
 *
 * 5. mdl_at_agent13_dropout_risk
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - user_id: BIGINT NOT NULL - 사용자 ID
 *    - risk_tier: VARCHAR(20) NOT NULL - 위험 등급 (Low/Medium/High/Critical)
 *    - risk_score: DECIMAL(5,2) - 위험 점수 (0-100)
 *    - ninactive: INT DEFAULT 0 - 비활성 횟수
 *    - eye_count: INT DEFAULT 0 - 지연 시청 횟수
 *    - tlaststroke_min: INT - 무입력 시간(분)
 *    - npomodoro: INT DEFAULT 0 - 포모도로 횟수
 *    - timecreated: INT NOT NULL - 생성 시간
 *    - INDEX idx_user_time (user_id, timecreated)
 *    - INDEX idx_risk_tier (risk_tier)
 *
 * 6. mdl_at_agent13_intervention_log
 *    - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 *    - user_id: BIGINT NOT NULL - 사용자 ID
 *    - persona_code: VARCHAR(30) NOT NULL - 페르소나 코드
 *    - risk_tier: VARCHAR(20) NOT NULL - 위험 등급
 *    - intervention_type: VARCHAR(50) DEFAULT 'chat' - 개입 유형
 *    - message_preview: TEXT - 메시지 미리보기
 *    - actions: JSON - 실행 액션
 *    - timecreated: INT NOT NULL - 생성 시간
 *    - INDEX idx_user_time (user_id, timecreated)
 *    - INDEX idx_persona (persona_code)
 *
 * =========================================================================
 */
