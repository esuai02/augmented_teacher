<?php
// 파일: mvp_system/lib/CascadeEngine.php (Line 1)
// Mathking Agentic MVP System - Cascade Propagation Engine
//
// Purpose: Execute chain interventions through rule relationships
// Usage: Internal component for MVPAgentOrchestrator
// Author: MVP Development Team

/**
 * CascadeEngine
 *
 * Executes cascade propagation through rule relationships using BFS traversal.
 *
 * Chain Intervention Example:
 * - Student shows low confidence (math_confidence <= 5)
 * - Triggers agent08_calmness_low rule
 * - Cascades to agent01_focus_loss (confidence * 0.85 weight)
 * - May cascade further to agent13_dropout_risk (confidence * 0.80 weight)
 *
 * Design Principles:
 * - Breadth-First Search for predictable execution order
 * - Loop prevention via visited tracking + max_depth
 * - Confidence decay through edge weights
 * - Dependency satisfaction checking
 * - Comprehensive execution logging
 *
 * Performance Targets:
 * - Single rule execution: <50ms
 * - Cascade depth 3: <200ms
 * - Cascade depth 5: <300ms
 * - Max cascades per context: 10 rules
 */
class CascadeEngine {
    /**
     * Database wrapper
     */
    private $db;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Configuration
     */
    private $max_depth = 5; // Maximum cascade depth
    private $min_confidence_threshold = 0.5; // Minimum confidence to continue
    private $max_cascades = 10; // Maximum rules to execute in cascade

    /**
     * Constructor
     *
     * @param object $db Moodle database object
     * @param MVPLogger $logger Logger instance
     * @param array $config Optional configuration overrides
     */
    public function __construct($db, $logger, $config = []) {
        $this->db = $db;
        $this->logger = $logger;

        // Allow configuration overrides
        if (isset($config['max_depth'])) {
            $this->max_depth = intval($config['max_depth']);
        }
        if (isset($config['min_confidence_threshold'])) {
            $this->min_confidence_threshold = floatval($config['min_confidence_threshold']);
        }
        if (isset($config['max_cascades'])) {
            $this->max_cascades = intval($config['max_cascades']);
        }
    }

    /**
     * Execute cascade propagation from initial rule
     *
     * Algorithm: Breadth-First Search with confidence decay
     *
     * 1. Start with initial rule (confidence = 1.0, depth = 0)
     * 2. Execute rule and add to queue
     * 3. Find outgoing edges (causes, complements)
     * 4. Calculate propagated confidence (parent_confidence * edge_weight)
     * 5. Add to queue if confidence >= threshold
     * 6. Repeat until queue empty or limits reached
     *
     * @param string $initial_rule_id Starting rule that matched context
     * @param array $context Student context data
     * @param array $graph Rule graph structure from RuleGraphBuilder
     * @param callable|null $execute_callback Optional callback for rule execution
     * @return array Execution results with cascade chain
     */
    public function propagate($initial_rule_id, $context, $graph, $execute_callback = null) {
        $start = microtime(true);

        // Validate initial rule exists in graph
        if (!isset($graph['nodes'][$initial_rule_id])) {
            $this->logger->error("Initial rule not found in graph", null, [
                'rule_id' => $initial_rule_id,
                'available_rules' => array_keys($graph['nodes'])
            ]);
            return [];
        }

        // Initialize BFS queue
        $queue = [[
            'rule_id' => $initial_rule_id,
            'depth' => 0,
            'confidence' => 1.0,
            'parent' => null,
            'via_edge' => null
        ]];

        $visited = []; // Track visited rules to prevent loops
        $executed = []; // Track execution results
        $cascade_count = 0;

        while (!empty($queue) && $cascade_count < $this->max_cascades) {
            $current = array_shift($queue);

            // Loop prevention: Skip if already visited
            if (isset($visited[$current['rule_id']])) {
                $this->logger->debug("Skipping already visited rule", $context, [
                    'rule_id' => $current['rule_id'],
                    'depth' => $current['depth']
                ]);
                continue;
            }

            // Depth limit: Stop if too deep
            if ($current['depth'] >= $this->max_depth) {
                $this->logger->debug("Stopping cascade at max depth", $context, [
                    'rule_id' => $current['rule_id'],
                    'depth' => $current['depth']
                ]);
                break;
            }

            // Confidence threshold: Stop if confidence too low
            if ($current['confidence'] < $this->min_confidence_threshold) {
                $this->logger->debug("Stopping cascade due to low confidence", $context, [
                    'rule_id' => $current['rule_id'],
                    'confidence' => $current['confidence'],
                    'threshold' => $this->min_confidence_threshold
                ]);
                continue;
            }

            // Mark as visited
            $visited[$current['rule_id']] = true;

            // Get rule from graph
            $rule = $graph['nodes'][$current['rule_id']]['rule'];

            // Execute rule (use callback if provided, otherwise default execution)
            $execution_start = microtime(true);
            if ($execute_callback !== null) {
                $result = call_user_func($execute_callback, $rule, $context, $current['confidence']);
            } else {
                $result = $this->default_execute_rule($rule, $context, $current['confidence']);
            }
            $execution_duration = (microtime(true) - $execution_start) * 1000;

            // Record execution
            $executed[] = [
                'rule_id' => $current['rule_id'],
                'depth' => $current['depth'],
                'confidence' => $current['confidence'],
                'parent' => $current['parent'],
                'via_edge' => $current['via_edge'],
                'action' => $rule['action'] ?? 'unknown',
                'result' => $result,
                'execution_ms' => round($execution_duration, 2)
            ];

            $cascade_count++;

            // Find outgoing edges for propagation
            $outgoing = $this->find_outgoing_edges($graph['edges'], $current['rule_id']);

            foreach ($outgoing as $edge) {
                // Skip if target already visited
                if (isset($visited[$edge['to']])) {
                    continue;
                }

                // Check dependency satisfaction for 'depends_on' edges
                if ($edge['type'] === 'depends_on') {
                    // This is a reverse dependency: current rule depends on target
                    // Skip cascade through depends_on edges
                    continue;
                }

                // Check if source dependencies are satisfied for target rule
                $dependencies_satisfied = $this->check_dependencies($edge['to'], $graph, $visited);

                if (!$dependencies_satisfied) {
                    $this->logger->debug("Skipping rule due to unsatisfied dependencies", $context, [
                        'rule_id' => $edge['to'],
                        'missing_dependencies' => $this->get_missing_dependencies($edge['to'], $graph, $visited)
                    ]);
                    continue;
                }

                // Calculate propagated confidence
                $propagated_confidence = $current['confidence'] * $edge['weight'];

                // Add to queue
                $queue[] = [
                    'rule_id' => $edge['to'],
                    'depth' => $current['depth'] + 1,
                    'confidence' => $propagated_confidence,
                    'parent' => $current['rule_id'],
                    'via_edge' => $edge['type']
                ];

                $this->logger->debug("Added rule to cascade queue", $context, [
                    'rule_id' => $edge['to'],
                    'depth' => $current['depth'] + 1,
                    'confidence' => round($propagated_confidence, 3),
                    'edge_type' => $edge['type']
                ]);
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->logger->info("Cascade propagation complete", $context, [
            'initial_rule' => $initial_rule_id,
            'rules_executed' => count($executed),
            'max_depth_reached' => max(array_column($executed, 'depth')),
            'duration_ms' => round($duration, 2)
        ]);

        return $executed;
    }

    /**
     * Default rule execution implementation
     *
     * Can be overridden by providing execute_callback to propagate()
     *
     * @param array $rule Rule definition
     * @param array $context Student context
     * @param float $confidence Propagated confidence
     * @return array Execution result
     */
    private function default_execute_rule($rule, $context, $confidence) {
        return [
            'success' => true,
            'action' => $rule['action'] ?? 'unknown',
            'confidence' => $confidence,
            'rationale' => $rule['rationale'] ?? 'No rationale provided'
        ];
    }

    /**
     * Find outgoing edges from a rule
     *
     * Includes: causes, complements (NOT depends_on)
     *
     * @param array $edges Edge list from graph
     * @param string $from_rule_id Source rule ID
     * @return array Outgoing edges
     */
    private function find_outgoing_edges($edges, $from_rule_id) {
        $outgoing = [];

        foreach ($edges as $edge) {
            if ($edge['from'] === $from_rule_id && $edge['active']) {
                // Only propagate through causes and complements
                if ($edge['type'] === 'causes' || $edge['type'] === 'complements') {
                    $outgoing[] = $edge;
                }
            }
        }

        return $outgoing;
    }

    /**
     * Check if all dependencies are satisfied for a rule
     *
     * A rule's dependencies are satisfied if all rules it depends_on have been visited
     *
     * @param string $rule_id Rule to check
     * @param array $graph Full graph structure
     * @param array $visited Visited rules map
     * @return bool True if all dependencies satisfied
     */
    private function check_dependencies($rule_id, $graph, $visited) {
        // Find all depends_on edges pointing TO this rule
        $dependencies = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['to'] === $rule_id && $edge['type'] === 'depends_on' && $edge['active']) {
                $dependencies[] = $edge['from'];
            }
        }

        // If no dependencies, always satisfied
        if (empty($dependencies)) {
            return true;
        }

        // Check if all dependencies have been visited
        foreach ($dependencies as $dep_rule_id) {
            if (!isset($visited[$dep_rule_id])) {
                return false; // Dependency not yet executed
            }
        }

        return true; // All dependencies satisfied
    }

    /**
     * Get list of missing dependencies for a rule
     *
     * Used for debugging and logging
     *
     * @param string $rule_id Rule to check
     * @param array $graph Full graph structure
     * @param array $visited Visited rules map
     * @return array Missing dependency rule IDs
     */
    private function get_missing_dependencies($rule_id, $graph, $visited) {
        $missing = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['to'] === $rule_id && $edge['type'] === 'depends_on' && $edge['active']) {
                if (!isset($visited[$edge['from']])) {
                    $missing[] = $edge['from'];
                }
            }
        }

        return $missing;
    }

    /**
     * Visualize cascade chain for debugging
     *
     * Returns ASCII tree representation of execution chain
     *
     * @param array $executed Execution results from propagate()
     * @return string ASCII tree visualization
     */
    public function visualize_cascade($executed) {
        if (empty($executed)) {
            return "No cascade executed\n";
        }

        $output = "Cascade Chain:\n";
        $output .= "==============\n\n";

        foreach ($executed as $i => $exec) {
            $indent = str_repeat('  ', $exec['depth']);
            $arrow = $exec['depth'] > 0 ? '└─> ' : '';

            $output .= "{$indent}{$arrow}[{$exec['rule_id']}]\n";
            $output .= "{$indent}    Depth: {$exec['depth']}, Confidence: " . round($exec['confidence'], 3) . "\n";
            $output .= "{$indent}    Action: {$exec['action']}\n";

            if ($exec['via_edge']) {
                $output .= "{$indent}    Via: {$exec['via_edge']}\n";
            }

            $output .= "\n";
        }

        return $output;
    }

    /**
     * Get cascade statistics
     *
     * @param array $executed Execution results
     * @return array Statistics
     */
    public function get_cascade_stats($executed) {
        if (empty($executed)) {
            return [
                'total_rules' => 0,
                'max_depth' => 0,
                'avg_confidence' => 0.0,
                'cascade_paths' => 0
            ];
        }

        $depths = array_column($executed, 'depth');
        $confidences = array_column($executed, 'confidence');

        // Count unique paths (rules at depth > 0 with different parents)
        $parents = array_filter(array_column($executed, 'parent'));
        $unique_parents = array_unique($parents);

        return [
            'total_rules' => count($executed),
            'max_depth' => max($depths),
            'avg_confidence' => round(array_sum($confidences) / count($confidences), 3),
            'cascade_paths' => count($unique_parents) + 1 // +1 for initial rule
        ];
    }
}

/**
 * File Location: mvp_system/lib/CascadeEngine.php (Line 1)
 * Purpose: Execute chain interventions through rule relationships
 *
 * Dependencies:
 * - RuleGraphBuilder.php: Graph structure (upstream)
 * - MVPLogger.php: Logging framework
 *
 * Classes:
 * - CascadeEngine: BFS-based cascade propagation
 *
 * Key Methods:
 * - propagate($initial_rule_id, $context, $graph, $callback): Execute cascade
 * - find_outgoing_edges($edges, $from_rule_id): Get propagation targets
 * - check_dependencies($rule_id, $graph, $visited): Verify dependencies
 * - visualize_cascade($executed): ASCII tree visualization
 * - get_cascade_stats($executed): Execution statistics
 *
 * Configuration:
 * - max_depth: Maximum cascade depth (default: 5)
 * - min_confidence_threshold: Stop if confidence < threshold (default: 0.5)
 * - max_cascades: Maximum rules to execute (default: 10)
 */
?>
