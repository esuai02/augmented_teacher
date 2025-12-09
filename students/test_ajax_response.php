<?php
/**
 * Test AJAX Endpoint Response
 * Shows exactly what contentsreview_ajax.php returns
 */

echo "<h1>AJAX Endpoint Response Test</h1>";
echo "<hr>";

// Simulate GET request
$_GET['action'] = 'get_review';
$_GET['contentsid'] = '29596';
$_POST = array();

echo "<h2>Test 1: GET Review (contentsid=29596)</h2>";
echo "<p><strong>Request:</strong> GET action=get_review&contentsid=29596</p>";
echo "<h3>Raw Response:</h3>";
echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ccc; white-space:pre-wrap; word-wrap:break-word;'>";

ob_start();
include('contentsreview_ajax.php');
$response = ob_get_clean();

echo htmlspecialchars($response);
echo "</pre>";

echo "<h3>Response Length:</h3>";
echo "<p>" . strlen($response) . " characters</p>";

echo "<h3>JSON Validation:</h3>";
$decoded = json_decode($response);
if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "<p style='color:red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
    echo "<p>Error code: " . json_last_error() . "</p>";

    // Show character at error position
    echo "<h3>Character Analysis:</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(strlen($response), 800); $i++) {
        $char = $response[$i];
        if ($char === "\n") {
            echo "[$i] \\n (newline)\n";
        } elseif ($char === "\r") {
            echo "[$i] \\r (carriage return)\n";
        } elseif ($char === "\t") {
            echo "[$i] \\t (tab)\n";
        } elseif (ctype_print($char)) {
            echo "[$i] $char\n";
        } else {
            echo "[$i] [" . ord($char) . "] (non-printable)\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color:green;'>✅ Valid JSON</p>";
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
}

echo "<hr>";
echo "<h2>Test 2: Check for PHP Errors/Warnings</h2>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$_POST = array(
    'action' => 'submit_review',
    'contentsid' => '29596',
    'cmid' => '87712',
    'pagenum' => '1',
    'review_level' => 'L4',
    'feedback' => 'Test feedback',
    'improvements' => 'Test improvements',
    'student_id' => '2',
    'wboard_id' => 'test_wboard'
);
$_GET = array();

echo "<p><strong>Request:</strong> POST submit_review</p>";
echo "<h3>Raw Response:</h3>";
echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ccc;'>";

ob_start();
include('contentsreview_ajax.php');
$response2 = ob_get_clean();

echo htmlspecialchars($response2);
echo "</pre>";

echo "<h3>JSON Validation:</h3>";
$decoded2 = json_decode($response2);
if ($decoded2 === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "<p style='color:red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
} else {
    echo "<p style='color:green;'>✅ Valid JSON</p>";
    echo "<pre>";
    print_r($decoded2);
    echo "</pre>";
}
?>
