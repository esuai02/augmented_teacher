<?php
/**
 * YAML Parse Diagnostic Script
 *
 * Purpose: Debug why loadAgentRules(4) returns 0 rules
 * Tests both yaml_parse() and custom parser to identify the issue
 *
 * Created: 2025-12-09
 *
 * Usage:
 *   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/yaml_parse_diagnostic.php
 */

// Moodle integration
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Security: Admin only
if (!is_siteadmin()) {
    die("Error: Admin access required - File: " . __FILE__ . ", Line: " . __LINE__);
}

// Output format
$isJson = isset($_GET['format']) && $_GET['format'] === 'json';

// Initialize diagnostic data
$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'checks' => []
];

// Helper function
function addCheck($name, $passed, $message, $data = []) {
    global $diagnostic;
    $diagnostic['checks'][$name] = [
        'passed' => $passed,
        'message' => $message,
        'data' => $data
    ];
}

// ============================================================
// CHECK 1: yaml_parse function availability
// ============================================================
$hasYamlParse = function_exists('yaml_parse');
addCheck('yaml_parse_available', $hasYamlParse,
    $hasYamlParse ? 'yaml_parse() is available' : 'yaml_parse() NOT available - using custom parser');

// ============================================================
// CHECK 2: rules.yaml file existence
// ============================================================
$orchestrationPath = '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration';
$rulesPath = $orchestrationPath . '/agents/agent04_inspect_weakpoints/rules/rules.yaml';
$fileExists = file_exists($rulesPath);
$fileSize = $fileExists ? filesize($rulesPath) : 0;

addCheck('rules_file_exists', $fileExists,
    $fileExists ? "File exists ({$fileSize} bytes)" : "File NOT found: {$rulesPath}",
    ['path' => $rulesPath, 'size' => $fileSize]);

if (!$fileExists) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// CHECK 3: Read file content
// ============================================================
$content = file_get_contents($rulesPath);
$contentLength = strlen($content);
$firstLines = implode("\n", array_slice(explode("\n", $content), 0, 20));

addCheck('file_read', $content !== false,
    "Read {$contentLength} characters",
    ['length' => $contentLength, 'first_20_lines' => $firstLines]);

// ============================================================
// CHECK 4: Test yaml_parse if available
// ============================================================
if ($hasYamlParse) {
    $yamlResult = @yaml_parse($content);
    $yamlSuccess = $yamlResult !== false;

    if ($yamlSuccess) {
        $topLevelKeys = array_keys($yamlResult);
        $hasRulesKey = isset($yamlResult['rules']);
        $rulesCount = $hasRulesKey ? count($yamlResult['rules']) : 0;

        // Examine first rule structure
        $firstRuleStructure = [];
        if ($hasRulesKey && $rulesCount > 0) {
            $firstRule = $yamlResult['rules'][0];
            $firstRuleStructure = [
                'type' => gettype($firstRule),
                'is_array' => is_array($firstRule),
                'keys' => is_array($firstRule) ? array_keys($firstRule) : [],
                'has_rule_id' => is_array($firstRule) && isset($firstRule['rule_id']),
                'rule_id_value' => is_array($firstRule) && isset($firstRule['rule_id']) ? $firstRule['rule_id'] : null
            ];
        }

        addCheck('yaml_parse_result', true,
            "Parsed successfully. Rules count: {$rulesCount}",
            [
                'top_level_keys' => $topLevelKeys,
                'has_rules_key' => $hasRulesKey,
                'rules_count' => $rulesCount,
                'first_rule_structure' => $firstRuleStructure
            ]);
    } else {
        addCheck('yaml_parse_result', false, 'yaml_parse() returned false');
    }
}

// ============================================================
// CHECK 5: Test RuleYamlLoader
// ============================================================
require_once($orchestrationPath . '/shared/quantum/RuleYamlLoader.php');

try {
    // Create loader with debug mode
    $loader = new RuleYamlLoader($orchestrationPath, true);

    // Test loadAgentRules
    $rules = $loader->loadAgentRules(4, false); // No cache
    $loadedCount = is_array($rules) ? count($rules) : 0;

    // Examine loaded rules structure
    $loadedStructure = [];
    if ($loadedCount > 0) {
        $firstLoaded = reset($rules);
        $loadedStructure = [
            'type' => gettype($firstLoaded),
            'is_array' => is_array($firstLoaded),
            'keys' => is_array($firstLoaded) ? array_keys($firstLoaded) : [],
            'sample' => $firstLoaded
        ];
    }

    addCheck('loader_result', $loadedCount > 0,
        "RuleYamlLoader returned {$loadedCount} rules",
        [
            'rules_count' => $loadedCount,
            'first_rule_structure' => $loadedStructure
        ]);

    // Get loader stats
    $stats = $loader->getStats();
    addCheck('loader_stats', true, 'Loader statistics', $stats);

    // Get debug logs from loader
    $debugLogs = $loader->getDebugLogs();
    addCheck('debug_logs', count($debugLogs) > 0,
        "Captured " . count($debugLogs) . " debug log entries",
        [
            'log_count' => count($debugLogs),
            'logs' => $debugLogs
        ]);

} catch (Exception $e) {
    addCheck('loader_result', false,
        "Exception: " . $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine()]);
}

// ============================================================
// CHECK 6: Manual parsing test - extract first rule
// ============================================================
// Find the first rule manually
preg_match('/- rule_id:\s*["\']?([^"\']+)["\']?/', $content, $matches);
$foundRuleId = isset($matches[1]) ? $matches[1] : 'NOT FOUND';

// Check if content has BOM or encoding issues
$hasBOM = substr($content, 0, 3) === "\xEF\xBB\xBF";
$encoding = mb_detect_encoding($content, ['UTF-8', 'ASCII', 'ISO-8859-1'], true);

addCheck('content_analysis', true, 'Content analysis',
    [
        'first_rule_id_by_regex' => $foundRuleId,
        'has_bom' => $hasBOM,
        'detected_encoding' => $encoding,
        'line_ending' => strpos($content, "\r\n") !== false ? 'CRLF' : 'LF'
    ]);

// ============================================================
// CHECK 7: Detailed normalizeRules test
// ============================================================
if ($hasYamlParse && isset($yamlResult['rules'])) {
    $rulesArray = $yamlResult['rules'];
    $normalizeTest = [];

    foreach ($rulesArray as $idx => $rule) {
        if ($idx >= 3) break; // Only check first 3

        $normalizeTest[$idx] = [
            'is_array' => is_array($rule),
            'has_rule_id' => is_array($rule) && isset($rule['rule_id']),
            'rule_id' => is_array($rule) && isset($rule['rule_id']) ? $rule['rule_id'] : null,
            'would_be_skipped' => !is_array($rule) || !isset($rule['rule_id'])
        ];
    }

    addCheck('normalize_test', true, 'Normalize rules test (first 3)', $normalizeTest);
}

// ============================================================
// OUTPUT
// ============================================================
$passedCount = 0;
$totalCount = count($diagnostic['checks']);
foreach ($diagnostic['checks'] as $check) {
    if ($check['passed']) $passedCount++;
}

$diagnostic['summary'] = [
    'passed' => $passedCount,
    'total' => $totalCount,
    'percentage' => round(($passedCount / $totalCount) * 100, 1)
];

if ($isJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML Output
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/yaml_parse_diagnostic.php');
$PAGE->set_title('YAML Parse Diagnostic');

echo $OUTPUT->header();
echo $OUTPUT->heading('YAML Parse Diagnostic', 2);
echo "<p><strong>Timestamp:</strong> {$diagnostic['timestamp']}</p>";
echo "<p><strong>PHP Version:</strong> {$diagnostic['php_version']}</p>";
echo "<hr>";

// Summary
$summaryColor = $passedCount === $totalCount ? 'green' : 'orange';
echo "<h3>Summary: <span style='color: {$summaryColor};'>{$passedCount}/{$totalCount} checks passed</span></h3>";

// Detail
foreach ($diagnostic['checks'] as $name => $check) {
    $icon = $check['passed'] ? '✅' : '❌';
    $color = $check['passed'] ? 'green' : 'red';

    echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h4 style='color: {$color}; margin: 0 0 10px 0;'>{$icon} {$name}</h4>";
    echo "<p><strong>Message:</strong> {$check['message']}</p>";

    if (!empty($check['data'])) {
        echo "<details><summary>Details</summary>";
        echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($check['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre></details>";
    }
    echo "</div>";
}

// Links
echo "<hr>";
echo "<h4>Related Links</h4>";
echo "<ul>";
echo "<li><a href='test_yaml_loader_standalone.php'>Standalone Tests</a></li>";
echo "<li><a href='test_phase1_integration.php'>Phase 1 Integration Tests</a></li>";
echo "<li><a href='?format=json'>JSON API</a></li>";
echo "</ul>";

echo $OUTPUT->footer();

// ============================================================
// Related Database Tables
// ============================================================
// N/A - This is a diagnostic script for YAML parsing
// ============================================================
