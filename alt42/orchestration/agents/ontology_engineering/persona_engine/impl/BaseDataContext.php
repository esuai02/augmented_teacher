<?php
/**
 * BaseDataContext - 데이터 컨텍스트 기본 구현체
 *
 * Moodle DB에서 학생 데이터를 가져와 컨텍스트를 구성하는 기본 구현체입니다.
 * 각 에이전트는 이를 상속하여 에이전트별 특화 데이터를 추가합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Impl;

use AugmentedTeacher\PersonaEngine\Core\IDataContext;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class BaseDataContext implements IDataContext {

    /** @var \moodle_database Moodle DB 객체 */
    protected $db;

    /** @var string 현재 파일 경로 (디버깅용) */
    protected $currentFile = __FILE__;

    /** @var array 감정 키워드 사전 */
    protected $emotionalKeywords = [
        'positive' => ['좋아요', '재미있어요', '기대돼요', '잘했어요', '감사해요', '행복해요'],
        'negative' => ['싫어요', '힘들어요', '어려워요', '못하겠어요', '지쳐요', '포기'],
        'anxiety' => ['불안해요', '걱정돼요', '두려워요', '무서워요', '긴장돼요'],
        'boredom' => ['지루해요', '재미없어요', '왜 해야 해요', '귀찮아요'],
        'confusion' => ['모르겠어요', '이해 안 돼요', '헷갈려요', '어떻게 해요']
    ];

    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByUserId(int $userId): array {
        try {
            // 사용자 기본 정보 조회
            $user = $this->db->get_record('user', ['id' => $userId], '*', MUST_EXIST);

            // 역할 정보 조회
            $roleData = $this->db->get_record_sql(
                "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
                [$userId]
            );

            return [
                'user_id' => $userId,
                'firstname' => $user->firstname ?? '',
                'lastname' => $user->lastname ?? '',
                'email' => $user->email ?? '',
                'lastaccess' => $user->lastaccess ?? 0,
                'timecreated' => $user->timecreated ?? 0,
                'role' => $roleData ? $roleData->data : 'student',
                'loaded_at' => time()
            ];

        } catch (\Exception $e) {
            $this->logError("사용자 컨텍스트 로드 실패: " . $e->getMessage(), __LINE__);
            return [
                'user_id' => $userId,
                'firstname' => '',
                'role' => 'student',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeMessage(string $message): array {
        $result = [
            'message_length' => mb_strlen($message),
            'word_count' => count(preg_split('/\s+/', trim($message))),
            'emotional_keywords' => [],
            'detected_emotion' => null,
            'question_detected' => false,
            'urgency_level' => 'normal'
        ];

        // 감정 키워드 감지
        foreach ($this->emotionalKeywords as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stripos($message, $keyword) !== false) {
                    $result['emotional_keywords'][] = $keyword;
                    if ($result['detected_emotion'] === null) {
                        $result['detected_emotion'] = $emotion;
                    }
                }
            }
        }

        // 질문 감지
        if (preg_match('/[?？]|어떻게|뭐|왜|언제|어디/', $message)) {
            $result['question_detected'] = true;
        }

        // 긴급도 판단
        $urgentKeywords = ['급해요', '빨리', '지금', '당장', '포기'];
        foreach ($urgentKeywords as $keyword) {
            if (mb_stripos($message, $keyword) !== false) {
                $result['urgency_level'] = 'high';
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * 기본 구현: 빈 배열 반환 (하위 클래스에서 오버라이드)
     */
    public function getAgentSpecificData(int $userId): array {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFullContext(int $userId, array $sessionData = []): array {
        // 1. 기본 컨텍스트 로드
        $baseContext = $this->loadByUserId($userId);

        // 2. 에이전트 특화 데이터 로드
        $agentData = $this->getAgentSpecificData($userId);

        // 3. 세션 데이터 병합
        return array_merge($baseContext, $agentData, $sessionData);
    }

    /**
     * 최근 활동 시간 계산 (분 단위)
     *
     * @param int $userId 사용자 ID
     * @return int 마지막 활동으로부터 경과 시간 (분)
     */
    protected function getMinutesSinceLastActivity(int $userId): int {
        try {
            $user = $this->db->get_record('user', ['id' => $userId], 'lastaccess');
            if ($user && $user->lastaccess > 0) {
                return (int)((time() - $user->lastaccess) / 60);
            }
        } catch (\Exception $e) {
            $this->logError("마지막 활동 시간 조회 실패: " . $e->getMessage(), __LINE__);
        }
        return 0;
    }

    /**
     * 24시간 내 특정 이벤트 횟수 조회
     *
     * @param int $userId 사용자 ID
     * @param string $tableName 테이블 이름
     * @param string $userField 사용자 ID 필드명
     * @param string $timeField 시간 필드명
     * @param array $conditions 추가 조건
     * @return int 이벤트 횟수
     */
    protected function getEventCount24h(
        int $userId,
        string $tableName,
        string $userField = 'userid',
        string $timeField = 'timecreated',
        array $conditions = []
    ): int {
        try {
            $since = time() - (24 * 60 * 60); // 24시간 전

            $sql = "SELECT COUNT(*) FROM {{$tableName}}
                    WHERE {$userField} = ? AND {$timeField} >= ?";
            $params = [$userId, $since];

            foreach ($conditions as $field => $value) {
                $sql .= " AND {$field} = ?";
                $params[] = $value;
            }

            return (int)$this->db->count_records_sql($sql, $params);

        } catch (\Exception $e) {
            $this->logError("이벤트 횟수 조회 실패: " . $e->getMessage(), __LINE__);
            return 0;
        }
    }

    /**
     * 에러 로깅
     */
    protected function logError(string $message, int $line): void {
        error_log("[DataContext ERROR] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 경고 로깅
     */
    protected function logWarning(string $message, int $line): void {
        error_log("[DataContext WARN] {$this->currentFile}:{$line} - {$message}");
    }

    /**
     * 감정 키워드 사전 설정
     *
     * @param array $keywords 감정별 키워드 배열
     */
    public function setEmotionalKeywords(array $keywords): void {
        $this->emotionalKeywords = array_merge($this->emotionalKeywords, $keywords);
    }
}

/*
 * 사용 예시:
 *
 * $dataContext = new BaseDataContext();
 *
 * // 사용자 컨텍스트 로드
 * $context = $dataContext->loadByUserId(123);
 *
 * // 메시지 분석
 * $analysis = $dataContext->analyzeMessage("공부하기 싫어요");
 * // 결과: ['emotional_keywords' => ['싫어요'], 'detected_emotion' => 'negative', ...]
 *
 * // 전체 컨텍스트 구성
 * $fullContext = $dataContext->buildFullContext(123, ['session_id' => 'abc']);
 *
 *
 * 하위 클래스에서 getAgentSpecificData() 오버라이드:
 *
 * class Agent13DataContext extends BaseDataContext {
 *     public function getAgentSpecificData(int $userId): array {
 *         return [
 *             'ninactive' => $this->getInactiveDays($userId),
 *             'npomodoro' => $this->getPomodoroCount($userId),
 *             'tlaststroke_min' => $this->getMinutesSinceLastStroke($userId),
 *             'nlazy_blocks' => $this->getLazyBlockCount($userId)
 *         ];
 *     }
 * }
 *
 * 파일 위치: ontology_engineering/persona_engine/impl/BaseDataContext.php
 *
 * 관련 DB 테이블:
 * - mdl_user: id, firstname, lastname, email, lastaccess, timecreated
 * - mdl_user_info_data: userid, fieldid, data
 */
