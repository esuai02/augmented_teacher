<?php
// File: tests/unit/MvpDatabaseConnectionTest.php

require_once(__DIR__ . '/../../lib/MvpDatabase.php');
require_once(__DIR__ . '/../../lib/MvpConfig.php');
require_once(__DIR__ . '/../../lib/MvpException.php');

class MvpDatabaseConnectionTest {

    public function testGetInstance() {
        $db1 = MvpDatabase::getInstance();
        $db2 = MvpDatabase::getInstance();

        // Singleton pattern - same instance
        assert($db1 === $db2, "getInstance() should return same instance");
        echo "✓ Singleton pattern test passed\n";
    }

    public function testConnect() {
        $db = MvpDatabase::getInstance();
        $db->connect();

        assert($db->isConnected(), "Database should be connected");
        echo "✓ Connection test passed\n";
    }

    public function testDisconnect() {
        $db = MvpDatabase::getInstance();
        $db->connect();
        $db->disconnect();

        assert(!$db->isConnected(), "Database should be disconnected");
        echo "✓ Disconnection test passed\n";
    }

    public function testGetServerInfo() {
        $db = MvpDatabase::getInstance();
        $db->connect();

        $info = $db->getServerInfo();
        assert(!empty($info), "Server info should not be empty");
        assert(strpos($info, '5.7') !== false, "Should be MySQL 5.7");

        echo "✓ Server info test passed\n";
        echo "  MySQL Version: {$info}\n";
    }

    public function testGetTablePrefix() {
        $db = MvpDatabase::getInstance();

        $prefix = $db->getTablePrefix();
        assert($prefix === 'mdl_', "Table prefix should be 'mdl_'");

        echo "✓ Table prefix test passed\n";
    }

    public function runAllTests() {
        echo "=== Running MvpDatabase Connection Tests ===\n";
        $this->testGetInstance();
        $this->testConnect();
        $this->testDisconnect();
        $this->testGetServerInfo();
        $this->testGetTablePrefix();
        echo "✅ All connection tests passed (5 tests)\n\n";
    }
}

// Run tests
$tester = new MvpDatabaseConnectionTest();
$tester->runAllTests();
?>
