<?php
// File: mvp_system/ui/standalone_feedback_api.php
// Standalone Feedback API (No Moodle Dependency)
//
// Purpose: Handle teacher feedback submissions via AJAX
// Access: Teachers only (standalone authentication)
// Error Location: /mvp_system/ui/standalone_feedback_api.php

require_once(__DIR__ . '/standalone_config.php');
require_once(__DIR__ . '/standalone_database.php');

// Set JSON response header
header('Content-Type: application/json');

// Require teacher access
try {
    require_teacher_access();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required',
        'location' => 'standalone_feedback_api.php:line 18'
    ]);
    exit;
}

$db = new StandaloneDB();
$user = get_current_user();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed - POST required',
        'location' => 'standalone_feedback_api.php:line 33'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input',
        'location' => 'standalone_feedback_api.php:line 47'
    ]);
    exit;
}

// Required fields
$decision_id = isset($data['decision_id']) ? intval($data['decision_id']) : 0;
$response = isset($data['response']) ? trim($data['response']) : '';
$comment = isset($data['comment']) ? trim($data['comment']) : '';

// Validate decision_id
if (!$decision_id || $decision_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid decision_id',
        'location' => 'standalone_feedback_api.php:line 62'
    ]);
    exit;
}

// Validate response
$allowed_responses = ['approve', 'reject', 'defer'];
if (!in_array($response, $allowed_responses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response type. Must be: approve, reject, or defer',
        'location' => 'standalone_feedback_api.php:line 74'
    ]);
    exit;
}

// Verify decision exists
try {
    $decision = $db->query(
        "SELECT id FROM mdl_mvp_decision_log WHERE id = ? LIMIT 1",
        [$decision_id]
    );

    if (empty($decision)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Decision not found',
            'location' => 'standalone_feedback_api.php:line 89'
        ]);
        exit;
    }
} catch (Exception $e) {
    log_message("Error verifying decision: " . $e->getMessage(), "ERROR");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error while verifying decision',
        'location' => 'standalone_feedback_api.php:line 98'
    ]);
    exit;
}

// Check if feedback already exists for this decision
try {
    $existing = $db->query(
        "SELECT id FROM mdl_mvp_teacher_feedback WHERE decision_id = ? LIMIT 1",
        [$decision_id]
    );

    if (!empty($existing)) {
        // Update existing feedback
        $sql = "
            UPDATE mdl_mvp_teacher_feedback
            SET
                teacher_id = ?,
                response = ?,
                comment = ?,
                timestamp = NOW()
            WHERE decision_id = ?
        ";

        $params = [
            $user['id'],
            $response,
            $comment,
            $decision_id
        ];

        $db->execute($sql, $params);

        log_message("Teacher feedback updated - Decision ID: $decision_id, Response: $response, Teacher: " . $user['username'], "INFO");

        echo json_encode([
            'success' => true,
            'message' => 'Feedback updated successfully',
            'data' => [
                'decision_id' => $decision_id,
                'response' => $response,
                'action' => 'update'
            ]
        ]);

    } else {
        // Insert new feedback
        $sql = "
            INSERT INTO mdl_mvp_teacher_feedback
            (decision_id, teacher_id, response, comment, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ";

        $params = [
            $decision_id,
            $user['id'],
            $response,
            $comment
        ];

        $db->execute($sql, $params);
        $feedback_id = $db->lastInsertId();

        log_message("Teacher feedback created - ID: $feedback_id, Decision ID: $decision_id, Response: $response, Teacher: " . $user['username'], "INFO");

        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => [
                'feedback_id' => $feedback_id,
                'decision_id' => $decision_id,
                'response' => $response,
                'action' => 'insert'
            ]
        ]);
    }

} catch (Exception $e) {
    log_message("Error saving feedback: " . $e->getMessage(), "ERROR");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save feedback: ' . $e->getMessage(),
        'location' => 'standalone_feedback_api.php:line 182'
    ]);
    exit;
}
?>
