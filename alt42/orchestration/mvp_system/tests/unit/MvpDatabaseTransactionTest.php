<?php
// File: tests/unit/MvpDatabaseTransactionTest.php

require_once(__DIR__ . '/../../lib/MvpDatabase.php');
require_once(__DIR__ . '/../../lib/MvpException.php');

/**
 * Test transaction support in MvpDatabase
 */
class MvpDatabaseTransactionTest {

    private $db;
    private $testTable = 'mdl_mvp_test_transactions';

    public function __construct() {
        $this->db = MvpDatabase::getInstance();
    }

    /**
     * Setup test table
     */
    private function setupTestTable() {
        $this->db->connect();

        // Drop table if exists
        $sql = "DROP TABLE IF EXISTS {$this->testTable}";
        $this->db->execute($sql);

        // Create test table with InnoDB (required for transactions)
        $sql = "CREATE TABLE {$this->testTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            value INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->execute($sql);
    }

    /**
     * Cleanup test table
     */
    private function cleanupTestTable() {
        try {
            $sql = "DROP TABLE IF EXISTS {$this->testTable}";
            $this->db->execute($sql);
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }

    /**
     * Test transaction commit persists data
     */
    public function testTransactionCommit() {
        echo "\n[" . __FILE__ . ":" . __LINE__ . "] Running testTransactionCommit...\n";

        try {
            $this->setupTestTable();

            // Start transaction
            $this->db->beginTransaction();

            // Insert data
            $sql = "INSERT INTO {$this->testTable} (name, value) VALUES (?, ?)";
            $this->db->execute($sql, ['test_commit', 100]);

            // Commit transaction
            $this->db->commit();

            // Verify data persisted
            $sql = "SELECT * FROM {$this->testTable} WHERE name = ?";
            $record = $this->db->fetchOne($sql, ['test_commit']);

            if (!$record) {
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction commit failed - no record found");
            }

            if ($record->value != 100) {
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction commit failed - wrong value: " . $record->value);
            }

            // Verify transaction status is false
            if ($this->db->inTransaction()) {
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction status should be false after commit");
            }

            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] PASS: testTransactionCommit\n";
            return true;

        } catch (Exception $e) {
            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] FAIL: testTransactionCommit - " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test transaction rollback discards data
     */
    public function testTransactionRollback() {
        echo "\n[" . __FILE__ . ":" . __LINE__ . "] Running testTransactionRollback...\n";

        try {
            $this->setupTestTable();

            // Start transaction
            $this->db->beginTransaction();

            // Insert data
            $sql = "INSERT INTO {$this->testTable} (name, value) VALUES (?, ?)";
            $this->db->execute($sql, ['test_rollback', 200]);

            // Rollback transaction
            $this->db->rollback();

            // Verify data was discarded
            $sql = "SELECT * FROM {$this->testTable} WHERE name = ?";
            $record = $this->db->fetchOne($sql, ['test_rollback']);

            if ($record) {
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction rollback failed - record still exists");
            }

            // Verify transaction status is false
            if ($this->db->inTransaction()) {
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction status should be false after rollback");
            }

            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] PASS: testTransactionRollback\n";
            return true;

        } catch (Exception $e) {
            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] FAIL: testTransactionRollback - " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test nested transactions are not allowed
     */
    public function testNestedTransactionsNotAllowed() {
        echo "\n[" . __FILE__ . ":" . __LINE__ . "] Running testNestedTransactionsNotAllowed...\n";

        try {
            $this->setupTestTable();

            // Start first transaction
            $this->db->beginTransaction();

            // Try to start nested transaction - should throw exception
            try {
                $this->db->beginTransaction();

                // If we get here, test failed
                $this->db->rollback(); // Cleanup
                $this->cleanupTestTable();
                echo "[" . __FILE__ . ":" . __LINE__ . "] FAIL: testNestedTransactionsNotAllowed - nested transaction was allowed\n";
                return false;

            } catch (MvpQueryException $e) {
                // Expected exception - verify message
                if (strpos($e->getMessage(), 'Transaction already in progress') === false) {
                    throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Wrong exception message: " . $e->getMessage());
                }

                // Verify transaction is still active
                if (!$this->db->inTransaction()) {
                    throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction should still be active after nested attempt");
                }

                // Cleanup
                $this->db->rollback();
                $this->cleanupTestTable();
                echo "[" . __FILE__ . ":" . __LINE__ . "] PASS: testNestedTransactionsNotAllowed\n";
                return true;
            }

        } catch (Exception $e) {
            try {
                if ($this->db->inTransaction()) {
                    $this->db->rollback();
                }
            } catch (Exception $e2) {
                // Ignore cleanup errors
            }
            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] FAIL: testNestedTransactionsNotAllowed - " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test transaction isolation
     * Uncommitted data should not be visible to other connections
     */
    public function testTransactionIsolation() {
        echo "\n[" . __FILE__ . ":" . __LINE__ . "] Running testTransactionIsolation...\n";

        try {
            $this->setupTestTable();

            // Connection 1: Start transaction and insert data
            $this->db->beginTransaction();
            $sql = "INSERT INTO {$this->testTable} (name, value) VALUES (?, ?)";
            $this->db->execute($sql, ['test_isolation', 300]);

            // Connection 2: Create new connection to verify isolation
            $db2 = new MvpDatabase();
            $db2->connect();

            // Try to read uncommitted data
            $sql = "SELECT * FROM {$this->testTable} WHERE name = ?";
            $record = $db2->fetchOne($sql, ['test_isolation']);

            if ($record) {
                // Cleanup
                $this->db->rollback();
                $db2->disconnect();
                $this->cleanupTestTable();
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction isolation failed - uncommitted data visible to other connection");
            }

            // Commit on connection 1
            $this->db->commit();

            // Now connection 2 should see the data
            $record = $db2->fetchOne($sql, ['test_isolation']);

            if (!$record) {
                $db2->disconnect();
                $this->cleanupTestTable();
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction isolation test failed - committed data not visible");
            }

            if ($record->value != 300) {
                $db2->disconnect();
                $this->cleanupTestTable();
                throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Transaction isolation test failed - wrong value: " . $record->value);
            }

            // Cleanup
            $db2->disconnect();
            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] PASS: testTransactionIsolation\n";
            return true;

        } catch (Exception $e) {
            try {
                if ($this->db->inTransaction()) {
                    $this->db->rollback();
                }
            } catch (Exception $e2) {
                // Ignore cleanup errors
            }
            $this->cleanupTestTable();
            echo "[" . __FILE__ . ":" . __LINE__ . "] FAIL: testTransactionIsolation - " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "\n=== MvpDatabase Transaction Tests ===\n";
        echo "[" . __FILE__ . ":" . __LINE__ . "] Starting transaction tests...\n";

        $results = [
            'testTransactionCommit' => $this->testTransactionCommit(),
            'testTransactionRollback' => $this->testTransactionRollback(),
            'testNestedTransactionsNotAllowed' => $this->testNestedTransactionsNotAllowed(),
            'testTransactionIsolation' => $this->testTransactionIsolation()
        ];

        $passed = array_sum($results);
        $total = count($results);

        echo "\n=== Test Summary ===\n";
        echo "[" . __FILE__ . ":" . __LINE__ . "] Tests passed: $passed/$total\n";

        if ($passed === $total) {
            echo "[" . __FILE__ . ":" . __LINE__ . "] All transaction tests PASSED!\n";
            return true;
        } else {
            echo "[" . __FILE__ . ":" . __LINE__ . "] Some transaction tests FAILED!\n";
            return false;
        }
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new MvpDatabaseTransactionTest();
    $success = $tester->runAllTests();
    exit($success ? 0 : 1);
}
?>
