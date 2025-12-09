<?php
// File: mvp_system/tests/e2e/calm_break_scenario.test.php (Line 1)
// Mathking Agentic MVP System - End-to-End Calm Break Scenario Tests
//
// Purpose: Test complete Calm Break intervention flow from start to finish
// Run: php calm_break_scenario.test.php

// Add parent directories to path
require_once(__DIR__ . '/../../orchestrator.php');
require_once(__DIR__ . '/../../config/app.config.php');
require_once(__DIR__ . '/../../lib/database.php');
require_once(__DIR__ . '/../../lib/logger.php');

class CalmBreakE2ETest
{
    /**
     * End-to-End Test Suite for Calm Break Scenario
     *
     * Tests complete intervention pipeline across all layers
     */

    private $orchestrator;
    private $db;
    private $logger;
    private $test_results = [];
    private $passed = 0;
    private $failed = 0;
    private $test_student_base_id = 10000; // Use high IDs to avoid conflicts

    public function __construct()
    {
        $this->orchestrator = new PipelineOrchestrator();
        $this->db = new MVPDatabase();
        $this->logger = new MVPLogger('e2e_test');
    }

    /**
     * Assert helper
     */
    private function assert($condition, $message)
    {
        if ($condition) {
            $this->passed++;
            echo "✅ PASS: $message\n";
            return true;
        } else {
            $this->failed++;
            echo "❌ FAIL: $message\n";
            return false;
        }
    }

    /**
     * Clean up test data from database
     */
    private function cleanup()
    {
        echo "\n🧹 Cleaning up test data...\n";

        // Clean up test records (student IDs >= 10000)
        $tables = [
            'mvp_snapshot_metrics',
            'mvp_decision_log',
            'mvp_intervention_execution',
            'mvp_system_metrics'
        ];

        foreach ($tables as $table) {
            try {
                $this->db->query(
                    "DELETE FROM mdl_{$table} WHERE student_id >= ?",
                    [$this->test_student_base_id]
                );
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }

        echo "   Cleanup completed\n";
    }

    /**
     * E2E Test 01: Critical Calm - Immediate Break Required
     */
    public function test_01_critical_calm_scenario()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 01: Critical Calm Scenario (score < 60)\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 1;

        // Critical calm scenario: high interruptions, low focus
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 15,  // Very high
            'focus_time' => 200,    // Low
            'correct_answers' => 3,
            'total_attempts' => 10
        ];

        echo "\n📊 Student Profile:\n";
        echo "   Interruptions: {$activity_data['interruptions']} (critical level)\n";
        echo "   Focus time: {$activity_data['focus_time']}s / {$activity_data['session_duration']}s\n";

        // Execute pipeline
        echo "\n🚀 Executing pipeline...\n";
        $result = $this->orchestrator->execute($student_id, $activity_data);

        // Validate pipeline success
        $this->assert(
            $result['success'] === true,
            "Pipeline executed successfully"
        );

        // Validate calm score
        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $this->assert(
            $calm_score < 60,
            "Calm score is critical (<60): {$calm_score}"
        );

        // Validate decision
        $action = $result['steps']['decision']['data']['action'];
        $confidence = $result['steps']['decision']['data']['confidence'];
        $rule_id = $result['steps']['decision']['data']['rule_id'];

        $this->assert(
            $action === 'micro_break',
            "Action is 'micro_break' for critical calm"
        );

        $this->assert(
            $confidence >= 0.90,
            "High confidence (>= 0.90) for critical scenario: {$confidence}"
        );

        $this->assert(
            $rule_id === 'calm_break_critical',
            "Matched correct rule: {$rule_id}"
        );

        // Validate intervention execution
        if ($action !== 'none') {
            $intervention = $result['steps']['execution']['data'];
            $this->assert(
                isset($intervention['intervention_id']),
                "Intervention dispatched with ID"
            );

            $this->assert(
                $intervention['status'] === 'sent',
                "Intervention status is 'sent'"
            );
        }

        // Validate SLA compliance
        $this->assert(
            $result['performance']['sla_met'] === true,
            "Pipeline completed within SLA"
        );

        // Validate database persistence
        $this->validateDatabaseRecords($student_id, $result);

        echo "\n📈 Performance:\n";
        echo "   Total time: {$result['performance']['total_ms']} ms\n";
        echo "   SLA met: " . ($result['performance']['sla_met'] ? 'Yes' : 'No') . "\n";
    }

    /**
     * E2E Test 02: Low Calm - Short Break Recommended
     */
    public function test_02_low_calm_scenario()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 02: Low Calm Scenario (60-74)\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 2;

        // Low calm scenario: moderate interruptions, below average focus
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 8,
            'focus_time' => 350,
            'correct_answers' => 6,
            'total_attempts' => 10
        ];

        echo "\n📊 Student Profile:\n";
        echo "   Interruptions: {$activity_data['interruptions']} (moderate)\n";
        echo "   Focus time: {$activity_data['focus_time']}s / {$activity_data['session_duration']}s\n";

        $result = $this->orchestrator->execute($student_id, $activity_data);

        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $action = $result['steps']['decision']['data']['action'];
        $rule_id = $result['steps']['decision']['data']['rule_id'];

        $this->assert(
            $calm_score >= 60 && $calm_score < 75,
            "Calm score in low range (60-74): {$calm_score}"
        );

        $this->assert(
            $action === 'micro_break',
            "Action is 'micro_break' for low calm"
        );

        $this->assert(
            $rule_id === 'calm_break_low',
            "Matched 'calm_break_low' rule"
        );

        // Check params contain duration
        $params = json_decode($result['steps']['decision']['data']['params'], true);
        $this->assert(
            isset($params['duration_minutes']) && $params['duration_minutes'] === 3,
            "Break duration is 3 minutes for low calm"
        );

        $this->validateDatabaseRecords($student_id, $result);

        echo "\n📈 Performance: {$result['performance']['total_ms']} ms\n";
    }

    /**
     * E2E Test 03: Moderate Calm - Continue Monitoring
     */
    public function test_03_moderate_calm_scenario()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 03: Moderate Calm Scenario (75-89)\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 3;

        // Moderate calm scenario: few interruptions, good focus
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 3,
            'focus_time' => 480,
            'correct_answers' => 8,
            'total_attempts' => 10
        ];

        echo "\n📊 Student Profile:\n";
        echo "   Interruptions: {$activity_data['interruptions']} (low)\n";
        echo "   Focus time: {$activity_data['focus_time']}s / {$activity_data['session_duration']}s\n";

        $result = $this->orchestrator->execute($student_id, $activity_data);

        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $action = $result['steps']['decision']['data']['action'];

        $this->assert(
            $calm_score >= 75 && $calm_score < 90,
            "Calm score in moderate range (75-89): {$calm_score}"
        );

        $this->assert(
            $action === 'none',
            "No intervention needed for moderate calm"
        );

        // Verify execution handled 'none' action
        $this->assert(
            $result['steps']['execution']['success'] === true,
            "Execution layer handled 'none' action gracefully"
        );

        $this->validateDatabaseRecords($student_id, $result);

        echo "\n📈 Performance: {$result['performance']['total_ms']} ms\n";
    }

    /**
     * E2E Test 04: High Calm - Optimal State
     */
    public function test_04_high_calm_scenario()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 04: High Calm Scenario (>= 90)\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 4;

        // High calm scenario: minimal interruptions, excellent focus
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 1,
            'focus_time' => 570,
            'correct_answers' => 10,
            'total_attempts' => 10
        ];

        echo "\n📊 Student Profile:\n";
        echo "   Interruptions: {$activity_data['interruptions']} (minimal)\n";
        echo "   Focus time: {$activity_data['focus_time']}s / {$activity_data['session_duration']}s\n";

        $result = $this->orchestrator->execute($student_id, $activity_data);

        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $action = $result['steps']['decision']['data']['action'];
        $rule_id = $result['steps']['decision']['data']['rule_id'];

        $this->assert(
            $calm_score >= 90,
            "Calm score is high (>= 90): {$calm_score}"
        );

        $this->assert(
            $action === 'none',
            "No intervention for optimal state"
        );

        $this->assert(
            $rule_id === 'calm_optimal',
            "Matched 'calm_optimal' rule"
        );

        // Check params suggest challenge
        $params = json_decode($result['steps']['decision']['data']['params'], true);
        $this->assert(
            isset($params['suggest_challenge']) && $params['suggest_challenge'] === true,
            "Suggests challenge for optimal state"
        );

        $this->validateDatabaseRecords($student_id, $result);

        echo "\n📈 Performance: {$result['performance']['total_ms']} ms\n";
    }

    /**
     * E2E Test 05: Multiple Sequential Executions
     */
    public function test_05_sequential_executions()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 05: Multiple Sequential Executions\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 5;
        $execution_count = 5;

        echo "\n🔄 Executing {$execution_count} sequential pipelines...\n";

        $results = [];
        $all_unique_ids = true;

        for ($i = 0; $i < $execution_count; $i++) {
            $result = $this->orchestrator->execute($student_id);
            $results[] = $result;

            $this->assert(
                $result['success'] === true,
                "Execution " . ($i + 1) . " succeeded"
            );
        }

        // Verify all pipeline IDs are unique
        $pipeline_ids = array_map(function($r) { return $r['pipeline_id']; }, $results);
        $unique_ids = array_unique($pipeline_ids);

        $this->assert(
            count($unique_ids) === $execution_count,
            "All {$execution_count} pipeline IDs are unique"
        );

        // Check database has all records
        $metrics_count = $this->db->query(
            "SELECT COUNT(*) as count FROM mdl_mvp_snapshot_metrics WHERE student_id = ?",
            [$student_id]
        )[0]['count'];

        $this->assert(
            $metrics_count >= $execution_count,
            "Database contains all {$execution_count} metric records"
        );

        echo "\n📊 Results:\n";
        echo "   Total executions: {$execution_count}\n";
        echo "   Unique pipeline IDs: " . count($unique_ids) . "\n";
        echo "   Database records: {$metrics_count}\n";
    }

    /**
     * E2E Test 06: Schema Compliance Validation
     */
    public function test_06_schema_compliance()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 06: Schema Compliance Validation\n";
        echo str_repeat("=", 70) . "\n";

        $student_id = $this->test_student_base_id + 6;
        $result = $this->orchestrator->execute($student_id);

        echo "\n🔍 Validating schema compliance...\n";

        // Validate metrics schema
        $metrics = $result['steps']['sensing']['data'];
        $required_metrics_fields = ['student_id', 'calm_score', 'timestamp'];

        foreach ($required_metrics_fields as $field) {
            $this->assert(
                isset($metrics[$field]),
                "Metrics contains required field: {$field}"
            );
        }

        // Validate decision schema
        $decision = $result['steps']['decision']['data'];
        $required_decision_fields = ['student_id', 'action', 'confidence', 'rationale'];

        foreach ($required_decision_fields as $field) {
            $this->assert(
                isset($decision[$field]),
                "Decision contains required field: {$field}"
            );
        }

        // Validate data types
        $this->assert(
            is_numeric($metrics['calm_score']),
            "calm_score is numeric"
        );

        $this->assert(
            $decision['confidence'] >= 0 && $decision['confidence'] <= 1,
            "Confidence is in range 0-1"
        );

        $this->assert(
            in_array($decision['action'], ['micro_break', 'ask_teacher', 'none']),
            "Action is valid enum value"
        );

        echo "\n✓ All schema validations passed\n";
    }

    /**
     * E2E Test 07: SLA Compliance Across Scenarios
     */
    public function test_07_sla_compliance()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "E2E Test 07: SLA Compliance Across Scenarios\n";
        echo str_repeat("=", 70) . "\n";

        $scenarios = [
            ['name' => 'Critical', 'interruptions' => 15],
            ['name' => 'Low', 'interruptions' => 8],
            ['name' => 'Moderate', 'interruptions' => 3],
            ['name' => 'High', 'interruptions' => 1]
        ];

        echo "\n⏱️ Testing SLA compliance across all scenarios...\n";

        $total_tests = count($scenarios);
        $sla_met_count = 0;
        $times = [];

        foreach ($scenarios as $index => $scenario) {
            $student_id = $this->test_student_base_id + 100 + $index;
            $activity_data = [
                'session_duration' => 600,
                'interruptions' => $scenario['interruptions'],
                'focus_time' => 600 - ($scenario['interruptions'] * 30),
                'correct_answers' => 10 - intval($scenario['interruptions'] / 2),
                'total_attempts' => 10
            ];

            $result = $this->orchestrator->execute($student_id, $activity_data);

            if ($result['performance']['sla_met']) {
                $sla_met_count++;
            }

            $times[] = $result['performance']['total_ms'];

            echo "   {$scenario['name']}: {$result['performance']['total_ms']} ms - ";
            echo ($result['performance']['sla_met'] ? '✅ SLA Met' : '❌ SLA Exceeded') . "\n";
        }

        $sla_compliance_percent = ($sla_met_count / $total_tests) * 100;
        $avg_time = array_sum($times) / count($times);

        $this->assert(
            $sla_compliance_percent >= 90,
            "SLA compliance >= 90%: {$sla_compliance_percent}%"
        );

        $this->assert(
            $avg_time < 3000,
            "Average execution time < 3 seconds: {$avg_time} ms"
        );

        echo "\n📊 SLA Statistics:\n";
        echo "   Tests run: {$total_tests}\n";
        echo "   SLA met: {$sla_met_count}\n";
        echo "   Compliance: {$sla_compliance_percent}%\n";
        echo "   Avg time: " . round($avg_time, 2) . " ms\n";
        echo "   Min time: " . min($times) . " ms\n";
        echo "   Max time: " . max($times) . " ms\n";
    }

    /**
     * Validate database records for a pipeline execution
     */
    private function validateDatabaseRecords($student_id, $result)
    {
        echo "\n💾 Validating database persistence...\n";

        // Check snapshot_metrics
        $metrics = $this->db->query(
            "SELECT * FROM mdl_mvp_snapshot_metrics WHERE student_id = ? ORDER BY id DESC LIMIT 1",
            [$student_id]
        );

        $this->assert(
            !empty($metrics),
            "Metrics record saved to database"
        );

        // Check decision_log
        $decisions = $this->db->query(
            "SELECT * FROM mdl_mvp_decision_log WHERE student_id = ? ORDER BY id DESC LIMIT 1",
            [$student_id]
        );

        $this->assert(
            !empty($decisions),
            "Decision record saved to database"
        );

        // Check intervention_execution (if action was not 'none')
        if ($result['steps']['decision']['data']['action'] !== 'none') {
            $interventions = $this->db->query(
                "SELECT * FROM mdl_mvp_intervention_execution WHERE target_student_id = ? ORDER BY id DESC LIMIT 1",
                [$student_id]
            );

            $this->assert(
                !empty($interventions),
                "Intervention record saved to database"
            );
        }

        // Check system_metrics
        $system_metrics = $this->db->query(
            "SELECT COUNT(*) as count FROM mdl_mvp_system_metrics
             WHERE metric_name LIKE 'pipeline_%'
             AND JSON_EXTRACT(context, '$.student_id') = ?
             ORDER BY id DESC",
            [$student_id]
        );

        $this->assert(
            $system_metrics[0]['count'] > 0,
            "Performance metrics saved to database"
        );

        echo "   ✓ All database records verified\n";
    }

    /**
     * Run all E2E tests
     */
    public function runTests()
    {
        echo "\n";
        echo str_repeat("═", 70) . "\n";
        echo "   MATHKING AGENTIC MVP SYSTEM\n";
        echo "   Calm Break Scenario - End-to-End Tests\n";
        echo str_repeat("═", 70) . "\n";

        $start_time = microtime(true);

        // Clean up before tests
        $this->cleanup();

        // Run all test methods
        $methods = get_class_methods($this);
        $test_methods = array_filter($methods, function($m) {
            return strpos($m, 'test_') === 0;
        });

        foreach ($test_methods as $method) {
            try {
                $this->$method();
            } catch (Exception $e) {
                $this->failed++;
                echo "❌ EXCEPTION in {$method}: " . $e->getMessage() . "\n";
            }
        }

        $total_time = round((microtime(true) - $start_time) * 1000, 2);

        // Clean up after tests
        $this->cleanup();

        // Summary
        echo "\n" . str_repeat("═", 70) . "\n";
        echo "   TEST SUMMARY\n";
        echo str_repeat("═", 70) . "\n";
        echo "Total assertions: " . ($this->passed + $this->failed) . "\n";
        echo "✅ Passed: " . $this->passed . "\n";
        echo "❌ Failed: " . $this->failed . "\n";
        echo "Total time: {$total_time} ms\n";
        echo str_repeat("═", 70) . "\n";

        if ($this->failed === 0) {
            echo "🎉 ALL E2E TESTS PASSED!\n";
            echo "The Calm Break scenario is fully operational.\n";
        } else {
            echo "⚠️  SOME TESTS FAILED\n";
            echo "Please review the failures above.\n";
        }

        echo str_repeat("═", 70) . "\n\n";

        return $this->failed === 0 ? 0 : 1;
    }
}


// =============================================================================
// Run Tests
// =============================================================================

if (php_sapi_name() === 'cli') {
    $test = new CalmBreakE2ETest();
    $exit_code = $test->runTests();
    exit($exit_code);
}


// =============================================================================
// Test Execution
// =============================================================================
//
// Direct execution:
//    php calm_break_scenario.test.php
//
// Expected output:
//    All 7 E2E test scenarios with detailed validation
//    Database persistence verification
//    Schema compliance checks
//    SLA compliance statistics
//
// =============================================================================
