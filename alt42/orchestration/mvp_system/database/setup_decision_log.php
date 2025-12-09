<?php
// 파일: mvp_system/database/setup_decision_log.php (Line 1)
// Mathking Agentic MVP System - Decision Log Table Setup
//
// Purpose: Create mdl_mvp_decision_log table for execution tracking
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
    echo "<p>Error Location: setup_decision_log.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
$logger = new MVPLogger('setup_decision_log');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decision Log Setup - MVP System</title>
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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Decision Log Table Setup</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Create database table for agent decision execution tracking
        </div>

<?php

try {
    // ============================================================
    // STEP 1: Check if table already exists
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 1: Checking Existing Table</div>";

    $dbman = $DB->get_manager();
    $table = new xmldb_table('mvp_decision_log');
    $table_exists = $dbman->table_exists($table);

    if ($table_exists) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Table Already Exists</strong><br>";
        echo "Table: mdl_mvp_decision_log<br>";

        // Get row count
        $count = $DB->count_records('mvp_decision_log');
        echo "Current rows: {$count}<br>";
        echo "<p>If you need to recreate the table, please drop it first using SQL:</p>";
        echo "<pre>DROP TABLE mdl_mvp_decision_log;</pre>";
        echo "</div>";

        $logger->info("Table already exists", [], ['row_count' => $count]);
    } else {
        echo "<div class='info'>ℹ️ Table does not exist. Proceeding with creation.</div>";
    }

    echo "</div>"; // End Step 1

    // ============================================================
    // STEP 2: Create Table (if not exists)
    // ============================================================
    if (!$table_exists) {
        echo "<div class='step'>";
        echo "<div class='step-title'>Step 2: Creating mdl_mvp_decision_log Table</div>";

        $sql = "
            CREATE TABLE mdl_mvp_decision_log (
                id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id BIGINT(10) UNSIGNED NOT NULL COMMENT '학생 ID (mdl_user.id)',
                agent_id VARCHAR(50) NOT NULL COMMENT '에이전트 ID (예: agent08_calmness)',
                agent_name VARCHAR(100) DEFAULT NULL COMMENT '에이전트 이름',
                rule_id VARCHAR(100) NOT NULL COMMENT '실행된 룰 ID',
                action VARCHAR(50) NOT NULL COMMENT '실행된 액션',
                confidence DECIMAL(5,4) NOT NULL DEFAULT 1.0000 COMMENT '실행 신뢰도 (0.0-1.0)',
                context_data TEXT DEFAULT NULL COMMENT '컨텍스트 데이터 (JSON)',
                result_data TEXT DEFAULT NULL COMMENT '실행 결과 데이터 (JSON)',
                is_cascade TINYINT(1) DEFAULT 0 COMMENT '캐스케이드 실행 여부',
                cascade_depth INT DEFAULT 0 COMMENT '캐스케이드 깊이 (0=초기)',
                parent_decision_id BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '부모 결정 ID (캐스케이드)',
                execution_time_ms DECIMAL(10,2) DEFAULT NULL COMMENT '실행 시간 (밀리초)',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                notes TEXT DEFAULT NULL COMMENT '추가 메모',
                PRIMARY KEY (id),
                INDEX idx_student_id (student_id),
                INDEX idx_agent_id (agent_id),
                INDEX idx_rule_id (rule_id),
                INDEX idx_created_at (created_at),
                INDEX idx_is_cascade (is_cascade),
                INDEX idx_parent_decision (parent_decision_id),
                FOREIGN KEY (parent_decision_id) REFERENCES mdl_mvp_decision_log(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 결정 실행 로그'
        ";

        $DB->execute($sql);

        echo "<div class='success'>";
        echo "✅ <strong>Table Created Successfully</strong><br>";
        echo "Table: mdl_mvp_decision_log<br>";
        echo "</div>";

        $logger->info("Created mdl_mvp_decision_log table", []);

        echo "</div>"; // End Step 2
    }

    // ============================================================
    // STEP 3: Verification
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 3: Table Verification</div>";

    // Verify table structure
    $columns = $DB->get_columns('mvp_decision_log');

    echo "<h3>Table Columns:</h3>";
    echo "<table>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Nullable</th></tr></thead>";
    echo "<tbody>";

    $expected_columns = ['id', 'student_id', 'agent_id', 'agent_name', 'rule_id', 'action', 'confidence', 'context_data', 'result_data', 'is_cascade', 'cascade_depth', 'parent_decision_id', 'execution_time_ms', 'created_at', 'notes'];
    $found_columns = [];

    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col->name) . "</td>";
        echo "<td>" . htmlspecialchars($col->type) . "</td>";
        echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
        echo "</tr>";
        $found_columns[] = $col->name;
    }

    echo "</tbody></table>";

    // Check for missing columns
    $missing = array_diff($expected_columns, $found_columns);
    if (empty($missing)) {
        echo "<div class='success'>";
        echo "<strong>✅ All Columns Present</strong><br>";
        echo "Expected: " . count($expected_columns) . " columns<br>";
        echo "Found: " . count($found_columns) . " columns";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Missing Columns</strong><br>";
        echo "Missing: " . implode(', ', $missing);
        echo "</div>";
    }

    // Row count
    $total_rows = $DB->count_records('mvp_decision_log');

    echo "<div class='info'>";
    echo "<strong>Total Decision Records:</strong> {$total_rows}<br>";
    echo "</div>";

    echo "</div>"; // End Step 3

    // ============================================================
    // STEP 4: Next Steps
    // ============================================================
    echo "<div class='step'>";
    echo "<div class='step-title'>Step 4: Next Steps</div>";

    echo "<div class='success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The Decision Log table is ready for use.</p>";
    echo "<ul>";
    echo "<li>Table: mdl_mvp_decision_log</li>";
    echo "<li>Columns: " . count($found_columns) . " / " . count($expected_columns) . "</li>";
    echo "<li>Decision records: {$total_rows}</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div class='info'>";
    echo "<h3>📋 Next Steps</h3>";
    echo "<ol>";
    echo "<li>Re-run backward compatibility test</li>";
    echo "<li>Verify v1 and v2 orchestrators produce identical results</li>";
    echo "<li>Proceed with agent migration if tests pass</li>";
    echo "</ol>";
    echo "</div>";

    echo "</div>"; // End Step 4

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Setup Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
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

<?php
/**
 * File Location: mvp_system/database/setup_decision_log.php (Line 1)
 * Purpose: One-click setup for Decision Log table
 *
 * Database Tables Created:
 * - mdl_mvp_decision_log: Agent decision execution tracking
 *
 * Fields:
 * - id: BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT
 * - student_id: BIGINT(10) UNSIGNED NOT NULL (학생 ID)
 * - agent_id: VARCHAR(50) NOT NULL (에이전트 ID)
 * - agent_name: VARCHAR(100) (에이전트 이름)
 * - rule_id: VARCHAR(100) NOT NULL (실행된 룰 ID)
 * - action: VARCHAR(50) NOT NULL (실행된 액션)
 * - confidence: DECIMAL(5,4) NOT NULL (신뢰도)
 * - context_data: TEXT (컨텍스트 JSON)
 * - result_data: TEXT (결과 JSON)
 * - is_cascade: TINYINT(1) (캐스케이드 여부)
 * - cascade_depth: INT (캐스케이드 깊이)
 * - parent_decision_id: BIGINT(10) UNSIGNED (부모 결정 ID)
 * - execution_time_ms: DECIMAL(10,2) (실행 시간)
 * - created_at: DATETIME
 * - notes: TEXT (추가 메모)
 *
 * Indexes:
 * - PRIMARY KEY (id)
 * - INDEX idx_student_id (student_id)
 * - INDEX idx_agent_id (agent_id)
 * - INDEX idx_rule_id (rule_id)
 * - INDEX idx_created_at (created_at)
 * - INDEX idx_is_cascade (is_cascade)
 * - INDEX idx_parent_decision (parent_decision_id)
 * - FOREIGN KEY (parent_decision_id) REFERENCES mdl_mvp_decision_log(id)
 */
?>
