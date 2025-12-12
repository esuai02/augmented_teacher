<?php
/**
 * Agent17DataContext - 데이터 컨텍스트 Fallback 구현체
 *
 * BaseDataContext가 없을 경우 사용되는 Agent17 전용 데이터 컨텍스트
 * 사용자 학습 상태, 활동 기록, 감정 상태 등을 관리합니다.
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine\Fallback
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 인터페이스 로드
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IDataContext.php');

use AugmentedTeacher\PersonaEngine\Core\IDataContext;

/**
 * Agent17 전용 데이터 컨텍스트
 */
class Agent17DataContext implements IDataContext {
    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var int 현재 사용자 ID */
    protected $userId;

    /** @var array 컨텍스트 데이터 */
    protected $data = [];

    /** @var array 세션 히스토리 */
    protected $history = [];

    /**
     * 컨텍스트 로드
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터
     * @return array 로드된 컨텍스트 배열
     */
    public function loadContext(int $userId, array $sessionData = []): array {
        global $DB;

        $this->userId = $userId;
        $this->data = [
            'user_id' => $userId,
            'session_data' => $sessionData,
            'loaded_at' => date('Y-m-d H:i:s')
        ];

        try {
            // 사용자 기본 정보 조회
            $user = $DB->get_record('user', ['id' => $userId], 'id, firstname, lastname, email');
            if ($user) {
                $this->data['user'] = [
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email
                ];
            }

            // 사용자 학습 상태 조회
            $userState = $DB->get_record('at_user_learning_state', ['userid' => $userId]);
            if ($userState) {
                $this->data['completion_rate'] = $userState->completion_rate ?? 0;
                $this->data['on_time_rate'] = $userState->on_time_rate ?? 100;
                $this->data['learning_style'] = $userState->learning_style ?? 'visual';
                $this->data['emotional_state'] = $userState->emotional_state ?? 'neutral';
            }

            // 최근 활동 기록 조회
            $recentActivity = $DB->get_record_sql(
                "SELECT * FROM {at_user_activities} WHERE userid = ? ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );
            if ($recentActivity) {
                $this->data['last_activity_gap_minutes'] =
                    (time() - strtotime($recentActivity->created_at)) / 60;
            }

        } catch (Exception $e) {
            error_log("[Agent17DataContext] {$this->currentFile}:" . __LINE__ .
                " - 컨텍스트 로드 실패: " . $e->getMessage());
        }

        return $this->data;
    }

    /**
     * 필드 값 가져오기
     *
     * @param string $field 필드명 (점 표기법 지원)
     * @param mixed $default 기본값
     * @return mixed 필드 값
     */
    public function get(string $field, $default = null) {
        // 점 표기법 지원
        if (strpos($field, '.') === false) {
            return $this->data[$field] ?? $default;
        }

        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * 필드 값 설정
     *
     * @param string $field 필드명 (점 표기법 지원)
     * @param mixed $value 설정할 값
     */
    public function set(string $field, $value): void {
        // 점 표기법 지원
        if (strpos($field, '.') === false) {
            $this->data[$field] = $value;
            return;
        }

        $keys = explode('.', $field);
        $ref = &$this->data;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $ref[$key] = $value;
            } else {
                if (!isset($ref[$key]) || !is_array($ref[$key])) {
                    $ref[$key] = [];
                }
                $ref = &$ref[$key];
            }
        }
    }

    /**
     * 전체 데이터를 배열로 반환
     *
     * @return array 컨텍스트 데이터 배열
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * 컨텍스트 새로고침
     */
    public function refresh(): void {
        if ($this->userId) {
            $sessionData = $this->data['session_data'] ?? [];
            $this->loadContext($this->userId, $sessionData);
        }
    }

    /**
     * 에이전트별 데이터 로드
     *
     * @param string $agentId 에이전트 ID
     * @return array 에이전트 데이터 배열
     */
    public function loadAgentData(string $agentId): array {
        global $DB;

        try {
            $agentContext = $DB->get_record('at_agent_context', [
                'userid' => $this->userId,
                'agent_id' => $agentId
            ]);

            if ($agentContext && $agentContext->context_data) {
                return json_decode($agentContext->context_data, true) ?: [];
            }
        } catch (Exception $e) {
            error_log("[Agent17DataContext] {$this->currentFile}:" . __LINE__ .
                " - 에이전트 데이터 로드 실패: " . $e->getMessage());
        }

        return [];
    }

    /**
     * 히스토리에 이벤트 추가
     *
     * @param string $event 이벤트 이름
     * @param array $data 이벤트 데이터
     */
    public function addToHistory(string $event, array $data): void {
        $this->history[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 감정 상태 설정
     *
     * @param string $emotion 감정 상태
     * @param float $intensity 감정 강도 (0.0 ~ 1.0)
     */
    public function setEmotionalState(string $emotion, float $intensity): void {
        $this->data['emotional_state'] = $emotion;
        $this->data['emotional_intensity'] = max(0.0, min(1.0, $intensity));
    }

    /**
     * 메시지 분석 (Agent17 확장 기능)
     *
     * @param string $message 분석할 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        $intent = 'general';
        $emotionalState = 'neutral';

        // 도움 요청 감지
        if (preg_match('/도와|모르겠|어떻게|힘들|어려워/u', $message)) {
            $intent = 'help_request';
            $emotionalState = 'confused';
        }

        // 좌절감 감지
        if (preg_match('/포기|그만|싫어|못하겠|짜증/u', $message)) {
            $intent = 'frustration_expression';
            $emotionalState = 'frustrated';
        }

        // 진행 상황 보고 감지
        if (preg_match('/했어요|완료|끝났|다음/u', $message)) {
            $intent = 'progress_report';
            $emotionalState = 'positive';
        }

        return [
            'intent' => $intent,
            'emotional_state' => $emotionalState,
            'message_length' => mb_strlen($message),
            'has_question' => strpos($message, '?') !== false
        ];
    }

    /**
     * 상황 결정 (Agent17 확장 기능)
     *
     * @param array $context 컨텍스트 데이터
     * @return string 상황 코드 (R1-R5)
     */
    public function determineSituation(array $context): string {
        $completionRate = $context['completion_rate'] ?? 50;
        $emotionalState = $context['emotional_state'] ?? 'neutral';

        if ($emotionalState === 'frustrated' || $completionRate < 20) {
            return 'R5';
        } elseif ($completionRate < 40) {
            return 'R4';
        } elseif ($completionRate < 60) {
            return 'R3';
        } elseif ($completionRate < 80) {
            return 'R2';
        }

        return 'R1';
    }

    /**
     * 컨텍스트 저장 (Agent17 확장 기능)
     *
     * @param int $userId 사용자 ID
     * @param array $context 저장할 컨텍스트
     * @return bool 성공 여부
     */
    public function saveContext(int $userId, array $context): bool {
        global $DB;

        try {
            $record = new stdClass();
            $record->userid = $userId;
            $record->agent_id = 'agent17';
            $record->context_data = json_encode($context);
            $record->updated_at = date('Y-m-d H:i:s');

            $existing = $DB->get_record('at_agent_context', [
                'userid' => $userId,
                'agent_id' => 'agent17'
            ]);

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('at_agent_context', $record);
            } else {
                $record->created_at = date('Y-m-d H:i:s');
                $DB->insert_record('at_agent_context', $record);
            }

            return true;

        } catch (Exception $e) {
            error_log("[Agent17DataContext] {$this->currentFile}:" . __LINE__ .
                " - 컨텍스트 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 히스토리 조회
     *
     * @return array 히스토리 배열
     */
    public function getHistory(): array {
        return $this->history;
    }
}

/*
 * 관련 인터페이스: IDataContext
 * 위치: /ontology_engineering/persona_engine/core/IDataContext.php
 *
 * 메서드:
 * - loadContext(int $userId, array $sessionData): array
 * - get(string $field, $default): mixed
 * - set(string $field, $value): void
 * - toArray(): array
 * - refresh(): void
 * - loadAgentData(string $agentId): array
 * - addToHistory(string $event, array $data): void
 * - setEmotionalState(string $emotion, float $intensity): void
 *
 * Agent17 확장 메서드:
 * - analyzeMessage(string $message): array
 * - determineSituation(array $context): string
 * - saveContext(int $userId, array $context): bool
 * - getHistory(): array
 *
 * 관련 DB 테이블:
 * - at_user_learning_state (사용자 학습 상태)
 * - at_agent_context (에이전트 컨텍스트)
 * - at_user_activities (사용자 활동)
 */
