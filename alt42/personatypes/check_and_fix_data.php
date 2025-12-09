<?php
/**
 * 데이터 확인 및 수정
 */
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h1>수학 인지관성 데이터 확인 및 수정</h1>";

// 1. 테이블 존재 확인
echo "<h2>1. 테이블 상태</h2>";
$tables = [
    'alt42i_math_patterns' => 0,
    'alt42i_pattern_categories' => 0,
    'alt42i_pattern_solutions' => 0,
    'alt42i_user_pattern_progress' => 0
];

foreach ($tables as $table => &$count) {
    if ($DB->get_manager()->table_exists($table)) {
        $count = $DB->count_records($table);
        echo "<p>✅ {$table}: {$count}개의 레코드</p>";
    } else {
        echo "<p>❌ {$table}: 테이블 없음</p>";
    }
}

// 2. 데이터가 없으면 샘플 데이터 추가
if ($tables['alt42i_math_patterns'] == 0) {
    echo "<h2>2. 샘플 데이터 추가</h2>";
    
    // 카테고리 확인
    if ($tables['alt42i_pattern_categories'] == 0) {
        echo "<p style='color: red;'>카테고리 테이블이 비어있습니다. 먼저 카테고리 데이터를 추가해야 합니다.</p>";
        echo "<p><a href='insert_categories.php'>카테고리 데이터 추가하기</a></p>";
    } else {
        echo "<p>카테고리가 있으므로 패턴 데이터를 추가할 수 있습니다.</p>";
        echo "<p><a href='insert_patterns.php'>패턴 데이터 추가하기</a></p>";
    }
} else {
    echo "<h2>2. 샘플 데이터</h2>";
    
    // 처음 5개 패턴 표시
    $patterns = $DB->get_records_sql("
        SELECT p.*, c.category_name 
        FROM {alt42i_math_patterns} p
        LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
        ORDER BY p.id
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>이름</th><th>카테고리</th><th>아이콘</th><th>우선순위</th></tr>";
    foreach ($patterns as $p) {
        echo "<tr>";
        echo "<td>{$p->id}</td>";
        echo "<td>{$p->name}</td>";
        echo "<td>{$p->category_name}</td>";
        echo "<td>{$p->icon}</td>";
        echo "<td>{$p->priority}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. API 테스트
echo "<h2>3. API 테스트</h2>";
echo "<ul>";
echo "<li><a href='test_api_simple.html'>Simple API Test (HTML)</a></li>";
echo "<li><a href='api/test_get_patterns.php'>Direct API Test (PHP)</a></li>";
echo "<li><a href='show_math_patterns.php'>독립 도감 페이지</a></li>";
echo "<li><a href='index.php'>메인 페이지</a></li>";
echo "</ul>";

// 4. SQL 쿼리 직접 실행
echo "<h2>4. SQL 쿼리 테스트</h2>";
try {
    $test_query = $DB->get_records_sql("
        SELECT COUNT(*) as total FROM {alt42i_math_patterns}
    ");
    $total = reset($test_query)->total;
    echo "<p>전체 패턴 수: {$total}</p>";
    
    // JOIN 쿼리 테스트
    $join_test = $DB->get_records_sql("
        SELECT COUNT(*) as total 
        FROM {alt42i_math_patterns} p
        JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
        JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
    ");
    $join_total = reset($join_test)->total;
    echo "<p>JOIN 가능한 패턴 수: {$join_total}</p>";
    
    if ($join_total == 0 && $total > 0) {
        echo "<p style='color: red;'>⚠️ 패턴은 있지만 카테고리나 솔루션 데이터가 없습니다!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>SQL 오류: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>이 페이지는 데이터 상태를 확인하고 필요시 샘플 데이터를 추가할 수 있도록 도와줍니다.</p>";
?>