<?php
/**
 * BaseConditionEvaluator - 조건 평가 기본 구현체
 *
 * 규칙의 조건을 평가하는 기본 구현체입니다.
 * 기본 연산자와 커스텀 연산자 등록을 지원합니다.
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @since 2025-12-03
 */

namespace AugmentedTeacher\PersonaEngine\Impl;

use AugmentedTeacher\PersonaEngine\Core\IConditionEvaluator;

class BaseConditionEvaluator implements IConditionEvaluator {

    /** @var array 커스텀 연산자 핸들러 */
    protected $customOperators = [];

    /** @var string 현재 파일 경로 (디버깅용) */
    protected $currentFile = __FILE__;

    /**
     * 생성자
     */
    public function __construct() {
        $this->registerDefaultOperators();
    }

    /**
     * 기본 연산자 등록
     */
    protected function registerDefaultOperators(): void {
        // contains_any: 배열 중 하나라도 포함되면 true
        $this->registerOperator('contains_any', function($fieldValue, $conditionValue) {
            if (!is_array($conditionValue)) {
                $conditionValue = [$conditionValue];
            }
            foreach ($conditionValue as $item) {
                if (stripos((string)$fieldValue, (string)$item) !== false) {
                    return true;
                }
            }
            return false;
        });

        // contains_all: 모든 값이 포함되어야 true
        $this->registerOperator('contains_all', function($fieldValue, $conditionValue) {
            if (!is_array($conditionValue)) {
                $conditionValue = [$conditionValue];
            }
            foreach ($conditionValue as $item) {
                if (stripos((string)$fieldValue, (string)$item) === false) {
                    return false;
                }
            }
            return true;
        });

        // in: 값이 배열에 포함되어 있으면 true
        $this->registerOperator('in', function($fieldValue, $conditionValue) {
            if (!is_array($conditionValue)) {
                return false;
            }
            return in_array($fieldValue, $conditionValue);
        });

        // not_in: 값이 배열에 포함되어 있지 않으면 true
        $this->registerOperator('not_in', function($fieldValue, $conditionValue) {
            if (!is_array($conditionValue)) {
                return true;
            }
            return !in_array($fieldValue, $conditionValue);
        });

        // regex: 정규식 매칭
        $this->registerOperator('regex', function($fieldValue, $conditionValue) {
            return preg_match($conditionValue, (string)$fieldValue) === 1;
        });

        // between: 범위 내에 있으면 true
        $this->registerOperator('between', function($fieldValue, $conditionValue) {
            if (!is_array($conditionValue) || count($conditionValue) < 2) {
                return false;
            }
            $min = $conditionValue[0];
            $max = $conditionValue[1];
            return $fieldValue >= $min && $fieldValue <= $max;
        });

        // empty: 비어있으면 true
        $this->registerOperator('empty', function($fieldValue, $conditionValue) {
            $isEmpty = empty($fieldValue);
            return $conditionValue ? $isEmpty : !$isEmpty;
        });

        // exists: 존재하면 true (null이 아니면)
        $this->registerOperator('exists', function($fieldValue, $conditionValue) {
            $exists = ($fieldValue !== null);
            return $conditionValue ? $exists : !$exists;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $condition, array $context): bool {
        // 필수 필드 검증
        if (!isset($condition['field']) || !isset($condition['operator'])) {
            $this->logWarning("조건에 field 또는 operator가 없음", __LINE__);
            return false;
        }

        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'] ?? null;

        // 컨텍스트에서 필드 값 추출 (점 표기법 지원)
        $fieldValue = $this->getFieldValue($context, $field);

        // 연산자별 평가
        return $this->evaluateOperator($operator, $fieldValue, $value);
    }

    /**
     * 필드 값 추출 (점 표기법 지원)
     *
     * @param array $context 컨텍스트
     * @param string $field 필드 경로 (예: 'user.profile.name')
     * @return mixed 필드 값
     */
    protected function getFieldValue(array $context, string $field) {
        $parts = explode('.', $field);
        $value = $context;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 연산자 평가
     *
     * @param string $operator 연산자
     * @param mixed $fieldValue 필드 값
     * @param mixed $conditionValue 조건 값
     * @return bool 평가 결과
     */
    protected function evaluateOperator(string $operator, $fieldValue, $conditionValue): bool {
        // 커스텀 연산자 확인
        if (isset($this->customOperators[$operator])) {
            return call_user_func($this->customOperators[$operator], $fieldValue, $conditionValue);
        }

        // 기본 연산자
        switch ($operator) {
            case '==':
            case 'eq':
            case 'equals':
                return $fieldValue == $conditionValue;

            case '===':
            case 'strict_equals':
                return $fieldValue === $conditionValue;

            case '!=':
            case 'ne':
            case 'not_equals':
                return $fieldValue != $conditionValue;

            case '!==':
            case 'strict_not_equals':
                return $fieldValue !== $conditionValue;

            case '>':
            case 'gt':
                return $fieldValue > $conditionValue;

            case '>=':
            case 'gte':
                return $fieldValue >= $conditionValue;

            case '<':
            case 'lt':
                return $fieldValue < $conditionValue;

            case '<=':
            case 'lte':
                return $fieldValue <= $conditionValue;

            case 'contains':
                return stripos((string)$fieldValue, (string)$conditionValue) !== false;

            case 'starts_with':
                return strpos((string)$fieldValue, (string)$conditionValue) === 0;

            case 'ends_with':
                $len = strlen((string)$conditionValue);
                return substr((string)$fieldValue, -$len) === (string)$conditionValue;

            default:
                $this->logWarning("알 수 없는 연산자: {$operator}", __LINE__);
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateOr(array $conditions, array $context): bool {
        if (empty($conditions)) {
            return true; // 빈 OR 조건은 true
        }

        foreach ($conditions as $condition) {
            if ($this->evaluate($condition, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateAnd(array $conditions, array $context): bool {
        if (empty($conditions)) {
            return true; // 빈 AND 조건은 true
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluate($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function registerOperator(string $operator, callable $handler): void {
        $this->customOperators[$operator] = $handler;
    }

    /**
     * 등록된 연산자 목록 조회
     *
     * @return array 연산자 이름 목록
     */
    public function getRegisteredOperators(): array {
        $builtin = [
            '==', '===', '!=', '!==', '>', '>=', '<', '<=',
            'eq', 'ne', 'gt', 'gte', 'lt', 'lte',
            'contains', 'starts_with', 'ends_with'
        ];
        return array_merge($builtin, array_keys($this->customOperators));
    }

    /**
     * 경고 로깅
     */
    protected function logWarning(string $message, int $line): void {
        error_log("[ConditionEvaluator WARN] {$this->currentFile}:{$line} - {$message}");
    }
}

/*
 * 사용 예시:
 *
 * $evaluator = new BaseConditionEvaluator();
 *
 * // 단일 조건 평가
 * $condition = ['field' => 'ninactive', 'operator' => '>=', 'value' => 4];
 * $context = ['ninactive' => 5, 'npomodoro' => 2];
 * $result = $evaluator->evaluate($condition, $context); // true
 *
 * // OR 조건 평가
 * $orConditions = [
 *     ['field' => 'ninactive', 'operator' => '>=', 'value' => 4],
 *     ['field' => 'npomodoro', 'operator' => '<=', 'value' => 1]
 * ];
 * $result = $evaluator->evaluateOr($orConditions, $context); // true
 *
 * // 커스텀 연산자 등록
 * $evaluator->registerOperator('is_weekend', function($value, $expected) {
 *     $dayOfWeek = date('N', strtotime($value));
 *     return $expected ? ($dayOfWeek >= 6) : ($dayOfWeek < 6);
 * });
 *
 * 지원 연산자:
 * - 비교: ==, ===, !=, !==, >, >=, <, <=
 * - 문자열: contains, starts_with, ends_with, regex
 * - 배열: in, not_in, contains_any, contains_all
 * - 범위: between
 * - 존재: empty, exists
 *
 * 파일 위치: ontology_engineering/persona_engine/impl/BaseConditionEvaluator.php
 */
