<?php
/**
 * Agent05DataContext - 학습 감정 데이터 컨텍스트
 *
 * Agent05 학습 감정 분석에 특화된 데이터 컨텍스트
 * BaseDataContext를 확장하여 학습 활동 및 감정 데이터 처리
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\Agent05\PersonaSystem;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 기본 데이터 컨텍스트 로드
require_once(dirname(__FILE__) . '/../../../../ontology_engineering/persona_engine/impl/BaseDataContext.php');

use AugmentedTeacher\PersonaEngine\Impl\BaseDataContext;

class Agent05DataContext extends BaseDataContext {

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    protected $agentId = 'agent05';

    /** @var array 학습 감정 키워드 사전 (확장) */
    protected $learningEmotionKeywords = [
        'anxiety' => [
            'high' => ['너무 어려워', '못하겠어', '포기할래', '무서워', '두려워', '떨려요'],
            'medium' => ['걱정돼', '불안해', '자신없어', '어떡해', '망했다'],
            'low' => ['조금 걱정', '좀 불안', '괜찮을까']
        ],
        'frustration' => [
            'high' => ['짜증나', '화나', '싫어', '왜 안돼', '진짜 모르겠어'],
            'medium' => ['답답해', '힘들어', '어려워', '헷갈려'],
            'low' => ['음...', '글쎄', '잘 모르겠어']
        ],
        'confidence' => [
            'high' => ['알겠어', '할 수 있어', '쉬워', '이해했어', '자신있어'],
            'medium' => ['그런 것 같아', '알 것 같아', '해볼게'],
            'low' => ['아마도', '될까', '한번 해볼게요']
        ],
        'curiosity' => [
            'high' => ['왜 그래요?', '어떻게', '궁금해', '더 알고 싶어'],
            'medium' => ['그게 뭐예요?', '왜요?', '설명해주세요'],
            'low' => ['그렇구나', '음', '아']
        ],
        'achievement' => [
            'high' => ['다 맞았어!', '해냈다!', '완전 이해했어'],
            'medium' => ['맞았어', '풀었어', '알겠어'],
            'low' => ['된 것 같아', '이정도면']
        ],
        'boredom' => [
            'high' => ['지루해', '재미없어', '그만하고 싶어', '또요?'],
            'medium' => ['싫증나', '언제 끝나', '몰라요'],
            'low' => ['음...', '네', '그냥']
        ]
    ];

    /** @var array 수학 특화 감정 키워드 */
    protected $mathEmotionKeywords = [
        'calculation_anxiety' => ['계산 실수', '틀릴까봐', '공식 잊었어', '숫자 헷갈려'],
        'concept_confusion' => ['왜 이렇게 되는지', '이해가 안 돼', '개념이 뭐야'],
        'problem_overwhelm' => ['문제가 너무 길어', '복잡해', '어디서 시작해야'],
        'time_pressure' => ['시간 없어', '빨리 해야', '늦겠다'],
        'comparison_stress' => ['다른 애들은', '나만 못해', '왜 나만'],
        'test_fear' => ['시험', '망할 것 같아', '불안해']
    ];

    /**
     * 학습 컨텍스트 로드 (학습 감정 특화)
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터
     * @return array 학습 컨텍스트
     */
    public function loadContext(int $userId, array $sessionData = []): array {
        // 기본 컨텍스트 로드
        $context = parent::loadContext($userId, $sessionData);

        // 학습 활동 데이터 추가
        $context['learning_data'] = $this->loadLearningActivityData($userId);

        // 감정 히스토리 추가
        $context['emotion_history'] = $this->loadEmotionHistory($userId);

        // 현재 학습 세션 정보
        $context['current_session'] = $this->loadCurrentLearningSession($userId);

        // 수학 학습 특화 데이터
        $context['math_data'] = $this->loadMathLearningData($userId);

        // 에이전트 간 공유 감정 데이터
        $context['shared_emotions'] = $this->loadSharedEmotions($userId);

        return $context;
    }

    /**
     * 학습 활동 데이터 로드
     *
     * @param int $userId 사용자 ID
     * @return array 학습 활동 데이터
     */
    protected function loadLearningActivityData(int $userId): array {
        global $DB;

        try {
            $data = [
                'recent_activities' => [],
                'activity_counts' => [],
                'preferred_activities' => [],
                'completion_rate' => 0
            ];

            // at_learning_activity 테이블에서 최근 활동 로드
            $activities = $DB->get_records_sql(
                "SELECT activity_type, COUNT(*) as count,
                        AVG(completion_rate) as avg_completion,
                        MAX(timecreated) as last_activity
                 FROM {at_learning_activity}
                 WHERE userid = ?
                 GROUP BY activity_type
                 ORDER BY count DESC
                 LIMIT 10",
                [$userId]
            );

            if ($activities) {
                foreach ($activities as $activity) {
                    $data['activity_counts'][$activity->activity_type] = $activity->count;
                    $data['completion_rate'] += $activity->avg_completion;
                }
                $data['completion_rate'] = $data['completion_rate'] / count($activities);

                // 선호 활동 상위 3개
                $data['preferred_activities'] = array_slice(array_keys($data['activity_counts']), 0, 3);
            }

            // 최근 10개 활동
            $recentActivities = $DB->get_records_sql(
                "SELECT id, activity_type, content_id, duration,
                        completion_rate, emotion_state, timecreated
                 FROM {at_learning_activity}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 10",
                [$userId]
            );

            if ($recentActivities) {
                $data['recent_activities'] = array_values((array)$recentActivities);
            }

            return $data;

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 학습 활동 데이터 로드 실패: " . $e->getMessage());
            return [
                'recent_activities' => [],
                'activity_counts' => [],
                'preferred_activities' => [],
                'completion_rate' => 0
            ];
        }
    }

    /**
     * 감정 히스토리 로드
     *
     * @param int $userId 사용자 ID
     * @param int $limit 조회 개수
     * @return array 감정 히스토리
     */
    protected function loadEmotionHistory(int $userId, int $limit = 20): array {
        global $DB;

        try {
            $history = $DB->get_records_sql(
                "SELECT id, emotion_type, emotion_intensity, activity_type,
                        trigger_text, context_data, timecreated
                 FROM {at_learning_emotion_log}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT ?",
                [$userId, $limit]
            );

            if (!$history) {
                return [];
            }

            return array_values(array_map(function($record) {
                $data = (array)$record;
                if (!empty($data['context_data'])) {
                    $data['context_data'] = json_decode($data['context_data'], true);
                }
                return $data;
            }, $history));

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 감정 히스토리 로드 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 현재 학습 세션 로드
     *
     * @param int $userId 사용자 ID
     * @return array 현재 학습 세션
     */
    protected function loadCurrentLearningSession(int $userId): array {
        global $DB;

        try {
            // 최근 1시간 이내 세션
            $oneHourAgo = time() - 3600;

            $session = $DB->get_record_sql(
                "SELECT * FROM {at_learning_session}
                 WHERE userid = ? AND timecreated > ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$userId, $oneHourAgo]
            );

            if (!$session) {
                return [
                    'is_active' => false,
                    'session_id' => null,
                    'activity_type' => null,
                    'start_time' => null,
                    'duration' => 0
                ];
            }

            return [
                'is_active' => true,
                'session_id' => $session->id,
                'activity_type' => $session->activity_type ?? null,
                'content_id' => $session->content_id ?? null,
                'start_time' => $session->timecreated,
                'duration' => time() - $session->timecreated,
                'current_emotion' => $session->current_emotion ?? 'neutral',
                'session_data' => json_decode($session->session_data ?? '{}', true)
            ];

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 현재 세션 로드 실패: " . $e->getMessage());
            return [
                'is_active' => false,
                'session_id' => null,
                'activity_type' => null,
                'start_time' => null,
                'duration' => 0
            ];
        }
    }

    /**
     * 수학 학습 데이터 로드
     *
     * @param int $userId 사용자 ID
     * @return array 수학 학습 데이터
     */
    protected function loadMathLearningData(int $userId): array {
        global $DB;

        try {
            $data = [
                'current_unit' => null,
                'mastery_level' => 0,
                'weak_areas' => [],
                'strong_areas' => [],
                'recent_problems' => [],
                'error_patterns' => []
            ];

            // 현재 학습 단원
            $currentUnit = $DB->get_record_sql(
                "SELECT unit_id, unit_name, progress, mastery_level
                 FROM {at_math_progress}
                 WHERE userid = ? AND is_current = 1
                 LIMIT 1",
                [$userId]
            );

            if ($currentUnit) {
                $data['current_unit'] = (array)$currentUnit;
                $data['mastery_level'] = $currentUnit->mastery_level ?? 0;
            }

            // 취약 영역 (정답률 50% 미만)
            $weakAreas = $DB->get_records_sql(
                "SELECT topic, AVG(correct_rate) as avg_rate, COUNT(*) as attempts
                 FROM {at_math_attempts}
                 WHERE userid = ?
                 GROUP BY topic
                 HAVING avg_rate < 0.5
                 ORDER BY avg_rate ASC
                 LIMIT 5",
                [$userId]
            );

            if ($weakAreas) {
                $data['weak_areas'] = array_values((array)$weakAreas);
            }

            // 강점 영역 (정답률 80% 이상)
            $strongAreas = $DB->get_records_sql(
                "SELECT topic, AVG(correct_rate) as avg_rate, COUNT(*) as attempts
                 FROM {at_math_attempts}
                 WHERE userid = ?
                 GROUP BY topic
                 HAVING avg_rate >= 0.8
                 ORDER BY avg_rate DESC
                 LIMIT 5",
                [$userId]
            );

            if ($strongAreas) {
                $data['strong_areas'] = array_values((array)$strongAreas);
            }

            // 최근 문제 풀이
            $recentProblems = $DB->get_records_sql(
                "SELECT problem_id, problem_type, is_correct, time_spent, error_type
                 FROM {at_math_attempts}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 10",
                [$userId]
            );

            if ($recentProblems) {
                $data['recent_problems'] = array_values((array)$recentProblems);

                // 오류 패턴 분석
                $errors = array_filter($recentProblems, function($p) {
                    return !$p->is_correct && !empty($p->error_type);
                });

                $errorCounts = [];
                foreach ($errors as $error) {
                    $type = $error->error_type;
                    $errorCounts[$type] = ($errorCounts[$type] ?? 0) + 1;
                }
                arsort($errorCounts);
                $data['error_patterns'] = $errorCounts;
            }

            return $data;

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 수학 데이터 로드 실패: " . $e->getMessage());
            return [
                'current_unit' => null,
                'mastery_level' => 0,
                'weak_areas' => [],
                'strong_areas' => [],
                'recent_problems' => [],
                'error_patterns' => []
            ];
        }
    }

    /**
     * 에이전트 간 공유 감정 로드
     *
     * @param int $userId 사용자 ID
     * @return array 공유 감정 데이터
     */
    protected function loadSharedEmotions(int $userId): array {
        global $DB;

        try {
            // 최근 24시간 이내 다른 에이전트로부터 공유된 감정
            $oneDayAgo = time() - 86400;

            $sharedEmotions = $DB->get_records_sql(
                "SELECT source_agent, emotion_type, emotion_intensity,
                        activity_type, context_data, timecreated
                 FROM {at_agent_emotion_share}
                 WHERE userid = ? AND source_agent != 'agent05' AND timecreated > ?
                 ORDER BY timecreated DESC
                 LIMIT 20",
                [$userId, $oneDayAgo]
            );

            if (!$sharedEmotions) {
                return [];
            }

            return array_values(array_map(function($record) {
                $data = (array)$record;
                if (!empty($data['context_data'])) {
                    $data['context_data'] = json_decode($data['context_data'], true);
                }
                return $data;
            }, $sharedEmotions));

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 공유 감정 로드 실패: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 감정 로그 저장
     *
     * @param int $userId 사용자 ID
     * @param array $emotionData 감정 데이터
     * @return bool 성공 여부
     */
    public function saveEmotionLog(int $userId, array $emotionData): bool {
        global $DB;

        try {
            $record = new \stdClass();
            $record->userid = $userId;
            $record->emotion_type = $emotionData['type'] ?? 'unknown';
            $record->emotion_intensity = $emotionData['intensity'] ?? 0.5;
            $record->activity_type = $emotionData['activity'] ?? null;
            $record->trigger_text = $emotionData['trigger'] ?? null;
            $record->context_data = json_encode($emotionData['context'] ?? []);
            $record->timecreated = time();

            $DB->insert_record('at_learning_emotion_log', $record);

            return true;

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 감정 로그 저장 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 학습 세션 업데이트
     *
     * @param int $userId 사용자 ID
     * @param array $sessionData 세션 데이터
     * @return bool 성공 여부
     */
    public function updateLearningSession(int $userId, array $sessionData): bool {
        global $DB;

        try {
            // 기존 세션 확인
            $existing = $DB->get_record_sql(
                "SELECT id FROM {at_learning_session}
                 WHERE userid = ? AND timecreated > ?
                 ORDER BY timecreated DESC LIMIT 1",
                [$userId, time() - 3600]
            );

            $record = new \stdClass();
            $record->userid = $userId;
            $record->activity_type = $sessionData['activity_type'] ?? null;
            $record->content_id = $sessionData['content_id'] ?? null;
            $record->current_emotion = $sessionData['emotion'] ?? 'neutral';
            $record->session_data = json_encode($sessionData);
            $record->timemodified = time();

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('at_learning_session', $record);
            } else {
                $record->timecreated = time();
                $DB->insert_record('at_learning_session', $record);
            }

            return true;

        } catch (\Exception $e) {
            error_log("[Agent05DataContext] {$this->currentFile}:" . __LINE__ . " - 세션 업데이트 실패: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 학습 감정 키워드 분석 (확장)
     *
     * @param string $message 메시지
     * @return array 분석 결과
     */
    public function analyzeLearningEmotionKeywords(string $message): array {
        $result = [
            'primary_emotion' => 'neutral',
            'intensity' => 0.5,
            'detected_keywords' => [],
            'math_specific' => []
        ];

        // 학습 감정 키워드 검사
        foreach ($this->learningEmotionKeywords as $emotion => $levels) {
            foreach ($levels as $level => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($message, $keyword) !== false) {
                        $result['detected_keywords'][] = [
                            'keyword' => $keyword,
                            'emotion' => $emotion,
                            'level' => $level
                        ];

                        // 가장 높은 강도의 감정을 primary로 설정
                        $intensity = $this->getIntensityFromLevel($level);
                        if ($intensity > $result['intensity']) {
                            $result['primary_emotion'] = $emotion;
                            $result['intensity'] = $intensity;
                        }
                    }
                }
            }
        }

        // 수학 특화 감정 키워드 검사
        foreach ($this->mathEmotionKeywords as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    $result['math_specific'][] = [
                        'keyword' => $keyword,
                        'type' => $emotion
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 레벨을 강도로 변환
     *
     * @param string $level 레벨 (high, medium, low)
     * @return float 강도
     */
    protected function getIntensityFromLevel(string $level): float {
        $intensities = [
            'high' => 0.9,
            'medium' => 0.6,
            'low' => 0.3
        ];

        return $intensities[$level] ?? 0.5;
    }

    /**
     * 감정 키워드 추가
     *
     * @param string $emotion 감정 타입
     * @param string $level 강도 레벨
     * @param array $keywords 키워드 배열
     */
    public function addLearningEmotionKeywords(string $emotion, string $level, array $keywords): void {
        if (!isset($this->learningEmotionKeywords[$emotion])) {
            $this->learningEmotionKeywords[$emotion] = [];
        }
        if (!isset($this->learningEmotionKeywords[$emotion][$level])) {
            $this->learningEmotionKeywords[$emotion][$level] = [];
        }

        $this->learningEmotionKeywords[$emotion][$level] = array_merge(
            $this->learningEmotionKeywords[$emotion][$level],
            $keywords
        );
    }
}

/*
 * 관련 DB 테이블:
 *
 * at_learning_activity:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - activity_type: varchar(50) NOT NULL
 *   - content_id: bigint(10)
 *   - duration: int(10) DEFAULT 0
 *   - completion_rate: decimal(5,2) DEFAULT 0
 *   - emotion_state: varchar(50)
 *   - timecreated: bigint(10) NOT NULL
 *
 * at_learning_emotion_log:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - emotion_type: varchar(50) NOT NULL
 *   - emotion_intensity: decimal(3,2) NOT NULL
 *   - activity_type: varchar(50)
 *   - trigger_text: text
 *   - context_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *
 * at_learning_session:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - activity_type: varchar(50)
 *   - content_id: bigint(10)
 *   - current_emotion: varchar(50)
 *   - session_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *   - timemodified: bigint(10) NOT NULL
 *
 * at_math_progress:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - unit_id: bigint(10) NOT NULL
 *   - unit_name: varchar(255)
 *   - progress: decimal(5,2) DEFAULT 0
 *   - mastery_level: decimal(3,2) DEFAULT 0
 *   - is_current: tinyint(1) DEFAULT 0
 *
 * at_math_attempts:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - problem_id: bigint(10) NOT NULL
 *   - problem_type: varchar(50)
 *   - topic: varchar(100)
 *   - is_correct: tinyint(1)
 *   - correct_rate: decimal(3,2)
 *   - time_spent: int(10)
 *   - error_type: varchar(50)
 *   - timecreated: bigint(10) NOT NULL
 *
 * at_agent_emotion_share:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - source_agent: varchar(50) NOT NULL
 *   - emotion_type: varchar(50) NOT NULL
 *   - emotion_intensity: decimal(3,2) NOT NULL
 *   - activity_type: varchar(50)
 *   - context_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 */
