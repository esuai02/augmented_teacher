<?php
// Debug script to check pomodoro data
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

$studentId = 1793; // From the URL

$currentTime = time();
$pomodoro12hStart = $currentTime - 43200; // 12 hours ago

echo "<h2>Debug Pomodoro Data for User $studentId</h2>";
echo "<p>Current Time: " . date('Y-m-d H:i:s', $currentTime) . " (Unix: $currentTime)</p>";
echo "<p>12h Start: " . date('Y-m-d H:i:s', $pomodoro12hStart) . " (Unix: $pomodoro12hStart)</p>";
echo "<hr>";

// Get all recent records without time filter
echo "<h3>All Recent Records (Top 5, No Time Filter)</h3>";
$allRecords = $DB->get_records_sql("
    SELECT
        id, userid, timecreated,
        status01, status02, status03, status04, status05, status06, status07, status08,
        status09, status10, status11, status12, status13, status14, status15, status16
    FROM mdl_abessi_todayplans
    WHERE userid = ?
    ORDER BY id DESC
    LIMIT 5
", [$studentId]);

if (empty($allRecords)) {
    echo "<p style='color: red;'>No records found at all!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Timecreated</th><th>Date</th><th>Hours Ago</th><th>Within 12h?</th><th>Status Fields</th></tr>";
    foreach ($allRecords as $rec) {
        $hoursAgo = ($currentTime - $rec->timecreated) / 3600;
        $within12h = ($rec->timecreated >= $pomodoro12hStart) ? 'YES' : 'NO';
        $statusCount = 0;
        for ($i = 1; $i <= 16; $i++) {
            $field = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!empty($rec->$field)) {
                $statusCount++;
            }
        }
        echo "<tr>";
        echo "<td>$rec->id</td>";
        echo "<td>$rec->timecreated</td>";
        echo "<td>" . date('Y-m-d H:i:s', $rec->timecreated) . "</td>";
        echo "<td>" . round($hoursAgo, 2) . "h</td>";
        echo "<td style='color: " . ($within12h === 'YES' ? 'green' : 'red') . ";'><strong>$within12h</strong></td>";
        echo "<td>$statusCount non-empty fields</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";

// Try the exact query from the code
echo "<h3>Query with 12h Filter (Exact from Code)</h3>";
$query12h = $DB->get_record_sql("
    SELECT
        status01, status02, status03, status04, status05, status06, status07, status08,
        status09, status10, status11, status12, status13, status14, status15, status16,
        timecreated,
        id
    FROM mdl_abessi_todayplans
    WHERE userid = ? AND timecreated >= ?
    ORDER BY id DESC
    LIMIT 1
", [$studentId, $pomodoro12hStart]);

if ($query12h) {
    echo "<p style='color: green;'>Found record: ID={$query12h->id}, timecreated=" . date('Y-m-d H:i:s', $query12h->timecreated) . "</p>";
    echo "<h4>Status Fields:</h4>";
    echo "<ul>";
    for ($i = 1; $i <= 16; $i++) {
        $field = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
        if (!empty($query12h->$field)) {
            echo "<li>Slot $i: {$query12h->$field}</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>No record found with 12h filter!</p>";
}

echo "<hr>";
echo "<h3>Variables that would be passed to JavaScript:</h3>";
echo "<pre>";

// Simulate the PHP processing
$pomodoroDiaryItems = [];
$pomodoroTotalCount = 0;
$pomodoroSatisfactionCount = 0;
$pomodoroSatisfactionSum = 0;

if ($query12h) {
    $diary = $query12h;
    $satisfactionMap = [
        '매우만족' => 3,
        '만족' => 2,
        '불만족' => 1
    ];

    for ($i = 1; $i <= 16; $i++) {
        $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $fieldValue = $diary->$statusField;

        if (!empty($fieldValue)) {
            $satisfaction = isset($satisfactionMap[$fieldValue])
                ? $satisfactionMap[$fieldValue]
                : null;

            $pomodoroDiaryItems[] = [
                'slot' => $i,
                'status' => $fieldValue,
                'satisfaction' => $satisfaction
            ];

            if ($satisfaction !== null) {
                $pomodoroSatisfactionSum += $satisfaction;
                $pomodoroSatisfactionCount++;
            }
            $pomodoroTotalCount++;
        }
    }
}

echo "pomodoroTotalCount = $pomodoroTotalCount\n";
echo "pomodoroSatisfactionCount = $pomodoroSatisfactionCount\n";
echo "pomodoroDiaryItems count = " . count($pomodoroDiaryItems) . "\n";
echo "\npomodoroDiaryItems JSON:\n";
echo json_encode($pomodoroDiaryItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";
?>
