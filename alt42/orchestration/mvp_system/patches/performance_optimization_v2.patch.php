<?php
/**
 * 🚀 Performance Optimization Patch for MVPAgentOrchestrator_v2
 *
 * Target: Reduce 216.1% overhead to ≤20%
 *
 * Optimizations Applied:
 * 1. ✅ Eliminate double database write (execution_time_ms)
 * 2. ✅ Lazy graph building (only when cascades enabled)
 * 3. ✅ Optimize JSON encoding (conditional serialization)
 * 4. ✅ Fix Moodle DML double prefix bug
 *
 * Performance Impact:
 * - Estimated reduction: 150-180% overhead
 * - Target overhead: ≤20%
 * - Expected final: 30-50% (acceptable range)
 *
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/patches/performance_optimization_v2.patch.php
 * Created: 2025-11-04
 * Status: READY FOR TESTING
 */

// ============================================================================
// OPTIMIZATION 1: Eliminate Double Database Write
// ============================================================================
// Location: MVPAgentOrchestrator_v2.php lines 540-621
// Impact: -50% database operations per decision

/**
 * BEFORE (SLOW - 2 queries):
 *
 * $decision_record->execution_time_ms = null;  // Line 570
 *
 * // Lines 598-600
 * if ($decision_record->execution_time_ms === null) {
 *     $decision_record->execution_time_ms = 0.00;
 * }
 * $decision_id = $DB->insert_record('mvp_decision_log', $decision_record);
 *
 * // Line 621
 * $DB->set_field('mvp_decision_log', 'execution_time_ms', round($duration_ms, 2), ['id' => $decision_id]);
 * // ❌ PROBLEM: 2 database operations (INSERT + UPDATE)
 */

/**
 * AFTER (FAST - 1 query):
 */
function execute_decision_OPTIMIZED($agent_id, $context, $rule, $confidence, $parent_decision_id = null, $cascade_depth = 0) {
    global $DB;

    $start_time = microtime(true);

    // Validate inputs
    if (!is_array($context) || empty($context['student_id'])) {
        throw new Exception("Invalid context: student_id is required");
    }

    $student_id = $context['student_id'];

    // Sanitize inputs
    $agent_id_safe = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $agent_id), 0, 50);
    $agent_name_safe = substr($rule['agent_name'] ?? '', 0, 100);
    $rule_id_safe = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $rule['rule_id'] ?? 'unknown'), 0, 100);
    $action_safe = substr($rule['action'] ?? 'no_action', 0, 50);
    $confidence_safe = min(max(floatval($confidence), -9.9999), 9.9999);
    $confidence_safe = round($confidence_safe, 4);

    $rationale = $rule['rationale'] ?? 'No rationale provided';

    // Execute the action
    $action_result = $this->execute_action($action_safe, $context, $rule);

    // ✅ OPTIMIZATION: Calculate execution time BEFORE database insert
    $duration_ms = (microtime(true) - $start_time) * 1000;

    // Build decision record with actual execution time
    $decision_record = new stdClass();
    $decision_record->student_id = intval($student_id);
    $decision_record->agent_id = $agent_id_safe;
    $decision_record->agent_name = $agent_name_safe;
    $decision_record->rule_id = $rule_id_safe;
    $decision_record->action = $action_safe;
    $decision_record->confidence = $confidence_safe;
    $decision_record->rationale = $rationale;

    // ✅ OPTIMIZATION: Conditional JSON encoding (only when data exists)
    $decision_record->context_data = !empty($context) ? json_encode($context) : null;
    $decision_record->result_data = !empty($action_result) ? json_encode([
        'success' => $action_result['success'] ?? false,
        'message' => $action_result['message'] ?? '',
        'data' => $action_result['data'] ?? []
    ]) : null;

    $decision_record->is_cascade = ($cascade_depth > 0) ? 1 : 0;
    $decision_record->cascade_depth = $cascade_depth;
    $decision_record->parent_decision_id = $parent_decision_id;

    // ✅ OPTIMIZATION: Set actual execution time directly (no null → 0.00 → UPDATE)
    $decision_record->execution_time_ms = round($duration_ms, 2);

    $decision_record->timestamp = date('Y-m-d H:i:s');
    $decision_record->created_at = date('Y-m-d H:i:s');
    $decision_record->notes = $rationale;

    // ✅ Single database write (no UPDATE needed)
    try {
        $decision_id = $DB->insert_record('mvp_decision_log', $decision_record);
    } catch (Exception $e) {
        throw new Exception("Failed to insert decision log: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
    }

    // Update agent statistics
    $this->update_agent_stats($agent_id_safe, $confidence_safe);

    return [
        'decision_id' => $decision_id,
        'action' => $action_safe,
        'confidence' => $confidence_safe,
        'rationale' => $rationale,
        'result' => $action_result,
        'execution_time_ms' => round($duration_ms, 2)
    ];
}


// ============================================================================
// OPTIMIZATION 2: Lazy Graph Building
// ============================================================================
// Location: MVPAgentOrchestrator_v2.php lines 383-467
// Impact: -30% graph building overhead when cascades disabled

/**
 * BEFORE (SLOW - always builds graph):
 *
 * // Lines 394-398
 * $graph_manager = new GraphManager($DB);
 * $graph = $graph_manager->build_graph();
 *
 * // Lines 452-467
 * $cascade_executed = $this->cascade_engine->propagate(...);
 * // ❌ PROBLEM: Graph built even when cascades disabled
 */

/**
 * AFTER (FAST - conditional graph building):
 */
function process_context_OPTIMIZED($context) {
    global $DB;

    $start_time = microtime(true);

    // Validate context
    if (!is_array($context) || empty($context['student_id'])) {
        throw new Exception("Invalid context: student_id is required at " . __FILE__ . ":" . __LINE__);
    }

    // Find best matching rule
    $best_match = $this->rule_matcher->find_best_match($context);

    if (!$best_match) {
        return [
            'success' => false,
            'message' => 'No matching rule found',
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        ];
    }

    $initial_rule_id = $best_match['rule_id'];

    // ✅ OPTIMIZATION: Only build graph if cascades are enabled
    $cascades_enabled = $this->is_cascade_enabled($initial_rule_id);

    $graph = null;
    if ($cascades_enabled) {
        $graph_manager = new GraphManager($DB);
        $graph = $graph_manager->build_graph();
    }

    // Execute initial decision
    $initial_result = $this->execute_decision(
        $best_match['agent_id'],
        $context,
        $best_match,
        $best_match['confidence'],
        null,
        0
    );

    // ✅ OPTIMIZATION: Skip cascade if not needed
    if ($cascades_enabled && $graph && !empty($graph[$initial_rule_id])) {
        $cascade_results = $this->cascade_engine->propagate(
            $initial_rule_id, $context, $graph,
            function($rule, $context, $confidence) use ($best_match, $initial_result) {
                return $this->execute_decision(
                    $best_match['agent_id'],
                    $context, $rule,
                    $confidence,
                    $initial_result['decision_id'],  // Proper parent tracking
                    1  // Depth = 1 for first cascade level
                );
            }
        );
    } else {
        $cascade_results = [];
    }

    return [
        'success' => true,
        'initial_decision' => $initial_result,
        'cascade_decisions' => $cascade_results,
        'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
    ];
}

/**
 * Helper function to check if cascade is enabled for a rule
 */
function is_cascade_enabled($rule_id) {
    global $DB;

    // ✅ Cache cascade settings per session
    static $cascade_cache = [];

    if (isset($cascade_cache[$rule_id])) {
        return $cascade_cache[$rule_id];
    }

    // Check if rule has outgoing edges in graph
    $has_cascades = $DB->record_exists('mvp_rule_graph', ['from_rule_id' => $rule_id]);

    $cascade_cache[$rule_id] = $has_cascades;
    return $has_cascades;
}


// ============================================================================
// OPTIMIZATION 3: Fix Moodle DML Double Prefix Bug
// ============================================================================
// Location: MVPAgentOrchestrator_v2.php lines 659, 688, 713
// Impact: Prevent query failures

/**
 * BEFORE (BROKEN):
 *
 * // Line 659
 * $stats = $DB->get_record('mdl_mvp_agent_status', ['agent_id' => $agent_id]);
 * // Results in: SELECT * FROM mdl_mdl_mvp_agent_status ❌ WRONG
 *
 * // Line 688
 * $DB->insert_record('mdl_mvp_agent_status', $new_stats);
 * // Results in: INSERT INTO mdl_mdl_mvp_agent_status ❌ WRONG
 *
 * // Line 713
 * $DB->update_record('mdl_mvp_agent_status', $stats);
 * // Results in: UPDATE mdl_mdl_mvp_agent_status ❌ WRONG
 */

/**
 * AFTER (FIXED):
 */
function update_agent_stats_FIXED($agent_id, $confidence) {
    global $DB;

    // ✅ FIX: Remove 'mdl_' prefix (Moodle adds it automatically)
    $stats = $DB->get_record('mvp_agent_status', ['agent_id' => $agent_id]);
    // Results in: SELECT * FROM mdl_mvp_agent_status ✅ CORRECT

    if (!$stats) {
        // ✅ Auto-initialize new agent
        $new_stats = new stdClass();
        $new_stats->agent_id = $agent_id;
        $new_stats->total_decisions = 1;
        $new_stats->avg_confidence = $confidence;
        $new_stats->last_active = date('Y-m-d H:i:s');
        $new_stats->created_at = date('Y-m-d H:i:s');

        $DB->insert_record('mvp_agent_status', $new_stats);
        // Results in: INSERT INTO mdl_mvp_agent_status ✅ CORRECT

        return;
    }

    // Update existing stats
    $stats->total_decisions += 1;

    // Moving average: new_avg = (old_avg * (n-1) + new_value) / n
    $stats->avg_confidence = (($stats->avg_confidence * ($stats->total_decisions - 1)) + $confidence) / $stats->total_decisions;
    $stats->last_active = date('Y-m-d H:i:s');

    $DB->update_record('mvp_agent_status', $stats);
    // Results in: UPDATE mdl_mvp_agent_status ✅ CORRECT
}


// ============================================================================
// INSTALLATION INSTRUCTIONS
// ============================================================================

/**
 * 📋 How to Apply This Patch:
 *
 * 1. **Backup Original File**:
 *    cp lib/MVPAgentOrchestrator_v2.php lib/MVPAgentOrchestrator_v2.php.backup
 *
 * 2. **Apply Optimizations**:
 *    Replace the following methods in MVPAgentOrchestrator_v2.php:
 *    - execute_decision() → execute_decision_OPTIMIZED()
 *    - process_context() → process_context_OPTIMIZED()
 *    - update_agent_stats() → update_agent_stats_FIXED()
 *    - Add is_cascade_enabled() helper method
 *
 * 3. **Run Performance Test**:
 *    php tests/test_backward_compatibility.php
 *
 * 4. **Verify Results**:
 *    ✅ All tests should pass (4/4)
 *    ✅ Performance overhead should be ≤50% (target: ≤20%)
 *    ✅ No database errors
 *
 * 5. **Rollback If Needed**:
 *    cp lib/MVPAgentOrchestrator_v2.php.backup lib/MVPAgentOrchestrator_v2.php
 */

// ============================================================================
// EXPECTED PERFORMANCE IMPROVEMENTS
// ============================================================================

/**
 * Performance Breakdown:
 *
 * BEFORE:
 * - Database writes: 2 queries per decision (INSERT + UPDATE)
 * - Graph building: Always executed (even when not needed)
 * - JSON encoding: Always executed (even for empty data)
 * - Average response time: 4.73ms
 * - Overhead: 216.1%
 *
 * AFTER:
 * - Database writes: 1 query per decision (INSERT only) ✅ -50% DB ops
 * - Graph building: Only when cascades enabled ✅ -30% in non-cascade mode
 * - JSON encoding: Conditional (only when data exists) ✅ -10% CPU
 * - Expected response time: 2.0-2.5ms
 * - Expected overhead: 30-50% (acceptable range)
 *
 * TOTAL ESTIMATED REDUCTION: 150-180% overhead
 * TARGET: ≤20% overhead
 * REALISTIC EXPECTATION: 30-50% overhead (still acceptable)
 */

// ============================================================================
// Database Fields Reference
// ============================================================================

/**
 * mdl_mvp_decision_log (V2 Schema):
 * - id: BIGINT(10) AUTO_INCREMENT PRIMARY KEY
 * - student_id: BIGINT(10) NOT NULL (indexed)
 * - agent_id: VARCHAR(50) NULL (indexed)
 * - agent_name: VARCHAR(100) NULL
 * - rule_id: VARCHAR(100) NULL (indexed)
 * - action: VARCHAR(50) NOT NULL (indexed)
 * - confidence: DECIMAL(5,4) NOT NULL
 * - rationale: TEXT NOT NULL
 * - context_data: TEXT NULL
 * - result_data: TEXT NULL
 * - is_cascade: TINYINT(1) NOT NULL DEFAULT 0
 * - cascade_depth: INT NOT NULL DEFAULT 0
 * - parent_decision_id: BIGINT NULL
 * - execution_time_ms: DECIMAL(10,2) NULL
 * - timestamp: DATETIME NOT NULL (indexed)
 * - created_at: DATETIME DEFAULT CURRENT_TIMESTAMP
 * - notes: TEXT NULL
 */
