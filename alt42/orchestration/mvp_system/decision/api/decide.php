<?php
// File: mvp_system/decision/api/decide.php (Line 1)
// Mathking Agentic MVP System - Decision Layer API
//
// Purpose: REST API endpoint for rule-based decision making
// Method: POST /mvp_system/decision/api/decide.php
// Input: JSON with student metrics (from Sensing layer)
// Output: JSON matching contracts/schemas/decision.schema.json

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Load MVP system dependencies
require_once(__DIR__ . '/../../config/app.config.php');
require_once(__DIR__ . '/../../lib/database.php');
require_once(__DIR__ . '/../../lib/logger.php');

// Initialize components
$logger = new MVPLogger('decision_api');
$mvp_db = new MVPDatabase();

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

    // Validate required fields
    $required_fields = ['student_id', 'calm_score'];
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

    $logger->info("Processing decision request", [
        'student_id' => $input_data['student_id'],
        'calm_score' => $input_data['calm_score']
    ]);

    // Call Python rule engine
    $python_script = __DIR__ . '/../rule_engine.py';
    $json_input = escapeshellarg(json_encode($input_data));
    $command = "python3 $python_script $json_input 2>&1";

    $logger->debug("Executing Python rule engine", ['command' => $command]);

    $start_time = microtime(true);
    $output = shell_exec($command);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2); // milliseconds

    if ($output === null) {
        throw new Exception("Failed to execute Python rule engine at " . __FILE__ . ':' . __LINE__);
    }

    $logger->debug("Python rule engine output", [
        'output' => substr($output, 0, 500),
        'execution_time_ms' => $execution_time
    ]);

    // Parse Python output (JSON)
    $decision = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse rule engine output: " . json_last_error_msg() . " at " . __FILE__ . ':' . __LINE__);
    }

    // Validate decision schema (basic validation)
    if (!isset($decision['student_id']) || !isset($decision['action']) || !isset($decision['confidence'])) {
        throw new Exception("Invalid decision output format at " . __FILE__ . ':' . __LINE__);
    }

    $logger->info("Decision made", [
        'student_id' => $decision['student_id'],
        'action' => $decision['action'],
        'confidence' => $decision['confidence'],
        'rule_id' => $decision['rule_id']
    ]);

    // Store decision in database
    $db_data = [
        'student_id' => $decision['student_id'],
        'action' => $decision['action'],
        'params' => $decision['params'],
        'confidence' => $decision['confidence'],
        'rationale' => $decision['rationale'],
        'rule_id' => $decision['rule_id'],
        'trace_data' => $decision['trace_data'],
        'timestamp' => date('Y-m-d H:i:s', strtotime($decision['timestamp']))
    ];

    $decision_id = $mvp_db->insert('decision_log', $db_data);

    $logger->info("Decision stored in database", [
        'decision_id' => $decision_id,
        'student_id' => $decision['student_id']
    ]);

    // Record system performance metric
    $mvp_db->insert('system_metrics', [
        'metric_name' => 'decision_processing_time',
        'metric_value' => $execution_time,
        'unit' => 'ms',
        'context' => json_encode([
            'student_id' => $decision['student_id'],
            'action' => $decision['action']
        ]),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Check if action requires teacher approval (ask_teacher or high-impact actions)
    $requires_teacher_approval = false;
    if ($decision['action'] === 'ask_teacher' ||
        ($decision['action'] === 'micro_break' && $decision['confidence'] < 0.80)) {
        $requires_teacher_approval = true;
    }

    // Success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => [
            'decision_id' => $decision_id,
            'student_id' => $decision['student_id'],
            'action' => $decision['action'],
            'confidence' => $decision['confidence'],
            'rationale' => $decision['rationale'],
            'rule_id' => $decision['rule_id'],
            'requires_teacher_approval' => $requires_teacher_approval,
            'timestamp' => $decision['timestamp']
        ],
        'performance' => [
            'execution_time_ms' => $execution_time
        ]
    ], JSON_UNESCAPED_UNICODE);

    $logger->info("API request completed successfully", [
        'decision_id' => $decision_id,
        'execution_time_ms' => $execution_time,
        'requires_approval' => $requires_teacher_approval
    ]);

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

curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/decision/api/decide.php \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 123,
    "calm_score": 70.5,
    "focus_score": 65.0,
    "recommendation": "Low calm state detected",
    "timestamp": "2025-11-02T10:30:00Z"
  }'

Response Example:
{
  "success": true,
  "data": {
    "decision_id": 25,
    "student_id": 123,
    "action": "micro_break",
    "confidence": 0.85,
    "rationale": "Calm score 70.5 is low (60-74). 3-minute breathing exercise recommended per agent08 policy.",
    "rule_id": "calm_break_low",
    "requires_teacher_approval": false,
    "timestamp": "2025-11-02T10:30:00Z"
  },
  "performance": {
    "execution_time_ms": 185.3
  }
}

=============================================================================
-->
