<?php
/**
 * OpenAI Chat Completions API 연결 테스트
 * GPT-4o-mini를 사용한 표준 Chat Completions API
 */

// Load OpenAI configuration
require_once(__DIR__ . '/config/openai_config.php');

echo "=== PatternBank OpenAI API 테스트 ===\n\n";

// 1. API 키 확인
echo "1. API 키 확인...\n";
if (defined('PATTERNBANK_OPENAI_API_KEY')) {
    echo "   ✅ API 키 설정됨: " . substr(PATTERNBANK_OPENAI_API_KEY, 0, 10) . "...\n\n";
} else {
    echo "   ❌ API 키가 설정되지 않았습니다.\n";
    exit(1);
}

// 2. OpenAI API 연결 테스트
echo "2. OpenAI API 연결 테스트...\n";

// 간단한 요청 생성 (Chat Completions API 형식)
$requestData = [
    'model' => 'gpt-4o-mini',  // 실제 사용 가능한 모델
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are a helpful assistant.'
        ],
        [
            'role' => 'user',
            'content' => '1+1은 무엇인가요? 답변을 간단히 해주세요.'
        ]
    ],
    'max_tokens' => 50,
    'temperature' => 0.5
];

echo "   요청 데이터:\n";
echo "   - 모델: " . $requestData['model'] . "\n";
echo "   - 메시지: " . $requestData['messages'][1]['content'] . "\n\n";

// 3. cURL 요청
echo "3. API 호출 중...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',  // 올바른 엔드포인트
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PATTERNBANK_OPENAI_API_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_VERBOSE => false
]);

// 응답 받기
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
    
    if ($httpCode === 200 && isset($responseData['choices'][0]['message']['content'])) {
        echo "   ✅ 연결 성공!\n";
        echo "   - 응답: " . $responseData['choices'][0]['message']['content'] . "\n";
        
        if (isset($responseData['usage'])) {
            echo "   - 토큰 사용량:\n";
            echo "     · 프롬프트: " . $responseData['usage']['prompt_tokens'] . "\n";
            echo "     · 완료: " . $responseData['usage']['completion_tokens'] . "\n";
            echo "     · 총합: " . $responseData['usage']['total_tokens'] . "\n";
        }
    } elseif (isset($responseData['error'])) {
        echo "   ❌ API 오류:\n";
        echo "     · 타입: " . ($responseData['error']['type'] ?? 'unknown') . "\n";
        echo "     · 메시지: " . ($responseData['error']['message'] ?? 'No message') . "\n";
        
        if (strpos($responseData['error']['message'], 'Incorrect API key') !== false) {
            echo "   💡 해결 방법: config/api_keys.php 파일의 API 키를 확인하세요.\n";
        } elseif (strpos($responseData['error']['message'], 'quota') !== false) {
            echo "   💡 해결 방법: OpenAI 계정의 사용량 한도를 확인하세요.\n";
        }
    } else {
        echo "   ❌ 연결 실패\n";
        echo "   - 응답: " . substr($response, 0, 200) . "\n";
    }
}

// 5. 유사문제 생성 테스트 함수 사용
echo "\n5. 유사문제 생성 테스트:\n";
echo "   OpenAI 설정 함수를 사용한 테스트...\n";

// testPatternBankOpenAI 함수 테스트
$testResult = testPatternBankOpenAI();

if ($testResult['success']) {
    echo "   ✅ testPatternBankOpenAI 함수 성공!\n";
    echo "   - 모델: " . $testResult['model'] . "\n";
    echo "   - 응답: " . $testResult['response'] . "\n";
} else {
    echo "   ❌ testPatternBankOpenAI 함수 실패!\n";
    echo "   - 오류: " . $testResult['error'] . "\n";
}

// 6. 문제 진단 요약
echo "\n6. 최종 진단:\n";
if ($httpCode === 401) {
    echo "   ❌ 인증 오류: API 키가 잘못되었거나 만료되었습니다.\n";
    echo "   💡 해결 방법:\n";
    echo "      1. config/api_keys.php 파일에서 API 키를 확인하세요\n";
    echo "      2. OpenAI 대시보드에서 새 API 키를 생성하세요\n";
    echo "      3. 계정에 크레딧이 있는지 확인하세요\n";
} elseif ($httpCode === 429) {
    echo "   ❌ Rate Limit: API 요청 한도를 초과했습니다.\n";
    echo "   💡 해결 방법: 잠시 후 다시 시도하세요\n";
} elseif ($httpCode === 404) {
    echo "   ❌ 엔드포인트 오류: 잘못된 API 엔드포인트입니다.\n";
    echo "   💡 이미 올바른 엔드포인트로 수정되었습니다.\n";
} elseif ($httpCode === 0) {
    echo "   ❌ 네트워크 오류: OpenAI 서버에 연결할 수 없습니다.\n";
    echo "   💡 해결 방법:\n";
    echo "      1. 인터넷 연결을 확인하세요\n";
    echo "      2. 방화벽 설정을 확인하세요\n";
    echo "      3. PHP cURL 확장이 활성화되어 있는지 확인하세요\n";
} elseif ($httpCode === 200) {
    echo "   ✅ OpenAI Chat Completions API 연결 성공!\n";
    echo "   - 모델: gpt-4o-mini 사용 중\n";
    echo "   - 엔드포인트: /v1/chat/completions\n";
    echo "   - PatternBank가 정상적으로 작동할 준비가 되었습니다.\n";
} else {
    echo "   ⚠️ 예상치 못한 HTTP 코드: $httpCode\n";
    echo "   - 전체 응답을 확인해보세요.\n";
}

echo "\n=== 테스트 완료 ===\n";
?>