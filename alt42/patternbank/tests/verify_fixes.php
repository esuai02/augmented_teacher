<?php
/**
 * Standalone verification of P0 fixes
 * File: /mnt/c/1 Project/augmented_teacher/alt42/patternbank/tests/verify_fixes.php
 *
 * This script tests the three critical fixes without requiring Moodle authentication.
 * Can be run directly: php verify_fixes.php
 */

// Load FormulaEncoder class
require_once(__DIR__ . '/../lib/FormulaEncoder.php');

echo "=== FormulaEncoder P0 Fix Verification ===\n";
echo "File: " . __FILE__ . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$passed = 0;
$failed = 0;

// TEST 1: Multiple LaTeX arguments (Fix #1)
echo "TEST 1: Multiple LaTeX arguments (Line ~202 fix)\n";
try {
    $input = ['q' => 'Calculate \\frac{1}{2} + \\sqrt{x} + x^{2}'];
    $encoded = FormulaEncoder::encode($input);
    $decoded = FormulaEncoder::decode($encoded);

    if ($input['q'] === $decoded['q']) {
        echo "✓ PASS: Multiple LaTeX arguments handled correctly\n";
        $passed++;
    } else {
        echo "✗ FAIL: Got: {$decoded['q']}\n";
        echo "       Expected: {$input['q']}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $failed++;
}
echo "\n";

// TEST 2: Display math before inline math (Fix #2)
echo "TEST 2: Display math with embedded $ (Lines ~203-204 fix)\n";
try {
    $input = ['q' => 'Formula: $$x + \$5$$ and inline $y$'];
    $encoded = FormulaEncoder::encode($input);
    $decoded = FormulaEncoder::decode($encoded);

    if ($input['q'] === $decoded['q']) {
        echo "✓ PASS: Display math processed correctly\n";
        $passed++;
    } else {
        echo "✗ FAIL: Got: {$decoded['q']}\n";
        echo "       Expected: {$input['q']}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $failed++;
}
echo "\n";

// TEST 3: Strict base64 decoding (Fix #3)
echo "TEST 3: Strict base64 decoding (Lines ~273-277 fix)\n";
try {
    // Test with invalid base64
    $input = ['q' => '{{FORMULA:INVALID!!!BASE64}}'];
    $decoded = FormulaEncoder::decode($input);

    // Should leave marker as-is (not decode corrupt data)
    if (strpos($decoded['q'], '{{FORMULA:') !== false) {
        echo "✓ PASS: Invalid base64 rejected (marker preserved)\n";
        $passed++;
    } else {
        echo "✗ FAIL: Invalid base64 was decoded (should be rejected)\n";
        echo "       Got: {$decoded['q']}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $failed++;
}
echo "\n";

// TEST 4: Complex LaTeX with subscripts and superscripts
echo "TEST 4: Complex LaTeX patterns (comprehensive test)\n";
try {
    $input = ['q' => '\\sum_{i=1}^{n} x_i + \\frac{\\partial f}{\\partial x}'];
    $encoded = FormulaEncoder::encode($input);
    $decoded = FormulaEncoder::decode($encoded);

    if ($input['q'] === $decoded['q']) {
        echo "✓ PASS: Complex LaTeX with subscripts/superscripts handled\n";
        $passed++;
    } else {
        echo "✗ FAIL: Got: {$decoded['q']}\n";
        echo "       Expected: {$input['q']}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $failed++;
}
echo "\n";

// TEST 5: Self-closing MathML
echo "TEST 5: Self-closing MathML tags\n";
try {
    $input = ['q' => 'Formula: <math xmlns="http://www.w3.org/1998/Math/MathML" />'];
    $encoded = FormulaEncoder::encode($input);
    $decoded = FormulaEncoder::decode($encoded);

    if ($input['q'] === $decoded['q']) {
        echo "✓ PASS: Self-closing MathML handled\n";
        $passed++;
    } else {
        echo "✗ FAIL: Got: {$decoded['q']}\n";
        echo "       Expected: {$input['q']}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $failed++;
}
echo "\n";

// Summary
echo "=== Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "\n";

if ($failed === 0) {
    echo "✓ All P0 fixes verified successfully!\n";
    exit(0);
} else {
    echo "✗ Some tests failed - review fixes needed\n";
    exit(1);
}
