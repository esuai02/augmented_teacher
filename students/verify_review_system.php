<?php
/**
 * Database Verification Script for Content Review System
 *
 * PURPOSE: Verify tables exist and show sample data
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "<h1>Content Review System Verification</h1>";
echo "<hr>";

// ========================================
// Check Tables
// ========================================
echo "<h2>1. Table Status</h2>";

try {
    $reviewCount = $DB->count_records('mdl_abessi_content_reviews');
    $historyCount = $DB->count_records('mdl_abessi_review_history');

    echo "<p style='color:green;'>✅ mdl_abessi_content_reviews: <strong>{$reviewCount}</strong> records</p>";
    echo "<p style='color:green;'>✅ mdl_abessi_review_history: <strong>{$historyCount}</strong> records</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: {$e->getMessage()}</p>";
    exit;
}

// ========================================
// Show Recent Reviews
// ========================================
echo "<h2>2. Recent Reviews</h2>";

try {
    $reviews = $DB->get_records_sql(
        "SELECT * FROM mdl_abessi_content_reviews
         ORDER BY timecreated DESC
         LIMIT 10"
    );

    if (count($reviews) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Content ID</th>";
        echo "<th>Level</th>";
        echo "<th>Status</th>";
        echo "<th>Reviewer</th>";
        echo "<th>Version</th>";
        echo "<th>Latest</th>";
        echo "<th>Created</th>";
        echo "</tr>";

        foreach($reviews as $r) {
            echo "<tr>";
            echo "<td>{$r->id}</td>";
            echo "<td>{$r->contentsid}</td>";
            echo "<td><strong style='color:#3b82f6;'>{$r->review_level}</strong></td>";
            echo "<td>" . ($r->review_status === 'approved' ? '✅' : '⏳') . " {$r->review_status}</td>";
            echo "<td>{$r->reviewer_name}</td>";
            echo "<td>{$r->version}</td>";
            echo "<td>" . ($r->is_latest ? '✅' : '❌') . "</td>";
            echo "<td>" . date('Y-m-d H:i', $r->timecreated) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No reviews found yet. Submit a review via contentsreview.php to test.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: {$e->getMessage()}</p>";
}

// ========================================
// Show Recent History
// ========================================
echo "<h2>3. Recent History</h2>";

try {
    $history = $DB->get_records_sql(
        "SELECT * FROM mdl_abessi_review_history
         ORDER BY timecreated DESC
         LIMIT 10"
    );

    if (count($history) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Review ID</th>";
        echo "<th>Action</th>";
        echo "<th>Old→New Level</th>";
        echo "<th>Changed By</th>";
        echo "<th>Created</th>";
        echo "</tr>";

        foreach($history as $h) {
            $levelChange = ($h->old_level ?? 'N/A') . ' → ' . $h->new_level;

            echo "<tr>";
            echo "<td>{$h->id}</td>";
            echo "<td>{$h->review_id}</td>";
            echo "<td><strong>{$h->action_type}</strong></td>";
            echo "<td>{$levelChange}</td>";
            echo "<td>{$h->changed_by_name}</td>";
            echo "<td>" . date('Y-m-d H:i', $h->timecreated) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No history records yet.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: {$e->getMessage()}</p>";
}

// ========================================
// Test AJAX Endpoint Availability
// ========================================
echo "<h2>4. AJAX Endpoint Check</h2>";

$ajaxFile = __DIR__ . '/contentsreview_ajax.php';
if (file_exists($ajaxFile)) {
    echo "<p style='color:green;'>✅ contentsreview_ajax.php exists</p>";
    echo "<p><strong>Path:</strong> $ajaxFile</p>";
} else {
    echo "<p style='color:red;'>❌ contentsreview_ajax.php NOT found</p>";
}

// ========================================
// Next Steps
// ========================================
echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>🌐 <a href='contentsreview.php?userid=2&cntid=87712&title=검수' target='_blank'>Open Content Review Page</a></li>";
echo "<li>📝 Select a content item (P001-P006)</li>";
echo "<li>⭐ Choose a review level (L1-L5)</li>";
echo "<li>💾 Click \"검수 완료\" button</li>";
echo "<li>🔄 Refresh this page to see new review in database</li>";
echo "<li>📊 Check browser console (F12) for debug messages</li>";
echo "</ol>";

echo "<p><em>Verified at " . date('Y-m-d H:i:s') . "</em></p>";
?>
