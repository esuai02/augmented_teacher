<?php
/**
 * Standalone YAML Parser Test (No Moodle Authentication Required)
 *
 * Purpose: Test customYamlParse() fix without Moodle login
 * This file can be accessed directly for quick verification
 *
 * Created: 2025-12-09
 *
 * Usage:
 *   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_yaml_standalone.php
 *
 * Security Note: This script only reads YAML files and tests parsing - no database access
 */

// Skip Moodle integration for standalone testing
// No database or user authentication needed

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Initialize results
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_name' => 'Standalone YAML Parser Fix Verification',
    'php_version' => phpversion(),
    'tests' => [],
    'summary' => []
];

/**
 * Add test result
 */
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
// SETUP: Define paths (using absolute server paths)
// ============================================================
$orchestrationPath = '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration';
$rulesPath = $orchestrationPath . '/agents/agent04_inspect_weakpoints/rules/rules.yaml';
$loaderPath = $orchestrationPath . '/shared/quantum/RuleYamlLoader.php';

// Check paths
addTest('orchestration_path', is_dir($orchestrationPath),
    "Orchestration path: {$orchestrationPath}",
    ['exists' => is_dir($orchestrationPath)]);

addTest('rules_yaml_exists', file_exists($rulesPath),
    file_exists($rulesPath) ? "Rules YAML found ({$rulesPath})" : "Rules YAML NOT found",
    ['path' => $rulesPath, 'exists' => file_exists($rulesPath)]);

addTest('loader_exists', file_exists($loaderPath),
    file_exists($loaderPath) ? "RuleYamlLoader found" : "RuleYamlLoader NOT found",
    ['path' => $loaderPath, 'exists' => file_exists($loaderPath)]);

if (!file_exists($rulesPath) || !file_exists($loaderPath)) {
    $results['summary'] = ['error' => 'Required files not found'];
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// TEST: Direct YAML Parsing with customYamlParse
// ============================================================

// Include loader class
require_once($loaderPath);

try {
    // Create loader instance with debug enabled
    $loader = new RuleYamlLoader($orchestrationPath, true);

    // Test 1: Load Agent04 rules
    $rules = $loader->loadAgentRules(4, false); // No cache
    $rulesCount = is_array($rules) ? count($rules) : 0;

    addTest('load_agent04_rules', $rulesCount > 0,
        "Loaded {$rulesCount} rules from Agent04",
        ['rules_count' => $rulesCount]);

    // Test 2: Verify first rule structure
    if ($rulesCount > 0) {
        $firstRule = reset($rules);

        $hasRuleId = isset($firstRule['rule_id']);
        $hasConditions = isset($firstRule['conditions']) && is_array($firstRule['conditions']);
        $hasAction = isset($firstRule['action']) && is_array($firstRule['action']);

        addTest('first_rule_structure', $hasRuleId && $hasConditions && $hasAction,
            "First rule structure validation",
            [
                'rule_id' => $firstRule['rule_id'] ?? 'MISSING',
                'has_conditions' => $hasConditions,
                'conditions_count' => $hasConditions ? count($firstRule['conditions']) : 0,
                'has_action' => $hasAction
            ]);

        // Test 3: CRITICAL - Verify condition field/operator/value structure
        if ($hasConditions && count($firstRule['conditions']) > 0) {
            $firstCondition = $firstRule['conditions'][0];

            $fieldValue = $firstCondition['field'] ?? 'MISSING';
            $operatorValue = $firstCondition['operator'] ?? 'MISSING';
            $valueValue = $firstCondition['value'] ?? 'MISSING';

            // Critical check: field should NOT be literally 'field'
            $fieldCorrect = $fieldValue !== 'field' && $fieldValue !== 'MISSING';
            $operatorCorrect = $operatorValue !== 'MISSING';
            $valueCorrect = $valueValue !== 'MISSING';

            $structureCorrect = $fieldCorrect && $operatorCorrect && $valueCorrect;

            addTest('CRITICAL_condition_structure', $structureCorrect,
                $structureCorrect
                    ? "PARSER FIX VERIFIED - Conditions parsed correctly!"
                    : "PARSER FIX FAILED - Conditions still malformed",
                [
                    'first_condition' => $firstCondition,
                    'field_value' => $fieldValue,
                    'field_should_not_be_literal_field' => $fieldCorrect,
                    'operator_value' => $operatorValue,
                    'operator_exists' => $operatorCorrect,
                    'value_value' => $valueValue,
                    'value_exists' => $valueCorrect,
                    'expected' => [
                        'field' => 'activity_type',
                        'operator' => '==',
                        'value' => 'concept_understanding'
                    ]
                ]);

            // Test 4: Check all conditions in first rule
            $allConditionsValid = true;
            $conditionDetails = [];
            foreach ($firstRule['conditions'] as $idx => $cond) {
                $isValid = isset($cond['field'])
                    && $cond['field'] !== 'field'
                    && isset($cond['operator']);
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

        // Test 5: Sample multiple rules
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

        addTest('sample_rules_validation', $rulesInvalid === 0,
            "Sampled {$rulesValid} valid, {$rulesInvalid} invalid rules",
            ['sample' => $sampleRules]);
    }

    // Get debug logs
    $debugLogs = $loader->getDebugLogs();
    addTest('debug_logs_captured', count($debugLogs) > 0,
        "Captured " . count($debugLogs) . " debug log entries",
        ['log_count' => count($debugLogs), 'sample' => array_slice($debugLogs, 0, 10)]);

} catch (Exception $e) {
    addTest('exception', false,
        "Exception: " . $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
}

// ============================================================
// SUMMARY
// ============================================================
$passed = 0;
$total = count($results['tests']);
foreach ($results['tests'] as $test) {
    if ($test['passed']) $passed++;
}

// Check critical test specifically
$criticalPassed = isset($results['tests']['CRITICAL_condition_structure'])
    && $results['tests']['CRITICAL_condition_structure']['passed'];

$results['summary'] = [
    'passed' => $passed,
    'total' => $total,
    'percentage' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
    'critical_fix_verified' => $criticalPassed,
    'fix_status' => $criticalPassed
        ? '✅ PARSER FIX VERIFIED - Rules loading correctly!'
        : '❌ PARSER FIX NOT WORKING - Check customYamlParse() changes'
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ============================================================
// Related Database Tables
// ============================================================
// N/A - This is a standalone test script without database access
// ============================================================
