<?php
/**
 * Agent19 Persona System 데이터베이스 설치 스크립트
 *
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/persona_system/db/install.php
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Database
 * @version     1.0.0
 * @created     2025-12-03
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die(json_encode([
        'success' => false,
        'error' => 'Admin access required',
        'file' => __FILE__,
        'line' => __LINE__
    ]));
}

header('Content-Type: application/json; charset=utf-8');

$results = [
    'success' => true,
    'tables_created' => [],
    'tables_existing' => [],
    'errors' => []
];

/**
 * 테이블 존재 여부 확인
 */
function tableExists($tableName) {
    global $DB;
    try {
        $DB->get_records_sql("SELECT 1 FROM {{$tableName}} LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * SQL 실행 및 결과 기록
 */
function executeSQL($sql, $tableName, &$results) {
    global $DB;

    if (tableExists($tableName)) {
        $results['tables_existing'][] = $tableName;
        return true;
    }

    try {
        $DB->execute($sql);
        $results['tables_created'][] = $tableName;
        return true;
    } catch (Exception $e) {
        $results['errors'][] = [
            'table' => $tableName,
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ];
        $results['success'] = false;
        return false;
    }
}

// ============================================================
// 테이블 생성 SQL
// ============================================================

// 1. 페르소나 상태 테이블
$sql_persona_state = "
CREATE TABLE IF NOT EXISTS mdl_agent19_persona_state (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    cognitive_code VARCHAR(10) NOT NULL DEFAULT 'C1',
    behavioral_code VARCHAR(10) NOT NULL DEFAULT 'B1',
    emotional_code VARCHAR(10) NOT NULL DEFAULT 'E6',
    composite_code VARCHAR(20) NOT NULL DEFAULT 'C1-B1-E6',
    confidence DECIMAL(5,4) DEFAULT 0.5000,
    last_detection_data LONGTEXT,
    updated_at BIGINT(10) NOT NULL,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_user (user_id),
    KEY idx_composite (composite_code),
    KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 사용자별 현재 페르소나 상태'
";
executeSQL($sql_persona_state, 'agent19_persona_state', $results);

// 2. 페르소나 이력 테이블
$sql_persona_history = "
CREATE TABLE IF NOT EXISTS mdl_agent19_persona_history (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    from_composite VARCHAR(20),
    to_composite VARCHAR(20) NOT NULL,
    trigger_situation VARCHAR(10),
    trigger_data LONGTEXT,
    confidence DECIMAL(5,4),
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_time (user_id, created_at),
    KEY idx_situation (trigger_situation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 페르소나 전환 이력'
";
executeSQL($sql_persona_history, 'agent19_persona_history', $results);

// 3. 컨텍스트 이력 테이블
$sql_context_history = "
CREATE TABLE IF NOT EXISTS mdl_agent19_context_history (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    content_id BIGINT(10),
    situation_code VARCHAR(10) NOT NULL,
    interaction_code VARCHAR(10),
    environment_code VARCHAR(10),
    temporal_code VARCHAR(10),
    sub_context VARCHAR(50),
    behavior_data LONGTEXT,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_time (user_id, created_at),
    KEY idx_content (content_id),
    KEY idx_situation (situation_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 컨텍스트 분석 이력'
";
executeSQL($sql_context_history, 'agent19_context_history', $results);

// 4. 컨텍스트 규칙 테이블
$sql_context_rules = "
CREATE TABLE IF NOT EXISTS mdl_agent19_context_rules (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    situation_code VARCHAR(10) NOT NULL,
    condition_data LONGTEXT NOT NULL,
    action_data LONGTEXT NOT NULL,
    priority INT(5) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at BIGINT(10) NOT NULL,
    updated_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_situation (situation_code),
    KEY idx_active_priority (is_active, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 컨텍스트 감지 규칙'
";
executeSQL($sql_context_rules, 'agent19_context_rules', $results);

// 5. 응답 템플릿 테이블
$sql_response_templates = "
CREATE TABLE IF NOT EXISTS mdl_agent19_response_templates (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    template_key VARCHAR(50) NOT NULL,
    situation_code VARCHAR(10),
    persona_code VARCHAR(20),
    template_data LONGTEXT NOT NULL,
    language VARCHAR(10) DEFAULT 'ko',
    is_active TINYINT(1) DEFAULT 1,
    use_count BIGINT(10) DEFAULT 0,
    created_at BIGINT(10) NOT NULL,
    updated_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_key_situation_persona (template_key, situation_code, persona_code),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 응답 템플릿'
";
executeSQL($sql_response_templates, 'agent19_response_templates', $results);

// 6. 응답 로그 테이블
$sql_response_log = "
CREATE TABLE IF NOT EXISTS mdl_agent19_response_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    content_id BIGINT(10),
    persona_code VARCHAR(20) NOT NULL,
    context_code VARCHAR(10) NOT NULL,
    response_data LONGTEXT,
    behavior_data LONGTEXT,
    confidence DECIMAL(5,4),
    ai_enhanced TINYINT(1) DEFAULT 0,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_time (user_id, created_at),
    KEY idx_content (content_id),
    KEY idx_persona (persona_code),
    KEY idx_context (context_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 API 응답 로그'
";
executeSQL($sql_response_log, 'agent19_response_log', $results);

// 7. 정서 상태 테이블
$sql_emotional_state = "
CREATE TABLE IF NOT EXISTS mdl_agent19_emotional_state (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    state_code VARCHAR(10) NOT NULL,
    confidence DECIMAL(5,4),
    triggers LONGTEXT,
    context_data LONGTEXT,
    detected_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_time (user_id, detected_at),
    KEY idx_state (state_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 정서 상태 감지 기록'
";
executeSQL($sql_emotional_state, 'agent19_emotional_state', $results);

// 8. 오류 패턴 테이블
$sql_error_patterns = "
CREATE TABLE IF NOT EXISTS mdl_agent19_error_patterns (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    content_id BIGINT(10),
    error_type VARCHAR(50) NOT NULL,
    error_count INT(5) DEFAULT 1,
    consecutive_count INT(5) DEFAULT 0,
    context_data LONGTEXT,
    first_occurrence BIGINT(10) NOT NULL,
    last_occurrence BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_content (user_id, content_id),
    KEY idx_error_type (error_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 오류 패턴 추적'
";
executeSQL($sql_error_patterns, 'agent19_error_patterns', $results);

// 9. 개입 기록 테이블
$sql_intervention_log = "
CREATE TABLE IF NOT EXISTS mdl_agent19_intervention_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    user_id BIGINT(10) NOT NULL,
    intervention_type VARCHAR(50) NOT NULL,
    situation_code VARCHAR(10),
    persona_before VARCHAR(20),
    persona_after VARCHAR(20),
    template_id BIGINT(10),
    outcome VARCHAR(20),
    outcome_data LONGTEXT,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user_time (user_id, created_at),
    KEY idx_intervention (intervention_type),
    KEY idx_outcome (outcome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent19 개입 효과 기록'
";
executeSQL($sql_intervention_log, 'agent19_intervention_log', $results);

// ============================================================
// 결과 출력
// ============================================================

$results['summary'] = [
    'total_tables' => 9,
    'created' => count($results['tables_created']),
    'existing' => count($results['tables_existing']),
    'failed' => count($results['errors'])
];

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/*
 * 실행 결과 예시:
 * {
 *   "success": true,
 *   "tables_created": ["agent19_persona_state", "agent19_persona_history", ...],
 *   "tables_existing": [],
 *   "errors": [],
 *   "summary": {
 *     "total_tables": 9,
 *     "created": 9,
 *     "existing": 0,
 *     "failed": 0
 *   }
 * }
 */
