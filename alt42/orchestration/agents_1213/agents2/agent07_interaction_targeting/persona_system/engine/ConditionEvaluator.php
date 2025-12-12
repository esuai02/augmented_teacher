<?php
/**
 * Condition Evaluator for Agent07 Persona System
 *
 * 규칙의 조건을 평가하여 매칭 여부와 신뢰도를 계산
 *
 * @version 1.0
 * @requires PHP 7.1.9+
 *
 * Related Files:
 * - rules.yaml: 조건 정의
 * - PersonaRuleEngine.php: 평가 결과 사용처
 * - DataContext.php: 평가에 사용되는 컨텍스트 데이터
 */

class ConditionEvaluator {

    /** @var array 설정값 */
    private $config;

    /**
     * 생성자
     *
     * @param array $config 설정 배열
     */
    public function __construct($config = array()) {
        $this->config = array_merge(array(
            'base_confidence' => 0.5,
            'condition_match_boost' => 0.1,
            'all_conditions_bonus' => 0.2,
            'keyword_match_boost' => 0.05,
            'min_confidence' => 0.3,
            'max_confidence' => 1.0
        ), $config);
    }

    /**
     * 조건 평가 메인 메서드
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return array 평가 결과
     */
    public function evaluate($conditions, $context) {
        if (empty($conditions)) {
            return array(
                'matched' => true,
                'confidence' => $this->config['base_confidence'],
                'matched_conditions' => array()
            );
        }

        $matchedConditions = array();
        $confidence = $this->config['base_confidence'];
        $allMatched = true;
        $anyMatched = false;
        $noneMatched = true;

        // 'all' 조건 평가
        if (isset($conditions['all'])) {
            $allResult = $this->evaluateAllConditions($conditions['all'], $context);
            $allMatched = $allResult['all_matched'];
            $matchedConditions = array_merge($matchedConditions, $allResult['matched']);

            if ($allMatched) {
                $confidence += $this->config['all_conditions_bonus'];
            }
        }

        // 'any' 조건 평가
        if (isset($conditions['any'])) {
            $anyResult = $this->evaluateAnyConditions($conditions['any'], $context);
            $anyMatched = $anyResult['any_matched'];
            $matchedConditions = array_merge($matchedConditions, $anyResult['matched']);

            $confidence += count($anyResult['matched']) * $this->config['condition_match_boost'];
        }

        // 'none' 조건 평가 (이 조건들이 모두 false여야 함)
        if (isset($conditions['none'])) {
            $noneResult = $this->evaluateNoneConditions($conditions['none'], $context);
            $noneMatched = $noneResult['none_matched'];

            if (!$noneMatched) {
                // none 조건 중 하나라도 true면 매칭 실패
                return array(
                    'matched' => false,
                    'confidence' => 0,
                    'matched_conditions' => array(),
                    'failed_reason' => 'none_condition_violated'
                );
            }
        }

        // 최종 매칭 판정
        $matched = $allMatched && (empty($conditions['any']) || $anyMatched);

        // 신뢰도 범위 제한
        $confidence = max(
            $this->config['min_confidence'],
            min($this->config['max_confidence'], $confidence)
        );

        return array(
            'matched' => $matched,
            'confidence' => round($confidence, 2),
            'matched_conditions' => $matchedConditions
        );
    }

    /**
     * 'all' 조건 평가 (모든 조건이 참이어야 함)
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateAllConditions($conditions, $context) {
        $matched = array();
        $allMatched = true;

        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition, $context);
            if ($result['matched']) {
                $matched[] = $condition;
            } else {
                $allMatched = false;
            }
        }

        return array(
            'all_matched' => $allMatched,
            'matched' => $matched
        );
    }

    /**
     * 'any' 조건 평가 (하나라도 참이면 됨)
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateAnyConditions($conditions, $context) {
        $matched = array();
        $anyMatched = false;

        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition, $context);
            if ($result['matched']) {
                $matched[] = $condition;
                $anyMatched = true;
            }
        }

        return array(
            'any_matched' => $anyMatched,
            'matched' => $matched
        );
    }

    /**
     * 'none' 조건 평가 (모든 조건이 거짓이어야 함)
     *
     * @param array $conditions 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateNoneConditions($conditions, $context) {
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition, $context);
            if ($result['matched']) {
                return array('none_matched' => false);
            }
        }

        return array('none_matched' => true);
    }

    /**
     * 단일 조건 평가
     *
     * @param mixed $condition 조건 (문자열 또는 배열)
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateSingleCondition($condition, $context) {
        // 문자열 조건 파싱
        if (is_string($condition)) {
            return $this->evaluateStringCondition($condition, $context);
        }

        // 배열 조건 (키워드 검사 등)
        if (is_array($condition)) {
            return $this->evaluateArrayCondition($condition, $context);
        }

        return array('matched' => false);
    }

    /**
     * 문자열 조건 평가
     *
     * @param string $condition 조건 문자열
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateStringCondition($condition, $context) {
        // context.field == value
        if (preg_match('/^context\.(\w+)\s*==\s*(.+)$/', $condition, $matches)) {
            $field = $matches[1];
            $expectedValue = $this->parseConditionValue($matches[2]);
            $actualValue = isset($context[$field]) ? $context[$field] : null;

            return array(
                'matched' => $actualValue === $expectedValue,
                'field' => $field,
                'expected' => $expectedValue,
                'actual' => $actualValue
            );
        }

        // context.field != value
        if (preg_match('/^context\.(\w+)\s*!=\s*(.+)$/', $condition, $matches)) {
            $field = $matches[1];
            $expectedValue = $this->parseConditionValue($matches[2]);
            $actualValue = isset($context[$field]) ? $context[$field] : null;

            return array(
                'matched' => $actualValue !== $expectedValue,
                'field' => $field
            );
        }

        // context.field > value
        if (preg_match('/^context\.(\w+)\s*>\s*(.+)$/', $condition, $matches)) {
            $field = $matches[1];
            $threshold = (float)$matches[2];
            $actualValue = isset($context[$field]) ? (float)$context[$field] : 0;

            return array(
                'matched' => $actualValue > $threshold,
                'field' => $field,
                'threshold' => $threshold,
                'actual' => $actualValue
            );
        }

        // context.field >= value
        if (preg_match('/^context\.(\w+)\s*>=\s*(.+)$/', $condition, $matches)) {
            $field = $matches[1];
            $threshold = (float)$matches[2];
            $actualValue = isset($context[$field]) ? (float)$context[$field] : 0;

            return array(
                'matched' => $actualValue >= $threshold,
                'field' => $field
            );
        }

        // context.field < value
        if (preg_match('/^context\.(\w+)\s*<\s*(.+)$/', $condition, $matches)) {
            $field = $matches[1];
            $threshold = (float)$matches[2];
            $actualValue = isset($context[$field]) ? (float)$context[$field] : 0;

            return array(
                'matched' => $actualValue < $threshold,
                'field' => $field
            );
        }

        // context.field (boolean check - truthy)
        if (preg_match('/^context\.(\w+)$/', $condition, $matches)) {
            $field = $matches[1];
            $actualValue = isset($context[$field]) ? $context[$field] : false;

            return array(
                'matched' => (bool)$actualValue,
                'field' => $field
            );
        }

        return array('matched' => false, 'reason' => 'unknown_condition_format');
    }

    /**
     * 배열 조건 평가 (키워드 검사 등)
     *
     * @param array $condition 조건 배열
     * @param array $context 컨텍스트 데이터
     * @return array
     */
    private function evaluateArrayCondition($condition, $context) {
        // context.message_contains_keywords: ["키워드1", "키워드2"]
        if (isset($condition['context.message_contains_keywords'])) {
            $keywords = $condition['context.message_contains_keywords'];
            $message = isset($context['message']) ? $context['message'] : '';

            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword) !== false) {
                    return array(
                        'matched' => true,
                        'matched_keyword' => $keyword
                    );
                }
            }
            return array('matched' => false);
        }

        return array('matched' => false);
    }

    /**
     * 조건값 파싱
     *
     * @param string $value 원본 값
     * @return mixed
     */
    private function parseConditionValue($value) {
        $value = trim($value);

        // 따옴표 제거
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
            return $matches[1];
        }

        // 불리언
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;

        // 숫자
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * 설정 업데이트
     *
     * @param array $config 새 설정
     */
    public function setConfig($config) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 현재 설정 반환
     *
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }
}
