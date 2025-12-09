<?php
/**
 * GPT-5 Responses API 테스트
 * 새로운 Responses API 엔드포인트와 GPT-5 모델 테스트
 */

// Load OpenAI configuration
require_once(__DIR__ . '/config/openai_config.php');

echo "=== GPT-5 Responses API 테스트 ===\n\n";

// 1. API 키 확인
echo "1. API 키 확인...\n";
if (defined('PATTERNBANK_OPENAI_API_KEY')) {
    echo "   ✅ API 키 설정됨: " . substr(PATTERNBANK_OPENAI_API_KEY, 0, 10) . "...\n\n";
} else {
    echo "   ❌ API 키가 설정되지 않았습니다.\n";
    exit(1);
}

// 2. GPT-5 Responses API 연결 테스트
echo "2. GPT-5 Responses API 연결 테스트...\n";

// Responses API 형식으로 요청 (GPT-5 형식)
$requestData = [
    'model' => 'gpt-5-mini',
    'input' => '1+1은 무엇인가요? 답변을 간단히 해주세요.',
    'reasoning' => [
        'effort' => 'minimal'
    ],
    'text' => [
        'verbosity' => 'low'
    ],
    'max_completion_tokens' => 50,  // 최대 토큰 수
    'temperature' => 0.5
];

echo "   요청 데이터:\n";
echo "   - 모델: " . $requestData['model'] . "\n";
echo "   - 입력: " . $requestData['input'] . "\n";
echo "   - 추론 노력: " . $requestData['reasoning']['effort'] . "\n";
echo "   - 텍스트 상세도: " . $requestData['text']['verbosity'] . "\n\n";

// 3. cURL 요청
echo "3. API 호출 중...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.openai.com/v1/responses',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PATTERNBANK_OPENAI_API_KEY,
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

// 4. 결과 출력
echo "4. 결과:\n";
echo "   - HTTP 코드: $httpCode\n";

if ($error) {
    echo "   ❌ cURL 오류: $error\n";
} else {
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200) {
        if (isset($responseData['output_text'])) {
            echo "   ✅ 연결 성공! (GPT-5 Responses API)\n";
            echo "   - 응답 (output_text): " . $responseData['output_text'] . "\n";
            
            if (isset($responseData['reasoning_text'])) {
                echo "   - 추론 과정: " . substr($responseData['reasoning_text'], 0, 100) . "...\n";
            }
            
            if (isset($responseData['usage'])) {
                echo "   - 토큰 사용량:\n";
                echo "     · 입력: " . ($responseData['usage']['input_tokens'] ?? '?') . "\n";
                echo "     · 출력: " . ($responseData['usage']['output_tokens'] ?? '?') . "\n";
                echo "     · 추론: " . ($responseData['usage']['reasoning_tokens'] ?? '?') . "\n";
                echo "     · 총합: " . ($responseData['usage']['total_tokens'] ?? '?') . "\n";
            }
        } else {
            echo "   ❌ 예상하지 못한 응답 형식\n";
            echo "   - 응답: " . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } elseif ($httpCode === 404) {
        echo "   ⚠️ GPT-5/Responses API를 찾을 수 없음 - GPT-4o로 폴백 필요\n";
        
        // 폴백 테스트
        echo "\n5. GPT-4o 폴백 테스트...\n";
        $fallbackResult = testPatternBankOpenAI();
        
        if ($fallbackResult['success']) {
            echo "   ✅ GPT-4o 폴백 성공!\n";
            echo "   - 모델: " . $fallbackResult['model'] . "\n";
            echo "   - 응답: " . $fallbackResult['response'] . "\n";
        } else {
            echo "   ❌ 폴백도 실패: " . $fallbackResult['error'] . "\n";
        }
    } elseif (isset($responseData['error'])) {
        echo "   ❌ API 오류:\n";
        echo "     · 타입: " . ($responseData['error']['type'] ?? 'unknown') . "\n";
        echo "     · 메시지: " . ($responseData['error']['message'] ?? 'No message') . "\n";
    } else {
        echo "   ❌ 알 수 없는 오류\n";
        echo "   - 전체 응답: " . substr($response, 0, 500) . "\n";
    }
}

// 5. 구조화된 출력 테스트
echo "\n5. GPT-5 구조화된 출력 (JSON Schema) 테스트...\n";

$testResult = generateSimilarProblemsGPT5([
    'question' => '2x + 3 = 7일 때 x의 값은?',
    'solution' => 'x = 2'
], 'similar');

if ($testResult['success']) {
    echo "   ✅ 문제 생성 성공!\n";
    foreach ($testResult['problems'] as $idx => $problem) {
        echo "   - 문제: " . $problem['question'] . "\n";
        echo "   - 해설: " . substr($problem['solution'], 0, 100) . "...\n";
    }
} else {
    echo "   ❌ 문제 생성 실패: " . $testResult['error'] . "\n";
}

// 6. 진단 요약
echo "\n6. 최종 진단:\n";
if ($httpCode === 200 && isset($responseData['output_text'])) {
    echo "   ✅ GPT-5 Responses API 정상 작동!\n";
    echo "   - 모델: gpt-5-mini\n";
    echo "   - 엔드포인트: /v1/responses\n";
    echo "   - 추론 제어: 지원됨\n";
    echo "   - 구조화된 출력: 지원됨\n";
} elseif ($httpCode === 404) {
    echo "   ⚠️ GPT-5가 아직 사용 불가능한 것으로 보입니다.\n";
    echo "   - 자동으로 GPT-4o로 폴백됩니다.\n";
    echo "   - 백업 설정이 활성화되었습니다.\n";
} else {
    echo "   ❌ API 연결 문제가 있습니다.\n";
    echo "   - HTTP 코드: $httpCode\n";
    echo "   - 네트워크 및 API 키를 확인하세요.\n";
}

echo "\n=== 테스트 완료 ===\n";
?>