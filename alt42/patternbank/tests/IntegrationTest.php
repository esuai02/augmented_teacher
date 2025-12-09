<?php
/**
 * IntegrationTest.php - Comprehensive integration tests for PatternBank safe JSON system
 *
 * Tests the complete workflow across all refactored files:
 * - patternbank_ajax.php
 * - generate_similar_problem.php
 * - openai_config.php
 * - JsonSafeHelper, FormulaEncoder, ApiResponseNormalizer
 */

require_once(__DIR__ . '/bootstrap.php');

class IntegrationTest {
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function runAllTests() {
        echo "\n╔════════════════════════════════════════════════════════════╗\n";
        echo "║     INTEGRATION TEST SUITE - PatternBank Safe JSON        ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        // Component Integration Tests
        echo "━━━ COMPONENT INTEGRATION TESTS ━━━\n";
        $this->testFormulaEncoderWithNormalizer();
        $this->testJsonSafeHelperEndToEnd();
        $this->testNestedKoreanWithFormulas();

        // Production File Integration Tests
        echo "\n━━━ PRODUCTION FILE INTEGRATION TESTS ━━━\n";
        $this->testGPTResponseFlowThroughAllFiles();
        $this->testErrorResponseWithKorean();
        $this->testMixedContentExtraction();

        // Database Integration Tests
        echo "\n━━━ DATABASE INTEGRATION TESTS ━━━\n";
        $this->testDatabaseRoundTrip();

        // Edge Case Tests
        echo "\n━━━ EDGE CASE TESTS ━━━\n";
        $this->testExtremeFormulas();
        $this->testMixedKoreanEnglish();
        $this->testMalformedAPIResponses();

        // Error Path Tests
        echo "\n━━━ ERROR PATH TESTS ━━━\n";
        $this->testErrorPathFallbacks();
        $this->testUltimateFallbackTrigger();

        $this->printResults();
    }

    // ========== COMPONENT INTEGRATION TESTS ==========

    private function testFormulaEncoderWithNormalizer() {
        $testName = "FormulaEncoder + ApiResponseNormalizer Integration";
        echo "Testing: $testName... ";

        try {
            // Load test fixture with Korean keys and formulas
            $rawData = file_get_contents(__DIR__ . '/fixtures/gpt_response_with_formulas.json');
            $data = json_decode($rawData, true);

            // Step 1: Normalize Korean keys
            $normalized = [];
            foreach ($data as $problem) {
                $normalized[] = ApiResponseNormalizer::normalize($problem);
            }

            // Step 2: Encode formulas
            $encoded = FormulaEncoder::encode($normalized);

            // Verify formulas are encoded
            $jsonEncoded = json_encode($encoded);
            $this->assert(
                strpos($jsonEncoded, '{{FORMULA:') !== false,
                "Formulas should be encoded to {{FORMULA:...}} markers"
            );
            $this->assert(
                strpos($jsonEncoded, '$x^2') === false,
                "Raw LaTeX should not appear in encoded JSON"
            );

            // Step 3: Decode formulas
            $decoded = FormulaEncoder::decode($encoded);

            // Verify formulas are restored
            $jsonDecoded = json_encode($decoded);
            $this->assert(
                strpos($jsonDecoded, '$x^2') !== false,
                "LaTeX formulas should be restored after decoding"
            );

            // Verify Korean keys were normalized to English
            $this->assert(
                isset($decoded[0]['question']),
                "Korean key '문항' should be normalized to 'question'"
            );
            $this->assert(
                isset($decoded[0]['solution']),
                "Korean key '해설' should be normalized to 'solution'"
            );
            $this->assert(
                isset($decoded[0]['choices']),
                "Korean key '선택지' should be normalized to 'choices'"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testJsonSafeHelperEndToEnd() {
        $testName = "JsonSafeHelper End-to-End Workflow";
        echo "Testing: $testName... ";

        try {
            // Create test data with Korean keys and formulas
            $testData = [
                '문항' => '다음 방정식을 풀어라: $ax^2 + bx + c = 0$',
                '해설' => '근의 공식: $x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}$',
                '정답' => '$x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}$'
            ];

            // Step 1: Encode with JsonSafeHelper (3-layer protection)
            $json = JsonSafeHelper::safeEncode($testData);

            // Verify it's valid JSON
            $this->assert(
                JsonSafeHelper::isValid($json),
                "Encoded output should be valid JSON"
            );

            // Verify formulas are protected
            $this->assert(
                strpos($json, '{{FORMULA:') !== false,
                "Formulas should be encoded in JSON"
            );

            // Verify Korean keys are normalized
            $this->assert(
                strpos($json, '"question"') !== false,
                "Korean keys should be normalized to English"
            );

            // Step 2: Decode with JsonSafeHelper
            $restored = JsonSafeHelper::safeDecode($json);

            // Verify formulas are restored
            $this->assert(
                strpos($restored['question'], '$ax^2') !== false,
                "Formulas should be restored in decoded data"
            );

            // Verify structure is preserved
            $this->assert(
                isset($restored['question']) && isset($restored['solution']) && isset($restored['answer']),
                "All normalized keys should be present"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testNestedKoreanWithFormulas() {
        $testName = "Nested Korean Keys with Formulas";
        echo "Testing: $testName... ";

        try {
            $rawData = file_get_contents(__DIR__ . '/fixtures/gpt_response_nested_korean.json');

            // Extract JSON and process
            $extracted = ApiResponseNormalizer::extractJson($rawData);
            $decoded = JsonSafeHelper::safeDecode($extracted);

            // Verify deeply nested Korean keys were normalized
            $this->assert(
                isset($decoded['문제집']),  // Not normalized at top level
                "Top-level Korean keys should remain if not in keyMap"
            );

            $this->assert(
                isset($decoded['문제집']['문항들'][0]['question']),
                "Nested '문항' should be normalized to 'question'"
            );

            // Verify formulas in nested structures
            $this->assert(
                strpos($decoded['문제집']['문항들'][0]['question'], '$f(x)') !== false,
                "Formulas in nested structures should be preserved"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    // ========== PRODUCTION FILE INTEGRATION TESTS ==========

    private function testGPTResponseFlowThroughAllFiles() {
        $testName = "GPT Response Flow (3-file integration)";
        echo "Testing: $testName... ";

        try {
            // Simulate GPT API response (from openai_config.php perspective)
            $rawGPTResponse = file_get_contents(__DIR__ . '/fixtures/gpt_response_with_formulas.json');

            // Step 1: Parse GPT response (openai_config.php pattern)
            try {
                $parsed = JsonSafeHelper::safeDecode($rawGPTResponse);
            } catch (Exception $e) {
                // Fallback to extractJson
                $extracted = ApiResponseNormalizer::extractJson($rawGPTResponse);
                $parsed = JsonSafeHelper::safeDecode($extracted);
            }

            $this->assert(
                is_array($parsed) && count($parsed) > 0,
                "GPT response should be parsed to array"
            );

            // Step 2: Encode for database storage (generate_similar_problem.php pattern)
            foreach ($parsed as $problem) {
                if (!empty($problem['choices'])) {
                    $choicesJson = JsonSafeHelper::safeEncode($problem['choices']);
                    $this->assert(
                        JsonSafeHelper::isValid($choicesJson),
                        "Choices should encode to valid JSON for database"
                    );
                }
            }

            // Step 3: Retrieve and send to client (patternbank_ajax.php pattern)
            $responseJson = JsonSafeHelper::safeEncode(['problems' => $parsed]);
            $this->assert(
                JsonSafeHelper::isValid($responseJson),
                "Final response should be valid JSON"
            );

            // Verify formulas survived the entire flow
            $finalData = JsonSafeHelper::safeDecode($responseJson);
            $this->assert(
                strpos($finalData['problems'][0]['question'], '$x^2') !== false,
                "Formulas should survive complete 3-file workflow"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testErrorResponseWithKorean() {
        $testName = "Error Response with Korean Text";
        echo "Testing: $testName... ";

        try {
            // Load Korean error response
            $errorResponse = file_get_contents(__DIR__ . '/fixtures/gpt_error_korean.json');

            // Parse error response (openai_config.php P0-1 fix pattern)
            try {
                $errorData = JsonSafeHelper::safeDecode($errorResponse);
                $errorMsg = $errorData['error']['message'] ?? "Unknown error";
            } catch (Exception $e) {
                $errorMsg = "Error parsing failed";
            }

            $this->assert(
                strpos($errorMsg, '요청') !== false,
                "Korean error message should be preserved"
            );

            // Test ultimate fallback pattern (P0-3 fix)
            try {
                $safeResponse = JsonSafeHelper::safeEncode(['error' => $errorMsg]);
            } catch (Exception $jsonEx) {
                $safeResponse = '{"error":"Request failed"}';  // ASCII-only
            }

            $this->assert(
                JsonSafeHelper::isValid($safeResponse),
                "Error response should always be valid JSON"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testMixedContentExtraction() {
        $testName = "Mixed Content (Text + JSON) Extraction";
        echo "Testing: $testName... ";

        try {
            // Load mixed content fixture
            $mixedContent = file_get_contents(__DIR__ . '/fixtures/gpt_response_mixed_content.txt');

            // Extract pure JSON using balanced bracket algorithm
            $extracted = ApiResponseNormalizer::extractJson($mixedContent);

            // Verify extraction worked
            $this->assert(
                json_decode($extracted, true) !== null,
                "Extracted content should be valid JSON"
            );

            // Verify no surrounding text
            $this->assert(
                strpos($extracted, 'Here are') === false,
                "Prefix text should be removed"
            );
            $this->assert(
                strpos($extracted, 'I hope') === false,
                "Suffix text should be removed"
            );

            // Parse and verify formulas
            $data = JsonSafeHelper::safeDecode($extracted);
            $this->assert(
                strpos($data[0]['question'], '$\\frac{d}{dx}') !== false,
                "Formulas in extracted JSON should be preserved"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    // ========== DATABASE INTEGRATION TESTS ==========

    private function testDatabaseRoundTrip() {
        $testName = "Database Round-Trip (Save/Retrieve)";
        echo "Testing: $testName... ";

        try {
            global $DB;

            // Create test problem with formulas
            $testProblem = [
                'question' => '다음 극한을 계산하라: $\\lim_{x \\to 0} \\frac{\\sin x}{x}$',
                'solution' => '로피탈 정리를 사용하면 답은 $1$입니다.',
                'answer' => '$1$',
                'choices' => ['$0$', '$1$', '$\\infty$', '존재하지 않음']
            ];

            // Encode choices for database storage
            $choicesJson = JsonSafeHelper::safeEncode($testProblem['choices']);

            // Simulate database save
            $record = new stdClass();
            $record->problem = $testProblem['question'];
            $record->inputanswer = $choicesJson;
            $record->solution = $testProblem['solution'];
            $record->answer = $testProblem['answer'];
            $record->timecreated = time();

            // Insert to database
            $insertedId = $DB->insert_record('abessi_patternbank', $record);

            // Retrieve from database
            $retrieved = $DB->get_record('abessi_patternbank', ['id' => $insertedId]);

            // Decode choices
            $decodedChoices = JsonSafeHelper::safeDecode($retrieved->inputanswer);

            // Verify formulas are preserved
            $this->assert(
                $decodedChoices[1] === '$1$',
                "Formula in choices should survive database round-trip"
            );

            $this->assert(
                count($decodedChoices) === 4,
                "All choices should be preserved"
            );

            // Cleanup
            $DB->delete_records('abessi_patternbank', ['id' => $insertedId]);

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    // ========== EDGE CASE TESTS ==========

    private function testExtremeFormulas() {
        $testName = "Extreme Formulas (Deeply Nested)";
        echo "Testing: $testName... ";

        try {
            // Create extremely complex formula
            $extremeFormula = '$\\frac{\\sqrt{\\frac{x^2 + y^2}{z^2}}}{\\log(\\sin(\\theta)) + \\cos^2(\\phi)}$';

            $data = [
                'question' => "간단히 하라: $extremeFormula",
                'choices' => [$extremeFormula, 'Cannot simplify', '$1$', '$0$']
            ];

            // Encode and decode
            $json = JsonSafeHelper::safeEncode($data);
            $restored = JsonSafeHelper::safeDecode($json);

            // Verify extreme formula is preserved
            $this->assert(
                strpos($restored['question'], '\\frac{\\sqrt{') !== false,
                "Deeply nested formula should be preserved"
            );

            $this->assert(
                $restored['choices'][0] === $extremeFormula,
                "Formula in array should match original"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testMixedKoreanEnglish() {
        $testName = "Mixed Korean/English Keys";
        echo "Testing: $testName... ";

        try {
            $rawData = file_get_contents(__DIR__ . '/fixtures/gpt_response_korean_keys.json');
            $data = json_decode($rawData, true);

            // Normalize mixed content
            $normalized = [];
            foreach ($data as $problem) {
                $normalized[] = ApiResponseNormalizer::normalize($problem);
            }

            // Verify all keys are normalized to English
            $this->assert(
                isset($normalized[0]['question']) && isset($normalized[1]['question']),
                "Both Korean and English 'question' keys should be normalized"
            );

            $this->assert(
                isset($normalized[0]['solution']) && isset($normalized[1]['solution']),
                "Both Korean and English 'solution' keys should be normalized"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testMalformedAPIResponses() {
        $testName = "Malformed API Responses (Error Recovery)";
        echo "Testing: $testName... ";

        try {
            // Test various malformed inputs
            $malformedInputs = [
                'Not JSON at all',
                '{"incomplete": ',
                '}{invalid}',
                '',
                null
            ];

            $recoveryCount = 0;
            foreach ($malformedInputs as $input) {
                try {
                    if ($input === null) {
                        $result = JsonSafeHelper::safeEncode($input);
                        $this->assert($result === '{}', "Null should encode to empty object");
                        $recoveryCount++;
                    } else {
                        JsonSafeHelper::safeDecode($input);
                    }
                } catch (Exception $e) {
                    // Expected to fail - error handling working
                    $recoveryCount++;
                }
            }

            $this->assert(
                $recoveryCount === count($malformedInputs),
                "All malformed inputs should be handled gracefully"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    // ========== ERROR PATH TESTS ==========

    private function testErrorPathFallbacks() {
        $testName = "Error Path Fallback Chain";
        echo "Testing: $testName... ";

        try {
            // Test 2-stage fallback: safeDecode → extractJson
            $mixedContent = 'Explanation: {"question": "$x^2 = 4$"}';

            try {
                $result = JsonSafeHelper::safeDecode($mixedContent);
                $method = 'direct';
            } catch (Exception $e) {
                // Fallback to extractJson
                try {
                    $extracted = ApiResponseNormalizer::extractJson($mixedContent);
                    $result = JsonSafeHelper::safeDecode($extracted);
                    $method = 'extractJson';
                } catch (Exception $e2) {
                    $method = 'failed';
                }
            }

            $this->assert(
                $method === 'extractJson',
                "Mixed content should trigger extractJson fallback"
            );

            $this->assert(
                isset($result['question']),
                "Fallback should successfully extract data"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    private function testUltimateFallbackTrigger() {
        $testName = "Ultimate ASCII-Only Fallback";
        echo "Testing: $testName... ";

        try {
            // Create error response that needs fallback
            $koreanError = "JSON 인코딩 실패: 수식 오류";

            // Test ultimate fallback pattern (P0-3 fix)
            try {
                $response = JsonSafeHelper::safeEncode(['error' => $koreanError]);
            } catch (Exception $jsonEx) {
                // Ultimate fallback triggered
                $response = '{"error":"Request failed"}';
            }

            // Verify response is always valid
            $this->assert(
                JsonSafeHelper::isValid($response),
                "Ultimate fallback should always produce valid JSON"
            );

            // Verify ASCII-only (no Korean characters)
            $decoded = json_decode($response, true);
            $this->assert(
                preg_match('/^[\x00-\x7F]*$/', $decoded['error']),
                "Ultimate fallback should be ASCII-only"
            );

            $this->pass($testName);
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    // ========== TEST HELPERS ==========

    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }

    private function pass($testName) {
        echo "✅ PASS\n";
        $this->testResults[] = ['name' => $testName, 'status' => 'PASS'];
        $this->totalTests++;
        $this->passedTests++;
    }

    private function fail($testName, $error) {
        echo "❌ FAIL\n";
        echo "   Error: $error\n";
        $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'error' => $error];
        $this->totalTests++;
        $this->failedTests++;
    }

    private function printResults() {
        echo "\n╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    TEST RESULTS SUMMARY                    ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        echo "Total Tests: $this->totalTests\n";
        echo "✅ Passed: $this->passedTests\n";
        echo "❌ Failed: $this->failedTests\n";
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 1) . "%\n\n";

        if ($this->failedTests > 0) {
            echo "━━━ FAILED TESTS ━━━\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "❌ {$result['name']}\n";
                    echo "   {$result['error']}\n\n";
                }
            }
        }

        echo "\n" . str_repeat("━", 60) . "\n";

        if ($this->failedTests === 0) {
            echo "🎉 ALL TESTS PASSED! System is production-ready.\n";
        } else {
            echo "⚠️  Some tests failed. Review errors above.\n";
        }

        echo str_repeat("━", 60) . "\n\n";
    }
}

// Run tests
$test = new IntegrationTest();
$test->runAllTests();
