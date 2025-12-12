<?php
/**
 * Centralized Error Handler
 * File: includes/error_handler.php
 *
 * Provides standardized error handling and logging for Agent01 Onboarding
 */

class AgentErrorHandler {
    /**
     * Log error with file and line information
     *
     * @param string $message Error message
     * @param string|null $file File path (auto-detected if null)
     * @param int|null $line Line number (auto-detected if null)
     */
    public static function logError($message, $file = null, $line = null) {
        $trace = debug_backtrace();
        $file = $file ?? $trace[0]['file'];
        $line = $line ?? $trace[0]['line'];

        error_log("[Agent01] Error in {$file}:{$line} - {$message}");
    }

    /**
     * Return standardized JSON error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $details Additional error details
     * @return string JSON encoded error response
     */
    public static function jsonError($message, $code = 500, $details = []) {
        $trace = debug_backtrace();

        return json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'file' => $trace[0]['file'],
            'line' => $trace[0]['line'],
            'details' => $details
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Database error handler with table checking
     *
     * @param Exception $e Exception object
     * @param string $context Error context description
     * @return null Always returns null for safe fallback
     */
    public static function handleDbError($e, $context = '') {
        $contextInfo = $context ? " ({$context})" : '';
        self::logError("Database error{$contextInfo}: " . $e->getMessage());
        return null;
    }

    /**
     * Check if table exists in database
     *
     * @param object $DB Moodle database object
     * @param string $tableName Table name without prefix
     * @return bool True if table exists
     */
    public static function tableExists($DB, $tableName) {
        try {
            return $DB->get_manager()->table_exists(new xmldb_table($tableName));
        } catch (Exception $e) {
            self::logError("Table check failed for {$tableName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Safe database query with error handling
     *
     * @param object $DB Moodle database object
     * @param string $table Table name
     * @param array $conditions Query conditions
     * @param string $fields Fields to retrieve
     * @return object|null Query result or null on error
     */
    public static function safeGetRecord($DB, $table, $conditions, $fields = '*') {
        try {
            // Check table exists first
            if (!self::tableExists($DB, $table)) {
                self::logError("Table '{$table}' does not exist");
                return null;
            }

            return $DB->get_record($table, $conditions, $fields);
        } catch (Exception $e) {
            self::handleDbError($e, "Getting record from {$table}");
            return null;
        }
    }

    /**
     * Safe database insert with error handling
     *
     * @param object $DB Moodle database object
     * @param string $table Table name
     * @param object $dataobject Data to insert
     * @return int|bool Insert ID or false on error
     */
    public static function safeInsertRecord($DB, $table, $dataobject) {
        try {
            // Check table exists first
            if (!self::tableExists($DB, $table)) {
                self::logError("Table '{$table}' does not exist");
                return false;
            }

            return $DB->insert_record($table, $dataobject);
        } catch (Exception $e) {
            self::handleDbError($e, "Inserting record into {$table}");
            return false;
        }
    }

    /**
     * Safe database update with error handling
     *
     * @param object $DB Moodle database object
     * @param string $table Table name
     * @param object $dataobject Data to update
     * @return bool True on success, false on error
     */
    public static function safeUpdateRecord($DB, $table, $dataobject) {
        try {
            // Check table exists first
            if (!self::tableExists($DB, $table)) {
                self::logError("Table '{$table}' does not exist");
                return false;
            }

            return $DB->update_record($table, $dataobject);
        } catch (Exception $e) {
            self::handleDbError($e, "Updating record in {$table}");
            return false;
        }
    }

    /**
     * Validate user ID
     *
     * @param mixed $userid User ID to validate
     * @param object|null $USER Moodle USER object for fallback
     * @return int Valid user ID or 0
     */
    public static function validateUserId($userid, $USER = null) {
        // Convert to integer
        $userid = intval($userid);

        // If invalid, try fallback to Moodle USER
        if ($userid <= 0 && $USER && isset($USER->id) && $USER->id > 0) {
            $userid = $USER->id;
            self::logError("Using Moodle USER->id as fallback: {$userid}");
        }

        // Final validation
        if ($userid <= 0) {
            self::logError("No valid userid found");
        }

        return $userid;
    }

    /**
     * Display user-friendly error page
     *
     * @param string $title Error title
     * @param string $message Error message
     * @param array $details Additional details
     */
    public static function displayErrorPage($title, $message, $details = []) {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    max-width: 600px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                }
                h1 {
                    color: #dc2626;
                    margin-bottom: 20px;
                }
                p {
                    color: #374151;
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .details {
                    background: #f9fafb;
                    border-left: 4px solid #dc2626;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 4px;
                }
                .details pre {
                    margin: 0;
                    color: #1f2937;
                    font-size: 14px;
                }
                .back-button {
                    display: inline-block;
                    background: #3b82f6;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 6px;
                    text-decoration: none;
                    margin-top: 20px;
                }
                .back-button:hover {
                    background: #2563eb;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>⚠️ <?php echo htmlspecialchars($title); ?></h1>
                <p><?php echo htmlspecialchars($message); ?></p>

                <?php if (!empty($details)): ?>
                <div class="details">
                    <strong>상세 정보:</strong>
                    <pre><?php echo htmlspecialchars(print_r($details, true)); ?></pre>
                </div>
                <?php endif; ?>

                <a href="javascript:history.back()" class="back-button">← 돌아가기</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
