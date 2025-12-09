<?php
// Test script for chatbot functionality
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php");
global $DB, $USER;
require_login();

echo "<h2>Chatbot System Test</h2>";
echo "<pre>";

// 1. Check if config.php exists and API key is set
echo "1. Checking API Configuration:\n";
if (defined('OPENAI_API_KEY')) {
    $key_length = strlen(OPENAI_API_KEY);
    if ($key_length > 10) {
        echo "   ✅ API Key is configured (length: $key_length)\n";
    } else {
        echo "   ⚠️ API Key seems too short\n";
    }
} else {
    echo "   ❌ OPENAI_API_KEY not defined\n";
}

if (defined('OPENAI_MODEL')) {
    echo "   ✅ Model: " . OPENAI_MODEL . "\n";
} else {
    echo "   ❌ OPENAI_MODEL not defined\n";
}

// 2. Check database tables
echo "\n2. Checking Database Tables:\n";
$tables = ['chatbot_messages', 'chatbot_preferences'];
foreach ($tables as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    if ($exists) {
        echo "   ✅ Table '{$table}' exists\n";
        
        // Count records
        try {
            $count = $DB->count_records($table);
            echo "      Records: $count\n";
        } catch (Exception $e) {
            echo "      ⚠️ Could not count records\n";
        }
    } else {
        echo "   ❌ Table '{$table}' does not exist\n";
        echo "      Run: execute_chatbot_sql.php to create tables\n";
    }
}

// 3. Check persona_modes table
echo "\n3. Checking Persona Modes:\n";
$persona_exists = $DB->get_manager()->table_exists('persona_modes');
if ($persona_exists) {
    echo "   ✅ persona_modes table exists\n";
    
    // Get current user's mode
    try {
        $mode = $DB->get_record_sql(
            "SELECT * FROM {persona_modes} WHERE student_id = :studentid ORDER BY timecreated DESC LIMIT 1",
            array('studentid' => $USER->id)
        );
        
        if ($mode) {
            echo "   ✅ Current mode: " . $mode->student_mode . "\n";
        } else {
            echo "   ℹ️ No mode selected for current user\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Error fetching mode: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ persona_modes table does not exist\n";
}

// 4. Test API endpoint accessibility
echo "\n4. Testing API Endpoint:\n";
$api_url = '/moodle/local/augmented_teacher/alt42/studenthome/chatbot_api.php';
echo "   Endpoint: $api_url\n";

// Test with a simple request
$test_data = [
    'action' => 'send_message',
    'student_id' => $USER->id,
    'learning_mode' => 'curriculum',
    'message' => 'Test message'
];

echo "   Sending test request...\n";

// 5. Test OpenAI API connection (optional)
echo "\n5. Testing OpenAI API Connection:\n";
if (defined('OPENAI_API_KEY') && strlen(OPENAI_API_KEY) > 10) {
    echo "   Testing connection to OpenAI...\n";
    
    // Simple test request
    $test_messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say "Hello, I am working!" in Korean.']
    ];
    
    $data = [
        'model' => OPENAI_MODEL ?? 'gpt-4o',
        'messages' => $test_messages,
        'max_tokens' => 50
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ❌ CURL Error: $curl_error\n";
    } elseif ($http_code === 200) {
        echo "   ✅ OpenAI API connection successful!\n";
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo "   Response: " . $result['choices'][0]['message']['content'] . "\n";
        }
    } else {
        echo "   ❌ OpenAI API error (HTTP $http_code)\n";
        $error_data = json_decode($response, true);
        if (isset($error_data['error']['message'])) {
            echo "   Error: " . $error_data['error']['message'] . "\n";
        }
    }
} else {
    echo "   ⚠️ Skipping - API key not configured\n";
}

echo "</pre>";

// Provide action links
echo "<h3>Actions:</h3>";
echo "<ul>";
if (!$DB->get_manager()->table_exists('chatbot_messages')) {
    echo "<li><a href='execute_chatbot_sql.php'>Create Database Tables</a></li>";
}
echo "<li><a href='index.php?userid=" . $USER->id . "'>Go to Main Page</a></li>";
echo "<li><a href='selectmode.php?userid=" . $USER->id . "'>Select Learning Mode</a></li>";
echo "</ul>";

echo "<h3>Troubleshooting Tips:</h3>";
echo "<ol>";
echo "<li>If tables don't exist, run <code>execute_chatbot_sql.php</code></li>";
echo "<li>If API key is not configured, edit <code>config.php</code></li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Ensure you're logged in to Moodle</li>";
echo "<li>Check server error logs for PHP errors</li>";
echo "</ol>";
?>