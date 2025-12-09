<?php
/**
 * PersonaEngine DB 설치 스크립트
 *
 * 페르소나 엔진에 필요한 DB 테이블 생성
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/install.php
 *
 * @package AugmentedTeacher\PersonaEngine\DB
 * @version 1.0
 * @author Claude Code
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$currentFile = __FILE__;

// 관리자 권한 확인 (보안)
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die("[{$currentFile}:" . __LINE__ . "] 관리자 권한이 필요합니다.");
}

$results = [];

/**
 * 테이블 생성 실행
 */
function createTable($name, $sql) {
    global $DB, $currentFile, $results;
    
    try {
        // 테이블 존재 여부 확인
        $dbman = $DB->get_manager();
        $table = new xmldb_table($name);
        
        if ($dbman->table_exists($table)) {
            $results[] = "⚠️ 테이블 '{$name}' 이미 존재합니다.";
            return true;
        }
        
        // 직접 SQL 실행 (Moodle xmldb보다 명확)
        $DB->execute($sql);
        $results[] = "✅ 테이블 '{$name}' 생성 완료";
        return true;
        
    } catch (Exception $e) {
        $results[] = "❌ 테이블 '{$name}' 생성 실패: " . $e->getMessage();
        error_log("[PersonaEngine DB ERROR] {$currentFile}:" . __LINE__ . " - " . $e->getMessage());
        return false;
    }
}

/**
 * 인덱스 생성
 */
function createIndex($table, $indexName, $columns) {
    global $DB, $results;
    
    try {
        $sql = "CREATE INDEX {$indexName} ON {{$table}} ({$columns})";
        $DB->execute($sql);
        $results[] = "✅ 인덱스 '{$indexName}' 생성 완료";
        return true;
    } catch (Exception $e) {
        // 인덱스 이미 존재하는 경우 무시
        if (strpos($e->getMessage(), 'Duplicate') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = "⚠️ 인덱스 '{$indexName}' 이미 존재합니다.";
            return true;
        }
        $results[] = "❌ 인덱스 '{$indexName}' 생성 실패: " . $e->getMessage();
        return false;
    }
}

// =====================================================
// 테이블 1: at_agent_persona_state (페르소나 상태)
// =====================================================
$sql_persona_state = "
CREATE TABLE {at_agent_persona_state} (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    agent_id VARCHAR(20) NOT NULL,
    persona_id VARCHAR(50) NOT NULL,
    state_data LONGTEXT,
    version INT(10) NOT NULL DEFAULT 1,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_user_agent (userid, agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
createTable('at_agent_persona_state', $sql_persona_state);

// 인덱스 추가
createIndex('at_agent_persona_state', 'idx_aps_userid', 'userid');
createIndex('at_agent_persona_state', 'idx_aps_agent', 'agent_id');
createIndex('at_agent_persona_state', 'idx_aps_persona', 'persona_id');
createIndex('at_agent_persona_state', 'idx_aps_modified', 'timemodified');

// =====================================================
// 테이블 2: at_agent_messages (에이전트 간 메시지)
// =====================================================
$sql_messages = "
CREATE TABLE {at_agent_messages} (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    message_id VARCHAR(100) NOT NULL,
    from_agent VARCHAR(20) NOT NULL,
    to_agent VARCHAR(20) NOT NULL,
    message_type VARCHAR(50) NOT NULL,
    priority TINYINT(3) NOT NULL DEFAULT 3,
    payload LONGTEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    checksum VARCHAR(64),
    protocol_version VARCHAR(10) NOT NULL DEFAULT '1.0.0',
    error_message TEXT,
    retry_count TINYINT(3) NOT NULL DEFAULT 0,
    timecreated BIGINT(10) NOT NULL,
    timeprocessed BIGINT(10),
    PRIMARY KEY (id),
    UNIQUE KEY uk_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
createTable('at_agent_messages', $sql_messages);

// 인덱스 추가
createIndex('at_agent_messages', 'idx_am_to_agent', 'to_agent');
createIndex('at_agent_messages', 'idx_am_status', 'status');
createIndex('at_agent_messages', 'idx_am_type', 'message_type');
createIndex('at_agent_messages', 'idx_am_priority', 'priority');
createIndex('at_agent_messages', 'idx_am_created', 'timecreated');
createIndex('at_agent_messages', 'idx_am_to_status', 'to_agent, status');

// =====================================================
// 테이블 3: at_persona_rules (페르소나 규칙 정의)
// =====================================================
$sql_rules = "
CREATE TABLE {at_persona_rules} (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    agent_id VARCHAR(20) NOT NULL,
    persona_id VARCHAR(50) NOT NULL,
    rule_type VARCHAR(50) NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    conditions LONGTEXT,
    actions LONGTEXT,
    priority INT(5) NOT NULL DEFAULT 100,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
createTable('at_persona_rules', $sql_rules);

createIndex('at_persona_rules', 'idx_pr_agent', 'agent_id');
createIndex('at_persona_rules', 'idx_pr_persona', 'persona_id');
createIndex('at_persona_rules', 'idx_pr_enabled', 'enabled');

// =====================================================
// 테이블 4: at_persona_history (페르소나 변경 이력)
// =====================================================
$sql_history = "
CREATE TABLE {at_persona_history} (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    agent_id VARCHAR(20) NOT NULL,
    from_persona VARCHAR(50),
    to_persona VARCHAR(50) NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_data LONGTEXT,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
createTable('at_persona_history', $sql_history);

createIndex('at_persona_history', 'idx_ph_userid', 'userid');
createIndex('at_persona_history', 'idx_ph_agent', 'agent_id');
createIndex('at_persona_history', 'idx_ph_created', 'timecreated');

// =====================================================
// 결과 출력
// =====================================================
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PersonaEngine DB 설치</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .result { margin: 5px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 PersonaEngine DB 설치 결과</h1>
    <p>실행 시간: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <h2>테이블 및 인덱스 생성</h2>
    <?php foreach ($results as $result): ?>
        <?php 
            $class = 'result ';
            if (strpos($result, '✅') !== false) $class .= 'success';
            elseif (strpos($result, '⚠️') !== false) $class .= 'warning';
            else $class .= 'error';
        ?>
        <div class="<?php echo $class; ?>"><?php echo $result; ?></div>
    <?php endforeach; ?>
    
    <h2>생성된 테이블 구조</h2>
    <ul>
        <li><strong>at_agent_persona_state</strong>: 에이전트별 사용자 페르소나 상태</li>
        <li><strong>at_agent_messages</strong>: 에이전트 간 통신 메시지 큐</li>
        <li><strong>at_persona_rules</strong>: DB 기반 페르소나 규칙 (선택적)</li>
        <li><strong>at_persona_history</strong>: 페르소나 변경 이력</li>
    </ul>
    
    <h2>다음 단계</h2>
    <ol>
        <li>에이전트별 persona_system 폴더 생성</li>
        <li>rules.yaml 파일로 페르소나 정의</li>
        <li>PersonaEngine 클래스 상속하여 에이전트 구현</li>
    </ol>
    
    <p><a href="/moodle/local/augmented_teacher/alt42/orchestration/">← 오케스트레이션 메인으로</a></p>
</body>
</html>
<?php

/*
 * 생성되는 DB 테이블 요약:
 * 
 * 1. at_agent_persona_state
 *    - userid (BIGINT): 사용자 ID
 *    - agent_id (VARCHAR 20): 에이전트 ID (agent01~agent21)
 *    - persona_id (VARCHAR 50): 현재 페르소나 ID
 *    - state_data (LONGTEXT): JSON 상태 데이터
 *    - version (INT): 버전 (충돌 감지용)
 *    - timecreated, timemodified (BIGINT): 시간
 * 
 * 2. at_agent_messages
 *    - message_id (VARCHAR 100): 고유 메시지 ID
 *    - from_agent, to_agent (VARCHAR 20): 발신/수신 에이전트
 *    - message_type (VARCHAR 50): 메시지 타입
 *    - priority (TINYINT): 우선순위 1~5
 *    - payload (LONGTEXT): JSON 페이로드
 *    - status (VARCHAR 20): pending/processed/failed/invalid
 *    - checksum (VARCHAR 64): SHA256 무결성 검증
 *    - retry_count (TINYINT): 재시도 횟수
 * 
 * 3. at_persona_rules
 *    - agent_id, persona_id: 에이전트/페르소나
 *    - rule_type, rule_name: 규칙 분류
 *    - conditions, actions: JSON 조건/액션
 *    - priority, enabled: 우선순위/활성화
 * 
 * 4. at_persona_history
 *    - userid, agent_id: 사용자/에이전트
 *    - from_persona, to_persona: 변경 전/후 페르소나
 *    - trigger_type, trigger_data: 변경 트리거
 */
