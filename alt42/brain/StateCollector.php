<?php
/**
 * StateCollector.php - 학생 상태 실시간 수집기
 * 
 * 학생의 행동, 감정, 인지 상태를 실시간으로 수집하여
 * 파동함수 계산에 필요한 데이터를 제공
 * 
 * Brain Layer의 핵심 컴포넌트
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.1.0
 * @created     2025-12-08
 * @updated     2025-12-08 - DB 테이블 참조 수정 (실제 테이블로)
 * 
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/StateCollector.php
 * 
 * 참조 테이블:
 * - alt42_student_profiles: 감정/동기/자신감 점수
 * - alt42_student_activity: 활동별 감정 상태
 * - alt42_goinghome: 하교 설문 (침착도 등)
 * - abessi_tracking: 문제 풀이 추적
 * - mdl_alt42_learning_sessions: 학습 세션
 */

// Moodle 환경
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 설정 로드
require_once(__DIR__ . '/../config/ai_services.config.php');

/**
 * Class StateCollector
 * 
 * 학생 상태 데이터를 수집하고 정규화
 * 에이전트들로부터 정보를 수집하여 통합
 */
class StateCollector
{
    /** @var StateCollector|null Singleton 인스턴스 */
    private static $instance = null;
    
    /** @var \moodle_database DB 인스턴스 */
    private $db;
    
    /** @var int 현재 학생 ID */
    private $studentId;
    
    /** @var array 수집된 상태 캐시 */
    private $stateCache = [];
    
    /** @var int 캐시 유효 시간 (초) */
    private $cacheTTL = 5;
    
    /** @var int 마지막 수집 시간 */
    private $lastCollectTime = 0;

    /**
     * Private 생성자
     */
    private function __construct()
    {
        global $DB;
        $this->db = $DB;
    }

    /**
     * Singleton 인스턴스 반환
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 학생 ID 설정
     */
    public function setStudent(int $studentId): self
    {
        $this->studentId = $studentId;
        $this->stateCache = []; // 캐시 초기화
        return $this;
    }

    /**
     * 실시간 상태 수집
     * 
     * @param int|null $studentId 학생 ID (없으면 현재 설정된 ID 사용)
     * @return array 수집된 상태 데이터
     */
    public function collectRealtime(?int $studentId = null): array
    {
        $studentId = $studentId ?? $this->studentId;
        
        if (!$studentId) {
            return $this->getDefaultState();
        }
        
        // 캐시 확인
        $now = time();
        if (!empty($this->stateCache) && ($now - $this->lastCollectTime) < $this->cacheTTL) {
            return $this->stateCache;
        }
        
        // 각 소스에서 데이터 수집
        $state = [
            // 기본 정보
            'student_id' => $studentId,
            'timestamp' => $now,
            
            // 행동 데이터
            'behavior' => $this->collectBehaviorData($studentId),
            
            // 감정 데이터 (Agent05 연동)
            'emotion' => $this->collectEmotionData($studentId),
            
            // 인지 데이터
            'cognitive' => $this->collectCognitiveData($studentId),
            
            // 컨텍스트 데이터
            'context' => $this->collectContextData($studentId),
            
            // 이탈 위험 (계산 기반)
            'dropout_risk' => $this->calculateDropoutRisk($studentId),
            
            // 침착도 (Agent08 연동)
            'calmness' => $this->collectCalmness($studentId)
        ];
        
        // 정규화된 상태 벡터 계산
        $state['normalized'] = $this->normalizeState($state);
        
        // 캐시 업데이트
        $this->stateCache = $state;
        $this->lastCollectTime = $now;
        
        return $state;
    }

    /**
     * 행동 데이터 수집
     * 테이블: abessi_tracking (문제 풀이 추적)
     */
    private function collectBehaviorData(int $studentId): array
    {
        try {
            // 최근 문제 풀이 기록 (abessi_tracking 테이블)
            $recentActivity = $this->db->get_record_sql(
                "SELECT *, UNIX_TIMESTAMP(timecreated) as created_ts 
                 FROM {abessi_tracking} 
                 WHERE userid = ? 
                 ORDER BY timecreated DESC LIMIT 1",
                [$studentId]
            );
            
            $idleSeconds = 0;
            $lastAction = 'unknown';
            
            if ($recentActivity) {
                $idleSeconds = time() - ($recentActivity->created_ts ?? time());
                $lastAction = 'problem_submit';
            }
            
            return [
                'idle_seconds' => min($idleSeconds, 300),  // 최대 5분
                'mouse_jitter' => 0,  // TODO: 실시간 WebSocket 수집
                'scroll_activity' => 0,
                'typing_speed' => 0,
                'last_action' => $lastAction
            ];
        } catch (Exception $e) {
            // 테이블이 없거나 오류 시 기본값
            return [
                'idle_seconds' => 0,
                'mouse_jitter' => 0,
                'scroll_activity' => 0,
                'typing_speed' => 0,
                'last_action' => 'error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 감정 데이터 수집 (Agent05 연동)
     * 테이블: alt42_student_profiles, alt42_student_activity
     */
    private function collectEmotionData(int $studentId): array
    {
        try {
            // 1. alt42_student_profiles에서 감정/동기/자신감 점수
            $profileData = $this->db->get_record_sql(
                "SELECT emotion_score, motivation_score, confidence_score, 
                        stress_level, confidence_level
                 FROM {alt42_student_profiles} 
                 WHERE userid = ?",
                [$studentId]
            );
            
            // 2. alt42_student_activity에서 최근 감정 상태
            $activityData = $this->db->get_record_sql(
                "SELECT emotion_state, anxiety_level, concentration_level,
                        fatigue_level, engagement_state
                 FROM {alt42_student_activity} 
                 WHERE userid = ? 
                 ORDER BY timecreated DESC LIMIT 1",
                [$studentId]
            );
            
            // 데이터 통합
            $emotionScore = floatval($profileData->emotion_score ?? 50) / 100;  // 0~1로 정규화
            $confidence = floatval($profileData->confidence_score ?? 50) / 100;
            $stress = floatval($profileData->stress_level ?? 30) / 100;
            $anxiety = floatval($activityData->anxiety_level ?? 30) / 100;
            
            // Valence (긍정/부정) 계산: 감정점수 - 스트레스
            $valence = max(0, min(1, ($emotionScore - $stress + 1) / 2));
            
            // Arousal (활성화) 계산: 집중도 기반
            $concentration = floatval($activityData->concentration_level ?? 50) / 100;
            $arousal = $concentration;
            
            return [
                'current' => $activityData->emotion_state ?? 'neutral',
                'valence' => $valence,
                'arousal' => $arousal,
                'frustration' => $stress,
                'anxiety' => $anxiety,
                'confidence' => $confidence
            ];
        } catch (Exception $e) {
            // 테이블이 없을 수 있음 - 기본값 반환
            return [
                'current' => 'neutral',
                'valence' => 0.5,
                'arousal' => 0.5,
                'frustration' => 0,
                'anxiety' => 0,
                'confidence' => 0.5
            ];
        }
    }

    /**
     * 인지 데이터 수집
     * 테이블: abessi_tracking (정답률 계산)
     */
    private function collectCognitiveData(int $studentId): array
    {
        try {
            // 최근 정답률 (abessi_tracking)
            $recentPerformance = $this->db->get_record_sql(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN iscorrect = 1 THEN 1 ELSE 0 END) as correct
                 FROM {abessi_tracking}
                 WHERE userid = ? 
                 AND UNIX_TIMESTAMP(timecreated) > ?",
                [$studentId, time() - 3600]  // 최근 1시간
            );
            
            $accuracy = 0.5;
            $total = intval($recentPerformance->total ?? 0);
            
            if ($total > 0) {
                $accuracy = intval($recentPerformance->correct ?? 0) / $total;
            }
            
            return [
                'recent_accuracy' => $accuracy,
                'problems_attempted' => $total,
                'cognitive_load' => $this->estimateCognitiveLoad($studentId),
                'understanding_level' => $accuracy  // 간단한 추정
            ];
        } catch (Exception $e) {
            return [
                'recent_accuracy' => 0.5,
                'problems_attempted' => 0,
                'cognitive_load' => 0.5,
                'understanding_level' => 0.5
            ];
        }
    }

    /**
     * 컨텍스트 데이터 수집
     * 테이블: mdl_alt42_learning_sessions
     */
    private function collectContextData(int $studentId): array
    {
        try {
            // 현재 학습 세션 정보
            $session = $this->db->get_record_sql(
                "SELECT *, UNIX_TIMESTAMP(timestart) as start_ts
                 FROM {mdl_alt42_learning_sessions}
                 WHERE userid = ? AND status = 'active'
                 ORDER BY timestart DESC LIMIT 1",
                [$studentId]
            );
            
            $sessionDuration = 0;
            $topic = 'unknown';
            $difficulty = 'medium';
            
            if ($session) {
                $sessionDuration = time() - ($session->start_ts ?? time());
                $topic = $session->topic ?? $session->current_unit ?? 'unknown';
                $difficulty = $session->difficulty_level ?? 'medium';
            }
            
            return [
                'session_duration_minutes' => round($sessionDuration / 60),
                'current_topic' => $topic,
                'difficulty_level' => $difficulty,
                'time_of_day' => date('H'),  // 시간대
                'day_of_week' => date('N')   // 요일 (1=월, 7=일)
            ];
        } catch (Exception $e) {
            return [
                'session_duration_minutes' => 0,
                'current_topic' => 'unknown',
                'difficulty_level' => 'medium',
                'time_of_day' => date('H'),
                'day_of_week' => date('N')
            ];
        }
    }

    /**
     * 이탈 위험 계산 (Agent13 로직 기반)
     * 직접 계산: 비활성 시간, 정답률, 감정 상태 기반
     */
    private function calculateDropoutRisk(int $studentId): float
    {
        try {
            // 비활성 시간
            $behavior = $this->collectBehaviorData($studentId);
            $idleMinutes = ($behavior['idle_seconds'] ?? 0) / 60;
            
            // 최근 정답률
            $cognitive = $this->collectCognitiveData($studentId);
            $accuracy = $cognitive['recent_accuracy'] ?? 0.5;
            
            // 감정 상태
            $emotion = $this->collectEmotionData($studentId);
            $frustration = $emotion['frustration'] ?? 0;
            
            // 이탈 위험 계산
            // 높은 비활성 시간 + 낮은 정답률 + 높은 좌절감 = 높은 이탈 위험
            $idleFactor = min(1.0, $idleMinutes / 10);  // 10분 이상이면 1.0
            $accuracyFactor = 1 - $accuracy;            // 낮은 정답률 = 높은 위험
            $frustrationFactor = $frustration;
            
            $risk = 0.4 * $idleFactor + 0.3 * $accuracyFactor + 0.3 * $frustrationFactor;
            
            return min(1.0, max(0.0, $risk));
        } catch (Exception $e) {
            return 0.2;  // 기본 낮은 위험
        }
    }

    /**
     * 침착도 수집 (Agent08 연동)
     * 테이블: alt42_goinghome (하교 설문) 또는 alt42_student_activity
     */
    private function collectCalmness(int $studentId): float
    {
        try {
            // 1. alt42_goinghome에서 최근 침착도
            $goingHome = $this->db->get_record_sql(
                "SELECT calmness 
                 FROM {alt42_goinghome} 
                 WHERE userid = ? 
                 ORDER BY timecreated DESC LIMIT 1",
                [$studentId]
            );
            
            if ($goingHome && isset($goingHome->calmness)) {
                // JSON에서 추출하거나 직접 값 사용
                $calmness = is_string($goingHome->calmness) 
                    ? json_decode($goingHome->calmness, true) 
                    : $goingHome->calmness;
                    
                if (is_numeric($calmness)) {
                    // 1-10 스케일을 0-1로 정규화
                    return floatval($calmness) / 10.0;
                }
            }
            
            // 2. alt42_student_activity에서 집중/긴장 상태로 추정
            $activity = $this->db->get_record_sql(
                "SELECT concentration_level, tension_level, anxiety_level
                 FROM {alt42_student_activity}
                 WHERE userid = ?
                 ORDER BY timecreated DESC LIMIT 1",
                [$studentId]
            );
            
            if ($activity) {
                $concentration = floatval($activity->concentration_level ?? 50) / 100;
                $tension = floatval($activity->tension_level ?? 30) / 100;
                $anxiety = floatval($activity->anxiety_level ?? 30) / 100;
                
                // 침착도 = 집중도 - 긴장 - 불안
                $calmness = max(0, $concentration - 0.3 * $tension - 0.3 * $anxiety);
                return min(1.0, $calmness);
            }
            
            return 0.625;  // 기본값 (중간)
        } catch (Exception $e) {
            return 0.625;  // 기본값
        }
    }

    /**
     * 인지 부하 추정
     */
    private function estimateCognitiveLoad(int $studentId): float
    {
        try {
            // 최근 문제 난이도와 소요 시간 기반 추정 (abessi_tracking)
            $problems = $this->db->get_records_sql(
                "SELECT difficulty, time_spent 
                 FROM {abessi_tracking}
                 WHERE userid = ?
                 ORDER BY timecreated DESC LIMIT 5",
                [$studentId]
            );
            
            if (empty($problems)) {
                return 0.5;
            }
            
            $totalLoad = 0;
            foreach ($problems as $p) {
                $diff = $p->difficulty ?? 'medium';
                $difficultyFactor = ($diff === 'hard' || $diff === '상') ? 0.8 : 
                                   (($diff === 'easy' || $diff === '하') ? 0.3 : 0.5);
                $timeFactor = min(1.0, ($p->time_spent ?? 60) / 120);  // 2분 이상이면 1.0
                $totalLoad += ($difficultyFactor + $timeFactor) / 2;
            }
            
            return $totalLoad / count($problems);
        } catch (Exception $e) {
            return 0.5;
        }
    }

    /**
     * 상태 정규화 (파동함수 계산용)
     */
    private function normalizeState(array $state): array
    {
        $emotion = $state['emotion'];
        $behavior = $state['behavior'];
        $cognitive = $state['cognitive'];
        
        // 8차원 StateVector 생성 (quantum-orchestration-design.md 기반)
        return [
            'metacognition' => $cognitive['understanding_level'],
            'self_efficacy' => $emotion['confidence'],
            'help_seeking' => 0.5,  // TODO: 도움 요청 빈도 추적
            'emotional_regulation' => $state['calmness'],
            'anxiety' => $emotion['anxiety'],
            'confidence' => $emotion['confidence'],
            'engagement' => 1.0 - min(1.0, $behavior['idle_seconds'] / 60),  // 활동 기반
            'motivation' => 1.0 - $state['dropout_risk']
        ];
    }

    /**
     * 기본 상태 반환 (학생 ID 없을 때)
     */
    private function getDefaultState(): array
    {
        return [
            'student_id' => 0,
            'timestamp' => time(),
            'behavior' => [
                'idle_seconds' => 0,
                'mouse_jitter' => 0,
                'scroll_activity' => 0,
                'typing_speed' => 0,
                'last_action' => 'none'
            ],
            'emotion' => [
                'current' => 'neutral',
                'valence' => 0.5,
                'arousal' => 0.5,
                'frustration' => 0,
                'anxiety' => 0,
                'confidence' => 0.5
            ],
            'cognitive' => [
                'recent_accuracy' => 0.5,
                'problems_attempted' => 0,
                'cognitive_load' => 0.5,
                'understanding_level' => 0.5
            ],
            'context' => [
                'session_duration_minutes' => 0,
                'current_topic' => 'unknown',
                'difficulty_level' => 'medium',
                'time_of_day' => date('H'),
                'day_of_week' => date('N')
            ],
            'dropout_risk' => 0.2,
            'calmness' => 0.625,
            'normalized' => [
                'metacognition' => 0.5,
                'self_efficacy' => 0.5,
                'help_seeking' => 0.5,
                'emotional_regulation' => 0.5,
                'anxiety' => 0,
                'confidence' => 0.5,
                'engagement' => 0.5,
                'motivation' => 0.5
            ]
        ];
    }

    /**
     * 상태를 JSON으로 반환 (API용)
     */
    public function toJSON(?int $studentId = null): string
    {
        $state = $this->collectRealtime($studentId);
        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
