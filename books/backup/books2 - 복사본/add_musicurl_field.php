<?php
// File: add_musicurl_field.php
// Purpose: Add musicurl field to mdl_icontent_pages table

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Only allow admin to run this script
if (!is_siteadmin($USER->id)) {
    die('[add_musicurl_field.php:' . __LINE__ . '] Access denied. Admin only.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Add musicurl Field to mdl_icontent_pages</h2>';

try {
    $table_name = 'icontent_pages';

    // Check if table exists
    if (!$DB->get_manager()->table_exists($table_name)) {
        echo '<p style="color: red;">[add_musicurl_field.php:' . __LINE__ . '] Table ' . $table_name . ' does not exist!</p>';
        exit;
    }

    // Check if musicurl field already exists
    $columns = $DB->get_columns($table_name);
    $field_exists = false;

    foreach ($columns as $column) {
        if ($column->name === 'musicurl') {
            $field_exists = true;
            break;
        }
    }

    if ($field_exists) {
        echo '<p style="color: orange;">[add_musicurl_field.php:' . __LINE__ . '] Field "musicurl" already exists in ' . $table_name . '</p>';

        // Show sample data
        $sql = "SELECT id, title, musicurl FROM {icontent_pages} WHERE musicurl IS NOT NULL AND musicurl != '' LIMIT 5";
        $records = $DB->get_records_sql($sql);

        if ($records) {
            echo '<h3>Sample Pages with Music URLs (최근 5개)</h3>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><th>ID</th><th>Title</th><th>Music URL</th></tr>';
            foreach ($records as $record) {
                echo '<tr>';
                echo '<td>' . $record->id . '</td>';
                echo '<td>' . htmlspecialchars($record->title) . '</td>';
                echo '<td><a href="' . htmlspecialchars($record->musicurl) . '" target="_blank">' . basename($record->musicurl) . '</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No pages with music URLs yet.</p>';
        }

    } else {
        // Add musicurl field
        echo '<p>[add_musicurl_field.php:' . __LINE__ . '] Adding "musicurl" field to ' . $table_name . '...</p>';

        $sql = "ALTER TABLE mdl_icontent_pages ADD COLUMN musicurl VARCHAR(500) DEFAULT NULL COMMENT 'URL to uploaded music file'";

        try {
            $DB->execute($sql);
            echo '<p style="color: green;"><strong>[add_musicurl_field.php:' . __LINE__ . '] Field added successfully!</strong></p>';
            echo '<pre>' . htmlspecialchars($sql) . '</pre>';
        } catch (Exception $sql_error) {
            echo '<p style="color: red;">[add_musicurl_field.php:' . __LINE__ . '] SQL Error: ' . $sql_error->getMessage() . '</p>';
            throw $sql_error;
        }
    }

    // Show table structure
    echo '<h3>Table Structure</h3>';
    $columns = $DB->get_columns($table_name);
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Column</th><th>Type</th><th>Max Length</th><th>Not Null</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        $highlight = ($column->name === 'musicurl') ? ' style="background-color: yellow;"' : '';
        echo '<tr' . $highlight . '>';
        echo '<td>' . $column->name . '</td>';
        echo '<td>' . $column->meta_type . '</td>';
        echo '<td>' . ($column->max_length ?? '-') . '</td>';
        echo '<td>' . ($column->not_null ? 'YES' : 'NO') . '</td>';
        echo '<td>' . ($column->has_default ? $column->default_value : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<hr>';
    echo '<p><a href="mynote2.php?dmn=&cid=106&nch=9&cmid=87731&quizid=&page=2&studentid=2">← Back to mynote2.php (page 2)</a></p>';
    echo '<p><a href="mynote2.php?dmn=&cid=106&nch=9&cmid=48902&page=1&studentid=2&quizid=84517">← Back to mynote2.php (page 1)</a></p>';

} catch (Exception $e) {
    echo '<p style="color: red;"><strong>[add_musicurl_field.php:' . __LINE__ . '] Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

/**
 * Field Details:
 *
 * Field: musicurl
 * Type: VARCHAR(500)
 * Default: NULL
 * Comment: URL to uploaded music file
 *
 * Purpose:
 * - Store the URL of uploaded MP3/WAV files
 * - Accessible from any page viewing the content
 * - One music file per page
 *
 * Usage:
 * - Upload music via mynote2.php + button
 * - URL stored in this field
 * - Loaded automatically when page opens
 */
?>
