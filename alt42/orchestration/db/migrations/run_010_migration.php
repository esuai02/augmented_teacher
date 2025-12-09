<?php
/**
 * Rule-Quantum Bridge - Migration 010: Create Rule Quantum State Table
 *
 * Purpose: Install rule quantum state table for 4-layer probability calculations
 * Part of: Phase 1 - Agent04-centric expansion
 *
 * Version: 1.0
 * Created: 2025-12-09
 *
 * Table: mdl_at_rule_quantum_state
 * - Stores 4-layer probability values
 * - Wave function parameters (JSON)
 * - 8D StateVector snapshots (JSON)
 * - Intervention tracking
 */

// Moodle integration
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Security: Admin only
if (!is_siteadmin()) {
    die("Error: Admin access required - File: " . __FILE__ . ", Line: " . __LINE__);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/db/migrations/run_010_migration.php');
$PAGE->set_title('Migration 010: Rule Quantum State Table');

// Check for JSON format request
$format = optional_param('format', 'html', PARAM_ALPHA);
$action = optional_param('action', 'status', PARAM_ALPHA);

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    run_json_migration($action);
    exit;
}

// HTML Output
echo $OUTPUT->header();
echo $OUTPUT->heading('Migration 010: Rule Quantum State Table', 2);
echo "<p><strong>Purpose:</strong> Create table for 4-layer quantum probability calculations</p>";
echo "<p><strong>Part of:</strong> Rule-Quantum Bridge Phase 1</p>";
echo "<hr>";

$errors = [];
$warnings = [];
$success_messages = [];
$table_name = 'at_rule_quantum_state';

try {
    // ============================================================
    // STEP 1: Check existing table
    // ============================================================
    echo "<h3>Step 1: Checking Existing Table</h3>";

    $dbman = $DB->get_manager();
    $table_obj = new xmldb_table($table_name);
    $table_exists = $dbman->table_exists($table_obj);

    if ($table_exists) {
        // Count existing records
        $record_count = $DB->count_records($table_name);
        $warnings[] = "Table 'mdl_{$table_name}' already exists with {$record_count} records";
        echo "<p style='color: orange;'>⚠️ Table 'mdl_{$table_name}' already exists</p>";
        echo "<p>Record count: <strong>{$record_count}</strong></p>";

        // Option to drop and recreate
        echo "<p><strong>Options:</strong></p>";
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='confirm_drop' value='1'>";
        echo "<input type='submit' value='DROP and Recreate' style='background: #dc3545; color: white; padding: 8px 16px; border: none; cursor: pointer; margin-right: 10px;'>";
        echo "</form>";
        echo "<a href='" . $PAGE->url->out() . "' style='padding: 8px 16px; background: #6c757d; color: white; text-decoration: none;'>Cancel / Check Status</a>";

        if (!optional_param('confirm_drop', 0, PARAM_INT)) {
            // Show current table structure
            show_table_status($table_name);
            echo $OUTPUT->footer();
            exit;
        }

        // Drop existing table
        echo "<h4>Dropping existing table...</h4>";
        try {
            $dbman->drop_table($table_obj);
            $success_messages[] = "Dropped table 'mdl_{$table_name}'";
            echo "<p style='color: green;'>✅ Dropped table 'mdl_{$table_name}'</p>";
        } catch (Exception $e) {
            $errors[] = "Failed to drop table: " . $e->getMessage() . " - File: " . __FILE__ . ", Line: " . __LINE__;
            echo "<p style='color: red;'>❌ Failed to drop table: " . $e->getMessage() . "</p>";
            throw $e;
        }
    } else {
        echo "<p style='color: green;'>✅ No existing table found - ready to create</p>";
    }

    // ============================================================
    // STEP 2: Create Table using XMLDB
    // ============================================================
    echo "<h3>Step 2: Creating Rule Quantum State Table</h3>";

    $table = new xmldb_table($table_name);

    // Primary key
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);

    // Context identifiers
    $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('sessionid', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
    $table->add_field('agentid', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL);
    $table->add_field('ruleid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);

    // 4-Layer Probability Values (using NUMBER for DECIMAL)
    $table->add_field('layer1_rule_conf', XMLDB_TYPE_NUMBER, '6,5', null, null, null, '0.00000');
    $table->add_field('layer2_wave_prob', XMLDB_TYPE_NUMBER, '6,5', null, null, null, '0.00000');
    $table->add_field('layer3_corr_inf', XMLDB_TYPE_NUMBER, '6,5', null, null, null, '0.00000');
    $table->add_field('layer4_final', XMLDB_TYPE_NUMBER, '6,5', null, null, null, '0.00000');

    // JSON fields (TEXT)
    $table->add_field('wave_params', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('state_vector', XMLDB_TYPE_TEXT, null, null, null, null, null);

    // Intervention tracking
    $table->add_field('intervention_type', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $table->add_field('intervention_executed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
    $table->add_field('intervention_result', XMLDB_TYPE_TEXT, null, null, null, null, null);

    // Rule metadata snapshot
    $table->add_field('rule_priority', XMLDB_TYPE_INTEGER, '3', null, null, null, '0');
    $table->add_field('rule_confidence', XMLDB_TYPE_NUMBER, '4,3', null, null, null, '0.000');
    $table->add_field('conditions_matched', XMLDB_TYPE_INTEGER, '3', null, null, null, '0');
    $table->add_field('conditions_total', XMLDB_TYPE_INTEGER, '3', null, null, null, '0');

    // Timestamps
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    // Add primary key
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

    // Add indexes for common queries
    $table->add_index('idx_student_session', XMLDB_INDEX_NOTUNIQUE, ['studentid', 'sessionid']);
    $table->add_index('idx_agent_rule', XMLDB_INDEX_NOTUNIQUE, ['agentid', 'ruleid']);
    $table->add_index('idx_session_time', XMLDB_INDEX_NOTUNIQUE, ['sessionid', 'timecreated']);
    $table->add_index('idx_intervention', XMLDB_INDEX_NOTUNIQUE, ['intervention_type', 'intervention_executed']);
    $table->add_index('idx_layer4_final', XMLDB_INDEX_NOTUNIQUE, ['layer4_final']);
    $table->add_index('idx_timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

    // Create the table
    try {
        $dbman->create_table($table);
        $success_messages[] = "Created table 'mdl_{$table_name}'";
        echo "<p style='color: green;'>✅ Created table 'mdl_{$table_name}'</p>";
    } catch (Exception $e) {
        $errors[] = "Failed to create table: " . $e->getMessage() . " - File: " . __FILE__ . ", Line: " . __LINE__;
        echo "<p style='color: red;'>❌ Failed to create table: " . $e->getMessage() . "</p>";
        throw $e;
    }

    // ============================================================
    // STEP 3: Verify Table Creation
    // ============================================================
    echo "<h3>Step 3: Verifying Table</h3>";

    // Refresh table object
    $table_obj = new xmldb_table($table_name);
    if ($dbman->table_exists($table_obj)) {
        echo "<p style='color: green;'>✅ Table verification passed</p>";
        show_table_status($table_name);
    } else {
        $errors[] = "Table verification failed - table does not exist after creation";
        echo "<p style='color: red;'>❌ Table verification failed</p>";
    }

    // ============================================================
    // STEP 4: Summary
    // ============================================================
    echo "<h3>Migration Summary</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Category</th><th>Count</th><th>Details</th></tr>";
    echo "<tr><td style='color: green;'>✅ Success</td><td>" . count($success_messages) . "</td><td>" . implode('<br>', $success_messages) . "</td></tr>";
    echo "<tr><td style='color: orange;'>⚠️ Warnings</td><td>" . count($warnings) . "</td><td>" . implode('<br>', $warnings) . "</td></tr>";
    echo "<tr><td style='color: red;'>❌ Errors</td><td>" . count($errors) . "</td><td>" . implode('<br>', $errors) . "</td></tr>";
    echo "</table>";

    if (empty($errors)) {
        echo "<h3 style='color: green;'>✅ Migration 010 Completed Successfully</h3>";
        echo "<p>Table <code>mdl_{$table_name}</code> is ready for use.</p>";
        echo "<h4>Next Steps:</h4>";
        echo "<ul>";
        echo "<li>Extend QuantumPersonaEngine.php with bridge integration</li>";
        echo "<li>Test Phase 1 components with Agent04</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Migration Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . __FILE__ . ", Line: " . $e->getLine() . "</p>";
}

// Links
echo "<hr>";
echo "<h4>Related Links</h4>";
echo "<ul>";
echo "<li><a href='../install_mvp_schema.php'>MVP Schema Installation</a></li>";
echo "<li><a href='../../shared/quantum/'>Quantum Bridge Files</a></li>";
echo "<li><a href='" . $PAGE->url->out() . "?format=json&action=status'>JSON API: Status</a></li>";
echo "</ul>";

echo $OUTPUT->footer();

// ============================================================
// Helper Functions
// ============================================================

/**
 * Show table status and structure
 */
function show_table_status($table_name) {
    global $DB;

    echo "<h4>Table Structure: mdl_{$table_name}</h4>";

    try {
        // Get column information using raw SQL (MySQL 5.7 compatible)
        $sql = "SHOW COLUMNS FROM {" . $table_name . "}";
        $columns = $DB->get_records_sql($sql);

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><code>{$col->field}</code></td>";
            echo "<td>{$col->type}</td>";
            echo "<td>{$col->null}</td>";
            echo "<td>{$col->key}</td>";
            echo "<td>" . ($col->default ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Show indexes
        echo "<h4>Indexes</h4>";
        $sql = "SHOW INDEX FROM {" . $table_name . "}";
        $indexes = $DB->get_records_sql($sql);

        $index_groups = [];
        foreach ($indexes as $idx) {
            $key_name = $idx->key_name;
            if (!isset($index_groups[$key_name])) {
                $index_groups[$key_name] = [
                    'unique' => !$idx->non_unique,
                    'columns' => []
                ];
            }
            $index_groups[$key_name]['columns'][] = $idx->column_name;
        }

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr><th>Index Name</th><th>Unique</th><th>Columns</th></tr>";
        foreach ($index_groups as $name => $info) {
            echo "<tr>";
            echo "<td><code>{$name}</code></td>";
            echo "<td>" . ($info['unique'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . implode(', ', $info['columns']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>Could not retrieve table info: " . $e->getMessage() . "</p>";
    }
}

/**
 * JSON API handler
 */
function run_json_migration($action) {
    global $DB;

    $table_name = 'at_rule_quantum_state';
    $response = [
        'success' => true,
        'action' => $action,
        'table' => "mdl_{$table_name}",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    try {
        $dbman = $DB->get_manager();
        $table_obj = new xmldb_table($table_name);
        $exists = $dbman->table_exists($table_obj);

        switch ($action) {
            case 'status':
                $response['exists'] = $exists;
                if ($exists) {
                    $response['record_count'] = $DB->count_records($table_name);

                    // Get column count
                    $columns = $DB->get_records_sql("SHOW COLUMNS FROM {" . $table_name . "}");
                    $response['column_count'] = count($columns);

                    // Get index count
                    $indexes = $DB->get_records_sql("SHOW INDEX FROM {" . $table_name . "}");
                    $index_names = array_unique(array_column($indexes, 'key_name'));
                    $response['index_count'] = count($index_names);

                    $response['status'] = 'ok';
                } else {
                    $response['status'] = 'missing';
                }
                break;

            case 'install':
                if ($exists) {
                    $response['message'] = 'Table already exists';
                    $response['status'] = 'already_exists';
                } else {
                    // Would need to replicate table creation logic here
                    $response['message'] = 'Please use the HTML interface to install the table';
                    $response['install_url'] = 'run_010_migration.php';
                    $response['success'] = false;
                }
                break;

            default:
                $response['success'] = false;
                $response['error'] = 'Unknown action: ' . $action;
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['file'] = __FILE__;
        $response['line'] = $e->getLine();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ============================================================
// Related Database Tables
// ============================================================
// mdl_user - Moodle user table (studentid references this)
// mdl_user_info_data - Custom user fields (fieldid=22 for role)
// ============================================================

// ============================================================
// File Dependencies
// ============================================================
// - RuleYamlLoader.php: Loads rules.yaml files
// - RuleToWaveMapper.php: Maps rules to wave parameters
// - QuantumPersonaEngine.php: Bridge integration (next step)
// ============================================================
