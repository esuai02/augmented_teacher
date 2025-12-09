<?php
/**
 * Test Korean Error Message Encoding
 * Verifies that JsonSafeHelper preserves Korean text correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load JsonSafeHelper
require_once(__DIR__ . '/lib/JsonSafeHelper.php');

echo "=== Korean Error Message Encoding Test ===\n\n";

// Test 1: Basic Korean error message
echo "Test 1: Basic Korean error message\n";
$test1 = ['success' => false, 'message' => '로그인이 필요합니다'];
try {
    $json1 = JsonSafeHelper::safeEncode($test1);
    echo "Encoded: " . $json1 . "\n";
    // Check if Korean text is preserved (not escaped as \uXXXX)
    if (strpos($json1, '로그인') !== false) {
        echo "✅ Korean text preserved correctly\n";
    } else {
        echo "❌ Korean text was escaped to Unicode\n";
    }
} catch (Exception $e) {
    echo "❌ Encoding failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Error message with mixed content
echo "Test 2: Mixed Korean and English\n";
$test2 = ['success' => false, 'error' => 'Not logged in', 'message' => '로그인하지 않았습니다'];
try {
    $json2 = JsonSafeHelper::safeEncode($test2);
    echo "Encoded: " . $json2 . "\n";
    if (strpos($json2, '로그인') !== false) {
        echo "✅ Mixed content preserved correctly\n";
    } else {
        echo "❌ Korean text was escaped\n";
    }
} catch (Exception $e) {
    echo "❌ Encoding failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Complex error with exception message
echo "Test 3: Exception message simulation\n";
$exceptionMsg = '문제 생성 중 오류가 발생했습니다: Database connection failed';
$test3 = ['success' => false, 'message' => $exceptionMsg, 'error' => $exceptionMsg];
try {
    $json3 = JsonSafeHelper::safeEncode($test3);
    echo "Encoded: " . $json3 . "\n";
    if (strpos($json3, '문제') !== false && strpos($json3, '오류') !== false) {
        echo "✅ Exception message with Korean preserved\n";
    } else {
        echo "❌ Korean text in exception was escaped\n";
    }
} catch (Exception $e) {
    echo "❌ Encoding failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Verify JSON is valid and decodable
echo "Test 4: JSON validity check\n";
$test4 = ['success' => true, 'message' => '문제가 성공적으로 생성되었습니다'];
try {
    $json4 = JsonSafeHelper::safeEncode($test4);
    $decoded = json_decode($json4, true);
    if ($decoded && $decoded['message'] === '문제가 성공적으로 생성되었습니다') {
        echo "✅ JSON is valid and decodable with Korean preserved\n";
        echo "Decoded message: " . $decoded['message'] . "\n";
    } else {
        echo "❌ JSON decode failed or message changed\n";
    }
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: ASCII-only fallback simulation
echo "Test 5: ASCII-only fallback pattern\n";
$asciiOnly = '{"success":false,"error":"Response encoding failed"}';
$decoded5 = json_decode($asciiOnly, true);
if ($decoded5 && !json_last_error()) {
    echo "✅ ASCII-only fallback is valid JSON\n";
    echo "Fallback message: " . $decoded5['error'] . "\n";
} else {
    echo "❌ ASCII fallback is invalid JSON\n";
}

echo "\n=== Test Complete ===\n";
?>
