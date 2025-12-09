<?php
// 파일: mvp_system/lib/ConflictResolver.php (Line 1)
// Mathking Agentic MVP System - Conflict Resolution Engine
//
// Purpose: Resolve rule contradictions through priority + confidence
// Usage: Internal component for MVPAgentOrchestrator
// Author: MVP Development Team

/**
 * ConflictResolver
 *
 * Resolves contradicting rules using priority-based strategy with confidence tiebreaker.
 *
 * Conflict Resolution Example:
 * - agent06_feedback_harsh (priority: 8, confidence: 0.9)
 * - agent06_feedback_encouraging (priority: 8, confidence: 0.95)
 * - Contradiction edge exists: harsh contradicts encouraging
 * - Resolution: Keep encouraging (higher confidence)
 *
 * Design Principles:
 * - Priority First: Higher priority always wins
 * - Confidence Tiebreaker: When priorities equal, higher confidence wins
 * - Transparency: All resolutions logged with rationale
 * - Graph-Aware: Uses contradiction edges from RuleGraphBuilder
 * - Batch Processing: Efficient resolution of multiple conflicts
 *
 * Performance Targets:
 * - Single conflict resolution: <10ms
 * - Batch resolution (10 rules): <50ms
 * - Detection + resolution: <80ms
 */
class ConflictResolver {
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
    private $default_priority = 5; // Default priority if not specified
    private $log_resolutions = true; // Log all conflict resolutions

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
        if (isset($config['default_priority'])) {
            $this->default_priority = intval($config['default_priority']);
        }
        if (isset($config['log_resolutions'])) {
            $this->log_resolutions = (bool)$config['log_resolutions'];
        }
    }

    /**
     * Resolve conflicts in candidate rule set
     *
     * Algorithm: Priority-based resolution with confidence tiebreaker
     *
     * 1. Find all contradiction edges in graph
     * 2. Group candidate rules by contradiction pairs
     * 3. For each pair, compare priorities
     * 4. If priorities equal, compare confidence
     * 5. Remove lower-priority/confidence rule
     * 6. Log resolution decision
     *
     * @param array $candidate_rules Rules that matched context (from RuleEvaluator)
     * @param array $graph Rule graph structure from RuleGraphBuilder
     * @param array $context Student context data
     * @return array Resolved rule set with conflicts removed
     */
    public function resolve($candidate_rules, $graph, $context = []) {
        $start = microtime(true);

        // Build rule lookup map for fast access
        $rule_map = [];
        foreach ($candidate_rules as $rule) {
            $rule_id = $rule['rule_id'] ?? null;
            if ($rule_id) {
                $rule_map[$rule_id] = $rule;
            }
        }

        if (empty($rule_map)) {
            $this->logger->info("No rules to resolve", $context);
            return [];
        }

        // Find contradiction edges involving candidate rules
        $contradiction_edges = $this->find_contradiction_edges($rule_map, $graph);

        if (empty($contradiction_edges)) {
            $this->logger->info("No contradictions found", $context, [
                'candidate_count' => count($rule_map)
            ]);
            return array_values($rule_map); // Return all candidates
        }

        // Track rules to remove
        $rules_to_remove = [];

        // Process each contradiction
        foreach ($contradiction_edges as $edge) {
            $rule1_id = $edge['from'];
            $rule2_id = $edge['to'];

            // Skip if either rule already removed
            if (isset($rules_to_remove[$rule1_id]) || isset($rules_to_remove[$rule2_id])) {
                continue;
            }

            // Get rule details
            $rule1 = $rule_map[$rule1_id] ?? null;
            $rule2 = $rule_map[$rule2_id] ?? null;

            if (!$rule1 || !$rule2) {
                continue; // Skip if either rule not in candidates
            }

            // Compare and resolve
            $winner = $this->compare_rules($rule1, $rule2);

            if ($winner === $rule1_id) {
                $rules_to_remove[$rule2_id] = true;
                $loser_id = $rule2_id;
                $reason = $this->get_resolution_reason($rule1, $rule2);
            } else {
                $rules_to_remove[$rule1_id] = true;
                $loser_id = $rule1_id;
                $reason = $this->get_resolution_reason($rule2, $rule1);
            }

            // Log resolution
            if ($this->log_resolutions) {
                $this->logger->info("Conflict resolved", $context, [
                    'winner' => $winner,
                    'loser' => $loser_id,
                    'reason' => $reason,
                    'rule1_priority' => $rule1['priority'] ?? $this->default_priority,
                    'rule2_priority' => $rule2['priority'] ?? $this->default_priority,
                    'rule1_confidence' => $rule1['confidence'] ?? 1.0,
                    'rule2_confidence' => $rule2['confidence'] ?? 1.0
                ]);
            }
        }

        // Remove conflicting rules
        $resolved_rules = [];
        foreach ($rule_map as $rule_id => $rule) {
            if (!isset($rules_to_remove[$rule_id])) {
                $resolved_rules[] = $rule;
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->logger->info("Conflict resolution complete", $context, [
            'original_count' => count($rule_map),
            'removed_count' => count($rules_to_remove),
            'final_count' => count($resolved_rules),
            'contradiction_edges' => count($contradiction_edges),
            'duration_ms' => round($duration, 2)
        ]);

        return $resolved_rules;
    }

    /**
     * Find contradiction edges involving candidate rules
     *
     * Only returns edges where BOTH endpoints are in candidate set
     *
     * @param array $rule_map Rule ID => rule mapping
     * @param array $graph Graph structure
     * @return array Contradiction edges
     */
    private function find_contradiction_edges($rule_map, $graph) {
        $contradiction_edges = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['type'] === 'contradicts' && $edge['active']) {
                // Check if both endpoints are in candidate set
                if (isset($rule_map[$edge['from']]) && isset($rule_map[$edge['to']])) {
                    $contradiction_edges[] = $edge;
                }
            }
        }

        return $contradiction_edges;
    }

    /**
     * Compare two rules and return winner ID
     *
     * Resolution Strategy:
     * 1. Compare priorities (higher wins)
     * 2. If equal, compare confidence (higher wins)
     * 3. If still equal, first rule wins (stable sort)
     *
     * @param array $rule1 First rule
     * @param array $rule2 Second rule
     * @return string Winner rule ID
     */
    private function compare_rules($rule1, $rule2) {
        $priority1 = $rule1['priority'] ?? $this->default_priority;
        $priority2 = $rule2['priority'] ?? $this->default_priority;

        // Priority comparison
        if ($priority1 > $priority2) {
            return $rule1['rule_id'];
        } elseif ($priority2 > $priority1) {
            return $rule2['rule_id'];
        }

        // Priority equal, compare confidence
        $confidence1 = $rule1['confidence'] ?? 1.0;
        $confidence2 = $rule2['confidence'] ?? 1.0;

        if ($confidence1 > $confidence2) {
            return $rule1['rule_id'];
        } elseif ($confidence2 > $confidence1) {
            return $rule2['rule_id'];
        }

        // Both equal, first rule wins (stable sort)
        return $rule1['rule_id'];
    }

    /**
     * Get human-readable resolution reason
     *
     * @param array $winner Winning rule
     * @param array $loser Losing rule
     * @return string Resolution reason
     */
    private function get_resolution_reason($winner, $loser) {
        $winner_priority = $winner['priority'] ?? $this->default_priority;
        $loser_priority = $loser['priority'] ?? $this->default_priority;

        if ($winner_priority > $loser_priority) {
            return "Higher priority ({$winner_priority} > {$loser_priority})";
        }

        $winner_confidence = $winner['confidence'] ?? 1.0;
        $loser_confidence = $loser['confidence'] ?? 1.0;

        if ($winner_confidence > $loser_confidence) {
            return "Equal priority, higher confidence (" . round($winner_confidence, 3) . " > " . round($loser_confidence, 3) . ")";
        }

        return "Equal priority and confidence, stable sort";
    }

    /**
     * Validate rule set for contradictions
     *
     * Checks if resolved rule set still contains contradictions
     * Used for testing and validation
     *
     * @param array $rules Rule set to validate
     * @param array $graph Graph structure
     * @return array Validation result ['valid' => bool, 'contradictions' => array]
     */
    public function validate_resolution($rules, $graph) {
        $rule_ids = array_map(function($rule) {
            return $rule['rule_id'];
        }, $rules);

        $contradictions = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['type'] === 'contradicts' && $edge['active']) {
                // Check if both endpoints are in rule set
                if (in_array($edge['from'], $rule_ids) && in_array($edge['to'], $rule_ids)) {
                    $contradictions[] = [
                        'from' => $edge['from'],
                        'to' => $edge['to']
                    ];
                }
            }
        }

        return [
            'valid' => empty($contradictions),
            'contradictions' => $contradictions
        ];
    }

    /**
     * Get conflict resolution statistics
     *
     * @param array $original_rules Original candidate set
     * @param array $resolved_rules Resolved candidate set
     * @return array Statistics
     */
    public function get_resolution_stats($original_rules, $resolved_rules) {
        $removed_count = count($original_rules) - count($resolved_rules);

        $removed_ids = array_diff(
            array_column($original_rules, 'rule_id'),
            array_column($resolved_rules, 'rule_id')
        );

        return [
            'original_count' => count($original_rules),
            'resolved_count' => count($resolved_rules),
            'removed_count' => $removed_count,
            'removal_rate' => count($original_rules) > 0 ? round($removed_count / count($original_rules) * 100, 1) : 0,
            'removed_rule_ids' => array_values($removed_ids)
        ];
    }
}

/**
 * File Location: mvp_system/lib/ConflictResolver.php (Line 1)
 * Purpose: Resolve rule contradictions through priority + confidence
 *
 * Dependencies:
 * - RuleGraphBuilder.php: Graph structure (upstream)
 * - MVPLogger.php: Logging framework
 *
 * Classes:
 * - ConflictResolver: Priority-based conflict resolution
 *
 * Key Methods:
 * - resolve($candidate_rules, $graph, $context): Main resolution algorithm
 * - find_contradiction_edges($rule_map, $graph): Find contradicting pairs
 * - compare_rules($rule1, $rule2): Priority + confidence comparison
 * - validate_resolution($rules, $graph): Check for remaining contradictions
 * - get_resolution_stats($original, $resolved): Resolution metrics
 *
 * Configuration:
 * - default_priority: Default priority if not specified (default: 5)
 * - log_resolutions: Enable resolution logging (default: true)
 */
?>
