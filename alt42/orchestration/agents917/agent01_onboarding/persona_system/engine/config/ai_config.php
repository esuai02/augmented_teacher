<?php
/**
 * AI 설정 파일
 *
 * 중요: 이 파일을 .gitignore에 추가하세요!
 * 실제 API 키로 교체 후 사용
 */

return [
    // OpenAI API 키 (필수)
    'openai_api_key' => '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',

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
