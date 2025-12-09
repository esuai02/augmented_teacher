<?php
/**
 * Quick fix to ensure math persona data is properly loaded
 * This will check and insert missing data if needed
 */

// Moodle 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Math Persona Display Fix</h1>";

// 1. Check if categories exist
$categories = $DB->get_records('alt42i_pattern_categories');
if (empty($categories)) {
    echo "<p>카테고리가 없습니다. 기본 카테고리 삽입 중...</p>";
    
    $default_categories = [
        ['category_code' => 'cognitive_overload', 'category_name' => '인지 과부하', 'display_order' => 1],
        ['category_code' => 'confidence_distortion', 'category_name' => '자신감 왜곡', 'display_order' => 2],
        ['category_code' => 'mistake_patterns', 'category_name' => '실수 패턴', 'display_order' => 3],
        ['category_code' => 'approach_errors', 'category_name' => '접근 전략 오류', 'display_order' => 4],
        ['category_code' => 'study_habits', 'category_name' => '학습 습관', 'display_order' => 5],
        ['category_code' => 'time_pressure', 'category_name' => '시간/압박 관리', 'display_order' => 6],
        ['category_code' => 'verification_absence', 'category_name' => '검증/확인 부재', 'display_order' => 7],
        ['category_code' => 'other_obstacles', 'category_name' => '기타 장애', 'display_order' => 8]
    ];
    
    foreach ($default_categories as $cat) {
        $cat['created_at'] = time();
        $DB->insert_record('alt42i_pattern_categories', $cat);
    }
    
    echo "<p>✓ 카테고리 삽입 완료</p>";
}

// 2. Check if patterns exist
$patterns = $DB->get_records('alt42i_math_patterns');
if (empty($patterns)) {
    echo "<p style='color: red;'>패턴 데이터가 없습니다!</p>";
    echo "<p>60personas.txt 데이터를 삽입하려면 <a href='insert_60_personas_data.php'>여기를 클릭</a>하세요.</p>";
} else {
    echo "<p>✓ " . count($patterns) . "개의 패턴이 존재합니다.</p>";
}

// 3. Test API directly
echo "<h2>API 직접 테스트</h2>";
echo "<pre>";

// Simulate API call
$api_patterns = $DB->get_records_sql("
    SELECT 
        p.*,
        c.category_name,
        c.category_code
    FROM {alt42i_math_patterns} p
    LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.pattern_id ASC
    LIMIT 5
");

foreach ($api_patterns as $pattern) {
    echo "패턴 #{$pattern->pattern_id}: {$pattern->pattern_name}\n";
    echo "  카테고리: {$pattern->category_name}\n";
    echo "  아이콘: {$pattern->icon}\n";
    echo "  오디오: http://mathking.kr/Contents/personas/mathlearning/thinkinginertia" . str_pad($pattern->pattern_id, 2, '0', STR_PAD_LEFT) . ".mp3\n";
    echo "\n";
}
echo "</pre>";

// 4. Create test links
echo "<h2>테스트 링크</h2>";
echo "<ul>";
echo "<li><a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/check_db_status.php'>데이터베이스 상태 확인</a></li>";
echo "<li><a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/test_math_persona.html'>수학 인지관성 도감 테스트</a></li>";
echo "<li><a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/simple_api_test.php'>API 테스트</a></li>";
echo "</ul>";

?>

<script>
// Test API from JavaScript
console.log('Testing API from JavaScript...');

fetch('api/get_math_patterns.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        user_id: 1
    })
})
.then(response => response.json())
.then(data => {
    console.log('API Response:', data);
    if (data.success && data.patterns.length > 0) {
        document.body.innerHTML += '<p style="color: green;">✓ API가 정상적으로 작동하고 있습니다. ' + data.patterns.length + '개의 패턴을 반환했습니다.</p>';
    } else {
        document.body.innerHTML += '<p style="color: red;">✗ API가 패턴을 반환하지 않았습니다.</p>';
    }
})
.catch(error => {
    console.error('API Error:', error);
    document.body.innerHTML += '<p style="color: red;">✗ API 오류: ' + error.message + '</p>';
});
</script>