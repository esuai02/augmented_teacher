<?php
/**
 * get_math_patterns.php API 테스트
 */

// Moodle 설정 포함
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER, $CFG;

// 로그인 확인
require_login();

header('Content-Type: text/plain; charset=utf-8');

echo "=== API 테스트 시작 ===\n\n";

// 1. 테이블 확인
echo "1. 테이블 존재 확인:\n";
$tables = ['alt42i_math_patterns', 'alt42i_pattern_categories', 'alt42i_pattern_solutions'];
foreach ($tables as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "  - {$table}: " . ($exists ? "존재함" : "없음") . "\n";
}

// 2. 데이터 카운트
echo "\n2. 데이터 개수:\n";
try {
    $pattern_count = $DB->count_records('alt42i_math_patterns');
    echo "  - 패턴: {$pattern_count}개\n";
    
    $category_count = $DB->count_records('alt42i_pattern_categories');
    echo "  - 카테고리: {$category_count}개\n";
} catch (Exception $e) {
    echo "  - 오류: " . $e->getMessage() . "\n";
}

// 3. 샘플 패턴 데이터
echo "\n3. 샘플 패턴 (처음 2개):\n";
try {
    $patterns = $DB->get_records('alt42i_math_patterns', null, 'id ASC', '*', 0, 2);
    foreach ($patterns as $p) {
        echo "  - ID: {$p->id}, 이름: {$p->name}\n";
    }
} catch (Exception $e) {
    echo "  - 오류: " . $e->getMessage() . "\n";
}

// 4. API 직접 실행
echo "\n4. API 로직 직접 실행:\n";
try {
    // 카테고리 데이터
    $categories = $DB->get_records('alt42i_pattern_categories', null, 'id ASC');
    echo "  - 카테고리 로드: " . count($categories) . "개\n";
    
    // 패턴 데이터
    $patterns = $DB->get_records_sql("
        SELECT 
            p.id,
            p.id as pattern_id,
            p.name as pattern_name,
            p.description as pattern_desc,
            p.category_id,
            p.icon,
            p.priority,
            p.audio_time,
            c.category_name,
            s.action,
            s.check_method,
            s.audio_script,
            s.teacher_dialog
        FROM {alt42i_math_patterns} p
        JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
        JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
        ORDER BY p.id ASC
        LIMIT 5
    ");
    echo "  - 패턴 로드: " . count($patterns) . "개\n";
    
    if (count($patterns) > 0) {
        $first = reset($patterns);
        echo "  - 첫 번째 패턴: {$first->pattern_name}\n";
    }
    
} catch (Exception $e) {
    echo "  - 오류: " . $e->getMessage() . "\n";
    echo "  - SQL 상태: " . $DB->get_last_error() . "\n";
}

echo "\n=== 테스트 완료 ===\n";
?>