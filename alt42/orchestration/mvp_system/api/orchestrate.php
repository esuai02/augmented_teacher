<?php
// File: mvp_system/api/orchestrate.php (Line 1)
// Mathking Agentic MVP System - Orchestrator API
//
// Purpose: REST API endpoint for complete pipeline execution
// Method: POST /mvp_system/api/orchestrate.php
// Input: student_id + optional activity_data
// Output: Complete pipeline result with SLA tracking

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');
require_once(__DIR__ . '/../orchestrator.php');

// Initialize components
$logger = new MVPLogger('orchestrator_api');
$mvp_db = new MVPDatabase();
$orchestrator = new PipelineOrchestrator();

// Set JSON response header
header('Content-Type: application/json');

try {
    // ============================================================
    // Handle GET request - SLA Statistics
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;

        if ($hours < 1 || $hours > 168) { // Max 7 days
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Hours parameter must be between 1 and 168',
                'location' => __FILE__ . ':' . __LINE__
            ]);
            exit;
        }

        $stats = $orchestrator->getSLAStats($hours);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $stats
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // Handle POST request - Execute Pipeline
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST or GET.',
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
    if (!isset($input_data['student_id'])) {
        http_response_code(400);
        $error_msg = "Missing required field: student_id at " . __FILE__ . ':' . __LINE__;
        $logger->error($error_msg, ['input_data' => $input_data]);
        echo json_encode([
            'success' => false,
            'error' => $error_msg
        ]);
        exit;
    }

    $student_id = intval($input_data['student_id']);

    if ($student_id <= 0) {
        http_response_code(400);
        $error_msg = "Invalid student_id: must be positive integer at " . __FILE__ . ':' . __LINE__;
        $logger->error($error_msg, ['student_id' => $student_id]);
        echo json_encode([
            'success' => false,
            'error' => $error_msg
        ]);
        exit;
    }

    // Get optional activity data
    $activity_data = $input_data['activity_data'] ?? null;

    $logger->info("Processing orchestration request", [
        'student_id' => $student_id,
        'has_activity_data' => $activity_data !== null
    ]);

    // Execute pipeline
    $result = $orchestrator->execute($student_id, $activity_data);

    // Return result
    if ($result['success']) {
        http_response_code(201);

        // Build simplified response
        $response = [
            'success' => true,
            'data' => [
                'pipeline_id' => $result['pipeline_id'],
                'student_id' => $result['student_id'],
                'metrics' => [
                    'calm_score' => $result['steps']['sensing']['data']['calm_score'] ?? null,
                    'recommendation' => $result['steps']['sensing']['data']['recommendation'] ?? null
                ],
                'decision' => [
                    'action' => $result['steps']['decision']['data']['action'] ?? null,
                    'confidence' => $result['steps']['decision']['data']['confidence'] ?? null,
                    'rationale' => $result['steps']['decision']['data']['rationale'] ?? null,
                    'rule_id' => $result['steps']['decision']['data']['rule_id'] ?? null
                ],
                'intervention' => [
                    'intervention_id' => $result['steps']['execution']['data']['intervention_id'] ?? null,
                    'status' => $result['steps']['execution']['data']['status'] ?? $result['steps']['execution']['action'] ?? null,
                    'message' => $result['steps']['execution']['message'] ?? null
                ],
                'performance' => $result['performance']
            ]
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

        $logger->info("Orchestration completed successfully", [
            'pipeline_id' => $result['pipeline_id'],
            'sla_met' => $result['performance']['sla_met']
        ]);

    } else {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'pipeline_id' => $result['pipeline_id'],
            'errors' => $result['errors'],
            'steps_completed' => array_keys($result['steps']),
            'performance' => $result['performance'] ?? null,
            'location' => __FILE__ . ':' . __LINE__
        ]);

        $logger->error("Orchestration failed", null, [
            'pipeline_id' => $result['pipeline_id'],
            'errors' => $result['errors']
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

Execute Pipeline (POST):
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{
    "student_id": 123
  }'

Execute Pipeline with Activity Data (POST):
curl -X POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION_ID" \
  -d '{
    "student_id": 123,
    "activity_data": {
      "session_duration": 600,
      "interruptions": 8,
      "focus_time": 300,
      "correct_answers": 5,
      "total_attempts": 10
    }
  }'

Get SLA Statistics (GET):
curl https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/api/orchestrate.php?hours=24

Response Example (Success):
{
  "success": true,
  "data": {
    "pipeline_id": "pipeline-673456789-123",
    "student_id": 123,
    "metrics": {
      "calm_score": 65.5,
      "recommendation": "낮음, 3~5분 휴식 후 재시작"
    },
    "decision": {
      "action": "micro_break",
      "confidence": 0.85,
      "rationale": "Calm score 65.5 is low (60-74). 3-minute breathing exercise recommended.",
      "rule_id": "calm_break_low"
    },
    "intervention": {
      "intervention_id": "int-673456790-123",
      "status": "sent",
      "message": null
    },
    "performance": {
      "sensing_ms": 145.2,
      "decision_ms": 98.5,
      "execution_ms": 156.8,
      "total_ms": 400.5,
      "total_seconds": 0.401,
      "sla_limit_seconds": 180,
      "sla_met": true
    }
  }
}

Response Example (SLA Stats):
{
  "success": true,
  "data": {
    "period_hours": 24,
    "total_pipelines": 47,
    "sla_met_count": 45,
    "sla_compliance_percent": 95.74,
    "avg_time_ms": 385.2,
    "min_time_ms": 298.1,
    "max_time_ms": 512.7,
    "sla_target_seconds": 180
  }
}

Response Example (Error):
{
  "success": false,
  "pipeline_id": "pipeline-673456791-123",
  "errors": [
    "Sensing layer failed: Python script timeout"
  ],
  "steps_completed": ["sensing"],
  "performance": null,
  "location": "/path/to/orchestrate.php:175"
}

=============================================================================
-->
