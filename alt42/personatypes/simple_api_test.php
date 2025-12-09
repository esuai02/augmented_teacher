<?php
/**
 * 간단한 API 테스트
 */

// Moodle 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Simple API Test</h1>";

// 1. 테이블 존재 확인
$tables = ['alt42i_pattern_categories', 'alt42i_math_patterns'];
foreach ($tables as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "<p>Table '$table' exists: " . ($exists ? 'YES' : 'NO') . "</p>";
    
    if ($exists) {
        $count = $DB->count_records($table);
        echo "<p>Records in '$table': $count</p>";
    }
}

// 2. 간단한 패턴 조회
echo "<h2>Pattern Test</h2>";
try {
    $patterns = $DB->get_records('alt42i_math_patterns', null, 'pattern_id ASC', '*', 0, 5);
    echo "<p>Successfully fetched " . count($patterns) . " patterns</p>";
    
    if (!empty($patterns)) {
        echo "<ul>";
        foreach ($patterns as $pattern) {
            echo "<li>#{$pattern->pattern_id}: {$pattern->pattern_name}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

// 3. API 호출 테스트
echo "<h2>API Call Test</h2>";
echo '<button onclick="testAPI()">Test API</button>';
echo '<pre id="api-result"></pre>';

?>

<script>
function testAPI() {
    fetch('api/get_math_patterns.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: 1
        })
    })
    .then(response => response.text())
    .then(text => {
        document.getElementById('api-result').textContent = text;
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
        } catch (e) {
            console.error('Parse error:', e);
        }
    })
    .catch(error => {
        document.getElementById('api-result').textContent = 'Error: ' + error.message;
    });
}
</script>