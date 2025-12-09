<?php
/**
 * Agent 16 Interaction Preparation - List Scenarios API
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/list_scenarios.php
 *
 * Purpose: Retrieve saved interaction scenarios for a user
 * Input: userid (optional, defaults to current user)
 * Output: JSON array of scenarios
 */

// Moodle integration
require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get userid from query parameter or use current user
    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : $USER->id;

    // Validate userid matches logged-in user or has appropriate permissions
    if ($userid != $USER->id && !has_capability('moodle/site:config', context_system::instance())) {
        throw new Exception('Permission denied: Cannot view scenarios for another user - File: ' . __FILE__ . ' Line: ' . __LINE__);
    }

    // Fetch scenarios from database
    $scenarios = $DB->get_records(
        'agent16_interaction_scenarios',
        ['userid' => $userid],
        'created_at DESC',
        '*',
        0,
        50  // Limit to 50 most recent
    );

    // Convert to array and format
    $scenarioList = [];
    foreach ($scenarios as $scenario) {
        $scenarioList[] = [
            'id' => $scenario->id,
            'guideMode' => $scenario->guide_mode,
            'vibeCodingPrompt' => $scenario->vibe_coding_prompt,
            'dbTrackingPrompt' => $scenario->db_tracking_prompt,
            'scenario' => $scenario->scenario,
            'createdAt' => date('Y-m-d H:i:s', $scenario->created_at),
            'createdAtUnix' => $scenario->created_at,
            'updatedAt' => $scenario->updated_at ? date('Y-m-d H:i:s', $scenario->updated_at) : null
        ];
    }

    // Log successful retrieval
    error_log('✅ Retrieved ' . count($scenarioList) . ' scenarios for user: ' . $userid . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return success response
    echo json_encode([
        'success' => true,
        'scenarios' => $scenarioList,
        'count' => count($scenarioList),
        'userid' => $userid
    ]);

} catch (Exception $e) {
    error_log('❌ Scenario retrieval error: ' . $e->getMessage() . ' - File: ' . __FILE__ . ' Line: ' . __LINE__);

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
