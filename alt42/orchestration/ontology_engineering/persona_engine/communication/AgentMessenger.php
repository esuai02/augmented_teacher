<?php
/**
 * AgentMessenger - 에이전트 간 메시징 시스템
 *
 * 21개 에이전트 간의 DB 기반 메시지 교환을 담당합니다.
 * 비동기 메시징, 우선순위 큐, 메시지 상태 추적을 지원합니다.
 *
 * @package PersonaEngine\Communication
 * @version 1.0.0
 * @author ALT42 Orchestration System
 *
 * 사용 예시:
 * ```php
 * $messenger = new AgentMessenger($DB);
 * $messenger->send(6, 1, 'persona_update', ['persona_id' => 'T_P1']);
 * $messages = $messenger->receive(1);
 * ```
 *
 * 관련 DB 테이블:
 * - at_agent_messages: 에이전트 간 메시지 저장
 *   - id (int): 메시지 ID
 *   - from_agent (int): 발신 에이전트 (1-21)
 *   - to_agent (int): 수신 에이전트 (1-21)
 *   - user_id (int): 관련 사용자 ID
 *   - message_type (varchar): 메시지 유형
 *   - payload (text): JSON 페이로드
 *   - priority (int): 우선순위 (1=높음, 5=낮음)
 *   - status (varchar): pending, read, processed, failed
 *   - created_at (datetime): 생성 시간
 *   - processed_at (datetime): 처리 시간
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB;

class AgentMessenger {

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var string 메시지 테이블명 */
    private $tableName = 'at_agent_messages';

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 메시지 유형 정의 */
    private $messageTypes = [
        'persona_update' => '페르소나 상태 업데이트',
        'context_share' => '컨텍스트 공유',
        'feedback_request' => '피드백 요청',
        'escalation' => '에스컬레이션',
        'sync_request' => '동기화 요청',
        'data_request' => '데이터 요청',
        'notification' => '알림',
        'command' => '명령'
    ];

    /**
     * 생성자
     *
     * @param object $db Moodle DB 객체 (null이면 global $DB 사용)
     */
    public function __construct($db = null) {
        global $DB;
        $this->db = $db ?? $DB;
    }

    /**
     * 메시지 전송
     *
     * @param int $fromAgent 발신 에이전트 번호 (1-21)
     * @param int $toAgent 수신 에이전트 번호 (1-21)
     * @param string $messageType 메시지 유형
     * @param array $payload 메시지 페이로드
     * @param int $userId 관련 사용자 ID (0이면 시스템 메시지)
     * @param int $priority 우선순위 (1-5, 1이 가장 높음)
     * @return int|false 생성된 메시지 ID 또는 실패시 false
     */
    public function send(
        int $fromAgent,
        int $toAgent,
        string $messageType,
        array $payload,
        int $userId = 0,
        int $priority = 3
    ) {
        // 유효성 검증
        if (!$this->validateAgentId($fromAgent) || !$this->validateAgentId($toAgent)) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 유효하지 않은 에이전트 ID: from={$fromAgent}, to={$toAgent}");
            return false;
        }

        if (!isset($this->messageTypes[$messageType])) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 알 수 없는 메시지 유형: {$messageType}");
            return false;
        }

        try {
            $record = new stdClass();
            $record->from_agent = $fromAgent;
            $record->to_agent = $toAgent;
            $record->user_id = $userId;
            $record->message_type = $messageType;
            $record->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $record->priority = max(1, min(5, $priority));
            $record->status = 'pending';
            $record->created_at = date('Y-m-d H:i:s');
            $record->processed_at = null;

            $id = $this->db->insert_record($this->tableName, $record);

            return $id;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 메시지 전송 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 메시지 수신 (대기 중인 메시지 조회)
     *
     * @param int $agentId 수신 에이전트 번호
     * @param array $options 옵션
     *        - 'status': 조회할 상태 (pending, read, processed)
     *        - 'limit': 최대 조회 수
     *        - 'message_type': 특정 메시지 유형만 조회
     *        - 'user_id': 특정 사용자 메시지만 조회
     *        - 'mark_read': 조회 시 읽음 처리 여부 (기본 true)
     * @return array 메시지 배열
     */
    public function receive(int $agentId, array $options = []): array {
        if (!$this->validateAgentId($agentId)) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 유효하지 않은 에이전트 ID: {$agentId}");
            return [];
        }

        $status = $options['status'] ?? 'pending';
        $limit = $options['limit'] ?? 50;
        $messageType = $options['message_type'] ?? null;
        $userId = $options['user_id'] ?? null;
        $markRead = $options['mark_read'] ?? true;

        try {
            $sql = "SELECT * FROM {{$this->tableName}}
                    WHERE to_agent = :to_agent AND status = :status";
            $params = [
                'to_agent' => $agentId,
                'status' => $status
            ];

            if ($messageType) {
                $sql .= " AND message_type = :message_type";
                $params['message_type'] = $messageType;
            }

            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
                $params['user_id'] = $userId;
            }

            $sql .= " ORDER BY priority ASC, created_at ASC LIMIT {$limit}";

            $messages = $this->db->get_records_sql($sql, $params);

            // 읽음 처리
            if ($markRead && !empty($messages)) {
                $ids = array_keys($messages);
                $this->markAsRead($ids);
            }

            // 페이로드 디코딩
            $result = [];
            foreach ($messages as $msg) {
                $msg->payload = json_decode($msg->payload, true);
                $result[] = $msg;
            }

            return $result;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 메시지 수신 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 특정 사용자 관련 메시지 조회
     *
     * @param int $userId 사용자 ID
     * @param int $agentId 에이전트 ID (선택)
     * @param int $limit 최대 조회 수
     * @return array 메시지 배열
     */
    public function getMessagesByUser(int $userId, int $agentId = null, int $limit = 100): array {
        try {
            $sql = "SELECT * FROM {{$this->tableName}} WHERE user_id = :user_id";
            $params = ['user_id' => $userId];

            if ($agentId) {
                $sql .= " AND (from_agent = :from_agent OR to_agent = :to_agent)";
                $params['from_agent'] = $agentId;
                $params['to_agent'] = $agentId;
            }

            $sql .= " ORDER BY created_at DESC LIMIT {$limit}";

            $messages = $this->db->get_records_sql($sql, $params);

            foreach ($messages as &$msg) {
                $msg->payload = json_decode($msg->payload, true);
            }

            return array_values($messages);

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 사용자 메시지 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 메시지 읽음 처리
     *
     * @param array $messageIds 메시지 ID 배열
     * @return bool 성공 여부
     */
    public function markAsRead(array $messageIds): bool {
        if (empty($messageIds)) {
            return true;
        }

        try {
            list($inSql, $params) = $this->db->get_in_or_equal($messageIds, SQL_PARAMS_NAMED);
            $sql = "UPDATE {{$this->tableName}}
                    SET status = 'read'
                    WHERE id {$inSql} AND status = 'pending'";

            $this->db->execute($sql, $params);
            return true;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 읽음 처리 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 메시지 처리 완료 표시
     *
     * @param int $messageId 메시지 ID
     * @param array $result 처리 결과 (선택)
     * @return bool 성공 여부
     */
    public function markAsProcessed(int $messageId, array $result = []): bool {
        try {
            $record = new stdClass();
            $record->id = $messageId;
            $record->status = 'processed';
            $record->processed_at = date('Y-m-d H:i:s');

            if (!empty($result)) {
                $existing = $this->db->get_record($this->tableName, ['id' => $messageId]);
                if ($existing) {
                    $payload = json_decode($existing->payload, true);
                    $payload['_result'] = $result;
                    $record->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
                }
            }

            $this->db->update_record($this->tableName, $record);
            return true;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 처리 완료 표시 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 메시지 처리 실패 표시
     *
     * @param int $messageId 메시지 ID
     * @param string $error 에러 메시지
     * @return bool 성공 여부
     */
    public function markAsFailed(int $messageId, string $error): bool {
        try {
            $existing = $this->db->get_record($this->tableName, ['id' => $messageId]);
            if (!$existing) {
                return false;
            }

            $payload = json_decode($existing->payload, true);
            $payload['_error'] = $error;
            $payload['_failed_at'] = date('Y-m-d H:i:s');

            $record = new stdClass();
            $record->id = $messageId;
            $record->status = 'failed';
            $record->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $record->processed_at = date('Y-m-d H:i:s');

            $this->db->update_record($this->tableName, $record);
            return true;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 실패 표시 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 브로드캐스트 메시지 전송 (모든 에이전트 또는 특정 그룹에게)
     *
     * @param int $fromAgent 발신 에이전트
     * @param string $messageType 메시지 유형
     * @param array $payload 페이로드
     * @param array $toAgents 수신 에이전트 목록 (빈 배열이면 전체)
     * @param int $userId 관련 사용자 ID
     * @return array 전송 결과 [성공 수, 실패 수]
     */
    public function broadcast(
        int $fromAgent,
        string $messageType,
        array $payload,
        array $toAgents = [],
        int $userId = 0
    ): array {
        if (empty($toAgents)) {
            $toAgents = range(1, 21);
        }

        // 자기 자신 제외
        $toAgents = array_diff($toAgents, [$fromAgent]);

        $success = 0;
        $failed = 0;

        foreach ($toAgents as $toAgent) {
            $result = $this->send($fromAgent, $toAgent, $messageType, $payload, $userId);
            if ($result !== false) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [$success, $failed];
    }

    /**
     * 대기 중인 메시지 수 조회
     *
     * @param int $agentId 에이전트 ID
     * @return int 대기 메시지 수
     */
    public function getPendingCount(int $agentId): int {
        try {
            return $this->db->count_records($this->tableName, [
                'to_agent' => $agentId,
                'status' => 'pending'
            ]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 오래된 메시지 정리
     *
     * @param int $daysOld 보관 기간 (일)
     * @param array $statuses 정리할 상태 (기본: processed, failed)
     * @return int 삭제된 메시지 수
     */
    public function cleanup(int $daysOld = 30, array $statuses = ['processed', 'failed']): int {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

            list($inSql, $params) = $this->db->get_in_or_equal($statuses, SQL_PARAMS_NAMED);
            $params['cutoff'] = $cutoff;

            $sql = "DELETE FROM {{$this->tableName}}
                    WHERE status {$inSql} AND created_at < :cutoff";

            $this->db->execute($sql, $params);

            // Moodle DB는 affected_rows를 직접 반환하지 않으므로 0 반환
            return 0;

        } catch (Exception $e) {
            error_log("[AgentMessenger] {$this->currentFile}:" . __LINE__ .
                " - 정리 실패: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 에이전트 ID 유효성 검증
     *
     * @param int $agentId 에이전트 ID
     * @return bool 유효 여부 (1-21)
     */
    private function validateAgentId(int $agentId): bool {
        return $agentId >= 1 && $agentId <= 21;
    }

    /**
     * 지원하는 메시지 유형 목록 반환
     *
     * @return array 메시지 유형 목록
     */
    public function getMessageTypes(): array {
        return $this->messageTypes;
    }
}
