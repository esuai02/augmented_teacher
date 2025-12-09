<?php
/**
 * RuleToWaveMapper.php - Rule to Wave Function Parameter Mapper
 *
 * Phase 1: Rule-Quantum Bridge Implementation
 * Part of the 4-Layer Probability System
 *
 * Responsibilities:
 * - Transform rules.yaml data into quantum wave function parameters
 * - Map rule fields to 13 wave function types
 * - Convert priority/confidence to amplitude/phase
 * - Map condition fields to 8D StateVector dimensions
 * - Calculate coupling strengths from operators
 *
 * @author Rule-Quantum Bridge System
 * @version 1.0
 * @since 2025-12-09
 *
 * Related DB Tables:
 * - mdl_at_rule_quantum_state (stores computed wave parameters)
 * - mdl_at_correlation_matrix (uses field overlap data)
 *
 * Related Files:
 * - RuleYamlLoader.php (provides rule data)
 * - QuantumPersonaEngine.php (consumes wave parameters)
 * - _quantum_wavefunction_system.py (13 wave functions definition)
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

require_once(__DIR__ . '/RuleYamlLoader.php');

class RuleToWaveMapper {

    /** @var RuleYamlLoader YAML loader instance */
    private $loader;

    /** @var bool Debug mode */
    private $debug = false;

    /** @var array Mapping statistics */
    private $stats = [
        'rules_mapped' => 0,
        'wave_params_generated' => 0,
        'field_mappings' => 0
    ];

    /**
     * 13 Wave Function Types from _quantum_wavefunction_system.py
     * Each wave function affects different aspects of learning state
     */
    const WAVE_FUNCTIONS = [
        'psi_core'      => ['desc' => 'Core learning state', 'params' => ['amplitude']],
        'psi_align'     => ['desc' => 'Alignment with learning goals', 'params' => ['phase', 'coherence']],
        'psi_fluct'     => ['desc' => 'Natural fluctuations', 'params' => ['frequency', 'damping']],
        'psi_tunnel'    => ['desc' => 'Breakthrough potential', 'params' => ['barrier_height', 'probability']],
        'psi_WM'        => ['desc' => 'Working memory capacity', 'params' => ['coupling_strength', 'decay_rate']],
        'psi_affect'    => ['desc' => 'Emotional state influence', 'params' => ['valence', 'arousal']],
        'psi_routine'   => ['desc' => 'Routine/habit strength', 'params' => ['stability', 'flexibility']],
        'psi_engage'    => ['desc' => 'Engagement level', 'params' => ['amplitude', 'persistence']],
        'psi_concept'   => ['desc' => 'Concept understanding', 'params' => ['clarity', 'connections']],
        'psi_cascade'   => ['desc' => 'Cascading effects', 'params' => ['cascade_amplitude', 'spread_rate']],
        'psi_meta'      => ['desc' => 'Metacognitive awareness', 'params' => ['phase', 'complexity_factor']],
        'psi_context'   => ['desc' => 'Contextual adaptation', 'params' => ['basis_weights', 'sensitivity']],
        'psi_predict'   => ['desc' => 'Predictive modeling', 'params' => ['horizon', 'confidence']]
    ];

    /**
     * 8D StateVector Dimensions
     * Maps to dimensions tracked in mdl_quantum_ab_test_state_changes
     */
    const STATE_DIMENSIONS = [
        'cognitive_clarity'     => ['index' => 0, 'range' => [0, 1]],
        'emotional_stability'   => ['index' => 1, 'range' => [0, 1]],
        'attention_level'       => ['index' => 2, 'range' => [0, 1]],
        'motivation_strength'   => ['index' => 3, 'range' => [0, 1]],
        'energy_level'          => ['index' => 4, 'range' => [0, 1]],
        'social_connection'     => ['index' => 5, 'range' => [0, 1]],
        'creative_flow'         => ['index' => 6, 'range' => [0, 1]],
        'learning_momentum'     => ['index' => 7, 'range' => [0, 1]]
    ];

    /**
     * Field to Dimension Mapping
     * Maps condition fields from rules.yaml to 8D StateVector dimensions
     */
    const FIELD_TO_DIMENSION = [
        // Cognitive fields → cognitive_clarity
        'concept_understanding' => 'cognitive_clarity',
        'problem_solving_ability' => 'cognitive_clarity',
        'knowledge_gap' => 'cognitive_clarity',
        'comprehension_level' => 'cognitive_clarity',

        // Emotional fields → emotional_stability
        'emotion_state' => 'emotional_stability',
        'stress_level' => 'emotional_stability',
        'frustration_level' => 'emotional_stability',
        'confidence_state' => 'emotional_stability',

        // Focus fields → attention_level
        'focus_stability' => 'attention_level',
        'distraction_count' => 'attention_level',
        'attention_span' => 'attention_level',
        'concentration_score' => 'attention_level',

        // Motivation fields → motivation_strength
        'motivation_level' => 'motivation_strength',
        'goal_clarity' => 'motivation_strength',
        'interest_score' => 'motivation_strength',
        'dropout_risk' => 'motivation_strength',

        // Energy fields → energy_level
        'fatigue_level' => 'energy_level',
        'session_duration' => 'energy_level',
        'break_needed' => 'energy_level',
        'energy_state' => 'energy_level',

        // Social fields → social_connection
        'peer_interaction' => 'social_connection',
        'collaboration_score' => 'social_connection',
        'help_seeking' => 'social_connection',
        'social_engagement' => 'social_connection',

        // Creative fields → creative_flow
        'creative_expression' => 'creative_flow',
        'problem_approach_variety' => 'creative_flow',
        'exploration_tendency' => 'creative_flow',
        'flow_state' => 'creative_flow',

        // Progress fields → learning_momentum
        'learning_progress' => 'learning_momentum',
        'skill_advancement' => 'learning_momentum',
        'completion_rate' => 'learning_momentum',
        'performance_trend' => 'learning_momentum'
    ];

    /**
     * Operator to Coupling Strength Mapping
     * Maps condition operators to coupling strength for ψ_WM
     */
    const OPERATOR_TO_COUPLING = [
        '>'     => 0.8,    // Strong positive coupling
        '>='    => 0.7,
        '<'     => 0.6,    // Inverse coupling
        '<='    => 0.5,
        '=='    => 0.9,    // Exact match - strong coupling
        '!='    => 0.3,    // Exclusion - weak coupling
        'in'    => 0.7,    // Set membership
        'not_in' => 0.4,
        'between' => 0.75, // Range check
        'contains' => 0.65,
        'default' => 0.5   // Default coupling
    ];

    /**
     * Constructor
     *
     * @param RuleYamlLoader|null $loader Optional loader instance
     * @param bool $debug Enable debug mode
     */
    public function __construct($loader = null, $debug = false) {
        $this->loader = $loader ?? new RuleYamlLoader(null, $debug);
        $this->debug = $debug;
    }

    /**
     * Map a single rule to wave function parameters
     *
     * @param array $rule Rule data from RuleYamlLoader
     * @param int $agentId Agent ID for context
     * @return array Wave function parameters
     */
    public function mapRuleToWaveParams($rule, $agentId = 0) {
        $waveParams = [
            'rule_id' => $rule['rule_id'] ?? 'unknown',
            'agent_id' => $agentId,
            'timestamp' => time(),
            'waves' => []
        ];

        // 1. Priority → Amplitude for ψ_core, ψ_engage
        $amplitude = $this->priorityToAmplitude($rule['priority'] ?? 50);
        $waveParams['waves']['psi_core'] = ['amplitude' => $amplitude];
        $waveParams['waves']['psi_engage'] = ['amplitude' => $amplitude, 'persistence' => $amplitude * 0.8];

        // 2. Confidence → Phase for ψ_align, ψ_meta
        $phase = $this->confidenceToPhase($rule['confidence'] ?? 0.5);
        $waveParams['waves']['psi_align'] = ['phase' => $phase, 'coherence' => $rule['confidence'] ?? 0.5];
        $waveParams['waves']['psi_meta'] = ['phase' => $phase];

        // 3. Conditions → Basis weights for ψ_context
        $basisWeights = $this->conditionsToBasisWeights($rule['conditions'] ?? []);
        $waveParams['waves']['psi_context'] = [
            'basis_weights' => $basisWeights,
            'sensitivity' => count($rule['conditions'] ?? []) / 10.0 // More conditions = more sensitive
        ];

        // 4. Conditions operators → Coupling strength for ψ_WM
        $couplingStrength = $this->conditionsToCoupling($rule['conditions'] ?? []);
        $waveParams['waves']['psi_WM'] = [
            'coupling_strength' => $couplingStrength,
            'decay_rate' => 1.0 - $couplingStrength // Inverse relationship
        ];

        // 5. Action count → Cascade amplitude for ψ_cascade
        $actionCount = count($rule['action'] ?? []);
        $cascadeAmplitude = $this->actionCountToCascade($actionCount);
        $waveParams['waves']['psi_cascade'] = [
            'cascade_amplitude' => $cascadeAmplitude,
            'spread_rate' => $cascadeAmplitude * 0.6
        ];

        // 6. Rationale length → Complexity factor for ψ_meta
        $rationale = $rule['rationale'] ?? '';
        $complexityFactor = $this->rationaleToComplexity($rationale);
        $waveParams['waves']['psi_meta']['complexity_factor'] = $complexityFactor;

        // 7. Derive additional wave parameters
        $waveParams['waves']['psi_fluct'] = [
            'frequency' => 0.5 + ($amplitude * 0.5),
            'damping' => 1.0 - ($rule['confidence'] ?? 0.5)
        ];

        $waveParams['waves']['psi_tunnel'] = [
            'barrier_height' => 1.0 - $amplitude,
            'probability' => $amplitude * ($rule['confidence'] ?? 0.5)
        ];

        $waveParams['waves']['psi_affect'] = $this->deriveAffectParams($rule);
        $waveParams['waves']['psi_routine'] = $this->deriveRoutineParams($rule);
        $waveParams['waves']['psi_concept'] = $this->deriveConceptParams($rule);
        $waveParams['waves']['psi_predict'] = $this->derivePredictParams($rule);

        // Calculate composite scores
        $waveParams['layer1_score'] = $this->calculateLayer1Score($rule);
        $waveParams['dimension_weights'] = $basisWeights;

        $this->stats['rules_mapped']++;
        $this->stats['wave_params_generated'] += count($waveParams['waves']);

        return $waveParams;
    }

    /**
     * Map all rules from an agent to wave parameters
     *
     * @param int $agentId Agent ID
     * @return array Array of wave parameters for each rule
     */
    public function mapAgentRulesToWaves($agentId) {
        $rules = $this->loader->loadAgentRules($agentId);
        if (!$rules) {
            return [];
        }

        $waveParamsArray = [];
        foreach ($rules as $rule) {
            $waveParamsArray[] = $this->mapRuleToWaveParams($rule, $agentId);
        }

        return $waveParamsArray;
    }

    /**
     * Map all rules from all agents
     *
     * @return array Nested array: agent_id => [wave_params_array]
     */
    public function mapAllAgentsToWaves() {
        $allWaveParams = [];

        for ($agentId = 1; $agentId <= 22; $agentId++) {
            $params = $this->mapAgentRulesToWaves($agentId);
            if (!empty($params)) {
                $allWaveParams[$agentId] = $params;
            }
        }

        return $allWaveParams;
    }

    // ========================================================================
    // TRANSFORMATION FUNCTIONS
    // ========================================================================

    /**
     * Transform priority (0-100) to amplitude (0-1)
     * Formula: amplitude = priority / 100
     *
     * @param int $priority Priority value
     * @return float Amplitude value
     */
    private function priorityToAmplitude($priority) {
        $priority = max(0, min(100, intval($priority)));
        return $priority / 100.0;
    }

    /**
     * Transform confidence (0-1) to phase (0-2π)
     * Formula: phase = confidence × 2π
     *
     * @param float $confidence Confidence value
     * @return float Phase value in radians
     */
    private function confidenceToPhase($confidence) {
        $confidence = max(0, min(1, floatval($confidence)));
        return $confidence * 2 * M_PI;
    }

    /**
     * Transform conditions to 8D basis weights
     * Maps each condition field to its corresponding dimension
     *
     * @param array $conditions Rule conditions
     * @return array 8D basis weights
     */
    private function conditionsToBasisWeights($conditions) {
        // Initialize 8D weights
        $weights = array_fill_keys(array_keys(self::STATE_DIMENSIONS), 0.0);

        if (empty($conditions)) {
            // Default uniform weights
            foreach ($weights as $key => &$val) {
                $val = 1.0 / 8.0;
            }
            return $weights;
        }

        // Count field occurrences per dimension
        $dimensionCounts = array_fill_keys(array_keys(self::STATE_DIMENSIONS), 0);

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $dimension = self::FIELD_TO_DIMENSION[$field] ?? null;

            if ($dimension && isset($dimensionCounts[$dimension])) {
                $dimensionCounts[$dimension]++;
                $this->stats['field_mappings']++;
            }
        }

        // Normalize to weights
        $total = array_sum($dimensionCounts);
        if ($total > 0) {
            foreach ($dimensionCounts as $dim => $count) {
                $weights[$dim] = $count / $total;
            }
        } else {
            // Fallback to uniform
            foreach ($weights as $key => &$val) {
                $val = 1.0 / 8.0;
            }
        }

        return $weights;
    }

    /**
     * Transform condition operators to coupling strength
     * Aggregates coupling values from all operators used
     *
     * @param array $conditions Rule conditions
     * @return float Average coupling strength
     */
    private function conditionsToCoupling($conditions) {
        if (empty($conditions)) {
            return self::OPERATOR_TO_COUPLING['default'];
        }

        $totalCoupling = 0;
        $count = 0;

        foreach ($conditions as $condition) {
            $operator = $condition['operator'] ?? 'default';
            $coupling = self::OPERATOR_TO_COUPLING[$operator] ?? self::OPERATOR_TO_COUPLING['default'];
            $totalCoupling += $coupling;
            $count++;
        }

        return $count > 0 ? $totalCoupling / $count : self::OPERATOR_TO_COUPLING['default'];
    }

    /**
     * Transform action count to cascade amplitude
     * Formula: cascade_amplitude = √(n / max_actions)
     * max_actions assumed to be 10
     *
     * @param int $actionCount Number of actions
     * @return float Cascade amplitude
     */
    private function actionCountToCascade($actionCount) {
        $maxActions = 10;
        $n = min($actionCount, $maxActions);
        return sqrt($n / $maxActions);
    }

    /**
     * Transform rationale to complexity factor
     * Formula: complexity = log(1 + length) / log(1 + max_length)
     * max_length assumed to be 500
     *
     * @param string $rationale Rationale text
     * @return float Complexity factor
     */
    private function rationaleToComplexity($rationale) {
        $maxLength = 500;
        $length = strlen($rationale);

        if ($length === 0) {
            return 0.1; // Minimum complexity
        }

        return log(1 + $length) / log(1 + $maxLength);
    }

    // ========================================================================
    // DERIVED WAVE PARAMETERS
    // ========================================================================

    /**
     * Derive ψ_affect parameters from rule context
     */
    private function deriveAffectParams($rule) {
        $valence = 0.5; // Neutral default
        $arousal = 0.5;

        // Check for emotion-related conditions
        foreach ($rule['conditions'] ?? [] as $cond) {
            $field = $cond['field'] ?? '';
            if (strpos($field, 'emotion') !== false || strpos($field, 'stress') !== false) {
                $arousal = 0.7;
            }
            if (strpos($field, 'positive') !== false || strpos($field, 'confidence') !== false) {
                $valence = 0.7;
            }
            if (strpos($field, 'frustration') !== false || strpos($field, 'anxiety') !== false) {
                $valence = 0.3;
                $arousal = 0.8;
            }
        }

        // Action analysis for valence
        $positiveActions = ['encourage', 'reward', 'celebrate', 'acknowledge'];
        $negativeActions = ['warn', 'alert', 'intervene'];

        foreach ($rule['action'] ?? [] as $action) {
            $actionLower = strtolower($action);
            foreach ($positiveActions as $pos) {
                if (strpos($actionLower, $pos) !== false) {
                    $valence = min(1.0, $valence + 0.1);
                }
            }
            foreach ($negativeActions as $neg) {
                if (strpos($actionLower, $neg) !== false) {
                    $arousal = min(1.0, $arousal + 0.1);
                }
            }
        }

        return ['valence' => $valence, 'arousal' => $arousal];
    }

    /**
     * Derive ψ_routine parameters from rule context
     */
    private function deriveRoutineParams($rule) {
        $stability = 0.5;
        $flexibility = 0.5;

        // Check for routine-related fields
        foreach ($rule['conditions'] ?? [] as $cond) {
            $field = $cond['field'] ?? '';
            if (strpos($field, 'routine') !== false || strpos($field, 'habit') !== false) {
                $stability = 0.7;
            }
            if (strpos($field, 'schedule') !== false || strpos($field, 'pattern') !== false) {
                $stability = 0.6;
            }
            if (strpos($field, 'adaptive') !== false || strpos($field, 'flexible') !== false) {
                $flexibility = 0.7;
            }
        }

        // High priority rules tend to be more stable
        $stability = $stability * (($rule['priority'] ?? 50) / 100.0) + $stability * 0.5;

        return ['stability' => min(1.0, $stability), 'flexibility' => $flexibility];
    }

    /**
     * Derive ψ_concept parameters from rule context
     */
    private function deriveConceptParams($rule) {
        $clarity = 0.5;
        $connections = 0.5;

        foreach ($rule['conditions'] ?? [] as $cond) {
            $field = $cond['field'] ?? '';
            if (strpos($field, 'concept') !== false || strpos($field, 'understanding') !== false) {
                $clarity = 0.7;
            }
            if (strpos($field, 'knowledge') !== false || strpos($field, 'skill') !== false) {
                $connections = 0.7;
            }
        }

        // More conditions suggest more connections
        $condCount = count($rule['conditions'] ?? []);
        $connections = min(1.0, $connections + ($condCount * 0.05));

        return ['clarity' => $clarity, 'connections' => $connections];
    }

    /**
     * Derive ψ_predict parameters from rule context
     */
    private function derivePredictParams($rule) {
        // Horizon based on rule type (predictive vs reactive)
        $horizon = 0.5; // Medium-term default
        $confidence = $rule['confidence'] ?? 0.5;

        // Check for prediction-related keywords
        $predictiveKeywords = ['predict', 'forecast', 'risk', 'likelihood', 'trend'];
        $ruleId = strtolower($rule['rule_id'] ?? '');

        foreach ($predictiveKeywords as $kw) {
            if (strpos($ruleId, $kw) !== false) {
                $horizon = 0.8; // Long-term prediction
                break;
            }
        }

        // Check conditions for time-related fields
        foreach ($rule['conditions'] ?? [] as $cond) {
            $field = strtolower($cond['field'] ?? '');
            if (strpos($field, 'future') !== false || strpos($field, 'trend') !== false) {
                $horizon = 0.9;
            }
            if (strpos($field, 'immediate') !== false || strpos($field, 'current') !== false) {
                $horizon = 0.3; // Short-term
            }
        }

        return ['horizon' => $horizon, 'confidence' => $confidence];
    }

    // ========================================================================
    // LAYER 1 CALCULATION
    // ========================================================================

    /**
     * Calculate Layer 1 score (Rule Confidence)
     * Formula: P_rule = confidence × (priority/100) × condition_match
     * condition_match is assumed 1.0 at mapping time (actual matching done at runtime)
     *
     * @param array $rule Rule data
     * @return float Layer 1 probability score
     */
    private function calculateLayer1Score($rule) {
        $confidence = $rule['confidence'] ?? 0.5;
        $priority = ($rule['priority'] ?? 50) / 100.0;
        $conditionMatch = 1.0; // Placeholder - actual matching at runtime

        return $confidence * $priority * $conditionMatch;
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Get wave function metadata
     *
     * @param string $waveName Wave function name
     * @return array|null Wave function info
     */
    public function getWaveFunctionInfo($waveName) {
        return self::WAVE_FUNCTIONS[$waveName] ?? null;
    }

    /**
     * Get all available wave function names
     *
     * @return array Wave function names
     */
    public function getAvailableWaveFunctions() {
        return array_keys(self::WAVE_FUNCTIONS);
    }

    /**
     * Get dimension for a field
     *
     * @param string $field Field name from conditions
     * @return string|null Dimension name or null
     */
    public function getFieldDimension($field) {
        return self::FIELD_TO_DIMENSION[$field] ?? null;
    }

    /**
     * Get all state dimensions
     *
     * @return array State dimensions with metadata
     */
    public function getStateDimensions() {
        return self::STATE_DIMENSIONS;
    }

    /**
     * Get mapping statistics
     *
     * @return array Statistics
     */
    public function getStats() {
        return $this->stats;
    }

    /**
     * Export wave parameters to JSON format
     * Suitable for Python bridge communication
     *
     * @param array $waveParams Wave parameters
     * @return string JSON string
     */
    public function exportToJson($waveParams) {
        return json_encode($waveParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a summary of wave parameters for logging/debugging
     *
     * @param array $waveParams Wave parameters
     * @return string Summary string
     */
    public function summarizeWaveParams($waveParams) {
        $summary = [];
        $summary[] = "Rule: {$waveParams['rule_id']} (Agent: {$waveParams['agent_id']})";
        $summary[] = "Layer1 Score: " . number_format($waveParams['layer1_score'], 4);

        foreach ($waveParams['waves'] as $waveName => $params) {
            $paramStr = [];
            foreach ($params as $k => $v) {
                if (is_array($v)) {
                    $v = json_encode($v);
                } elseif (is_numeric($v)) {
                    $v = number_format($v, 4);
                }
                $paramStr[] = "{$k}={$v}";
            }
            $summary[] = "  {$waveName}: " . implode(', ', $paramStr);
        }

        return implode("\n", $summary);
    }

    /**
     * Validate wave parameters structure
     *
     * @param array $waveParams Wave parameters to validate
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    public function validateWaveParams($waveParams) {
        $errors = [];

        // Check required fields
        if (!isset($waveParams['rule_id'])) {
            $errors[] = "Missing rule_id";
        }
        if (!isset($waveParams['waves']) || !is_array($waveParams['waves'])) {
            $errors[] = "Missing or invalid waves array";
        }

        // Validate wave parameter ranges
        if (isset($waveParams['waves'])) {
            foreach ($waveParams['waves'] as $waveName => $params) {
                if (!isset(self::WAVE_FUNCTIONS[$waveName])) {
                    $errors[] = "Unknown wave function: {$waveName}";
                    continue;
                }

                // Check amplitude/probability ranges (should be 0-1)
                foreach (['amplitude', 'probability', 'coherence', 'coupling_strength'] as $rangeParam) {
                    if (isset($params[$rangeParam])) {
                        $val = $params[$rangeParam];
                        if ($val < 0 || $val > 1) {
                            $errors[] = "{$waveName}.{$rangeParam} out of range: {$val}";
                        }
                    }
                }

                // Check phase range (should be 0-2π)
                if (isset($params['phase'])) {
                    $phase = $params['phase'];
                    if ($phase < 0 || $phase > 2 * M_PI) {
                        $errors[] = "{$waveName}.phase out of range: {$phase}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

/**
 * Example Usage:
 *
 * $mapper = new RuleToWaveMapper();
 *
 * // Map single rule
 * $loader = new RuleYamlLoader();
 * $rules = $loader->loadAgentRules(4);
 * $waveParams = $mapper->mapRuleToWaveParams($rules[0], 4);
 *
 * // Map all rules from an agent
 * $agentWaves = $mapper->mapAgentRulesToWaves(4);
 *
 * // Map all agents
 * $allWaves = $mapper->mapAllAgentsToWaves();
 *
 * // Export for Python bridge
 * $json = $mapper->exportToJson($waveParams);
 *
 * // Validate wave parameters
 * $validation = $mapper->validateWaveParams($waveParams);
 * if (!$validation['valid']) {
 *     print_r($validation['errors']);
 * }
 */
