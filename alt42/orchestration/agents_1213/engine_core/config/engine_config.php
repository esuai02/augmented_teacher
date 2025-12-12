<?php
/**
 * engine_config.php
 *
 * 에이전트 엔진 코어 전역 설정
 * 22개 에이전트 통합 시스템의 공통 설정 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/config/engine_config.php
 */

defined('MOODLE_INTERNAL') || die();

/**
 * 에이전트 엔진 코어 설정 상수 및 배열
 */

// =============================================================================
// 버전 정보
// =============================================================================
define('ENGINE_CORE_VERSION', '1.0.0');
define('ENGINE_CORE_RELEASE_DATE', '2025-12-03');

// =============================================================================
// 에이전트 정의 (1-22)
// =============================================================================
define('AGENT_CONFIG', [
    1  => ['name' => 'onboarding',              'kr_name' => '온보딩',              'category' => 'foundation'],
    2  => ['name' => 'exam_schedule',           'kr_name' => '시험 일정',           'category' => 'planning'],
    3  => ['name' => 'goals_analysis',          'kr_name' => '목표 분석',           'category' => 'diagnosis'],
    4  => ['name' => 'inspect_weakpoints',      'kr_name' => '약점 검사',           'category' => 'diagnosis'],
    5  => ['name' => 'learning_emotion',        'kr_name' => '학습 감정',           'category' => 'emotion'],
    6  => ['name' => 'teacher_feedback',        'kr_name' => '교사 피드백',         'category' => 'interaction'],
    7  => ['name' => 'interaction_targeting',   'kr_name' => '상호작용 타겟팅',     'category' => 'interaction'],
    8  => ['name' => 'calmness',                'kr_name' => '평온도',              'category' => 'emotion'],
    9  => ['name' => 'learning_management',     'kr_name' => '학습 관리',           'category' => 'management'],
    10 => ['name' => 'concept_notes',           'kr_name' => '개념 노트',           'category' => 'content'],
    11 => ['name' => 'problem_notes',           'kr_name' => '문제 노트',           'category' => 'content'],
    12 => ['name' => 'rest_routine',            'kr_name' => '휴식 루틴',           'category' => 'wellness'],
    13 => ['name' => 'learning_dropout',        'kr_name' => '학습 이탈',           'category' => 'risk'],
    14 => ['name' => 'current_position',         'kr_name' => '현재 위치',           'category' => 'diagnosis'],
    15 => ['name' => 'problem_redefinition',    'kr_name' => '문제 재정의',         'category' => 'content'],
    16 => ['name' => 'interaction_preparation', 'kr_name' => '상호작용 준비',       'category' => 'interaction'],
    17 => ['name' => 'remaining_activities',    'kr_name' => '남은 활동',           'category' => 'planning'],
    18 => ['name' => 'signature_routine',       'kr_name' => '시그니처 루틴',       'category' => 'routine'],
    19 => ['name' => 'interaction_content',     'kr_name' => '상호작용 콘텐츠',     'category' => 'interaction'],
    20 => ['name' => 'intervention_preparation','kr_name' => '개입 준비',           'category' => 'intervention'],
    21 => ['name' => 'intervention_execution',  'kr_name' => '개입 실행',           'category' => 'intervention'],
    22 => ['name' => 'module_improvement',      'kr_name' => '모듈 개선',           'category' => 'system'],
]);

// 카테고리 정의
define('AGENT_CATEGORIES', [
    'foundation'   => ['name' => '기반',       'agents' => [1]],
    'planning'     => ['name' => '계획',       'agents' => [2, 17]],
    'diagnosis'    => ['name' => '진단',       'agents' => [3, 4, 14]],
    'emotion'      => ['name' => '감정/상태',  'agents' => [5, 8]],
    'interaction'  => ['name' => '상호작용',   'agents' => [6, 7, 16, 19]],
    'management'   => ['name' => '관리',       'agents' => [9]],
    'content'      => ['name' => '콘텐츠',     'agents' => [10, 11, 15]],
    'wellness'     => ['name' => '웰니스',     'agents' => [12]],
    'risk'         => ['name' => '위험관리',   'agents' => [13]],
    'routine'      => ['name' => '루틴',       'agents' => [18]],
    'intervention' => ['name' => '개입',       'agents' => [20, 21]],
    'system'       => ['name' => '시스템',     'agents' => [22]],
]);

// =============================================================================
// 에이전트 간 협력 관계 정의
// =============================================================================
define('AGENT_COLLABORATION', [
    // Agent05(학습 감정) → Agent08(평온도): 좌절 감지 시 평온화 모드 활성화
    5 => [
        'triggers' => [
            ['target' => 8, 'on' => 'frustration_detected', 'action' => 'activate_calmness'],
            ['target' => 9, 'on' => 'emotion_summary', 'action' => 'update_learning_plan'],
        ],
        'listens_to' => [9, 13],
    ],
    // Agent08(평온도) ← Agent05 수신
    8 => [
        'triggers' => [],
        'listens_to' => [5, 12],
    ],
    // Agent09(학습 관리) → 전체 브로드캐스트 가능
    9 => [
        'triggers' => [
            ['target' => 0, 'on' => 'dropout_risk_detected', 'action' => 'broadcast_alert'],
        ],
        'listens_to' => [3, 5, 13],
    ],
    // Agent13(학습 이탈) → 전체 브로드캐스트
    13 => [
        'triggers' => [
            ['target' => 0, 'on' => 'high_risk_detected', 'action' => 'emergency_broadcast'],
        ],
        'listens_to' => [5, 9],
    ],
    // Agent20(개입 준비) → Agent21(개입 실행)
    20 => [
        'triggers' => [
            ['target' => 21, 'on' => 'preparation_complete', 'action' => 'execute_intervention'],
        ],
        'listens_to' => [13, 9],
    ],
    // Agent21(개입 실행) ← Agent20 수신
    21 => [
        'triggers' => [],
        'listens_to' => [20],
    ],
    // Agent03(목표 분석) → Agent09(학습 관리)
    3 => [
        'triggers' => [
            ['target' => 9, 'on' => 'diagnosis_complete', 'action' => 'create_learning_plan'],
        ],
        'listens_to' => [],
    ],
]);

// =============================================================================
// 메시지 큐 설정
// =============================================================================
define('MESSAGE_QUEUE_CONFIG', [
    'default_priority'  => 5,           // 기본 우선순위 (1-10)
    'default_ttl'       => 3600,        // 기본 메시지 유효 시간 (초)
    'max_retry_count'   => 3,           // 최대 재시도 횟수
    'cleanup_interval'  => 300,         // 만료 메시지 정리 주기 (초)
    'batch_size'        => 50,          // 한 번에 처리할 메시지 수
    'urgent_ttl'        => 300,         // 긴급 메시지 TTL (초)
]);

// 메시지 우선순위 정의
define('MESSAGE_PRIORITY', [
    'CRITICAL' => 1,    // 즉시 처리 (시스템 긴급 상황)
    'URGENT'   => 2,    // 긴급 (사용자 위험 상황)
    'HIGH'     => 3,    // 높음 (중요 알림)
    'NORMAL'   => 5,    // 보통 (일반 통신)
    'LOW'      => 7,    // 낮음 (배치 처리 가능)
    'DEFERRED' => 10,   // 지연 가능 (로그성 메시지)
]);

// 메시지 유형 정의
define('MESSAGE_TYPES', [
    // 감정 관련
    'emotion_alert'        => ['category' => 'emotion', 'default_priority' => 3],
    'frustration_detected' => ['category' => 'emotion', 'default_priority' => 2],
    'calmness_trigger'     => ['category' => 'emotion', 'default_priority' => 3],

    // 학습 관련
    'dropout_risk'         => ['category' => 'risk',    'default_priority' => 1],
    'learning_update'      => ['category' => 'learning','default_priority' => 5],
    'diagnosis_complete'   => ['category' => 'diagnosis','default_priority' => 4],

    // 개입 관련
    'intervention_ready'   => ['category' => 'intervention', 'default_priority' => 3],
    'intervention_result'  => ['category' => 'intervention', 'default_priority' => 4],

    // 시스템 관련
    'health_check'         => ['category' => 'system', 'default_priority' => 7],
    'status_update'        => ['category' => 'system', 'default_priority' => 8],
]);

// =============================================================================
// 페르소나 엔진 설정
// =============================================================================
define('PERSONA_ENGINE_CONFIG', [
    'cache_ttl'              => 300,     // 규칙 캐시 TTL (초)
    'min_confidence'         => 0.5,     // 최소 신뢰도 임계값
    'transition_cooldown'    => 60,      // 페르소나 전환 쿨다운 (초)
    'max_history_records'    => 100,     // 최대 이력 보관 수
    'default_persona'        => 'neutral', // 기본 페르소나
    'response_timeout'       => 30,      // 응답 생성 타임아웃 (초)
]);

// =============================================================================
// 테스트 설정
// =============================================================================
define('TEST_CONFIG', [
    'response_time_limit'    => 500,     // 응답 시간 제한 (ms)
    'min_coverage'           => 80,      // 최소 테스트 커버리지 (%)
    'parallel_tests'         => 5,       // 병렬 테스트 수
    'test_user_id_prefix'    => 99900,   // 테스트 사용자 ID 접두사
]);

// =============================================================================
// DB 테이블 정의
// =============================================================================
define('DB_TABLES', [
    // 공통 테이블
    'common' => [
        'messages'       => 'mdl_at_agent_messages',
        'persona_state'  => 'mdl_at_agent_persona_state',
        'transitions'    => 'mdl_at_agent_transitions',
        'agent_logs'     => 'mdl_at_agent_logs',
    ],
    // 에이전트별 테이블 접두사
    'agent_prefix' => 'mdl_at_agent',  // mdl_at_agent{XX}_*
]);

// =============================================================================
// 로깅 설정
// =============================================================================
define('LOGGING_CONFIG', [
    'enabled'       => true,
    'level'         => 'INFO',  // DEBUG, INFO, WARNING, ERROR, CRITICAL
    'max_file_size' => 10485760, // 10MB
    'retention_days'=> 30,
    'log_path'      => '/var/log/augmented_teacher/agents/',
]);

// =============================================================================
// 헬퍼 함수
// =============================================================================

/**
 * 에이전트 번호로 정보 조회
 *
 * @param int $agentNumber 에이전트 번호 (1-22)
 * @return array|null 에이전트 정보
 */
function get_agent_info(int $agentNumber): ?array
{
    return AGENT_CONFIG[$agentNumber] ?? null;
}

/**
 * 에이전트 이름으로 번호 조회
 *
 * @param string $agentName 에이전트 이름
 * @return int|null 에이전트 번호
 */
function get_agent_number_by_name(string $agentName): ?int
{
    foreach (AGENT_CONFIG as $num => $config) {
        if ($config['name'] === $agentName) {
            return $num;
        }
    }
    return null;
}

/**
 * 에이전트별 DB 테이블명 생성
 *
 * @param int    $agentNumber 에이전트 번호
 * @param string $tableSuffix 테이블 접미사 (예: 'emotion_log')
 * @return string 완전한 테이블명
 */
function get_agent_table_name(int $agentNumber, string $tableSuffix): string
{
    return sprintf('%s%02d_%s', DB_TABLES['agent_prefix'], $agentNumber, $tableSuffix);
}

/**
 * 협력 관계 에이전트 조회
 *
 * @param int $agentNumber 에이전트 번호
 * @return array ['sends_to' => [], 'receives_from' => []]
 */
function get_collaboration_map(int $agentNumber): array
{
    $collaboration = AGENT_COLLABORATION[$agentNumber] ?? ['triggers' => [], 'listens_to' => []];

    $sends_to = array_map(function($trigger) {
        return $trigger['target'];
    }, $collaboration['triggers']);

    return [
        'sends_to'       => array_unique($sends_to),
        'receives_from'  => $collaboration['listens_to'],
    ];
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 이 설정 파일에서 참조하는 모든 DB 테이블 목록:
 *
 * 공통 테이블:
 * - mdl_at_agent_messages      : 에이전트 간 메시지 큐
 * - mdl_at_agent_persona_state : 사용자별 페르소나 상태
 * - mdl_at_agent_transitions   : 페르소나 전환 이력
 * - mdl_at_agent_logs          : 에이전트 활동 로그
 *
 * 에이전트별 테이블 패턴: mdl_at_agent{XX}_*
 * (각 에이전트의 전용 데이터 저장)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
