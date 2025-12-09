<?php
// 파일: mvp_system/lib/RuleGraphBuilder.php (Line 1)
// Mathking Agentic MVP System - Rule Graph Builder
//
// Purpose: Build execution graphs from YAML rules + DB relations
// Usage: Internal component for MVPAgentOrchestrator
// Author: MVP Development Team

/**
 * RuleGraphBuilder
 *
 * Constructs executable rule graphs by combining:
 * 1. YAML rules (loaded via YamlManager) - Static, version-controlled relationships
 * 2. DB relations (mdl_mvp_rule_relations) - Dynamic, runtime-modifiable relationships
 *
 * Design Principles:
 * - DB relations OVERRIDE YAML for dynamic control
 * - Backward compatible: Rules without relationships work as-is
 * - Efficient: Single DB query per agent, indexed lookups
 * - Cacheable: Graph structure can be cached alongside YAML
 *
 * Performance Targets:
 * - Graph building: <20ms per agent (22 agents = <440ms total)
 * - DB query: <10ms (indexed by from_rule_id)
 * - Memory: ~50KB per agent graph (22 agents = ~1.1MB)
 */
class RuleGraphBuilder {
    /**
     * Database wrapper
     */
    private $db;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Constructor
     *
     * @param object $db Moodle database object
     * @param MVPLogger $logger Logger instance
     */
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Build execution graph from YAML rules + DB relations
     *
     * Graph Structure:
     * [
     *   'nodes' => [
     *     'rule_id' => [
     *       'rule' => [...],              // Original YAML rule
     *       'yaml_relations' => [...],    // Relationships from YAML
     *       'db_relations' => [...]       // Relationships from DB (overrides)
     *     ]
     *   ],
     *   'edges' => [
     *     [
     *       'from' => 'rule_id',
     *       'to' => 'rule_id',
     *       'type' => 'causes|depends_on|contradicts|complements',
     *       'weight' => 0.0-1.0,
     *       'source' => 'yaml|database'
     *     ]
     *   ]
     * ]
     *
     * @param array $rules Parsed YAML rules from YamlManager
     * @param string $agent_id Agent identifier for logging
     * @return array Graph structure with nodes and edges
     */
    public function build_graph($rules, $agent_id = 'unknown') {
        $start = microtime(true);

        $graph = [
            'nodes' => [],
            'edges' => []
        ];

        // Step 1: Build nodes from YAML rules
        foreach ($rules as $rule) {
            $rule_id = $rule['rule_id'] ?? null;

            if (!$rule_id) {
                $this->logger->warning("Rule missing rule_id", [], ['agent_id' => $agent_id]);
                continue;
            }

            $graph['nodes'][$rule_id] = [
                'rule' => $rule,
                'yaml_relations' => $rule['relationships'] ?? [],
                'db_relations' => []
            ];
        }

        $node_count = count($graph['nodes']);

        if ($node_count === 0) {
            $this->logger->warning("No valid rules for graph", [], ['agent_id' => $agent_id]);
            return $graph;
        }

        // Step 2: Load DB relations for all rules in this agent
        $rule_ids = array_keys($graph['nodes']);
        $db_relations = $this->load_db_relations($rule_ids);

        // Step 3: Process DB relations (these OVERRIDE YAML)
        $db_edge_count = 0;
        foreach ($db_relations as $rel) {
            // Validate that both rules exist in current agent
            if (!isset($graph['nodes'][$rel->from_rule_id]) || !isset($graph['nodes'][$rel->to_rule_id])) {
                $this->logger->warning("Relation references unknown rule", [], [
                    'from' => $rel->from_rule_id,
                    'to' => $rel->to_rule_id
                ]);
                continue;
            }

            $graph['edges'][] = [
                'from' => $rel->from_rule_id,
                'to' => $rel->to_rule_id,
                'type' => $rel->relation_type,
                'weight' => floatval($rel->weight),
                'source' => 'database',
                'active' => (bool)$rel->is_active
            ];

            $db_edge_count++;

            // Track DB relations in node for debugging
            $graph['nodes'][$rel->from_rule_id]['db_relations'][] = [
                'to' => $rel->to_rule_id,
                'type' => $rel->relation_type,
                'weight' => $rel->weight
            ];
        }

        // Step 4: Add YAML relations NOT overridden by DB
        $yaml_edge_count = 0;
        foreach ($graph['nodes'] as $rule_id => $node) {
            $yaml_relations = $node['yaml_relations'];

            if (empty($yaml_relations)) {
                continue; // No YAML relations
            }

            // Process 'causes' relationships
            if (isset($yaml_relations['causes']) && is_array($yaml_relations['causes'])) {
                foreach ($yaml_relations['causes'] as $target_rule_id) {
                    // Check if DB already defines this edge
                    if ($this->edge_exists($graph['edges'], $rule_id, $target_rule_id, 'causes')) {
                        continue; // DB overrides YAML
                    }

                    // Validate target exists
                    if (!isset($graph['nodes'][$target_rule_id])) {
                        $this->logger->warning("YAML relation references unknown rule", [], [
                            'from' => $rule_id,
                            'to' => $target_rule_id,
                            'type' => 'causes'
                        ]);
                        continue;
                    }

                    $graph['edges'][] = [
                        'from' => $rule_id,
                        'to' => $target_rule_id,
                        'type' => 'causes',
                        'weight' => floatval($yaml_relations['cascade_weight'] ?? 1.0),
                        'source' => 'yaml',
                        'active' => true
                    ];

                    $yaml_edge_count++;
                }
            }

            // Process 'depends_on' relationships
            if (isset($yaml_relations['depends_on']) && is_array($yaml_relations['depends_on'])) {
                foreach ($yaml_relations['depends_on'] as $dependency_rule_id) {
                    if ($this->edge_exists($graph['edges'], $rule_id, $dependency_rule_id, 'depends_on')) {
                        continue;
                    }

                    if (!isset($graph['nodes'][$dependency_rule_id])) {
                        $this->logger->warning("YAML relation references unknown rule", [], [
                            'from' => $rule_id,
                            'to' => $dependency_rule_id,
                            'type' => 'depends_on'
                        ]);
                        continue;
                    }

                    $graph['edges'][] = [
                        'from' => $rule_id,
                        'to' => $dependency_rule_id,
                        'type' => 'depends_on',
                        'weight' => 1.0,
                        'source' => 'yaml',
                        'active' => true
                    ];

                    $yaml_edge_count++;
                }
            }

            // Process 'contradicts' relationships
            if (isset($yaml_relations['contradicts']) && is_array($yaml_relations['contradicts'])) {
                foreach ($yaml_relations['contradicts'] as $conflict_rule_id) {
                    if ($this->edge_exists($graph['edges'], $rule_id, $conflict_rule_id, 'contradicts')) {
                        continue;
                    }

                    if (!isset($graph['nodes'][$conflict_rule_id])) {
                        $this->logger->warning("YAML relation references unknown rule", [], [
                            'from' => $rule_id,
                            'to' => $conflict_rule_id,
                            'type' => 'contradicts'
                        ]);
                        continue;
                    }

                    $graph['edges'][] = [
                        'from' => $rule_id,
                        'to' => $conflict_rule_id,
                        'type' => 'contradicts',
                        'weight' => 1.0,
                        'source' => 'yaml',
                        'active' => true
                    ];

                    $yaml_edge_count++;
                }
            }

            // Process 'complements' relationships
            if (isset($yaml_relations['complements']) && is_array($yaml_relations['complements'])) {
                foreach ($yaml_relations['complements'] as $complement_rule_id) {
                    if ($this->edge_exists($graph['edges'], $rule_id, $complement_rule_id, 'complements')) {
                        continue;
                    }

                    if (!isset($graph['nodes'][$complement_rule_id])) {
                        $this->logger->warning("YAML relation references unknown rule", [], [
                            'from' => $rule_id,
                            'to' => $complement_rule_id,
                            'type' => 'complements'
                        ]);
                        continue;
                    }

                    $graph['edges'][] = [
                        'from' => $rule_id,
                        'to' => $complement_rule_id,
                        'type' => 'complements',
                        'weight' => floatval($yaml_relations['cascade_weight'] ?? 0.9),
                        'source' => 'yaml',
                        'active' => true
                    ];

                    $yaml_edge_count++;
                }
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->logger->info("Built rule graph", [], [
            'agent_id' => $agent_id,
            'nodes' => $node_count,
            'db_edges' => $db_edge_count,
            'yaml_edges' => $yaml_edge_count,
            'total_edges' => count($graph['edges']),
            'duration_ms' => round($duration, 2)
        ]);

        return $graph;
    }

    /**
     * Load DB relations for given rule IDs
     *
     * Uses indexed query on from_rule_id for fast lookup
     *
     * @param array $rule_ids Rule identifiers to load relations for
     * @return array DB relation records
     */
    private function load_db_relations($rule_ids) {
        if (empty($rule_ids)) {
            return [];
        }

        // Build SQL IN clause
        list($in_sql, $params) = $this->db->get_in_or_equal($rule_ids, SQL_PARAMS_NAMED);

        $sql = "SELECT id, from_rule_id, to_rule_id, relation_type, weight, is_active, notes
                FROM {mvp_rule_relations}
                WHERE from_rule_id {$in_sql} AND is_active = 1
                ORDER BY from_rule_id, relation_type";

        try {
            $records = $this->db->get_records_sql($sql, $params);
            return $records ?: [];
        } catch (Exception $e) {
            $this->logger->error("Failed to load DB relations", $e, [
                'rule_count' => count($rule_ids)
            ]);
            return [];
        }
    }

    /**
     * Check if edge already exists in graph
     *
     * @param array $edges Edge list
     * @param string $from From rule ID
     * @param string $to To rule ID
     * @param string $type Relationship type
     * @return bool True if edge exists
     */
    private function edge_exists($edges, $from, $to, $type) {
        foreach ($edges as $edge) {
            if ($edge['from'] === $from && $edge['to'] === $to && $edge['type'] === $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all outgoing edges from a rule
     *
     * @param array $edges Edge list
     * @param string $from_rule_id Source rule ID
     * @param string|null $type Optional filter by relationship type
     * @return array Matching edges
     */
    public function get_outgoing_edges($edges, $from_rule_id, $type = null) {
        $outgoing = [];

        foreach ($edges as $edge) {
            if ($edge['from'] === $from_rule_id && $edge['active']) {
                if ($type === null || $edge['type'] === $type) {
                    $outgoing[] = $edge;
                }
            }
        }

        return $outgoing;
    }

    /**
     * Get all incoming edges to a rule
     *
     * @param array $edges Edge list
     * @param string $to_rule_id Target rule ID
     * @param string|null $type Optional filter by relationship type
     * @return array Matching edges
     */
    public function get_incoming_edges($edges, $to_rule_id, $type = null) {
        $incoming = [];

        foreach ($edges as $edge) {
            if ($edge['to'] === $to_rule_id && $edge['active']) {
                if ($type === null || $edge['type'] === $type) {
                    $incoming[] = $edge;
                }
            }
        }

        return $incoming;
    }

    /**
     * Validate graph integrity
     *
     * Checks:
     * - No self-loops
     * - All edge endpoints exist as nodes
     * - No duplicate edges
     * - Weight ranges (0.0-1.0)
     *
     * @param array $graph Graph structure
     * @return array Validation results ['valid' => bool, 'errors' => array]
     */
    public function validate_graph($graph) {
        $errors = [];

        // Check for self-loops
        foreach ($graph['edges'] as $edge) {
            if ($edge['from'] === $edge['to']) {
                $errors[] = "Self-loop detected: {$edge['from']} → {$edge['to']}";
            }
        }

        // Check all edge endpoints exist
        foreach ($graph['edges'] as $edge) {
            if (!isset($graph['nodes'][$edge['from']])) {
                $errors[] = "Edge references non-existent node: {$edge['from']}";
            }
            if (!isset($graph['nodes'][$edge['to']])) {
                $errors[] = "Edge references non-existent node: {$edge['to']}";
            }
        }

        // Check weight ranges
        foreach ($graph['edges'] as $edge) {
            if ($edge['weight'] < 0.0 || $edge['weight'] > 1.0) {
                $errors[] = "Invalid weight: {$edge['from']} → {$edge['to']} (weight: {$edge['weight']})";
            }
        }

        // Check for duplicate edges
        $edge_signatures = [];
        foreach ($graph['edges'] as $edge) {
            $signature = "{$edge['from']}|{$edge['to']}|{$edge['type']}";
            if (isset($edge_signatures[$signature])) {
                $errors[] = "Duplicate edge: {$edge['from']} → {$edge['to']} ({$edge['type']})";
            }
            $edge_signatures[$signature] = true;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

/**
 * File Location: mvp_system/lib/RuleGraphBuilder.php (Line 1)
 * Purpose: Build execution graphs from YAML + DB relations
 *
 * Database Tables Used:
 * - mdl_mvp_rule_relations: Rule relationship definitions
 *
 * Dependencies:
 * - YamlManager.php: YAML rule loading (upstream)
 * - MVPLogger.php: Logging framework
 *
 * Classes:
 * - RuleGraphBuilder: Main graph construction class
 *
 * Key Methods:
 * - build_graph($rules, $agent_id): Construct executable graph
 * - load_db_relations($rule_ids): Query DB for relationships
 * - edge_exists($edges, $from, $to, $type): Check edge existence
 * - get_outgoing_edges($edges, $from_rule_id, $type): Find outgoing edges
 * - get_incoming_edges($edges, $to_rule_id, $type): Find incoming edges
 * - validate_graph($graph): Verify graph integrity
 */
?>
