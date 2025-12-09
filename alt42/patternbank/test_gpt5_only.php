<?php
/**
 * PatternBank GPT-5 전용 테스트
 * GPT-5 Responses API만 사용 (폴백 없음)
 */

// 설정 파일 로드
require_once(__DIR__ . '/config/api_keys.php');

// 출력 형식 설정
$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>GPT-5 PatternBank Test</title>';
    echo '<style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial; max-width: 1200px; margin: 20px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 15px; }
        .test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 12px; border: 1px solid #e9ecef; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 8px; overflow-x: auto; }
        .json { color: #98c379; }
        h2 { color: #495057; margin-top: 30px; }
        .result-box { border-left: 4px solid #28a745; padding-left: 15px; margin: 15px 0; background: #d4edda; padding: 15px; border-radius: 4px; }
        .error-box { border-left: 4px solid #dc3545; padding-left: 15px; margin: 15px 0; background: #f8d7da; padding: 15px; border-radius: 4px; }
        .code-block { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: "Fira Code", monospace; }
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #5a67d8; }
    </style></head><body><div class="container">';
    echo '<h1>🚀 PatternBank GPT-5 전용 테스트</h1>';
    echo '<p class="info">GPT-5 Responses API를 사용한 문제 생성 시스템 테스트</p>';
} else {
    echo "=== PatternBank GPT-5 전용 테스트 ===\n\n";
}

// API 키 설정
$apiKey = OPENAI_API_KEY_SECURE;

// ========================================
// 1. API 키 확인
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>1. API 키 확인</h2>' : "1. API 키 확인\n";

if ($apiKey && $apiKey !== 'your_api_key_here') {
    $apiKeyShort = substr($apiKey, 0, 20) . '...';
    echo $isWeb ? "<p class='success'>✅ API 키 설정됨: <code>$apiKeyShort</code></p>" : "   ✅ API 키 설정됨: $apiKeyShort\n";
} else {
    echo $isWeb ? "<p class='error'>❌ API 키가 설정되지 않았습니다.</p>" : "   ❌ API 키가 설정되지 않았습니다.\n";
    if ($isWeb) echo '</div></div></body></html>';
    exit(1);
}
if ($isWeb) echo '</div>';

// ========================================
// 2. GPT-5 Responses API 연결 테스트
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>2. GPT-5 Responses API 연결 테스트</h2>' : "\n2. GPT-5 Responses API 연결 테스트\n";

$testRequest = [
    'model' => 'gpt-5-mini',
    'input' => '1+1은 얼마인가요? 숫자만 답하세요.',
    'reasoning' => ['effort' => 'minimal'],
    'text' => ['verbosity' => 'low'],
    'max_output_tokens' => 20  // Minimum is 16
    // temperature not supported in GPT-5
];

if ($isWeb) {
    echo '<p>요청 데이터:</p>';
    echo '<pre class="code-block">' . json_encode($testRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
}

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testRequest),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo $isWeb ? "<div class='error-box'>❌ CURL 오류: $error</div>" : "   ❌ CURL 오류: $error\n";
} else {
    echo $isWeb ? "<p>HTTP 응답 코드: <strong>$httpCode</strong></p>" : "   HTTP 응답 코드: $httpCode\n";
    
    $responseData = json_decode($response, true);
    
    // GPT-5 returns output as an array with reasoning and message
    $outputText = null;
    if ($httpCode === 200 && isset($responseData['output']) && is_array($responseData['output'])) {
        foreach ($responseData['output'] as $output) {
            if ($output['type'] === 'message' && isset($output['content'])) {
                // Handle both string and array content
                if (is_array($output['content'])) {
                    $outputText = isset($output['content'][0]['text']) ? $output['content'][0]['text'] : json_encode($output['content']);
                } else {
                    $outputText = $output['content'];
                }
                break;
            }
        }
    }
    
    if ($httpCode === 200 && $outputText !== null) {
        echo $isWeb ? "<div class='result-box'>" : "";
        echo $isWeb ? "<p class='success'>✅ GPT-5 연결 성공!</p>" : "   ✅ GPT-5 연결 성공!\n";
        echo $isWeb ? "<p><strong>응답:</strong> $outputText</p>" : "   응답: $outputText\n";
        
        if (isset($responseData['usage'])) {
            echo $isWeb ? "<p><strong>토큰 사용량:</strong>" : "   토큰 사용량:\n";
            echo $isWeb ? " 입력: {$responseData['usage']['input_tokens']}" : "     입력: {$responseData['usage']['input_tokens']}\n";
            echo $isWeb ? " | 출력: {$responseData['usage']['output_tokens']}" : "     출력: {$responseData['usage']['output_tokens']}\n";
            echo $isWeb ? " | 추론: " . ($responseData['usage']['reasoning_tokens'] ?? 0) : "     추론: " . ($responseData['usage']['reasoning_tokens'] ?? 0) . "\n";
            echo $isWeb ? "</p>" : "";
        }
        echo $isWeb ? "</div>" : "";
    } else {
        echo $isWeb ? "<div class='error-box'>" : "";
        echo $isWeb ? "<p class='error'>❌ GPT-5 연결 실패</p>" : "   ❌ GPT-5 연결 실패\n";
        
        if (isset($responseData['error'])) {
            echo $isWeb ? "<p><strong>오류 타입:</strong> {$responseData['error']['type']}</p>" : "   오류 타입: {$responseData['error']['type']}\n";
            echo $isWeb ? "<p><strong>오류 메시지:</strong> {$responseData['error']['message']}</p>" : "   오류 메시지: {$responseData['error']['message']}\n";
        } else {
            echo $isWeb ? "<p>전체 응답:</p><pre>" . substr($response, 0, 500) . "</pre>" : "   응답: " . substr($response, 0, 200) . "\n";
        }
        echo $isWeb ? "</div>" : "";
    }
}
if ($isWeb) echo '</div>';

// ========================================
// 3. 구조화된 출력 (JSON Schema) 테스트
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>3. 구조화된 출력 테스트</h2>' : "\n3. 구조화된 출력 테스트\n";

$schema = [
    'type' => 'object',
    'properties' => [
        'question' => ['type' => 'string'],
        'solution' => ['type' => 'string']
    ],
    'required' => ['question', 'solution'],
    'additionalProperties' => false
];

$structuredRequest = [
    'model' => 'gpt-5-mini',
    'input' => '간단한 수학 문제를 1개 만들어주세요. JSON 형식으로 question(문제)과 solution(해설)을 포함해주세요.',
    'reasoning' => ['effort' => 'low'],
    'text' => [
        'verbosity' => 'medium',
        'format' => [
            'type' => 'json_schema',
            'name' => 'MathProblem',
            'schema' => $schema,
            'strict' => true
        ]
    ],
    'max_output_tokens' => 500
    // temperature not supported in GPT-5
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($structuredRequest),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);

// Parse GPT-5 output structure
$problem = null;
if ($httpCode === 200 && isset($responseData['output']) && is_array($responseData['output'])) {
    foreach ($responseData['output'] as $output) {
        if ($output['type'] === 'message' && isset($output['content'])) {
            // Handle both string and array content
            if (is_array($output['content'])) {
                $content = isset($output['content'][0]['text']) ? $output['content'][0]['text'] : json_encode($output['content']);
            } else {
                $content = $output['content'];
            }
            // Try to parse as JSON
            $problem = json_decode($content, true);
            break;
        }
    }
}

if ($httpCode === 200 && $problem !== null) {
    echo $isWeb ? "<div class='result-box'>" : "";
    echo $isWeb ? "<p class='success'>✅ 구조화된 출력 성공!</p>" : "   ✅ 구조화된 출력 성공!\n";
    echo $isWeb ? "<p><strong>생성된 문제:</strong></p>" : "   생성된 문제:\n";
    echo $isWeb ? "<pre class='json'>" . json_encode($problem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>" : "   " . json_encode($problem, JSON_UNESCAPED_UNICODE) . "\n";
    echo $isWeb ? "</div>" : "";
} else {
    echo $isWeb ? "<div class='error-box'>" : "";
    echo $isWeb ? "<p class='error'>❌ 구조화된 출력 실패</p>" : "   ❌ 구조화된 출력 실패\n";
    
    if (isset($responseData['error'])) {
        echo $isWeb ? "<p>오류: {$responseData['error']['message']}</p>" : "   오류: {$responseData['error']['message']}\n";
    }
    echo $isWeb ? "</div>" : "";
}
if ($isWeb) echo '</div>';

// ========================================
// 4. 추론 제어 테스트
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>4. 추론 제어 수준별 테스트</h2>' : "\n4. 추론 제어 수준별 테스트\n";

$reasoningLevels = ['minimal', 'low', 'medium'];
$testProblem = 'x^2 - 5x + 6 = 0의 해를 구하세요.';

foreach ($reasoningLevels as $level) {
    echo $isWeb ? "<h3>추론 수준: <code>$level</code></h3>" : "   추론 수준: $level\n";
    
    $reasoningRequest = [
        'model' => 'gpt-5-mini',
        'input' => $testProblem,
        'reasoning' => ['effort' => $level],
        'text' => ['verbosity' => 'low'],
        'max_output_tokens' => 200
        // temperature not supported in GPT-5
    ];
    
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($reasoningRequest),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $outputText = null;
        
        // Parse GPT-5 output structure
        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $output) {
                if ($output['type'] === 'message' && isset($output['content'])) {
                    // Handle both string and array content
                    if (is_array($output['content'])) {
                        $outputText = isset($output['content'][0]['text']) ? $output['content'][0]['text'] : json_encode($output['content']);
                    } else {
                        $outputText = $output['content'];
                    }
                    break;
                }
            }
        }
        
        if ($outputText !== null) {
            echo $isWeb ? "<div class='result-box' style='margin-left: 20px;'>" : "";
            echo $isWeb ? "<p>✅ 응답: $outputText</p>" : "     응답: $outputText\n";
            
            if (isset($data['usage']['reasoning_tokens'])) {
                echo $isWeb ? "<p>추론 토큰: {$data['usage']['reasoning_tokens']}</p>" : "     추론 토큰: {$data['usage']['reasoning_tokens']}\n";
            }
            echo $isWeb ? "</div>" : "";
        }
    } else {
        echo $isWeb ? "<p class='error' style='margin-left: 20px;'>❌ 실패</p>" : "     ❌ 실패\n";
    }
}
if ($isWeb) echo '</div>';

// ========================================
// 5. PatternBank 문제 생성 테스트
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>5. PatternBank 유사문제 생성</h2>' : "\n5. PatternBank 유사문제 생성\n";

$originalProblem = "다음 수열의 일반항을 구하시오: 3, 6, 12, 24, ...";
$originalSolution = "첫째항이 3이고 공비가 2인 등비수열입니다. 따라서 일반항은 a_n = 3 × 2^(n-1)입니다.";

$prompt = "다음 수학 문제를 바탕으로 유사한 문제를 1개 생성해주세요.\n\n";
$prompt .= "원본 문제:\n";
$prompt .= "문제: $originalProblem\n";
$prompt .= "해설: $originalSolution\n\n";
$prompt .= "JSON 형식으로 작성해주세요: {\"question\": \"문제\", \"solution\": \"해설\"}";

$patternRequest = [
    'model' => 'gpt-5-mini',
    'input' => $prompt,
    'reasoning' => ['effort' => 'low'],
    'text' => [
        'verbosity' => 'medium',
        'format' => [
            'type' => 'json_schema',
            'name' => 'MathProblem',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'question' => ['type' => 'string'],
                    'solution' => ['type' => 'string']
                ],
                'required' => ['question', 'solution'],
                'additionalProperties' => false
            ],
            'strict' => true
        ]
    ],
    'max_output_tokens' => 1000
    // temperature not supported in GPT-5
];

if ($isWeb) {
    echo '<p><strong>원본 문제:</strong></p>';
    echo "<div class='code-block'>문제: $originalProblem<br>해설: $originalSolution</div>";
}

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($patternRequest),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);

if ($httpCode === 200) {
    $generatedProblem = null;
    
    // Parse GPT-5 output structure
    if (isset($responseData['output']) && is_array($responseData['output'])) {
        foreach ($responseData['output'] as $output) {
            if ($output['type'] === 'message' && isset($output['content'])) {
                // Handle both string and array content
                if (is_array($output['content'])) {
                    $content = isset($output['content'][0]['text']) ? $output['content'][0]['text'] : json_encode($output['content']);
                } else {
                    $content = $output['content'];
                }
                // Try to parse as JSON
                $generatedProblem = json_decode($content, true);
                break;
            }
        }
    }
    
    if ($generatedProblem) {
        echo $isWeb ? "<div class='result-box'>" : "";
        echo $isWeb ? "<p class='success'>✅ 유사문제 생성 성공!</p>" : "   ✅ 유사문제 생성 성공!\n";
        echo $isWeb ? "<p><strong>생성된 문제:</strong></p>" : "   생성된 문제:\n";
        echo $isWeb ? "<div class='code-block'>" : "";
        echo $isWeb ? "문제: {$generatedProblem['question']}<br>" : "   문제: {$generatedProblem['question']}\n";
        echo $isWeb ? "해설: {$generatedProblem['solution']}" : "   해설: {$generatedProblem['solution']}\n";
        echo $isWeb ? "</div>" : "";
        echo $isWeb ? "</div>" : "";
    } else {
        echo $isWeb ? "<p class='warning'>⚠️ 문제는 생성되었으나 파싱 실패</p>" : "   ⚠️ 문제는 생성되었으나 파싱 실패\n";
    }
} else {
    echo $isWeb ? "<div class='error-box'>" : "";
    echo $isWeb ? "<p class='error'>❌ 문제 생성 실패 (HTTP $httpCode)</p>" : "   ❌ 문제 생성 실패 (HTTP $httpCode)\n";
    
    if (isset($responseData['error'])) {
        echo $isWeb ? "<p>오류: {$responseData['error']['message']}</p>" : "   오류: {$responseData['error']['message']}\n";
    }
    echo $isWeb ? "</div>" : "";
}

if ($isWeb) echo '</div>';

// ========================================
// 6. 최종 요약
// ========================================
if ($isWeb) {
    echo '<div class="test-section" style="background: #e8f5e9;">';
    echo '<h2>📊 테스트 요약</h2>';
    echo '<p>GPT-5 Responses API를 사용한 PatternBank 시스템 테스트가 완료되었습니다.</p>';
    echo '<p><strong>테스트된 기능:</strong></p>';
    echo '<ul>';
    echo '<li>기본 API 연결</li>';
    echo '<li>구조화된 출력 (JSON Schema)</li>';
    echo '<li>추론 제어 (minimal, low, medium)</li>';
    echo '<li>유사문제 생성</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div></body></html>';
} else {
    echo "\n=== 테스트 완료 ===\n";
}
?>