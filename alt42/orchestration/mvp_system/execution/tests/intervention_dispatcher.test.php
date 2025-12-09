<?php
// File: mvp_system/execution/tests/intervention_dispatcher.test.php (Line 1)
// Mathking Agentic MVP System - Intervention Dispatcher Unit Tests
//
// Purpose: Test intervention preparation and execution logic
// Run: php intervention_dispatcher.test.php

// Add parent directory to path
require_once(__DIR__ . '/../intervention_dispatcher.php');
require_once(__DIR__ . '/../../config/app.config.php');
require_once(__DIR__ . '/../../lib/database.php');
require_once(__DIR__ . '/../../lib/logger.php');

class InterventionDispatcherTest
{
    /**
     * Test suite for InterventionDispatcher class
     */

    private $dispatcher;
    private $test_results = [];
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->dispatcher = new InterventionDispatcher();
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
     * Test 01: Template loading
     */
    public function test_01_template_loading()
    {
        echo "\nTest 01: Template loading at " . __FILE__ . ":48\n";

        // Use reflection to access private method
        $reflection = new ReflectionClass($this->dispatcher);
        $method = $reflection->getMethod('loadTemplates');
        $method->setAccessible(true);
        $templates = $method->invoke($this->dispatcher);

        $this->assert(
            is_array($templates) && count($templates) > 0,
            "Templates loaded successfully"
        );

        $this->assert(
            isset($templates['micro_break']) && isset($templates['ask_teacher']),
            "Required action templates exist"
        );

        $this->assert(
            isset($templates['micro_break']['critical']),
            "Critical micro_break template exists"
        );

        echo "   Template count: " . count($templates) . "\n";
        echo "   Actions available: " . implode(', ', array_keys($templates)) . "\n";
    }

    /**
     * Test 02: Prepare intervention - micro_break critical
     */
    public function test_02_prepare_micro_break_critical()
    {
        echo "\nTest 02: Prepare micro_break (critical) at " . __FILE__ . ":80\n";

        $decision = [
            'id' => 100,
            'student_id' => 123,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 5, 'urgency' => 'critical']),
            'confidence' => 0.95,
            'rationale' => 'Critical calm score',
            'rule_id' => 'calm_break_critical'
        ];

        $intervention = $this->dispatcher->prepare($decision);

        // Assertions
        $this->assert(
            isset($intervention['intervention_id']),
            "Intervention ID generated"
        );

        $this->assert(
            $intervention['type'] === 'micro_break',
            "Intervention type is micro_break"
        );

        $this->assert(
            $intervention['target_student_id'] === 123,
            "Target student ID is 123"
        );

        $this->assert(
            $intervention['status'] === 'pending',
            "Initial status is pending"
        );

        // Check message content
        $message = json_decode($intervention['message'], true);
        $this->assert(
            isset($message['title']) && !empty($message['title']),
            "Message has title"
        );

        $this->assert(
            isset($message['urgency']) && $message['urgency'] !== 'none',
            "Message has urgency level"
        );

        echo "   Intervention ID: " . $intervention['intervention_id'] . "\n";
        echo "   Message title: " . $message['title'] . "\n";
        echo "   Urgency: " . $message['urgency'] . "\n";
    }

    /**
     * Test 03: Prepare intervention - micro_break low
     */
    public function test_03_prepare_micro_break_low()
    {
        echo "\nTest 03: Prepare micro_break (low) at " . __FILE__ . ":136\n";

        $decision = [
            'id' => 101,
            'student_id' => 124,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 3, 'urgency' => 'low']),
            'confidence' => 0.85,
            'rationale' => 'Low calm score',
            'rule_id' => 'calm_break_low'
        ];

        $intervention = $this->dispatcher->prepare($decision);

        // Check message contains duration
        $message = json_decode($intervention['message'], true);
        $this->assert(
            strpos($message['body'], '3') !== false || strpos($message['body'], '분') !== false,
            "Message mentions duration"
        );

        echo "   Message: " . substr($message['body'], 0, 50) . "...\n";
    }

    /**
     * Test 04: Prepare intervention - ask_teacher
     */
    public function test_04_prepare_ask_teacher()
    {
        echo "\nTest 04: Prepare ask_teacher at " . __FILE__ . ":167\n";

        $decision = [
            'id' => 102,
            'student_id' => 125,
            'action' => 'ask_teacher',
            'params' => json_encode(['reason' => 'declining_performance']),
            'confidence' => 0.75,
            'rationale' => 'Teacher review needed',
            'rule_id' => 'calm_declining_teacher_check'
        ];

        $intervention = $this->dispatcher->prepare($decision);

        $this->assert(
            $intervention['type'] === 'ask_teacher',
            "Intervention type is ask_teacher"
        );

        $message = json_decode($intervention['message'], true);
        $this->assert(
            isset($message['title']) && strpos($message['title'], '선생님') !== false,
            "Message mentions teacher"
        );

        echo "   Message: " . $message['body'] . "\n";
    }

    /**
     * Test 05: Prepare intervention - none (no action)
     */
    public function test_05_prepare_none()
    {
        echo "\nTest 05: Prepare none (no action) at " . __FILE__ . ":201\n";

        $decision = [
            'id' => 103,
            'student_id' => 126,
            'action' => 'none',
            'params' => json_encode(['monitor_interval_minutes' => 5]),
            'confidence' => 0.80,
            'rationale' => 'Calm score acceptable',
            'rule_id' => 'calm_moderate_monitor'
        ];

        $intervention = $this->dispatcher->prepare($decision);

        $this->assert(
            $intervention['type'] === 'none',
            "Intervention type is none"
        );

        $message = json_decode($intervention['message'], true);
        $this->assert(
            $message['action_button'] === null,
            "No action button for 'none' intervention"
        );
    }

    /**
     * Test 06: Execute intervention - success
     */
    public function test_06_execute_success()
    {
        echo "\nTest 06: Execute intervention (success) at " . __FILE__ . ":232\n";

        $decision = [
            'id' => 104,
            'student_id' => 127,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 3, 'urgency' => 'medium']),
            'confidence' => 0.85,
            'rationale' => 'Low calm score',
            'rule_id' => 'calm_break_low'
        ];

        $intervention = $this->dispatcher->prepare($decision);
        $result = $this->dispatcher->execute($intervention);

        // Assertions
        $this->assert(
            $result['success'] === true,
            "Execution succeeded"
        );

        $this->assert(
            isset($result['intervention_id']),
            "Intervention ID returned"
        );

        $this->assert(
            isset($result['intervention_db_id']) && $result['intervention_db_id'] > 0,
            "Intervention saved to database"
        );

        $this->assert(
            isset($result['execution_time_ms']) && $result['execution_time_ms'] > 0,
            "Execution time measured"
        );

        $this->assert(
            isset($result['lms_result']['message_id']),
            "LMS message ID returned"
        );

        echo "   Execution time: " . $result['execution_time_ms'] . " ms\n";
        echo "   LMS message ID: " . $result['lms_result']['message_id'] . "\n";
        echo "   Status: " . $result['status'] . "\n";
    }

    /**
     * Test 07: Get intervention status
     */
    public function test_07_get_status()
    {
        echo "\nTest 07: Get intervention status at " . __FILE__ . ":282\n";

        // Create and execute an intervention
        $decision = [
            'id' => 105,
            'student_id' => 128,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 3]),
            'confidence' => 0.85,
            'rule_id' => 'calm_break_low'
        ];

        $intervention = $this->dispatcher->prepare($decision);
        $result = $this->dispatcher->execute($intervention);

        // Get status
        $status = $this->dispatcher->getStatus($intervention['intervention_id']);

        $this->assert(
            $status !== null,
            "Status retrieved successfully"
        );

        $this->assert(
            $status['intervention_id'] === $intervention['intervention_id'],
            "Correct intervention ID"
        );

        $this->assert(
            in_array($status['status'], ['pending', 'sent', 'delivered', 'completed', 'failed']),
            "Valid status value"
        );

        echo "   Intervention ID: " . $status['intervention_id'] . "\n";
        echo "   Status: " . $status['status'] . "\n";
    }

    /**
     * Test 08: Metadata inclusion
     */
    public function test_08_metadata_inclusion()
    {
        echo "\nTest 08: Metadata inclusion at " . __FILE__ . ":325\n";

        $decision = [
            'id' => 106,
            'student_id' => 129,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 3, 'urgency' => 'medium']),
            'confidence' => 0.85,
            'rationale' => 'Low calm score',
            'rule_id' => 'calm_break_low'
        ];

        $intervention = $this->dispatcher->prepare($decision);

        // Check metadata
        $metadata = json_decode($intervention['metadata'], true);

        $this->assert(
            isset($metadata['template_source']),
            "Metadata includes template source"
        );

        $this->assert(
            isset($metadata['decision_confidence']),
            "Metadata includes decision confidence"
        );

        $this->assert(
            isset($metadata['decision_rule_id']),
            "Metadata includes rule ID"
        );

        echo "   Template source: " . $metadata['template_source'] . "\n";
        echo "   Decision confidence: " . $metadata['decision_confidence'] . "\n";
        echo "   Rule ID: " . $metadata['decision_rule_id'] . "\n";
    }

    /**
     * Test 09: Intervention ID uniqueness
     */
    public function test_09_intervention_id_uniqueness()
    {
        echo "\nTest 09: Intervention ID uniqueness at " . __FILE__ . ":367\n";

        $decision = [
            'id' => 107,
            'student_id' => 130,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 3]),
            'confidence' => 0.85,
            'rule_id' => 'calm_break_low'
        ];

        $intervention1 = $this->dispatcher->prepare($decision);
        $intervention2 = $this->dispatcher->prepare($decision);

        $this->assert(
            $intervention1['intervention_id'] !== $intervention2['intervention_id'],
            "Each intervention has unique ID"
        );

        echo "   ID 1: " . $intervention1['intervention_id'] . "\n";
        echo "   ID 2: " . $intervention2['intervention_id'] . "\n";
    }

    /**
     * Test 10: Message format validation
     */
    public function test_10_message_format()
    {
        echo "\nTest 10: Message format validation at " . __FILE__ . ":397\n";

        $decision = [
            'id' => 108,
            'student_id' => 131,
            'action' => 'micro_break',
            'params' => json_encode(['duration_minutes' => 5, 'urgency' => 'critical']),
            'confidence' => 0.95,
            'rule_id' => 'calm_break_critical'
        ];

        $intervention = $this->dispatcher->prepare($decision);
        $message = json_decode($intervention['message'], true);

        // Validate message structure
        $required_fields = ['title', 'body', 'urgency'];
        foreach ($required_fields as $field) {
            $this->assert(
                isset($message[$field]),
                "Message has field: $field"
            );
        }

        $this->assert(
            is_string($message['title']) && strlen($message['title']) > 0,
            "Message title is non-empty string"
        );

        $this->assert(
            is_string($message['body']) && strlen($message['body']) > 10,
            "Message body is substantive (>10 chars)"
        );

        echo "   Title length: " . strlen($message['title']) . "\n";
        echo "   Body length: " . strlen($message['body']) . "\n";
    }

    /**
     * Run all tests
     */
    public function runTests()
    {
        echo "=" . str_repeat("=", 69) . "\n";
        echo "Mathking Agentic MVP System - Intervention Dispatcher Tests\n";
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
    $test = new InterventionDispatcherTest();
    $exit_code = $test->runTests();
    exit($exit_code);
}


// =============================================================================
// Test Execution Methods
// =============================================================================
//
// Direct execution:
//    php intervention_dispatcher.test.php
//
// Using PHPUnit (if available):
//    phpunit intervention_dispatcher.test.php
//
// =============================================================================
