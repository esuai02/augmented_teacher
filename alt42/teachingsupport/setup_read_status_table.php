<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>읽음 상태 테이블 설정</h2>";

try {
    $dbman = $DB->get_manager();
    
    // 테이블 존재 확인
    if (!$dbman->table_exists('ktm_interaction_read_status')) {
        // 테이블 생성
        $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_interaction_read_status (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            interaction_id BIGINT(10) NOT NULL,
            student_id BIGINT(10) NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timeread BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_interaction_student (interaction_id, student_id),
            INDEX idx_student_id (student_id),
            INDEX idx_interaction_id (interaction_id),
            INDEX idx_is_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->execute($sql);
        echo "<p>✅ ktm_interaction_read_status 테이블이 생성되었습니다.</p>";
    } else {
        echo "<p>ℹ️ ktm_interaction_read_status 테이블이 이미 존재합니다.</p>";
    }
    
    // 테이블 구조 확인
    echo "<h3>테이블 구조</h3>";
    $sql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_interaction_read_status";
    $columns = $DB->get_records_sql($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>필드명</th><th>타입</th><th>Null</th><th>키</th><th>기본값</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->field}</td>";
        echo "<td>{$column->type}</td>";
        echo "<td>{$column->null}</td>";
        echo "<td>{$column->key}</td>";
        echo "<td>{$column->default}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 현재 데이터 수 확인
    $count = $DB->count_records('ktm_interaction_read_status');
    echo "<p>현재 저장된 읽음 상태 레코드 수: <strong>$count</strong></p>";
    
    echo "<h3>✅ 설정 완료</h3>";
    echo "<p>이제 학생들이 강의를 볼 때 자동으로 읽음 처리됩니다.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ 오류 발생</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h3 { color: #2c3e50; margin-top: 20px; }
table { margin: 10px 0; }
th { background: #f8f9fa; padding: 8px; text-align: left; }
td { padding: 8px; border: 1px solid #ddd; }
</style>