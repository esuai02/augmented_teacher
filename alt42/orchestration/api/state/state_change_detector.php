<?php
/**
 * State Change Detector
 * 상태 변화 감지 및 최적화 평가
 * Δstate 기반 영향받는 룰만 선별 평가
 * 
 * @package ALT42\State
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

namespace ALT42\State;

require_once(__DIR__ . '/../mapping/event_scenario_mapper.php');
require_once(__DIR__ . '/../rule_engine/rule_evaluator.php');
require_once(__DIR__ . '/../database/agent_data_layer.php');
require_once(__DIR__ . '/../events/event_bus.php');

use ALT42\Mapping\EventScenarioMapper;
use ALT42\RuleEngine\RuleEvaluator;
use ALT42\Database\AgentDataLayer;
use ALT42\Events\EventBus;

class StateChangeDetector {
    private $mapper;
    private $ruleEvaluator;
    private $dataLayer;
    private $eventBus;
    
    public function __construct() {
        $this->mapper = new EventScenarioMapper();
        $this->ruleEvaluator = new RuleEvaluator();
        $this->dataLayer = new AgentDataLayer();
        $this->eventBus = new EventBus();
    }
    
    /**
     * 상태 변화 감지 및 최적화 평가
     * 
     * @param array $oldState 이전 상태
     * @param array $newState 새로운 상태
     * @param string $studentId 학생 ID
     * @return array 평가 결과
     */
    public function detectAndEvaluate(array $oldState, array $newState, $studentId) {
        $startTime = microtime(true);
        
        // 변화된 필드 추출
        $changedFields = $this->getChangedFields($oldState, $newState);
        
        if (empty($changedFields)) {
            return array(
                'changed' => false,
                'message' => 'No state changes detected'
            );
        }
        
        // 변화 필드에 영향받는 룰만 조회
        $affectedRules = $this->getAffectedRules($changedFields);
        
        if (empty($affectedRules)) {
            return array(
                'changed' => true,
                'changed_fields' => $changedFields,
                'affected_rules' => 0,
                'message' => 'No rules affected by state change'
            );
        }
        
        // 영향받는 룰만 평가
        $evaluationResults = array();
        $context = array_merge($newState, array('student_id' => $studentId));
        
        foreach ($affectedRules as $ruleGroup) {
            $scenarioId = $ruleGroup['scenario_id'];
            $ruleIds = $ruleGroup['rule_ids'];
            
            try {
                $result = $this->ruleEvaluator->evaluateScenario(
                    $scenarioId,
                    $ruleIds,  // 영향받는 룰만
                    $context,
                    'priority_first'
                );
                
                $evaluationResults[$scenarioId] = $result;
                
            } catch (\Exception $e) {
                error_log("State change evaluation error for scenario {$scenarioId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
                $evaluationResults[$scenarioId] = array(
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        // 상태 재분류
        $reclassifiedState = $this->reclassifyState($newState, $evaluationResults);
        
        // 피드백 호출 (필요시)
        $feedbackTriggered = false;
        if ($this->shouldTriggerFeedback($evaluationResults)) {
            $feedbackTriggered = $this->triggerFeedback($studentId, $evaluationResults, $reclassifiedState);
        }
        
        // 상태 변화 이벤트 발행
        $this->publishStateChangeEvent($studentId, $changedFields, $evaluationResults);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return array(
            'changed' => true,
            'changed_fields' => $changedFields,
            'affected_rules' => count($affectedRules),
            'evaluation_results' => $evaluationResults,
            'reclassified_state' => $reclassifiedState,
            'feedback_triggered' => $feedbackTriggered,
            'duration_ms' => $duration
        );
    }
    
    /**
     * 변화된 필드 추출
     * 
     * @param array $oldState 이전 상태
     * @param array $newState 새로운 상태
     * @return array 변화된 필드 목록
     */
    private function getChangedFields(array $oldState, array $newState) {
        $changed = array();
        
        // 새 상태의 모든 필드 확인
        foreach ($newState as $key => $value) {
            // student_id는 제외
            if ($key === 'student_id') {
                continue;
            }
            
            // 필드가 새로 추가되었거나 값이 변경됨
            if (!isset($oldState[$key]) || $oldState[$key] !== $value) {
                $changed[] = $key;
            }
        }
        
        // 이전 상태에서 제거된 필드 확인
        foreach ($oldState as $key => $value) {
            if ($key === 'student_id') {
                continue;
            }
            
            if (!isset($newState[$key])) {
                $changed[] = $key;
            }
        }
        
        return array_unique($changed);
    }
    
    /**
     * 변화 필드에 영향받는 룰 조회
     * 
     * @param array $changedFields 변화된 필드 목록
     * @return array 영향받는 룰 그룹 (시나리오별)
     */
    private function getAffectedRules(array $changedFields) {
        // 실제 구현 시 DB 쿼리 또는 캐시 활용
        // 현재는 매퍼를 통해 간접적으로 조회
        
        $affectedRules = array();
        
        // 각 변화 필드에 대해 관련 시나리오 찾기
        // 예: stress_level 변경 → S3, S4 시나리오
        $fieldScenarioMap = $this->getFieldScenarioMapping();
        
        foreach ($changedFields as $field) {
            if (isset($fieldScenarioMap[$field])) {
                $scenarios = $fieldScenarioMap[$field];
                
                foreach ($scenarios as $scenarioId) {
                    if (!isset($affectedRules[$scenarioId])) {
                        $scenarioConfig = $this->mapper->getRulesForScenario($scenarioId);
                        $affectedRules[$scenarioId] = array(
                            'scenario_id' => $scenarioId,
                            'rule_ids' => isset($scenarioConfig['rules']) ? $scenarioConfig['rules'] : array(),
                            'affected_fields' => array()
                        );
                    }
                    
                    $affectedRules[$scenarioId]['affected_fields'][] = $field;
                }
            }
        }
        
        return array_values($affectedRules);
    }
    
    /**
     * 필드-시나리오 매핑 조회
     * 변화 필드가 어떤 시나리오에 영향을 주는지 매핑
     * 
     * @return array 필드 → 시나리오 매핑
     */
    private function getFieldScenarioMapping() {
        // 실제 구현 시 DB 또는 설정 파일에서 로드
        // 현재는 하드코딩된 매핑 (향후 DB화)
        
        return array(
            // 학습 관련 필드
            'answer_count' => array('S1', 'S2'),
            'wrong_count' => array('S1', 'S2'),
            'correct_count' => array('S2'),
            'problem_count' => array('S1', 'S2'),
            'last_activity' => array('S1'),
            'session_duration' => array('S1', 'S2'),
            
            // 생체 신호 필드
            'stress_level' => array('S3', 'S4'),
            'concentration_level' => array('S1', 'S3'),
            'bio_state' => array('S3', 'S4'),
            
            // 감정 상태 필드
            'emotion_state' => array('S4'),
            'confidence' => array('S4'),
            
            // 활동 상태 필드
            'activity_state' => array('S1', 'S3'),
            'learning_stopped' => array('S1', 'S2'),
            
            // 학습 진행 필드
            'current_unit' => array('S2'),
            'progress' => array('S2'),
            'completion_rate' => array('S1', 'S2')
        );
    }
    
    /**
     * 상태 재분류
     * 평가 결과를 바탕으로 상태 재분류
     * 
     * @param array $currentState 현재 상태
     * @param array $evaluationResults 평가 결과
     * @return array 재분류된 상태
     */
    private function reclassifyState(array $currentState, array $evaluationResults) {
        $reclassified = $currentState;
        
        // 평가 결과를 바탕으로 상태 재분류
        foreach ($evaluationResults as $scenarioId => $result) {
            if (!empty($result['matched_rules'])) {
                $matchedRule = $result['matched_rules'][0];
                
                // 룰의 액션에 따라 상태 업데이트
                $actions = isset($matchedRule['action']) ? $matchedRule['action'] : array();
                
                foreach ($actions as $action) {
                    if (is_string($action) && strpos($action, 'update_state:') === 0) {
                        // 상태 업데이트 액션 파싱
                        $stateUpdate = substr($action, strlen('update_state:'));
                        $parts = explode('=', $stateUpdate);
                        
                        if (count($parts) === 2) {
                            $field = trim($parts[0]);
                            $value = trim($parts[1]);
                            $reclassified[$field] = $value;
                        }
                    }
                }
            }
        }
        
        return $reclassified;
    }
    
    /**
     * 피드백 호출 필요 여부 판단
     * 
     * @param array $evaluationResults 평가 결과
     * @return bool 피드백 호출 필요 여부
     */
    private function shouldTriggerFeedback(array $evaluationResults) {
        // 매칭된 룰이 있고, 긴급도가 높은 경우
        foreach ($evaluationResults as $result) {
            if (!empty($result['matched_rules'])) {
                $matchedRule = $result['matched_rules'][0];
                $priority = isset($matchedRule['priority']) ? $matchedRule['priority'] : 0;
                
                // 우선순위가 높은 경우 (8 이상)
                if ($priority >= 8) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 피드백 호출
     * 
     * @param string $studentId 학생 ID
     * @param array $evaluationResults 평가 결과
     * @param array $reclassifiedState 재분류된 상태
     * @return bool 피드백 호출 성공 여부
     */
    private function triggerFeedback($studentId, array $evaluationResults, array $reclassifiedState) {
        try {
            // 피드백 이벤트 발행
            $feedbackEvent = array(
                'topic' => 'state.feedback_triggered',
                'student_id' => $studentId,
                'evaluation_results' => $evaluationResults,
                'reclassified_state' => $reclassifiedState,
                'timestamp' => date('c')
            );
            
            $this->eventBus->publish('state.feedback_triggered', $feedbackEvent, 5);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Feedback trigger error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }
    
    /**
     * 상태 변화 이벤트 발행
     * 
     * @param string $studentId 학생 ID
     * @param array $changedFields 변화된 필드
     * @param array $evaluationResults 평가 결과
     */
    private function publishStateChangeEvent($studentId, array $changedFields, array $evaluationResults) {
        try {
            $stateChangeEvent = array(
                'topic' => 'state.change_detected',
                'student_id' => $studentId,
                'changed_fields' => $changedFields,
                'evaluation_results' => $evaluationResults,
                'timestamp' => date('c')
            );
            
            $this->eventBus->publish('state.change_detected', $stateChangeEvent, 5);
            
        } catch (\Exception $e) {
            error_log("State change event publish error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
        }
    }
}

