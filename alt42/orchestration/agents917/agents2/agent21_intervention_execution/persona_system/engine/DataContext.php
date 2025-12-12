<?php
/**
 * DataContext - Moodle DB 연동 데이터 컨텍스트
 *
 * Agent21 개입 실행에 특화된 데이터 컨텍스트입니다.
 * Moodle DB에서 학생 데이터를 로드하고 개입 반응을 분석합니다.
 *
 * @package AugmentedTeacher\Agent21\PersonaSystem
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

    /** @var array 개입 반응 키워드 사전 */
    private $interventionResponseKeywords = [
        // 수용형 (Acceptance) 키워드
        'acceptance' => [
            'active' => ['알겠습니다', '해볼게요', '좋아요', '네', '그렇게 할게요', '시작해볼까요'],
            'understanding' => ['이해했어요', '그렇군요', '알겠어요', '그래요'],
            'cooperative' => ['함께', '같이', '도와주세요', '가르쳐주세요']
        ],
        // 저항형 (Resistance) 키워드
        'resistance' => [
            'explicit' => ['싫어요', '안 할래요', '안 해요', '하기 싫어', '왜요', '왜 해야해요'],
            'passive' => ['나중에', '다음에', '지금은', '바빠서', '시간 없어'],
            'defensive' => ['이미 알아요', '필요 없어요', '그건 아닌데', '그게 아니라']
        ],
        // 무응답형 (No Response) - 주로 행동 패턴으로 감지
        'no_response' => [
            'minimal' => ['네', '음', '...', '그래요'],
            'deflection' => ['잘 모르겠어요', '생각해볼게요', '그냥요']
        ],
        // 지연 반응형 (Delayed) 키워드
        'delayed' => [
            'postpone' => ['나중에', '다음에', '시간 없어', '바빠서', '이따가'],
            'conditional' => ['하면', '되면', '있으면', '끝나면']
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
            'response_type' => $sessionData['response_type'] ?? 'A', // A=수용, R=저항, N=무응답, D=지연
            'user_message' => $sessionData['user_message'] ?? '',
            'response_length' => 0,
            'emotional_keywords' => [],
            'negative_keywords' => [],
            'intervention_history' => [],
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

            // 개입 실행 히스토리 (커스텀 테이블)
            $interventionHistory = $this->getInterventionHistory($userId);
            $context['intervention_history'] = $interventionHistory;

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
     * 개입 반응 유형 결정
     *
     * @param array $sessionData 세션 데이터
     * @return string 반응 유형 코드 (A=수용, R=저항, N=무응답, D=지연)
     */
    public function determineResponseType(array $sessionData): string {
        // 명시적으로 반응 유형이 지정된 경우
        if (!empty($sessionData['response_type'])) {
            return $sessionData['response_type'];
        }

        // 메시지 분석
        $message = $sessionData['user_message'] ?? '';
        $responseTime = $sessionData['response_time_seconds'] ?? 0;
        $consecutiveNoResponse = $sessionData['consecutive_no_response'] ?? 0;

        // 무응답 감지 (응답 시간 또는 연속 무응답)
        if (empty($message) || $consecutiveNoResponse >= 2) {
            return 'N'; // No Response
        }

        // 지연 반응 감지 (응답 시간 기반)
        if ($responseTime > 300) { // 5분 이상
            // 지연 키워드 확인
            foreach ($this->interventionResponseKeywords['delayed'] as $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($message, $keyword) !== false) {
                        return 'D'; // Delayed
                    }
                }
            }
        }

        // 저항 감지
        $resistanceScore = 0;
        foreach ($this->interventionResponseKeywords['resistance'] as $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $resistanceScore++;
                }
            }
        }

        // 수용 감지
        $acceptanceScore = 0;
        foreach ($this->interventionResponseKeywords['acceptance'] as $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $acceptanceScore++;
                }
            }
        }

        // 점수 비교
        if ($resistanceScore > $acceptanceScore && $resistanceScore > 0) {
            return 'R'; // Resistance
        }

        if ($acceptanceScore > 0) {
            return 'A'; // Acceptance
        }

        // 짧은 응답 + 저항/수용 키워드 없음 → 무응답 경향
        if (mb_strlen($message) < 5) {
            return 'N';
        }

        // 기본값: 수용
        return 'A';
    }

    /**
     * 메시지 분석 (개입 반응 특화)
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
            'shows_acceptance' => false,
            'shows_resistance' => false,
            'resistance_level' => 'none',
            'response_type_indicators' => []
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

        // 개입 반응 키워드 분석
        foreach ($this->interventionResponseKeywords as $type => $subcategories) {
            foreach ($subcategories as $emotion => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($message, $keyword) !== false) {
                        $analysis['response_type_indicators'][] = [
                            'type' => $type,
                            'category' => $emotion,
                            'keyword' => $keyword
                        ];
                    }
                }
            }
        }

        // 수용/저항 판단
        $acceptanceCount = 0;
        $resistanceCount = 0;
        foreach ($analysis['response_type_indicators'] as $indicator) {
            if ($indicator['type'] === 'acceptance') {
                $acceptanceCount++;
            } elseif ($indicator['type'] === 'resistance') {
                $resistanceCount++;
            }
        }

        $analysis['shows_acceptance'] = $acceptanceCount > 0;
        $analysis['shows_resistance'] = $resistanceCount > 0;

        // 저항 수준 결정
        if ($resistanceCount >= 3) {
            $analysis['resistance_level'] = 'strong';
        } elseif ($resistanceCount >= 2) {
            $analysis['resistance_level'] = 'moderate';
        } elseif ($resistanceCount >= 1) {
            $analysis['resistance_level'] = 'mild';
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

        return $analysis;
    }

    /**
     * 개입 실행 히스토리 조회
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 개입 히스토리
     */
    private function getInterventionHistory(int $userId, int $limit = 10): array {
        try {
            // augmented_teacher_interventions 테이블이 존재하는지 확인
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_interventions');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT
                        intervention_id,
                        intervention_type,
                        response_type,
                        execution_status,
                        created_at
                    FROM {augmented_teacher_interventions}
                    WHERE user_id = ? AND agent_id = 'agent21'
                    ORDER BY created_at DESC
                    LIMIT ?";

            $interventions = $this->db->get_records_sql($sql, [$userId, $limit]);
            return $interventions ? array_values((array) $interventions) : [];

        } catch (Exception $e) {
            error_log("[DataContext] {$this->currentFile}:" . __LINE__ . " - 개입 히스토리 조회 실패: " . $e->getMessage());
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
                    WHERE user_id = ? AND agent_id = 'agent21'
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
                    WHERE user_id = ? AND agent_id = 'agent21'
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
            $record->agent_id = 'agent21';
            $record->session_key = $sessionKey;
            $record->current_situation = $context['response_type'] ?? null;
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

    /**
     * 개입 반응 키워드 사전 업데이트
     *
     * @param string $responseType 반응 유형 (acceptance, resistance, no_response, delayed)
     * @param string $category 카테고리
     * @param array $keywords 키워드 배열
     */
    public function addInterventionResponseKeywords(string $responseType, string $category, array $keywords): void {
        if (!isset($this->interventionResponseKeywords[$responseType])) {
            $this->interventionResponseKeywords[$responseType] = [];
        }
        if (!isset($this->interventionResponseKeywords[$responseType][$category])) {
            $this->interventionResponseKeywords[$responseType][$category] = [];
        }
        $this->interventionResponseKeywords[$responseType][$category] = array_merge(
            $this->interventionResponseKeywords[$responseType][$category],
            $keywords
        );
    }
}

/*
 * 반응 유형 코드:
 * - A (Acceptance): 수용형 - 개입을 긍정적으로 받아들임
 * - R (Resistance): 저항형 - 개입에 저항하거나 거부
 * - N (No Response): 무응답형 - 응답 없음 또는 최소 응답
 * - D (Delayed): 지연반응형 - 응답이 지연되거나 미루는 경향
 *
 * 관련 DB 테이블:
 *
 * Moodle 기본 테이블:
 * - mdl_user: id, username, firstname, lastname, email
 * - mdl_user_info_data: userid, fieldid, data (역할 정보: fieldid=22)
 *
 * 커스텀 테이블:
 * - augmented_teacher_personas: user_id(INT), agent_id(VARCHAR), persona_id(VARCHAR), situation(VARCHAR), confidence(DECIMAL), matched_rule(VARCHAR), created_at(TIMESTAMP)
 * - augmented_teacher_sessions: user_id(INT), agent_id(VARCHAR), session_key(VARCHAR), current_situation(VARCHAR), current_persona(VARCHAR), context_data(JSON), last_activity(TIMESTAMP), created_at(TIMESTAMP)
 * - augmented_teacher_interventions: user_id(INT), agent_id(VARCHAR), intervention_id(VARCHAR), intervention_type(VARCHAR), response_type(VARCHAR), execution_status(VARCHAR), created_at(TIMESTAMP)
 */
