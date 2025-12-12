<?php
/**
 * EmotionStateRepository.php
 *
 * Agent05 학습감정 상태 저장소
 * 감정 상태의 CRUD 및 분석 쿼리 제공
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\DB
 * @author Augmented Teacher Development Team
 * @version 1.0.0
 * @since 2025-06-03
 *
 * 관련 DB 테이블:
 * - mdl_at_learning_emotion_log: 감정 로그 (emotion_type, emotion_intensity, confidence_score 등)
 * - mdl_at_emotion_transition_log: 감정 전환 기록 (from_emotion, to_emotion, trigger_type 등)
 * - mdl_at_agent_emotion_share: 에이전트간 감정 공유 (source_agent, target_agent, emotion_data 등)
 * - mdl_at_learning_activity_log: 학습활동 로그 (activity_type, context_data, emotion_id 등)
 * - mdl_at_persona_response_log: 페르소나 응답 로그 (persona_type, template_used, response_text 등)
 * - mdl_at_emotion_pattern: 감정 패턴 (pattern_type, pattern_data, frequency 등)
 */

namespace AugmentedTeacher\Agent05\PersonaSystem\DB;

defined('MOODLE_INTERNAL') || die();

class EmotionStateRepository
{
    /** @var \moodle_database */
    private $db;

    /** @var string 테이블 접두사 */
    private const TABLE_PREFIX = 'at_';

    /** @var array 테이블 이름 상수 */
    private const TABLES = [
        'emotion_log' => 'at_learning_emotion_log',
        'transition_log' => 'at_emotion_transition_log',
        'agent_share' => 'at_agent_emotion_share',
        'activity_log' => 'at_learning_activity_log',
        'response_log' => 'at_persona_response_log',
        'pattern' => 'at_emotion_pattern'
    ];

    /** @var array 유효한 감정 타입 */
    private const VALID_EMOTIONS = [
        'anxiety', 'frustration', 'confidence', 'curiosity',
        'boredom', 'fatigue', 'achievement', 'confusion'
    ];

    /** @var array 유효한 강도 레벨 */
    private const VALID_INTENSITIES = ['high', 'medium', 'low'];

    /**
     * 생성자
     */
    public function __construct()
    {
        global $DB;
        $this->db = $DB;
    }

    // ========================================================================
    // 감정 로그 (Learning Emotion Log) CRUD
    // ========================================================================

    /**
     * 새 감정 상태 기록
     *
     * @param int $userId 사용자 ID
     * @param string $emotionType 감정 타입
     * @param string $intensity 감정 강도
     * @param float $confidenceScore 신뢰도 점수 (0.0 ~ 1.0)
     * @param string $detectionSource 감지 소스 (keyword, pattern, emoticon, mixed, ai)
     * @param string|null $triggerText 감지 트리거 텍스트
     * @param array $contextData 추가 컨텍스트 데이터
     * @return int|false 삽입된 레코드 ID 또는 실패시 false
     */
    public function saveEmotionState(
        int $userId,
        string $emotionType,
        string $intensity,
        float $confidenceScore,
        string $detectionSource = 'mixed',
        ?string $triggerText = null,
        array $contextData = []
    ) {
        // 유효성 검사
        if (!$this->validateEmotionType($emotionType)) {
            $this->logError(__METHOD__, __LINE__, "Invalid emotion type: {$emotionType}");
            return false;
        }

        if (!$this->validateIntensity($intensity)) {
            $this->logError(__METHOD__, __LINE__, "Invalid intensity: {$intensity}");
            return false;
        }

        $record = new \stdClass();
        $record->userid = $userId;
        $record->emotion_type = $emotionType;
        $record->emotion_intensity = $intensity;
        $record->confidence_score = max(0.0, min(1.0, $confidenceScore));
        $record->detection_source = $detectionSource;
        $record->trigger_text = $triggerText;
        $record->context_data = json_encode($contextData, JSON_UNESCAPED_UNICODE);
        $record->timecreated = time();

        try {
            return $this->db->insert_record(self::TABLES['emotion_log'], $record);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 특정 사용자의 최근 감정 상태 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 최대 레코드 수
     * @return array 감정 로그 목록
     */
    public function getRecentEmotions(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                ORDER BY timecreated DESC
                LIMIT :limit";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    /**
     * 특정 사용자의 현재(최신) 감정 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return object|null 최신 감정 레코드 또는 null
     */
    public function getCurrentEmotion(int $userId): ?object
    {
        $sql = "SELECT * FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                ORDER BY timecreated DESC
                LIMIT 1";

        try {
            $result = $this->db->get_record_sql($sql, ['userid' => $userId]);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return null;
        }
    }

    /**
     * 기간별 감정 로그 조회
     *
     * @param int $userId 사용자 ID
     * @param int $startTime 시작 timestamp
     * @param int $endTime 종료 timestamp
     * @param string|null $emotionType 특정 감정 타입 필터 (선택)
     * @return array 감정 로그 목록
     */
    public function getEmotionsByPeriod(
        int $userId,
        int $startTime,
        int $endTime,
        ?string $emotionType = null
    ): array {
        $params = [
            'userid' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];

        $emotionFilter = '';
        if ($emotionType && $this->validateEmotionType($emotionType)) {
            $emotionFilter = 'AND emotion_type = :emotion_type';
            $params['emotion_type'] = $emotionType;
        }

        $sql = "SELECT * FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                AND timecreated >= :start_time
                AND timecreated <= :end_time
                {$emotionFilter}
                ORDER BY timecreated ASC";

        try {
            return $this->db->get_records_sql($sql, $params);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // 감정 전환 로그 (Emotion Transition Log) 관리
    // ========================================================================

    /**
     * 감정 전환 기록
     *
     * @param int $userId 사용자 ID
     * @param string $fromEmotion 이전 감정
     * @param string $toEmotion 새 감정
     * @param string $triggerType 트리거 타입 (user_input, time_based, activity_change, external_event)
     * @param int|null $fromEmotionLogId 이전 감정 로그 ID
     * @param int|null $toEmotionLogId 새 감정 로그 ID
     * @param array $transitionData 추가 전환 데이터
     * @return int|false 삽입된 레코드 ID
     */
    public function saveEmotionTransition(
        int $userId,
        string $fromEmotion,
        string $toEmotion,
        string $triggerType,
        ?int $fromEmotionLogId = null,
        ?int $toEmotionLogId = null,
        array $transitionData = []
    ) {
        $record = new \stdClass();
        $record->userid = $userId;
        $record->from_emotion = $fromEmotion;
        $record->to_emotion = $toEmotion;
        $record->from_emotion_log_id = $fromEmotionLogId;
        $record->to_emotion_log_id = $toEmotionLogId;
        $record->trigger_type = $triggerType;
        $record->transition_data = json_encode($transitionData, JSON_UNESCAPED_UNICODE);
        $record->timecreated = time();

        try {
            return $this->db->insert_record(self::TABLES['transition_log'], $record);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 감정 전환 히스토리 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 최대 레코드 수
     * @return array 전환 로그 목록
     */
    public function getTransitionHistory(int $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM {" . self::TABLES['transition_log'] . "}
                WHERE userid = :userid
                ORDER BY timecreated DESC
                LIMIT :limit";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // 학습 활동 로그 (Learning Activity Log) 관리
    // ========================================================================

    /**
     * 학습 활동 기록
     *
     * @param int $userId 사용자 ID
     * @param string $activityType 활동 타입
     * @param int|null $emotionLogId 연관 감정 로그 ID
     * @param array $contextData 컨텍스트 데이터
     * @param int|null $durationSeconds 활동 지속 시간 (초)
     * @return int|false 삽입된 레코드 ID
     */
    public function saveActivityLog(
        int $userId,
        string $activityType,
        ?int $emotionLogId = null,
        array $contextData = [],
        ?int $durationSeconds = null
    ) {
        $record = new \stdClass();
        $record->userid = $userId;
        $record->activity_type = $activityType;
        $record->emotion_id = $emotionLogId;
        $record->context_data = json_encode($contextData, JSON_UNESCAPED_UNICODE);
        $record->duration_seconds = $durationSeconds;
        $record->timecreated = time();

        try {
            return $this->db->insert_record(self::TABLES['activity_log'], $record);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 활동별 감정 상관관계 조회
     *
     * @param int $userId 사용자 ID
     * @param string $activityType 활동 타입
     * @return array 감정 분포 통계
     */
    public function getEmotionsByActivity(int $userId, string $activityType): array
    {
        $sql = "SELECT
                    el.emotion_type,
                    el.emotion_intensity,
                    COUNT(*) as count,
                    AVG(el.confidence_score) as avg_confidence
                FROM {" . self::TABLES['activity_log'] . "} al
                JOIN {" . self::TABLES['emotion_log'] . "} el ON al.emotion_id = el.id
                WHERE al.userid = :userid
                AND al.activity_type = :activity_type
                GROUP BY el.emotion_type, el.emotion_intensity
                ORDER BY count DESC";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'activity_type' => $activityType
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // 페르소나 응답 로그 (Persona Response Log) 관리
    // ========================================================================

    /**
     * 페르소나 응답 기록
     *
     * @param int $userId 사용자 ID
     * @param string $personaType 페르소나 타입
     * @param int|null $emotionLogId 연관 감정 로그 ID
     * @param string $templateUsed 사용된 템플릿 ID
     * @param string $responseText 응답 텍스트
     * @param string|null $userFeedback 사용자 피드백
     * @return int|false 삽입된 레코드 ID
     */
    public function saveResponseLog(
        int $userId,
        string $personaType,
        ?int $emotionLogId,
        string $templateUsed,
        string $responseText,
        ?string $userFeedback = null
    ) {
        $record = new \stdClass();
        $record->userid = $userId;
        $record->emotion_id = $emotionLogId;
        $record->persona_type = $personaType;
        $record->template_used = $templateUsed;
        $record->response_text = $responseText;
        $record->user_feedback = $userFeedback;
        $record->timecreated = time();

        try {
            return $this->db->insert_record(self::TABLES['response_log'], $record);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 페르소나 응답 피드백 업데이트
     *
     * @param int $responseLogId 응답 로그 ID
     * @param string $feedback 피드백 (positive, negative, neutral)
     * @return bool 성공 여부
     */
    public function updateResponseFeedback(int $responseLogId, string $feedback): bool
    {
        try {
            return $this->db->set_field(
                self::TABLES['response_log'],
                'user_feedback',
                $feedback,
                ['id' => $responseLogId]
            );
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 페르소나별 효과 분석
     *
     * @param int $userId 사용자 ID
     * @return array 페르소나별 피드백 통계
     */
    public function getPersonaEffectiveness(int $userId): array
    {
        $sql = "SELECT
                    persona_type,
                    COUNT(*) as total_responses,
                    SUM(CASE WHEN user_feedback = 'positive' THEN 1 ELSE 0 END) as positive_count,
                    SUM(CASE WHEN user_feedback = 'negative' THEN 1 ELSE 0 END) as negative_count,
                    SUM(CASE WHEN user_feedback = 'neutral' THEN 1 ELSE 0 END) as neutral_count
                FROM {" . self::TABLES['response_log'] . "}
                WHERE userid = :userid
                GROUP BY persona_type
                ORDER BY positive_count DESC";

        try {
            return $this->db->get_records_sql($sql, ['userid' => $userId]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // 감정 패턴 (Emotion Pattern) 관리
    // ========================================================================

    /**
     * 감정 패턴 저장 또는 업데이트
     *
     * @param int $userId 사용자 ID
     * @param string $patternType 패턴 타입 (daily, weekly, activity_based, trigger_based)
     * @param array $patternData 패턴 데이터
     * @return int|false 레코드 ID
     */
    public function saveEmotionPattern(
        int $userId,
        string $patternType,
        array $patternData
    ) {
        // 기존 패턴 확인
        $existing = $this->db->get_record(self::TABLES['pattern'], [
            'userid' => $userId,
            'pattern_type' => $patternType
        ]);

        $record = new \stdClass();
        $record->userid = $userId;
        $record->pattern_type = $patternType;
        $record->pattern_data = json_encode($patternData, JSON_UNESCAPED_UNICODE);
        $record->timemodified = time();

        try {
            if ($existing) {
                // 기존 레코드 업데이트
                $record->id = $existing->id;
                $record->frequency = $existing->frequency + 1;
                $this->db->update_record(self::TABLES['pattern'], $record);
                return $existing->id;
            } else {
                // 새 레코드 삽입
                $record->frequency = 1;
                $record->timecreated = time();
                return $this->db->insert_record(self::TABLES['pattern'], $record);
            }
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 감정 패턴 조회
     *
     * @param int $userId 사용자 ID
     * @param string|null $patternType 특정 패턴 타입 (선택)
     * @return array 패턴 목록
     */
    public function getEmotionPatterns(int $userId, ?string $patternType = null): array
    {
        $conditions = ['userid' => $userId];
        if ($patternType) {
            $conditions['pattern_type'] = $patternType;
        }

        try {
            return $this->db->get_records(self::TABLES['pattern'], $conditions, 'frequency DESC');
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // 분석 및 통계 메서드
    // ========================================================================

    /**
     * 감정 분포 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param int|null $days 최근 N일 (기본 30일)
     * @return array 감정별 통계
     */
    public function getEmotionDistribution(int $userId, ?int $days = 30): array
    {
        $startTime = time() - ($days * 24 * 60 * 60);

        $sql = "SELECT
                    emotion_type,
                    emotion_intensity,
                    COUNT(*) as count,
                    AVG(confidence_score) as avg_confidence,
                    MIN(timecreated) as first_occurrence,
                    MAX(timecreated) as last_occurrence
                FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                AND timecreated >= :start_time
                GROUP BY emotion_type, emotion_intensity
                ORDER BY count DESC";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'start_time' => $startTime
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    /**
     * 시간대별 감정 추세 분석
     *
     * @param int $userId 사용자 ID
     * @param int $days 분석 기간 (일)
     * @return array 시간대별 감정 분포
     */
    public function getEmotionTrend(int $userId, int $days = 7): array
    {
        $startTime = time() - ($days * 24 * 60 * 60);

        $sql = "SELECT
                    DATE(FROM_UNIXTIME(timecreated)) as date,
                    HOUR(FROM_UNIXTIME(timecreated)) as hour,
                    emotion_type,
                    COUNT(*) as count
                FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                AND timecreated >= :start_time
                GROUP BY date, hour, emotion_type
                ORDER BY date, hour";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'start_time' => $startTime
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    /**
     * 주요 감정 트리거 분석
     *
     * @param int $userId 사용자 ID
     * @param string $emotionType 분석할 감정 타입
     * @return array 트리거 분석 결과
     */
    public function analyzeTriggers(int $userId, string $emotionType): array
    {
        $sql = "SELECT
                    detection_source,
                    trigger_text,
                    COUNT(*) as frequency,
                    AVG(confidence_score) as avg_confidence
                FROM {" . self::TABLES['emotion_log'] . "}
                WHERE userid = :userid
                AND emotion_type = :emotion_type
                AND trigger_text IS NOT NULL
                GROUP BY detection_source, trigger_text
                ORDER BY frequency DESC
                LIMIT 20";

        try {
            return $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'emotion_type' => $emotionType
            ]);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return [];
        }
    }

    /**
     * 연속 부정적 감정 감지
     *
     * @param int $userId 사용자 ID
     * @param int $threshold 연속 횟수 임계값
     * @return array 연속 부정 감정 시퀀스
     */
    public function detectNegativeStreak(int $userId, int $threshold = 3): array
    {
        $negativeEmotions = ['anxiety', 'frustration', 'boredom', 'fatigue', 'confusion'];
        $recentEmotions = $this->getRecentEmotions($userId, $threshold + 2);

        $streak = [];
        $currentStreak = [];

        foreach ($recentEmotions as $emotion) {
            if (in_array($emotion->emotion_type, $negativeEmotions)) {
                $currentStreak[] = $emotion;
                if (count($currentStreak) >= $threshold) {
                    $streak = $currentStreak;
                    break;
                }
            } else {
                $currentStreak = [];
            }
        }

        return [
            'has_streak' => count($streak) >= $threshold,
            'streak_count' => count($streak),
            'emotions' => $streak
        ];
    }

    /**
     * 감정 개선 추적
     *
     * @param int $userId 사용자 ID
     * @param string $fromEmotion 이전 감정
     * @param string $toEmotion 목표 감정
     * @param int $hours 추적 기간 (시간)
     * @return array 개선 추적 결과
     */
    public function trackEmotionImprovement(
        int $userId,
        string $fromEmotion,
        string $toEmotion,
        int $hours = 24
    ): array {
        $startTime = time() - ($hours * 60 * 60);

        $sql = "SELECT * FROM {" . self::TABLES['transition_log'] . "}
                WHERE userid = :userid
                AND from_emotion = :from_emotion
                AND to_emotion = :to_emotion
                AND timecreated >= :start_time
                ORDER BY timecreated DESC";

        try {
            $transitions = $this->db->get_records_sql($sql, [
                'userid' => $userId,
                'from_emotion' => $fromEmotion,
                'to_emotion' => $toEmotion,
                'start_time' => $startTime
            ]);

            return [
                'improvement_count' => count($transitions),
                'transitions' => $transitions,
                'success' => count($transitions) > 0
            ];
        } catch (\Exception $e) {
            $this->logError(__METHOD__, __LINE__, $e->getMessage());
            return ['improvement_count' => 0, 'transitions' => [], 'success' => false];
        }
    }

    // ========================================================================
    // 유틸리티 메서드
    // ========================================================================

    /**
     * 감정 타입 유효성 검사
     *
     * @param string $emotionType 검사할 감정 타입
     * @return bool 유효 여부
     */
    private function validateEmotionType(string $emotionType): bool
    {
        return in_array($emotionType, self::VALID_EMOTIONS);
    }

    /**
     * 강도 유효성 검사
     *
     * @param string $intensity 검사할 강도
     * @return bool 유효 여부
     */
    private function validateIntensity(string $intensity): bool
    {
        return in_array($intensity, self::VALID_INTENSITIES);
    }

    /**
     * 에러 로깅
     *
     * @param string $method 메서드명
     * @param int $line 라인 번호
     * @param string $message 에러 메시지
     */
    private function logError(string $method, int $line, string $message): void
    {
        error_log("[EmotionStateRepository] {$method}:{$line} - {$message}");
    }

    /**
     * 테이블 존재 여부 확인
     *
     * @return array 테이블별 존재 여부
     */
    public function checkTablesExist(): array
    {
        $dbManager = $this->db->get_manager();
        $status = [];

        foreach (self::TABLES as $key => $tableName) {
            $status[$key] = $dbManager->table_exists($tableName);
        }

        return $status;
    }

    /**
     * 사용자 감정 데이터 삭제 (GDPR 준수용)
     *
     * @param int $userId 사용자 ID
     * @return array 삭제 결과
     */
    public function deleteUserData(int $userId): array
    {
        $results = [];

        foreach (self::TABLES as $key => $tableName) {
            try {
                $deleted = $this->db->delete_records($tableName, ['userid' => $userId]);
                $results[$key] = ['success' => true, 'deleted' => $deleted];
            } catch (\Exception $e) {
                $results[$key] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
