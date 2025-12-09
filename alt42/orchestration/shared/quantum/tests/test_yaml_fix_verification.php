<?php
/**
 * YAML Parser Fix Verification Script
 *
 * Purpose: Verify that the customYamlParse() fix correctly parses agent04 rules.yaml
 * Tests the priority fix for nested condition fields
 *
 * Created: 2025-12-09
 * Issue: customYamlParse() was parsing conditions incorrectly due to execution order
 *
 * Usage:
 *   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_yaml_fix_verification.php
 *
 * Expected Result: rules should load with proper field/operator/value structure
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

header('Content-Type: ' . ($isJson ? 'application/json' : 'text/html') . '; charset=utf-8');

// Initialize results
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_name' => 'YAML Parser Fix Verification',
    'tests' => [],
    'summary' => []
];

// Helper function
function addTest($name, $passed, $message, $data = []) {
    global $results;
    $results['tests'][$name] = [
        'passed' => $passed,
        'message' => $message,
        'data' => $data
    ];
    return $passed;
}

// ============================================================
// SETUP
// ============================================================
$orchestrationPath = '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration';
require_once($orchestrationPath . '/shared/quantum/RuleYamlLoader.php');

// ============================================================
// TEST 1: Load Agent04 Rules
// ============================================================
try {
    $loader = new RuleYamlLoader($orchestrationPath, true); // Debug mode enabled
    $rules = $loader->loadAgentRules(4, false); // Agent 4, no cache

    $rulesCount = is_array($rules) ? count($rules) : 0;
    addTest('load_agent04_rules', $rulesCount > 0,
        "Loaded {$rulesCount} rules from Agent04",
        ['rules_count' => $rulesCount]);

    // Get debug logs
    $debugLogs = $loader->getDebugLogs();
    addTest('debug_logs', true, "Captured debug logs", ['log_count' => count($debugLogs)]);

} catch (Exception $e) {
    addTest('load_agent04_rules', false,
        "Exception: " . $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine()]);
    $rules = [];
}

// ============================================================
// TEST 2: Verify First Rule Structure (CU_A1_weak_point_detection)
// ============================================================
if (!empty($rules)) {
    $firstRule = reset($rules);
    $hasRuleId = isset($firstRule['rule_id']);
    $hasConditions = isset($firstRule['conditions']) && is_array($firstRule['conditions']);
    $hasAction = isset($firstRule['action']) && is_array($firstRule['action']);

    addTest('first_rule_structure', $hasRuleId && $hasConditions && $hasAction,
        "First rule has required structure",
        [
            'rule_id' => $firstRule['rule_id'] ?? 'MISSING',
            'has_conditions' => $hasConditions,
            'conditions_count' => $hasConditions ? count($firstRule['conditions']) : 0,
            'has_action' => $hasAction,
            'action_count' => $hasAction ? count($firstRule['action']) : 0
        ]);

    // ============================================================
    // TEST 3: Verify Condition Field/Operator/Value Structure (CRITICAL FIX TEST)
    // ============================================================
    if ($hasConditions && count($firstRule['conditions']) > 0) {
        $firstCondition = $firstRule['conditions'][0];

        // The fix should ensure:
        // - 'field' contains 'activity_type' (NOT the literal 'field')
        // - 'operator' contains '==' (NOT missing)
        // - 'value' contains 'concept_understanding' (NOT missing)

        $fieldValue = $firstCondition['field'] ?? 'MISSING';
        $operatorValue = $firstCondition['operator'] ?? 'MISSING';
        $valueValue = $firstCondition['value'] ?? 'MISSING';

        // Critical check: field should NOT be literally 'field'
        $fieldCorrect = $fieldValue !== 'field' && $fieldValue !== 'MISSING';
        $operatorCorrect = $operatorValue !== 'MISSING';
        $valueCorrect = $valueValue !== 'MISSING';

        $structureCorrect = $fieldCorrect && $operatorCorrect && $valueCorrect;

        addTest('condition_structure_fix', $structureCorrect,
            $structureCorrect
                ? "Condition structure correctly parsed (PARSER FIX VERIFIED!)"
                : "Condition structure INCORRECT - parser fix may not be applied",
            [
                'first_condition' => $firstCondition,
                'field_value' => $fieldValue,
                'field_correct' => $fieldCorrect,
                'operator_value' => $operatorValue,
                'operator_correct' => $operatorCorrect,
                'value_value' => $valueValue,
                'value_correct' => $valueCorrect,
                'expected_field' => 'activity_type',
                'expected_operator' => '==',
                'expected_value' => 'concept_understanding'
            ]);

        // Check all conditions in first rule
        $allConditionsValid = true;
        $conditionDetails = [];
        foreach ($firstRule['conditions'] as $idx => $cond) {
            $isValid = isset($cond['field']) && $cond['field'] !== 'field' && isset($cond['operator']);
            if (!$isValid) $allConditionsValid = false;
            $conditionDetails[$idx] = [
                'field' => $cond['field'] ?? 'MISSING',
                'operator' => $cond['operator'] ?? 'MISSING',
                'value' => $cond['value'] ?? 'MISSING',
                'valid' => $isValid
            ];
        }

        addTest('all_conditions_valid', $allConditionsValid,
            $allConditionsValid
                ? "All conditions in first rule are valid"
                : "Some conditions have invalid structure",
            ['conditions' => $conditionDetails]);
    }

    // ============================================================
    // TEST 4: Sample Multiple Rules
    // ============================================================
    $sampleRules = [];
    $rulesValid = 0;
    $rulesInvalid = 0;

    foreach (array_slice($rules, 0, 5) as $rule) {
        $hasValidConditions = true;
        if (isset($rule['conditions'])) {
            foreach ($rule['conditions'] as $cond) {
                if (!isset($cond['field']) || $cond['field'] === 'field' || !isset($cond['operator'])) {
                    $hasValidConditions = false;
                    break;
                }
            }
        }

        if ($hasValidConditions) {
            $rulesValid++;
        } else {
            $rulesInvalid++;
        }

        $sampleRules[] = [
            'rule_id' => $rule['rule_id'] ?? 'unknown',
            'conditions_count' => isset($rule['conditions']) ? count($rule['conditions']) : 0,
            'valid' => $hasValidConditions
        ];
    }

    addTest('sample_rules_valid', $rulesInvalid === 0,
        "Sampled {$rulesValid} valid, {$rulesInvalid} invalid rules",
        ['sample' => $sampleRules]);
}

// ============================================================
// TEST 5: Wave Mapping Integration
// ============================================================
try {
    require_once($orchestrationPath . '/shared/quantum/RuleToWaveMapper.php');

    $mapper = new RuleToWaveMapper($orchestrationPath, $loader);
    $waveParams = $mapper->mapAgentRulesToWaves(4);

    $waveCount = is_array($waveParams) ? count($waveParams) : 0;
    addTest('wave_mapping', $waveCount > 0,
        "Mapped {$waveCount} rules to wave parameters",
        ['wave_params_count' => $waveCount]);

    // Sample first wave params
    if ($waveCount > 0) {
        $firstWave = reset($waveParams);
        addTest('wave_params_structure', isset($firstWave['amplitude']) && isset($firstWave['frequency']),
            "Wave parameters have required structure",
            ['first_wave_sample' => $firstWave]);
    }

} catch (Exception $e) {
    addTest('wave_mapping', false,
        "Wave mapping exception: " . $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine()]);
}

// ============================================================
// SUMMARY
// ============================================================
$passed = 0;
$total = count($results['tests']);
foreach ($results['tests'] as $test) {
    if ($test['passed']) $passed++;
}

$results['summary'] = [
    'passed' => $passed,
    'total' => $total,
    'percentage' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
    'fix_status' => isset($results['tests']['condition_structure_fix']) && $results['tests']['condition_structure_fix']['passed']
        ? 'PARSER FIX VERIFIED - Rules loading correctly!'
        : 'PARSER FIX NOT APPLIED - Check customYamlParse() changes'
];

// ============================================================
// OUTPUT
// ============================================================
if ($isJson) {
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML Output
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_yaml_fix_verification.php');
$PAGE->set_title('YAML Parser Fix Verification');

echo $OUTPUT->header();
echo $OUTPUT->heading('YAML Parser Fix Verification', 2);

// Summary Banner
$statusColor = $results['summary']['percentage'] >= 80 ? '#28a745' : ($results['summary']['percentage'] >= 50 ? '#ffc107' : '#dc3545');
$statusText = $results['summary']['fix_status'];
echo "<div style='background: {$statusColor}; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3 style='margin: 0;'>📋 {$results['summary']['passed']}/{$results['summary']['total']} Tests Passed ({$results['summary']['percentage']}%)</h3>";
echo "<p style='margin: 10px 0 0 0; font-size: 1.1em;'>{$statusText}</p>";
echo "</div>";

// Individual Tests
foreach ($results['tests'] as $name => $test) {
    $icon = $test['passed'] ? '✅' : '❌';
    $bg = $test['passed'] ? '#d4edda' : '#f8d7da';
    $border = $test['passed'] ? '#c3e6cb' : '#f5c6cb';

    echo "<div style='background: {$bg}; border: 1px solid {$border}; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4 style='margin: 0 0 10px 0;'>{$icon} {$name}</h4>";
    echo "<p><strong>Result:</strong> {$test['message']}</p>";

    if (!empty($test['data'])) {
        echo "<details><summary>📊 Details</summary>";
        echo "<pre style='background: white; padding: 10px; font-size: 12px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($test['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre></details>";
    }
    echo "</div>";
}

// Debug Logs Section
if (isset($debugLogs) && count($debugLogs) > 0) {
    echo "<h3>🔍 Debug Logs</h3>";
    echo "<details><summary>Show {$results['tests']['debug_logs']['data']['log_count']} log entries</summary>";
    echo "<pre style='background: #f5f5f5; padding: 15px; font-size: 11px; max-height: 400px; overflow: auto;'>";
    foreach ($debugLogs as $log) {
        echo htmlspecialchars($log) . "\n";
    }
    echo "</pre></details>";
}

// Links
echo "<hr>";
echo "<h4>🔗 Related Links</h4>";
echo "<ul>";
echo "<li><a href='yaml_parse_diagnostic.php'>YAML Parse Diagnostic</a></li>";
echo "<li><a href='test_phase1_integration.php'>Phase 1 Integration Tests</a></li>";
echo "<li><a href='?format=json'>JSON API</a></li>";
echo "</ul>";

echo $OUTPUT->footer();

// ============================================================
// Related Database Tables
// ============================================================
// N/A - This is a verification script for YAML parsing fix
// ============================================================
