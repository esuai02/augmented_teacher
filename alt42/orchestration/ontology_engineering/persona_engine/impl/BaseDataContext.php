<?php
/**
 * BaseDataContext - 기본 데이터 컨텍스트 구현
 *
 * IDataContext 인터페이스의 기본 구현체
 * Moodle DB 연동 및 사용자 컨텍스트 관리
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 2.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/../core/IDataContext.php');

class BaseDataContext implements IDataContext {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array 캐시된 데이터 */
    protected $cache = [];

    /** @var string 에이전트 ID */
    protected $agentId;

    /** @var array 세션 데이터 저장소 */
    protected $sessionStorage = [];

    /** @var array 감정 키워드 사전 */
    protected $emotionKeywords = [
        'positive' => ['좋', '감사', '기쁨', '잘', '행복', '신나', '재미', '성공', '해냈', '이해', '최고', '대박'],
        'negative' => ['싫', '힘들', '어려', '모르', '못', '불안', '걱정', '스트레스', '포기', '최악', '짜증', '화나'],
        'neutral' => ['그냥', '보통', '평범', '일반', '음', '글쎄', '그래'],
        'anxious' => ['긴장', '떨려', '불안', '걱정', '두렵', '무서', '초조'],
        'confused' => ['혼란', '모르겠', '이해가 안', '뭔지', '어떻게', '왜', '헷갈'],
        'frustrated' => ['짜증', '화나', '답답', '왜 안', '계속', '또', '어휴', '에휴']
    ];

    /** @var array 의도 패턴 */
    protected $intentPatterns = [
        'question' => ['?', '어떻게', '왜', '무엇', '뭐', '언제', '어디', '누가', '어느'],
        'help_request' => ['도와', '알려', '설명', '가르쳐', '모르겠어', '해줘', '부탁'],
        'confirmation' => ['맞아', '그래', '응', '네', '확인', '알겠어', '이해했어'],
        'complaint' => ['불만', '싫어', '왜 이래', '안 돼', '문제', '이상해'],
        'greeting' => ['안녕', '반가', 'ㅎㅇ', '하이', '헬로', '좋은 아침'],
        'farewell' => ['잘가', '바이', '안녕', '다음에', '끝', '나갈게']
    ];

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID
     */
    public function __construct(string $agentId = 'default') {
        $this->agentId = $agentId;
    }

    // ==================== IDataContext 인터페이스 구현 ====================

    /**
     * 컨텍스트 로드 (AbstractPersonaEngine 호환)
     *
     * AbstractPersonaEngine::process()에서 호출하는 메인 메서드
     * loadStudentContext()를 래핑하여 호환성 제공
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터 (옵션)
     * @return array 컨텍스트 데이터
     */
    public function loadContext(int $userId, array $sessionData = []): array {
        return $this->loadStudentContext($userId, $sessionData);
    }

    /**
     * 사용자 ID로 컨텍스트 로드 (자식 클래스 호환)
     *
     * 자식 클래스에서 parent::loadByUserId() 호출 시 사용
     * loadStudentContext()의 별칭
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터 (옵션)
     * @return array 컨텍스트 데이터
     */
    public function loadByUserId(int $userId, array $sessionData = []): array {
        return $this->loadStudentContext($userId, $sessionData);
    }

    /**
     * 에이전트별 데이터 로드 (AbstractPersonaEngine 호환)
     *
     * @param string $agentId 에이전트 ID
     * @return array 에이전트 관련 데이터
     */
    public function loadAgentData(string $agentId): array {
        global $DB;

        $agentData = [
            'agent_id' => $agentId,
            'loaded_at' => date('Y-m-d H:i:s')
        ];

        try {
            // 에이전트별 설정 로드 (테이블이 있는 경우)
            $config = $DB->get_record('at_agent_config', ['agent_id' => $agentId]);
            if ($config) {
                $agentData['config'] = json_decode($config->config_data ?? '{}', true);
                $agentData['is_active'] = (bool)($config->is_active ?? true);
            }
        } catch (Exception $e) {
            // 테이블이 없거나 에러 시 기본값 유지
            error_log("[BaseDataContext] {$this->currentFile}:" . __LINE__ .
                " - 에이전트 데이터 로드 실패 (정상일 수 있음): " . $e->getMessage());
        }

        return $agentData;
    }

    /**
     * 학생 컨텍스트 로드 (인터페이스 구현)
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터 (옵션)
     * @return array 학생 컨텍스트 데이터
     */
    public function loadStudentContext(int $userId, array $sessionData = []): array {
        global $DB;

        $cacheKey = "student_{$userId}";
        if (isset($this->cache[$cacheKey])) {
            return array_merge($this->cache[$cacheKey], $sessionData);
        }

        try {
            $context = [
                'user_type' => 'student',
                'loaded_at' => date('Y-m-d H:i:s')
            ];

            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $userId]);
            if ($user) {
                $context['user'] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email
                ];
            }

            // 사용자 역할 정보 (fieldid=22)
            $roleData = $DB->get_record_sql(
                "SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            $context['user_role'] = $roleData ? $roleData->data : 'student';

            // 기존 페르소나 상태 로드
            $personaState = $DB->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'agent_id' => $this->agentId
            ]);
            if ($personaState) {
                $context['previous_persona'] = $personaState->persona_id;
                $context['persona_confidence'] = $personaState->confidence;
                $context['last_interaction'] = $personaState->updated_at;
            }

            // 학습 진도 정보 (있는 경우)
            $this->loadLearningProgress($userId, $context);

            $this->cache[$cacheKey] = $context;
            return array_merge($context, $sessionData);

        } catch (Exception $e) {
            error_log("[BaseDataContext] {$this->currentFile}:" . __LINE__ .
                " - 학생 컨텍스트 로드 실패: " . $e->getMessage());
            return array_merge(['error' => $e->getMessage()], $sessionData);
        }
    }

    /**
     * 교사 컨텍스트 로드 (인터페이스 구현)
     *
     * @param int $teacherId 교사 ID
     * @return array 교사 컨텍스트 데이터
     */
    public function loadTeacherContext(int $teacherId): array {
        global $DB;

        $cacheKey = "teacher_{$teacherId}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $context = [
                'user_type' => 'teacher',
                'loaded_at' => date('Y-m-d H:i:s')
            ];

            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $teacherId]);
            if ($user) {
                $context['user'] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email
                ];
            }

            // 교사 역할 확인
            $context['user_role'] = 'teacher';

            // 담당 과목/클래스 정보 (있는 경우)
            $this->loadTeacherCourses($teacherId, $context);

            $this->cache[$cacheKey] = $context;
            return $context;

        } catch (Exception $e) {
            error_log("[BaseDataContext] {$this->currentFile}:" . __LINE__ .
                " - 교사 컨텍스트 로드 실패: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 컨텍스트 데이터 업데이트 (인터페이스 구현)
     *
     * @param int $userId 사용자 ID
     * @param array $data 업데이트할 데이터
     * @return bool 성공 여부
     */
    public function updateContext(int $userId, array $data): bool {
        global $DB;

        try {
            $personaId = $data['persona_id'] ?? null;
            $confidence = $data['confidence'] ?? 0.5;

            if (!$personaId) {
                return false;
            }

            $existing = $DB->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'agent_id' => $this->agentId
            ]);

            $now = date('Y-m-d H:i:s');

            if ($existing) {
                $existing->persona_id = $personaId;
                $existing->confidence = $confidence;
                $existing->context_data = json_encode($data);
                $existing->updated_at = $now;
                $DB->update_record('at_agent_persona_state', $existing);
            } else {
                $record = new stdClass();
                $record->user_id = $userId;
                $record->agent_id = $this->agentId;
                $record->persona_id = $personaId;
                $record->confidence = $confidence;
                $record->context_data = json_encode($data);
                $record->created_at = $now;
                $record->updated_at = $now;
                $DB->insert_record('at_agent_persona_state', $record);
            }

            // 캐시 무효화
            unset($this->cache["student_{$userId}"]);
            unset($this->cache["teacher_{$userId}"]);

            return true;

        } catch (Exception $e) {
            error_log("[BaseDataContext] {$this->currentFile}:" . __LINE__ .
                " - 컨텍스트 업데이트 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 세션 데이터 저장 (인터페이스 구현)
     *
     * @param int $userId 사용자 ID
     * @param string $key 키
     * @param mixed $value 값
     * @return bool 성공 여부
     */
    public function saveSessionData(int $userId, string $key, $value): bool {
        try {
            if (!isset($this->sessionStorage[$userId])) {
                $this->sessionStorage[$userId] = [];
            }

            $this->sessionStorage[$userId][$key] = [
                'value' => $value,
                'saved_at' => date('Y-m-d H:i:s')
            ];

            return true;

        } catch (Exception $e) {
            error_log("[BaseDataContext] {$this->currentFile}:" . __LINE__ .
                " - 세션 데이터 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 세션 데이터 조회 (인터페이스 구현)
     *
     * @param int $userId 사용자 ID
     * @param string $key 키
     * @param mixed $default 기본값
     * @return mixed 세션 데이터
     */
    public function getSessionData(int $userId, string $key, $default = null) {
        if (isset($this->sessionStorage[$userId][$key])) {
            return $this->sessionStorage[$userId][$key]['value'];
        }
        return $default;
    }

    /**
     * 컨텍스트 필드 값 조회 (인터페이스 구현)
     *
     * @param string $field 필드명 (점 표기법 지원: student.grade)
     * @param array $context 컨텍스트
     * @return mixed 필드 값
     */
    public function getContextValue(string $field, array $context) {
        $parts = explode('.', $field);
        $value = $context;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    // ==================== 확장 메서드 ====================

    /**
     * 현재 상황 코드 결정
     *
     * @param array $sessionData 세션 데이터
     * @return string 상황 코드
     */
    public function determineSituation(array $sessionData): string {
        $emotion = $sessionData['emotional_state'] ?? 'neutral';
        if (in_array($emotion, ['anxious', 'frustrated', 'negative'])) {
            return 'emotional_support';
        }

        $intent = $sessionData['intent'] ?? '';
        if ($intent === 'question') return 'information_request';
        if ($intent === 'help_request') return 'assistance_needed';

        $activity = $sessionData['current_activity'] ?? '';
        if (!empty($activity)) return $activity;

        return 'default';
    }

    /**
     * 메시지 분석
     *
     * @param string $message 사용자 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        return [
            'original' => $message,
            'length' => mb_strlen($message),
            'emotional_state' => $this->detectEmotion($message),
            'intent' => $this->detectIntent($message),
            'keywords' => $this->extractKeywords($message),
            'has_question' => mb_strpos($message, '?') !== false,
            'is_greeting' => $this->isGreeting($message),
            'urgency' => $this->detectUrgency($message)
        ];
    }

    /**
     * 감정 감지
     */
    protected function detectEmotion(string $message): string {
        $scores = [];
        foreach ($this->emotionKeywords as $emotion => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $score++;
                }
            }
            $scores[$emotion] = $score;
        }

        $maxEmotion = 'neutral';
        $maxScore = 0;
        foreach ($scores as $emotion => $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
                $maxEmotion = $emotion;
            }
        }
        return $maxEmotion;
    }

    /**
     * 의도 감지
     */
    protected function detectIntent(string $message): string {
        foreach ($this->intentPatterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_strpos($message, $pattern) !== false) {
                    return $intent;
                }
            }
        }
        return 'statement';
    }

    /**
     * 키워드 추출
     */
    protected function extractKeywords(string $message): array {
        $words = preg_split('/\s+/', $message);
        $keywords = [];
        $stopwords = ['그', '저', '이', '은', '는', '이', '가', '을', '를', '에', '의', '와', '과'];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 2 && !in_array($word, $stopwords)) {
                $keywords[] = $word;
            }
        }
        return array_unique($keywords);
    }

    /**
     * 인사 감지
     */
    protected function isGreeting(string $message): bool {
        $greetings = ['안녕', '반가', 'ㅎㅇ', '하이', '헬로'];
        foreach ($greetings as $greeting) {
            if (mb_strpos($message, $greeting) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 긴급도 감지
     */
    protected function detectUrgency(string $message): string {
        $urgentKeywords = ['급해', '빨리', '지금', '당장', '긴급'];
        $highKeywords = ['중요', '꼭', '반드시', '오늘까지'];

        foreach ($urgentKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return 'urgent';
            }
        }
        foreach ($highKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                return 'high';
            }
        }
        return 'normal';
    }

    /**
     * 학습 진도 정보 로드
     */
    protected function loadLearningProgress(int $userId, array &$context): void {
        global $DB;

        try {
            // 학습 진도 테이블이 있는 경우 로드
            $progress = $DB->get_records('at_student_progress', ['user_id' => $userId]);
            if ($progress) {
                $context['learning_progress'] = $progress;
            }
        } catch (Exception $e) {
            // 테이블이 없거나 에러 발생 시 무시
        }
    }

    /**
     * 교사 담당 코스 정보 로드
     */
    protected function loadTeacherCourses(int $teacherId, array &$context): void {
        global $DB;

        try {
            // Moodle 코스 정보 로드
            $courses = $DB->get_records_sql(
                "SELECT c.id, c.fullname, c.shortname
                 FROM mdl_course c
                 JOIN mdl_enrol e ON e.courseid = c.id
                 JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
                 WHERE ue.userid = ?",
                [$teacherId]
            );

            if ($courses) {
                $context['courses'] = array_values($courses);
            }
        } catch (Exception $e) {
            // 에러 발생 시 무시
        }
    }

    /**
     * 캐시 클리어
     */
    public function clearCache(): void {
        $this->cache = [];
    }

    /**
     * 에이전트 ID 설정
     */
    public function setAgentId(string $agentId): void {
        $this->agentId = $agentId;
        $this->clearCache();
    }

    /**
     * 감정 키워드 추가
     */
    public function addEmotionKeywords(string $emotion, array $keywords): void {
        if (!isset($this->emotionKeywords[$emotion])) {
            $this->emotionKeywords[$emotion] = [];
        }
        $this->emotionKeywords[$emotion] = array_merge($this->emotionKeywords[$emotion], $keywords);
    }

    /**
     * 의도 패턴 추가
     */
    public function addIntentPatterns(string $intent, array $patterns): void {
        if (!isset($this->intentPatterns[$intent])) {
            $this->intentPatterns[$intent] = [];
        }
        $this->intentPatterns[$intent] = array_merge($this->intentPatterns[$intent], $patterns);
    }
}

/*
 * 관련 DB 테이블:
 *
 * - mdl_at_agent_persona_state (페르소나 상태 저장)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), 사용자 ID
 *   - agent_id: varchar(50), 에이전트 식별자
 *   - persona_id: varchar(50), 현재 페르소나 ID
 *   - confidence: decimal(3,2), 신뢰도 (0.00-1.00)
 *   - context_data: text, JSON 컨텍스트 데이터
 *   - created_at: datetime
 *   - updated_at: datetime
 *   - INDEX: (user_id, agent_id)
 *
 * - mdl_at_student_context (학생 컨텍스트)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), 사용자 ID
 *   - context_type: varchar(50), 컨텍스트 유형
 *   - context_value: text, 컨텍스트 값
 *   - created_at: datetime
 *
 * - mdl_at_persona_session (페르소나 세션)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), 사용자 ID
 *   - session_key: varchar(100), 세션 키
 *   - session_data: text, 세션 데이터 (JSON)
 *   - expires_at: datetime
 */
