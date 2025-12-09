<?php
/**
 * BaseConditionEvaluator - 기본 조건 평가기 구현
 *
 * IConditionEvaluator 인터페이스의 기본 구현체
 * 30개 이상의 연산자 지원, AND/OR 중첩 조건 평가
 *
 * @package AugmentedTeacher\PersonaEngine\Impl
 * @version 1.0
 * @author Claude Code
 */

require_once(__DIR__ . '/../core/IConditionEvaluator.php');

class BaseConditionEvaluator implements IConditionEvaluator {

    /** @var array 연산자 핸들러 */
    private $operators = [];

    /** @var bool 디버그 모드 */
    private $debugMode = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 생성자 - 기본 연산자 등록
     */
    public function __construct(bool $debugMode = false) {
        $this->debugMode = $debugMode;
        $this->registerDefaultOperators();
    }

    /**
     * 기본 연산자 등록
     */
    private function registerDefaultOperators(): void {
        // 비교 연산자
        $this->operators['=='] = fn($a, $b) => $a == $b;
        $this->operators['==='] = fn($a, $b) => $a === $b;
        $this->operators['!='] = fn($a, $b) => $a != $b;
        $this->operators['!=='] = fn($a, $b) => $a !== $b;
        $this->operators['>'] = fn($a, $b) => $a > $b;
        $this->operators['<'] = fn($a, $b) => $a < $b;
        $this->operators['>='] = fn($a, $b) => $a >= $b;
        $this->operators['<='] = fn($a, $b) => $a <= $b;

        // 문자열 연산자
        $this->operators['contains'] = fn($a, $b) => strpos((string)$a, (string)$b) !== false;
        $this->operators['not_contains'] = fn($a, $b) => strpos((string)$a, (string)$b) === false;
        $this->operators['starts_with'] = fn($a, $b) => strpos((string)$a, (string)$b) === 0;
        $this->operators['ends_with'] = fn($a, $b) => substr((string)$a, -strlen((string)$b)) === (string)$b;
        $this->operators['regex'] = fn($a, $b) => preg_match($b, (string)$a) === 1;
        $this->operators['empty'] = fn($a, $b) => empty($a);
        $this->operators['not_empty'] = fn($a, $b) => !empty($a);
        $this->operators['length_gt'] = fn($a, $b) => mb_strlen((string)$a) > $b;
        $this->operators['length_lt'] = fn($a, $b) => mb_strlen((string)$a) < $b;

        // 범위 연산자
        $this->operators['in'] = fn($a, $b) => is_array($b) && in_array($a, $b);
        $this->operators['not_in'] = fn($a, $b) => is_array($b) && !in_array($a, $b);
        $this->operators['between'] = function($a, $b) {
            if (!is_array($b) || count($b) < 2) return false;
            return $a >= $b[0] && $a <= $b[1];
        };
        $this->operators['not_between'] = function($a, $b) {
            if (!is_array($b) || count($b) < 2) return false;
            return $a < $b[0] || $a > $b[1];
        };

        // 타입 연산자
        $this->operators['is_null'] = fn($a, $b) => is_null($a);
        $this->operators['is_not_null'] = fn($a, $b) => !is_null($a);
        $this->operators['is_array'] = fn($a, $b) => is_array($a);
        $this->operators['is_numeric'] = fn($a, $b) => is_numeric($a);

        // 배열 연산자
        $this->operators['array_contains'] = fn($a, $b) => is_array($a) && in_array($b, $a);
        $this->operators['array_has_key'] = fn($a, $b) => is_array($a) && array_key_exists($b, $a);
        $this->operators['array_size'] = fn($a, $b) => is_array($a) && count($a) == $b;
        $this->operators['array_size_gt'] = fn($a, $b) => is_array($a) && count($a) > $b;

        // 논리 연산자 (단일 필드용)
        $this->operators['true'] = fn($a, $b) => (bool)$a === true;
        $this->operators['false'] = fn($a, $b) => (bool)$a === false;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $condition, array $context): bool {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if ($field === null) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 조건에 field가 필요합니다"
            );
        }

        // 중첩 필드 지원 (점 표기법)
        $fieldValue = $this->getNestedValue($context, $field);

        // 연산자 확인
        if (!isset($this->operators[$operator])) {
            throw new \InvalidArgumentException(
                "[{$this->currentFile}:" . __LINE__ . "] 지원하지 않는 연산자: {$operator}"
            );
        }

        $result = $this->operators[$operator]($fieldValue, $value);

        if ($this->debugMode) {
            error_log("[BaseConditionEvaluator DEBUG] {$field} {$operator} " . 
                      json_encode($value) . " => " . ($result ? 'true' : 'false'));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function evaluateAll(array $conditions, array $context, string $logic = 'AND'): bool {
        if (empty($conditions)) {
            return true; // 조건이 없으면 true
        }

        $logic = strtoupper($logic);

        foreach ($conditions as $condition) {
            // 중첩 조건 확인
            if (isset($condition['AND'])) {
                $result = $this->evaluateAll($condition['AND'], $context, 'AND');
            } elseif (isset($condition['OR'])) {
                $result = $this->evaluateAll($condition['OR'], $context, 'OR');
            } else {
                $result = $this->evaluate($condition, $context);
            }

            // Short-circuit 평가
            if ($logic === 'AND' && !$result) {
                return false;
            }
            if ($logic === 'OR' && $result) {
                return true;
            }
        }

        return $logic === 'AND';
    }

    /**
     * @inheritDoc
     */
    public function registerOperator(string $operator, callable $handler): void {
        $this->operators[$operator] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedOperators(): array {
        return array_keys($this->operators);
    }

    /**
     * 중첩 필드 값 가져오기 (점 표기법)
     *
     * @param array $data 데이터 배열
     * @param string $field 필드명 (예: 'user.profile.name')
     * @return mixed 필드 값
     */
    private function getNestedValue(array $data, string $field) {
        $keys = explode('.', $field);
        $value = $data;

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
 * 관련 DB 테이블: 없음
 *
 * 참조 파일:
 * - core/IConditionEvaluator.php (인터페이스)
 * - agents/agent01_onboarding/persona_system/engine/ConditionEvaluator.php (원본)
 */
