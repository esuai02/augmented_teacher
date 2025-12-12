<?php
/**
 * Agent19 Persona System Configuration
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Config
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    // 시스템 기본 설정
    'system' => [
        'version' => '1.0.0',
        'agent_id' => 19,
        'agent_name' => 'interaction_content',
        'description' => '학습 콘텐츠 상호작용 페르소나 시스템'
    ],

    // AI 연동 설정
    'ai' => [
        'confidence_threshold' => 0.7,  // 이 값 미만이면 AI 강화 요청
        'enable_ai_enhancement' => true,
        'ai_endpoint' => '/api/agent19/ai/enhance',
        'max_ai_calls_per_session' => 10,
        'ai_timeout_seconds' => 30
    ],

    // 페르소나 설정
    'persona' => [
        'dimensions' => ['cognitive', 'behavioral', 'emotional'],
        'cognitive_codes' => ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'],
        'behavioral_codes' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6'],
        'emotional_codes' => ['E1', 'E2', 'E3', 'E4', 'E5', 'E6'],
        'default_persona' => 'C1-B1-E6',  // 기본 페르소나
        'transition_cooldown' => 60,      // 페르소나 전환 최소 간격(초)
        'history_retention_days' => 90    // 이력 보관 기간
    ],

    // 컨텍스트 설정
    'context' => [
        'situations' => ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7'],
        'interactions' => ['I1', 'I2', 'I3', 'I4', 'I5', 'I6', 'I7'],
        'environments' => ['E1_CTX', 'E2_CTX', 'E3_CTX', 'E4_CTX'],
        'temporals' => ['T1_CTX', 'T2_CTX', 'T3_CTX', 'T4_CTX', 'T5_CTX'],
        'priority_order' => ['S4', 'S5', 'S1', 'S2', 'S3', 'S6', 'S7']
    ],

    // 임계값 설정
    'thresholds' => [
        // 이탈 감지 (S1)
        'dropout' => [
            'inactive_seconds' => 300,      // 5분 비활동 시 이탈 감지
            'engagement_decline' => -0.2     // 참여도 20% 하락
        ],

        // 지연 감지 (S2)
        'delay' => [
            'response_time_multiplier' => 2.0,  // 평균 응답시간의 2배
            'hesitation_seconds' => 30           // 시작 전 망설임
        ],

        // 휴식 권장 (S3)
        'rest' => [
            'session_duration' => 2400,    // 40분 후 휴식 권장
            'accuracy_decline' => -0.15,   // 정확도 15% 하락
            'recommended_break' => 300     // 5분 휴식 권장
        ],

        // 오류 패턴 (S4)
        'error' => [
            'consecutive_errors' => 3,     // 연속 3회 오류
            'same_type_errors' => 3,       // 동일 유형 3회 오류
            'error_rate' => 0.4            // 40% 오류율
        ],

        // 정서 감지 (S5)
        'emotional' => [
            'confidence_streak' => 5,      // 연속 5회 정답 → 자신감
            'frustration_pause' => 60,     // 60초 멈춤 → 좌절
            'boredom_skip_rate' => 0.3     // 30% 스킵율 → 권태
        ],

        // 활동 불균형 (S6)
        'imbalance' => [
            'activity_concentration' => 0.7,  // 70% 이상 한 활동에 집중
            'difficulty_bias' => 0.8          // 80% 이상 같은 난이도
        ]
    ],

    // 응답 생성 설정
    'response' => [
        'max_length' => 500,              // 최대 응답 길이
        'include_emoji' => true,           // 이모지 포함
        'personalization_level' => 'high', // high, medium, low
        'language' => 'ko',                // 기본 언어
        'tone_adaptation' => true          // 톤 적응 활성화
    ],

    // 로깅 설정
    'logging' => [
        'enable_persona_log' => true,
        'enable_context_log' => true,
        'enable_response_log' => true,
        'log_level' => 'info',  // debug, info, warning, error
        'log_retention_days' => 30
    ],

    // 캐싱 설정
    'cache' => [
        'enable' => true,
        'persona_ttl' => 300,     // 5분
        'context_ttl' => 60,      // 1분
        'template_ttl' => 3600    // 1시간
    ],

    // API 설정
    'api' => [
        'base_path' => '/api/agent19',
        'rate_limit' => 100,       // 분당 최대 요청 수
        'require_auth' => true,
        'cors_enabled' => false
    ],

    // 데이터베이스 테이블
    'tables' => [
        'persona_state' => 'mdl_agent19_persona_state',
        'persona_history' => 'mdl_agent19_persona_history',
        'context_history' => 'mdl_agent19_context_history',
        'context_rules' => 'mdl_agent19_context_rules',
        'response_templates' => 'mdl_agent19_response_templates',
        'response_log' => 'mdl_agent19_response_log'
    ]
];
