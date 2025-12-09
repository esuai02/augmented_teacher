<?php
/**
 * Step 6: Full Test Suite Runner
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/run_all_tests.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TASK 4 - STEP 6: FULL TEST SUITE                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Running complete test suite for PatternBank Safe JSON System\n\n";
echo str_repeat("=", 60) . "\n\n";

// Run all tests
require_once(__DIR__ . '/tests/run_all_tests.php');
