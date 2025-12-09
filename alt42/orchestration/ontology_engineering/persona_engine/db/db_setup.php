<?php
/**
 * DB Setup - 페르소나 엔진 데이터베이스 설정
 *
 * 이 파일을 실행하여 필요한 테이블을 생성합니다.
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/db_setup.php
 *
 * @package AugmentedTeacher\PersonaEngine\DB
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die("관리자 권한이 필요합니다. [" . __FILE__ . ":" . __LINE__ . "]");
}

$currentFile = __FILE__;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Persona Engine DB Setup</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee;}";
echo ".success{color:#4ade80;}.error{color:#f87171;}.info{color:#60a5fa;}.warn{color:#fbbf24;}";
echo "pre{background:#16213e;padding:10px;border-radius:5px;overflow:auto;}</style></head><body>";
echo "<h1>🔧 Persona Engine DB Setup</h1>";

$tables = [
    'at_agent_messages' => "
        CREATE TABLE IF NOT EXISTS {at_agent_messages} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            source_agent VARCHAR(20) NOT NULL,
            target_agent VARCHAR(20) NOT NULL,
            message_type VARCHAR(50) NOT NULL,
            payload LONGTEXT,
            priority TINYINT(3) DEFAULT 5,
            status VARCHAR(20) DEFAULT 'pending',
            response LONGTEXT,
            created_at BIGINT(10) NOT NULL,
            processed_at BIGINT(10) DEFAULT NULL,
            expires_at BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_target_status (target_agent, status),
            INDEX idx_source_agent (source_agent),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",

    'at_persona_events' => "
        CREATE TABLE IF NOT EXISTS {at_persona_events} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            agent_id VARCHAR(20) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            event_data LONGTEXT,
            user_id BIGINT(10) DEFAULT 0,
            persona_id VARCHAR(50) DEFAULT NULL,
            created_at BIGINT(10) NOT NULL,
            expires_at BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_user_event (user_id, event_type),
            INDEX idx_agent_id (agent_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",

    'at_persona_session' => "
        CREATE TABLE IF NOT EXISTS {at_persona_session} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) NOT NULL,
            agent_id VARCHAR(20) DEFAULT NULL,
            current_persona VARCHAR(50) DEFAULT NULL,
            previous_persona VARCHAR(50) DEFAULT NULL,
            current_situation VARCHAR(10) DEFAULT 'S1',
            interaction_count INT(10) DEFAULT 0,
            session_data LONGTEXT,
            created_at BIGINT(10) NOT NULL,
            updated_at BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX idx_user_agent (user_id, agent_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",

    'at_persona_action_log' => "
        CREATE TABLE IF NOT EXISTS {at_persona_action_log} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            agent_id VARCHAR(20) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            action_data LONGTEXT,
            result LONGTEXT,
            user_id BIGINT(10) DEFAULT 0,
            persona_id VARCHAR(50) DEFAULT NULL,
            success TINYINT(1) DEFAULT 1,
            created_at BIGINT(10) NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_user_action (user_id, action_type),
            INDEX idx_agent_id (agent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",

    'at_response_templates' => "
        CREATE TABLE IF NOT EXISTS {at_response_templates} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            template_id VARCHAR(50) NOT NULL,
            agent_id VARCHAR(20) DEFAULT NULL,
            persona_id VARCHAR(50) DEFAULT NULL,
            template_text LONGTEXT NOT NULL,
            tone VARCHAR(30) DEFAULT 'Professional',
            intervention_type VARCHAR(50) DEFAULT NULL,
            language VARCHAR(10) DEFAULT 'ko',
            is_active TINYINT(1) DEFAULT 1,
            created_at BIGINT(10) NOT NULL,
            updated_at BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_agent_persona (agent_id, persona_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",

    'at_agent_config' => "
        CREATE TABLE IF NOT EXISTS {at_agent_config} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            agent_id VARCHAR(20) NOT NULL,
            config_key VARCHAR(100) NOT NULL,
            config_value LONGTEXT,
            config_type VARCHAR(20) DEFAULT 'string',
            description VARCHAR(255) DEFAULT NULL,
            created_at BIGINT(10) NOT NULL,
            updated_at BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX idx_agent_key (agent_id, config_key),
            INDEX idx_agent_id (agent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

$results = [];
$allSuccess = true;

foreach ($tables as $tableName => $sql) {
    echo "<h3>📋 테이블: {$tableName}</h3>";

    try {
        // 테이블 존재 여부 확인
        $tableExists = $DB->get_manager()->table_exists($tableName);

        if ($tableExists) {
            echo "<p class='info'>✓ 테이블이 이미 존재합니다.</p>";
            $results[$tableName] = 'exists';
        } else {
            // 테이블 생성
            $DB->execute($sql);
            echo "<p class='success'>✓ 테이블 생성 완료!</p>";
            $results[$tableName] = 'created';
        }

    } catch (Exception $e) {
        echo "<p class='error'>✗ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='error'>위치: {$currentFile}:" . __LINE__ . "</p>";
        $results[$tableName] = 'error: ' . $e->getMessage();
        $allSuccess = false;
    }
}

// 결과 요약
echo "<hr><h2>📊 결과 요약</h2>";
echo "<pre>";
foreach ($results as $table => $status) {
    $icon = ($status === 'created' || $status === 'exists') ? '✓' : '✗';
    echo "{$icon} {$table}: {$status}\n";
}
echo "</pre>";

if ($allSuccess) {
    echo "<p class='success'><strong>🎉 모든 테이블 설정 완료!</strong></p>";
} else {
    echo "<p class='warn'><strong>⚠️ 일부 테이블 생성에 문제가 있습니다. 로그를 확인하세요.</strong></p>";
}

// 테이블 상태 확인
echo "<h2>📈 테이블 상태</h2>";
echo "<table border='1' style='border-collapse:collapse;'><tr><th>테이블</th><th>레코드 수</th></tr>";

foreach (array_keys($tables) as $tableName) {
    try {
        $count = $DB->count_records($tableName);
        echo "<tr><td>{$tableName}</td><td>{$count}</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>{$tableName}</td><td class='error'>오류</td></tr>";
    }
}
echo "</table>";

echo "</body></html>";
