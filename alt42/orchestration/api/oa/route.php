<?php
/**
 * Orchestrator Agent (OA) Router
 * Central routing logic for 21-agent system
 * 
 * @package ALT42\OA
 * @version 1.0.0
 */

namespace ALT42\OA;

// Include dependencies
require_once(dirname(__DIR__) . '/events/event_bus.php');
require_once(dirname(__DIR__) . '/database/agent_data_layer.php');

// BaseAgent는 선택적 (없어도 동작)
$base_agent_path = dirname(__DIR__) . '/../agents/base_agent.php';
if (file_exists($base_agent_path)) {
    require_once($base_agent_path);
}

use ALT42\Events\EventBus;
use ALT42\Database\AgentDataLayer;

/**
 * Orchestrator Agent Router
 * Manages NBA (Next Best Action) decisions and agent coordination
 */
class OrchestratorRouter {
    private $eventBus;
    private $dataLayer;
    private $agentRegistry = array();
    private $routingRules = array();
    private $circuitBreakers = array();
    
    public function __construct() {
        $this->eventBus = new EventBus();
        $this->dataLayer = new AgentDataLayer();
        $this->initializeRoutingRules();
        $this->registerAgents();
    }
    
    /**
     * Initialize routing rules based on event types and context
     */
    private function initializeRoutingRules() {
        $self = $this;
        $this->routingRules = array(
            // High stress intervention
            'high_stress' => array(
                'condition' => function($ctx) {
                    return (isset($ctx['stress_level']) ? $ctx['stress_level'] : 0) >= 8;
                },
                'agents' => array(8, 19, 20), // 침착도 → 개입준비 → 개입실행
                'priority' => 10,
                'mode' => 'sync',
                'teacher_approval' => true
            ),
            
            // Learning dropout
            'learning_dropout' => array(
                'condition' => function($ctx) {
                    return (isset($ctx['activity_status']) ? $ctx['activity_status'] : '') === 'dropped';
                },
                'agents' => array(9, 16, 17, 19), // 학습이탈 → 문제재정의 → 전략재조정 → 개입준비
                'priority' => 9,
                'mode' => 'async'
            ),
            
            // Exam approaching (D-3)
            'exam_urgent' => array(
                'condition' => function($ctx) use ($self) {
                    return $self->isExamUrgent($ctx);
                },
                'agents' => array(3, 5, 12, 17), // 상황유형 → 지도모드 → 진행상황 → 전략재조정
                'priority' => 8,
                'mode' => 'sync'
            ),
            
            // Wrong answer pattern
            'wrong_answer' => array(
                'condition' => function($ctx) {
                    return (isset($ctx['result']) ? $ctx['result'] : '') === 'wrong';
                },
                'agents' => array(14, 4, 17, 18), // 오답노트 → 활동유형 → 전략재조정 → 상호작용컨텐츠
                'priority' => 6,
                'mode' => 'async'
            ),
            
            // Teacher feedback
            'teacher_feedback' => array(
                'condition' => function($ctx) {
                    return isset($ctx['teacher_feedback']);
                },
                'agents' => array(15, 16, 17, 18), // 교사피드백 → 문제재정의 → 전략재조정 → 상호작용컨텐츠
                'priority' => 7,
                'mode' => 'async'
            ),
            
            // Regular heartbeat
            'heartbeat' => array(
                'condition' => function($ctx) {
                    return (isset($ctx['type']) ? $ctx['type'] : '') === 'periodic' || (isset($ctx['topic']) ? $ctx['topic'] : '') === 'cron.heartbeat_30m';
                },
                'agents' => array(10, 12, 21), // 학습내용 → 진행상황 → 모듈개선
                'priority' => 2,
                'mode' => 'async'
            )
        );
    }
    
    /**
     * Register available agents
     */
    private function registerAgents() {
        // Agent registry with metadata
        $this->agentRegistry = [
            1 => ['name' => '온보딩', 'class' => 'OnboardingAgent'],
            2 => ['name' => '문제발견', 'class' => 'ProblemDiscoveryAgent'],
            3 => ['name' => '상황유형', 'class' => 'SituationTypeAgent'],
            4 => ['name' => '활동유형', 'class' => 'ActivityTypeAgent'],
            5 => ['name' => '지도모드', 'class' => 'GuidanceModeAgent'],
            6 => ['name' => '목표분석', 'class' => 'GoalAnalysisAgent'],
            7 => ['name' => '포모도르일기', 'class' => 'PomodoroDiaryAgent'],
            8 => ['name' => '침착도', 'class' => 'CalmnessAgent'],
            9 => ['name' => '학습이탈', 'class' => 'LearningDropoutAgent'],
            10 => ['name' => '학습내용', 'class' => 'LearningContentAgent'],
            11 => ['name' => '휴식패턴', 'class' => 'RestPatternAgent'],
            12 => ['name' => '진행상황', 'class' => 'ProgressStatusAgent'],
            13 => ['name' => '풀이노트', 'class' => 'SolutionNotesAgent'],
            14 => ['name' => '오답노트', 'class' => 'ErrorNotesAgent'],
            15 => ['name' => '교사피드백', 'class' => 'TeacherFeedbackAgent'],
            16 => ['name' => '문제재정의', 'class' => 'ProblemRedefinitionAgent'],
            17 => ['name' => '전략재조정', 'class' => 'StrategyReadjustmentAgent'],
            18 => ['name' => '상호작용컨텐츠', 'class' => 'InteractiveContentAgent'],
            19 => ['name' => '개입준비', 'class' => 'InterventionPreparationAgent'],
            20 => ['name' => '개입실행', 'class' => 'InterventionExecutionAgent'],
            21 => ['name' => '모듈개선', 'class' => 'ModuleImprovementAgent']
        ];
    }
    
    /**
     * Route event to appropriate agents
     * 
     * @param array $event Event data
     * @return array Routing result
     */
    public function route($event) {
        $start_time = microtime(true);
        
        try {
            // Analyze context
            $context = $this->analyzeContext($event);
            
            // Determine routing strategy
            $strategy = $this->determineStrategy($context);
            
            // Check teacher approval if needed
            if ($strategy['teacher_approval'] ?? false) {
                if (!$this->checkTeacherApproval($context)) {
                    return $this->createResponse('blocked', 'Teacher approval required', $event);
                }
            }
            
            // Execute agents based on strategy
            $results = $this->executeAgents($strategy['agents'], $context, $strategy['mode']);
            
            // Aggregate responses
            $response = $this->aggregateResponses($results);
            
            // Log execution
            $this->logExecution($event, $strategy, $response, microtime(true) - $start_time);
            
            return $response;
            
        } catch (\Exception $e) {
            error_log("OA Router Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return $this->createResponse('error', $e->getMessage(), $event);
        }
    }
    
    /**
     * Analyze event context for routing decisions
     */
    private function analyzeContext($event) {
        $student_id = $event['student_id'] ?? null;
        
        // Get student state from database
        $student_state = $student_id ? $this->dataLayer->getStudentState($student_id) : [];
        
        // Merge event data with student state
        $context = array_merge($student_state, $event);
        
        // Calculate additional metrics
        $context['urgency'] = $this->calculateUrgency($context);
        $context['complexity'] = $this->calculateComplexity($context);
        $context['risk_level'] = $this->calculateRiskLevel($context);
        
        return $context;
    }
    
    /**
     * Determine routing strategy based on context
     */
    private function determineStrategy($context) {
        foreach ($this->routingRules as $rule_name => $rule) {
            if ($rule['condition']($context)) {
                return array_merge($rule, ['rule_name' => $rule_name]);
            }
        }
        
        // Default strategy
        return [
            'rule_name' => 'default',
            'agents' => [4, 5, 10], // Basic workflow
            'priority' => 5,
            'mode' => 'async',
            'teacher_approval' => false
        ];
    }
    
    /**
     * Execute agents based on strategy
     */
    private function executeAgents($agent_ids, $context, $mode) {
        $results = [];
        
        if ($mode === 'sync') {
            // Sequential execution
            foreach ($agent_ids as $agent_id) {
                if ($this->isCircuitBreakerOpen($agent_id)) {
                    $results[$agent_id] = ['status' => 'skipped', 'reason' => 'circuit_breaker_open'];
                    continue;
                }
                
                $result = $this->executeAgent($agent_id, $context);
                $results[$agent_id] = $result;
                
                // Update context with agent output for next agent
                if ($result['status'] === 'success') {
                    $context = array_merge($context, $result['outputs'] ?? []);
                }
            }
        } else {
            // Parallel execution (simulated)
            foreach ($agent_ids as $agent_id) {
                if ($this->isCircuitBreakerOpen($agent_id)) {
                    $results[$agent_id] = ['status' => 'skipped', 'reason' => 'circuit_breaker_open'];
                    continue;
                }
                
                $results[$agent_id] = $this->executeAgent($agent_id, $context);
            }
        }
        
        return $results;
    }
    
    /**
     * Execute individual agent
     */
    private function executeAgent($agent_id, $context) {
        try {
            // Get agent class
            $agent_info = $this->agentRegistry[$agent_id] ?? null;
            if (!$agent_info) {
                throw new \Exception("Agent {$agent_id} not found");
            }
            
            // Create agent instance (would be actual class in production)
            // For now, simulate agent execution
            $response = $this->simulateAgentExecution($agent_id, $context);
            
            // Update circuit breaker
            $this->updateCircuitBreaker($agent_id, true);
            
            return $response;
            
        } catch (\Exception $e) {
            // Update circuit breaker on failure
            $this->updateCircuitBreaker($agent_id, false);
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'agent_id' => $agent_id
            ];
        }
    }
    
    /**
     * Simulate agent execution (placeholder for actual agent calls)
     */
    private function simulateAgentExecution($agent_id, $context) {
        // Simulate processing time
        usleep(rand(10000, 50000)); // 10-50ms
        
        return [
            'status' => 'success',
            'agent_id' => $agent_id,
            'agent_name' => $this->agentRegistry[$agent_id]['name'],
            'outputs' => [
                'recommendation' => "Agent {$agent_id} recommendation",
                'confidence' => rand(70, 95) / 100,
                'next_action' => 'continue'
            ],
            'execution_time_ms' => rand(10, 50)
        ];
    }
    
    /**
     * Aggregate responses from multiple agents
     */
    private function aggregateResponses($results) {
        $successful = [];
        $failed = [];
        
        foreach ($results as $agent_id => $result) {
            if ($result['status'] === 'success') {
                $successful[] = $result;
            } else {
                $failed[] = $result;
            }
        }
        
        // Calculate consensus
        $consensus = $this->calculateConsensus($successful);
        
        return [
            'status' => count($successful) > 0 ? 'success' : 'failed',
            'nba' => $consensus['next_best_action'] ?? 'review',
            'confidence' => $consensus['confidence'] ?? 0,
            'successful_agents' => count($successful),
            'failed_agents' => count($failed),
            'details' => $results,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * Calculate consensus from agent responses
     */
    private function calculateConsensus($responses) {
        if (empty($responses)) {
            return ['next_best_action' => 'review', 'confidence' => 0];
        }
        
        $total_confidence = 0;
        $recommendations = [];
        
        foreach ($responses as $response) {
            $confidence = $response['outputs']['confidence'] ?? 0;
            $total_confidence += $confidence;
            
            $recommendation = $response['outputs']['recommendation'] ?? '';
            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }
        
        $avg_confidence = $total_confidence / count($responses);
        
        return [
            'next_best_action' => $recommendations[0] ?? 'continue',
            'confidence' => $avg_confidence,
            'all_recommendations' => $recommendations
        ];
    }
    
    /**
     * Check if exam is urgent (D-3 or less)
     */
    private function isExamUrgent($context) {
        $exam_date = $context['exam_date'] ?? null;
        if (!$exam_date) {
            return false;
        }
        
        $days_until_exam = (strtotime($exam_date) - time()) / 86400;
        return $days_until_exam <= 3;
    }
    
    /**
     * Calculate urgency score
     */
    private function calculateUrgency($context) {
        $urgency = 0;
        
        // Stress level contribution
        $stress = $context['stress_level'] ?? 0;
        $urgency += $stress * 0.3;
        
        // Exam proximity contribution
        if ($this->isExamUrgent($context)) {
            $urgency += 0.4;
        }
        
        // Error rate contribution
        $error_rate = $context['error_rate'] ?? 0;
        $urgency += $error_rate * 0.3;
        
        return min($urgency, 1.0);
    }
    
    /**
     * Calculate complexity score
     */
    private function calculateComplexity($context) {
        $factors = [
            'multiple_concepts' => isset($context['concepts']) && count($context['concepts']) > 3,
            'high_error_rate' => ($context['error_rate'] ?? 0) > 0.3,
            'learning_gaps' => ($context['gap_count'] ?? 0) > 5,
            'low_engagement' => ($context['engagement_score'] ?? 1) < 0.5
        ];
        
        $complexity = array_sum(array_map(function($f) {
            return $f ? 0.25 : 0;
        }, $factors));
        
        return min($complexity, 1.0);
    }
    
    /**
     * Calculate risk level
     */
    private function calculateRiskLevel($context) {
        $risk = 0;
        
        // High stress is risky
        if (($context['stress_level'] ?? 0) >= 8) {
            $risk += 0.4;
        }
        
        // Learning dropout is risky
        if (($context['activity_status'] ?? '') === 'dropped') {
            $risk += 0.3;
        }
        
        // Low performance is risky
        if (($context['performance_score'] ?? 1) < 0.4) {
            $risk += 0.3;
        }
        
        return min($risk, 1.0);
    }
    
    /**
     * Check teacher approval for high-risk interventions
     */
    private function checkTeacherApproval($context) {
        $intervention_strength = $context['intervention_strength'] ?? 0;
        
        // High-risk interventions require approval
        if ($intervention_strength >= 8) {
            // Check if teacher has pre-approved (placeholder)
            // TODO: Implement actual teacher approval check
            return true; // For now, allow all
        }
        
        return true; // No approval needed for low-risk
    }
    
    /**
     * Circuit breaker management
     */
    private function isCircuitBreakerOpen($agent_id) {
        if (!isset($this->circuitBreakers[$agent_id])) {
            $this->circuitBreakers[$agent_id] = [
                'failures' => 0,
                'successes' => 0,
                'state' => 'closed',
                'last_failure' => 0
            ];
        }
        
        $breaker = &$this->circuitBreakers[$agent_id];
        
        // Reset if enough time has passed
        if ($breaker['state'] === 'open' && 
            time() - $breaker['last_failure'] > 60) {
            $breaker['state'] = 'half-open';
        }
        
        return $breaker['state'] === 'open';
    }
    
    /**
     * Update circuit breaker state
     */
    private function updateCircuitBreaker($agent_id, $success) {
        if (!isset($this->circuitBreakers[$agent_id])) {
            $this->circuitBreakers[$agent_id] = [
                'failures' => 0,
                'successes' => 0,
                'state' => 'closed',
                'last_failure' => 0
            ];
        }
        
        $breaker = &$this->circuitBreakers[$agent_id];
        
        if ($success) {
            $breaker['successes']++;
            if ($breaker['state'] === 'half-open' && $breaker['successes'] >= 3) {
                $breaker['state'] = 'closed';
                $breaker['failures'] = 0;
            }
        } else {
            $breaker['failures']++;
            $breaker['last_failure'] = time();
            
            if ($breaker['failures'] >= 5) {
                $breaker['state'] = 'open';
            }
        }
    }
    
    /**
     * Log execution details
     */
    private function logExecution($event, $strategy, $response, $execution_time) {
        $log = [
            'timestamp' => date('c'),
            'event_id' => $event['event_id'] ?? 'unknown',
            'strategy' => $strategy['rule_name'],
            'agents' => $strategy['agents'],
            'status' => $response['status'],
            'execution_time_ms' => $execution_time * 1000,
            'nba' => $response['nba'] ?? null
        ];
        
        error_log(json_encode($log) . " at " . __FILE__ . ":" . __LINE__, 3, '/tmp/alt42_oa_router.log');
    }
    
    /**
     * Create response structure
     */
    private function createResponse($status, $message, $event) {
        return [
            'status' => $status,
            'message' => $message,
            'event_id' => $event['event_id'] ?? null,
            'timestamp' => date('c')
        ];
    }
}

// Handle API request if called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'route.php') {
    header('Content-Type: application/json');
    
    $input = file_get_contents('php://input');
    $event = json_decode($input, true);
    
    if (!$event) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $router = new OrchestratorRouter();
    $response = $router->route($event);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}

