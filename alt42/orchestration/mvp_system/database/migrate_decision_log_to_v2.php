<?php
// 파일: mvp_system/database/migrate_decision_log_to_v2.php
// Purpose: Migrate mdl_mvp_decision_log from v1 to v2 schema
// Usage: Direct browser access (ONE TIME MIGRATION)

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
    <title>Decision Log v1 → v2 Migration</title>
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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Decision Log Table Migration (v1 → v2)</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

        <div class="warning">
            <strong>⚠️ MIGRATION WARNING</strong><br>
            This script will modify the mdl_mvp_decision_log table structure.<br>
            <strong>Backup your data before proceeding!</strong>
        </div>

<?php

try {
    // Check if table exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table('mvp_decision_log');

    if (!$dbman->table_exists($table)) {
        echo "<div class='error'>";
        echo "❌ Table mdl_mvp_decision_log does not exist!<br>";
        echo "Please run setup_decision_log.php first.";
        echo "</div>";
        exit;
    }

    // Get current schema
    echo "<h2>Step 1: Current Schema Analysis</h2>";
    $columns = $DB->get_columns('mvp_decision_log');

    echo "<table>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Status</th></tr></thead>";
    echo "<tbody>";

    $v2_columns = [
        'id', 'student_id', 'agent_id', 'agent_name', 'rule_id', 'action',
        'confidence', 'context_data', 'result_data', 'is_cascade', 'cascade_depth',
        'parent_decision_id', 'execution_time_ms', 'created_at', 'notes'
    ];

    $existing_columns = array_keys($columns);
    $missing_columns = array_diff($v2_columns, $existing_columns);
    $extra_columns = array_diff($existing_columns, $v2_columns);

    foreach ($columns as $col) {
        $status = in_array($col->name, $v2_columns) ? '✅ OK' : '⚠️ Extra';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col->name) . "</td>";
        echo "<td>" . htmlspecialchars($col->type) . "</td>";
        echo "<td>" . ($col->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    if (empty($missing_columns) && empty($extra_columns)) {
        echo "<div class='success'>";
        echo "✅ Table is already in v2 schema format!";
        echo "</div>";
        exit;
    }

    // Show migration plan
    echo "<h2>Step 2: Migration Plan</h2>";

    if (!empty($missing_columns)) {
        echo "<div class='info'>";
        echo "<strong>Missing Columns (will be added):</strong><br>";
        echo implode(', ', $missing_columns);
        echo "</div>";
    }

    if (!empty($extra_columns)) {
        echo "<div class='warning'>";
        echo "<strong>Extra Columns (v1 legacy, will be preserved):</strong><br>";
        echo implode(', ', $extra_columns);
        echo "</div>";
    }

    // Count existing records
    $record_count = $DB->count_records('mvp_decision_log');
    echo "<div class='info'>";
    echo "<strong>Existing Records:</strong> {$record_count}<br>";
    echo "These records will be preserved during migration.";
    echo "</div>";

    // Perform migration
    echo "<h2>Step 3: Executing Migration</h2>";

    $migration_steps = [];

    // Add missing columns
    if (in_array('agent_name', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN agent_name VARCHAR(100) DEFAULT NULL COMMENT '에이전트 이름' AFTER agent_id");
        $migration_steps[] = "Added column: agent_name VARCHAR(100)";
    }

    if (in_array('context_data', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN context_data TEXT DEFAULT NULL COMMENT '컨텍스트 데이터 (JSON)' AFTER confidence");
        $migration_steps[] = "Added column: context_data TEXT";
    }

    if (in_array('result_data', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN result_data TEXT DEFAULT NULL COMMENT '실행 결과 데이터 (JSON)' AFTER context_data");
        $migration_steps[] = "Added column: result_data TEXT";
    }

    if (in_array('is_cascade', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN is_cascade TINYINT(1) DEFAULT 0 COMMENT '캐스케이드 실행 여부' AFTER result_data");
        $migration_steps[] = "Added column: is_cascade TINYINT(1)";
    }

    if (in_array('cascade_depth', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN cascade_depth INT DEFAULT 0 COMMENT '캐스케이드 깊이' AFTER is_cascade");
        $migration_steps[] = "Added column: cascade_depth INT";
    }

    if (in_array('parent_decision_id', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN parent_decision_id BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '부모 결정 ID' AFTER cascade_depth");
        $migration_steps[] = "Added column: parent_decision_id BIGINT(10)";
    }

    if (in_array('execution_time_ms', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN execution_time_ms DECIMAL(10,2) DEFAULT NULL COMMENT '실행 시간 (밀리초)' AFTER parent_decision_id");
        $migration_steps[] = "Added column: execution_time_ms DECIMAL(10,2)";
    }

    if (in_array('notes', $missing_columns)) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD COLUMN notes TEXT DEFAULT NULL COMMENT '추가 메모' AFTER execution_time_ms");
        $migration_steps[] = "Added column: notes TEXT";
    }

    // Modify confidence column if needed
    if (isset($columns['confidence']) && $columns['confidence']->type === 'decimal' && $columns['confidence']->max_length == 3) {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log MODIFY COLUMN confidence DECIMAL(5,4) NOT NULL DEFAULT 1.0000 COMMENT '실행 신뢰도 (0.0-1.0)'");
        $migration_steps[] = "Modified column: confidence DECIMAL(3,2) → DECIMAL(5,4)";
    }

    // Add indexes if missing
    try {
        $DB->execute("CREATE INDEX idx_is_cascade ON mdl_mvp_decision_log(is_cascade)");
        $migration_steps[] = "Added index: idx_is_cascade";
    } catch (Exception $e) {
        // Index might already exist
    }

    try {
        $DB->execute("CREATE INDEX idx_parent_decision ON mdl_mvp_decision_log(parent_decision_id)");
        $migration_steps[] = "Added index: idx_parent_decision";
    } catch (Exception $e) {
        // Index might already exist
    }

    // Add foreign key if missing
    try {
        $DB->execute("ALTER TABLE mdl_mvp_decision_log ADD CONSTRAINT fk_parent_decision FOREIGN KEY (parent_decision_id) REFERENCES mdl_mvp_decision_log(id) ON DELETE SET NULL");
        $migration_steps[] = "Added foreign key: fk_parent_decision";
    } catch (Exception $e) {
        // Foreign key might already exist
    }

    echo "<div class='success'>";
    echo "<h3>✅ Migration Completed Successfully!</h3>";
    echo "<ul>";
    foreach ($migration_steps as $step) {
        echo "<li>" . htmlspecialchars($step) . "</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Verify final schema
    echo "<h2>Step 4: Verification</h2>";
    $columns_after = $DB->get_columns('mvp_decision_log');

    echo "<table>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Status</th></tr></thead>";
    echo "<tbody>";

    foreach ($v2_columns as $col_name) {
        $exists = isset($columns_after[$col_name]);
        $status = $exists ? '✅ Present' : '❌ Missing';
        $type = $exists ? $columns_after[$col_name]->type : 'N/A';
        $nullable = $exists ? ($columns_after[$col_name]->not_null ? 'NO' : 'YES') : 'N/A';

        echo "<tr>";
        echo "<td>" . htmlspecialchars($col_name) . "</td>";
        echo "<td>" . htmlspecialchars($type) . "</td>";
        echo "<td>" . $nullable . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    $final_missing = array_diff($v2_columns, array_keys($columns_after));

    if (empty($final_missing)) {
        echo "<div class='success'>";
        echo "<strong>✅ All v2 columns present!</strong><br>";
        echo "Total columns: " . count($columns_after) . "<br>";
        echo "Total records: " . $DB->count_records('mvp_decision_log');
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Still missing columns:</strong><br>";
        echo implode(', ', $final_missing);
        echo "</div>";
    }

    echo "<h2>Step 5: Next Steps</h2>";
    echo "<div class='info'>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<ol>";
    echo "<li>Re-run backward compatibility test</li>";
    echo "<li>Verify v1 and v2 orchestrators produce identical results</li>";
    echo "<li>Proceed with agent migration if tests pass</li>";
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
 * File Location: mvp_system/database/migrate_decision_log_to_v2.php
 * Purpose: Migrate mdl_mvp_decision_log from v1 schema to v2 schema
 *
 * Migration Actions:
 * - Add missing v2 columns: agent_name, context_data, result_data, is_cascade, cascade_depth, parent_decision_id, execution_time_ms, notes
 * - Modify confidence: DECIMAL(3,2) → DECIMAL(5,4)
 * - Add indexes: idx_is_cascade, idx_parent_decision
 * - Add foreign key: parent_decision_id → id
 * - Preserve existing v1 data and columns
 *
 * V1 Columns (legacy, preserved):
 * - params, rationale, trace_data, timestamp
 *
 * V2 Columns (added):
 * - agent_name, context_data, result_data, is_cascade, cascade_depth, parent_decision_id, execution_time_ms, notes
 */
?>
