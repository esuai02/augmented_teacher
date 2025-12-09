<?php
/**
 * Agent02DataContext.php
 *
 * 시험 일정 에이전트 데이터 컨텍스트
 * DB 접근 및 데이터 처리 로직
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent02ExamSchedule
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../engine_core/interfaces/DataContextInterface.php');

/**
 * Agent02 데이터 컨텍스트
 *
 * 시험 일정 관련 모든 데이터 접근 담당
 */
class Agent02DataContext implements DataContextInterface
{
    /** @var object Moodle DB 인스턴스 */
    protected $db;

    /** @var string 시험 일정 테이블 */
    protected $examTable = 'mdl_alt42_exam_schedule';

    /** @var string 전략 테이블 */
    protected $strategyTable = 'mdl_alt42g_exam_strategies';

    /** @var string 페르소나 상태 테이블 */
    protected $stateTable = 'mdl_at_agent_persona_state';

    /** @var int 에이전트 번호 */
    protected $nagent = 2;

    /**
     * 생성자
     *
     * @param object|null $db Moodle DB 인스턴스
     */
    public function __construct($db = null)
    {
        if ($db === null) {
            global $DB;
            $this->db = $DB;
        } else {
            $this->db = $db;
        }

        // 테이블 존재 확인 및 생성
        $this->ensureTablesExist();
    }

    /**
     * 테이블 존재 확인 및 생성
     *
     * @return void
     */
    protected function ensureTablesExist(): void
    {
        // 시험 일정 테이블 생성 (없을 경우)
        $sql = "CREATE TABLE IF NOT EXISTS {$this->examTable} (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            userid BIGINT NOT NULL,
            exam_name VARCHAR(255) NOT NULL,
            exam_date DATE NOT NULL,
            target_score INT DEFAULT NULL,
            subjects TEXT,
            exam_scope TEXT,
            status VARCHAR(20) DEFAULT 'active',
            timecreated INT NOT NULL,
            timemodified INT NOT NULL,
            INDEX idx_userid (userid),
            INDEX idx_exam_date (exam_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->execute($sql);
        } catch (Exception $e) {
            // 테이블이 이미 존재하면 무시
        }
    }

    // =========================================================================
    // DataContextInterface 구현
    // =========================================================================

    /**
     * 컨텍스트 데이터 조회
     *
     * @param int $userId 사용자 ID
     * @param array $options 옵션
     * @return array 컨텍스트 데이터
     */
    public function getContext(int $userId, array $options = []): array
    {
        $context = [
            'user_id' => $userId,
            'nagent' => $this->nagent,
            'exams' => [],
            'next_exam' => null,
            'd_day' => null,
            'timeline_level' => 'no_exam',
            'has_study_history' => false,
            'is_vacation' => false
        ];

        // 시험 목록 조회
        $exams = $this->getExams($userId, ['status' => 'active']);
        $context['exams'] = $exams;

        // 다음 시험 조회
        $nextExam = $this->getNextExam($userId);
        if ($nextExam) {
            $context['next_exam'] = $nextExam;
            $context['d_day'] = $this->calculateDDay($nextExam['exam_date']);
        }

        // 방학 여부 확인
        $context['is_vacation'] = $this->checkVacation();

        // 학습 이력 확인 (다른 에이전트 데이터 참조)
        $context['has_study_history'] = $this->checkStudyHistory($userId);

        return $context;
    }

    /**
     * 컨텍스트 데이터 저장
     *
     * @param int $userId 사용자 ID
     * @param array $data 저장할 데이터
     * @return bool 성공 여부
     */
    public function saveContext(int $userId, array $data): bool
    {
        // 페르소나 상태 저장
        if (isset($data['persona_code'])) {
            return $this->savePersonaState($userId, $data);
        }

        return false;
    }

    /**
     * 테스트 연결
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $result = $this->db->get_record_sql("SELECT 1 as test");
            return $result && isset($result->test) && $result->test == 1;
        } catch (Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // 시험 일정 관련 메서드
    // =========================================================================

    /**
     * 다음 시험 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 다음 시험 정보
     */
    public function getNextExam(int $userId): ?array
    {
        $today = date('Y-m-d');

        $sql = "SELECT * FROM {$this->examTable}
                WHERE userid = :userid
                AND exam_date >= :today
                AND status = 'active'
                ORDER BY exam_date ASC
                LIMIT 1";

        try {
            $record = $this->db->get_record_sql($sql, [
                'userid' => $userId,
                'today' => $today
            ]);

            if ($record) {
                return $this->formatExamRecord($record);
            }
        } catch (Exception $e) {
            $this->logError('getNextExam failed', $e, __FILE__, __LINE__);
        }

        return null;
    }

    /**
     * 시험 목록 조회
     *
     * @param int $userId 사용자 ID
     * @param array $options 옵션 (status, limit, offset, sort)
     * @return array 시험 목록
     */
    public function getExams(int $userId, array $options = []): array
    {
        $status = isset($options['status']) ? $options['status'] : null;
        $limit = isset($options['limit']) ? (int)$options['limit'] : 10;
        $offset = isset($options['offset']) ? (int)$options['offset'] : 0;
        $sort = isset($options['sort']) ? $options['sort'] : 'exam_date ASC';

        $params = ['userid' => $userId];
        $where = "userid = :userid";

        if ($status) {
            $where .= " AND status = :status";
            $params['status'] = $status;
        }

        $sql = "SELECT * FROM {$this->examTable}
                WHERE {$where}
                ORDER BY {$sort}
                LIMIT {$limit} OFFSET {$offset}";

        try {
            $records = $this->db->get_records_sql($sql, $params);
            $exams = [];

            foreach ($records as $record) {
                $exams[] = $this->formatExamRecord($record);
            }

            return $exams;
        } catch (Exception $e) {
            $this->logError('getExams failed', $e, __FILE__, __LINE__);
            return [];
        }
    }

    /**
     * 시험 추가
     *
     * @param int $userId 사용자 ID
     * @param array $examData 시험 데이터
     * @return array 결과
     */
    public function addExam(int $userId, array $examData): array
    {
        $now = time();

        $record = new stdClass();
        $record->userid = $userId;
        $record->exam_name = $examData['exam_name'];
        $record->exam_date = $examData['exam_date'];
        $record->target_score = isset($examData['target_score']) ? (int)$examData['target_score'] : null;
        $record->subjects = isset($examData['subjects']) ? json_encode($examData['subjects']) : null;
        $record->exam_scope = isset($examData['exam_scope']) ? $examData['exam_scope'] : null;
        $record->status = 'active';
        $record->timecreated = $now;
        $record->timemodified = $now;

        try {
            $id = $this->db->insert_record($this->examTable, $record);
            return [
                'success' => true,
                'id' => $id
            ];
        } catch (Exception $e) {
            $this->logError('addExam failed', $e, __FILE__, __LINE__);
            throw $e;
        }
    }

    /**
     * 시험 수정
     *
     * @param int $examId 시험 ID
     * @param int $userId 사용자 ID
     * @param array $examData 수정할 데이터
     * @return bool 성공 여부
     */
    public function updateExam(int $examId, int $userId, array $examData): bool
    {
        // 소유권 확인
        $existing = $this->db->get_record($this->examTable, [
            'id' => $examId,
            'userid' => $userId
        ]);

        if (!$existing) {
            throw new Exception("Exam not found or access denied. File: " . __FILE__ . " Line: " . __LINE__);
        }

        $record = new stdClass();
        $record->id = $examId;
        $record->timemodified = time();

        if (isset($examData['exam_name'])) {
            $record->exam_name = $examData['exam_name'];
        }
        if (isset($examData['exam_date'])) {
            $record->exam_date = $examData['exam_date'];
        }
        if (isset($examData['target_score'])) {
            $record->target_score = (int)$examData['target_score'];
        }
        if (isset($examData['subjects'])) {
            $record->subjects = json_encode($examData['subjects']);
        }
        if (isset($examData['status'])) {
            $record->status = $examData['status'];
        }

        try {
            return $this->db->update_record($this->examTable, $record);
        } catch (Exception $e) {
            $this->logError('updateExam failed', $e, __FILE__, __LINE__);
            throw $e;
        }
    }

    /**
     * 시험 삭제 (soft delete)
     *
     * @param int $examId 시험 ID
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function deleteExam(int $examId, int $userId): bool
    {
        return $this->updateExam($examId, $userId, ['status' => 'cancelled']);
    }

    /**
     * 시험 완료 처리
     *
     * @param int $examId 시험 ID
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function completeExam(int $examId, int $userId): bool
    {
        return $this->updateExam($examId, $userId, ['status' => 'completed']);
    }

    // =========================================================================
    // 페르소나 상태 관련 메서드
    // =========================================================================

    /**
     * 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 페르소나 상태
     */
    public function getPersonaState(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->stateTable}
                WHERE user_id = :user_id AND nagent = :nagent";

        try {
            $record = $this->db->get_record_sql($sql, [
                'user_id' => $userId,
                'nagent' => $this->nagent
            ]);

            if ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'nagent' => $record->nagent,
                    'persona_code' => $record->persona_code,
                    'confidence' => (float)$record->confidence,
                    'context_data' => json_decode($record->context_data, true),
                    'timecreated' => $record->timecreated,
                    'timemodified' => $record->timemodified
                ];
            }
        } catch (Exception $e) {
            $this->logError('getPersonaState failed', $e, __FILE__, __LINE__);
        }

        return null;
    }

    /**
     * 페르소나 상태 저장
     *
     * @param int $userId 사용자 ID
     * @param array $data 상태 데이터
     * @return bool 성공 여부
     */
    public function savePersonaState(int $userId, array $data): bool
    {
        $now = time();
        $existing = $this->getPersonaState($userId);

        $record = new stdClass();
        $record->user_id = $userId;
        $record->nagent = $this->nagent;
        $record->persona_code = $data['persona_code'];
        $record->confidence = isset($data['confidence']) ? $data['confidence'] : 0.5;
        $record->context_data = json_encode(isset($data['context_data']) ? $data['context_data'] : []);
        $record->timemodified = $now;

        try {
            if ($existing) {
                $record->id = $existing['id'];
                return $this->db->update_record($this->stateTable, $record);
            } else {
                $record->timecreated = $now;
                $this->db->insert_record($this->stateTable, $record);
                return true;
            }
        } catch (Exception $e) {
            $this->logError('savePersonaState failed', $e, __FILE__, __LINE__);
            return false;
        }
    }

    // =========================================================================
    // 전략 관련 메서드
    // =========================================================================

    /**
     * 생성된 전략 저장
     *
     * @param int $userId 사용자 ID
     * @param array $strategyData 전략 데이터
     * @return int 저장된 ID
     */
    public function saveStrategy(int $userId, array $strategyData): int
    {
        $record = new stdClass();
        $record->userid = $userId;
        $record->exam_timeline = $strategyData['timeline'];
        $record->goal_analysis_data = json_encode(isset($strategyData['goal_analysis']) ? $strategyData['goal_analysis'] : []);
        $record->generated_strategy = json_encode($strategyData['strategy']);
        $record->timecreated = time();

        try {
            return $this->db->insert_record($this->strategyTable, $record);
        } catch (Exception $e) {
            $this->logError('saveStrategy failed', $e, __FILE__, __LINE__);
            throw $e;
        }
    }

    /**
     * 최근 전략 조회
     *
     * @param int $userId 사용자 ID
     * @param string $timeline 타임라인
     * @return array|null 전략 데이터
     */
    public function getLatestStrategy(int $userId, string $timeline): ?array
    {
        $sql = "SELECT * FROM {$this->strategyTable}
                WHERE userid = :userid AND exam_timeline = :timeline
                ORDER BY timecreated DESC
                LIMIT 1";

        try {
            $record = $this->db->get_record_sql($sql, [
                'userid' => $userId,
                'timeline' => $timeline
            ]);

            if ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->userid,
                    'timeline' => $record->exam_timeline,
                    'goal_analysis' => json_decode($record->goal_analysis_data, true),
                    'strategy' => json_decode($record->generated_strategy, true),
                    'timecreated' => $record->timecreated
                ];
            }
        } catch (Exception $e) {
            $this->logError('getLatestStrategy failed', $e, __FILE__, __LINE__);
        }

        return null;
    }

    // =========================================================================
    // 유틸리티 메서드
    // =========================================================================

    /**
     * D-Day 계산
     *
     * @param string $examDate 시험 날짜
     * @return int D-Day
     */
    protected function calculateDDay(string $examDate): int
    {
        $exam = new DateTime($examDate);
        $today = new DateTime('today');
        $diff = $today->diff($exam);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * 방학 여부 확인
     *
     * @return bool
     */
    protected function checkVacation(): bool
    {
        $month = (int)date('m');

        // 간단한 방학 체크 (7-8월, 12-2월)
        if (in_array($month, [7, 8, 12, 1, 2])) {
            return true;
        }

        return false;
    }

    /**
     * 학습 이력 확인
     *
     * @param int $userId 사용자 ID
     * @return bool
     */
    protected function checkStudyHistory(int $userId): bool
    {
        // Agent09(학습관리) 또는 Agent10(개념노트) 데이터 확인
        // 간단한 구현 - 실제로는 다른 에이전트 데이터 조회 필요
        try {
            $sql = "SELECT COUNT(*) as cnt FROM mdl_at_agent_persona_state
                    WHERE user_id = :user_id AND nagent IN (9, 10)";
            $result = $this->db->get_record_sql($sql, ['user_id' => $userId]);
            return $result && $result->cnt > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 시험 레코드 포맷
     *
     * @param object $record DB 레코드
     * @return array 포맷된 데이터
     */
    protected function formatExamRecord($record): array
    {
        return [
            'id' => $record->id,
            'userid' => $record->userid,
            'exam_name' => $record->exam_name,
            'exam_date' => $record->exam_date,
            'target_score' => $record->target_score,
            'subjects' => $record->subjects ? json_decode($record->subjects, true) : [],
            'exam_scope' => isset($record->exam_scope) ? $record->exam_scope : null,
            'status' => $record->status,
            'd_day' => $this->calculateDDay($record->exam_date),
            'timecreated' => $record->timecreated,
            'timemodified' => $record->timemodified
        ];
    }

    /**
     * 에러 로깅
     *
     * @param string $message 메시지
     * @param Exception $e 예외
     * @param string $file 파일
     * @param int $line 라인
     * @return void
     */
    protected function logError(string $message, Exception $e, string $file, int $line): void
    {
        error_log(sprintf(
            "[Agent02DataContext] %s - Error: %s | File: %s | Line: %d",
            $message,
            $e->getMessage(),
            $file,
            $line
        ));
    }
}

/*
 * =========================================================================
 * 관련 DB 테이블
 * =========================================================================
 *
 * mdl_alt42_exam_schedule (시험 일정)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - userid: BIGINT NOT NULL
 * - exam_name: VARCHAR(255) NOT NULL
 * - exam_date: DATE NOT NULL
 * - target_score: INT
 * - subjects: TEXT (JSON array)
 * - exam_scope: TEXT
 * - status: VARCHAR(20) DEFAULT 'active' (active|completed|cancelled)
 * - timecreated: INT NOT NULL
 * - timemodified: INT NOT NULL
 *
 * mdl_alt42g_exam_strategies (생성된 전략)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - userid: BIGINT NOT NULL
 * - exam_timeline: VARCHAR(20) NOT NULL
 * - goal_analysis_data: TEXT (JSON)
 * - generated_strategy: TEXT (JSON)
 * - timecreated: INT NOT NULL
 *
 * mdl_at_agent_persona_state (공통 페르소나 상태)
 * - id: BIGINT AUTO_INCREMENT PRIMARY KEY
 * - user_id: BIGINT NOT NULL
 * - nagent: TINYINT NOT NULL
 * - persona_code: VARCHAR(20)
 * - confidence: DECIMAL(3,2)
 * - context_data: JSON
 * - timecreated: INT
 * - timemodified: INT
 * =========================================================================
 */
