<?php
/**
 * PersonaDataRepository - 페르소나 데이터 저장소
 *
 * Agent09 페르소나 시스템의 데이터 접근 계층
 * - 페르소나 상태 저장/조회
 * - 페르소나 전환 기록
 * - 개입 기록 관리
 *
 * @package AugmentedTeacher\Agent09\PersonaSystem\DB
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class PersonaDataRepository {

    /** @var string 현재 파일 경로 */
    private $currentFile;

    /** @var string 에이전트 ID */
    private $agentId = 'agent09';

    /** @var array 캐시 */
    private $cache = [];

    /** @var int 캐시 TTL (초) */
    private $cacheTTL = 300;

    /**
     * 생성자
     */
    public function __construct(string $agentId = 'agent09') {
        $this->currentFile = __FILE__;
        $this->agentId = $agentId;
    }

    // ==========================================
    // 페르소나 상태 관리
    // ==========================================

    /**
     * 현재 활성 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 페르소나 상태 데이터
     */
    public function getActivePersonaState(int $userId): ?array {
        global $DB;

        $cacheKey = "persona_state_{$userId}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $sql = "SELECT * FROM {at_agent_persona_state}
                    WHERE user_id = ?
                    AND agent_id = ?
                    AND is_active = 1
                    ORDER BY updated_at DESC
                    LIMIT 1";

            $record = $DB->get_record_sql($sql, [$userId, $this->agentId]);

            if ($record) {
                $data = $this->mapPersonaStateRecord($record);
                $this->cache[$cacheKey] = $data;
                return $data;
            }

            return null;

        } catch (Exception $e) {
            $this->logError("페르소나 상태 조회 실패", $e);
            return null;
        }
    }

    /**
     * 페르소나 상태 저장 또는 업데이트
     *
     * @param int $userId 사용자 ID
     * @param array $personaData 페르소나 데이터
     * @return int|bool 저장된 레코드 ID 또는 실패 시 false
     */
    public function savePersonaState(int $userId, array $personaData) {
        global $DB;

        try {
            // 기존 활성 상태 비활성화
            $this->deactivatePreviousStates($userId);

            // 새 상태 저장
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->persona_code = $personaData['persona_code'] ?? 'UNKNOWN';
            $record->persona_series = $personaData['persona_series'] ?? $this->extractSeries($record->persona_code);
            $record->confidence_score = $personaData['confidence_score'] ?? 0.0;
            $record->data_density_score = $personaData['data_density_score'] ?? null;
            $record->balance_score = $personaData['balance_score'] ?? null;
            $record->stability_score = $personaData['stability_score'] ?? null;
            $record->dropout_risk_score = $personaData['dropout_risk_score'] ?? null;
            $record->intervention_level = $personaData['intervention_level'] ?? 'none';
            $record->recommended_tone = $personaData['recommended_tone'] ?? null;
            $record->recommended_pace = $personaData['recommended_pace'] ?? null;
            $record->context_snapshot = isset($personaData['context']) ? json_encode($personaData['context']) : null;
            $record->is_active = 1;
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');

            $newId = $DB->insert_record('at_agent_persona_state', $record);

            // 캐시 무효화
            $this->invalidateCache("persona_state_{$userId}");

            return $newId;

        } catch (Exception $e) {
            $this->logError("페르소나 상태 저장 실패", $e);
            return false;
        }
    }

    /**
     * 페르소나 상태 업데이트
     *
     * @param int $stateId 상태 ID
     * @param array $updates 업데이트할 필드
     * @return bool 성공 여부
     */
    public function updatePersonaState(int $stateId, array $updates): bool {
        global $DB;

        try {
            $record = new stdClass();
            $record->id = $stateId;

            foreach ($updates as $field => $value) {
                if ($field === 'context') {
                    $record->context_snapshot = json_encode($value);
                } else {
                    $record->$field = $value;
                }
            }

            $record->updated_at = date('Y-m-d H:i:s');

            $DB->update_record('at_agent_persona_state', $record);

            return true;

        } catch (Exception $e) {
            $this->logError("페르소나 상태 업데이트 실패", $e);
            return false;
        }
    }

    /**
     * 이전 활성 상태 비활성화
     */
    private function deactivatePreviousStates(int $userId): void {
        global $DB;

        try {
            $sql = "UPDATE {at_agent_persona_state}
                    SET is_active = 0, updated_at = ?
                    WHERE user_id = ?
                    AND agent_id = ?
                    AND is_active = 1";

            $DB->execute($sql, [date('Y-m-d H:i:s'), $userId, $this->agentId]);

        } catch (Exception $e) {
            $this->logError("이전 상태 비활성화 실패", $e);
        }
    }

    /**
     * 페르소나 코드에서 시리즈 추출
     */
    private function extractSeries(string $personaCode): string {
        if (preg_match('/^([A-Z])-/', $personaCode, $matches)) {
            return $matches[1];
        }
        return 'U'; // Unknown
    }

    // ==========================================
    // 페르소나 전환 기록
    // ==========================================

    /**
     * 페르소나 전환 기록 저장
     *
     * @param int $userId 사용자 ID
     * @param string|null $fromPersona 이전 페르소나
     * @param string $toPersona 새 페르소나
     * @param array $metadata 추가 메타데이터
     * @return int|bool 저장된 레코드 ID 또는 실패 시 false
     */
    public function logPersonaTransition(
        int $userId,
        ?string $fromPersona,
        string $toPersona,
        array $metadata = []
    ) {
        global $DB;

        try {
            // 전환 유형 결정
            $transitionType = $this->determineTransitionType($fromPersona, $toPersona, $metadata);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->from_persona = $fromPersona;
            $record->to_persona = $toPersona;
            $record->trigger_rule_id = $metadata['trigger_rule_id'] ?? null;
            $record->trigger_reason = $metadata['trigger_reason'] ?? null;
            $record->confidence_before = $metadata['confidence_before'] ?? null;
            $record->confidence_after = $metadata['confidence_after'] ?? null;
            $record->context_diff = isset($metadata['context_diff']) ? json_encode($metadata['context_diff']) : null;
            $record->transition_type = $transitionType;
            $record->created_at = date('Y-m-d H:i:s');

            return $DB->insert_record('at_persona_transition_log', $record);

        } catch (Exception $e) {
            $this->logError("페르소나 전환 기록 실패", $e);
            return false;
        }
    }

    /**
     * 전환 유형 결정
     */
    private function determineTransitionType(?string $from, string $to, array $metadata): string {
        if (empty($from)) {
            return 'initial';
        }

        // 위험도 기반 전환 유형
        $riskLevels = [
            'D-WARNING' => 1,
            'D-ALERT' => 2,
            'D-CRITICAL' => 3
        ];

        $fromRisk = $riskLevels[$from] ?? 0;
        $toRisk = $riskLevels[$to] ?? 0;

        if ($toRisk > $fromRisk) {
            return 'upgrade'; // 위험도 상승
        } elseif ($toRisk < $fromRisk) {
            return 'downgrade'; // 위험도 하락
        }

        // 시리즈 변경 확인
        $fromSeries = $this->extractSeries($from);
        $toSeries = $this->extractSeries($to);

        if ($fromSeries !== $toSeries) {
            return 'lateral'; // 다른 시리즈로 이동
        }

        return 'lateral';
    }

    /**
     * 사용자의 페르소나 전환 이력 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 전환 이력
     */
    public function getTransitionHistory(int $userId, int $limit = 20): array {
        global $DB;

        try {
            $sql = "SELECT * FROM {at_persona_transition_log}
                    WHERE user_id = ?
                    AND agent_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?";

            $records = $DB->get_records_sql($sql, [$userId, $this->agentId, $limit]);

            return array_map(function($record) {
                return [
                    'id' => $record->id,
                    'from_persona' => $record->from_persona,
                    'to_persona' => $record->to_persona,
                    'transition_type' => $record->transition_type,
                    'trigger_rule_id' => $record->trigger_rule_id,
                    'trigger_reason' => $record->trigger_reason,
                    'created_at' => $record->created_at
                ];
            }, $records);

        } catch (Exception $e) {
            $this->logError("전환 이력 조회 실패", $e);
            return [];
        }
    }

    // ==========================================
    // 개입 기록 관리
    // ==========================================

    /**
     * 개입 기록 저장
     *
     * @param int $userId 사용자 ID
     * @param array $interventionData 개입 데이터
     * @return int|bool 저장된 레코드 ID 또는 실패 시 false
     */
    public function logIntervention(int $userId, array $interventionData) {
        global $DB;

        try {
            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->persona_code = $interventionData['persona_code'] ?? '';
            $record->intervention_type = $interventionData['intervention_type'] ?? 'encouragement';
            $record->intervention_level = $interventionData['intervention_level'] ?? '주의';
            $record->indicator_type = $interventionData['indicator_type'] ?? 'composite';
            $record->message_sent = $interventionData['message'] ?? null;
            $record->response_received = 0;
            $record->follow_up_needed = $interventionData['follow_up_needed'] ?? 0;
            $record->follow_up_date = $interventionData['follow_up_date'] ?? null;
            $record->notes = $interventionData['notes'] ?? null;
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');

            return $DB->insert_record('at_intervention_log', $record);

        } catch (Exception $e) {
            $this->logError("개입 기록 저장 실패", $e);
            return false;
        }
    }

    /**
     * 개입 응답 기록
     *
     * @param int $interventionId 개입 ID
     * @param string $responseContent 응답 내용
     * @param float|null $effectivenessScore 효과 점수
     * @return bool 성공 여부
     */
    public function recordInterventionResponse(
        int $interventionId,
        string $responseContent,
        ?float $effectivenessScore = null
    ): bool {
        global $DB;

        try {
            $record = new stdClass();
            $record->id = $interventionId;
            $record->response_received = 1;
            $record->response_content = $responseContent;
            $record->response_at = date('Y-m-d H:i:s');
            $record->effectiveness_score = $effectivenessScore;
            $record->updated_at = date('Y-m-d H:i:s');

            $DB->update_record('at_intervention_log', $record);

            return true;

        } catch (Exception $e) {
            $this->logError("개입 응답 기록 실패", $e);
            return false;
        }
    }

    /**
     * 최근 개입 기록 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 일수
     * @return array 개입 기록
     */
    public function getRecentInterventions(int $userId, int $days = 30): array {
        global $DB;

        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            $sql = "SELECT * FROM {at_intervention_log}
                    WHERE user_id = ?
                    AND agent_id = ?
                    AND created_at >= ?
                    ORDER BY created_at DESC";

            $records = $DB->get_records_sql($sql, [$userId, $this->agentId, $startDate]);

            return array_map(function($record) {
                return [
                    'id' => $record->id,
                    'persona_code' => $record->persona_code,
                    'intervention_type' => $record->intervention_type,
                    'intervention_level' => $record->intervention_level,
                    'indicator_type' => $record->indicator_type,
                    'message_sent' => $record->message_sent,
                    'response_received' => (bool)$record->response_received,
                    'effectiveness_score' => $record->effectiveness_score,
                    'created_at' => $record->created_at
                ];
            }, $records);

        } catch (Exception $e) {
            $this->logError("개입 기록 조회 실패", $e);
            return [];
        }
    }

    /**
     * 후속 조치 필요한 개입 조회
     *
     * @return array 후속 조치 필요 개입 목록
     */
    public function getPendingFollowUps(): array {
        global $DB;

        try {
            $sql = "SELECT * FROM {at_intervention_log}
                    WHERE agent_id = ?
                    AND follow_up_needed = 1
                    AND follow_up_date <= ?
                    ORDER BY follow_up_date ASC";

            $records = $DB->get_records_sql($sql, [$this->agentId, date('Y-m-d')]);

            return array_map(function($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'persona_code' => $record->persona_code,
                    'intervention_type' => $record->intervention_type,
                    'intervention_level' => $record->intervention_level,
                    'follow_up_date' => $record->follow_up_date,
                    'notes' => $record->notes
                ];
            }, $records);

        } catch (Exception $e) {
            $this->logError("후속 조치 조회 실패", $e);
            return [];
        }
    }

    // ==========================================
    // 통계 및 분석
    // ==========================================

    /**
     * 사용자 페르소나 통계 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 분석 기간 (일)
     * @return array 통계 데이터
     */
    public function getPersonaStatistics(int $userId, int $days = 30): array {
        global $DB;

        try {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 페르소나별 빈도
            $personaFrequency = $DB->get_records_sql(
                "SELECT persona_code, COUNT(*) as count
                 FROM {at_agent_persona_state}
                 WHERE user_id = ? AND agent_id = ? AND created_at >= ?
                 GROUP BY persona_code
                 ORDER BY count DESC",
                [$userId, $this->agentId, $startDate]
            );

            // 전환 빈도
            $transitionCount = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {at_persona_transition_log}
                 WHERE user_id = ? AND agent_id = ? AND created_at >= ?",
                [$userId, $this->agentId, $startDate]
            );

            // 개입 효과
            $interventionStats = $DB->get_record_sql(
                "SELECT
                    COUNT(*) as total_interventions,
                    SUM(response_received) as responded_count,
                    AVG(effectiveness_score) as avg_effectiveness
                 FROM {at_intervention_log}
                 WHERE user_id = ? AND agent_id = ? AND created_at >= ?",
                [$userId, $this->agentId, $startDate]
            );

            return [
                'persona_frequency' => array_map(function($r) {
                    return ['code' => $r->persona_code, 'count' => (int)$r->count];
                }, $personaFrequency),
                'transition_count' => (int)$transitionCount,
                'intervention_stats' => [
                    'total' => (int)($interventionStats->total_interventions ?? 0),
                    'responded' => (int)($interventionStats->responded_count ?? 0),
                    'response_rate' => $interventionStats->total_interventions > 0
                        ? round($interventionStats->responded_count / $interventionStats->total_interventions, 2)
                        : 0,
                    'avg_effectiveness' => round((float)($interventionStats->avg_effectiveness ?? 0), 2)
                ]
            ];

        } catch (Exception $e) {
            $this->logError("통계 조회 실패", $e);
            return [];
        }
    }

    /**
     * 위험 학생 목록 조회
     *
     * @param string $riskLevel 위험 수준 ('high', 'critical')
     * @param int $limit 조회 개수
     * @return array 위험 학생 목록
     */
    public function getAtRiskStudents(string $riskLevel = 'high', int $limit = 50): array {
        global $DB;

        try {
            $riskPersonas = ['D-WARNING', 'D-ALERT', 'D-CRITICAL'];

            if ($riskLevel === 'critical') {
                $riskPersonas = ['D-CRITICAL'];
            } elseif ($riskLevel === 'high') {
                $riskPersonas = ['D-ALERT', 'D-CRITICAL'];
            }

            $placeholders = implode(',', array_fill(0, count($riskPersonas), '?'));

            $sql = "SELECT ps.*, u.firstname, u.lastname
                    FROM {at_agent_persona_state} ps
                    JOIN {user} u ON ps.user_id = u.id
                    WHERE ps.agent_id = ?
                    AND ps.is_active = 1
                    AND ps.persona_code IN ({$placeholders})
                    ORDER BY ps.dropout_risk_score DESC
                    LIMIT ?";

            $params = array_merge([$this->agentId], $riskPersonas, [$limit]);
            $records = $DB->get_records_sql($sql, $params);

            return array_map(function($record) {
                return [
                    'user_id' => $record->user_id,
                    'name' => $record->firstname . ' ' . $record->lastname,
                    'persona_code' => $record->persona_code,
                    'dropout_risk_score' => (float)$record->dropout_risk_score,
                    'intervention_level' => $record->intervention_level,
                    'last_updated' => $record->updated_at
                ];
            }, $records);

        } catch (Exception $e) {
            $this->logError("위험 학생 조회 실패", $e);
            return [];
        }
    }

    // ==========================================
    // 유틸리티 메서드
    // ==========================================

    /**
     * 페르소나 상태 레코드 매핑
     */
    private function mapPersonaStateRecord($record): array {
        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'persona_code' => $record->persona_code,
            'persona_series' => $record->persona_series,
            'confidence_score' => (float)$record->confidence_score,
            'data_density_score' => $record->data_density_score ? (float)$record->data_density_score : null,
            'balance_score' => $record->balance_score ? (float)$record->balance_score : null,
            'stability_score' => $record->stability_score ? (float)$record->stability_score : null,
            'dropout_risk_score' => $record->dropout_risk_score ? (float)$record->dropout_risk_score : null,
            'intervention_level' => $record->intervention_level,
            'recommended_tone' => $record->recommended_tone,
            'recommended_pace' => $record->recommended_pace,
            'context' => $record->context_snapshot ? json_decode($record->context_snapshot, true) : null,
            'is_active' => (bool)$record->is_active,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at
        ];
    }

    /**
     * 캐시 무효화
     */
    private function invalidateCache(string $key): void {
        unset($this->cache[$key]);
    }

    /**
     * 에러 로깅
     */
    private function logError(string $message, Exception $e): void {
        error_log("[PersonaDataRepository] {$this->currentFile}:" . __LINE__ .
            " - {$message}: " . $e->getMessage());
    }

    /**
     * 디버그 정보 반환
     */
    public function getDebugInfo(): array {
        return [
            'agent_id' => $this->agentId,
            'cache_size' => count($this->cache),
            'file' => $this->currentFile
        ];
    }
}

/*
 * 사용 예시:
 *
 * $repo = new PersonaDataRepository('agent09');
 *
 * // 현재 페르소나 상태 조회
 * $state = $repo->getActivePersonaState($userId);
 *
 * // 새 페르소나 상태 저장
 * $repo->savePersonaState($userId, [
 *     'persona_code' => 'D-ALERT',
 *     'persona_series' => 'D',
 *     'confidence_score' => 0.85,
 *     'dropout_risk_score' => 0.65,
 *     'intervention_level' => 'high',
 *     'recommended_tone' => 'Warm',
 *     'recommended_pace' => 'slow'
 * ]);
 *
 * // 페르소나 전환 기록
 * $repo->logPersonaTransition($userId, 'D-WARNING', 'D-ALERT', [
 *     'trigger_rule_id' => 'PI_D002',
 *     'trigger_reason' => 'dropout_risk_score increased above 0.5'
 * ]);
 *
 * // 개입 기록
 * $repo->logIntervention($userId, [
 *     'persona_code' => 'D-ALERT',
 *     'intervention_type' => 'encouragement',
 *     'intervention_level' => '경고',
 *     'indicator_type' => 'attendance',
 *     'message' => '최근 출석이 불규칙해요. 힘든 일이 있으신가요?',
 *     'follow_up_needed' => 1,
 *     'follow_up_date' => date('Y-m-d', strtotime('+3 days'))
 * ]);
 *
 * // 위험 학생 조회
 * $atRiskStudents = $repo->getAtRiskStudents('high', 20);
 *
 * 관련 DB 테이블:
 * - mdl_at_agent_persona_state (페르소나 상태)
 * - mdl_at_persona_transition_log (전환 로그)
 * - mdl_at_intervention_log (개입 기록)
 */
