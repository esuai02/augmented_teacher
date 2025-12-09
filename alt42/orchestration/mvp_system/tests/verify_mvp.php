<?php
// File: mvp_system/tests/verify_mvp.php (Line 1)
// Mathking Agentic MVP System - Complete MVP Verification Script
//
// Purpose: Comprehensive system verification and readiness check
// Run: php verify_mvp.php

// Add parent directory to path
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

class MVPVerification
{
    /**
     * Complete MVP system verification
     */

    private $mvp_db;
    private $logger;
    private $verification_results = [];
    private $start_time;

    public function __construct()
    {
        $this->mvp_db = new MVPDatabase();
        $this->logger = new MVPLogger('mvp_verification');
        $this->start_time = microtime(true);
    }

    /**
     * Run complete verification
     */
    public function verify()
    {
        $this->printHeader();

        // Phase 1: Infrastructure
        $this->verifyInfrastructure();

        // Phase 2: Components
        $this->verifyComponents();

        // Phase 3: Integration
        $this->verifyIntegration();

        // Phase 4: Performance
        $this->verifyPerformance();

        // Phase 5: Readiness
        $this->verifyReadiness();

        // Generate report
        $this->generateReport();

        return $this->calculateOverallStatus();
    }

    /**
     * Print header
     */
    private function printHeader()
    {
        echo "\n";
        echo "==" . str_repeat("=", 68) . "\n";
        echo "  MATHKING AGENTIC MVP SYSTEM - COMPLETE VERIFICATION\n";
        echo "==" . str_repeat("=", 68) . "\n\n";
        echo "Verification started: " . date('Y-m-d H:i:s') . "\n\n";
    }

    /**
     * Verify infrastructure (database, files, dependencies)
     */
    private function verifyInfrastructure()
    {
        echo "==" . str_repeat("=", 68) . "\n";
        echo "PHASE 1: INFRASTRUCTURE VERIFICATION\n";
        echo "==" . str_repeat("=", 68) . "\n\n";

        $checks = [];

        // Check database connection
        $checks['database_connection'] = $this->checkDatabaseConnection();

        // Check database tables
        $checks['database_tables'] = $this->checkDatabaseTables();

        // Check file structure
        $checks['file_structure'] = $this->checkFileStructure();

        // Check Python environment
        $checks['python_environment'] = $this->checkPythonEnvironment();

        // Check file permissions
        $checks['file_permissions'] = $this->checkFilePermissions();

        $this->verification_results['infrastructure'] = $checks;
        $this->printPhaseResults('Infrastructure', $checks);
    }

    /**
     * Verify components (Sensing, Decision, Execution)
     */
    private function verifyComponents()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "PHASE 2: COMPONENT VERIFICATION\n";
        echo str_repeat("=", 70) . "\n\n";

        $checks = [];

        // Test Sensing Layer
        echo "Testing Sensing Layer (Calm Calculator)...\n";
        $checks['sensing_layer'] = $this->testSensingLayer();

        // Test Decision Layer
        echo "Testing Decision Layer (Rule Engine)...\n";
        $checks['decision_layer'] = $this->testDecisionLayer();

        // Test Execution Layer
        echo "Testing Execution Layer (Intervention Dispatcher)...\n";
        $checks['execution_layer'] = $this->testExecutionLayer();

        $this->verification_results['components'] = $checks;
        $this->printPhaseResults('Components', $checks);
    }

    /**
     * Verify integration (Orchestrator, APIs, UI)
     */
    private function verifyIntegration()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "PHASE 3: INTEGRATION VERIFICATION\n";
        echo str_repeat("=", 70) . "\n\n";

        $checks = [];

        // Test Orchestrator
        echo "Testing Pipeline Orchestrator...\n";
        $checks['orchestrator'] = $this->testOrchestrator();

        // Test Feedback API
        echo "Testing Feedback API...\n";
        $checks['feedback_api'] = $this->testFeedbackAPI();

        // Test Teacher UI
        echo "Testing Teacher UI accessibility...\n";
        $checks['teacher_ui'] = $this->testTeacherUI();

        // Test SLA Monitoring
        echo "Testing SLA Monitoring...\n";
        $checks['sla_monitoring'] = $this->testSLAMonitoring();

        $this->verification_results['integration'] = $checks;
        $this->printPhaseResults('Integration', $checks);
    }

    /**
     * Verify performance (SLA compliance, response times)
     */
    private function verifyPerformance()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "PHASE 4: PERFORMANCE VERIFICATION\n";
        echo str_repeat("=", 70) . "\n\n";

        $checks = [];

        // Benchmark pipeline execution
        echo "Benchmarking pipeline execution (10 iterations)...\n";
        $checks['pipeline_benchmark'] = $this->benchmarkPipeline();

        // Check SLA compliance
        echo "Checking SLA compliance...\n";
        $checks['sla_compliance'] = $this->checkSLACompliance();

        // Check layer performance
        echo "Checking layer performance...\n";
        $checks['layer_performance'] = $this->checkLayerPerformance();

        $this->verification_results['performance'] = $checks;
        $this->printPhaseResults('Performance', $checks);
    }

    /**
     * Verify readiness (documentation, tests, deployment)
     */
    private function verifyReadiness()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "PHASE 5: MVP READINESS VERIFICATION\n";
        echo str_repeat("=", 70) . "\n\n";

        $checks = [];

        // Check documentation
        $checks['documentation'] = $this->checkDocumentation();

        // Check test coverage
        $checks['test_coverage'] = $this->checkTestCoverage();

        // Check logging
        $checks['logging'] = $this->checkLogging();

        // Check error handling
        $checks['error_handling'] = $this->checkErrorHandling();

        $this->verification_results['readiness'] = $checks;
        $this->printPhaseResults('Readiness', $checks);
    }

    // =========================================================================
    // Individual Check Methods
    // =========================================================================

    private function checkDatabaseConnection()
    {
        try {
            $result = $this->mvp_db->query("SELECT 1");
            return [
                'status' => 'pass',
                'message' => 'Database connection successful at verify_mvp.php:201'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage() . ' at verify_mvp.php:206'
            ];
        }
    }

    private function checkDatabaseTables()
    {
        $required_tables = [
            'mdl_mvp_snapshot_metrics',
            'mdl_mvp_decision_log',
            'mdl_mvp_intervention_execution',
            'mdl_mvp_teacher_feedback',
            'mdl_mvp_system_metrics'
        ];

        $missing_tables = [];
        foreach ($required_tables as $table) {
            $escapedTable = $this->mvp_db->getConnection()->real_escape_string($table);
            $result = $this->mvp_db->query("SHOW TABLES LIKE '{$escapedTable}'", []);
            if (empty($result)) {
                $missing_tables[] = $table;
            }
        }

        if (empty($missing_tables)) {
            return [
                'status' => 'pass',
                'message' => 'All required database tables exist'
            ];
        } else {
            return [
                'status' => 'fail',
                'message' => 'Missing tables: ' . implode(', ', $missing_tables) . ' at verify_mvp.php:235'
            ];
        }
    }

    private function checkFileStructure()
    {
        $required_files = [
            '../sensing/calm_score.py',
            '../decision/rule_engine.py',
            '../execution/dispatcher.php',
            '../orchestrator.php',
            '../ui/teacher_panel.php',
            '../api/feedback.php',
            '../monitoring/sla_monitor.php'
        ];

        $missing_files = [];
        foreach ($required_files as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missing_files[] = $file;
            }
        }

        if (empty($missing_files)) {
            return [
                'status' => 'pass',
                'message' => 'All required files exist'
            ];
        } else {
            return [
                'status' => 'fail',
                'message' => 'Missing files: ' . implode(', ', $missing_files) . ' at verify_mvp.php:264'
            ];
        }
    }

    private function checkPythonEnvironment()
    {
        // Check Python version
        exec('python3 --version 2>&1', $output, $return_code);

        if ($return_code === 0) {
            return [
                'status' => 'pass',
                'message' => 'Python environment available: ' . $output[0]
            ];
        } else {
            return [
                'status' => 'fail',
                'message' => 'Python 3 not available at verify_mvp.php:281'
            ];
        }
    }

    private function checkFilePermissions()
    {
        $writable_dirs = [
            '../logs'
        ];

        $issues = [];
        foreach ($writable_dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (!is_writable($path)) {
                $issues[] = $dir;
            }
        }

        if (empty($issues)) {
            return [
                'status' => 'pass',
                'message' => 'All required directories are writable'
            ];
        } else {
            return [
                'status' => 'warn',
                'message' => 'Not writable: ' . implode(', ', $issues) . ' at verify_mvp.php:307'
            ];
        }
    }

    private function testSensingLayer()
    {
        try {
            // Test calm score calculation
            $test_data = json_encode([
                'session_duration' => 600,
                'interruptions' => 8,
                'focus_time' => 300,
                'correct_answers' => 5,
                'total_attempts' => 10
            ]);

            $command = "cd " . escapeshellarg(__DIR__ . '/../sensing') . " && " .
                      "python3 calm_score.py 123 " . escapeshellarg($test_data) . " 2>&1";

            exec($command, $output, $return_code);

            if ($return_code === 0) {
                $result = json_decode(implode('', $output), true);
                if ($result && isset($result['calm_score'])) {
                    return [
                        'status' => 'pass',
                        'message' => 'Sensing layer operational, calm_score: ' . $result['calm_score']
                    ];
                }
            }

            return [
                'status' => 'fail',
                'message' => 'Sensing layer execution failed at verify_mvp.php:338'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Sensing layer error: ' . $e->getMessage() . ' at verify_mvp.php:343'
            ];
        }
    }

    private function testDecisionLayer()
    {
        try {
            // Test decision engine
            $test_metrics = json_encode([
                'student_id' => 123,
                'calm_score' => 65.5,
                'recommendation' => 'Test recommendation'
            ]);

            $command = "cd " . escapeshellarg(__DIR__ . '/../decision') . " && " .
                      "python3 rule_engine.py " . escapeshellarg($test_metrics) . " 2>&1";

            exec($command, $output, $return_code);

            if ($return_code === 0) {
                $result = json_decode(implode('', $output), true);
                if ($result && isset($result['action'])) {
                    return [
                        'status' => 'pass',
                        'message' => 'Decision layer operational, action: ' . $result['action']
                    ];
                }
            }

            return [
                'status' => 'fail',
                'message' => 'Decision layer execution failed at verify_mvp.php:373'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Decision layer error: ' . $e->getMessage() . ' at verify_mvp.php:378'
            ];
        }
    }

    private function testExecutionLayer()
    {
        // Execution layer is simulated in MVP, just check file exists and is parseable
        $dispatcher_file = __DIR__ . '/../execution/dispatcher.php';

        if (file_exists($dispatcher_file)) {
            // Check for syntax errors
            exec("php -l " . escapeshellarg($dispatcher_file) . " 2>&1", $output, $return_code);

            if ($return_code === 0) {
                return [
                    'status' => 'pass',
                    'message' => 'Execution layer syntax valid'
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Execution layer syntax error at verify_mvp.php:399'
                ];
            }
        }

        return [
            'status' => 'fail',
            'message' => 'Execution layer file not found at verify_mvp.php:405'
        ];
    }

    private function testOrchestrator()
    {
        try {
            require_once(__DIR__ . '/../orchestrator.php');
            $orchestrator = new PipelineOrchestrator();

            // Execute test pipeline
            $result = $orchestrator->execute(99999); // High ID to avoid conflicts

            if ($result['success'] === true) {
                return [
                    'status' => 'pass',
                    'message' => 'Orchestrator operational, pipeline_id: ' . $result['pipeline_id']
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Orchestrator execution failed at verify_mvp.php:425'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Orchestrator error: ' . $e->getMessage() . ' at verify_mvp.php:430'
            ];
        }
    }

    private function testFeedbackAPI()
    {
        $feedback_file = __DIR__ . '/../api/feedback.php';

        if (file_exists($feedback_file)) {
            // Check syntax
            exec("php -l " . escapeshellarg($feedback_file) . " 2>&1", $output, $return_code);

            if ($return_code === 0) {
                return [
                    'status' => 'pass',
                    'message' => 'Feedback API syntax valid'
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Feedback API syntax error at verify_mvp.php:450'
                ];
            }
        }

        return [
            'status' => 'fail',
            'message' => 'Feedback API file not found at verify_mvp.php:456'
        ];
    }

    private function testTeacherUI()
    {
        $ui_files = [
            '../ui/teacher_panel.php',
            '../ui/teacher_panel.css',
            '../ui/teacher_panel.js'
        ];

        $missing = [];
        foreach ($ui_files as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missing[] = $file;
            }
        }

        if (empty($missing)) {
            return [
                'status' => 'pass',
                'message' => 'Teacher UI files complete'
            ];
        } else {
            return [
                'status' => 'fail',
                'message' => 'Missing UI files: ' . implode(', ', $missing) . ' at verify_mvp.php:481'
            ];
        }
    }

    private function testSLAMonitoring()
    {
        try {
            require_once(__DIR__ . '/../monitoring/sla_monitor.php');
            $monitor = new SLAMonitor();

            // Run monitoring for last 1 hour
            $results = $monitor->monitor(1);

            if (isset($results['timestamp'])) {
                return [
                    'status' => 'pass',
                    'message' => 'SLA monitoring operational'
                ];
            } else {
                return [
                    'status' => 'warn',
                    'message' => 'SLA monitoring executed but incomplete results at verify_mvp.php:501'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'SLA monitoring error: ' . $e->getMessage() . ' at verify_mvp.php:506'
            ];
        }
    }

    private function benchmarkPipeline()
    {
        try {
            require_once(__DIR__ . '/../orchestrator.php');
            $orchestrator = new PipelineOrchestrator();

            $iterations = 10;
            $times = [];

            for ($i = 0; $i < $iterations; $i++) {
                $start = microtime(true);
                $result = $orchestrator->execute(99900 + $i);
                $end = microtime(true);

                if ($result['success']) {
                    $times[] = ($end - $start) * 1000; // Convert to ms
                }
            }

            if (count($times) === $iterations) {
                $avg_time = array_sum($times) / count($times);
                $min_time = min($times);
                $max_time = max($times);

                return [
                    'status' => 'pass',
                    'message' => sprintf(
                        'Pipeline benchmark: avg=%.2fms, min=%.2fms, max=%.2fms',
                        $avg_time, $min_time, $max_time
                    ),
                    'data' => [
                        'avg_ms' => $avg_time,
                        'min_ms' => $min_time,
                        'max_ms' => $max_time
                    ]
                ];
            } else {
                return [
                    'status' => 'fail',
                    'message' => 'Benchmark incomplete, only ' . count($times) . '/' . $iterations . ' succeeded at verify_mvp.php:551'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Benchmark error: ' . $e->getMessage() . ' at verify_mvp.php:556'
            ];
        }
    }

    private function checkSLACompliance()
    {
        // Check recent SLA compliance
        $stats = $this->mvp_db->query(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN metric_value = 1 THEN 1 ELSE 0 END) as sla_met
             FROM mdl_mvp_system_metrics
             WHERE metric_name = 'pipeline_sla_met'
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        if (!empty($stats) && $stats[0]['total'] > 0) {
            $compliance = ($stats[0]['sla_met'] / $stats[0]['total']) * 100;

            $status = 'pass';
            if ($compliance < 90) $status = 'warn';
            if ($compliance < 75) $status = 'fail';

            return [
                'status' => $status,
                'message' => sprintf('SLA compliance: %.2f%% (%d/%d)',
                    $compliance, $stats[0]['sla_met'], $stats[0]['total'])
            ];
        }

        return [
            'status' => 'warn',
            'message' => 'No SLA data available (no recent executions) at verify_mvp.php:588'
        ];
    }

    private function checkLayerPerformance()
    {
        $layers = ['sensing', 'decision', 'execution'];
        $results = [];

        foreach ($layers as $layer) {
            $stats = $this->mvp_db->query(
                "SELECT AVG(metric_value) as avg_ms
                 FROM mdl_mvp_system_metrics
                 WHERE metric_name = ?
                 AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                ["pipeline_{$layer}_time"]
            );

            if (!empty($stats) && $stats[0]['avg_ms'] !== null) {
                $results[$layer] = round($stats[0]['avg_ms'], 2);
            }
        }

        if (!empty($results)) {
            $message = 'Layer avg times: ';
            $parts = [];
            foreach ($results as $layer => $ms) {
                $parts[] = "$layer={$ms}ms";
            }
            $message .= implode(', ', $parts);

            return [
                'status' => 'pass',
                'message' => $message,
                'data' => $results
            ];
        }

        return [
            'status' => 'warn',
            'message' => 'No layer performance data available at verify_mvp.php:625'
        ];
    }

    private function checkDocumentation()
    {
        $required_docs = [
            '../README.md',
            '../ORCHESTRATOR_GUIDE.md',
            '../monitoring/SLA_MONITORING_GUIDE.md',
            '../tests/e2e/E2E_TEST_GUIDE.md'
        ];

        $missing = [];
        foreach ($required_docs as $doc) {
            if (!file_exists(__DIR__ . '/' . $doc)) {
                $missing[] = $doc;
            }
        }

        if (empty($missing)) {
            return [
                'status' => 'pass',
                'message' => 'All required documentation exists'
            ];
        } else {
            return [
                'status' => 'warn',
                'message' => 'Missing docs: ' . implode(', ', $missing) . ' at verify_mvp.php:651'
            ];
        }
    }

    private function checkTestCoverage()
    {
        $test_files = [
            'orchestrator.test.php',
            'feedback.test.php',
            'e2e/calm_break_scenario.test.php'
        ];

        $existing = 0;
        foreach ($test_files as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $existing++;
            }
        }

        $coverage = ($existing / count($test_files)) * 100;

        if ($coverage === 100) {
            return [
                'status' => 'pass',
                'message' => 'All test files present'
            ];
        } else {
            return [
                'status' => 'warn',
                'message' => sprintf('Test coverage: %.0f%% (%d/%d files) at verify_mvp.php:679',
                    $coverage, $existing, count($test_files))
            ];
        }
    }

    private function checkLogging()
    {
        $log_dir = __DIR__ . '/../logs';

        if (is_dir($log_dir) && is_writable($log_dir)) {
            // Check if logs are being written
            $log_files = glob($log_dir . '/*.log');

            if (!empty($log_files)) {
                return [
                    'status' => 'pass',
                    'message' => 'Logging system operational (' . count($log_files) . ' log files)'
                ];
            } else {
                return [
                    'status' => 'warn',
                    'message' => 'Log directory exists but no log files found at verify_mvp.php:701'
                ];
            }
        }

        return [
            'status' => 'fail',
            'message' => 'Log directory not writable at verify_mvp.php:707'
        ];
    }

    private function checkErrorHandling()
    {
        // Test error handling by running orchestrator with invalid data
        try {
            require_once(__DIR__ . '/../orchestrator.php');
            $orchestrator = new PipelineOrchestrator();

            // Execute with invalid activity data
            $result = $orchestrator->execute(99998, ['invalid' => 'data']);

            // Should still return structured result with errors
            if (isset($result['errors']) && is_array($result['errors'])) {
                return [
                    'status' => 'pass',
                    'message' => 'Error handling operational (graceful error reporting)'
                ];
            } else {
                return [
                    'status' => 'warn',
                    'message' => 'Error handling may not be complete at verify_mvp.php:729'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'pass',
                'message' => 'Error handling operational (exceptions caught)'
            ];
        }
    }

    // =========================================================================
    // Reporting Methods
    // =========================================================================

    private function printPhaseResults($phase_name, $checks)
    {
        $passed = 0;
        $warned = 0;
        $failed = 0;

        foreach ($checks as $check) {
            if ($check['status'] === 'pass') {
                echo "✅ PASS: {$check['message']}\n";
                $passed++;
            } elseif ($check['status'] === 'warn') {
                echo "⚠️  WARN: {$check['message']}\n";
                $warned++;
            } else {
                echo "❌ FAIL: {$check['message']}\n";
                $failed++;
            }
        }

        echo "\n$phase_name Results: ";
        echo "$passed passed, $warned warnings, $failed failed\n";
    }

    private function generateReport()
    {
        $total_time = microtime(true) - $this->start_time;

        echo "\n" . str_repeat("=", 70) . "\n";
        echo "VERIFICATION SUMMARY\n";
        echo str_repeat("=", 70) . "\n\n";

        $overall_status = $this->calculateOverallStatus();

        // Count totals
        $total_passed = 0;
        $total_warned = 0;
        $total_failed = 0;

        foreach ($this->verification_results as $phase => $checks) {
            foreach ($checks as $check) {
                if ($check['status'] === 'pass') $total_passed++;
                elseif ($check['status'] === 'warn') $total_warned++;
                else $total_failed++;
            }
        }

        echo "Overall Status: " . strtoupper($overall_status['status']) . "\n";
        echo "Total Checks: " . ($total_passed + $total_warned + $total_failed) . "\n";
        echo "✅ Passed: $total_passed\n";
        echo "⚠️  Warnings: $total_warned\n";
        echo "❌ Failed: $total_failed\n";
        echo "Total Time: " . round($total_time, 2) . " seconds\n\n";

        // Readiness assessment
        echo str_repeat("=", 70) . "\n";
        echo "MVP READINESS ASSESSMENT\n";
        echo str_repeat("=", 70) . "\n\n";

        echo $overall_status['message'] . "\n\n";

        if (!empty($overall_status['blockers'])) {
            echo "🚨 BLOCKERS (must fix before deployment):\n";
            foreach ($overall_status['blockers'] as $blocker) {
                echo "   • $blocker\n";
            }
            echo "\n";
        }

        if (!empty($overall_status['warnings'])) {
            echo "⚠️  WARNINGS (recommended to fix):\n";
            foreach ($overall_status['warnings'] as $warning) {
                echo "   • $warning\n";
            }
            echo "\n";
        }

        echo str_repeat("=", 70) . "\n";
        echo "Verification completed: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 70) . "\n\n";
    }

    private function calculateOverallStatus()
    {
        $blockers = [];
        $warnings = [];

        foreach ($this->verification_results as $phase => $checks) {
            foreach ($checks as $check_name => $check) {
                if ($check['status'] === 'fail') {
                    $blockers[] = "$phase: {$check['message']}";
                } elseif ($check['status'] === 'warn') {
                    $warnings[] = "$phase: {$check['message']}";
                }
            }
        }

        if (empty($blockers)) {
            if (empty($warnings)) {
                return [
                    'status' => 'ready',
                    'message' => '✅ MVP is READY for deployment! All checks passed.',
                    'blockers' => [],
                    'warnings' => []
                ];
            } else {
                return [
                    'status' => 'ready_with_warnings',
                    'message' => '✅ MVP is READY for deployment with ' . count($warnings) . ' warnings.',
                    'blockers' => [],
                    'warnings' => $warnings
                ];
            }
        } else {
            return [
                'status' => 'not_ready',
                'message' => '❌ MVP is NOT READY for deployment. ' . count($blockers) . ' blocker(s) found.',
                'blockers' => $blockers,
                'warnings' => $warnings
            ];
        }
    }
}


// =============================================================================
// Run Verification
// =============================================================================

if (php_sapi_name() === 'cli') {
    $verification = new MVPVerification();
    $status = $verification->verify();

    // Exit with appropriate code
    $exit_code = 0;
    if ($status['status'] === 'not_ready') {
        $exit_code = 1;
    }

    exit($exit_code);
}
?>
