<?php
/**
 * Step 2: TDD RED Phase - Verify tests FAIL
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_jsonsafe_test_step1.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TASK 4 - STEP 2: TDD RED PHASE (Tests Must FAIL)        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Expected Result: All tests FAIL with 'Class JsonSafeHelper not found'\n\n";
echo str_repeat("=", 60) . "\n\n";

// Run the test - should fail
require_once(__DIR__ . '/tests/JsonSafeHelperTest.php');
