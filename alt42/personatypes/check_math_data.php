<?php
/**
 * 수학 인지관성 데이터 확인
 */
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

echo "<h1>수학 인지관성 데이터 확인</h1>";

// 1. 테이블 존재 확인
echo "<h2>1. 테이블 존재 확인</h2>";
$tables = [
    'alt42i_math_patterns' => '패턴 테이블',
    'alt42i_pattern_categories' => '카테고리 테이블',
    'alt42i_pattern_solutions' => '솔루션 테이블',
    'alt42i_user_pattern_progress' => '진행상황 테이블'
];

foreach ($tables as $table => $desc) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "<p>{$desc} ({$table}): " . ($exists ? "✅ 존재" : "❌ 없음") . "</p>";
}

// 2. 데이터 개수 확인
echo "<h2>2. 데이터 개수</h2>";
try {
    $pattern_count = $DB->count_records('alt42i_math_patterns');
    echo "<p>패턴 수: {$pattern_count}개</p>";
    
    $category_count = $DB->count_records('alt42i_pattern_categories');
    echo "<p>카테고리 수: {$category_count}개</p>";
    
    $solution_count = $DB->count_records('alt42i_pattern_solutions');
    echo "<p>솔루션 수: {$solution_count}개</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>데이터 카운트 오류: " . $e->getMessage() . "</p>";
}

// 3. 샘플 데이터
echo "<h2>3. 샘플 패턴 데이터 (처음 3개)</h2>";
try {
    $patterns = $DB->get_records_sql("
        SELECT p.*, c.category_name 
        FROM {alt42i_math_patterns} p
        LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
        ORDER BY p.id
        LIMIT 3
    ");
    
    if ($patterns) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>이름</th><th>설명</th><th>카테고리</th><th>아이콘</th></tr>";
        foreach ($patterns as $p) {
            echo "<tr>";
            echo "<td>{$p->id}</td>";
            echo "<td>{$p->name}</td>";
            echo "<td>" . substr($p->description, 0, 50) . "...</td>";
            echo "<td>{$p->category_name}</td>";
            echo "<td>{$p->icon}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>패턴 데이터가 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>샘플 데이터 로드 오류: " . $e->getMessage() . "</p>";
}

// 4. API 테스트 링크
echo "<h2>4. 추가 테스트</h2>";
echo "<p><a href='test_api_direct.php'>API 직접 테스트</a></p>";
echo "<p><a href='show_math_patterns.php'>수학 인지관성 도감 (독립 페이지)</a></p>";
echo "<p><a href='index.php'>메인 페이지로 돌아가기</a></p>";
?>