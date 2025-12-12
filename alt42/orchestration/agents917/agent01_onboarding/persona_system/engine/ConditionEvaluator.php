<?php
/**
 * ConditionEvaluator - 규칙 조건 평가기
 *
 * 규칙의 조건을 학생 컨텍스트와 비교하여 평가합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

class ConditionEvaluator {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 배열
     * @param array $context 학생 컨텍스트
     * @return bool 조건 충족 여부
     */
    public function evaluate(array $condition, array $context): bool {
        if (!isset($condition['field']) || !isset($condition['operator'])) {
            return false;
        }

        $field = $condition['field'];
        $operator = $condition['operator'];
        $expectedValue = $condition['value'] ?? null;

        // 컨텍스트에서 필드 값 가져오기
        $actualValue = $this->getFieldValue($context, $field);

        // 연산자별 평가
        return $this->compareValues($actualValue, $operator, $expectedValue);
    }

    /**
     * OR 조건 평가 (단락 평가)
     *
     * @param array $conditions 조건 배열
     * @param array $context 학생 컨텍스트
     * @return bool 하나라도 true면 true
     */
    public function evaluateOr(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            // 중첩된 AND
            if (isset($condition['AND'])) {
                if ($this->evaluateAnd($condition['AND'], $context)) {
                    return true;
                }
            }
            // 단일 조건
            elseif ($this->evaluate($condition, $context)) {
                return true; // 단락 평가: 첫 번째 true에서 중단
            }
        }
        return false;
    }

    /**
     * AND 조건 평가 (단락 평가)
     *
     * @param array $conditions 조건 배열
     * @param array $context 학생 컨텍스트
     * @return bool 모두 true여야 true
     */
    public function evaluateAnd(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            // 중첩된 OR
            if (isset($condition['OR'])) {
                if (!$this->evaluateOr($condition['OR'], $context)) {
                    return false;
                }
            }
            // 단일 조건
            elseif (!$this->evaluate($condition, $context)) {
                return false; // 단락 평가: 첫 번째 false에서 중단
            }
        }
        return true;
    }

    /**
     * 컨텍스트에서 필드 값 가져오기
     *
     * @param array $context 컨텍스트
     * @param string $field 필드명 (점 표기법 지원)
     * @return mixed 필드 값
     */
    private function getFieldValue(array $context, string $field) {
        // 점 표기법 지원 (예: user.profile.name)
        $keys = explode('.', $field);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 값 비교
     *
     * @param mixed $actual 실제 값
     * @param string $operator 연산자
     * @param mixed $expected 기대 값
     * @return bool 비교 결과
     */
    private function compareValues($actual, string $operator, $expected): bool {
        switch ($operator) {
            // 동등 비교
            case '==':
            case '=':
            case 'equals':
                return $actual == $expected;

            case '===':
            case 'strict_equals':
                return $actual === $expected;

            // 불일치
            case '!=':
            case '<>':
            case 'not_equals':
                return $actual != $expected;

            case '!==':
            case 'strict_not_equals':
                return $actual !== $expected;

            // 대소 비교
            case '>':
            case 'gt':
                return $actual > $expected;

            case '>=':
            case 'gte':
                return $actual >= $expected;

            case '<':
            case 'lt':
                return $actual < $expected;

            case '<=':
            case 'lte':
                return $actual <= $expected;

            // 문자열 포함
            case 'contains':
                if (is_string($actual) && is_string($expected)) {
                    return mb_strpos($actual, $expected) !== false;
                }
                return false;

            // 배열 중 하나 포함
            case 'contains_any':
                if (is_string($actual) && is_array($expected)) {
                    foreach ($expected as $keyword) {
                        if (mb_strpos($actual, $keyword) !== false) {
                            return true;
                        }
                    }
                }
                return false;

            // 배열 내 모두 포함
            case 'contains_all':
                if (is_string($actual) && is_array($expected)) {
                    foreach ($expected as $keyword) {
                        if (mb_strpos($actual, $keyword) === false) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;

            // 문자열 시작
            case 'starts_with':
                if (is_string($actual) && is_string($expected)) {
                    return mb_strpos($actual, $expected) === 0;
                }
                return false;

            // 문자열 끝
            case 'ends_with':
                if (is_string($actual) && is_string($expected)) {
                    $length = mb_strlen($expected);
                    return mb_substr($actual, -$length) === $expected;
                }
                return false;

            // 배열 내 포함
            case 'in':
                if (is_array($expected)) {
                    return in_array($actual, $expected);
                }
                return false;

            // 배열 내 미포함
            case 'not_in':
                if (is_array($expected)) {
                    return !in_array($actual, $expected);
                }
                return true;

            // 정규식 매칭
            case 'regex':
            case 'matches':
                if (is_string($actual) && is_string($expected)) {
                    return preg_match($expected, $actual) === 1;
                }
                return false;

            // 빈 값 체크
            case 'is_empty':
                return empty($actual);

            case 'is_not_empty':
                return !empty($actual);

            // null 체크
            case 'is_null':
                return $actual === null;

            case 'is_not_null':
                return $actual !== null;

            // 타입 체크
            case 'is_string':
                return is_string($actual);

            case 'is_numeric':
                return is_numeric($actual);

            case 'is_array':
                return is_array($actual);

            case 'is_bool':
                return is_bool($actual);

            // 범위 체크
            case 'between':
                if (is_array($expected) && count($expected) >= 2) {
                    return $actual >= $expected[0] && $actual <= $expected[1];
                }
                return false;

            // 길이 체크
            case 'length_equals':
                return $this->getLength($actual) == $expected;

            case 'length_gt':
                return $this->getLength($actual) > $expected;

            case 'length_lt':
                return $this->getLength($actual) < $expected;

            default:
                error_log("[ConditionEvaluator] {$this->currentFile}:" . __LINE__ . " - 알 수 없는 연산자: {$operator}");
                return false;
        }
    }

    /**
     * 값의 길이 반환
     *
     * @param mixed $value 값
     * @return int 길이
     */
    private function getLength($value): int {
        if (is_string($value)) {
            return mb_strlen($value);
        }
        if (is_array($value)) {
            return count($value);
        }
        return 0;
    }
}

/*
 * 지원 연산자:
 * - 동등: ==, ===, !=, !==
 * - 대소: >, >=, <, <=
 * - 문자열: contains, contains_any, contains_all, starts_with, ends_with
 * - 배열: in, not_in
 * - 정규식: regex, matches
 * - 체크: is_empty, is_not_empty, is_null, is_not_null
 * - 타입: is_string, is_numeric, is_array, is_bool
 * - 범위: between
 * - 길이: length_equals, length_gt, length_lt
 */
