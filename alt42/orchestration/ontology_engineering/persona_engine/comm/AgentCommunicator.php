<?php
/**
 * AgentCommunicator - 에이전트 간 통신 모듈
 *
 * 21개 에이전트 간의 메시지 교환 및 이벤트 전달을 담당합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Comm
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class AgentCommunicator {

    /** @var string 현재 에이전트 ID */
    private $agentId;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 설정 */
    private $config = [
        'log_enabled' => true,
        'message_ttl' => 86400,
        'max_retries' => 3
    ];

    public function __construct(string $agentId, array $config = []) {
        $this->agentId = $agentId;
        $this->config = array_merge($this->config, $config);
    }

    public function sendMessage(string $targetAgent, string $messageType, array $payload, int $priority = 5): array {
        global $DB;

        try {
            $message = new stdClass();
            $message->source_agent = $this->agentId;
            $message->target_agent = $targetAgent;
            $message->message_type = $messageType;
            $message->payload = json_encode($payload);
            $message->priority = min(10, max(0, $priority));
            $message->status = 'pending';
            $message->created_at = time();
            $message->expires_at = time() + $this->config['message_ttl'];

            $messageId = $DB->insert_record('at_agent_messages', $message);

            return ['success' => true, 'message_id' => $messageId, 'target_agent' => $targetAgent];

        } catch (Exception $e) {
            $this->logError("메시지 전송 실패: " . $e->getMessage(), __LINE__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessages(array $types = [], int $limit = 10): array {
        global $DB;

        try {
            $sql = "SELECT * FROM {at_agent_messages} WHERE target_agent = ? AND status = 'pending' AND (expires_at > ? OR expires_at IS NULL)";
            $params = [$this->agentId, time()];

            if (!empty($types)) {
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $sql .= " AND message_type IN ({$placeholders})";
                $params = array_merge($params, $types);
            }

            $sql .= " ORDER BY priority DESC, created_at ASC LIMIT {$limit}";
            $messages = $DB->get_records_sql($sql, $params);

            $result = [];
            foreach ($messages as $msg) {
                $result[] = [
                    'id' => $msg->id,
                    'source_agent' => $msg->source_agent,
                    'message_type' => $msg->message_type,
                    'payload' => json_decode($msg->payload, true),
                    'priority' => $msg->priority,
                    'created_at' => $msg->created_at
                ];
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }

    public function acknowledgeMessage(int $messageId, string $status = 'processed', array $response = []): bool {
        global $DB;

        try {
            $update = new stdClass();
            $update->id = $messageId;
            $update->status = $status;
            $update->response = json_encode($response);
            $update->processed_at = time();

            $DB->update_record('at_agent_messages', $update);
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function broadcast(string $messageType, array $payload, array $excludeAgents = []): array {
        $results = [];
        $excludeAgents[] = $this->agentId;

        for ($i = 1; $i <= 21; $i++) {
            $targetAgent = 'agent' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!in_array($targetAgent, $excludeAgents)) {
                $results[$targetAgent] = $this->sendMessage($targetAgent, $messageType, $payload, 3);
            }
        }

        return $results;
    }

    public function multicast(array $targetAgents, string $messageType, array $payload): array {
        $results = [];
        foreach ($targetAgents as $targetAgent) {
            if ($targetAgent !== $this->agentId) {
                $results[$targetAgent] = $this->sendMessage($targetAgent, $messageType, $payload);
            }
        }
        return $results;
    }

    private function logError(string $message, int $line): void {
        if ($this->config['log_enabled']) {
            error_log("[AgentCommunicator:{$this->agentId} ERROR] {$this->currentFile}:{$line} - {$message}");
        }
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_at_agent_messages
 *   - id INT PRIMARY KEY AUTO_INCREMENT
 *   - source_agent VARCHAR(20)
 *   - target_agent VARCHAR(20)
 *   - message_type VARCHAR(50)
 *   - payload TEXT
 *   - priority INT DEFAULT 5
 *   - status ENUM('pending','processed','failed','skipped')
 *   - response TEXT
 *   - created_at INT
 *   - processed_at INT
 *   - expires_at INT
 */
