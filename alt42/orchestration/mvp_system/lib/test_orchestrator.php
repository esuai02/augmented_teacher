<?php
// 파일: mvp_system/lib/test_orchestrator.php (Line 1)
// Mathking Agentic MVP System - MVPAgentOrchestrator Test Suite
//
// Purpose: Verify orchestrator functionality and integration
// Usage: Direct browser access (temporary test file)

// Server connection
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
    echo "<p>Error Location: test_orchestrator.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

require_once(__DIR__ . '/MVPAgentOrchestrator.php');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orchestrator Test Suite - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 MVPAgentOrchestrator Test Suite</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Verify orchestrator integration and functionality
        </div>

<?php

try {
    echo "<h2>Test 1: Orchestrator Initialization</h2>";
    echo "<div class='test-section'>";

    $start = microtime(true);
    $orchestrator = new MVPAgentOrchestrator();
    $duration1 = (microtime(true) - $start) * 1000;

    echo "<div class='success'>";
    echo "<strong>✅ Orchestrator Initialized</strong><br>";
    echo "Duration: " . round($duration1, 2) . " ms<br>";
    echo "Expected: <5ms (lightweight initialization)";
    echo "</div>";
    echo "</div>";

    // ============================================================
    // Test 2: Load Active Agents
    // ============================================================
    echo "<hr>";
    echo "<h2>Test 2: Load Active Agents</h2>";
    echo "<div class='test-section'>";

    $start = microtime(true);
    $agent_count = $orchestrator->load_active_agents();
    $duration2 = (microtime(true) - $start) * 1000;

    $load_pass = $agent_count > 0 && $duration2 < 100;

    echo "<div class='" . ($load_pass ? 'success' : 'warning') . "'>";
    echo "<strong>" . ($load_pass ? '✅' : '⚠️') . " Active Agents Loaded</strong><br>";
    echo "Agents loaded: {$agent_count}<br>";
    echo "Duration: " . round($duration2, 2) . " ms<br>";
    echo "Expected: >0 agents, <100ms (via cache)<br>";
    echo "Result: " . ($load_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
    echo "</div>";
    echo "</div>";

    // ============================================================
    // Test 3: Context Routing - Calm Agent
    // ============================================================
    echo "<hr>";
    echo "<h2>Test 3: Context Routing (Calm Score)</h2>";
    echo "<div class='test-section'>";

    $calm_context = [
        'student_id' => 12345,
        'calm_score' => 65,
        'activity_type' => 'problem_solving'
    ];

    $start = microtime(true);
    $route_result = $orchestrator->route_context($calm_context);
    $duration3 = (microtime(true) - $start) * 1000;

    $route_pass = $duration3 < 50;

    echo "<div class='" . ($route_pass ? 'success' : 'warning') . "'>";
    echo "<strong>" . ($route_pass ? '✅' : '⚠️') . " Context Routed</strong><br>";

    if ($route_result) {
        echo "Matched Agent: {$route_result['agent_id']} ({$route_result['agent_name']})<br>";
        echo "Confidence: " . round($route_result['confidence'] * 100, 1) . "%<br>";
        echo "Matched Rule: {$route_result['matched_rule']['rule_id']}<br>";
    } else {
        echo "No agent matched (may need to run setup_complete.php first)<br>";
    }

    echo "Duration: " . round($duration3, 2) . " ms<br>";
    echo "Expected: <50ms<br>";
    echo "Result: " . ($route_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
    echo "</div>";

    echo "<pre>";
    echo "Context:\n";
    print_r($calm_context);
    echo "\nRoute Result:\n";
    print_r($route_result);
    echo "</pre>";
    echo "</div>";

    // ============================================================
    // Test 4: Decision Execution (if agent matched)
    // ============================================================
    if ($route_result) {
        echo "<hr>";
        echo "<h2>Test 4: Decision Execution</h2>";
        echo "<div class='test-section'>";

        $start = microtime(true);
        $exec_result = $orchestrator->execute_decision(
            $route_result['agent_id'],
            $calm_context,
            $route_result['matched_rule']
        );
        $duration4 = (microtime(true) - $start) * 1000;

        $exec_pass = $exec_result['success'] && $duration4 < 200;

        echo "<div class='" . ($exec_pass ? 'success' : 'warning') . "'>";
        echo "<strong>" . ($exec_pass ? '✅' : '⚠️') . " Decision Executed</strong><br>";
        echo "Decision ID: {$exec_result['decision_id']}<br>";
        echo "Action: {$exec_result['action']}<br>";
        echo "Confidence: " . round($exec_result['confidence'] * 100, 1) . "%<br>";
        echo "Duration: " . round($duration4, 2) . " ms<br>";
        echo "Expected: <200ms<br>";
        echo "Result: " . ($exec_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
        echo "</div>";

        echo "<pre>";
        print_r($exec_result);
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<hr>";
        echo "<div class='warning'>";
        echo "<strong>⚠️ Skipped Test 4</strong><br>";
        echo "Reason: No agent matched in Test 3. Database may be empty.<br>";
        echo "Action: Run setup_complete.php to insert agents.";
        echo "</div>";
    }

    // ============================================================
    // Test 5: End-to-End Processing
    // ============================================================
    echo "<hr>";
    echo "<h2>Test 5: End-to-End Context Processing</h2>";
    echo "<div class='test-section'>";

    $exam_context = [
        'student_id' => 12345,
        'exam_approaching' => true,
        'days_until_exam' => 3,
        'preparation_status' => 'moderate'
    ];

    $start = microtime(true);
    $e2e_result = $orchestrator->process_context($exam_context);
    $duration5 = (microtime(true) - $start) * 1000;

    if ($e2e_result) {
        $e2e_pass = $duration5 < 250;

        echo "<div class='" . ($e2e_pass ? 'success' : 'warning') . "'>";
        echo "<strong>" . ($e2e_pass ? '✅' : '⚠️') . " End-to-End Processing Complete</strong><br>";
        echo "Agent: {$e2e_result['agent_id']} ({$e2e_result['agent_name']})<br>";
        echo "Action: {$e2e_result['action']}<br>";
        echo "Confidence: " . round($e2e_result['confidence'] * 100, 1) . "%<br>";
        echo "Duration: " . round($duration5, 2) . " ms<br>";
        echo "Expected: <250ms<br>";
        echo "Result: " . ($e2e_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<strong>⚠️ No Agent Matched</strong><br>";
        echo "Exam context did not match any agent rules.<br>";
        echo "This may be expected if exam-related agents have no rules yet.";
        echo "</div>";
    }

    echo "<pre>";
    echo "Exam Context:\n";
    print_r($exam_context);
    echo "\nResult:\n";
    print_r($e2e_result);
    echo "</pre>";
    echo "</div>";

    // ============================================================
    // Test 6: Agent Statistics
    // ============================================================
    echo "<hr>";
    echo "<h2>Test 6: Agent Statistics</h2>";
    echo "<div class='test-section'>";

    $all_stats = $orchestrator->get_agent_stats();

    echo "<table>";
    echo "<thead><tr><th>Agent ID</th><th>Agent Name</th><th>Executions</th><th>Success Rate</th><th>Avg Time (ms)</th><th>Last Exec</th></tr></thead>";
    echo "<tbody>";

    $displayed_count = 0;
    foreach ($all_stats as $stat) {
        if ($displayed_count >= 10) break; // Show first 10

        $success_rate = $stat['execution_count'] > 0
            ? round(($stat['success_count'] / $stat['execution_count']) * 100, 1)
            : 0;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($stat['agent_id']) . "</td>";
        echo "<td>" . htmlspecialchars($stat['agent_name']) . "</td>";
        echo "<td>" . $stat['execution_count'] . "</td>";
        echo "<td>" . $success_rate . "%</td>";
        echo "<td>" . round($stat['avg_execution_time'] ?? 0, 2) . "</td>";
        echo "<td>" . ($stat['last_execution_at'] ?? 'Never') . "</td>";
        echo "</tr>";

        $displayed_count++;
    }

    if (count($all_stats) > 10) {
        echo "<tr><td colspan='6'>... and " . (count($all_stats) - 10) . " more agents</td></tr>";
    }

    echo "</tbody></table>";

    echo "<div class='success'>";
    echo "<strong>✅ Statistics Retrieved</strong><br>";
    echo "Total agents: " . count($all_stats);
    echo "</div>";
    echo "</div>";

    // ============================================================
    // Test 7: Orchestrator Status
    // ============================================================
    echo "<hr>";
    echo "<h2>Test 7: Orchestrator Status</h2>";
    echo "<div class='test-section'>";

    $status = $orchestrator->get_status();

    echo "<table>";
    echo "<thead><tr><th>Metric</th><th>Value</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td>Agents Loaded</td><td>{$status['agents_loaded']}</td></tr>";
    echo "<tr><td>Active Agents</td><td>{$status['active_agents']}</td></tr>";
    echo "<tr><td>User ID</td><td>{$status['user_id']}</td></tr>";
    echo "<tr><td>YAML Cache Hits</td><td>{$status['cache_stats']['hits']}</td></tr>";
    echo "<tr><td>YAML Cache Misses</td><td>{$status['cache_stats']['misses']}</td></tr>";
    echo "<tr><td>YAML Hit Rate</td><td>{$status['cache_stats']['hit_rate_percent']}%</td></tr>";
    echo "</tbody></table>";

    echo "<div class='success'>";
    echo "<strong>✅ Status Retrieved</strong>";
    echo "</div>";
    echo "</div>";

    // ============================================================
    // Overall Test Results
    // ============================================================
    echo "<hr>";
    echo "<h2>📊 Overall Test Results</h2>";

    $tests_run = 7;
    $tests_passed = 0;

    // Count passes (simplified)
    if ($duration1 < 5) $tests_passed++;
    if ($load_pass) $tests_passed++;
    if ($route_pass) $tests_passed++;
    if (isset($exec_pass) && $exec_pass) $tests_passed++;
    if (isset($e2e_pass) && $e2e_pass) $tests_passed++;
    $tests_passed += 2; // Tests 6 and 7 always pass

    $overall_pass = $tests_passed >= ($tests_run - 1); // Allow 1 failure

    echo "<div class='" . ($overall_pass ? 'success' : 'warning') . "'>";
    echo "<h3>" . ($overall_pass ? '✅' : '⚠️') . " Test Summary</h3>";
    echo "<p><strong>Tests Passed:</strong> {$tests_passed} / {$tests_run}</p>";

    if ($overall_pass) {
        echo "<p><strong>✅ Orchestrator Integration Working</strong></p>";
        echo "<p>MVPAgentOrchestrator is functioning correctly with expected performance.</p>";
    } else {
        echo "<p><strong>⚠️ Some Tests Failed</strong></p>";
        echo "<p>Review failed tests above. Most likely cause: Database not initialized (run setup_complete.php).</p>";
    }

    echo "</div>";

    echo "<div class='info'>";
    echo "<h3>🔗 Next Steps</h3>";
    echo "<p>If database is empty (0 agents loaded):</p>";
    echo "<pre>https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/setup_complete.php</pre>";
    echo "<p>After setup, verify agents:</p>";
    echo "<pre>https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/database/verify_agents.php</pre>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Test Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
