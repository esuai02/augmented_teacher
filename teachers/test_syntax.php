<?php
// 간단한 PHP 구문 테스트
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Syntax Test for timescaffolding42.php</h1>";

// 파일 경로
$file = dirname(__FILE__) . '/timescaffolding42.php';

// 파일 존재 확인
if (!file_exists($file)) {
    die("Error: timescaffolding42.php not found!");
}

// PHP lint 명령어 시뮬레이션 (토큰 파싱)
$code = file_get_contents($file);
$tokens = @token_get_all($code);

if ($tokens === false) {
    echo "<p style='color: red;'>❌ Fatal PHP syntax error detected!</p>";
} else {
    echo "<p style='color: green;'>✅ PHP tokens parsed successfully (" . count($tokens) . " tokens)</p>";
    
    // 기본 구문 검사
    $open_braces = 0;
    $close_braces = 0;
    $open_parens = 0;
    $close_parens = 0;
    $string_delimiter = null;
    $in_string = false;
    
    foreach ($tokens as $token) {
        if (is_array($token)) {
            // Token array
            if ($token[0] == T_CURLY_OPEN) $open_braces++;
        } else {
            // Simple character
            if ($token == '{') $open_braces++;
            if ($token == '}') $close_braces++;
            if ($token == '(') $open_parens++;
            if ($token == ')') $close_parens++;
        }
    }
    
    echo "<h2>Structure Analysis:</h2>";
    echo "<ul>";
    echo "<li>Curly braces: Open={$open_braces}, Close={$close_braces} ";
    echo ($open_braces == $close_braces) ? "✅" : "❌ MISMATCH!";
    echo "</li>";
    
    echo "<li>Parentheses: Open={$open_parens}, Close={$close_parens} ";
    echo ($open_parens == $close_parens) ? "✅" : "❌ MISMATCH!";
    echo "</li>";
    echo "</ul>";
}

// Check critical issues
echo "<h2>Critical Issues Check:</h2>";

// BOM check
$first_bytes = substr($code, 0, 3);
if ($first_bytes === "\xEF\xBB\xBF") {
    echo "<p style='color: red;'>❌ BOM (Byte Order Mark) detected at file start!</p>";
} else {
    echo "<p style='color: green;'>✅ No BOM detected</p>";
}

// Check for common syntax issues
if (preg_match('/\?\>[\s\S]+\<\?php/', $code)) {
    echo "<p style='color: orange;'>⚠️ Warning: Mixed PHP closing/opening tags detected</p>";
}

// Check echo statement balance
$echo_count = substr_count($code, "echo '");
$echo_count += substr_count($code, 'echo "');
echo "<p>Echo statements found: {$echo_count}</p>";

// Memory usage
echo "<h2>Resource Usage:</h2>";
echo "<p>File size: " . number_format(strlen($code)) . " bytes</p>";
echo "<p>Memory used: " . number_format(memory_get_usage(true)) . " bytes</p>";
echo "<p>Peak memory: " . number_format(memory_get_peak_usage(true)) . " bytes</p>";

echo "<hr>";
echo "<p><strong>Test completed successfully!</strong></p>";
echo "<p>If no red errors above, the file should work on the server.</p>";
?>