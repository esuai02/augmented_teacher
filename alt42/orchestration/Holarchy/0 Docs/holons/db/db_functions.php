<?php
/**
 * Quantum A/B Testing Framework - Database Functions Library
 *
 * Phase 11.1: Database Integration
 * Provides CRUD operations and helper functions for A/B testing tables
 *
 * @package    local_augmented_teacher
 * @subpackage quantum_ab_testing
 * @copyright  2025 Quantum Orchestrator Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Tables:
 *   - mdl_quantum_ab_tests: Group assignments
 *   - mdl_quantum_ab_test_outcomes: Learning metrics
 *   - mdl_quantum_ab_test_state_changes: 8D StateVector tracking
 *   - mdl_quantum_ab_test_reports: Cached analysis reports
 *   - mdl_quantum_ab_test_config: Test configuration
 *
 * Usage:
 *   require_once('db/db_functions.php');
 *   $group = quantum_ab_get_student_group('quantum_v1', $USER->id);
 */

defined('MOODLE_INTERNAL') || define('MOODLE_INTERNAL', true);

// =========================================================
// Constants
// =========================================================

/** Default treatment ratio (50/50 split) */
define('QUANTUM_AB_DEFAULT_RATIO', 0.50);

/** Default random seed for reproducibility */
define('QUANTUM_AB_DEFAULT_SEED', 42);

/** Report cache duration in seconds (1 hour) */
define('QUANTUM_AB_CACHE_TTL', 3600);

/** 8D StateVector dimensions */
define('QUANTUM_AB_DIMENSIONS', serialize([
    'cognitive_clarity',
    'emotional_stability',
    'attention_level',
    'motivation_strength',
    'energy_level',
    'social_connection',
    'creative_flow',
    'learning_momentum'
]));

// =========================================================
// 1. GROUP ASSIGNMENT FUNCTIONS
// =========================================================

/**
 * Get student's A/B test group assignment
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @return object|false Group assignment record or false
 */
function quantum_ab_get_student_group($testId, $studentId) {
    global $DB;

    return $DB->get_record('quantum_ab_tests', [
        'test_id' => $testId,
        'student_id' => $studentId
    ]);
}

/**
 * Assign student to A/B test group using deterministic hashing
 *
 * Algorithm:
 *   1. Generate MD5 hash of (test_id + student_id + seed)
 *   2. Convert first 8 hex chars to decimal
 *   3. Normalize to 0-1 range
 *   4. Assign to treatment if hash_value < treatment_ratio
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @param float $treatmentRatio Ratio for treatment group (0.0-1.0)
 * @param int $seed Random seed for reproducibility
 * @return object Group assignment record
 */
function quantum_ab_assign_student_group($testId, $studentId, $treatmentRatio = null, $seed = null) {
    global $DB;

    // Check for existing assignment
    $existing = quantum_ab_get_student_group($testId, $studentId);
    if ($existing) {
        return $existing;
    }

    // Get test config for defaults
    $config = quantum_ab_get_test_config($testId);
    if ($treatmentRatio === null) {
        $treatmentRatio = $config ? $config->treatment_ratio : QUANTUM_AB_DEFAULT_RATIO;
    }
    if ($seed === null) {
        $seed = QUANTUM_AB_DEFAULT_SEED;
    }

    // Calculate deterministic hash
    $hashInput = $testId . '_' . $studentId . '_' . $seed;
    $hashValue = quantum_ab_calculate_hash($hashInput);

    // Determine group
    $groupName = ($hashValue < $treatmentRatio) ? 'treatment' : 'control';

    // Create assignment record
    $record = new stdClass();
    $record->test_id = $testId;
    $record->student_id = $studentId;
    $record->group_name = $groupName;
    $record->treatment_ratio = $treatmentRatio;
    $record->seed = $seed;
    $record->hash_value = $hashValue;
    $record->timecreated = time();
    $record->timemodified = time();

    $record->id = $DB->insert_record('quantum_ab_tests', $record);

    return $record;
}

/**
 * Get group counts for a test
 *
 * @param string $testId Test identifier
 * @return object Object with control_count and treatment_count
 */
function quantum_ab_get_group_counts($testId) {
    global $DB;

    $sql = "SELECT group_name, COUNT(*) as count
            FROM {quantum_ab_tests}
            WHERE test_id = ?
            GROUP BY group_name";

    $counts = $DB->get_records_sql($sql, [$testId]);

    $result = new stdClass();
    $result->control_count = 0;
    $result->treatment_count = 0;
    $result->total = 0;

    foreach ($counts as $row) {
        if ($row->group_name === 'control') {
            $result->control_count = (int)$row->count;
        } else if ($row->group_name === 'treatment') {
            $result->treatment_count = (int)$row->count;
        }
    }
    $result->total = $result->control_count + $result->treatment_count;

    return $result;
}

/**
 * Get all students in a specific group
 *
 * @param string $testId Test identifier
 * @param string $groupName Group name ('control' or 'treatment')
 * @return array Array of assignment records
 */
function quantum_ab_get_group_students($testId, $groupName) {
    global $DB;

    return $DB->get_records('quantum_ab_tests', [
        'test_id' => $testId,
        'group_name' => $groupName
    ]);
}

// =========================================================
// 2. OUTCOME RECORDING FUNCTIONS
// =========================================================

/**
 * Record a learning outcome metric
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @param string $metricName Metric name (learning_gain, engagement_rate, etc.)
 * @param float $metricValue Numeric value of the metric
 * @param string|null $sessionId Optional session identifier
 * @param array|null $contextData Optional additional context
 * @return int Record ID
 */
function quantum_ab_record_outcome($testId, $studentId, $metricName, $metricValue, $sessionId = null, $contextData = null) {
    global $DB;

    $record = new stdClass();
    $record->test_id = $testId;
    $record->student_id = $studentId;
    $record->metric_name = $metricName;
    $record->metric_value = $metricValue;
    $record->session_id = $sessionId;
    $record->context_data = $contextData ? json_encode($contextData) : null;
    $record->timecreated = time();

    return $DB->insert_record('quantum_ab_test_outcomes', $record);
}

/**
 * Get outcomes for a student
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @param string|null $metricName Optional filter by metric name
 * @return array Array of outcome records
 */
function quantum_ab_get_student_outcomes($testId, $studentId, $metricName = null) {
    global $DB;

    $conditions = [
        'test_id' => $testId,
        'student_id' => $studentId
    ];

    if ($metricName !== null) {
        $conditions['metric_name'] = $metricName;
    }

    return $DB->get_records('quantum_ab_test_outcomes', $conditions, 'timecreated DESC');
}

/**
 * Get metrics summary for a test by group
 *
 * @param string $testId Test identifier
 * @param string $metricName Metric name to summarize
 * @return array Associative array with control and treatment statistics
 */
function quantum_ab_get_metrics_summary($testId, $metricName) {
    global $DB;

    $sql = "SELECT
                t.group_name,
                COUNT(DISTINCT o.student_id) as n,
                AVG(o.metric_value) as mean,
                STDDEV(o.metric_value) as std,
                MIN(o.metric_value) as min_val,
                MAX(o.metric_value) as max_val
            FROM {quantum_ab_test_outcomes} o
            JOIN {quantum_ab_tests} t ON o.test_id = t.test_id AND o.student_id = t.student_id
            WHERE o.test_id = ? AND o.metric_name = ?
            GROUP BY t.group_name";

    $results = $DB->get_records_sql($sql, [$testId, $metricName]);

    $summary = [
        'control' => null,
        'treatment' => null
    ];

    foreach ($results as $row) {
        $stats = new stdClass();
        $stats->n = (int)$row->n;
        $stats->mean = round((float)$row->mean, 4);
        $stats->std = round((float)$row->std, 4);
        $stats->min = round((float)$row->min_val, 4);
        $stats->max = round((float)$row->max_val, 4);

        $summary[$row->group_name] = $stats;
    }

    return $summary;
}

/**
 * Get all available metrics for a test
 *
 * @param string $testId Test identifier
 * @return array Array of metric names
 */
function quantum_ab_get_available_metrics($testId) {
    global $DB;

    $sql = "SELECT DISTINCT metric_name FROM {quantum_ab_test_outcomes} WHERE test_id = ?";
    $records = $DB->get_records_sql($sql, [$testId]);

    return array_keys($records);
}

// =========================================================
// 3. STATE CHANGE FUNCTIONS (8D StateVector)
// =========================================================

/**
 * Record a state change in the 8D StateVector
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @param string $dimensionName One of the 8D dimensions
 * @param float|null $beforeValue Value before intervention
 * @param float|null $afterValue Value after intervention
 * @param string|null $interventionType Type of intervention applied
 * @param int|null $agentId Agent that caused the change
 * @return int Record ID
 */
function quantum_ab_record_state_change($testId, $studentId, $dimensionName, $beforeValue = null, $afterValue = null, $interventionType = null, $agentId = null) {
    global $DB;

    $changeValue = null;
    if ($beforeValue !== null && $afterValue !== null) {
        $changeValue = $afterValue - $beforeValue;
    }

    $record = new stdClass();
    $record->test_id = $testId;
    $record->student_id = $studentId;
    $record->dimension_name = $dimensionName;
    $record->before_value = $beforeValue;
    $record->after_value = $afterValue;
    $record->change_value = $changeValue;
    $record->intervention_type = $interventionType;
    $record->agent_id = $agentId;
    $record->timecreated = time();

    return $DB->insert_record('quantum_ab_test_state_changes', $record);
}

/**
 * Get state changes for a student
 *
 * @param string $testId Test identifier
 * @param int $studentId Moodle user ID
 * @param string|null $dimensionName Optional filter by dimension
 * @return array Array of state change records
 */
function quantum_ab_get_state_changes($testId, $studentId, $dimensionName = null) {
    global $DB;

    $conditions = [
        'test_id' => $testId,
        'student_id' => $studentId
    ];

    if ($dimensionName !== null) {
        $conditions['dimension_name'] = $dimensionName;
    }

    return $DB->get_records('quantum_ab_test_state_changes', $conditions, 'timecreated DESC');
}

/**
 * Get dimension summary by group
 *
 * @param string $testId Test identifier
 * @param string $dimensionName 8D dimension name
 * @return array Associative array with control and treatment statistics
 */
function quantum_ab_get_dimension_summary($testId, $dimensionName) {
    global $DB;

    $sql = "SELECT
                t.group_name,
                COUNT(*) as n,
                AVG(s.change_value) as avg_change,
                STDDEV(s.change_value) as std_change,
                AVG(s.before_value) as avg_before,
                AVG(s.after_value) as avg_after
            FROM {quantum_ab_test_state_changes} s
            JOIN {quantum_ab_tests} t ON s.test_id = t.test_id AND s.student_id = t.student_id
            WHERE s.test_id = ? AND s.dimension_name = ? AND s.change_value IS NOT NULL
            GROUP BY t.group_name";

    $results = $DB->get_records_sql($sql, [$testId, $dimensionName]);

    $summary = [
        'control' => null,
        'treatment' => null
    ];

    foreach ($results as $row) {
        $stats = new stdClass();
        $stats->n = (int)$row->n;
        $stats->avg_change = round((float)$row->avg_change, 4);
        $stats->std_change = round((float)$row->std_change, 4);
        $stats->avg_before = round((float)$row->avg_before, 4);
        $stats->avg_after = round((float)$row->avg_after, 4);

        $summary[$row->group_name] = $stats;
    }

    return $summary;
}

/**
 * Get all 8D dimensions
 *
 * @return array Array of dimension names
 */
function quantum_ab_get_dimensions() {
    return unserialize(QUANTUM_AB_DIMENSIONS);
}

// =========================================================
// 4. REPORT FUNCTIONS
// =========================================================

/**
 * Cache a report for a test
 *
 * @param string $testId Test identifier
 * @param string $reportType Report type (overview, metrics, full)
 * @param array $reportData Report data array
 * @param string|null $recommendation ADOPT, CONTINUE, or REJECT
 * @param string|null $confidence high, medium, or low
 * @param int|null $ttl Cache TTL in seconds
 * @return int Record ID
 */
function quantum_ab_cache_report($testId, $reportType, $reportData, $recommendation = null, $confidence = null, $ttl = null) {
    global $DB;

    if ($ttl === null) {
        $ttl = QUANTUM_AB_CACHE_TTL;
    }

    // Get group counts for the report
    $counts = quantum_ab_get_group_counts($testId);

    $record = new stdClass();
    $record->test_id = $testId;
    $record->report_type = $reportType;
    $record->report_data = json_encode($reportData);
    $record->control_size = $counts->control_count;
    $record->treatment_size = $counts->treatment_count;
    $record->recommendation = $recommendation;
    $record->confidence = $confidence;
    $record->timecreated = time();
    $record->valid_until = time() + $ttl;

    // Check for existing report and update or insert
    $existing = $DB->get_record('quantum_ab_test_reports', [
        'test_id' => $testId,
        'report_type' => $reportType
    ]);

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('quantum_ab_test_reports', $record);
        return $record->id;
    }

    return $DB->insert_record('quantum_ab_test_reports', $record);
}

/**
 * Get cached report if valid
 *
 * @param string $testId Test identifier
 * @param string $reportType Report type
 * @return object|false Report record with decoded data or false
 */
function quantum_ab_get_cached_report($testId, $reportType) {
    global $DB;

    $record = $DB->get_record('quantum_ab_test_reports', [
        'test_id' => $testId,
        'report_type' => $reportType
    ]);

    if (!$record) {
        return false;
    }

    // Check if cache is still valid
    if ($record->valid_until < time()) {
        return false;
    }

    // Decode report data
    $record->report_data = json_decode($record->report_data, true);

    return $record;
}

/**
 * Invalidate report cache for a test
 *
 * @param string $testId Test identifier
 * @param string|null $reportType Optional specific report type
 * @return bool Success
 */
function quantum_ab_invalidate_report_cache($testId, $reportType = null) {
    global $DB;

    $conditions = ['test_id' => $testId];
    if ($reportType !== null) {
        $conditions['report_type'] = $reportType;
    }

    return $DB->delete_records('quantum_ab_test_reports', $conditions);
}

/**
 * Clean expired reports from cache
 *
 * @return int Number of deleted records
 */
function quantum_ab_clean_expired_reports() {
    global $DB;

    $sql = "DELETE FROM {quantum_ab_test_reports} WHERE valid_until < ?";
    return $DB->execute($sql, [time()]);
}

// =========================================================
// 5. CONFIGURATION FUNCTIONS
// =========================================================

/**
 * Get test configuration
 *
 * @param string $testId Test identifier
 * @return object|false Test configuration record or false
 */
function quantum_ab_get_test_config($testId) {
    global $DB;

    $config = $DB->get_record('quantum_ab_test_config', ['test_id' => $testId]);

    if ($config && $config->target_metrics) {
        $config->target_metrics = json_decode($config->target_metrics, true);
    }

    return $config;
}

/**
 * Create a new A/B test
 *
 * @param string $testId Unique test identifier
 * @param string $testName Human readable name
 * @param string|null $description Test description
 * @param float $treatmentRatio Treatment group ratio
 * @param array $targetMetrics Array of metric names to track
 * @param int|null $createdBy User ID (defaults to current user)
 * @return int Record ID
 */
function quantum_ab_create_test($testId, $testName, $description = null, $treatmentRatio = null, $targetMetrics = null, $createdBy = null) {
    global $DB, $USER;

    if ($treatmentRatio === null) {
        $treatmentRatio = QUANTUM_AB_DEFAULT_RATIO;
    }

    if ($targetMetrics === null) {
        $targetMetrics = ['learning_gain', 'engagement_rate', 'effectiveness_score'];
    }

    if ($createdBy === null) {
        $createdBy = $USER->id;
    }

    $record = new stdClass();
    $record->test_id = $testId;
    $record->test_name = $testName;
    $record->description = $description;
    $record->status = 'active';
    $record->treatment_ratio = $treatmentRatio;
    $record->min_sample_size = 100;
    $record->target_metrics = json_encode($targetMetrics);
    $record->created_by = $createdBy;
    $record->timecreated = time();
    $record->timemodified = time();
    $record->timestarted = time();
    $record->timeended = null;

    return $DB->insert_record('quantum_ab_test_config', $record);
}

/**
 * Update test status
 *
 * @param string $testId Test identifier
 * @param string $status New status (active, paused, completed, archived)
 * @return bool Success
 */
function quantum_ab_update_test_status($testId, $status) {
    global $DB;

    $validStatuses = ['active', 'paused', 'completed', 'archived'];
    if (!in_array($status, $validStatuses)) {
        return false;
    }

    $record = new stdClass();
    $record->status = $status;
    $record->timemodified = time();

    if ($status === 'completed') {
        $record->timeended = time();
    }

    return $DB->set_field('quantum_ab_test_config', 'status', $status, ['test_id' => $testId]) &&
           $DB->set_field('quantum_ab_test_config', 'timemodified', time(), ['test_id' => $testId]);
}

/**
 * Get all active tests
 *
 * @return array Array of test configuration records
 */
function quantum_ab_get_active_tests() {
    global $DB;

    return $DB->get_records('quantum_ab_test_config', ['status' => 'active']);
}

/**
 * Get all tests
 *
 * @param string|null $status Optional filter by status
 * @return array Array of test configuration records
 */
function quantum_ab_get_all_tests($status = null) {
    global $DB;

    if ($status !== null) {
        return $DB->get_records('quantum_ab_test_config', ['status' => $status]);
    }

    return $DB->get_records('quantum_ab_test_config');
}

// =========================================================
// 6. STATISTICAL HELPER FUNCTIONS
// =========================================================

/**
 * Calculate deterministic hash value for group assignment
 *
 * @param string $input Input string to hash
 * @return float Hash value normalized to 0-1 range
 */
function quantum_ab_calculate_hash($input) {
    $hash = md5($input);
    $hexValue = substr($hash, 0, 8);
    $decValue = hexdec($hexValue);
    $maxValue = hexdec('ffffffff');

    return $decValue / $maxValue;
}

/**
 * Calculate Cohen's d effect size
 *
 * @param float $mean1 Mean of group 1
 * @param float $mean2 Mean of group 2
 * @param float $std1 Standard deviation of group 1
 * @param float $std2 Standard deviation of group 2
 * @return float Cohen's d value
 */
function quantum_ab_calculate_cohens_d($mean1, $mean2, $std1, $std2) {
    $pooledStd = sqrt((pow($std1, 2) + pow($std2, 2)) / 2);

    if ($pooledStd == 0) {
        return 0;
    }

    return abs($mean2 - $mean1) / $pooledStd;
}

/**
 * Interpret Cohen's d effect size
 *
 * @param float $cohensD Cohen's d value
 * @return string Effect size interpretation
 */
function quantum_ab_interpret_effect_size($cohensD) {
    $d = abs($cohensD);

    if ($d >= 0.8) return 'large';
    if ($d >= 0.5) return 'medium';
    if ($d >= 0.2) return 'small';
    return 'negligible';
}

/**
 * Calculate t-statistic for independent samples
 *
 * @param float $mean1 Mean of group 1
 * @param float $mean2 Mean of group 2
 * @param float $std1 Standard deviation of group 1
 * @param float $std2 Standard deviation of group 2
 * @param int $n1 Sample size of group 1
 * @param int $n2 Sample size of group 2
 * @return float t-statistic
 */
function quantum_ab_calculate_t_statistic($mean1, $mean2, $std1, $std2, $n1, $n2) {
    if ($n1 == 0 || $n2 == 0) {
        return 0;
    }

    $se = sqrt((pow($std1, 2) / $n1) + (pow($std2, 2) / $n2));

    if ($se == 0) {
        return 0;
    }

    return ($mean2 - $mean1) / $se;
}

/**
 * Approximate p-value from t-statistic
 *
 * @param float $t t-statistic
 * @param int $df Degrees of freedom
 * @return float Approximate p-value
 */
function quantum_ab_approximate_p_value($t, $df) {
    $absT = abs($t);

    // Approximation based on t-distribution critical values
    if ($absT > 3.5) return 0.001;
    if ($absT > 2.576) return 0.01;
    if ($absT > 1.96) return 0.05;
    if ($absT > 1.645) return 0.1;

    return 0.5;
}

/**
 * Calculate complete statistics for a metric comparison
 *
 * @param string $testId Test identifier
 * @param string $metricName Metric name
 * @return array Complete statistical analysis
 */
function quantum_ab_calculate_statistics($testId, $metricName) {
    $summary = quantum_ab_get_metrics_summary($testId, $metricName);

    $result = [
        'metric_name' => $metricName,
        'control' => $summary['control'],
        'treatment' => $summary['treatment'],
        'analysis' => null
    ];

    if ($summary['control'] && $summary['treatment']) {
        $control = $summary['control'];
        $treatment = $summary['treatment'];

        $cohensD = quantum_ab_calculate_cohens_d(
            $control->mean, $treatment->mean,
            $control->std, $treatment->std
        );

        $tStat = quantum_ab_calculate_t_statistic(
            $control->mean, $treatment->mean,
            $control->std, $treatment->std,
            $control->n, $treatment->n
        );

        $df = $control->n + $treatment->n - 2;
        $pValue = quantum_ab_approximate_p_value($tStat, $df);

        $result['analysis'] = [
            'difference' => round($treatment->mean - $control->mean, 4),
            'percent_change' => $control->mean != 0 ?
                round(($treatment->mean - $control->mean) / $control->mean * 100, 2) : 0,
            'cohens_d' => round($cohensD, 4),
            'effect_size' => quantum_ab_interpret_effect_size($cohensD),
            't_statistic' => round($tStat, 4),
            'p_value' => $pValue,
            'significant' => $pValue < 0.05,
            'df' => $df
        ];
    }

    return $result;
}

/**
 * Generate recommendation based on metrics analysis
 *
 * @param string $testId Test identifier
 * @return array Recommendation with action, confidence, and reasoning
 */
function quantum_ab_generate_recommendation($testId) {
    $config = quantum_ab_get_test_config($testId);

    if (!$config || !$config->target_metrics) {
        return [
            'action' => 'CONTINUE',
            'confidence' => 'low',
            'message' => 'Insufficient configuration for recommendation',
            'details' => []
        ];
    }

    $metrics = is_array($config->target_metrics) ? $config->target_metrics : [$config->target_metrics];
    $counts = quantum_ab_get_group_counts($testId);

    // Check minimum sample size
    if ($counts->total < $config->min_sample_size) {
        return [
            'action' => 'CONTINUE',
            'confidence' => 'low',
            'message' => "Need more data. Current: {$counts->total}, Required: {$config->min_sample_size}",
            'details' => ['sample_size' => $counts->total, 'required' => $config->min_sample_size]
        ];
    }

    // Analyze each metric
    $largeEffects = 0;
    $significantResults = 0;
    $details = [];

    foreach ($metrics as $metricName) {
        $stats = quantum_ab_calculate_statistics($testId, $metricName);

        if ($stats['analysis']) {
            $details[$metricName] = $stats['analysis'];

            if ($stats['analysis']['effect_size'] === 'large') {
                $largeEffects++;
            }
            if ($stats['analysis']['significant']) {
                $significantResults++;
            }
        }
    }

    // Decision logic
    if ($largeEffects >= 2 && $significantResults >= 2) {
        return [
            'action' => 'ADOPT',
            'confidence' => 'high',
            'message' => 'Treatment shows significant improvement across multiple metrics',
            'details' => $details
        ];
    }

    if ($largeEffects >= 1 || $significantResults >= 1) {
        return [
            'action' => 'CONTINUE',
            'confidence' => 'medium',
            'message' => 'Promising results, continue testing for stronger evidence',
            'details' => $details
        ];
    }

    return [
        'action' => 'REJECT',
        'confidence' => 'medium',
        'message' => 'No significant improvements detected',
        'details' => $details
    ];
}

// =========================================================
// 7. COMPREHENSIVE REPORT FUNCTIONS
// =========================================================

/**
 * Generate full A/B test report
 *
 * @param string $testId Test identifier
 * @param bool $useCache Whether to use cached report if available
 * @return array Complete report data
 */
function quantum_ab_generate_full_report($testId, $useCache = true) {
    // Check cache first
    if ($useCache) {
        $cached = quantum_ab_get_cached_report($testId, 'full');
        if ($cached) {
            return $cached->report_data;
        }
    }

    $config = quantum_ab_get_test_config($testId);
    $counts = quantum_ab_get_group_counts($testId);
    $recommendation = quantum_ab_generate_recommendation($testId);

    $report = [
        'test_id' => $testId,
        'generated_at' => date('Y-m-d H:i:s'),
        'overview' => [
            'test_name' => $config ? $config->test_name : $testId,
            'status' => $config ? $config->status : 'unknown',
            'control_size' => $counts->control_count,
            'treatment_size' => $counts->treatment_count,
            'total_participants' => $counts->total
        ],
        'metrics' => [],
        'state_changes' => [],
        'recommendation' => $recommendation
    ];

    // Add metrics analysis
    if ($config && $config->target_metrics) {
        $metrics = is_array($config->target_metrics) ? $config->target_metrics : [$config->target_metrics];
        foreach ($metrics as $metricName) {
            $report['metrics'][$metricName] = quantum_ab_calculate_statistics($testId, $metricName);
        }
    }

    // Add 8D state changes summary
    $dimensions = quantum_ab_get_dimensions();
    foreach ($dimensions as $dimension) {
        $report['state_changes'][$dimension] = quantum_ab_get_dimension_summary($testId, $dimension);
    }

    // Cache the report
    quantum_ab_cache_report(
        $testId,
        'full',
        $report,
        $recommendation['action'],
        $recommendation['confidence']
    );

    return $report;
}

/**
 * Generate overview report (lightweight)
 *
 * @param string $testId Test identifier
 * @return array Overview data
 */
function quantum_ab_generate_overview_report($testId) {
    $cached = quantum_ab_get_cached_report($testId, 'overview');
    if ($cached) {
        return $cached->report_data;
    }

    $config = quantum_ab_get_test_config($testId);
    $counts = quantum_ab_get_group_counts($testId);

    $report = [
        'test_id' => $testId,
        'test_name' => $config ? $config->test_name : $testId,
        'status' => $config ? $config->status : 'unknown',
        'control_size' => $counts->control_count,
        'treatment_size' => $counts->treatment_count,
        'total' => $counts->total,
        'treatment_ratio' => $config ? $config->treatment_ratio : QUANTUM_AB_DEFAULT_RATIO,
        'started' => $config && $config->timestarted ? date('Y-m-d H:i:s', $config->timestarted) : null
    ];

    quantum_ab_cache_report($testId, 'overview', $report);

    return $report;
}

// =========================================================
// Database Schema Reference
// =========================================================
/*
Tables used by this library:

1. mdl_quantum_ab_tests
   - id (BIGINT, PK)
   - test_id (VARCHAR 255)
   - student_id (BIGINT)
   - group_name (VARCHAR 50)
   - treatment_ratio (DECIMAL 5,2)
   - seed (INT)
   - hash_value (DECIMAL 10,8)
   - timecreated, timemodified (BIGINT)

2. mdl_quantum_ab_test_outcomes
   - id (BIGINT, PK)
   - test_id, student_id
   - metric_name (VARCHAR 100)
   - metric_value (DECIMAL 12,4)
   - session_id (VARCHAR 100)
   - context_data (TEXT - JSON)
   - timecreated

3. mdl_quantum_ab_test_state_changes
   - id (BIGINT, PK)
   - test_id, student_id
   - dimension_name (VARCHAR 100)
   - before_value, after_value, change_value (DECIMAL 10,4)
   - intervention_type (VARCHAR 50)
   - agent_id (INT)
   - timecreated

4. mdl_quantum_ab_test_reports
   - id (BIGINT, PK)
   - test_id, report_type
   - report_data (LONGTEXT - JSON)
   - control_size, treatment_size
   - recommendation, confidence
   - timecreated, valid_until

5. mdl_quantum_ab_test_config
   - id (BIGINT, PK)
   - test_id (UNIQUE), test_name
   - description, status
   - treatment_ratio, min_sample_size
   - target_metrics (TEXT - JSON)
   - created_by
   - timecreated, timemodified, timestarted, timeended
*/
