<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

echo "=== 시스템 통합 테스트 ===\n\n";

// 1. 테이블 존재 확인
echo "1. 테이블 확인:\n";
$tables = ['ktm_teaching_interactions', 'ktm_teaching_events', 'ktm_interaction_read_status'];
foreach ($tables as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "   - {$table}: " . ($exists ? "✓ 존재" : "✗ 없음") . "\n";
}

echo "\n2. 최근 상호작용 (최근 3일):\n";
$recent_time = time() - (3 * 24 * 3600);
$interactions = $DB->get_records_select('ktm_teaching_interactions', 
    'timecreated > ?', 
    array($recent_time), 
    'timecreated DESC', 
    '*', 
    0, 
    10
);

if ($interactions) {
    foreach ($interactions as $int) {
        echo "   ID: {$int->id}\n";
        echo "   - 학생: {$int->userid}\n";
        echo "   - 선생님: " . ($int->teacherid ?: '미지정') . "\n";
        echo "   - 상태: {$int->status}\n";
        echo "   - 문제유형: {$int->problem_type}\n";
        echo "   - 추가요청: " . (!empty($int->modification_prompt) ? '있음' : '없음') . "\n";
        echo "   - 해설: " . (!empty($int->solution_text) ? '있음' : '없음') . "\n";
        echo "   - 시간: " . date('Y-m-d H:i:s', $int->timecreated) . "\n";
        echo "   ---\n";
    }
} else {
    echo "   최근 상호작용이 없습니다.\n";
}

echo "\n3. 대기중인 요청:\n";
$pending = $DB->get_records_select('ktm_teaching_interactions', 
    "(status = 'pending' OR status = 'processing') AND problem_image IS NOT NULL", 
    array(), 
    'timecreated DESC', 
    '*', 
    0, 
    5
);

if ($pending) {
    foreach ($pending as $p) {
        echo "   ID: {$p->id} - 학생: {$p->userid} - 선생님: " . ($p->teacherid ?: '미지정') . "\n";
    }
} else {
    echo "   대기중인 요청이 없습니다.\n";
}

echo "\n4. API 엔드포인트 확인:\n";
$endpoints = [
    'save_interaction.php',
    'get_new_requests.php', 
    'get_student_messages.php',
    'mark_message_read.php'
];

foreach ($endpoints as $endpoint) {
    $path = __DIR__ . '/' . $endpoint;
    echo "   - {$endpoint}: " . (file_exists($path) ? "✓ 존재" : "✗ 없음") . "\n";
}

echo "\n5. 백업된 파일:\n";
$backup = __DIR__ . '/simple_save_interaction.php.backup';
echo "   - simple_save_interaction.php.backup: " . (file_exists($backup) ? "✓ 존재" : "✗ 없음") . "\n";

echo "\n=== 테스트 완료 ===\n";
?>