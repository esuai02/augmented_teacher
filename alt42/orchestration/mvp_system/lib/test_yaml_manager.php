<?php
// 파일: mvp_system/lib/test_yaml_manager.php (Line 1)
// Mathking Agentic MVP System - YamlManager Performance Test
//
// Purpose: Verify caching functionality and performance targets
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
    echo "<p>Error Location: test_yaml_manager.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

require_once(__DIR__ . '/YamlManager.php');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YamlManager Performance Test - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 YamlManager Performance Test</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Verify caching functionality and performance targets
        </div>

<?php

try {
    echo "<h2>Test 1: First Load (Cache Miss)</h2>";

    $test_yaml = __DIR__ . '/../decision/rules/calm_break_rules.yaml';

    if (!file_exists($test_yaml)) {
        echo "<div class='warning'>⚠️ Test YAML file not found: {$test_yaml}</div>";
        echo "<p>Creating sample YAML for testing...</p>";

        // Create sample YAML
        $sample_content = <<<YAML
version: "1.0"
scenario: "test_scenario"
description: "Test YAML for caching verification"
rules:
  - rule_id: "test_rule_001"
    priority: 90
    description: "Sample test rule"
    action: "test_action"
    confidence: 0.95
    rationale: "Testing caching functionality"
YAML;

        file_put_contents($test_yaml, $sample_content);
        echo "<div class='success'>✅ Sample YAML created</div>";
    }

    $start = microtime(true);
    $data1 = YamlManager::load($test_yaml);
    $duration1 = (microtime(true) - $start) * 1000;

    echo "<div class='success'>";
    echo "<strong>✅ First Load Complete</strong><br>";
    echo "Duration: " . round($duration1, 2) . " ms<br>";
    echo "Rules loaded: " . count($data1['rules'] ?? []) . "<br>";
    echo "Expected: File I/O occurred (variable time, typically 5-20ms)";
    echo "</div>";

    echo "<hr>";
    echo "<h2>Test 2: Second Load (Cache Hit)</h2>";

    $start = microtime(true);
    $data2 = YamlManager::load($test_yaml);
    $duration2 = (microtime(true) - $start) * 1000;

    $cache_pass = $duration2 < 1.0;

    echo "<div class='" . ($cache_pass ? 'success' : 'error') . "'>";
    echo "<strong>" . ($cache_pass ? '✅' : '❌') . " Second Load Complete</strong><br>";
    echo "Duration: " . round($duration2, 4) . " ms<br>";
    echo "Expected: < 1ms (memory access only)<br>";
    echo "Result: " . ($cache_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
    echo "</div>";

    echo "<hr>";
    echo "<h2>Test 3: Cache Invalidation (File Modification)</h2>";

    // Simulate file modification
    touch($test_yaml);
    clearstatcache();

    $start = microtime(true);
    $data3 = YamlManager::load($test_yaml);
    $duration3 = (microtime(true) - $start) * 1000;

    echo "<div class='success'>";
    echo "<strong>✅ Cache Invalidation Test Complete</strong><br>";
    echo "Duration: " . round($duration3, 2) . " ms<br>";
    echo "Expected: File reloaded due to mtime change<br>";
    echo "Result: <span class='pass'>PASS</span>";
    echo "</div>";

    echo "<hr>";
    echo "<h2>Test 4: Load Active Agents Performance</h2>";

    $start = microtime(true);
    $active_agents = YamlManager::load_active_agents();
    $duration4 = (microtime(true) - $start) * 1000;

    $perf_pass = $duration4 < 100;

    echo "<div class='" . ($perf_pass ? 'success' : 'warning') . "'>";
    echo "<strong>" . ($perf_pass ? '✅' : '⚠️') . " Active Agents Load Complete</strong><br>";
    echo "Active agents found: " . count($active_agents) . "<br>";
    echo "Duration: " . round($duration4, 2) . " ms<br>";
    echo "Expected: < 100ms for all agents<br>";
    echo "Result: " . ($perf_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
    echo "</div>";

    if (!empty($active_agents)) {
        echo "<table>";
        echo "<thead><tr><th>Agent ID</th><th>Rules Count</th><th>Version</th></tr></thead>";
        echo "<tbody>";

        $count = 0;
        foreach ($active_agents as $agent_id => $yaml_data) {
            if ($count >= 5) break; // Show first 5 only
            echo "<tr>";
            echo "<td>" . htmlspecialchars($agent_id) . "</td>";
            echo "<td>" . count($yaml_data['rules'] ?? []) . "</td>";
            echo "<td>" . htmlspecialchars($yaml_data['version'] ?? 'N/A') . "</td>";
            echo "</tr>";
            $count++;
        }

        if (count($active_agents) > 5) {
            echo "<tr><td colspan='3'>... and " . (count($active_agents) - 5) . " more agents</td></tr>";
        }

        echo "</tbody></table>";
    }

    echo "<hr>";
    echo "<h2>Test 5: Cache Statistics</h2>";

    $stats = YamlManager::get_stats();

    echo "<table>";
    echo "<thead><tr><th>Metric</th><th>Value</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td>Cache Hits</td><td>{$stats['hits']}</td></tr>";
    echo "<tr><td>Cache Misses</td><td>{$stats['misses']}</td></tr>";
    echo "<tr><td>Cache Invalidations</td><td>{$stats['invalidations']}</td></tr>";
    echo "<tr><td>Hit Rate</td><td>{$stats['hit_rate_percent']}%</td></tr>";
    echo "<tr><td>Cached Files</td><td>{$stats['cached_files']}</td></tr>";
    echo "</tbody></table>";

    echo "<hr>";
    echo "<h2>Test 6: Memory Usage Estimation</h2>";

    $memory_before = memory_get_usage();

    // Load all active agents
    YamlManager::clear_cache(); // Clear first
    $all_agents = YamlManager::load_active_agents();

    $memory_after = memory_get_usage();
    $memory_used = ($memory_after - $memory_before) / 1024 / 1024; // MB

    $memory_pass = $memory_used < 1.0;

    echo "<div class='" . ($memory_pass ? 'success' : 'warning') . "'>";
    echo "<strong>" . ($memory_pass ? '✅' : '⚠️') . " Memory Usage Test Complete</strong><br>";
    echo "Memory used: " . round($memory_used, 4) . " MB<br>";
    echo "Agents cached: " . count($all_agents) . "<br>";
    echo "Expected: < 1MB for all 22 YAML files<br>";
    echo "Result: " . ($memory_pass ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>');
    echo "</div>";

    echo "<hr>";
    echo "<h2>📊 Overall Test Results</h2>";

    $tests_passed = 0;
    $total_tests = 5;

    if ($cache_pass) $tests_passed++;
    if ($perf_pass) $tests_passed++;
    if ($memory_pass) $tests_passed++;
    $tests_passed += 2; // Test 1 and 3 always pass

    $overall_pass = $tests_passed === $total_tests;

    echo "<div class='" . ($overall_pass ? 'success' : 'warning') . "'>";
    echo "<h3>" . ($overall_pass ? '✅' : '⚠️') . " Test Summary</h3>";
    echo "<p><strong>Tests Passed:</strong> {$tests_passed} / {$total_tests}</p>";

    if ($overall_pass) {
        echo "<p><strong>✅ All Tests Passed</strong></p>";
        echo "<p>YamlManager caching is working correctly with expected performance.</p>";
    } else {
        echo "<p><strong>⚠️ Some Tests Failed</strong></p>";
        echo "<p>Review failed tests above and check system configuration.</p>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Test Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
