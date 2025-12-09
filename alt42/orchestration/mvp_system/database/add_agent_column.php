<?php
// 파일: mvp_system/database/add_agent_column.php (Line 1)
// Mathking Agentic MVP System - Safe Column/Index Addition
//
// Purpose: Add agent_id column and index to decision_log table (idempotent)
// Usage: Direct browser access after running 002_agent_orchestration_safe.sql
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
    echo "<p>Error Location: add_agent_column.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
$logger = new MVPLogger('add_agent_column');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Agent Column - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Add agent_id Column to decision_log</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

<?php

try {
    // Step 1: Check if column already exists
    echo "<h2>Step 1: Checking for agent_id column</h2>";

    $columns = $DB->get_records_sql("DESCRIBE mdl_mvp_decision_log");
    $column_exists = false;

    foreach ($columns as $col) {
        if ($col->field === 'agent_id') {
            $column_exists = true;
            break;
        }
    }

    if ($column_exists) {
        echo "<div class='warning'>⚠️ Column agent_id already exists in mdl_mvp_decision_log - skipping ADD COLUMN</div>";
        $logger->warning("Column already exists", null, ['column' => 'agent_id']);
    } else {
        echo "<div class='info'>ℹ️ Column agent_id not found - will add it</div>";

        // Add column
        $sql = "ALTER TABLE mdl_mvp_decision_log
                ADD COLUMN agent_id VARCHAR(50) DEFAULT NULL COMMENT '실행한 에이전트 ID' AFTER rule_id";

        $DB->execute($sql);

        echo "<div class='success'>✅ Column agent_id added successfully</div>";
        $logger->info("Column added", null, ['column' => 'agent_id']);
    }

    // Step 2: Check if index already exists
    echo "<h2>Step 2: Checking for idx_agent_id index</h2>";

    $indexes = $DB->get_records_sql("SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id'");
    $index_exists = !empty($indexes);

    if ($index_exists) {
        echo "<div class='warning'>⚠️ Index idx_agent_id already exists - skipping ADD INDEX</div>";
        $logger->warning("Index already exists", null, ['index' => 'idx_agent_id']);
    } else {
        echo "<div class='info'>ℹ️ Index idx_agent_id not found - will add it</div>";

        // Add index
        $sql = "ALTER TABLE mdl_mvp_decision_log ADD INDEX idx_agent_id (agent_id)";
        $DB->execute($sql);

        echo "<div class='success'>✅ Index idx_agent_id added successfully</div>";
        $logger->info("Index added", null, ['index' => 'idx_agent_id']);
    }

    // Step 3: Verify
    echo "<h2>Step 3: Verification</h2>";

    // Re-check column
    $columns = $DB->get_records_sql("DESCRIBE mdl_mvp_decision_log");
    $column_verified = false;

    foreach ($columns as $col) {
        if ($col->field === 'agent_id') {
            $column_verified = true;
            echo "<div class='success'>✅ Column agent_id verified: Type={$col->type}, Null={$col->null}, Default={$col->default}</div>";
            break;
        }
    }

    if (!$column_verified) {
        echo "<div class='error'>❌ Column agent_id verification failed</div>";
    }

    // Re-check index
    $indexes = $DB->get_records_sql("SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id'");

    if (!empty($indexes)) {
        echo "<div class='success'>✅ Index idx_agent_id verified</div>";
        echo "<pre>" . print_r($indexes, true) . "</pre>";
    } else {
        echo "<div class='error'>❌ Index idx_agent_id verification failed</div>";
    }

    echo "<hr>";
    echo "<h2>✅ Operation Complete</h2>";
    echo "<div class='success'>The agent_id column and index have been successfully added to mdl_mvp_decision_log table.</div>";

    $logger->info("Operation completed successfully");

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";

    $logger->error("Operation failed", null, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

    </div>
</body>
</html>
