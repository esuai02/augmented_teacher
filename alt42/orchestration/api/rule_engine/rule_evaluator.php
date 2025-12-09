<?php
/**
 * Rule Evaluator
 * 룰 평가 엔진 인터페이스
 * 시나리오 그룹의 룰들을 평가하고 매칭된 룰 반환
 * 
 * @package ALT42\RuleEngine
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

namespace ALT42\RuleEngine;

require_once(__DIR__ . '/../database/agent_data_layer.php');
use ALT42\Database\AgentDataLayer;

class RuleEvaluator {
    private $dataLayer;
    private $ruleCache = array();
    
    public function __construct() {
        $this->dataLayer = new AgentDataLayer();
    }
    
    /**
     * Evaluate scenario group rules
     * 시나리오 그룹의 룰들을 평가
     * 
     * @param string $scenarioId Scenario ID (e.g., 'S1')
     * @param array $ruleIds Rule IDs to evaluate
     * @param array $context Student context/state
     * @param string $evaluationMode Evaluation mode ('priority_first' or 'all_matching')
     * @return array Evaluation results
     */
    public function evaluateScenario(
        $scenarioId,
        $ruleIds,
        $context,
        $evaluationMode = 'priority_first'
    ) {
        $startTime = microtime(true);
        $matchedRules = array();
        
        foreach ($ruleIds as $ruleId) {
            try {
                $rule = $this->getRule($ruleId);
                if (!$rule) {
                    continue; // Rule not found
                }
                
                $isMatched = $this->evaluateRule($rule, $context);
                
                if ($isMatched) {
                    $matchedRules[] = array(
                        'rule_id' => $ruleId,
                        'priority' => isset($rule['priority']) ? $rule['priority'] : 0,
                        'action' => isset($rule['action']) ? $rule['action'] : array(),
                        'confidence' => isset($rule['confidence']) ? $rule['confidence'] : 0.5,
                        'rationale' => isset($rule['rationale']) ? $rule['rationale'] : ''
                    );
                    
                    // priority_first 모드: 첫 매칭 룰에서 중단
                    if ($evaluationMode === 'priority_first') {
                        break;
                    }
                }
            } catch (\Exception $e) {
                error_log("Rule evaluation error for {$ruleId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
                continue;
            }
        }
        
        // 우선순위 정렬 (all_matching 모드)
        if ($evaluationMode === 'all_matching' && count($matchedRules) > 1) {
            usort($matchedRules, function($a, $b) {
                $priorityB = isset($b['priority']) ? $b['priority'] : 0;
                $priorityA = isset($a['priority']) ? $a['priority'] : 0;
                return $priorityB - $priorityA;
            });
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return array(
            'scenario_id' => $scenarioId,
            'rules_evaluated' => count($ruleIds),
            'rules_matched' => count($matchedRules),
            'matched_rules' => $matchedRules,
            'evaluation_mode' => $evaluationMode,
            'duration_ms' => $duration
        );
    }
    
    /**
     * Evaluate a single rule
     * 단일 룰 평가
     * 
     * @param array $rule Rule definition
     * @param array $context Student context
     * @return bool True if rule matches
     */
    public function evaluateRule($rule, $context) {
        $conditions = isset($rule['conditions']) ? $rule['conditions'] : array();
        
        if (empty($conditions)) {
            return true; // No conditions = always match
        }
        
        // 모든 조건이 만족되어야 함 (AND 로직)
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Evaluate a single condition
     * 단일 조건 평가
     * 
     * @param array $condition Condition definition
     * @param array $context Student context
     * @return bool True if condition matches
     */
    private function evaluateCondition($condition, $context) {
        $field = isset($condition['field']) ? $condition['field'] : null;
        $operator = isset($condition['operator']) ? $condition['operator'] : '==';
        $value = isset($condition['value']) ? $condition['value'] : null;
        
        if ($field === null) {
            return false;
        }
        
        // 중첩 필드 접근 지원 (예: 'goals.long_term')
        $contextValue = $this->getNestedValue($context, $field);
        
        // NULL 체크
        if ($contextValue === null && $value !== null) {
            return false;
        }
        
        // Operator별 평가
        switch ($operator) {
            case '==':
            case '=':
                return $contextValue == $value;
                
            case '!=':
            case '<>':
                return $contextValue != $value;
                
            case '<':
                return is_numeric($contextValue) && is_numeric($value) && $contextValue < $value;
                
            case '<=':
                return is_numeric($contextValue) && is_numeric($value) && $contextValue <= $value;
                
            case '>':
                return is_numeric($contextValue) && is_numeric($value) && $contextValue > $value;
                
            case '>=':
                return is_numeric($contextValue) && is_numeric($value) && $contextValue >= $value;
                
            case 'in':
                if (!is_array($value)) {
                    return false;
                }
                return in_array($contextValue, $value);
                
            case 'not_in':
                if (!is_array($value)) {
                    return false;
                }
                return !in_array($contextValue, $value);
                
            case 'matches':
            case 'regex':
                if (!is_string($contextValue) || !is_string($value)) {
                    return false;
                }
                return (bool)preg_match($value, $contextValue);
                
            case 'contains':
                if (!is_string($contextValue) || !is_string($value)) {
                    return false;
                }
                return strpos($contextValue, $value) !== false;
                
            default:
                error_log("Unknown operator: {$operator} at " . __FILE__ . ":" . __LINE__);
                return false;
        }
    }
    
    /**
     * Get nested value from context
     * 중첩 필드 접근 (예: 'goals.long_term')
     * 
     * @param array $context Context array
     * @param string $field Field path (dot notation)
     * @return mixed Field value or null
     */
    private function getNestedValue($context, $field) {
        $parts = explode('.', $field);
        $value = $context;
        
        foreach ($parts as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
    
    /**
     * Get rule definition
     * 룰 정의 가져오기 (캐시 지원)
     * 
     * @param string $ruleId Rule ID
     * @return array|null Rule definition or null
     */
    private function getRule($ruleId) {
        // 캐시 확인
        if (isset($this->ruleCache[$ruleId])) {
            return $this->ruleCache[$ruleId];
        }
        
        // TODO: 실제 구현 시 DB 또는 YAML 파일에서 룰 로드
        // 현재는 기본 구조만 제공
        
        // 예시: DB에서 룰 로드
        try {
            $sql = "
                SELECT rule_id, priority, conditions, action, confidence, rationale
                FROM mdl_alt42_rules
                WHERE rule_id = ?
                LIMIT 1
            ";
            
            $stmt = AgentDataLayer::executeQuery($sql, [$ruleId]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rule) {
                // JSON 필드 파싱
                if (isset($rule['conditions']) && is_string($rule['conditions'])) {
                    $decoded = json_decode($rule['conditions'], true);
                    $rule['conditions'] = $decoded !== null ? $decoded : array();
                }
                if (isset($rule['action']) && is_string($rule['action'])) {
                    $decoded = json_decode($rule['action'], true);
                    $rule['action'] = $decoded !== null ? $decoded : array();
                }
                
                $this->ruleCache[$ruleId] = $rule;
                return $rule;
            }
        } catch (\Exception $e) {
            // 테이블이 없으면 무시 (향후 구현)
            error_log("Rule table not found or error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
        }
        
        // Fallback: 기본 룰 구조 반환 (실제 룰 엔진 연동 전까지)
        return null;
    }
    
    /**
     * Clear rule cache
     * 룰 캐시 초기화
     */
    public function clearCache() {
        $this->ruleCache = array();
    }
}

