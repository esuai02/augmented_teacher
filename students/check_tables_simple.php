<?php
/**
 * Simple Table Check
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "<h1>Simple Table Check</h1><hr>";

// Check using SHOW TABLES
try {
    $tables = $DB->get_records_sql("SHOW TABLES LIKE 'mdl_abessi%'");

    echo "<h2>Tables Starting with 'mdl_abessi':</h2>";
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// Try direct count
echo "<h2>Direct Count Attempts:</h2>";

try {
    $result = $DB->get_record_sql("SELECT COUNT(*) as cnt FROM mdl_abessi_content_reviews");
    echo "<p>✅ mdl_abessi_content_reviews: {$result->cnt} records</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ mdl_abessi_content_reviews error: " . $e->getMessage() . "</p>";
}

try {
    $result = $DB->get_record_sql("SELECT COUNT(*) as cnt FROM mdl_abessi_review_history");
    echo "<p>✅ mdl_abessi_review_history: {$result->cnt} records</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ mdl_abessi_review_history error: " . $e->getMessage() . "</p>";
}

// Show database connection info
echo "<h2>Database Info:</h2>";
echo "<p>DB type: " . $DB->get_dbfamily() . "</p>";
echo "<p>DB name: " . $DB->get_name() . "</p>";
?>
