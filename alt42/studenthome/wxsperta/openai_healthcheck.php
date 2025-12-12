<?php
/**
 * OpenAI Healthcheck (관리자용)
 * - OpenAI API 연결/인증/모델 오류를 빠르게 확인
 */
require_once("/home/moodle/public_html/moodle/config.php");
require_once(__DIR__ . "/config.php");
global $USER;
require_login();

if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

header('Content-Type: text/plain; charset=utf-8');

$envKey = getenv('OPENAI_API_KEY');
$constKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
$apiKey = trim((string)($envKey ?: $constKey));

echo "WXSPERTA OpenAI Healthcheck\n";
echo "---------------------------\n";
echo "OPENAI_API_URL: " . (defined('OPENAI_API_URL') ? OPENAI_API_URL : '(undefined)') . "\n";
echo "OPENAI_MODEL  : " . (defined('OPENAI_MODEL') ? OPENAI_MODEL : '(undefined)') . "\n";
echo "API_KEY source: " . ($envKey ? 'env' : 'config.php') . "\n";
echo "API_KEY set?  : " . ($apiKey !== '' ? 'YES' : 'NO') . "\n";
echo "API_KEY looks placeholder?: " . (function_exists('wxsperta_is_placeholder_openai_key') && wxsperta_is_placeholder_openai_key($apiKey) ? 'YES' : 'NO') . "\n";
echo "\n";

$cv = function_exists('curl_version') ? curl_version() : null;
if ($cv) {
    echo "curl_version: " . ($cv['version'] ?? '') . "\n";
    echo "ssl_version : " . ($cv['ssl_version'] ?? '') . "\n";
}
echo "\n";

$messages = [
    ['role' => 'system', 'content' => 'You are a test agent. Reply with: OK'],
    ['role' => 'user', 'content' => 'healthcheck']
];

$res = call_openai_api($messages, 0);
if ($res === false) {
    echo "RESULT: FAIL (call_openai_api returned false)\n";
    echo "Check server outbound network, TLS, API key, model access.\n";
    exit;
}

echo "RESULT: SUCCESS\n";
echo "RESPONSE:\n";
echo $res . "\n";


