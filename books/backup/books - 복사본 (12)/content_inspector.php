<?php
/**
 * Content Inspector - Teacher Content Review Tool
 *
 * PURPOSE: Split-screen interface for reviewing educational content
 *          Left panel: Table of contents, Right panel: Content details
 *
 * ACCESS: Teachers and Admins only (students blocked)
 *
 * URL PATTERN:
 *   content_inspector.php?cmid={course_module_id}&studentid={user_id}
 *
 * FEATURES:
 *   - Click TOC item to load details
 *   - View content thumbnail image
 *   - Check audio status (audiourl/audiourl2)
 *   - Play audio narrations
 *   - Link to editprompt.php for editing
 *
 * DEPENDENCIES:
 *   - fetch_content_detail.php (AJAX endpoint)
 *   - jQuery 3.6.0 (CDN)
 *   - Bootstrap 4 (CDN)
 *   - Moodle DB API
 *
 * DATABASE TABLES:
 *   - mdl_icontent_pages (content source)
 *   - mdl_user_info_data (role checking)
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 * VERSION: 1.0
 */

// Moodle bootstrap
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Get parameters
$cmid = $_GET["cmid"];
$studentid = $_GET["studentid"] ?? $USER->id;

// Validate required parameters
if(empty($cmid)) {
    error_log(sprintf('[Content Inspector Error] File: %s, Line: %d, Error: Missing cmid parameter',
        basename(__FILE__), __LINE__));
    die('<h1>Error</h1><p>Missing required parameter: cmid</p>');
}

// Role check - BLOCK STUDENTS
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data;

if($role === 'student') {
    error_log(sprintf('[Access Denied] File: %s, Line: %d, User: %d, Role: %s',
        basename(__FILE__), __LINE__, $USER->id, $role));
    die('<h1>Access Denied</h1><p>Students cannot access this page.</p>');
}

// Fetch table of contents
$cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cmid' ORDER BY pagenum ASC");

if(!$cntpages || count($cntpages) === 0) {
    error_log(sprintf('[Content Inspector Warning] File: %s, Line: %d, cmid: %s, No content pages found',
        basename(__FILE__), __LINE__, $cmid));
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Inspector - Teacher Review Tool</title>

    <!-- Bootstrap 4 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
    /* Split-screen Layout CSS */
    body {
        margin: 0;
        font-family: 'Arial', 'Malgun Gothic', sans-serif;
        overflow: hidden;
    }

    .content-inspector {
        display: flex;
        height: 100vh;
        width: 100%;
    }

    /* Left Panel - Table of Contents (60%) */
    .toc-panel {
        width: 60%;
        overflow-y: auto;
        border-right: 2px solid #ccc;
        padding: 20px;
        background: #fff;
    }

    .toc-panel h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-size: 24px;
        border-bottom: 3px solid #4CAF50;
        padding-bottom: 10px;
    }

    .toc-table {
        width: 100%;
        border-collapse: collapse;
    }

    .toc-row {
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s ease;
    }

    .toc-row:hover {
        background-color: #f5f5f5;
    }

    .toc-row.active {
        background-color: #e3f2fd;
        font-weight: bold;
        border-left: 4px solid #2196F3;
    }

    .toc-title {
        padding: 12px 8px;
        color: #333;
        font-size: 15px;
    }

    .toc-row.active .toc-title {
        color: #1976D2;
    }

    .toc-audio-icon {
        padding: 12px 8px;
        text-align: center;
        width: 50px;
        font-size: 18px;
    }

    /* Right Panel - Content Details (40%) */
    .detail-panel {
        width: 40%;
        padding: 20px;
        overflow-y: auto;
        background: #fafafa;
    }

    .placeholder {
        color: #999;
        text-align: center;
        padding-top: 100px;
        font-size: 18px;
        line-height: 1.6;
    }

    /* Scrollbar styling */
    .toc-panel::-webkit-scrollbar,
    .detail-panel::-webkit-scrollbar {
        width: 8px;
    }

    .toc-panel::-webkit-scrollbar-track,
    .detail-panel::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .toc-panel::-webkit-scrollbar-thumb,
    .detail-panel::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .toc-panel::-webkit-scrollbar-thumb:hover,
    .detail-panel::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Bootstrap alert override for no-content message */
    .toc-panel .alert {
        margin-top: 20px;
    }
    </style>
</head>
<body>

<div class="content-inspector">
    <!-- Left Panel (60%) - Table of Contents -->
    <div class="toc-panel">
        <h2>Table of Contents (Module ID: <?php echo htmlspecialchars($cmid); ?>)</h2>

        <?php if($cntpages && count($cntpages) > 0): ?>
            <table class="toc-table">
                <tbody>
                <?php foreach($cntpages as $page): ?>
                    <tr class="toc-row" data-contentsid="<?php echo $page->id; ?>" onclick="loadContentDetail(<?php echo $page->id; ?>)">
                        <td class="toc-title"><?php echo htmlspecialchars($page->title); ?></td>
                        <td class="toc-audio-icon">
                            <?php
                            // Show audio icon if either audiourl or audiourl2 exists
                            if($page->audiourl || $page->audiourl2):
                                echo '🎧';
                            endif;
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>No content found</strong> for this module (cmid: <?php echo htmlspecialchars($cmid); ?>)
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Panel (40%) - Content Details -->
    <div class="detail-panel" id="detailPanel">
        <div class="placeholder">👈 Click a content item to inspect</div>
    </div>
</div>

<script>
/**
 * AJAX Content Detail Loading
 * jQuery 3.6.0 already loaded from CDN in <head>
 */

function loadContentDetail(contentsid) {
    console.log('[Content Inspector] Loading detail for contentsid:', contentsid);

    // Update active row styling - remove from all, add to clicked
    $('.toc-row').removeClass('active');
    $('[data-contentsid="' + contentsid + '"]').addClass('active');

    // Show loading indicator in detail panel
    $('#detailPanel').html('<div class="placeholder">⏳ Loading content details...</div>');

    // AJAX request to fetch content detail
    $.ajax({
        url: 'fetch_content_detail.php',
        type: 'GET',
        data: {
            contentsid: contentsid,
            studentid: <?php echo $studentid; ?>
        },
        dataType: 'html',
        success: function(response) {
            $('#detailPanel').html(response);
            console.log('[Content Inspector] Detail loaded successfully for contentsid:', contentsid);
        },
        error: function(xhr, status, error) {
            console.error('[AJAX Error] File: content_inspector.php, Line: 235, Status:', status, 'Error:', error, 'XHR:', xhr);
            $('#detailPanel').html(
                '<div class="placeholder" style="color:#d32f2f; padding-top:50px;">' +
                '<h3>⚠️ Failed to Load Content</h3>' +
                '<p>An error occurred while loading content details.</p>' +
                '<p><small>Status: ' + status + '</small></p>' +
                '<p><small>Check browser console for details.</small></p>' +
                '</div>'
            );
        }
    });
}

// Page ready handler
$(document).ready(function() {
    console.log('[Content Inspector] Page ready - cmid:', '<?php echo $cmid; ?>', 'role:', '<?php echo $role; ?>');
    console.log('[Content Inspector] Total TOC items:', $('.toc-row').length);

    // Optional: Auto-load first content item on page load
    // Uncomment below to enable auto-loading:
    /*
    var firstId = $('.toc-row').first().data('contentsid');
    if(firstId) {
        console.log('[Content Inspector] Auto-loading first item:', firstId);
        loadContentDetail(firstId);
    }
    */
});
</script>

</body>
</html>
