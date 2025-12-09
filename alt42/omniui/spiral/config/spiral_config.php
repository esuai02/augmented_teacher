<?php
/**
 * Spiral Scheduler Configuration
 * 
 * @package    OmniUI
 * @subpackage spiral
 * @copyright  2024 MathKing
 */

// Include main config
require_once(__DIR__ . '/../../config.php');

// Spiral Scheduler Settings
define('SPIRAL_VERSION', '1.0.0');
define('SPIRAL_DEBUG', DEBUG_MODE);

// Default Ratios
define('SPIRAL_DEFAULT_PREVIEW_RATIO', 0.70);
define('SPIRAL_DEFAULT_REVIEW_RATIO', 0.30);

// Time Constraints
define('SPIRAL_MIN_SESSION_MINUTES', 20);
define('SPIRAL_MAX_SESSION_MINUTES', 50);
define('SPIRAL_BREAK_MINUTES', 10);

// Daily Study Limits by Grade Level
$SPIRAL_DAILY_LIMITS = [
    'elementary' => 90,   // 초등: 90분/일
    'middle' => 120,      // 중등: 120분/일
    'high' => 150        // 고등: 150분/일
];

// Subject Time Allocation
$SPIRAL_SUBJECT_ALLOCATION = [
    'math' => 0.4,       // 수학 40%
    'korean' => 0.3,     // 국어 30%
    'english' => 0.3     // 영어 30%
];

// Difficulty Weights
$SPIRAL_DIFFICULTY_WEIGHTS = [
    1 => 0.6,  // Very Easy
    2 => 0.8,  // Easy
    3 => 1.0,  // Normal
    4 => 1.2,  // Hard
    5 => 1.5   // Very Hard
];

// Conflict Types
define('CONFLICT_TIME_OVERLAP', 'TIME_OVERLAP');
define('CONFLICT_PREREQUISITE', 'PREREQUISITE');
define('CONFLICT_COGNITIVE_LOAD', 'COGNITIVE_LOAD');
define('CONFLICT_PHYSICAL_LIMIT', 'PHYSICAL_LIMIT');

// Schedule Status
define('SCHEDULE_STATUS_DRAFT', 'draft');
define('SCHEDULE_STATUS_PUBLISHED', 'published');
define('SCHEDULE_STATUS_ACTIVE', 'active');
define('SCHEDULE_STATUS_COMPLETED', 'completed');

// Session Status
define('SESSION_STATUS_PENDING', 'pending');
define('SESSION_STATUS_STARTED', 'started');
define('SESSION_STATUS_COMPLETED', 'completed');
define('SESSION_STATUS_SKIPPED', 'skipped');

// Preview/Review Windows
define('SPIRAL_PREVIEW_WINDOW_DAYS', 14);  // Start preview 14 days before exam
define('SPIRAL_REVIEW_WINDOW_DAYS', 7);    // Start intensive review 7 days before exam

// Performance Thresholds
define('SPIRAL_MIN_COMPLETION_RATE', 0.85);  // 85% minimum completion
define('SPIRAL_TARGET_PERFORMANCE', 0.75);   // 75% target score

// Database Connection Helper
function get_spiral_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . MATHKING_DB_HOST . 
                   ";dbname=" . MATHKING_DB_NAME . 
                   ";charset=utf8mb4";
            $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Spiral DB connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $pdo;
}

// Utility Functions
function spiral_log($message, $level = 'info') {
    if (SPIRAL_DEBUG) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        error_log($logEntry, 3, __DIR__ . '/../../logs/spiral.log');
    }
}

function spiral_json_response($data, $success = true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function spiral_error_response($message, $code = 400) {
    http_response_code($code);
    spiral_json_response(['error' => $message], false);
}