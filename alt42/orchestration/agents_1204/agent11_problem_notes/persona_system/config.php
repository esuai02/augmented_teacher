<?php
/**
 * Agent11 PersonaSystem 로컬 설정
 *
 * 전역 설정(persona_engine.config.php)을 오버라이드하는 에이전트별 설정
 * 문제노트 에이전트에 최적화된 설정값
 *
 * @package AugmentedTeacher\Agent11\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\Agent11\PersonaSystem;

// 에이전트 식별
define('AGENT11_ID', 'agent11');
define('AGENT11_NAME', '문제노트');
define('AGENT11_VERSION', '1.0.0');

/**
 * Agent11Config - 문제노트 에이전트 설정
 */
class Agent11Config {

    /** @var array 에이전트별 설정 */
    private static $config = [
        // 페르소나 설정
        'personas' => [
            'default' => 'AnalyticalHelper',
            'available' => [
                'AnalyticalHelper',
                'EncouragingCoach',
                'PatientGuide',
                'PracticeLeader'
            ],
            'transition' => [
                'min_interval' => 300,      // 최소 전환 간격 (초)
                'emotion_override' => true,  // 감정 기반 즉시 전환 허용
                'session_memory' => true     // 세션 내 전환 기록 유지
            ]
        ],

        // 응답 설정
        'response' => [
            'default_tone' => 'Professional',
            'max_length' => 500,            // 최대 응답 길이 (문자)
            'include_encouragement' => true, // 격려 메시지 포함
            'show_error_cause' => true,      // 오류 원인 표시
            'suggest_practice' => true       // 연습 문제 제안
        ],

        // 분석 설정
        'analysis' => [
            'error_classification' => [
                'concept_confusion' => '개념 혼동',
                'calculation_mistake' => '계산 실수',
                'reading_error' => '문제 읽기 오류',
                'process_error' => '풀이 과정 오류',
                'careless_mistake' => '부주의 실수'
            ],
            'severity_levels' => [
                'critical' => 1,    // 즉시 개입 필요
                'high' => 2,        // 주의 필요
                'medium' => 3,      // 일반적
                'low' => 4          // 경미
            ],
            'track_patterns' => true,       // 오류 패턴 추적
            'pattern_threshold' => 3        // 패턴 인식 최소 횟수
        ],

        // 캐시 설정 (전역 오버라이드)
        'cache' => [
            'state_ttl' => 120,             // 상태 캐시 2분 (빈번한 갱신)
            'rules_ttl' => 1800,            // 규칙 캐시 30분
            'analysis_ttl' => 300           // 분석 결과 캐시 5분
        ],

        // 메시지 설정
        'messaging' => [
            'priority_for_emotion' => 2,    // 감정 관련 메시지 우선순위
            'broadcast_emotions' => true,   // 감정 상태 브로드캐스트
            'listen_from' => [              // 메시지 수신 대상 에이전트
                'agent07' => 'feedback',    // 피드백 에이전트
                'agent08' => 'motivation',  // 동기부여 에이전트
                'agent09' => 'analytics'    // 분석 에이전트
            ]
        ],

        // 통합 설정
        'integration' => [
            'quiz_agent' => 'agent06',      // 퀴즈 에이전트
            'feedback_agent' => 'agent07',  // 피드백 에이전트
            'parent_agent' => 'agent10',    // 학부모 에이전트
            'report_agent' => 'agent20'     // 리포트 에이전트
        ],

        // 디버그 설정
        'debug' => [
            'enabled' => false,
            'log_transitions' => true,
            'log_analyses' => true,
            'log_messages' => false
        ]
    ];

    /**
     * 설정값 가져오기
     *
     * @param string $key 설정 키 (점 표기법 지원)
     * @param mixed $default 기본값
     * @return mixed 설정값
     */
    public static function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;

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
     * 런타임 설정 변경
     *
     * @param string $key 설정 키
     * @param mixed $value 값
     */
    public static function set(string $key, $value): void {
        $keys = explode('.', $key);
        $current = &self::$config;

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

    /**
     * 사용 가능한 페르소나 목록
     *
     * @return array 페르소나 목록
     */
    public static function getAvailablePersonas(): array {
        return self::get('personas.available', []);
    }

    /**
     * 오류 분류 목록
     *
     * @return array 오류 분류
     */
    public static function getErrorClassifications(): array {
        return self::get('analysis.error_classification', []);
    }

    /**
     * 디버그 모드 확인
     *
     * @return bool 디버그 모드 여부
     */
    public static function isDebugMode(): bool {
        return self::get('debug.enabled', false);
    }

    /**
     * 통합 에이전트 ID 조회
     *
     * @param string $role 역할 (quiz, feedback, parent, report)
     * @return string|null 에이전트 ID
     */
    public static function getIntegrationAgent(string $role): ?string {
        return self::get("integration.{$role}_agent");
    }

    /**
     * 전체 설정 반환 (디버그용)
     *
     * @return array 전체 설정
     */
    public static function getAll(): array {
        return self::$config;
    }
}

/*
 * 사용 예시:
 *
 * use AugmentedTeacher\Agent11\PersonaSystem\Agent11Config;
 *
 * // 기본 페르소나 조회
 * $default = Agent11Config::get('personas.default'); // 'AnalyticalHelper'
 *
 * // 오류 분류 조회
 * $errors = Agent11Config::getErrorClassifications();
 *
 * // 캐시 TTL 조회
 * $ttl = Agent11Config::get('cache.state_ttl'); // 120
 *
 * // 런타임 설정 변경
 * Agent11Config::set('debug.enabled', true);
 *
 * // 통합 에이전트 조회
 * $quizAgent = Agent11Config::getIntegrationAgent('quiz'); // 'agent06'
 */

