<?php
/**
 * AgentCommunicator - 에이전트 간 통신 관리
 * 
 * DB 기반 에이전트 간 메시지 송수신 및 페르소나 상태 공유
 * 
 * @package AugmentedTeacher\PersonaEngine\Communication
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class AgentCommunicator {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var string 현재 에이전트 ID */
    protected $agentId;

    /** @var array 메시지 타입 정의 */
    protected $messageTypes = [
        'persona_change' => '페르소나 변경 알림',
        'state_sync' => '상태 동기화 요청',
        'event' => '일반 이벤트',
        'request' => '요청',
        'response' => '응답',
        'broadcast' => '전체 브로드캐스트'
    ];

    /** @var int 기본 메시지 만료 시간 (초) */
    protected $defaultExpireSeconds = 3600;

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID
     */
    public function __construct(string $agentId) {
        $this->agentId = $agentId;
    }

    /**
     * 메시지 발송
     *
     * @param string|null $targetAgent 대상 에이전트 (null=브로드캐스트)
     * @param string $messageType 메시지 타입
     * @param array $payload 페이로드
     * @param array $options 추가 옵션
     * @return int|bool 메시지 ID 또는 false
     */
    public function sendMessage(?string $targetAgent, string $messageType, array $payload, array $options = []) {
        global $DB;

        try {
            $now = date('Y-m-d H:i:s');
            $expireSeconds = $options['expires_in'] ?? $this->defaultExpireSeconds;

            $record = new stdClass();
            $record->source_agent = $this->agentId;
            $record->target_agent = $targetAgent;
            $record->user_id = $options['user_id'] ?? null;
            $record->message_type = $messageType;
            $record->event_name = $options['event_name'] ?? null;
            $record->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $record->priority = $options['priority'] ?? 5;
            $record->status = 'pending';
            $record->expires_at = date('Y-m-d H:i:s', time() + $expireSeconds);
            $record->retry_count = 0;
            $record->created_at = $now;

            $messageId = $DB->insert_record('at_agent_messages', $record);
            
            $this->logDebug("메시지 발송: {$messageType} -> {$targetAgent}", [
                'message_id' => $messageId,
                'payload_keys' => array_keys($payload)
            ]);

            return $messageId;

        } catch (Exception $e) {
            $this->logError("메시지 발송 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 브로드캐스트 메시지 발송 (모든 에이전트 대상)
     *
     * @param string $eventName 이벤트 이름
     * @param array $payload 페이로드
     * @param array $options 추가 옵션
     * @return int|bool 메시지 ID 또는 false
     */
    public function broadcast(string $eventName, array $payload, array $options = []) {
        $options['event_name'] = $eventName;
        return $this->sendMessage(null, 'broadcast', $payload, $options);
    }

    /**
     * 페르소나 변경 알림
     *
     * @param int $userId 사용자 ID
     * @param string $oldPersona 이전 페르소나
     * @param string $newPersona 새 페르소나
     * @param array $context 추가 컨텍스트
     * @return int|bool
     */
    public function notifyPersonaChange(int $userId, string $oldPersona, string $newPersona, array $context = []) {
        return $this->broadcast('persona_changed', [
            'user_id' => $userId,
            'old_persona' => $oldPersona,
            'new_persona' => $newPersona,
            'changed_by' => $this->agentId,
            'context' => $context,
            'timestamp' => time()
        ], ['user_id' => $userId, 'priority' => 3]);
    }

    /**
     * 대기 중인 메시지 조회
     *
     * @param int $limit 최대 조회 수
     * @return array 메시지 목록
     */
    public function getPendingMessages(int $limit = 50): array {
        global $DB;

        try {
            $now = date('Y-m-d H:i:s');
            
            $sql = "SELECT * FROM {at_agent_messages} 
                    WHERE (target_agent = ? OR target_agent IS NULL)
                    AND status = 'pending'
                    AND (expires_at IS NULL OR expires_at > ?)
                    ORDER BY priority ASC, created_at ASC
                    LIMIT ?";

            $messages = $DB->get_records_sql($sql, [$this->agentId, $now, $limit]);
            
            $result = [];
            foreach ($messages as $msg) {
                $msg->payload = json_decode($msg->payload, true);
                $result[] = $msg;
            }

            return $result;

        } catch (Exception $e) {
            $this->logError("메시지 조회 실패: " . $e->getMessage(), __LINE__);
            return [];
        }
    }

    /**
     * 메시지 처리 완료 표시
     *
     * @param int $messageId 메시지 ID
     * @param bool $success 성공 여부
     * @param string|null $errorMessage 오류 메시지
     * @return bool
     */
    public function markProcessed(int $messageId, bool $success = true, ?string $errorMessage = null): bool {
        global $DB;

        try {
            $update = new stdClass();
            $update->id = $messageId;
            $update->status = $success ? 'processed' : 'failed';
            $update->processed_at = date('Y-m-d H:i:s');
            
            if ($errorMessage) {
                $update->error_message = $errorMessage;
            }

            $DB->update_record('at_agent_messages', $update);
            return true;

        } catch (Exception $e) {
            $this->logError("메시지 상태 업데이트 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 다른 에이전트의 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @param string|null $targetAgent 특정 에이전트 (null=전체)
     * @return array 페르소나 상태 목록
     */
    public function getPersonaStates(int $userId, ?string $targetAgent = null): array {
        global $DB;

        try {
            $params = ['user_id' => $userId];
            
            if ($targetAgent) {
                $params['agent_id'] = $targetAgent;
                $states = $DB->get_records('at_agent_persona_state', $params);
            } else {
                $states = $DB->get_records('at_agent_persona_state', $params);
            }

            $result = [];
            foreach ($states as $state) {
                $state->context_data = json_decode($state->context_data, true);
                $result[$state->agent_id] = $state;
            }

            return $result;

        } catch (Exception $e) {
            $this->logError("페르소나 상태 조회 실패: " . $e->getMessage(), __LINE__);
            return [];
        }
    }

    /**
     * 자신의 페르소나 상태 업데이트
     *
     * @param int $userId 사용자 ID
     * @param array $stateData 상태 데이터
     * @return bool
     */
    public function updateMyPersonaState(int $userId, array $stateData): bool {
        global $DB;

        try {
            $now = date('Y-m-d H:i:s');
            
            $existing = $DB->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'agent_id' => $this->agentId
            ]);

            if ($existing) {
                $existing->persona_id = $stateData['persona_id'] ?? $existing->persona_id;
                $existing->confidence = $stateData['confidence'] ?? $existing->confidence;
                $existing->situation_code = $stateData['situation_code'] ?? $existing->situation_code;
                $existing->emotional_state = $stateData['emotional_state'] ?? $existing->emotional_state;
                $existing->intent = $stateData['intent'] ?? $existing->intent;
                $existing->context_data = isset($stateData['context_data']) 
                    ? json_encode($stateData['context_data'], JSON_UNESCAPED_UNICODE) 
                    : $existing->context_data;
                $existing->session_id = $stateData['session_id'] ?? $existing->session_id;
                $existing->updated_at = $now;
                
                $DB->update_record('at_agent_persona_state', $existing);
            } else {
                $record = new stdClass();
                $record->user_id = $userId;
                $record->agent_id = $this->agentId;
                $record->persona_id = $stateData['persona_id'] ?? 'default';
                $record->confidence = $stateData['confidence'] ?? 0.5;
                $record->situation_code = $stateData['situation_code'] ?? 'default';
                $record->emotional_state = $stateData['emotional_state'] ?? 'neutral';
                $record->intent = $stateData['intent'] ?? null;
                $record->context_data = json_encode($stateData['context_data'] ?? [], JSON_UNESCAPED_UNICODE);
                $record->session_id = $stateData['session_id'] ?? null;
                $record->created_at = $now;
                $record->updated_at = $now;
                
                $DB->insert_record('at_agent_persona_state', $record);
            }

            return true;

        } catch (Exception $e) {
            $this->logError("페르소나 상태 업데이트 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 만료된 메시지 정리
     *
     * @return int 정리된 메시지 수
     */
    public function cleanupExpiredMessages(): int {
        global $DB;

        try {
            $now = date('Y-m-d H:i:s');
            
            $sql = "UPDATE {at_agent_messages} 
                    SET status = 'expired' 
                    WHERE status = 'pending' 
                    AND expires_at IS NOT NULL 
                    AND expires_at < ?";
            
            $DB->execute($sql, [$now]);
            
            // 처리된 메시지 중 7일 이상 된 것 삭제
            $oldDate = date('Y-m-d H:i:s', strtotime('-7 days'));
            $deleteSql = "DELETE FROM {at_agent_messages} 
                          WHERE status IN ('processed', 'expired', 'failed') 
                          AND created_at < ?";
            
            $DB->execute($deleteSql, [$oldDate]);
            
            return 1; // 성공 표시

        } catch (Exception $e) {
            $this->logError("메시지 정리 실패: " . $e->getMessage(), __LINE__);
            return 0;
        }
    }

    /**
     * 에러 로깅
     */
    protected function logError(string $message, int $line): void {
        error_log("[{$this->agentId}:AgentCommunicator ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 디버그 로깅
     */
    protected function logDebug(string $message, array $context = []): void {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[{$this->agentId}:AgentCommunicator DEBUG] {$message} " . json_encode($context));
        }
    }

    /**
     * 에이전트 ID getter
     */
    public function getAgentId(): string {
        return $this->agentId;
    }
}

/**
 * 관련 DB 테이블:
 * - mdl_at_agent_messages: 에이전트 간 메시지 큐
 *   - id: BIGINT PK
 *   - source_agent: VARCHAR(50) 발신 에이전트
 *   - target_agent: VARCHAR(50) 수신 에이전트 (NULL=브로드캐스트)
 *   - user_id: BIGINT 관련 사용자
 *   - message_type: VARCHAR(50) 메시지 유형
 *   - payload: JSON 페이로드
 *   - status: ENUM('pending','processing','processed','failed','expired')
 *   - created_at: DATETIME
 * 
 * - mdl_at_agent_persona_state: 에이전트별 페르소나 상태
 *   - id: BIGINT PK
 *   - user_id: BIGINT
 *   - agent_id: VARCHAR(50)
 *   - persona_id: VARCHAR(100)
 *   - confidence: DECIMAL(5,4)
 *   - emotional_state: VARCHAR(50)
 */
