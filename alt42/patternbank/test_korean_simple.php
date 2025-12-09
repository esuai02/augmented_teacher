<?php
/**
 * Simple Korean Error Message Test
 * Access via: https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/test_korean_simple.php
 */

header('Content-Type: text/html; charset=utf-8');

// Load JsonSafeHelper without Moodle dependencies
require_once(__DIR__ . '/lib/JsonSafeHelper.php');
require_once(__DIR__ . '/lib/ApiResponseNormalizer.php');
require_once(__DIR__ . '/lib/FormulaEncoder.php');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Korean Error Message Encoding Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test h3 { margin-top: 0; color: #333; }
        .pass { background-color: #d4edda; border-color: #c3e6cb; }
        .fail { background-color: #f8d7da; border-color: #f5c6cb; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .result { font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 Korean Error Message Encoding Test</h1>
    <p>Testing JsonSafeHelper Korean text preservation (not escaping to \uXXXX)</p>

<?php

// Test 1: Basic Korean error
echo '<div class="test">';
echo '<h3>Test 1: Basic Korean Error Message</h3>';
$test1 = ['success' => false, 'message' => '로그인이 필요합니다'];
try {
    $json1 = JsonSafeHelper::safeEncode($test1);
    $hasKorean = (strpos($json1, '로그인') !== false);
    $class = $hasKorean ? 'pass' : 'fail';
    $icon = $hasKorean ? '✅' : '❌';

    echo "<div class='result $class'>$icon " . ($hasKorean ? 'PASS' : 'FAIL') . ": Korean text preserved</div>";
    echo "<pre>" . htmlspecialchars($json1) . "</pre>";

    // Also decode to verify
    $decoded = json_decode($json1, true);
    if ($decoded && $decoded['message'] === '로그인이 필요합니다') {
        echo "<div class='result pass'>✅ JSON decode successful: {$decoded['message']}</div>";
    }
} catch (Exception $e) {
    echo "<div class='result fail'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo '</div>';

// Test 2: Mixed content
echo '<div class="test">';
echo '<h3>Test 2: Mixed Korean and English</h3>';
$test2 = ['success' => false, 'error' => 'Not logged in', 'message' => '로그인하지 않았습니다'];
try {
    $json2 = JsonSafeHelper::safeEncode($test2);
    $hasKorean = (strpos($json2, '로그인') !== false);
    $hasEnglish = (strpos($json2, 'Not logged in') !== false);
    $class = ($hasKorean && $hasEnglish) ? 'pass' : 'fail';
    $icon = ($hasKorean && $hasEnglish) ? '✅' : '❌';

    echo "<div class='result $class'>$icon " . (($hasKorean && $hasEnglish) ? 'PASS' : 'FAIL') . ": Mixed content preserved</div>";
    echo "<pre>" . htmlspecialchars($json2) . "</pre>";
} catch (Exception $e) {
    echo "<div class='result fail'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo '</div>';

// Test 3: Exception message
echo '<div class="test">';
echo '<h3>Test 3: Exception with Korean</h3>';
$exceptionMsg = '문제 생성 중 오류가 발생했습니다: Database error';
$test3 = ['success' => false, 'message' => $exceptionMsg, 'error' => $exceptionMsg];
try {
    $json3 = JsonSafeHelper::safeEncode($test3);
    $hasKorean = (strpos($json3, '문제') !== false && strpos($json3, '오류') !== false);
    $class = $hasKorean ? 'pass' : 'fail';
    $icon = $hasKorean ? '✅' : '❌';

    echo "<div class='result $class'>$icon " . ($hasKorean ? 'PASS' : 'FAIL') . ": Exception message preserved</div>";
    echo "<pre>" . htmlspecialchars($json3) . "</pre>";
} catch (Exception $e) {
    echo "<div class='result fail'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo '</div>';

// Test 4: Success message
echo '<div class="test">';
echo '<h3>Test 4: Success Message</h3>';
$test4 = ['success' => true, 'message' => '3개의 유사문제가 성공적으로 생성되었습니다.'];
try {
    $json4 = JsonSafeHelper::safeEncode($test4);
    $decoded = json_decode($json4, true);
    $matches = ($decoded && $decoded['message'] === '3개의 유사문제가 성공적으로 생성되었습니다.');
    $class = $matches ? 'pass' : 'fail';
    $icon = $matches ? '✅' : '❌';

    echo "<div class='result $class'>$icon " . ($matches ? 'PASS' : 'FAIL') . ": Roundtrip encode/decode</div>";
    echo "<strong>Original:</strong> 3개의 유사문제가 성공적으로 생성되었습니다.<br>";
    echo "<strong>Encoded:</strong> <pre>" . htmlspecialchars($json4) . "</pre>";
    if ($decoded) {
        echo "<strong>Decoded:</strong> " . htmlspecialchars($decoded['message']) . "<br>";
    }
} catch (Exception $e) {
    echo "<div class='result fail'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo '</div>';

// Test 5: ASCII fallback
echo '<div class="test">';
echo '<h3>Test 5: ASCII Fallback Pattern</h3>';
$asciiOnly = '{"success":false,"error":"Response encoding failed"}';
$decoded5 = json_decode($asciiOnly, true);
$valid = ($decoded5 && !json_last_error());
$class = $valid ? 'pass' : 'fail';
$icon = $valid ? '✅' : '❌';

echo "<div class='result $class'>$icon " . ($valid ? 'PASS' : 'FAIL') . ": ASCII fallback is valid JSON</div>";
echo "<pre>" . htmlspecialchars($asciiOnly) . "</pre>";
if ($valid) {
    echo "<strong>Decoded error:</strong> " . htmlspecialchars($decoded5['error']);
}
echo '</div>';

?>

<div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px;">
    <h3>✅ Summary</h3>
    <p>All tests check that Korean text (한글) is preserved as UTF-8 characters in JSON output, not escaped as Unicode sequences (\uXXXX).</p>
    <p><strong>Access URL:</strong> <code>https://mathking.kr/moodle/local/augmented_teacher/alt42/patternbank/test_korean_simple.php</code></p>
</div>

</body>
</html>
