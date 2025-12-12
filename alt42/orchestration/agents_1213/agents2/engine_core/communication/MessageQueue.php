<?php
/**
 * MessageQueue.php
 *
 * DB 기반 메시지 큐 관리 클래스
 * 에이전트 간 메시지의 저장, 조회, 상태 관리를 담당
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/communication/MessageQueue.php
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/AgentMessage.php');
require_once(__DIR__ . '/../config/engine_config.php');

/**
 * Class MessageQueue
 *
 * DB 기반 메시지 큐 구현
 * 우선순위 기반 정렬, 만료 처리, 재시도 로직 제공
 */
class MessageQueue
{
    /** @var \moodle_database DB 인스턴스 */
    private $db;

    /** @var string 메시지 테이블명 */
    private $tableName;

    /** @var int 에이전트 번호 (이 큐를 소유한 에이전트) */
    private $agentNumber;

    /** @var int 마지막 정리 시간 */
    private $lastCleanupTime = 0;

    /**
     * MessageQueue 생성자
     *
     * @param int $agentNumber 에이전트 번호
     */
    public function __construct(int $agentNumber)
    {
        global $DB;

        if ($agentNumber < 1 || $agentNumber > 21) {
            throw new InvalidArgumentException(
                "[MessageQueue.php:" . __LINE__ . "] 에이전트 번호가 유효 범위(1-21)를 벗어남: {$agentNumber}"
            );
        }

        $this->db = $DB;
        $this->agentNumber = $agentNumber;
        $this->tableName = DB_TABLES['common']['messages'];
    }

    // =========================================================================
    // 메시지 발행 (Publish)
    // =========================================================================

    /**
     * 메시지 큐에 추가
     *
     * @param AgentMessage $message 메시지 객체
     * @return array ['success' => bool, 'message_id' => string, 'error' => string|null]
     */
    public function enqueue(AgentMessage $message): array
    {
        try {
            // 만료된 메시지는 거부
            if ($message->isExpired()) {
                return [
                    'success'    => false,
                    'message_id' => $message->getMessageId(),
                    'error'      => '[MessageQueue.php:' . __LINE__ . '] 메시지가 이미 만료됨'
                ];
            }

            // DB에 삽입
            $record = (object) $message->toDbArray();

            $this->db->insert_record($this->tableName, $record);

            $this->logQueueActivity('enqueue', $message);

            return [
                'success'    => true,
                'message_id' => $message->getMessageId(),
                'error'      => null
            ];

        } catch (Exception $e) {
            return [
                'success'    => false,
                'message_id' => $message->getMessageId(),
                'error'      => '[MessageQueue.php:' . __LINE__ . '] DB 삽입 실패: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 여러 메시지 일괄 추가
     *
     * @param array $messages AgentMessage 배열
     * @return array ['success' => bool, 'sent_count' => int, 'message_ids' => array, 'errors' => array]
     */
    public function enqueueBatch(array $messages): array
    {
        $results = [
            'success'     => true,
            'sent_count'  => 0,
            'message_ids' => [],
            'errors'      => []
        ];

        foreach ($messages as $message) {
            if (!($message instanceof AgentMessage)) {
                $results['errors'][] = '[MessageQueue.php:' . __LINE__ . '] 유효하지 않은 메시지 객체';
                $results['success'] = false;
                continue;
            }

            $result = $this->enqueue($message);

            if ($result['success']) {
                $results['sent_count']++;
                $results['message_ids'][] = $result['message_id'];
            } else {
                $results['errors'][] = $result['error'];
                $results['success'] = false;
            }
        }

        return $results;
    }

    // =========================================================================
    // 메시지 소비 (Consume)
    // =========================================================================

    /**
     * 대기 중인 메시지 조회
     *
     * @param string|null $messageType 메시지 유형 필터
     * @param int         $limit       최대 조회 수
     * @param bool        $markProcessing 조회 후 processing으로 표시할지 여부
     * @return array AgentMessage 배열
     */
    public function dequeue(?string $messageType = null, int $limit = 50, bool $markProcessing = true): array
    {
        // 주기적 정리 수행
        $this->periodicCleanup();

        $conditions = [
            'to_agent' => $this->agentNumber,
            'status'   => 'pending'
        ];

        if ($messageType !== null) {
            $conditions['message_type'] = $messageType;
        }

        // 우선순위 + 생성 시간 기준 정렬
        $sql = "SELECT * FROM {{$this->tableName}}
                WHERE (to_agent = :to_agent OR to_agent = 0)
                  AND status = :status
                  AND expires_at > NOW()";

        $params = [
            'to_agent' => $this->agentNumber,
            'status'   => 'pending'
        ];

        if ($messageType !== null) {
            $sql .= " AND message_type = :message_type";
            $params['message_type'] = $messageType;
        }

        $sql .= " ORDER BY priority ASC, timecreated ASC
                  LIMIT {$limit}";

        try {
            $records = $this->db->get_records_sql($sql, $params);
            $messages = [];

            foreach ($records as $record) {
                $message = AgentMessage::fromDbRecord($record);

                if ($markProcessing) {
                    $this->updateStatus($message->getMessageId(), 'processing');
                }

                $messages[] = $message;
            }

            return $messages;

        } catch (Exception $e) {
            $this->logError('dequeue', $e->getMessage());
            return [];
        }
    }

    /**
     * 특정 메시지 조회
     *
     * @param string $messageId 메시지 ID
     * @return AgentMessage|null
     */
    public function getMessage(string $messageId): ?AgentMessage
    {
        try {
            $record = $this->db->get_record($this->tableName, ['message_id' => $messageId]);

            if ($record) {
                return AgentMessage::fromDbRecord($record);
            }

            return null;

        } catch (Exception $e) {
            $this->logError('getMessage', $e->getMessage());
            return null;
        }
    }

    /**
     * 브로드캐스트 메시지 조회 (to_agent = 0)
     *
     * @param int  $limit 최대 조회 수
     * @param bool $markProcessing 조회 후 processing으로 표시할지 여부
     * @return array AgentMessage 배열
     */
    public function getBroadcastMessages(int $limit = 50, bool $markProcessing = true): array
    {
        $sql = "SELECT * FROM {{$this->tableName}}
                WHERE to_agent = 0
                  AND status = :status
                  AND expires_at > NOW()
                ORDER BY priority ASC, timecreated ASC
                LIMIT {$limit}";

        try {
            $records = $this->db->get_records_sql($sql, ['status' => 'pending']);
            $messages = [];

            foreach ($records as $record) {
                $message = AgentMessage::fromDbRecord($record);

                if ($markProcessing) {
                    // 브로드캐스트는 모든 에이전트가 받으므로 별도 처리 테이블 필요
                    // 여기서는 단순히 조회만 수행
                }

                $messages[] = $message;
            }

            return $messages;

        } catch (Exception $e) {
            $this->logError('getBroadcastMessages', $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // 메시지 상태 관리
    // =========================================================================

    /**
     * 메시지 상태 업데이트
     *
     * @param string      $messageId 메시지 ID
     * @param string      $status    새 상태
     * @param string|null $result    처리 결과
     * @return bool 성공 여부
     */
    public function updateStatus(string $messageId, string $status, ?string $result = null): bool
    {
        if (!in_array($status, AgentMessage::VALID_STATUSES)) {
            $this->logError('updateStatus', "유효하지 않은 상태: {$status}");
            return false;
        }

        try {
            $update = [
                'status' => $status
            ];

            if ($status === 'processed' || $status === 'failed') {
                $update['timeprocessed'] = time();
            }

            // result는 별도 테이블이나 필드에 저장 필요시 확장

            $this->db->set_field($this->tableName, 'status', $status, ['message_id' => $messageId]);

            if (isset($update['timeprocessed'])) {
                $this->db->set_field($this->tableName, 'timeprocessed', $update['timeprocessed'], ['message_id' => $messageId]);
            }

            return true;

        } catch (Exception $e) {
            $this->logError('updateStatus', $e->getMessage());
            return false;
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
        return $this->updateStatus($messageId, $status, $result);
    }

    /**
     * 메시지 재시도
     *
     * @param string $messageId 메시지 ID
     * @return bool 성공 여부
     */
    public function retry(string $messageId): bool
    {
        try {
            $message = $this->getMessage($messageId);

            if (!$message) {
                $this->logError('retry', "메시지를 찾을 수 없음: {$messageId}");
                return false;
            }

            if (!$message->canRetry()) {
                $this->logError('retry', "최대 재시도 횟수 초과: {$messageId}");
                return false;
            }

            // 재시도 횟수 증가 및 상태 초기화
            $this->db->execute(
                "UPDATE {{$this->tableName}}
                 SET status = 'pending',
                     retry_count = retry_count + 1,
                     timeprocessed = NULL
                 WHERE message_id = :message_id",
                ['message_id' => $messageId]
            );

            return true;

        } catch (Exception $e) {
            $this->logError('retry', $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 큐 상태 조회
    // =========================================================================

    /**
     * 큐 상태 통계 조회
     *
     * @return array ['pending' => int, 'processing' => int, 'failed' => int, 'expired' => int, 'oldest' => int]
     */
    public function getStatus(): array
    {
        try {
            $result = [
                'pending'    => 0,
                'processing' => 0,
                'failed'     => 0,
                'expired'    => 0,
                'oldest'     => 0
            ];

            // 상태별 카운트
            $sql = "SELECT status, COUNT(*) as cnt
                    FROM {{$this->tableName}}
                    WHERE to_agent = :to_agent OR to_agent = 0
                    GROUP BY status";

            $counts = $this->db->get_records_sql($sql, ['to_agent' => $this->agentNumber]);

            foreach ($counts as $row) {
                if (isset($result[$row->status])) {
                    $result[$row->status] = (int) $row->cnt;
                }
            }

            // 만료 메시지 카운트
            $expiredCount = $this->db->count_records_sql(
                "SELECT COUNT(*) FROM {{$this->tableName}}
                 WHERE (to_agent = :to_agent OR to_agent = 0)
                   AND expires_at < NOW()
                   AND status = 'pending'",
                ['to_agent' => $this->agentNumber]
            );
            $result['expired'] = (int) $expiredCount;

            // 가장 오래된 대기 메시지 시간
            $oldest = $this->db->get_field_sql(
                "SELECT MIN(timecreated) FROM {{$this->tableName}}
                 WHERE (to_agent = :to_agent OR to_agent = 0)
                   AND status = 'pending'",
                ['to_agent' => $this->agentNumber]
            );
            $result['oldest'] = $oldest ? (int) $oldest : 0;

            return $result;

        } catch (Exception $e) {
            $this->logError('getStatus', $e->getMessage());
            return [
                'pending'    => 0,
                'processing' => 0,
                'failed'     => 0,
                'expired'    => 0,
                'oldest'     => 0,
                'error'      => $e->getMessage()
            ];
        }
    }

    /**
     * 메시지 히스토리 조회
     *
     * @param array $filters 필터 조건
     * @param int   $limit   최대 조회 수
     * @return array AgentMessage 배열
     */
    public function getHistory(array $filters = [], int $limit = 100): array
    {
        try {
            $sql = "SELECT * FROM {{$this->tableName}} WHERE 1=1";
            $params = [];

            // 에이전트 필터
            if (isset($filters['agent'])) {
                $sql .= " AND (from_agent = :from_agent OR to_agent = :to_agent)";
                $params['from_agent'] = $filters['agent'];
                $params['to_agent'] = $filters['agent'];
            } else {
                $sql .= " AND (to_agent = :to_agent OR to_agent = 0)";
                $params['to_agent'] = $this->agentNumber;
            }

            // 상태 필터
            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params['status'] = $filters['status'];
            }

            // 메시지 유형 필터
            if (isset($filters['message_type'])) {
                $sql .= " AND message_type = :message_type";
                $params['message_type'] = $filters['message_type'];
            }

            // 시간 범위 필터
            if (isset($filters['from_time'])) {
                $sql .= " AND timecreated >= :from_time";
                $params['from_time'] = $filters['from_time'];
            }

            if (isset($filters['to_time'])) {
                $sql .= " AND timecreated <= :to_time";
                $params['to_time'] = $filters['to_time'];
            }

            $sql .= " ORDER BY timecreated DESC LIMIT {$limit}";

            $records = $this->db->get_records_sql($sql, $params);
            $messages = [];

            foreach ($records as $record) {
                $messages[] = AgentMessage::fromDbRecord($record);
            }

            return $messages;

        } catch (Exception $e) {
            $this->logError('getHistory', $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // 정리 (Cleanup)
    // =========================================================================

    /**
     * 만료된 메시지 정리
     *
     * @return int 정리된 메시지 수
     */
    public function cleanupExpired(): int
    {
        try {
            // 만료된 pending 메시지를 expired로 변경
            $affectedRows = $this->db->execute(
                "UPDATE {{$this->tableName}}
                 SET status = 'expired'
                 WHERE expires_at < NOW()
                   AND status = 'pending'"
            );

            // 오래된 처리 완료 메시지 삭제 (30일 이상)
            $cutoffTime = time() - (30 * 24 * 60 * 60);
            $this->db->delete_records_select(
                $this->tableName,
                "status IN ('processed', 'expired') AND timecreated < :cutoff",
                ['cutoff' => $cutoffTime]
            );

            $this->lastCleanupTime = time();

            return (int) $affectedRows;

        } catch (Exception $e) {
            $this->logError('cleanupExpired', $e->getMessage());
            return 0;
        }
    }

    /**
     * 주기적 정리 (설정된 간격마다 자동 실행)
     */
    private function periodicCleanup(): void
    {
        $cleanupInterval = MESSAGE_QUEUE_CONFIG['cleanup_interval'] ?? 300;

        if (time() - $this->lastCleanupTime > $cleanupInterval) {
            $this->cleanupExpired();
        }
    }

    /**
     * 큐 전체 비우기 (테스트용)
     *
     * @param bool $confirm 확인 플래그
     * @return bool
     */
    public function purge(bool $confirm = false): bool
    {
        if (!$confirm) {
            return false;
        }

        try {
            $this->db->delete_records($this->tableName, [
                'to_agent' => $this->agentNumber
            ]);
            return true;

        } catch (Exception $e) {
            $this->logError('purge', $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 로깅
    // =========================================================================

    /**
     * 큐 활동 로그
     *
     * @param string       $action  동작
     * @param AgentMessage $message 메시지
     */
    private function logQueueActivity(string $action, AgentMessage $message): void
    {
        if (!LOGGING_CONFIG['enabled']) {
            return;
        }

        try {
            $logData = (object) [
                'agent_number' => $this->agentNumber,
                'action'       => 'queue_' . $action,
                'message_id'   => $message->getMessageId(),
                'message_type' => $message->getMessageType(),
                'from_agent'   => $message->getFromAgent(),
                'to_agent'     => $message->getToAgent(),
                'priority'     => $message->getPriority(),
                'timecreated'  => time()
            ];

            $this->db->insert_record(DB_TABLES['common']['agent_logs'], $logData);

        } catch (Exception $e) {
            // 로깅 실패는 무시
        }
    }

    /**
     * 에러 로그
     *
     * @param string $method  메서드명
     * @param string $message 에러 메시지
     */
    private function logError(string $method, string $message): void
    {
        error_log("[MessageQueue.php:{$method}] Agent{$this->agentNumber}: {$message}");
    }

    /**
     * 에이전트 번호 반환
     *
     * @return int
     */
    public function getAgentNumber(): int
    {
        return $this->agentNumber;
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 사용 테이블: mdl_at_agent_messages
 *
 * 이 클래스가 수행하는 주요 DB 작업:
 *
 * 1. INSERT (enqueue)
 *    - 새 메시지 삽입
 *    - 필드: message_id, from_agent, to_agent, message_type, priority,
 *            payload, status, checksum, retry_count, expires_at, timecreated
 *
 * 2. SELECT (dequeue, getMessage, getHistory)
 *    - 우선순위 + 생성시간 정렬 조회
 *    - 만료 여부 필터링 (expires_at > NOW())
 *    - 에이전트별/메시지유형별 필터링
 *
 * 3. UPDATE (updateStatus, acknowledge, retry)
 *    - status 필드 변경
 *    - timeprocessed 업데이트
 *    - retry_count 증가
 *
 * 4. DELETE (cleanupExpired, purge)
 *    - 30일 이상 지난 처리 완료 메시지 삭제
 *    - 특정 에이전트 큐 비우기 (테스트용)
 *
 * 주요 인덱스 사용:
 * - idx_to_status: (to_agent, status) - dequeue 성능 최적화
 * - idx_priority: (priority, timecreated) - 우선순위 정렬 최적화
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
