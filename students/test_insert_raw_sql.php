<?php
/**
 * Test Raw SQL Insert
 * Verifies raw SQL INSERT works for our tables
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

echo "<h1>Test Raw SQL Insert</h1>";
echo "<hr>";

echo "<p><strong>User:</strong> {$USER->firstname} {$USER->lastname} (ID: {$USER->id})</p>";

// Test raw SQL INSERT
try {
    echo "<h2>1. Inserting Test Review Record</h2>";

    $insertSql = "INSERT INTO mdl_abessi_content_reviews (
        contentsid, cmid, pagenum, review_level, review_status,
        feedback, improvements, reviewer_id, reviewer_name, reviewer_role,
        student_id, wboard_id, timecreated, timemodified, version, is_latest
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $DB->execute($insertSql, [
        29596,  // contentsid
        87712,  // cmid
        1,      // pagenum
        'L4',   // review_level
        'pending',  // review_status
        'Test feedback from raw SQL',  // feedback
        'Test improvements',  // improvements
        $USER->id,  // reviewer_id
        $USER->firstname . ' ' . $USER->lastname,  // reviewer_name
        'teacher',  // reviewer_role
        2,      // student_id
        'test_wboard',  // wboard_id
        time(),  // timecreated
        time(),  // timemodified
        1,      // version
        1       // is_latest
    ]);

    // Get inserted ID
    $reviewId = $DB->get_field_sql("SELECT LAST_INSERT_ID()");

    echo "<p style='color:green;'>✅ Review inserted successfully! ID: $reviewId</p>";

    // Verify record exists
    echo "<h2>2. Verifying Inserted Record</h2>";

    $verify = $DB->get_record_sql("SELECT * FROM mdl_abessi_content_reviews WHERE id = ?", [$reviewId]);

    if ($verify) {
        echo "<p style='color:green;'>✅ Record verified in database</p>";
        echo "<pre>";
        print_r($verify);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ Could not find inserted record</p>";
    }

    // Insert history record
    echo "<h2>3. Inserting History Record</h2>";

    $historySql = "INSERT INTO mdl_abessi_review_history (
        review_id, contentsid, action_type, old_level, new_level,
        old_status, new_status, change_summary, changed_by, changed_by_name, timecreated
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $DB->execute($historySql, [
        $reviewId,
        29596,
        'created',
        null,
        'L4',
        null,
        'pending',
        'Test history entry',
        $USER->id,
        $USER->firstname . ' ' . $USER->lastname,
        time()
    ]);

    $historyId = $DB->get_field_sql("SELECT LAST_INSERT_ID()");

    echo "<p style='color:green;'>✅ History inserted successfully! ID: $historyId</p>";

    // Cleanup
    echo "<h2>4. Cleanup</h2>";

    $DB->execute("DELETE FROM mdl_abessi_review_history WHERE id = ?", [$historyId]);
    $DB->execute("DELETE FROM mdl_abessi_content_reviews WHERE id = ?", [$reviewId]);

    echo "<p style='color:green;'>✅ Test records deleted</p>";

    echo "<hr>";
    echo "<h2>✅ Success!</h2>";
    echo "<p>Raw SQL INSERT/DELETE operations work correctly!</p>";
    echo "<p><strong>Next:</strong> Try submitting a review in contentsreview.php</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Test failed</p>";
    echo "<p>Error: {$e->getMessage()}</p>";
    echo "<p>File: {$e->getFile()}</p>";
    echo "<p>Line: {$e->getLine()}</p>";
}

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
