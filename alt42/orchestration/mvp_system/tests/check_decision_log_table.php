<?php
// 파일: mvp_system/tests/check_decision_log_table.php
// Purpose: Verify mdl_mvp_decision_log table structure
// Usage: Direct browser access for diagnostics

// Server connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Authentication
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check authorization
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    die("Access denied");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Decision Log Table Check</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #3498db; color: white; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .metric { display: inline-block; background: #e8f4f8; padding: 5px 10px; border-radius: 3px; margin: 5px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Decision Log Table Diagnostic</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

<?php

try {
    $dbman = $DB->get_manager();

    // Check if table exists
    echo "<h2>Step 1: Table Existence Check</h2>";
    $table = new xmldb_table('mvp_decision_log');
    $exists = $dbman->table_exists($table);

    if (!$exists) {
        echo "<div class='error'>";
        echo "<strong>❌ Table does not exist!</strong><br>";
        echo "Table: mdl_mvp_decision_log<br>";
        echo "<p>Please run setup_decision_log.php first.</p>";
        echo "</div>";
        exit;
    }

    echo "<div class='success'>";
    echo "✅ <strong>Table exists:</strong> mdl_mvp_decision_log";
    echo "</div>";

    // Get actual column structure
    echo "<h2>Step 2: Column Structure</h2>";
    $columns = $DB->get_columns('mvp_decision_log');

    echo "<table>";
    echo "<thead><tr><th>Column Name</th><th>Type</th><th>Max Length</th><th>Not Null</th><th>Default</th></tr></thead>";
    echo "<tbody>";

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column->name) . "</td>";
        echo "<td>" . htmlspecialchars($column->type) . "</td>";
        echo "<td>" . htmlspecialchars($column->max_length ?? 'N/A') . "</td>";
        echo "<td>" . ($column->not_null ? '✓ YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($column->default_value ?? 'NULL') . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    // Check required columns
    echo "<h2>Step 3: Required Columns Verification</h2>";

    $required_columns = [
        'id' => 'int',
        'student_id' => 'int',
        'agent_id' => 'char',
        'agent_name' => 'char',
        'rule_id' => 'char',
        'action' => 'char',
        'confidence' => 'number',
        'context_data' => 'text',
        'result_data' => 'text',
        'is_cascade' => 'int',
        'cascade_depth' => 'int',
        'parent_decision_id' => 'int',
        'execution_time_ms' => 'number',
        'created_at' => 'datetime',
        'notes' => 'text'
    ];

    $missing = [];
    $present = [];

    foreach ($required_columns as $col_name => $expected_type) {
        if (isset($columns[$col_name])) {
            $present[] = $col_name;
        } else {
            $missing[] = $col_name;
        }
    }

    if (empty($missing)) {
        echo "<div class='success'>";
        echo "✅ <strong>All required columns present</strong><br>";
        echo "Found: " . count($present) . " / " . count($required_columns) . " columns";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Missing columns:</strong><br>";
        echo implode(', ', $missing);
        echo "</div>";
    }

    // Check indexes
    echo "<h2>Step 4: Index Verification</h2>";

    try {
        $indexes = $DB->get_records_sql("SHOW INDEX FROM {mvp_decision_log}");

        echo "<span class='metric'>Total Indexes: " . count($indexes) . "</span>";

        echo "<table>";
        echo "<thead><tr><th>Key Name</th><th>Column</th><th>Unique</th></tr></thead>";
        echo "<tbody>";

        foreach ($indexes as $index) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($index->key_name) . "</td>";
            echo "<td>" . htmlspecialchars($index->column_name) . "</td>";
            echo "<td>" . ($index->non_unique == 0 ? '✓' : '') . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

    } catch (Exception $e) {
        echo "<div class='info'>Index check not available (may require additional permissions)</div>";
    }

    // Check foreign keys
    echo "<h2>Step 5: Foreign Key Verification</h2>";

    try {
        $fks = $DB->get_records_sql("
            SELECT
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'mdl_mvp_decision_log'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (empty($fks)) {
            echo "<div class='info'>ℹ️ No foreign keys found</div>";
        } else {
            echo "<table>";
            echo "<thead><tr><th>Constraint</th><th>Column</th><th>References</th></tr></thead>";
            echo "<tbody>";

            foreach ($fks as $fk) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($fk->constraint_name) . "</td>";
                echo "<td>" . htmlspecialchars($fk->column_name) . "</td>";
                echo "<td>" . htmlspecialchars($fk->referenced_table_name . '.' . $fk->referenced_column_name) . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        }

    } catch (Exception $e) {
        echo "<div class='info'>Foreign key check not available</div>";
    }

    // Test insert capability
    echo "<h2>Step 6: Insert Test</h2>";

    try {
        // Prepare minimal test record
        $test_record = new stdClass();
        $test_record->student_id = $USER->id;
        $test_record->agent_id = 'test_agent';
        $test_record->agent_name = 'Test Agent';
        $test_record->rule_id = 'test_rule';
        $test_record->action = 'test_action';
        $test_record->confidence = 0.95;
        $test_record->context_data = '{"test": true}';
        $test_record->result_data = '{"success": true}';
        $test_record->is_cascade = 0;
        $test_record->cascade_depth = 0;
        $test_record->parent_decision_id = null;
        $test_record->execution_time_ms = 1.23;
        $test_record->created_at = date('Y-m-d H:i:s');
        $test_record->notes = 'Diagnostic test insert';

        echo "<div class='info'>";
        echo "<strong>Attempting test insert...</strong><br>";
        echo "<pre>" . htmlspecialchars(json_encode($test_record, JSON_PRETTY_PRINT)) . "</pre>";
        echo "</div>";

        $inserted_id = $DB->insert_record('mvp_decision_log', $test_record);

        echo "<div class='success'>";
        echo "✅ <strong>Insert successful!</strong><br>";
        echo "Inserted ID: {$inserted_id}<br>";
        echo "</div>";

        // Clean up test record
        $DB->delete_records('mvp_decision_log', ['id' => $inserted_id]);
        echo "<span class='metric'>Test record deleted</span>";

    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>❌ Insert failed!</strong><br>";
        echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }

    // Summary
    echo "<h2>Summary</h2>";
    $row_count = $DB->count_records('mvp_decision_log');
    echo "<div class='success'>";
    echo "<strong>Table Status:</strong> ✅ Ready<br>";
    echo "<strong>Total Records:</strong> {$row_count}<br>";
    echo "<strong>Columns:</strong> " . count($present) . " / " . count($required_columns) . "<br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Diagnostic Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
<?php
/**
 * File Location: mvp_system/tests/check_decision_log_table.php
 * Purpose: Diagnostic tool for mdl_mvp_decision_log table structure verification
 *
 * Checks:
 * 1. Table existence
 * 2. Column structure and types
 * 3. Required columns presence
 * 4. Indexes
 * 5. Foreign keys
 * 6. Insert capability (with test record)
 */
?>
