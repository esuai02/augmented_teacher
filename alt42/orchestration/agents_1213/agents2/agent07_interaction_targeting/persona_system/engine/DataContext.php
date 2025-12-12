<?php
/**
 * Data Context for Agent07 Persona System
 *
 * 페르소나 식별에 필요한 컨텍스트 데이터를 수집하고 관리
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - contextlist.md: 컨텍스트 파라미터 정의
 * - PersonaRuleEngine.php: 수집된 데이터 사용처
 *
 * DB Tables:
 * - mdl_agent07_context_log: 컨텍스트 로그
 */

class DataContext {

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var int 사용자 ID */
    private $userId;

    /** @var array 수집된 컨텍스트 데이터 */
    private $data = array();

    /** @var array 기본값 정의 */
    private $defaults;

    /**
     * 생성자
     *
     * @param object $db Moodle $DB 객체
     * @param int $userId 사용자 ID
     */
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
        $this->defaults = $this->getDefaultValues();
    }

    /**
     * 기본값 정의
     *
     * @return array
     */
    private function getDefaultValues() {
        return array(
            // Activity Parameters
            'current_activity' => 'idle',
            'pomodoro_active' => false,
            'session_type' => 'focus',
            'help_button_clicked' => false,
            'preview_activity' => false,
            'viewing_roadmap' => false,
            'viewing_preview_material' => false,
            'material_access_count' => 0,

            // Temporal Parameters
            'time_to_class' => 1440,
            'stuck_duration' => 0,
            'idle_time' => 0,
            'no_activity_duration' => 0,
            'session_start' => false,
            'session_ending' => false,

            // Behavioral Parameters
            'help_request_explicit' => false,
            'problem_articulation' => 0.5,
            'urgency_expressed' => false,
            'confusion_signals' => false,
            'interaction_frequency_decreasing' => false,
            'question_type' => 'what',
            'self_discovery_preference' => false,
            'distraction_signals' => false,
            'task_switching_frequency' => 0,
            'waiting_passively' => false,
            'reassurance_seeking_count' => 0,
            'question_preparation' => false,
            'early_termination_request' => false,
            'reflection_avoidance' => false,
            'option_exploration' => false,

            // Emotional Parameters
            'anxiety_signals' => false,
            'confidence_score' => 0.5,
            'motivation_score' => 0.5,
            'meaning_questioning' => false,
            'negative_self_talk' => false,
            'self_criticism_score' => 0.3,
            'achievement_dismissal' => false,
            'reflection_willingness' => 0.5,

            // Message Parameters
            'message' => '',
            'message_length' => 0,
            'response_length' => 0,
            'goal_statement_length' => 0,
            'expression_vague' => false,

            // Performance Parameters
            'focus_score' => 0.5,
            'task_completion_rate' => 0.5,
            'error_count' => 0,
            'preparation_level' => 0.5,
            'initiative_score' => 0.5,
            'goal_clarity' => 0.5,
            'plan_specificity' => 0.5,
            'self_direction_score' => 0.5,
            'concept_curiosity' => 0.5,
            'direction_certainty' => 0.5,

            // Source Classification
            'goal_source' => 'internal',
            'intrinsic_motivation' => 0.5,
            'obligation_driven' => false,

            // Temporal Scope
            'vision_timeframe' => 'medium_term',
            'focus_timeframe' => 'short_term',
            'roadmap_interest' => 0.5,
            'milestone_awareness' => false,
            'discovery_oriented' => false,

            // Session State
            'weekly_planning' => false,
            'daily_summary' => false,
            'self_assessment_active' => false,
            'learning_synthesis_attempt' => false,
            'termination_urgency' => false,
            'immediate_needs_priority' => false
        );
    }

    /**
     * 컨텍스트 데이터 수집
     *
     * @param array $inputData 추가 입력 데이터 (메시지 등)
     * @return array 수집된 전체 컨텍스트
     */
    public function collect($inputData = array()) {
        // 1. 기본값으로 시작
        $this->data = $this->defaults;

        // 2. 입력 데이터 적용
        $this->applyInputData($inputData);

        // 3. DB에서 사용자 상태 로드
        $this->loadUserState();

        // 4. 세션 상태 로드
        $this->loadSessionState();

        // 5. 메시지 분석 (있는 경우)
        if (!empty($inputData['message'])) {
            $this->analyzeMessage($inputData['message']);
        }

        // 6. 파생 값 계산
        $this->calculateDerivedValues();

        return $this->data;
    }

    /**
     * 입력 데이터 적용
     *
     * @param array $inputData 입력 데이터
     */
    private function applyInputData($inputData) {
        foreach ($inputData as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            }
        }

        // 메시지 길이 자동 계산
        if (isset($inputData['message'])) {
            $this->data['message'] = $inputData['message'];
            $this->data['message_length'] = mb_strlen($inputData['message']);
        }
    }

    /**
     * DB에서 사용자 상태 로드
     */
    private function loadUserState() {
        try {
            // 사용자의 최근 학습 활동 조회
            $sql = "SELECT * FROM {agent07_user_state}
                    WHERE userid = :userid
                    ORDER BY updated_at DESC LIMIT 1";

            $state = $this->db->get_record_sql($sql, array('userid' => $this->userId));

            if ($state) {
                $this->data['current_activity'] = $state->current_activity ?: 'idle';
                $this->data['pomodoro_active'] = (bool)$state->pomodoro_active;
                $this->data['focus_score'] = (float)$state->focus_score;
                $this->data['motivation_score'] = (float)$state->motivation_score;
            }

        } catch (Exception $e) {
            // DB 오류 시 기본값 유지
            error_log(sprintf(
                "[DataContext] Failed to load user state: %s (File: %s, Line: %d)",
                $e->getMessage(),
                __FILE__,
                __LINE__
            ));
        }
    }

    /**
     * 세션 상태 로드
     */
    private function loadSessionState() {
        // 세션에서 추가 정보 로드 (있는 경우)
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION['agent07_session_start'])) {
                $sessionStartTime = $_SESSION['agent07_session_start'];
                $elapsed = time() - $sessionStartTime;

                $this->data['session_start'] = ($elapsed < 300); // 5분 이내
            }

            if (isset($_SESSION['agent07_help_clicks'])) {
                $this->data['help_button_clicked'] = $_SESSION['agent07_help_clicks'] > 0;
            }
        }
    }

    /**
     * 메시지 분석
     *
     * @param string $message 사용자 메시지
     */
    private function analyzeMessage($message) {
        // 키워드 기반 분석

        // 도움 요청 키워드
        $helpKeywords = array('도와', '도움', '모르겠', '어려워', '막혀', '안돼', '헷갈');
        foreach ($helpKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['help_request_explicit'] = true;
                break;
            }
        }

        // 긴급성 키워드
        $urgentKeywords = array('급해', '빨리', '지금', '당장', '바로');
        foreach ($urgentKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['urgency_expressed'] = true;
                break;
            }
        }

        // 혼란 신호
        $confusionKeywords = array('음', '글쎄', '그냥', '몰라', '뭐지', '아...');
        $confusionCount = 0;
        foreach ($confusionKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $confusionCount++;
            }
        }
        $this->data['confusion_signals'] = $confusionCount >= 2 || mb_strlen($message) < 10;

        // 질문 유형 분석
        if (mb_strpos($message, '왜') !== false) {
            $this->data['question_type'] = 'why';
            $this->data['concept_curiosity'] = min(1.0, $this->data['concept_curiosity'] + 0.2);
        } elseif (mb_strpos($message, '어떻게') !== false) {
            $this->data['question_type'] = 'how';
        } elseif (mb_strpos($message, '뭐') !== false || mb_strpos($message, '무엇') !== false) {
            $this->data['question_type'] = 'what';
        }

        // 부정적 자기 대화
        $negativeKeywords = array('못했', '부족', '실패', '후회', '바보', '멍청');
        foreach ($negativeKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['negative_self_talk'] = true;
                $this->data['self_criticism_score'] = min(1.0, $this->data['self_criticism_score'] + 0.3);
                break;
            }
        }

        // 모호한 표현
        $vagueKeywords = array('잘하고싶', '열심히', '좀더', '그냥', '뭔가');
        foreach ($vagueKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['expression_vague'] = true;
                $this->data['goal_clarity'] = max(0, $this->data['goal_clarity'] - 0.2);
                break;
            }
        }

        // 외부 압력 표현
        $externalKeywords = array('엄마가', '아빠가', '선생님이', '해야해서', '시켜서');
        foreach ($externalKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['goal_source'] = 'external';
                $this->data['obligation_driven'] = true;
                $this->data['intrinsic_motivation'] = max(0, $this->data['intrinsic_motivation'] - 0.3);
                break;
            }
        }

        // 조기 종료 요청
        $terminationKeywords = array('끝내자', '그만', '됐어', '가야해', '빨리끝');
        foreach ($terminationKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $this->data['early_termination_request'] = true;
                $this->data['termination_urgency'] = true;
                break;
            }
        }

        // 문제 표현 명확도 (메시지 길이 기반 추정)
        $messageLen = mb_strlen($message);
        if ($messageLen > 100) {
            $this->data['problem_articulation'] = 0.8;
        } elseif ($messageLen > 50) {
            $this->data['problem_articulation'] = 0.6;
        } elseif ($messageLen > 20) {
            $this->data['problem_articulation'] = 0.4;
        } else {
            $this->data['problem_articulation'] = 0.2;
        }
    }

    /**
     * 파생 값 계산
     */
    private function calculateDerivedValues() {
        // 반성 의지 계산
        if ($this->data['self_assessment_active'] || $this->data['learning_synthesis_attempt']) {
            $this->data['reflection_willingness'] = min(1.0, $this->data['reflection_willingness'] + 0.3);
        }

        // 회고 회피 계산
        if ($this->data['early_termination_request'] && $this->data['message_length'] < 20) {
            $this->data['reflection_avoidance'] = true;
        }

        // 수동적 대기 계산
        if ($this->data['no_activity_duration'] > 300 && $this->data['initiative_score'] < 0.4) {
            $this->data['waiting_passively'] = true;
        }

        // 집중력 저하 신호
        if ($this->data['task_switching_frequency'] > 3 || $this->data['idle_time'] > 60) {
            $this->data['distraction_signals'] = true;
        }
    }

    /**
     * 특정 컨텍스트 값 가져오기
     *
     * @param string $key 키
     * @return mixed
     */
    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * 특정 컨텍스트 값 설정
     *
     * @param string $key 키
     * @param mixed $value 값
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * 전체 컨텍스트 데이터 반환
     *
     * @return array
     */
    public function getAll() {
        return $this->data;
    }

    /**
     * 컨텍스트 로깅
     *
     * @param string $sessionId 세션 ID
     */
    public function logContext($sessionId) {
        try {
            $record = new stdClass();
            $record->userid = $this->userId;
            $record->session_id = $sessionId;
            $record->context_data = json_encode($this->data);
            $record->created_at = time();

            $this->db->insert_record('agent07_context_log', $record);

        } catch (Exception $e) {
            error_log(sprintf(
                "[DataContext] Logging failed: %s (File: %s, Line: %d)",
                $e->getMessage(),
                __FILE__,
                __LINE__
            ));
        }
    }
}
