<?php
/**
 * GPT-5 Responses API 최소 테스트
 * 가장 간단한 형태로 API 호출 테스트
 */

// Load API key
require_once(__DIR__ . '/config/api_keys.php');

echo "=== GPT-5 최소 테스트 ===\n\n";

// 1. 가장 간단한 요청 (필수 파라미터만)
echo "1. 최소 파라미터 테스트...\n";
$requestData = [
    'model' => 'gpt-5-mini',
    'input' => 'Hello'
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY_SECURE,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP 코드: $httpCode\n";
$data = json_decode($response, true);

if ($httpCode === 200) {
    echo "✅ 성공!\n";
    echo "응답: " . ($data['output_text'] ?? 'No output_text') . "\n";
} else {
    echo "❌ 실패\n";
    echo "응답: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 2. reasoning 추가 테스트
echo "\n2. Reasoning 파라미터 테스트...\n";
$requestData = [
    'model' => 'gpt-5-mini',
    'input' => '2+2는?',
    'reasoning' => ['effort' => 'minimal']
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY_SECURE,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP 코드: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Reasoning 파라미터 작동!\n";
    echo "응답: " . ($data['output_text'] ?? '') . "\n";
} else {
    echo "❌ Reasoning 파라미터 실패\n";
}

// 3. text.verbosity 테스트
echo "\n3. Text verbosity 테스트...\n";
$requestData = [
    'model' => 'gpt-5-mini',
    'input' => '3+3는?',
    'text' => ['verbosity' => 'low']
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY_SECURE,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP 코드: $httpCode\n";
if ($httpCode === 200) {
    echo "✅ Text verbosity 작동!\n";
} else {
    $data = json_decode($response, true);
    echo "❌ Text verbosity 실패: " . ($data['error']['message'] ?? '') . "\n";
}

// 4. max_completion_tokens 테스트
echo "\n4. max_completion_tokens 테스트...\n";
$requestData = [
    'model' => 'gpt-5-mini',
    'input' => '4+4는?',
    'max_completion_tokens' => 10
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY_SECURE,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP 코드: $httpCode\n";
if ($httpCode === 200) {
    echo "✅ max_completion_tokens 작동!\n";
} else {
    $data = json_decode($response, true);
    echo "❌ max_completion_tokens 실패: " . ($data['error']['message'] ?? '') . "\n";
}

echo "\n=== 테스트 완료 ===\n";
?>