<?php
/**
 * ConditionEvaluator - 조건 평가기
 *
 * 페르소나 식별을 위한 조건 평가 로직
 * 문제 재정의 도메인 특화 조건 처리
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class ConditionEvaluator {

    /** @var array 연산자 맵 */
    private $operators = [
        '==' => 'evaluateEqual',
        '!=' => 'evaluateNotEqual',
        '>' => 'evaluateGreaterThan',
        '>=' => 'evaluateGreaterOrEqual',
        '<' => 'evaluateLessThan',
        '<=' => 'evaluateLessOrEqual',
        'contains' => 'evaluateContains',
        'in' => 'evaluateIn',
        'not_in' => 'evaluateNotIn',
        'matches' => 'evaluateMatches',
        'exists' => 'evaluateExists',
        'empty' => 'evaluateEmpty',
        'between' => 'evaluateBetween'
    ];

    /** @var array 평가 캐시 */
    private $evaluationCache = [];

    /**
     * 조건 세트 평가
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return float 매칭 점수 (0.0 ~ 1.0)
     */
    public function evaluate($conditions, $context) {
        if (empty($conditions)) {
            return 0.5; // 조건 없으면 중립 점수
        }

        $totalScore = 0;
        $conditionCount = 0;

        foreach ($conditions as $condition) {
            $score = $this->evaluateSingleCondition($condition, $context);
            $totalScore += $score;
            $conditionCount++;
        }

        return $conditionCount > 0 ? $totalScore / $conditionCount : 0;
    }

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건
     * @param array $context 컨텍스트
     * @return float 점수 (0.0 ~ 1.0)
     */
    public function evaluateSingleCondition($condition, $context) {
        // 복합 조건 처리
        if (isset($condition['type']) && $condition['type'] === 'compound') {
            return $this->evaluateCompoundCondition($condition, $context);
        }

        // 필드가 없으면 평가 불가
        if (!isset($condition['field'])) {
            return 0;
        }

        $field = $condition['field'];
        $operator = $condition['operator'] ?? '==';
        $expectedValue = $condition['value'] ?? null;

        // 컨텍스트에서 실제 값 추출
        $actualValue = $this->getValueFromContext($field, $context);

        // 연산자별 평가
        $result = $this->applyOperator($operator, $actualValue, $expectedValue);

        // 가중치 적용
        $weight = $condition['weight'] ?? 1.0;

        return $result ? $weight : 0;
    }

    /**
     * 복합 조건 평가 (AND/OR)
     *
     * @param array $condition 복합 조건
     * @param array $context 컨텍스트
     * @return float 점수
     */
    private function evaluateCompoundCondition($condition, $context) {
        $operator = strtoupper($condition['operator'] ?? 'AND');
        $subConditions = $condition['conditions'] ?? [];

        if (empty($subConditions)) {
            return 0;
        }

        $scores = [];
        foreach ($subConditions as $subCondition) {
            $scores[] = $this->evaluateSingleCondition($subCondition, $context);
        }

        switch ($operator) {
            case 'AND':
                // 모든 조건이 충족되어야 함 (최소값 반환)
                return min($scores);

            case 'OR':
                // 하나라도 충족되면 됨 (최대값 반환)
                return max($scores);

            case 'NOT':
                // 부정 (첫 번째 조건의 반대)
                return 1 - $scores[0];

            case 'AVG':
                // 평균
                return array_sum($scores) / count($scores);

            default:
                return 0;
        }
    }

    /**
     * 컨텍스트에서 값 추출 (점 표기법 지원)
     *
     * @param string $field 필드 경로 (예: 'agent_data.performance.score')
     * @param array $context 컨텍스트
     * @return mixed 추출된 값
     */
    private function getValueFromContext($field, $context) {
        // 캐시 확인
        $cacheKey = md5($field . json_encode($context));
        if (isset($this->evaluationCache[$cacheKey])) {
            return $this->evaluationCache[$cacheKey];
        }

        $parts = explode('.', $field);
        $value = $context;

        foreach ($parts as $part) {
            // 배열 인덱스 처리 [n]
            if (preg_match('/^(.+)\[(\d+)\]$/', $part, $matches)) {
                $key = $matches[1];
                $index = intval($matches[2]);

                if (!isset($value[$key]) || !is_array($value[$key])) {
                    return null;
                }
                $value = $value[$key][$index] ?? null;
            } else {
                if (!isset($value[$part])) {
                    return null;
                }
                $value = $value[$part];
            }
        }

        // 캐시 저장
        $this->evaluationCache[$cacheKey] = $value;

        return $value;
    }

    /**
     * 연산자 적용
     *
     * @param string $operator 연산자
     * @param mixed $actual 실제 값
     * @param mixed $expected 기대 값
     * @return bool 평가 결과
     */
    private function applyOperator($operator, $actual, $expected) {
        if (!isset($this->operators[$operator])) {
            return false;
        }

        $method = $this->operators[$operator];
        return $this->$method($actual, $expected);
    }

    // === 연산자 메서드 ===

    private function evaluateEqual($actual, $expected) {
        return $actual == $expected;
    }

    private function evaluateNotEqual($actual, $expected) {
        return $actual != $expected;
    }

    private function evaluateGreaterThan($actual, $expected) {
        return is_numeric($actual) && is_numeric($expected) && $actual > $expected;
    }

    private function evaluateGreaterOrEqual($actual, $expected) {
        return is_numeric($actual) && is_numeric($expected) && $actual >= $expected;
    }

    private function evaluateLessThan($actual, $expected) {
        return is_numeric($actual) && is_numeric($expected) && $actual < $expected;
    }

    private function evaluateLessOrEqual($actual, $expected) {
        return is_numeric($actual) && is_numeric($expected) && $actual <= $expected;
    }

    private function evaluateContains($actual, $expected) {
        if (is_string($actual) && is_string($expected)) {
            return stripos($actual, $expected) !== false;
        }
        if (is_array($actual)) {
            return in_array($expected, $actual);
        }
        return false;
    }

    private function evaluateIn($actual, $expected) {
        if (is_string($expected)) {
            $expected = array_map('trim', explode(',', $expected));
        }
        return is_array($expected) && in_array($actual, $expected);
    }

    private function evaluateNotIn($actual, $expected) {
        return !$this->evaluateIn($actual, $expected);
    }

    private function evaluateMatches($actual, $expected) {
        if (!is_string($actual) || !is_string($expected)) {
            return false;
        }
        return preg_match($expected, $actual) === 1;
    }

    private function evaluateExists($actual, $expected) {
        $exists = $actual !== null;
        return $expected ? $exists : !$exists;
    }

    private function evaluateEmpty($actual, $expected) {
        $isEmpty = empty($actual);
        return $expected ? $isEmpty : !$isEmpty;
    }

    private function evaluateBetween($actual, $expected) {
        if (!is_numeric($actual)) {
            return false;
        }
        if (is_array($expected) && count($expected) >= 2) {
            return $actual >= $expected[0] && $actual <= $expected[1];
        }
        return false;
    }

    // === 문제 재정의 특화 조건 평가 ===

    /**
     * 트리거 시나리오 조건 평가
     *
     * @param string $scenario 시나리오 코드
     * @param array $context 컨텍스트
     * @return float 점수
     */
    public function evaluateTriggerScenario($scenario, $context) {
        $agentData = $context['agent_data'] ?? [];

        switch ($scenario) {
            case 'S1': // 학습 성과 하락
                return $this->evaluatePerformanceDecline($agentData);

            case 'S2': // 학습이탈 경고
                return $this->evaluateDropoutRisk($agentData);

            case 'S3': // 동일 오답 반복
                return $this->evaluateRepeatedErrors($agentData);

            case 'S4': // 루틴 불안정
                return $this->evaluateRoutineStability($agentData);

            case 'S5': // 시간관리 실패
                return $this->evaluateTimeManagement($agentData);

            case 'S6': // 정서/동기 저하
                return $this->evaluateEmotionalState($agentData);

            case 'S7': // 개념 이해 부진
                return $this->evaluateConceptUnderstanding($agentData);

            case 'S8': // 교사 피드백 경고
                return $this->evaluateTeacherFeedback($agentData);

            case 'S9': // 전략 불일치
                return $this->evaluateStrategyAlignment($agentData);

            case 'S10': // 회복 실패
                return $this->evaluateRecoverySuccess($agentData);

            default:
                return 0;
        }
    }

    private function evaluatePerformanceDecline($data) {
        if (empty($data['performance'])) return 0;
        $trend = $data['performance']['score_trend'] ?? 0;
        if ($trend >= 0) return 0;
        return min(abs($trend) / 20, 1.0); // -20 이상이면 최대 점수
    }

    private function evaluateDropoutRisk($data) {
        $events = $data['dropout_events'] ?? [];
        $count = count($events);
        if ($count < 2) return 0;
        return min($count / 5, 1.0);
    }

    private function evaluateRepeatedErrors($data) {
        $patterns = $data['error_patterns'] ?? [];
        $maxRepeat = 0;
        foreach ($patterns as $pattern) {
            $maxRepeat = max($maxRepeat, $pattern['count'] ?? 0);
        }
        if ($maxRepeat < 3) return 0;
        return min(($maxRepeat - 2) / 5, 1.0);
    }

    private function evaluateRoutineStability($data) {
        $completion = $data['study_patterns']['pomodoro_completion'] ?? 100;
        if ($completion >= 50) return 0;
        return (50 - $completion) / 50;
    }

    private function evaluateTimeManagement($data) {
        $diff = $data['time_management']['plan_vs_actual_diff'] ?? 0;
        if ($diff <= 30) return 0;
        return min(($diff - 30) / 70, 1.0);
    }

    private function evaluateEmotionalState($data) {
        $logs = $data['emotion_logs'] ?? [];
        $negative = 0;
        foreach ($logs as $log) {
            if (in_array($log['emotion'] ?? '', ['frustration', 'boredom', 'anxiety', 'hopelessness'])) {
                $negative++;
            }
        }
        if ($negative < 3) return 0;
        return min($negative / 8, 1.0);
    }

    private function evaluateConceptUnderstanding($data) {
        $scores = $data['concept_test_scores'] ?? [];
        if (empty($scores)) return 0;

        $lowCount = 0;
        foreach ($scores as $score) {
            if ($score < 60) $lowCount++;
        }

        return $lowCount > 0 ? min($lowCount / count($scores), 1.0) : 0;
    }

    private function evaluateTeacherFeedback($data) {
        $feedback = $data['teacher_feedback'] ?? [];
        $warnings = 0;
        foreach ($feedback as $fb) {
            if (in_array($fb['type'] ?? '', ['concentration_low', 'basics_weak', 'behavior_concern'])) {
                $warnings++;
            }
        }
        return min($warnings / 3, 1.0);
    }

    private function evaluateStrategyAlignment($data) {
        $match = $data['strategy_data']['mode_vs_behavior_match'] ?? 100;
        if ($match >= 50) return 0;
        return (50 - $match) / 50;
    }

    private function evaluateRecoverySuccess($data) {
        $focus = $data['recovery_data']['post_break_focus'] ?? 100;
        if ($focus >= 50) return 0;
        return (50 - $focus) / 50;
    }

    /**
     * 평가 캐시 초기화
     */
    public function clearCache() {
        $this->evaluationCache = [];
    }
}
