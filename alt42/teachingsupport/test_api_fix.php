<?php
// Test script to check if get_student_messages.php API works
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? $USER->id;

echo "<h2>API Test for student_inbox.php</h2>";
echo "<p>Testing student ID: $studentid</p>";
echo "<p>User ID: {$USER->id}</p>";

// Test 1: Regular messages (Direct PHP call)
echo "<h3>Test 1: Regular Messages (Direct API Test)</h3>";
try {
    // Simulate the API call directly
    $_GET['studentid'] = $studentid;
    $_GET['page'] = 0;
    $_GET['perpage'] = 10;
    
    ob_start();
    include 'get_student_messages.php';
    $result1 = ob_get_clean();
    
    echo "<p>✅ API call successful</p>";
    echo "<pre>" . htmlspecialchars($result1) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ API call failed: " . $e->getMessage() . "</p>";
}

// Test 2: Read-only messages (Direct PHP call)
echo "<h3>Test 2: Read-Only Messages (Archive) - Direct API Test</h3>";
try {
    // Reset GET parameters and simulate archive API call
    $_GET = array();
    $_GET['studentid'] = $studentid;
    $_GET['page'] = 0;
    $_GET['perpage'] = 50;
    $_GET['read_only'] = 1;
    
    ob_start();
    include 'get_student_messages.php';
    $result2 = ob_get_clean();
    
    echo "<p>✅ Archive API call successful</p>";
    echo "<pre>" . htmlspecialchars($result2) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Archive API call failed: " . $e->getMessage() . "</p>";
}

// Test 3: Database connectivity
echo "<h3>Test 3: Database Tables Check</h3>";
$tables_to_check = [
    'ktm_teaching_interactions',
    'ktm_interaction_read_status'
];

foreach ($tables_to_check as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "<p>Table {$table}: " . ($exists ? "EXISTS" : "NOT EXISTS") . "</p>";
    
    if ($exists) {
        try {
            if ($table == 'ktm_interaction_read_status') {
                $count = $DB->count_records($table, array('student_id' => $studentid));
            } else {
                $count = $DB->count_records($table, array('userid' => $studentid));
            }
            echo "<p>Records for student $studentid: $count</p>";
        } catch (Exception $e) {
            echo "<p>Error counting records: " . $e->getMessage() . "</p>";
        }
    }
}
?>