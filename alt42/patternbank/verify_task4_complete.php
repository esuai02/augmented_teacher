<?php
/**
 * Task 4 Completion Verification Script
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/verify_task4_complete.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TASK 4 - COMPLETION VERIFICATION                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];

// Check file existence
echo "1. Checking file existence...\n";

$requiredFiles = [
    'lib/JsonSafeHelper.php' => 'JsonSafeHelper implementation',
    'tests/JsonSafeHelperTest.php' => 'JsonSafeHelper tests',
    'run_jsonsafe_test_step1.php' => 'RED phase runner',
    'run_jsonsafe_test_step2.php' => 'GREEN phase runner',
    'run_all_tests.php' => 'Full suite runner',
    'tests/run_all_tests.php' => 'Backend test orchestrator',
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file - $description\n";
    } else {
        echo "   ❌ $file - MISSING\n";
        $errors[] = "Missing file: $file";
    }
}

echo "\n2. Checking class definitions...\n";

// Check JsonSafeHelper class
require_once(__DIR__ . '/lib/JsonSafeHelper.php');

if (class_exists('JsonSafeHelper')) {
    echo "   ✅ JsonSafeHelper class exists\n";

    // Check methods
    $methods = ['safeEncode', 'safeDecode', 'isValid'];
    foreach ($methods as $method) {
        if (method_exists('JsonSafeHelper', $method)) {
            echo "   ✅ JsonSafeHelper::$method() exists\n";
        } else {
            echo "   ❌ JsonSafeHelper::$method() MISSING\n";
            $errors[] = "Missing method: JsonSafeHelper::$method()";
        }
    }
} else {
    echo "   ❌ JsonSafeHelper class NOT FOUND\n";
    $errors[] = "JsonSafeHelper class not defined";
}

echo "\n3. Checking dependencies...\n";

$dependencies = [
    'FormulaEncoder' => 'lib/FormulaEncoder.php',
    'ApiResponseNormalizer' => 'lib/ApiResponseNormalizer.php',
];

foreach ($dependencies as $class => $file) {
    if (class_exists($class)) {
        echo "   ✅ $class available\n";
    } else {
        echo "   ❌ $class NOT FOUND\n";
        $errors[] = "Missing dependency: $class";
    }
}

echo "\n4. Quick functionality test...\n";

try {
    // Test basic encoding
    $testData = [
        '문항' => 'Test: \\frac{1}{2}',
        '해설' => 'Answer: $x^2$'
    ];

    $encoded = JsonSafeHelper::safeEncode($testData);
    echo "   ✅ safeEncode() executed\n";

    // Verify JSON is valid
    if (JsonSafeHelper::isValid($encoded)) {
        echo "   ✅ Generated JSON is valid\n";
    } else {
        echo "   ❌ Generated JSON is INVALID\n";
        $errors[] = "Generated JSON failed validation";
    }

    // Test decoding
    $decoded = JsonSafeHelper::safeDecode($encoded);
    echo "   ✅ safeDecode() executed\n";

    // Verify normalization
    if (isset($decoded['question'])) {
        echo "   ✅ Korean key '문항' normalized to 'question'\n";
    } else {
        echo "   ❌ Key normalization FAILED\n";
        $errors[] = "Key normalization not working";
    }

    // Verify formula restoration
    if (strpos($decoded['question'], '\\frac{1}{2}') !== false) {
        echo "   ✅ Formula restored correctly\n";
    } else {
        echo "   ❌ Formula restoration FAILED\n";
        $errors[] = "Formula restoration not working";
    }

} catch (Exception $e) {
    echo "   ❌ Functionality test FAILED: " . $e->getMessage() . "\n";
    $errors[] = "Functionality test exception: " . $e->getMessage();
}

// Final summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

if (count($errors) === 0) {
    echo "✅ ALL CHECKS PASSED\n\n";
    echo "Task 4 is COMPLETE and ready for testing!\n\n";
    echo "Next steps:\n";
    echo "1. Run RED phase test: run_jsonsafe_test_step1.php\n";
    echo "2. Run GREEN phase test: run_jsonsafe_test_step2.php\n";
    echo "3. Run full test suite: run_all_tests.php\n";
} else {
    echo "❌ VERIFICATION FAILED\n\n";
    echo "Errors found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (count($warnings) > 0) {
    echo "\n⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

echo "\n";
