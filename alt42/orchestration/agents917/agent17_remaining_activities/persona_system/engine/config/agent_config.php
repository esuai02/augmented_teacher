<?php
/**
 * Agent17 설정 파일
 *
 * 잔여 활동 조정 에이전트의 환경별 설정
 *
 * @package AugmentedTeacher\Agent17\Config
 * @version 1.0
 */

return [
    // ========================================
    // 기본 설정
    // ========================================
    'agent' => [
        'id' => 'agent17',
        'name' => '잔여 활동 조정 에이전트',
        'version' => '1.0.0',
        'description' => '학습 리듬 회복 및 잔여 활동 조정을 담당하는 에이전트'
    ],

    // ========================================
    // 엔진 설정
    // ========================================
    'engine' => [
        'debug_mode' => false,
        'log_enabled' => true,
        'cache_enabled' => true,
        'ai_enabled' => false,
        'ai_threshold' => 0.7
    ],

    // ========================================
    // 리듬 체크 설정
    // ========================================
    'rhythm' => [
        // 리듬 체크 간격 (초)
        'check_interval' => 300, // 5분

        // 비활동 임계값 (분)
        'inactivity_threshold' => 60,

        // 연속 실패 임계값
        'failure_thresholds' => [
            'warning' => 3,    // R4 진입
            'critical' => 5    // R5 진입
        ],

        // 완료율 임계값
        'completion_thresholds' => [
            'excellent' => 80,  // R1
            'good' => 50,       // R2
            'delayed' => 30,    // R3
            'stagnant' => 0     // R4
        ]
    ],

    // ========================================
    // 전략 설정
    // ========================================
    'strategies' => [
        'ST1' => [
            'name' => '질문하기',
            'description' => '탐색적 질문으로 학습자 스스로 발견 유도',
            'applicable_situations' => ['R1', 'R2'],
            'autonomy_level' => 'high'
        ],
        'ST2' => [
            'name' => '도제학습 전환',
            'description' => '시범을 보여주고 따라하게 함',
            'applicable_situations' => ['R3', 'R4', 'R5'],
            'autonomy_level' => 'low'
        ],
        'ST3' => [
            'name' => '활동축소',
            'description' => '핵심만 남기고 부담 감소',
            'applicable_situations' => ['R2', 'R3', 'R4'],
            'autonomy_level' => 'medium'
        ],
        'ST4' => [
            'name' => '하이튜터링',
            'description' => '1:1 집중 지원',
            'applicable_situations' => ['R4', 'R5'],
            'autonomy_level' => 'very_low'
        ],
        'ST5' => [
            'name' => '징검다리 활동',
            'description' => '현재와 목표 사이 중간 활동 제공',
            'applicable_situations' => ['R1', 'R3', 'R5'],
            'autonomy_level' => 'medium'
        ]
    ],

    // ========================================
    // 알림 설정
    // ========================================
    'alerts' => [
        // R5 상황에서 교사에게 알림
        'notify_teacher_on_breakdown' => true,

        // 알림 채널
        'channels' => [
            'database' => true,
            'email' => false,
            'push' => false
        ],

        // 알림 지연 (초) - 같은 사용자에 대해 중복 알림 방지
        'debounce_seconds' => 300
    ],

    // ========================================
    // 로깅 설정
    // ========================================
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'include_context' => true,
        'max_message_length' => 1000
    ],

    // ========================================
    // 캐시 설정
    // ========================================
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1시간
        'prefix' => 'agent17_persona_'
    ],

    // ========================================
    // API 설정
    // ========================================
    'api' => [
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 60,
            'window_seconds' => 60
        ],
        'timeout' => 30,
        'max_message_length' => 5000
    ],

    // ========================================
    // 데이터베이스 테이블
    // ========================================
    'tables' => [
        'user_learning_state' => 'at_user_learning_state',
        'agent_context' => 'at_agent_context',
        'user_activities' => 'at_user_activities',
        'agent_messages' => 'at_agent_messages',
        'persona_state' => 'at_agent_persona_state'
    ]
];

/*
 * 환경별 설정 오버라이드 예시:
 *
 * // 개발 환경
 * if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
 *     $config['engine']['debug_mode'] = true;
 *     $config['logging']['level'] = 'debug';
 *     $config['cache']['enabled'] = false;
 * }
 *
 * // 운영 환경
 * if (defined('PRODUCTION_MODE') && PRODUCTION_MODE) {
 *     $config['engine']['debug_mode'] = false;
 *     $config['logging']['level'] = 'error';
 *     $config['alerts']['notify_teacher_on_breakdown'] = true;
 * }
 */
