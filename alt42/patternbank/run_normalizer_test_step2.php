<?php
/**
 * Web-accessible test runner for ApiResponseNormalizer (Step 2: Should Pass)
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_normalizer_test_step2.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "================================\n";
echo "ApiResponseNormalizer Test (Step 2: Should PASS)\n";
echo "================================\n\n";

echo "TDD Step 2: Testing AFTER implementation\n";
echo "Expected: All 4 tests should pass\n\n";

// Run the test
require_once(__DIR__ . '/tests/ApiResponseNormalizerTest.php');
