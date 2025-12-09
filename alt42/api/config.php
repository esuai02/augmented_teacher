<?php
/**
 * API Configuration
 * File: alt42/api/config.php
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Moodle integration (minimal for now)
// require_once(__DIR__ . '/../../../config.php');
// require_login();

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 400, $file = null, $line = null) {
    $error = [
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($file && $line) {
        $error['debug'] = [
            'file' => $file,
            'line' => $line
        ];
    }

    sendResponse($error, $statusCode);
}

/**
 * Get POST body as JSON
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON input: ' . json_last_error_msg(), 400, __FILE__, __LINE__);
    }

    return $data;
}

/**
 * Validate required fields
 */
function validateFields($data, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            sendError("Missing required field: {$field}", 400, __FILE__, __LINE__);
        }
    }
}

/**
 * Log API request (for debugging)
 */
function logRequest($endpoint, $method, $data = null) {
    $logFile = __DIR__ . '/../logs/api.log';
    $logDir = dirname($logFile);

    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logEntry = sprintf(
        "[%s] %s %s | Data: %s\n",
        date('Y-m-d H:i:s'),
        $method,
        $endpoint,
        $data ? json_encode($data) : 'none'
    );

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
