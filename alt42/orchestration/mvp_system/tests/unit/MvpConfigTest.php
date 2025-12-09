<?php
// File: tests/unit/MvpConfigTest.php

require_once(__DIR__ . '/../../lib/MvpConfig.php');

class MvpConfigTest {

    public function testGetDatabaseConfig() {
        $config = MvpConfig::getDatabaseConfig();

        // Verify required keys exist
        assert(isset($config['host']), "Missing 'host' in config");
        assert(isset($config['name']), "Missing 'name' in config");
        assert(isset($config['user']), "Missing 'user' in config");
        assert(isset($config['pass']), "Missing 'pass' in config");
        assert(isset($config['prefix']), "Missing 'prefix' in config");
        assert(isset($config['charset']), "Missing 'charset' in config");

        // Verify types
        assert(is_string($config['host']));
        assert(is_string($config['name']));
        assert(is_string($config['user']));
        assert(is_string($config['prefix']));

        // Verify charset default
        assert($config['charset'] === 'utf8mb4');

        // Verify password exists but don't output it (security)
        assert(!empty($config['pass']), "Password should not be empty");

        echo "✓ Database config structure test passed\n";
        echo "  Host: {$config['host']}\n";
        echo "  Database: {$config['name']}\n";
        echo "  Prefix: {$config['prefix']}\n";
    }

    public function testMoodleConfigPath() {
        $configPath = MvpConfig::getMoodleConfigPath();

        assert(file_exists($configPath), "Moodle config.php not found at: $configPath");
        echo "✓ Moodle config path test passed\n";
        echo "  Path: {$configPath}\n";
    }

    public function testValidateConfigSuccess() {
        $validConfig = [
            'host' => '58.180.27.46',
            'name' => 'mathking',
            'user' => 'testuser',
            'pass' => 'testpass',
            'prefix' => 'mdl_'
        ];

        $result = MvpConfig::validateConfig($validConfig);
        assert($result === true, "Valid config should return true");
        echo "✓ Valid config validation test passed\n";
    }

    public function testValidateConfigMissingKeys() {
        $testCases = [
            'missing host' => ['name' => 'db', 'user' => 'u', 'pass' => 'p', 'prefix' => 'mdl_'],
            'missing name' => ['host' => 'localhost', 'user' => 'u', 'pass' => 'p', 'prefix' => 'mdl_'],
            'missing user' => ['host' => 'localhost', 'name' => 'db', 'pass' => 'p', 'prefix' => 'mdl_'],
            'missing pass' => ['host' => 'localhost', 'name' => 'db', 'user' => 'u', 'prefix' => 'mdl_'],
            'missing prefix' => ['host' => 'localhost', 'name' => 'db', 'user' => 'u', 'pass' => 'p']
        ];

        foreach ($testCases as $testName => $config) {
            $exceptionThrown = false;
            try {
                MvpConfig::validateConfig($config);
            } catch (Exception $e) {
                $exceptionThrown = true;
                assert(strpos($e->getMessage(), 'missing required key') !== false,
                       "Exception message should mention missing key for: $testName");
            }
            assert($exceptionThrown, "Should throw exception for: $testName");
        }
        echo "✓ Invalid config validation test passed (5 cases)\n";
    }

    public function runAllTests() {
        echo "=== Running MvpConfig Tests ===\n";
        $this->testMoodleConfigPath();
        $this->testGetDatabaseConfig();
        $this->testValidateConfigSuccess();
        $this->testValidateConfigMissingKeys();
        echo "✅ All MvpConfig tests passed (4 tests)\n\n";
    }
}

// Run tests
$tester = new MvpConfigTest();
$tester->runAllTests();
?>
