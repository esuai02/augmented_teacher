<?php
/**
 * OpenAI GPT-5 Responses API Configuration for PatternBank
 * Uses GPT-5 with the new Responses API endpoint
 * Based on official OpenAI GPT-5 documentation
 */

// Load API key from secure config
require_once(__DIR__ . '/api_keys.php');

// Define the PatternBank constant for compatibility
if (!defined('PATTERNBANK_OPENAI_API_KEY')) {
    define('PATTERNBANK_OPENAI_API_KEY', OPENAI_API_KEY_SECURE);
}

// GPT-5 Configuration
define('PATTERNBANK_GPT5_MODEL', 'gpt-5-mini'); // Using gpt-5-mini for cost efficiency
define('PATTERNBANK_GPT5_TEMPERATURE', 0.0);
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
    
    // Parse the structured output
    $parsedOutput = isset($result['output_parsed']) ? $result['output_parsed'] : null;
    
    if (!$parsedOutput && isset($result['output_text'])) {
        $parsedOutput = json_decode($result['output_text'], true);
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
        'input' => $input,
        'max_tokens' => $options['max_tokens'] ?? PATTERNBANK_GPT5_MAX_TOKENS,
        'temperature' => $options['temperature'] ?? PATTERNBANK_GPT5_TEMPERATURE
    ];
    
    // Add reasoning configuration if specified
    if (isset($options['reasoning'])) {
        $data['reasoning'] = $options['reasoning'];
    }
    
    // Add text verbosity if specified
    if (isset($options['text'])) {
        $data['text'] = $options['text'];
    }
    
    // Add response format for structured outputs
    if (isset($options['response_format'])) {
        $data['response_format'] = $options['response_format'];
    }
    
    // Add tools if specified
    if (isset($options['tools'])) {
        $data['tools'] = $options['tools'];
    }
    
    // Add tool_choice if specified
    if (isset($options['tool_choice'])) {
        $data['tool_choice'] = $options['tool_choice'];
    }
    
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
        return ['error' => 'CURL error: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);
        $errorMsg = isset($responseData['error']) 
            ? $responseData['error']['message'] 
            : "HTTP $httpCode error";
        return ['error' => $errorMsg];
    }
    
    $responseData = json_decode($response, true);
    
    // GPT-5 Responses API returns output_text and optionally output_parsed
    if (!isset($responseData['output_text']) && !isset($responseData['output_parsed'])) {
        return ['error' => 'Invalid API response structure'];
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
        error_log("GPT-5 API failed: " . $result['error'] . ". Falling back to GPT-4o.");
        
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
        return generateSimilarProblemsSmart($originalProblem, $type);
    }
}
?>