<?php
/**
 * 룰 평가기
 * 룰 조건을 평가하고 매칭되는 룰 반환
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class RuleEvaluator {
    private $rules;
    
    public function __construct($rules) {
        $this->rules = $rules;
    }
    
    /**
     * 룰 평가
     * 
     * @param array $context 컨텍스트
     * @return array 매칭된 룰 목록
     */
    public function evaluate($context) {
        $matchedRules = [];
        
        // 우선순위 순으로 정렬
        usort($this->rules, function($a, $b) {
            return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
        });
        
        foreach ($this->rules as $rule) {
            if ($this->evaluateRule($rule, $context)) {
                $matchedRules[] = $rule;
            }
        }
        
        return $matchedRules;
    }
    
    /**
     * 단일 룰 평가
     */
    private function evaluateRule($rule, $context) {
        if (!isset($rule['conditions']) || empty($rule['conditions'])) {
            return false;
        }
        
        // 모든 조건이 충족되어야 함 (AND 로직)
        foreach ($rule['conditions'] as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 조건 평가
     */
    private function evaluateCondition($condition, $context) {
        // OR 조건 처리
        if (isset($condition['OR']) && is_array($condition['OR'])) {
            foreach ($condition['OR'] as $orCondition) {
                if ($this->evaluateCondition($orCondition, $context)) {
                    return true;
                }
            }
            return false;
        }
        
        // 일반 조건 처리
        if (!isset($condition['field'])) {
            return false;
        }
        
        $field = $condition['field'];
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;
        
        // 필드 값 가져오기 (중첩 필드 지원)
        $fieldValue = $this->getFieldValue($field, $context);
        
        // 연산자별 평가
        return $this->evaluateOperator($fieldValue, $operator, $value);
    }
    
    /**
     * 필드 값 가져오기 (중첩 필드 지원)
     */
    private function getFieldValue($field, $context) {
        // 점 표기법 지원 (예: "goals.long_term")
        if (strpos($field, '.') !== false) {
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
        
        return $context[$field] ?? null;
    }
    
    /**
     * 연산자 평가
     */
    private function evaluateOperator($fieldValue, $operator, $expectedValue) {
        switch ($operator) {
            case '==':
            case 'equal':
                return $fieldValue == $expectedValue;
            
            case '!=':
            case 'not_equal':
                return $fieldValue != $expectedValue;
            
            case '<':
            case 'less_than':
                return $fieldValue < $expectedValue;
            
            case '<=':
            case 'less_than_or_equal':
                return $fieldValue <= $expectedValue;
            
            case '>':
            case 'greater_than':
                return $fieldValue > $expectedValue;
            
            case '>=':
            case 'greater_than_or_equal':
                return $fieldValue >= $expectedValue;
            
            case 'in':
                if (!is_array($expectedValue)) {
                    return false;
                }
                return in_array($fieldValue, $expectedValue);
            
            case 'contains':
            case 'not_contains':
                $contains = strpos($fieldValue ?? '', $expectedValue ?? '') !== false;
                return $operator === 'contains' ? $contains : !$contains;
            
            case 'matches':
                return preg_match('/' . $expectedValue . '/u', $fieldValue ?? '') === 1;
            
            default:
                return false;
        }
    }
}

