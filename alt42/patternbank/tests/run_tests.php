<?php
/**
 * Web-accessible test runner for FormulaEncoder
 * File: /mnt/c/1 Project/augmented_teacher/alt42/patternbank/tests/run_tests.php
 *
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/tests/run_tests.php
 */

// Include the test file
require_once __DIR__ . '/FormulaEncoderTest.php';

// Set headers for plain text output
header('Content-Type: text/plain; charset=utf-8');

echo "=== FormulaEncoder Test Runner ===\n";
echo "File: " . __FILE__ . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Run the test
try {
    $test = new FormulaEncoderTest();
    $test->runAll();
    echo "\n=== All tests completed ===\n";
} catch (Exception $e) {
    echo "\n=== ERROR ===\n";
    echo "Error at " . __FILE__ . ":" . __LINE__ . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
