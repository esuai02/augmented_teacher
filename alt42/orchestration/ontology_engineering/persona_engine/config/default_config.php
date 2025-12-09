<?php
/**
 * Persona Engine 기본 설정
 * 
 * 모든 에이전트에 공통으로 적용되는 기본 설정
 * 에이전트별 커스텀 설정은 각 에이전트 폴더의 config.php에서 오버라이드
 * 
 * @package AugmentedTeacher\PersonaEngine\Config
 * @version 1.0
 */

return [
    // ==========================================
    // 시스템 설정
    // ==========================================
    'system' => [
        'debug_mode' => false,
        'log_enabled' => true,
        'log_level' => 'error', // debug, info, warning, error
        'timezone' => 'Asia/Seoul',
        'locale' => 'ko_KR',
    ],

    // ==========================================
    // 캐시 설정
    // ==========================================
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 초 (1시간)
        'driver' => 'memory', // memory, file, database
        'prefix' => 'persona_engine_',
    ],

    // ==========================================
    // 페르소나 엔진 설정
    // ==========================================
    'engine' => [
        'default_persona' => 'default',
        'default_tone' => 'Professional',
        'default_intervention' => 'InformationProvision',
        'confidence_threshold' => 0.5, // 최소 신뢰도
        'rules_format' => 'yaml', // yaml, json
        'enable_fallback' => true,
    ],

    // ==========================================
    // 조건 평가기 설정
    // ==========================================
    'condition_evaluator' => [
        'operators' => [
            '==', '!=', '>', '<', '>=', '<=',
            'contains', 'not_contains', 'contains_any', 'contains_all',
            'in', 'not_in',
            'regex',
            'between',
            'starts_with', 'ends_with',
            'empty', 'not_empty',
            'length_gt', 'length_lt', 'length_eq',
        ],
        'short_circuit_or' => true,
        'short_circuit_and' => true,
    ],

    // ==========================================
    // 데이터 컨텍스트 설정
    // ==========================================
    'data_context' => [
        'load_user_profile' => true,
        'load_learning_history' => false,
        'load_previous_persona' => true,
        'history_limit' => 10, // 최근 N개 대화 로드
    ],

    // ==========================================
    // 응답 생성기 설정
    // ==========================================
    'response_generator' => [
        'template_format' => 'mustache', // mustache ({{variable}})
        'default_language' => 'ko',
        'enable_emoji' => true,
        'max_response_length' => 2000, // 문자 수
    ],

    // ==========================================
    // 톤 스타일 정의
    // ==========================================
    'tone_styles' => [
        'Professional' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '님',
            'formal' => true,
            'emoji_level' => 0, // 0=없음, 1=최소, 2=보통, 3=많음
        ],
        'Warm' => [
            'prefix' => '안녕하세요! ',
            'suffix' => ' 😊',
            'honorific' => '님',
            'formal' => false,
            'emoji_level' => 2,
        ],
        'Encouraging' => [
            'prefix' => '잘하고 있어요! ',
            'suffix' => ' 파이팅!',
            'honorific' => '',
            'formal' => false,
            'emoji_level' => 2,
        ],
        'Calm' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '님',
            'formal' => true,
            'emoji_level' => 0,
        ],
        'Playful' => [
            'prefix' => '헤이! ',
            'suffix' => ' ㅋㅋ',
            'honorific' => '',
            'formal' => false,
            'emoji_level' => 3,
        ],
        'Direct' => [
            'prefix' => '',
            'suffix' => '',
            'honorific' => '',
            'formal' => false,
            'emoji_level' => 0,
        ],
        'Empathetic' => [
            'prefix' => '그런 마음이 드셨군요. ',
            'suffix' => ' 걱정하지 마세요.',
            'honorific' => '님',
            'formal' => true,
            'emoji_level' => 1,
        ],
    ],

    // ==========================================
    // 개입 패턴 정의
    // ==========================================
    'intervention_patterns' => [
        'EmotionalSupport' => [
            'template_suffix' => '_emotional',
            'priority' => 1,
            'requires_empathy' => true,
            'description' => '정서적 지원 및 공감',
        ],
        'InformationProvision' => [
            'template_suffix' => '_info',
            'priority' => 2,
            'requires_empathy' => false,
            'description' => '정보 제공',
        ],
        'SkillBuilding' => [
            'template_suffix' => '_skill',
            'priority' => 3,
            'requires_empathy' => false,
            'description' => '스킬 개발 지원',
        ],
        'BehaviorModification' => [
            'template_suffix' => '_behavior',
            'priority' => 4,
            'requires_empathy' => true,
            'description' => '행동 수정 유도',
        ],
        'SafetyNet' => [
            'template_suffix' => '_safety',
            'priority' => 0, // 최우선
            'requires_empathy' => true,
            'description' => '안전망 개입',
        ],
        'PlanDesign' => [
            'template_suffix' => '_plan',
            'priority' => 3,
            'requires_empathy' => false,
            'description' => '계획 수립 지원',
        ],
        'CrisisIntervention' => [
            'template_suffix' => '_crisis',
            'priority' => 0, // 최우선
            'requires_empathy' => true,
            'description' => '위기 개입',
        ],
    ],

    // ==========================================
    // 감정 키워드 사전
    // ==========================================
    'emotion_keywords' => [
        'positive' => ['좋', '감사', '기쁨', '잘', '행복', '신나', '재미', '성공', '해냈', '이해'],
        'negative' => ['싫', '힘들', '어려', '모르', '못', '불안', '걱정', '스트레스', '포기', '최악'],
        'neutral' => ['그냥', '보통', '평범', '일반', '음', '글쎄'],
        'anxious' => ['긴장', '떨려', '불안', '걱정', '두렵', '무서'],
        'confused' => ['혼란', '모르겠', '이해가 안', '뭔지', '어떻게'],
        'frustrated' => ['짜증', '화나', '답답', '왜 안', '계속', '또', '어휴'],
    ],

    // ==========================================
    // 의도 패턴 사전
    // ==========================================
    'intent_patterns' => [
        'question' => ['?', '어떻게', '왜', '무엇', '뭐', '언제', '어디', '누가'],
        'help_request' => ['도와', '알려', '설명', '가르쳐', '모르겠어'],
        'confirmation' => ['맞아', '그래', '응', '네', '확인'],
        'complaint' => ['불만', '싫어', '왜 이래', '안 돼', '문제'],
        'greeting' => ['안녕', '반가', 'ㅎㅇ', '하이', '헬로'],
        'farewell' => ['잘가', '바이', '안녕', '다음에', '끝'],
    ],

    // ==========================================
    // 통신 설정
    // ==========================================
    'communication' => [
        'message_expire_seconds' => 3600, // 메시지 만료 시간
        'max_retry_count' => 3,
        'cleanup_days' => 7, // 오래된 메시지 정리 기준
        'broadcast_enabled' => true,
    ],

    // ==========================================
    // 로깅 설정
    // ==========================================
    'logging' => [
        'log_requests' => true,
        'log_responses' => true,
        'log_events' => true,
        'log_errors' => true,
        'include_context' => false, // 컨텍스트 전체 로깅 (대용량)
    ],

    // ==========================================
    // 성능 설정
    // ==========================================
    'performance' => [
        'max_processing_time_ms' => 5000, // 최대 처리 시간
        'enable_profiling' => false,
        'batch_size' => 100, // 배치 처리 크기
    ],
];

/**
 * 설정 파일 사용법:
 * 
 * $config = require(__DIR__ . '/default_config.php');
 * 
 * // 또는 에이전트별 커스텀 설정 병합
 * $defaultConfig = require(__DIR__ . '/default_config.php');
 * $agentConfig = require($agentPath . '/config.php');
 * $config = array_replace_recursive($defaultConfig, $agentConfig);
 */
