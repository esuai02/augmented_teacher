<?php
/**
 * Direct AJAX Test - bypasses require_login for testing
 */

// Comment out require_login for testing
define('NO_MOODLE_COOKIES', true);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Set up fake user for testing
$USER = new stdClass();
$USER->id = 2;
$USER->firstname = 'Test';
$USER->lastname = 'User';

header('Content-Type: application/json; charset=utf-8');

// Test GET request
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'AJAX endpoint is working',
        'test' => $_GET['test'],
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// If no test parameter, show info
echo json_encode([
    'success' => true,
    'message' => 'AJAX endpoint test ready',
    'endpoints' => [
        'get_review' => 'direct_ajax_test.php?test=get_review',
        'submit_review' => 'direct_ajax_test.php?test=submit_review'
    ]
], JSON_UNESCAPED_UNICODE);
