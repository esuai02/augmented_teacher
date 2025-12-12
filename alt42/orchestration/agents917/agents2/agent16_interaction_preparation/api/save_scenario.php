<?php
/**
 * Agent 16 Interaction Preparation - Save Scenario API
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/save_scenario.php
 *
 * Purpose: Save generated interaction scenarios to database
 * Input: userid, guideMode, scenario, vibeCodingPrompt, dbTrackingPrompt
 * Output: JSON with scenarioId or error
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
    $userid = $input['userid'] ?? null;
    $guideMode = $input['guideMode'] ?? null;
    $scenario = $input['scenario'] ?? null;
    $vibeCodingPrompt = $input['vibeCodingPrompt'] ?? '';
    $dbTrackingPrompt = $input['dbTrackingPrompt'] ?? '';

    if (!$userid || !$guideMode || !$scenario) {
        throw new Exception('Missing required fields: userid, guideMode, scenario - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Validate userid matches logged-in user or has appropriate permissions
    if ($userid != $USER->id && !has_capability('moodle/site:config', context_system::instance())) {
        throw new Exception('Permission denied: Cannot save scenario for another user - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Prepare database record
    $record = new stdClass();
    $record->userid = $userid;
    $record->guide_mode = $guideMode;
    $record->vibe_coding_prompt = $vibeCodingPrompt;
    $record->db_tracking_prompt = $dbTrackingPrompt;
    $record->scenario = $scenario;
    $record->created_at = time();
    $record->updated_at = time();

    // Insert into database
    $scenarioId = $DB->insert_record('agent16_interaction_scenarios', $record);

    if (!$scenarioId) {
        throw new Exception('Failed to insert scenario into database - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Log successful save
    error_log('✅ Scenario saved successfully - ID: ' . $scenarioId . ', User: ' . $userid . ', Mode: ' . $guideMode . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return success response
    echo json_encode([
        'success' => true,
        'scenarioId' => $scenarioId,
        'message' => '시나리오가 성공적으로 저장되었습니다.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('❌ Scenario save error: ' . $e->getMessage() . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
