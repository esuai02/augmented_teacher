<?php
/**
 * MoodleDataContext - Moodle 데이터 컨텍스트 구현
 *
 * IDataContext 인터페이스의 Moodle 연동 구현체
 * Moodle DB에서 사용자 및 학습 데이터 로드
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @author Claude Code
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/../core/IDataContext.php');

class MoodleDataContext implements IDataContext {

    /** @var array 컨텍스트 데이터 */
    private $data = [];

    /** @var int 사용자 ID */
    private $userId;

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 세션 히스토리 */
    private $history = [];

    /** @var array 감정 키워드 매핑 */
    private $emotionKeywords = [
        'anxiety' => ['걱정', '불안', '두려워', '무서워', '떨려'],
        'frustration' => ['짜증', '화나', '열받', '답답', '힘들어', '어려워'],
        'confusion' => ['모르겠', '이해가 안', '헷갈', '복잡해'],
        'confidence' => ['할 수 있', '자신', '쉬워', '알겠', '이해'],
        'boredom' => ['지루', '재미없', '심심'],
        'joy' => ['좋아', '재밌', '흥미', '신나']
    ];

    /**
     * 생성자
     */
    public function __construct(bool $debugMode = false) {
        $this->debugMode = $debugMode;
    }

    /**
     * @inheritDoc
     */
    public function loadContext(int $userId, array $sessionData = []): array {
        global $DB;

        $this->userId = $userId;
        $this->data = $sessionData;

        try {
            // 사용자 기본 정보 로드
            $user = $DB->get_record('user', ['id' => $userId]);
            if ($user) {
                $this->data['user'] = [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'lastaccess' => $user->lastaccess
                ];
                $this->data['firstname'] = $user->firstname;
            }

            // 사용자 역할 정보 로드 (fieldid=22)
            $roleData = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            $this->data['role'] = $roleData->data ?? 'student';

            // 학년 정보 로드 (fieldid가 다를 수 있음)
            $gradeData = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 1",
                [$userId]
            );
            $this->data['grade'] = $gradeData->data ?? null;

            // 이전 페르소나 상태 로드
            $this->loadPreviousState();

            // 타임스탬프
            $this->data['timestamp'] = time();
            $this->data['datetime'] = date('Y-m-d H:i:s');

        } catch (\Exception $e) {
            error_log("[MoodleDataContext ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 컨텍스트 로드 실패: " . $e->getMessage());
        }

        return $this->data;
    }

    /**
     * 이전 페르소나 상태 로드
     */
    private function loadPreviousState(): void {
        global $DB;

        try {
            $states = $DB->get_records_sql(
                "SELECT * FROM {at_agent_persona_state} 
                 WHERE userid = ? 
                 ORDER BY timemodified DESC",
                [$this->userId]
            );

            $this->data['previous_states'] = [];
            foreach ($states as $state) {
                $this->data['previous_states'][$state->agent_id] = [
                    'persona_id' => $state->persona_id,
                    'state_data' => json_decode($state->state_data, true),
                    'timemodified' => $state->timemodified
                ];
            }

            // 가장 최근 상태
            if (!empty($states)) {
                $latest = reset($states);
                $this->data['current_persona'] = $latest->persona_id;
                $this->data['current_situation'] = 'S1'; // 기본값
            }

        } catch (\Exception $e) {
            if ($this->debugMode) {
                error_log("[MoodleDataContext DEBUG] 이전 상태 로드 실패 (테이블 없을 수 있음): " . 
                          $e->getMessage());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $field, $default = null) {
        return $this->getNestedValue($this->data, $field, $default);
    }

    /**
     * @inheritDoc
     */
    public function set(string $field, $value): void {
        $this->setNestedValue($this->data, $field, $value);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function refresh(): void {
        if ($this->userId) {
            $sessionData = $this->data;
            $this->loadContext($this->userId, $sessionData);
        }
    }

    /**
     * @inheritDoc
     */
    public function loadAgentData(string $agentId): array {
        global $DB;

        $agentData = [];

        try {
            // 에이전트별 맞춤 데이터 로드
            switch ($agentId) {
                case 'agent11':
                    // 문제 노트 관련 데이터
                    $agentData = $this->loadProblemNoteData();
                    break;
                case 'agent01':
                    // 온보딩 관련 데이터
                    $agentData = $this->loadOnboardingData();
                    break;
                default:
                    // 기본 에이전트 데이터
                    $agentData = ['agent_id' => $agentId];
            }

        } catch (\Exception $e) {
            error_log("[MoodleDataContext ERROR] {$this->currentFile}:" . __LINE__ . 
                      " - 에이전트 데이터 로드 실패: " . $e->getMessage());
        }

        return $agentData;
    }

    /**
     * 문제 노트 데이터 로드 (agent11)
     *
     * @return array
     */
    private function loadProblemNoteData(): array {
        global $DB;

        $data = ['agent_id' => 'agent11'];

        try {
            // 최근 풀이 노트 통계
            $recentNotes = $DB->get_records_sql(
                "SELECT * FROM {at_problem_notes} 
                 WHERE userid = ? 
                 ORDER BY timecreated DESC LIMIT 10",
                [$this->userId]
            );

            $data['recent_notes_count'] = count($recentNotes);
            $data['has_problem_history'] = !empty($recentNotes);

            // 오답 패턴 분석 결과
            $patterns = $DB->get_records_sql(
                "SELECT * FROM {at_wrong_answer_patterns} 
                 WHERE userid = ? 
                 ORDER BY frequency DESC LIMIT 5",
                [$this->userId]
            );

            $data['common_error_patterns'] = [];
            foreach ($patterns as $p) {
                $data['common_error_patterns'][] = [
                    'pattern_type' => $p->pattern_type ?? 'unknown',
                    'frequency' => $p->frequency ?? 0
                ];
            }

        } catch (\Exception $e) {
            // 테이블이 없을 수 있음
            if ($this->debugMode) {
                error_log("[MoodleDataContext DEBUG] 문제노트 테이블 없음: " . $e->getMessage());
            }
        }

        return $data;
    }

    /**
     * 온보딩 데이터 로드 (agent01)
     *
     * @return array
     */
    private function loadOnboardingData(): array {
        global $DB;

        $data = ['agent_id' => 'agent01'];

        try {
            // 온보딩 진행 상태
            $progress = $DB->get_record('at_onboarding_progress', [
                'userid' => $this->userId
            ]);

            if ($progress) {
                $data['onboarding_step'] = $progress->current_step ?? 0;
                $data['onboarding_completed'] = (bool)($progress->completed ?? false);
            } else {
                $data['onboarding_step'] = 0;
                $data['onboarding_completed'] = false;
            }

        } catch (\Exception $e) {
            if ($this->debugMode) {
                error_log("[MoodleDataContext DEBUG] 온보딩 테이블 없음");
            }
            $data['onboarding_step'] = 0;
            $data['onboarding_completed'] = false;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function addToHistory(string $event, array $data): void {
        $this->history[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ];

        // 히스토리를 컨텍스트에도 반영
        $this->data['history'] = $this->history;
    }

    /**
     * @inheritDoc
     */
    public function setEmotionalState(string $emotion, float $intensity): void {
        $this->data['detected_emotion'] = $emotion;
        $this->data['emotion_intensity'] = max(0.0, min(1.0, $intensity));
    }

    /**
     * 메시지에서 감정 감지
     *
     * @param string $message 메시지
     * @return array ['emotion' => string, 'intensity' => float]
     */
    public function detectEmotionFromMessage(string $message): array {
        foreach ($this->emotionKeywords as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return [
                        'emotion' => $emotion,
                        'intensity' => 0.7
                    ];
                }
            }
        }

        return [
            'emotion' => 'neutral',
            'intensity' => 0.0
        ];
    }

    /**
     * 중첩 필드 값 가져오기
     */
    private function getNestedValue(array $data, string $field, $default = null) {
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 중첩 필드 값 설정
     */
    private function setNestedValue(array &$data, string $field, $value): void {
        $keys = explode('.', $field);
        $current = &$data;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_user (id, firstname, lastname, email, lastaccess)
 * - mdl_user_info_data (userid, fieldid, data)
 *   - fieldid=22: 역할
 *   - fieldid=1: 학년 (추정)
 * - at_agent_persona_state (userid, agent_id, persona_id, state_data, timecreated, timemodified)
 * - at_problem_notes (agent11용, userid, timecreated 등)
 * - at_wrong_answer_patterns (agent11용)
 * - at_onboarding_progress (agent01용)
 *
 * 참조 파일:
 * - core/IDataContext.php (인터페이스)
 * - agents/agent01_onboarding/persona_system/engine/DataContext.php (원본)
 */
