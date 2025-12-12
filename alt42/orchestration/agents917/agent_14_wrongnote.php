<?php
/**
 * Agent 14 - Error Notes Analysis
 * Specializes in analyzing error patterns from incorrect_answers table
 * Identifies weak areas, concepts, and generates targeted recommendations
 */

require_once(__DIR__ . '/base_agent.php');

/**
 * Agent 14 implementation for error pattern analysis
 */
class Agent14 extends BaseAgent implements AgentInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(14, 'Error Notes Analysis Agent');
    }
    
    /**
     * Initialize agent configuration
     */
    protected function initialize(): void
    {
        $this->capabilities = [
            'error_pattern_analysis',
            'concept_weakness_identification', 
            'learning_gap_detection',
            'remediation_recommendations',
            'progress_tracking'
        ];
        
        $this->subscriptions = [
            'learning.answer_wrong',
            'learning.problem_submitted'
        ];
    }
    
    /**
     * Agent-specific validation
     * @param array $eventData Event data
     * @return array Validation errors
     */
    protected function validateAgentSpecific(array $eventData): array
    {
        $errors = [];
        
        // Require student_id for analysis
        if (!isset($eventData['student_id']) || empty($eventData['student_id'])) {
            $errors[] = 'student_id is required for error analysis';
        }
        
        // Validate event types this agent handles
        $validTopics = ['learning.answer_wrong', 'learning.problem_submitted'];
        if (!in_array($eventData['topic'] ?? '', $validTopics)) {
            $errors[] = 'Agent 14 only handles learning-related events';
        }
        
        return $errors;
    }
    
    /**
     * Main execution logic for error analysis
     * @param array $eventData Event to process
     * @return array Analysis results
     */
    protected function execute(array $eventData): array
    {
        $studentId = $eventData['student_id'];
        
        // Get comprehensive student data
        $studentData = $this->getStudentData($studentId, 30); // 30 days of data
        
        // Perform detailed error analysis
        $errorAnalysis = $this->performErrorAnalysis($studentData, $eventData);
        
        // Identify learning gaps and weak concepts
        $weakConcepts = $this->identifyWeakConcepts($studentData);
        
        // Generate targeted recommendations
        $recommendations = $this->generateTargetedRecommendations($errorAnalysis, $weakConcepts);
        
        // Create remediation plan
        $remediationPlan = $this->createRemediationPlan($errorAnalysis, $weakConcepts);
        
        // Calculate confidence based on data quality and patterns
        $confidence = $this->calculateAnalysisConfidence($studentData, $errorAnalysis);
        
        return [
            'insights' => $this->generateInsights($errorAnalysis, $weakConcepts),
            'recommendations' => $recommendations,
            'actions' => $this->generateActionPlan($remediationPlan),
            'error_patterns' => $errorAnalysis['patterns'],
            'weak_concepts' => $weakConcepts,
            'remediation_plan' => $remediationPlan,
            'confidence' => $confidence,
            'analysis_depth' => $this->getAnalysisDepth($studentData),
            'priority_areas' => $this->identifyPriorityAreas($errorAnalysis, $weakConcepts)
        ];
    }
    
    /**
     * Perform comprehensive error analysis
     * @param array $studentData Student learning data
     * @param array $eventData Current event data
     * @return array Error analysis results
     */
    private function performErrorAnalysis(array $studentData, array $eventData): array
    {
        $incorrectAnswers = $studentData['incorrect_answers'] ?? [];
        
        $analysis = [
            'total_errors' => count($incorrectAnswers),
            'patterns' => [],
            'trends' => [],
            'severity' => 'low',
            'recurring_errors' => []
        ];
        
        if (empty($incorrectAnswers)) {
            return $analysis;
        }
        
        // Analyze by concept area
        $conceptErrors = [];
        $errorTypes = [];
        $timeDistribution = [];
        
        foreach ($incorrectAnswers as $error) {
            $concept = $error['concept_area'] ?? 'unknown';
            $errorType = $error['error_type'] ?? 'computational';
            $timestamp = strtotime($error['timestamp'] ?? 'now');
            $dayOfWeek = date('w', $timestamp);
            $hourOfDay = date('H', $timestamp);
            
            // Count by concept
            $conceptErrors[$concept] = ($conceptErrors[$concept] ?? 0) + 1;
            
            // Count by error type
            $errorTypes[$errorType] = ($errorTypes[$errorType] ?? 0) + 1;
            
            // Track time patterns
            $timeDistribution['day_of_week'][$dayOfWeek] = ($timeDistribution['day_of_week'][$dayOfWeek] ?? 0) + 1;
            $timeDistribution['hour_of_day'][$hourOfDay] = ($timeDistribution['hour_of_day'][$hourOfDay] ?? 0) + 1;
        }
        
        // Sort to find most problematic areas
        arsort($conceptErrors);
        arsort($errorTypes);
        
        $analysis['patterns'] = [
            'concept_errors' => $conceptErrors,
            'error_types' => $errorTypes,
            'time_distribution' => $timeDistribution
        ];
        
        // Identify recurring errors (same concept + error type)
        $analysis['recurring_errors'] = $this->findRecurringErrors($incorrectAnswers);
        
        // Analyze trends over time
        $analysis['trends'] = $this->analyzeTrends($incorrectAnswers);
        
        // Determine severity
        $analysis['severity'] = $this->calculateSeverity($analysis);
        
        return $analysis;
    }
    
    /**
     * Identify weak concepts based on error patterns
     * @param array $studentData Student learning data
     * @return array Weak concepts with severity scores
     */
    private function identifyWeakConcepts(array $studentData): array
    {
        $incorrectAnswers = $studentData['incorrect_answers'] ?? [];
        $correctAnswers = $studentData['correct_answers'] ?? [];
        
        $weakConcepts = [];
        
        // Group by concept
        $conceptStats = [];
        
        foreach ($incorrectAnswers as $error) {
            $concept = $error['concept_area'] ?? 'unknown';
            if (!isset($conceptStats[$concept])) {
                $conceptStats[$concept] = ['incorrect' => 0, 'correct' => 0];
            }
            $conceptStats[$concept]['incorrect']++;
        }
        
        foreach ($correctAnswers as $correct) {
            $concept = $correct['concept_area'] ?? 'unknown';
            if (!isset($conceptStats[$concept])) {
                $conceptStats[$concept] = ['incorrect' => 0, 'correct' => 0];
            }
            $conceptStats[$concept]['correct']++;
        }
        
        // Calculate weakness scores
        foreach ($conceptStats as $concept => $stats) {
            $total = $stats['incorrect'] + $stats['correct'];
            if ($total > 0) {
                $errorRate = $stats['incorrect'] / $total;
                $frequency = $stats['incorrect'];
                
                // Weakness score combines error rate and frequency
                $weaknessScore = ($errorRate * 0.7) + (min($frequency / 10, 1) * 0.3);
                
                if ($weaknessScore > 0.3) { // Only include concepts with significant weakness
                    $weakConcepts[$concept] = [
                        'weakness_score' => $weaknessScore,
                        'error_rate' => $errorRate,
                        'total_attempts' => $total,
                        'incorrect_count' => $stats['incorrect'],
                        'correct_count' => $stats['correct'],
                        'severity' => $this->getConceptSeverity($weaknessScore)
                    ];
                }
            }
        }
        
        // Sort by weakness score
        uasort($weakConcepts, function($a, $b) {
            return $b['weakness_score'] <=> $a['weakness_score'];
        });
        
        return $weakConcepts;
    }
    
    /**
     * Generate targeted recommendations based on analysis
     * @param array $errorAnalysis Error analysis results
     * @param array $weakConcepts Weak concepts
     * @return array Targeted recommendations
     */
    private function generateTargetedRecommendations(array $errorAnalysis, array $weakConcepts): array
    {
        $recommendations = [];
        
        // Recommendations based on weak concepts
        $topWeakConcepts = array_slice(array_keys($weakConcepts), 0, 3);
        if (!empty($topWeakConcepts)) {
            $recommendations[] = "Focus immediate attention on: " . implode(', ', $topWeakConcepts);
            $recommendations[] = "Provide additional practice problems for weak concept areas";
        }
        
        // Recommendations based on error types
        $topErrorTypes = array_slice(array_keys($errorAnalysis['patterns']['error_types'] ?? []), 0, 2);
        if (!empty($topErrorTypes)) {
            foreach ($topErrorTypes as $errorType) {
                switch ($errorType) {
                    case 'computational':
                        $recommendations[] = "Review basic computational skills and calculation methods";
                        break;
                    case 'conceptual':
                        $recommendations[] = "Strengthen conceptual understanding with visual aids and examples";
                        break;
                    case 'procedural':
                        $recommendations[] = "Practice step-by-step problem-solving procedures";
                        break;
                    case 'application':
                        $recommendations[] = "Work on applying concepts to real-world problems";
                        break;
                    default:
                        $recommendations[] = "Address specific error pattern: {$errorType}";
                }
            }
        }
        
        // Recommendations based on recurring errors
        if (!empty($errorAnalysis['recurring_errors'])) {
            $recommendations[] = "Address recurring error patterns with targeted intervention";
            $recommendations[] = "Implement spaced repetition for problematic concepts";
        }
        
        // Recommendations based on trends
        $trend = $errorAnalysis['trends']['overall_trend'] ?? 'stable';
        switch ($trend) {
            case 'improving':
                $recommendations[] = "Performance is improving - maintain current learning pace";
                break;
            case 'declining':
                $recommendations[] = "URGENT: Performance declining - immediate intervention needed";
                $recommendations[] = "Consider reducing problem difficulty temporarily";
                break;
            case 'stable_high_errors':
                $recommendations[] = "High error rate persisting - review teaching approach";
                break;
        }
        
        // Time-based recommendations
        $timePatterns = $errorAnalysis['patterns']['time_distribution'] ?? [];
        if (!empty($timePatterns['hour_of_day'])) {
            $peakErrorHour = array_keys($timePatterns['hour_of_day'], max($timePatterns['hour_of_day']))[0];
            if ($peakErrorHour >= 14) { // Afternoon/evening
                $recommendations[] = "Consider scheduling math practice earlier in the day when focus is higher";
            }
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Create detailed remediation plan
     * @param array $errorAnalysis Error analysis
     * @param array $weakConcepts Weak concepts
     * @return array Remediation plan
     */
    private function createRemediationPlan(array $errorAnalysis, array $weakConcepts): array
    {
        $plan = [
            'phases' => [],
            'timeline' => '2-4 weeks',
            'success_criteria' => [],
            'monitoring_schedule' => []
        ];
        
        // Phase 1: Address most critical concepts
        $criticalConcepts = array_filter($weakConcepts, function($concept) {
            return $concept['severity'] === 'critical';
        });
        
        if (!empty($criticalConcepts)) {
            $plan['phases']['phase_1'] = [
                'name' => 'Critical Concept Recovery',
                'duration' => '1 week',
                'focus_areas' => array_keys($criticalConcepts),
                'activities' => [
                    'diagnostic_assessment',
                    'concept_review_sessions',
                    'guided_practice',
                    'frequent_check_ins'
                ],
                'success_criteria' => 'Achieve 70% accuracy in focus areas'
            ];
        }
        
        // Phase 2: Strengthen moderate weaknesses
        $moderateConcepts = array_filter($weakConcepts, function($concept) {
            return $concept['severity'] === 'moderate';
        });
        
        if (!empty($moderateConcepts)) {
            $plan['phases']['phase_2'] = [
                'name' => 'Skill Consolidation',
                'duration' => '1-2 weeks',
                'focus_areas' => array_keys($moderateConcepts),
                'activities' => [
                    'progressive_practice',
                    'mixed_problem_sets',
                    'peer_collaboration',
                    'self_assessment'
                ],
                'success_criteria' => 'Achieve 80% accuracy in focus areas'
            ];
        }
        
        // Phase 3: Mastery and application
        $plan['phases']['phase_3'] = [
            'name' => 'Mastery and Application',
            'duration' => '1 week',
            'focus_areas' => 'All previously weak concepts',
            'activities' => [
                'complex_problem_solving',
                'application_problems',
                'comprehensive_review',
                'progress_celebration'
            ],
            'success_criteria' => 'Achieve 85% accuracy across all areas'
        ];
        
        // Set monitoring schedule
        $plan['monitoring_schedule'] = [
            'daily' => 'Track problem accuracy and completion rate',
            'weekly' => 'Assess concept mastery and adjust plan',
            'bi_weekly' => 'Comprehensive progress review'
        ];
        
        return $plan;
    }
    
    /**
     * Helper methods for analysis
     */
    private function findRecurringErrors(array $incorrectAnswers): array
    {
        $errorCombinations = [];
        
        foreach ($incorrectAnswers as $error) {
            $key = ($error['concept_area'] ?? 'unknown') . '|' . ($error['error_type'] ?? 'unknown');
            $errorCombinations[$key] = ($errorCombinations[$key] ?? 0) + 1;
        }
        
        // Return combinations that occur more than once
        return array_filter($errorCombinations, function($count) {
            return $count > 1;
        });
    }
    
    private function analyzeTrends(array $incorrectAnswers): array
    {
        if (count($incorrectAnswers) < 5) {
            return ['overall_trend' => 'insufficient_data'];
        }
        
        // Sort by timestamp
        usort($incorrectAnswers, function($a, $b) {
            return strtotime($a['timestamp'] ?? 'now') <=> strtotime($b['timestamp'] ?? 'now');
        });
        
        // Split into periods
        $total = count($incorrectAnswers);
        $firstHalf = array_slice($incorrectAnswers, 0, floor($total / 2));
        $secondHalf = array_slice($incorrectAnswers, floor($total / 2));
        
        $firstPeriodDays = $this->getTimePeriodDays($firstHalf);
        $secondPeriodDays = $this->getTimePeriodDays($secondHalf);
        
        $firstRate = count($firstHalf) / max(1, $firstPeriodDays);
        $secondRate = count($secondHalf) / max(1, $secondPeriodDays);
        
        $trend = 'stable';
        if ($secondRate > $firstRate * 1.3) {
            $trend = 'declining';
        } elseif ($secondRate < $firstRate * 0.7) {
            $trend = 'improving';
        } elseif ($firstRate > 0.5 && $secondRate > 0.5) {
            $trend = 'stable_high_errors';
        }
        
        return [
            'overall_trend' => $trend,
            'first_period_rate' => $firstRate,
            'second_period_rate' => $secondRate,
            'trend_strength' => abs($secondRate - $firstRate) / max($firstRate, 0.1)
        ];
    }
    
    private function getTimePeriodDays(array $errors): int
    {
        if (empty($errors)) return 1;
        
        $timestamps = array_map(function($error) {
            return strtotime($error['timestamp'] ?? 'now');
        }, $errors);
        
        $days = (max($timestamps) - min($timestamps)) / 86400; // Convert to days
        return max(1, (int)$days);
    }
    
    private function calculateSeverity(array $analysis): string
    {
        $totalErrors = $analysis['total_errors'];
        $recurringCount = count($analysis['recurring_errors']);
        
        if ($totalErrors >= 20 || $recurringCount >= 5) {
            return 'critical';
        } elseif ($totalErrors >= 10 || $recurringCount >= 3) {
            return 'high';
        } elseif ($totalErrors >= 5 || $recurringCount >= 1) {
            return 'moderate';
        } else {
            return 'low';
        }
    }
    
    private function getConceptSeverity(float $weaknessScore): string
    {
        if ($weaknessScore >= 0.8) return 'critical';
        if ($weaknessScore >= 0.6) return 'high';
        if ($weaknessScore >= 0.4) return 'moderate';
        return 'low';
    }
    
    private function calculateAnalysisConfidence(array $studentData, array $errorAnalysis): float
    {
        $dataPoints = count($studentData['incorrect_answers'] ?? []) + count($studentData['correct_answers'] ?? []);
        $timeSpan = $this->getDataTimeSpan($studentData);
        
        // Base confidence on data quantity and recency
        $quantityFactor = min(1.0, $dataPoints / 20); // More data = higher confidence
        $timeSpanFactor = min(1.0, $timeSpan / 14); // 2+ weeks of data = higher confidence
        $patternStrength = min(1.0, count($errorAnalysis['recurring_errors']) / 3);
        
        $confidence = ($quantityFactor * 0.4) + ($timeSpanFactor * 0.3) + ($patternStrength * 0.3);
        
        return max(0.3, min(0.95, $confidence)); // Keep within reasonable bounds
    }
    
    private function getDataTimeSpan(array $studentData): int
    {
        $allAnswers = array_merge(
            $studentData['incorrect_answers'] ?? [],
            $studentData['correct_answers'] ?? []
        );
        
        if (empty($allAnswers)) return 0;
        
        $timestamps = array_map(function($answer) {
            return strtotime($answer['timestamp'] ?? 'now');
        }, $allAnswers);
        
        return (max($timestamps) - min($timestamps)) / 86400; // Days
    }
    
    private function getAnalysisDepth(array $studentData): string
    {
        $totalData = count($studentData['incorrect_answers'] ?? []) + count($studentData['correct_answers'] ?? []);
        
        if ($totalData >= 50) return 'comprehensive';
        if ($totalData >= 20) return 'detailed';
        if ($totalData >= 10) return 'moderate';
        if ($totalData >= 5) return 'basic';
        return 'limited';
    }
    
    private function identifyPriorityAreas(array $errorAnalysis, array $weakConcepts): array
    {
        $priorities = [];
        
        // High priority: Critical concepts with recent errors
        $criticalConcepts = array_filter($weakConcepts, function($concept) {
            return $concept['severity'] === 'critical';
        });
        
        if (!empty($criticalConcepts)) {
            $priorities['immediate'] = array_keys($criticalConcepts);
        }
        
        // Medium priority: Recurring error patterns
        if (!empty($errorAnalysis['recurring_errors'])) {
            $priorities['urgent'] = array_keys($errorAnalysis['recurring_errors']);
        }
        
        // Lower priority: Moderate weaknesses
        $moderateConcepts = array_filter($weakConcepts, function($concept) {
            return $concept['severity'] === 'moderate';
        });
        
        if (!empty($moderateConcepts)) {
            $priorities['important'] = array_keys($moderateConcepts);
        }
        
        return $priorities;
    }
    
    private function generateInsights(array $errorAnalysis, array $weakConcepts): string
    {
        $insights = [];
        
        // Overall performance insight
        $totalErrors = $errorAnalysis['total_errors'];
        $severity = $errorAnalysis['severity'];
        
        $insights[] = "Analysis of {$totalErrors} errors reveals {$severity} level intervention needs.";
        
        // Concept-specific insights
        $topWeakConcept = array_keys($weakConcepts)[0] ?? null;
        if ($topWeakConcept) {
            $conceptData = $weakConcepts[$topWeakConcept];
            $errorRate = round($conceptData['error_rate'] * 100);
            $insights[] = "'{$topWeakConcept}' shows {$errorRate}% error rate and requires immediate attention.";
        }
        
        // Pattern insights
        $trend = $errorAnalysis['trends']['overall_trend'] ?? 'stable';
        switch ($trend) {
            case 'declining':
                $insights[] = "Performance is declining - early intervention critical.";
                break;
            case 'improving':
                $insights[] = "Positive improvement trend detected - maintain current approach.";
                break;
            case 'stable_high_errors':
                $insights[] = "Persistent high error rate indicates need for strategy change.";
                break;
        }
        
        return implode(' ', $insights);
    }
    
    private function generateActionPlan(array $remediationPlan): array
    {
        $actions = [];
        
        foreach ($remediationPlan['phases'] as $phase) {
            foreach ($phase['activities'] as $activity) {
                $actions[] = $activity;
            }
        }
        
        // Add monitoring actions
        $actions[] = 'implement_progress_monitoring';
        $actions[] = 'schedule_regular_assessments';
        $actions[] = 'provide_targeted_feedback';
        
        return array_unique($actions);
    }
}

/**
 * Usage Example:
 * 
 * $agent14 = new Agent14();
 * 
 * $eventData = [
 *     'topic' => 'learning.answer_wrong',
 *     'student_id' => 'S2024001',
 *     'problem_id' => 'math_fractions_001',
 *     'concept_area' => 'fractions',
 *     'error_type' => 'computational',
 *     'timestamp' => '2025-01-01T12:00:00+09:00'
 * ];
 * 
 * $result = $agent14->processEvent($eventData);
 * 
 * Expected result structure:
 * {
 *   "success": true,
 *   "agent_id": 14,
 *   "agent_name": "Error Notes Analysis Agent",
 *   "data": {
 *     "insights": "Analysis reveals critical needs in fractions...",
 *     "recommendations": [...],
 *     "actions": [...],
 *     "error_patterns": {...},
 *     "weak_concepts": {...},
 *     "remediation_plan": {...},
 *     "confidence": 0.85
 *   }
 * }
 */
?>