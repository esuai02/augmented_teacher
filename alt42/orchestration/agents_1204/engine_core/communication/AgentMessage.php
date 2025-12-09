<?php
/**
 * AgentMessage.php
 *
 * 에이전트 간 메시지 Data Transfer Object (DTO)
 * 메시지의 구조와 유효성 검증을 담당
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/communication/AgentMessage.php
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../config/engine_config.php');

/**
 * Class AgentMessage
 *
 * 에이전트 간 통신에 사용되는 메시지 객체
 * 불변(Immutable) 객체로 설계되어 메시지 무결성 보장
 */
class AgentMessage
{
    /** @var string 고유 메시지 ID (UUID v4) */
    private $messageId;

    /** @var int 발신 에이전트 번호 (1-21) */
    private $fromAgent;

    /** @var int 수신 에이전트 번호 (1-21, 0=브로드캐스트) */
    private $toAgent;

    /** @var string 메시지 유형 */
    private $messageType;

    /** @var int 우선순위 (1=최고, 10=최저) */
    private $priority;

    /** @var array 메시지 페이로드 */
    private $payload;

    /** @var string 메시지 상태 */
    private $status;

    /** @var string 페이로드 체크섬 (SHA256) */
    private $checksum;

    /** @var int 재시도 횟수 */
    private $retryCount;

    /** @var int 만료 시간 (Unix timestamp) */
    private $expiresAt;

    /** @var int 생성 시간 (Unix timestamp) */
    private $createdAt;

    /** @var int|null 처리 시간 (Unix timestamp) */
    private $processedAt;

    /** @var string|null 처리 결과 */
    private $result;

    /** @var array 유효한 상태값 */
    const VALID_STATUSES = ['pending', 'processing', 'processed', 'failed', 'expired'];

    /**
     * AgentMessage 생성자
     *
     * @param array $data 메시지 데이터
     * @throws InvalidArgumentException 유효하지 않은 데이터인 경우
     */
    public function __construct(array $data)
    {
        $this->validateData($data);

        $this->messageId   = $data['message_id'] ?? $this->generateUUID();
        $this->fromAgent   = (int) $data['from_agent'];
        $this->toAgent     = (int) ($data['to_agent'] ?? 0);
        $this->messageType = $data['message_type'];
        $this->priority    = (int) ($data['priority'] ?? $this->getDefaultPriority($this->messageType));
        $this->payload     = $data['payload'] ?? [];
        $this->status      = $data['status'] ?? 'pending';
        $this->retryCount  = (int) ($data['retry_count'] ?? 0);
        $this->createdAt   = (int) ($data['created_at'] ?? time());
        $this->processedAt = isset($data['processed_at']) ? (int) $data['processed_at'] : null;
        $this->result      = $data['result'] ?? null;

        // TTL 계산
        $ttl = (int) ($data['ttl'] ?? MESSAGE_QUEUE_CONFIG['default_ttl']);
        $this->expiresAt = $this->createdAt + $ttl;

        // 체크섬 생성
        $this->checksum = $this->calculateChecksum();
    }

    /**
     * 데이터 유효성 검증
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        // 필수 필드 검증
        if (!isset($data['from_agent'])) {
            throw new InvalidArgumentException(
                "[AgentMessage.php:" . __LINE__ . "] 필수 필드 'from_agent' 누락"
            );
        }

        if (!isset($data['message_type'])) {
            throw new InvalidArgumentException(
                "[AgentMessage.php:" . __LINE__ . "] 필수 필드 'message_type' 누락"
            );
        }

        // 에이전트 번호 범위 검증
        $fromAgent = (int) $data['from_agent'];
        if ($fromAgent < 1 || $fromAgent > 21) {
            throw new InvalidArgumentException(
                "[AgentMessage.php:" . __LINE__ . "] 발신 에이전트 번호가 유효 범위(1-21)를 벗어남: {$fromAgent}"
            );
        }

        $toAgent = (int) ($data['to_agent'] ?? 0);
        if ($toAgent < 0 || $toAgent > 21) {
            throw new InvalidArgumentException(
                "[AgentMessage.php:" . __LINE__ . "] 수신 에이전트 번호가 유효 범위(0-21)를 벗어남: {$toAgent}"
            );
        }

        // 우선순위 범위 검증
        if (isset($data['priority'])) {
            $priority = (int) $data['priority'];
            if ($priority < 1 || $priority > 10) {
                throw new InvalidArgumentException(
                    "[AgentMessage.php:" . __LINE__ . "] 우선순위가 유효 범위(1-10)를 벗어남: {$priority}"
                );
            }
        }

        // 상태 검증
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            throw new InvalidArgumentException(
                "[AgentMessage.php:" . __LINE__ . "] 유효하지 않은 상태: {$data['status']}"
            );
        }
    }

    /**
     * 메시지 유형별 기본 우선순위 반환
     *
     * @param string $messageType
     * @return int
     */
    private function getDefaultPriority(string $messageType): int
    {
        if (isset(MESSAGE_TYPES[$messageType])) {
            return MESSAGE_TYPES[$messageType]['default_priority'];
        }
        return MESSAGE_QUEUE_CONFIG['default_priority'];
    }

    /**
     * UUID v4 생성
     *
     * @return string
     */
    private function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 페이로드 체크섬 계산
     *
     * @return string
     */
    private function calculateChecksum(): string
    {
        return hash('sha256', json_encode($this->payload));
    }

    /**
     * 체크섬 검증
     *
     * @return bool
     */
    public function verifyChecksum(): bool
    {
        return $this->checksum === $this->calculateChecksum();
    }

    /**
     * 메시지 만료 여부 확인
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return time() > $this->expiresAt;
    }

    /**
     * 재시도 가능 여부 확인
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->retryCount < MESSAGE_QUEUE_CONFIG['max_retry_count'];
    }

    /**
     * 긴급 메시지 여부 확인
     *
     * @return bool
     */
    public function isUrgent(): bool
    {
        return $this->priority <= MESSAGE_PRIORITY['URGENT'];
    }

    /**
     * 브로드캐스트 메시지 여부 확인
     *
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return $this->toAgent === 0;
    }

    // =========================================================================
    // Getter 메서드
    // =========================================================================

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getFromAgent(): int
    {
        return $this->fromAgent;
    }

    public function getToAgent(): int
    {
        return $this->toAgent;
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?int
    {
        return $this->processedAt;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * 발신 에이전트 정보 반환
     *
     * @return array|null
     */
    public function getFromAgentInfo(): ?array
    {
        return get_agent_info($this->fromAgent);
    }

    /**
     * 수신 에이전트 정보 반환
     *
     * @return array|null
     */
    public function getToAgentInfo(): ?array
    {
        if ($this->toAgent === 0) {
            return ['name' => 'broadcast', 'kr_name' => '전체', 'category' => 'system'];
        }
        return get_agent_info($this->toAgent);
    }

    // =========================================================================
    // 변환 메서드
    // =========================================================================

    /**
     * 배열로 변환
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message_id'    => $this->messageId,
            'from_agent'    => $this->fromAgent,
            'to_agent'      => $this->toAgent,
            'message_type'  => $this->messageType,
            'priority'      => $this->priority,
            'payload'       => $this->payload,
            'status'        => $this->status,
            'checksum'      => $this->checksum,
            'retry_count'   => $this->retryCount,
            'expires_at'    => $this->expiresAt,
            'created_at'    => $this->createdAt,
            'processed_at'  => $this->processedAt,
            'result'        => $this->result,
        ];
    }

    /**
     * DB 저장용 배열로 변환
     *
     * @return array
     */
    public function toDbArray(): array
    {
        return [
            'message_id'    => $this->messageId,
            'from_agent'    => $this->fromAgent,
            'to_agent'      => $this->toAgent,
            'message_type'  => $this->messageType,
            'priority'      => $this->priority,
            'payload'       => json_encode($this->payload),
            'status'        => $this->status,
            'checksum'      => $this->checksum,
            'retry_count'   => $this->retryCount,
            'expires_at'    => date('Y-m-d H:i:s', $this->expiresAt),
            'timecreated'   => $this->createdAt,
            'timeprocessed' => $this->processedAt,
        ];
    }

    /**
     * JSON으로 변환
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * DB 레코드에서 AgentMessage 생성
     *
     * @param object $record DB 레코드
     * @return AgentMessage
     */
    public static function fromDbRecord(object $record): AgentMessage
    {
        return new self([
            'message_id'   => $record->message_id,
            'from_agent'   => $record->from_agent,
            'to_agent'     => $record->to_agent,
            'message_type' => $record->message_type,
            'priority'     => $record->priority,
            'payload'      => json_decode($record->payload, true) ?: [],
            'status'       => $record->status,
            'retry_count'  => $record->retry_count ?? 0,
            'created_at'   => $record->timecreated,
            'processed_at' => $record->timeprocessed,
            'ttl'          => strtotime($record->expires_at) - $record->timecreated,
        ]);
    }

    /**
     * 재시도용 새 메시지 생성
     *
     * @return AgentMessage
     */
    public function createRetryMessage(): AgentMessage
    {
        $data = $this->toArray();
        $data['status'] = 'pending';
        $data['retry_count'] = $this->retryCount + 1;
        $data['created_at'] = time();
        $data['processed_at'] = null;
        $data['result'] = null;

        return new self($data);
    }

    /**
     * 상태 변경된 새 메시지 생성 (불변 객체 유지)
     *
     * @param string      $newStatus    새 상태
     * @param string|null $result       처리 결과
     * @return AgentMessage
     */
    public function withStatus(string $newStatus, ?string $result = null): AgentMessage
    {
        $data = $this->toArray();
        $data['status'] = $newStatus;
        $data['result'] = $result;

        if ($newStatus === 'processed' || $newStatus === 'failed') {
            $data['processed_at'] = time();
        }

        return new self($data);
    }

    /**
     * 디버그용 문자열 표현
     *
     * @return string
     */
    public function __toString(): string
    {
        $fromInfo = $this->getFromAgentInfo();
        $toInfo = $this->getToAgentInfo();

        return sprintf(
            "[Message:%s] %s(%d) -> %s(%d) | Type: %s | Priority: %d | Status: %s",
            substr($this->messageId, 0, 8),
            $fromInfo['name'] ?? 'unknown',
            $this->fromAgent,
            $toInfo['name'] ?? 'unknown',
            $this->toAgent,
            $this->messageType,
            $this->priority,
            $this->status
        );
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 메시지 유형 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // 1. 일반 메시지 생성
 * $msg = new AgentMessage([
 *     'from_agent'   => 5,                      // Agent05 (학습 감정)
 *     'to_agent'     => 8,                      // Agent08 (평온도)
 *     'message_type' => 'frustration_detected',
 *     'payload'      => [
 *         'user_id'   => 123,
 *         'emotion'   => 'frustration',
 *         'intensity' => 0.85,
 *         'context'   => 'math_problem_solving'
 *     ]
 * ]);
 *
 * // 2. 긴급 메시지 생성 (우선순위 명시)
 * $urgentMsg = new AgentMessage([
 *     'from_agent'   => 13,                     // Agent13 (학습 이탈)
 *     'to_agent'     => 0,                      // 브로드캐스트
 *     'message_type' => 'dropout_risk',
 *     'priority'     => 1,                      // CRITICAL
 *     'payload'      => [
 *         'user_id'    => 456,
 *         'risk_level' => 'high',
 *         'inactive_days' => 7
 *     ],
 *     'ttl'          => 300                     // 5분 유효
 * ]);
 *
 * // 3. DB에서 메시지 복원
 * $dbRecord = $DB->get_record('mdl_at_agent_messages', ['message_id' => 'xxx']);
 * $restoredMsg = AgentMessage::fromDbRecord($dbRecord);
 *
 * // 4. 상태 확인
 * if ($msg->isExpired()) { ... }
 * if ($msg->canRetry()) { ... }
 * if ($msg->isUrgent()) { ... }
 * if ($msg->isBroadcast()) { ... }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
