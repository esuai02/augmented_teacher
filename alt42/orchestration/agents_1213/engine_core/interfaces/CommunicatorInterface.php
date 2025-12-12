<?php
/**
 * CommunicatorInterface.php
 *
 * 에이전트 간 통신 인터페이스
 * DB 기반 Message Queue를 통한 에이전트 상호작용 표준 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/interfaces/CommunicatorInterface.php
 */

defined('MOODLE_INTERNAL') || die();

interface CommunicatorInterface
{
    /**
     * 에이전트 번호 반환
     *
     * @return int 에이전트 번호 (1-21)
     */
    public function getAgentNumber(): int;

    /**
     * 특정 에이전트에게 메시지 전송
     *
     * @param int    $toAgent     수신 에이전트 번호 (1-21)
     * @param string $messageType 메시지 유형
     * @param array  $payload     메시지 페이로드
     * @param int    $priority    우선순위 (1=최고, 10=최저, 기본=5)
     * @param int    $ttl         메시지 유효 시간 (초, 기본=3600)
     * @return array [
     *     'success'    => bool,
     *     'message_id' => string,
     *     'error'      => string|null
     * ]
     */
    public function send(int $toAgent, string $messageType, array $payload, int $priority = 5, int $ttl = 3600): array;

    /**
     * 모든 에이전트에게 브로드캐스트 메시지 전송
     *
     * @param string    $messageType 메시지 유형
     * @param array     $payload     메시지 페이로드
     * @param array     $exclude     제외할 에이전트 번호 배열
     * @param int       $priority    우선순위
     * @return array [
     *     'success'    => bool,
     *     'sent_count' => int,
     *     'message_ids'=> array,
     *     'errors'     => array
     * ]
     */
    public function broadcast(string $messageType, array $payload, array $exclude = [], int $priority = 5): array;

    /**
     * 대기 중인 메시지 수신
     *
     * @param string|null $messageType 메시지 유형 필터 (null이면 전체)
     * @param int         $limit       최대 수신 개수
     * @param bool        $markRead    수신 후 처리 완료로 표시할지 여부
     * @return array 메시지 배열 [
     *     [
     *         'message_id'   => string,
     *         'from_agent'   => int,
     *         'message_type' => string,
     *         'payload'      => array,
     *         'priority'     => int,
     *         'created_at'   => int
     *     ], ...
     * ]
     */
    public function receive(?string $messageType = null, int $limit = 50, bool $markRead = true): array;

    /**
     * 메시지 처리 완료 표시
     *
     * @param string $messageId 메시지 ID
     * @param bool   $success   처리 성공 여부
     * @param string $result    처리 결과 (선택적)
     * @return bool 표시 성공 여부
     */
    public function acknowledge(string $messageId, bool $success = true, string $result = ''): bool;

    /**
     * 긴급 알림 전송
     * 우선순위 1로 즉시 처리되어야 하는 메시지
     *
     * @param int    $toAgent   수신 에이전트 번호
     * @param string $alertType 알림 유형
     * @param array  $data      알림 데이터
     * @return array 전송 결과
     */
    public function sendUrgentAlert(int $toAgent, string $alertType, array $data): array;

    /**
     * 특정 에이전트에게 데이터 요청
     *
     * @param int    $toAgent     요청 대상 에이전트 번호
     * @param string $requestType 요청 유형
     * @param array  $params      요청 파라미터
     * @param int    $timeout     응답 대기 시간 (초)
     * @return array|null 응답 데이터 또는 null (타임아웃)
     */
    public function request(int $toAgent, string $requestType, array $params, int $timeout = 30): ?array;

    /**
     * 요청에 대한 응답 전송
     *
     * @param string $requestId 원본 요청 ID
     * @param array  $response  응답 데이터
     * @return bool 전송 성공 여부
     */
    public function respond(string $requestId, array $response): bool;

    /**
     * 메시지 큐 상태 조회
     *
     * @return array [
     *     'pending'    => int,    // 대기 중 메시지 수
     *     'processing' => int,    // 처리 중 메시지 수
     *     'failed'     => int,    // 실패 메시지 수
     *     'expired'    => int,    // 만료 메시지 수
     *     'oldest'     => int     // 가장 오래된 메시지 시간
     * ]
     */
    public function getQueueStatus(): array;

    /**
     * 만료된 메시지 정리
     *
     * @return int 정리된 메시지 수
     */
    public function cleanupExpired(): int;

    /**
     * 에이전트 구독 등록
     * 특정 메시지 유형에 대해 자동 수신 설정
     *
     * @param array    $messageTypes 구독할 메시지 유형들
     * @param callable $handler      메시지 핸들러 콜백
     * @return bool 등록 성공 여부
     */
    public function subscribe(array $messageTypes, callable $handler): bool;

    /**
     * 구독 해제
     *
     * @param array $messageTypes 해제할 메시지 유형들
     * @return bool 해제 성공 여부
     */
    public function unsubscribe(array $messageTypes): bool;

    /**
     * 통신 로그 조회
     *
     * @param array $filters 필터 조건
     * @param int   $limit   조회 제한 수
     * @return array 통신 로그
     */
    public function getCommunicationLog(array $filters = [], int $limit = 100): array;

    /**
     * 에이전트 가용성 확인
     *
     * @param int $agentNumber 확인할 에이전트 번호
     * @return bool 가용 여부
     */
    public function isAgentAvailable(int $agentNumber): bool;
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 에이전트 간 통신에 사용되는 메시지 큐 테이블:
 *
 * 테이블명: mdl_at_agent_messages
 * ┌───────────────────┬──────────────────┬────────────────────────────────────┐
 * │ Field             │ Type             │ Description                        │
 * ├───────────────────┼──────────────────┼────────────────────────────────────┤
 * │ id                │ BIGINT           │ Primary Key, Auto Increment        │
 * │ message_id        │ VARCHAR(64)      │ 고유 메시지 ID (UUID)               │
 * │ from_agent        │ TINYINT          │ 발신 에이전트 번호 (1-21)            │
 * │ to_agent          │ TINYINT          │ 수신 에이전트 번호 (0=브로드캐스트)   │
 * │ message_type      │ VARCHAR(50)      │ 메시지 유형                          │
 * │ priority          │ TINYINT          │ 우선순위 (1-10, 기본 5)              │
 * │ payload           │ JSON             │ 메시지 페이로드                       │
 * │ status            │ ENUM             │ pending/processing/processed/failed/expired │
 * │ checksum          │ VARCHAR(64)      │ 페이로드 체크섬 (SHA256)             │
 * │ retry_count       │ TINYINT          │ 재시도 횟수                           │
 * │ expires_at        │ DATETIME         │ 만료 시간                             │
 * │ timecreated       │ INT              │ 생성 시간 (Unix timestamp)           │
 * │ timeprocessed     │ INT              │ 처리 시간 (Unix timestamp)           │
 * └───────────────────┴──────────────────┴────────────────────────────────────┘
 *
 * 인덱스:
 * - idx_to_status: (to_agent, status) - 수신자별 대기 메시지 조회
 * - idx_priority: (priority, timecreated) - 우선순위 기반 정렬
 * - uk_message_id: UNIQUE (message_id) - 메시지 ID 중복 방지
 *
 * 메시지 유형 예시:
 * - emotion_alert: 감정 변화 알림 (Agent05 → Agent08)
 * - dropout_risk: 이탈 위험 알림 (Agent13 → Broadcast)
 * - calmness_trigger: 평온화 모드 요청 (Agent05 → Agent08)
 * - intervention_ready: 개입 준비 완료 (Agent20 → Agent21)
 * - diagnosis_complete: 진단 완료 알림 (Agent03 → Agent09)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
