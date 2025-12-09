<?php
/**
 * Integration tests for JsonSafeHelper
 * Tests 3-layer protection: normalize → encode → validate
 */

require_once __DIR__ . '/bootstrap.php';

class JsonSafeHelperTest {
    private $testResults = [];

    public function runAllTests() {
        echo "=== JsonSafeHelper Integration Tests ===\n\n";

        $this->testSafeEncodeWithFormulas();
        $this->testSafeDecodeRestoresFormulas();
        $this->testSafeEncodeWithKoreanKeys();
        $this->testFullWorkflowIntegration();
        $this->testSafeEncodeWithNullInput();          // NEW
        $this->testSafeEncodeWithOversizedData();      // NEW
        $this->testSafeDecodeWithInvalidJson();        // NEW

        $this->printResults();
    }

    private function testSafeEncodeWithFormulas() {
        echo "Test 1: safeEncode() with LaTeX formulas...\n";

        try {
            $data = [
                'question' => 'Solve: \\frac{1}{2} + \\frac{1}{3}',
                'solution' => 'Answer is $\\frac{5}{6}$'
            ];

            $encoded = JsonSafeHelper::safeEncode($data);
            $decoded = json_decode($encoded, true);

            // Verify JSON is valid
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON: " . json_last_error_msg());
            }

            // Verify formulas are encoded
            if (strpos($encoded, '{{FORMULA:') === false) {
                throw new Exception("Formulas not encoded");
            }

            // Verify no raw LaTeX in JSON
            if (strpos($encoded, '\\frac') !== false) {
                throw new Exception("Raw LaTeX found in JSON");
            }

            $this->testResults[] = ['Test 1', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 1', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testSafeDecodeRestoresFormulas() {
        echo "Test 2: safeDecode() restores formulas...\n";

        try {
            $original = [
                'question' => 'Solve: \\frac{1}{2}',
                'answer' => '$x^2$'
            ];

            // Round trip
            $encoded = JsonSafeHelper::safeEncode($original);
            $restored = JsonSafeHelper::safeDecode($encoded);

            // Verify formulas are restored
            if ($restored['question'] !== $original['question']) {
                throw new Exception("Question formula not restored");
            }

            if ($restored['answer'] !== $original['answer']) {
                throw new Exception("Answer formula not restored");
            }

            $this->testResults[] = ['Test 2', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 2', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testSafeEncodeWithKoreanKeys() {
        echo "Test 3: safeEncode() normalizes Korean keys...\n";

        try {
            $data = [
                '문항' => 'Question text',
                '해설' => 'Solution text',
                '선택지' => ['A', 'B', 'C']
            ];

            $encoded = JsonSafeHelper::safeEncode($data);
            $decoded = json_decode($encoded, true);

            // Verify keys are normalized
            if (!isset($decoded['question'])) {
                throw new Exception("'문항' not normalized to 'question'");
            }

            if (!isset($decoded['solution'])) {
                throw new Exception("'해설' not normalized to 'solution'");
            }

            if (!isset($decoded['choices'])) {
                throw new Exception("'선택지' not normalized to 'choices'");
            }

            $this->testResults[] = ['Test 3', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 3', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testFullWorkflowIntegration() {
        echo "Test 4: Full workflow (normalize + encode + validate)...\n";

        try {
            // Simulate GPT response with Korean keys and formulas
            $gptResponse = [
                '문항' => 'Calculate: \\frac{1}{2} + \\frac{1}{3}',
                '해설' => 'First find LCD, then $\\frac{3}{6} + \\frac{2}{6} = \\frac{5}{6}$',
                '선택지' => ['\\frac{5}{6}', '\\frac{2}{5}', '\\frac{1}{6}']
            ];

            // Encode with all protections
            $encoded = JsonSafeHelper::safeEncode($gptResponse);

            // Verify valid JSON
            if (!JsonSafeHelper::isValid($encoded)) {
                throw new Exception("Generated JSON is invalid");
            }

            // Decode and restore
            $restored = JsonSafeHelper::safeDecode($encoded);

            // Verify structure
            if (!isset($restored['question']) || !isset($restored['solution']) || !isset($restored['choices'])) {
                throw new Exception("Structure not preserved");
            }

            // Verify formulas restored
            if (strpos($restored['question'], '\\frac{1}{2}') === false) {
                throw new Exception("Question formula not restored");
            }

            if (strpos($restored['solution'], '$\\frac{3}{6}') === false) {
                throw new Exception("Solution formula not restored");
            }

            if ($restored['choices'][0] !== '\\frac{5}{6}') {
                throw new Exception("Choice formula not restored");
            }

            $this->testResults[] = ['Test 4', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 4', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testSafeEncodeWithNullInput() {
        echo "Test 5: safeEncode() handles null input...\n";

        try {
            $result = JsonSafeHelper::safeEncode(null);

            if (!JsonSafeHelper::isValid($result)) {
                throw new Exception("Null input produced invalid JSON");
            }

            // Should return empty object
            $decoded = json_decode($result, true);
            if (!is_array($decoded) || count($decoded) > 0) {
                throw new Exception("Null input should return empty JSON object, got: " . $result);
            }

            $this->testResults[] = ['Test 5', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 5', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testSafeEncodeWithOversizedData() {
        echo "Test 6: safeEncode() rejects oversized data...\n";

        try {
            // Create >1MB data
            $largeData = [];
            for ($i = 0; $i < 10000; $i++) {
                $largeData["key_$i"] = str_repeat("x", 200);
            }

            try {
                JsonSafeHelper::safeEncode($largeData);
                throw new Exception("Should have rejected oversized data");
            } catch (Exception $e) {
                // Should throw exception about size limit
                if (strpos($e->getMessage(), 'size limit') === false) {
                    throw new Exception("Wrong exception type: " . $e->getMessage());
                }
                // This is expected - test passes
            }

            $this->testResults[] = ['Test 6', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 6', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function testSafeDecodeWithInvalidJson() {
        echo "Test 7: safeDecode() handles invalid JSON...\n";

        try {
            try {
                JsonSafeHelper::safeDecode("invalid json {");
                throw new Exception("Should have thrown exception for invalid JSON");
            } catch (Exception $e) {
                // Should throw exception about decoding failure
                if (strpos($e->getMessage(), 'decoding failed') === false) {
                    throw new Exception("Wrong exception type: " . $e->getMessage());
                }
                // This is expected - test passes
            }

            $this->testResults[] = ['Test 7', 'PASS'];
            echo "  ✅ PASS\n\n";

        } catch (Exception $e) {
            $this->testResults[] = ['Test 7', 'FAIL: ' . $e->getMessage()];
            echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        }
    }

    private function printResults() {
        echo "=== Test Results Summary ===\n";
        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as [$name, $result]) {
            if (strpos($result, 'PASS') !== false) {
                echo "✅ $name: $result\n";
                $passed++;
            } else {
                echo "❌ $name: $result\n";
                $failed++;
            }
        }

        echo "\nTotal: " . ($passed + $failed) . " tests\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
    }
}

// Run tests
$tester = new JsonSafeHelperTest();
$tester->runAllTests();
