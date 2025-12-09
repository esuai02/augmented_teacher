<?php
/**
 * Database Migration - Content Review System
 *
 * PURPOSE: Create tables for content assessment/review storage
 *
 * TABLES:
 *   - mdl_abessi_content_reviews: Main review records
 *   - mdl_abessi_review_history: Historical changes tracking
 *
 * USAGE: Access this file directly via browser to create tables
 *   https://mathking.kr/moodle/local/augmented_teacher/students/db_migration_content_review.php
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 * VERSION: 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "<h1>Content Review Database Migration</h1>";
echo "<p>Creating tables for content assessment system...</p>";
echo "<hr>";

$errors = [];
$success = [];

// ========================================
// Table 1: mdl_abessi_content_reviews
// ========================================
echo "<h2>1. Creating mdl_abessi_content_reviews</h2>";

$table1 = "mdl_abessi_content_reviews";

// Drop existing table if exists
try {
    $DB->execute("DROP TABLE IF EXISTS $table1");
    echo "<p style='color:orange;'>⚠️  Dropped existing table: $table1</p>";
} catch (Exception $e) {
    echo "<p style='color:gray;'>ℹ️  Table $table1 did not exist (first run)</p>";
}

// Create table using Moodle's execute_sql_arr() for multi-statement support
// OR use direct mysqli if available
$sql1 = "CREATE TABLE $table1 (
id BIGINT(10) NOT NULL AUTO_INCREMENT,
contentsid BIGINT(10) NOT NULL,
cmid BIGINT(10) NOT NULL,
pagenum INT(5) NOT NULL,
review_level VARCHAR(10) NOT NULL,
review_status VARCHAR(20) DEFAULT 'pending',
feedback TEXT,
improvements TEXT,
reviewer_id BIGINT(10) NOT NULL,
reviewer_name VARCHAR(255) NOT NULL,
reviewer_role VARCHAR(50),
student_id BIGINT(10),
wboard_id VARCHAR(100),
timecreated BIGINT(10) NOT NULL,
timemodified BIGINT(10) NOT NULL,
version INT(5) DEFAULT 1,
is_latest TINYINT(1) DEFAULT 1,
PRIMARY KEY (id),
KEY idx_contentsid (contentsid),
KEY idx_cmid (cmid),
KEY idx_reviewer (reviewer_id),
KEY idx_student (student_id),
KEY idx_status (review_status),
KEY idx_level (review_level),
KEY idx_latest (is_latest),
KEY idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $DB->execute($sql1);
    $success[] = "✅ Created table: $table1";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";
} catch (Exception $e) {
    $errors[] = "❌ Failed to create $table1: " . $e->getMessage();
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Table 2: mdl_abessi_review_history
// ========================================
echo "<h2>2. Creating mdl_abessi_review_history</h2>";

$table2 = "mdl_abessi_review_history";

// Drop existing table if exists
try {
    $DB->execute("DROP TABLE IF EXISTS $table2");
    echo "<p style='color:orange;'>⚠️  Dropped existing table: $table2</p>";
} catch (Exception $e) {
    echo "<p style='color:gray;'>ℹ️  Table $table2 did not exist (first run)</p>";
}

// Create table
$sql2 = "CREATE TABLE $table2 (
id BIGINT(10) NOT NULL AUTO_INCREMENT,
review_id BIGINT(10) NOT NULL,
contentsid BIGINT(10) NOT NULL,
action_type VARCHAR(20) NOT NULL,
old_level VARCHAR(10),
new_level VARCHAR(10),
old_status VARCHAR(20),
new_status VARCHAR(20),
change_summary TEXT,
changed_by BIGINT(10) NOT NULL,
changed_by_name VARCHAR(255) NOT NULL,
timecreated BIGINT(10) NOT NULL,
PRIMARY KEY (id),
KEY idx_review_id (review_id),
KEY idx_contentsid (contentsid),
KEY idx_action (action_type),
KEY idx_timecreated (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $DB->execute($sql2);
    $success[] = "✅ Created table: $table2";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";
} catch (Exception $e) {
    $errors[] = "❌ Failed to create $table2: " . $e->getMessage();
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Insert Sample Data
// ========================================
echo "<h2>3. Inserting Sample Data (Optional)</h2>";

$sampleData = array(
    'contentsid' => 29596,  // P001 중급 대표유형
    'cmid' => 87712,
    'pagenum' => 1,
    'review_level' => 'L4',
    'review_status' => 'approved',
    'feedback' => '설명이 명확하고 예시가 적절합니다.',
    'improvements' => '난이도 조정, 추가 예시 제공',
    'reviewer_id' => 2,
    'reviewer_name' => '이태상 T',
    'reviewer_role' => 'teacher',
    'student_id' => 2,
    'wboard_id' => 'keytopic_review_29596_user2',
    'timecreated' => time(),
    'timemodified' => time(),
    'version' => 1,
    'is_latest' => 1
);

try {
    $insertId = $DB->insert_record($table1, (object)$sampleData);
    $success[] = "✅ Inserted sample review record (ID: $insertId)";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

    // Insert history record
    $historyData = array(
        'review_id' => $insertId,
        'contentsid' => 29596,
        'action_type' => 'created',
        'old_level' => null,
        'new_level' => 'L4',
        'old_status' => null,
        'new_status' => 'approved',
        'change_summary' => 'Initial review created',
        'changed_by' => 2,
        'changed_by_name' => '이태상 T',
        'timecreated' => time()
    );

    $historyId = $DB->insert_record($table2, (object)$historyData);
    $success[] = "✅ Inserted sample history record (ID: $historyId)";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

} catch (Exception $e) {
    $errors[] = "⚠️  Sample data insertion failed: " . $e->getMessage();
    echo "<p style='color:orange;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Verify Tables
// ========================================
echo "<h2>4. Verification</h2>";

try {
    $count1 = $DB->count_records($table1);
    $count2 = $DB->count_records($table2);

    echo "<p>✅ Table '$table1' has $count1 records</p>";
    echo "<p>✅ Table '$table2' has $count2 records</p>";

    // Show sample query
    echo "<h3>Sample Data from $table1:</h3>";
    $records = $DB->get_records_sql("SELECT * FROM $table1 LIMIT 5");

    if ($records) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Content</th><th>Level</th><th>Status</th><th>Reviewer</th><th>Created</th></tr>";
        foreach($records as $rec) {
            echo "<tr>";
            echo "<td>{$rec->id}</td>";
            echo "<td>{$rec->contentsid}</td>";
            echo "<td><strong>{$rec->review_level}</strong></td>";
            echo "<td>{$rec->review_status}</td>";
            echo "<td>{$rec->reviewer_name}</td>";
            echo "<td>" . date('Y-m-d H:i', $rec->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found (empty table)</p>";
    }

} catch (Exception $e) {
    $errors[] = "❌ Verification failed: " . $e->getMessage();
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Summary
// ========================================
echo "<hr>";
echo "<h2>Migration Summary</h2>";

if (count($errors) === 0) {
    echo "<p style='color:green; font-size:18px; font-weight:bold;'>✅ Migration completed successfully!</p>";
} else {
    echo "<p style='color:red; font-size:18px; font-weight:bold;'>⚠️  Migration completed with " . count($errors) . " error(s)</p>";
}

echo "<h3>Success (" . count($success) . "):</h3>";
echo "<ul>";
foreach($success as $s) {
    echo "<li>$s</li>";
}
echo "</ul>";

if (count($errors) > 0) {
    echo "<h3>Errors (" . count($errors) . "):</h3>";
    echo "<ul>";
    foreach($errors as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Update contentsreview.php to save review data to mdl_abessi_content_reviews</li>";
echo "<li>Implement history tracking on updates</li>";
echo "<li>Create admin dashboard to view review statistics</li>";
echo "<li>Add query functions for retrieving reviews by content/user</li>";
echo "</ol>";

echo "<p><em>Migration completed at " . date('Y-m-d H:i:s') . "</em></p>";

// ========================================
// SQL Reference for Future Use
// ========================================
echo "<hr>";
echo "<h2>SQL Reference</h2>";
echo "<details>";
echo "<summary><strong>Click to show SQL queries for common operations</strong></summary>";
echo "<pre style='background:#f5f5f5; padding:15px; margin-top:10px;'>";
echo "
-- Get all reviews for a specific content
SELECT * FROM mdl_abessi_content_reviews
WHERE contentsid = 29596
ORDER BY timecreated DESC;

-- Get latest review for each content in a course module
SELECT * FROM mdl_abessi_content_reviews
WHERE cmid = 87712 AND is_latest = 1
ORDER BY pagenum ASC;

-- Get review history for a specific content
SELECT r.*, h.*
FROM mdl_abessi_content_reviews r
LEFT JOIN mdl_abessi_review_history h ON r.id = h.review_id
WHERE r.contentsid = 29596
ORDER BY h.timecreated DESC;

-- Count reviews by level
SELECT review_level, COUNT(*) as count
FROM mdl_abessi_content_reviews
WHERE cmid = 87712 AND is_latest = 1
GROUP BY review_level;

-- Get all reviews by a specific reviewer
SELECT * FROM mdl_abessi_content_reviews
WHERE reviewer_id = 2
ORDER BY timecreated DESC;

-- Get pending reviews
SELECT * FROM mdl_abessi_content_reviews
WHERE review_status = 'pending' AND is_latest = 1
ORDER BY timecreated ASC;
";
echo "</pre>";
echo "</details>";

?>

<!--
DATABASE SCHEMA REFERENCE
=========================

Table: mdl_abessi_content_reviews
----------------------------------
id              BIGINT(10)      Primary key
contentsid      BIGINT(10)      FK to mdl_icontent_pages.id
cmid            BIGINT(10)      Course module ID
pagenum         INT(5)          Page number
review_level    VARCHAR(10)     L1, L2, L3, L4, L5
review_status   VARCHAR(20)     pending, approved, revision_needed
feedback        TEXT            Overall feedback
improvements    TEXT            Improvement suggestions
reviewer_id     BIGINT(10)      FK to mdl_user.id
reviewer_name   VARCHAR(255)    Reviewer name
reviewer_role   VARCHAR(50)     teacher, admin, expert
student_id      BIGINT(10)      FK to mdl_user.id
wboard_id       VARCHAR(100)    Whiteboard ID
timecreated     BIGINT(10)      Creation timestamp
timemodified    BIGINT(10)      Modification timestamp
version         INT(5)          Version number
is_latest       TINYINT(1)      Latest flag

Table: mdl_abessi_review_history
---------------------------------
id              BIGINT(10)      Primary key
review_id       BIGINT(10)      FK to mdl_abessi_content_reviews.id
contentsid      BIGINT(10)      FK to mdl_icontent_pages.id
action_type     VARCHAR(20)     created, updated, deleted, approved
old_level       VARCHAR(10)     Previous level
new_level       VARCHAR(10)     New level
old_status      VARCHAR(20)     Previous status
new_status      VARCHAR(20)     New status
change_summary  TEXT            Change description
changed_by      BIGINT(10)      FK to mdl_user.id
changed_by_name VARCHAR(255)    User name
timecreated     BIGINT(10)      Change timestamp
-->
