<?php
/**
 * PersonaEngine 설정 파일
 *
 * 전체 페르소나 엔진의 글로벌 설정
 * 에이전트별 로컬 설정은 각 agent/persona_system/config.php에서 오버라이드
 *
 * @package AugmentedTeacher\PersonaEngine\Config
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\PersonaEngine\Config;

/**
 * 글로벌 설정 상수
 */
define('PERSONA_ENGINE_VERSION', '1.0.0');
define('PERSONA_ENGINE_DEBUG', false);

/**
 * PersonaEngineConfig - 페르소나 엔진 설정 클래스
 */
class PersonaEngineConfig {

    /** @var array 기본 설정 */
    private static $defaults = [
        // 일반 설정
        'debug_mode' => false,
        'log_level' => 'error',  // debug, info, warning, error
        'timezone' => 'Asia/Seoul',
        
        // 캐시 설정
        'cache' => [
            'enabled' => true,
            'ttl' => 300,           // 5분
            'state_ttl' => 60,      // 상태 캐시 1분
            'rules_ttl' => 3600     // 규칙 캐시 1시간
        ],
        
        // 메시지 버스 설정
        'message_bus' => [
            'batch_size' => 10,             // 한 번에 처리할 메시지 수
            'retry_limit' => 3,             // 재시도 제한
            'cleanup_days' => 7,            // 오래된 메시지 정리 (일)
            'message_ttl' => 3600,          // 메시지 유효 시간 (초)
            'priority_levels' => [
                'critical' => 1,
                'high' => 2,
                'normal' => 3,
                'low' => 4,
                'batch' => 5
            ]
        ],
        
        // 페르소나 상태 설정
        'persona_state' => [
            'sync_interval' => 30,          // 동기화 간격 (초)
            'version_conflict_strategy' => 'latest_wins', // latest_wins, merge, reject
            'history_retention_days' => 90  // 이력 보관 기간
        ],
        
        // 규칙 파서 설정
        'rule_parser' => [
            'default_format' => 'yaml',
            'supported_formats' => ['yaml', 'yml', 'json'],
            'cache_parsed_rules' => true
        ],
        
        // 조건 평가기 설정
        'condition_evaluator' => [
            'strict_mode' => false,         // 엄격 모드 (타입 체크 강화)
            'default_logic' => 'AND'        // 기본 논리 연산자
        ],
        
        // 응답 생성기 설정
        'response_generator' => [
            'default_mode' => 'template',   // template, ai, hybrid
            'default_tone' => 'Professional',
            'supported_tones' => [
                'Professional', 'Friendly', 'Encouraging', 
                'Empathetic', 'Supportive', 'Directive', 'Informative'
            ],
            'ai_fallback' => true           // AI 응답 실패 시 템플릿 폴백
        ],
        
        // 에이전트 설정
        'agents' => [
            'timeout' => 30,                // 에이전트 응답 타임아웃 (초)
            'max_concurrent_messages' => 100 // 동시 처리 최대 메시지
        ],
        
        // 보안 설정
        'security' => [
            'validate_checksums' => true,   // 메시지 체크섬 검증
            'encrypt_state_data' => false,  // 상태 데이터 암호화
            'allowed_agents' => null        // null이면 모든 에이전트 허용
        ]
    ];

    /** @var array 로드된 설정 */
    private static $config = null;

    /** @var array 에이전트별 오버라이드 */
    private static $agentOverrides = [];

    /**
     * 설정 로드 (싱글톤)
     *
     * @return array 전체 설정
     */
    public static function load(): array {
        if (self::$config === null) {
            self::$config = self::$defaults;
        }
        return self::$config;
    }

    /**
     * 설정값 가져오기
     *
     * @param string $key 설정 키 (점 표기법 지원: 'cache.ttl')
     * @param mixed $default 기본값
     * @return mixed 설정값
     */
    public static function get(string $key, $default = null) {
        $config = self::load();
        return self::getNestedValue($config, $key, $default);
    }

    /**
     * 설정값 설정 (런타임)
     *
     * @param string $key 설정 키
     * @param mixed $value 값
     */
    public static function set(string $key, $value): void {
        self::load();
        self::setNestedValue(self::$config, $key, $value);
    }

    /**
     * 에이전트별 설정 가져오기
     *
     * @param string $agentId 에이전트 ID
     * @param string $key 설정 키
     * @param mixed $default 기본값
     * @return mixed 설정값
     */
    public static function getForAgent(string $agentId, string $key, $default = null) {
        // 에이전트별 오버라이드 확인
        if (isset(self::$agentOverrides[$agentId][$key])) {
            return self::$agentOverrides[$agentId][$key];
        }
        
        // 전역 설정 반환
        return self::get($key, $default);
    }

    /**
     * 에이전트별 설정 오버라이드
     *
     * @param string $agentId 에이전트 ID
     * @param array $overrides 오버라이드 설정
     */
    public static function setAgentOverrides(string $agentId, array $overrides): void {
        self::$agentOverrides[$agentId] = array_merge(
            self::$agentOverrides[$agentId] ?? [],
            $overrides
        );
    }

    /**
     * 디버그 모드 확인
     *
     * @return bool 디버그 모드 여부
     */
    public static function isDebugMode(): bool {
        return self::get('debug_mode', false) || PERSONA_ENGINE_DEBUG;
    }

    /**
     * 지원되는 톤 목록 가져오기
     *
     * @return array 톤 목록
     */
    public static function getSupportedTones(): array {
        return self::get('response_generator.supported_tones', []);
    }

    /**
     * 우선순위 레벨 가져오기
     *
     * @param string $level 레벨명
     * @return int 우선순위 값
     */
    public static function getPriorityLevel(string $level): int {
        $levels = self::get('message_bus.priority_levels', []);
        return $levels[strtolower($level)] ?? 3;
    }

    /**
     * 에이전트 ID 목록
     *
     * @return array 에이전트 ID => 이름 매핑
     */
    public static function getAgentList(): array {
        return [
            'agent01' => '온보딩',
            'agent02' => '평가',
            'agent03' => '커리큘럼',
            'agent04' => '학습경로',
            'agent05' => '콘텐츠',
            'agent06' => '퀴즈',
            'agent07' => '피드백',
            'agent08' => '동기부여',
            'agent09' => '분석',
            'agent10' => '학부모',
            'agent11' => '문제노트',
            'agent12' => '멘토',
            'agent13' => '협업',
            'agent14' => '게이미피케이션',
            'agent15' => '접근성',
            'agent16' => '알림',
            'agent17' => '일정',
            'agent18' => '리소스',
            'agent19' => '평가도구',
            'agent20' => '리포트',
            'agent21' => '통합'
        ];
    }

    /**
     * 설정 초기화
     */
    public static function reset(): void {
        self::$config = null;
        self::$agentOverrides = [];
    }

    /**
     * 중첩 값 가져오기
     */
    private static function getNestedValue(array $data, string $key, $default = null) {
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 중첩 값 설정
     */
    private static function setNestedValue(array &$data, string $key, $value): void {
        $keys = explode('.', $key);
        $current = &$data;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }
}

/*
 * 사용 예시:
 * 
 * use AugmentedTeacher\PersonaEngine\Config\PersonaEngineConfig;
 * 
 * // 설정값 가져오기
 * $ttl = PersonaEngineConfig::get('cache.ttl'); // 300
 * $debug = PersonaEngineConfig::isDebugMode(); // false
 * 
 * // 런타임 설정 변경
 * PersonaEngineConfig::set('debug_mode', true);
 * 
 * // 에이전트별 오버라이드
 * PersonaEngineConfig::setAgentOverrides('agent11', [
 *     'response_generator.default_tone' => 'Encouraging'
 * ]);
 * $tone = PersonaEngineConfig::getForAgent('agent11', 'response_generator.default_tone');
 */
