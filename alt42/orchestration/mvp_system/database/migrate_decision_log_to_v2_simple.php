<?php
// 파일: mvp_system/database/migrate_decision_log_to_v2_simple.php
// Purpose: Migrate mdl_mvp_decision_log from v1 to v2 schema (simplified)
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

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Decision Log v1 → v2 Migration (Simplified)</title>
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
        <h1>🔧 Decision Log Table Migration (v1 → v2) - Simplified</h1>

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
    // Get direct mysqli connection for raw SQL
    $mysqli = $DB->get_mysqli();

    if (!$mysqli) {
        throw new Exception("Failed to get mysqli connection");
    }

    echo "<h2>Step 1: Add Missing Columns</h2>";
    echo "<div class='step'>";

    $migration_sql = [
        "ADD agent_name VARCHAR(100) DEFAULT NULL",
        "ADD context_data TEXT DEFAULT NULL",
        "ADD result_data TEXT DEFAULT NULL",
        "ADD is_cascade TINYINT(1) DEFAULT 0",
        "ADD cascade_depth INT DEFAULT 0",
        "ADD parent_decision_id BIGINT(10) UNSIGNED DEFAULT NULL",
        "ADD execution_time_ms DECIMAL(10,2) DEFAULT NULL",
        "ADD notes TEXT DEFAULT NULL",
        "MODIFY confidence DECIMAL(5,4) NOT NULL DEFAULT 1.0000"
    ];

    $success_count = 0;
    $error_count = 0;

    foreach ($migration_sql as $sql_part) {
        $full_sql = "ALTER TABLE {$CFG->prefix}mvp_decision_log " . $sql_part;

        echo "<strong>Executing:</strong> <code>" . htmlspecialchars($sql_part) . "</code><br>";

        try {
            $result = $mysqli->query($full_sql);

            if ($result === false) {
                // Check if error is "Duplicate column" (already exists)
                if (strpos($mysqli->error, 'Duplicate column') !== false) {
                    echo "⚠️ Column already exists (skipped)<br>";
                } else {
                    echo "<span style='color: #dc3545;'>❌ Error: " . htmlspecialchars($mysqli->error) . "</span><br>";
                    $error_count++;
                }
            } else {
                echo "<span style='color: #28a745;'>✅ Success</span><br>";
                $success_count++;
            }
        } catch (Exception $e) {
            echo "<span style='color: #dc3545;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</span><br>";
            $error_count++;
        }

        echo "<br>";
    }

    echo "</div>";

    echo "<h2>Step 2: Add Indexes</h2>";
    echo "<div class='step'>";

    $index_sql = [
        "CREATE INDEX idx_is_cascade ON {$CFG->prefix}mvp_decision_log(is_cascade)",
        "CREATE INDEX idx_parent_decision ON {$CFG->prefix}mvp_decision_log(parent_decision_id)"
    ];

    foreach ($index_sql as $sql) {
        echo "<strong>Executing:</strong> <code>" . htmlspecialchars($sql) . "</code><br>";

        try {
            $result = $mysqli->query($sql);

            if ($result === false) {
                if (strpos($mysqli->error, 'Duplicate key') !== false || strpos($mysqli->error, 'already exists') !== false) {
                    echo "⚠️ Index already exists (skipped)<br>";
                } else {
                    echo "<span style='color: #dc3545;'>❌ Error: " . htmlspecialchars($mysqli->error) . "</span><br>";
                }
            } else {
                echo "<span style='color: #28a745;'>✅ Success</span><br>";
            }
        } catch (Exception $e) {
            echo "<span style='color: #dc3545;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }

        echo "<br>";
    }

    echo "</div>";

    echo "<h2>Step 3: Add Foreign Key</h2>";
    echo "<div class='step'>";

    $fk_sql = "ALTER TABLE {$CFG->prefix}mvp_decision_log
                ADD CONSTRAINT fk_parent_decision
                FOREIGN KEY (parent_decision_id)
                REFERENCES {$CFG->prefix}mvp_decision_log(id)
                ON DELETE SET NULL";

    echo "<strong>Executing foreign key constraint...</strong><br>";

    try {
        $result = $mysqli->query($fk_sql);

        if ($result === false) {
            if (strpos($mysqli->error, 'already exists') !== false || strpos($mysqli->error, 'Duplicate') !== false) {
                echo "⚠️ Foreign key already exists (skipped)<br>";
            } else {
                echo "<span style='color: #dc3545;'>❌ Error: " . htmlspecialchars($mysqli->error) . "</span><br>";
            }
        } else {
            echo "<span style='color: #28a745;'>✅ Foreign key added successfully</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: #dc3545;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    }

    echo "</div>";

    // Verify final schema
    echo "<h2>Step 4: Verification</h2>";
    echo "<div class='step'>";

    $columns = $DB->get_columns('mvp_decision_log');

    $v2_required = [
        'id', 'student_id', 'agent_id', 'agent_name', 'rule_id', 'action',
        'confidence', 'context_data', 'result_data', 'is_cascade', 'cascade_depth',
        'parent_decision_id', 'execution_time_ms', 'created_at', 'notes'
    ];

    $existing = array_keys($columns);
    $missing = array_diff($v2_required, $existing);

    echo "<strong>Required V2 Columns:</strong><br>";
    foreach ($v2_required as $col) {
        $present = in_array($col, $existing);
        $icon = $present ? '✅' : '❌';
        $color = $present ? '#28a745' : '#dc3545';
        echo "<span style='color: $color;'>$icon $col</span><br>";
    }

    echo "<br>";

    if (empty($missing)) {
        echo "<div class='success'>";
        echo "<h3>✅ Migration Completed Successfully!</h3>";
        echo "<ul>";
        echo "<li><strong>Total Columns:</strong> " . count($columns) . "</li>";
        echo "<li><strong>V2 Required Columns:</strong> " . count($v2_required) . " / " . count($v2_required) . " ✅</li>";
        echo "<li><strong>Existing Records:</strong> " . $DB->count_records('mvp_decision_log') . " (preserved)</li>";
        echo "<li><strong>Successful Operations:</strong> $success_count</li>";
        if ($error_count > 0) {
            echo "<li><strong>Errors (non-fatal):</strong> $error_count</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Migration Incomplete</h3>";
        echo "<strong>Still Missing:</strong> " . implode(', ', $missing);
        echo "</div>";
    }

    echo "</div>";

    echo "<h2>Step 5: Next Steps</h2>";
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
 * File Location: mvp_system/database/migrate_decision_log_to_v2_simple.php
 * Purpose: Simplified migration using direct mysqli connection
 *
 * Changes Applied:
 * - Add missing v2 columns without AFTER clause (order doesn't matter)
 * - Modify confidence precision: DECIMAL(3,2) → DECIMAL(5,4)
 * - Add indexes for performance
 * - Add foreign key for cascade tracking
 * - Preserve all existing data
 *
 * Compatible with: MySQL 5.7, PHP 7.1.9, Moodle 3.7
 */
?>
