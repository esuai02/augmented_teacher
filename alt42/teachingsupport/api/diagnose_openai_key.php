<?php
/**
 * OpenAI 키 진단 엔드포인트 (민감정보 마스킹)
 * - $CFG->openai_api_key가 env_only로 제대로 주입되는지 확인용
 * - 응답은 JSON
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

include_once("/home/moodle/public_html/moodle/config.php");
global $CFG, $USER;

try {
    require_login();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => '로그인이 필요합니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once(__DIR__ . '/../config.php');

function mask_key($key) {
    if (!is_string($key)) return '';
    $key = trim($key);
    if ($key === '') return '';
    $len = strlen($key);
    if ($len <= 12) return str_repeat('*', $len);
    return substr($key, 0, 7) . str_repeat('*', max(0, $len - 11)) . substr($key, -4);
}

$cfgKey = (isset($CFG) && is_object($CFG) && isset($CFG->openai_api_key)) ? (string)$CFG->openai_api_key : '';
$envKey = (string)getenv('OPENAI_API_KEY');
$resolved = function_exists('get_openai_api_key') ? get_openai_api_key() : '';

echo json_encode([
    'success' => true,
    'user_id' => $USER->id ?? null,
    'cfg_openai_api_key_masked' => mask_key($cfgKey),
    'env_OPENAI_API_KEY_masked' => mask_key($envKey),
    'resolved_key_masked' => mask_key($resolved),
    'cfg_key_valid' => function_exists('is_valid_openai_api_key') ? is_valid_openai_api_key($cfgKey) : null,
    'env_key_valid' => function_exists('is_valid_openai_api_key') ? is_valid_openai_api_key($envKey) : null,
    'resolved_key_valid' => function_exists('is_valid_openai_api_key') ? is_valid_openai_api_key($resolved) : null,
], JSON_UNESCAPED_UNICODE);


