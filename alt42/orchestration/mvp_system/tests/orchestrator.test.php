<?php
// File: mvp_system/tests/orchestrator.test.php (Line 1)
// Mathking Agentic MVP System - Orchestrator Unit Tests
//
// Purpose: Test complete pipeline orchestration
// Run: php orchestrator.test.php

// Add parent directory to path
require_once(__DIR__ . '/../orchestrator.php');
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

class OrchestratorTest
{
    /**
     * Test suite for PipelineOrchestrator class
     */

    private $orchestrator;
    private $test_results = [];
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->orchestrator = new PipelineOrchestrator();
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
     * Test 01: Complete pipeline execution - low calm (intervention needed)
     */
    public function test_01_pipeline_low_calm()
    {
        echo "\nTest 01: Complete pipeline - low calm at " . __FILE__ . ":48\n";

        $student_id = 123;
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 8,
            'focus_time' => 300,
            'correct_answers' => 5,
            'total_attempts' => 10
        ];

        $result = $this->orchestrator->execute($student_id, $activity_data);

        // Basic assertions
        $this->assert(
            isset($result['pipeline_id']),
            "Pipeline ID generated"
        );

        $this->assert(
            $result['student_id'] === $student_id,
            "Student ID matches"
        );

        $this->assert(
            $result['success'] === true,
            "Pipeline execution succeeded"
        );

        // Check all three steps executed
        $this->assert(
            isset($result['steps']['sensing']) &&
            isset($result['steps']['decision']) &&
            isset($result['steps']['execution']),
            "All three pipeline steps present"
        );

        // Check Sensing step
        $this->assert(
            $result['steps']['sensing']['success'] === true,
            "Sensing layer succeeded"
        );

        $this->assert(
            isset($result['steps']['sensing']['data']['calm_score']),
            "Calm score calculated"
        );

        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $this->assert(
            $calm_score >= 0 && $calm_score <= 100,
            "Calm score in valid range (0-100)"
        );

        // Check Decision step
        $this->assert(
            $result['steps']['decision']['success'] === true,
            "Decision layer succeeded"
        );

        $action = $result['steps']['decision']['data']['action'];
        $this->assert(
            in_array($action, ['micro_break', 'ask_teacher', 'none']),
            "Valid action decided"
        );

        // Check Execution step
        $this->assert(
            $result['steps']['execution']['success'] === true,
            "Execution layer succeeded"
        );

        // Check performance tracking
        $this->assert(
            isset($result['performance']['total_ms']) &&
            $result['performance']['total_ms'] > 0,
            "Total execution time measured"
        );

        $this->assert(
            isset($result['performance']['sla_met']),
            "SLA compliance tracked"
        );

        echo "   Pipeline ID: " . $result['pipeline_id'] . "\n";
        echo "   Calm score: " . $calm_score . "\n";
        echo "   Action: " . $action . "\n";
        echo "   Total time: " . $result['performance']['total_ms'] . " ms\n";
        echo "   SLA met: " . ($result['performance']['sla_met'] ? 'Yes' : 'No') . "\n";
    }

    /**
     * Test 02: Pipeline execution - high calm (no intervention)
     */
    public function test_02_pipeline_high_calm()
    {
        echo "\nTest 02: Pipeline - high calm (no intervention) at " . __FILE__ . ":148\n";

        $student_id = 124;
        $activity_data = [
            'session_duration' => 600,
            'interruptions' => 1,
            'focus_time' => 550,
            'correct_answers' => 9,
            'total_attempts' => 10
        ];

        $result = $this->orchestrator->execute($student_id, $activity_data);

        $this->assert(
            $result['success'] === true,
            "Pipeline succeeded for high calm"
        );

        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $action = $result['steps']['decision']['data']['action'];

        $this->assert(
            $calm_score >= 75,
            "Calm score is high (>=75)"
        );

        $this->assert(
            $action === 'none',
            "No intervention action for high calm"
        );

        $this->assert(
            $result['steps']['execution']['success'] === true,
            "Execution handled 'none' action gracefully"
        );

        echo "   Calm score: " . $calm_score . "\n";
        echo "   Action: " . $action . "\n";
    }

    /**
     * Test 03: Pipeline execution with default activity data
     */
    public function test_03_pipeline_default_data()
    {
        echo "\nTest 03: Pipeline with default activity data at " . __FILE__ . ":190\n";

        $student_id = 125;

        // Execute without providing activity_data
        $result = $this->orchestrator->execute($student_id);

        $this->assert(
            $result['success'] === true,
            "Pipeline succeeded with default data"
        );

        $this->assert(
            isset($result['steps']['sensing']['data']['calm_score']),
            "Calm score calculated from defaults"
        );

        echo "   Used default activity data\n";
        echo "   Calm score: " . $result['steps']['sensing']['data']['calm_score'] . "\n";
    }

    /**
     * Test 04: Performance metrics recording
     */
    public function test_04_performance_metrics()
    {
        echo "\nTest 04: Performance metrics recording at " . __FILE__ . ":216\n";

        $student_id = 126;
        $result = $this->orchestrator->execute($student_id);

        // Check individual layer times
        $this->assert(
            isset($result['performance']['sensing_ms']) &&
            $result['performance']['sensing_ms'] > 0,
            "Sensing time recorded"
        );

        $this->assert(
            isset($result['performance']['decision_ms']) &&
            $result['performance']['decision_ms'] > 0,
            "Decision time recorded"
        );

        $this->assert(
            isset($result['performance']['execution_ms']) &&
            $result['performance']['execution_ms'] >= 0,
            "Execution time recorded"
        );

        // Check total time
        $total_ms = $result['performance']['total_ms'];
        $sum_layers = $result['performance']['sensing_ms'] +
                     $result['performance']['decision_ms'] +
                     $result['performance']['execution_ms'];

        $this->assert(
            $total_ms >= $sum_layers,
            "Total time includes all layers"
        );

        echo "   Sensing: " . $result['performance']['sensing_ms'] . " ms\n";
        echo "   Decision: " . $result['performance']['decision_ms'] . " ms\n";
        echo "   Execution: " . $result['performance']['execution_ms'] . " ms\n";
        echo "   Total: " . $total_ms . " ms\n";
    }

    /**
     * Test 05: SLA compliance tracking
     */
    public function test_05_sla_tracking()
    {
        echo "\nTest 05: SLA compliance tracking at " . __FILE__ . ":263\n";

        $student_id = 127;
        $result = $this->orchestrator->execute($student_id);

        $this->assert(
            isset($result['performance']['sla_limit_seconds']),
            "SLA limit recorded"
        );

        $this->assert(
            $result['performance']['sla_limit_seconds'] === 180,
            "SLA limit is 180 seconds (3 minutes)"
        );

        $this->assert(
            isset($result['performance']['sla_met']),
            "SLA met status recorded"
        );

        $this->assert(
            is_bool($result['performance']['sla_met']),
            "SLA met is boolean"
        );

        // For MVP with simulated LMS, should always meet SLA
        $this->assert(
            $result['performance']['sla_met'] === true,
            "Pipeline meets SLA (expected for MVP)"
        );

        echo "   SLA limit: " . $result['performance']['sla_limit_seconds'] . " seconds\n";
        echo "   Total time: " . $result['performance']['total_seconds'] . " seconds\n";
        echo "   SLA met: " . ($result['performance']['sla_met'] ? 'Yes' : 'No') . "\n";
    }

    /**
     * Test 06: Pipeline ID uniqueness
     */
    public function test_06_pipeline_id_uniqueness()
    {
        echo "\nTest 06: Pipeline ID uniqueness at " . __FILE__ . ":304\n";

        $student_id = 128;

        $result1 = $this->orchestrator->execute($student_id);
        $result2 = $this->orchestrator->execute($student_id);

        $this->assert(
            $result1['pipeline_id'] !== $result2['pipeline_id'],
            "Each pipeline execution has unique ID"
        );

        echo "   Pipeline ID 1: " . $result1['pipeline_id'] . "\n";
        echo "   Pipeline ID 2: " . $result2['pipeline_id'] . "\n";
    }

    /**
     * Test 07: Error array structure
     */
    public function test_07_error_structure()
    {
        echo "\nTest 07: Error array structure at " . __FILE__ . ":326\n";

        $student_id = 129;
        $result = $this->orchestrator->execute($student_id);

        $this->assert(
            isset($result['errors']) && is_array($result['errors']),
            "Errors array exists"
        );

        if ($result['success']) {
            $this->assert(
                count($result['errors']) === 0,
                "No errors for successful pipeline"
            );
        }

        echo "   Error count: " . count($result['errors']) . "\n";
    }

    /**
     * Test 08: Steps result structure
     */
    public function test_08_steps_structure()
    {
        echo "\nTest 08: Steps result structure at " . __FILE__ . ":351\n";

        $student_id = 130;
        $result = $this->orchestrator->execute($student_id);

        // Check steps structure
        $required_steps = ['sensing', 'decision', 'execution'];
        foreach ($required_steps as $step) {
            $this->assert(
                isset($result['steps'][$step]),
                "Step '{$step}' exists in result"
            );

            $this->assert(
                isset($result['steps'][$step]['success']),
                "Step '{$step}' has success field"
            );
        }

        echo "   All required steps present\n";
    }

    /**
     * Test 09: Data flow through pipeline
     */
    public function test_09_data_flow()
    {
        echo "\nTest 09: Data flow through pipeline at " . __FILE__ . ":379\n";

        $student_id = 131;
        $result = $this->orchestrator->execute($student_id);

        // Check data flows from Sensing to Decision
        $metrics_student_id = $result['steps']['sensing']['data']['student_id'];
        $decision_student_id = $result['steps']['decision']['data']['student_id'];

        $this->assert(
            $metrics_student_id === $decision_student_id,
            "Student ID flows from Sensing to Decision"
        );

        $this->assert(
            $decision_student_id === $student_id,
            "Student ID matches input"
        );

        // Check calm_score used in decision
        $calm_score = $result['steps']['sensing']['data']['calm_score'];
        $rationale = $result['steps']['decision']['data']['rationale'];

        $this->assert(
            strpos($rationale, (string)$calm_score) !== false ||
            strpos($rationale, number_format($calm_score, 1)) !== false,
            "Decision rationale mentions calm score"
        );

        echo "   Data flows correctly through all layers\n";
    }

    /**
     * Test 10: SLA statistics retrieval
     */
    public function test_10_sla_statistics()
    {
        echo "\nTest 10: SLA statistics retrieval at " . __FILE__ . ":416\n";

        // Execute a few pipelines first
        for ($i = 0; $i < 3; $i++) {
            $this->orchestrator->execute(200 + $i);
        }

        // Get stats
        $stats = $this->orchestrator->getSLAStats(24);

        $this->assert(
            isset($stats['total_pipelines']),
            "Total pipelines count present"
        );

        $this->assert(
            isset($stats['sla_compliance_percent']),
            "SLA compliance percentage calculated"
        );

        $this->assert(
            isset($stats['avg_time_ms']),
            "Average time calculated"
        );

        $this->assert(
            $stats['sla_compliance_percent'] >= 0 &&
            $stats['sla_compliance_percent'] <= 100,
            "SLA compliance percent in valid range"
        );

        echo "   Total pipelines: " . $stats['total_pipelines'] . "\n";
        echo "   SLA compliance: " . $stats['sla_compliance_percent'] . "%\n";
        echo "   Avg time: " . $stats['avg_time_ms'] . " ms\n";
    }

    /**
     * Run all tests
     */
    public function runTests()
    {
        echo "=" . str_repeat("=", 69) . "\n";
        echo "Mathking Agentic MVP System - Pipeline Orchestrator Tests\n";
        echo "=" . str_repeat("=", 69) . "\n";

        // Run all test methods
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test_') === 0) {
                $this->$method();
            }
        }

        // Summary
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "Test Summary\n";
        echo str_repeat("=", 70) . "\n";
        echo "Tests run: " . ($this->passed + $this->failed) . "\n";
        echo "Successes: " . $this->passed . "\n";
        echo "Failures: " . $this->failed . "\n";
        echo "\n";

        return $this->failed === 0 ? 0 : 1;
    }
}


// =============================================================================
// Run Tests
// =============================================================================

if (php_sapi_name() === 'cli') {
    $test = new OrchestratorTest();
    $exit_code = $test->runTests();
    exit($exit_code);
}


// =============================================================================
// Test Execution Methods
// =============================================================================
//
// Direct execution:
//    php orchestrator.test.php
//
// Using PHPUnit (if available):
//    phpunit orchestrator.test.php
//
// =============================================================================
