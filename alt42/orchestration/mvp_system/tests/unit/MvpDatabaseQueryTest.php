<?php
// File: tests/unit/MvpDatabaseQueryTest.php

require_once(__DIR__ . '/../../lib/MvpDatabase.php');
require_once(__DIR__ . '/../../lib/MvpConfig.php');
require_once(__DIR__ . '/../../lib/MvpException.php');

class MvpDatabaseQueryTest {

    private $db;
    private $testTable = 'mdl_mvp_test_queries';

    public function __construct() {
        $this->db = MvpDatabase::getInstance();
        $this->db->connect();
        $this->setupTestTable();
    }

    private function setupTestTable() {
        // Create test table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->testTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            value INT NOT NULL,
            created_at BIGINT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->execute($sql);

        // Clear any existing data
        $this->db->execute("TRUNCATE TABLE {$this->testTable}");
    }

    public function testExecuteInsert() {
        $result = $this->db->execute(
            "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
            ['test1', 100, time()]
        );

        assert($result === true, "INSERT should return true");
        assert($this->db->affectedRows() === 1, "Should affect 1 row");

        $insertId = $this->db->lastInsertId();
        assert($insertId > 0, "Should return valid insert ID");

        echo "✓ Execute INSERT test passed (ID: {$insertId})\n";
    }

    public function testFetchOne() {
        // Insert test data
        $this->db->execute(
            "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
            ['fetchone_test', 200, time()]
        );
        $id = $this->db->lastInsertId();

        // Fetch single record
        $record = $this->db->fetchOne(
            "SELECT * FROM {$this->testTable} WHERE id = ?",
            [$id]
        );

        assert($record !== null, "Should return a record");
        assert($record->id == $id, "ID should match");
        assert($record->name === 'fetchone_test', "Name should match");
        assert($record->value == 200, "Value should match");

        echo "✓ FetchOne test passed\n";
    }

    public function testFetchAll() {
        // Insert multiple records
        for ($i = 1; $i <= 5; $i++) {
            $this->db->execute(
                "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
                ["test{$i}", $i * 10, time()]
            );
        }

        // Fetch all records
        $records = $this->db->fetchAll(
            "SELECT * FROM {$this->testTable} WHERE name LIKE ?",
            ['test%']
        );

        assert(is_array($records), "Should return array");
        assert(count($records) >= 5, "Should return at least 5 records");

        echo "✓ FetchAll test passed (" . count($records) . " records)\n";
    }

    public function testExecuteUpdate() {
        // Insert test data
        $this->db->execute(
            "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
            ['update_test', 300, time()]
        );
        $id = $this->db->lastInsertId();

        // Update
        $result = $this->db->execute(
            "UPDATE {$this->testTable} SET value = ? WHERE id = ?",
            [999, $id]
        );

        assert($result === true, "UPDATE should return true");
        assert($this->db->affectedRows() === 1, "Should affect 1 row");

        // Verify update
        $record = $this->db->fetchOne(
            "SELECT value FROM {$this->testTable} WHERE id = ?",
            [$id]
        );
        assert($record->value == 999, "Value should be updated");

        echo "✓ Execute UPDATE test passed\n";
    }

    public function testExecuteDelete() {
        // Insert test data
        $this->db->execute(
            "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
            ['delete_test', 400, time()]
        );
        $id = $this->db->lastInsertId();

        // Delete
        $result = $this->db->execute(
            "DELETE FROM {$this->testTable} WHERE id = ?",
            [$id]
        );

        assert($result === true, "DELETE should return true");
        assert($this->db->affectedRows() === 1, "Should affect 1 row");

        // Verify deletion
        $record = $this->db->fetchOne(
            "SELECT * FROM {$this->testTable} WHERE id = ?",
            [$id]
        );
        assert($record === null, "Record should not exist");

        echo "✓ Execute DELETE test passed\n";
    }

    public function testEscape() {
        $dangerous = "'; DROP TABLE users; --";
        $safe = $this->db->escape($dangerous);

        // Verify single quote is escaped (backslash added)
        assert(strpos($safe, "\\'") !== false, "Should escape single quotes with backslash");

        // Functional test: Verify it's safe to use in query
        $testId = 9999;
        $this->db->execute(
            "INSERT INTO {$this->testTable} (name, value, created_at) VALUES (?, ?, ?)",
            [$dangerous, $testId, time()]
        );

        $record = $this->db->fetchOne(
            "SELECT name FROM {$this->testTable} WHERE value = ?",
            [$testId]
        );
        assert($record->name === $dangerous, "Dangerous string should be safely stored");

        echo "✓ Escape test passed\n";
    }

    public function cleanup() {
        // Drop test table
        $this->db->execute("DROP TABLE IF EXISTS {$this->testTable}");
    }

    public function runAllTests() {
        echo "=== Running MvpDatabase Query Tests ===\n";

        try {
            $this->testExecuteInsert();
            $this->testFetchOne();
            $this->testFetchAll();
            $this->testExecuteUpdate();
            $this->testExecuteDelete();
            $this->testEscape();

            echo "✅ All query tests passed\n\n";
        } finally {
            $this->cleanup();
        }
    }
}

// Run tests
$tester = new MvpDatabaseQueryTest();
$tester->runAllTests();
?>
