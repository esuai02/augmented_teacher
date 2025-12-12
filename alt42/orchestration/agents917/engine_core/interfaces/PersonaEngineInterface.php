<?php
/**
 * PersonaEngineInterface.php
 *
 * 모든 에이전트 Persona Engine이 구현해야 하는 핵심 인터페이스
 * 21개 에이전트(agent01~agent21)의 통일된 동작을 보장
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 사용법:
 * - 각 에이전트의 PersonaEngine 클래스에서 이 인터페이스를 구현
 * - AbstractPersonaEngine을 상속하면 자동으로 구현됨
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/interfaces/PersonaEngineInterface.php
 */

defined('MOODLE_INTERNAL') || die();

interface PersonaEngineInterface
{
    /**
     * 에이전트 번호 반환 (1-21)
     *
     * @return int 에이전트 번호
     */
    public function getAgentNumber(): int;

    /**
     * 에이전트 이름 반환
     *
     * @return string 에이전트 이름 (예: 'onboarding', 'learning_emotion')
     */
    public function getAgentName(): string;

    /**
     * 사용자의 현재 페르소나 식별
     *
     * @param int   $userId     사용자 ID
     * @param array $contextData 컨텍스트 데이터 (선택적)
     * @return array [
     *     'persona_code' => string,    // 페르소나 코드
     *     'confidence'   => float,     // 신뢰도 (0.0 ~ 1.0)
     *     'metadata'     => array      // 추가 메타데이터
     * ]
     */
    public function identifyPersona(int $userId, array $contextData = []): array;

    /**
     * 페르소나 기반 응답 생성
     *
     * @param int    $userId      사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param string $userMessage 사용자 메시지
     * @param array  $options     추가 옵션
     * @return array [
     *     'response'    => string,  // 생성된 응답
     *     'template_id' => string,  // 사용된 템플릿 ID
     *     'actions'     => array    // 실행할 액션 목록
     * ]
     */
    public function generateResponse(int $userId, string $personaCode, string $userMessage, array $options = []): array;

    /**
     * 페르소나 전환 처리
     *
     * @param int    $userId      사용자 ID
     * @param string $fromPersona 현재 페르소나
     * @param string $toPersona   대상 페르소나
     * @param array  $triggerData 전환 트리거 데이터
     * @return bool 전환 성공 여부
     */
    public function handleTransition(int $userId, string $fromPersona, string $toPersona, array $triggerData = []): bool;

    /**
     * 규칙 파일(rules.yaml) 로드 및 파싱
     *
     * @return array 파싱된 규칙 배열
     */
    public function loadRules(): array;

    /**
     * 조건 평가
     *
     * @param array $conditions 평가할 조건 배열
     * @param array $context    컨텍스트 데이터
     * @return bool 조건 충족 여부
     */
    public function evaluateConditions(array $conditions, array $context): bool;

    /**
     * 액션 실행
     *
     * @param array $actions 실행할 액션 배열
     * @param int   $userId  사용자 ID
     * @param array $context 컨텍스트 데이터
     * @return array 실행 결과
     */
    public function executeActions(array $actions, int $userId, array $context): array;

    /**
     * 페르소나 상태 저장
     *
     * @param int    $userId      사용자 ID
     * @param string $personaCode 페르소나 코드
     * @param float  $confidence  신뢰도
     * @param array  $contextData 컨텍스트 데이터
     * @return bool 저장 성공 여부
     */
    public function savePersonaState(int $userId, string $personaCode, float $confidence, array $contextData = []): bool;

    /**
     * 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 페르소나 상태 또는 null
     */
    public function getPersonaState(int $userId): ?array;

    /**
     * 에이전트 초기화
     *
     * @param array $config 초기화 설정
     * @return bool 초기화 성공 여부
     */
    public function initialize(array $config = []): bool;

    /**
     * 에이전트 상태 검증
     *
     * @return array [
     *     'healthy'  => bool,    // 정상 여부
     *     'details'  => array,   // 세부 정보
     *     'warnings' => array    // 경고 메시지
     * ]
     */
    public function healthCheck(): array;
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 이 인터페이스를 구현하는 클래스가 사용하는 공통 DB 테이블:
 *
 * 테이블명: mdl_at_agent_persona_state
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key, Auto Increment        │
 * │ user_id         │ BIGINT           │ 사용자 ID                           │
 * │ nagent          │ TINYINT          │ 에이전트 번호 (1-21)                 │
 * │ persona_code    │ VARCHAR(20)      │ 페르소나 코드                        │
 * │ confidence      │ DECIMAL(3,2)     │ 신뢰도 (0.00-1.00)                  │
 * │ context_data    │ JSON             │ 컨텍스트 데이터                      │
 * │ timecreated     │ INT              │ 생성 시간 (Unix timestamp)          │
 * │ timemodified    │ INT              │ 수정 시간 (Unix timestamp)          │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 테이블명: mdl_at_agent_transitions
 * ┌─────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field           │ Type             │ Description                        │
 * ├─────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id              │ BIGINT           │ Primary Key, Auto Increment        │
 * │ user_id         │ BIGINT           │ 사용자 ID                           │
 * │ nagent          │ TINYINT          │ 에이전트 번호 (1-21)                 │
 * │ from_persona    │ VARCHAR(20)      │ 이전 페르소나                        │
 * │ to_persona      │ VARCHAR(20)      │ 새 페르소나                          │
 * │ trigger_type    │ VARCHAR(50)      │ 전환 트리거 유형                     │
 * │ confidence      │ DECIMAL(3,2)     │ 전환 신뢰도                          │
 * │ context_snapshot│ JSON             │ 전환 시점 컨텍스트 스냅샷            │
 * │ timecreated     │ INT              │ 전환 시간 (Unix timestamp)          │
 * └─────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
