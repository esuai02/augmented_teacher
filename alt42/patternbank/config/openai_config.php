<?php
/**
 * OpenAI GPT-5 Responses API Configuration for PatternBank
 * Uses GPT-5 with the new Responses API endpoint
 * Based on official OpenAI GPT-5 documentation
 */

// Load API key from secure config
require_once(__DIR__ . '/api_keys.php');

// Load safe JSON handling libraries
require_once(__DIR__ . '/../lib/JsonSafeHelper.php');
require_once(__DIR__ . '/../lib/ApiResponseNormalizer.php');

// Define the PatternBank constant for compatibility
if (!defined('PATTERNBANK_OPENAI_API_KEY')) {
    define('PATTERNBANK_OPENAI_API_KEY', OPENAI_API_KEY_SECURE);
}

// GPT-5 Configuration
define('PATTERNBANK_GPT5_MODEL', 'gpt-4o'); // Using gpt-5-mini for cost efficiency
define('PATTERNBANK_GPT5_TEMPERATURE', 0.1);
define('PATTERNBANK_GPT5_MAX_TOKENS', 4000);

/**
 * Test GPT-5 API connection
 * @return array Test result with success status and details
 */
function testPatternBankGPT5() {
    $testMessage = "간단히 1+1을 계산해주세요.";

    $result = callGPT5Responses($testMessage, [
        'reasoning' => ['effort' => 'minimal'],
        'text' => ['verbosity' => 'low']
    ]);

    if (isset($result['error'])) {
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }

    return [
        'success' => true,
        'model' => PATTERNBANK_GPT5_MODEL,
        'response' => $result['output_text'] ?? 'No response',
        'usage' => $result['usage'] ?? []
    ];
}

/**
 * Generate similar problems using GPT-5
 * @param array $originalProblem Original problem data
 * @param string $type Problem type (similar or modified)
 * @return array Generated problems or error
 */
function generateSimilarProblemsGPT5($originalProblem, $type = 'similar') {
    $numProblems = 1;

    // Construct the prompt for GPT-5
    $prompt = "다음 수학 문제를 바탕으로 ";
    $prompt .= ($type === 'similar') ? "유사한" : "변형된";
    $prompt .= " 문제를 1개 생성해주세요.\n\n";

    if (!empty($originalProblem['question'])) {
        $prompt .= "원본 문제:\n";
        $prompt .= "문제: " . $originalProblem['question'] . "\n";

        if (!empty($originalProblem['solution'])) {
            $prompt .= "해설: " . $originalProblem['solution'] . "\n";
        }

        if (!empty($originalProblem['choices'])) {
            $prompt .= "선택지:\n";
            foreach ($originalProblem['choices'] as $choice) {
                $prompt .= "- " . $choice . "\n";
            }
        }
    }

    $prompt .= "\n생성 규칙:\n";
    if ($type === 'similar') {
        $prompt .= "1. 같은 개념을 다루되 숫자나 조건을 바꿔주세요\n";
        $prompt .= "2. 난이도는 비슷하게 유지해주세요\n";
        $prompt .= "3. 문제 형식은 동일하게 유지해주세요\n";
    } else {
        $prompt .= "1. 핵심 개념은 유지하되 접근 방법을 다르게 해주세요\n";
        $prompt .= "2. 난이도를 약간 높이거나 낮춰주세요\n";
        $prompt .= "3. 다른 상황이나 맥락으로 바꿔주세요\n";
    }

    $prompt .= "\nJSON 형식으로 작성해주세요:\n";
    $prompt .= '{"question": "[문제 내용]", "solution": "[상세한 풀이 과정과 답]"}';

    // Call GPT-5 with structured output
    $schema = [
        'type' => 'object',
        'properties' => [
            'question' => ['type' => 'string'],
            'solution' => ['type' => 'string'],
            'choices' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'maxItems' => 5,
                'required' => false
            ]
        ],
        'required' => ['question', 'solution'],
        'additionalProperties' => false
    ];

    $result = callGPT5Responses($prompt, [
        'reasoning' => ['effort' => ($type === 'similar') ? 'low' : 'medium'],
        'text' => ['verbosity' => 'medium'],
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'MathProblem',
                'schema' => $schema
            ]
        ]
    ]);

    if (isset($result['error'])) {
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }

    // Parse the structured output using safe JSON handling
    $parsedOutput = isset($result['output_parsed']) ? $result['output_parsed'] : null;

    if (!$parsedOutput && isset($result['output_text'])) {
        try {
            $parsedOutput = JsonSafeHelper::safeDecode($result['output_text']);
        } catch (Exception $e) {
            error_log("[openai_config.php:" . __LINE__ . "] Failed to parse structured output: " . $e->getMessage());
            $parsedOutput = null;
        }
    }

    if (!$parsedOutput) {
        return [
            'success' => false,
            'error' => 'Failed to parse generated problem'
        ];
    }

    return [
        'success' => true,
        'problems' => [$parsedOutput],
        'usage' => $result['usage'] ?? []
    ];
}

/**
 * Call GPT-5 Responses API
 * @param string $input User input
 * @param array $options Additional options (reasoning, text, response_format, etc.)
 * @return array API response or error
 */
function callGPT5Responses($input, $options = []) {
    $apiKey = PATTERNBANK_OPENAI_API_KEY;
    $model = $options['model'] ?? PATTERNBANK_GPT5_MODEL;

    $data = [
        'model' => $model,
        'input' => $input
        // temperature not supported in GPT-5 API
    ];

    // Add reasoning configuration if specified
    if (isset($options['reasoning'])) {
        $data['reasoning'] = $options['reasoning'];
    }

    // Add text configuration with response format moved here
    if (isset($options['text'])) {
        $data['text'] = $options['text'];

        // Move response_format to text.format as per API requirement
        if (isset($options['response_format'])) {
            // Transform the format structure for GPT-5 API
            $format = [
                'type' => $options['response_format']['type'] ?? 'json_schema',
                'name' => $options['response_format']['json_schema']['name'] ?? 'Response',
                'schema' => $options['response_format']['json_schema']['schema'] ?? null,
                'strict' => $options['response_format']['json_schema']['strict'] ?? true
            ];
            $data['text']['format'] = $format;
        }
    } else if (isset($options['response_format'])) {
        // If text not set but response_format is, create text with format
        $format = [
            'type' => $options['response_format']['type'] ?? 'json_schema',
            'name' => $options['response_format']['json_schema']['name'] ?? 'Response',
            'schema' => $options['response_format']['json_schema']['schema'] ?? null,
            'strict' => $options['response_format']['json_schema']['strict'] ?? true
        ];
        $data['text'] = ['format' => $format];
    }

    // Use max_output_tokens as per API requirement
    if (isset($options['max_tokens'])) {
        $data['max_output_tokens'] = $options['max_tokens'];
    } elseif (defined('PATTERNBANK_GPT5_MAX_TOKENS')) {
        $data['max_output_tokens'] = PATTERNBANK_GPT5_MAX_TOKENS;
    }

    // Add tools if specified
    if (isset($options['tools'])) {
        $data['tools'] = $options['tools'];
    }

    // Add tool_choice if specified
    if (isset($options['tool_choice'])) {
        $data['tool_choice'] = $options['tool_choice'];
    }

    // Safe JSON encode for API request with Korean content
    try {
        $jsonPayload = JsonSafeHelper::safeEncode($data);
    } catch (Exception $e) {
        error_log("[openai_config.php:" . __LINE__ . "] Failed to encode API request: " . $e->getMessage());
        try {
            return JsonSafeHelper::safeEncode(['error' => 'Failed to encode API request']);
        } catch (Exception $jsonEx) {
            error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
            return ['error' => 'Request failed'];  // ASCII-only guaranteed
        }
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("[openai_config.php:" . __LINE__ . "] CURL error: " . $error);
        try {
            return JsonSafeHelper::safeEncode(['error' => 'CURL error: ' . $error]);
        } catch (Exception $jsonEx) {
            error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
            return ['error' => 'Request failed'];  // ASCII-only guaranteed
        }
    }

    if ($httpCode !== 200) {
        // Error responses may contain Korean text - use safe decode
        try {
            $responseData = JsonSafeHelper::safeDecode($response);
            $errorMsg = isset($responseData['error']['message'])
                ? $responseData['error']['message']
                : "HTTP $httpCode error";
        } catch (Exception $e) {
            error_log("[openai_config.php:" . __LINE__ . "] Failed to parse error response: " . $e->getMessage());
            $errorMsg = "HTTP $httpCode error (response parse failed)";
        }
        error_log("[openai_config.php:" . __LINE__ . "] HTTP error $httpCode: $errorMsg");
        try {
            return JsonSafeHelper::safeEncode(['error' => $errorMsg]);
        } catch (Exception $jsonEx) {
            error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
            return ['error' => 'Request failed'];  // ASCII-only guaranteed
        }
    }

    // CRITICAL: Parse GPT-5 API response with safe JSON handling
    try {
        $responseData = JsonSafeHelper::safeDecode($response);
    } catch (Exception $e) {
        error_log("[openai_config.php:" . __LINE__ . "] First decode failed, using extractJson: " . $e->getMessage());
        try {
            $extracted = ApiResponseNormalizer::extractJson($response);
            $responseData = JsonSafeHelper::safeDecode($extracted);
        } catch (Exception $e2) {
            error_log("[openai_config.php:" . __LINE__ . "] extractJson also failed: " . $e2->getMessage());
            try {
                return JsonSafeHelper::safeEncode(['error' => 'GPT 응답을 파싱할 수 없습니다']);
            } catch (Exception $jsonEx) {
                error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                return ['error' => 'Request failed'];  // ASCII-only guaranteed
            }
        }
    }

    // GPT-5 Responses API returns output array with reasoning and message
    if (isset($responseData['output']) && is_array($responseData['output'])) {
        $outputText = null;
        $outputParsed = null;

        foreach ($responseData['output'] as $output) {
            if ($output['type'] === 'message' && isset($output['content'])) {
                // Handle both string and array content
                if (is_array($output['content'])) {
                    // Content is an array, extract text from first item
                    $outputText = isset($output['content'][0]['text']) ? $output['content'][0]['text'] : json_encode($output['content']);
                } else {
                    $outputText = $output['content'];
                }

                // CRITICAL: Try to parse JSON content if it looks like JSON
                if (is_string($outputText) && (substr($outputText, 0, 1) === '{' || substr($outputText, 0, 1) === '[')) {
                    try {
                        $outputParsed = JsonSafeHelper::safeDecode($outputText);
                    } catch (Exception $e) {
                        error_log("[openai_config.php:" . __LINE__ . "] Failed to parse output text as JSON: " . $e->getMessage());
                        // Try extractJson for mixed content
                        try {
                            $extracted = ApiResponseNormalizer::extractJson($outputText);
                            $outputParsed = JsonSafeHelper::safeDecode($extracted);
                        } catch (Exception $e2) {
                            error_log("[openai_config.php:" . __LINE__ . "] extractJson on output text also failed: " . $e2->getMessage());
                            // Not fatal - outputParsed remains null
                        }
                    }
                }
                break;
            }
        }

        // Add parsed output to response
        $responseData['output_text'] = $outputText;
        if ($outputParsed !== null) {
            $responseData['output_parsed'] = $outputParsed;
        }
    }

    // Check if we have valid output
    if (!isset($responseData['output_text']) && !isset($responseData['output_parsed'])) {
        error_log("[openai_config.php:" . __LINE__ . "] Invalid API response structure");
        try {
            return JsonSafeHelper::safeEncode(['error' => 'Invalid API response structure']);
        } catch (Exception $jsonEx) {
            error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
            return ['error' => 'Request failed'];  // ASCII-only guaranteed
        }
    }

    return $responseData;
}

/**
 * Fallback to GPT-4o if GPT-5 fails
 * Automatically switches to the backup configuration
 */
function autoFallbackToGPT4() {
    $backupFile = __DIR__ . '/openai_config_backup.php';
    if (file_exists($backupFile)) {
        require_once($backupFile);
        return true;
    }
    return false;
}

/**
 * Smart API caller with automatic fallback
 * Tries GPT-5 first, falls back to GPT-4o if it fails
 */
function generateSimilarProblemsSmart($originalProblem, $type = 'similar') {
    // Try GPT-5 first
    $result = generateSimilarProblemsGPT5($originalProblem, $type);

    // If GPT-5 fails, try fallback to GPT-4o
    if (!$result['success']) {
        error_log("[openai_config.php:" . __LINE__ . "] GPT-5 API failed: " . $result['error'] . ". Falling back to GPT-4o.");

        // Load backup configuration
        if (autoFallbackToGPT4()) {
            // Call the original function from backup
            if (function_exists('generateSimilarProblems')) {
                $result = generateSimilarProblems($originalProblem, $type);
                $result['fallback_used'] = true;
                $result['fallback_reason'] = 'GPT-5 API unavailable';
            }
        }
    }

    return $result;
}

// Wrapper functions for backward compatibility
if (!function_exists('testPatternBankOpenAI')) {
    function testPatternBankOpenAI() {
        return testPatternBankGPT5();
    }
}

if (!function_exists('generateSimilarProblems')) {
    function generateSimilarProblems($originalProblem, $type = 'similar') {
        // GPT-5 API는 아직 공개되지 않았을 수 있으므로, 바로 GPT-4o로 폴백
        // 백업 설정 파일 로드 (GPT-4o 사용)
        $backupFile = __DIR__ . '/openai_config_backup.php';
        if (file_exists($backupFile)) {
            // 백업 파일의 함수들을 임시로 로드
            $backupFunctions = file_get_contents($backupFile);

            // generateSimilarProblems 함수만 추출하여 실행
            // 직접 GPT-4o API 호출
            $numProblems = 1;

            // 프롬프트 구성
            $prompt = "당신은 한국 고등학교 수학 교육과정 전문가입니다.\n\n";
            
            // 유형 분석 정보가 있으면 먼저 강조
            if (!empty($originalProblem['analysis'])) {
                $prompt .= "=== 중요: 유형 분석 ===\n";
                $prompt .= $originalProblem['analysis'] . "\n\n";
                $prompt .= "**반드시 준수사항**: 위 유형 분석에 명시된 수학 주제와 문제 유형을 정확히 따라야 합니다. 다른 주제(예: 일차방정식, 이차방정식 등)로 생성하면 안 됩니다.\n\n";
            }

            // 이미지가 있으면 이미지에서 문제를 추출하도록 지시
            if (!empty($originalProblem['imageUrl'])) {
                $prompt .= "=== 대표유형 이미지 ===\n";
                $prompt .= "이미지에 있는 문제를 먼저 분석하고, 그 문제의 유형과 특징을 파악한 후 ";
                $prompt .= ($type === 'similar') ? "유사한" : "변형된";
                $prompt .= " 문제를 생성해주세요.\n\n";
                $prompt .= "**이미지 분석 지침**:\n";
                $prompt .= "1. 이미지에 있는 문제의 수학 주제를 정확히 파악하세요 (예: 경우의 수, 순열, 조합, 확률 등)\n";
                $prompt .= "2. 문제의 핵심 개념과 해결 방법을 이해하세요\n";
                $prompt .= "3. 유형 분석에 명시된 주제와 일치하는지 확인하세요\n\n";
            }

            if (!empty($originalProblem['question'])) {
                $prompt .= "=== 원본 문제 (참고용) ===\n";
                $prompt .= "문제: " . $originalProblem['question'] . "\n";

                if (!empty($originalProblem['solution'])) {
                    $prompt .= "해설: " . $originalProblem['solution'] . "\n";
                }

                if (!empty($originalProblem['choices'])) {
                    $prompt .= "선택지:\n";
                    foreach ($originalProblem['choices'] as $choice) {
                        $prompt .= "- " . $choice . "\n";
                    }
                }
                $prompt .= "\n";
            } else {
                // 원본 문제가 없으면 유형 분석만으로 생성
                if (!empty($originalProblem['analysis'])) {
                    $prompt .= "원본 문제가 제공되지 않았으므로, 위 유형 분석에 명시된 수학 주제와 특징을 바탕으로 새로운 문제를 생성해주세요.\n\n";
                }
            }

            $prompt .= "=== 생성 요구사항 ===\n";
            if ($type === 'similar') {
                $prompt .= "**유사문제 생성 규칙**:\n";
                $prompt .= "1. **같은 수학 주제와 개념**을 사용해야 합니다 (유형 분석에 명시된 주제)\n";
                $prompt .= "2. **문제 구조와 형식**을 동일하게 유지하세요\n";
                $prompt .= "3. 숫자나 조건만 바꾸되, **핵심 개념은 변경하지 마세요**\n";
                $prompt .= "4. 난이도는 비슷하게 유지하세요\n";
                if (!empty($originalProblem['analysis'])) {
                    $prompt .= "5. **유형 분석에 명시된 주제와 다른 주제로 생성하면 안 됩니다**\n";
                }
            } else {
                $prompt .= "**변형문제 생성 규칙**:\n";
                $prompt .= "1. **같은 수학 주제**를 유지하되 접근 방법을 다르게 하세요\n";
                $prompt .= "2. 핵심 개념은 유지하되 문제 상황이나 맥락을 변경하세요\n";
                $prompt .= "3. 난이도를 약간 높이거나 낮춰도 되지만, **주제는 변경하면 안 됩니다**\n";
                if (!empty($originalProblem['analysis'])) {
                    $prompt .= "4. **유형 분석에 명시된 주제를 기반으로 변형**하세요\n";
                }
            }

            $prompt .= "\n=== 출력 형식 ===\n";
            $prompt .= "**중요: 모든 수식은 반드시 $ 기호로 감싸야 합니다.**\n";
            $prompt .= "- 인라인 수식: $수식$\n";
            $prompt .= "- 예: $x^2 + 2x + 1 = 0$, $\\frac{a}{b}$, $\\sum_{i=1}^{n} a_i$\n";
            $prompt .= "- 선택지의 수식도 반드시 $ 기호로 감싸야 합니다: $\\frac{1}{2}$, $\\sqrt{2}$ 등\n\n";
            
            $prompt .= "다음 JSON 형식으로 작성해주세요:\n";
            $prompt .= '{"question": "[문제 내용 (모든 수식은 $...$ 형식 사용)]", "solution": "[상세한 풀이 과정과 답 (모든 수식은 $...$ 형식 사용)]", "choices": ["① 선택지1 (수식이 있으면 $...$ 형식)", "② 선택지2", "③ 선택지3", "④ 선택지4", "⑤ 선택지5"]}';
            
            $prompt .= "\n\n=== 출력 예제 (반드시 이 형식을 따르세요) ===\n";
            $exampleJson = [
                'question' => '다음 급수의 값을 구하시오: $\sum_{n=1}^{\infty} \frac{4^n}{(4^{n+1}+1)(4^n+1)}$',
                'solution' => '주어진 급수를 부분분수로 분해하면 $\frac{4^n}{(4^{n+1}+1)(4^n+1)} = \frac{1}{4^n+1} - \frac{1}{4^{n+1}+1}$이다. 따라서 $\sum_{n=1}^{\infty} \frac{4^n}{(4^{n+1}+1)(4^n+1)} = \lim_{n \to \infty} \sum_{k=1}^{n} \left(\frac{1}{4^k+1} - \frac{1}{4^{k+1}+1}\right) = \frac{1}{5} - 0 = \frac{1}{5}$이다.',
                'choices' => ['① $\frac{1}{8}$', '② $\frac{1}{6}$', '③ $\frac{1}{5}$', '④ $\frac{1}{4}$', '⑤ $1$']
            ];
            $prompt .= json_encode($exampleJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            $prompt .= "\n\n**최종 확인**:\n";
            $prompt .= "1. 생성된 문제가 유형 분석에 명시된 수학 주제와 일치하는지 확인하세요.\n";
            $prompt .= "2. 모든 수식이 $ 기호로 감싸져 있는지 확인하세요.\n";
            $prompt .= "3. 위 예제와 동일한 JSON 형식을 사용하세요.";

            // GPT-4o API 호출
            $apiKey = PATTERNBANK_OPENAI_API_KEY;
            $model = 'gpt-4o-mini';

            // 사용자 메시지 구성 (이미지가 있으면 배열 형식으로)
            $userContent = [];
            $userContent[] = [
                'type' => 'text',
                'text' => $prompt
            ];
            
            // 이미지 URL이 있으면 추가
            if (!empty($originalProblem['imageUrl'])) {
                $imageUrl = $originalProblem['imageUrl'];
                error_log("[openai_config.php:" . __LINE__ . "] Adding image URL to API request: " . substr($imageUrl, 0, 100));
                
                // 이미지 URL이 상대 경로인 경우 절대 URL로 변환
                if (strpos($imageUrl, 'http') !== 0) {
                    // 상대 경로인 경우 기본 URL 추가 (필요시 수정)
                    if (strpos($imageUrl, '/') === 0) {
                        $imageUrl = 'https://mathking.kr' . $imageUrl;
                    } else {
                        $imageUrl = 'https://mathking.kr/' . $imageUrl;
                    }
                }
                
                $userContent[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => $imageUrl]
                ];
            }

            // 시스템 메시지 구성
            $systemMessage = 'You are a Korean high school mathematics curriculum expert. Your task is to create practice problems that match the specific mathematical topic and problem type provided in the analysis. ';
            $systemMessage .= 'You must strictly follow the topic specified in the analysis (e.g., permutations, combinations, probability, sequences, etc.) and never create problems from different topics. ';
            $systemMessage .= 'Always respond in valid JSON format with question, solution, and choices fields. ';
            $systemMessage .= 'CRITICAL: All mathematical expressions MUST be wrapped with dollar signs ($...$). For example: $x^2 + 2x + 1 = 0$, $\\frac{a}{b}$, $\\sum_{i=1}^{n} a_i$. ';
            $systemMessage .= 'All formulas in choices must also be wrapped with $ signs, like $\\frac{1}{2}$ or $\\sqrt{2}$. ';
            $systemMessage .= 'Follow the exact JSON format provided in the example.';
            
            if (!empty($originalProblem['analysis'])) {
                $systemMessage .= ' The analysis section clearly specifies the mathematical topic - you must create problems only from that topic.';
            }
            
            $data = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemMessage
                    ],
                    [
                        'role' => 'user',
                        'content' => count($userContent) > 1 ? $userContent : $prompt  // 이미지가 있으면 배열, 없으면 문자열
                    ]
                ],
                'temperature' => 0.1,  // 매우 낮춰서 일관된 결과 생성 (예제 형식 유지)
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object']
            ];

            // Safe JSON encode for API request with Korean content
            try {
                $jsonPayload = JsonSafeHelper::safeEncode($data);
            } catch (Exception $e) {
                error_log("[openai_config.php:" . __LINE__ . "] Failed to encode fallback API request: " . $e->getMessage());
                try {
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => 'Failed to encode API request'
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("[openai_config.php:" . __LINE__ . "] CURL error: " . $error);
                try {
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => '네트워크 연결 오류가 발생했습니다: ' . $error,
                        'error_type' => 'network_error',
                        'error_code' => 'CURL_ERROR',
                        'error_details' => [
                            'type' => 'network_error',
                            'code' => 'CURL_ERROR',
                            'message' => $error,
                            'description' => 'API 서버에 연결할 수 없습니다. 네트워크 연결을 확인해주세요.'
                        ]
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            if ($httpCode !== 200) {
                // Error responses may contain Korean text - use safe decode
                try {
                    $responseData = JsonSafeHelper::safeDecode($response);
                    $errorMsg = isset($responseData['error']['message'])
                        ? $responseData['error']['message']
                        : "HTTP $httpCode error";
                    
                    // 토큰 초과 오류 감지
                    $errorType = isset($responseData['error']['type']) ? $responseData['error']['type'] : '';
                    $errorCode = isset($responseData['error']['code']) ? $responseData['error']['code'] : '';
                    
                    // 토큰 관련 오류 키워드 확인
                    $isTokenError = false;
                    $tokenErrorMsg = '';
                    
                    if (stripos($errorMsg, 'token') !== false || 
                        stripos($errorMsg, 'maximum context length') !== false ||
                        stripos($errorMsg, 'context_length_exceeded') !== false ||
                        stripos($errorCode, 'token') !== false ||
                        stripos($errorType, 'invalid_request_error') !== false && stripos($errorMsg, 'length') !== false) {
                        $isTokenError = true;
                        $maxTokens = defined('PATTERNBANK_GPT5_MAX_TOKENS') ? PATTERNBANK_GPT5_MAX_TOKENS : 2000;
                        $tokenErrorMsg = "최대 토큰 수 초과 오류가 발생했습니다. (현재 설정: Max Tokens = $maxTokens) ";
                        $tokenErrorMsg .= "응답이 너무 길어서 잘렸을 수 있습니다. max_tokens 값을 늘리거나 프롬프트를 단축해주세요.";
                    }
                    
                } catch (Exception $e) {
                    error_log("[openai_config.php:" . __LINE__ . "] Failed to parse error response: " . $e->getMessage());
                    $errorMsg = "HTTP $httpCode error (response parse failed)";
                    $isTokenError = false;
                    $tokenErrorMsg = '';
                }
                error_log("[openai_config.php:" . __LINE__ . "] HTTP error $httpCode: $errorMsg");
                try {
                    $finalErrorMsg = $isTokenError ? $tokenErrorMsg . "\n\n원본 오류: " . $errorMsg : $errorMsg;
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => $finalErrorMsg,
                        'is_token_error' => $isTokenError,
                        'max_tokens' => defined('PATTERNBANK_GPT5_MAX_TOKENS') ? PATTERNBANK_GPT5_MAX_TOKENS : 2000,
                        'error_type' => $errorType ?: 'api_error',
                        'error_code' => $errorCode ?: '',
                        'http_code' => $httpCode,
                        'error_details' => [
                            'type' => $errorType,
                            'code' => $errorCode,
                            'message' => $errorMsg,
                            'http_status' => $httpCode
                        ]
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            // CRITICAL: Parse GPT-4o API response with safe JSON handling
            try {
                $responseData = JsonSafeHelper::safeDecode($response);
            } catch (Exception $e) {
                error_log("[openai_config.php:" . __LINE__ . "] First decode failed, using extractJson: " . $e->getMessage());
                try {
                    $extracted = ApiResponseNormalizer::extractJson($response);
                    $responseData = JsonSafeHelper::safeDecode($extracted);
                } catch (Exception $e2) {
                    error_log("[openai_config.php:" . __LINE__ . "] extractJson also failed: " . $e2->getMessage());
                    try {
                        return JsonSafeHelper::safeEncode([
                            'success' => false,
                            'error' => 'GPT 응답을 파싱할 수 없습니다'
                        ]);
                    } catch (Exception $jsonEx) {
                        error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                        return [
                            'success' => false,
                            'error' => 'Request failed'
                        ];  // ASCII-only guaranteed
                    }
                }
            }

            if (!$responseData || !isset($responseData['choices'][0]['message']['content'])) {
                error_log("[openai_config.php:" . __LINE__ . "] Invalid API response structure");
                try {
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => 'Invalid API response structure'
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            // 토큰 제한으로 인한 응답 잘림 확인
            $finishReason = isset($responseData['choices'][0]['finish_reason']) ? $responseData['choices'][0]['finish_reason'] : null;
            $isTruncated = ($finishReason === 'length');
            
            $content = $responseData['choices'][0]['message']['content'];
            
            if ($isTruncated) {
                error_log("[openai_config.php:" . __LINE__ . "] Response truncated due to token limit. finish_reason: length");
            }

            // 원본 내용 로깅 (디버깅용)
            error_log("[openai_config.php:" . __LINE__ . "] Raw API response content (first 1000 chars): " . substr($content, 0, 1000));

            // CRITICAL: Extract and parse JSON with mixed content support
            try {
                $extracted = ApiResponseNormalizer::extractJson($content);
                $problem = JsonSafeHelper::safeDecode($extracted);
            } catch (Exception $e) {
                error_log("[openai_config.php:" . __LINE__ . "] Failed to extract/parse JSON: " . $e->getMessage());
                error_log("[openai_config.php:" . __LINE__ . "] Full content (first 1500 chars): " . substr($content, 0, 1500));
                try {
                    $isTruncatedForParsing = $isTruncated;
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => '응답 파싱 오류가 발생했습니다: ' . $e->getMessage() . ($isTruncatedForParsing ? ' (응답이 토큰 제한으로 잘렸을 수 있습니다)' : ''),
                        'error_type' => 'parsing_error',
                        'error_code' => 'JSON_PARSE_ERROR',
                        'is_truncated' => $isTruncatedForParsing,
                        'error_details' => [
                            'type' => 'parsing_error',
                            'code' => 'JSON_PARSE_ERROR',
                            'message' => $e->getMessage(),
                            'description' => 'API 응답을 JSON 형식으로 파싱하는 중 오류가 발생했습니다.',
                            'is_response_truncated' => $isTruncatedForParsing
                        ]
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            if (!$problem || empty($problem['question']) || empty($problem['solution'])) {
                error_log("[openai_config.php:" . __LINE__ . "] Invalid problem structure. Content: " . substr($content, 0, 500));
                try {
                    $missingFields = [];
                    if (empty($problem['question'])) $missingFields[] = 'question';
                    if (empty($problem['solution'])) $missingFields[] = 'solution';
                    
                    return JsonSafeHelper::safeEncode([
                        'success' => false,
                        'error' => '생성된 문제 형식이 올바르지 않습니다. 필수 필드가 누락되었습니다: ' . implode(', ', $missingFields),
                        'error_type' => 'validation_error',
                        'error_code' => 'INVALID_PROBLEM_STRUCTURE',
                        'is_truncated' => $isTruncated,
                        'error_details' => [
                            'type' => 'validation_error',
                            'code' => 'INVALID_PROBLEM_STRUCTURE',
                            'message' => '필수 필드 누락: ' . implode(', ', $missingFields),
                            'description' => 'API 응답에 필수 필드(question, solution)가 없거나 비어있습니다.',
                            'is_response_truncated' => $isTruncated,
                            'missing_fields' => $missingFields
                        ]
                    ]);
                } catch (Exception $jsonEx) {
                    error_log("[openai_config.php:" . __LINE__ . "] Ultimate fallback - error encoding failed");
                    return [
                        'success' => false,
                        'error' => 'Request failed'
                    ];  // ASCII-only guaranteed
                }
            }

            $result = [
                'success' => true,
                'problems' => [$problem],
                'usage' => $responseData['usage'] ?? [],
                'fallback_used' => true,
                'fallback_reason' => 'Using GPT-4o-mini'
            ];
            
            // 토큰 제한으로 잘린 경우 경고 추가
            if ($isTruncated) {
                $maxTokens = defined('PATTERNBANK_GPT5_MAX_TOKENS') ? PATTERNBANK_GPT5_MAX_TOKENS : 2000;
                $result['warning'] = "응답이 토큰 제한으로 인해 잘렸을 수 있습니다. (현재 Max Tokens: $maxTokens)";
                $result['is_truncated'] = true;
            }
            
            return $result;
        } else {
            // 백업 파일이 없으면 GPT-5 시도
            return generateSimilarProblemsGPT5($originalProblem, $type);
        }
    }
}
?>
