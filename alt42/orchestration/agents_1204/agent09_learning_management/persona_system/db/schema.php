<?php
/**
 * Agent09 Persona System DB Schema
 *
 * 학습관리 에이전트의 페르소나 시스템을 위한 DB 스키마 정의 및 마이그레이션
 * 실행 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/db/schema.php
 *
 * @package AugmentedTeacher\Agent09\PersonaSystem\DB
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 체크
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die("관리자 권한이 필요합니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
}

/**
 * 스키마 정의 클래스
 */
class Agent09SchemaManager {

    /** @var string 현재 파일 경로 */
    private $currentFile;

    /** @var array 실행 결과 */
    private $results = [];

    public function __construct() {
        $this->currentFile = __FILE__;
    }

    /**
     * 모든 테이블 생성
     */
    public function createAllTables(): array {
        $this->results = [];

        // 1. 페르소나 상태 테이블
        $this->createPersonaStateTable();

        // 2. 페르소나 전환 로그 테이블
        $this->createPersonaTransitionTable();

        // 3. 개입 기록 테이블
        $this->createInterventionLogTable();

        // 4. 학습 지표 테이블들 (기존 테이블 확인 및 생성)
        $this->createAttendanceLogTable();
        $this->createStudentGoalsTable();
        $this->createPomodoroSessionsTable();
        $this->createWrongNotesTable();
        $this->createTestResultsTable();

        return $this->results;
    }

    /**
     * 페르소나 상태 테이블
     */
    private function createPersonaStateTable(): void {
        global $DB;
        $tableName = 'mdl_at_agent_persona_state';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(20) NOT NULL DEFAULT 'agent09',
            persona_code VARCHAR(30) NOT NULL COMMENT '현재 페르소나 코드 (P-SPARSE, D-CRITICAL 등)',
            persona_series VARCHAR(10) NOT NULL COMMENT '페르소나 시리즈 (P, D, A, G, F, R, T, E)',
            confidence_score DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT '식별 신뢰도 (0.00~1.00)',
            data_density_score DECIMAL(3,2) DEFAULT NULL COMMENT '데이터 밀도 점수',
            balance_score DECIMAL(3,2) DEFAULT NULL COMMENT '데이터 균형 점수',
            stability_score DECIMAL(3,2) DEFAULT NULL COMMENT '패턴 안정성 점수',
            dropout_risk_score DECIMAL(3,2) DEFAULT NULL COMMENT '이탈 위험 점수',
            intervention_level ENUM('none', 'low', 'medium', 'high', 'critical') DEFAULT 'none',
            recommended_tone VARCHAR(30) DEFAULT NULL COMMENT '권장 톤 (Warm, Gentle, Encouraging 등)',
            recommended_pace VARCHAR(20) DEFAULT NULL COMMENT '권장 페이스 (very_slow, slow, normal, fast)',
            context_snapshot JSON DEFAULT NULL COMMENT '컨텍스트 스냅샷',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_agent (user_id, agent_id),
            KEY idx_persona_code (persona_code),
            KEY idx_active (is_active),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Agent09 페르소나 상태 저장'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 페르소나 전환 로그 테이블
     */
    private function createPersonaTransitionTable(): void {
        global $DB;
        $tableName = 'mdl_at_persona_transition_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(20) NOT NULL DEFAULT 'agent09',
            from_persona VARCHAR(30) DEFAULT NULL COMMENT '이전 페르소나',
            to_persona VARCHAR(30) NOT NULL COMMENT '새 페르소나',
            trigger_rule_id VARCHAR(50) DEFAULT NULL COMMENT '트리거된 규칙 ID',
            trigger_reason VARCHAR(255) DEFAULT NULL COMMENT '전환 이유',
            confidence_before DECIMAL(3,2) DEFAULT NULL,
            confidence_after DECIMAL(3,2) DEFAULT NULL,
            context_diff JSON DEFAULT NULL COMMENT '컨텍스트 변화',
            transition_type ENUM('upgrade', 'downgrade', 'lateral', 'initial') NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_agent (user_id, agent_id),
            KEY idx_transition (from_persona, to_persona),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='페르소나 전환 이력'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 개입 기록 테이블
     */
    private function createInterventionLogTable(): void {
        global $DB;
        $tableName = 'mdl_at_intervention_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(20) NOT NULL DEFAULT 'agent09',
            persona_code VARCHAR(30) NOT NULL,
            intervention_type ENUM('encouragement', 'routine_redesign', 'urgent_contact', 'reentry_path', 'goal_adjustment', 'review_reminder') NOT NULL,
            intervention_level ENUM('주의', '경고', '긴급') NOT NULL,
            indicator_type ENUM('attendance', 'goal', 'pomodoro', 'wrong_note', 'test', 'composite') NOT NULL,
            message_sent TEXT DEFAULT NULL COMMENT '발송된 메시지 내용',
            response_received TINYINT(1) DEFAULT 0,
            response_content TEXT DEFAULT NULL,
            response_at DATETIME DEFAULT NULL,
            effectiveness_score DECIMAL(3,2) DEFAULT NULL COMMENT '개입 효과 점수 (0.00~1.00)',
            follow_up_needed TINYINT(1) DEFAULT 0,
            follow_up_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_agent (user_id, agent_id),
            KEY idx_intervention_type (intervention_type),
            KEY idx_level (intervention_level),
            KEY idx_indicator (indicator_type),
            KEY idx_follow_up (follow_up_needed, follow_up_date),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='학습 개입 기록'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 출결 로그 테이블
     */
    private function createAttendanceLogTable(): void {
        $tableName = 'mdl_at_attendance_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'absent',
            check_in_time DATETIME DEFAULT NULL,
            check_out_time DATETIME DEFAULT NULL,
            duration_minutes INT(10) DEFAULT NULL COMMENT '체류 시간(분)',
            location VARCHAR(100) DEFAULT NULL COMMENT '출석 장소',
            verification_method VARCHAR(50) DEFAULT NULL COMMENT '확인 방법',
            notes VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_date (user_id, attendance_date),
            KEY idx_status (status),
            KEY idx_date (attendance_date),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='학생 출결 로그'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 학생 목표 테이블
     */
    private function createStudentGoalsTable(): void {
        $tableName = 'mdl_at_student_goals';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            goal_type ENUM('daily', 'weekly', 'monthly', 'term', 'custom') NOT NULL DEFAULT 'weekly',
            goal_title VARCHAR(255) NOT NULL,
            goal_description TEXT DEFAULT NULL,
            target_value INT(10) UNSIGNED NOT NULL DEFAULT 100,
            current_value INT(10) UNSIGNED NOT NULL DEFAULT 0,
            unit VARCHAR(50) DEFAULT NULL COMMENT '단위 (문제, 시간, 세션 등)',
            start_date DATE NOT NULL,
            due_date DATE NOT NULL,
            status ENUM('active', 'completed', 'cancelled', 'overdue') NOT NULL DEFAULT 'active',
            priority TINYINT(1) UNSIGNED NOT NULL DEFAULT 3 COMMENT '우선순위 (1: 최우선 ~ 5: 낮음)',
            category VARCHAR(50) DEFAULT NULL COMMENT '목표 카테고리 (학습, 출석, 복습 등)',
            parent_goal_id BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '상위 목표 ID',
            reminder_enabled TINYINT(1) DEFAULT 1,
            reminder_days_before INT(10) DEFAULT 3,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_user_status (user_id, status),
            KEY idx_due_date (due_date),
            KEY idx_priority (priority),
            KEY idx_parent (parent_goal_id),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='학생 목표 설정'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 포모도로 세션 테이블
     */
    private function createPomodoroSessionsTable(): void {
        $tableName = 'mdl_at_pomodoro_sessions';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            session_date DATE NOT NULL,
            planned_sessions TINYINT(3) UNSIGNED NOT NULL DEFAULT 4,
            completed_sessions TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            focus_duration_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '총 집중 시간(분)',
            break_duration_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '총 휴식 시간(분)',
            interruption_count TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            early_quit_count TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '조기 종료 횟수',
            average_focus_quality DECIMAL(3,2) DEFAULT NULL COMMENT '평균 집중 품질 (0.00~1.00)',
            subject_focus VARCHAR(100) DEFAULT NULL COMMENT '집중 과목',
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_date (user_id, session_date),
            KEY idx_date (session_date),
            KEY idx_completion (completed_sessions),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='포모도로 세션 기록'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 오답노트 테이블
     */
    private function createWrongNotesTable(): void {
        $tableName = 'mdl_at_wrong_notes';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            concept_name VARCHAR(100) NOT NULL COMMENT '개념명',
            concept_category VARCHAR(50) NOT NULL COMMENT '개념 카테고리 (연산, 도형, 확률 등)',
            error_type VARCHAR(50) NOT NULL COMMENT '오류 유형 (계산실수, 개념오해, 문제이해 등)',
            question_id BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '관련 문제 ID',
            error_count INT(10) UNSIGNED NOT NULL DEFAULT 1,
            review_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
            correct_after_review INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '복습 후 정답 횟수',
            mastery_level DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT '숙달도 (0.00~1.00)',
            difficulty_perceived TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '체감 난이도 (1~5)',
            self_explanation TEXT DEFAULT NULL COMMENT '학생 자기 설명',
            teacher_feedback TEXT DEFAULT NULL COMMENT '선생님 피드백',
            next_review_date DATE DEFAULT NULL COMMENT '다음 복습 예정일',
            last_review_date DATE DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_concept (concept_name),
            KEY idx_category (concept_category),
            KEY idx_mastery (mastery_level),
            KEY idx_next_review (next_review_date),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='오답노트'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 시험 결과 테이블
     */
    private function createTestResultsTable(): void {
        $tableName = 'mdl_at_test_results';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            test_name VARCHAR(100) NOT NULL,
            test_type ENUM('quiz', 'unit_test', 'midterm', 'final', 'mock', 'diagnostic', 'custom') NOT NULL,
            test_date DATE NOT NULL,
            subject VARCHAR(50) DEFAULT NULL COMMENT '과목',
            score DECIMAL(5,2) NOT NULL,
            max_score DECIMAL(5,2) NOT NULL DEFAULT 100.00,
            percentile INT(10) UNSIGNED DEFAULT NULL COMMENT '백분위',
            grade VARCHAR(10) DEFAULT NULL COMMENT '등급',
            time_taken_minutes INT(10) UNSIGNED DEFAULT NULL COMMENT '소요 시간(분)',
            questions_total INT(10) UNSIGNED DEFAULT NULL,
            questions_correct INT(10) UNSIGNED DEFAULT NULL,
            questions_wrong INT(10) UNSIGNED DEFAULT NULL,
            questions_skipped INT(10) UNSIGNED DEFAULT NULL,
            weak_areas JSON DEFAULT NULL COMMENT '취약 영역 배열',
            strong_areas JSON DEFAULT NULL COMMENT '강점 영역 배열',
            error_analysis JSON DEFAULT NULL COMMENT '오류 분석 상세',
            teacher_comment TEXT DEFAULT NULL,
            self_reflection TEXT DEFAULT NULL COMMENT '학생 자기 성찰',
            improvement_plan TEXT DEFAULT NULL COMMENT '개선 계획',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_date (test_date),
            KEY idx_type (test_type),
            KEY idx_subject (subject),
            KEY idx_score (score),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='시험 결과'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 테이블 존재 여부 확인
     */
    private function tableExists(string $tableName): bool {
        global $DB;

        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = $DB->get_record_sql($sql, [$tableName]);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * SQL 실행
     */
    private function executeSQL(string $sql, string $tableName): void {
        global $DB;

        try {
            $DB->execute($sql);
            $this->results[] = "✓ {$tableName} - 생성 완료";
        } catch (Exception $e) {
            $this->results[] = "✗ {$tableName} - 생성 실패: " . $e->getMessage() .
                " [File: {$this->currentFile}, Line: " . __LINE__ . "]";
        }
    }

    /**
     * 테이블 삭제 (개발용)
     */
    public function dropTable(string $tableName): bool {
        global $DB;

        try {
            $sql = "DROP TABLE IF EXISTS {$tableName}";
            $DB->execute($sql);
            return true;
        } catch (Exception $e) {
            error_log("테이블 삭제 실패: {$tableName} - " . $e->getMessage() .
                " [File: {$this->currentFile}, Line: " . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 전체 테이블 목록 조회
     */
    public function getPersonaSystemTables(): array {
        return [
            'mdl_at_agent_persona_state',
            'mdl_at_persona_transition_log',
            'mdl_at_intervention_log',
            'mdl_at_attendance_log',
            'mdl_at_student_goals',
            'mdl_at_pomodoro_sessions',
            'mdl_at_wrong_notes',
            'mdl_at_test_results'
        ];
    }

    /**
     * 테이블 상태 확인
     */
    public function checkTablesStatus(): array {
        $status = [];

        foreach ($this->getPersonaSystemTables() as $table) {
            $status[$table] = [
                'exists' => $this->tableExists($table),
                'count' => $this->tableExists($table) ? $this->getTableRowCount($table) : 0
            ];
        }

        return $status;
    }

    /**
     * 테이블 행 수 조회
     */
    private function getTableRowCount(string $tableName): int {
        global $DB;

        try {
            $sql = "SELECT COUNT(*) as cnt FROM {$tableName}";
            $result = $DB->get_record_sql($sql);
            return (int)($result->cnt ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
}

// ==========================================
// 실행 모드 처리
// ==========================================

$action = $_GET['action'] ?? 'status';
$schemaManager = new Agent09SchemaManager();

echo "<html><head><meta charset='utf-8'><title>Agent09 Schema Manager</title>";
echo "<style>
    body { font-family: 'Noto Sans KR', sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #666; margin-top: 30px; }
    .result { padding: 10px; margin: 5px 0; border-radius: 4px; }
    .success { background: #e8f5e9; color: #2e7d32; }
    .error { background: #ffebee; color: #c62828; }
    .info { background: #e3f2fd; color: #1565c0; }
    .btn { display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-create { background: #4CAF50; }
    .btn-status { background: #2196F3; }
    .btn-danger { background: #f44336; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f5f5f5; }
    .exists { color: #4CAF50; }
    .not-exists { color: #f44336; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>Agent09 Persona System - DB Schema Manager</h1>";

switch ($action) {
    case 'create':
        echo "<h2>테이블 생성 결과</h2>";
        $results = $schemaManager->createAllTables();
        foreach ($results as $result) {
            $class = strpos($result, '✓') !== false ? 'success' : 'error';
            echo "<div class='result {$class}'>{$result}</div>";
        }
        break;

    case 'status':
    default:
        echo "<h2>테이블 상태</h2>";
        $status = $schemaManager->checkTablesStatus();
        echo "<table>";
        echo "<tr><th>테이블명</th><th>존재 여부</th><th>레코드 수</th></tr>";
        foreach ($status as $table => $info) {
            $existsClass = $info['exists'] ? 'exists' : 'not-exists';
            $existsText = $info['exists'] ? '✓ 존재' : '✗ 없음';
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td class='{$existsClass}'>{$existsText}</td>";
            echo "<td>{$info['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        break;
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='?action=status' class='btn btn-status'>상태 확인</a>";
echo "<a href='?action=create' class='btn btn-create'>테이블 생성</a>";
echo "</div>";

echo "<div class='result info' style='margin-top: 20px;'>";
echo "<strong>파일 위치:</strong> " . __FILE__ . "<br>";
echo "<strong>현재 시각:</strong> " . date('Y-m-d H:i:s');
echo "</div>";

echo "</div></body></html>";

/*
 * 생성되는 테이블 목록:
 *
 * 1. mdl_at_agent_persona_state - 현재 페르소나 상태
 *    - persona_code: P-SPARSE, D-CRITICAL, A-DECLINING 등
 *    - persona_series: P(Pattern), D(Dropout), A(Attendance), G(Goal), F(Pomodoro), R(Wrong note), T(Test), E(Emotion)
 *    - confidence_score: 식별 신뢰도
 *    - data_density_score, balance_score, stability_score: 분석 점수
 *    - dropout_risk_score: 이탈 위험 점수
 *    - intervention_level: 개입 수준
 *    - recommended_tone, recommended_pace: 권장 응답 스타일
 *
 * 2. mdl_at_persona_transition_log - 페르소나 전환 기록
 *    - from_persona, to_persona: 전환 전후 페르소나
 *    - trigger_rule_id: 규칙 ID
 *    - transition_type: upgrade, downgrade, lateral, initial
 *
 * 3. mdl_at_intervention_log - 개입 기록
 *    - intervention_type: encouragement, routine_redesign, urgent_contact 등
 *    - intervention_level: 주의, 경고, 긴급
 *    - indicator_type: 5대 지표 유형
 *    - effectiveness_score: 개입 효과
 *
 * 4. mdl_at_attendance_log - 출결 로그
 * 5. mdl_at_student_goals - 학생 목표
 * 6. mdl_at_pomodoro_sessions - 포모도로 세션
 * 7. mdl_at_wrong_notes - 오답노트
 * 8. mdl_at_test_results - 시험 결과
 */
