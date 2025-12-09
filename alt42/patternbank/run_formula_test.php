<?php
/**
 * Web-accessible test runner for FormulaEncoder
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_formula_test.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "================================\n";
echo "FormulaEncoder Test Runner\n";
echo "================================\n\n";

// Run the test
require_once(__DIR__ . '/tests/FormulaEncoderTest.php');
