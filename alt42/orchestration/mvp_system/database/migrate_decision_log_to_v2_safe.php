<?php
// 파일: mvp_system/database/migrate_decision_log_to_v2_safe.php
// Purpose: Migrate mdl_mvp_decision_log from v1 to v2 schema (safe method)
// Usage: Direct browser access (ONE TIME MIGRATION)

// Server connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

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

// Load Moodle XMLDB library
require_once($CFG->libdir . '/ddllib.php');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Decision Log v1 → v2 Migration (Safe)</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Decision Log Table Migration (v1 → v2) - Safe Method</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

        <div class="warning">
            <strong>⚠️ MIGRATION WARNING</strong><br>
            This script will modify the mdl_mvp_decision_log table structure.<br>
            <strong>Existing records will be preserved.</strong>
        </div>

<?php

try {
    $dbman = $DB->get_manager();
    $table = new xmldb_table('mvp_decision_log');

    if (!$dbman->table_exists($table)) {
        throw new Exception("Table mvp_decision_log does not exist!");
    }

    echo "<h2>Step 1: Check Current Schema</h2>";
    echo "<div class='step'>";

    $columns = $DB->get_columns('mvp_decision_log');

    $v2_required = [
        'agent_name' => ['type' => XMLDB_TYPE_CHAR, 'length' => 100, 'notnull' => false],
        'context_data' => ['type' => XMLDB_TYPE_TEXT, 'notnull' => false],
        'result_data' => ['type' => XMLDB_TYPE_TEXT, 'notnull' => false],
        'is_cascade' => ['type' => XMLDB_TYPE_INTEGER, 'length' => 1, 'notnull' => true, 'default' => 0],
        'cascade_depth' => ['type' => XMLDB_TYPE_INTEGER, 'length' => 10, 'notnull' => true, 'default' => 0],
        'parent_decision_id' => ['type' => XMLDB_TYPE_INTEGER, 'length' => 10, 'notnull' => false],
        'execution_time_ms' => ['type' => XMLDB_TYPE_NUMBER, 'length' => 10, 'decimals' => 2, 'notnull' => false],
        'notes' => ['type' => XMLDB_TYPE_TEXT, 'notnull' => false]
    ];

    $missing = [];
    foreach ($v2_required as $col_name => $spec) {
        if (!isset($columns[$col_name])) {
            $missing[] = $col_name;
            echo "<span style='color: #dc3545;'>❌ Missing: $col_name</span><br>";
        } else {
            echo "<span style='color: #28a745;'>✅ Present: $col_name</span><br>";
        }
    }

    echo "</div>";

    if (empty($missing)) {
        echo "<div class='success'>";
        echo "<h3>✅ Table Already Migrated!</h3>";
        echo "<p>All v2 columns are present. No migration needed.</p>";
        echo "</div>";

        // Still show next steps
        echo "<h2>Next Steps</h2>";
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li>Re-run backward compatibility test</li>";
        echo "<li>Verify v1 and v2 orchestrators produce identical results</li>";
        echo "</ol>";
        echo "</div>";

        echo "</div></body></html>";
        exit;
    }

    echo "<h2>Step 2: Add Missing Columns</h2>";
    echo "<div class='step'>";

    $success_count = 0;
    $skip_count = 0;

    // Add agent_name
    if (in_array('agent_name', $missing)) {
        try {
            $field = new xmldb_field('agent_name', XMLDB_TYPE_CHAR, '100', null, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: agent_name<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: agent_name (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add context_data
    if (in_array('context_data', $missing)) {
        try {
            $field = new xmldb_field('context_data', XMLDB_TYPE_TEXT, null, null, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: context_data<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: context_data (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add result_data
    if (in_array('result_data', $missing)) {
        try {
            $field = new xmldb_field('result_data', XMLDB_TYPE_TEXT, null, null, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: result_data<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: result_data (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add is_cascade
    if (in_array('is_cascade', $missing)) {
        try {
            $field = new xmldb_field('is_cascade', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
            echo "✅ Added: is_cascade<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: is_cascade (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add cascade_depth
    if (in_array('cascade_depth', $missing)) {
        try {
            $field = new xmldb_field('cascade_depth', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
            echo "✅ Added: cascade_depth<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: cascade_depth (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add parent_decision_id
    if (in_array('parent_decision_id', $missing)) {
        try {
            $field = new xmldb_field('parent_decision_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: parent_decision_id<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: parent_decision_id (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add execution_time_ms
    if (in_array('execution_time_ms', $missing)) {
        try {
            $field = new xmldb_field('execution_time_ms', XMLDB_TYPE_NUMBER, '10, 2', null, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: execution_time_ms<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: execution_time_ms (already exists or error)<br>";
            $skip_count++;
        }
    }

    // Add notes
    if (in_array('notes', $missing)) {
        try {
            $field = new xmldb_field('notes', XMLDB_TYPE_TEXT, null, null, false, null, null);
            $dbman->add_field($table, $field);
            echo "✅ Added: notes<br>";
            $success_count++;
        } catch (Exception $e) {
            echo "⚠️ Skip: notes (already exists or error)<br>";
            $skip_count++;
        }
    }

    echo "</div>";

    // Modify confidence precision
    echo "<h2>Step 3: Modify Confidence Precision</h2>";
    echo "<div class='step'>";

    try {
        // Check current confidence field
        if (isset($columns['confidence'])) {
            $current_confidence = $columns['confidence'];

            // If it's DECIMAL(3,2), change to DECIMAL(5,4)
            if ($current_confidence->type === 'decimal' && $current_confidence->max_length == 3) {
                $field = new xmldb_field('confidence', XMLDB_TYPE_NUMBER, '5, 4', null, XMLDB_NOTNULL, null, '1.0000');
                $dbman->change_field_precision($table, $field);
                echo "✅ Modified: confidence DECIMAL(3,2) → DECIMAL(5,4)<br>";
            } else {
                echo "⚠️ Confidence already correct precision<br>";
            }
        }
    } catch (Exception $e) {
        echo "⚠️ Could not modify confidence precision: " . htmlspecialchars($e->getMessage()) . "<br>";
    }

    echo "</div>";

    // Add indexes
    echo "<h2>Step 4: Add Indexes</h2>";
    echo "<div class='step'>";

    try {
        $index = new xmldb_index('idx_is_cascade', XMLDB_INDEX_NOTUNIQUE, ['is_cascade']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
            echo "✅ Added index: idx_is_cascade<br>";
        } else {
            echo "⚠️ Index idx_is_cascade already exists<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Could not add idx_is_cascade: " . htmlspecialchars($e->getMessage()) . "<br>";
    }

    try {
        $index = new xmldb_index('idx_parent_decision', XMLDB_INDEX_NOTUNIQUE, ['parent_decision_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
            echo "✅ Added index: idx_parent_decision<br>";
        } else {
            echo "⚠️ Index idx_parent_decision already exists<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Could not add idx_parent_decision: " . htmlspecialchars($e->getMessage()) . "<br>";
    }

    echo "</div>";

    // Add foreign key
    echo "<h2>Step 5: Add Foreign Key</h2>";
    echo "<div class='step'>";

    try {
        $key = new xmldb_key('fk_parent_decision', XMLDB_KEY_FOREIGN, ['parent_decision_id'], 'mvp_decision_log', ['id']);
        $dbman->add_key($table, $key);
        echo "✅ Added foreign key: fk_parent_decision<br>";
    } catch (Exception $e) {
        echo "⚠️ Could not add foreign key (may already exist): " . htmlspecialchars($e->getMessage()) . "<br>";
    }

    echo "</div>";

    // Final verification
    echo "<h2>Step 6: Verification</h2>";
    echo "<div class='step'>";

    $columns_after = $DB->get_columns('mvp_decision_log');
    $still_missing = [];

    foreach (array_keys($v2_required) as $col_name) {
        if (!isset($columns_after[$col_name])) {
            $still_missing[] = $col_name;
        }
    }

    if (empty($still_missing)) {
        echo "<div class='success'>";
        echo "<h3>✅ Migration Completed Successfully!</h3>";
        echo "<ul>";
        echo "<li><strong>Columns Added:</strong> $success_count</li>";
        echo "<li><strong>Columns Skipped:</strong> $skip_count</li>";
        echo "<li><strong>Total Columns:</strong> " . count($columns_after) . "</li>";
        echo "<li><strong>Records Preserved:</strong> " . $DB->count_records('mvp_decision_log') . "</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Migration Incomplete</h3>";
        echo "<strong>Still Missing:</strong> " . implode(', ', $still_missing);
        echo "</div>";
    }

    echo "</div>";

    echo "<h2>Step 7: Next Steps</h2>";
    echo "<div class='info'>";
    echo "<h3>📋 What to do next:</h3>";
    echo "<ol>";
    echo "<li><strong>Re-run backward compatibility test</strong><br>";
    echo "<code>https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/tests/test_backward_compatibility.php</code></li>";
    echo "<li><strong>Verify test passes</strong> (should show 100% pass rate)</li>";
    echo "<li><strong>Proceed with agent migration</strong> if tests pass</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Migration Error</h2>";
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
 * File Location: mvp_system/database/migrate_decision_log_to_v2_safe.php
 * Purpose: Safe migration using Moodle's XMLDB library (Moodle 3.7 compatible)
 *
 * Uses standard Moodle DML methods:
 * - xmldb_field for field definitions
 * - $dbman->add_field() for adding columns
 * - $dbman->change_field_precision() for modifying types
 * - xmldb_index for index definitions
 * - xmldb_key for foreign key definitions
 *
 * Compatible with: MySQL 5.7, PHP 7.1.9, Moodle 3.7
 */
?>
