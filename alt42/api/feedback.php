<?php
/**
 * POST /api/feedback
 * Records student feedback for educational content
 * File: alt42/api/feedback.php
 */

// Load API configuration
require_once(__DIR__ . '/config.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', 405, __FILE__, __LINE__);
}

try {
    // Get JSON input
    $data = getJsonInput();

    // Log the request
    logRequest('feedback', 'POST', $data);

    // Validate required fields
    validateFields($data, ['studentId', 'contentId', 'feedback', 'reactionTime']);

    // Extract data
    $studentId = $data['studentId'];
    $contentId = $data['contentId'];
    $feedback = $data['feedback'];
    $reactionTime = floatval($data['reactionTime']);

    // Validate data types
    if (!is_numeric($reactionTime)) {
        sendError('reactionTime must be a number', 400, __FILE__, __LINE__);
    }

    // TODO: Replace with actual database insertion
    // For now, just log the feedback
    $feedbackLog = [
        'studentId' => $studentId,
        'contentId' => $contentId,
        'feedback' => $feedback,
        'reactionTime' => $reactionTime,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // In production, save to database:
    // $db->insert('student_feedback', $feedbackLog);

    // Return successful response
    sendResponse([
        'success' => true,
        'message' => 'Feedback recorded successfully',
        'feedbackId' => uniqid('fb_'), // Mock feedback ID
        'timestamp' => date('Y-m-d H:i:s')
    ], 200);

} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500, __FILE__, __LINE__);
}
