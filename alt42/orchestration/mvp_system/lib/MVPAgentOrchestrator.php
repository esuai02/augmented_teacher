<?php
// 파일: mvp_system/lib/MVPAgentOrchestrator.php (Line 1)
// Mathking Agentic MVP System - Agent Orchestrator
//
// Purpose: Route student contexts to 22 agents and coordinate execution
// Architecture: Lazy loading + rule-based routing + metric tracking
// Performance: <100ms decision time via YamlManager cache

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/YamlManager.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/logger.php');

/**
 * MVP Agent Orchestrator
 *
 * Coordinates execution of 22 specialized agents for personalized student learning.
 *
 * Core Responsibilities:
 * 1. Agent Loading: Lazy-load active agents via YamlManager cache
 * 2. Rule Matching: Route student contexts to appropriate agents
 * 3. Decision Execution: Execute agent actions and interventions
 * 4. Metric Tracking: Record execution stats in agent_status table
 * 5. Audit Logging: Maintain decision trail in decision_log
 *
 * Performance Targets:
 * - Agent loading: <100ms for all 22 agents (via cache)
 * - Rule matching: <50ms per context
 * - Decision execution: <200ms total
 * - Memory usage: <2MB for all loaded agents
 */
class MVPAgentOrchestrator {
    /**
     * Loaded agents cache
     * Structure: [agent_id => yaml_data]
     */
    private $agents = [];

    /**
     * Database wrapper
     */
    private $db;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Agent status cache (from mdl_mvp_agent_status)
     * Structure: [agent_id => status_record]
     */
    private $agent_status = [];

    /**
     * Current user ID
     */
    private $user_id;

    /**
     * Constructor
     *
     * @param int $user_id User ID for tracking (default: current $USER->id)
     * @throws Exception if initialization fails
     */
    public function __construct($user_id = null) {
        global $USER;

        $this->db = new MVPDatabase();
        $this->logger = new MVPLogger('orchestrator');
        $this->user_id = $user_id ?? $USER->id;

        $this->logger->info("Orchestrator initialized", [
            'user_id' => $this->user_id
        ]);
    }

    /**
     * Load all active agents from database + YAML files
     *
     * Uses YamlManager for cached loading and mdl_mvp_agent_status for active status.
     *
     * @return int Number of agents loaded
     * @throws Exception on load failure
     */
    public function load_active_agents() {
        $start_time = microtime(true);

        try {
            // Load agents via YamlManager (cached)
            $this->agents = YamlManager::load_active_agents();

            // Load agent status records from database
            global $DB;
            $status_records = $DB->get_records_sql(
                "SELECT agent_id, agent_name, is_active, execution_count, success_count, error_count
                 FROM mdl_mvp_agent_status
                 WHERE is_active = 1
                 ORDER BY agent_id"
            );

            foreach ($status_records as $record) {
                $this->agent_status[$record->agent_id] = $record;
            }

            $duration_ms = (microtime(true) - $start_time) * 1000;

            $this->logger->info("Active agents loaded", [
                'count' => count($this->agents),
                'status_count' => count($this->agent_status),
                'duration_ms' => round($duration_ms, 2)
            ]);

            return count($this->agents);

        } catch (Exception $e) {
            $this->logger->error("Failed to load active agents", $e, [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            throw $e;
        }
    }

    /**
     * Route student context to appropriate agent
     *
     * Matches context against agent rules and returns best matching agent.
     *
     * @param array $context Student context data
     *   Example: [
     *     'student_id' => 123,
     *     'calm_score' => 85,
     *     'exam_approaching' => true,
     *     'learning_emotion' => 'frustrated',
     *     'activity_type' => 'problem_solving'
     *   ]
     * @return array|null Matched agent data or null if no match
     *   Returns: ['agent_id' => 'agent08_calmness', 'matched_rule' => {...}, 'confidence' => 0.95]
     */
    public function route_context($context) {
        $start_time = microtime(true);

        if (empty($this->agents)) {
            $this->load_active_agents();
        }

        $best_match = null;
        $highest_confidence = 0.0;

        // Iterate through all loaded agents
        foreach ($this->agents as $agent_id => $yaml_data) {
            // Skip agents with no rules
            if (empty($yaml_data['rules'])) {
                continue;
            }

            // Evaluate each rule for this agent
            foreach ($yaml_data['rules'] as $rule) {
                $match_result = $this->evaluate_rule($rule, $context);

                if ($match_result['matched'] && $match_result['confidence'] > $highest_confidence) {
                    $best_match = [
                        'agent_id' => $agent_id,
                        'agent_name' => $this->agent_status[$agent_id]->agent_name ?? $agent_id,
                        'matched_rule' => $rule,
                        'confidence' => $match_result['confidence']
                    ];
                    $highest_confidence = $match_result['confidence'];
                }
            }
        }

        $duration_ms = (microtime(true) - $start_time) * 1000;

        if ($best_match) {
            $this->logger->info("Context routed to agent", [
                'agent_id' => $best_match['agent_id'],
                'confidence' => $best_match['confidence'],
                'duration_ms' => round($duration_ms, 2)
            ]);
        } else {
            $this->logger->warning("No agent matched context", null, [
                'context' => $context,
                'duration_ms' => round($duration_ms, 2)
            ]);
        }

        return $best_match;
    }

    /**
     * Evaluate rule against student context
     *
     * @param array $rule Rule data from YAML
     * @param array $context Student context
     * @return array Match result ['matched' => bool, 'confidence' => float]
     */
    private function evaluate_rule($rule, $context) {
        // Extract rule conditions
        $conditions = $rule['conditions'] ?? [];

        // If no conditions, rule always matches (with rule confidence)
        if (empty($conditions)) {
            return [
                'matched' => true,
                'confidence' => $rule['confidence'] ?? 0.5
            ];
        }

        $conditions_met = 0;
        $total_conditions = count($conditions);

        foreach ($conditions as $condition) {
            if ($this->evaluate_condition($condition, $context)) {
                $conditions_met++;
            }
        }

        // Calculate match confidence
        $match_ratio = $total_conditions > 0 ? ($conditions_met / $total_conditions) : 0;
        $rule_confidence = $rule['confidence'] ?? 0.8;

        // Rule matched if at least 70% of conditions met
        $matched = $match_ratio >= 0.7;
        $confidence = $matched ? ($match_ratio * $rule_confidence) : 0.0;

        return [
            'matched' => $matched,
            'confidence' => $confidence
        ];
    }

    /**
     * Evaluate single condition
     *
     * @param mixed $condition Condition definition (string or array)
     * @param array $context Student context
     * @return bool True if condition met
     */
    private function evaluate_condition($condition, $context) {
        // Simple string matching for now
        if (is_string($condition)) {
            // Example: "calm_score < 75"
            if (preg_match('/^(\w+)\s*([<>=!]+)\s*(.+)$/', $condition, $matches)) {
                $field = $matches[1];
                $operator = $matches[2];
                $value = $matches[3];

                if (!isset($context[$field])) {
                    return false;
                }

                $context_value = $context[$field];

                switch ($operator) {
                    case '<':
                        return $context_value < floatval($value);
                    case '>':
                        return $context_value > floatval($value);
                    case '>=':
                        return $context_value >= floatval($value);
                    case '<=':
                        return $context_value <= floatval($value);
                    case '==':
                    case '=':
                        return $context_value == $value;
                    case '!=':
                        return $context_value != $value;
                }
            }

            // Fallback: check if condition string appears in context values
            foreach ($context as $value) {
                if (is_string($value) && strpos($value, $condition) !== false) {
                    return true;
                }
            }

            return false;
        }

        // Array conditions (more complex logic can be added here)
        if (is_array($condition)) {
            // Example: ['field' => 'calm_score', 'operator' => '<', 'value' => 75]
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '==';
            $value = $condition['value'] ?? null;

            if ($field && isset($context[$field])) {
                $context_value = $context[$field];

                switch ($operator) {
                    case '<':
                        return $context_value < $value;
                    case '>':
                        return $context_value > $value;
                    case '>=':
                        return $context_value >= $value;
                    case '<=':
                        return $context_value <= $value;
                    case '==':
                        return $context_value == $value;
                    case '!=':
                        return $context_value != $value;
                }
            }
        }

        return false;
    }

    /**
     * Execute agent decision
     *
     * @param string $agent_id Agent identifier
     * @param array $context Student context
     * @param array $matched_rule Matched rule data
     * @return array Execution result
     * @throws Exception on execution failure
     */
    public function execute_decision($agent_id, $context, $matched_rule) {
        $start_time = microtime(true);

        global $DB;

        try {
            // Extract action from rule
            $action = $matched_rule['action'] ?? 'none';
            $confidence = $matched_rule['confidence'] ?? 0.8;
            $rationale = $matched_rule['rationale'] ?? 'Rule matched';

            // Prepare decision record
            $decision_record = new stdClass();
            $decision_record->student_id = $context['student_id'] ?? null;
            $decision_record->rule_id = $matched_rule['rule_id'] ?? 'unknown';
            $decision_record->agent_id = $agent_id;
            $decision_record->action = $action;
            $decision_record->confidence = $confidence;
            $decision_record->rationale = $rationale;
            $decision_record->context_data = json_encode($context);
            $decision_record->executed_by = $this->user_id;
            $decision_record->timestamp = date('Y-m-d H:i:s');
            $decision_record->created_at = date('Y-m-d H:i:s');

            // Insert decision log (Moodle DML automatically adds mdl_ prefix)
            $decision_id = $DB->insert_record('mvp_decision_log', $decision_record);

            // Update agent execution statistics
            $this->update_agent_stats($agent_id, true, microtime(true) - $start_time);

            $duration_ms = (microtime(true) - $start_time) * 1000;

            $this->logger->info("Decision executed", [
                'decision_id' => $decision_id,
                'agent_id' => $agent_id,
                'action' => $action,
                'confidence' => $confidence,
                'duration_ms' => round($duration_ms, 2)
            ]);

            return [
                'success' => true,
                'decision_id' => $decision_id,
                'action' => $action,
                'confidence' => $confidence,
                'duration_ms' => round($duration_ms, 2)
            ];

        } catch (Exception $e) {
            // Update agent error statistics
            $this->update_agent_stats($agent_id, false, microtime(true) - $start_time, $e->getMessage());

            $this->logger->error("Decision execution failed", $e, [
                'agent_id' => $agent_id,
                'context' => $context
            ]);

            throw $e;
        }
    }

    /**
     * Update agent execution statistics
     *
     * @param string $agent_id Agent identifier
     * @param bool $success Whether execution succeeded
     * @param float $execution_time Execution time in seconds
     * @param string|null $error_msg Error message if failed
     */
    private function update_agent_stats($agent_id, $success, $execution_time, $error_msg = null) {
        global $DB;

        try {
            // Get current stats
            $stats = $DB->get_record('mdl_mvp_agent_status', ['agent_id' => $agent_id]);

            if (!$stats) {
                $this->logger->warning("Agent not found in status table", null, [
                    'agent_id' => $agent_id
                ]);
                return;
            }

            // Update counters
            $stats->execution_count = ($stats->execution_count ?? 0) + 1;

            if ($success) {
                $stats->success_count = ($stats->success_count ?? 0) + 1;
            } else {
                $stats->error_count = ($stats->error_count ?? 0) + 1;
                $stats->last_error_at = date('Y-m-d H:i:s');
                $stats->last_error_msg = $error_msg;
            }

            // Update average execution time (moving average)
            $execution_time_ms = $execution_time * 1000;
            $current_avg = $stats->avg_execution_time ?? 0;
            $stats->avg_execution_time = (($current_avg * ($stats->execution_count - 1)) + $execution_time_ms) / $stats->execution_count;

            $stats->last_execution_at = date('Y-m-d H:i:s');
            $stats->updated_at = date('Y-m-d H:i:s');

            // Update database
            $DB->update_record('mdl_mvp_agent_status', $stats);

            $this->logger->info("Agent stats updated", [
                'agent_id' => $agent_id,
                'execution_count' => $stats->execution_count,
                'success_rate' => round(($stats->success_count / $stats->execution_count) * 100, 2)
            ]);

        } catch (Exception $e) {
            $this->logger->error("Failed to update agent stats", $e, [
                'agent_id' => $agent_id
            ]);
        }
    }

    /**
     * Process student context end-to-end
     *
     * High-level API that:
     * 1. Routes context to best agent
     * 2. Executes agent decision
     * 3. Returns result
     *
     * @param array $context Student context data
     * @return array|null Execution result or null if no match
     */
    public function process_context($context) {
        $start_time = microtime(true);

        try {
            // Step 1: Route to best agent
            $match = $this->route_context($context);

            if (!$match) {
                $this->logger->info("No agent matched - no action taken", [
                    'context' => $context
                ]);
                return null;
            }

            // Step 2: Execute decision
            $result = $this->execute_decision(
                $match['agent_id'],
                $context,
                $match['matched_rule']
            );

            $total_duration_ms = (microtime(true) - $start_time) * 1000;

            $this->logger->info("Context processing complete", [
                'agent_id' => $match['agent_id'],
                'action' => $result['action'],
                'total_duration_ms' => round($total_duration_ms, 2)
            ]);

            return array_merge($result, [
                'agent_id' => $match['agent_id'],
                'agent_name' => $match['agent_name']
            ]);

        } catch (Exception $e) {
            $this->logger->error("Context processing failed", $e, [
                'context' => $context
            ]);
            throw $e;
        }
    }

    /**
     * Get agent statistics
     *
     * @param string|null $agent_id Specific agent ID or null for all
     * @return array Agent statistics
     */
    public function get_agent_stats($agent_id = null) {
        global $DB;

        try {
            if ($agent_id) {
                $stats = $DB->get_record('mdl_mvp_agent_status', ['agent_id' => $agent_id]);
                return $stats ? (array)$stats : null;
            } else {
                $stats = $DB->get_records('mdl_mvp_agent_status', null, 'agent_id');
                return array_map(function($stat) {
                    return (array)$stat;
                }, $stats);
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to get agent stats", $e, [
                'agent_id' => $agent_id
            ]);
            return null;
        }
    }

    /**
     * Get orchestrator status
     *
     * @return array Status information
     */
    public function get_status() {
        return [
            'agents_loaded' => count($this->agents),
            'active_agents' => count($this->agent_status),
            'user_id' => $this->user_id,
            'cache_stats' => YamlManager::get_stats()
        ];
    }
}

/**
 * Database Tables Used:
 * - mdl_mvp_decision_log: Decision execution log (student_id, rule_id, agent_id, action, confidence, timestamp)
 * - mdl_mvp_agent_status: Agent execution statistics (agent_id, execution_count, success_count, error_count, avg_execution_time)
 *
 * Dependencies:
 * - YamlManager: Cached YAML rule loading
 * - MVPDatabase: Moodle DB wrapper
 * - MVPLogger: Structured logging
 *
 * Performance Notes:
 * - Agent loading: <100ms via YamlManager cache
 * - Rule matching: <50ms per context
 * - Decision execution: <200ms total
 * - Memory usage: <2MB for all agents
 */
?>
