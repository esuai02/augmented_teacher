<?php declare(strict_types=1);
/**
 * PHPUnit Bootstrap File
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define test environment constants
define('MOODLE_INTERNAL', true);
define('DEBUG_MODE', true);

// Set up autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Mock Moodle environment for testing
if (!defined('MOODLE_CONFIG_FILE')) {
    define('MOODLE_CONFIG_FILE', true);
}

// Mock global Moodle variables
global $CFG, $DB, $USER, $SITE, $PAGE, $OUTPUT;

$CFG = new stdClass();
$CFG->wwwroot = 'http://localhost';
$CFG->dataroot = '/tmp/moodle-test';
$CFG->libdir = __DIR__ . '/../../../../lib';
$CFG->directorypermissions = 02777;
$CFG->sessiontimeout = 3600;
$CFG->sesskey = 'test_sesskey_123';

// Mock USER object
$USER = new stdClass();
$USER->id = 1;
$USER->username = 'testuser';
$USER->email = 'test@example.com';

// Mock database functions for testing
class MockDB {
    public function start_delegated_transaction() {
        return new MockTransaction();
    }
    
    public function insert_record($table, $record) {
        return mt_rand(1, 1000);
    }
    
    public function insert_records($table, $records) {
        return true;
    }
    
    public function update_record($table, $record) {
        return true;
    }
    
    public function get_record($table, $conditions) {
        return (object)['id' => 1, 'name' => 'test'];
    }
    
    public function get_records($table, $conditions = null) {
        return [
            1 => (object)['id' => 1, 'name' => 'test1'],
            2 => (object)['id' => 2, 'name' => 'test2']
        ];
    }
}

class MockTransaction {
    public function allow_commit() {
        return true;
    }
    
    public function rollback($exception = null) {
        return true;
    }
}

$DB = new MockDB();

// Mock Moodle functions
if (!function_exists('required_param')) {
    function required_param($paramname, $type) {
        return $_REQUEST[$paramname] ?? null;
    }
}

if (!function_exists('optional_param')) {
    function optional_param($paramname, $default, $type) {
        return $_REQUEST[$paramname] ?? $default;
    }
}

if (!function_exists('clean_param')) {
    function clean_param($param, $type) {
        switch ($type) {
            case 'PARAM_INT':
                return (int)$param;
            case 'PARAM_FLOAT':
                return (float)$param;
            case 'PARAM_TEXT':
                return strip_tags($param);
            default:
                return $param;
        }
    }
}

if (!function_exists('sesskey')) {
    function sesskey() {
        return 'test_sesskey_123';
    }
}

if (!function_exists('require_sesskey')) {
    function require_sesskey() {
        return true;
    }
}

if (!function_exists('s')) {
    function s($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!class_exists('moodle_exception')) {
    class moodle_exception extends Exception {
        public function __construct($errorcode, $module = '', $link = '', $a = null, $debuginfo = null) {
            $message = $debuginfo ?: "Moodle exception: $errorcode";
            parent::__construct($message);
        }
    }
}

if (!class_exists('context_system')) {
    class context_system {
        public static function instance() {
            return new stdClass();
        }
    }
}

// Define parameter type constants
define('PARAM_INT', 'int');
define('PARAM_FLOAT', 'float');
define('PARAM_TEXT', 'text');
define('PARAM_RAW', 'raw');
define('PARAM_BOOL', 'bool');

// Include OmniUI core classes
$coreDir = __DIR__ . '/../../../omniui/spiral/core';
if (is_dir($coreDir)) {
    require_once $coreDir . '/SpiralScheduler.php';
    require_once $coreDir . '/TimeAllocator.php';
    require_once $coreDir . '/RatioCalculator.php';
    require_once $coreDir . '/ConflictResolver.php';
}

// Include plugin classes
$pluginClassesDir = __DIR__ . '/../classes';
if (is_dir($pluginClassesDir)) {
    foreach (glob($pluginClassesDir . '/**/*.php') as $file) {
        require_once $file;
    }
}

// Set up test database if needed
if (getenv('SETUP_TEST_DB') === 'true') {
    setupTestDatabase();
}

function setupTestDatabase() {
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'test_mathking';
    $user = getenv('DB_USER') ?: 'test_user';
    $pass = getenv('DB_PASS') ?: 'test_pass';
    
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test database
        $pdo->exec("DROP DATABASE IF EXISTS $dbname");
        $pdo->exec("CREATE DATABASE $dbname");
        $pdo->exec("USE $dbname");
        
        // Create test tables
        createTestTables($pdo);
        
        echo "Test database setup complete.\n";
        
    } catch (PDOException $e) {
        echo "Test database setup failed: " . $e->getMessage() . "\n";
    }
}

function createTestTables($pdo) {
    // Create minimal test tables
    $tables = [
        'spiral_schedules' => "
            CREATE TABLE spiral_schedules (
                id BIGINT(10) PRIMARY KEY AUTO_INCREMENT,
                teacher_id BIGINT(10) NOT NULL,
                student_id BIGINT(10) NOT NULL,
                schedule_type VARCHAR(50) DEFAULT 'auto',
                status VARCHAR(20) DEFAULT 'draft',
                ratio_preview DECIMAL(3,2) DEFAULT 0.70,
                ratio_review DECIMAL(3,2) DEFAULT 0.30,
                start_date BIGINT(10) NOT NULL,
                end_date BIGINT(10) NOT NULL,
                schedule_data TEXT,
                timecreated BIGINT(10) NOT NULL,
                timemodified BIGINT(10) NOT NULL
            )
        ",
        'spiral_sessions' => "
            CREATE TABLE spiral_sessions (
                id BIGINT(10) PRIMARY KEY AUTO_INCREMENT,
                schedule_id BIGINT(10) NOT NULL,
                session_date BIGINT(10) NOT NULL,
                session_time TIME NOT NULL,
                duration_minutes INT NOT NULL,
                session_type VARCHAR(20) NOT NULL,
                unit_id VARCHAR(100) NOT NULL,
                unit_name VARCHAR(255) NOT NULL,
                difficulty_level INT DEFAULT 3,
                completion_status VARCHAR(20) DEFAULT 'pending',
                timecreated BIGINT(10) NOT NULL,
                timemodified BIGINT(10) NOT NULL
            )
        "
    ];
    
    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
    }
}

echo "PHPUnit bootstrap loaded successfully.\n";