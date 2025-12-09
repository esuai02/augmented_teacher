<?php
require_once(__DIR__ . '/bootstrap.php');

class ApiResponseNormalizerTest {
    public function testNormalizeKoreanKeys() {
        $input = [
            '문항' => 'Test question',
            '해설' => 'Test solution',
            '선택지' => ['A', 'B', 'C']
        ];

        $normalized = ApiResponseNormalizer::normalize($input);

        if (!isset($normalized['question']) || $normalized['question'] !== 'Test question') {
            throw new Exception("Korean '문항' not converted to 'question'");
        }

        if (!isset($normalized['solution']) || $normalized['solution'] !== 'Test solution') {
            throw new Exception("Korean '해설' not converted to 'solution'");
        }

        if (!isset($normalized['choices']) || count($normalized['choices']) !== 3) {
            throw new Exception("Korean '선택지' not converted to 'choices'");
        }

        echo "✓ testNormalizeKoreanKeys passed\n";
    }

    public function testNormalizeMixedKeys() {
        $input = [
            '문항' => 'Question',
            'solution' => 'Solution',
            '선택지' => ['A', 'B']
        ];

        $normalized = ApiResponseNormalizer::normalize($input);

        if (!isset($normalized['question']) || !isset($normalized['solution']) || !isset($normalized['choices'])) {
            throw new Exception("Mixed keys not normalized");
        }

        echo "✓ testNormalizeMixedKeys passed\n";
    }

    public function testExtractJsonFromMixedContent() {
        $mixedContent = 'Here is the problem:\n\n{"question": "Test", "solution": "Answer"}\n\nI hope this helps!';

        $extracted = ApiResponseNormalizer::extractJson($mixedContent);
        $decoded = json_decode($extracted, true);

        if (!is_array($decoded) || !isset($decoded['question'])) {
            throw new Exception("Failed to extract JSON from mixed content");
        }

        echo "✓ testExtractJsonFromMixedContent passed\n";
    }

    public function testEnsureArray() {
        // Test single object
        $single = ['question' => 'Q1'];
        $array = ApiResponseNormalizer::ensureArray($single);

        if (!is_array($array) || count($array) !== 1) {
            throw new Exception("Single object not converted to array");
        }

        // Test array
        $multiple = [['question' => 'Q1'], ['question' => 'Q2']];
        $array = ApiResponseNormalizer::ensureArray($multiple);

        if (!is_array($array) || count($array) !== 2) {
            throw new Exception("Array not preserved");
        }

        echo "✓ testEnsureArray passed\n";
    }

    public function testRealFixture() {
        $jsonContent = file_get_contents(__DIR__ . '/fixtures/mixed_keys.json');
        $data = json_decode($jsonContent, true);

        $normalized = ApiResponseNormalizer::normalize($data);

        if (!isset($normalized['question']) || !isset($normalized['solution']) || !isset($normalized['choices'])) {
            throw new Exception("Real fixture normalization failed");
        }

        echo "✓ testRealFixture passed\n";
    }

    public function testNestedNormalization() {
        $input = [
            '문항' => 'Test question',
            'metadata' => [
                '해설' => 'Nested solution',
                'details' => [
                    '선택지' => ['A', 'B', 'C']
                ]
            ]
        ];

        $normalized = ApiResponseNormalizer::normalize($input);

        if (!isset($normalized['question']) || $normalized['question'] !== 'Test question') {
            throw new Exception("Top-level Korean key not normalized");
        }

        if (!isset($normalized['metadata']['solution']) || $normalized['metadata']['solution'] !== 'Nested solution') {
            throw new Exception("Nested Korean key not normalized");
        }

        if (!isset($normalized['metadata']['details']['choices']) || count($normalized['metadata']['details']['choices']) !== 3) {
            throw new Exception("Deep nested Korean key not normalized");
        }

        echo "✓ testNestedNormalization passed\n";
    }

    public function testExtractNestedJson() {
        // Test with nested objects and escaped quotes
        $complexJson = 'Prefix text {"outer": {"inner": "value with \\"quotes\\"", "nested": {"deep": "data"}}} Suffix';

        $extracted = ApiResponseNormalizer::extractJson($complexJson);
        $decoded = json_decode($extracted, true);

        if (!is_array($decoded) || !isset($decoded['outer']['inner'])) {
            throw new Exception("Failed to extract nested JSON");
        }

        if ($decoded['outer']['inner'] !== 'value with "quotes"') {
            throw new Exception("Failed to handle escaped quotes in nested JSON");
        }

        echo "✓ testExtractNestedJson passed\n";
    }

    public function testExtractJsonWithBrackets() {
        // Test with arrays containing objects
        $arrayJson = 'Text before [{"key": "value1"}, {"key": "value2"}] text after';

        $extracted = ApiResponseNormalizer::extractJson($arrayJson);
        $decoded = json_decode($extracted, true);

        if (!is_array($decoded) || count($decoded) !== 2) {
            throw new Exception("Failed to extract JSON array");
        }

        echo "✓ testExtractJsonWithBrackets passed\n";
    }

    public function testValidation() {
        $validData = ['question' => 'Test', 'solution' => 'Answer'];

        try {
            $result = ApiResponseNormalizer::validate($validData);
            if ($result !== true) {
                throw new Exception("Valid data rejected");
            }
        } catch (Exception $e) {
            throw new Exception("Valid data validation failed: " . $e->getMessage());
        }

        // Test invalid type
        try {
            ApiResponseNormalizer::validate("not an array");
            throw new Exception("String input should have been rejected");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "must be array") === false) {
                throw new Exception("Wrong validation error for non-array");
            }
        }

        // Test size limit (create data > 1MB)
        $largeData = array_fill(0, 100000, str_repeat('x', 100));
        try {
            ApiResponseNormalizer::validate($largeData);
            throw new Exception("Oversized data should have been rejected");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "exceeds size limit") === false) {
                throw new Exception("Wrong validation error for oversized data");
            }
        }

        echo "✓ testValidation passed\n";
    }

    public function testRecursionDepthLimit() {
        // Create deeply nested structure
        $data = ['문항' => 'test'];
        $current = &$data;
        for ($i = 0; $i < 15; $i++) {
            $current['nested'] = ['해설' => 'level ' . $i];
            $current = &$current['nested'];
        }

        $normalized = ApiResponseNormalizer::normalize($data);

        // Should stop at depth 10 and return data as-is for deeper levels
        if (!isset($normalized['question'])) {
            throw new Exception("Top-level normalization failed");
        }

        echo "✓ testRecursionDepthLimit passed\n";
    }

    public function runAll() {
        echo "\n=== ApiResponseNormalizer Tests ===\n";
        $this->testNormalizeKoreanKeys();
        $this->testNormalizeMixedKeys();
        $this->testExtractJsonFromMixedContent();
        $this->testEnsureArray();
        $this->testRealFixture();
        $this->testNestedNormalization();
        $this->testExtractNestedJson();
        $this->testExtractJsonWithBrackets();
        $this->testValidation();
        $this->testRecursionDepthLimit();
        echo "All tests passed!\n\n";
    }
}

// Run tests
$test = new ApiResponseNormalizerTest();
$test->runAll();
