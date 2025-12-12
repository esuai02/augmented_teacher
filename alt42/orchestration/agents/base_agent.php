<?php
/**
 * Base Agent Abstract Class for ALT42 Orchestration System
 * Provides standard structure and common functionality for all agents
 */

require_once(__DIR__ . '/../api/database/agent_data_layer.php');
require_once(__DIR__ . '/../api/config/event_schemas.php');

/**
 * Abstract base class for all ALT42 agents
 * Defines standard interface and common functionality
 */
abstract class BaseAgent
{
    protected int $agentId;
    protected string $agentName;
    protected array $capabilities;
    protected array $subscriptions;
    protected array $metrics;
    
    /**
     * Constructor
     * @param int $agentId Agent identifier (1-21)
     * @param string $agentName Human-readable agent name
     */
    public function __construct(int $agentId, string $agentName)
    {
        $this->agentId = $agentId;
        $this->agentName = $agentName;
        $this->capabilities = [];
        $this->subscriptions = [];
        $this->metrics = [
            'events_processed' => 0,
            'success_rate' => 0,
            'average_processing_time' => 0,
            'last_execution' => null
        ];
        
        $this->initialize();
    }
    
    /**
     * Initialize agent-specific configuration
     * Override this method in concrete agents
     */
    protected function initialize(): void
    {
        // Default initialization - override in subclasses
    }
    
    /**
     * Process an event - main entry point
     * @param array $eventData Event to process
     * @return array Processing result
     */
    public function processEvent(array $eventData): array
    {
        $startTime = microtime(true);
        
        try {
            // Validate input
            $validationResult = $this->validateInput($eventData);
            if (!$validationResult['valid']) {
                return $this->createErrorResponse('Input validation failed', $validationResult['errors']);
            }
            
            // Pre-processing hooks
            $this->preProcess($eventData);
            
            // Main processing logic (implemented by concrete agents)
            $result = $this->execute($eventData);
            
            // Post-processing hooks
            $this->postProcess($result, $eventData);
            
            // Calculate metrics
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->updateMetrics(true, $processingTime);
            
            // Publish completion event if needed
            $this->publishCompletionEvent($eventData, $result);
            
            return $this->createSuccessResponse($result, $processingTime);
            
        } catch (Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->updateMetrics(false, $processingTime);
            
            error_log("Agent {$this->agentId} ({$this->agentName}) error: " . $e->getMessage());
            
            return $this->createErrorResponse('Agent execution failed', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'agent_id' => $this->agentId,
                'agent_name' => $this->agentName
            ]);
        }
    }
    
    /**
     * Main execution logic - must be implemented by concrete agents
     * @param array $eventData Event to process
     * @return array Processing result data
     */
    abstract protected function execute(array $eventData): array;
    
    /**
     * Validate input data
     * @param array $eventData Event data to validate
     * @return array Validation result
     */
    protected function validateInput(array $eventData): array
    {
        $errors = [];
        
        // Basic validation
        if (empty($eventData)) {
            $errors[] = 'Event data is empty';
        }
        
        if (!isset($eventData['topic'])) {
            $errors[] = 'Event topic is missing';
        }
        
        if (!isset($eventData['timestamp'])) {
            $errors[] = 'Event timestamp is missing';
        }
        
        // Agent-specific validation (override in subclasses)
        $agentSpecificErrors = $this->validateAgentSpecific($eventData);
        $errors = array_merge($errors, $agentSpecificErrors);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Agent-specific validation - override in subclasses
     * @param array $eventData Event data
     * @return array Array of error messages
     */
    protected function validateAgentSpecific(array $eventData): array
    {
        return []; // No additional validation by default
    }
    
    /**
     * Pre-processing hook - override in subclasses
     * @param array $eventData Event data
     */
    protected function preProcess(array $eventData): void
    {
        // Default: log event processing start
        error_log("Agent {$this->agentId} ({$this->agentName}) started processing event: {$eventData['topic']}");
    }
    
    /**
     * Post-processing hook - override in subclasses
     * @param array $result Processing result
     * @param array $eventData Original event data
     */
    protected function postProcess(array $result, array $eventData): void
    {
        // Default: log completion
        error_log("Agent {$this->agentId} ({$this->agentName}) completed processing");
    }
    
    /**
     * Create success response
     * @param array $data Result data
     * @param float $processingTime Processing time in milliseconds
     * @return array Success response
     */
    protected function createSuccessResponse(array $data, float $processingTime): array
    {
        return [
            'success' => true,
            'agent_id' => $this->agentId,
            'agent_name' => $this->agentName,
            'data' => $data,
            'processing_time_ms' => $processingTime,
            'timestamp' => date('c'),
            'quality_metrics' => $this->calculateQualityMetrics($data)
        ];
    }
    
    /**
     * Create error response
     * @param string $message Error message
     * @param array $details Error details
     * @return array Error response
     */
    protected function createErrorResponse(string $message, array $details = []): array
    {
        return [
            'success' => false,
            'agent_id' => $this->agentId,
            'agent_name' => $this->agentName,
            'error' => $message,
            'error_details' => $details,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * Calculate quality metrics for the processing result
     * @param array $data Processing result data
     * @return array Quality metrics
     */
    protected function calculateQualityMetrics(array $data): array
    {
        $metrics = [
            'completeness' => $this->calculateCompleteness($data),
            'confidence' => $this->calculateConfidence($data),
            'relevance' => $this->calculateRelevance($data),
            'timeliness' => $this->calculateTimeliness()
        ];
        
        $metrics['overall_quality'] = array_sum($metrics) / count($metrics);
        
        return $metrics;
    }
    
    /**
     * Calculate completeness score (0-1)
     * @param array $data Result data
     * @return float Completeness score
     */
    protected function calculateCompleteness(array $data): float
    {
        // Basic completeness - check if all expected fields are present
        $expectedFields = ['insights', 'recommendations', 'actions'];
        $presentFields = 0;
        
        foreach ($expectedFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $presentFields++;
            }
        }
        
        return $presentFields / count($expectedFields);
    }
    
    /**
     * Calculate confidence score (0-1)
     * @param array $data Result data
     * @return float Confidence score
     */
    protected function calculateConfidence(array $data): float
    {
        // If confidence is explicitly provided, use it
        if (isset($data['confidence'])) {
            return (float)$data['confidence'];
        }
        
        // Default confidence based on data quality
        return 0.7; // Default moderate confidence
    }
    
    /**
     * Calculate relevance score (0-1)
     * @param array $data Result data
     * @return float Relevance score
     */
    protected function calculateRelevance(array $data): float
    {
        // Default relevance - can be overridden by specific agents
        return 0.8;
    }
    
    /**
     * Calculate timeliness score (0-1)
     * @return float Timeliness score
     */
    protected function calculateTimeliness(): float
    {
        // Based on processing speed - faster is more timely
        $avgTime = $this->metrics['average_processing_time'] ?? 1000;
        return max(0.1, min(1.0, 2000 / $avgTime)); // Normalize to 0-1 range
    }
    
    /**
     * Update agent performance metrics
     * @param bool $success Whether processing was successful
     * @param float $processingTime Processing time in milliseconds
     */
    protected function updateMetrics(bool $success, float $processingTime): void
    {
        $this->metrics['events_processed']++;
        $this->metrics['last_execution'] = date('c');
        
        // Update success rate (rolling average)
        $oldRate = $this->metrics['success_rate'];
        $newRate = $success ? 1.0 : 0.0;
        $this->metrics['success_rate'] = ($oldRate * 0.9) + ($newRate * 0.1);
        
        // Update average processing time (rolling average)
        $oldAvg = $this->metrics['average_processing_time'];
        $this->metrics['average_processing_time'] = ($oldAvg * 0.9) + ($processingTime * 0.1);
    }
    
    /**
     * Publish completion event for other agents
     * @param array $originalEvent Original event data
     * @param array $result Processing result
     */
    protected function publishCompletionEvent(array $originalEvent, array $result): void
    {
        // Create completion event
        $completionEvent = [
            'topic' => 'system.agent_response',
            'agent_id' => $this->agentId,
            'request_id' => $originalEvent['event_id'] ?? uniqid(),
            'status' => 'success',
            'response_data' => $result,
            'execution_time_ms' => $result['processing_time_ms'] ?? 0,
            'timestamp' => date('c')
        ];
        
        try {
            // Store completion event (other agents can subscribe to these)
            AgentDataLayer::storeEvent($completionEvent);
        } catch (Exception $e) {
            error_log("Failed to publish completion event for agent {$this->agentId}: " . $e->getMessage());
        }
    }
    
    /**
     * Get student learning data helper
     * @param string $studentId Student identifier
     * @param int $days Number of days to look back
     * @return array Student learning data
     */
    protected function getStudentData(string $studentId, int $days = 7): array
    {
        try {
            return AgentDataLayer::getStudentLearningData($studentId, $days);
        } catch (Exception $e) {
            error_log("Failed to get student data for {$studentId}: " . $e->getMessage());
            return [
                'student_id' => $studentId,
                'incorrect_answers' => [],
                'correct_answers' => [],
                'total_problems' => 0,
                'accuracy_rate' => 0.5
            ];
        }
    }
    
    /**
     * Analyze patterns in student data
     * @param array $studentData Student learning data
     * @return array Pattern analysis
     */
    protected function analyzePatterns(array $studentData): array
    {
        $incorrectAnswers = $studentData['incorrect_answers'] ?? [];
        $patterns = [];
        
        // Analyze error patterns by concept area
        $conceptErrors = [];
        foreach ($incorrectAnswers as $error) {
            $concept = $error['concept_area'] ?? 'unknown';
            $conceptErrors[$concept] = ($conceptErrors[$concept] ?? 0) + 1;
        }
        
        // Find most problematic concepts
        arsort($conceptErrors);
        $patterns['error_concepts'] = array_slice(array_keys($conceptErrors), 0, 3);
        
        // Analyze temporal patterns
        $recentErrors = array_filter($incorrectAnswers, function($error) {
            return strtotime($error['timestamp'] ?? 'now') > strtotime('-24 hours');
        });
        
        $patterns['recent_error_rate'] = count($recentErrors) / max(1, count($incorrectAnswers));
        $patterns['total_errors'] = count($incorrectAnswers);
        $patterns['accuracy_trend'] = $this->calculateAccuracyTrend($studentData);
        
        return $patterns;
    }
    
    /**
     * Calculate accuracy trend (improving, declining, stable)
     * @param array $studentData Student learning data
     * @return string Trend description
     */
    protected function calculateAccuracyTrend(array $studentData): string
    {
        $incorrectAnswers = $studentData['incorrect_answers'] ?? [];
        
        if (count($incorrectAnswers) < 5) {
            return 'insufficient_data';
        }
        
        // Split into recent and older data
        $recent = array_filter($incorrectAnswers, function($error) {
            return strtotime($error['timestamp'] ?? 'now') > strtotime('-3 days');
        });
        
        $older = array_filter($incorrectAnswers, function($error) {
            return strtotime($error['timestamp'] ?? 'now') <= strtotime('-3 days');
        });
        
        if (empty($recent) || empty($older)) {
            return 'stable';
        }
        
        $recentErrorRate = count($recent) / 3; // Errors per day
        $olderErrorRate = count($older) / max(1, (count($incorrectAnswers) - count($recent)) / 7); // Errors per day
        
        if ($recentErrorRate > $olderErrorRate * 1.2) {
            return 'declining';
        } elseif ($recentErrorRate < $olderErrorRate * 0.8) {
            return 'improving';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Generate recommendations based on analysis
     * @param array $patterns Analysis patterns
     * @param array $context Additional context
     * @return array Recommendations
     */
    protected function generateRecommendations(array $patterns, array $context = []): array
    {
        $recommendations = [];
        
        // Recommendations based on error patterns
        if (!empty($patterns['error_concepts'])) {
            $recommendations[] = "Focus on concept reinforcement in: " . implode(', ', $patterns['error_concepts']);
        }
        
        // Recommendations based on trend
        switch ($patterns['accuracy_trend'] ?? 'stable') {
            case 'declining':
                $recommendations[] = "Immediate intervention needed - performance is declining";
                $recommendations[] = "Consider reducing difficulty temporarily";
                break;
            case 'improving':
                $recommendations[] = "Performance is improving - maintain current strategy";
                break;
            case 'stable':
                $recommendations[] = "Performance is stable - consider introducing new challenges";
                break;
        }
        
        // Recommendations based on recent error rate
        if (($patterns['recent_error_rate'] ?? 0) > 0.7) {
            $recommendations[] = "High recent error rate detected - provide additional support";
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Get agent information
     * @return array Agent information
     */
    public function getInfo(): array
    {
        return [
            'agent_id' => $this->agentId,
            'agent_name' => $this->agentName,
            'capabilities' => $this->capabilities,
            'subscriptions' => $this->subscriptions,
            'metrics' => $this->metrics
        ];
    }
    
    /**
     * Check if agent can handle specific event type
     * @param string $eventType Event type to check
     * @return bool True if agent can handle the event
     */
    public function canHandle(string $eventType): bool
    {
        return in_array($eventType, $this->subscriptions) || in_array('*', $this->subscriptions);
    }
    
    /**
     * Get agent health status
     * @return array Health status
     */
    public function getHealthStatus(): array
    {
        $health = 'healthy';
        $issues = [];
        
        // Check success rate
        if ($this->metrics['success_rate'] < 0.8) {
            $health = 'degraded';
            $issues[] = 'Low success rate: ' . round($this->metrics['success_rate'] * 100, 2) . '%';
        }
        
        // Check processing time
        if ($this->metrics['average_processing_time'] > 5000) {
            $health = 'degraded';
            $issues[] = 'High processing time: ' . round($this->metrics['average_processing_time'], 2) . 'ms';
        }
        
        // Check last execution
        $lastExecution = $this->metrics['last_execution'];
        if ($lastExecution && strtotime($lastExecution) < strtotime('-1 hour')) {
            $health = 'inactive';
            $issues[] = 'No recent activity';
        }
        
        return [
            'status' => $health,
            'issues' => $issues,
            'metrics' => $this->metrics,
            'timestamp' => date('c')
        ];
    }
}

/**
 * Agent interface for standardized behavior
 */
interface AgentInterface
{
    /**
     * Process an event
     * @param array $eventData Event to process
     * @return array Processing result
     */
    public function processEvent(array $eventData): array;
    
    /**
     * Get agent information
     * @return array Agent information
     */
    public function getInfo(): array;
    
    /**
     * Check if agent can handle event type
     * @param string $eventType Event type
     * @return bool True if can handle
     */
    public function canHandle(string $eventType): bool;
}

/**
 * Usage Example:
 * 
 * class MyCustomAgent extends BaseAgent implements AgentInterface
 * {
 *     protected function initialize(): void
 *     {
 *         $this->capabilities = ['analysis', 'recommendations'];
 *         $this->subscriptions = ['learning.answer_wrong', 'learning.problem_submitted'];
 *     }
 *     
 *     protected function execute(array $eventData): array
 *     {
 *         // Agent-specific processing logic
 *         $studentId = $eventData['student_id'];
 *         $studentData = $this->getStudentData($studentId);
 *         $patterns = $this->analyzePatterns($studentData);
 *         $recommendations = $this->generateRecommendations($patterns);
 *         
 *         return [
 *             'insights' => 'Student shows specific error patterns',
 *             'recommendations' => $recommendations,
 *             'actions' => ['provide_feedback', 'adjust_difficulty'],
 *             'confidence' => 0.85
 *         ];
 *     }
 * }
 */
?>