<?php
/**
 * AgentMessageBus - 에이전트 간 메시지 버스
 *
 * 21개 에이전트 간 비동기 메시지 전달, 큐 관리, 구독 패턴 지원
 * DB 기반 pub/sub 구현
 *
 * @package AugmentedTeacher\PersonaEngine\Communication
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\PersonaEngine\Communication;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB;

require_once(__DIR__ . '/InterAgentProtocol.php');

class AgentMessageBus {

    /** @var InterAgentProtocol 프로토콜 인스턴스 */
    private $protocol;

    /** @var string 현재 에이전트 ID */
    private $agentId;

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 구독 핸들러 */
    private $subscribers = [];

    /** @var string 메시지 테이블명 */
    const MESSAGE_TABLE = 'at_agent_messages';

    /**
     * 생성자
     *
     * @param string $agentId 현재 에이전트 ID
     * @param bool $debugMode 디버그 모드
     */
    public function __construct(string $agentId, bool $debugMode = false) {
        $this->protocol = new InterAgentProtocol();
        $this->agentId = $agentId;
        $this->debugMode = $debugMode;

        if (!$this->protocol->isValidAgent($agentId)) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 유효하지 않은 에이전트 ID: {$agentId}"
            );
        }
    }

    /**
     * 메시지 발송
     *
     * @param string $type 메시지 타입
     * @param string $toAgent 수신 에이전트 (또는 'broadcast')
     * @param array $payload 페이로드
     * @param int $priority 우선순위
     * @return string|false 메시지 ID 또는 실패
     */
    public function send(
        string $type, 
        string $toAgent, 
        array $payload, 
        int $priority = 3
    ) {
        global $DB;

        try {
            // 프로토콜에 맞는 메시지 생성
            $message = $this->protocol->createMessage(
                $type, 
                $this->agentId, 
                $toAgent, 
                $payload, 
                $priority
            );

            // 브로드캐스트인 경우 모든 에이전트에게 전송
            if ($toAgent === 'broadcast') {
                return $this->broadcast($message);
            }

            // 단일 수신자에게 전송
            return $this->saveMessage($message, $toAgent);

        } catch (\Exception $e) {
            error_log("[AgentMessageBus ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 메시지 발송 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 브로드캐스트 메시지 발송
     *
     * @param array $message 메시지
     * @return string 메시지 ID
     */
    private function broadcast(array $message): string {
        $agents = $this->protocol->getAgentList();
        
        foreach ($agents as $agentId => $agentName) {
            if ($agentId !== $this->agentId) {
                $this->saveMessage($message, $agentId);
            }
        }

        return $message['id'];
    }

    /**
     * 메시지 DB 저장
     *
     * @param array $message 메시지
     * @param string $toAgent 수신 에이전트
     * @return string 메시지 ID
     */
    private function saveMessage(array $message, string $toAgent): string {
        global $DB;

        $record = new \stdClass();
        $record->message_id = $message['id'];
        $record->from_agent = $message['from_agent'];
        $record->to_agent = $toAgent;
        $record->message_type = $message['type'];
        $record->priority = $message['priority'];
        $record->payload = json_encode($message['payload']);
        $record->status = 'pending';
        $record->checksum = $message['checksum'];
        $record->protocol_version = $message['version'];
        $record->timecreated = $message['timestamp'];
        $record->timeprocessed = null;
        $record->retry_count = 0;

        $DB->insert_record(self::MESSAGE_TABLE, $record);

        if ($this->debugMode) {
            error_log("[AgentMessageBus DEBUG] 메시지 저장: {$message['id']} -> {$toAgent}");
        }

        return $message['id'];
    }

    /**
     * 대기 중인 메시지 수신
     *
     * @param int $limit 최대 수신 개수
     * @param array $types 필터할 메시지 타입들 (빈 배열이면 전체)
     * @return array 메시지 목록
     */
    public function receive(int $limit = 10, array $types = []): array {
        global $DB;

        try {
            $sql = "SELECT * FROM {" . self::MESSAGE_TABLE . "} 
                    WHERE to_agent = ? AND status = 'pending'";
            $params = [$this->agentId];

            // 메시지 타입 필터링
            if (!empty($types)) {
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $sql .= " AND message_type IN ({$placeholders})";
                $params = array_merge($params, $types);
            }

            $sql .= " ORDER BY priority ASC, timecreated ASC LIMIT {$limit}";

            $records = $DB->get_records_sql($sql, $params);

            $messages = [];
            foreach ($records as $record) {
                $message = [
                    'id' => $record->message_id,
                    'from_agent' => $record->from_agent,
                    'to_agent' => $record->to_agent,
                    'type' => $record->message_type,
                    'priority' => (int)$record->priority,
                    'payload' => json_decode($record->payload, true),
                    'timestamp' => $record->timecreated,
                    'version' => $record->protocol_version,
                    'checksum' => $record->checksum,
                    'db_id' => $record->id
                ];

                // 메시지 검증
                $validation = $this->protocol->validateMessage($message);
                if ($validation['valid']) {
                    $messages[] = $message;
                } else {
                    $this->markMessageStatus($record->id, 'invalid', 
                        implode(', ', $validation['errors']));
                }
            }

            return $messages;

        } catch (\Exception $e) {
            error_log("[AgentMessageBus ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 메시지 수신 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 메시지 처리 완료 표시
     *
     * @param int $dbId DB 레코드 ID
     * @param string $status 상태 ('processed', 'failed', 'invalid')
     * @param string|null $errorMessage 오류 메시지
     * @return bool 성공 여부
     */
    public function acknowledge(int $dbId, string $status = 'processed', ?string $errorMessage = null): bool {
        return $this->markMessageStatus($dbId, $status, $errorMessage);
    }

    /**
     * 메시지 상태 업데이트
     *
     * @param int $dbId DB 레코드 ID
     * @param string $status 상태
     * @param string|null $errorMessage 오류 메시지
     * @return bool 성공 여부
     */
    private function markMessageStatus(int $dbId, string $status, ?string $errorMessage = null): bool {
        global $DB;

        try {
            $record = new \stdClass();
            $record->id = $dbId;
            $record->status = $status;
            $record->timeprocessed = time();
            
            if ($errorMessage) {
                $record->error_message = $errorMessage;
            }

            if ($status === 'failed') {
                // 실패 시 재시도 카운트 증가
                $DB->execute(
                    "UPDATE {" . self::MESSAGE_TABLE . "} 
                     SET status = ?, timeprocessed = ?, retry_count = retry_count + 1 
                     WHERE id = ?",
                    [$status, time(), $dbId]
                );
            } else {
                $DB->update_record(self::MESSAGE_TABLE, $record);
            }

            return true;

        } catch (\Exception $e) {
            error_log("[AgentMessageBus ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 상태 업데이트 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 메시지 핸들러 등록 (구독)
     *
     * @param string $messageType 메시지 타입
     * @param callable $handler 핸들러 함수
     */
    public function subscribe(string $messageType, callable $handler): void {
        if (!isset($this->subscribers[$messageType])) {
            $this->subscribers[$messageType] = [];
        }
        $this->subscribers[$messageType][] = $handler;
    }

    /**
     * 등록된 핸들러로 메시지 처리
     *
     * @param int $limit 처리할 메시지 수
     * @return array 처리 결과
     */
    public function processMessages(int $limit = 10): array {
        $messages = $this->receive($limit);
        $results = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($messages as $message) {
            $type = $message['type'];
            
            if (!isset($this->subscribers[$type]) || empty($this->subscribers[$type])) {
                $results['skipped']++;
                continue;
            }

            try {
                // 등록된 모든 핸들러 실행
                foreach ($this->subscribers[$type] as $handler) {
                    call_user_func($handler, $message);
                }

                $this->acknowledge($message['db_id'], 'processed');
                $results['processed']++;

            } catch (\Exception $e) {
                $this->acknowledge($message['db_id'], 'failed', $e->getMessage());
                $results['failed']++;

                error_log("[AgentMessageBus ERROR] {$this->currentFile}:" . __LINE__ . 
                          " - 메시지 처리 실패: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 오래된 메시지 정리
     *
     * @param int $olderThanDays 며칠 이전 메시지 삭제
     * @return int 삭제된 레코드 수
     */
    public function cleanup(int $olderThanDays = 7): int {
        global $DB;

        try {
            $threshold = time() - ($olderThanDays * 86400);
            
            $count = $DB->count_records_select(
                self::MESSAGE_TABLE, 
                "timecreated < ? AND status IN ('processed', 'invalid')",
                [$threshold]
            );

            $DB->delete_records_select(
                self::MESSAGE_TABLE,
                "timecreated < ? AND status IN ('processed', 'invalid')",
                [$threshold]
            );

            return $count;

        } catch (\Exception $e) {
            error_log("[AgentMessageBus ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 정리 실패: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 대기 중인 메시지 수 조회
     *
     * @return int 대기 메시지 수
     */
    public function getPendingCount(): int {
        global $DB;

        try {
            return $DB->count_records(self::MESSAGE_TABLE, [
                'to_agent' => $this->agentId,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 현재 에이전트 ID 조회
     *
     * @return string 에이전트 ID
     */
    public function getAgentId(): string {
        return $this->agentId;
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_messages
 *   - id: 자동증가 PK
 *   - message_id: 고유 메시지 ID (문자열)
 *   - from_agent: 발신 에이전트
 *   - to_agent: 수신 에이전트
 *   - message_type: 메시지 타입
 *   - priority: 우선순위 (1-5)
 *   - payload: JSON 페이로드
 *   - status: 상태 (pending/processed/failed/invalid)
 *   - checksum: 무결성 체크섬
 *   - protocol_version: 프로토콜 버전
 *   - error_message: 오류 메시지 (nullable)
 *   - retry_count: 재시도 횟수
 *   - timecreated: 생성 시간
 *   - timeprocessed: 처리 시간 (nullable)
 *
 * 참조 파일:
 * - InterAgentProtocol.php (프로토콜 정의)
 * - PersonaStateSync.php (상태 동기화)
 */
