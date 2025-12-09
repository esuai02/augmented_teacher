<?php
/**
 * Database Setup for Agent15 Problem Redefinition Persona System
 *
 * 필요한 DB 테이블 생성 및 마이그레이션 스크립트
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/engine/db_setup.php
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 관리자 권한 확인
require_login();
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die("Error: Admin access required [" . __FILE__ . ":" . __LINE__ . "]");
}

/**
 * 테이블 생성 실행
 */
function run_db_setup() {
    global $DB;

    $results = [];

    // 1. at_agent_persona_state 테이블
    $results[] = create_persona_state_table();

    // 2. at_persona_transition_log 테이블
    $results[] = create_transition_log_table();

    // 3. at_agent_messages 테이블 (에이전트 간 통신)
    $results[] = create_agent_messages_table();

    // 4. at_agent_action_log 테이블 (액션 로그)
    $results[] = create_action_log_table();

    // 5. at_notifications 테이블 (알림)
    $results[] = create_notifications_table();

    // 6. at_cause_analysis_log 테이블 (원인 분석 로그)
    $results[] = create_cause_analysis_table();

    return $results;
}

/**
 * at_agent_persona_state 테이블 생성
 */
function create_persona_state_table() {
    global $DB;

    $tablename = 'at_agent_persona_state';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            nagent INT(5) NOT NULL DEFAULT 15,
            persona_id VARCHAR(50) NOT NULL,
            persona_name VARCHAR(255) DEFAULT '',
            trigger_scenario VARCHAR(50) DEFAULT '',
            confidence DECIMAL(5,4) DEFAULT 0.0000,
            state_data LONGTEXT,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            timemodified BIGINT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_userid (userid),
            KEY idx_nagent (nagent),
            KEY idx_persona_id (persona_id),
            KEY idx_timecreated (timecreated),
            KEY idx_user_agent (userid, nagent)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * at_persona_transition_log 테이블 생성
 */
function create_transition_log_table() {
    global $DB;

    $tablename = 'at_persona_transition_log';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            nagent INT(5) NOT NULL DEFAULT 15,
            from_persona VARCHAR(50) DEFAULT '',
            to_persona VARCHAR(50) NOT NULL,
            trigger_reason TEXT,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_userid (userid),
            KEY idx_nagent (nagent),
            KEY idx_timecreated (timecreated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * at_agent_messages 테이블 생성
 */
function create_agent_messages_table() {
    global $DB;

    $tablename = 'at_agent_messages';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            from_agent INT(5) NOT NULL,
            to_agent INT(5) NOT NULL,
            userid BIGINT(10) NOT NULL,
            message_type VARCHAR(50) NOT NULL,
            message_data LONGTEXT,
            status VARCHAR(20) DEFAULT 'pending',
            processed_at BIGINT(10) DEFAULT NULL,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_from_agent (from_agent),
            KEY idx_to_agent (to_agent),
            KEY idx_userid (userid),
            KEY idx_status (status),
            KEY idx_timecreated (timecreated),
            KEY idx_to_agent_status (to_agent, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * at_agent_action_log 테이블 생성
 */
function create_action_log_table() {
    global $DB;

    $tablename = 'at_agent_action_log';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            nagent INT(5) NOT NULL DEFAULT 15,
            action_id VARCHAR(100) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            action_data LONGTEXT,
            status VARCHAR(20) DEFAULT 'initiated',
            result_data LONGTEXT,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            timecompleted BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_userid (userid),
            KEY idx_nagent (nagent),
            KEY idx_action_id (action_id),
            KEY idx_status (status),
            KEY idx_timecreated (timecreated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * at_notifications 테이블 생성
 */
function create_notifications_table() {
    global $DB;

    $tablename = 'at_notifications';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            data LONGTEXT,
            status VARCHAR(20) DEFAULT 'unread',
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            timeread BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_userid (userid),
            KEY idx_type (type),
            KEY idx_status (status),
            KEY idx_timecreated (timecreated),
            KEY idx_user_status (userid, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * at_cause_analysis_log 테이블 생성
 */
function create_cause_analysis_table() {
    global $DB;

    $tablename = 'at_cause_analysis_log';

    try {
        $dbman = $DB->get_manager();

        if ($dbman->table_exists($tablename)) {
            return ['table' => $tablename, 'status' => 'exists', 'message' => 'Table already exists'];
        }

        $sql = "CREATE TABLE {" . $tablename . "} (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            userid BIGINT(10) NOT NULL,
            nagent INT(5) NOT NULL DEFAULT 15,
            trigger_scenario VARCHAR(50) NOT NULL,
            cognitive_analysis LONGTEXT,
            behavioral_analysis LONGTEXT,
            motivational_analysis LONGTEXT,
            environmental_analysis LONGTEXT,
            primary_cause VARCHAR(255),
            redefined_problem TEXT,
            action_plan LONGTEXT,
            confidence DECIMAL(5,4) DEFAULT 0.0000,
            timecreated BIGINT(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_userid (userid),
            KEY idx_nagent (nagent),
            KEY idx_trigger (trigger_scenario),
            KEY idx_timecreated (timecreated)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->execute(str_replace('{' . $tablename . '}', $DB->get_prefix() . $tablename, $sql));

        return ['table' => $tablename, 'status' => 'created', 'message' => 'Table created successfully'];

    } catch (Exception $e) {
        return ['table' => $tablename, 'status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * 테이블 상태 확인
 */
function check_tables_status() {
    global $DB;

    $tables = [
        'at_agent_persona_state',
        'at_persona_transition_log',
        'at_agent_messages',
        'at_agent_action_log',
        'at_notifications',
        'at_cause_analysis_log'
    ];

    $status = [];
    $dbman = $DB->get_manager();

    foreach ($tables as $table) {
        $exists = $dbman->table_exists($table);
        $count = 0;

        if ($exists) {
            try {
                $count = $DB->count_records($table);
            } catch (Exception $e) {
                $count = -1;
            }
        }

        $status[] = [
            'table' => $table,
            'exists' => $exists,
            'record_count' => $count
        ];
    }

    return $status;
}

// === 실행 ===

header('Content-Type: text/html; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : 'status';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Agent15 DB Setup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .status-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .status-table th, .status-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .status-table th { background: #f8f9fa; font-weight: bold; }
        .status-exists { color: #28a745; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
        .status-created { color: #17a2b8; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #17a2b8; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agent15 Problem Redefinition - DB Setup</h1>

    <?php if ($action === 'setup'): ?>
        <h2>Setup Results</h2>
        <?php
        $results = run_db_setup();
        ?>
        <table class="status-table">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($result['table']); ?></code></td>
                    <td class="status-<?php echo $result['status']; ?>">
                        <?php echo strtoupper($result['status']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($result['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="info">
            <strong>Setup Complete!</strong>
            <p>All database tables have been processed. Please verify the status above.</p>
        </div>

        <a href="?action=status" class="btn">Check Status</a>

    <?php else: ?>
        <h2>Current Table Status</h2>
        <?php
        $status = check_tables_status();
        ?>
        <table class="status-table">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Exists</th>
                    <th>Record Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($status as $item): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($item['table']); ?></code></td>
                    <td class="<?php echo $item['exists'] ? 'status-exists' : 'status-missing'; ?>">
                        <?php echo $item['exists'] ? 'YES' : 'NO'; ?>
                    </td>
                    <td><?php echo $item['record_count'] >= 0 ? $item['record_count'] : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="warning">
            <strong>Warning:</strong> Running setup will create missing tables.
            Existing tables will NOT be modified or deleted.
        </div>

        <a href="?action=setup" class="btn btn-success">Run Setup</a>
        <a href="?action=status" class="btn">Refresh Status</a>
    <?php endif; ?>

    <h2>Table Descriptions</h2>
    <ul>
        <li><strong>at_agent_persona_state</strong>: 페르소나 상태 저장 (사용자별, 에이전트별)</li>
        <li><strong>at_persona_transition_log</strong>: 페르소나 전환 이력</li>
        <li><strong>at_agent_messages</strong>: 에이전트 간 메시지 통신</li>
        <li><strong>at_agent_action_log</strong>: 액션 실행 로그</li>
        <li><strong>at_notifications</strong>: 사용자 알림</li>
        <li><strong>at_cause_analysis_log</strong>: 원인 분석 결과 로그</li>
    </ul>

    <p style="color: #666; font-size: 12px; margin-top: 40px;">
        Agent15 Problem Redefinition Persona System v1.0<br>
        File: <?php echo __FILE__; ?>
    </p>
</div>
</body>
</html>
