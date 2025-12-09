<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

// 관리자 권한 확인
require_login();
require_capability('moodle/site:config', context_system::instance());

// SQL 파일 읽기
$sql_file = __DIR__ . '/create_persona_modes_table.sql';
if (!file_exists($sql_file)) {
    die("SQL 파일을 찾을 수 없습니다: $sql_file");
}

$sql_content = file_get_contents($sql_file);

// SQL 문을 개별 쿼리로 분리 (세미콜론 기준)
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

echo "<h2>SQL 파일 실행: create_persona_modes_table.sql</h2>";
echo "<pre>";

$success_count = 0;
$error_count = 0;

foreach ($queries as $index => $query) {
    // 주석 제거
    $query = preg_replace('/--.*$/m', '', $query);
    $query = trim($query);
    
    if (empty($query)) {
        continue;
    }
    
    echo "\n쿼리 " . ($index + 1) . " 실행 중...\n";
    echo "<div style='background: #f0f0f0; padding: 5px; margin: 5px 0; border-radius: 3px;'>";
    echo htmlspecialchars(substr($query, 0, 100)) . "...\n";
    echo "</div>";
    
    try {
        $DB->execute($query);
        echo "<span style='color: green;'>✓ 성공</span>\n";
        $success_count++;
    } catch (Exception $e) {
        echo "<span style='color: red;'>✗ 실패: " . $e->getMessage() . "</span>\n";
        $error_count++;
    }
}

echo "\n";
echo "=====================================\n";
echo "실행 결과:\n";
echo "성공: $success_count 개\n";
echo "실패: $error_count 개\n";
echo "=====================================\n";

if ($error_count == 0) {
    echo "<strong style='color: green;'>\n모든 테이블이 성공적으로 생성되었습니다!</strong>\n";
} else {
    echo "<strong style='color: orange;'>\n일부 쿼리가 실패했습니다. 테이블이 이미 존재할 수 있습니다.</strong>\n";
}

echo "</pre>";

// 테이블 상태 확인
echo "<h3>현재 테이블 상태</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>테이블명</th><th>상태</th><th>레코드 수</th></tr>";

$tables = ['mdl_persona_modes', 'mdl_message_transformations', 'mdl_chat_messages'];

foreach ($tables as $table) {
    echo "<tr>";
    echo "<td style='padding: 5px;'>$table</td>";
    
    try {
        $count = $DB->count_records_sql("SELECT COUNT(*) FROM $table");
        echo "<td style='padding: 5px; color: green;'>✓ 존재</td>";
        echo "<td style='padding: 5px;'>$count</td>";
    } catch (Exception $e) {
        echo "<td style='padding: 5px; color: red;'>✗ 없음</td>";
        echo "<td style='padding: 5px;'>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";
?>