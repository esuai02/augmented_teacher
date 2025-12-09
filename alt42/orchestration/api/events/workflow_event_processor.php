<?php
/**
 * Workflow Event Processor
 * 워크플로우 이벤트 단위 평가 처리
 * 
 * @package ALT42\Events
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

namespace ALT42\Events;

require_once(__DIR__ . '/../mapping/event_scenario_mapper.php');
require_once(__DIR__ . '/../rule_engine/rule_evaluator.php');
require_once(__DIR__ . '/../database/agent_data_layer.php');

use ALT42\Mapping\EventScenarioMapper;
use ALT42\RuleEngine\RuleEvaluator;
use ALT42\Database\AgentDataLayer;

class WorkflowEventProcessor {
    private $mapper;
    private $ruleEvaluator;
    private $dataLayer;
    
    public function __construct() {
        $this->mapper = new EventScenarioMapper();
        $this->ruleEvaluator = new RuleEvaluator();
        $this->dataLayer = new AgentDataLayer();
    }
    
    /**
     * Get workflow group for event type
     * 
     * @param string $eventType Event type
     * @return string Workflow group name
     */
    public function getWorkflowGroup($eventType) {
        $config = $this->mapper->getScenariosForEvent($eventType);
        return isset($config['workflow_group']) ? $config['workflow_group'] : 'unknown';
    }
    
    /**
     * 워크플로우 이벤트 단위 처리
     * 
     * @param array $event Event data
     * @return array Processing result
     */
    public function processWorkflowEvent(array $event) {
        $startTime = microtime(true);
        $eventType = $event['topic'] ?? $event['type'] ?? '';
        $studentId = $event['student_id'] ?? null;
        
        if (empty($eventType)) {
            return array(
                'success' => false,
                'error' => 'Event type is required at ' . __FILE__ . ':' . __LINE__
            );
        }
        
        if (empty($studentId)) {
            return array(
                'success' => false,
                'error' => 'Student ID is required at ' . __FILE__ . ':' . __LINE__
            );
        }
        
        // 워크플로우 설정 조회
        $workflowConfig = $this->mapper->getScenariosForEvent($eventType);
        
        if (empty($workflowConfig['scenarios'])) {
            return array(
                'success' => true,
                'message' => 'No scenarios to evaluate for this event type',
                'event_type' => $eventType,
                'workflow_group' => isset($workflowConfig['workflow_group']) ? $workflowConfig['workflow_group'] : 'unknown'
            );
        }
        
        // 학생 상태 조회
        $studentState = $this->getStudentState($studentId);
        
        // 컨텍스트 구성
        $context = array_merge($studentState, array(
            'student_id' => $studentId,
            'event_type' => $eventType,
            'event_data' => $event
        ));
        
        $scenarioResults = array();
        $evaluationMode = isset($workflowConfig['evaluation_mode']) ? $workflowConfig['evaluation_mode'] : 'priority_first';
        
        // 워크플로우 그룹의 시나리오들만 평가
        foreach ($workflowConfig['scenarios'] as $scenarioId) {
            try {
                $scenarioConfig = $this->mapper->getRulesForScenario($scenarioId);
                $rules = isset($scenarioConfig['rules']) ? $scenarioConfig['rules'] : array();
                
                if (empty($rules)) {
                    error_log("No rules found for scenario {$scenarioId} at " . __FILE__ . ":" . __LINE__);
                    continue;
                }
                
                // 시나리오 그룹 평가
                $scenarioResult = $this->ruleEvaluator->evaluateScenario(
                    $scenarioId,
                    $rules,
                    $context,
                    $evaluationMode
                );
                
                $scenarioResults[$scenarioId] = $scenarioResult;
                
                // priority_first 모드: 첫 매칭 룰에서 중단
                if ($evaluationMode === 'priority_first' && 
                    !empty($scenarioResult['matched_rules'])) {
                    break;
                }
                
            } catch (\Exception $e) {
                error_log("Scenario evaluation error for {$scenarioId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
                $scenarioResults[$scenarioId] = array(
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return array(
            'success' => true,
            'event_type' => $eventType,
            'workflow_group' => isset($workflowConfig['workflow_group']) ? $workflowConfig['workflow_group'] : 'unknown',
            'affected_fields' => isset($workflowConfig['affected_fields']) ? $workflowConfig['affected_fields'] : array(),
            'scenarios_evaluated' => count($workflowConfig['scenarios']),
            'scenario_results' => $scenarioResults,
            'evaluation_mode' => $evaluationMode,
            'duration_ms' => $duration
        );
    }
    
    /**
     * 학생 상태 조회
     * 
     * @param string $studentId Student ID
     * @return array Student state
     */
    private function getStudentState($studentId) {
        try {
            // 실제 구현 시 AgentDataLayer를 통해 학생 상태 조회
            // 현재는 기본 구조만 제공
            return array(
                'student_id' => $studentId,
                'last_activity' => date('c'),
                'activity_state' => 'active'
            );
        } catch (\Exception $e) {
            error_log("Error getting student state for {$studentId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return array('student_id' => $studentId);
        }
    }
}

