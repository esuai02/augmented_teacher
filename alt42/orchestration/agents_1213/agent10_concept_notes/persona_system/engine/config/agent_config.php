<?php
/**
 * Agent10 페르소나 엔진 설정
 *
 * @package AugmentedTeacher\Agents\Agent10\Config
 * @version 1.0
 * @created 2025-12-02
 */

return [
    // 기본 설정
    'debug_mode' => false,
    'log_enabled' => true,
    'cache_enabled' => true,
    'ai_enabled' => false,
    'ai_threshold' => 0.7,

    // Agent10 전용 설정
    'agent_info' => [
        'id' => 'agent10',
        'name' => 'Concept Notes Agent',
        'name_ko' => '개념 노트 에이전트',
        'description' => '개념노트(화이트보드 필기) 데이터를 기반으로 개념 이해도와 학습 흐름을 해석',
        'version' => '1.0.0'
    ],

    // 노트 메트릭 임계값
    'note_metrics' => [
        'stroke_threshold_high' => 100,     // 높은 필기량 기준 (획 수)
        'stroke_threshold_low' => 20,        // 낮은 필기량 기준
        'recency_days_recent' => 7,          // 최근 기준 (일)
        'recency_days_old' => 30,            // 오래된 기준 (일)
        'usedtime_threshold_short' => 300,   // 짧은 사용시간 (초, 5분)
        'usedtime_threshold_long' => 1800    // 긴 사용시간 (초, 30분)
    ],

    // 상황 코드 정의
    'situation_codes' => [
        'N1' => [
            'name' => '노트 탐색 시작',
            'description' => '학생이 처음 개념 노트 시스템에 접근하거나 새로운 노트를 시작하는 단계',
            'default_persona' => 'N1_P1'
        ],
        'N2' => [
            'name' => '개념 이해도 분석',
            'description' => '기존 노트 데이터를 기반으로 개념 이해 수준을 분석하는 단계',
            'default_persona' => 'N2_P1'
        ],
        'N3' => [
            'name' => '학습 흐름 해석',
            'description' => '노트 작성 패턴과 시간 흐름을 분석하여 학습 흐름을 파악하는 단계',
            'default_persona' => 'N3_P1'
        ],
        'N4' => [
            'name' => '복습 권장 판단',
            'description' => '오래된 노트나 복습이 필요한 개념을 식별하고 권장하는 단계',
            'default_persona' => 'N4_P1'
        ],
        'N5' => [
            'name' => '노트 활용 전략',
            'description' => '효과적인 노트 작성 및 활용 전략을 제안하는 단계',
            'default_persona' => 'N5_P1'
        ]
    ],

    // 응답 톤 설정
    'tone_settings' => [
        'default' => 'Professional',
        'available' => ['Professional', 'Gentle', 'Encouraging', 'Analytical', 'Supportive']
    ],

    // 개입 유형 설정
    'intervention_types' => [
        'default' => 'InformationProvision',
        'available' => [
            'InformationProvision',      // 정보 제공
            'ReviewRecommendation',      // 복습 권장
            'StrategyGuidance',          // 전략 안내
            'MotivationBoost',           // 동기 부여
            'PatternAnalysis',           // 패턴 분석
            'ProgressFeedback'           // 진행 피드백
        ]
    ],

    // 캐시 설정
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,           // 캐시 유효 시간 (초)
        'prefix' => 'agent10_'
    ],

    // 로깅 설정
    'logging' => [
        'enabled' => true,
        'level' => 'info',       // debug, info, warning, error
        'log_persona_changes' => true,
        'log_context_updates' => true
    ]
];

/*
 * 파일 정보:
 * - 경로: agent10_concept_notes/persona_system/engine/config/agent_config.php
 */
