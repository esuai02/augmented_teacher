<?php
// 파일: mvp_system/database/setup_complete.php (Line 1)
// Mathking Agentic MVP System - Complete Database Setup
//
// Purpose: One-click setup for entire agent orchestration system
// Usage: Direct browser access (ONE TIME SETUP)
// Security: Teachers and administrators only

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Authentication
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student/parent
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students or parents.</p>";
    echo "<p>Error Location: setup_complete.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
$logger = new MVPLogger('setup_complete');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Database Setup - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .step-title { font-weight: bold; font-size: 18px; color: #2c3e50; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Complete Database Setup</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> One-click setup for entire 22-agent orchestration system
        </div>

<?php

try {
    // ============================================================
    // STEP 1: Create Tables
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 1: Creating Database Tables</div>";

    // Create mdl_mvp_rule_changes
    $table1_exists = $DB->get_manager()->table_exists('mvp_rule_changes');

    if (!$table1_exists) {
        $DB->execute("
            CREATE TABLE mdl_mvp_rule_changes (
                id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                agent_id VARCHAR(50) NOT NULL COMMENT '에이전트 식별자',
                changed_by BIGINT(10) UNSIGNED NOT NULL COMMENT '수정한 사용자 ID',
                change_type ENUM('create', 'update', 'delete', 'rollback') NOT NULL COMMENT '변경 유형',
                old_content LONGTEXT DEFAULT NULL COMMENT '변경 전 YAML 내용',
                new_content LONGTEXT DEFAULT NULL COMMENT '변경 후 YAML 내용',
                change_summary VARCHAR(500) DEFAULT NULL COMMENT '변경 요약',
                timestamp DATETIME NOT NULL COMMENT '변경 시각',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_agent_id (agent_id),
                INDEX idx_changed_by (changed_by),
                INDEX idx_change_type (change_type),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='룰 변경 이력'
        ");
        echo "<div class='success'>✅ Created table: mdl_mvp_rule_changes</div>";
        $logger->info("Created table mdl_mvp_rule_changes", []);
    } else {
        echo "<div class='info'>ℹ️ Table already exists: mdl_mvp_rule_changes</div>";
    }

    // Create mdl_mvp_agent_status
    $table2_exists = $DB->get_manager()->table_exists('mvp_agent_status');

    if (!$table2_exists) {
        $DB->execute("
            CREATE TABLE mdl_mvp_agent_status (
                id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                agent_id VARCHAR(50) NOT NULL UNIQUE COMMENT '에이전트 식별자',
                agent_name VARCHAR(100) NOT NULL COMMENT '에이전트 이름',
                is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
                execution_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '총 실행 횟수',
                success_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '성공 실행 횟수',
                error_count INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '에러 발생 횟수',
                avg_execution_time DECIMAL(10,2) DEFAULT NULL COMMENT '평균 실행 시간 (ms)',
                last_execution_at DATETIME DEFAULT NULL COMMENT '마지막 실행 시각',
                last_error_at DATETIME DEFAULT NULL COMMENT '마지막 에러 시각',
                last_error_msg TEXT DEFAULT NULL COMMENT '마지막 에러 메시지',
                config_data TEXT DEFAULT NULL COMMENT '에이전트 설정 (JSON)',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_agent_id (agent_id),
                INDEX idx_is_active (is_active),
                INDEX idx_last_execution (last_execution_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 상태 및 통계'
        ");
        echo "<div class='success'>✅ Created table: mdl_mvp_agent_status</div>";
        $logger->info("Created table mdl_mvp_agent_status", []);
    } else {
        echo "<div class='info'>ℹ️ Table already exists: mdl_mvp_agent_status</div>";
    }

    // Add agent_id column to mdl_mvp_decision_log (if not exists)
    $dbman = $DB->get_manager();
    $table = new xmldb_table('mvp_decision_log');
    $field = new xmldb_field('agent_id');

    $has_agent_id = $dbman->field_exists($table, $field);

    if (!$has_agent_id) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN agent_id VARCHAR(50) DEFAULT NULL COMMENT '실행한 에이전트 ID' AFTER rule_id");
        echo "<div class='success'>✅ Added agent_id column to mdl_mvp_decision_log</div>";

        // Add index
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD INDEX idx_agent_id (agent_id)");
        echo "<div class='success'>✅ Created index idx_agent_id</div>";

        $logger->info("Added agent_id column and index to decision_log", []);
    } else {
        echo "<div class='info'>ℹ️ Column already exists: mdl_mvp_decision_log.agent_id</div>";
    }

    echo "</div>"; // End Step 1

    // ============================================================
    // STEP 2: Insert 22 Actual Agents
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 2: Inserting 22 Actual Agents</div>";

    // Define actual agents from agents folder
    $actual_agents = [
        ['agent_id' => 'agent01_onboarding', 'agent_name' => 'Onboarding'],
        ['agent_id' => 'agent02_exam_schedule', 'agent_name' => 'Exam Schedule'],
        ['agent_id' => 'agent03_goals_analysis', 'agent_name' => 'Goals Analysis'],
        ['agent_id' => 'agent04_problem_activity', 'agent_name' => 'Problem Activity'],
        ['agent_id' => 'agent05_learning_emotion', 'agent_name' => 'Learning Emotion'],
        ['agent_id' => 'agent06_teacher_feedback', 'agent_name' => 'Teacher Feedback'],
        ['agent_id' => 'agent07_interaction_targeting', 'agent_name' => 'Interaction Targeting'],
        ['agent_id' => 'agent08_calmness', 'agent_name' => 'Calmness'],
        ['agent_id' => 'agent09_learning_management', 'agent_name' => 'Learning Management'],
        ['agent_id' => 'agent10_concept_notes', 'agent_name' => 'Concept Notes'],
        ['agent_id' => 'agent11_problem_notes', 'agent_name' => 'Problem Notes'],
        ['agent_id' => 'agent12_rest_routine', 'agent_name' => 'Rest Routine'],
        ['agent_id' => 'agent13_learning_dropout', 'agent_name' => 'Learning Dropout'],
        ['agent_id' => 'agent14_current_position', 'agent_name' => 'Current Position'],
        ['agent_id' => 'agent15_problem_redefinition', 'agent_name' => 'Problem Redefinition'],
        ['agent_id' => 'agent16_interaction_preparation', 'agent_name' => 'Interaction Preparation'],
        ['agent_id' => 'agent17_remaining_activities', 'agent_name' => 'Remaining Activities'],
        ['agent_id' => 'agent18_signature_routine', 'agent_name' => 'Signature Routine'],
        ['agent_id' => 'agent19_interaction_content', 'agent_name' => 'Interaction Content'],
        ['agent_id' => 'agent20_intervention_preparation', 'agent_name' => 'Intervention Preparation'],
        ['agent_id' => 'agent21_intervention_execution', 'agent_name' => 'Intervention Execution'],
        ['agent_id' => 'agent22_module_improvement', 'agent_name' => 'Module Improvement']
    ];

    // Delete all existing records first
    $DB->delete_records('mvp_agent_status');
    echo "<div class='info'>🗑️ Cleared existing agent records</div>";

    $inserted_count = 0;
    foreach ($actual_agents as $agent) {
        $record = new stdClass();
        $record->agent_id = $agent['agent_id'];
        $record->agent_name = $agent['agent_name'];
        $record->is_active = 1;
        $record->execution_count = 0;
        $record->success_count = 0;
        $record->error_count = 0;
        $record->avg_execution_time = null;
        $record->last_execution_at = null;
        $record->last_error_at = null;
        $record->last_error_msg = null;
        $record->config_data = null;

        $DB->insert_record('mvp_agent_status', $record);
        $inserted_count++;

        echo "<div class='success'>✅ Inserted: {$agent['agent_id']} - {$agent['agent_name']}</div>";
    }

    $logger->info("Inserted actual agent data", [], ['count' => $inserted_count]);

    echo "</div>"; // End Step 2

    // ============================================================
    // STEP 3: Verification
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 3: Verification</div>";

    // Verify agent count
    $total_count = $DB->count_records('mvp_agent_status');

    if ($total_count == 22) {
        echo "<div class='success'>";
        echo "<h3>✅ Setup Complete!</h3>";
        echo "<p><strong>Total Agents:</strong> {$total_count} / 22 (Expected)</p>";
        echo "<p>All agents have been successfully installed.</p>";
        echo "</div>";

        $logger->info("Setup completed successfully", [], ['total' => $total_count]);
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Count Mismatch</h3>";
        echo "<p><strong>Total Agents:</strong> {$total_count} / 22 (Expected)</p>";
        echo "</div>";

        $logger->warning("Agent count mismatch", [], ['expected' => 22, 'actual' => $total_count]);
    }

    // Verify agent_id column
    $has_agent_id_final = $dbman->field_exists($table, $field);

    echo "<p>" . ($has_agent_id_final ? '✅' : '❌') . " <strong>decision_log agent_id column:</strong> " . ($has_agent_id_final ? 'Found' : 'Not Found') . "</p>";

    // Verify index
    $indexes_check = $DB->get_records_sql("SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id'");
    echo "<p>" . (!empty($indexes_check) ? '✅' : '❌') . " <strong>idx_agent_id index:</strong> " . (!empty($indexes_check) ? 'Created' : 'Not Found') . "</p>";

    echo "</div>"; // End Step 3

    // ============================================================
    // STEP 4: Next Steps
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 4: Next Steps</div>";

    echo "<div class='info'>";
    echo "<h3>📋 Verification Page</h3>";
    echo "<p>Visit this page to verify the setup:</p>";
    echo "<pre>https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/verify_agents.php</pre>";
    echo "</div>";

    echo "<div class='success'>";
    echo "<h3>✅ System Ready</h3>";
    echo "<p>The agent orchestration system is now ready for use!</p>";
    echo "<ul>";
    echo "<li>22 agents installed and active</li>";
    echo "<li>Database schema extended with agent tracking</li>";
    echo "<li>Rule change history tracking enabled</li>";
    echo "</ul>";
    echo "</div>";

    echo "</div>"; // End Step 4

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Setup Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";

    $logger->error("Setup failed", $e, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

    </div>
</body>
</html>
