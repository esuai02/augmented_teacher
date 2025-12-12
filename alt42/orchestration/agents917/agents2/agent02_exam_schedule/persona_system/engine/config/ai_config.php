<?php
/**
 * AI 설정 파일 - Agent02 시험일정 에이전트
 *
 * 중요: 이 파일을 .gitignore에 추가하세요!
 * 실제 API 키로 교체 후 사용
 *
 * @package AugmentedTeacher\Agent02\PersonaSystem
 * @version 1.0
 */

return [
    // OpenAI API 키 (필수) - 실제 키로 교체 필요
    'openai_api_key' => 'YOUR_OPENAI_API_KEY_HERE',

    // 모델 설정 (선택)
    'models' => [
        'nlu' => 'gpt-4-1106-preview',       // 자연어 이해
        'reasoning' => 'gpt-4-1106-preview',  // 추론
        'chat' => 'gpt-4o-mini',              // 일반 대화
        'strategy' => 'gpt-4o'                // 시험 전략 생성
    ],

    // 비용 제한 (일일 토큰 한도)
    'daily_token_limit' => 100000,

    // 캐시 설정
    'cache_enabled' => true,
    'cache_ttl' => 3600,

    // 디버그 모드
    'debug_mode' => false,

    // Agent02 전용 설정
    'agent02_settings' => [
        // D-Day 알림 임계값
        'dday_thresholds' => [
            'critical' => 3,   // D-3 이하: 긴급
            'warning' => 7,    // D-7 이하: 경고
            'normal' => 14,    // D-14 이하: 주의
            'planning' => 30   // D-30 이하: 계획
        ],

        // 학생 유형별 기본 전략
        'default_strategies' => [
            'P1' => 'structured_sprint',    // 계획형
            'P2' => 'calm_focus',           // 불안형
            'P3' => 'habit_building',       // 회피형
            'P4' => 'reality_check',        // 자신감과잉
            'P5' => 'progressive_build',    // 혼란형
            'P6' => 'external_structure'    // 외부의존
        ],

        // 응답 길이 제한
        'max_response_tokens' => 500
    ]
];
