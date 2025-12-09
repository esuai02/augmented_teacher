<?php
// File: check_musicurl.php
// Purpose: Check musicurl field status for debugging

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Only allow admin to run this script
if (!is_siteadmin($USER->id)) {
    die('[check_musicurl.php:' . __LINE__ . '] Access denied. Admin only.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Music URL Status Check</h2>';

try {
    $contentsid = isset($_GET['contentsid']) ? intval($_GET['contentsid']) : 0;

    if ($contentsid === 0) {
        // Show all pages with music URLs
        echo '<h3>All Pages with Music URLs</h3>';
        $sql = "SELECT id, title, musicurl FROM {icontent_pages} WHERE musicurl IS NOT NULL AND musicurl != '' ORDER BY id DESC LIMIT 20";
        $pages = $DB->get_records_sql($sql);

        if ($pages) {
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            echo '<tr style="background-color: #f0f0f0;">';
            echo '<th>ID</th><th>Title</th><th>Music URL</th><th>File Exists</th><th>Actions</th>';
            echo '</tr>';

            foreach ($pages as $page) {
                $filename = basename($page->musicurl);
                $file_path = '/home/moodle/public_html/Contents/audiofiles/music/' . $filename;
                $file_exists = file_exists($file_path);
                $file_color = $file_exists ? 'green' : 'red';
                $file_status = $file_exists ? '✓ Yes' : '✗ No';

                echo '<tr>';
                echo '<td>' . $page->id . '</td>';
                echo '<td>' . htmlspecialchars(substr($page->title, 0, 50)) . '</td>';
                echo '<td><a href="' . htmlspecialchars($page->musicurl) . '" target="_blank">' . htmlspecialchars($filename) . '</a></td>';
                echo '<td style="color: ' . $file_color . '; font-weight: bold;">' . $file_status . '</td>';
                echo '<td><a href="?contentsid=' . $page->id . '">Details</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No pages with music URLs found.</p>';
        }

        // Show statistics
        echo '<h3>Statistics</h3>';
        $total_pages = $DB->count_records('icontent_pages');
        $pages_with_music = $DB->count_records_sql("SELECT COUNT(*) FROM {icontent_pages} WHERE musicurl IS NOT NULL AND musicurl != ''");
        echo '<ul>';
        echo '<li>Total pages: ' . $total_pages . '</li>';
        echo '<li>Pages with music: ' . $pages_with_music . '</li>';
        echo '<li>Percentage: ' . ($total_pages > 0 ? round($pages_with_music / $total_pages * 100, 2) : 0) . '%</li>';
        echo '</ul>';

    } else {
        // Show specific page details
        echo '<p><a href="check_musicurl.php">← Back to list</a></p>';
        echo '<h3>Page Details (ID: ' . $contentsid . ')</h3>';

        $page = $DB->get_record('icontent_pages', ['id' => $contentsid], 'id, title, musicurl');

        if ($page) {
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td>ID</td><td>' . $page->id . '</td></tr>';
            echo '<tr><td>Title</td><td>' . htmlspecialchars($page->title) . '</td></tr>';
            echo '<tr><td>Music URL</td><td>' . htmlspecialchars($page->musicurl ?: 'NULL') . '</td></tr>';

            if (!empty($page->musicurl)) {
                $filename = basename($page->musicurl);
                $file_path = '/home/moodle/public_html/Contents/audiofiles/music/' . $filename;
                $file_exists = file_exists($file_path);

                echo '<tr><td>Filename</td><td>' . htmlspecialchars($filename) . '</td></tr>';
                echo '<tr><td>File Path</td><td>' . htmlspecialchars($file_path) . '</td></tr>';
                echo '<tr><td>File Exists</td><td style="color: ' . ($file_exists ? 'green' : 'red') . '; font-weight: bold;">' . ($file_exists ? '✓ Yes' : '✗ No') . '</td></tr>';

                if ($file_exists) {
                    $filesize = filesize($file_path);
                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    echo '<tr><td>File Size</td><td>' . round($filesize / 1024, 2) . ' KB</td></tr>';
                    echo '<tr><td>File Type</td><td>' . htmlspecialchars($filetype) . '</td></tr>';
                }

                echo '<tr><td>Test URL</td><td><a href="' . htmlspecialchars($page->musicurl) . '" target="_blank">Open in new tab</a></td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p style="color: red;">Page not found!</p>';
        }
    }

} catch (Exception $e) {
    echo '<p style="color: red;"><strong>[check_musicurl.php:' . __LINE__ . '] Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<hr>';
echo '<p><small>Last updated: ' . date('Y-m-d H:i:s') . '</small></p>';
?>
