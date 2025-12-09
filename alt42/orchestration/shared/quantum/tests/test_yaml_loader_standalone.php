<?php
/**
 * RuleYamlLoader Standalone Test
 *
 * Purpose: Test YAML parsing without Moodle authentication
 * Part of: Phase 1 - Rule-Quantum Bridge Implementation
 *
 * Version: 1.0
 * Created: 2025-12-09
 *
 * Usage (CLI or browser):
 *   php test_yaml_loader_standalone.php
 *   https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_yaml_loader_standalone.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check format
$isJson = (isset($_GET['format']) && $_GET['format'] === 'json') || php_sapi_name() === 'cli' && in_array('--json', $argv ?? []);
$isCli = php_sapi_name() === 'cli';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'yaml_extension' => extension_loaded('yaml'),
    'tests' => [],
    'summary' => []
];

$passedTests = 0;
$totalTests = 0;

function recordTest($name, $passed, $message = '', $details = []) {
    global $results, $passedTests, $totalTests;
    $totalTests++;
    if ($passed) {
        $passedTests++;
    }

    $results['tests'][] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];
}

// ============================================================
// Test 1: RuleYamlLoader File Existence
// ============================================================
$loaderPath = dirname(__DIR__) . '/RuleYamlLoader.php';
$loaderExists = file_exists($loaderPath);
recordTest(
    'RuleYamlLoader.php exists',
    $loaderExists,
    $loaderExists ? "Found at: {$loaderPath}" : "NOT FOUND at: {$loaderPath}"
);

if (!$loaderExists) {
    outputResults($results, $isJson, $isCli);
    exit;
}

// ============================================================
// Test 2: Class Loading
// ============================================================
try {
    require_once($loaderPath);
    $classExists = class_exists('RuleYamlLoader');
    recordTest(
        'RuleYamlLoader class exists',
        $classExists,
        $classExists ? "Class loaded successfully" : "Class not found after require"
    );
} catch (Exception $e) {
    recordTest(
        'RuleYamlLoader class exists',
        false,
        "Exception: " . $e->getMessage()
    );
}

// ============================================================
// Test 3: Instantiation
// ============================================================
try {
    $loader = new RuleYamlLoader();
    recordTest('RuleYamlLoader instantiation', true, "Object created successfully");
} catch (Exception $e) {
    recordTest(
        'RuleYamlLoader instantiation',
        false,
        "Exception: " . $e->getMessage()
    );
    outputResults($results, $isJson, $isCli);
    exit;
}

// ============================================================
// Test 4: Agent04 rules.yaml File Existence
// ============================================================
$agent04RulesPath = dirname(dirname(dirname(__DIR__))) . '/agents/agent04_inspect_weakpoints/rules/rules.yaml';
$agent04Exists = file_exists($agent04RulesPath);
recordTest(
    'Agent04 rules.yaml exists',
    $agent04Exists,
    $agent04Exists ? "Found: {$agent04RulesPath}" : "NOT FOUND: {$agent04RulesPath}",
    ['path' => $agent04RulesPath]
);

if (!$agent04Exists) {
    outputResults($results, $isJson, $isCli);
    exit;
}

// ============================================================
// Test 5: Raw File Content Check
// ============================================================
$rawContent = file_get_contents($agent04RulesPath);
$hasRulesSection = strpos($rawContent, 'rules:') !== false;
$hasVersion = strpos($rawContent, 'version:') !== false;
$hasScenario = strpos($rawContent, 'scenario:') !== false;

recordTest(
    'YAML structure check (top-level keys)',
    $hasRulesSection && $hasVersion,
    "version: " . ($hasVersion ? 'YES' : 'NO') .
    ", scenario: " . ($hasScenario ? 'YES' : 'NO') .
    ", rules: " . ($hasRulesSection ? 'YES' : 'NO'),
    [
        'has_version' => $hasVersion,
        'has_scenario' => $hasScenario,
        'has_rules' => $hasRulesSection,
        'file_size' => strlen($rawContent)
    ]
);

// ============================================================
// Test 6: Load rules.yaml via loadAgentRules(4)
// ============================================================
try {
    // Use loadAgentRules(4) for agent04_inspect_weakpoints
    $rules = $loader->loadAgentRules(4);
    $rulesCount = is_array($rules) ? count($rules) : 0;

    recordTest(
        'Load Agent04 rules.yaml via loadAgentRules(4)',
        $rulesCount > 0,
        "Loaded {$rulesCount} rules",
        ['rule_count' => $rulesCount]
    );

    // Check if rules are actual rule objects (not top-level metadata)
    if ($rulesCount > 0) {
        $firstRule = reset($rules);
        $firstKey = key($rules);

        // Valid rule should have 'conditions' or 'action' - not be 'version', 'scenario', etc.
        $isValidRuleStructure = is_array($firstRule) &&
            (isset($firstRule['conditions']) || isset($firstRule['action']) || isset($firstRule['priority']));

        recordTest(
            'Rules structure validation (not metadata)',
            $isValidRuleStructure,
            "First key: '{$firstKey}', has conditions/action/priority: " . ($isValidRuleStructure ? 'YES' : 'NO'),
            [
                'first_rule_key' => $firstKey,
                'first_rule_keys' => is_array($firstRule) ? array_keys($firstRule) : []
            ]
        );
    }
} catch (Exception $e) {
    recordTest(
        'Load Agent04 rules.yaml via loadAgentRules(4)',
        false,
        "Exception: " . $e->getMessage(),
        ['file' => $e->getFile(), 'line' => $e->getLine()]
    );
    outputResults($results, $isJson, $isCli);
    exit;
}

// ============================================================
// Test 7: Rule Field Extraction
// ============================================================
if ($rulesCount > 0) {
    $sampleRuleId = key($rules);
    $sampleRule = $rules[$sampleRuleId];

    $hasConditions = isset($sampleRule['conditions']);
    $hasAction = isset($sampleRule['action']);
    $hasPriority = isset($sampleRule['priority']);
    $hasConfidence = isset($sampleRule['confidence']);

    recordTest(
        'Rule field extraction',
        $hasConditions || $hasAction || $hasPriority,
        "Rule '{$sampleRuleId}': conditions=" . ($hasConditions ? 'YES' : 'NO') .
        ", action=" . ($hasAction ? 'YES' : 'NO') .
        ", priority=" . ($hasPriority ? (int)$sampleRule['priority'] : 'NO') .
        ", confidence=" . ($hasConfidence ? $sampleRule['confidence'] : 'NO'),
        [
            'rule_id' => $sampleRuleId,
            'has_conditions' => $hasConditions,
            'has_action' => $hasAction,
            'priority' => $hasPriority ? $sampleRule['priority'] : null,
            'confidence' => $hasConfidence ? $sampleRule['confidence'] : null
        ]
    );

    // Test 8: Conditions array parsing
    if ($hasConditions) {
        $conditions = $sampleRule['conditions'];
        $conditionsIsArray = is_array($conditions);
        $conditionsCount = $conditionsIsArray ? count($conditions) : 0;

        recordTest(
            'Conditions array parsing',
            $conditionsIsArray && $conditionsCount > 0,
            "Conditions count: {$conditionsCount}",
            [
                'is_array' => $conditionsIsArray,
                'count' => $conditionsCount,
                'first_condition' => $conditionsCount > 0 ? $conditions[0] : null
            ]
        );

        // Test 9: Condition field structure
        if ($conditionsCount > 0) {
            $firstCondition = $conditions[0];
            $hasField = isset($firstCondition['field']);
            $hasOperator = isset($firstCondition['operator']);
            $hasValue = isset($firstCondition['value']);

            recordTest(
                'Condition field structure',
                $hasField && $hasOperator,
                "field=" . ($hasField ? $firstCondition['field'] : 'MISSING') .
                ", operator=" . ($hasOperator ? $firstCondition['operator'] : 'MISSING') .
                ", value=" . ($hasValue ? (is_array($firstCondition['value']) ? '[array]' : $firstCondition['value']) : 'MISSING'),
                [
                    'field' => $hasField ? $firstCondition['field'] : null,
                    'operator' => $hasOperator ? $firstCondition['operator'] : null,
                    'value' => $hasValue ? $firstCondition['value'] : null
                ]
            );
        }
    }

    // Test 10: Action array parsing (inline array test - key bug fix verification)
    if ($hasAction) {
        $action = $sampleRule['action'];
        $actionIsArray = is_array($action);
        $actionCount = $actionIsArray ? count($action) : 0;

        recordTest(
            'Action array parsing (inline array fix)',
            $actionIsArray,
            "Action is array: " . ($actionIsArray ? 'YES' : 'NO') .
            ", count: {$actionCount}",
            [
                'is_array' => $actionIsArray,
                'count' => $actionCount,
                'actions' => $actionIsArray ? $action : [$action]
            ]
        );
    }
}

// ============================================================
// Test 11: Multiple Rules Parsing
// ============================================================
$rulesWithConditions = 0;
$rulesWithActions = 0;
$totalPriority = 0;
$validPriorityCount = 0;

// Null safety: ensure $rules is an array before iterating
$rulesArray = is_array($rules) ? $rules : [];

foreach ($rulesArray as $ruleId => $rule) {
    if (isset($rule['conditions']) && is_array($rule['conditions'])) {
        $rulesWithConditions++;
    }
    if (isset($rule['action'])) {
        $rulesWithActions++;
    }
    if (isset($rule['priority']) && is_numeric($rule['priority'])) {
        $totalPriority += (int)$rule['priority'];
        $validPriorityCount++;
    }
}

$avgPriority = $validPriorityCount > 0 ? round($totalPriority / $validPriorityCount, 2) : 0;

recordTest(
    'Multiple rules parsing quality',
    $rulesWithConditions > 0,
    "Rules with conditions: {$rulesWithConditions}/{$rulesCount}, " .
    "with actions: {$rulesWithActions}/{$rulesCount}, " .
    "avg priority: {$avgPriority}",
    [
        'total_rules' => $rulesCount,
        'with_conditions' => $rulesWithConditions,
        'with_actions' => $rulesWithActions,
        'avg_priority' => $avgPriority
    ]
);

// ============================================================
// Test 12: persona_system rules.yaml
// ============================================================
$personaRulesPath = dirname(dirname(dirname(__DIR__))) . '/agents/agent04_inspect_weakpoints/persona_system/rules.yaml';
$personaRulesExists = file_exists($personaRulesPath);

recordTest(
    'persona_system/rules.yaml exists',
    $personaRulesExists,
    $personaRulesExists ? "Found" : "NOT FOUND",
    ['path' => $personaRulesPath]
);

if ($personaRulesExists) {
    // Note: persona_system/rules.yaml is in a non-standard location
    // RuleYamlLoader.loadAgentRules() only supports standard agent paths
    // For now, verify file is readable and has YAML content

    $personaContent = @file_get_contents($personaRulesPath);
    $personaReadable = ($personaContent !== false && strlen($personaContent) > 0);

    recordTest(
        'persona_system/rules.yaml readable',
        $personaReadable,
        $personaReadable
            ? "File readable (" . strlen($personaContent) . " bytes) - Full parsing deferred to Phase 2"
            : "File not readable or empty",
        [
            'path' => $personaRulesPath,
            'size_bytes' => $personaReadable ? strlen($personaContent) : 0,
            'note' => 'Non-standard path - requires PersonaRulesLoader extension'
        ]
    );
}

// ============================================================
// SUMMARY
// ============================================================
$results['summary'] = [
    'passed' => $passedTests,
    'total' => $totalTests,
    'percentage' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0,
    'status' => ($passedTests === $totalTests) ? 'ALL_PASSED' : 'SOME_FAILED'
];

outputResults($results, $isJson, $isCli);

// ============================================================
// Output Functions
// ============================================================
function outputResults($results, $isJson, $isCli) {
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($isCli) {
        outputCli($results);
        return;
    }

    outputHtml($results);
}

function outputCli($results) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║          RuleYamlLoader Standalone Test Results              ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Timestamp: " . $results['timestamp'] . "\n";
    echo "PHP Version: " . $results['php_version'] . "\n";
    echo "YAML Extension: " . ($results['yaml_extension'] ? 'YES' : 'NO') . "\n";
    echo "\n";
    echo "┌────────────────────────────────────────────────────────────────┐\n";
    echo "│ Test Results                                                    │\n";
    echo "├────────────────────────────────────────────────────────────────┤\n";

    foreach ($results['tests'] as $test) {
        $icon = $test['passed'] ? '✅' : '❌';
        $name = str_pad(substr($test['name'], 0, 40), 40);
        echo "│ {$icon} {$name}      │\n";
        if ($test['message']) {
            $msg = "      " . substr($test['message'], 0, 55);
            echo "│ {$msg}│\n";
        }
    }

    echo "└────────────────────────────────────────────────────────────────┘\n";
    echo "\n";

    $status = $results['summary']['status'] === 'ALL_PASSED' ? '✅ ALL PASSED' : '❌ SOME FAILED';
    echo "Summary: {$results['summary']['passed']}/{$results['summary']['total']} tests passed ";
    echo "({$results['summary']['percentage']}%) - {$status}\n\n";
}

function outputHtml($results) {
    ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuleYamlLoader Test Results</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .summary { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary.pass { border-left: 4px solid #28a745; }
        .summary.fail { border-left: 4px solid #dc3545; }
        .test { background: white; padding: 15px; margin-bottom: 10px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .test.pass { border-left: 3px solid #28a745; }
        .test.fail { border-left: 3px solid #dc3545; }
        .test-name { font-weight: bold; margin-bottom: 5px; }
        .test-message { color: #666; font-size: 0.9em; }
        .test-details { background: #f8f9fa; padding: 10px; margin-top: 10px; border-radius: 4px; font-family: monospace; font-size: 0.85em; overflow-x: auto; }
        .meta { color: #888; font-size: 0.85em; margin-bottom: 20px; }
        .links { margin-top: 20px; padding: 15px; background: white; border-radius: 5px; }
        .links a { margin-right: 15px; color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🧪 RuleYamlLoader Test Results</h1>

    <div class="meta">
        <strong>Timestamp:</strong> <?= htmlspecialchars($results['timestamp']) ?> |
        <strong>PHP:</strong> <?= htmlspecialchars($results['php_version']) ?> |
        <strong>YAML Extension:</strong> <?= $results['yaml_extension'] ? '✅' : '❌' ?>
    </div>

    <div class="summary <?= $results['summary']['status'] === 'ALL_PASSED' ? 'pass' : 'fail' ?>">
        <h2>
            <?= $results['summary']['status'] === 'ALL_PASSED' ? '✅' : '⚠️' ?>
            <?= $results['summary']['passed'] ?>/<?= $results['summary']['total'] ?> Tests Passed
            (<?= $results['summary']['percentage'] ?>%)
        </h2>
        <p><?= $results['summary']['status'] === 'ALL_PASSED' ? 'All YAML parsing tests passed successfully!' : 'Some tests failed - check details below.' ?></p>
    </div>

    <h3>Test Details</h3>
    <?php foreach ($results['tests'] as $test): ?>
    <div class="test <?= $test['passed'] ? 'pass' : 'fail' ?>">
        <div class="test-name">
            <?= $test['passed'] ? '✅' : '❌' ?>
            <?= htmlspecialchars($test['name']) ?>
        </div>
        <?php if (!empty($test['message'])): ?>
        <div class="test-message"><?= htmlspecialchars($test['message']) ?></div>
        <?php endif; ?>
        <?php if (!empty($test['details'])): ?>
        <div class="test-details">
            <pre><?= htmlspecialchars(json_encode($test['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="links">
        <h4>Related Links</h4>
        <a href="?format=json">📄 JSON Output</a>
        <a href="db_diagnostic.php">🗄️ DB Diagnostic</a>
        <a href="test_phase1_integration.php">🧪 Phase 1 Tests</a>
        <a href="PHASE1_TEST_GUIDE.md">📖 Test Guide</a>
    </div>
</body>
</html>
    <?php
}
