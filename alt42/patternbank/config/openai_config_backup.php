<?php
/**
 * OpenAI API Configuration and Functions for PatternBank
 * Uses GPT-4o Chat Completions API (standard endpoint)
 */

// Load API key from secure config
require_once(__DIR__ . '/api_keys.php');

// Define the PatternBank constant for compatibility
if (!defined('PATTERNBANK_OPENAI_API_KEY')) {
    define('PATTERNBANK_OPENAI_API_KEY', OPENAI_API_KEY_SECURE);
}

// OpenAI Configuration
define('PATTERNBANK_OPENAI_MODEL', 'gpt-4o-mini'); // Using GPT-4o-mini for cost efficiency
define('PATTERNBANK_OPENAI_TEMPERATURE', 0.0);
define('PATTERNBANK_OPENAI_MAX_TOKENS', 4000);

/**
 * Test OpenAI API connection
 * @return array Test result with success status and details
 */
function testPatternBankOpenAI() {
    $testMessage = "간단히 1+1을 계산해주세요.";
    
    $result = callOpenAIChat($testMessage);
    
    if (isset($result['error'])) {
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }
    
    return [
        'success' => true,
        'model' => PATTERNBANK_OPENAI_MODEL,
        'response' => $result['content'] ?? 'No response',
        'usage' => $result['usage'] ?? []
    ];
}

/**
 * Generate similar problems using OpenAI
 * @param array $originalProblem Original problem data
 * @param string $type Problem type (similar or modified)
 * @return array Generated problems or error
 */
function generateSimilarProblems($originalProblem, $type = 'similar') {
    $numProblems = 1;  // Changed from 3/2 to always generate 1 problem
    
    // Construct the prompt
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
    
    $prompt .= "\n다음 형식으로 작성해주세요:\n";
    $prompt .= "문제: [문제 내용]\n";
    $prompt .= "해설: [상세한 풀이 과정과 답]\n\n";
    
    // Call OpenAI API
    $result = callOpenAIChat($prompt, PATTERNBANK_OPENAI_MAX_TOKENS);
    
    if (isset($result['error'])) {
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }
    
    // Parse the response
    $content = $result['content'] ?? '';
    $problems = parseProblems($content);
    
    if (empty($problems)) {
        return [
            'success' => false,
            'error' => 'Failed to parse generated problems'
        ];
    }
    
    return [
        'success' => true,
        'problems' => $problems,
        'usage' => $result['usage'] ?? []
    ];
}

/**
 * Call OpenAI Chat Completions API
 * @param string $message User message
 * @param int $maxTokens Maximum tokens for response
 * @return array API response or error
 */
function callOpenAIChat($message, $maxTokens = 500) {
    $apiKey = PATTERNBANK_OPENAI_API_KEY;
    $model = PATTERNBANK_OPENAI_MODEL;
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful math tutor that creates practice problems.'
            ],
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'temperature' => PATTERNBANK_OPENAI_TEMPERATURE,
        'max_tokens' => $maxTokens
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
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
    
    if (!isset($responseData['choices'][0]['message']['content'])) {
        return ['error' => 'Invalid API response structure'];
    }
    
    return [
        'content' => $responseData['choices'][0]['message']['content'],
        'usage' => $responseData['usage'] ?? []
    ];
}

/**
 * Parse problems from OpenAI response
 * @param string $content Response content
 * @return array Parsed problems
 */
function parseProblems($content) {
    $problems = [];
    
    // For single problem, directly parse the content
    // Check if content starts with "문제 1:" or just "문제:"
    if (strpos($content, '문제:') !== false || strpos($content, '문제 1:') !== false) {
        $problem = parseSingleProblem($content);
        if ($problem) {
            $problems[] = $problem;
        }
    } else {
        // Try to parse numbered problems if any
        preg_match_all('/문제\s*(\d+):(.*?)(?=문제\s*\d+:|$)/s', $content, $matches);
        
        if (!empty($matches[2])) {
            foreach ($matches[2] as $problemText) {
                $problem = parseSingleProblem($problemText);
                if ($problem) {
                    $problems[] = $problem;
                    break; // Only take the first problem
                }
            }
        }
    }
    
    return $problems;
}

/**
 * Parse a single problem from text
 * @param string $text Problem text
 * @return array|null Parsed problem or null
 */
function parseSingleProblem($text) {
    $problem = [
        'question' => '',
        'solution' => '',
        'choices' => []
    ];
    
    // Extract question
    if (preg_match('/문제:\s*(.+?)(?=해설:|선택지:|$)/s', $text, $match)) {
        $problem['question'] = trim($match[1]);
    }
    
    // Extract solution
    if (preg_match('/해설:\s*(.+?)(?=선택지:|$)/s', $text, $match)) {
        $problem['solution'] = trim($match[1]);
    }
    
    // Extract choices if present
    if (preg_match('/선택지:\s*(.+)/s', $text, $match)) {
        $choicesText = $match[1];
        preg_match_all('/[①②③④⑤\d)]\s*(.+?)(?=[①②③④⑤\d)]|$)/s', $choicesText, $choiceMatches);
        if (!empty($choiceMatches[1])) {
            $problem['choices'] = array_map('trim', $choiceMatches[1]);
        }
    }
    
    // Return only if we have at least a question
    return !empty($problem['question']) ? $problem : null;
}
?>