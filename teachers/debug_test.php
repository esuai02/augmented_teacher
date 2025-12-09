<?php
// 긴급 진단 파일 - HTTP 500 오류 추적용
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

echo "<h1>Emergency Diagnostic Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";

// 1. Moodle config 테스트
echo "<h2>1. Moodle Config Test</h2>";
try {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        echo "✅ Moodle config file exists<br>";
        include_once("/home/moodle/public_html/moodle/config.php");
        echo "✅ Moodle config loaded successfully<br>";
        
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

// 2. OpenAI config 테스트
echo "<h2>2. OpenAI Config Test</h2>";
try {
    $openai_path = dirname(__FILE__) . "/openai_config.php";
    if (file_exists($openai_path)) {
        echo "✅ OpenAI config file exists: $openai_path<br>";
        include_once($openai_path);
        echo "✅ OpenAI config loaded successfully<br>";
        
        if (function_exists('createAnalysisCacheTable')) {
            echo "✅ createAnalysisCacheTable function exists<br>";
        } else {
            echo "❌ createAnalysisCacheTable function not found<br>";
        }
    } else {
        echo "❌ OpenAI config file not found: $openai_path<br>";
    }
} catch (Exception $e) {
    echo "❌ OpenAI config error: " . $e->getMessage() . "<br>";
}

// 3. 메모리 사용량 테스트
echo "<h2>3. Memory Usage Test</h2>";
echo "Current memory usage: " . memory_get_usage(true) . " bytes<br>";
echo "Peak memory usage: " . memory_get_peak_usage(true) . " bytes<br>";

// 4. 파일 크기 및 권한 테스트
echo "<h2>4. File System Test</h2>";
$main_file = dirname(__FILE__) . "/timescaffolding42.php";
if (file_exists($main_file)) {
    echo "✅ Main file exists<br>";
    echo "File size: " . filesize($main_file) . " bytes<br>";
    echo "File readable: " . (is_readable($main_file) ? "Yes" : "No") . "<br>";
} else {
    echo "❌ Main file not found<br>";
}

echo "<h2>5. Include Path Test</h2>";
echo "Include path: " . get_include_path() . "<br>";
echo "Current directory: " . getcwd() . "<br>";
echo "Script directory: " . dirname(__FILE__) . "<br>";

echo "<p><strong>Test completed at: " . date('Y-m-d H:i:s') . "</strong></p>";
?>