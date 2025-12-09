<?php
// File: test_config_web.php
// Web-accessible test for MvpConfig class

header('Content-Type: text/plain; charset=utf-8');

require_once(__DIR__ . '/lib/MvpConfig.php');

echo "=== Testing MvpConfig via Web ===\n\n";

// Test 1: Get Moodle Config Path
try {
    $configPath = MvpConfig::getMoodleConfigPath();
    echo "✓ Test 1: getMoodleConfigPath()\n";
    echo "  Path: {$configPath}\n";
    echo "  Exists: " . (file_exists($configPath) ? "Yes" : "No") . "\n\n";
} catch (Exception $e) {
    echo "✗ Test 1 failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Get Database Config
try {
    $config = MvpConfig::getDatabaseConfig();
    echo "✓ Test 2: getDatabaseConfig()\n";
    echo "  Host: {$config['host']}\n";
    echo "  Database: {$config['name']}\n";
    echo "  User: {$config['user']}\n";
    echo "  Prefix: {$config['prefix']}\n";
    echo "  Charset: {$config['charset']}\n";
    echo "  Collation: {$config['collation']}\n\n";

    // Test 3: Validate Config
    echo "✓ Test 3: validateConfig()\n";
    $isValid = MvpConfig::validateConfig($config);
    echo "  Valid: " . ($isValid ? "Yes" : "No") . "\n\n";

} catch (Exception $e) {
    echo "✗ Tests 2/3 failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Validate Invalid Config (should throw exception)
try {
    $invalidConfig = ['host' => 'localhost'];
    MvpConfig::validateConfig($invalidConfig);
    echo "✗ Test 4 failed: Should have thrown exception for invalid config\n\n";
} catch (Exception $e) {
    echo "✓ Test 4: validateConfig() properly rejects invalid config\n";
    echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "✅ All MvpConfig web tests completed\n";
?>
