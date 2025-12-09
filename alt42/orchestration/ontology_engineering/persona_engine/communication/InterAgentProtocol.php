<?php
/**
 * InterAgentProtocol - 에이전트 간 통신 프로토콜 정의
 *
 * 21개 에이전트 간 통신 규약, 메시지 형식, 버전 호환성 관리
 *
 * @package AugmentedTeacher\PersonaEngine\Communication
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\PersonaEngine\Communication;

class InterAgentProtocol {

    /** @var string 프로토콜 버전 */
    const VERSION = '1.0.0';

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 메시지 타입 정의 */
    const MESSAGE_TYPES = [
        'PERSONA_UPDATE'      => 'persona_update',       // 페르소나 상태 변경
        'EMOTION_DETECTED'    => 'emotion_detected',     // 감정 감지 알림
        'CONTEXT_SHARE'       => 'context_share',        // 컨텍스트 공유
        'INTERVENTION_REQUEST'=> 'intervention_request', // 개입 요청
        'LEARNING_EVENT'      => 'learning_event',       // 학습 이벤트
        'STATE_QUERY'         => 'state_query',          // 상태 조회 요청
        'STATE_RESPONSE'      => 'state_response',       // 상태 조회 응답
        'SYNC_REQUEST'        => 'sync_request',         // 동기화 요청
        'SYNC_COMPLETE'       => 'sync_complete',        // 동기화 완료
        'HEARTBEAT'           => 'heartbeat',            // 상태 확인
        'ERROR'               => 'error'                 // 오류 알림
    ];

    /** @var array 우선순위 레벨 */
    const PRIORITY_LEVELS = [
        'CRITICAL' => 1,   // 즉시 처리 필수
        'HIGH'     => 2,   // 빠른 처리 필요
        'NORMAL'   => 3,   // 일반 처리
        'LOW'      => 4,   // 여유 있는 처리
        'BATCH'    => 5    // 배치 처리 가능
    ];

    /** @var array 에이전트 ID 범위 */
    const AGENT_IDS = [
        'agent01' => 'onboarding',
        'agent02' => 'evaluation',
        'agent03' => 'curriculum',
        'agent04' => 'learning_path',
        'agent05' => 'content',
        'agent06' => 'quiz',
        'agent07' => 'feedback',
        'agent08' => 'motivation',
        'agent09' => 'analytics',
        'agent10' => 'parent',
        'agent11' => 'problem_notes',
        'agent12' => 'mentor',
        'agent13' => 'collaboration',
        'agent14' => 'gamification',
        'agent15' => 'accessibility',
        'agent16' => 'notification',
        'agent17' => 'schedule',
        'agent18' => 'resource',
        'agent19' => 'assessment',
        'agent20' => 'report',
        'agent21' => 'integration'
    ];

    /**
     * 메시지 생성
     *
     * @param string $type 메시지 타입
     * @param string $fromAgent 발신 에이전트
     * @param string $toAgent 수신 에이전트 (또는 'broadcast')
     * @param array $payload 메시지 데이터
     * @param int $priority 우선순위
     * @return array 형식화된 메시지
     */
    public function createMessage(
        string $type, 
        string $fromAgent, 
        string $toAgent, 
        array $payload, 
        int $priority = 3
    ): array {
        // 메시지 타입 검증
        if (!in_array($type, self::MESSAGE_TYPES)) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 유효하지 않은 메시지 타입: {$type}"
            );
        }

        // 에이전트 ID 검증
        if (!$this->isValidAgent($fromAgent)) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 유효하지 않은 발신 에이전트: {$fromAgent}"
            );
        }

        if ($toAgent !== 'broadcast' && !$this->isValidAgent($toAgent)) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 유효하지 않은 수신 에이전트: {$toAgent}"
            );
        }

        return [
            'id' => $this->generateMessageId(),
            'version' => self::VERSION,
            'type' => $type,
            'from_agent' => $fromAgent,
            'to_agent' => $toAgent,
            'priority' => $priority,
            'payload' => $payload,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'checksum' => $this->generateChecksum($type, $fromAgent, $toAgent, $payload)
        ];
    }

    /**
     * 메시지 검증
     *
     * @param array $message 메시지 배열
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateMessage(array $message): array {
        $errors = [];
        $required = ['id', 'version', 'type', 'from_agent', 'to_agent', 'payload', 'timestamp'];

        // 필수 필드 확인
        foreach ($required as $field) {
            if (!isset($message[$field])) {
                $errors[] = "필수 필드 누락: {$field}";
            }
        }

        // 버전 호환성 확인
        if (isset($message['version'])) {
            if (!$this->isVersionCompatible($message['version'])) {
                $errors[] = "호환되지 않는 프로토콜 버전: {$message['version']}";
            }
        }

        // 체크섬 검증
        if (isset($message['checksum'])) {
            $expectedChecksum = $this->generateChecksum(
                $message['type'] ?? '',
                $message['from_agent'] ?? '',
                $message['to_agent'] ?? '',
                $message['payload'] ?? []
            );
            if ($message['checksum'] !== $expectedChecksum) {
                $errors[] = "메시지 무결성 검증 실패 (체크섬 불일치)";
            }
        }

        // 메시지 만료 확인 (1시간 이상 된 메시지)
        if (isset($message['timestamp'])) {
            $age = time() - $message['timestamp'];
            if ($age > 3600) {
                $errors[] = "만료된 메시지 (생성 후 {$age}초 경과)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 페르소나 업데이트 메시지 생성
     *
     * @param string $fromAgent 발신 에이전트
     * @param int $userId 사용자 ID
     * @param string $personaId 페르소나 ID
     * @param array $stateData 상태 데이터
     * @return array 메시지
     */
    public function createPersonaUpdateMessage(
        string $fromAgent,
        int $userId,
        string $personaId,
        array $stateData
    ): array {
        return $this->createMessage(
            self::MESSAGE_TYPES['PERSONA_UPDATE'],
            $fromAgent,
            'broadcast',
            [
                'user_id' => $userId,
                'persona_id' => $personaId,
                'state_data' => $stateData,
                'change_type' => 'update'
            ],
            self::PRIORITY_LEVELS['HIGH']
        );
    }

    /**
     * 감정 감지 메시지 생성
     *
     * @param string $fromAgent 발신 에이전트
     * @param int $userId 사용자 ID
     * @param string $emotion 감지된 감정
     * @param float $intensity 강도 (0.0 ~ 1.0)
     * @param array $context 추가 컨텍스트
     * @return array 메시지
     */
    public function createEmotionDetectedMessage(
        string $fromAgent,
        int $userId,
        string $emotion,
        float $intensity,
        array $context = []
    ): array {
        return $this->createMessage(
            self::MESSAGE_TYPES['EMOTION_DETECTED'],
            $fromAgent,
            'broadcast',
            [
                'user_id' => $userId,
                'emotion' => $emotion,
                'intensity' => max(0.0, min(1.0, $intensity)),
                'context' => $context
            ],
            $intensity > 0.7 ? self::PRIORITY_LEVELS['HIGH'] : self::PRIORITY_LEVELS['NORMAL']
        );
    }

    /**
     * 상태 조회 요청 메시지 생성
     *
     * @param string $fromAgent 발신 에이전트
     * @param string $toAgent 수신 에이전트
     * @param int $userId 사용자 ID
     * @param array $fields 조회할 필드들
     * @return array 메시지
     */
    public function createStateQueryMessage(
        string $fromAgent,
        string $toAgent,
        int $userId,
        array $fields = []
    ): array {
        return $this->createMessage(
            self::MESSAGE_TYPES['STATE_QUERY'],
            $fromAgent,
            $toAgent,
            [
                'user_id' => $userId,
                'fields' => $fields,
                'request_id' => uniqid('query_')
            ],
            self::PRIORITY_LEVELS['NORMAL']
        );
    }

    /**
     * 에이전트 유효성 검증
     *
     * @param string $agentId 에이전트 ID
     * @return bool 유효 여부
     */
    public function isValidAgent(string $agentId): bool {
        return isset(self::AGENT_IDS[$agentId]) || $agentId === 'system';
    }

    /**
     * 버전 호환성 확인
     *
     * @param string $version 확인할 버전
     * @return bool 호환 여부
     */
    public function isVersionCompatible(string $version): bool {
        $current = explode('.', self::VERSION);
        $target = explode('.', $version);

        // 메이저 버전이 같으면 호환
        return isset($current[0]) && isset($target[0]) && $current[0] === $target[0];
    }

    /**
     * 메시지 ID 생성
     *
     * @return string 고유 메시지 ID
     */
    private function generateMessageId(): string {
        return sprintf('%s_%s_%s', 
            date('Ymd'), 
            uniqid(),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * 체크섬 생성
     *
     * @param string $type 메시지 타입
     * @param string $from 발신자
     * @param string $to 수신자
     * @param array $payload 페이로드
     * @return string 체크섬
     */
    private function generateChecksum(string $type, string $from, string $to, array $payload): string {
        $data = $type . '|' . $from . '|' . $to . '|' . json_encode($payload);
        return hash('sha256', $data);
    }

    /**
     * 에이전트 이름 조회
     *
     * @param string $agentId 에이전트 ID
     * @return string|null 에이전트 이름
     */
    public function getAgentName(string $agentId): ?string {
        return self::AGENT_IDS[$agentId] ?? null;
    }

    /**
     * 전체 에이전트 목록 조회
     *
     * @return array 에이전트 목록
     */
    public function getAgentList(): array {
        return self::AGENT_IDS;
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_messages (메시지 저장)
 *
 * 참조 파일:
 * - AgentMessageBus.php (메시지 전달)
 * - PersonaStateSync.php (상태 동기화)
 */
