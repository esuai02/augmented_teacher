<?php
/**
 * Web-accessible test runner for ApiResponseNormalizer (Step 1: Verify Failure)
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_normalizer_test_step1.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "================================\n";
echo "ApiResponseNormalizer Test (Step 1: Should FAIL)\n";
echo "================================\n\n";

echo "TDD Step 1: Testing BEFORE implementation\n";
echo "Expected: Fatal error - Class 'ApiResponseNormalizer' not found\n\n";

// Run the test
require_once(__DIR__ . '/tests/ApiResponseNormalizerTest.php');
