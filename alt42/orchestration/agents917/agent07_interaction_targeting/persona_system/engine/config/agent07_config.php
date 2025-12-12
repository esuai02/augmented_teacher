<?php
/**
 * Agent07 Persona System Configuration
 *
 * 페르소나 시스템의 기본 설정값 정의
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - PersonaRuleEngine.php: 설정 사용처
 * - ResponseGenerator.php: 응답 생성 설정
 */

// 환경별 설정이 필요한 경우 여기서 분기
$environment = 'production'; // development, staging, production

return array(
    /**
     * 환경 설정
     */
    'environment' => $environment,
    'debug_mode' => ($environment === 'development'),

    /**
     * 규칙 엔진 설정
     */
    'rule_engine' => array(
        // 신뢰도 계산 관련
        'base_confidence' => 0.5,          // 기본 신뢰도
        'condition_match_boost' => 0.1,    // 조건 매칭시 부스트
        'all_conditions_bonus' => 0.2,     // 모든 조건 충족시 보너스
        'keyword_match_boost' => 0.05,     // 키워드 매칭 부스트
        'min_confidence' => 0.3,           // 최소 신뢰도
        'max_confidence' => 1.0,           // 최대 신뢰도

        // 캐싱 설정
        'cache_rules' => true,
        'cache_ttl' => 3600,               // 1시간

        // 로깅 설정
        'enable_logging' => true,
        'log_level' => 'info'              // debug, info, warning, error
    ),

    /**
     * 응답 생성 설정
     */
    'response_generator' => array(
        'enable_logging' => true,
        'default_tone' => 'supportive, encouraging',
        'max_response_length' => 2000,
        'include_persona_info' => false,   // 디버그용
        'language' => 'ko'
    ),

    /**
     * 컨텍스트 데이터 설정
     */
    'data_context' => array(
        // 세션 설정
        'session_start_threshold' => 300,  // 5분 (세션 시작 판단 기준)

        // 시간 임계값
        'stuck_duration_threshold' => 180, // 3분 (문제에 막힘 판단)
        'idle_time_threshold' => 60,       // 1분 (비활동 판단)

        // 메시지 분석 설정
        'min_message_length_for_clarity' => 20,
        'high_clarity_threshold' => 100,   // 100자 이상 = 명확한 표현

        // 한국어 키워드 설정
        'korean_keywords' => array(
            'help' => array('도와', '도움', '모르겠', '어려워', '막혀', '안돼', '헷갈'),
            'urgent' => array('급해', '빨리', '지금', '당장', '바로'),
            'confusion' => array('음', '글쎄', '그냥', '몰라', '뭐지', '아...'),
            'negative_self' => array('못했', '부족', '실패', '후회', '바보', '멍청'),
            'vague' => array('잘하고싶', '열심히', '좀더', '그냥', '뭔가'),
            'external_pressure' => array('엄마가', '아빠가', '선생님이', '해야해서', '시켜서'),
            'termination' => array('끝내자', '그만', '됐어', '가야해', '빨리끝')
        )
    ),

    /**
     * 상황(Situation) 기본 설정
     */
    'situations' => array(
        'S1' => array(
            'name' => '실시간고민',
            'description' => '학습 중 즉각적인 도움이 필요한 상황',
            'priority' => 90
        ),
        'S2' => array(
            'name' => '포모도로',
            'description' => '집중 학습 세션 중',
            'priority' => 85
        ),
        'S3' => array(
            'name' => '수업준비',
            'description' => '다가오는 수업을 위한 준비',
            'priority' => 80
        ),
        'S4' => array(
            'name' => '목표설정',
            'description' => '학습 목표 수립 단계',
            'priority' => 70
        ),
        'S5' => array(
            'name' => '귀가검사',
            'description' => '하루 학습 마무리 및 회고',
            'priority' => 75
        ),
        'S6' => array(
            'name' => '커리큘럼',
            'description' => '장기 학습 계획 수립',
            'priority' => 65
        )
    ),

    /**
     * 페르소나별 톤 설정
     */
    'persona_tones' => array(
        // S1: 실시간고민
        'S1_P1' => 'calm, patient, step-by-step',
        'S1_P2' => 'gentle, probing, clarifying',
        'S1_P3' => 'enthusiastic, curious, engaging',

        // S2: 포모도로
        'S2_P1' => 'understanding, redirecting',
        'S2_P2' => 'empathetic, motivating',
        'S2_P3' => 'encouraging, acknowledging',

        // S3: 수업준비
        'S3_P1' => 'structured, efficient',
        'S3_P2' => 'urgent, focused, practical',
        'S3_P3' => 'encouraging, preparatory',

        // S4: 목표설정
        'S4_P1' => 'patient, guiding',
        'S4_P2' => 'structured, clarifying',
        'S4_P3' => 'inspiring, visionary',

        // S5: 귀가검사
        'S5_P1' => 'reflective, supportive',
        'S5_P2' => 'celebratory, reinforcing',
        'S5_P3' => 'efficient, understanding',

        // S6: 커리큘럼
        'S6_P1' => 'patient, guiding',
        'S6_P2' => 'strategic, comprehensive',
        'S6_P3' => 'curious, encouraging'
    ),

    /**
     * Fallback 설정
     */
    'fallback' => array(
        'default_situation' => 'S4',        // 기본 상황: 목표설정
        'situation_confidence' => 0.3,
        'persona_confidence' => 0.4,
        'defaults' => array(
            'S1' => 'S1_P2',
            'S2' => 'S2_P2',
            'S3' => 'S3_P1',
            'S4' => 'S4_P2',
            'S5' => 'S5_P2',
            'S6' => 'S6_P2'
        )
    ),

    /**
     * DB 테이블 설정
     */
    'database' => array(
        'persona_log_table' => 'agent07_persona_log',
        'response_log_table' => 'agent07_response_log',
        'context_log_table' => 'agent07_context_log',
        'user_state_table' => 'agent07_user_state'
    ),

    /**
     * API 설정
     */
    'api' => array(
        'rate_limit' => 60,                // 분당 최대 요청 수
        'timeout' => 30,                   // 타임아웃 (초)
        'cors_enabled' => false,
        'allowed_origins' => array()
    )
);
