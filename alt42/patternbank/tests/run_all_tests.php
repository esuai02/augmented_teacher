<?php
/**
 * Master test runner for PatternBank safe JSON system
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  PatternBank Safe JSON Implementation - Full Test Suite   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$startTime = microtime(true);

// Run all test files
$testFiles = [
    'FormulaEncoderTest.php',
    'ApiResponseNormalizerTest.php',
    'JsonSafeHelperTest.php'
];

foreach ($testFiles as $testFile) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Running: $testFile\n";
    echo str_repeat("=", 60) . "\n\n";

    require_once __DIR__ . '/' . $testFile;
    echo "\n";
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 3);

echo "\n" . str_repeat("=", 60) . "\n";
echo "All tests completed in {$duration} seconds\n";
echo str_repeat("=", 60) . "\n";
