<?php
/**
 * Agent18 Signature Routine - Condition Evaluator
 *
 * 규칙의 조건을 평가하여 매칭 여부를 결정.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/ConditionEvaluator.php
 */

class ConditionEvaluator {

    /** @var DataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var array 지원하는 연산자 목록 */
    private $operators = ['==', '!=', '>', '>=', '<', '<=', 'in', 'contains', 'contains_any', 'is_empty'];

    /**
     * 생성자
     *
     * @param DataContext $dataContext 데이터 컨텍스트
     */
    public function __construct(DataContext $dataContext) {
        $this->dataContext = $dataContext;
    }

    /**
     * 조건 목록 평가
     *
     * @param array $conditions 조건 목록
     * @return array 평가 결과 ['matched' => bool, 'score' => float, 'matched_conditions' => array]
     */
    public function evaluate($conditions) {
        if (empty($conditions)) {
            return [
                'matched' => false,
                'score' => 0,
                'matched_conditions' => []
            ];
        }

        $matchedConditions = [];
        $totalScore = 0;
        $conditionCount = count($conditions);

        foreach ($conditions as $condition) {
            $result = $this->evaluateCondition($condition);

            if ($result['matched']) {
                $matchedConditions[] = [
                    'condition' => $condition,
                    'score' => $result['score'],
                    'actual_value' => $result['actual_value']
                ];
                $totalScore += $result['score'];
            }
        }

        // 모든 조건이 만족되어야 규칙 매칭
        $allMatched = count($matchedConditions) === $conditionCount;
        $averageScore = $conditionCount > 0 ? $totalScore / $conditionCount : 0;

        return [
            'matched' => $allMatched,
            'score' => $averageScore,
            'matched_conditions' => $matchedConditions
        ];
    }

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 ['field' => string, 'operator' => string, 'value' => mixed]
     * @return array 평가 결과 ['matched' => bool, 'score' => float, 'actual_value' => mixed]
     */
    public function evaluateCondition($condition) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $expectedValue = $condition['value'] ?? null;

        // 데이터 컨텍스트에서 실제 값 가져오기
        $actualValue = $this->getFieldValue($field);

        // 연산자별 평가
        $matched = $this->compare($actualValue, $operator, $expectedValue);

        // 매칭 점수 계산
        $score = $matched ? $this->calculateScore($actualValue, $operator, $expectedValue) : 0;

        return [
            'matched' => $matched,
            'score' => $score,
            'actual_value' => $actualValue
        ];
    }

    /**
     * 필드 값 가져오기
     *
     * @param string $field 필드명 (점 표기법 지원: user.profile.name)
     * @return mixed 필드 값
     */
    private function getFieldValue($field) {
        // 점 표기법 처리
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $value = $this->dataContext->getField($parts[0]);

            for ($i = 1; $i < count($parts); $i++) {
                if (is_array($value) && isset($value[$parts[$i]])) {
                    $value = $value[$parts[$i]];
                } elseif (is_object($value) && isset($value->{$parts[$i]})) {
                    $value = $value->{$parts[$i]};
                } else {
                    return null;
                }
            }
            return $value;
        }

        return $this->dataContext->getField($field);
    }

    /**
     * 비교 연산 수행
     *
     * @param mixed $actual 실제 값
     * @param string $operator 연산자
     * @param mixed $expected 기대 값
     * @return bool 비교 결과
     */
    private function compare($actual, $operator, $expected) {
        try {
            switch ($operator) {
                case '==':
                    return $actual == $expected;

                case '!=':
                    return $actual != $expected;

                case '>':
                    return is_numeric($actual) && is_numeric($expected) && $actual > $expected;

                case '>=':
                    return is_numeric($actual) && is_numeric($expected) && $actual >= $expected;

                case '<':
                    return is_numeric($actual) && is_numeric($expected) && $actual < $expected;

                case '<=':
                    return is_numeric($actual) && is_numeric($expected) && $actual <= $expected;

                case 'in':
                    return is_array($expected) && in_array($actual, $expected);

                case 'contains':
                    if (is_string($actual) && is_string($expected)) {
                        return strpos($actual, $expected) !== false;
                    }
                    if (is_array($actual)) {
                        return in_array($expected, $actual);
                    }
                    return false;

                case 'contains_any':
                    if (!is_array($expected)) {
                        return false;
                    }
                    if (is_string($actual)) {
                        foreach ($expected as $term) {
                            if (strpos($actual, $term) !== false) {
                                return true;
                            }
                        }
                    }
                    if (is_array($actual)) {
                        return count(array_intersect($actual, $expected)) > 0;
                    }
                    return false;

                case 'is_empty':
                    $isEmpty = empty($actual);
                    return $expected ? $isEmpty : !$isEmpty;

                default:
                    error_log("[Agent18 ConditionEvaluator] 알 수 없는 연산자: {$operator} at " .
                              __FILE__ . ":" . __LINE__);
                    return false;
            }
        } catch (Exception $e) {
            error_log("[Agent18 ConditionEvaluator] 비교 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }

    /**
     * 매칭 점수 계산
     *
     * @param mixed $actual 실제 값
     * @param string $operator 연산자
     * @param mixed $expected 기대 값
     * @return float 점수 (0.0 ~ 1.0)
     */
    private function calculateScore($actual, $operator, $expected) {
        switch ($operator) {
            case '==':
                return 1.0;

            case '!=':
                return 0.8;

            case '>':
            case '>=':
                // 기대값 대비 초과 비율로 점수 계산
                if (is_numeric($actual) && is_numeric($expected) && $expected > 0) {
                    $ratio = $actual / $expected;
                    return min(1.0, 0.5 + ($ratio - 1) * 0.5);
                }
                return 0.7;

            case '<':
            case '<=':
                return 0.7;

            case 'in':
                return 0.8;

            case 'contains':
                return 0.7;

            case 'contains_any':
                // 매칭된 항목 수에 따라 점수 조정
                if (is_array($expected) && is_string($actual)) {
                    $matchCount = 0;
                    foreach ($expected as $term) {
                        if (strpos($actual, $term) !== false) {
                            $matchCount++;
                        }
                    }
                    return min(1.0, 0.5 + ($matchCount / count($expected)) * 0.5);
                }
                return 0.6;

            case 'is_empty':
                return 0.5;

            default:
                return 0.5;
        }
    }

    /**
     * 메시지 기반 키워드 매칭
     *
     * @param string $message 사용자 메시지
     * @param array $keywords 키워드 목록
     * @return array ['matched' => bool, 'matched_keywords' => array, 'score' => float]
     */
    public function matchKeywords($message, $keywords) {
        $message = mb_strtolower($message, 'UTF-8');
        $matchedKeywords = [];

        foreach ($keywords as $keyword) {
            if (mb_strpos($message, mb_strtolower($keyword, 'UTF-8')) !== false) {
                $matchedKeywords[] = $keyword;
            }
        }

        $score = count($keywords) > 0 ? count($matchedKeywords) / count($keywords) : 0;

        return [
            'matched' => count($matchedKeywords) > 0,
            'matched_keywords' => $matchedKeywords,
            'score' => $score
        ];
    }

    /**
     * 숫자 범위 체크
     *
     * @param float $value 값
     * @param float $min 최소값
     * @param float $max 최대값
     * @return bool 범위 내 여부
     */
    public function inRange($value, $min, $max) {
        return is_numeric($value) && $value >= $min && $value <= $max;
    }

    /**
     * 시간대 체크
     *
     * @param int $timestamp 타임스탬프
     * @param int $startHour 시작 시간 (0-23)
     * @param int $endHour 종료 시간 (0-23)
     * @return bool 시간대 내 여부
     */
    public function inTimeRange($timestamp, $startHour, $endHour) {
        $hour = (int)date('H', $timestamp);

        if ($startHour <= $endHour) {
            return $hour >= $startHour && $hour < $endHour;
        } else {
            // 야간 시간대 (예: 22-6)
            return $hour >= $startHour || $hour < $endHour;
        }
    }

    /**
     * 복합 조건 평가 (OR 논리)
     *
     * @param array $conditionGroups 조건 그룹 목록
     * @return array 평가 결과
     */
    public function evaluateOr($conditionGroups) {
        foreach ($conditionGroups as $conditions) {
            $result = $this->evaluate($conditions);
            if ($result['matched']) {
                return $result;
            }
        }

        return [
            'matched' => false,
            'score' => 0,
            'matched_conditions' => []
        ];
    }
}
