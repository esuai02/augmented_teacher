<?php
// File: lib/MvpDatabase.php

require_once(__DIR__ . '/MvpConfig.php');
require_once(__DIR__ . '/MvpException.php');

/**
 * MVP Database Wrapper - Direct MySQLi connection
 * Bypasses Moodle $DB cache completely
 */
class MvpDatabase {

    /** @var MvpDatabase Singleton instance */
    private static $instance = null;

    /** @var mysqli Database connection */
    private $mysqli = null;

    /** @var array Database configuration */
    private $config = [];

    /** @var bool Connection status */
    private $connected = false;

    /** @var int Maximum reconnection attempts */
    private $maxReconnectAttempts = 3;

    /** @var bool Transaction status */
    private $inTransaction = false;

    /** @var int Last affected rows count */
    private $lastAffectedRows = 0;

    /**
     * Constructor - can create new instance for testing
     */
    public function __construct() {
        $this->config = MvpConfig::getDatabaseConfig();
    }

    /**
     * Get singleton instance
     * @return MvpDatabase
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MvpDatabase();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     * @throws MvpConnectionException
     */
    public function connect() {
        if ($this->connected) {
            return; // Already connected
        }

        try {
            $this->mysqli = new mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['name']
            );

            if ($this->mysqli->connect_error) {
                throw new MvpConnectionException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Connection failed: " . $this->mysqli->connect_error,
                    $this->mysqli->connect_errno,
                    ['host' => $this->config['host'], 'database' => $this->config['name']]
                );
            }

            // Set charset
            if (!$this->mysqli->set_charset($this->config['charset'])) {
                throw new MvpConnectionException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Error setting charset: " . $this->mysqli->error,
                    0,
                    ['charset' => $this->config['charset']]
                );
            }

            $this->connected = true;

        } catch (Exception $e) {
            if ($e instanceof MvpConnectionException) {
                throw $e;
            }
            throw new MvpConnectionException(
                "[" . __FILE__ . ":" . __LINE__ . "] Unexpected error during connection: " . $e->getMessage(),
                0,
                ['original_exception' => get_class($e)]
            );
        }
    }

    /**
     * Disconnect from database
     */
    public function disconnect() {
        if ($this->mysqli !== null) {
            $this->mysqli->close();
            $this->mysqli = null;
            $this->connected = false;
        }
    }

    /**
     * Check if connected
     * @return bool
     */
    public function isConnected() {
        return $this->connected && $this->mysqli !== null && $this->mysqli->ping();
    }

    /**
     * Get MySQL server info
     * @return string
     */
    public function getServerInfo() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->mysqli->server_info;
    }

    /**
     * Get table prefix
     * @return string
     */
    public function getTablePrefix() {
        return $this->config['prefix'];
    }

    /**
     * Get mysqli connection object
     * @return mysqli
     * @throws MvpConnectionException
     */
    public function getConnection() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->mysqli;
    }

    /**
     * Execute SQL query with prepared statement
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return bool Success status
     * @throws MvpQueryException
     */
    public function execute($sql, array $params = []) {
        if (!$this->isConnected()) {
            $this->connect();
        }

        try {
            $stmt = $this->mysqli->prepare($sql);

            if ($stmt === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to prepare statement: " . $this->mysqli->error,
                    $this->mysqli->errno,
                    ['sql' => $sql]
                );
            }

            // Bind parameters if provided
            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                if (!$stmt->bind_param($types, ...$params)) {
                    throw new MvpQueryException(
                        "[" . __FILE__ . ":" . __LINE__ . "] Failed to bind parameters: " . $stmt->error,
                        $stmt->errno,
                        ['sql' => $sql, 'types' => $types, 'params' => $params]
                    );
                }
            }

            // Execute
            $result = $stmt->execute();

            if ($result === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Query execution failed: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql, 'params' => $params]
                );
            }

            // Store affected rows BEFORE closing statement
            $this->lastAffectedRows = $stmt->affected_rows;

            $stmt->close();
            return true;

        } catch (Exception $e) {
            if ($e instanceof MvpQueryException) {
                throw $e;
            }
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Unexpected error during query execution: " . $e->getMessage(),
                0,
                ['sql' => $sql, 'params' => $params]
            );
        }
    }

    /**
     * Fetch single record
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return object|null Single record or null
     * @throws MvpQueryException
     */
    public function fetchOne($sql, array $params = []) {
        if (!$this->isConnected()) {
            $this->connect();
        }

        // Store original params for error reporting
        $originalParams = $params;
        $stmt = null;
        $meta = null;

        try {
            $stmt = $this->mysqli->prepare($sql);

            if ($stmt === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to prepare statement: " . $this->mysqli->error,
                    $this->mysqli->errno,
                    ['sql' => $sql]
                );
            }

            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                if (!$stmt->bind_param($types, ...$params)) {
                    throw new MvpQueryException(
                        "[" . __FILE__ . ":" . __LINE__ . "] Failed to bind parameters: " . $stmt->error,
                        $stmt->errno,
                        ['sql' => $sql, 'types' => $types, 'params' => $params]
                    );
                }
            }

            if (!$stmt->execute()) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Query execution failed: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql, 'params' => $originalParams]
                );
            }

            // Store result in memory (required for bind_result without mysqlnd)
            if (!$stmt->store_result()) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to store result: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            // Get result metadata (works without mysqlnd)
            $meta = $stmt->result_metadata();
            if ($meta === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to get result metadata: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            $fields = $meta->fetch_fields();

            // Create array to bind results (use different variable name to avoid overwriting $params)
            $row = [];
            $bindParams = [];
            foreach ($fields as $field) {
                $row[$field->name] = null;  // Initialize before creating reference
                $bindParams[] = &$row[$field->name];
            }

            // Bind result columns
            if (!call_user_func_array([$stmt, 'bind_result'], $bindParams)) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to bind result: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            // Fetch single record
            $record = null;
            if ($stmt->fetch()) {
                $record = new stdClass();
                foreach ($row as $key => $val) {
                    $record->$key = $val;
                }
            }

            // Close metadata before closing statement
            if ($meta !== null) {
                $meta->close();
            }
            if ($stmt !== null) {
                $stmt->close();
            }
            return $record;

        } catch (Exception $e) {
            // Clean up resources
            if ($meta !== null) {
                $meta->close();
            }
            if ($stmt !== null) {
                $stmt->close();
            }
            
            if ($e instanceof MvpQueryException) {
                throw $e;
            }
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Unexpected error during fetchOne: " . $e->getMessage(),
                0,
                ['sql' => $sql, 'params' => $originalParams]
            );
        }
    }

    /**
     * Fetch all records
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array Array of records
     * @throws MvpQueryException
     */
    public function fetchAll($sql, array $params = []) {
        if (!$this->isConnected()) {
            $this->connect();
        }

        // Store original params for error reporting
        $originalParams = $params;
        $stmt = null;
        $meta = null;

        try {
            $stmt = $this->mysqli->prepare($sql);

            if ($stmt === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to prepare statement: " . $this->mysqli->error,
                    $this->mysqli->errno,
                    ['sql' => $sql]
                );
            }

            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                if (!$stmt->bind_param($types, ...$params)) {
                    throw new MvpQueryException(
                        "[" . __FILE__ . ":" . __LINE__ . "] Failed to bind parameters: " . $stmt->error,
                        $stmt->errno,
                        ['sql' => $sql, 'types' => $types, 'params' => $params]
                    );
                }
            }

            if (!$stmt->execute()) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Query execution failed: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql, 'params' => $originalParams]
                );
            }

            // Store result in memory (required for bind_result without mysqlnd)
            if (!$stmt->store_result()) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to store result: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            // Get result metadata (works without mysqlnd)
            $meta = $stmt->result_metadata();
            if ($meta === false) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to get result metadata: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            $fields = $meta->fetch_fields();

            // Create array to bind results (use different variable name to avoid overwriting $params)
            $row = [];
            $bindParams = [];
            foreach ($fields as $field) {
                $row[$field->name] = null;  // Initialize before creating reference
                $bindParams[] = &$row[$field->name];
            }

            // Bind result columns
            if (!call_user_func_array([$stmt, 'bind_result'], $bindParams)) {
                throw new MvpQueryException(
                    "[" . __FILE__ . ":" . __LINE__ . "] Failed to bind result: " . $stmt->error,
                    $stmt->errno,
                    ['sql' => $sql]
                );
            }

            // Fetch all records
            $records = [];
            while ($stmt->fetch()) {
                $record = new stdClass();
                foreach ($row as $key => $val) {
                    $record->$key = $val;
                }
                $records[] = $record;
            }

            // Close metadata before closing statement
            if ($meta !== null) {
                $meta->close();
            }
            if ($stmt !== null) {
                $stmt->close();
            }
            return $records;

        } catch (Exception $e) {
            // Clean up resources
            if ($meta !== null) {
                $meta->close();
            }
            if ($stmt !== null) {
                $stmt->close();
            }
            
            if ($e instanceof MvpQueryException) {
                throw $e;
            }
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Unexpected error during fetchAll: " . $e->getMessage(),
                0,
                ['sql' => $sql, 'params' => $originalParams]
            );
        }
    }

    /**
     * Get last inserted ID
     * @return int
     */
    public function lastInsertId() {
        return $this->mysqli->insert_id;
    }

    /**
     * Get affected rows count
     * @return int
     */
    public function affectedRows() {
        return $this->lastAffectedRows;
    }

    /**
     * Escape string for SQL
     * @param string $value
     * @return string
     */
    public function escape($value) {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->mysqli->real_escape_string($value);
    }

    /**
     * Determine parameter types for bind_param
     * @param array $params
     * @return string Type string (e.g., "ssi" for string, string, int)
     */
    private function getParamTypes(array $params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob
            }
        }
        return $types;
    }

    /**
     * Begin transaction
     * @throws MvpQueryException
     */
    public function beginTransaction() {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if ($this->inTransaction) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Transaction already in progress. Nested transactions are not supported.",
                0,
                ['current_transaction_active' => true]
            );
        }

        if (!$this->mysqli->begin_transaction()) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Failed to begin transaction: " . $this->mysqli->error,
                $this->mysqli->errno
            );
        }

        $this->inTransaction = true;
    }

    /**
     * Commit transaction
     * @throws MvpQueryException
     */
    public function commit() {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!$this->inTransaction) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] No active transaction to commit",
                0
            );
        }

        if (!$this->mysqli->commit()) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Failed to commit transaction: " . $this->mysqli->error,
                $this->mysqli->errno
            );
        }

        $this->inTransaction = false;
    }

    /**
     * Rollback transaction
     * @throws MvpQueryException
     */
    public function rollback() {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!$this->inTransaction) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] No active transaction to rollback",
                0
            );
        }

        if (!$this->mysqli->rollback()) {
            throw new MvpQueryException(
                "[" . __FILE__ . ":" . __LINE__ . "] Failed to rollback transaction: " . $this->mysqli->error,
                $this->mysqli->errno
            );
        }

        $this->inTransaction = false;
    }

    /**
     * Check if in transaction
     * @return bool
     */
    public function inTransaction() {
        return $this->inTransaction;
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->disconnect();
    }
}
?>
