<?php
/**
 * Agent05 Learning Emotion - DB Schema
 *
 * 학습 감정 에이전트의 페르소나 시스템을 위한 DB 스키마 정의 및 마이그레이션
 * 실행 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/db/schema.php
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem\DB
 * @version 1.0
 * @author Claude Code
 * @created 2024-12-02
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
 * Agent05 스키마 관리자
 */
class Agent05SchemaManager {

    /** @var string 현재 파일 경로 */
    private $currentFile;

    /** @var array 실행 결과 */
    private $results = [];

    /** @var string 에이전트 ID */
    private $agentId = 'agent05';

    public function __construct() {
        $this->currentFile = __FILE__;
    }

    /**
     * 모든 테이블 생성
     */
    public function createAllTables(): array {
        $this->results = [];

        // 1. 학습 감정 로그 테이블
        $this->createLearningEmotionLogTable();

        // 2. 감정 전환 로그 테이블
        $this->createEmotionTransitionLogTable();

        // 3. 에이전트 간 감정 공유 테이블
        $this->createAgentEmotionShareTable();

        // 4. 학습 활동 로그 테이블
        $this->createLearningActivityLogTable();

        // 5. 페르소나 응답 로그 테이블
        $this->createPersonaResponseLogTable();

        // 6. 감정 패턴 분석 테이블
        $this->createEmotionPatternTable();

        return $this->results;
    }

    /**
     * 학습 감정 로그 테이블
     * 학생의 학습 중 감정 상태를 기록
     */
    private function createLearningEmotionLogTable(): void {
        $tableName = 'mdl_at_learning_emotion_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            session_id VARCHAR(100) DEFAULT NULL COMMENT '학습 세션 ID',

            -- 감정 정보
            emotion_type ENUM('anxiety', 'frustration', 'confidence', 'curiosity', 'boredom', 'fatigue', 'achievement', 'confusion') NOT NULL COMMENT '감정 유형',
            emotion_intensity ENUM('high', 'medium', 'low') NOT NULL DEFAULT 'medium' COMMENT '감정 강도',
            emotion_score DECIMAL(3,2) NOT NULL DEFAULT 0.50 COMMENT '감정 점수 (0.00~1.00)',

            -- 복합 감정 (JSON)
            secondary_emotions JSON DEFAULT NULL COMMENT '2차 감정 배열 [{type, intensity, score}]',
            complex_emotion_type VARCHAR(50) DEFAULT NULL COMMENT '복합 감정 유형 (anxious_curiosity 등)',

            -- 감지 소스
            detection_source ENUM('keyword', 'pattern', 'emoticon', 'mixed', 'ai') NOT NULL DEFAULT 'mixed',
            detection_confidence DECIMAL(3,2) NOT NULL DEFAULT 0.70 COMMENT '감지 신뢰도',

            -- 컨텍스트
            activity_type VARCHAR(50) DEFAULT NULL COMMENT '학습 활동 유형',
            message_text TEXT DEFAULT NULL COMMENT '원본 메시지 (분석용)',
            url_context VARCHAR(500) DEFAULT NULL COMMENT 'URL 컨텍스트',

            -- 응답 정보
            persona_used VARCHAR(50) DEFAULT NULL COMMENT '사용된 페르소나',
            response_generated TINYINT(1) DEFAULT 0 COMMENT '응답 생성 여부',

            -- 메타
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_user_session (user_id, session_id),
            KEY idx_emotion_type (emotion_type),
            KEY idx_intensity (emotion_intensity),
            KEY idx_activity (activity_type),
            KEY idx_created (created_at),
            KEY idx_user_created (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Agent05 학습 감정 로그'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 감정 전환 로그 테이블
     * 감정 상태 변화를 추적
     */
    private function createEmotionTransitionLogTable(): void {
        $tableName = 'mdl_at_emotion_transition_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            session_id VARCHAR(100) DEFAULT NULL,

            -- 전환 정보
            from_emotion VARCHAR(30) DEFAULT NULL COMMENT '이전 감정',
            from_intensity ENUM('high', 'medium', 'low') DEFAULT NULL,
            to_emotion VARCHAR(30) NOT NULL COMMENT '새 감정',
            to_intensity ENUM('high', 'medium', 'low') NOT NULL,

            -- 전환 분석
            transition_type ENUM('positive', 'negative', 'neutral', 'escalation', 'de-escalation') NOT NULL COMMENT '전환 유형',
            trigger_event VARCHAR(100) DEFAULT NULL COMMENT '트리거 이벤트',
            trigger_data JSON DEFAULT NULL COMMENT '트리거 상세 데이터',

            -- 페르소나 변경
            persona_before VARCHAR(50) DEFAULT NULL,
            persona_after VARCHAR(50) DEFAULT NULL,
            persona_transition_triggered TINYINT(1) DEFAULT 0,

            -- 메타
            duration_seconds INT(10) UNSIGNED DEFAULT NULL COMMENT '이전 감정 유지 시간',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_user_session (user_id, session_id),
            KEY idx_transition (from_emotion, to_emotion),
            KEY idx_type (transition_type),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='감정 전환 이력'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 에이전트 간 감정 공유 테이블
     * 다른 에이전트들과 감정 정보 공유
     */
    private function createAgentEmotionShareTable(): void {
        $tableName = 'mdl_at_agent_emotion_share';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,

            -- 공유 정보
            from_agent VARCHAR(20) NOT NULL DEFAULT 'agent05',
            to_agent VARCHAR(20) NOT NULL COMMENT '수신 에이전트',

            -- 감정 요약
            emotion_summary JSON NOT NULL COMMENT '감정 요약 {primary, secondary, intensity, recommendation}',
            recommended_approach VARCHAR(100) DEFAULT NULL COMMENT '권장 접근법',
            urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',

            -- 상태
            status ENUM('pending', 'delivered', 'acknowledged', 'expired') DEFAULT 'pending',
            delivered_at DATETIME DEFAULT NULL,
            acknowledged_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL COMMENT '만료 시간',

            -- 메타
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_to_agent (to_agent),
            KEY idx_status (status),
            KEY idx_urgency (urgency_level),
            KEY idx_to_agent_status (to_agent, status),
            KEY idx_expires (expires_at),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 간 감정 정보 공유'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 학습 활동 로그 테이블
     * 학생의 학습 활동 유형 기록
     */
    private function createLearningActivityLogTable(): void {
        $tableName = 'mdl_at_learning_activity_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            session_id VARCHAR(100) DEFAULT NULL,

            -- 활동 정보
            activity_type ENUM('concept_understanding', 'type_learning', 'problem_solving', 'error_note', 'qa', 'review', 'pomodoro', 'home_check') NOT NULL,
            activity_score DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '활동 확신도',

            -- 감지 소스
            detected_by_url TINYINT(1) DEFAULT 0,
            detected_by_message TINYINT(1) DEFAULT 0,
            detected_by_session TINYINT(1) DEFAULT 0,
            url_context VARCHAR(500) DEFAULT NULL,

            -- 활동 상세
            duration_seconds INT(10) UNSIGNED DEFAULT NULL COMMENT '활동 지속 시간',
            problems_attempted INT(10) UNSIGNED DEFAULT 0,
            problems_correct INT(10) UNSIGNED DEFAULT 0,

            -- 감정 연계
            primary_emotion VARCHAR(30) DEFAULT NULL,
            emotion_intensity ENUM('high', 'medium', 'low') DEFAULT NULL,

            -- 전환 정보
            previous_activity VARCHAR(50) DEFAULT NULL,
            transition_recommended TINYINT(1) DEFAULT 0,
            transition_reason VARCHAR(255) DEFAULT NULL,

            -- 메타
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ended_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_user_session (user_id, session_id),
            KEY idx_activity (activity_type),
            KEY idx_user_activity (user_id, activity_type),
            KEY idx_started (started_at),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='학습 활동 로그'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 페르소나 응답 로그 테이블
     * 페르소나 기반 응답 기록
     */
    private function createPersonaResponseLogTable(): void {
        $tableName = 'mdl_at_persona_response_log';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(20) NOT NULL DEFAULT 'agent05',
            session_id VARCHAR(100) DEFAULT NULL,

            -- 응답 컨텍스트
            emotion_log_id BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '연결된 감정 로그 ID',
            activity_type VARCHAR(50) DEFAULT NULL,

            -- 페르소나 정보
            persona_used VARCHAR(50) NOT NULL COMMENT '사용된 페르소나',
            persona_selection_reason VARCHAR(255) DEFAULT NULL,
            persona_confidence DECIMAL(3,2) DEFAULT NULL,

            -- 응답 정보
            response_mode ENUM('template', 'ai', 'hybrid') NOT NULL DEFAULT 'template',
            template_id VARCHAR(100) DEFAULT NULL COMMENT '사용된 템플릿 ID',
            response_text TEXT DEFAULT NULL COMMENT '생성된 응답 텍스트',
            response_metadata JSON DEFAULT NULL COMMENT '응답 메타데이터',

            -- 품질 지표
            response_length INT(10) UNSIGNED DEFAULT NULL,
            generation_time_ms INT(10) UNSIGNED DEFAULT NULL COMMENT '생성 소요 시간(ms)',

            -- 피드백 (선택적)
            user_feedback_score TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '사용자 피드백 (1~5)',
            user_feedback_text VARCHAR(500) DEFAULT NULL,

            -- 메타
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_user_agent (user_id, agent_id),
            KEY idx_emotion_log (emotion_log_id),
            KEY idx_persona (persona_used),
            KEY idx_mode (response_mode),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='페르소나 응답 로그'";

        $this->executeSQL($sql, $tableName);
    }

    /**
     * 감정 패턴 분석 테이블
     * 학생별 감정 패턴 요약
     */
    private function createEmotionPatternTable(): void {
        $tableName = 'mdl_at_emotion_pattern';

        if ($this->tableExists($tableName)) {
            $this->results[] = "✓ {$tableName} - 이미 존재함";
            return;
        }

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,

            -- 기간
            analysis_period ENUM('daily', 'weekly', 'monthly') NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,

            -- 감정 분포
            emotion_distribution JSON NOT NULL COMMENT '감정별 빈도 {anxiety: 10, confidence: 25, ...}',
            dominant_emotion VARCHAR(30) NOT NULL COMMENT '주요 감정',
            dominant_intensity ENUM('high', 'medium', 'low') NOT NULL,

            -- 통계
            total_emotion_records INT(10) UNSIGNED NOT NULL DEFAULT 0,
            average_emotion_score DECIMAL(3,2) DEFAULT NULL,
            emotion_volatility DECIMAL(3,2) DEFAULT NULL COMMENT '감정 변동성 (0~1)',

            -- 활동별 감정
            activity_emotion_map JSON DEFAULT NULL COMMENT '활동별 주요 감정',

            -- 전환 패턴
            common_transitions JSON DEFAULT NULL COMMENT '빈번한 감정 전환 패턴',
            escalation_count INT(10) UNSIGNED DEFAULT 0 COMMENT '감정 악화 횟수',
            de_escalation_count INT(10) UNSIGNED DEFAULT 0 COMMENT '감정 완화 횟수',

            -- 권장 사항
            recommended_personas JSON DEFAULT NULL COMMENT '권장 페르소나 목록',
            intervention_needed TINYINT(1) DEFAULT 0,
            intervention_type VARCHAR(50) DEFAULT NULL,

            -- 메타
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY uk_user_period (user_id, analysis_period, period_start),
            KEY idx_dominant (dominant_emotion),
            KEY idx_period (period_start, period_end),
            KEY idx_intervention (intervention_needed),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='감정 패턴 분석 요약'";

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
    public function getAgent05Tables(): array {
        return [
            'mdl_at_learning_emotion_log',
            'mdl_at_emotion_transition_log',
            'mdl_at_agent_emotion_share',
            'mdl_at_learning_activity_log',
            'mdl_at_persona_response_log',
            'mdl_at_emotion_pattern'
        ];
    }

    /**
     * 테이블 상태 확인
     */
    public function checkTablesStatus(): array {
        $status = [];

        foreach ($this->getAgent05Tables() as $table) {
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
$schemaManager = new Agent05SchemaManager();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Agent05 Schema Manager</title>";
echo "<style>
    body { font-family: 'Noto Sans KR', Arial, sans-serif; padding: 20px; background: #f0f4f8; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    h1 { color: #2d3748; border-bottom: 3px solid #667eea; padding-bottom: 15px; }
    h2 { color: #4a5568; margin-top: 30px; }
    .result { padding: 12px 15px; margin: 8px 0; border-radius: 6px; font-size: 14px; }
    .success { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
    .error { background: #fed7d7; color: #742a2a; border-left: 4px solid #f56565; }
    .info { background: #bee3f8; color: #2a4365; border-left: 4px solid #4299e1; }
    .btn { display: inline-block; padding: 12px 24px; margin: 8px; border-radius: 6px; text-decoration: none; color: white; font-weight: 500; transition: transform 0.2s; }
    .btn:hover { transform: translateY(-2px); }
    .btn-create { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .btn-status { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .btn-danger { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { background: #edf2f7; font-weight: 600; color: #4a5568; }
    .exists { color: #38a169; font-weight: 500; }
    .not-exists { color: #e53e3e; font-weight: 500; }
    .agent-badge { display: inline-block; background: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-left: 10px; }
    .table-info { font-size: 12px; color: #718096; margin-top: 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🎭 Agent05 Learning Emotion <span class='agent-badge'>Persona System</span></h1>";
echo "<p class='table-info'>학습 감정 분석을 위한 DB 스키마 관리</p>";

switch ($action) {
    case 'create':
        echo "<h2>📦 테이블 생성 결과</h2>";
        $results = $schemaManager->createAllTables();
        foreach ($results as $result) {
            $class = strpos($result, '✓') !== false ? 'success' : 'error';
            echo "<div class='result {$class}'>{$result}</div>";
        }
        break;

    case 'status':
    default:
        echo "<h2>📊 테이블 상태</h2>";
        $status = $schemaManager->checkTablesStatus();
        echo "<table>";
        echo "<tr><th>테이블명</th><th>존재 여부</th><th>레코드 수</th><th>설명</th></tr>";

        $descriptions = [
            'mdl_at_learning_emotion_log' => '학습 중 감정 상태 기록',
            'mdl_at_emotion_transition_log' => '감정 전환 이력',
            'mdl_at_agent_emotion_share' => '에이전트 간 감정 공유',
            'mdl_at_learning_activity_log' => '학습 활동 유형 기록',
            'mdl_at_persona_response_log' => '페르소나 응답 기록',
            'mdl_at_emotion_pattern' => '감정 패턴 분석 요약'
        ];

        foreach ($status as $table => $info) {
            $existsClass = $info['exists'] ? 'exists' : 'not-exists';
            $existsText = $info['exists'] ? '✓ 존재' : '✗ 없음';
            $desc = $descriptions[$table] ?? '-';
            echo "<tr>";
            echo "<td><code>{$table}</code></td>";
            echo "<td class='{$existsClass}'>{$existsText}</td>";
            echo "<td>{$info['count']}</td>";
            echo "<td class='table-info'>{$desc}</td>";
            echo "</tr>";
        }
        echo "</table>";
        break;
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='?action=status' class='btn btn-status'>📊 상태 확인</a>";
echo "<a href='?action=create' class='btn btn-create'>📦 테이블 생성</a>";
echo "</div>";

echo "<div class='result info' style='margin-top: 20px;'>";
echo "<strong>📁 파일 위치:</strong> " . __FILE__ . "<br>";
echo "<strong>🕐 현재 시각:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>🔗 URL:</strong> <a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/db/schema.php'>schema.php</a>";
echo "</div>";

echo "</div></body></html>";

/*
 * =====================================
 * 생성되는 테이블 요약
 * =====================================
 *
 * 1. mdl_at_learning_emotion_log
 *    - emotion_type: anxiety, frustration, confidence, curiosity, boredom, fatigue, achievement, confusion
 *    - emotion_intensity: high, medium, low
 *    - detection_source: keyword, pattern, emoticon, mixed, ai
 *    - secondary_emotions: JSON 배열로 복합 감정 저장
 *
 * 2. mdl_at_emotion_transition_log
 *    - from_emotion → to_emotion: 감정 변화 추적
 *    - transition_type: positive, negative, neutral, escalation, de-escalation
 *    - persona 변경 연계 기록
 *
 * 3. mdl_at_agent_emotion_share
 *    - 에이전트 간 감정 정보 공유
 *    - emotion_summary: JSON으로 감정 요약 전송
 *    - urgency_level: 긴급도 표시
 *
 * 4. mdl_at_learning_activity_log
 *    - activity_type: 8가지 학습 활동 유형
 *    - 활동별 감정 연계 기록
 *
 * 5. mdl_at_persona_response_log
 *    - 사용된 페르소나 및 응답 기록
 *    - response_mode: template, ai, hybrid
 *
 * 6. mdl_at_emotion_pattern
 *    - 기간별 감정 패턴 분석 요약
 *    - 권장 페르소나 및 개입 필요성
 */
