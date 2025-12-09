<?php
// File: mvp_system/ui/standalone_database.php
// Standalone Database Class (No Moodle Dependency)
//
// Purpose: PDO-based database connection for standalone teacher panel
// Error Location: /mvp_system/ui/standalone_database.php

require_once(__DIR__ . '/standalone_config.php');

/**
 * Standalone Database Class using PDO
 */
class StandaloneDB {
    private $pdo;
    private $table_prefix;

    /**
     * Constructor - Initialize PDO connection
     */
    public function __construct() {
        $this->table_prefix = TABLE_PREFIX;

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            log_message("Database connection established", "INFO");

        } catch (PDOException $e) {
            $error_msg = "Database connection failed at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage();
            log_message($error_msg, "ERROR");
            throw new Exception($error_msg);
        }
    }

    /**
     * Execute SELECT query and return results
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            $error_msg = "Query error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage();
            log_message($error_msg, "ERROR");
            throw new Exception($error_msg);
        }
    }

    /**
     * Execute INSERT, UPDATE, DELETE query
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return bool Success
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            log_message("Query executed successfully", "INFO");
            return $result;

        } catch (PDOException $e) {
            $error_msg = "Execute error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage();
            log_message($error_msg, "ERROR");
            throw new Exception($error_msg);
        }
    }

    /**
     * Get last insert ID
     * @return int Last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->pdo->rollBack();
    }

    /**
     * Get PDO instance
     * @return PDO PDO instance
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Escape and quote string for SQL
     * @param string $value Value to quote
     * @return string Quoted value
     */
    public function quote($value) {
        return $this->pdo->quote($value);
    }
}
?>
