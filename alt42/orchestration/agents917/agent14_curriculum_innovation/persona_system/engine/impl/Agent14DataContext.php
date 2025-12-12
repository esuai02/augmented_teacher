<?php
/**
 * Agent14DataContext - Agent14 데이터 컨텍스트
 *
 * Agent14 전용 사용자 데이터 및 컨텍스트 관리
 *
 * @package AugmentedTeacher\Agent14\PersonaEngine\Impl
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/../../../../../ontology_engineering/persona_engine/core/IDataContext.php');

class Agent14DataContext implements IDataContext {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var array 캐시 */
    protected $cache = [];

    /** @var array Agent14 상황 코드 */
    const SITUATION_CODES = ['C1', 'C2', 'C3', 'C4', 'C5'];

    /** @var array 의도 키워드 매핑 */
    protected $intentKeywords = [
        'analyze' => ['분석', '분석해', 'analyze', 'analysis', '살펴', '검토', '파악'],
        'design' => ['설계', '디자인', 'design', '구성', '계획', '만들어'],
        'create' => ['생성', '만들', 'create', '개발', '제작', '작성'],
        'evaluate' => ['평가', '시험', 'evaluate', 'assess', '측정', '검증'],
        'improve' => ['개선', '향상', 'improve', '발전', '업그레이드', '최적화'],
        'help' => ['도움', '도와', 'help', '어떻게', '방법', '알려'],
        'question' => ['질문', '궁금', 'what', 'why', 'how', '뭐', '왜', '어떤']
    ];

    /** @var array 감정 키워드 매핑 */
    protected $emotionKeywords = [
        'positive' => ['좋아', '훌륭', '감사', '기대', '흥미', '좋네', '잘됐', '만족'],
        'negative' => ['어렵', '힘들', '모르겠', '못하', '걱정', '불안', '답답', '안돼'],
        'neutral' => [],
        'confused' => ['헷갈', '혼란', '복잡', '뭔지', '이해가', '모르', '뭐지'],
        'motivated' => ['해보고', '시작', '배우고', '도전', '열심히', '노력']
    ];

    /**
     * 사용자 ID로 컨텍스트 로드
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 현재 세션 데이터
     * @return array 사용자 컨텍스트
     */
    public function loadByUserId(int $userId, array $sessionData = []): array {
        global $DB;

        $cacheKey = "user_{$userId}";
        if (isset($this->cache[$cacheKey])) {
            return array_merge($this->cache[$cacheKey], $sessionData);
        }

        try {
            $context = [];

            // 기본 사용자 정보
            $user = $DB->get_record('user', ['id' => $userId], 'id, username, firstname, lastname, email');
            if ($user) {
                $context['user'] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'fullname' => trim($user->firstname . ' ' . $user->lastname)
                ];
            }

            // 사용자 역할 (Moodle 커스텀 필드)
            $roleData = $DB->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );
            $context['user_role'] = $roleData ? $roleData->data : 'student';

            // 최근 활동 (curriculum 관련)
            $context['recent_activity'] = $this->getRecentActivity($userId);

            // 학습 진행 상황
            $context['learning_progress'] = $this->getLearningProgress($userId);

            // 이전 페르소나 상태 (있는 경우)
            $previousState = $DB->get_record('at_agent_persona_state', [
                'user_id' => $userId,
                'agent_id' => 'agent14'
            ]);
            if ($previousState) {
                $context['previous_persona'] = $previousState->persona_id;
                $context['previous_situation'] = $previousState->situation;
            }

            // 세션 데이터 병합
            $context = array_merge($context, $sessionData);

            // 캐시 저장
            $this->cache[$cacheKey] = $context;

            return $context;

        } catch (Exception $e) {
            error_log("[Agent14DataContext] {$this->currentFile}:" . __LINE__ .
                " - 컨텍스트 로드 실패: " . $e->getMessage());
            return $sessionData;
        }
    }

    /**
     * 최근 활동 조회
     *
     * @param int $userId 사용자 ID
     * @return array 최근 활동
     */
    protected function getRecentActivity(int $userId): array {
        global $DB;

        try {
            // 최근 30일간 활동
            $since = time() - (30 * 24 * 60 * 60);

            $logs = $DB->get_records_sql(
                "SELECT component, action, COUNT(*) as count
                 FROM {logstore_standard_log}
                 WHERE userid = ? AND timecreated > ?
                 GROUP BY component, action
                 ORDER BY count DESC
                 LIMIT 10",
                [$userId, $since]
            );

            $activities = [];
            foreach ($logs as $log) {
                $activities[] = [
                    'component' => $log->component,
                    'action' => $log->action,
                    'count' => (int)$log->count
                ];
            }

            return $activities;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 학습 진행 상황 조회
     *
     * @param int $userId 사용자 ID
     * @return array 학습 진행 상황
     */
    protected function getLearningProgress(int $userId): array {
        global $DB;

        try {
            // 평균 성적 조회
            $grades = $DB->get_records_sql(
                "SELECT AVG(finalgrade) as avg_grade, COUNT(*) as grade_count
                 FROM {grade_grades}
                 WHERE userid = ? AND finalgrade IS NOT NULL",
                [$userId]
            );

            $progress = [
                'avg_grade' => 0,
                'grade_count' => 0,
                'level' => 'beginner'
            ];

            if (!empty($grades)) {
                $gradeData = reset($grades);
                $progress['avg_grade'] = round((float)$gradeData->avg_grade, 2);
                $progress['grade_count'] = (int)$gradeData->grade_count;

                // 레벨 결정
                if ($progress['avg_grade'] >= 90) {
                    $progress['level'] = 'advanced';
                } elseif ($progress['avg_grade'] >= 70) {
                    $progress['level'] = 'intermediate';
                } else {
                    $progress['level'] = 'beginner';
                }
            }

            return $progress;

        } catch (Exception $e) {
            return ['avg_grade' => 0, 'grade_count' => 0, 'level' => 'beginner'];
        }
    }

    /**
     * 현재 상황 코드 결정
     *
     * @param array $sessionData 세션 데이터
     * @return string 상황 코드
     */
    public function determineSituation(array $sessionData): string {
        // 명시적 상황 지정
        if (isset($sessionData['situation']) && in_array($sessionData['situation'], self::SITUATION_CODES)) {
            return $sessionData['situation'];
        }

        // 의도 기반 상황 결정
        $intent = $sessionData['intent'] ?? 'general';
        $message = $sessionData['user_message'] ?? '';

        // C1: 교육과정 분석 - 분석, 파악, 검토 관련
        if ($intent === 'analyze' || $this->containsKeywords($message, ['분석', '파악', '검토', '현황', '상태'])) {
            return 'C1';
        }

        // C2: 콘텐츠 설계 - 설계, 디자인, 구성 관련
        if ($intent === 'design' || $intent === 'create' || $this->containsKeywords($message, ['설계', '디자인', '콘텐츠', '자료', '교재'])) {
            return 'C2';
        }

        // C3: 교수법 혁신 - 교수법, 수업, 방법 관련
        if ($this->containsKeywords($message, ['교수법', '수업', '지도', '방법', '혁신', '새로운'])) {
            return 'C3';
        }

        // C4: 평가 설계 - 평가, 시험, 측정 관련
        if ($intent === 'evaluate' || $this->containsKeywords($message, ['평가', '시험', '측정', '성적', '테스트'])) {
            return 'C4';
        }

        // C5: 적용 및 피드백 - 적용, 실행, 피드백 관련
        if ($this->containsKeywords($message, ['적용', '실행', '피드백', '결과', '개선'])) {
            return 'C5';
        }

        // 이전 상황 유지 또는 기본값
        return $sessionData['previous_situation'] ?? 'C1';
    }

    /**
     * 메시지 분석
     *
     * @param string $message 사용자 메시지
     * @return array 분석 결과
     */
    public function analyzeMessage(string $message): array {
        $analysis = [
            'intent' => $this->detectIntent($message),
            'emotional_state' => $this->detectEmotion($message),
            'keywords' => $this->extractKeywords($message),
            'message_length' => mb_strlen($message),
            'is_question' => $this->isQuestion($message)
        ];

        return $analysis;
    }

    /**
     * 의도 감지
     *
     * @param string $message 메시지
     * @return string 감지된 의도
     */
    protected function detectIntent(string $message): string {
        $message = mb_strtolower($message);

        foreach ($this->intentKeywords as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * 감정 감지
     *
     * @param string $message 메시지
     * @return string 감지된 감정
     */
    protected function detectEmotion(string $message): string {
        $message = mb_strtolower($message);
        $scores = [];

        foreach ($this->emotionKeywords as $emotion => $keywords) {
            $scores[$emotion] = 0;
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $scores[$emotion]++;
                }
            }
        }

        arsort($scores);
        $topEmotion = key($scores);

        return ($scores[$topEmotion] > 0) ? $topEmotion : 'neutral';
    }

    /**
     * 키워드 추출
     *
     * @param string $message 메시지
     * @return array 추출된 키워드
     */
    protected function extractKeywords(string $message): array {
        $keywords = [];

        // 교육과정 관련 키워드
        $curriculumKeywords = ['교육과정', '커리큘럼', '학습', '수업', '콘텐츠', '평가', '목표', '역량'];
        foreach ($curriculumKeywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $keywords[] = $keyword;
            }
        }

        return array_unique($keywords);
    }

    /**
     * 질문 여부 확인
     *
     * @param string $message 메시지
     * @return bool 질문 여부
     */
    protected function isQuestion(string $message): bool {
        // 물음표 확인
        if (mb_strpos($message, '?') !== false) {
            return true;
        }

        // 의문형 패턴
        $questionPatterns = ['어떻게', '무엇', '왜', '어디', '언제', '누가', '뭐', '할까', '인가요', '할까요', '인지'];
        foreach ($questionPatterns as $pattern) {
            if (mb_strpos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 키워드 포함 여부 확인
     *
     * @param string $text 텍스트
     * @param array $keywords 키워드 배열
     * @return bool 포함 여부
     */
    protected function containsKeywords(string $text, array $keywords): bool {
        $lowerText = mb_strtolower($text);
        foreach ($keywords as $keyword) {
            if (mb_strpos($lowerText, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 컨텍스트 저장
     *
     * @param int $userId 사용자 ID
     * @param array $context 저장할 컨텍스트
     * @return bool 저장 성공 여부
     */
    public function saveContext(int $userId, array $context): bool {
        global $DB;

        try {
            // 세션 로그 저장
            $logData = new stdClass();
            $logData->user_id = $userId;
            $logData->agent_id = 'agent14';
            $logData->request_type = 'context_save';
            $logData->input_data = json_encode($context);
            $logData->success = 1;
            $logData->created_at = time();

            $DB->insert_record('at_persona_log', $logData);

            // 캐시 업데이트
            $this->cache["user_{$userId}"] = $context;

            return true;

        } catch (Exception $e) {
            error_log("[Agent14DataContext] {$this->currentFile}:" . __LINE__ .
                " - 컨텍스트 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void {
        $this->cache = [];
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_user: 사용자 정보
 * - mdl_user_info_data: 사용자 추가 필드
 * - mdl_grade_grades: 성적 정보
 * - mdl_logstore_standard_log: 활동 로그
 * - at_agent_persona_state: 페르소나 상태
 * - at_persona_log: 처리 로그
 */
