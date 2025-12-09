<?php
// File: mvp_system/execution/api/execute.php (Line 1)
// Mathking Agentic MVP System - Execution Layer API
//
// Purpose: REST API endpoint for intervention execution
// Method: POST /mvp_system/execution/api/execute.php
// Input: Decision object from Decision Layer
// Output: JSON with intervention execution status

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Load MVP system dependencies
require_once(__DIR__ . '/../../config/app.config.php');
require_once(__DIR__ . '/../../lib/database.php');
require_once(__DIR__ . '/../../lib/logger.php');
require_once(__DIR__ . '/../intervention_dispatcher.php');

// Initialize components
$logger = new MVPLogger('execution_api');
$mvp_db = new MVPDatabase();
$dispatcher = new InterventionDispatcher();

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate HTTP method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST.',
            'location' => __FILE__ . ':' . __LINE__
        ]);
        exit;
    }

    // Get raw POST data
    $raw_input = file_get_contents('php://input');
    $logger->debug("Received POST request", ['raw_input' => substr($raw_input, 0, 200)]);

    // Parse JSON input
    $input_data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        $error_msg = 'Invalid JSON input: ' . json_last_error_msg() . ' at ' . __FILE__ . ':' . __LINE__;
        $logger->error($error_msg);
        echo json_encode([
            'success' => false,
            'error' => $error_msg
        ]);
        exit;
    }

    // Two modes of operation:
    // 1. Execute from decision_id (fetch decision from DB)
    // 2. Execute from full decision object (direct input)

    $decision = null;

    if (isset($input_data['decision_id'])) {
        // Mode 1: Fetch decision from database
        $decision_id = $input_data['decision_id'];
        $decision_records = $mvp_db->query(
            "SELECT * FROM mdl_mvp_decision_log WHERE id = ?",
            [$decision_id]
        );

        if (empty($decision_records)) {
            http_response_code(404);
            $error_msg = "Decision not found: {$decision_id} at " . __FILE__ . ':' . __LINE__;
            $logger->error($error_msg);
            echo json_encode([
                'success' => false,
                'error' => $error_msg
            ]);
            exit;
        }

        $decision = $decision_records[0];

    } else {
        // Mode 2: Use provided decision object
        // Validate required fields
        $required_fields = ['student_id', 'action'];
        foreach ($required_fields as $field) {
            if (!isset($input_data[$field])) {
                http_response_code(400);
                $error_msg = "Missing required field: $field at " . __FILE__ . ':' . __LINE__;
                $logger->error($error_msg, ['input_data' => $input_data]);
                echo json_encode([
                    'success' => false,
                    'error' => $error_msg
                ]);
                exit;
            }
        }

        $decision = $input_data;
    }

    $logger->info("Processing intervention execution", [
        'decision_id' => $decision['id'] ?? null,
        'action' => $decision['action'],
        'student_id' => $decision['student_id']
    ]);

    // Check if action is 'none' (no intervention needed)
    if ($decision['action'] === 'none') {
        $logger->info("No intervention needed", [
            'decision_id' => $decision['id'] ?? null,
            'student_id' => $decision['student_id']
        ]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => 'No intervention required for this decision',
                'action' => 'none',
                'student_id' => $decision['student_id']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Prepare intervention
    $start_time = microtime(true);
    $intervention = $dispatcher->prepare($decision);

    // Execute intervention
    $execution_result = $dispatcher->execute($intervention);

    $total_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($execution_result['success']) {
        $logger->info("Intervention executed successfully", [
            'intervention_id' => $execution_result['intervention_id'],
            'total_time_ms' => $total_time
        ]);

        // Success response
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => [
                'intervention_id' => $execution_result['intervention_id'],
                'intervention_db_id' => $execution_result['intervention_db_id'],
                'student_id' => $decision['student_id'],
                'action' => $decision['action'],
                'status' => $execution_result['status'],
                'lms_message_id' => $execution_result['lms_result']['message_id'] ?? null,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'performance' => [
                'execution_time_ms' => $execution_result['execution_time_ms'],
                'total_time_ms' => $total_time
            ]
        ], JSON_UNESCAPED_UNICODE);

    } else {
        http_response_code(500);
        $logger->error("Intervention execution failed", null, [
            'intervention_id' => $execution_result['intervention_id'],
            'error' => $execution_result['error']
        ]);

        echo json_encode([
            'success' => false,
            'error' => $execution_result['error'],
            'intervention_id' => $execution_result['intervention_id'],
            'location' => __FILE__ . ':' . __LINE__
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $e->getMessage();
    $logger->error("API request failed", $e, ['input_data' => $input_data ?? null]);

    echo json_encode([
        'success' => false,
        'error' => $error_msg,
        'location' => __FILE__ . ':' . __LINE__
    ]);
}
?>

<!--
=============================================================================
API Usage Examples
=============================================================================

Mode 1: Execute from decision_id (fetch decision from DB)
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/api/execute.php \
  -H "Content-Type: application/json" \
  -d '{
    "decision_id": 25
  }'

Mode 2: Execute from full decision object
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/execution/api/execute.php \
  -H "Content-Type: application/json" \
  -d '{
    "id": 25,
    "student_id": 123,
    "action": "micro_break",
    "params": "{\"duration_minutes\": 3, \"urgency\": \"medium\"}",
    "confidence": 0.85,
    "rationale": "Calm score is low",
    "rule_id": "calm_break_low"
  }'

Response Example (Success):
{
  "success": true,
  "data": {
    "intervention_id": "int-673456789-123",
    "intervention_db_id": 42,
    "student_id": 123,
    "action": "micro_break",
    "status": "sent",
    "lms_message_id": 1730567890123,
    "timestamp": "2025-11-02T10:30:18Z"
  },
  "performance": {
    "execution_time_ms": 145.8,
    "total_time_ms": 156.2
  }
}

Response Example (No Intervention):
{
  "success": true,
  "data": {
    "message": "No intervention required for this decision",
    "action": "none",
    "student_id": 123
  }
}

Response Example (Error):
{
  "success": false,
  "error": "Decision not found: 999",
  "location": "/path/to/execute.php:85"
}

=============================================================================
-->
