<?php
/**
 * ai_services.config.php - AI 서비스 설정 중앙화
 * 
 * 모든 AI 서비스(LLM, TTS, 실시간 튜터)의 설정을 중앙에서 관리
 * 
 * @package     AugmentedTeacher
 * @subpackage  Config
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/config/ai_services.config.php
 */

// 기존 config.php에서 API 키 로드
if (file_exists(__DIR__ . '/../config.php')) {
    require_once(__DIR__ . '/../config.php');
}

// =============================================================================
// 1. OpenAI API 설정
// =============================================================================

// API 키 (환경변수 우선, 없으면 기존 config.php 사용)
if (!defined('OPENAI_API_KEY')) {
    $envKey = getenv('OPENAI_API_KEY');
    define('OPENAI_API_KEY', $envKey ?: 'your-api-key-here');
}

// =============================================================================
// 2. LLM 설정
// =============================================================================
define('LLM_CONFIG', [
    // 모델 설정
    'default_model' => 'gpt-4o',           // 기본 모델 (고품질)
    'realtime_model' => 'gpt-4o-mini',     // 실시간용 모델 (빠른 응답)
    'advanced_model' => 'gpt-4o',          // 복잡한 분석용
    
    // 토큰 설정
    'max_tokens_default' => 300,           // 기본 최대 토큰
    'max_tokens_quick' => 100,             // 빠른 응답용
    'max_tokens_detailed' => 1000,         // 상세 설명용
    
    // 생성 파라미터
    'temperature_default' => 0.7,          // 기본 temperature (창의성)
    'temperature_precise' => 0.3,          // 정확한 응답용
    'temperature_creative' => 0.9,         // 창의적 응답용
    
    // 타임아웃
    'timeout_seconds' => 30,               // API 타임아웃
    'timeout_realtime' => 10,              // 실시간 튜터용 타임아웃
    
    // 재시도 설정
    'max_retries' => 2,                    // 최대 재시도 횟수
    'retry_delay_ms' => 500                // 재시도 간 대기 시간
]);

// =============================================================================
// 3. TTS 설정
// =============================================================================
define('TTS_CONFIG', [
    // 모델
    'model' => 'tts-1',                    // 기본: tts-1 (빠름), tts-1-hd (고품질)
    'model_hd' => 'tts-1-hd',              // 고품질 모델
    
    // 기본 음성
    'default_voice' => 'alloy',            // 기본 음성
    
    // 속도 설정
    'default_speed' => 1.0,                // 기본 속도 (0.25 ~ 4.0)
    'min_speed' => 0.25,
    'max_speed' => 4.0,
    
    // 포맷
    'response_format' => 'mp3',            // mp3, opus, aac, flac
    
    // 타임아웃
    'timeout_seconds' => 30,
    
    // 캐시 설정
    'cache_enabled' => true,
    'cache_ttl_hours' => 24                // 캐시 유효 시간
]);

// =============================================================================
// 4. 실시간 튜터 설정
// =============================================================================
define('REALTIME_TUTOR_CONFIG', [
    // 지연 시간 목표
    'latency_target_ms' => 300,            // 목표 지연 시간 (ms)
    'latency_max_ms' => 500,               // 최대 허용 지연 시간
    
    // 개입 임계값 (Brain Layer)
    'intervention_threshold' => 0.7,       // 개입 결정 임계값
    'micro_hint_threshold' => 0.4,         // 미세 힌트 임계값
    'observation_threshold' => 0.2,        // 관찰만 하는 임계값
    
    // 타이밍 설정
    'backchannel_interval_seconds' => 15,  // 추임새 최소 간격
    'silence_check_seconds' => 20,         // 침묵 후 상태 체크
    'golden_time_seconds' => 30,           // 골든 타임 (개입 최적 시간)
    
    // 행동 감지
    'mouse_jitter_threshold' => 50,        // 마우스 흔들림 감지 (px)
    'idle_threshold_seconds' => 10,        // 비활성 감지 (초)
    
    // 감정 임계값
    'frustration_threshold' => 0.7,        // 좌절 감지 임계값
    'anxiety_threshold' => 0.6,            // 불안 감지 임계값
    'boredom_threshold' => 0.5,            // 지루함 감지 임계값
    
    // 모드 설정
    'modes' => [
        'observe' => '관찰만 (개입 없음)',
        'suggest' => '힌트 제안',
        'guide' => '적극적 가이드',
        'intervene' => '즉시 개입'
    ]
]);

// =============================================================================
// 5. 파동함수 → 스타일 매핑
// =============================================================================
define('WAVEFUNCTION_STYLE_MAP', [
    // 감정 파동 (ψ_Affect) 기반
    'affect_calm' => [
        'tone' => 'calm',
        'speed' => 0.9,
        'voice' => 'alloy',
        'description' => '차분하고 안정적인 상태'
    ],
    'affect_anxious' => [
        'tone' => 'encouraging',
        'speed' => 0.95,
        'voice' => 'shimmer',
        'description' => '불안한 상태 → 격려 필요'
    ],
    'affect_frustrated' => [
        'tone' => 'encouraging',
        'speed' => 0.9,
        'voice' => 'shimmer',
        'description' => '좌절 상태 → 위로와 격려'
    ],
    'affect_excited' => [
        'tone' => 'excited',
        'speed' => 1.1,
        'voice' => 'nova',
        'description' => '흥분/열정 상태'
    ],
    
    // 아하 모멘트 (ψ_Aha) 기반
    'aha_moment' => [
        'tone' => 'excited',
        'speed' => 1.2,
        'voice' => 'nova',
        'description' => '깨달음의 순간! 즉각 칭찬'
    ],
    
    // 집중 파동 (ψ_WM) 기반
    'focus_high' => [
        'tone' => 'calm',
        'speed' => 0.95,
        'voice' => 'alloy',
        'description' => '높은 집중 → 방해하지 않음'
    ],
    'focus_breaking' => [
        'tone' => 'curious',
        'speed' => 1.0,
        'voice' => 'echo',
        'description' => '집중 이탈 조짐 → 주의 환기'
    ],
    
    // 이탈 위험 (ψ_Dropout) 기반
    'dropout_risk_low' => [
        'tone' => 'neutral',
        'speed' => 1.0,
        'voice' => 'alloy',
        'description' => '정상 상태'
    ],
    'dropout_risk_medium' => [
        'tone' => 'encouraging',
        'speed' => 1.0,
        'voice' => 'shimmer',
        'description' => '주의 필요'
    ],
    'dropout_risk_high' => [
        'tone' => 'serious',
        'speed' => 0.95,
        'voice' => 'onyx',
        'description' => '긴급 개입 필요'
    ]
]);

// =============================================================================
// 6. 페르소나별 설정
// =============================================================================
define('PERSONA_CONFIG', [
    'sprinter' => [
        'name' => '스프린터',
        'voice' => 'nova',
        'speed' => 1.1,
        'style' => '빠르고 간결하게'
    ],
    'diver' => [
        'name' => '다이버',
        'voice' => 'echo',
        'speed' => 0.95,
        'style' => '깊이 있고 상세하게'
    ],
    'gamer' => [
        'name' => '게이머',
        'voice' => 'nova',
        'speed' => 1.05,
        'style' => '도전적이고 재미있게'
    ],
    'architect' => [
        'name' => '아키텍트',
        'voice' => 'onyx',
        'speed' => 0.9,
        'style' => '체계적이고 논리적으로'
    ]
]);

// =============================================================================
// 7. 에이전트별 AI 사용 설정
// =============================================================================
define('AGENT_AI_CONFIG', [
    // Brain Layer 핵심 에이전트
    'brain_layer' => [
        'agents' => [7, 8, 9, 10, 11, 13, 14],
        'llm_model' => 'gpt-4o-mini',      // 빠른 판단용
        'priority' => 'high'
    ],
    
    // Mind Layer 에이전트
    'mind_layer' => [
        'agents' => [16, 19],
        'llm_model' => 'gpt-4o',           // 고품질 대사 생성
        'priority' => 'high'
    ],
    
    // Mouth Layer 에이전트
    'mouth_layer' => [
        'agents' => [21],
        'tts_model' => 'tts-1',
        'priority' => 'critical'
    ],
    
    // 분석 에이전트 (비실시간)
    'analysis_layer' => [
        'agents' => [3, 4, 5, 6],
        'llm_model' => 'gpt-4o',
        'priority' => 'normal'
    ]
]);

// =============================================================================
// 8. 디버그 및 로깅 설정
// =============================================================================
define('AI_DEBUG_CONFIG', [
    'enabled' => false,                    // 디버그 모드
    'log_api_calls' => false,              // API 호출 로깅
    'log_tokens' => false,                 // 토큰 사용량 로깅
    'log_latency' => true,                 // 지연 시간 로깅
    'log_path' => __DIR__ . '/../logs/ai_services.log'
]);

// =============================================================================
// 헬퍼 함수
// =============================================================================

/**
 * 양자 상태에 따른 스타일 가져오기
 * 
 * @param float $affect 감정 수치 (0~1)
 * @param float $confusion 혼란 수치 (0~1)
 * @param float $energy 에너지 수치 (0~1)
 * @return array
 */
function getStyleByQuantumState(float $affect, float $confusion = 0, float $energy = 0.5): array
{
    $map = WAVEFUNCTION_STYLE_MAP;
    
    if ($affect < 0.3) {
        return $confusion > 0.5 ? $map['affect_anxious'] : $map['affect_frustrated'];
    }
    if ($affect > 0.8) {
        return $map['affect_excited'];
    }
    if ($confusion > 0.6) {
        return $map['focus_breaking'];
    }
    if ($energy > 0.7) {
        return $map['affect_calm'];
    }
    
    return $map['dropout_risk_low'];
}

/**
 * 페르소나별 설정 가져오기
 * 
 * @param string $personaType
 * @return array
 */
function getPersonaConfig(string $personaType): array
{
    $config = PERSONA_CONFIG;
    return $config[$personaType] ?? $config['sprinter'];
}

