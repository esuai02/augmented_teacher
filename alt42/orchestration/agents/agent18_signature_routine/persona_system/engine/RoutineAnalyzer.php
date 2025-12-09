<?php
/**
 * Agent18 Signature Routine - Routine Analyzer
 *
 * 학습자의 루틴 패턴을 분석하여 시그너처 루틴을 발견.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/RoutineAnalyzer.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class RoutineAnalyzer {

    /** @var int 사용자 ID */
    private $userId;

    /** @var array 분석 결과 */
    private $analysisResult = [];

    /** @var array 추천 데이터 */
    private $recommendation = [];

    /** @var int 분석 기간 (일) */
    private $analysisPeriod = 30;

    /** @var float 패턴 인식 신뢰도 임계값 */
    private $confidenceThreshold = 0.7;

    /**
     * 생성자
     *
     * @param int $userId 사용자 ID
     */
    public function __construct($userId) {
        $this->userId = $userId;
    }

    /**
     * 전체 루틴 분석 실행
     *
     * @return array 분석 결과
     */
    public function analyze() {
        try {
            // 1. 학습 세션 데이터 로드
            $sessions = $this->loadLearningData();

            if (empty($sessions)) {
                return $this->getEmptyAnalysis();
            }

            // 2. 시간대별 성과 분석
            $timeAnalysis = $this->analyzeTimePatterns($sessions);

            // 3. 세션 길이 분석
            $durationAnalysis = $this->analyzeDuration($sessions);

            // 4. 휴식 패턴 분석
            $breakAnalysis = $this->analyzeBreakPatterns($sessions);

            // 5. 요일별 패턴 분석
            $weekdayAnalysis = $this->analyzeWeekdayPatterns($sessions);

            // 6. 골든타임 계산
            $goldenTime = $this->calculateGoldenTime($timeAnalysis);

            // 7. 시그너처 루틴 생성
            $signatureRoutine = $this->generateSignatureRoutine(
                $timeAnalysis,
                $durationAnalysis,
                $breakAnalysis,
                $weekdayAnalysis,
                $goldenTime
            );

            // 8. 추천 생성
            $this->generateRecommendation($signatureRoutine);

            $this->analysisResult = [
                'time_patterns' => $timeAnalysis,
                'duration_patterns' => $durationAnalysis,
                'break_patterns' => $breakAnalysis,
                'weekday_patterns' => $weekdayAnalysis,
                'golden_time' => $goldenTime,
                'signature_routine' => $signatureRoutine,
                'session_count' => count($sessions),
                'analysis_period' => $this->analysisPeriod,
                'analyzed_at' => time()
            ];

            return $this->analysisResult;

        } catch (Exception $e) {
            error_log("[Agent18 RoutineAnalyzer] 분석 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            return $this->getEmptyAnalysis();
        }
    }

    /**
     * 학습 데이터 로드
     *
     * @return array 학습 세션 목록
     */
    private function loadLearningData() {
        global $DB;

        $startTime = time() - ($this->analysisPeriod * 24 * 60 * 60);

        $sessions = $DB->get_records_sql(
            "SELECT *
             FROM {alt42_learning_sessions}
             WHERE userid = ? AND started_at >= ?
             ORDER BY started_at ASC",
            [$this->userId, $startTime]
        );

        return $sessions ? array_values($sessions) : [];
    }

    /**
     * 시간대별 패턴 분석
     *
     * @param array $sessions 세션 목록
     * @return array 시간대별 분석 결과
     */
    private function analyzeTimePatterns($sessions) {
        $timeSlots = [
            'early_morning' => ['start' => 5, 'end' => 8, 'sessions' => [], 'scores' => []],
            'morning' => ['start' => 8, 'end' => 12, 'sessions' => [], 'scores' => []],
            'afternoon' => ['start' => 12, 'end' => 17, 'sessions' => [], 'scores' => []],
            'evening' => ['start' => 17, 'end' => 21, 'sessions' => [], 'scores' => []],
            'night' => ['start' => 21, 'end' => 24, 'sessions' => [], 'scores' => []],
            'late_night' => ['start' => 0, 'end' => 5, 'sessions' => [], 'scores' => []]
        ];

        foreach ($sessions as $session) {
            $hour = (int)date('H', $session->started_at);
            $score = $session->performance_score ?? 0;

            foreach ($timeSlots as $slotName => &$slot) {
                $inSlot = false;
                if ($slotName === 'late_night') {
                    $inSlot = ($hour >= 0 && $hour < 5);
                } elseif ($slotName === 'night') {
                    $inSlot = ($hour >= 21 && $hour < 24);
                } else {
                    $inSlot = ($hour >= $slot['start'] && $hour < $slot['end']);
                }

                if ($inSlot) {
                    $slot['sessions'][] = $session;
                    $slot['scores'][] = $score;
                    break;
                }
            }
        }

        // 통계 계산
        $result = [];
        foreach ($timeSlots as $slotName => $slot) {
            $count = count($slot['sessions']);
            $avgScore = $count > 0 ? array_sum($slot['scores']) / $count : 0;
            $stdDev = $count > 1 ? $this->calculateStdDev($slot['scores']) : 0;

            $result[$slotName] = [
                'session_count' => $count,
                'avg_score' => round($avgScore, 2),
                'std_dev' => round($stdDev, 2),
                'consistency' => $stdDev > 0 ? round(1 - min($stdDev / 100, 1), 2) : 0,
                'time_range' => sprintf("%02d:00-%02d:00", $slot['start'], $slot['end'])
            ];
        }

        return $result;
    }

    /**
     * 세션 길이 분석
     *
     * @param array $sessions 세션 목록
     * @return array 세션 길이 분석 결과
     */
    private function analyzeDuration($sessions) {
        $durations = [];
        $durationScores = [];

        foreach ($sessions as $session) {
            $duration = ($session->duration ?? 0) / 60; // 분 단위
            if ($duration > 0) {
                $durations[] = $duration;
                $durationScores[$this->getDurationCategory($duration)][] =
                    $session->performance_score ?? 0;
            }
        }

        if (empty($durations)) {
            return [
                'avg_duration' => 0,
                'optimal_duration' => 45,
                'category_performance' => []
            ];
        }

        // 카테고리별 성과 분석
        $categoryPerformance = [];
        foreach ($durationScores as $category => $scores) {
            $categoryPerformance[$category] = [
                'count' => count($scores),
                'avg_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0
            ];
        }

        // 최적 세션 길이 계산
        $optimalDuration = $this->calculateOptimalDuration($categoryPerformance);

        return [
            'avg_duration' => round(array_sum($durations) / count($durations), 1),
            'min_duration' => round(min($durations), 1),
            'max_duration' => round(max($durations), 1),
            'optimal_duration' => $optimalDuration,
            'category_performance' => $categoryPerformance
        ];
    }

    /**
     * 세션 길이 카테고리 분류
     *
     * @param float $duration 세션 길이 (분)
     * @return string 카테고리
     */
    private function getDurationCategory($duration) {
        if ($duration < 15) return 'micro';      // 15분 미만
        if ($duration < 30) return 'short';      // 15-30분
        if ($duration < 60) return 'medium';     // 30-60분
        if ($duration < 90) return 'long';       // 60-90분
        return 'extended';                        // 90분 이상
    }

    /**
     * 최적 세션 길이 계산
     *
     * @param array $categoryPerformance 카테고리별 성과
     * @return int 최적 세션 길이 (분)
     */
    private function calculateOptimalDuration($categoryPerformance) {
        $bestCategory = null;
        $bestScore = 0;

        foreach ($categoryPerformance as $category => $data) {
            if ($data['count'] >= 3 && $data['avg_score'] > $bestScore) {
                $bestScore = $data['avg_score'];
                $bestCategory = $category;
            }
        }

        $categoryDurations = [
            'micro' => 10,
            'short' => 25,
            'medium' => 45,
            'long' => 75,
            'extended' => 120
        ];

        return $bestCategory ? $categoryDurations[$bestCategory] : 45;
    }

    /**
     * 휴식 패턴 분석
     *
     * @param array $sessions 세션 목록
     * @return array 휴식 패턴 분석 결과
     */
    private function analyzeBreakPatterns($sessions) {
        $breakCounts = [];
        $breakEffects = [];

        foreach ($sessions as $session) {
            $breakCount = $session->break_count ?? 0;
            $score = $session->performance_score ?? 0;

            $breakCounts[] = $breakCount;

            $breakCategory = $this->getBreakCategory($breakCount);
            if (!isset($breakEffects[$breakCategory])) {
                $breakEffects[$breakCategory] = [];
            }
            $breakEffects[$breakCategory][] = $score;
        }

        // 휴식 카테고리별 성과 분석
        $categoryAnalysis = [];
        foreach ($breakEffects as $category => $scores) {
            $categoryAnalysis[$category] = [
                'count' => count($scores),
                'avg_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0
            ];
        }

        // 최적 휴식 빈도 계산
        $optimalBreakFrequency = $this->calculateOptimalBreakFrequency($categoryAnalysis);

        return [
            'avg_breaks_per_session' => count($breakCounts) > 0 ?
                round(array_sum($breakCounts) / count($breakCounts), 1) : 0,
            'category_analysis' => $categoryAnalysis,
            'optimal_break_frequency' => $optimalBreakFrequency,
            'recommendation' => $this->getBreakRecommendation($optimalBreakFrequency)
        ];
    }

    /**
     * 휴식 횟수 카테고리 분류
     *
     * @param int $breakCount 휴식 횟수
     * @return string 카테고리
     */
    private function getBreakCategory($breakCount) {
        if ($breakCount === 0) return 'no_break';
        if ($breakCount <= 2) return 'few_breaks';
        if ($breakCount <= 4) return 'moderate_breaks';
        return 'frequent_breaks';
    }

    /**
     * 최적 휴식 빈도 계산
     *
     * @param array $categoryAnalysis 카테고리별 분석
     * @return string 최적 휴식 빈도
     */
    private function calculateOptimalBreakFrequency($categoryAnalysis) {
        $bestCategory = 'moderate_breaks';
        $bestScore = 0;

        foreach ($categoryAnalysis as $category => $data) {
            if ($data['count'] >= 2 && $data['avg_score'] > $bestScore) {
                $bestScore = $data['avg_score'];
                $bestCategory = $category;
            }
        }

        return $bestCategory;
    }

    /**
     * 휴식 추천 메시지
     *
     * @param string $optimalFrequency 최적 휴식 빈도
     * @return string 추천 메시지
     */
    private function getBreakRecommendation($optimalFrequency) {
        $recommendations = [
            'no_break' => '집중 모드가 효과적! 방해받지 않는 환경을 만들어보세요.',
            'few_breaks' => '포모도로 테크닉(25분 집중 + 5분 휴식)이 잘 맞아요!',
            'moderate_breaks' => '적절한 휴식으로 집중력을 유지하고 있어요. 지금 방식을 유지하세요.',
            'frequent_breaks' => '짧은 집중 사이클이 효과적이네요. 15분 집중 + 3분 휴식을 시도해보세요.'
        ];

        return $recommendations[$optimalFrequency] ?? $recommendations['moderate_breaks'];
    }

    /**
     * 요일별 패턴 분석
     *
     * @param array $sessions 세션 목록
     * @return array 요일별 분석 결과
     */
    private function analyzeWeekdayPatterns($sessions) {
        $weekdays = [
            0 => ['name' => '일요일', 'sessions' => [], 'scores' => []],
            1 => ['name' => '월요일', 'sessions' => [], 'scores' => []],
            2 => ['name' => '화요일', 'sessions' => [], 'scores' => []],
            3 => ['name' => '수요일', 'sessions' => [], 'scores' => []],
            4 => ['name' => '목요일', 'sessions' => [], 'scores' => []],
            5 => ['name' => '금요일', 'sessions' => [], 'scores' => []],
            6 => ['name' => '토요일', 'sessions' => [], 'scores' => []]
        ];

        foreach ($sessions as $session) {
            $dayOfWeek = (int)date('w', $session->started_at);
            $weekdays[$dayOfWeek]['sessions'][] = $session;
            $weekdays[$dayOfWeek]['scores'][] = $session->performance_score ?? 0;
        }

        $result = [];
        $bestDay = null;
        $bestScore = 0;

        foreach ($weekdays as $dayNum => $data) {
            $count = count($data['sessions']);
            $avgScore = $count > 0 ? array_sum($data['scores']) / $count : 0;

            $result[$dayNum] = [
                'name' => $data['name'],
                'session_count' => $count,
                'avg_score' => round($avgScore, 2)
            ];

            if ($count >= 2 && $avgScore > $bestScore) {
                $bestScore = $avgScore;
                $bestDay = $dayNum;
            }
        }

        return [
            'daily_analysis' => $result,
            'best_day' => $bestDay,
            'best_day_name' => $bestDay !== null ? $weekdays[$bestDay]['name'] : null,
            'weekend_vs_weekday' => $this->compareWeekendWeekday($result)
        ];
    }

    /**
     * 주말 vs 평일 비교
     *
     * @param array $dailyAnalysis 일별 분석
     * @return array 비교 결과
     */
    private function compareWeekendWeekday($dailyAnalysis) {
        $weekdayScores = [];
        $weekendScores = [];

        foreach ($dailyAnalysis as $dayNum => $data) {
            if ($data['session_count'] > 0) {
                if ($dayNum == 0 || $dayNum == 6) {
                    $weekendScores[] = $data['avg_score'];
                } else {
                    $weekdayScores[] = $data['avg_score'];
                }
            }
        }

        $weekdayAvg = count($weekdayScores) > 0 ?
            array_sum($weekdayScores) / count($weekdayScores) : 0;
        $weekendAvg = count($weekendScores) > 0 ?
            array_sum($weekendScores) / count($weekendScores) : 0;

        return [
            'weekday_avg' => round($weekdayAvg, 2),
            'weekend_avg' => round($weekendAvg, 2),
            'better_period' => $weekdayAvg > $weekendAvg ? 'weekday' : 'weekend'
        ];
    }

    /**
     * 골든타임 계산
     *
     * @param array $timeAnalysis 시간대 분석 결과
     * @return array 골든타임 정보
     */
    private function calculateGoldenTime($timeAnalysis) {
        $bestSlot = null;
        $bestScore = 0;
        $totalSessions = 0;

        foreach ($timeAnalysis as $slotName => $data) {
            $totalSessions += $data['session_count'];
            if ($data['session_count'] >= 3 && $data['avg_score'] > $bestScore) {
                $bestScore = $data['avg_score'];
                $bestSlot = $slotName;
            }
        }

        if (!$bestSlot) {
            return [
                'identified' => false,
                'confidence' => 0,
                'message' => '더 많은 학습 데이터가 필요합니다.'
            ];
        }

        // 신뢰도 계산
        $slotSessions = $timeAnalysis[$bestSlot]['session_count'];
        $confidence = min(1.0, ($slotSessions / $totalSessions) + ($bestScore / 100));

        $slotNames = [
            'early_morning' => '이른 아침 (05-08시)',
            'morning' => '오전 (08-12시)',
            'afternoon' => '오후 (12-17시)',
            'evening' => '저녁 (17-21시)',
            'night' => '밤 (21-24시)',
            'late_night' => '심야 (00-05시)'
        ];

        return [
            'identified' => $confidence >= $this->confidenceThreshold,
            'slot' => $bestSlot,
            'slot_name' => $slotNames[$bestSlot] ?? $bestSlot,
            'time_range' => $timeAnalysis[$bestSlot]['time_range'],
            'avg_score' => $bestScore,
            'confidence' => round($confidence, 2),
            'consistency' => $timeAnalysis[$bestSlot]['consistency']
        ];
    }

    /**
     * 시그너처 루틴 생성
     *
     * @param array $timeAnalysis 시간 분석
     * @param array $durationAnalysis 세션 길이 분석
     * @param array $breakAnalysis 휴식 분석
     * @param array $weekdayAnalysis 요일 분석
     * @param array $goldenTime 골든타임
     * @return array 시그너처 루틴
     */
    private function generateSignatureRoutine($timeAnalysis, $durationAnalysis, $breakAnalysis, $weekdayAnalysis, $goldenTime) {
        $confidence = $this->calculateOverallConfidence(
            $timeAnalysis,
            $durationAnalysis,
            $goldenTime
        );

        return [
            'optimal_time' => $goldenTime['slot_name'] ?? '미확인',
            'optimal_duration' => $durationAnalysis['optimal_duration'] . '분',
            'break_pattern' => $breakAnalysis['optimal_break_frequency'],
            'best_day' => $weekdayAnalysis['best_day_name'] ?? '미확인',
            'confidence' => $confidence,
            'is_reliable' => $confidence >= $this->confidenceThreshold
        ];
    }

    /**
     * 전체 신뢰도 계산
     *
     * @param array $timeAnalysis 시간 분석
     * @param array $durationAnalysis 세션 길이 분석
     * @param array $goldenTime 골든타임
     * @return float 신뢰도
     */
    private function calculateOverallConfidence($timeAnalysis, $durationAnalysis, $goldenTime) {
        $factors = [];

        // 데이터 양 기반 신뢰도
        $totalSessions = array_sum(array_column($timeAnalysis, 'session_count'));
        $factors[] = min(1.0, $totalSessions / 20); // 20세션 이상이면 최대

        // 골든타임 신뢰도
        $factors[] = $goldenTime['confidence'] ?? 0;

        // 세션 길이 일관성
        $durationRange = ($durationAnalysis['max_duration'] - $durationAnalysis['min_duration']);
        $factors[] = $durationRange > 0 ? max(0, 1 - ($durationRange / 120)) : 0.5;

        return count($factors) > 0 ? round(array_sum($factors) / count($factors), 2) : 0;
    }

    /**
     * 추천 생성
     *
     * @param array $signatureRoutine 시그너처 루틴
     */
    private function generateRecommendation($signatureRoutine) {
        $this->recommendation = [
            'primary' => $this->generatePrimaryRecommendation($signatureRoutine),
            'secondary' => $this->generateSecondaryRecommendations($signatureRoutine),
            'routine_suggestion' => $this->generateRoutineSuggestion($signatureRoutine)
        ];
    }

    /**
     * 주요 추천 생성
     *
     * @param array $signatureRoutine 시그너처 루틴
     * @return string 추천 메시지
     */
    private function generatePrimaryRecommendation($signatureRoutine) {
        if (!$signatureRoutine['is_reliable']) {
            return "아직 충분한 학습 데이터가 모이지 않았어요. 조금 더 학습하면 맞춤 루틴을 발견할 수 있을 거예요!";
        }

        return sprintf(
            "%s에 %s 정도 학습할 때 가장 효과적이에요! 이 시간대를 '골든타임'으로 활용해보세요.",
            $signatureRoutine['optimal_time'],
            $signatureRoutine['optimal_duration']
        );
    }

    /**
     * 부가 추천 생성
     *
     * @param array $signatureRoutine 시그너처 루틴
     * @return array 추천 목록
     */
    private function generateSecondaryRecommendations($signatureRoutine) {
        $recommendations = [];

        if ($signatureRoutine['best_day']) {
            $recommendations[] = "{$signatureRoutine['best_day']}에 중요한 학습을 계획해보세요.";
        }

        $breakRecommendations = [
            'no_break' => '집중력이 뛰어나시네요! 긴 세션도 잘 소화하고 계세요.',
            'few_breaks' => '적절한 휴식 타이밍을 잘 잡고 계세요.',
            'moderate_breaks' => '규칙적인 휴식이 학습 효율을 높여주고 있어요.',
            'frequent_breaks' => '짧은 집중-휴식 사이클이 효과적이네요.'
        ];

        if (isset($breakRecommendations[$signatureRoutine['break_pattern']])) {
            $recommendations[] = $breakRecommendations[$signatureRoutine['break_pattern']];
        }

        return $recommendations;
    }

    /**
     * 루틴 제안 생성
     *
     * @param array $signatureRoutine 시그너처 루틴
     * @return array 루틴 제안
     */
    private function generateRoutineSuggestion($signatureRoutine) {
        return [
            'start_time' => $signatureRoutine['optimal_time'],
            'duration' => $signatureRoutine['optimal_duration'],
            'break_style' => $signatureRoutine['break_pattern'],
            'preferred_day' => $signatureRoutine['best_day']
        ];
    }

    /**
     * 추천 데이터 반환
     *
     * @return array 추천 데이터
     */
    public function getRecommendation() {
        return $this->recommendation;
    }

    /**
     * 빈 분석 결과 반환
     *
     * @return array 빈 분석 결과
     */
    private function getEmptyAnalysis() {
        return [
            'time_patterns' => [],
            'duration_patterns' => ['avg_duration' => 0, 'optimal_duration' => 45],
            'break_patterns' => [],
            'weekday_patterns' => [],
            'golden_time' => ['identified' => false],
            'signature_routine' => ['is_reliable' => false],
            'session_count' => 0,
            'analysis_period' => $this->analysisPeriod,
            'analyzed_at' => time()
        ];
    }

    /**
     * 표준편차 계산
     *
     * @param array $values 값 배열
     * @return float 표준편차
     */
    private function calculateStdDev($values) {
        $count = count($values);
        if ($count < 2) return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }
}

/*
 * DB 테이블 정보:
 *
 * 1. mdl_alt42_learning_sessions
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - started_at: bigint(10) NOT NULL
 *    - ended_at: bigint(10)
 *    - duration: int(10) (초 단위)
 *    - subject: varchar(100)
 *    - performance_score: decimal(5,2)
 *    - break_count: int(5)
 */
