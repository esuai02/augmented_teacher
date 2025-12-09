<?php
/**
 * 테이블 스키마 확인 스크립트
 * created_at 필드의 데이터 타입을 확인합니다
 */

require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $CFG;

require_login();

header('Content-Type: text/html; charset=utf-8');

echo "<h1>테이블 스키마 확인</h1>";

// 확인할 테이블들
$tables = [
    'alt42i_pattern_categories',
    'alt42i_math_patterns',
    'alt42i_pattern_solutions',
    'alt42i_audio_files'
];

foreach ($tables as $table) {
    echo "<h2>{$table} 테이블 구조</h2>";
    
    try {
        // 테이블 구조 조회
        $sql = "SHOW COLUMNS FROM {$CFG->prefix}{$table}";
        $columns = $DB->get_records_sql($sql);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column->field}</td>";
            echo "<td>{$column->type}</td>";
            echo "<td>{$column->null}</td>";
            echo "<td>{$column->key}</td>";
            echo "<td>{$column->default}</td>";
            echo "<td>{$column->extra}</td>";
            echo "</tr>";
            
            // created_at 필드 타입 확인
            if (strpos(strtolower($column->field), 'created') !== false || 
                strpos(strtolower($column->field), 'updated') !== false) {
                echo "<tr style='background-color: #ffffcc;'>";
                echo "<td colspan='6'>⚠️ 타임스탬프 필드 발견: {$column->field} - 타입: {$column->type}</td>";
                echo "</tr>";
            }
        }
        
        echo "</table><br>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>오류: " . $e->getMessage() . "</p>";
    }
}

// Moodle의 타임스탬프 처리 방법 확인
echo "<h2>Moodle 타임스탬프 정보</h2>";
echo "<p>현재 시간 (time()): " . time() . "</p>";
echo "<p>현재 시간 (date): " . date('Y-m-d H:i:s') . "</p>";

// 테스트 데이터로 확인
$test_table = 'alt42i_pattern_categories';
$test_record = $DB->get_record_sql("SELECT * FROM {$CFG->prefix}{$test_table} LIMIT 1");
if ($test_record) {
    echo "<h3>기존 레코드 예시 ({$test_table})</h3>";
    echo "<pre>" . print_r($test_record, true) . "</pre>";
}

?>

<hr>
<p><a href="insert_60_personas_data_safe.php">데이터 삽입 페이지로 돌아가기</a></p>