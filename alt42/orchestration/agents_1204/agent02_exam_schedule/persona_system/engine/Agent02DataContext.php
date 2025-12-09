<?php
/**
 * Agent02DataContext - 시험일정 기반 데이터 컨텍스트
 *
 * D-Day 계산 및 학생 유형 판단을 통해 33개 페르소나 식별을 위한
 * 데이터 컨텍스트를 구성합니다.
 *
 * @package AugmentedTeacher\Agent02\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class Agent02DataContext {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var object Moodle DB 객체 */
    private $db;

    /** @var array 캐시된 사용자 데이터 */
    private $userCache = [];

    /** @var string Agent ID */
    private $agentId = 'agent02';

    /**
     * D-Day 상황 코드 정의
     * - D_URGENT: D-Day ≤ 3 (긴급)
     * - D_BALANCED: D-Day 4~10 (균형)
     * - D_CONCEPT: D-Day 11~30 (개념중심)
     * - D_FOUNDATION: D-Day 31+ (기초)
     */
    const SITUATION_URGENT = 'D_URGENT';      // D ≤ 3
    const SITUATION_BALANCED = 'D_BALANCED';  // D 4~10
    const SITUATION_CONCEPT = 'D_CONCEPT';    // D 11~30
    const SITUATION_FOUNDATION = 'D_FOUNDATION'; // D 31+
    const SITUATION_NO_EXAM = 'NO_EXAM';      // 시험 없음

    /**
     * 학생 유형 정의
     */
    const STUDENT_TYPES = [
        'P1' => '계획형',        // 자기주도 계획적 학습자
        'P2' => '불안형',        // 시험 불안, 완벽주의
        'P3' => '회피형',        // 학습 회피, 미루기
        'P4' => '자신감 과잉형', // 과대평가, 준비부족
        'P5' => '혼란형',        // 학습방법 모름
        'P6' => '외부 의존형'    // 타인 의존, 수동적
    ];

    /**
     * 감정 키워드 사전 (시험 관련)
     */
    private $emotionalKeywords = [
        'anxiety' => ['불안', '걱정', '긴장', '무서워', '두려워', '떨려', '조급', '시간 없어'],
        'avoidance' => ['싫어', '하기 싫어', '안 할래', '내일', '나중에', '귀찮'],
        'overconfidence' => ['괜찮아', '다 알아', '쉬워', '걱정 마', '당연히'],
        'confusion' => ['모르겠어', '어떻게', '뭐부터', '뭘 해야', '막막'],
        'dependency' => ['알려줘', '해줘', '도와줘', '어떻게 해', '시켜줘'],
        'planning' => ['계획', '순서', '일정', '먼저', '그다음', '정리']
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
            'agent_id' => $this->agentId,
            'situation' => self::SITUATION_NO_EXAM,
            'user_message' => $sessionData['user_message'] ?? '',
            'student_type' => null,
            'd_day' => null,
            'exam_info' => null,
            'emotional_keywords' => [],
            'session_history' => [],
            'moodle_data' => []
        ];

        try {
            // 1. Moodle 사용자 기본 정보
            $user = $this->db->get_record('user', ['id' => $userId], 'id, username, firstname, lastname, email');
            if ($user) {
                $context['moodle_data']['user'] = (array) $user;
                $context['firstname'] = $user->firstname;
                $context['student_name'] = $user->firstname;
            }

            // 2. 역할 정보
            $roleData = $this->db->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            if ($roleData) {
                $context['moodle_data']['role'] = $roleData->data;
            }

            // 3. 시험 일정 및 D-Day 계산 (핵심!)
            $examInfo = $this->getUpcomingExam($userId);
            if ($examInfo) {
                $context['exam_info'] = $examInfo;
                $context['d_day'] = $examInfo['d_day'];
                $context['situation'] = $this->determineSituationByDDay($examInfo['d_day']);
            }

            // 4. 이전 페르소나 기록에서 학생 유형 추론
            $previousPersonas = $this->getPreviousPersonas($userId);
            $context['previous_personas'] = $previousPersonas;
            $context['inferred_student_type'] = $this->inferStudentType($previousPersonas);

            // 5. 세션 히스토리
            $sessionHistory = $this->getSessionHistory($userId);
            $context['session_history'] = $sessionHistory;

            // 캐시 저장
            $this->userCache[$cacheKey] = $context;

        } catch (Exception $e) {
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 데이터 로드 실패: " . $e->getMessage());
        }

        // 세션 데이터 병합
        return array_merge($context, $sessionData);
    }

    /**
     * 가장 가까운 시험 일정 조회
     *
     * @param int $userId 사용자 ID
     * @return array|null 시험 정보 또는 null
     */
    public function getUpcomingExam(int $userId): ?array {
        try {
            // at_exam_schedules 테이블 존재 확인
            $tableExists = $this->db->get_manager()->table_exists('at_exam_schedules');
            if (!$tableExists) {
                // 테이블이 없으면 null 반환
                error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - at_exam_schedules 테이블 없음");
                return null;
            }

            // 오늘 이후의 가장 가까운 시험 조회
            $today = date('Y-m-d');
            $sql = "SELECT id, user_id, exam_name, exam_date, subject, exam_type,
                           notes, created_at, updated_at
                    FROM {at_exam_schedules}
                    WHERE user_id = ? AND exam_date >= ?
                    ORDER BY exam_date ASC
                    LIMIT 1";

            $exam = $this->db->get_record_sql($sql, [$userId, $today]);

            if (!$exam) {
                return null;
            }

            // D-Day 계산
            $examDate = new DateTime($exam->exam_date);
            $todayDate = new DateTime($today);
            $interval = $todayDate->diff($examDate);
            $dDay = (int) $interval->days;

            return [
                'id' => $exam->id,
                'exam_name' => $exam->exam_name,
                'exam_date' => $exam->exam_date,
                'subject' => $exam->subject ?? '전체',
                'exam_type' => $exam->exam_type ?? 'regular',
                'd_day' => $dDay,
                'is_today' => ($dDay === 0),
                'is_tomorrow' => ($dDay === 1),
                'formatted_date' => date('Y년 m월 d일', strtotime($exam->exam_date))
            ];

        } catch (Exception $e) {
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 시험 조회 실패: " . $e->getMessage());
            return null;
        }
    }

    /**
     * D-Day 기반 상황 코드 결정
     *
     * @param int $dDay D-Day 값
     * @return string 상황 코드
     */
    public function determineSituationByDDay(int $dDay): string {
        if ($dDay <= 3) {
            return self::SITUATION_URGENT;      // D-3 이하: 긴급
        } elseif ($dDay <= 10) {
            return self::SITUATION_BALANCED;    // D-4 ~ D-10: 균형
        } elseif ($dDay <= 30) {
            return self::SITUATION_CONCEPT;     // D-11 ~ D-30: 개념중심
        } else {
            return self::SITUATION_FOUNDATION;  // D-31 이상: 기초
        }
    }

    /**
     * 메시지 분석 (학생 유형 판단 포함)
     *
     * @param string $message 사용자 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        $analysis = [
            'response_length' => mb_strlen($message),
            'word_count' => $this->countWords($message),
            'detected_keywords' => [],
            'detected_student_type' => null,
            'type_confidence' => 0.0,
            'has_question' => strpos($message, '?') !== false || preg_match('/[?？]/', $message),
            'is_short_response' => mb_strlen($message) < 10,
            'emotional_indicators' => []
        ];

        // 학생 유형별 키워드 매칭
        $typeScores = [
            'P1' => 0, // 계획형
            'P2' => 0, // 불안형
            'P3' => 0, // 회피형
            'P4' => 0, // 자신감 과잉형
            'P5' => 0, // 혼란형
            'P6' => 0  // 외부 의존형
        ];

        foreach ($this->emotionalKeywords as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $analysis['detected_keywords'][] = [
                        'keyword' => $keyword,
                        'emotion' => $emotion
                    ];

                    // 감정-학생유형 매핑
                    switch ($emotion) {
                        case 'anxiety':
                            $typeScores['P2'] += 1.5; // 불안형
                            break;
                        case 'avoidance':
                            $typeScores['P3'] += 1.5; // 회피형
                            break;
                        case 'overconfidence':
                            $typeScores['P4'] += 1.5; // 자신감 과잉형
                            break;
                        case 'confusion':
                            $typeScores['P5'] += 1.5; // 혼란형
                            break;
                        case 'dependency':
                            $typeScores['P6'] += 1.5; // 외부 의존형
                            break;
                        case 'planning':
                            $typeScores['P1'] += 1.5; // 계획형
                            break;
                    }
                }
            }
        }

        // 최고 점수 유형 결정
        $maxScore = max($typeScores);
        if ($maxScore > 0) {
            $maxType = array_search($maxScore, $typeScores);
            $analysis['detected_student_type'] = $maxType;
            $analysis['type_confidence'] = min($maxScore / 3.0, 1.0); // 정규화
            $analysis['type_scores'] = $typeScores;
        }

        // 감정 지표 설정
        $analysis['emotional_indicators'] = [
            'anxiety_level' => $typeScores['P2'] / 3.0,
            'avoidance_level' => $typeScores['P3'] / 3.0,
            'confidence_level' => $typeScores['P4'] / 3.0,
            'confusion_level' => $typeScores['P5'] / 3.0
        ];

        return $analysis;
    }

    /**
     * 이전 페르소나 기록에서 학생 유형 추론
     *
     * @param array $previousPersonas 이전 페르소나 배열
     * @return string|null 추론된 학생 유형
     */
    private function inferStudentType(array $previousPersonas): ?string {
        if (empty($previousPersonas)) {
            return null;
        }

        // 최근 10개 기록에서 학생 유형 빈도 계산
        $typeCounts = [];
        foreach ($previousPersonas as $record) {
            $personaId = $record->persona_id ?? $record['persona_id'] ?? '';
            // 페르소나 ID에서 학생 유형 추출 (예: D_URGENT_P2 → P2)
            if (preg_match('/_(P[1-6])$/', $personaId, $matches)) {
                $type = $matches[1];
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            }
        }

        if (empty($typeCounts)) {
            return null;
        }

        // 가장 빈번한 유형 반환
        arsort($typeCounts);
        return key($typeCounts);
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
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_personas');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT persona_id, situation, confidence, matched_rule, created_at
                    FROM {augmented_teacher_personas}
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?";

            $personas = $this->db->get_records_sql($sql, [$userId, $this->agentId, $limit]);
            return $personas ? array_values((array) $personas) : [];

        } catch (Exception $e) {
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 페르소나 기록 조회 실패: " . $e->getMessage());
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
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_sessions');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT session_key, current_situation, current_persona,
                           context_data, message_count, last_activity
                    FROM {augmented_teacher_sessions}
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY last_activity DESC
                    LIMIT ?";

            $sessions = $this->db->get_records_sql($sql, [$userId, $this->agentId, $limit]);

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
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 세션 조회 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 컨텍스트 저장
     *
     * @param int $userId 사용자 ID
     * @param array $context 저장할 컨텍스트
     * @return bool 저장 성공 여부
     */
    public function saveContext(int $userId, array $context): bool {
        try {
            $tableExists = $this->db->get_manager()->table_exists('augmented_teacher_sessions');
            if (!$tableExists) {
                error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - augmented_teacher_sessions 테이블 없음");
                return false;
            }

            $sessionKey = $context['session_key'] ?? md5($userId . '_' . $this->agentId . '_' . time());

            // 기존 세션 확인
            $existing = $this->db->get_record('augmented_teacher_sessions', [
                'user_id' => $userId,
                'agent_id' => $this->agentId,
                'session_key' => $sessionKey
            ]);

            $record = new stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->session_key = $sessionKey;
            $record->current_situation = $context['situation'] ?? null;
            $record->current_persona = $context['persona_id'] ?? null;
            $record->context_data = json_encode($context);
            $record->message_count = ($context['message_count'] ?? 0) + 1;
            $record->last_message = $context['user_message'] ?? null;
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
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 컨텍스트 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 시험 일정 등록
     *
     * @param int $userId 사용자 ID
     * @param array $examData 시험 데이터
     * @return int|bool 생성된 ID 또는 실패 시 false
     */
    public function registerExam(int $userId, array $examData) {
        try {
            $tableExists = $this->db->get_manager()->table_exists('at_exam_schedules');
            if (!$tableExists) {
                error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - at_exam_schedules 테이블 없음");
                return false;
            }

            $record = new stdClass();
            $record->user_id = $userId;
            $record->exam_name = $examData['exam_name'] ?? '시험';
            $record->exam_date = $examData['exam_date'];
            $record->subject = $examData['subject'] ?? null;
            $record->exam_type = $examData['exam_type'] ?? 'regular';
            $record->notes = $examData['notes'] ?? null;
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');

            $id = $this->db->insert_record('at_exam_schedules', $record);

            // 캐시 무효화
            unset($this->userCache["user_{$userId}"]);

            return $id;

        } catch (Exception $e) {
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 시험 등록 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 모든 시험 일정 조회
     *
     * @param int $userId 사용자 ID
     * @param bool $futureOnly 미래 일정만 조회
     * @return array 시험 일정 목록
     */
    public function getAllExams(int $userId, bool $futureOnly = true): array {
        try {
            $tableExists = $this->db->get_manager()->table_exists('at_exam_schedules');
            if (!$tableExists) {
                return [];
            }

            $sql = "SELECT id, exam_name, exam_date, subject, exam_type, notes, created_at
                    FROM {at_exam_schedules}
                    WHERE user_id = ?";
            $params = [$userId];

            if ($futureOnly) {
                $sql .= " AND exam_date >= ?";
                $params[] = date('Y-m-d');
            }

            $sql .= " ORDER BY exam_date ASC";

            $exams = $this->db->get_records_sql($sql, $params);

            $result = [];
            $today = new DateTime(date('Y-m-d'));

            foreach ($exams as $exam) {
                $examDate = new DateTime($exam->exam_date);
                $interval = $today->diff($examDate);
                $dDay = (int) $interval->days;
                if ($examDate < $today) {
                    $dDay = -$dDay;
                }

                $result[] = [
                    'id' => $exam->id,
                    'exam_name' => $exam->exam_name,
                    'exam_date' => $exam->exam_date,
                    'subject' => $exam->subject,
                    'exam_type' => $exam->exam_type,
                    'd_day' => $dDay,
                    'formatted_date' => date('Y년 m월 d일', strtotime($exam->exam_date)),
                    'situation' => $dDay >= 0 ? $this->determineSituationByDDay($dDay) : 'PAST'
                ];
            }

            return $result;

        } catch (Exception $e) {
            error_log("[Agent02DataContext] {$this->currentFile}:" . __LINE__ . " - 시험 목록 조회 실패: " . $e->getMessage());
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
        $words = preg_split('/\s+/', trim($text));
        return count(array_filter($words));
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->userCache = [];
    }

    /**
     * 상황 코드 → 한글 이름 변환
     *
     * @param string $situation 상황 코드
     * @return string 한글 이름
     */
    public function getSituationName(string $situation): string {
        $names = [
            self::SITUATION_URGENT => 'D-3 이하 (긴급)',
            self::SITUATION_BALANCED => 'D-4~10 (균형)',
            self::SITUATION_CONCEPT => 'D-11~30 (개념중심)',
            self::SITUATION_FOUNDATION => 'D-31+ (기초)',
            self::SITUATION_NO_EXAM => '시험 없음'
        ];
        return $names[$situation] ?? $situation;
    }

    /**
     * 학생 유형 → 한글 이름 변환
     *
     * @param string $type 학생 유형 코드
     * @return string 한글 이름
     */
    public function getStudentTypeName(string $type): string {
        return self::STUDENT_TYPES[$type] ?? $type;
    }
}

/*
 * Agent02DataContext v1.0 - 시험일정 기반 데이터 컨텍스트
 *
 * 핵심 기능:
 * - D-Day 계산 및 상황 결정 (D_URGENT, D_BALANCED, D_CONCEPT, D_FOUNDATION)
 * - 학생 유형 판단 (P1~P6)
 * - 시험 일정 CRUD
 * - 세션 컨텍스트 관리
 *
 * 관련 DB 테이블:
 * - at_exam_schedules: id(BIGINT), user_id(BIGINT), exam_name(VARCHAR), exam_date(DATE),
 *                      subject(VARCHAR), exam_type(VARCHAR), notes(TEXT),
 *                      created_at(TIMESTAMP), updated_at(TIMESTAMP)
 * - augmented_teacher_personas: user_id, agent_id='agent02', persona_id, situation, confidence
 * - augmented_teacher_sessions: user_id, agent_id='agent02', session_key, context_data
 *
 * Moodle 테이블:
 * - mdl_user: id, username, firstname, lastname, email
 * - mdl_user_info_data: userid, fieldid=22, data (역할)
 */
