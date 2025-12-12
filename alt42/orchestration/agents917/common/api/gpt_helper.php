<?php
/**
 * GPT API Helper Functions
 *
 * Handles OpenAI API communication for agent analysis generation
 *
 * @version 1.0
 * @date 2025-01-21
 * File: api/gpt_helper.php
 */

require_once(__DIR__ . '/gpt_config.php');

/**
 * Call OpenAI GPT API with prompt
 *
 * @param string $prompt The prompt to send to GPT
 * @param array $options Optional settings (temperature, max_tokens, etc.)
 * @return array ['success' => bool, 'response' => string, 'error' => string]
 */
function callGPTAPI($prompt, $options = []) {
    // Check if GPT is enabled
    if (!isGPTEnabled()) {
        return [
            'success' => false,
            'response' => null,
            'error' => 'GPT API not configured. Please set OPENAI_API_KEY in gpt_config.php',
            'using_placeholder' => true
        ];
    }

    // Validate configuration
    $validation = validateGPTConfig();
    if (!$validation['valid']) {
        return [
            'success' => false,
            'response' => null,
            'error' => 'GPT configuration invalid: ' . implode(', ', $validation['errors']),
            'using_placeholder' => true
        ];
    }

    // Merge options with defaults
    $temperature = $options['temperature'] ?? OPENAI_TEMPERATURE;
    $max_tokens = $options['max_tokens'] ?? OPENAI_MAX_TOKENS;
    $model = $options['model'] ?? OPENAI_MODEL;

    // Prepare request payload
    $payload = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => '당신은 교육 전문 AI 분석가입니다. 학생의 학습 문제를 분석하고 구체적이고 실행 가능한 개선 방안을 제시합니다. 분석은 한국어로 작성하며, 교육학적 근거와 실천 가능성을 중시합니다.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    ];

    $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    // Log request (without API key)
    error_log("[gpt_helper.php] Calling GPT API | Model: {$model} | Tokens: {$max_tokens}");

    // Initialize cURL
    $ch = curl_init(OPENAI_API_ENDPOINT);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_TIMEOUT => OPENAI_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Handle cURL errors
    if ($response === false) {
        error_log("[gpt_helper.php] cURL error: {$curl_error} - File: gpt_helper.php, Line: " . __LINE__);
        return [
            'success' => false,
            'response' => null,
            'error' => "API 연결 실패: {$curl_error}",
            'http_code' => 0
        ];
    }

    // Parse response
    $data = json_decode($response, true);

    // Handle HTTP errors
    if ($http_code !== 200) {
        $error_message = isset($data['error']['message'])
            ? $data['error']['message']
            : "HTTP {$http_code} 오류";

        error_log("[gpt_helper.php] API error: {$error_message} (HTTP {$http_code}) - File: gpt_helper.php, Line: " . __LINE__);

        return [
            'success' => false,
            'response' => null,
            'error' => $error_message,
            'http_code' => $http_code
        ];
    }

    // Extract response text
    if (!isset($data['choices'][0]['message']['content'])) {
        error_log("[gpt_helper.php] Invalid API response structure - File: gpt_helper.php, Line: " . __LINE__);
        return [
            'success' => false,
            'response' => null,
            'error' => 'API 응답 형식이 올바르지 않습니다.',
            'http_code' => $http_code
        ];
    }

    $response_text = trim($data['choices'][0]['message']['content']);

    // Log success
    $response_length = strlen($response_text);
    error_log("[gpt_helper.php] API call successful | Response length: {$response_length} chars");

    return [
        'success' => true,
        'response' => $response_text,
        'error' => null,
        'http_code' => $http_code,
        'usage' => $data['usage'] ?? null
    ];
}

/**
 * Generate agent analysis using GPT-4
 *
 * @param int $agent_number Agent number (1-21)
 * @param string $agent_name Agent name
 * @param string $agent_description Agent description
 * @param string $problem_text Problem description
 * @param string $student_name Student name
 * @param array $student_data Optional student context data
 * @return array Analysis with 4 sections or error
 */
function generateGPTAnalysis($agent_number, $agent_name, $agent_description, $problem_text, $student_name, $student_data = []) {
    // Build structured prompt using WXSPERTA framework
    $prompt = buildAnalysisPrompt(
        $agent_number,
        $agent_name,
        $agent_description,
        $problem_text,
        $student_name,
        $student_data
    );

    // Call GPT API
    $result = callGPTAPI($prompt);

    if (!$result['success']) {
        return [
            'success' => false,
            'error' => $result['error'],
            'using_placeholder' => $result['using_placeholder'] ?? false
        ];
    }

    // Parse GPT response into structured format
    $analysis = parseGPTResponse($result['response']);

    // Validate analysis structure
    if (!validateAnalysisStructure($analysis)) {
        error_log("[gpt_helper.php] Invalid analysis structure from GPT - File: gpt_helper.php, Line: " . __LINE__);
        return [
            'success' => false,
            'error' => 'GPT 응답 파싱 실패. 올바른 형식이 아닙니다.',
            'raw_response' => $result['response']
        ];
    }

    return [
        'success' => true,
        'analysis' => $analysis,
        'usage' => $result['usage']
    ];
}

/**
 * Build structured analysis prompt
 *
 * @param int $agent_number
 * @param string $agent_name
 * @param string $agent_description
 * @param string $problem_text
 * @param string $student_name
 * @param array $student_data
 * @return string
 */
function buildAnalysisPrompt($agent_number, $agent_name, $agent_description, $problem_text, $student_name, $student_data) {
    $prompt = "# 학습 문제 분석 요청\n\n";
    $prompt .= "## 에이전트 정보\n";
    $prompt .= "- **에이전트 번호**: Agent {$agent_number}\n";
    $prompt .= "- **에이전트 이름**: {$agent_name}\n";
    $prompt .= "- **담당 영역**: {$agent_description}\n\n";

    $prompt .= "## 학생 정보\n";
    $prompt .= "- **학생 이름**: {$student_name}\n";

    if (!empty($student_data)) {
        foreach ($student_data as $key => $value) {
            $prompt .= "- **{$key}**: {$value}\n";
        }
    }

    $prompt .= "\n## 분석 대상 문제\n";
    $prompt .= "{$problem_text}\n\n";

    $prompt .= "## 요청 사항\n";
    $prompt .= "위 문제에 대해 다음 4가지 섹션으로 구조화된 분석을 제공해주세요:\n\n";
    $prompt .= "1. **[문제 상황]**: 현재 학생이 겪고 있는 구체적인 문제 상황을 2-3문장으로 기술\n";
    $prompt .= "2. **[원인 분석]**: 문제의 근본 원인을 3가지 이상 분석 (교육학적 근거 포함)\n";
    $prompt .= "3. **[개선 방안]**: 실행 가능한 구체적인 개선 방안을 단계별로 제시 (최소 3단계)\n";
    $prompt .= "4. **[예상 효과]**: 개선 방안 적용 시 기대되는 구체적인 효과 (정량적 지표 포함)\n\n";

    $prompt .= "## 작성 지침\n";
    $prompt .= "- 한국어로 작성\n";
    $prompt .= "- 각 섹션은 150-250자 분량\n";
    $prompt .= "- 교육학적 근거와 실천 가능성 중시\n";
    $prompt .= "- 구체적인 수치와 기간 명시\n";
    $prompt .= "- {$student_name} 학생에게 맞춤화된 내용\n\n";

    $prompt .= "응답 형식:\n";
    $prompt .= "[문제 상황]\n(내용)\n\n";
    $prompt .= "[원인 분석]\n(내용)\n\n";
    $prompt .= "[개선 방안]\n(내용)\n\n";
    $prompt .= "[예상 효과]\n(내용)";

    return $prompt;
}

/**
 * Parse GPT response into structured format
 *
 * @param string $response GPT API response text
 * @return array
 */
function parseGPTResponse($response) {
    $sections = [
        'problem_situation' => '',
        'cause_analysis' => '',
        'improvement_plan' => '',
        'expected_outcome' => ''
    ];

    // Extract sections using regex
    $patterns = [
        'problem_situation' => '/\[문제\s*상황\]\s*\n(.*?)(?=\n\[|$)/s',
        'cause_analysis' => '/\[원인\s*분석\]\s*\n(.*?)(?=\n\[|$)/s',
        'improvement_plan' => '/\[개선\s*방안\]\s*\n(.*?)(?=\n\[|$)/s',
        'expected_outcome' => '/\[예상\s*효과\]\s*\n(.*?)(?=\n\[|$)/s'
    ];

    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $response, $matches)) {
            $sections[$key] = trim($matches[1]);
        }
    }

    // Fallback: If structured format not found, split by double newlines
    if (empty($sections['problem_situation'])) {
        $parts = preg_split('/\n\n+/', trim($response));
        if (count($parts) >= 4) {
            $sections['problem_situation'] = $parts[0];
            $sections['cause_analysis'] = $parts[1];
            $sections['improvement_plan'] = $parts[2];
            $sections['expected_outcome'] = $parts[3];
        } else {
            // Last resort: Use entire response as problem_situation
            $sections['problem_situation'] = $response;
        }
    }

    return $sections;
}

/**
 * Validate analysis structure
 *
 * @param array $analysis
 * @return bool
 */
function validateAnalysisStructure($analysis) {
    $required_keys = ['problem_situation', 'cause_analysis', 'improvement_plan', 'expected_outcome'];

    foreach ($required_keys as $key) {
        if (!isset($analysis[$key]) || empty(trim($analysis[$key]))) {
            error_log("[gpt_helper.php] Missing or empty section: {$key} - File: gpt_helper.php, Line: " . __LINE__);
            return false;
        }
    }

    return true;
}
