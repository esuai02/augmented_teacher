<?php
/**
 * Generate Onboarding Report (GPT)
 * Endpoint: /moodle/local/augmented_teacher/alt42/orchestration7/api/generate_onboarding_report.php
 * Method: POST (preferred) or GET (fallback)
 * Params: userid (required)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $CFG, $DB, $USER, $PAGE;

header('Content-Type: application/json; charset=utf-8');

try {
    // Resolve userid early (before enforcing login)
    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 0);

    // Allow guest access when userid is explicitly provided (align with onboarding_info.php behavior)
    if ($userid > 0) {
        if (function_exists('context_system')) {
            $PAGE->set_context(context_system::instance());
        }
        if (isset($CFG)) {
            $PAGE->set_url('/local/augmented_teacher/alt42/orchestration7/api/generate_onboarding_report.php');
        }
    } else {
        // Fallback to current user (requires login)
        require_login();
        $userid = isset($USER->id) ? intval($USER->id) : 0;
    }
    if ($userid <= 0) {
        throw new Exception('Invalid or missing userid');
    }

    // If action is provided, delegate to shared handler (enables DB save)
    if (isset($_POST['action']) || isset($_GET['action'])) {
        if (!defined('ALT42_ALLOW_GUEST')) {
            define('ALT42_ALLOW_GUEST', true);
        }
        // Prevent report_service.php from intercepting the POST
        if (!defined('ALT42_SERVICE_DISABLE_DIRECT_ACTION')) {
            define('ALT42_SERVICE_DISABLE_DIRECT_ACTION', true);
        }
        // Ensure userid is forwarded
        if (!isset($_POST['userid']) && !isset($_GET['userid'])) {
            $_POST['userid'] = $userid;
        }
        require_once __DIR__ . '/../agents/agent01_onboarding/report_generator.php';
        // report_generator.php will handle the response and exit
        exit;
    }

    // No action: generate via GPT and return preview (no save)
    require_once __DIR__ . '/../agents/agent01_onboarding/report_generator.php';
    $gpt = generateReportWithGPT($userid);
    if (!$gpt['success']) {
        echo json_encode([
            'success' => false,
            'error' => $gpt['error'] ?? 'GPT generation failed'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'success' => true,
        'reportHTML' => $gpt['reportHTML'],
        'reportType' => 'gpt-preview'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}


