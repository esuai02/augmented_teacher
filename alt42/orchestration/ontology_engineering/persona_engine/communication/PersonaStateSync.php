<?php
/**
 * PersonaStateSync - 에이전트 간 페르소나 상태 동기화
 *
 * 여러 에이전트에서 사용자의 페르소나 상태를 일관성 있게 유지
 * 상태 충돌 해결, 버전 관리, 캐싱
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
require_once(__DIR__ . '/AgentMessageBus.php');

class PersonaStateSync {

    /** @var AgentMessageBus 메시지 버스 */
    private $messageBus;

    /** @var InterAgentProtocol 프로토콜 */
    private $protocol;

    /** @var string 현재 에이전트 ID */
    private $agentId;

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 상태 캐시 */
    private $stateCache = [];

    /** @var int 캐시 TTL (초) */
    private $cacheTtl = 60;

    /** @var string 상태 테이블명 */
    const STATE_TABLE = 'at_agent_persona_state';

    /**
     * 생성자
     *
     * @param string $agentId 현재 에이전트 ID
     * @param bool $debugMode 디버그 모드
     */
    public function __construct(string $agentId, bool $debugMode = false) {
        $this->protocol = new InterAgentProtocol();
        $this->messageBus = new AgentMessageBus($agentId, $debugMode);
        $this->agentId = $agentId;
        $this->debugMode = $debugMode;

        // 상태 업데이트 메시지 구독
        $this->messageBus->subscribe(
            InterAgentProtocol::MESSAGE_TYPES['PERSONA_UPDATE'],
            [$this, 'handlePersonaUpdate']
        );
    }

    /**
     * 현재 에이전트의 페르소나 상태 저장 및 동기화
     *
     * @param int $userId 사용자 ID
     * @param string $personaId 페르소나 ID
     * @param array $stateData 상태 데이터
     * @param bool $broadcast 다른 에이전트에게 알릴지 여부
     * @return bool 성공 여부
     */
    public function saveState(
        int $userId, 
        string $personaId, 
        array $stateData, 
        bool $broadcast = true
    ): bool {
        global $DB;

        try {
            // 버전 번호 증가
            $version = $this->getNextVersion($userId, $this->agentId);

            // 상태에 메타정보 추가
            $stateData['_meta'] = [
                'version' => $version,
                'agent_id' => $this->agentId,
                'updated_at' => time()
            ];

            // 기존 레코드 확인
            $existing = $DB->get_record(self::STATE_TABLE, [
                'userid' => $userId,
                'agent_id' => $this->agentId
            ]);

            $record = new \stdClass();
            $record->userid = $userId;
            $record->agent_id = $this->agentId;
            $record->persona_id = $personaId;
            $record->state_data = json_encode($stateData);
            $record->version = $version;
            $record->timemodified = time();

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record(self::STATE_TABLE, $record);
            } else {
                $record->timecreated = time();
                $DB->insert_record(self::STATE_TABLE, $record);
            }

            // 캐시 업데이트
            $cacheKey = "{$userId}_{$this->agentId}";
            $this->stateCache[$cacheKey] = [
                'data' => $stateData,
                'persona_id' => $personaId,
                'expires' => time() + $this->cacheTtl
            ];

            // 다른 에이전트에게 브로드캐스트
            if ($broadcast) {
                $this->broadcastStateChange($userId, $personaId, $stateData);
            }

            if ($this->debugMode) {
                error_log("[PersonaStateSync DEBUG] 상태 저장: user={$userId}, persona={$personaId}, version={$version}");
            }

            return true;

        } catch (\Exception $e) {
            error_log("[PersonaStateSync ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 상태 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 특정 에이전트의 사용자 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @param string|null $agentId 에이전트 ID (null이면 현재 에이전트)
     * @param bool $useCache 캐시 사용 여부
     * @return array|null 상태 데이터
     */
    public function getState(int $userId, ?string $agentId = null, bool $useCache = true): ?array {
        global $DB;

        $targetAgent = $agentId ?? $this->agentId;
        $cacheKey = "{$userId}_{$targetAgent}";

        // 캐시 확인
        if ($useCache && isset($this->stateCache[$cacheKey])) {
            if ($this->stateCache[$cacheKey]['expires'] > time()) {
                return $this->stateCache[$cacheKey];
            }
            unset($this->stateCache[$cacheKey]);
        }

        try {
            $record = $DB->get_record(self::STATE_TABLE, [
                'userid' => $userId,
                'agent_id' => $targetAgent
            ]);

            if (!$record) {
                return null;
            }

            $state = [
                'persona_id' => $record->persona_id,
                'data' => json_decode($record->state_data, true),
                'version' => (int)$record->version,
                'agent_id' => $record->agent_id,
                'timemodified' => $record->timemodified
            ];

            // 캐시 저장
            $this->stateCache[$cacheKey] = [
                'data' => $state['data'],
                'persona_id' => $state['persona_id'],
                'expires' => time() + $this->cacheTtl
            ];

            return $state;

        } catch (\Exception $e) {
            error_log("[PersonaStateSync ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 상태 조회 실패: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 모든 에이전트의 사용자 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array 에이전트별 상태 목록
     */
    public function getAllStates(int $userId): array {
        global $DB;

        try {
            $records = $DB->get_records(self::STATE_TABLE, ['userid' => $userId]);
            
            $states = [];
            foreach ($records as $record) {
                $states[$record->agent_id] = [
                    'persona_id' => $record->persona_id,
                    'data' => json_decode($record->state_data, true),
                    'version' => (int)$record->version,
                    'timemodified' => $record->timemodified
                ];
            }

            return $states;

        } catch (\Exception $e) {
            error_log("[PersonaStateSync ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 전체 상태 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 상태 변경 브로드캐스트
     *
     * @param int $userId 사용자 ID
     * @param string $personaId 페르소나 ID
     * @param array $stateData 상태 데이터
     */
    private function broadcastStateChange(int $userId, string $personaId, array $stateData): void {
        $this->messageBus->send(
            InterAgentProtocol::MESSAGE_TYPES['PERSONA_UPDATE'],
            'broadcast',
            [
                'user_id' => $userId,
                'persona_id' => $personaId,
                'state_data' => $stateData,
                'change_type' => 'update',
                'source_agent' => $this->agentId
            ],
            InterAgentProtocol::PRIORITY_LEVELS['NORMAL']
        );
    }

    /**
     * 페르소나 업데이트 메시지 핸들러
     *
     * @param array $message 수신된 메시지
     */
    public function handlePersonaUpdate(array $message): void {
        $payload = $message['payload'];
        $userId = $payload['user_id'] ?? null;
        $sourceAgent = $payload['source_agent'] ?? $message['from_agent'];

        if (!$userId || $sourceAgent === $this->agentId) {
            return; // 자기 자신의 메시지는 무시
        }

        // 캐시 무효화
        $cacheKey = "{$userId}_{$sourceAgent}";
        unset($this->stateCache[$cacheKey]);

        if ($this->debugMode) {
            error_log("[PersonaStateSync DEBUG] 상태 업데이트 수신: from={$sourceAgent}, user={$userId}");
        }
    }

    /**
     * 상태 충돌 해결
     *
     * @param int $userId 사용자 ID
     * @param array $agentIds 충돌 해결할 에이전트 목록
     * @return array 병합된 상태
     */
    public function resolveConflicts(int $userId, array $agentIds = []): array {
        $states = $this->getAllStates($userId);
        
        if (empty($states)) {
            return [];
        }

        if (!empty($agentIds)) {
            $states = array_intersect_key($states, array_flip($agentIds));
        }

        // 가장 최근 수정된 상태를 우선
        uasort($states, function($a, $b) {
            return ($b['timemodified'] ?? 0) - ($a['timemodified'] ?? 0);
        });

        // 공통 필드 병합 (최신 우선)
        $merged = [
            'persona_id' => null,
            'data' => [],
            'sources' => []
        ];

        foreach ($states as $agentId => $state) {
            if (!$merged['persona_id']) {
                $merged['persona_id'] = $state['persona_id'];
            }

            // 데이터 병합 (최신 우선이므로 이미 있는 키는 덮어쓰지 않음)
            if (isset($state['data']) && is_array($state['data'])) {
                foreach ($state['data'] as $key => $value) {
                    if (!isset($merged['data'][$key]) && $key !== '_meta') {
                        $merged['data'][$key] = $value;
                    }
                }
            }

            $merged['sources'][] = $agentId;
        }

        return $merged;
    }

    /**
     * 동기화 요청 발송
     *
     * @param int $userId 사용자 ID
     * @param string $toAgent 대상 에이전트
     * @return bool 성공 여부
     */
    public function requestSync(int $userId, string $toAgent): bool {
        return (bool)$this->messageBus->send(
            InterAgentProtocol::MESSAGE_TYPES['SYNC_REQUEST'],
            $toAgent,
            [
                'user_id' => $userId,
                'requesting_agent' => $this->agentId
            ],
            InterAgentProtocol::PRIORITY_LEVELS['NORMAL']
        );
    }

    /**
     * 다음 버전 번호 가져오기
     *
     * @param int $userId 사용자 ID
     * @param string $agentId 에이전트 ID
     * @return int 다음 버전 번호
     */
    private function getNextVersion(int $userId, string $agentId): int {
        global $DB;

        try {
            $record = $DB->get_record(self::STATE_TABLE, [
                'userid' => $userId,
                'agent_id' => $agentId
            ]);

            return $record ? ((int)$record->version + 1) : 1;

        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * 캐시 비우기
     */
    public function clearCache(): void {
        $this->stateCache = [];
    }

    /**
     * 캐시 TTL 설정
     *
     * @param int $seconds TTL (초)
     */
    public function setCacheTtl(int $seconds): void {
        $this->cacheTtl = max(1, $seconds);
    }

    /**
     * 수신 메시지 처리
     *
     * @param int $limit 처리할 메시지 수
     * @return array 처리 결과
     */
    public function processIncomingMessages(int $limit = 10): array {
        return $this->messageBus->processMessages($limit);
    }

    /**
     * 메시지 버스 인스턴스 반환
     *
     * @return AgentMessageBus 메시지 버스
     */
    public function getMessageBus(): AgentMessageBus {
        return $this->messageBus;
    }
}

/*
 * 관련 DB 테이블:
 * - at_agent_persona_state
 *   - id: 자동증가 PK
 *   - userid: 사용자 ID (FK to mdl_user)
 *   - agent_id: 에이전트 ID (agent01~agent21)
 *   - persona_id: 현재 페르소나 ID
 *   - state_data: JSON 상태 데이터
 *   - version: 버전 번호 (충돌 감지용)
 *   - timecreated: 생성 시간
 *   - timemodified: 수정 시간
 *
 * - at_agent_messages (AgentMessageBus에서 사용)
 *
 * 참조 파일:
 * - InterAgentProtocol.php (프로토콜 정의)
 * - AgentMessageBus.php (메시지 전달)
 */
