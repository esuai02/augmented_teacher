<?php
// File: mvp_system/ui/diagnose.php
// Diagnostic Tool for Standalone System
// Error Location: /mvp_system/ui/diagnose.php

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Standalone System Diagnostic</h1>";
echo "<hr>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Required: 7.1.9 or higher<br>";
echo phpversion() >= '7.1.9' ? "✅ PASS<br>" : "❌ FAIL<br>";
echo "<hr>";

// Test 2: File Paths
echo "<h2>2. File Paths</h2>";
$current_dir = __DIR__;
echo "Current Directory: $current_dir<br>";

$mvp_root = dirname(__DIR__);
echo "MVP Root: $mvp_root<br>";

$log_path = $mvp_root . '/logs';
echo "Log Path: $log_path<br>";
echo "Log Directory Exists: " . (is_dir($log_path) ? "✅ YES" : "❌ NO") . "<br>";
echo "Log Directory Writable: " . (is_writable($log_path) ? "✅ YES" : "❌ NO") . "<br>";
echo "<hr>";

// Test 3: Required Files
echo "<h2>3. Required Files</h2>";
$files = [
    'standalone_config.php',
    'standalone_database.php',
    'standalone_login.php',
    'standalone_teacher_panel.php',
    'standalone_feedback_api.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $readable = is_readable($path);

    echo "$file: ";
    echo $exists ? "✅ Exists " : "❌ Missing ";
    echo $readable ? "✅ Readable<br>" : "❌ Not Readable<br>";
}
echo "<hr>";

// Test 4: Session Test
echo "<h2>4. Session Test</h2>";
try {
    session_name('mvp_teacher_session');
    session_start();
    $_SESSION['test'] = 'working';
    echo "Session Start: ✅ SUCCESS<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "Session Start: ❌ FAILED<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 5: Database Connection Test
echo "<h2>5. Database Connection Test</h2>";

// Database credentials
$db_host = 'localhost';
$db_name = 'mathking';
$db_user = 'moodle';
$db_pass = 'your_password_here'; // UPDATE THIS

echo "Host: $db_host<br>";
echo "Database: $db_name<br>";
echo "User: $db_user<br>";
echo "Password: " . ($db_pass === 'your_password_here' ? "❌ NOT SET (UPDATE REQUIRED)" : "✅ SET") . "<br><br>";

if ($db_pass !== 'your_password_here') {
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo "Database Connection: ✅ SUCCESS<br>";

        // Test query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "MySQL Version: " . $result['version'] . "<br>";

        // Test table access
        $tables = [
            'mdl_user',
            'mdl_user_info_data',
            'mdl_mvp_decision_log',
            'mdl_mvp_teacher_feedback'
        ];

        echo "<br><strong>Table Access Test:</strong><br>";
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
                $result = $stmt->fetch();
                echo "$table: ✅ Accessible (Rows: {$result['cnt']})<br>";
            } catch (PDOException $e) {
                echo "$table: ❌ Error - " . $e->getMessage() . "<br>";
            }
        }

    } catch (PDOException $e) {
        echo "Database Connection: ❌ FAILED<br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<strong style='color: red;'>⚠️ Please update database password in diagnose.php first!</strong><br>";
}

echo "<hr>";

// Test 6: Log File Write Test
echo "<h2>6. Log File Write Test</h2>";
if (is_dir($log_path) && is_writable($log_path)) {
    $test_log = $log_path . '/test.log';
    $test_content = "[" . date('Y-m-d H:i:s') . "] Test log entry\n";

    if (file_put_contents($test_log, $test_content, FILE_APPEND)) {
        echo "Log Write Test: ✅ SUCCESS<br>";
        echo "Test log created at: $test_log<br>";
    } else {
        echo "Log Write Test: ❌ FAILED<br>";
    }
} else {
    echo "Log Write Test: ❌ SKIPPED (directory not writable)<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If log directory doesn't exist, create it: <code>mkdir -p " . $log_path . "</code></li>";
echo "<li>Set proper permissions: <code>chmod 755 " . $log_path . "</code></li>";
echo "<li>Update database password in standalone_config.php</li>";
echo "<li>After fixing issues, try accessing standalone_login.php again</li>";
echo "</ol>";
?>
