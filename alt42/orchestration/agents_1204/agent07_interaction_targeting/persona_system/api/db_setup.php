<?php
/**
 * Agent07 Persona System - Database Setup
 *
 * 필요한 DB 테이블 생성/마이그레이션 스크립트
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/api/db_setup.php
 *
 * Tables:
 * - mdl_agent07_persona_log: 페르소나 식별 로그
 * - mdl_agent07_response_log: 응답 로그
 * - mdl_agent07_context_log: 컨텍스트 로그
 * - mdl_agent07_user_state: 사용자 상태
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 관리자만 접근 가능
require_login();
if (!is_siteadmin()) {
    die("Administrator access required.");
}

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Agent07 DB Setup</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h1>Agent07 Persona System - Database Setup</h1>";

$tables = array();
$errors = array();

// 1. mdl_agent07_persona_log
$sql1 = "
CREATE TABLE IF NOT EXISTS {$CFG->prefix}agent07_persona_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    situation_id VARCHAR(10) NOT NULL,
    persona_id VARCHAR(10) NOT NULL,
    confidence_score DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    context_data LONGTEXT,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_persona (persona_id),
    INDEX idx_situation (situation_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    $DB->execute($sql1);
    $tables[] = "agent07_persona_log";
    echo "<p class='success'>✅ Table agent07_persona_log created/verified.</p>";
} catch (Exception $e) {
    $errors[] = "agent07_persona_log: " . $e->getMessage();
    echo "<p class='error'>❌ Error creating agent07_persona_log: " . $e->getMessage() . "</p>";
}

// 2. mdl_agent07_response_log
$sql2 = "
CREATE TABLE IF NOT EXISTS {$CFG->prefix}agent07_response_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    persona_id VARCHAR(10) NOT NULL,
    situation_id VARCHAR(10) NOT NULL,
    response_text TEXT,
    confidence_score DECIMAL(3,2) DEFAULT NULL,
    user_message TEXT,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_persona (persona_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    $DB->execute($sql2);
    $tables[] = "agent07_response_log";
    echo "<p class='success'>✅ Table agent07_response_log created/verified.</p>";
} catch (Exception $e) {
    $errors[] = "agent07_response_log: " . $e->getMessage();
    echo "<p class='error'>❌ Error creating agent07_response_log: " . $e->getMessage() . "</p>";
}

// 3. mdl_agent07_context_log
$sql3 = "
CREATE TABLE IF NOT EXISTS {$CFG->prefix}agent07_context_log (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    session_id VARCHAR(50) DEFAULT NULL,
    context_data LONGTEXT,
    created_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_userid (userid),
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    $DB->execute($sql3);
    $tables[] = "agent07_context_log";
    echo "<p class='success'>✅ Table agent07_context_log created/verified.</p>";
} catch (Exception $e) {
    $errors[] = "agent07_context_log: " . $e->getMessage();
    echo "<p class='error'>❌ Error creating agent07_context_log: " . $e->getMessage() . "</p>";
}

// 4. mdl_agent07_user_state
$sql4 = "
CREATE TABLE IF NOT EXISTS {$CFG->prefix}agent07_user_state (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    current_activity VARCHAR(50) DEFAULT 'idle',
    pomodoro_active TINYINT(1) DEFAULT 0,
    focus_score DECIMAL(3,2) DEFAULT 0.50,
    motivation_score DECIMAL(3,2) DEFAULT 0.50,
    session_id VARCHAR(50) DEFAULT NULL,
    updated_at BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_userid_unique (userid),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    $DB->execute($sql4);
    $tables[] = "agent07_user_state";
    echo "<p class='success'>✅ Table agent07_user_state created/verified.</p>";
} catch (Exception $e) {
    $errors[] = "agent07_user_state: " . $e->getMessage();
    echo "<p class='error'>❌ Error creating agent07_user_state: " . $e->getMessage() . "</p>";
}

// 결과 요약
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p class='info'>Tables created/verified: " . count($tables) . "</p>";
echo "<p>" . implode(", ", $tables) . "</p>";

if (!empty($errors)) {
    echo "<p class='error'>Errors: " . count($errors) . "</p>";
    foreach ($errors as $err) {
        echo "<p class='error'>- " . $err . "</p>";
    }
} else {
    echo "<p class='success'>All tables ready!</p>";
}

// 테이블 상태 확인
echo "<hr>";
echo "<h2>Table Status</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table</th><th>Row Count</th><th>Status</th></tr>";

$tableNames = array(
    'agent07_persona_log',
    'agent07_response_log',
    'agent07_context_log',
    'agent07_user_state'
);

foreach ($tableNames as $tableName) {
    try {
        $count = $DB->count_records($tableName);
        echo "<tr><td>{$tableName}</td><td>{$count}</td><td class='success'>OK</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>{$tableName}</td><td>-</td><td class='error'>Error</td></tr>";
    }
}

echo "</table>";

echo "<hr>";
echo "<p><a href='chat.php'>Go to Chat API (GET for status)</a></p>";
echo "<p><a href='../index.php'>Go to Test Interface</a></p>";

echo "</body></html>";
