<?php
// Test script for mynote1.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing mynote1.php Configuration</h2>";

// Check for Moodle config
$config_paths = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../moodle/config.php',
    __DIR__ . '/../../moodle/config.php',
    '/var/www/html/moodle/config.php',
    '/home/moodle/public_html/moodle/config.php'
];

echo "<h3>1. Checking for Moodle config files:</h3>";
echo "<ul>";
$moodle_found = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "<li style='color:green'>✓ Found: $path</li>";
        $moodle_found = true;
    } else {
        echo "<li style='color:red'>✗ Not found: $path</li>";
    }
}
echo "</ul>";

// Check for alternative config
echo "<h3>2. Checking for alternative config:</h3>";
$alt_config = __DIR__ . '/../alt42/config.php';
if (file_exists($alt_config)) {
    echo "<p style='color:green'>✓ Alternative config found: $alt_config</p>";
} else {
    echo "<p style='color:red'>✗ Alternative config not found: $alt_config</p>";
}

// Check PHP configuration
echo "<h3>3. PHP Configuration:</h3>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✓ Enabled' : '✗ Disabled') . "</li>";
echo "<li>Memory Limit: " . ini_get('memory_limit') . "</li>";
echo "<li>Max Execution Time: " . ini_get('max_execution_time') . " seconds</li>";
echo "</ul>";

// Check required files
echo "<h3>4. Checking required files:</h3>";
$required_files = [
    'check_status.php',
    'uploadimage.php',
    'generate_narration.php',
    'openai_tts_pmemory.php'
];

echo "<ul>";
foreach ($required_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        echo "<li style='color:green'>✓ Found: $file</li>";
    } else {
        echo "<li style='color:orange'>⚠ Warning - Not found: $file (might cause issues with some features)</li>";
    }
}
echo "</ul>";

// Test database connection if not using Moodle
if (!$moodle_found) {
    echo "<h3>5. Testing standalone database connection:</h3>";

    $host = 'localhost';
    $dbname = 'moodle';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green'>✓ Database connection successful</p>";

        // Test query
        $stmt = $pdo->query("SHOW TABLES LIKE 'mdl_%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Found " . count($tables) . " Moodle tables in database</p>";

    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:orange'>⚠ You need to configure database settings in mynote1.php</p>";
    }
}

echo "<h3>6. Test Links:</h3>";
echo "<p>Test mynote1.php with sample parameters:</p>";
echo "<ul>";
echo "<li><a href='mynote1.php?cid=1&nch=1&cmid=1&dmn=test&page=1&studentid=1'>Test with minimal parameters</a></li>";
echo "<li><a href='mynote1.php?cid=1&nch=1&cmid=1&dmn=test&page=1&pgtype=quiz&quizid=1&studentid=1'>Test with quiz parameters</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Summary:</strong> ";
if ($moodle_found) {
    echo "Moodle configuration found. The page should work if Moodle is properly configured.";
} else {
    echo "Moodle configuration not found. Using standalone mode. Make sure to configure database settings.";
}
echo "</p>";
?>