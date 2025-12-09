<?php
/**
 * PatternBank 완전 테스트
 * GPT-5 시도 후 자동으로 GPT-4o로 폴백
 */

// 설정 파일 로드
require_once(__DIR__ . '/config/openai_config.php');

// 출력 형식 설정
$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PatternBank Test</title>';
    echo '<style>
        body { font-family: Arial; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .result-box { border-left: 4px solid #4CAF50; padding-left: 15px; margin: 10px 0; }
        .error-box { border-left: 4px solid #f44336; padding-left: 15px; margin: 10px 0; background: #ffebee; padding: 15px; }
    </style></head><body>';
    echo '<h1>🔬 PatternBank 완전 테스트</h1>';
} else {
    echo "=== PatternBank 완전 테스트 ===\n\n";
}

// 테스트 결과 저장
$testResults = [];

// ========================================
// 1. API 키 확인
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>1. API 키 확인</h2>' : "1. API 키 확인\n";

if (defined('PATTERNBANK_OPENAI_API_KEY') && PATTERNBANK_OPENAI_API_KEY !== 'your_api_key_here') {
    $apiKeyShort = substr(PATTERNBANK_OPENAI_API_KEY, 0, 10) . '...';
    echo $isWeb ? "<p class='success'>✅ API 키 설정됨: $apiKeyShort</p>" : "   ✅ API 키 설정됨: $apiKeyShort\n";
    $testResults['api_key'] = true;
} else {
    echo $isWeb ? "<p class='error'>❌ API 키가 설정되지 않았습니다.</p>" : "   ❌ API 키가 설정되지 않았습니다.\n";
    $testResults['api_key'] = false;
    if ($isWeb) echo '</div></body></html>';
    exit(1);
}
if ($isWeb) echo '</div>';

// ========================================
// 2. GPT-5 연결 테스트 (자동 폴백 포함)
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>2. API 연결 테스트</h2>' : "\n2. API 연결 테스트\n";

// GPT-5 먼저 시도
echo $isWeb ? '<p class="info">GPT-5 시도 중...</p>' : "   GPT-5 시도 중...\n";

$gpt5Request = [
    'model' => 'gpt-5-mini',
    'input' => '1+1은 얼마인가요? 숫자만 답하세요.',
    'reasoning' => ['effort' => 'minimal'],
    'text' => ['verbosity' => 'low'],
    'max_output_tokens' => 20  // Minimum is 16
    // temperature not supported in GPT-5
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($gpt5Request),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PATTERNBANK_OPENAI_API_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$apiWorking = false;
$modelUsed = '';

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['output_text'])) {
        echo $isWeb ? "<div class='result-box'><p class='success'>✅ GPT-5 연결 성공!</p>" : "   ✅ GPT-5 연결 성공!\n";
        echo $isWeb ? "<p>응답: {$data['output_text']}</p></div>" : "   응답: {$data['output_text']}\n";
        $apiWorking = true;
        $modelUsed = 'gpt-5-mini';
        $testResults['gpt5'] = true;
    }
} else {
    echo $isWeb ? "<p class='warning'>⚠️ GPT-5 연결 실패 (HTTP $httpCode)</p>" : "   ⚠️ GPT-5 연결 실패 (HTTP $httpCode)\n";
    $testResults['gpt5'] = false;
    
    // GPT-4o로 폴백
    echo $isWeb ? '<p class="info">GPT-4o로 폴백 시도...</p>' : "   GPT-4o로 폴백 시도...\n";
    
    $gpt4Request = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => '1+1은 얼마인가요? 숫자만 답하세요.']
        ],
        'max_tokens' => 10
        // temperature => 0
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($gpt4Request),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PATTERNBANK_OPENAI_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            echo $isWeb ? "<div class='result-box'><p class='success'>✅ GPT-4o 폴백 성공!</p>" : "   ✅ GPT-4o 폴백 성공!\n";
            echo $isWeb ? "<p>응답: {$data['choices'][0]['message']['content']}</p></div>" : "   응답: {$data['choices'][0]['message']['content']}\n";
            $apiWorking = true;
            $modelUsed = 'gpt-4o-mini';
            $testResults['gpt4o'] = true;
        }
    } else {
        echo $isWeb ? "<div class='error-box'><p class='error'>❌ GPT-4o도 실패 (HTTP $httpCode)</p></div>" : "   ❌ GPT-4o도 실패 (HTTP $httpCode)\n";
        $testResults['gpt4o'] = false;
    }
}

if ($isWeb) echo '</div>';

// ========================================
// 3. 유사문제 생성 테스트
// ========================================
if ($apiWorking) {
    if ($isWeb) echo '<div class="test-section">';
    echo $isWeb ? '<h2>3. 유사문제 생성 테스트</h2>' : "\n3. 유사문제 생성 테스트\n";
    echo $isWeb ? "<p class='info'>사용 모델: $modelUsed</p>" : "   사용 모델: $modelUsed\n";
    
    $originalProblem = [
        'question' => '다음 수열의 일반항을 구하시오: 2, 4, 8, 16, ...',
        'solution' => '첫째항이 2이고 공비가 2인 등비수열입니다. 일반항은 a_n = 2^n'
    ];
    
    echo $isWeb ? '<p>원본 문제: ' . $originalProblem['question'] . '</p>' : "   원본 문제: {$originalProblem['question']}\n";
    
    // 실제 함수 호출 테스트
    $result = generateSimilarProblems($originalProblem, 'similar');
    
    if ($result['success']) {
        echo $isWeb ? "<div class='result-box'><p class='success'>✅ 문제 생성 성공!</p>" : "   ✅ 문제 생성 성공!\n";
        
        foreach ($result['problems'] as $idx => $problem) {
            $num = $idx + 1;
            echo $isWeb ? "<h4>생성된 문제 $num:</h4>" : "\n   생성된 문제 $num:\n";
            echo $isWeb ? "<p><strong>문제:</strong> {$problem['question']}</p>" : "   문제: {$problem['question']}\n";
            echo $isWeb ? "<p><strong>해설:</strong> {$problem['solution']}</p>" : "   해설: {$problem['solution']}\n";
        }
        
        if (isset($result['fallback_used']) && $result['fallback_used']) {
            echo $isWeb ? "<p class='warning'>⚠️ GPT-4o 폴백 사용됨</p>" : "   ⚠️ GPT-4o 폴백 사용됨\n";
        }
        
        echo $isWeb ? '</div>' : '';
        $testResults['problem_generation'] = true;
    } else {
        echo $isWeb ? "<div class='error-box'><p class='error'>❌ 문제 생성 실패</p>" : "   ❌ 문제 생성 실패\n";
        echo $isWeb ? "<p>오류: {$result['error']}</p></div>" : "   오류: {$result['error']}\n";
        $testResults['problem_generation'] = false;
    }
    
    if ($isWeb) echo '</div>';
}

// ========================================
// 4. 변형문제 생성 테스트
// ========================================
if ($apiWorking) {
    if ($isWeb) echo '<div class="test-section">';
    echo $isWeb ? '<h2>4. 변형문제 생성 테스트</h2>' : "\n4. 변형문제 생성 테스트\n";
    
    $result = generateSimilarProblems($originalProblem, 'modified');
    
    if ($result['success']) {
        echo $isWeb ? "<div class='result-box'><p class='success'>✅ 변형문제 생성 성공!</p>" : "   ✅ 변형문제 생성 성공!\n";
        
        foreach ($result['problems'] as $idx => $problem) {
            $num = $idx + 1;
            echo $isWeb ? "<h4>변형문제 $num:</h4>" : "\n   변형문제 $num:\n";
            echo $isWeb ? "<p><strong>문제:</strong> {$problem['question']}</p>" : "   문제: {$problem['question']}\n";
            echo $isWeb ? "<p><strong>해설:</strong> {$problem['solution']}</p>" : "   해설: {$problem['solution']}\n";
        }
        echo $isWeb ? '</div>' : '';
        $testResults['modified_generation'] = true;
    } else {
        echo $isWeb ? "<div class='error-box'><p class='error'>❌ 변형문제 생성 실패</p>" : "   ❌ 변형문제 생성 실패\n";
        $testResults['modified_generation'] = false;
    }
    
    if ($isWeb) echo '</div>';
}

// ========================================
// 5. 테스트 요약
// ========================================
if ($isWeb) echo '<div class="test-section">';
echo $isWeb ? '<h2>📊 테스트 요약</h2>' : "\n=== 테스트 요약 ===\n";

$totalTests = count($testResults);
$passedTests = array_sum($testResults);
$successRate = round(($passedTests / $totalTests) * 100, 1);

if ($isWeb) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th style='padding: 10px; text-align: left;'>테스트 항목</th><th style='padding: 10px;'>결과</th></tr>";
    
    $testNames = [
        'api_key' => 'API 키 설정',
        'gpt5' => 'GPT-5 연결',
        'gpt4o' => 'GPT-4o 폴백',
        'problem_generation' => '유사문제 생성',
        'modified_generation' => '변형문제 생성'
    ];
    
    foreach ($testResults as $key => $result) {
        $name = $testNames[$key] ?? $key;
        $status = $result ? '✅ 통과' : '❌ 실패';
        $color = $result ? 'green' : 'red';
        echo "<tr><td style='padding: 10px; border-top: 1px solid #ddd;'>$name</td>";
        echo "<td style='padding: 10px; border-top: 1px solid #ddd; text-align: center; color: $color; font-weight: bold;'>$status</td></tr>";
    }
    
    echo "</table>";
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px;'>";
    echo "<p style='font-size: 18px;'><strong>전체 성공률: $successRate%</strong> ($passedTests/$totalTests 테스트 통과)</p>";
    
    if ($modelUsed) {
        echo "<p><strong>사용 중인 모델:</strong> $modelUsed</p>";
    }
    
    echo "</div>";
} else {
    foreach ($testResults as $key => $result) {
        $status = $result ? '✅' : '❌';
        echo "   $key: $status\n";
    }
    echo "\n   전체 성공률: $successRate% ($passedTests/$totalTests 테스트 통과)\n";
    if ($modelUsed) {
        echo "   사용 모델: $modelUsed\n";
    }
}

// ========================================
// 6. 권장사항
// ========================================
if ($isWeb) {
    echo '<div style="margin-top: 20px; padding: 15px; background: #fff3e0; border-radius: 8px;">';
    echo '<h3>💡 권장사항</h3>';
    
    if (!$testResults['gpt5'] && $testResults['gpt4o']) {
        echo '<p>• GPT-5가 아직 사용 불가능합니다. 시스템은 자동으로 GPT-4o를 사용합니다.</p>';
    }
    
    if ($successRate === 100) {
        echo '<p>• 모든 테스트를 통과했습니다! PatternBank를 사용할 준비가 되었습니다.</p>';
    } elseif ($successRate >= 80) {
        echo '<p>• 대부분의 기능이 정상 작동합니다. 실패한 테스트를 확인하세요.</p>';
    } else {
        echo '<p>• API 키와 네트워크 연결을 확인하세요.</p>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</body></html>';
} else {
    echo "\n=== 테스트 완료 ===\n";
}
?>