<?php
/**
 * RuleYamlLoader.php - YAML Parser for 22 Agent Rules
 *
 * Phase 1: Rule-Quantum Bridge Implementation
 * Part of the 4-Layer Probability System
 *
 * Responsibilities:
 * - Load rules.yaml from all 22 agents
 * - Parse YAML to PHP arrays
 * - Cache parsed rules for session performance
 * - Provide normalized rule data structures
 *
 * @author Rule-Quantum Bridge System
 * @version 1.0
 * @since 2025-12-09
 *
 * Related DB Tables:
 * - mdl_at_rule_quantum_state (stores rule execution states)
 * - mdl_at_correlation_matrix (stores inter-agent correlations)
 *
 * Related Files:
 * - RuleToWaveMapper.php (uses loaded rules)
 * - QuantumPersonaEngine.php (integration target)
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

class RuleYamlLoader {

    /** @var string Base path to orchestration folder */
    private $orchestrationPath;

    /** @var array Cached rules by agent ID */
    private $rulesCache = [];

    /** @var array Agent folder names mapping */
    private $agentFolders = [];

    /** @var bool Debug mode flag */
    private $debug = false;

    /** @var array Load statistics */
    private $stats = [
        'loaded_agents' => 0,
        'total_rules' => 0,
        'parse_errors' => 0,
        'cache_hits' => 0
    ];

    /** @var array Debug logs storage for retrieval */
    private $debugLogs = [];

    /**
     * 22 Agent IDs and their folder names
     * Format: agent_id => folder_name_pattern
     */
    const AGENT_MAPPING = [
        1  => 'agent01_basic_student_info',
        2  => 'agent02_learning_preference',
        3  => 'agent03_context_evaluation',
        4  => 'agent04_inspect_weakpoints',
        5  => 'agent05_realtime_focus',
        6  => 'agent06_intervention_design',
        7  => 'agent07_feedback_analysis',
        8  => 'agent08_emotion_state',
        9  => 'agent09_routine_builder',
        10 => 'agent10_concept_map',
        11 => 'agent11_problem_solving',
        12 => 'agent12_schedule_optimizer',
        13 => 'agent13_dropout_prevention',
        14 => 'agent14_progress_tracker',
        15 => 'agent15_skill_assessment',
        16 => 'agent16_resource_recommender',
        17 => 'agent17_goal_alignment',
        18 => 'agent18_habit_formation',
        19 => 'agent19_peer_learning',
        20 => 'agent20_parent_communication',
        21 => 'agent21_teacher_assistant',
        22 => 'agent22_analytics_reporter'
    ];

    /**
     * Constructor
     *
     * @param string|null $orchestrationPath Custom path to orchestration folder
     * @param bool $debug Enable debug mode
     */
    public function __construct($orchestrationPath = null, $debug = false) {
        // Default path based on server structure
        $this->orchestrationPath = $orchestrationPath ??
            '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration';

        $this->debug = $debug;
        $this->initAgentFolders();
    }

    /**
     * Initialize agent folder mappings by scanning directory
     */
    private function initAgentFolders() {
        $agentsPath = $this->orchestrationPath . '/agents';

        if (!is_dir($agentsPath)) {
            $this->logError("Agents directory not found: {$agentsPath}", __LINE__);
            return;
        }

        // Scan for agent folders
        $folders = scandir($agentsPath);
        foreach ($folders as $folder) {
            if (preg_match('/^agent(\d{2})_/', $folder, $matches)) {
                $agentId = intval($matches[1]);
                $this->agentFolders[$agentId] = $folder;
            }
        }

        if ($this->debug) {
            $this->log("Initialized " . count($this->agentFolders) . " agent folders");
        }
    }

    /**
     * Load rules for a specific agent
     *
     * @param int $agentId Agent ID (1-22)
     * @param bool $useCache Use cached version if available
     * @return array|null Parsed rules or null on failure
     */
    public function loadAgentRules($agentId, $useCache = true) {
        $agentId = intval($agentId);

        if ($agentId < 1 || $agentId > 22) {
            $this->logError("Invalid agent ID: {$agentId}", __LINE__);
            return null;
        }

        // Check cache
        if ($useCache && isset($this->rulesCache[$agentId])) {
            $this->stats['cache_hits']++;
            return $this->rulesCache[$agentId];
        }

        // Get rules file path
        $rulesPath = $this->getRulesFilePath($agentId);
        if (!$rulesPath) {
            return null;
        }

        // Parse YAML
        $rules = $this->parseYamlFile($rulesPath);
        if ($rules !== null) {
            $this->rulesCache[$agentId] = $rules;
            $this->stats['loaded_agents']++;
            $this->stats['total_rules'] += count($rules);
        }

        return $rules;
    }

    /**
     * Load rules for all 22 agents
     *
     * @param bool $useCache Use cached versions if available
     * @return array Associative array of agent_id => rules
     */
    public function loadAllAgentRules($useCache = true) {
        $allRules = [];

        for ($agentId = 1; $agentId <= 22; $agentId++) {
            $rules = $this->loadAgentRules($agentId, $useCache);
            if ($rules !== null) {
                $allRules[$agentId] = $rules;
            }
        }

        return $allRules;
    }

    /**
     * Get the file path for an agent's rules.yaml
     *
     * @param int $agentId Agent ID
     * @return string|null File path or null if not found
     */
    public function getRulesFilePath($agentId) {
        if (!isset($this->agentFolders[$agentId])) {
            // Try default mapping
            $folderName = self::AGENT_MAPPING[$agentId] ?? null;
            if (!$folderName) {
                $this->logError("No folder mapping for agent {$agentId}", __LINE__);
                return null;
            }
        } else {
            $folderName = $this->agentFolders[$agentId];
        }

        $rulesPath = $this->orchestrationPath . "/agents/{$folderName}/rules/rules.yaml";

        if (!file_exists($rulesPath)) {
            $this->logError("Rules file not found: {$rulesPath}", __LINE__);
            return null;
        }

        return $rulesPath;
    }

    /**
     * Parse a YAML file to PHP array
     * Supports both yaml_parse (if available) and custom parser
     *
     * @param string $filePath Path to YAML file
     * @return array|null Parsed array or null on failure
     */
    private function parseYamlFile($filePath) {
        if (!file_exists($filePath)) {
            $this->logError("File not found: {$filePath}", __LINE__);
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logError("Failed to read file: {$filePath}", __LINE__);
            return null;
        }

        if ($this->debug) {
            $this->debugLog("parseYamlFile: Read file ({$filePath}), size: " . strlen($content) . " bytes");
        }

        // Try native yaml_parse if available
        if (function_exists('yaml_parse')) {
            if ($this->debug) {
                $this->debugLog("parseYamlFile: yaml_parse() available, attempting native parse");
            }
            $result = @yaml_parse($content);
            if ($result !== false) {
                if ($this->debug) {
                    $this->debugLog("parseYamlFile: yaml_parse() success, top-level keys: " . implode(', ', array_keys($result)));
                    $hasRulesKey = isset($result['rules']) && is_array($result['rules']);
                    $this->debugLog("parseYamlFile: has 'rules' key: " . ($hasRulesKey ? 'yes (' . count($result['rules']) . ' items)' : 'no'));
                }
                // Extract 'rules' array if present (rules.yaml has top-level keys: version, scenario, description, rules)
                $rulesArray = isset($result['rules']) && is_array($result['rules'])
                    ? $result['rules']
                    : $result;
                $normalized = $this->normalizeRules($rulesArray);
                if ($this->debug) {
                    $this->debugLog("parseYamlFile: After normalization: " . count($normalized) . " rules");
                }
                return $normalized;
            } else {
                if ($this->debug) {
                    $this->debugLog("parseYamlFile: yaml_parse() returned false, falling back to custom parser");
                }
            }
        } else {
            if ($this->debug) {
                $this->debugLog("parseYamlFile: yaml_parse() NOT available, using custom parser");
            }
        }

        // Fallback to custom parser
        return $this->customYamlParse($content);
    }

    /**
     * Custom YAML parser for rules.yaml format
     * Optimized for the specific structure used in agent rules
     * Handles top-level keys: version, scenario, description, rules
     *
     * @param string $content YAML content
     * @return array|null Parsed rules
     */
    private function customYamlParse($content) {
        $rules = [];
        $currentRule = null;
        $currentKey = null;
        $currentList = null;
        $indentLevel = 0;
        $inRulesSection = false;  // Track if we're inside the 'rules:' section

        $lines = explode("\n", $content);

        // Debug: Log parsing start
        if ($this->debug) {
            $this->debugLog("customYamlParse: Starting parse, content length: " . strlen($content) . ", lines: " . count($lines));
        }

        foreach ($lines as $lineNum => $line) {
            // Skip empty lines and comments
            if (trim($line) === '' || preg_match('/^\s*#/', $line)) {
                continue;
            }

            // Calculate indent level
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $trimmedLine = trim($line);

            // Check for top-level 'rules:' key (starts the rules section)
            if ($indent === 0 && preg_match('/^rules:\s*$/', $trimmedLine)) {
                $inRulesSection = true;
                if ($this->debug) {
                    $this->debugLog("customYamlParse: Found 'rules:' section at line {$lineNum}");
                }
                continue;
            }

            // Skip top-level keys (version, scenario, description) before rules section
            if ($indent === 0 && preg_match('/^(version|scenario|description):\s*/', $trimmedLine)) {
                continue;
            }

            // New rule item (starts with -)
            if (preg_match('/^- rule_id:\s*["\']?([^"\']+)["\']?/', $trimmedLine, $matches)) {
                $inRulesSection = true;  // Also set flag if directly found
                if ($currentRule !== null) {
                    $rules[] = $currentRule;
                }
                $currentRule = ['rule_id' => $matches[1]];
                $currentKey = null;
                $currentList = null;
                if ($this->debug) {
                    $this->debugLog("customYamlParse: Found rule_id '{$matches[1]}' at line {$lineNum}, total so far: " . count($rules));
                }
                continue;
            }

            // PRIORITY FIX: Nested condition field (- field:) MUST be checked BEFORE generic list continuation
            // This handles YAML structure like:
            //   conditions:
            //     - field: "activity_type"
            //       operator: "=="
            //       value: "concept_understanding"
            if ($currentList === 'conditions' && preg_match('/^-\s*field:\s*["\']?([^"\']+)["\']?/', $trimmedLine, $matches)) {
                $currentRule['conditions'][] = ['field' => $matches[1]];
                if ($this->debug) {
                    $this->debugLog("customYamlParse: Added condition field '{$matches[1]}' at line {$lineNum}");
                }
                continue;
            }

            // Nested property (like operator, value in conditions) - MUST be before generic list continuation
            if ($currentList === 'conditions' && $currentRule !== null && isset($currentRule['conditions']) && count($currentRule['conditions']) > 0) {
                if (preg_match('/^(\w+):\s*(.+)$/', $trimmedLine, $matches)) {
                    $propKey = $matches[1];
                    // Only handle condition properties, not rule-level keys
                    if (in_array($propKey, ['operator', 'value'])) {
                        $lastIdx = count($currentRule['conditions']) - 1;
                        if ($lastIdx >= 0) {
                            $currentRule['conditions'][$lastIdx][$propKey] = $this->parseYamlValue($matches[2]);
                            if ($this->debug) {
                                $this->debugLog("customYamlParse: Set condition {$propKey}='{$matches[2]}' at line {$lineNum}");
                            }
                        }
                        continue;
                    }
                }
            }

            // List continuation for conditions/action (generic - for action items and simple conditions)
            if (preg_match('/^-\s+(.+)$/', $trimmedLine, $matches) && $currentList !== null) {
                $value = $this->parseYamlValue($matches[1]);

                // Handle condition objects
                if ($currentList === 'conditions' && is_string($value)) {
                    // Parse condition string like "field: 'value'"
                    $condObj = $this->parseConditionString($value);
                    if ($condObj) {
                        $currentRule[$currentList][] = $condObj;
                    } else {
                        $currentRule[$currentList][] = $value;
                    }
                } else {
                    $currentRule[$currentList][] = $value;
                }
                continue;
            }

            // Key-value pair (rule-level properties)
            if (preg_match('/^(\w+):\s*(.*)$/', $trimmedLine, $matches)) {
                $key = $matches[1];
                $value = $matches[2];

                // List indicator (conditions: or action:)
                if ($value === '' || $value === null) {
                    $currentKey = $key;
                    if (in_array($key, ['conditions', 'action'])) {
                        $currentList = $key;
                        if (!isset($currentRule[$key])) {
                            $currentRule[$key] = [];
                        }
                    }
                    continue;
                }

                // Direct value (priority, confidence, description, rationale, enabled)
                $currentRule[$key] = $this->parseYamlValue($value);
                // Don't reset currentList for rule-level properties
                if (!in_array($key, ['operator', 'value', 'field'])) {
                    $currentList = null;
                }
            }

            // Legacy: Nested condition field with original $line (backward compatibility)
            if ($currentList === 'conditions' && preg_match('/^\s+-\s*field:\s*["\']?([^"\']+)["\']?/', $line, $matches)) {
                // Check if this condition was already added
                $alreadyAdded = false;
                if (isset($currentRule['conditions'])) {
                    foreach ($currentRule['conditions'] as $cond) {
                        if (isset($cond['field']) && $cond['field'] === $matches[1]) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                }
                if (!$alreadyAdded) {
                    $currentRule['conditions'][] = ['field' => $matches[1]];
                }
                continue;
            }

            // Legacy: Nested property with original logic (backward compatibility)
            if ($currentList === 'conditions' && preg_match('/^\s+(\w+):\s*(.+)$/', $trimmedLine, $matches)) {
                $lastIdx = count($currentRule['conditions']) - 1;
                if ($lastIdx >= 0) {
                    $currentRule['conditions'][$lastIdx][$matches[1]] = $this->parseYamlValue($matches[2]);
                }
            }
        }

        // Add last rule
        if ($currentRule !== null) {
            $rules[] = $currentRule;
        }

        if ($this->debug) {
            $this->debugLog("customYamlParse: Completed parsing. Raw rules count: " . count($rules));
            if (count($rules) > 0) {
                $ruleIds = array_map(function($r) { return $r['rule_id'] ?? 'unknown'; }, $rules);
                $this->debugLog("customYamlParse: Rule IDs: " . implode(', ', $ruleIds));
            }
        }

        $normalized = $this->normalizeRules($rules);

        if ($this->debug) {
            $this->debugLog("customYamlParse: After normalization: " . count($normalized) . " rules");
        }

        return $normalized;
    }

    /**
     * Parse a YAML value (handles strings, numbers, booleans, and inline arrays)
     *
     * @param string $value Raw value string
     * @return mixed Parsed value
     */
    private function parseYamlValue($value) {
        $value = trim($value);

        // Remove quotes
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
            return $matches[1];
        }

        // Inline array: ["item1", "item2", "item3"] or ['item1', 'item2']
        if (preg_match('/^\[(.+)\]$/', $value, $matches)) {
            $arrayContent = $matches[1];
            $items = [];

            // Split by comma, handling quoted strings
            preg_match_all('/["\']([^"\']+)["\']|([^,\s]+)/', $arrayContent, $arrayMatches);

            foreach ($arrayMatches[0] as $idx => $match) {
                $item = !empty($arrayMatches[1][$idx])
                    ? $arrayMatches[1][$idx]  // Quoted string
                    : trim($arrayMatches[2][$idx]);  // Unquoted

                if ($item !== '') {
                    $items[] = $this->parseYamlValue($item);  // Recursive parse
                }
            }
            return $items;
        }

        // Boolean
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;

        // Null
        if (strtolower($value) === 'null' || $value === '~') return null;

        // Number
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? floatval($value) : intval($value);
        }

        return $value;
    }

    /**
     * Parse condition string format
     *
     * @param string $str Condition string
     * @return array|null Condition object
     */
    private function parseConditionString($str) {
        // Handle "field: value" format
        if (preg_match('/^(["\']?)(\w+)\1:\s*["\']?([^"\']+)["\']?$/', $str, $matches)) {
            return ['field' => $matches[2], 'value' => $matches[3]];
        }
        return null;
    }

    /**
     * Normalize rules to standard format
     *
     * @param array $rules Raw parsed rules
     * @return array Normalized rules
     */
    private function normalizeRules($rules) {
        if (!is_array($rules)) {
            if ($this->debug) {
                $this->debugLog("normalizeRules: Input is not array, type: " . gettype($rules));
            }
            return [];
        }

        if ($this->debug) {
            $this->debugLog("normalizeRules: Processing " . count($rules) . " raw rules");
        }

        $normalized = [];
        $skipped = 0;

        foreach ($rules as $idx => $rule) {
            if (!is_array($rule)) {
                if ($this->debug) {
                    $this->debugLog("normalizeRules: Skipping idx {$idx} - not array, type: " . gettype($rule));
                }
                $skipped++;
                continue;
            }
            if (!isset($rule['rule_id'])) {
                if ($this->debug) {
                    $keys = array_keys($rule);
                    $this->debugLog("normalizeRules: Skipping idx {$idx} - no rule_id, keys: " . implode(', ', $keys));
                }
                $skipped++;
                continue;
            }

            $normalized[] = [
                'rule_id' => $rule['rule_id'] ?? 'unknown',
                'priority' => intval($rule['priority'] ?? 50),
                'confidence' => floatval($rule['confidence'] ?? 0.5),
                'conditions' => $rule['conditions'] ?? [],
                'action' => $rule['action'] ?? [],
                'rationale' => $rule['rationale'] ?? '',
                'enabled' => $rule['enabled'] ?? true
            ];
        }

        if ($this->debug) {
            $this->debugLog("normalizeRules: Result - {$skipped} skipped, " . count($normalized) . " normalized");
        }

        return $normalized;
    }

    /**
     * Get a specific rule by ID from an agent
     *
     * @param int $agentId Agent ID
     * @param string $ruleId Rule ID
     * @return array|null Rule data or null
     */
    public function getRule($agentId, $ruleId) {
        $rules = $this->loadAgentRules($agentId);
        if (!$rules) return null;

        foreach ($rules as $rule) {
            if ($rule['rule_id'] === $ruleId) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Get all rules matching certain criteria
     *
     * @param array $criteria Search criteria
     * @return array Matching rules with agent info
     */
    public function findRules($criteria) {
        $results = [];
        $allRules = $this->loadAllAgentRules();

        foreach ($allRules as $agentId => $rules) {
            foreach ($rules as $rule) {
                if ($this->matchesCriteria($rule, $criteria)) {
                    $results[] = [
                        'agent_id' => $agentId,
                        'rule' => $rule
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Check if a rule matches search criteria
     *
     * @param array $rule Rule data
     * @param array $criteria Search criteria
     * @return bool True if matches
     */
    private function matchesCriteria($rule, $criteria) {
        // Priority threshold
        if (isset($criteria['min_priority'])) {
            if ($rule['priority'] < $criteria['min_priority']) return false;
        }

        // Confidence threshold
        if (isset($criteria['min_confidence'])) {
            if ($rule['confidence'] < $criteria['min_confidence']) return false;
        }

        // Contains field in conditions
        if (isset($criteria['has_field'])) {
            $found = false;
            foreach ($rule['conditions'] as $cond) {
                if (isset($cond['field']) && $cond['field'] === $criteria['has_field']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
        }

        // Rule ID pattern
        if (isset($criteria['rule_pattern'])) {
            if (!preg_match($criteria['rule_pattern'], $rule['rule_id'])) return false;
        }

        return true;
    }

    /**
     * Extract all unique condition fields across all agents
     *
     * @return array List of unique field names
     */
    public function extractAllConditionFields() {
        $fields = [];
        $allRules = $this->loadAllAgentRules();

        foreach ($allRules as $agentId => $rules) {
            foreach ($rules as $rule) {
                foreach ($rule['conditions'] as $condition) {
                    if (isset($condition['field'])) {
                        $field = $condition['field'];
                        if (!isset($fields[$field])) {
                            $fields[$field] = ['count' => 0, 'agents' => []];
                        }
                        $fields[$field]['count']++;
                        if (!in_array($agentId, $fields[$field]['agents'])) {
                            $fields[$field]['agents'][] = $agentId;
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Get loader statistics
     *
     * @return array Statistics array
     */
    public function getStats() {
        return $this->stats;
    }

    /**
     * Clear rules cache
     */
    public function clearCache() {
        $this->rulesCache = [];
        $this->stats['cache_hits'] = 0;
    }

    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param int $line Line number
     */
    private function logError($message, $line) {
        $this->stats['parse_errors']++;
        if ($this->debug) {
            error_log("[RuleYamlLoader.php:{$line}] ERROR: {$message}");
        }
    }

    /**
     * Log a debug message (stores in array and optionally writes to error_log)
     *
     * @param string $message Debug message
     */
    private function debugLog($message) {
        if ($this->debug) {
            $timestamp = date('H:i:s');
            $logEntry = "[{$timestamp}] {$message}";
            $this->debugLogs[] = $logEntry;
            error_log("[RuleYamlLoader.php] {$message}");
        }
    }

    /**
     * Get all stored debug logs
     *
     * @return array Array of debug log messages
     */
    public function getDebugLogs() {
        return $this->debugLogs;
    }

    /**
     * Clear stored debug logs
     */
    public function clearDebugLogs() {
        $this->debugLogs = [];
    }
}

/**
 * Example Usage:
 *
 * $loader = new RuleYamlLoader();
 *
 * // Load single agent rules
 * $rules = $loader->loadAgentRules(4);
 *
 * // Load all agents
 * $allRules = $loader->loadAllAgentRules();
 *
 * // Find rules with specific criteria
 * $highPriorityRules = $loader->findRules([
 *     'min_priority' => 90,
 *     'min_confidence' => 0.8
 * ]);
 *
 * // Extract condition fields for correlation analysis
 * $fields = $loader->extractAllConditionFields();
 */
