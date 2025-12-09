<?php
/**
 * API 직접 테스트
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id; 

header('Content-Type: text/plain; charset=utf-8');

echo "=== API 테스트 시작 ===\n\n";

// 1. 패턴 개수 확인
$pattern_count = $DB->count_records('alt42i_math_patterns');
echo "1. 총 패턴 수: $pattern_count\n";

// 2. 카테고리 확인
$categories = $DB->get_records('alt42i_pattern_categories', null, 'id');
echo "\n2. 카테고리 목록:\n";
foreach ($categories as $cat) {
    echo "   - ID: {$cat->id}, 이름: {$cat->category_name}\n";
}

// 3. 첫 번째 패턴 상세 확인
$first_pattern = $DB->get_record_sql("
    SELECT 
        p.id,
        p.name,
        p.description,
        p.icon,
        p.priority,
        p.audio_time,
        p.category_id,
        c.category_name,
        s.action,
        s.check_method
    FROM {alt42i_math_patterns} p
    JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
    JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
    WHERE p.id = 1
");

echo "\n3. 첫 번째 패턴 정보:\n";
if ($first_pattern) {
    echo "   - ID: {$first_pattern->id}\n";
    echo "   - 이름: {$first_pattern->name}\n";
    echo "   - 아이콘: {$first_pattern->icon}\n";
    echo "   - 카테고리: {$first_pattern->category_name}\n";
    echo "   - 우선순위: {$first_pattern->priority}\n";
} else {
    echo "   패턴을 찾을 수 없습니다.\n";
}

// 4. API 호출 시뮬레이션
echo "\n4. API 응답 형식 (JSON):\n";

// API와 동일한 방식으로 데이터 가져오기
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
    ORDER BY p.id
    LIMIT 3
");

$pattern_data = [];
foreach ($patterns as $pattern) {
    $pattern_data[] = [
        'pattern_id' => (int)$pattern->pattern_id,
        'pattern_name' => $pattern->pattern_name,
        'pattern_desc' => $pattern->pattern_desc,
        'category_id' => (int)$pattern->category_id,
        'category_name' => $pattern->category_name,
        'icon' => $pattern->icon,
        'priority' => $pattern->priority
    ];
}

echo json_encode($pattern_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

echo "\n\n=== 테스트 완료 ===\n";