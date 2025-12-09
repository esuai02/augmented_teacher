<?php
/**
 * Step 4: TDD GREEN Phase - Verify tests PASS
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_jsonsafe_test_step2.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TASK 4 - STEP 4: TDD GREEN PHASE (Tests Must PASS)      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Expected Result: All 4 tests PASS\n\n";
echo str_repeat("=", 60) . "\n\n";

// Run the test - should pass now
require_once(__DIR__ . '/tests/JsonSafeHelperTest.php');
