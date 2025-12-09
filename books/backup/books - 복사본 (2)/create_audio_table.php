<?php
// File: create_audio_table.php
// Purpose: Create database table for audio file tracking

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Only allow admin to run this script
if (!is_siteadmin($USER->id)) {
    die('[create_audio_table.php:' . __LINE__ . '] Access denied. Admin only.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Audio Files Table Creation</h2>';

try {
    $table_name = 'abessi_audio_files';
    $full_table_name = '{' . $table_name . '}';

    // Check if table already exists
    $table_exists = $DB->get_manager()->table_exists($table_name);

    if ($table_exists) {
        echo '<p style="color: orange;">[create_audio_table.php:' . __LINE__ . '] Table ' . $table_name . ' already exists.</p>';

        // Show existing records count
        $count = $DB->count_records($table_name);
        echo '<p>Current records: ' . $count . '</p>';

        // Show sample records
        if ($count > 0) {
            $records = $DB->get_records($table_name, null, 'timecreated DESC', '*', 0, 5);
            echo '<h3>Sample Records (최근 5개)</h3>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><th>ID</th><th>Contents ID</th><th>Student ID</th><th>Filename</th><th>File Size</th><th>Created</th></tr>';
            foreach ($records as $record) {
                echo '<tr>';
                echo '<td>' . $record->id . '</td>';
                echo '<td>' . $record->contentsid . '</td>';
                echo '<td>' . $record->studentid . '</td>';
                echo '<td>' . $record->filename . '</td>';
                echo '<td>' . round($record->filesize/1024, 2) . ' KB</td>';
                echo '<td>' . date('Y-m-d H:i:s', $record->timecreated) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

    } else {
        // Create table using raw SQL (compatible with MySQL 5.7 and PHP 7.1)
        $sql = "CREATE TABLE IF NOT EXISTS `mdl_abessi_audio_files` (
            `id` bigint(10) NOT NULL AUTO_INCREMENT,
            `contentsid` bigint(10) NOT NULL DEFAULT 0,
            `studentid` bigint(10) NOT NULL DEFAULT 0,
            `filename` varchar(255) NOT NULL DEFAULT '',
            `filepath` varchar(500) NOT NULL DEFAULT '',
            `filesize` bigint(10) NOT NULL DEFAULT 0,
            `filetype` varchar(10) NOT NULL DEFAULT '',
            `uploadedby` bigint(10) NOT NULL DEFAULT 0,
            `timecreated` bigint(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_contentsid` (`contentsid`),
            KEY `idx_studentid` (`studentid`),
            KEY `idx_timecreated` (`timecreated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audio files uploaded for contents';";

        $DB->execute($sql);

        echo '<p style="color: green;"><strong>[create_audio_table.php:' . __LINE__ . '] Table created successfully!</strong></p>';
        echo '<pre>' . htmlspecialchars($sql) . '</pre>';

        // Verify table creation
        $table_exists = $DB->get_manager()->table_exists($table_name);
        if ($table_exists) {
            echo '<p style="color: green;">✓ Table verification successful!</p>';
        } else {
            echo '<p style="color: red;">✗ Table verification failed!</p>';
        }
    }

    // Show table structure
    echo '<h3>Table Structure</h3>';
    $columns = $DB->get_columns($table_name);
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . $column->name . '</td>';
        echo '<td>' . $column->meta_type . '</td>';
        echo '<td>' . ($column->not_null ? 'YES' : 'NO') . '</td>';
        echo '<td>' . ($column->has_default ? $column->default_value : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<hr>';
    echo '<p><a href="mynote2.php?dmn=&cid=106&nch=9&cmid=48902&page=1&studentid=2&quizid=84517">← Back to mynote2.php</a></p>';

} catch (Exception $e) {
    echo '<p style="color: red;"><strong>[create_audio_table.php:' . __LINE__ . '] Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

/**
 * Table Schema Details:
 *
 * Field Descriptions:
 * - id: Primary key, auto-increment
 * - contentsid: Reference to mdl_icontent_pages.id
 * - studentid: Reference to mdl_user.id
 * - filename: Stored filename (unique with timestamp)
 * - filepath: Full web-accessible URL
 * - filesize: File size in bytes
 * - filetype: File extension (mp3, wav)
 * - uploadedby: User ID who uploaded the file
 * - timecreated: Unix timestamp of creation
 *
 * Indexes:
 * - idx_contentsid: Fast lookup by content
 * - idx_studentid: Fast lookup by student
 * - idx_timecreated: Fast lookup by date
 */
?>
