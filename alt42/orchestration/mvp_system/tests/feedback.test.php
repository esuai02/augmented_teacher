<?php
// File: mvp_system/tests/feedback.test.php (Line 1)
// Mathking Agentic MVP System - Feedback API Tests
//
// Purpose: Test teacher feedback submission workflow
// Run: php feedback.test.php

// Add parent directory to path
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

class FeedbackTest
{
    /**
     * Test suite for feedback API and storage
     */

    private $mvp_db;
    private $logger;
    private $test_results = [];
    private $passed = 0;
    private $failed = 0;
    private $test_decision_id = null;
    private $test_feedback_id = null;

    public function __construct()
    {
        $this->mvp_db = new MVPDatabase();
        $this->logger = new MVPLogger('feedback_test');
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
     * Setup: Create test decision
     */
    private function setupTestDecision()
    {
        echo "\n🔧 Setting up test decision...\n";

        // Use high student ID to avoid conflicts with real data
        $test_student_id = 90001;

        // Create test metrics record
        $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_snapshot_metrics
             (student_id, calm_score, recommendation, context, timestamp)
             VALUES (?, ?, ?, ?, NOW())",
            [
                $test_student_id,
                65.5,
                'Low calm - recommend 3-minute break',
                json_encode(['test' => true]),
            ]
        );

        // Create test decision record
        $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_decision_log
             (student_id, action, params, confidence, rationale, rule_id, timestamp)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $test_student_id,
                'micro_break',
                json_encode(['duration_minutes' => 3]),
                0.85,
                'Calm score 65.5 is low (60-74). 3-minute break recommended.',
                'calm_break_low'
            ]
        );

        // Get the inserted decision ID
        $this->test_decision_id = $this->mvp_db->getLastInsertId();

        echo "   Created test decision ID: {$this->test_decision_id}\n";

        return $this->test_decision_id !== null;
    }

    /**
     * Cleanup: Remove test data
     */
    private function cleanupTestData()
    {
        echo "\n🧹 Cleaning up test data...\n";

        if ($this->test_feedback_id) {
            $this->mvp_db->execute(
                "DELETE FROM mdl_mvp_teacher_feedback WHERE id = ?",
                [$this->test_feedback_id]
            );
        }

        if ($this->test_decision_id) {
            $this->mvp_db->execute(
                "DELETE FROM mdl_mvp_decision_log WHERE id = ?",
                [$this->test_decision_id]
            );
            $this->mvp_db->execute(
                "DELETE FROM mdl_mvp_snapshot_metrics WHERE student_id >= 90000"
            );
        }

        echo "   Cleanup completed\n";
    }

    /**
     * Test 01: Insert new feedback (approve)
     */
    public function test_01_insert_feedback_approve()
    {
        echo "\nTest 01: Insert new feedback (approve) at " . __FILE__ . ":114\n";

        $test_teacher_id = 1; // Test teacher ID

        $result = $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_teacher_feedback
             (decision_id, teacher_id, response, comment, timestamp)
             VALUES (?, ?, ?, ?, NOW())",
            [$this->test_decision_id, $test_teacher_id, 'approve', 'Good decision']
        );

        $this->assert($result !== false, "Feedback insert succeeded");

        // Get the inserted feedback ID
        $this->test_feedback_id = $this->mvp_db->getLastInsertId();

        $this->assert(
            $this->test_feedback_id > 0,
            "Feedback ID generated: {$this->test_feedback_id}"
        );

        // Verify feedback was stored
        $feedback = $this->mvp_db->query(
            "SELECT * FROM mdl_mvp_teacher_feedback WHERE id = ?",
            [$this->test_feedback_id]
        );

        $this->assert(
            !empty($feedback),
            "Feedback record exists in database"
        );

        $this->assert(
            $feedback[0]['response'] === 'approve',
            "Response is 'approve'"
        );

        $this->assert(
            $feedback[0]['comment'] === 'Good decision',
            "Comment stored correctly"
        );

        echo "   Feedback ID: {$this->test_feedback_id}\n";
    }

    /**
     * Test 02: Update existing feedback (reject)
     */
    public function test_02_update_feedback_reject()
    {
        echo "\nTest 02: Update existing feedback (reject) at " . __FILE__ . ":163\n";

        $result = $this->mvp_db->execute(
            "UPDATE mdl_mvp_teacher_feedback
             SET response = ?, comment = ?, timestamp = NOW()
             WHERE id = ?",
            ['reject', 'On second thought, this is not appropriate', $this->test_feedback_id]
        );

        $this->assert($result !== false, "Feedback update succeeded");

        // Verify feedback was updated
        $feedback = $this->mvp_db->query(
            "SELECT * FROM mdl_mvp_teacher_feedback WHERE id = ?",
            [$this->test_feedback_id]
        );

        $this->assert(
            $feedback[0]['response'] === 'reject',
            "Response updated to 'reject'"
        );

        $this->assert(
            strpos($feedback[0]['comment'], 'not appropriate') !== false,
            "Comment updated correctly"
        );
    }

    /**
     * Test 03: Query feedback with decision join
     */
    public function test_03_query_feedback_with_decision()
    {
        echo "\nTest 03: Query feedback with decision join at " . __FILE__ . ":195\n";

        $result = $this->mvp_db->query(
            "SELECT
                d.id as decision_id,
                d.student_id,
                d.action,
                d.confidence,
                d.rationale,
                f.id as feedback_id,
                f.response,
                f.comment
             FROM mdl_mvp_decision_log d
             LEFT JOIN mdl_mvp_teacher_feedback f ON d.id = f.decision_id
             WHERE d.id = ?",
            [$this->test_decision_id]
        );

        $this->assert(
            !empty($result),
            "Join query returned results"
        );

        $row = $result[0];

        $this->assert(
            $row['decision_id'] == $this->test_decision_id,
            "Decision ID matches"
        );

        $this->assert(
            $row['feedback_id'] == $this->test_feedback_id,
            "Feedback ID matches"
        );

        $this->assert(
            $row['action'] === 'micro_break',
            "Decision action retrieved"
        );

        $this->assert(
            $row['response'] === 'reject',
            "Feedback response retrieved"
        );
    }

    /**
     * Test 04: Defer feedback
     */
    public function test_04_defer_feedback()
    {
        echo "\nTest 04: Defer feedback at " . __FILE__ . ":244\n";

        $result = $this->mvp_db->execute(
            "UPDATE mdl_mvp_teacher_feedback
             SET response = ?, comment = ?
             WHERE id = ?",
            ['defer', 'Need more information before deciding', $this->test_feedback_id]
        );

        $this->assert($result !== false, "Defer update succeeded");

        // Verify defer status
        $feedback = $this->mvp_db->query(
            "SELECT response FROM mdl_mvp_teacher_feedback WHERE id = ?",
            [$this->test_feedback_id]
        );

        $this->assert(
            $feedback[0]['response'] === 'defer',
            "Response is 'defer'"
        );
    }

    /**
     * Test 05: Feedback statistics query
     */
    public function test_05_feedback_statistics()
    {
        echo "\nTest 05: Feedback statistics query at " . __FILE__ . ":273\n";

        // Get total feedback counts
        $stats = $this->mvp_db->query(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN response = 'approve' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN response = 'reject' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN response = 'defer' THEN 1 ELSE 0 END) as deferred
             FROM mdl_mvp_teacher_feedback"
        );

        $this->assert(
            isset($stats[0]['total']),
            "Statistics query succeeded"
        );

        $this->assert(
            $stats[0]['total'] >= 1,
            "At least one feedback record exists"
        );

        echo "   Total feedback: {$stats[0]['total']}\n";
        echo "   Approved: {$stats[0]['approved']}\n";
        echo "   Rejected: {$stats[0]['rejected']}\n";
        echo "   Deferred: {$stats[0]['deferred']}\n";
    }

    /**
     * Test 06: Feedback with empty comment
     */
    public function test_06_feedback_empty_comment()
    {
        echo "\nTest 06: Feedback with empty comment at " . __FILE__ . ":305\n";

        // Create another test decision for this test
        $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_decision_log
             (student_id, action, params, confidence, rationale, rule_id, timestamp)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [90002, 'none', '{}', 0.95, 'High calm score', 'calm_optimal']
        );

        $decision_id_2 = $this->mvp_db->getLastInsertId();

        // Insert feedback without comment
        $result = $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_teacher_feedback
             (decision_id, teacher_id, response, comment, timestamp)
             VALUES (?, ?, ?, ?, NOW())",
            [$decision_id_2, 1, 'approve', '']
        );

        $this->assert($result !== false, "Feedback with empty comment inserted");

        // Cleanup
        $feedback_id_2 = $this->mvp_db->getLastInsertId();
        $this->mvp_db->execute("DELETE FROM mdl_mvp_teacher_feedback WHERE id = ?", [$feedback_id_2]);
        $this->mvp_db->execute("DELETE FROM mdl_mvp_decision_log WHERE id = ?", [$decision_id_2]);
    }

    /**
     * Test 07: Pending feedback query (no feedback yet)
     */
    public function test_07_pending_feedback_query()
    {
        echo "\nTest 07: Pending feedback query at " . __FILE__ . ":339\n";

        // Create decision without feedback
        $this->mvp_db->execute(
            "INSERT INTO mdl_mvp_decision_log
             (student_id, action, params, confidence, rationale, rule_id, timestamp)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [90003, 'micro_break', '{}', 0.80, 'Moderate intervention', 'calm_break_low']
        );

        $decision_id_3 = $this->mvp_db->getLastInsertId();

        // Query for pending (no feedback)
        $pending = $this->mvp_db->query(
            "SELECT d.id
             FROM mdl_mvp_decision_log d
             LEFT JOIN mdl_mvp_teacher_feedback f ON d.id = f.decision_id
             WHERE d.id = ? AND (f.response IS NULL OR f.response = 'defer')",
            [$decision_id_3]
        );

        $this->assert(
            !empty($pending),
            "Pending decision found (no feedback)"
        );

        // Cleanup
        $this->mvp_db->execute("DELETE FROM mdl_mvp_decision_log WHERE id = ?", [$decision_id_3]);
    }

    /**
     * Test 08: Feedback timestamp validation
     */
    public function test_08_feedback_timestamp()
    {
        echo "\nTest 08: Feedback timestamp validation at " . __FILE__ . ":374\n";

        $feedback = $this->mvp_db->query(
            "SELECT timestamp FROM mdl_mvp_teacher_feedback WHERE id = ?",
            [$this->test_feedback_id]
        );

        $this->assert(
            !empty($feedback[0]['timestamp']),
            "Timestamp exists"
        );

        $timestamp = strtotime($feedback[0]['timestamp']);
        $now = time();
        $diff = abs($now - $timestamp);

        $this->assert(
            $diff < 300, // Within 5 minutes
            "Timestamp is recent (within 5 minutes)"
        );

        echo "   Timestamp: {$feedback[0]['timestamp']}\n";
    }

    /**
     * Run all tests
     */
    public function runTests()
    {
        echo "==" . str_repeat("=", 68) . "\n";
        echo "Mathking Agentic MVP System - Feedback Tests\n";
        echo "==" . str_repeat("=", 68) . "\n";

        // Setup
        if (!$this->setupTestDecision()) {
            echo "❌ Failed to setup test decision. Aborting tests.\n";
            return 1;
        }

        // Run all test methods
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test_') === 0) {
                try {
                    $this->$method();
                } catch (Exception $e) {
                    echo "❌ EXCEPTION in $method: " . $e->getMessage() . " at " . __FILE__ . ":422\n";
                    $this->failed++;
                }
            }
        }

        // Cleanup
        $this->cleanupTestData();

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
    $test = new FeedbackTest();
    $exit_code = $test->runTests();
    exit($exit_code);
}
?>
