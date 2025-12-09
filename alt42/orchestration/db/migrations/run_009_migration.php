<?php
/**
 * Migration Runner: 009_create_agent_communication_tables.sql
 * 에이전트 간 통신 시스템 테이블 생성
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore\Migrations
 * @version     1.0.2
 * @created     2025-12-03
 * @modified    2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_009_migration.php
 *
 * 실행 방법:
 * 1. 브라우저: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_009_migration.php
 * 2. CLI: php run_009_migration.php
 *
 * 변경 이력:
 * - v1.0.2: 전체 실행 로직 AgentDataLayer PDO 기반으로 변환 (테이블 생성, 데이터 삽입, 검증)
 * - v1.0.1: JSON → LONGTEXT 변경 (MySQL 5.7 호환성), AgentDataLayer 사용
 */

// ============================================================================
// 환경 설정
// ============================================================================

// 오류 표시 (개발 환경)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CLI vs 웹 환경 감지
$is_cli = php_sapi_name() === 'cli';
$newline = $is_cli ? "\n" : "<br>\n";

// 웹 환경에서 pre 태그 시작
if (!$is_cli) {
    echo "<pre style='font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px;'>";
}

/**
 * 출력 헬퍼 함수
 */
function output($message, $type = 'info') {
    global $is_cli, $newline;

    $prefix = '';
    switch ($type) {
        case 'success': $prefix = "✓ "; break;
        case 'error':   $prefix = "✗ "; break;
        case 'warning': $prefix = "⚠ "; break;
        case 'header':  $prefix = "=== "; break;
        default:        $prefix = "  "; break;
    }

    $suffix = ($type === 'header') ? " ===" . $newline : $newline;

    echo $prefix . $message . $suffix;
    flush();
}

// ============================================================================
// AgentDataLayer 로드
// ============================================================================

require_once(__DIR__ . '/../../api/database/agent_data_layer.php');
use ALT42\Database\AgentDataLayer;

// ============================================================================
// Moodle 연결 (설정 로드용)
// ============================================================================

output("Migration 009: Agent Communication Tables", "header");
output("시작 시간: " . date('Y-m-d H:i:s'));

$moodle_config = '/home/moodle/public_html/moodle/config.php';
$moodle_available = file_exists($moodle_config);

if (!$moodle_available) {
    output("Moodle config 파일을 찾을 수 없습니다: {$moodle_config}", "error");
    output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
    exit(1);
}

require_once($moodle_config);
global $CFG;

output("Moodle 설정 로드 성공", "success");

// PDO 연결 테스트
try {
    $conn = AgentDataLayer::getConnection();
    output("PDO 데이터베이스 연결 성공", "success");
} catch (Exception $e) {
    output("PDO 연결 실패: " . $e->getMessage(), "error");
    output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
    exit(1);
}

// ============================================================================
// 테이블 정의 (MySQL 5.7 호환: JSON → LONGTEXT)
// ============================================================================

$tables = [
    // 1. 메시지 큐 테이블
    'mdl_at_agent_messages' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_messages` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `message_id` VARCHAR(64) NOT NULL COMMENT 'UUID v4 형식의 고유 메시지 ID',
            `from_agent` TINYINT(3) UNSIGNED NOT NULL COMMENT '발신 에이전트 번호 (1-21)',
            `to_agent` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '수신 에이전트 번호 (1-21, 0=브로드캐스트)',
            `message_type` VARCHAR(50) NOT NULL COMMENT '메시지 유형',
            `priority` TINYINT(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT '우선순위 (1=최고, 10=최저)',
            `payload` LONGTEXT COMMENT '메시지 페이로드 (JSON 형식)',
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '상태: pending, processing, processed, failed, expired',
            `checksum` VARCHAR(64) COMMENT 'SHA256 페이로드 체크섬',
            `retry_count` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            `expires_at` DATETIME NOT NULL,
            `timecreated` INT(10) UNSIGNED NOT NULL,
            `timeprocessed` INT(10) UNSIGNED DEFAULT NULL,
            UNIQUE KEY `uk_message_id` (`message_id`),
            KEY `idx_to_agent_status` (`to_agent`, `status`),
            KEY `idx_from_agent` (`from_agent`),
            KEY `idx_priority_created` (`priority`, `timecreated`),
            KEY `idx_message_type` (`message_type`),
            KEY `idx_status_expires` (`status`, `expires_at`),
            KEY `idx_created` (`timecreated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 간 메시지 큐'
    ",

    // 2. 페르소나 상태 테이블
    'mdl_at_agent_persona_state' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_persona_state` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` BIGINT(10) UNSIGNED NOT NULL,
            `nagent` TINYINT(3) UNSIGNED NOT NULL,
            `persona_code` VARCHAR(20) NOT NULL,
            `confidence` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            `context_data` LONGTEXT COMMENT '컨텍스트 데이터 (JSON 형식)',
            `timecreated` INT(10) UNSIGNED NOT NULL,
            `timemodified` INT(10) UNSIGNED NOT NULL,
            UNIQUE KEY `uk_user_agent` (`user_id`, `nagent`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_nagent` (`nagent`),
            KEY `idx_persona_code` (`persona_code`),
            KEY `idx_modified` (`timemodified`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트별 사용자 페르소나 상태'
    ",

    // 3. 전환 이력 테이블
    'mdl_at_agent_transitions' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_transitions` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` BIGINT(10) UNSIGNED NOT NULL,
            `nagent` TINYINT(3) UNSIGNED NOT NULL,
            `from_persona` VARCHAR(20) NOT NULL,
            `to_persona` VARCHAR(20) NOT NULL,
            `trigger_type` VARCHAR(50) NOT NULL,
            `confidence` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            `context_snapshot` LONGTEXT COMMENT '전환 시점 컨텍스트 (JSON 형식)',
            `timecreated` INT(10) UNSIGNED NOT NULL,
            KEY `idx_user_agent` (`user_id`, `nagent`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_nagent` (`nagent`),
            KEY `idx_trigger_type` (`trigger_type`),
            KEY `idx_created` (`timecreated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='페르소나 전환 이력'
    ",

    // 4. 하트비트 테이블
    'mdl_at_agent_heartbeat' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_heartbeat` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nagent` TINYINT(3) UNSIGNED NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT '상태: active, inactive, maintenance',
            `last_activity` INT(10) UNSIGNED NOT NULL,
            `messages_processed` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `errors_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `metadata` LONGTEXT COMMENT '추가 메타데이터 (JSON 형식)',
            `timecreated` INT(10) UNSIGNED NOT NULL,
            `timemodified` INT(10) UNSIGNED NOT NULL,
            UNIQUE KEY `uk_nagent` (`nagent`),
            KEY `idx_status` (`status`),
            KEY `idx_last_activity` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 하트비트 및 가용성'
    ",

    // 5. 구독 테이블
    'mdl_at_agent_subscriptions' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_subscriptions` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nagent` TINYINT(3) UNSIGNED NOT NULL,
            `message_type` VARCHAR(50) NOT NULL,
            `handler_class` VARCHAR(255) COMMENT '핸들러 클래스명',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `timecreated` INT(10) UNSIGNED NOT NULL,
            UNIQUE KEY `uk_agent_type` (`nagent`, `message_type`),
            KEY `idx_message_type` (`message_type`),
            KEY `idx_nagent` (`nagent`),
            KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 메시지 구독 관리'
    ",

    // 6. 요청-응답 테이블
    'mdl_at_agent_request_response' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_request_response` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `request_id` VARCHAR(64) NOT NULL,
            `request_message_id` VARCHAR(64) NOT NULL,
            `response_message_id` VARCHAR(64) DEFAULT NULL,
            `from_agent` TINYINT(3) UNSIGNED NOT NULL,
            `to_agent` TINYINT(3) UNSIGNED NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'waiting' COMMENT '상태: waiting, responded, timeout, error',
            `timeout_at` INT(10) UNSIGNED NOT NULL,
            `timecreated` INT(10) UNSIGNED NOT NULL,
            `timeresponded` INT(10) UNSIGNED DEFAULT NULL,
            UNIQUE KEY `uk_request_id` (`request_id`),
            KEY `idx_status` (`status`),
            KEY `idx_from_agent` (`from_agent`),
            KEY `idx_to_agent` (`to_agent`),
            KEY `idx_timeout` (`timeout_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='요청-응답 패턴 매핑'
    ",

    // 7. 통신 로그 테이블
    'mdl_at_agent_communication_log' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_communication_log` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `message_id` VARCHAR(64) NOT NULL,
            `action` VARCHAR(30) NOT NULL,
            `from_agent` TINYINT(3) UNSIGNED NOT NULL,
            `to_agent` TINYINT(3) UNSIGNED NOT NULL,
            `status` VARCHAR(20) NOT NULL,
            `details` LONGTEXT COMMENT '상세 정보 (JSON 형식)',
            `timecreated` INT(10) UNSIGNED NOT NULL,
            KEY `idx_message_id` (`message_id`),
            KEY `idx_from_agent` (`from_agent`),
            KEY `idx_to_agent` (`to_agent`),
            KEY `idx_action` (`action`),
            KEY `idx_created` (`timecreated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 통신 로그'
    ",

    // 8. 협력 관계 테이블
    'mdl_at_agent_collaboration' => "
        CREATE TABLE IF NOT EXISTS `mdl_at_agent_collaboration` (
            `id` BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `from_agent` TINYINT(3) UNSIGNED NOT NULL,
            `to_agent` TINYINT(3) UNSIGNED NOT NULL,
            `collaboration_type` VARCHAR(50) NOT NULL,
            `is_bidirectional` TINYINT(1) NOT NULL DEFAULT 0,
            `priority` TINYINT(3) UNSIGNED NOT NULL DEFAULT 5,
            `description` TEXT COMMENT '협력 관계 설명',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `timecreated` INT(10) UNSIGNED NOT NULL,
            UNIQUE KEY `uk_collaboration` (`from_agent`, `to_agent`, `collaboration_type`),
            KEY `idx_from_agent` (`from_agent`),
            KEY `idx_to_agent` (`to_agent`),
            KEY `idx_type` (`collaboration_type`),
            KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='에이전트 협력 관계 정의'
    "
];

// ============================================================================
// 헬퍼 함수: 테이블 존재 여부 확인 (PDO 기반)
// ============================================================================

/**
 * 테이블 존재 여부 확인 (INFORMATION_SCHEMA 사용)
 * @param string $table_name 테이블명
 * @return bool
 */
function table_exists_pdo($table_name) {
    global $CFG;
    try {
        // INFORMATION_SCHEMA를 사용한 정확한 테이블 존재 확인
        $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        $stmt = ALT42\Database\AgentDataLayer::executeQuery($sql, [$CFG->dbname, $table_name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    } catch (Exception $e) {
        output("테이블 확인 오류 ({$table_name}): " . $e->getMessage(), "error");
        return false;
    }
}

/**
 * 테이블 레코드 수 확인
 * @param string $table_name 테이블명
 * @return int
 */
function count_records_pdo($table_name) {
    try {
        $sql = "SELECT COUNT(*) as cnt FROM `{$table_name}`";
        $stmt = ALT42\Database\AgentDataLayer::executeQuery($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

// ============================================================================
// 테이블 생성 실행 (AgentDataLayer PDO 사용)
// ============================================================================

output("테이블 생성 시작...", "header");

$success_count = 0;
$error_count = 0;
$skip_count = 0;

foreach ($tables as $table_name => $create_sql) {
    output("처리 중: {$table_name}...");

    try {
        // 테이블 존재 여부 확인 (PDO 기반)
        $exists_before = table_exists_pdo($table_name);
        output("  → 테이블 존재 여부: " . ($exists_before ? "예" : "아니오"));

        if ($exists_before) {
            output("{$table_name} - 이미 존재함 (스킵)", "warning");
            $skip_count++;
            continue;
        }

        // AgentDataLayer를 통한 DDL 실행
        output("  → CREATE TABLE 실행 중...");
        AgentDataLayer::executeQuery($create_sql);
        output("  → CREATE TABLE 실행 완료");

        // 생성 확인 (잠시 대기 후)
        usleep(100000); // 100ms 대기
        $exists_after = table_exists_pdo($table_name);
        output("  → 생성 확인: " . ($exists_after ? "성공" : "실패"));

        if ($exists_after) {
            output("{$table_name} - 생성 완료", "success");
            $success_count++;
        } else {
            output("{$table_name} - 생성 실패 (확인 불가)", "error");
            output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
            $error_count++;
        }

    } catch (Exception $e) {
        output("{$table_name} - 오류: " . $e->getMessage(), "error");
        output("SQL 길이: " . strlen($create_sql) . " 바이트");
        output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
        $error_count++;
    }
}

// ============================================================================
// 초기 데이터 삽입 (AgentDataLayer PDO 사용)
// ============================================================================

output("초기 데이터 삽입...", "header");

// 하트비트 초기 데이터 (21개 에이전트)
try {
    $now = time();
    $heartbeat_count = count_records_pdo('mdl_at_agent_heartbeat');

    if ($heartbeat_count == 0) {
        $insert_sql = "INSERT INTO `mdl_at_agent_heartbeat`
            (`nagent`, `status`, `last_activity`, `messages_processed`, `errors_count`, `timecreated`, `timemodified`)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        for ($i = 1; $i <= 21; $i++) {
            AgentDataLayer::executeQuery($insert_sql, [$i, 'active', $now, 0, 0, $now, $now]);
        }
        output("에이전트 하트비트 초기화 (21개)", "success");
    } else {
        output("에이전트 하트비트 - 이미 데이터 존재 ({$heartbeat_count}개)", "warning");
    }
} catch (Exception $e) {
    output("하트비트 초기화 오류: " . $e->getMessage(), "error");
    output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
}

// 협력 관계 초기 데이터
$collaborations = [
    ['from' => 5, 'to' => 8, 'type' => 'frustration_to_calmness', 'priority' => 2,
     'desc' => 'Agent05(학습감정)가 좌절 감지 시 Agent08(평온도)에 평온화 모드 활성화 요청'],
    ['from' => 9, 'to' => 0, 'type' => 'dropout_broadcast', 'priority' => 1,
     'desc' => 'Agent09(학습관리)가 이탈 위험 감지 시 전체 에이전트에 브로드캐스트'],
    ['from' => 3, 'to' => 9, 'type' => 'diagnosis_to_planning', 'priority' => 3,
     'desc' => 'Agent03(목표분석)이 진단 완료 후 Agent09(학습관리)에 학습 계획 생성 요청'],
    ['from' => 20, 'to' => 21, 'type' => 'preparation_to_execution', 'priority' => 2,
     'desc' => 'Agent20(개입준비)가 준비 완료 후 Agent21(개입실행)에 실행 요청'],
    ['from' => 13, 'to' => 0, 'type' => 'dropout_risk_broadcast', 'priority' => 1,
     'desc' => 'Agent13(학습이탈)이 이탈 위험 감지 시 전체 알림'],
    ['from' => 5, 'to' => 13, 'type' => 'emotion_to_dropout', 'priority' => 3,
     'desc' => 'Agent05(학습감정)이 부정적 감정 지속 시 Agent13(학습이탈)에 알림'],
    ['from' => 8, 'to' => 5, 'type' => 'calmness_feedback', 'priority' => 4,
     'desc' => 'Agent08(평온도)이 평온화 완료 후 Agent05(학습감정)에 피드백']
];

try {
    $collab_count = count_records_pdo('mdl_at_agent_collaboration');

    if ($collab_count == 0) {
        $now = time();
        $insert_sql = "INSERT INTO `mdl_at_agent_collaboration`
            (`from_agent`, `to_agent`, `collaboration_type`, `is_bidirectional`, `priority`, `description`, `is_active`, `timecreated`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        foreach ($collaborations as $collab) {
            AgentDataLayer::executeQuery($insert_sql, [
                $collab['from'],
                $collab['to'],
                $collab['type'],
                0,
                $collab['priority'],
                $collab['desc'],
                1,
                $now
            ]);
        }
        output("에이전트 협력 관계 초기화 (" . count($collaborations) . "개)", "success");
    } else {
        output("에이전트 협력 관계 - 이미 데이터 존재 ({$collab_count}개)", "warning");
    }
} catch (Exception $e) {
    output("협력 관계 초기화 오류: " . $e->getMessage(), "error");
    output("파일 위치: " . __FILE__ . ":" . __LINE__, "error");
}

// ============================================================================
// 결과 요약
// ============================================================================

output("Migration 009 완료", "header");
output("생성: {$success_count}개 테이블", "success");
output("스킵: {$skip_count}개 테이블 (이미 존재)");
if ($error_count > 0) {
    output("오류: {$error_count}개 테이블", "error");
}
output("완료 시간: " . date('Y-m-d H:i:s'));

// ============================================================================
// 테이블 검증 (AgentDataLayer PDO 사용)
// ============================================================================

output("테이블 검증...", "header");

$all_tables = [
    'mdl_at_agent_messages',
    'mdl_at_agent_persona_state',
    'mdl_at_agent_transitions',
    'mdl_at_agent_heartbeat',
    'mdl_at_agent_subscriptions',
    'mdl_at_agent_request_response',
    'mdl_at_agent_communication_log',
    'mdl_at_agent_collaboration'
];

$verified = 0;
foreach ($all_tables as $table) {
    if (table_exists_pdo($table)) {
        $count = count_records_pdo($table);
        output("{$table}: 존재함 ({$count}개 레코드)", "success");
        $verified++;
    } else {
        output("{$table}: 존재하지 않음", "error");
    }
}

output("검증 결과: {$verified}/" . count($all_tables) . " 테이블", $verified == count($all_tables) ? "success" : "error");

// ============================================================================
// HTML 래퍼 (웹 환경)
// ============================================================================

if (!$is_cli) {
    echo "</pre>";
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 생성된 테이블 목록:
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 1. mdl_at_agent_messages        - 에이전트 간 메시지 큐
 * 2. mdl_at_agent_persona_state   - 사용자별 페르소나 상태
 * 3. mdl_at_agent_transitions     - 페르소나 전환 이력
 * 4. mdl_at_agent_heartbeat       - 에이전트 하트비트/가용성
 * 5. mdl_at_agent_subscriptions   - 메시지 구독 관리
 * 6. mdl_at_agent_request_response - 요청-응답 패턴
 * 7. mdl_at_agent_communication_log - 통신 로그
 * 8. mdl_at_agent_collaboration   - 에이전트 협력 관계
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
