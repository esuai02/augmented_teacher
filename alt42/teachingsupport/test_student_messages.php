<?php
// Test script for student messages
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Student Messages Test</h1>";

try {
    // Check if user is logged in
    if (!isset($USER) || $USER->id <= 0) {
        echo "<p style='color: red;'>Error: User not logged in. Please log in to Moodle first.</p>";
        echo "<p><a href='/moodle/login/index.php'>Login to Moodle</a></p>";
        exit;
    }
    
    echo "<p>User ID: " . $USER->id . " (" . fullname($USER) . ")</p>";
    
    // Test the API
    $test_url = "get_student_messages.php?studentid=" . $USER->id . "&page=0&perpage=5";
    echo "<h2>Testing API Call</h2>";
    echo "<p>API URL: <a href='$test_url' target='_blank'>$test_url</a></p>";
    
    // Make the API call
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] . "\r\n"
        ]
    ]);
    
    $response = file_get_contents($test_url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>Failed to call API</p>";
    } else {
        echo "<h3>API Response:</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        $data = json_decode($response, true);
        if ($data) {
            echo "<h3>Parsed Response:</h3>";
            echo "<ul>";
            echo "<li>Success: " . ($data['success'] ? 'Yes' : 'No') . "</li>";
            
            if (isset($data['error'])) {
                echo "<li style='color: red;'>Error: " . htmlspecialchars($data['error']) . "</li>";
            }
            
            if (isset($data['messages'])) {
                echo "<li>Messages count: " . count($data['messages']) . "</li>";
            }
            
            if (isset($data['stats'])) {
                echo "<li>Stats: Total=" . $data['stats']['total'] . ", Unread=" . $data['stats']['unread'] . ", Read=" . $data['stats']['read'] . "</li>";
            }
            
            if (isset($data['notice'])) {
                echo "<li style='color: orange;'>Notice: " . htmlspecialchars($data['notice']) . "</li>";
            }
            
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>Failed to parse JSON response</p>";
        }
    }
    
    // Test database connectivity
    echo "<h2>Database Test</h2>";
    $tables = $DB->get_tables();
    $message_tables = array_filter($tables, function($table) {
        return strpos($table, 'message') !== false;
    });
    
    echo "<p>Message-related tables found:</p>";
    echo "<ul>";
    foreach($message_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Test link to inbox
    echo "<h2>Test Inbox</h2>";
    $inbox_url = "student_inbox.php?studentid=" . $USER->id;
    echo "<p><a href='$inbox_url' target='_blank'>Open Student Inbox</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Run Database Debug</a></p>";
?>