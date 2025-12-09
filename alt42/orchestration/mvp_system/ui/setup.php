<?php
// File: mvp_system/ui/setup.php
// Deployment Setup and Configuration Tool
//
// Purpose: Automate deployment setup and fix common issues
// Access this once after deployment to configure the system
// Error Location: /mvp_system/ui/setup.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Standalone System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        .step { background: #f9f9f9; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #5568d3; }
        ul { line-height: 1.8; }
        hr { border: none; border-top: 2px solid #eee; margin: 30px 0; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Standalone Teacher Panel Setup</h1>";
echo "<p><strong>System Path:</strong> " . __DIR__ . "</p>";
echo "<hr>";

// Step 1: Check PHP Version
echo "<div class='step'>";
echo "<h2>Step 1: PHP Version Check</h2>";
$php_version = phpversion();
echo "<p>Current PHP Version: <strong>$php_version</strong></p>";
if (version_compare($php_version, '7.1.9', '>=')) {
    echo "<p class='success'>✅ PHP version is compatible</p>";
} else {
    echo "<p class='error'>❌ PHP version must be 7.1.9 or higher</p>";
}
echo "</div>";

// Step 2: Create Log Directory
echo "<div class='step'>";
echo "<h2>Step 2: Log Directory Setup</h2>";
$mvp_root = dirname(__DIR__);
$log_path = $mvp_root . '/logs';
echo "<p>Log Directory Path: <code>$log_path</code></p>";

if (!is_dir($log_path)) {
    echo "<p class='warning'>⚠️ Log directory does not exist. Creating...</p>";
    if (@mkdir($log_path, 0755, true)) {
        echo "<p class='success'>✅ Log directory created successfully</p>";
    } else {
        echo "<p class='error'>❌ Failed to create log directory. Please create manually:</p>";
        echo "<pre>mkdir -p $log_path\nchmod 755 $log_path</pre>";
    }
} else {
    echo "<p class='success'>✅ Log directory exists</p>";
}

// Check writability
if (is_writable($log_path)) {
    echo "<p class='success'>✅ Log directory is writable</p>";
} else {
    echo "<p class='error'>❌ Log directory is not writable. Please fix permissions:</p>";
    echo "<pre>chmod 755 $log_path</pre>";
}
echo "</div>";

// Step 3: Test Session
echo "<div class='step'>";
echo "<h2>Step 3: Session Test</h2>";
try {
    session_name('mvp_teacher_session');
    @session_start();
    $_SESSION['test'] = 'working';
    echo "<p class='success'>✅ Session system is working</p>";
    echo "<p>Session ID: <code>" . session_id() . "</code></p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Session initialization failed: " . $e->getMessage() . "</p>";
    echo "<p>Check session directory permissions</p>";
}
echo "</div>";

// Step 4: Database Configuration Check
echo "<div class='step'>";
echo "<h2>Step 4: Database Configuration</h2>";
$config_file = __DIR__ . '/standalone_config.php';
$config_safe_file = __DIR__ . '/standalone_config_safe.php';

echo "<p>Checking configuration files:</p>";
echo "<ul>";
echo "<li>Original: <code>standalone_config.php</code> - ";
echo file_exists($config_file) ? "<span class='success'>✅ Exists</span>" : "<span class='error'>❌ Missing</span>";
echo "</li>";
echo "<li>Safe Version: <code>standalone_config_safe.php</code> - ";
echo file_exists($config_safe_file) ? "<span class='success'>✅ Exists</span>" : "<span class='error'>❌ Missing</span>";
echo "</li>";
echo "</ul>";

echo "<h3>⚠️ IMPORTANT: Update Database Password</h3>";
echo "<p>Edit <code>standalone_config_safe.php</code> and update line 12:</p>";
echo "<pre>define('DB_PASS', 'your_actual_database_password');</pre>";
echo "<p class='warning'>⚠️ The system will NOT work until you update the database password</p>";
echo "</div>";

// Step 5: Test Database Connection (only if password updated)
echo "<div class='step'>";
echo "<h2>Step 5: Database Connection Test</h2>";

// Check if safe config exists
if (file_exists($config_safe_file)) {
    require_once($config_safe_file);

    echo "<p>Database Configuration:</p>";
    echo "<ul>";
    echo "<li>Host: <code>" . DB_HOST . "</code></li>";
    echo "<li>Database: <code>" . DB_NAME . "</code></li>";
    echo "<li>User: <code>" . DB_USER . "</code></li>";
    echo "<li>Password: ";

    if (DB_PASS === 'your_password_here') {
        echo "<span class='error'>❌ NOT SET (still placeholder)</span></li>";
        echo "</ul>";
        echo "<p class='error'>❌ Cannot test database connection until password is updated</p>";
    } else {
        echo "<span class='success'>✅ Set</span></li>";
        echo "</ul>";

        // Try database connection
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            echo "<p class='success'>✅ Database connection successful</p>";

            // Test tables
            $tables = ['mdl_user', 'mdl_user_info_data', 'mdl_mvp_decision_log', 'mdl_mvp_teacher_feedback'];
            echo "<p>Testing table access:</p><ul>";
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
                    $result = $stmt->fetch();
                    echo "<li>$table: <span class='success'>✅ Accessible ({$result['cnt']} rows)</span></li>";
                } catch (PDOException $e) {
                    echo "<li>$table: <span class='error'>❌ Error - " . $e->getMessage() . "</span></li>";
                }
            }
            echo "</ul>";

        } catch (PDOException $e) {
            echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p class='error'>❌ standalone_config_safe.php not found</p>";
}
echo "</div>";

// Step 6: File Replacement Instructions
echo "<div class='step'>";
echo "<h2>Step 6: Activation Instructions</h2>";
echo "<p>After updating the database password in <code>standalone_config_safe.php</code>:</p>";
echo "<ol>";
echo "<li>Backup the original config file:
<pre>mv standalone_config.php standalone_config.backup.php</pre>
</li>";
echo "<li>Activate the safe config file:
<pre>mv standalone_config_safe.php standalone_config.php</pre>
</li>";
echo "<li>Test the login page:
<a href='standalone_login.php' class='btn' target='_blank'>Test Login Page</a>
</li>";
echo "</ol>";
echo "</div>";

// Step 7: Next Steps
echo "<hr>";
echo "<h2>📋 Complete Setup Checklist</h2>";
echo "<ol>";
echo "<li>✅ Run this setup.php script</li>";
echo "<li>⚠️ Edit <code>standalone_config_safe.php</code> and update database password (line 12)</li>";
echo "<li>⚠️ Backup original: <code>mv standalone_config.php standalone_config.backup.php</code></li>";
echo "<li>⚠️ Activate safe version: <code>mv standalone_config_safe.php standalone_config.php</code></li>";
echo "<li>⚠️ Test login page: <a href='standalone_login.php'>standalone_login.php</a></li>";
echo "<li>⚠️ Delete this setup.php file for security</li>";
echo "</ol>";

echo "<hr>";
echo "<p class='info'>💡 If you still encounter HTTP 500 errors after setup, run the diagnostic tool:</p>";
echo "<a href='diagnose.php' class='btn'>Run Diagnostic Tool</a>";

echo "</div></body></html>";
?>
