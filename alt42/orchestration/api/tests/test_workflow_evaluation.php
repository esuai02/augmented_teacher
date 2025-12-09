<?php
/**
 * Workflow Event Evaluation Test
 * 워크플로우 이벤트 평가 테스트
 * 
 * @package ALT42\Tests
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Standalone mode (Moodle 독립)
$moodle_available = file_exists('/home/moodle/public_html/moodle/config.php');
if ($moodle_available) {
    require_once('/home/moodle/public_html/moodle/config.php');
}

// Include dependencies
require_once(__DIR__ . '/../events/workflow_event_processor.php');
require_once(__DIR__ . '/../state/state_change_detector.php');

use ALT42\Events\WorkflowEventProcessor;
use ALT42\State\StateChangeDetector;

/**
 * Test Workflow Event Processor
 */
function testWorkflowEventProcessor() {
    echo "=== Workflow Event Processor Test ===\n";
    echo "Started at " . date('Y-m-d H:i:s') . "\n\n";
    
    $processor = new WorkflowEventProcessor();
    
    // Test 1: 학습 관련 이벤트
    echo "1. Testing learning.answer_wrong event...\n";
    $event1 = array(
        'topic' => 'learning.answer_wrong',
        'student_id' => 'S0000001',
        'result' => 'wrong',
        'timestamp' => date('c')
    );
    
    $result1 = $processor->processWorkflowEvent($event1);
    echo "   Result: " . ($result1['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "   Workflow Group: " . $result1['workflow_group'] . "\n";
    echo "   Scenarios Evaluated: " . $result1['scenarios_evaluated'] . "\n";
    echo "   Duration: " . $result1['duration_ms'] . " ms\n\n";
    
    // Test 2: 생체 신호 이벤트
    echo "2. Testing bio.stress_spike event...\n";
    $event2 = array(
        'topic' => 'bio.stress_spike',
        'student_id' => 'S0000001',
        'stress_level' => 8.5,
        'timestamp' => date('c')
    );
    
    $result2 = $processor->processWorkflowEvent($event2);
    echo "   Result: " . ($result2['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "   Workflow Group: " . $result2['workflow_group'] . "\n";
    echo "   Scenarios Evaluated: " . $result2['scenarios_evaluated'] . "\n";
    echo "   Duration: " . $result2['duration_ms'] . " ms\n\n";
    
    // Test 3: 잘못된 이벤트
    echo "3. Testing invalid event (no student_id)...\n";
    $event3 = array(
        'topic' => 'learning.answer_wrong',
        'result' => 'wrong'
    );
    
    $result3 = $processor->processWorkflowEvent($event3);
    echo "   Result: " . ($result3['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    if (!$result3['success']) {
        echo "   Error: " . $result3['error'] . "\n";
    }
    echo "\n";
    
    echo "=== Workflow Event Processor Test Completed ===\n\n";
}

/**
 * Test State Change Detector
 */
function testStateChangeDetector() {
    echo "=== State Change Detector Test ===\n";
    echo "Started at " . date('Y-m-d H:i:s') . "\n\n";
    
    $detector = new StateChangeDetector();
    
    // Test 1: 스트레스 레벨 변화
    echo "1. Testing stress level change...\n";
    $oldState1 = array(
        'student_id' => 'S0000001',
        'stress_level' => 5.0,
        'activity_state' => 'active'
    );
    
    $newState1 = array(
        'student_id' => 'S0000001',
        'stress_level' => 8.5,  // 변화
        'activity_state' => 'active'
    );
    
    $result1 = $detector->detectAndEvaluate($oldState1, $newState1, 'S0000001');
    echo "   Changed: " . ($result1['changed'] ? 'YES' : 'NO') . "\n";
    if ($result1['changed']) {
        echo "   Changed Fields: " . implode(', ', $result1['changed_fields']) . "\n";
        echo "   Affected Rules: " . $result1['affected_rules'] . "\n";
        echo "   Duration: " . $result1['duration_ms'] . " ms\n";
    }
    echo "\n";
    
    // Test 2: 학습 활동 변화
    echo "2. Testing learning activity change...\n";
    $oldState2 = array(
        'student_id' => 'S0000001',
        'answer_count' => 10,
        'wrong_count' => 3,
        'last_activity' => '2025-01-27T10:00:00Z'
    );
    
    $newState2 = array(
        'student_id' => 'S0000001',
        'answer_count' => 11,  // 변화
        'wrong_count' => 4,     // 변화
        'last_activity' => date('c')  // 변화
    );
    
    $result2 = $detector->detectAndEvaluate($oldState2, $newState2, 'S0000001');
    echo "   Changed: " . ($result2['changed'] ? 'YES' : 'NO') . "\n";
    if ($result2['changed']) {
        echo "   Changed Fields: " . implode(', ', $result2['changed_fields']) . "\n";
        echo "   Affected Rules: " . $result2['affected_rules'] . "\n";
        echo "   Duration: " . $result2['duration_ms'] . " ms\n";
    }
    echo "\n";
    
    // Test 3: 변화 없음
    echo "3. Testing no state change...\n";
    $oldState3 = array(
        'student_id' => 'S0000001',
        'stress_level' => 5.0
    );
    
    $newState3 = array(
        'student_id' => 'S0000001',
        'stress_level' => 5.0  // 동일
    );
    
    $result3 = $detector->detectAndEvaluate($oldState3, $newState3, 'S0000001');
    echo "   Changed: " . ($result3['changed'] ? 'YES' : 'NO') . "\n";
    if (!$result3['changed']) {
        echo "   Message: " . $result3['message'] . "\n";
    }
    echo "\n";
    
    echo "=== State Change Detector Test Completed ===\n\n";
}

/**
 * Run all tests
 */
function runAllTests() {
    echo "========================================\n";
    echo "Workflow Evaluation System Test Suite\n";
    echo "========================================\n\n";
    
    try {
        testWorkflowEventProcessor();
        testStateChangeDetector();
        
        echo "========================================\n";
        echo "All Tests Completed Successfully\n";
        echo "========================================\n";
        
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
        exit(1);
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    runAllTests();
}

