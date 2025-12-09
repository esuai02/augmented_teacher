<?php
// ํŒŒ์ผ: mvp_system/sensing/api/metrics.php (Line 1)
// Mathking Agentic MVP System - Sensing Layer API
//
// Purpose: REST API endpoint for calculating and storing calm metrics
// Method: POST /mvp_system/sensing/api/metrics.php
// Input: JSON with raw student activity data
// Output: JSON matching contracts/schemas/metrics.schema.json

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Load MVP system dependencies
require_once(__DIR__ . '/../../config/app.config.php');
require_once(__DIR__ . '/../../lib/database.php');
require_once(__DIR__ . '/../../lib/logger.php');

// Initialize components
$logger = new MVPLogger('sensing_api');
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
    $required_fields = ['student_id', 'session_duration', 'interruptions'];
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

    $logger->info("Processing calm calculation request", [
        'student_id' => $input_data['student_id'],
        'session_duration' => $input_data['session_duration']
    ]);

    // Call Python calculator
    $python_script = __DIR__ . '/../calm_calculator.py';
    $json_input = escapeshellarg(json_encode($input_data));
    $command = "python3 $python_script $json_input 2>&1";

    $logger->debug("Executing Python calculator", ['command' => $command]);

    $start_time = microtime(true);
    $output = shell_exec($command);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2); // milliseconds

    if ($output === null) {
        throw new Exception("Failed to execute Python calculator at " . __FILE__ . ':' . __LINE__);
    }

    $logger->debug("Python calculator output", [
        'output' => substr($output, 0, 500),
        'execution_time_ms' => $execution_time
    ]);

    // Parse Python output (JSON)
    $metrics = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse calculator output: " . json_last_error_msg() . " at " . __FILE__ . ':' . __LINE__);
    }

    // Validate metrics schema (basic validation)
    if (!isset($metrics['student_id']) || !isset($metrics['calm_score']) || !isset($metrics['timestamp'])) {
        throw new Exception("Invalid metrics output format at " . __FILE__ . ':' . __LINE__);
    }

    $logger->info("Calm score calculated", [
        'student_id' => $metrics['student_id'],
        'calm_score' => $metrics['calm_score'],
        'recommendation' => $metrics['recommendation']
    ]);

    // Store metrics in database
    $db_data = [
        'student_id' => $metrics['student_id'],
        'calm_score' => $metrics['calm_score'],
        'focus_score' => $metrics['focus_score'],
        'flow_score' => $metrics['flow_score'],
        'goal_alignment' => $metrics['goal_alignment'],
        'raw_data' => $metrics['raw_data'],
        'recommendation' => $metrics['recommendation'],
        'timestamp' => date('Y-m-d H:i:s', strtotime($metrics['timestamp']))
    ];

    $metric_id = $mvp_db->insert('snapshot_metrics', $db_data);

    $logger->info("Metrics stored in database", [
        'metric_id' => $metric_id,
        'student_id' => $metrics['student_id']
    ]);

    // Record system performance metric
    $mvp_db->insert('system_metrics', [
        'metric_name' => 'sensing_calculation_time',
        'metric_value' => $execution_time,
        'unit' => 'ms',
        'context' => json_encode(['student_id' => $metrics['student_id']]),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => [
            'metric_id' => $metric_id,
            'student_id' => $metrics['student_id'],
            'calm_score' => $metrics['calm_score'],
            'focus_score' => $metrics['focus_score'],
            'recommendation' => $metrics['recommendation'],
            'timestamp' => $metrics['timestamp']
        ],
        'performance' => [
            'execution_time_ms' => $execution_time
        ]
    ], JSON_UNESCAPED_UNICODE);

    $logger->info("API request completed successfully", [
        'metric_id' => $metric_id,
        'execution_time_ms' => $execution_time
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
API ์‚ฌ์šฉ ์˜ˆ์‹œ (Example Usage)
=============================================================================

curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/sensing/api/metrics.php \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 123,
    "session_duration": 1800,
    "interruptions": 2,
    "correct_answers": 8,
    "total_questions": 10,
    "focus_time": 1600
  }'

์'๋‹ต ์˜ˆ์‹œ:
{
  "success": true,
  "data": {
    "metric_id": 15,
    "student_id": 123,
    "calm_score": 88.5,
    "focus_score": 88.89,
    "recommendation": "์•ˆ์ •, ๊ฐ€๋ฒผ์šด ๋ณต์Šต ๊ถŒ์žฅ",
    "timestamp": "2025-11-02T10:30:00Z"
  },
  "performance": {
    "execution_time_ms": 245.5
  }
}

=============================================================================
-->
