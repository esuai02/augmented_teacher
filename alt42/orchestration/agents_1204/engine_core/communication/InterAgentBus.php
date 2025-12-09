<?php
/**
 * InterAgentBus.php
 *
 * 에이전트 간 중앙 통신 버스 (Singleton)
 * 21개 에이전트 간의 메시지 발행, 구독, 라우팅을 총괄 관리
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/communication/InterAgentBus.php
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/AgentMessage.php');
require_once(__DIR__ . '/MessageQueue.php');
require_once(__DIR__ . '/../config/engine_config.php');

/**
 * Class InterAgentBus
 *
 * Singleton 패턴으로 구현된 중앙 메시지 버스
 * 모든 에이전트 간 통신의 중심점 역할 수행
 *
 * 주요 기능:
 * - 메시지 발행 (publish) / 구독 (subscribe)
 * - 브로드캐스트 메시지 전송
 * - 요청-응답 패턴 지원
 * - 에이전트 가용성 모니터링
 * - 메시지 라우팅 및 필터링
 */
class InterAgentBus
{
    /** @var InterAgentBus|null Singleton 인스턴스 */
    private static $instance = null;

    /** @var \moodle_database DB 인스턴스 */
    private $db;

    /** @var array 에이전트별 MessageQueue 캐시 */
    private $queues = [];

    /** @var array 구독 핸들러 등록 */
    private $subscriptions = [];

    /** @var array 에이전트 가용성 상태 */
    private $agentStatus = [];

    /** @var array 대기 중인 요청-응답 */
    private $pendingRequests = [];

    /** @var string 메시지 테이블명 */
    private $tableName;

    /** @var string 로그 테이블명 */
    private $logTableName;

    /**
     * Private 생성자 (Singleton)
     */
    private function __construct()
    {
        global $DB;
        $this->db = $DB;
        $this->tableName = DB_TABLES['common']['messages'];
        $this->logTableName = DB_TABLES['common']['agent_logs'];
    }

    /**
     * Clone 방지 (Singleton)
     */
    private function __clone() {}

    /**
     * Singleton 인스턴스 반환
     *
     * @return InterAgentBus
     */
    public static function getInstance(): InterAgentBus
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 인스턴스 초기화 (테스트용)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    // =========================================================================
    // 메시지 발행 (Publish)
    // =========================================================================

    /**
     * 특정 에이전트에게 메시지 전송
     *
     * @param AgentMessage $message 메시지 객체
     * @return array ['success' => bool, 'message_id' => string, 'error' => string|null]
     */
    public function publish(AgentMessage $message): array
    {
        try {
            // 메시지 유효성 검증
            if ($message->isExpired()) {
                return [
                    'success'    => false,
                    'message_id' => $message->getMessageId(),
                    'error'      => '[InterAgentBus.php:' . __LINE__ . '] 메시지가 이미 만료됨'
                ];
            }

            // 협력 관계 검증 (선택적)
            if (!$this->validateCollaboration($message)) {
                // 협력 관계가 아니어도 전송은 허용하되 경고 로그
                $this->logWarning('publish', sprintf(
                    'Agent%02d → Agent%02d 간 정의된 협력 관계 없음',
                    $message->getFromAgent(),
                    $message->getToAgent()
                ));
            }

            // DB에 직접 삽입
            $record = (object) $message->toDbArray();
            $this->db->insert_record($this->tableName, $record);

            // 활동 로그
            $this->logActivity('publish', $message);

            // 구독 핸들러 트리거 (비동기 처리용)
            $this->triggerSubscriptions($message);

            return [
                'success'    => true,
                'message_id' => $message->getMessageId(),
                'error'      => null
            ];

        } catch (Exception $e) {
            return [
                'success'    => false,
                'message_id' => $message->getMessageId(),
                'error'      => '[InterAgentBus.php:' . __LINE__ . '] 발행 실패: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 간편 메시지 전송
     *
     * @param int    $fromAgent   발신 에이전트
     * @param int    $toAgent     수신 에이전트
     * @param string $messageType 메시지 유형
     * @param array  $payload     페이로드
     * @param int    $priority    우선순위 (기본값 5)
     * @param int    $ttl         유효 시간 (초)
     * @return array
     */
    public function send(
        int $fromAgent,
        int $toAgent,
        string $messageType,
        array $payload,
        int $priority = 5,
        int $ttl = 3600
    ): array {
        $message = new AgentMessage([
            'from_agent'   => $fromAgent,
            'to_agent'     => $toAgent,
            'message_type' => $messageType,
            'payload'      => $payload,
            'priority'     => $priority,
            'ttl'          => $ttl
        ]);

        return $this->publish($message);
    }

    /**
     * 브로드캐스트 메시지 전송 (모든 에이전트에게)
     *
     * @param int    $fromAgent   발신 에이전트
     * @param string $messageType 메시지 유형
     * @param array  $payload     페이로드
     * @param array  $exclude     제외할 에이전트 번호
     * @param int    $priority    우선순위
     * @return array ['success' => bool, 'sent_count' => int, 'message_ids' => array, 'errors' => array]
     */
    public function broadcast(
        int $fromAgent,
        string $messageType,
        array $payload,
        array $exclude = [],
        int $priority = 5
    ): array {
        $results = [
            'success'     => true,
            'sent_count'  => 0,
            'message_ids' => [],
            'errors'      => []
        ];

        // 자기 자신과 제외 목록에서 발신자 제거
        $exclude[] = $fromAgent;
        $exclude = array_unique($exclude);

        // 브로드캐스트 메시지 (to_agent = 0)로 하나만 생성
        $message = new AgentMessage([
            'from_agent'   => $fromAgent,
            'to_agent'     => 0,  // 브로드캐스트
            'message_type' => $messageType,
            'payload'      => array_merge($payload, ['_exclude' => $exclude]),
            'priority'     => $priority
        ]);

        $result = $this->publish($message);

        if ($result['success']) {
            $results['sent_count'] = 21 - count($exclude);
            $results['message_ids'][] = $result['message_id'];
        } else {
            $results['success'] = false;
            $results['errors'][] = $result['error'];
        }

        return $results;
    }

    /**
     * 긴급 알림 전송
     *
     * @param int    $fromAgent 발신 에이전트
     * @param int    $toAgent   수신 에이전트
     * @param string $alertType 알림 유형
     * @param array  $data      알림 데이터
     * @return array
     */
    public function sendUrgentAlert(int $fromAgent, int $toAgent, string $alertType, array $data): array
    {
        return $this->send(
            $fromAgent,
            $toAgent,
            $alertType,
            $data,
            MESSAGE_PRIORITY['CRITICAL'],  // 최고 우선순위
            MESSAGE_QUEUE_CONFIG['urgent_ttl']  // 짧은 TTL
        );
    }

    // =========================================================================
    // 메시지 수신 (Receive)
    // =========================================================================

    /**
     * 특정 에이전트의 대기 메시지 수신
     *
     * @param int         $agentNumber  에이전트 번호
     * @param string|null $messageType  메시지 유형 필터
     * @param int         $limit        최대 수신 수
     * @param bool        $markRead     수신 후 처리 완료로 표시
     * @return array AgentMessage 배열
     */
    public function receive(int $agentNumber, ?string $messageType = null, int $limit = 50, bool $markRead = true): array
    {
        try {
            $conditions = [];
            $params = [];

            // 직접 수신 + 브로드캐스트
            $sql = "SELECT * FROM {{$this->tableName}}
                    WHERE (to_agent = :to_agent OR to_agent = 0)
                      AND status = 'pending'
                      AND expires_at > NOW()";

            $params['to_agent'] = $agentNumber;

            if ($messageType !== null) {
                $sql .= " AND message_type = :message_type";
                $params['message_type'] = $messageType;
            }

            $sql .= " ORDER BY priority ASC, timecreated ASC
                      LIMIT {$limit}";

            $records = $this->db->get_records_sql($sql, $params);
            $messages = [];

            foreach ($records as $record) {
                // 브로드캐스트 제외 목록 확인
                $payload = json_decode($record->payload, true) ?: [];
                if (isset($payload['_exclude']) && in_array($agentNumber, $payload['_exclude'])) {
                    continue;
                }

                $message = AgentMessage::fromDbRecord($record);

                if ($markRead && $record->to_agent != 0) {
                    // 브로드캐스트가 아닌 경우만 상태 변경
                    $this->updateMessageStatus($message->getMessageId(), 'processing');
                }

                $messages[] = $message;
            }

            return $messages;

        } catch (Exception $e) {
            $this->logError('receive', $e->getMessage());
            return [];
        }
    }

    /**
     * 메시지 처리 완료 표시
     *
     * @param string $messageId 메시지 ID
     * @param bool   $success   성공 여부
     * @param string $result    처리 결과
     * @return bool
     */
    public function acknowledge(string $messageId, bool $success = true, string $result = ''): bool
    {
        $status = $success ? 'processed' : 'failed';
        return $this->updateMessageStatus($messageId, $status);
    }

    // =========================================================================
    // 요청-응답 패턴
    // =========================================================================

    /**
     * 동기식 요청 (응답 대기)
     *
     * @param int    $fromAgent   발신 에이전트
     * @param int    $toAgent     수신 에이전트
     * @param string $requestType 요청 유형
     * @param array  $params      요청 파라미터
     * @param int    $timeout     타임아웃 (초)
     * @return array|null 응답 데이터 또는 null (타임아웃)
     */
    public function request(
        int $fromAgent,
        int $toAgent,
        string $requestType,
        array $params,
        int $timeout = 30
    ): ?array {
        $requestId = $this->generateRequestId();

        // 요청 메시지 전송
        $result = $this->send(
            $fromAgent,
            $toAgent,
            'request_' . $requestType,
            array_merge($params, ['_request_id' => $requestId]),
            MESSAGE_PRIORITY['HIGH'],
            $timeout + 10  // TTL은 타임아웃보다 약간 길게
        );

        if (!$result['success']) {
            return null;
        }

        // 응답 대기 (폴링)
        $startTime = time();
        while (time() - $startTime < $timeout) {
            $response = $this->checkResponse($requestId, $fromAgent);
            if ($response !== null) {
                return $response;
            }
            usleep(100000);  // 0.1초 대기
        }

        // 타임아웃
        $this->logWarning('request', "요청 타임아웃: {$requestId}");
        return null;
    }

    /**
     * 요청에 대한 응답 전송
     *
     * @param int    $fromAgent 발신 에이전트 (응답자)
     * @param string $requestId 원본 요청 ID
     * @param array  $response  응답 데이터
     * @return bool
     */
    public function respond(int $fromAgent, string $requestId, array $response): bool
    {
        // 원본 요청 찾기
        $originalRequest = $this->db->get_record_sql(
            "SELECT * FROM {{$this->tableName}}
             WHERE payload LIKE :request_id
               AND message_type LIKE 'request_%'",
            ['request_id' => '%' . $requestId . '%']
        );

        if (!$originalRequest) {
            $this->logError('respond', "원본 요청을 찾을 수 없음: {$requestId}");
            return false;
        }

        $result = $this->send(
            $fromAgent,
            $originalRequest->from_agent,
            'response_' . str_replace('request_', '', $originalRequest->message_type),
            array_merge($response, ['_request_id' => $requestId]),
            MESSAGE_PRIORITY['HIGH']
        );

        return $result['success'];
    }

    /**
     * 응답 확인
     *
     * @param string $requestId   요청 ID
     * @param int    $agentNumber 응답을 기다리는 에이전트
     * @return array|null
     */
    private function checkResponse(string $requestId, int $agentNumber): ?array
    {
        try {
            $record = $this->db->get_record_sql(
                "SELECT * FROM {{$this->tableName}}
                 WHERE to_agent = :to_agent
                   AND message_type LIKE 'response_%'
                   AND payload LIKE :request_id
                   AND status = 'pending'",
                [
                    'to_agent'   => $agentNumber,
                    'request_id' => '%' . $requestId . '%'
                ]
            );

            if ($record) {
                $this->updateMessageStatus($record->message_id, 'processed');
                $payload = json_decode($record->payload, true) ?: [];
                unset($payload['_request_id']);
                return $payload;
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 요청 ID 생성
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(8));
    }

    // =========================================================================
    // 구독 (Subscribe)
    // =========================================================================

    /**
     * 메시지 유형 구독
     *
     * @param int      $agentNumber  구독 에이전트
     * @param array    $messageTypes 구독할 메시지 유형
     * @param callable $handler      처리 핸들러
     * @return bool
     */
    public function subscribe(int $agentNumber, array $messageTypes, callable $handler): bool
    {
        foreach ($messageTypes as $type) {
            $key = "{$agentNumber}_{$type}";
            $this->subscriptions[$key] = $handler;
        }

        $this->logActivity('subscribe', null, [
            'agent'    => $agentNumber,
            'types'    => $messageTypes,
            'action'   => 'subscribe'
        ]);

        return true;
    }

    /**
     * 구독 해제
     *
     * @param int   $agentNumber  에이전트 번호
     * @param array $messageTypes 해제할 메시지 유형
     * @return bool
     */
    public function unsubscribe(int $agentNumber, array $messageTypes): bool
    {
        foreach ($messageTypes as $type) {
            $key = "{$agentNumber}_{$type}";
            unset($this->subscriptions[$key]);
        }

        return true;
    }

    /**
     * 구독 핸들러 트리거
     *
     * @param AgentMessage $message
     */
    private function triggerSubscriptions(AgentMessage $message): void
    {
        $toAgent = $message->getToAgent();
        $type = $message->getMessageType();

        // 특정 에이전트 구독
        if ($toAgent > 0) {
            $key = "{$toAgent}_{$type}";
            if (isset($this->subscriptions[$key])) {
                try {
                    call_user_func($this->subscriptions[$key], $message);
                } catch (Exception $e) {
                    $this->logError('triggerSubscription', $e->getMessage());
                }
            }
        }

        // 브로드캐스트: 모든 구독자에게
        if ($toAgent === 0) {
            foreach ($this->subscriptions as $key => $handler) {
                if (strpos($key, "_{$type}") !== false) {
                    try {
                        call_user_func($handler, $message);
                    } catch (Exception $e) {
                        $this->logError('triggerSubscription', $e->getMessage());
                    }
                }
            }
        }
    }

    // =========================================================================
    // 에이전트 상태 관리
    // =========================================================================

    /**
     * 에이전트 가용성 확인
     *
     * @param int $agentNumber 에이전트 번호
     * @return bool
     */
    public function isAgentAvailable(int $agentNumber): bool
    {
        // 캐시된 상태 확인
        if (isset($this->agentStatus[$agentNumber])) {
            $status = $this->agentStatus[$agentNumber];
            if (time() - $status['checked_at'] < 60) {  // 1분 캐시
                return $status['available'];
            }
        }

        // 최근 활동 확인 (5분 이내)
        try {
            $recentActivity = $this->db->get_record_sql(
                "SELECT MAX(timecreated) as last_activity
                 FROM {{$this->tableName}}
                 WHERE from_agent = :agent
                   AND timecreated > :since",
                [
                    'agent' => $agentNumber,
                    'since' => time() - 300  // 5분
                ]
            );

            $available = $recentActivity && $recentActivity->last_activity !== null;

            $this->agentStatus[$agentNumber] = [
                'available'  => $available,
                'checked_at' => time()
            ];

            return $available;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 에이전트 하트비트 전송
     *
     * @param int $agentNumber 에이전트 번호
     * @return bool
     */
    public function heartbeat(int $agentNumber): bool
    {
        return $this->send(
            $agentNumber,
            0,  // 자기 자신에게 (시스템용)
            'health_check',
            ['status' => 'alive', 'timestamp' => time()],
            MESSAGE_PRIORITY['DEFERRED'],
            60  // 1분 TTL
        )['success'];
    }

    /**
     * 모든 에이전트 상태 조회
     *
     * @return array
     */
    public function getAllAgentStatus(): array
    {
        $status = [];

        for ($i = 1; $i <= 21; $i++) {
            $agentInfo = get_agent_info($i);
            $status[$i] = [
                'number'    => $i,
                'name'      => $agentInfo['name'] ?? 'unknown',
                'kr_name'   => $agentInfo['kr_name'] ?? '알수없음',
                'available' => $this->isAgentAvailable($i),
                'queue'     => $this->getAgentQueueStatus($i)
            ];
        }

        return $status;
    }

    /**
     * 에이전트별 큐 상태 조회
     *
     * @param int $agentNumber
     * @return array
     */
    public function getAgentQueueStatus(int $agentNumber): array
    {
        try {
            $pending = $this->db->count_records_sql(
                "SELECT COUNT(*) FROM {{$this->tableName}}
                 WHERE to_agent = :agent AND status = 'pending'",
                ['agent' => $agentNumber]
            );

            $processing = $this->db->count_records_sql(
                "SELECT COUNT(*) FROM {{$this->tableName}}
                 WHERE to_agent = :agent AND status = 'processing'",
                ['agent' => $agentNumber]
            );

            return [
                'pending'    => (int) $pending,
                'processing' => (int) $processing
            ];

        } catch (Exception $e) {
            return ['pending' => 0, 'processing' => 0, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 협력 관계 관리
    // =========================================================================

    /**
     * 협력 관계 검증
     *
     * @param AgentMessage $message
     * @return bool
     */
    private function validateCollaboration(AgentMessage $message): bool
    {
        if ($message->isBroadcast()) {
            return true;  // 브로드캐스트는 항상 허용
        }

        $fromAgent = $message->getFromAgent();
        $toAgent = $message->getToAgent();

        // 협력 관계 정의 확인
        if (!isset(AGENT_COLLABORATION[$fromAgent])) {
            return false;
        }

        $collaboration = AGENT_COLLABORATION[$fromAgent];

        // 직접 트리거 관계
        foreach ($collaboration['triggers'] as $trigger) {
            if ($trigger['target'] === $toAgent || $trigger['target'] === 0) {
                return true;
            }
        }

        // 청취 관계 (역방향)
        if (isset(AGENT_COLLABORATION[$toAgent])) {
            $targetCollaboration = AGENT_COLLABORATION[$toAgent];
            if (in_array($fromAgent, $targetCollaboration['listens_to'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 에이전트 협력 맵 조회
     *
     * @param int $agentNumber
     * @return array
     */
    public function getCollaborationMap(int $agentNumber): array
    {
        return get_collaboration_map($agentNumber);
    }

    // =========================================================================
    // 유틸리티
    // =========================================================================

    /**
     * 메시지 상태 업데이트
     *
     * @param string $messageId
     * @param string $status
     * @return bool
     */
    private function updateMessageStatus(string $messageId, string $status): bool
    {
        try {
            $update = ['status' => $status];

            if ($status === 'processed' || $status === 'failed') {
                $update['timeprocessed'] = time();
            }

            $this->db->update_record($this->tableName, (object) array_merge(
                ['id' => $this->getMessageDbId($messageId)],
                $update
            ));

            return true;

        } catch (Exception $e) {
            $this->logError('updateMessageStatus', $e->getMessage());
            return false;
        }
    }

    /**
     * 메시지 ID로 DB ID 조회
     *
     * @param string $messageId
     * @return int|null
     */
    private function getMessageDbId(string $messageId): ?int
    {
        $record = $this->db->get_record($this->tableName, ['message_id' => $messageId], 'id');
        return $record ? (int) $record->id : null;
    }

    /**
     * 만료 메시지 정리
     *
     * @return int 정리된 메시지 수
     */
    public function cleanupExpired(): int
    {
        try {
            // 만료된 pending → expired
            $this->db->execute(
                "UPDATE {{$this->tableName}}
                 SET status = 'expired'
                 WHERE expires_at < NOW()
                   AND status = 'pending'"
            );

            // 30일 이상 지난 처리 완료 메시지 삭제
            $cutoffTime = time() - (30 * 24 * 60 * 60);
            $deleted = $this->db->delete_records_select(
                $this->tableName,
                "status IN ('processed', 'expired') AND timecreated < :cutoff",
                ['cutoff' => $cutoffTime]
            );

            return (int) $deleted;

        } catch (Exception $e) {
            $this->logError('cleanupExpired', $e->getMessage());
            return 0;
        }
    }

    /**
     * 통신 로그 조회
     *
     * @param array $filters
     * @param int   $limit
     * @return array
     */
    public function getCommunicationLog(array $filters = [], int $limit = 100): array
    {
        try {
            $sql = "SELECT * FROM {{$this->tableName}} WHERE 1=1";
            $params = [];

            if (isset($filters['from_agent'])) {
                $sql .= " AND from_agent = :from_agent";
                $params['from_agent'] = $filters['from_agent'];
            }

            if (isset($filters['to_agent'])) {
                $sql .= " AND to_agent = :to_agent";
                $params['to_agent'] = $filters['to_agent'];
            }

            if (isset($filters['message_type'])) {
                $sql .= " AND message_type = :message_type";
                $params['message_type'] = $filters['message_type'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params['status'] = $filters['status'];
            }

            if (isset($filters['since'])) {
                $sql .= " AND timecreated >= :since";
                $params['since'] = $filters['since'];
            }

            $sql .= " ORDER BY timecreated DESC LIMIT {$limit}";

            $records = $this->db->get_records_sql($sql, $params);
            $logs = [];

            foreach ($records as $record) {
                $logs[] = [
                    'message_id'   => $record->message_id,
                    'from_agent'   => $record->from_agent,
                    'to_agent'     => $record->to_agent,
                    'message_type' => $record->message_type,
                    'priority'     => $record->priority,
                    'status'       => $record->status,
                    'created_at'   => $record->timecreated,
                    'processed_at' => $record->timeprocessed
                ];
            }

            return $logs;

        } catch (Exception $e) {
            $this->logError('getCommunicationLog', $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // 로깅
    // =========================================================================

    /**
     * 활동 로그
     *
     * @param string            $action
     * @param AgentMessage|null $message
     * @param array             $extra
     */
    private function logActivity(string $action, ?AgentMessage $message, array $extra = []): void
    {
        if (!LOGGING_CONFIG['enabled']) {
            return;
        }

        try {
            $logData = (object) [
                'agent_number' => $message ? $message->getFromAgent() : ($extra['agent'] ?? 0),
                'action'       => 'bus_' . $action,
                'message_id'   => $message ? $message->getMessageId() : null,
                'message_type' => $message ? $message->getMessageType() : ($extra['types'][0] ?? null),
                'details'      => json_encode($extra),
                'timecreated'  => time()
            ];

            $this->db->insert_record($this->logTableName, $logData);

        } catch (Exception $e) {
            // 로깅 실패 무시
        }
    }

    /**
     * 경고 로그
     *
     * @param string $method
     * @param string $message
     */
    private function logWarning(string $method, string $message): void
    {
        error_log("[InterAgentBus.php:{$method}] WARNING: {$message}");
    }

    /**
     * 에러 로그
     *
     * @param string $method
     * @param string $message
     */
    private function logError(string $method, string $message): void
    {
        error_log("[InterAgentBus.php:{$method}] ERROR: {$message}");
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // 버스 인스턴스 획득
 * $bus = InterAgentBus::getInstance();
 *
 * // === 1. 간단한 메시지 전송 ===
 * $result = $bus->send(
 *     5,                          // Agent05 (학습 감정)
 *     8,                          // Agent08 (평온도)
 *     'frustration_detected',     // 메시지 유형
 *     [                           // 페이로드
 *         'user_id'   => 123,
 *         'emotion'   => 'frustration',
 *         'intensity' => 0.85
 *     ]
 * );
 *
 * // === 2. 긴급 알림 전송 ===
 * $bus->sendUrgentAlert(
 *     13,                         // Agent13 (학습 이탈)
 *     9,                          // Agent09 (학습 관리)
 *     'dropout_risk',
 *     ['user_id' => 456, 'risk_level' => 'high']
 * );
 *
 * // === 3. 브로드캐스트 ===
 * $bus->broadcast(
 *     9,                          // Agent09
 *     'learning_plan_updated',
 *     ['user_id' => 123, 'plan_id' => 'plan_001'],
 *     [5, 8]                      // Agent05, 08 제외
 * );
 *
 * // === 4. 메시지 수신 ===
 * $messages = $bus->receive(8);   // Agent08의 대기 메시지
 *
 * foreach ($messages as $msg) {
 *     // 메시지 처리
 *     processMessage($msg);
 *
 *     // 처리 완료 표시
 *     $bus->acknowledge($msg->getMessageId(), true);
 * }
 *
 * // === 5. 요청-응답 패턴 ===
 * $response = $bus->request(
 *     9,                          // Agent09 (요청자)
 *     3,                          // Agent03 (응답자)
 *     'get_user_diagnosis',
 *     ['user_id' => 123],
 *     30                          // 30초 타임아웃
 * );
 *
 * if ($response !== null) {
 *     // 응답 처리
 * }
 *
 * // === 6. 구독 ===
 * $bus->subscribe(8, ['frustration_detected', 'anxiety_detected'], function($msg) {
 *     // 감정 알림 처리
 * });
 *
 * // === 7. 상태 확인 ===
 * $allStatus = $bus->getAllAgentStatus();
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 사용 테이블:
 * - mdl_at_agent_messages: 메시지 큐
 * - mdl_at_agent_logs: 활동 로그
 *
 * 주요 쿼리 패턴:
 *
 * 1. 메시지 삽입 (publish)
 *    INSERT INTO mdl_at_agent_messages (message_id, from_agent, to_agent, ...)
 *
 * 2. 대기 메시지 조회 (receive)
 *    SELECT * FROM mdl_at_agent_messages
 *    WHERE (to_agent = ? OR to_agent = 0)
 *      AND status = 'pending'
 *      AND expires_at > NOW()
 *    ORDER BY priority ASC, timecreated ASC
 *
 * 3. 상태 업데이트 (acknowledge)
 *    UPDATE mdl_at_agent_messages
 *    SET status = ?, timeprocessed = ?
 *    WHERE message_id = ?
 *
 * 4. 만료 메시지 정리 (cleanupExpired)
 *    UPDATE mdl_at_agent_messages SET status = 'expired'
 *    WHERE expires_at < NOW() AND status = 'pending'
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
