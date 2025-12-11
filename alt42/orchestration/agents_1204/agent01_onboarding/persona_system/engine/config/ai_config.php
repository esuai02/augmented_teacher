<?php
/**
 * AI 설정 파일
 *
 * API 키는 Moodle $CFG->openai_api_key에서 가져옴
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $CFG;

// API 키 검증
$openai_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($openai_key)) {
    error_log('[ai_config.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}

return [
    // OpenAI API 키 ($CFG에서 가져옴)
    'openai_api_key' => $openai_key,

    // 모델 설정 (선택)
    'models' => [
        'nlu' => 'gpt-4-1106-preview',
        'reasoning' => 'gpt-4-1106-preview',
        'chat' => 'gpt-4o-mini',
        'code' => 'gpt-4o'
    ],

    // 비용 제한 (일일 토큰 한도)
    'daily_token_limit' => 100000,

    // 캐시 설정
    'cache_enabled' => true,
    'cache_ttl' => 3600,

    // 디버그 모드
    'debug_mode' => false
];
