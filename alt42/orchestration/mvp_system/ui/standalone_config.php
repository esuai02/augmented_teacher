<?php
// File: mvp_system/ui/standalone_config.php
// Standalone Configuration (No Moodle Dependency)
//
// Purpose: Database and system configuration for standalone teacher panel
// Error Location: /mvp_system/ui/standalone_config.php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mathking');
define('DB_USER', 'moodle');
define('DB_PASS', 'your_password_here'); // IMPORTANT: Update this with actual password
define('DB_CHARSET', 'utf8mb4');

// Table Prefix
define('TABLE_PREFIX', 'mdl_mvp_');

// Session Configuration
define('SESSION_NAME', 'mvp_teacher_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// System Paths
define('MVP_ROOT', dirname(__DIR__));
define('LOG_PATH', MVP_ROOT . '/logs');

// Base URL
define('BASE_URL', 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/ui');

// Timezone
date_default_timezone_set('Asia/Seoul');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Disable for production
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/standalone_errors.log');

// Start session
session_name(SESSION_NAME);
session_start();

/**
 * Simple authentication check
 * @return bool True if authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user is NOT student
 * @return bool True if user can access (not student)
 */
function can_access_teacher_panel() {
    if (!is_authenticated()) {
        return false;
    }

    $role = $_SESSION['user_role'] ?? '';
    return ($role !== 'student');
}

/**
 * Get current user info
 * @return array User information
 */
function get_current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? 'guest',
        'firstname' => $_SESSION['firstname'] ?? '',
        'lastname' => $_SESSION['lastname'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'guest'
    ];
}

/**
 * Require authentication or redirect to login
 */
function require_auth() {
    if (!is_authenticated()) {
        header('Location: standalone_login.php');
        exit;
    }
}

/**
 * Require teacher access or show error
 */
function require_teacher_access() {
    require_auth();

    if (!can_access_teacher_panel()) {
        http_response_code(403);
        echo "<h1>Access Denied</h1>";
        echo "<p>This page is not accessible to students.</p>";
        echo "<p>Error Location: standalone_config.php:line " . __LINE__ . "</p>";
        exit;
    }
}

/**
 * Log message to file
 * @param string $message Log message
 * @param string $level Log level (INFO, WARNING, ERROR)
 */
function log_message($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";

    $log_file = LOG_PATH . '/standalone_teacher_ui.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>
