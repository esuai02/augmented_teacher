<?php
/**
 * Agent 16 Interaction Preparation - Delete Scenario API
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/delete_scenario.php
 *
 * Purpose: Delete a saved interaction scenario
 * Input: scenarioId
 * Output: JSON success/error
 */

// Moodle integration
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Validate required fields
    $scenarioId = $input['scenarioId'] ?? null;

    if (!$scenarioId) {
        throw new Exception('Missing required field: scenarioId - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Fetch scenario to verify ownership
    $scenario = $DB->get_record('agent16_interaction_scenarios', ['id' => $scenarioId]);

    if (!$scenario) {
        throw new Exception('Scenario not found - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Validate userid matches logged-in user or has appropriate permissions
    if ($scenario->userid != $USER->id && !has_capability('moodle/site:config', context_system::instance())) {
        throw new Exception('Permission denied: Cannot delete scenario owned by another user - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Delete scenario
    $success = $DB->delete_records('agent16_interaction_scenarios', ['id' => $scenarioId]);

    if (!$success) {
        throw new Exception('Failed to delete scenario from database - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Log successful deletion
    error_log('✅ Scenario deleted successfully - ID: ' . $scenarioId . ', User: ' . $USER->id . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => '시나리오가 성공적으로 삭제되었습니다.',
        'scenarioId' => $scenarioId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('❌ Scenario deletion error: ' . $e->getMessage() . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
