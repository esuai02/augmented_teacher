<?php
/**
 * ConditionEvaluator - 조건 평가기 구현
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 */

require_once(__DIR__ . '/../core/IConditionEvaluator.php');

class ConditionEvaluator implements IConditionEvaluator {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 단일 조건 평가
     */
    public function evaluate(array $condition, array $context): bool {
        if (empty($condition)) {
            return true;
        }

        // OR 조건 처리
        if (isset($condition['or'])) {
            return $this->evaluateOr($condition['or'], $context);
        }

        // AND 조건 처리
        if (isset($condition['and'])) {
            return $this->evaluateAnd($condition['and'], $context);
        }

        // 단일 조건 평가 (field, operator, value)
        if (isset($condition['field']) && isset($condition['operator'])) {
            return $this->evaluateOperator(
                $condition['field'],
                $condition['operator'],
                $condition['value'] ?? null,
                $context
            );
        }

        // 간단 형식: [field => value] (동등 비교)
        foreach ($condition as $field => $expectedValue) {
            if (!is_array($expectedValue)) {
                if (!$this->evaluateOperator($field, '==', $expectedValue, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * OR 조건 평가
     */
    public function evaluateOr(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            if ($this->evaluate($condition, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * AND 조건 평가
     */
    public function evaluateAnd(array $conditions, array $context): bool {
        foreach ($conditions as $condition) {
            if (!$this->evaluate($condition, $context)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 연산자 평가
     */
    public function evaluateOperator(string $field, string $operator, $expected, array $context): bool {
        $actual = $this->getFieldValue($field, $context);

        switch ($operator) {
            case '==':
            case 'eq':
            case 'equals':
                return $actual == $expected;

            case '===':
            case 'strict_equals':
                return $actual === $expected;

            case '!=':
            case 'ne':
            case 'not_equals':
                return $actual != $expected;

            case '>':
            case 'gt':
                return is_numeric($actual) && is_numeric($expected) && $actual > $expected;

            case '>=':
            case 'gte':
                return is_numeric($actual) && is_numeric($expected) && $actual >= $expected;

            case '<':
            case 'lt':
                return is_numeric($actual) && is_numeric($expected) && $actual < $expected;

            case '<=':
            case 'lte':
                return is_numeric($actual) && is_numeric($expected) && $actual <= $expected;

            case 'contains':
                if (is_array($actual)) {
                    return in_array($expected, $actual);
                }
                return is_string($actual) && strpos($actual, $expected) !== false;

            case 'contains_any':
                if (!is_array($expected)) {
                    $expected = [$expected];
                }
                if (is_array($actual)) {
                    return !empty(array_intersect($actual, $expected));
                }
                foreach ($expected as $needle) {
                    if (is_string($actual) && strpos($actual, $needle) !== false) {
                        return true;
                    }
                }
                return false;

            case 'in':
                if (!is_array($expected)) {
                    $expected = [$expected];
                }
                return in_array($actual, $expected);

            case 'not_in':
                if (!is_array($expected)) {
                    $expected = [$expected];
                }
                return !in_array($actual, $expected);

            case 'regex':
            case 'matches':
                return is_string($actual) && @preg_match($expected, $actual) === 1;

            case 'exists':
                return $actual !== null;

            case 'not_exists':
                return $actual === null;

            case 'empty':
                return empty($actual);

            case 'not_empty':
                return !empty($actual);

            case 'between':
                if (!is_array($expected) || count($expected) !== 2) {
                    return false;
                }
                return is_numeric($actual) && $actual >= $expected[0] && $actual <= $expected[1];

            default:
                error_log("[ConditionEvaluator] 알 수 없는 연산자: {$operator} [{$this->currentFile}:" . __LINE__ . "]");
                return false;
        }
    }

    /**
     * 컨텍스트에서 필드 값 추출 (점 표기법 지원)
     *
     * @param string $field 필드명 (예: student.grade, session.emotion)
     * @param array $context 컨텍스트
     * @return mixed 필드 값
     */
    private function getFieldValue(string $field, array $context) {
        $keys = explode('.', $field);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }
}

/*
 * 지원 연산자 상세:
 * - == / eq / equals : 동등 비교 (타입 무관)
 * - === / strict_equals : 엄격 동등 비교
 * - != / ne / not_equals : 불일치
 * - > / gt : 초과
 * - >= / gte : 이상
 * - < / lt : 미만
 * - <= / lte : 이하
 * - contains : 부분 일치 (문자열/배열)
 * - contains_any : 배열 중 하나 포함
 * - in : 배열 내 포함
 * - not_in : 배열 내 미포함
 * - regex / matches : 정규식 매칭
 * - exists : 필드 존재 여부
 * - not_exists : 필드 미존재
 * - empty : 빈 값 여부
 * - not_empty : 빈 값 아님
 * - between : 범위 내 (배열 [min, max] 필요)
 *
 * 관련 DB 테이블:
 * - 직접적인 DB 연동 없음
 */
