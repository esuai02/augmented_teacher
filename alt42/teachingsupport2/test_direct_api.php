<?php
// Direct API test without HTTP requests
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? $USER->id;

echo "<h2>Direct API Test Results</h2>";
echo "<p>Testing Student ID: $studentid</p>";
echo "<p>Current User ID: {$USER->id}</p>";

// Test database access directly
echo "<h3>Database Connection Test</h3>";
try {
    $count_interactions = $DB->count_records('ktm_teaching_interactions', array('userid' => $studentid));
    echo "<p>✅ ktm_teaching_interactions records: $count_interactions</p>";
    
    $count_read_status = $DB->count_records('ktm_interaction_read_status', array('student_id' => $studentid));
    echo "<p>✅ ktm_interaction_read_status records: $count_read_status</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test regular messages query
echo "<h3>Regular Messages Query Test</h3>";
try {
    $sql = "SELECT * FROM {ktm_teaching_interactions} 
            WHERE userid = :studentid 
            AND status = 'completed' 
            AND solution_text IS NOT NULL 
            ORDER BY timecreated DESC";
    $params = array('studentid' => $studentid);
    $messages = $DB->get_records_sql($sql, $params, 0, 10);
    
    echo "<p>✅ Found " . count($messages) . " regular messages</p>";
    foreach($messages as $msg) {
        echo "<p>- Message ID: {$msg->id}, Created: " . date('Y-m-d H:i', $msg->timecreated) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Regular messages query error: " . $e->getMessage() . "</p>";
}

// Test read-only messages query
echo "<h3>Read-Only Messages Query Test</h3>";
try {
    if ($DB->get_manager()->table_exists('ktm_interaction_read_status')) {
        $sql = "SELECT ti.* FROM {ktm_teaching_interactions} ti
                INNER JOIN {ktm_interaction_read_status} rs 
                     ON ti.id = rs.interaction_id 
                WHERE ti.userid = :studentid 
                AND ti.status = 'completed' 
                AND ti.solution_text IS NOT NULL 
                AND rs.student_id = :studentid2 
                AND rs.is_read = 1 
                ORDER BY ti.timecreated DESC";
        $params = array('studentid' => $studentid, 'studentid2' => $studentid);
        $read_messages = $DB->get_records_sql($sql, $params, 0, 50);
        
        echo "<p>✅ Found " . count($read_messages) . " read messages</p>";
        foreach($read_messages as $msg) {
            echo "<p>- Read Message ID: {$msg->id}, Created: " . date('Y-m-d H:i', $msg->timecreated) . "</p>";
        }
    } else {
        echo "<p>⚠️ Read status table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Read messages query error: " . $e->getMessage() . "</p>";
}

// Test JSON response format
echo "<h3>JSON Response Format Test</h3>";
try {
    $response = array(
        'success' => true,
        'messages' => array(),
        'stats' => array(
            'total' => $count_interactions,
            'read' => $count_read_status,
            'unread' => $count_interactions - $count_read_status
        )
    );
    
    echo "<p>✅ JSON Response Format:</p>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ JSON format error: " . $e->getMessage() . "</p>";
}
?>