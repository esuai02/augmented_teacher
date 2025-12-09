<?php
/**
 * Diagnose Table Name Issue
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "<h1>Table Name Diagnostic</h1>";
echo "<hr>";

// Check if our tables exist with exact names
echo "<h2>1. Direct Table Check</h2>";

$tablesToCheck = [
    'mdl_abessi_content_reviews',
    'mdl_abessi_review_history'
];

foreach ($tablesToCheck as $tableName) {
    echo "<h3>Checking: $tableName</h3>";

    try {
        // Try to get table structure
        $result = $DB->get_records_sql("SHOW CREATE TABLE $tableName");

        if ($result) {
            echo "<p style='color:green;'>✅ Table EXISTS</p>";

            // Show structure
            foreach ($result as $r) {
                echo "<details><summary>Show CREATE TABLE</summary>";
                echo "<pre style='background:#f5f5f5; padding:10px;'>";
                echo htmlspecialchars(print_r($r, true));
                echo "</pre></details>";
            }

            // Try to count records
            try {
                $count = $DB->count_records($tableName);
                echo "<p>Record count: <strong>$count</strong></p>";
            } catch (Exception $e) {
                echo "<p style='color:red;'>Count failed: {$e->getMessage()}</p>";
            }

        } else {
            echo "<p style='color:red;'>❌ Table DOES NOT EXIST</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Table DOES NOT EXIST</p>";
        echo "<p>Error: {$e->getMessage()}</p>";
    }
}

// List all mdl_abessi_* tables
echo "<hr>";
echo "<h2>2. All mdl_abessi_* Tables</h2>";

try {
    $tables = $DB->get_records_sql("SHOW TABLES LIKE 'mdl_abessi%'");

    echo "<p>Found " . count($tables) . " tables:</p>";
    echo "<ul style='column-count:3; font-size:12px;'>";

    foreach ($tables as $table) {
        $tableName = current((array)$table);

        // Highlight our target tables
        if (in_array($tableName, $tablesToCheck)) {
            echo "<li style='color:green; font-weight:bold;'>✅ $tableName</li>";
        } else {
            echo "<li>$tableName</li>";
        }
    }

    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error listing tables: {$e->getMessage()}</p>";
}

// Test insert capability
echo "<hr>";
echo "<h2>3. Test INSERT Capability</h2>";

try {
    $testRecord = new stdClass();
    $testRecord->contentsid = 99999;
    $testRecord->cmid = 99999;
    $testRecord->pagenum = 999;
    $testRecord->review_level = 'L5';
    $testRecord->review_status = 'test';
    $testRecord->feedback = 'Test feedback';
    $testRecord->improvements = 'Test improvements';
    $testRecord->reviewer_id = 2;
    $testRecord->reviewer_name = 'Test User';
    $testRecord->reviewer_role = 'test';
    $testRecord->student_id = 2;
    $testRecord->wboard_id = 'test_wboard';
    $testRecord->timecreated = time();
    $testRecord->timemodified = time();
    $testRecord->version = 1;
    $testRecord->is_latest = 1;

    echo "<p>Attempting to insert test record...</p>";

    $insertId = $DB->insert_record('mdl_abessi_content_reviews', $testRecord, true);

    if ($insertId) {
        echo "<p style='color:green;'>✅ INSERT successful! ID: $insertId</p>";

        // Delete test record
        $DB->delete_records('mdl_abessi_content_reviews', ['id' => $insertId]);
        echo "<p>Test record deleted.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ INSERT failed</p>";
    echo "<p>Error: {$e->getMessage()}</p>";
    echo "<p>File: {$e->getFile()}</p>";
    echo "<p>Line: {$e->getLine()}</p>";
}

// Check database connection info
echo "<hr>";
echo "<h2>4. Database Connection Info</h2>";
echo "<p><strong>DB Family:</strong> " . $DB->get_dbfamily() . "</p>";
echo "<p><strong>DB Name:</strong> " . $DB->get_name() . "</p>";

// Check current database
try {
    $currentDb = $DB->get_record_sql("SELECT DATABASE() as db");
    echo "<p><strong>Current Database:</strong> {$currentDb->db}</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Could not determine current database</p>";
}

echo "<p><em>Diagnostic completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
