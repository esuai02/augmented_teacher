<?php
// 파일: mvp_system/lib/database.php (Line 1)
// Mathking Agentic MVP System - Database Utility

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

/**
 * MVP Database Wrapper Class
 * Moodle DB를 래핑하여 에러 처리 및 로깅 제공
 */
class MVPDatabase {
    private $db;
    private $table_prefix = 'mvp_';

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;

        if (!$DB) {
            throw new Exception("Moodle DB not initialized at " . __FILE__ . ":" . __LINE__);
        }

        $this->db = $DB;
        error_log("[MVPDatabase] Initialized at " . __FILE__ . ":" . __LINE__);
    }

    /**
     * Insert record into table
     * @param string $table Table name (without mdl_mvp_ prefix)
     * @param array|object $data Data to insert
     * @return int Inserted record ID
     * @throws Exception on failure
     */
    public function insert($table, $data) {
        $full_table = $this->get_full_table_name($table);

        try {
            $data_obj = is_array($data) ? (object)$data : $data;
            $id = $this->db->insert_record($full_table, $data_obj);

            error_log("[MVPDatabase] Inserted into $full_table (ID: $id) at " . __FILE__ . ":" . __LINE__);
            return $id;

        } catch (Exception $e) {
            $error_msg = "DB Insert Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage();
            error_log($error_msg);
            throw new Exception($error_msg);
        }
    }

    /**
     * Select records from table
     * @param string $table Table name
     * @param array $conditions Where conditions
     * @param string $fields Fields to select (default: *)
     * @param string $sort Sort order
     * @param int $limitfrom Offset
     * @param int $limitnum Limit
     * @return array Array of records
     */
    public function select($table, $conditions = [], $fields = '*', $sort = '', $limitfrom = 0, $limitnum = 0) {
        $full_table = $this->get_full_table_name($table);

        try {
            return $this->db->get_records($full_table, $conditions, $sort, $fields, $limitfrom, $limitnum);
        } catch (Exception $e) {
            error_log("DB Select Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Select single record
     * @param string $table Table name
     * @param array $conditions Where conditions
     * @param string $fields Fields to select
     * @return object|false Record object or false
     */
    public function select_one($table, $conditions, $fields = '*') {
        $full_table = $this->get_full_table_name($table);

        try {
            return $this->db->get_record($full_table, $conditions, $fields);
        } catch (Exception $e) {
            error_log("DB Select One Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute raw SQL query and return results
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array Array of records (as associative arrays)
     */
    public function query($sql, $params = []) {
        try {
            $records = $this->db->get_records_sql($sql, $params);
            error_log("[MVPDatabase] Query executed at " . __FILE__ . ":" . __LINE__);

            // Convert to array format (each record as associative array)
            if (!$records) {
                return [];
            }

            $result = [];
            foreach ($records as $record) {
                $result[] = (array)$record;
            }

            return $result;
        } catch (Exception $e) {
            error_log("DB Query Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute raw SQL query (no return)
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return bool Success
     */
    public function execute($sql, $params = []) {
        try {
            $this->db->execute($sql, $params);
            error_log("[MVPDatabase] Executed SQL at " . __FILE__ . ":" . __LINE__);
            return true;
        } catch (Exception $e) {
            error_log("DB Execute Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get full table name with mdl_mvp_ prefix
     * @param string $table Short table name
     * @return string Full table name
     */
    private function get_full_table_name($table) {
        // If table already has mdl_ prefix, return as-is
        if (strpos($table, 'mdl_') === 0) {
            return $table;
        }

        // Add mdl_mvp_ prefix
        return 'mdl_' . $this->table_prefix . $table;
    }

    /**
     * Count records in table
     * @param string $table Table name
     * @param array $conditions Where conditions
     * @return int Record count
     */
    public function count($table, $conditions = []) {
        $full_table = $this->get_full_table_name($table);

        try {
            return $this->db->count_records($full_table, $conditions);
        } catch (Exception $e) {
            error_log("DB Count Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete records from table
     * @param string $table Table name
     * @param array $conditions Where conditions
     * @return bool Success
     */
    public function delete($table, $conditions) {
        $full_table = $this->get_full_table_name($table);

        try {
            $this->db->delete_records($full_table, $conditions);
            error_log("[MVPDatabase] Deleted from $full_table at " . __FILE__ . ":" . __LINE__);
            return true;
        } catch (Exception $e) {
            error_log("DB Delete Error at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Begin transaction
     */
    public function begin_transaction() {
        $this->db->start_delegated_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit_transaction() {
        $this->db->commit_delegated_transaction();
    }

    /**
     * Rollback transaction
     */
    public function rollback_transaction() {
        $this->db->rollback_delegated_transaction();
    }
}
?>
