<?php
// 파일: mvp_system/tests/test_backward_compatibility.php (Line 1)
// Mathking Agentic MVP System - Backward Compatibility Test
//
// Purpose: Verify MVPAgentOrchestrator_v2 works with existing agents
// Usage: Direct browser access or command-line execution
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
    echo "<p>Error Location: test_backward_compatibility.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

// Load MVP dependencies
require_once(__DIR__ . '/../lib/logger.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/MVPAgentOrchestrator.php');
require_once(__DIR__ . '/../lib/MVPAgentOrchestrator_v2.php');

$logger = new MVPLogger('backward_compatibility_test');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backward Compatibility Test - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1400px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        h3 { color: #7f8c8d; margin-top: 20px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .test-case { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6; }
        .test-header { font-weight: bold; font-size: 16px; color: #2c3e50; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        .diff { background: #fffacd; }
        .match { background: #d4edda; }
        .metric { display: inline-block; margin: 5px 10px; padding: 5px 10px; background: #e8f4f8; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Backward Compatibility Test</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Verify MVPAgentOrchestrator_v2 behaves identically to v1 when graph features are disabled
        </div>

<?php

try {
    // ============================================================
    // PRE-FLIGHT CHECK: DATABASE SCHEMA VALIDATION
    // ============================================================
    echo "<div class='test-case'>";
    echo "<div class='test-header'>Pre-Flight Check: Database Schema Validation</div>";

    // Check if mdl_mvp_decision_log table has v2 schema
    $columns = $DB->get_columns('mvp_decision_log');

    $v2_required_columns = [
        'agent_name' => 'VARCHAR(100) - Agent display name',
        'context_data' => 'TEXT - Context data (JSON)',
        'result_data' => 'TEXT - Execution result (JSON)',
        'is_cascade' => 'TINYINT(1) - Cascade flag',
        'cascade_depth' => 'INT - Cascade depth level',
        'parent_decision_id' => 'BIGINT(10) - Parent decision ID',
        'execution_time_ms' => 'DECIMAL(10,2) - Execution time (ms)',
        'notes' => 'TEXT - Additional notes'
    ];

    $existing_columns = array_keys($columns);
    $missing_columns = array_diff(array_keys($v2_required_columns), $existing_columns);

    // Check confidence precision
    $confidence_needs_upgrade = false;
    if (isset($columns['confidence'])) {
        $confidence_col = $columns['confidence'];
        // Check if it's DECIMAL(3,2) - needs upgrade to DECIMAL(5,4)
        if ($confidence_col->type === 'decimal' && $confidence_col->max_length == 3) {
            $confidence_needs_upgrade = true;
        }
    }

    // If schema mismatch detected, halt and show migration instructions
    if (!empty($missing_columns) || $confidence_needs_upgrade) {
        echo "<div class='error' style='border-left-width: 8px; padding: 25px;'>";
        echo "<h2 style='color: #dc3545; margin-top: 0;'>🚨 DATABASE SCHEMA MISMATCH DETECTED</h2>";
        echo "<p style='font-size: 16px; font-weight: bold;'>The database table still has v1 schema. Migration must be run BEFORE testing.</p>";

        echo "<div style='background: #fff; border: 2px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 4px;'>";
        echo "<h3 style='margin-top: 0; color: #dc3545;'>❌ Missing v2 Columns:</h3>";
        echo "<ul style='font-family: monospace; font-size: 13px;'>";
        foreach ($missing_columns as $col) {
            echo "<li><strong>" . htmlspecialchars($col) . "</strong>: " . htmlspecialchars($v2_required_columns[$col]) . "</li>";
        }
        echo "</ul>";

        if ($confidence_needs_upgrade) {
            echo "<p style='color: #856404; background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
            echo "⚠️ <strong>Confidence field needs upgrade:</strong> DECIMAL(3,2) → DECIMAL(5,4)";
            echo "</p>";
        }
        echo "</div>";

        echo "<div style='background: #e8f4f8; border: 2px solid #3498db; padding: 20px; margin: 20px 0; border-radius: 4px;'>";
        echo "<h3 style='margin-top: 0; color: #3498db;'>📋 Required Action:</h3>";
        echo "<ol style='font-size: 15px; line-height: 1.8;'>";
        echo "<li><strong>Run the migration script</strong> to add missing v2 columns</li>";
        echo "<li><strong>Verify migration success</strong> (should show \"✅ Migration Completed Successfully!\")</li>";
        echo "<li><strong>Return here</strong> to run backward compatibility tests</li>";
        echo "</ol>";
        echo "</div>";

        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<p style='font-size: 14px; color: #666; margin-bottom: 15px;'>Migration Script URL:</p>";
        echo "<pre style='background: #f8f9fa; color: #000; padding: 15px; font-size: 12px; border: 1px solid #ddd; text-align: left; overflow-x: auto;'>";
        echo "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/migrate_decision_log_to_v2_safe.php";
        echo "</pre>";
        echo "<a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/migrate_decision_log_to_v2_safe.php' ";
        echo "style='display: inline-block; padding: 15px 40px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; margin-top: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);' ";
        echo "target='_blank'>";
        echo "🚀 RUN MIGRATION SCRIPT NOW";
        echo "</a>";
        echo "</div>";

        echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px;'>";
        echo "<p style='margin: 0; color: #856404;'><strong>ℹ️ Note:</strong> The migration script is safe to run. It will:</p>";
        echo "<ul style='color: #856404; margin: 10px 0 0 0;'>";
        echo "<li>Preserve all existing data (no data loss)</li>";
        echo "<li>Add missing v2 columns</li>";
        echo "<li>Modify confidence precision</li>";
        echo "<li>Add indexes and foreign keys</li>";
        echo "<li>Can be re-run safely (idempotent)</li>";
        echo "</ul>";
        echo "</div>";

        echo "</div>"; // End error div
        echo "</div>"; // End test-case
        echo "</div></body></html>"; // End document

        $logger->warning("Backward compatibility test halted - schema mismatch detected", [], [
            'missing_columns' => $missing_columns,
            'confidence_needs_upgrade' => $confidence_needs_upgrade,
            'action_required' => 'run_migration_script'
        ]);

        exit; // Stop test execution - migration must be run first
    }

    // Schema validation passed
    echo "<div class='success' style='border-left-width: 6px; padding: 20px;'>";
    echo "✅ <strong>Database Schema Verified</strong><br>";
    echo "<span class='metric'>All v2 columns present: " . count($v2_required_columns) . "</span>";
    echo "<span class='metric'>Confidence precision: DECIMAL(5,4) ✓</span>";
    echo "<p style='margin: 10px 0 0 0; color: #155724;'>Ready to proceed with backward compatibility tests</p>";
    echo "</div>";

    echo "</div>"; // End pre-flight check

    // ============================================================
    // TEST SETUP
    // ============================================================
    echo "<div class='test-case'>";
    echo "<div class='test-header'>Test Setup</div>";

    // Initialize both orchestrators
    $start_init_v1 = microtime(true);
    $orchestrator_v1 = new MVPAgentOrchestrator($USER->id);
    $init_time_v1 = (microtime(true) - $start_init_v1) * 1000;

    $start_init_v2 = microtime(true);
    $orchestrator_v2 = new MVPAgentOrchestrator_v2($USER->id, [
        'enable_cascades' => false,
        'enable_conflict_resolution' => false
    ]);
    $init_time_v2 = (microtime(true) - $start_init_v2) * 1000;

    echo "<div class='success'>";
    echo "✅ <strong>Both orchestrators initialized successfully</strong><br>";
    echo "<span class='metric'>V1 Init: " . round($init_time_v1, 2) . "ms</span>";
    echo "<span class='metric'>V2 Init: " . round($init_time_v2, 2) . "ms</span>";
    echo "<span class='metric'>Difference: " . round($init_time_v2 - $init_time_v1, 2) . "ms</span>";
    echo "</div>";

    echo "</div>"; // End setup

    // ============================================================
    // TEST CASES
    // ============================================================
    echo "<h2>Test Cases</h2>";

    // Define test contexts
    $test_contexts = [
        [
            'name' => 'Low Math Confidence',
            'description' => 'Student with low math confidence (should trigger agent08)',
            'context' => [
                'student_id' => $USER->id,
                'math_confidence' => 3,
                'avg_score' => 45,
                'recent_interaction' => 'quiz_failed'
            ]
        ],
        [
            'name' => 'High Confidence Student',
            'description' => 'Student with high confidence (should trigger agent13 or no match)',
            'context' => [
                'student_id' => $USER->id,
                'math_confidence' => 9,
                'avg_score' => 92,
                'recent_interaction' => 'quiz_passed'
            ]
        ],
        [
            'name' => 'Mid-Range Performance',
            'description' => 'Student with average performance',
            'context' => [
                'student_id' => $USER->id,
                'math_confidence' => 6,
                'avg_score' => 70,
                'recent_interaction' => 'lesson_completed'
            ]
        ],
        [
            'name' => 'Edge Case - Empty Context',
            'description' => 'Empty context (should return no match)',
            'context' => []
        ]
    ];

    $results_summary = [];
    $total_tests = count($test_contexts);
    $passed_tests = 0;
    $failed_tests = 0;

    foreach ($test_contexts as $test) {
        echo "<div class='test-case'>";
        echo "<div class='test-header'>Test: {$test['name']}</div>";
        echo "<p><em>{$test['description']}</em></p>";

        // Display test context
        echo "<h4>Test Context:</h4>";
        echo "<pre>" . htmlspecialchars(json_encode($test['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        // Execute V1
        echo "<h4>V1 Execution:</h4>";
        $start_v1 = microtime(true);
        try {
            $result_v1 = $orchestrator_v1->route_context($test['context']);
            $duration_v1 = (microtime(true) - $start_v1) * 1000;

            echo "<pre>" . htmlspecialchars(json_encode($result_v1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            echo "<span class='metric'>Duration: " . round($duration_v1, 2) . "ms</span>";
        } catch (Exception $e) {
            $duration_v1 = (microtime(true) - $start_v1) * 1000;
            echo "<div class='error'>❌ V1 Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            $result_v1 = null;
        }

        // Execute V2
        echo "<h4>V2 Execution (cascades=false, conflicts=false):</h4>";
        $start_v2 = microtime(true);
        try {
            $result_v2 = $orchestrator_v2->process_context($test['context']);
            $duration_v2 = (microtime(true) - $start_v2) * 1000;

            echo "<pre>" . htmlspecialchars(json_encode($result_v2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            echo "<span class='metric'>Duration: " . round($duration_v2, 2) . "ms</span>";
        } catch (Exception $e) {
            $duration_v2 = (microtime(true) - $start_v2) * 1000;
            echo "<div class='error'>";
            echo "<strong>❌ V2 Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
            echo "<strong>Stack Trace:</strong><br>";
            echo "<pre style='font-size: 11px; max-height: 200px; overflow-y: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
            $result_v2 = null;
        }

        // Compare results
        echo "<h4>Comparison:</h4>";

        $comparison = [
            'test_name' => $test['name'],
            'v1_matched' => $result_v1 !== null,
            'v2_matched' => $result_v2 !== null && $result_v2['success'],
            'v1_agent' => $result_v1['agent_id'] ?? null,
            'v2_agent' => $result_v2['agent_id'] ?? null,
            'v1_confidence' => $result_v1['confidence'] ?? null,
            'v2_confidence' => null,
            'v1_duration' => $duration_v1,
            'v2_duration' => $duration_v2,
            'performance_diff' => $duration_v2 - $duration_v1,
            'passed' => false
        ];

        // Extract v2 confidence from initial_result
        if ($result_v2 && isset($result_v2['initial_result']['confidence'])) {
            $comparison['v2_confidence'] = $result_v2['initial_result']['confidence'];
        }

        // Determine if test passed
        $agent_match = ($comparison['v1_agent'] === $comparison['v2_agent']);
        $confidence_match = abs(($comparison['v1_confidence'] ?? 0) - ($comparison['v2_confidence'] ?? 0)) < 0.01;
        $both_null = ($result_v1 === null && ($result_v2 === null || !$result_v2['success']));

        if ($agent_match && $confidence_match) {
            $comparison['passed'] = true;
            $passed_tests++;
            echo "<div class='success'>✅ <strong>TEST PASSED</strong> - Both versions produced identical results</div>";
        } elseif ($both_null) {
            $comparison['passed'] = true;
            $passed_tests++;
            echo "<div class='success'>✅ <strong>TEST PASSED</strong> - Both versions correctly returned no match</div>";
        } else {
            $failed_tests++;
            echo "<div class='error'>❌ <strong>TEST FAILED</strong> - Results differ between versions</div>";

            if (!$agent_match) {
                echo "<p><strong>Agent Mismatch:</strong> V1={$comparison['v1_agent']}, V2={$comparison['v2_agent']}</p>";
            }
            if (!$confidence_match) {
                echo "<p><strong>Confidence Mismatch:</strong> V1={$comparison['v1_confidence']}, V2={$comparison['v2_confidence']}</p>";
            }
        }

        // Performance comparison
        $perf_diff_pct = ($duration_v1 > 0) ? (($duration_v2 - $duration_v1) / $duration_v1) * 100 : 0;

        echo "<table>";
        echo "<tr><th>Metric</th><th>V1</th><th>V2</th><th>Difference</th></tr>";
        echo "<tr><td>Matched Agent</td><td>" . ($comparison['v1_agent'] ?? 'None') . "</td><td>" . ($comparison['v2_agent'] ?? 'None') . "</td>";
        echo "<td class='" . ($agent_match ? 'match' : 'diff') . "'>" . ($agent_match ? '✓ Match' : '✗ Diff') . "</td></tr>";
        echo "<tr><td>Confidence</td><td>" . ($comparison['v1_confidence'] ?? 'N/A') . "</td><td>" . ($comparison['v2_confidence'] ?? 'N/A') . "</td>";
        echo "<td class='" . ($confidence_match ? 'match' : 'diff') . "'>" . ($confidence_match ? '✓ Match' : '✗ Diff') . "</td></tr>";
        echo "<tr><td>Duration</td><td>" . round($duration_v1, 2) . "ms</td><td>" . round($duration_v2, 2) . "ms</td>";
        echo "<td>" . round($comparison['performance_diff'], 2) . "ms (" . round($perf_diff_pct, 1) . "%)</td></tr>";
        echo "</table>";

        $results_summary[] = $comparison;

        echo "</div>"; // End test case
    }

    // ============================================================
    // SUMMARY
    // ============================================================
    echo "<h2>Test Summary</h2>";

    $pass_rate = ($total_tests > 0) ? ($passed_tests / $total_tests) * 100 : 0;

    if ($pass_rate === 100.0) {
        echo "<div class='success'>";
        echo "<h3>✅ All Tests Passed</h3>";
        echo "<p><strong>Passed:</strong> {$passed_tests} / {$total_tests}</p>";
        echo "<p><strong>Pass Rate:</strong> 100%</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Some Tests Failed</h3>";
        echo "<p><strong>Passed:</strong> {$passed_tests} / {$total_tests}</p>";
        echo "<p><strong>Failed:</strong> {$failed_tests} / {$total_tests}</p>";
        echo "<p><strong>Pass Rate:</strong> " . round($pass_rate, 1) . "%</p>";
        echo "</div>";
    }

    // Performance summary
    echo "<h3>Performance Comparison</h3>";
    echo "<table>";
    echo "<tr><th>Test Case</th><th>V1 Duration</th><th>V2 Duration</th><th>Difference</th><th>% Change</th></tr>";

    $total_v1 = 0;
    $total_v2 = 0;

    foreach ($results_summary as $result) {
        $total_v1 += $result['v1_duration'];
        $total_v2 += $result['v2_duration'];

        $perf_diff_pct = ($result['v1_duration'] > 0) ? (($result['v2_duration'] - $result['v1_duration']) / $result['v1_duration']) * 100 : 0;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($result['test_name']) . "</td>";
        echo "<td>" . round($result['v1_duration'], 2) . "ms</td>";
        echo "<td>" . round($result['v2_duration'], 2) . "ms</td>";
        echo "<td>" . round($result['performance_diff'], 2) . "ms</td>";
        echo "<td>" . ($perf_diff_pct >= 0 ? '+' : '') . round($perf_diff_pct, 1) . "%</td>";
        echo "</tr>";
    }

    $avg_v1 = $total_tests > 0 ? $total_v1 / $total_tests : 0;
    $avg_v2 = $total_tests > 0 ? $total_v2 / $total_tests : 0;
    $avg_diff = $avg_v2 - $avg_v1;
    $avg_diff_pct = ($avg_v1 > 0) ? ($avg_diff / $avg_v1) * 100 : 0;

    echo "<tr style='font-weight: bold; background: #f8f9fa;'>";
    echo "<td>Average</td>";
    echo "<td>" . round($avg_v1, 2) . "ms</td>";
    echo "<td>" . round($avg_v2, 2) . "ms</td>";
    echo "<td>" . round($avg_diff, 2) . "ms</td>";
    echo "<td>" . ($avg_diff_pct >= 0 ? '+' : '') . round($avg_diff_pct, 1) . "%</td>";
    echo "</tr>";
    echo "</table>";

    // ============================================================
    // CONCLUSIONS
    // ============================================================
    echo "<h2>Conclusions</h2>";

    if ($pass_rate === 100.0) {
        echo "<div class='success'>";
        echo "<h3>✅ Backward Compatibility: VERIFIED</h3>";
        echo "<ul>";
        echo "<li>MVPAgentOrchestrator_v2 produces identical results to v1 when graph features are disabled</li>";
        echo "<li>All test cases passed with matching agent selection and confidence scores</li>";
        echo "<li>Performance overhead is acceptable: +" . round($avg_diff_pct, 1) . "% average (" . round($avg_diff, 2) . "ms)</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Backward Compatibility: ISSUES DETECTED</h3>";
        echo "<ul>";
        echo "<li>{$failed_tests} test case(s) failed with different results between v1 and v2</li>";
        echo "<li>Investigation required before proceeding with agent migration</li>";
        echo "</ul>";
        echo "</div>";
    }

    // Performance assessment
    if ($avg_diff_pct <= 20) {
        echo "<div class='success'>";
        echo "<h3>✅ Performance: ACCEPTABLE</h3>";
        echo "<p>V2 performance overhead of " . round($avg_diff_pct, 1) . "% is within acceptable range (target: ≤20%)</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Performance: NEEDS OPTIMIZATION</h3>";
        echo "<p>V2 performance overhead of " . round($avg_diff_pct, 1) . "% exceeds acceptable range (target: ≤20%)</p>";
        echo "<p>Consider optimization before production deployment</p>";
        echo "</div>";
    }

    // Next steps
    echo "<div class='info'>";
    echo "<h3>📋 Next Steps</h3>";

    if ($pass_rate === 100.0 && $avg_diff_pct <= 20) {
        echo "<ol>";
        echo "<li>✅ Backward compatibility verified - safe to proceed</li>";
        echo "<li>Begin agent migration with critical agents (agent08, agent13, agent05)</li>";
        echo "<li>Add relationship fields to YAML files</li>";
        echo "<li>Test cascade and conflict resolution features</li>";
        echo "</ol>";
    } else {
        echo "<ol>";
        echo "<li>❌ Fix compatibility issues before proceeding</li>";
        echo "<li>Investigate failing test cases</li>";
        echo "<li>Optimize performance if needed</li>";
        echo "<li>Re-run tests until 100% pass rate achieved</li>";
        echo "</ol>";
    }

    echo "</div>";

    $logger->info("Backward compatibility test completed", [], [
        'total_tests' => $total_tests,
        'passed' => $passed_tests,
        'failed' => $failed_tests,
        'pass_rate' => $pass_rate,
        'avg_v1_duration' => $avg_v1,
        'avg_v2_duration' => $avg_v2,
        'performance_overhead_pct' => $avg_diff_pct
    ]);

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Test Execution Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";

    $logger->error("Backward compatibility test failed", $e, [
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
 * File Location: mvp_system/tests/test_backward_compatibility.php (Line 1)
 * Purpose: Verify MVPAgentOrchestrator_v2 backward compatibility with v1
 *
 * Test Cases:
 * 1. Low Math Confidence - Should trigger agent08 consistently
 * 2. High Confidence Student - Should trigger agent13 or no match
 * 3. Mid-Range Performance - Should match appropriate agent
 * 4. Edge Case - Empty context should return no match
 *
 * Success Criteria:
 * - 100% pass rate (identical results between v1 and v2)
 * - Performance overhead ≤20%
 * - No exceptions thrown during execution
 *
 * Dependencies:
 * - MVPAgentOrchestrator.php (v1)
 * - MVPAgentOrchestrator_v2.php (v2)
 * - RuleGraphBuilder.php
 * - CascadeEngine.php
 * - ConflictResolver.php
 * - MVPLogger.php
 * - MVPDatabase.php
 */
?>
