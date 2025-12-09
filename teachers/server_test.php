<?php
// Minimal server test for timescaffolding42.php components
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

echo "Server Test - " . date('Y-m-d H:i:s') . "<br><br>";

// Test 1: Basic PHP functionality
echo "1. PHP Version: " . phpversion() . " ✅<br>";
echo "2. Memory Limit: " . ini_get('memory_limit') . " ✅<br>";

// Test 2: Try to include Moodle config
echo "<br><strong>Testing Moodle Config:</strong><br>";
try {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
        echo "✅ Moodle config loaded<br>";
        
        if (isset($DB)) {
            echo "✅ Database object available<br>";
        } else {
            echo "❌ Database object not available<br>";
        }
        
        if (isset($USER)) {
            echo "✅ User object available<br>";
        } else {
            echo "❌ User object not available<br>";
        }
    } else {
        echo "❌ Moodle config file not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Moodle config error: " . $e->getMessage() . "<br>";
}

// Test 3: Try to include OpenAI config
echo "<br><strong>Testing OpenAI Config:</strong><br>";
try {
    $openai_path = dirname(__FILE__) . "/openai_config.php";
    if (file_exists($openai_path)) {
        include_once($openai_path);
        echo "✅ OpenAI config file found and loaded<br>";
        
        // Test for caching functions
        if (function_exists('createAnalysisCacheTable')) {
            echo "✅ Cache functions available<br>";
        } else {
            echo "❌ Cache functions not found<br>";
        }
    } else {
        echo "❌ OpenAI config file not found at: $openai_path<br>";
    }
} catch (Exception $e) {
    echo "❌ OpenAI config error: " . $e->getMessage() . "<br>";
}

// Test 4: File permissions
echo "<br><strong>Testing File Access:</strong><br>";
$main_file = dirname(__FILE__) . "/timescaffolding42.php";
if (file_exists($main_file)) {
    echo "✅ Main file exists<br>";
    echo "✅ File readable: " . (is_readable($main_file) ? "Yes" : "No") . "<br>";
    echo "✅ File size: " . number_format(filesize($main_file)) . " bytes<br>";
} else {
    echo "❌ Main file not found<br>";
}

echo "<br><strong>Test Summary:</strong><br>";
echo "🔧 Basic server components check completed<br>";
echo "📁 Current directory: " . getcwd() . "<br>";
echo "📁 Script directory: " . dirname(__FILE__) . "<br>";
echo "<br>If all tests pass, timescaffolding42.php should load without HTTP 500 error.<br>";
?>