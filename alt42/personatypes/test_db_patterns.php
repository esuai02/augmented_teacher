<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id; 

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$userid' AND fieldid='22'"); 
$role = $userrole->data ?? 'student';

 

header('Content-Type: text/html; charset=utf-8');

echo "<h2>DB 패턴 데이터 확인</h2>";

// 1. 패턴 개수 확인
$pattern_count = $DB->count_records('alt42i_math_patterns');
echo "<p>총 패턴 수: $pattern_count</p>";

// 2. 샘플 패턴 5개 표시
$patterns = $DB->get_records_sql("
    SELECT 
        p.id,
        p.name,
        p.description,
        p.icon,
        p.category_id,
        c.category_name,
        s.action
    FROM {alt42i_math_patterns} p
    JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
    JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
    ORDER BY p.id
    LIMIT 5
");

echo "<h3>샘플 패턴 데이터:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>이름</th><th>설명</th><th>아이콘</th><th>카테고리</th><th>해결방법</th></tr>";

foreach ($patterns as $pattern) {
    echo "<tr>";
    echo "<td>{$pattern->id}</td>";
    echo "<td>{$pattern->name}</td>";
    echo "<td>" . substr($pattern->description, 0, 50) . "...</td>";
    echo "<td>{$pattern->icon}</td>";
    echo "<td>{$pattern->category_name}</td>";
    echo "<td>" . substr($pattern->action, 0, 50) . "...</td>";
    echo "</tr>";
}

echo "</table>";

// 3. 카테고리 확인
echo "<h3>카테고리 목록:</h3>";
$categories = $DB->get_records('alt42i_pattern_categories', null, 'id');
echo "<ul>";
foreach ($categories as $cat) {
    echo "<li>ID: {$cat->id} - {$cat->category_name}</li>";
}
echo "</ul>";

// 4. 수학인지관성 도감 페이지 링크
echo "<h3>수학인지관성 도감 페이지:</h3>";
echo "<p><a href='index.php'>메인 페이지로 이동</a></p>";
echo "<p>index.php에서 '수학인지관성 도감' 버튼을 클릭하면 패턴 목록을 볼 수 있습니다.</p>";