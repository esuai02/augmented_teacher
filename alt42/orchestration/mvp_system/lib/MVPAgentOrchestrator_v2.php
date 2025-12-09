<?php
// 파일: mvp_system/lib/MVPAgentOrchestrator_v2.php (Line 1)
// Mathking Agentic MVP System - Agent Orchestrator v2 (Rule Ontology Edition)
//
// Purpose: Graph-based agent coordination with cascade propagation
// Architecture: Rule Ontology + BFS Cascade + Priority Conflict Resolution
// Performance: <650ms decision time (was <300ms, +350ms for graph reasoning)

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/YamlManager.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/logger.php');
require_once(__DIR__ . '/RuleGraphBuilder.php');
require_once(__DIR__ . '/CascadeEngine.php');
require_once(__DIR__ . '/ConflictResolver.php');

/**
 * MVP Agent Orchestrator v2 - Rule Ontology Edition
 *
 * Graph-based orchestrator with semantic rule relationships and cascade propagation.
 *
 * New Capabilities (v2):
 * - Semantic Rule Graph: Rules connected via typed relationships (causes, depends_on, contradicts, complements)
 * - Cascade Propagation: Chain interventions following relationship edges
 * - Conflict Resolution: Priority-based resolution for contradicting rules
 * - Dynamic Relationships: DB-configurable relationships override YAML
 *
 * Example Cascade:
 * 1. Student context → agent08_calmness_low matches (confidence: 1.0)
 * 2. Cascade through "causes" edge → agent13_dropout_risk (confidence: 0.85)
 * 3. Both interventions executed with appropriate confidence levels
 *
 * Backward Compatibility:
 * - Rules without relationships work exactly as before
 * - Single-rule execution path preserved
 * - Same API signatures for existing callers
 *
 * Performance Targets:
 * - Agent loading: <120ms (was <100ms, +20%)
 * - Rule matching: <80ms (was <50ms, +60%)
 * - Cascade execution: <450ms for depth 3-5
 * - Total: <650ms (was <300ms, acceptable for better reasoning)
 */
class MVPAgentOrchestrator_v2 {
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
     * Graph components
     */
    private $graph_builder;
    private $cascade_engine;
    private $conflict_resolver;

    /**
     * Configuration
     */
    private $enable_cascades = true; // Enable cascade propagation
    private $enable_conflict_resolution = true; // Enable conflict resolution

    /**
     * Constructor
     *
     * @param int $user_id User ID for tracking (default: current $USER->id)
     * @param array $config Optional configuration overrides
     * @throws Exception if initialization fails
     */
    public function __construct($user_id = null, $config = []) {
        global $USER, $DB;

        $this->db = new MVPDatabase();
        $this->logger = new MVPLogger('orchestrator_v2');
        $this->user_id = $user_id ?? $USER->id;

        // Initialize graph components
        $this->graph_builder = new RuleGraphBuilder($DB, $this->logger);
        $this->cascade_engine = new CascadeEngine($DB, $this->logger, $config['cascade'] ?? []);
        $this->conflict_resolver = new ConflictResolver($DB, $this->logger, $config['conflict'] ?? []);

        // Apply configuration overrides
        if (isset($config['enable_cascades'])) {
            $this->enable_cascades = (bool)$config['enable_cascades'];
        }
        if (isset($config['enable_conflict_resolution'])) {
            $this->enable_conflict_resolution = (bool)$config['enable_conflict_resolution'];
        }

        $this->logger->info("Orchestrator v2 initialized", [
            'user_id' => $this->user_id,
            'cascades_enabled' => $this->enable_cascades,
            'conflict_resolution_enabled' => $this->enable_conflict_resolution
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
     * Find all matching rules across all agents
     *
     * Replaces single-agent route_context() with multi-rule matching.
     * Returns ALL matching rules (not just best), enabling conflict resolution.
     *
     * @param array $context Student context data
     * @return array Matched rules with metadata
     *   Returns: [
     *     ['agent_id' => '...', 'rule' => {...}, 'confidence' => 0.95],
     *     ...
     *   ]
     */
    private function find_matching_rules($context) {
        $start_time = microtime(true);

        if (empty($this->agents)) {
            $this->load_active_agents();
        }

        $matched_rules = [];

        // Iterate through all loaded agents
        foreach ($this->agents as $agent_id => $yaml_data) {
            // Skip agents with no rules
            if (empty($yaml_data['rules'])) {
                continue;
            }

            // Evaluate each rule for this agent
            foreach ($yaml_data['rules'] as $rule) {
                $match_result = $this->evaluate_rule($rule, $context);

                if ($match_result['matched']) {
                    $matched_rules[] = [
                        'agent_id' => $agent_id,
                        'agent_name' => $this->agent_status[$agent_id]->agent_name ?? $agent_id,
                        'rule' => $rule,
                        'confidence' => $match_result['confidence']
                    ];
                }
            }
        }

        $duration_ms = (microtime(true) - $start_time) * 1000;

        $this->logger->info("Rule matching complete", [
            'matched_count' => count($matched_rules),
            'duration_ms' => round($duration_ms, 2)
        ]);

        return $matched_rules;
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
     * Process student context with graph-based reasoning
     *
     * New v2 flow:
     * 1. Find all matching rules across agents
     * 2. Build rule graph for matched agent
     * 3. Resolve conflicts using priority + confidence
     * 4. Execute best rule
     * 5. Propagate cascade through relationship edges
     * 6. Log all executions
     *
     * @param array $context Student context data
     * @return array|null Execution result including cascade chain
     */
    public function process_context($context) {
        $start_time = microtime(true);

        try {
            // Step 1: Find all matching rules
            $matched_rules = $this->find_matching_rules($context);

            if (empty($matched_rules)) {
                $this->logger->info("No rules matched - no action taken", [
                    'context' => $context
                ]);
                return null;
            }

            // Step 2: Group rules by agent and build graph for primary agent
            // Use highest confidence rule's agent as primary
            usort($matched_rules, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });

            $primary_match = $matched_rules[0];
            $primary_agent_id = $primary_match['agent_id'];

            // Build graph for primary agent
            $agent_rules = $this->agents[$primary_agent_id]['rules'] ?? [];
            $graph = $this->graph_builder->build_graph($agent_rules, $primary_agent_id);

            $this->logger->info("Rule graph built", [
                'agent_id' => $primary_agent_id,
                'nodes' => count($graph['nodes']),
                'edges' => count($graph['edges'])
            ]);

            // Step 3: Resolve conflicts if enabled
            $resolved_rules = $matched_rules;
            if ($this->enable_conflict_resolution && count($matched_rules) > 1) {
                // Convert to rule format expected by ConflictResolver
                $candidate_rules = array_map(function($match) {
                    $rule = $match['rule'];
                    $rule['confidence'] = $match['confidence']; // Override with match confidence
                    return $rule;
                }, $matched_rules);

                $resolved = $this->conflict_resolver->resolve($candidate_rules, $graph, $context);

                // Convert back to match format
                $resolved_rules = array_map(function($rule) use ($matched_rules) {
                    // Find original match
                    foreach ($matched_rules as $match) {
                        if ($match['rule']['rule_id'] === $rule['rule_id']) {
                            return $match;
                        }
                    }
                    return null;
                }, $resolved);

                $resolved_rules = array_filter($resolved_rules); // Remove nulls
            }

            if (empty($resolved_rules)) {
                $this->logger->warning("All rules removed by conflict resolution", null, [
                    'original_count' => count($matched_rules)
                ]);
                return null;
            }

            // Step 4: Execute best rule (highest confidence after resolution)
            usort($resolved_rules, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });

            $best_match = $resolved_rules[0];
            $initial_result = $this->execute_decision(
                $best_match['agent_id'],
                $context,
                $best_match['rule'],
                null, // No cascade confidence override
                null, // No parent decision
                0     // Initial depth = 0
            );

            // Step 5: Execute cascade if enabled
            $cascade_results = [];
            if ($this->enable_cascades) {
                $initial_rule_id = $best_match['rule']['rule_id'];

                // Track decision IDs for cascade chain linking
                $execution_results = [];
                $execution_results[$initial_rule_id] = $initial_result;

                $cascade_executed = $this->cascade_engine->propagate(
                    $initial_rule_id,
                    $context,
                    $graph,
                    function($rule, $context, $confidence) use ($best_match, &$execution_results) {
                        // CascadeEngine doesn't pass depth/parent, so we infer from execution_results
                        // For now, simple cascade without full parent tracking
                        // TODO: Enhance CascadeEngine to pass depth and parent_rule_id
                        return $this->execute_decision(
                            $best_match['agent_id'],
                            $context,
                            $rule,
                            $confidence,    // Propagated confidence
                            null,          // Parent decision ID (simplified for now)
                            1              // Cascade depth (simplified to 1 for all cascades)
                        );
                    }
                );

                $cascade_results = $cascade_executed;
            }

            $total_duration_ms = (microtime(true) - $start_time) * 1000;

            $this->logger->info("Context processing complete", [
                'agent_id' => $best_match['agent_id'],
                'initial_action' => $initial_result['action'],
                'cascade_depth' => count($cascade_results),
                'total_duration_ms' => round($total_duration_ms, 2)
            ]);

            return [
                'success' => true,
                'agent_id' => $best_match['agent_id'],
                'agent_name' => $best_match['agent_name'],
                'initial_result' => $initial_result,
                'cascade_chain' => $cascade_results,
                'cascade_enabled' => $this->enable_cascades,
                'conflicts_resolved' => count($matched_rules) - count($resolved_rules),
                'total_duration_ms' => round($total_duration_ms, 2)
            ];

        } catch (Exception $e) {
            $this->logger->error("Context processing failed", $e, [
                'context' => $context,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            throw $e;
        }
    }

    /**
     * Execute agent decision
     *
     * @param string $agent_id Agent identifier
     * @param array $context Student context
     * @param array $rule Rule data
     * @param float|null $cascade_confidence Optional cascade confidence override
     * @param int|null $parent_decision_id Parent decision ID for cascade tracking
     * @param int $cascade_depth Cascade depth (0=initial)
     * @return array Execution result
     * @throws Exception on execution failure
     */
    private function execute_decision($agent_id, $context, $rule, $cascade_confidence = null, $parent_decision_id = null, $cascade_depth = 0) {
        $start_time = microtime(true);

        global $DB;

        try {
            // Extract action from rule
            $action = $rule['action'] ?? 'none';
            $confidence = $cascade_confidence ?? ($rule['confidence'] ?? 0.8);
            $rationale = $rule['rationale'] ?? 'Rule matched';

            // Get agent name
            $agent_name = $this->agent_status[$agent_id]->agent_name ?? null;

            // Validate and sanitize data for database constraints
            // student_id: BIGINT(10) UNSIGNED NOT NULL
            $student_id = $context['student_id'] ?? $this->user_id;
            if (empty($student_id)) {
                throw new Exception("execute_decision error: student_id is required (MVPAgentOrchestrator_v2.php:" . __LINE__ . ")");
            }

            // agent_id: VARCHAR(50) NOT NULL
            if (empty($agent_id)) {
                throw new Exception("execute_decision error: agent_id is required (MVPAgentOrchestrator_v2.php:" . __LINE__ . ")");
            }
            $agent_id_safe = substr($agent_id, 0, 50);

            // agent_name: VARCHAR(100) DEFAULT NULL
            $agent_name_safe = $agent_name ? substr($agent_name, 0, 100) : null;

            // rule_id: VARCHAR(100) NOT NULL
            $rule_id = $rule['rule_id'] ?? 'unknown';
            $rule_id_safe = substr($rule_id, 0, 100);

            // action: VARCHAR(50) NOT NULL
            $action_safe = substr($action, 0, 50);

            // confidence: DECIMAL(5,4) NOT NULL - max 9.9999, 4 decimal places
            $confidence_safe = min(max(floatval($confidence), -9.9999), 9.9999);
            $confidence_safe = round($confidence_safe, 4);

            // Prepare decision record (match table schema)
            $decision_record = new stdClass();
            $decision_record->student_id = intval($student_id);
            $decision_record->agent_id = $agent_id_safe;
            $decision_record->agent_name = $agent_name_safe;
            $decision_record->rule_id = $rule_id_safe;
            $decision_record->action = $action_safe;
            $decision_record->confidence = $confidence_safe;
            $decision_record->context_data = json_encode($context);
            $decision_record->result_data = json_encode([
                'success' => true,
                'action' => $action,
                'confidence' => $confidence
            ]);
            $decision_record->is_cascade = ($cascade_depth > 0) ? 1 : 0;
            $decision_record->cascade_depth = $cascade_depth;
            $decision_record->parent_decision_id = $parent_decision_id;
            $decision_record->execution_time_ms = null; // Will update after execution
            $decision_record->created_at = date('Y-m-d H:i:s');
            $decision_record->notes = $rationale;

            // DEBUG: Log the exact record being inserted
            $this->logger->debug("Attempting to insert decision record", [], [
                'record' => (array)$decision_record,
                'field_types' => [
                    'student_id' => gettype($decision_record->student_id),
                    'agent_id' => gettype($decision_record->agent_id),
                    'agent_name' => gettype($decision_record->agent_name),
                    'rule_id' => gettype($decision_record->rule_id),
                    'action' => gettype($decision_record->action),
                    'confidence' => gettype($decision_record->confidence),
                    'context_data' => gettype($decision_record->context_data),
                    'result_data' => gettype($decision_record->result_data),
                    'is_cascade' => gettype($decision_record->is_cascade),
                    'cascade_depth' => gettype($decision_record->cascade_depth),
                    'parent_decision_id' => gettype($decision_record->parent_decision_id),
                    'execution_time_ms' => gettype($decision_record->execution_time_ms),
                    'created_at' => gettype($decision_record->created_at),
                    'notes' => gettype($decision_record->notes)
                ]
            ]);

            // Insert decision log
            try {
                // ✅ Apply null handling for execution_time_ms (NOT NULL constraint safety)
                if ($decision_record->execution_time_ms === null) {
                    $decision_record->execution_time_ms = 0.00;
                }

                // ✅ Moodle DML automatically adds mdl_ prefix - use table name without prefix
                $decision_id = $DB->insert_record('mvp_decision_log', $decision_record);
                $this->logger->debug("✅ Decision inserted", [], ['decision_id' => $decision_id]);
            } catch (Exception $insert_error) {
                $this->logger->error("💥 Database insert failed", $insert_error, [
                    'sql_table' => 'mdl_mvp_decision_log',
                    'error_message' => $insert_error->getMessage(),
                    'error_code' => $insert_error->getCode(),
                    'record_json' => json_encode((array)$decision_record, JSON_PRETTY_PRINT)
                ]);
                throw new Exception("Failed to insert decision log: " . $insert_error->getMessage() . " (MVPAgentOrchestrator_v2.php:" . __LINE__ . ")", 0, $insert_error);
            }

            // Update agent execution statistics
            $this->update_agent_stats($agent_id, true, microtime(true) - $start_time);

            $duration_ms = (microtime(true) - $start_time) * 1000;

            // Update execution time in decision log
            $DB->set_field('mvp_decision_log', 'execution_time_ms', round($duration_ms, 2), ['id' => $decision_id]);

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
                'context' => $context,
                'file' => __FILE__,
                'line' => __LINE__
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

        } catch (Exception $e) {
            $this->logger->error("Failed to update agent stats", $e, [
                'agent_id' => $agent_id,
                'file' => __FILE__,
                'line' => __LINE__
            ]);
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
            'version' => '2.0',
            'agents_loaded' => count($this->agents),
            'active_agents' => count($this->agent_status),
            'user_id' => $this->user_id,
            'cache_stats' => YamlManager::get_stats(),
            'features' => [
                'cascades' => $this->enable_cascades,
                'conflict_resolution' => $this->enable_conflict_resolution
            ]
        ];
    }
}

/**
 * File Location: mvp_system/lib/MVPAgentOrchestrator_v2.php (Line 1)
 * Purpose: Graph-based agent orchestration with cascade propagation
 *
 * Database Tables Used:
 * - mdl_mvp_decision_log: Decision execution log (now includes cascade decisions)
 * - mdl_mvp_agent_status: Agent execution statistics
 * - mdl_mvp_rule_relations: Rule relationship definitions (via RuleGraphBuilder)
 *
 * Dependencies:
 * - YamlManager: Cached YAML rule loading
 * - RuleGraphBuilder: YAML + DB graph construction
 * - CascadeEngine: BFS cascade propagation
 * - ConflictResolver: Priority-based conflict resolution
 * - MVPDatabase: Moodle DB wrapper
 * - MVPLogger: Structured logging
 *
 * New Features (v2):
 * - Semantic Rule Graph with typed relationships
 * - Cascade Propagation following relationship edges
 * - Conflict Resolution via priority + confidence
 * - Dynamic DB-configurable relationships
 *
 * Backward Compatibility:
 * - Rules without relationships work as before
 * - Same API for existing callers
 * - Cascades and conflicts can be disabled via config
 *
 * Performance Notes:
 * - Agent loading: <120ms (was <100ms)
 * - Rule matching: <80ms (was <50ms)
 * - Cascade execution: <450ms
 * - Total: <650ms (was <300ms)
 */
?>
