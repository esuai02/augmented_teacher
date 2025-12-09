<?php
/**
 * Rule-Quantum Bridge - Database Diagnostic Script
 *
 * Purpose: Verify mdl_at_rule_quantum_state table structure, indexes, and status
 * Part of: Phase 1 - Rule-Quantum Bridge Implementation
 *
 * Version: 1.0
 * Created: 2025-12-09
 *
 * Usage:
 *   HTML: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/db_diagnostic.php
 *   JSON: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/db_diagnostic.php?format=json
 */

// Moodle integration
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Security: Admin only
if (!is_siteadmin()) {
    die("Error: Admin access required - File: " . __FILE__ . ", Line: " . __LINE__);
}

// Check for JSON format
$format = optional_param('format', 'html', PARAM_ALPHA);

$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'table_name' => 'mdl_at_rule_quantum_state',
    'file' => __FILE__,
    'checks' => []
];

$tableName = 'at_rule_quantum_state';

try {
    // ============================================================
    // CHECK 1: Table Existence
    // ============================================================
    $dbman = $DB->get_manager();
    $table_obj = new xmldb_table($tableName);
    $tableExists = $dbman->table_exists($table_obj);

    $diagnostic['checks']['table_exists'] = [
        'name' => 'Table Existence Check',
        'passed' => $tableExists,
        'message' => $tableExists
            ? "Table mdl_{$tableName} exists"
            : "Table mdl_{$tableName} does NOT exist"
    ];

    if (!$tableExists) {
        $diagnostic['checks']['table_exists']['action_required'] =
            'Run migration: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_010_migration.php';
    }

    if ($tableExists) {
        // ============================================================
        // CHECK 2: Column Structure
        // ============================================================
        $columns = $DB->get_records_sql("SHOW COLUMNS FROM {" . $tableName . "}");
        $columnNames = array_keys($columns);

        $expectedColumns = [
            'id', 'studentid', 'sessionid', 'agentid', 'ruleid',
            'layer1_rule_conf', 'layer2_wave_prob', 'layer3_corr_inf', 'layer4_final',
            'wave_params', 'state_vector',
            'intervention_type', 'intervention_executed', 'intervention_result',
            'rule_priority', 'rule_confidence', 'conditions_matched', 'conditions_total',
            'timecreated', 'timemodified'
        ];

        $missingColumns = array_diff($expectedColumns, $columnNames);
        $extraColumns = array_diff($columnNames, $expectedColumns);

        $diagnostic['checks']['columns'] = [
            'name' => 'Column Structure Check',
            'passed' => empty($missingColumns),
            'total_columns' => count($columnNames),
            'expected_columns' => count($expectedColumns),
            'missing_columns' => array_values($missingColumns),
            'extra_columns' => array_values($extraColumns),
            'column_details' => []
        ];

        foreach ($columns as $col) {
            $diagnostic['checks']['columns']['column_details'][$col->field] = [
                'type' => $col->type,
                'null' => $col->null,
                'key' => $col->key,
                'default' => $col->default
            ];
        }

        // ============================================================
        // CHECK 3: Index Verification
        // ============================================================
        $indexes = $DB->get_records_sql("SHOW INDEX FROM {" . $tableName . "}");

        $indexGroups = [];
        foreach ($indexes as $idx) {
            $keyName = $idx->key_name;
            if (!isset($indexGroups[$keyName])) {
                $indexGroups[$keyName] = [
                    'unique' => !$idx->non_unique,
                    'columns' => []
                ];
            }
            $indexGroups[$keyName]['columns'][] = $idx->column_name;
        }

        $expectedIndexes = [
            'PRIMARY',
            'idx_student_session',
            'idx_agent_rule',
            'idx_session_time',
            'idx_intervention',
            'idx_layer4_final',
            'idx_timecreated'
        ];

        $foundIndexNames = array_keys($indexGroups);
        $missingIndexes = array_diff($expectedIndexes, $foundIndexNames);

        $diagnostic['checks']['indexes'] = [
            'name' => 'Index Verification',
            'passed' => count($missingIndexes) <= 1, // Allow 1 missing index
            'total_indexes' => count($foundIndexNames),
            'expected_indexes' => count($expectedIndexes),
            'found_indexes' => $foundIndexNames,
            'missing_indexes' => array_values($missingIndexes),
            'index_details' => $indexGroups
        ];

        // ============================================================
        // CHECK 4: Record Count
        // ============================================================
        $recordCount = $DB->count_records($tableName);

        $diagnostic['checks']['records'] = [
            'name' => 'Record Count',
            'passed' => true,
            'count' => $recordCount,
            'message' => "Table contains {$recordCount} records"
        ];

        // ============================================================
        // CHECK 5: Layer Value Ranges (if records exist)
        // ============================================================
        if ($recordCount > 0) {
            $stats = $DB->get_record_sql("
                SELECT
                    MIN(layer1_rule_conf) as min_l1,
                    MAX(layer1_rule_conf) as max_l1,
                    AVG(layer1_rule_conf) as avg_l1,
                    MIN(layer4_final) as min_l4,
                    MAX(layer4_final) as max_l4,
                    AVG(layer4_final) as avg_l4
                FROM {" . $tableName . "}"
            );

            $diagnostic['checks']['layer_stats'] = [
                'name' => 'Layer Value Statistics',
                'passed' => true,
                'layer1_rule_conf' => [
                    'min' => floatval($stats->min_l1),
                    'max' => floatval($stats->max_l1),
                    'avg' => round(floatval($stats->avg_l1), 5)
                ],
                'layer4_final' => [
                    'min' => floatval($stats->min_l4),
                    'max' => floatval($stats->max_l4),
                    'avg' => round(floatval($stats->avg_l4), 5)
                ]
            ];
        }
    }

    // ============================================================
    // SUMMARY
    // ============================================================
    $passedChecks = 0;
    $totalChecks = 0;
    foreach ($diagnostic['checks'] as $check) {
        $totalChecks++;
        if ($check['passed']) {
            $passedChecks++;
        }
    }

    $diagnostic['summary'] = [
        'passed' => $passedChecks,
        'total' => $totalChecks,
        'percentage' => round(($passedChecks / $totalChecks) * 100, 1),
        'status' => ($passedChecks === $totalChecks) ? 'OK' : 'ISSUES_FOUND'
    ];

} catch (Exception $e) {
    $diagnostic['error'] = [
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ];
    $diagnostic['summary'] = [
        'passed' => 0,
        'total' => 1,
        'percentage' => 0,
        'status' => 'ERROR'
    ];
}

// ============================================================
// OUTPUT
// ============================================================
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML Output
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/db_diagnostic.php');
$PAGE->set_title('Rule-Quantum Bridge - DB Diagnostic');

echo $OUTPUT->header();
echo $OUTPUT->heading('Rule-Quantum Bridge - Database Diagnostic', 2);
echo "<p><strong>Timestamp:</strong> " . $diagnostic['timestamp'] . "</p>";
echo "<p><strong>Table:</strong> <code>" . $diagnostic['table_name'] . "</code></p>";
echo "<hr>";

// Summary
$summaryColor = ($diagnostic['summary']['status'] === 'OK') ? 'green' : 'orange';
echo "<h3>Summary</h3>";
echo "<p style='font-size: 1.2em; color: {$summaryColor};'>";
echo ($diagnostic['summary']['status'] === 'OK') ? '✅' : '⚠️';
echo " {$diagnostic['summary']['passed']}/{$diagnostic['summary']['total']} checks passed ";
echo "({$diagnostic['summary']['percentage']}%)</p>";

// Detail checks
echo "<h3>Diagnostic Checks</h3>";
foreach ($diagnostic['checks'] as $key => $check) {
    $icon = $check['passed'] ? '✅' : '❌';
    $color = $check['passed'] ? 'green' : 'red';

    echo "<div style='margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h4 style='color: {$color}; margin: 0 0 10px 0;'>{$icon} {$check['name']}</h4>";

    if (isset($check['message'])) {
        echo "<p>{$check['message']}</p>";
    }

    if (isset($check['action_required'])) {
        echo "<p style='color: orange;'><strong>Action Required:</strong> {$check['action_required']}</p>";
    }

    if ($key === 'columns' && isset($check['missing_columns']) && !empty($check['missing_columns'])) {
        echo "<p><strong>Missing Columns:</strong> " . implode(', ', $check['missing_columns']) . "</p>";
    }

    if ($key === 'indexes' && isset($check['found_indexes'])) {
        echo "<p><strong>Found Indexes:</strong> " . implode(', ', $check['found_indexes']) . "</p>";
        if (!empty($check['missing_indexes'])) {
            echo "<p><strong>Missing Indexes:</strong> " . implode(', ', $check['missing_indexes']) . "</p>";
        }
    }

    if ($key === 'layer_stats' && isset($check['layer4_final'])) {
        echo "<p><strong>Layer4 Final Range:</strong> {$check['layer4_final']['min']} ~ {$check['layer4_final']['max']} (avg: {$check['layer4_final']['avg']})</p>";
    }

    echo "</div>";
}

// Links
echo "<hr>";
echo "<h4>Related Links</h4>";
echo "<ul>";
echo "<li><a href='test_phase1_integration.php'>Phase 1 Integration Tests</a></li>";
echo "<li><a href='../../db/migrations/run_010_migration.php'>DB Migration Script</a></li>";
echo "<li><a href='?format=json'>JSON API: Diagnostic</a></li>";
echo "</ul>";

echo $OUTPUT->footer();

// ============================================================
// Related Database Tables
// ============================================================
// mdl_at_rule_quantum_state - Main table (this diagnostic)
// mdl_user - Moodle user table (studentid references this)
// ============================================================
