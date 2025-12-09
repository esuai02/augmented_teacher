<?php
/**
 * 📊 Automated Performance Benchmark Tool
 *
 * Purpose: Measure and compare MVPAgentOrchestrator V1 vs V2 performance
 *
 * Features:
 * - Automated 10-iteration benchmark
 * - Statistical analysis (mean, median, std dev)
 * - Before/After comparison
 * - JSON output for logging
 * - Visual progress indicators
 *
 * Usage:
 *   php scripts/benchmark_performance.php
 *   php scripts/benchmark_performance.php --iterations 20
 *   php scripts/benchmark_performance.php --json > results.json
 *
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/scripts/benchmark_performance.php
 */

define('CLI_SCRIPT', true);
require_once("/home/moodle/public_html/moodle/config.php");
require_once($CFG->libdir.'/clilib.php');

// Parse command line arguments
$iterations = 10;
$output_json = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--iterations=') === 0) {
        $iterations = intval(substr($arg, 13));
    }
    if ($arg === '--json') {
        $output_json = true;
    }
}

// Validate iterations
if ($iterations < 1 || $iterations > 100) {
    echo "Error: Iterations must be between 1 and 100\n";
    exit(1);
}

if (!$output_json) {
    echo "🚀 MVPAgentOrchestrator Performance Benchmark\n";
    echo str_repeat("=", 70) . "\n";
    echo "Iterations: {$iterations}\n";
    echo "Start Time: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat("=", 70) . "\n\n";
}

// Test cases (same as backward compatibility tests)
$test_cases = [
    [
        'name' => 'High Confidence Match',
        'context' => [
            'student_id' => 12345,
            'current_level' => 5,
            'performance' => 0.85,
            'time_spent' => 120,
            'error_rate' => 0.1
        ]
    ],
    [
        'name' => 'Medium Confidence Match',
        'context' => [
            'student_id' => 12346,
            'current_level' => 3,
            'performance' => 0.65,
            'time_spent' => 90,
            'error_rate' => 0.3
        ]
    ],
    [
        'name' => 'Low Confidence Match',
        'context' => [
            'student_id' => 12347,
            'current_level' => 2,
            'performance' => 0.45,
            'time_spent' => 60,
            'error_rate' => 0.5
        ]
    ],
    [
        'name' => 'Edge Case - Minimal Context',
        'context' => [
            'student_id' => 12348,
            'current_level' => 1
        ]
    ]
];

// Initialize orchestrators
require_once(__DIR__ . '/../lib/MVPAgentOrchestrator_v1.php');
require_once(__DIR__ . '/../lib/MVPAgentOrchestrator_v2.php');

$orchestrator_v1 = new MVPAgentOrchestrator_v1();
$orchestrator_v2 = new MVPAgentOrchestrator_v2();

// Storage for results
$results = [
    'v1' => [],
    'v2' => [],
    'metadata' => [
        'iterations' => $iterations,
        'test_cases' => count($test_cases),
        'start_time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'moodle_version' => $CFG->version ?? 'unknown'
    ]
];

// Run benchmark
for ($iteration = 1; $iteration <= $iterations; $iteration++) {
    if (!$output_json) {
        echo "Iteration {$iteration}/{$iterations} ";
        flush();
    }

    $iteration_results_v1 = [];
    $iteration_results_v2 = [];

    foreach ($test_cases as $test) {
        // Test V1
        $start_v1 = microtime(true);
        try {
            $result_v1 = $orchestrator_v1->process_context($test['context']);
            $duration_v1 = (microtime(true) - $start_v1) * 1000;
            $iteration_results_v1[] = $duration_v1;
        } catch (Exception $e) {
            $iteration_results_v1[] = -1; // Error marker
        }

        // Test V2
        $start_v2 = microtime(true);
        try {
            $result_v2 = $orchestrator_v2->process_context($test['context']);
            $duration_v2 = (microtime(true) - $start_v2) * 1000;
            $iteration_results_v2[] = $duration_v2;
        } catch (Exception $e) {
            $iteration_results_v2[] = -1; // Error marker
        }
    }

    // Calculate averages for this iteration
    $avg_v1 = array_sum($iteration_results_v1) / count($iteration_results_v1);
    $avg_v2 = array_sum($iteration_results_v2) / count($iteration_results_v2);

    $results['v1'][] = [
        'iteration' => $iteration,
        'average_ms' => round($avg_v1, 2),
        'individual_ms' => array_map(function($v) { return round($v, 2); }, $iteration_results_v1)
    ];

    $results['v2'][] = [
        'iteration' => $iteration,
        'average_ms' => round($avg_v2, 2),
        'individual_ms' => array_map(function($v) { return round($v, 2); }, $iteration_results_v2)
    ];

    if (!$output_json) {
        echo "✓ (V1: " . round($avg_v1, 2) . "ms, V2: " . round($avg_v2, 2) . "ms)\n";
    }
}

// Calculate statistics
function calculate_stats($data) {
    $values = array_column($data, 'average_ms');

    $mean = array_sum($values) / count($values);

    sort($values);
    $count = count($values);
    $median = ($count % 2 == 0)
        ? ($values[$count/2 - 1] + $values[$count/2]) / 2
        : $values[floor($count/2)];

    $variance = 0;
    foreach ($values as $val) {
        $variance += pow($val - $mean, 2);
    }
    $std_dev = sqrt($variance / count($values));

    return [
        'mean' => round($mean, 2),
        'median' => round($median, 2),
        'std_dev' => round($std_dev, 2),
        'min' => round(min($values), 2),
        'max' => round(max($values), 2)
    ];
}

$stats_v1 = calculate_stats($results['v1']);
$stats_v2 = calculate_stats($results['v2']);

$overhead_percent = (($stats_v2['mean'] - $stats_v1['mean']) / $stats_v1['mean']) * 100;

// Add statistics to results
$results['statistics'] = [
    'v1' => $stats_v1,
    'v2' => $stats_v2,
    'overhead_percent' => round($overhead_percent, 1),
    'performance_ratio' => round($stats_v2['mean'] / $stats_v1['mean'], 2)
];

$results['metadata']['end_time'] = date('Y-m-d H:i:s');

// Output results
if ($output_json) {
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "📊 Benchmark Results\n";
    echo str_repeat("=", 70) . "\n\n";

    echo "V1 Performance:\n";
    echo "  Mean:   {$stats_v1['mean']} ms\n";
    echo "  Median: {$stats_v1['median']} ms\n";
    echo "  StdDev: {$stats_v1['std_dev']} ms\n";
    echo "  Range:  {$stats_v1['min']} - {$stats_v1['max']} ms\n\n";

    echo "V2 Performance:\n";
    echo "  Mean:   {$stats_v2['mean']} ms\n";
    echo "  Median: {$stats_v2['median']} ms\n";
    echo "  StdDev: {$stats_v2['std_dev']} ms\n";
    echo "  Range:  {$stats_v2['min']} - {$stats_v2['max']} ms\n\n";

    echo "Performance Comparison:\n";
    echo "  Overhead: {$overhead_percent}%\n";
    echo "  Ratio:    {$results['statistics']['performance_ratio']}x\n\n";

    // Visual indicator
    if ($overhead_percent <= 20) {
        echo "  Status: ✅ EXCELLENT (Target: ≤20%)\n";
    } elseif ($overhead_percent <= 50) {
        echo "  Status: ✅ ACCEPTABLE (Target: ≤50%)\n";
    } elseif ($overhead_percent <= 100) {
        echo "  Status: ⚠️  NEEDS IMPROVEMENT (Target: ≤50%)\n";
    } else {
        echo "  Status: ❌ CRITICAL (Target: ≤50%)\n";
    }

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "Benchmark completed at: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat("=", 70) . "\n";
}

/**
 * Sample Output:
 *
 * 🚀 MVPAgentOrchestrator Performance Benchmark
 * ======================================================================
 * Iterations: 10
 * Start Time: 2025-11-04 14:30:22
 * ======================================================================
 *
 * Iteration 1/10 ✓ (V1: 1.52ms, V2: 4.73ms)
 * Iteration 2/10 ✓ (V1: 1.48ms, V2: 4.68ms)
 * Iteration 3/10 ✓ (V1: 1.51ms, V2: 4.71ms)
 * Iteration 4/10 ✓ (V1: 1.49ms, V2: 4.69ms)
 * Iteration 5/10 ✓ (V1: 1.50ms, V2: 4.70ms)
 * Iteration 6/10 ✓ (V1: 1.52ms, V2: 4.72ms)
 * Iteration 7/10 ✓ (V1: 1.51ms, V2: 4.71ms)
 * Iteration 8/10 ✓ (V1: 1.49ms, V2: 4.69ms)
 * Iteration 9/10 ✓ (V1: 1.50ms, V2: 4.70ms)
 * Iteration 10/10 ✓ (V1: 1.51ms, V2: 4.71ms)
 *
 * ======================================================================
 * 📊 Benchmark Results
 * ======================================================================
 *
 * V1 Performance:
 *   Mean:   1.50 ms
 *   Median: 1.51 ms
 *   StdDev: 0.01 ms
 *   Range:  1.48 - 1.52 ms
 *
 * V2 Performance:
 *   Mean:   4.70 ms
 *   Median: 4.71 ms
 *   StdDev: 0.02 ms
 *   Range:  4.68 - 4.73 ms
 *
 * Performance Comparison:
 *   Overhead: 213.3%
 *   Ratio:    3.13x
 *
 *   Status: ❌ CRITICAL (Target: ≤50%)
 *
 * ======================================================================
 * Benchmark completed at: 2025-11-04 14:30:45
 * ======================================================================
 */
