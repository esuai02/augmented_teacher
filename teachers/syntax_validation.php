<?php
// Syntax validation for timescaffolding42.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Syntax Validation Report</h1>";
echo "<p>Testing timescaffolding42.php for syntax errors...</p>";

// Test 1: Check PHP syntax
echo "<h2>1. PHP Syntax Check</h2>";
$file_path = dirname(__FILE__) . "/timescaffolding42.php";

if (!file_exists($file_path)) {
    echo "❌ File not found: $file_path<br>";
    exit;
}

// Read file content
$content = file_get_contents($file_path);
if ($content === false) {
    echo "❌ Cannot read file<br>";
    exit;
}

echo "✅ File exists and readable<br>";
echo "File size: " . strlen($content) . " bytes<br>";

// Test 2: Check for common syntax issues
echo "<h2>2. Common Syntax Issues Check</h2>";

// Check for unmatched quotes in echo statements
$echo_issues = 0;
$lines = explode("\n", $content);
foreach ($lines as $line_num => $line) {
    if (preg_match('/echo\s+[\'"].*?[^\\\\][\'"].*;?\s*$/', $line) && 
        (substr_count($line, '"') % 2 !== 0 || substr_count($line, "'") % 2 !== 0)) {
        echo "⚠️ Potential quote issue at line " . ($line_num + 1) . ": " . htmlspecialchars(trim($line)) . "<br>";
        $echo_issues++;
    }
}

if ($echo_issues === 0) {
    echo "✅ No obvious quote issues found<br>";
} else {
    echo "❌ Found $echo_issues potential quote issues<br>";
}

// Test 3: Check for unmatched PHP tags
echo "<h2>3. PHP Tag Balance Check</h2>";
$php_open_count = substr_count($content, '<?php');
$php_close_count = substr_count($content, '?>');

echo "PHP open tags: $php_open_count<br>";
echo "PHP close tags: $php_close_count<br>";

if ($php_open_count === $php_close_count) {
    echo "✅ PHP tags are balanced<br>";
} else {
    echo "⚠️ PHP tags may be unbalanced (this could be normal for included files)<br>";
}

// Test 4: Check include statements
echo "<h2>4. Include Statement Check</h2>";
if (strpos($content, 'include_once(dirname(__FILE__) . "/openai_config.php")') !== false) {
    echo "✅ OpenAI config include statement is correct<br>";
} else {
    echo "❌ OpenAI config include statement issue<br>";
}

if (strpos($content, 'include_once("/home/moodle/public_html/moodle/config.php")') !== false) {
    echo "✅ Moodle config include statement found<br>";
} else {
    echo "❌ Moodle config include statement issue<br>";
}

// Test 5: Check critical function definitions
echo "<h2>5. Critical Function Check</h2>";
$functions_to_check = [
    'showConsentModal',
    'generateAnalysisWithCache',
    'generateFeedbackWithCache',
    'showAnalysisResult',
    'saveConsentChoice',
    'loadNotes',
    'addNewNote'
];

foreach ($functions_to_check as $func) {
    if (strpos($content, "function $func") !== false) {
        echo "✅ Function $func found<br>";
    } else {
        echo "⚠️ Function $func not found (may be in separate file)<br>";
    }
}

// Test 6: Final structure validation
echo "<h2>6. File Structure Validation</h2>";

// Check for proper HTML closing
if (strpos($content, "</body>\n</html>") !== false) {
    echo "✅ HTML structure properly closed<br>";
} else {
    echo "❌ HTML structure may not be properly closed<br>";
}

// Check for script tags balance
$script_open = substr_count($content, '<script');
$script_close = substr_count($content, '</script>');

echo "Script open tags: $script_open<br>";
echo "Script close tags: $script_close<br>";

if ($script_open === $script_close) {
    echo "✅ Script tags are balanced<br>";
} else {
    echo "❌ Script tags are unbalanced<br>";
}

echo "<h2>Summary</h2>";
echo "<p>✅ Basic syntax validation completed.</p>";
echo "<p>🚨 <strong>Critical:</strong> This is a static analysis only. For complete validation, the file should be tested on the actual server environment.</p>";
echo "<p>📝 Next step: Test the file on mathking.kr server to verify HTTP 500 error is resolved.</p>";

?>