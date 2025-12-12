<?php
/**
 * Agent17ConditionEvaluator - 조건 평가기 Fallback 구현체
 *
 * BaseConditionEvaluator가 없을 경우 사용되는 Agent17 전용 조건 평가기
 * 규칙의 조건을 컨텍스트와 비교하여 평가합니다.
 *
 * @package AugmentedTeacher\Agent17\PersonaEngine\Fallback
 * @version 1.0
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}

// 인터페이스 로드
$corePath = dirname(__DIR__, 4) . '/ontology_engineering/persona_engine/core/';
require_once($corePath . 'IConditionEvaluator.php');

use AugmentedTeacher\PersonaEngine\Core\IConditionEvaluator;

/**
 * Agent17 전용 조건 평가기 (BaseConditionEvaluator 없을 경우 사용)
 */
class Agent17ConditionEvaluator implements IConditionEvaluator {
    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 커스텀 연산자 핸들러 */
    protected $customOperators = [];

    /** @var array 기본 지원 연산자 */
    protected $defaultOperators = [
        '==', '===', '!=', '!==', '>', '>=', '<', '<=',
        'equals', 'gt', 'gte', 'lt', 'lte',
        'in', 'not_in', 'contains', 'starts_with', 'ends_with', 'regex', 'between'
    ];

    /**
     * 단일 조건 평가
     *
     * @param array $condition 조건 배열 ['field', 'operator', 'value']
     * @param array $context 컨텍스트 데이터
     * @return bool 조건 충족 여부
     */
    public function evaluate(array $condition, array $context): bool {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        $contextValue = $this->getNestedValue($context, $field);

        // 커스텀 연산자 확인
        if (isset($this->customOperators[$operator])) {
            return call_user_func($this->customOperators[$operator], $contextValue, $value);
        }

        switch ($operator) {
            case '==':
            case 'equals':
                return $contextValue == $value;
            case '===':
                return $contextValue === $value;
            case '!=':
                return $contextValue != $value;
            case '!==':
                return $contextValue !== $value;
            case '>':
            case 'gt':
                return $contextValue > $value;
            case '>=':
            case 'gte':
                return $contextValue >= $value;
            case '<':
            case 'lt':
                return $contextValue < $value;
            case '<=':
            case 'lte':
                return $contextValue <= $value;
            case 'in':
                return is_array($value) && in_array($contextValue, $value);
            case 'not_in':
                return is_array($value) && !in_array($contextValue, $value);
            case 'contains':
                return is_string($contextValue) && strpos($contextValue, $value) !== false;
            case 'starts_with':
                return is_string($contextValue) && strpos($contextValue, $value) === 0;
            case 'ends_with':
                return is_string($contextValue) && substr($contextValue, -strlen($value)) === $value;
            case 'regex':
                return is_string($contextValue) && preg_match($value, $contextValue);
            case 'between':
                return is_array($value) && count($value) >= 2 &&
                       $contextValue >= $value[0] && $contextValue <= $value[1];
            default:
                error_log("[Agent17ConditionEvaluator] {$this->currentFile}:" . __LINE__ .
                    " - 알 수 없는 연산자: {$operator}");
                return false;
        }
    }

    /**
     * 복수 조건 평가
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @param string $logic 논리 연산 (AND/OR)
     * @return bool 전체 조건 충족 여부
     */
    public function evaluateAll(array $conditions, array $context, string $logic = 'AND'): bool {
        if (empty($conditions)) {
            return true;
        }

        $logic = strtoupper($logic);

        foreach ($conditions as $condition) {
            // 중첩 조건 처리
            if (isset($condition['conditions'])) {
                $nestedLogic = $condition['logic'] ?? 'AND';
                $result = $this->evaluateAll($condition['conditions'], $context, $nestedLogic);
            } else {
                $result = $this->evaluate($condition, $context);
            }

            if ($logic === 'OR' && $result) {
                return true;
            }
            if ($logic === 'AND' && !$result) {
                return false;
            }
        }

        return $logic === 'AND';
    }

    /**
     * 커스텀 연산자 등록
     *
     * @param string $operator 연산자 이름
     * @param callable $handler 핸들러 함수
     */
    public function registerOperator(string $operator, callable $handler): void {
        $this->customOperators[$operator] = $handler;
    }

    /**
     * 지원 연산자 목록 반환
     *
     * @return array 연산자 배열
     */
    public function getSupportedOperators(): array {
        return array_merge($this->defaultOperators, array_keys($this->customOperators));
    }

    /**
     * 중첩 키 값 추출 (점 표기법 지원)
     *
     * @param array $context 컨텍스트 배열
     * @param string $field 필드명 (점 표기법 가능)
     * @return mixed 필드 값
     */
    protected function getNestedValue(array $context, string $field) {
        if (strpos($field, '.') === false) {
            return $context[$field] ?? null;
        }

        $keys = explode('.', $field);
        $value = $context;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}

/*
 * 관련 인터페이스: IConditionEvaluator
 * 위치: /ontology_engineering/persona_engine/core/IConditionEvaluator.php
 *
 * 메서드:
 * - evaluate(array $condition, array $context): bool
 * - evaluateAll(array $conditions, array $context, string $logic): bool
 * - registerOperator(string $operator, callable $handler): void
 * - getSupportedOperators(): array
 *
 * 지원 연산자:
 * - 비교: ==, ===, !=, !==, >, >=, <, <=, equals, gt, gte, lt, lte
 * - 집합: in, not_in, between
 * - 문자열: contains, starts_with, ends_with, regex
 */
