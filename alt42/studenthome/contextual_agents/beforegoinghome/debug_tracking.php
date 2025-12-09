<?php
// Debug script to check tracking table for pomodoro data
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

$studentId = 1793;
$currentTime = time();
$pomodoro12hStart = $currentTime - 43200; // 12 hours ago

echo "<h2>Debug Tracking Data for User $studentId</h2>";
echo "<p>Current Time: " . date('Y-m-d H:i:s', $currentTime) . " (Unix: $currentTime)</p>";
echo "<p>12h Start: " . date('Y-m-d H:i:s', $pomodoro12hStart) . " (Unix: $pomodoro12hStart)</p>";
echo "<hr>";

// Check mdl_abessi_tracking table
echo "<h3>mdl_abessi_tracking Records (Last 24h)</h3>";
$trackingRecords = $DB->get_records_sql("
    SELECT *
    FROM mdl_abessi_tracking
    WHERE userid = ?
    AND timecreated >= ?
    AND hide = 0
    ORDER BY id DESC
    LIMIT 20
", [$studentId, $currentTime - 86400]);

if (empty($trackingRecords)) {
    echo "<p style='color: red;'>No tracking records found in last 24h!</p>";
} else {
    echo "<p style='color: green;'>Found " . count($trackingRecords) . " tracking records</p>";
    echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr>";
    echo "<th>ID</th><th>Timecreated</th><th>Date</th><th>Hours Ago</th><th>Within 12h?</th>";
    echo "<th>Status</th><th>Duration</th><th>Result</th><th>Content (truncated)</th>";
    echo "</tr>";

    foreach ($trackingRecords as $rec) {
        $hoursAgo = ($currentTime - $rec->timecreated) / 3600;
        $within12h = ($rec->timecreated >= $pomodoro12hStart) ? 'YES' : 'NO';
        $content = isset($rec->content) ? substr($rec->content, 0, 50) : 'N/A';

        echo "<tr>";
        echo "<td>$rec->id</td>";
        echo "<td>$rec->timecreated</td>";
        echo "<td>" . date('Y-m-d H:i:s', $rec->timecreated) . "</td>";
        echo "<td>" . round($hoursAgo, 2) . "h</td>";
        echo "<td style='color: " . ($within12h === 'YES' ? 'green' : 'red') . ";'><strong>$within12h</strong></td>";
        echo "<td>" . (isset($rec->status) ? $rec->status : 'N/A') . "</td>";
        echo "<td>" . (isset($rec->duration) ? $rec->duration : 'N/A') . "</td>";
        echo "<td>" . (isset($rec->result) ? $rec->result : 'N/A') . "</td>";
        echo "<td>$content...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";

// Check with 12h filter
echo "<h3>Tracking Records Within 12h</h3>";
$tracking12h = $DB->get_records_sql("
    SELECT *
    FROM mdl_abessi_tracking
    WHERE userid = ?
    AND timecreated >= ?
    AND hide = 0
    ORDER BY id DESC
", [$studentId, $pomodoro12hStart]);

if (empty($tracking12h)) {
    echo "<p style='color: red;'>No records within 12h!</p>";
} else {
    echo "<p style='color: green;'>Found " . count($tracking12h) . " records within 12h</p>";
    echo "<h4>Sample Records:</h4>";
    $count = 0;
    foreach ($tracking12h as $rec) {
        if ($count >= 5) break;
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>ID:</strong> $rec->id | <strong>Time:</strong> " . date('Y-m-d H:i:s', $rec->timecreated) . "</p>";
        echo "<p><strong>Status:</strong> " . (isset($rec->status) ? $rec->status : 'N/A') . " | ";
        echo "<strong>Duration:</strong> " . (isset($rec->duration) ? $rec->duration : 'N/A') . " | ";
        echo "<strong>Result:</strong> " . (isset($rec->result) ? $rec->result : 'N/A') . "</p>";
        if (isset($rec->content)) {
            echo "<p><strong>Content:</strong> " . htmlspecialchars(substr($rec->content, 0, 200)) . "...</p>";
        }
        echo "</div>";
        $count++;
    }
}

echo "<hr>";
echo "<h3>Table Structure Analysis</h3>";
echo "<p>Checking if table has required fields for Pomodoro diary...</p>";

// Get one record to see structure
$oneRecord = $DB->get_record_sql("
    SELECT *
    FROM mdl_abessi_tracking
    WHERE userid = ?
    ORDER BY id DESC
    LIMIT 1
", [$studentId]);

if ($oneRecord) {
    echo "<p>Available fields:</p><ul>";
    foreach ($oneRecord as $field => $value) {
        echo "<li><strong>$field:</strong> " . gettype($value) . "</li>";
    }
    echo "</ul>";
}
?>
