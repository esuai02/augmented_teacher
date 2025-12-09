<?php
// File: mvp_system/api/feedback.php (Line 1)
// Mathking Agentic MVP System - Teacher Feedback API
//
// Purpose: Handle teacher feedback submission for intervention decisions
// Method: POST with JSON body
// Input: { decision_id, response, comment }
// Output: { success, feedback_id } or { success: false, error }

// Server connection (NOT local development)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Load MVP system dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

$mvp_db = new MVPDatabase();
$logger = new MVPLogger('feedback_api');

// Set JSON response headers
header('Content-Type: application/json');

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is teacher or admin
if ($role !== 'teacher' && $role !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Only teachers can submit feedback at feedback.php:33'
    ]);
    $logger->error('Unauthorized feedback attempt', [
        'user_id' => $USER->id,
        'role' => $role
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST at feedback.php:46'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input at feedback.php:57'
    ]);
    $logger->error('Invalid JSON input', ['raw_input' => substr($input, 0, 200)]);
    exit;
}

// Validate required fields
$decision_id = $data['decision_id'] ?? null;
$response = $data['response'] ?? null;
$comment = $data['comment'] ?? '';

if (!$decision_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing decision_id at feedback.php:71'
    ]);
    exit;
}

if (!$response) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing response at feedback.php:79'
    ]);
    exit;
}

// Validate response value
$valid_responses = ['approve', 'reject', 'defer'];
if (!in_array($response, $valid_responses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response. Must be approve, reject, or defer at feedback.php:90'
    ]);
    exit;
}

// Log the feedback request
$logger->info('Feedback submission started', [
    'decision_id' => $decision_id,
    'response' => $response,
    'has_comment' => !empty($comment),
    'teacher_id' => $USER->id,
    'teacher_name' => $USER->username
]);

try {
    // Check if decision exists
    $decision = $mvp_db->query(
        "SELECT id, student_id, action, confidence FROM mdl_mvp_decision_log WHERE id = ?",
        [$decision_id]
    );

    if (empty($decision)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Decision not found at feedback.php:116'
        ]);
        $logger->error('Decision not found', ['decision_id' => $decision_id]);
        exit;
    }

    $decision = $decision[0];

    // Check if feedback already exists
    $existing_feedback = $mvp_db->query(
        "SELECT id, response FROM mdl_mvp_teacher_feedback WHERE decision_id = ?",
        [$decision_id]
    );

    if (!empty($existing_feedback)) {
        // Update existing feedback
        $feedback_id = $existing_feedback[0]['id'];
        $previous_response = $existing_feedback[0]['response'];

        $update_result = $mvp_db->execute(
            "UPDATE mdl_mvp_teacher_feedback
             SET response = ?, comment = ?, timestamp = NOW()
             WHERE id = ?",
            [$response, $comment, $feedback_id]
        );

        if (!$update_result) {
            throw new Exception('Failed to update feedback at feedback.php:145');
        }

        $logger->info('Feedback updated', [
            'feedback_id' => $feedback_id,
            'decision_id' => $decision_id,
            'previous_response' => $previous_response,
            'new_response' => $response,
            'teacher_id' => $USER->id
        ]);

        echo json_encode([
            'success' => true,
            'feedback_id' => $feedback_id,
            'action' => 'updated',
            'message' => 'Feedback updated successfully'
        ]);

    } else {
        // Insert new feedback
        $insert_result = $mvp_db->execute(
            "INSERT INTO mdl_mvp_teacher_feedback
             (decision_id, teacher_id, response, comment, timestamp)
             VALUES (?, ?, ?, ?, NOW())",
            [$decision_id, $USER->id, $response, $comment]
        );

        if (!$insert_result) {
            throw new Exception('Failed to insert feedback at feedback.php:176');
        }

        // Get the inserted feedback ID
        $feedback_id = $mvp_db->getLastInsertId();

        $logger->info('Feedback created', [
            'feedback_id' => $feedback_id,
            'decision_id' => $decision_id,
            'response' => $response,
            'teacher_id' => $USER->id,
            'student_id' => $decision['student_id']
        ]);

        echo json_encode([
            'success' => true,
            'feedback_id' => $feedback_id,
            'action' => 'created',
            'message' => 'Feedback submitted successfully'
        ]);
    }

    // Log feedback statistics
    $logger->info('Feedback statistics', [
        'decision_action' => $decision['action'],
        'decision_confidence' => $decision['confidence'],
        'teacher_response' => $response,
        'agreement' => ($response === 'approve'),
        'has_comment' => !empty($comment)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage() . ' at feedback.php:213'
    ]);

    $logger->error('Feedback submission failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'decision_id' => $decision_id,
        'teacher_id' => $USER->id
    ]);
}
?>
