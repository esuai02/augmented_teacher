<?php
/**
 * DataContext - Moodle DB 연동 데이터 컨텍스트
 *
 * Moodle DB에서 학생 데이터를 로드하고 컨텍스트를 구성합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class DataContext {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var array 캐시된 사용자 데이터 */
    private $userCache = [];

    /** @var array 감정 키워드 사전 */
    private $emotionalKeywords = [
        'negative' => [
            'anxiety' => ['불안', '걱정', '무서워', '두려워', '긴장', '떨려'],
            'frustration' => ['짜증', '화나', '답답', '싫어', '힘들어', '못하겠어'],
            'sadness' => ['슬퍼', '우울', '속상', '서러워', '눈물'],
            'helplessness' => ['모르겠어', '포기', '못해', '안돼', '힘들어']
        ],
        'positive' => [
            'confidence' => ['자신', '할 수 있어', '잘 할', '해볼게'],
            'excitement' => ['기대', '재밌', '좋아', '신나', '흥미'],
            'gratitude' => ['감사', '고마워', '다행', '기쁘']
        ],
        'neutral' => [
            'uncertainty' => ['글쎄', '모르겠', '잘', '그냥', '음'],
            'indifference' => ['상관없', '그럭저럭', '보통']
        ]
    ];

    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * 사용자 ID로 학생 컨텍스트 로드
     *
     * @param int $userId Moodle 사용자 ID
     * @param array $sessionData 현재 세션 데이터 (선택)
     * @return array 학생 컨텍스트
     */
    public function loadByUserId(int $userId, array $sessionData = []): array {
        // 캐시 확인
        $cacheKey = "user_{$userId}";
        if (isset($this->userCache[$cacheKey])) {
            return array_merge($this->userCache[$cacheKey], $sessionData);
        }

        $context = [
            'user_id' => $userId,
            'situation' => $sessionData['situation'] ?? 'S0',
            'user_message' => $sessionData['user_message'] ?? '',
            'response_length' => 0,
            'emotional_keywords' => [],
            'negative_keywords' => [],
            'session_history' => [],
            'moodle_data' => []
        ];

        try {
            // Moodle 사용자 기본 정보
            $user = $this->db->get_record('user', ['id' => $userId], 'id, username, firstname, lastname, email');
            if ($user) {
                $context['moodle_data']['user'] = (array) $user;
            }

            // 역할 정보 가져오기
            $roleData = $this->db->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            if ($roleData) {
                $context['moodle_data']['role'] = $roleData->data;
            }

            // 성적 데이터 요약
            $grades = $this->getGradeSummary($userId);
            $context['moodle_data']['grades'] = $grades;

            // 최근 활동 로그
            $recentActivity = $this->getRecentActivity($userId);
            $context['moodle_data']['recent_activity'] = $recentActivity;

            // AI 세션 히스토리 (커스텀 테이블)
            $sessionHistory = $this->getSessionHistory($userId);
            $context['session_history'] = $sessionHistory;

            // 이전 페르소나 기록
            $previousPersonas = $this->getPreviousPersonas($userId);
            $context['previous_personas'] = $previousPersonas;

            // 캐시 저장
            $this->userCache[$cacheKey] = $context;

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 사용자 데이터 로드 실패: " . $e->getMessage());
        }

        // 세션 데이터 병합
        return array_merge($context, $sessionData);
    }

    /**
     * 현재 상황 코드 결정
     *
     * @param array $sessionData 세션 데이터
     * @return string 상황 코드 (S0-S5, C, Q, E)
     */
    public function determineSituation(array $sessionData): string {
        // 명시적으로 상황이 지정된 경우
        if (!empty($sessionData['situation'])) {
            return $sessionData['situation'];
        }

        // 세션 상태 기반 결정
        $isNewUser = $sessionData['is_new_user'] ?? false;
        $hasActiveSession = $sessionData['has_active_session'] ?? false;
        $currentPhase = $sessionData['current_phase'] ?? '';

        // S0: 정보 수집 (새 학생 또는 진단 필요)
        if ($isNewUser || $currentPhase === 'diagnosis') {
            return 'S0';
        }

        // S1: 신규 학생 등록
        if ($currentPhase === 'registration' || $currentPhase === 'onboarding') {
            return 'S1';
        }

        // S2: 학습 설계
        if ($currentPhase === 'planning' || $currentPhase === 'design') {
            return 'S2';
        }

        // S3: 진도 판단
        if ($currentPhase === 'progress' || $currentPhase === 'assessment') {
            return 'S3';
        }

        // S4: 학부모 상담
        if ($currentPhase === 'parent_meeting' || $currentPhase === 'parent_consultation') {
            return 'S4';
        }

        // S5: 장기 목표
        if ($currentPhase === 'goal_setting' || $currentPhase === 'long_term') {
            return 'S5';
        }

        // C: 복합 상황 감지
        $complexIndicators = $sessionData['complex_indicators'] ?? [];
        if (count($complexIndicators) >= 2) {
            return 'C';
        }

        // E: 정서적 상황 감지
        $emotionalState = $sessionData['emotional_state'] ?? '';
        if (in_array($emotionalState, ['anxious', 'frustrated', 'excited', 'fearful'])) {
            return 'E';
        }

        // Q: 일반 질문
        return 'Q';
    }

    /**
     * 메시지 분석
     *
     * @param string $message 사용자 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        $analysis = [
            'response_length' => mb_strlen($message),
            'word_count' => $this->countWords($message),
            'emotional_keywords' => [],
            'negative_keywords' => [],
            'positive_keywords' => [],
            'emotional_state' => 'neutral',
            'has_question' => strpos($message, '?') !== false || preg_match('/[?？]/', $message),
            'is_short_response' => mb_strlen($message) < 10,
            'is_defensive' => false,
            'shows_confidence' => false,
            'shows_anxiety' => false
        ];

        // 감정 키워드 추출
        foreach ($this->emotionalKeywords as $category => $subcategories) {
            foreach ($subcategories as $emotion => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($message, $keyword) !== false) {
                        $analysis['emotional_keywords'][] = $keyword;

                        if ($category === 'negative') {
                            $analysis['negative_keywords'][] = $keyword;
                        } elseif ($category === 'positive') {
                            $analysis['positive_keywords'][] = $keyword;
                        }
                    }
                }
            }
        }

        // 감정 상태 결정
        $negativeCount = count($analysis['negative_keywords']);
        $positiveCount = count($analysis['positive_keywords']);

        if ($negativeCount > $positiveCount && $negativeCount > 0) {
            $analysis['emotional_state'] = 'negative';
        } elseif ($positiveCount > $negativeCount && $positiveCount > 0) {
            $analysis['emotional_state'] = 'positive';
        }

        // 방어적 응답 감지
        $defensivePatterns = ['몰라요', '모르겠어요', '그냥', '상관없어요', '됐어요'];
        foreach ($defensivePatterns as $pattern) {
            if (mb_strpos($message, $pattern) !== false) {
                $analysis['is_defensive'] = true;
                break;
            }
        }

        // 자신감 표현 감지
        $confidencePatterns = ['잘 할 수', '할 수 있', '자신있', '해볼게'];
        foreach ($confidencePatterns as $pattern) {
            if (mb_strpos($message, $pattern) !== false) {
                $analysis['shows_confidence'] = true;
                break;
            }
        }

        // 불안 표현 감지
        $anxietyPatterns = ['불안', '걱정', '무서워', '두려워', '긴장', '떨려'];
        foreach ($anxietyPatterns as $pattern) {
            if (mb_strpos($message, $pattern) !== false) {
                $analysis['shows_anxiety'] = true;
                break;
            }
        }

        return $analysis;
    }

    /**
     * 성적 요약 조회
     *
     * @param int $userId 사용자 ID
     * @return array 성적 요약
     */
    private function getGradeSummary(int $userId): array {
        try {
            $sql = "SELECT
                        gi.courseid,
                        COUNT(gg.id) as grade_count,
                        AVG(gg.finalgrade) as avg_grade,
                        MAX(gg.finalgrade) as max_grade,
                        MIN(gg.finalgrade) as min_grade
                    FROM {grade_grades} gg
                    JOIN {grade_items} gi ON gg.itemid = gi.id
                    WHERE gg.userid = ?
                    GROUP BY gi.courseid
                    ORDER BY gi.courseid DESC
                    LIMIT 5";

            $grades = $this->db->get_records_sql($sql, [$userId]);
            return $grades ? array_values((array) $grades) : [];

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 성적 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 최근 활동 조회
     *
     * @param int $userId 사용자 ID
     * @param int $days 조회 기간 (일)
     * @return array 최근 활동
     */
    private function getRecentActivity(int $userId, int $days = 7): array {
        try {
            $timeSince = time() - ($days * 24 * 60 * 60);

            $sql = "SELECT
                        component,
                        action,
                        COUNT(*) as count,
                        MAX(timecreated) as last_activity
                    FROM {logstore_standard_log}
                    WHERE userid = ? AND timecreated > ?
                    GROUP BY component, action
                    ORDER BY count DESC
                    LIMIT 10";

            $activities = $this->db->get_records_sql($sql, [$userId, $timeSince]);
            return $activities ? array_values((array) $activities) : [];

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 활동 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * AI 세션 히스토리 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 세션 히스토리
     */
    private function getSessionHistory(int $userId, int $limit = 5): array {
        try {
            // augmented_teacher_sessions 테이블이 존재하는지 확인
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_sessions');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT
                        session_key,
                        current_situation,
                        current_persona,
                        context_data,
                        last_activity
                    FROM {augmented_teacher_sessions}
                    WHERE user_id = ? AND agent_id = 'agent01'
                    ORDER BY last_activity DESC
                    LIMIT ?";

            $sessions = $this->db->get_records_sql($sql, [$userId, $limit]);

            // JSON 데이터 파싱
            $result = [];
            foreach ($sessions as $session) {
                $sessionData = (array) $session;
                if (!empty($sessionData['context_data'])) {
                    $sessionData['context_data'] = json_decode($sessionData['context_data'], true);
                }
                $result[] = $sessionData;
            }

            return $result;

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 세션 히스토리 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 이전 페르소나 기록 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 이전 페르소나 기록
     */
    private function getPreviousPersonas(int $userId, int $limit = 10): array {
        try {
            // augmented_teacher_personas 테이블이 존재하는지 확인
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_personas');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT
                        persona_id,
                        situation,
                        confidence,
                        matched_rule,
                        created_at
                    FROM {augmented_teacher_personas}
                    WHERE user_id = ? AND agent_id = 'agent01'
                    ORDER BY created_at DESC
                    LIMIT ?";

            $personas = $this->db->get_records_sql($sql, [$userId, $limit]);
            return $personas ? array_values((array) $personas) : [];

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 페르소나 기록 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 단어 수 계산 (한국어 지원)
     *
     * @param string $text 텍스트
     * @return int 단어 수
     */
    private function countWords(string $text): int {
        // 공백으로 분리 후 빈 문자열 제거
        $words = preg_split('/\s+/', trim($text));
        return count(array_filter($words));
    }

    /**
     * 컨텍스트 저장 (세션 업데이트)
     *
     * @param int $userId 사용자 ID
     * @param array $context 저장할 컨텍스트
     * @return bool 저장 성공 여부
     */
    public function saveContext(int $userId, array $context): bool {
        try {
            // 테이블 존재 확인
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_sessions');
            if (!$tableExists) {
                error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - augmented_teacher_sessions 테이블이 존재하지 않음");
                return false;
            }

            $sessionKey = $context['session_key'] ?? md5($userId . time());

            // 기존 세션 확인
            $existing = $this->db->get_record('augmented_teacher_sessions', [
                'user_id' => $userId,
                'session_key' => $sessionKey
            ]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = 'agent01';
            $record->session_key = $sessionKey;
            $record->current_situation = $context['situation'] ?? null;
            $record->current_persona = $context['persona_id'] ?? null;
            $record->context_data = json_encode($context);
            $record->last_activity = date('Y-m-d H:i:s');

            if ($existing) {
                $record->id = $existing->id;
                $this->db->update_record('augmented_teacher_sessions', $record);
            } else {
                $record->created_at = date('Y-m-d H:i:s');
                $this->db->insert_record('augmented_teacher_sessions', $record);
            }

            // 캐시 무효화
            unset($this->userCache["user_{$userId}"]);

            return true;

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 컨텍스트 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->userCache = [];
    }

    /**
     * 감정 키워드 사전 업데이트
     *
     * @param string $category 카테고리 (negative, positive, neutral)
     * @param string $emotion 감정 유형
     * @param array $keywords 키워드 배열
     */
    public function addEmotionalKeywords(string $category, string $emotion, array $keywords): void {
        if (!isset($this->emotionalKeywords[$category])) {
            $this->emotionalKeywords[$category] = [];
        }
        if (!isset($this->emotionalKeywords[$category][$emotion])) {
            $this->emotionalKeywords[$category][$emotion] = [];
        }
        $this->emotionalKeywords[$category][$emotion] = array_merge(
            $this->emotionalKeywords[$category][$emotion],
            $keywords
        );
    }
}

/*
 * 관련 DB 테이블:
 *
 * Moodle 기본 테이블:
 * - mdl_user: id, username, firstname, lastname, email
 * - mdl_user_info_data: userid, fieldid, data (역할 정보: fieldid=22)
 * - mdl_grade_grades: userid, itemid, finalgrade
 * - mdl_grade_items: id, courseid, itemname
 * - mdl_logstore_standard_log: userid, component, action, timecreated
 *
 * 커스텀 테이블:
 * - augmented_teacher_personas: user_id(INT), agent_id(VARCHAR), persona_id(VARCHAR), situation(VARCHAR), confidence(DECIMAL), matched_rule(VARCHAR), created_at(TIMESTAMP)
 * - augmented_teacher_sessions: user_id(INT), agent_id(VARCHAR), session_key(VARCHAR), current_situation(VARCHAR), current_persona(VARCHAR), context_data(JSON), last_activity(TIMESTAMP), created_at(TIMESTAMP)
 */
