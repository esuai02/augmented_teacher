<?php
require_once(__DIR__ . '/bootstrap.php');

class FormulaEncoderTest {
    public function testEncodeLatexFormula() {
        $input = ['question' => 'Calculate \\frac{1}{2}'];
        $encoded = FormulaEncoder::encode($input);

        // Should contain formula marker
        if (strpos($encoded['question'], '{{FORMULA:') === false) {
            throw new Exception("Formula not encoded");
        }

        // Should not contain raw LaTeX
        if (strpos($encoded['question'], '\\frac') !== false) {
            throw new Exception("Raw LaTeX still present");
        }

        echo "✓ testEncodeLatexFormula passed\n";
    }

    public function testDecodeFormula() {
        $encoded = ['question' => 'Calculate {{FORMULA:XGZyYWN7MX17Mn0=}}'];
        $decoded = FormulaEncoder::decode($encoded);

        // Should restore original formula
        if (strpos($decoded['question'], '\\frac{1}{2}') === false) {
            throw new Exception("Formula not decoded");
        }

        echo "✓ testDecodeFormula passed\n";
    }

    public function testRoundTrip() {
        $original = ['question' => 'Solve \\frac{1}{2} + \\frac{1}{3}'];
        $encoded = FormulaEncoder::encode($original);
        $decoded = FormulaEncoder::decode($encoded);

        if ($original['question'] !== $decoded['question']) {
            throw new Exception("Round-trip failed: {$original['question']} !== {$decoded['question']}");
        }

        echo "✓ testRoundTrip passed\n";
    }

    public function testMultipleFormulas() {
        $input = ['question' => '\\frac{1}{2} and \\sqrt{4} and $$x^2$$'];
        $encoded = FormulaEncoder::encode($input);
        $decoded = FormulaEncoder::decode($encoded);

        if ($input['question'] !== $decoded['question']) {
            throw new Exception("Multiple formulas round-trip failed");
        }

        echo "✓ testMultipleFormulas passed\n";
    }

    public function testStripFormulas() {
        $input = ['question' => 'Solve \\frac{1}{2}'];
        $stripped = FormulaEncoder::stripFormulas($input);

        if (strpos($stripped['question'], '\\frac') !== false) {
            throw new Exception("Formula not stripped");
        }

        if (strpos($stripped['question'], '[수식]') === false) {
            throw new Exception("Formula placeholder not added");
        }

        echo "✓ testStripFormulas passed\n";
    }

    public function runAll() {
        echo "\n=== FormulaEncoder Tests ===\n";
        $this->testEncodeLatexFormula();
        $this->testDecodeFormula();
        $this->testRoundTrip();
        $this->testMultipleFormulas();
        $this->testStripFormulas();
        echo "All tests passed!\n\n";
    }
}

// Run tests
$test = new FormulaEncoderTest();
$test->runAll();
