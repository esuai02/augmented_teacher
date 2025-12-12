<?php
/**
 * PersonaTransitionManager - 페르소나 전환 관리자
 *
 * 페르소나 상태 전환 및 이력 관리
 * 에이전트 간 페르소나 상태 공유 및 동기화
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 *
 * 관련 DB 테이블:
 * - at_agent_persona_state: 페르소나 상태 저장
 *   - id (int): PK
 *   - userid (int): 사용자 ID
 *   - nagent (int): 에이전트 번호 (15)
 *   - persona_id (varchar): 페르소나 식별자
 *   - persona_name (varchar): 페르소나 이름
 *   - trigger_scenario (varchar): 트리거 시나리오 코드
 *   - confidence (float): 신뢰도 점수
 *   - state_data (text): JSON 상태 데이터
 *   - timecreated (int): 생성 시간
 *   - timemodified (int): 수정 시간
 *
 * - at_persona_transition_log: 전환 이력
 *   - id (int): PK
 *   - userid (int): 사용자 ID
 *   - nagent (int): 에이전트 번호
 *   - from_persona (varchar): 이전 페르소나
 *   - to_persona (varchar): 새 페르소나
 *   - trigger_reason (text): 전환 사유
 *   - timecreated (int): 전환 시간
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class PersonaTransitionManager {

    /** @var int 에이전트 번호 */
    const AGENT_NUMBER = 15;

    /** @var int 전환 쿨다운 (초) */
    const TRANSITION_COOLDOWN = 300;

    /** @var float 최소 전환 신뢰도 */
    const MIN_TRANSITION_CONFIDENCE = 0.6;

    /** @var array 유효한 전환 경로 정의 */
    private $validTransitions = [];

    /**
     * 생성자
     */
    public function __construct() {
        $this->initValidTransitions();
    }

    /**
     * 유효한 전환 경로 초기화
     */
    private function initValidTransitions() {
        // R-Series (인식형) 전환 가능 경로
        $this->validTransitions['R1'] = ['A1', 'A2', 'V1', 'E1'];
        $this->validTransitions['R2'] = ['A1', 'A3', 'V2', 'E2'];
        $this->validTransitions['R3'] = ['A2', 'A4', 'V1', 'E1'];

        // A-Series (귀인형) 전환 가능 경로
        $this->validTransitions['A1'] = ['V1', 'V2', 'S1', 'E1'];
        $this->validTransitions['A2'] = ['V1', 'V3', 'S2', 'E2'];
        $this->validTransitions['A3'] = ['V2', 'V4', 'S3', 'E1'];
        $this->validTransitions['A4'] = ['V3', 'V4', 'S4', 'E2'];

        // V-Series (검증형) 전환 가능 경로
        $this->validTransitions['V1'] = ['S1', 'S2', 'A1', 'E1'];
        $this->validTransitions['V2'] = ['S1', 'S3', 'A2', 'E2'];
        $this->validTransitions['V3'] = ['S2', 'S4', 'A3', 'E1'];
        $this->validTransitions['V4'] = ['S3', 'S4', 'A4', 'E2'];

        // S-Series (솔루션형) 전환 가능 경로
        $this->validTransitions['S1'] = ['R1', 'E1', 'E2'];
        $this->validTransitions['S2'] = ['R2', 'E1', 'E2'];
        $this->validTransitions['S3'] = ['R3', 'E1', 'E2'];
        $this->validTransitions['S4'] = ['R1', 'R2', 'E1', 'E2'];

        // E-Series (정서형) 전환 가능 경로
        $this->validTransitions['E1'] = ['R1', 'R2', 'R3', 'A1'];
        $this->validTransitions['E2'] = ['R1', 'R2', 'R3', 'A2'];
    }

    /**
     * 현재 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 페르소나 상태
     */
    public function getCurrentState($userId) {
        global $DB;

        try {
            $state = $DB->get_record_sql(
                "SELECT * FROM {at_agent_persona_state}
                 WHERE userid = ? AND nagent = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$userId, self::AGENT_NUMBER]
            );

            if ($state) {
                return [
                    'id' => $state->id,
                    'persona_id' => $state->persona_id,
                    'persona_name' => $state->persona_name,
                    'trigger_scenario' => $state->trigger_scenario,
                    'confidence' => floatval($state->confidence),
                    'state_data' => json_decode($state->state_data, true),
                    'created' => $state->timecreated
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("Failed to get current persona state: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return null;
        }
    }

    /**
     * 페르소나 전환 실행
     *
     * @param int $userId 사용자 ID
     * @param array $newPersona 새 페르소나 정보
     * @param string $triggerScenario 트리거 시나리오
     * @param string $reason 전환 사유
     * @return bool 성공 여부
     */
    public function transition($userId, $newPersona, $triggerScenario, $reason = '') {
        global $DB;

        // 현재 상태 조회
        $currentState = $this->getCurrentState($userId);

        // 전환 유효성 검증
        if (!$this->validateTransition($currentState, $newPersona)) {
            error_log("Invalid persona transition attempted [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }

        // 쿨다운 확인
        if ($currentState && !$this->checkCooldown($currentState)) {
            error_log("Persona transition cooldown not elapsed [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }

        try {
            $now = time();

            // 새 상태 저장
            $stateRecord = new stdClass();
            $stateRecord->userid = $userId;
            $stateRecord->nagent = self::AGENT_NUMBER;
            $stateRecord->persona_id = $newPersona['id'];
            $stateRecord->persona_name = $newPersona['name'];
            $stateRecord->trigger_scenario = $triggerScenario;
            $stateRecord->confidence = $newPersona['confidence'] ?? 0.7;
            $stateRecord->state_data = json_encode([
                'characteristics' => $newPersona['characteristics'] ?? [],
                'behavior_rules' => $newPersona['behavior_rules'] ?? [],
                'transition_reason' => $reason
            ]);
            $stateRecord->timecreated = $now;
            $stateRecord->timemodified = $now;

            $DB->insert_record('at_agent_persona_state', $stateRecord);

            // 전환 이력 기록
            $this->logTransition($userId, $currentState, $newPersona, $reason);

            // 에이전트 간 메시지 발송
            $this->notifyAgents($userId, $currentState, $newPersona, $triggerScenario);

            return true;

        } catch (Exception $e) {
            error_log("Persona transition failed: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 전환 유효성 검증
     *
     * @param array|null $currentState 현재 상태
     * @param array $newPersona 새 페르소나
     * @return bool 유효 여부
     */
    private function validateTransition($currentState, $newPersona) {
        // 신규 상태 (현재 상태 없음)
        if (!$currentState) {
            return true;
        }

        $currentId = $currentState['persona_id'];
        $newId = $newPersona['id'];

        // 동일 페르소나로의 전환은 허용 (상태 갱신)
        if ($currentId === $newId) {
            return true;
        }

        // 유효한 전환 경로 확인
        if (isset($this->validTransitions[$currentId])) {
            return in_array($newId, $this->validTransitions[$currentId]);
        }

        // 정의되지 않은 페르소나는 자유 전환 허용
        return true;
    }

    /**
     * 쿨다운 확인
     *
     * @param array $currentState 현재 상태
     * @return bool 쿨다운 경과 여부
     */
    private function checkCooldown($currentState) {
        $lastTransition = $currentState['created'] ?? 0;
        return (time() - $lastTransition) >= self::TRANSITION_COOLDOWN;
    }

    /**
     * 전환 이력 기록
     *
     * @param int $userId 사용자 ID
     * @param array|null $fromState 이전 상태
     * @param array $toPersona 새 페르소나
     * @param string $reason 전환 사유
     */
    private function logTransition($userId, $fromState, $toPersona, $reason) {
        global $DB;

        try {
            $log = new stdClass();
            $log->userid = $userId;
            $log->nagent = self::AGENT_NUMBER;
            $log->from_persona = $fromState ? $fromState['persona_id'] : 'none';
            $log->to_persona = $toPersona['id'];
            $log->trigger_reason = $reason;
            $log->timecreated = time();

            $DB->insert_record('at_persona_transition_log', $log);

        } catch (Exception $e) {
            error_log("Failed to log persona transition: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
        }
    }

    /**
     * 다른 에이전트에 전환 알림
     *
     * @param int $userId 사용자 ID
     * @param array|null $fromState 이전 상태
     * @param array $toPersona 새 페르소나
     * @param string $triggerScenario 트리거 시나리오
     */
    private function notifyAgents($userId, $fromState, $toPersona, $triggerScenario) {
        global $DB;

        // 관련 에이전트 목록 (연계 에이전트)
        $relatedAgents = [1, 2, 3, 5, 6, 12, 13, 14, 16, 17, 20];

        try {
            $messageData = [
                'event' => 'persona_transition',
                'source_agent' => self::AGENT_NUMBER,
                'user_id' => $userId,
                'from_persona' => $fromState ? $fromState['persona_id'] : null,
                'to_persona' => $toPersona['id'],
                'trigger_scenario' => $triggerScenario,
                'confidence' => $toPersona['confidence'] ?? 0.7,
                'timestamp' => time()
            ];

            foreach ($relatedAgents as $targetAgent) {
                $message = new stdClass();
                $message->from_agent = self::AGENT_NUMBER;
                $message->to_agent = $targetAgent;
                $message->userid = $userId;
                $message->message_type = 'persona_update';
                $message->message_data = json_encode($messageData);
                $message->status = 'pending';
                $message->timecreated = time();

                // at_agent_messages 테이블이 있으면 저장
                if ($DB->get_manager()->table_exists('at_agent_messages')) {
                    $DB->insert_record('at_agent_messages', $message);
                }
            }

        } catch (Exception $e) {
            error_log("Failed to notify agents: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
        }
    }

    /**
     * 페르소나 상태 이력 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 이력 목록
     */
    public function getStateHistory($userId, $limit = 10) {
        global $DB;

        try {
            $records = $DB->get_records_sql(
                "SELECT * FROM {at_agent_persona_state}
                 WHERE userid = ? AND nagent = ?
                 ORDER BY timecreated DESC
                 LIMIT ?",
                [$userId, self::AGENT_NUMBER, $limit]
            );

            $history = [];
            foreach ($records as $record) {
                $history[] = [
                    'persona_id' => $record->persona_id,
                    'persona_name' => $record->persona_name,
                    'trigger_scenario' => $record->trigger_scenario,
                    'confidence' => floatval($record->confidence),
                    'created' => $record->timecreated
                ];
            }

            return $history;

        } catch (Exception $e) {
            error_log("Failed to get persona history: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return [];
        }
    }

    /**
     * 전환 이력 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 전환 이력
     */
    public function getTransitionHistory($userId, $limit = 10) {
        global $DB;

        try {
            $records = $DB->get_records_sql(
                "SELECT * FROM {at_persona_transition_log}
                 WHERE userid = ? AND nagent = ?
                 ORDER BY timecreated DESC
                 LIMIT ?",
                [$userId, self::AGENT_NUMBER, $limit]
            );

            $history = [];
            foreach ($records as $record) {
                $history[] = [
                    'from' => $record->from_persona,
                    'to' => $record->to_persona,
                    'reason' => $record->trigger_reason,
                    'time' => $record->timecreated
                ];
            }

            return $history;

        } catch (Exception $e) {
            error_log("Failed to get transition history: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return [];
        }
    }

    /**
     * 다른 에이전트의 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @param int $agentNumber 에이전트 번호
     * @return array|null 페르소나 상태
     */
    public function getAgentPersonaState($userId, $agentNumber) {
        global $DB;

        try {
            $state = $DB->get_record_sql(
                "SELECT * FROM {at_agent_persona_state}
                 WHERE userid = ? AND nagent = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$userId, $agentNumber]
            );

            if ($state) {
                return [
                    'agent' => $agentNumber,
                    'persona_id' => $state->persona_id,
                    'persona_name' => $state->persona_name,
                    'confidence' => floatval($state->confidence),
                    'created' => $state->timecreated
                ];
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 모든 에이전트의 현재 페르소나 상태 조회
     *
     * @param int $userId 사용자 ID
     * @return array 에이전트별 페르소나 상태
     */
    public function getAllAgentStates($userId) {
        global $DB;

        $states = [];

        try {
            // 각 에이전트의 최신 상태 조회
            $sql = "SELECT t1.*
                    FROM {at_agent_persona_state} t1
                    INNER JOIN (
                        SELECT nagent, MAX(timecreated) as max_time
                        FROM {at_agent_persona_state}
                        WHERE userid = ?
                        GROUP BY nagent
                    ) t2 ON t1.nagent = t2.nagent AND t1.timecreated = t2.max_time
                    WHERE t1.userid = ?";

            $records = $DB->get_records_sql($sql, [$userId, $userId]);

            foreach ($records as $record) {
                $states[$record->nagent] = [
                    'agent' => $record->nagent,
                    'persona_id' => $record->persona_id,
                    'persona_name' => $record->persona_name,
                    'confidence' => floatval($record->confidence),
                    'created' => $record->timecreated
                ];
            }

        } catch (Exception $e) {
            error_log("Failed to get all agent states: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
        }

        return $states;
    }

    /**
     * 페르소나 전환 추천
     *
     * @param int $userId 사용자 ID
     * @param array $context 컨텍스트 데이터
     * @return array|null 추천 전환 정보
     */
    public function recommendTransition($userId, $context) {
        $currentState = $this->getCurrentState($userId);

        if (!$currentState) {
            return null;
        }

        $currentId = $currentState['persona_id'];
        $possibleTransitions = $this->validTransitions[$currentId] ?? [];

        if (empty($possibleTransitions)) {
            return null;
        }

        // 컨텍스트 기반 최적 전환 추천
        $recommendation = $this->evaluateTransitions($possibleTransitions, $context);

        if ($recommendation) {
            return [
                'current' => $currentId,
                'recommended' => $recommendation['persona'],
                'reason' => $recommendation['reason'],
                'confidence' => $recommendation['confidence']
            ];
        }

        return null;
    }

    /**
     * 전환 옵션 평가
     *
     * @param array $options 가능한 전환 옵션
     * @param array $context 컨텍스트
     * @return array|null 최적 전환
     */
    private function evaluateTransitions($options, $context) {
        $agentData = $context['agent_data'] ?? [];
        $emotionLogs = $agentData['emotion_logs'] ?? [];
        $performance = $agentData['performance'] ?? [];

        $bestOption = null;
        $bestScore = 0;

        foreach ($options as $personaId) {
            $score = $this->scoreTransition($personaId, $context);

            if ($score > $bestScore && $score >= self::MIN_TRANSITION_CONFIDENCE) {
                $bestScore = $score;
                $bestOption = $personaId;
            }
        }

        if ($bestOption) {
            return [
                'persona' => $bestOption,
                'confidence' => $bestScore,
                'reason' => $this->getTransitionReason($bestOption, $context)
            ];
        }

        return null;
    }

    /**
     * 전환 점수 계산
     *
     * @param string $personaId 페르소나 ID
     * @param array $context 컨텍스트
     * @return float 점수 (0-1)
     */
    private function scoreTransition($personaId, $context) {
        $agentData = $context['agent_data'] ?? [];
        $score = 0.5; // 기본 점수

        // 시리즈별 점수 조정
        $series = substr($personaId, 0, 1);

        switch ($series) {
            case 'R': // 인식형
                // 새로운 문제 상황이 감지되면 높은 점수
                if (!empty($agentData['new_issues'])) {
                    $score += 0.3;
                }
                break;

            case 'A': // 귀인형
                // 원인 분석이 필요하면 높은 점수
                if (empty($context['cause_analysis'])) {
                    $score += 0.25;
                }
                break;

            case 'V': // 검증형
                // 가설이 있고 검증이 필요하면 높은 점수
                if (!empty($context['hypothesis']) && empty($context['validation'])) {
                    $score += 0.3;
                }
                break;

            case 'S': // 솔루션형
                // 원인이 파악되고 해결책이 필요하면 높은 점수
                if (!empty($context['cause_analysis']) && empty($context['action_plan'])) {
                    $score += 0.35;
                }
                break;

            case 'E': // 정서형
                // 부정적 감정이 감지되면 높은 점수
                $emotionLogs = $agentData['emotion_logs'] ?? [];
                $negativeEmotions = ['frustration', 'anxiety', 'hopelessness'];
                foreach ($emotionLogs as $log) {
                    if (in_array($log['emotion'] ?? '', $negativeEmotions)) {
                        $score += 0.4;
                        break;
                    }
                }
                break;
        }

        return min(1.0, $score);
    }

    /**
     * 전환 사유 생성
     *
     * @param string $personaId 페르소나 ID
     * @param array $context 컨텍스트
     * @return string 전환 사유
     */
    private function getTransitionReason($personaId, $context) {
        $series = substr($personaId, 0, 1);

        $reasons = [
            'R' => '새로운 문제 상황 인식 필요',
            'A' => '근본 원인 분석 필요',
            'V' => '가설 검증 및 확인 필요',
            'S' => '구체적 해결책 제시 필요',
            'E' => '정서적 지원 및 공감 필요'
        ];

        return $reasons[$series] ?? '상황 변화에 따른 페르소나 전환';
    }

    /**
     * 강제 전환 (관리자용)
     *
     * @param int $userId 사용자 ID
     * @param array $persona 페르소나 정보
     * @param string $reason 사유
     * @return bool 성공 여부
     */
    public function forceTransition($userId, $persona, $reason = 'Admin forced transition') {
        global $DB;

        try {
            $now = time();

            $stateRecord = new stdClass();
            $stateRecord->userid = $userId;
            $stateRecord->nagent = self::AGENT_NUMBER;
            $stateRecord->persona_id = $persona['id'];
            $stateRecord->persona_name = $persona['name'] ?? '';
            $stateRecord->trigger_scenario = 'ADMIN';
            $stateRecord->confidence = 1.0;
            $stateRecord->state_data = json_encode([
                'forced' => true,
                'reason' => $reason
            ]);
            $stateRecord->timecreated = $now;
            $stateRecord->timemodified = $now;

            $DB->insert_record('at_agent_persona_state', $stateRecord);

            return true;

        } catch (Exception $e) {
            error_log("Force transition failed: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }
}
