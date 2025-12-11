<?php
/**
 * HybridDataBridge - 하이브리드 시스템과 기존 Moodle 데이터 연동
 * 
 * 기존 테이블들과 HybridStateStabilizer를 연결하는 브릿지 클래스
 * - alt42_student_activity: 학생 활동 데이터
 * - abessi_tracking: 트래킹 데이터
 * - alt42_student_profiles: 학생 프로필
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

require_once(__DIR__ . '/HybridStateStabilizer.php');

class HybridDataBridge
{
    /** @var string 현재 파일 경로 (에러 출력용) */
    private $currentFile = __FILE__;

    /** @var HybridStateStabilizer */
    private $stabilizer;

    /** @var int 사용자 ID */
    private $userId;

    /** @var object Moodle DB 객체 */
    private $db;

    // ============================================================
    // 이벤트 매핑 (기존 활동 → 하이브리드 이벤트)
    // ============================================================

    /**
     * 기존 활동 유형을 하이브리드 이벤트로 매핑
     */
    const ACTIVITY_TO_EVENT = [
        // 문제 풀이 관련
        'problem_correct' => 'correct_answer',
        'problem_wrong' => 'wrong_answer',
        'problem_skip' => 'skip_problem',
        'problem_start' => 'click_problem',
        
        // 힌트/도움 관련
        'hint_used' => 'hint_click',
        'solution_viewed' => 'hint_click',
        'explanation_viewed' => 'page_view',
        
        // 네비게이션
        'page_view' => 'page_view',
        'scroll' => 'scroll_active',
        'video_play' => 'scroll_active',
        'video_pause' => 'idle_short',
        
        // 비활성
        'idle' => 'long_pause',
        'tab_away' => 'tab_switch',
        'session_end' => 'idle_long',
    ];

    /**
     * 활동 소스 테이블과 필드 매핑
     */
    const DATA_SOURCES = [
        'activity' => [
            'table' => 'alt42_student_activity',
            'user_field' => 'userid',
            'time_field' => 'timecreated',
        ],
        'tracking' => [
            'table' => 'abessi_tracking',
            'user_field' => 'userid',
            'time_field' => 'timecreated',
        ],
        'profile' => [
            'table' => 'alt42_student_profiles',
            'user_field' => 'user_id',
            'time_field' => 'updated_at',
        ],
    ];

    // ============================================================
    // 생성자
    // ============================================================

    public function __construct(int $userId = 0)
    {
        global $DB, $USER;
        
        $this->db = $DB;
        $this->userId = $userId ?: ($USER->id ?? 0);
        $this->stabilizer = new HybridStateStabilizer($this->userId);
    }

    // ============================================================
    // 실시간 이벤트 처리
    // ============================================================

    /**
     * 학생 활동 이벤트 처리
     * 기존 활동 로그를 하이브리드 시스템에 전달
     *
     * @param string $activityType 활동 유형
     * @param array $activityData 활동 데이터
     * @return array 처리 결과
     */
    public function processActivityEvent(string $activityType, array $activityData = []): array
    {
        try {
            // 1. 활동 유형을 하이브리드 이벤트로 변환
            $eventType = self::ACTIVITY_TO_EVENT[$activityType] ?? 'page_view';
            
            // 2. 이벤트 데이터 정제
            $eventData = $this->prepareEventData($activityType, $activityData);
            
            // 3. Kalman Correction 실행
            $result = $this->stabilizer->kalmanCorrection($eventType, $eventData);
            
            // 4. 로그 저장 (선택적)
            $this->logEventProcessing($activityType, $eventType, $result);
            
            return [
                'success' => true,
                'original_activity' => $activityType,
                'mapped_event' => $eventType,
                'correction_result' => $result,
                'current_state' => $this->stabilizer->getFullState(),
            ];
            
        } catch (Exception $e) {
            error_log("[HybridDataBridge] processActivityEvent error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 이벤트 데이터 준비
     */
    private function prepareEventData(string $activityType, array $data): array
    {
        $prepared = [];
        
        // 시간 관련
        if (isset($data['time_taken'])) {
            $prepared['time_taken'] = floatval($data['time_taken']);
        }
        if (isset($data['duration'])) {
            $prepared['time_taken'] = floatval($data['duration']);
        }
        
        // 시도 횟수
        if (isset($data['attempt_count'])) {
            $prepared['attempt_count'] = intval($data['attempt_count']);
        }
        
        // 난이도
        if (isset($data['difficulty'])) {
            $prepared['difficulty'] = floatval($data['difficulty']);
        }
        
        // 문제 ID
        if (isset($data['problem_id'])) {
            $prepared['problem_id'] = $data['problem_id'];
        }
        
        return $prepared;
    }

    // ============================================================
    // 배치 동기화 (과거 데이터 반영)
    // ============================================================

    /**
     * 최근 활동 데이터를 기반으로 상태 동기화
     * 세션 시작 시 호출하여 기존 데이터로 초기 상태 설정
     *
     * @param int $lookbackMinutes 조회할 과거 시간 (분)
     * @return array 동기화 결과
     */
    public function syncFromRecentActivity(int $lookbackMinutes = 30): array
    {
        try {
            $since = time() - ($lookbackMinutes * 60);
            $events = [];
            
            // 1. 최근 활동 조회
            $activities = $this->getRecentActivities($since);
            
            // 2. 각 활동을 이벤트로 변환하여 처리
            foreach ($activities as $activity) {
                $activityType = $activity->activity_type ?? 'page_view';
                $activityData = json_decode($activity->activity_data ?? '{}', true);
                
                $eventType = self::ACTIVITY_TO_EVENT[$activityType] ?? 'page_view';
                $this->stabilizer->kalmanCorrection($eventType, $activityData);
                
                $events[] = [
                    'time' => $activity->timecreated,
                    'type' => $activityType,
                    'event' => $eventType,
                ];
            }
            
            // 3. 프로필 데이터로 보정
            $this->adjustFromProfile();
            
            return [
                'success' => true,
                'events_processed' => count($events),
                'lookback_minutes' => $lookbackMinutes,
                'current_state' => $this->stabilizer->getFullState(),
            ];
            
        } catch (Exception $e) {
            error_log("[HybridDataBridge] syncFromRecentActivity error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 최근 활동 조회
     */
    private function getRecentActivities(int $since): array
    {
        $sql = "SELECT * FROM {alt42_student_activity} 
                WHERE userid = ? AND timecreated >= ?
                ORDER BY timecreated ASC
                LIMIT 100";
        
        try {
            return $this->db->get_records_sql($sql, [$this->userId, $since]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 프로필 데이터로 상태 보정
     */
    private function adjustFromProfile(): void
    {
        try {
            $profile = $this->db->get_record('alt42_student_profiles', ['user_id' => $this->userId]);
            
            if ($profile && isset($profile->profile_data)) {
                $data = json_decode($profile->profile_data, true);
                
                // 감정 점수 반영
                if (isset($data['emotion_score'])) {
                    $emotionValue = floatval($data['emotion_score']) / 100;
                    if ($emotionValue < 0.3) {
                        // 낮은 감정 → 집중도 하향 보정
                        $this->stabilizer->kalmanCorrection('long_pause', []);
                    } elseif ($emotionValue > 0.7) {
                        // 높은 감정 → 집중도 상향 보정
                        $this->stabilizer->kalmanCorrection('scroll_active', []);
                    }
                }
            }
        } catch (Exception $e) {
            // 프로필 없으면 무시
        }
    }

    // ============================================================
    // 트래킹 데이터 연동
    // ============================================================

    /**
     * abessi_tracking 데이터를 센서 데이터로 변환하여 Fast Loop 실행
     *
     * @param array $trackingData 트래킹 데이터
     * @return array 처리 결과
     */
    public function processTrackingData(array $trackingData): array
    {
        try {
            // 트래킹 데이터를 센서 형식으로 변환
            $sensorData = [
                'mouse_velocity' => floatval($trackingData['mouse_velocity'] ?? 0),
                'scroll_rate' => floatval($trackingData['scroll_rate'] ?? 0),
                'pause_duration' => floatval($trackingData['idle_duration'] ?? 0),
                'keystroke_rate' => floatval($trackingData['keystroke_rate'] ?? 0),
            ];
            
            // Fast Loop 실행
            $result = $this->stabilizer->fastLoopPredict($sensorData);
            
            return [
                'success' => true,
                'prediction_result' => $result,
                'current_state' => $this->stabilizer->getFullState(),
            ];
            
        } catch (Exception $e) {
            error_log("[HybridDataBridge] processTrackingData error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ============================================================
    // 상태 조회 및 저장
    // ============================================================

    /**
     * 현재 하이브리드 상태 조회
     */
    public function getCurrentState(): array
    {
        return $this->stabilizer->getFullState();
    }

    /**
     * 특정 사용자의 상태 조회
     */
    public function getStateForUser(int $userId): array
    {
        $stabilizer = new HybridStateStabilizer($userId);
        return $stabilizer->getFullState();
    }

    /**
     * 세션 시작 시 초기화
     */
    public function initializeSession(array $options = []): array
    {
        // 1. 기존 상태 로드 시도
        $state = $this->stabilizer->getFullState();
        
        // 2. 오래된 상태면 리셋
        $staleThreshold = 3600; // 1시간
        if (time() - ($state['last_update'] ?? 0) > $staleThreshold) {
            $this->stabilizer->initializeState($options);
        }
        
        // 3. 최근 활동으로 동기화
        $syncResult = $this->syncFromRecentActivity(30);
        
        return [
            'success' => true,
            'initial_state' => $this->stabilizer->getFullState(),
            'sync_result' => $syncResult,
        ];
    }

    // ============================================================
    // 로깅
    // ============================================================

    /**
     * 이벤트 처리 로그 저장
     */
    private function logEventProcessing(string $originalType, string $mappedEvent, array $result): void
    {
        // 향후 분석용 로그 테이블에 저장
        // 현재는 error_log로 대체
        if (isset($result['success']) && $result['success']) {
            // 성공 로그는 기록하지 않음 (성능)
        } else {
            error_log("[HybridDataBridge] Event processing: {$originalType} -> {$mappedEvent}");
        }
    }

    // ============================================================
    // 스태빌라이저 접근
    // ============================================================

    /**
     * 내부 스태빌라이저 객체 반환
     */
    public function getStabilizer(): HybridStateStabilizer
    {
        return $this->stabilizer;
    }
}



