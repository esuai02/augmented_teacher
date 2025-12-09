<?php
/**
 * AgentStateSync - 에이전트 상태 동기화 시스템
 *
 * 에이전트 간 페르소나 상태를 동기화하고 공유합니다.
 * 특정 사용자에 대한 페르소나 상태를 여러 에이전트가 일관성 있게 유지하도록 합니다.
 *
 * @package PersonaEngine\Communication
 * @version 1.0.0
 * @author ALT42 Orchestration System
 *
 * 사용 예시:
 * ```php
 * $sync = new AgentStateSync($DB);
 * $sync->updateState(6, $userId, ['persona_id' => 'T_P1', 'tone' => 'supportive']);
 * $state = $sync->getState(6, $userId);
 * ```
 *
 * 관련 DB 테이블:
 * - at_agent_persona_state: 에이전트별 페르소나 상태
 *   - id (int): 레코드 ID
 *   - agent_id (int): 에이전트 번호 (1-21)
 *   - user_id (int): Moodle 사용자 ID
 *   - persona_id (varchar): 현재 페르소나 ID
 *   - context_data (text): JSON 컨텍스트 데이터
 *   - last_interaction (datetime): 마지막 상호작용 시간
 *   - created_at (datetime): 생성 시간
 *   - updated_at (datetime): 업데이트 시간
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB;

class AgentStateSync {

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var string 상태 테이블명 */
    private $tableName = 'at_agent_persona_state';

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 캐시 */
    private $cache = [];

    /** @var bool 캐시 사용 여부 */
    private $cacheEnabled = true;

    /**
     * 생성자
     *
     * @param object $db Moodle DB 객체
     * @param bool $cacheEnabled 캐시 사용 여부
     */
    public function __construct($db = null, bool $cacheEnabled = true) {
        global $DB;
        $this->db = $db ?? $DB;
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * 페르소나 상태 조회
     *
     * @param int $agentId 에이전트 번호 (1-21)
     * @param int $userId Moodle 사용자 ID
     * @return array|null 상태 배열 또는 없으면 null
     */
    public function getState(int $agentId, int $userId): ?array {
        $cacheKey = "{$agentId}_{$userId}";

        // 캐시 확인
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $record = $this->db->get_record($this->tableName, [
                'agent_id' => $agentId,
                'user_id' => $userId
            ]);

            if (!$record) {
                return null;
            }

            $state = [
                'id' => $record->id,
                'agent_id' => $record->agent_id,
                'user_id' => $record->user_id,
                'persona_id' => $record->persona_id,
                'context_data' => json_decode($record->context_data ?? '{}', true),
                'last_interaction' => $record->last_interaction,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at
            ];

            // 캐시 저장
            if ($this->cacheEnabled) {
                $this->cache[$cacheKey] = $state;
            }

            return $state;

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 상태 조회 실패: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 페르소나 상태 업데이트/생성
     *
     * @param int $agentId 에이전트 번호
     * @param int $userId 사용자 ID
     * @param array $data 업데이트할 데이터
     *        - 'persona_id': 페르소나 ID
     *        - 'context_data': 컨텍스트 데이터 (기존에 병합됨)
     *        - 기타 필드
     * @return bool 성공 여부
     */
    public function updateState(int $agentId, int $userId, array $data): bool {
        try {
            $existing = $this->db->get_record($this->tableName, [
                'agent_id' => $agentId,
                'user_id' => $userId
            ]);

            $now = date('Y-m-d H:i:s');

            if ($existing) {
                // 업데이트
                $record = new stdClass();
                $record->id = $existing->id;
                $record->updated_at = $now;
                $record->last_interaction = $now;

                if (isset($data['persona_id'])) {
                    $record->persona_id = $data['persona_id'];
                }

                if (isset($data['context_data'])) {
                    // 기존 컨텍스트와 병합
                    $existingContext = json_decode($existing->context_data ?? '{}', true);
                    $newContext = array_merge($existingContext, $data['context_data']);
                    $record->context_data = json_encode($newContext, JSON_UNESCAPED_UNICODE);
                }

                $this->db->update_record($this->tableName, $record);

            } else {
                // 생성
                $record = new stdClass();
                $record->agent_id = $agentId;
                $record->user_id = $userId;
                $record->persona_id = $data['persona_id'] ?? '';
                $record->context_data = json_encode($data['context_data'] ?? [], JSON_UNESCAPED_UNICODE);
                $record->last_interaction = $now;
                $record->created_at = $now;
                $record->updated_at = $now;

                $this->db->insert_record($this->tableName, $record);
            }

            // 캐시 무효화
            $cacheKey = "{$agentId}_{$userId}";
            unset($this->cache[$cacheKey]);

            return true;

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 상태 업데이트 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 특정 사용자의 모든 에이전트 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array 에이전트별 상태 배열 [agent_id => state]
     */
    public function getAllStatesForUser(int $userId): array {
        try {
            $records = $this->db->get_records($this->tableName, ['user_id' => $userId]);

            $states = [];
            foreach ($records as $record) {
                $states[$record->agent_id] = [
                    'id' => $record->id,
                    'agent_id' => $record->agent_id,
                    'user_id' => $record->user_id,
                    'persona_id' => $record->persona_id,
                    'context_data' => json_decode($record->context_data ?? '{}', true),
                    'last_interaction' => $record->last_interaction
                ];
            }

            return $states;

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 전체 상태 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 에이전트 간 상태 동기화
     * 특정 에이전트의 상태를 다른 에이전트에게 전파
     *
     * @param int $sourceAgentId 소스 에이전트
     * @param int $targetAgentId 대상 에이전트
     * @param int $userId 사용자 ID
     * @param array $fieldsToSync 동기화할 필드 목록
     * @return bool 성공 여부
     */
    public function syncBetweenAgents(
        int $sourceAgentId,
        int $targetAgentId,
        int $userId,
        array $fieldsToSync = ['persona_id']
    ): bool {
        try {
            $sourceState = $this->getState($sourceAgentId, $userId);
            if (!$sourceState) {
                return false;
            }

            $syncData = [];
            foreach ($fieldsToSync as $field) {
                if (isset($sourceState[$field])) {
                    $syncData[$field] = $sourceState[$field];
                } elseif (isset($sourceState['context_data'][$field])) {
                    if (!isset($syncData['context_data'])) {
                        $syncData['context_data'] = [];
                    }
                    $syncData['context_data'][$field] = $sourceState['context_data'][$field];
                }
            }

            if (empty($syncData)) {
                return true;
            }

            return $this->updateState($targetAgentId, $userId, $syncData);

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 동기화 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 특정 필드 값 조회
     *
     * @param int $agentId 에이전트 ID
     * @param int $userId 사용자 ID
     * @param string $field 필드명
     * @param mixed $default 기본값
     * @return mixed 필드 값
     */
    public function getField(int $agentId, int $userId, string $field, $default = null) {
        $state = $this->getState($agentId, $userId);
        if (!$state) {
            return $default;
        }

        if (isset($state[$field])) {
            return $state[$field];
        }

        if (isset($state['context_data'][$field])) {
            return $state['context_data'][$field];
        }

        return $default;
    }

    /**
     * 컨텍스트 데이터에 값 설정
     *
     * @param int $agentId 에이전트 ID
     * @param int $userId 사용자 ID
     * @param string $key 키
     * @param mixed $value 값
     * @return bool 성공 여부
     */
    public function setContextField(int $agentId, int $userId, string $key, $value): bool {
        return $this->updateState($agentId, $userId, [
            'context_data' => [$key => $value]
        ]);
    }

    /**
     * 마지막 상호작용 시간 업데이트
     *
     * @param int $agentId 에이전트 ID
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function touchLastInteraction(int $agentId, int $userId): bool {
        try {
            $existing = $this->db->get_record($this->tableName, [
                'agent_id' => $agentId,
                'user_id' => $userId
            ]);

            if (!$existing) {
                return false;
            }

            $record = new stdClass();
            $record->id = $existing->id;
            $record->last_interaction = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');

            $this->db->update_record($this->tableName, $record);
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 상태 삭제
     *
     * @param int $agentId 에이전트 ID
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function deleteState(int $agentId, int $userId): bool {
        try {
            $this->db->delete_records($this->tableName, [
                'agent_id' => $agentId,
                'user_id' => $userId
            ]);

            // 캐시 무효화
            $cacheKey = "{$agentId}_{$userId}";
            unset($this->cache[$cacheKey]);

            return true;

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 상태 삭제 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 비활성 상태 정리
     *
     * @param int $daysInactive 비활성 기간 (일)
     * @return int 삭제된 레코드 수
     */
    public function cleanupInactive(int $daysInactive = 90): int {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysInactive} days"));

            $sql = "DELETE FROM {{$this->tableName}}
                    WHERE last_interaction < :cutoff";

            $this->db->execute($sql, ['cutoff' => $cutoff]);
            return 0; // Moodle은 affected_rows 미제공

        } catch (Exception $e) {
            error_log("[AgentStateSync] {$this->currentFile}:" . __LINE__ .
                " - 정리 실패: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->cache = [];
    }

    /**
     * 상태 히스토리 조회용 - 최근 활성 사용자 목록
     *
     * @param int $agentId 에이전트 ID
     * @param int $limit 최대 조회 수
     * @return array 사용자 상태 목록
     */
    public function getRecentActiveUsers(int $agentId, int $limit = 50): array {
        try {
            $sql = "SELECT * FROM {{$this->tableName}}
                    WHERE agent_id = :agent_id
                    ORDER BY last_interaction DESC
                    LIMIT {$limit}";

            $records = $this->db->get_records_sql($sql, ['agent_id' => $agentId]);

            $result = [];
            foreach ($records as $record) {
                $record->context_data = json_decode($record->context_data ?? '{}', true);
                $result[] = (array) $record;
            }

            return $result;

        } catch (Exception $e) {
            return [];
        }
    }
}
