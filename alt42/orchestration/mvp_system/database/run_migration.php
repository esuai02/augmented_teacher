<?php
// 파일: mvp_system/database/run_migration.php (Line 1)
// Mathking Agentic MVP System - Database Migration Runner
//
// Purpose: Execute SQL migration files with error handling and logging
// Usage: Direct browser access or CLI: php run_migration.php 002
// Security: Teachers and administrators only

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout to minimize Moodle theme
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Use output buffering to suppress Moodle theme output
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student (allow all non-student roles)
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students or parents.</p>";
    echo "<p>Error Location: run_migration.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

$logger = new MVPLogger('migration');

// Get migration file number from query parameter or command line
$migration_number = $_GET['migration'] ?? ($argv[1] ?? '002');

// Validate migration number format
if (!preg_match('/^\d{3}$/', $migration_number)) {
    $error_msg = "Invalid migration number format. Use 3-digit format (e.g., 002) at " . __FILE__ . ":" . __LINE__;
    $logger->error($error_msg, null, []);
    die($error_msg);
}

$migration_file = __DIR__ . '/migrations/' . $migration_number . '_*.sql';
$matched_files = glob($migration_file);

if (empty($matched_files)) {
    $error_msg = "Migration file not found: {$migration_file} at " . __FILE__ . ":" . __LINE__;
    $logger->error($error_msg, null, []);
    die($error_msg);
}

$sql_file_path = $matched_files[0];
$logger->info("Starting migration", ['file' => $sql_file_path]);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - MVP System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .migration-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .info {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
        .statement {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        .verification {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1>🔧 Database Migration Runner</h1>

        <div class="info">
            <strong>Migration File:</strong> <?php echo htmlspecialchars(basename($sql_file_path)); ?><br>
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

        <h2>Executing Migration...</h2>

<?php

try {
    // Read SQL file
    $sql_content = file_get_contents($sql_file_path);
    if ($sql_content === false) {
        throw new Exception("Failed to read migration file at " . __FILE__ . ":" . __LINE__);
    }

    // Initialize database wrapper
    $mvpdb = new MVPDatabase();

    // Split SQL statements by semicolon (simple parser)
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            // Filter out empty statements and comments
            $stmt = trim($stmt);
            return !empty($stmt) &&
                   substr($stmt, 0, 2) !== '--' &&
                   substr($stmt, 0, 2) !== '/*';
        }
    );

    $executed_count = 0;
    $skipped_count = 0;
    $errors = [];

    echo "<h3>Statements:</h3>";

    foreach ($statements as $index => $statement) {
        $statement = trim($statement);

        // Display statement (truncated if too long)
        $display_stmt = strlen($statement) > 200 ? substr($statement, 0, 200) . '...' : $statement;
        echo "<div class='statement'>" . htmlspecialchars($display_stmt) . "</div>";

        try {
            // Execute statement
            $mvpdb->execute($statement);
            echo "<div class='success'>✅ Statement " . ($index + 1) . " executed successfully</div>";
            $executed_count++;

            $logger->info("Statement executed", [
                'statement_num' => $index + 1,
                'preview' => substr($statement, 0, 100)
            ]);

        } catch (Exception $e) {
            // Check if error is "already exists" or "duplicate key" - treat as warning, not failure
            $error_msg = $e->getMessage();
            $is_duplicate = (
                strpos($error_msg, 'already exists') !== false ||
                strpos($error_msg, 'Duplicate') !== false ||
                strpos($error_msg, 'duplicate key') !== false ||
                strpos($error_msg, 'Duplicate key') !== false ||
                strpos(strtolower($error_msg), 'duplicate') !== false
            );

            if ($is_duplicate) {
                echo "<div class='info'>⚠️ Statement " . ($index + 1) . " skipped (already exists)</div>";
                $skipped_count++;

                $logger->warning("Statement skipped", null, [
                    'statement_num' => $index + 1,
                    'reason' => 'Already exists',
                    'error_hint' => substr($error_msg, 0, 100)
                ]);
            } else {
                echo "<div class='error'>❌ Statement " . ($index + 1) . " failed: " . htmlspecialchars($error_msg) . "</div>";
                $errors[] = [
                    'statement' => $index + 1,
                    'error' => $error_msg,
                    'sql' => substr($statement, 0, 200)
                ];

                $logger->error("Statement failed", null, [
                    'statement_num' => $index + 1,
                    'error' => $error_msg,
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            }
        }

        // Flush output to show progress in real-time
        flush();
    }

    // Summary
    echo "<hr>";
    echo "<h2>📊 Migration Summary</h2>";
    echo "<div class='success'>";
    echo "<strong>Total Statements:</strong> " . count($statements) . "<br>";
    echo "<strong>Executed:</strong> {$executed_count}<br>";
    echo "<strong>Skipped:</strong> {$skipped_count}<br>";
    echo "<strong>Errors:</strong> " . count($errors) . "<br>";
    echo "</div>";

    if (!empty($errors)) {
        echo "<h3>❌ Error Details:</h3>";
        foreach ($errors as $error) {
            echo "<div class='error'>";
            echo "<strong>Statement #{$error['statement']}:</strong> " . htmlspecialchars($error['error']) . "<br>";
            echo "<code>" . htmlspecialchars($error['sql']) . "</code>";
            echo "</div>";
        }
    }

    // Run verification queries
    echo "<hr>";
    echo "<h2>✅ Verification</h2>";
    echo "<div class='verification'>";

    // Check tables created
    $tables_check = $DB->get_records_sql(
        "SELECT table_name FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name IN ('mdl_mvp_rule_changes', 'mdl_mvp_agent_status')"
    );
    echo "<h3>1. Tables Created:</h3>";
    echo "<pre>" . print_r(array_keys($tables_check), true) . "</pre>";

    // Check agent_id column added to decision_log
    $columns_check = $DB->get_records_sql("DESCRIBE mdl_mvp_decision_log");
    $has_agent_id = false;
    foreach ($columns_check as $col) {
        if ($col->field === 'agent_id') {
            $has_agent_id = true;
            break;
        }
    }
    echo "<h3>2. agent_id Column in decision_log:</h3>";
    echo "<pre>" . ($has_agent_id ? "✅ Found" : "❌ Not Found") . "</pre>";

    // Check index created
    $indexes_check = $DB->get_records_sql(
        "SHOW INDEX FROM mdl_mvp_decision_log WHERE Key_name = 'idx_agent_id'"
    );
    echo "<h3>3. Index idx_agent_id:</h3>";
    echo "<pre>" . (!empty($indexes_check) ? "✅ Created" : "❌ Not Found") . "</pre>";

    // Check agent status records
    $agent_count_result = $DB->get_records_sql("SELECT COUNT(*) as count FROM mdl_mvp_agent_status");
    $agent_count = $agent_count_result ? reset($agent_count_result)->count : 0;
    echo "<h3>4. Agent Status Records:</h3>";
    echo "<pre>{$agent_count} agents initialized (expected: 22)</pre>";

    echo "</div>";

    $logger->info("Migration completed", [
        'executed' => $executed_count,
        'skipped' => $skipped_count,
        'errors' => count($errors)
    ]);

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Critical Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";

    $logger->error("Migration failed", null, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

    </div>
</body>
</html>
